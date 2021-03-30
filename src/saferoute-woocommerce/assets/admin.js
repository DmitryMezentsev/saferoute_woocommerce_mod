/* global SafeRouteCabinetWidget */

(function ($) {
  // Достаёт из URL значение параметра с указанным именем
  function getQueryVariable(variable) {
    const vars = window.location.search.substring(1).split('&');

    for (let i = 0; i < vars.length; ++i) {
      const pair = vars[i].split('=');
      if (decodeURIComponent(pair[0]) === variable)
        return decodeURIComponent(pair[1]);
    }
  }

  $(function () {
    const SR_IS_SELECTED_CLASSNAME = 'sr-is-selected';

    const postId = getQueryVariable('post');
    const widget = new SafeRouteCabinetWidget(SR_TOKEN);

    const $editAddress = $('#order_data .order_data_column:nth-child(3) .edit_address');
    const itemsEditBtns = '.woocommerce_order_items .edit-order-item, .woocommerce_order_items .delete-order-item';
    const addItemBtns = '#woocommerce-order-items .add-order-item, #woocommerce-order-items .add-order-fee, #woocommerce-order-items .add-order-shipping, #woocommerce-order-items .add-order-tax';



    // Проверяет, выбрана ли доставка SafeRoute для текущего заказа в настоящий момент
    function checkSRSelected(callback) {
      if (!postId) return false;

      $.ajax({
        url: myajax.url,
        dataType: 'json',
        data: { action: 'check_delivery', order_id: postId },
        success: (response) => {
          callback(response.selected);
        }
      });
    }

    // Активирует редактирование заказа через виджет
    function activateEditViaWidget() {
      $('#post').addClass(SR_IS_SELECTED_CLASSNAME);

      $editAddress.find('input, textarea').prop('readonly', true);
      $editAddress.find('select').prop('disabled', true);
      $editAddress.find('.form-field').on('click', runWidget);

      $(`${itemsEditBtns}, ${addItemBtns}`).on('click', runWidgetWithPreventing);
    }

    // Деактивирует редактирование заказа через виджет
    function deactivateEditViaWidget() {
      $('#post').removeClass(SR_IS_SELECTED_CLASSNAME);

      $editAddress.find('input, textarea').prop('readonly', false);
      $editAddress.find('select').prop('disabled', false);
      $editAddress.find('.form-field').off('click', runWidget);

      $(`${itemsEditBtns}, ${addItemBtns}`).off('click', runWidgetWithPreventing);
    }

    // Запускает виджет для создания/редактирования заказа
    function runWidget() {
      if (SR_ORDER_ID) {
        widget.run(widget.MODE.ORDER_EDIT, { id: SR_ORDER_ID });
      } else {
        let fullName = $('input#_shipping_first_name').val();

        if ($('input#_shipping_last_name').val())
          fullName += ' ' + $('input#_shipping_last_name').val();

        const company = $('input#_shipping_company').val();

        widget.run(widget.MODE.ORDER_CREATE, {
          cmsId: postId,
          products: $('.woocommerce_order_items #order_line_items .item').map(function() {
            const name = $(this).find('.name .wc-order-item-name').text();
            const vendorCode = $.trim($(this).find('.name .wc-order-item-sku').text().replace(/^[^:]+:/, ''));
            const count = Number($(this).find('.quantity .view').text().replace(/\D/g, '')) || 1;
            const priceCod = parseFloat($(this).find('.item_cost .woocommerce-Price-amount.amount').text());

            return { vendorCode, name, count, priceCod };
          }).toArray(),
          recipient: {
            fullName,
            legalEntity: company ? { name: company } : undefined,
          },
          comment: $('#excerpt').val(),
        });
      }
    }

    // Запускает виджет редактирования заказа, перехватывая поведение по умолчанию
    function runWidgetWithPreventing(e) {
      if (e) {
        e.stopPropagation();
        e.preventDefault();
      }

      runWidget();
    }

    // Удаляет SafeRoute ID заказа из данных WooCommerce-заказа
    function removeSROrderID() {
      SR_ORDER_ID = null;

      $.ajax({
        url: myajax.url,
        type: 'POST',
        dataType: 'json',
        data: { action: 'remove_sr_order_id', id: postId },
        error: () => alert('Ошибка при удалении ID заказа SafeRoute'),
      });
    }



    // Проверка выбранной доставки сразу после загрузки страницы
    checkSRSelected(selected => {
      if (selected) {
        // Активация редактирования заказа через виджет
        activateEditViaWidget();

        // Если нет связанного заказа в SafeRoute, открываем виджет для выбора доставки
        if (!SR_ORDER_ID) runWidget();
      } else if (SR_ORDER_ID) {
        // Если доставка SafeRoute не выбрана, а SafeRoute ID есть, удаляем его
        removeSROrderID();
      }
    });

    // Отслеживание обновления HTML блока списка товаров
    new MutationObserver(() => {
      // Когда отображается прелоадер, выполнять функцию не нужно
      if ($('#woocommerce-order-items .blockUI').is(':visible')) return;

      checkSRSelected(selected => {
        if (selected) {
          // Освежаем обработчики (по-другому, без MutationObserver, сделать не удалось)
          $(`${itemsEditBtns}, ${addItemBtns}`)
            .off('click', runWidgetWithPreventing)
            .on('click', runWidgetWithPreventing);
          // Выбрана доставка SafeRoute, но не оформлен заказ в ЛК => запуск виджета
          if (!SR_ORDER_ID) runWidget();
        } else {
          deactivateEditViaWidget();
          removeSROrderID();
        }
      });
    })
      .observe($('#woocommerce-order-items').get(0), {
        attributes: false,
        childList: true,
        subtree: false,
      });



    // Событие сохранения заказа в виджете
    widget.on(widget.EVENT.SAVE, order => {
      $.ajax({
        url: myajax.url,
        type: 'POST',
        dataType: 'json',
        data: { action: 'save_cabinet_widget_data', order, post_id: postId },
        success: () => location.reload(),
        error: () => alert('Ошибка при обновлении данных заказа в WooCommerce'),
      });
    });

    // Обработка ошибок виджета
    widget.on(widget.EVENT.ERROR, code => {
      if (code === widget.ERROR.INVALID_TOKEN)
        return alert('Задан некорректный токен аккаунта SafeRoute');
      if (code === widget.ERROR.INVALID_ORDER_ID)
        return alert('Связанный заказ в SafeRoute не найден');
    });
  });
})(jQuery || $);
