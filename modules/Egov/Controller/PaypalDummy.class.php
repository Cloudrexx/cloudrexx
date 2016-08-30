<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/* PayPal crashtest dummy */

// Please choose:

$paypalUriNok = '';
$paypalUriOk  = '';
$paypalUriIpn = '';

$strForm = '';
$ipn = false;

foreach ($_POST as $name => $value) {
    if ($name == 'cancel_return') {
        if (!FWValidator::isUri($value)) {
            continue;
        }
        $paypalUriNok = $value;
        continue;
    } elseif ($name == 'return') {
        if (!FWValidator::isUri($value)) {
            continue;
        }
        $paypalUriOk  = $value;
        continue;
    } elseif ($name == 'notify_url') {
        if (!FWValidator::isUri($value)) {
            continue;
        }
        $paypalUriIpn = $value;
        continue;
    } elseif ($name == 'cmd') {
        if ($value == '_notify-validate') {
            die("VERIFIED");
        }
        continue;
    }
    addParam($name, $value);
}

function addParam($name, $value)
{
    global $strForm;
    $strForm .= "        <tr><td>".contrexx_input2xhtml($name)."</td><td><input type=\"text\" name=\"".contrexx_input2xhtml($name)."\" value=\"".urlencode($value)."\" /></td></tr>\n";
}

die(
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <title>PayPal Dummy</title>
    <script type="text/javascript">
    // <![CDATA[
    function submitResult(result) {
      targetUri = "";
      if (result == -1) {
        targetUri = "'.$paypalUriIpn.'";
      }
      if (result == 0) {
        targetUri = "'.$paypalUriNok.'";
      }
      if (result == 1) {
        targetUri = "'.$paypalUriOk.'";
      }
      if (result == 2) {
        targetUri = "'.$paypalUriNok.'";
      }
      document.paypal.action=targetUri;
      document.paypal.submit();
    }
    // ]]>
    </script>
  </head>
  <body>
    <form name="paypal" method="post" action="'.htmlspecialchars($paypalUriNok).'">
      <table summary="">
'.$strForm.
'      </table>
      <input type="button" value="Notification"
        onclick="submitResult(-1);" />&nbsp;
      <input type="button" value="Abort"
        onclick="submitResult(0);" />&nbsp;
      <input type="button" value="Success"
        onclick="submitResult(1);" />&nbsp;
      <input type="button" value="Cancel"
        onclick="submitResult(2);" />&nbsp;
    </form>
  </body>
</html>
');

?>
