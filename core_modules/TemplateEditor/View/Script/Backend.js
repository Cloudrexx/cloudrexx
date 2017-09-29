/**
 * This file is loaded by the abstract SystemComponentBackendController
 * You may add own JS files using
 * \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/FileName.css', 1));
 * or remove this file if you don't need it
 */

function updateOption(optionName,optionData, callback){
    jQuery('#saveOptionsButton').attr("disabled", "disabled");
    jQuery.post( "index.php?cmd=JsonData&object=TemplateEditor&act=updateOption&tid="+cx.variables.get('themeid','TemplateEditor'), { optionName: optionName, optionData:optionData }, function (reponse) {
        if (reponse.status != 'error'){
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
        }
        callback(reponse);
        jQuery('#saveOptionsButton').removeAttr("disabled");
    }, "json");
}

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
                element: '.option-list > .option',
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
