(function() {
    var
    dropdownToggleHash = {};

    cx.jQuery.extend({
        dropdownToggle: function(options) {
            // default options
            options = cx.jQuery.extend({
                //switcherSelector: "#id" or ".class",          - button
                //dropdownID: "id",                             - drop panel
                //anchorSelector: "#id" or ".class",            - near field
                //noActiveSwitcherSelector: "#id" or ".class",  - dont hide
                addTop: 0,
                addLeft: 0,
                position: "absolute",
                fixWinSize: true,
                enableAutoHide: true,
                showFunction: null,
                hideFunction: null,
                alwaysUp: false
            }, options);

            var _toggle = function(switcherObj, dropdownID, addTop, addLeft, fixWinSize, position, anchorSelector, showFunction, alwaysUp) {
                fixWinSize = fixWinSize === true;
                addTop = addTop || 0;
                addLeft = addLeft || 0;
                position = position || "absolute";

                var targetPos = cx.jQuery(anchorSelector || switcherObj).offset();
                var dropdownItem = cx.jQuery("#" + dropdownID);

                var elemPosLeft = targetPos.left;
                var elemPosTop = targetPos.top + cx.jQuery(anchorSelector || switcherObj).outerHeight();

                var w = cx.jQuery(window);
                var topPadding = w.scrollTop();
                var leftPadding = w.scrollLeft();

                if (position == "fixed") {
                    addTop -= topPadding;
                    addLeft -= leftPadding;
                }

                var scrWidth = w.width();
                var scrHeight = w.height();

                if (fixWinSize
                    && (targetPos.left + addLeft + dropdownItem.outerWidth()) > (leftPadding + scrWidth))
                    elemPosLeft = Math.max(0, leftPadding + scrWidth - dropdownItem.outerWidth()) - addLeft;

                if (fixWinSize
                    && (elemPosTop + dropdownItem.outerHeight()) > (topPadding + scrHeight)
                    && (targetPos.top - dropdownItem.outerHeight()) > topPadding
                    || alwaysUp)
                    elemPosTop = targetPos.top - dropdownItem.outerHeight();

                dropdownItem.css(
                {
                    "position": position,
                    "top": elemPosTop + addTop,
                    "left": elemPosLeft + addLeft
                });
                if (typeof showFunction === "function")
                    showFunction(switcherObj, dropdownItem);

                dropdownItem.toggle();
            };

            var _registerAutoHide = function(event, switcherSelector, dropdownSelector, hideFunction) {
                if (cx.jQuery(dropdownSelector).is(":visible")) {
                    var $targetElement = cx.jQuery((event.target) ? event.target : event.srcElement);
                    if (!$targetElement.parents().andSelf().is(switcherSelector + ", " + dropdownSelector)) {
                        if (typeof hideFunction === "function")
                            hideFunction($targetElement);
                        cx.jQuery(dropdownSelector).hide();
                    }
                }
            };

            if (options.switcherSelector && options.dropdownID) {
                var toggleFunc = function(e) {
                    _toggle(cx.jQuery(this), options.dropdownID, options.addTop, options.addLeft, options.fixWinSize, options.position, options.anchorSelector, options.showFunction, options.alwaysUp);
                };
                if (!dropdownToggleHash.hasOwnProperty(options.switcherSelector + options.dropdownID)) {
                    cx.jQuery(options.switcherSelector).live("click", toggleFunc);
                    dropdownToggleHash[options.switcherSelector + options.dropdownID] = true;
                }
            }

            if (options.enableAutoHide && options.dropdownID) {
                var hideFunc = function(e) {
                    var allSwitcherSelectors = options.noActiveSwitcherSelector ?
                    options.switcherSelector + ", " + options.noActiveSwitcherSelector : options.switcherSelector;
                    _registerAutoHide(e, allSwitcherSelectors, "#" + options.dropdownID, options.hideFunction);

                };
                cx.jQuery(document).unbind("click", hideFunc);
                cx.jQuery(document).bind("click", hideFunc);
            }

            return {
                toggle: _toggle,
                registerAutoHide: _registerAutoHide
            };
        }
    });
})();
function setTableRow(tableId) {
    count = 0;
    $J('table#'+tableId+' tbody tr:visible').each(function(){
        $J(this).removeClass("row1 row2");
        rowClass = (count%2 == 0) ? "row1" : "row2";
        $J(this).addClass(rowClass);
        count++;
    });
}
$J(function(){

    $J('form#searchcustomer #term').autocomplete({
            minLength: 2,
            delay: 500,
            source: function( request, response ) {
                lastXhr = $J.ajax({
                    url         : 'index.php?cmd=JsonData&object=Crm&act=searchContacts',
                    type        : "POST",
                    data        : $J('form#searchcustomer input[type!=hidden], form#searchcustomer select').serialize(),
                    dataType    : 'json',
                    success: function(res, status, xhr) {
                        if (xhr === lastXhr) {
                            response( res.data );
                    }
                    }
                });
            },
            select: function(event, ui) {
                // cx is activated for all page
                var customer_id = ui.item.id;
                window.location.replace('./index.php?cmd=Crm&act=customers&tpl=showcustdetail&id='+customer_id+'&csrf='+ cx.variables.get('csrf', 'contrexx'));
            }
        });
});
