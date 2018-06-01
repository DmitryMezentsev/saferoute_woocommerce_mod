<?php

require_once 'DDeliveryWooCommerceBase.php';

/**
 * Добавляет способ доставки DDelivery в WooCommerce
 */
function addDDeliveryShippingMethod()
{
    if (!DDeliveryWooCommerceBase::checkWooCommerce()) return;


    function _addDDeliveryShippingMethod($methods)
    {
        $methods[DDeliveryWooCommerceBase::ID] = 'DDeliveryWooCommerceShippingMethod';
        return $methods;
    }


    function _initDDeliveryShippingMethod()
    {
        class DDeliveryWooCommerceShippingMethod extends WC_Shipping_Method
        {
            /**
             * @param int $instance_id
             */
            public function __construct($instance_id = 0)
            {
                $this->id                = DDeliveryWooCommerceBase::ID;
                $this->instance_id       = absint($instance_id);
                $this->enabled            = 'yes';
                $this->supports           = ['shipping-zones', 'instance-settings', 'instance-settings-modal'];
                $this->method_title       = __('DDelivery', DDeliveryWooCommerceBase::TEXT_DOMAIN);
                $this->method_description = __('DDelivery Shipping Services Aggregator', DDeliveryWooCommerceBase::TEXT_DOMAIN);

                $this->init();

                add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
            }

            public function init()
            {
                $this->instance_form_fields = [
                    'title' => [
                        'title'   => __('Title', 'woocommerce'),
                        'type'    => 'text',
                        'default' => __('DDelivery', DDeliveryWooCommerceBase::TEXT_DOMAIN),
                    ],
                ];

                $this->title = $this->get_option('title');
            }

            /**
             * @param array $package
             */
            public function calculate_shipping($package = [])
            {
                $this->add_rate([
                    'id'       => $this->id,
                    'label'    => $this->title,
                    'cost'     => 0,
                    'calc_tax' => 'per_item',
                ]);
            }
        }
    }


    add_filter('woocommerce_shipping_methods', '_addDDeliveryShippingMethod');
    add_action('woocommerce_shipping_init', '_initDDeliveryShippingMethod');
}