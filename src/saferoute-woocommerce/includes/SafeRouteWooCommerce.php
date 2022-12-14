<?php

require_once 'SafeRouteWooCommerceBase.php';

require_once 'SafeRouteWooCommerceShippingMethod.php';

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
        add_option(self::SR_SHOP_ID_OPTION, '');
        add_option(self::SR_TOKEN_OPTION, '');
        add_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION, 1);
        add_option(self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION, 1);
        add_option(self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION, self::ORDER_STATUS_FOR_SENDING_TO_SR_DEFAULT);
        add_option(self::SEND_ORDERS_AS_CONFIRMED_OPTION, '');
        add_option(self::PRICE_DECLARED_PERCENT_OPTION, self::PRICE_DECLARED_PERCENT_DEFAULT);
        add_option(self::COD_PAY_METHOD_OPTION, '');
        add_option(self::CARD_COD_PAY_METHOD_OPTION, '');
        add_option(self::STATUSES_MATCHING_OPTION, []);
        add_option(self::DISABLE_AUTOSCROLL_TO_WIDGET, 0);
        add_option(self::WIDGET_PLACEMENT_IN_CHECKOUT, self::WIDGET_PLACEMENT_IN_CHECKOUT_DEFAULT);
    }

    /**
     * Вызывается при удалении плагина
     */
    public static function _uninstallHook()
    {
        delete_option(self::SR_SHOP_ID_OPTION);
        delete_option(self::SR_TOKEN_OPTION);
        delete_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION);
        delete_option(self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION);
        delete_option(self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION);
        delete_option(self::SEND_ORDERS_AS_CONFIRMED_OPTION);
        delete_option(self::PRICE_DECLARED_PERCENT_OPTION);
        delete_option(self::COD_PAY_METHOD_OPTION);
        delete_option(self::CARD_COD_PAY_METHOD_OPTION);
        delete_option(self::STATUSES_MATCHING_OPTION);
        delete_option(self::DISABLE_AUTOSCROLL_TO_WIDGET);
        delete_option(self::WIDGET_PLACEMENT_IN_CHECKOUT);
    }


    /**
     * Возвращает inline JS с параметрами, необходимыми для инициализации виджета
     *
     * @return string
     */
    private static function _getInlineJs(): string
    {
        global $woocommerce;

        if (!$woocommerce->cart) return '';

        $woocommerce->cart->calculate_totals();

        $widget_params = [
            'LANG'                         => self::getCurrentLang(),
            'BASE_URL'                     => get_site_url(),
            'API_URL'                      => self::getWidgetApiScriptPath(),
            'PRODUCTS'                     => self::_getProducts(),
            'COUNTRIES'                    => self::_getSRDeliveryCountries(),
            'WEIGHT'                       => wc_get_weight($woocommerce->cart->get_cart_contents_weight(), 'kg'),
            'DISCOUNT'                     => $woocommerce->cart->get_discount_total(),
            'CURRENCY'                     => self::getWCCurrency(),
            'PAY_METHOD_WITH_COD'          => get_option(self::COD_PAY_METHOD_OPTION, ''),
            'PAY_METHOD_WITH_COD_CARD'     => get_option(self::CARD_COD_PAY_METHOD_OPTION, ''),
            'DISABLE_AUTOSCROLL_TO_WIDGET' => get_option(self::DISABLE_AUTOSCROLL_TO_WIDGET, 0),
        ];

        $js  = 'var SR_WIDGET = ' . json_encode($widget_params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . ';';
        $js .= 'var SR_HIDE_CHECKOUT_BILLING_BLOCK = ' . (get_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION) ? 'true' : 'false') . ';';

        return $js;
    }

    /**
     * Возвращает содержимое корзины
     *
     * @return array
     */
    private static function _getProducts(): array
    {
        global $woocommerce;

        $products = [];

        foreach($woocommerce->cart->get_cart() as $woo_cart_item)
        {
            // Пропуск виртуальных и скачиваемых товаров, т.к. доставка для них не нужна
            if($woo_cart_item['data']->is_virtual() || $woo_cart_item['data']->is_downloadable())
                continue;

            $dimensions = self::getProductDimensions($woo_cart_item['data']);

            $products[] = [
                'name'       => $woo_cart_item['data']->get_name(),
                'vendorCode' => $woo_cart_item['data']->get_sku(),
                'vat'        => self::calcProductVAT($woo_cart_item),
                'price'      => self::calcProductPriceAndDiscount($woo_cart_item['data'])['price'],
                'discount'   => self::calcProductPriceAndDiscount($woo_cart_item['data'])['discount'],
                'count'      => $woo_cart_item['quantity'],
                'width'      => $dimensions['width'],
                'height'     => $dimensions['height'],
                'length'     => $dimensions['length'],
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
                wp_enqueue_script('saferoute-widget-api', self::SAFEROUTE_WIDGET_API_PATH);
                wp_enqueue_script('saferoute-helpers', plugins_url('assets/helpers.js', dirname(__FILE__)), ['jquery']);
                wp_enqueue_script('saferoute-checkout', plugins_url('assets/checkout.js', dirname(__FILE__)), ['jquery']);
                wp_add_inline_script('saferoute-checkout', self::_getInlineJs(), 'before');
                // ...и CSS
                wp_enqueue_style('saferoute-css', plugins_url('assets/checkout.css', dirname(__FILE__)));

                // Вывод HTML блока с виджетом
                add_action(get_option(self::WIDGET_PLACEMENT_IN_CHECKOUT, self::WIDGET_PLACEMENT_IN_CHECKOUT_DEFAULT), function () {
                    require self::getPluginDir() . '/views/checkout-widget-block.php';
                });

                add_filter('woocommerce_billing_fields', function ($billing_fields) {
                    if(!is_checkout()) return $billing_fields;

                    $billing_fields['billing_phone']['required'] = true;

                    // При включённой опции 'Скрыть блок "Детали оплаты" в чекауте' поля оплаты делаем необязательными,
                    // а те поля, которые необязательными сделать нельзя, полностью удаляем
                    if (get_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION)) {
                        unset($billing_fields['billing_company']);
                        unset($billing_fields['billing_first_name']);
                        unset($billing_fields['billing_last_name']);
                        unset($billing_fields['billing_address_1']);
                        unset($billing_fields['billing_address_2']);
                        unset($billing_fields['billing_country']);
                        unset($billing_fields['billing_city']);
                        unset($billing_fields['billing_state']);
                        unset($billing_fields['billing_postcode']);
                    }

                    return $billing_fields;
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
    public static function _onAfterOrderCreate($order_id, array $posted)
    {
        if (empty($_SESSION['sr_data'])) return;

        if (!session_id()) session_start();

        // Только для заказов, для которых была выбрана доставка SafeRoute
        // и не была выбрана собственная компания доставки
        if ($posted['shipping_method'][0] === self::ID)
        {
            self::saveWidgetSafeRouteOrderData($order_id, $_SESSION['sr_data']);

            // Если выбрана собственная доставка, в ЛК заказ передавать не нужно
            if (!empty($_SESSION['sr_data']['delivery']['isMyDelivery'])) return;

            // Проверка статуса
            if (get_post($order_id)->post_status === get_option(self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION))
            {
                SafeRouteWooCommerceBackendApi::createOrderInSafeRoute($order_id);
            }
        }
    }


    /**
     * Инициализация плагина
     *
     * @param $plugin_file string
     */
    public static function init(string $plugin_file)
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

        // Добавление в систему способа доставки SafeRoute
        addSafeRouteShippingMethod();
    }
}