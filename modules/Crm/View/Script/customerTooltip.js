$J(function(){
    var hideDelay = 50;
    var currentID;
    var hideTimer = null;

    // One instance that's reused to show info for the current person
    var container = $J('<div id="personPopupContainer">'
        + '<table width="" border="0" cellspacing="0" cellpadding="0" align="center" class="personPopupPopup">'
        + '<tr>'
        + '   <td class="corner topLeft"></td>'
        + '   <td class="top"></td>'
        + '   <td class="corner topRight"></td>'
        + '</tr>'
        + '<tr>'
        + '   <td class="left">&nbsp;</td>'
        + '   <td><div id="personPopupContent"></div></td>'
        + '   <td class="right">&nbsp;</td>'
        + '</tr>'
        + '<tr>'
        + '   <td class="corner bottomLeft">&nbsp;</td>'
        + '   <td class="bottom">&nbsp;</td>'
        + '   <td class="corner bottomRight"></td>'
        + '</tr>'
        + '</table>'
        + '</div>');

    $J('body').append(container);

    $J('.personPopupTrigger').live('mouseover', function()
    {
        // format of 'rel' tag: pageid,personguid
        var settings = $J(this).attr('rel').split(',');
        var pageID = settings[0];
        currentID = settings[1];

        // If no guid in url rel tag, don't popup blank
        if (currentID == '')
            return;

        if (hideTimer)
            clearTimeout(hideTimer);

        var pos = $J(this).offset();
        var width = $J(this).width();
        container.css({
            left: (pos.left) + 'px',
            top: pos.top + 10 + 'px'
        });

        $J('#personPopupContent').html('&nbsp;');

        $J.ajax({
            type: 'GET',
            url: 'index.php?cmd=Crm&act=customertooltipdetail',
            data: 'contactid=' + pageID,
            success: function(data)
            {
                $J('#personPopupContent').html(data);

            }
        });

        container.css('display', 'block');
    });

    $J('.personPopupTrigger').live('mouseout', function()
    {
        if (hideTimer)
            clearTimeout(hideTimer);
        hideTimer = setTimeout(function()
        {
            container.css('display', 'none');
        }, hideDelay);
    });

    // Allow mouse over of details without hiding details
    $J('#personPopupContainer').mouseover(function()
    {
        if (hideTimer)
            clearTimeout(hideTimer);
    });

    // Hide after mouseout
    $J('#personPopupContainer').mouseout(function()
    {
        if (hideTimer)
            clearTimeout(hideTimer);
        hideTimer = setTimeout(function()
        {
            container.css('display', 'none');
        }, hideDelay);
    });
});
