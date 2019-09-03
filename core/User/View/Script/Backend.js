/**
 * This file is loaded by the abstract SystemComponentBackendController
 * You may add own JS files using
 * \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/FileName.css', 1));
 * or remove this file if you don't need it
 */

jQuery(document).ready(function($){
    if ( cx.jQuery( "#form-0-emailAccess" ).length ) {
        var value = $( "#form-0-emailAccess" ).val();

        cx.jQuery( '[name="emailAccess"]' ).val(value);
    }

    if ( cx.jQuery( "#form-0-backendLangId" ).length ) {
        var value = cx.jQuery( "#form-0-backendLangId" ).val();

        cx.jQuery( '[name="backendLangId"]' ).val(value);
    }

    jQuery('#form-0-primaryGroup').change(function() {
        var optionId = jQuery(this).find(":selected").val();
        jQuery('#form-0-group option[value="'+optionId+'"]').attr('selected', true);
        jQuery("#form-0-group > option:selected").each(function() {
            jQuery(this).attr('selected', true);
        });
        jQuery('#form-0-group').trigger("chosen:updated");
    });

    // START password
    /**
     * Checks the complexity of the given password.
     * @param string password
     */
    var checkComplexity = function(password) {
        // scope for variables
        var scope = 'user-password';

        // Initial strength
        var strength = 0;

        // If the password is empty, return message.
        if (password.length == 0) {
            cx.jQuery('#password-complexity').removeClass();
            return '';
        }

        // If the password length is less than 6, return message.
        if (password.length < 6) {
            cx.jQuery('#password-complexity').removeClass();
            cx.jQuery('#password-complexity').addClass('short');
            return cx.variables.get('TXT_CORE_USER_PASSWORD_TOO_SHORT', scope);
        }

        if (cx.variables.get('CORE_USER_PASSWORT_COMPLEXITY', scope) == 'on') {
            if (!password.match(/([a-zA-Z])/) || !password.match(/([0-9])/) || !password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {
                cx.jQuery('#password-complexity').removeClass();
                cx.jQuery('#password-complexity').addClass('short');
                return cx.variables.get('TXT_CORE_USER_PASSWORD_INVALID', scope);
            }
        }

        // Length is ok, lets continue.

        // If length is 8 characters or more, increase strength value.
        if (password.length > 7) strength += 1;

        // If password contains both lower and uppercase characters, increase strength value.
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;

        // If it has numbers and characters, increase strength value.
        if (password.match(/([a-zA-Z])/) && password.match(/([0-9])/)) strength += 1;

        // If it has one special character, increase strength value.
        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;

        // If it has two special characters, increase strength value.
        if (password.match(/(.*[!,%,&,@,#,$,^,*,?,_,~].*[!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;

        // Now we have calculated strength value, we can return messages.

        // If value is less than 2.
        if (strength < 2 ) {
            cx.jQuery('#password-complexity').removeClass().addClass('weak');
            return cx.variables.get('TXT_CORE_USER_PASSWORD_WEAK', scope);
        } else if (strength == 2 ) {
            cx.jQuery('#password-complexity').removeClass().addClass('good');
            return cx.variables.get('TXT_CORE_USER_PASSWORD_GOOD', scope);
        } else {
            cx.jQuery('#password-complexity').removeClass().addClass('strong');
            return cx.variables.get('TXT_CORE_USER_PASSWORD_STRONG', scope);
        }
    }

    cx.jQuery('input[name=password]').keyup(function(){
        cx.jQuery('#password-complexity').html(checkComplexity(cx.jQuery(this).val()))
    })

    cx.jQuery('#password-complexity').html(checkComplexity(cx.jQuery('input[name=password]').val()));

    cx.jQuery('input[name="notification_email"]').change(function() {
        if(cx.jQuery(this).val()==1) {
            cx.jQuery('.password-field').hide();
            cx.jQuery('.password-field input').val('');
            cx.jQuery('#password-complexity').removeClass().html('');
        } else if(cx.jQuery(this).val()==0) {
            cx.jQuery('.password-field').show();
        }
    });

    if(cx.jQuery('input[name="notification_email"]').is(':visible')) {
        cx.jQuery('input[name="notification_email"]:checked').trigger('change');
    }

    // END password
});
