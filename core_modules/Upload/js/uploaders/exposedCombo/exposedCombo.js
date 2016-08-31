/**
 * @param theConfig Object {
 *   starterElement: <exposedComboUploaderStarter-div>,
 *   uploaderDiv: <exposedComboUploader-div>,
 *   title: 'dialogtitle'
 *   comboUploader: <ComboUploader-instance>
 * }
 */
var ExposedCombo = function(theConfig) {
    var $ = cx.jQuery; //we want jQuery at $ internally
    var config = theConfig;

    var starter = $(theConfig.starterElement); //open button of the dialog
    var comboUploader = theConfig.comboUploader; //the combouploader we enhance
    var uploader = $(theConfig.uploaderDiv); //the div we expose

    var dialog;
    dialog = cx.ui.dialog({
        title: theConfig.title,
        content: uploader,
        modal: true,
        autoOpen: false,
        position: ['center',200],
        width: 700
    });

    var uploadId = theConfig.uploadId;

    //we need to refresh the uploader each time the dialog is opened.
    //to achieve this, we decorate the dialog's open method:
    var oldOpen = dialog.open;
    dialog.open = function() {
        //maybe the uploader cached information about already uploaded files - clean those up.
        comboUploader.refresh();
        oldOpen();
    };

    //add some functionality to the starter
    starter.bind('click', function() {
        dialog.open();
        return false;
    });

    //initialize the finishButton
    comboUploader.displayFinishButton(function() {
        dialog.close();
    });

    //public properties of ExposedCombo
    return {
        dialog: function() {
            return dialog;
        },
        uploadId: function() {
            return uploadId;
        }
    };
};
