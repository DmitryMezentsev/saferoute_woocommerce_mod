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
    private static function _getApiRoutes(): array
    {
        return [
            'order-status-update'      => ['_orderStatusUpdateApi'     , 'POST'],
            'product-quantity-update'  => ['_productQuantityUpdateApi' , 'POST'],
            'products'                 => ['_productsApi'              , 'GET'],
        ];
    }

    /**
     * Проверяет, совпадает ли переданный токен c токеном, указанным в настройках плагина
     *
     * @param $token string Токен для проверки
     * @return bool
     */
    private static function _checkToken(string $token): bool
    {
        return ($token && $token === get_option(self::SR_TOKEN_OPTION));
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
            return new WP_Error('id_is_required', "Parameter 'id' is required", ['status' => 400]);

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
            return new WP_Error('not_found', "Order $data[id] not found", ['status' => 404]);
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
            if (!empty($data['statusSR']))
            {
                $wc_order = new WC_Order($id);
                $status_wc = self::getMatchedWCStatus($data['statusSR']);

                if ($status_wc) $wc_order->update_status($status_wc);

                // Отправка уведомления покупателю, когда заказ передан в службу доставки
                if ((int) $data['statusSR'] === self::SUBMITTED_TO_DELIVERY_SERVICE_STATUS_CODE)
                    self::sendCustomerEmailNotification($id, 'submitted_to_delivery_service');
            }

            return ['status' => 'ok'];
        }
    }

    /**
     * API синхронизации остатков товара
     *
     * @param $data WP_REST_Request
     * @return mixed
     */
    public static function _productQuantityUpdateApi(WP_REST_Request $data)
    {
        // Не передан обязательный параметр 'vendorCode'
        if (empty($data['vendorCode']))
            return new WP_Error('vendor_code_is_required', "Parameter 'vendorCode' is required", ['status' => 400]);

        // Не передан обязательный параметр 'quantity'
        if (!isset($data['quantity']))
            return new WP_Error('quantity_is_required', "Parameter 'quantity' is required", ['status' => 400]);

        $quantity = (int) $data['quantity'];

        // Передано некорректное количество остатков
        if (!is_numeric($data['quantity']) || $quantity < 0)
            return new WP_Error('invalid_quantity', "Parameter 'quantity' is invalid", ['status' => 400]);

        $product_id = wc_get_product_id_by_sku($data['vendorCode']);

        // Товар по переданному артикулу не найден
        if (!$product_id)
            return new WP_Error('not_found', "Product with SKU '$data[vendorCode]' not found", ['status' => 404]);

        wc_update_product_stock($product_id, $quantity);

        return ['status' => 'ok'];
    }

    /**
     * Выводит товары для автокомплита на странице заказа в ЛК
     *
     * @param $data WP_REST_Request
     * @return array
     */
    public static function _productsApi(WP_REST_Request $data): array
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

            $dimensions = self::getProductDimensions($product);

            return [
                'id'         => $product->id,
                'name'       => $product->name,
                'vendorCode' => $product->sku,
                'vat'        => $vat,
                'price'      => (float) $product->price,
                'weight'     => wc_get_weight($product->get_weight(), 'kg'),
                'width'      => $dimensions['width'],
                'height'     => $dimensions['height'],
                'length'     => $dimensions['length'],
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
        $order = wc_get_order($post_id);

        $order_sr_id = get_post_meta($post_id, self::SAFEROUTE_ID_META_KEY, true);
        $sr_data = get_post_meta($post_id, self::WIDGET_ORDER_DATA, true);

        if (!$sr_data) return;

        // Только посты, являющиеся заказами WooCommerce, и в которых не выбран собственный вариант доставки SafeRoute
        if ($post->post_type === 'shop_order' && empty($sr_data['delivery']['isMyDelivery']))
        {
            // Если заказ не имеет SafeRoute ID (т.е. не перенесён в ЛК), для него была выбрана доставка SR и ему присвоен статус для передачи в ЛК
            if (!$order_sr_id && $order->has_shipping_method(self::ID) && $post->post_status === get_option(self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION))
            {
                // Создание заказа в ЛК SafeRoute
                self::createOrderInSafeRoute($post_id);
            }
            // Если заказ имеет SafeRoute ID и он либо был отменён в WooCommerce, либо ему присвоена другая доставка, не SafeRoute
            elseif ($order_sr_id && ($post->post_status === self::ORDER_CANCELLED_STATUS || !$order->has_shipping_method(self::ID)))
            {
                // Отмена заказа в SafeRoute
                self::cancelOrderInSafeRoute($post_id);
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
}