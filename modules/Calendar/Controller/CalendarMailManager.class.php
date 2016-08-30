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
     */
    const MAIL_INVITATION    = 1;
    
    /**
     * Mail Action Confirm registration
     */
    const MAIL_CONFIRM_REG   = 2;
    
    /**
     * Mail Action Alert registration
     */
    const MAIL_ALERT_REG     = 3;
    
    /**
     * mail action notify new appoinment
     */
    const MAIL_NOTFY_NEW_APP = 4;

    /**
     * Constructor
     */
    function __construct()
    {
        $this->getFrontendLanguages();
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
            
            $objResult = $objDatabase->Execute("SELECT `name` FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_mail_action WHERE id='".$objMail->action_id."' LIMIT 1 ");
            if ($objResult !== false) {
                $action = $_ARRAYLANG['TXT_CALENDAR_MAIL_ACTION_'.strtoupper($objResult->fields['name'])];
            }
            
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
     * @param integer                                       $actionId       Mail action id
     * @param integer                                       $regId          Registration id
     * @param string                                        $mailTemplate   Mail template id
     */
    function sendMail(CalendarEvent $event, $actionId, $regId=null, $mailTemplate = null)
    {
        global $objDatabase,$_ARRAYLANG, $_CONFIG ;
                
        $this->mailList = array();  
        
        // Loads the mail template which needs for this action
        $this->loadMailList($actionId, $mailTemplate);
        
        if (!empty($this->mailList)) {

            $objRegistration = null;
            if(!empty($regId)) {
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
                $objResult = $objDatabase->Execute($query);
                
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

            $placeholder = array('[[TITLE]]', '[[START_DATE]]', '[[END_DATE]]', '[[LINK_EVENT]]', '[[LINK_REGISTRATION]]', '[[USERNAME]]', '[[FIRSTNAME]]', '[[LASTNAME]]', '[[URL]]', '[[DATE]]');
            
            $recipients = $this->getSendMailRecipients($actionId, $event, $regId, $objRegistration);
                        
            $objMail = new \phpmailer();

            if ($_CONFIG['coreSmtpServer'] > 0) {
                $arrSmtp = \SmtpSettings::getSmtpAccount($_CONFIG['coreSmtpServer']);
                if ($arrSmtp !== false) {
                    $objMail->IsSMTP();
                    $objMail->Host = $arrSmtp['hostname'];
                    $objMail->Port = $arrSmtp['port'];
                    $objMail->SMTPAuth = true;
                    $objMail->Username = $arrSmtp['username'];
                    $objMail->Password = $arrSmtp['password'];
                }
            }

            $objMail->CharSet = CONTREXX_CHARSET;
            $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreGlobalPageTitle']);

            foreach ($recipients as $mailAdress => $langId) {
                if (!empty($mailAdress)) {
                    
                    $langId = $this->getSendMailLangId($actionId, $mailAdress, $langId);
            
                    if ($objUser = \FWUser::getFWUserObject()->objUser->getUsers($filter = array('email' => $mailAdress, 'is_active' => true))) {                        
                        $userNick      = $objUser->getUsername();
                        $userFirstname = $objUser->getProfileAttribute('firstname');
                        $userLastname  = $objUser->getProfileAttribute('lastname');
                    } else {
                        $userNick = $mailAdress;
                        if (!empty($regId) && $mailAdress == $regMail) {
                            $userFirstname = $regFirstname;
                            $userLastname  = $regLastname;
                        } else {
                            $userFirstname = '';
                            $userLastname  = '';
                        }
                    }

                    $mailTitle       = $this->mailList[$langId]['mail']->title;
                    $mailContentText = !empty($this->mailList[$langId]['mail']->content_text) ? $this->mailList[$langId]['mail']->content_text : strip_tags($this->mailList[$langId]['mail']->content_html);
                    $mailContentHtml = !empty($this->mailList[$langId]['mail']->content_html) ? $this->mailList[$langId]['mail']->content_html : $this->mailList[$langId]['mail']->content_text;
                    
                    // actual language of selected e-mail template
                    $contentLanguage = $this->mailList[$langId]['lang_id'];

                    if ($actionId == self::MAIL_NOTFY_NEW_APP && $event->arrSettings['confirmFrontendEvents'] == 1) {
                        $eventLink = $domain."/cadmin/index.php?cmd={$this->moduleName}&act=modify_event&id={$event->id}&confirm=1";
                    } else {
                        $eventLink = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'detail', $contentLanguage, array('id' => $event->id, 'date' => $event->startDate->getTimestamp()))->toString();
                    }            
                    $regLink   = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'register', $contentLanguage, array('id' => $event->id, 'date' => $event->startDate->getTimestamp()))->toString();

                    $replaceContent  = array($eventTitle, $eventStart, $eventEnd, $eventLink, $regLink, $userNick, $userFirstname, $userLastname, $domain, $date);

                    $mailTitle       = str_replace($placeholder, $replaceContent, $mailTitle);                                                                           
                    $mailContentText = str_replace($placeholder, $replaceContent, $mailContentText);                                                                           
                    $mailContentHtml = str_replace($placeholder, $replaceContent, $mailContentHtml);

                    if (!empty($regId)) {
                        $mailTitle       = str_replace($regSearch, $regReplace, $mailTitle);                                                                           
                        $mailContentText = str_replace($regSearch, $regReplace, $mailContentText);                                                                           
                        $mailContentHtml = str_replace($regSearch, $regReplace, $mailContentHtml);

                        $mailContentText = str_replace('[[REGISTRATION_DATA]]', $registrationDataText, $mailContentText);                                                                           
                        $mailContentHtml = str_replace('[[REGISTRATION_DATA]]', $registrationDataHtml, $mailContentHtml);
                    }
                    
                    /*echo "send to: ".$mailAdress."<br />";
                    echo "send title: ".$mailTitle."<br />";*/
                    
                    $objMail->Subject = $mailTitle;
                    $objMail->Body    = $mailContentHtml;
                    $objMail->AltBody = $mailContentText; 
                    $objMail->AddAddress($mailAdress);   
                    $objMail->Send();
                    $objMail->ClearAddresses();
                }
            }
        }
    }
    
    /**
     * Loads the mail template for the give action     
     * 
     * @param integer $actionId     Mail action see CalendarMailManager:: const vars
     * @param integer $mailTemplate Specific Mail template id to load
     */
    private function loadMailList($actionId, $mailTemplate)
    {
        global $objDatabase;
        
        if($mailTemplate) {
            $whereId = " AND mail.id = " . intval($mailTemplate);
        } else {
            $whereId = "";
        }

        $query = "SELECT mail.id, action.default_recipient, mail.lang_id, mail.is_default, mail.recipients    
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_mail AS mail, 
                         ".DBPREFIX."module_".$this->moduleTablePrefix."_mail_action AS action
                   WHERE mail.action_id='".intval($actionId)."'  
                     AND status='1'    
                     AND action.id = mail.action_id$whereId
                ORDER BY is_default DESC";   
                
        $objResult = $objDatabase->Execute($query);  
        
        if ($objResult !== false) {
            while (!$objResult->EOF) { 
                $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($objResult->fields['id']));
                if($objResult->fields['is_default'] == 1) {
                    $supRecipients = explode(",", $objResult->fields['recipients']);
                    $this->mailList[0]['recipients'] = array();
                    foreach ($supRecipients as $mail) {
                        if (!empty($mail)) {
                            $this->mailList[0]['recipients'][$mail] = intval($objResult->fields['lang_id']);
                        }
                    }
                    $this->mailList[0]['default_recipient'] = array();
                    if ($objResult->fields['default_recipient'] != 'empty') {
                        $this->mailList[0]['default_recipient'][$objResult->fields['default_recipient']] = intval($objResult->fields['lang_id']);
                    }
                    $this->mailList[0]['mail'] = $objMail;
                }
                 
                $supRecipients = explode(",", $objResult->fields['recipients']);
                $this->mailList[intval($objResult->fields['lang_id'])]['recipients'] = array();
                foreach ($supRecipients as $mail) {
                    if (!empty($mail)) {
                        $this->mailList[intval($objResult->fields['lang_id'])]['recipients'][$mail] = intval($objResult->fields['lang_id']);
                    }
                }
                $this->mailList[intval($objResult->fields['lang_id'])]['default_recipient'] = array();
                if ($objResult->fields['default_recipient'] != 'empty') {
                    $this->mailList[intval($objResult->fields['lang_id'])]['default_recipient'][$objResult->fields['default_recipient']] = intval($objResult->fields['lang_id']);
                }
                $this->mailList[intval($objResult->fields['lang_id'])]['mail'] = $objMail;
                
                $objResult->MoveNext();
            }
        }
    }
        
    /**
     * Returns the array recipients
     *      
     * @param integer $actionId         Mail Action
     * @param object  $objEvent         Event object
     * @param integer $regId            registration id
     * @param object  $objRegistration  Registration object
     * 
     * @return array returns the array recipients
     */
    private function getSendMailRecipients($actionId, $objEvent, $regId = 0, $objRegistration = null)
    {
        global $_CONFIG, $_LANGID;
        
        $recipients = array();
        
        if (array_key_exists('admin', $this->mailList[0]['default_recipient'])) {
            $recipients[$_CONFIG['coreAdminEmail']] = $_LANGID;
        } elseif (array_key_exists('author', $this->mailList[$_LANGID]['default_recipient']) || array_key_exists('author', $this->mailList[0]['default_recipient'])) {
            if (!empty($regId) && !empty($objRegistration)) {
                if (!empty($objRegistration->userId)) {
                    $objFWUser = \FWUser::getFWUserObject();
                    if ($objUser = $objFWUser->objUser->getUser($id = intval($objRegistration->userId))) {
                        $userMail = $objUser->getEmail();
                        $userLang = $objUser->getFrontendLanguage();
                        
                        $recipients[$userMail] = $userLang;
                    }
                }
                
                foreach ($objRegistration->fields as $arrField) {
                    if ($arrField['type'] == 'mail' && !empty($arrField['value'])) {
                        $recipients[$arrField['value']] = isset($this->mailList[$_LANGID]) ? $_LANGID : 0;
                    }
                }
            }
        }
        
        switch ($actionId) {
            case 1:
                $invitedMails = explode(",", $objEvent->invitedMails);
                foreach ($invitedMails as $mail) {
                    if (!empty($mail)) {
                        $recipients[$mail] = $_LANGID;
                    }
                }
                $invitedGroups = array();
                if ($objUser = \FWUser::getFWUserObject()->objUser->getUsers()) {
                    while (!$objUser->EOF) {
                        foreach ($objUser->getAssociatedGroupIds() as $groupId) {
                            if (in_array($groupId, $objEvent->invitedGroups))  {
                             $invitedGroups[$objUser->getEmail()] = $objUser->getFrontendLanguage();
                            }
                        }
                        $objUser->next();
                    }
                }

                $recipients = array_merge($recipients, $invitedGroups);
                break;
            case 3:
                $notificationEmails = explode(",", $objEvent->notificationTo);

                foreach ($notificationEmails as $mail) {
                    $recipients[$mail] = $_LANGID;
                }
                break;
            default:
        }
                
        foreach ($this->mailList as $langId => $mailList) {
            foreach ($mailList['recipients'] as $email => $langId) {
                $recipients[$email] = $langId;
            }
        }
        
        if(isset($this->mailList[$_LANGID]) && $this->mailList[$_LANGID]['mail']->title == '') {
            $langId = 0;
        } else {
            $langId = $_LANGID;
        }
                
        if (isset($this->mailList[$langId]) && is_array($this->mailList[$langId]['default_recipient'])) {
            $recipients = array_merge($recipients, $this->mailList[$langId]['default_recipient']);
        }
        
        return $recipients;
    }
    
    private function getSendMailLangId($actionId, $receiverEmail, $language_id)
    {
        $langId = 0; // default template
        
        // language selection process 1
        if (   $actionId == self::MAIL_CONFIRM_REG
            && \FWUser::getFWUserObject()->objUser->getEmail() == $receiverEmail
            && isset($this->mailList[$language_id])
           ) {
            $langId = $language_id;
        } else {
            // language selection process 2
            if ($objUser = \FWUser::getFWUserObject()->objUser->getUsers($filter = array('email' => $receiverEmail, 'is_active' => true))) {
                switch (true) {
                    case (isset($this->mailList[$objUser->getBackendLanguage()])):
                        $langId = $objUser->getBackendLanguage();
                        break;
                    case (isset($this->mailList[$objUser->getFrontendLanguage()])):
                        $langId = $objUser->getFrontendLanguage();
                        break;
                    default:
                        break;
                }
            }
        }
        
        if (isset($this->mailList[$langId])) {
            return $langId;
        } else {
            reset($this->mailList);
            return key($this->mailList);
        }
        
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
        foreach ($objRegistration->fields as $arrField) {
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
