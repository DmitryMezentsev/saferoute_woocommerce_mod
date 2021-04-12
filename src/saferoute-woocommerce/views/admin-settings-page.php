<div class="wrap">
    <h1><?php _e('SafeRoute integration settings', SafeRouteWooCommerce::TEXT_DOMAIN); ?></h1>

    <form action="<?= SafeRouteWooCommerceAdmin::ADMIN_PARENT_SLUG; ?>?page=<?= SafeRouteWooCommerceAdmin::ADMIN_MENU_SLUG; ?>" method="post">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="<?php echo SafeRouteWooCommerce::SR_TOKEN_OPTION; ?>">
                        <?php _e('SafeRoute token', SafeRouteWooCommerce::TEXT_DOMAIN); ?> <span class="required">*</span>
                    </label>
                </th>
                <td>
                    <input name="<?php echo SafeRouteWooCommerce::SR_TOKEN_OPTION; ?>"
                           id="<?php echo SafeRouteWooCommerce::SR_TOKEN_OPTION; ?>"
                           value="<?php echo get_option(SafeRouteWooCommerce::SR_TOKEN_OPTION); ?>"
                           class="regular-text"
                           type="text"
                           autocomplete="off"
                           maxlength="64"
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?php echo SafeRouteWooCommerce::SR_SHOP_ID_OPTION; ?>">
                        <?php _e('SafeRoute shop ID', SafeRouteWooCommerce::TEXT_DOMAIN); ?> <span class="required">*</span>
                    </label>
                </th>
                <td>
                    <input name="<?php echo SafeRouteWooCommerce::SR_SHOP_ID_OPTION; ?>"
                           id="<?php echo SafeRouteWooCommerce::SR_SHOP_ID_OPTION; ?>"
                           value="<?php echo get_option(SafeRouteWooCommerce::SR_SHOP_ID_OPTION); ?>"
                           class="regular-text"
                           type="text"
                           autocomplete="off"
                           maxlength="16"
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?php echo SafeRouteWooCommerce::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION; ?>">
                        <?php _e('Enable order editing in SafeRoute from your WordPress admin', SafeRouteWooCommerce::TEXT_DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input name="<?php echo SafeRouteWooCommerce::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION; ?>"
                           id="<?php echo SafeRouteWooCommerce::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION; ?>"
                           value="1"
                           class="regular-text"
                           type="checkbox"
                           autocomplete="off"
                        <?= get_option(SafeRouteWooCommerce::ENABLE_SAFEROUTE_CABINET_WIDGET_OPTION) ? 'checked' : ''; ?>
                    >
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?php echo SafeRouteWooCommerce::HIDE_CHECKOUT_BILLING_BLOCK_OPTION; ?>">
                        <?php _e('Hide billing block in checkout', SafeRouteWooCommerce::TEXT_DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input name="<?php echo SafeRouteWooCommerce::HIDE_CHECKOUT_BILLING_BLOCK_OPTION; ?>"
                           id="<?php echo SafeRouteWooCommerce::HIDE_CHECKOUT_BILLING_BLOCK_OPTION; ?>"
                           value="1"
                           class="regular-text"
                           type="checkbox"
                           autocomplete="off"
                        <?= get_option(SafeRouteWooCommerce::HIDE_CHECKOUT_BILLING_BLOCK_OPTION) ? 'checked' : ''; ?>
                    >
                </td>
            </tr>
            </tbody>
        </table>

        <p><?php _e('Token and shop ID can be found on in the SafeRoute cabinet. See installation instructions for details.', SafeRouteWooCommerce::TEXT_DOMAIN); ?></p>

        <input type="hidden" name="_nonce" value="<?= wp_create_nonce('sr_settings_save'); ?>">
        <?php submit_button(); ?>
    </form>

    <div class="clear"></div>
</div>