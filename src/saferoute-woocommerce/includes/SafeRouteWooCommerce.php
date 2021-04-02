<?php

require_once 'SafeRouteWooCommerceBase.php';

require_once 'SafeRouteWooCommerceShippingMethod.php';
require_once 'SafeRouteWooCommercePaymentMethod.php';

require_once 'SafeRouteWooCommerceAdmin.php';
require_once 'SafeRouteWooCommerceAdminApi.php';
require_once 'SafeRouteWooCommerceBackendApi.php';
require_once 'SafeRouteWooCommerceWidgetApi.php';

/**
 * Основной класс плагина
 */
final class SafeRouteWooCommerce extends SafeRouteWooCommerceBase
{
    /**
     * Вызывается при активации плагина
     */
    public static function _activationHook()
    {
        add_option(self::SR_SHOP_ID_OPTION, '', '', 'no');
        add_option(self::SR_TOKEN_OPTION, '', '', 'no');
        add_option(self::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION, '', '', 'no');
    }

    /**
     * Вызывается при удалении плагина
     */
    public static function _uninstallHook()
    {
        delete_option(self::SR_SHOP_ID_OPTION);
        delete_option(self::SR_TOKEN_OPTION);
        delete_option(self::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION);
    }


    /**
     * Возвращает inline JS с параметрами, необходимыми для инициализации виджета
     *
     * @return string
     */
    private static function _getInlineJs()
    {
        global $woocommerce;

        if (!$woocommerce->cart) return '';

        $woocommerce->cart->calculate_totals();

        $widget_params = [
            'LANG'     => get_locale(),
            'BASE_URL' => get_site_url(),
            'API_URL'  => get_site_url() . '/wp-json/' . SafeRouteWooCommerceWidgetApi::API_PATH . '/saferoute',
            'PRODUCTS' => self::_getProducts(),
            'WEIGHT'   => wc_get_weight($woocommerce->cart->get_cart_contents_weight(), 'kg'),
            'DISCOUNT' => $woocommerce->cart->get_discount_total(),
            'CURRENCY' => get_woocommerce_currency(),
        ];

        return 'var SR_WIDGET = ' . json_encode($widget_params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . ';';
    }

    /**
     * Возвращает содержимое корзины
     *
     * @return array
     */
    private static function _getProducts()
    {
        global $woocommerce;

        $products = [];

        foreach($woocommerce->cart->get_cart() as $woo_cart_item)
        {
            // Пропуск виртуальных и скачиваемых товаров, т.к. доставка для них не нужна
            if($woo_cart_item['data']->is_virtual() || $woo_cart_item['data']->is_downloadable())
                continue;

            // Вычисление НДС
            $vat = ($woo_cart_item['line_total'] && $woo_cart_item['line_tax'])
                ? round(100 / ($woo_cart_item['line_total'] / $woo_cart_item['line_tax']))
                : null;

            // Начальная цена
            $regular_price = (float) $woo_cart_item['data']->get_regular_price();
            // Размер скидки (начальная цена минус цена продажи)
            $discount = $regular_price - $woo_cart_item['data']->get_price();

            // Индивидуальные атрибуты товара
            $attributes = $woo_cart_item['data']->get_attributes();

            // Получение штрих-кода из индивидуальных атрибутов товара
            $barcode = (isset($attributes[self::PRODUCT_BARCODE_ATTR_NAME]))
                ? $attributes[self::PRODUCT_BARCODE_ATTR_NAME]->get_options()[0]
                : '';

            $products[] = [
                'name'       => $woo_cart_item['data']->get_name(),
                'vendorCode' => $woo_cart_item['data']->get_sku(),
                'barcode'    => $barcode,
                'vat'        => $vat,
                'price'      => $regular_price,
                'discount'   => $discount,
                'count'      => $woo_cart_item['quantity'],
                'width'      => wc_get_dimension($woo_cart_item['data']->get_width(), 'cm'),
                'height'     => wc_get_dimension($woo_cart_item['data']->get_height(), 'cm'),
                'length'     => wc_get_dimension($woo_cart_item['data']->get_length(), 'cm'),
            ];
        }

        return $products;
    }

    /**
     * Добавляет виджет в форму чекаута
     */
    private static function _addWidgetInCheckout()
    {
        add_action('woocommerce_init', function () {
            add_action('wp_loaded', function () {
                // Подключение JS...
                wp_enqueue_script('saferoute-widget-api', 'https://widgets.saferoute.ru/cart/api.js?new');
                wp_enqueue_script('saferoute-widget-init', plugins_url('assets/sr-widget-init.js', dirname(__FILE__)), ['jquery']);
                wp_add_inline_script('saferoute-widget-init', self::_getInlineJs(), 'before');
                // ...и CSS
                wp_enqueue_style('saferoute-widget-css', plugins_url('assets/common.css', dirname(__FILE__)));

                // Вывод HTML блока с виджетом
                add_action('woocommerce_checkout_before_customer_details', function () {
                    require self::getPluginDir() . '/views/checkout-widget-block.php';
                });

                // Добавление доп. полей для заказа
                add_filter('woocommerce_checkout_fields', function ($fields) {
                    $fields['order']['saferoute_id'] = [
                        'label'    => __('Shipping type', self::TEXT_DOMAIN),
                        'type'     => 'text',
                        'required' => self::_getProducts() ? 1 : 0,
                    ];
                    $fields['order']['saferoute_type']       = ['type' => 'hidden'];
                    $fields['order']['saferoute_company']    = ['type' => 'hidden'];
                    $fields['order']['saferoute_days']       = ['type' => 'hidden'];
                    $fields['order']['saferoute_in_cabinet'] = ['type' => 'hidden'];

                    return $fields;
                });

                // Изменение текста ошибки валидации поля SafeRoute ID
                add_filter('woocommerce_add_error', function ($error) {
                    if (strpos($error, __('Shipping type', self::TEXT_DOMAIN)) !== false)
                        return __('Select and confirm shipping type.', self::TEXT_DOMAIN);

                    return $error;
                });
            });
        });
    }

    /**
     * Вызывается после создания заказа на сайте
     *
     * @param $order_id int
     * @param $posted array
     */
    public static function _onAfterOrderCreate($order_id, $posted)
    {
        // Только для заказов, для которых была выбрана доставка SafeRoute
        if (!empty($posted['saferoute_id']))
        {
            // Только если не была выбрана собственная компания доставки
            if ($posted['saferoute_id'] !== 'no')
            {
                $order = get_post($order_id);

                self::setDeliveryMetaData($order_id, [
                    'type'    => $posted['saferoute_type'],
                    'days'    => $posted['saferoute_days'],
                    'company' => $posted['saferoute_company'],
                ]);

                // Сохранение SafeRoute ID заказа
                update_post_meta($order_id, self::SAFEROUTE_ID_META_KEY, $posted['saferoute_id']);

                if ($posted['saferoute_in_cabinet']) update_post_meta($order_id, self::IN_SAFEROUTE_CABINET_META_KEY, 1);

                // Отправка запроса к бэку SafeRoute
                $response = SafeRouteWooCommerceBackendApi::updateOrderInSafeRoute([
                    'id'            => $posted['saferoute_id'],
                    'cmsId'         => $order_id,
                    'status'        => $order->post_status,
                    'paymentMethod' => $posted['payment_method'],
                ]);

                // Если заказ был перенесен в ЛК
                if ($response && $response['cabinetId'])
                {
                    // Обновляем его SafeRoute ID и устанавливаем флаг, что заказ находится в ЛК
                    update_post_meta($order_id, self::SAFEROUTE_ID_META_KEY, $response['cabinetId']);
                    update_post_meta($order_id, self::IN_SAFEROUTE_CABINET_META_KEY, 1);
                }
            }

            // Если используется эквайринг виджета
            if ($posted['payment_method'] === 'saferoute')
            {
                // Подтверждение завершения оформления заказа в CMS
                if (SafeRouteWooCommerceBackendApi::confirmOrder($_COOKIE['SR_checkoutSessId']))
                {
                    // Установка для заказа статуса "Обработка"
                    wp_update_post(['ID' => $order_id, 'post_status' => 'wc-processing']);
                }
            }
        }
    }


    /**
     * Инициализация плагина
     *
     * @param $plugin_file string
     */
    public static function init($plugin_file)
    {
        // Загрузка перевода
        load_plugin_textdomain(self::TEXT_DOMAIN, false, basename(self::getPluginDir()) . '/languages/');

        register_activation_hook($plugin_file, [__CLASS__, '_activationHook']);
        register_uninstall_hook($plugin_file, [__CLASS__, '_uninstallHook']);

        if (is_admin())
        {
            SafeRouteWooCommerceAdmin::init(plugin_basename($plugin_file));
            SafeRouteWooCommerceAdminApi::init();
        }
        else
        {
            SafeRouteWooCommerceWidgetApi::init();
            self::_addWidgetInCheckout();
            add_action('woocommerce_checkout_update_order_meta', __CLASS__ . '::_onAfterOrderCreate', 10, 2);
        }

        SafeRouteWooCommerceBackendApi::init();

        // Добавление в систему способа доставки и способа оплаты SafeRoute
        addSafeRouteShippingMethod();
        addSafeRoutePaymentMethod();
    }
}