CKEDITOR.on('dialogDefinition', function (event) {
    var editor = event.editor;
    var dialogDefinition = event.data.definition;
    var tabCount = dialogDefinition.contents.length;

    //Customize the advanced tab
    var advancedTab = dialogDefinition.getContents( 'advanced' );
    if (advancedTab !== null) {
        advancedTab.add({
            type: 'text',
            label: 'Srcset',
            id: 'txtdlgGenSrcSet',
            'default': ''
        });
        var style = advancedTab.get('txtdlgGenStyle');
        style['default'] = '';
    }

    //Customize the info tab
    var infoTab = dialogDefinition.getContents( 'info' );
    if (infoTab !== null) {
        infoTab.remove( 'ratioLock' );
        infoTab.remove( 'txtWidth' );
        infoTab.remove( 'txtHeight' );
    }

    //Customize the code inserted for image
    dialogDefinition.onOk = function (e) {
        var dialog = this;
        var img = editor.document.createElement( 'img' );
        setTagAttribute(img, 'src', dialog.getValueOf('info', 'txtUrl'));
        setTagAttribute(img, 'alt', dialog.getValueOf('info', 'txtAlt'));
        setTagAttribute(img, 'id', dialog.getValueOf('advanced', 'linkId'));
        setTagAttribute(img, 'dir', dialog.getValueOf('advanced', 'cmbLangDir'));
        setTagAttribute(img, 'lang', dialog.getValueOf('advanced', 'txtLangCode'));
        setTagAttribute(img, 'longdesc', dialog.getValueOf('advanced', 'txtGenLongDescr'));
        setTagAttribute(img, 'class', dialog.getValueOf('advanced', 'txtGenClass'));
        setTagAttribute(img, 'title', dialog.getValueOf('advanced', 'txtGenTitle'));
        setTagAttribute(img, 'style', dialog.getValueOf('advanced', 'txtdlgGenStyle'));
        setTagAttribute(img, 'srcset', dialog.getValueOf('advanced', 'txtdlgGenSrcSet'));

        var html = img;
        if (dialog.getValueOf('Link', 'txtUrl')) {
            var aTag = editor.document.createElement( 'a' );
            setTagAttribute(aTag, 'href', dialog.getValueOf('Link', 'txtUrl'));
            setTagAttribute(aTag, 'target', dialog.getValueOf('Link', 'cmbTarget'));
            aTag.setHtml(img.getOuterHtml());
            html = aTag;
        }
        editor.insertElement(html);
    };

    var setTagAttribute = function (tag, attrName, attrVal) {
        if (!tag || !attrName) {
            return;
        }

        if (attrVal) {
            //If the attribute is style, check and remove the property
            //values of width and height from style attribute
            if (attrName == 'style') {
                attrVal = removeProperty(/^(width|height)$/, attrVal);
            }
            tag.setAttribute(attrName, attrVal);
        }
    };

    var removeProperty = function (pattern, value) {
        if (!pattern || !value) {
            return '';
        }

        var property = value.split(';'), result = [];
        $J.each(property, function(i, v) {
            var propertyName = v.split(':')[0];
            if (pattern.test($J.trim(propertyName)) === false) {
               result.push(v);
            }
        });
        return result.length > 0 ? result.join(';') : '';
    };

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
                    dialog.find('select[name=size] option:first-child').attr('value', callback.data[0].datainfo.width);
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
                                    var image, style, thumbnail = $J("[name='size']").val();
                                    image = callback.data[0].datainfo.thumbnail[thumbnail];
                                    if (typeof image === 'undefined') {
                                        image = callback.data[0].datainfo.filepath;
                                    }
                                    dialogDefinition.dialog.setValueOf('info', 'txtUrl', image);

                                    // set shadowbox image
                                    shadowboxOption = dialogDefinition.dialog.getValueOf('advanced', 'txtdlgGenShadowbox');
                                    if (shadowboxOption) {
                                        var originalImage = image.replace(/\.thumb_([^.]+)\.(.{3,4})$/, '.$2').replace(/\.thumb$/,'')
                                        dialogDefinition.dialog.setValueOf('advanced', 'txtdlgGenShadowboxSrc', originalImage);
                                    }

                                    //Set max-width to style
                                    style = removeProperty(/^(max-width|width|height)$/, dialogDefinition.dialog.getValueOf('advanced', 'txtdlgGenStyle'));
                                    dialogDefinition.dialog.setValueOf('advanced', 'txtdlgGenStyle', style + 'max-width: ' + thumbnail + 'px;');

                                    //Set default value to srcSet
                                    var srcSetValue = [];
                                    $J.each(callback.data[0].datainfo.thumbnail, function(i, v) {
                                        srcSetValue.push(v + ' ' + i + 'w');
                                    });
                                    dialogDefinition.dialog.setValueOf('advanced', 'txtdlgGenSrcSet', srcSetValue.join(', '));
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
                    if (event.sender.getSelectedElement()) {
                        var srcset = event.sender.getSelectedElement().getAttribute('srcset');
                        dialogDefinition.dialog.setValueOf('advanced', 'txtdlgGenSrcSet', srcset);
                    }
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
