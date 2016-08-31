function _window_width() {
    return document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientWidth:document.body.clientWidth;
}
function is_mobile_phone() {
    // width might need adjustment.. iphones are 480x320 for instance, the
    // EeePC is 800x400. As nobody uses 800x600 anymore for web layouts,
    // we should treat the Eee as a mobile device.
    return (_window_width() > 0 && _window_width() <= 800);
}

function is_smallscreen_set() {
    var a   = document.cookie;
    if (a.search('smallscreen=') != -1) {
        return true;
    }
    return false;
}

if (is_mobile_phone() && !is_smallscreen_set()) {
    var url = window.location.href;
    if (url.search(/\?/) == -1) {
        window.location = url + '?smallscreen=1';
    }
    else {
        window.location = url + '&smallscreen=1';
    }
}
