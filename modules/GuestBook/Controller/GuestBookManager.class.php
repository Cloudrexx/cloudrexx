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
 * GuestBook Module
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
 * The admin class to manage all the guestbook entries
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_guestbook
 */
class GuestBookManager extends GuestBookLibrary
{
    public $_objTpl;
    public $pageTitle='';
    public $strErrMessage = '';
    public $strOkMessage = '';
    public $imagePath;
    public $langId;
    public $arrSettings = array();

    private $act = '';

    /**
     * constructor
     */
    function __construct()
    {
        global $_ARRAYLANG, $objTemplate, $objInit;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/GuestBook/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->imagePath = ASCMS_MODULE_FOLDER;
        $this->langId=$objInit->userFrontendLangId;
        $this->getSettings();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION","
            <a href='?cmd=GuestBook' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_OVERVIEW']."</a>
            <a href='?cmd=GuestBook&amp;act=add' class='".($this->act == 'add' ? 'active' : '')."'>".$_ARRAYLANG['TXT_ADD_GUESTBOOK_ENTRY']."</a>
            <a href='?cmd=GuestBook&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_SETTINGS']."</a>");
    }


    /**
     * Gets the requested methods
     * @global   array
     * @global   array
     * @global   \Cx\Core\Html\Sigma
     * @return   string    parsed content
     */
    function getPage()
    {
        global $_ARRAYLANG, $objTemplate;

        if(!isset($_GET['act'])){
            $_GET['act']="";
        }

        switch($_GET['act']){
            case "settings":
                $this->_settings();
                break;
            case "delete":
                $this->_delete();
                $this->_overview();
                break;
            case "update":
                $this->_update();
                $this->_overview();
                break;
            case "store":
                $this->_store();
                $this->_overview();
                break;
            case "add":
                $this->_showAdd();
                break;
            case "edit":
                $this->_showEdit();
                break;
            case "multi_delete":
                $this->_multiDelete();
                $this->_overview();
                break;
            case "multi_activate":
                $this->_multiActivate();
                $this->_overview();
                break;
            case "multi_deactivate":
                $this->_multiDeactivate();
                $this->_overview();
            case "activate":
                $id = intval($_GET['id']);
                $this->_activateEntry($id);
                $this->_overview();
                break;
            default:
                $this->_overview();
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE' => $this->pageTitle,
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT' => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    function _settings()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->pageTitle = $_ARRAYLANG['TXT_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_guestbook_settings.html',true,true);

        // Store settings
        if (isset($_POST['store'])) {
            $activate = isset($_POST['guestbook_activate_submitted_entries']) ? intval($_POST['guestbook_activate_submitted_entries']) : 0;
            $notification = isset($_POST['guestbook_send_notification_email']) ? intval($_POST['guestbook_send_notification_email']) : 0;
            $replace_at = isset($_POST['guestbook_replace_at']) ? intval($_POST['guestbook_replace_at']) : 0;
            $maintainLangSeparated = isset($_POST['guestbook_only_lang_entries']) ? intval($_POST['guestbook_only_lang_entries']) : 0;

            $objDatabase->Execute("UPDATE ".DBPREFIX."module_guestbook_settings SET value='".$activate."' WHERE name='guestbook_activate_submitted_entries'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_guestbook_settings SET value='".$notification."' WHERE name='guestbook_send_notification_email'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_guestbook_settings SET value='".$replace_at."' WHERE name='guestbook_replace_at'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_guestbook_settings SET value='".$maintainLangSeparated."' WHERE name='guestbook_only_lang_entries'");

            $this->strOkMessage = $_ARRAYLANG['TXT_GUESTBOOK_RECORD_STORED_SUCCESSFUL'];
            // renew the settings values
            $this->getSettings();
        }

         // Show settings
        $this->_objTpl->setVariable(array(
            'GUESTBOOK_ACTIVATE_SUBMITTED_ENTRIES' => $this->arrSettings['guestbook_activate_submitted_entries'] == '1' ? "checked=\"checked\"" : "",
            'GUESTBOOK_SEND_NOTIFICATION_EMAIL' => $this->arrSettings['guestbook_send_notification_email'] == '1' ? "checked=\"checked\"" : "",
            'GUESTBOOK_REPLACE_AT' => $this->arrSettings['guestbook_replace_at'] == '1' ? "checked=\"checked\"" : "",
            'GUESTBOOK_ONLY_LANG_ENTRIES' => $this->arrSettings['guestbook_only_lang_entries'] == '1' ? "checked=\"checked\"" : "",
            'TXT_STORE' => $_ARRAYLANG['TXT_STORE'],
            'TXT_SETTINGS'  => $_ARRAYLANG['TXT_SETTINGS'],
            'TXT_AUTO_ACTIVATE_NEW_ENTRIES' => $_ARRAYLANG['TXT_AUTO_ACTIVATE_NEW_ENTRIES'],
            'TXT_SEND_NOTIFICATION_MESSAGE' => $_ARRAYLANG['TXT_SEND_NOTIFICATION_MESSAGE'],
            'TXT_REPLACE_AT' => $_ARRAYLANG['TXT_REPLACE_AT'],
            'TXT_GUESTBOOK_ONLY_LANG_ENTRIES' => $_ARRAYLANG['TXT_GUESTBOOK_ONLY_LANG_ENTRIES']
        ));
    }


    /**
     * shows the edit page
     *
     * @global  ADONewConnection
     * @global  array
     * @access private
     */
    function _showAdd()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_guestbook_add.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_ADD_GUESTBOOK_ENTRY'];

        $javascript = $this->_getJavaScript();

        $this->_objTpl->setVariable(array(
            'TXT_ADD_ENTRY'     => $_ARRAYLANG['TXT_ADD_ENTRY'],
            'TXT_FORENAME'      => $_ARRAYLANG['TXT_FORENAME'],
            'TXT_NAME'          => $_ARRAYLANG['TXT_NAME'],
            'TXT_COMMENT'       => $_ARRAYLANG['TXT_COMMENT'],
            'TXT_LOCATION'      => $_ARRAYLANG['TXT_LOCATION'],
            'TXT_SEX'           => $_ARRAYLANG['TXT_SEX'],
            'TXT_MALE'          => $_ARRAYLANG['TXT_MALE'],
            'TXT_FEMALE'        => $_ARRAYLANG['TXT_FEMALE'],
            'TXT_EMAIL'         => $_ARRAYLANG['TXT_EMAIL'],
            'TXT_HOMEPAGE'      => $_ARRAYLANG['TXT_HOMEPAGE'],
            'TXT_STORE'         => $_ARRAYLANG['TXT_STORE'],
            'TXT_RESET'         => $_ARRAYLANG['TXT_RESET'],
// TODO: $_ARRAYLANG['txtBackToIndex'] does not exist
//            'TXT_BACK_TO_INDEX' => $_ARRAYLANG['txtBackToIndex'], // N/A
            'GUESTBOOK_JAVASCRIPT'  => $javascript
        ));
        // $this->_store();
    }


    /**
     * Save an entry
     *
     * @global  ADONewConnection
     * @global  array
     * @return  string   $status
     */
    function _store()
    {
        global $objDatabase, $_ARRAYLANG;

        $error = '';
        if (!empty($_POST['forename']) AND !empty($_POST['name']) /*AND !empty($_POST['comment'])*/) {
            $forename = contrexx_addslashes(strip_tags($_POST['forename']));
            $name = contrexx_addslashes(strip_tags($_POST['name']));
            $gender = contrexx_addslashes(strip_tags($_POST['malefemale']));
            $mail = isset($_POST['email']) ? contrexx_addslashes($_POST['email']) : '';
            $url = (isset($_POST['url'])&& strlen($_POST['url'])>7) ?  contrexx_addslashes(strip_tags($_POST['url'])) : '';
            $comment = contrexx_addslashes(nl2br($this->addHyperlinking(strip_tags($_POST['comment']))));
            $location = contrexx_addslashes(strip_tags($_POST['location']));

            if (!empty($url)) {
                if (!\FWValidator::isUri($url)) {
                    $error .= $_ARRAYLANG['TXT_INVALID_INTERNET_ADDRESS']."<br />";
                }
            }
            if (!\FWValidator::isEmail($mail)) {
                $error .= $_ARRAYLANG['TXT_INVALID_EMAIL_ADDRESS']."<br />";
            }
            if (empty($error)) {
                $query = "
                    INSERT INTO ".DBPREFIX."module_guestbook (
                        forename, name, gender,
                        url, datetime, email, comment,
                        location, lang_id
                    ) VALUES (
                        '$forename', '$name', '$gender',
                        '$url', '".date('Y-m-d H:i:s')."', '$mail', '$comment',
                        '$location', '$this->langId'
                    )";
                $objDatabase->Execute($query);
                $this->strOkMessage = $_ARRAYLANG['TXT_GUESTBOOK_RECORD_STORED_SUCCESSFUL'];
            } else {
                $this->strErrMessage = $error;
            }
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_FILL_OUT_ALL_REQUIRED_FIELDS'];
        }
    }


    /**
     * shows the edit page
     *
     * @global  ADONewConnection
     * @global  array
     * @access private
     */

    function _showEdit()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_guestbook_edit.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_EDIT_GUESTBOOK'];
        $javascript = $this->_getJavaScript();

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_ENTRY' => $_ARRAYLANG['TXT_EDIT_ENTRY'],
            'TXT_NAME'       => $_ARRAYLANG['TXT_NAME'],
            'TXT_COMMENT'    => $_ARRAYLANG['TXT_COMMENT'],
            'TXT_LOCATION'   => $_ARRAYLANG['TXT_LOCATION'],
            'TXT_SEX'        => $_ARRAYLANG['TXT_SEX'],
            'TXT_MALE'       => $_ARRAYLANG['TXT_MALE'],
            'TXT_FEMALE'     => $_ARRAYLANG['TXT_FEMALE'],
            'TXT_EMAIL'      => $_ARRAYLANG['TXT_EMAIL'],
            'TXT_HOMEPAGE'   => $_ARRAYLANG['TXT_HOMEPAGE'],
            'TXT_DATE'       => $_ARRAYLANG['TXT_DATE'],
            'TXT_STORE'      => $_ARRAYLANG['TXT_STORE'],
            'TXT_RESET'      => $_ARRAYLANG['TXT_RESET'],
            'GUESTBOOK_JAVASCRIPT'  => $javascript
        ));

        if(!empty($_GET['id'])){
            $query = "SELECT forename,
                               name,
                               id,
                               gender,
                               url,
                               location,
                               email,
                               comment,
                               datetime
                          FROM ".DBPREFIX."module_guestbook
                         WHERE id = ".intval($_GET['id']);
            $objResult = $objDatabase->SelectLimit($query, 1);

            while (!$objResult->EOF) {
                switch($objResult->fields["gender"]) {
                    case "M" : $gender_m = "checked"; break;
                    case "F" : $gender_f = "checked"; break;
                    default  : $gender_m = ""; $gender_f = ""; break;
                }
                $this->_objTpl->setVariable(array(
                    'GUESTBOOK_FORENAME'  => htmlentities($objResult->fields["forename"], ENT_QUOTES, CONTREXX_CHARSET),
                    'GUESTBOOK_NAME'      => htmlentities($objResult->fields["name"], ENT_QUOTES, CONTREXX_CHARSET),
                    'GUESTBOOK_CHECKED_M' => $gender_m,
                    'GUESTBOOK_CHECKED_F' => $gender_f,
                    'GUESTBOOK_URL'       => htmlentities($objResult->fields["url"], ENT_QUOTES, CONTREXX_CHARSET),
                    'GUESTBOOK_LOCATION'  => htmlentities($objResult->fields["location"], ENT_QUOTES, CONTREXX_CHARSET),
                    'GUESTBOOK_MAIL'      => htmlentities($objResult->fields["email"], ENT_QUOTES, CONTREXX_CHARSET),
                    'GUESTBOOK_COMMENT'   => $objResult->fields["comment"],
                    'GUESTBOOK_DATE'         => $objResult->fields["datetime"],
                    'GUESTBOOK_ID'        => $objResult->fields["id"]
                ));
                $objResult->MoveNext();
            }
        }
    }


    /**
     * Deletes the selected guestbook entry
     *
     * @global  ADONewConnection
     * @global  array
     * @return  string   status message
     */
    function _delete()
    {
        global $objDatabase, $_ARRAYLANG;

        $id = intval($_GET['id']);
        if($this->_deleteEntry($id)){
            $this->strOkMessage = $_ARRAYLANG['TXT_GUESTBOOK_RECORD_DELETED_SUCCESSFUL'];
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_GUESTBOOK_RECORD_DELETE_ERROR'];
        }
    }


    /**
     * default guestbook entries list
     *
     * @global  ADONewConnection
     * @global  array
     * @global  array
     */
    function _overview()
    {
        global $objDatabase, $_CONFIG, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_guestbook_show.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_GUESTBOOK'];

        $this->_objTpl->setVariable(array(
            'TXT_MANAGE_ENTRIES'         => $_ARRAYLANG['TXT_MANAGE_ENTRIES'],
            'TXT_NAME'                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_COMMENT'                => $_ARRAYLANG['TXT_COMMENT'],
            'TXT_LOCATION'               => $_ARRAYLANG['TXT_LOCATION'],
            'TXT_CONFIRM_DELETE_DATA'    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_SELECT_ALL'             => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'           => $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_SUBMIT_SELECT'          => $_ARRAYLANG['TXT_SUBMIT_SELECT'],
            'TXT_SUBMIT_DELETE'          => $_ARRAYLANG['TXT_SUBMIT_DELETE'],
            'TXT_SUBMIT_ACTIVATE'        => $_ARRAYLANG['TXT_SUBMIT_ACTIVATE'],
            'TXT_SUBMIT_DEACTIVATE'      => $_ARRAYLANG['TXT_SUBMIT_DEACTIVATE'],
            'TXT_ACTION'                 => $_ARRAYLANG['TXT_GUESTBOOK_ACTION'],
            'TXT_DELETE_CATEGORY_ALL'    => $_ARRAYLANG['TXT_DELETE_CATEGORY_ALL']
        ));

        $pos = (isset($_GET['pos']) ? intval($_GET['pos']) : 0);

        /** start paging **/
        $query = "SELECT *
            FROM ".DBPREFIX."module_guestbook "
            .($this->arrSettings['guestbook_only_lang_entries'] ? "WHERE lang_id='$this->langId' " : '');
        $objResult = $objDatabase->Execute($query);

        $count = $objResult->RecordCount();
// TODO: $_ARRAYLANG['TXT_GUESTBOOK_ENTRIES'] does not exist
//        $paging = getPaging($count, $pos, "&amp;cmd=GuestBook", "<b>".$_ARRAYLANG['TXT_GUESTBOOK_ENTRIES']."</b>", true);
        $paging = getPaging($count, $pos, "&cmd=GuestBook", '', true);

        $this->_objTpl->setVariable("GUESTBOOK_PAGING", $paging);
        /** end paging **/

        $query = "
            SELECT id, status, forename, name, gender,
                   url, email, comment, location,
                   datetime
              FROM ".DBPREFIX."module_guestbook".
                    ($this->arrSettings['guestbook_only_lang_entries']
                        ? " WHERE lang_id='$this->langId'" : '').
                    " ORDER BY id DESC";
        $objResult = $objDatabase->SelectLimit(
            $query, $_CONFIG['corePagingLimit'], $pos);

        $i = 0;
        while(!$objResult->EOF) {
// TODO: $_ARRAYLANG['guestbookGenderMale'] and
// $_ARRAYLANG['guestbookGenderFemale'] do not exist
//            $gender = ($objResult->fields["gender"] == "M") ? $_ARRAYLANG['guestbookGenderMale'] : $_ARRAYLANG['guestbookGenderFemale'];
            $gender = '';
            $url = '';
            $mail = '';
            if ($objResult->fields['url'] != '') {
                $url = "<a href='".$objResult->fields['url']."' target='_blank'><img alt=\"".$objResult->fields['url']."\" title=\"".$objResult->fields['url']."\" src=\".." . $this->imagePath . "/GuestBook/View/Media/www.gif\" border=\"0\" /></a>";
            }
            if ($objResult->fields['email'] != '') {
                $mail = "<a href=\"mailto:".$objResult->fields["email"]."\"><img alt=\"".$objResult->fields["email"]."\" title=\"".$objResult->fields["email"]."\" src=\".." . $this->imagePath . "/GuestBook/View/Media/email.gif\" border=\"0\" /></a>";
            }
            $statusIcon = ($objResult->fields['status'] == 0) ? 'led_red' : 'led_green';
            if ($objResult->fields['status'] == 0) {
                $rowclass = 'rowWarn';
            } else {
                $rowclass = ($i % 2) ? 'row1' : 'row2';
            }
            $this->_objTpl->setVariable(array(
                       'GUESTBOOK_ROWCLASS' => $rowclass,
                       'GUESTBOOK_STATUS'   => $statusIcon,
                       'GUESTBOOK_FORENAME'    => htmlentities($objResult->fields["forename"], ENT_QUOTES, CONTREXX_CHARSET),
                       'GUESTBOOK_NAME'        => htmlentities($objResult->fields["name"], ENT_QUOTES, CONTREXX_CHARSET),
                       'GUESTBOOK_GENDER'   => $gender,
                       'GUESTBOOK_URL'      => $url,
                       'GUESTBOOK_LOCATION' => htmlentities($objResult->fields["location"], ENT_QUOTES, CONTREXX_CHARSET),
                       'GUESTBOOK_DATE'     => date(ASCMS_DATE_FORMAT, strtotime($objResult->fields['datetime'])),
                       'GUESTBOOK_MAIL'     => $mail,
                       'GUESTBOOK_COMMENT'  => nl2br($objResult->fields["comment"]),
                       'GUESTBOOK_ID'       => $objResult->fields["id"],
            ));
            $this->_objTpl->parse('guestbook_row');
            $i++;
            $objResult->MoveNext();
        }
        if ($i == 0) {
            $this->_objTpl->hideBlock('guestbook_row');
        }
    }


    /**
     * Update guestbook
     *
     * @global  ADONewConnection
     * @global  array
     */
    function _update()
    {
        global $objDatabase, $_ARRAYLANG;
        $guestbookId = intval($_GET['id']);
        $error = "";

        if (!empty($guestbookId)) {
            $forename = contrexx_addslashes(strip_tags($_POST['forename']));
            $name      = contrexx_addslashes(strip_tags($_POST['name']));
            $gender   = contrexx_addslashes(strip_tags($_POST['malefemale']));
            $mail = isset($_POST['email']) ?  contrexx_addslashes(strip_tags($_POST['email'])) : '';
            $url = (isset($_POST['url'])&& strlen($_POST['url'])>7) ?  contrexx_addslashes(strip_tags($_POST['url'])) : "";
            $comment  = contrexx_addslashes(strip_tags($_POST['comment']));
            $location = contrexx_addslashes(strip_tags($_POST['location']));
            $date     = contrexx_addslashes(strip_tags($_POST['datetime']));

            $objValidator = new \FWValidator();

            if(!empty($url)) {
                if (!$this->isUrl($url)) {
                    $error.= $_ARRAYLANG['TXT_INVALID_INTERNET_ADDRESS']."<br />";
                }
            }
            if(!$objValidator->isEmail($mail)) {
                $error.= $_ARRAYLANG['TXT_INVALID_EMAIL_ADDRESS']."<br />";
            }
            if(!empty($forename) && !empty($name) /*&& !empty($comment) && empty($error)*/) {
                $query = "UPDATE ".DBPREFIX."module_guestbook
                               SET forename='$forename',
                                      name='$name',
                                   gender='$gender',
                                   email='$mail',
                                   url='$url',
                                   comment='$comment',
                                   location='$location',
                                   datetime='$date',
                                   lang_id='$this->langId'
                             WHERE id=$guestbookId";
                $objDatabase->Execute($query);
            }
        }
        if (empty($error)) {
            $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
        } else {
            $this->strErrMessage = $error;
        }
    }


    /**
     * Activate Entry
     *
     * Activates or deactivates an entry
     */
    function _activateEntry($id, $act=NULL)
    {
        global $objDatabase;

        if ($act=="activate") {
            $status = 1;
        } elseif ($act=="deactivate") {
            $status = 0;
        } else {
            $query = "SELECT * FROM ".DBPREFIX."module_guestbook WHERE id = $id";
            $objResult = $objDatabase->Execute($query);

            if ($objResult->fields['status'] == 0) {
                $status = 1;
            } else {
                $status = 0;
            }
        }

        $query = "UPDATE ".DBPREFIX."module_guestbook SET status = $status WHERE id = $id";
        $objDatabase->Execute($query);
    }


    /**
     * Deletes an entry
     */
    function _deleteEntry($id)
    {
        global $objDatabase;

        return $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_guestbook WHERE id = $id");
    }


    /**
     * Activates one or more entries
     */
    function _multiActivate()
    {
        if (isset($_POST['selectedId'])) {
            foreach ($_POST['selectedId'] as $intCatId) {
                $this->_activateEntry($intCatId, "activate");
            }
        }
    }


    /**
     * Deactivates one or more entries
     */
    function _multiDeactivate()
    {
        if (isset($_POST['selectedId'])) {
            foreach ($_POST['selectedId'] as $intCatId) {
                $this->_activateEntry($intCatId, "deactivate");
            }
        }
    }


    /**
     * Deletes one or more entries
     */
    function _multiDelete()
    {
        global $_ARRAYLANG;

        if (isset($_POST['selectedId'])) {
            $error = false;
            foreach ($_POST['selectedId'] as $intCatId) {
                if (!$this->_deleteEntry($intCatId)) {
                    $error = true;
                }
            }
            if ($error) {
                $this->strErrMessage = $_ARRAYLANG['TXT_GUESTBOOK_RECORD_MULTI_DELETE_ERROR'];
            }
        }
    }
}

?>
