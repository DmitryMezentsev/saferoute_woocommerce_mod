<div class="wrap">
    <h1>Настройки интеграции с DDelivery</h1>

    <form action="<?php echo DDeliveryWooCommercePlugin::ADMIN_PARENT_SLUG; ?>?page=<?php echo DDeliveryWooCommercePlugin::ADMIN_MENU_SLUG; ?>" method="post">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="ddelivery-api-key"><?php _e('API-key', 'ddelivery_woocommerce'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input name="ddelivery_api_key"
                           id="ddelivery-api-key"
                           value="<?php echo get_option(DDeliveryWooCommercePlugin::API_KEY_OPTION); ?>"
                           class="regular-text"
                           type="text"
                           autocomplete="off"
                           maxlength="64"
                           required>
                </td>
            </tr>
            </tbody>
        </table>

        <p>API-ключ вы можете найти на странице магазина в Личном кабинете DDelivery.</p>

        <?php submit_button(); ?>
    </form>

    <div class="clear"></div>
</div>