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

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
$uri = $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);

if(substr($uri,-1) != '/') {
    $uri = $uri.'/';
}

$options = array('uri' => $protocol.$uri);

$SOAPServer = new \SoapServer(null, $options);
$SOAPServer->setClass('CalendarWebserviceServer');
$SOAPServer->handle();

/**
 * Calendar Class CalendarWebserviceServer
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarWebserviceServer
{
    /**
     * Directory path
     *
     * @access private
     * @var string
     */
    private $dirPath;

    /**
     * Table prefix
     *
     * @var string
     */
    private $tablePrefix;

    /**
     * CalendarWebserviceServer Constructor
     */
    public function __construct() {

        $this->dirPath = str_replace("/modules/Calendar/Controller","", dirname($_SERVER['SCRIPT_FILENAME']));
        \Env::get('ClassLoader')->loadFile($this->dirPath.'/config/configuration.php');

        mysql_connect($_DBCONFIG['host'], $_DBCONFIG['user'], $_DBCONFIG['password']);
        mysql_select_db ($_DBCONFIG['database']);
        mysql_set_charset($_DBCONFIG['charset']);

        $this->tablePrefix = $_DBCONFIG['tablePrefix'];
    }

    /**
     * verify the host name, if its exists returns the host data
     *
     * @param string $foreignHost host name
     * @param string $foreignKey  reference Key
     *
     * @return mixed host details on success, false otherwise
     */
    public function verifyHost($foreignHost,$foreignKey) {
        if(substr($foreignHost,0,7) == 'http://') {
            $foreignHost = substr($foreignHost,7);
        }

        if(substr($foreignHost,0,4) == 'www.') {
            $foreignHost = substr($foreignHost,4);
        }

        $query = "SELECT `id`,`key`,`cat_id` FROM ".$this->tablePrefix."module_calendar_host WHERE uri LIKE '%".$foreignHost."' LIMIT 1";
        $result = mysql_query($query);
        $row = mysql_fetch_array($result);

        if($row['key'] == $foreignKey) {
            $hostData = array();
            $hostData['id'] = intval($row['id']);
            $hostData['cat_id'] = intval($row['cat_id']);
            $hostData['key'] = $row['key'];

            return $hostData;
        } else {
            return false;
        }
    }

    /**
     * Get the event list
     *
     * @param integer $start_date                     Start date
     * @param integer $end_date                       End date
     * @param boolean $auth                           Authorization
     * @param string  $term                           search term
     * @param integer $langId                         Language id
     * @param integer $hostId                         Foreign Host id
     * @param integer $foreignHostId                  Host id
     * @param boolean $showEventsOnlyInActiveLanguage get event only active
     *                                                frontend language
     *
     * @return \CalendarWebserviceEvent Event list object
     */
    public function getEventList($start_date, $end_date, $auth, $term, $langId, $hostId, $foreignHostId, $showEventsOnlyInActiveLanguage)
    {
        $needAuth_where = ($auth == false ? ' AND event.access=0' : '');
        //$showIn_where = "AND event.show_in LIKE '%".intval($langId)."%' ";

        if($showEventsOnlyInActiveLanguage == 1) {
            $showIn_where = "AND FIND_IN_SET('".intval($langId)."',event.show_in)>0 ";
        } else {
            $showIn_where = "";
        }

        if (intval($end_date) != 0) {
            $dateScope_where = '((
                ((event.startdate <= '.$start_date.') AND ('.$end_date.' <= event.enddate)) OR
                ((('.$start_date.' <= event.startdate) AND ('.$end_date.' <= event.enddate)) AND ((event.startdate <= '.$start_date.') AND ('.$end_date.' <= event.enddate))) OR
                (((event.startdate <= '.$start_date.') AND (event.enddate <= '.$end_date.')) AND (('.$start_date.' <= event.enddate) AND (event.enddate <= '.$end_date.'))) OR
                (('.$start_date.' <= event.startdate) AND (event.enddate <= '.$end_date.'))
            ) OR (
                (event.series_status = 1) AND (event.startdate <= '.$end_date.')
            ))';

        } else {
            $dateScope_where = '((
                ((event.enddate >= '.$start_date.') AND (event.startdate <= '.$start_date.')) OR
                ((event.startdate >= '.$start_date.') AND (event.enddate >= '.$start_date.'))
            ) OR (
                (event.series_status = 1)
            ))';
        }

        if(!empty($term)) {
            $searchTerm_DB = ", ".$this->tablePrefix."module_calendar_event_field AS field";
            $searchTerm_where = " AND ((field.title LIKE '%".$term."%' OR field.description LIKE '%".$term."%' OR field.place LIKE '%".$term."%') AND field.event_id = event.id)";
        } else {
            $searchTerm_DB = "";
        }

        $query = "SELECT event.id AS id
                    FROM ".$this->tablePrefix."module_calendar_event AS event,
                         ".$this->tablePrefix."module_calendar_rel_event_host AS host
                         ".$searchTerm_DB."
                   WHERE ".$dateScope_where."
                         AND event.status=1
                         ".$needAuth_where."
                         ".$searchTerm_where."
                         ".$showIn_where."
                     AND (host.event_id = event.id AND host.host_id = '".$hostId."')
                GROUP BY event.id
                ORDER BY event.startdate";

        $result = mysql_query($query);

        $eventList = array();

        while ($row = mysql_fetch_array($result)) {
            $objEvent = new \Cx\Modules\Calendar\Controller\CalendarWebserviceEvent($row['id'], $langId, $showEventsOnlyInActiveLanguage);
            $objEvent->hostId = $foreignHostId;

            $eventList[] = $objEvent;
        }

        return $eventList;
    }

    /**
     * addiere
     *
     * @param integer $a number value
     * @param integer $b number value
     *
     * @return integer sum of the numbers
     */
    public function addiere($a, $b) {
        return $a + $b + $a;
    }
}

/**
 * Calendar Class CalendarWebserviceEvent
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarWebserviceEvent
{
    /**
     * Event id
     *
     * @access public
     * @var integer
     */
    public $id;

    /**
     * Event Type
     *
     * @access public
     * @var integer
     */
    public $type;

    /**
     * Event host id
     *
     * @access public
     * @var string
     */
    public $hostId;

    /**
     * Event title
     *
     * @var string
     * @access public
     */
    public $title;

    /**
     * Event Picture
     *
     * @access public
     * @var string
     */
    public $pic;

    /**
     * Event attachment file name
     *
     * @access public
     * @var string
     */
    public $attach;

    /**
     * Event Start date timestamp
     *
     * @access public
     * @var integer
     */
    public $startDate;

    /**
     * Event enddate timestamp
     *
     * @access public
     * @var integer
     */
    public $endDate;

    /**
     * Event show start date on list view
     *
     * @access public
     * @var boolean
     */
    public $showStartDateList;

    /**
     * Event show End date on list view
     *
     * @access public
     * @var boolean
     */
    public $showEndDateList;

    /**
     * Event show start time on list view
     *
     * @access public
     * @var boolean
     */
    public $showStartTimeList;

    /**
     * Event show End time on list view
     *
     * @access public
     * @var boolean
     */
    public $showEndTimeList;

    /**
     * Event time type on list view
     *
     * @access public
     * @var integer
     */
    public $showTimeTypeList;

    /**
     * Event show start date on detail view
     *
     * @access public
     * @var boolean
     */
    public $showStartDateDetail;

    /**
     * Event show end date on detail view
     *
     * @access public
     * @var boolean
     */
    public $showEndDateDetail;

    /**
     * Event show start time on detail view
     *
     * @access public
     * @var boolean
     */
    public $showStartTimeDetail;

    /**
     * Event show end time on detail view
     *
     * @access public
     * @var boolean
     */
    public $showEndTimeDetail;

    /**
     * Event time type on detail view
     *
     * @access public
     * @var integer
     */
    public $showTimeTypeDetail;

    /**
     * Event priority
     *
     * @access public
     * @var integer
     */
    public $priority;

    /**
     * Event show end date on detail view
     *
     * @access public
     * @var boolean
     */
    public $access;

    /**
     * Event description
     *
     * @access public
     * @var string
     */
    public $description;

    /**
     * Event place
     *
     * @access public
     * @var string
     */
    public $place;

    /**
     * Event status
     *
     * @access public
     * @var integer
     */
    public $status;

    /**
     * Category IDs
     * @access public
     * @var     array
     */
    public $category_ids = null;

    /**
     * Event series status
     *
     * @access public
     * @var integer
     */
    public $seriesStatus;

    /**
     * Event series data
     *
     * @access public
     * @var array
     */
    public $seriesData = array();

    /**
     * Event languages to show
     *
     * @access public
     * @var array
     */
    public $showIn;

    /**
     * Avaliable languages
     *
     * @access public
     * @var array
     */
    public $availableLang;

    /**
     * Event map status
     *
     * @access public
     * @var integer
     */
    public $map;

    /**
     * Event invited group
     *
     * @access public
     * @var array
     */
    public $invitedGroups = array();

    /**
     * Event invited mail
     *
     * @access public
     * @var array
     */
    public $invitedMails = array();

    /**
     * is Event invitation sent
     *
     * @access public
     * @var boolean
     */
    public $invitationSent;

    /**
     * Event status of registration
     *
     * @access public
     * @var boolean
     */
    public $registration;

    /**
     * Event number of subscriber
     *
     * @access public
     * @var integer
     */
    public $numSubscriber;

    /**
     * Event notification the event
     *
     * @access public
     * @var string
     */
    public $notificationTo;

    /**
     * Event related websites
     *
     * @access public
     * @var array
     */
    public $relatedHosts = array();
    /**
     * Event data
     *
     * @access public
     * @var array
     */
    public $arrData = array();

    /**
     * External
     *
     * @access public
     * @var boolean
     */
    public $external = false;

    /**
     * showEventsOnlyInActiveLanguage
     *
     * @var boolean get event only active frontend language
     */
    public $showEventsOnlyInActiveLanguage = 1;

    /**
     * Language id
     *
     * @access private
     * @var integer language id
     */
    private $langId;

    /**
     * Directory path
     *
     * @access private
     * @var string
     */
    private $dirPath;

    /**
     * Table prefix
     *
     * @access private
     * @var string
     */
    private $tablePrefix;

    /**
     * Character Encoding format from config
     *
     * @access private
     * @var string Character Encoding
     */
    private $coreCharacterEncoding;

    /**
     * Constructor
     *
     * Loads the event object of given id
     *
     * @param integer $id                             Event id
     * @param integer $langId                         Language id
     * @param boolean $showEventsOnlyInActiveLanguage get event only active
     *                                                frontend language
     */
    public function __construct($id=null, $langId=null, $showEventsOnlyInActiveLanguage){
        $this->dirPath = str_replace("/modules/Calendar/Controller/","", dirname($_SERVER['SCRIPT_FILENAME']));
        \Env::get('ClassLoader')->loadFile($this->dirPath.'/config/configuration.php');

        mysql_connect($_DBCONFIG['host'], $_DBCONFIG['user'], $_DBCONFIG['password']);
        mysql_select_db ($_DBCONFIG['database']);
        mysql_set_charset($_DBCONFIG['charset']);

        $this->tablePrefix = $_DBCONFIG['tablePrefix'];
        $this->coreCharacterEncoding = $_CONFIG['coreCharacterEncoding'];
        $this->langId = $langId;
        $this->showEventsOnlyInActiveLanguage = $showEventsOnlyInActiveLanguage;


        if($id != null) {
            self::get($id, null);
        }
    }

    /**
     * Load the requested event by id
     *
     * @param integer $eventId        Event Id
     * @param integer $langId         Language id
     *
     * @return null
     */
    private function get($eventId, $langId)
    {
        \Env::get('ClassLoader')->loadFile($this->dirPath . '/config/configuration.php');
        if($langId == null) {
            $lang_where = "AND field.lang_id = '".intval($this->langId)."' ";
        } else {
            $lang_where = "AND field.lang_id = '".intval($langId)."' ";
        }
        $query = "
            SELECT event.id,
                event.type,
                event.startdate,
                event.enddate,
                event.showStartDateList,
                event.showEndDateList,
                event.showStartTimeList,
                event.showEndTimeList,
                event.showTimeTypeList,
                event.showStartDateDetail,
                event.showEndDateDetail,
                event.showStartTimeDetail,
                event.showEndTimeDetail,
                event.showTimeTypeDetail,
                event.access,
                event.pic,
                event.attach,
                event.priority,
                event.status,
                event.show_in,
                event.google,
                event.invited_groups,
                event.invited_mails,
                event.invitation_sent,
                event.registration,
                event.registration_num,
                event.registration_notification,
                event.series_status,
                event.series_type,
                event.series_pattern_count,
                event.series_pattern_weekday,
                event.series_pattern_day,
                event.series_pattern_week,
                event.series_pattern_month,
                event.series_pattern_type,
                event.series_pattern_dourance_type,
                event.series_pattern_end,
                event.series_pattern_end_date,
                event.series_pattern_begin,
                event.series_pattern_exceptions,
                field.title,
                field.description,
                         field.place AS place
                    FROM ".$this->tablePrefix."module_calendar_event AS event,
                         ".$this->tablePrefix."module_calendar_event_field AS field
                   WHERE event.id = '".intval($eventId)."'
            AND (event.id=field.event_id $lang_where)
                   LIMIT 1";
        $result = mysql_query($query);
        $count = mysql_num_rows($result);
        if($this->showEventsOnlyInActiveLanguage == 2) {
            if($count == 0) {
                if($langId == null) {
                    $langId = 1;
                } else {
                    $langId++;
                }

                if($langId <= 99) {
                    self::get($eventId,$langId);
                }
            } else {
                if($langId == null) {
                    $langId = $_LANGID;
                }
            }
        } else {
           $langId = $_LANGID;
        }

        while ($row = mysql_fetch_array($result)) {
            if(!empty($row['title'])) {
                $this->id = intval($eventId);
                $this->type = intval($row['type']);
                $this->title = htmlentities(stripslashes($row['title']),ENT_QUOTES,$this->coreCharacterEncoding);
                $this->pic = htmlentities(stripslashes($row['pic']),ENT_QUOTES,$this->coreCharacterEncoding);
                $this->attach = htmlentities(stripslashes($row['attach']),ENT_QUOTES,$this->coreCharacterEncoding);
                $this->startDate = intval($row['startdate']);
                $this->endDate = intval($row['enddate']);
                $this->showStartDateList = intval($row['showStartDateList']);
                $this->showEndDateList = intval($row['showEndDateList']);
                $this->showStartTimeList = intval($row['showStartTimeList']);
                $this->showEndTimeList = intval($row['showEndTimeList']);
                $this->showTimeTypeList = intval($row['showTimeTypeList']);
                $this->showStartDateDetail = intval($row['showStartDateDetail']);
                $this->showEndDateDetail = intval($row['showEndDateDetail']);
                $this->showStartTimeDetail = intval($row['showStartTimeDetail']);
                $this->showEndTimeDetail = intval($row['showEndTimeDetail']);
                $this->showTimeTypeDetail = intval($row['showTimeTypeDetail']);
                $this->invitationSent = intval($row['invitation_sent']);
                $this->access = intval($row['access']);
                $this->priority = intval($row['priority']);
                $this->description = stripslashes($row['description']);
                $this->place = htmlentities(stripslashes($row['place']),ENT_QUOTES,$this->coreCharacterEncoding);
                $this->showIn = htmlentities(stripslashes($row['show_in']),ENT_QUOTES,$this->coreCharacterEncoding);
                $this->availableLang = intval($langId);
                $this->status = intval($row['status']);
                $this->map = intval($row['google']);
                $this->seriesStatus = intval($row['series_status']);
                if($this->seriesStatus == 1) {
                    $this->seriesData['seriesPatternCount'] = intval($row['series_pattern_count']);
                    $this->seriesData['seriesType'] = intval($row['series_type']);
                    $this->seriesData['seriesPatternCount'] = intval($row['series_pattern_count']);
                    $this->seriesData['seriesPatternWeekday'] = htmlentities($row['series_pattern_weekday'], ENT_QUOTES, CONTREXX_CHARSET);
                    $this->seriesData['seriesPatternDay'] = intval($row['series_pattern_day']);
                    $this->seriesData['seriesPatternWeek'] = intval($row['series_pattern_week']);
                    $this->seriesData['seriesPatternMonth'] = intval($row['series_pattern_month']);
                    $this->seriesData['seriesPatternType'] = intval($row['series_pattern_type']);
                    $this->seriesData['seriesPatternDouranceType'] = intval($row['series_pattern_dourance_type']);
                    $this->seriesData['seriesPatternEnd'] = intval($row['series_pattern_end']);
                    $this->seriesData['seriesPatternEndDate'] = strtotime($row['series_pattern_end_date']);
                    $this->seriesData['seriesPatternBegin'] = intval($row['series_pattern_begin']);
                    $this->seriesData['seriesPatternExceptions'] = array_map('strtotime', (array) explode(",", $row['series_pattern_exceptions']));
                }
                $this->invitedGroups = explode(',', $row['invited_groups']);
                $this->invitedMails =  htmlentities(
                    stripslashes($row['invited_mails']),
                    ENT_QUOTES, $this->coreCharacterEncoding);
                $this->registration = intval($row['registration']);
                $this->numSubscriber = intval($row['registration_num']);
                $this->notificationTo = htmlentities(
                    stripslashes($row['registration_notification']),
                    ENT_QUOTES, $this->coreCharacterEncoding);
                $calendarCategory = new CalendarCategory();
                $this->category_ids = $calendarCategory->getIdsByEventId($eventId);
                $this->getData();
            }
        }
    }

    /**
     * set the event start date
     *
     * @param integer $value start date
     *
     * @return null
     */
    public function setStartDate($value){
        $this->startDate = intval($value);
    }

    /**
     * set the event end date
     *
     * @param integer $value End date
     *
     * @return null
     */
    public function setEndDate($value){
        $this->endDate = intval($value);
    }

    /**
     * gets the data for the event
     *
     * @return null
     */
    private function getData()
    {
        $activeLangs = explode(",", $this->showIn);
        $this->arrData = array();

        foreach ($activeLangs as $key => $langId) {
            $query2 = "SELECT field.title AS title3,
                             field.place AS place,
                             field.place_street AS place_street,
                             field.place_zip AS place_zip,
                             field.place_city AS place_city,
                             field.place_country AS place_country,
                             field.description AS description
                        FROM ".$this->tablePrefix."module_calendar_event_field AS field
                       WHERE field.event_id = '".intval($this->id)."'
                         AND field.lang_id = '".intval($langId)."'
                       LIMIT 1";

            $result2 = mysql_query($query2);

            while ($row2 = mysql_fetch_array($result2)) {
                $this->arrData['title'][intval($langId)] = htmlentities(stripslashes($row2['title']),ENT_QUOTES, $this->coreCharacterEncoding);
                $this->arrData['place'][intval($langId)] = htmlentities(stripslashes($row2['place']),ENT_QUOTES, $this->coreCharacterEncoding);
                $this->arrData['place_street'][intval($langId)] = htmlentities(stripslashes($row2['place_street']), ENT_QUOTES, $this->coreCharacterEncoding);
                $this->arrData['place_zip'][intval($langId)] = htmlentities(stripslashes($row2['place_zip']),ENT_QUOTES, $this->coreCharacterEncoding);
                $this->arrData['place_city'][intval($langId)] = htmlentities(stripslashes($row2['place_city']),ENT_QUOTES, $this->coreCharacterEncoding);
                $this->arrData['place_country'][intval($langId)] = htmlentities(stripslashes($row2['place_country']),ENT_QUOTES, $this->coreCharacterEncoding);
                $this->arrData['description'][intval($langId)] = $row2['description'];
            }
        }
    }
}
