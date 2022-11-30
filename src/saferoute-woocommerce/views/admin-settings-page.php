<div class="wrap">
    <h1><?php _e('SafeRoute integration settings', self::TEXT_DOMAIN) ?></h1>
    <form action="<?= self::ADMIN_PARENT_SLUG?>?page=<?= self::ADMIN_MENU_SLUG?>" method="post">
        <h2 class="title"><?php _e('Authorization settings', self::TEXT_DOMAIN)?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="<?=self::SR_TOKEN_OPTION?>">
                        <?php _e('SafeRoute token', self::TEXT_DOMAIN)?> <span class="required">*</span>
                    </label>
                </th>
                <td>
                    <input name="<?=self::SR_TOKEN_OPTION?>"
                           id="<?=self::SR_TOKEN_OPTION?>"
                           value="<?=get_option(self::SR_TOKEN_OPTION)?>"
                           class="regular-text"
                           type="text"
                           autocomplete="off"
                           maxlength="64"
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?=self::SR_SHOP_ID_OPTION?>">
                        <?php _e('SafeRoute shop ID', self::TEXT_DOMAIN)?> <span class="required">*</span>
                    </label>
                </th>
                <td>
                    <input name="<?=self::SR_SHOP_ID_OPTION?>"
                           id="<?=self::SR_SHOP_ID_OPTION?>"
                           value="<?=get_option(self::SR_SHOP_ID_OPTION)?>"
                           class="regular-text"
                           type="text"
                           autocomplete="off"
                           maxlength="16"
                           required>
                </td>
            </tr>
            </tbody>
        </table>
        <p style="margin-bottom: 2.5em;">
            <?php _e('Token and shop ID can be found on in the SafeRoute cabinet. See installation instructions for details.', self::TEXT_DOMAIN) ?>
        </p>
        <h2 class="title"><?php _e('Common settings', self::TEXT_DOMAIN)?></h2>
        <table class="form-table">
            <tbody>
            <tr>
              <th scope="row">
                <label for="<?=self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION?>">
                    <?php _e('Order status for sending to SafeRoute', self::TEXT_DOMAIN)?> <span class="required">*</span>
                </label>
              </th>
              <td>
                <select
                    id="<?=self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION?>"
                    name="<?=self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION?>"
                    autocomplete="off"
                    required>
                    <?php foreach (wc_get_order_statuses() as $status_id => $status_title): ?>
                      <option
                          value="<?=$status_id?>"
                          <?=$status_id === self::ORDER_CANCELLED_STATUS ? 'disabled' : ''?>
                          <?= get_option(
                              self::ORDER_STATUS_FOR_SENDING_TO_SR_OPTION,
                              self::ORDER_STATUS_FOR_SENDING_TO_SR_DEFAULT
                          ) === $status_id ? 'selected' : '' ?>>
                          <?=$status_title?>
                      </option>
                    <?php endforeach ?>
                </select>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="<?=self::SEND_ORDERS_AS_CONFIRMED_OPTION?>">
                    <?php _e('Send orders to SafeRoute as confirmed', self::TEXT_DOMAIN)?>
                </label>
              </th>
              <td>
                <input name="<?=self::SEND_ORDERS_AS_CONFIRMED_OPTION?>"
                       id="<?=self::SEND_ORDERS_AS_CONFIRMED_OPTION?>"
                       value="1"
                       type="checkbox"
                       autocomplete="off"
                    <?= get_option(self::SEND_ORDERS_AS_CONFIRMED_OPTION) ? 'checked' : '' ?>
                >
              </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?=self::PRICE_DECLARED_PERCENT_OPTION?>">
                        <?php _e('Price declared percent', self::TEXT_DOMAIN)?>
                    </label>
                </th>
                <td>
                    <input name="<?=self::PRICE_DECLARED_PERCENT_OPTION?>"
                           id="<?=self::PRICE_DECLARED_PERCENT_OPTION?>"
                           value="<?=get_option(self::PRICE_DECLARED_PERCENT_OPTION)?>"
                           class="regular-text"
                           type="number"
                           autocomplete="off"
                           placeholder="<?=self::PRICE_DECLARED_PERCENT_DEFAULT?>"
                           min="0"
                           max="100">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?=self::COD_PAY_METHOD_OPTION?>">
                        <?php _e('COD pay method', self::TEXT_DOMAIN)?>
                    </label>
                </th>
                <td>
                  <select
                      id="<?=self::COD_PAY_METHOD_OPTION?>"
                      name="<?=self::COD_PAY_METHOD_OPTION?>"
                      class="cod-pay-method-select"
                      autocomplete="off">
                      <option value=""><?php _e('Not selected', self::TEXT_DOMAIN)?></option>
                      <?php foreach (WC()->payment_gateways->get_available_payment_gateways() as $payment_gateway): ?>
                        <option
                            value="<?=$payment_gateway->id?>"
                            <?= get_option(self::COD_PAY_METHOD_OPTION) === $payment_gateway->id ? 'selected' : '' ?>>
                            <?=$payment_gateway->title?>
                        </option>
                      <?php endforeach ?>
                  </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?=self::CARD_COD_PAY_METHOD_OPTION?>">
                        <?php _e('Card COD pay method', self::TEXT_DOMAIN)?>
                    </label>
                </th>
                <td>
                  <select
                      id="<?=self::CARD_COD_PAY_METHOD_OPTION?>"
                      name="<?=self::CARD_COD_PAY_METHOD_OPTION?>"
                      class="card-cod-pay-method-select"
                      autocomplete="off">
                    <option value=""><?php _e('Not selected', self::TEXT_DOMAIN)?></option>
                    <?php foreach (WC()->payment_gateways->get_available_payment_gateways() as $payment_gateway): ?>
                      <option
                          value="<?=$payment_gateway->id?>"
                          <?= get_option(self::CARD_COD_PAY_METHOD_OPTION) === $payment_gateway->id ? 'selected' : '' ?>>
                          <?=$payment_gateway->title?>
                      </option>
                    <?php endforeach ?>
                  </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?=self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION?>">
                        <?php _e('Hide billing block in checkout', self::TEXT_DOMAIN)?>
                    </label>
                </th>
                <td>
                    <input name="<?=self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION?>"
                           id="<?=self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION?>"
                           value="1"
                           type="checkbox"
                           autocomplete="off"
                        <?= get_option(self::HIDE_CHECKOUT_BILLING_BLOCK_OPTION) ? 'checked' : '' ?>
                    >
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?=self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION?>">
                        <?php _e('Show details in delivery name', self::TEXT_DOMAIN) ?>
                    </label>
                </th>
                <td>
                    <input name="<?=self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION?>"
                           id="<?=self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION?>"
                           value="1"
                           type="checkbox"
                           autocomplete="off"
                        <?= get_option(self::SHOW_DETAILS_IN_DELIVERY_NAME_OPTION) ? 'checked' : '' ?>
                    >
                </td>
            </tr>
            </tbody>
        </table>
        <h2 class="title"><?php _e('Statuses matching', self::TEXT_DOMAIN)?></h2>
        <?php $sr_statuses = self::loadSRStatuses() ?>
        <?php if ($sr_statuses): ?>
            <table class="form-table" id="statuses-matching">
                <tbody>
                    <?php foreach ($sr_statuses as $sr_status_id => $sr_status_title): ?>
                        <tr>
                            <th scope="row">
                                <label for="sr-status-<?=$sr_status_id?>">[<?=$sr_status_id?>] <?=$sr_status_title?></label>
                            </th>
                            <td>
                                <select
                                    id="sr-status-<?=$sr_status_id?>"
                                    name="<?=self::STATUSES_MATCHING_OPTION?>[<?=$sr_status_id?>]"
                                    autocomplete="off">
                                    <option
                                        value=""
                                        <?= (!self::getMatchedWCStatus($sr_status_id)) ? 'selected' : '' ?>>
                                        <?php _e('Not set', self::TEXT_DOMAIN)?>
                                    </option>
                                    <?php foreach (wc_get_order_statuses() as $wc_status_id => $wc_status_title): ?>
                                        <option
                                            value="<?=$wc_status_id?>"
                                            <?= self::getMatchedWCStatus($sr_status_id) === $wc_status_id ? 'selected' : '' ?>>
                                            <?=$wc_status_title?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                      </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php else: ?>
            <b>
                <?php if (!get_option(self::SR_TOKEN_OPTION) || !get_option(self::SR_SHOP_ID_OPTION)): ?>
                    <?php _e('Set auth data to setup statuses matching', self::TEXT_DOMAIN) ?>
                <?php elseif ($sr_statuses === false): ?>
                    <?php _e('Invalid auth settings', self::TEXT_DOMAIN) ?>
                <?php else: ?>
                    <?php _e('Error while getting the list of statuses from SafeRoute', self::TEXT_DOMAIN) ?>
                <?php endif; ?>
            </b>
        <?php endif; ?>
        <input type="hidden" name="_nonce" value="<?=wp_create_nonce('sr_settings_save')?>">
        <?php submit_button() ?>
    </form>

    <div class="clear"></div>
</div>