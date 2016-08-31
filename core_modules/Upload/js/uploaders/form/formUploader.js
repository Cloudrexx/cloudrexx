var FormUploader = function(uploaderId) {
    // upload/restrictUpload2SingleFile might not be set and must therefore be cast into Boolean
    var restrictUpload2SingleFile = Boolean(cx.variables.get('restrictUpload2SingleFile', 'upload/widget_' + uploaderId));
    if (restrictUpload2SingleFile) {
        return {};
    }

    var div = $J('#form_uploader_' + uploaderId);

    var bindDelete = function(fileDiv) {
        fileDiv.find('.delete:first').bind('click',function(event) {
            //only delete if it's not the last entry
            if(div.find('.file').length > 1) {
                fileDiv.remove();
            }
            return false; //do not follow link
        });
    };

    var add = function() {
        //take first file entry to make a copy
        var file = div.find('.file:first').clone();
        //clear selected file
        file.find('input[type=file]:first').attr('value','');
        //append the div
        file = file.appendTo(div.find('.files:first'));
        //set delete handler
        bindDelete(file);
    };

    div.find('.add:first').bind('click',function() {
        add();
        return false; //do not follow link
    });
    bindDelete(div.find('.file:first'));

    // display add/delete file functions for multi file upload
    div.find('.delete:first').css('display', 'inline-block');
    div.find('.add:first').css('display', 'block');

    return {
        add:add
    };
};
