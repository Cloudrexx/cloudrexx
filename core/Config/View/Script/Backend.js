(function($){
    $(document).ready(function(){
        // initialize captcha config
        $('#captchaMethod').change(function() {
            var displayReCaptcha = ($.trim($(this).val()) === 'contrexxCaptcha') ? 'none' : 'table-row';
            $('input[name="recaptchaSiteKey"]').closest('tr').css('display', displayReCaptcha);
            $('input[name="recaptchaSecretKey"]').closest('tr').css('display', displayReCaptcha);
        });
        $('#captchaMethod').trigger('change');

        // initialize client side script upload config
        $('#allowClientsideScriptUpload').change(function() {
            var displayClientSideGroupSelection = ($.trim($(this).val()) === 'groups') ? 'table-row' : 'none';
            $('select[name="allowClientSideScriptUploadOnGroups[]"]').closest('tr').css('display', displayClientSideGroupSelection);
        });
        $('#allowClientsideScriptUpload').trigger('change');

        // Show cookieNoteTtl options based on cookieNote on or off
        jQuery('input[name="cookieNote"]').change(function() {
            var cookieNoteOpt = jQuery('input[name="cookieNote"]:checked').val();
            if (cookieNoteOpt === 'off') {
                jQuery('select[name="cookieNoteTtl"]').closest('tr').hide();
            } else {
                jQuery('select[name="cookieNoteTtl"]').closest('tr').show();
            }
        });
        jQuery('input[name="cookieNote"]').trigger('change');
    });
})(cx.jQuery);