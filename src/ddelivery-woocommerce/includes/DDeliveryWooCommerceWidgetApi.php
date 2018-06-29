<?php

require_once 'DDeliveryWooCommerceBase.php';
require_once 'DDeliveryWidgetApi.php';

/**
 * Добавляет в движок API для взаимодействия с корзинным виджетом
 */
class DDeliveryWooCommerceWidgetApi extends DDeliveryWooCommerceBase
{
    const API_PATH = 'ddelivery-widget-api';


    /**
     * Возвращает настройки роутов API
     *
     * @return array
     */
    private static function _getApiRoutes()
    {
        return [
            'sdk' => ['_sdkApi', 'GET, POST'],
            'set-shipping-cost' => ['_setShippingCostApi', 'POST'],
        ];
    }
    
    
    /**
     * Назначает стоимость доставки DDelivery
     * 
     * @param $data object
     * @return array
     */
    private static function _setShippingCostApi($data)
    {
        if (!session_id()) session_start();
        $_SESSION['ddelivery_shipping_cost'] = $data->get_param('cost');
        
        // Сброс кэшированного значения
        WC()->session->set('shipping_for_package_0', null);
        
        return ['status' => 'ok'];
    }
    
    /**
     * Перенаправляет запрос к API SDK
     * 
     * @param $data object
     * @return mixed
     */
    private static function _sdkApi($data)
    {
        $params = $data->get_param('data');
        if (!$params) $params = [];
        
        $widgetApi = new DDeliveryWidgetApi();
        
        $widgetApi->setApiKey(get_option(self::API_KEY_OPTION));
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
                ]);
            }
        });
    }
}