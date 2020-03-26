cx.jQuery(document).ready(function() {
    addGenerateButton();

    cx.bind("delete", function (deleteIds) {
        if (!deleteIds.length) {
            return;
        }
        if (confirm(
            cx.variables.get("TXT_CORE_RECORD_DELETE_CONFIRM", "ViewGenerator/lang")
        )) {
            window.location.replace(
                "?csrf=" + cx.variables.get("csrf", "contrexx") + "&deleteids="
                + encodeURI(deleteIds) + "&vg_increment_number=0"
            );
        }
    }, "apikey");
});

// This and the next method are copied from https://stackoverflow.com/a/27747377
function dec2hex (dec) {
  return ('0' + dec.toString(16)).substr(-2)
}

// generateId :: Integer -> String
function generateId (len) {
  var arr = new Uint8Array((len || 40) / 2)
  window.crypto.getRandomValues(arr)
  return Array.from(arr, dec2hex).join('')
}

function addGenerateButton() {
    let inputField = cx.jQuery('#form-0-apiKey');

    // Show btn
    const btnText = cx.variables.get('TXT_CORE_MODULE_DATA_ACCESS_GENERATE_BTN', 'DataAccess/lang');
    let btn = ' <button id="generate-api-key">' + btnText + '</button>';
    inputField.after(btn);

    // Generate API-Key on click.
    cx.jQuery('#generate-api-key').click(function (event) {
        event.preventDefault();

        inputField.val(generateId(cx.variables.get('minKeyLength', 'DataAccess')));
        cx.jQuery('#generate-api-key').hide();
        inputField.trigger("keyup");
    });

    // only show the button if there's no content in the input field
    inputField.keyup(function(event) {
        if (undefined === inputField.val() || inputField.val().length) {
            cx.jQuery('#generate-api-key').hide();
        } else {
            cx.jQuery('#generate-api-key').show();
        }
    });
    inputField.trigger("keyup");
}
