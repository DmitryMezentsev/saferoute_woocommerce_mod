<?php

require_once 'DDeliveryWooCommerceBase.php';

require_once 'DDeliveryWooCommerceShippingMethod.php';
require_once 'DDeliveryWooCommercePaymentMethod.php';

require_once 'DDeliveryWooCommerceAdmin.php';
require_once 'DDeliveryWooCommerceSdkApi.php';
require_once 'DDeliveryWooCommerceWidgetApi.php';

/**
 * Основной класс плагина
 */
final class DDeliveryWooCommerce extends DDeliveryWooCommerceBase
{
    /**
     * Вызывается при активации плагина
     */
    public static function _activationHook()
    {
        add_option(self::API_KEY_OPTION, '', '', 'no');
    }

    /**
     * Вызывается при удалении плагина
     */
    public static function _uninstallHook()
    {
        delete_option(self::API_KEY_OPTION);
    }


    /**
     * Инициализация плагина
     *
     * @param $plugin_file string
     */
    public static function init($plugin_file)
    {
        // Загрузка перевода
        load_plugin_textdomain(self::TEXT_DOMAIN, false, basename(self::PLUGIN_DIR_ABS) . '/languages/');

        register_activation_hook($plugin_file, [__CLASS__, '_activationHook']);
        register_uninstall_hook($plugin_file, [__CLASS__, '_uninstallHook']);

        if (is_admin())
        {
            DDeliveryWooCommerceAdmin::init(plugin_basename($plugin_file));
        }
        else
        {
            DDeliveryWooCommerceWidgetApi::init();

            // Подключение JavaScript-файла с кодом инициализации виджета
            wp_enqueue_script('ddelivery-widget-init', '/' . self::PLUGIN_DIR . 'assets/dd-widget-init.js', ['jquery']);
        }

        DDeliveryWooCommerceSdkApi::init();

        // Добавление в систему способа доставки и способа оплаты DDelivery
        addDDeliveryShippingMethod();
        addDDeliveryPaymentMethod();
    }
}