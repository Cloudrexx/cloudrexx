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
 * Newsletter Modul
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.1.0
 * @package     cloudrexx
 * @subpackage  module_newsletter
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Newsletter\Controller;

/**
 * Newsletter Modul
 *
 * frontend newsletter class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.1.0
 * @package     cloudrexx
 * @subpackage  module_newsletter
 * @todo        Edit PHP DocBlocks!
 */
class Newsletter extends NewsletterLib
{
    public $_objTpl;
    public $months = array();

    /**
     * Constructor
     * @param  string  $pageContent
     */
    function __construct($pageContent)
    {
        global $_ARRAYLANG;
        $this->pageContent = $pageContent;
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $months = explode(',', $_ARRAYLANG['TXT_NEWSLETTER_MONTHS_ARRAY']);
        $i=0;
        foreach ($months as $month) {
            $this->months[++$i] = $month;
        }
    }

    function getPage()
    {
        if (!isset($_REQUEST['cmd'])) {
            $_REQUEST['cmd'] = '';
        }

        // All actions must not be cached. This includes all requests to
        // unsubscribe, subscribe, confirm and profile.
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getComponent('Cache')->addException('Newsletter');

        switch($_REQUEST['cmd']) {
            case 'unsubscribe':
                $this->_unsubscribe();
                break;
            case 'confirm':
                $this->_confirm();
                break;
            case 'subscribe':
            case 'profile':
            default:
                $this->_profile();
                break;
        }
        return $this->_objTpl->get();
    }


    function _confirm()
    {
        global $objDatabase, $_ARRAYLANG;
        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $arrSettings = $this->_getSettings();
        $userEmail = isset($_GET['email'])
            ? rawurldecode(contrexx_input2raw($_GET['email'])) : '';
        // Get when user confirms a mailing permission link
        $categoryId = isset($_GET['category'])
            ? contrexx_input2raw($_GET['category']) : '';
        $categoryIds = array();
        if (!empty($categoryId)) {
            $categoryIds = array_map(
                'contrexx_raw2db',
                explode('/', urldecode($categoryId))
            );
        }
        $code = isset($_GET['code']) ? contrexx_input2db($_GET['code']) : '';
        $count = 0;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $dateTime = $cx->getComponent('DateTime')->createDateTimeForDb('');
        $currentTime = $dateTime->format('Y-m-d H:i:s');

        if (!empty($userEmail)) {
            $query = '
                SELECT
                    `id`,
                    `emaildate`
                FROM
                    `' . DBPREFIX . 'module_newsletter_user`
                WHERE
                    `email`  = "' . contrexx_raw2db($userEmail) . '" AND
                    `status` = "' . ((bool) count($categoryIds)) . '" AND
                    `code`   = "' . $code . '"
            ';
            $objResult = $objDatabase->Execute($query);
            $count     = $objResult->RecordCount();
        }
        if (empty($count)) {
            $this->_objTpl->setVariable("NEWSLETTER_MESSAGE", '<span class="text-danger">'.$_ARRAYLANG['TXT_NOT_VALID_EMAIL'].'</span>');
            return;
        }
        $userId    = $objResult->fields['id'];
        $emailDate = $cx->getComponent('DateTime')->createDateTimeForDb(
            '@' . $objResult->fields['emaildate']
        );

        // Checks registered time with current time, if time exceeds
        // configured number of hours user will be removed from a list
        $confirmLinkHour = $arrSettings['confirmLinkHour']['setvalue'];
        $dateTime = $cx->getComponent('DateTime')->createDateTimeForDb('now');
        $dateTime->modify('-' . $confirmLinkHour . ' hours');
        // If link has expired we drop or deactivate the user
        if ($emailDate < $dateTime) {
            $this->autoCleanRegisters();
            $this->_objTpl->setVariable(
                'NEWSLETTER_MESSAGE',
                '<span class="text-danger">'. $_ARRAYLANG['TXT_NEWSLETTER_NOT_CONFIRM_MSG'] .'</span>'
            );
            return;
        }

        // Check if consent is null in module_newsletter_rel_user_cat table when user
        // clicks a mailing permission link. If null: continue code below this condition,
        // otherwise return a error message
        $catConsentQuery = ' AND `source` = "opt-in"';
        if (count($categoryIds)) {
            $catConsentQuery = ' AND `category` IN(' . implode(', ', $categoryIds) . ')';
            $objUserRel = $objDatabase->Execute('
                SELECT
                    `consent`
                FROM
                    `' . DBPREFIX . 'module_newsletter_rel_user_cat`
                WHERE
                    `user` = "' . contrexx_raw2db($userId) . ' AND
                    `consent` IS NULL"
                    ' . $catConsentQuery . '
            ');
            // we show an error message if the user already gave consent in
            // order to make it impossible to find registered addresses by
            // try and error.
            if ($objUserRel && $objUserRel->RecordCount() == 0) {
                $this->_objTpl->setVariable(
                    'NEWSLETTER_MESSAGE',
                    '<span class="text-danger">'.$_ARRAYLANG['TXT_NOT_VALID_EMAIL'].'</span>'
                );
                return;
            }
        }

        // Update the consent value in module_newsletter_rel_user_cat table based
        // on recipient id or update the consent value in module_newsletter_rel_user_cat
        // table based on recipient id and category id when user clicks a mailing permission link
        $objUserCat = $objDatabase->Execute('
            UPDATE
                `' . DBPREFIX . 'module_newsletter_rel_user_cat`
            SET
                `consent` = "' . $currentTime . '"
            WHERE
                `user` = "' . contrexx_raw2db($userId) . '" AND
                `consent` IS NULL' . 
                $catConsentQuery . '
        ');

        if ($objUserCat !== false && count($categoryIds)) {
            $this->_objTpl->setVariable(
                'NEWSLETTER_MESSAGE',
                $_ARRAYLANG['TXT_NEWSLETTER_MAILING_CONFIRM_SUCCESSFUL']
            );
        }

        // Update a consent and status value in module_newsletter_user table based
        // on recipient email id when user confirms a subscription.
        $objResult = $objDatabase->Execute('
            UPDATE
                `' . DBPREFIX . 'module_newsletter_user`
            SET
                `status` = 1,
                `source` = "opt-in",
                `consent` = "' . $currentTime . '"
            WHERE
                `email` = "' . contrexx_raw2db($userEmail) . '" AND
                `consent` IS NULL
        ');

        if ($objResult !== false && !count($categoryIds)) {
            $this->_objTpl->setVariable("NEWSLETTER_MESSAGE", $_ARRAYLANG['TXT_NEWSLETTER_CONFIRMATION_SUCCESSFUL']);

            //send notification
            $this->_sendNotificationEmail(1, $userId);

            //send mail
            $query = "SELECT id, sex, salutation, firstname, lastname, code FROM ".DBPREFIX."module_newsletter_user WHERE email='". contrexx_raw2db($userEmail) ."'";
            $objResult = $objDatabase->Execute($query);

            if ($objResult !== false) {
                $userFirstname = $objResult->fields['firstname'];
                $userLastname  = $objResult->fields['lastname'];
                $userTitle     = $objResult->fields['salutation'];
                $userSex       = $objResult->fields['sex'];

                // TODO: use FWUSER
                $arrRecipientTitles = $this->_getRecipientTitles();
                $userTitle          = $arrRecipientTitles[$userTitle];

                switch ($userSex) {
                    case "m":
                        $userSex = $_ARRAYLANG['TXT_NEWSLETTER_MALE'];
                        break;

                    case "f":
                        $userSex = $_ARRAYLANG['TXT_NEWSLETTER_FEMALE'];
                        break;

                    default:
                        $userSex = '';
                        break;
                }

                $url = \Cx\Core\Core\Controller\Cx::instanciate()->getRequest()->getUrl()->getDomain();
                $arrMailTemplate = array(
                    'key'          => 'confirm_email',
                    'section'      => 'Newsletter',
                    'lang_id'      => FRONTEND_LANG_ID,
                    'to'           => $userEmail,
                    'from'         => $arrSettings['sender_mail']['setvalue'],
                    'sender'       => $arrSettings['sender_name']['setvalue'],
                    'reply'        => $arrSettings['reply_mail']['setvalue'],
                    'substitution' => array(
                        'NEWSLETTER_USER_SEX'       => $userSex,
                        'NEWSLETTER_USER_TITLE'     => $userTitle,
                        'NEWSLETTER_USER_FIRSTNAME' => $userFirstname,
                        'NEWSLETTER_USER_LASTNAME'  => $userLastname,
                        'NEWSLETTER_USER_EMAIL'     => $userEmail,
                        'NEWSLETTER_DOMAIN_URL'     => $url,
                        'NEWSLETTER_CURRENT_DATE'   => date(ASCMS_DATE_FORMAT),
                    ),
                );
                \Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate);
            }
        }
    }

    function _unsubscribe()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent);
        $message = '';


        if (
            !isset($_REQUEST['mail']) ||
            !isset($_REQUEST['code'])
        ) {
            $message = '<span class="text-danger">'.$_ARRAYLANG['TXT_AUTHENTICATION_FAILED'].'</span>';
            $this->_objTpl->setVariable("NEWSLETTER_MESSAGE", $message);
            return;
        }
        $requestedMail = contrexx_input2raw($_REQUEST['mail']);
        $code = contrexx_input2raw($_REQUEST['code']);

        if (($objUser = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_user WHERE code='".contrexx_raw2db($code)."' AND email='".contrexx_raw2db($requestedMail)."' AND status='1'", 1)) && $objUser->RecordCount() == 1) {
            $objSystem = $objDatabase->Execute("SELECT `setname`, `setvalue` FROM `".DBPREFIX."module_newsletter_settings`");
            if ($objSystem !== false) {
                while (!$objSystem->EOF) {
                    $arrSystem[$objSystem->fields['setname']] = $objSystem->fields['setvalue'];
                    $objSystem->MoveNext();
                }
            }

            if ($arrSystem['defUnsubscribe'] == 1) {
                //delete
                //send notification before trying to delete the record
                $this->_sendNotificationEmail(2, $objUser->fields['id']);
                if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_rel_user_cat WHERE user=".$objUser->fields['id']) && $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_newsletter_user WHERE id=".$objUser->fields['id'])) {
                    $message = '<span class="text-success">' . $_ARRAYLANG['TXT_EMAIL_SUCCESSFULLY_DELETED'] . '</span>';
                } else {
                    $message = '<span class="text-danger">' . $_ARRAYLANG['TXT_NEWSLETTER_FAILED_REMOVING_FROM_SYSTEM'] . '</span>';
                }
            } else {
                //deactivate
                if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_newsletter_user SET status='0' WHERE id='".$objUser->fields['id']."'")) {
                    //send notification
                    $this->_sendNotificationEmail(2, $objUser->fields['id']);
                    $message = '<span class="text-success">' . $_ARRAYLANG['TXT_EMAIL_SUCCESSFULLY_DELETED'] . '</span>';
                } else {
                    $message = '<span class="text-danger">' . $_ARRAYLANG['TXT_NEWSLETTER_FAILED_REMOVING_FROM_SYSTEM'] . '</span>';
                }
            }
        } else {
            $message = '<span class="text-danger">'.$_ARRAYLANG['TXT_AUTHENTICATION_FAILED'].'</span>';
        }

        $this->_objTpl->setVariable("NEWSLETTER_MESSAGE", $message);
    }

    function _profile()
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $this->_objTpl->setTemplate($this->pageContent);

        $showForm = true;
        $arrStatusMessage = array('ok' => array(), 'error' => array());

        $isNewsletterRecipient = false;
        $isAccessRecipient = false;
        $isAuthenticatedUser = false;
        $recipientId = 0;
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
        $recipientLanguage = '';
        $recipientStatus = 0;
        $requestedMail = isset($_GET['mail']) ? contrexx_input2raw($_GET['mail']) : (isset($_POST['mail']) ? contrexx_input2raw($_POST['mail']) : '');
        $arrAssociatedLists = array();
        $arrPreAssociatedInactiveLists = array();
        $code = isset($_REQUEST['code']) ? contrexx_addslashes($_REQUEST['code']) : '';
        $source = 'opt-in';

        if (!empty($code) && !empty($requestedMail)) {
            $objRecipient = $objDatabase->SelectLimit("SELECT accessUserID
                FROM ".DBPREFIX."module_newsletter_access_user AS nu
                INNER JOIN ".DBPREFIX."access_users AS au ON au.id=nu.accessUserID
                WHERE nu.code='".$code."'
                AND email='".contrexx_raw2db($requestedMail)."'", 1);
            if ($objRecipient && $objRecipient->RecordCount() == 1) {
                $objUser = \FWUser::getFWUserObject()->objUser->getUser($objRecipient->fields['accessUserID']);
                if ($objUser) {
                    $recipientId = $objUser->getId();
                    $isAccessRecipient = true;

                    //$arrAssociatedLists = $objUser->getSubscribedNewsletterListIDs();
                    $arrPreAssociatedInactiveLists = $objUser->getSubscribedNewsletterListIDs();
                }
            } else {
                $objRecipient = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_newsletter_user WHERE status=1 AND code='".$code."' AND email='".contrexx_raw2db($requestedMail)."'", 1);
                if ($objRecipient && $objRecipient->RecordCount() == 1) {
                    $recipientId = $objRecipient->fields['id'];
                    $isNewsletterRecipient = true;
                }
            }
        } else {
            if (\FWUser::getFWUserObject()->objUser->login()) {
                $objUser = \FWUser::getFWUserObject()->objUser;
                $recipientId = $objUser->getId();
                $isAccessRecipient = true;

                //$arrAssociatedLists = $objUser->getSubscribedNewsletterListIDs();
                $arrPreAssociatedInactiveLists = $objUser->getSubscribedNewsletterListIDs();
            }
        }

        // Check if the user is verified.
        // It the user is verified, we won't have to use the CAPTCHA protection.
        if ($isAccessRecipient || $isNewsletterRecipient) {
            $isAuthenticatedUser = true;
        }

        // Get interface settings
        $objInterface = $objDatabase->Execute('SELECT `setvalue`
                                                FROM `'.DBPREFIX.'module_newsletter_settings`
                                                WHERE `setname` = "recipient_attribute_status"');

        $recipientAttributeStatus = json_decode($objInterface->fields['setvalue'], true);

        $captchaOk = true;
        if (isset($_POST['recipient_save'])) {
            if (
                !$isAuthenticatedUser &&
                isset($recipientAttributeStatus['captcha']) &&
                $recipientAttributeStatus['captcha']['active']
            ) {
                if (!\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
                    $captchaOk = false;
                    array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_FAILED_CAPTCHA']);
                }
            }
            if (isset($_POST['email'])) {
                $recipientEmail = $_POST['email'];
            }
            if (isset($_POST['website'])) {
                $recipientUri = $_POST['website'];
            }
            if (isset($_POST['sex'])) {
                $recipientSex = in_array($_POST['sex'], array('f', 'm')) ? $_POST['sex'] : '';
            }
            if (isset($_POST['salutation'])) {
// TODO: use FWUSER
                $arrRecipientTitles = $this->_getRecipientTitles();
                $recipientSalutation = in_array($_POST['salutation'], array_keys($arrRecipientTitles)) ? intval($_POST['salutation']) : 0;
            }
            if (isset($_POST['title'])) {
                $recipientTitle = $_POST['title'];
            }
            if (isset($_POST['lastname'])) {
                $recipientLastname = $_POST['lastname'];
            }
            if (isset($_POST['firstname'])) {
                $recipientFirstname = $_POST['firstname'];
            }
            if (isset($_POST['position'])) {
                $recipientPosition = $_POST['position'];
            }
            if (isset($_POST['company'])) {
                $recipientCompany = $_POST['company'];
            }
            if (isset($_POST['industry_sector'])) {
                $recipientIndustrySector = $_POST['industry_sector'];
            }
            if (isset($_POST['address'])) {
                $recipientAddress = $_POST['address'];
            }
            if (isset($_POST['zip'])) {
                $recipientZip = $_POST['zip'];
            }
            if (isset($_POST['city'])) {
                $recipientCity = $_POST['city'];
            }
            if (isset($_POST['newsletter_country_id'])) {
                $recipientCountry = $_POST['newsletter_country_id'];
            }
            if (isset($_POST['phone_office'])) {
                $recipientPhoneOffice = $_POST['phone_office'];
            }
            if (isset($_POST['phone_private'])) {
                $recipientPhonePrivate = $_POST['phone_private'];
            }
            if (isset($_POST['phone_mobile'])) {
                $recipientPhoneMobile = $_POST['phone_mobile'];
            }
            if (isset($_POST['fax'])) {
                $recipientFax = $_POST['fax'];
            }
            if (isset($_POST['day']) && isset($_POST['month']) && isset($_POST['year'])) {
                $recipientBirthday = str_pad(intval($_POST['day']),2,'0',STR_PAD_LEFT).'-'.str_pad(intval($_POST['month']),2,'0',STR_PAD_LEFT).'-'.intval($_POST['year']);
            }
            if (isset($_POST['language'])) {
                $recipientLanguage = $_POST['language'];
            }
            if (isset($_POST['notes'])) {
                $recipientNotes = $_POST['notes'];
            }

            if (isset($_POST['list'])) {
                foreach ($_POST['list'] as $listId => $status) {
                    if (intval($status) == 1) {
                        array_push($arrAssociatedLists, intval($listId));
                    }
                }
            } elseif (!$recipientId) {
                // Signup request where no recipient list had been selected

                // check if the user didn't select any list or if there is non or just 1 recipient list visible and was therefore not visible for the user to select
                // only show newsletter-lists that are visible for new users (not yet registered ones)
                $excludeDisabledLists = 1;
                $arrLists = self::getLists($excludeDisabledLists);
                switch (count($arrLists)) {
                    case 0:
                        // no active lists > ok
                        break;

                    case 1:
                        // only 1 list is active, therefore no list was visible for selection -> let's signup the new recipient to this very list
                        $arrAssociatedLists = array_keys($arrLists);
                        break;

                    default:
                        // more than one list is active, therefore the user would have been able to select his preferred lists.
                        // however, the fact that we landed in this case is that the user didn't make any selection at all.
                        // so lets be it like that > the user won't be subscribed to any list
                        break;
                }
            }

            if (!$isAccessRecipient) {
                    // add or update existing newsletter recipient (for access user see ELSE case)
                    $arrPreAssociatedInactiveLists = $this->_getAssociatedListsOfRecipient($recipientId, false);
                    $arrAssociatedInactiveLists = array_intersect($arrPreAssociatedInactiveLists, $arrAssociatedLists);

                    $objValidator = new \FWValidator();
                    if ($objValidator->isEmail($recipientEmail)) {

                        // Let's check if a user account with the provided email address is already present
                        // Important: we must check only for active accounts (active => 1), otherwise we'll send a notification e-mail
                        //            to a user that won't be able to active himself due to his account's inactive state.
// TODO: implement feature
                        $objUser = null;//FWUser::getFWUserObject()->objUser->getUsers(array('email' => $recipientEmail, 'active' => 1));
                        if (false && $objUser) {
                            // there is already a user account present by the same email address as the one submitted by the user
// TODO: send notification e-mail about existing e-mail account
                            // Important: We must output the same status message as if the user has been newly added!
                            //            This shall prevent email-address-crawling-bots from detecting existing e-mail accounts.
                            array_push($arrStatusMessage['ok'], $_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIBE_OK']);
                            $showForm = false;
                        } else {
                            if ($this->_validateRecipientAttributes($recipientAttributeStatus, $recipientUri, $recipientSex, $recipientSalutation, $recipientTitle, $recipientLastname, $recipientFirstname, $recipientPosition, $recipientCompany, $recipientIndustrySector, $recipientAddress, $recipientZip, $recipientCity, $recipientCountry, $recipientPhoneOffice, $recipientPhonePrivate, $recipientPhoneMobile, $recipientFax, $recipientBirthday)) {
                                if ($captchaOk && $this->_isUniqueRecipientEmail($recipientEmail, $recipientId)) {
                                    if (!empty($arrAssociatedInactiveLists) || !empty($arrAssociatedLists) && ($objList = $objDatabase->SelectLimit('SELECT id FROM '.DBPREFIX.'module_newsletter_category WHERE status=1 AND (id='.implode(' OR id=', $arrAssociatedLists).')' , 1)) && $objList->RecordCount() > 0) {
                                        if ($recipientId > 0) {
                                            if ($this->_updateRecipient($recipientAttributeStatus, $recipientId, $recipientEmail, $recipientUri, $recipientSex, $recipientSalutation, $recipientTitle, $recipientLastname, $recipientFirstname, $recipientPosition, $recipientCompany, $recipientIndustrySector, $recipientAddress, $recipientZip, $recipientCity, $recipientCountry, $recipientPhoneOffice, $recipientPhonePrivate, $recipientPhoneMobile, $recipientFax, $recipientNotes, $recipientBirthday, 1, $arrAssociatedLists, $recipientLanguage, $source, true)) {
                                                array_push($arrStatusMessage['ok'], $_ARRAYLANG['TXT_NEWSLETTER_YOUR_DATE_SUCCESSFULLY_UPDATED']);
                                                $showForm = false;
                                            } else {
                                                array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_FAILED_UPDATE_YOUR_DATA']);
                                            }
                                        } else {
                                            if ($this->_addRecipient($recipientEmail, $recipientUri, $recipientSex, $recipientSalutation, $recipientTitle, $recipientLastname, $recipientFirstname, $recipientPosition, $recipientCompany, $recipientIndustrySector, $recipientAddress, $recipientZip, $recipientCity, $recipientCountry, $recipientPhoneOffice, $recipientPhonePrivate, $recipientPhoneMobile, $recipientFax, $recipientNotes, $recipientBirthday, $recipientStatus, $arrAssociatedLists, $recipientLanguage, $source)) {
                                                if ($this->_sendAuthorizeEmail(contrexx_input2raw($recipientEmail), $recipientSex, $recipientSalutation, $recipientFirstname, $recipientLastname)) {
                                                    array_push($arrStatusMessage['ok'], $_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIBE_OK']);
                                                    $showForm = false;
                                            } else {
                                                    $objDatabase->Execute("DELETE tblU, tblR FROM ".DBPREFIX."module_newsletter_user AS tblU, ".DBPREFIX."module_newsletter_rel_user_cat AS tblR WHERE tblU.email='".contrexx_addslashes($recipientEmail)."' AND tblR.user = tblU.id");
                                                    array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIPTION_CANCELED_BY_EMAIL']);
                                                }
                                            } else {
                                                array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_FAILED_ADDING_YOU']);
                                            }
                                        }
                                    } else {
                                        $unsub = $_ARRAYLANG['TXT_UNSUBSCRIBE'];
                                        if(isset($_REQUEST['code']) && isset($_REQUEST['mail'])) {
                                            $nm = new \Cx\Modules\Newsletter\Controller\NewsletterLib();
                                            $unsub = $nm->GetUnsubscribeURL($_REQUEST['code'], $_REQUEST['mail']);
                                        }
                                        array_push($arrStatusMessage['error'], sprintf($_ARRAYLANG['TXT_NEWSLETTER_UNSUBSCRIBE_IF_ONLY_ONE_LIST_ACTIVE'], $unsub));
                                    }
                                } elseif ($captchaOk && empty($recipientId)) {
                                    // We must send a new confirmation e-mail here
                                    // otherwise someone could reactivate someone else's e-mail address

                                    // It could be that a user who has unsubscribed himself from the newsletter system (recipient = deactivated) would like to subscribe the newsletter again.
                                    // Therefore, lets see if we can find a recipient by the specified e-mail address that has been deactivated (status=0)
                                    $objRecipient = $objDatabase->SelectLimit("SELECT id, language, notes, status FROM ".DBPREFIX."module_newsletter_user WHERE email='".contrexx_input2db($recipientEmail)."'", 1);
                                    $recipientId  = $objRecipient && !$objRecipient->EOF ? $objRecipient->fields['id'] : 0;

                                    if ($recipientId) {
                                        $arrPreAssociatedActiveLists = array();
                                        if ($objRecipient->fields['status']) {
                                            // When recipient is active then load his associative list and send notification about the new/existing subscription lists
                                            // otherwise someone could unsubscribe lists of someone else
                                            $arrPreAssociatedActiveLists = $this->_getAssociatedListsOfRecipient($recipientId);
                                            $arrAssociatedLists = array_merge($arrPreAssociatedActiveLists, $arrAssociatedLists);
                                        }

                                        // Important: We intentionally do not load existing recipient list associations, due to the fact that the user most likely had
                                        // himself been unsubscribed from the newsletter system some time in the past. Therefore the user most likey does not want
                                        // to be subscribed to any lists more than to those he just selected
                                        $arrAssociatedLists = array_unique($arrAssociatedLists);
                                        $this->_setRecipientLists($recipientId, $arrAssociatedLists, $source);
                                        if (!$objRecipient->fields['status']) {
                                            $recipientLanguage = $objRecipient->fields['language'];

                                            // Important: We do not update the recipient's profile data here by the reason that we can't verify the recipient's identity at this point!

                                            if ($this->_sendAuthorizeEmail(contrexx_input2raw($recipientEmail), $recipientSex, $recipientSalutation, $recipientFirstname, $recipientLastname)) {
                                                // Important: We must output the same status message as if the user has been newly added!
                                                //            This shall prevent email-address-crawling-bots from detecting existing e-mail accounts.
                                                array_push($arrStatusMessage['ok'], $_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIBE_OK']);
                                                $showForm = false;
                                            } else {
                                                array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_FAILED_ADDING_YOU']);
                                                array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIPTION_CANCELED_BY_EMAIL']);
                                            }
                                        } else {
                                            $this->sendSubscriptionNotificationMail(contrexx_input2raw($recipientEmail), $recipientSex, $recipientSalutation, $recipientFirstname, $recipientLastname, $arrAssociatedLists, $arrPreAssociatedActiveLists);
                                            array_push($arrStatusMessage['ok'], $_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIBE_OK']);
                                            $showForm = false;
                                        }
                                    }
                                } else if ($captchaOk) {
                                    array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_SUBSCRIBER_ALREADY_INSERTED']);
                                }
                            } else {
                                array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_MANDATORY_FIELD_ERROR']);
                            }
                        }
                    } else {
                        array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NOT_VALID_EMAIL']);
                    }
            } else if ($captchaOk) {
                // update subscribed lists of access user
                $arrAssociatedLists = array_unique($arrAssociatedLists);
                $objUser->setSubscribedNewsletterListIDs($arrAssociatedLists);
                if ($objUser->store()) {
                    array_push($arrStatusMessage['ok'], $_ARRAYLANG['TXT_NEWSLETTER_YOUR_DATE_SUCCESSFULLY_UPDATED']);
                    $showForm = false;
                } else {
                    $arrStatusMessage['error'] = array_merge($arrStatusMessage['error'], $objUser->getErrorMsg());
                }
            }
        } elseif ($isNewsletterRecipient) {
            $objRecipient = $objDatabase->SelectLimit("SELECT uri, sex, salutation, title, lastname, firstname, position, company, industry_sector, address, zip, city, country_id, phone_office, phone_private, phone_mobile, fax, notes, birthday, status, language FROM ".DBPREFIX."module_newsletter_user WHERE id=".$recipientId, 1);
            if ($objRecipient !== false && $objRecipient->RecordCount() == 1) {
                $recipientEmail = contrexx_input2raw($_REQUEST['mail']);
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
                $recipientNotes = $objRecipient->fields['notes'];

                $arrAssociatedLists = $this->_getAssociatedListsOfRecipient($recipientId, false);
                $arrPreAssociatedInactiveLists = $this->_getAssociatedListsOfRecipient($recipientId, false);
            } else {
                array_push($arrStatusMessage['error'], $_ARRAYLANG['TXT_NEWSLETTER_AUTHENTICATION_FAILED']);
                $showForm = false;
            }
        } elseif ($isAccessRecipient) {
            $objUser = \FWUser::getFWUserObject()->objUser->getUser($recipientId);
            if ($objUser) {
                $arrAssociatedLists = $objUser->getSubscribedNewsletterListIDs();
                $arrPreAssociatedInactiveLists = $objUser->getSubscribedNewsletterListIDs();
            }
        }

        $this->_createDatesDropdown($recipientBirthday);

        if (count($arrStatusMessage['ok']) > 0) {
            $this->_objTpl->setVariable('NEWSLETTER_OK_MESSAGE', implode('<br />', $arrStatusMessage['ok']));
            $this->_objTpl->parse('newsletter_ok_message');
        } else {
            $this->_objTpl->hideBlock('newsletter_ok_message');
        }
        if (count($arrStatusMessage['error']) > 0) {
            $this->_objTpl->setVariable('NEWSLETTER_ERROR_MESSAGE', implode('<br />', $arrStatusMessage['error']));
            $this->_objTpl->parse('newsletter_error_message');
        } else {
            $this->_objTpl->hideBlock('newsletter_error_message');
        }

        $languages = '<select name="language" class="selectLanguage" id="language" >';
        $languages .= '<option value="0">'.$_ARRAYLANG['TXT_NEWSLETTER_LANGUAGE_PLEASE_CHOSE'].'</option>';
        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $selected = ($frontendLanguage['id'] == $recipientLanguage) ? 'selected' : '';
            $languages .= '<option value="'.$frontendLanguage['id'].'" '.$selected.'>'.contrexx_raw2xhtml($frontendLanguage['name']).'</option>';
        }
        $languages .= '</select>';

        if ($showForm) {
            if ($isAccessRecipient) {
                if ($this->_objTpl->blockExists('recipient_profile')) {
                    $this->_objTpl->hideBlock('recipient_profile');
                }
            } else {
                //display settings recipient profile detials
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
                    'recipient_website',
                );
                foreach ($recipientAttributesArray as $attribute) {
                    if ($this->_objTpl->blockExists($attribute)) {
                        if ($recipientAttributeStatus[$attribute]['active']) {
                            $this->_objTpl->touchBlock($attribute);
                            $this->_objTpl->setVariable(array(
                                'NEWSLETTER_'.strtoupper($attribute).'_MANDATORY' => ($recipientAttributeStatus[$attribute]['required']) ? '*' : '',
                            ));
                        } else {
                            $this->_objTpl->hideBlock($attribute);
                        }
                    }
                }

                // use CAPTCHA if it has been activated and the user is not authenticated
                if (!$isAuthenticatedUser &&
                    $recipientAttributeStatus['captcha']['active']
                ) {
                    $this->_objTpl->setVariable(array(
                        'TXT_MODULE_CAPTCHA'   => $_CORELANG['TXT_CORE_CAPTCHA'],
                        'MODULE_CAPTCHA_CODE'  => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),

                        // this is a legacy placeholder
                        'NEWSLETTER_CAPTCHA_MANDATORY' => $recipientAttributeStatus['captcha']['required'] ? '*' : '',
                    ));
                    $this->_objTpl->touchBlock('captcha');
                } else {
                    $this->_objTpl->hideBlock('captcha');
                }

                $this->_objTpl->setVariable(array(
                    'NEWSLETTER_EMAIL'        => htmlentities($recipientEmail, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_WEBSITE'          => htmlentities($recipientUri, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_SEX_F'        => $recipientSex == 'f' ? 'checked="checked"' : '',
                    'NEWSLETTER_SEX_M'        => $recipientSex == 'm' ? 'checked="checked"' : '',
                    'NEWSLETTER_SALUTATION'        => $this->_getRecipientTitleMenu($recipientSalutation, 'name="salutation" size="1"'),
                    'NEWSLETTER_TITLE'    => htmlentities($recipientTitle, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_LASTNAME'    => htmlentities($recipientLastname, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_FIRSTNAME'    => htmlentities($recipientFirstname, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_POSITION'    => htmlentities($recipientPosition, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_COMPANY'    => htmlentities($recipientCompany, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_INDUSTRY_SECTOR'    => htmlentities($recipientIndustrySector, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_ADDRESS'        => htmlentities($recipientAddress, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_ZIP'        => htmlentities($recipientZip, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_CITY'        => htmlentities($recipientCity, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_COUNTRY'    => $this->getCountryMenu($recipientCountry, ($recipientAttributeStatus['recipient_country']['active']  && $recipientAttributeStatus['recipient_country']['required'])),
                    'NEWSLETTER_PHONE'        => htmlentities($recipientPhoneOffice, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_PHONE_PRIVATE'        => htmlentities($recipientPhonePrivate, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_PHONE_MOBILE'        => htmlentities($recipientPhoneMobile, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_FAX'        => htmlentities($recipientFax, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_NOTES'        => htmlentities($recipientNotes, ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWSLETTER_LANGUAGE'     => $languages
                ));

                $this->_objTpl->setVariable(array(
                    'TXT_NEWSLETTER_EMAIL_ADDRESS'    => $_ARRAYLANG['TXT_NEWSLETTER_EMAIL_ADDRESS'],
                    'TXT_NEWSLETTER_SALUTATION'     => $_ARRAYLANG['TXT_NEWSLETTER_SALUTATION'],
                    'TXT_NEWSLETTER_SEX'            => $_ARRAYLANG['TXT_NEWSLETTER_SEX'],
                    'TXT_NEWSLETTER_FEMALE'            => $_ARRAYLANG['TXT_NEWSLETTER_FEMALE'],
                    'TXT_NEWSLETTER_MALE'            => $_ARRAYLANG['TXT_NEWSLETTER_MALE'],
                    'TXT_NEWSLETTER_TITLE'        => $_ARRAYLANG['TXT_NEWSLETTER_TITLE'],
                    'TXT_NEWSLETTER_LASTNAME'        => $_ARRAYLANG['TXT_NEWSLETTER_LASTNAME'],
                    'TXT_NEWSLETTER_FIRSTNAME'        => $_ARRAYLANG['TXT_NEWSLETTER_FIRSTNAME'],
                    'TXT_NEWSLETTER_POSITION'        => $_ARRAYLANG['TXT_NEWSLETTER_POSITION'],
                    'TXT_NEWSLETTER_COMPANY'        => $_ARRAYLANG['TXT_NEWSLETTER_COMPANY'],
                    'TXT_NEWSLETTER_INDUSTRY_SECTOR'        => $_ARRAYLANG['TXT_NEWSLETTER_INDUSTRY_SECTOR'],
                    'TXT_NEWSLETTER_ADDRESS'            => $_ARRAYLANG['TXT_NEWSLETTER_ADDRESS'],
                    'TXT_NEWSLETTER_ZIP'            => $_ARRAYLANG['TXT_NEWSLETTER_ZIP'],
                    'TXT_NEWSLETTER_CITY'            => $_ARRAYLANG['TXT_NEWSLETTER_CITY'],
                    'TXT_NEWSLETTER_COUNTRY'        => $_ARRAYLANG['TXT_NEWSLETTER_COUNTRY'],
                    'TXT_NEWSLETTER_PHONE_PRIVATE'            => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_PRIVATE'],
                    'TXT_NEWSLETTER_PHONE_MOBILE'            => $_ARRAYLANG['TXT_NEWSLETTER_PHONE_MOBILE'],
                    'TXT_NEWSLETTER_FAX'            => $_ARRAYLANG['TXT_NEWSLETTER_FAX'],
                    'TXT_NEWSLETTER_PHONE'            => $_ARRAYLANG['TXT_NEWSLETTER_PHONE'],
                    'TXT_NEWSLETTER_NOTES'            => $_ARRAYLANG['TXT_NEWSLETTER_NOTES'],
                    'TXT_NEWSLETTER_BIRTHDAY'        => $_ARRAYLANG['TXT_NEWSLETTER_BIRTHDAY'],
                    'TXT_NEWSLETTER_LANGUAGE'      => $_ARRAYLANG['TXT_NEWSLETTER_LANGUAGE'],
                    'TXT_NEWSLETTER_WEBSITE'        => $_ARRAYLANG['TXT_NEWSLETTER_WEBSITE'],
                    'TXT_NEWSLETTER_RECIPIENT_DATE' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_DATE'],
                    'TXT_NEWSLETTER_RECIPIENT_MONTH'=> $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_MONTH'],
                    'TXT_NEWSLETTER_RECIPIENT_YEAR' => $_ARRAYLANG['TXT_NEWSLETTER_RECIPIENT_YEAR'],
                ));

                if ($this->_objTpl->blockExists('recipient_profile')) {
                    $this->_objTpl->parse('recipient_profile');
                }
            }

            // only show newsletter-lists that are visible for new users (not yet registered ones)
            $excludeDisabledLists = $recipientId == 0;
            $arrLists = self::getLists($excludeDisabledLists);
            if ($this->_objTpl->blockExists('newsletter_lists') && !empty($arrLists)) {
                foreach ($arrLists as $listId => $arrList) {
                    if ($arrList['status'] || in_array($listId, $arrPreAssociatedInactiveLists)) {
                        $this->_objTpl->setVariable(array(
                            'NEWSLETTER_LIST_ID'        => $listId,
                            'NEWSLETTER_LIST_NAME'      => contrexx_raw2xhtml($arrList['name']),
                            'NEWSLETTER_LIST_SELECTED'  => in_array($listId, $arrAssociatedLists) ? 'checked="checked"' : ''
                        ));
                        $this->_objTpl->parse('newsletter_list');
                    }
                }

                $this->_objTpl->setVariable(array(
                    'TXT_NEWSLETTER_LISTS'             => $_ARRAYLANG['TXT_NEWSLETTER_LISTS'],
                ));
                $this->_objTpl->parse('newsletter_lists');
            }

            $this->_objTpl->setVariable(array(
                'NEWSLETTER_PROFILE_MAIL' => contrexx_raw2xhtml($requestedMail),
                'NEWSLETTER_USER_CODE'    => $code,
                'TXT_NEWSLETTER_SAVE'     => $_ARRAYLANG['TXT_NEWSLETTER_SAVE'],
            ));

            $this->_objTpl->parse('newsletterForm');
        } else {
            $this->_objTpl->hideBlock('newsletterForm');
        }
    }

    /**
     * Send notification mail to the user
     *
     * @param string $recipientEmail      E-mail
     * @param string $recipientSex        Sex
     * @param string $recipientTitle      User title
     * @param string $recipientFirstname  First name
     * @param string $recipientLastname   Last name
     *
     * @return boolean true when notification send false otherwise
     */
    function _sendAuthorizeEmail($recipientEmail, $recipientSex, $recipientTitle, $recipientFirstname, $recipientLastname)
    {
        global $_CONFIG, $_ARRAYLANG;

        // TODO: add validation CODE!!!
        // TODO: use FWUSER
        $arrRecipientTitles = $this->_getRecipientTitles();
        $recipientTitleTxt  = $arrRecipientTitles[$recipientTitle];

        switch ($recipientSex) {
             case 'm':
                 $recipientSexTxt = $_ARRAYLANG['TXT_NEWSLETTER_MALE'];
                 break;

             case 'f':
                 $recipientSexTxt = $_ARRAYLANG['TXT_NEWSLETTER_FEMALE'];
                 break;

             default:
                 $recipientSexTxt = '';
                 break;
        }

        $arrSettings = $this->_getSettings();
        $objDatabase = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
        $objUser     = $objDatabase->Execute(
            'SELECT
                `code`
            FROM '. DBPREFIX .'module_newsletter_user
            WHERE `email` = "'. contrexx_raw2db($recipientEmail) .'"'
        );

        $url = \Cx\Core\Core\Controller\Cx::instanciate()->getRequest()->getUrl()->getDomain();
        $now = date(ASCMS_DATE_FORMAT);

        $arrMailTemplate = array(
            'key'          => 'activation_email',
            'section'      => 'Newsletter',
            'lang_id'      => FRONTEND_LANG_ID,
            'to'           => $recipientEmail,
            'from'         => $arrSettings['sender_mail']['setvalue'],
            'sender'       => $arrSettings['sender_name']['setvalue'],
            'reply'        => $arrSettings['reply_mail']['setvalue'],
            'substitution' => array(
                'NEWSLETTER_USER_SEX'       => $recipientSexTxt,
                'NEWSLETTER_USER_TITLE'     => $recipientTitleTxt,
                'NEWSLETTER_USER_FIRSTNAME' => $recipientFirstname,
                'NEWSLETTER_USER_LASTNAME'  => $recipientLastname,
                'NEWSLETTER_USER_EMAIL'     => $recipientEmail,
                'NEWSLETTER_CONFIRM_CODE'   =>
                    ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . CONTREXX_SCRIPT_PATH .
                    '?section=Newsletter&cmd=confirm&email=' . urlencode($recipientEmail) .
                    '&code=' . $objUser->fields['code'],
                'NEWSLETTER_DOMAIN_URL'     => $url,
                'NEWSLETTER_CURRENT_DATE'   => $now,
            ),
        );
        if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate)) {
            return false;
        }

        return true;
    }

    /**
     * Send the notificaiton about the newly subscribed lists
     *
     * @param string $recipientEmail                E-mail
     * @param string $recipientSex                  Sex
     * @param string $recipientTitle                User title
     * @param string $recipientFirstname            First name
     * @param string $recipientLastname             Last name
     * @param array  $arrAssociatedLists            User subscribed list
     * @param array  $arrPreAssociatedActiveLists   User already assigned lists
     *
     * @return null
     */
    public function sendSubscriptionNotificationMail($recipientEmail, $recipientSex, $recipientTitle, $recipientFirstname, $recipientLastname, $arrAssociatedLists, $arrPreAssociatedActiveLists)
    {
        global $_CONFIG;

        sort($arrAssociatedLists);
        sort($arrPreAssociatedActiveLists);
        $newsletterKey = '';
        $substitution  = array();
        if ($arrAssociatedLists == $arrPreAssociatedActiveLists) {
            // send the notification about the subscribe of same newsletter again
            $newsletterKey = 'notify_subscription_list_same';
        } else {
            $newLists = array_diff($arrAssociatedLists, $arrPreAssociatedActiveLists);
            if (!empty($newLists)) {
                // Send consent mail if no consent for this list has been given
                // If user already gave consent: NOOP
                $this->sendConsentConfirmationMail(
                    $newLists,
                    $recipientEmail
                );
                return;
            }
        }
        if (empty($newsletterKey)) {
            return;
        }

        $arrSettings = $this->_getSettings();

        $arrMailTemplate = array(
            'key'          => $newsletterKey,
            'section'      => 'Newsletter',
            'lang_id'      => FRONTEND_LANG_ID,
            'to'           => $recipientEmail,
            'from'         => $arrSettings['sender_mail']['setvalue'],
            'sender'       => $arrSettings['sender_name']['setvalue'],
            'reply'        => $arrSettings['reply_mail']['setvalue'],
            'substitution' => array(
                'NEWSLETTER_USER_SEX'       => $recipientSex,
                'NEWSLETTER_USER_TITLE'     => $recipientTitle,
                'NEWSLETTER_USER_FIRSTNAME' => $recipientFirstname,
                'NEWSLETTER_USER_LASTNAME'  => $recipientLastname,
                'NEWSLETTER_USER_EMAIL'     => $recipientEmail,
                'NEWSLETTER_DOMAIN_URL'     => $_CONFIG['domainUrl'],
                'NEWSLETTER_CURRENT_DATE'   => date(ASCMS_DATE_FORMAT),
            ),
        );
        $arrMailTemplate['substitution'] = $substitution + $arrMailTemplate['substitution'];
        \Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate);
    }

    /**
     * Send notification mail
     *
     * @param integer $action      1 = subscribe | 2 = unsubscribe
     * @param integer $recipientId Id of the recipient
     *
     * @return boolean True when notification mail send successfully, false otherwise
     */
    function _sendNotificationEmail($action, $recipientId)
    {
        global $_CONFIG, $_ARRAYLANG, $objDatabase;
        //action: 1 = subscribe | 2 = unsubscribe

        $objSettings = $objDatabase->Execute("SELECT `setname`, `setvalue` FROM `".DBPREFIX."module_newsletter_settings` WHERE `setname` = 'notificationSubscribe' OR  `setname` = 'notificationUnsubscribe' ");
        if ($objSettings !== false) {
            while (!$objSettings->EOF) {
                $arrSettings[$objSettings->fields['setname']] = $objSettings->fields['setvalue'];
                $objSettings->MoveNext();
            }
        }

        if (   ($arrSettings['notificationSubscribe'] == 1 && $action == 1)
            || ($arrSettings['notificationUnsubscribe'] == 1 && $action == 2)
        ) {

            $objRecipient = $objDatabase->SelectLimit("SELECT sex, salutation, lastname, firstname, email FROM ".DBPREFIX."module_newsletter_user WHERE id=".$recipientId, 1);
            if ($objRecipient !== false) {
                $arrRecipient['sex'] = $objRecipient->fields['sex'];
                $arrRecipient['salutation'] = $objRecipient->fields['salutation'];
                $arrRecipient['lastname'] = $objRecipient->fields['lastname'];
                $arrRecipient['firstname'] = $objRecipient->fields['firstname'];
                $arrRecipient['email'] = $objRecipient->fields['email'];
            }

            $objRecipientTitle = $objDatabase->SelectLimit("SELECT title FROM ".DBPREFIX."module_newsletter_user_title WHERE id=".$arrRecipient['salutation'], 1);
            if ($objRecipientTitle !== false) {
                $arrRecipientTitle = $objRecipientTitle->fields['title'];
            }

            $notifyMails = array();
            if($action == 1) {
                $txtAction = $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_SUBSCRIBE'];
            } else {
                $txtAction = $_ARRAYLANG['TXT_NEWSLETTER_NOTIFICATION_UNSUBSCRIBE'];
                $objNotificationAdressesFromLists = $objDatabase->Execute('SELECT notification_email FROM '.DBPREFIX.'module_newsletter_category AS c
                                                                        INNER JOIN '.DBPREFIX.'module_newsletter_rel_user_cat AS r ON r.category = c.id
                                                                        WHERE r.user = '.contrexx_addslashes($recipientId));

                if ($objNotificationAdressesFromLists !== false) {
                    while (!$objNotificationAdressesFromLists->EOF) {
                        foreach (explode(',', $objNotificationAdressesFromLists->fields['notification_email']) as $mail) {
                            if (!in_array($mail, $notifyMails)) {
                                array_push($notifyMails, trim($mail));
                            }
                        }
                        $objNotificationAdressesFromLists->MoveNext();
                    }
                }
            }

            $arrSettings = $this->_getSettings();

            $arrMailTemplate = array(
                'key'          => 'notification_email',
                'section'      => 'Newsletter',
                'lang_id'      => FRONTEND_LANG_ID,
                'from'         => $arrSettings['sender_mail']['setvalue'],
                'sender'       => $arrSettings['sender_name']['setvalue'],
                'reply'        => $arrSettings['reply_mail']['setvalue'],
                'substitution' => array(
                    'NEWSLETTER_NOTIFICATION_ACTION'    => $txtAction,
                    'NEWSLETTER_USER_SEX'       => $arrRecipient['sex'],
                    'NEWSLETTER_USER_TITLE'     => $arrRecipientTitle,
                    'NEWSLETTER_USER_FIRSTNAME' => $arrRecipient['firstname'],
                    'NEWSLETTER_USER_LASTNAME'  => $arrRecipient['lastname'],
                    'NEWSLETTER_USER_EMAIL'     => $arrRecipient['email'],
                    'NEWSLETTER_DOMAIN_URL'     => $_CONFIG['domainUrl'],
                    'NEWSLETTER_CURRENT_DATE'   => date(ASCMS_DATE_FORMAT),
                ),
            );
            if (count($notifyMails)) {
                $arrMailTemplate['to'] = implode(',', $notifyMails);
            }
            if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate)) {
                return false;
            }
            return true;
        }
// TODO: This used to return *nothing* when notifications were turned off.
// Probably true should be returned in this case instead.
// -- See the condition way above.
        return false;
    }



    function setBlock(&$code)
    {
        $html = $this->_getHTML();
        $code = str_replace("{NEWSLETTER_BLOCK}", $html, $code);
    }


    /**
     * displays newsletter contentn in browser
     *
     */
    public static function displayInBrowser()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $id    = !empty($_GET['id'])    ? contrexx_input2raw($_GET['id'])    : '';
        $email = !empty($_GET['email']) ? contrexx_input2raw($_GET['email']) : '';
        $code  = !empty($_GET['code'])  ? contrexx_input2raw($_GET['code'])  : '';
        $crmId = !empty($_GET['cId'])  ? contrexx_input2raw($_GET['cId'])  : '';

        $unsubscribe = '';
        $profile     = '';
        $date        = '';

        $sex         = '';
        $salutation  = '';
        $title       = '';
        $firstname   = '';
        $lastname    = '';
        $position    = '';
        $company     = '';
        $industry_sector = '';
        $address     = '';
        $city        = '';
        $zip         = '';
        $country     = '';
        $phoneOffice = '';
        $phoneMobile = '';
        $phonePrivate = '';
        $fax         = '';
        $birthday    = '';
        $website     = '';

        if (!self::checkCode($id, $email, $code)) {
            // unable to verify user, therefore we will not load any user data to prevent leaking any privacy data
            $email = '';
            $code = '';
        }

        // Get newsletter content and template.
        $query = '
                SELECT `n`.`content`, `n`.`subject`, `t`.`html`, `n`.`date_sent`
                  FROM `'.DBPREFIX.'module_newsletter` as `n`
            INNER JOIN `'.DBPREFIX.'module_newsletter_template` as `t`
                    ON `n`.`template` = `t`.`id`
                 WHERE `n`.`id` = "'.contrexx_raw2db($id).'"
        ';
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount()) {
            $html    = $objResult->fields['html'];
            $content = $objResult->fields['content'];
            $subject = contrexx_raw2xhtml($objResult->fields['subject']);
            $dateSent= $objResult->fields['date_sent'] ? $objResult->fields['date_sent'] : time();
            $date    = date(ASCMS_DATE_FORMAT_DATE, $dateSent);
        } else {
            // newsletter not found > redirect to homepage
            \Cx\Core\Csrf\Controller\Csrf::header('Location: '.\Cx\Core\Routing\Url::fromDocumentRoot());
            exit();
        }
        $crmUser = new \Cx\Modules\Crm\Model\Entity\CrmContact();

        // Get user details.
        $query = '
            SELECT `id`, `email`, `uri`, `salutation`, `title`, `position`, `company`, `industry_sector`, `sex`,
                   `lastname`, `firstname`, `address`, `zip`, `city`, `country_id`,
                   `phone_office`, `phone_mobile`, `phone_private`, `fax`, `birthday`
            FROM `'.DBPREFIX.'module_newsletter_user`
            WHERE `email` = "'.contrexx_raw2db($email).'"
        ';
        $objResult  = $objDatabase->Execute($query);

        if ($objResult->RecordCount()) {
            // set recipient sex
            switch ($objResult->fields['sex']) {
                case 'm':
                    $gender = 'gender_male';
                    break;
                case 'f':
                    $gender = 'gender_female';
                    break;
                default:
                    $gender = 'gender_undefined';
                    break;
            }

            $objUser        = \FWUser::getFWUserObject()->objUser;
            $userId         = $objResult->fields['id'];
            $sex            = $objUser->objAttribute->getById($gender)->getName();

            //$salutation     = contrexx_raw2xhtml($objUser->objAttribute->getById('title_'.$objResult->fields['salutation'])->getName());
            $objNewsletterLib = new NewsletterLib();
            $arrRecipientTitles = $objNewsletterLib->_getRecipientTitles();
            $salutation     = $arrRecipientTitles[$objResult->fields['salutation']];

            $title          = contrexx_raw2xhtml($objResult->fields['title']);
            $firstname      = contrexx_raw2xhtml($objResult->fields['firstname']);
            $lastname       = contrexx_raw2xhtml($objResult->fields['lastname']);
            $position       = contrexx_raw2xhtml($objResult->fields['position']);
            $company        = contrexx_raw2xhtml($objResult->fields['company']);
            $industry_sector= contrexx_raw2xhtml($objResult->fields['industry_sector']);
            $address        = contrexx_raw2xhtml($objResult->fields['address']);
            $city           = contrexx_raw2xhtml($objResult->fields['city']);
            $zip            = contrexx_raw2xhtml($objResult->fields['zip']);
// TODO: migrate to Country class
            $country        = contrexx_raw2xhtml($objUser->objAttribute->getById('country_'.$objResult->fields['country_id'])->getName());
            $phoneOffice    = contrexx_raw2xhtml($objResult->fields['phone_office']);
            $phoneMobile    = contrexx_raw2xhtml($objResult->fields['phone_mobile']);
            $phonePrivate   = contrexx_raw2xhtml($objResult->fields['phone_private']);
            $fax            = contrexx_raw2xhtml($objResult->fields['fax']);
            $website        = contrexx_raw2xhtml($objResult->fields['uri']);
            $birthday       = contrexx_raw2xhtml($objResult->fields['birthday']);

            // unsubscribe and profile links have been removed from browser-view - 12/20/12 TD
            //$unsubscribe        = '<a href="'.\Cx\Core\Routing\Url::fromModuleAndCmd('Newsletter', 'unsubscribe', '', array('code' => $code, 'mail' => $email)).'">'.$_ARRAYLANG['TXT_UNSUBSCRIBE'].'</a>';
            //$profile            = '<a href="'.\Cx\Core\Routing\Url::fromModuleAndCmd('Newsletter', 'profile', '', array('code' => $code, 'mail' => $email)).'">'.$_ARRAYLANG['TXT_EDIT_PROFILE'].'</a>';
        } elseif ($objUser = \FWUser::getFWUserObject()->objUser->getUsers(array('email' => contrexx_raw2db($email), 'active' => 1), null, null, null, 1)) {
            $sex            = $objUser->objAttribute->getById($objUser->getProfileAttribute('gender'))->getName();
            $salutation     = contrexx_raw2xhtml($objUser->objAttribute->getById('title_'.$objUser->getProfileAttribute('title'))->getName());
            $firstname      = contrexx_raw2xhtml($objUser->getProfileAttribute('firstname'));
            $lastname       = contrexx_raw2xhtml($objUser->getProfileAttribute('lastname'));
            $company        = contrexx_raw2xhtml($objUser->getProfileAttribute('company'));
            $address        = contrexx_raw2xhtml($objUser->getProfileAttribute('address'));
            $city           = contrexx_raw2xhtml($objUser->getProfileAttribute('city'));
            $zip            = contrexx_raw2xhtml($objUser->getProfileAttribute('zip'));
// TODO: migrate to Country class
            $country        = contrexx_raw2xhtml($objUser->objAttribute->getById('country_'.$objUser->getProfileAttribute('country'))->getName());
            $phoneOffice    = contrexx_raw2xhtml($objUser->getProfileAttribute('phone_office'));
            $phoneMobile    = contrexx_raw2xhtml($objUser->getProfileAttribute('phone_mobile'));
            $phonePrivate   = contrexx_raw2xhtml($objUser->getProfileAttribute('phone_private'));
            $fax            = contrexx_raw2xhtml($objUser->getProfileAttribute('phone_fax'));
            $website        = contrexx_raw2xhtml($objUser->getProfileAttribute('website'));
            $birthday       = date(ASCMS_DATE_FORMAT_DATE, $objUser->getProfileAttribute('birthday'));

            // unsubscribe and profile links have been removed from browser-view - 12/20/12 TD
            //$unsubscribe = '<a href="'.\Cx\Core\Routing\Url::fromModuleAndCmd('Newsletter', 'unsubscribe', '', array('code' => $code, 'mail' => $email)).'">'.$_ARRAYLANG['TXT_UNSUBSCRIBE'].'</a>';
            //$profile     = '<a href="'.\Cx\Core\Routing\Url::fromModuleAndCmd('Newsletter', 'profile', '', array('code' => $code, 'mail' => $email)).'">'.$_ARRAYLANG['TXT_EDIT_PROFILE'].'</a>';
        } elseif ($crmUser->load($crmId)) {

            $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute
                ->getById('title_' . $crmUser->salutation);
            $salutation = '';
            if (!$objAttribute->EOF) {
                $salutation = $objAttribute->getName();
            }

            $gender = $crmUser->contact_gender == 1
                ? 'gender_female'
                : ($crmUser->contact_gender == 2
                    ? 'gender_male'
                    : 'gender_undefined'
                );
            $email                     = $crmUser->email;
            $lastname                  = $crmUser->family_name;
            $firstname                 = $crmUser->customerName;
            $address                   = $crmUser->address;
            $sex                       = \FWUser::getFWUserObject()->objUser->objAttribute->getById($gender)->getName();
            $company                   = $crmUser->linkedCompany;
            $title                     = $crmUser->contact_title;
            $position                  = $crmUser->contact_role;
            $zip                       = $crmUser->zip;
            $city                      = $crmUser->city;
            $website                   = $crmUser->url;
            $phoneOffice               = $crmUser->phone;

        } else {
            // no user found by the specified e-mail address, therefore we will unset any profile specific data to prevent leaking any privacy data
            $email  = '';
            $code   = '';
        }

        $search = array(
            // meta data
            '[[email]]',
            '[[date]]',
            '[[display_in_browser_url]]',
            '[[subject]]',

            // subscription
            // unsubscribe and profile links have been removed from browser-view - 12/20/12 TD
            '[[unsubscribe]]',
            '[[unsubscribe_url]]',
            '[[profile_setup]]',
            '[[profile_setup_url]]',

            // profile data
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

        $params = array(
            'locale'=> \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
            'code'  => $code,
            'email' => $email,
            'id'    => $id,
        );
        $browserViewUrl = \Cx\Core\Routing\Url::fromApi(
            'Newsletter',
            array('View'),
            $params
        );
        $replace = array(
            // meta data
            $email,
            $date,
            $browserViewUrl->toString(),
            $subject,

            // subscription
            // unsubscribe and profile links have been removed from browser-view - 12/20/12 TD
            // > do empty placeholder
            '',
            '',
            '',
            '',

            // profile data
            $sex,
            $salutation,
            $title,
            $firstname,
            $lastname,
            $position,
            $company,
            $industry_sector,
            $address,
            $city,
            $zip,
            $country,
            $phoneOffice,
            $phoneMobile,
            $phonePrivate,
            $fax,
            $birthday,
            $website,
        );

        // Replaces the placeholder in the template and content.
        $html    = str_replace($search, $replace, $html);
        $content = str_replace($search, $replace, $content);

        // prepare links in content for tracking
        if (is_object($objUser) && $objUser->getId()) {
            $userId = $objUser->getId();
            $userType = self::USER_TYPE_ACCESS;
        } elseif ($crmUser->load($crmId)) {
            $userId = $crmId;
            $userType = self::USER_TYPE_CRM;
        } else {
            $userId = $userId ? $userId : 0;
            $userType = self::USER_TYPE_NEWSLETTER;
        } 

        $langId = FRONTEND_LANG_ID;
        $content = self::prepareNewsletterLinksForSend($id, $content, $userId, $userType, $langId);

        // Finally replace content placeholder in the template.
        $html = str_replace('[[content]]', $content, $html);

        // parse node-url placeholders
        \LinkGenerator::parseTemplate($html);

        // Output
        die($html);
    }


    /**
     * checks if given code matches given email adress
     *
     * @param   id       $$id
     * @param   string   $email
     * @param   string   $code
     * @return  boolean
     */
    private static function checkCode($id, $email, $code){
        global $objDatabase;

        $query = 'SELECT `code` FROM `'.DBPREFIX.'module_newsletter_tmp_sending` WHERE `newsletter` = '.$id.' AND `email` = "'.$email.'";';
        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            if($objResult->fields['code'] == $code) {
                return true;
            }
        }

        return false;
    }

    public static function isTrackLink() {
        if (!isset($_GET['n'])) {
            return false;
        }
        if (!isset($_GET['l'])) {
            return false;
        }
        if (!isset($_GET['r']) && !isset($_GET['m']) && !isset($_GET['c'])) {
            return false;
        }
        return true;
    }

    /**
     * track link: save feedback to database
     *
     * @return boolean
     */
    public static function trackLink()
    {
        global $objDatabase;

        $profileUrl = '';
        $unsubscribeUrl = '';
        $recipientId = 0;
        if (isset($_GET['m'])) {
            $recipientId = intval($_GET['m']);
            $recipientType = NewsletterLib::USER_TYPE_NEWSLETTER;
        } else if (isset($_GET['r'])) {
            $recipientId = intval($_GET['r']);
            $recipientType = NewsletterLib::USER_TYPE_ACCESS;
        } else if (isset($_GET['c'])) {
            $recipientId = intval($_GET['c']);
            $recipientType = NewsletterLib::USER_TYPE_CRM;
        } else {
            return false;
        }
        $emailId = isset($_GET['n']) ? intval($_GET['n']) : 0;
        $linkId = isset($_GET['l']) ? intval($_GET['l']) : 0;

        if (!empty($recipientId)) {
            // find out recipient type
            if ($recipientType == NewsletterLib::USER_TYPE_ACCESS) {
                $objUser = \FWUser::getFWUserObject()->objUser->getUser(intval($recipientId));
                $recipientId = null;
                if ($objUser !== false) {
                    $recipientId = $objUser->getId();
                }
            } elseif ($recipientType == NewsletterLib::USER_TYPE_CRM) {
                $crmUser = new \Cx\Modules\Crm\Model\Entity\CrmContact();
                if (!$crmUser->load($recipientId)) {
                    $recipientId = null;
                }
            } elseif ($recipientType == NewsletterLib::USER_TYPE_NEWSLETTER) {
                $objUser = $objDatabase->SelectLimit("SELECT `id` FROM ".DBPREFIX."module_newsletter_user WHERE id='".contrexx_raw2db($recipientId)."'", 1);
                $recipientId = null;
                if (!($objUser === false || $objUser->RecordCount() != 1)) {
                    $recipientId = $objUser->fields['id'];
                }
            }
        }

        /*
         * Request must be redirected to the newsletter $linkId URL. If the $linkId
         * can't be looked up in the database (by what reason  so ever), then the request shall be
         * redirected to the URL provided by the url-modificator s of the request
         */
        $objLink = $objDatabase->SelectLimit("SELECT `url` FROM ".DBPREFIX."module_newsletter_email_link WHERE id=".contrexx_raw2db($linkId)." AND email_id=".contrexx_raw2db($emailId), 1);
        if ($objLink === false || $objLink->RecordCount() != 1) {
            return false;
        }

        $url = $objLink->fields['url'];

        \LinkGenerator::parseTemplate($url);

        $arrSettings = static::_getSettings();
        if (!$arrSettings['statistics']['setvalue']) {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: '.$url);
            exit;
        }

        if (!empty($recipientId)) {
            // save feedback for valid user
            $query = "
                INSERT IGNORE INTO ".DBPREFIX."module_newsletter_email_link_feedback (link_id, email_id, recipient_id, recipient_type)
                VALUES (".contrexx_raw2db($linkId).", ".contrexx_raw2db($emailId).", ".contrexx_raw2db($recipientId).", '".contrexx_raw2db($recipientType)."')
            ";
            $objDatabase->Execute($query);
        }

        if (!empty($recipientType)) {
            $code = null;
            $email = null;
            // code taken from NewsletterManager::getNewsletterUserData()
            switch ($recipientType) {
                case self::USER_TYPE_ACCESS:
                    $objUser = \FWUser::getFWUserObject()->objUser->getUser($id);
                    if (!$objUser) {
                        break;
                    }
                    $email = $objUser->getEmail();
                    $query = "
                        SELECT code
                          FROM ".DBPREFIX."module_newsletter_access_user
                         WHERE accessUserID = $recipientId";
                    $result = $objDatabase->SelectLimit($query, 1);
                    if ($result && !$result->EOF) {
                        $code = $result->fields['code'];
                    }
                    break;

                case self::USER_TYPE_CORE:
                case self::USER_TYPE_CRM:
                    break;

                case self::USER_TYPE_NEWSLETTER:
                default:
                    $query = "
                        SELECT code, email
                          FROM ".DBPREFIX."module_newsletter_user
                         WHERE id=$recipientId";
                    $result = $objDatabase->Execute($query);
                    if ($result && !$result->EOF) {
                        $code = $result->fields['code'];
                        $email = $result->fields['email'];
                    }
                    break;

            }

            if (!empty($code) && !empty($email)) {
                $nm = new \Cx\Modules\Newsletter\Controller\NewsletterLib();
                $profileUrl = $nm->GetProfileURL($code, $email, $recipientType, false);
                $unsubscribeUrl = $nm->GetUnsubscribeURL($code, $email, $recipientType, false);
            }
        }

        $arrPlaceholders = array(
            '[[profile_setup_url]]' => $profileUrl,
            '[[unsubscribe_url]]'   => $unsubscribeUrl,
        );
        $url = str_replace(
            array_keys($arrPlaceholders),
            $arrPlaceholders,
            $url
        );

        if (empty($url)) {
            $url = \Cx\Core\Routing\Url::fromModuleAndCmd('Error');
        }

        \Cx\Core\Csrf\Controller\Csrf::header('Location: '.$url);
        exit;
    }
}
