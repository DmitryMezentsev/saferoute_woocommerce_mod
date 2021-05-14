<?php

require_once 'SafeRouteWooCommerceBase.php';

/**
 * Класс, управляющий отображением плагина в админке
 */
class SafeRouteWooCommerceAdmin extends SafeRouteWooCommerceBase
{
    // Раздел админки, куда будет добавлена страница настроек плагина
    const ADMIN_PARENT_SLUG = 'options-general.php';

    // Уникальное название страницы плагина в разделе настроек
    const ADMIN_MENU_SLUG = 'saferoute-settings';

    // Имя опции, в которой хранятся уведомления для админки
    const ADMIN_NOTICES_OPTION_NAME = 'saferoute_admin_notices';


    /**
     * Удаляет из строки настроек лишние символы
     *
     * @param $value string
     * @return string
     */
    private static function clearOptionValue($value)
    {
        return sanitize_text_field(preg_replace('/["\'\\\<>]/', '', $value));
    }


    /**
     * Добавляет уведомление в стэк уведомлений
     *
     * @param $text string Текст уведомления
     */
    public static function _pushNotice($text)
    {
        $notices = get_option(self::ADMIN_NOTICES_OPTION_NAME, []);
        $notices[] = $text;

        update_option(self::ADMIN_NOTICES_OPTION_NAME, array_unique($notices));
    }

    /**
     * Выводит все уведомления из стэка
     */
    public static function _echoNotices()
    {
        add_action('admin_init', function () {
            foreach(get_option(self::ADMIN_NOTICES_OPTION_NAME, []) as $text)
                echo '<div class="notice notice-warning"><p>' . esc_html($text) . '</p></div>';

            delete_option(self::ADMIN_NOTICES_OPTION_NAME);
        });
    }

    /**
     * Добавляет ссылку на страницу настроек плагина
     *
     * @param $links array
     * @return array
     */
    public static function _addSettingsLink(array $links)
    {
        $links[] = '<a href="' . self::ADMIN_PARENT_SLUG . '?page=' . self::ADMIN_MENU_SLUG . '">' . __('Settings') . '</a>';
        return $links;
    }

    /**
     * Страница настроек плагина в админке
     */
    public static function _adminSettingsPage()
    {
        // Сохранение изменений
        if (isset($_POST[self::SR_TOKEN_OPTION]) && isset($_POST[self::SR_SHOP_ID_OPTION]) && wp_verify_nonce($_POST['_nonce'], 'sr_settings_save'))
        {
            update_option(self::SR_TOKEN_OPTION, self::clearOptionValue($_POST[self::SR_TOKEN_OPTION]));
            update_option(self::SR_SHOP_ID_OPTION, self::clearOptionValue($_POST[self::SR_SHOP_ID_OPTION]));
            update_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION, $_POST[self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION]);
            update_option(self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION, $_POST[self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION]);
            update_option(self::SR_ORDER_CONFIRMATION_STATUS_OPTION, $_POST[self::SR_ORDER_CONFIRMATION_STATUS_OPTION]);
            // Перезагрузка страницы, чтобы исчезло уведомление об отсутствии настроек
            wp_redirect($_SERVER['REQUEST_URI']);
        }

        // Подключение шаблона страницы
        require self::getPluginDir() . '/views/admin-settings-page.php';
    }

    /**
     * Создает в админке страницу настроек плагина
     */
    public static function _createAdminSettingsPage()
    {
        add_submenu_page(self::ADMIN_PARENT_SLUG, __('SafeRoute'), __('SafeRoute'), 'administrator', self::ADMIN_MENU_SLUG, __CLASS__ . '::_adminSettingsPage');
    }

    /**
     * Выводит на страницу заказа блок со ссылкой на связанный заказ в ЛК SafeRoute
     */
    public static function _addOrderMetaBox()
    {
        add_action('add_meta_boxes', function () {
            add_meta_box('shop_order_saferoute_link', __('SafeRoute', self::TEXT_DOMAIN), function ($post) {
                $saferoute_id = get_post_meta($post->ID, self::SAFEROUTE_ID_META_KEY, true);

                echo '<a href="' . self::SAFEROUTE_TRACKING_URL . $saferoute_id . '" target="_blank">';
                _e('Order Tracking', self::TEXT_DOMAIN);
                echo '</a>';
            }, 'shop_order');
        });
    }

    /**
     * @param $plugin_basename string
     */
    public static function init($plugin_basename)
    {
        // Проверка, что WooCommerce установлен и активирован
        if (self::checkWooCommerce())
        {
            add_action('admin_menu', __CLASS__ . '::_createAdminSettingsPage');
            add_filter('plugin_action_links_' . $plugin_basename, [__CLASS__, '_addSettingsLink']);
            add_action('load-post.php', __CLASS__ . '::_addOrderMetaBox');
        }
        else
        {
            // Сообщение, что для плагина SafeRoute WooCommerce необходим WooCommerce
            self::_pushNotice(__('WooCommerce is required for SafeRoute WooCommerce plugin.', self::TEXT_DOMAIN));
        }

        // Сообщение, параметры SafeRoute (токен и ID магазина) не заданы в настройках плагина
        if (!self::checkSettings())
            self::_pushNotice(__('SafeRoute settings not set.', self::TEXT_DOMAIN));

        self::_echoNotices();
    }
}