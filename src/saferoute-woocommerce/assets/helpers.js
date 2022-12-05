/**
 * @typedef {{ street: string, building: string, bulk: string, apartment: string, zipCode: string }} WidgetAddressData
 */

window.SRHelpers = {
  /**
   * @param widgetAddress {WidgetAddressData}
   * @return string
   */
  buildAddress1(widgetAddress) {
    let address = widgetAddress.street || '';

    if (widgetAddress.building) {
      if (address) address += ', ';
      address += `д. ${widgetAddress.building}`;
    }
    if (widgetAddress.bulk) address += ` (корп. ${widgetAddress.bulk})`;

    return address.trim();
  },
  /**
   * @param widgetAddress {WidgetAddressData}
   * @return string
   */
  buildAddress2: (widgetAddress) => widgetAddress.apartment ? `Кв/офис ${widgetAddress.apartment}` : '',
};
