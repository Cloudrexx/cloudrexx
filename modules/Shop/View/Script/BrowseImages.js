var notDefined = new Array();

var openBrowser = function openBrowser(id) {
    fieldID = id;
    cx.jQuery('#media_browser_shop').trigger('click');
    return false;
}

function deleteImage(id) {
    const noPic = cx.variables.get('SHOP_NO_PICTURE_ICON', 'shopProduct');
    document.getElementById('product-image-' + id).src = noPic;
    document.getElementById('product-image-src-' + id).value='{SHOP_NO_PICTURE_ICON}';
    document.getElementById('product-image-width-'+ id).value='0';
    document.getElementById('product-image-height-'+ id).value='0'
}

var setSelectedImage = function(data) {
    if (data.type == 'file') {
        var extension = data.data[0].datainfo.extension.toLowerCase();
        if(jQuery.inArray(extension, ['gif','png','jpg','jpeg']) == -1) {
            return;
        }
        var url = data.data[0].datainfo.filepath;

        notDefined[fieldID] = true;
        var elInput = document.getElementById("product-image-src-"+fieldID);
        elInput.setAttribute('name', 'picture['+ fieldID +'][src]');
        elInput.setAttribute('id', 'product-image-src-'+fieldID);
        elInput.setAttribute('value', decodeURIComponent(url));
        if (document.getElementById('lnk_'+fieldID) == null) {
            notDefined[fieldID] = true;
        } else {
            notDefined[fieldID] = false;
        }
        url = url.substring(1);
        var fileRegExp = /.+\/(.+)$/;
        fileRegExp.exec(url);
        var elTxt = document.createTextNode(decodeURIComponent(RegExp.$1));
        var img = document.getElementById('product-image-'+fieldID);
        img.src = '/'+url;
        var fact = 1;

        var newImg = new Image();
        newImg.onload = function() {
            var height = newImg.height;
            var width  = newImg.width;

            if (width > height) {
                fact = 80 / width;
            } else {
                fact = 80 / height;
            }
            img.style.width  = width*fact+'px';
            img.style.height = height*fact+'px';
            // set resized width and height in hidden fields
            elInput_w = document.getElementById('product-image-width-' + fieldID);
            elInput_w.setAttribute('name', 'picture['+ fieldID +'][width]');
            elInput_w.setAttribute('value', width);
            elInput_h = document.getElementById('product-image-height-' + fieldID);
            elInput_h.setAttribute('name', 'picture['+ fieldID +'][height]');
            elInput_h.setAttribute('value', height);
            if (notDefined[fieldID]) {
                var elALink = document.createElement('a');
            } else {
                var elALink = document.getElementById("lnk_"+fieldID);
            }
            elALink.setAttribute('href', decodeURIComponent(url));
            elALink.setAttribute('target', '_blank');
            elALink.setAttribute('id', 'lnk_'+fieldID);
            elALink.setAttribute('title', 'product Image');
            if (notDefined[fieldID]) {
                elALink.appendChild(elTxt);
                img.parentNode.appendChild(elALink);
                img.parentNode.insertBefore(document.createElement('br'), elALink);
            } else {
                elALink = document.getElementById("lnk_"+fieldID);
                elALink.removeChild(elALink.childNodes[0]);
                fileRegExp = /.+\/(.+)$/;
                fileRegExp.exec(decodeURIComponent(url));
                elTxt = document.createTextNode(RegExp.$1);
                elALink.appendChild(elTxt);
                img.parentNode.appendChild(elALink);
            }
        };
        newImg.src = url;
    }
};