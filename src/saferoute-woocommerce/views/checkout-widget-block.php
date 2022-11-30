<div class="saferoute_widget_block">
    <h3><?php _e('Select Shipping Type', self::TEXT_DOMAIN); ?></h3>
    <div id="sr_widget"></div>
    <?php if (!get_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION)): ?>
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input id="copy-widget-data-into-bill" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" autocomplete="off" checked><?php _e('Copy widget data into billing details', self::TEXT_DOMAIN); ?>
        </label>
    <?php endif; ?>
</div>
<div class="saferoute_delivery_info"></div>