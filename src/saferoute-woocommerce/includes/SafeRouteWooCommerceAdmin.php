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
     * Подключает к странице редактирования заказа ЛК SafeRoute
     */
    private static function _useCabinetForEdit()
    {
        add_action('current_screen', function () {
            if (get_current_screen()->id === 'shop_order' && get_option(self::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION)) {
                $order_sr_id = (isset($_GET['post'])) ? get_post_meta($_GET['post'], self::SAFEROUTE_ID_META_KEY, true) : '';

                wp_enqueue_script('saferoute-cabinet-api', 'https://cabinet-next.saferoute.ru/api.js');
                wp_enqueue_script('saferoute-admin', plugins_url('assets/admin.js', dirname(__FILE__)), ['jquery']);
                wp_add_inline_script(
                    'saferoute-admin',
                    "const SR_TOKEN = '" . get_option(self::SR_TOKEN_OPTION) . "'; let SR_ORDER_ID = '" . $order_sr_id . "';",
                    'before'
                );

                wp_enqueue_style('saferoute-css', plugins_url('assets/common.css', dirname(__FILE__)));

                wp_localize_script('saferoute-admin', 'myajax', ['url' => admin_url('admin-ajax.php')]);
            }
        });
    }

    /**
     * Возвращает детали доставки SafeRoute из метаданных
     *
     * @param $order_id int|string
     * @return array|null
     */
    private static function _getSRDeliveryDetails($order_id)
    {
        $shipping = array_values(wc_get_order($order_id)->get_items('shipping'));
        if (!$shipping || $shipping[0]->get_method_id() !== self::ID) return null;

        $meta_data = $shipping[0]->get_meta_data();
        if (empty($meta_data)) return null;

        $data = [];

        foreach($meta_data as $meta_item) {
            switch ($meta_item->key) {
                case self::DELIVERY_TYPE_META_KEY:
                    $data[self::DELIVERY_TYPE_META_KEY] = self::getDeliveryType($meta_item->value); break;
                case self::DELIVERY_DAYS_META_KEY:
                    $data[self::DELIVERY_DAYS_META_KEY] = $meta_item->value; break;
                case self::DELIVERY_COMPANY_META_KEY:
                    $data[self::DELIVERY_COMPANY_META_KEY] = $meta_item->value; break;
            }
        }

        return $data;
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
     * Преобразует ключи метаданных с информацией о доставке и код типа доставки в текстовые названия
     *
     * @param $metadata array
     * @return array
     */
    public static function _formatOrderMetaData($metadata)
    {
        foreach($metadata as $metadata_item) {
            switch($metadata_item->key) {
                case self::DELIVERY_TYPE_META_KEY:
                    $metadata_item->display_key = __('Type', self::TEXT_DOMAIN);
                    $metadata_item->display_value = self::getDeliveryType($metadata_item->value);
                    break;
                case self::DELIVERY_DAYS_META_KEY:
                    $metadata_item->display_key = __('Time (days)', self::TEXT_DOMAIN);
                    break;
                case self::DELIVERY_COMPANY_META_KEY:
                    $metadata_item->display_key = __('Company', self::TEXT_DOMAIN);
                    break;
            }
        }

        return $metadata;
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
            update_option(self::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION, $_POST[self::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION]);
            update_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION, $_POST[self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION]);
            update_option(self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION, $_POST[self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION]);
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
            add_meta_box('shop_order_saferoute_link', __('Order tracking', self::TEXT_DOMAIN), function ($post) {
                $saferoute_id = get_post_meta($post->ID, self::SAFEROUTE_ID_META_KEY, true);
                $track_number = get_post_meta($post->ID, self::TRACKING_NUMBER_META_KEY, true);

                echo '<p><a href="' . self::SAFEROUTE_TRACKING_URL . $saferoute_id . '" target="_blank">';
                _e('SafeRoute order tracking', self::TEXT_DOMAIN);
                echo '</a></p>';

                if ($track_number)
                    echo '<p>'. __('Delivery track-number', self::TEXT_DOMAIN) . ': ' . $track_number . '.</p>';
            }, 'shop_order');
        });
    }

    /**
     * Добавляет столбец с деталями доставки SafeRoute в список заказов
     */
    public static function _addDeliveryDetailsColumnInOrders()
    {
        add_filter('manage_edit-shop_order_columns', function ($columns) {
            $reordered_columns = [];

            foreach ($columns as $key => $column) {
                $reordered_columns[$key] = $column;

                // Вставка после поля "Статус"
                if ($key === 'order_status')
                    $reordered_columns['sr-delivery-details'] = __('Delivery details', self::TEXT_DOMAIN);
            }

            return $reordered_columns;
        }, 20);

        add_action('manage_shop_order_posts_custom_column', function ($column, $post_id) {
            if ($column === 'sr-delivery-details') {
                $details = self::_getSRDeliveryDetails($post_id);
                $track_number = get_post_meta($post_id, self::TRACKING_NUMBER_META_KEY, true);

                if ($details || $track_number) {
                    echo '<div style="line-height: 1.3;">';
                    if (!empty($details[self::DELIVERY_TYPE_META_KEY]))
                        echo '<div>' . __('Type', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_TYPE_META_KEY] . '.</div>';
                    if (!empty($details[self::DELIVERY_DAYS_META_KEY]))
                        echo '<div>' . __('Time (days)', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_DAYS_META_KEY] . '.</div>';
                    if (!empty($details[self::DELIVERY_COMPANY_META_KEY]))
                        echo '<div>' . __('Company', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_COMPANY_META_KEY] . '.</div>';
                    if ($track_number)
                        echo '<div>'. __('Delivery track-number', self::TEXT_DOMAIN) . ': ' . $track_number . '.</div>';
                    echo '</div>';
                } else {
                    echo '&mdash;';
                }
            }
        }, 20, 2);
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
            add_filter('woocommerce_order_item_get_formatted_meta_data', [__CLASS__, '_formatOrderMetaData']);
            add_action('load-post.php', __CLASS__ . '::_addOrderMetaBox');
            self::_addDeliveryDetailsColumnInOrders();
        }
        else
        {
            // Сообщение, что для плагина SafeRoute WooCommerce необходим WooCommerce
            self::_pushNotice(__('WooCommerce is required for SafeRoute WooCommerce plugin.', self::TEXT_DOMAIN));
        }

        self::_useCabinetForEdit();

        // Сообщение, что параметры SafeRoute (токен и ID магазина) не заданы в настройках плагина
        if (!self::checkSettings())
            self::_pushNotice(__('SafeRoute settings not set.', self::TEXT_DOMAIN));

        self::_echoNotices();
    }
}