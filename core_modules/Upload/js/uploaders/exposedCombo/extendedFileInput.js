var ExtendedFileInput = function(options) {
    var field = options.field; //the input field we extend

    //fetch the uploader instance...
    var fileUploader = cx.instances.get(options.instance,'uploader');
    //...and the corresponding dialog.
    var fileUploaderDialog = fileUploader.dialog();
    //fetch the upload widget
    var uploadWidget = cx.instances.get(options.widget,'upload/folderWidget');

    //called if user clicks on the field
    var inputClicked = function() {
        fileUploaderDialog.open();
        return false;
    };

    //called if user closes upload window
    var uploadWindowClosed = function() {
        uploadWidget.refresh();
    };

    //register events
    field.bind('click', inputClicked);
    fileUploaderDialog.bind('close', uploadWindowClosed);

    field.removeAttr('disabled'); //enable the field.
};
