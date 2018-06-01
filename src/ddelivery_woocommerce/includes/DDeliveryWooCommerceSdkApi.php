<?php

require_once 'DDeliveryWooCommerceBase.php';

/**
 * Добавляет в движок API для взаимодействия с SDK DDelivery
 */
class DDeliveryWooCommerceSdkApi extends DDeliveryWooCommerceBase
{
    const API_PATH = 'ddelivery-api';


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
    private function _checkApiKey($key)
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
     * API синхронизации статусов заказов WP со статусами в ЛК DDelivery
     *
     * @param $data object
     * @return array
     */
    public static function _trafficOrdersApi($data)
    {
        // Не передан обязательный параметр 'id'
        if (!isset($data['id']) || !$data['id'])
            return new WP_Error('id_is_required', 'Parameter \'id\' is required', ['status' => 400]);

        // Находим заказ в БД по DDelivery ID
        $query = new WP_Query([
            'post_type'   => 'shop_order',
            'meta_key'    => self::DDELIVERY_ID_META_KEY,
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
     * @param int $post_id ID изменяемого поста
     */
    public static function _onPostEdit($post_id)
    {
        $post = get_post($post_id);

        $order_dd_id = get_post_meta($post_id, self::DDELIVERY_ID_META_KEY, true);
        $order_in_dd_cabinet = get_post_meta($post_id, self::IN_DDELIVERY_CABINET_META_KEY, true);

        // Только посты, являющиеся заказами WooCommerce, имеющие DDelivery ID, и ещё не перенесенные в ЛК
        if ($post->post_type === 'shop_order' && $order_dd_id && !$order_in_dd_cabinet)
        {
            $response = self::updateOrderInDDelivery([
                'id'     => $order_dd_id,
                'status' => $post_id->post_status,
                'cms_id' => $post_id,
            ]);

            if ($response['status'] === 'ok')
            {
                // Если заказ был перенесен в ЛК
                if (isset($response['data']['cabinet_id']))
                    // Устанавливаем соответствующий флаг
                    update_post_meta($post_id, self::IN_DDELIVERY_CABINET_META_KEY, 1);
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
     * Обновляет данные заказа на сервере DDelivery
     *
     * @param array Параметры запроса
     */
    public static function updateOrderInDDelivery($data)
    {
        $api = 'https://ddelivery.ru/api/' . get_option(self::API_KEY_OPTION) . '/sdk/update-order.json';

        $curl = curl_init($api);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);

        return $response;
    }
}