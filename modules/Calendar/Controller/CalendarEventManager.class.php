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
 * Calendar Class EventManager
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarEventManager extends CalendarLibrary
{
    /**
     * Start date
     *
     * @access private
     * @var integer
     */
    private $startDate;

    /**
     * End date
     *
     * @access private
     * @var integer
     */
    private $endDate;

    /**
     * Date
     *
     * @access private
     * @var string
     */
    private $date;

    /**
     * Seprator date time
     *
     * @access private
     * @var string
     */
    private $sepDateTime;

    /**
     * Time
     *
     * @access private
     * @var string
     */
    private $time;

    /**
     * Clock
     *
     * @access private
     * @var string
     */
    private $clock;

    /**
     * Category id
     *
     * @access private
     * @var integer
     */
    private $categoryId;

    /**
     * show series
     *
     * @access private
     * @var boolean
     */
    private $showSeries;

    /**
     * Search term
     *
     * @access private
     * @var string
     */
    private $searchTerm;

    /**
     * Need authorization
     *
     * @access private
     * @var boolean
     */
    private $needAuth;

    /**
     * Only active
     *
     * @access public
     * @var boolean
     */
    private $onlyActive;

    /**
     * Start position
     *
     * @access private
     * @var integer
     */
    private $startPos;

    /**
     * Num Events
     *
     * @access private
     * @var integer
     */
    private $numEvents;

    /**
     * Sort Direction
     *
     * @access private
     * @var string
     */
    private $sortDirection;

    /**
     * Only confirmed
     *
     * @access private
     * @var boolean
     */
    private $onlyConfirmed;

    /**
     * Author name
     *
     * @access private
     * @var string
     */
    private $author;

    /**
     * Event list
     *
     * @access private
     * @var array
     */
    public $eventList = array();

    /**
     * Event count
     *
     * @access private
     * @var integer
     */
    public $countEvents;

    /**
     * show only upcoming events or all events
     * possible options are all or upcoming
     *
     * default is all
     *
     * @var string
     */
    public $listType;

    /**
     * @var array List of indexData of calendar events synced from a remote location
     */
    protected static $syncedIds;

    /**
     * Loads the event manager configuration
     *
     * @param integer $startDate     Start date Unix timestamp
     * @param integer $endDate       End date timestamp
     * @param integer $categoryId    Category Id
     * @param string  $searchTerm    Search Term
     * @param boolean $showSeries    Show Series
     * @param boolean $needAuth      Need authorization
     * @param boolean $onlyActive    Only active Events
     * @param integer $startPos      Start position
     * @param integer $numEvents     Number of events
     * @param string  $sortDirection Sort direction, possible values ASC, DESC
     * @param boolean $onlyConfirmed only confirmed Entries
     * @param string  $author        author name
     */
    function __construct(\DateTime $startDate = null, \DateTime $endDate = null, $categoryId=null, $searchTerm=null, $showSeries=true, $needAuth=false, $onlyActive=false, $startPos=0, $numEvents='n', $sortDirection='ASC', $onlyConfirmed=true, $author=null, $listType = 'all') {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->categoryId = intval($categoryId);
        $this->showSeries = $showSeries;
        $this->searchTerm = contrexx_addslashes($searchTerm);
        $this->needAuth = $needAuth;
        $this->onlyActive = $onlyActive;
        $this->startPos = $startPos;
        $this->numEvents = $numEvents;
        $this->sortDirection = $sortDirection;
        $this->onlyConfirmed = $onlyConfirmed;
        $this->author = $author;
        $this->listType = $listType;
        $this->init();
    }

    /**
     * Get's list of event and assign into $this->eventList
     *
     * @return null
     */
    function getEventList() {
        global $objDatabase, $_ARRAYLANG, $_LANGID, $objInit;
        $this->getSettings();
        // need for database TIMESTAMP
        $startDate = $this->startDate
            ? $this->getDbDateTimeFromIntern($this->startDate)->format('Y-m-d H:i:s')
            : '0000-00-00 00:00:00';
        $endDate   = $this->endDate
            ? $this->getDbDateTimeFromIntern($this->endDate)->format("Y-m-d H:i:s")
            : '0000-00-00 00:00:00';
        $onlyActive_where = ($this->onlyActive ? ' AND event.status=1' : '');
        $joins = $categoryId_where = $showIn_where = $dateScope_where =
            $searchTerm_where = $searchTerm_DB = $author_where = '';
        if ($this->categoryId) {
            $categoryId_where = '
                AND rel_categories.category_id='.intval($this->categoryId);
            $joins = '
                JOIN ' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_events_categories AS rel_categories
                ON event.id=rel_categories.event_id';
        }
        if ($objInit->mode !== \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            if($this->arrSettings['showEventsOnlyInActiveLanguage'] == 1) {
                $showIn_where = "
                    AND FIND_IN_SET('" . $_LANGID . "', event.show_in)>0";
            }
        }
        if ($this->endDate !== null) {
            // Note: 'NOW' in the following comments refers to the filtered
            //       date of the request.
            $dateScope_where = '
                (('
                // Event is happening now (it did already start) and will go on
                // after the selected range.
                // Logic: startdate <= START && enddate <= END
                .'(event.startdate <= "'.$startDate.'" AND "'.$endDate.'" <= event.enddate) OR '
                // Event is about to happen in the selected range, but will go
                // on afterwards as well.
                // Logic: START <= startdate <= END <= enddate
                .'("'.$startDate.'" <= event.startdate AND "'.$endDate.'" <= event.enddate AND event.startdate <= "'.$endDate.'") OR '
                // Event is happening now and is about to end in the selected range.
                // Logic: startdate <= START <= enddate <= END
                .'(event.startdate <= "'.$startDate.'" AND event.enddate <= "'.$endDate.'" AND "'.$startDate.'" <= event.enddate) OR '
                // Event is happening exactly within the selected range
                // Logic: START <= startdate <= enddate <= END
                .'("'.$startDate.'" <= event.startdate AND event.enddate <= "'.$endDate.'")
            ) OR (
                (event.series_status = 1) AND (event.startdate <= "'.$endDate.'")
            ))';
        } else {
            // Note: 'NOW' in the following comments refers to the filtered
            //       date of the request.
            $dateScope_where = '(('
                // event is happening now (startdate <= NOW <= enddate)
                .'((event.enddate >= "'.$startDate.'") AND (event.startdate <= "'.$startDate.'")) OR '
                // event lies in the future (NOW <= startdate <= enddate)
                .'((event.startdate >= "'.$startDate.'") AND (event.enddate >= "'.$startDate.'"))
            ) OR (
                (event.series_status = 1)
            ))';
        }

        if(!empty($this->searchTerm) && $this->searchTerm != $_ARRAYLANG['TXT_CALENDAR_KEYWORD']) {
            $searchTerm_DB = ", ".DBPREFIX."module_".$this->moduleTablePrefix."_event_field AS field";
            $searchTerm_where = " AND ((field.title LIKE '%".$this->searchTerm."%' OR field.teaser LIKE '%".$this->searchTerm."%' OR field.description LIKE '%".$this->searchTerm."%' OR field.place LIKE '%".$this->searchTerm."%') AND field.event_id = event.id)";
        } else {
            $searchTerm_where = $searchTerm_DB = '';
        }

        if($this->onlyConfirmed) {
            $confirmed_where =' AND (event.confirmed = 1)';
        } else {
            $confirmed_where =' AND (event.confirmed = 0)';
        }

        $author_where = '';
        if(intval($this->author) != 0) {
            $author_where =' AND (event.author = '.intval($this->author).')';
        }

        $query = "
            SELECT event.id AS id
            FROM " . DBPREFIX . "module_" . $this->moduleTablePrefix . "_event AS event
                $searchTerm_DB
                $joins
            WHERE $dateScope_where
                $onlyActive_where
                $categoryId_where
                $searchTerm_where
                $showIn_where
                $confirmed_where
                $author_where
            GROUP BY event.id
            ORDER BY event.startdate";
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            $objFWUser = \FWUser::getFWUserObject();
            while (!$objResult->EOF) {
                $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent(intval($objResult->fields['id']));

                if ($objEvent->access) {
                    // cache userbased
                    $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                    $cx->getComponent('Cache')->forceUserbasedPageCache();
                    if (!$objFWUser->objUser->login()) {
                        $objResult->MoveNext();
                        continue;
                    }
                }
                if ($objInit->mode !== \Cx\Core\Core\Controller\Cx::MODE_BACKEND
                    || $this->showSeries) {
                    $checkFutureEvents = true;
                    if($this->_addToEventList($objEvent)) {
                        $this->eventList[] = $objEvent;
                        if ($this->listType == 'upcoming') {
                            $checkFutureEvents = false;
                        }
                    }

                    if ($checkFutureEvents &&
                        $objEvent->seriesStatus == 1 &&
                        (
                            !isset($_GET['cmd']) ||
                            $_GET['cmd'] != 'my_events'
                        )
                    ) {
                        $additionalRecurrences = $objEvent->seriesData['seriesAdditionalRecurrences'];
                        $this->generateRecurrencesOfEvent($objEvent, $additionalRecurrences);
                    }
                } else {
                    $this->eventList[] = $objEvent;
                }
                $objResult->MoveNext();
            }
        }
        self::_clearEmptyEvents();
        self::_sortEventList();
        $this->countEvents = count($this->eventList);
        if ($this->numEvents != 'n' && $this->numEvents != 0) {
            $this->eventList = array_slice($this->eventList, $this->startPos, $this->numEvents);
        }
    }

    /**
     * Import Events
     *
     * @return null
     */
    function _importEvents()
    {
        global $objDatabase, $objInit, $_LANGID, $_CONFIG;

        if ($objInit->mode !== \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $this->getSettings();

            $objHostManager = new \Cx\Modules\Calendar\Controller\CalendarHostManager($this->categoryId, true, true);
            $objHostManager->getHostList();


            foreach($objHostManager->hostList as $key => $objHost)  {
                $id = $objHost->id;
                $name = $objHost->title;
                $key = $objHost->key;

                if(substr($objHost->uri,-1) != '/') {
                    $uri = $objHost->uri.'/';
                } else {
                    $uri = $objHost->uri;
                }

                if(substr($objHost->uri,0,7) != 'http://') {
                    $protocol = 'http://';
                } else {
                    $protocol = '';
                }

                $location = $protocol.$uri."modules/Calendar/Controller/CalendarWebserviceServer.class.php";

                if(self::urlfind($protocol.$uri)){
                    $connection = true;
                } else {
                    $connection = false;
                }

                if($connection) {
                    if($objWebserviceClient = new \Cx\Modules\Calendar\Controller\CalendarWebserviceClient($location, $uri)) {
                        $myHost = $_CONFIG['domainUrl'].ASCMS_PATH_OFFSET;

                        if(substr($myHost,-1) != '/') {
                            $myHost = $myHost.'/';
                        }

                        $catId = $objHost->catId;
                        $key = $objHost->key;

                        $foreignHostData = $objWebserviceClient->verifyHost($myHost,$key);

                        if($foreignHostData != false) {
                            $arrEvents = $objWebserviceClient->getEventList($this->startDate->getTimestamp(), $this->endDate->getTimestamp(), $this->needAuth, $this->searchTerm, $_LANGID, $foreignHostData['id'], $id, $this->arrSettings['showEventsOnlyInActiveLanguage']);

                            if(!empty($arrEvents[0])) {
                                foreach ($arrEvents as $key => $objExternalEvent) {
                                    /*$objExternalEvent->showStartDateList = intval($this->arrSettings['showStartDateList']);
                                    $objExternalEvent->showEndDateList = intval($this->arrSettings['showEndDateList']);
                                    $objExternalEvent->showStartTimeList = intval($this->arrSettings['showStartTimeList']);
                                    $objExternalEvent->showEndTimeList = intval($this->arrSettings['showEndTimeList']);
                                    $objExternalEvent->showTimeTypeList = intval($this->arrSettings['showTimeTypeList']);
                                    $objExternalEvent->showStartDateDetail = intval($this->arrSettings['showStartDateDetail']);
                                    $objExternalEvent->showEndDateDetail = intval($this->arrSettings['showEndDateDetail']);
                                    $objExternalEvent->showStartTimeDetail = intval($this->arrSettings['showStartTimeDetail']);
                                    $objExternalEvent->showEndTimeDetail = intval($this->arrSettings['showEndTimeDetail']);
                                    $objExternalEvent->showTimeTypeDetail = intval($this->arrSettings['showTimeTypeDetail']);*/
                                    $objExternalEvent->startDate = $this->getInternDateTimeFromDb($objExternalEvent->startDate);
                                    $objExternalEvent->endDate   = $this->getInternDateTimeFromDb($objExternalEvent->endDate);

                                    if($objExternalEvent->seriesStatus == 1 && $_GET['cmd'] != 'my_events') {
                                        $additionalRecurrences = $objExternalEvent->seriesData['seriesAdditionalRecurrences'];
                                        $this->generateRecurrencesOfEvent($objExternalEvent, $additionalRecurrences);
                                    }

                                    $this->eventList[] = $objExternalEvent;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Clears the empty events
     *
     * Empty events will be found if event title is empty
     *
     * @return null
     */
    function _clearEmptyEvents() {
        // customizing: hide synced events in backend
        $cx = \Env::get('cx');
        if (!isset(static::$syncedIds)) {
            $query = '
                SELECT
                    `local_id`
                FROM
                    `' . DBPREFIX . 'core_module_sync_id_mapping`
                WHERE
                    `entity_type` LIKE \'Cx\\\\\\\\Modules\\\\\\\\Calendar\\\\\\\\Model\\\\\\\\Entity\\\\\\\\Event\'
            ';
            $adoDb = $cx->getDb()->getAdoDb();
            $result = $adoDb->execute($query);
            static::$syncedIds = array();
            while (!$result->EOF) {
                static::$syncedIds[] = $result->fields['local_id'];
                $result->MoveNext();
            }
        }

         foreach($this->eventList as $key => $objEvent) {
             if(empty($objEvent->title)) {
                unset($this->eventList[$key]);
                continue;
            }
            if ($cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
                $indexData = serialize(array('id' => (string) $objEvent->getId()));
                if (in_array($indexData, static::$syncedIds)) {
                    unset($this->eventList[$key]);
                    continue;
                }
             }
         }
    }

    /**
     * Checks the event for adding it into eventlist
     *
     * This will used the check the whether the gievn event object is valid to
     * add into event list
     *
     * @param object $objEvent Event object
     *
     * @return boolean true if the event is valid, false oterwise
     */
    public function _addToEventList($objEvent) {
        if ($this->startDate == null) {
            if($objEvent->endDate < $this->endDate || $this->endDate == null) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($this->endDate == null) {
                if(($objEvent->endDate >= $this->startDate) || ($objEvent->startDate >= $this->startDate)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                if(
                     ($objEvent->startDate >= $this->startDate && $objEvent->startDate <= $this->endDate)
                  || ($objEvent->endDate >= $this->startDate && $objEvent->endDate <= $this->endDate)
                ) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Sort the event list
     *
     * @return null
     */
    function _sortEventList(){
        usort($this->eventList, array(__CLASS__, "cmp"));
    }

    /**
     * Compare function
     *
     * @param array $a first array
     * @param array $b second array
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    function cmp($a, $b)
    {
        if ($a->startDate == $b->startDate) {
            return 0;
        }

        if($this->sortDirection == 'DESC') {
            return ($a->startDate > $b->startDate) ? -1 : 1;
        } else {
            return ($a->startDate < $b->startDate) ? -1 : 1;
        }
    }

    /**
     * Get the event using calendar event class and assign it into $this->eventList
     *
     * @param mixed   $event         \Cx\Modules\Calendar\Controller\CalendarEvent
     *                                or Event id
     * @param integer $eventStartDate Unix timestamp of start date
     * @param boolean $forceCalculateSeries  Whether or not to calculate the event's next serie's occurrence. Default to FALSE to only perform the calculation in FRONTEND-Mode
     *
     * @return null
     */
    function getEvent($event, $eventStartDate, $forceCalculateSeries = false) {
        global $objInit;

        if ($event instanceof \Cx\Modules\Calendar\Controller\CalendarEvent) {
            $objEvent = $event;
        } else {
            $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent(intval($event));
        }

        $this->eventList[] = $objEvent;

        if (   $objEvent->seriesStatus == 1
            && ($forceCalculateSeries
            || $objInit->mode !== \Cx\Core\Core\Controller\Cx::MODE_BACKEND)
        ) {
            $additionalRecurrences = $objEvent->seriesData['seriesAdditionalRecurrences'];
            $this->generateRecurrencesOfEvent($objEvent, $additionalRecurrences);
        }
        foreach ($this->eventList as $tmpKey => $tmpObjEvent) {
            if (!$tmpObjEvent->startDate || $tmpObjEvent->startDate->getTimestamp() != $eventStartDate) {
                unset($this->eventList[$tmpKey]);
            }
        }

        sort($this->eventList);
    }

    /**
     * Import events
     *
     * @param integer $eventId        Event id
     * @param integer $eventStartDate Unix timestamp of start date
     *
     * @return null
     */
    function getExternalEvent($eventId, $eventStartDate) {
        global $objInit;

        self::_importEvents();

        foreach ($this->eventList as $tmpKey => $tmpObjEvent) {
            if ($tmpObjEvent->startDate->getTimestamp() != $eventStartDate) {
                unset($this->eventList[$tmpKey]);
            }
        }

        sort($this->eventList);
    }

    /**
     * Sets the placeholders used for the event
     *
     * @param object  $objTpl         Template object
     * @param integer $eventId        Event Id
     * @param integer $eventStartDate Description
     *
     * @return null
     */
    function showEvent($objTpl, $eventId, $eventStartDate, &$start = null) {
        global $objInit, $_ARRAYLANG, $_LANGID, $_CONFIG;

        $this->getSettings();

        // legacy db event object
        $objEvent = null;

        // id of the invite (if provided by the HTTP request)
        $inviteId = 0;

        // token of the invite (if provided by the HTTP request)
        $inviteToken = '';

        $request = $this->cx->getRequest();

        // fetch arguments from HTTP request
        try {
            $inviteId = $request->getParam(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_ID);
        } catch (\Exception $e) {}
        try {
            $inviteToken = $request->getParam(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_TOKEN);
        } catch (\Exception $e) {}

        // Doctrine entity event that is associated to the requested registration form
        $event = null;

        // invite of an invitee
        $invite = null;

        if ($objInit->mode === \Cx\Core\Core\Controller\Cx::MODE_BACKEND
            || $eventId == null || $eventStartDate == null) {
            return;
        }

        // in case the registration form was requested by an invitee
        // and the start-date of the event has changed meanwhile,
        // we shall try to load the event based on the invitation data
        if (empty($this->eventList[0])) {
            // abort in case the registration form has not been requested by an invitee
            if (empty($inviteId) || empty($inviteToken)) {
                return;
            }
        } else {
            // note: the associated event has been loaded in Calendar::loadEventManager()
            $objEvent = $this->eventList[0];
        }

        // Fetch requested event.
        // Note: In contrary to Calendar::loadEventManager(), the following
        // does not take the requested event-date into consideration.
        $eventRepo = $this->em->getRepository('Cx\Modules\Calendar\Model\Entity\Event');
        $event = $eventRepo->findOneBy(array(
            'id'     => $eventId,
            'status' => 1,
        ));

        // abort in case the event of the invitation is not published
        if (!$event) {
            \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, ''));
            return;
        }

        // check if event has been published in currently requested locale region
        if ($this->arrSettings['showEventsOnlyInActiveLanguage'] == 1) {
            $publishedLanguages = explode(',', $event->getShowIn());
            if (!in_array(FRONTEND_LANG_ID, $publishedLanguages)) {
                \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, ''));
                return;
            }
        }

        if (!$objEvent) {
            $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent($eventId);
            $this->eventList = array($objEvent);
        }

        // load invitation
        if (!empty($inviteId) && !empty($inviteToken)) {
            $inviteRepo = $this->em->getRepository('Cx\Modules\Calendar\Model\Entity\Invite');
            $invite = $inviteRepo->findOneBy(array(
                'event' => $event,
                'id'    => $inviteId,
                'token' => $inviteToken,
            ));
        }

        // abort in case the specific event date (or its iteration) isn't valid
        // and no valid invitation has been supplied
        if (!$objEvent && !$invite) {
            \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, ''));
            return;
        }

        // Abort in case the associated event is protected and the requestee has
        // not sufficient access rights.
        // Note: access to invitees is always granted
        if (!$invite && $objEvent->access == 1 && !\FWUser::getFWUserObject()->objUser->login()) {
            $link = base64_encode(CONTREXX_SCRIPT_PATH.'?'.$_SERVER['QUERY_STRING']);
            \Cx\Core\Csrf\Controller\Csrf::redirect(CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
            return;
        }
            $objCategory = CalendarCategory::getCurrentCategory(
                $this->categoryId, $objEvent);
        list ($priority, $priorityImg) = $this->getPriorityImage($objEvent);
        $plainDescription = contrexx_html2plaintext($objEvent->description);
        if (strlen($plainDescription) > 100) {
            $points = '...';
        } else {
            $points = '';
        }
        $parts= explode("\n", wordwrap($plainDescription, 100, "\n"));

        $attachNamePos  = strrpos($objEvent->attach, '/');
        $attachNamelength = strlen($objEvent->attach);
        $attachName        = substr($objEvent->attach, $attachNamePos+1, $attachNamelength);

        $hostUri    = '';
        $hostTarget = '';
        if($objEvent->external) {
            $objHost = new \Cx\Modules\Calendar\Controller\CalendarHost($objEvent->hostId);

            if(substr($objHost->uri,-1) != '/') {
                 $hostUri = $objHost->uri.'/';
            } else {
                 $hostUri = $objHost->uri;
            }

            if(substr($hostUri,0,7) != 'http://') {
                $hostUri = "http://".$hostUri;
            }

            $hostTarget = 'target="_blank"';
        }

        if($this->arrSettings['showEventsOnlyInActiveLanguage'] == 2) {
            $_LANGID = $objEvent->availableLang;
        }

        $picThumb = file_exists(\Env::get('cx')->getWebsitePath().$objEvent->pic.".thumb") ? $objEvent->pic.".thumb" : $objEvent->pic;

        $startDate = $objEvent->startDate;
        $endDate   = $objEvent->endDate;

        if ($objEvent->numSubscriber) {
            $freeSeats = \FWValidator::isEmpty($objEvent->getFreePlaces()) ? '0 ('.$_ARRAYLANG['TXT_CALENDAR_SAVE_IN_WAITLIST'].')' : $objEvent->getFreePlaces();
        } else {
            $freeSeats = $_ARRAYLANG['TXT_CALENDAR_YES'];
        }
        if (in_array($objEvent->registration, array(CalendarEvent::EVENT_REGISTRATION_NONE, CalendarEvent::EVENT_REGISTRATION_EXTERNAL))) {
            $freeSeats = $_ARRAYLANG['TXT_CALENDAR_NOT_SPECIFIED'];
        }

        $start = $objEvent->startDate;
        $objTpl->setVariable(array(
            $this->moduleLangVar.'_EVENT_ID'                => $objEvent->id,
            $this->moduleLangVar.'_EVENT_START'             => $this->format2userDateTime($startDate),
            $this->moduleLangVar.'_EVENT_START_DATE'        => $this->format2userDate($startDate),
            $this->moduleLangVar.'_EVENT_START_DATE_DAY'    => $this->formatDateTime2user($startDate, 'd'),
            $this->moduleLangVar.'_EVENT_START_DATE_MONTH'  => $this->formatDateTime2user($startDate, 'm'),
            $this->moduleLangVar.'_EVENT_START_DATE_YEAR'   => $this->formatDateTime2user($startDate, 'Y'),
            $this->moduleLangVar.'_EVENT_START_TIME'        => $this->format2userTime($startDate),
            $this->moduleLangVar.'_EVENT_START_TIME_MINUTE' => $this->formatDateTime2user($startDate, 'i'),
            $this->moduleLangVar.'_EVENT_START_TIME_HOUR'   => $this->formatDateTime2user($startDate, 'H'),
            $this->moduleLangVar.'_EVENT_START_TIMESTAMP'   => $startDate->getTimestamp(),
            $this->moduleLangVar.'_EVENT_END'               => $this->format2userDateTime($endDate),
            $this->moduleLangVar.'_EVENT_END_DATE'          => $this->format2userDate($endDate),
            $this->moduleLangVar.'_EVENT_END_DATE_DAY'      => $this->formatDateTime2user($endDate, 'd'),
            $this->moduleLangVar.'_EVENT_END_DATE_MONTH'    => $this->formatDateTime2user($endDate, 'm'),
            $this->moduleLangVar.'_EVENT_END_DATE_YEAR'     => $this->formatDateTime2user($endDate, 'Y'),
            $this->moduleLangVar.'_EVENT_END_TIME'          => $this->format2userTime($endDate),
            $this->moduleLangVar.'_EVENT_END_TIME_MINUTE'   => $this->formatDateTime2user($endDate, 'i'),
            $this->moduleLangVar.'_EVENT_END_TIME_HOUR'     => $this->formatDateTime2user($endDate, 'H'),
            $this->moduleLangVar.'_EVENT_END_TIMESTAMP'     => $endDate->getTimestamp(),
            $this->moduleLangVar.'_EVENT_TITLE'             => $objEvent->title,
            $this->moduleLangVar.'_EVENT_TEASER'            => $objEvent->teaser,
            $this->moduleLangVar.'_EVENT_ATTACHMENT'        => $objEvent->attach != '' ? '<a href="'.$hostUri.$objEvent->attach.'" target="_blank" >'.$attachName.'</a>' : '',
            $this->moduleLangVar.'_EVENT_ATTACHMENT_SOURCE' => $objEvent->attach,
            $this->moduleLangVar.'_EVENT_PICTURE'           => $objEvent->pic != '' ? '<img src="'.$hostUri.$objEvent->pic.'" alt="'.$objEvent->title.'" title="'.$objEvent->title.'" />' : '',
            $this->moduleLangVar.'_EVENT_PICTURE_SOURCE'    => $objEvent->pic,
            $this->moduleLangVar.'_EVENT_THUMBNAIL'         => $picThumb != '' ? '<img src="'.$hostUri.$picThumb.'" alt="'.$objEvent->title.'" title="'.$objEvent->title.'" />' : '',
            $this->moduleLangVar.'_EVENT_DESCRIPTION'       => $objEvent->description,
            $this->moduleLangVar.'_EVENT_SHORT_DESCRIPTION' => $parts[0].$points,
            $this->moduleLangVar.'_EVENT_LINK'              => $objEvent->link ? "<a href='".$objEvent->link."' target='_blank' >".$objEvent->link."</a>" : "",
            $this->moduleLangVar.'_EVENT_LINK_SOURCE'       => $objEvent->link,
            $this->moduleLangVar.'_EVENT_PRIORITY'          => $priority,
            $this->moduleLangVar.'_EVENT_PRIORITY_IMG'      => $priorityImg,
            $this->moduleLangVar.'_EVENT_CATEGORY'          => $objCategory->name,
            $this->moduleLangVar.'_EVENT_EXPORT_LINK'       => $hostUri.'index.php?section='.$this->moduleName.'&amp;export='.$objEvent->id,
            $this->moduleLangVar.'_EVENT_EXPORT_ICON'       => '<a href="'.$hostUri.'index.php?section='.$this->moduleName.'&amp;export='.$objEvent->id.'"><img src="modules/Calendar/View/Media/ical_export.gif" border="0" title="'.$_ARRAYLANG['TXT_CALENDAR_EXPORT_ICAL_EVENT'].'" alt="'.$_ARRAYLANG['TXT_CALENDAR_EXPORT_ICAL_EVENT'].'" /></a>',
            $this->moduleLangVar.'_EVENT_PRICE'             => $this->arrSettings['paymentCurrency'].' '.$objEvent->price,
            $this->moduleLangVar.'_EVENT_FREE_PLACES'       => $freeSeats,
            $this->moduleLangVar.'_EVENT_ACCESS'            => $_ARRAYLANG['TXT_CALENDAR_EVENT_ACCESS_'.$objEvent->access],
            $this->moduleLangVar.'_REGISTRATIONS_SUBSCRIBER'=> $objEvent->numSubscriber,
        ));

        // hide attachment template block in case no attachment is set
        if ($objTpl->blockExists('calendarAttachment')) {
            if (empty($objEvent->attach)) {
                $objTpl->hideBlock('calendarAttachment');
            } else {
                $objTpl->parse('calendarAttachment');
            }
        }

        //show date and time by user settings
        if($objTpl->blockExists('calendarDateDetail')) {

            $showStartDateDetail  = $objEvent->useCustomDateDisplay ? $objEvent->showStartDateDetail : ($this->arrSettings['showStartDateDetail'] == 1);
            $showEndDateDetail    = $objEvent->useCustomDateDisplay ? $objEvent->showEndDateDetail : ($this->arrSettings['showEndDateDetail'] == 1);
            $showStartTimeDetail  = ($objEvent->all_day) ? false : ($objEvent->useCustomDateDisplay ? $objEvent->showStartTimeDetail : ($this->arrSettings['showStartTimeDetail'] == 1));
            $showEndTimeDetail    = ($objEvent->all_day) ? false : ($objEvent->useCustomDateDisplay ? $objEvent->showEndTimeDetail : ($this->arrSettings['showEndTimeDetail'] == 1));
            $showTimeTypeDetail   = $objEvent->useCustomDateDisplay ? $objEvent->showTimeTypeDetail : 1;

            // get date for several days format > show starttime with startdate and endtime with enddate > only if several days event and all values (dates/times) are displayed
            if($this->format2userDate($startDate) != $this->format2userDate($endDate) && ($showStartDateDetail && $showEndDateDetail && $showStartTimeDetail && $showEndTimeDetail)) {
                //part 1
                $part = 1;
                $this->getMultiDateBlock($objEvent, $this->arrSettings['separatorDateTimeDetail'], $this->arrSettings['separatorSeveralDaysDetail'], ($this->arrSettings['showClockDetail'] == 1), $part);

                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_DATE_DETAIL'                => $this->date,
                    $this->moduleLangVar.'_SEP_DATE_TIME_DETAIL'       => $this->sepDateTime,
                    $this->moduleLangVar.'_TIME_DETAIL'                => $this->time,
                    'TXT_'.$this->moduleLangVar.'_CLOCK_DETAIL'        => $this->clock,
                ));

                $objTpl->parse('calendarDateDetail');

                //part 2
                $part = 2;
                $this->getMultiDateBlock($objEvent, $this->arrSettings['separatorDateTimeDetail'], $this->arrSettings['separatorSeveralDaysDetail'], ($this->arrSettings['showClockDetail'] == 1), $part);

                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_DATE_DETAIL'                => $this->date,
                    $this->moduleLangVar.'_SEP_DATE_TIME_DETAIL'       => $this->sepDateTime,
                    $this->moduleLangVar.'_TIME_DETAIL'                => $this->time,
                    'TXT_'.$this->moduleLangVar.'_CLOCK_DETAIL'        => $this->clock,
                ));
                $objTpl->parse('calendarDateDetail');
            } else {
                // get date for single day format
                $this->getSingleDateBlock($objEvent, $showStartDateDetail, $showEndDateDetail, $this->arrSettings['separatorDateDetail'], $showTimeTypeDetail, $showStartTimeDetail, $showEndTimeDetail, $this->arrSettings['separatorDateTimeDetail'], $this->arrSettings['separatorTimeDetail'], ($this->arrSettings['showClockDetail'] == 1));

                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_DATE_DETAIL'                => $this->date,
                    $this->moduleLangVar.'_SEP_DATE_TIME_DETAIL'       => $this->sepDateTime,
                    $this->moduleLangVar.'_TIME_DETAIL'                => $this->time,
                    'TXT_'.$this->moduleLangVar.'_CLOCK_DETAIL'        => $this->clock,
                ));
                $objTpl->parse('calendarDateDetail');
            }
        }

        if (
            (
                $this->arrSettings['placeData'] > 1 &&
                $objEvent->locationType == 2
            ) || (
                $this->arrSettings['placeDataHost'] > 1 &&
                $objEvent->hostType == 2
            )
        ) {
            $_ARRAYLANG = array_merge(
                $_ARRAYLANG,
                \Env::get('init')->getComponentSpecificLanguageData('MediaDir')
            );
        }

        if (($this->arrSettings['placeData'] == 1) && $objEvent->place == '' && $objEvent->place_street == '' && $objEvent->place_zip == '' && $objEvent->place_city == '' && $objEvent->place_country == '' && $objEvent->place_website == '' && $objEvent->place_phone == '') {
            $objTpl->hideBlock('calendarEventAddress');
        } else {
            if ($this->arrSettings['placeData'] > 1 && $objEvent->locationType == 2) {
                $objEvent->loadPlaceFromMediadir($objEvent->place_mediadir_id, 'place');
                list($placeLink, $placeLinkSource) = $objEvent->loadPlaceLinkFromMediadir($objEvent->place_mediadir_id, 'place');
            } else {
                $placeLink         = $objEvent->place_link != '' ? "<a href='".$objEvent->place_link."' target='_blank' >".$objEvent->place_link."</a>" : "";
                $placeLinkSource   = $objEvent->place_link;
            }
            if($objEvent->google) {
// TODO: implement with new Google Maps Embed API. see https://developers.google.com/maps/documentation/embed/guide
                /*$googleCoordinates = self::_getCoorinates($objEvent->place_street, $objEvent->place_zip, $objEvent->place_city);
                if($googleCoordinates != false) {
                    $lat = $googleCoordinates[0];
                    $lon = $googleCoordinates[1];

                    $objGoogleMap = new googleMap();
                    $objGoogleMap->setMapId($this->moduleName.'GoogleMap');
                    $objGoogleMap->setMapStyleClass('mapLarge');
                    $objGoogleMap->setMapType(0);
                    $objGoogleMap->setMapZoom(12);
                    $objGoogleMap->setMapCenter($lon, $lat);

                    $strValueClick = 'marker'.$objEvent->id.'.openInfoWindowHtml(info'.$objEvent->id.');';
                    $objGoogleMap->addMapMarker($objEvent->id, $lon, $lat, "<b>".$objEvent->place."</b><br />".$objEvent->place_street."<br />".$objEvent->place_zip." ".$objEvent->place_city."<br />".$objEvent->place_country,true, null, true, $strValueClick, null, null);

                    $googleMap = $objGoogleMap->getMap();
                } else {*/
                //}
                $lookupData = array();
                if (!empty($objEvent->place)) {
                    $lookupData[] = html_entity_decode($objEvent->place, ENT_QUOTES, CONTREXX_CHARSET);
                }
                if (!empty($objEvent->place_street)) {
                    $lookupData[] = html_entity_decode($objEvent->place_street, ENT_QUOTES, CONTREXX_CHARSET);
                }
                if (!empty($objEvent->place_zip)) {
                    $lookupData[] = html_entity_decode($objEvent->place_zip, ENT_QUOTES, CONTREXX_CHARSET);
                }
                if (!empty($objEvent->place_city)) {
                    $lookupData[] = html_entity_decode($objEvent->place_city, ENT_QUOTES, CONTREXX_CHARSET);
                }
                $lookupString = join('+', contrexx_raw2encodedUrl($lookupData, true));
                $googleMapLink = '<a href="http://maps.google.ch/maps?q=' . $lookupString . '&z=15" target="_blank">'.$_ARRAYLANG['TXT_CALENDAR_MAP'].'</a>';
            } else {
                $googleMapLink = '';
            }


            //place map
            $hasPlaceMap = !empty($objEvent->place_map) && file_exists(\Env::get('cx')->getWebsitePath().$objEvent->place_map);
            if ($hasPlaceMap) {
                $arrInfo   = getimagesize(\Env::get('cx')->getWebsitePath().$objEvent->place_map);
                $picWidth  = $arrInfo[0]+20;
                $picHeight = $arrInfo[1]+20;
            }

            $map_thumb_name = file_exists(\Env::get('cx')->getWebsitePath().$objEvent->place_map.".thumb") ? $objEvent->place_map.".thumb" : $objEvent->place_map;

            $placeWebsite      = $objEvent->place_website != '' ? "<a href='".$objEvent->place_website."' target='_blank' >".$objEvent->place_website."</a>" : "";
            $placeWebsiteSource= $objEvent->place_website;

            $objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_PLACE'           => $objEvent->place,
                $this->moduleLangVar.'_EVENT_LOCATION_PLACE'            => $objEvent->place,
                $this->moduleLangVar.'_EVENT_LOCATION_ADDRESS'=> $objEvent->place_street,
                $this->moduleLangVar.'_EVENT_LOCATION_ZIP'    => $objEvent->place_zip,
                $this->moduleLangVar.'_EVENT_LOCATION_CITY'   => $objEvent->place_city,
                $this->moduleLangVar.'_EVENT_LOCATION_COUNTRY'=> $objEvent->place_country,
                $this->moduleLangVar.'_EVENT_LOCATION_WEBSITE'          => $placeWebsite,
                $this->moduleLangVar.'_EVENT_LOCATION_WEBSITE_SOURCE'   => $placeWebsiteSource,
                $this->moduleLangVar.'_EVENT_LOCATION_LINK'          => $placeLink,
                $this->moduleLangVar.'_EVENT_LOCATION_LINK_SOURCE'   => $placeLinkSource,
                $this->moduleLangVar.'_EVENT_LOCATION_PHONE'            => $objEvent->place_phone,
                $this->moduleLangVar.'_EVENT_LOCATION_MAP_LINK'        => $hasPlaceMap ? '<a href="'.$objEvent->place_map.'" onClick="window.open(this.href,\'\',\'resizable=no,location=no,menubar=no,scrollbars=no,status=no,toolbar=no,fullscreen=no,dependent=no,width='.$picWidth.',height='.$picHeight.',status\'); return false">'.$_ARRAYLANG['TXT_CALENDAR_MAP'].'</a>' : "",
                $this->moduleLangVar.'_EVENT_LOCATION_MAP_THUMBNAIL'   => $hasPlaceMap ? '<a href="'.$objEvent->place_map.'" onClick="window.open(this.href,\'\',\'resizable=no,location=no,menubar=no,scrollbars=no,status=no,toolbar=no,fullscreen=no,dependent=no,width='.$picWidth.',height='.$picHeight.',status\'); return false"><img src="'.$map_thumb_name.'" border="0" alt="'.$objEvent->place_map.'" /></a>' : "",
                $this->moduleLangVar.'_EVENT_LOCATION_MAP_SOURCE'      => $hasPlaceMap ? $objEvent->place_map : '',
                $this->moduleLangVar.'_EVENT_LOCATION_GOOGLE_MAP_LINK' => $googleMapLink,
            ));

            if ($objTpl->blockExists('calendarEventAddressWebsite')) {
                if (empty($placeWebsite)) {
                    $objTpl->hideBlock('calendarEventAddressWebsite');
                } else {
                    $objTpl->touchBlock('calendarEventAddressWebsite');
                }
            }

            if ($objTpl->blockExists('calendarEventAddressLink')) {
                if (empty($placeLink)) {
                    $objTpl->hideBlock('calendarEventAddressLink');
                } else {
                    $objTpl->touchBlock('calendarEventAddressLink');
                }
            }

            if ($objTpl->blockExists('calendarEventAddressPhone')) {
                if (empty($objEvent->place_phone)) {
                    $objTpl->hideBlock('calendarEventAddressPhone');
                } else {
                    $objTpl->touchBlock('calendarEventAddressPhone');
                }
            }

            if ($objTpl->blockExists('calendarEventAddressMap')) {
                if ($hasPlaceMap) {
                    $objTpl->touchBlock('calendarEventAddressMap');
                } else {
                    $objTpl->hideBlock('calendarEventAddressMap');
                }
            }

            $objTpl->parse('calendarEventAddress');
        }

        $hostWebsite      = $objEvent->org_website != '' ? "<a href='".$objEvent->org_website."' target='_blank' >".$objEvent->org_website."</a>" : "";
        $hostWebsiteSource= $objEvent->org_website;

        $hostLink         = $objEvent->org_link != '' ? "<a href='".$objEvent->org_link."' target='_blank' >".$objEvent->org_link."</a>" : "";
        $hostLinkSource   = $objEvent->org_link;
        if ($this->arrSettings['placeDataHost'] > 1 && $objEvent->hostType == 2) {
            $objEvent->loadPlaceFromMediadir($objEvent->host_mediadir_id, 'host');
            list($hostLink, $hostLinkSource) = $objEvent->loadPlaceLinkFromMediadir($objEvent->host_mediadir_id, 'host');
        }
        if(($this->arrSettings['placeDataHost'] == 1) && $objEvent->org_name == '' && $objEvent->org_street == '' && $objEvent->org_zip == '' && $objEvent->org_city == '' && $objEvent->org_country == '' && $objEvent->org_website == '' && $objEvent->org_phone == '') {
            $objTpl->hideBlock('calendarEventHost');
        } else {
            $objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_HOST'         => $objEvent->org_name,
                $this->moduleLangVar.'_EVENT_HOST_ADDRESS' => $objEvent->org_street,
                $this->moduleLangVar.'_EVENT_HOST_ZIP'     => $objEvent->org_zip,
                $this->moduleLangVar.'_EVENT_HOST_CITY'    => $objEvent->org_city,
                $this->moduleLangVar.'_EVENT_HOST_COUNTRY' => $objEvent->org_country,
                $this->moduleLangVar.'_EVENT_HOST_WEBSITE'          => $hostWebsite,
                $this->moduleLangVar.'_EVENT_HOST_WEBSITE_SOURCE'   => $hostWebsiteSource,
                $this->moduleLangVar.'_EVENT_HOST_LINK'    => $hostLink,
                $this->moduleLangVar.'_EVENT_HOST_LINK_SOURCE'  => $hostLinkSource,
                $this->moduleLangVar.'_EVENT_HOST_PHONE'            => $objEvent->org_phone,
                $this->moduleLangVar.'_EVENT_HOST_EMAIL'        => $objEvent->org_email != '' ? "<a href='mailto:".$objEvent->org_email."' >".$objEvent->org_email."</a>" : "",
                $this->moduleLangVar.'_EVENT_HOST_EMAIL_SOURCE' => $objEvent->org_email,
            ));

            if ($objTpl->blockExists('calendarEventHostWebsite')) {
                if (empty($hostWebsite)) {
                    $objTpl->hideBlock('calendarEventHostWebsite');
                } else {
                    $objTpl->touchBlock('calendarEventHostWebsite');
                }
            }

            if ($objTpl->blockExists('calendarEventHostLink')) {
                if (empty($hostLink)) {
                    $objTpl->hideBlock('calendarEventHostLink');
                } else {
                    $objTpl->touchBlock('calendarEventHostLink');
                }
            }

            if ($objTpl->blockExists('calendarEventHostPhone')) {
                if (empty($objEvent->org_phone)) {
                    $objTpl->hideBlock('calendarEventHostPhone');
                } else {
                    $objTpl->touchBlock('calendarEventHostPhone');
                }
            }

            if ($objTpl->blockExists('calendarEventHostEmail')) {
                if (empty($objEvent->org_email)) {
                    $objTpl->hideBlock('calendarEventHostEmail');
                } else {
                    $objTpl->touchBlock('calendarEventHostEmail');
                }
            }

            $objTpl->parse('calendarEventHost');
        }

        $this->parseRegistrationPlaceholders($objTpl, $objEvent, $hostUri, $hostTarget, $invite);

        if ($objTpl->placeholderExists('CALENDAR_EVENT_MONTH_BOX')) {
            $objTpl->setVariable(
                'CALENDAR_EVENT_MONTH_BOX',
                $this->getDetailMonthBox($objEvent)
            );
        }
    }

    /**
     * Parse the registration related palceholders
     * $hostUri and $hostTarget should be set before calling this method
     *
     * @param \Cx\Core\Html\Sigma                           $objTpl         Template instance
     * @param \Cx\Modules\Calendar\Controller\CalendarEvent $event          Event instance
     * @param string                                        $hostUri        Host uri of the event(internal/external)
     * @param string                                        $hostTarget     Host uri target type (_blank/null)
     *
     * @return null
     */
    public function parseRegistrationPlaceholders(
        \Cx\Core\Html\Sigma $objTpl,
        CalendarEvent $event,
        $hostUri = '',
        $hostTarget = '',
        $invite = null
    ) {
        global $_ARRAYLANG;

        $numRegistrations  = contrexx_input2int($event->getRegistrationCount());
        $numDeregistration = contrexx_input2int($event->getCancellationCount());
        $objEscortManager  = new \Cx\Modules\Calendar\Controller\CalendarRegistrationManager($event, true, false);
        $objTpl->setVariable(array(
            $this->moduleLangVar.'_EVENT_COUNT_REG'          => $numRegistrations,
            $this->moduleLangVar.'_EVENT_COUNT_SIGNOFF'      => $numDeregistration,
            $this->moduleLangVar.'_EVENT_COUNT_SUBSCRIBER'   => $objEscortManager->getEscortData(),
            $this->moduleLangVar.'_REGISTRATIONS_SUBSCRIBER' => $event->numSubscriber,
        ));

        // Only link to registration form if event registration is set up and event lies in the future
        if (!$event->registration || time() > $event->startDate->getTimestamp()) {
            $objTpl->hideBlock('calendarEventRegistration');
            return;
        }

        // Only show registration form if event accepts registrations.
        // Event accepts registrations, if
        //     - no attendee limit is set
        //     - or if there are still free places available
        $registrationOpen = true;
        $regLinkTarget = '_self';
        $regLinkSrcQueryString = '';
        if ((
                // event registration is handled by external app
                // and it hasn't been marked as booked out yet
                $event->registration == CalendarEvent::EVENT_REGISTRATION_EXTERNAL &&
                !$event->registrationExternalFullyBooked
            ) || (
                // event registration is handled internally
                $event->registration == CalendarEvent::EVENT_REGISTRATION_INTERNAL && (
                    // request contains a valid invite
                    $invite ||
                    // and no invitee limit is set
                    empty($event->numSubscriber) ||
                    // or the event is not yet booked out
                    !\FWValidator::isEmpty($event->getFreePlaces())
                )
            )
        ) {
            if ($event->registration == CalendarEvent::EVENT_REGISTRATION_EXTERNAL) {
                $regLinkSrc = \FWValidator::getUrl($event->registrationExternalLink);
                $regLinkTarget = '_blank';
            } elseif ($hostUri) {
                $regLinkSrc = $hostUri. '/' .CONTREXX_DIRECTORY_INDEX.'?section='.$this->moduleName.'&amp;cmd=register&amp;id='.$event->id.'&amp;date='.$event->startDate->getTimestamp();
            } else {
                $params = array(
                    'id'    => $event->id,
                    'date'  => $event->startDate->getTimestamp(),
                );

                // id of the invite (if provided by the HTTP request)
                $inviteId = 0;

                // token of the invite (if provided by the HTTP request)
                $inviteToken = '';

                // random hash of the invite to prevent browser caching (if
                // provided by the HTTP request)
                $inviteRandom = '';

                $request = $this->cx->getRequest();

                // fetch arguments from HTTP request
                try {
                    $inviteId = $request->getParam(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_ID);
                } catch (\Exception $e) {}
                try {
                    $inviteToken = $request->getParam(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_TOKEN);
                } catch (\Exception $e) {}
                try {
                    $inviteRandom = $request->getParam(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_RANDOM);
                } catch (\Exception $e) {}

                // pass invitation info to registration form
                if (!empty($inviteId) && !empty($inviteToken)) {
                    $params[\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_ID] = $inviteId;
                    $params[\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_TOKEN] = $inviteToken;
                }
                if (!empty($inviteRandom)) {
                    $params[\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_RANDOM] = $inviteRandom;
                }

                $regLinkUrl = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'register', FRONTEND_LANG_ID, $params);
                $regLinkSrc = $regLinkUrl->toString();
                $regLinkSrcQueryString = $regLinkUrl->getSuggestedParams();
            }
            $regLink = '<a href="'.$regLinkSrc.'" '.$hostTarget.'>'.$_ARRAYLANG['TXT_CALENDAR_REGISTRATION'].'</a>';
        } else {
            $regLink          = '<i>' . $_ARRAYLANG['TXT_CALENDAR_EVENT_FULLY_BLOCKED'] . '</i>';
            $regLinkSrc       = '';
            $registrationOpen = false;
        }

        $regLinkSrc = str_replace(
            '[[SERIES_ELEMENT_STARTDATE]]',
            $event->startDate->getTimestamp(),
            $regLinkSrc
        );
        $objTpl->setVariable(array(
            $this->moduleLangVar . '_EVENT_REGISTRATION_LINK'        => $regLink,
            $this->moduleLangVar . '_EVENT_REGISTRATION_LINK_SRC'    => $regLinkSrc,
            $this->moduleLangVar . '_EVENT_REGISTRATION_LINK_SRC_QUERY_STRING' => $regLinkSrcQueryString,
            $this->moduleLangVar . '_EVENT_REGISTRATION_LINK_TARGET' => $regLinkTarget,
        ));
        if ($objTpl->blockExists('calendarEventRegistrationOpen')) {
            if ($registrationOpen) {
                $objTpl->touchBlock('calendarEventRegistrationOpen');
            } else {
                $objTpl->hideBlock('calendarEventRegistrationOpen');
            }
        }
        if ($objTpl->blockExists('calendarEventRegistrationClosed')) {
            if (!$registrationOpen) {
                $objTpl->touchBlock('calendarEventRegistrationClosed');
            } else {
                $objTpl->hideBlock('calendarEventRegistrationClosed');
            }
        }
        $objTpl->parse('calendarEventRegistration');
    }

    /**
     * Get Calendar month box for event detail view
     *
     * @param \Cx\Modules\Calendar\Controller\CalendarEvent $event
     */
    public function getDetailMonthBox(CalendarEvent $event)
    {
        global $_ARRAYLANG;

        $monthnames = explode(",", $_ARRAYLANG['TXT_CALENDAR_MONTH_ARRAY']);
        $daynames   = explode(',', $_ARRAYLANG['TXT_CALENDAR_DAY_ARRAY']);

        $year  = !empty($_GET['yearID']) ? contrexx_input2int($_GET['yearID']) : 0;
        $month = !empty($_GET['monthID']) ? contrexx_input2int($_GET['monthID']) : 0;

        $startdate = $this->getUserDateTimeFromIntern($event->startDate);
        if (empty($year) && empty($month)) {
            $year      = $startdate->format('Y');
            $month     = $startdate->format('m');
        }

        $eventList = array($event);
        // If event series is enabled refresh the eventlist
        if ($event->seriesStatus == 1) {
            try {
                $endDate = new \DateTime('1-'.$month.'-'.$year);
            } catch (\Exception $e) {
                $year = $startdate->format('Y');
                $month = $startdate->format('m');
                $endDate = new \DateTime('1-'.$month.'-'.$year);
            }
            $endDate->modify('+1 month');

            $eventManager = new static(null, $endDate);
            $objEvent     = new \Cx\Modules\Calendar\Controller\CalendarEvent(intval($event->id));
            if ($eventManager->_addToEventList($objEvent)) {
                $eventManager->eventList[] = $objEvent;
            }
            $additionalRecurrences = $objEvent->seriesData['seriesAdditionalRecurrences'];
            $eventManager->generateRecurrencesOfEvent($objEvent, $additionalRecurrences);
            $eventList = $eventManager->eventList;
        }

        $url = \Cx\Core\Core\Controller\Cx::instanciate()->getRequest()->getUrl();
        $cal = new \activeCalendar($year, $month);
        $cal->setMonthNames($monthnames);
        $cal->setDayNames($daynames);
        $cal->enableMonthNav($url->toString(false));

        $currentTime = time();
        $isNotIndependentSerieEventStarted = array();
        foreach ($eventList as $objEvent) {
            $eventDate  = $this->getUserDateTimeFromIntern($objEvent->startDate);
            $eventYear  = $eventDate->format('Y');
            $eventMonth = $eventDate->format('m');
            if ($eventYear != $year && $eventMonth != $month) {
                continue;
            }

            $eventDay                   = $eventDate->format('d');
            $isSeriesByNotIndependent   = $objEvent->seriesStatus && !$objEvent->independentSeries;
            $isEventStarted             = $currentTime >= $objEvent->startDate->getTimestamp();

            // check if non-independent-serie-event did already start
            if ($isSeriesByNotIndependent) {
                // the first date of a non-independent-serie-event does determine if
                // the event did already start
                if (!isset($isNotIndependentSerieEventStarted[$objEvent->getId()])) {
                    $isNotIndependentSerieEventStarted[$objEvent->getId()] = $isEventStarted;
                }
                // overwrite the started info from the serie data
                $isEventStarted = $isNotIndependentSerieEventStarted[$objEvent->getId()];
            }

            $freePlaces = (   $isEventStarted
                           || (in_array($objEvent->registration, array(CalendarEvent::EVENT_REGISTRATION_NONE, CalendarEvent::EVENT_REGISTRATION_EXTERNAL)))
                           || ($objEvent->registration == CalendarEvent::EVENT_REGISTRATION_INTERNAL && empty($objEvent->numSubscriber))
                           || $isSeriesByNotIndependent
                          ) ? 0 : $objEvent->getFreePlaces();
            $eventClass = ' event_full';
            $eventurl = false;

            if (   !$isEventStarted
                && (   $objEvent->registration == CalendarEvent::EVENT_REGISTRATION_NONE
                    || ($objEvent->registration == CalendarEvent::EVENT_REGISTRATION_EXTERNAL && !$objEvent->registrationExternalFullyBooked)
                    || (   $objEvent->registration == CalendarEvent::EVENT_REGISTRATION_INTERNAL
                        && (empty($objEvent->numSubscriber) || !\FWValidator::isEmpty($objEvent->getFreePlaces()))
                    )
                )
            ) {
                $eventClass = ' event_open';
                $eventurl   = $this->_getDetailLink($objEvent);
            }

            $seatsLeft = empty($freePlaces) ? '&nbsp;' : $freePlaces . ' ' . $_ARRAYLANG['TXT_CALENDAR_EVENT_FREE'];
            $cal->setEvent($eventYear, $eventMonth, $eventDay, $eventClass, $eventurl);
            $cal->setEventContent($eventYear, $eventMonth, $eventDay, $seatsLeft, $eventurl, 'free_places');
        }
        return $cal->showMonth(false, true);
    }

    /**
     * Sets the placeholders used for the event list view
     *
     * @param object  $objTpl Template object
     * @param integer $type   Event type
     *
     * @return null
     */
    function showEventList($objTpl, $type='', &$firstEndDate = null) {
        global $objInit, $_ARRAYLANG, $_LANGID;

        $this->getFrontendLanguages();

        if (
            $this->arrSettings['placeData'] > 1 ||
            $this->arrSettings['placeDataHost'] > 1
        ) {
            $_ARRAYLANG = array_merge(
                $_ARRAYLANG,
                \Env::get('init')->getComponentSpecificLanguageData('MediaDir')
            );
        }

        $i=0;
        foreach ($this->eventList as $objEvent) {
            $category_names = CalendarCategory::getNamesByEvent($objEvent);
            $showIn = explode(",",$objEvent->showIn);
            $languages = '';
            if (count(\FWLanguage::getActiveFrontendLanguages()) > 1) {
                $langState = array();
                foreach ($this->arrFrontendLanguages as $langKey => $arrLang) {
                    if (in_array($arrLang['id'], $showIn)) {
                        $langState[$langKey] = 'active';
                    }
                }
                $languages = \Html::getLanguageIcons($langState, 'index.php?cmd=Calendar&amp;act=modify_event&amp;id=' . $objEvent->id . '&amp;langId=%1$d'.($type == 'confirm' ? "&amp;confirm=1" : ""));

                if($type == 'confirm' && $objTpl->blockExists('txt_languages_block_confirm_list')) {
                    $objTpl->touchBlock('txt_languages_block_confirm_list');
                } elseif ($objTpl->blockExists('txt_languages_block')) {
                    $objTpl->touchBlock('txt_languages_block');
                }
            } else {
                if($type == 'confirm' && $objTpl->blockExists('txt_languages_block_confirm_list')) {
                    $objTpl->hideBlock('txt_languages_block_confirm_list');
                } elseif ($objTpl->blockExists('txt_languages_block')) {
                    $objTpl->hideBlock('txt_languages_block');
                }
            }

            list ($priority, $priorityImg) = $this->getPriorityImage($objEvent);

            $plainDescription = contrexx_html2plaintext($objEvent->description);
            if (strlen($plainDescription) > 100) {
                $points = '...';
            } else {
                $points = '';
            }
            $parts= explode("\n", wordwrap($plainDescription, 100, "\n"));

            $attachNamePos    = strrpos($objEvent->attach, '/');
            $attachNamelength = strlen($objEvent->attach);
            $attachName       = substr($objEvent->attach, $attachNamePos+1, $attachNamelength);

            $hostUri    = '';
            $hostTarget = '';
            if($objEvent->external) {
                $objHost = new \Cx\Modules\Calendar\Controller\CalendarHost($objEvent->hostId);

                if(substr($objHost->uri,-1) != '/') {
                     $hostUri = $objHost->uri.'/';
                } else {
                     $hostUri = $objHost->uri;
                }

                if(substr($hostUri,0,7) != 'http://') {
                    $hostUri = "http://".$hostUri;
                }
                $hostTarget = 'target="_blank"';
            }
            $copyLink = '';
            if($objInit->mode == 'backend') {
                $editLink = 'index.php?cmd='.$this->moduleName.'&amp;act=modify_event&id='.$objEvent->id.($type == 'confirm' ? "&amp;confirm=1" : "");
                $copyLink = $editLink."&amp;copy=1";
            } else {
                $editLink = CONTREXX_DIRECTORY_INDEX.'?section='.$this->moduleName.'&amp;cmd=edit&id='.$objEvent->id;
            }
            $picThumb = file_exists(\Env::get('cx')->getWebsitePath()."{$objEvent->pic}.thumb") ? "{$objEvent->pic}.thumb" : ($objEvent->pic != '' ? $objEvent->pic : '');

            $placeWebsite      = $objEvent->place_website != '' ? "<a href='".$objEvent->place_website."' target='_blank' >".$objEvent->place_website."</a>" : "";
            $placeWebsiteSource= $objEvent->place_website;

            $placeLink         = $objEvent->place_link != '' ? "<a href='".$objEvent->place_link."' target='_blank' >".$objEvent->place_link."</a>" : "";
            $placeLinkSource   = $objEvent->place_link;
            if ($this->arrSettings['placeData'] > 1 && $objEvent->locationType == 2) {
                $objEvent->loadPlaceFromMediadir($objEvent->place_mediadir_id, 'place');
                list($placeLink, $placeLinkSource) = $objEvent->loadPlaceLinkFromMediadir($objEvent->place_mediadir_id, 'place');
            }

            $hostWebsite      = $objEvent->org_website != '' ? "<a href='".$objEvent->org_website."' target='_blank' >".$objEvent->org_website."</a>" : "";
            $hostWebsiteSource= $objEvent->org_website;

            $hostLink         = $objEvent->org_link != '' ? "<a href='".$objEvent->org_link."' target='_blank' >".$objEvent->org_link."</a>" : "";
            $hostLinkSource   = $objEvent->org_link;
            if ($this->arrSettings['placeDataHost'] > 1 && $objEvent->hostType == 2) {
                $objEvent->loadPlaceFromMediadir($objEvent->host_mediadir_id, 'host');
                list($hostLink, $hostLinkSource) = $objEvent->loadPlaceLinkFromMediadir($objEvent->host_mediadir_id, 'host');
            }

            $startDate = $objEvent->startDate;
            $endDate   = $objEvent->endDate;
            if (!$firstEndDate || $endDate < $firstEndDate) {
                $firstEndDate = $endDate;
            }

            if ($objEvent->numSubscriber) {
                $freeSeats = \FWValidator::isEmpty($objEvent->getFreePlaces()) ? '0 ('.$_ARRAYLANG['TXT_CALENDAR_SAVE_IN_WAITLIST'].')' : $objEvent->getFreePlaces();
            } else {
                $freeSeats = $_ARRAYLANG['TXT_CALENDAR_YES'];
            }
            if (in_array($objEvent->registration, array(CalendarEvent::EVENT_REGISTRATION_NONE, CalendarEvent::EVENT_REGISTRATION_EXTERNAL))) {
                $freeSeats = $_ARRAYLANG['TXT_CALENDAR_NOT_SPECIFIED'];
            }

            $objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_ROW'            => $i%2==0 ? 'row1' : 'row2',
                $this->moduleLangVar.'_EVENT_LED'            => $objEvent->status==0 ? 'red' : 'green',
                $this->moduleLangVar.'_EVENT_STATUS'         => $objEvent->status==0 ? $_ARRAYLANG['TXT_CALENDAR_INACTIVE'] : $_ARRAYLANG['TXT_CALENDAR_ACTIVE'],
                $this->moduleLangVar.'_EVENT_ID'             => $objEvent->id,
                $this->moduleLangVar.'_EVENT_TITLE'          => $objEvent->title,
                $this->moduleLangVar.'_EVENT_TEASER'         => $objEvent->teaser,
                $this->moduleLangVar.'_EVENT_PICTURE'        => $objEvent->pic != '' ? '<img src="'.$objEvent->pic.'" alt="'.$objEvent->title.'" title="'.$objEvent->title.'" />' : '',
                $this->moduleLangVar.'_EVENT_PICTURE_SOURCE' => $objEvent->pic,
                $this->moduleLangVar.'_EVENT_THUMBNAIL'      => $objEvent->pic != '' ? '<img src="'.$picThumb.'" alt="'.$objEvent->title.'" title="'.$objEvent->title.'" />' : '',
                $this->moduleLangVar.'_EVENT_PRIORITY'       => $priority,
                $this->moduleLangVar.'_EVENT_PRIORITY_IMG'   => $priorityImg,
                $this->moduleLangVar.'_EVENT_PLACE'          => $objEvent->place,
                $this->moduleLangVar.'_EVENT_DESCRIPTION'    => $objEvent->description,
                $this->moduleLangVar.'_EVENT_SHORT_DESCRIPTION' => $parts[0].$points,
                $this->moduleLangVar.'_EVENT_LINK'           => $objEvent->link ? "<a href='".$objEvent->link."' target='_blank' >".$objEvent->link."</a>" : "",
                $this->moduleLangVar.'_EVENT_LINK_SOURCE'    => $objEvent->link,
                $this->moduleLangVar.'_EVENT_ATTACHMENT'     => $objEvent->attach != '' ? '<a href="'.$hostUri.$objEvent->attach.'" target="_blank" >'.$attachName.'</a>' : '',
                $this->moduleLangVar.'_EVENT_ATTACHMENT_SOURCE' => $objEvent->attach,
                $this->moduleLangVar.'_EVENT_START'          => $this->format2userDateTime($startDate),
                $this->moduleLangVar.'_EVENT_START_DATE'     => $this->format2userDate($startDate),
                $this->moduleLangVar.'_EVENT_START_DATE_DAY' => $this->formatDateTime2user($startDate, 'd'),
                $this->moduleLangVar.'_EVENT_START_DATE_MONTH'=> $this->formatDateTime2user($startDate, 'm'),
                $this->moduleLangVar.'_EVENT_START_DATE_YEAR'=> $this->formatDateTime2user($startDate, 'Y'),
                $this->moduleLangVar.'_EVENT_START_TIME'     => $this->format2userTime($startDate),
                $this->moduleLangVar.'_EVENT_START_TIME_MINUTE'=> $this->formatDateTime2user($startDate, 'i'),
                $this->moduleLangVar.'_EVENT_START_TIME_HOUR'=> $this->formatDateTime2user($startDate, 'H'),
                $this->moduleLangVar.'_EVENT_START_TIMESTAMP'=> $startDate->getTimestamp(),
                $this->moduleLangVar.'_EVENT_DATE'           => $this->format2userDate($startDate),
                $this->moduleLangVar.'_EVENT_END'            => $this->format2userDateTime($endDate),
                $this->moduleLangVar.'_EVENT_END_DATE'       => $this->format2userDate($endDate),
                $this->moduleLangVar.'_EVENT_END_DATE_DAY'   => $this->formatDateTime2user($endDate, 'd'),
                $this->moduleLangVar.'_EVENT_END_DATE_MONTH' => $this->formatDateTime2user($endDate, 'm'),
                $this->moduleLangVar.'_EVENT_END_DATE_YEAR'  => $this->formatDateTime2user($endDate, 'Y'),
                $this->moduleLangVar.'_EVENT_END_TIME'       => $this->format2userTime($endDate),
                $this->moduleLangVar.'_EVENT_END_TIME_MINUTE'=> $this->formatDateTime2user($endDate, 'i'),
                $this->moduleLangVar.'_EVENT_END_TIME_HOUR'  => $this->formatDateTime2user($endDate, 'H'),
                $this->moduleLangVar.'_EVENT_END_TIMESTAMP'  => $endDate->getTimestamp(),
                $this->moduleLangVar.'_EVENT_LANGUAGES'      => $languages,
                $this->moduleLangVar.'_EVENT_CATEGORY'       =>
                    implode(', ', $category_names),
                $this->moduleLangVar.'_EVENT_EXPORT_LINK'    => $hostUri.'index.php?section='.$this->moduleName.'&amp;export='.$objEvent->id,
                $this->moduleLangVar.'_EVENT_EXPORT_ICON'    => '<a href="'.$hostUri.'index.php?section='.$this->moduleName.'&amp;export='.$objEvent->id.'"><img src="modules/Calendar/View/Media/ical_export.gif" border="0" title="'.$_ARRAYLANG['TXT_CALENDAR_EXPORT_ICAL_EVENT'].'" alt="'.$_ARRAYLANG['TXT_CALENDAR_EXPORT_ICAL_EVENT'].'" /></a>',
                $this->moduleLangVar.'_EVENT_EDIT_LINK'      => $editLink,
                $this->moduleLangVar.'_EVENT_COPY_LINK'      => $copyLink,
                $this->moduleLangVar.'_EVENT_SERIES'         => $objEvent->seriesStatus == 1 ? '<img src="'.ASCMS_MODULE_WEB_PATH.'/'.$this->moduleName.'/View/Media/Repeat.png" border="0"/>' : '<i>'.$_ARRAYLANG['TXT_CALENDAR_NO_SERIES'].'</i>',
                $this->moduleLangVar.'_EVENT_FREE_PLACES'    => $freeSeats,
                $this->moduleLangVar.'_EVENT_ACCESS'         => $_ARRAYLANG['TXT_CALENDAR_EVENT_ACCESS_'.$objEvent->access],
                'TXT_' . $this->moduleLangVar . '_MORE_INFO' => $_ARRAYLANG['TXT_CALENDAR_MORE_INFO'],
            ));

            // hide attachment template block in case no attachment is set
            if ($objTpl->blockExists('calendarAttachment')) {
                if (empty($objEvent->attach)) {
                    $objTpl->hideBlock('calendarAttachment');
                } else {
                    $objTpl->parse('calendarAttachment');
                }
            }

            if ($objEvent->showDetailView) {
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_EVENT_DETAIL_LINK'    => $objEvent->type==0 ? self::_getDetailLink($objEvent) : $objEvent->arrData['redirect'][$_LANGID],
                    $this->moduleLangVar.'_EVENT_DETAIL_TARGET'  => $objEvent->type==0 ? '_self' : '_blank',
                ));
                if ($objTpl->blockExists('event_detail_view')) {
                    $objTpl->touchBlock('event_detail_view');
                }
                if ($objTpl->blockExists('event_no_detail_view')) {
                    $objTpl->hideBlock('event_no_detail_view');
                }
            } else {
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_EVENT_DETAIL_LINK'    => '#',
                    $this->moduleLangVar.'_EVENT_DETAIL_TARGET'  => '',
                ));
                if ($objTpl->blockExists('event_detail_view')) {
                    $objTpl->hideBlock('event_detail_view');
                }
                if ($objTpl->blockExists('event_no_detail_view')) {
                    $objTpl->touchBlock('event_no_detail_view');
                }
            }

            $hasPlaceMap = !empty($objEvent->place_map) && file_exists(\Env::get('cx')->getWebsitePath().$objEvent->place_map);
            if ($hasPlaceMap) {
                $arrInfo   = getimagesize(\Env::get('cx')->getWebsitePath().$objEvent->place_map);
                $picWidth  = $arrInfo[0]+20;
                $picHeight = $arrInfo[1]+20;
            }

            $map_thumb_name = file_exists(\Env::get('cx')->getWebsitePath().$objEvent->place_map.".thumb") ? $objEvent->place_map.".thumb" : $objEvent->place_map;
            $objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_LOCATION_PLACE'         => $objEvent->place,
                $this->moduleLangVar.'_EVENT_LOCATION_ADDRESS'       => $objEvent->place_street,
                $this->moduleLangVar.'_EVENT_LOCATION_ZIP'           => $objEvent->place_zip,
                $this->moduleLangVar.'_EVENT_LOCATION_CITY'          => $objEvent->place_city,
                $this->moduleLangVar.'_EVENT_LOCATION_COUNTRY'       => $objEvent->place_country,
                $this->moduleLangVar.'_EVENT_LOCATION_WEBSITE'       => $placeWebsite,
                $this->moduleLangVar.'_EVENT_LOCATION_WEBSITE_SOURCE'=> $placeWebsiteSource,
                $this->moduleLangVar.'_EVENT_LOCATION_LINK'          => $placeLink,
                $this->moduleLangVar.'_EVENT_LOCATION_LINK_SOURCE'   => $placeLinkSource,
                $this->moduleLangVar.'_EVENT_LOCATION_PHONE'         => $objEvent->place_phone,
                $this->moduleLangVar.'_EVENT_LOCATION_MAP_LINK'      => $hasPlaceMap ? '<a href="'.$objEvent->place_map.'" onClick="window.open(this.href,\'\',\'resizable=no,location=no,menubar=no,scrollbars=no,status=no,toolbar=no,fullscreen=no,dependent=no,width='.$picWidth.',height='.$picHeight.',status\'); return false">'.$_ARRAYLANG['TXT_CALENDAR_MAP'].'</a>' : "",
                $this->moduleLangVar.'_EVENT_LOCATION_MAP_THUMBNAIL' => $hasPlaceMap ? '<a href="'.$objEvent->place_map.'" onClick="window.open(this.href,\'\',\'resizable=no,location=no,menubar=no,scrollbars=no,status=no,toolbar=no,fullscreen=no,dependent=no,width='.$picWidth.',height='.$picHeight.',status\'); return false"><img src="'.$map_thumb_name.'" border="0" alt="'.$objEvent->place_map.'" /></a>' : "",
                $this->moduleLangVar.'_EVENT_LOCATION_MAP_SOURCE'    => $hasPlaceMap ? $objEvent->place_map : '',

                $this->moduleLangVar.'_EVENT_HOST'              => $objEvent->org_name,
                $this->moduleLangVar.'_EVENT_HOST_ADDRESS'      => $objEvent->org_street,
                $this->moduleLangVar.'_EVENT_HOST_ZIP'          => $objEvent->org_zip,
                $this->moduleLangVar.'_EVENT_HOST_CITY'         => $objEvent->org_city,
                $this->moduleLangVar.'_EVENT_HOST_COUNTRY'      => $objEvent->org_country,
                $this->moduleLangVar.'_EVENT_HOST_WEBSITE'      => $hostWebsite,
                $this->moduleLangVar.'_EVENT_HOST_WEBSITE_SOURCE'=> $hostWebsiteSource,
                $this->moduleLangVar.'_EVENT_HOST_LINK'         => $hostLink,
                $this->moduleLangVar.'_EVENT_HOST_LINK_SOURCE'  => $hostLinkSource,
                $this->moduleLangVar.'_EVENT_HOST_PHONE'        => $objEvent->org_phone,
                $this->moduleLangVar.'_EVENT_HOST_EMAIL'        => $objEvent->org_email != '' ? "<a href='mailto:".$objEvent->org_email."' >".$objEvent->org_email."</a>" : "",
                $this->moduleLangVar.'_EVENT_HOST_EMAIL_SOURCE' => $objEvent->org_email,
            ));

            if ($objTpl->blockExists('event_location_website')) {
                if (empty($placeWebsite)) {
                    $objTpl->hideBlock('event_location_website');
                } else {
                    $objTpl->touchBlock('event_location_website');
                }
            }

            if ($objTpl->blockExists('event_location_link')) {
                if (empty($placeLink)) {
                    $objTpl->hideBlock('event_location_link');
                } else {
                    $objTpl->touchBlock('event_location_link');
                }
            }

            if ($objTpl->blockExists('event_location_phone')) {
                if (empty($objEvent->place_phone)) {
                    $objTpl->hideBlock('event_location_phone');
                } else {
                    $objTpl->touchBlock('event_location_phone');
                }
            }

            if ($objTpl->blockExists('event_location_map')) {
                if ($hasPlaceMap) {
                    $objTpl->touchBlock('event_location_map');
                } else {
                    $objTpl->hideBlock('event_location_map');
                }
            }

            if ($objTpl->blockExists('event_host_website')) {
                if (empty($hostWebsite)) {
                    $objTpl->hideBlock('event_host_website');
                } else {
                    $objTpl->touchBlock('event_host_website');
                }
            }

            if ($objTpl->blockExists('event_host_link')) {
                if (empty($hostLink)) {
                    $objTpl->hideBlock('event_host_link');
                } else {
                    $objTpl->touchBlock('event_host_link');
                }
            }

            if ($objTpl->blockExists('event_host_phone')) {
                if (empty($objEvent->org_phone)) {
                    $objTpl->hideBlock('event_host_phone');
                } else {
                    $objTpl->touchBlock('event_host_phone');
                }
            }

            if ($objTpl->blockExists('event_host_email')) {
                if (empty($objEvent->org_email)) {
                    $objTpl->hideBlock('event_host_email');
                } else {
                    $objTpl->touchBlock('event_host_email');
                }
            }

            if($objInit->mode == 'backend') {
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_EVENT_COUNT_REG'      => $objEvent->getRegistrationCount(),
                    $this->moduleLangVar.'_EVENT_COUNT_DEREG'    => $objEvent->getCancellationCount(),
                    $this->moduleLangVar.'_EVENT_COUNT_WAITLIST' => $objEvent->getWaitlistCount(),
                ));
            }

            $i++;

            // show date block
            if($objTpl->blockExists('calendarDateList')) {

                $showStartDateList  = $objEvent->useCustomDateDisplay ? $objEvent->showStartDateList : ($this->arrSettings['showStartDateList'] == 1);
                $showEndDateList    = $objEvent->useCustomDateDisplay ? $objEvent->showEndDateList : ($this->arrSettings['showEndDateList'] == 1);
                $showStartTimeList  = ($objEvent->all_day) ? false : ($objEvent->useCustomDateDisplay ? $objEvent->showStartTimeList : ($this->arrSettings['showStartTimeList'] == 1));
                $showEndTimeList    = ($objEvent->all_day) ? false : ($objEvent->useCustomDateDisplay ? $objEvent->showEndTimeList : ($this->arrSettings['showEndTimeList'] == 1));
                $showTimeTypeList   = $objEvent->useCustomDateDisplay ? $objEvent->showTimeTypeList : 1;

                // get date for several days format > show starttime with startdate and endtime with enddate > only if several days event and all values (dates/times) are displayed
                if ($this->format2userDate($startDate) != $this->format2userDate($endDate) && ($showStartDateList && $showEndDateList && $showStartTimeList && $showEndTimeList)) {

                    //part 1
                    $part = 1;
                    $this->getMultiDateBlock($objEvent, $this->arrSettings['separatorDateTimeList'], $this->arrSettings['separatorSeveralDaysList'], ($this->arrSettings['showClockList'] == 1), $part);

                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_DATE_LIST'                => $this->date,
                        $this->moduleLangVar.'_SEP_DATE_TIME_LIST'       => $this->sepDateTime,
                        $this->moduleLangVar.'_TIME_LIST'                => $this->time,
                        'TXT_'.$this->moduleLangVar.'_CLOCK_LIST'        => $this->clock,
                    ));
                    $objTpl->parse('calendarDateList');
                    //part 2
                    $part = 2;
                    $this->getMultiDateBlock($objEvent, $this->arrSettings['separatorDateTimeList'], $this->arrSettings['separatorSeveralDaysList'], ($this->arrSettings['showClockList'] == 1), $part);

                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_DATE_LIST'                => $this->date,
                        $this->moduleLangVar.'_SEP_DATE_TIME_LIST'       => $this->sepDateTime,
                        $this->moduleLangVar.'_TIME_LIST'                => $this->time,
                        'TXT_'.$this->moduleLangVar.'_CLOCK_LIST'        => $this->clock,
                    ));
                    $objTpl->parse('calendarDateList');
                } else {
                    // get date for single day format
                   $this->getSingleDateBlock($objEvent, $showStartDateList, $showEndDateList, $this->arrSettings['separatorDateList'], $showTimeTypeList, $showStartTimeList, $showEndTimeList, $this->arrSettings['separatorDateTimeList'], $this->arrSettings['separatorTimeList'], ($this->arrSettings['showClockList'] == 1));

                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_DATE_LIST'                => $this->date,
                        $this->moduleLangVar.'_SEP_DATE_TIME_LIST'       => $this->sepDateTime,
                        $this->moduleLangVar.'_TIME_LIST'                => $this->time,
                        'TXT_'.$this->moduleLangVar.'_CLOCK_LIST'        => $this->clock,
                    ));
                    $objTpl->parse('calendarDateList');
                }
            }

            if ($objInit->mode == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
                $this->parseRegistrationPlaceholders($objTpl, $objEvent, $hostUri, $hostTarget);
            }
            if($type == 'confirm') {
                if($objTpl->blockExists('eventConfirmList')) {
                    $objTpl->parse('eventConfirmList');
                }
            } else {
                if($objTpl->blockExists('eventList')) {
                    $objTpl->parse('eventList');
                }

                if($objTpl->blockExists('calendar_headlines_row')) {
                    $objTpl->parse('calendar_headlines_row');
                }
            }

        }
        if(count($this->eventList) == 0 && $type != 'confirm') {
            $objTpl->hideBlock('eventList');

            $objTpl->setVariable(array(
                'TXT_'.$this->moduleLangVar.'_NO_EVENTS'        => $_ARRAYLANG['TXT_CALENDAR_EVENTS_NO'],
            ));

            $objTpl->parse('emptyEventList');
        }
    }

    /**
     * Returns the events with date
     *
     * @return array Events list
     */
    function getEventsWithDate() {
        $arrEvents = array();
        foreach ($this->eventList as $objEvent) {
            $eventDate = $this->getUserDateTimeFromIntern($objEvent->startDate);
            $arrEvents[] = array(
                'year'  => $eventDate->format('Y'),
                'month' => $eventDate->format('m'),
                'day'   => $eventDate->format('d')
            );
        }
        return $arrEvents;
    }

    /**
     * Returns the Event detail page link
     *
     * @param object $objEvent Event object
     *
     * @return string link for the detail page
     */
    function _getDetailLink($objEvent)
    {
        $url = \Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, 'detail');
        $url->setParams(array(
            'id' => $objEvent->id,
            'date' => $objEvent->startDate->getTimestamp()
        ));

        if($objEvent->external) {
            $url->setParam('external', 1);
        }
        return (string)$url;
    }

    /**
     * Find the url exists or not
     *
     * @param string $url url
     *
     * @return boolean true on url exists, false otherwise
     */
    function urlfind($url){
        if (!ini_get('allow_url_fopen')) {
            ini_set('allow_url_fopen', 'On');
        }

        if (ini_get('allow_url_fopen')) {
            if($url) {
                $file = @fopen ($url.'/modules/Calendar/Controller/CalendarWebserviceServer.class.php', "r");
            }

            if($file){
                fclose($file);
                return true;
            } else {
                return false;
            }
        } else {
            try {
                $request  = new \HTTP_Request2($url.'modules/Calendar/Controller/CalendarWebserviceServer.class.php');
                $response = $request->send();
                if (404 == $response->getStatus()) {
                    return false;
                } else {
                    return true;
                }
            } catch (Exception $e) {
                \DBG::msg($e->getMessage());
                return false;
            }
        }
    }

    /**
     * Generate the next recurrence of a series event.
     *
     * @param   CalendarEvent   $objEvent   Event instance to check for
     *                                      recurrences for.
     * @param array   $additionalRecurrences   Array of additional (manually
     *                                         set) recurrence dates as
     *                                         DateTime objects.
     */
    public function generateRecurrencesOfEvent(
        $objEvent,
        $additionalRecurrences = array()
    ) {
        $this->getSettings();

        // create a copy of the event instance which can be used
        // to generate the recurrences on and iterate over them
        $recurrenceEvent = clone $objEvent;

        // fetch and iterate over the recurrence dates
        while (
            $nextEvent = $this->getNextRecurrenceDate(
                $objEvent,
                $recurrenceEvent,
                $additionalRecurrences
            )
        ) {
            // skip recurrence if it has been manually excluded
            if (
                $this->isDateExists(
                    $nextEvent->startDate,
                    $objEvent->seriesData['seriesPatternExceptions']
                )
            ) {
                continue;
            }

            // verify that the recurrence is within the current date boundary
            if (!$this->_addToEventList($nextEvent)) {
                continue;
            }

            // add recurrence to the date list
            array_push($this->eventList, $nextEvent);

            // in case each event shall only get listed once (used
            // by backend view), do abortfurther processing of recurrences
            // of this event
            if ($this->listType == 'upcoming') {
                break;
            }
        }
    }


    /**
     * Calculate the next recurrence date
     *
     * The event $objCloneEvent is advanced to the next recurrence date based
     * on the set series options of $objEvent.
     *
     * @param   CalendarEvent   $objEvent   Original event of which the
     *                                      recurrence is calculated on.
     * @param   CalendarEvent   $objCloneEvent  Event on which the next
     *                                          recurrence shall be calculated
     *                                          on.
     * @param   array   $additionalRecurrences  Array of additional (manually
     *                                          added) recurrences as DateTime
     *                                          objects.
     */
    protected function getNextRecurrenceDate($objEvent, $objCloneEvent, &$additionalRecurrences = array()) {
        while ($nextEvent = $this->fetchNextRecurrence($objEvent, $objCloneEvent, $additionalRecurrences)) {
            switch ($objCloneEvent->seriesData['seriesPatternDouranceType']) {
                // no recurrence end set
                case 1:
                    if ($this->startDate != null) {
                        $lastDate = clone $this->startDate;
                        $lastDate->setDate($lastDate->format('Y') + intval($this->arrSettings['maxSeriesEndsYear']) + 1, $lastDate->format('m'), $lastDate->format('d'));
                        if ($nextEvent->startDate > $lastDate) {
                            // recurrence is out of date boundary -> skip
                            return null;
                        }
                    } elseif ($this->endDate != null) { // start date will be null only on archive
                        if ($nextEvent->endDate > $this->endDate) {
                            // recurrence is out of date boundary -> skip
                            return null;
                        }
                    } else {
                        // recurrence is out of date boundary -> skip
                        return null;
                    }
                    break;

                // recurrence shall end after a certain amount of recurrences
                case 2:
                    $objCloneEvent->seriesData['seriesPatternEnd'] = $objCloneEvent->seriesData['seriesPatternEnd']-1;
                    if ($objCloneEvent->seriesData['seriesPatternEnd'] <= 0) {
                        // recurrence is out of date boundary -> skip
                        return null;
                    }
                    break;

                // recurrence shall end after a specific date
                case 3:
                    if ($nextEvent->startDate > $objCloneEvent->seriesData['seriesPatternEndDate']) {
                        // recurrence is out of date boundary -> skip
                        return null;
                    }
                    break;
            }

            return $nextEvent;
        }

        return null;
    }

    /**
     * Fetch the next recurrence date
     *
     * The event $recurrenceEvent is advanced to the next recurrence date based
     * on the set series options of $objEvent. Additionally the array
     * $additionalRecurrences is checked for any manually added recurrences
     * that will occur before the next regular recurrence. If there are any,
     * then $recurrenceEvent will not be advanced to the next recurrence date.
     *
     * @param   CalendarEvent   $objEvent   Original event of which the
     *                                      recurrence is calculated on.
     * @param   CalendarEvent   $recurrenceEvent  Event on which the next
     *                                          recurrence shall be calculated
     *                                          on.
     * @param   array   $additionalRecurrences  Array of additional (manually
     *                                          added) recurrences as DateTime
     *                                          objects. If an additional
     *                                          recurrence is returned, then
     *                                          the DateTime object of that
     *                                          particular recurrence is being
     *                                          removed from the array
     *                                          $additionalRecurrences.
     * @return  CalendarEvent   The next calculated recurrence event. This is
     *                          either a manually added recurrence or the next
     *                          regular recurrence based on the series options.
     */
    protected function fetchNextRecurrence(
        $objEvent,
        $recurrenceEvent,
        &$additionalRecurrences = array()
    ) {
        // create local copy of the recurrence event to be used for the
        // calculation of the next recurrence
        $objCloneEvent = clone $recurrenceEvent;

        // calculate next regular recurrence (based on set series pattern)
        switch ($objCloneEvent->seriesData['seriesType']) {
            case 1:
                //daily
                if ($objCloneEvent->seriesData['seriesPatternType'] == 1) {
                    $modifyString = '+' . intval($objEvent->seriesData['seriesPatternDay']) . ' days';
                } else {
                    $modifyString = '+1 Weekday';
                }

                $objCloneEvent->startDate->modify($modifyString);
                $objCloneEvent->startDate->setTime(
                    $objEvent->startDate->format('H'),
                    $objEvent->startDate->format('i'),
                    $objEvent->startDate->format('s')
                );

                $objCloneEvent->endDate->modify($modifyString);
                $objCloneEvent->endDate->setTime(
                    $objEvent->endDate->format('H'),
                    $objEvent->endDate->format('i'),
                    $objEvent->endDate->format('s')
                );
                break;

            case 2:
                //weekly
                $weekdays       = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                $oldWeekday     = $objCloneEvent->startDate->format('w');
                $oldWeekNum     = $objCloneEvent->startDate->format('W');
                $weekdayPattern = $objCloneEvent->seriesData['seriesPatternWeekday'];

                $nxtWeekDay = null;
                if (($pos = strpos($weekdayPattern, '1', $oldWeekday)) !== false) {
                    $nxtWeekDay = $pos;
                } elseif (($pos = strpos($weekdayPattern, '1', 0)) !== false) {
                    $nxtWeekDay = $pos;
                } else {
                    // invalid pattern stored -> abort
                    return null;
                }
                if ($nxtWeekDay !== null) {
                    $objCloneEvent->startDate->modify('next '. $weekdays[$nxtWeekDay]);
                }
                $newWeekNum = $objCloneEvent->startDate->format('W');
                if ($objEvent->seriesData['seriesPatternWeek'] > 1 && ($oldWeekNum < $newWeekNum)) {
                    $objCloneEvent->startDate->modify('+'. ($objEvent->seriesData['seriesPatternWeek'] - 1) .' weeks');
                }
                $objCloneEvent->startDate->setTime(
                    $objEvent->startDate->format('H'),
                    $objEvent->startDate->format('i'),
                    $objEvent->startDate->format('s')
                );

                $addDays = $objCloneEvent->startDate->diff($objEvent->startDate)->days;
                $objCloneEvent->endDate->modify('+'. $addDays .' days');
                $objCloneEvent->endDate->setTime(
                    $objEvent->endDate->format('H'),
                    $objEvent->endDate->format('i'),
                    $objEvent->endDate->format('s')
                );
                break;

            case 3:
                //monthly
                if ($objCloneEvent->seriesData['seriesPatternType'] == 1) {

                    $patternDay = intval($objEvent->seriesData['seriesPatternDay']);
                    $addMonths  = intval($objEvent->seriesData['seriesPatternMonth']);

                    $objCloneEvent->startDate->modify('+'. $addMonths .' months');

                    // if the recurrence day is beyond the number of days the current
                    // month has, then we have to fast-forward to the next month
                    while ($patternDay > $objCloneEvent->startDate->format('t')) {
                        $objCloneEvent->startDate->modify('+'. $addMonths .' months');
                    }

                    $objCloneEvent->startDate->setDate(
                        $objCloneEvent->startDate->format('Y'),
                        $objCloneEvent->startDate->format('m'),
                        $patternDay
                    );
                } else {
                    $weekdays         = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                    $weekDayCountType = array(1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'last');

                    $weekdayPattern = $objEvent->seriesData['seriesPatternWeekday'];
                    $countPattern   = intval($objEvent->seriesData['seriesPatternCount']);
                    $addMonths      = intval($objEvent->seriesData['seriesPatternMonth']);

                    $objCloneEvent->startDate->modify('+'. $addMonths .' months');

                    $weekDay = null;
                    if (($pos = strpos($weekdayPattern, '1')) !== false) {
                        $weekDay = $pos;
                    }

                    // abort in case the event has an invalid recurrence
                    if ($weekDay === null || !isset($weekDayCountType[$countPattern])) {
                        return null;
                    }

                    $objCloneEvent->startDate->modify(
                        $weekDayCountType[$countPattern] .' '. $weekdays[$weekDay] .' of this month'
                    );
                }
                $objCloneEvent->startDate->setTime(
                    $objEvent->startDate->format('H'),
                    $objEvent->startDate->format('i'),
                    $objEvent->startDate->format('s')
                );

                $addDays = $objCloneEvent->startDate->diff($objEvent->startDate)->days;
                $objCloneEvent->endDate->modify('+'. $addDays .' days');
                $objCloneEvent->endDate->setTime(
                    $objEvent->endDate->format('H'),
                    $objEvent->endDate->format('i'),
                    $objEvent->endDate->format('s')
                );
                break;

            default:
                // unkown pattern -> abort
                return null;
                break;
        }

        // check if event has additional set recurrences and add them if
        // they are within the current date boundary
        if (count($additionalRecurrences)) {
            // get event duration. will be used to calculate the end-date
            // of the additional event recurrence
            $diffDays = $objEvent->startDate->diff($objEvent->endDate)->days;

            // check if any of the manually added recurrences will occur
            // before the next calculated recurrence
            foreach ($additionalRecurrences as $key => $additionalRecurrence) {
                // skip manually added recurrences that will occur after the
                // next regular recurrence
                if (
                    $additionalRecurrence > $objCloneEvent->startDate
                ) {
                    continue;
                }
                // apply date/time of manually added recurrence
                $objCloneEvent->startDate->setDate(
                        $additionalRecurrence->format('Y'),
                        $additionalRecurrence->format('m'),
                        $additionalRecurrence->format('d')
                );
                $objCloneEvent->endDate->setDate(
                        $additionalRecurrence->format('Y'),
                        $additionalRecurrence->format('m'),
                        $additionalRecurrence->format('d')
                );

                // adjust end date of manually added recurrence
                $objCloneEvent->endDate->modify('+' . $diffDays . ' days');

                // remove recurrence from list of manually added recurrences
                // as it has been processed now and must not be processed a
                // second time
                unset($additionalRecurrences[$key]);

                // manually added recurrence
                // note that we intentionally did not iterate on
                // $recurrenceEvent as the regular next recurrence has yet to
                // be processed
                return clone $objCloneEvent;
            }
        }

        // adjust $recurrenceEvent to the date/time of
        // the next calculated recurrence (which is $objCloneEvent)
        $recurrenceEvent->startDate->add(
            $recurrenceEvent->startDate->diff(
                $objCloneEvent->startDate
            )
        );
        $recurrenceEvent->endDate->add(
            $recurrenceEvent->endDate->diff(
                $objCloneEvent->endDate
            )
        );

        // return calculated (regular) next recurrence
        return $objCloneEvent;
    }

    /**
     * Check whether the given date exists in the datetime array
     *
     * @param \DateTime $dateTime
     * @param array     $dateTimeArray
     *
     * @return boolean True when date exists, false otherwise
     */
    public function isDateExists(\DateTime $dateTime, $dateTimeArray = array())
    {
        $date = $dateTime->format('Y-m-d');
        foreach ($dateTimeArray as $targetDateTime) {
            if ($date == $targetDateTime->format('Y-m-d')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return Coorinates
     *
     * @param string $street  Street addres
     * @param string $zipcode postal code
     * @param string $city    Name of the city
     *
     * @return boolean true or false
     */
    function _getCoorinates($street,$zipcode,$city) {
        global $_CONFIG;

        if (!ini_get('allow_url_fopen')) {
            ini_set('allow_url_fopen', 'On');
        }

        if(ini_get('allow_url_fopen')) {
            $address = '';
            $address .= $street;
            $address .= ', ';
            $address .= $city;
            $address .= ', ';
            $address .= $zipcode;

            $key = $_CONFIG['googleMapsAPIKey'];

            $url='http://maps.google.com/maps/geo?output=xml&q=';
            $urlcontent = file_get_contents($url . urlencode($address));
            $urlcontent = utf8_encode($urlcontent);
            $xml = new \SimpleXMLElement($urlcontent);

            $arrCoordinates = explode (",",$xml->Response->Placemark->Point->coordinates);
        } else {
            //echo "please check your Google Maps API Key or activate 'allow_url_fopen' or deactivate Goope Maps in this Event.";
            $arrCoordinates   = false;
        }

        return $arrCoordinates;
    }

    /**
     * show date and time by user settings > single day view > start-/endtime separated by start-/enddate
     *
     * @param object  $objEvent          Event object
     * @param boolean $showStartDate     true to show start date, false to hide
     * @param boolean $showEndDate       true to show clock, false to hide
     * @param string  $separatorDate     Date separator
     * @param integer $showTimeType      Event time type
     * @param boolean $showStartTime     true to show start time, false to hide
     * @param boolean $showEndTime       true to show end time, false to hide
     * @param string  $separatorDateTime Date time separator
     * @param string  $separatorTime     Time separator
     * @param boolean $showClock         true to show clock, false to hide
     *
     * @return null
     */
    function getSingleDateBlock($objEvent, $showStartDate, $showEndDate, $separatorDate, $showTimeType, $showStartTime, $showEndTime, $separatorDateTime, $separatorTime, $showClock) {
        global $_ARRAYLANG;

        $startDate = $objEvent->startDate;
        $endDate   = $objEvent->endDate;

        //date
        if($showStartDate && $showEndDate) {
            $this->date = $this->format2userDate($startDate) . $separatorDate . $this->format2userDate($endDate);
        } else if($showStartDate) {
            $this->date = $this->format2userDate($startDate);
        } else if($showEndDate) {
            $this->date = $this->format2userDate($endDate);
        } else {
            $this->date = '';
        }

        //time
        if($showTimeType == 1) {
            //start and/or end time
            if($showStartTime && $showEndTime) {
                $this->sepDateTime = html_entity_decode($separatorDateTime);
                $this->time = $this->format2userTime($startDate) . $separatorTime . $this->format2userTime($endDate);
            } else if($showStartTime) {
                $this->sepDateTime = html_entity_decode($separatorDateTime);
                $this->time = $this->format2userTime($startDate);
            } else if($showEndTime) {
                $this->sepDateTime = html_entity_decode($separatorDateTime);
                $this->time = $this->format2userTime($endDate);
            } else {
                $this->time = '';
            }
            //show / hide clock
            ($showClock && $this->time != '') ? $this->clock = '&nbsp;'.$_ARRAYLANG['TXT_CALENDAR_OCLOCK'] : $this->clock = '';
        } else if($showTimeType == 2) {
            //fulltime
            $this->clock = '';
            $this->sepDateTime = html_entity_decode($separatorDateTime);
            $this->time = $_ARRAYLANG['TXT_CALENDAR_TIME_TYPE_FULLTIME'];
        } else {
            //no time
            $this->clock = '';
            $this->time = '';
        }
    }

    /**
     * show date and time by user settings > several day view
     *
     * @param object  $objEvent             Event object
     * @param string  $separatorDateTime    Date time separator
     * @param string  $separatorSeveralDays SeveralDays separator
     * @param boolean $showClock            true to show clock, false to hide
     * @param integer $part                 Part of the multi date event
     *
     * @return null
     */
    function getMultiDateBlock($objEvent, $separatorDateTime, $separatorSeveralDays, $showClock, $part) {
        global $_ARRAYLANG;

        $this->sepDateTime = html_entity_decode($separatorDateTime);

        if($part == 1) {
            // parse part 1 (start)
            //date
            $startDate  = $objEvent->startDate;
            $this->date = $this->format2userDate($startDate);
            //time
            $this->time = $this->format2userTime($startDate);
            //show / hide clock
            ($showClock && $this->time != '') ? $this->clock = '&nbsp;'.$_ARRAYLANG['TXT_CALENDAR_OCLOCK'] : $this->clock = '';
            //add separator for several days
            if($this->clock != '') {
                $this->clock .= html_entity_decode($separatorSeveralDays);
            } else {
                $this->time .= html_entity_decode($separatorSeveralDays);
            }
        } else {
            // parse part 2 (end)
            //date
            $endDate   = $objEvent->endDate;
            $this->date = $this->format2userDate($endDate);
            //time
            $this->time = $this->format2userTime($endDate);
            //show / hide clock
            ($showClock && $this->time != '') ? $this->clock = '&nbsp;'.$_ARRAYLANG['TXT_CALENDAR_OCLOCK'] : $this->clock = '';
        }
    }

    /**
     * Returns the calendar boxes
     *
     * @param  integer $boxes  Number of boxes
     * @param  year    $year   Year
     * @param  integer $month  month
     * @param  integer $day    day
     * @param  integer $catid  category id
     *
     * @return string  calendar boxes
     */
    function getBoxes($boxes, $year, $month=0, $day=0, $catid=0)
    {
        global $_ARRAYLANG, $objInit;

        if ($catid != 0 && !empty($catid)) {
            $url_cat = "&amp;catid=$catid";
        } else {
            $url_cat = "";
        }

        $url = htmlentities($this->calendarBoxUrl, ENT_QUOTES, CONTREXX_CHARSET).$url_cat;

        $firstblock = true;
        $month      = intval($month);
        $year       = intval($year);
        $day        = intval($day);
        $monthnames = explode(",", $_ARRAYLANG['TXT_CALENDAR_MONTH_ARRAY']);
        $daynames   = explode(',', $_ARRAYLANG['TXT_CALENDAR_DAY_ARRAY']);
        $calenderBoxes = '';
        for ($i=0; $i<$boxes; $i++) {
            $cal = new \activeCalendar($year, $month, $day);
            $cal->setMonthNames($monthnames);
            $cal->setDayNames($daynames);

            if ($firstblock) {
                $cal->enableMonthNav($url);
            } else {
                // This is necessary for the modification of the linkname
                // The modification makes a link on the monthname
                $cal->urlNav=$url;
            }

            // for seperate variable for the month links
            if (!empty($this->calendarBoxMonthNavUrl)) {
                $cal->urlMonthNav = htmlentities($this->calendarBoxMonthNavUrl, ENT_QUOTES, CONTREXX_CHARSET);
            }

            //load events
            foreach ($this->eventList as $objEvent) {
                if ($objEvent->access
                    && $objInit->mode !== \Cx\Core\Core\Controller\Cx::MODE_BACKEND
                    && !\Permission::checkAccess(116, 'static', true)) {
                    continue;
                }
                $startdate     = $this->getUserDateTimeFromIntern($objEvent->startDate);
                $enddate       = $this->getUserDateTimeFromIntern($objEvent->endDate);

                $eventYear     = $startdate->format('Y');
                $eventMonth    = $startdate->format('m');
                $eventDay      = $startdate->format('d');
                $eventEndDay   = $enddate->format('d');
                $eventEndMonth = $enddate->format('m');

                // do only something when the event is in the current month
                if ($eventMonth <= $month && $eventEndMonth >= $month) {
                    // if the event is longer than one day but every day is in the same month
                    if ($eventEndDay > $eventDay && $eventMonth == $eventEndMonth) {
                        $curday = $eventDay;
                        while ($curday <= $eventEndDay) {
                            $eventurl = $url."&amp;yearID=$eventYear&amp;monthID=$month&amp;dayID=$curday".$url_cat;
                            $cal->setEvent("$eventYear", "$eventMonth", "$curday", false, $eventurl);
                            $curday++;
                        }
                    } elseif ($eventEndMonth > $eventMonth) {
                        if ($eventMonth == $month) {
                            // Show the part of the event in the starting month
                            $curday = $eventDay;
                            while ($curday <= 31) {
                                $eventurl = $url."&amp;yearID=$eventYear&amp;monthID=$month&amp;dayID=$curday".$url_cat;
                                $cal->setEvent("$eventYear", "$eventMonth", "$curday", false, $eventurl);
                                $curday++;
                            }
                        } elseif ($eventEndMonth == $month) {
                            // show the part of the event in the ending month
                            $curday = $eventEndDay;
                            while ($curday > 0) {
                                $eventurl = $url."&amp;yearID=$eventYear&amp;monthID=$month&amp;dayID=$curday".$url_cat;
                                $cal->setEvent("$eventYear", "$eventEndMonth", "$curday", false, $eventurl);
                                $curday--;
                            }
                        } elseif ($eventMonth < $month && $eventEndMonth > $month) {
                            foreach (range(0,31,1) as $curday) {
                                $eventurl = $url."&amp;yearID=$eventYear&amp;monthID=$month&amp;dayID=$curday".$url_cat;
                                $cal->setEvent("$eventYear", "$month", "$curday", false, $eventurl);
                            }
                        }
                    } else {
                        $eventurl = $url."&amp;yearID=$eventYear&amp;monthID=$month&amp;dayID=$eventDay".$url_cat;
                        $cal->setEvent("$eventYear", "$eventMonth", "$eventDay", false, $eventurl);
                    }
                }
            }

            $calenderBoxes .= $cal->showMonth(false, true);
            if ($month == 12) {
                $year++;
                $month = 1;
            } else {
                $month++;
            }
            $day = 0;
            $firstblock = false;
        }

        return $calenderBoxes;
    }

    /**
     * Returns the javascript used for the calendar boxes
     *
     * @return string javascript
     */
    function getCalendarBoxJS()
    {
            return     '<script type="text/javascript">
                            /* <![CDATA[ */
                            function changecat()
                            {
                                    var href = window.location.href;
                                    var catid = $J("#selectcat").val();
                                    href = href.replace(/&catid=[0-9]+/g, \'\');
                                    href = href.replace(/&act=search/g, \'\');
                                    href += "&catid=" + catid;
                                    window.location.href = href;
                            }
                            /* ]]> */
                            </script>';
    }

    function getPriorityImage($objEvent)
    {
        global $_ARRAYLANG;

        $priority    = '';
        $priorityImg = '';
        switch ($objEvent->priority) {
            case 1:
                $priority    = $_ARRAYLANG['TXT_CALENDAR_PRIORITY_VERY_HEIGHT'];
                $priorityImg = "<img src='modules/Calendar/View/Media/very_height.gif' border='0' title='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_VERY_HEIGHT']."' alt='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_VERY_HEIGHT']."' />";
                break;
            case 2:
                $priority    = $_ARRAYLANG['TXT_CALENDAR_PRIORITY_HEIGHT'];
                $priorityImg = "<img src='modules/Calendar/View/Media/height.gif' border='0' title='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_HEIGHT']."' alt='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_HEIGHT']."' />";
                break;
            case 3:
                $priority    = $_ARRAYLANG['TXT_CALENDAR_PRIORITY_NORMAL'];
                $priorityImg = "&nbsp;";
                break;
            case 4:
                $priority    = $_ARRAYLANG['TXT_CALENDAR_PRIORITY_LOW'];
                $priorityImg = "<img src='modules/Calendar/View/Media/low.gif' border='0' title='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_LOW']."' alt='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_LOW']."' />";
                break;
            case 5:
                $priority    = $_ARRAYLANG['TXT_CALENDAR_PRIORITY_VERY_LOW'];
                $priorityImg = "<img src='modules/Calendar/View/Media/very_low.gif' border='0' title='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_VERY_LOW']."' alt='".$_ARRAYLANG['TXT_CALENDAR_PRIORITY_VERY_LOW']."' />";
                break;
        }

        return array($priority, $priorityImg);
    }
}
