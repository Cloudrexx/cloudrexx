cx.bind("delete", function (deleteIds) {
    var scope = 'order';
    if (confirm(
        cx.variables.get('TXT_CONFIRM_DELETE_ORDER', scope)+'\n'+ cx.variables.get('TXT_ACTION_IS_IRREVERSIBLE', scope)
    )) {
        var stockUpdate = false;
        if (confirm(
            cx.variables.get('TXT_SHOP_CONFIRM_RESET_STOCK', scope)
        )) {
            stockUpdate = true;
        }
        cx.ajax(
            'Order',
            'deleteOrders',
            {
                type: 'POST',
                data: {
                    orderIds: deleteIds,
                    updateStock: stockUpdate,
                },
                success: function(response) {
                    if (response.status == 'success') {
                        cx.tools.StatusMessage.showMessage(response.message);
                        deleteIds.forEach(function(entityId) {console.log('entityId');
                            document.getElementsByName('status-' + entityId)[0].selectedIndex = 2;
                        });
                    }
                },
                preError: function(xhr, status, error) {
                    cx.tools.StatusMessage.showMessage(error);
                }
            },
            cx.variables.get('language', 'contrexx')
        );
    }
}, 'order');

// Function to overwrite delete onclick event. See BackendController $option['functions]['onclick']['delete']
function deleteOrder(deleteUrl) {
    var scopeDelete = 'order';
    if (confirm(
        cx.variables.get('TXT_CONFIRM_DELETE_ORDER', scopeDelete)+'\n'+ cx.variables.get('TXT_ACTION_IS_IRREVERSIBLE', scopeDelete)
    )) {
        var stockUpdate = false;
        if (confirm(
            cx.variables.get('TXT_SHOP_CONFIRM_RESET_STOCK', scopeDelete)
        )) {
            stockUpdate = true;
        }

        const entityId = parseInt(getParameterByName('deleteid', deleteUrl));

        cx.ajax(
            'Order',
            'deleteOrder',
            {
                type: 'POST',
                data: {
                    orderId: entityId,
                    updateStock: stockUpdate,
                },
                success: function(response) {
                    if (response.status == 'success') {
                        cx.tools.StatusMessage.showMessage(response.message);
                        document.getElementsByName('status-' + entityId)[0].selectedIndex = 2;
                    }
                },
                preError: function(xhr, status, error) {
                    cx.tools.StatusMessage.showMessage(error);
                }
            },
            cx.variables.get('language', 'contrexx')
        );
    }
}

// https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

var scope = 'shopDelete';
cx.bind("delete", function (deleteIds) {
    if (confirm(
        cx.variables.get('TXT_CONFIRM_DELETE', scope)+'\n'+ cx.variables.get('TXT_ACTION_IS_IRREVERSIBLE', scope)
    )) {
        window.location.replace(
            "?deleteids=" + encodeURI(deleteIds) + "&csrf=" + cx.variables.get('CSRF_PARAM', scope) + "&vg_increment_number=0"
        );
    }
}, 'shopDelete');


function toggle_header()
{
    if (typeof document.getElementsByName('headerOn')[0] == 'undefined') {
        return;
    }

    if (document.getElementsByName('headerOn')[0]) {
        var disp = (document.getElementsByName('headerOn')[0].checked ? "block" : "none");
        document.getElementById('headerLeft').parentNode.parentElement.parentElement.style.display = disp;
    }
}

function toggle_footer()
{
    if (typeof document.getElementsByName('footerOn')[0] == 'undefined') {
        return;
    }

    if (document.getElementsByName('footerOn')[0]) {
        var disp = (document.getElementsByName('footerOn')[0].checked ? "block" : "none");
        document.getElementById('footerLeft').parentNode.parentElement.parentElement.style.display = disp;
    }
}

function toggle_categories(status)
{
    var check = jQuery('#category-all:checked').length > 0;
    if(status && !check){
        return;
    }
    jQuery('.category input').prop('checked', check);
}

jQuery(document).ready(function($){
    // Add class to all orders with pending status
    cx.jQuery('.order-status select option:selected[value="0"]').closest('tr').addClass('pending');

    $('.category').change(function(){
        var check = true;
        $('.category').each(function(){
            if(!$(this).find('input').is(":checked")){
                check = false;
            }
            $('#category-all').prop('checked', check);
        });
    });
    $('#category-all').change(function(){
        toggle_categories(false);
    });
    $('#form-0-headerOn').change(function(){
        toggle_header();
    });
    $('#form-0-footerOn').change(function(){
        toggle_footer();
    });
    toggle_header();
    toggle_footer();
    toggle_categories(true);

    if (typeof document.getElementsByName('headerOn')[0] != 'undefined') {
        document.getElementById('headerRight').value = document.getElementById('form-0-headerRight').value;

        document.getElementById('headerRight').onchange = function () {
            document.getElementById('form-0-headerRight').value = document.getElementById('headerRight').value
        };
    }

    if (typeof document.getElementsByName('footerOn')[0] != 'undefined') {
        document.getElementById('footerRight').value = document.getElementById('form-0-footerRight').value;

        document.getElementById('footerRight').onchange = function () {
            document.getElementById('form-0-footerRight').value = document.getElementById('footerRight').value
        };
    }

    cx.jQuery('#vg-0-filter-field-showAllPendentOrders').click(function() {
        cx.jQuery('.vg-searchSubmit').click();
    });

    if (typeof document.getElementById('discount-group-table') != 'undefined') {
        const titleRow = document.getElementById('discount-group-table').rows[1];

        // Empty the column so that the input fields of the title row are not passed
        let c = 1;
        while (cell=titleRow.cells[c++]) {
            cell.innerHTML = '';
        }
    }

    let previousStatusId;
    cx.jQuery('.order-status select').focus(function(e) {
        previousStatusId = cx.jQuery(this).val();
    }).change(function () {
        scope = 'order';
        let updateStock = false;
        let sendMailToCrm = false;
        const statusId = cx.jQuery(this).find('option:selected').val();
        if (confirm(cx.variables.get('TXT_SHOP_CONFIRM_UPDATE_STATUS', scope))) {
            if (   shopOrder.isStockIncreasable(previousStatusId, statusId)
                || shopOrder.isStockDecreasable(previousStatusId, statusId)) {
                updateStock = true;
            }
            if (statusId == 4 && confirm(cx.variables.get('TXT_SHOP_SEND_TEMPLATE_TO_CUSTOMER', scope))) {
                sendMailToCrm = true;
            }

            let togglePending = false;
            if (statusId == 0 || previousStatusId == 0) {
                togglePending = true;
            }

            const el = cx.jQuery(this);
            cx.ajax(
                'Order',
                'updateOrderStatus',
                {
                    type: 'POST',
                    data: {
                        orderId: parseInt(cx.jQuery(this).parent().parent().find('.order-id').text()),
                        statusId: statusId,
                        oldStatusId: previousStatusId,
                        updateStock: updateStock,
                        sendMailToCrm: sendMailToCrm,
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            cx.tools.StatusMessage.showMessage(response.message);
                            if (togglePending) {
                                el.closest('tr').toggleClass('pending');
                            }
                            if (togglePending && (!getParameterByName('search') || !getParameterByName('search').includes('showAllPendentOrders=1'))) {
                                el.closest('tr').remove();
                            }
                        }
                    },
                    preError: function(xhr, status, error) {
                        cx.tools.StatusMessage.showMessage(error);
                    }
                },
                cx.variables.get('language', 'contrexx')
            );
        } else {
            cx.jQuery(this).val(previousStatusId);
        }

    })
});

var shopOrder = {
    jq: cx.jQuery,
    deletedStatus: [2, 3],
    isStockIncreasable: function(oldStatus, newStatus) {
        return    this.jq.inArray(parseInt(oldStatus), this.deletedStatus) == -1
            && this.jq.inArray(parseInt(newStatus), this.deletedStatus) != -1
            && confirm(cx.variables.get('TXT_SHOP_CONFIRM_RESET_STOCK', scope));
    },
    isStockDecreasable: function(oldStatus, newStatus) {
        return     this.jq.inArray(parseInt(oldStatus), this.deletedStatus) != -1
            && this.jq.inArray(parseInt(newStatus), this.deletedStatus) == -1
            && confirm(cx.variables.get('TXT_SHOP_CONFIRM_REDUCE_STOCK', scope));
    }
};

function updateDefault(defaultEntity) {
    cx.jQuery(".adminlist tbody tr").has('.id').each(function(index, el) {
        var entity = cx.jQuery(el);
        if (parseInt(cx.jQuery(defaultEntity).closest('tr').find('.id').text()) != parseInt(entity.find('.id').text())) {
            entity.find('.default input').removeProp('checked');
        }
    });
}

function updateCurrencyCode(selectElement)
{
    var currency_increment = cx.variables.get('CURRENCY_INCREMENT', 'currency');
    console.log(selectElement.options[selectElement.selectedIndex].text);
    var code = selectElement.value;
    var name = selectElement.options[selectElement.selectedIndex].text;
    // By default, the name is followed by the code in parentheses
    name = name.replace(/\s*\([A-Z]+\)$/, "");
    var increment = currency_increment[code];
    console.log("code "+code+", name: "+name+", increment: "+increment);
    document.getElementsByName("symbol")[0].value = code;
    document.getElementsByName("name")[0].value = name;
    document.getElementsByName("increment")[0].value = increment;
}

function updateExchangeRates(selectedElement)
{
    // The Currency ID
    var indexSelected = selectedElement.value;
    console.log(indexSelected);
    // The ID of the previous standard Currency.
    // This can only be determined if its rate is exactly 1.
    var indexStandard = -1;
    // The factor used to calculate the new exchange rates
    var standardFactor = document.getElementsByName("rate-"+indexSelected)[0].value;
//alert("standard factor: "+standardFactor);
    // If no standard factor can be determined, use 1.
    // This means that none of the rates are actually changed, except for
    // the new standard currency, whose rate is always set to 1.
    if (standardFactor <= 0) {
        standardFactor = 1;
    }
    // Find all input elements
    var arrInputElement = document.getElementsByTagName('input');
//alert("element: "+arrInputElement+", length: "+arrInputElement.length);
    // Array to store all exchange rate elements,
    // except for the new standard currency.
    var arrRateElement = new Array();
    // Loop through the input elements
    for (var i = 0; i < arrInputElement.length; ++i) {
        var element = arrInputElement[i];
        // Skip non currency rate fields
        if (!element.name.match(/^rate\-(\d+)/)) {
            continue;
        }
        // The index, aka Currency ID
        var index = RegExp.$1
        // Nothing to do for the new standard currency here.
        if (index == indexSelected) {
            continue;
        }
        // Any currency having a rate of 1 is considered as
        // being the previous standard.

        if (element.value == 1) {
            indexStandard = index;
//alert("standard: i: "+i+", value: "+element.value);
        }
        // Remember those rate elements that need to be updated.
        arrRateElement.push(element);
//alert("i: "+i+", rate: "+arrInputElement[i].value);
    }
    // Only if there was a previous standard currency with a rate of 1...

    if (indexStandard != -1) {
        for (var i = 0; i < arrRateElement.length; ++i) {
            element = arrRateElement[i];
            // ...update the other rates
            element.value = (element.value / standardFactor).toFixed(6);
        }
    }
    // Finally, set the new standard currency rate to 1.
    document.getElementsByName("rate-"+indexSelected)[0].value = "1.000000";
    return true;
}
