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
 * FileSharingManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_filesharing
 */
namespace Cx\Modules\FileSharing\Controller;
/**
 * FileSharingManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_filesharing
 */
class FileSharingManager extends FileSharingLib
{
    private $_objTpl;

    public function __construct(&$objTpl)
    {
        global $_ARRAYLANG, $objInit;

        $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('FileSharing'));

        $this->_objTpl = $objTpl;
        $this->_objTpl->setRoot(\Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath() . '/FileSharing/View/Template/Backend');
        if ($_GET['act'] == 'settings') {
            $templateFile = 'module_filesharing_settings.html';
        } else {
            $templateFile = 'module_filesharing_detail.html';
        }
        $this->_objTpl->loadTemplateFile($templateFile, true, true);
        \JS::activate("cx");
    }

    public function getDetailPage()
    {
        global $_ARRAYLANG, $objDatabase;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $file = str_replace($cx->getWebsiteOffsetPath(), '', $_GET["path"]) . $_GET["file"];
        $objResult = $objDatabase->Execute("SELECT `id`, `file`, `source`, `hash`, `check`, `expiration_date` FROM " . DBPREFIX . "module_filesharing WHERE `source` = '" . contrexx_raw2db($file) . "'");

        $existing = $objResult !== false && $objResult->RecordCount() > 0;
        if ($_GET["switch"]) {
            if ($existing) {
                $objDatabase->Execute("DELETE FROM " . DBPREFIX . "module_filesharing WHERE `source` = '" . contrexx_raw2db($file) . "'");
            } else {
                $hash = FileSharingLib::createHash();
                $check = FileSharingLib::createCheck($hash);
                $source = str_replace($cx->getWebsiteOffsetPath(), '', $_GET["path"]) . $_GET["file"];
                $objDatabase->Execute("INSERT INTO " . DBPREFIX . "module_filesharing (`file`, `source`, `hash`, `check`) VALUES ('" . contrexx_raw2db($source) .  "', '" . contrexx_raw2db($source) . "', '" . contrexx_raw2db($hash) . "', '" . contrexx_raw2db($check) . "')");
            }

            $existing = !$existing;
        }

        if ($existing) {
            $this->_objTpl->setVariable(array(
                'FILE_STATUS' => $_ARRAYLANG["TXT_FILESHARING_SHARED"],
                'FILE_STATUS_SWITCH' => $_ARRAYLANG["TXT_FILESHARING_STOP_SHARING"],
                'FILE_STATUS_SWITCH_HREF' => 'index.php?cmd=Media&amp;archive=FileSharing&amp;act=filesharing&amp;path=' . $_GET["path"] . '&amp;file=' . $_GET["file"] . '&amp;switch=1',
            ));
            $this->_objTpl->touchBlock('shared');
        } else {
            $this->_objTpl->setVariable(array(
                'FILE_STATUS' => $_ARRAYLANG["TXT_FILESHARING_NOT_SHARED"],
                'FILE_STATUS_SWITCH' => $_ARRAYLANG["TXT_FILESHARING_START_SHARING"],
                'FILE_STATUS_SWITCH_HREF' => 'index.php?cmd=Media&amp;archive=FileSharing&amp;act=filesharing&amp;path=' . $_GET["path"] . '&amp;file=' . $_GET["file"] . '&amp;switch=1',
            ));
            $this->_objTpl->hideBlock('shared');
        }

        if ($_POST["shareFiles"]) {
            $emails = array();
            foreach(preg_split('/[;,\s]+/', $_POST["email"]) as $email){
                if(\FWValidator::isEmail($email)){
                    $emails[] = contrexx_input2raw($email);
                }
            }
            if (count($emails) > 0) {
                FileSharingLib::sendMail($objResult->fields["id"], $_POST["subject"], $emails, $_POST["message"]);
            }
        } elseif ($_POST["saveExpiration"]) {
            if ($_POST["expiration"]) {
                $objDatabase->Execute("UPDATE " . DBPREFIX . "module_filesharing SET `expiration_date` = NULL WHERE `id` = " . $objResult->fields["id"]);
            } else {
                $objDatabase->Execute("UPDATE " . DBPREFIX . "module_filesharing SET `expiration_date` = '" . date('Y-m-d H:i:s', strtotime($_POST["expirationDate"])) . "' WHERE `id` = " . $objResult->fields["id"]);
            }
        }

        $objResult = $objDatabase->Execute("SELECT `id`, `hash`, `check`, `expiration_date` FROM " . DBPREFIX . "module_filesharing WHERE `source` = '" . contrexx_raw2db($file) . "'");

        $this->_objTpl->setVariable(array(
            'FORM_ACTION' => 'index.php?cmd=Media&amp;archive=FileSharing&amp;act=filesharing&amp;path=' . $_GET["path"] . '&amp;file=' . $_GET["file"],
            'FORM_METHOD' => 'POST',

            'FILESHARING_INFO' => $_ARRAYLANG['TXT_FILESHARING_INFO'],
            'FILESHARING_LINK_BACK_HREF' => 'index.php?cmd=Media&amp;archive=FileSharing&amp;path=' . $_GET["path"],
            'FILESHARING_LINK_BACK' => $_ARRAYLANG['TXT_FILESHARING_LINK_BACK'],
            'FILESHARING_DOWNLOAD_LINK' => $_ARRAYLANG['TXT_FILESHARING_DOWNLOAD_LINK'],
            'FILE_DOWNLOAD_LINK_HREF' => FileSharingLib::getDownloadLink($objResult->fields["id"]),
            'FILE_DELETE_LINK_HREF' => FileSharingLib::getDeleteLink($objResult->fields["id"]),
            'FILESHARING_DELETE_LINK' => $_ARRAYLANG['TXT_FILESHARING_DELETE_LINK'],
            'FILESHARING_STATUS' => $_ARRAYLANG['TXT_FILESHARING_STATUS'],

            'FILESHARING_EXPIRATION' => $_ARRAYLANG['TXT_FILESHARING_EXPIRATION'],
            'FILESHARING_NEVER' => $_ARRAYLANG['TXT_FILESHARING_NEVER'],
            'FILESHARING_EXPIRATION_CHECKED' => htmlentities($objResult->fields["expiration_date"] == NULL ? 'checked="checked"' : '', ENT_QUOTES, CONTREXX_CHARSET),
            'FILESHARING_EXPIRATION_DATE' => htmlentities($objResult->fields["expiration_date"] != NULL ?
                    date('d.m.Y H:i', strtotime($objResult->fields["expiration_date"])) : date('d.m.Y H:i', time() + 3600 * 24 * 7), ENT_QUOTES, CONTREXX_CHARSET),

            'FILESHARING_SEND_MAIL' => $_ARRAYLANG['TXT_FILESHARING_SEND_MAIL'],
            'FILESHARING_EMAIL' => $_ARRAYLANG["TXT_FILESHARING_EMAIL"],
            'FILESHARING_EMAIL_INFO' => $_ARRAYLANG["TXT_FILESHARING_EMAIL_INFO"],
            'FILESHARING_SUBJECT' => $_ARRAYLANG["TXT_FILESHARING_SUBJECT"],
            'FILESHARING_SUBJECT_INFO' => $_ARRAYLANG["TXT_FILESHARING_SUBJECT_INFO"],
            'FILESHARING_MESSAGE' => $_ARRAYLANG["TXT_FILESHARING_MESSAGE"],
            'FILESHARING_MESSAGE_INFO' => $_ARRAYLANG["TXT_FILESHARING_MESSAGE_INFO"],
            'FILESHARING_SEND' => $_ARRAYLANG["TXT_FILESHARING_SEND"],
            'FILESHARING_SAVE' => $_ARRAYLANG["TXT_FILESHARING_SAVE"],
            'TXT_CORE_MAILTEMPLATE_NOTE_TO' => $_ARRAYLANG['TXT_CORE_MAILTEMPLATE_NOTE_TO'],
        ));
    }

    public function parseSettingsPage()
    {
        global $_ARRAYLANG, $objDatabase;

        \Cx\Core\Setting\Controller\Setting::init('FileSharing', 'config');
        if (!\Cx\Core\Setting\Controller\Setting::getValue('permission','FileSharing')) {
            \Cx\Core\Setting\Controller\Setting::add('permission', 'off');
        }

        if (isset($_POST['save_settings'])) {
            $this->saveSettings();
        }

        $this->_objTpl->setVariable(array(
            'FILESHARING_INFO' => $_ARRAYLANG["TXT_FILESHARING_SETTINGS_GENERAL_INFORMATION"],
            'FILESHARING_MAIL_TEMPLATES' => $_ARRAYLANG["TXT_FILESHARING_MAIL_TEMPLATES"],
            'TXT_FILESHARING_SECURITY' => $_ARRAYLANG["TXT_FILESHARING_SECURITY"],
            'TXT_FILESHARING_SECURITY_INFO' => $_ARRAYLANG["TXT_FILESHARING_SECURITY_INFO"],
            'TXT_FILESHARING_APPLICATION_NAME' => $_ARRAYLANG["TXT_FILESHARING_SETTINGS_GENERAL_MODULE_NAME_TITLE"],
            'FILESHARING_APPLICATION_NAME' => $_ARRAYLANG["TXT_FILESHARING_MODULE"],
            'TXT_FILESHARING_DESCRIPTION' => $_ARRAYLANG["TXT_FILESHARING_SETTINGS_GENERAL_MODULE_DESCRIPTION_TITLE"],
            'FILESHARING_DESCRIPTION' => $_ARRAYLANG["TXT_FILESHARING_SETTINGS_GENERAL_MODULE_DESCRIPTION"],
            'TXT_FILESHARING_MANUAL' => $_ARRAYLANG["TXT_FILESHARING_SETTINGS_GENERAL_MODULE_MANUAL_TITLE"],
            'FILESHARING_MANUAL' => $_ARRAYLANG["TXT_FILESHARING_SETTINGS_GENERAL_MODULE_MANUAL"],
        ));

        /**
         * parse mailtemplates
         */
        $arrActiveSystemFrontendLanguages = \FWLanguage::getActiveFrontendLanguages();
        foreach ($arrActiveSystemFrontendLanguages as $activeLang) {
            $objMailTemplate = $objDatabase->Execute("SELECT `subject`, `content` FROM " . DBPREFIX . "module_filesharing_mail_template WHERE `lang_id` = " . intval($activeLang["id"]));
            if ($objMailTemplate !== false) {
                $content = str_replace(array('{', '}'), array('[[', ']]'), $objMailTemplate->fields["content"]);
                $this->_objTpl->setVariable(array(
                    'FILESHARING_MAIL_SUBJECT' => htmlentities($objMailTemplate->fields["subject"], ENT_QUOTES, CONTREXX_CHARSET),
                    'FILESHARING_MAIL_CONTENT' => htmlentities($content, ENT_QUOTES, CONTREXX_CHARSET),
                ));
            }
            $this->_objTpl->setVariable(array(
                'TXT_MAIL_SUBJECT' => $_ARRAYLANG['TXT_MAIL_SUBJECT'],
                'TXT_MAIL_CONTENT' => $_ARRAYLANG['TXT_MAIL_CONTENT'],

                'LANG_NAME' => $activeLang["name"],
                'LANG' => $activeLang["id"],
            ));
            $this->_objTpl->parse('filesharing_email_template');
        }

        /**
         * parse permissions
         */
        $oldFilesharingPermission = \Cx\Core\Setting\Controller\Setting::getValue('permission','FileSharing');
        $objFWUser = \FWUser::getFWUserObject();
        $arrAssociatedGroups = array();
        $arrAssociatedGroupOptions = array();

        if (!is_numeric($oldFilesharingPermission)) {
            // Get all groups
            $objGroup = $objFWUser->objGroup->getGroups();
        } else {
            // Get access groups
            $objGroup = $objFWUser->objGroup->getGroups(
                array('dynamic' => $oldFilesharingPermission)
            );
            $arrAssociatedGroups = $objGroup->getLoadedGroupIds();
        }

        $objGroup = $objFWUser->objGroup->getGroups();
        while (!$objGroup->EOF) {
            $option = '<option value="' . $objGroup->getId() . '">' . htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET) . ' [' . $objGroup->getType() . ']</option>';

            if (in_array($objGroup->getId(), $arrAssociatedGroups)) {
                $arrAssociatedGroupOptions[] = $option;
            } else {
                $arrNotAssociatedGroupOptions[] = $option;
            }

            $objGroup->next();
        }

        $this->_objTpl->setVariable(array(
            'FILESHARING_ALLOW_USER_UPLOAD_ON' => ($oldFilesharingPermission == 'on') ? 'checked="checked"' : '',
            'FILESHARING_ALLOW_USER_UPLOAD_OFF' => ($oldFilesharingPermission == 'off') ? 'checked="checked"' : '',
            'FILESHARING_ALLOW_USER_UPLOAD_GROUP' => (is_numeric($oldFilesharingPermission)) ? 'checked="checked"' : '',
            'FILESHARING_ACCESS_DISPLAY' => (is_numeric($oldFilesharingPermission)) ? 'block' : 'none',
            'FILESHARING_ACCESS_ASSOCIATED_GROUPS' => implode("\n", $arrAssociatedGroupOptions),
            'FILESHARING_ACCESS_NOT_ASSOCIATED_GROUPS' => implode("\n", $arrNotAssociatedGroupOptions),
            'FILESHARING_SECURITY' => $_ARRAYLANG["TXT_FILESHARING_SECURITY"],
        ));
        $this->_objTpl->parse('filesharing_security');
    }

    private function saveSettings()
    {
        global $objDatabase;
        /**
         * save mailtemplates
         */
        if (isset($_POST["filesharingMail"])) {
            foreach ($_POST["filesharingMail"] as $lang => $inputs) {
                $objMailTemplate = $objDatabase->Execute("SELECT `subject`, `content` FROM " . DBPREFIX . "module_filesharing_mail_template WHERE `lang_id` = " . intval($lang));

                $content = str_replace(array('{', '}'), array('[[', ']]'), contrexx_input2raw($inputs["content"]));
                if ($objMailTemplate === false or $objMailTemplate->RecordCount() == 0) {
                    $objDatabase->Execute("INSERT INTO " . DBPREFIX . "module_filesharing_mail_template (`subject`, `content`, `lang_id`) VALUES ('" . contrexx_input2db($inputs["subject"]) . "', '" . contrexx_raw2db($content) . "', '" . intval($lang) . "')");
                } else {
                    $objDatabase->Execute("UPDATE " . DBPREFIX . "module_filesharing_mail_template SET `subject` = '" . contrexx_input2db($inputs["subject"]) . "', `content` = '" . contrexx_raw2db($content) . "' WHERE `lang_id` = '" . intval($lang) . "'");
                }
            }
        }

        /**
         * save permissions
         */
        \Cx\Core\Setting\Controller\Setting::init('FileSharing', 'config');
        $oldFilesharingSetting = \Cx\Core\Setting\Controller\Setting::getValue('permission','FileSharing');
        $newFilesharingSetting = $_POST['filesharingSettingsPermission'];
        if (!is_numeric($newFilesharingSetting)) {
            if (is_numeric($oldFilesharingSetting)) {
                // remove AccessId
                \Permission::removeAccess($oldFilesharingSetting, 'dynamic');
            }
        } else {
            $accessGroups = '';
            if (isset($_POST['filesharing_access_associated_groups'])) {
                $accessGroups = $_POST['filesharing_access_associated_groups'];
            }
            // get groups
            \Permission::removeAccess($oldFilesharingSetting, 'dynamic');
            if (isset($_POST['filesharing_access_associated_groups'])) {
                $accessGroups = $_POST['filesharing_access_associated_groups'];
            }

            // add AccessID
            $newFilesharingSetting = \Permission::createNewDynamicAccessId();

            // save AccessID
            if (count($accessGroups)) {
                \Permission::setAccess($newFilesharingSetting, 'dynamic', $accessGroups);
            }
        }

        // save new setting
        \Cx\Core\Setting\Controller\Setting::set('permission', $newFilesharingSetting);
        \Cx\Core\Setting\Controller\Setting::updateAll();
    }
}

?>
