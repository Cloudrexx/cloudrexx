cx.jQuery(document).ready(function() {
    // set unique with to all fallback dropdowns
    var maxFallbackSelectWidth = 0;
    // get width of widest fallback dropdown
    cx.jQuery('.localeFallback select').each(function() {
        var fallbackSelectWidth = cx.jQuery(this).width();
        if (fallbackSelectWidth > maxFallbackSelectWidth) {
            maxFallbackSelectWidth = fallbackSelectWidth;
        }
    });
    cx.jQuery('.localeFallback select').width(maxFallbackSelectWidth);

    //  hide delete function for default locale
    cx.jQuery(".localeDefault input:checked").parent().parent("tr").find('.functions a.delete').hide();

    cx.jQuery("#content :input").change(function() {
       cx.jQuery("#content input[name=\"updateLocales\"").show();
    });

    cx.jQuery(".localeFallback select").change(function() {
        if (cx.jQuery(this).val() == "NULL") {
            cx.jQuery(this).parent().parent("tr").find(".functions").find(".copyLink, .linkLink").hide();
        } else {
            cx.jQuery(this).parent().parent("tr").find(".functions").find(".copyLink, .linkLink").show();
        }
    });
});

cx.jQuery(function() {
    updateCurrent(true);
});

function updateCurrent(init) {
    cx.jQuery(".adminlist tbody tr").each(function(index, el) {
        var locale = cx.jQuery(el);
        var localeId = locale.children(".localeId").text();
        var defField = locale.children(".localeDefault").children("input");
        var fallbackField = locale.children(".localeFallback").children("select");
        // handle fallbacks according to defField
        if (init) {
            fallbackField.children("option[value=" + localeId + "]").remove();
        }
        if (defField.is(":checked")) {
            fallbackField.val("NULL").hide();
        } else {
            fallbackField.show();
        }
    });
}

function copyPages(toLangId) {
    performLanguageAction("copy", toLangId);
}

function linkPages(toLangId) {
    performLanguageAction("link", toLangId);
}

function performLanguageAction(actionName, toLangId, action) {
    var defaultLangId = cx.jQuery(".localeDefault input:checked").parent("tr").children('.localeId').text();
    var toLangRow = cx.jQuery(".localeId").filter(function() {
        return cx.jQuery.trim(cx.jQuery(this).text()) == toLangId;
    }).parent("tr");
    var fromLangId = toLangRow.children(".localeFallback").children("select").val();
    if (fromLangId === "") {
        return;
    } else if (fromLangId == 0) {
        fromLangId = defaultLangId;
    }
    var fromLangRow = cx.jQuery(".localeId").filter(function() {
        return cx.jQuery.trim(cx.jQuery(this).text()) == fromLangId;
    }).parent("tr");
    var fromLangName = cx.jQuery.trim(fromLangRow.children(".localeLabel").text());
    var toLangName = cx.jQuery.trim(toLangRow.children(".localeLabel").text());
    showActionDialog(actionName, fromLangName, toLangName, function() {
        var waitDialog = cx.ui.dialog({
            title: cx.variables.get("waitTitle", "Locale/Locale"),
            content: cx.variables.get("waitText", "Locale/Locale"),
            modal: true,
            autoOpen: true,
            open: function(event, ui) {
                cx.jQuery(".ui-dialog-titlebar-close").hide();
            }
        });
        performLanguageRequest(actionName, toLangId, 0, waitDialog, function() {
            waitDialog.close();
            cx.ui.dialog({
                content: cx.variables.get(actionName + "Success", "Locale/Locale"),
                modal: true,
                autoOpen: true,
                buttons: [
                    {
                        text: "Ok",
                        icons: {
                            primary: "ui-icon-heart"
                        },
                        click: function() {
                            cx.jQuery(this).dialog("close");
                        }
                    }
                ]
            });
        });
    }, cx.variables.get("warningText", "Locale/Locale").replace("%1",  "<b>" + fromLangName + "</b>").replace("%2",  "<b>" + toLangName + "</b>"));
}

function performLanguageRequest(actionName, toLangId, offset, dialog, doneFunc) {
    var url = "index.php?cmd=JsonData&object=cm&act=" + actionName + "&to=" + toLangId + "&offset=" + offset + "&limit=1";
    cx.jQuery.ajax({
        url: url,
        dataType: "json",
        type: "GET",
        success: function(json) {
            if (json.status != "success") {
                cx.ui.dialog({
                    title: json.status,
                    content: json.message,
                    modal: true,
                    autoOpen: true
                });
                return;
            }
            offset = json.data.offset;
            count = json.data.count;
            var newText = cx.variables.get("waitText", "Locale/Locale") + "<br /><br />" + offset + " / " + count + " (" + Math.round(offset * 100 / count) + "%)";
            //console.log(offset + " / " + count + " (" + Math.round(offset * 100 / count) + "%)");
            dialog.getElement().html(newText);
            if (offset < count) {
                performLanguageRequest(actionName, toLangId, offset, dialog, doneFunc);
            } else {
                doneFunc();
            }
        }
    });
}

/**
 * @param string action Name of action
 * @param string fromLang Language name to copy from
 * @param string toLang Language name to copy to
 * @param function yesAction Function to call when "yes" is clicked
 * @param string checkboxText (optional) Text for checkbox label. If null, no checkbox is shown
 * @return cx.ui.dialog Cx Ui Dialog object
 */
function showActionDialog(action, fromLang, toLang, yesAction, checkboxText) {
    var yesOption = cx.variables.get("yesOption", "Locale/Locale");
    var noOption = cx.variables.get("noOption", "Locale/Locale");
    var buttons = new Object();
    buttons[yesOption] = function() {
        cx.jQuery(this).dialog("close");
        if (checkboxText) {
            if (!dialog.getElement().children().children("#really").is(":checked")) {
                return;
            }
        }
        yesAction();
    };
    buttons[noOption] = function() {cx.jQuery(this).dialog("close");}
    var content = "<p>" + cx.variables.get(action + "Text", "Locale/Locale");
    if (action == 'link') {
        content = content.replace("%1",  "<b>" + toLang + "</b>").replace("%2", "<b>" + fromLang + "</b>");
    } else {
        content = content.replace("%1",  "<b>" + fromLang + "</b>").replace("%2",  "<b>" + toLang + "</b>");
    }
    cx.jQuery("#really").remove();
    if (checkboxText) {
        content += "<br /><br /><input type=\"checkbox\" id=\"really\" class=\"really\" value=\"true\" /> <label for=\"really\" class=\"really\">" + checkboxText + "</label>";
    }
    content += "</p>";
    var dialog = cx.ui.dialog({
        title: cx.variables.get(action + "Title", "Locale/Locale"),
        content: content,
        buttons: buttons,
        modal: true,
        width: 400,
        autoOpen: false
    });
    var yesButton = dialog.getElement().siblings(".ui-dialog-buttonpane").children(".ui-dialog-buttonset").children("button").first();
    yesButton.hide();
    cx.jQuery("#really").change(function() {
        console.log('changed');
        yesButton.toggle(cx.jQuery("#really").is(":checked"));
    });
    dialog.open();
    return dialog;
}
