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
 * Core Mail and Template Management
 *
 * @version     3.0.0
 * @since       2.2.0
 * @package     cloudrexx
 * @subpackage  core_mailtemplate
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @todo        Test!
 */

namespace Cx\Core\MailTemplate\Controller;

/**
 * Core Mail and Template Class
 *
 * Manages e-mail templates in any language, accessible by module
 * and key for easy access.
 * Includes a nice wrapper for the phpmailer class that allows
 * sending all kinds of mail in plain text or HTML, also with
 * attachments.
 * @version     3.0.0
 * @since       2.2.0
 * @package     cloudrexx
 * @subpackage  core_mailtemplate
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @todo        Test!
 */
class MailTemplate
{
    /**
     * Class constant for the mail template name Text key
     */
    const TEXT_NAME = 'core_mail_template_name';
    /**
     * Class constant for the mail template from Text key
     */
    const TEXT_FROM = 'core_mail_template_from';
    /**
     * Class constant for the mail template sender Text key
     */
    const TEXT_SENDER = 'core_mail_template_sender';
    /**
     * Class constant for the mail template reply Text key
     */
    const TEXT_REPLY = 'core_mail_template_reply';
    /**
     * Class constant for the mail template to Text key
     */
    const TEXT_TO = 'core_mail_template_to';
    /**
     * Class constant for the mail template cc Text key
     */
    const TEXT_CC = 'core_mail_template_cc';
    /**
     * Class constant for the mail template bcc Text key
     */
    const TEXT_BCC = 'core_mail_template_bcc';
    /**
     * Class constant for the mail template subject Text key
     */
    const TEXT_SUBJECT = 'core_mail_template_subject';
    /**
     * Class constant for the mail template message plain Text key
     */
    const TEXT_MESSAGE = 'core_mail_template_message';
    /**
     * Class constant for the mail template message HTML Text key
     */
    const TEXT_MESSAGE_HTML = 'core_mail_template_message_html';
    /**
     * Class constant for the mail template attachments Text key
     */
    const TEXT_ATTACHMENTS = 'core_mail_template_attachments';
    /**
     * Class constant for the mail template inline Text key
     */
    const TEXT_INLINE = 'core_mail_template_inline';
    /**
     * Class constant for Attach dynamic PDF-document Text key
     */
    const TEXT_ATTACH_PDF = 'core_mail_template_attach_pdf';
    /**
     * Class constant for PDF template id Text key
     */
    const TEXT_PDF_TEMPLATE = 'core_mail_template_pdf_template';

    /**
     * The module ID used when init() was called
     * @var integer
     */
    private static $section = null;

    /**
     * The array of mail templates loaded in {@see init()}
     * @var array
     */
    private static $arrTemplates = null;

    /**
     * An empty template
     * @var   array
     */
    private static $empty = array(
        'key'          => null,
        'name'         => null,
        'from'         => null,
        'sender'       => null,
        'reply'        => null,
        'to'           => null,
        'cc'           => null,
        'bcc'          => null,
        'subject'      => null,
        'message'      => null,
        'message_html' => null,
        'attachments'  => null,
        'inline'       => null,
        'protected'    => false,
        'html'         => false,
        'available'    => false,
        'attach_pdf'   => false,
        'pdf_template' => null,
    );

    private static $text = array(
        'name' => self::TEXT_NAME,
        'from' => self::TEXT_FROM,
        'sender' => self::TEXT_SENDER,
        'reply' => self::TEXT_REPLY,
        'to' => self::TEXT_TO,
        'cc' => self::TEXT_CC,
        'bcc' => self::TEXT_BCC,
        'subject' => self::TEXT_SUBJECT,
        'message' => self::TEXT_MESSAGE,
        'message_html' => self::TEXT_MESSAGE_HTML,
        'attachments' => self::TEXT_ATTACHMENTS,
        'inline' => self::TEXT_INLINE,
        'attach_pdf'   => self::TEXT_ATTACH_PDF,
        'pdf_template' => self::TEXT_PDF_TEMPLATE
    );

    /**
     * Returns a new, empty MailTemplate
     *
     * Note that this is *NOT* a constructor, but a static method that
     * returns an empty template array with all fields empty, with the
     * exception of the optional $key.
     * @param   string    $key      The optional key
     * @return  array               The MailTemplate array
     */
    static function getEmpty($key='')
    {
        self::$empty['key'] = $key;
        return self::$empty;
    }


    /**
     * Reset the class
     *
     * Forces a call to {@link init()} the next time content is accessed
     */
    static function reset()
    {
        self::$arrTemplates = null;
    }


    /**
     * Initialize the mail template array for the current module
     *
     * Uses the given language ID $lang_id if not empty, or all active
     * frontend languages otherwise.
     * The $limit value defaults to the value of the
     * mailtemplate_per_page_backend setting from the core settings
     * (@see \Cx\Core\Setting\Controller\Setting}.
     * @param   integer     $section        The section
     * @param   integer     $lang_id        The optional language ID
     * @param   string      $order          The optional sorting order string,
     *                                      SQL syntax
     * @param   integer     $position       The optional position offset,
     *                                      defaults to zero
     * @param   integer     $limit          The optional limit for returned
     *                                      templates
     * @param   integer     $count          The actual count of templates
     *                                      available in total, by reference
     * @return  boolean                     True on success, false otherwise
     */
    static function init(
        $section, $lang_id=null, $order='', $position=0, $limit=-1, &$count=0
    ) {
        global $objDatabase;

        if (empty($section)) {
die("MailTemplate::init(): Empty section!");
        }
        $arrLanguageId = null;
        if ($lang_id) {
            // Init one language
            $arrLanguageId = array($lang_id);
        } else {
            // Load all languages if none is specified
            $arrLanguageId = \FWLanguage::getIdArray();
        }
        self::reset();
        if (empty($limit)) $limit = \Cx\Core\Setting\Controller\Setting::getValue(
            'mailtemplate_per_page_backend');
        if (empty($limit)) $limit = 25;
        $query_from = null;
        self::$arrTemplates = array();
        foreach ($arrLanguageId as $lang_id) {
            $arrSql = \Text::getSqlSnippets(
                '`mail`.`text_id`', $lang_id, $section,
                array(
                    'name' => self::TEXT_NAME,
                    'from' => self::TEXT_FROM,
                    'sender' => self::TEXT_SENDER,
                    'reply' => self::TEXT_REPLY,
                    'to' => self::TEXT_TO,
                    'cc' => self::TEXT_CC,
                    'bcc' => self::TEXT_BCC,
                    'subject' => self::TEXT_SUBJECT,
                    'message' => self::TEXT_MESSAGE,
                    'message_html' => self::TEXT_MESSAGE_HTML,
                    'attachments' => self::TEXT_ATTACHMENTS,
                    'inline' => self::TEXT_INLINE,
                    'attach_pdf'   => self::TEXT_ATTACH_PDF,
                    'pdf_template' => self::TEXT_PDF_TEMPLATE,
                ));
            $query_from = "
                  FROM `".DBPREFIX."core_mail_template` AS `mail`".
                       $arrSql['join']."
                 WHERE `mail`.`section`".
                (isset($section)
                  ? "='".addslashes($section)."'" : ' IS NULL');
            $query_order = ($order ? " ORDER BY $order" : '');
            // The count of available templates needs to be initialized to zero
            // in case there is a problem with one of the queries ahead.
            // Ignore the code analyzer warning.
            $count = 0;
            $objResult = $objDatabase->SelectLimit("
                SELECT `mail`.`key`, `mail`.`text_id`, `mail`.`protected`, `mail`.`html`, ".
                    $arrSql['field'].
                    $query_from.
                    $query_order,
                $limit, $position);
            if (!$objResult) return self::errorHandler();
            while (!$objResult->EOF) {
                $available = true;
                $key = $objResult->fields['key'];
                $text_id = $objResult->fields['text_id'];
                $strName = $objResult->fields['name'];
                if ($strName === null) {
                    $strName = \Text::getById(
                        $text_id, $section, self::TEXT_NAME)->content();
                    if ($strName) {
                        $available = false;
                    }
                }
                $strFrom = $objResult->fields['from'];
                if ($strFrom === null) {
                    $strFrom = \Text::getById(
                        $text_id, $section, self::TEXT_FROM)->content();
                    if ($strFrom) $available = false;
                }
                $strSender = $objResult->fields['sender'];
                if ($strSender === null) {
                    $strSender = \Text::getById(
                        $text_id, $section, self::TEXT_SENDER)->content();
                    if ($strSender) $available = false;
                }
                $strReply = $objResult->fields['reply'];
                if ($strReply === null) {
                    $strReply = \Text::getById(
                        $text_id, $section, self::TEXT_REPLY)->content();
                    if ($strReply) $available = false;
                }
                $strTo = $objResult->fields['to'];
                if ($strTo === null) {
                    $strTo = \Text::getById(
                        $text_id, $section, self::TEXT_TO)->content();
                    if ($strTo) $available = false;
                }
                $strCc = $objResult->fields['cc'];
                if ($strCc === null) {
                    $strCc = \Text::getById(
                        $text_id, $section, self::TEXT_CC)->content();
                    if ($strCc) $available = false;
                }
                $strBcc = $objResult->fields['bcc'];
                if ($strBcc === null) {
                    $strBcc = \Text::getById(
                        $text_id, $section, self::TEXT_BCC)->content();
                    if ($strBcc) $available = false;
                }
                $strSubject = $objResult->fields['subject'];
                if ($strSubject === null) {
                    $strSubject = \Text::getById(
                        $text_id, $section, self::TEXT_SUBJECT)->content();
                    if ($strSubject) $available = false;
                }
                $strMessage = $objResult->fields['message'];
                if ($strMessage === null) {
                    $strMessage = \Text::getById(
                        $text_id, $section, self::TEXT_MESSAGE)->content();
                    if ($strMessage) $available = false;
                }
                $strMessageHtml = $objResult->fields['message_html'];
                if ($strMessageHtml === null) {
                    $strMessageHtml = \Text::getById(
                        $text_id, $section, self::TEXT_MESSAGE_HTML)->content();
                    if ($strMessageHtml) $available = false;
                }
                $strAttachments = $objResult->fields['attachments'];
                if ($strAttachments === null) {
                    $strAttachments = \Text::getById(
                        $text_id, $section, self::TEXT_ATTACHMENTS)->content();
                    if ($strAttachments) $available = false;
                }
                $strInline = $objResult->fields['inline'];
                if ($strInline === null) {
                    $strInline = \Text::getById(
                        $text_id, $section, self::TEXT_INLINE)->content();
                    if ($strInline) $available = false;
                }
                $strAttachPdf = $objResult->fields['attach_pdf'];
                if ($strAttachPdf === null) {
                    $strAttachPdf = \Text::getById(
                        $text_id,
                        $section,
                        self::TEXT_ATTACH_PDF
                    )->content();
                    if ($strAttachPdf) {
                        $available = false;
                    }
                }
                $strPdfTplId = $objResult->fields['pdf_template'];
                if ($strPdfTplId === null) {
                    $strPdfTplId = \Text::getById(
                        $text_id,
                        $section,
                        self::TEXT_PDF_TEMPLATE
                    )->content();
                    if ($strPdfTplId) {
                        $available = false;
                    }
                }
// TODO: Hard to decide which should be mandatory, as any of them may
// be filled in "just in time". -- Time will tell.
//                if (   $strName == ''
//                    || $strFrom == ''
//                    || $strSender == ''
//                    || $strReply == ''
//                    || $strTo == ''
//                    || $strCc == ''
//                    || $strBcc == ''
//                    || $strSubject == ''
//                    || $strMessage == ''
//                    || $strMessageHtml == ''
//                    || $strAttachments == ''
//                    || $strInline == ''
//                ) {
//                    $available = false;
//                }
                self::$arrTemplates[$lang_id][$key] = array(
                    'key'          => $key,
                    'text_id'      => $text_id,
                    'name'         => $strName,
                    'protected'    => $objResult->fields['protected'],
                    'html'         => $objResult->fields['html'],
                    'from'         => $strFrom,
                    'sender'       => $strSender,
                    'reply'        => $strReply,
                    'to'           => $strTo,
                    'cc'           => $strCc,
                    'bcc'          => $strBcc,
                    'subject'      => $strSubject,
                    'message'      => $strMessage,
                    'message_html' => $strMessageHtml,
                    'attachments'  => eval("$strAttachments;"),
                    'inline'       => eval("$strInline;"),
                    'available'    => $available,
                    'attach_pdf'   => $strAttachPdf,
                    'pdf_template' => $strPdfTplId,
                );
                $objResult->MoveNext();
            }
        }
        $objResult = $objDatabase->Execute("
            SELECT COUNT(*) AS `count` $query_from");
        if (!$objResult) return self::errorHandler();
        $count += $objResult->fields['count'];
        // Remember the module used
        self::$section = $section;
        return true;
    }


    /**
     * Returns the complete array of templates available for the selected
     * language and module.
     *
     * If the optional $lang_id argument is empty, the current language ID,
     * or the global FRONTEND_LANG_ID constant is used instead, in this order.
     * @param   integer     $lang_id        The optional language ID
     * @param   integer     $section        The section
     * @param   string      $order          The optional sorting order string,
     *                                      SQL syntax
     * @param   integer     $position       The optional position offset,
     *                                      defaults to zero
     * @param   integer     $limit          The optional limit for returned
     *                                      templates.
     *                                      Defaults to -1 (no limit)
     * @param   integer     $count          The actual count of templates
     *                                      available in total, by reference
     * @return  mixed                       The template array on success,
     *                                      null otherwise
     */
    static function getArray(
        $section, $lang_id=null, $order=null, $position=0, $limit=-1, &$count=0
    ) {
        self::init($section, $lang_id, $order, $position, $limit, $count);
        if (!$lang_id) $lang_id = FRONTEND_LANG_ID;
        if (isset(self::$arrTemplates[$lang_id]))
            return self::$arrTemplates[$lang_id];
        return null;
    }


    /**
     * Returns the selected mail template and associated fields
     * from the database.
     *
     * The $key parameter uniquely identifies the template for each
     * module.
     * The optional $lang_id may be provided to override the language ID
     * present in the global FRONTEND_LANG_ID constant.
     * @param   string  $key            The key identifying the template
     * @param   integer $lang_id        The optional language ID
     * @param   integer $section        The optional module ID
     * @return  mixed                   The mail template array on success,
     *                                  null otherwise
     * @global  ADONewConnection
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function get($section, $key, $lang_id=null)
    {
        if (!$lang_id) $lang_id = FRONTEND_LANG_ID;
        if ($section != self::$section
         || empty(self::$arrTemplates[$lang_id][$key])) {
            self::init($section, $lang_id);
        }
        if (empty(self::$arrTemplates[$lang_id][$key])) {
            return null;
        }
        return self::$arrTemplates[$lang_id][$key];
    }


    /**
     * Set up and send an email
     *
     * The array argument is searched for the following indices:
     *  key           The key of any mail template to be used
     *  section       The module to initialize for (mandatory when key is set)
     *  sender        The sender name
     *  from          The sender e-mail address
     *  to            The recipient e-mail address(es), comma separated
     *  reply         The reply-to e-mail address
     *  cc            The carbon copy e-mail address(es), comma separated
     *  bcc           The blind carbon copy e-mail address(es), comma separated
     *  subject       The message subject
     *  message       The plain text message body
     *  message_html  The HTML message body
     *  html          If this evaluates to true, turns on HTML mode
     *  attachments   An array of file paths to attach.  The array keys may
     *                be used for the paths, and the values for the name.
     *                If the keys are numeric, the values are regarded as paths.
     *  inline        An array of inline (image) file paths to attach.
     *                If this is used, HTML mode is switched on automatically.
     *  search        The array of patterns to be replaced by...
     *  replace       The array of replacements for the patterns
     *  substitution  A more complex structure for replacing placeholders
     *                and/or complete blocks, conditionally or repeatedly.
     * If the key index is present, the corresponding mail template is loaded
     * first.  Other indices present (sender, from, to, subject, message, etc.)
     * will override the template fields.
     * Missing mandatory fields are filled with the
     * default values from the global $_CONFIG array (sender, from, to),
     * or some core language variables (subject, message).
     * A simple {@see str_replace()} is used for the search and replace
     * operation, and the placeholder names are quoted in the substitution,
     * so you cannot use regular expressions.
     * More complex substitutions including repeated blocks may be specified
     * in the substitution subarray of the $arrField parameter value.
     * The e-mail addresses in the To: field will be used as follows:
     * - Groups of addresses are separated by semicola (;)
     * - Single addresses are separated by comma (,)
     * All recipients of any single group are added to the To: field together,
     * Groups are processed separately.  So, if your To: looks like
     *    a@a.com,b@b.com;c@c.com,d@d.com
     * a total of two e-mails will be sent; one to a and b, and a second one
     * to c and d.
     * Addresses for copies (Cc:) and blind copies (Bcc:) are added to all
     * e-mails sent, so if your e-mail is in the Cc: or Bcc: field in the
     * example above, you will receive two copies.
     * Note:  The attachment paths must comply with the requirements for
     * file paths as defined in the {@see File} class version 2.2.0.
     * @static
     * @param   array     $arrField         The array of template fields
     * @return  boolean                     True if the mail could be sent,
     *                                      false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function send($arrField)
    {
        global $_CONFIG; //, $_CORELANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        if (empty($arrField['lang_id'])) {
            $arrField['lang_id'] = FRONTEND_LANG_ID;
        }
        $section = (isset($arrField['section']) ? $arrField['section'] : null);
        $arrTemplate = null;
        if (empty($arrField['key'])) {
            $arrTemplate = self::getEmpty();
        } else {
            $arrTemplate = self::get($section, $arrField['key'], $arrField['lang_id']);
            if (empty($arrTemplate)) {
\DBG::log("MailTemplate::send(): WARNING: No Template for key {$arrField['key']} (section $section)");
                return false;
            }
        }
        $search  =
            (isset($arrField['search']) && is_array($arrField['search'])
              ? $arrField['search'] : null);
        $replace =
            (isset($arrField['replace']) && is_array($arrField['replace'])
              ? $arrField['replace'] : null);
        $substitution =
            (   isset($arrField['substitution'])
             && is_array($arrField['substitution'])
              ? $arrField['substitution'] : null);
//echo("Substitution:<br />".nl2br(var_export($arrField['substitution'], true))."<hr />");
        $strip = empty($arrField['do_not_strip_empty_placeholders']);
        // Replace node placeholders generated by Wysiwyg
        $arrTemplate['message_html'] = preg_replace('/\[\[NODE_([a-zA-Z_0-9]*)\]\]/', '{NODE_$1}', $arrTemplate['message_html']);
        \LinkGenerator::parseTemplate($arrTemplate['message_html'], true);
        foreach ($arrTemplate as $field => &$value) {
            if (   $field == 'inline'
                || $field == 'attachments') continue;
            if (isset($arrField[$field])) $value = $arrField[$field];
            if (empty($value) || is_numeric($value)) continue;
// TODO: Fix the regex to produce proper "CR/LF" in any case.
// Must handle any of CR, LF, CR/LF, and LF/CR!
//                preg_replace('/[\015\012]/', "\015\012", $value);
            if ($search) {
                // we need to replace raw data with HTML entities
                // for HTML-body of email
                if ($field == 'message_html') {
                    foreach ($search as $index => $searchTerm) {
                        $value = str_replace($searchTerm, contrexx_raw2xhtml($replace[$index]), $value);
                    }
                } else {
                    $value = str_replace($search, $replace, $value);
                }
            }
            if ($substitution) {
                $convertToHtmlEntities = false;
                if ($field == 'message_html') {
                    $convertToHtmlEntities = true;
                }
                self::substitute($value, $substitution, $convertToHtmlEntities);
            }
            if ($strip) self::clearEmptyPlaceholders($value);
        }
        if ($arrTemplate['attach_pdf'] && isset($arrTemplate['pdf_template'])) {
            $pdf = $cx->getComponent('Pdf');
            if ($pdf) {
                $pdfAttachment = $pdf->generatePDF(
                    $arrTemplate['pdf_template'],
                    $substitution,
                    $arrTemplate['key'],
                    true
                );
            }
        }
//DBG::log("MailTemplate::send(): Substituted: ".var_export($arrTemplate, true));
//echo("MailTemplate::send(): Substituted:<br /><pre>".nl2br(htmlentities(var_export($arrTemplate, true), ENT_QUOTES, CONTREXX_CHARSET))."</PRE><hr />");
//die();//return true;
        // Use defaults for missing mandatory fields
//        if (empty($arrTemplate['sender']))
//            $arrTemplate['sender'] = $_CONFIG['coreAdminName'];
        if (empty($arrTemplate['from'])) {
\DBG::log("MailTemplate::send(): INFO: Empty 'from:', falling back to config");
            $arrTemplate['from'] = $_CONFIG['coreAdminEmail'];
        }
        if (empty($arrTemplate['to'])) {
\DBG::log("MailTemplate::send(): INFO: Empty 'to:', falling back to config");
            $arrTemplate['to'] = $_CONFIG['coreAdminEmail'];
        }
//        if (empty($arrTemplate['subject']))
//            $arrTemplate['subject'] = $_CORELANG['TXT_CORE_MAILTEMPLATE_NO_SUBJECT'];
//        if (empty($arrTemplate['message']))
//            $arrTemplate['message'] = $_CORELANG['TXT_CORE_MAILTEMPLATE_NO_MESSAGE'];

        $objMail->SetFrom($arrTemplate['from'], $arrTemplate['sender']);
        $objMail->Subject = $arrTemplate['subject'];
//        $objMail->IsHTML(false);
        if ($arrTemplate['html']) {
            $objMail->IsHTML(true);
            $objMail->Body = $arrTemplate['message_html'];
            $objMail->AltBody = $arrTemplate['message'];
        } else {
            $objMail->Body = $arrTemplate['message'];
        }
        foreach (preg_split('/\s*,\s*/', $arrTemplate['reply'], null, PREG_SPLIT_NO_EMPTY) as $address) {
            $objMail->AddReplyTo($address);
        }
//        foreach (preg_split('/\s*,\s*/', $arrTemplate['to'], null, PREG_SPLIT_NO_EMPTY) as $address) {
//            $objMail->AddAddress($address);
//        }
        foreach (preg_split('/\s*,\s*/', $arrTemplate['cc'], null, PREG_SPLIT_NO_EMPTY) as $address) {
            $objMail->AddCC($address);
        }
        foreach (preg_split('/\s*,\s*/', $arrTemplate['bcc'], null, PREG_SPLIT_NO_EMPTY) as $address) {
            $objMail->AddBCC($address);
        }

        // Applicable to attachments stored with the MailTemplate only!
        $arrTemplate['attachments'] =
            self::attachmentsToArray($arrTemplate['attachments']);
        if (isset($pdfAttachment)) {
            $arrTemplate['attachments'][$pdfAttachment['filePath']] =
                $pdfAttachment['fileName'];
        }
//DBG::log("MailTemplate::send(): Template Attachments: ".var_export($arrTemplate['attachments'], true));
        // Now the MailTemplates' attachments index is guaranteed to
        // contain an array.
        // Add attachments from the parameter array, if any.
        if (   isset($arrField['attachments'])
            && is_array($arrField['attachments'])) {
            foreach ($arrField['attachments'] as $path => $name) {
//                if (empty($path)) $path = $name;
//                if (empty($name)) $name = basename($path);
                $arrTemplate['attachments'][$path] = $name;
//DBG::log("MailTemplate::send(): Added Field Attachment: $path / $name");
            }
        }
//DBG::log("MailTemplate::send(): All Attachments: ".var_export($arrTemplate['attachments'], true));
        foreach ($arrTemplate['attachments'] as $path => $name) {
            if (is_numeric($path)) $path = $name;
            $objMail->AddAttachment($cx->getWebsiteDocumentRootPath().'/'.$path, $name);
        }
        $arrTemplate['inline'] =
            self::attachmentsToArray($arrTemplate['inline']);
        if ($arrTemplate['inline']) $arrTemplate['html'] = true;
        foreach ($arrTemplate['inline'] as $path => $name) {
            if (is_numeric($path)) $path = $name;
            $objMail->AddEmbeddedImage($cx->getWebsiteDocumentRootPath().'/'.$path, uniqid(), $name);
        }
        if (   isset($arrField['inline'])
            && is_array($arrField['inline'])) {
            $arrTemplate['html'] = true;
            foreach ($arrField['inline'] as $path => $name) {
                if (is_numeric($path)) $path = $name;
                $objMail->AddEmbeddedImage($cx->getWebsiteDocumentRootPath().'/'.$path, uniqid(), $name);
            }
        }
//die("MailTemplate::send(): Attachments and inlines<br />".var_export($objMail, true));
        $objMail->IsHTML($arrTemplate['html']);
//DBG::log("MailTemplate::send(): Sending: ".nl2br(htmlentities(var_export($objMail, true), ENT_QUOTES, CONTREXX_CHARSET))."<br />Sending...<hr />");
        $result = true;
        foreach (preg_split('/\s*;\s*/', $arrTemplate['to'], null, PREG_SPLIT_NO_EMPTY) as $addresses) {
            $objMail->ClearAddresses();
            foreach (preg_split('/\s*[,]\s*/', $addresses, null, PREG_SPLIT_NO_EMPTY) as $address) {
                $objMail->AddAddress($address);
            }
//DBG::log("MailTemplate::send(): ".var_export($objMail, true));
// TODO: Comment for test only!
            $result &= $objMail->Send();
// TODO: $objMail->Send() seems to sometimes return true on localhost where
// sending the mail is actually impossible.  Dunno why.
        }
        return $result;
    }


    /**
     * Substitutes the placeholders found in the $substitution array in $value
     *
     * Each array key in $substitution is regarded as a placeholder name.
     * Each name is enclosed in square brackets ("[", "]") to form the
     * full placeholder.
     * If its value is an array, it represents a repeatable block with contents
     * in an (indexed) array, otherwise it's a simple replacement.
     *
     * Your template $string might look something like
     *
     *    A single [PLACEHOLDER] is substituted here once.
     *
     *    [[BLOCK]This line is repeated for each [ITEM] in the block.[BLOCK]]
     *    If there is no [ITEM] in the BLOCK subarray, the block is never
     *    parsed and thus removed.
     *
     * The $substitution array looks like
     *  array(
     *    'PLACEHOLDER' => 'Scalar replacement value',
     *    'BLOCK'       => array(
     *      index => array(
     *        'ITEM'                        => 'Another scalar value',
     *        'MORE_PLACEHOLDERS_OR_BLOCKS' => 'Nest as deep as memory allows',
     *        ... more ...
     *      ),
     *      ... more ...
     *    ),
     *    ... more ...
     *  )
     *
     * Of course, all names used above are just examples, any block or
     * placeholder name may be an arbitrary word consisting of letters
     * and underscores.
     *
     * Any block may occur more than once.  Its contents are repeated.
     * Each block name *MUST* occur an even number of times in the string.
     *
     * Mind that the names in the substitution array *SHOULD* be unique,
     * or the order of the elements becomes relevant and the results may not
     * be what you expect!  The array is processed depth-first, so every time
     * a block array is encountered, it is completely substituted recursively
     * before the next value is even looked at.
     *
     * Final note:
     * To replace any blocks or placeholders from the string that have not been
     * substituted, call {@see clearEmptyPlaceholders()} after *all* values
     * have been substituted.  This will take care both of unused blocks and
     * placeholders.  See {@see send()} for an example.
     * @param   string    $string         The string to be searched and replaced,
     *                                    by reference
     * @param   array     $substitution   The array of placeholders and values,
     *                                    by reference
     * @param   boolean   $convertToHtmlEntities if true, converts the values to html entities
     */
    static function substitute(&$string, &$substitution, $convertToHtmlEntities = false)
    {
        if (empty($string)) return;
//DBG::log("substitute($string, \$substitution): Entered");
        $match = array();
//DBG::log("Substitution is $substitution ".var_export($substitution, true));
        foreach ($substitution as $placeholder => $value) {
            $block_quoted = preg_quote("[$placeholder]", '/');
            $block_re = '/\['.$block_quoted.'(.+?)'.$block_quoted.'\]/is';
//DBG::log("substitute(): RE: $block_re");
            if (   preg_match($block_re, $string, $match)
                && $match[1]) {
                $block_template = $match[1];
//DBG::log("substitute(): Matched BLOCK $placeholder (RE: $block_re)");
//echo("substitute(): Block template: $block_template<br />");
                $block_parsed = '';
                if (is_array($value)) {
//DBG::log("substitute(): LOOP $placeholder, template $block_template");
                    // Parse block with subarray contents (nested block)
                    foreach ($value as $value_inner) {
                        $block = $block_template;
                        self::substitute($block, $value_inner, $convertToHtmlEntities);
                        $block_parsed .= $block;
//echo("substitute(): Block parsed: ".htmlentities($block)."<br />");
                    }
                } else {
//DBG::log("substitute(): COND $placeholder, template $block_template");
                    // Substitute the block normally, but drop it if
                    // it does not contain any placeholder present
                    // in the substitution (conditional block)
                    $block = $block_template;
                    self::substitute($block, $substitution, $convertToHtmlEntities);
                    if ($block != $block_template) {
                        $block_parsed = $block;
                    }
                }
                $string = preg_replace($block_re, $block_parsed, $string);
//echo("substitute(): Block substituted: ".nl2br(htmlentities($block_parsed))."<br />");
            } else {
                // Cannot operate on simple placeholders with an array
                if (is_array($value)) continue;
                $placeholder_re = '/'.preg_quote("[$placeholder]", '/').'/i';
                if ($convertToHtmlEntities) {
                    $value = contrexx_raw2xhtml($value);
                }
                if (preg_match($placeholder_re, $string)) {
                    $string = preg_replace(
                        $placeholder_re, $value, $string);
//echo("substitute(): PLACEHOLDER $placeholder -> ".nl2br(htmlentities($string))."<br />");
                }
            }
//echo("substitute(): made ".nl2br(contrexx_input2xhtml($string))."<br /><br />");
        }
//echo("substitute($string, ".var_export($substitution, true)."): Leaving<hr />");
//echo("substitute($string, \$substitution): Leaving<hr />");
    }


    /**
     * Removes left over placeholders and blocks from the string
     * @param   string    $value        The string, by reference
     */
    static function clearEmptyPlaceholders(&$value)
    {
        // Replace left over blocks
        $value = preg_replace('/\[(\[\w+\]).+?\1\]/s', '', $value);
        // Replace left over placeholders
        $value = preg_replace('/\[\w+\]/', '', $value);
//echo("clearEmptyPlaceholders($value): Leaving<hr />");
    }


    /**
     * Converts the attachment string from the database table into an array,
     * if necessary
     *
     * If the parameter value is an array already, it is returned unchanged.
     * If the parameter value is a string, it *MUST* be in one of the forms
     *  'return array();'
     * or
     *  'return array(index => "path/filename");'
     * or
     *  'return array("path" => "filename");'
     * with zero or more entries containing at least the "filename" as value
     * and an optional path as key.  "path" *MUST* be either a path relative
     * to the document root (including the file name), or a numeric key.
     * If "path" is numeric, it will be ignored and the "filename" will be used.
     * In this case, "filename" itself needs to contain the path to the
     * attachment.
     * The third form allows you to specify a file name different from the
     * original provided in "path" to be used for the e-mail.
     * @param   mixed     $attachments      The attachment string or array
     * @return  array                       The attachment array on success,
     *                                      the empty array otherwise
     */
    static function attachmentsToArray($attachments)
    {
        if (is_array($attachments)) return $attachments;
        $arrAttachments = array();
//echo("Attachment string: ".var_export($attachments, true)."\n");
        try {
            $arrAttachments = @eval($attachments);
        } catch (Exception $e) {
            \DBG::log($e->__toString());
//echo("Eval error: /".$e->__toString()."/\n");
        }
//echo("Attachment array: ".var_export($arrAttachments, true)."\n");
//die("EVAL");
        if (!is_array($arrAttachments)) $arrAttachments = array();
        return $arrAttachments;
    }


    /**
     * Delete the template with the given key
     *
     * Protected (system) templates can not be deleted.
     * Deletes all languages available.
     * if the $key argument is left out, looks for a key in the
     * delete_mailtemplate_key index of the $_REQUEST array.
     * @param   string    $key    The optional template key
     * @return  boolean           True on success, false otherwise
     */
    static function deleteTemplate($section, $key='')
    {
        global $objDatabase, $_CORELANG;

        if (empty($key)) {
            if (empty($_REQUEST['delete_mailtemplate_key'])) return '';
            $key = $_REQUEST['delete_mailtemplate_key'];
            // Prevent this from being run twice
            unset($_REQUEST['delete_mailtemplate_key']);
        }
        $arrTemplate = self::get($section, $key);
        // Cannot delete protected (system) templates
        if ($arrTemplate['protected']) {
            return \Message::error($_CORELANG['TXT_CORE_MAILTEMPLATE_IS_PROTECTED']);
        }
        // Preemptively force a reinit
        self::reset();
        // Delete associated Text records
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_NAME)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_FROM)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_SENDER)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_REPLY)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_TO)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_CC)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_BCC)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_SUBJECT)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_MESSAGE)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_MESSAGE_HTML)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_ATTACHMENTS)) return false;
        if (!\Text::deleteById(
            $arrTemplate['text_id'], $section, self::TEXT_INLINE)) return false;
        if (!$objDatabase->Execute("
            DELETE FROM `".DBPREFIX."core_mail_template`
             WHERE `key`='".addslashes($key)."'")) {
            return \Message::error($_CORELANG['TXT_CORE_MAILTEMPLATE_DELETING_FAILED']);
        }

        return \Message::ok($_CORELANG['TXT_CORE_MAILTEMPLATE_DELETED_SUCCESSFULLY']);
    }


    /**
     * Update or add a new template
     *
     * Stores the template for the given section
     * Uses the language ID from the lang_id index, if present,
     * or the FRONTEND_LANG_ID constant otherwise.
     *  key           The key of any mail template to be used
     *  lang_id       The language ID
     *  sender        The sender name
     *  from          The sender e-mail address
     *  to            The recipient e-mail address(es), comma separated
     *  reply         The reply-to e-mail address
     *  cc            The carbon copy e-mail address(es), comma separated
     *  bcc           The blind carbon copy e-mail address(es), comma separated
     *  subject       The message subject
     *  message       The plain text message body
     *  message_html  The HTML message body
     *  html          If this evaluates to true, turns on HTML mode
     *  attachments   An array of file paths to attach.  The array keys may
     *                be used for the paths, and the values for the name.
     *                If the keys are numeric, the values are regarded as paths.
     * The key index is mandatory.  If available, the corresponding mail
     * template is loaded, and updated.
     * Missing fields are filled with default values, which are generally empty.
     * The protected flag can neither be set nor cleared by calling this method,
     * but is always kept as-is.
     * Note:  The attachment paths must comply with the requirements for
     * file paths as defined in the {@see File} class version 2.2.0.
     * @param   string    $section    The section
     * @param   array     $arrField   The field array
     * @return  boolean               True on success, false otherwise
     */
    static function store($section, $arrField)
    {
        global $objDatabase;

        if (empty($arrField['key'])) return false;
// TODO: Field verification
// This is non-trivial, as any placeholders must also be recognized and accepted!
//        if (!empty($arrField['from']) && !\FWValidator::isEmail($arrField['from'])) ...
        $lang_id = (isset($arrField['lang_id'])
            ? $arrField['lang_id'] : FRONTEND_LANG_ID);
        $key = $arrField['key'];
        // Strip crap characters from the key; neither umlauts nor symbols allowed
        $key = preg_replace('/[^_a-z\d]/i', '', $key);
        $text_id = 0;

        // The original template is needed for the Text IDs and protected
        // flag only
        $arrTemplate = self::get($section, $key, $lang_id);
        if ($arrTemplate) { // && $arrTemplate['available']) {
            $arrField['protected'] = $arrTemplate['protected'];
            $text_id = $arrTemplate['text_id'];
            // If the key is present in the database, update the record.
            $query = "
                UPDATE ".DBPREFIX."core_mail_template
                   SET `html`=".(empty($arrField['html']) ? 0 : 1).",
                       `protected`=".(empty($arrField['protected']) ? 0 : 1)."
                 WHERE `key`='".addslashes($key)."'
                   AND `section`".
                (isset($section) ? "='".addslashes($section)."'" : ' IS NULL');
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) {
\DBG::log("MailTemplate::store(): ERROR updating Template with key $key");
                return self::errorHandler();
            }
        } else {
            $query = "
                SELECT MAX(`text_id`) AS `id`
                  FROM ".DBPREFIX."core_mail_template";
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) return self::errorHandler();
            $text_id = $objResult->fields['id'] + 1;
            $query = "
                INSERT INTO ".DBPREFIX."core_mail_template (
                    `key`, `section`, `html`, `protected`, `text_id`
                ) VALUES (
                    '".addslashes($key)."', ".
                    (isset($section) ? "'".addslashes($section)."'" : 'NULL').",
                    ".(empty($arrField['html']) ? 0 : 1).",
                    ".(empty($arrField['protected']) ? 0 : 1).",
                    $text_id
                )";
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) {
\DBG::log("MailTemplate::store(): ERROR inserting Template with key $key");
                return self::errorHandler();
            }
        }
        foreach (self::$text as $index => $key) {
            if (isset($arrField[$index])) {
                if (!\Text::replace($text_id, $lang_id, $section,
                    $key, $arrField[$index])) {
\DBG::log("MailTemplate::store(): ERROR replacing text for key $key, ID $text_id, lang ID $lang_id");
                    return false;
                }
            } else {
                if (!\Text::deleteById($text_id, $section, $key, $lang_id)) {
\DBG::log("MailTemplate::store(): ERROR deleting text for key $key, ID $text_id, lang ID $lang_id");
                    return false;
                }
            }
        }
        // Force reinit
        self::reset();
        return true;
    }


    /**
     * Show on adminview of the mail templates for the given section and group
     *
     * @param   string    $section      The section
     * @param   string    $group        The group
     *                                  default set to nonempty
     * @param   integer   $limit        The optional limit for the number
     *                                  of templates to get listed per page
     * @param   string   $act           The action of the mail template
     *                                  default set to mailtemplate_overview
     * @return  \Cx\Core\Html\Sigma     The template object
     */
    static function adminView($section, $group = 'nonempty', $limit = 0, $act = 'mailtemplate_overview') {
        self::storeFromPost($section);
        if (!isset($_GET['key'])) {
            if (!empty($_GET['delete_mailtemplate_key'])) {
               self::deleteTemplate($section);
            }
            return static::overview($section, $group, $limit, false, $act);
        } else {
            return static::edit($section, '', false, $act);
        }
    }


    /**
     * Show on overview of the mail templates for the given section and group
     *
     * If empty, the $limit defaults to the
     * "numof_mailtemplate_per_page_backend" setting for the given section
     * and group.
     * @param   string    $section      The section
     * @param   string    $group        The group
     * @param   integer   $limit        The optional limit for the number
     *                                  of templates to be shown
     * @param   string   $act           The action of the mail template
     *                                  default set to mailtemplate_overview
     * @return  \Cx\Core\Html\Sigma     The template object
     */
    static function overview($section, $group, $limit=0, $useDefaultActs = true, $act = 'mailtemplate_edit')
    {
        global $_CORELANG;

        $objTemplateLocal = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseCorePath().'/MailTemplate/View/Template/Generic');
        $objTemplateLocal->setErrorHandling(PEAR_ERROR_DIE);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objTemplateLocal);
        if (!$objTemplateLocal->loadTemplateFile('Overview.html'))
            die("Failed to load template Overview.html");
        if (empty ($section) || empty ($group)) {
            \Message::error(
                $_CORELANG['TXT_CORE_MAILTEMPLATE_ERROR_NO_SECTION_OR_GROUP']);
            return false;
        }
        if (empty($limit)) {
            \Cx\Core\Setting\Controller\Setting::init($section, $group);
            $limit = \Cx\Core\Setting\Controller\Setting::getValue('numof_mailtemplate_per_page_backend', $section);
// TODO: TEMPORARY
            if (is_null($limit)) {
              $limit = 25;
              \Cx\Core\Setting\Controller\Setting::add('numof_mailtemplate_per_page_backend', $limit,
                  1001, 'text', '', $group);
            }
        }
        $uri = \Html::getRelativeUri_entities();
        $tab_index = \Cx\Core\Setting\Controller\Setting::tab_index();
        \Html::replaceUriParameter($uri, 'active_tab='.$tab_index);
        \Html::replaceUriParameter($uri, 'userFrontendLangId='.FRONTEND_LANG_ID);
//echo("Made uri for sorting: ".htmlentities($uri)."<br />");
        \Html::stripUriParam($uri, 'key');
        \Html::stripUriParam($uri, 'delete_mailtemplate_key');
        $uri_edit = $uri_overview = $uri;
//echo("Made uri for sorting: ".htmlentities($uri)."<br />");
        if ($useDefaultActs) {
            \Html::stripUriParam($uri, 'act');
            \Html::replaceUriParameter($uri_edit, 'act=mailtemplate_edit');
            \Html::replaceUriParameter($uri_overview, 'act=mailtemplate_overview');
        }
        $objSorting = new \Sorting(
            $uri_overview,
            array(
                'name' => $_CORELANG['TXT_CORE_MAILTEMPLATE_NAME'],
                'key'  => $_CORELANG['TXT_CORE_MAILTEMPLATE_KEY'],
                'html' => $_CORELANG['TXT_CORE_MAILTEMPLATE_IS_HTML'],
                'protected' => $_CORELANG['TXT_CORE_MAILTEMPLATE_PROTECTED'],
            ),
            true,
            'order_mailtemplate'
        );
        $count = 0;
        // Template titles are shown in the current language only, no need
        // (and no way either) to load them all.  Names are shown in the
        // currently active frontend language only.
        $pagingParameterName = $section.'_'.$group;
        $arrTemplates = self::getArray(
            $section,
            FRONTEND_LANG_ID,
            $objSorting->getOrder(),
            \Paging::getPosition($pagingParameterName),
            $limit,
            $count
        );
        $arrLanguageName = \FWLanguage::getNameArray();
        $objTemplateLocal->setGlobalVariable(
            $_CORELANG
          + array(
            'CORE_MAILTEMPLATE_NAME' => $objSorting->getHeaderForField('name'),
            'CORE_MAILTEMPLATE_KEY' => $objSorting->getHeaderForField('key'),
            'CORE_MAILTEMPLATE_HTML' => $objSorting->getHeaderForField('html'),
            'CORE_MAILTEMPLATE_PROTECTED' => $objSorting->getHeaderForField('protected'),
            'PAGING' => \Paging::get(
                $uri_overview, $_CORELANG['TXT_CORE_MAILTEMPLATE_PAGING'],
                $count, $limit, true, null, $pagingParameterName),
            'URI_BASE' => $uri,
            'URI_EDIT' => $uri_edit,
            'CORE_MAILTEMPLATE_COLSPAN' => 5 + count($arrLanguageName),
        ));
        foreach ($arrLanguageName as $language_name) {
            $objTemplateLocal->setVariable(
                'MAILTEMPLATE_LANGUAGE_HEADER', $language_name);
            $objTemplateLocal->parse('core_mailtemplate_language_header');
        }

        if (empty($arrTemplates)) {
            \Message::information($_CORELANG['TXT_CORE_MAILTEMPLATE_WARNING_NONE']);
            $arrTemplates = array();
        }
        // Load *all* templates and languages
        self::init($section);
        $i = 0;
        foreach ($arrTemplates as $arrTemplate) {
            $key = $arrTemplate['key'];
            $objTemplateLocal->setVariable(array(
                'MAILTEMPLATE_ROWCLASS' => ++$i % 2 + 1,
                'MAILTEMPLATE_PROTECTED' =>
                    \Html::getCheckmark($arrTemplate['protected']),
                'MAILTEMPLATE_HTML' =>
                    \Html::getCheckmark($arrTemplate['html']),
                'MAILTEMPLATE_NAME' =>
                    '<a href="'.$uri_edit.
                    '&amp;key='.urlencode($key).'">'.
                      contrexx_raw2xhtml($arrTemplate['name']).
                    '</a>',
                'MAILTEMPLATE_KEY' => $arrTemplate['key'],
                'MAILTEMPLATE_FUNCTIONS' => \Html::getBackendFunctions(
                    array(
                        'copy'   => $uri_edit.'&amp;copy=1&amp;key='.$arrTemplate['key'],
                        'edit'   => $uri_edit.'&amp;key='.$arrTemplate['key'],
                        'delete' => ($arrTemplate['protected']
                          ? ''
                          : $uri_overview.'&amp;delete_mailtemplate_key='.$arrTemplate['key'].'&amp;csrf='.\Cx\Core\Csrf\Controller\Csrf::code()),
                    ),
                    array(
                        'delete' => $_CORELANG['TXT_CORE_MAILTEMPLATE_DELETE_CONFIRM'],
                    )
                ),
            ));
            foreach (array_keys($arrLanguageName) as $lang_id) {
                $available =
                    (   isset(self::$arrTemplates[$lang_id][$key])
                     && self::$arrTemplates[$lang_id][$key]['available']);
                $title = ($available
                    ? $_CORELANG['TXT_CORE_MAILTEMPLATE_EDIT']
                    : $_CORELANG['TXT_CORE_MAILTEMPLATE_NEW']);
                $icon =
                    '<a href="'.
                        CONTREXX_DIRECTORY_INDEX.
                        "?cmd=$section&amp;act=".$act.
                        '&amp;key='.$key.
                        '&amp;userFrontendLangId='.$lang_id.'"'.
                    ' title="'.$title.'">'.
                    '<img src="../core/Core/View/Media/icons/'.
                    ($available ? 'edit.gif' : 'add.png').'"'.
                    ' width="16" height="16" alt="'.$title.'" border="0" /></a>';
                $objTemplateLocal->setVariable('MAILTEMPLATE_LANGUAGE', $icon);
                $objTemplateLocal->parse('core_mailtemplate_language_column');
            }
            $objTemplateLocal->parse('core_mailtemplate_row');
        }
        return $objTemplateLocal;
    }


    /**
     * Show the selected mail template for editing
     *
     * Stores the MailTemplate if the 'bsubmit' parameter has been posted.
     * If the $key argument is empty, tries to pick the value from
     * $_REQUEST['key'].
     * @param   mixed     $section      The section of the mail template
     *                                  to be edited
     * @param   string    $key          The optional key of the mail template
     *                                  to be edited
     * @param   string    $act          The action of the mail template
     * @return  \Cx\Core\Html\Sigma     The template object
     */
    static function edit(
        $section,
        $key = '',
        $useDefaultActs = true,
        $act = 'mailtemplate_overview'
    ) {
        global $_CORELANG;

        // If the $key parameter is empty, check the request
        if (empty($key) && isset($_REQUEST['key'])) {
            $key = $_REQUEST['key'];
        }
        // Try to load an existing template for any non-empty key
        $arrTemplate = null;
        if ($key != '') {
            $arrTemplate = self::get($section, $key, FRONTEND_LANG_ID);
        }
        // If there is none, get an empty template
        $new = false;
        if (!$arrTemplate) {
            $new = true;
            $arrTemplate = self::getEmpty($key);
        }
        // Copy the template?
        if (isset($_REQUEST['copy'])) {
            $arrTemplate['key'] = '';
            $new = true;
        }
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objTemplate = new \Cx\Core\Html\Sigma(
            $cx->getCodeBaseCorePath() . '/MailTemplate/View/Template/Generic'
        );
        $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objTemplate);
        if (!$objTemplate->loadTemplateFile('Edit.html')) {
            die("Failed to load template Edit.html");
        }
        $uri = \Html::getRelativeUri_entities();
        \Html::stripUriParam($uri, 'key');
        $uriAppendix = '';
        if ($useDefaultActs) {
            \Html::stripUriParam($uri, 'act');
            $uriAppendix = '&amp;act=' . $act;
        }
        $tab_index = \Cx\Core\Setting\Controller\Setting::tab_index();
        \Html::replaceUriParameter($uri, 'active_tab=' . $tab_index);
        \Html::replaceUriParameter(
            $uri,
            'userFrontendLangId=' . FRONTEND_LANG_ID
        );
        $objTemplate->setGlobalVariable(
            $_CORELANG + array(
                'CORE_MAILTEMPLATE_EDIT_TITLE' => (
                    $new
                    ? $_CORELANG['TXT_CORE_MAILTEMPLATE_ADD']
                    : $_CORELANG['TXT_CORE_MAILTEMPLATE_EDIT']
                ),
                'CORE_MAILTEMPLATE_CMD'        =>
                    (isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : ''),
                'CORE_MAILTEMPLATE_ACTIVE_TAB' => $tab_index,
                'URI_BASE'                     => $uri . $uriAppendix,
            )
        );

        $i = 0;
        foreach ($arrTemplate as $name => $value) {
            // See if there is a posted parameter value
            if (isset($_POST[$name])) {
                $value = $_POST[$name];
            }

            // IDs are set up as hidden fields.
            // They *MUST NOT* be edited!
            if (preg_match('/(?:_id)$/', $name)) {
                // For copies, IDs *MUST NOT* be reused!
                if (isset($_REQUEST['copy'])) {
                    $value = 0;
                }
                $objTemplate->setVariable(
                    'MAILTEMPLATE_HIDDEN',
                    \Html::getHidden($name, $value)
                );
                $objTemplate->parse('core_mailtemplate_hidden');
                continue;
            }

            // Regular inputs of various kinds
            $input = '';
            $attribute = '';
//            $arrMimetype = '';
            switch ($name) {
                case 'available':
                    continue 2;

                /*
                  TODO: WARNING: FCKEditor v2.x f***s up the blocks in the mail template!
                  Use plain text areas instead.  See below.
                 */
                case 'message_html':
                    // Show WYSIWYG only if HTML is enabled
                    if (empty($arrTemplate['html']))
                        continue 2;
                    $objTemplate->setVariable(array(
                        'MAILTEMPLATE_ROWCLASS' => ( ++$i % 2 + 1),
                        'MAILTEMPLATE_SPECIAL'  => $_CORELANG[
                            'TXT_CORE_MAILTEMPLATE_' . strtoupper($name)
                        ],
                    ));
                    $objTemplate->touchBlock('core_mailtemplate_special');
                    $objTemplate->parse('core_mailtemplate_special');
                    $objTemplate->setVariable(array(
                        'MAILTEMPLATE_ROWCLASS' => ( ++$i % 2 + 1),
                        'MAILTEMPLATE_SPECIAL' => new \Cx\Core\Wysiwyg\Wysiwyg(
                            $name,
                            contrexx_raw2xhtml($value),
                            'fullpage'
                        ),
                    ));
                    $objTemplate->touchBlock('core_mailtemplate_special');
                    $objTemplate->parse('core_mailtemplate_special');
                    continue 2;
                //$objTemplate->replaceBlock('core_mailtemplate_special', '', true);
//              case 'message_html':
                case 'message':
                    $input = \Html::getTextarea(
                        $name,
                        $value,
                        '',
                        10,
                        'style="width: 600px;"'
                    );
                    break;
                case 'protected':
                    $input = \Html::getCheckbox(
                        $name,
                        1,
                        '',
                        $value,
                        '',
                        \Html::ATTRIBUTE_DISABLED
                    );
                    break;
                case 'attach_pdf':
                    \JS::registerCode('
                        jQuery(document).ready(function() {
                            showOrHidePdfTpl();
                        });
                        function showOrHidePdfTpl() {
                            var that = jQuery("select[name=pdf_template]")
                                .closest("tr");
                            that.hide();
                            if (jQuery("input[name=attach_pdf]").is(":checked")) {
                                that.show();
                            }
                        }
                    ');
                    $input = \Html::getCheckbox(
                        $name,
                        1,
                        '',
                        $value,
                        'showOrHidePdfTpl();',
                        $attribute
                    );
                    break;
                case 'pdf_template':
                    $arrOptions = $cx->getComponent('Pdf')->getPdfTemplates();
                    $input      = \Html::getSelect(
                        $name,
                        $arrOptions,
                        $value,
                        false,
                        '',
                        'style="width:300px;"'
                    );
                    break;
                case 'html':
                    $input = \Html::getCheckbox(
                        $name,
                        1,
                        '',
                        $value,
                        '',
                        $attribute
                    ) . '&nbsp;' .
                    $_CORELANG['TXT_CORE_MAILTEMPLATE_STORE_TO_SWITCH_EDITOR'];
                    break;

                case 'inline':
                case 'attachments':
                    continue 2;
// TODO: These do not work properly yet
                /*
                  // These are identical except for the MIME types
                  case 'inline':
                  $arrMimetype = \Filetype::getImageMimetypes();
                  case 'attachments':
                  $arrAttachments = self::attachmentsToArray($arrTemplate[$name]);
                  // Show at least one empty attachment/inline row
                  if (empty($arrAttachments))
                  $arrAttachments = array(array('path' => '', ), );
                  $i = 0;
                  foreach ($arrAttachments as $arrAttachment) {
                  $div_id = $name.'-'.(++$i);
                  $element_name = $name.'['.$i.']';
                  $input .=
                  '<div id="'.$div_id.'">'.
                  \Html::getHidden(
                  $element_name.'[old]', $arrAttachment['path'],
                  $name.'-hidden-'.$i).
                  $arrAttachment['path'].'&nbsp;'.
                  $_CORELANG['TXT_CORE_MAILTEMPLATE_ATTACHMENT_UPLOAD'].
                  \Html::getInputFileupload(
                  $element_name.'[new]', $name.'-file-'.$i,
                  \Filetype::MAXIMUM_UPLOAD_FILE_SIZE,
                  $arrMimetype).
                  // Links for adding/removing inputs
                  \Html::getRemoveAddLinks($div_id).
                  '</div>';
                  }
                  //echo("$name => ".htmlentities($input)."<hr />");
                  break;
                 */

                // Once the key is defined, it cannot be changed.
                // To fix a wrong key, copy the old template and enter a new key,
                // then delete the old one.
                case 'key':
                    $input = (
                        $arrTemplate['key']
                        ? $value . \Html::getHidden($name, $value)
                        : \Html::getInputText(
                              $name,
                              $value,
                              '',
                              'style="width: 300px;"'
                          )
                    );
//echo("Key /$key/ -> attr $attribute<br />");
                    break;

                default:
                    $input = \Html::getInputText(
                        $name,
                        $value,
                        '',
                        'style="width: 300px;"'
                    );
            }
            $name_upper = strtoupper($name);
            $objTemplate->setVariable(array(
                'MAILTEMPLATE_ROWCLASS' => ( ++$i % 2 + 1),
                'MAILTEMPLATE_NAME'     =>
                $_CORELANG['TXT_CORE_MAILTEMPLATE_' . $name_upper],
                'MAILTEMPLATE_VALUE'    => $input,
            ));
            // Add note with helpful hints, if available
            if (isset($_CORELANG['TXT_CORE_MAILTEMPLATE_NOTE_' . $name_upper])) {
                $objTemplate->setVariable(
                    'MAILTEMPLATE_VALUE_NOTE',
                    $_CORELANG['TXT_CORE_MAILTEMPLATE_NOTE_' . $name_upper]
                );
            }
            $objTemplate->parse('core_mailtemplate_row');
        }

        // Send the (possibly edited and now stored) mail, if requested
        if (empty($_POST['to_test'])) {
            return $objTemplate;
        }
        if (empty($key)) {
            \Message::error($_CORELANG['TXT_CORE_MAILTEMPLATE_ERROR_NO_KEY']);
            return $objTemplate;
        }
        $to_test = contrexx_input2raw($_POST['to_test']);
        $objTemplate->setVariable(array(
            'CORE_MAILTEMPLATE_TO_TEST' => $to_test,
            'TXT_CORE_MAILTEMPLATE_TESTMAIL_NOTE' => $_CORELANG[
                'TXT_CORE_MAILTEMPLATE_TESTMAIL_NOTE'
            ],
        ));
        return $objTemplate;
    }

    static function sendTestMail($section, $key, $email) {
        global $_CORELANG;

        if (empty($email)) return;

        // try to load "from" and use it if it contains no placeholder
        $from = '';
        $template = static::get($section, $key);
        if (
            $template &&
            isset($template['from']) &&
            !preg_match('/\[.*\]/', $template['from'])
        ) {
            $from = $template['from'];
        }

        $sent = self::send(array(
            'section' => $section,
            'key' => $key,
            'to' => $email,
            'from' => $from,
            'cc' => '',
            'bcc' => '',
            'do_not_strip_empty_placeholders' => true, ));
        if ($sent) {
            \Message::ok(sprintf(
                $_CORELANG['TXT_CORE_MAILTEMPLATE_KEY_SENT_SUCCESSFULLY_TO'],
                $key, $email));
        } else {
            \Message::error(sprintf(
                $_CORELANG['TXT_CORE_MAILTEMPLATE_ERROR_SENDING_KEY_TO'],
                $key, $email));
        }
    }

    /**
     * Stores a template after editing
     *
     * Sets appropriate messages.
     * @param   string    $section          The section
     * @return  boolean                     True on success, null if nothing
     *                                      needs storing, false otherwise
     */
    static function storeFromPost($section)
    {
        global $_CORELANG;

        if (empty($_POST['bsubmit'])) return null;
        if (empty($_POST['key'])) {
            return \Message::error($_CORELANG['TXT_CORE_MAILTEMPLATE_ERROR_NO_KEY']);
        }
        if (empty($_POST['name'])) {
            return \Message::error($_CORELANG['TXT_CORE_MAILTEMPLATE_ERROR_NO_NAME']);
        }
// TODO: Wrong; might stripslashes() again later, yielding wrong results
        foreach ($_POST as &$value) {
            $value = contrexx_input2raw($value);
        }
        if (self::store($section, $_POST)) {
// Prevent this from being run twice
//            unset($_POST['text_from_id']);
            self::sendTestMail($section, $_POST['key'], contrexx_input2raw($_POST['to_test']));
            return \Message::ok($_CORELANG['TXT_CORE_MAILTEMPLATE_STORED_SUCCESSFULLY']);
        }
// Prevent this from being run twice
//        unset($_POST['text_from_id']);
        return \Message::error($_CORELANG['TXT_CORE_MAILTEMPLATE_STORING_FAILED']);
    }


    /**
     * Returns true if the template for the key exists and is available in
     * that language
     *
     * Mind that {@see init()} should have been called before, setting up
     * the data in all languages you're about to test.
     * Returns false on failure.
     * @param   string    $key          The MailTemplate key
     * @param   integer   $lang_id      The language ID
     * @return  boolean                 True if the key is available in that
     *                                  language, false otherwise
     */
    static function available($key, $lang_id)
    {
        return !empty(self::$arrTemplates[$lang_id][$key]['available']);
    }


    /**
     * Handles many problems caused by the database table
     * @return    boolean     False.  Always.
     */
    static function errorHandler()
    {
        \Text::errorHandler();
//DBG::activate(DBG_DB_FIREPHP);
        $table_name = DBPREFIX."core_mail_template";
        $table_structure = array(
            'key' => array('type' => 'TINYTEXT', 'default' => ''),
            'section' => array('type' => 'TINYTEXT', 'notnull' => false, 'default' => null, 'renamefrom' => 'module_id'),
            'text_id' => array('type' => 'INT(10)', 'unsigned' => true, 'renamefrom' => 'text_name_id'),
            'html' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '0'),
            'protected' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '0'),
        );
        $table_index =  array(
// TODO: Primary keys with a length (like "key(32)") are not supported
// by UpdateUtil
//            'primary' => array('key(32)', 'section(32)'),
        );
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        \Cx\Lib\UpdateUtil::sql("
            ALTER TABLE `$table_name`
              ADD PRIMARY KEY (`key` (32), `section` (32))");
//DBG::log("Mailtemplate::errorHandler(): Migrated table core_mail_template");

        // Always!
        return false;
    }

}
