
var handlerEvent = function()
{
    if (editAllowed == null || !editAllowed) {
        $('error_box_content').innerHTML = notAllowedMsg;
        Effect.SlideDown('error_box', {duration : 0.5});
        window.setTimeout(function() {
            Effect.DropOut('error_box', {duration: 0.3});
        }, 1400);
    }
}



var statusMsg = function(msg)
{
    if (msg != "") {
        eval("obj = " + msg + ";");
        if (obj.status != null) {
            if (obj.status == 0) {
                $('error_box_content').innerHTML = obj.message;
                Effect.SlideDown('error_box', {duration : 0.5});
                window.setTimeout(function() {
                    Effect.DropOut('error_box', {duration: 0.3});
                }, 1400);
                return false;
            } else{
                return true;
            }
        }
    }

    return true;
}
