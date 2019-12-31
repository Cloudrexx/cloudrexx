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
 * Guestbook frontend
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_guestbook
 */
class GuestBook extends GuestBookLibrary {

    var $langId;
    var $_objTpl;
    var $statusMessage;
    var $arrSettings = array();

    /**
     * Constructor
     * @param  string   $pageContent
     * @global integer
     * @access public
     */
    function __construct($pageContent) {
        global $_LANGID;

        $this->pageContent = $pageContent;
        $this->langId = $_LANGID;

        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        // get the guestbook settings
        $this->getSettings();
    }

    /**
     * Gets the page
     */
    function getPage() {
        if (!isset($_GET['cmd'])) {
            $_GET['cmd'] = '';
        }

        switch ($_GET['cmd']) {
            case 'post':
                $this->_newEntry();
                break;
            default:
                $this->_showList();
                break;
        }
        return $this->_objTpl->get();
    }

    /**
     * Gets the guestbook status
     *
     * @global  ADONewConnection
     * @global  array
     * @global  array
     * @access private
     */
    function _showList() {
        global $objDatabase, $_CONFIG, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        // initialize variables
        $i = 1;
        $paging = "";
        $pos = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;

        /** start paging * */
        $query = "    SELECT         id
                    FROM         " . DBPREFIX . "module_guestbook
                    WHERE         " . ($this->arrSettings['guestbook_only_lang_entries'] ? "lang_id='$this->langId' AND " : '') . "status = 1";
        $objResult = $objDatabase->Execute($query);
        $count = $objResult->RecordCount();
        $paging = getPaging($count, $pos, "&section=GuestBook", "<b>" . $_ARRAYLANG['TXT_GUESTBOOK_ENTRIES'] . "</b>", false);
        /** end paging * */
        $this->_objTpl->setVariable("GUESTBOOK_PAGING", $paging);
        $this->_objTpl->setVariable("GUESTBOOK_TOTAL_ENTRIES", $count);

        $query = "    SELECT         id,
                                forename,
                                name,
                                gender,
                                url,
                                email,
                                comment,
                                location,
                                datetime
                    FROM         " . DBPREFIX . "module_guestbook
                    WHERE         " . ($this->arrSettings['guestbook_only_lang_entries'] ? "lang_id='$this->langId' AND " : '') . "status = 1
                    ORDER BY     id DESC";
        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);

        while ($objResult !== false and !$objResult->EOF) {
            $class = ($i % 2) ? "row1" : "row2";
            $gender = ($objResult->fields["gender"] == "M") ? $_ARRAYLANG['TXT_MALE'] : $_ARRAYLANG['TXT_FEMALE']; // N/A

            if ($objResult->fields['url'] != "") {
                $this->_objTpl->setVariable('GUESTBOOK_URL', '<a href="' . $objResult->fields['url'] . '" target="_blank"><img alt="' . $objResult->fields['url'] . '" src=".'.ASCMS_MODULE_FOLDER.'/GuestBook/View/Media/www.gif" style="vertical-align:baseline" border="0" /></a>');
            }
            if ($objResult->fields['email'] != "") {
                if ($this->arrSettings['guestbook_replace_at']) {
                    $email = $this->changeMail($objResult->fields['email']);
                } else {
                    $email = $objResult->fields['email'];
                }

                $strMailTo = $this->createAsciiString('mailto:' . $email);
                $strMailAdress = $this->createAsciiString($email);

                $asciiStrGuestbookEmail = '<a href="' . $strMailTo . '"><img alt="' . $strMailAdress . '" src=".'.ASCMS_MODULE_FOLDER.'/GuestBook/View/Media/email.gif" style="vertical-align:baseline" border="0" /></a>';


                $this->_objTpl->setVariable('GUESTBOOK_EMAIL', $asciiStrGuestbookEmail);
            }

            $this->_objTpl->setVariable(array(
                'GUESTBOOK_ROWCLASS' => $class,
                'GUESTBOOK_FORENAME' => htmlentities($objResult->fields["forename"], ENT_QUOTES, CONTREXX_CHARSET),
                'GUESTBOOK_NAME' => htmlentities($objResult->fields["name"], ENT_QUOTES, CONTREXX_CHARSET),
                'GUESTBOOK_GENDER' => $gender,
                'GUESTBOOK_LOCATION' => htmlentities($objResult->fields["location"], ENT_QUOTES, CONTREXX_CHARSET),
                'GUESTBOOK_DATE' => date(ASCMS_DATE_FORMAT, strtotime($objResult->fields['datetime'])),
                'GUESTBOOK_COMMENT' => nl2br($objResult->fields["comment"]),
                'GUESTBOOK_ID' => $objResult->fields["id"],
            ));
            $this->_objTpl->parse('guestbook_row');
            $i++;
            $objResult->MoveNext();
        }
        $this->_objTpl->setVariable("GUESTBOOK_STATUS", $this->statusMessage);
    }

    /**
     * New entry
     *
     * Decides what to do, preview, safe or output the errors
     */
    function _newEntry() {
        if (isset($_POST['save'])) {
            if ($this->checkInput()) {
                $this->saveEntry();
            } else {
                $this->_showForm();
            }
        } else {
            $this->_showForm();
        }
    }

    /**
     * shows the submit form
     *
     * @access private
     */
    function _showForm() {
        global $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent);

        if (!empty($this->error)) {
            foreach ($this->error as $error) {
                $errors .= $error . "<br />";
            }
        }

        $checked = "checked=\"checked\"";

        if ($_POST['malefemale'] == "F") {
            $female_checked = $checked;
            $male_checked = "";
        } else {
            $female_checked = "";
            $male_checked = $checked;
        }

        $this->_objTpl->setVariable(array(
            "FORENAME" => htmlentities($_POST['forename'], ENT_QUOTES, CONTREXX_CHARSET),
            "NAME" => htmlentities($_POST['name'], ENT_QUOTES, CONTREXX_CHARSET),
            "COMMENT" => $_POST['comment'],
            "FEMALE_CHECKED" => $female_checked,
            "MALE_CHECKED" => $male_checked,
            "LOCATION" => htmlentities($_POST['location'], ENT_QUOTES, CONTREXX_CHARSET),
            "HOMEPAGE" => htmlentities($_POST['url'], ENT_QUOTES, CONTREXX_CHARSET),
            "EMAIL" => htmlentities($_POST['email'], ENT_QUOTES, CONTREXX_CHARSET)
        ));

        $this->_objTpl->setVariable(array(
            "ERROR" => $errors,
            "FEMALE_CHECKED" => $checked
        ));

        if ($this->_objTpl->blockExists('guestbook_captcha')) {
            if (\FWUser::getFWUserObject()->objUser->login()) {
                $this->_objTpl->hideBlock('guestbook_captcha');
            } else {
                $this->_objTpl->setVariable(array(
                    'TXT_GUESTBOOK_CAPTCHA' => $_CORELANG['TXT_CORE_CAPTCHA'],
                    'GUESTBOOK_CAPTCHA_CODE' => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
                ));
                $this->_objTpl->parse('guestbook_captcha');
            }
        }

        $this->_objTpl->hideBlock('guestbookStatus');
        $this->_objTpl->parse('guestbookForm');
    }

    /**
     * Saves an entry
     */
    function saveEntry() {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent);

// TODO: Never used
//        $objValidator = new FWValidator();

        $name = $_POST['name'];
        $forename = $_POST['forename'];
        $gender = $_POST['malefemale'];
        $mail = isset($_POST['email']) ? $_POST['email'] : '';
        $comment = $this->addHyperlinking($_POST['comment']);
        $location = $_POST['location'];

        if (strlen($_POST['url']) > 7) {
            $url = $_POST['url'];

            if (!preg_match("%^https?://%", $url)) {
                $url = "http://" . $url;
            }
        }

        $status = $this->arrSettings['guestbook_activate_submitted_entries'];

        $query = "INSERT INTO " . DBPREFIX . "module_guestbook
                        (status,
                         forename,
                         name,
                         gender,
                         url,
                         datetime,
                         email,
                         comment,
                         location,
                         lang_id)
                 VALUES ($status,
                        '" . addslashes($name) . "',
                        '" . addslashes($forename) . "',
                        '" . addslashes($gender) . "',
                        '" . addslashes($url) . "',
                        '".date('Y-m-d H:i:s')."',
                        '" . addslashes($mail) . "',
                        '" . addslashes($comment) . "',
                        '" . addslashes($location) . "',
                        " . $this->langId . ")";
        $objDatabase->Execute($query);

        if ($this->arrSettings['guestbook_send_notification_email'] == 1) {
            $this->sendNotificationEmail($forename, $name, $comment);
        }
        $this->statusMessage = $_ARRAYLANG['TXT_GUESTBOOK_RECORD_STORED_SUCCESSFUL'] . "<br />";
        if ($this->arrSettings['guestbook_activate_submitted_entries'] == 0) {
            $this->statusMessage .= '<b>' . $_ARRAYLANG['TXT_GUESTBOOK_RECORD_STORED_ACTIVATE'] . '</b>';
        }

        $this->_objTpl->setVariable(array(
            "GUESTBOOK_STATUS" => $this->statusMessage
        ));

        $this->_objTpl->parse('guestbookStatus');
        $this->_objTpl->hideBlock('guestbookForm');
    }

    /**
     * checks input
     */
    function checkInput() {
        global $_ARRAYLANG;

        $objValidator = new \FWValidator();
        $captchaCheck = true;

        $_POST['forename'] = strip_tags(contrexx_stripslashes($_POST['forename']));
        $_POST['name'] = strip_tags(contrexx_stripslashes($_POST['name']));
        $_POST['comment'] = htmlentities(strip_tags(contrexx_stripslashes($_POST['comment'])), ENT_QUOTES, CONTREXX_CHARSET);
        $_POST['location'] = strip_tags(contrexx_stripslashes($_POST['location']));
        $_POST['email'] = strip_tags(contrexx_stripslashes($_POST['email']));
        $_POST['url'] = strip_tags(contrexx_stripslashes($_POST['url']));

        if (!\FWUser::getFWUserObject()->objUser->login() && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
            $captchaCheck = false;
        }

        if (empty($_POST['name']) || empty($_POST['forename'])) {
            $this->makeError($_ARRAYLANG['TXT_NAME']);
        }

        if (empty($_POST['comment'])) {
            $this->makeError($_ARRAYLANG['TXT_COMMENT']);
        }

        if (empty($_POST['malefemale'])) {
            $this->makeError($_ARRAYLANG['TXT_SEX']);
        }

        if (empty($_POST['location'])) {
            $this->makeError($_ARRAYLANG['TXT_LOCATION']);
        }

        if (!$objValidator->isEmail($_POST['email'])) {
            $this->makeError($_ARRAYLANG['TXT_EMAIL']);
        }

        if (empty($this->error) && $captchaCheck) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Makes an error
     */
    function makeError($term) {
        global $_ARRAYLANG;

        $this->error[] = $term . " " . $_ARRAYLANG['TXT_IS_INVALID'];
    }

    /**
     * @return void
     * @desc Sends a notification email to the administrator
     */
    function sendNotificationEmail($forename, $name, $comment) {
        global $_ARRAYLANG, $_CONFIG;

        $message = $_ARRAYLANG['TXT_CHECK_GUESTBOOK_ENTRY'] . "\n\n";
        $message .= $_ARRAYLANG['TXT_ENTRY_READS'] . "\n" . $forename . " " . $name . "\n" . html_entity_decode($comment, ENT_QUOTES, CONTREXX_CHARSET);
        $mailto = $_CONFIG['coreAdminEmail'];
        $subject = $_ARRAYLANG['TXT_NEW_GUESTBOOK_ENTRY'] . " " . $_CONFIG['domainUrl'];

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $from = $mailto;
        $objMail->SetFrom($from);
        $objMail->Subject = $subject;
        $objMail->IsHTML(false);
        $objMail->Body = $message;
        $objMail->AddAddress($mailto);
        if ($objMail->Send()) {
            return true;
        }

        return false;
    }

}

?>
