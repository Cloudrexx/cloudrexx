(function($) {
    $(document).ready(function() {

        var count = $("#mediadir  .reference").size();
        for (var i = count; i < 12; i++) {
            $(".reference").last().after('<div class="reference grid3 graybox_entry"></div>');
        }

  //Add Grey box image when an image is not loading
        $('#mediadir .reference img').each(function() {
                $(this).load().error(function() {
                 $(this).parent().addClass('graybox_entry');
                 $(this).hide();
                });
        });
//Resize Grey box image based on real image
$(window).load(function() {
  var Image_height = $('#mediadir .reference img').height();
    if (Image_height > 0) {
    $J("#mediadir .reference.graybox_entry").css("height",Image_height);
    $J("#mediadir .reference.graybox_entry").show();
}
}).resize(function() {
  var Image_height = $('#mediadir .reference img').height();
    if (Image_height > 0) {
    $J("#mediadir .reference.graybox_entry").css("height",Image_height);
    $J("#mediadir .reference.graybox_entry").show();
}
});

        /* add div for every three child in mediadirectory overview page  */
        var entries = $J("#mediadir").children('.reference');

        for (var i = 0; i < entries.length; i += 4) {
            entries.slice(i, i + 4).wrapAll('<div class="full-width"></div>');
        }

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
        $('#navigation > li').hover(function() {
            $(this).children('ul').animate({
                height: 'toggle',
                opacity: 'toggle'
            }, 150);
        }, function() {
            $(this).children('ul').hide();
        });

        $('#navigation li.level-1:first').addClass('first');
        $('#navigation li.level-1:last').addClass('last');
        $('#navigation li.level-1').children('ul').each(function() {
            $(this).children('li:last').addClass('last');
        });
        $('#navigation li.active').parents('ul.menu').siblings('a').removeClass('starter_normal').addClass('starter_active').parent().removeClass('starter_normal').addClass('starter_active');

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

        $("#slider").responsiveSlides({
            speed: 1000
        });

       j1('#sidr-menu').sidr({
            name: 'sidr',
            side: 'right'
        });

        j1('#sidr-menu').click(function() {
            $J('body').css('overflow-y', 'hidden');
        });


        //Show the product name when hover a image
        $('#mediadir .full-width .reference img').hover(function() {
            $("#media_name").html("");
            $("#media_name").html(this.alt);
        }, function() {
            $("#media_name").html(this.alt);
        });

        $(".login-toggle a.open").click(function() {
            $('.login-toggle').removeClass('open');
            $('.login-toggle').addClass('open');
            $("header.login").slideDown();
            return false;
        });
        $(".login-toggle a.close").click(function() {
            $('.login-toggle').removeClass('open');
            $("header.login").slideUp();
            return false;
        });

        /* Start Show the produkt title in mobile */

        if ($(window).width() < 1025) {
            $("#media_name").hide();
            $('#mediadir .reference img').each(function() {
                $(this).after('<span class="produkt_title_mobile">' + this.alt + '</span>');
            });

        } else {
            $('#mediadir .reference span').remove();
            $("#media_name").show();
        }

        $(window).resize(function() {
            if ($(window).width() < 1025) {
                if (!$('#mediadir .reference span').length > 0) {
                    $("#media_name").hide();
                    $('#mediadir .reference img').each(function() {
                        $(this).after('<span class="produkt_title_mobile">' + this.alt + '</span>');
                    });
                }
            } else {
                $('#mediadir .reference span').remove();
                $("#media_name").show();
            }
        });

        /* End Show the produkt title in mobile */


    });
})(jQuery);
