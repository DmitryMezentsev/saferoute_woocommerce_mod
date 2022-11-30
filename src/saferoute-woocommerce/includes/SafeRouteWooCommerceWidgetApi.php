<?php

require_once 'SafeRouteWooCommerceBase.php';
require_once 'SafeRouteWidgetApi.php';

/**
 * Добавляет в движок API для взаимодействия с корзинным виджетом
 */
class SafeRouteWooCommerceWidgetApi extends SafeRouteWooCommerceBase
{
    const API_PATH = 'saferoute-widget-api';


    /**
     * Возвращает настройки роутов API
     *
     * @return array
     */
    private static function _getApiRoutes(): array
    {
        return [
            'saferoute' => ['_srApi', 'GET, POST'],
            'set-delivery' => ['_setDeliveryApi', 'POST'],
        ];
    }


    /**
     * Сохраняет в сессии параметры выбранной доставки
     *
     * @param $data object
     * @return array
     */
    private static function _setDeliveryApi($data): array
    {
        if (!session_id()) session_start();

        $_SESSION['sr_data'] = $data->get_param('sr_data');
        $_SESSION['pay_method'] = $data->get_param('pay_method');

        return ['status' => 'ok'];
    }

    /**
     * Перенаправляет запрос к серверу SafeRoute
     *
     * @param $data object
     * @return mixed
     */
    private static function _srApi($data)
    {
        $params = $data->get_param('data');
        if (!$params) $params = [];

        $widgetApi = new SafeRouteWidgetApi(get_option(self::SR_TOKEN_OPTION), get_option(self::SR_SHOP_ID_OPTION));

        $widgetApi->setMethod($data->get_method());
        $widgetApi->setData($params);

        $response = $widgetApi->submit($data->get_param('url'));

        // Для ответов API
        if (json_decode($response))
            return json_decode($response);

        // Небольшой костыль для загрузки iframe
        header('Content-type: text/html');
        exit($response);
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
                        return call_user_func_array(__CLASS__ . '::' . $route_params[0], [$data]);
                    },
                    'permission_callback' => '__return_true',
                ]);
            }
        });
    }
}