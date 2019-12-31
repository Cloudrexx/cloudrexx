cx.ready(function() {
    cx.jQuery('#instance_table').append('<div id ="load-lock"></div>');

    cx.vm();

    cx.vm.reloadThemesPage = function(){
        document.themesForm.submit();
    };

    cx.vm.deleteFiles = function(obj) {
        $J('#themesPage').val($J(obj.parents('li')).children('a.naming').attr('data-rel'));
        $J('#isFolder').val($J(obj.parents('li')).children('a.naming').hasClass('folder'));

        msg    =   $J(obj.parents('li')).children('a.naming').hasClass('folder')
                 ? cx.variables.get('confirmDeleteFolder', "viewmanager/lang")
                 : cx.variables.get('confirmDeleteFile', "viewmanager/lang");
        msgTxt = msg.replace('%s', obj.closest('li').find('> a').text());
        if (!cx.vm.confirmDelete(msgTxt)) return;

        cx.vm.themesManipulationAjaxCall(
                "index.php?cmd=JsonData&object=ViewManager&act=delete",
                $J('form[name=themesForm]').serialize(),
                cx.vm.callbackDeleteFiles
        );
        return;
    };

    cx.vm.resetFiles = function(obj) {
        $J('#themesPage').val($J(obj.parents('li')).children('a.naming').attr('data-rel'));
        $J('#isFolder').val($J(obj.parents('li')).children('a.naming').hasClass('folder'));

        msgTxt =   $J(obj.parents('li')).children('a.naming').hasClass('folder')
                 ? cx.variables.get('confirmResetFolder', "viewmanager/lang")
                 : cx.variables.get('confirmResetFile', "viewmanager/lang");

        if (!cx.vm.confirmDelete(msgTxt)) return;

        cx.vm.themesManipulationAjaxCall(
                "index.php?cmd=JsonData&object=ViewManager&act=delete",
                $J('form[name=themesForm]').serialize() +'&reset=1',
                cx.vm.callbackDeleteFiles
        );
        return;
    };

    cx.vm.callbackDeleteFiles = function(res) {
        if (res.status) {
            cx.tools.StatusMessage.showMessage(res.message, null,2000);
        }
        if (res.reload != 'undefined' && res.reload) {
            cx.vm.reloadThemesPage();
        }
    };
    var TXT_CANCEL = cx.variables.get('cancel', "viewmanager/lang");
    var TXT_CREATE = cx.variables.get('create', "viewmanager/lang");
    var TXT_RENAME = cx.variables.get('rename', "viewmanager/lang");

    cx.vm.callbackNewFile = function(res) {
        if (res.status == 'success') {
            cx.tools.StatusMessage.showMessage(res.message, null,3000);
        } else {
            $J('.dialogMessage').html('<div class="alertbox">'+ res.message +'</div>');
            cx.vm.dialog.open();
            return;
        }
        if (res.path != 'undefined') {
            cx.jQuery('#themesPage').val(res.path);
        }

        if (res.reload != 'undefined' && res.reload) {
            cx.vm.reloadThemesPage();
        }
    };

    cx.vm.dialog;

    cx.vm.renameFile = function(obj) {

        var title,
            label,
            view,
            isFolder = 0,
            buttons = new Object();

        if (obj.closest('a').hasClass('folder')) {
            isFolder = 1;
            title = cx.variables.get('renameFolderOperation', "viewmanager/lang");
            label = cx.variables.get('txtName', "viewmanager/lang");
        } else {
            title = cx.variables.get('renameFileOperation', "viewmanager/lang");
            label = cx.variables.get('fileName', "viewmanager/lang");
        }

        view  = '<div class="dialogMessage"></div>';
        view += '<table>\n\
                    <tr>\n\
                        <td>'
                            + label +
                       '</td>\n\
                        <td>\n\
                            <input type="hidden" id="old_file_name" value="'+ $J(obj.parents('li')).children('a.naming').attr('data-rel') +'"/>\n\
                            <input type="text" id="new_file_name" value="'+ $J.trim($J(obj.closest('li')).children('a.naming').text()) +'"/>\n\
                        </td>\n\
                    </tr>\n\
                </table>';

        buttons[TXT_CANCEL] = function() {
            cx.jQuery(this).dialog("close");
        };

        buttons[TXT_RENAME] = function() {
            cx.jQuery(this).dialog("close");
            cx.vm.themesManipulationAjaxCall(
                    "index.php?cmd=JsonData&object=ViewManager&act=rename",
                    {   theme    : cx.jQuery('.selectedTheme').val(),
                        isFolder : isFolder,
                        oldName  : cx.jQuery('#old_file_name').val(),
                        newName  : cx.jQuery('#new_file_name').val()
                    },
                    cx.vm.callbackRenameFile
            );
        };

        if (typeof cx.vm.dialog == 'object') {
            cx.vm.dialog.getElement().remove();
        }

        cx.vm.dialog = cx.ui.dialog({
            width: 350,
            title: title,
            content: view,
            autoOpen: true,
            modal: true,
            buttons: buttons,
            open: function() {
                cx.jQuery('#new_file_name').select();
            }
        });
    };

    cx.vm.callbackRenameFile = function(res) {
        if (res.status == 'success') {
            cx.tools.StatusMessage.showMessage(res.message, null,3000);
        } else {
            $J('.dialogMessage').html('<div class="alertbox">'+ res.message +'</div>');
            cx.vm.dialog.open();
            return;
        }

        if (res.path != 'undefined') {
            cx.jQuery('#themesPage').val(res.path);
        }

        if (res.reload != 'undefined' && res.reload) {
            cx.vm.reloadThemesPage();
        }
    };

    cx.vm.themesManipulationAjaxCall = function(url, data, callback) {
        cx.trigger("loadingStart", "viewmanager", {});
        cx.tools.StatusMessage.showMessage("<div id=\"loading\">" + cx.variables.get('loading', "viewmanager/lang") + "</div>");
        cx.vm.lock();
        cx.jQuery.ajax({
            url: url,
            dataType: "json",
            type: "POST",
            data: data,
            success: function(response) {
                cx.trigger("loadingEnd", "viewmanager", {});
                if (response.status == 'error') {
                    cx.tools.StatusMessage.showMessage(response.message, null,2000);
                    cx.vm.unlock();
                    return;
                }

                if (callback && typeof(callback) === "function") {
                    callback(response.data);
                } else {
                    if (response.data.status == 'error') {
                        cx.tools.StatusMessage.showMessage(response.data.message, null,2000);
                    }

                    if (response.data.status == 'success') {
                        cx.tools.StatusMessage.showMessage(response.data.message, null,3000);
                    }
                }
                cx.tools.StatusMessage.removeAllDialogs();
                cx.vm.unlock();
            }
        });
    };

    cx.vm.confirmDelete = function(msgTxt) {
        return confirm(msgTxt);
    };

    /*
     * Locks the file-tree in order to prevent user input
     */
    cx.vm.lock = function() {
        cx.jQuery("#load-lock").show();
    };

    /*
     * UnLocks the file-tree in order to prevent user input
     */
    cx.vm.unlock = function() {
        cx.jQuery("#load-lock").hide();
    };

    /*
     * Bind the create new file/folder event
     */
    cx.jQuery('div.new_file > a').click(function(event) {
        var title,
            label,
            view,
            buttons  = new Object(),
            isFolder = 0;

        if (cx.jQuery(this).hasClass('file')) {
            title = cx.variables.get('newFileOperation', "viewmanager/lang");
            label = cx.variables.get('fileName', "viewmanager/lang");
        } else {
            title = cx.variables.get('newFolderOperation', "viewmanager/lang");
            label = cx.variables.get('txtName', "viewmanager/lang");
            isFolder = 1;
        }

        view  = '<div class="dialogMessage"></div>';
        view += '<table>\n\
                    <tr>\n\
                        <td>'
                            + label +
                       '</td>\n\
                        <td>\n\
                            <input type="text" id="newName" value=""/>\n\
                        </td>\n\
                    </tr>\n\
                </table>';

        buttons[TXT_CANCEL] = function() {
            cx.jQuery(this).dialog("close");
        };

        buttons[TXT_CREATE] = function() {
            cx.jQuery(this).dialog("close");
            cx.vm.themesManipulationAjaxCall(
                    "index.php?cmd=JsonData&object=ViewManager&act=newWithin",
                    {   theme    : cx.jQuery('.selectedTheme').val(),
                        isFolder : isFolder,
                        name     : cx.jQuery('#newName').val()
                    },
                    cx.vm.callbackNewFile
            );
        };

        if (typeof cx.vm.dialog == 'object') {
            cx.vm.dialog.getElement().remove();
        }

        cx.vm.dialog = cx.ui.dialog({
            width: 350,
            title: title,
            content: view,
            autoOpen: true,
            modal: true,
            buttons: buttons
        });

        return;
    });
});

cx.vm = function() {
    return true;
};
