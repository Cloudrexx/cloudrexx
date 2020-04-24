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
document.addEventListener("DOMContentLoaded", function() {
    // fetch the current vertical position of the body
    var toolbarOffset = parseInt(
        window.getComputedStyle(document.querySelector("body")).paddingTop
    );
    if (!toolbarOffset) {
        toolbarOffset = 0;
    }

    // position the body and the workbench-warning-bar
    const el = document.querySelector("#workbenchWarning");
    const style = window.getComputedStyle(el);
    var height = parseInt(style.height);
    if (style.boxSizing != "border-box") {
        height += parseInt(style.paddingTop) +
        parseInt(style.paddingBottom) +
        parseInt(style.borderBottomWidth) +
        parseInt(style.borderTopWidth);
    }
    document.querySelector("body").style.paddingTop = parseInt(height + toolbarOffset) + "px";
    el.style.top = toolbarOffset + "px";
});
