/* global SafeRouteCartWidget, myajax */

(($) => {
  $(function () {
    $('.wp-admin').append(
      `<div id="sr-widget-container">
        <div id="sr-widget-wrap">
          <div id="sr-widget-close" onclick="closeWidget()">x</div>
          <div id="sr-widget"></div>
        </div>
      </div>`
    );
  });


  /**
   * @param action {string}
   * @param errorCode {number}
   * @param id {number}
   * @param target
   */
  window.errorAction = (action, errorCode, id, target) => {
    const $srError = $(target).closest('.sr-error');

    if ($srError.hasClass('processing')) return;

    $srError.addClass('processing');

    $.ajax({
      url: myajax.url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: `error_action_${action}`,
        errorCode,
        id,
      },
      success(res) {
        switch (res.status) {
          case 'success':
            $srError.remove();
            break;
          case 'next_error':
            location.reload();
            break;
          default:
            $srError.removeClass('processing');
        }
      },
      error() {
        alert('Ошибка при выполнении запроса');
        $srError.removeClass('processing');
      },
    });
  }


  let widget = null;

  /**
   * Открывает виджет для выбора доставки
   */
  window.openWidget = () => {
    const id = $('#post_ID').val();

    $('#sr-widget-container').addClass('visible');

    $.ajax({
      url: myajax.url,
      type: 'POST',
      dataType: 'json',
      data: { action: 'get_widget_params', id },
      success(res) {
        if (res.products.length) {
          widget = new SafeRouteCartWidget('sr-widget', {
            onlyDeliverySelect: true,
            inputAddress: true,
            lang: res.lang,
            currency: res.currency,
            apiScript: res.apiScript,
            products: res.products,
            weight: res.weight,
            discount: res.discount,
            onlyCountries: res.onlyCountries,
            regionName: $('[name=_shipping_city]').val(),
            overrideSettings: {
              styles: '',
            },
          });

          widget.on('select', (data) => {
            $.ajax({
              url: myajax.url,
              type: 'POST',
              dataType: 'json',
              data: { action: 'set_delivery', id, data },
              success() {
                // Удаляем поля, чтобы при пересчёте доставки старые значения не затирали только что сохранённые из виджета
                $('#order_shipping_line_items').find('.meta_items, .shipping_method_name').remove();

                $('button.calculate-action').trigger('click');

                // Установка в поля адреса доставки
                $('#order_data .edit_address').trigger('click');
                $('input#_shipping_city').val(data.city.name);
                $('input#_shipping_state').val(data.city.region);
                $('textarea#excerpt').val(data.comment);
                $('select#_shipping_country').val(data.city.countryIsoCode).trigger('change');
                if (data.delivery.point) {
                  $('input#_shipping_address_1').val(data.delivery.point.address);
                  $('input#_shipping_address_2').val('');
                  $('input#_shipping_postcode').val(data.delivery.point.zipCode);
                } else {
                  $('input#_shipping_address_1').val(
                    `${data.contacts.address.street || ''} ${data.contacts.address.building || ''} ${data.contacts.address.bulk || ''}`
                      .trim()
                  );
                  $('input#_shipping_address_2').val(
                    data.contacts.address.apartment ? `Кв/офис ${data.contacts.address.apartment}` : ''
                  );
                  $('input#_shipping_postcode').val(data.contacts.address.zipCode);
                }
              },
              error() {
                alert('Ошибка установки способа доставки');
              },
              complete() {
                closeWidget();
              },
            });
          });

          widget.on('error', (errors) => console.error(errors));
        } else {
          alert('Перед запуском виджета добавьте товары для доставки');
          closeWidget();
        }
      },
      error() {
        alert('Ошибка при получении параметров виджета');
        closeWidget();
      },
    });
  }

  /**
   * Закрывает виджет выбора доставки
   */
  window.closeWidget = () => {
    if (widget) widget.destruct();
    $('#sr-widget-container').removeClass('visible');
  }

})(jQuery || $);
