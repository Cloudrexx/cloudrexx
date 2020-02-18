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
 * User Settings Mail Object
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * User Settings Mail Object
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class User_Setting_Mail
{
    var $type;
    var $lang_id;
    var $sender_mail;
    var $sender_name;
    var $subject;
    var $format;
    var $body_text;
    var $body_html;

    /**
     * @access public
     */
    var $EOF;
    var $languageEOF;

    var $arrLoadedMails = array();


    /**
     * Contains the message if an error occurs
     *
     * @var string
     * @access private
     */
    var $error_msg;

    var $arrAvailableFormats = array(
        'text'        => 'TXT_ACCESS_ONLY_TEXT',
        'html'        => 'TXT_ACCESS_HTML_UC',
        'multipart'    => 'TXT_ACCESS_MULTIPART'
    );

    /**
     * @access private
     */
    var $arrAttributes = array(
        'type'                => 'string',
        'lang_id'            => 'int',
        'sender_mail'        => 'string',
        'sender_name'        => 'string',
        'subject'            => 'string',
        'format'            => 'string',
        'body_text'            => 'string',
        'body_html'            => 'string'
    );

    var $arrAvailableTypes = array(
        'reg_confirm'        => array(
            'title'    => 'TXT_ACCESS_REGISTER_CONFIRMATION',
            'placeholders'    => array(
                '[[USERNAME]]'            => 'TXT_ACCESS_USERNAME_DESC',
                '[[HOST]]'                => 'TXT_ACCESS_HOST_DESC',
                '[[ACTIVATION_LINK]]'    => 'TXT_ACCESS_ACTIVATION_LINK_DESC',
                '[[HOST_LINK]]'            => 'TXT_ACCESS_HOST_LINK_DESC',
                '[[SENDER]]'            => 'TXT_ACCESS_SENDER_DESC',
                '[[YEAR]]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array(
                '[[ACTIVATION_LINK]]'
            )
        ),
        'reset_pw'            => array(
            'title'    => 'TXT_ACCESS_RESET_PASSWORD',
            'placeholders'    => array(
                '[[USERNAME]]'    => 'TXT_ACCESS_USERNAME_DESC',
                '[[URL]]'        => 'TXT_ACCESS_RESET_PW_URL_DESC',
                '[[SENDER]]'    => 'TXT_ACCESS_SENDER_DESC',
                '[[YEAR]]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array(
                '[[URL]]'
            )
        ),
        'user_activated'    => array(
            'title'    => 'TXT_ACCESS_USER_ACCOUNT_ACTIVATED',
            'placeholders'    => array(
                '[[USERNAME]]'    => 'TXT_ACCESS_USERNAME_DESC',
                '[[HOST]]'        => 'TXT_ACCESS_HOST_DESC',
                '[[SENDER]]'    => 'TXT_ACCESS_SENDER_DESC',
                '[[YEAR]]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array()
        ),
        'user_deactivated'    => array(
            'title'    => 'TXT_ACCESS_USER_ACCOUNT_DEACTIVATED',
            'placeholders'    => array(
                '[[USERNAME]]'    => 'TXT_ACCESS_USERNAME_DESC',
                '[[HOST]]'        => 'TXT_ACCESS_HOST_DESC',
                '[[SENDER]]'    => 'TXT_ACCESS_SENDER_DESC',
                '[[YEAR]]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array()
        ),
        'new_user'            => array(
            'title'    => 'TXT_ACCESS_NEW_USER_REGISTRATION',
            'placeholders'    => array(
                '[[USERNAME]]'    => 'TXT_ACCESS_USERNAME_DESC',
                '[[LINK]]'        => 'TXT_ACCESS_MANAGE_USER_LINK_DESC',
                '[[SENDER]]'    => 'TXT_ACCESS_SENDER_DESC',
                '[[YEAR]]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array(
                '[[USERNAME]]'
            )
        ),
        'user_account_invitation'    => array(
            'title'    => 'TXT_ACCESS_USER_ACCOUNT_INVITATION',
            'placeholders'    => array(
                '[[FIRSTNAME]]'    => 'TXT_ACCESS_FIRSTNAME',
                '[[LASTNAME]]'        => 'TXT_ACCESS_LASTNAME',
                '[[WEBSITE]]'        => 'TXT_SETTINGS_DOMAIN_URL',
                '[[LINK]]'        => 'TXT_ACCESS_LINK_DESC',
                '[[EMAIL]]'        => 'TXT_ACCESS_EMAIL_DESC',
                '[[PASSWORD]]'        => 'TXT_ACCESS_PASSWORD',
                '[[SENDER]]'    => 'TXT_ACCESS_SENDER_DESC',
                '[[YEAR]]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array()
        ),
        'signup_notification'    => array(
            'title'    => 'TXT_ACCESS_SIGNUP_NOTIFICATION',
            'placeholders'    => array(
                '[HOST]'          => 'TXT_ACCESS_HOST_DESC',
                '[USER_ID]'       => 'TXT_ACCESS_USER_ID_SIGNUP',
                '[PROFILE_NAME]'  => 'TXT_ACCESS_PROFILE_NAME_SIGNUP_DESC',
                '[PROFILE_DATA]'  => 'TXT_ACCESS_PROFILE_DATA_SIGNUP_DESC',
                '[[PROFILE_ATTRIBUTE_LIST]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;[PROFILE_ATTRIBUTE_NAME]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;[PROFILE_ATTRIBUTE_OLD_VALUE]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;[PROFILE_ATTRIBUTE_VALUE]<br />
                    [PROFILE_ATTRIBUTE_LIST]]'  => 'TXT_ACCESS_PROFILE_ATTRIBUTE_LIST_SIGNUP_DESC',
                '[YEAR]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array()
        ),
        'user_profile_modification'    => array(
            'title'    => 'TXT_ACCESS_USER_PROFILE_MODIFICATION',
            'placeholders'    => array(
                '[HOST]'          => 'TXT_ACCESS_HOST_DESC',
                '[USER_ID]'       => 'TXT_ACCESS_USER_ID',
                '[PROFILE_NAME]'  => 'TXT_ACCESS_PROFILE_NAME_DESC',
                '[PROFILE_DATA]'  => 'TXT_ACCESS_PROFILE_DATA_DESC',
                '[[PROFILE_ATTRIBUTE_LIST]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;[[PROFILE_ATTRIBUTE_CHANGED]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[PROFILE_ATTRIBUTE_NAME]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[PROFILE_ATTRIBUTE_OLD_VALUE]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[PROFILE_ATTRIBUTE_VALUE]<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;[PROFILE_ATTRIBUTE_CHANGED]]<br />
                    [PROFILE_ATTRIBUTE_LIST]]'  => 'TXT_ACCESS_PROFILE_ATTRIBUTE_LIST_DESC',
                '[YEAR]'          => 'TXT_ACCESS_YEAR_DESC',
            ),
            'required'    => array()
        ),
    );


    function __construct()
    {
        $this->clean();
    }

    /**
     * Clean data
     *
     * Reset all data for a new mail template.
     *
     */
    function clean()
    {
        $this->type = null;
        $this->lang_id = null;
        $this->sender_mail = '';
        $this->sender_name = '';
        $this->subject = '';
        $this->format = null;
        $this->body_text = '';
        $this->body_html = '';
        $this->EOF = true;
    }


    /**
     * Load e-mail template
     *
     * Get attributes of an e-mail template from the database
     * and put them into the analogous class variables.
     *
     * @param string $type
     * @param integer $langId
     * @return unknown
     */
    function load($type, $langId = 0)
    {
        if ($type) {
            if (!isset($this->arrLoadedMails[$type][$langId])) {
                return $this->loadMails($type, $langId);
            }
            foreach ($this->arrLoadedMails[$type][$langId] as $attribute => $value) {
                $this->{$attribute} = $value;
            }
            return true;
        }
        $this->clean();
        return true;
    }


    function loadMails($type = null, $langId = null)
    {
        global $objDatabase;

        $this->arrLoadedMails = array();

        $query = '
            SELECT `'.implode('`,`', array_keys($this->arrAttributes)).'`
            FROM `'.DBPREFIX.'access_user_mail`'
            .(isset($type) || isset($langId) ? ' WHERE'.(isset($type) ? " `type` = '".$type."'".(isset($langId) ? " AND `lang_id` = ".$langId : '') : " `lang_id` = ".$langId)    : '')
            .' ORDER BY `type`, `lang_id`';
        $objMail = $objDatabase->Execute($query);

        if ($objMail && !$objMail->EOF) {
            while (!$objMail->EOF) {
                foreach ($objMail->fields as $attribute => $value) {
                    $this->arrLoadedMails[$objMail->fields['type']][$objMail->fields['lang_id']][$attribute] = $value;
                }
                $objMail->MoveNext();
            }
            $this->first();
            return true;
        }
        $this->clean();
        return false;
    }


    function store()
    {
        global $objDatabase, $_CORELANG;

        if (!$this->validateType() || !$this->validateSenderMail() || !$this->validateSenderName() || !$this->validateFormat() || !$this->validateBody()) {
            return false;
        }

        if (isset($this->arrLoadedMails[$this->type][$this->lang_id])) {
            if ($objDatabase->Execute("
                UPDATE `".DBPREFIX."access_user_mail`
                SET
                    `sender_mail` = '".addslashes($this->sender_mail)."',
                    `sender_name` = '".addslashes($this->sender_name)."',
                    `subject` = '".addslashes($this->subject)."',
                    `format` = '".$this->format."',
                    `body_text` = '".addslashes($this->body_text)."',
                    `body_html` = '".addslashes($this->body_html)."'
                WHERE
                    `type` = '".$this->type."'
                AND
                    `lang_id` = ".$this->lang_id
            ) === false) {
                $this->error_msg[] = $_CORELANG['TXT_ACCESS_MAIL_UPDATED_FAILED'];
                return false;
            }
        } else {
            if ($objDatabase->Execute("
                INSERT INTO `".DBPREFIX."access_user_mail` (
                    `type`,
                    `lang_id`,
                    `sender_mail`,
                    `sender_name`,
                    `subject`,
                    `format`,
                    `body_text`,
                    `body_html`
                ) VALUES (
                    '".$this->type."',
                    ".$this->lang_id.",
                    '".addslashes($this->sender_mail)."',
                    '".addslashes($this->sender_name)."',
                    '".addslashes($this->subject)."',
                    '".$this->format."',
                    '".addslashes($this->body_text)."',
                    '".addslashes($this->body_html)."'
                )"
            ) === false) {
                $this->error_msg[] = $_CORELANG['TXT_ACCESS_MAIL_ADDED_FAILED'];
                return false;
            }
        }

        return true;
    }

    function delete()
    {
        global $_CORELANG, $objDatabase;

        if ($this->isRemovable() && $objDatabase->Execute("DELETE FROM `".DBPREFIX."access_user_mail` WHERE `type` = '".$this->type."' AND `lang_id` = ".$this->lang_id) !== false) {
            $this->loadMails();
            return true;
        } else {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_EMAIL_DEL_FAILED'];
            return false;
        }
    }

    function isRemovable()
    {
        return (bool)$this->lang_id;
    }

    /**
     * Load first mail
     *
     */
    function first()
    {
        if (reset($this->arrLoadedMails) === false || !$this->firstLanguage()) {
            $this->EOF = true;
        } else {
            $this->EOF = false;
        }
    }

    /**
     * Load next mail
     *
     */
    function next()
    {
        if (next($this->arrLoadedMails) === false || !$this->firstLanguage()) {
            $this->EOF = true;
        }
    }

    function firstLanguage()
    {
        if (reset($this->arrLoadedMails[key($this->arrLoadedMails)]) === false ||
        !$this->load(key($this->arrLoadedMails), key($this->arrLoadedMails[key($this->arrLoadedMails)]))) {
            return !$this->languageEOF = true;
        } else {
            return !$this->languageEOF = false;
        }
    }

    function nextLanguage()
    {
        if (next($this->arrLoadedMails[$this->type]) === false || !$this->load($this->type, key($this->arrLoadedMails[$this->type]))) {
            $this->languageEOF = true;
        }
    }

    function validateType()
    {
        global $_CORELANG;

        if (isset($this->arrAvailableTypes[$this->type])) {
            return true;
        } else {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_UNKNOWN_TYPE_SPECIFIED'];
            return false;
        }
    }

    function validateSenderMail()
    {
        global $_CORELANG;

        $objValidator = new FWValidator();

        if ($objValidator->isEmail($this->sender_mail)) {
            return true;
        } else {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_INVALID_SENDER_ADDRESS'];
            return false;
        }
    }

    function validateSenderName()
    {
        global $_CORELANG;

        if (empty($this->sender_name)) {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_SET_SENDER_NAME'];
            return false;
        } else {
            return true;
        }
    }

    function validateFormat()
    {
        global $_CORELANG;

        if (in_array($this->format, array_keys($this->arrAvailableFormats))) {
            return true;
        } else {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_UNKOWN_FORMAT_SPECIFIED'];
            return false;
        }
    }

    function validateBody()
    {
        $status = true;

        $arrFormat =
            ($this->format == 'multipart'
                ? array('text', 'html') : array($this->format)
            );
        foreach ($arrFormat as $format) {
            if (!$this->isValidBody($format)) {
                $status = false;
            }
        }

        return $status;
    }



    function isValidBody($format)
    {
        global $_CORELANG;

        $arrPlaceholders = array();
        $formatUC = strtoupper($format);
        if (preg_match_all('/\[\[[0-9A-Za-z_-]+\]\]/', $this->{'body_'.$format}, $arrPlaceholders)) {
            $arrMissedPlaceholders = array_diff($this->arrAvailableTypes[$this->type]['required'], $arrPlaceholders[0]);
            if (count($arrMissedPlaceholders) > 1) {
                $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_REQUIRED_PLACEHOLDERS_IN_'.$formatUC], implode(', ', $arrMissedPlaceholders));
                return false;
            } elseif (count($arrMissedPlaceholders) == 1) {
                $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_REQUIRED_PLACEHOLDER_IN_'.$formatUC], current($arrMissedPlaceholders));
                return false;
            } else  {
                return true;
            }
        } elseif (count($this->arrAvailableTypes[$this->type]['required']) > 1) {
            $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_REQUIRED_PLACEHOLDERS_IN_'.$formatUC], implode(', ', $this->arrAvailableTypes[$this->type]['required']));
            return false;
        } elseif (count($this->arrAvailableTypes[$this->type]['required']) == 1) {
            $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_REQUIRED_PLACEHOLDER_IN_'.$formatUC], current($this->arrAvailableTypes[$this->type]['required']));
            return false;
        } else {
            return true;
        }
    }




    function setType($type)
    {
        $this->type = $type;
    }

    function setLangId($langId)
    {
        $this->lang_id = $langId;
    }

    function setSenderMail($senderMail)
    {
        $this->sender_mail = $senderMail;
    }

    function setSenderName($senderName)
    {
        $this->sender_name = $senderName;
    }

    function setSubject($subject)
    {
        $this->subject = $subject;
    }

    function setFormat($format)
    {
        $this->format = $format;
    }

    function setBodyText($bodyText)
    {
        $this->body_text = $bodyText;
    }

    function setBodyHtml($bodyHtml)
    {
        $this->body_html = $bodyHtml;
    }

    function getErrorMsg()
    {
        return $this->error_msg;
    }

    function getType()
    {
        return $this->type;
    }

    function getTypeDescription()
    {
        global $_CORELANG;

        return $_CORELANG[$this->arrAvailableTypes[$this->type]['title']];
    }

    function getLangId()
    {
        return $this->lang_id;
    }

    function getSubject()
    {
        return $this->subject;
    }

    function getFormat()
    {
        return $this->format;
    }

    function getSenderMail()
    {
        return $this->sender_mail;
    }

    function getSenderName()
    {
        return $this->sender_name;
    }

    function getBodyText()
    {
        return $this->body_text;
    }

    function getBodyHtml()
    {
        return $this->body_html;
    }

    function getPlaceholders()
    {
        $arrPlaceholders = array_map(
            function ($langVar) {
                global $_CORELANG;

                return $_CORELANG[$langVar];
            },
            $this->arrAvailableTypes[$this->type]['placeholders']
        );

        if ($this->type == 'reg_confirm') {
            $objFWUser = \FWUser::getFWUserObject();
            $objFWUser->objUser->objAttribute->first();
            while (!$objFWUser->objUser->objAttribute->EOF) {
                $objAttribute = $objFWUser->objUser->objAttribute->getById(
                    $objFWUser->objUser->objAttribute->getId()
                );

                $name = '[[USER_' . strtoupper($objFWUser->objUser->objAttribute->getId()) . ']]';
                $arrPlaceholders[$name] = $objFWUser->objUser->objAttribute->getName(BACKEND_LANG_ID);
                $objFWUser->objUser->objAttribute->next();
            }
        }

        return $arrPlaceholders;
    }

    function getFormats()
    {
        return array_map(
            function ($langVar) {
                global $_CORELANG;

                return $_CORELANG[$langVar];
            },
            $this->arrAvailableFormats
        );
    }
}
