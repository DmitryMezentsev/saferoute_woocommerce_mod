(($) => {
  const $CODPayMethodSelect = $('.cod-pay-method-select');
  const $cardCODPayMethodSelect = $('.card-cod-pay-method-select');

  $CODPayMethodSelect
    .on('change', function () {
      const CODPayMethod = $(this).val();

      $cardCODPayMethodSelect.find('option').each(function () {
        $(this).prop('disabled', CODPayMethod && CODPayMethod === $(this).attr('value'));
      });
    })
    .trigger('change');

  $cardCODPayMethodSelect
    .on('change', function () {
      const cardCODPayMethod = $(this).val();

      $CODPayMethodSelect.find('option').each(function () {
        $(this).prop('disabled', cardCODPayMethod && cardCODPayMethod === $(this).attr('value'));
      });
    })
    .trigger('change');
})(jQuery || $);
