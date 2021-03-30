<?php

require_once 'SafeRouteWooCommerceBase.php';

/**
 * Добавляет способ доставки SafeRoute в WooCommerce
 */
function addSafeRouteShippingMethod()
{
    if (!SafeRouteWooCommerceBase::checkWooCommerce()) return;


    function _addSafeRouteShippingMethod($methods)
    {
        $methods[SafeRouteWooCommerceBase::ID] = 'SafeRouteWooCommerceShippingMethod';
        return $methods;
    }


    function _initSafeRouteShippingMethod()
    {
        class SafeRouteWooCommerceShippingMethod extends WC_Shipping_Method
        {
            /**
             * @param int $instance_id
             */
            public function __construct($instance_id = 0)
            {
                $this->id                = SafeRouteWooCommerceBase::ID;
                $this->instance_id       = absint($instance_id);
                $this->enabled            = 'yes';
                $this->supports           = ['shipping-zones', 'instance-settings', 'instance-settings-modal'];
                $this->method_title       = __('SafeRoute', SafeRouteWooCommerceBase::TEXT_DOMAIN);
                $this->method_description = __('SafeRoute Shipping Services Aggregator', SafeRouteWooCommerceBase::TEXT_DOMAIN);

                $this->init();

                add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
            }

            public function init()
            {
                $this->instance_form_fields = [
                    'title' => [
                        'title'   => __('Title', 'woocommerce'),
                        'type'    => 'text',
                        'default' => __('SafeRoute', SafeRouteWooCommerceBase::TEXT_DOMAIN),
                    ],
                ];

                $this->title = $this->get_option('title');
            }

            /**
             * @param array $package
             */
            public function calculate_shipping($package = [])
            {
                if (!session_id()) session_start();

                $label = $this->title;

                if (!empty($_SESSION['saferoute_company']) && !empty($_SESSION['saferoute_days']))
                    $label .= " ($_SESSION[saferoute_company], $_SESSION[saferoute_days] дн.)";

                $this->add_rate([
                    'id'       => $this->id,
                    'label'    => $label,
                    'cost'     => 0,
                    'calc_tax' => 'per_item',
                ]);
            }
        }
    }

    // Чтобы стоимость доставки нормально пересчитывалась
    function disableShippingRatesCache($packages)
    {
        $packages[0][rand()] = rand();
        return $packages;
    }


    add_filter('woocommerce_package_rates', function ($rates) {
        if (!session_id()) session_start();

        foreach($rates as $rate_key => $rate_values)
        {
            // Назначение стоимости доставки SafeRoute
            if ($rate_values->method_id === SafeRouteWooCommerceBase::ID)
                $rates[$rate_values->id]->cost = isset($_SESSION['saferoute_price']) ? $_SESSION['saferoute_price'] : null;
        }

        return $rates;
    });


    add_filter('woocommerce_shipping_methods', '_addSafeRouteShippingMethod');
    add_action('woocommerce_shipping_init', '_initSafeRouteShippingMethod');
    add_filter('woocommerce_cart_shipping_packages', 'disableShippingRatesCache', 10, 2);
}