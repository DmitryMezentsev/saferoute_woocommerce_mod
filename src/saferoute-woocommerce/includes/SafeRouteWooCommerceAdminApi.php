<?php

/**
 * Добавляет API для админки
 */
class SafeRouteWooCommerceAdminApi extends SafeRouteWooCommerceBase
{
    /**
     * API действий в ошибке (повторить / скрыть)
     */
    private static function addErrorActionsApi()
    {
        // Повторить
        add_action('wp_ajax_error_action_retry', function()
        {
            $id = (int) $_POST['id'];
            $error_code = (int) $_POST['errorCode'];

            $status = null;

            if ($error_code === self::ORDER_CREATION_ERROR_CODE)
            {
                $current_error_code = (int) get_post_meta($id, self::ERROR_CODE_META_KEY, true);
                $current_error_message = get_post_meta($id, self::ERROR_MESSAGE_META_KEY, true);

                if (self::createOrderInSafeRoute($id))
                {
                    $status = 'success';
                }
                else
                {
                    $status = (
                        $current_error_code !== (int) get_post_meta($id, self::ERROR_CODE_META_KEY, true) ||
                        $current_error_message !== get_post_meta($id, self::ERROR_MESSAGE_META_KEY, true)
                    ) ? 'next_error' : 'error';
                }
            }
            elseif ($error_code === self::ORDER_CONFIRMATION_ERROR_CODE)
            {
                $status = self::confirmOrderInSafeRoute($id) ? 'success' : 'error';
            }
            elseif ($error_code === self::ORDER_CANCELLING_ERROR_CODE)
            {
                $status = self::cancelOrderInSafeRoute($id) ? 'success' : 'error';
            }

            exit(json_encode(['status' => $status]));
        });

        // Скрыть
        add_action('wp_ajax_error_action_hide', function()
        {
            $id = (int) $_POST['id'];

            update_post_meta($id, self::ERROR_CODE_META_KEY, null);
            update_post_meta($id, self::ERROR_MESSAGE_META_KEY, null);

            exit(json_encode(['status' => 'success']));
        });
    }

    /**
     * API установки выбранной в виджете доставки
     */
    private static function addSetDeliveryApi()
    {
        add_action('wp_ajax_set_delivery', function()
        {
            $id = (int) $_POST['id'];

            self::saveWidgetSafeRouteOrderData($id, $_POST['data'], true);

            exit(json_encode(['status' => 'success']));
        });
    }

    /**
     * API формирования параметров для запуска виджета
     */
    private static function addWidgetParamsApi()
    {
        add_action('wp_ajax_get_widget_params', function()
        {
            $id = (int) $_POST['id'];

            exit(json_encode([
                'lang'          => self::getCurrentLang(),
                'currency'      => self::getWCCurrency(),
                'onlyCountries' => self::_getSRDeliveryCountries(),
                'weight'        => self::getOrderWeight($id),
                'products'      => self::getOrderProducts($id),
                'discount'      => self::getOrderCouponsSum($id),
                'apiScript'     => self::getWidgetApiScriptPath(),
            ]));
        });
    }

    public static function init()
    {
        // Проверяем, что WooCommerce установлен и активирован
        if (!self::checkWooCommerce()) return;

        self::addErrorActionsApi();
        self::addWidgetParamsApi();
        self::addSetDeliveryApi();
    }
}