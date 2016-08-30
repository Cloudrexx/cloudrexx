var shopUseJsCart = true;

cx.jQuery(function() {
    //hideCart();
    showCart('<ul><li class="loading">' +
        cx.variables.get('TXT_SHOP_CART_IS_LOADING', 'shop/cart') +
        '</li></ul>');
    cx.jQuery.ajax(cx.variables.get('url', 'shop/cart')
        + '&r=' + Math.random(), {
        dataType: 'json',
        success: shopUpdateCart,
        error: function() {
            showCart('<ul><li class="not-loaded">' + cx.variables.get('TXT_SHOP_COULD_NOT_LOAD_CART', 'shop/cart') +
                '</li></ul>');
        }
    });
});

function hideCart() {
    var cart = cx.jQuery('#shopJsCart')
    if (!cart) return;
    cart.hide();
}

function showCart(html) {
    var cart = cx.jQuery('#shopJsCart')
    if (!cart) return;
    cart.html(html).show();
}

function shopUpdateCart(data, textStatus, jqXHR) {
    try {
        objCart = data;
//console.log('Cart: '+objCart.toSource());
        if (cx.jQuery('#shopJsCart').length == 0) {
//console.log('No shopJsCart!');
            return;
        }
        if (objCart.item_count == 0) {
//console.log('Empty cart!');
            showCart('<ul><li class="empty">' + cx.variables.get('TXT_EMPTY_SHOPPING_CART', 'shop/cart') +
                '</li></ul>');
            return;
        }
        cart = '';
        cx.jQuery.each(objCart.items, function(n, i) {
            cartProduct = cartProductsTpl.replace('{SHOP_JS_PRODUCT_QUANTITY}', i.quantity);
            cartProduct = cartProduct.replace('{SHOP_JS_PRODUCT_TITLE}', i.title + i.options_cart);
            cartProduct = cartProduct.replace('{SHOP_JS_PRODUCT_PRICE}', i.price);
            cartProduct = cartProduct.replace('{SHOP_JS_TOTAL_PRICE_UNIT}', objCart.unit);
            cartProduct = cartProduct.replace('{SHOP_JS_PRODUCT_ID}', i.cart_id);
            cart += cartProduct;

            //Update Quanity-Field for Minimum-Order-Quanity-Validation
            if(cx.jQuery('input[name="productId"]').length > 0){

                cx.jQuery('input[name="productId"]').each(function(){
                    var elProductId = cx.jQuery(this);
                    var elProductForm = elProductId.closest("form");
                    var elProductQuanity = elProductForm.find('input[name="orderQuanity"]');

                    if(elProductId.val() == i.id && elProductQuanity.length > 0){
                        var orderQuanity = elProductQuanity.val()
                        var effectiveMinimumQuanity = i.minimum_order_quantity - i.quantity;
                        elProductQuanity.attr('data-minimum-order-quantity',effectiveMinimumQuanity);
                    }
                })
            }
        })
        cart = cartTpl.replace('{SHOP_JS_CART_PRODUCTS}', cart);
        // Old
        cart = cart.replace('{SHOP_JS_PRDOCUT_COUNT}', objCart.item_count);
        // New
        cart = cart.replace('{SHOP_JS_PRODUCT_COUNT}', objCart.item_count);
        cart = cart.replace('{SHOP_JS_TOTAL_PRICE}', objCart.total_price);
        cart = cart.replace('{SHOP_JS_TOTAL_PRICE_UNIT}', objCart.unit);
        showCart(cart);
    } catch (e) {
    }
    request_active = false;
}
