<?php

/**
 * Функции и константы, общие для всех остальных классов плагина
 */
class SafeRouteWooCommerceBase
{
    const ID = 'saferoute';

    // Имя параметра 'API-ключ' в БД WordPress
    const API_KEY_OPTION = 'saferoute_api_key';

    // Имя мета-параметра SafeRoute ID заказа
    const SAFEROUTE_ID_META_KEY = '_order_saferoute_id';

    // Имя мета-параметра трек-номера заказа
    const TRACKING_NUMBER_META_KEY = 'order_tracking_number';

    // Имя мета-параметра флага переноса заказа в ЛК
    const IN_SAFEROUTE_CABINET_META_KEY = '_order_in_saferoute_cabinet';

    // Имя атрибута со штрих-кодом товара
    const PRODUCT_BARCODE_ATTR_NAME =  'barcode';

    // Text Domain плагина
    const TEXT_DOMAIN = 'saferoute_woocommerce';
    
    // URL ЛК SafeRoute
    const SAFEROUTE_CABINET_URL = 'https://cabinet.saferoute.ru/';

    // URL API SafeRoute
    const SAFEROUTE_API_URL = 'https://api.saferoute.ru/api/';


    /**
     * Ворзвращает путь к директории плагина
     *
     * @return string
     */
    public static function getPluginDir()
    {
        return dirname(dirname(__FILE__));
    }

    /**
     * Проверяет, активирован ли WooCommerce
     *
     * @return bool
     */
    public static function checkWooCommerce()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * Проверяет, сохранен ли в настройках плагина API-ключ
     *
     * @return bool
     */
    public static function checkApiKey()
    {
        return (bool) strlen(get_option(self::API_KEY_OPTION));
    }

    /**
     * Возвращает массив всех возможных статусов поста/заказа (статусы WP + статусы WC)
     *
     * @return array
     */
    public static function getAllStatuses()
    {
        return array_merge(array_keys(wc_get_order_statuses()), array_keys(get_post_statuses()));
    }
}