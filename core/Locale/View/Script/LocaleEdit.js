cx.jQuery(document).ready(function() {
    cx.jQuery("#form-0-iso1, #form-0-country").change(function() {
        generateLabel();
    });

    function generateLabel() {
        var iso1 = cx.jQuery("#form-0-iso1").val();
        var alpha2 = cx.jQuery("#form-0-country").val();

        cx.ajax(
            "Locale",
            "getGeneratedLabel",
            {
                data: {
                    iso1: iso1,
                    alpha2: alpha2
                },
                success: function(json) {
                    cx.jQuery('#form-0-label').val(json.data);
                }
            }
        );

    }
});