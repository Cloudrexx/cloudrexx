var scope = 'manufacturer';
cx.bind("delete", function (deleteIds) {
    if (confirm(
        cx.variables.get('TXT_CONFIRM_DELETE_MANUFACTURER', scope)+'\n'+ cx.variables.get('TXT_ACTION_IS_IRREVERSIBLE', scope)
    )) {
        window.location.replace(
            "?deleteids=" + encodeURI(deleteIds) + "&csrf=" + cx.variables.get('CSRF_PARAM', scope) + "&vg_increment_number=0"
        );
    }
}, 'manufacturer');