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
                jQuery.ajax({
                    type: 'POST',
                    data: data,
                    url:  cadminPath + 'index.php&cmd=JsonData&object=' + opt.jsonObject + '&act=' + opt.jsonAct,
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
                });
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
});

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
        existingData
    );
}
/*
* This function creates a cx dialag for the ViewGenerator and opens it
*
*/
function openDialogForAssociation(content, className, existingData)
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
        dialog.getElement().find('[name='+property[0]+']').not('[type=button]').val(property[1]);
        dialog.getElement().find('[type=button].mappedAssocciationButton').prop("disabled", true);
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
function createAjaxRequest(entityClass, mappedBy, className, sessionKey, existingData){
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
                existingData
            );
            jQuery('.datepicker').datepicker({
                dateFormat: 'dd.mm.yy'
            });
        }
    });
}
