CKEDITOR.on('dialogDefinition', function (event) {
    var editor = event.editor;
    var dialogDefinition = event.data.definition;
    var tabCount = dialogDefinition.contents.length;
    for (var i = 0; i < tabCount; i++) {
        if (dialogDefinition.contents[i] == undefined) {
            continue;
        }
        var browseButton = dialogDefinition.contents[i].get('browse');
        if (browseButton !== null) {
            /**
             * Handling image selection.
             */
            if (browseButton.filebrowser.target == 'info:txtUrl' || browseButton.filebrowser.target == 'info:src') {
                var targetType = browseButton.filebrowser.target.split(':');
                browseButton.hidden = false;
                var filelistCallback = function (callback) {
                    if (callback.type == 'close') {
                        return;
                    }
                    $J.ajax({
                        type: "GET",
                        url: cx.variables.get('cadminPath') + "index.php?cmd=jsondata&object=MediaBrowser&act=createThumbnails&file=" + callback.data[0].datainfo.filepath
                    });
                    var dialog = cx.variables.get('jquery', 'mediabrowser')(cx.variables.get('thumbnails_template', 'mediabrowser'));
                    var image = dialog.find('.image');
                    image.attr('src', callback.data[0].datainfo.filepath);
                    bootbox.dialog({
                        title: cx.variables.get('TXT_FILEBROWSER_SELECT_THUMBNAIL', 'mediabrowser'),
                        message: dialog.html(),
                        buttons: {
                            success: {
                                label: cx.variables.get('TXT_FILEBROWSER_SELECT_THUMBNAIL', 'mediabrowser'),
                                className: "btn-success",
                                callback: function () {
                                    var image, thumbnail = $J("[name='size']").val();
                                    if (thumbnail == 0) {
                                        image = callback.data[0].datainfo.filepath;
                                    } else {
                                        image = callback.data[0].datainfo.thumbnail[thumbnail];
                                    }
                                    dialogDefinition.dialog.setValueOf(targetType[0], targetType[1], image);

                                    // set shadowbox image
                                    shadowboxOption = dialogDefinition.dialog.getValueOf('advanced', 'txtdlgGenShadowbox');
                                    if (shadowboxOption) {
                                        var originalImage = image.replace(/\.thumb_([^.]+)\.(.{3,4})$/, '.$2').replace(/\.thumb$/,'')
                                        dialogDefinition.dialog.setValueOf('advanced', 'txtdlgGenShadowboxSrc', originalImage);
                                    }
                                }
                            }
                        }
                    });
                };
                browseButton.onClick = function (dialog, i) {
                    editor._.filebrowserSe = this;
                    //editor.execCommand ('image');
                    cx.variables.get('jquery', 'mediabrowser')('#ckeditor_image_button').trigger("click", {
                        callback: filelistCallback,
                        cxMbViews: 'filebrowser,uploader',
                        cxMbStartview: 'filebrowser'
                    });
                };
                dialogDefinition.dialog.on('show', function (event) {
                    var that = this;
                    setTimeout(function () {
                        var inputfield = that.getValueOf('info', 'txtUrl');
                        if (inputfield == '') {
                            cx.variables.get('jquery', 'mediabrowser')('#ckeditor_image_button').trigger("click", {
                                callback: filelistCallback,
                                cxMbViews: 'filebrowser,uploader',
                                cxMbStartview: 'filebrowser'
                            });
                        }
                    }, 2);
                });
            }
            /**
             * Handling node links.
             */
            else if (browseButton.filebrowser.target == 'Link:txtUrl' || browseButton.filebrowser.target == 'info:url') {
                var target = browseButton.filebrowser.target.split(':');
                var sitestructureCallback = function (callback) {
                    var link;
                    if (callback.type == 'close') {
                        return;
                    }
                    if (callback.data[0].node) {
                        link = callback.data[0].node;
                    } else {
                        link = callback.data[0].datainfo.filepath;
                    }
                    dialogDefinition.dialog.setValueOf(target[0], target[1], link);
                    /**
                     * Protocol field exists only in the info tab.
                     */
                    if (target[0] == 'info') {
                        dialogDefinition.dialog.setValueOf('info', 'protocol', '');
                    }
                };
                browseButton.hidden = false;
                browseButton.onClick = function (dialog, i) {
                    //editor.execCommand ('image');
                    cx.variables.get('jquery', 'mediabrowser')('#ckeditor_image_button').trigger("click", {
                        callback: sitestructureCallback,
                        cxMbViews: 'uploader,filebrowser,sitestructure',
                        cxMbStartview: 'Sitestructure'
                    });
                };
            }
        }
    }
});
