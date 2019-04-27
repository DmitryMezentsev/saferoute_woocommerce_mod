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
     * @param array
     * @return array
     */
    public static function _addSettingsLink($links)
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
        if (isset($_POST['saferoute_api_key']) && wp_verify_nonce($_POST['_nonce'], 'sr_settings_save'))
        {
            update_option(self::API_KEY_OPTION, sanitize_key($_POST['saferoute_api_key']));
            // Перезагрузка страницы, чтобы исчезло уведомление об отсутствии API-ключа
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
                $in_saferoute_cabinet = get_post_meta($post->ID, self::IN_SAFEROUTE_CABINET_META_KEY, true);

                if ($in_saferoute_cabinet)
                {
                    echo '<a href="' . self::SAFEROUTE_CABINET_URL . 'orders/' . $saferoute_id . '" target="_blank">';
                    _e('Open order in the SafeRoute Cabinet', self::TEXT_DOMAIN);
                    echo '</a>';
                }
                else
                {
                    _e('Order is not in the SafeRoute Cabinet', self::TEXT_DOMAIN);
                }
            }, 'shop_order');
        });
    }


    /**
     * @param $plugin_basename string
     */
    public static function init($plugin_basename)
    {
        // Проверяем, что WooCommerce установлен и активирован
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

        // Сообщение, что API-ключ SafeRoute не задан в настройках плагина
        if (!self::checkApiKey())
            self::_pushNotice(__('SafeRoute API-key not set.', self::TEXT_DOMAIN));

        self::_echoNotices();
    }
}