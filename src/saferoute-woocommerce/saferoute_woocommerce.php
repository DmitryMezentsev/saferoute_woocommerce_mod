<?php
/**
 * Plugin name: SafeRoute WooCommerce
 * Description: SafeRoute Widget for WooCommerce
 * Version: 1.0.0
 * Author: Dmitry Mezentsev
 * Text Domain: saferoute_woocommerce
 * Domain Path: /languages/
 * License: GPLv2 or later
 */


// Exit if accessed directly
if (!defined('ABSPATH')) exit;


require_once 'includes/SafeRouteWooCommerce.php';


SafeRouteWooCommerce::init(__FILE__);