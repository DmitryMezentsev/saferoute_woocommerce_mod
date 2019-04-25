<div class="wrap">
    <h1><?php _e('SafeRoute integration settings', SafeRouteWooCommerce::TEXT_DOMAIN); ?></h1>

    <form action="<?php echo SafeRouteWooCommerceAdmin::ADMIN_PARENT_SLUG; ?>?page=<?php echo SafeRouteWooCommerceAdmin::ADMIN_MENU_SLUG; ?>" method="post">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="<?php echo SafeRouteWooCommerce::API_KEY_OPTION; ?>">
                        <?php _e('API-key', SafeRouteWooCommerce::TEXT_DOMAIN); ?> <span class="required">*</span>
                    </label>
                </th>
                <td>
                    <input name="<?php echo SafeRouteWooCommerce::API_KEY_OPTION; ?>"
                           id="<?php echo SafeRouteWooCommerce::API_KEY_OPTION; ?>"
                           value="<?php echo get_option(SafeRouteWooCommerce::API_KEY_OPTION); ?>"
                           class="regular-text"
                           type="text"
                           autocomplete="off"
                           maxlength="64"
                           required>
                </td>
            </tr>
            </tbody>
        </table>

        <p><?php _e('API-key can be found on the shop page in SafeRoute cabinet.', SafeRouteWooCommerce::TEXT_DOMAIN); ?></p>

        <?php submit_button(); ?>
    </form>

    <div class="clear"></div>
</div>