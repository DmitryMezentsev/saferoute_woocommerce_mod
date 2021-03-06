<?php

/**
 * Функции и константы, общие для всех остальных классов плагина
 */
class SafeRouteWooCommerceBase
{
    const ID = 'saferoute';

    // Имя параметра 'Токен аккаунта SafeRoute' в БД WordPress
    const SR_TOKEN_OPTION = 'saferoute_token';

    // Имя параметра 'ID магазина SafeRoute' в БД WordPress
    const SR_SHOP_ID_OPTION = 'saferoute_shop_id';

    // Имя параметра 'Включить редактирование заказа в SafeRoute прямо из админки WordPress' в БД WordPress
    const ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION = 'enable_saferoute_cabinet_widget';

    // Имя мета-параметра SafeRoute ID заказа
    const SAFEROUTE_ID_META_KEY = '_order_saferoute_id';

    // Имя мета-параметра трек-номера заказа
    const TRACKING_NUMBER_META_KEY = 'order_tracking_number';

    // Имя мета-параметра флага переноса заказа в ЛК
    const IN_SAFEROUTE_CABINET_META_KEY = '_order_in_saferoute_cabinet';

    // Имя атрибута со штрих-кодом товара
    const PRODUCT_BARCODE_ATTR_NAME =  'barcode';

    // Text Domain плагина
    const TEXT_DOMAIN = 'saferoute_woocommerce';

    // URL трекинга в SafeRoute
    const SAFEROUTE_TRACKING_URL = 'https://saferoute.ru/#tracking/';

    // URL API SafeRoute
    const SAFEROUTE_API_URL = 'https://api.saferoute.ru/v2/';

    // Имена метаданных с деталями по доставке
    const DELIVERY_TYPE_META_KEY = 'Тип';
    const DELIVERY_DAYS_META_KEY = 'Срок (дней)';
    const DELIVERY_COMPANY_META_KEY = 'Компания';


    /**
     * Ворзвращает путь к директории плагина
     *
     * @return string
     */
    public static function getPluginDir()
    {
        return dirname(dirname(__FILE__));
    }

    /**
     * Проверяет, активирован ли WooCommerce
     *
     * @return bool
     */
    public static function checkWooCommerce()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * Проверяет, сохранены ли в настройках плагина токен и ID магазина
     *
     * @return bool
     */
    public static function checkSettings()
    {
        return strlen(get_option(self::SR_TOKEN_OPTION)) && strlen(get_option(self::SR_SHOP_ID_OPTION));
    }

    /**
     * Возвращает массив всех возможных статусов поста/заказа (статусы WP + статусы WC)
     *
     * @return array
     */
    public static function getAllStatuses()
    {
        return array_merge(array_keys(wc_get_order_statuses()), array_keys(get_post_statuses()));
    }

    /**
     * Устанавливает значения для метаданных доставки
     *
     * @param $order_id  int|string  ID заказа в WooCommerce
     * @param $data      array       Значения: тип доставки, срок в днях, компания, стоимость (опционально)
     * @return boolean
     */
    public static function setDeliveryMetaData($order_id, array $data)
    {
        global $wpdb;

        // Получение order item, в котором находится доставка
        $shipping = $wpdb->get_results(
            "SELECT `order_item_id` FROM `".$wpdb->prefix."woocommerce_order_items` WHERE `order_id`=$order_id AND `order_item_type`='shipping'"
        );

        if ($shipping)
        {
            $delivery_types = ['1' => 'Самовывоз', '2' => 'Курьерская', '3' => 'Почта'];

            $delivery_item_id = $shipping[0]->order_item_id;

            // Сохранение подробностей по выбранной доставке в метаданных
            $data_store = WC_Data_Store::load('order-item');
            $data_store->update_metadata($delivery_item_id, self::DELIVERY_TYPE_META_KEY, $delivery_types[$data['type']]);
            $data_store->update_metadata($delivery_item_id, self::DELIVERY_DAYS_META_KEY, $data['days']);
            $data_store->update_metadata($delivery_item_id, self::DELIVERY_COMPANY_META_KEY, $data['company']);

            // Обновление стоимости доставки
            if (isset($data['cost']))
                wc_update_order_item_meta($delivery_item_id, 'cost', $data['cost']);

            return true;
        }

        return false;
    }
}