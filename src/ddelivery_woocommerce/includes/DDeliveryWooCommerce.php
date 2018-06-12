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
     * Возвращает inline JS с параметрами, необходимыми для инициализации виджета
     * 
     * @return string
     */
    private static function _getInlineJs()
    {
        global $woocommerce;
        
        $widget_params = [
            'BASE_URL' => get_site_url(),
            'API_URL'  => get_site_url() . '/wp-json/' . DDeliveryWooCommerceWidgetApi::API_PATH . '/sdk',
            'PRODUCTS' => self::_getProducts(),
            'WEIGHT'   => $woocommerce->cart->get_cart_contents_weight(),
            'LANG'     => get_locale(),
        ];
        
        return 'var DD_WIDGET = ' . json_encode($widget_params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . ';';
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
            // Вычисление НДС
            $nds = ($woo_cart_item['line_total'] && $woo_cart_item['line_tax'])
                ? round(100 / ($woo_cart_item['line_total'] / $woo_cart_item['line_tax']))
                : null;
            
            // Начальная цена
            $regular_price = (float) $woo_cart_item['data']->regular_price;
            // Размер скидки (начальная цена минус цена продажи)
            $discount = $regular_price - $woo_cart_item['data']->price;

            $products[] = [
                'name'       => $woo_cart_item['data']->name,
                'vendorCode' => $woo_cart_item['data']->sku,
                'barcode'    => get_post_meta($woo_cart_item['product_id'], self::PRODUCT_BARCODE_META_KEY, true),
                'nds'        => $nds,
                'price'      => $regular_price,
                'discount'   => $discount,
                'count'      => $woo_cart_item['quantity'],
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
            // Подключение JS...
            wp_enqueue_script('ddelivery-widget-api', 'https://ddelivery.ru/front/widget-cart/public/api.js');
            wp_enqueue_script('ddelivery-widget-init', '/' . self::PLUGIN_DIR . 'assets/dd-widget-init.js', ['jquery']);
            wp_add_inline_script('ddelivery-widget-init', self::_getInlineJs(), 'before');
            // ...и CSS
            wp_enqueue_style('ddelivery-widget-css', '/' . self::PLUGIN_DIR . 'assets/common.css');
            
            // Вывод HTML блока с виджетом
            add_action('woocommerce_checkout_before_customer_details', function () {
                require self::PLUGIN_DIR_ABS . 'views/checkout-widget-block.php';
            });
            
            // Добавление поля DDelivery ID заказа
            add_filter('woocommerce_checkout_fields', function ($fields) {
                $fields['order']['ddelivery_id'] = [
                    'label'    => __('Shipping type', self::TEXT_DOMAIN),
                    'type'     => 'text',
                    'required' => 1,
                ];
                
                return $fields;
            });
            
            // Изменение текста ошибки валидации поля DDelivery ID
            add_filter('woocommerce_add_error', function ($error) {
                if (strpos($error, __('Shipping type', self::TEXT_DOMAIN)) !== false)
                    return __('Select and confirm shipping type.', self::TEXT_DOMAIN);
                
                return $error;
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
        // Только для заказов, для которых была выбрана доставка DDelivery
        if (isset($posted['ddelivery_id']) && $posted['ddelivery_id'] !== 'no')
        {
            $order = get_post($order_id);
            
            // Сохранение DDelivery ID заказа
            update_post_meta($order_id, self::DDELIVERY_ID_META_KEY, $posted['ddelivery_id']);
            
            if (mb_strlen($posted['ddelivery_id'], 'utf-8') < 15)
                update_post_meta($order_id, self::IN_DDELIVERY_CABINET_META_KEY, 1);
            
            // Отправка запроса к SDK
            $response = DDeliveryWooCommerceSdkApi::updateOrderInDDelivery([
                'id'             => $posted['ddelivery_id'],
                'cms_id'         => $order_id,
                'status'         => $order->post_status,
                'payment_method' => $posted['payment_method'],
            ]);
            
            // Если заказ был перенесен в ЛК
            if ($response['status'] === 'ok' && isset($response['data']['cabinet_id']))
            {
                // Обновляем его DDelivery ID и устанавливаем флаг, что заказ находится в ЛК
                update_post_meta($order_id, self::DDELIVERY_ID_META_KEY, $response['data']['cabinet_id']);
                update_post_meta($order_id, self::IN_DDELIVERY_CABINET_META_KEY, 1);
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
            self::_addWidgetInCheckout();
            add_action('woocommerce_checkout_update_order_meta', __CLASS__ . '::_onAfterOrderCreate', 10, 2);
        }

        DDeliveryWooCommerceSdkApi::init();
        
        // Добавление в систему способа доставки и способа оплаты DDelivery
        addDDeliveryShippingMethod();
        addDDeliveryPaymentMethod();
    }
}