/**
 * MultiSite
 * @author: Thomas Däppen <thomas.daeppen@comvation.com>
 * @version: 1.0
 * @package: contrexx
 * @subpackage: coremodules_multisite
 */

/**
 * Position the maintenance-indication-bar
 */
function toggleMaintenanceIndicationBar(show,cookie,noResize) {
    // TODO: implement as setting option and then set through cxjs
    var timeout = new Date('2019-12-20T01:00:00.000Z');
    if (show) {
        // check if maintenance indication bar has been approved before
        var value = "; " + document.cookie;
        var parts = value.split("; ClxMultiSiteMaintenanceIndication=");
        if (
            parts.length == 2 &&
            parts.pop().split(";").shift() == timeout.getTime()
        ) {
            return toggleMaintenanceIndicationBar(false);
        }

        cx.jQuery('#MultiSiteMaintenanceIndication').show();
    } else {
        cx.jQuery('#MultiSiteMaintenanceIndication').hide();
        if (cookie) {
            document.cookie = 'ClxMultiSiteMaintenanceIndication=' + timeout.getTime() + '; path=/; expires=' + timeout.toGMTString();
        }
    }

    if (noResize) {
        return;
    }

    // fetch the current vertical position of the body
    var toolbarOffset = parseInt(cx.jQuery("body").css("padding-top"));
    if (!toolbarOffset) {
        toolbarOffset = 0;
    }

    // position the body and the maintenance-indication-bar
    var bodyOffset = 0;
    var MaintenanceIndicationBarHeight = parseInt(cx.jQuery("#MultiSiteMaintenanceIndication").outerHeight());
    if (show) {
        bodyOffset = MaintenanceIndicationBarHeight;
    } else {
        bodyOffset = -MaintenanceIndicationBarHeight;
    }
    cx.jQuery("body").css("padding-top", (bodyOffset + toolbarOffset) + "px");
    cx.jQuery("#MultiSiteMaintenanceIndication").css({
        top: toolbarOffset + "px"
    });
}

cx.ready(function() {
    toggleMaintenanceIndicationBar(true);

    // hide maintenance indication bar when maximizing the wysiwyg editor.
    // we do not need to adjust the position of the body as the wysiwyg
    // editor will take care of this (therefore the argument noResize is
    // set to false)
    if (typeof CKEDITOR != 'undefined') {
        CKEDITOR.on('instanceReady', function(ev) {
            ev.editor.on('maximize', function(evt) {
                if (evt.data == 1) {
                    toggleMaintenanceIndicationBar(false, false, true);
                } else {
                    toggleMaintenanceIndicationBar(true, false, true);
                }
            });
        });
    }
});
