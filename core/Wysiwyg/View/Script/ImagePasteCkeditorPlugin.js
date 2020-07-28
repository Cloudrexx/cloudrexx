var wysiwygEditorMode = [];
for (var instanceName in CKEDITOR.instances) {
    CKEDITOR.instances[instanceName].on('paste', function (event) {
        var result   = wysiwygGetDataFromContent(event.data.dataValue);
        var callback = function(event, value) {
            event.editor.insertHtml(value);
        };

        if (!result.files || result.files.length === 0) {
            event.data.dataValue = result.content;
            return;
        }

        //Upload process for pasted images
        wysiwygUploadInlineFile(event, result.files, result.dataSrc, result.content, callback);
        event.data.dataValue = '';
    });
    CKEDITOR.instances[instanceName].on('mode', function (event) {
        if (wysiwygEditorMode[event.editor.name] == null) {
            wysiwygEditorMode[event.editor.name] = event.editor.mode;
        }
        if (
            wysiwygEditorMode[event.editor.name] === 'source' &&
            event.editor.mode === 'wysiwyg'
        ) {
            var result   = wysiwygGetDataFromContent(event.editor.getData());
            var callback = function(event, value) {
                    event.editor.setData(value);
                };
            if (!result.files || result.files.length === 0) {
                event.editor.setData(result.content);
                return;
            }

            //Upload process for pasted images
            wysiwygUploadInlineFile(event, result.files, result.dataSrc, result.content, callback);
        }
        wysiwygEditorMode[event.editor.name] = event.editor.mode;
    });
}

/**
 * get file object from Data image present in the content
 *
 * @param {string} content     content
 * @returns {object}
 */
function wysiwygGetDataFromContent(content)
{
    var match,
        files = [],
        dataSrc = [],
        data;
    var imagePattern = {
        dataImagePattern:   /<img\s+[^>]*src=([\'\"])(data\:(\s|)image\/(\w{3,4})\;base64\,(\s|)([^\'\"]*)\s*)\1[^>]*>/g,
        inlineImagePattern: /url\(([\'\"])(data\:(\s|)image\/(\w{3,4})\;base64\,(\s|)([^\'\"]*)\s*)\1(\))/g
    };

    data = content;
    for (patternIdx in imagePattern) {
        while (match = imagePattern[patternIdx].exec(content)) {
            var file = wysiwygCreateBlobFromBase64String(match[2]);
            if (!file) {
                data = data.replace(match[0], '');
            } else {
                dataSrc[file.name] = [match[0], match[2]];
                files.push(file);
            }
        }
    }

    return {'files' : files, 'dataSrc' : dataSrc, 'content' : data};
}

/**
 * Do the upload process
 *
 * @param {object} event   event object
 * @param {array}  files   array of upload files
 * @param {array}  dataSrc array of data URI with file name
 * @param {string} content pasted content
 */
function wysiwygUploadInlineFile(event, files, dataSrc, content, callback)
{
    var uploaderId = cx.variables.get('ckeditorUploaderId', 'wysiwyg'),
        targetPath = cx.variables.get('ckeditorUploaderPath', 'wysiwyg');

    if (uploaderId == null || targetPath == null) {
        return;
    }
    cx.jQuery('<a/>')
        .attr('id', 'wysiwygPasteUploadButton_' + uploaderId)
        .attr('style', 'display:none')
        .appendTo(document.body);
    var options = {
        runtimes: 'html5,flash,silverlight,html4',
        multi_selection: true,
        max_file_size: '500mb',
        browse_button: 'wysiwygPasteUploadButton_' + uploaderId,
        url: cx.variables.get('cadminPath','contrexx') + '?cmd=JsonData&object=Uploader&act=upload&csrf=' + cx.variables.get('csrf'),
        flash_swf_url: cx.variables.get('basePath','contrexx') + 'lib/plupload/js/Moxie.swf',
        silverlight_xap_url: cx.variables.get('basePath','contrexx') + 'lib/plupload/js/Moxie.xap',
        prevent_duplicates: true,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Check-CSRF': 'false'
        },
        chunk_size: cx.variables.get('chunk_size','uploader'),
        max_retries: 3,
        multipart_params: {
            "path": targetPath
        }
    };

    var uploader = new plupload.Uploader(options);
    uploader.bind('BeforeUpload', function (up, files) {
        //Show loading message
        wysiwygShowStatusMessage(
            cx.jQuery('<div />').html(
                cx.jQuery('<div/>')
                .attr('id', 'loading')
                .html(
                    cx.jQuery('<img/>')
                    .attr('src', '/lib/javascript/jquery/jstree/themes/default/throbber.gif')
                    .attr('alt', 'Loading')
                ).append(
                    cx.jQuery('<span />')
                    .text('Loading...')
                )
            ).html(),
            null,
            true
        );
    });

    uploader.bind('PostInit', function (up) {
        up.addFile(files);
    });

    uploader.bind('FilesAdded', function (up, files) {
        up.start();
    });

    uploader.bind('FileUploaded', function (up, file, res) {
        var response   = cx.jQuery.parseJSON(res.response),
            currentImg = dataSrc[file.name][0];
        if (response.status != 'error') {
            content = content.replace(dataSrc[file.name][1], targetPath + file.name);
        } else {
            content = content.replace(currentImg, '');
        }
    });

    uploader.bind('UploadComplete', function (up, files) {
        callback(event, content);
        wysiwygShowStatusMessage('', null, false);
    });

    uploader.init();
}

/**
 * Show success/error message
 *
 * @param {string}  message message content
 * @param {string}  status  upload status
 * @param {boolean} lock    lock
 */
function wysiwygShowStatusMessage(message, status, lock)
{
    var showTime;
    if (lock) {
        showTime = null;
        if (cx.jQuery('#content #load-lock').length > 0) {
            cx.jQuery('#content #load-lock').show();
        } else {
            cx.jQuery('<div />')
                .attr('id', 'load-lock')
                .appendTo('#content')
                .css({
                    'position': 'absolute',
                    'opacity': '0.5',
                    'background-color': 'white',
                    'z-index': '500',
                    'height': '100%',
                    'width': '100%',
                    'top': '0',
                    'left': '0'
                });
        }
    } else {
        showTime = 1;
        cx.jQuery('#content #load-lock').hide();
    }
    cx.tools.StatusMessage.showMessage(message, status, showTime);
}

/**
 * Create file by using data URI
 *
 * @param {string} dataURI data URI
 *
 * @returns {mOxie.File}
 */
function wysiwygCreateBlobFromBase64String(dataURI) {
    /* convert base64/URLEncoded data component to raw binary data held in a string */
    try {
        var byteString;
        if (dataURI.split(',')[0].indexOf('base64') >= 0) {
            byteString = atob(dataURI.split(',')[1]);
        } else {
            byteString = unescape(dataURI.split(',')[1]);
        }

        /* separate out the mime component*/
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

        /* write the bytes of the string to a typed array */
        var typedArray = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            typedArray[i] = byteString.charCodeAt(i);
        }

        var resultingBlob =  new Blob([typedArray], {type:mimeString});
        return new mOxie.File(null, resultingBlob);
    } catch (ex) {
        return false;
    }
}
