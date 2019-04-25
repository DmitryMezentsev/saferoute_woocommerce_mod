(function($) {
    $(function () {
        var lang = (function () {
            switch (SR_WIDGET.LANG) {
                case 'en_US': return 'en';
                case 'zh_CN': return 'zh';
            }
            return 'ru';
        })();


        // Переключает отображаемые способы оплаты
        function paymentMethodsToggle (useSafeRoute) {
            var $paymentMethodSafeRoute = $('.wc_payment_method.payment_method_saferoute'),
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

        // Скрывает все варианты доставки кроме SafeRoute
        function hideOtherShippings () {
            $('.shipping ul input.shipping_method').each(function () {
                if ($(this).val() !== 'saferoute')
                    $(this).closest('li').hide();
            });
        }

        // Проверяет текущий выбранный способ доставки и отображает блок с виджетом в случае,
        // если выбрана доставка SafeRoute
        function checkSelectedShippingMethod () {
            var $inputs = $('input.shipping_method');

            var shippingMethod = ($inputs.length > 1)
                ? $inputs.filter(':checked').val()
                : $inputs.val();

            if (shippingMethod === 'saferoute') {
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
            var firstName = '',
                lastName = '';

            if (fullName) {
                var splitted = fullName.split(' ');

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

        // Объединяет улицу и дом в единую строку адреса
        function buildAddress (address) {
            var result = '';

            if (address.street) result += address.street;
            if (address.street && address.house) result += ', ';
            if (address.house) result += address.house;

            return result;
        }

        // Копирует данные виджета в блок "Детали оплаты", если был установлен соответствующий чекбокс
        function copyWidgetDataIntoBillingDetails (data) {
            if (!$('input#copy-widget-data-into-bill').is(':checked')) return;

            var fullName = splitFullName(data.contacts.fullName);

            $('input#billing_first_name').val(fullName.firstName);
            $('input#billing_last_name').val(fullName.lastName);
            $('input#billing_address_1').val(buildAddress(data.contacts.address));
            $('input#billing_address_2').val(data.contacts.address.flat);
            $('input#billing_city').val(data.city.name);
            $('input#billing_state').val(data.city.name);
            $('input#billing_postcode').val(data.contacts.address.index);
            $('input#billing_phone').val(data.contacts.phone);
        }

        // Копирует данные виджета в блок с адресом доставки
        function copyWidgetDataIntoShippingDetails (data) {
            var defaultIndex = '000000';

            var fullName = splitFullName(data.contacts.fullName);

            $('input#shipping_first_name').val(fullName.firstName);
            $('input#shipping_last_name').val(fullName.lastName || '-');
            $('input#shipping_city').val(data.city.name);
            $('input#shipping_state').val(data.city.name);

            // Только для самовывоза
            if (Number(data.delivery.type) === 1) {
                $('input#shipping_address_1').val(data.delivery.point.address); // Адрес точки самовывоза
                $('input#shipping_address_2').val('-');
                $('input#shipping_postcode').val(defaultIndex); // Поскольку индекса все равно нет
            } else {
                $('input#shipping_address_1').val(buildAddress(data.contacts.address));
                $('input#shipping_address_2').val(data.contacts.address.flat);
                $('input#shipping_postcode').val(data.contacts.address.index || defaultIndex);
            }
        }

        // Передает в WooCommerce стоимость доставки, обновляет стоимость на странице
        function setShippingCost (cost, end) {
            $.post(SR_WIDGET.BASE_URL + '/wp-json/saferoute-widget-api/set-shipping-cost', {
                cost: cost
            }, function (response) {
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
            var t = {
                ru: { delivery: 'Доставка', deliveryAndPay: 'Доставка и оплата', selectedShipping: 'Выбрана доставка',
                    courier: 'Курьерская', pickup: 'Самовывоз', post: 'Почта России' },
                en: { delivery: 'Shipping', deliveryAndPay: 'Shipping and payment', selectedShipping: 'Selected Shipping',
                    courier: 'Courier', pickup: 'Pickup', post: 'Post of Russia' },
                zh: { delivery: '', deliveryAndPay: '', selectedShipping: '', courier: '', pickup: '', post: '' }
            };

            var type = Number(data.delivery.type);
            var message = t[lang].selectedShipping + ': ';

            message += (function () {
                if (type === 1) return t[lang].pickup + ' (' + data.delivery.point.address + ')';
                if (type === 2) return t[lang].courier;
                return t[lang].post;
            })();

            if (type !== 3) {
                message += ', ' + (data.delivery.point ? data.delivery.point.delivery_company_abbr : data.delivery.delivery_company_abbr);
            }

            message += ', ' + (data.delivery.point ? data.delivery.point.delivery_date : data.delivery.delivery_date);

            $('.saferoute_widget_block').html('<h3>' + t[lang].delivery + '</h3>' + message + '.');
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


        var widget = {
            _: null,
            data: null,
            init: function () {
                if (!this._) {
                    this._ = new SafeRouteCartWidget('sr_widget', {
                        lang: lang,
                        apiScript: SR_WIDGET.API_URL,
                        products: SR_WIDGET.PRODUCTS,
                        weight: SR_WIDGET.WEIGHT,
                        discount: SR_WIDGET.DISCOUNT,
                        mod: 'woocommerce'
                    });

                    this._.on('error', function (errors) { console.error(errors); });

                    this._.on('change', function (data) {
                        widget.data = data;

                        copyWidgetDataIntoBillingDetails(data);
                        copyWidgetDataIntoShippingDetails(data);
                    });

                    this._.on('afterSubmit', function (response) {
                        if (response.status === 'ok') {
                            $('input#saferoute_id').val(response.id || 'no');

                            $('.saferoute_widget_block').addClass('submitted');
                            hideOtherShippings();

                            setShippingCost(
                                widget.data.delivery.point ? widget.data.delivery.point.price_delivery : widget.data.delivery.total_price,
                                'update_checkout'
                            );

                            showSuccessMessage(widget.data);
                        }
                    });

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
            $(document).ajaxSuccess(function (event, jqxhr, settings) {
                if (settings.url.indexOf('wc-ajax=update_order_review') !== -1)
                    // Проверка выбранного способа доставки после загрузки блоков
                    checkSelectedShippingMethod();
            });

            // Переключение выбранного способа доставки
            $(document).on('change', '.shipping input.shipping_method', checkSelectedShippingMethod);
            // Проверка изначально выбранного способа доставки
            checkSelectedShippingMethod();

            // Изменение состояния чекбокса "Использовать данные доставки в блоке деталей оплаты"
            $('input#copy-widget-data-into-bill').on('change', function () {
                copyWidgetDataIntoBillingDetails(widget.data);
            });
        }

        // Для страницы корзины
        if ($('form.woocommerce-cart-form').length) {
            // Отправка запроса для обновления блока со способами доставки
            $(document).ajaxSuccess(function (event, jqxhr, settings) {
                if (settings.url.indexOf('wc-ajax=update_shipping_method') !== -1)
                    hideSafeRouteCostInCart();
            });

            hideSafeRouteCostInCart();

            // Обнуление стоимости ранее выбранной доставки
            setShippingCost(0, function () {
                // Обновление блока со стоимостью
                $('.shipping input.shipping_method').first().trigger('change');
            });
        }
    });
})(jQuery || $);