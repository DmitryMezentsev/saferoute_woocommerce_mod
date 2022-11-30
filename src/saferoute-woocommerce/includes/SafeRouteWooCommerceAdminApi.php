<?php

/**
 * Добавляет API для админки
 */
class SafeRouteWooCommerceAdminApi extends SafeRouteWooCommerceBase
{
    public static function init()
    {
        // Проверяем, что WooCommerce установлен и активирован
        if (!self::checkWooCommerce()) return;


    }
}