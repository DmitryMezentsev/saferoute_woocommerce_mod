<?php

require_once 'SafeRouteWooCommerceBase.php';
require_once 'SafeRouteWooCommerceCountries.php';

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
    private static function clearOptionValue(string $value): string
    {
        return sanitize_text_field(preg_replace('/["\'\\\<>]/', '', $value));
    }

    /**
     * Загружает список статусов заказа в SafeRoute
     *
     * @return array|false|null
     */
    private static function loadSRStatuses()
    {
        $token   = get_option(self::SR_TOKEN_OPTION);
        $shop_id = get_option(self::SR_SHOP_ID_OPTION);

        if (!$token || !$shop_id) return false;

        $res = wp_remote_get(self::SAFEROUTE_API_URL . 'lists/order-statuses?lang=' . self::getCurrentLang(), [
            'timeout' => 20,
            'headers' => ['Authorization' => "Bearer $token", 'Shop-Id' => $shop_id],
            'sslverify' => false,
        ]);
        $body = json_decode($res['body'], true);

        // Ошибка авторизации
        if ($res['response']['code'] === 401 || ($res['response']['code'] === 400 && $body['code'] === self::INVALID_SHOP_ID_ERROR_CODE))
            return false;

        return $res['response']['code'] === 200 ? $body : null;
    }

    /**
     * Добавляет уведомление в стэк уведомлений
     *
     * @param $text string Текст уведомления
     */
    public static function _pushNotice(string $text)
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
        add_action('admin_notices', function () {
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
    public static function _addSettingsLink(array $links): array
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
    public static function _formatOrderMetaData(array $metadata): array
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
                case self::DELIVERY_POINT_ID_META_KEY:
                    $metadata_item->display_key = __('Pickup point ID', self::TEXT_DOMAIN);
                    break;
                case self::DELIVERY_POINT_ADDRESS_META_KEY:
                    $metadata_item->display_key = __('Pickup point address', self::TEXT_DOMAIN);
                    break;
            }
        }

        return $metadata;
    }

    /**
     * Выводит ошибку SafeRoute (при её наличии) на странице заказа
     */
    public static function _showOrderError()
    {
        echo isset($_GET['post']) ? self::getErrorBlock($_GET['post']) : '';
    }

    /**
     * Страница настроек плагина в админке
     */
    public static function _adminSettingsPage()
    {
        // Сохранение изменений
        if (isset($_POST[self::SR_TOKEN_OPTION]) && isset($_POST[self::SR_SHOP_ID_OPTION]) && wp_verify_nonce($_POST['_nonce'], 'sr_settings_save'))
        {
            $price_declared_percent = $_POST[self::PRICE_DECLARED_PERCENT_OPTION];
            if ($price_declared_percent < 0) $price_declared_percent = 0;
            elseif ($price_declared_percent > 100) $price_declared_percent = 100;

            // Настройки авторизации
            update_option(self::SR_TOKEN_OPTION, self::clearOptionValue($_POST[self::SR_TOKEN_OPTION]));
            update_option(self::SR_SHOP_ID_OPTION, self::clearOptionValue($_POST[self::SR_SHOP_ID_OPTION]));
            // Общие настройки
            update_option(self::PRICE_DECLARED_PERCENT_OPTION, $price_declared_percent);
            update_option(self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION, $_POST[self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION]);
            update_option(self::SEND_ORDERS_AS_CONFIRMED_OPTION, $_POST[self::SEND_ORDERS_AS_CONFIRMED_OPTION]);
            update_option(self::COD_PAY_METHOD_OPTION, $_POST[self::COD_PAY_METHOD_OPTION]);
            update_option(self::CARD_COD_PAY_METHOD_OPTION, $_POST[self::CARD_COD_PAY_METHOD_OPTION]);
            update_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION, $_POST[self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION]);
            update_option(self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION, $_POST[self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION]);
            update_option(self::DISABLE_AUTOSCROLL_TO_WIDGET, $_POST[self::DISABLE_AUTOSCROLL_TO_WIDGET]);
            update_option(self::WIDGET_PLACEMENT_IN_CHECKOUT, $_POST[self::WIDGET_PLACEMENT_IN_CHECKOUT]);
            // Соответствие статусов
            if (is_array($_POST[self::STATUSES_MATCHING_OPTION]))
                update_option(self::STATUSES_MATCHING_OPTION, $_POST[self::STATUSES_MATCHING_OPTION]);

            // Перезагрузка страницы, чтобы исчезло уведомление об отсутствии настроек
            wp_redirect($_SERVER['REQUEST_URI']);
        }

        wp_enqueue_script('saferoute-settings-page', plugins_url('assets/admin-settings-page.js', dirname(__FILE__)), ['jquery']);

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
                $track_url = get_post_meta($post->ID, self::TRACKING_URL_META_KEY, true);

                if ($saferoute_id)
                {
                    echo '<p><a href="' . self::SAFEROUTE_TRACKING_URL . $saferoute_id . '" target="_blank">';
                    _e('SafeRoute order tracking', self::TEXT_DOMAIN);
                    echo '</a></p>';
                }

                if ($track_url)
                    echo '<p>' . __('Delivery track-number', self::TEXT_DOMAIN) . ': ' . "<a href='$track_url' target='_blank'>$track_number</a>.</p>";
                elseif ($track_number)
                    echo '<p>'. __('Delivery track-number', self::TEXT_DOMAIN) . ': ' . $track_number . '.</p>';

                if (!$saferoute_id && !$track_url && !$track_number) _e('Unavailable', self::TEXT_DOMAIN);
            }, 'shop_order');
        });
    }

    /**
     * Выводит в блок выбранной доставки SafeRoute кнопку запуска виджета, а также различную информацию, связанную
     * с выбором доставки в виджете
     *
     * @param $item_id int
     * @param $item WC_Order_Item_Shipping|WC_Order_Item_Product
     */
    public static function _addWidgetBlock($item_id, $item)
    {
        if (is_a($item, 'WC_Order_Item_Shipping') && $item->get_method_id() === self::ID)
        {
            $order = wc_get_order(isset($_POST['order_id']) ? $_POST['order_id'] : false);

            // Если заказ отменён, ничего не выводим
            if (get_post($order->get_id())->post_status === self::ORDER_CANCELLED_STATUS) return;

            $order_in_sr = get_post_meta($order->get_id(), self::SAFEROUTE_ID_META_KEY, true);
            $widget_order_data = get_post_meta($order->get_id(), self::WIDGET_ORDER_DATA, true);

            echo '<div class="widget-block">';
            echo '<button class="button button-primary" type="button" onclick="openWidget()" ' . ($order_in_sr ? 'disabled' : '') . '>';
            echo __('Change delivery', self::TEXT_DOMAIN);
            echo '</button>';

            if (!$order_in_sr && !$widget_order_data)
                echo '<b>' . __('Select a delivery by clicking "Change delivery"', self::TEXT_DOMAIN) . '</b>';

            if ($order_in_sr)
                echo '<div class="muted-msg">' . __('Order already send in SafeRoute, change delivery is impossible', self::TEXT_DOMAIN) . '</div>';

            echo '</div>';
        }
    }

    /**
     * Вызывается при запуске пересчёта заказа
     *
     * @param $and_taxes bool
     * @param $order WC_Order object
     */
    public static function _onRecalculateOrder($and_taxes, $order)
    {
        // Если у заказа доставка не SafeRoute, ничего не делаем
        if (!$order->has_shipping_method(self::ID)) return;

        $id = $order->get_id();

        $sr_order_id = (int) get_post_meta($id, self::SAFEROUTE_ID_META_KEY, true);

        // Если заказ уже попал в SafeRoute, ничего не делаем
        if ($sr_order_id) return;

        $widget_order_data = get_post_meta($id, self::WIDGET_ORDER_DATA, true);

        // Если нет данных по доставке из виджета, ничего не делаем
        if (!$widget_order_data) return;

        $price_declared_percent = get_option(self::PRICE_DECLARED_PERCENT_OPTION);

        $res = wp_remote_post(self::SAFEROUTE_API_URL . 'calculator/one', [
            'body' => [
                'reception' => [
                    'countryCode' => $widget_order_data['city']['countryIsoCode'],
                    'cityFias'    => $widget_order_data['city']['fias'],
                    'cityName'    => $widget_order_data['city']['name'],
                    'cityType'    => $widget_order_data['city']['type'],
                    'zipCode'     => $order->shipping_postcode,
                    'region'      => $widget_order_data['city']['region'],
                ],
                'products' => array_map(function ($product) use ($price_declared_percent) {
                    return [
                        'vendorCode' => $product['vendorCode'],
                        'priceDeclared' => $product['price'] * $price_declared_percent / 100,
                        'priceCod' => $product['price'],
                        'discount' => $product['discount'],
                        'dimensions' => [
                            'width'  => $product['width'],
                            'height' => $product['height'],
                            'length' => $product['length'],
                        ],
                        'count' => $product['count'],
                    ];
                }, self::getOrderProducts($id)),
                'discount' => self::getOrderCouponsSum($id),
                'weight'   => self::getOrderWeight($id),
                'applyDefaultDimensions' => true,
                'applyWidgetSettings'    => true,
            ],
            'timeout'   => 30,
            'sslverify' => false,
            'headers'   => [
                'Authorization' => 'Bearer ' . get_option(self::SR_TOKEN_OPTION),
                'Shop-Id'       => get_option(self::SR_SHOP_ID_OPTION),
                'Type'          => $widget_order_data['delivery']['type'],
                'Company-Id'    => $widget_order_data['delivery']['deliveryCompanyId'],
            ],
        ]);

        $res_body = json_decode($res['body'], true);

        if ($res['response']['code'] === 200 && $res_body) {
            // Для самовывоза достаём стоимость доставки из ПВЗ
            if ((int) $widget_order_data['delivery']['type'] === self::DELIVERY_TYPE_PICKUP) {
                $point = array_filter($res_body['points'], function($point) use ($widget_order_data) {
                    return $point['id'] === (int) $widget_order_data['delivery']['point']['id'];
                });

                // Если такой точки ПВЗ в результатах не найдено, сбрасываем доставку
                if (empty($point)) {
                    self::removeSafeRouteData($id);
                    return;
                }

                $price = array_values($point)[0]['totalPrice'];
            // Для остальных вариантов доставки стоимость находится в данных компании
            } else {
                $price = $res_body['totalPrice'];
            }

            if ($order->payment_method) {
                if ($order->payment_method === get_option(self::COD_PAY_METHOD_OPTION))
                    $price += $res_body['priceCommissionCod'];
                elseif ($order->payment_method === get_option(self::CARD_COD_PAY_METHOD_OPTION))
                    $price += $res_body['priceCommissionCodCard'];
            }

            $days = $res_body['deliveryDays']['min'];
            if ($days !== $res_body['deliveryDays']['max']) $days .= '-' . $res_body['deliveryDays']['max'];

            self::setDeliveryMetaData($id, [
                'cost' => $price,
                'days' => $days,
            ]);
        // В случае отсутствия доступности прежнего варианта или ошибки - сброс доставки
        } else {
            self::removeSafeRouteData($id);
        }
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
                $details = self::getSRDeliveryDetails($post_id);
                $track_number = get_post_meta($post_id, self::TRACKING_NUMBER_META_KEY, true);
                $track_url = get_post_meta($post_id, self::TRACKING_URL_META_KEY, true);
                $error = self::getErrorBlock($post_id);

                if ($details || $track_url || $track_number || $error) {
                    echo '<div style="line-height: 1.3;">';
                    if (!empty($details[self::DELIVERY_TYPE_META_KEY]))
                        echo '<div>' . __('Type', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_TYPE_META_KEY] . '.</div>';
                    if (!empty($details[self::DELIVERY_DAYS_META_KEY]))
                        echo '<div>' . __('Time (days)', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_DAYS_META_KEY] . '.</div>';
                    if (!empty($details[self::DELIVERY_COMPANY_META_KEY]))
                        echo '<div>' . __('Company', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_COMPANY_META_KEY] . '.</div>';
                    if (!empty($details[self::DELIVERY_POINT_ID_META_KEY]))
                        echo '<div>' . __('Pickup point ID', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_POINT_ID_META_KEY] . '.</div>';
                    if (!empty($details[self::DELIVERY_POINT_ADDRESS_META_KEY]))
                        echo '<div>' . __('Pickup point address', self:: TEXT_DOMAIN) . ': ' . $details[self::DELIVERY_POINT_ADDRESS_META_KEY] . '.</div>';
                    if ($track_url)
                        echo '<div>' . __('Delivery track-number', self::TEXT_DOMAIN) . ': ' . "<a href='$track_url' target='_blank'>$track_number</a>.</div>";
                    elseif ($track_number)
                        echo '<div>' . __('Delivery track-number', self::TEXT_DOMAIN) . ': ' . $track_number . '.</div>';
                    echo $error;
                    echo '</div>';
                } else {
                    echo '&mdash;';
                }
            }
        }, 20, 2);
    }

    /**
     * Добавляет в товар поля "Штрих-код", "ТН ВЭД", "Бренд", "Страна-производитель", "Название на английском"
     */
    public static function _addProductFields()
    {
        add_action('woocommerce_product_options_general_product_data', function () {
            woocommerce_wp_text_input([
                'id' => self::PRODUCT_BARCODE_META_KEY,
                'label' => __('Barcode', self::TEXT_DOMAIN),
                'custom_attributes' => ['autocomplete' => 'off'],
            ]);
            woocommerce_wp_select([
                'id' => self::PRODUCT_PRODUCING_COUNTRY_META_KEY,
                'label' => __('Producing country', self::TEXT_DOMAIN),
                'custom_attributes' => ['autocomplete' => 'off'],
                'options' => array_merge(
                    ['' => __('Not selected', self::TEXT_DOMAIN)],
                    SafeRouteWooCommerceCountries::get()
                ),
            ]);
            woocommerce_wp_text_input([
                'id' => self::PRODUCT_BRAND_META_KEY,
                'label' => __('Brand', self::TEXT_DOMAIN),
                'custom_attributes' => ['autocomplete' => 'off'],
            ]);
            woocommerce_wp_text_input([
                'id' => self::PRODUCT_TNVED_META_KEY,
                'label' => __('Product code', self::TEXT_DOMAIN),
                'desc_tip' => true,
                'description' => __('Required for SafeRoute international shipping', self::TEXT_DOMAIN),
                'custom_attributes' => ['maxlength' => 20, 'autocomplete' => 'off'],
            ]);
            woocommerce_wp_text_input([
                'id' => self::PRODUCT_NAME_EN_META_KEY,
                'label' => __('English product title', self::TEXT_DOMAIN),
                'desc_tip' => true,
                'description' => __('Required for SafeRoute international shipping', self::TEXT_DOMAIN),
                'custom_attributes' => ['autocomplete' => 'off'],
            ]);
        });

        add_action('woocommerce_process_product_meta', function ($post_id) {
            update_post_meta($post_id, self::PRODUCT_BARCODE_META_KEY, esc_attr($_POST[self::PRODUCT_BARCODE_META_KEY]));
            update_post_meta($post_id, self::PRODUCT_TNVED_META_KEY, esc_attr($_POST[self::PRODUCT_TNVED_META_KEY]));
            update_post_meta($post_id, self::PRODUCT_PRODUCING_COUNTRY_META_KEY, $_POST[self::PRODUCT_PRODUCING_COUNTRY_META_KEY]);
            update_post_meta($post_id, self::PRODUCT_BRAND_META_KEY, esc_attr($_POST[self::PRODUCT_BRAND_META_KEY]));
            update_post_meta($post_id, self::PRODUCT_NAME_EN_META_KEY, esc_attr($_POST[self::PRODUCT_NAME_EN_META_KEY]));
        });
    }

    /**
     * @param $plugin_basename string
     */
    public static function init(string $plugin_basename)
    {
        // Проверка, что WooCommerce установлен и активирован
        if (self::checkWooCommerce())
        {
            add_action('admin_menu', __CLASS__ . '::_createAdminSettingsPage');
            add_filter('plugin_action_links_' . $plugin_basename, [__CLASS__, '_addSettingsLink']);
            add_filter('woocommerce_order_item_get_formatted_meta_data', [__CLASS__, '_formatOrderMetaData']);
            add_action('woocommerce_admin_order_data_after_shipping_address', __CLASS__ . '::_showOrderError');
            add_action('load-post.php', __CLASS__ . '::_addOrderMetaBox');
            add_action('woocommerce_after_order_itemmeta', __CLASS__ . '::_addWidgetBlock', 10, 2);
            add_action('woocommerce_order_before_calculate_totals', __CLASS__ . '::_onRecalculateOrder', 10, 2);

            self::_addDeliveryDetailsColumnInOrders();
            self::_addProductFields();
        }
        else
        {
            // Сообщение, что для плагина SafeRoute WooCommerce необходим WooCommerce
            self::_pushNotice(__('WooCommerce is required for SafeRoute WooCommerce plugin.', self::TEXT_DOMAIN));
        }

        // Сообщение, что параметры SafeRoute (токен и ID магазина) не заданы в настройках плагина
        if (!self::checkSettings())
            self::_pushNotice(__('SafeRoute settings not set.', self::TEXT_DOMAIN));

        // Подключение скриптов и CSS к админке
        add_action('wp_loaded', function () {
            wp_enqueue_script('saferoute-widget-api', self::SAFEROUTE_WIDGET_API_PATH);
            wp_enqueue_style('saferoute-widget-css', plugins_url('assets/admin.css', dirname(__FILE__)));
            wp_enqueue_script('saferoute-helpers', plugins_url('assets/helpers.js', dirname(__FILE__)), ['jquery']);
            wp_enqueue_script('saferoute-common', plugins_url('assets/admin-common.js', dirname(__FILE__)), ['jquery']);
            wp_localize_script('saferoute-common', 'myajax', ['url' => admin_url('admin-ajax.php')]);
        });

        self::_echoNotices();
    }
}