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
 * Newsletter
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_newsletter
 * @todo        Edit PHP DocBlocks!
 * @todo        make total mailrecipient count static in newsletter list (act=mails)
 *              (new count field)
 *              check if mail already sent when a user unsubscribes -> adjust count
 */

namespace Cx\Modules\Newsletter\Controller;

/**
 * Class newsletter
 *
 * Newsletter module class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_newsletter
 */
class NewsletterManager extends NewsletterLib
{
    public $_objTpl;
    public $_pageTitle;
    public static $strErrMessage = '';
    public static $strOkMessage = '';
    public $months = array();
    public $_arrMailPriority = array(
        1 => 'TXT_NEWSLETTER_VERY_HIGH',
        2 => 'TXT_NEWSLETTER_HIGH',
        3 => 'TXT_NEWSLETTER_MEDIUM',
        4 => 'TXT_NEWSLETTER_LOW',
        5 => 'TXT_NEWSLETTER_VERY_LOW'
    );
    public $_stdMailPriority = 3;
    public $_attachmentPath = '/images/attach/';

    private $act = '';

    /**
     * PHP5 constructor
     * @global \Cx\Core\Html\Sigma
     * @global array $_ARRAYLANG
     */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Newsletter/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->act = isset($_GET['act']) ? $_GET['act'] : '';

        if (!isset($_REQUEST['standalone'])) {
            $objTemplate->setVariable(
                "CONTENT_NAVIGATION",
                "<a href='index.php?cmd=Newsletter&amp;act=mails' class='".(($this->act == '' || $this->act == 'mails') ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEWSLETTER_EMAIL_CAMPAIGNS']."</a>"
                .(\Permission::checkAccess(172, 'static', true) ? "<a href='index.php?cmd=Newsletter&amp;act=lists' class='".($this->act == 'lists' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEWSLETTER_LISTS']."</a>" : '')
                .(\Permission::checkAccess(174, 'static', true) ? "<a href='index.php?cmd=Newsletter&amp;act=users' class='".($this->act == 'users' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEWSLETTER_RECIPIENTS']."</a>" : '')
                .(\Permission::checkAccess(175, 'static', true) ? "<a href='index.php?cmd=Newsletter&amp;act=news' class='".($this->act == 'news' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEWSLETTER_NEWS']."</a>" : '')
                .(\Permission::checkAccess(176, 'static', true) ? "<a href='index.php?cmd=Newsletter&amp;act=Settings' class='".($this->act == 'Settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_SETTINGS']."</a>" : ''));
        }
        $months = explode(',', $_ARRAYLANG['TXT_NEWSLETTER_MONTHS_ARRAY']);
        $i = 0;
        foreach ($months as $month) {
            $this->months[++$i] = $month;
        }
    }


    /**
     * Set the backend page
     * @access public
     * @global \Cx\Core\Html\Sigma
     * @global array $_ARRAYLANG
     */
    function getPage()
    {
        global $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act'] = '';
        }

        switch ($_GET['act']) {
            case "lists":
                \Permission::checkAccess(172, 'static');
                $this->_lists();
                break;
            case "editlist":
                \Permission::checkAccess(172, 'static');
                $this->_editList();
                break;
            case "flushList":
                \Permission::checkAccess(172, 'static');
                $this->_flushList();
                $this->_lists();
                break;
            case "changeListStatus":
                \Permission::checkAccess(172, 'static');
                $this->_changeListStatus();
                $this->_lists();
                break;
            case "deleteList":
                \Permission::checkAccess(172, 'static');
                $this->_deleteList();
                $this->_lists();
                break;
            case "gethtml":
                \Permission::checkAccess(172, 'static');
                $this->_getListHTML();
                break;
            case "mails":
                \Permission::checkAccess(171, 'static');
                $this->_mails();
                break;
            case "deleteMail":
                \Permission::checkAccess(171, 'static');
                $this->_deleteMail();
                break;
            case "copyMail":
                \Permission::checkAccess(171, 'static');
                $this->_copyMail();
                break;
            case "editMail":
                \Permission::checkAccess(171, 'static');
                $this->_editMail();
                break;
            case "sendMail":
                \Permission::checkAccess(171, 'static');
                $this->_sendMailPage();
                break;
            case "send":
                \Permission::checkAccess(171, 'static');
                $this->_sendMail();
                break;
            case "news":
                \Permission::checkAccess(175, 'static');
                $this->_getNewsPage();
                break;
            case "newspreview":
                \Permission::checkAccess(175, 'static');
                $this->_getNewsPreviewPage();
                break;
            case "users":
                \Permission::checkAccess(174, 'static');
                $this->_users();
                break;
            case "editusersort":
                \Permission::checkAccess(174, 'static');
                $this->edituserSort();
                break;
            case "update":
                \Permission::checkAccess(171, 'static');
                $this->_update();
                break;
            case "deleteInactive":
                \Permission::checkAccess(174, 'static');
                $this->_deleteInactiveRecipients();
                $this->_users();
                break;
            case "feedback":
                \Permission::checkAccess(171, 'static');
                if (isset($_GET['id'])) {
                    $this->_showEmailFeedbackAnalysis();
                    break;
                } elseif (isset($_GET['link_id'])) {
// TODO: refactor and reactivate these extended statistics
                    /*if (isset($_GET['recipient_id']) && isset($_GET['recipient_type'])) {
                        $this->_showRecipientEmailFeedbackAnalysis();
                    } else {
                        $this->_showLinkFeedbackAnalysis();
                    }*/
                    break;
                }
            case 'mailtemplate_overview':
            case 'mailtemplate_edit':
                $_GET['tpl'] = 'EmailTemplates';
            case 'dispatch': // fallback for older implementation
            case 'Settings':
                \Permission::checkAccess(176, 'static');
                $this->showSettings();
                break;
            default:
                \Permission::checkAccess(152, 'static');
                $this->_mails();
                //$this->overview();
                break;
        }

        if (!isset($_REQUEST['standalone'])) {
            $objTemplate->setVariable(array(
                'CONTENT_TITLE' => $this->_pageTitle,
                'CONTENT_OK_MESSAGE' => self::$strOkMessage,
                'CONTENT_STATUS_MESSAGE' => self::$strErrMessage,
                'ADMIN_CONTENT' => $this->_objTpl->get(),
            ));
        } else {
            $this->_objTpl->show();
            exit;
        }
    }

    /**
     * Parse the newsletter settings section
     */
    public function showSettings()
    {
        global $_ARRAYLANG;

        $this->_pageTitle = $_ARRAYLANG['TXT_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_newsletter_settings.html');

        $tpl = isset($_GET['tpl']) ? contrexx_input2raw($_GET['tpl']) : '';
        switch ($tpl) {
            case 'EmailTemplates':
                $this->emailTemplates();
                break;
            case 'Confightml':
                $this->ConfigHTML();
                break;
            case 'Interface':
                $this->interfaceSettings();
                break;
            case 'Tpledit':
                $tpl = 'Templates';
                $this->_editTemplate();
                break;
            case 'Tpldel':
                $tpl = 'Templates';
                $this->delTemplate();
            case 'Templates':
                $this->_templates();
                break;
            case 'Dispatch':
            default :
                $tpl = 'Dispatch';
                $this->ConfigDispatch();
                break;
        }

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_GENERAL'           => $_ARRAYLANG['TXT_NEWSLETTER_GENERAL'],
            'TXT_GENERATE_HTML'              => $_ARRAYLANG['TXT_GENERATE_HTML'],
            'TXT_NEWSLETTER_TEMPLATES'       => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES'],
            'TXT_NEWSLETTER_INTERFACE'       => $_ARRAYLANG['TXT_NEWSLETTER_INTERFACE'],
            'TXT_NEWSLETTER_EMAIL_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES'],
            'NEWSLETTER_'. strtoupper($tpl) ."_ACTIVE" => 'active',
        ));
    }

    /**
     * Takes a date in the format dd.mm.yyyy hh:mm and returns it's representation as mktime()-timestamp.
     *
     * @param $value string
     * @return long timestamp
     */
    function dateFromInput($value) {
        if($value === null || $value === '') //not set POST-param passed, return null for the other functions to know this
            return null;
        $arrDate = array();
        if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})\s*([0-9]{1,2})\:([0-9]{1,2})/', $value, $arrDate)) {
            return mktime(intval($arrDate[4]), intval($arrDate[5]), 0, intval($arrDate[2]), intval($arrDate[1]), intval($arrDate[3]));
        } else {
            return time();
        }
    }

    /**
     * Takes a mktime()-timestamp and formats it as dd.mm.yyyy hh:mm
     *
     * @param $value long timestamp
     * @return string
     */
    function valueFromDate($value = 0, $format = 'd.m.Y H:i:s') {
        if($value === null //user provided no POST
            || $value === '0') //empty date field
            return ''; //make an empty date
        if($value)
            return date($format,$value);
        else
            return date($format);
    }

    /**
     * Takes a mktime()-timestamp and formats it as yyyy-mm-dd hh:mm:00 for insertion in db.
     *
     * @param $value long timestamp
     * @return string
     */
    function dbFromDate($value) {
        if($value !== null) {
            return date('"Y-m-d H:i:00"', $value);
        }
        else {
            return 'DEFAULT';
        }
    }

    /**
     * Display the list administration page
     * @access private
     * @global array $_ARRAYLANG
     * @global ADONewConnection
     */
    function _lists()
    {
        global $_ARRAYLANG, $objDatabase;

        $this->_objTpl->loadTemplateFile('module_newsletter_lists.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_LISTS'];
        $rowNr = 0;

        if (isset($_GET['tpl']) && ($_GET['tpl'] == 'consentMail')) {
            $categoryId = isset($_GET['id']) ? contrexx_input2int($_GET['id']) : 0;
            if ($this->sendConsentConfirmationMail(array($categoryId))) {
                static::$strOkMessage = $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_SUCCESS'];
            } else {
                static::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_CANCELED_BY_EMAIL'] . static::$strErrMessage;
            }
        }

        if (isset($_GET["bulkdelete"])) {
            $error=0;
            if (!empty($_POST['listid'])) {
                foreach ($_POST['listid'] as $listid) {
                    $listid=intval($listid);
                    if (    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_category WHERE id=$listid") !== false) {
                        $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_rel_cat_news WHERE category=$listid");
                        $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE category=$listid");
                        $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_access_user WHERE newsletterCategoryID=$listid");
                    } else {
                        $error=1;
                    }
                }
                if ($error) {
                    self::$strErrMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETE_ERROR'];
                } else {
                    self::$strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                }
            }
        }

        $arrLists = self::getLists(false, true);

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_CONFIRM_DELETE_LIST' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_DELETE_LIST'],
            'TXT_NEWSLETTER_CANNOT_UNDO_OPERATION' => $_ARRAYLANG['TXT_NEWSLETTER_CANNOT_UNDO_OPERATION'],
            'TXT_NEWSLETTER_LISTS' => $_ARRAYLANG['TXT_NEWSLETTER_LISTS'],
            'TXT_NEWSLETTER_ID_UC' => $_ARRAYLANG['TXT_NEWSLETTER_ID_UC'],
            'TXT_NEWSLETTER_STATUS' => $_ARRAYLANG['TXT_NEWSLETTER_STATUS'],
            'TXT_NEWSLETTER_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NAME'],
            'TXT_NEWSLETTER_LAST_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_LAST_EMAIL'],
            'TXT_NEWSLETTER_RECIPIENTS' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENTS'],
            'TXT_NEWSLETTER_FUNCTIONS' => $_ARRAYLANG['TXT_NEWSLETTER_FUNCTIONS'],
            'TXT_NEWSLETTER_CONFIRM_CHANGE_LIST_STATUS' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_CHANGE_LIST_STATUS'],
            'TXT_NEWSLETTER_ADD_NEW_LIST' => $_ARRAYLANG['TXT_NEWSLETTER_ADD_NEW_LIST'],
            'TXT_EXPORT' => $_ARRAYLANG['TXT_NEWSLETTER_EXPORT'],
            'TXT_NEWSLETTER_CHECK_ALL' => $_ARRAYLANG['TXT_NEWSLETTER_CHECK_ALL'],
            'TXT_NEWSLETTER_UNCHECK_ALL' => $_ARRAYLANG['TXT_NEWSLETTER_UNCHECK_ALL'],
            'TXT_NEWSLETTER_WITH_SELECTED' => $_ARRAYLANG['TXT_NEWSLETTER_WITH_SELECTED'],
            'TXT_NEWSLETTER_DELETE' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE'],
            'TXT_NEWSLETTER_FLUSH' => $_ARRAYLANG['TXT_NEWSLETTER_FLUSH'],
            'TXT_CONFIRM_DELETE_DATA' => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_NEWSLETTER_CONFIRM_FLUSH_LIST' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_FLUSH_LIST'],
            'TXT_NEWSLETTER_EXPORT_ALL_LISTS' => $_ARRAYLANG['TXT_NEWSLETTER_EXPORT_ALL_LISTS'],
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_MODIFY' => $_ARRAYLANG['TXT_NEWSLETTER_MODIFY'],
            'TXT_NEWSLETTER_DELETE' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE'],
            'TXT_NEWSLETTER_GENERATE_HTML_SOURCE_CODE' => $_ARRAYLANG['TXT_NEWSLETTER_GENERATE_HTML_SOURCE_CODE'],
            'TXT_NEWSLETTER_SHOW_LAST_SENT_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_SHOW_LAST_SENT_EMAIL'],
            'TXT_NEWSLETTER_CREATE_NEW_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_CREATE_NEW_EMAIL'],
            'TXT_NEWSLETTER_NOTIFY_ON_UNSUBSCRIBE' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFY_ON_UNSUBSCRIBE'],
            'TXT_NEWSLETTER_CONSENT_MAIL_SEND' => $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_MAIL_SEND'],
        ));

        if (!empty($arrLists)) {
            foreach ($arrLists as $id => $arrList) {
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_LIST_ID' => $id,
                    'NEWSLETTER_ROW_CLASS' => $rowNr % 2 == 1 ? "row1" : "row2",
                    'NEWSLETTER_LIST_STATUS_IMG' => $arrList['status'] == 1 ? "folder_on.gif" : "folder_off.gif",
                    'NEWSLETTER_LIST_NAME' => contrexx_raw2xhtml($arrList['name']),
                    'NEWSLETTER_LAST_MAIL_ID' => $arrList['mail_id'],
                    'NEWSLETTER_LIST_RECIPIENT' => $arrList['recipients'] > 0 ? '<a href="index.php?cmd=Newsletter&amp;act=users&amp;newsletterListId='.$id.'" title="'.
                                                            sprintf($_ARRAYLANG['TXT_NEWSLETTER_SHOW_RECIPIENTS_OF_LIST'], contrexx_raw2xhtml($arrList['name'])).'">'.$arrList['recipients'].'</a>' : '-',
                    'NEWSLETTER_LIST_STATUS_MSG' => $arrList['status'] == 1 ? $_ARRAYLANG['TXT_NEWSLETTER_VISIBLE_STATUS_TXT'] : $_ARRAYLANG['TXT_NEWSLETTER_INVISIBLE_STATUS_TXT'],
                    'NEWSLETTER_NOTIFICATION_EMAIL' => trim($arrList['notification_email']) == '' ? '-' : contrexx_raw2xhtml($arrList['notification_email']),
                ));

                if ($arrList['mail_sent'] > 0) {
                    $this->_objTpl->setVariable('NEWSLETTER_LIST_LAST_MAIL', date(ASCMS_DATE_FORMAT_DATE, $arrList['mail_sent'])." (".contrexx_raw2xhtml($arrList['mail_name']).")");
                    $this->_objTpl->touchBlock('newsletter_list_last_mail');
                    $this->_objTpl->hideBlock('newsletter_list_no_last_mail');
                } else {
                    $this->_objTpl->hideBlock('newsletter_list_last_mail');
                    $this->_objTpl->touchBlock('newsletter_list_no_last_mail');
                }

                $this->_objTpl->parse('newsletter_lists');
                $rowNr++;
            }
        } else {
            $this->_objTpl->hideBlock('newsletter_lists');
            $this->_objTpl->setVariable('TXT_NEWSLETTER_NO_LISTS', $_ARRAYLANG['TXT_NEWSLETTER_NO_LISTS']);
            $this->_objTpl->parse('newsletter_no_lists');
        }
    }


    function _flushList()
    {
        global $objDatabase, $_ARRAYLANG;
        $listID = (!empty($_GET['id'])) ? intval($_GET['id']) : false;
        if ($listID) {
            if ($objDatabase->Execute(
                        "DELETE FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE category = $listID"
                    ) !== false &&
                $objDatabase->Execute(
                        "DELETE FROM ".DBPREFIX."module_newsletter_access_user WHERE newsletterCategoryID=$listID"
                    ) !== false) {
                self::$strOkMessage = $_ARRAYLANG['TXT_NEWSLETTER_SUCCESSFULLY_FLUSHED'];
            } else {
                self::$strErrMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETE_ERROR'];
            }
        } else {
            self::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_NO_ID_SPECIFIED'];
        }
    }


    function _deleteList()
    {
        global $objDatabase, $_ARRAYLANG;
        $listId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($listId > 0) {
            if (($arrList = $this->_getList($listId)) !== false) {
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_rel_cat_news WHERE category=".$listId);
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE category=".$listId);
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_access_user WHERE newsletterCategoryID=$listId");

                if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_category WHERE id=".$listId) !== false) {
                    self::$strOkMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_LIST_SUCCESSFULLY_DELETED'], $arrList['name']);
                } else {
                    self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_COULD_NOT_DELETE_LIST'], $arrList['name']);
                }
            }
        }
    }


    function _getList($listId)
    {
        global $objDatabase;

        $objList = $objDatabase->SelectLimit("SELECT `status`, `name`, `notification_email` FROM ".DBPREFIX."module_newsletter_category WHERE id=".$listId, 1);
        if ($objList !== false && $objList->RecordCount() == 1) {
            return array(
                'status' => $objList->fields['status'],
                'name' => $objList->fields['name'],
                'notification_email' => $objList->fields['notification_email'],
            );
        }
        return false;
    }


    function _changeListStatus()
    {
        global $objDatabase;

        $listId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($listId > 0) {
            if (($arrList = $this->_getList($listId)) !== false) {
                $objDatabase->Execute("UPDATE ".DBPREFIX."module_newsletter_category SET `status`=".($arrList['status'] == 1 ? "0" : "1")." WHERE id=".$listId);
            }
        }
    }


    function _editList()
    {
        global $_ARRAYLANG;

        $listId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        if (isset($_POST['save'])) {
            $listName = isset($_POST['newsletter_list_name']) ? contrexx_addslashes($_POST['newsletter_list_name']) : '';
            $listStatus = (isset($_POST['newsletter_list_status']) && intval($_POST['newsletter_list_status']) == '1') ? intval($_POST['newsletter_list_status']) : 0;
            $notificationMail = isset($_POST['newsletter_notification_mail']) ? contrexx_addslashes($_POST['newsletter_notification_mail']) : '';
            if (!empty($listName)) {
                if ($this->_checkUniqueListName($listId, $listName) !== false) {
                    if ($listId == 0) {
                        if ($this->_addList($listName, $listStatus, $notificationMail) !== false) {
                            self::$strOkMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_LIST_SUCCESSFULLY_CREATED'], $listName);
                            return $this->_lists();
                        } else {
                            self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_COULD_NOT_CREATE_LIST'], $listName);
                        }
                    } else {
                        if ($this->_updateList($listId, $listName, $listStatus, $notificationMail) !== false) {
                            self::$strOkMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_LIST_SUCCESSFULLY_UPDATED'], $listName);
                            return $this->_lists();
                        } else {
                            self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_COULD_NOT_UPDATE_LIST'], $listName);
                        }
                    }
                } else {
                    self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_DUPLICATE_LIST_NAME_MSG'];
                }
            } else {
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_DEFINE_LIST_NAME_MSG'];
            }
        } elseif ($listId > 0 && ($arrList = $this->_getList($listId)) !== false) {
            $listName = $arrList['name'];
            $listStatus = $arrList['status'];
            $notificationMail = $arrList['notification_email'];
        } else {
            $listName = isset($_POST['newsletter_list_name']) ? contrexx_addslashes($_POST['newsletter_list_name']) : '';
            $listStatus = (isset($_POST['newsletter_list_status']) && intval($_POST['newsletter_list_status']) == '1') ? intval($_POST['newsletter_list_status']) : 0;
            $notificationMail = isset($_POST['newsletter_notification_mail']) ? contrexx_addslashes($_POST['newsletter_notification_mail']) : '';
        }

        $this->_objTpl->loadTemplateFile('module_newsletter_list_edit.html');
        $this->_pageTitle = $listId > 0 ? $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_LIST'] : $_ARRAYLANG['TXT_NEWSLETTER_ADD_NEW_LIST'];

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NAME'],
            'TXT_NEWSLETTER_STATUS' => $_ARRAYLANG['TXT_NEWSLETTER_STATUS'],
            'TXT_NEWSLETTER_VISIBLE' => $_ARRAYLANG['TXT_NEWSLETTER_VISIBLE'],
            'TXT_NEWSLETTER_NOTIFICATION_SEND_BY_UNSUBSCRIBE' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_SEND_BY_UNSUBSCRIBE'],
            'TXT_NEWSLETTER_SEPARATE_MULTIPLE_VALUES_BY_COMMA' => $_ARRAYLANG['TXT_CORE_MAILTEMPLATE_NOTE_TO'],
            'TXT_ACTIVE' => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_NEWSLETTER_BACK' => $_ARRAYLANG['TXT_NEWSLETTER_BACK'],
            'TXT_NEWSLETTER_SAVE' => $_ARRAYLANG['TXT_NEWSLETTER_SAVE'],
            'TXT_NEWSLETTER_NOTIFY_ON_UNSUBSCRIBE' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFY_ON_UNSUBSCRIBE'],
        ));

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_LIST_TITLE' => $listId > 0 ? $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_LIST'] : $_ARRAYLANG['TXT_NEWSLETTER_ADD_NEW_LIST'],
            'NEWSLETTER_LIST_ID' => $listId,
            'NEWSLETTER_LIST_NAME' => htmlentities($listName, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_LIST_STATUS' => $listStatus == 1 ? 'checked="checked"' : '',
            'NEWSLETTER_NOTIFICATION_MAIL' => $notificationMail,
        ));
        return true;
    }


    function _updateList($listId, $listName, $listStatus, $notificationMail)
    {
        global $objDatabase;

        if ($objDatabase->Execute("
            UPDATE ".DBPREFIX."module_newsletter_category
               SET `name`='$listName',
                   `status`=$listStatus,
                   `notification_email`='$notificationMail'
             WHERE id=".intval($listId))) {
            return true;
        }
        return false;
    }


/**
 * Moved to NewsletterLib.class.php
 */
//function _addList($listName, $listStatus)


    /**
     * Add/Edit the mail template
     *
     * @param boolean $copy If true, copy the newsletter mail when mailid is not empty
     *                      If false, Add/Edit the newsletter mail
     * @return null
     */
    public function _editMail($copy = false)
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $mailId = isset($_REQUEST['id']) ? contrexx_input2int($_REQUEST['id']) : 0;
        $arrAttachment = array();
        $attachmentNr = 0;
        $arrAssociatedLists = array();
        $crmMembershipFilter = array(
            'associate' => array(),
            'include' => array(),
            'exclude' => array()
        );
        $arrAssociatedGroups = array();
        $status = true;

        $mailSubject = isset($_POST['newsletter_mail_subject']) ? contrexx_stripslashes($_POST['newsletter_mail_subject']) : '';

        $objMailSentDate = $objDatabase->Execute("SELECT `date_sent` FROM ".DBPREFIX."module_newsletter WHERE id=".$mailId);
        $mailSendDate    = ($objMailSentDate) ? $objMailSentDate->fields['date_sent'] : 0;

        $mailTemplate =   isset($_POST['newsletter_mail_template'])
                        ? contrexx_input2int($_POST['newsletter_mail_template'])
                        : key($this->_getTemplates());
        if (!empty($_POST['newsletter_import_template'])) {
            $importTemplate = contrexx_input2int($_POST['newsletter_import_template']);
        }

        if (!empty($_POST['newsletter_mail_html_content'])) {
            $mailHtmlContent = $this->_getBodyContent(contrexx_input2raw($_POST['newsletter_mail_html_content']));
        } elseif (isset($_POST['selected'])) {
            $selectedCategoryNews = json_decode(contrexx_input2raw($_POST['selected']), true);
            $selectedNews   = array();
            foreach ($selectedCategoryNews as $news) {
                foreach ($news as $newsId) {
                    if (in_array($newsId, $selectedNews)) {
                        continue;
                    }
                    $selectedNews[] = $newsId;
                }
            }
            $HTML_TemplateSource_Import = $this->_getBodyContent($this->_prepareNewsPreview($this->GetTemplateSource($importTemplate, 'html')));
            $_REQUEST['standalone'] = true;

            $query = '  SELECT  n.id                AS newsid,
                                n.userid            AS newsuid,
                                n.date              AS newsdate,
                                n.teaser_image_path,
                                n.teaser_image_thumbnail_path,
                                n.redirect,
                                n.publisher,
                                n.publisher_id,
                                n.author,
                                n.author_id,
                                nc.category_id      AS categoryId,
                                nl.title            AS newstitle,
                                nl.text             AS newscontent,
                                nl.teaser_text,
                                nc.name             AS name
                    FROM        '.DBPREFIX.'module_news AS n
                    INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    INNER JOIN  '.DBPREFIX.'module_news_rel_categories AS nr ON nr.news_id = n.id
                    INNER JOIN  '.DBPREFIX.'module_news_categories_locale AS nc ON nc.category_id = nr.category_id
                    WHERE       status = 1
                                AND nl.is_active=1
                                AND nl.lang_id='.FRONTEND_LANG_ID.'
                                AND nc.lang_id='.FRONTEND_LANG_ID.'
                                AND n.id IN ('.implode(",", contrexx_input2int($selectedNews)).')
                    ORDER BY nc.name ASC, n.date DESC';

            $objNews = $objDatabase->Execute($query);
            $currentCategory = 0;
            if ($objNews !== false) {
                $objNewsLib = new \Cx\Core_Modules\News\Controller\NewsLibrary();
                $objNewsTpl = new \Cx\Core\Html\Sigma();
                \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objNewsTpl);
                $objNewsTpl->setTemplate($HTML_TemplateSource_Import);
                $isNewsListExists = $objNewsTpl->blockExists('news_list');
                $newsHtmlContent  = '';
                while (!$objNews->EOF) {
                    $categoryId = $objNews->fields['categoryId'];
                    if (    !array_key_exists($categoryId, $selectedCategoryNews)
                        || !in_array($objNews->fields['newsid'], $selectedCategoryNews[$categoryId])
                    ) {
                        $objNews->MoveNext();
                        continue;
                    }
                    if ($isNewsListExists) {
                        $this->parseNewsDetails($objNewsTpl, $objNewsLib, $objNews, $currentCategory);
                    } else {
                        $content = $this->getNewsMailContent($importTemplate, $objNewsLib, $objNews);
                        if ($newsHtmlContent != '') {
                            $newsHtmlContent .= "<br/>" . $content;
                        } else {
                            $newsHtmlContent = $content;
                        }
                    }
                    $objNews->MoveNext();
                }
                $mailHtmlContent = ($isNewsListExists) ? $objNewsTpl->get() : $newsHtmlContent;
            }
            unset($_REQUEST['standalone']);
        } else {
            $mailHtmlContent = '';
        }

        if (isset($_POST['newsletter_mail_attachment']) && is_array($_POST['newsletter_mail_attachment'])) {
            foreach ($_POST['newsletter_mail_attachment'] as $attachment) {
                array_push($arrAttachment, contrexx_addslashes($attachment));
            }
        }

        if (isset($_POST['newsletter_mail_priority'])) {
            $mailPriority = contrexx_input2int($_POST['newsletter_mail_priority']);
            if ($mailPriority < 1 || $mailPriority > 5) {
                $mailPriority = $this->_stdMailPriority;
            }
        } else {
            $mailPriority = $this->_stdMailPriority;
        }

        if (isset($_POST['newsletter_mail_associated_list'])) {
            foreach ($_POST['newsletter_mail_associated_list'] as $listId => $status) {
                if (contrexx_input2int($status) == 1) {
                    array_push($arrAssociatedLists, contrexx_input2int($listId));
                }
            }
        }

        // get the Crm membership association
        if (isset($_POST['newsletter_mail_crm_memberships'])) {
            if (isset($_POST['newsletter_mail_crm_memberships']['associate'])) {
                foreach ($_POST['newsletter_mail_crm_memberships']['associate'] as $crmMembershipId) {
                    $crmMembershipFilter['associate'][] = intval($crmMembershipId);
                }
            }
        }
        // get the Crm membership filter
        if (isset($_POST['newsletter_mail_crm_filter_memberships'])) {
            if (isset($_POST['newsletter_mail_crm_filter_memberships']['include'])) {
                foreach ($_POST['newsletter_mail_crm_filter_memberships']['include'] as $crmMembershipId) {
                    $crmMembershipFilter['include'][] = intval($crmMembershipId);
                }
            }
            if (isset($_POST['newsletter_mail_crm_filter_memberships']['exclude'])) {
                foreach ($_POST['newsletter_mail_crm_filter_memberships']['exclude'] as $crmMembershipId) {
                    if (in_array(intval($crmMembershipId), $crmMembershipFilter['include'])) {
                        continue;
                    }
                    $crmMembershipFilter['exclude'][] = intval($crmMembershipId);
                }
            }
        }

        // get the associated groups from the post variables in case the form was already sent
        if (isset($_POST['newsletter_mail_associated_group'])) {
            foreach ($_POST['newsletter_mail_associated_group']
                        as $groupID => $status) {
                if ($status) {
                    $arrAssociatedGroups[] = contrexx_input2int($groupID);
                }
            }
        }

        $arrSettings = $this->_getSettings();
        $mailSenderMail = isset($_POST['newsletter_mail_sender_mail']) ? contrexx_stripslashes($_POST['newsletter_mail_sender_mail']) : $arrSettings['sender_mail']['setvalue'];
        $mailSenderName = isset($_POST['newsletter_mail_sender_name']) ? contrexx_stripslashes($_POST['newsletter_mail_sender_name']) : $arrSettings['sender_name']['setvalue'];
        $mailReply = isset($_POST['newsletter_mail_sender_reply']) ? contrexx_stripslashes($_POST['newsletter_mail_sender_reply']) : $arrSettings['reply_mail']['setvalue'];
        $mailSmtpServer = isset($_POST['newsletter_mail_smtp_account']) ? contrexx_input2int($_POST['newsletter_mail_smtp_account']) : $_CONFIG['coreSmtpServer'];


        $this->_objTpl->loadTemplateFile('module_newsletter_mail_edit.html');
        $this->_pageTitle = $mailId > 0 ? ($copy ? $_ARRAYLANG['TXT_NEWSLETTER_COPY_EMAIL'] : $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_EMAIL']) : $_ARRAYLANG['TXT_NEWSLETTER_CREATE_NEW_EMAIL'];

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_MAIL_EDIT_TITLE' => $mailId > 0 ? ($copy ? $_ARRAYLANG['TXT_NEWSLETTER_COPY_EMAIL'] : $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_EMAIL']) : $_ARRAYLANG['TXT_NEWSLETTER_CREATE_NEW_EMAIL']
        ));

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        if (isset($_POST['newsletter_mail_save'])) {
            $objAttachment = $objDatabase->Execute("SELECT file_name FROM ".DBPREFIX."module_newsletter_attachment WHERE newsletter=".$mailId);
            if ($objAttachment !== false) {
                $arrCurrentAttachments = array();
                while (!$objAttachment->EOF) {
                    array_push($arrCurrentAttachments, $cx->getWebsiteImagesAttachWebPath() . '/' . $objAttachment->fields['file_name']);
                    $objAttachment->MoveNext();
                }

                $arrNewAttachments = array_diff($arrAttachment, $arrCurrentAttachments);
                $arrRemovedAttachments = array_diff($arrCurrentAttachments, $arrAttachment);
            }

            $mailHtmlContentReplaced = preg_replace('/\[\[([A-Z0-9_]*?)\]\]/', '{\\1}' , $mailHtmlContent);
            $mailHtmlContentReplaced = $this->_getBodyContent($mailHtmlContentReplaced);

            if ($mailId > 0) {
                $status = $this->_updateMail($mailId, $mailSubject, $mailTemplate, $mailSenderMail, $mailSenderName, $mailReply, $mailSmtpServer, $mailPriority, $arrAttachment, $mailHtmlContentReplaced);
            } else {
                $mailId = $this->_addMail($mailSubject, $mailTemplate, $mailSenderMail, $mailSenderName, $mailReply, $mailSmtpServer, $mailPriority, $arrAttachment, $mailHtmlContentReplaced);
                if ($mailId === false) {
                    $status = false;
                }
            }

            if ($status) {
                // prepare every link of HTML body for tracking function
                $this->_prepareNewsletterLinksForStore($mailId);

                $this->_setMailLists($mailId, $arrAssociatedLists, $mailSendDate);
                $this->setCrmMembershipFilter($mailId, $crmMembershipFilter['associate'], 'associate', $mailSendDate);
                $this->setCrmMembershipFilter($mailId, $crmMembershipFilter['include'], 'include', $mailSendDate);
                $this->setCrmMembershipFilter($mailId, $crmMembershipFilter['exclude'], 'exclude', $mailSendDate);
                $this->setMailGroups($mailId, $arrAssociatedGroups, $mailSendDate);

                foreach ($arrNewAttachments as $attachment) {
                    $this->_addMailAttachment($attachment, $mailId);
                }

                foreach ($arrRemovedAttachments as $attachment) {
                    $this->_removeMailAttachment($attachment, $mailId);
                }

                self::$strOkMessage .= $_ARRAYLANG['TXT_DATA_RECORD_STORED_SUCCESSFUL'];

                if (isset($_GET['sendMail']) && $_GET['sendMail'] == '1') {
                    return $this->_sendMailPage();
                } else {
                    return $this->_mails();
                }
            }
        } elseif ((!isset($_GET['setFormat']) || $_GET['setFormat'] != '1') && $mailId > 0) {
            $objResult = $objDatabase->SelectLimit("SELECT
                subject,
                template,
                content,
                attachment,
                priority,
                sender_email,
                sender_name,
                return_path,
                smtp_server
                FROM ".DBPREFIX."module_newsletter
                WHERE id=".$mailId, 1);
            if ($objResult !== false) {
                if ($objResult->RecordCount() == 1) {
                    $mailSubject = $objResult->fields['subject'];
                    $mailTemplate = $objResult->fields['template'];
                    $mailHtmlContent = preg_replace('/\{([A-Z0-9_-]+)\}/', '[[\\1]]', $objResult->fields['content']);
                    $mailPriority = $objResult->fields['priority'];
                    $mailSenderMail = $objResult->fields['sender_email'];
                    $mailSenderName = $objResult->fields['sender_name'];
                    $mailReply = $objResult->fields['return_path'];
                    $mailSmtpServer = $objResult->fields['smtp_server'];

                    $objList = $objDatabase->Execute("SELECT category FROM ".DBPREFIX."module_newsletter_rel_cat_news WHERE newsletter=".$mailId);
                    if ($objList !== false) {
                        while (!$objList->EOF) {
                            array_push($arrAssociatedLists, $objList->fields['category']);
                            $objList->MoveNext();
                        }

                    }

                    $crmMembershipFilter =
                        $this->emailEditGetCrmMembershipFilter($mailId);

                    $arrAssociatedGroups =
                        $this->emailEditGetAssociatedGroups($mailId);

                    if ($objResult->fields['attachment'] == '1') {
                        $objAttachment = $objDatabase->Execute("SELECT file_name FROM ".DBPREFIX."module_newsletter_attachment WHERE newsletter=".$mailId);
                        if ($objAttachment !== false) {
                            while (!$objAttachment->EOF) {
                                array_push($arrAttachment, $cx->getWebsiteImagesAttachWebPath() . '/' . $objAttachment->fields['file_name']);
                                $objAttachment->MoveNext();
                            }
                        }
                    }
                } else {
                    return $this->_mails();
                }
            }
        } else {
            $arrSettings = $this->_getSettings();

            $mailSenderMail = $arrSettings['sender_mail']['setvalue'];
            $mailSenderName = $arrSettings['sender_name']['setvalue'];
            $mailReply = $arrSettings['reply_mail']['setvalue'];
            $mailSmtpServer = $_CONFIG['coreSmtpServer'];

            if (!empty($_POST['textfield'])) {
                $mailHtmlContent = nl2br($_POST['textfield']);
            }
        }

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_EMAIL_ACCOUNT' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ACCOUNT'],
            'TXT_NEWSLETTER_SUBJECT' => $_ARRAYLANG['TXT_NEWSLETTER_SUBJECT'],
            'TXT_NEWSLETTER_SEND_AS' => $_ARRAYLANG['TXT_NEWSLETTER_SEND_AS'],
            'TXT_NEWSLETTER_TEMPLATE' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATE'],
            'TXT_NEWSLETTER_SENDER' => $_ARRAYLANG['TXT_NEWSLETTER_SENDER'],
            'TXT_NEWSLETTER_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL'],
            'TXT_NEWSLETTER_URI' => $_ARRAYLANG['TXT_NEWSLETTER_URI'],
            'TXT_NEWSLETTER_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NAME'],
            'TXT_NEWSLETTER_REPLY_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_REPLY_ADDRESS'],
            'TXT_NEWSLETTER_PRIORITY' => $_ARRAYLANG['TXT_NEWSLETTER_PRIORITY'],
            'TXT_NEWSLETTER_PRIORITY' => $_ARRAYLANG['TXT_NEWSLETTER_PRIORITY'],
            'TXT_NEWSLETTER_ATTACH' => $_ARRAYLANG['TXT_NEWSLETTER_ATTACH'],
            'TXT_NEWSLETTER_DISPLAY_FILE' => $_ARRAYLANG['TXT_NEWSLETTER_DISPLAY_FILE'],
            'TXT_NEWSLETTER_REMOVE_FILE' => $_ARRAYLANG['TXT_NEWSLETTER_REMOVE_FILE'],
            'TXT_NEWSLETTER_HTML_CONTENT' => $_ARRAYLANG['TXT_NEWSLETTER_HTML_CONTENT'],
            'TXT_NEWSLETTER_PLACEHOLDER_DIRECTORY' => $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDER_DIRECTORY'],
            'TXT_NEWSLETTER_USER_DATA' => $_ARRAYLANG['TXT_NEWSLETTER_USER_DATA'],
            'TXT_NEWSLETTER_EMAIL_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'],
            'TXT_NEWSLETTER_SEX' => $_ARRAYLANG['TXT_NEWSLETTER_SEX'],
            'TXT_NEWSLETTER_SALUTATION' => $_ARRAYLANG['TXT_NEWSLETTER_SALUTATION'],
            'TXT_NEWSLETTER_TITLE' => $_ARRAYLANG['TXT_NEWSLETTER_TITLE'],
            'TXT_NEWSLETTER_POSITION' => $_ARRAYLANG['TXT_NEWSLETTER_POSITION'],
            'TXT_NEWSLETTER_COMPANY' => $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'],
            'TXT_NEWSLETTER_INDUSTRY_SECTOR' => $_ARRAYLANG['TXT_NEWSLETTER_INDUSTRY_SECTOR'],
            'TXT_NEWSLETTER_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_NEWSLETTER_PHONE_PRIVATE' => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_PRIVATE'],
            'TXT_NEWSLETTER_PHONE_MOBILE' => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_MOBILE'],
            'TXT_NEWSLETTER_FAX' => $_ARRAYLANG['TXT_NEWSLETTER_FAX'],
            'TXT_NEWSLETTER_WEBSITE' => $_ARRAYLANG['TXT_NEWSLETTER_WEBSITE'],

            'TXT_NEWSLETTER_LASTNAME' => $_ARRAYLANG['TXT_NEWSLETTER_LASTNAME'],
            'TXT_NEWSLETTER_FIRSTNAME' => $_ARRAYLANG['TXT_NEWSLETTER_FIRSTNAME'],
            'TXT_NEWSLETTER_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_NEWSLETTER_ZIP' => $_ARRAYLANG['TXT_NEWSLETTER_ZIP'],
            'TXT_NEWSLETTER_CITY' => $_ARRAYLANG['TXT_NEWSLETTER_CITY'],
            'TXT_NEWSLETTER_COUNTRY' => $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY'],
            'TXT_NEWSLETTER_PHONE' => $_ARRAYLANG['TXT_NEWSLETTER_PHONE'],
            'TXT_NEWSLETTER_BIRTHDAY' => $_ARRAYLANG['TXT_NEWSLETTER_BIRTHDAY'],
            'TXT_NEWSLETTER_GENERAL' => $_ARRAYLANG['TXT_NEWSLETTER_GENERAL'],
            'TXT_NEWSLETTER_MODIFY_PROFILE' => $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_PROFILE'],
            'TXT_NEWSLETTER_UNSUBSCRIBE' => $_ARRAYLANG['TXT_NEWSLETTER_UNSUBSCRIBE'],
            'TXT_NEWSLETTER_PLACEHOLDER_NOT_ON_BROWSER_VIEW' => $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDER_NOT_ON_BROWSER_VIEW'],
            'TXT_NEWSLETTER_PLACEHOLDER_NOT_FOR_CRM' => $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDER_NOT_FOR_CRM'],
            'TXT_NEWSLETTER_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_DATE'],
            'TXT_NEWSLETTER_DISPLAY_IN_BROWSER_LINK' => $_ARRAYLANG['TXT_NEWSLETTER_DISPLAY_IN_BROWSER_LINK'],
            'TXT_NEWSLETTER_SUBJECT' => $_ARRAYLANG['TXT_NEWSLETTER_SUBJECT'],
            'TXT_NEWSLETTER_SAVE' => $_ARRAYLANG['TXT_NEWSLETTER_SAVE'],
            'TXT_NEWSLETTER_BACK' => $_ARRAYLANG['TXT_NEWSLETTER_BACK'],
            'TXT_NEWSLETTER_CONFIRM_EMPTY_TEXT' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_EMPTY_TEXT']
        ));

        $act = $copy ? 'copyMail' : 'editMail';
        // remove newsletter_link_N value from rel attribute of the links
        if ($copy) {
            $mailHtmlContent = $this->_prepareNewsletterLinksForCopy($mailHtmlContent);
        }

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_MAIL_ID' => ($copy ? 0 : $mailId),
            'NEWSLETTER_MAIL_SUBJECT' => htmlentities($mailSubject, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_MAIL_HTML_CONTENT' => new \Cx\Core\Wysiwyg\Wysiwyg('newsletter_mail_html_content', contrexx_raw2xhtml($mailHtmlContent), 'fullpage'),
            'NEWSLETTER_MAIL_PRIORITY_MENU' => $this->_getMailPriorityMenu($mailPriority, 'name="newsletter_mail_priority" style="width:300px;"'),
            'NEWSLETTER_MAIL_TEMPLATE_MENU' => $this->_getTemplateMenu($mailTemplate, 'name="newsletter_mail_template" style="width:300px;" onchange="document.getElementById(\'newsletter_mail_form\').action=\'index.php?cmd=Newsletter&amp;act='.$act.'&amp;id='.$mailId.'&amp;setFormat=1\';document.getElementById(\'newsletter_mail_form\').submit()"'),
            'NEWSLETTER_MAIL_SENDER_MAIL' => htmlentities($mailSenderMail, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_MAIL_SENDER_NAME' => htmlentities($mailSenderName, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_MAIL_REPLY' => htmlentities($mailReply, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_MAIL_SMTP_SERVER' => \SmtpSettings::getSmtpAccountMenu($mailSmtpServer, 'name="newsletter_mail_smtp_account" style="width:300px;"'),
            'NEWSLETTER_MAIL_SEND' => $_GET['act'] == 'sendMail' ? 1 : 0
        ));

        $this->_objTpl->setVariable('TXT_NEWSLETTER_HTML_UC', $_ARRAYLANG['TXT_NEWSLETTER_HTML_UC']);
        $this->_objTpl->touchBlock('newsletter_mail_html_content');

        // campaign has been sent already, disable recipient selection
        if (!$copy && $mailId > 0 && $mailSendDate > 0) {
            $this->_objTpl->touchBlock('associatedListToolTip');
            $this->_objTpl->touchBlock('associatedGroupToolTipAfterSent');
            $this->_objTpl->hideBlock('associatedGroupToolTipBeforeSend');
            $this->_objTpl->touchBlock('crmMembershipFilterToolTipAfterSent');
            $this->_objTpl->hideBlock('crmMembershipFilterToolTipBeforeSend');
            $this->_objTpl->touchBlock('crmMembershipAssociatedToolTipAfterSend');
            $this->_objTpl->hideBlock('crmMembershipAssociatedToolTipBeforeSend');

            $this->_objTpl->setGlobalVariable(array(
                'TXT_NEWSLETTER_INFO_ABOUT_ASSOCIATED_LISTS' => $_ARRAYLANG['TXT_NEWSLETTER_INFO_ABOUT_ASSOCIATED_LISTS'],
                'NEWSLETTER_LIST_DISABLED'                   => 'disabled="disabled"'
            ));
        } else {
            // allow recipient selection as long as the campaign has not yet
            // been sent
            $this->_objTpl->setVariable(array(
                'TXT_NEWSLETTER_INFO_ABOUT_ASSOCIATED_LISTS_SEND' => $_ARRAYLANG['TXT_NEWSLETTER_INFO_ABOUT_ASSOCIATED_LISTS_SEND'],
                'TXT_NEWSLETTER_CRM_MEMBERSHIP_FILTER_TOOLTIP'    => sprintf($_ARRAYLANG['TXT_NEWSLETTER_CRM_MEMBERSHIP_FILTER_TOOLTIP'], '<em>'.$_ARRAYLANG['TXT_NEWSLETTER_ASSOCIATED_LISTS'].'</em>', '<em>'.$_ARRAYLANG['TXT_NEWSLETTER_ASSOCIATED_GROUPS'].'</em>'),
            ));

            $this->_objTpl->hideBlock('associatedListToolTip');
            $this->_objTpl->hideBlock('associatedGroupToolTipAfterSent');
            $this->_objTpl->touchBlock('associatedGroupToolTipBeforeSend');
            $this->_objTpl->hideBlock('crmMembershipFilterToolTipAfterSent');
            $this->_objTpl->touchBlock('crmMembershipFilterToolTipBeforeSend');
            $this->_objTpl->hideBlock('crmMembershipAssociatedToolTipAfterSend');
            $this->_objTpl->touchBlock('crmMembershipAssociatedToolTipBeforeSend');
        }

        // parse newsletter list selection
        $this->emailEditParseLists($arrAssociatedLists);

        // parse user group selection
        if (!empty($arrSettings['deliver_user_recipients']['setvalue'])) {
            $this->emailEditParseGroups($arrAssociatedGroups);
            $this->_objTpl->touchBlock('deliver_user_recipients');
        } else {
            $this->_objTpl->hideBlock('deliver_user_recipients');
        }

        // parse Crm membership filter
        if (
            !empty($arrSettings['deliver_crm_recipients']['setvalue']) ||
            !empty($arrSettings['use_crm_filter']['setvalue'])
        ) {
            $objCrmLibrary = new \Cx\Modules\Crm\Controller\CrmLibrary('Crm');
            $crmMemberships = array_keys($objCrmLibrary->getMemberships());

            if (!empty($arrSettings['deliver_crm_recipients']['setvalue'])) {
                $objCrmLibrary->getMembershipDropdown($this->_objTpl, $crmMemberships, 'crmMembership', $crmMembershipFilter['associate']);
                $this->_objTpl->touchBlock('deliver_crm_recipients');
            } else {
                $this->_objTpl->hideBlock('deliver_crm_recipients');
            }
            if (!empty($arrSettings['use_crm_filter']['setvalue'])) {
                $objCrmLibrary->getMembershipDropdown($this->_objTpl, $crmMemberships, 'crmMembershipFilterInclude', $crmMembershipFilter['include']);
                $objCrmLibrary->getMembershipDropdown($this->_objTpl, $crmMemberships, 'crmMembershipFilterExclude', $crmMembershipFilter['exclude']);
                $this->_objTpl->touchBlock('use_crm_filter');
            } else {
                $this->_objTpl->hideBlock('use_crm_filter');
            }
            $this->_objTpl->setVariable(array(
                'TXT_NEWSLETTER_CRM_MEMBERSHIP_FILTER'          => $_ARRAYLANG['TXT_NEWSLETTER_CRM_MEMBERSHIP_FILTER'],
                'TXT_NEWSLETTER_CRM_MEMBERSHIP'                 => $_ARRAYLANG['TXT_NEWSLETTER_CRM_MEMBERSHIP'],
                'TXT_NEWSLETTER_CHOOSE_CRM_MEMBERSHIPS'         => $_ARRAYLANG['TXT_NEWSLETTER_CHOOSE_CRM_MEMBERSHIPS'],
                'TXT_NEWSLETTER_CRM_MEMBERSHIP_INCLUDE_TXT'     => $_ARRAYLANG['TXT_NEWSLETTER_CRM_MEMBERSHIP_INCLUDE_TXT'],
                'TXT_NEWSLETTER_CRM_MEMBERSHIP_EXCLUDE_TXT'     => $_ARRAYLANG['TXT_NEWSLETTER_CRM_MEMBERSHIP_EXCLUDE_TXT'],
            ));
            \JS::activate('chosen');
        } else {
            $this->_objTpl->hideBlock('deliver_crm_recipients');
            $this->_objTpl->hideBlock('use_crm_filter');
        }

        if (count($arrAttachment) > 0) {
            foreach ($arrAttachment as $attachment) {
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_MAIL_ATTACHMENT_NR' => $attachmentNr,
                    'NEWSLETTER_MAIL_ATTACHMENT_NAME' => substr($attachment, strrpos($attachment, '/')+1),
                    'NEWSLETTER_MAIL_ATTACHMENT_URL' => $attachment,
                ));
                $this->_objTpl->parse('newsletter_mail_attachment_list');
                $attachmentNr++;
            }
        } else {
            $this->_objTpl->hideBlock('newsletter_mail_attachment_list');
        }

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_MAIL_ATTACHMENT_NR' => $attachmentNr,
            'NEWSLETTER_MAIL_ATTACHMENT_BOX' => $attachmentNr > 0 ? 'block' : 'none',
        ));

        // Mediabrowser
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions(array(
            'views' => 'filebrowser',
            'type' => 'button'
        ));
        $mediaBrowser->setCallback('mediaBrowserCallback');
        $this->_objTpl->setVariable(array(
            'NEWSLETTER_ATTACH_FILE' => $mediaBrowser->getXHtml($_ARRAYLANG['TXT_NEWSLETTER_ATTACH_FILE'])
        ));

        return true;
    }


    /**
     * Parse the lists to be selected as email recipients
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       array $associatedLists
     */
    function emailEditParseLists($associatedLists)
    {
        global $_ARRAYLANG;

        $arrLists = self::getLists();
        $listNr = 0;
        foreach ($arrLists as $listID => $listItem) {
            $column = $listNr % 3;
            $this->_objTpl->setVariable(array(
                'NEWSLETTER_LIST_ID' => $listID,
                'NEWSLETTER_LIST_NAME' => contrexx_raw2xhtml($listItem['name']),
                'NEWSLETTER_SHOW_RECIPIENTS_OF_LIST_TXT' => sprintf(
                    $_ARRAYLANG['TXT_NEWSLETTER_SHOW_RECIPIENTS_OF_LIST'],
                    contrexx_raw2xhtml($listItem['name'])),
                'NEWSLETTER_LIST_ASSOCIATED' =>
                    (in_array($listID, $associatedLists)
                        ? 'checked="checked"' : ''),
                'TXT_NEWSLETTER_ASSOCIATED_LISTS' =>
                    $_ARRAYLANG['TXT_NEWSLETTER_ASSOCIATED_LISTS'],
            ));
            $this->_objTpl->parse('newsletter_mail_associated_list_'.$column);
            $listNr++;
        }
    }


    /**
     * Parse the groups into the mail edit page
     *
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @todo        Apparently parses one group too much
     */
    private function emailEditParseGroups($associatedGroups = array()) {
        global $_ARRAYLANG;

        $groups = $this->_getGroups();
        $groupNr = 0;
        foreach ($groups as $groupID => $groupItem) {
            $column = $groupNr % 3;
            $this->_objTpl->setVariable(array(
                'NEWSLETTER_GROUP_ID' => $groupID,
                'NEWSLETTER_GROUP_NAME' => htmlentities(
                    $groupItem, ENT_QUOTES, CONTREXX_CHARSET),
                'NEWSLETTER_SHOW_RECIPIENTS_OF_GROUP_TXT' => sprintf(
                    $_ARRAYLANG['TXT_NEWSLETTER_SHOW_RECIPIENTS_OF_GROUP'],
                    $groupItem
                ),
                'NEWSLETTER_GROUP_ASSOCIATED' =>
                    (in_array($groupID, $associatedGroups)
                        ? 'checked="checked"' : ''),
                'TXT_NEWSLETTER_ASSOCIATED_GROUPS' =>
                    $_ARRAYLANG['TXT_NEWSLETTER_ASSOCIATED_GROUPS'],
            ));
            $this->_objTpl->parse('newsletter_mail_associated_group_'.$column);
            $groupNr++;
        }
    }

    /**
     * Return the Crm membership filter of an email
     * @param       int $mail
     * @return      array
     */
    protected function emailEditGetCrmMembershipFilter($mail)
    {
        global $objDatabase;

        $query = sprintf('
            SELECT `membership_id`, `type`
              FROM `%1$smodule_newsletter_rel_crm_membership_newsletter`
             WHERE `newsletter_id`=%2$s',
            DBPREFIX, $mail
        );
        $data = $objDatabase->Execute($query);
        $list = array(
            'associate' => array(),
            'include' => array(),
            'exclude' => array()
        );
        if ($data !== false) {
            while (!$data->EOF) {
                $list[$data->fields['type']][] =  $data->fields['membership_id'];
                $data->MoveNext();
            }
        }
        return $list;
    }

    /**
     * Return the associated access groups of an email
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $mail
     * @return      array
     */
    private function emailEditGetAssociatedGroups($mail)
    {
        global $objDatabase;

        $query = sprintf('
            SELECT `userGroup`
              FROM `%1$smodule_newsletter_rel_usergroup_newsletter`
             WHERE `newsletter`=%2$s',
            DBPREFIX, $mail
        );
        $data = $objDatabase->Execute($query);
        $list = array();
        if ($data !== false) {
            while (!$data->EOF) {
                $list[] =  $data->fields['userGroup'];
                $data->MoveNext();
            }
        }
        return $list;
    }

    function _update()
    {
        die('Feature not available!');

        global $objDatabase;

        $objColumns = $objDatabase->MetaColumns(DBPREFIX."module_newsletter");
        if ($objColumns !== false) {
            if ($objColumns['DATE_CREATE']->type != 'int') {
                $query = "SELECT `id`, `date_create` FROM ".DBPREFIX."module_newsletter";
                $objNewsletter = $objDatabase->Execute($query);
                if ($objNewsletter !== false) {
                    $arrNewsletter = array();
                    while (!$objNewsletter->EOF) {
                        $arrNewsletter[$objNewsletter->fields['id']] = $objNewsletter->fields['date_create'];
                        $objNewsletter->MoveNext();
                    }

                    $query = "ALTER TABLE ".DBPREFIX."module_newsletter CHANGE `date_create` `date_create` INT( 14 ) UNSIGNED NOT NULL";
                    if ($objDatabase->Execute($query) === false) {
                        die('DB error: '.$query);
                    }

                    foreach ($arrNewsletter as $id => $dateCreate) {
                        $date = mktime(0,0,0,intval(substr($dateCreate,5,2)),intval(substr($dateCreate,8,2)),intval(substr($dateCreate,0,4)));
                        $query = "UPDATE ".DBPREFIX."module_newsletter SET `date_create`=".$date." WHERE `id`=".$id;
                        if ($objDatabase->Execute($query) === false) {
                            print "DB error: ".$query."<br />";
                        }
                    }
                }
            }

            if ($objColumns['DATE_SENT']->type != 'int') {
                $query = "SELECT `id`, `date_sent` FROM ".DBPREFIX."module_newsletter";
                $objNewsletter = $objDatabase->Execute($query);
                if ($objNewsletter !== false) {
                    $arrNewsletter = array();
                    while (!$objNewsletter->EOF) {
                        $arrNewsletter[$objNewsletter->fields['id']] = $objNewsletter->fields['date_sent'];
                        $objNewsletter->MoveNext();
                    }

                    $query = "ALTER TABLE ".DBPREFIX."module_newsletter CHANGE `date_sent` `date_sent` INT( 14 ) UNSIGNED NOT NULL";
                    if ($objDatabase->Execute($query) === false) {
                        die('DB error: '.$query);
                    }

                    foreach ($arrNewsletter as $id => $dateSent) {
                        $date = mktime(0,0,0,intval(substr($dateSent,5,2)),intval(substr($dateSent,8,2)),intval(substr($dateSent,0,4)));
                        $query = "UPDATE ".DBPREFIX."module_newsletter SET `date_sent`=".$date." WHERE `id`=".$id;
                        if ($objDatabase->Execute($query) === false) {
                            print "DB error: ".$query."<br />";
                        }
                    }
                }
            }
        }
    }


    function _mails()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_CAMPAIGNS'];
        $this->_objTpl->loadTemplateFile('module_newsletter_mails.html');
        $rowNr = 0;
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_SUBJECT' => $_ARRAYLANG['TXT_NEWSLETTER_SUBJECT'],
            'TXT_NEWSLETTER_NAME_OF_EMAIL_CAMPAIGN' => $_ARRAYLANG['TXT_NEWSLETTER_NAME_OF_EMAIL_CAMPAIGN'],
            'TXT_NEWSLETTER_STATS' => $_ARRAYLANG['TXT_NEWSLETTER_STATS'],
            'TXT_NEWSLETTER_EMAIL_CAMPAIGNS' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_CAMPAIGNS'],
            'TXT_NEWSLETTER_OVERVIEW' => $_ARRAYLANG['TXT_NEWSLETTER_OVERVIEW'],
            'TXT_NEWSLETTER_SENT' => $_ARRAYLANG['TXT_NEWSLETTER_SENT'],
            'TXT_NEWSLETTER_FEEDBACK' => $_ARRAYLANG['TXT_NEWSLETTER_FEEDBACK'],
            'TXT_NEWSLETTER_SENDER' => $_ARRAYLANG['TXT_NEWSLETTER_SENDER'],
            'TXT_NEWSLETTER_FORMAT' => $_ARRAYLANG['TXT_NEWSLETTER_FORMAT'],
            'TXT_NEWSLETTER_TEMPLATE' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATE'],
            'TXT_NEWSLETTER_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_DATE'],
            'TXT_NEWSLETTER_FUNCTIONS' => $_ARRAYLANG['TXT_NEWSLETTER_FUNCTIONS'],
            'TXT_NEWSLETTER_CHECK_ALL' => $_ARRAYLANG['TXT_NEWSLETTER_CHECK_ALL'],
            'TXT_NEWSLETTER_UNCHECK_ALL' => $_ARRAYLANG['TXT_NEWSLETTER_UNCHECK_ALL'],
            'TXT_NEWSLETTER_WITH_SELECTED' => $_ARRAYLANG['TXT_NEWSLETTER_WITH_SELECTED'],
            'TXT_NEWSLETTER_DELETE' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE'],
            'TXT_NEWSLETTER_CREATE_NEW_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_CREATE_NEW_EMAIL'],
            'TXT_NEWSLETTER_CREATE_NEW_EMAIL_WITH_NEWS' => $_ARRAYLANG['TXT_NEWSLETTER_CREATE_NEW_EMAIL_WITH_NEWS'],
            'TXT_NEWSLETTER_CONFIRM_DELETE_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_DELETE_MAIL'],
            'TXT_NEWSLETTER_CANNOT_UNDO_OPERATION' => $_ARRAYLANG['TXT_NEWSLETTER_CANNOT_UNDO_OPERATION'],
            'TXT_NEWSLETTER_CONFIRM_DELETE_CHECKED_EMAILS' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_DELETE_CHECKED_EMAILS']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_SEND_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_SEND_EMAIL'],
            'TXT_NEWSLETTER_MODIFY_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_EMAIL'],
            'TXT_NEWSLETTER_COPY_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_COPY_EMAIL'],
            'TXT_NEWSLETTER_DELETE_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE_EMAIL'],
        ));

        $objResultCount = $objDatabase->SelectLimit("SELECT COUNT(1) AS mail_count FROM ".DBPREFIX."module_newsletter", 1);
        if ($objResultCount !== false) {
            $mailCount = $objResultCount->fields['mail_count'];
        } else {
            $mailCount = 0;
        }

        $arrTemplates = $this->_getTemplates();
        // feedback counting
        $arrFeedback = array();
        $objFeedback = $objDatabase->SelectLimit("SELECT
            tblMail.id,
            COUNT(DISTINCT tblMailLinkFB.recipient_id,tblMailLinkFB.recipient_type) AS feedback_count
            FROM ".DBPREFIX."module_newsletter AS tblMail
                INNER JOIN ".DBPREFIX."module_newsletter_email_link_feedback AS tblMailLinkFB ON tblMailLinkFB.email_id = tblMail.id
            GROUP BY tblMail.id
            ORDER BY status, id DESC", $_CONFIG['corePagingLimit'], $pos);
        if ($objFeedback !== false) {
            while (!$objFeedback->EOF) {
                $arrFeedback[$objFeedback->fields['id']] = $objFeedback->fields['feedback_count'];
                $objFeedback->MoveNext();
            }
        }
        $arrSettings = $this->_getSettings();
        $objResult = $objDatabase->SelectLimit("SELECT
            tblMail.id,
            tblMail.subject,
            tblMail.date_create,
            tblMail.sender_email,
            tblMail.sender_name,
            tblMail.template,
            tblMail.status,
            tblMail.`count`,
            tblMail.date_sent
            FROM ".DBPREFIX."module_newsletter AS tblMail
            ORDER BY date_create DESC, status, id DESC", $_CONFIG['corePagingLimit'], $pos);
        if ($objResult !== false) {
            $arrMailRecipientCount = $this->_getMailRecipientCount(NULL, $_CONFIG['corePagingLimit'], $pos);
            while (!$objResult->EOF) {
                $feedbackCount = isset($arrFeedback[$objResult->fields['id']]) ? $arrFeedback[$objResult->fields['id']] : 0;
                $feedbackStrFormat = '%1$s (%2$s%%)';
                $feedbackPercent = ($objResult->fields['count'] > 0 && $feedbackCount  > 0) ? round(100 / $objResult->fields['count'] * $feedbackCount) : 0;
                $feedback = sprintf($feedbackStrFormat, $feedbackCount, $feedbackPercent);
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_MAIL_ROW_CLASS' => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'NEWSLETTER_MAIL_SUBJECT' => htmlentities($objResult->fields['subject'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_MAIL_SENDER_NAME' => htmlentities($objResult->fields['sender_name'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_MAIL_SENDER_EMAIL' => htmlentities($objResult->fields['sender_email'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_MAIL_FEEDBACK' => $feedback,
                    'NEWSLETTER_FEEDBACK_OVERVIEW' => sprintf($_ARRAYLANG['TXT_NEWSLETTER_FEEDBACK_OVERVIEW'], $feedbackCount),
                    'NEWSLETTER_MAIL_SENT_DATE' => $objResult->fields['date_sent'] > 0 ? date(ASCMS_DATE_FORMAT_DATETIME, $objResult->fields['date_sent']) : '-',
                    //'NEWSLETTER_MAIL_FORMAT' => $objResult->fields['format'],
                    'NEWSLETTER_MAIL_TEMPLATE' => htmlentities($arrTemplates[$objResult->fields['template']]['name'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_MAIL_DATE' => date(ASCMS_DATE_FORMAT_DATETIME, $objResult->fields['date_create']),
                    'NEWSLETTER_MAIL_COUNT' => $objResult->fields['count'],
                    'NEWSLETTER_MAIL_USERS' => isset($arrMailRecipientCount[$objResult->fields['id']]) ? $arrMailRecipientCount[$objResult->fields['id']] : 0
                ));

                $this->_objTpl->setGlobalVariable('NEWSLETTER_MAIL_ID', $objResult->fields['id']);

                if ($arrSettings['statistics']['setvalue']) {
                    if ($objResult->fields['count'] > 0 && $feedbackCount > 0) {
                        $this->_objTpl->touchBlock('newsletter_mail_feedback_link');
                        $this->_objTpl->hideBlock('newsletter_mail_feedback_empty');
                    } else {
                        $this->_objTpl->touchBlock('newsletter_mail_feedback_empty');
                        $this->_objTpl->hideBlock('newsletter_mail_feedback_link');
                    }
                } else {
                    $this->_objTpl->hideBlock('newsletter_stats_entry');
                }

                $this->_objTpl->parse("newsletter_list");
                $objResult->MoveNext();
                $rowNr++;
            }
            if ($rowNr > 0) {
                $this->_objTpl->touchBlock("newsletter_list_multiAction");
//                if ($mailCount > $_CONFIG['corePagingLimit']) {
// TODO: All calls to getPaging(): Shouldn't '&' be written as '&amp;'?
                $paging = getPaging($mailCount, $pos, "&cmd=Newsletter&act=mails", "", false, $_CONFIG['corePagingLimit']);
//                }
                $this->_objTpl->setVariable('NEWSLETTER_MAILS_PAGING', "<br />".$paging."<br />");
            } else {
                $this->_objTpl->hideBlock("newsletter_list_multiAction");
            }
            if ($arrSettings['statistics']['setvalue']) {
                $this->_objTpl->touchBlock('newsletter_stats');
            } else {
                $this->_objTpl->hideBlock('newsletter_stats');
            }
        }
    }


    function _deleteMail()
    {
        global $objDatabase, $_ARRAYLANG;

        $status = true;
        $arrMailIds = array();
        if (isset($_GET['id'])) {
            array_push($arrMailIds, intval($_GET['id']));
        } elseif (isset($_POST['newsletter_mail_selected'])) {
            foreach ($_POST['newsletter_mail_selected'] as $mailId) {
                array_push($arrMailIds, intval($mailId));
            }
        }

        if (count($arrMailIds) > 0) {
            foreach ($arrMailIds as $mailId) {
                if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_attachment where newsletter=".$mailId) !== false &&
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_rel_cat_news where newsletter=".$mailId) !== false &&
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_tmp_sending where newsletter=".$mailId) !== false &&
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_email_link where email_id=".$mailId) !== false &&
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_email_link_feedback where email_id=".$mailId) !== false &&
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter where id=".$mailId)) {
                } else {
                    $status = false;
                }
            }

            if ($status) {
                self::$strOkMessage = count($arrMailIds) > 1 ? $_ARRAYLANG['TXT_NEWSLETTER_EMAILS_DELETED'] : $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_DELETED'];
            } else {
                self::$strErrMessage = count($arrMailIds) > 1 ? $_ARRAYLANG['TXT_NEWSLETTER_ERROR_DELETE_EMAILS'] : $_ARRAYLANG['TXT_NEWSLETTER_ERROR_DELETE_EMAIL'];
            }
        }
        $this->_mails();
    }


    function _copyMail()
    {
        $this->_editMail(true);
    }


    function _getBodyContent($fullContent)
    {
        $posBody = 0;
        $posStartBodyContent = 0;
        $arrayMatches = array();
        $res = preg_match_all('/<body[^>]*>/i', $fullContent, $arrayMatches);
        if ($res==true) {
            $bodyStartTag = $arrayMatches[0][0];
            // Position des Start-Tags holen
            $posBody = strpos($fullContent, $bodyStartTag, 0);
            // Beginn des Contents ohne Body-Tag berechnen
            $posStartBodyContent = $posBody + strlen($bodyStartTag);
        }
        $posEndTag=strlen($fullContent);
        $res = preg_match_all('/<\/body>/i', $fullContent, $arrayMatches);
        if ($res == true) {
            $bodyEndTag=$arrayMatches[0][0];
            // Position des End-Tags holen
            $posEndTag = strpos($fullContent, $bodyEndTag, 0);
            // Content innerhalb der Body-Tags auslesen
         }
         $content = substr($fullContent, $posStartBodyContent, $posEndTag  - $posStartBodyContent);
         return $content;
    }


    function _addMailAttachment($attachment, $mailId = 0)
    {
        global $objDatabase;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        $fileName = substr($attachment, strrpos($attachment, '/')+1);

        $objAttachment = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_attachment WHERE file_name='".$fileName."'", 1);
        if ($objAttachment !== false) {
            if ($objAttachment->RecordCount() == 1) {
                $md5Current = @md5_file($cx->getWebsiteImagesAttachPath() . '/' . $fileName);
                $md5New = @md5_file($cx->getWebsiteDocumentRootPath() . $attachment);

                if ($md5Current !== false && $md5Current === $md5New) {
                    if ($objDatabase->Execute("    INSERT INTO ".DBPREFIX."module_newsletter_attachment (`newsletter`, `file_name`)
                                                VALUES (".$mailId.", '".$fileName."')") !== false) {
                        return true;
                    }
                }
            }

            $nr = 0;
            $fileNameTmp = $fileName;
            while (file_exists($cx->getWebsiteImagesAttachPath().'/'.$fileNameTmp)) {
                $md5Current = @md5_file($cx->getWebsiteImagesAttachPath() . '/' . $fileNameTmp);
                $md5New = @md5_file($cx->getWebsiteDocumentRootPath() . $attachment);

                if ($md5Current !== false && $md5Current === $md5New) {
                    if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_newsletter_attachment (`newsletter`, `file_name`) VALUES (".$mailId.", '".$fileNameTmp."')") !== false) {
                        return true;
                    }
                }
                $nr++;
                $PathInfo = pathinfo($fileName);
                $fileNameTmp = substr($PathInfo['basename'],0,strrpos($PathInfo['basename'],'.')).$nr.'.'.$PathInfo['extension'];
            }

            if (copy($cx->getWebsiteDocumentRootPath() . $attachment, $cx->getWebsiteImagesAttachPath().'/'.$fileNameTmp)) {
                if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_newsletter_attachment (`newsletter`, `file_name`) VALUES (".$mailId.", '".$fileNameTmp."')") !== false) {
                    return true;
                }
            }
        }
        return false;
    }


    function _removeMailAttachment($attachment, $mailId = 0)
    {
        global $objDatabase;

        $fileName = substr($attachment, strrpos($attachment, '/')+1);
        $objAttachment = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_attachment WHERE file_name='".$fileName."'", 2);
        if ($objAttachment !== false) {
            if ($objAttachment->RecordCount() < 2) {
                @unlink(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesAttachPath().'/'.$fileName);
            }

            if ($objDatabase->SelectLimit("DELETE FROM ".DBPREFIX."module_newsletter_attachment WHERE file_name='".$fileName."' AND newsletter=".$mailId, 1) !== false) {
                return true;
            }
        }
        return false;
    }


    function _getMailPriorityMenu($selectedPriority = 3, $attributes = '')
    {
        global $_ARRAYLANG;

        $menu = "<select".(!empty($attributes) ? " ".$attributes : "").">\n";
        foreach ($this->_arrMailPriority as $priorityId => $priority) {
            $menu .= "<option value=\"".$priorityId."\"".($selectedPriority == $priorityId ? "selected=\"selected\"" : "").">".$_ARRAYLANG[$priority]."</option>\n";
        }
        $menu .= "</select>\n";

        return $menu;
    }


    function _addMail($subject, $template, $senderMail, $senderName, $replyMail, $smtpServer, $priority, $arrAttachment, $htmlContent)
    {
        global $objDatabase, $_ARRAYLANG;

        if (!empty($subject)) {
            if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_newsletter
                (subject,
                template,
                content,
                attachment,
                priority,
                sender_email,
                sender_name,
                return_path,
                smtp_server,
                date_create
                ) VALUES (
                '".addslashes($subject)."',
                ".intval($template).",
                '".addslashes($htmlContent)."',
                '".(count($arrAttachment) > 0 ? '1' : '0')."',
                ".intval($priority).",
                '".addslashes($senderMail)."',
                '".addslashes($senderName)."',
                '".addslashes($replyMail)."',
                ".intval($smtpServer).",
                ".time().")") !== false) {
                $mailId = $objDatabase->Insert_ID();
                return $mailId;
            } else {
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_ERROR_SAVE_RETRY'];


            }
        } else {
            self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_ERROR_NO_SUBJECT'];
        }
        return false;
    }


    function _updateMail($mailId, $subject, $template, $senderMail, $senderName, $replyMail, $smtpServer, $priority, $arrAttachment, $htmlContent)
    {
        global $objDatabase, $_ARRAYLANG;

        if (!empty($subject)) {
            if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_newsletter
                SET subject='".addslashes($subject)."',
                template=".intval($template).",
                content='".addslashes($htmlContent)."',
                attachment='".(count($arrAttachment) > 0 ? '1' : '0')."',
                priority=".intval($priority).",
                sender_email='".addslashes($senderMail)."',
                sender_name='".addslashes($senderName)."',
                return_path='".addslashes($replyMail)."',
                smtp_server=".intval($smtpServer)."
                WHERE id=".$mailId) !== false) {
                return true;
            } else {
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_ERROR_SAVE_RETRY'];
            }
        } else {
            self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_ERROR_NO_SUBJECT'];
        }
        return false;
    }


    function _setMailLists($mailId, $arrLists, $mailSentDate)
    {
        global $objDatabase;

        $arrCurrentList = array();

        if ($mailSentDate > 0) {
            return false;
        }

        $objRelList = $objDatabase->Execute("SELECT category FROM ".DBPREFIX."module_newsletter_rel_cat_news WHERE newsletter=".$mailId);
        if (!$objRelList) {
            return false;
        }
        while (!$objRelList->EOF) {
            array_push($arrCurrentList, $objRelList->fields['category']);
            $objRelList->MoveNext();
        }

        $arrNewLists = array_diff($arrLists, $arrCurrentList);
        $arrRemovedLists = array_diff($arrCurrentList, $arrLists);

        foreach ($arrNewLists as $listId) {
            $objDatabase->Execute("
                INSERT INTO ".DBPREFIX."module_newsletter_rel_cat_news (
                    `newsletter`, `category`
                ) VALUES (
                    $mailId, $listId
                )
            ");
        }

        foreach ($arrRemovedLists as $listId) {
            $objDatabase->Execute("
                DELETE FROM ".DBPREFIX."module_newsletter_rel_cat_news
                 WHERE newsletter=$mailId
                   AND category=$listId
            ");
        }
        return true;
    }

    /**
     * Store the CRM membership filter
     *
     * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
     * @param       int $mailID
     * @param       array $membershipFilter
     * @param       string  $type
     * @param       string $mailSentDate sent date
     * @return      boolean true if modification was made, else otherwise
     */
    protected function setCrmMembershipFilter($mailID, $membershipFilter, $type, $mailSentDate) {
        global $objDatabase;

        if ($mailSentDate > 0) {
            return false;
        }

        foreach ($membershipFilter as $membershipId) {
            $objDatabase->Execute(
                sprintf('
                    REPLACE INTO
                        `%smodule_newsletter_rel_crm_membership_newsletter`
                        (`newsletter_id`, `membership_id`, `type`)
                    VALUES
                        (%s, %s, \'%s\')
                    ', DBPREFIX, $mailID, intval($membershipId), $type
                )
            );
        }
        if (count($membershipFilter) > 0) {
            $delString = implode(',', $membershipFilter);

            $query = sprintf('
                DELETE FROM
                    `%smodule_newsletter_rel_crm_membership_newsletter`
                WHERE
                    `membership_id` NOT IN (%s)
                AND
                    `newsletter_id` = %s
                AND `type` = \'%s\'
                ',
                DBPREFIX,
                $delString,
                $mailID,
                $type
            );
            $objDatabase->Execute($query);
        } else {
            // no groups were selected -> remove all group associations
            $query = sprintf('
                DELETE FROM
                    `%smodule_newsletter_rel_crm_membership_newsletter`
                WHERE
                    `newsletter_id` = %s
                AND `type` = \'%s\'
                ',
                DBPREFIX,
                $mailID,
                $type
            );
            $objDatabase->Execute($query);
        }
        return true;
    }

    /**
     * Associate the user groups with the mail
     *
     * Associate the access user groups with the
     * newsletter email.
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $mailID
     * @param       array $groups
     * @param       string $mailSentDate sent date
     */
    private function setMailGroups($mailID, $groups, $mailSentDate) {
        global $objDatabase;

        if ($mailSentDate > 0) {
            return false;
        }

        foreach ($groups as $group) {
            $objDatabase->Execute(
                sprintf('
                    REPLACE INTO
                        `%smodule_newsletter_rel_usergroup_newsletter`
                        (`newsletter`, `userGroup`)
                    VALUES
                        (%s, %s)
                    ', DBPREFIX, $mailID, intval($group)
                )
            );
        }
        if (count($groups) > 0) {
            $delString = implode(',', $groups);

            $query = sprintf('
                DELETE FROM
                    `%smodule_newsletter_rel_usergroup_newsletter`
                WHERE
                    `userGroup` NOT IN (%s)
                AND
                    `newsletter` = %s
                ',
                DBPREFIX,
                $delString,
                $mailID
            );
            $objDatabase->Execute($query);
        } else {
            // no groups were selected -> remove all group associations
            $query = sprintf('
                DELETE FROM
                    `%smodule_newsletter_rel_usergroup_newsletter`
                WHERE
                    `newsletter` = %s
                ',
                DBPREFIX,
                $mailID
            );
            $objDatabase->Execute($query);
        }
    }


    static function _checkUniqueListName($listId, $listName)
    {
        global $objDatabase;

        $result = $objDatabase->SelectLimit("
            SELECT id
              FROM ".DBPREFIX."module_newsletter_category
             WHERE `name`='".$listName."'
               AND `id`!=".$listId, 1);
        if ($result && $result->RecordCount() == 0) return true;
        return false;
    }


    function ConfigHTML()
    {
        global $_ARRAYLANG;

        $this->_objTpl->addBlockfile('NEWSLETTER_SETTINGS_FILE', 'settings_block', 'newsletter_config_html.html');

        $this->_objTpl->setVariable(array(
            'HTML_CODE' => htmlentities($this->_getHTML(), ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_TITLE' => $_ARRAYLANG['TXT_GENERATE_HTML'],
            'TXT_SELECT_ALL' => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_NEWSLETTER_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES'],
            'TXT_NEWSLETTER_INTERFACE' => $_ARRAYLANG['TXT_NEWSLETTER_INTERFACE'],
            'TXT_GENERATE_HTML' => $_ARRAYLANG['TXT_GENERATE_HTML'],
            'TXT_PLACEHOLDER' => $_ARRAYLANG['TXT_PLACEHOLDER'],
            'TXT_CONFIRM_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRMATION_EMAIL'],
            'TXT_ACTIVATE_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_ACTIVATION_EMAIL'],
            'TXT_NOTIFICATION_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_MAIL'],
            'TXT_SYSTEM_SETINGS' => "System",
            'TXT_NEWSLETTER_EMAIL_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES'],
        ));
    }

    function interfaceSettings()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate('jquery');

        $this->_objTpl->addBlockfile('NEWSLETTER_SETTINGS_FILE', 'settings_block', 'newsletter_config_interface.html');

        $recipientAttributeStatus = array();
        if (isset($_POST['interfaceSettings'])) {

            $recipientAttributeStatus = array(
                'recipient_sex'           => array(
                    'active'              => (isset($_POST['recipientSex'])),
                    'required'            => (isset($_POST['requiredSex'])),
                    ),
                'recipient_salutation'    => array(
                    'active'              => (isset($_POST['recipientSalutation'])),
                    'required'            => (isset($_POST['requiredSalutation'])),
                    ),
                'recipient_title'         => array(
                    'active'              => (isset($_POST['recipientTitle'])),
                    'required'            => (isset($_POST['requiredTitle'])),
                    ),
                'recipient_firstname'     => array(
                    'active'              => (isset($_POST['recipientFirstName'])),
                    'required'            => (isset($_POST['requiredFirstName'])),
                    ),
                'recipient_lastname'      => array(
                    'active'              => (isset($_POST['recipientLastName'])),
                    'required'            => (isset($_POST['requiredLastName'])),
                    ),
                'recipient_position'      => array(
                    'active'              => (isset($_POST['recipientPosition'])),
                    'required'            => (isset($_POST['requiredPosition'])),
                    ),
                'recipient_company'       => array(
                    'active'              => (isset($_POST['recipientCompany'])),
                    'required'            => (isset($_POST['requiredCompany'])),
                    ),
                'recipient_industry'      => array(
                    'active'              => (isset($_POST['recipientIndustry'])),
                    'required'            => (isset($_POST['requiredIndustry'])),
                    ),
                'recipient_address'       => array(
                    'active'              => (isset($_POST['recipientAddress'])),
                    'required'            => (isset($_POST['requiredAddress'])),
                    ),
                'recipient_city'          => array(
                    'active'              => (isset($_POST['recipientCity'])),
                    'required'            => (isset($_POST['requiredCity'])),
                    ),
                'recipient_zip'           => array(
                    'active'              => (isset($_POST['recipientZip'])),
                    'required'            => (isset($_POST['requiredZip'])),
                    ),
                'recipient_country'       => array(
                    'active'              => (isset($_POST['recipientCountry'])),
                    'required'            => (isset($_POST['requiredCountry'])),
                    ),
                'recipient_phone'         => array(
                    'active'              => (isset($_POST['recipientPhone'])),
                    'required'            => (isset($_POST['requiredPhone'])),
                    ),
                'recipient_private'       => array(
                    'active'              => (isset($_POST['recipientPrivate'])),
                    'required'            => (isset($_POST['requiredPrivate'])),
                    ),
                'recipient_mobile'        => array(
                    'active'              => (isset($_POST['recipientMobile'])),
                    'required'            => (isset($_POST['requiredMobile'])),
                    ),
                'recipient_fax'           => array(
                    'active'              => (isset($_POST['recipientFax'])),
                    'required'            => (isset($_POST['requiredFax'])),
                    ),
                'recipient_birthday'      => array(
                    'active'              => (isset($_POST['recipientBirthDay'])),
                    'required'            => (isset($_POST['requiredBirthDay'])),
                    ),
                'recipient_website'       => array(
                    'active'              => (isset($_POST['recipientWebsite'])),
                    'required'            => (isset($_POST['requiredWebsite'])),
                    ),
                'captcha'       => array(
                    'active'              => (isset($_POST['captcha'])),
                    'required'            => (isset($_POST['requiredCaptcha'])),
                ),
            );

            $objUpdateStatus = $objDatabase->Execute("UPDATE ".DBPREFIX."module_newsletter_settings
                                                        SET `setvalue`='".json_encode($recipientAttributeStatus)."'
                                                      WHERE `setname` = 'recipient_attribute_status'");

            if ($objUpdateStatus) {
                self::$strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
            } else {
                self::$strErrMessage = $_ARRAYLANG['TXT_DATABASE_ERROR'];
            }
        }

        $objInterface = $objDatabase->Execute('SELECT `setvalue`
                                                FROM `'.DBPREFIX.'module_newsletter_settings`
                                                WHERE `setname` = "recipient_attribute_status"');
        $recipientStatus = json_decode($objInterface->fields['setvalue'], true);

        foreach ($recipientStatus as $attributeName => $recipientStatusArray) {
            $this->_objTpl->setVariable(array(
                 'NEWSLETTER_'.strtoupper($attributeName)                       =>  ($recipientStatusArray['active']) ? 'checked="checked"' : '',
                 'NEWSLETTER_'.strtoupper($attributeName).'_REQUIRED'           =>  ($recipientStatusArray['active'] && $recipientStatusArray['required']) ? 'checked="checked"' : '',
                 'NEWSLETTER_'.strtoupper($attributeName).'_MANTOTRY_DISPLAY'   =>  ($recipientStatusArray['active']) ? 'block' : 'none',
            ));
        }

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES'],
            'TXT_NEWSLETTER_INTERFACE'      => $_ARRAYLANG['TXT_NEWSLETTER_INTERFACE'],
            'TXT_GENERATE_HTML'             => $_ARRAYLANG['TXT_GENERATE_HTML'],
            'TXT_ACTIVATE_MAIL'             => $_ARRAYLANG['TXT_NEWSLETTER_ACTIVATION_EMAIL'],
            'TXT_CONFIRM_MAIL'              => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRMATION_EMAIL'],
            'TXT_NOTIFICATION_MAIL'         => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_MAIL'],
            'TXT_NEWSLETTER_PROFILE_DETAILS' => $_ARRAYLANG['TXT_NEWSLETTER_PROFILE_DETAILS'],
            'TXT_NEWSLETTER_SALUTATION'     => $_ARRAYLANG['TXT_NEWSLETTER_SALUTATION'],
            'TXT_NEWSLETTER_TITLE'          => $_ARRAYLANG['TXT_NEWSLETTER_TITLE'],
            'TXT_NEWSLETTER_POSITION'       => $_ARRAYLANG['TXT_NEWSLETTER_POSITION'],
            'TXT_NEWSLETTER_COMPANY'        => $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'],
            'TXT_NEWSLETTER_INDUSTRY_SECTOR' => $_ARRAYLANG['TXT_NEWSLETTER_INDUSTRY_SECTOR'],
            'TXT_NEWSLETTER_ADDRESS'        => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_NEWSLETTER_PHONE_PRIVATE'  => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_PRIVATE'],
            'TXT_NEWSLETTER_PHONE_MOBILE'   => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_MOBILE'],
            'TXT_NEWSLETTER_FAX'            => $_ARRAYLANG['TXT_NEWSLETTER_FAX'],
            'TXT_NEWSLETTER_WEBSITE'        => $_ARRAYLANG['TXT_NEWSLETTER_WEBSITE'],
            'TXT_NEWSLETTER_CAPTCHA'        => $_ARRAYLANG['TXT_NEWSLETTER_CAPTCHA'],
            'TXT_NEWSLETTER_EMAIL_ADDRESS'  => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'],
            'TXT_NEWSLETTER_WEBSITE'        => $_ARRAYLANG['TXT_NEWSLETTER_WEBSITE'],
            'TXT_NEWSLETTER_SALUTATION'     => $_ARRAYLANG['TXT_NEWSLETTER_SALUTATION'],
            'TXT_NEWSLETTER_TITLE'          => $_ARRAYLANG['TXT_NEWSLETTER_TITLE'],
            'TXT_NEWSLETTER_SEX'            => $_ARRAYLANG['TXT_NEWSLETTER_SEX'],
            'TXT_NEWSLETTER_FEMALE'         => $_ARRAYLANG['TXT_NEWSLETTER_FEMALE'],
            'TXT_NEWSLETTER_MALE'           => $_ARRAYLANG['TXT_NEWSLETTER_MALE'],
            'TXT_NEWSLETTER_LASTNAME'       => $_ARRAYLANG['TXT_NEWSLETTER_LASTNAME'],
            'TXT_NEWSLETTER_FIRSTNAME'      => $_ARRAYLANG['TXT_NEWSLETTER_FIRSTNAME'],
            'TXT_NEWSLETTER_COMPANY'        => $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'],
            'TXT_NEWSLETTER_ADDRESS'        => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_NEWSLETTER_ZIP'            => $_ARRAYLANG['TXT_NEWSLETTER_ZIP'],
            'TXT_NEWSLETTER_CITY'           => $_ARRAYLANG['TXT_NEWSLETTER_CITY'],
            'TXT_NEWSLETTER_COUNTRY'        => $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY'],
            'TXT_NEWSLETTER_PHONE'          => $_ARRAYLANG['TXT_NEWSLETTER_PHONE'],
            'TXT_NEWSLETTER_BIRTHDAY'       => $_ARRAYLANG['TXT_NEWSLETTER_BIRTHDAY'],
            'TXT_SAVE'                      => $_ARRAYLANG['TXT_SAVE'],
            'TXT_ACTIVE'                    => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_NEWSLETTER_MANDATORY_FIELD' => $_ARRAYLANG['TXT_NEWSLETTER_MANDATORY_FIELD'],
            'TXT_NEWSLETTER_EMAIL_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES'],
            'TXT_SYSTEM_SETINGS'             => $_ARRAYLANG['TXT_NEWSLETTER_SYSTEM'],
        ));


    }

    function ConfigDispatch()
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->addBlockfile('NEWSLETTER_SETTINGS_FILE', 'settings_block', 'newsletter_config_dispatch.html');

        if (isset($_POST["update"])) {
            $queryUpdateSetting = '
                UPDATE
                    `'. DBPREFIX .'module_newsletter_settings`
                SET `setvalue` = CASE `setname`
                                 WHEN "sender_mail" THEN "'. contrexx_input2db($_POST['sender_email']) .'"
                                 WHEN "sender_name" THEN "'. contrexx_input2db($_POST['sender_name']) .'"
                                 WHEN "reply_mail" THEN "'. contrexx_input2db($_POST['return_path']) .'"
                                 WHEN "mails_per_run" THEN "'. contrexx_input2int($_POST['mails_per_run']) .'"
                                 WHEN "overview_entries_limit" THEN "'. contrexx_input2int($_POST["overview_entries"]) .'"
                                 WHEN "test_mail" THEN "'. contrexx_input2db($_POST['test_mail']) .'"
                                 WHEN "rejected_mail_operation" THEN "'. contrexx_input2db($_POST['newsletter_rejected_mail_task']) .'"
                                 WHEN "defUnsubscribe" THEN "'. contrexx_input2int($_POST['def_unsubscribe']) .'"
                                 WHEN "notificationSubscribe" THEN "'. contrexx_input2int($_POST["mailSendSubscribe"]) .'"
                                 WHEN "notificationUnsubscribe" THEN "'. contrexx_input2int($_POST["mailSendUnsubscribe"]) .'"
                                 WHEN "statistics" THEN "'. contrexx_input2int($_POST["statistics"]) .'"
                                 WHEN "confirmLinkHour" THEN "'. contrexx_input2int($_POST["confirmLinkHour"]) .'"
                                 WHEN "deliver_user_recipients" THEN "'. contrexx_input2int($_POST['deliver-user-recipients']) .'"
                                 WHEN "deliver_crm_recipients" THEN "'. contrexx_input2int($_POST['deliver-crm-recipients']) .'"
                                 WHEN "use_crm_filter" THEN "'. contrexx_input2int($_POST['use-crm-filter']) .'"
                                 END
                WHERE `setname` IN("sender_mail", "sender_name", "reply_mail", "mails_per_run", "overview_entries_limit", "test_mail", "rejected_mail_operation", "defUnsubscribe", "notificationSubscribe", "notificationUnsubscribe", "statistics", "confirmLinkHour", "deliver_user_recipients", "deliver_crm_recipients", "use_crm_filter")';
            $objDatabase->Execute($queryUpdateSetting);
            if (
                isset($_POST['statistics_drop']) &&
                $_POST['statistics_drop'] == 1
            ) {
                $objDatabase->Execute('
                    DELETE FROM
                        `'. DBPREFIX .'module_newsletter_email_link_feedback`
                ');
            }
        }

        // Load Values
        $objSettings = $objDatabase->Execute("SELECT setname, setvalue FROM ".DBPREFIX."module_newsletter_settings");
        if ($objSettings !== false) {
            while (!$objSettings->EOF) {
                $arrSettings[$objSettings->fields['setname']] = $objSettings->fields['setvalue'];
                $objSettings->MoveNext();
            }
        }
        $this->_objTpl->setVariable(array(
            'TXT_SETTINGS' => $_ARRAYLANG['TXT_SETTINGS'],
            'TXT_NEWSLETTER_SENDER' => $_ARRAYLANG['TXT_NEWSLETTER_SENDER'],
            'TXT_NEWSLETTER_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NAME'],
            'TXT_RETURN_PATH' => $_ARRAYLANG['TXT_RETURN_PATH'],
            'TXT_SEND_LIMIT' => $_ARRAYLANG['TXT_SEND_LIMIT'],
            'TXT_SAVE' => $_ARRAYLANG['TXT_SAVE'],
            'TXT_FILL_OUT_ALL_REQUIRED_FIELDS' => $_ARRAYLANG['TXT_FILL_OUT_ALL_REQUIRED_FIELDS'],
            'TXT_WILDCART_INFOS' => $_ARRAYLANG['TXT_WILDCART_INFOS'],
            'TXT_USER_DATA' => $_ARRAYLANG["TXT_USER_DATA"],
            'TXT_EMAIL_ADDRESS' => $_ARRAYLANG['TXT_EMAIL_ADDRESS'],
            'TXT_FIRSTNAME' => $_ARRAYLANG['TXT_FIRSTNAME'],
            'TXT_NEWSLETTER_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_ZIP' => $_ARRAYLANG['TXT_ZIP'],
            'TXT_CITY' => $_ARRAYLANG['TXT_CITY'],
            'TXT_COUNTRY' => $_ARRAYLANG['TXT_COUNTRY'],
            'TXT_PHONE' => $_ARRAYLANG['TXT_PHONE'],
            'TXT_BIRTHDAY' => $_ARRAYLANG['TXT_BIRTHDAY'],
            'TXT_GENERALLY' => $_ARRAYLANG['TXT_GENERALLY'],
            'TXT_DATE' => $_ARRAYLANG['TXT_DATE'],
            'TXT_NEWSLETTER_CONTENT' => $_ARRAYLANG['TXT_NEWSLETTER_CONTENT'],
            'TXT_CONFIRM_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRMATION_EMAIL'],
            'TXT_NOTIFICATION_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_MAIL'],
            'TXT_ACTIVATE_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_ACTIVATION_EMAIL'],
            'TXT_DISPATCH_SETINGS' => $_ARRAYLANG['TXT_DISPATCH_SETINGS'],
            'TXT_NEWSLETTER_EMAIL_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES'],
            'TXT_GENERATE_HTML' => $_ARRAYLANG['TXT_GENERATE_HTML'],
            'TXT_NEWSLETTER_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES'],
            'TXT_NEWSLETTER_INTERFACE' => $_ARRAYLANG['TXT_NEWSLETTER_INTERFACE'],
            'TXT_TEST_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_TEST_RECIPIENT'],
            'TXT_FAILED' => $_ARRAYLANG['TXT_NEWSLETTER_FAILED'],
            'TXT_NEWSLETTER_INFO_ABOUT_ADMIN_INFORM' => $_ARRAYLANG['TXT_NEWSLETTER_INFO_ABOUT_ADMIN_INFORM'],
            'TXT_NEWSLETTER_OVERVIEW_ENTRIES' => $_ARRAYLANG['TXT_NEWSLETTER_OVERVIEW_ENTRIES'],
            'TXT_NEWSLETTER_REPLY_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_REPLY_ADDRESS'],
            'TXT_SYSTEM_SETINGS' => "System",
            'TXT_NEWSLETTER_DO_NOTING' => $_ARRAYLANG['TXT_NEWSLETTER_DO_NOTING'],
            'TXT_NEWSLETTER_TASK_REJECTED_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_TASK_REJECTED_EMAIL'],
            'TXT_NEWSLETTER_DEACTIVATE_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_DEACTIVATE_EMAIL'],
            'TXT_NEWSLETTER_DELETE_EMAIL_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE_EMAIL_ADDRESS'],
            'TXT_NEWSLETTER_INFORM_ADMIN' => $_ARRAYLANG['TXT_NEWSLETTER_INFORM_ADMIN'],
            'TXT_NEWSLETTER_REJECT_INFO_MAIL_TEXT' => $_ARRAYLANG['TXT_NEWSLETTER_REJECT_INFO_MAIL_TEXT'],
            'TXT_NEWSLETTER_INFO_ABOUT_INFORM_TEXT' => $_ARRAYLANG['TXT_NEWSLETTER_INFO_ABOUT_INFORM_TEXT'],
            'TXT_NEWSLETTER_UNSUBSCRIBE_DEACTIVATE' => $_CORELANG['TXT_DEACTIVATED'],
            'TXT_NEWSLETTER_UNSUBSCRIBE_DELETE'     => $_CORELANG['TXT_DELETED'],
            'TXT_NEWSLETTER_DEF_UNSUBSCRIBE' => $_ARRAYLANG['TXT_STATE_OF_SUBSCRIBED_USER'],
            'TXT_NEWSLETTER_NOTIFICATION_ACTIVATE'   => $_ARRAYLANG['TXT_NEWSLETTER_ACTIVATE'],
            'TXT_NEWSLETTER_NOTIFICATION_DEACTIVATE' => $_ARRAYLANG['TXT_NEWSLETTER_DEACTIVATE'],
            'TXT_NEWSLETTER_SEND_BY_SUBSCRIBE'       => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_SEND_BY_SUBSCRIBE'],
            'TXT_NEWSLETTER_SEND_BY_UNSUBSCRIBE'     => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_SEND_BY_UNSUBSCRIBE'],
            'TXT_NEWSLETTER_STATISTICS' => $_ARRAYLANG['TXT_NEWSLETTER_STATISTICS'],
            'TXT_NEWSLETTER_STATISTICS_TOOLTIP' => $_ARRAYLANG['TXT_NEWSLETTER_STATISTICS_TOOLTIP'],
            'TXT_NEWSLETTER_STATISTICS_DROP' => $_ARRAYLANG['TXT_NEWSLETTER_STATISTICS_DROP'],
            'TXT_NEWSLETTER_GENERAL' => $_ARRAYLANG['TXT_NEWSLETTER_GENERAL'],
            'TXT_NEWSLETTER_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL'],
            'TXT_NEWSLETTER_EMAIL_CAMPAIGNS' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_CAMPAIGNS'],
            'TXT_NEWSLETTER_DEFAULTS_FOR_EMAIL_CAMPAIGNS' => $_ARRAYLANG['TXT_NEWSLETTER_DEFAULTS_FOR_EMAIL_CAMPAIGNS'],
            'TXT_NEWSLETTER_RECIPIENTS' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENTS'],
            'TXT_NEWSLETTER_LISTS' => $_ARRAYLANG['TXT_NEWSLETTER_LISTS'],
            'TXT_NEWSLETTER_DELIVER_USER_RECIPIENTS' => $_ARRAYLANG['TXT_NEWSLETTER_DELIVER_USER_RECIPIENTS'],
            'TXT_NEWSLETTER_DELIVER_CRM_RECIPIENTS' => $_ARRAYLANG['TXT_NEWSLETTER_DELIVER_CRM_RECIPIENTS'],
            'TXT_NEWSLETTER_USE_CRM_FILTER' => $_ARRAYLANG['TXT_NEWSLETTER_USE_CRM_FILTER'],

            'SENDERMAIL_VALUE' => htmlentities(
                $arrSettings['sender_mail'], ENT_QUOTES, CONTREXX_CHARSET),
            'SENDERNAME_VALUE' => htmlentities(
                $arrSettings['sender_name'], ENT_QUOTES, CONTREXX_CHARSET),
            'RETURNPATH_VALUE' => htmlentities(
                $arrSettings['reply_mail'], ENT_QUOTES, CONTREXX_CHARSET),
            'MAILSPERRUN_VALUE' => $arrSettings['mails_per_run'],
            'OVERVIEW_ENTRIES_VALUE' => $arrSettings['overview_entries_limit'],
            'TEST_MAIL_VALUE' => htmlentities(
                $arrSettings['test_mail'], ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_REJECTED_MAIL_IGNORE' =>
                ($arrSettings['rejected_mail_operation'] == 'ignore'
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_REJECTED_MAIL_DEACTIVATE' =>
                ($arrSettings['rejected_mail_operation'] == 'deactivate'
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_REJECTED_MAIL_DELETE' =>
                ($arrSettings['rejected_mail_operation'] == 'delete'
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_REJECTED_MAIL_INFORM' =>
                ($arrSettings['rejected_mail_operation'] == 'inform'
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_UNSUBSCRIBE_DELETE_ON' =>
                ($arrSettings['defUnsubscribe'] == 1
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_UNSUBSCRIBE_DEACTIVATE_ON' =>
                ($arrSettings['defUnsubscribe'] != 1
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_SEND_BY_SUBSCRIBE_ON' =>
                ($arrSettings['notificationSubscribe'] == 1
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_SEND_BY_SUBSCRIBE_OFF' =>
                ($arrSettings['notificationSubscribe'] != 1
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_SEND_BY_UNSUBSCRIBE_ON' =>
                ($arrSettings['notificationUnsubscribe'] == 1
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_SEND_BY_UNSUBSCRIBE_OFF' =>
                ($arrSettings['notificationUnsubscribe'] != 1
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_STATISTICS_ON' =>
                ($arrSettings['statistics'] != 0
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_STATISTICS_OFF' =>
                ($arrSettings['statistics'] == 0
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_DELIVER_USER_RECIPIENTS_ON' =>
                ($arrSettings['deliver_user_recipients'] != 0
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_DELIVER_USER_RECIPIENTS_OFF' =>
                ($arrSettings['deliver_user_recipients'] == 0
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_DELIVER_CRM_RECIPIENTS_ON' =>
                ($arrSettings['deliver_crm_recipients'] != 0
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_DELIVER_CRM_RECIPIENTS_OFF' =>
                ($arrSettings['deliver_crm_recipients'] == 0
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_USE_CRM_FILTER_ON' =>
                ($arrSettings['use_crm_filter'] != 0
                    ? 'checked="checked"' : ''),
            'NEWSLETTER_USE_CRM_FILTER_OFF' =>
                ($arrSettings['use_crm_filter'] == 0
                    ? 'checked="checked"' : ''),
            'TXT_NEWSLETTER_CONFIRM_LINK_HOUR'   => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_LINK_VALIDITY_HOUR'],
            'NEWSLETTER_CONFIRM_LINK_HOUR_VALUE' => contrexx_raw2xhtml($arrSettings['confirmLinkHour']),
        ));
    }


    function _templates()
    {
        global $objDatabase, $_ARRAYLANG;

        $rowNr = 0;
        $this->_objTpl->addBlockfile('NEWSLETTER_SETTINGS_FILE', 'settings_block', 'module_newsletter_templates.html');
        $this->_objTpl->setVariable('TXT_TITLE', $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES']);

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_CANNOT_UNDO_OPERATION' => $_ARRAYLANG['TXT_NEWSLETTER_CANNOT_UNDO_OPERATION'],
            'TXT_NEWSLETTER_CONFIRM_DELETE_TEMPLATE' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_DELETE_TEMPLATE'],
            'TXT_NEWSLETTER_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES'],
            'TXT_NEWSLETTER_ID_UC' => $_ARRAYLANG['TXT_NEWSLETTER_ID_UC'],
            'TXT_NEWSLETTER_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NAME'],
            'TXT_NEWSLETTER_DESCRIPTION' => $_ARRAYLANG['TXT_NEWSLETTER_DESCRIPTION'],
            'TXT_NEWSLETTER_TYPE' => $_ARRAYLANG['TXT_NEWSLETTER_TYPE'],
            'TXT_NEWSLETTER_FUNCTIONS' => $_ARRAYLANG['TXT_NEWSLETTER_FUNCTIONS'],
            'TXT_TEMPLATE_ADD_NEW_TEMPLATE' => $_ARRAYLANG['TXT_TEMPLATE_ADD_NEW_TEMPLATE'],
            'TXT_CONFIRM_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRMATION_EMAIL'],
            'TXT_ACTIVATE_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_ACTIVATION_EMAIL'],
            'TXT_GENERATE_HTML' => $_ARRAYLANG['TXT_GENERATE_HTML'],
            'TXT_SYSTEM_SETINGS' => "System",
            'TXT_NOTIFICATION_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_MAIL'],
            'TXT_NEWSLETTER_INTERFACE' => $_ARRAYLANG['TXT_NEWSLETTER_INTERFACE'],
            'TXT_NEWSLETTER_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES'],
            'TXT_NEWSLETTER_EMAIL_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES'],
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_MODIFY_TEMPLATE' => $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_TEMPLATE'],
            'TXT_NEWSLETTER_DELETE_TEMPLATE' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE_TEMPLATE']
        ));

        $objTemplate = $objDatabase->Execute("SELECT id, name, required, description, type FROM ".DBPREFIX."module_newsletter_template ORDER BY id DESC");
        if ($objTemplate !== false) {
            while (!$objTemplate->EOF) {
                if ($objTemplate->fields['required'] == 0) {
                    $this->_objTpl->touchBlock('newsletter_template_delete');
                    $this->_objTpl->hideBlock('newsletter_templalte_spacer');
                } else {
                    $this->_objTpl->hideBlock('newsletter_template_delete');
                    $this->_objTpl->touchBlock('newsletter_templalte_spacer');
                }

                switch ($objTemplate->fields['type']) {
                    case 'e-mail':
                        $type = $_ARRAYLANG['TXT_NEWSLETTER_TYPE_EMAIL'];
                        break;
                    case 'news':
                        $type = $_ARRAYLANG['TXT_NEWSLETTER_TYPE_NEWS_IMPORT'];
                        break;
                }

                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_TEMPLATE_ROW_CLASS' => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'NEWSLETTER_TEMPLATE_ID' => $objTemplate->fields['id'],
                    'NEWSLETTER_TEMPLATE_NAME' => htmlentities($objTemplate->fields['name'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_TEMPLATE_NAME_JS' => htmlentities(addslashes($objTemplate->fields['name']), ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_TEMPLATE_DESCRIPTION' => htmlentities($objTemplate->fields['description'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_TEMPLATE_TYPE' => $type
                ));

                $rowNr++;
                $this->_objTpl->parse("templates_row");
                $objTemplate->MoveNext();
            }
        }
    }


    function _updateTemplate($id, $name, $description, $html, $type)
    {
        global $objDatabase;
        if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_newsletter_template SET name='".addslashes($name)."', description='".addslashes($description)."', html='".addslashes($html)."', type='".$type."' WHERE id=".$id) !== false) {
            return true;
        } else {
             return false;
        }
    }


    function _addTemplate($name, $description, $html, $type)
    {
        global $objDatabase;
        if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_newsletter_template (`name`, `description`, `html`, `type`) VALUES ('".addslashes($name)."', '".addslashes($description)."', '".addslashes($html)."', '".$type."')") !== false) {
            return true;
        } else {
             return false;
        }
    }


    function _editTemplate()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate('cx');

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $name = '';
        $description = '';
        $type = '';
        $html = "<html>\n<head>\n<title>[[subject]]</title>\n</head>\n<body>\n[[content]]\n<br />\n<br />\n[[profile_setup]]\n[[unsubscribe]]\n</body>\n</html>";
        $saveStatus = true;

        if (isset($_POST['newsletter_template_save'])) {
            if (!empty($_POST['template_edit_name'])) {
                $name = contrexx_stripslashes($_POST['template_edit_name']);
            } else {
                $saveStatus = false;
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_DEFINE_TEMPLATE_NAME']."<br />";
            }

            if (isset($_POST['template_edit_description'])) {
                $description = contrexx_stripslashes($_POST['template_edit_description']);
            }

            if (isset($_POST['template_edit_type'])) {
                $type = contrexx_stripslashes($_POST['template_edit_type']);
            }

            if (isset($_POST['template_edit_html'])) {
                $html = contrexx_stripslashes($_POST['template_edit_html']);
            }
            $arrContentMatches = array();
            if (preg_match_all('/\[\[content\]\]/', $html, $arrContentMatches) ) {
                if (count($arrContentMatches[0]) > 1) {
                    $saveStatus = false;
                    self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_MAX_CONTENT_PLACEHOLDER_HTML_MSG']."<br />";
                }
            } elseif ($type != 'news') {
                $saveStatus = false;
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_MIN_CONTENT_PLACEHOLDER_HTML_MSG']."<br />";
            }

            if ($saveStatus) {
                $objTemplate = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_template WHERE id!=".$id." AND name='".addslashes($name)."' AND type='".$type."'", 1);
                if ($objTemplate !== false && $objTemplate->RecordCount() == 0) {
                    if ($id > 0) {
                        $this->_updateTemplate($id, $name, $description, $html, $type);
                    } else {
                        $this->_addTemplate($name, $description, $html, $type);
                    }

                    return $this->_templates();
                } else {
                    self::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_DUPLICATE_LIST_NAME_MSG'];
                }
            }
        } elseif ($id > 0) {
            $objTemplate = $objDatabase->SelectLimit("SELECT id, name, description, html, type FROM ".DBPREFIX."module_newsletter_template WHERE id=".$id, 1);
            if ($objTemplate !== false && $objTemplate->RecordCount() == 1) {
                $name = $objTemplate->fields['name'];
                $description = $objTemplate->fields['description'];
                $type = $objTemplate->fields['type'];
                $html = $objTemplate->fields['html'];
            }
        }

        switch ($type) {
            case 'e-mail':
                $newsImportDirectoryDisplay = 'none';
                $emailDirectoryDisplay = 'table-row-group';
                break;
            case 'news':
                $newsImportDirectoryDisplay = 'table-row-group';
                $emailDirectoryDisplay = 'none';
                break;
            default:
                $newsImportDirectoryDisplay = 'none';
                $emailDirectoryDisplay = 'table-row-group';
                break;
        }

        $typeOps = "<option value=\"e-mail\"".($type=='e-mail' ? " selected" : "").">".$_ARRAYLANG['TXT_NEWSLETTER_TYPE_EMAIL']."</option>\n";
        $typeOps .= "<option value=\"news\"".($type=='news' ? " selected" : "").">".$_ARRAYLANG['TXT_NEWSLETTER_TYPE_NEWS_IMPORT']."</option>\n";

        $this->_objTpl->addBlockfile('NEWSLETTER_SETTINGS_FILE', 'settings_block', 'module_newsletter_template_edit.html');

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_PLACEHOLDER_DIRECTORY' => $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDER_DIRECTORY'],
            'TXT_NEWSLETTER_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NAME'],
            'TXT_NEWSLETTER_TYPE' => $_ARRAYLANG['TXT_NEWSLETTER_TYPE'],
            'TXT_NEWSLETTER_DESCRIPTION' => $_ARRAYLANG['TXT_NEWSLETTER_DESCRIPTION'],
            'TXT_NEWSLETTER_HTML_TEMPLATE' => $_ARRAYLANG['TXT_NEWSLETTER_HTML_TEMPLATE'],
            'TXT_NEWSLETTER_TEXT_TEMPLATE' => $_ARRAYLANG['TXT_NEWSLETTER_TEXT_TEMPLATE'],
            'TXT_NEWSLETTER_BACK' => $_ARRAYLANG['TXT_NEWSLETTER_BACK'],
            'TXT_NEWSLETTER_SAVE' => $_ARRAYLANG['TXT_NEWSLETTER_SAVE'],
            'TXT_NEWSLETTER_USER_DATA' => $_ARRAYLANG['TXT_NEWSLETTER_USER_DATA'],
            'TXT_NEWSLETTER_EMAIL_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'],
            'TXT_NEWSLETTER_URI' => $_ARRAYLANG['TXT_NEWSLETTER_URI'],
            'TXT_NEWSLETTER_SEX' => $_ARRAYLANG['TXT_NEWSLETTER_SEX'],
            'TXT_NEWSLETTER_TITLE' => $_ARRAYLANG['TXT_NEWSLETTER_TITLE'],
            'TXT_NEWSLETTER_LASTNAME' => $_ARRAYLANG['TXT_NEWSLETTER_LASTNAME'],
            'TXT_NEWSLETTER_FIRSTNAME' => $_ARRAYLANG['TXT_NEWSLETTER_FIRSTNAME'],
            'TXT_NEWSLETTER_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_NEWSLETTER_ZIP' => $_ARRAYLANG['TXT_NEWSLETTER_ZIP'],
            'TXT_NEWSLETTER_CITY' => $_ARRAYLANG['TXT_NEWSLETTER_CITY'],
            'TXT_NEWSLETTER_COUNTRY' => $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY'],
            'TXT_NEWSLETTER_PHONE' => $_ARRAYLANG['TXT_NEWSLETTER_PHONE'],
            'TXT_NEWSLETTER_BIRTHDAY' => $_ARRAYLANG['TXT_NEWSLETTER_BIRTHDAY'],
            'TXT_NEWSLETTER_GENERAL' => $_ARRAYLANG['TXT_NEWSLETTER_GENERAL'],
            'TXT_NEWSLETTER_CONTENT' => $_ARRAYLANG['TXT_NEWSLETTER_CONTENT'],
            'TXT_NEWSLETTER_PROFILE_SETUP' => $_ARRAYLANG['TXT_NEWSLETTER_PROFILE_SETUP'],
            'TXT_NEWSLETTER_UNSUBSCRIBE' => $_ARRAYLANG['TXT_NEWSLETTER_UNSUBSCRIBE'],
            'TXT_NEWSLETTER_PLACEHOLDER_NOT_ON_BROWSER_VIEW' => $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDER_NOT_ON_BROWSER_VIEW'],
            'TXT_NEWSLETTER_PLACEHOLDER_NOT_FOR_CRM' => $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDER_NOT_FOR_CRM'],
            'TXT_NEWSLETTER_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_DATE'],
            'TXT_NEWSLETTER_DISPLAY_IN_BROWSER_LINK' => $_ARRAYLANG['TXT_NEWSLETTER_DISPLAY_IN_BROWSER_LINK'],
            'TXT_NEWSLETTER_SUBJECT' => $_ARRAYLANG['TXT_NEWSLETTER_SUBJECT'],
            'TXT_NEWSLETTER_NEWS_IMPORT' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_IMPORT'],
            'TXT_NEWSLETTER_NEWS_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_DATE'],
            'TXT_NEWSLETTER_NEWS_LONG_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_LONG_DATE'],
            'TXT_NEWSLETTER_NEWS_TITLE' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_TITLE'],
            'TXT_NEWSLETTER_NEWS_URL' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_URL'],
            'TXT_NEWSLETTER_NEWS_IMAGE_PATH' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_IMAGE_PATH'],
            'TXT_NEWSLETTER_NEWS_TEASER_TEXT' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_TEASER_TEXT'],
            'TXT_NEWSLETTER_NEWS_TEXT' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_TEXT'],
            'TXT_NEWSLETTER_NEWS_AUTHOR' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_AUTHOR'],
            'TXT_NEWSLETTER_NEWS_TYPE_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_TYPE_NAME'],
            'TXT_NEWSLETTER_NEWS_CATEGORY_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_CATEGORY_NAME'],
            'TXT_CONFIRM_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRMATION_EMAIL'],
            'TXT_ACTIVATE_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_ACTIVATION_EMAIL'],
            'TXT_GENERATE_HTML' => $_ARRAYLANG['TXT_GENERATE_HTML'],
            'TXT_SYSTEM_SETINGS' => "System",
            'TXT_NOTIFICATION_MAIL' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_MAIL'],
            'TXT_NEWSLETTER_INTERFACE' => $_ARRAYLANG['TXT_NEWSLETTER_INTERFACE'],
            'TXT_NEWSLETTER_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATES'],
            'TXT_NEWSLETTER_EMAIL_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES'],
        ));

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_TEMPLATE_ID' => $id,
            'NEWSLETTER_TEMPLATE_NAME' => htmlentities($name, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_TEMPLATE_DESCRIPTION' => htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_TEMPLATE_TYPE' => $type,
            'NEWSLETTER_TEMPLATE_HTML' => new \Cx\Core\Wysiwyg\Wysiwyg('template_edit_html', contrexx_raw2xhtml($html), 'fullpage'),
            'NEWSLETTER_TEMPLATE_TITLE_TEXT' => $id > 0 ? $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_TEMPLATE'] : $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATE_ADD'],
            'NEWSLETTER_TEMPLATE_TYPE_MENU' => $typeOps,
            'NEWSLETTER_TEMPLATE_NEWS_IMPORT_DIRECTORY_DISPLAY' => $newsImportDirectoryDisplay,
            'NEWSLETTER_TEMPLATE_NEWS_EMAIL_DIRECTORY_DISPLAY' => $emailDirectoryDisplay
        ));
        return true;
    }


    function delTemplate()
    {
        global $objDatabase, $_ARRAYLANG;

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            $objResult = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter WHERE template=".$id, 1);
            if ($objResult !== false) {
                if ($objResult->RecordCount() == 1) {
                    self::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATE_STILL_IN_USE'];
                    return false;
                } else {
                    if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_template WHERE required=0 AND id=".$id) !== false) {
                        self::$strOkMessage = $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATE_DELETED'];
                        return true;
                    }
                }
            }
        }

        self::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_TEMPLATE_DELETE_ERROR'];
        return false;
    }

    /**
     * Parse Settings E-mail templates section
     */
    function emailTemplates()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $this->_objTpl->addBlockfile('NEWSLETTER_SETTINGS_FILE', 'settings_block', 'newsletter_config_email_templates.html');
        $this->_objTpl->setVariable('TXT_TITLE', $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES']);

        $_REQUEST['active_tab'] = 1;
        if (   isset($_REQUEST['act'])
            && $_REQUEST['act'] == 'mailtemplate_edit'
        ) {
            $_REQUEST['active_tab'] = 2;
        }
        \Cx\Core\MailTemplate\Controller\MailTemplate::deleteTemplate('Newsletter');
        // If there is anything to be stored, and if that fails, return to
        // the edit view in order to save the posted form content
        $resultStore = \Cx\Core\MailTemplate\Controller\MailTemplate::storeFromPost('Newsletter');
        if ($resultStore === false) {
            $_REQUEST['active_tab'] = 2;
        }
        $objTemplate = null;
        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            $_CORELANG['TXT_CORE_MAILTEMPLATES'],
            \Cx\Core\MailTemplate\Controller\MailTemplate::overview('Newsletter', 'config')->get()
        );
        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            (empty($_REQUEST['key'])
              ? $_CORELANG['TXT_CORE_MAILTEMPLATE_ADD']
              : $_CORELANG['TXT_CORE_MAILTEMPLATE_EDIT']),
            \Cx\Core\MailTemplate\Controller\MailTemplate::edit('Newsletter')->get()
        );
        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDERS'],
            $this->getNewsletterPlaceHoldersList()
        );
        $this->_objTpl->setVariable(array(
            'NEWSLETTER_MAIL_TEMPLATE_SETTINGS'  => $objTemplate->get(),
            'TXT_NEWSLETTER_EMAIL_TEMPLATES' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_TEMPLATES'],
        ));
    }

    /**
     * Get available placeholders in newsletter notification mails
     *
     * @return string Newsletter placehodlers list
     */
    public function getNewsletterPlaceHoldersList()
    {
        global $_ARRAYLANG;

        $objTemplate = new \Cx\Core\Html\Sigma(\Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath().'/Newsletter/View/Template/Backend');
        $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $objTemplate->loadTemplateFile('module_newsletter_config_placeholders.html');

        $objTemplate->setVariable(array(
            'TXT_NEWSLETTER_PLACEHOLDERS'        => $_ARRAYLANG['TXT_NEWSLETTER_PLACEHOLDERS'],
            'TXT_NEWSLETTER_GENERAL'             => $_ARRAYLANG['TXT_NEWSLETTER_GENERAL'],
            'TXT_NEWSLETTER_USER_TITLE'          => $_ARRAYLANG['TXT_TITLE'],
            'TXT_NEWSLETTER_USER_SEX'            => $_ARRAYLANG['TXT_NEWSLETTER_SEX'],
            'TXT_NEWSLETTER_USER_FIRSTNAME'      => $_ARRAYLANG['TXT_FIRSTNAME'],
            'TXT_NEWSLETTER_USER_LASTNAME'       => $_ARRAYLANG['TXT_LASTNAME'],
            'TXT_NEWSLETTER_USER_EMAIL'          => $_ARRAYLANG['TXT_EMAIL'],
            'TXT_NEWSLETTER_DOMAIN_URL'          => $_ARRAYLANG['TXT_NEWSLETTER_URL'],
            'TXT_NEWSLETTER_CURRENT_DATE'        => $_ARRAYLANG['TXT_DATE'],
            'TXT_NEWSLETTER_CONFIRM_CODE'        => $_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_CODE'],
            'TXT_NEWSLETTER_NOTIFICATION_ACTION' => $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_ACTION'],
            'TXT_NEWSLETTER_SUBJECT'             => $_ARRAYLANG['TXT_NEWSLETTER_SUBJECT'],
            'TXT_NEWSLETTER_USER_EDIT_LINK'      => $_ARRAYLANG['TXT_NEWSLETTER_USER_EDIT_LINK'],
            'TXT_NEWSLETTER_EMAIL_KEY'           => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_KEY'],
            'TXT_NEWSLETTER_EMAIL_CONFIRM_ACTION'      => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_CONFIRM_ACTION'],
        ));
        return $objTemplate->get();
    }

    /**
     * Show the mail send page
     */
    function _sendMailPage()
    {
        global $_ARRAYLANG;

        \JS::activate('cx');

        if (isset($_POST['newsletter_mail_edit'])) {
            return $this->_editMail();
        } elseif (!isset($_REQUEST['id'])) {
            return $this->_mails();
        }

        $mailId = intval($_REQUEST['id']);
        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_SEND_EMAIL'];

        $this->_objTpl->loadTemplateFile('module_newsletter_mail_send.html');

        if (isset($_POST['newsletter_mail_send_test'])) {
            $status = $this->_sendTestMail($mailId);
        } else {
            $status = true;
        }

        if ((isset($_GET['testSent']) || isset($_POST['test_sent'])) && $status) {
            $this->_objTpl->setVariable(array(
                'TXT_NEWSLETTER_SEND_TESTMAIL_FIRST' => '',
                'NEWSLETTER_TESTMAIL_SENT2' => 'test_sent'
            ));
            $this->_objTpl->touchBlock("bulkSend");
        } else {
            $this->_objTpl->setVariable(array(
                "NEWSLETTER_TESTMAIL_SENT" => "&amp;testSent=1",
                'TXT_NEWSLETTER_SEND_TESTMAIL_FIRST' => $_ARRAYLANG['TXT_NEWSLETTER_SEND_TESTMAIL_FIRST']
            ));
            $this->_objTpl->hideBlock("bulkSend");
        }

        $arrSettings = $this->_getSettings();
        $testmail = $arrSettings['test_mail']['setvalue'];

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_EMAIL_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'],
            'TXT_NEWSLETTER_SEND' => $_ARRAYLANG['TXT_NEWSLETTER_SEND'],
            'TXT_SEND_TEST_EMAIL' => $_ARRAYLANG['TXT_SEND_TEST_EMAIL'],
            'TXT_NEWSLETTER_MODIFY_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_EMAIL'],
            'TXT_NEWSLETTER_NOTICE_TESTMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_NOTICE_TESTMAIL'],
            'TXT_NEWSLETTER_NOTICE' => $_ARRAYLANG['TXT_NEWSLETTER_NOTICE'],
        ));

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_MAIL_ID' => $mailId,
            'NEWSLETTER_MAIL_TEST_EMAIL' => $testmail
        ));

        if ($status) {
            $mailRecipientCount = $this->_getMailRecipientCount($mailId);
            if ($mailRecipientCount > 0) {
                $this->_sendMail();
                $this->_objTpl->touchBlock('newsletter_mail_send_status');
                $this->_objTpl->hideBlock('newsletter_mail_list_required');
            } else {
                $this->_objTpl->setVariable(array(
                    'TXT_NEWSLETTER_MAIL_LIST_REQUIRED_TXT' => $_ARRAYLANG['TXT_CATEGORY_ERROR']
                ));

                $this->_objTpl->touchBlock('newsletter_mail_list_required');
                $this->_objTpl->hideBlock('newsletter_mail_send_status');
            }
        }
        return true;
    }


    function _sendTestMail($mailId)
    {
        global $_ARRAYLANG;

        $objValidator = new \FWValidator();

        if (!empty($_POST['newsletter_test_mail']) && $objValidator->isEmail($_POST['newsletter_test_mail'])) {
            if ($this->SendEmail(0, $mailId, $_POST['newsletter_test_mail'], 0, self::USER_TYPE_ACCESS, false) !== false) {
                self::$strOkMessage = str_replace("%s", $_POST["newsletter_test_mail"], $_ARRAYLANG['TXT_TESTMAIL_SEND_SUCCESSFUL']);
                return true;
            } else {
                self::$strErrMessage .= $_ARRAYLANG['TXT_SENDING_MESSAGE_ERROR'];
                return false;
            }
        } else {
            self::$strErrMessage = $_ARRAYLANG['TXT_INVALID_EMAIL_ADDRESS'];
            return false;
        }
    }


    private function _getMailRecipientCount($mailId = null, $limit = 0, $pos = 0)
    {
        global $objDatabase;

        $count = empty($mailId) ? array() : 0;

        $objResult = $objDatabase->Execute("
            SELECT `id`, `tmp_copy`
            FROM   `".DBPREFIX."module_newsletter`
            ".(!empty($mailId) ? "WHERE `id` = ".$mailId : '')."
            ORDER BY status, id DESC
            ".($limit ? "LIMIT $pos, $limit" : ''));
        if ($objResult !== false) {
            if (empty($mailId)) {
                $count = $this->getFinalMailRecipientCount();
                while (!$objResult->EOF) {
                    if (!$objResult->fields['tmp_copy']) {
                        $count[$objResult->fields['id']] = $this->getCurrentMailRecipientCount($objResult->fields['id']);
                    }
                    $objResult->MoveNext();
                }
            } else {
                if ($objResult->fields['tmp_copy']) {
                    $count = $this->getFinalMailRecipientCount($mailId);
                } else {
                    $count = $this->getCurrentMailRecipientCount($mailId);
                }
            }

        }

        return $count;
    }


    /**
     * @todo I think this should be rewritten too
     */
    private function getFinalMailRecipientCount($mailId = null)
    {
        global $objDatabase;

        $count = empty($mailId) ? array() : 0;

        $objResult = $objDatabase->Execute("
            SELECT
                `id`,
                `recipient_count`
            FROM
                `".DBPREFIX."module_newsletter`
            ".(!empty($mailId) ? "WHERE `id` = ".$mailId : ''));
        if ($objResult !== false && $objResult->RecordCount() > 0) {
            if (empty($mailId)) {
                while (!$objResult->EOF) {
                    $count[$objResult->fields['id']] = $objResult->fields['recipient_count'];
                    $objResult->MoveNext();
                }
            } else {
                $count = $objResult->fields['recipient_count'];

            }
        }

        return $count;
    }


    /**
     * Return the recipient count of the emails
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
     * @param       int $mailId
     * @return      int
     */
    private function getCurrentMailRecipientCount($mailId)
    {
        global $objDatabase;

        $query = 'SELECT COUNT(*) AS `recipientCount` FROM ('.$this->getMailRecipientQuery($mailId, false).') AS `subquery`';
        $objResult = $objDatabase->Execute($query);
        if ($objResult && $objResult->RecordCount() == 1) {
            return intval($objResult->fields['recipientCount']);
        }
        return 0;
    }

    /**
     * Return the recipient SQL query for a specific e-mail campaign
     *
     * @param   int $mailId The ID of the e-mail campaign to return the SQL query to fetch the recipients from
     * @return  string  SQL query to fetch the recipients of the specified e-mail campaign
     * @todo    Move the query parts of $accessUserRecipientsQuery and &userGroupRecipientsQuery into a separate
     *          method as they are almost identical except for different table aliases that have to be used.
     */
    protected function getMailRecipientQuery($mailId, $distinctByType = true) {
        // fetch CRM membership filter
        $crmMembershipFilter = $this->emailEditGetCrmMembershipFilter($mailId);

        # case 1: crm membership filter include is set -> do not include native newsletter recipients
        # case 2: crm membership filter exclude is set -> include native newsletter recipients
        # case 3: crm membership filter include & exclude are set -> do not include native newsletter recipients

        // select recipients based on access users
        $accessUserRecipientsQuery = 'SELECT `email` '.($distinctByType ? ', "'.self::USER_TYPE_ACCESS.'" AS `type` ' : '').

                                // select Access User
                                'FROM `%1$saccess_users` AS `cu` '.

                                // join with the subscriebed Newsletter Lists
                                'INNER JOIN `%1$smodule_newsletter_access_user` AS `cnu`
                                        ON `cnu`.`accessUserID`=`cu`.`id` '.

                                // join with the selected e-mail campaign lists
                                'INNER JOIN `%1$smodule_newsletter_rel_cat_news` AS `crn`
                                        ON `cnu`.`newsletterCategoryID`=`crn`.`category` '.

                                // optionally, filter users by CRM membership...
                                (!($crmMembershipFilter['include'] || $crmMembershipFilter['exclude']) ? '' :

                                    // join with CRM Person (contact_type = 2)
                                    'INNER JOIN `%1$smodule_crm_contacts` AS `ccrm_contact`
                                            ON `ccrm_contact`.`user_account` = `cu`.`id`
                                           AND `ccrm_contact`.`contact_type` = 2 '.

                                    // join with CRM Company (contact_type = 1)
                                    'LEFT JOIN `%1$smodule_crm_contacts` AS `ccrm_company`
                                            ON `ccrm_company`.`id` = `ccrm_contact`.`contact_customer`
                                           AND `ccrm_company`.`contact_type` = 1 '.

                                    // only select users of which the associated CRM Company is not a part of the selected CRM membership
                                    (!$crmMembershipFilter['exclude'] ? '' :
                                        'AND `ccrm_company`.`id` NOT IN (
                                                SELECT `ccrm_company_membership_exclude`.`contact_id`
                                                  FROM `%1$smodule_crm_customer_membership` AS `ccrm_company_membership_exclude` 
                                                 WHERE `ccrm_company_membership_exclude`.`membership_id` IN ('.join(',', $crmMembershipFilter['exclude']).')) ').

                                    (!$crmMembershipFilter['include'] ? '' :
                                        // join the CRM Memberships of the CRM Person
                                        'LEFT JOIN `%1$smodule_crm_customer_membership` AS `ccrm_membership_include`
                                                ON `ccrm_membership_include`.`contact_id` = `ccrm_contact`.`id` '.

                                        // join the CRM Memberships of the CRM Company
                                        'LEFT JOIN `%1$smodule_crm_customer_membership` AS `ccrm_company_membership_include`
                                                ON `ccrm_company_membership_include`.`contact_id` = `ccrm_company`.`id` ')).

                                // filter by selected e-mail campaign
                                'WHERE `crn`.`newsletter`=%2$s '.

                                // select only active Acess Users
                                'AND `cu`.`active` = 1 '.

                                // only select users of which the associated CRM Person or CRM Company has the selected CRM membership
                                (!$crmMembershipFilter['include'] ? '' :
                                'AND (
                                         `ccrm_membership_include`.`membership_id` IN ('.join(',', $crmMembershipFilter['include']).')
                                      OR `ccrm_company_membership_include`.`membership_id` IN ('.join(',', $crmMembershipFilter['include']).')) ').

                                // only select users of which the associated CRM Person is not a part of the selected CRM membership
                                (!$crmMembershipFilter['exclude'] ? '' :
                                'AND `ccrm_contact`.`id` NOT IN (
                                    SELECT `ccrm_membership_exclude`.`contact_id`
                                      FROM `%1$smodule_crm_customer_membership` AS `ccrm_membership_exclude` 
                                     WHERE `ccrm_membership_exclude`.`membership_id` IN ('.join(',', $crmMembershipFilter['exclude']).'))');

        // select recipients based on selected newsletter lists
        if ($crmMembershipFilter['include']) {
            $nativeRecipientsQuery = '';
        } else {
            $nativeRecipientsQuery = 'UNION DISTINCT
                        SELECT `email`'.($distinctByType ? ', "'.self::USER_TYPE_NEWSLETTER.'" AS `type`' : '').'
                          FROM `%1$smodule_newsletter_user` AS `nu`
                    INNER JOIN `%1$smodule_newsletter_rel_user_cat` AS `rc`
                            ON `rc`.`user`=`nu`.`id`
                    INNER JOIN `%1$smodule_newsletter_rel_cat_news` AS `nrn`
                            ON `nrn`.`category`=`rc`.`category`
                         WHERE `nrn`.`newsletter`=%2$s
                           AND `nu`.`status` = 1';
        }

        // select recipients based on selected user groups
        $userGroupRecipientsQuery = 'UNION DISTINCT
                                SELECT `email`'.($distinctByType ? ', "'.self::USER_TYPE_CORE.'" AS `type`' : '').
                                // select Access User
                                'FROM `%1$saccess_users` AS `au` '.

                                // join with the associated User Groups
                                'INNER JOIN `%1$saccess_rel_user_group` AS `rg`
                                        ON `rg`.`user_id`=`au`.`id` '.

                                // join with the selected User Groups of the e-mail campaign
                                'INNER JOIN `%1$smodule_newsletter_rel_usergroup_newsletter` AS `arn`
                                        ON `arn`.`userGroup`=`rg`.`group_id` '.

                                // optionally, filter users by CRM membership...
                                (!($crmMembershipFilter['include'] || $crmMembershipFilter['exclude']) ? '' :

                                    // join with CRM Person (contact_type = 2)
                                    'INNER JOIN `%1$smodule_crm_contacts` AS `acrm_contact`
                                            ON `acrm_contact`.`user_account` = `au`.`id`
                                           AND `acrm_contact`.`contact_type` = 2 '.

                                    // join with CRM Company (contact_type = 1)
                                    'LEFT JOIN `%1$smodule_crm_contacts` AS `acrm_company`
                                            ON `acrm_company`.`id` = `acrm_contact`.`contact_customer`
                                           AND `acrm_company`.`contact_type` = 1 '.

                                    // only select users of which the associated CRM Company is not a part of the selected CRM membership
                                    (!$crmMembershipFilter['exclude'] ? '' :
                                        'AND `acrm_company`.`id` NOT IN (
                                                SELECT `acrm_company_membership_exclude`.`contact_id`
                                                  FROM `%1$smodule_crm_customer_membership` AS `acrm_company_membership_exclude` 
                                                 WHERE `acrm_company_membership_exclude`.`membership_id` IN ('.join(',', $crmMembershipFilter['exclude']).')) ').

                                    (!$crmMembershipFilter['include'] ? '' :
                                        // join the CRM Memberships of the CRM Person
                                        'LEFT JOIN `%1$smodule_crm_customer_membership` AS `acrm_membership_include`
                                                ON `acrm_membership_include`.`contact_id` = `acrm_contact`.`id` '.

                                        // join the CRM Memberships of the CRM Company
                                        'LEFT JOIN `%1$smodule_crm_customer_membership` AS `acrm_company_membership_include`
                                                ON `acrm_company_membership_include`.`contact_id` = `acrm_company`.`id` ')).

                                // filter by selected e-mail campaign
                                'WHERE `arn`.`newsletter`=%2$s '.

                                // select only active Acess Users
                                'AND `au`.`active` = 1 '.

                                // only select users of which the associated CRM Person or CRM Company has the selected CRM membership
                                (!$crmMembershipFilter['include'] ? '' :
                                'AND (
                                         `acrm_membership_include`.`membership_id` IN ('.join(',', $crmMembershipFilter['include']).')
                                      OR `acrm_company_membership_include`.`membership_id` IN ('.join(',', $crmMembershipFilter['include']).')) ').

                                // only select users of which the associated CRM Person is not a part of the selected CRM membership
                                (!$crmMembershipFilter['exclude'] ? '' :
                                'AND `acrm_contact`.`id` NOT IN (
                                    SELECT `acrm_membership_exclude`.`contact_id`
                                      FROM `%1$smodule_crm_customer_membership` AS `acrm_membership_exclude` 
                                     WHERE `acrm_membership_exclude`.`membership_id` IN ('.join(',', $crmMembershipFilter['exclude']).'))');

        // select recipients based on selected crm memberships
        $crmMembershipQuery = '';
        if($crmMembershipFilter['associate']){
            $crmMembershipQuery = 'UNION DISTINCT SELECT DISTINCT `crm`.`email`' .($distinctByType ? ', "'.self::USER_TYPE_CRM.'" AS `type`' : '').'
                                        FROM `' . DBPREFIX . 'module_crm_contacts` AS `contact` 
                                        INNER JOIN `' . DBPREFIX . 'module_crm_customer_contact_emails` AS `crm` 
                                        ON `crm`.`contact_id` = `contact`.`id` ' .
                                    $this->getCrmMembershipConditions($crmMembershipFilter);
        }

        return sprintf(
                    // note: intentionally enclosed the following strings in a string to include line breaks
                    //
                    // this following query selects the recipients in the following order
                    // 1. access users that have subscribed to one of the selected recipient-lists
                    // 2. newsletter recipients of one of the selected recipient-lists
                    // 3. access users of one of the selected user groups
                    // 4. crm contacts of one of the selected crm user groups
                    "$accessUserRecipientsQuery
                    $nativeRecipientsQuery
                    $userGroupRecipientsQuery
                    $crmMembershipQuery",

                    DBPREFIX, $mailId
        );
    }

    /**
     * Returns the where condition for the filtered crm newsletter recipients
     *
     * @param  array   $crmMembershipFilter      the filters for the given mail
     * @param  boolean $allowOtherNewsletterType check if the e-mail address is
     *                                        an crm address
     * @return string                         the WHERE statement of the sql query
     */
    protected function getCrmMembershipConditions(
        $crmMembershipFilter,
        $allowOtherNewsletterType = false
    ){
        // if there are excluded membership groups, members of them should
        // NOT be selected, so we exclude them with this query
        $excludeQuery = '';
        if($crmMembershipFilter['exclude']){
            $excludedMembership = join(',',$crmMembershipFilter['exclude']);
            $excludeQuery = ' 
                AND `contact`.`id` NOT IN (
                    SELECT m.`contact_id`
                        FROM `' . DBPREFIX . 'module_crm_customer_membership` AS m 
                            WHERE m.`membership_id` IN (' .  $excludedMembership . ')
             )';
        }

        // allow other newsletter types to match in this query without matching
        // the crm conditions. This is used to also select user accounts and
        // normal newsletter recipients
        $otherNewsletterTypeQuery = '';
        if($allowOtherNewsletterType){
            $otherNewsletterTypeQuery = ' OR `s`.`type` != \'' . self::USER_TYPE_CRM . '\'';
        }

        $associatedMembership = join(',', $crmMembershipFilter['associate']);
        return '
            LEFT JOIN `' . DBPREFIX . 'module_crm_customer_membership` `membership` 
                ON `membership`.`contact_id` = `contact`.`id`
            WHERE
                ((`membership`.`membership_id` IN (' . $associatedMembership . ')' .
                    $excludeQuery .
                    ' AND `contact`.`contact_type` = \'2\'
                      AND `crm`.`is_primary` = \'1\'
                )' . $otherNewsletterTypeQuery .'
                )';
    }

    /**
     * Send the mails
     */
    function _sendMail()
    {
        global $objDatabase, $_ARRAYLANG;

        if (!isset($_REQUEST['id'])) {
            die($_ARRAYLANG['TXT_NEWSLETTER_INVALID_EMAIL']);
        }
        $mailId = intval($_REQUEST['id']);

        $mailRecipientCount = $this->_getMailRecipientCount($mailId);
        if ($mailRecipientCount == 0) {
            $arrJsonData = array(
                'sentComplete' => true,
                'message' => $_ARRAYLANG['TXT_CATEGORY_ERROR']
            );
            die(json_encode($arrJsonData));
        }

        //Get some newsletter data
        $newsletterData = $this->getNewsletterData($mailId);
        $progressbarStatus = round(100 / $mailRecipientCount * $newsletterData['count'], 0);

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_SUBJECT'        => $_ARRAYLANG['TXT_NEWSLETTER_SUBJECT'],
            'TXT_NEWSLETTER_MAILS_SENT'     => $_ARRAYLANG['TXT_NEWSLETTER_SENT_EMAILS']
        ));

        $this->_objTpl->setVariable(array(
            'CONTREXX_CHARSET'              => CONTREXX_CHARSET,
            'NEWSLETTER_MAIL_ID'            => $mailId,
            'NEWSLETTER_MAIL_USERES'        => $mailRecipientCount,
            'NEWSLETTER_SENDT'              => $newsletterData['count'],
            'NEWSLETTER_MAIL_SUBJECT'       => contrexx_raw2xhtml($newsletterData['subject']),
            'NEWSLETTER_PROGRESSBAR_STATUS' => $progressbarStatus
        ));

        // the newsletter was not sent
        if ($newsletterData['status'] == 0) {
            if (!empty($_POST['send'])) {
                // request was sent through ajax
                $arrJsonData = array(
                    'sentComplete'         => false,
                    'count'             => $newsletterData['count'],
                    'progressbarStatus' => $progressbarStatus
                );

                if ($newsletterData['tmp_copy'] == 0) {
                    // The newsletter recipients aren't set. Copy them to the temp table
                    $this->_setTmpSending($mailId);
                } else {
                    // send the mails
                    $arrSettings = $this->_getSettings();
                    $mails_per_run = $arrSettings['mails_per_run']['setvalue'];
                    $timeout = time() + (ini_get('max_execution_time') ? ini_get('max_execution_time') : 300 /* Default Apache and IIS Timeout */);
                    $tmpSending = $this->getTmpSending($mailId, $mails_per_run);

                    // attention: in case there happens a database error, $tmpSending->valid() will return false.
                    //            this will cause to stop the send process even if the newsletter send process wasn't complete yet!!
                    if ($tmpSending->valid()) {
                        foreach ($tmpSending as $send) {
                            $beforeSend = time();
                            $this->SendEmail($send['id'], $mailId, $send['email'], 1, $send['type'], true);

                            // timeout prevention
                            if (time() >= $timeout - (time() - $beforeSend) * 2) {
                                break;
                            }
                        }
                    } else {
                        // basically the send process is done.
                        // the delivery of the last e-mail failed. because of that, the $newsletterData['status'] was not set to 1
                        // we shall set it to 1 one, so that the next ajax request will abbort regularly
                        $objDatabase->Execute("
                            UPDATE ".DBPREFIX."module_newsletter
                               SET status=1
                             WHERE id=$mailId");
                    }
                }

        die(json_encode($arrJsonData));
            } else {
                // request was sent through regular POST
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_MAIL_SEND_INFO_DISPLAY'   => '',
                    'NEWSLETTER_MAIL_SEND_BUTTON_DISPLAY' => '',
                    'NEWSLETTER_MAIL_SENT_STATUS_DISPLAY' => 'none',
                ));
            }
        } else {
            $recipientCount = $this->_getMailRecipientCount($mailId);

            $message = $_ARRAYLANG['TXT_NEWSLETTER_MAIL_SENT_STATUS'];
            $message .= '<br />'.sprintf($_ARRAYLANG['TXT_NEWSLETTER_MAIL_SENT_TO_RECIPIENTS'], $newsletterData['count']);

            // in case the e-mail was not sent to all recipients, output a according message
            if ($newsletterData['count'] < $recipientCount) {
// TODO: check if there are any recipients left that were missed out in the send process (sendt=1).
//       if there are any, provide an option to continue sending the newsletter.
//       additionally, the status of the newsletter must be set back to '0'

// TODO: check if the delivery to any recipients failed (sendt=2).
//       if there are any, provide an option to resend the e-mail to those recipients.
//       the sendt flag must be set back to sendt=1 to be able to resend the e-mail to those recipients where the send process has failed
//       additionally, the status of the newsletter must be set back to '0' (see also option above, where we shall allow to resend the e-mail to those who were left out in the send process (sendt=1)

                $message .= '<br />'.sprintf($_ARRAYLANG['TXT_NEWSLETTER_MAIL_NOT_SENT_TO_RECIPIENTS'], $recipientCount - $newsletterData['count']);
            }

            if (!empty($_POST['send'])) {
                // request was sent through ajax
                $arrJsonData = array(
                    'sentComplete'         => true,
                    'count'             => $newsletterData['count'],
                    'progressbarStatus' => $progressbarStatus,
                    'message'           => $message,
                );
                die(json_encode($arrJsonData));
            } else {
                // request was sent through regular POST
                $this->_objTpl->setVariable('NEWSLETTER_MAIL_SENT_STATUS', $message);
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_MAIL_SEND_INFO_DISPLAY'   => 'none',
                    'NEWSLETTER_MAIL_SEND_BUTTON_DISPLAY' => 'none',
                    'NEWSLETTER_MAIL_SENT_STATUS_DISPLAY' => '',
                ));
            }
        }
    }


    /**
     * Get the emails from the tmp sending page
     *
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @return      DBIterator
     */
    protected function getTmpSending($id, $amount) {
        global $objDatabase;

        // get custom crm filters
        $crmMembershipFilter = $this->emailEditGetCrmMembershipFilter($id);

        $query = "
            SELECT DISTINCT (CASE WHEN `s`.`type` = '".self::USER_TYPE_NEWSLETTER."'
                         THEN `nu`.`id`
                         ELSE (
                         	CASE WHEN `s`.`type` = '".self::USER_TYPE_CRM."'
                         	THEN `crm`.`contact_id`
                         	ELSE `au`.`id`
                        END)
                        END) AS `id`,
                    `s`.email,
                    `s`.type,
                    # this code is used for newsletter browser view
                    `s`.`code`

              FROM `".DBPREFIX."module_newsletter_tmp_sending` AS `s`

         LEFT JOIN `".DBPREFIX."module_newsletter_user` AS `nu`
                ON `nu`.`email` = `s`.`email`
               AND `s`.`type` = '".self::USER_TYPE_NEWSLETTER."'

        LEFT JOIN `".DBPREFIX."module_crm_customer_contact_emails` AS `crm`
                ON `crm`.`email` = `s`.`email`
               AND `s`.`type` = '".self::USER_TYPE_CRM."'
         LEFT JOIN `".DBPREFIX."module_crm_contacts` AS `contact`
                ON `crm`.`contact_id` = `contact`.`id`

         LEFT JOIN `".DBPREFIX."access_users` AS `au`
                ON `au`.`email` = `s`.`email`
               AND (`s`.`type` = '".self::USER_TYPE_ACCESS."' OR `s`.`type` = '".self::USER_TYPE_CORE."')".
         (
            $crmMembershipFilter['associate']
                ? $this->getCrmMembershipConditions($crmMembershipFilter, true) . ' AND '
                : ' WHERE '
         ) . '
              
           `s`.`newsletter` = '.intval($id).'
           AND `s`.`sendt` = 0
           AND (
            `au`.`email` IS NOT NULL 
            OR `nu`.`email` IS NOT NULL
            OR `crm`.`email` IS NOT NULL
           )';
        $res = $objDatabase->SelectLimit($query, $amount, 0);
        return new DBIterator($res);
    }


    /**
     * Return some newsletter data
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $id
     * @throws      Exception
     * @return      array(subject, status, count, tmp_copy)
     */
    protected function getNewsletterData($id)
    {
        global $objDatabase;

        $query = "
            SELECT subject, status, `count`, tmp_copy
              FROM ".DBPREFIX."module_newsletter
             WHERE id=$id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return array();
        return array(
            'subject' => $objResult->fields['subject'],
            'status' => $objResult->fields['status'],
            'count' => $objResult->fields['count'],
            'tmp_copy' => $objResult->fields['tmp_copy'],
        );
    }


    /**
     * Add the email address to the temp
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $mailId
     */
    function _setTmpSending($mailId)
    {
        $mailAddresses = $this->getAllRecipientEmails($mailId);
        $mailAddresses->rewind();
        while ($mailAddresses->valid()) {
            $mail = $mailAddresses->current();
            $this->insertTmpEmail($mailId, $mail['email'], $mail['type']);
            $mailAddresses->next();
        }
        $this->updateNewsletterRecipientCount($mailId);
    }


    /**
     * Insert an email address into the email table
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $mail
     * @param       string $email
     */
    private function insertTmpEmail($mail, $email, $type)
    {
        global $objDatabase;

        $query = '
            INSERT IGNORE INTO `'.DBPREFIX.'module_newsletter_tmp_sending` (
                `newsletter`, `email`, `type`, `code`
            ) VALUES (
                "'.$mail.'", "'.$email.'", "'.$type.'", "'.self::_emailCode().'"
            )
        ';
        $objDatabase->Execute($query);
    }


    /**
     * Return the recipient count of a newsletter in the temp table
     *
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $id
     * @return      int
     */
    private function getTmpRecipientCount($id) {
        global $objDatabase;

        $query = "
            SELECT
                COUNT(1) AS recipient_count
            FROM
                `".DBPREFIX."module_newsletter_tmp_sending`
            WHERE
                `newsletter` = $id
            GROUP BY
                `newsletter`";
        $objResult = $objDatabase->Execute($query);

        return
              $objResult !== false
            ? intval($objResult->fields['recipient_count'])
            : 0;
    }


    /**
     * Update the recipient count of a newsletter
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $newsletter
     */
    private function updateNewsletterRecipientCount($newsletter)
    {
        global $objDatabase;

        $count = $this->getTmpRecipientCount($newsletter);
        $query = "
            UPDATE ".DBPREFIX."module_newsletter
               SET tmp_copy=1,
                   date_sent=".time().",
                   recipient_count=$count
             WHERE id=".intval($newsletter);
        $objDatabase->Execute($query);
    }


    /**
     * Return all email recipients
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $mailID
     * @return      object
     */
    private function getAllRecipientEmails($mailID)
    {
        global $objDatabase;

        return new DBIterator($objDatabase->Execute($this->getMailRecipientQuery(intval($mailID))));
    }


    /**
     * Deliver an email campaign to a recipient
     *
     * @param      int $UserID  ID of the recipient to receive the email
     *                      campaign.
     * @param      int $NewsletterID ID of the email campaign to deliver
     * @param      string $TargetEmail Email of the recipient to receive the
     *                      email campaign.
     * @param      boolean  $TmpEntry   If set to TRUE, then the send operation
     *                      will execute a live delivery to the recipient.
     *                      Otherwise if set to FALSE, then the send operation
     *                      performs a test delivery to the recipient.
     * @param      string $type Type of the recipient to receive the email
     *                      campaign. One of the following:
     *                      - NewsletterManager::USER_TYPE_NEWSLETTER (default)
     *                      - NewsletterManager::USER_TYPE_ACCESS
     *                      - NewsletterManager::USER_TYPE_CORE
     * @param      boolean  $sealDelivery If set to TRUE, the email campaign
     *                      will be marked as sent in the case the campaign has
     *                      been delivered (or attempted to) to all recipients.
     *                      If set to FALSE, the delivery check will be skipped
     *                      and the campaign will not be marked as sent (in case
     *                      it hasn't been so anyway already). Defaults to TRUE.
     */
    protected function SendEmail(
        $UserID, $NewsletterID, $TargetEmail, $TmpEntry,
        $type=self::USER_TYPE_NEWSLETTER,
        $sealDelivery = true
    ) {
        global $objDatabase, $_ARRAYLANG, $_DBCONFIG;

        $newsletterValues = $this->getNewsletterValues($NewsletterID);
        if ($newsletterValues !== false) {
            $subject      = $newsletterValues['subject'];
            $template     = $newsletterValues['template'];
            $content      = $newsletterValues['content'];
            $priority     = $newsletterValues['priority'];
            $sender_email = $newsletterValues['sender_email'];
            $sender_name  = $newsletterValues['sender_name'];
            $return_path  = $newsletterValues['return_path'];
            $count        = $newsletterValues['count'];
            $smtpAccount  = $newsletterValues['smtp_server'];
        }
        $HTML_TemplateSource = $this->GetTemplateSource($template, 'html');
// TODO: Unused
//        $TEXT_TemplateSource = $this->GetTemplateSource($template, 'text');
        $newsletterUserData = $this->getNewsletterUserData($UserID, $type);

        $testDelivery = !$TmpEntry;

        $NewsletterBody_HTML = $this->ParseNewsletter(
            $subject,
            $content,
            $HTML_TemplateSource,
            '',
            $TargetEmail,
            $newsletterUserData,
            $NewsletterID,
            $testDelivery
        );
        \LinkGenerator::parseTemplate($NewsletterBody_HTML, true);

        $NewsletterBody_TEXT = $this->ParseNewsletter(
            '',
            '',
            '',
            'text',
            '',
            $newsletterUserData,
            $NewsletterID,
            $testDelivery
        );
        \LinkGenerator::parseTemplate($NewsletterBody_TEXT, true);

        $mail = new \Cx\Core\MailTemplate\Model\Entity\Mail();
        if ($smtpAccount > 0) {
            if (($arrSmtp = \SmtpSettings::getSmtpAccount($smtpAccount)) !== false) {
                $mail->IsSMTP();
                $mail->Host     = $arrSmtp['hostname'];
                $mail->Port     = $arrSmtp['port'];
                $mail->SMTPAuth = $arrSmtp['username'] == '-' ? false : true;
                $mail->Username = $arrSmtp['username'];
                $mail->Password = $arrSmtp['password'];
            }
        }
        $mail->AddReplyTo($return_path);
        $mail->SetFrom($sender_email, $sender_name);
        $mail->Subject  = $subject;
        $mail->Priority = $priority;
        $mail->Body     = $NewsletterBody_HTML;
        $mail->AltBody  = $NewsletterBody_TEXT;

        $queryATT     = "SELECT newsletter, file_name FROM ".DBPREFIX."module_newsletter_attachment where newsletter=".$NewsletterID."";
        $objResultATT = $objDatabase->Execute($queryATT);
        if ($objResultATT !== false) {
            while (!$objResultATT->EOF) {
                $mail->AddAttachment(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesAttachPath() . "/" . $objResultATT->fields['file_name'], $objResultATT->fields['file_name']);
                $objResultATT->MoveNext();
            }
        }
        $mail->AddAddress($TargetEmail);

        if ($UserID) {
            // mark recipient as in-action to prevent multiple tries of sending the newsletter to the same recipient
            $query = "UPDATE ".DBPREFIX."module_newsletter_tmp_sending SET sendt=2 where email='".$TargetEmail."' AND newsletter=".$NewsletterID." AND sendt=0";
            if ($objDatabase->Execute($query) === false || $objDatabase->Affected_Rows() == 0) {
                return $count;
            }
        }

        if ($mail->Send()) { // && $UserID == 0) {
            $ReturnVar = $count++;
            if ($TmpEntry==1) {
                // Insert TMP-ENTRY Sended Email & Count++
                $query = "UPDATE ".DBPREFIX."module_newsletter_tmp_sending SET sendt=1 where email='".$TargetEmail."' AND newsletter=".$NewsletterID."";
                if ($objDatabase->Execute($query) === false) {
                    if ($_DBCONFIG['dbType'] == 'mysql' && $objDatabase->ErrorNo() == 2006) {
                        @$objDatabase->Connect($_DBCONFIG['host'], $_DBCONFIG['user'], $_DBCONFIG['password'], $_DBCONFIG['database'], true);
                        if ($objDatabase->Execute($query) === false) {
                            return false;
                        }
                    }
                }

                $objDatabase->Execute("
                    UPDATE ".DBPREFIX."module_newsletter
                       SET count=count+1
                     WHERE id=$NewsletterID");
                // mark email campaign as sent in case it has been delivered (or attempted to) to all recipients
                if ($sealDelivery) {
                    $queryCheck     = "SELECT 1 FROM ".DBPREFIX."module_newsletter_tmp_sending where newsletter=".$NewsletterID." and sendt=0";
                    $objResultCheck = $objDatabase->SelectLimit($queryCheck, 1);
                    if ($objResultCheck->RecordCount() == 0) {
                        $objDatabase->Execute("
                            UPDATE ".DBPREFIX."module_newsletter
                               SET status=1
                             WHERE id=$NewsletterID");
                    }
                }
            } /*elseif ($mail->error_count) {
                if (strstr($mail->ErrorInfo, 'authenticate')) {
                    self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_MAIL_AUTH_FAILED'], htmlentities($arrSmtp['name'], ENT_QUOTES, CONTREXX_CHARSET)).'<br />';
                    $ReturnVar = false;
                }
            } */
        } else {
            $performRejectedMailOperation = false;
            if (strstr($mail->ErrorInfo, 'authenticate')) {
                // -> smtp error
                self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_MAIL_AUTH_FAILED'], htmlentities($arrSmtp['name'], ENT_QUOTES, CONTREXX_CHARSET)).'<br />';
            } elseif (strstr($mail->ErrorInfo, 'from_failed')) {
                // -> mail error
                self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_FROM_ADDR_REJECTED'], htmlentities($sender_email, ENT_QUOTES, CONTREXX_CHARSET)).'<br />';
            } elseif (strstr($mail->ErrorInfo, 'recipients_failed')) {
                // -> recipient error
                $performRejectedMailOperation = true;
                self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_FAILED'], htmlentities($TargetEmail, ENT_QUOTES, CONTREXX_CHARSET)).'<br />';
            } elseif (strstr($mail->ErrorInfo, 'instantiate')) {
                // -> php error
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_LOCAL_SMTP_FAILED'].'<br />';
            } elseif (strstr($mail->ErrorInfo, 'connect_host')) {
                // -> smtp error
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_CONNECT_SMTP_FAILED'].'<br />';
            } else {
                // -> mail error
                self::$strErrMessage .= $mail->ErrorInfo.'<br />';
            }
            $ReturnVar = false;

            if ($TmpEntry == 1) {
                $arrSettings = $this->_getSettings();
                if ($performRejectedMailOperation && $arrSettings['rejected_mail_operation']['setvalue'] != 'ignore') {
                    switch ($arrSettings['rejected_mail_operation']['setvalue']) {
                        case 'deactivate':
                            // Remove temporary data from the module
                            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_newsletter_tmp_sending` WHERE `email` ='".addslashes($TargetEmail)."'") !== false) {
                                switch ($type) {
                                    case self::USER_TYPE_CORE:
                                    case self::USER_TYPE_CRM:
                                        // do nothing with system users
                                        // crm users should also not be deactivated
                                        break;

                                    case self::USER_TYPE_ACCESS:
// TODO: Remove newsletter subscription for access_user
                                        break;

                                    case self::USER_TYPE_NEWSLETTER:
                                    default:
                                        // Deactivate user
                                        $objDatabase->Execute("UPDATE `".DBPREFIX."module_newsletter_user` SET `status` = 0 WHERE `id` = ".$UserID);
                                        break;
                                }
                            }
                            break;

                        case 'delete':
                            switch ($type) {
                                case self::USER_TYPE_CORE:
                                case self::USER_TYPE_CRM:
                                    // do nothing with system users
                                    // crm users should also not be deactivated
                                    break;

                                case self::USER_TYPE_ACCESS:
// TODO: Remove newsletter subscription for access_user
                                    break;

                                case self::USER_TYPE_NEWSLETTER:
                                default:
                                    // Remove user data from the module
                                    $this->_deleteRecipient($UserID);
                                    break;
                            }
                            break;


                        case 'inform':
                            $this->informAdminAboutRejectedMail($NewsletterID, $UserID, $TargetEmail, $type, $newsletterUserData);
                            break;
                    }
                }
                $ReturnVar = $count;
            }
        }
        $mail->ClearAddresses();
        $mail->ClearAttachments();

        return $ReturnVar;
    }


    /**
     * Return the newsletter values
     * @param      int $id
     * @return     array | bool
     */
    private function getNewsletterValues($id)
    {
        global $objDatabase;

        $queryNewsletterValues = "
            SELECT id, subject, template, content,
                   attachment, priority, sender_email, sender_name,
                   return_path, smtp_server, status, count,
                   date_create, date_sent
              FROM ".DBPREFIX."module_newsletter
             WHERE id=$id";
        $result = $objDatabase->Execute($queryNewsletterValues);
        return $result !== false ? $result->fields : false;
    }

    /**
     * Inform the admin about a reject
     *
     * If an email could not be sent, inform the administrator
     * about that (only if the option to do so was set)
     *
     * @param integer $newsletterID        Nesletter id
     * @param integer $userID              User Id
     * @param string  $email               E-mail id of the user
     * @param string  $type                User type
     * @param array   $newsletterUserData  Info about the newsletter user
     */
    protected function informAdminAboutRejectedMail($newsletterID, $userID, $email, $type, $newsletterUserData)
    {
        global $_CONFIG;

        // Get the current user's email address
        $loggedUserMail   = \FWUser::getFWUserObject()->objUser->getEmail();
        $newsletterValues = $this->getNewsletterValues($newsletterID);

        $arrMailTemplate = array(
            'key'          => 'notify_undelivered_email',
            'section'      => 'Newsletter',
            'lang_id'      => BACKEND_LANG_ID,
            'to'           => $loggedUserMail,
            'from'         => $newsletterValues['sender_email'],
            'sender'       => $newsletterValues['sender_name'],
            'reply'        => $newsletterValues['return_path'],
            'substitution' => array(
                'NEWSLETTER_USER_SEX'       => $newsletterUserData['sex'],
                'NEWSLETTER_USER_TITLE'     => $newsletterUserData['title'],
                'NEWSLETTER_USER_FIRSTNAME' => $newsletterUserData['firstname'],
                'NEWSLETTER_USER_LASTNAME'  => $newsletterUserData['lastname'],
                'NEWSLETTER_USER_EMAIL'     => $newsletterUserData['email'],
                'NEWSLETTER_DOMAIN_URL'     => $_CONFIG['domainUrl'],
                'NEWSLETTER_CURRENT_DATE'   => date(ASCMS_DATE_FORMAT),
                'NEWSLETTER_SUBJECT'        => $newsletterValues['subject'],
                'NEWSLETTER_USER_EDIT_LINK' => $this->getUserEditLink($userID, $type),
            ),
        );
        \Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate);
    }


    /**
     * Return the Edit link of the inform email
     *
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $userID
     * @param       const $type
     */
    protected function getUserEditLink($userID, $type)
    {
        global $_CONFIG;

        // crm users can not be edited by the user itself
        if($type == self::USER_TYPE_CORE) {
            return '';
        }
        $link = 'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET;

        switch ($type) {
            case self::USER_TYPE_CORE:
            case self::USER_TYPE_ACCESS:
                $link .= '/cadmin/index.php?cmd=Access&act=user&tpl=modify&id='.$userID;
                break;

            case self::USER_TYPE_NEWSLETTER:
            default:
                $link .= '/cadmin/index.php?cmd=Newsletter&act=users&tpl=edit&id='.$userID;
                break;
        }

        return $link;
    }


    function GetTemplateSource($TemplateID) {
        global $objDatabase;
        $TemplateSource = '';
        $queryPN = "select id, name, description, type, html from ".DBPREFIX."module_newsletter_template where id=".$TemplateID."";
        $objResultPN = $objDatabase->Execute($queryPN);
        if ($objResultPN !== false) {
            $TemplateSource = $objResultPN->fields['html'];
        }
        return $TemplateSource;
    }

    /**
     * Parse the newsletter
     * @author      Cloudrexx AG
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       string $userType Which type the user has (newsletter or access)
     */
    function ParseNewsletter(
        $subject, $content_text, $TemplateSource,
        $format, $TargetEmail, $userData, $NewsletterID,
        $testDelivery = false
    ) {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $NewsletterBody = '';
        $codeResult     = $objDatabase->Execute('SELECT `code` FROM `'.DBPREFIX.'module_newsletter_tmp_sending` WHERE `newsletter` = '.$NewsletterID.' AND `email` = "'.$userData['email'].'"');
        $code           = $codeResult->fields['code'];
// TODO: replace with new methode $this->GetBrowserViewURL()

        $crmId = '';
        if($userData['type'] == self::USER_TYPE_CRM) {
            $crmId = '&cId='.$userData['id'];
        }

        $params = array(
            'locale'=> \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
            'code'  => $code,
            'email' => $userData['email'],
            'id'    => $NewsletterID . $crmId,
        );
        $browserViewUrl = \Cx\Core\Routing\Url::fromApi(
            'Newsletter',
            array('View'),
            $params
        );

        if ($format == 'text') {
            $NewsletterBody = $_ARRAYLANG['TXT_NEWSLETTER_BROWSER_VIEW']."\n".$browserViewUrl->toString();
            return $NewsletterBody;
        }

        $country = empty($userData['country_id'])
            ? ''
            : htmlentities(
                  \FWUser::getFWUserObject()->objUser->objAttribute->getById('country_'.$userData['country_id'])->getName(),
                  ENT_QUOTES, CONTREXX_CHARSET
              );

        switch ($userData['sex']) {
            case 'm':
                $sex = $_ARRAYLANG['TXT_NEWSLETTER_MALE'];
                break;
            case 'f':
                $sex = $_ARRAYLANG['TXT_NEWSLETTER_FEMALE'];
                break;
            default:
                $sex = '';
                break;
        }


        // lets prepare all links for tracker before we replace placeholders
// TODO: migrate tracker to new URL-format
        $langId = $this->getRecipientLocaleIdByRecipientId($userData['id'], $userData['type']);
        $content_text = self::prepareNewsletterLinksForSend($NewsletterID, $content_text, $userData['id'], $userData['type'], $langId);

        $search = array(
            '[[email]]',
            '[[sex]]',
            '[[salutation]]',
            '[[title]]',
            '[[firstname]]',
            '[[lastname]]',
            '[[position]]',
            '[[company]]',
            '[[industry_sector]]',
            '[[address]]',
            '[[city]]',
            '[[zip]]',
            '[[country]]',
            '[[phone_office]]',
            '[[phone_private]]',
            '[[phone_mobile]]',
            '[[fax]]',
            '[[birthday]]',
            '[[website]]',
        );
        $replace = array(
            $userData['email'],
            $sex,
            $userData['salutation'],
            $userData['title'],
            $userData['firstname'],
            $userData['lastname'],
            $userData['position'],
            $userData['company'],
            $userData['industry_sector'],
            $userData['address'],
            $userData['city'],
            $userData['zip'],
            $country,
            $userData['phone_office'],
            $userData['phone_private'],
            $userData['phone_mobile'],
            $userData['fax'],
            $userData['birthday'],
            $userData['website'],
        );

        if ($testDelivery) {
            $replace = $search;
        }
        // do the replacement
        $content_text       = str_replace($search, $replace, $content_text);
        $TemplateSource     = str_replace($search, $replace, $TemplateSource);

        $search = array(
            '[[display_in_browser_url]]',
            '[[profile_setup]]',
            '[[profile_setup_url]]',
            '[[unsubscribe]]',
            '[[unsubscribe_url]]',
            '[[date]]',
            '[[subject]]',
        );
        $replace = array(
            $browserViewUrl->toString(),
            $this->GetProfileURL($userData['code'], $TargetEmail, $userData['type']),
            $this->GetProfileURL($userData['code'], $TargetEmail, $userData['type'], false),
            $this->GetUnsubscribeURL($userData['code'], $TargetEmail, $userData['type']),
            $this->GetUnsubscribeURL($userData['code'], $TargetEmail, $userData['type'], false),
            date(ASCMS_DATE_FORMAT_DATE),
            $subject,
        );

        // add content to template
        $NewsletterBody = str_replace("[[content]]", $content_text, $TemplateSource);

        // Replace the links in the content
        $NewsletterBody = str_replace($search, $replace, $NewsletterBody);

        // i believe this replaces image paths...
        $allImg = array();
        preg_match_all('/src="([^"]*)"/', $NewsletterBody, $allImg, PREG_PATTERN_ORDER);
        $size = sizeof($allImg[1]);

        $i = 0;
        while ($i < $size) {
            $URLforReplace = $allImg[1][$i];

            $replaceUrl = new \Cx\Core\Routing\Url($URLforReplace, true);
            if ($replaceUrl->isInternal()) {
                $ReplaceWith = $replaceUrl->toString();
            } else {
                $ReplaceWith = $URLforReplace;
            }

            $NewsletterBody = str_replace(
                '"'.$URLforReplace.'"',
                '"'. contrexx_raw2encodedUrl($ReplaceWith) .'"',
                $NewsletterBody
            );
            $i++;
        }

        // Set HTML height and width attributes for img-tags
        $allImgsWithHeightOrWidth = array();
        preg_match_all('/<img[^>]+>/', $NewsletterBody, $allImgsWithHeightOrWidth);
        foreach ($allImgsWithHeightOrWidth[0] as $img) {
            $htmlHeight = $this->getAttributeOfTag($img, 'img', 'height');
            $htmlWidth = $this->getAttributeOfTag($img, 'img', 'width');
            // no need to proceed if attributes are already set
            if (!empty($htmlHeight) && !empty($htmlWidth)) {
                continue;
            }

            $cssHeight = $this->getCssAttributeOfTag($img, 'img', 'height');
            if (strpos($cssHeight, 'px') !== false) {
                $cssHeight = str_replace('px', '', $cssHeight);
            } else {
                $cssHeight = '';
            }

            $cssWidth = $this->getCssAttributeOfTag($img, 'img', 'width');
            if (strpos($cssWidth, 'px') !== false) {
                $cssWidth = str_replace('px', '', $cssWidth);
            } else {
                $cssWidth = '';
            }

            // no need to proceed if we have no values to set
            if (empty($cssHeight) && empty($cssWidth)) {
                continue;
            }

            $imgOrig = $img;
            // set height and width attributes (if not yet set)
            if (empty($htmlHeight) && !empty($cssHeight)) {
                $img = $this->setAttributeOfTag($img, 'img', 'height', $cssHeight);
            }
            if (empty($htmlWidth) && !empty($cssWidth)) {
                $img = $this->setAttributeOfTag($img, 'img', 'width', $cssWidth);
            }
            $NewsletterBody = str_replace($imgOrig, $img, $NewsletterBody);
        }

        return $NewsletterBody;
    }

    /**
     * Returns the value of an attribute of the specified HTML tag name
     * @param string $html HTML to perform search in
     * @param string $tagName HTML tag to look for
     * @param string $attributeName HTML attribute to look for
     * @return string Attribute value or empty string if not set
     */
    protected function getAttributeOfTag($html, $tagName, $attributeName) {
        $matches = array();
        preg_match('/<' . preg_quote($tagName) . '[^>]*' . preg_quote($attributeName) . '=(["\'])([^\1]*)/', $html, $matches);
        if (!isset($matches[1])) {
            return '';
        }
        return $matches[1];
    }

    /**
     * Sets the HTML attribute of a tag to a specified value
     * @param string $html HTML to perform search in
     * @param string $tagName HTML tag to look for
     * @param string $attributeName HTML attribute to look for
     * @param string $attributeValue Value to set
     * @return string altered HTML
     */
    protected function setAttributeOfTag($html, $tagName, $attributeName, $attributeValue) {
        $count = 0;
        $html = preg_replace('/(<' . preg_quote($tagName) . '[^>]*' . preg_quote($attributeName) . '=(["\']))[^\2]*/', '\1' . $attributeValue, $html, -1, $count);
        if ($count == 0) {
            $html = preg_replace('/(<' . preg_quote($tagName) . '[^>]*)(\/?>)/U', '\1 ' . $attributeName . '="' . $attributeValue . '" \2', $html);
        }
        return $html;
    }

    /**
     * Returns the value of an attribute of the style attribute of an HTML tag
     * @param string $html HTML to perform search in
     * @param string $tagName HTML tag to look for
     * @param string $cssAttributeName CSS attribute to look for in style attribute
     * @return string Attribute value or empty string if not set
     */
    protected function getCssAttributeOfTag($html, $tagName, $cssAttributeName) {
        $matches = array();
        preg_match('/<' . preg_quote($tagName) . '[^>]*style=(["\'])[^\1]*' . preg_quote($cssAttributeName) . '\s*:\s*([^;\1]*)/', $html, $matches);
        if (!isset($matches[2])) {
            return '';
        }
        return $matches[2];
    }


    /**
     * Return the user data
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $id
     * @param       string $type
     * @param       int ID of newsletter e-mail
     * @return      adodb result object
     */
    private function getNewsletterUserData($id, $type)
    {
        global $objDatabase;

        $arrUserData = array(
            'code'              => '',
            'email'             => '',
            'sex'               => '',
            'salutation'        => '',
            'title'             => '',
            'firstname'         => '',
            'lastname'          => '',
            'position'          => '',
            'company'           => '',
            'industry_sector'   => '',
            'address'           => '',
            'city'              => '',
            'zip'               => '',
            'country_id'        => '0',
            'phone_office'      => '',
            'phone_private'     => '',
            'phone_mobile'      => '',
            'fax'               => '',
            'birthday'          => '00-00-0000',
            'website'           => '',
            'type'              => $type,
            'id'                => $id
        );

        if (!$id) return $arrUserData;

        switch ($type) {
            case self::USER_TYPE_ACCESS:
                $query = "
                    SELECT code
                      FROM ".DBPREFIX."module_newsletter_access_user
                     WHERE accessUserID = $id";
                $result = $objDatabase->SelectLimit($query, 1);
                if ($result && !$result->EOF) {
                    $arrUserData['code'] = $result->fields['code'];
                }

                // intentionally no break here!!

            case self::USER_TYPE_CORE:
                $objUser = \FWUser::getFWUserObject()->objUser->getUser($id);

                if (!$objUser) {
                    // in case no user account exists by the supplied ID, then reset the code and abort operation
                    $arrUserData['code'] = '';
                    break;
                }

                switch ($objUser->getProfileAttribute('gender')) {
                    case 'gender_male':
                        $arrUserData['sex'] = 'm';
                        break;
                    case 'gender_female':
                        $arrUserData['sex'] = 'f';
                        break;
                }

                $arrUserData['email']           = $objUser->getEmail();
                $arrUserData['website']         = $objUser->getProfileAttribute('website');
                $arrUserData['salutation']      = $objUser->objAttribute->getById('title_'.$objUser->getProfileAttribute('title'))->getName();
                $arrUserData['lastname']        = $objUser->getProfileAttribute('lastname');
                $arrUserData['firstname']       = $objUser->getProfileAttribute('firstname');
                $arrUserData['company']         = $objUser->getProfileAttribute('company');
                $arrUserData['address']         = $objUser->getProfileAttribute('address');
                $arrUserData['zip']             = $objUser->getProfileAttribute('zip');
                $arrUserData['city']            = $objUser->getProfileAttribute('city');
                $arrUserData['country_id']      = $objUser->getProfileAttribute('country');
                $arrUserData['phone_office']    = $objUser->getProfileAttribute('phone_office');
                $arrUserData['phone_private']   = $objUser->getProfileAttribute('phone_private');
                $arrUserData['phone_mobile']    = $objUser->getProfileAttribute('phone_mobile');
                $arrUserData['fax']             = $objUser->getProfileAttribute('phone_fax');
                $arrUserData['birthday']        = $objUser->getProfileAttribute('birthday');
                break;

            case self::USER_TYPE_CRM:
                $crmUser = new \Cx\Modules\Crm\Model\Entity\CrmContact();
                $crmUser->load($id);

                $arrUserData['sex'] = '';
                if($crmUser->contact_gender == 1){
                    $arrUserData['sex'] = 'f';
                } else if($crmUser->contact_gender == 2){
                    $arrUserData['sex'] = 'm';
                }

                $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute
                    ->getById('title_' . $crmUser->salutation);
                $salutation = '';
                if (!$objAttribute->EOF) {
                    $salutation = $objAttribute->getName();
                }
                // crm dos not support the following fields:
                // birthday, industry_sector, country
                $arrUserData['email']           = $crmUser->email;
                $arrUserData['lastname']        = $crmUser->family_name;
                $arrUserData['firstname']       = $crmUser->customerName;
                $arrUserData['salutation']      = $salutation;
                $arrUserData['address']         = $crmUser->address;
                $arrUserData['company']         = $crmUser->linkedCompany;
                $arrUserData['title']           = $crmUser->contact_title;
                $arrUserData['position']        = $crmUser->contact_role;
                $arrUserData['zip']             = $crmUser->zip;
                $arrUserData['city']            = $crmUser->city;
                $arrUserData['website']         = $crmUser->url;
                $arrUserData['phone_office']    = $crmUser->phone;
                break;

            case self::USER_TYPE_NEWSLETTER:
            default:
                $query = "
                    SELECT code, sex, email, uri,
                           salutation, title, lastname, firstname,
                           position, address, zip, city, country_id,
                           phone_office, company, industry_sector, birthday,
                           phone_private, phone_mobile, fax
                      FROM ".DBPREFIX."module_newsletter_user
                     WHERE id=$id";
                $result = $objDatabase->Execute($query);
                if (!$result || $result->EOF) {
                    break;
                }

// TODO: use FWUser instead of _getRecipientTitles()
                $arrRecipientTitles = $this->_getRecipientTitles();
                $arrUserData['code']            = $result->fields['code'];
                $arrUserData['sex']             = $result->fields['sex'];
                $arrUserData['email']           = $result->fields['email'];
                $arrUserData['salutation']      = $arrRecipientTitles[$result->fields['salutation']];
                $arrUserData['title']           = $result->fields['title'];
                $arrUserData['firstname']       = $result->fields['firstname'];
                $arrUserData['lastname']        = $result->fields['lastname'];
                $arrUserData['position']        = $result->fields['position'];
                $arrUserData['company']         = $result->fields['company'];
                $arrUserData['industry_sector'] = $result->fields['industry_sector'];
                $arrUserData['address']         = $result->fields['address'];
                $arrUserData['city']            = $result->fields['city'];
                $arrUserData['zip']             = $result->fields['zip'];
                $arrUserData['country_id']      = $result->fields['country_id'];
                $arrUserData['phone_office']    = $result->fields['phone_office'];
                $arrUserData['phone_private']   = $result->fields['phone_private'];
                $arrUserData['phone_mobile']    = $result->fields['phone_mobile'];
                $arrUserData['fax']             = $result->fields['fax'];
                $arrUserData['birthday']        = $result->fields['birthday'];
                $arrUserData['website']         = $result->fields['uri'];
                break;
        }

        return $arrUserData;
    }

    /**
     * Parse the news section
     *
     * @return null
     */
    public function _getNewsPage()
    {
        global $objDatabase, $objInit, $_ARRAYLANG;

        \JS::activate('cx');

// TODO: Unused
//        $objFWUser = \FWUser::getFWUserObject();

        $newsdate = time() - 86400 * 30;
        if (!empty($_POST['newsDate'])) {
            $newsdate = $this->dateFromInput(contrexx_input2raw($_POST['newsDate']));
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_NEWS_IMPORT'];
        $this->_objTpl->loadTemplateFile('newsletter_news.html');
        $this->_objTpl->setVariable(array(
            'TXT_NEWS_IMPORT' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_IMPORT'],
            'TXT_DATE_SINCE' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_DATE_SINCE'],
            'TXT_SELECTED_MESSAGES' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_SELECTED_MESSAGES'],
            'TXT_NEXT' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_NEXT'],
            'NEWS_CREATE_DATE' => $this->valueFromDate($newsdate)
        ));

        $query = ' SELECT   n.id,
                            n.date,
                            nl.title,
                            nl.text,
                            n.userid,
                            nl.teaser_text,
                            nc.category_id AS categoryId,
                            n.teaser_image_path,
                            n.teaser_image_thumbnail_path,
                            cl.name    AS categoryName,
                            tl.name    AS typename
                    FROM '.DBPREFIX.'module_news n
                    LEFT JOIN '.DBPREFIX.'module_news_locale nl ON n.id = nl.news_id AND nl.lang_id='.$objInit->userFrontendLangId.'
                    LEFT JOIN '.DBPREFIX.'module_news_rel_categories AS nc ON nc.news_id = n.id
                    LEFT JOIN '.DBPREFIX.'module_news_categories_locale AS cl ON cl.category_id = nc.category_id AND cl.lang_id ='.$objInit->userFrontendLangId.'
                    LEFT JOIN '.DBPREFIX.'module_news_types_locale tl ON n.typeid = tl.type_id AND tl.lang_id='.$objInit->userFrontendLangId.'
                    WHERE n.date > '.$newsdate.'
                            AND n.status = "1"
                            AND n.validated = "1"
                    ORDER BY categoryName ASC, n.date DESC';
            /*AND (n.startdate <> '0000-00-00 00:00:00' OR n.enddate <> '0000-00-00 00:00:00')*/

        $objNews = $objDatabase->Execute($query);
        $current_category = '';
        if ($objNews !== false) {
            while (!$objNews->EOF) {
                $this->_objTpl->setVariable(array(
                    'NEWS_CATEGORY_NAME' => contrexx_raw2xhtml($objNews->fields['categoryName']),
                    'NEWS_CATEGORY_ID'   => $objNews->fields['categoryId'],
                ));
                if($current_category == $objNews->fields['categoryId'] && $this->_objTpl->blockExists("news_category")){
                    $this->_objTpl->hideBlock("news_category");
                }
                $current_category = $objNews->fields['categoryId'];
// TODO: Unused
//                $newstext = ltrim(strip_tags($objNews->fields['text']));
                $newsteasertext = ltrim(strip_tags($objNews->fields['teaser_text']));
                //$newslink = $this->newsletterUri.ASCMS_PROTOCOL."://".$_SERVER['HTTP_HOST'].ASCMS_PATH_OFFSET."/index.php?section=News&cmd=details&newsid=".$objNews->fields['id'];
                /*if ($objNews->fields['userid'] && ($objUser = $objFWUser->objUser->getUser($objNews->fields['userid']))) {
                        $author = htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET);
                    } else {
                        $author = $_ARRAYLANG['TXT_ANONYMOUS'];
                    }*/
                $image = $objNews->fields['teaser_image_path'];
                $thumbnail = $objNews->fields['teaser_image_thumbnail_path'];

                if (!empty($thumbnail)) {
                    $imageSrc = $thumbnail;
                } elseif (!empty($image) && file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsitePath() . \ImageManager::getThumbnailFilename($image))) {
                    $imageSrc = \ImageManager::getThumbnailFilename($image);
                } elseif (!empty($image)) {
                    $imageSrc = $image;
                } else {
                    $imageSrc = '';
                }
                $this->_objTpl->setVariable(array(
                    //'NEWS_CATEGORY_NAME' => $objNews->fields['catname'],
                    'NEWS_ID' => $objNews->fields['id'],
                    'NEWS_CATEGORY_ID' => $objNews->fields['categoryId'],
                    //'NEWS_DATE' => date(ASCMS_DATE_FORMAT_DATE, $objNews->fields['date']),
                    //'NEWS_LONG_DATE' => date(ASCMS_DATE_FORMAT_DATETIME, $objNews->fields['date']),
                    'NEWS_TITLE' => contrexx_raw2xhtml($objNews->fields['title']),
                    //'NEWS_URL' => $newslink,
                    'NEWS_IMAGE_PATH' => contrexx_raw2encodedUrl($imageSrc),
                    'NEWS_TEASER_TEXT' => contrexx_raw2xhtml($newsteasertext),
                    //'NEWS_TEXT' => $newstext,
                    //'NEWS_AUTHOR' => $author,
                    //'NEWS_TYPE_NAME' => $objNews->fields['typename'],
                    //'TXT_LINK_TO_REPORT_INFO_SOURCES' => $_ARRAYLANG['TXT_LINK_TO_REPORT_INFO_SOURCES']
                ));
                $this->_objTpl->parse("news_list");
                $objNews->MoveNext();
            }
        } else {
            $this->_objTpl->setVariable('NEWS_EMPTY_LIST', $_ARRAYLANG['TXT_NEWSLETTER_NEWS_EMPTY_LIST']);
        }
    }

    /**
     * Display the preview of the news in email template
     *
     * @return null
     */
    public function _getNewsPreviewPage()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate('cx');

	$mailTemplate   = isset($_POST['newsletter_mail_template'])
                                ? contrexx_input2int($_POST['newsletter_mail_template'])
                                : '1';
	$importTemplate = isset($_POST['newsletter_import_template'])
                                ? contrexx_input2int($_POST['newsletter_mail_template'])
                                : '2';

	if (isset($_GET['view']) && $_GET['view'] == 'iframe') {
            $selectedCategoryNews = isset($_POST['selected'])
                                      ? json_decode(contrexx_input2raw($_POST['selected']), true)
                                      : '';
            $mailTemplate   = isset($_POST['emailtemplate']) ? contrexx_input2int($_POST['emailtemplate']) : '1';
            $importTemplate = isset($_POST['importtemplate']) ? contrexx_input2int($_POST['importtemplate']) : '2';
            $selectedNews   = array();
            foreach ($selectedCategoryNews as $news) {
                foreach ($news as $newsId) {
                    if (in_array($newsId, $selectedNews)) {
                        continue;
                    }
                    $selectedNews[] = $newsId;
                }
            }
            $HTML_TemplateSource_Import = $this->_getBodyContent($this->_prepareNewsPreview($this->GetTemplateSource($importTemplate, 'html')));

            $_REQUEST['standalone'] = true;
            $this->_objTpl = new \Cx\Core\Html\Sigma();
            \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
            $this->_objTpl->setTemplate($HTML_TemplateSource_Import);

            $query = '  SELECT  n.id                AS newsid,
                                n.userid            AS newsuid,
                                n.date              AS newsdate,
                                n.teaser_image_path,
                                n.teaser_image_thumbnail_path,
                                n.redirect,
                                n.publisher,
                                n.publisher_id,
                                n.author,
                                n.author_id,
                                nc.category_id      AS categoryId,
                                nl.title            AS newstitle,
                                nl.text             AS newscontent,
                                nl.teaser_text,
                                nc.name             AS name
                    FROM        '.DBPREFIX.'module_news AS n
                    INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    INNER JOIN  '.DBPREFIX.'module_news_rel_categories AS nr ON nr.news_id = n.id
                    INNER JOIN  '.DBPREFIX.'module_news_categories_locale AS nc ON nc.category_id = nr.category_id
                    WHERE       status = 1
                                AND nl.is_active=1
                                AND nl.lang_id='.FRONTEND_LANG_ID.'
                                AND nc.lang_id='.FRONTEND_LANG_ID.'
                                AND n.id IN ('.implode(",", $selectedNews).')
                    ORDER BY nc.name ASC, n.date DESC';

            $objNews = $objDatabase->Execute($query);
            $currentCategory = 0;
            if ($objNews !== false) {
                $objNewsLib = new \Cx\Core_Modules\News\Controller\NewsLibrary();
                $isNewsListExists = $this->_objTpl->blockExists('news_list');
                $newsHtmlContent = '';
                while (!$objNews->EOF) {
                    $categoryId = $objNews->fields['categoryId'];
                    if(!array_key_exists($categoryId, $selectedCategoryNews)
                        || !in_array($objNews->fields['newsid'], $selectedCategoryNews[$categoryId])){
                        $objNews->MoveNext();
                        continue;
                    }
                    if ($isNewsListExists) {
                        $this->parseNewsDetails($this->_objTpl, $objNewsLib, $objNews, $currentCategory);
                    } else {
                        $content = $this->getNewsMailContent($importTemplate, $objNewsLib, $objNews, true);
                        if ($newsHtmlContent != '') {
                            $newsHtmlContent .= "<br/>" . $content;
                        } else {
                            $newsHtmlContent = $content;
                        }
                    }
                    $objNews->MoveNext();
                }
                $parsedNewsList = ($isNewsListExists) ? $this->_objTpl->get() : $newsHtmlContent;
            }
            $previewHTML = str_replace("[[content]]", $parsedNewsList, $this->GetTemplateSource($mailTemplate, 'html'));
            $this->_objTpl->setTemplate($previewHTML);
            return $this->_objTpl->get();
        } else {
            $selectedNews = isset($_POST['selectedNews']) ? contrexx_input2raw($_POST['selectedNews']) : '';

            $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_NEWS_IMPORT_PREVIEW'];
            $this->_objTpl->loadTemplateFile('newsletter_news_preview.html');
            $this->_objTpl->setVariable(array(
            'TXT_EMAIL_LAYOUT' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_EMAIL_LAYOUT'],
            'TXT_IMPORT_LAYOUT' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_IMPORT_LAYOUT'],
            'TXT_NEWS_PREVIEW' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_PREVIEW'],
            'TXT_CREATE_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_NEWS_CREATE_EMAIL'],
            'NEWSLETTER_MAIL_TEMPLATE_MENU' => $this->_getTemplateMenu($mailTemplate, 'id="newsletter_mail_template" name="newsletter_mail_template" style="width:300px;" onchange="refreshIframe();"'),
            'NEWSLETTER_IMPORT_TEMPLATE_MENU' => $this->_getTemplateMenu($importTemplate, 'id="newsletter_import_template" name="newsletter_import_template" style="width:300px;" onchange="refreshIframe();"', 'news'),
                'NEWSLETTER_SELECTED_NEWS' => json_encode($selectedNews),
            'NEWSLETTER_SELECTED_EMAIL_TEMPLATE' => $mailTemplate,
            'NEWSLETTER_SELECTED_IMPORT_TEMPLATE' => $importTemplate
            ));
        }
    }

    /**
     * Replace the placeholders formats
     *
     * @param  string  $TemplateSource template content
     *
     * @return string
     */
    public function _prepareNewsPreview($TemplateSource)
    {
        $TemplateSource = str_replace("[[","{",$TemplateSource);
        $TemplateSource = str_replace("]]","}",$TemplateSource);
        return $TemplateSource;
    }

    function exportuser()
    {
        global $_ARRAYLANG;

        $separator = ';';
        $listId = isset($_REQUEST['listId']) ? intval($_REQUEST['listId']) : 0;
// TODO: use FWUSER
        $arrRecipientTitles = $this->_getRecipientTitles();
        if ($listId > 0) {
            $list = $this->_getList($listId);
            $listname = $list['name'];
        } else {
            $listname = "all_lists";
        }
        /*
        $query    = "    SELECT * FROM ".DBPREFIX."module_newsletter_rel_user_cat
                    RIGHT JOIN ".DBPREFIX."module_newsletter_user
                        ON ".DBPREFIX."module_newsletter_rel_user_cat.user=".DBPREFIX."module_newsletter_user.id ".
                    $WhereStatement." GROUP BY user";
        */

// TODO: $WhereStatement is not defined
$WhereStatement = '';
        list ($users, $count) = $this->returnNewsletterUser(
            $WhereStatement, $order = '', $listId);
// TODO: $count is never used
++$count;

// TODO: $query is not defined, this has probably been superseeded by the
// method call above?
//        $objResult     = $objDatabase->Execute($query);
        $StringForFile = $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_SEX'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_SALUTATION'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_TITLE'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_LASTNAME'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_FIRSTNAME'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_POSITION'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_INDUSTRY_SECTOR'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_ZIP'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_CITY'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_PHONE'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_PHONE_PRIVATE'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_PHONE_MOBILE'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_FAX'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_BIRTHDAY'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_WEBSITE'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_NOTES'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_LANGUAGE'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY_ID'].$separator;
        $StringForFile .= $_ARRAYLANG['TXT_NEWSLETTER_STATUS'];
        $StringForFile .= chr(13).chr(10);

        foreach ($users as $user) {
            $StringForFile .= $user['email'].$separator;
            $StringForFile .= $user['sex'].$separator;
            $StringForFile .= $arrRecipientTitles[$user['salutation']].$separator;
            $StringForFile .= $user['title'].$separator;
            $StringForFile .= $user['lastname'].$separator;
            $StringForFile .= $user['firstname'].$separator;
            $StringForFile .= $user['position'].$separator;
            $StringForFile .= $user['company'].$separator;
            $StringForFile .= $user['industry_sector'].$separator;
            $StringForFile .= $user['address'].$separator;
            $StringForFile .= $user['zip'].$separator;
            $StringForFile .= $user['city'].$separator;
            $StringForFile .= \FWUser::getFWUserObject()->objUser->objAttribute->getById('country_'.$user['country_id'])->getName().$separator;
            $StringForFile .= $user['phone_office'].$separator;
            $StringForFile .= $user['phone_private'].$separator;
            $StringForFile .= $user['phone_mobile'].$separator;
            $StringForFile .= $user['fax'].$separator;
            $StringForFile .= $user['birthday'].$separator;
            $StringForFile .= $user['uri'].$separator;
            $StringForFile .= $user['notes'].$separator;
            $StringForFile .= $user['language'].$separator;
            $StringForFile .= $user['country_id'].$separator;
            $StringForFile .= $user['status'];
            $StringForFile .= chr(13).chr(10);
        }
        if (strtolower(CONTREXX_CHARSET) != 'utf-8') {
            $StringForFile = utf8_encode($StringForFile);
        }
        header("Content-Type: text/comma-separated-values");
        header('Content-Disposition: attachment; filename="'.date('Y_m_d')."-".$listname.'.csv"');
        die($StringForFile);
    }


    function edituserSort()
    {
        global $_CONFIG;

        $output = array(
            'recipient_count'   => 0,
            'user' => array()
        );

        $fieldValues = array('status', 'email', 'uri', 'company', 'lastname', 'firstname', 'address', 'zip', 'city', 'country_id', 'feedback', 'emaildate', );
        $field  = !empty($_REQUEST['field']) && in_array($_REQUEST['field'], $fieldValues) ? $_REQUEST['field'] : 'emaildate';
        $order  = !empty($_REQUEST['order']) && $_REQUEST['order'] == 'desc' ? 'desc' : 'asc';
        $listId = !empty($_REQUEST['list'])  ? intval($_REQUEST['list']) : '';
        $limit  = !empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $_CONFIG['corePagingLimit'];
        $pos    = !empty($_REQUEST['pos'])   ? intval($_REQUEST['pos']) : 0;

        if ($field == 'country') $field = 'country_id';

        $keyword = !empty($_REQUEST['keyword']) ? contrexx_raw2db(trim($_REQUEST['keyword'])) : '';
        $searchfield  = !empty($_REQUEST['filter_attribute']) && in_array($_REQUEST['filter_attribute'], $fieldValues) ? $_REQUEST['filter_attribute'] : '';
        $searchstatus = isset($_REQUEST['filter_status']) ? ($_REQUEST['filter_status'] == '1' ? '1' : ($_REQUEST['filter_status'] == '0' ? '0' : null)) : null;

        // don't ignore search stuff
        $search_where = '';
        if (!empty($keyword)) {
            if (!empty($searchfield)) {
                $search_where = "AND `$searchfield` LIKE '%$keyword%'";
            } else {
                $search_where = 'AND (     email LIKE "%'.$keyword.'%"
                                        OR company LIKE "%'.$keyword.'%"
                                        OR lastname LIKE "%'.$keyword.'%"
                                        OR firstname LIKE "%'.$keyword.'%"
                                        OR address LIKE "%'.$keyword.'%"
                                        OR zip LIKE "%'.$keyword.'%"
                                        OR city LIKE "%'.$keyword.'%"'.
                                        /*OR country_id LIKE "%'.$keyword.'%"*/'
                                        OR phone_office LIKE "%'.$keyword.'%"
                                        OR birthday LIKE "%'.$keyword.'%")';
            }
        }

        /*if ($searchstatus !== null) {
            $search_where .= " AND `status` = $searchstatus ";
        }*/

        list ($users, $output['recipient_count']) = $this->returnNewsletterUser(
            $search_where, "ORDER BY `$field` $order", $listId, $searchstatus, $limit, $pos);

        $linkCount            = array();
        $feedbackCount        = array();
        $emailCount           = array();
        $this->feedback($users, $linkCount, $feedbackCount, $emailCount);

        foreach ($users as $user) {
            $type = str_replace("_user", "", $user['type']);
            $link_count = isset($linkCount[$user['id']][$type]) ? $linkCount[$user['id']][$type] : 0;
            $feedback = isset($feedbackCount[$user['id']][$type]) ? $feedbackCount[$user['id']][$type] : 0;
            $feedbackdata = $link_count > 0 ? round(100 / $link_count * $feedback).'%' : '-';

            $country = empty($user['country_id'])
                ? ''
                : \FWUser::getFWUserObject()->objUser->objAttribute->getById(
                    'country_'.$user['country_id'])->getName();

            $consentValue = static::parseConsentView(
                $user['source'],
                $user['consent']
            );
            if (!empty($user['cat_source'])) {
                $consentValue .= ' / ' . static::parseConsentView(
                    $user['cat_source'],
                    $user['cat_consent']
                );
            }

            $output['user'][] = array(
                'id'        => $user['id'],
                'status'    => $user['status'],
                'email'     => $user['email'],
                'company'   => empty($user['company']) ? '-' : $user['company'],
                'lastname'  => empty($user['lastname']) ? '-' : (mb_strlen($user['lastname'], CONTREXX_CHARSET) > 30) ? mb_substr($user['lastname'], 0, 27, CONTREXX_CHARSET).'...' : $user['lastname'],
                'firstname' => empty($user['firstname']) ? '-' : (mb_strlen($user['firstname'], CONTREXX_CHARSET) > 30) ? mb_substr($user['firstname'], 0, 27, CONTREXX_CHARSET).'...' : $user['firstname'],
                'address'   => empty($user['address']) ? '-' : $user['address'],
                'zip'       => empty($user['zip']) ? '-' : $user['zip'],
                'city'      => empty($user['city']) ? '-' : $user['city'],
                'country'   => $country,
                'emaildate' => date(ASCMS_DATE_FORMAT, $user['emaildate']),
                'type'      => $type,
                'consent'   => $consentValue,
            );
            $arrSettings = $this->_getSettings();
            if ($arrSettings['statistics']['setvalue']) {
                $currentKey = key($output['user']);
                $output['user'][$currentKey]['feedback']  = $feedbackdata;
            }
        }
        die(json_encode($output));
    }

// TODO: Refactor this method
// TODO: $emailCount never used!!
    function feedback(&$users, &$linkCount, &$feedbackCount, &$emailCount)
    {
        global $objDatabase;

        // count feedback
        $newsletterUserIds    = array();
        $newsletterUserEmails = array();
        $accessUserIds        = array();
        $accessUserEmails     = array();

        // ATTENTION: this very use of $user['type'] is not related to self::USER_TYPE_ACCESS, self::USER_TYPE_CORE or self::USER_TYPE_NEWSLETTER!
        foreach ($users as $user) {
            if ($user['type'] == 'newsletter_user') {
                $newsletterUserIds[] = $user['id'];
                $newsletterUserEmails[] = $user['email'];
            } elseif ($user['type'] == 'access_user') {
                $accessUserIds[] = $user['id'];
                $accessUserEmails[] = $user['email'];
            }
        }

        // select stats of native newsletter recipients
        if (count($newsletterUserIds) > 0) {
            $objLinks = $objDatabase->Execute("SELECT
                    tlbUser.id,
                    COUNT(tlbLink.id) AS link_count,
                    COUNT(DISTINCT tblSent.newsletter) AS email_count
                FROM ".DBPREFIX."module_newsletter_tmp_sending AS tblSent
                    INNER JOIN ".DBPREFIX."module_newsletter_user AS tlbUser ON tlbUser.email = tblSent.email
                    LEFT JOIN ".DBPREFIX."module_newsletter_email_link AS tlbLink ON tlbLink.email_id = tblSent.newsletter
                WHERE tblSent.email IN ('".implode("', '", $newsletterUserEmails)."') AND tblSent.sendt > 0 AND tblSent.type = '".self::USER_TYPE_NEWSLETTER."'
                GROUP BY tblSent.email");
            if ($objLinks !== false) {
                while (!$objLinks->EOF) {
                    $linkCount[$objLinks->fields['id']][self::USER_TYPE_NEWSLETTER] = $objLinks->fields['link_count'];
                    $emailCount[$objLinks->fields['id']][self::USER_TYPE_NEWSLETTER] = $objLinks->fields['email_count'];
                    $objLinks->MoveNext();
                }
            }

            $objLinks = $objDatabase->Execute("SELECT
                    tblLink.recipient_id,
                    COUNT(tblLink.id) AS feedback_count
                FROM ".DBPREFIX."module_newsletter_email_link_feedback AS tblLink
                WHERE tblLink.recipient_id IN (".implode(", ", $newsletterUserIds).") AND tblLink.recipient_type = '".self::USER_TYPE_NEWSLETTER."'
                GROUP BY tblLink.recipient_id");
            if ($objLinks !== false) {
                while (!$objLinks->EOF) {
                    $feedbackCount[$objLinks->fields['recipient_id']][self::USER_TYPE_NEWSLETTER] = $objLinks->fields['feedback_count'];
                    $objLinks->MoveNext();
                }
            }
        }

        // select stats of access users
        if (count($accessUserIds) > 0) {
            $objLinks = $objDatabase->Execute("SELECT
                    tlbUser.id,
                    COUNT(tlbLink.id) AS link_count,
                    COUNT(DISTINCT tblSent.newsletter) AS email_count
                FROM ".DBPREFIX."module_newsletter_tmp_sending AS tblSent
                    INNER JOIN ".DBPREFIX."access_users AS tlbUser ON tlbUser.email = tblSent.email
                    LEFT JOIN ".DBPREFIX."module_newsletter_email_link AS tlbLink ON tlbLink.email_id = tblSent.newsletter
                WHERE tblSent.email IN ('".implode("', '", $accessUserEmails)."') AND tblSent.sendt > 0 AND (tblSent.type = '".self::USER_TYPE_ACCESS."' OR tblSent.type = '".self::USER_TYPE_CORE."')
                GROUP BY tblSent.email");
            if ($objLinks !== false) {
                while (!$objLinks->EOF) {
                    $linkCount[$objLinks->fields['id']][self::USER_TYPE_ACCESS] = $objLinks->fields['link_count'];
                    $emailCount[$objLinks->fields['id']][self::USER_TYPE_ACCESS] = $objLinks->fields['email_count'];
                    $objLinks->MoveNext();
                }
            }
            $objLinks = $objDatabase->Execute("SELECT
                    tblLink.recipient_id,
                    COUNT(tblLink.id) AS feedback_count
                FROM ".DBPREFIX."module_newsletter_email_link_feedback AS tblLink
                WHERE tblLink.recipient_id IN (".implode(", ", $accessUserIds).") AND tblLink.recipient_type = '".self::USER_TYPE_ACCESS."'
                GROUP BY tblLink.recipient_id");
            if ($objLinks !== false) {
                while (!$objLinks->EOF) {
                    $feedbackCount[$objLinks->fields['recipient_id']][self::USER_TYPE_ACCESS] = $objLinks->fields['feedback_count'];
                    $objLinks->MoveNext();
                }
            }
        }
    }




    function importuser()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::registerJS('modules/Newsletter/View/Script/Backend.js');

        // Store the language interface text in javascript variable
        \ContrexxJavascript::getInstance()->setVariable(
            'NEWSLETTER_CONSENT_CONFIRM_ERROR',
            $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_MESSAGE_ERROR'],
            'Newsletter'
        );

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objTpl = new \Cx\Core\Html\Sigma($cx->getCodeBaseModulePath() . '/Newsletter/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objTpl);
        $objTpl->setErrorHandling(PEAR_ERROR_DIE);

        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/importexport/import.class.php');
        $objImport = new \Import();
        $arrFields = array(
            'email'           => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'],
            'sex'             => $_ARRAYLANG['TXT_NEWSLETTER_SEX'],
            'salutation'      => $_ARRAYLANG['TXT_NEWSLETTER_SALUTATION'],
            'title'           => $_ARRAYLANG['TXT_NEWSLETTER_TITLE'],
            'lastname'        => $_ARRAYLANG['TXT_NEWSLETTER_LASTNAME'],
            'firstname'       => $_ARRAYLANG['TXT_NEWSLETTER_FIRSTNAME'],
            'position'        => $_ARRAYLANG['TXT_NEWSLETTER_POSITION'],
            'company'         => $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'],
            'industry_sector' => $_ARRAYLANG['TXT_NEWSLETTER_INDUSTRY_SECTOR'],
            'address'         => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'zip'             => $_ARRAYLANG['TXT_NEWSLETTER_ZIP'],
            'city'            => $_ARRAYLANG['TXT_NEWSLETTER_CITY'],
            'country_id'      => $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY'],
            'phone_office'    => $_ARRAYLANG['TXT_NEWSLETTER_PHONE'],
            'phone_private'   => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_PRIVATE'],
            'phone_mobile'    => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_MOBILE'],
            'fax'             => $_ARRAYLANG['TXT_NEWSLETTER_FAX'],
            'birthday'        => $_ARRAYLANG['TXT_NEWSLETTER_BIRTHDAY'],
            'uri'             => $_ARRAYLANG['TXT_NEWSLETTER_WEBSITE'],
            'notes'           => $_ARRAYLANG['TXT_NEWSLETTER_NOTES'],
            'language'        => $_ARRAYLANG['TXT_NEWSLETTER_LANGUAGE']
        );
        $source = 'backend';

        if (isset($_POST['import_cancel'])) {
            // Abbrechen. Siehe Abbrechen
            $objImport->cancel();
            \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?cmd=Newsletter&act=users&tpl=import");
            exit;
        } elseif (isset($_POST['fieldsSelected'])) {
            // Speichern der Daten. Siehe Final weiter unten.
            $arrRecipients = $objImport->getFinalData($arrFields);

            if (empty($_POST['newsletter_recipient_associated_list'])) {
                self::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_SELECT_CATEGORY'];
            } else {
                $arrLists = array();

                if (isset($_POST['newsletter_recipient_associated_list'])) {
                    foreach (explode(',', $_POST['newsletter_recipient_associated_list']) as $listId) {
                        array_push($arrLists, intval($listId));
                    }
                }
                $EmailCount = 0;
                $arrBadEmails = array();
                $ExistEmails = 0;
                $NewEmails = 0;
                $recipientSendEmailId = (isset($_POST['sendEmail'])) ? intval($_POST['sendEmail']) : 0;
                foreach ($arrRecipients as $arrRecipient) {
                    if (empty($arrRecipient['email'])) {
                        continue;
                    }
                    if (!strpos($arrRecipient['email'],'@')) {
                        continue;
                    }

                    $arrRecipient['email'] = trim($arrRecipient['email']);
                    if (!\FWValidator::isEmail($arrRecipient['email'])) {
                        array_push($arrBadEmails, $arrRecipient['email']);
                    } else {
                        $EmailCount++;
                        $arrRecipientLists = $arrLists;

// TODO: use FWUSER
                        if (in_array($arrRecipient['salutation'], $this->_getRecipientTitles())) {
                            $arrRecipientTitles = array_flip($this->_getRecipientTitles());
                            $recipientSalutationId = $arrRecipientTitles[$arrRecipient['salutation']];
                        } else {
                            $recipientSalutationId = $this->_addRecipientTitle($arrRecipient['salutation']);
                        }

                        // try to parse the imported birthday in a usable format
                        if (!empty($arrRecipient['birthday'])) {
                            $arrDate = date_parse($arrRecipient['birthday']);
                            $arrRecipient['birthday'] = $arrDate['day'].'-'.$arrDate['month'].'-'.$arrDate['year'];
                        }

                        $objRecipient = $objDatabase->SelectLimit("SELECT `id`,
                                                                          `language`,
                                                                          `status`,
                                                                          `notes`
                                                                   FROM `".DBPREFIX."module_newsletter_user`
                                                                   WHERE `email` = '".addslashes($arrRecipient['email'])."'", 1);
                        if ($objRecipient->RecordCount() == 1) {

                            $recipientId       = $objRecipient->fields['id'];
                            $recipientLanguage = $objRecipient->fields['language'];
                            $recipientStatus   = $objRecipient->fields['status'];
                            $recipientNotes    = (!empty($objRecipient->fields['notes']) ? $objRecipient->fields['notes'].' '.$arrRecipient['notes'] : $arrRecipient['notes']);

                            $objList = $objDatabase->Execute("SELECT `category` FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE user=".$recipientId);
                            if ($objList !== false) {
                                while (!$objList->EOF) {
                                    array_push($arrRecipientLists, $objList->fields['category']);
                                    $objList->MoveNext();
                                }
                            }
                            $arrRecipientLists = array_unique($arrRecipientLists);

                            $recipientAttributeStatus = array();
                            $this->_updateRecipient($recipientAttributeStatus, $recipientId, $arrRecipient['email'], $arrRecipient['uri'], $arrRecipient['sex'],
                                            $recipientSalutationId, $arrRecipient['title'], $arrRecipient['lastname'], $arrRecipient['firstname'],
                                            $arrRecipient['position'], $arrRecipient['company'], $arrRecipient['industry_sector'],
                                            $arrRecipient['address'], $arrRecipient['zip'], $arrRecipient['city'], $arrRecipient['country_id'],
                                            $arrRecipient['phone_office'], $arrRecipient['phone_private'], $arrRecipient['phone_mobile'],
                                            $arrRecipient['fax'], $recipientNotes, $arrRecipient['birthday'], $recipientStatus, $arrRecipientLists,
                                            $recipientLanguage, $source);

                            $ExistEmails++;
                        } else {
                            $NewEmails ++;

                            if (!$this->_addRecipient($arrRecipient['email'], $arrRecipient['uri'], $arrRecipient['sex'], $recipientSalutationId, $arrRecipient['title'], $arrRecipient['lastname'], $arrRecipient['firstname'], $arrRecipient['position'], $arrRecipient['company'], $arrRecipient['industry_sector'], $arrRecipient['address'], $arrRecipient['zip'], $arrRecipient['city'], $arrRecipient['country_id'], $arrRecipient['phone_office'], $arrRecipient['phone_private'], $arrRecipient['phone_mobile'], $arrRecipient['fax'], $arrRecipient['notes'], $arrRecipient['birthday'], 1, $arrRecipientLists, $arrRecipient['language'], $source)) {
                                array_push($arrBadEmails, $arrRecipient['email']);
                            } elseif (!empty($recipientSendEmailId)) {
                                $objRecipient = $objDatabase->SelectLimit("
                                    SELECT id
                                    FROM ".DBPREFIX."module_newsletter_user
                                        WHERE email='".contrexx_input2db(
// TODO: Undefined
//                                        $recipientEmail
// Should probably be
                                        $arrRecipient['email']
                                            )."'", 1);
                                $recipientId  = $objRecipient->fields['id'];

                                $this->insertTmpEmail($recipientSendEmailId, $arrRecipient['email'], self::USER_TYPE_NEWSLETTER);
                                if ($this->SendEmail($recipientId, $recipientSendEmailId, $arrRecipient['email'], 1, self::USER_TYPE_NEWSLETTER, false) == false) {
                                    self::$strErrMessage .= $_ARRAYLANG['TXT_SENDING_MESSAGE_ERROR'];
                                } else {
// TODO: Unused
//                                    $objUpdateCount    =
                                    $objDatabase->execute('
                                        UPDATE '.DBPREFIX.'module_newsletter
                                        SET recipient_count = recipient_count+1
                                        WHERE id='.intval($recipientSendEmailId));
                                }
                            }
                        }
                    }
                }
                self::$strOkMessage = $_ARRAYLANG['TXT_DATA_IMPORT_SUCCESSFUL']."<br/>"
                                        .$_ARRAYLANG['TXT_CORRECT_EMAILS'].": ".$EmailCount."<br/>"
                                        .$_ARRAYLANG['TXT_NOT_VALID_EMAILS'].": ".implode(', ', $arrBadEmails)."<br/>"
                                        .$_ARRAYLANG['TXT_EXISTING_EMAILS'].": ".$ExistEmails."<br/>"
                                        .$_ARRAYLANG['TXT_NEW_ADDED_EMAILS'].": ".$NewEmails;

                $objImport->initFileSelectTemplate($objTpl);
                $objTpl->setVariable('IMPORT_ADD_HELP', $_ARRAYLANG['TXT_NEWSLETTER_IMPORT_SEND_MAIL_DESC']);
                $objTpl->parse('additional_tooltip');
                $objTpl->setVariable(array(
                    "IMPORT_ACTION" => "index.php?cmd=Newsletter&amp;act=users&amp;tpl=import",
                    'TXT_FILETYPE' => $_ARRAYLANG['TXT_NEWSLETTER_FILE_TYPE'],
                    'TXT_HELP' => $_ARRAYLANG['TXT_NEWSLETTER_IMPORT_HELP'],
                    'IMPORT_ADD_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_SEND_EMAIL'],
                    //'IMPORT_ADD_VALUE' => $this->CategoryDropDown(),
                    'IMPORT_ADD_VALUE' => $this->_getEmailsDropDown(),
                    'IMPORT_ROWCLASS' => 'row1'
                ));
                $objTpl->parse("additional");
                $objTpl->setVariable(array(
                    'IMPORT_ADD_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_LIST'],
                    'IMPORT_ADD_VALUE' => $this->_getAssociatedListSelection(),
                    'IMPORT_ROWCLASS' => 'row2'
                ));
                $objTpl->parse("additional");
                $this->_objTpl->setVariable('NEWSLETTER_USER_FILE', $objTpl->get());
            }
        } elseif (
            empty($_POST['importfile']) ||
            (
                isset($_POST['imported']) &&
                (
                    empty($_POST['newsletter_recipient_associated_list']) ||
                    !isset($_POST['consentConfirm'])
                )
            )
        ) {
            // Dateiauswahldialog. Siehe Fileselect
            $this->_pageTitle = $_ARRAYLANG['TXT_IMPORT'];
            $this->_objTpl->addBlockfile('NEWSLETTER_USER_FILE', 'module_newsletter_user_import', 'module_newsletter_user_import.html');

            if (isset($_POST['imported'])) {
                $arrStatusMessage = array();
                if (empty($_POST['newsletter_recipient_associated_list'])) {
                    $arrStatusMessage[] = $_ARRAYLANG['TXT_NEWSLETTER_SELECT_CATEGORY'];
                }
                if (!isset($_POST['consentConfirm'])) {
                    $arrStatusMessage[] = $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_MESSAGE_ERROR'];
                }
                self::$strErrMessage = implode('<br />', $arrStatusMessage);
            }

            $objImport->initFileSelectTemplate($objTpl);
            $objTpl->setVariable('IMPORT_ADD_HELP', $_ARRAYLANG['TXT_NEWSLETTER_IMPORT_SEND_MAIL_DESC']);
            $objTpl->parse('additional_tooltip');
            $objTpl->setVariable(array(
                "IMPORT_ACTION" => "index.php?cmd=Newsletter&amp;act=users&amp;tpl=import",
                'TXT_FILETYPE' => $_ARRAYLANG['TXT_NEWSLETTER_FILE_TYPE'],
                'TXT_HELP' => $_ARRAYLANG['TXT_NEWSLETTER_IMPORT_HELP'],
                'IMPORT_ADD_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_SEND_EMAIL'],
                //'IMPORT_ADD_VALUE' => $this->CategoryDropDown(),
                'IMPORT_ADD_VALUE' => $this->_getEmailsDropDown(),
                'IMPORT_ROWCLASS' => 'row1'
            ));
            $objTpl->parse("additional");
            $objTpl->setVariable(array(
                'IMPORT_ADD_NAME' => $_ARRAYLANG['TXT_NEWSLETTER_LIST'],
                'IMPORT_ADD_VALUE' => $this->_getAssociatedListSelection(),
                'IMPORT_ROWCLASS' => 'row2'
            ));
            $objTpl->parse("additional");
            // Get consent confirm html
            $objTpl->setVariable(array(
                'IMPORT_ADD_NAME'  => $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_CONFIRM_IMPORT'],
                'IMPORT_ADD_VALUE' => \Html::getCheckbox(
                    'consentConfirm',
                    1,
                    'consentConfirmImport',
                    isset($_POST['importfile']) && isset($_POST['consentConfirm'])
                ),
                'IMPORT_ROWCLASS'  => 'row1',
            ));
            $objTpl->parse("additional");
            $this->_objTpl->setVariable(array(
                'TXT_NEWSLETTER_IMPORT_FROM_FILE' => $_ARRAYLANG['TXT_NEWSLETTER_IMPORT_FROM_FILE'],
                'TXT_IMPORT' => $_ARRAYLANG['TXT_IMPORT'],
                'TXT_NEWSLETTER_LIST' => $_ARRAYLANG['TXT_NEWSLETTER_LIST'],
                'TXT_ENTER_EMAIL_ADDRESS' => $_ARRAYLANG['TXT_ENTER_EMAIL_ADDRESS'],
                'NEWSLETTER_CATEGORY_MENU' => $this->_getAssociatedListSelection(),
                'NEWSLETTER_IMPORT_FRAME' => $objTpl->get(),
                'TXT_NEWSLETTER_CONSENT_CONFIRM_IMPORT' => $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_CONFIRM_IMPORT'],
            ));

            if (isset($_POST['newsletter_import_plain'])) {
                if (empty($_POST['newsletter_recipient_associated_list'])) {
                    self::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_SELECT_CATEGORY'];
                } elseif (!isset($_POST['consentConfirm'])) {
                    self::$strErrMessage = $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_MESSAGE_ERROR'];
                } else {
                    $arrLists = array();

                    if (isset($_POST['newsletter_recipient_associated_list'])) {
                        foreach ($_POST['newsletter_recipient_associated_list'] as $listId) {
                            array_push($arrLists, intval($listId));
                        }
                    }
                    $EmailList = str_replace(array(']','[',"\t","\n","\r"), ' ', $_REQUEST["Emails"]);
                    $EmailArray = preg_split('/[\s"\';,:<>\n]+/', contrexx_stripslashes($EmailList));
                    $EmailCount = 0;
                    $arrBadEmails = array();
                    $ExistEmails = 0;
                    $NewEmails = 0;
                    foreach ($EmailArray as $email) {
                        if (empty($email)) continue;
                        if (!strpos($email, '@')) continue;
                        if (!\FWValidator::isEmail($email)) {
                            array_push($arrBadEmails, $email);
                        } else {
                            $EmailCount++;
                            $objRecipient = $objDatabase->SelectLimit("SELECT `id` FROM `".DBPREFIX."module_newsletter_user` WHERE `email` = '".addslashes($email)."'", 1);
                            if ($objRecipient->RecordCount() == 1) {
                                static::_setRecipientLists(
                                    $objRecipient->fields['id'],
                                    $arrLists,
                                    $source
                                );
                                $ExistEmails++;
                            } else {
                                $NewEmails ++;
                                if ($objDatabase->Execute("
                                    INSERT INTO `".DBPREFIX."module_newsletter_user` (
                                        `code`,
                                        `email`,
                                        `status`,
                                        `emaildate`,
                                        `source`
                                    ) VALUES (
                                        '". $this->_emailCode() ."',
                                        '". addslashes($email) ."',
                                        1,
                                        '". time() ."',
                                        '". $source ."'
                                    )"
                                ) !== false) {
                                    static::_setRecipientLists(
                                        $objDatabase->Insert_ID(),
                                        $arrLists,
                                        $source
                                    );
                                } else {
                                    array_push($arrBadEmails, $email);
                                }
                            }
                        }
                    }
                    self::$strOkMessage = $_ARRAYLANG['TXT_DATA_IMPORT_SUCCESSFUL']."<br/>".$_ARRAYLANG['TXT_CORRECT_EMAILS'].": ".$EmailCount."<br/>".$_ARRAYLANG['TXT_NOT_VALID_EMAILS'].": &quot;".implode(', ', $arrBadEmails)."&quot;<br/>".$_ARRAYLANG['TXT_EXISTING_EMAILS'].": ".$ExistEmails."<br/>".$_ARRAYLANG['TXT_NEW_ADDED_EMAILS'].": ".$NewEmails;
                }
            }
            $this->_objTpl->parse('module_newsletter_user_import');
        } else {
            // Felderzuweisungsdialog. Siehe Fieldselect
            $objImport->initFieldSelectTemplate($objTpl, $arrFields);

            $arrLists = array();
            if (isset($_POST['newsletter_recipient_associated_list'])) {
                foreach ($_POST['newsletter_recipient_associated_list'] as $listId) {
                    array_push($arrLists, intval($listId));
                }
            }

            $objTpl->setVariable(array(
                'IMPORT_HIDDEN_NAME' => 'newsletter_recipient_associated_list',
                'IMPORT_HIDDEN_VALUE' =>
                    (!empty($arrLists) ? implode(',', $arrLists) : ''),
            ));
            $objTpl->parse('hidden_fields');
            $objTpl->setVariable(array(
                'IMPORT_HIDDEN_NAME' => 'sendEmail',
                'IMPORT_HIDDEN_VALUE' => (isset($_POST['sendEmail']) ? intval($_POST['sendEmail']) : 0),
            ));
            $objTpl->parse('hidden_fields');
            $objTpl->setVariable(array(
                'IMPORT_ACTION' => 'index.php?cmd=Newsletter&amp;act=users&amp;tpl=import',
            ));

            $this->_objTpl->setVariable(array(
                'TXT_REMOVE_PAIR' => $_ARRAYLANG['TXT_REMOVE_PAIR'],
                'NEWSLETTER_USER_FILE' => $objTpl->get(),
            ));
        }
    }

    function _getEmailsDropDown($name='sendEmail', $selected=0, $attrs='')
    {
        global $objDatabase, $_ARRAYLANG;

        $objNewsletterMails = $objDatabase->Execute('SELECT
                                                      id,
                                                      subject
                                                      FROM '.DBPREFIX.'module_newsletter
                                                      ORDER BY status, id DESC');


        $ReturnVar = '<select name="'.$name.'"'.(!empty($attrs) ? ' '.$attrs : '').'>
        <option value="0">'.$_ARRAYLANG['TXT_NEWSLETTER_DO_NOT_SEND_EMAIL'].'</option>';

        if ($objNewsletterMails !== false) {
            while (!$objNewsletterMails->EOF) {
                $ReturnVar .= '<option value="'.$objNewsletterMails->fields['id'].'"'.($objNewsletterMails->fields['id'] == $selected ? 'selected="selected"' : '').'>'.contrexx_raw2xhtml($objNewsletterMails->fields['subject']).'</option>';
                $objNewsletterMails->MoveNext();
            }
        }
        $ReturnVar .= '</select>';

        return $ReturnVar;
    }

    function _getAssociatedListSelection()
    {
        global $_ARRAYLANG;

        $arrLists = self::getLists();
// TODO: Unused
//        $listNr = 1;
        $lists = '';
        foreach ($arrLists as $listId => $arrList) {
// TODO: Unused
//            $column = $listNr % 3;
            $lists .= ' <div style="float:left;width:33%;">
                            <input type="checkbox"
                                 name="newsletter_recipient_associated_list['.intval($listId).']"
                                 id="newsletter_mail_associated_list_'.intval($listId).'"
                                 value="'.intval($listId).'" />
                            <a href="index.php?cmd=Newsletter&amp;act=users&amp;newsletterListId='.intval($listId).'"
                               target="_blank" title="'.sprintf($_ARRAYLANG['TXT_NEWSLETTER_SHOW_RECIPIENTS_OF_LIST'], contrexx_raw2xhtml($arrList['name'])).'">
                                   '.contrexx_raw2xhtml($arrList['name']).'
                            </a>
                        </div>';
        }
        return $lists;
    }
    /**
     * delete all inactice recipients
     * @return void
     */
    function _deleteInactiveRecipients()
    {
        global $objDatabase, $_ARRAYLANG;

        $count = 0;
        if ( ($objRS = $objDatabase->Execute('SELECT `id` FROM `'.DBPREFIX.'module_newsletter_user` WHERE `status` = 0 ')) !== false ) {
            while(!$objRS->EOF) {
                $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'module_newsletter_user` WHERE `id` = '. $objRS->fields['id']);
                $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'module_newsletter_rel_user_cat` WHERE `user` = '. $objRS->fields['id']);
                $objRS->MoveNext();
                $count++;
            }
            self::$strOkMessage = $_ARRAYLANG['TXT_NEWSLETTER_INACTIVE_RECIPIENTS_SUCCESSFULLY_DELETED'] . ' ( '. $count .' )';
        } else {
            self::$strErrMessage = $_ARRAYLANG['TXT_DATABASE_ERROR'] . $objDatabase->ErrorMsg();
        }
    }


    function _users()
    {
        global $_ARRAYLANG;

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENTS'];
        $this->_objTpl->loadTemplateFile('module_newsletter_user.html');
        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_OVERVIEW' => $_ARRAYLANG['TXT_NEWSLETTER_OVERVIEW'],
            'TXT_NEWSLETTER_IMPORT' => $_ARRAYLANG['TXT_NEWSLETTER_IMPORT'],
            'TXT_NEWSLETTER_EXPORT' => $_ARRAYLANG['TXT_NEWSLETTER_EXPORT'],
            'TXT_NEWSLETTER_ADD_USER' => $_ARRAYLANG['TXT_NEWSLETTER_ADD_USER'],
        ));

        if (!isset($_REQUEST['tpl'])) {
            $_REQUEST['tpl'] = '';
        }
        $arrSettings = $this->_getSettings();
        if (
            $_REQUEST['tpl'] == 'feedback' &&
            !$arrSettings['statistics']['setvalue']
        ) {
            $_REQUEST['tpl'] = '';
        }
        switch ($_REQUEST['tpl']) {
            case 'edit':
                $this->_editUser();
                break;
            case 'import':
                $this->importuser();
                break;
            case 'export':
                $this->exportuser();
                break;
            case 'feedback':
                $this->_showRecipientFeedbackAnalysis();
                break;
            default:
                $this->_userList();
                break;
        }
    }


    function UserCount()
    {
        global $objDatabase;

        $objResult_value = $objDatabase->Execute("
            SELECT COUNT(*) AS `counter`
              FROM ".DBPREFIX."module_newsletter_user");
        if ($objResult_value !== false && !$objResult_value->EOF) {
            return $objResult_value->fields["counter"];
        }
        return 0;
    }


    function NewsletterSendCount()
    {
        global $objDatabase;

        $objResult_value = $objDatabase->Execute("
            SELECT COUNT(*) AS `counter`
              FROM ".DBPREFIX."module_newsletter
             WHERE status=1");
        if ($objResult_value && !$objResult_value->EOF) {
            return $objResult_value->fields["counter"];
        }
        return 0;
    }


    function NewsletterNotSendCount()
    {
        global $objDatabase;

        $objResult_value = $objDatabase->Execute("
            SELECT COUNT(*) AS `counter`
              FROM ".DBPREFIX."module_newsletter
             WHERE status=0");
        if ($objResult_value && !$objResult_value->EOF) {
            return $objResult_value->fields["counter"];
        }
        return 0;
    }

    function _editUser()
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        \JS::registerJS('modules/Newsletter/View/Script/Backend.js');
        \JS::registerCSS('modules/Newsletter/View/Style/Backend.css');

        // Store the language interface text in javascript variable
        \ContrexxJavascript::getInstance()->setVariable(
           'NEWSLETTER_CONSENT_CONFIRM_ERROR',
            $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_MESSAGE_ERROR'],
            'Newsletter'
        );

        $activeFrontendlang = \FWLanguage::getActiveFrontendLanguages();

        $copy = isset($_REQUEST['copy']) && $_REQUEST['copy'] == 1;
        $recipientId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $recipientEmail = '';
        $recipientUri = '';
        $recipientSex = '';
        $recipientSalutation = 0;
        $recipientTitle = '';
        $recipientPosition = '';
        $recipientIndustrySector = '';
        $recipientPhoneMobile = '';
        $recipientPhonePrivate = '';
        $recipientFax = '';
        $recipientNotes = '';
        $recipientLastname = '';
        $recipientFirstname = '';
        $recipientCompany = '';
        $recipientAddress = '';
        $recipientZip = '';
        $recipientCity = '';
        $recipientCountry = '';
        $recipientPhoneOffice = '';
        $recipientBirthday = '';
        $recipientLanguage = (count($activeFrontendlang) == 1) ? key($activeFrontendlang) : '';
        $recipientStatus = (isset($_POST['newsletter_recipient_status'])) ? 1 : (empty($_POST) ? 1 : 0);
        $arrAssociatedLists = array();
        $recipientSendEmailId = isset($_POST['sendEmail']) ? intval($_POST['sendEmail']) : 0;
        $recipientSendMailDisplay = false;
        $source = 'backend';

        if (isset($_POST['newsletter_recipient_email'])) {
            $recipientEmail = $_POST['newsletter_recipient_email'];
        }
        if (isset($_POST['newsletter_recipient_uri'])) {
            $recipientUri = $_POST['newsletter_recipient_uri'];
        }
        if (isset($_POST['newsletter_recipient_sex'])) {
            $recipientSex = in_array($_POST['newsletter_recipient_sex'], array('f', 'm')) ? $_POST['newsletter_recipient_sex'] : '';
        }
        if (isset($_POST['newsletter_recipient_salutation'])) {
// TODO: use FWUSER
            $arrRecipientSalutation = $this->_getRecipientTitles();
            $recipientSalutation = in_array($_POST['newsletter_recipient_salutation'], array_keys($arrRecipientSalutation)) ? intval($_POST['newsletter_recipient_salutation']) : 0;
        }
        if (isset($_POST['newsletter_recipient_lastname'])) {
            $recipientLastname = $_POST['newsletter_recipient_lastname'];
        }
        if (isset($_POST['newsletter_recipient_firstname'])) {
            $recipientFirstname = $_POST['newsletter_recipient_firstname'];
        }
        if (isset($_POST['newsletter_recipient_company'])) {
            $recipientCompany = $_POST['newsletter_recipient_company'];
        }
        if (isset($_POST['newsletter_recipient_address'])) {
            $recipientAddress = $_POST['newsletter_recipient_address'];
        }
        if (isset($_POST['newsletter_recipient_zip'])) {
            $recipientZip = $_POST['newsletter_recipient_zip'];
        }
        if (isset($_POST['newsletter_recipient_city'])) {
            $recipientCity = $_POST['newsletter_recipient_city'];
        }
        if (isset($_POST['newsletter_country_id'])) {
            $recipientCountry = $_POST['newsletter_country_id'];
        }
        if (isset($_POST['newsletter_recipient_phone_office'])) {
            $recipientPhoneOffice = $_POST['newsletter_recipient_phone_office'];
        }
        if (isset($_POST['newsletter_recipient_notes'])) {
            $recipientNotes = $_POST['newsletter_recipient_notes'];
        }
        if (isset($_POST['day']) && isset($_POST['month']) && isset($_POST['year'])) {
            $recipientBirthday = str_pad(intval($_POST['day']),2,'0',STR_PAD_LEFT).'-'.str_pad(intval($_POST['month']),2,'0',STR_PAD_LEFT).'-'.intval($_POST['year']);
        }
        if (isset($_POST['newsletter_recipient_title'])) {
            $recipientTitle = $_POST['newsletter_recipient_title'];
        }
        if (isset($_POST['newsletter_recipient_position'])) {
            $recipientPosition = $_POST['newsletter_recipient_position'];
        }
        if (isset($_POST['newsletter_recipient_industry_sector'])) {
            $recipientIndustrySector = $_POST['newsletter_recipient_industry_sector'];
        }
        if (isset($_POST['newsletter_recipient_phone_mobile'])) {
            $recipientPhoneMobile = $_POST['newsletter_recipient_phone_mobile'];
        }
        if (isset($_POST['newsletter_recipient_phone_private'])) {
            $recipientPhonePrivate = $_POST['newsletter_recipient_phone_private'];
        }
        if (isset($_POST['newsletter_recipient_fax'])) {
            $recipientFax = $_POST['newsletter_recipient_fax'];
        }
        if (isset($_POST['language'])) {
            $recipientLanguage = $_POST['language'];
        }

        if (isset($_POST['newsletter_recipient_associated_list'])) {
            foreach ($_POST['newsletter_recipient_associated_list'] as $listId => $status) {
                if (intval($status) == 1) {
                    array_push($arrAssociatedLists, intval($listId));
                }
            }
        }

        // Get interface settings
        $objInterface = $objDatabase->Execute('SELECT `setvalue`
                                                FROM `'.DBPREFIX.'module_newsletter_settings`
                                                WHERE `setname` = "recipient_attribute_status"');
        $recipientAttributeStatus = json_decode($objInterface->fields['setvalue'], true);

        if (isset($_POST['newsletter_recipient_save'])) {
            $objValidator = new \FWValidator();
            if ($objValidator->isEmail($recipientEmail)) {
                if (isset($_POST['consentConfirm'])) {
                    if ($this->_validateRecipientAttributes($recipientAttributeStatus, $recipientUri, $recipientSex, $recipientSalutation, $recipientTitle, $recipientLastname, $recipientFirstname, $recipientPosition, $recipientCompany, $recipientIndustrySector, $recipientAddress, $recipientZip, $recipientCity, $recipientCountry, $recipientPhoneOffice, $recipientPhonePrivate, $recipientPhoneMobile, $recipientFax, $recipientBirthday)) {
                        if ($this->_isUniqueRecipientEmail($recipientEmail, $recipientId, $copy)) {
                            //reset the $recipientId on copy function
                            $recipientId = $copy ? 0 : $recipientId;
                            if ($recipientId > 0) {
                                if ($this->_updateRecipient($recipientAttributeStatus, $recipientId, $recipientEmail, $recipientUri, $recipientSex, $recipientSalutation, $recipientTitle, $recipientLastname, $recipientFirstname, $recipientPosition, $recipientCompany, $recipientIndustrySector, $recipientAddress, $recipientZip, $recipientCity, $recipientCountry, $recipientPhoneOffice, $recipientPhonePrivate, $recipientPhoneMobile, $recipientFax, $recipientNotes, $recipientBirthday, $recipientStatus, $arrAssociatedLists, $recipientLanguage, $source)) {
                                    self::$strOkMessage .= $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_UPDATED_SUCCESSFULLY'];
                                    return $this->_userList();
                                } else {
                                    // fall back to old recipient id, if any error occurs on copy
                                    $recipientId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
                                    self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_ERROR_UPDATE_RECIPIENT'];
                                }
                            } else {
                                if ($this->_addRecipient($recipientEmail, $recipientUri, $recipientSex, $recipientSalutation, $recipientTitle, $recipientLastname, $recipientFirstname, $recipientPosition, $recipientCompany, $recipientIndustrySector, $recipientAddress, $recipientZip, $recipientCity, $recipientCountry, $recipientPhoneOffice, $recipientPhonePrivate, $recipientPhoneMobile, $recipientFax, $recipientNotes, $recipientBirthday, $recipientStatus, $arrAssociatedLists, $recipientLanguage, $source)) {
                                    if (!empty($recipientSendEmailId)) {
                                        $objRecipient = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_user WHERE email='".contrexx_input2db($recipientEmail)."'", 1);
                                        $recipientId  = $objRecipient->fields['id'];

                                        $this->insertTmpEmail($recipientSendEmailId, $recipientEmail, self::USER_TYPE_NEWSLETTER);
                                        if ($this->SendEmail($recipientId, $recipientSendEmailId, $recipientEmail, 1, self::USER_TYPE_NEWSLETTER, false) == false) {
                                            // fall back to old recipient id, if any error occurs on copy
                                            $recipientId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
                                            self::$strErrMessage .= $_ARRAYLANG['TXT_SENDING_MESSAGE_ERROR'];
                                        } else {
                                            $objRecipientCount = $objDatabase->execute('SELECT subject FROM '.DBPREFIX.'module_newsletter WHERE id='.intval($recipientSendEmailId));
                                            $newsTitle         = $objRecipientCount->fields['subject'];
    // TODO: Unused
    //                                        $objUpdateCount    =
                                            $objDatabase->execute('
                                                UPDATE '.DBPREFIX.'module_newsletter
                                                SET recipient_count = recipient_count+1
                                                WHERE id='.intval($recipientSendEmailId));
                                            self::$strOkMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_MAIL_SEND_SUCCESSFULLY'].'<br />', '<strong>'.$newsTitle.'</strong>');
                                        }
                                    }
                                    self::$strOkMessage .= $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_SAVED_SUCCESSFULLY'];
                                    return $this->_userList();
                                } else {
                                    // fall back to old recipient id, if any error occurs on copy
                                    $recipientId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
                                    self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_ERROR_SAVE_RECIPIENT'];
                                }
                            }
                        } elseif (empty($recipientId)) {
                            $objRecipient      = $objDatabase->SelectLimit("SELECT id, language, status, notes FROM ".DBPREFIX."module_newsletter_user WHERE email='".contrexx_input2db($recipientEmail)."'", 1);
                            $recipientId       = $objRecipient->fields['id'];
                            $recipientLanguage = $objRecipient->fields['language'];
                            $recipientStatus   = $objRecipient->fields['status'];
                            $recipientNotes    = (!empty($objRecipient->fields['notes']) ? $objRecipient->fields['notes'].' '.$recipientNotes : $recipientNotes);

                            $objList = $objDatabase->Execute("SELECT category FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE user=".$recipientId);
                            if ($objList !== false) {
                                while (!$objList->EOF) {
                                    array_push($arrAssociatedLists, $objList->fields['category']);
                                    $objList->MoveNext();
                                }
                            }
                            $arrAssociatedLists = array_unique($arrAssociatedLists);

                            // set all attributes status to false to set the omitEmpty value to true
                            foreach ($recipientAttributeStatus as $attribute => $value) {
                                $recipientAttributeStatus[$attribute]['active'] = false;
                            }

                            if ($this->_updateRecipient($recipientAttributeStatus, $recipientId, $recipientEmail, $recipientUri, $recipientSex, $recipientSalutation, $recipientTitle, $recipientLastname, $recipientFirstname, $recipientPosition, $recipientCompany, $recipientIndustrySector, $recipientAddress, $recipientZip, $recipientCity, $recipientCountry, $recipientPhoneOffice, $recipientPhonePrivate, $recipientPhoneMobile, $recipientFax, $recipientNotes, $recipientBirthday, $recipientStatus, $arrAssociatedLists, $recipientLanguage, $source)) {
                                self::$strOkMessage .= $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_UPDATED_SUCCESSFULLY'];
                                self::$strOkMessage .= $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_NO_EMAIL_SENT'];
                                return $this->_userList();
                            } else {
                                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_ERROR_UPDATE_RECIPIENT'];
                            }
                        } else {
                            //reset the $recipientId on copy function
                            $objResult = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_user WHERE email='".contrexx_input2db($recipientEmail)."' AND id!=".($copy ? 0 : $recipientId), 1);
                            self::$strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWSLETTER_ERROR_EMAIL_ALREADY_EXISTS'], '<a href="index.php?cmd=Newsletter&amp;act=users&amp;tpl=edit&amp;id=' . $objResult->fields['id'] . '" target="_blank">' . $_ARRAYLANG['TXT_NEWSLETTER_ERROR_EMAIL_ALREADY_EXISTS_CLICK_HERE'] . '</a>');
                        }
                    } else {
                        self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_MANDATORY_FIELD_ERROR'];
                    }
                } else {
                    self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_MESSAGE_ERROR'];
                }
            } else {
                self::$strErrMessage .= $_ARRAYLANG['TXT_NEWSLETTER_INVALIDE_EMAIL_ADDRESS'];
            }
        } elseif ($recipientId > 0) {
            $objRecipient = $objDatabase->SelectLimit("SELECT email, uri, sex, salutation, title, lastname, firstname, position, company, industry_sector, address, zip, city, country_id, phone_office, phone_private, phone_mobile, fax, notes, birthday, status, language FROM ".DBPREFIX."module_newsletter_user WHERE id=".$recipientId, 1);
            if ($objRecipient !== false && $objRecipient->RecordCount() == 1) {
                $recipientEmail = $objRecipient->fields['email'];
                $recipientUri = $objRecipient->fields['uri'];
                $recipientSex = $objRecipient->fields['sex'];
                $recipientSalutation = $objRecipient->fields['salutation'];
                $recipientTitle = $objRecipient->fields['title'];
                $recipientLastname = $objRecipient->fields['lastname'];
                $recipientFirstname = $objRecipient->fields['firstname'];
                $recipientPosition = $objRecipient->fields['position'];
                $recipientCompany = $objRecipient->fields['company'];
                $recipientIndustrySector = $objRecipient->fields['industry_sector'];
                $recipientAddress = $objRecipient->fields['address'];
                $recipientZip = $objRecipient->fields['zip'];
                $recipientCity = $objRecipient->fields['city'];
                $recipientCountry = $objRecipient->fields['country_id'];
                $recipientPhoneOffice = $objRecipient->fields['phone_office'];
                $recipientPhonePrivate = $objRecipient->fields['phone_private'];
                $recipientPhoneMobile = $objRecipient->fields['phone_mobile'];
                $recipientFax = $objRecipient->fields['fax'];
                $recipientBirthday = $objRecipient->fields['birthday'];
                $recipientLanguage = $objRecipient->fields['language'];
                $recipientStatus = $objRecipient->fields['status'];
                $recipientNotes = $objRecipient->fields['notes'];

                $objList = $objDatabase->Execute("SELECT category FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE user=".$recipientId);
                if ($objList !== false) {
                    while (!$objList->EOF) {
                        array_push($arrAssociatedLists, $objList->fields['category']);
                        $objList->MoveNext();
                    }
                }
            } else {
                return $this->_userList();
            }
        }

        $this->_pageTitle = $recipientId > 0 && !$copy ? $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_RECIPIENT'] : $_ARRAYLANG['TXT_NEWSLETTER_ADD_NEW_RECIPIENT'];
        $this->_objTpl->addBlockfile('NEWSLETTER_USER_FILE', 'module_newsletter_user_edit', 'module_newsletter_user_edit.html');
        $this->_objTpl->setVariable('TXT_NEWSLETTER_USER_TITLE', $recipientId > 0 && !$copy ? $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_RECIPIENT'] : $_ARRAYLANG['TXT_NEWSLETTER_ADD_NEW_RECIPIENT']);

        $this->_createDatesDropdown($recipientBirthday);

        $arrLists = self::getLists();
        $listNr = 0;
        $query = '
            SELECT
                `category`,
                `source`,
                `consent`
            FROM
                `' . DBPREFIX . 'module_newsletter_rel_user_cat`
            WHERE
                `user` = ' . $recipientId . '
        ';
        $consentResult = $objDatabase->Execute($query);
        $consent = array();
        while (!$consentResult->EOF) {
            $consent[$consentResult->fields['category']] = array(
                'source' => $consentResult->fields['source'],
                'consent' => $consentResult->fields['consent'],
            );
            $consentResult->MoveNext();
        }
        foreach ($arrLists as $listId => $arrList) {
            $column = $listNr % 3;
            $this->_objTpl->setVariable(array(
                'NEWSLETTER_LIST_ID' => $listId,
                'NEWSLETTER_LIST_NAME' => contrexx_raw2xhtml($arrList['name']),
                'NEWSLETTER_SHOW_RECIPIENTS_OF_LIST_TXT' => sprintf($_ARRAYLANG['TXT_NEWSLETTER_SHOW_RECIPIENTS_OF_LIST'], contrexx_raw2xhtml($arrList['name'])),
                'NEWSLETTER_LIST_ASSOCIATED' => in_array($listId, $arrAssociatedLists) ? 'checked="checked"' : ''
            ));
            if (isset($consent[$listId])) {
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_CONSENT' => static::parseConsentView(
                        $consent[$listId]['source'],
                        $consent[$listId]['consent']
                    ),
                ));
            }
            $this->_objTpl->parse('newsletter_mail_associated_list_'.$column);
            $listNr++;
        }

        if (count($activeFrontendlang) > 1) {
            foreach ($activeFrontendlang as $lang) {
                $selected = ($lang['id'] == $recipientLanguage) ? 'selected="selected"' : '';

                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_LANGUAGE_ID'        => contrexx_raw2xhtml($lang['id']),
                    'NEWSLETTER_LANGUAGE_NAME'      => contrexx_raw2xhtml($lang['name']),
                    'NEWSLETTER_LANGUAGES_SELECTED' => $selected
                ));
                $this->_objTpl->parse('languages');
            }
            $languageOptionDisplay = true;
        } else {
            $this->_objTpl->hideBlock('languageOption');
        }

        if (empty($recipientId) || $copy) {
            $objNewsletterMails = $objDatabase->Execute('SELECT
                                                      id,
                                                      subject
                                                      FROM '.DBPREFIX.'module_newsletter
                                                      ORDER BY status, id DESC');

            while (!$objNewsletterMails->EOF) {

                $selected = ($recipientSendEmailId == $objNewsletterMails->fields['id']) ? 'selected="selected"' : '';

                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_EMAIL_ID'       => contrexx_raw2xhtml($objNewsletterMails->fields['id']),
                    'NEWSLETTER_EMAIL_NAME'     => contrexx_raw2xhtml($objNewsletterMails->fields['subject']),
                    'NEWSLETTER_EMAIL_SELECTED' => $selected
                ));
                $this->_objTpl->parse('allMails');
                $objNewsletterMails->MoveNext();
            }
            $recipientSendMailDisplay = true;
        } else {
            $this->_objTpl->hideBlock('sendEmail');
        }

        // Display settings recipient general attributes

        $sendMailRowClass = ($languageOptionDisplay) ? 'row2' : 'row1';

        if ($languageOptionDisplay && $recipientSendMailDisplay) {
            $associatedListRowClass = 'row1';
        } elseif ($languageOptionDisplay || $recipientSendMailDisplay) {
            $associatedListRowClass = 'row2';
        } else {
            $associatedListRowClass = 'row1';
        }

        $recipientNotesRowClass = ($associatedListRowClass == 'row1') ? 'row2' : 'row1';

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_SEND_EMAIL_ROWCLASS'       => $sendMailRowClass,
            'NEWSLETTER_ASSOCIATED_LISTS_ROWCLASS' => $associatedListRowClass,
            'NEWSLETTER_NOTES_ROWCLASS'            => $recipientNotesRowClass
        ));


        //display settings recipient profile detials
        $recipientAttributeDisplay = false;
        foreach ($recipientAttributeStatus as $value) {
            if ($value['active']) {
                $recipientAttributeDisplay = true;
                break;
            }
        }

        $profileRowCount = 0;
        $recipientAttributesArray = array(
            'recipient_sex',
            'recipient_salutation',
            'recipient_title',
            'recipient_firstname',
            'recipient_lastname',
            'recipient_position',
            'recipient_company',
            'recipient_industry',
            'recipient_address',
            'recipient_city',
            'recipient_zip',
            'recipient_country',
            'recipient_phone',
            'recipient_private',
            'recipient_mobile',
            'recipient_fax',
            'recipient_birthday',
            'recipient_website'
            );
        if ($recipientAttributeDisplay) {
            foreach ($recipientAttributesArray as $attribute) {
                if ($recipientAttributeStatus[$attribute]['active'] && $this->_objTpl->blockExists($attribute)) {
                    $this->_objTpl->touchBlock($attribute);
                    $this->_objTpl->setVariable(array(
                        'NEWSLETTER_'.strtoupper($attribute).'_ROW_CLASS' => ($profileRowCount%2 == 0) ? 'row2' : 'row1',
                        'NEWSLETTER_'.strtoupper($attribute).'_MANDATORY' => ($recipientAttributeStatus[$attribute]['required']) ? '*' : '',
                    ));
                    $profileRowCount++;
                } else {
                    $this->_objTpl->hideBlock($attribute);
                }
            }
        } else {
            $this->_objTpl->hideBlock('recipientProfileAttributes');
        }

        $filterParams =
            (!empty($_GET['newsletterListId']) ? '&newsletterListId='.contrexx_input2raw($_GET['newsletterListId']) : '').
            (!empty($_GET['filterkeyword']) ? '&filterkeyword='.contrexx_input2raw($_GET['filterkeyword']) : '').
            (!empty($_GET['filterattribute']) ? '&filterattribute='.contrexx_input2raw($_GET['filterattribute']) : '').
            (!empty($_GET['filterStatus']) ? '&filterStatus='.contrexx_input2raw($_GET['filterStatus']) : '')
        ;

        $this->_objTpl->setVariable(array(
            'NEWSLETTER_RECIPIENT_ID'           => $recipientId,
            'NEWSLETTER_RECIPIENT_EMAIL'        => htmlentities($recipientEmail, ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_NEWSLETTER_STATUS'             => $_ARRAYLANG['TXT_NEWSLETTER_STATUS'],
            'TXT_NEWSLETTER_LANGUAGE'           => $_ARRAYLANG['TXT_NEWSLETTER_LANGUAGE'],
            'TXT_NEWSLETTER_SEND_EMAIL'         => $_ARRAYLANG['TXT_NEWSLETTER_SEND_EMAIL'],
            'TXT_NEWSLETTER_ASSOCIATED_LISTS'   => $_ARRAYLANG['TXT_NEWSLETTER_ASSOCIATED_LISTS'],
            'TXT_NEWSLETTER_NOTES'              => $_ARRAYLANG['TXT_NEWSLETTER_NOTES'],
            'TXT_NEWSLETTER_PROFILE'            => $_ARRAYLANG['TXT_NEWSLETTER_PROFILE'],
            'TXT_NEWSLETTER_POSITION'           => $_ARRAYLANG['TXT_NEWSLETTER_POSITION'],
            'TXT_NEWSLETTER_INDUSTRY_SECTOR'    => $_ARRAYLANG['TXT_NEWSLETTER_INDUSTRY_SECTOR'],
            'TXT_NEWSLETTER_PHONE_MOBILE'       => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_MOBILE'],
            'TXT_NEWSLETTER_PHONE_PRIVATE'      => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_PRIVATE'],
            'TXT_NEWSLETTER_FAX'                => $_ARRAYLANG['TXT_NEWSLETTER_FAX'],

            'NEWSLETTER_RECIPIENT_STATUS'       => $recipientStatus == '1' ? 'checked="checked"' : '',
            'NEWSLETTER_RECIPIENT_NOTES'        => htmlentities($recipientNotes, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_URI'          => htmlentities($recipientUri, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_FEMALE'       => $recipientSex == 'f' ? 'checked="checked"' : '',
            'NEWSLETTER_RECIPIENT_MALE'         => $recipientSex == 'm' ? 'checked="checked"' : '',
            'NEWSLETTER_RECIPIENT_SALUTATION'   => $this->_getRecipientTitleMenu($recipientSalutation, 'name="newsletter_recipient_salutation" style="width:296px" size="1"'),
            'NEWSLETTER_RECIPIENT_TITLE'        => htmlentities($recipientTitle, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_FIRSTNAME'    => htmlentities($recipientFirstname, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_LASTNAME'     => htmlentities($recipientLastname, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_POSITION'     => htmlentities($recipientPosition, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_COMPANY'      => htmlentities($recipientCompany, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_INDUSTRY_SECTOR' => htmlentities($recipientIndustrySector, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_ADDRESS'      => htmlentities($recipientAddress, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_ZIP'          => htmlentities($recipientZip, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_CITY'         => htmlentities($recipientCity, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_COUNTRY'      => $this->getCountryMenu($recipientCountry, ($recipientAttributeStatus['recipient_country']['active']  && $recipientAttributeStatus['recipient_country']['required'])),
            'NEWSLETTER_RECIPIENT_PHONE'        => htmlentities($recipientPhoneOffice, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_PHONE_MOBILE' => htmlentities($recipientPhoneMobile, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_PHONE_PRIVATE' => htmlentities($recipientPhonePrivate, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_FAX'          => htmlentities($recipientFax, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_BIRTHDAY'     => htmlentities($recipientBirthday, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWSLETTER_RECIPIENT_COPY'         => $copy ? 1 : 0,

            'TXT_NEWSLETTER_EMAIL_ADDRESS'  => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'],
            'TXT_NEWSLETTER_WEBSITE'        => $_ARRAYLANG['TXT_NEWSLETTER_WEBSITE'],
            'TXT_NEWSLETTER_SALUTATION'     => $_ARRAYLANG['TXT_NEWSLETTER_SALUTATION'],
            'TXT_NEWSLETTER_TITLE'          => $_ARRAYLANG['TXT_NEWSLETTER_TITLE'],
            'TXT_NEWSLETTER_SEX'            => $_ARRAYLANG['TXT_NEWSLETTER_SEX'],
            'TXT_NEWSLETTER_FEMALE'         => $_ARRAYLANG['TXT_NEWSLETTER_FEMALE'],
            'TXT_NEWSLETTER_MALE'           => $_ARRAYLANG['TXT_NEWSLETTER_MALE'],
            'TXT_NEWSLETTER_LASTNAME'       => $_ARRAYLANG['TXT_NEWSLETTER_LASTNAME'],
            'TXT_NEWSLETTER_FIRSTNAME'      => $_ARRAYLANG['TXT_NEWSLETTER_FIRSTNAME'],
            'TXT_NEWSLETTER_COMPANY'        => $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'],
            'TXT_NEWSLETTER_ADDRESS'        => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_NEWSLETTER_ZIP'            => $_ARRAYLANG['TXT_NEWSLETTER_ZIP'],
            'TXT_NEWSLETTER_CITY'           => $_ARRAYLANG['TXT_NEWSLETTER_CITY'],
            'TXT_NEWSLETTER_COUNTRY'        => $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY'],
            'TXT_NEWSLETTER_PHONE'          => $_ARRAYLANG['TXT_NEWSLETTER_PHONE'],
            'TXT_NEWSLETTER_BIRTHDAY'       => $_ARRAYLANG['TXT_NEWSLETTER_BIRTHDAY'],
            'TXT_NEWSLETTER_SAVE'           => $_ARRAYLANG['TXT_NEWSLETTER_SAVE'],
            'TXT_CANCEL'                    => $_CORELANG['TXT_CANCEL'],
            'TXT_NEWSLETTER_DO_NOT_SEND_EMAIL'     => $_ARRAYLANG['TXT_NEWSLETTER_DO_NOT_SEND_EMAIL'],
            'TXT_NEWSLETTER_INFO_ABOUT_SEND_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_INFO_ABOUT_SEND_EMAIL'],
            'TXT_NEWSLETTER_RECIPIENT_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_DATE'],
            'TXT_NEWSLETTER_RECIPIENT_MONTH'=> $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_MONTH'],
            'TXT_NEWSLETTER_RECIPIENT_YEAR' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_YEAR'],
//            'JAVASCRIPTCODE' => $this->JSadduser(),
            'NEWSLETTER_FILTER_PARAMS'      => $filterParams,
            'TXT_NEWSLETTER_CONSENT_CONFIRM'   => $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_CONFIRM'],
            'NEWSLETTER_CONSENT_CONFIRM_CHECK' => isset($_POST['consentConfirm'])
                ? 'checked="checked"' : '',
        ));
        $this->_objTpl->parse('module_newsletter_user_edit');
        return true;
    }

    /**
     * @todo instead of just not linking the access users probably link to
     *       the access module in case the user has the appropriate rights
     */
    function _userList()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG, $_CORELANG;

        \JS::activate('cx');

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_USER_ADMINISTRATION'];
        $this->_objTpl->addBlockfile('NEWSLETTER_USER_FILE', 'module_newsletter_user_overview', 'module_newsletter_user_overview.html');

        $limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : $_CONFIG['corePagingLimit'];

        //for User storage in Access-Module
        if(isset($_GET['store']) && $_GET['store'] == 'true'){
            self::$strOkMessage .= $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_UPDATED_SUCCESSFULLY'];
        }

        $newsletterListId = isset($_REQUEST['newsletterListId']) ? intval($_REQUEST['newsletterListId']) : 0;
        $this->_objTpl->setVariable(array(
            'TXT_TITLE' => $_ARRAYLANG['TXT_SEARCH'],
            'TXT_CHANGELOG_SUBMIT' => $_CORELANG['TXT_MULTISELECT_SELECT'],
            'TXT_CHANGELOG_SUBMIT_DEL' => $_CORELANG['TXT_MULTISELECT_DELETE'],
            'TXT_SELECT_ALL' => $_CORELANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL' => $_CORELANG['TXT_DESELECT_ALL'],
            'TXT_DELETE_HISTORY_MSG_ALL' => $_CORELANG['TXT_DELETE_HISTORY_ALL'],
            'TXT_NEWSLETTER_REGISTRATION_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_REGISTRATION_DATE'],
            'TXT_NEWSLETTER_ROWS_PER_PAGE' => $_ARRAYLANG['TXT_NEWSLETTER_ROWS_PER_PAGE'],
            'TXT_NEWSLETTER_RECIPIENTS' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENTS'],
            'TXT_NEWSLETTER_DELETE_ALL_INACTIVE' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE_ALL_INACTIVE'],
            'TXT_NEWSLETTER_REALLY_DELETE_ALL_INACTIVE' => $_ARRAYLANG['TXT_NEWSLETTER_REALLY_DELETE_ALL_INACTIVE'],
            'TXT_NEWSLETTER_CANNOT_UNDO_OPERATION' => $_ARRAYLANG['TXT_NEWSLETTER_CANNOT_UNDO_OPERATION'],
            'NEWSLETTER_PAGING_LIMIT' => $limit,
            'NEWSLETTER_LIST_ID' => $newsletterListId,
            'NEWSLETTER_FILTER_KEYWORD' => (!empty($_GET['filterkeyword']) ? contrexx_input2raw($_GET['filterkeyword']) : ''),
            'NEWSLETTER_FILTER_ATTRIBUTE' => (!empty($_GET['filterattribute']) ? contrexx_input2raw($_GET['filterattribute']) : ''),
            'NEWSLETTER_FILTER_STATUS' => (!empty($_GET['filterStatus']) ? contrexx_input2raw($_GET['filterStatus']) : ''),
            'TXT_NEWSLETTER_USER_FEEDBACK' => $_ARRAYLANG['TXT_NEWSLETTER_USER_FEEDBACK'],
        ));

        $this->_objTpl->setVariable('NEWSLETTER_LIST_MENU', $this->CategoryDropDown('newsletterListId', $newsletterListId, "id='newsletterListId' onchange=\"newsletterList.setList(this.value)\""));
        if (isset($_GET["addmailcode"])) {
            $query = "SELECT id, code FROM ".DBPREFIX."module_newsletter_user where code=''";
            $objResult = $objDatabase->Execute($query);
            if ($objResult) {
                while (!$objResult->EOF) {
                    $objDatabase->Execute("
                        UPDATE ".DBPREFIX."module_newsletter_user
                           SET code='".$this->_emailCode()."'
                         WHERE id=".$objResult->fields['id']
                    );
                    $objResult->MoveNext();
                }
            }
        }

        if (isset($_GET["delete"])) {
            $recipientId = intval($_GET["id"]);
            if ($this->_deleteRecipient($recipientId)) {
                self::$strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
            } else {
                self::$strErrMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETE_ERROR'];
            }
        }

        if (isset($_GET["bulkdelete"])) {
            $error = 0;
            if (!empty($_POST['userid'])) {
                foreach ($_POST['userid'] as $userid) {
                    $userid=intval($userid);
                    if (!$this->_deleteRecipient($userid)) {
                        $error = 1;
                    }
                }
            }
/*
            if (!empty($_POST['accessUserid'])) {
                foreach ($_POST['accessUserid'] as $userID) {
                    if ($this->removeAccessRecipient($userID)) {
                        $error = 1;
                    }
                }
            }
*/
            if ($error) {
                self::$strErrMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETE_ERROR'];
            } else {
                self::$strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
            }
        }

        $queryCHECK         = "SELECT id, code FROM ".DBPREFIX."module_newsletter_user where code=''";
        $objResultCHECK     = $objDatabase->Execute($queryCHECK);
        $count         = $objResultCHECK->RecordCount();
        if ($count > 0) {
            $email_code_check = '<div style="color: red;">'.$_ARRAYLANG['TXT_EMAIL_WITHOUT_CODE_MESSAGE'].'!<br/><a href="index.php?cmd=Newsletter&act=users&addmailcode=1">'.$_ARRAYLANG['TXT_ADD_EMAIL_CODE_LINK'].' ></a></div/><br/>';
            $this->_objTpl->setVariable('EMAIL_CODE_CHECK', $email_code_check);
        }

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_CHECK_ALL' => $_ARRAYLANG['TXT_NEWSLETTER_CHECK_ALL'],
            'TXT_NEWSLETTER_UNCHECK_ALL' => $_ARRAYLANG['TXT_NEWSLETTER_UNCHECK_ALL'],
            'TXT_NEWSLETTER_WITH_SELECTED' => $_ARRAYLANG['TXT_NEWSLETTER_WITH_SELECTED'],
            'TXT_NEWSLETTER_DELETE' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE'],
            'TXT_STATUS' => $_ARRAYLANG['TXT_STATUS'],
            'TXT_SEARCH' => $_ARRAYLANG['TXT_SEARCH'],
            'TXT_EMAIL_ADDRESS' => $_ARRAYLANG['TXT_EMAIL_ADDRESS'],
            'TXT_NEWSLETTER_COMPANY' => $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'],
            'TXT_LASTNAME' => $_ARRAYLANG['TXT_LASTNAME'],
            'TXT_FIRSTNAME' => $_ARRAYLANG['TXT_FIRSTNAME'],
            'TXT_NEWSLETTER_ADDRESS' => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
            'TXT_ZIP' => $_ARRAYLANG['TXT_ZIP'],
            'TXT_CITY' => $_ARRAYLANG['TXT_CITY'],
            'TXT_COUNTRY' => $_ARRAYLANG['TXT_COUNTRY'],
            'TXT_PHONE' => $_ARRAYLANG['TXT_PHONE'],
            'TXT_BIRTHDAY' => $_ARRAYLANG['TXT_BIRTHDAY'],
            'TXT_USER_DATA' => $_ARRAYLANG['TXT_USER_DATA'],
            'TXT_NEWSLETTER_CATEGORYS' => $_ARRAYLANG['TXT_NEWSLETTER_CATEGORYS'],
            'TXT_STATUS' => $_ARRAYLANG['TXT_STATUS'],
            'SELECTLIST_FIELDS' => $this->SelectListFields(),
            'SELECTLIST_CATEGORY' => $this->SelectListCategory(),
            'SELECTLIST_STATUS' => $this->SelectListStatus(),
            'JAVASCRIPTCODE' => $this->JSedituser(),
            'TXT_EDIT' => $_ARRAYLANG['TXT_EDIT'],
            'TXT_ADD' => $_ARRAYLANG['TXT_ADD'],
            'TXT_IMPORT' => $_ARRAYLANG['TXT_IMPORT'],
            'TXT_EXPORT' => $_ARRAYLANG['TXT_EXPORT'],
            'TXT_FUNCTIONS' => $_CORELANG['TXT_FUNCTIONS'],
            'TXT_NEWSLETTER_CONSENT' => $_ARRAYLANG['TXT_NEWSLETTER_CONSENT'],
            'TXT_NEWSLETTER_CONSENT_TOOLTIP' => $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_TOOLTIP'],
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_MODIFY_RECIPIENT' => $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_RECIPIENT'],
            'TXT_NEWSLETTER_COPY_RECIPIENT'   => $_ARRAYLANG['TXT_NEWSLETTER_COPY_RECIPIENT'],
            'TXT_NEWSLETTER_DELETE_RECIPIENT' => $_ARRAYLANG['TXT_NEWSLETTER_DELETE_RECIPIENT'],
        ));

        $arrSettings = $this->_getSettings();
        if ($arrSettings['statistics']['setvalue']) {
            $this->_objTpl->touchBlock('statistics');
        } else {
            $this->_objTpl->hideBlock('statistics');
        }
        $this->_objTpl->parse('module_newsletter_user_overview');
    }



    /**
     * Return all newsletter users and those access users who are assigned
     * to the list and their information
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       string $where The where String for searching
     * @param       int $newsletterListId The id of the newsletter category
     *              to be selected (0 for all users)
     * @return      array(array, int)
     */
    private function returnNewsletterUser($where, $order = '', $newsletterListId=0, $status = null, $limit = null, $pagingPos = 0)
    {
        global $objDatabase;

        $arrRecipientFields = array(
            'newsletter'    => array(),
            'access'        => array(),
            'list'          => array(
                'id',
                'status',
                'email',
                'uri',
                'sex',
                'salutation',
                'title',
                'lastname',
                'firstname',
                'position',
                'company',
                'industry_sector',
                'address',
                'zip',
                'city',
                'country_id',
                'phone_office',
                'phone_private',
                'phone_mobile',
                'fax',
                'notes',
                'birthday',
                'type',
                'emaildate',
                'language',
            )
        );

        $arrFieldsWrapperDefinition = array(
            'newsletter' => array(
                'type'              => array('type' => 'data', 'def' => 'newsletter_user')
            ),
            'access' => array(
                'status'            => array('type' => 'field', 'def' => 'active'),
                'uri'               => array('type' => 'field', 'def' => 'website'),
                'sex'               => array('type' => 'operation', 'def' => '(CASE
                                                                                    WHEN `gender`=\'gender_female\' THEN \'f\'
                                                                                    WHEN `gender`=\'gender_male\' THEN \'m\'
                                                                                    ELSE \'-\'
                                                                                END)'),
                'salutation'        => array('type' => 'field', 'def' => 'title'),
                'title'             => array('type' => 'data',  'def' => ''),
                'position'          => array('type' => 'data',  'def' => ''),
                'industry_sector'   => array('type' => 'data',  'def' => ''),
                'country_id'        => array('type' => 'field', 'def' => 'country'),
                'fax'               => array('type' => 'field', 'def' => 'phone_fax'),
                'notes'             => array('type' => 'data',  'def' => ''),
                'type'              => array('type' => 'data', 'def' => 'access_user'),
                'emaildate'         => array('type' => 'field', 'def' => 'regdate'),
                'language'          => array('type' => 'data',  'def' => ''),
            )
        );

        foreach ($arrFieldsWrapperDefinition as $recipientType => $arrWrapperDefinitions) {
            foreach ($arrRecipientFields['list'] as $field) {
                $wrapper = '';

                if (isset($arrWrapperDefinitions[$field])) {
                    $wrapper = $arrWrapperDefinitions[$field]['type'];
                }

                switch ($wrapper) {
                    case 'field':
                        $wrappedField = sprintf('`%1$s` AS `%2$s`', $arrWrapperDefinitions[$field]['def'], $field);
                        break;

                    case 'data':
                        $wrappedField = sprintf('\'%1$s\' AS `%2$s`', $arrWrapperDefinitions[$field]['def'], $field);
                        break;

                    case 'operation':
                        $wrappedField = sprintf('%1$s AS `%2$s`', $arrWrapperDefinitions[$field]['def'], $field);
                        break;

                    default:
                        $wrappedField = sprintf('`%1$s`', $field);
                        break;
                }

                $arrRecipientFields[$recipientType][] = $wrappedField;
            }
        }

        array_push(
            $arrRecipientFields['newsletter'],
            '`nu`.`source`',
            '`nu`.`consent`'
        );
        array_push(
            $arrRecipientFields['access'],
            "'undefined' AS `source`",
            "'undefined' AS `consent`"
        );
        if (!empty($newsletterListId)) {
            array_push(
                $arrRecipientFields['newsletter'],
                '`rc`.`source` AS `cat_source`',
                '`rc`.`consent` AS `cat_consent`'
            );
            array_push(
                $arrRecipientFields['access'],
                "`cnu`.`source` AS `cat_source`",
                "`cnu`.`consent` AS `cat_consent`"
            );
        }

        $query   = sprintf('
            (
                SELECT SQL_CALC_FOUND_ROWS
                %2$s
                FROM `%1$smodule_newsletter_user` AS `nu`
                %3$s
                WHERE 1
                AND (
                    nu.source != "opt-in"
                    OR (
                        nu.source = "opt-in"
                        AND nu.consent IS NOT NULL
                    )
                )
                %4$s
                %5$s
                %10$s
            )
            UNION DISTINCT
            (
                SELECT
                %6$s
                FROM `%1$smodule_newsletter_access_user` AS `cnu`
                    INNER JOIN `%1$saccess_users` AS `cu` ON `cu`.`id`=`cnu`.`accessUserID`
                    INNER JOIN `%1$saccess_user_profile` AS `cup` ON `cup`.`user_id`=`cu`.`id`
                WHERE 1
                %7$s
                %5$s
                %11$s
            )
            %8$s
            %9$s',

            // %1$s
            DBPREFIX,

            // %2$s
            implode(',', $arrRecipientFields['newsletter']),

            // %3$s
            ( !empty($newsletterListId)
                ?  'INNER JOIN `'.DBPREFIX.'module_newsletter_rel_user_cat` AS `rc` ON `rc`.`user`=`nu`.`id`' : ''),

            // %4$s
            ( !empty($newsletterListId)
                ? sprintf('AND `rc`.`category`=%s', intval($newsletterListId)) : ''),

            // %5$s
            $where,

            // %6$s
            implode(',', $arrRecipientFields['access']),

            // %7$s
            (!empty($newsletterListId)
                ? sprintf('AND `cnu`.`newsletterCategoryID`=%s', intval($newsletterListId)) : ''),

            // %8$s
            $order,

            // %9$s
            ($limit ? sprintf('LIMIT %s, %s', $pagingPos, $limit) : ''),

            // %10$s
            ($status === null ? '' : 'AND `nu`.`status` = '.$status),

            // %11$s
            ($status === null ? '' : 'AND `cu`.`active` = '.$status)
        );

        $data = $objDatabase->Execute($query);
        $users = array();
        if ($data !== false ) {
            while (!$data->EOF) {
                $users[] = $data->fields;
                $data->MoveNext();
            }
        }
        $data = $objDatabase->Execute('SELECT FOUND_ROWS() AS `count`');
        $count = $data->fields['count'];
        return array($users, $count);
    }


    function SelectListStatus()
    {
        global $_ARRAYLANG;

        return '<select id="newsletterRecipientFilterStatus">
                    <option value="">-- '.$_ARRAYLANG['TXT_STATUS'].' --</option>
                    <option value="0">'.$_ARRAYLANG['TXT_OPEN_ISSUE'].'</option>
                    <option value="1">'.$_ARRAYLANG['TXT_ACTIVE'].'</option>
                </select>';
    }


    function SelectListCategory()
    {
        global $objDatabase, $_ARRAYLANG;

        $ReturnVar = '<select name="SearchCategory">';
        $ReturnVar .= '<option value="">-- '.$_ARRAYLANG['TXT_NEWSLETTER_CATEGORYS'].' --</option>';
        $queryPS = "SELECT * FROM ".DBPREFIX."module_newsletter_category order by name";
        $objResultPS = $objDatabase->Execute($queryPS);
        if ($objResultPS) {
            while (!$objResultPS->EOF) {
                $ReturnVar .= '<option value="'.$objResultPS->fields['id'].'" >'.$objResultPS->fields['name'].'</option>';
                $objResultPS->MoveNext();
            }
        }
        $ReturnVar .= '</select>';
        return $ReturnVar;
    }


    function SelectListFields()
    {
        global $_ARRAYLANG;

        return '<select id="newsletterRecipientFilterAttribute">
                    <option value="">-- '.$_ARRAYLANG['TXT_SEARCH_ON'].' --</option>
                    <option value="email">'.$_ARRAYLANG['TXT_EMAIL_ADDRESS'].'</option>
                    <option value="company">'.$_ARRAYLANG['TXT_NEWSLETTER_COMPANY'].'</option>
                    <option value="lastname">'.$_ARRAYLANG['TXT_LASTNAME'].'</option>
                    <option value="firstname">'.$_ARRAYLANG['TXT_FIRSTNAME'].'</option>
                    <option value="address">'.$_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'].'</option>
                    <option value="zip">'.$_ARRAYLANG['TXT_ZIP'].'</option>
                    <option value="city">'.$_ARRAYLANG['TXT_CITY'].'</option>
                    <option value="country_id">'.$_ARRAYLANG['TXT_COUNTRY'].'</option>
                    <option value="phone">'.$_ARRAYLANG['TXT_PHONE'].'</option>
                    <option value="birthday">'.$_ARRAYLANG['TXT_BIRTHDAY'].'</option>
                </select>';
    }


    function JSadduser()
    {
        global $_ARRAYLANG;

        \JS::registerCode('
function SubmitAddForm() {
  if (CheckMail(document.adduser.email.value)==true) {
    document.adduser.submit();
  } else {
    alert("'.$_ARRAYLANG['TXT_MAILERROR'].'");
    document.adduser.email.focus();
  }
}

function CheckMail(s) {
  var a = false;
  var res = false;
  if (typeof(RegExp) == "function") {
    var b = new RegExp("abc");
    if (b.test("abc") == true) {a = true;}
  }
  if (a == true) {
    reg = new RegExp(
      "^([a-zA-Z0-9\\-\\.\\_]+)"+
      "(\\@)([a-zA-Z0-9\\-\\.]+)"+
      "(\\.)([a-zA-Z]{2,4})$");
    res = (reg.test(s));
  } else {
    res = (s.search("@") >= 1 &&
    s.lastIndexOf(".") > s.search("@") &&
    s.lastIndexOf(".") >= s.length-5)
  }
  return(res);
}
');
    }


    function JSedituser()
    {
        global $_ARRAYLANG;

        \JS::registerCode('
function DeleteUser(UserID, email) {
  strConfirmMsg = "'.$_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_DELETE_RECIPIENT_OF_ADDRESS'].'";
  if (confirm(strConfirmMsg.replace("%s", email)+"\n'.$_ARRAYLANG['TXT_NEWSLETTER_CANNOT_UNDO_OPERATION'].'")) {
    document.location.href = "index.php?cmd=Newsletter&'.\Cx\Core\Csrf\Controller\Csrf::param().'&act=users&delete=1&id="+UserID;
  }
}

function MultiAction() {
  with (document.userlist) {
    switch (userlist_MultiAction.value) {
      case "delete":
        if (confirm(\''.$_ARRAYLANG['TXT_NEWSLETTER_CONFIRM_DELETE_SELECTED_RECIPIENTS'].'\n'.$_ARRAYLANG['TXT_NEWSLETTER_CANNOT_UNDO_OPERATION'].'\')) {
          submit();
        }
        break;
    }
  }
}
');
    }


    function DateForDB()
    {
        return date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATE);
    }


    function _getTemplateMenu($selectedTemplate='', $attributes='', $type='e-mail')
    {
        $menu = "<select".(!empty($attributes) ? " ".$attributes : "").">\n";
        foreach ($this->_getTemplates($type) as $templateId => $arrTemplate) {
            $menu .= "<option value=\"".$templateId."\"".($templateId == $selectedTemplate ? "selected=\"selected\"" : "").">".htmlentities($arrTemplate['name'], ENT_QUOTES, CONTREXX_CHARSET)."</option>\n";
        }
        $menu .= "</select>\n";
        return $menu;
    }


    function _getTemplates($type = 'e-mail')
    {
        global $objDatabase;

        $arrTemplates = array();
        $objTemplate = $objDatabase->Execute("SELECT id, name, description, html, type FROM ".DBPREFIX."module_newsletter_template WHERE type='".$type."'");
        if ($objTemplate !== false) {
            while (!$objTemplate->EOF) {
                $arrTemplates[$objTemplate->fields['id']] = array(
                    'name' => $objTemplate->fields['name'],
                    'description' => $objTemplate->fields['description'],
                    'type' => $objTemplate->fields['type'],
                    'html' => $objTemplate->fields['html'],
                );
                $objTemplate->MoveNext();
            }
        }
        return $arrTemplates;
    }


    function CategoryDropDown($name='category', $selected=0, $attrs='')
    {
        global $objDatabase, $_ARRAYLANG;

        $ReturnVar = '<select name="'.$name.'"'.(!empty($attrs) ? ' '.$attrs : '').'>
        <option value="selectcategory">'.$_ARRAYLANG['TXT_NEWSLETTER_SELECT_CATEGORY'].'</option>
        <option value="">'.$_ARRAYLANG['TXT_NEWSLETTER_ALL'].'</option>';
        $queryCS         = "SELECT id, name FROM ".DBPREFIX."module_newsletter_category ORDER BY name";
        $objResultCS     = $objDatabase->Execute($queryCS);
        if ($objResultCS !== false) {
            $CategorysFounded = 1;
            while (!$objResultCS->EOF) {
                $ReturnVar .= '<option value="'.$objResultCS->fields['id'].'"'.($objResultCS->fields['id'] == $selected ? 'selected="selected"' : '').'>'.htmlentities($objResultCS->fields['name'], ENT_QUOTES, CONTREXX_CHARSET).'</option>';
                $objResultCS->MoveNext();
            }
        }
        $ReturnVar .= '</select>';
        if ($CategorysFounded!=1) {
            $ReturnVar = '';
        }
        return $ReturnVar;
    }


    function _getListHTML()
    {
        global $_ARRAYLANG;

        $listId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($listId == 0) {
            return $this->_lists();
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_LISTS'];
        $this->_objTpl->loadTemplateFile('module_newsletter_list_sourcecode.html');
        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_GENERATE_HTML_SOURCE_CODE' => $_ARRAYLANG['TXT_NEWSLETTER_GENERATE_HTML_SOURCE_CODE'],
            'TXT_NEWSLETTER_BACK' => $_ARRAYLANG['TXT_NEWSLETTER_BACK'],
            'NEWSLETTER_HTML_CODE' => htmlentities($this->_getHTML($listId), ENT_QUOTES, CONTREXX_CHARSET)
        ));
        return true;
    }

    // TODO: we consider, that attribute values are all included in double quotes (""):  wysiwyg editor replaces them automatically
    // In general, user can use single quotes (' ') or miss quotes
    function _prepareNewsletterLinksForStore($MailId)
    {
        global $objDatabase;

        $objMail = $objDatabase->SelectLimit("
            SELECT `content`
            FROM ".DBPREFIX."module_newsletter
            WHERE id=$MailId", 1);
        if ($objMail !== false && $objMail->RecordCount() == 1) {
            $htmlContent = $objMail->fields['content'];
            $linkIds = array();

            $matches = NULL;
            if (preg_match_all("/<a([^>]+)>(.*?)<\/a>/is", $htmlContent, $matches)) {
                $tagCount = count($matches[0]);
                $fullKey = 0;
                $attrKey = 1;
                $textKey = 2;
                $rmatches = NULL;
                for ($i = 0; $i < $tagCount; $i++) {
// TODO: wouldn't that
                   if (!preg_match("/href\s*=\s*['\"][^#]/i", $matches[$attrKey][$i])) {
// be the same as
//                     if (preg_match("/href\s*=\s*['\"][#]/i", $matches[$attrKey][$i])) {
// ?
                        // we might have a placeholder link here, it will be parsed on send
                        continue;
                    }
                    $rel = '';
                    $href = '';
                    if (preg_match("/rel\s*=\s*(['\"])(.*?)\\1/i", $matches[$attrKey][$i], $rmatches)) {
                        $rel = $rmatches[2];
                    }
                    if (preg_match("/href\s*=\s*(['\"])(.*?)\\1/i", $matches[$attrKey][$i], $rmatches)) {
                        $href = html_entity_decode($rmatches[2], ENT_QUOTES, CONTREXX_CHARSET);
                    }
                    if ($rel) {
                        if (preg_match("/newsletter_link_(\d+)/i", $rel, $rmatches)) {
                            if (in_array($rmatches[1], $linkIds)) {
                                $query = "INSERT INTO ".DBPREFIX."module_newsletter_email_link (email_id, title, url) VALUES
                                    (".intval($MailId).", '".contrexx_raw2db($matches[$textKey][$i])."', '".contrexx_raw2db($href)."')";
                                if ($objDatabase->Execute($query)) {
                                    $linkId = $objDatabase->Insert_ID();
                                    $matches[$attrKey][$i] = str_replace(
                                        'newsletter_link_'.$rmatches[1],
                                        'newsletter_link_'.$linkId,
                                        $matches[$attrKey][$i]);
                                }
                            } else {
                                // update existed link
                                $query = "UPDATE ".DBPREFIX."module_newsletter_email_link
                                    SET title = '".contrexx_raw2db($matches[$textKey][$i])."',
                                        url = '".contrexx_raw2db($href)."'
                                    WHERE id = ".intval($rmatches[1]);
                                $objDatabase->Execute($query);
                                $linkId = $rmatches[1];
                            }
                        } else {
                            // insert new link into database and update rel attribute
                            $query = "INSERT INTO ".DBPREFIX."module_newsletter_email_link (email_id, title, url) VALUES
                                (".intval($MailId).", '".
                                contrexx_raw2db($matches[$textKey][$i])."', '".
                                contrexx_raw2db($href)."')";
                            if ($objDatabase->Execute($query)) {
                                $linkId = $objDatabase->Insert_ID();
                                $matches[$attrKey][$i] = preg_replace(
                                    "/rel\s*=\s*(['\"])(.*?)\\1/i",
                                    "rel=\"$2 newsletter_link_".$linkId."\"",
                                    $matches[$attrKey][$i]);
                            }
                        }
                    } else {
                        // insert new link into database and create rel attribute
                        $query = "INSERT INTO ".DBPREFIX."module_newsletter_email_link (email_id, title, url) VALUES
                            (".intval($MailId).", '".
                            contrexx_raw2db($matches[$textKey][$i])."', '".
                            contrexx_raw2db($href)."')";
                        if ($objDatabase->Execute($query)) {
                            $linkId = $objDatabase->Insert_ID();
                            $matches[$attrKey][$i] .= ' rel="newsletter_link_'.$linkId.'"';
                        }
                    }
                    $linkIds[] = $linkId;
                    $htmlContent = preg_replace(
                        "/".preg_quote($matches[$fullKey][$i], '/')."/is",
                        "<a ".$matches[$attrKey][$i].">".$matches[$textKey][$i]."</a>",
                        $htmlContent, 1);
                }
                // update mail content
                $query = "UPDATE ".DBPREFIX."module_newsletter
                    SET content = '".contrexx_raw2db($htmlContent)."'
                    WHERE id = ".intval($MailId);
                $objDatabase->Execute($query);
            }
            // remove deleted links from database; we can remove them, because we can't edit sent email
            if (count($linkIds) > 0) {
                $query = "DELETE FROM ".DBPREFIX."module_newsletter_email_link
                    WHERE id NOT IN (".implode(", ", $linkIds).") AND email_id = ".$MailId;
                $objDatabase->Execute($query);
            }
        }
    }

    function _prepareNewsletterLinksForCopy($MailHtmlContent)
    {
        $result = $MailHtmlContent;
        $matches = NULL;
        if (preg_match_all("/<a([^>]+)>(.*?)<\/a>/is", $result, $matches)) {
            $tagCount = count($matches[0]);
            $fullKey = 0;
            $attrKey = 1;
            $textKey = 2;
            for ($i = 0; $i < $tagCount; $i++) {
                if (!preg_match("/href\s*=\s*['\"]/i", $matches[$attrKey][$i])) {
                   continue;
                }
                // remove newsletter_link_N from rel attribute
// TODO: This code should go into the library as a private method.
// See prepareNewsletterLinksForSend()
                $matches[$attrKey][$i] = preg_replace("/newsletter_link_([0-9]+)/i", "", $matches[$attrKey][$i]);
                // remove empty rel attribute
                $matches[$attrKey][$i] = preg_replace("/\s*rel\s*=\s*(['\"])\s*\\1/i", "", $matches[$attrKey][$i]);
                // remove left and right spaces
// TODO: These REs miserably fail when apostrophes (') are used
// TODO: What do they *really* do?
                $matches[$attrKey][$i] = preg_replace("/([^=])\s*\"/i", "$1\"", $matches[$attrKey][$i]);
                $matches[$attrKey][$i] = preg_replace("/=\"\s*/i", "=\"", $matches[$attrKey][$i]);
                $result = preg_replace(
// TODO: The /s flag is probably unnecessary
                    "/".preg_quote($matches[$fullKey][$i], '/')."/is",
                    "<a ".$matches[$attrKey][$i].">".$matches[$textKey][$i]."</a>",
                    $result, 1);
            }
        }
        return $result;
    }

    function _showEmailFeedbackAnalysis()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_FEEDBACK'];
        $this->_objTpl->loadTemplateFile('module_newsletter_email_feedback.html');
        $rowNr = 0;
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $mailId = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $email = '';
        $objMail = $objDatabase->SelectLimit("SELECT `subject` FROM ".DBPREFIX."module_newsletter WHERE id=".$mailId, 1);
        if ($objMail !== false && $objMail->RecordCount() == 1) {
            $email = contrexx_raw2xhtml($objMail->fields['subject']);
        }

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_LINK_TITLE' => $_ARRAYLANG['TXT_NEWSLETTER_LINK_TITLE'],
            'TXT_NEWSLETTER_FEEDBACK_RECIPIENTS' => $_ARRAYLANG['TXT_NEWSLETTER_FEEDBACK_RECIPIENTS'],
            'TXT_NEWSLETTER_LINK_SOURCE' => $_ARRAYLANG['TXT_NEWSLETTER_LINK_SOURCE'],
            'TXT_NEWSLETTER_FUNCTIONS' => $_ARRAYLANG['TXT_NEWSLETTER_FUNCTIONS'],
            'TXT_NEWSLETTER_BACK' => $_ARRAYLANG['TXT_NEWSLETTER_BACK'],
            'TXT_NEWSLETTER_EMAIL_FEEDBACK' => sprintf($_ARRAYLANG['TXT_NEWSLETTER_SELECTED_EMAIL_FEEDBACK'], $email)
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB' => $_ARRAYLANG['TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB'],
            'TXT_NEWSLETTER_LINK_FEEDBACK_ANALYZE' => $_ARRAYLANG['TXT_NEWSLETTER_LINK_FEEDBACK_ANALYZE']
        ));

        $objResultCount = $objDatabase->SelectLimit("SELECT COUNT(id) AS link_count FROM ".DBPREFIX."module_newsletter_email_link
            WHERE email_id = ".$mailId, 1);
        if ($objResultCount !== false) {
            $linkCount = $objResultCount->fields['link_count'];
        } else {
            $linkCount = 0;
        }

        $objResult = $objDatabase->SelectLimit("SELECT
            tblLink.id,
            tblLink.title,
            tblLink.url,
            tblMail.count,
            COUNT(tblMailLinkFB.id) AS feedback_count
            FROM ".DBPREFIX."module_newsletter_email_link AS tblLink
                INNER JOIN ".DBPREFIX."module_newsletter AS tblMail ON tblMail.id = ".$mailId."
                LEFT JOIN ".DBPREFIX."module_newsletter_email_link_feedback AS tblMailLinkFB ON tblMailLinkFB.link_id = tblLink.id
            WHERE tblLink.email_id = ".$mailId."
            GROUP BY tblLink.id
            ORDER BY tblLink.title ASC", $_CONFIG['corePagingLimit'], $pos);
        if ($objResult !== false) {
            while (!$objResult->EOF) {

                // parse NODE-Url placeholders in link
                \LinkGenerator::parseTemplate($objResult->fields['url'], true);

                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_LINK_ROW_CLASS' => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'NEWSLETTER_LINK_TITLE'     => $objResult->fields['title'],
                    'NEWSLETTER_LINK_URL'       => $objResult->fields['url'],
                    'NEWSLETTER_MAIL_USERS'     => (int) $objResult->fields['feedback_count'], // number of users, who have clicked the link
                    'NEWSLETTER_LINK_FEEDBACK'  => $objResult->fields['count'] > 0 ? round(100 /  $objResult->fields['count'] * $objResult->fields['feedback_count']) : 0
                ));

                $this->_objTpl->setGlobalVariable('NEWSLETTER_LINK_ID', $objResult->fields['id']);

                $this->_objTpl->parse("link_list");
                $objResult->MoveNext();
                $rowNr++;
            }
            if ($rowNr > 0) {
                $paging = getPaging($linkCount, $pos, ("&cmd=Newsletter&act=feedback&email_id=".$mailId), "", false, $_CONFIG['corePagingLimit']);
                $this->_objTpl->setVariable('NEWSLETTER_LINKS_PAGING', "<br />".$paging."<br />");
            }
        }
    }

    private function getLinkData($linkId)
    {
        global $objDatabase;

        $objLink = $objDatabase->SelectLimit('
            SELECT  email_id,
                    title
            FROM    `'.DBPREFIX.'module_newsletter_email_link`
            WHERE   id='.$linkId, 1);
        if ($objLink == false || !$objLink->RecordCount()) {
            return false;
        }

        return array(
            'email_id'      => $objLink->fields['email_id'],
            'link_title'    => $objLink->fields['title']
        );
    }

    function _showLinkFeedbackAnalysis()
    {
// TODO: refactor method
        die('Feature unavailable');

        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        if (empty($_GET['link_id'])) return $this->_mails();

        $rowNr = 0;
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $linkId = intval($_GET['link_id']);

        $arrLinkData = $this->getLinkData($linkId);
        if (!$arrLinkData) return $this->_mails();

        $arrNewsletterData = $this->getNewsletterData($arrLinkData['email_id']);
        if (!$arrNewsletterData) return $this->_mails();

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_FEEDBACK'];
        $this->_objTpl->loadTemplateFile('module_newsletter_link_feedback.html');

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_RECIPIENT'      => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT'],
            'TXT_NEWSLETTER_FEEDBACK_CLICKS'=> $_ARRAYLANG['TXT_NEWSLETTER_FEEDBACK_CLICKS'],
            'TXT_NEWSLETTER_LINK_SOURCE'    => $_ARRAYLANG['TXT_NEWSLETTER_LINK_SOURCE'],
            'TXT_NEWSLETTER_FUNCTIONS'      => $_ARRAYLANG['TXT_NEWSLETTER_FUNCTIONS'],
            'TXT_NEWSLETTER_BACK'           => $_ARRAYLANG['TXT_NEWSLETTER_BACK'],
            'TXT_NEWSLETTER_LINK_FEEDBACK'  => sprintf( $_ARRAYLANG['TXT_NEWSLETTER_LINK_FEEDBACK'],
                                                        contrexx_raw2xhtml($arrLinkData['link_title']),
                                                        contrexx_raw2xhtml($arrNewsletterData['subject']))
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB' => $_ARRAYLANG['TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB'],
            'TXT_NEWSLETTER_RECIPIENT_FEEDBACK_ANALYZE_DETAIL' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_FEEDBACK_ANALYZE_DETAIL'],
            'TXT_NEWSLETTER_MODIFY_RECIPIENT' => $_ARRAYLANG['TXT_NEWSLETTER_MODIFY_RECIPIENT'],
            'NEWSLETTER_LINK_ID' => $linkId
        ));

        // The amount of tracked links of the selected e-mail
        $objResultCount = $objDatabase->SelectLimit("SELECT COUNT(id) AS link_count FROM ".DBPREFIX."module_newsletter_email_link
            WHERE email_id = ".$arrLinkData['email_id'], 1);
        if ($objResultCount !== false) {
            $linkCount = $objResultCount->fields['link_count'];
        } else {
            $linkCount = 0;
        }

        $newsletterUserIds = array();
        $accessUserIds = array();
        $feedbackCount = array();

        $query = "SELECT
                CASE WHEN `s`.`type` = '".self::USER_TYPE_NEWSLETTER."'
                    THEN `nu`.`id`
                    ELSE `au`.`id` END AS `id`,
                CASE WHEN `s`.`type` = '".self::USER_TYPE_NEWSLETTER."'
                    THEN `nu`.`firstname`
                    ELSE `aup`.`firstname` END AS `firstname`,
                CASE WHEN `s`.`type` = '".self::USER_TYPE_NEWSLETTER."'
                    THEN `nu`.`lastname`
                    ELSE `aup`.`lastname` END AS `lastname`,
                `s`.email,
                `s`.type,
                `s`.`sendt`
            FROM `".DBPREFIX."module_newsletter_tmp_sending` AS `s`
                LEFT JOIN `".DBPREFIX."module_newsletter_user` AS `nu` ON `nu`.`email` = `s`.`email` AND `s`.`type` = '".self::USER_TYPE_NEWSLETTER."'
                LEFT JOIN `".DBPREFIX."access_users` AS `au` ON `au`.`email` = `s`.`email` AND (`s`.`type` = '".self::USER_TYPE_ACCESS."' OR `s`.`type` = '".self::USER_TYPE_CORE."')
                LEFT JOIN `".DBPREFIX."access_user_profile` AS `aup` ON `aup`.`user_id` = `au`.`id`
            WHERE `s`.`newsletter` = ".$arrLinkData['email_id']." AND `s`.`sendt` > 0 AND (`au`.`email` IS NOT NULL OR `nu`.`email` IS NOT NULL)
            ORDER BY `lastname` ASC, `firstname` ASC";

        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);
        if ($objResult !== false) {
            $users = array();
            while (!$objResult->EOF) {
                $users[] = array(
                    'id'        => $objResult->fields['id'],
                    'firstname' => $objResult->fields['firstname'],
                    'lastname'  => $objResult->fields['lastname'],
                    'type'      => $objResult->fields['type']
                );

                switch ($objResult->fields['type']) {
                    case self::USER_TYPE_ACCESS:
                    case self::USER_TYPE_CORE:
                        $accessUserIds[] = $objResult->fields['id'];
                        break;

                    case self::USER_TYPE_NEWSLETTER:
                    default:
                        $newsletterUserIds[] = $objResult->fields['id'];
                        break;
                }

                $objResult->MoveNext();
            }

            // select stats of native newsletter recipients
            if (count($newsletterUserIds) > 0) {
                $objLinks = $objDatabase->Execute("SELECT
                        tblLink.recipient_id,
                        COUNT(tblLink.id) AS link_count
                    FROM ".DBPREFIX."module_newsletter_email_link_feedback AS tblLink
                    WHERE
                        tblLink.email_id = ".$arrLinkData['email_id']."
                        AND tblLink.recipient_id IN (".implode(", ", $newsletterUserIds).")
                        AND tblLink.recipient_type = '".self::USER_TYPE_NEWSLETTER."'
                    GROUP BY tblLink.recipient_id");
                if ($objLinks !== false) {
                    while (!$objLinks->EOF) {
                        $feedbackCount[$objLinks->fields['recipient_id']][self::USER_TYPE_NEWSLETTER] = $objLinks->fields['link_count'];
                        $objLinks->MoveNext();
                    }
                }
            }

            // select stats of access users
            if (count($accessUserIds) > 0) {
                $objLinks = $objDatabase->Execute("SELECT
                        tblLink.recipient_id,
                        COUNT(tblLink.id) AS link_count
                    FROM ".DBPREFIX."module_newsletter_email_link_feedback AS tblLink
                    WHERE
                        tblLink.email_id = ".$arrLinkData['email_id']."
                        AND tblLink.recipient_id IN (".implode(", ", $accessUserIds).")
                        # we only need to select by self::USER_TYPE_ACCESS here. stats of users with self::USER_TYPE_CORE are also created using self::USER_TYPE_ACCESS
                        AND tblLink.recipient_type = '".self::USER_TYPE_ACCESS."'
                    GROUP BY tblLink.recipient_id");
                if ($objLinks !== false) {
                    while (!$objLinks->EOF) {
                        $feedbackCount[$objLinks->fields['recipient_id']][self::USER_TYPE_ACCESS] = $objLinks->fields['link_count'];
                        $objLinks->MoveNext();
                    }
                }
            }

            foreach ($users as $user) {
                // stats for users of type self::USER_TYPE_CORE are made using type self::USER_TYPE_ACCESS
                if ($user['type'] == self::USER_TYPE_CORE) {
                    $user['type'] = self::USER_TYPE_ACCESS;
                }
                // The amount of valid requests from that certain recipient of the selected e-mail
                $feedback = isset($feedbackCount[$user['id']][$user['type']]) ? $feedbackCount[$user['id']][$user['type']] : 0;
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_RECIPIENT_ROW_CLASS' => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'USER_ID' => $user['id'],
                    'USER_TYPE' => $user['type'],
                    'NEWSLETTER_RECIPIENT_NAME' => htmlentities(trim($user['lastname'].' '.$user['firstname']), ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_RECIPIENT_FEEDBACK' => $linkCount > 0 ? round(100 /  $linkCount * $feedback) : 0,
                    'NEWSLETTER_RECIPIENT_CLICKS' => $feedback
                ));

                if ($user['type'] == self::USER_TYPE_ACCESS) {
                    $this->_objTpl->touchBlock('access_user_type');
                    $this->_objTpl->hideBlock('newsletter_user_type');
                } else {
                    $this->_objTpl->hideBlock('access_user_type');
                    $this->_objTpl->touchBlock('newsletter_user_type');
                }

                $this->_objTpl->parse("recipient_list");
                $rowNr++;
            }
            if ($rowNr > 0) {
                $paging = getPaging($arrNewsletterData['count'], $pos, ("&cmd=Newsletter&act=feedback&link_id=".$linkId), "", false, $_CONFIG['corePagingLimit']);
                $this->_objTpl->setVariable('NEWSLETTER_RECIPIENTS_PAGING', "<br />".$paging."<br />");
            }
        }
        $this->_objTpl->setVariable('NEWSLETTER_EMAIL_ID', $arrLinkData['email_id']);
        return true;
    }

    function _showRecipientEmailFeedbackAnalysis()
    {
// TODO: refactor method
        die('Feature unavailable');

        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $linkId = isset($_GET['link_id']) ? intval($_GET['link_id']) : 0;

        $recipientId = isset($_REQUEST['recipient_id']) ? intval($_REQUEST['recipient_id']) : 0;
        $recipientType = isset($_REQUEST['recipient_type']) ? $_REQUEST['recipient_type'] : '';
        if ($recipientId > 0) {
            if ($recipientType == 'newsletter') {
                $query = "SELECT lastname, firstname FROM ".DBPREFIX."module_newsletter_user WHERE id=".$recipientId;
            } elseif ($recipientType == 'access') {
                $query = "SELECT tlbProfile.lastname, tlbProfile.firstname
                    FROM ".DBPREFIX."access_users AS tlbUser
                        INNER JOIN ".DBPREFIX."access_user_profile AS tlbProfile ON tlbProfile.user_id = tlbUser.id
                    WHERE tlbUser.id=".$recipientId;
            }
            $objRecipient = $objDatabase->SelectLimit($query, 1);
            if ($objRecipient !== false && $objRecipient->RecordCount() == 1) {
                $recipientLastname = $objRecipient->fields['lastname'];
                $recipientFirstname = $objRecipient->fields['firstname'];
            } else {
                return $this->_mails();
            }
        }

        $mailId = 0;
        $mailTitle = '';
        $query = "SELECT tlbMail.id, tlbMail.subject
            FROM ".DBPREFIX."module_newsletter_email_link AS tlbLink
                INNER JOIN ".DBPREFIX."module_newsletter AS tlbMail ON tlbLink.email_id = tlbMail.id
            WHERE tlbLink.id=".$linkId;
        $objMail = $objDatabase->SelectLimit($query, 1);
        if ($objMail !== false && $objMail->RecordCount() == 1) {
            $mailId = $objMail->fields['id'];
            $mailTitle = $objMail->fields['subject'];
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_FEEDBACK'];
        $this->_objTpl->loadTemplateFile('module_newsletter_user_email_feedback.html');
        $this->_objTpl->setVariable('TXT_NEWSLETTER_USER_FEEDBACK_TITLE', sprintf($_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_EMAIL_FEEDBACK'], htmlentities(trim($recipientLastname." ".$recipientFirstname), ENT_QUOTES, CONTREXX_CHARSET), htmlentities($mailTitle, ENT_QUOTES, CONTREXX_CHARSET)));
        $this->_objTpl->setVariable('NEWSLETTER_LINK_ID', $linkId);

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_LINK_TITLE' => $_ARRAYLANG['TXT_NEWSLETTER_LINK_TITLE'],
            'TXT_NEWSLETTER_LINK_SOURCE' => $_ARRAYLANG['TXT_NEWSLETTER_LINK_SOURCE'],
            'TXT_NEWSLETTER_FUNCTIONS' => $_ARRAYLANG['TXT_NEWSLETTER_FUNCTIONS'],
            'TXT_NEWSLETTER_BACK' => $_ARRAYLANG['TXT_NEWSLETTER_BACK']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB' => $_ARRAYLANG['TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB']
        ));

        $objResultCount = $objDatabase->SelectLimit("SELECT COUNT(id) AS link_count FROM ".DBPREFIX."module_newsletter_email_link_feedback
            WHERE recipient_id = ".$recipientId." AND recipient_type = '".$recipientType."'", 1);
        if ($objResultCount !== false) {
            $linkCount = $objResultCount->fields['link_count'];
        } else {
            $linkCount = 0;
        }

        $rowNr = 0;
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $objResult = $objDatabase->SelectLimit("SELECT
            tblLink.id,
            tblLink.title,
            tblLink.url
            FROM ".DBPREFIX."module_newsletter_email_link_feedback AS tblMailLinkFB
                INNER JOIN ".DBPREFIX."module_newsletter AS tblMail ON tblMail.id = tblMailLinkFB.email_id
                INNER JOIN ".DBPREFIX."module_newsletter_email_link  AS tblLink ON tblLink.id = tblMailLinkFB.link_id
            WHERE
                tblMail.id = ".$mailId."
                AND tblMailLinkFB.recipient_id = ".$recipientId."
                AND tblMailLinkFB.recipient_type = '".$recipientType."'
            ORDER BY tblLink.title ASC", $_CONFIG['corePagingLimit'], $pos);
        if ($objResult !== false) {

            while (!$objResult->EOF) {
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_LINK_ROW_CLASS' => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'NEWSLETTER_LINK_TITLE' => htmlentities($objResult->fields['title'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_LINK_URL' => $objResult->fields['url']
                ));

                $this->_objTpl->parse("link_list");
                $objResult->MoveNext();
                $rowNr++;
            }
            if ($rowNr > 0) {
                $paging = getPaging($linkCount, $pos, ("&cmd=Newsletter&act=feedback&link_id=".$linkId."&recipient_id=".$recipientId."&recipient_type=".$recipientType), "", false, $_CONFIG['corePagingLimit']);
                $this->_objTpl->setVariable('NEWSLETTER_LINKS_PAGING', "<br />".$paging."<br />");
            } else {
                $this->_objTpl->setVariable('NEWSLETTER_USER_NO_FEEDBACK', $_ARRAYLANG['TXT_NEWSLETTER_USER_NO_FEEDBACK']);
                $this->_objTpl->touchBlock('link_list_empty');
                $this->_objTpl->hideBlock('link_list');
            }
        }

        return true;
    }

    function _showRecipientFeedbackAnalysis()
    {

        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        if (   empty($_REQUEST['id'])
            || empty($_REQUEST['recipient_type'])
            || !in_array($_REQUEST['recipient_type'], array(self::USER_TYPE_NEWSLETTER, self::USER_TYPE_ACCESS))
        ) {
            return $this->_userList();
        }

        $recipientId = intval($_REQUEST['id']);
        $recipientType = $_REQUEST['recipient_type'];
        $linkCount = 0;

        if ($recipientType == self::USER_TYPE_NEWSLETTER) {
            $objRecipient = $objDatabase->SelectLimit('SELECT `lastname`, `firstname` FROM `'.DBPREFIX.'module_newsletter_user` WHERE `id`='.$recipientId, 1);
            if ($objRecipient !== false && $objRecipient->RecordCount() == 1) {
                $recipientLastname = $objRecipient->fields['lastname'];
                $recipientFirstname = $objRecipient->fields['firstname'];
            } else {
                return $this->_userList();
            }
        } else {
            $objRecipient = \FWUser::getFWUserObject()->objUser->getUser($recipientId);
            if ($objRecipient) {
                $recipientLastname = $objRecipient->getProfileAttribute('lastname');
                $recipientFirstname = $objRecipient->getProfileAttribute('firstname');
            } else {
                return $this->_userList();
            }
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_NEWSLETTER_USER_ADMINISTRATION'];
        $this->_objTpl->addBlockfile('NEWSLETTER_USER_FILE', 'module_newsletter_user_feedback', 'module_newsletter_user_feedback.html');
        $this->_objTpl->setVariable('TXT_NEWSLETTER_USER_FEEDBACK_TITLE', sprintf($_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_FEEDBACK'], contrexx_raw2xhtml(trim($recipientLastname." ".$recipientFirstname))));

        $this->_objTpl->setVariable(array(
            'TXT_NEWSLETTER_LINK_TITLE' => $_ARRAYLANG['TXT_NEWSLETTER_LINK_TITLE'],
            'TXT_NEWSLETTER_EMAIL' => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL'],
            'TXT_NEWSLETTER_LINK_SOURCE' => $_ARRAYLANG['TXT_NEWSLETTER_LINK_SOURCE'],
            'TXT_NEWSLETTER_FUNCTIONS' => $_ARRAYLANG['TXT_NEWSLETTER_FUNCTIONS'],
            'TXT_NEWSLETTER_BACK' => $_ARRAYLANG['TXT_NEWSLETTER_BACK']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB' => $_ARRAYLANG['TXT_NEWSLETTER_OPEN_LINK_IN_NEW_TAB']
        ));

        $objResultCount = $objDatabase->SelectLimit('
            SELECT COUNT(1) AS `link_count`
              FROM `'.DBPREFIX.'module_newsletter_email_link_feedback`
             WHERE `recipient_id` = '.$recipientId.'
               AND `recipient_type` = \''.$recipientType.'\'', 1);
        if ($objResultCount !== false) {
            $linkCount = $objResultCount->fields['link_count'];
        }

        $rowNr = 0;
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $objResult = $objDatabase->SelectLimit("SELECT
            tblLink.id,
            tblLink.title,
            tblLink.url,
            tblMail.subject
            FROM ".DBPREFIX."module_newsletter_email_link_feedback AS tblMailLinkFB
                INNER JOIN ".DBPREFIX."module_newsletter AS tblMail ON tblMail.id = tblMailLinkFB.email_id
                INNER JOIN ".DBPREFIX."module_newsletter_email_link  AS tblLink ON tblMailLinkFB.link_id = tblLink.id
            WHERE tblMailLinkFB.recipient_id = ".$recipientId."  AND tblMailLinkFB.recipient_type = '".$recipientType."'
            ORDER BY tblLink.title ASC", $_CONFIG['corePagingLimit'], $pos);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_LINK_ROW_CLASS' => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'NEWSLETTER_LINK_TITLE'     => contrexx_raw2xhtml($objResult->fields['title']),
                    'NEWSLETTER_LINK_URL'       => $objResult->fields['url'],
                    'NEWSLETTER_EMAIL'          => $objResult->fields['subject']
                ));

                $this->_objTpl->setGlobalVariable('NEWSLETTER_LINK_ID', $objResult->fields['id']);

                $this->_objTpl->parse("link_list");
                $objResult->MoveNext();
                $rowNr++;
            }
            if ($rowNr > 0) {
                $paging = getPaging($linkCount, $pos, ("&cmd=Newsletter&act=users&tpl=feedback&id=".$recipientId), "", false, $_CONFIG['corePagingLimit']);
                $this->_objTpl->setVariable('NEWSLETTER_LINKS_PAGING', "<br />".$paging."<br />");
            } else {
                $this->_objTpl->setVariable('NEWSLETTER_USER_NO_FEEDBACK', $_ARRAYLANG['TXT_NEWSLETTER_USER_NO_FEEDBACK']);
                $this->_objTpl->touchBlock('link_list_empty');
                $this->_objTpl->hideBlock('link_list');
            }
        }

        return true;
    }

    /**
     * Parse the news details blocks
     *
     * @param \Cx\Core\Html\Sigma                          $objNewsTpl      Template object
     * @param \Cx\Core_Modules\News\Controller\NewsLibrary $objNewsLib      News library object
     * @param object                                       $objNews         Database Records
     * @param integer                                      $currentCategory Current category id
     *
     * @return null
     */
    public function parseNewsDetails(\Cx\Core\Html\Sigma $objNewsTpl, \Cx\Core_Modules\News\Controller\NewsLibrary $objNewsLib, $objNews, &$currentCategory)
    {
        global $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        $objNewsTpl->setVariable(array(
            'NEWS_CATEGORY_NAME' => $objNews->fields['name']
        ));
        $categoryId = $objNews->fields['categoryId'];
        if ($currentCategory == $categoryId && $objNewsTpl->blockExists("news_category")) {
            $objNewsTpl->hideBlock("news_category");
        }
        $currentCategory = $categoryId;
        $newsId = $objNews->fields['newsid'];
        $newsCategories = $objNewsLib->getCategoriesByNewsId($newsId);
        $newstitle = $objNews->fields['newstitle'];
        $newslink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                        'News',
                        $objNewsLib->findCmdById('details', array_keys($newsCategories)),
                        FRONTEND_LANG_ID,
                        array('newsid' => $newsId)
                    );
        $newsUrl = empty($objNews->fields['redirect'])
                            ? (empty($objNews->fields['newscontent'])
                                    ? ''
                                    : $newslink)
                            : $objNews->fields['redirect'];
        $newstext = ltrim(strip_tags($objNews->fields['newscontent']));
        $newsteasertext = ltrim(strip_tags($objNews->fields['teaser_text']));
        if ($objNews->fields['newsuid'] && ($objUser = $objFWUser->objUser->getUser($objNews->fields['newsuid']))) {
            $author = htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET);
        } else {
            $author = $_ARRAYLANG['TXT_ANONYMOUS'];
        }

        list($image, $htmlLinkImage, $imageSource) = \Cx\Core_Modules\News\Controller\NewsLibrary::parseImageThumbnail($objNews->fields['teaser_image_path'], $objNews->fields['teaser_image_thumbnail_path'], $newstitle, $newsUrl);

        $objNewsTpl->setVariable(array(
            'NEWS_CATEGORY_NAME' => contrexx_raw2xhtml($objNews->fields['name']),
            'NEWS_DATE' => date(ASCMS_DATE_FORMAT_DATE, $objNews->fields['newsdate']),
            'NEWS_LONG_DATE' => date(ASCMS_DATE_FORMAT_DATETIME, $objNews->fields['newsdate']),
            'NEWS_TITLE' => contrexx_raw2xhtml($newstitle),
            'NEWS_URL' => $newsUrl,
            'NEWS_TEASER_TEXT' => $newsteasertext,
            'NEWS_TEXT' => $newstext,
            'NEWS_AUTHOR' => $author,
        ));

        $imageTemplateBlock = "news_image";
        if (!empty($image)) {
            $objNewsTpl->setVariable(array(
                'NEWS_IMAGE' => $image,
                'NEWS_IMAGE_SRC' => contrexx_raw2xhtml($imageSource),
                'NEWS_IMAGE_ALT' => contrexx_raw2xhtml($newstitle),
                'NEWS_IMAGE_LINK' => $htmlLinkImage,
            ));

            if ($objNewsTpl->blockExists($imageTemplateBlock)) {
                $objNewsTpl->parse($imageTemplateBlock);
            }
        } else {
            if ($objNewsTpl->blockExists($imageTemplateBlock)) {
                $objNewsTpl->hideBlock($imageTemplateBlock);
            }
        }
        $objNewsTpl->parse("news_list");
    }

    /**
     * Get News content to send email
     *
     * @param string                                       $importTemplate News Template content
     * @param \Cx\Core_Modules\News\Controller\NewsLibrary $objNewsLib     News library object
     * @param object                                       $objNews        Database Records
     * @param boolean                                      $stripTeaser    Strip the Teaser text when true
     *
     * @return string
     */
    public function getNewsMailContent($importTemplate, \Cx\Core_Modules\News\Controller\NewsLibrary $objNewsLib, $objNews, $stripTeaser = false)
    {
        global $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        $content = $this->_getBodyContent($this->GetTemplateSource($importTemplate, 'html'));
        $newstext = ltrim(strip_tags($objNews->fields['newscontent']));
        $newsteasertext = ltrim(strip_tags($objNews->fields['teaser_text']));
        if ($stripTeaser) {
            $newsteasertext = substr($newsteasertext, 0, 100);
        }
        $newsId = $objNews->fields['newsid'];
        $newsCategories = $objNewsLib->getCategoriesByNewsId($newsId);
        $newslink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                        'News',
                        $objNewsLib->findCmdById('details', array_keys($newsCategories)),
                        FRONTEND_LANG_ID,
                        array('newsid' => $newsId)
                    );
        if ($objNews->fields['newsuid'] && ($objUser = $objFWUser->objUser->getUser($objNews->fields['newsuid']))) {
                $author = htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET);
        } else {
                $author = $_ARRAYLANG['TXT_ANONYMOUS'];
        }
        $search = array(
            '[[NEWS_DATE]]',
            '[[NEWS_LONG_DATE]]',
            '[[NEWS_TITLE]]',
            '[[NEWS_URL]]',
            '[[NEWS_IMAGE_PATH]]',
            '[[NEWS_TEASER_TEXT]]',
            '[[NEWS_TEXT]]',
            '[[NEWS_AUTHOR]]',
            '[[NEWS_TYPE_NAME]]',
            '[[NEWS_CATEGORY_NAME]]'
        );
        $replace = array(
            date(ASCMS_DATE_FORMAT_DATE, $objNews->fields['newsdate']),
            date(ASCMS_DATE_FORMAT_DATETIME, $objNews->fields['newsdate']),
            $objNews->fields['newstitle'],
            $newslink,
            htmlentities($objNews->fields['teaser_image_thumbnail_path'], ENT_QUOTES, CONTREXX_CHARSET),
            $newsteasertext,
            $newstext,
            $author,
            $objNews->fields['typename'],
            $objNews->fields['name']
        );

        return str_replace($search, $replace, $content);
    }
}


if (!class_exists('DBIterator', false)) {

    /**
     * Iterator wrapper for adodb result objects
     *
     * @copyright   CLOUDREXX CMS - CLOUDREXX AG
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @package     cloudrexx
     * @subpackage  module_newsletter
     */
    class DBIterator implements \Iterator {
        /**
         * The result object of adodb
         */
        private $obj;

        /**
         * If the result was empty
         *
         * (To prevent illegal object access)
         */
        private $empty;

        /**
         * The position in the rows
         *
         * Mainly just to have something to return in the
         * key() method.
         */
        private $position = 0;

        /**
         * Assign the object
         *
         * @param       object (adodb result object)
         */
        public function __construct($obj) {
            $this->empty = (!($obj instanceof \ADORecordSet_pdo) && empty($obj->fields));

            $this->obj = $obj;
        }

        /**
         * Go back to first position
         */
        public function rewind() {
            if (!$this->empty) {
                $this->obj->MoveFirst();
            }

            $this->position = 0;
        }

        /**
         * Return the current object
         *
         * @return      array
         */
        public function current() {
            return $this->obj->fields;
            // if valid return false, this function should never be called,
            // so no problem with illegal access here i guess
        }

        /**
         * Return the current key
         *
         * @return      int
         */
        public function key() {
            return $this->position;
        }

        /**
         * Go to the next item
         */
        public function next() {
            if (!$this->empty) {
                $this->obj->MoveNext();
            }

            ++$this->position;
        }

        /**
         * Return if there are any items left
         *
         * @return      bool
         */
        public function valid() {
            if ($this->empty) {
                return false;
            }

            return !$this->obj->EOF;
        }
    }
}
