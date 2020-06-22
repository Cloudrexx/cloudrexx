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
 * Calendar Class Mail Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx <info@cloudrexx.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_calendar
 */

namespace Cx\Modules\Calendar\Controller;
/**
 * CalendarMailManager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx <info@cloudrexx.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
class CalendarMailManager extends CalendarLibrary {
    /**
     * Mail list array
     *
     * @access public
     * @var array
     */
    public $mailList = array();

    /**
     * Mail action Invitation
     *
     * Default recipient: none
     */
    const MAIL_INVITATION    = 1;

    /**
     * Mail Action Confirm registration
     *
     * Default recipient: author
     */
    const MAIL_CONFIRM_REG   = 2;

    /**
     * Mail Action Alert registration
     *
     * Default recipient: none
     */
    const MAIL_ALERT_REG     = 3;

    /**
     * mail action notify new appoinment
     *
     * Default recipient: admin
     */
    const MAIL_NOTFY_NEW_APP = 4;

    /**
     * Send the invitation mail to all contacts
     * This is the default option
     */
    const MAIL_INVITATION_TO_ALL = 'all';

    /**
     * Send the invitation mail only to registered in contacts
     */
    const MAIL_INVITATION_TO_REGISTERED = 'registered';

    /**
     * Send the invitation mail only to signed in contacts
     */
    const MAIL_INVITATION_TO_SIGNEDIN_FILTERED = 'signedIn';

    /**
     * Send the invitation mail only to inactive contacts
     *
     * Default: all
     */
    const MAIL_INVITATION_TO_INACTIVE = 'inactive';

    /**
     * Send the invitation mail only to new contacts that have not yet
     * received an invitation
     */
    const MAIL_INVITATION_TO_NEW = 'new';

    /**
     * Notification mail types
     *
     * @var array
     */
    protected $mailActions = array();

    /**
     * Constructor
     */
    function __construct()
    {
        global $_ARRAYLANG;

        $this->mailActions = array(
            static::MAIL_INVITATION     => $_ARRAYLANG['TXT_CALENDAR_MAIL_ACTION_INVITATIONTEMPLATE'],
            static::MAIL_CONFIRM_REG    => $_ARRAYLANG['TXT_CALENDAR_MAIL_ACTION_CONFIRMATIONREGISTRATION'],
            static::MAIL_ALERT_REG      => $_ARRAYLANG['TXT_CALENDAR_MAIL_ACTION_NOTIFICATIONREGISTRATION'],
            static::MAIL_NOTFY_NEW_APP  => $_ARRAYLANG['TXT_CALENDAR_MAIL_ACTION_NOTIFICATIONNEWENTRYFE'],
        );

        $this->getFrontendLanguages();
        $this->init();
    }

    /**
     * Get list (as array) of available notification mail types
     *
     * @return array Returns the list ($this->mailActions) of available
     *               notification mail types
     */
    public function getMailActions() {
        return $this->mailActions;
    }

    /**
     * Return's the mailing list
     *
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     * @global integer $_LANGID
     * @return array Return's the mailing list
     */
    function getMailList()
    {
        global $objDatabase,$_ARRAYLANG,$_LANGID;

        $query = "SELECT id
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_mail
                ORDER BY action_id ASC, title ASC";

        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($objResult->fields['id']));
                $this->mailList[] = $objMail;
                $objResult->MoveNext();
            }
        }
    }

    /**
     * Set the mailing list placeholders to the template
     *
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     * @param object $objTpl
     */
    function showMailList($objTpl)
    {
        global $objDatabase, $_ARRAYLANG;

        $i=0;
        foreach ($this->mailList as $key => $objMail) {
            foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                if($arrLang['id'] == $objMail->lang_id) {
                    $langName = $arrLang['name'];
                }
            }

            $action = $this->mailActions[$objMail->action_id];

            if($objMail->is_default == 1) {
                $isDefault = 'checked="checked"';
            } else {
                $isDefault = '';
            }

            $objTpl->setVariable(array(
                $this->moduleLangVar.'_TEMPLATE_ROW'       => $i%2==0 ? 'row1' : 'row2',
                $this->moduleLangVar.'_TEMPLATE_ID'              => $objMail->id,
                $this->moduleLangVar.'_TEMPLATE_STATUS'          => $objMail->status==0 ? 'red' : 'green',
                $this->moduleLangVar.'_TEMPLATE_LANG'            => $langName,
                $this->moduleLangVar.'_TEMPLATE_TITLE'           => $objMail->title,
                $this->moduleLangVar.'_TEMPLATE_ACTION'          => $action,
                $this->moduleLangVar.'_TEMPLATE_DEFAULT'         => $isDefault,
                $this->moduleLangVar.'_TEMPLATE_DEFAULT_NAME'    => "isDefault_".$objMail->action_id,
            ));

            $i++;
            $objTpl->parse('templateList');
        }

        if(count($this->mailList) == 0) {
            $objTpl->hideBlock('templateList');

            $objTpl->setVariable(array(
                'TXT_CALENDAR_NO_TEMPLATES_FOUND' => $_ARRAYLANG['TXT_CALENDAR_NO_TEMPLATES_FOUND'],
            ));

            $objTpl->parse('emptyTemplateList');
        }
    }

    /**
     * Sets the mail placeholders to the template
     *
     * @global object $objInit
     * @global array $_ARRAYLANG
     * @param object $objTpl
     * @param integer $mailId
     */
    function showMail($objTpl, $mailId)
    {
        global $objInit, $_ARRAYLANG;

        $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($mailId));
        $this->mailList[$mailId] = $objMail;

        $objTpl->setVariable(array(
            $this->moduleLangVar.'_TEMPLATE_ID'              => $objMail->id,
            $this->moduleLangVar.'_TEMPLATE_ACTION'          => $objMail->action_id,
            $this->moduleLangVar.'_TEMPLATE_LANG'            => $objMail->lang_id,
            $this->moduleLangVar.'_TEMPLATE_RECIPIENTS'      => $objMail->recipients,
            $this->moduleLangVar.'_TEMPLATE_TITLE'           => $objMail->title,
            $this->moduleLangVar.'_TEMPLATE_CONTENT_TEXT'    => stripslashes($objMail->content_text),
            $this->moduleLangVar.'_TEMPLATE_CONTENT_HTML'    => $objMail->content_html,
        ));
    }

    /**
     * Initialize the mail functionality to the recipient
     *
     * @param \Cx\Modules\Calendar\Controller\CalendarEvent $event          Event instance
     * @param integer   $actionId               Mail action id
     * @param integer   $regId                  Registration id
     * @param array     $arrMailTemplateIds     Prefered templates of the specified action to be sent
     * @param array     $sendInvitationTo       Which group of contacts should get the mail
     */
    function sendMail(
        CalendarEvent $event,
        $actionId,
        $regId = null,
        $arrMailTemplateIds = array(),
        $sendInvitationTo = CalendarMailManager::MAIL_INVITATION_TO_ALL
    ) {
        global $_ARRAYLANG, $_CONFIG ;

        $db = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
        $this->mailList = array();

        // Loads the mail template which needs for this action
        $this->loadMailList($actionId, $arrMailTemplateIds);

        if (empty($this->mailList)) {
            return;
        }

        $objRegistration = null;
        if ($actionId == self::MAIL_CONFIRM_REG || $actionId == self::MAIL_ALERT_REG) {
            if (empty($regId)) {
                return;
            }

            $objRegistration = new \Cx\Modules\Calendar\Controller\CalendarRegistration($event->registrationForm, $regId);
            list($registrationDataText, $registrationDataHtml) = $this->getRegistrationData($objRegistration);

            $query = 'SELECT `v`.`value`, `n`.`default`, `f`.`type`
                      FROM '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value AS `v`
                      INNER JOIN '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name AS `n`
                      ON `v`.`field_id` = `n`.`field_id`
                      INNER JOIN '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field AS `f`
                      ON `v`.`field_id` = `f`.`id`
                      WHERE `v`.`reg_id` = '.$regId.'
                      AND (
                             `f`.`type` = "salutation"
                          OR `f`.`type` = "firstname"
                          OR `f`.`type` = "lastname"
                          OR `f`.`type` = "mail"
                      )';
            $objResult = $db->Execute($query);

            $arrDefaults = array();
            $arrValues   = array();
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    if (!empty($objResult->fields['default'])) {
                        $arrDefaults[$objResult->fields['type']] = explode(',', $objResult->fields['default']);
                    }
                    $arrValues[$objResult->fields['type']] = $objResult->fields['value'];
                    $objResult->MoveNext();
                }
            }

            $regSalutation = !empty($arrValues['salutation']) ? $arrDefaults['salutation'][$arrValues['salutation'] - 1] : '';
            $regFirstname  = !empty($arrValues['firstname'])  ? $arrValues['firstname'] : '';
            $regLastname   = !empty($arrValues['lastname'])   ? $arrValues['lastname']  : '';
            $regMail       = !empty($arrValues['mail'])       ? $arrValues['mail']      : '';
            $regType       = $objRegistration->type == 1 ? $_ARRAYLANG['TXT_CALENDAR_REG_REGISTRATION'] : $_ARRAYLANG['TXT_CALENDAR_REG_SIGNOFF'];

            $regSearch     = array('[[REGISTRATION_TYPE]]', '[[REGISTRATION_SALUTATION]]', '[[REGISTRATION_FIRSTNAME]]', '[[REGISTRATION_LASTNAME]]', '[[REGISTRATION_EMAIL]]');
            $regReplace    = array(      $regType,                 $regSalutation,                $regFirstname,                $regLastname,                $regMail);
        }

        $domain     = ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET."/";
        $date       = $this->format2userDateTime(new \DateTime());
        $startDate  = $event->startDate;
        $endDate    = $event->endDate;

        $eventTitle = $event->title;
        $eventStart = $event->all_day ? $this->format2userDate($startDate) : $this->formatDateTime2user($startDate, $this->getDateFormat() . ' (H:i:s)');
        $eventEnd   = $event->all_day ? $this->format2userDate($endDate) : $this->formatDateTime2user($endDate, $this->getDateFormat() . ' (H:i:s)');

        $placeholder = array('[[TITLE]]', '[[START_DATE]]', '[[END_DATE]]', '[[LINK_ATTACHMENT]]', '[[LINK_EVENT]]', '[[LINK_REGISTRATION]]', '[[USERNAME]]', '[[SALUTATION]]', '[[FIRSTNAME]]', '[[LASTNAME]]', '[[URL]]', '[[DATE]]');

        $recipients = $this->getSendMailRecipients($actionId, $event, $regId, $objRegistration, $sendInvitationTo);

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();
        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreGlobalPageTitle']);

        switch ($actionId) {
            case self::MAIL_ALERT_REG:
                if (empty($regMail)) {
                    break;
                }
                $objMail->addReplyTo($regMail);
                break;

            // In case we're about to send out event invitations,
            // do check if any have been sent already and do load
            // them in such case.
            case self::MAIL_INVITATION:
            case self::MAIL_CONFIRM_REG:
                $eventRepo = $this->em->getRepository('Cx\Modules\Calendar\Model\Entity\Event');
                $eventByDoctrine = $eventRepo->findOneById($event->id);

                $inviteRepo = $this->em->getRepository('Cx\Modules\Calendar\Model\Entity\Invite');

                // this should not happen!
                if (!$eventByDoctrine) {
                    return;
                }
                break;

            default:
                break;
        }

        // fetch active frontend languages
        $this->getFrontendLanguages();

        // fetch published locales of event
        $publishedLanguages = explode(',',$event->showIn);

        // send out mail for each recipient
        foreach ($recipients as $recipient) {
            // event invitation
            $invite = null;

            // abort in case recipient's mail address is invalid
            if (\FWValidator::isEmpty($recipient->getAddress()) || !\FWValidator::isEmail($recipient->getAddress())) {
                continue;
            }

            // let's see if there exists a user account by the provided e-mail address
            if ($recipient->getType() == MailRecipient::RECIPIENT_TYPE_MAIL) {
                $objUser = \FWUser::getFWUserObject()->objUser->getUsers($filter = array('email' => $recipient->getAddress(), 'is_active' => true));
                if ($objUser) {
                    // convert recipient to an Access User recipient
                    $recipient->setLang($objUser->getFrontendLanguage());
                    $recipient->setType(MailRecipient::RECIPIENT_TYPE_ACCESS_USER);
                    $recipient->setId($objUser->getId());
                    $recipient->setSalutationId($objUser->getProfileAttribute('title'));
                    $recipient->setFirstname($objUser->getProfileAttribute('firstname'));
                    $recipient->setLastname($objUser->getProfileAttribute('lastname'));
                    $recipient->setUsername($objUser->getUsername());
                } else {
                    if (!empty($regId) && $recipient->getAddress() == $regMail) {
                        $recipient->setFirstname($regFirstname);
                        $recipient->setLastname($regLastname);
                    }
                }
            }

            // find existing locale of notification mail
            // that best fits recipient
            $langId = $this->getSendMailLangId($actionId, $recipient);

            // fetch mail template data
            $template = $this->mailList[$langId]['mail'];
            $mailTitle = $template->title;
            $mailContentText = !empty($template->content_text) ? $template->content_text : strip_tags($template->content_html);
            $mailContentHtml = !empty($template->content_html) ? $template->content_html : $template->content_text;

            // re-fetch recipient's prefered language,
            // in case it was not available as mail template
            if (empty($langId)) {
                $langId = $recipient->getLang();
            }

            // verify that prefered language is available as content in frontend
            if (   !isset($this->arrFrontendLanguages[$langId])
                || (   $this->arrSettings['showEventsOnlyInActiveLanguage'] == 1
                    && !in_array($langId, $publishedLanguages)
                )
            ) {
                $langId = FRONTEND_LANG_ID;
            }

            // default params
            $params = array(
                \CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_EVENT  => $event->id,
                \CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_DATE   => $event->startDate->getTimestamp(),
                \CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_RANDOM => time(),
            );

            // URL pointing to the event subscription page
            $regLink = '';

            // URL pointing to the event's attachment
            $linkAttachment = '';
            if (!empty($event->attach)) {
                // parse node links
                $attachment = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $event->attach);
                $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
                \LinkGenerator::parseTemplate($attachment, true, $domainRepo->getMainDomain());

                // make link absolute
                $attachmentUrl = \Cx\Core\Routing\Url::fromMagic($attachment);
                $linkAttachment = $attachmentUrl->toString();
            }

            // URL pointing to the event's detail page
            $eventLink = '';

            // generate event and registration link based on requested mail action
            switch ($actionId) {
                case self::MAIL_INVITATION:
                    // check if an invitation to the recipient
                    // has been sent before
                    $findCriteria = array(
                        'event'        => $eventByDoctrine,
                        'inviteeType'  => $recipient->getType(),
                    );

                    // Virtual recipients (identified only by email address) will
                    // be looked up only by their email address.
                    // Physical recipients (origin from a component) will be looked
                    // up by their entity ID.
                    if ($recipient->getType() == MailRecipient::RECIPIENT_TYPE_MAIL) {
                        $findCriteria['email'] = $recipient->getAddress();
                    } else {
                        $findCriteria['inviteeId'] = $recipient->getId();
                    }

                    $invite = $inviteRepo->findOneBy($findCriteria);

                    // store invitation to db, in case no intivation
                    // has been sent before
                    if (!$invite) {
                        $invite = new \Cx\Modules\Calendar\Model\Entity\Invite();
                        $invite->setEvent($eventByDoctrine);
                        // note: we need to use $event->startDate here,
                        // instead of $eventByDoctrine->getStartDate(),
                        // as $eventByDoctrine->getStartDate() does not use UTC as timezone
                        $invite->setDate($event->startDate);
                        $invite->setInviteeType($recipient->getType());
                        $invite->setInviteeId($recipient->getId());
                        $invite->setEmail($recipient->getAddress());
                        $invite->setToken($this->generateKey());
                        $this->em->persist($invite);
                        $this->em->flush();
                    }

                    $params[\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_ID] = $invite->getId();
                    $params[\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_TOKEN] = $invite->getToken();
                    $eventLink = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'detail', $langId, $params)->toString();
                    $regLink   = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'register', $langId, $params)->toString();
                    break;

                case self::MAIL_CONFIRM_REG:
                    // id of the invite
                    $inviteId = $objRegistration->getInvite()->getId();

                    // token of the invite
                    $inviteToken = $objRegistration->getInvite()->getToken();

                    if (!empty($inviteId) && !empty($inviteToken)) {
                        // fetch the invitation
                        $invite = $inviteRepo->findOneBy(array(
                            'event' => $eventByDoctrine,
                            'id'    => $inviteId,
                            'token' => $inviteToken,
                        ));
                    }

                    if ($invite) {
                        $params[\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_ID] = $invite->getId();
                        $params[\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_TOKEN] = $invite->getToken();
                    }

                    $eventLink = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'detail', $langId, $params)->toString();
                    break;

                case self::MAIL_NOTFY_NEW_APP:
                    if ($event->arrSettings['confirmFrontendEvents'] == 1) {
                        $eventLink = $domain."cadmin/index.php?cmd={$this->moduleName}&act=modify_event&id={$event->id}&confirm=1";
                        break;
                    }

                    // intentinally no break here
                default:
                    $eventLink = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'detail', $langId, $params)->toString();
                    break;
            }

            if (empty($regLink)) {
                $regLink   = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'register', $langId, $params)->toString();
            }

            $salutation = '';
            $salutationId = $recipient->getSalutationId();
            if (!empty($salutationId)) {
                // load the title profile attributes from access user
                $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute->getById('title_' . $salutationId);
                if (!$objAttribute->EOF) {
                    $salutation = $objAttribute->getName($langId);
                }
            }
            $replaceContent  = array($eventTitle, $eventStart, $eventEnd, $linkAttachment, $eventLink, $regLink, $recipient->getUsername(), $salutation, $recipient->getFirstname(), $recipient->getLastname(), $domain, $date);

            $mailTitle       = str_replace($placeholder, array_map('contrexx_xhtml2raw', $replaceContent), $mailTitle);
            $mailContentText = str_replace($placeholder, array_map('contrexx_xhtml2raw', $replaceContent), $mailContentText);
            $mailContentHtml = str_replace($placeholder, $replaceContent, $mailContentHtml);

            if (!empty($regId)) {
                $mailTitle       = str_replace($regSearch, array_map('contrexx_xhtml2raw', $regReplace), $mailTitle);
                $mailContentText = str_replace($regSearch, array_map('contrexx_xhtml2raw', $regReplace), $mailContentText);
                $mailContentHtml = str_replace($regSearch, $regReplace, $mailContentHtml);

                $mailContentText = str_replace('[[REGISTRATION_DATA]]', $registrationDataText, $mailContentText);
                $mailContentHtml = str_replace('[[REGISTRATION_DATA]]', $registrationDataHtml, $mailContentHtml);
            }

            $objMail->Subject = $mailTitle;
            $objMail->Body    = $mailContentHtml;
            $objMail->AltBody = $mailContentText;
            $objMail->AddAddress($recipient->getAddress());
            $objMail->Send();
            $objMail->ClearAddresses();
        }
    }

    /**
     * Loads the mail template for the give action
     *
     * @param integer $actionId     Mail action see CalendarMailManager:: const vars
     * @param array $arrMailTemplateIds Specific Mail template ids to load
     */
    private function loadMailList($actionId, $arrMailTemplateIds)
    {
        global $objDatabase;

        $whereId = '';

        if (!empty($arrMailTemplateIds)) {
            $whereId = 'AND id IN (' . join(',', $arrMailTemplateIds) . ')';
        }

        $query = "SELECT id, lang_id, is_default, recipients
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_mail
                   WHERE action_id='".intval($actionId)."'
                     AND status='1'
                         $whereId
                ORDER BY is_default DESC";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $langId = $objResult->fields['lang_id'];

                $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($objResult->fields['id']));

                // load additional recipients of mail template
                $supRecipients = explode(",", $objResult->fields['recipients']);
                $this->mailList[$langId]['recipients'] = array();
                foreach ($supRecipients as $mail) {
                    if (empty($mail) || !\FWValidator::isEmail($mail)) {
                        continue;
                    }

                    $this->mailList[$langId]['recipients'][$mail] = $langId;
                }

                // store mail template in list of loaded mail templates
                $this->mailList[$langId]['mail'] = $objMail;

                // in case mail template is set as default template for the current action,
                // then it shall be set for fallback language '0'
                if($objResult->fields['is_default'] == 1) {
                    $this->mailList[0] = $this->mailList[$langId];
                }

                $objResult->MoveNext();
            }
        }
    }

    /**
     * Returns the array recipients count
     *
     * @param integer $actionId          Mail Action
     * @param object  $objEvent          Event object
     * @param integer $regId             registration id
     * @param object  $objRegistration   Registration object
     * @param string  $sendInvitationTo  The filter to which contacts the
     *                                   mail should be sent
     *
     * @return integer                  returns the array recipients count
     */
    public function getSendMailRecipientsCount(
        $actionId,
        $objEvent,
        $regId = 0,
        $objRegistration = null,
        $sendInvitationTo = self::MAIL_INVITATION_TO_ALL
    )
    {
        return count(
            $this->getSendMailRecipients(
                $actionId, $objEvent, $regId, $objRegistration, $sendInvitationTo
            )
        );
    }

    /**
     * Returns the array recipients
     *
     * @param integer $actionId         Mail Action
     * @param object  $objEvent         Event object
     * @param integer $regId            registration id
     * @param object  $objRegistration  Registration object
     * @param string  $sendInvitationTo The filter to which contacts the
     *
     * @throws \Cx\Modules\Calendar\Controller\CalendarException if type is
     * invalid or processing fails
     *
     * @return array returns the array recipients
     */
    private function getSendMailRecipients(
        $actionId,
        $objEvent,
        $regId = 0,
        $objRegistration = null,
        $sendInvitationTo = self::MAIL_INVITATION_TO_ALL
    ) {
        global $_CONFIG, $_LANGID;

        $recipients = array();
        $db = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();

        switch ($actionId) {
            case static::MAIL_INVITATION:

                if ($sendInvitationTo == self::MAIL_INVITATION_TO_REGISTERED) {
                    $recipients = $objEvent->getRegistrationMailRecipients();
                    break;
                }

                // fetch manually invited users
                $invitedMails = explode(",", $objEvent->invitedMails);
                foreach ($invitedMails as $mail) {
                    if (!empty($mail)) {
                        $recipients[$mail] = (new MailRecipient())->setLang($_LANGID)->setAddress($mail);
                    }
                }

                // fetch users from Crm groups
                $excludeQuery = '';

                if ($objEvent->excludedCrmGroups) {
                    $excludeQuery = '
                        AND `crm_contact`.`id` NOT IN (
                            SELECT m.`contact_id`
                            FROM `' . DBPREFIX . 'module_crm_customer_membership` AS m
                            WHERE m.`membership_id` IN ('
                        . join(',', $objEvent->excludedCrmGroups) . ')
                        )
                        AND (`crm_company`.`id` IS NULL
                            OR `crm_company`.`id` NOT IN (
                                SELECT m.`contact_id`
                                FROM `' . DBPREFIX . 'module_crm_customer_membership` AS m
                                WHERE m.`membership_id` IN ('
                        . join(',', $objEvent->excludedCrmGroups) . ')
                            )
                        )';
                }
                $result = $db->Execute('
                    SELECT
                         crm_contact.id
                    FROM '.
                        // select CRM Person (contact_type = 2)
                        '`'.DBPREFIX.'module_crm_contacts` AS `crm_contact` '.

                        // join the CRM Memberships of the CRM Person
                        'LEFT JOIN `'.DBPREFIX.'module_crm_customer_membership` AS `crm_contact_membership`
                            ON `crm_contact_membership`.`contact_id` = `crm_contact`.`id` '.

                        // join with CRM Company (contact_type = 1)
                        'LEFT JOIN `'.DBPREFIX.'module_crm_contacts` AS `crm_company`
                            ON `crm_company`.`id` = `crm_contact`.`contact_customer`
                            AND `crm_company`.`contact_type` = 1 '.

                        // join the CRM Memberships of the CRM Company
                        'LEFT JOIN `'.DBPREFIX.'module_crm_customer_membership` AS `crm_company_membership`
                            ON `crm_company_membership`.`contact_id` = `crm_company`.`id` '.

                        // only select users of which the associated CRM Person or CRM Company has the selected CRM membership
                    'WHERE
                       `crm_contact`.`contact_type` = 2
                        AND
                             (   
                             `crm_contact_membership`.`membership_id` IN (' . join(',', $objEvent->invitedCrmGroups) . ')
                              OR `crm_company_membership`.`membership_id` IN (' . join(',', $objEvent->invitedCrmGroups) . ')
                              ) ' . $excludeQuery
                );
                if ($result !== false) {
                    $crmContact = new \Cx\Modules\Crm\Model\Entity\CrmContact();
                    while (!$result->EOF) {
                        if (!$crmContact->load($result->fields['id'])) {
                            $result->MoveNext();
                            continue;
                        }

                        $recipients[$crmContact->email] = (new MailRecipient())
                            ->setLang($crmContact->contact_language)
                            ->setAddress($crmContact->email)
                            ->setType(MailRecipient::RECIPIENT_TYPE_CRM_CONTACT)
                            ->setId($crmContact->id)
                            ->setSalutationId($crmContact->salutation)
                            ->setFirstname($crmContact->customerName)
                            ->setLastname($crmContact->family_name);
                        $result->MoveNext();
                    }
                }

                // fetch users from Access groups, if any are set
                if (!count($objEvent->invitedGroups)) {
                    break;
                }

                // only fetch active users
                $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array('active' => 1));
                if (!$objUser) {
                    break;
                }

                while (!$objUser->EOF) {
                    foreach ($objUser->getAssociatedGroupIds() as $groupId) {
                        if (in_array($groupId, $objEvent->invitedGroups))  {
                            $recipients[$objUser->getEmail()] = (new MailRecipient())
                                ->setLang($objUser->getFrontendLanguage())
                                ->setAddress($objUser->getEmail())
                                ->setType(MailRecipient::RECIPIENT_TYPE_ACCESS_USER)
                                ->setId($objUser->getId())
                                ->setSalutationId($objUser->getProfileAttribute('title'))
                                ->setFirstname($objUser->getProfileAttribute('firstname'))
                                ->setLastname($objUser->getProfileAttribute('lastname'))
                                ->setUsername($objUser->getUsername());
                        }
                    }
                    $objUser->next();
                }
                break;

            case static::MAIL_CONFIRM_REG:
                // abort in case no registration-data is present
                if (empty($regId) || empty($objRegistration)) {
                    break;
                }

                // add currently signed-in user as recipient
                if (!empty($objRegistration->userId)) {
                    $objFWUser = \FWUser::getFWUserObject();
                    if ($objUser = $objFWUser->objUser->getUser($id = intval($objRegistration->userId))) {
                        $recipients[$objUser->getEmail()] = (new MailRecipient())
                            ->setLang($objUser->getFrontendLanguage())
                            ->setAddress($objUser->getEmail())
                            ->setType(MailRecipient::RECIPIENT_TYPE_ACCESS_USER)
                            ->setId($objUser->getId())
                            ->setSalutationId($objUser->getProfileAttribute('title'))
                            ->setFirstname($objUser->getProfileAttribute('firstname'))
                            ->setLastname($objUser->getProfileAttribute('lastname'))
                            ->setUsername($objUser->getUsername());
                    }
                }

                // add recipient based on form data (field 'mail')
                foreach ($objRegistration->fields as $arrField) {
                    if ($arrField['type'] == 'mail' && !empty($arrField['value'])) {
                        $recipients[$arrField['value']] = (new MailRecipient())->setLang(isset($this->mailList[$_LANGID]) ? $_LANGID : 0)->setAddress($arrField['value']);
                    }
                }

                // set user that submitted the registration as such
                if (   $objRegistration->getInvite()->getInviteeType() == MailRecipient::RECIPIENT_TYPE_MAIL
                    && \FWValidator::isEmpty($objRegistration->getInvite()->getEmail())
                    && count($recipients)
                ) {
                    $participant = end($recipients);
                    $objRegistration->getInvite()->setEmail($participant->getAddress());
                    // TODO: this is a workaround
                    // Due to the existance of both, the legacy and doctrine
                    // model, we have to make the following change through
                    // legacy SQL. As otherwise ($this->em->flush()) would throw
                    // an exception, as the associated Event entity has been
                    // detachted by the legacy event system in Calendar.
                    // As soon as the legacy model has been dropped, the
                    // following code can be removed as well: 
                    $inviteId = $objRegistration->getInvite()->getId();
                    if ($inviteId) {
                        $db->Execute('UPDATE '.DBPREFIX.'module_calendar_invite SET `email` = \'' . contrexx_raw2db($participant->getAddress()) . '\' WHERE id = '. $inviteId);
                        $this->em->getConfiguration()->getResultCacheImpl()->deleteAll();
                    }
                    // This would be the proper statement, once the legacy
                    // model has been removed:
                    // $this->em->flush();
                }
                break;

            case static::MAIL_ALERT_REG:
                // add recipients specifically set to be notified by the current event
                $notificationEmails = explode(",", $objEvent->notificationTo);

                foreach ($notificationEmails as $mail) {
                    $recipients[$mail] = (new MailRecipient())->setLang($_LANGID)->setAddress($mail);
                }
                break;

            case static::MAIL_NOTFY_NEW_APP:
                // add website-administrator as recipient
                $recipients[$_CONFIG['coreAdminEmail']] = (new MailRecipient())->setLang($_LANGID)->setAddress($_CONFIG['coreAdminEmail']);
                break;

            default:
        }

        // add recipients specified by option 'Additional recipients' of each loaded mail template
        foreach ($this->mailList as $langId => $mailList) {
            foreach ($mailList['recipients'] as $email => $langId) {
                $recipients[$email] = (new MailRecipient())->setLang($langId)->setAddress($email);
            }
        }

        if (
            $sendInvitationTo != self::MAIL_INVITATION_TO_ALL &&
            $actionId == static::MAIL_INVITATION
        ) {

            // get all guests which are on any list
            $query = 'SELECT `v`.`value` AS `mail`
                        FROM `'.DBPREFIX.'module_calendar_registration_form_field_value` AS `v`
                        INNER JOIN `'.DBPREFIX.'module_calendar_registration_form_field` AS `f`
                          ON `v`.`field_id` = `f`.`id`
                        INNER JOIN `'.DBPREFIX.'module_calendar_registration` AS `r`
                          ON `v`.`reg_id` = `r`.`id`
                        WHERE `r`.`event_id` = ' . $objEvent->getId() . '
                        AND `f`.`type` = \'mail\'';

            switch ($sendInvitationTo) {
                case self::MAIL_INVITATION_TO_INACTIVE:
                    // exclude all guests which are already registered on any list
                    $result = $db->Execute($query);
                    if ($result === false) {
                        throw new CalendarException(
                            'Unable to process invitation type ' . $sendInvitationTo
                        );
                    }
                    while (!$result->EOF) {
                        // delete all registered guests out of the recipients
                        unset($recipients[$result->fields['mail']]);
                        $result->MoveNext();
                    }
                    break;

                case self::MAIL_INVITATION_TO_SIGNEDIN_FILTERED:
                    // only get signed in
                    $query .= ' AND `r`.`type` = 1';
                    $signedinRecipients = array();
                    $result = $db->Execute($query);
                    if ($result === false) {
                        throw new CalendarException(
                            'Unable to process invitation type ' . $sendInvitationTo
                        );
                    }
                    while (!$result->EOF) {
                        $signedinRecipients[$result->fields['mail']] = '';
                        $result->MoveNext();
                    }
                    $recipients = array_intersect_key(
                        $recipients,
                        $signedinRecipients
                    );
                    break;

                case self::MAIL_INVITATION_TO_NEW:
                    // exclude all guests that are already registered as invitees
                    $query = 'SELECT `email`
                        FROM `'.DBPREFIX.'module_calendar_invite`
                        WHERE `event_id` = ' . $objEvent->getId();
                    $result = $db->Execute($query);
                    if ($result === false) {
                        throw new CalendarException(
                            'Unable to process invitation type ' . $sendInvitationTo
                        );
                    }
                    while (!$result->EOF) {
                        // delete all registered guests out of the recipients
                        unset($recipients[$result->fields['email']]);
                        $result->MoveNext();
                    }
                    break;

                case self::MAIL_INVITATION_TO_REGISTERED:
                    break;

                default:
                    throw new CalendarException(
                        $sendInvitationTo . ' is not a valid invitation type'
                    );
                    break;

            }
        }

        return $recipients;
    }

    private function getSendMailLangId($actionId, $recipient)
    {
        switch ($actionId) {
            // MAIL_ALERT_REG and MAIL_NOTFY_NEW_APP are mail notifications
            // that target backend users. Therefore, we shall check if
            // the recipient is a backend user and if so, we shall check if
            // there is a mail template available in the user's prefered
            // backend language.
            case static::MAIL_ALERT_REG:
            case static::MAIL_NOTFY_NEW_APP:
                // backend users are of type MailRecipient::RECIPIENT_TYPE_ACCESS_USER
                if ($recipient->getType() != MailRecipient::RECIPIENT_TYPE_ACCESS_USER) {
                    break;
                }

                // abort in case the backend user is not active
                $objUser = \FWUser::getFWUserObject()->objUser->getUsers($filter = array('id' => $recipient->getId(), 'is_active' => true));
                if (!$objUser) {
                    break;
                }

                // try to use prefered backend language
                if (isset($this->mailList[$objUser->getBackendLanguage()])) {
                    return $objUser->getBackendLanguage();
                }

                // try to use prefered frontend language as fallback
                if (isset($this->mailList[$objUser->getFrontendLanguage()])) {
                    return $objUser->getFrontendLanguage();
                }

            default:
                break;
        }

        // check if prefered language of recipient exists as a mail template
        if (isset($this->mailList[$recipient->getLang()])) {
            return $recipient->getLang();
        }

        // use fallback mail template, in case none exists in the recipient's
        // prefered language
        reset($this->mailList);
        return key($this->mailList);
    }

    /**
     * Loads the RegistrationData text and Html mail content
     *
     * @param object $objRegistration Registration object
     *
     * @return array RegistrationData text and Html mail
     */
    private function getRegistrationData($objRegistration)
    {
        global $_ARRAYLANG;

        $registrationDataText = '';
        $registrationDataHtml = '<table align="top" border="0" cellpadding="3" cellspacing="0">';
        foreach ($objRegistration->getForm()->inputfields as $arrInputfield) {
            $arrField = $objRegistration->fields[$arrInputfield['id']];
            $hide = false;
            switch ($arrField['type']) {
                case 'select':
                case 'radio':
                case 'checkbox':
                case 'salutation':
                    $options = explode(",", $arrField['default']);
                    $values  = explode(",", $arrField['value']);
                    $output  = array();

                    foreach ($values as $value) {
                        $arrValue = explode('[[', $value);
                        $value    = $arrValue[0];
                        $input    = str_replace(']]','', $arrValue[1]);

                        $newOptions = explode('[[', $options[$value-1]);
                        if (!empty($input)) {
                            $output[]  = $newOptions[0].": ".$input;
                        } else {
                            if ($newOptions[0] == '') {
                                $newOptions[0] = $value == 1 ? $_ARRAYLANG['TXT_CALENDAR_YES'] : $_ARRAYLANG['TXT_CALENDAR_NO'];
                            }

                            $output[] = $newOptions[0];
                        }
                    }
                    $htmlValue = $textValue = join(", ", $output);
                    break;
                case 'agb':
                    $htmlValue = $textValue = $arrField['value'] ? $_ARRAYLANG["TXT_{$this->moduleLangVar}_YES"] : $_ARRAYLANG["TXT_{$this->moduleLangVar}_NO"];
                    break;
                case 'textarea':
                    $textValue = $arrField['value'];
                    $htmlValue = nl2br($arrField['value']);
                    break;
                case 'fieldset':
                    $hide = true;
                    break;
                default :
                    $htmlValue = $textValue = $arrField['value'];
                    break;
            }

            if (!$hide) {
                $registrationDataText .= html_entity_decode($arrField['name']).":\t".html_entity_decode($textValue)."\n";
                $registrationDataHtml .= '<tr><td><b>'.$arrField['name'].":</b></td><td>". $htmlValue."</td></tr>";
            }
        }
        $registrationDataHtml .= '</table>';

        return array($registrationDataText, $registrationDataHtml);
    }
}
