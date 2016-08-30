/**
 * Workbench
 * @author: Thomas Däppen <thomas.daeppen@comvation.com>
 * @version: 1.0
 * @package: cloudrexx
 * @subpackage: coremodules_workbench
 */

/**
 * Position the workbench-warning-bar
 */
cx.ready(function() {
    // fetch the current vertical position of the body
    var toolbarOffset = parseInt(cx.jQuery("body").css("padding-top"));
    if (!toolbarOffset) {
        toolbarOffset = 0;
    }

    // position the body and the workbench-warning-bar
    cx.jQuery("body").css("padding-top", (parseInt(cx.jQuery("#workbenchWarning").outerHeight()) + toolbarOffset) + "px");
    cx.jQuery("#workbenchWarning").css({
        top: toolbarOffset + "px"
    });
});
