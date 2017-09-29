var calendarFrontend = {

    _validation : function(form) {
        $form = form;
        that  = this;
        var frmError = false;

        $form.find('[class*=validate]').each(function(){
            $field = $J(this);
            if ( !that._validateField($field) ) {
                $field.css('border', '#ff0000 1px solid');
                $J("#calendarErrorMessage").show();
                frmError = true;
            } else {
                $field.css('border', '');
            }
        });

        if ( frmError ) {
            return false;
        }

        return true;
    },
    _validateField : function(field) {
        /**
         * inspired from validation Engine
         */
        var rules = /validate\[(.*)\]/.exec($field.attr('class'));

        if (!rules)
            return false;

        var str = rules[1];
        var rules = str.split(/\[|,|\]/);
        // Fix for adding spaces in the rules
        for (var i = 0; i < rules.length; i++) {
            rules[i] = rules[i].replace(" ", "");
            // Remove any parsing errors
            if (rules[i] === '') {
                    delete rules[i];
            }
        }

        for (var i = 0; i < rules.length; i++) {
            switch (rules[i]) {
                case "event_title":
                    language = field.attr("data-id");
                    if ($J("#showIn_"+language).is(":checked")) {
                        var field_val      = $J.trim( field.val() );
                        if (
                                   ( !field_val )
                        ) {
                                return false;
                        }
                        return true;
                    }
                    return true;
                    break;
                case "required":
                    switch (field.prop("type")) {
                        case "text":
                        case "password":
                        case "textarea":
                        case "file":
                        case "select-one":
                        case "select-multiple":
                        default:
                                var field_val      = $J.trim( field.val() );
                                if (
                                           ( !field_val )
                                ) {
                                        return false;
                                }
                                return true;
                                break;
                        case "radio":
                        case "checkbox":
                                var form = field.closest("form");
                                var name = field.attr("name");
                                if (form.find("input[name='" + name + "']:checked").size() == 0) {
                                        if (form.find("input[name='" + name + "']:visible").size() == 1)
                                                return true;
                                        else
                                                return false;
                                }
                                break;
                    }
                    break;
            }
        }


    }
};
var modifyEvent = {
    // elm => jquery object
    _handleSeriesEventRowDisplay : function(elm){
      if (elm.is(":checked")) {
          $J('.series-event-row').show();
          showOrHide();
      } else {
          $J('.series-event-row').hide();
      }
    },
    _handleAllDayEvent: function(elm) {
        if (elm.is(":checked")) {
            // Timepicker should be shown atleast once before disable
            // Instead of showing, initialization of datepicker will solve the issue
            cx.jQuery(".startDate").datetimepicker('setDate', cx.jQuery( ".startDate" ).data('prevDate'));
            cx.jQuery(".endDate").datetimepicker('setDate', cx.jQuery( ".endDate" ).data('prevDate'));
            cx.jQuery(".startDate, .endDate").datetimepicker('disableTimepicker');
        } else {
            cx.jQuery(".startDate, .endDate").datetimepicker('enableTimepicker');
        }
        cx.jQuery(".startDate").datetimepicker('setDate', cx.jQuery( ".startDate" ).data('prevDate'));
        cx.jQuery(".endDate").datetimepicker('setDate', cx.jQuery( ".endDate" ).data('prevDate'));
    },
    _isNumber : function(evt) {
      evt = (evt) ? evt : window.event;
      var charCode = (evt.which) ? evt.which : evt.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) {
          return false;
      }
      return true;
    }
};

$J(function(){
    $J(".all_day").click(function(){
        modifyEvent._handleAllDayEvent($J(this));
    });
    var $eventTab = $J("#event-tabs");
    $eventTab.tabs();
    $eventTab.tabs( "select", "#event-tab-"+$J(".lang_check:checked").first().data('id') );

    $J("#formModifyEvent").submit(function(){
        form = $J(this);
        return calendarFrontend._validation(form);
    });
    $J(".lang_check").each(function(index) {
       if (!$J(this).is(":checked")) {
           $eventTab.tabs( "disable", index );
           $J('.input_field_'+ $J(this).val()).hide();
       }
    });
    showOrHideExpandMinimize();
    $J(".lang_check").click(function(){
        if ($J(".lang_check:checked").length < 1) {
            showOrHideExpandMinimize();
            return false;
        }
        langIndex = $J(".lang_check").index($J(this));
        if ($J(this).is(":checked")) {
            // enable current language selection and switch to it
            $eventTab.tabs( "enable", langIndex );
            $eventTab.tabs( "select", langIndex );
            $J('.input_field_'+ $J(this).val()).show();
        } else {
            $eventTab.tabs( "select", "#event-tab-"+$J(".lang_check:checked").first().data('id') );
            $eventTab.tabs( "disable", langIndex );
            $J('.input_field_'+ $J(this).val()).hide();
        }
        showOrHideExpandMinimize();
    });
    $J("#event-type").change(function(){
        $J(".event-description").hide();
        $J(".event-redirect").hide();
        if ($J(this).val() == '0') {
            $J(".event-description").show();
        } else {
            $J(".event-redirect").show();
        }
    });
    $J( ".eventLocationType" ).click(function(){
        showOrHidePlaceFields($J(this).val(), 'place');
    });
    $J( ".eventHostType" ).click(function(){
        showOrHidePlaceFields($J(this).val(), 'host');
    });
    cx.jQuery('.eventInputfieldDefault').each(function(){
        var id = cx.jQuery(this).data('id');
        var relatedFieldPrefix = 'event';
        cx.jQuery(this).data('lastDefaultValue', cx.jQuery(this).val());

        cx.jQuery(this).keyup(function(){
            var that = cx.jQuery(this);
            var id = cx.jQuery(this).data('id');

            var relatedFieldPrefix = 'event';
            cx.jQuery.each(activeLang, function(i, v) {
                if (   cx.jQuery('input[name="showIn[]"]:checked').length == 1
                    || cx.jQuery('#'+ relatedFieldPrefix + '_' + id +'_'+ v).val() == that.data('lastDefaultValue')
                ) {
                    cx.jQuery('#'+ relatedFieldPrefix + '_' + id +'_'+ v).val(that.val());
                }
            });
            cx.jQuery(this).data('lastDefaultValue', cx.jQuery(this).val());
        });

        cx.jQuery('#'+ relatedFieldPrefix + '_' + id +'_'+ defaultLang).keyup(function(){
            var id = cx.jQuery(this).data('id');
            var relatedFieldPrefix = 'event';
            cx.jQuery('#'+ relatedFieldPrefix + '_' + id +'_0').val(cx.jQuery(this).val());
            cx.jQuery('#'+ relatedFieldPrefix + '_' + id +'_0').data('lastDefaultValue', cx.jQuery(this).val());
        });
  cx.jQuery("#category").chosen({
    placeholder_text_multiple: "",
    display_disabled_options: false,
    display_selected_options: false
  });
    });
});
function ExpandMinimize(toggle){
    var elm1 = document.getElementById('event_' + toggle + '_Minimized');
    var elm2 = document.getElementById('event_' + toggle + '_Expanded');

    elm1.style.display = (elm1.style.display=='none') ? 'block' : 'none';
    elm2.style.display = (elm2.style.display=='none') ? 'block' : 'none';
}
function showOrHidePlaceFields(inputValue, type) {
    if (inputValue == '1') {
        $J( "div.event_"+type+"_manual" ).css("display", "table-row");
        $J( "div.event_"+type+"_mediadir" ).css("display", "none");
    } else {
        $J( "div.event_"+type+"_manual" ).css("display", "none");
        $J( "div.event_"+type+"_mediadir" ).css("display", "table-row");
    }
}
function showOrHideExpandMinimize() {
    if (cx.jQuery('input[name="showIn[]"]:checked').length > 1) {
        cx.jQuery('.input_field_expand').show();
    } else {
        cx.jQuery('.event_expanded_block').each(function(){
            if (cx.jQuery(this).is(':visible')) {
                cx.jQuery(this).closest('div.event_multilingual').find('.event_minimized_block').show();
                cx.jQuery(this).hide();
            }
        });
        cx.jQuery('.input_field_expand').hide();
        cx.jQuery('.event_minimized_block').find('input').trigger('keyup');
    }
}
