(function($) {
    $(document).ready(function() {
        jQuery("ul.sf-menu").supersubs({
            minWidth: 10, // requires em unit.
            maxWidth: 13, // requires em unit.
            extraWidth: 3  // extra width can ensure lines don't sometimes turn over due to slight browser differences in how they round-off values
                    // due to slight rounding differences and font-family
        }).superfish();
        jQuery('#topnav').tinyNav({
            active: 'starter_active'
        });
        /*Header trasparent START here*/
        var fadeSpeed = 10, fadeTo = 0.6, topDistance = 30;
        var topbarME = function() {
            $('.header').fadeTo(fadeSpeed, 1);
        }, topbarML = function() {
            $('.header').fadeTo(fadeSpeed, fadeTo);
        };
        var inside = false;
        $(window).scroll(function() {
            position = $(window).scrollTop();
            if (position > topDistance && !inside) {
                topbarML();
                $('.header').bind('mouseenter', topbarME);
                $('.header').bind('mouseleave', topbarML);
                inside = true;
            }
            else if (position < topDistance) {
                topbarME();
                $('.header').unbind('mouseenter', topbarME);
                $('.header').unbind('mouseleave', topbarML);
                inside = false;
            }
        });
        /*Header trasparent END here*/
        /*Mobile naviagtion START here*/
        function navHide() {
            $('section, footer').animate({right: '0'}, 10);
            $('.mobile-nav').css('visibility', 'hidden');
            $('.btn-navbar').one("click", navOpen);
        }
        function navOpen() {
            $('section, footer').animate({right: '100%'}, 10);
            $('.mobile-nav').css('visibility', 'visible');
            $('.btn-navbar').one("click", navHide);
        }
        $('.btn-navbar').one("click", navOpen);
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
        $('#back-top').click(function() {
            $('html, body').animate({
                scrollTop: 0
            }, 400);
            return false;
        });
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
