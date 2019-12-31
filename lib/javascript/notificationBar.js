/**
 * Can show messages or a loading bar.
 * @param theContainer the element to show messages / loading bar in.
 */
var NotificationBar = function(theContainer) {
    var loadingImagePath = cx.variables.get("basePath", "contrexx") + 'lib/javascript/jquery/jstree/themes/default/throbber.gif';

    var $ = cx.jQuery;
    var container = $(theContainer);
    container.addClass('notificationBar');

    var empty = function() {
        container.empty();  
    };

    var show = function() {
        container.fadeIn();
    };

    var hide = function() {
        container.fadeOut();
    };

    var message = function(type, message) {
        empty();

        var span = $('<span></span>');
        span.addClass(type);
        span.html(message);
        
        span.appendTo(container);

        show();
    };
    var showLoadingImg = function() {
        empty();

        var img = $('<img></img>');
        img.attr('src',loadingImagePath);
        img.appendTo(container);
        var span = $('<span></span>');
        span.css({ 'float': 'left' });
        span.html('&nbsp;&nbsp;Loading...');
        span.insertAfter(img);

        show();
    };

    var hideLoadingImg = function() {
        hide();        
        empty();
    };

    return { 
        message: {
            info	: function(msg) {
                message('info	', msg);
            },
            warn: function(msg) {
                message('warn', msg);
            },
            error: function(msg) {
                message('error', msg);
            },
            hide: function() {
                hide();
                empty();
            }
        },
        loading: {
            start: showLoadingImg,
            stop: hideLoadingImg
        }
    };
};
