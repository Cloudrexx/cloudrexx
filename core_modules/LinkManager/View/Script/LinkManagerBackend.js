/**
 * LinkManagerBackend js for ajax loading and clicking functionality for changing link status(link solved or not)
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

$J(document).ready(function() {
    //solving a link
    $J(".solvedBox").click(function() {
        var id   = $J(this).data('id');
        var stat = $J(this).data('status');
        $elm = $J(this);
        lastXhr = $J.ajax({
            url: 'index.php?cmd=jsondata&object=link&act=modifyLinkStatus&id=' + id + '&status=' + stat,
            type: "GET",
            dataType: 'json',
            success: function(res, status, xhr) {
                if (xhr === lastXhr) {
                    if (res.status == 'success') {
                        $elm.data('status', res.data.linkStatus);
                        if (res.data.linkStatus == 1) {
                            $J('td.user_' + id).text(res.data.userName);
                            $elm.attr('checked', 'checked');
                        } else {
                            $J('td.user_' + id).text("");
                            $elm.removeAttr('checked');
                        }
                        fadeOkbox(cx.variables.get('updateSuccessMsg', 'LinkManager'));
                    }
                }
            }
        });
    });
});
$J(document).ajaxStart(function() {
    $J('body').append(getMessageDiv(cx.variables.get('loadingLabel', 'LinkManager')));
}).ajaxStop(function() {
    $J("#ajaxMessageDiv").remove();
});
function getMessageDiv(mes) {
    return '<div id="ajaxMessageDiv"><img style="float: left;" src="./images/icons/ui-anim_basic_16x16.gif" alt="Loading"/><span class="ajaxMsg">' + mes + '</span></div>';
}
function getOkBox(mes) {
    return '<div class="okbox">' + mes + '</div>';
}
function fadeOkbox(msg) {
    $J('.okbox').remove();
    $J('table#subnavbar_level1').after(getOkBox(msg));
    setTimeout(function() {
        $J('.okbox').fadeOut('slow');
    }, 6000);
}
function setTableRow(tableId) {
    count = 0;
    $J('table#' + tableId + ' tbody tr:visible').each(function() {
        $J(this).removeClass("row1 row2");
        rowClass = (count % 2 == 0) ? "row1" : "row2";
        $J(this).addClass(rowClass);
        count++;
    });
}
