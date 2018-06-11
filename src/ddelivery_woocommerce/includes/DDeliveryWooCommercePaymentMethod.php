<?php

require_once 'DDeliveryWooCommerceBase.php';

/**
 * Добавляет способ оплаты "Оплата через DDelivery"
 */
function addDDeliveryPaymentMethod()
{
    if (!DDeliveryWooCommerceBase::checkWooCommerce()) return;


    function _addDDeliveryPaymentMethod($methods)
    {
        $methods[DDeliveryWooCommerceBase::ID] = 'DDeliveryWooCommercePaymentMethod';
        return $methods;
    }


    function _initDDeliveryPaymentMethod()
    {
        class DDeliveryWooCommercePaymentMethod extends WC_Payment_Gateway
        {
            public function __construct()
            {
                $this->id                 = DDeliveryWooCommerceBase::ID;
                $this->method_title       = __('Payment via DDelivery', DDeliveryWooCommerceBase::TEXT_DOMAIN);
                $this->method_description = __('Payment via DDelivery Widget', DDeliveryWooCommerceBase::TEXT_DOMAIN);
                $this->title              = $this->get_option('title');
                $this->has_fields         = false;

                if (!$this->title)
                {
                    $this->update_option('title', __('Payment via DDelivery', DDeliveryWooCommerceBase::TEXT_DOMAIN));
                    $this->title = __('Payment via DDelivery', DDeliveryWooCommerceBase::TEXT_DOMAIN);
                }

                $this->init_form_fields();
                $this->init_settings();

                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            }

            public function init_form_fields()
            {
                $this->form_fields = [
                    'title' => [
                        'title' => __('Title', 'woocommerce'),
                        'type' => 'text',
                    ],
                ];
            }
            
            public function process_payment($order_id)
            {
                $order = wc_get_order($order_id);
                
                return [
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                ];
            }
            
            public function thankyou_page()
            {
                if ($this->instructions)
                    echo wpautop(wptexturize($this->instructions));
            }
        }
    }


    add_filter('woocommerce_payment_gateways', '_addDDeliveryPaymentMethod');
    add_action('plugins_loaded', '_initDDeliveryPaymentMethod');
}