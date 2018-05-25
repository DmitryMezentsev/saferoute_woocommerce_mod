<?php
/**
 * Plugin name: DDelivery WooCommerce
 * Description: DDelivery Widget for WooCommerce
 * Version: 1.0.0
 * Author: Dmitry Mezentsev
 * Plugin URI: https://ddelivery.ru/integration
 * Text Domain: ddelivery_woocommerce
 * Domain Path: /languages/
 * License: GPLv2 or later
 */



// Exit if accessed directly
if (!defined('ABSPATH')) exit;



require_once 'includes/DDeliveryWooCommercePlugin.php';



register_activation_hook(__FILE__, ['DDeliveryWooCommercePlugin', 'activationHook']);
register_uninstall_hook(__FILE__, ['DDeliveryWooCommercePlugin', 'uninstallHook']);



// Проверяем, что WooCommerce установлен и активирован
if (DDeliveryWooCommercePlugin::checkWooCommerce())
{
    add_action('admin_menu', 'DDeliveryWooCommercePlugin::createAdminSettingsPage');
    add_action('init', 'DDeliveryWooCommercePlugin::init');
}
else
{
    // Вывод сообщения, что для плагина DDelivery WooCommerce необходим WooCommerce
    add_action('admin_notices', 'DDeliveryWooCommercePlugin::wooCommerceNotFoundNotice');
}