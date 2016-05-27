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
 * Calendar
 *  
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */

namespace Cx\Modules\Calendar\Controller;
/**
 * Calendar
 * 
 * Calendar Class Registration
 * 
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */ 
class CalendarRegistration extends CalendarLibrary
{
    /**
     * Registration id
     *
     * @access public
     * @var integer 
     */
    public $id; 
    
    /**
     * Event Id
     *
     * @access public
     * @var integer 
     */
    public $eventId;  
    
    /**
     * Event date
     *
     * @access public
     * @var integer Timestamp of Event date
     */
    public $eventDate; 
    
    /**
     * User id
     *
     * @access public
     * @var interger 
     */
    public $userId;   
    
    /**
     * Language Id
     *
     * @access public
     * @var integer
     */
    public $langId; 
    
    /**
     * Type
     *
     * @access public
     * @var integer
     */
    public $type; 
    
    /**
     * Host name
     *
     * @access public
     * @var string
     */
    public $hostName; 
    
    /**
     * User Ip address
     *
     * @access public
     * @var string
     */
    public $ipAddress;
    
    /**
     * Reg Key
     *
     * @access public
     * @var string 
     */
    public $key;     
    
    /**
     * First Export time
     *
     * @access public
     * @var integer 
     */
    public $firstExport;
    
    /**
     * Paymend method
     *
     * @access public
     * @var integer
     */
    public $paymentMethod;
    
    /**
     * Payment status
     *
     * @access public
     * @var interger
     */
    public $paid;
    
    /**
     * Save In
     *
     * @access public
     * @var integer 
     */
    public $saveIn;
    
    /**
     * Fields
     *
     * @access public
     * @var array 
     */
    public $fields = array(); 
    
    /**
     * Registration form object
     *
     * @access private
     * @var object 
     */
    private $form;
    
    /**
     * Constructor for registration class
     * 
     * Loads the form object from CalendarForm class
     * IF the $id is not null load the register object for the given id
     * 
     * @param integer $formId Registration Form Id
     * @param integer $id     Registration id
     */
    function __construct($formId, $id=null){              
        $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm(intval($formId));
        $this->form = $objForm;     
        
        if($id != null) {
            self::get($id);
        }        
    }
    
    /**
     * Loads the registration by id
     *      
     * @param integer $regId Registration id
     * 
     * @return null
     */
    function get($regId) {
        global $objDatabase, $_LANGID;    
        
        $query = 'SELECT registration.`id` AS `id`,
                         registration.`event_id` AS `event_id`,
                         registration.`date` AS `date`,
                         registration.`host_name` AS `host_name`,
                         registration.`ip_address` AS `ip_address`,
                         registration.`type` AS `type`,
                         registration.`key` AS `key`,
                         registration.`user_id` AS `user_id`,
                         registration.`lang_id` AS `lang_id`,
                         registration.`export` AS `first_export`,
                         registration.`payment_method` AS `payment_method`,
                         registration.`paid` AS `paid`
                   FROM '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration AS registration
                   WHERE registration.`id` = "'.$regId.'"
                   LIMIT 1';   
        
        $objResult = $objDatabase->Execute($query);  
        
        if($objResult !== false) {
            $this->id = intval($objResult->fields['id']);
            $this->eventId = intval($objResult->fields['event_id']);           
            $this->eventDate = intval($objResult->fields['date']);        
            $this->userId= intval($objResult->fields['user_id']);        
            $this->langId= intval($objResult->fields['lang_id']);        
            $this->type = intval($objResult->fields['type']);        
            $this->hostName = htmlentities($objResult->fields['host_name'], ENT_QUOTES, CONTREXX_CHARSET);      
            $this->ipAddress = htmlentities($objResult->fields['ip_address'], ENT_QUOTES, CONTREXX_CHARSET);        
            $this->key = htmlentities($objResult->fields['key'], ENT_QUOTES, CONTREXX_CHARSET);          
            $this->firstExport = intval($objResult->fields['first_export']);
            $this->paymentMethod = intval($objResult->fields['payment_method']);
            $this->paid = intval($objResult->fields['paid']);
            
            foreach ($this->form->inputfields as $key => $arrInputfield) {         
                $name = $arrInputfield['name'][$_LANGID];
                $default = $arrInputfield['default_value'][$_LANGID];
                
                $queryField = 'SELECT field.`value` AS `value`
                                 FROM '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value AS field
                                WHERE field.`reg_id` = "'.$regId.'" AND
                                      field.`field_id` = "'.intval($arrInputfield['id']).'"
                                LIMIT 1';
                $objResultField = $objDatabase->Execute($queryField);          
                
                if($objResultField !== false) {
                     $this->fields[$arrInputfield['id']]['name']    =  $name;
                     $this->fields[$arrInputfield['id']]['type']    =  $arrInputfield['type'];
                     $this->fields[$arrInputfield['id']]['value']   =  htmlentities($objResultField->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                     $this->fields[$arrInputfield['id']]['default'] =  $default;  
                }   
            } 
        }       
    }
    
    /**
     * Save the registration
     *      
     * @param array $data posted data from the form
     * 
     * @return boolean true if the registration saved, false otherwise
     */
    function save($data) {
        global $objDatabase, $objInit, $_LANGID;
        
        /* foreach ($this->form->inputfields as $key => $arrInputfield) {
            if($arrInputfield['type'] == 'selectBillingAddress') { 
                $affiliationStatus = $data['registrationField'][$arrInputfield['id']];
            }
        } */
        
        foreach ($this->form->inputfields as $key => $arrInputfield) {
            /* if($affiliationStatus == 'sameAsContact') {
                if($arrInputfield['required'] == 1 && empty($data['registrationField'][$arrInputfield['id']]) && $arrInputfield['affiliation'] != 'billing') {
                    return false;
                } 
            
                if($arrInputfield['required'] == 1 && $arrInputfield['type'] == 'mail' && $arrInputfield['affiliation'] != 'billing') {
                    $objValidator = new FWValidator();
                    
                    if(!$objValidator->isEmail($data['registrationField'][$arrInputfield['id']])) {
                        return false;    
                    }
                }
            } else { */
                if($arrInputfield['required'] == 1 && empty($data['registrationField'][$arrInputfield['id']])) {
                    return false;
                } 
            
                if($arrInputfield['required'] == 1 && $arrInputfield['type'] == 'mail') {
                    $objValidator = new \FWValidator();
                    
                    if(!$objValidator->isEmail($data['registrationField'][$arrInputfield['id']])) {
                        return false;    
                    }
                }
            /* } */
        }
        
        $regId = intval($data['regid']);
        $eventId = intval($data['id']);
        $formId = intval($data['form']);
        $eventDate = intval($data['date']);
        $userId = intval($data['userid']);
        
        $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent($eventId);

        if (   $objInit->mode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND
            && $objEvent->seriesStatus
            && $objEvent->independentSeries
        ) {
            $eventDate = isset($data['registrationEventDate']) ? contrexx_input2int($data['registrationEventDate']) : $eventDate;
        }

        $query = '
            SELECT
                `id`
            FROM
                `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field`
            WHERE
                `form` = '. $formId .'
            AND
                `type` = "seating"
            LIMIT 1
        ';
        $objResult = $objDatabase->Execute($query);
        
        $numSeating = intval($data['registrationField'][$objResult->fields['id']]);
        $type       =   empty($regId) && intval($objEvent->getFreePlaces() - $numSeating) < 0
                      ? 2 : (isset($data['registrationType']) ? intval($data['registrationType']) : 1);
        $this->saveIn = intval($type);
        $paymentMethod = intval($data['paymentMethod']);
        $paid = intval($data['paid']);
        $hostName = 0;
        $ipAddress = 0;
        $key = $this->generateKey();
        
        if ($regId == 0) {
            $query = 'INSERT INTO '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration
                                  (`event_id`,`date`,`host_name`,`ip_address`,`type`,`key`,`user_id`,`lang_id`,`export`,`payment_method`,`paid`)
                           VALUES ("'.$eventId.'","'.$eventDate.'","'.$hostName.'","'.$ipAddress.'","'.$type.'","'.$key.'","'.$userId.'","'.$_LANGID.'",0,"'.$paymentMethod.'","'.$paid.'")';
            
            $objResult = $objDatabase->Execute($query);
            
            if($objResult !== false) {
                $this->id = $objDatabase->Insert_ID();
            } else {
                return false;
            }
        } else {
            $query = 'UPDATE `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration`
                         SET `event_id` = '.$eventId.',
                             `date` = '.$eventDate.',
                             `host_name` = '.$hostName.',
                             `ip_address` = '.$ipAddress.',
                             `key` = "'.$key.'",
                             `user_id` = '.$userId.',
                             `type`    = '.$type.',
                             `lang_id` = '.$_LANGID.',
                             `payment_method` = '.$paymentMethod.',
                             `paid` = '.$paid.'
                       WHERE `id` = '.$regId;
            
            $objResult = $objDatabase->Execute($query);
            
            if($objResult === false) {
                return false;
            }
        }
        
        if ($regId != 0) {
            $this->id = $regId;
            $deleteQuery = 'DELETE FROM '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value
                            WHERE `reg_id` = '.$this->id;
            
            $objDeleteResult = $objDatabase->Execute($deleteQuery);
            
            if ($objDeleteResult === false) {
                return false;
            }
        }

        foreach ($this->form->inputfields as $key => $arrInputfield) {
            $value = $data['registrationField'][$arrInputfield['id']];
            $id    = $arrInputfield['id'];
            
            if(is_array($value)) {   
                $subvalue = array();
                foreach ($value as $key => $element) {
                    if(!empty($data['registrationFieldAdditional'][$id][$element-1])) {
                        $subvalue[] = $element.'[['.$data['registrationFieldAdditional'][$id][$element-1].']]';
                    } else {
                        $subvalue[] = $element;
                    } 
                }
                $value = join(",", $subvalue);
            } else {                                                                   
                if(isset($data['registrationFieldAdditional'][$id][$value-1])) {
                    $value = $value."[[".$data['registrationFieldAdditional'][$id][$value-1]."]]";
                }
            }
            
            $query = 'INSERT INTO '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value
                                  (`reg_id`, `field_id`, `value`) 
                           VALUES ('.$this->id.', '.$id.', "'.  contrexx_input2db($value).'")';

            $objResult = $objDatabase->Execute($query);
            
            if ($objResult === false) {
                return false;
            }
        }
        
        if ($objInit->mode == 'frontend') {
            $objMailManager = new \Cx\Modules\Calendar\Controller\CalendarMailManager();
            
            $templateId     = $objEvent->emailTemplate[FRONTEND_LANG_ID];
            $objMailManager->sendMail(intval($_REQUEST['id']), \Cx\Modules\Calendar\Controller\CalendarMailManager::MAIL_CONFIRM_REG, $this->id, $templateId);
            
            $objMailManager->sendMail(intval($_REQUEST['id']), \Cx\Modules\Calendar\Controller\CalendarMailManager::MAIL_ALERT_REG, $this->id);
        }
        
        return true;
    }
    
    /**
     * Delete the registration
     *      
     * @param integer $regId Registration id
     * 
     * @return boolean true if data deleted, false otherwise
     */
    function delete($regId) {
        global $objDatabase; 
        
        if (!empty($regId)) {
            $query = '
                DELETE FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration`
                WHERE `id` = '.intval($regId);
            $objResult = $objDatabase->Execute($query);
            
            if ($objResult !== false) {
                $query = '
                    DELETE FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value`
                    WHERE `reg_id` = '.intval($regId)
                ;
                $objResult = $objDatabase->Execute($query);
                
                if ($objResult !== false) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Update the registration to the given type
     *      
     * @param integer $regId  Registration id
     * @param integer $typeId Type Id
     * 
     * @return boolean true if registration updated, false otherwise
     */
    function move($regId, $typeId) {
        global $objDatabase, $_LANGID; 
        
        if (!empty($regId)) {
            $query = '
                UPDATE `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration`
                SET `type` = '.$typeId.'
                WHERE `id` = '.$regId.'
                AND `lang_id` = '.$_LANGID
            ;
            $objResult = $objDatabase->Execute($query);
            
            if ($objResult !== false) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Update the export date into the registration
     *      
     * @return boolean true if date updated sucessfully, false otherwise
     */
    function tagExport() { 
        global $objDatabase, $_LANGID;
        
        $now = time();
        
        if(intval($this->id) != 0) {
            $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_registration SET `export` = '".intval($now)."' WHERE `id` = '".intval($this->id)."'";              
            $objResult = $objDatabase->Execute($query);     
            if($objResult !== false) {
                $this->firstExport = $now;
                return true;  
            } else {
                return false;
            }  
        }
    }

    /**
     * Updatete the payment status
     *      
     * @param integer $payStatus payment status
     * 
     * @return null
     */
    function setPaid($payStatus = 0) {
        global $objDatabase;
        $query = '
                    UPDATE `'.DBPREFIX.'module_calendar_registration` AS `r`
                    SET `paid` = ? WHERE `id` = ?
                ';
        $objResult = $objDatabase->Execute($query, array($payStatus, $this->id));
    }
}
