cx.jQuery(document).ready(function(){
    var $ = cx.libs.jquery['jquery-nstslider'];
    // init range slider
    $('.nstSlider').nstSlider({
        'left_grip_selector': '.leftGrip',
        'right_grip_selector': '.rightGrip',
        'value_bar_selector': '.bar',
        'value_changed_callback': function(cause, leftValue, rightValue) {
            // display selected values in associated labels
            $(this).parent().find('.pull-left').text(leftValue);
            $(this).parent().find('.pull-right').text(rightValue);
            
            // copy selected values to hidden input fields
            var fieldId = $(this).attr("id");
            $(this).parent().find("#"+fieldId+"_min").val(leftValue);
            $(this).parent().find("#"+fieldId+"_max").val(rightValue);
        }
    });
})
