///////////////////////////////
// Set Variables
///////////////////////////////
var container = jQuery('.thumbs.masonry');
var colWidth;
var gridGutter = 20;
///////////////////////////////
// iPad and iPod Detection
///////////////////////////////
function isMobile(){
    return (
        (navigator.userAgent.match(/Android/i)) ||
        (navigator.userAgent.match(/webOS/i)) ||
        (navigator.userAgent.match(/iPhone/i)) ||
        (navigator.userAgent.match(/iPod/i)) ||
        (navigator.userAgent.match(/iPad/i)) ||
        (navigator.userAgent.match(/BlackBerry/))
        );
}
///////////////////////////////
// Isotope Browser Check
///////////////////////////////
function isotopeAnimationEngine(){
    if(jQuery.browser.mozilla || jQuery.browser.msie){
        return "jquery";
    }else{
        return "css";
    }
}
//////////////////////////////
// Responsive Nav
/////////////////////////////
jQuery(function(){
    var $responsive_nav = jQuery("<select />");
    jQuery("<option />", {
        "selected": "selected",
        "value": "",
        "text": "Select a page"
    }).appendTo($responsive_nav);
    $responsive_nav.appendTo("#mainNav");
    jQuery("#mainNav ul li a").each(function(){
        var nav_url = jQuery(this).attr("href");
        var nav_text = jQuery(this).text();
        if (jQuery(this).parents("li").length == 2) {
            nav_text = '- ' + nav_text;
        }
        if (jQuery(this).parents("li").length == 3) {
            nav_text = "-- " + nav_text;
        }
        if (jQuery(this).parents("li").length > 3) {
            nav_text = "--- " + nav_text;
        }
        jQuery("<option />", {
            "value": nav_url,
            "text": nav_text
        }).appendTo($responsive_nav)
    });
    field_id = "#mainNav select";
    jQuery(field_id).change(function()
    {
        value = jQuery(this).attr('value');
        window.location = value;
    });
});
///////////////////////////////
// Project Filtering
///////////////////////////////
function projectFilterInit() {
    jQuery('#filterNav a').click(function(){
        var selector = jQuery(this).attr('data-filter');
        var container = jQuery('.thumbs.masonry');
        container.isotope({
            filter: selector,
            hiddenStyle : {
                opacity: 0,
                scale : 1
            },
            resizable: false
        });
        if ( !jQuery(this).hasClass('selected') ) {
            jQuery(this).parents('#filterNav').find('.selected').removeClass('selected');
            jQuery(this).addClass('selected');
        }
        return false;
    });
}
///////////////////////////////
// Isotope Grid Resize
///////////////////////////////

function gridResize() {

}
///////////////////////////////
// Project thumbs
///////////////////////////////
function projectThumbInit() {
    if(!isMobile()) {
        jQuery(".project.small .inside a").hover(
            function() {
                jQuery(this).find('img:last').stop().fadeTo("fast", .1);
                jQuery(this).find('img:last').attr('title','');
            },
            function() {
                jQuery(this).find('img:last').stop().fadeTo("fast", 1);
            });
        jQuery(".project.small .inside").hover(
            function() {
                jQuery(this).find('.title').stop().fadeTo("fast", 1);
                jQuery(this).find('img:last').attr('title','');
            },
            function() {
                jQuery(this).find('.title').stop().fadeTo("fast", 0);
            });
    }
    var container = jQuery('.thumbs.masonry');

    gridResize();
    jQuery(".project.small").css("opacity", "1");
}
jQuery.noConflict();
jQuery(window).load(function(){
    projectThumbInit();
    projectFilterInit();

    jQuery('img').attr('title','');
});
