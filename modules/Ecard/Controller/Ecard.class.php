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
 * E-Card
 *
 * Send electronic postcards to your friends
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.1.0
 * @since       2.1.0
 * @package     cloudrexx
 * @subpackage  module_ecard
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Ecard\Controller;

/**
 * E-Card
 *
 * Send electronic postcards to your friends
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.1.0
 * @since       2.1.0
 * @package     cloudrexx
 * @subpackage  module_ecard
 * @todo        Edit PHP DocBlocks!
 */
class Ecard
{
    /**
     * Template object
     * @access  private
     * @var     \Cx\Core\Html\Sigma
     */
    public $_objTpl;
    public $pageContent;

    /**
     * Constructor
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function __construct($pageContent) {
        $this->pageContent = $pageContent;
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
    }

    /**
     * Get content page
     * @access public
     */
    function getPage()
    {
        $_GET['cmd'] = (isset($_GET['cmd'])) ? $_GET['cmd'] : '';
        switch ($_GET['cmd']) {
            case 'preview':
                $this->preview();
                break;
            case 'send':
                $this->send();
                break;
            case 'show':
                $this->showEcard();
                break;
            default: //case '':
                $this->showEcards();
                break;
        }
        return $this->_objTpl->get();
    }


    function showEcards()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate('shadowbox');

        $this->_objTpl->setTemplate($this->pageContent);
        // Initialize POST variables
        $selectedId = isset($_POST['selectedEcard']) ? intval($_POST['selectedEcard']) : 0;
        $message = !empty($_POST['ecardMessage']) ? htmlentities(contrexx_stripslashes($_POST['ecardMessage']), ENT_QUOTES, CONTREXX_CHARSET) : "";
        $recipientSalutation = !empty($_POST['ecardRecipientSalutation']) ? htmlentities(contrexx_stripslashes($_POST['ecardRecipientSalutation']), ENT_QUOTES, CONTREXX_CHARSET) : "";
        $senderName = !empty($_POST['ecardSenderName']) ? htmlentities(contrexx_stripslashes($_POST['ecardSenderName']), ENT_QUOTES, CONTREXX_CHARSET) : "";
        $senderEmail = !empty($_POST['ecardSenderEmail']) && \FWValidator::isEmail($_POST['ecardSenderEmail']) ? $_POST['ecardSenderEmail'] : "";
        $recipientName = !empty($_POST['ecardRecipientName']) ? htmlentities(contrexx_stripslashes($_POST['ecardRecipientName']), ENT_QUOTES, CONTREXX_CHARSET) : "";
        $recipientEmail = !empty($_POST['ecardRecipientEmail']) && \FWValidator::isEmail($_POST['ecardRecipientEmail']) ? $_POST['ecardRecipientEmail'] : "";

        // Get max. number of characters and lines per message
        $query = "
              SELECT `setting_name`, `setting_value`
                FROM ".DBPREFIX."module_ecard_settings";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            switch ($objResult->fields['setting_name']) {
                case "maxCharacters":
                    $maxCharacters = $objResult->fields['setting_value'];
                    break;
                case "maxLines":
                    $maxLines = $objResult->fields['setting_value'];
                    break;
            }
            $objResult->MoveNext();
        }

        $this->_objTpl->setVariable(array(
            'ECARD_MESSAGE' => $message,
            'ECARD_SENDERNAME' => $senderName,
            'ECARD_RECIPIENTNAME' => $recipientName,
            'ECARD_SENDEREMAIL' => $senderEmail,
            'ECARD_RECIPIENTEMAIL' => $recipientEmail,

            'ECARD_SALUTATION_SELECTED_MALE' =>
                ($recipientSalutation == $_ARRAYLANG['TXT_ECARD_TITLE_MALE']
                    ? ' checked="checked"' : ''
                ),
            'ECARD_SALUTATION_SELECTED_FEMALE' =>
                ($recipientSalutation == $_ARRAYLANG['TXT_ECARD_TITLE_FEMALE']
                    ? ' checked="checked"' : ''
                ),
            'TXT_ECARD_CHOOSE_IMAGE' => $_ARRAYLANG['TXT_ECARD_CHOOSE_IMAGE'],
            'TXT_ECARD_ENTER_RECIPIENT_INFO' => $_ARRAYLANG['TXT_ECARD_ENTER_RECIPIENT_INFO'],
            'TXT_ECARD_TITLE_MALE' => $_ARRAYLANG['TXT_ECARD_TITLE_MALE'],
            'TXT_ECARD_TITLE_FEMALE' => $_ARRAYLANG['TXT_ECARD_TITLE_FEMALE'],
            'TXT_ECARD_RECIPIENT_TITLE' => $_ARRAYLANG['TXT_ECARD_RECIPIENT_TITLE'],
            'TXT_ECARD_RECIPIENT_NAME' => $_ARRAYLANG['TXT_ECARD_RECIPIENT_NAME'],
            'TXT_ECARD_SENDER_NAME' => $_ARRAYLANG['TXT_ECARD_SENDER_NAME'],
            'TXT_ECARD_RECIPIENT_EMAIL' => $_ARRAYLANG['TXT_ECARD_RECIPIENT_EMAIL'],
            'TXT_ECARD_SENDER_EMAIL' => $_ARRAYLANG['TXT_ECARD_SENDER_EMAIL'],
            'TXT_ECARD_ENTER_MESSAGE' => $_ARRAYLANG['TXT_ECARD_ENTER_MESSAGE'],
            'TXT_ECARD_NUMBER_OF_CHARACTERS_LEFT' =>
                sprintf($_ARRAYLANG['TXT_ECARD_NUMBER_OF_CHARACTERS_LEFT'], $maxCharacters),
            'TXT_ECARD_NUMBER_OF_LINES_LEFT' =>
                sprintf($_ARRAYLANG['TXT_ECARD_NUMBER_OF_LINES_LEFT'], $maxLines),
            'TXT_ECARD_PREVIEW' => $_ARRAYLANG['TXT_ECARD_PREVIEW'],
        ));

        // Select motives from DB
        $query = "
            SELECT setting_value, setting_name
              FROM ".DBPREFIX."module_ecard_settings
             WHERE setting_name LIKE 'motive_%'
             ORDER BY setting_name ASC";
        $i = 0;
        $objResult = $objDatabase->Execute($query);
        // Initialize DATA placeholder
        while (!$objResult->EOF) {
            $motive = $objResult->fields['setting_value'];
            $motive = basename($motive);
            if (empty($motive)) {
                $objResult->MoveNext();
                continue;
            }

            $arrMatch = array();
            $id = 0;
            if (preg_match('/(\d+)$/', $objResult->fields['setting_name'], $arrMatch))
                $id = $arrMatch[1];

            $this->_objTpl->setVariable(array(
                'ECARD_MOTIVE_OPTIMIZED_PATH' => ASCMS_ECARD_OPTIMIZED_WEB_PATH.'/'.$motive,
                'ECARD_MOTIVE_ID' => $id,
                'ECARD_THUMBNAIL_PATH' => ASCMS_ECARD_THUMBNAIL_WEB_PATH.'/'.$motive,
                'ECARD_CSSNUMBER' => ($i % 3) + 1,
                'ECARD_IMAGE_SELECTED' =>
                    ($id == $selectedId ? ' checked="checked"' : ''),
            ));
            $this->_objTpl->parse('motiveBlock');
            if ($i % 3 == 0) {
                $this->_objTpl->parse('motiveRow');
            }
            ++$i;
            $objResult->MoveNext();
        }
        $this->_objTpl->setVariable(
            'ECARD_JAVASCRIPT', self::getJavascript($maxCharacters)
        );
    }


    function preview()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent);
        // Initialize POST variables
        $id = intval($_POST['selectedEcard']);
        $message = contrexx_input2xhtml($_POST['ecardMessage']);
        $messagePreview = nl2br($message);
        $recipientSalutation = htmlentities(contrexx_stripslashes($_POST['ecardRecipientSalutation']), ENT_QUOTES, CONTREXX_CHARSET);
        $senderName = htmlentities(contrexx_stripslashes($_POST['ecardSenderName']), ENT_QUOTES, CONTREXX_CHARSET);
        $senderEmail = \FWValidator::isEmail($_POST['ecardSenderEmail']) ? $_POST['ecardSenderEmail'] : '';
        $recipientName = htmlentities(contrexx_stripslashes($_POST['ecardRecipientName']), ENT_QUOTES, CONTREXX_CHARSET);
        $recipientEmail = \FWValidator::isEmail($_POST['ecardRecipientEmail']) ? $_POST['ecardRecipientEmail'] : '';
        // Get path from choosen motive
        $query = "
            SELECT setting_value
              FROM ".DBPREFIX."module_ecard_settings
             WHERE setting_name='motive_$id'";
        $objResult = $objDatabase->SelectLimit($query, 1);
        $selectedMotive = basename($objResult->fields['setting_value']);
        $this->_objTpl->setVariable(array(
            'ECARD_DATA' =>
                '<strong>'.$senderName.'</strong> (<a href="mailto:'.$senderEmail.'">'.
                $senderEmail.'</a>) '.$_ARRAYLANG['TXT_ECARD_HAS_SENT_YOU_AN_ECARD'],
            'ECARD_MOTIVE' =>
                '<img src="'.ASCMS_ECARD_OPTIMIZED_WEB_PATH.'/'.$selectedMotive.
                '" alt="'.$selectedMotive.'" title="'.$selectedMotive.'" />',
            'ECARD_MOTIVE_ID' => $id,
            'ECARD_MESSAGE' => $message,
            'ECARD_MESSAGE_PREVIEW' => $messagePreview,
            'ECARD_SENDER_NAME' => $senderName,
            'ECARD_SENDER_EMAIL' => $senderEmail,
            'ECARD_RECIPIENT_NAME' => $recipientName,
            'ECARD_RECIPIENT_EMAIL' => $recipientEmail,
            'ECARD_RECIPIENT_SALUTATION' => $recipientSalutation,
            'TXT_ECARD_LOOKS_LIKE' => $_ARRAYLANG['TXT_ECARD_LOOKS_LIKE'],
            'TXT_ECARD_EDIT' => $_ARRAYLANG['TXT_ECARD_EDIT'],
            'TXT_ECARD_SEND' => $_ARRAYLANG['TXT_ECARD_SEND'],
        ));
    }


    function send()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->setTemplate($this->pageContent);

        // Initialize variables
        $code = substr(md5(rand()), 1, 10);
        $url = \Cx\Core\Routing\Url::fromModuleAndCmd(
            'Ecard',
            'show',
            '',
            array(
                'code' => $code,
            )
        )->toString();

        // Initialize POST variables
        $id = intval($_POST['selectedEcard']);
        $message = contrexx_input2db($_POST['ecardMessage']);
        $recipientSalutation = contrexx_stripslashes($_POST['ecardRecipientSalutation']);
        $senderName = contrexx_stripslashes($_POST['ecardSenderName']);
        $senderEmail = \FWValidator::isEmail($_POST['ecardSenderEmail']) ? $_POST['ecardSenderEmail'] : '';
        $recipientName = contrexx_stripslashes($_POST['ecardRecipientName']);
        $recipientEmail = \FWValidator::isEmail($_POST['ecardRecipientEmail']) ? $_POST['ecardRecipientEmail'] : '';

        if (empty($senderEmail) || empty($recipientEmail)) {
            $this->_objTpl->setVariable(array(
                'STATUS_MESSAGE' => $_ARRAYLANG['TXT_ECARD_SENDING_ERROR']
            ));
            return false;
        }

        $query = "
            SELECT `setting_name`, `setting_value`
              FROM ".DBPREFIX."module_ecard_settings";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            switch ($objResult->fields['setting_name']) {
                case 'validdays':
                    $validdays = $objResult->fields['setting_value'];
                    break;
// Never used
//                case 'greetings':
//                    $greetings = $objResult->fields['setting_value'];
//                    break;
                case 'subject':
                    $subject = $objResult->fields['setting_value'];
                    break;
                case 'emailText':
                    $emailText = strip_tags($objResult->fields['setting_value']);
                    break;
            }
            $objResult->MoveNext();
        }
        $timeToLife = $validdays * 86400;
        // Replace placeholders with used in notification mail with user data
        $emailText = str_replace('[[ECARD_RECIPIENT_SALUTATION]]', $recipientSalutation, $emailText);
        $emailText = str_replace('[[ECARD_RECIPIENT_NAME]]', $recipientName, $emailText);
        $emailText = str_replace('[[ECARD_RECIPIENT_EMAIL]]', $recipientEmail, $emailText);
        $emailText = str_replace('[[ECARD_SENDER_NAME]]', $senderName, $emailText);
        $emailText = str_replace('[[ECARD_SENDER_EMAIL]]', $senderEmail, $emailText);
        $emailText = str_replace('[[ECARD_VALID_DAYS]]', $validdays, $emailText);
        $emailText = str_replace('[[ECARD_URL]]', $url, $emailText);
        $body = $emailText;

        // Insert ecard to DB
        $query = "
            INSERT INTO `".DBPREFIX."module_ecard_ecards` (
                code, date, TTL, salutation,
                senderName, senderEmail,
                recipientName, recipientEmail,
                message
            ) VALUES (
                '".$code."',
                '".time()."',
                '".$timeToLife."',
                '".addslashes($recipientSalutation)."',
                '".addslashes($senderName)."',
                '".$senderEmail."',
                '".addslashes($recipientName)."',
                '".$recipientEmail."',
                '".$message."');";
        if ($objDatabase->Execute($query)) {
            $query = "
                SELECT setting_value
                  FROM ".DBPREFIX."module_ecard_settings
                 WHERE setting_name='motive_$id'";
            $objResult = $objDatabase->SelectLimit($query, 1);

            // Copy motive to new file with $code as filename
            $fileExtension = preg_replace('/^.+(\.[^\.]+)$/', '$1', $objResult->fields['setting_value']);
            $fileName = $objResult->fields['setting_value'];

            $objFile = new \File();
            if ($objFile->copyFile(ASCMS_ECARD_OPTIMIZED_PATH.'/', $fileName, ASCMS_ECARD_SEND_ECARDS_PATH.'/', $code.$fileExtension)) {
                $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

                // Send notification mail to ecard-recipient
                $objMail->SetFrom($senderEmail, $senderName);
                $objMail->Subject = $subject;
                $objMail->IsHTML(false);
                $objMail->Body = $body;
                $objMail->AddAddress($recipientEmail);
                if ($objMail->Send()) {
                    $this->_objTpl->setVariable(array(
                        'STATUS_MESSAGE' => $_ARRAYLANG['TXT_ECARD_HAS_BEEN_SENT']
                    ));
                } else {
                    $this->_objTpl->setVariable(array(
                        'STATUS_MESSAGE' => $_ARRAYLANG['TXT_ECARD_MAIL_SENDING_ERROR']
                    ));
                }
            }
        } else {
            $this->_objTpl->setVariable(array(
                'STATUS_MESSAGE' => $_ARRAYLANG['TXT_ECARD_SENDING_ERROR']
            ));
        }
    }


    function showEcard()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent);

        // Initialize variables
        $code = contrexx_addslashes($_GET['code']);
        // Get data from DB
        $query = "
            SELECT *
              FROM ".DBPREFIX."module_ecard_ecards
             WHERE code='$code'";
        $objResult = $objDatabase->Execute($query);
        // If entered code does match a record in db
        if (!$objResult->EOF) {
            $message = nl2br(htmlentities($objResult->fields['message'], ENT_QUOTES, CONTREXX_CHARSET));
            $senderName = htmlentities($objResult->fields['senderName'], ENT_QUOTES, CONTREXX_CHARSET);
            $senderEmail = $objResult->fields['senderEmail'];
            $recipientName = htmlentities($objResult->fields['recipientName'], ENT_QUOTES, CONTREXX_CHARSET);
            $recipientEmail = $objResult->fields['recipientEmail'];
            $recipientsalutation = htmlentities($objResult->fields['salutation'], ENT_QUOTES, CONTREXX_CHARSET);
            // Get right file extension
            $globArray = glob(ASCMS_ECARD_SEND_ECARDS_PATH.'/'.$code.".*");
            $fileextension = substr($globArray[0], -4);
            $selectedMotive = $code.$fileextension;
            // Initialize DATA placeholder
            $this->_objTpl->setVariable(array(
                'ECARD_DATA' =>
                    '<strong>'.$senderName.'</strong> (<a href="mailto:'.$senderEmail.'">'.
                    $senderEmail.'</a>) '.$_ARRAYLANG['TXT_ECARD_HAS_SENT_YOU_AN_ECARD'],
                'ECARD_MOTIVE' =>
                    '<img src="'.ASCMS_ECARD_SEND_ECARDS_WEB_PATH.'/'.$selectedMotive.
                    '" alt="'.$selectedMotive.'" title="'.$selectedMotive.'" />',
                'ECARD_FROM' => $_ARRAYLANG['TXT_ECARD_FROM'].' '.$senderName,
                'ECARD_MESSAGE' => $message,
                'ECARD_SENDER_NAME' => $senderName,
                'ECARD_SENDER_EMAIL' => $senderEmail,
                'ECARD_RECIPIENT_SALUTATION' => $recipientsalutation,
                'ECARD_RECIPIENT_NAME' => $recipientName,
                'ECARD_RECIPIENT_EMAIL' => $recipientEmail,
            ));
        } else {
            // display error message
            $this->_objTpl->setVariable(array(
                'ECARD_MESSAGE' => $_ARRAYLANG['TXT_ECARD_WRONG_CODE'],
                'ECARD_FROM' => $_ARRAYLANG['TXT_ECARD_CAN_NOT_BE_DISPLAYED'],
            ));
        }
    }


    static function getJavascript($maxCharacters=100)
    {
        global $_ARRAYLANG;

        return '
<script type="text/javascript">
/* <![CDATA[ */
var maxChars = '.$maxCharacters.';
function checkAllFields() {
    value = document.getElementById("ecardMessage").value.substr(0, maxChars);
    //USED CHAR COUNTER
    currentChars = value.length;
    leftChars = (maxChars - currentChars);
    document.getElementById("charCounter").value = leftChars;
    document.getElementById("ecardMessage").value = value;
}

function checkInput() {
    var ecardCount = 0;
    var wrongFieldsArray = new Array();
    var fieldsArray = new Array("motiveFieldset", "fieldDescription_salutation", "ecardMessage", "ecardSenderName", "ecardRecipientName", "ecardSenderName", "ecardSenderEmail", "ecardRecipientEmail");

    for(var i = 0; i < document.getElementsByName("selectedEcard").length; i++) {
        if(document.getElementsByName("selectedEcard")[i].checked == true) {
            ecardCount++
        }
    }

    if (ecardCount == 0) {wrongFieldsArray.push("motiveFieldset");}
    if ((document.getElementsByName("ecardRecipientSalutation")[0].checked == false) && (document.getElementsByName("ecardRecipientSalutation")[1].checked == false)) {wrongFieldsArray.push("fieldDescription_salutation");}
    if(document.getElementsByName("ecardMessage")[0].value == "") {wrongFieldsArray.push("ecardMessage");}
    if(document.getElementsByName("ecardSenderName")[0].value == "") {wrongFieldsArray.push("ecardSenderName");}
    if(document.getElementsByName("ecardRecipientName")[0].value == "") {wrongFieldsArray.push("ecardRecipientName");}
    if(document.getElementsByName("ecardSenderName")[0].value == "") {wrongFieldsArray.push("ecardSenderName");}
    if(checkEmail(document.getElementsByName("ecardSenderEmail")[0].value) == false) {wrongFieldsArray.push("ecardSenderEmail");}
    if(checkEmail(document.getElementsByName("ecardRecipientEmail")[0].value) == false) {wrongFieldsArray.push("ecardRecipientEmail");}

    for (var i=0; i < fieldsArray.length; i++) {
        if (wrongFieldsArray.toString().indexOf(fieldsArray[i]) == -1) {
            if (fieldsArray[i] == "fieldDescription_salutation") {
                document.getElementById(fieldsArray[i]).style.color = "";
            } else {
                document.getElementById(fieldsArray[i]).style.border = "";
            }
        }
    }

    if (wrongFieldsArray.length == 0) {
        return true;
    } else {
        for (var i=0; i < wrongFieldsArray.length; i++) {
            if (wrongFieldsArray[i] == "fieldDescription_salutation") {
                document.getElementById(wrongFieldsArray[i]).style.color= "red";
            } else {
                document.getElementById(wrongFieldsArray[i]).style.border = "1px solid red";
            }
        }
        alert("'.$_ARRAYLANG['TXT_ECARD_FIELD_INPUT_INCORRECT'].'");
        return false;
    }
}

function checkEmail(s) {
    var a = false;
    var res = false;
    if(typeof(RegExp) == "function") {
        var b = new RegExp("abc");
        if(b.test("abc") == true){a = true;}
    }

    if(a == true) {
        reg = new RegExp("^([a-zA-Z0-9\\-\\.\\_]+)"+
                         "(\\@)([a-zA-Z0-9\\-\\.]+)"+
                         "(\\.)([a-zA-Z]{2,4})$");
        res = (reg.test(s));
    } else {
        res = (s.search("@") >= 1 &&
               s.lastIndexOf('.') > s.search("@") &&
               s.lastIndexOf('.') >= s.length-5)
    }
    return(res);
}

checkAllFields()
/* ]]> */
</script>';
    }

}
