//(function ($) {
$J19(document).ready(function() {
    $('ul.sf-menu').superfish({
        delay: 100
    });
    $(".starter_active").addClass("active");
    if ($('#subnavigation li.level-2').length == 0) {
        $('#subnavigation').hide();
    }
    $('#subnavigation li.active').parents('ul.menu').siblings('a').removeClass('inactive').addClass('active').parent().removeClass('inactive').addClass('active');
    if ($('#subnavigation li.level-2:visible:last ul:visible').length == 0) {
        $('#subnavigation li.level-2 > a:visible:last').addClass('no-border');
    }
    if ($('#subnavigation li:visible:last').hasClass('level-3')) {
        $('#subnavigation li:visible:last').parent().addClass('no-border');
    }
    if ($('#subnavigation li:visible:last').hasClass('level-4')) {
        $('#subnavigation li:visible:last').parent().parent().parent().addClass('no-border');
    }
    $('#header').carousel({
        interval: 6000
    });
    if ($('#subnavigation li.level-2').length != 0) {
        $('h3.submenu-title').css('display', 'block');
        $('.menu-break').css('display', 'block');
    }
});
//})(jQuery);
