(function($){
    $(document).ready(function(){
        $('#captchaMethod').change(function() {
            var displayReCaptcha = ($.trim($(this).val()) === 'contrexxCaptcha') ? 'none' : 'table-row';
            $('input[name="recaptchaSiteKey"]').closest('tr').css('display', displayReCaptcha);
            $('input[name="recaptchaSecretKey"]').closest('tr').css('display', displayReCaptcha);
        });
        $('#captchaMethod').trigger('change');
    });
})(cx.jQuery);
