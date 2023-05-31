<?php

/**
 * Функции и константы, общие для всех остальных классов плагина
 */
class SafeRouteWooCommerceBase
{
    const ID = 'saferoute';

    // Путь к API виджета выбора доставки
    const SAFEROUTE_WIDGET_API_PATH = 'https://widgets.saferoute.ru/cart/api.js';

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

    // Имя параметра 'Отключить автоматическое прокручивание до виджета в чекауте' в БД WordPress
    const DISABLE_AUTOSCROLL_TO_WIDGET = 'disable_autoscroll_to_widget';

    // Имя параметра 'Место вывода виджета на странице чекаута' в БД WordPress
    const WIDGET_PLACEMENT_IN_CHECKOUT = 'widget_placement_in_checkout';

    // Имя параметра с данными заказа для создания его в SafeRoute
    const WIDGET_ORDER_DATA = 'sr_widget_order_data';

    // Имя мета-параметра кода ошибки
    const ERROR_CODE_META_KEY = 'sr_error_code';

    // Имя мета-параметра текста ошибки
    const ERROR_MESSAGE_META_KEY = 'sr_error_message';

    // Имя мета-параметра SafeRoute ID заказа
    const SAFEROUTE_ID_META_KEY = '_order_saferoute_id';

    // Имя мета-параметра трек-номера заказа
    const TRACKING_NUMBER_META_KEY = 'order_tracking_number';

    // Имя мета-параметра ссылки на трекинг заказа на сайте службы доставки
    const TRACKING_URL_META_KEY = 'order_tracking_url';

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
    const DELIVERY_POINT_ID_META_KEY = 'sr_delivery_point_id';
    const DELIVERY_POINT_ADDRESS_META_KEY = 'sr_delivery_point_address';

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

    // Расположение виджета в чекауте по умолчанию
    const WIDGET_PLACEMENT_IN_CHECKOUT_DEFAULT = 'woocommerce_checkout_after_customer_details';

    // Статус для передачи заказов в SafeRoute по умолчанию ("На удержании")
    const ORDER_STATUS_FOR_SENDING_TO_SR_DEFAULT = 'wc-on-hold';

    // Код WC-статуса заказа "Отменён", при котором будет происходить отмена заказа в SafeRoute
    const ORDER_CANCELLED_STATUS = 'wc-cancelled';

    // Возможные ошибки
    const ORDER_CREATION_ERROR_CODE     = 1;
    const ORDER_CONFIRMATION_ERROR_CODE = 2;
    const ORDER_CANCELLING_ERROR_CODE   = 3;


    /**
     * Получает габариты товара в см
     *
     * @param $product
     * @return array
     */
    protected static function getProductDimensions($product): array
    {
        return [
            'width'  => (float) wc_get_dimension((float) $product->get_width(), 'cm') ?: null,
            'height' => (float) wc_get_dimension((float) $product->get_height(), 'cm') ?: null,
            'length' => (float) wc_get_dimension((float) $product->get_length(), 'cm') ?: null,
        ];
    }

    /**
     * Возвращает путь к директории плагина
     *
     * @return string
     */
    public static function getPluginDir(): string
    {
        return dirname(__FILE__, 2);
    }

    /**
     * Проверяет, активирован ли WooCommerce
     *
     * @return bool
     */
    public static function checkWooCommerce(): bool
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * Проверяет, сохранены ли в настройках плагина токен и ID магазина
     *
     * @return bool
     */
    public static function checkSettings(): bool
    {
        return strlen(get_option(self::SR_TOKEN_OPTION)) && strlen(get_option(self::SR_SHOP_ID_OPTION));
    }

    /**
     * Возвращает массив всех возможных статусов поста/заказа (статусы WP + статусы WC)
     *
     * @return array
     */
    public static function getAllStatuses(): array
    {
        return array_merge(array_keys(wc_get_order_statuses()), array_keys(get_post_statuses()));
    }

    /**
     * Устанавливает значения для метаданных доставки
     *
     * @param $order_id  int|string  ID заказа в WooCommerce
     * @param $data      array       Значения: тип доставки, срок в днях, компания, стоимость (опционально), адрес и ID ПВЗ
     * @return boolean
     */
    public static function setDeliveryMetaData($order_id, array $data): bool
    {
        global $wpdb;

        // Получение order item, в котором находится доставка
        $shipping = $wpdb->get_results(
            "SELECT `order_item_id` FROM `".$wpdb->prefix."woocommerce_order_items` WHERE `order_id`=$order_id AND `order_item_type`='shipping'"
        );

        if ($shipping)
        {
            $delivery_item_id = $shipping[0]->order_item_id;

            $data_store = WC_Data_Store::load('order-item');

            // Сохранение подробностей по выбранной доставке в метаданных
            if (array_key_exists('type', $data))
                $data_store->update_metadata($delivery_item_id, self::DELIVERY_TYPE_META_KEY, $data['type']);
            if (array_key_exists('days', $data))
                $data_store->update_metadata($delivery_item_id, self::DELIVERY_DAYS_META_KEY, $data['days']);
            if (array_key_exists('company', $data))
                $data_store->update_metadata($delivery_item_id, self::DELIVERY_COMPANY_META_KEY, $data['company']);
            if (array_key_exists('point_id', $data))
                $data_store->update_metadata($delivery_item_id, self::DELIVERY_POINT_ID_META_KEY, $data['point_id']);
            if (array_key_exists('point_address', $data))
                $data_store->update_metadata($delivery_item_id, self::DELIVERY_POINT_ADDRESS_META_KEY, $data['point_address']);

            // Обновление стоимости доставки
            if (isset($data['cost'])) wc_update_order_item_meta($delivery_item_id, 'cost', $data['cost']);

            if (array_key_exists('company', $data) && array_key_exists('type', $data) && array_key_exists('days', $data))
            {
                $title = (get_option(self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION) && !empty($data['company']) && !empty($data['type']))
                    ? "$data[company], $data[type], $data[days] дн."
                    : 'SafeRoute';

                // Обновление названия доставки
                $wpdb->update($wpdb->prefix . 'woocommerce_order_items', ['order_item_name' => $title], ['order_item_id' => $delivery_item_id]);
            }

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
                case self::DELIVERY_POINT_ID_META_KEY:
                    $data[self::DELIVERY_POINT_ID_META_KEY] = $meta_item->value; break;
                case self::DELIVERY_POINT_ADDRESS_META_KEY:
                    $data[self::DELIVERY_POINT_ADDRESS_META_KEY] = $meta_item->value; break;
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
     * Возвращает текущий язык в формате API виджета SafeRoute ('ru', 'en')
     *
     * @return string
     */
    public static function getCurrentLang(): string
    {
        return get_locale() === 'ru_RU' ? 'ru' : 'en';
    }

    /**
     * Возвращает текущую валюту в формате API виджета SafeRoute
     *
     * @return string
     */
    public static function getWCCurrency()
    {
        $currency = get_woocommerce_currency();

        return [
            'RUB' => 'rub',
            'USD' => 'usd',
            'EUR' => 'euro',
        ][$currency];
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

    /**
     * Вычисляет НДС товара
     *
     * @param $item
     * @return float|null
     */
    public static function calcProductVAT($item)
    {
        return ($item['line_total'] && $item['line_tax'])
            ? round(100 / ($item['line_total'] / $item['line_tax']))
            : null;
    }

    /**
     * Вычисляет стоимость товара и скидку на него
     *
     * @param $product
     * @return array
     */
    public static function calcProductPriceAndDiscount($product)
    {
        // Начальная цена
        $regular_price = (float) $product->get_regular_price();
        // Размер скидки (начальная цена минус цена продажи)
        $discount = $regular_price - $product->get_price();

        return [
            'price' => $regular_price,
            'discount' => $discount,
        ];
    }

    /**
     * Сохраняет данные доставки SafeRoute из виджета в данных заказа
     *
     * @param $order_id int ID заказа в WooCommerce
     * @param $data array Данные доставки в SafeRoute
     * @param $update_delivery_price bool Обновить ли стоимость доставки
     */
    public static function saveWidgetSafeRouteOrderData($order_id, $data, $update_delivery_price = false)
    {
        update_post_meta($order_id, self::WIDGET_ORDER_DATA, $data);

        $days = $data['delivery']['deliveryDays'];
        if ($data['delivery']['maxDeliveryDays'] !== $days)
            $days .= '-' . $data['delivery']['maxDeliveryDays'];

        $saved_data = [
            'type'          => self::getDeliveryType($data['delivery']['type']),
            'days'          => $days,
            'company'       => $data['delivery']['deliveryCompanyName'],
            'point_id'      => isset($data['delivery']['point']) ? $data['delivery']['point']['id'] : null,
            'point_address' => isset($data['delivery']['point']) ? $data['delivery']['point']['address'] : null,
        ];

        if ($update_delivery_price)
        {
            $payment_method = (wc_get_order($order_id))->get_payment_method();

            $price = (float) $data['delivery']['totalPrice'];

            if ($payment_method === get_option(SafeRouteWooCommerceBase::COD_PAY_METHOD_OPTION))
                $price += $data['delivery']['priceCommissionCod'];
            elseif ($payment_method === get_option(SafeRouteWooCommerceBase::CARD_COD_PAY_METHOD_OPTION))
                $price += $data['delivery']['priceCommissionCodCard'];

            $saved_data['cost'] = $price;
        }

        self::setDeliveryMetaData($order_id, $saved_data);
    }

    /**
     * Возвращает блок с данными об ошибке (если она есть)
     *
     * @param $order_id int ID заказа в WooCommerce
     * @return string
     */
    public static function getErrorBlock($order_id): string
    {
        $error_code = (int) get_post_meta($order_id, self::ERROR_CODE_META_KEY, true);
        $error_text = get_post_meta($order_id, self::ERROR_MESSAGE_META_KEY, true);

        if ($error_code)
        {
            $html = '<div class="sr-error">';
            $html .= '<p>';
            $html .= '<b>';

            switch ($error_code) {
                case self::ORDER_CREATION_ERROR_CODE:
                    $html .= __('Order creation error', self::TEXT_DOMAIN);
                    break;
                case self::ORDER_CONFIRMATION_ERROR_CODE:
                    $html .= __('Order confirmation error', self::TEXT_DOMAIN);
                    break;
                case self::ORDER_CANCELLING_ERROR_CODE:
                    $html .= __('Order cancelling error', self::TEXT_DOMAIN);
                    break;
            }

            $html .= '</b>';
            if ($error_text) $html .= ': ' . mb_strtolower($error_text);

            $html .= '<div class="sr-error-actions">';
            $html .= "<a onclick='errorAction(\"retry\", $error_code, $order_id, this)'>" . __('Retry', self::TEXT_DOMAIN) . '</a>';
            if ($error_code !== self::ORDER_CREATION_ERROR_CODE)
                $html .= "<a onclick='errorAction(\"hide\", $error_code, $order_id, this)'>" . __('Hide', self::TEXT_DOMAIN) . '</a>';
            $html .= '</div>';

            $html .= '</p>';
            $html .= '</div>';

            return $html;
        }
        else
        {
            $order = wc_get_order($order_id);
            $widget_order_data = get_post_meta($order_id, self::WIDGET_ORDER_DATA, true);
            $sr_id = get_post_meta($order_id, self::SAFEROUTE_ID_META_KEY, true);

            // Выбрана доставка SafeRoute, но конкретная доставка в виджете выбрана не была, и заказ в ЛК не был создан
            if ($order->has_shipping_method(self::ID) && !$widget_order_data && !$sr_id)
            {
                $html  = '<div class="sr-error">';
                $html .= '<b>' . __('Warning: delivery method not selected!', self::TEXT_DOMAIN) . '</b>';
                $html .= '</div>';

                return $html;
            }
        }

        return '';
    }

    /**
     * Проверяет наличие НП в заказе по методу оплаты
     *
     * @param $order_id int ID заказа
     * @return bool
     */
    public static function checkCODInOrder($order_id): bool
    {
        $payment_method = (wc_get_order($order_id))->get_payment_method();

        return
            $payment_method && (
                $payment_method === get_option(self::COD_PAY_METHOD_OPTION) ||
                $payment_method === get_option(self::CARD_COD_PAY_METHOD_OPTION)
            );
    }

    /**
     * Создаёт заказ на сервере SafeRoute
     *
     * @param $order_id int ID заказа в WP
     * @return bool
     */
    public static function createOrderInSafeRoute($order_id): bool
    {
        $order = wc_get_order($order_id);
        $widget_data = get_post_meta($order_id, self::WIDGET_ORDER_DATA, true);

        $order_items = array_filter($order->get_items(), function ($item) {
            // Пропуск виртуальных и скачиваемых товаров
            return !$item->get_product()->is_virtual() && !$item->get_product()->is_downloadable();
        });

        $price_declared_percent = get_option(self::PRICE_DECLARED_PERCENT_OPTION, self::PRICE_DECLARED_PERCENT_DEFAULT);
        if ($price_declared_percent === '') $price_declared_percent = self::PRICE_DECLARED_PERCENT_DEFAULT;

        $cod = self::checkCODInOrder($order_id);
        // При доставке за границу не должно быть наложенного платежа
        if ($widget_data['city']['countryIsoCode'] !== 'RU') $cod = false;

        // Разбор адреса доставки
        $address = preg_replace('/\s{2,}/', ' ', $order->shipping_address_1);
        // Достаём корпус
        preg_match('/\(к(орп)?\.?\s+([а-я\w\-]+)\)$/iu', $address, $bulk);
        if ($bulk) $address = trim(str_replace($bulk[0], '', $address));
        $bulk = $bulk ? $bulk[2] : null;
        // Достаём дом
        preg_match('/д\.?\s+([\w\-]+)/iu', $address, $house);
        if ($house) {
            // Вытаскиваем улицу
            $street = trim(str_replace($house[0], '', $address), ' ,');
            $house = $house[1];
        } else {
            $house = null;
            $street = $address;
        }
        unset($address);

        $services = !empty($widget_data['_meta']['widgetSettings']['enabledServices'])
            ? array_filter($widget_data['_meta']['widgetSettings']['enabledServices'], function ($service) use ($widget_data) {
                return !empty($widget_data['delivery']['services']) && in_array($service, $widget_data['delivery']['services']);
            })
            : [];

        $data = [
            'cmsId' => self::getOrderNumber($order_id),
            'shopId' => get_option(self::SR_SHOP_ID_OPTION),
            'products' => array_map(function ($item) use ($price_declared_percent, $cod) {
                $id = $item->get_product_id();
                $product = $item->get_product();
                $price = $product->get_price();
                $dimensions = self::getProductDimensions($product);

                return [
                    'name'             => $item->get_name(),
                    'nameEn'           => get_post_meta($id, self::PRODUCT_NAME_EN_META_KEY, true),
                    'vendorCode'       => $product->get_sku(),
                    'vat'              => self::calcProductVAT($item),
                    'priceDeclared'    => $price * $price_declared_percent / 100,
                    'priceCod'         => $cod ? $price : 0,
                    'prePay'           => $cod ? 0 : $price,
                    'tnved'            => get_post_meta($id, self::PRODUCT_TNVED_META_KEY, true),
                    'count'            => $item->get_quantity(),
                    'weight'           => wc_get_weight($product->get_weight(), 'kg') ?: null,
                    'brand'            => get_post_meta($id, self::PRODUCT_BRAND_META_KEY, true),
                    'producingCountry' => get_post_meta($id, self::PRODUCT_PRODUCING_COUNTRY_META_KEY, true),
                    'barcode'          => get_post_meta($id, self::PRODUCT_BARCODE_META_KEY, true),
                    'dimensions'       => [
                        'width'  => $dimensions['width'],
                        'height' => $dimensions['height'],
                        'length' => $dimensions['length'],
                    ],
                ];
            }, $order_items),
            'discount' => $order->get_discount_total(),
            'dimensions' => ['places' => 1],
            'deliveryAddress' => [
                'city' => [
                    'countryCode' => $widget_data['city']['countryIsoCode'],
                    'name'        => $widget_data['city']['name'],
                    'fias'        => $widget_data['city']['fias'],
                    'type'        => $widget_data['city']['type'],
                    'region'      => $widget_data['city']['region'],
                ],
                'street'  => $street,
                'house'   => $house,
                'bulk'    => $bulk,
                'flat'    => trim(preg_replace("/Кв\/офис/", '', $order->shipping_address_2)),
                'zipCode' => $order->shipping_postcode != '000000' ? $order->shipping_postcode : null,
            ],
            'recipient' => [
                'fullName'    => trim($order->shipping_first_name . ' ' . $order->shipping_last_name),
                'phone'       => $order->billing_phone ?: $order->shipping_phone,
                'email'       => $order->billing_email ?: $order->shipping_email,
                'legalEntity' => ['name' => $order->shipping_company],
            ],
            'applyWidgetSettings' => true,
            'applyDefaultDimensions' => true,
            'delivery' => [
                'company'  => ['id' => $widget_data['delivery']['deliveryCompanyId']],
                'type'     => (int) $widget_data['delivery']['type'],
                'point'    => (int) $widget_data['delivery']['type'] === self::DELIVERY_TYPE_PICKUP
                    ? ['id' => $widget_data['delivery']['point']['id']]
                    : null,
                'services' => array_map(function ($service) {
                    return ['id' => $service];
                }, $services),
            ],
            'clientPrice'        => $cod ? $widget_data['delivery']['totalPrice'] : 0,
            'clientPrePay'       => $cod ? 0 : $widget_data['delivery']['totalPrice'],
            'clientDeliveryDate' => $widget_data['deliveryDate']['date'],
            'clientCourierTime'  => !empty($widget_data['deliveryDate']['time']['id'])
                ? $widget_data['deliveryDate']['time']['id']
                : null,
            'comment'            => $order->get_customer_note(),
        ];

        $res = wp_remote_post(self::SAFEROUTE_API_URL . 'orders', [
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . get_option(self::SR_TOKEN_OPTION),
                'Shop-Id' => get_option(self::SR_SHOP_ID_OPTION),
            ],
        ]);

        $res_body = json_decode($res['body'], true);

        // Если заказ был создан в ЛК
        if ($res['response']['code'] === 201)
        {
            // Сохраняем его SafeRoute ID
            update_post_meta($order_id, self::SAFEROUTE_ID_META_KEY, $res_body['id']);
            // Код и текст ошибки сбрасываем, если они были
            update_post_meta($order_id, self::ERROR_CODE_META_KEY, null);
            update_post_meta($order_id, self::ERROR_MESSAGE_META_KEY, null);

            // Если заказы нужно передавать как подтверждённые и доставка не является экспресс-доставкой
            if (get_option(self::SEND_ORDERS_AS_CONFIRMED_OPTION) && !$widget_data['delivery']['isExpress'])
            {
                return self::confirmOrderInSafeRoute($order_id);
            }

            return true;
        }

        // В случае ошибки сохраняем код и текст ошибки
        update_post_meta($order_id, self::ERROR_CODE_META_KEY, self::ORDER_CREATION_ERROR_CODE);
        update_post_meta($order_id, self::ERROR_MESSAGE_META_KEY, $res_body['message']);

        return false;
    }

    /**
     * Подтверждает заказ на сервере SafeRoute
     *
     * @param $wc_order_id int ID заказа в WP
     * @return bool
     */
    public static function confirmOrderInSafeRoute($wc_order_id): bool
    {
        $sr_order_id = (int) get_post_meta($wc_order_id, self::SAFEROUTE_ID_META_KEY, true);

        $res = wp_remote_post(self::SAFEROUTE_API_URL . 'orders/confirm', [
            'body' => ['ids' => [$sr_order_id]],
            'timeout' => 30,
            'sslverify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . get_option(self::SR_TOKEN_OPTION),
                'Shop-Id' => get_option(self::SR_SHOP_ID_OPTION),
                'Stay-New' => 'true',
            ],
        ]);

        // Если при подтверждении была ошибка, сохраняем информацию о ней
        if ($res['response']['code'] !== 204)
        {
            $res_body = json_decode($res['body'], true);

            update_post_meta($wc_order_id, self::ERROR_CODE_META_KEY, self::ORDER_CONFIRMATION_ERROR_CODE);
            update_post_meta($wc_order_id, self::ERROR_MESSAGE_META_KEY, $res_body['message']);

            return false;
        }

        // Если всё ок, сбрасываем ошибки, если вдруг они были
        update_post_meta($wc_order_id, self::ERROR_CODE_META_KEY, null);
        update_post_meta($wc_order_id, self::ERROR_MESSAGE_META_KEY, null);

        return true;
    }

    /**
     * Отменяет заказ на сервере SafeRoute
     *
     * @param $wc_order_id int ID заказа в WP
     * @return bool
     */
    public static function cancelOrderInSafeRoute($wc_order_id): bool
    {
        $sr_order_id = (int) get_post_meta($wc_order_id, self::SAFEROUTE_ID_META_KEY, true);

        $res = wp_remote_post(self::SAFEROUTE_API_URL . 'orders/cancel', [
            'body' => ['ids' => [$sr_order_id]],
            'timeout' => 60,
            'sslverify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . get_option(self::SR_TOKEN_OPTION),
                'Shop-Id' => get_option(self::SR_SHOP_ID_OPTION),
                'Silent' => 1, // Чтобы бэк не пытался отменить этот заказ в WC
            ],
        ]);

        // Если при отмене была ошибка, сохраняем информацию о ней
        if ($res['response']['code'] !== 204)
        {
            $res_body = json_decode($res['body'], true);

            update_post_meta($wc_order_id, self::ERROR_CODE_META_KEY, self::ORDER_CANCELLING_ERROR_CODE);
            update_post_meta($wc_order_id, self::ERROR_MESSAGE_META_KEY, $res_body['message']);

            return false;
        }

        // Если всё ок, сбрасываем ошибки, если вдруг они были
        update_post_meta($wc_order_id, self::ERROR_CODE_META_KEY, null);
        update_post_meta($wc_order_id, self::ERROR_MESSAGE_META_KEY, null);

        // И удаляем все данные по заказу
        self::removeSafeRouteData($wc_order_id);

        return true;
    }

    /**
     * Удаляет все данные SafeRoute по заказу
     *
     * @param $order_id int
     */
    public static function removeSafeRouteData($order_id)
    {
        update_post_meta($order_id, self::WIDGET_ORDER_DATA, null);
        update_post_meta($order_id, self::SAFEROUTE_ID_META_KEY, null);
        update_post_meta($order_id, self::TRACKING_NUMBER_META_KEY, null);
        update_post_meta($order_id, self::TRACKING_URL_META_KEY, null);

        self::setDeliveryMetaData($order_id, [
            'type'          => null,
            'days'          => null,
            'company'       => null,
            'point_id'      => null,
            'point_address' => null,
            'cost'          => 0,
        ]);
    }

    /**
     * Возвращает список кодов стран, куда согласно настройкам WooCommerce разрешена доставка SafeRoute
     *
     * @return array
     */
    public static function _getSRDeliveryCountries(): array
    {
        $zones = array_filter(WC_Shipping_Zones::get_zones(), function ($zone) {
            return array_filter($zone['shipping_methods'], function ($shipping_method) {
                return $shipping_method->id === self::ID && $shipping_method->enabled === 'yes';
            });
        });

        $countries = [];

        foreach($zones as $zone) {
            foreach($zone['zone_locations'] as $zone_location) {
                $countries[] = preg_replace('/:.+$/', '', $zone_location->code);
            }
        }

        return array_values(array_unique($countries));
    }

    /**
     * Возвращает путь к API-скрипту виджета
     *
     * @return string
     */
    public static function getWidgetApiScriptPath(): string
    {
        return get_site_url() . '/wp-json/' . SafeRouteWooCommerceWidgetApi::API_PATH . '/saferoute';
    }

    /**
     * Возвращает массив товаров заказа
     *
     * @param $order_id int ID заказа
     * @return array
     */
    public static function getOrderProducts($order_id): array
    {
        $items = wc_get_order($order_id)->get_items();

        $products = array_map(function ($item)
        {
            $product = $item->get_product();

            // Пропуск виртуальных и скачиваемых товаров, т.к. доставка для них не нужна
            if($product->is_virtual() || $product->is_downloadable()) return null;

            $data = [
                'name'       => $product->get_name(),
                'vendorCode' => $product->get_sku(),
                'vat'        => self::calcProductVAT($item),
                'price'      => self::calcProductPriceAndDiscount($product)['price'],
                'discount'   => self::calcProductPriceAndDiscount($product)['discount'],
                'count'      => $item->get_quantity(),
            ];

            $dimensions = self::getProductDimensions($product);

            if ($dimensions['width']) $data['width'] = $dimensions['width'];
            if ($dimensions['height']) $data['height'] = $dimensions['height'];
            if ($dimensions['length']) $data['length'] = $dimensions['length'];

            return $data;
        }, $items);

        return array_values(array_filter($products, function ($product)
        {
            return (bool) $product;
        }));
    }

    /**
     * Возвращает сумму применённых к заказу купонов
     *
     * @param $order_id int ID заказа
     * @return float
     */
    public static function getOrderCouponsSum($order_id)
    {
        $sum = 0;

        foreach (wc_get_order($order_id)->get_coupon_codes() as $coupon_code)
        {
            $coupon = new WC_Coupon($coupon_code);
            $sum += $coupon->get_amount();
        }

        return $sum;
    }

    /**
     * Вычисляет и возвращает вес заказа в кг
     *
     * @param $order_id int ID заказа
     * @return float Вес в кг
     */
    public static function getOrderWeight($order_id)
    {
        $order = wc_get_order($order_id);
        $total_weight = 0;

        foreach ($order->get_items() as $item_id => $product_item)
        {
            $product = $product_item->get_product();

            if ($product) $total_weight += floatval($product->get_weight() * $product_item->get_quantity());
        }

        return wc_get_weight($total_weight, 'kg');
    }
}