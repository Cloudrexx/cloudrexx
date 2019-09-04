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

class CalendarException extends \Exception { }

/**
 * Calendar
 *
 * LibClass to manage cms calendar
 * 
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */  
class CalendarLibrary
{
    /**
     * Template object
     * 
     * @access public
     * @var object 
     */
    public $_objTpl;  
    
    /**
     * template content
     *
     * @access public
     * @var string 
     */
    public $pageContent; 
    
    /**
     * module name
     *
     * @access public
     * @var string 
     */
    public $moduleName = "Calendar";
    
    /**
     * module table prefix
     *
     * @access public
     * @var string 
     */
    public $moduleTablePrefix = "calendar";
    
    /**
     * module language variable prefix
     *
     * @access public
     * @var string 
     */
    public $moduleLangVar  = "CALENDAR";
        
    /**
     * Error message
     *
     * @access public
     * @var string 
     */
    public $errMessage = '';
    
    /**
     * Success message
     *
     * @access public
     * @var type 
     */
    public $okMessage = '';
    
    /**
     * CSV separator
     *
     * @access public
     * @var string
     */
    public $csvSeparator = ';';
    
    /**
     * active frontend languages
     *
     * @access public
     * @var array 
     */
    public $arrFrontendLanguages = array();
    
    /**
     * Settings array
     *
     * @access public
     * @var array 
     */
    public $arrSettings = array();
    
    /**
     * Static settings array to cache the fetched data from the database
     *
     * @var array 
     */
    public static $settings = array();

    /**
     * Community group array
     *
     * @access public
     * @var array 
     */
    public $arrCommunityGroups = array();    
        
    /**
     * @var \Cx\Core\Core\Controller\Cx
     */
    protected $cx;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * map field key
     *
     * @var string
     */
    const MAP_FIELD_KEY     = 'map_id';
    
    /**
     * Picture field key
     *
     * @var string
     */
    const PICTURE_FIELD_KEY = 'picture_id';
    
    /**
     * Attachment field key
     * 
     * @var string
     */
    const ATTACHMENT_FIELD_KEY = 'attachment_id';

    /**
     * Setting value for option frontendPastEvents defining that all events
     * having their start date as of today shall be listed in frontend till
     * the end of today.
     *
     * @var integer
     */
    const SHOW_EVENTS_OF_TODAY = 0;

    /**
     * Setting value for option frontendPastEvents defining that only those
     * events shall be listed in frontend that have not yet ended (end date lies
     * in the past)
     *
     * @var integer
     */
    const SHOW_EVENTS_UNTIL_END = 1;

    /**
     * Setting value for option frontendPastEvents defining that only those
     * events shall be listed in frontend that have not yet started (start date
     * lies in the future)
     *
     * @todo Implement behavior of this option
     * @var integer
     */
    const SHOW_EVENTS_UNTIL_START = 2;
    
    /**
     * Assign the template path
     * Sets the Global variable for the calendar module
     * 
     * @param string $tplPath Template path
     */
    public function __construct($tplPath = '') {
        $this->_objTpl = new \Cx\Core\Html\Sigma($tplPath);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);    
        
        $this->_objTpl->setGlobalVariable(array(
            $this->moduleLangVar.'_MODULE_NAME'  => $this->moduleName,
            $this->moduleLangVar.'_CSRF'         => 'csrf='.\Cx\Core\Csrf\Controller\Csrf::code(),     
            $this->moduleLangVar.'_DATE_FORMAT'  => self::getDateFormat(1),
            $this->moduleLangVar.'_JAVASCRIPT'   => self::getJavascript(),
        ));

        $this->init();
    }

    /**
     * Initialize $cx and $em
     */
    public function init() {
        $this->cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $this->em = $this->cx->getDb()->getEntityManager();
    }

    /**
     * Checks the access level for the given action     
     *      
     * It checks the access level for the given action
     * and return's null if access is granted otherwise it redirect the action
     * to the respective fallback pages.
     *  
     * @param string $strAction possible values are add_event, 
     *                          edit_event, my_events
     * 
     * @return null
     */
    function checkAccess($strAction)
    {
        global $objInit;

        if($objInit->mode == 'backend') {
            //backend access
        } else {
            //frontend access

            $strStatus = '';
            $objFWUser  = \FWUser::getFWUserObject();

            //get user attributes
            $objUser         = $objFWUser->objUser;
            $intUserId      = intval($objUser->getId());
            $intUserName    = $objUser->getUsername();
            $bolUserLogin   = $objUser->login();
            $intUserIsAdmin = $objUser->getAdminStatus();                                                                                 

            $accessId = 0; //used to remember which access id the user needs to have. this is passed to Permission::checkAccess() later.
            
            $intUserIsAdmin = false;

            if(!$intUserIsAdmin) {
                self::getSettings();

                switch($strAction) {
                    case 'add_event':  
                       if($this->arrSettings['addEventsFrontend'] == 1 || $this->arrSettings['addEventsFrontend'] == 2) {
                            if($this->arrSettings['addEventsFrontend'] == 2) {
                                if($bolUserLogin) {
                                    $bolAdd = true;
                                } else {
                                    $bolAdd = false;
                                }
                            } else {
                                $bolAdd = true;
                            } 

                            if($bolAdd) {
                                //get groups attributes
                                $arrUserGroups  = array();
                                $objGroup = $objFWUser->objGroup->getGroups($filter = array('is_active' => true, 'type' => 'frontend'));

                                while (!$objGroup->EOF) {
                                    if(in_array($objGroup->getId(), $objUser->getAssociatedGroupIds())) {
                                        $arrUserGroups[] = $objGroup->getId();
                                    }
                                    $objGroup->next();
                                }                  
                            } else {
                                $strStatus = 'login';
                            }
                        } else {
                            $strStatus = 'redirect';
                        }
                        
                        break;
                    case 'edit_event':                
                        if($this->arrSettings['addEventsFrontend'] == 1 || $this->arrSettings['addEventsFrontend'] == 2) {
                            if($bolUserLogin) {         
                                if(isset($_POST['submitFormModifyEvent'])) {
                                    $eventId = intval($_POST['id']);
                                } else {
                                    $eventId = intval($_GET['id']);
                                }                       
                                
                                $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent($eventId);
                                
                                if($objEvent->author != $intUserId) {
                                    $strStatus = 'no_access';
                                }
                            } else {
                                $strStatus = 'login';
                            }   
                        } else {  
                            $strStatus = 'redirect';
                        }
                        break;
                    
                    case 'my_events':
                        if($this->arrSettings['addEventsFrontend'] == 1 || $this->arrSettings['addEventsFrontend'] == 2) {
                            if(!$bolUserLogin) {
                                $strStatus = 'login';
                            }
                        } else {  
                            $strStatus = 'redirect';
                        }
                        break;
                }

                switch($strStatus) {
                    case 'no_access':
                        \Cx\Core\Csrf\Controller\Csrf::redirect(CONTREXX_SCRIPT_PATH.'?section=Login&cmd=noaccess');
                        exit();
                        break;
                    case 'login':
                        $link = base64_encode(CONTREXX_SCRIPT_PATH.'?'.$_SERVER['QUERY_STRING']);
                        \Cx\Core\Csrf\Controller\Csrf::redirect(CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
                        exit();
                        break;
                    case 'redirect':
                        \Cx\Core\Csrf\Controller\Csrf::redirect(CONTREXX_SCRIPT_PATH.'?section='.$this->moduleName);   
                        exit();
                        break;
                }
            }
        }
    }
    
    /**
     * Prepares the settings from database to array format
     * 
     * Loads the settings values from the database and assign those values into
     * $this->arrSettings
     * 
     * @return null
     */    
    function getSettings()
    {
        global $objDatabase, $_ARRAYLANG, $objInit;
        
        // only initialize once
        if ($this->arrSettings) {
            return;
        }
        
        // hotfix: this fixes the issue that the settings are being fetch from the
        // database over and over again.
        // This is just workaround without having to refactor the whole implementation of CalendarLibrary::$arrSettings
        if (isset(static::$settings[$this->moduleTablePrefix])) {
            $this->arrSettings = static::$settings[$this->moduleTablePrefix];
            return;
        }

        // TODO: we have to manually load the language-data here, as it
        // would not be available in the adjustResponse hook.
        // AS a result, the date format specific options (which depend on the
        // language-data) won't work properly.
        // As soon as CLX-1045 has been fixed and completed, the manual
        // loading of the language-data can be removed from here.
        $frontend = false;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if ($cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $frontend = true;
        }
        $_ARRAYLANG = array_merge(
            $_ARRAYLANG,
            \Env::get('init')->getComponentSpecificLanguageData(
                'Calendar',
                $frontend
            )
        );

    	$arrSettings = array();
        $arrDateSettings =  array(
                            'separatorDateList','separatorDateTimeList', 'separatorSeveralDaysList', 'separatorTimeList',
                            'separatorDateDetail','separatorDateTimeDetail', 'separatorSeveralDaysDetail', 'separatorTimeDetail',
                            );

        $objSettings = $objDatabase->Execute("SELECT name,value,options, type FROM  ".DBPREFIX."module_".$this->moduleTablePrefix."_settings ORDER BY name ASC");
        if ($objSettings !== false) {
            while (!$objSettings->EOF) {
                //return date settings
                if($objSettings->fields['type'] == 5 && in_array($objSettings->fields['name'], $arrDateSettings) )
                {
                    $strOptions = $objSettings->fields['options'];
                    $arrOptions = explode(',', $strOptions );
                    $value = $arrOptions[$objSettings->fields['value']];
                    
                    if($objInit->mode == 'backend') {
                        // This is for the preview in settings > Date
                        $arrSettings["{$objSettings->fields['name']}_value"] = isset($_ARRAYLANG["{$value}_VALUE"]) ? htmlspecialchars($_ARRAYLANG["{$value}_VALUE"], ENT_QUOTES, CONTREXX_CHARSET) : '';
                    }
                    $value = isset($_ARRAYLANG[$value]) ? $_ARRAYLANG[$value] : '';
                    $arrSettings[$objSettings->fields['name']] = htmlspecialchars($value, ENT_QUOTES, CONTREXX_CHARSET);
                } else {
                    //return all exept date settings
                    $arrSettings[$objSettings->fields['name']] = htmlspecialchars($objSettings->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                }

                $objSettings->MoveNext();
            }
        }
        
        static::$settings[$this->moduleTablePrefix] = $arrSettings;
        $this->arrSettings = $arrSettings;
    }
    
    /**
     * Initialize the active frontend languages array
     * 
     * Fetch the active frontend languages from the database and assign those
     * values into $this->arrFrontendLanguages
     * 
     * @return null
     */
    function getFrontendLanguages()
    {        
        // return $arrLanguages;
        $this->arrFrontendLanguages = \FWLanguage::getActiveFrontendLanguages();
    }
    
    /**
     * Return's the dataformat based on the type
     *
     * Return's the dateformat by the given type
     * 1 => frontend (javascript format alone) else backend
     *
     * @param integer $type type 1 => frontend (javascript format alone) else backend
     *
     * @return string Date format
     */
    function getDateFormat($type=null)
    {
        self::getSettings();
        $dateFormat = $this->arrSettings['dateFormat'];
        
        if($type == 1) {
            switch ($dateFormat) {
                 case 0:  
                    $dateFormat = 'dd.mm.yy';
                    break;
                 case 1:
                    $dateFormat = 'dd/mm/yy';
                    break;
                 case 2:
                    $dateFormat = 'yy.mm.dd';
                    break;
                 case 3:
                    $dateFormat = 'mm/dd/yy';
                    break;
                 case 4:
                    $dateFormat = 'yy-mm-dd';
                    break;
            }                                                                
        } else {   
            switch ($dateFormat) {
                 case 0:  
                    $dateFormat = 'd.m.Y';
                    break;
                 case 1:
                    $dateFormat = 'd/m/Y';
                    break;
                 case 2:
                    $dateFormat = 'Y.m.d';
                    break;
                 case 3:
                    $dateFormat = 'm/d/Y';
                    break;
                 case 4:
                    $dateFormat = 'Y-m-d';
                    break;
            } 
        }
        
        return $dateFormat;
    }
    
    /**
     * Returns a \DateTime object from a calendar date/time string.
     * The format of a calendar date/time string can be configured
     * in the settings section of the calendar component.
     *
     * Note: In constrast to this method, the method getUserDateTimeFromUser()
     * expects a PHP date/time string.
     *
     * The SUPPLIED calendar date/time string must be in USER timezone.
     * The RETURNED \DateTime object will be in INTERNAL timezone.
     *
     * @param string $date A calendar date/time string in user timezone
     * @param integer $hour Hour value
     * @param integer $minute Minute value
     * @return \DateTime \DateTime object in internal timezone
     */
    function getDateTime($date, $hour = 0, $minute = 0)
    {
        self::getSettings();
        
        switch($this->arrSettings['dateFormat']) {
            case 0:
                $date = str_replace(".", "", $date);                 
                $posYear = 4;
                $posMonth = 2;  
                $posDay = 0;       
                break;
            case 1:                                                
                $date = str_replace("/", "", $date); 
                $posYear = 4;
                $posMonth = 2;  
                $posDay = 0;   
                break;
            case 2:                                               
                $date = str_replace(".", "", $date); 
                $posYear = 0;
                $posMonth = 4;  
                $posDay = 6;
                break;
            case 3:                                           
                
                $date = str_replace("/", "", $date);   
                $posYear = 4;
                $posMonth = 0;  
                $posDay = 2;
                break;
            case 4:   
                $date = str_replace("-", "", $date);  
                $posYear = 0;
                $posMonth = 4;  
                $posDay = 6;
                break;
        }
                                                                   
        $year = substr($date, $posYear,4);
        $month = str_pad(substr($date, $posMonth,2), 2, '0', STR_PAD_LEFT);
        $day = str_pad(substr($date, $posDay,2), 2, '0', STR_PAD_LEFT);
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);

        return $this->getInternDateTimeFromUser($year . '-' . $month . '-' . $day . ' ' .$hour . ':' . $minute . ':00');
    }
    
    /**
     * Initilize the available group
     * 
     * Fetch the available group from the database and assign those values into
     * $this->arrCommunityGroups
     * 
     * @return null
     */
    function getCommunityGroups()
    {
        global $objDatabase;

        $arrCommunityGroups = array();

        $objCommunityGroups = $objDatabase->Execute("SELECT
                                                        `group`.`group_id` AS group_id,
                                                        `group`.`group_name` AS group_name,
                                                        `group`.`is_active` AS is_active,
                                                        `group`.`type` AS `type`
                                                      FROM  ".DBPREFIX."access_user_groups AS `group`");
        if ($objCommunityGroups !== false) {
            while (!$objCommunityGroups->EOF) {
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['id'] = intval($objCommunityGroups->fields['group_id']);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['name'] = htmlspecialchars($objCommunityGroups->fields['group_name'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['active'] = intval($objCommunityGroups->fields['is_active']);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['type'] = htmlspecialchars($objCommunityGroups->fields['type'], ENT_QUOTES, CONTREXX_CHARSET);  

                $objCommunityGroups->MoveNext();
            }
        }
                                           
        $this->arrCommunityGroups = $arrCommunityGroups;
    }
    
    /**
     * Return's the billing address javascript
     * 
     * @return string Billing HereDoc phpscript
     */
    function getJavascript()
    {
        $javascript = <<< EOF
<script type="text/javascript" src="lib/datepickercontrol/datepickercontrol.js"></script>
EOF;
        if (isset($_GET['cmd']) && $_GET['cmd'] == 'register') {
             $javascript .= <<< EOF
             
<script type="text/javascript">
/* <![CDATA[ */
if(\$J('#calendarSelectBillingAddress').length > 0) {
    \$J(document).ready(function() {
        checkSelectBillingAddress();        
    });
    
    \$J('#calendarSelectBillingAddress').change(function() {
        checkSelectBillingAddress();
    });
}

function checkSelectBillingAddress() {
    var displayValue;
    var selectValue =  \$J('#calendarSelectBillingAddress').val();
        
    if(selectValue == 'deviatesFromContact') {
        displayValue = 'block'; 
    } else {
        displayValue = 'none'; 
    }
    
    \$J(".affiliationBilling").each(function() { 
       \$J(this).css('display', displayValue); 
    });  
}

/* ]]> */
</script>
             
EOF;
        }
        
        
        return $javascript;
    }
    
    /**
     * generates the random key
     * 
     * @return string combination of alphabet and number in random order
     */
    function generateKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }
    
    /**
     * Returns all series dates based on the given post data
     *       
     * @return array Array of dates
     */    
    function getExeceptionDates()
    {
        $exceptionDates = array();
        
        $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent();
        $objEvent->loadEventFromData($_GET);

        $objEventManager = new \Cx\Modules\Calendar\Controller\CalendarEventManager($objEvent->startDate);
        $objEventManager->generateRecurrencesOfEvent($objEvent);
        
        $_CORELANG = \Env::get('init')->getComponentSpecificLanguageData(
            'Core',
            false
        );
        $dayArray = explode(',', $_CORELANG['TXT_CORE_DAY_ABBREV2_ARRAY']);
        foreach ($objEventManager->eventList as $event) {
            $startDate = $this->format2userDate($event->startDate);
            $endDate   = $this->format2userDate($event->endDate);

            $label = $dayArray[$this->formatDateTime2user($event->startDate, "w")] .
                ", " . $startDate;
            if ($startDate != $endDate) {
                $label .= ' - ' .
                    $dayArray[
                        $this->formatDateTime2user($event->endDate, "w")
                    ] .
                    ", ". $endDate;
            }

            $exceptionDates[] = array(
                'date'  => $startDate,
                'label' => $label,
            );
        }

        return $exceptionDates;
    }

    /**
     * Get component controller object
     *
     * @param string $name  component name  
     *
     * @return \Cx\Core\Core\Model\Entity\SystemComponentController
     * The requested component controller or null if no such component exists
     */
    public function getComponent($name)
    {
        if (empty($name)) {
            return null;
        }
        $componentRepo = \Cx\Core\Core\Controller\Cx::instanciate()
                            ->getDb()
                            ->getEntityManager()
                            ->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $component     = $componentRepo->findOneBy(array('name' => $name));
        if (!$component) {
            throw new CalendarException('The component => '. $name .' not available');
        }
        return $component;
    }

    /**
     * Returns the date/time string (according to the calendar's
     * configuration) from a \DateTime object.
     *
     * The SUPPLIED \DateTime object must be in INTERNAL timezone.
     * The RETURNED date/time string will be in USER timezone.
     *
     * @param \DateTime $dateTime DateTime object in internal timezone
     * @return string A date/time string
     */
    public function format2userDateTime(\DateTime $dateTime)
    {
        return $this->formatDateTime2user($dateTime, $this->getDateFormat() .' H:i');
    }

    /**
     * Returns the date string (according to the calendar's
     * configuration) from a \DateTime object.
     *
     * The SUPPLIED \DateTime object must be in INTERNAL timezone.
     * The RETURNED date string will be in USER timezone.
     *
     * @param \DateTime $dateTime DateTime object in internal timezone
     * @return string A date string
     */
    public function format2userDate(\DateTime $dateTime)
    {
        return $this->formatDateTime2user($dateTime, $this->getDateFormat());
    }

    /**
     * Returns the time string 'H:i' from a \DateTime object
     *
     * The SUPPLIED \DateTime object must be in INTERNAL timezone.
     * The RETURNED time string will be in USER timezone.
     *
     * @param \DateTime $dateTime DateTime object in internal timezone
     * @return string A time string
     */
    public function format2userTime(\DateTime $dateTime)
    {
        return $this->formatDateTime2user($dateTime, 'H:i');
    }

    /**
     * Returns a date/time string from a \DateTime object.
     *
     * The SUPPLIED \DateTime object must be in INTERNAL timezone.
     * The RETURNED date/time string will be in USER timezone.
     *
     * @param \DateTime $dateTime DateTime object in internal timezone
     * @param string $format Format string
     * @return string A date/time string formatted according to $format
     */
    public function formatDateTime2user(\DateTime $dateTime, $format)
    {
        return $this->getUserDateTimeFromIntern($dateTime)
                    ->format($format);
    }

    /**
     * Returns a \DateTime object in user timezone
     *
     * The SUPPLIED \DateTime object must be in INTERNAL timezone.
     * The RETURNED \DateTime object will be in USER timezone.
     *
     * @param \DateTime $dateTime \DateTime object in internal timezone
     * @return \DateTime \DateTime in user timezone
     */
    public function getUserDateTimeFromIntern(\DateTime $dateTime)
    {
        $dateTimeInUserTimezone = clone($dateTime);
        return $this->getComponent('DateTime')->intern2user($dateTimeInUserTimezone);
    }

    /**
     * Returns a \DateTime object from a date/time string.
     *
     * The SUPPLIED date/time string must be in USER timezone.
     * The RETURNED \DateTime object will be in INTERNAL timezone.
     * 
     * @param string $time A date/time string in user timezone
     * @return \DateTime \DateTime object in internal timezone
     */
    public function getInternDateTimeFromUser($time = 'now')
    {
        $dateTime = $this->getComponent('DateTime')->createDateTimeForUser($time);
        return $this->getComponent('DateTime')->user2intern($dateTime);
    }

    /**
     * Returns a \DateTime object from a date/time string.
     *
     * The SUPPLIED date/time string must be in DB timezone.
     * The RETURNED \DateTime object will be in INTERNAL timezone.
     * 
     * @param string $time A date/time string in db timezone
     * @return \DateTime \DateTime object in internal timezone
     */
    public function getInternDateTimeFromDb($time = 'now')
    {
        $dateTime = $this->getComponent('DateTime')->createDateTimeForDb($time);
        return $this->getComponent('DateTime')->db2intern($dateTime);
    }

    /**
     * Returns a \DateTime object in db timezone
     *
     * The SUPPLIED \DateTime object must be in INTERNAL timezone.
     * The RETURNED \DateTime object will be in DB timezone.
     *
     * @param \DateTime $dateTime \DateTime object in internal timezone
     * @return \DateTime \DateTime in db timezone
     */
    public function getDbDateTimeFromIntern(\DateTime $dateTime)
    {
        $dateTimeInDbTimezone = clone($dateTime);
        return $this->getComponent('DateTime')
                    ->intern2db($dateTimeInDbTimezone);
    }

    /**
     * Trigger the event
     *
     * @param string  $eventName trigger event name
     * @param object  $entity    entity object
     * @param array   $relations entity relations
     * @param boolean $isDetach  is detachable entity
     *
     * @return null
     */
    public function triggerEvent(
        $eventName,
        $entity = null,
        $relations = array(),
        $isDetach = false
    ) {
        if (empty($eventName)) {
            return null;
        }

        if ($eventName == 'clearEsiCache') {
            $this->cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array('Widget', static::getHeadlinePlaceholders())
            );
            return;
        }

        if ($eventName == 'model/postFlush') {
            $this->cx->getEvents()->triggerEvent(
                $eventName,
                array(
                    new \Doctrine\ORM\Event\PostFlushEventArgs($this->em)
                )
            );
            return;
        }

        if (!$entity) {
            return null;
        }

        if ($isDetach) {
            if (!empty($relations) && $relations['relations']) {
                $this->detachJoinedEntity(
                    $entity,
                    $relations['relations'],
                    isset($relations['joinEntityRelations']) ? $relations['joinEntityRelations'] : array()
                );
            }
            $this->em->detach($entity);
        }

        $this->cx->getEvents()->triggerEvent(
            $eventName,
            array(
                new \Doctrine\ORM\Event\LifecycleEventArgs(
                    $entity, $this->em
                )
            )
        );
    }

    /**
     * Detach the entity
     *
     * @param object $entity             entity object
     * @param string $methodName         method name
     * @param array  $relation           relationship array
     * @param array  $joinEntityRelation joined entity's relationship array
     *
     * @return null
     */
    public function detachEntity(
        $entity,
        $methodName,
        $relation,
        $joinEntityRelation
    ) {
        if (!$entity || empty($methodName) || empty($relation)) {
            return null;
        }

        if (!method_exists($entity, $methodName) || !($entity->$methodName())) {
            return null;
        }

        if ($relation == 'oneToMany') {
            foreach ($entity->$methodName() as $subEntity) {
                if (isset($joinEntityRelation[$methodName])) {
                    $this->detachJoinedEntity(
                        $subEntity,
                        $joinEntityRelation[$methodName],
                        $joinEntityRelation
                    );
                }
                $this->em->detach($subEntity);
            }
        } else if ($relation == 'manyToOne') {
            if (isset($joinEntityRelation[$methodName])) {
                $this->detachJoinedEntity(
                    $entity->$methodName(),
                    $joinEntityRelation[$methodName],
                    $joinEntityRelation
                );
            }
            $this->em->detach($entity->$methodName());
        }
    }

    /**
     * Detach the jointed entity
     *
     * @param object $entity             entity object
     * @param array  $relations          relationship array
     * @param array  $joinEntityRelation joined entity's relationship array
     *
     * @return null
     */
    public function detachJoinedEntity(
        $entity,
        $relations,
        $joinEntityRelation
    ) {
        if (!$entity || empty($relations)) {
            return null;
        }

        foreach ($relations as $relation => $methodName) {
            if (!is_array($methodName)) {
                $this->detachEntity(
                    $entity,
                    $methodName,
                    $relation,
                    $joinEntityRelation
                );
                continue;
            }

            foreach ($methodName as $functionName) {
                $this->detachEntity(
                    $entity,
                    $functionName,
                    $relation,
                    $joinEntityRelation
                );
            }
        }
    }

    /**
     * Get the list of calendar headline placeholders
     *
     * @return array
     */
    public static function getHeadlinePlaceholders()
    {
        $placeholders = array();
        for ($i = 1; $i <= 21; $i++) {
            $id = '';
            if ($i > 1) {
                $id = $i;
            }

            $placeholders[] = 'EVENTS' . $id . '_FILE';
        }

        return $placeholders;
    }

    /**
     * Split a datetime string (i.E.: '08.06.2015 13:37') into an array
     * containing the date, hour and minutes information as separate
     * elements.
     *
     * @param   string  $datetime   The datetime string to parse.
     * @param   boolean $allDay     If set to TRUE, then the returned hour
     *                              and minutes value are set to 0, unless
     *                              $end is also set to TRUE.
     * @param   boolean $end        If set to TRUE and $allDay is also set to
     *                              TRUE, then the returned hour is set to 23
     *                              and the minutes to 59. If $allDay is not
     *                              set to TRUE, then this argument has no
     *                              effect.
     * @return  array               Return parsed datetime as array having the
     *                              following format:
     *                              <pre>array(
     *                                  d.m.Y,
     *                                  G,
     *                                  m
     *                             )</pre>
     */
    protected function parseDateTimeString(
        $datetime,
        $allDay = false,
        $end = false
    ) {
        // init time defaults
        $hour = 0;
        $min = 0;

        // set end time defaults for all-day event
        if ($allDay && $end) {
            $hour = 23;
            $min = 59;
        }

        // fetch data
        $parts = explode(' ', $datetime);
        $date = $parts[0];

        // fetch time if event is not an all-day event
        if (
            !$allDay &&
            isset($parts[1])
        ) {
            // match time as HH:MM / HH.MM / HH,MM / HHMM
            $timeData = preg_split(
                '/(?:[.,:]|(\d{2}$))/',
                $parts[1],
                2,
                PREG_SPLIT_DELIM_CAPTURE
            );
            if (isset($timeData[0])) {
                // remove leading zero
                $hour = intval($timeData[0]);
            }
            if (isset($timeData[1])) {
                $min = $timeData[1];
            }
        }

        return array(
            $date,
            $hour,
            $min
        );
    }
}
