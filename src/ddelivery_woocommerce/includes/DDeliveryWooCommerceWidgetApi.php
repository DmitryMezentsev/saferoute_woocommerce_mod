<?php

require_once 'DDeliveryWooCommerceBase.php';
require_once 'DDeliveryWidgetApi.php';

/**
 * Класс, добавляющий в движок API для взаимодействия с корзинным виджетом
 */
class DDeliveryWooCommerceWidgetApi extends DDeliveryWooCommerceBase
{
    public static function init()
    {
        // Проверяем, что WooCommerce установлен и активирован
        if (!self::checkWooCommerce()) return;


    }
}