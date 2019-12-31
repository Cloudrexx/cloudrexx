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
 * OBSOLETE -- See {@see core/MailTemplate.class.php}
 *
 * Note that this partial class is left over for updating to the new
 * MailTemplate.  We use it to migrate the existing templates ONLY.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * OBSOLETE -- See {@see core/MailTemplate.class.php}
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class Mail
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
        if (self::$lang_id == $lang_id) return true;
        // Reset the language ID used
        self::$lang_id = null;
        // Use the current language if none is specified
        if (empty($lang_id)) $lang_id = FRONTEND_LANG_ID;
        self::$arrTemplate = array();
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
             WHERE `content`.`lang_id`=$lang_id");
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
die("Mail::send(): Obsolete method called!");
        global $_CONFIG;

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $from = preg_replace('/\015\012/', '', $mailFrom);
        $fromName = preg_replace('/\015\012/', '', $mailSender);
        $objMail->SetFrom($from, $fromName);
        $objMail->Subject = $mailSubject;
        $objMail->IsHTML(false);
        $objMail->Body = preg_replace('/\015\012/', "\012", $mailBody);
        $objMail->AddAddress($mailTo);
        if ($objMail->Send()) return true;
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
die("Mail::getTemplate(): Obsolete method called!");
        if (empty($lang_id)) $lang_id = FRONTEND_LANG_ID;
        self::init($lang_id);
        return self::$arrTemplate[$template_id];
    }


    /**
     * Validate the email address
     *
     * Does an extensive syntax check to determine whether the string argument
     * is a real email address.
     * Note that this doesn't mean that the address is necessarily valid,
     * but only that it isn't just an arbitrary character sequence.
     * @todo    Some valid addresses are rejected by this method,
     * such as *%+@mymail.com.
     * Valid (atom) characters are: "!#$%&'*+-/=?^_`{|}~" (without the double quotes),
     * see {@link http://rfc.net/rfc2822.html RFC 2822} for details.
     * @todo    The rules applied to host names are not correct either, see
     * {@link http://rfc.net/rfc1738.html RFC 1738} and {@link http://rfc.net/rfc3986.html}.
     * Excerpt from RFC 1738:
     * - hostport       = host [ ":" port ]
     * - host           = hostname | hostnumber
     * - hostname       = *[ domainlabel "." ] toplabel
     * - domainlabel    = alphadigit | alphadigit *[ alphadigit | "-" ] alphadigit
     * - toplabel       = alpha | alpha *[ alphadigit | "-" ] alphadigit
     * - alphadigit     = alpha | digit
     * Excerpt from RFC 3986:
     * "Non-ASCII characters must first be encoded according to UTF-8 [STD63],
     * and then each octet of the corresponding UTF-8 sequence must be percent-
     * encoded to be represented as URI characters".
     * @todo    This doesn't really belong here.  Should be placed into a
     *          proper core e-mail class as a static method.
     * @param   string  $string
     * @return  boolean
     */
    function isValidAddress($string)
    {
die("Mail::isValidAddress(): Obsolete method called!");
        if (preg_match(
            '/^[a-z0-9]+([-_\.a-z0-9]+)*'.  // user
            '@([a-z0-9]+([-\.a-z0-9]+)*)+'. // domain
            '\.[a-z]{2,4}$/',               // sld, tld
            $string
        )) return true;
        return false;
    }


    static function store()
    {
die("Mail::store(): Obsolete method called!");
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
die("Mail::deleteTemplate(): Obsolete method called!");
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

        return true;
    }


    /**
     * Update or add new template
     */
    function storeTemplate()
    {
die("Mail::storeTemplate(): Obsolete method called!");
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
                  SET `tplname`='".contrexx_input2db($_POST['shopMailTemplate'])."'
                WHERE `id`=$template_id"
             : "INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_mail (
                    `protected`, `tplname`
                ) VALUES (
                    0,
                    '".contrexx_input2db($_POST['shopMailTemplate'])."'
                )"
        );
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        if (empty($template_id)) $template_id = $objDatabase->Insert_ID();
        $query =
            (   $template_id
             && self::$arrTemplate[$template_id]['available']
            ? "UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_mail_content
                  SET `from_mail`='".contrexx_input2db($_POST['shopMailFromAddress'])."',
                      `xsender`='".contrexx_input2db($_POST['shopMailFromName'])."',
                      `subject`='".contrexx_input2db($_POST['shopMailSubject'])."',
                      `message`='".contrexx_input2db($_POST['shopMailBody'])."'
                WHERE `tpl_id`=$template_id
                  AND `lang_id`=$lang_id" //FRONTEND_LANG_ID"
            : "INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_mail_content (
                    `tpl_id`, `lang_id`,
                    `from_mail`, `xsender`,
                    `subject`, `message`
                ) VALUES (
                    $template_id, $lang_id,
                    '".contrexx_input2db($_POST['shopMailFromAddress'])."',
                    '".contrexx_input2db($_POST['shopMailFromName'])."',
                    '".contrexx_input2db($_POST['shopMailSubject'])."',
                    '".contrexx_input2db($_POST['shopMailBody'])."'
                )"
         );
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        return true;
    }


    /**
     * Migrates existing old Shop mailtemplates to the new MailTemplate class
     * @return  boolean         False.  Always.
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
        if (!include_once \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseFrameworkPath() .'/UpdateUtil') return false;
        if (\Cx\Lib\UpdateUtil::table_empty(DBPREFIX.'core_mail_template')) {
            // Make sure there are no bodies lying around
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_NAME);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_FROM);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_SENDER);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_REPLY);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_TO);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_CC);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_BCC);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_SUBJECT);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_MESSAGE);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_MESSAGE_HTML);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_ATTACHMENTS);
            \Text::deleteByKey('Shop', \Cx\Core\MailTemplate\Controller\MailTemplate::TEXT_INLINE);
        }
        // Migrate existing templates from the shop to the MailTemplate.
        // These are the keys replacing the IDs.
// TODO: Migrate the old template using the original IDs, make them unprotected
// TODO: Add the new default templates with the new keys
// and have the user migrate changes herself!
        $arrKey =  array(
            // DE: Bestellungsbestätigung (includes account data, obsoletes #4)
            // EN:
            // FR: Confirmation de commande Cloudrexx
            // IT:
            1 => 'order_confirmation',
            // DE: Auftrag abgeschlossen
            // EN:
            // FR: Commande terminée
            // IT:
            2 => 'order_complete',
            // DE: Logindaten
            // EN:
            // FR: Identifiants de connexion
            // IT:
            3 => 'customer_login',
            // DE: Bestellungsbestätigung mit Zugangsdaten (obsolete!)
            // EN:
            // FR: Confirmation de commande et identifiants de connexion
            // IT:
            4 => 'order_confirmation_login',
        );
        $arrLanguageId = \FWLanguage::getIdArray();
        if (empty($arrLanguageId)) {
            throw new \Cx\Lib\Update_DatabaseException(
               "Failed to get frontend language IDs");
        }
        foreach ($arrLanguageId as $lang_id) {
            // Mind that the template name is single language yet!
            $arrTemplates = self::getTemplateArray($lang_id);
            if (empty($arrTemplates)) continue;
            foreach ($arrTemplates as $id => $arrTemplate) {
// TODO: utf8_encode() may not be necessary in all cases.
// It worked without it for me earlier, but was necessary for verkehrstheorie.ch
                $arrTemplate = array_map("utf8_encode", $arrTemplate);
                if (isset($arrKey[$id])) {
                    // System templates get their default key
                    $arrTemplate['key'] = $arrKey[$id];
                    if ($id == 4) {
                        // Clear the protected flag, so the obsolete template
                        // #4 may be removed at will
                        $arrTemplate['protected'] = false;
                    }
                } else {
                    // Custom templates:
                    // Make the name lowercase and replace any non-letter
                    $new_key = preg_replace(
                        '/[^a-z]/', '_', strtolower($arrTemplate['name']));
                    // Keep it unique!  Use the ID if the key is taken
                    if (in_array($new_key, $arrKey)) {
                        $new_key = $id;
                    }
                    // Remember used keys, and replace the former ID
                    $arrKey[$id] = $new_key;
                    $arrTemplate['key'] = $new_key;
                }
                foreach ($arrTemplate as &$string) {
                    // Replace old <PLACEHOLDERS> with new [PLACEHOLDERS].
                    $string = preg_replace('/\\<([A-Z_]+)\\>/', '[$1]', $string);
// TODO: This is completely unreliable.
// Use the process as described above, not replacing the old templates,
// but adding the new ones instead.
//                    $string = str_replace('[ORDER_DATA]', $order_data, $string);
//                    $string = preg_replace('/[\\w\\s\\:]+\\[USERNAME\\](?:\\n|<br\\s?\\/?
//                          >)*[\\w\\s\\:]+\\[PASSWORD\\]/',
//                        $login_data, $string);
                }
//                $arrTemplate['message_html'] = preg_replace(
//                    '/(?:\r|\n|\r\n)/', "<br />\n", $arrTemplate['message']);
                $arrTemplate['lang_id'] = $lang_id;
                if (!\Cx\Core\MailTemplate\Controller\MailTemplate::store('Shop', $arrTemplate)) {
                    throw new \Cx\Lib\Update_DatabaseException(
                       "Failed to store Mailtemplate");
                }
            }
        }
        // Drop old Mail tables after successful migration
        \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_shop_mail_content');
        \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_shop_mail');

        // Always!
        return false;
    }

}
