/**
 * This file is loaded by the abstract SystemComponentBackendController
 * You may add own JS files using
 * \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/FileName.css', 1));
 * or remove this file if you don't need it
 */
function prepareConfiguration(formname) {
  formname = !formname ? '' : formname;
  // Trigger the download of the configuration
  cx.jQuery('[data-group="edit"]')[2].click();
  // get the content of the textarea
  var configuration = cx.jQuery('.configCode').val();
  // go back to the configurator view
  cx.jQuery('[data-group="config"]')[1].click();
  var pattern = /config\.removeButtons\s=\s'([a-z0-9,]*)*/i;
  if (pattern.test(configuration)) {
    var removedButtons = pattern.exec(configuration);
    cx.jQuery('form[name="' + formname + '"]').append(cx.jQuery('<input />').attr({
      "type":   'hidden',
      "name":   'removedButtons',
      "value":  removedButtons[1]
    }));
  }
  return true;
}
