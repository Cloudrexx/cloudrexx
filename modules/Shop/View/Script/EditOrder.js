var scopeOrder = 'order';
document.addEventListener('DOMContentLoaded', function () {
    for (var i = 0; i < getArrProduct().length; ++i) {
        calcPrice(getArrProduct()[i]['id']);
    }

    document.getElementById('form-0-shipper').onchange = function() {
        calcPrice(0);
    }
    document.getElementsByName('status')[0].onchange = function() {
        swapSendToStatus(this.value);
    }
    swapSendToStatus();

    document.getElementsByName("shipmentAmount")[0].addEventListener('change', calcShipPaymentCost);
    document.getElementsByName("paymentAmount")[0].addEventListener('change', calcShipPaymentCost);
});


// weight settings
// units -- *MUST* be all lowercase!
units = new Array('g', 'kg', 't', '');
// factors -- match units above, defaults to gram
facts = new Array(1, 1000, 1000000, 1);

vat_included = cx.variables.get('VAT_INCLUDED', scopeOrder);

arrProducts = JSON.parse(cx.variables.get('PRODUCT_LIST', scopeOrder));

// get an array of all product IDs from the order
// index => array ('id' => order_item_id, 'value' => product_id)
function getArrProduct()
{
    var arrProductId = new Array();
    var indexId = 0;
    // Loop through all input tags
    var arrElements = document.getElementsByClassName("product_ids");
    for (var i = 0; i < arrElements.length; i++) {
        var element = arrElements[i];
        // input tags' id attribute
        var elementId = element.getAttribute("id");
        if (!elementId) continue;
        var match = elementId.match(/product_product_id-(\d+)/);
        if (!match) continue;
        var orderItemId = Number(match[1]);
        arrProductId[indexId] = new Array();
        // Order item ID
        arrProductId[indexId]['id'] = orderItemId;
        // Product ID
        arrProductId[indexId]['value'] =
            element.value;
        ++indexId;
    }
    return arrProductId;
}

// Change the order item row "orderItemId" to contain the product
// with ID "newProductId" if a product with the id "newProductId"
// doesn't exist already
function changeProduct(orderItemId, newProductId)
{
    // list of product items in the order
    var arrProductId = getArrProduct();
    // try to find the new product id in the order array.
    // if newProductId is zero, an item is about to be removed.
    if (newProductId != 0) {
        for (i = 0; i < arrProductId.length; ++i) {
            if (newProductId == arrProductId[i]['value']
                && orderItemId != arrProductId[i]['id']) {
                alert(cx.variables.get('TXT_PRODUCT_ALREADY_PRESENT',scopeOrder));
                document.getElementById("product_product_name-"+orderItemId).selectedIndex =
                    document.getElementById("product_product_id-"+orderItemId).value;
                return;
            }
        }
    }
    if (newProductId == "0") {
        // the new product ID is zero, so an item is to be deleted
        if (orderItemId == 0) {
            document.getElementById("product_product_id-"+orderItemId).value = "0";
        }
        document.getElementById("product_quantity-"+orderItemId).value = 0;
        document.getElementById("product_price-"+orderItemId).value = '0.00';
        document.getElementById("product_vat_rate-"+orderItemId).value = '0.00';
        document.getElementById("product_weight-"+orderItemId).value = '0 g';
    } else {
        // An existing items' id has been changed to another product id.
        // There has to be at least one
        var quantity = document.getElementById("product_quantity-"+orderItemId).value;
        if (quantity < 1) {
            quantity = 1;
            document.getElementById("product_quantity-"+orderItemId).value = quantity;
        }
        document.getElementById("product_product_id-"+orderItemId).value = newProductId;
        document.getElementById("product_vat_rate-"+orderItemId).value =
            arrProducts[newProductId]['percent'].toFixed(2);
        document.getElementById("product_weight-"+orderItemId).value =
            weightToString(
                getWeightGrams(arrProducts[newProductId]['weight'])
                * Number(document.getElementById("product_quantity-"+orderItemId).value)
            );
        // TODO: Deduct count type discount
        var prices = arrProducts[newProductId]['price'];
        var price = null;
        for (var count = 0; count < prices.length; count += 2) {
            var quantity_min = prices[count];
            if (quantity >= quantity_min) {
                price = prices[count+1];
                console.log("TOOK\nCount: "+count+", quantity: "+quantity+", min.: "+quantity_min+" (price: "+(prices[count+1])+")");
                break;
            }
        }
        document.getElementById("product_price-"+orderItemId).value =
            price.toFixed(2);
    }
    // Calculate the product price, row sum and the total order sum
    calcPrice(orderItemId);
}

// convert weight string to grams (integer)
function getWeightGrams(strWeight)
{
    // match positive float value
    var match = /(\d*\.?\d*)/.exec(strWeight);
    if (!match) {
        return 0;
    }
    var number = Number(match[1]);
    var factor = 1; // default is grams
    for (var unit_index = 0; unit_index < facts.length; ++unit_index) {
        // Match one of the units after a decimal point, digit, or whitespace
        var regex = new RegExp("[\\.\\d\\s]"+units[unit_index]);
        if (regex.test(strWeight)) {
            factor = facts[unit_index];
            break;
        }
    }
    var weight = number * factor;
    return weight;
}

// convert weight in grams (integer) to bigger unit, if appropriate (string)
function weightToString(intWeight)
{
    var unit_index = 0;
    // find useful unit
    while (intWeight >= 1000) {
        ++unit_index;
        intWeight /= 1000;
    }
    if (unit_index == 0) return parseInt(intWeight)+" "+units[0];
    // round weight to three significant digits, add unit
    var weight = intWeight.toPrecision(3)+' '+units[unit_index];
    return weight;
}

// update the total order weight
function updateWeight()
{
    // total weight
    var totalWeight = 0;
    // loop through input tags, search for all product weights
    var elements = document.getElementsByTagName("input");
    for (var i = 0; i < elements.length; ++i) {
        // input tags' id attribute
        var element = elements[i];
        var elementId = element.getAttribute("id");
        if (!elementId) continue;
        var match = /product_weight-(\d+)/.exec(elementId);
        if (!match) {
            continue;
        }
        var orderItemId = match[1];
        // pick number of items
        var quantity = document.getElementById("product_quantity-"+orderItemId).value
        // pick weight string from order item
        var weight = element.value.toLowerCase();
        // convert to grams (integer)
        var grams = getWeightGrams(weight);
        // Write formatted weight
        element.value = weightToString(grams);
        // sum of all items' weights
        totalWeight += grams * quantity;
    }
    // convert total weight to appropriate unit
    totalWeight = weightToString(totalWeight);
    document.getElementById("total-weight").value = totalWeight;
}

// set the price of the selected shipment method
// the given shipper id is looked up in the shipments available,
// and those are searched for the cheapest match.
function updateShipment()
{
    // who's shipping it?
    var sid = document.getElementById("form-0-shipper").value;
    // Bail out if sid is not valid -- that means that no shipment is
    // selected, and possibly not needed anyway.
    if (sid == 0) {
        document.getElementById("shippingPrice").value = 0;
        return;
    }
    // copy the price from the global variable
    var price = Number(document.getElementById("netprice").value);
    // get the total weight
    var weight = getWeightGrams(document.getElementById("total-weight").value);
    // check shipments available by this shipper
    var shipments = JSON.parse(cx.variables.get('SHIPPER_INFORMATION', scopeOrder))[sid]; //
    // find the best match for the current order weight and shipment cost.
    // arbitrary upper limit - we *SHOULD* be able to find one that's lower!
    // we'll just try to find the cheapest way to handle the delivery.
    var lowest_cost = 1e100;
    // temporary shipment cost
    var cost = 0;
    // found flag is set to the index of a suitable shipment, if encountered below.
    // if the flag stays at -1, there is no way to deliver it!
    var found = -1;
    // try all the available shipments;
    // (see Shipment.class.php::getJSArrays())
    for (var i = 0; i < shipments.length; ++i) {
        var price_free = shipments[i][2];
        var max_weight = getWeightGrams(shipments[i][1]);
        // get the shipment conditions that are closest to our order:
        // we have to make sure the maximum weight is big enough for the order,
        // or that it's unspecified (don't care)
        if ((max_weight > 0 && weight <= max_weight ) || max_weight == 0) {
            // if price_free is set, the order amount has to be higher than that
            // in order to get the shipping for free.
            if (price_free > 0 && price >= price_free) {
                // we're well within the weight limit, and the order is also expensive
                // enough to get a free shipping.
                cost = '0.00';
            } else {
                // either the order amount is too low, or price_free is unset, or zero,
                // so the shipping has to be paid for in any case.
                cost = shipments[i][3];
            }
            // we found a kind of shipment that can handle the order, but maybe
            // it's too expensive. - keep the cheapest way to deliver it
            if (cost < lowest_cost) {
                // Aahh, found a cheaper one. keep the index.
                found = i;
                lowest_cost = cost;
            }
        }
    }
    if (found != -1) {
        // after checking all the shipments, we found the lowest cost for the
        // given weight and order price. - update the shipping cost
        document.getElementsByName("shipmentAmount")[0].value =
            Number(lowest_cost).toFixed(2);
    } else {
        alert(cx.variables.get('TXT_WARNING_SHIPPER_WEIGHT',scopeOrder));
    }
}

// recalculate the entire form upto and including the total price.
// if a order item  ID is given, update the corresponding row first.
function calcPrice(orderItemId)
{
    var arrProductId = getArrProduct();
    // Recalculate the row of the selected product:
    // Determine the row price according to the quantity
    var price = 0;
    var quantity = 0;
    var newProductId =
        document.getElementById("product_product_id-"+orderItemId).value;
    if (newProductId > 0) {
        var price =
            Number(document.getElementById("product_price-"+orderItemId).value);
        var quantity =
            document.getElementById("product_quantity-"+orderItemId).value;
    }
    let priceAttributes = 0;
    if (typeof document.getElementById("product_price-"+orderItemId).dataset.priceattributes !== 'undefined') {
        priceAttributes = Number(
            document.getElementById("product_price-"+orderItemId).dataset.priceattributes
        )
    }

    document.getElementById("product_price-"+orderItemId).value =
        Number(price).toFixed(2);
    document.getElementById("product_sum-"+orderItemId).value =
        (quantity * (price + priceAttributes)).toFixed(2);
    // Totals
    var totalVat = 0;
    var totalSum = 0;
    for (var i = 0; i < arrProductId.length; ++i) {
        // update the total VAT amount
        if (vat_included) {
            let exclVat =
                Number(document.getElementById(
                    "product_sum-"+arrProductId[i]['id']).value) / (Number(document.getElementById(
                "product_vat_rate-"+arrProductId[i]['id']).value) / 100 + 1);
            totalVat += Number(document.getElementById(
                "product_sum-"+arrProductId[i]['id']).value) - exclVat;
        } else {
            totalVat +=
                Number(document.getElementById(
                    "product_vat_rate-"+arrProductId[i]['id']).value)
                * Number(document.getElementById(
                "product_sum-"+arrProductId[i]['id']).value) / 100;
        }

        // update the total order value
        totalSum += Number(document.getElementById(
            "product_sum-"+arrProductId[i]['id']).value);
    }
    var couponAmount = document.getElementById('coupon-amount');
    if (couponAmount) {
        if (couponAmount.dataset.rate > 0) {
            couponAmount.value = '-' + (totalSum / 100 * Number(couponAmount.dataset.rate)).toFixed(2);
            totalSum = (totalSum / 100 * (100-Number(couponAmount.dataset.rate))).toFixed(2);
        } else if (couponAmount.dataset.amount > 0) {
            if (couponAmount.dataset.amount >= totalSum) {
                couponAmount.value = '-' + totalSum.toFixed(2);
                totalSum = 0.00;
            } else {
                couponAmount.value = '-' + couponAmount.dataset.amount;
                totalSum = (totalSum - couponAmount.dataset.amount).toFixed(2);
            }
            
        }
    }
    // store totalSum as net total before adding VAT
    document.getElementById("netprice").value =
        (Math.round(totalSum*100)/100).toFixed(2);
    updateWeight();
    // if the total weight changes, so may the shipping conditions
    updateShipment();
    // round prices to cents
    totalVat = Math.floor(totalVat*100)/100;
    totalSum = Number(totalSum);
    if (vat_included == 0) {
        if (couponAmount) {
            totalVat -= Math.abs(Number(couponAmount.value)) * Number(document.getElementById(
                "product_vat_rate-"+arrProductId[0]['id']).value) / 100;
        }
        totalSum += Number(totalVat);
    } else {
        if (couponAmount) {
            totalVat -= Math.abs(Number(couponAmount.value)) / (1 + Number(document.getElementById(
            "product_vat_rate-"+arrProductId[0]['id']).value) / 100) * Number(document.getElementById(
            "product_vat_rate-"+arrProductId[0]['id']).value) / 100;
        }
    }
    totalVat = Math.round(totalVat / 0.05) * 0.05;

    // add shipping and payment cost
    totalSum +=
        Number(document.getElementsByName("shipmentAmount")[0].value)
        + Number(document.getElementsByName("paymentAmount")[0].value);

    totalSum = Math.round(totalSum*100)/100;
    document.getElementsByName("sum")[1].value = totalSum.toFixed(2);
    document.getElementsByName("vatAmount")[0].value = totalVat.toFixed(2);
}

function calcShipPaymentCost() {
    let totalSum = Number(document.getElementsByName("sum")[1].value);
    totalSum -= Number(this.defaultValue);
    totalSum += Number(this.value);
    document.getElementsByName("sum")[1].value = totalSum.toFixed(2);
}

function swapSendToStatus()
{
    if (document.getElementsByName("status")[0].selectedIndex == 4) {
        document.getElementById("sendMailDiv").style.display = 'inline';
    } else {
        document.getElementById("sendMailDiv").style.display = 'none';
    }
}
