/**
 * @param theConfig Object {
 *   div: <advancedFileUploader-div>
 *   uploaders: [
 *     {
 *       type: 'uploader_type',
 *       description: 'uploader_name'
 *     }
 *   ],
 *   uploadId: upload_id,
 *   switchUrl: 'switch_url',
 *   responseUrl: 'response_url',
 *   otherUploadersCaption: 'captionstring'
 * }
 */
var ComboUploader = function(theConfig) {
    var $ = $J; //we want jquery at $ internally

    var config = theConfig;
    var uploaders = theConfig.uploaders;
    var div = $(config.div);

    var curType = 'form';

    //loads code of another uploader via ajax
    var switchUploader = function(type) {
        $.post(
            config.switchUrl,
            {
                uploadType: type,
                uploadId: config.uploadId
            },
            function(data) {
                var uploaderDiv = div.find('.uploader:first');
                uploaderDiv.html(data);
                curType = type;
            },
            'html' //we specify html here to make sure embedded js is executed
        );
    };

    var initUploader = function (flashSupported, javaSupported, form) {
        var browserRuntimes = { 'flash': flashSupported, 'java': javaSupported, 'form': form};
        //uploader type => runtime relation
        var typeRuntimes = {
            'pl': 'flash',
            'jump' : 'java',
            'form' : 'form'
        };

        //makes a click-handler for the switch-links
        var switchLinkClicked = function(type)
        {
            return function() {
                switchUploader(type);
                return false;
            };
        };

        //initialize 'advanced'-menu
        $(uploaders).each(function(index,uploader) {
            //can the browser display the runtime?
            if(browserRuntimes[typeRuntimes[uploader.type]]) {
                var link = $('<a></a>').attr('href','');
                link.bind('click', switchLinkClicked(uploader.type));
                link.html(uploader.description);
                link.appendTo(div.find('.uploaderLinks:first'));
            }
        });

        //initialize 'advanced'-link
        div.find('.advancedLink:first').bind('click',function() {
            var advancedLink = div.find('.advancedLink:first');
            if(!advancedLink.hasClass('expanded')) {
                div.find('.uploaderLinks:first').fadeIn();
                advancedLink.addClass('expanded');
                advancedLink.html(config.otherUploadersCaption + ' &laquo;');
            }
            else {
                div.find('.uploaderLinks:first').fadeOut();
                advancedLink.removeClass('expanded');
                advancedLink.html(config.otherUploadersCaption + ' &raquo;');
            }
            return false;
        });

        //checks if a player is enabled
        var playerEnabled = function(type) {
            var found = false;
            $.each(uploaders, function(index, uploader) {
                if(uploader.type == type)
                    found = true;
                });
            return found;//successfull
        };

        //initialize correct player
        if(playerEnabled('pl') && browserRuntimes.flash) //flashy enough for pluploader
            switchUploader('pl');
        else if(playerEnabled('jump') && browserRuntimes.java)//try java if not
            switchUploader('jump');
    };

    if(uploaders.length > 1) { //multiple uploaders to choose from, load functionality to switch
        //check what is supported by the browser
        var getJavaVersion = function() {
            var arrVersion;
            // Walk through the full list of mime types.
            for (var i = 0, size=navigator.mimeTypes.length; i < size; i++) {
                // The jpi-version is the plug-in version.  This is the best version to use.
                if ((arrVersion = navigator.mimeTypes[i].type.match(/^application\/x-java-applet;jpi-version=(.*)$/)) !== null) {
                    return arrVersion[1];
                }
            }
            return null;
        }

        var checkRuntimes = function(completeFunction) {
            var flashSupported = swfobject.getFlashPlayerVersion().major >= 10;

            var javaSupported = false;
            if (navigator != undefined) {
                var javaVersion = getJavaVersion();
                if (navigator.javaEnabled() && javaVersion !== null && parseFloat(javaVersion.substring(0,3)) > 1.4) {
                    javaSupported = true;
                }
                completeFunction(flashSupported, javaSupported, true);
            } else {
                cx.include('lib/javascript/deployJava.js', function() {
                    $(deployJava.getJREs()).each(function(index,jre) { //look for an installed jre > 1.4 (jumploader minimum)
                        if(parseFloat(jre.substring(0,3)) > 1.4)
                            javaSupported = true;
                    });
                    completeFunction(flashSupported, javaSupported, true);
                }, false);
            }
        };
        //remember the browser's capabilities
        checkRuntimes(function(flashSupported, javaSupported, forms) {
            initUploader(flashSupported, javaSupported, forms);
        });
    }
    else { //only a single player, most likely advanced uploading was disabled
        div.find('.advancedLink:first').remove(); //remove advanced link
    }

    //'upload more' clicked after uploading
    div.find('.moreButton').bind('click', function() {
        div.find('.uploadView').show();
        div.find('.responseView').hide();

        switchUploader(curType);
    });

    var uploadResponse = function() {
        $.get(
            config.responseUrl,
            {
                upload: config.uploadId
            },
            function(data) {
                //sort out empty responses
                if(!data.fileCount && !data.messages) {
                    setTimeout(uploadResponse, 1000);
                    return;
                }

                if(data.messages && data.messages.length > 0) {
                    var html = '<ul>';
                    for(var i = 0; i < data.messages.length; i++) {
                        var d = data.messages[i];
                        var message = d.message;
                        var status = d.status;
                        var file = d.file;

                        html += '<li class="'+status+'"><strong>'+file+'</strong>: '+message+'</li>';
                    }
                    html += '</ul>';
                    div.find('.responseView .message .errors .fileList').html(html);
                    div.find('.responseView .message .errors').show();
                }
                else {
                    div.find('.responseView .message .errors').hide();
                }

                var fileCount = data.fileCount;
                if(fileCount > 0) {
                    div.find('.responseView .message .files').show();
                    div.find('.responseView .message .files .count').html(fileCount);
                }
                else {
                    div.find('.responseView .message .files').hide();
                }

                div.find('.responseView').show();
                div.find('.uploadView').hide();
                setTimeout(uploadResponse, 1000);
            },
            'json'
        );
    };

    //hello mr. ugly hack!
    //periodically check whether the upload finished and display response if yes.
    //we do this polling because of the api mess (jumploader: global callbacks, pl: nice,
    //simple: todo)
    setTimeout(uploadResponse, 1000);

    return {
        refresh: function() {
            switchUploader(curType);
        },
        //the finish button is used for exposedCombo-uploaders only, so they have to call this function.
        displayFinishButton: function(callback) {
            div.find('.finishButton').bind('click', callback).show();
        }
    };
};
