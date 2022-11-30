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

    // Имя параметра 'Статус заказа для передачи его в SafeRoute' в БД WordPress
    const ORDER_STATUS_FOR_SENDING_TO_SR_OPTION = 'order_status_for_sending_to_sr';

    // Имя параметра 'Передавать заказы в Личный кабинет как подтверждённые' в БД WordPress
    const SEND_ORDERS_AS_CONFIRMED_OPTION = 'send_orders_as_confirmed';

    // Имя параметра 'Процент оценочной стоимости' в БД WordPress
    const PRICE_DECLARED_PERCENT_OPTION = 'price_declared_percent';

    // Имя параметра 'Скрыть блок "Детали оплаты" в чекауте' в БД WordPress
    const HIDE_CHECKOUT_BILLING_BLOCK_OPTION = 'hide_checkout_billing_block';

    // Имя параметра 'Выводить в названии способа доставки детали по доставке' в БД WordPress
    const SHOW_DETAILS_IN_DELIVERY_NAME_OPTION = 'show_details_in_delivery_name';

    // Имя параметра 'Способ оплаты с наложенным платежом' в БД WordPress
    const COD_PAY_METHOD_OPTION = 'cod_pay_method_option';

    // Имя параметра 'Способ оплаты с наложенным платежом картой' в БД WordPress
    const CARD_COD_PAY_METHOD_OPTION = 'card_cod_pay_method_option';

    // Имя параметра, в котором хранятся настройки соответствия статусов в БД WordPress
    const STATUSES_MATCHING_OPTION = 'statuses_matching_option';

    // Имя мета-параметра SafeRoute ID заказа
    const SAFEROUTE_ID_META_KEY = '_order_saferoute_id';

    // Имя мета-параметра трек-номера заказа
    const TRACKING_NUMBER_META_KEY = 'order_tracking_number';

    // Имя мета-параметра ссылки на трекинг заказа на сайте службы доставки
    const TRACKING_URL_META_KEY = 'order_tracking_url';

    // Имя мета-параметра флага переноса заказа в ЛК
    const IN_SAFEROUTE_CABINET_META_KEY = '_order_in_saferoute_cabinet';

    // Имя атрибута со штрих-кодом товара
    const PRODUCT_BARCODE_META_KEY = 'sr_barcode';

    // Имя атрибута с кодом товара
    const PRODUCT_TNVED_META_KEY = 'sr_tnved';

    // Имя атрибута с кодом страны-производителя
    const PRODUCT_PRODUCING_COUNTRY_META_KEY = 'sr_producing_country';

    // Имя атрибута с названием бренда
    const PRODUCT_BRAND_META_KEY = 'sr_brand';

    // Имя атрибута с названием товара на английском
    const PRODUCT_NAME_EN_META_KEY = 'sr_name_en';

    // Text Domain плагина
    const TEXT_DOMAIN = 'saferoute_woocommerce';

    // URL трекинга в SafeRoute
    const SAFEROUTE_TRACKING_URL = 'https://saferoute.ru/tracking/';

    // URL API SafeRoute
    const SAFEROUTE_API_URL = 'https://api.saferoute.ru/v2/';

    // Имена метаданных с деталями по доставке
    const DELIVERY_TYPE_META_KEY = 'sr_delivery_type';
    const DELIVERY_DAYS_META_KEY = 'sr_delivery_days';
    const DELIVERY_COMPANY_META_KEY = 'sr_delivery_company_name';

    // Типы доставок
    const DELIVERY_TYPE_PICKUP  = 1;
    const DELIVERY_TYPE_COURIER = 2;
    const DELIVERY_TYPE_POST    = 3;

    // Код статуса "Принят компанией доставки"
    const SUBMITTED_TO_DELIVERY_SERVICE_STATUS_CODE = 41;

    // Код ошибки "Магазин не найден" / "Некорректный ID магазина"
    const INVALID_SHOP_ID_ERROR_CODE = 2001;

    // Значение процента оценочной стоимости по умолчанию
    const PRICE_DECLARED_PERCENT_DEFAULT = 100;

    // Статус для передачи заказов в SafeRoute по умолчанию ("На удержании")
    const ORDER_STATUS_FOR_SENDING_TO_SR_DEFAULT = 'wc-on-hold';


    /**
     * Возвращает путь к директории плагина
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
            $delivery_item_id = $shipping[0]->order_item_id;

            // Сохранение подробностей по выбранной доставке в метаданных
            $data_store = WC_Data_Store::load('order-item');
            $data_store->update_metadata($delivery_item_id, self::DELIVERY_TYPE_META_KEY, $data['type']);
            $data_store->update_metadata($delivery_item_id, self::DELIVERY_DAYS_META_KEY, $data['days']);
            $data_store->update_metadata($delivery_item_id, self::DELIVERY_COMPANY_META_KEY, $data['company']);

            // Обновление стоимости доставки
            if (isset($data['cost']))
                wc_update_order_item_meta($delivery_item_id, 'cost', $data['cost']);

            return true;
        }

        return false;
    }

    /**
     * Возвращает номер заказа в WooCommerce
     *
     * @param $post_id string|int ID поста (заказа в WooCommerce)
     * @return string
     */
    public static function getOrderNumber($post_id)
    {
        $plugins_meta_keys = [
            '_order_number', // Sequential Order Numbers for WooCommerce, WooCommerce Sequential Order Numbers
            '_alg_wc_full_custom_order_number', // Custom Order Numbers for WooCommerce
        ];

        foreach($plugins_meta_keys as $meta_key)
        {
            $order_number = get_post_meta($post_id, $meta_key, true);
            if (!empty($order_number)) return $order_number;
        }

        return $post_id;
    }

    /**
     * Возвращает текстовое описание способа доставки по коду
     *
     * @param $type int|string Тип доставки
     * @return mixed
     */
    public static function getDeliveryType($type)
    {
        switch((int) $type) {
            case self::DELIVERY_TYPE_PICKUP: return __('Pickup', self::TEXT_DOMAIN);
            case self::DELIVERY_TYPE_COURIER: return __('Courier', self::TEXT_DOMAIN);
            case self::DELIVERY_TYPE_POST: return __('Post', self::TEXT_DOMAIN);
        }

        return $type;
    }

    /**
     * Возвращает детали доставки SafeRoute из метаданных
     *
     * @param $order_id int|string
     * @return array|null
     */
    public static function getSRDeliveryDetails($order_id)
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
     * Отправляет покупателю E-mail уведомление об изменениях в заказе
     *
     * @param $id string ID заказа
     * @param $event string Тип произошедшего события
     * @return bool|void
     */
    public static function sendCustomerEmailNotification($id, $event)
    {
        $order = new WC_Order($id);
        if (!$order) return false;

        $email = $order->billing_email;
        if (!$email) return false;

        $order_number     = $order->get_order_number();
        $track_number     = get_post_meta($id, self::TRACKING_NUMBER_META_KEY, true);
        $track_url        = get_post_meta($id, self::TRACKING_URL_META_KEY, true);
        $site_name        = get_bloginfo('name');
        $delivery_details = self::getSRDeliveryDetails($id);

        $tracking_link = $track_url ? "<a href='$track_url'>" . __('Order tracking', self::TEXT_DOMAIN) . '</a>' : '';

        if ($event === 'track_number_updated')
        {
            $title = $site_name . ' :: ' . __('Your order has been assigned a track number', self::TEXT_DOMAIN);

            $message  = '<p>' . __('Order', self::TEXT_DOMAIN) . " $order_number " . __('received a track number', self::TEXT_DOMAIN) . " $track_number ";
            $message .= __('in the delivery service', self::TEXT_DOMAIN) . ' &laquo;' . $delivery_details[self::DELIVERY_COMPANY_META_KEY] . '&raquo;.</p>';
            $message .= '<p>' . __('In the near future, the order will be transferred to the delivery service.', self::TEXT_DOMAIN) . '</p>';
            $message .= $tracking_link;
        }
        elseif ($event === 'submitted_to_delivery_service')
        {
            $title = $site_name . ' :: ' . __('Your order has been sent', self::TEXT_DOMAIN);

            $message  = '<p>' . __('Order', self::TEXT_DOMAIN) . " $order_number " . __('has been transferred to the delivery service', self::TEXT_DOMAIN);
            $message .= ' &laquo;' . $delivery_details[self::DELIVERY_COMPANY_META_KEY] . '&raquo;.</p>';
            $message .= '<p>' . __('Expect delivery in', self::TEXT_DOMAIN) . ' ' . $delivery_details[self::DELIVERY_DAYS_META_KEY] . ' ' . __('days', self::TEXT_DOMAIN) . '.</p>';
            $message .= $tracking_link;
        }
        else
        {
            return false;
        }

        wc_mail($email, $title, $message, ['content-type: text/html']);
    }

    /**
     * Возвращает текущий язык в формате API SafeRoute ('ru', 'en')
     *
     * @return string
     */
    public static function getCurrentLang()
    {
        return get_locale() === 'ru_RU' ? 'ru' : 'en';
    }

    /**
     * Возвращает по коду статуса SafeRoute код соответствующего статуса WC
     *
     * @param $sr_status_code string|int Код статуса в SafeRoute
     * @return string|null
     */
    public static function getMatchedWCStatus($sr_status_code)
    {
        $statuses_matching = get_option(self::STATUSES_MATCHING_OPTION, []);
        if (!is_array($statuses_matching)) $statuses_matching = [];

        return array_key_exists($sr_status_code, $statuses_matching) ? $statuses_matching[$sr_status_code] : null;
    }
}