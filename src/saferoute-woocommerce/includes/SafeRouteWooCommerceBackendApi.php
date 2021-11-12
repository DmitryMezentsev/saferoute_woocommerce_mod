<?php

require_once 'SafeRouteWooCommerceBase.php';

/**
 * Добавляет в движок API для взаимодействия с бэком SafeRoute
 */
class SafeRouteWooCommerceBackendApi extends SafeRouteWooCommerceBase
{
    const API_PATH = 'saferoute-api';


    /**
     * Возвращает настройки роутов API
     *
     * @return array
     */
    private static function _getApiRoutes()
    {
        return [
            'statuses.json'        => ['_statusesApi'          , 'GET'],
            'payment-methods.json' => ['_paymentMethodsApi'    , 'GET'],
            'order-status-update'  => ['_orderStatusUpdateApi' , 'POST'],
            'products'             => ['_productsApi'          , 'GET'],
        ];
    }

    /**
     * Проверяет, совпадает ли переданный токен c токеном, указанным в настройках плагина
     *
     * @param $token string Токен для проверки
     * @return bool
     */
    private static function _checkToken($token)
    {
        return ($token && $token === get_option(self::SR_TOKEN_OPTION));
    }

    /**
     * Выводит список статусов заказов
     *
     * @param $data WP_REST_Request
     * @return array
     */
    public static function _statusesApi(WP_REST_Request $data)
    {
        return wc_get_order_statuses();
    }

    /**
     * Выводит список способов оплаты
     *
     * @param $data WP_REST_Request
     * @return array
     */
    public static function _paymentMethodsApi(WP_REST_Request $data)
    {
        $methods = [];

        foreach(WC()->payment_gateways->get_available_payment_gateways() as $gateway)
            $methods[$gateway->id] = $gateway->title;

        return $methods;
    }

    /**
     * API синхронизации статусов заказов WP со статусами в ЛК SafeRoute
     *
     * @param $data WP_REST_Request
     * @return mixed
     */
    public static function _orderStatusUpdateApi(WP_REST_Request $data)
    {
        // Не передан обязательный параметр 'id'
        if (empty($data['id']))
            return new WP_Error('id_is_required', 'Parameter \'id\' is required', ['status' => 400]);

        // Находим заказ в БД по SafeRoute ID
        $query = new WP_Query([
            'post_type'   => 'shop_order',
            'meta_key'    => self::SAFEROUTE_ID_META_KEY,
            'meta_value'  => $data['id'],
            'post_status' => self::getAllStatuses(),
        ]);

        // Заказ не найден
        if (!$query->posts)
        {
            return new WP_Error('not_found', 'Order \'' . $data['id'] . '\'not found', ['status' => 404]);
        }
        else
        {
            $id = $query->posts[0]->ID;

            // Сохранение ссылки на трекинг
            if (!empty($data['trackUrl']))
                update_post_meta($id, self::TRACKING_URL_META_KEY, $data['trackUrl']);

            // Сохранение трек-номера
            if (!empty($data['trackNumber'])) {
                $old_track_number = get_post_meta($id, self::TRACKING_NUMBER_META_KEY, true);

                if ($old_track_number !== (string) $data['trackNumber']) {
                    update_post_meta($id, self::TRACKING_NUMBER_META_KEY, $data['trackNumber']);
                    // Отправка уведомления покупателю с информацией о присвоенном трек-номере
                    self::sendCustomerEmailNotification($id, 'track_number_updated');
                }
            }

            // Обновление статуса заказа
            if (!empty($data['statusCMS']))
            {
                $wc_order = new WC_Order($id);
                $wc_order->update_status($data['statusCMS']);
                // Отправка уведомления покупателю, когда заказ передан в службу доставки
                if ((int) $data['statusSR'] === self::SUBMITTED_TO_DELIVERY_SERVICE_STATUS_CODE)
                    self::sendCustomerEmailNotification($id, 'submitted_to_delivery_service');
            }

            return ['status' => 'ok'];
        }
    }

    /**
     * Выводит товары для автокомплита на странице заказа в ЛК
     *
     * @param $data WP_REST_Request
     * @return array
     */
    public static function _productsApi(WP_REST_Request $data)
    {
        // Для поиска по частичному совпадению названия товара
        add_filter('woocommerce_product_data_store_cpt_get_products_query', function ($query, $query_vars) {
            if (!empty($query_vars['like_title']))
                $query['s'] = esc_attr($query_vars['like_title']);
            return $query;
        }, 10, 2);

        $products = wc_get_products([
            'virtual'      => false,
            'downloadable' => false,
            'limit'        => 50,
            'visibility'   => 'visible',
            'like_title'   => $data['name'],
            'sku'          => $data['vendorCode'],
        ]);

        return array_map(function ($product) {
            // Вычисление НДС
            $price_including_tax = wc_get_price_including_tax($product);
            $diff = abs($price_including_tax - $product->price);
            $vat = ($diff) ? round(100 / ($product->price / $diff)) : null;

            return [
                'id'         => $product->id,
                'name'       => $product->name,
                'vendorCode' => $product->sku,
                'vat'        => $vat,
                'price'      => (float) $product->price,
                'weight'     => wc_get_weight($product->get_weight(), 'kg'),
                'width'      => (float) wc_get_dimension($product->get_width(), 'cm') ?: null,
                'height'     => (float) wc_get_dimension($product->get_height(), 'cm') ?: null,
                'length'     => (float) wc_get_dimension($product->get_length(), 'cm') ?: null,
            ];
        }, $products);
    }

    /**
     * Обработчик события изменения постов
     *
     * @param $post_id int ID изменяемого поста
     */
    public static function _onPostEdit($post_id)
    {
        $post = get_post($post_id);

        $order_sr_id = get_post_meta($post_id, self::SAFEROUTE_ID_META_KEY, true);
        $order_in_sr_cabinet = get_post_meta($post_id, self::IN_SAFEROUTE_CABINET_META_KEY, true);

        // Только посты, являющиеся заказами WooCommerce, имеющие SafeRoute ID, и ещё не перенесенные в ЛК
        if ($post->post_type === 'shop_order' && $order_sr_id && !$order_in_sr_cabinet)
        {
            $response = self::updateOrderInSafeRoute([
                'id'     => $order_sr_id,
                'status' => $post->post_status,
                'cmsId'  => self::getOrderNumber($post_id),
            ]);

            // Если заказ был перенесен в ЛК
            if (!empty($response['cabinetId']))
            {
                // Устанавливаем соответствующий флаг
                update_post_meta($post_id, self::IN_SAFEROUTE_CABINET_META_KEY, 1);
                // Сохраняем его новый SafeRoute ID
                update_post_meta($post_id, self::SAFEROUTE_ID_META_KEY, $response['cabinetId']);
            }
        }
    }


    public static function init()
    {
        // Проверяем, что WooCommerce установлен и активирован
        if (!self::checkWooCommerce()) return;

        add_action('rest_api_init', function()
        {
            // Добавляет API в /wp-json/
            foreach(self::_getApiRoutes() as $route => $route_params)
            {
                register_rest_route(self::API_PATH, $route, [
                    'methods' => $route_params[1],
                    'callback' => function($request) use ($route_params)
                    {
                        // Проверка токена
                        if (self::_checkToken($request->get_header('token')))
                            // Если токен валиден, вызываем обработчик роута
                            return call_user_func_array(__CLASS__ . '::' . $route_params[0], [$request]);

                        // Вывод ошибки при невалидном токене
                        return new WP_Error('invalid_token', 'Invalid token', ['status' => 401]);
                    },
                    'permission_callback' => '__return_true',
                ]);
            }
        });

        // Ловим событие изменения постов (заказов)
        add_action('edit_post', __CLASS__ . '::_onPostEdit');
    }

    /**
     * Обновляет данные заказа на сервере SafeRoute
     *
     * @param $data array Параметры запроса
     * @return mixed
     */
    public static function updateOrderInSafeRoute(array $data)
    {
        $api = self::SAFEROUTE_API_URL . 'widgets/update-order';

        $res = wp_remote_post($api, [
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . get_option(self::SR_TOKEN_OPTION),
                'Shop-Id' => get_option(self::SR_SHOP_ID_OPTION),
            ],
        ]);

        return ($res['response']['code'] === 200)
            ? json_decode($res['body'], true)
            : null;
    }

    /**
     * Отправляет запрос с подтверждением оформления заказа в CMS
     *
     * @param $checkout_sess_id string ID сессии чекаута
     * @return bool
     */
    public static function confirmOrder($checkout_sess_id)
    {
        $api = self::SAFEROUTE_API_URL . 'widgets/confirm-order';

        $res = wp_remote_post($api, [
            'body' => ['checkoutSessId' => $checkout_sess_id],
            'timeout' => 30,
            'sslverify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . get_option(self::SR_TOKEN_OPTION),
                'Shop-Id' => get_option(self::SR_SHOP_ID_OPTION),
            ],
        ]);

        return ($res['response']['code'] === 200);
    }
}