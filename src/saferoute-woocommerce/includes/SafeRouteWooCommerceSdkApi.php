<?php

require_once 'SafeRouteWooCommerceBase.php';

/**
 * Добавляет в движок API для взаимодействия с SDK SafeRoute
 */
class SafeRouteWooCommerceSdkApi extends SafeRouteWooCommerceBase
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
            'statuses.json'        => ['_statusesApi'       , 'GET'],
            'payment-methods.json' => ['_paymentMethodsApi' , 'GET'],
            'traffic-orders.json'  => ['_trafficOrdersApi'  , 'POST'],
        ];
    }

    /**
     * Проверяет, совпадает ли переданный API-ключ c API-ключом, указанным в настройках плагина
     *
     * @param $key string API-ключ для проверки
     * @return bool
     */
    private static function _checkApiKey($key)
    {
        return ($key && $key === get_option(self::API_KEY_OPTION));
    }

    /**
     * Выводит список статусов заказов
     *
     * @param $data object
     * @return array
     */
    public static function _statusesApi($data)
    {
        return wc_get_order_statuses();
    }

    /**
     * Выводит список способов оплаты
     *
     * @param $data object
     * @return array
     */
    public static function _paymentMethodsApi($data)
    {
        $methods = [];

        foreach(WC()->payment_gateways->get_available_payment_gateways() as $gateway)
            $methods[$gateway->id] = $gateway->title;

        return $methods;
    }

    /**
     * API синхронизации статусов заказов WP со статусами в ЛК SafeRoute
     *
     * @param $data object
     * @return mixed
     */
    public static function _trafficOrdersApi($data)
    {
        // Не передан обязательный параметр 'id'
        if (!isset($data['id']) || !$data['id'])
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

            // Сохранение трек-номера
            if (isset($data['track_number']))
                update_post_meta($id, self::TRACKING_NUMBER_META_KEY, $data['track_number']);

            // Обновление статуса заказа
            if (isset($data['status_cms']))
                wp_update_post(['ID' => $id, 'post_status' => $data['status_cms']]);

            return ['status' => 'ok'];
        }
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
                'cms_id' => $post_id,
            ]);
            
            if ($response['status'] === 'ok')
            {
                // Если заказ был перенесен в ЛК
                if (isset($response['data']['cabinet_id']))
                {
                    // Устанавливаем соответствующий флаг
                    update_post_meta($post_id, self::IN_SAFEROUTE_CABINET_META_KEY, 1);
                    // Сохраняем его новый SafeRoute ID
                    update_post_meta($post_id, self::SAFEROUTE_ID_META_KEY, $response['data']['cabinet_id']);
                }
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
                    'callback' => function($data) use ($route_params)
                    {
                        // Проверка API-ключа
                        if (self::_checkApiKey($data['k']))
                            // Если ключ валиден, вызываем обработчик роута
                            return call_user_func_array(__CLASS__ . '::' . $route_params[0], [$data]);

                        // Вывод ошибки при невалидном API-ключе
                        return new WP_Error('invalid_api_key', 'Invalid API-key', ['status' => 401]);
                    },
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
        $api = self::SAFEROUTE_API_URL . get_option(self::API_KEY_OPTION) . '/sdk/update-order.json';

        $res = wp_remote_post($api, [
            'body' => $data,
            'timeout' => 30,
        ]);

        return json_decode($res['body'], true);
    }
}