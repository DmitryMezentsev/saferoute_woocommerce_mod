<?php

/**
 * Добавляет API для админки
 */
class SafeRouteWooCommerceAdminApi extends SafeRouteWooCommerceBase
{
    /**
     * Обновляет список товаров заказа по данным о товарах из виджета
     *
     * @param $order_id int|string ID заказа
     * @param $products array Массив товаров из виджета
     */
    private static function updateOrderProducts($order_id, array $products)
    {
        // Очистка товаров заказа
        foreach(wc_get_order($order_id)->get_items() as $item)
            if ($item->get_type() === 'line_item') wc_delete_order_item($item->get_id());

        // Формирование нового списка товаров на основе данных из виджета
        foreach($products as $product)
        {
            $product_id = wc_get_product_id_by_sku($product['vendorCode']);

            $new_item_id = wc_add_order_item($order_id, ['order_item_name' => $product['name']]);
            wc_add_order_item_meta($new_item_id, '_product_id', $product_id ?: null);
            wc_add_order_item_meta($new_item_id, '_qty', $product['count']);
            wc_add_order_item_meta($new_item_id, '_line_subtotal', $product['priceCod']);
            wc_add_order_item_meta($new_item_id, '_line_total', $product['priceCod'] * $product['count']);
        }

        // Пересчёт стоимости заказа
        wc_get_order($order_id)->calculate_totals();
    }

    /**
     * Проверяет, выбрана ли доставка SafeRoute в указанном заказе
     */
    private static function addCheckDeliveryApi()
    {
        add_action('wp_ajax_check_delivery', function()
        {
            $shipping = array_values(wc_get_order($_GET['order_id'])->get_items('shipping'));

            exit(json_encode([
                'selected' => $shipping && $shipping[0]->get_method_id() === self::ID,
            ]));
        });
    }

    /**
     * Сохраняет данные из виджета в данных заказа
     */
    private static function addSaveCabinetWidgetDataApi()
    {
        add_action('wp_ajax_save_cabinet_widget_data', function()
        {
            $delivery = $_POST['order']['delivery'];
            $delivery_is_pickup = (int) $delivery['type'] === 1;

            $delivery_days = ($delivery['days']['min'] === $delivery['days']['max'])
                ? $delivery['days']['min']
                : $delivery['days']['min'] . ' - ' . $delivery['days']['max'];

            // Обновление метаданных доставки (срок, тип, компания, стоимость)
            self::setDeliveryMetaData($_POST['post_id'], [
                'type'    => $delivery['type'],
                'days'    => $delivery_days,
                'company' => $delivery['company']['name'],
                'cost'    => $delivery['totalPrice'],
            ]);

            // Пересчёт стоимости заказа
            wc_get_order($_POST['post_id'])->calculate_totals();

            // Формирование адреса доставки
            if ($delivery_is_pickup) {
                $address = $delivery['point']['address'];
            } else {
                $address = '';

                if (trim($_POST['order']['deliveryAddress']['street']))
                    $address .= trim($_POST['order']['deliveryAddress']['street']);
                if (trim($_POST['order']['deliveryAddress']['house']))
                    $address .= ', дом ' . trim($_POST['order']['deliveryAddress']['house']);
                if (trim($_POST['order']['deliveryAddress']['bulk']))
                    $address .= ' (корп. ' . trim($_POST['order']['deliveryAddress']['bulk']) . ')';
                if (trim($_POST['order']['deliveryAddress']['flat']))
                    $address .= ', ' . trim($_POST['order']['deliveryAddress']['flat']);
            }

            // Обновление полей блока "Доставка" на странице заказа
            update_post_meta($_POST['post_id'], '_shipping_first_name', $_POST['order']['recipient']['fullName']);
            update_post_meta($_POST['post_id'], '_shipping_last_name', '');
            update_post_meta($_POST['post_id'], '_shipping_company', $_POST['order']['recipient']['legalEntity']['name']);
            update_post_meta($_POST['post_id'], '_shipping_address_1', $address);
            update_post_meta($_POST['post_id'], '_shipping_address_2', '');
            update_post_meta($_POST['post_id'], '_shipping_city', $_POST['order']['deliveryAddress']['city']['name']);
            update_post_meta($_POST['post_id'], '_shipping_postcode', $delivery_is_pickup ? '' : $_POST['order']['deliveryAddress']['zipCode']);
            update_post_meta($_POST['post_id'], '_shipping_country', $_POST['order']['deliveryAddress']['city']['countryCode']);
            update_post_meta($_POST['post_id'], '_shipping_state', $_POST['order']['deliveryAddress']['city']['region']);
            update_post_meta($_POST['post_id'], '_shipping_address_index', $address);
            wp_update_post(['ID' => $_POST['post_id'], 'post_excerpt' => trim($_POST['order']['comment'])]);

            // Обновление списка товаров заказа
            self::updateOrderProducts($_POST['post_id'], $_POST['order']['products']);

            // Сохранение SafeRoute ID заказа
            update_post_meta($_POST['post_id'], self::SAFEROUTE_ID_META_KEY, $_POST['order']['id']);
            update_post_meta($_POST['post_id'], self::IN_SAFEROUTE_CABINET_META_KEY, 1);

            exit(json_encode([]));
        });
    }

    /**
     * Удаляет ID связанного заказа в SafeRoute
     */
    private static function addRemoveSROrderIDApi()
    {
        add_action('wp_ajax_remove_sr_order_id', function()
        {
            update_post_meta($_POST['id'], self::SAFEROUTE_ID_META_KEY, null);
            exit(json_encode([]));
        });
    }


    public static function init()
    {
        // Проверяем, что WooCommerce установлен и активирован
        if (!self::checkWooCommerce()) return;

        self::addCheckDeliveryApi();
        self::addSaveCabinetWidgetDataApi();
        self::addRemoveSROrderIDApi();
    }
}