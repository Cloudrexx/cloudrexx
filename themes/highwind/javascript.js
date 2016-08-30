(function($) {
    $(document).ready(function() {

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
        // Add .parent class to appropriate menu items
        $('ul.menu').parent().addClass('parent');

        // Add the 'show-nav' class to the body when the nav toggle is clicked
    jQuery( '.nav-toggle' ).click(function(e) {

        // Prevent default behaviour
        e.preventDefault();

        // Add the 'show-nav' class
        jQuery( 'body' ).toggleClass( 'show-nav' );

    });


    // Remove the 'show-nav' class from the body when the nav-close anchor is clicked
    jQuery('.nav-close').click(function(e) {

        // Prevent default behaviour
        e.preventDefault();

        // Remove the 'show-nav' class
        jQuery( 'body' ).removeClass( 'show-nav' );
    });

    // Remove the 'show-nav' class from the body when the use clicks (taps) outside #navigation
    var hasParent = function(el, id) {
        if (el) {
            do {
                if (el.id === id) {
                    return true;
                }
                if (el.nodeType === 9) {
                    break;
                }
            }
            while((el = el.parentNode));
        }
        return false;
    };

    if (jQuery(window).width() < 991) {
        if (jQuery('body')[0].addEventListener){
            document.addEventListener('touchstart', function(e) {
            if ( jQuery( 'body' ).hasClass( 'show-nav' ) && !hasParent( e.target, 'navigation' ) ) {
                // Prevent default behaviour
                e.preventDefault();

                // Remove the 'show-nav' class
                jQuery( 'body' ).removeClass( 'show-nav' );
            }
        }, false);
        } else if (jQuery('body')[0].attachEvent){
            document.attachEvent('ontouchstart', function(e) {
            if ( jQuery( 'body' ).hasClass( 'show-nav' ) && !hasParent( e.target, 'navigation' ) ) {
                // Prevent default behaviour
                e.preventDefault();

                // Remove the 'show-nav' class
                jQuery( 'body' ).removeClass( 'show-nav' );
            }
        });
        }
    }
        /**
         * Scroll to top
         */
        $(function() {
            $('.back-to-top').click(function() {
                $('body,html').animate({
                    scrollTop: 0
                }, 800);
                return false;
            });
        });
        $(window).scroll(function() {
            if ($(this).scrollTop() > 200) {
                $('.back-to-top').fadeIn();
            } else {
                $('.back-to-top').fadeOut();
            }
        });
    });
})(jQuery);
