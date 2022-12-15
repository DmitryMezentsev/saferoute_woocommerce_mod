/* global SafeRouteCartWidget, SR_HIDE_CHECKOUT_BILLING_BLOCK, SR_WIDGET */

(($) => {
  $(function () {
    // Если в корзине только скачиваемые/виртуальные товары
    if (!SR_WIDGET.PRODUCTS.length) return;


    // Обновляет отображаемые способы оплаты, скрывая недоступные для текущей доставки
    function updatePaymentMethods () {
      // Способ оплаты с наложенным платежом
      const $paymentMethodWithCOD = $(`.wc_payment_method.payment_method_${SR_WIDGET.PAY_METHOD_WITH_COD}`);
      // Способ оплаты с наложенным платежом картой
      const $paymentMethodWithCODCard = $(`.wc_payment_method.payment_method_${SR_WIDGET.PAY_METHOD_WITH_COD_CARD}`);

      if (widget.data) {
        const cashPaymentAvailable = widget.data.delivery.point
          ? widget.data.delivery.point.cashPaymentAvailable
          : widget.data.delivery.CODAvailable;

        const cardPaymentAvailable = widget.data.delivery.point
          ? widget.data.delivery.point.cardPaymentAvailable
          : widget.data.delivery.CODAvailable;

        if ($paymentMethodWithCODCard.length) {
          if (!cashPaymentAvailable) $paymentMethodWithCOD.hide();
          if (!cardPaymentAvailable) $paymentMethodWithCODCard.hide();
        } else {
          if (!cashPaymentAvailable && !cardPaymentAvailable) $paymentMethodWithCOD.hide();
        }
      } else {
        $paymentMethodWithCOD.show();
        $paymentMethodWithCODCard.show();
      }

      // Автоматическое переключение способа оплаты на первый доступный, если ранее
      // выбранный способ оплаты больше не доступен
      if ($('.wc_payment_method input:checked').closest('.wc_payment_method').is(':hidden'))
        $('.wc_payment_method:visible input').first().trigger('click');
    }

    // Скрывает все варианты доставки, кроме SafeRoute
    function hideOtherShippings () {
      $('.shipping ul input.shipping_method').each(function () {
        if ($(this).val() !== 'saferoute')
          $(this).closest('li').hide();
      });
    }

    // Вернет true, если выбрана доставка SafeRoute
    function checkSelectedShippingMethod () {
      const $inputs = $('input.shipping_method');

      const shippingMethod = ($inputs.length > 1)
        ? $inputs.filter(':checked').val()
        : $inputs.val();

      return shippingMethod === 'saferoute';
    }

    // Переключает отображение полей адреса
    function toggleAddressFields (show) {
      const $fields = $(
        '#shipping_country, #shipping_address_1, #shipping_address_2, #shipping_city, #shipping_state, #shipping_postcode'
      ).closest('.form-row');

      if (show) $fields.show();
      else $fields.hide();
    }

    // Отображает блок с виджетом в случае, если выбрана доставка SafeRoute
    function renderWidget () {
      if (checkSelectedShippingMethod()) {
        toggleAddressFields(false);
        widget.init();

        if ($('.saferoute_widget_block').hasClass('submitted'))
          hideOtherShippings();
      } else {
        toggleAddressFields(true);
        widget.destroy();
      }
    }

    // Передаёт в WooCommerce данные выбранной доставки и способа оплаты
    function setDelivery (data, end) {
      $.post(`${SR_WIDGET.BASE_URL}/wp-json/saferoute-widget-api/set-delivery`, {
        sr_data: data,
        pay_method: $('input[name=payment_method]:checked').val(),
      }, (response) => {
        if (response && response.status === 'ok' && end) {
          if (typeof end === 'string')
            $('body').trigger(end);
          else if (typeof end === 'function')
            end();
        }
      });
    }

    // Отображает вместо виджета сообщение с информацией о выбранной доставке
    function showSuccessMessage (data) {
      const t = {
        ru: { delivery: 'Выбранная доставка', changeDelivery: 'Изменить параметры доставки' },
        en: { delivery: 'Selected delivery method', changeDelivery: 'Change delivery' }
      };

      $('.saferoute_delivery_info').show().html(
        '<h3>' + t[SR_WIDGET.LANG].delivery + '</h3>' +
        '<div class="info-wrap">' + data._meta.commonDeliveryDataHTML + '</div>' +
        '<a class="saferoute_change_delivery">' + t[SR_WIDGET.LANG].changeDelivery + '</div>'
      );
    }

    // Скрывает стоимость доставки SafeRoute в корзине в блоке "Сумма заказов"
    function hideSafeRouteCostInCart () {
      $('.cart_totals').each(function () {
        $(this)
          .find('ul#shipping_method li input.shipping_method[value=saferoute]')
          .closest('li')
          .addClass('saferoute_shipping');

        if ($(this).find('.shipping input.shipping_method').length === 1) {
          $(this)
            .find('.shipping input.shipping_method[value=saferoute]')
            .closest('td')
            .addClass('saferoute_shipping');
        }
      });
    }

    // Скроллит страницу до блока с виджетом
    function scrollToWidget () {
      if (!SR_WIDGET.DISABLE_AUTOSCROLL_TO_WIDGET) {
        $('html, body').animate({
          scrollTop: $('#sr_widget').offset().top - 120
        }, 500);
	  }
    }


    const widget = {
      _: null,
      data: null,
      init: function () {
        if (!this._) {
          widget.data = null;

          this._ = new SafeRouteCartWidget('sr_widget', {
            onlyDeliverySelect: true,
            inputAddress: true,
            lang: SR_WIDGET.LANG,
            currency: SR_WIDGET.CURRENCY,
            apiScript: SR_WIDGET.API_URL,
            products: SR_WIDGET.PRODUCTS,
            weight: SR_WIDGET.WEIGHT,
            discount: SR_WIDGET.DISCOUNT,
            onlyCountries: SR_WIDGET.COUNTRIES,
            regionName: $('input[name=shipping_city]').val(),
          });

          this._.on('start', scrollToWidget);

          this._.on('select', (data) => {
            widget.data = data;

            $('textarea[name=order_comments]').val(data.comment);
            $('input[name=shipping_city]').val(data.city.name);
            $('input[name=shipping_state]').val(data.city.region);
            $('select[name=shipping_country]').val(data.city.countryIsoCode).trigger('change');

            if (data.delivery.point) {
              $('input[name=shipping_address_1]').val(data.delivery.point.address);
              $('input[name=shipping_postcode]').val(data.delivery.point.zipCode || '000000');
            } else {
              $('input[name=shipping_address_1]').val(SRHelpers.buildAddress1(data.contacts.address));
              $('input[name=shipping_address_2]').val(SRHelpers.buildAddress2(data.contacts.address));
              $('input[name=shipping_postcode]').val(data.contacts.address.zipCode || '000000');
            }

            $('.saferoute_widget_block').addClass('submitted').hide();
            hideOtherShippings();

            updatePaymentMethods();

            setDelivery(data, 'update_checkout');

            showSuccessMessage(widget.data);
          });

          this._.on('error', (errors) => console.error(errors));

          // Активация блока "Доставка по другому адресу"
          $('input[name=ship_to_different_address]').prop('checked', true).trigger('change');
        }

        $('.woocommerce-checkout').addClass('saferoute_shipping_selected');
      },
      destroy: function () {
        if (this._) {
          setDelivery(null, 'update_checkout');

          this._.destruct();
          this._ = null;

          updatePaymentMethods(null);
        }

        $('.woocommerce-checkout').removeClass('saferoute_shipping_selected');
      }
    };


    // Для страницы чекаута
    if ($('form.woocommerce-checkout').length) {
      // Обнуление ранее выбранной доставки
      setDelivery(null, 'update_checkout');

      // Отправка запроса для обновления блоков
      $(document).ajaxSuccess((event, jqxhr, settings) => {
        if (widget.data) updatePaymentMethods(widget.data.payType);

        if (settings.url.indexOf('wc-ajax=update_order_review') !== -1)
          // Проверка выбранного способа доставки после загрузки блоков
          renderWidget();
      });

      // Переключение выбранного способа доставки
      $(document).on('change', '.shipping input.shipping_method', renderWidget);
      // Проверка изначально выбранного способа доставки
      renderWidget();

      // Переключение выбранного способа оплаты
      $(document).on('change', '.payment_methods input[name=payment_method]', () => {
        if (checkSelectedShippingMethod() && widget.data)
          setDelivery(widget.data, 'update_checkout');
      });

      // Костыль, потому что событие 'applied_coupon' не срабатывает
      const couponRemoveBtnSelector = '.woocommerce-checkout-review-order .woocommerce-remove-coupon';
      const appliedCouponsCount = $(couponRemoveBtnSelector).length;
      new MutationObserver(() => {
        if (appliedCouponsCount !== $(couponRemoveBtnSelector).length) location.reload();
      })
        .observe($('.woocommerce-checkout-review-order').get(0), {
          attributes: false,
          childList: true,
          subtree: true,
        });

      if (SR_HIDE_CHECKOUT_BILLING_BLOCK) $('.woocommerce-billing-fields').find('h1, h2, h3, h4, h5, h6').hide();
    }

    // Для страницы корзины
    if ($('form.woocommerce-cart-form').length) {
      // Отправка запроса для обновления блока со способами доставки
      $(document).ajaxSuccess((event, jqxhr, settings) => {
        if (settings.url.indexOf('wc-ajax=update_shipping_method') !== -1)
          hideSafeRouteCostInCart();
      });

      hideSafeRouteCostInCart();

      // Обнуление ранее выбранной доставки
      setDelivery(null, () => {
        // Обновление блока со стоимостью
        $('.shipping input.shipping_method').first().trigger('change');
      });
    }

    // Костыль для отмены отправки формы в случае, если доставка в виджете не была выбрана
    // (т.к. при использовании для этих целей валидации самого WooCommerce возникают проблемы с назначением стоимости доставки)
    $('form.checkout.woocommerce-checkout').on('checkout_place_order', () => {
      if (checkSelectedShippingMethod() && !widget.data) {
        scrollToWidget();

        alert(SR_WIDGET.LANG === 'ru'
          ? 'Выберите и подтвердите способ доставки в виджете'
          : 'Select and confirm delivery method in the widget'
        );
        return false;
      }
    });

    // Повторное открытие виджета кликом по "Изменить параметры доставки"
    $(document).on('click', '.saferoute_change_delivery', () => {
      $('.saferoute_widget_block').removeClass('submitted').show();

      setDelivery(null, 'update_checkout');

      $('.saferoute_delivery_info').hide().empty();

      if (widget._) {
        widget._.destruct();
        widget._ = null;
      }

      widget.init();
    });
  });
})(jQuery || $);