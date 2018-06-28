<?php
/**
 * Plugin name: DDelivery WooCommerce
 * Description: DDelivery Widget for WooCommerce
 * Version: 1.0.1
 * Author: Dmitry Mezentsev
 * Plugin URI: https://ddelivery.ru/integration
 * Text Domain: ddelivery_woocommerce
 * Domain Path: /languages/
 * License: GPLv2 or later
 */


// Exit if accessed directly
if (!defined('ABSPATH')) exit;


require_once 'includes/DDeliveryWooCommerce.php';


DDeliveryWooCommerce::init(__FILE__);