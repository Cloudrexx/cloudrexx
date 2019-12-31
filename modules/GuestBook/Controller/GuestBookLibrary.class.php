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

/**
 * GuestBook
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_guestbook
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\GuestBook\Controller;
/**
 * GuestBook
 *
 * Library for the GuestBook
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_guestbook
 */
class GuestBookLibrary
{

    /**
    * Gets the guestbook settings
    *
    * @global    ADONewConnection
    */
    function getSettings()
    {
        global $objDatabase;
        $query = "SELECT name, value FROM ".DBPREFIX."module_guestbook_settings";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['name']] = $objResult->fields['value'];
            $objResult->MoveNext();
        }
    }


    /**
    * add URL hyperlinking function to a string
    *
    * Finds all possible protocols: http ftp https chrome irc etc...
    * Ignores links which come after a = or " so href=", href= and the like are not linked
    * Finds URLs beginning with www.
    * Accepts all URL special chars, even rarely used ones like | , # ( or )
    *
    * @param  string $string
    * @return string $string
    */
    function addHyperlinking($string)
    {
        $string = preg_replace("/((http(s?):\/\/)|(www\.))([\S\.]+)\b/i","<a href=\"http$3://$4$5\" target=\"_blank\">$2$4$5</a>", $string);
        return $string;
    }

    /**
    * Checks the url
    *
    * @param  string  $string
    * @return boolean result
    */
    function isUrl($string)
    {
        return \FWValidator::isUri($string);
    }

    /**
     * Changes the Mail address
     */
    function changeMail($mail)
    {
            $at = array('[AT]', ' AT ', '(AT)');
            srand ((double)microtime()*1000000);
            $rand = rand(0, count($at)-1);
            return $mail = preg_replace("%@%", $at[$rand], $mail);
    }


    /**
    * get email validation javascript code
    *
    * @return     string    $javascript
    */
    function _getJavaScript()
    {
        global $_ARRAYLANG;
        $strJavascript = '
            <script language="JavaScript" type="text/javascript">
            <!--
            function validate() {
                var errorMsg = "";
                if(document.getElementById("forename").value == ""){
                    errorMsg += "\t- '.$_ARRAYLANG['TXT_FORENAME'].'\n";
                }
                                if(document.getElementById("name").value == ""){
                    errorMsg += "\t- '.$_ARRAYLANG['TXT_NAME'].'\n";
                }
                if(document.getElementById("comment").value == ""){
                    errorMsg += "\t- '.$_ARRAYLANG['TXT_COMMENT'].'\n";
                }
                if(document.getElementById("location").value == ""){
                    errorMsg += "\t- '.$_ARRAYLANG['TXT_LOCATION'].'\n";
                }
                if(!document.getElementsByName("malefemale")[0].checked && !document.getElementsByName("malefemale")[1].checked){
                    errorMsg += "\t- '.$_ARRAYLANG['TXT_SEX'].'\n";
                }
                if(document.getElementById("email").value == ""){
                    errorMsg += "\t- '.$_ARRAYLANG['TXT_EMAIL'].'\n";
                } else {
                                        if(!/'.\FWValidator::REGEX_EMAIL_JS.'/.test(document.getElementById("email").value)) {
                                            errorMsg += "\n'.$_ARRAYLANG['TXT_INVALID_EMAIL_ADDRESS'].'";
                                        }
                }
                if(errorMsg.length>0){
                    errorMsg = "'.$_ARRAYLANG['TXT_FILL_OUT_ALL_REQUIRED_FIELDS'].'\n\n" + errorMsg;
                    alert(errorMsg);
                    return false;
                } else {
                    return true;
                }
            }
            //-->
            </script>';
        return $strJavascript;
    }

    /**
    * Checks the url
    *
    * @param  string  $text
    * @return string $str
    */
    function createAsciiString($text) {
        $i = 0;
        $str = '';
        while($i < strlen($text)) {
            $str .= '&#'.ord(substr($text,$i,1)).';';
            $i++;
        }
        return $str;
    }
}

?>
