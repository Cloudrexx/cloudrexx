 function cx_multisite_signup(defaultOptions) {
    var options = defaultOptions;
    var ongoingRequest = false;
    var ongoingSetup = false;
    var submitRequested = false;
    var submitButtonRequested = false;
    var signUpForm;
    var objModal;
    var objMail;
    var objAddress;
    var objTerms;
    var objPayButton;
    var isPaymentUrlRequested = false;

    function initSignUpForm() {
        jQuery('#multisite_signup_form')
                .bootstrapValidator()
                .on('success.field.bv', function() {
                  if (submitButtonRequested) {
                    submitButtonRequested = false;
                    if (options.IsPayment) {
                      // jQuery('.multisite_pay').modal('show');
                    } else {
                      submitForm();
                    }                    
                  }
                })
                .on('error.field.bv', function() {
                  submitButtonRequested = false;
                });
        signUpForm = jQuery('#multisite_signup_form');
        objModal = signUpForm.parents('.modal');
        objModal.on('show.bs.modal', init);
        objModal.find('.multisite_cancel').on('click', cancelSetup);

        signUpForm.submit(submitForm);

        objMail = objModal.find('#multisite_email_address');
        objMail.bind('change', verifyEmail);

        objAddress = objModal.find('#multisite_address');
        objAddress.bind('change', verifyAddress);

        objTerms = objModal.find('#multisite_terms');
        objTerms.bind('change', verifyTerms);

        objModal.find('.multisite_submit').on('click', submitForm);
        objModal.find('.multisite_pay').on('click', setPaymentUrl);
        
        objPayButton = objModal.find('.multisite_pay_button');
        init();
        
    }

    function cancelSetup() {
        ongoingRequest = false;
        ongoingSetup = false;
        submitRequested = false;
    }

    function init() {
        if (ongoingRequest) {
            return;
        }

        if (typeof(cx_multisite_options) != 'undefined') {
            options = cx_multisite_options;
        }

        setFormHeader(options.headerInitTxt);
        hideProgress();
        showForm();

        clearFormStatus();

        if (typeof(options.email) == 'string' && !objMail.val()) {
            objMail.val(options.email);
        }
        //objMail.data('valid', false);
        objMail.data('verifyUrl', options.emailUrl);

        if (typeof(options.address) == 'string' && !objAddress.val()) {
            objAddress.val(options.address);
        }
        //objAddress.data('valid', false);
        objAddress.data('verifyUrl', options.addressUrl);

        //objTerms.data('valid', false);
        objTerms.change();

        setFormButtonState('close', false);
        setFormButtonState('cancel', true, true);
        if (options.IsPayment) {
            objPayButton.payrexxModal({
                hideObjects: ["#contact-details", ".contact"],
                show: function(e) {
                    //signup form validation and check valid payment
                    if (!formValidation() || !isPaymentUrlValid()) {
                        return e.preventDefault();
                    }

                    return true;
                },
                hidden: function(transaction) {
                    switch (transaction.status) {
                        case 'confirmed':
                            setFormButtonState('pay', false);
                            callSignUp();
                            break;
                        case 'waiting':
                        case 'cancelled':
                        default:
                            setFormButtonState('pay', false);
                            setFormButtonState('submit', true, true);
                            break;
                    }
                }
            });
            setFormButtonState('submit', false);
            setFormButtonState('pay', true, true);
        } else {
            setFormButtonState('pay', false);
            setFormButtonState('submit', true, true);
        }


        if (objTerms.length) {
            jQuery("#multisite_signup_form").data('bootstrapValidator').updateStatus('agb', 'NOT_VALIDATED');
        }
        if (objMail.length) {
            jQuery("#multisite_signup_form").data('bootstrapValidator').updateStatus('multisite_email_address', 'NOT_VALIDATED');
        }
        if (objAddress.val() == ''){
            jQuery("#multisite_signup_form").data('bootstrapValidator').updateStatus('multisite_address', 'NOT_VALIDATED');
        }
        else {
            jQuery(objAddress).trigger('change');
        }
    }

    function verifyEmail() {
        verifyInput(this, {multisite_email_address : jQuery(this).val()});
    }

    function verifyAddress() {
        verifyInput(this, {multisite_address : jQuery(this).val().toLowerCase()});
    }

    function verifyTerms() {
        verifyInput(this);
    }

    function verifyInput(domElement, data) {
        jQuery(domElement).data('server-msg', '');
        jQuery("#multisite_signup_form").data('bootstrapValidator').validateField('multisite_address');
        jQuery(domElement).data('valid', false);
        jQuery(domElement).prop('disabled', true);
        if (jQuery(domElement).data('verifyUrl')) {
            jQuery.ajax({
                dataType: "json",
                url: jQuery(domElement).data('verifyUrl'),
                data: data,
                type: "POST",
                success: function(response){parseResponse(response, domElement);}
            });
        } else {
            parseResponse({status:'success',data:{status:'success'}}, domElement);
        }
    }

    function formValidation() {
        jQuery("#multisite_signup_form").data('bootstrapValidator').validate();
        if (!isFormValid() || !jQuery("#multisite_signup_form").data('bootstrapValidator').isValid()) {
            return false;
        }
        
        return true;
    }
    
    function isPaymentUrlValid() {
        var urlPattern = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
        var url = objPayButton.data('href');
        
        jQuery('.alert-danger').remove();
        if (!urlPattern.test(url)) {
            jQuery('<div class="alert alert-danger" role="alert">Invalid Payrexx Form Url</div>').insertAfter(jQuery('#product_id'));
            return false;
        }
        
        return true;
    }
    
    function setPaymentUrl() {
        if (isPaymentUrlRequested) {
          return;
        }
        if (!formValidation()) {
            return;
        }
        isPaymentUrlRequested = true;
        try {
            jQuery.ajax({
                dataType: "json",
                url: options.paymentUrl,
                data: {
                    multisite_email_address : objMail.val(),
                    multisite_address : objAddress.val(),
                    product_id : jQuery("#product_id").val(),
                    renewalOption: options.renewalOption
                },
                type: "POST",
                beforeSend: function (xhr, settings) {
                    objModal.find('.multisite_pay').button('loading');
                    objModal.find('.multisite_pay').prop('disabled', true);
					jQuery('.multisite_pay').removeClass('btn-primary');
                },
                success: function(response){
                    isPaymentUrlRequested = false;
                    if (response.status == 'error') {
                        return;
                    }
                    
                    if (response.status == 'success' && response.data.link) {
                        objPayButton.data('href', response.data.link);                        
                        objPayButton.trigger('click');
                    }
                },
                complete: function (xhr, settings) {
                    objModal.find('.multisite_pay').button('reset');
                    objModal.find('.multisite_pay').prop('disabled', false);
                }
            });
        } catch (e) {
            console.log(e);
        }
    }
    
    function verifyForm() {
        isFormValid();
    }

    function submitForm() {
        try {
            
            if (!formValidation()) {
                submitButtonRequested = true;
                return;
            }

            setFormButtonState('submit', false);

            if (submitRequested) {
                return;
            }
            //signUpForm.find(':input').prop('disabled', true);
            submitRequested = true;
            callSignUp();
        } catch (e) {

        }

        // always return false. We don't want to form to get actually submitted
        // as everything is done using AJAX
        return false;
    }

    function isFormValid() {
        if (objMail.length && !objMail.data('valid')) {
            return false;
        }

        if (objAddress.length && !objAddress.data('valid')) {
            return false;
        }

        if (objTerms.length && !objTerms.data('valid')) {
            return false;
        }

        return true;

    }
    function setFormHeader(headerTxt) {
        objModal.find('.modal-header .modal-title').html(headerTxt);
    }

    function setFormButtonState(btnName, show, active) {
        var btn = objModal.find('.multisite_' + btnName);
        show ? btn.show() : btn.hide();
        btn.prop('disabled', !active);
    }
    
    function callSignUp() {
        try {
            ongoingRequest = true;
            setFormButtonState('close', true, true);
            setFormButtonState('cancel', false, false);
            setFormHeader(options.headerSetupTxt);

            hideForm();
            showProgress();

            trackConversions();

            jQuery.ajax({
                dataType: "json",
                url: options.signUpUrl,
                data: {
                    multisite_email_address : objMail.val(),
                    multisite_address : objAddress.val(),
                    product_id : jQuery("#product_id").val(),
                    renewalOption: options.renewalOption
                },
                type: "POST",
                success: function(response){parseResponse(response, null);},
                error: function() {
                    showSystemError();
                }
            });
        } catch (e) {
            console.log(e);
        }
    }

    /**
     * @param {{data:{loginUrl}}} response The url to which the user gets redirected if auto-login is active.
     * @param {jQuery} objCaller
     */
    function parseResponse(response, objCaller) {
        var type, message, errorObject,errorMessage,errorType;
        hideProgress();

        if (!response.status) {
            showSystemError();
            return;
        }

        // handle form validation
        if (objCaller) {
            jQuery(objCaller).prop('disabled', false);

            // fetch verification state of form element
            if (response.status == 'success') {
                jQuery(objCaller).data('server-msg', '');
                jQuery(objCaller).data('valid', true);

                jQuery("#multisite_signup_form").data('bootstrapValidator').revalidateField(jQuery(objCaller).attr('name'));
                return true;
            } else {
                type = 'danger';
                message = response.message;
                if (typeof(response.message) == 'object') {
                    message = typeof(response.message.message) != null ? response.message.message : null;
                    type = typeof(response.message.type) != null ? response.message.type : null;
                }
                jQuery(objCaller).data('server-msg', message);
            }

            jQuery("#multisite_signup_form").data('bootstrapValidator').revalidateField(jQuery(objCaller).attr('name'));


            verifyForm();

            return;
        }

        // handle signup
        switch (response.status) {
            case 'success':
                // this is a workaround for 
                if (!response.message && !response.data) {
                    showSystemError();
                    return;
                }
                
                if (options.callBackOnSuccess && typeof  options.callBackOnSuccess === 'function') {
                    options.callBackOnSuccess(response.data);
                }

                // fetch message
                message = response.data.message;

                // redirect to website, in case auto-login is active
                if (message == 'auto-login') {
                    setFormButtonState('close', false);
                    setFormButtonState('cancel', false);
                    setFormButtonState('submit', false);
                    setFormHeader(options.headerSuccessTxt);
                    setFormStatus('success', options.messageRedirectTxt);
                    window.location.href = response.data.loginUrl;
                    return;
                }else if(response.data.reload){
                    location.reload();
                }

                setMessage(message, 'success');
                break;

            case 'error':
            default:
                errorObject = null;
                errorType = 'danger';
                errorMessage = response.message;
                if (typeof(response.message) == 'object') {
                    errorObject = typeof(response.message.object) != null ? response.message.object : null;
                    errorMessage = typeof(response.message.message) != null ? response.message.message : null;
                    errorType = typeof(response.message.type) != null ? response.message.type : null;
                }
                setMessage(errorMessage, errorType, errorObject);
                break;
        }
    }

    function setMessage(message, type, errorObject) {
        var objElement;
        if (!type) type = 'info';
        objElement = null;

        switch (errorObject) {
            case 'email':
                objElement = objMail;
                /* FALLTHROUGH */
            case 'address':
                if (!objElement) objElement = objAddress;

                setFormHeader(options.headerInitTxt);
                setFormButtonState('close', false);
                setFormButtonState('cancel', true, true);
                hideProgress();
                showForm();
                jQuery('<div class="alert alert-' + type + '" role="alert">' + message + '</div>').insertAfter(objElement);
                objElement.data('valid', false);
                cancelSetup();

                jQuery("#multisite_signup_form").data('bootstrapValidator').updateStatus('multisite_address', 'NOT_VALIDATED');
                break;

            case 'form':
                setFormHeader(options.headerErrorTxt);
                setFormButtonState('close', false);
                setFormButtonState('cancel', true, true);
                hideForm();
                hideProgress();
                setFormStatus(type, message);
                cancelSetup();
                break;

            default:
                setFormHeader(options.headerSuccessTxt);
                setFormButtonState('close', true, true);
                setFormButtonState('cancel', false);
                hideForm();
                hideProgress();
                setFormStatus(type, message);
                cancelSetup();
                break;
        }
    }

    function showSystemError() {
        setMessage(options.messageErrorTxt, 'danger');
    }

    function showForm() {
        objModal.find('.multisite-form').show();
        jQuery('#multiSiteSignUp').find('.modal-body').css({'min-height': jQuery('#multiSiteSignUp').find('.multisite-form').height()});
    }

    function hideForm() {
        objModal.find('.multisite-form').hide();
    }

    function showProgress() {
        var message = options.messageBuildTxt;
        message = message.replace('%1$s', '<a href="mailto:' + objMail.val() + '">' + objMail.val() + '</a>');
        message = message.replace('%2$s', '<a href="https://' + objAddress.val() + '.' + options.multisiteDomain + '" target="_blank">https://' + objAddress.val() + '.' + options.multisiteDomain + '</a>');
        objModal.find('.multisite-progress div').html(message);
        objModal.find('.multisite-progress').show();
    }

    function hideProgress() {
        objModal.find('.multisite-progress').hide();
    }

    function clearFormStatus() {
        objModal.find('.multisite-status').hide();
        objModal.find('.multisite-status').children().remove();
    }

    function setFormStatus(type, message) {
        clearFormStatus();
        objModal.find('.multisite-status').append('<div class="alert alert-' + type + '" role="alert">' + message + '</div>');
        objModal.find('.multisite-status').show();
    }

    function trackConversions() {
        // check if conversion tracking shall be done
        if (!options.conversionTrack) {
            return;
        }
        
        price = options.productPrice;
        currency = options.orderCurrency;
        trackGoogleConversion(price, currency);
        trackFacebookConversion(price, currency);
    }

    function trackGoogleConversion(price, currency) {
        // check if google conversion tracking shall be done
        if (!options.trackGoogleConversion) {
            return;
        }

        jQuery.getScript('//www.googleadservices.com/pagead/conversion_async.js', function() {
            goog_snippet_vars = function() {
                var w = window;
                w.google_conversion_id = options.googleConversionId;
                w.google_conversion_label = "ujWnCMeCvF4Q3eSdxgM";
                w.google_remarketing_only = false;
                w.google_conversion_value  = price;
                w.google_conversion_currency = currency;
            }
            // DO NOT CHANGE THE CODE BELOW.
            goog_report_conversion = function(url) {
                goog_snippet_vars();
                window.google_conversion_format = "3";
                window.google_is_call = true;
                var opt = new Object();
                opt.onload_callback = function() {
                    if (typeof(url) != 'undefined') {
                        window.location = url;
                    }
                }
                var conv_handler = window['google_trackConversion'];
                if (typeof(conv_handler) == 'function') {
                    conv_handler(opt);
                }
            }
            goog_report_conversion();
        });
    }

    function trackFacebookConversion(price, currency) {
        // check if facebook conversion tracking shall be done
        if (!options.trackFacebookConversion) {
            return;
        }

        var _fbq = window._fbq || (window._fbq = []);
        jQuery.getScript('//connect.facebook.net/en_US/fbds.js', function() {
            _fbq.loaded = true;
        });

        window._fbq = window._fbq || [];
        window._fbq.push(['track', options.facebookConversionId, {'value':price,'currency':currency}]);
    }

    initSignUpForm();
}

jQuery(document).ready(cx_multisite_signup(cx_multisite_options));
