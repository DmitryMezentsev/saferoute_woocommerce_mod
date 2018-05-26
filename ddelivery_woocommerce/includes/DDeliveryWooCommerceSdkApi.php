<?php

require_once 'DDeliveryWooCommerceBase.php';

/**
 * Класс, добавляющий в движок API для взаимодействия с SDK DDelivery
 */
class DDeliveryWooCommerceSdkApi extends DDeliveryWooCommerceBase
{
    const API_PATH = 'ddelivery-api';


    /**
     * Возвращает настройки роутов API
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
     * @return mixed
     */
    private function checkApiKey($key)
    {
        return ($key && $key === get_option(DDeliveryWooCommerce::API_KEY_OPTION));
    }

    /**
     * Выводит список статусов заказов
     *
     * @param $data object
     * @return array
     */
    public static function _statusesApi($data)
    {
        return [];
    }

    /**
     * Выводит список способов оплаты
     *
     * @param $data object
     * @return array
     */
    public static function _paymentMethodsApi($data)
    {
        return [];
    }

    /**
     * API синхронизации статусов заказов WP со статусами в ЛК DDelivery
     *
     * @param $data object
     * @return array
     */
    public static function _trafficOrdersApi($data)
    {
        return [];
    }


    public static function init()
    {
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
                        if (self::checkApiKey($data['k']))
                            // Если ключ валиден, вызываем обработчик роута
                            return call_user_func_array(__CLASS__ . '::' . $route_params[0], [$data]);

                        // Вывод ошибки при невалидном API-ключе
                        return new WP_Error('invalid_api_key', 'Invalid API-key', ['status' => 401]);
                    },
                ]);
            }
        });
    }
}