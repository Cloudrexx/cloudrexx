(function($) {
    $(document).ready(function() {
        /* Scroll Start */
        $(window).scroll(function() {
            if ($(this).width() > 1199) {
                if ($(this).scrollTop() > 105) {
                    $('.header').css({position: 'fixed', top: '0'});
                } else {
                    $('.header').css({position: 'relative', top: '102px'});
                }
            }
        });
        /* Scroll End */
        /*Mobile naviagtion START here*/
        $(".toggle-menu").click(function() {
            $(".toggle-menu").css({background: '#2e2e2e', color: '#fff', 'border-bottom': '0'});
            $(".mobile-nav").slideToggle('400', function() {
                if ($(".mobile-nav").is(":hidden")) {
                    $(".toggle-menu").css({background: '#fff', color: '#575756', 'border-bottom': '1px solid #575756'});
                } else {
                    $(".toggle-menu").css({background: '#2e2e2e', color: '#fff', 'border-bottom': '0'});
                }
            });
        });
        /*Mobile naviagtion END here*/
        $('.carousel').carousel({
            interval: 2000
        });
        $('#social-media div').css('float', 'left');
        $('.profile.networks .btn-google').removeClass('btn-google').addClass('btn-google-plus');
        $('.profile.networks .fa-google').removeClass('fa-google').addClass('fa-google-plus');
        $('.profile.networks .fa-facebook').removeClass('fa-facebook').addClass('fa-facebook-square');
        /* Metanavigation Start */
        if ($('#select-language a').length > 1) {
            var activeLanguage = $('#select-language a.active');
            activeLanguage.remove();
            $('#select-language a:not(\'.active\')').slice(0, 1).before(activeLanguage);
            $('#select-language a.active').click(function() {
                $('#select-language').toggleClass('active');
                $(':not(\'#select-language a\')').click(function() {
                    $('#select-language').toggleClass('active');
                    $(this).unbind('click');
                });
                return false;
            });
        } else {
            $('#select-language a').addClass('alone');
        }
        if ($('#metanavigation .login-toggle').length > 0) {
            var hideTimeout = null;
            $('#metanavigation .login-toggle').children('a').click(function(e) {
                e.preventDefault();
                if ($(this).next('div').css('display') == 'none') {
                    $(this).addClass('active');
                    $(this).next('div').show();
                } else {
                    $(this).removeClass('active');
                    $(this).next('div').hide();
                }
            });
            if ($('#metanavigation .login-toggle').children('div').hover(function() {
                clearTimeout(hideTimeout);
            }, function() {
                hideTimeout = setTimeout(function() {
                    $('#metanavigation .login-toggle').children('a').removeClass('active');
                    $('#metanavigation .login-toggle').children('div').hide();
                }, 2000);
            }))
                ;
        }
        /* Metanavigation End */
        /* Navigation Start */
        $('#navigation-wrapper .navigation > li').hover(function() {
            $(this).children('ul').animate({
                height: 'toggle',
                opacity: 'toggle'
            }, 150);
        }, function() {
            $(this).children('ul').hide();
        });
        $('.navigation li.level-1:first').addClass('first');
        $('.navigation li.level-1:last').addClass('last');
        $('.navigation li.level-1').children('ul').each(function() {
            $(this).children('li:last').addClass('last');
        });
        $('.navigation li.active').parents('ul.menu').siblings('a').removeClass('starter_normal').addClass('starter_active').parent().removeClass('starter_normal').addClass('starter_active');
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
        /* Navigation End */
        /* Shop Start */
        $('#shop-categories li a:last').addClass('last');
        $('#shop-currencies a:last').addClass('last');
        /* Shop End */
        $(".login-toggle a.open").click(function() {
            $('.login-toggle').removeClass('open');
            $('.login-toggle').addClass('open');
            $(".login").slideDown();
            return false;
        });
        $(".login-toggle a.hidetext").click(function() {
            $('.login-toggle').removeClass('open');
            $(".login").slideUp();
            return false;
        });
    });
})(jQuery);
