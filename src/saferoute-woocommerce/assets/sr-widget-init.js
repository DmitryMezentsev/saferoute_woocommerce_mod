/* global SafeRouteCartWidget, SR_HIDE_CHECKOUT_BILLING_BLOCK */

(function ($) {
  $(function () {
    // Если в корзине только скачиваемые/виртуальные товары
    if (!SR_WIDGET.PRODUCTS.length) return;


    const deliveryTypes = { 1: 'Самовывоз', 2: 'Курьерская', 3: 'Почта' };

    const availableLangs = { en_US: 'en', ru_RU: 'ru' };
    const availableCurrencies = { RUB: 'rub', USD: 'usd', EUR: 'euro' };

    const lang = availableLangs[SR_WIDGET.LANG] || 'ru';
    const currency = availableCurrencies[SR_WIDGET.CURRENCY];


    // Переключает отображаемые способы оплаты
    function togglePaymentMethods (type) {
      // Способ оплаты наличными при получении
      const $paymentMethodWithCOD = widget.data && widget.data._meta.widgetSettings.payMethodWithCOD
        ? $('.wc_payment_method.payment_method_' + widget.data._meta.widgetSettings.payMethodWithCOD)
        : null;
      // Способ оплаты картой при получении
      const $cardPaymentMethodWithCOD = widget.data && widget.data._meta.widgetSettings.cardPayMethodWithCOD
        ? $('.wc_payment_method.payment_method_' + widget.data._meta.widgetSettings.cardPayMethodWithCOD)
        : null;
      // Способ оплаты "Оплата через SafeRoute"
      const $paymentMethodSafeRoute = $('.wc_payment_method.payment_method_saferoute');

      // Скрывает способы оплаты с НП, если оплата НП отключена у доставки
      function hideCODPaymentMethodsIfNppDisabled () {
        if (widget.data.delivery && widget.data.delivery.nppDisabled) {
          if ($paymentMethodWithCOD) $paymentMethodWithCOD.hide();
          if ($cardPaymentMethodWithCOD) $cardPaymentMethodWithCOD.hide();
        }
      }

      // Выбрана доставка SafeRoute
      if (checkSelectedShippingMethod()) {
        // Выбрана оплата при получении
        if (type === 1) {
          // Отображение только способов с оплатой при получении
          $('.wc_payment_method').hide();
          if ($paymentMethodWithCOD) $paymentMethodWithCOD.show();
          if ($cardPaymentMethodWithCOD) $cardPaymentMethodWithCOD.show();
        // Выбрана оплата через виджет
        } else if (type === 2) {
          // Отображение только способа "Оплата через SafeRoute"
          $paymentMethodSafeRoute.show().siblings().hide();
        // В виджете был выбран другой способ оплаты, либо в виджете не было шага выбора оплаты
        } else {
          // Скрытие способа оплаты "Оплата через SafeRoute"
          $paymentMethodSafeRoute.hide().siblings().show();
          // Скрытие оплат при получении, если доставка их запрещает
          hideCODPaymentMethodsIfNppDisabled();
        }
      // Доставка SafeRoute не выбрана
      } else {
        // Скрытие способа оплаты "Оплата через SafeRoute"
        $paymentMethodSafeRoute.hide().siblings().show();
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
    // Отображает блок с виджетом в случае, если выбрана доставка SafeRoute
    function renderWidget () {
      if (checkSelectedShippingMethod()) {
        widget.init();

        if ($('.saferoute_widget_block').hasClass('submitted'))
          hideOtherShippings();
      } else {
        widget.destroy();
      }
    }

    // Разделяет ФИО на отдельные имя и фамилию
    function splitFullName (fullName) {
      let firstName = '',
        lastName = '';

      if (fullName) {
        const splitted = fullName.split(/\s+/);

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

      return { firstName, lastName };
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
      if ($('input#copy-widget-data-into-bill').is(':checked') || SR_HIDE_CHECKOUT_BILLING_BLOCK) {
        const fullName = splitFullName(data.contacts.fullName);

        $('input#billing_first_name').val(fullName.firstName);
        $('input#billing_last_name').val(fullName.lastName);
        $('input#billing_address_1').val(buildAddress(data.contacts.address));
        $('input#billing_address_2').val(data.contacts.address.apartment);
        if (data.city) {
          $('input#billing_city').val(data.city.name);
          $('input#billing_state').val(data.city.region || data.city.name);
        }
        $('input#billing_postcode').val(data.contacts.address.zipCode);
        $('input#billing_company').val(data.contacts.companyName);
        $('input#billing_phone').val(data.contacts.phone);
        if (data.contacts.email) {
          $('input#billing_email').val(data.contacts.email);
        }
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
        $('input#shipping_state').val(data.city.region || data.city.name);
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

    // Передаёт в WooCommerce данные выбранной доставки, обновляет стоимость на странице
    function setDelivery ({ price, days, company, type }, end) {
      $.post(SR_WIDGET.BASE_URL + '/wp-json/saferoute-widget-api/set-delivery', {
        price,
        days,
        company,
        type: type ? deliveryTypes[type] : null,
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
        '<h3>' + t[lang].delivery + '</h3>' +
        '<div class="info-wrap">' + data._meta.commonDeliveryDataHTML + '</div>' +
        '<a class="saferoute_change_delivery">' + t[lang].changeDelivery + '</div>'
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

    // Возвращает строковое представление срока доставки
    function getDeliveryDaysString() {
      if (!widget.data) return '';

      return widget.data.delivery.deliveryDays === widget.data.delivery.maxDeliveryDays
        ? widget.data.delivery.deliveryDays
        : widget.data.delivery.deliveryDays + '-' + widget.data.delivery.maxDeliveryDays;
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
            onlyCountries: SR_WIDGET.COUNTRIES,
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

            $('input#saferoute_days').val(getDeliveryDaysString());
            $('input#saferoute_company').val(widget.data.delivery.deliveryCompanyName);
            $('input#saferoute_type').val(widget.data.delivery.type);

            $('.saferoute_widget_block').addClass('submitted').hide();
            hideOtherShippings();

            togglePaymentMethods(widget.data.payType);

            setDelivery({
              price: getCurrentShippingCost(),
              days: getDeliveryDaysString(),
              company: widget.data.delivery.deliveryCompanyName,
              type: widget.data.delivery.type,
            }, 'update_checkout');

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
          setDelivery({ price: 0 }, 'update_checkout');

          this._.destruct();
          this._ = null;

          togglePaymentMethods(null);
        }

        $('input#saferoute_id').val('no');
        $('.woocommerce-checkout').removeClass('saferoute_shipping_selected');
      }
    };


    // Для страницы чекаута
    if ($('form.woocommerce-checkout').length) {
      // Обнуление ранее выбранной доставки
      setDelivery({ price: 0 }, 'update_checkout');

      // Отправка запроса для обновления блоков
      $(document).ajaxSuccess((event, jqxhr, settings) => {
        if (widget.data) togglePaymentMethods(widget.data.payType);

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
          setDelivery({
            price: getCurrentShippingCost(),
            days: getDeliveryDaysString(),
            company: widget.data ? widget.data.delivery.deliveryCompanyName : null,
            type: widget.data ? widget.data.delivery.type : null,
          }, 'update_checkout');
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

      if (SR_HIDE_CHECKOUT_BILLING_BLOCK) $('.woocommerce-billing-fields').hide();
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
      setDelivery({ price: 0 }, () => {
        // Обновление блока со стоимостью
        $('.shipping input.shipping_method').first().trigger('change');
      });
    }

    // Костыль для отмены отправки формы в случае, если доставка в виджете не была выбрана
    // (т.к. при использовании для этих целей валидации самого WooCommerce возникают проблемы с назначением стоимости доставки)
    $('form.checkout.woocommerce-checkout').on('checkout_place_order', () => {
      if (checkSelectedShippingMethod() && !$('input#saferoute_id').val()) {
        alert(SR_WIDGET.LANG === 'en_US'
          ? 'Select and confirm delivery method in the widget'
          : 'Выберите и подтвердите способ доставки в виджете'
        );
        return false;
      }
    });

    // Повторное открытие виджета кликом по "Изменить параметры доставки"
    $(document).on('click', '.saferoute_change_delivery', () => {
      widget.finalized = false;
      $('input#saferoute_id').val('');
      $('.saferoute_widget_block').removeClass('submitted').show();

      setDelivery({ price: 0 }, 'update_checkout');

      $('.saferoute_delivery_info').hide().empty();

      widget._.destruct();
      widget._ = null;

      widget.init();
    });
  });
})(jQuery || $);