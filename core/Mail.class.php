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
 * Mail
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * OBSOLETE -- See {@see core/MailTemplate.class.php}
 * Note that this partial class is left over for updating to the new
 * MailTemplate.  We use it to migrate the existing templates ONLY.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */
class OBOLETE_Mail
{
    /**
     * The language ID used when init() was called
     * @var integer
     */
    private static $lang_id = false;

    /**
     * The array of mail templates
     * @var array
     */
    private static $arrTemplate = false;


    /**
     * Reset the class
     *
     * Forces an {@link init()} the next time content is accessed
     */
    static function reset()
    {
        self::$lang_id = false;
    }


    /**
     * Initialize the mail template array
     *
     * Uses the given language ID, if any, or the language set in the
     * LANG_ID global constant.
     * Upon success, stores the language ID used in the $lang_id class
     * variable.
     * @param   integer     $lang_id        The optional language ID
     * @return  boolean                     True on success, false otherwise
     */
    static function init($lang_id=0)
    {
        global $objDatabase;

        // The array has been initialized with that language already
        if (self::$lang_id === $lang_id) return true;

        // Reset the language ID used
        self::$lang_id = false;
        // Use the current language if none is specified
        if (empty($lang_id)) $lang_id = FRONTEND_LANG_ID;
        self::$arrTemplate = array();

        $arrLanguages = FWLanguage::getLanguageArray();
        foreach ($arrLanguages as $arrLanguage) {
            if ($arrLanguage['frontend'] && $arrLanguage['is_default'] == 'true') {
                $defaultLangId = $arrLanguage['id'];
                break;
            }
        }
        $objResult = $objDatabase->Execute("
            SELECT `mail`.`id`, `mail`.`tplname`, `mail`.`protected`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_mail` AS `mail`");
        if (!$objResult) return false;
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            self::$arrTemplate[$id] = array(
                'id' => $id,
                'name' => $objResult->fields['tplname'],
                'protected' => $objResult->fields['protected'],
                // *MUST* be set!
                'available' => false,
            );
            $objResult->MoveNext();
        }
        $objResult = $objDatabase->Execute("
            SELECT `content`.`tpl_id`,
                   `content`.`from_mail`, `content`.`xsender`,
                   `content`.`subject`, `content`.`message`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_mail_content` AS `content`
            ORDER BY FIELD(`content`.`lang_id`, $defaultLangId, $lang_id) DESC");
        if (!$objResult) return false;
        while (!$objResult->EOF) {
            $id = $objResult->fields['tpl_id'];
            if (!self::$arrTemplate[$id]['available']) {
                self::$arrTemplate[$id]['available'] = true;
                self::$arrTemplate[$id]['from'] = $objResult->fields['from_mail'];
                self::$arrTemplate[$id]['sender'] = $objResult->fields['xsender'];
                self::$arrTemplate[$id]['subject'] = $objResult->fields['subject'];
                self::$arrTemplate[$id]['message'] = $objResult->fields['message'];
            }
            $objResult->MoveNext();
        }
        // Remember the language used
        self::$lang_id = $lang_id;
        return true;
    }


    static function getTemplateArray($lang_id=0)
    {
        if (empty($lang_id)) return false;
        self::init($lang_id);
        return self::$arrTemplate;
    }


    /**
     * Set up and send an email from the shop.
     * @static
     * @param   string    $mailTo           Recipient mail address
     * @param   string    $mailFrom         Sender mail address
     * @param   string    $mailSender       Sender name
     * @param   string    $mailSubject      Message subject
     * @param   string    $mailBody         Message body
     * @return  boolean                     True if the mail could be sent,
     *                                      false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function send(
        $mailTo, $mailFrom, $mailSender, $mailSubject, $mailBody
    ) {
        global $_CONFIG;

        if (@include_once ASCMS_LIBRARY_PATH.'/phpmailer/class.phpmailer.php') {
            $objMail = new phpmailer();
            if (   isset($_CONFIG['coreSmtpServer'])
                && $_CONFIG['coreSmtpServer'] > 0
                && @include_once ASCMS_CORE_PATH.'/SmtpSettings.class.php') {
                if (($arrSmtp = SmtpSettings::getSmtpAccount($_CONFIG['coreSmtpServer'])) !== false) {
                    $objMail->IsSMTP();
                    $objMail->Host = $arrSmtp['hostname'];
                    $objMail->Port = $arrSmtp['port'];
                    $objMail->SMTPAuth = true;
                    $objMail->Username = $arrSmtp['username'];
                    $objMail->Password = $arrSmtp['password'];
                }
            }
            $objMail->CharSet = CONTREXX_CHARSET;
            $from = preg_replace('/\015\012/', '', $mailFrom);
            $fromName = preg_replace('/\015\012/', '', $mailSender);
            $objMail->SetFrom($from, $fromName);
            $objMail->Subject = $mailSubject;
            $objMail->IsHTML(false);
            $objMail->Body = preg_replace('/\015\012/', "\012", $mailBody);
            $objMail->AddAddress($mailTo);
            if ($objMail->Send()) return true;
        }
        return false;
    }


    /**
     * Pick a mail template from the database
     *
     * Get the selected mail template and associated fields from the database.
     * @static
     * @param   integer $template_id    The mail template ID
     * @param   integer $lang_id        The language ID
     * @global  ADONewConnection
     * @return  mixed                   The mail template array on success,
     *                                  false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getTemplate($template_id, $lang_id=0)
    {
        if (empty($lang_id)) $lang_id = FRONTEND_LANG_ID;
        self::init($lang_id);
        return self::$arrTemplate[$template_id];
    }


    static function store()
    {
        if (empty(self::$arrTemplate)) self::init();
        $total_result = true;
        $result = self::deleteTemplate();
        if ($result !== '') $total_result &= $result;
        $result = self::storeTemplate();
        if ($result !== '') $total_result &= $result;
        // Force reinit after storing, or the user might not
        // see any changes at first
        self::reset();
        return $total_result;
    }


    /**
     * Delete template
     */
    static function deleteTemplate()
    {
        global $objDatabase;

        if (empty($_GET['delTplId'])) return '';
        $template_id = $_GET['delTplId'];
        // Cannot delete protected (system) templates
        if (self::$arrTemplate[$template_id]['protected']) return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_mail
             WHERE id=$template_id");
        if (!$objResult) return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_mail_content
             WHERE tpl_id=$template_id");
        if (!$objResult) return false;
        $objDatabase->Execute("OPTIMIZE TABLE ".DBPREFIX."module_shop".MODULE_INDEX."_mail");
        $objDatabase->Execute("OPTIMIZE TABLE ".DBPREFIX."module_shop".MODULE_INDEX."_mail_content");
        return true;
    }


    /**
     * Update or add new template
     */
    function storeTemplate()
    {
        global $objDatabase;

        if (empty($_POST['mails'])) return '';
        // Use the posted template ID only if the "store as new" checkbox
        // hasn't been marked
        $template_id =
            (empty($_POST['shopMailSaveNew']) && !empty($_POST['tplId'])
                ? $_POST['tplId'] : 0);
        if (empty($_POST['langId'])) return '';
        $lang_id = $_POST['langId'];
        self::init($lang_id);
        if ($template_id) {
            $arrTemplate = self::$arrTemplate[$template_id];
            if (!$arrTemplate) {
                // Template not found.  Clear the ID.
                $template_id = 0;
            }
        }
        // If the template ID is known, update.
        // Note that the protected flag is not changed.
        // For newly inserted templates, the protected flag is always 0 (zero).
        $query =
            (   $template_id
             && isset(self::$arrTemplate[$template_id])
            ? "UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_mail
                  SET `tplname`='".contrexx_addslashes($_POST['shopMailTemplate'])."'
                WHERE `id`=$template_id"
             : "INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_mail (
                    `protected`, `tplname`
                ) VALUES (
                    0,
                    '".contrexx_addslashes($_POST['shopMailTemplate'])."'
                )"
        );
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        if (empty($template_id))
            $template_id = $objDatabase->Insert_ID();
        $query =
            (   $template_id
             && self::$arrTemplate[$template_id]['available']
            ? "UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_mail_content
                  SET `from_mail`='".contrexx_addslashes($_POST['shopMailFromAddress'])."',
                      `xsender`='".contrexx_addslashes($_POST['shopMailFromName'])."',
                      `subject`='".contrexx_addslashes($_POST['shopMailSubject'])."',
                      `message`='".contrexx_addslashes($_POST['shopMailBody'])."'
                WHERE `tpl_id`=$template_id
                  AND `lang_id`=$lang_id" //FRONTEND_LANG_ID"
            : "INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_mail_content (
                    `tpl_id`, `lang_id`,
                    `from_mail`, `xsender`,
                    `subject`, `message`
                ) VALUES (
                    $template_id, $lang_id,
                    '".contrexx_addslashes($_POST['shopMailFromAddress'])."',
                    '".contrexx_addslashes($_POST['shopMailFromName'])."',
                    '".contrexx_addslashes($_POST['shopMailSubject'])."',
                    '".contrexx_addslashes($_POST['shopMailBody'])."'
                )"
         );
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        return true;
    }

}
