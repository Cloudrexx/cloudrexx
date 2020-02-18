/**
 * This file is loaded in FormGenerator over \JS::registerJS for requests over cx.ajax
 *
 */

/**
 * Script for initializing the row sorting functionality in ViewGenerator
 */
cx.ready(function() {
    var jQuery = cx.jQuery;
    var cadminPath = cx.variables.get('cadminPath', 'contrexx'),
        sortable = {
            ajaxCall : function(opt) {
                var data = 'sortOrder=' + opt.sortOrder + '&curPosition=' + opt.curIndex
                            + '&prePosition=' + opt.preIndex + '&sortField=' + opt.sortField
                            + '&pagingPosition=' + opt.position,
                    recordCount = 0;
                if (opt.component && opt.entity) {
                    data += '&component=' + opt.component + '&entity=' + opt.entity;
                }
                if (opt.repeat) {
                    data += '&' + opt.updatedOrder;
                }
                cx.ajax(
                    opt.jsonObject,
                    opt.jsonAct,
                    {
                        type: 'POST',
                        data: data,
                        beforeSend: function() {
                            jQuery('body').addClass('loading');
                            opt.that.sortable("disable");
                            opt.uiItem.find('td:first-child').addClass('sorter-loading');
                        },
                        success: function(msg) {
                            if (msg.data && msg.data.status === 'success') {
                                recordCount = msg.data.recordCount;
                            }
                        },
                        complete: function() {
                            sortable.updateOrder(opt, recordCount);
                            opt.that.sortable("enable");
                            jQuery('body').removeClass('loading');
                            opt.uiItem.find('td:first-child').removeClass('sorter-loading');
                        }
                    }
                );
            },
            //Check the same 'order' field value is repeated or not
            isOrderNoRepeat : function(options) {
                var orderArray = [], currentval, obj = options.sortTd,
                    condition = options.curIndex > options.preIndex,
                    min       = condition ? options.preIndex : options.curIndex,
                    max       = condition ? options.curIndex : options.preIndex;
                while (min <= max) {
                    currentval = condition ? obj.eq(min - 1).text() : obj.eq(max - 1).text();
                    if (jQuery.inArray(currentval, orderArray) === -1) {
                        orderArray.push(currentval);
                        condition ? min++ : max--;
                        continue;
                    }
                    return true;
                }
                return false;
            },
            //Update the sorted order in the 'order' field
            updateOrder : function(options, recordCnt) {
                var currentObj, currentOrder, order, firstObj, obj = options.sortTd,
                    condition = options.curIndex > options.preIndex,
                    min       = condition ? options.preIndex : options.curIndex,
                    max       = condition ? options.curIndex : options.preIndex,
                    isDescOrder = (options.sortOrder === 'DESC'), first = true;

                //If the same 'order' field value is repeated,
                //we need to update all the entries.
                if (options.repeat) {
                    var pagingCnt = isDescOrder
                                    ? (recordCnt - options.position) + 1
                                    : options.position;
                    obj.each(function() {
                        isDescOrder ? pagingCnt-- : pagingCnt++;
                        jQuery(this).text(pagingCnt);
                    });
                    return;
                }

                //If the same 'order' field value is not repeated,
                //we need to update all the entries between dragged and dropped position
                while (min <= max) {
                    currentObj = condition ? obj.eq(min - 1) : obj.eq(max - 1);
                    currentOrder = currentObj.text();
                    if (first) {
                        first = false;
                        order = currentOrder;
                        firstObj = currentObj;
                        continue;
                    } else if (min === max) {
                        firstObj.text(currentOrder);
                        currentObj.text(order);
                    }
                    currentObj.text(order);
                    order = currentOrder;
                    condition ? min++ : max--;
                }
            }
        };

    jQuery('table.sortable tbody').sortable({
        axis: "y",
        items: "> tr.row1,> tr.row2 ",
        start: function (event, ui) {
            jQuery(ui.item).data('pIndex', ui.item.index());
        },
        update: function (event, ui) {
            var obj    = jQuery(this).closest('table.sortable'),
                params = {
                    that       : jQuery(this),
                    uiItem     : jQuery(ui.item),
                    jsonObject : obj.data('object'),
                    jsonAct    : obj.data('act'),
                    sortField  : obj.data('field'),
                    sortOrder  : obj.data('order'),
                    component  : obj.data('component'),
                    entity     : obj.data('entity'),
                    position   : parseInt(obj.data('pos')),
                    curIndex   : parseInt(ui.item.index()),
                    preIndex   : parseInt(jQuery(ui.item).data('pIndex')),
                    updatedOrder : jQuery(this).sortable('serialize')
                };
            params['sortTd'] = params.that.find('td.sortBy' + params.sortField);
            params['repeat'] = sortable.isOrderNoRepeat(params);

            //If jsonObject and jsonAct values are empty, stop the update process
            if (    typeof(params.jsonObject) === 'undefined'
                ||  typeof(params.jsonAct) === 'undefined'
                ||  !params.jsonObject
                ||  !params.jsonAct
            ) {
                return;
            }

            params.uiItem.removeData('pIndex');
            sortable.ajaxCall(params);
        }
    });

    // Get first element from tabmenu and select tab
    var firstTab = document.getElementsByClassName('vg-tabs')[0];
    if (document.getElementById('form-0-tab-legend') && typeof document.getElementById('form-0-tab-legend') !== 'undefined') {
        document.getElementById('form-0-tab-legend').style.display = 'block';
        if (document.getElementById('form-0-tabmenu') != null) {
            selectTab(firstTab.id, true);
        } else {
            firstTab.style.display = 'block';
        }
        initializeTabClickEvent(0);
    }

    cx.jQuery(".chzn").chosen();

    var cadminPath = cx.variables.get('cadminPath', 'contrexx'),
        status = {
            ajaxCall : function(opt) {
                cx.ajax(
                    opt.jsonObject,
                    opt.jsonAct,
                    {
                        type: 'POST',
                        data: {
                            'entityId': opt.entityId,
                            'newStatus': opt.statusValue,
                            'statusField': opt.statusField,
                            'component': opt.component,
                            'entity': opt.entity,
                        },
                        showMessage: true,
                        beforeSend: function() {
                            cx.jQuery(opt.that).addClass('loading');
                        },
                        success: function(json) {
                            cx.jQuery(opt.that).toggleClass('active');
                        },
                        preError: function(xhr, status, error) {
                            cx.tools.StatusMessage.showMessage(error);
                            cx.jQuery(this).data('status-value', (cx.jQuery(this).hasClass('active') ? 0 : 1));
                        },
                        complete: function() {
                            cx.jQuery(opt.that).removeClass('loading');
                        }
                    },
                    cx.variables.get('frontendLocale', 'contrexx')
                );
            },
        };
    cx.jQuery('.vg-function-status').click(function () {
        var table = cx.jQuery(this).closest('table.status');
        cx.jQuery(this).data('status-value', (cx.jQuery(this).hasClass('active') ? 0 : 1));

        var params = {
            that: cx.jQuery(this),
            entityId: cx.jQuery(this).data('entity-id'),
            jsonObject: table.data('status-object'),
            jsonAct: table.data('status-act'),
            component: table.data('status-component'),
            entity: table.data('status-entity'),
            statusField: table.data('status-field'),
            statusValue: cx.jQuery(this).data('status-value'),
        };
        status.ajaxCall(params);
    });

    cx.jQuery(".vg-export").click(function(e) {
        e.preventDefault();
        var url = new URL(window.location);
        var params = {
            type: cx.jQuery(this).data('object'),
        };
        if (url.searchParams.get('search')) {
            params.search = url.searchParams.get('search');
        }
        if (url.searchParams.get('term')) {
            params.term = url.searchParams.get('term');
        }
        cx.ajax(
            cx.jQuery(this).data('adapter'),
            cx.jQuery(this).data('method'),
            {
                showMessage: true,
                data: params,
                postSuccess: function(data) {
                    window.location.href = data.data;
                }
            }
        );
    });
});

function initializeTabClickEvent(formId) {
    cx.jQuery('.tabmenu a').click(function () {
        var tabName = cx.jQuery(this).attr('id').split('_')[1];
        selectTab(tabName, true, formId);
    });
}

jQuery(document).ready(function(){
    jQuery('.mappedAssocciationButton, .edit').click(function() {
        editAssociation(jQuery(this));
    });
});

function editAssociation (thisElement) {
    var paramAssociativeArray = {};
    if (jQuery(thisElement).attr("class").indexOf('mappedAssocciationButton') >= 0) {
        paramIndexedArray = jQuery(thisElement).attr('data-params').split(';');
    } else {
        paramIndexedArray = jQuery(thisElement).parent().siblings('.mappedAssocciationButton').attr('data-params').split(';');
    }

    jQuery.each(paramIndexedArray, function(index, value){
        paramAssociativeArray[value.split(':')[0]] = value.split(':')[1];
    });
    existingData = '';
    if (jQuery(thisElement).hasClass('edit')) {
        jQuery(thisElement).parent().children('input').addClass('current');
        existingData = jQuery(thisElement).parent().children('input').attr('value')
    }
    createAjaxRequest(
        paramAssociativeArray['entityClass'],
        paramAssociativeArray['mappedBy'],
        paramAssociativeArray['cssName'],
        paramAssociativeArray['sessionKey'],
        existingData,
        thisElement
    );
}
/*
* This function creates a cx dialag for the ViewGenerator and opens it
*
*/
function openDialogForAssociation(content, className, existingData, currentElement)
{

    buttons = [
        {
            text: cx.variables.get('TXT_CANCEL', 'Html/lang'),
            click: function() {
                jQuery(this).dialog('close');
                cx.tools.StatusMessage.removeAllDialogs();
                jQuery('.oneToManyEntryRow').children('.current').removeClass('current');
            }
        },
        {
            text: cx.variables.get('TXT_SUBMIT', 'Html/lang'),
            click: function() {
                var element = jQuery(this).closest('.ui-dialog').children('.ui-dialog-content').children('form');
                if (!cx.ui.forms.validate(element)) {
                    return false;
                }
                saveToMappingForm(element, className);
                jQuery(this).dialog('close');
            }
        }
    ];
    var dialog = cx.ui.dialog({
        width: 600,
        height: 300,
        autoOpen: true,
        content: content,
        modal: true,
        dialogClass: "cx-ui",
        resizable: false,
        buttons:buttons,
        close: function() {
            jQuery(this).dialog('close');
        }
    });
    jQuery.each(existingData.split('&'), function(index, value){
        property = value.split('=');
        var el = dialog.getElement().find('[name='+property[0]+']');
        if (el.attr('type') == 'button') {
            el.filter('.mappedAssocciationButton').prop("disabled", true);
        } else if (el.attr('type') == 'radio') {
            dialog.getElement().find('[name='+property[0]+']').filter('[value='+property[1]+']').click();
        } else if (el.attr('type') == 'checkbox') {
            dialog.getElement().find('[name='+property[0]+']').filter('[value='+property[1]+']').prop('checked', true);
        } else {
            el.val(property[1]);
        }
        if (property[0] == 'id') {
            jQuery('<input>').attr({
                value: property[1],
                id: 'id',
                name: 'id',
                type: 'hidden'
            }).appendTo(jQuery('.ui-dialog-content').children('form'));
        }
    });


}

/*
 * This function takes the data from dialog form and writes it into our many form
 *
 */
function saveToMappingForm(element, className)
{
    value = element.serialize().split('&');
    var valuesAsString = '';
    jQuery.each(value, function(index, value){
        if(value.split('=')[0] != 'vg_increment_number' && value.split('=')[0] != 'id'){
            if (value.split('=')[1] != "") {
                decodedValue = value.split('=')[1];
                decodedValue = decodedValue.replace('+', ' '); // because serialize makes a plus out of whitespaces
                valuesAsString += decodeURIComponent(decodedValue) + ' / ';
            } else {
                valuesAsString += '-' + ' / ';
            }
        }
    });

    // if the last attribute is not set, we remove the notSetString "- / " for better optic
    while (valuesAsString.slice(-5) == ' - / ') {
        valuesAsString = valuesAsString.substr(0, valuesAsString.length - 5);
    }

    // remove the last slash and the last two whitespaces for better optic
    valuesAsString = valuesAsString.substr(0, valuesAsString.length - 3);

    // we only create a new element if it is not empty
    if (valuesAsString != "") {
        current = jQuery('.oneToManyEntryRow').children('.current');
        if(jQuery(current).is(':empty')){
            jQuery(current).attr('value', element.serialize());
            jQuery(current).removeClass('current');
        } else {
            jQuery('.add_'+className+'').before('<div class=\'oneToManyEntryRow\'>'
                + '<input type=\'hidden\' name=\'' + className + '[]\' value=\'' + element.serialize() + '\'>'
                + '<a onclick=\'editAssociation(this)\' class=\'edit\' title=\'' + cx.variables.get('TXT_EDIT', 'Html/lang') + '\'></a>'
                + '<a onclick=\'deleteAssociationMappingEntry(this)\' class=\'remove\' title=\'' + cx.variables.get('TXT_DELETE', 'Html/lang') + '\'></a>'
                + '</div>'
            );
        }
    }
}

/*
 * This function removes an association which we created over dialog
 *
 */
function deleteAssociationMappingEntry(element)
{
    // if we have an already existing entry (which is saved in the database), we only hide it, because we will remove
    // it as soon as the main formular is submitted.
    // otherwise we have an entry which doesn't exists in the database and we can simply remove the element, because we
    // do not need to store it and so it is useless
    if (jQuery(element).hasClass('existing')) {
        jQuery(element).parent().css('display', 'none');
        entryInput = jQuery(element).parent().children('input');
        entryInput.attr('value', entryInput.attr('value') + '&delete=1');
    } else {
        jQuery(element).parent().remove();
    }
}


/*
 * This function creates an ajax request to the ViewGenerator and on success call the function to open the dialog where
 * we can insert the data for the mapped association
 *
 */
function createAjaxRequest(entityClass, mappedBy, className, sessionKey, existingData, currentElement){
    cx.ajax(
        'Html',
        'getViewOverJson',
    {
        data: {
            entityClass: entityClass,
            mappedBy:    mappedBy,
            sessionKey:  sessionKey
        },
        success: function(data) {
            openDialogForAssociation(
                data.data,
                className,
                existingData,
                currentElement
            );
            jQuery('.datepicker').datepicker({
                dateFormat: 'dd.mm.yy'
            });
        }
    });
}

// Search and filter functionalities
cx.ready(function() {
    // since split() does weird things if limit param is used...
    // from https://stackoverflow.com/questions/29998343/limiting-the-times-that-split-splits-rather-than-truncating-the-resulting-ar
    function JavaSplit(string,separator,n) {
        var split = string.split(separator);
        if (split.length <= n)
            return split;
        var out = split.slice(0,n-1);
        out.push(split.slice(n-1).join(separator));
        return out;
    }

    // Since decodeURI() does not decode all characters and CSRF does weird things:
    // TODO: Check if this is necessary (CSRF bug?) and either remove or move it
    cx.tools.decodeURI = function (uri) {
        uri = decodeURI(uri);
        uri = uri.replace(/%2C/g, ",");
        uri = uri.replace(/%3D/g, "=");
        uri = uri.replace(/%2F/g, "/");
        return uri;
    }
    
    // If any of the dropdowns change or search button is pressed:
    // manually generate url and document.location it
    var getSubmitHandler = function(ev) {
        var formId = jQuery(this).attr("form");
        var elements = cx.jQuery("[form=" + formId + "]").filter("select,input,textarea").not("[type=button]").not("[type=submit]");
        var vgId = jQuery("#" + formId).data("vg-id");
        var url = cx.tools.decodeURI(document.location.href);
        var attrGroups = {}
        elements.each(function(index, el) {
            el = cx.jQuery(el);
            var regex = new RegExp("([?&])" + el.attr("name") + "[^&]+");
            var replacement = "";
            if (el.val().length) {
                var val = el.val();
                if (el.is(".vg-encode")) {
                    if (el.data("vg-attrgroup")) {
                        val = "{" + vgId + "," + el.data("vg-field") + "=" + el.val() + "}";
                        if (!attrGroups[el.data("vg-attrgroup")]) {
                            attrGroups[el.data("vg-attrgroup")] = "";
                        } else {
                            attrGroups[el.data("vg-attrgroup")] += ",";
                        }
                        attrGroups[el.data("vg-attrgroup")] += val;
                        return;
                    }
                    val = "{" + vgId + "," + el.val() + "}";
                }
                replacement = el.attr("name") + "=" + val;
            }
            if (regex.test(url)) {
                if (replacement.length) {
                    replacement = "$1" + replacement;
                }
                url = url.replace(regex, replacement);
            } else if (replacement.length) {
                url += "&" + replacement;
            }
        });
        // TODO: Need to remove attrGroups from URL
        cx.jQuery(".vg-encode[data-vg-attrgroup]").each(function(index, el) {
            el = cx.jQuery(el);
            if (!attrGroups[el.data("vg-attrgroup")]) {
                attrGroups[el.data("vg-attrgroup")] = "";
            }
        });
        if (attrGroups) {
            cx.jQuery.each(attrGroups, function(index, el) {
                val = "";
                if (el.length) {
                    val = index + "=" + el;
                }
                regex = new RegExp("([?&])" + index + "[^&]+");
                if (regex.test(url)) {
                    if (val.length) {
                        val = "$1" + val;
                    }
                    url = url.replace(regex, val);
                } else if (val.length) {
                    url += "&" + val;
                }
            });
        }
        document.location = url;
        ev.preventDefault();
    };
    cx.jQuery("select.vg-searchSubmit").change(getSubmitHandler);
    cx.jQuery(".vg-searchSubmit").filter("a,input").click(getSubmitHandler);
    
    (function() {
        var url = decodeURIComponent(document.location.href);
        var parts = JavaSplit(url, "?", 2);
        if (parts.length < 2) {
            return;
        }
        parts = parts[1].split("&");
        var elements = cx.jQuery("select,input,textarea").filter(".vg-encode");
        if (!elements.length) {
            return;
        }
        var regex = /\{([0-9+]),(?:([^=]+)=)?([^\}]+)\}/
        cx.jQuery.each(parts, function(index, part) {
            var attribute = JavaSplit(part, "=", 2);
            if (attribute[0] == "csrf") {
                return;
            }
            if (attribute[0] == "search" || attribute[0] == "term") {
                var conditions = attribute[1].substr(1, attribute[1].length - 1).split("},{");
                cx.jQuery.each(conditions, function(index, condition) {
                    condition = "{" + condition + "}";
                    var matches = condition.match(regex);
                    if (matches.length < 4) {
                        return;
                    }
                    var formId = "vg-" + matches[1] + "-searchForm";
                    var formElement;
                    if (matches[2]) {
                        formElement = cx.jQuery("[form=" + formId + "][data-vg-field=" + matches[2] + "]");
                    } else {
                        formElement = cx.jQuery("[form=" + formId + "][name=" + attribute[0] + "]");
                    }
                    formElement.val(matches[3]);
                });
            }
        });
    })();
});

cx.bind('Html:postFormFix', function() {
    var formId = 0;
    cx.jQuery.each(cx.ui.forms.get(), function(index, el) {
        formId = cx.jQuery(el).attr("id").split("-")[1];
        cx.jQuery('#form-' + formId).find('*[id*="form-0-"]').each(function () {
            var id = cx.jQuery(this).attr('id');
            cx.jQuery(this).attr('id', id.replace('-0-', '-' + formId + '-'));
        });
    });

    initializeTabClickEvent(formId);

    var forms = document.getElementsByTagName('form');
    for (var i = 0; i < forms.length; i++) {
        var firstTab = forms.item(i).getElementsByClassName('vg-tabs')[0];
        if (!firstTab) {
            continue;
        }
        document.getElementById('form-'+formId+'-tab-legend').style.display = 'block';
        if (document.getElementById('form-'+formId+'-tabmenu') != null) {
            selectTab(firstTab.id, true, formId);
        } else {
            firstTab.style.display = 'block';
        }
    }

}, 'ViewGenerator');