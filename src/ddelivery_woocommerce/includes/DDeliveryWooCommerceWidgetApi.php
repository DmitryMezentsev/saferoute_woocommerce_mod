<?php

require_once 'DDeliveryWooCommerceBase.php';
require_once 'DDeliveryWidgetApi.php';

/**
 * Добавляет в движок API для взаимодействия с корзинным виджетом
 */
class DDeliveryWooCommerceWidgetApi extends DDeliveryWooCommerceBase
{
	const API_PATH = 'ddelivery-widget-api';
	
	
	/**
     * Возвращает настройки роутов API
	 * 
	 * @return array
     */
    private static function _getApiRoutes()
    {
        return [
            'cart'     => ['_cartApi'     , 'GET'],
            'settings' => ['_settingsApi' , 'GET'],
        ];
    }
	
	/**
	 * Выводит содержимое корзины
	 * 
	 * @param $data object
	 * @return array
	 */
	public static function _cartApi($data)
	{
		global $woocommerce;
		
		$cart = [];
		
		// Товары корзины
		$cart['products'] = [];
		// Общий вес товаров корзины
		$cart['weight'] = $woocommerce->cart->get_cart_contents_weight();
		
		foreach($woocommerce->cart->get_cart() as $woo_cart_item)
		{
			// Штрих-код
			$barcode = get_post_meta($woo_cart_item['product_id'] , self::PRODUCT_BARCODE_META_KEY, true);
			// НДС
			$vat = wc_get_product_terms($woo_cart_item['product_id'], self::PRODUCT_VAT_SLUG_NAME)[0];
			
			// Начальная цена
			$regular_price = (float) $woo_cart_item['data']->regular_price;
			// Размер скидки (начальная цена минус цена продажи)
			$discount = $regular_price - $woo_cart_item['data']->price;
			
			$cart['products'][] = [
				'name'       => $woo_cart_item['data']->name,
				'vendorCode' => $woo_cart_item['data']->sku,
				'barcode'    => $barcode,
				'nds'        => $vat ? (int) $vat : null,
				'price'      => $regular_price,
				'discount'   => $discount,
				'count'      => $woo_cart_item['quantity'],
			];
		}
		
		return $cart;
	}
	
	/**
	 * Выводит настройки CMS, необходимые виджету
	 * 
	 * @param $data object
	 * @return array
	 */
	public static function _settingsApi($data)
	{
		return [
			'lang' => get_locale(),
		];
	}
	
	
    public static function init()
    {
		// Проверяем, что WooCommerce установлен и активирован
        if (!self::checkWooCommerce()) return;
		
		add_action('rest_api_init', function()
        {
            // Добавляет API в /wp-json/
            foreach(self::_getApiRoutes() as $route => $route_params)
            {
                register_rest_route(self::API_PATH, $route, [
                    'methods' => $route_params[1],
                    'callback' => function($data) use ($route_params)
                    {
                        return call_user_func_array(__CLASS__ . '::' . $route_params[0], [$data]);
                    },
                ]);
            }
        });
    }
}