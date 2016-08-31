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
 * u2uAdmin
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_u2u
 */

namespace Cx\Modules\U2u\Controller;

/**
 * u2uAdmin
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Raveendran.L
 * @package     cloudrexx
 * @subpackage  module_u2u
 */
class U2uAdmin extends U2uLibrary {


    var $_objTpl,$strMessages;
    var $_strPageTitle  = '';
    var $_strErrMessage = '';
    var $_strOkMessage  = '';

    private $act = '';

    /**
    * Constructor   -> Create the module-menu and an internal template-object
    * @global   object      $objInit
    * @global   object      $objTemplate
    * @global   array       $_CORELANG
    */
    function __construct() {

        global $objInit, $objTemplate, $_ARRAYLANG, $_CORELANG;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/U2u/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->_intLanguageId = $objInit->userFrontendLangId;

        $objFWUser = \FWUser::getFWUserObject();
        $this->_intCurrentUserId = $objFWUser->objUser->getId();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION',' <a href="?cmd=U2u&amp;act=settings" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_ARRAYLANG['TXT_U2U_SETTINGS'].'</a>');
    }

    /**
     * Perform the right operation depending on the $_GET-params
     *
     * @global   object      $objTemplate
     */
    function getPage() {
        global $objTemplate;

        if(!isset($_GET['act'])) {
            $_GET['act']='';
        }
        switch($_GET['act'])  {
            case 'saveSettings':
                \Permission::checkAccess(121, 'static');
                $this->saveSettings();
                 $this->settings();
                break;

            default:
                \Permission::checkAccess(121, 'static');
                $this->settings();
                break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->_strPageTitle,
            'CONTENT_OK_MESSAGE'        => $this->_strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->_strErrMessage,
            'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    /**
     * It will show the page with the Maximum posting of the users and..
     * The maximum postings chars of the users..
     * @global   $_ARRAYLANG $objTemplate $_CORELANG
     */
    function settings() {

        global $_ARRAYLANG,$objTemplate,$_CORELANG;

        $this->_objTpl->loadTemplatefile('module_u2u_settings.html',true,true);
        $this->_pageTitle = $_ARRAYLANG['TXT_U2U_SETTINGS'];

        $settingMaxPosting   = $this->_getMaxPostingDetails();
        $settingMaxChars     = $this->_getMaxCharDetails();
        $settingEmailSubject = $this->_getEmailSubjectDetails();
        $settingEmailFrom    = $this->_getEmailFromDetails();
        $settingEmailMessage = $this->_getEmailMessageDetails();
        $this->strMessages   = contrexx_stripslashes($settingEmailMessage['email_message']);
        $strEmailInputHTML   = new \Cx\Core\Wysiwyg\Wysiwyg('private_message',$this->strMessages, 'fullpage');

        $this->_objTpl->setVariable(array(
            'TXT_U2U_MAX_POSTING_SIZE'                 => $settingMaxPosting['max_posting_size'],
            'TXT_U2U_MAX_POSTING_CHARS'              => $settingMaxChars['max_posting_chars'],
            'TXT_U2U_SETTINGS_MAIL_SUBJECT'          => $settingEmailSubject['subject'],
            'TXT_U2U_SETTINGS_MAIL_FROM'             => $settingEmailFrom['from'],
            'TXT_U2U_ENTER_THE_SUBJECT_OF_THE_MAIL'  => $_ARRAYLANG['TXT_U2U_ENTER_THE_SUBJECT_OF_THE_MAIL'],
            'TXT_U2U_ENTER_THE_FROM_ADDRESS'         => $_ARRAYLANG['TXT_U2U_ENTER_THE_FROM_ADDRESS'],
            'TXT_U2U_NOTIFICATIION_EMAILS'           => $_ARRAYLANG['TXT_U2U_NOTIFICATIION_EMAILS'],
            'TXT_U2U_SUBJECT'                        => $_ARRAYLANG['TXT_U2U_SUBJECT'],
            'TXT_U2U_FROM_LINE'                      => $_ARRAYLANG['TXT_U2U_FROM_LINE'],
            'TXT_U2U_MESSAGE'                        => $_ARRAYLANG['TXT_U2U_MESSAGE'],
            'TXT_U2U_NOTE'                           => $_ARRAYLANG['TXT_U2U_NOTE'],
            'TXT_U2U_SENDER_NAME_CONTENT'            => $_ARRAYLANG['TXT_U2U_SENDER_NAME_CONTENT'],
            'TXT_U2U_DOMAIN_NAME_CONTENT'            => $_ARRAYLANG['TXT_U2U_DOMAIN_NAME_CONTENT'],
            'TXT_U2U_RECEIVER_NAME_CONTENT'          => $_ARRAYLANG['TXT_U2U_RECEIVER_NAME_CONTENT'],
            'TXT_U2U_EMAIL_CONTENT'                  => $strEmailInputHTML,
            'TXT_U2U_MAX_POSTING_SIZE_INBOX'         => $_ARRAYLANG['TXT_U2U_MAX_POSTING_SIZE_INBOX'],
            'TXT_U2U_MAX_CHARS_PER_MSG'              => $_ARRAYLANG['TXT_U2U_MAX_CHARS_PER_MSG'],
            'TXT_U2U_VALUE'                          => $_ARRAYLANG['TXT_U2U_VALUE'],
            'TXT_SAVE'                               => $_ARRAYLANG['TXT_SAVE']
        ));
    }

    /**
     * When the admin changed the settings,this function will be called..
     * The maximum postings chars of the users..
     * @global   $_CORELANG $_ARRAYLANG $objDatabase;
     */
    function saveSettings() {

       global $_CORELANG, $_ARRAYLANG, $objDatabase;
       if($_POST['frmSettings_submit']) {
            $settings =  array();
            $settings =  array('max_inbox'      => contrexx_addslashes(strip_tags($_POST['frmSettings_max_inbox'])),
                                   'max_chars'         => contrexx_addslashes(strip_tags($_POST['frmSettings_max_chars'])),
                                   'mail_subject'     => contrexx_addslashes(strip_tags($_POST['frmSettings_subject'])),
                                   'mail_from'         => contrexx_addslashes(strip_tags($_POST['frmSettings_from'])),
                                   'mail_message'     => contrexx_addslashes($_POST['private_message']),
                               );
            $updateMaxPostings=' UPDATE '.DBPREFIX.'module_u2u_settings
                                SET `value`      = "'.$settings['max_inbox'].'"
                                WHERE `name` = "max_posting_size"';
            $objDatabase->Execute($updateMaxPostings);

            $updateMaxPostingChars=' UPDATE '.DBPREFIX.'module_u2u_settings
                                    SET `value`      = "'.$settings['max_chars'].'"
                                         WHERE `name` = "max_posting_chars"';
            $objDatabase->Execute($updateMaxPostingChars);

            $updateMailSubject=' UPDATE '.DBPREFIX.'module_u2u_settings
                                SET `value`      = "'.$settings['mail_subject'].'"
                                WHERE `name` = "subject"';
            $objDatabase->Execute($updateMailSubject);

            $updateMailFrom=' UPDATE '.DBPREFIX.'module_u2u_settings
                                SET `value`      = "'.$settings['mail_from'].'"
                                WHERE `name` = "from"';
            $objDatabase->Execute($updateMailFrom);

            $updateMailMessage=' UPDATE '.DBPREFIX.'module_u2u_settings
                                SET `value`      = "'.$settings['mail_message'].'"
                                WHERE `name` = "email_message"';
            $objDatabase->Execute($updateMailMessage);
            $this->_strOkMessage = "Success";
        } else {
            $this->_strErrMessage = "Not success";
        }

    }
}
?>
