document.addEventListener("DOMContentLoaded", function() {
    var cookieNote = document.getElementById("cookie-note");
    var cookieOk = document.getElementById("cookie-note-ok");
    if (!cookieNote || !cookieOk) {
        return;
    }
    var value = "; " + document.cookie;
    var parts = value.split("; ClxCookieNote=");
    if (
        parts.length == 2 &&
        parts.pop().split(";").shift() == "accepted"
    ) {
        cookieNote.style.display = "none";
        if (typeof cxCookieNoteAccepted === 'function') {
            cxCookieNoteAccepted(false);
        }
    }
    cookieOk.addEventListener(
        "click",
        function () {
            // Set cookie expire limit based on the value of config 'cookieNoteTtl'
            var date = new Date(), expires = '', expireTime = '';
            switch (cookieNoteTtl) {
                case 'week':
                    date.setTime(date.getTime() + 7 * 24 * 60 * 60 * 1000);
                    expireTime = date.toUTCString();
                    break;
                case 'month':
                    date.setMonth(date.getMonth() + 1);
                    expireTime = date.toUTCString();
                    break;
                case 'year':
                    date.setFullYear(date.getFullYear() + 1);
                    expireTime = date.toUTCString();
                    break;
                case 'unlimited':
                    date.setFullYear(date.getFullYear() + 10);
                    expireTime = date.toUTCString();
                    break;
                default:
                    break;
            }
            if (expireTime) {
                expires = ' expires=' + expireTime + ';';
            }
            document.cookie = 'ClxCookieNote=accepted; path=/;' + expires;
            cookieNote.style.display = "none";
            if (typeof cxCookieNoteAccepted === 'function') {
                cxCookieNoteAccepted(true);
            }
        }
    );
});
