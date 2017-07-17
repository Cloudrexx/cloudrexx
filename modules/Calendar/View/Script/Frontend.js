/* global cx */
// Defined in \Cx\Modules\Calendar::modifyEvent()
// TODO: Avoid using globals!
/* global activeLang, defaultLang */
//console.log("activeLang ",activeLang);
//console.log("defaultLang ",defaultLang);
var calendarFrontend = {
  _validation: function(form) {
    $form = form;
    that = this;
    var frmError = false;
    $form.find('[class*=validate]').each(function() {
      $field = cx.jQuery(this);
      if (!that._validateField($field)) {
        $field.css('border', '#ff0000 1px solid');
        cx.jQuery("#calendarErrorMessage").show();
        frmError = true;
      } else {
        $field.css('border', '');
      }
    });
    if (frmError) {
      return false;
    }
    return true;
  },
  _validateField: function(field) {
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
          if (cx.jQuery("#showIn_" + language).is(":checked")) {
            var field_val = cx.jQuery.trim(field.val());
            if (!field_val) {
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
              var field_val = cx.jQuery.trim(field.val());
              if (!field_val) {
                return false;
              }
              return true;
              break;
            case "radio":
            case "checkbox":
              var form = field.closest("form");
              var name = field.attr("name");
              if (form.find("input[name='" + name + "']:checked").size() === 0) {
                if (form.find("input[name='" + name + "']:visible").size() === 1)
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
  _handleSeriesEventRowDisplay: function(elm) {
    if (elm.is(":checked")) {
      cx.jQuery('.series-event-row').show();
      showOrHide();
    } else {
      cx.jQuery('.series-event-row').hide();
    }
  },
  _handleAllDayEvent: function(elm) {
    if (elm.is(":checked")) {
      // Timepicker should be shown atleast once before disable
      // Instead of showing, initialization of datepicker will solve the issue
      cx.jQuery(".startDate").datetimepicker('setDate', cx.jQuery(".startDate").data('prevDate'));
      cx.jQuery(".endDate").datetimepicker('setDate', cx.jQuery(".endDate").data('prevDate'));
      cx.jQuery(".startDate, .endDate").datetimepicker('disableTimepicker');
    } else {
      cx.jQuery(".startDate, .endDate").datetimepicker('enableTimepicker');
    }
    cx.jQuery(".startDate").datetimepicker('setDate', cx.jQuery(".startDate").data('prevDate'));
    cx.jQuery(".endDate").datetimepicker('setDate', cx.jQuery(".endDate").data('prevDate'));
  },
  _isNumber: function(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
      return false;
    }
    return true;
  }
};
cx.jQuery(function() {
  cx.jQuery(".all_day").click(function() {
    modifyEvent._handleAllDayEvent(cx.jQuery(this));
  });
  var $eventTab = cx.jQuery("#event-tabs");
  $eventTab.tabs();
  $eventTab.tabs("select", "#event-tab-" + cx.jQuery(".lang_check:checked").first().data('id'));
  cx.jQuery("#formModifyEvent").submit(function() {
    form = cx.jQuery(this);
    return calendarFrontend._validation(form);
  });
  cx.jQuery(".lang_check").each(function(index) {
    if (!cx.jQuery(this).is(":checked")) {
      $eventTab.tabs("disable", index);
      cx.jQuery('.input_field_' + cx.jQuery(this).val()).hide();
    }
  });
  showOrHideExpandMinimize();
  cx.jQuery(".lang_check").click(function() {
    if (cx.jQuery(".lang_check:checked").length < 1) {
      showOrHideExpandMinimize();
      return false;
    }
    langIndex = cx.jQuery(".lang_check").index(cx.jQuery(this));
    if (cx.jQuery(this).is(":checked")) {
      // enable current language selection and switch to it
      $eventTab.tabs("enable", langIndex);
      $eventTab.tabs("select", langIndex);
      cx.jQuery('.input_field_' + cx.jQuery(this).val()).show();
    } else {
      $eventTab.tabs("select", "#event-tab-" + cx.jQuery(".lang_check:checked").first().data('id'));
      $eventTab.tabs("disable", langIndex);
      cx.jQuery('.input_field_' + cx.jQuery(this).val()).hide();
    }
    showOrHideExpandMinimize();
  });
  cx.jQuery("#event-type").change(function() {
    cx.jQuery(".event-description").hide();
    cx.jQuery(".event-redirect").hide();
    if (cx.jQuery(this).val() === '0') {
      cx.jQuery(".event-description").show();
    } else {
      cx.jQuery(".event-redirect").show();
    }
  });
  cx.jQuery(".eventLocationType").click(function() {
    showOrHidePlaceFields(cx.jQuery(this).val(), 'place');
  });
  cx.jQuery(".eventHostType").click(function() {
    showOrHidePlaceFields(cx.jQuery(this).val(), 'host');
  });
  cx.jQuery('.eventInputfieldDefault').each(function() {
    var id = cx.jQuery(this).data('id');
    var relatedFieldPrefix = 'event';
    cx.jQuery(this).data('lastDefaultValue', cx.jQuery(this).val());
    cx.jQuery(this).keyup(function() {
      var that = cx.jQuery(this);
      var id = cx.jQuery(this).data('id');
      var relatedFieldPrefix = 'event';
      cx.jQuery.each(activeLang, function(i, v) {
        if (cx.jQuery('input[name="showIn[]"]:checked').length === 1
          || cx.jQuery('#' + relatedFieldPrefix + '_' + id + '_' + v).val() === that.data('lastDefaultValue')
          ) {
          cx.jQuery('#' + relatedFieldPrefix + '_' + id + '_' + v).val(that.val());
        }
      });
      cx.jQuery(this).data('lastDefaultValue', cx.jQuery(this).val());
    });
    cx.jQuery('#' + relatedFieldPrefix + '_' + id + '_' + defaultLang).keyup(function() {
      var id = cx.jQuery(this).data('id');
      var relatedFieldPrefix = 'event';
      cx.jQuery('#' + relatedFieldPrefix + '_' + id + '_0').val(cx.jQuery(this).val());
      cx.jQuery('#' + relatedFieldPrefix + '_' + id + '_0').data('lastDefaultValue', cx.jQuery(this).val());
    });
  });
  cx.jQuery("#category").chosen({
    placeholder_text_multiple: "",
    display_disabled_options: false,
    display_selected_options: false
  });
});
function ExpandMinimize(toggle) {
  var elm1 = document.getElementById('event_' + toggle + '_Minimized');
  var elm2 = document.getElementById('event_' + toggle + '_Expanded');
  elm1.style.display = (elm1.style.display === 'none') ? 'block' : 'none';
  elm2.style.display = (elm2.style.display === 'none') ? 'block' : 'none';
}
function showOrHidePlaceFields(inputValue, type) {
  if (inputValue === '1') {
    cx.jQuery("div.event_" + type + "_manual").css("display", "table-row");
    cx.jQuery("div.event_" + type + "_mediadir").css("display", "none");
  } else {
    cx.jQuery("div.event_" + type + "_manual").css("display", "none");
    cx.jQuery("div.event_" + type + "_mediadir").css("display", "table-row");
  }
}
function showOrHideExpandMinimize() {
  if (cx.jQuery('input[name="showIn[]"]:checked').length > 1) {
    cx.jQuery('.input_field_expand').show();
  } else {
    cx.jQuery('.event_expanded_block').each(function() {
      if (cx.jQuery(this).is(':visible')) {
        cx.jQuery(this).closest('div.event_multilingual').find('.event_minimized_block').show();
        cx.jQuery(this).hide();
      }
    });
    cx.jQuery('.input_field_expand').hide();
    cx.jQuery('.event_minimized_block').find('input').trigger('keyup');
  }
}
