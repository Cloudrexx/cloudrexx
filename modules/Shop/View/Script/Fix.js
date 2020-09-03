/**
 * Javascript File to move the HTML elements to the correct positions. Because the placeholder CONTENT_NAVIGATION is no
 * longer in the same position in the ViewGenerator.
 */
cx.jQuery(function () {
    cx.jQuery(document).ready(function() {
        if (jQuery('#content > #subnavbar_level1 #subnavbar_level1').length > 0) {
            if (jQuery('#content > #subnavbar_level1 #subnavbar_level2').length > 0) {
                jQuery('#content > #subnavbar_level1').after(jQuery('#content > #subnavbar_level1 #subnavbar_level2'));
                jQuery('#content > #subnavbar_level2').before(jQuery('#content > #subnavbar_level1 #subnavbar_level1'));
                jQuery('#content > #subnavbar_level1').addClass('no_margin');
            } else {
                jQuery('#content > #subnavbar_level1').after(jQuery('#content > #subnavbar_level1 #subnavbar_level1'));
            }
            jQuery('#content > #subnavbar_level1')[0].remove();
        }
    });
});