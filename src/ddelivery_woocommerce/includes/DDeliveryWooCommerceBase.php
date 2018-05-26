<?php

/**
 * Класс с функционалом и константами, общими для всех остальных классов плагина
 */
class DDeliveryWooCommerceBase
{
    // Директория плагина
    const PLUGIN_DIR = ABSPATH . 'wp-content/plugins/ddelivery_woocommerce/';

    // Имя параметра 'API-ключ' в БД WordPress
    const API_KEY_OPTION = 'ddelivery_api_key';

    // Text Domain плагина
    const TEXT_DOMAIN = 'ddelivery_woocommerce';


    /**
     * Проверяет, активирован ли WooCommerce
     */
    public static function checkWooCommerce()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
}