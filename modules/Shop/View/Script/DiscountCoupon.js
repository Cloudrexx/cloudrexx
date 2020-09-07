cx.ready(function() {
    loadGenerateCodeButton();

    var storeValues = {};
    cx.jQuery('.shop-unlimited').change(function() {
        var fieldName = cx.jQuery(this).attr('name');
        storeValues[fieldName] = toggleLimitedField(cx.jQuery(this), storeValues[fieldName]);
    });
    cx.jQuery(".shop-unlimited").trigger("change");

    toggleCouponType();
    cx.jQuery('input[name="coupon_type"]').click(function () {
        toggleCouponType();
    });

    cx.jQuery('input[name="global"]').click(function () {
        cx.jQuery('#user-live-search').toggle();
    });

    cx.jQuery('.coupon-url-link').blur(function () {
        cx.jQuery('#coupon_uri_aj9GpJtM-1').hide();
    });

    cx.jQuery('.coupon-url-link').blur(function () {
        cx.jQuery(this).hide();
    });

    cx.jQuery('.coupon-url-link').focus(function () {
        cx.jQuery(this).select();
    });

    cx.jQuery('.coupon-url-icon').click(function() {
        cx.jQuery(this).next().show();
    });
});


function loadGenerateCodeButton() {
    const codeInput = cx.jQuery('form.discountcoupon #form-0-code');

    if (codeInput.length == 0 || codeInput.val().length > 0) {
        return;
    }

    const code = cx.variables.get('SHOP_GET_NEW_DISCOUNT_COUPON', 'Shop');
    const loadCodeEvent = 'cx.jQuery(\'#form-0-code\').val(\''+ code +'\'); cx.jQuery(this).css(\'display\', \'none\');';
    const generateCodeButton = '<input type="button" id="vg-0-create-code" tabindex="14" value="' +
        cx.variables.get('TXT_SHOP_GENERATE_NEW_CODE', 'Shop') + '" onclick="'+ loadCodeEvent +'" />';

    codeInput.after(generateCodeButton);
}

function toggleLimitedField(checkbox, oldValue) {
    if (oldValue == undefined) {
        oldValue = checkbox.prev().val();
    }
    if (checkbox.is(':checked')) {
        oldValue = checkbox.prev().val();
        checkbox.prev().attr('disabled', true);
        checkbox.prev().data('date-class', checkbox.prev().attr('class'));
        checkbox.prev().attr('class', '');
        checkbox.prev().val('');
    } else {
        checkbox.prev().removeAttr('disabled');
        checkbox.prev().attr('class', checkbox.prev().data('date-class'));
        checkbox.prev().val(oldValue);
    }
    return oldValue;
}

function toggleCouponType() {
    if (cx.jQuery('#discountRate').is(':checked')) {
        cx.jQuery('#group-0-discountAmount').hide();
        cx.jQuery('#group-0-subjectToVat').hide();
        cx.jQuery('#group-0-discountRate').show();
    } else {
        cx.jQuery('#group-0-discountRate').hide();
        cx.jQuery('#group-0-discountAmount').show();
        cx.jQuery('#group-0-subjectToVat').show();
    }
}
