/* global SafeRouteCartWidget */

(function ($) {
  $(function () {
    // Если в корзине только скачиваемые/виртуальные товары
    if (!SR_WIDGET.PRODUCTS.length) return;


    const availableLangs = { en_US: 'en', ru_RU: 'ru' };
    const availableCurrencies = { RUB: 'rub', USD: 'usd', EUR: 'euro' };

    const lang = availableLangs[SR_WIDGET.LANG] || 'ru';
    const currency = availableCurrencies[SR_WIDGET.CURRENCY];


    // Переключает отображаемые способы оплаты
    function paymentMethodsToggle (useSafeRoute) {
      const $paymentMethodSafeRoute = $('.wc_payment_method.payment_method_saferoute'),
        $otherPaymentMethods = $('.wc_payment_method:not(.payment_method_saferoute)');

      function selectAnyVisiblePaymentMethod () {
        $('.wc_payment_method:visible').find('label').trigger('click');
      }

      if (useSafeRoute) {
        if ($paymentMethodSafeRoute.length) {
          // Отображение способа оплаты "Оплата через SafeRoute"
          $paymentMethodSafeRoute.show();
          // Скрытие остальных способов оплаты
          $otherPaymentMethods.hide();

          selectAnyVisiblePaymentMethod();
        }
      } else {
        // Скрытие способа оплаты "Оплата через SafeRoute"
        $paymentMethodSafeRoute.hide();
        // Отображение остальных способов оплаты, если они были скрыты
        $otherPaymentMethods.show();

        selectAnyVisiblePaymentMethod();
      }
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
    // Отображает блок с виджетом в случае, если выбрана доставка SafeRoute
    function renderWidget () {
      if (checkSelectedShippingMethod()) {
        widget.init();
        paymentMethodsToggle(true);

        if ($('.saferoute_widget_block').hasClass('submitted'))
          hideOtherShippings();
      } else {
        widget.destroy();
        paymentMethodsToggle();
      }
    }

    // Разделяет ФИО на отдельные имя и фамилию
    function splitFullName (fullName) {
      let firstName = '',
        lastName = '';

      if (fullName) {
        const splitted = fullName.split(' ');

        // ФИО
        if (splitted.length >= 3) {
          firstName = splitted[1];
          lastName = splitted[0];
          // ИФ
        } else if (splitted.length === 2) {
          firstName = splitted[0];
          lastName = splitted[1];
          // И
        } else if (splitted.length === 1) {
          firstName = splitted[0];
        }
      }

      return {
        firstName: firstName,
        lastName: lastName
      };
    }

    // Объединяет улицу и дом с корпусом в единую строку адреса
    function buildAddress (address) {
      let result = '';

      if (address.street) result += address.street;
      if (address.street && address.building) result += ', ';
      if (address.building) {
        result += address.building;
        if (address.bulk) result += ' (' + address.bulk + ')'
      }

      return result;
    }

    // Копирует данные виджета в блок "Детали оплаты", если был установлен соответствующий чекбокс
    function copyWidgetDataIntoBillingDetails (data) {
      if (!$('input#copy-widget-data-into-bill').is(':checked')) return;

      const fullName = splitFullName(data.contacts.fullName);

      $('input#billing_first_name').val(fullName.firstName);
      $('input#billing_last_name').val(fullName.lastName);
      $('input#billing_address_1').val(buildAddress(data.contacts.address));
      $('input#billing_address_2').val(data.contacts.address.apartment);
      if (data.city) {
        $('input#billing_city').val(data.city.name);
        $('input#billing_state').val(data.city.name);
      }
      $('input#billing_postcode').val(data.contacts.address.zipCode);
      $('input#billing_company').val(data.contacts.companyName);
      $('input#billing_phone').val(data.contacts.phone);
      if (data.contacts.email) {
        $('input#billing_email').val(data.contacts.email);
      }
    }

    // Копирует данные виджета в блок с адресом доставки
    function copyWidgetDataIntoShippingDetails (data) {
      const defaultZipCode = '000000';

      const fullName = splitFullName(data.contacts.fullName);

      $('input#shipping_first_name').val(fullName.firstName);
      $('input#shipping_last_name').val(fullName.lastName || '-');
      if (data.city) {
        $('input#shipping_city').val(data.city.name);
        $('input#shipping_state').val(data.city.name);
      }
      if (data.contacts.email) {
        $('input#shipping_email').val(data.contacts.email);
      }

      // Только для самовывоза
      if (data.delivery && data.delivery.type === 1) {
        $('input#shipping_address_1').val(data.delivery.point.address); // Адрес точки самовывоза
        $('input#shipping_address_2').val('-');
        $('input#shipping_postcode').val(defaultZipCode); // Поскольку индекса все равно нет
      } else {
        $('input#shipping_address_1').val(buildAddress(data.contacts.address));
        $('input#shipping_address_2').val(data.contacts.address.apartment);
        $('input#shipping_postcode').val(data.contacts.address.zipCode || defaultZipCode);
      }
    }

    // Передает в WooCommerce стоимость доставки, обновляет стоимость на странице
    function setShippingCost (cost, end) {
      $.post(SR_WIDGET.BASE_URL + '/wp-json/saferoute-widget-api/set-shipping-cost', {
        cost: cost
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
        ru: { delivery: 'Выбранная доставка', deliveryAndPay: 'Выбранная доставка и оплата', changeDelivery: 'Изменить параметры доставки' },
        en: { delivery: 'Selected delivery method', deliveryAndPay: 'Selected delivery method and payment', changeDelivery: 'Change delivery' }
      };

      $('.saferoute_delivery_info').show().html(
        '<h3>' + t[lang].delivery + '</h3><div>' + data._meta.commonDeliveryData + '</div><a class="saferoute_change_delivery">' + t[lang].changeDelivery + '</div>'
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
      $('html, body').animate({
        scrollTop: $('#sr_widget').offset().top - 120
      }, 500);
    }

    // Проверяет, был ли выбран способ оплаты с наложенным платежом
    function checkCODPaymentSelected() {
      const selected = $('.payment_methods input[name=payment_method]:checked').val();

      return widget.data && selected && (
        selected === widget.data._meta.widgetSettings.payMethodWithCOD || selected === widget.data._meta.widgetSettings.cardPayMethodWithCOD
      );
    }

    // Возвращает полную текущую стоимость доставки
    function getCurrentShippingCost() {
      if (!widget.data) return 0;

      const paySelected = $('.payment_methods input[name=payment_method]:checked').val();

      let cost = widget.data.delivery.totalPrice + (widget.data.payTypeCommission || 0);

      if (paySelected && paySelected === widget.data._meta.widgetSettings.payMethodWithCOD)
        cost += widget.data.delivery.priceCommissionCod || 0;
      else if (paySelected && paySelected === widget.data._meta.widgetSettings.cardPayMethodWithCOD)
        cost += widget.data.delivery.priceCommissionCodCard || 0;

      return cost;
    }


    const widget = {
      _: null,
      data: null,
      finalized: false,
      init: function () {
        if (!this._) {
          widget.finalized = false;

          this._ = new SafeRouteCartWidget('sr_widget', {
            lang,
            currency,
            apiScript: SR_WIDGET.API_URL,
            products: SR_WIDGET.PRODUCTS,
            weight: SR_WIDGET.WEIGHT,
            discount: SR_WIDGET.DISCOUNT,
            mod: 'woocommerce',
          });

          this._.on('start', scrollToWidget);

          this._.on('change', (data) => {
            widget.data = data;

            copyWidgetDataIntoBillingDetails(data);
            copyWidgetDataIntoShippingDetails(data);
          });

          this._.on('done', (response) => {
            widget.finalized = true;

            $('input#saferoute_id').val(response.id || 'no');
            $('input#saferoute_in_cabinet').val(response.confirmed ? 1 : 0);

            const days = widget.data.delivery.deliveryDays === widget.data.delivery.maxDeliveryDays
              ? widget.data.delivery.deliveryDays
              : widget.data.delivery.deliveryDays + '-' + widget.data.delivery.maxDeliveryDays;

            const types = { 1: 'Самовывоз', 2: 'Курьерская', 3: 'Почта' };

            $('input#saferoute_days').val(days);
            $('input#saferoute_company').val(widget.data.delivery.deliveryCompanyName);
            $('input#saferoute_type').val(types[widget.data.delivery.type]);

            $('.saferoute_widget_block').addClass('submitted').hide();
            hideOtherShippings();

            setShippingCost(getCurrentShippingCost(), 'update_checkout');

            showSuccessMessage(widget.data);
          });

          this._.on('error', (errors) => console.error(errors));

          $('input#saferoute_id').val('');
          // Активация блока "Доставка по другому адресу"
          $('input[name=ship_to_different_address]').prop('checked', true).trigger('change');
        }

        $('.woocommerce-checkout').addClass('saferoute_shipping_selected');
      },
      destroy: function () {
        if (this._) {
          setShippingCost(0, 'update_checkout');

          this._.destruct();
          this._ = null;
        }

        $('input#saferoute_id').val('no');
        $('.woocommerce-checkout').removeClass('saferoute_shipping_selected');
      }
    };


    // Для страницы чекаута
    if ($('form.woocommerce-checkout').length) {
      // Обнуление стоимости ранее выбранной доставки
      setShippingCost(0, 'update_checkout');

      // Отправка запроса для обновления блоков
      $(document).ajaxSuccess((event, jqxhr, settings) => {
        if (settings.url.indexOf('wc-ajax=update_order_review') !== -1)
          // Проверка выбранного способа доставки после загрузки блоков
          renderWidget();
      });

      // Переключение выбранного способа доставки
      $(document).on('change', '.shipping input.shipping_method', renderWidget);
      // Проверка изначально выбранного способа доставки
      renderWidget();

      // Изменение состояния чекбокса "Использовать данные доставки в блоке деталей оплаты"
      $('input#copy-widget-data-into-bill').on('change', () => {
        copyWidgetDataIntoBillingDetails(widget.data);
      });

      // Переключение выбранного способа оплаты
      $(document).on('change', '.payment_methods input[name=payment_method]', () => {
        if (checkSelectedShippingMethod() && widget.finalized)
          setShippingCost(getCurrentShippingCost(), 'update_checkout');
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
          subtree: false
        });
    }

    // Для страницы корзины
    if ($('form.woocommerce-cart-form').length) {
      // Отправка запроса для обновления блока со способами доставки
      $(document).ajaxSuccess((event, jqxhr, settings) => {
        if (settings.url.indexOf('wc-ajax=update_shipping_method') !== -1)
          hideSafeRouteCostInCart();
      });

      hideSafeRouteCostInCart();

      // Обнуление стоимости ранее выбранной доставки
      setShippingCost(0, () => {
        // Обновление блока со стоимостью
        $('.shipping input.shipping_method').first().trigger('change');
      });
    }

    // Костыль для отмены отправки формы в случае, если доставка в виджете не была выбрана
    // (т.к. при использовании для этих целей валидации самого WooCommerce возникают проблемы с назначением стоимости доставки)
    $('form.checkout.woocommerce-checkout').on('checkout_place_order', () => {
      if (checkSelectedShippingMethod() && !$('input#saferoute_id').val()) {
        alert(SR_WIDGET.LANG === 'en_US' ? 'Select and confirm delivery method in the widget' : 'Выберите и подтвердите способ доставки в виджете');
        return false;
      }

      if (checkSelectedShippingMethod() && checkCODPaymentSelected() && widget.data.delivery.nppDisabled) {
        alert(SR_WIDGET.LANG === 'en_US' ? 'The selected payment method is not available' : 'Выбранный способ оплаты недоступен');
        return false;
      }
    });

    // Повторное открытие виджета кликом по "Изменить параметры доставки"
    $(document).on('click', '.saferoute_change_delivery', () => {
      widget.finalized = false;
      $('input#saferoute_id').val('');
      $('.saferoute_widget_block').removeClass('submitted').show();

      setShippingCost(0, 'update_checkout');

      $('.saferoute_delivery_info').hide().empty();

      widget._.destruct();
      widget._ = null;

      widget.init();
    });
  });
})(jQuery || $);