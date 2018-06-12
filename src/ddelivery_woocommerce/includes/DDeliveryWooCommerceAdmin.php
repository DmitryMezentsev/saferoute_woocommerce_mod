<?php

require_once 'DDeliveryWooCommerceBase.php';

/**
 * Класс, управляющий отображением плагина в админке
 */
class DDeliveryWooCommerceAdmin extends DDeliveryWooCommerceBase
{
    // Раздел админки, куда будет добавлена страница настроек плагина
    const ADMIN_PARENT_SLUG = 'options-general.php';

    // Уникальное название страницы плагина в разделе настроек
    const ADMIN_MENU_SLUG = 'ddelivery-settings';

    // Имя опции, в которой хранятся уведомления для админки
    const ADMIN_NOTICES_OPTION_NAME = 'ddelivery_admin_notices';


    /**
     * Выводит уведомления в админке
     *
     * @param $push string
     * @param $echo bool
     */
    public static function _notice($push = '', $echo = false)
    {
        if ($push)
        {
            $notices = get_option(self::ADMIN_NOTICES_OPTION_NAME, []);
            $notices[] = $push;
            update_option(self::ADMIN_NOTICES_OPTION_NAME, array_unique($notices));
        }

        if ($echo)
        {
            add_action('admin_init', function () {
                foreach(get_option(self::ADMIN_NOTICES_OPTION_NAME, []) as $text)
                    echo '<div class="notice notice-warning"><p>' . esc_html($text) . '</p></div>';

                delete_option(self::ADMIN_NOTICES_OPTION_NAME);
            });
        }
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
        if (isset($_POST['ddelivery_api_key']))
        {
            update_option(self::API_KEY_OPTION, trim($_POST['ddelivery_api_key']));
            // Перезагрузка страницы, чтобы исчезло уведомление об отсутствии API-ключа
            wp_redirect($_SERVER['REQUEST_URI']);
        }

        // Подключение шаблона страницы
        require self::PLUGIN_DIR_ABS . 'views/admin-settings-page.php';
    }

    /**
     * Создает в админке страницу настроек плагина
     */
    public static function _createAdminSettingsPage()
    {
        add_submenu_page(self::ADMIN_PARENT_SLUG, __('DDelivery'), __('DDelivery'), 8, self::ADMIN_MENU_SLUG, __CLASS__ . '::_adminSettingsPage');
    }

    /**
     * Выводит на страницу заказа блок со ссылкой на связанный заказ в ЛК DDelivery
     */
    public static function _addOrderMetaBox()
    {
        add_action('add_meta_boxes', function () {
            add_meta_box('shop_order_ddelivery_link', __('DDelivery', self::TEXT_DOMAIN), function ($post) {
                $ddelivery_id = get_post_meta($post->ID, self::DDELIVERY_ID_META_KEY, true);
                $in_ddelivery_cabinet = get_post_meta($post->ID, self::IN_DDELIVERY_CABINET_META_KEY, true);

                if ($in_ddelivery_cabinet)
                {
                    echo '<a href="' . self::DDELIVERY_CABINET_URL . 'orders/' . $ddelivery_id . '" target="_blank">';
                    _e('Open order in the DDelivery Cabinet', self::TEXT_DOMAIN);
                    echo '</a>';
                }
                else
                {
                    _e('Order is not in the DDelivery Cabinet', self::TEXT_DOMAIN);
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
            // Вывод сообщения, что для плагина DDelivery WooCommerce необходим WooCommerce
            self::_notice(__('WooCommerce is required for DDelivery WooCommerce plugin.', self::TEXT_DOMAIN));
        }

        // Вывод уведомления, если API-ключ DDelivery не задан в настройках плагина
        if (!self::checkApiKey())
            self::_notice(__('DDelivery API-key not set.', self::TEXT_DOMAIN), true);
    }
}