(function ($) {
    cx.ready(function () {
        cx.bind("loadingStart", cx.lock, "websiteBackup");
        cx.bind("loadingEnd", cx.unlock, "websiteBackup");

        if ($('#serviceServerList').length > 0) {
            $.trim(cx.variables.get('serviceServers', 'multisite/lang')) == ''
            ? $J('#serviceServerOption').remove()
            : $('#serviceServerList').append(getEditOption('dropdown', 'serviceServer', 'serviceServer', '', cx.variables.get('serviceServers', 'multisite/lang')));            
        }
        
        cx.jQuery('#upload_backup').click(function(){
            if (cx.variables.get('showServiceSelectionModal', 'multisite/lang')) {
                var buttons = [
                    {
                        text: cx.variables.get('websiteRestoreOkButton', 'multisite/lang'),
                        click: function () {
                            $that = cx.jQuery(this);
                            var serviceServerUrl = cx.jQuery('#chooseServiceServer .backup_service_server').val();
                            if (serviceServerUrl != '') {
                                var ajaxUrl = serviceServerUrl + '/cadmin/?cmd=JsonData&object=MultiSite&act=getUploaderId';
                                cx.jQuery.ajax({
                                    url: ajaxUrl,
                                    // allow to include cookies in request
                                    crossDomain: true,
                                    xhrFields: {
                                        withCredentials: true
                                    },
                                    headers: {
                                        'Check-CSRF': 'false'
                                    },
                                    dataType: 'json',
                                    type: 'POST',
                                    beforeSend: function () {
                                        cx.trigger("loadingStart", "websiteBackup", {});
                                        cx.tools.StatusMessage.showMessage("<div id=\"loading\" class = \"websiteBackup\">" + cx.jQuery('#loading').html() + "</div>");
                                    },
                                    success: function (response) {
                                        var resp = (response.data) ? response.data : response;
                                        if (resp.status == 'success') {
                                            var serviceUploaderId = resp.id;
                                            var uploaderId = cx.jQuery('#multisite_backup_upload_btn').data('uploaderId');
                                            var controllerScope = angular.element(jQuery('#uploader-modal-'+ uploaderId)).scope();
                                            controllerScope.plUrl = serviceServerUrl + '/cadmin/?cmd=JsonData&object=Uploader&act=upload&id='+ serviceUploaderId;
                                            controllerScope.plInstance.settings.url = serviceServerUrl + '/cadmin/?cmd=JsonData&object=Uploader&act=upload&id='+ serviceUploaderId;
                                            $that.dialog("close");
                                            cx.tools.StatusMessage.removeAllDialogs();
                                            cx.trigger("loadingEnd", "websiteBackup", {});
                                            cx.jQuery('#multisite_backup_upload_btn').trigger('click');
                                        }
                                    }
                                });
                            }
                        }
                    },
                    {
                        text: cx.variables.get('websiteRestoreCancelButton', 'multisite/lang'),
                        click: function () {
                            cx.jQuery(this).dialog("close");
                        }
                    }
                ];
                cx.jQuery('#chooseServiceServer').dialog({
                    width: 650,
                    height: 350,
                    autoOpen: true,
                    modal: true,
                    buttons: buttons,
                    close: function () {
                        $J(this).dialog("destroy");
                    }
                });
            } else {
                cx.jQuery('#multisite_backup_upload_btn').trigger('click');
            }
        });

        cx.bind("userSelected", MultisiteBackupAndRestore.showSubscriptionSelection, "user/live-search/restoreUserId");
        cx.bind("userCleared", function () {
            $('#subscriptionSelection').hide();
            $('#SubscriptionOption').hide();
            $('#subscriptionSelection #subscriptionList').html('');
        }, "user/live-search/restoreUserId");

        //Create a backup of website(s)
        $('.websiteBackup').click(function () {
            var iAttr = $(this).data('params').split(':');
            var params = '';
            if (!iAttr[0] || !iAttr[1]) {
                return false;
            }

            switch (iAttr[0]) {
                case 'service':
                    params = {serviceServerId: iAttr[1], responseType: 'json'};
                    break;
                case 'website':
                    params = {websiteId: iAttr[1], responseType: 'json'};
                    break;
                default:
                    return false;
                    break;
            }
            if (!confirm(cx.variables.get('websiteBackupConfirm', 'multisite/lang'))) {
                return false;
            }

            $.ajax({
                url: cx.variables.get('cadminPath', 'contrexx') + "?cmd=JsonData&object=MultiSite&act=triggerWebsiteBackup",
                data: params,
                type: "POST",
                dataType: "json",
                beforeSend: function () {
                    cx.trigger("loadingStart", "websiteBackup", {});
                    cx.tools.StatusMessage.showMessage("<div id=\"loading\" class = \"websiteBackup\">" + cx.jQuery('#loading').html() + "</div>");
                    $('#loading > span').html(cx.variables.get('websiteInProgress', 'multisite/lang'));
                },
                success: function (response) {
                    var $resp = (response.data) ? response.data : response;
                    cx.tools.StatusMessage.showMessage($resp.message ? $resp.message : $resp, null, 2000);
                    cx.trigger("loadingEnd", "websiteBackup", {});
                }
            });

        });

        //Website restore by clicking restore button
        $('.websiteRestore').click(function () {
            var userId = $(this).attr('data-userId') != undefined ? $(this).attr('data-userId') : 0;
            var serviceServerId = $(this).attr('data-serviceId') != undefined ? $(this).attr('data-serviceId'): 0;
            var params = {backupedServiceServer: serviceServerId, websiteBackupFileName: $(this).attr('data-backupFile')};
            MultisiteBackupAndRestore.websiteRestore(params, false, userId);
        });

        //Remove Backuped website in the service server
        $('.deleteWebsiteBackup').click(function () {
            if (!confirm(cx.variables.get('websiteBackupDeleteConfirm', 'multisite/lang'))) {
                return false;
            }

            $.ajax({
                url: cx.variables.get('cadminPath', 'contrexx') + "?cmd=JsonData&object=MultiSite&act=triggerWebsiteBackup",
                data: {serviceServerId: $(this).attr('data-serviceId'), websiteBackupFileName: $(this).attr('data-backupFile')},
                type: "POST",
                dataType: "json",
                beforeSend: function () {
                    cx.trigger("loadingStart", "deleteWebsiteBackup", {});
                    cx.tools.StatusMessage.showMessage("<div id=\"loading\">" + cx.jQuery('#loading').html() + "</div>");
                    $('#loading > span').html(cx.variables.get('websiteBackupDeleteInProgress', 'multisite/lang'));
                },
                success: function (response) {
                    var $resp = (response.data) ? response.data : response;
                    if ($resp.status == 'success') {
                        location.reload();
                    }
                    cx.tools.StatusMessage.showMessage($resp.message, null, 2000);
                    cx.trigger("loadingEnd", "deleteWebsiteBackup", {});
                }
            });
        });

    });
})(jQuery);

function websiteRestoreCallbackJs(callback) {
    if ($J.trim(callback[0]) !== '') {
        var params = {uploadedFilePath: callback[0]};
        if (cx.variables.get('showServiceSelectionModal', 'multisite/lang')) {
            params['backupedServiceServer'] = cx.jQuery('#chooseServiceServer .backup_service_server option:selected').data('id');
        }
        $J.ajax({
            url: cx.variables.get('cadminPath', 'contrexx') + "?cmd=JsonData&object=MultiSite&act=checkUserStatusOnRestore",
            data: params,
            dataType: 'json',
            type: 'POST',
            beforeSend: function () {
                cx.trigger("loadingStart", "websiteBackup", {});
                cx.tools.StatusMessage.showMessage("<div id=\"loading\" class = \"websiteBackup\">" + cx.jQuery('#loading').html() + "</div>");
            },
            success: function (response) {
                var resp = (response.data) ? response.data : response;
                if (resp.status === 'success') {
                    cx.tools.StatusMessage.removeAllDialogs(true);
                    cx.trigger("loadingEnd", "websiteBackup", {});
                    MultisiteBackupAndRestore.websiteRestore(params, true, resp.userId ? resp.userId : 0);
                }
            }
        });
    }
}

var MultisiteBackupAndRestore = {
    websiteRestore: function (data, upload, userId) {
        MultisiteBackupAndRestore.showUserOrSubscriptionSelection(userId);

        // check availability of the website name
        $J('#restore_websiteName #restoreWebsiteName').bind('change', MultisiteBackupAndRestore.checkWebsiteNameOnRestore);
        $J('#userSelection .selectUserType').bind('change', MultisiteBackupAndRestore.validateInputOnRestore);

        var buttons = [
            {
                text: cx.variables.get('websiteRestoreButton', 'multisite/lang'),
                class: 'websiteRestoreButton',
                click: function () {
                    MultisiteBackupAndRestore.validateInputOnRestore('#restoreWebsite .selectUserType', '#userSelection', 'validateUserSelection');
                    MultisiteBackupAndRestore.validateInputOnRestore('#restore_websiteName .restoreWebsiteName', '#restore_websiteName', 'validateWebsiteName');

                    if (   $J('#restoreWebsiteForm').attr('validUserSelection') == false
                        || $J('#restoreWebsiteForm').attr('websiteNameValid') == false
                     ) {
                        $J('.websiteRestoreButton').attr('disabled', true);
                        $J('#restoreWebsite #restoreform_error').show();
                        return false;
                    }

                    $J('.websiteRestoreButton').attr('disabled', false);
                    $J('#restoreWebsite #restoreform_error').hide();
                    if (!confirm(cx.variables.get('websiteRestoreConfirm', 'multisite/lang'))) {
                        return false;
                    }

                    var selectedUserId = $J("input:radio[name='selectUserType']:checked").val() == '2'
                            ? $J('#restoreUserId').val()
                            : 0;
                    var subscriptionId = ($J('#subscriptionList .subscriptionOptions').length != 0) && $J("input:radio[name='subscription']:checked").val() == '2'
                            ? $J('#subscriptionList .subscriptionOptions').val()
                            : 0;
                    var params = {
                        uploadedFilePath: upload ? data.uploadedFilePath : '',
                        backupedServiceServer: data.backupedServiceServer,
                        websiteBackupFileName: !upload ? data.websiteBackupFileName : '',
                        restoreOnServiceServer: $J('#restoreWebsite .serviceServer').length > 0
                                                ? $J('#restoreWebsite .serviceServer').val()
                                                : 0,
                        restoreWebsiteName: $J('#restoreWebsite #restoreWebsiteName').val(),
                        responseType: 'json',
                        selectedUserId: selectedUserId,
                        subscriptionId: subscriptionId
                    };

                    $J.ajax({
                        url: cx.variables.get('cadminPath', 'contrexx') + "?cmd=JsonData&object=MultiSite&act=triggerWebsiteRestore",
                        data: params,
                        type: "POST",
                        dataType: "json",
                        beforeSend: function () {
                            $J('#restoreWebsite').dialog("close");
                            cx.trigger("loadingStart", "websiteRestore", {});
                            cx.tools.StatusMessage.showMessage("<div id=\"loading\" class = \"websiteBackup\">" + cx.jQuery('#loading').html() + "</div>");
                            $J('#loading > span').html(cx.variables.get('websiteRestoreInProgress', 'multisite/lang'));
                        },
                        success: function (response) {
                            var $resp = (response.data) ? response.data : response,
                                    $message = ($resp.message) ? $resp.message : $resp;
                            if (typeof ($resp.websiteUrl) != "undefined" && $resp.websiteUrl !== null) {
                                window.open($resp.websiteUrl, '_blank').focus();
                            }
                            cx.tools.StatusMessage.showMessage($message, null, 2000);
                            cx.trigger("loadingEnd", "websiteRestore", {});
                        }
                    });
                }
            },
            {
                text: cx.variables.get('websiteRestoreCancelButton', 'multisite/lang'),
                click: function () {
                    $J(this).dialog("close");
                }
            }
        ];

        $J('#restoreWebsite').dialog({
            width: 650,
            height: 350,
            autoOpen: true,
            modal: true,
            buttons: buttons,
            close: function () {
                $J(this).dialog("destroy");
                MultisiteBackupAndRestore.resetModalValuesOnRestore($J(this));
            }
        });
    },
    resetModalValuesOnRestore: function ($element) {
        $J('#restoreWebsite .serviceServer').val('');
        $J('#restoreWebsite #restoreWebsiteName').val('');
        $J('#restoreWebsite #restoreform_error').hide();
        $element.find('.restore_error').html('').hide();
        $element.find("*").removeClass("border-red");
        $element.find("form#restoreWebsiteForm").removeAttr("validuserselection");
        $element.find("form#restoreWebsiteForm").removeAttr("websitenamevalid");
    },
    checkWebsiteNameOnRestore: function () {
        var $restoreForm = $J('#restoreWebsiteForm');
        var errElement = $J('#restore_websiteName').find('.restore_error');
        $J('.websiteRestoreButton').attr('disabled', true);
        jQuery.ajax({
            dataType: "json",
            url: cx.variables.get('cadminPath', 'contrexx') + "?cmd=JsonData&object=MultiSite&act=address",
            data: {multisite_address: $J(this).val()},
            type: "POST",
            success: function (response) {
                var errorMessage = (response.message.message)
                        ? response.message.message
                        : '';
                MultisiteBackupAndRestore.parseErrorMessageOnRestore($restoreForm, 'websiteNameValid', errElement, '.restoreWebsiteName', errorMessage, !errorMessage);
                if (   $J('#restoreWebsiteForm').attr('validUserSelection') == true
                    && $J('#restoreWebsiteForm').attr('websiteNameValid')  == true
                ) {
                    $J('.websiteRestoreButton').attr('disabled', false).trigger('click');
                }
            }
        });
    },
    parseErrorMessageOnRestore: function (form, errorAttr, errElem, field, errorMsg, valid) {
        (valid) ? form.attr(errorAttr, true) : form.attr(errorAttr, false);
        (valid) ? errElem.html('').hide() : errElem.html(errorMsg).show();
        (valid) ? $J(field).removeClass('border-red') : $J(field).addClass('border-red');
    },
    validateInputOnRestore: function ($this, error_block, inputType) {
        $this = ($this) ? $this : $J(this);
        error_block = (error_block) ? error_block : '#userSelection';

        var $restoreForm = $J('#restoreWebsiteForm');
        if (inputType == 'validateWebsiteName'
                && $restoreForm.attr('websiteNameValid') == 'false'
                ) {
            return false;
        }

        var errElement   = $J(error_block).find('.restore_error'),
            errorMessage = (inputType == 'validateWebsiteName')
                            ? cx.variables.get('websiteNameRequired', 'multisite/lang')
                            : cx.variables.get('websiteUserRequired', 'multisite/lang'),
            errorAttr    = (inputType == 'validateWebsiteName')
                            ? 'websiteNameValid'
                            : 'validUserSelection',
            valid        = (inputType == 'validateWebsiteName')
                            ? !$J($this + '#restoreWebsiteName').val().match(/^[a-z0-9]+$/)
                            : ($J($this + ":checked").val() == '2') && $J('#restoreUserId').val() == 0;
        MultisiteBackupAndRestore.parseErrorMessageOnRestore($restoreForm, errorAttr, errElement, $this, errorMessage, !valid);
    },
    showUserOrSubscriptionSelection: function (userId, selectedType) {
        var userFromBackupObj = $J("#createUserFromBackup");
        switch (selectedType) {
            case 'subscriptionOption':
                $J("input:radio[name='subscription']:checked").val() == '2'
                        ? $J('#subscriptionSelection').show()
                        : $J('#subscriptionSelection').hide();
                break;
            default:
                $J('.live-search-user-clear').trigger('click');
                $J('.live-search-user-add').hide();
                if (userFromBackupObj.parent('label').is(':hidden')) {
                    userFromBackupObj.attr('checked', true).parent('label').show();
                    $J("#selectUserFromOther").attr('checked', false).parent('label').show();
                }

                if (userId != 0 && userId != null) {
                    userFromBackupObj.attr('checked', false).parent('label').hide();
                    $J("#selectUserFromOther").attr('checked', true).parent('label').hide();
                }

                if ($J("input:radio[name='selectUserType']:checked").val() == '2') {
                    $J('.live-search-user-add').show();
                }
                break;
        }
    },
    showSubscriptionSelection: function (objUser) {
        $J('#createNewSubscription').attr('checked', true);
        $J('#subscriptionSelection').hide();
        $J('#userSelection').find('.restore_error').hide();
        $J('.websiteRestoreButton').attr('disabled', false);
        $J.ajax({
            url: cx.variables.get('cadminPath', 'contrexx') + "?cmd=JsonData&object=MultiSite&act=getAvailableSubscriptionsByUserId",
            data: {userId: objUser.id},
            type: "POST",
            dataType: "json",
            success: function (response) {
                var $resp = (response.data) ? response.data : response;
                if ($resp.subscriptionsList != undefined && $resp.status == 'success') {
                    $J('#SubscriptionOption').show();
                    $J('#subscriptionSelection #subscriptionList')
                            .html('')
                            .append(getEditOption('dropdown', 'subscription', 'subscriptionOptions', '', $resp.subscriptionsList));
                } else {
                    $J('#SubscriptionOption').hide();
                }
            }
        });
    }
};
