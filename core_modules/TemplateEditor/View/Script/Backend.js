/**
 * This file is loaded by the abstract SystemComponentBackendController
 * You may add own JS files using
 * \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/FileName.css', 1));
 * or remove this file if you don't need it
 */

/**
 * Update the option on the server over jQuery.post
 *
 * @param {string}    optionName  the name of the option
 * @param {array}     optionData  the data which should be updated
 * @param {function}  callback    function which will be executed (should return
 *                                true to enable save button (false to disable it)
 */
function updateOption(optionName, optionData, callback){
    jQuery('#saveOptionsButton').attr("disabled", "disabled");
    jQuery.post( "index.php?cmd=JsonData&object=TemplateEditor&act=updateOption&tid="+cx.variables.get('themeid','TemplateEditor'), { optionName: optionName, optionData:optionData }, function (response) {
        if (response.status == 'error'){
            callback(response);
            return;
        }
        var previewIframe = jQuery("#preview-template-editor");
        try {
            var iframeLocation = previewIframe.get(0).contentDocument.location;
            if (iframeLocation.host == window.location.host){
                previewIframe.attr('src', iframeLocation.href);
            }
            else {
                previewIframe.attr('src', cx.variables.get('iframeUrl','TemplateEditor'));
            }
        }
        catch (e){
            previewIframe.attr('src', cx.variables.get('iframeUrl','TemplateEditor'));
        }
        // check for false. This is needed for options with no callback
        if(callback(response) !== false) {
            jQuery('#saveOptionsButton').removeAttr("disabled");
        }
    }, "json");
}
/**
 * Save the option to yaml on server over jQuery.post
 */
var saveOptions = function (){
    if (jQuery(this).attr('disabled')){
        return;
    }

    var that = this;
    bootbox.dialog({
        title: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_TITLE','TemplateEditor'),
        message: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_CONTENT','TemplateEditor'),
        buttons: {
            success: {
                label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE','TemplateEditor'),
                className: "btn-success",
                callback: function() {
                    var loading = bootbox.dialog({
                        message: '<img style="margin: 30px auto; display:block;" src="' + cx.variables.get('basePath', 'contrexx') + 'lib/javascript/jquery/jstree/themes/default/throbber.gif" alt=""/>',
                        title: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE','TemplateEditor'),
                        onEscape: function() {},
                        closeButton: false
                    });
                    jQuery.post( "index.php?cmd=JsonData&object=TemplateEditor&act=saveOptions&tid="+cx.variables.get('themeid','TemplateEditor'), {}, function (response) {
                        jQuery(that).addClass('saved');
                        setTimeout(function(){
                            jQuery(that).removeClass('saved');
                        }, 2000);
                        loading.modal('hide');
                    }, "json");
                }
            },
            main: {
                label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL','TemplateEditor'),
                className: "btn-danger",
                callback: function() {
                }
            }
        }
    });

};

jQuery(function(){

    jQuery('.option.view .buttons button').click(function(){
        jQuery("#preview-template-editor").css({'width': jQuery(this).data('size')});
        jQuery('.option.view .buttons button').removeClass('active');
        jQuery(this).addClass('active');
    });

    jQuery('#saveOptionsButton').click(saveOptions);

    jQuery('.add-preset').click(function(){
        bootbox.dialog({
                title: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET_TITLE','TemplateEditor'),
                message: jQuery('#new-preset').html(),
                buttons: {
                    success: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE','TemplateEditor'),
                        className: "btn-success",
                        callback: function () {
                            var preset = jQuery('.new-preset-name').val();
                            var presetPreset = jQuery('#preset-for-preset').val();
                            jQuery.post( "index.php?cmd=JsonData&object=TemplateEditor&act=addPreset", {
                                tid: cx.variables.get('themeid','TemplateEditor'),
                                preset: preset,
                                presetpreset:presetPreset
                            }, function (response) {
                                if (response.status == 'error'){
                                    jQuery('.add-preset').trigger('click');
                                    jQuery('.new-preset-name').val(preset);
                                    jQuery('#preset-for-preset').val(presetPreset);
                                    bootbox.alert(response.message);
                                    return;
                                }
                                var newLocation = location.href.replace(/preset=[a-z0-9]+/i, "preset="+response.data.preset);
                                window.location.href = (newLocation.search('preset=') == -1 ? newLocation + "&preset=" + response.data.preset : newLocation);
                            }, "json");
                        }
                    },
                    danger: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL','TemplateEditor'),
                        className: "btn-danger"
                    }
                }
            }
        );
    });

    jQuery('.activate-preset').click(function(){
        bootbox.dialog({
                title: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_ACTIVATE_PRESET_TITLE','TemplateEditor'),
                message: jQuery('#active-preset').html(),
                buttons: {
                    success: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_YES','TemplateEditor'),
                        className: "btn-success",
                        callback: function () {
                            jQuery.post( "index.php?cmd=JsonData&object=TemplateEditor&act=activatePreset", {
                                tid: cx.variables.get('themeid','TemplateEditor'),
                                preset: jQuery('#preset').val()
                            }, function (response) {
                                window.location.href = window.location.href;
                            }, "json");
                        }
                    },
                    danger: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_NO','TemplateEditor'),
                        className: "btn-danger"
                    }
                }
            }
        );
    });

    jQuery('.remove-preset').click(function(){
        bootbox.dialog({
                title: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_TITLE','TemplateEditor'),
                message: jQuery('#remove-preset').html(),
                buttons: {
                    success: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_YES','TemplateEditor'),
                        className: "btn-success",
                        callback: function () {
                            jQuery.post( "index.php?cmd=JsonData&object=TemplateEditor&act=removePreset", {
                                tid: cx.variables.get('themeid','TemplateEditor'),
                                preset: jQuery('#preset').val()
                            }, function (response) {
                                window.location.href = window.location.href;
                            }, "json");
                        }
                    },
                    danger: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL','TemplateEditor'),
                        className: "btn-danger"
                    }
                }
            }
        );
    });

    jQuery('.reset-preset').click(function(){
        bootbox.dialog({
                title: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_TITLE','TemplateEditor'),
                message: jQuery('#reset-preset').html(),
                buttons: {
                    success: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_YES','TemplateEditor'),
                        className: "btn-success",
                        callback: function () {
                            jQuery.post( "index.php?cmd=JsonData&object=TemplateEditor&act=resetPreset", {
                                tid: cx.variables.get('themeid','TemplateEditor'),
                                preset: jQuery('#preset').val()
                            }, function (response) {
                                window.location.href = window.location.href;
                            }, "json");
                        }
                    },
                    danger: {
                        label: cx.variables.get('TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL','TemplateEditor'),
                        className: "btn-danger"
                    }
                }
            }
        );
    });

    jQuery('#layout').change(function(){
        var newLocation = location.href.replace("tid="+cx.variables.get('themeid','TemplateEditor'), "tid="+jQuery(this).val());
        window.location.href = (newLocation.search('tid=') == -1 ? newLocation + "&tid=" + jQuery(this).val() : newLocation);
    });

    jQuery('#preset').change(function(){
        var newLocation = location.href.replace(/preset=[a-z0-9]+/i, "preset="+jQuery(this).val());
        window.location.href = (newLocation.search('preset=') == -1 ? newLocation + "&preset=" + jQuery(this).val() : newLocation);
    });

    var intro = introJs();
    intro.setOptions({
        nextLabel:cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_NEXT","TemplateEditor"),
        prevLabel:cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_BACK","TemplateEditor"),
        skipLabel:cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_STOP","TemplateEditor"),
        doneLabel:cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_STOP","TemplateEditor"),
        showStepNumbers: false,
        steps: [
            {
                element: '.option.layout',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_LAYOUT_OPTION","TemplateEditor")
            },
            {
                element: '.option.preset',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_OPTION","TemplateEditor")
            },
            {
                element: '.activate-preset',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ACTIVATE","TemplateEditor")
            },
            {
                element: '.add-preset',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ADD","TemplateEditor")
            },
            {
                element: '.reset-preset',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_RESET","TemplateEditor")
            },
            {
                element: '.option.view',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION","TemplateEditor")
            },
            {
                element: '.option-list',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION_LIST","TemplateEditor"),
                position: 'right'
            },
            {
                element: '#preview-template-editor',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PREVIEW","TemplateEditor"),
                position: 'left'
            },
            {
                element: 'button.save',
                intro: cx.variables.get("TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_SAVE","TemplateEditor"),
                position: 'top'
            }
        ]
    });

    jQuery('.help').click(function(){
        jQuery('.sidebar .options').scrollTop(0);
        intro.start();
    });

});

/**
 * Get the value of a series element by its id
 *
 * @param   {string}  name     the name of which field the value is needed
 * @param   {string}  id       the id of which element the field is needed
 * @param   {array}   elements should contain all elements
 * @returns {string}           returns the value. If not found an empty string
 */
function getSeriesElementValueById (name, id, elements) {
    if (elements[id] !== undefined) {
        if (elements[id][name] !== undefined) {
            return elements[id][name];
        }
        console.error('Element value was not found in element');
    } else {
        console.error('Element not found in elements');
    }
    return '';
}

/**
 * Remove an element from a series
 *
 * @param {object} button     the button which was clicked
 */
function removeElement(button) {
    var id = jQuery(button).parent().find('input, textarea')
        .first().attr('id');
    var parent = jQuery(button).parent();
    parent.addClass('saving');

    // the last element should not be deletable
    var seriesElements = parent.parent('.elements').children('.element');
    // if there are only 2 elements left before deleting one, we hide the remove
    // button for the elements, so the last element can't be removed
    if (seriesElements.length == 2) {
        jQuery(seriesElements).children('.remove-element').hide();
    }
    // this case should not occur normally, because the delete button of the last
    // element should be hidden
    if (seriesElements.length == 1) {
        console.message('Last element is not deletable');
        return;
    }
    updateOption(
        id,
        {
            elm: '',
            action:'remove'
        },
        function () {
            parent.removeClass('saving');
            parent.addClass('saved');
            setTimeout(function () {
                parent.removeClass('saved');
            }, 2000);
            parent.slideUp(function(){
                parent.remove();
            });
        }
    )
}

/**
 * Set the toggle icon for an element
 *
 * @param {object} element the element which should get the icon
 */
function setToggleIcon(element) {
    // check if we open or close the group and set the matching class, so we
    // can load the proper icon
    if (jQuery(element).hasClass('open')) {
        jQuery(element).removeClass('open');
    } else {
        jQuery(element).addClass('open');
    }
}

jQuery(document).ready(function(){
    // if there exists only one group in the GUI open it for better usability
    if (jQuery('.groupName').length == 1) {
        var group = jQuery('.groupName').first();
        jQuery(group).siblings('.optionsInGroup').toggle();
        jQuery(group).addClass('open');
    }
    jQuery('.groupName').click(function(){
        // close all other groups
        var otherGroups = jQuery(this).parent().siblings('.optionGroup');
        jQuery(otherGroups).children('.optionsInGroup').hide();
        jQuery(otherGroups).children('.groupName').removeClass('open');
        setToggleIcon(jQuery(this));
        jQuery(this).siblings('.optionsInGroup').toggle();
    });
    jQuery('.series').children('label').click(function(){
        setToggleIcon(jQuery(this).parent());
        jQuery(this).siblings('.series-list').toggle();
    });
    jQuery('.elements').each(function(){
        // if there is only one element in a series, it should not be deletable
        var seriesElements = jQuery(this).children('.element');
        if (seriesElements.length == 1) {
            seriesElements.find('.remove-element').hide();
        }
    });
});