<?php

class DDeliveryWooCommercePlugin
{
    // Директория плагина
    const PLUGIN_DIR = ABSPATH . 'wp-content/plugins/ddelivery_woocommerce/';

    // Раздел админки, куда будет добавлена страница настроек плагина
    const ADMIN_PARENT_SLUG = 'options-general.php';

    // Уникальное название страницы плагина в разделе настроек
    const ADMIN_MENU_SLUG = 'ddelivery-settings';

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

    /**
     * Выводит сообщение в админке, что WooCommerce должен быть установлен и активирован
     */
    public static function wooCommerceNotFoundNotice()
    {
        $msg = __('WooCommerce is required for DDelivery WooCommerce plugin.', self::TEXT_DOMAIN);
        echo '<div class="notice notice-error"><p>' . esc_html($msg) . '</p></div>';
    }



    /**
     * Вызывается при активации плагина
     */
    public static function activationHook()
    {
        add_option(self::API_KEY_OPTION, '', '', 'no');
    }

    /**
     * Вызывается при удалении плагина
     */
    public static function uninstallHook()
    {
        delete_option(self::API_KEY_OPTION);
    }



    /**
     * Страница настроек плагина в админке
     */
    public static function adminSettingsPage()
    {
        // Сохранение изменений
        if (isset($_POST['ddelivery_api_key']))
        {
            update_option(self::API_KEY_OPTION, trim($_POST['ddelivery_api_key']));
        }

        // Подключение шаблона страницы
        require self::PLUGIN_DIR . 'views/admin-settings-page.php';
    }

    /**
     * Создает в админке страницу настроек плагина
     */
    public static function createAdminSettingsPage()
    {
        add_submenu_page(self::ADMIN_PARENT_SLUG, 'DDelivery', 'DDelivery', 8, self::ADMIN_MENU_SLUG, __CLASS__ . '::adminSettingsPage');
    }



    /**
     *
     */
    public static function init()
    {
        // Загрузка перевода
        load_plugin_textdomain(self::TEXT_DOMAIN, false, basename(self::PLUGIN_DIR) . '/languages/');


    }
}