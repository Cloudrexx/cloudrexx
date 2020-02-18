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
 * Class newsletter library
 *
 * Newsletter module class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_newsletter
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Newsletter\Controller;

/**
 * Class newsletter library
 *
 * Newsletter module class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_newsletter
 * @todo        Edit PHP DocBlocks!
 */
class NewsletterLib
{
    const USER_TYPE_NEWSLETTER = 'newsletter';
    const USER_TYPE_ACCESS = 'access';
    const USER_TYPE_CORE = 'core';
    const USER_TYPE_CRM = 'crm';

    public $_arrRecipientTitles = null;

    /**
     * List of present Lists
     * @see     getListIdByName()
     * @var     array
     * @static
     */
    private static $arrLists = null;

    /**
     * Returns an array of all Newsletter lists
     * @param   boolean   Set to true, if only visible newsletter-lists shall be returned. Defaults to FALSE
     * @param   boolean   Set to true, if the returned array shall contain extended information about the newsletter-lists. Defaults to FALSE
     * @return  array     The array of Newsletter lists
     * @internal  Made public for mediadir and access module -- RK / TD
     */
    static public function getLists($excludeHiddenLists=false, $extendedInfo=false)
    {
        global $objDatabase;

        $arrLists = array();
        $objList = $objDatabase->Execute('
            SELECT
                    id,
                    status,
                    name,
                    notification_email
            FROM    '.DBPREFIX.'module_newsletter_category'
            .($excludeHiddenLists
                ? ' WHERE status=1'
                : '').'
            ORDER BY name');
        if ($objList !== false) {
            while (!$objList->EOF) {
                $mailId   = 0;
                $mailSend = 0;
                $mailName = '';
                $recipients = 0;

                if ($extendedInfo) {
                    $objMail = $objDatabase->SelectLimit('
                        SELECT
                                   tblNewsletter.id,
                                   tblNewsletter.subject,
                                   tblNewsletter.date_sent
                        FROM       '.DBPREFIX.'module_newsletter AS tblNewsletter
                        INNER JOIN '.DBPREFIX.'module_newsletter_rel_cat_news AS tblRel
                                ON tblRel.newsletter = tblNewsletter.id
                        WHERE      tblRel.category='.$objList->fields['id'].'
                        ORDER BY   date_sent DESC', 1);
                    if ($objMail !== false && $objMail->RecordCount() == 1) {
                        $mailSend = $objMail->fields['date_sent'];
                        $mailId   = ($mailSend > 0)
                                        ? $objMail->fields['id']
                                        : 0;
                        $mailName = $objMail->fields['subject'];
                    }

                    $recipients = self::getListRecipientCount($objList->fields['id']);
                }

                $arrLists[$objList->fields['id']] = array(
                    'status'            => $objList->fields['status'],
                    'name'              => $objList->fields['name'],
                    'recipients'        => $recipients,
                    'mail_sent'         => $mailSend,
                    'mail_name'         => $mailName,
                    'mail_id'           => $mailId,
                    'notification_email'=> $objList->fields['notification_email'],
                );
                $objList->MoveNext();
            }
        }
        return $arrLists;
    }

    /**
     * Parses the consent icons
     * @param string $source Either "backend", "api", "opt-in", "undefined"
     * @param string $consent Date parseable by DateTime or empty string
     * @return string HTML content
     */
    public static function parseConsentView($source, $consent) {
        global $_ARRAYLANG;

        if ($source == 'undefined') {
            // show empty icon
            $consentValue = '<img src="/core/Core/View/Media/icons/pixel.gif" height="13" width="13" />';
            return $consentValue;
        } else if (empty($consent)) {
            // show orange icon with source as tooltip
            $langVarName = 'TXT_NEWSLETTER_CONSENT_SOURCE_';
            $langVarName .= str_replace('-', '_', strtoupper($source));
            $consentValue = $_ARRAYLANG[$langVarName];
            $consentValue = '<img src="/core/Core/View/Media/icons/led_orange.gif" title="' . $consentValue . '" />';
            return $consentValue;
        }
        // show green icon with date as tooltip
        $consentValue = sprintf(
            $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_SOURCE_OPT_IN'],
            static::getUserDateTime($consent)
        );
        $consentValue = '<img src="/core/Core/View/Media/icons/led_green.gif" title="' . $consentValue . '" />';
        return $consentValue;
    }

    /**
     * Get a user dateTime in H:i:s d.m.Y format from db data
     *
     * @param string $userDateTime DateTime from a db
     * @return string Return a formatted dateTime as string
     */
    protected static function getUserDateTime($userDateTime)
    {
        $cx                  = \Cx\Core\Core\Controller\Cx::instanciate();
        $dateTime            = $cx->getComponent('DateTime');
        $createDateTimeForDb = $dateTime->createDateTimeForDb($userDateTime);
        $db2User             = $dateTime->db2user($createDateTimeForDb);

        return $db2User->format('H:i:s d.m.Y');
    }

    /**
     * Get the URL to the page to unsubscribe
     */
    public function GetUnsubscribeURL($code, $email, $type = self::USER_TYPE_NEWSLETTER, $htmlTag = true)
    {
        global $_ARRAYLANG;

        if (
            $type == self::USER_TYPE_CORE ||
            $type == self::USER_TYPE_CRM
        ) {
            // recipients that will receive the newsletter through the selection of their user group don't have a profile
            return '';
        }

        $cmd = '';
        switch ($type) {
            case self::USER_TYPE_ACCESS:
                $cmd = 'profile';
                break;

            case self::USER_TYPE_NEWSLETTER:
            default:
                $cmd = 'unsubscribe';
                break;
        }

        $unsubscribeUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
            'Newsletter',
            $cmd,
            $this->getUsersPreferredLanguageId(
                $email,
                $type
            ),
            array(
                'code' => $code,
                'mail' => urlencode($email),
            )
        );

        if ($htmlTag) {
            return '<a href="'.$unsubscribeUrl->toString().'">'.$_ARRAYLANG['TXT_UNSUBSCRIBE'].'</a>';
        } else {
            return $unsubscribeUrl->toString();
        }
    }


    /**
     * Return link to the profile of a user
     */
    function GetProfileURL($code, $email, $type = self::USER_TYPE_NEWSLETTER, $htmlTag = true)
    {
        global $_ARRAYLANG;

        if (
            $type == self::USER_TYPE_CORE ||
            $type == self::USER_TYPE_CRM
        ) {
            // recipients that will receive the newsletter through the selection of their user group don't have a profile
            return '';
        }

        $profileUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
            'Newsletter',
            'profile',
            $this->getUsersPreferredLanguageId(
                $email,
                $type
            ),
            array(
                'code' => $code,
                'mail' => urlencode($email),
            )
        );
        if ($htmlTag) {
            return '<a href="'.$profileUrl->toString().'">'.$_ARRAYLANG['TXT_EDIT_PROFILE'].'</a>';
        } else {
            return $profileUrl->toString();
        }
    }

    /**
     * Returns the Language ID for a newsletter user
     *
     * If the user's preferred language can not be found, the default language
     * ID is returned.
     * For crm email addresses this will be the system default language by now
     * @param string $email E-mail address of the user
     * @param string $type User type (see constants)
     * @return integer Language ID
     */
    public function getUsersPreferredLanguageId($email, $type) {
        global $objDatabase;

        $userLanguage = \FWLanguage::getDefaultLangId(); // used also for crm
        switch ($type) {
            case self::USER_TYPE_CORE:
            case self::USER_TYPE_ACCESS:
                // get user's language by email
                $user = \FWUser::getFWUserObject()->objUser->getUsers(
                    array(
                        'email' => $email,
                    )
                );
                if ($user && $user->getFrontendLanguage()) {
                    $userLanguage = $user->getFrontendLanguage();
                }
                break;

            case self::USER_TYPE_NEWSLETTER:
            default:
                // get user's language by email
                $query = '
                    SELECT
                        `language`
                    FROM
                        `' . DBPREFIX . 'module_newsletter_user`
                    WHERE
                        `email` = \'' . contrexx_raw2db($email) . '\'
                ';
                $result = $objDatabase->Execute($query);
                if (!empty($result->fields['language'])) {
                    $userLanguage = $result->fields['language'];
                }
                break;
        }
        return $userLanguage;
    }

    /**
     * Returns the Language ID for a newsletter recipient
     *
     * If the recipients's preferred language can not be found, the default
     * language ID is returned.
     *
     * @param integer $id   id of the recipient or the linked access/crm user
     * @param string  $type User type (see constants)
     * @return integer Language ID
     */
    public function getRecipientLocaleIdByRecipientId($id, $type) {
        global $objDatabase;

        $userLanguage = \FWLanguage::getDefaultLangId();
        switch ($type) {
            case self::USER_TYPE_CORE:
            case self::USER_TYPE_ACCESS:
                // get user's language by email
                $user = \FWUser::getFWUserObject()->objUser->getUsers(
                    array('id' => $id)
                );
                if ($user && $user->getFrontendLanguage()) {
                    $userLanguage = $user->getFrontendLanguage();
                }
                break;
            case self::USER_TYPE_CRM:
                $crmUser = new \Cx\Modules\Crm\Model\Entity\CrmContact();
                $crmUser->load($id);

                if ($crmUser && $crmUser->contact_language) {
                    $userLanguage = $crmUser->contact_language;
                }
                break;
            case self::USER_TYPE_NEWSLETTER:
            default:
                // get user's language by email
                $query = '
                    SELECT
                        `language`
                    FROM
                        `' . DBPREFIX . 'module_newsletter_user`
                    WHERE
                        `id` = \'' . contrexx_raw2db($id) . '\'
                ';
                $result = $objDatabase->Execute($query);
                if (!empty($result->fields['language'])) {
                    $userLanguage = $result->fields['language'];
                }
                break;
        }
        return $userLanguage;
    }

    /**
     * Return the count of recipients of a list
     *
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       int $id
     * @return      int
     */
    static protected function getListRecipientCount($id)
    {
        global $objDatabase;

        // Ignore the code analyzer warning.  There's plenty of arguments
        $query = sprintf('
            SELECT COUNT(*) AS `recipientCount`
              FROM (
                SELECT `email`
                  FROM `%1$smodule_newsletter_user` AS `nu`
                  LEFT JOIN `%1$smodule_newsletter_rel_user_cat` AS `rc`
                    ON `rc`.`user` = `nu`.`id`
                 WHERE `rc`.`category`=%2$s
                    AND (
                        nu.source != "opt-in"
                        OR (
                            nu.source = "opt-in"
                            AND nu.consent IS NOT NULL
                        )
                    )
                 UNION DISTINCT
                SELECT `email`
                  FROM `%1$saccess_users` AS `cu`
                  LEFT JOIN `%1$smodule_newsletter_access_user` AS `cnu`
                    ON `cnu`.`accessUserID`=`cu`.`id`
                  LEFT JOIN `%1$smodule_newsletter_rel_cat_news` AS `crn`
                    ON `cnu`.`newsletterCategoryID`=`crn`.`category`
                 WHERE `cnu`.`newsletterCategoryID`=%2$s
              ) AS `subquery`',
            DBPREFIX, $id
        );
        $data = $objDatabase->Execute($query);
        return $data->fields['recipientCount'];
    }


    /**
     * Return the access user groups
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       string $orderBy
     * @return      array
     */
    protected function _getGroups($orderBy="`group_name`")
    {
        global $objDatabase;

        $query = sprintf('
            SELECT `group_id`   AS `id`,
                   `group_name` AS `name`
              FROM `%1$saccess_user_groups`
             WHERE `is_active`=1
             ORDER BY %2$s',
            DBPREFIX, $orderBy
        );
        $list = $objDatabase->Execute($query);
        $groups = array();
        while ($list !== false && !$list->EOF) {
            $groups[$list->fields['id']] = $list->fields['name'];
            $list->moveNext();
        }
        return $groups;
    }

    /**
     * Add a recipient with the given parameter values and subscribe to the
     * lists with their ID present in $arrLists
     * @param   string    $email      The e-mail address
     * @param   string    $uri        The website URL
     * @param   string    $sex        The sex
     * @param   string    $title      The title
     * @param   string    $lastname   The last name
     * @param   string    $firstname  The first name
     * @param   string    $company    The company name
     * @param   string    $address    The address address
     * @param   string    $zip        The ZIP
     * @param   string    $city       The city
     * @param   string    $country    The country ID
     * @param   string    $phoneOffice The phone number
     * @param   string    $birthday   The birth date
     * @param   string    $status     The active status
     * @param   array     $arrLists   The array of subscribed list IDs
     * @param   integer   $language   The preferred language ID
     * @return  boolean               True on success, false otherwise
     * @static
     */
    static function _addRecipient(
        $email, $uri, $sex, $salutation, $title, $lastname, $firstname, $position, $company, $industry_sector,
        $address, $zip, $city, $country, $phone_office, $phone_private, $phone_mobile, $fax, $notes, $birthday, $status,
        $arrLists, $language, $source
    ) {
        global $objDatabase;

        if (!$objDatabase->Execute("
            INSERT INTO ".DBPREFIX."module_newsletter_user (
                `code`, `email`, `uri`, `sex`, `salutation`, `title`,
                `lastname`, `firstname`, `position`, `company`, `industry_sector`,
                `address`, `zip`, `city`, `country_id`, `phone_office`, `phone_private`,
                `phone_mobile`, `fax`, `notes`, `birthday`, `status`,
                `emaildate`, `language`, `source`
            ) VALUES (
                '".self::_emailCode()."',
                '".contrexx_addslashes($email)."',
                '".contrexx_addslashes($uri)."',
                ".(empty($sex) ? 'NULL' : "'".contrexx_addslashes($sex)."'").",
                ".intval($salutation).",
                '".contrexx_addslashes($title)."',
                '".contrexx_addslashes($lastname)."',
                '".contrexx_addslashes($firstname)."',
                '".contrexx_addslashes($position)."',
                '".contrexx_addslashes($company)."',
                '".contrexx_addslashes($industry_sector)."',
                '".contrexx_addslashes($address)."',
                '".contrexx_addslashes($zip)."',
                '".contrexx_addslashes($city)."',
                '".intval($country)."',
                '".contrexx_addslashes($phone_office)."',
                '".contrexx_addslashes($phone_private)."',
                '".contrexx_addslashes($phone_mobile)."',
                '".contrexx_addslashes($fax)."',
                '".contrexx_addslashes($notes)."',
                '".contrexx_addslashes($birthday)."',
                '".intval($status)."',
                '".time()."',
                '".intval($language)."',
                '". $source ."'
            )")
        ) {
            return false;
        }
        return static::_setRecipientLists(
            $objDatabase->Insert_ID(),
            $arrLists,
            $source
        );
    }


    function _updateRecipient(
        $recipientAttributeStatus, $id, $email, $uri, $sex, $salutation, $title, $lastname, $firstname, $position, $company, $industry_sector,
        $address, $zip, $city, $country, $phone_office, $phone_private, $phone_mobile, $fax, $notes, $birthday, $status,
        $arrLists, $language, $source, $setTime = false
    ) {
        global $objDatabase;

        $query = \SQL::update('module_newsletter_user', array(
            'email' => contrexx_addslashes($email),
            'uri' => array(
                'val'       => contrexx_addslashes($uri),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_website']) ||
                               !$recipientAttributeStatus['recipient_website']['active']
            ),
            'sex' => array(
                'val'       => contrexx_addslashes($sex),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_sex']) ||
                               !$recipientAttributeStatus['recipient_sex']['active']
            ),
            'salutation' => array(
                'val'       => contrexx_addslashes($salutation),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_salutation']) ||
                               !$recipientAttributeStatus['recipient_salutation']['active']
            ),
            'title' => array(
                'val'       => contrexx_addslashes($title),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_title']) ||
                               !$recipientAttributeStatus['recipient_title']['active']
            ),
            'lastname' => array(
                'val'       => contrexx_addslashes($lastname),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_lastname']) ||
                               !$recipientAttributeStatus['recipient_lastname']['active']
            ),
            'firstname' => array(
                'val'       => contrexx_addslashes($firstname),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_firstname']) ||
                               !$recipientAttributeStatus['recipient_firstname']['active']
            ),
            'position' => array(
                'val'       => contrexx_addslashes($position),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_position']) ||
                               !$recipientAttributeStatus['recipient_position']['active']
            ),
            'company' => array(
                'val'       => contrexx_addslashes($company),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_company']) ||
                               !$recipientAttributeStatus['recipient_company']['active']
            ),
            'industry_sector' => array(
                'val'       => contrexx_addslashes($industry_sector),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_industry']) ||
                               !$recipientAttributeStatus['recipient_industry']['active']
            ),
            'address' => array(
                'val'       => contrexx_addslashes($address), 
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_address']) ||
                               !$recipientAttributeStatus['recipient_address']['active']
            ),
            'zip' => array(
                'val'       => contrexx_addslashes($zip),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_zip']) ||
                               !$recipientAttributeStatus['recipient_zip']['active']
            ),
            'city' => array(
                'val'       => contrexx_addslashes($city),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_city']) ||
                               !$recipientAttributeStatus['recipient_city']['active']
            ),
            'country_id' => array(
                'val'       => contrexx_addslashes($country),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_country']) ||
                               !$recipientAttributeStatus['recipient_country']['active']
            ),
            'phone_office' => array(
                'val'       => contrexx_addslashes($phone_office),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_phone']) ||
                               !$recipientAttributeStatus['recipient_phone']['active']
            ),
            'phone_private' => array(
                'val'       => contrexx_addslashes($phone_private),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_private']) ||
                               !$recipientAttributeStatus['recipient_private']['active']
            ),
            'phone_mobile' => array(
                'val'       => contrexx_addslashes($phone_mobile),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_mobile']) ||
                               !$recipientAttributeStatus['recipient_mobile']['active']
            ),
            'fax' => array(
                'val'       => contrexx_addslashes($fax),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_fax']) ||
                               !$recipientAttributeStatus['recipient_fax']['active']
            ),
            'notes' => (!$notes ? '' : contrexx_addslashes($notes)),
            'birthday' => array(
                'val'       => contrexx_addslashes($birthday),
                'omitEmpty' => !isset($recipientAttributeStatus['recipient_birthday']) ||
                               !$recipientAttributeStatus['recipient_birthday']['active']
            ),
            'status' => intval($status),
            'language' => intval($language)
        ))."WHERE id=".$id;

        if (!$objDatabase->Execute($query)) {
            return false;
        }
        return static::_setRecipientLists($id, $arrLists, $source, $setTime);
    }


    /**
     * Add the recipient with the given ID to all the lists with their IDs
     * present in the array
     * @param   integer   $recipientId      The recipient ID
     * @param   array     $arrLists         The array of list IDs to subscribe
     * @param string $source One of "opt-in", "backend", "api"
     * @param boolean $setTime (optional) if set to true, consent is stored as confirmed
     * @return  boolean                     True on success, false otherwise
     * @static
     */
    static function _setRecipientLists($recipientId, $arrLists, $source, $setTime = false)
    {
        global $objDatabase;

        // delete
        if ($objDatabase->Execute('
            DELETE FROM
                `' . DBPREFIX . 'module_newsletter_rel_user_cat`
            WHERE
                `user` = ' . $recipientId . ' AND
                `category` NOT IN (' . implode(', ', $arrLists) . ')
        ') === false) {
            return false;
        }

        // insert missing relations
        $currentTime = 'NULL';
        if ($setTime) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $dateTime = $cx->getComponent('DateTime')->createDateTimeForDb('');
            $currentTime = '"' . $dateTime->format('Y-m-d H:i:s') . '"';
        }
        if ($objDatabase->Execute('
            INSERT IGNORE INTO
                `' . DBPREFIX . 'module_newsletter_rel_user_cat`
                (
                    `user`,
                    `category`,
                    `source`,
                    `consent`
                )
            SELECT
                ' . $recipientId . ' AS `user`,
                `id` AS `category`,
                "' . $source . '" AS `source`,
                ' . $currentTime . ' AS `consent`
            FROM
                `' . DBPREFIX . 'module_newsletter_category`
            WHERE
                `id` IN (' . implode(', ', $arrLists) . ')
        ') === false) {
            return false;
        }
        return true;
    }

    static function _emailCode()
    {
        $ReturnVar = '';
        $pool =
            "qwertzupasdfghkyxcvbnm".
            "23456789".
            "WERTZUPLKJHGFDSAYXCVBNM";
        srand((double)microtime()*1000000);
        for ($index = 0; $index < 10; ++$index) {
            $ReturnVar .= substr($pool, (rand()%(strlen($pool))), 1);
        }
        return $ReturnVar;
    }


    function _isUniqueRecipientEmail($email, $recipientId, $copy = false)
    {
        global $objDatabase;

        //reset the $recipientId on copy function
        $recipientId = $copy ? 0 : $recipientId;

        $objRecipient = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_user WHERE email='".contrexx_addslashes($email)."' AND id!=".$recipientId, 1);
        if ($objRecipient !== false && $objRecipient->RecordCount() == 0) {
            return true;
        }
        return false;
    }


    function _getSettings()
    {
        global $objDatabase;

        static $arrSettings = array();
        if (count($arrSettings) == 0) {
            $objSettings = $objDatabase->Execute("SELECT setid, setname, setvalue, status FROM ".DBPREFIX."module_newsletter_settings");
            if ($objSettings !== false) {
                while (!$objSettings->EOF) {
                    $arrSettings[$objSettings->fields['setname']] = array(
                        'setid'         => $objSettings->fields['setid'],
                        'setvalue'      => $objSettings->fields['setvalue'],
                        'status'        => $objSettings->fields['status']
                    );

                    $objSettings->MoveNext();
                }
            }
        }
        return $arrSettings;
    }


    /**
     * Return a setting from the settings table
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @param       string $name
     * @return      string | bool
     */
    protected function getSetting($name)
    {
        global $objDatabase;

        $query = "
            SELECT setvalue
              FROM ".DBPREFIX."module_newsletter_settings
             WHERE setvalue='".$name."'";
        $result = $objDatabase->Execute($query);
        return $result !== false ? $result->fields['setvalue'] : false;
    }


    function _getHTML($onlyId=false)
    {
        global $objDatabase, $_ARRAYLANG;

        $html = '';
        if ($onlyId) {
            $objResult = true;
        } else {
            $objResult = $objDatabase->Execute("SELECT id, name FROM ".DBPREFIX."module_newsletter_category WHERE status='1' ORDER BY name");
        }

        if ($objResult !== false) {
            $html .= '<form name="newsletter" action="'.CONTREXX_DIRECTORY_INDEX.'?section=Newsletter&amp;act=subscribe" method="post">'."\n";

            if ($onlyId || $objResult->RecordCount() == 1) {
                $html .= '<input type="hidden" name="list['.($onlyId ? $onlyId : $objResult->fields['id']).']" value="1" />'."\n";
            } elseif ($objResult->RecordCount() == 0) {
                $this->_objTpl->setVariable('TXT_NO_CATEGORIES', $_ARRAYLANG['TXT_NO_CATEGORIES']);
            } else {
                while (!$objResult->EOF) {
                    $html .= '<input type="checkbox" name="list['.$objResult->fields['id'].']" id="list_'.$objResult->fields['id'].'" value="1" /> <label for="list_'.$objResult->fields['id'].'">'.htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)."</label><br />\n";
                    $objResult->MoveNext();
                }

                $html .= "<br />";
            }

            $html .= '<input type="text" onfocus="this.value=\'\'" name="email" value="'.$_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'].'" style="width: 165px;" maxlength="255" /><br /><br />'."\n";
            $html .= '<input type="submit" name="recipient_save" value="'.$_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIBE'].'" />'."\n";
            $html .= "</form>\n";
        }

        return $html;
    }


    /**
     * Returns an array of List IDs to which the recipient with the given
     * ID is subscribed
     *
     * On failure, or if the ID is invalid or empty, the empty array
     * is returned.
     * @param   integer   $recipientId        The recipient ID
     * @param   boolean   $onlyActiveLists    Return all lists if false,
     *                                        all lists otherwise.
     *                                        Defaults to true
     * @return  array                         The array of subscribed List IDs
     *                                        on success, the empty array
     *                                        otherwise
     * @static
     */
    static function _getAssociatedListsOfRecipient($recipientId, $onlyActiveLists=true)
    {
        global $objDatabase;

        $recipientId = intval($recipientId);
        if (empty($recipientId)) return array();

        $objList = $objDatabase->Execute("
            SELECT tblR.`category`
              FROM `".DBPREFIX."module_newsletter_rel_user_cat` AS tblR".
            ($onlyActiveLists
              ? ' INNER JOIN `'.DBPREFIX.'module_newsletter_category` AS tblC ON tblC.`id` = tblR.`category`' : '').'
             WHERE tblR.`user`='.$recipientId.
            ($onlyActiveLists
              ? ' AND tblC.`status` != 0' : ''));
        $arrLists = array();
        while ($objList && !$objList->EOF) {
            $arrLists[] = $objList->fields['category'];
            $objList->MoveNext();
        }
        return $arrLists;
    }


// TODO: Merge with user_profile_attribute_title
    function _getRecipientTitleMenu($selected=0, $attrs='')
    {
        global $_ARRAYLANG;

        $menu = '<select'.(!empty($attrs) ? ' '.$attrs : '').">\n";

        $arrTitles = $this->_getRecipientTitles();

// TODO: add language variable
        $menu .= '<option value="0"'.($selected == 0 ? ' selected="selected"' : '').'>'.$_ARRAYLANG['TXT_NEWSLETTER_UNKNOWN']."</option>\n";
        foreach ($arrTitles as $id => $title) {
            if (!empty($id)) {
                $menu .= '<option value="'.$id.'"'.($selected == $id ? ' selected="selected"' : '').'>'.htmlentities($title, ENT_QUOTES, CONTREXX_CHARSET)."</option>\n";
            }
        }
        $menu .= "</select>\n";

        return $menu;
    }


// TODO: Merge with user_profile_attribute_title
    function _getRecipientTitles()
    {
        if (!is_array($this->_arrRecipientTitles)) {
            $this->_initRecipientTitles();
        }
        return $this->_arrRecipientTitles;
    }


// TODO: Merge with user_profile_attribute_title
    function _addRecipientTitle($title)
    {
        global $objDatabase;

        $recipientTitleId = 0;
        if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_newsletter_user_title` (`title`) VALUES ('".addslashes($title)."')") !== false) {
            $recipientTitleId = $objDatabase->Insert_ID();
        }
        $this->_initRecipientTitles();
        return $recipientTitleId;
    }


// TODO: Merge with user_profile_attribute_title
    function _initRecipientTitles()
    {
        global $objDatabase;

        $this->_arrRecipientTitles = array(0 => '');

        $objTitle = $objDatabase->Execute("SELECT `id`, `title` FROM `".DBPREFIX."module_newsletter_user_title`");
        if ($objTitle !== false) {
            while (!$objTitle->EOF) {
                $this->_arrRecipientTitles[$objTitle->fields['id']] = $objTitle->fields['title'];
                $objTitle->MoveNext();
            }
        }
    }


    function _deleteRecipient($id)
    {
        global $objDatabase;

// TODO: refactor to use a proper single SQL DELETE statement
        $objUser = $objDatabase->SelectLimit('SELECT `email` FROM `'.DBPREFIX.'module_newsletter_user` WHERE id='.$id, 1);
        if ($objUser !== false && $objUser->RecordCount() == 1) {
            if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_user WHERE id=".$id) !== false) {
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE user=".$id);
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_tmp_sending WHERE `email` = '".addslashes($objUser->fields['email'])."'");
                return true;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Create and select the date dropdowns for choosing the birthday
     *
     * @param (array|string) $birthday
     */
    function _createDatesDropdown($birthday = '') {
        if (!empty($birthday)) {
            $birthday = (is_array($birthday)) ? $birthday : explode('-', $birthday);
            $day = !empty($birthday[0]) ? $birthday[0] : '';
            $month = !empty($birthday[1]) ? $birthday[1] : '';
            $year = !empty($birthday[2]) ? $birthday[2] : '';
        } else {
            $day = '';
            $month = '';
            $year = '';
        }

        for($i=1;$i<=31;$i++) {
            $selected = ($day == str_pad($i,2,'0',STR_PAD_LEFT)) ? 'selected="selected"' : '' ;
            $this->_objTpl->setVariable(array(
                'USERS_BIRTHDAY_DAY' => str_pad($i,2,'0', STR_PAD_LEFT),
                'USERS_BIRTHDAY_DAY_NAME' => $i,
                'SELECTED_DAY' => $selected
            ));
            $this->_objTpl->parse('birthday_day');
        }

        for($i=1;$i<=12;$i++) {
            $selected = ($month == str_pad($i,2,'0',STR_PAD_LEFT)) ? 'selected="selected"' : '' ;
            $this->_objTpl->setVariable(array(
                'USERS_BIRTHDAY_MONTH' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'USERS_BIRTHDAY_MONTH_NAME' => $this->months[$i],
                'SELECTED_MONTH' => $selected
            ));
            $this->_objTpl->parse('birthday_month');
        }

        for($i=date("Y");$i>=1900;$i--) {
            $selected = ($year == $i) ? 'selected="selected"' : '' ;
            $this->_objTpl->setVariable(array(
                'USERS_BIRTHDAY_YEAR' => $i,
                'SELECTED_YEAR' => $selected
            ));
            $this->_objTpl->parse('birthday_year');
        }
    }

    protected function getCountryMenu($selectedCountry = 0, $mantatory = false)
    {
        global $_ARRAYLANG;
        $menu  = '<select name="newsletter_country_id" size="1">';
        $menu .= "<option value='0'>".(($mantatory) ? $_ARRAYLANG['TXT_NEWSLETTER_PLEASE_SELECT'] : $_ARRAYLANG['TXT_NEWSLETTER_NOT_SPECIFIED'])."</option>";
        $menu .= \Cx\Core\Country\Controller\Country::getMenuoptions($selectedCountry);
        $menu .= '</select>';
        return $menu;
    }


    /**
     * Returns the ID of the list specified by its name
     *
     * Used for importing/setting up User-List relations
     * @param   string  $list_name  The List name
     * @return  integer             The matching list ID if found,
     *                              null otherwise
     */
    static function getListIdByName($list_name)
    {
        if (!isset(self::$arrLists)) {
            self::$arrLists = self::getLists(false, true);
        }
        foreach (self::$arrLists as $id => $arrList) {
            if ($list_name == $arrList['name']) return $id;
        }
        return null;
    }

    /**
     * Get newsletter list name by given id
     *
     * @param integer $listId List id
     *
     * @return mixed string or null
     */
    public function getListNameById($listId)
    {
        if (!isset(self::$arrLists)) {
            self::$arrLists = self::getLists(false, true);
        }
        if (isset(self::$arrLists[$listId])) {
            return self::$arrLists[$listId]['name'];
        }
        return null;
    }

    /**
     * Add a list with the given name and status
     *
     * Upon successfully adding a new list, resets the $arrLists class
     * variable to null.
     * @param   string    $listName     The new list name
     * @param   boolean   $listStatus   The new list status,
     *                                  defaults to false for inactive
     * @return  boolean                 True on success, false otherwise
     * @static
     */
    static function _addList($listName, $listStatus=false, $notificationMail='')
    {
        global $objDatabase;

        if ($objDatabase->Execute("
            INSERT INTO ".DBPREFIX."module_newsletter_category (
                `name`, `status`, `notification_email`
            ) VALUES (
                '$listName', ".($listStatus ? 1 : 0).", '".$notificationMail."'
            )")
        ) {
            self::$arrLists = null;
            return $objDatabase->Insert_ID();
        }
        return false;
    }

    function _validateRecipientAttributes($recipientAttributeStatus, $recipient_website, $recipient_sex, $recipient_salutation,
            $recipient_title, $recipient_lastname, $recipient_firstname, $recipient_position, $recipient_company, $recipient_industry,
            $recipient_address, $recipient_zip, $recipient_city, $recipient_country, $recipient_phone, $recipient_private, $recipient_mobile,
            $recipient_fax, $recipient_birthday)
    {
        foreach ($recipientAttributeStatus as $attributeName => $recipientStatusArray) {
            if ($recipientStatusArray['active'] && $recipientStatusArray['required']) {
                if ($attributeName == 'recipient_birthday') {
                    $birthday = explode("-", ${$attributeName});

                    if (checkdate($birthday[1], $birthday[0], $birthday[2])) {
                        continue;
                    } else {
                        return false;
                    }
                }
                $value = trim(${$attributeName});
                if (empty($value)) {
                    return false;
                }
            }
        }
        return true;
    }

    protected static function prepareNewsletterLinksForSend($MailId, $MailHtmlContent, $UserId, $recipientType, $langId = null)
    {
        global $objDatabase;

        $arrSettings = static::_getSettings();
        if (!$arrSettings['statistics']['setvalue']) {
            return $MailHtmlContent;
        }

        $result = $MailHtmlContent;
        $matches = NULL;
        if (preg_match_all("/<a([^>]+)>(.*?)<\/a>/is", $result, $matches)) {
            // get all links info
            $arrLinks = array();
            $objLinks = $objDatabase->Execute("
                SELECT `id`, `title`, `url`
                FROM ".DBPREFIX."module_newsletter_email_link
                WHERE `email_id`=$MailId");
            if ($objLinks) {
                while (!$objLinks->EOF) {
                    $arrLinks[$objLinks->fields['id']] = array('title' => $objLinks->fields['title'], 'url' => $objLinks->fields['url']);
                    $objLinks->MoveNext();
                }
            }

            // replace links
            if (count($arrLinks) > 0) {
                $tagCount = count($matches[0]);
                $fullKey = 0;
                $attrKey = 1;
                $textKey = 2;
                $rmatches = NULL;
                for ($i = 0; $i < $tagCount; $i++) {
                    if (!preg_match("/newsletter_link_(\d+)/i",
                            $matches[$attrKey][$i], $rmatches)) {
                       continue;
                    }
                    $linkId = $rmatches[1];
                    $url = '';
                    if (preg_match("/href\s*=\s*(['\"])(.*?)\\1/i", $matches[$attrKey][$i], $rmatches)) {
                        $url = $rmatches[2];
                    }
                    // remove newsletter_link_N from rel attribute
                    $matches[$attrKey][$i] = preg_replace("/newsletter_link_".$linkId."/i", "", $matches[$attrKey][$i]);
                    // remove empty rel attribute
                    $matches[$attrKey][$i] = preg_replace("/\s*rel=\s*(['\"])\s*\\1/i", "", $matches[$attrKey][$i]);
                    // remove left and right spaces
                    $matches[$attrKey][$i] = preg_replace("/([^=])\s*\"/i", "$1\"", $matches[$attrKey][$i]);
                    $matches[$attrKey][$i] = preg_replace("/=\"\s*/i", "=\"", $matches[$attrKey][$i]);
                    // replace href attribute
                    if (isset($arrLinks[$linkId])) {
// TODO: use new URL-format
                        $shortType = '';
                        switch ($recipientType) {
                            case NewsletterLib::USER_TYPE_ACCESS:
                            case NewsletterLib::USER_TYPE_CORE:
                                $shortType = 'r';
                                break;
                            case NewsletterLib::USER_TYPE_NEWSLETTER:
                                $shortType = 'm';
                                break;
                            case NewsletterLib::USER_TYPE_CRM:
                                $shortType = 'c';
                                break;
                        }

                        $arrParameters = array(
                            'section'               => 'Newsletter',
                            'n'                     => $MailId,
                            'l'                     => $linkId,
                            $shortType              => $UserId,
                        );
                        $protocol = null;
                        if (\Env::get('config')['forceProtocolFrontend'] != 'none') {
                            $protocol = \Env::get('config')['forceProtocolFrontend'];
                        }
                        $newUrl = \Cx\Core\Routing\Url::fromDocumentRoot(
                            $arrParameters, $langId, $protocol)->toString();
                        $matches[$attrKey][$i] = preg_replace(
                            "/href\s*=\s*(['\"]).*?\\1/i",
                            "href=\"".$newUrl."\"", $matches[$attrKey][$i]);
                    }
                    $result = preg_replace(
                        "/".preg_quote($matches[$fullKey][$i], '/')."/is",
                        "<a ".$matches[$attrKey][$i].">".$matches[$textKey][$i]."</a>",
                        $result, 1);
                }
            }
        }
        return $result;
    }

// TODO: This should not be used anymore.  See {@see preg_quote()}!
    protected static function prepareForRegExp($Text)
    {
        $search  = array('\\', '/', '^', '$', '.', '[', ']', '|', '(', ')', '?', '*', '+', '{', '}', '-');
        $replace = array('\\\\', '\\/', '\\^', '\\$', '\\.', '\\[', '\\]', '\\|', '\\(', '\\)', '\\?', '\\*', '\\+', '\\{', '\\}', '\\-');
        $Text = str_replace($search, $replace, $Text);
        return $Text;
    }

    /**
     * Auto clean a registers
     */
    public function autoCleanRegisters()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objDatabase = $cx->getDb()->getAdoDb();
        $arrSettings = $this->_getSettings();
        $confirmLinkHour = $arrSettings['confirmLinkHour']['setvalue'];
        $dateTime = $cx->getComponent('DateTime')->createDateTimeForDb('now');
        $dateTime->modify('-' . $confirmLinkHour . ' hours');

        if ($arrSettings['defUnsubscribe']['setvalue'] == 1) {
            $objUser = $objDatabase->Execute('
                DELETE
                    `userCat`,
                    `users`
                FROM
                    `' . DBPREFIX . 'module_newsletter_user` AS `users`
                INNER JOIN
                    `' . DBPREFIX . 'module_newsletter_rel_user_cat` AS `userCat`
                ON
                    `users`.`id` = `userCat`.`user`
                WHERE
                    `users`.`source` = "opt-in" AND
                    `users`.`consent` IS NULL AND
                    `users`.`emaildate` < "' . $dateTime->getTimeStamp() . '"
            ');
        } else {
            $objUser = $objDatabase->Execute('
                UPDATE
                    `' . DBPREFIX . 'module_newsletter_user` AS `users`
                SET
                    `users`.`status` = 0
                WHERE
                    `users`.`source` = "opt-in" AND
                    `users`.`consent` IS NULL AND
                    `users`.`emaildate` < "' . $dateTime->getTimeStamp() . '"
            ');
        }
    }

    /**
     * Send a consent confirmation mail to users based on mailing list
     * @param array $categoryIds Category to send mail for
     * @param string $email (optional) Only sends the mail to this user
     * @return boolean False if something went wrong, true otherwise
     */
    public function sendConsentConfirmationMail($categoryIds, $email = '')
    {
        global $_ARRAYLANG, $_CONFIG;

        $objDatabase = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
        $arrSettings = $this->_getSettings();

        $userQuery = '';
        if (!empty($email)) {
            $userQuery = ' AND
                `u`.`email` = "' . contrexx_raw2db($email) . '"';
        }
        $objUserRel = $objDatabase->Execute('
            SELECT
                `u`.`code`,
                `u`.`email`,
                `u`.`sex`,
                `u`.`title`,
                `u`.`firstname`,
                `u`.`lastname`,
                `c`.`id`,
                `c`.`name`
            FROM
                `' . DBPREFIX . 'module_newsletter_rel_user_cat` AS `r`
            INNER JOIN
                `' . DBPREFIX . 'module_newsletter_user` AS `u`
            ON
                `u`.`id` = `r`.`user`
            INNER JOIN
                `' . DBPREFIX . 'module_newsletter_category` AS `c`
            ON
                `c`.`id` = `r`.`category`
            WHERE
                `r`.`category` IN(' . implode(
                    ', ',
                    array_map('contrexx_raw2db', $categoryIds)
                ) . ') AND
                `r`.`consent` IS NULL AND
                `u`.`status` = 1' . $userQuery . '
        ');

        if ($objUserRel && $objUserRel->RecordCount() == 0) {
            return true;
        }

        $objDatabase->Execute('
            UPDATE
                `' . DBPREFIX . 'module_newsletter_user` AS `u`
            INNER JOIN
                `' . DBPREFIX . 'module_newsletter_rel_user_cat` AS `r`
            ON
                `u`.`id` = `r`.`user`
            SET
                `u`.`emaildate` = "' . time() . '"
            WHERE
                `r`.`category` IN(' . implode(
                    ', ',
                    array_map('contrexx_raw2db', $categoryIds)
                ) . ') AND
                `r`.`consent` IS NULL AND
                `u`.`status` = 1' . $userQuery . '
        ');

        $mailData = array();
        $notSentTo = array();
        while (!$objUserRel->EOF) {
            $sex = '';
            switch ($objUserRel->fields['sex']) {
                case 'm':
                    $sex = 'MALE';
                    break;
                case 'f':
                    $sex = 'FEMALE';
                    break;
            }

            $email = $objUserRel->fields['email'];
            if (!isset($mailData[$email])) {
                $mailData[$email] = array();
            }
            if (!isset($mailData[$email]['categories'])) {
                $mailData[$email]['categories'] = array(
                    $objUserRel->fields['id'] => $objUserRel->fields['name'],
                );
            } else {
                $mailData[$email]['categories'][
                    $objUserRel->fields['id']
                ] = $objUserRel->fields['name'];
            }
            $mailData[$email]['data'] = array(
                'lang_id' => $this->getUsersPreferredLanguageId(
                    $email,
                    static::USER_TYPE_NEWSLETTER
                ),
                'sex' => $sex,
                'title' => $objUserRel->fields['title'],
                'firstname' => $objUserRel->fields['firstname'],
                'lastname' => $objUserRel->fields['lastname'],
                'code' => $objUserRel->fields['code'],
            );
            $objUserRel->MoveNext();
        }

        foreach ($mailData as $email=>$data) {
            $arrMailTemplate = array(
                'key'          => 'consent_confirmation_email',
                'section'      => 'Newsletter',
                'lang_id'      => $data['data']['lang_id'],
                'to'           => $email,
                'from'         => $arrSettings['sender_mail']['setvalue'],
                'sender'       => $arrSettings['sender_name']['setvalue'],
                'reply'        => $arrSettings['reply_mail']['setvalue'],
                'substitution' => array(
                    'NEWSLETTER_USER_SEX' => $_ARRAYLANG[
                        'TXT_NEWSLETTER_' . $data['data']['sex']
                    ],
                    'NEWSLETTER_USER_TITLE' => $data['data']['title'],
                    'NEWSLETTER_USER_FIRSTNAME' => $data['data']['firstname'],
                    'NEWSLETTER_USER_LASTNAME' => $data['data']['lastname'],
                    'NEWSLETTER_USER_EMAIL' => $email,
                    'NEWSLETTER_CONSENT_CONFIRM_CODE' => \Cx\Core\Routing\Url::fromDocumentRoot(
                        array(
                            'section' => 'Newsletter',
                            'cmd' => 'confirm',
                            'email' => urlencode($email),
                            'code' => $data['data']['code'],
                            'category' => implode(
                                '/',
                                array_keys($data['categories'])
                            ),
                        )
                    )->toString(),
                    'NEWSLETTER_DOMAIN_URL' => $_CONFIG['domainUrl'],
                    'NEWSLETTER_LISTS' => array(),
                ),
            );
            foreach ($data['categories'] as $catId=>$catName) {
                $arrMailTemplate['substitution']['NEWSLETTER_LISTS'][] = array(
                    'NEWSLETTER_LIST' => contrexx_raw2xhtml($catName),
                );
            }
            if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate)) {
                $notSentTo[] = $objUserRel->fields['email'];
            }
        }
        if (count($notSentTo)) {
            if (isset(static::$strErrMessage)) {
                static::$strErrMessage = ' ' . sprintf(
                    $_ARRAYLANG['TXT_NEWSLETTER_CONSENT_SOME_NOT_SENT'],
                    implode('<br />', $notSentTo)
                );
            } else {
                // currently, front-end is not capable of showing an error
                // message, therefore we simply log it:
                \DBG::msg(
                    'Consent mail could not be delivered to he following addresses:'
                );
                \DBG::dump($notSentTo);
            }
        }

        return empty($notSentTo);
    }
}
