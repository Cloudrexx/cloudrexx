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
 * Calendar Class Event
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */

namespace Cx\Modules\Calendar\Controller;
/**
 * Calendar Class Event
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarEvent extends CalendarLibrary
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
     * Event title
     *
     * @var string
     * @access public
     */
    public $title;

    /**
     * Event teaser
     *
     * @var string
     */
    public $teaser;

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
     * Event price
     *
     * @access public
     * @var integer
     */
    public $price;

    /**
     * Event link
     *
     * @access public
     * @var string
     */
    public $link;

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
     * Event location type
     * 1 => Manual Entry
     * 2 => Refer to mediadir module
     *
     * @access public
     * @var integer
     */
    public $locationType;

    /**
     * Event Host type
     * 1 => Manual Entry
     * 2 => Refer to mediadir module
     *
     * @access public
     * @var integer
     */
    public $hostType;

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
     * Event confirmed
     *
     * @access public
     * @var boolean
     */
    public $confirmed;

    /**
     * Whether or not to show the detail view of the event
     *
     * @var boolean
     */
    public $showDetailView;

    /**
     * Event author
     *
     * @access public
     * @var string
     */
    public $author;

    /**
     * Event category id
     *
     * @access public
     * @var integer
     */
    public $catId;

    /**
     * Event series status
     *
     * @access public
     * @var integer
     */
    public $seriesStatus;

    /**
     * True when event is independent series
     *
     * @var boolean
     */
    public $independentSeries;

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
     * Event google status
     *
     * @access public
     * @var integer
     */
    public $google;

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
     * Template Id of the invitation mail
     *
     * @var integer
     */
    public $invitationTemplate;

    /**
     * Event status of registration
     *
     * @access public
     * @var boolean
     */
    public $registration;

    /**
     * Event registration form
     *
     * @access public
     * @var integer
     */
    public $registrationForm;

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
     * Event E-mail template
     *
     * @access public
     * @var integer
     */
    public $emailTemplate;

    /**
     * Event ticket sales
     *
     * @access public
     * @var integer
     */
    public $ticketSales;

    /**
     * Event available seating
     *
     * @access public
     * @var integer
     */
    public $numSeating;

    /**
     * Event free palces
     *
     * @access public
     * @var integer
     */
    protected $freePlaces;

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
     * Event host id
     *
     * @access public
     * @var string
     */
    public $hostId = "local";

    /**
     * module image upload physical path
     *
     * @access public
     * @var string
     */
    public $uploadImgPath = '';

    /**
     * module uploaded image web path
     *
     * @access public
     * @var string
     */
    public $uploadImgWebPath = '';

    /**
     * Registered members count for the event
     *
     * @var integer
     */
    protected $registrationCount = 0;

    /**
     * Waitlist members count for the event
     *
     * @var integer
     */
    protected $waitlistCount = 0;

    /**
     * Cancellation members count for the event
     *
     * @var integer
     */
    protected $cancellationCount = 0;

    /**
     * Array of language IDs the event has been fetched from the database from already
     * @var array
     */
    protected $fetchedLangIds = array();

    /**
     * Event street
     *
     * @access public
     * @var string
     */
    public $place_street;

    /**
     * Event zip
     *
     * @access public
     * @var string
     */
    public $place_zip;

    /**
     * Event city
     *
     * @access public
     * @var string
     */
    public $place_city;

    /**
     * Event country
     *
     * @access public
     * @var string
     */
    public $place_country;

    /**
     * Event place website
     *
     * @access public
     * @var string
     */
    public $place_website;

    /**
     * Event map
     *
     * @access public
     * @var string
     */
    public $place_map;

    /**
     * Event link
     *
     * @access public
     * @var string
     */
    public $place_link;

    /**
     * Event place phone
     *
     * @access public
     * @var string
     */
    public $place_phone;

    /**
     * Event organizer name
     *
     * @access public
     * @var string
     */
    public $org_name;

    /**
     * Event organizer street
     *
     * @access public
     * @var string
     */
    public $org_street;

    /**
     * Event organizer zip
     *
     * @access public
     * @var string
     */
    public $org_zip;

    /**
     * Event organizer city
     *
     * @access public
     * @var string
     */
    public $org_city;

    /**
     * Event organizer country
     *
     * @access public
     * @var string
     */
    public $org_country;

    /**
     * Event organizer website
     *
     * @access public
     * @var string
     */
    public $org_website;

    /**
     * Event organizer link
     *
     * @access public
     * @var string
     */
    public $org_link;

    /**
     * Event organizer phone
     *
     * @access public
     * @var string
     */
    public $org_phone;

    /**
     * Event organizer email
     *
     * @access public
     * @var string
     */
    public $org_email;

    /**
     * Flag to check whether the registration is already calculated or not.
     *
     * @var boolean
     */
    protected $registrationCalculated = false;

    /**
     * Registration type none
     */
    const EVENT_REGISTRATION_NONE = 0;

    /**
     * Registration type internal
     */
    const EVENT_REGISTRATION_INTERNAL = 1;

    /**
     * Registration type external
     */
    const EVENT_REGISTRATION_EXTERNAL = 2;

    /**
     * Registration link, when registration type is external
     *
     * @var string
     */
    public $registrationExternalLink;

    /**
     * True when external registration is fully booked
     *
     * @var boolean
     */
    public $registrationExternalFullyBooked;

    /**
     * Constructor
     *
     * Loads the event object of given id
     * Call the parent constructor to initialize the settings values
     *
     * @param integer $id Event id
     */
    function __construct($id=null){
        if($id != null) {
            $this->get($id);
        }

        $this->uploadImgPath    = \Env::get('cx')->getWebsiteImagesPath().'/'.$this->moduleName.'/';
        $this->uploadImgWebPath = \Env::get('cx')->getWebsiteImagesWebPath().'/'.$this->moduleName.'/';

        $this->getSettings();
    }

    /**
     * Load the requested event by id
     *
     * @param integer $eventId        Event Id
     * @param integer $eventStartDate Event start date
     * @param integer $langId         Language id
     *
     * @return null
     */
    function get($eventId, $eventStartDate=null, $langId=null) {
        global $objDatabase, $_ARRAYLANG, $_LANGID, $objInit;

        $this->getSettings();

        if ($langId == null) {
            $langId = $_LANGID;
        }

        $query = "SELECT event.id AS id,
                         event.type AS type,
                         event.startdate AS startdate,
                         event.enddate AS enddate,
                         event.use_custom_date_display AS useCustomDateDisplay,
                         event.showStartDateList AS showStartDateList,
                         event.showEndDateList AS showEndDateList,
                         event.showStartTimeList AS showStartTimeList,
                         event.showEndTimeList AS showEndTimeList,
                         event.showTimeTypeList AS showTimeTypeList,
                         event.showStartDateDetail AS showStartDateDetail,
                         event.showEndDateDetail AS showEndDateDetail,
                         event.showStartTimeDetail AS showStartTimeDetail,
                         event.showEndTimeDetail AS showEndTimeDetail,
                         event.showTimeTypeDetail AS showTimeTypeDetail,
                         event.access AS access,
                         event.price AS price,
                         event.link AS link,
                         event.pic AS pic,
                         event.attach AS attach,
                         event.place_mediadir_id AS place_mediadir_id,
                         event.host_mediadir_id AS host_mediadir_id,
                         event.priority AS priority,
                         event.catid AS catid,
                         event.status AS status,
                         event.author AS author,
                         event.confirmed AS confirmed,
                         event.show_detail_view,
                         event.show_in AS show_in,
                         event.google AS google,
                         event.invited_groups AS invited_groups,
                         event.invited_mails AS invited_mails,
                         event.invitation_sent AS invitation_sent,
                         event.invitation_email_template AS invitation_email_template,
                         event.registration AS registration,
                         event.registration_form AS registration_form,
                         event.registration_num AS registration_num,
                         event.registration_notification AS registration_notification,
                         event.registration_external_link,
                         event.registration_external_fully_booked,
                         event.email_template AS email_template,
                         event.ticket_sales AS ticket_sales,
                         event.num_seating AS num_seating,
                         event.series_status AS series_status,
                         event.independent_series,
                         event.series_type AS series_type,
                         event.series_pattern_count AS series_pattern_count,
                         event.series_pattern_weekday AS series_pattern_weekday,
                         event.series_pattern_day AS series_pattern_day,
                         event.series_pattern_week AS series_pattern_week,
                         event.series_pattern_month AS series_pattern_month,
                         event.series_pattern_type AS series_pattern_type,
                         event.series_pattern_dourance_type AS series_pattern_dourance_type,
                         event.series_pattern_end AS series_pattern_end,
                         event.series_pattern_end_date AS series_pattern_end_date,
                         event.series_pattern_begin AS series_pattern_begin,
                         event.series_pattern_exceptions AS series_pattern_exceptions,
                         event.all_day,
                         event.location_type AS location_type,
                         field.place AS place,
                         event.place_street AS place_street,
                         event.place_zip AS place_zip,
                         field.place_city AS place_city,
                         field.place_country AS place_country,
                         event.place_website AS place_website,
                         event.place_link AS place_link,
                         event.place_phone AS place_phone,
                         event.place_map AS place_map,
                         event.host_type AS host_type,
                         field.org_name AS org_name,
                         event.org_street AS org_street,
                         event.org_zip AS org_zip,
                         field.org_city AS org_city,
                         field.org_country AS org_country,
                         event.org_website AS org_website,
                         event.org_link AS org_link,
                         event.org_phone AS org_phone,
                         event.org_email AS org_email,
                         field.title AS title,
                         field.teaser AS teaser,
                         field.description AS description
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_event AS event,
                         ".DBPREFIX."module_".$this->moduleTablePrefix."_event_field AS field
                   WHERE event.id = '".intval($eventId)."'
                     AND (    event.id = field.event_id
                          AND field.lang_id = '".intval($langId)."'
                          AND FIND_IN_SET('".intval($langId)."',event.show_in)>0)
                   LIMIT 1";


        $objResult = $objDatabase->Execute($query);

        $this->fetchedLangIds[] = $langId;

        // check if events of all languages shall be listed (not only those available in the requested language)
        if (   \Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND
            || $this->arrSettings['showEventsOnlyInActiveLanguage'] == 2
        ) {
            // try to refetch the event in case it does not exist in the current requested language
            if($objResult->RecordCount() == 0) {
                $langIdsToFetch = array_diff(array_keys(\FWLanguage::getActiveFrontendLanguages()), $this->fetchedLangIds);
                if ($langIdsToFetch) {
                    $this->get($eventId,$eventStartDate,current($langIdsToFetch));
                }
            }
        }

        if ($objResult !== false) {
            if(!empty($objResult->fields['title'])) {
                $this->id = intval($eventId);
                $this->type = intval($objResult->fields['type']);
                $this->title = htmlentities(stripslashes($objResult->fields['title']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->teaser = htmlentities(stripslashes($objResult->fields['teaser']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->pic = htmlentities($objResult->fields['pic'], ENT_QUOTES, CONTREXX_CHARSET);
                $this->attach = htmlentities($objResult->fields['attach'], ENT_QUOTES, CONTREXX_CHARSET);
                $this->author = htmlentities($objResult->fields['author'], ENT_QUOTES, CONTREXX_CHARSET);
                $this->startDate = $this->getInternDateTimeFromDb($objResult->fields['startdate']);
                $this->endDate   = $this->getInternDateTimeFromDb($objResult->fields['enddate']);
                $this->useCustomDateDisplay = intval($objResult->fields['useCustomDateDisplay']);
                $this->showStartDateList = intval($objResult->fields['showStartDateList']);
                $this->showEndDateList = intval($objResult->fields['showEndDateList']);
                $this->showStartTimeList = intval($objResult->fields['showStartTimeList']);
                $this->showEndTimeList = intval($objResult->fields['showEndTimeList']);
                $this->showTimeTypeList = intval($objResult->fields['showTimeTypeList']);
                $this->showStartDateDetail = intval($objResult->fields['showStartDateDetail']);
                $this->showEndDateDetail = intval($objResult->fields['showEndDateDetail']);
                $this->showStartTimeDetail = intval($objResult->fields['showStartTimeDetail']);
                $this->showEndTimeDetail = intval($objResult->fields['showEndTimeDetail']);
                $this->showTimeTypeDetail = intval($objResult->fields['showTimeTypeDetail']);
                $this->all_day  = intval($objResult->fields['all_day']);
                $this->confirmed = intval($objResult->fields['confirmed']);
                $this->invitationSent = intval($objResult->fields['invitation_sent']);
                $this->invitationTemplate = json_decode($objResult->fields['invitation_email_template'], true);
                $this->access = intval($objResult->fields['access']);
                $this->price = intval($objResult->fields['price']);
                $this->link = htmlentities(stripslashes($objResult->fields['link']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->priority = intval($objResult->fields['priority']);
                $this->description = $objResult->fields['description'];

                $this->locationType = (int) $objResult->fields['location_type'];
                $this->place_mediadir_id = (int) $objResult->fields['place_mediadir_id'];
                $this->place        = htmlentities(stripslashes($objResult->fields['place']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->place_street = htmlentities(stripslashes($objResult->fields['place_street']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->place_zip    = htmlentities(stripslashes($objResult->fields['place_zip']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->place_city   = htmlentities(stripslashes($objResult->fields['place_city']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->place_country = htmlentities(stripslashes($objResult->fields['place_country']), ENT_QUOTES, CONTREXX_CHARSET);
                $this->place_website= contrexx_raw2xhtml($objResult->fields['place_website']);
                $this->place_link   = contrexx_raw2xhtml($objResult->fields['place_link']);
                $this->place_phone  = contrexx_raw2xhtml($objResult->fields['place_phone']);
                $this->place_map    = contrexx_raw2xhtml($objResult->fields['place_map']);
                $this->hostType = (int) $objResult->fields['host_type'];
                $this->host_mediadir_id = (int) $objResult->fields['host_mediadir_id'];
                $this->org_name     = contrexx_raw2xhtml($objResult->fields['org_name']);
                $this->org_street   = contrexx_raw2xhtml($objResult->fields['org_street']);
                $this->org_zip      = contrexx_raw2xhtml($objResult->fields['org_zip']);
                $this->org_city     = contrexx_raw2xhtml($objResult->fields['org_city']);
                $this->org_country  = contrexx_raw2xhtml($objResult->fields['org_country']);
                $this->org_website  = contrexx_raw2xhtml($objResult->fields['org_website']);
                $this->org_link     = contrexx_raw2xhtml($objResult->fields['org_link']);
                $this->org_phone    = contrexx_raw2xhtml($objResult->fields['org_phone']);
                $this->org_email    = contrexx_raw2xhtml($objResult->fields['org_email']);

                $this->showIn = htmlentities($objResult->fields['show_in'], ENT_QUOTES, CONTREXX_CHARSET);
                $this->availableLang = intval($langId);
                $this->status = intval($objResult->fields['status']);
                $this->showDetailView = intval($objResult->fields['show_detail_view']);
                $this->catId = intval($objResult->fields['catid']);
                $this->google = intval($objResult->fields['google']);
                $this->seriesStatus = intval($objResult->fields['series_status']);
                $this->independentSeries = intval($objResult->fields['independent_series']);

                if($this->seriesStatus == 1) {
                    $this->seriesData['seriesPatternCount'] = intval($objResult->fields['series_pattern_count']);
                    $this->seriesData['seriesType'] = intval($objResult->fields['series_type']);
                    $this->seriesData['seriesPatternCount'] = intval($objResult->fields['series_pattern_count']);
                    $this->seriesData['seriesPatternWeekday'] = htmlentities($objResult->fields['series_pattern_weekday'], ENT_QUOTES, CONTREXX_CHARSET);
                    $this->seriesData['seriesPatternDay'] = intval($objResult->fields['series_pattern_day']);
                    $this->seriesData['seriesPatternWeek'] = intval($objResult->fields['series_pattern_week']);
                    $this->seriesData['seriesPatternMonth'] = intval($objResult->fields['series_pattern_month']);
                    $this->seriesData['seriesPatternType'] = intval($objResult->fields['series_pattern_type']);
                    $this->seriesData['seriesPatternDouranceType'] = intval($objResult->fields['series_pattern_dourance_type']);
                    $this->seriesData['seriesPatternEnd'] = intval($objResult->fields['series_pattern_end']);
                    $this->seriesData['seriesPatternEndDate'] = $this->getInternDateTimeFromDb($objResult->fields['series_pattern_end_date']);
                    $this->seriesData['seriesPatternBegin'] = intval($objResult->fields['series_pattern_begin']);
                    $seriesPatternExceptions = array();
                    if (!empty($objResult->fields['series_pattern_exceptions'])) {
                        $seriesPatternExceptions = array_map(array($this, 'getInternDateTimeFromDb'), (array) explode(",", $objResult->fields['series_pattern_exceptions']));
                    }
                    $this->seriesData['seriesPatternExceptions'] = $seriesPatternExceptions;
                }


                $this->invitedGroups = explode(',', $objResult->fields['invited_groups']);
                $this->invitedMails =  htmlentities($objResult->fields['invited_mails'], ENT_QUOTES, CONTREXX_CHARSET);
                $this->registration = intval($objResult->fields['registration']);
                $this->registrationForm = intval($objResult->fields['registration_form']);
                $this->numSubscriber = intval($objResult->fields['registration_num']);
                $this->notificationTo = htmlentities($objResult->fields['registration_notification'], ENT_QUOTES, CONTREXX_CHARSET);
                $this->emailTemplate = json_decode($objResult->fields['email_template'], true);
                $this->registrationExternalLink = contrexx_raw2xhtml($objResult->fields['registration_external_link']);
                $this->registrationExternalFullyBooked = contrexx_input2int($objResult->fields['registration_external_fully_booked']);
                $this->ticketSales = intval($objResult->fields['ticket_sales']);
                $this->arrNumSeating = json_decode($objResult->fields['num_seating']);
                $this->numSeating = !empty($this->arrNumSeating) ? implode(',', $this->arrNumSeating) : '';

                $queryHosts = '
                    SELECT host_id
                    FROM '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_event_host
                    WHERE event_id = '.intval($eventId)
                ;

                $objResultHosts = $objDatabase->Execute($queryHosts);

                if ($objResultHosts !== false) {
                    while (!$objResultHosts->EOF) {
                        $this->relatedHosts[] = intval($objResultHosts->fields['host_id']);
                        $objResultHosts->MoveNext();
                    }
                }

                $this->getData();
            }
        }
    }

    /**
     * gets the data for the event
     *
     * @return null
     */
    function getData() {
        global $objDatabase, $_ARRAYLANG, $_LANGID;

        $activeLangs = explode(",", $this->showIn);
        $this->arrData = array();

        foreach ($activeLangs as $key => $langId) {
            $query = "SELECT field.title AS title,
                             field.teaser AS teaser,
                             field.description AS description,
                             field.redirect AS redirect,
                             field.place AS place,
                             field.place_city AS place_city,
                             field.place_country AS place_country,
                             field.org_name AS org_name,
                             field.org_city AS org_city,
                             field.org_country AS org_country
                        FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_event_field AS field
                       WHERE field.event_id = '".intval($this->id)."'
                         AND field.lang_id = '".intval($langId)."'
                       LIMIT 1";

            $objResult = $objDatabase->Execute($query);

            if ($objResult !== false) {
                while (!$objResult->EOF) {
                        $this->arrData['title'][$langId] = htmlentities(stripslashes($objResult->fields['title']), ENT_QUOTES, CONTREXX_CHARSET);
                        $this->arrData['teaser'][$langId] = htmlentities(stripslashes($objResult->fields['teaser']), ENT_QUOTES, CONTREXX_CHARSET);
                        $this->arrData['description'][$langId] = stripslashes($objResult->fields['description']);
                        $this->arrData['redirect'][$langId] = htmlentities(stripslashes($objResult->fields['redirect']), ENT_QUOTES, CONTREXX_CHARSET);
                        $this->arrData['place'][$langId] = contrexx_raw2xhtml($objResult->fields['place']);
                        $this->arrData['place_city'][$langId] = contrexx_raw2xhtml($objResult->fields['place_city']);
                        $this->arrData['place_country'][$langId] = contrexx_raw2xhtml($objResult->fields['place_country']);
                        $this->arrData['org_name'][$langId] = contrexx_raw2xhtml($objResult->fields['org_name']);
                        $this->arrData['org_city'][$langId] = contrexx_raw2xhtml($objResult->fields['org_city']);
                        $this->arrData['org_country'][$langId] = contrexx_raw2xhtml($objResult->fields['org_country']);
                        $objResult->MoveNext();
                }
            }
        }
    }

    /**
     * Save the event to the database
     *
     * @param array $data
     *
     * @return boolean true if saved successfully, false otherwise
     */
    function save($data){
        global $objDatabase, $_LANGID, $_CONFIG, $objInit;

        $this->getSettings();

        if(empty($data['startDate']) || empty($data['endDate']) || empty($data['category']) || ($data['seriesStatus'] == 1 && $data['seriesType'] == 2 && empty($data['seriesWeeklyDays']))) {
            return false;
        }

        foreach ($_POST['showIn'] as $key => $langId) {
            if(empty($_POST['title'][$langId]) && empty($_POST['title'][$_LANGID])) {
                return false;
            }
        }

        list($startDate, $strStartTime) = explode(' ', $data['startDate']);
        list($startHour, $startMin)     = explode(':', $strStartTime);

        list($endDate, $strEndTime)     = explode(' ', $data['endDate']);
        list($endHour, $endMin)         = explode(':', $strEndTime);

        if ($data['all_day']) {
            list($startHour, $startMin) = array(0, 0);
            list($endHour, $endMin)     = array(23, 59);;
        }

        //event data
        $id            = isset($data['copy']) && !empty($data['copy']) ? 0 : (isset($data['id']) ? intval($data['id']) : 0);
        $type          = isset($data['type']) ? intval($data['type']) : 0;

        $startDate = $this->getDbDateTimeFromIntern($this->getDateTime($startDate, intval($startHour), intval($startMin)))->format('Y-m-d H:i:s');
        $endDate   = $this->getDbDateTimeFromIntern($this->getDateTime($endDate, intval($endHour), intval($endMin)))->format('Y-m-d H:i:s');

        $google        = isset($data['google']) ? intval($data['google']) : 0;
        $allDay        = isset($data['all_day']) ? 1 : 0;
        $convertBBCode = ($objInit->mode == 'frontend' && empty($id));
        $showDetailView= isset($data['show-detail-view']) ? 1 : 0;

        $useCustomDateDisplay = isset($data['showDateSettings']) ? 1 : 0;
        $showStartDateList    = isset($data['showStartDateList']) ? $data['showStartDateList'] : 0;
        $showEndDateList      = isset($data['showEndDateList']) ? $data['showEndDateList'] : 0;

        if($objInit->mode == 'backend') {
            // reset time values if "no time" is selected
            if($data['showTimeTypeList'] == 0 ) {
                $showStartTimeList = 0;
                $showEndTimeList   = 0;
            } else {
                $showStartTimeList = isset($data['showStartTimeList']) ? $data['showStartTimeList'] : '';
                $showEndTimeList   = isset($data['showEndTimeList']) ? $data['showEndTimeList'] : '';
            }

            $showTimeTypeList    = isset($data['showTimeTypeList']) ? $data['showTimeTypeList'] : '';
            $showStartDateDetail = isset($data['showStartDateDetail']) ? $data['showStartDateDetail'] : '';
            $showEndDateDetail   = isset($data['showEndDateDetail']) ? $data['showEndDateDetail'] : '';

            // reset time values if "no time" is selected
            if( $data['showTimeTypeDetail'] == 0){
                $showStartTimeDetail = 0;
                $showEndTimeDetail   = 0;
            } else {
                $showStartTimeDetail = isset($data['showStartTimeDetail']) ? $data['showStartTimeDetail'] : '';
                $showEndTimeDetail   = isset($data['showEndTimeDetail']) ? $data['showEndTimeDetail'] : '';
            }
            $showTimeTypeDetail = isset($data['showTimeTypeDetail']) ? $data['showTimeTypeDetail'] : '';
        } else {
            $showStartDateList = ($this->arrSettings['showStartDateList'] == 1) ? 1 : 0;
            $showEndDateList   = ($this->arrSettings['showEndDateList'] == 1) ? 1 : 0;
            $showStartTimeList = ($this->arrSettings['showStartTimeList'] == 1) ? 1 : 0;
            $showEndTimeList   = ($this->arrSettings['showEndTimeList'] == 1) ? 1 : 0;

            // reset time values if "no time" is selected
            if($showStartTimeList == 1 || $showEndTimeList == 1) {
                $showTimeTypeList = 1;
            } else {
                $showStartTimeList = 0;
                $showEndTimeList   = 0;
                $showTimeTypeList  = 0;
            }

            $showStartDateDetail = ($this->arrSettings['showStartDateDetail'] == 1) ? 1 : 0;
            $showEndDateDetail   = ($this->arrSettings['showEndDateDetail'] == 1) ? 1 : 0;
            $showStartTimeDetail = ($this->arrSettings['showStartTimeDetail'] == 1) ? 1 : 0;
            $showEndTimeDetail   = ($this->arrSettings['showEndTimeDetail'] == 1) ? 1 : 0;

            // reset time values if "no time" is selected
            if($showStartTimeDetail == 1 || $showEndTimeDetail == 1) {
                $showTimeTypeDetail = 1;
            } else {
                $showStartTimeDetail = 0;
                $showEndTimeDetail   = 0;
                $showTimeTypeDetail  = 0;
            }
        }

        //backward compatibility, multilingual fields might be empty
        if (empty($data['event_place'])) {
            $data['event_place'] = array(
                isset($data['place']) ? $data['place'] : ''
            );
        }
        if (empty($data['event_place_city'])) {
            $data['event_place_city'] = array(
                isset($data['city']) ? $data['city'] : ''
            );
        }
        if (empty($data['event_place_country'])) {
            $data['event_place_country'] = array(
                isset($data['country']) ? $data['country'] : ''
            );
        }
        if (empty($data['event_org_name'])) {
            $data['event_org_name'] = array(
                isset($data['organizerName']) ? $data['organizerName'] : ''
            );
        }
        if (empty($data['event_org_city'])) {
            $data['event_org_city'] = array(
                isset($data['organizerCity']) ? $data['organizerCity'] : ''
            );
        }
        if (empty($data['event_org_country'])) {
            $data['event_org_country'] = array(
                isset($data['organizerCountry']) ? $data['organizerCountry'] : ''
            );
        }

        $access                    = isset($data['access']) ? intval($data['access']) : 0;
        $priority                  = isset($data['priority']) ? intval($data['priority']) : 0;
        $placeMediadir             = isset($data['placeMediadir']) ? intval($data['placeMediadir']) : 0;
        $hostMediadir              = isset($data['hostMediadir']) ? intval($data['hostMediadir']) : 0;
        $price                     = isset($data['price']) ? contrexx_addslashes(contrexx_strip_tags($data['price'])) : 0;
        $link                      = isset($data['link']) ? contrexx_addslashes(contrexx_strip_tags($data['link'])) : '';
        $pic                       = isset($data['picture']) ? contrexx_addslashes(contrexx_strip_tags($data['picture'])) : '';
        $attach                    = isset($data['attachment']) ? contrexx_addslashes(contrexx_strip_tags($data['attachment'])) : '';
        $catId                     = isset($data['category']) ? intval($data['category']) : '';
        $showIn                    = isset($data['showIn']) ? contrexx_addslashes(contrexx_strip_tags(join(",",$data['showIn']))) : '';
        $invited_groups            = isset($data['selectedGroups']) ? join(',', $data['selectedGroups']) : '';
        $invited_mails             = isset($data['invitedMails']) ? contrexx_addslashes(contrexx_strip_tags($data['invitedMails'])) : '';
        $send_invitation           = isset($data['sendInvitation']) ? intval($data['sendInvitation']) : 0;
        $invitationTemplate        = isset($data['invitationEmailTemplate']) ? contrexx_input2db($data['invitationEmailTemplate']) : 0;
        $registration              =   isset($data['registration']) && in_array($data['registration'], array(self::EVENT_REGISTRATION_NONE, self::EVENT_REGISTRATION_INTERNAL, self::EVENT_REGISTRATION_EXTERNAL))
                                     ? intval($data['registration']) : 0;
        $registration_form         = isset($data['registrationForm']) ? intval($data['registrationForm']) : 0;
        $registration_num          = isset($data['numSubscriber']) ? intval($data['numSubscriber']) : 0;
        $registration_notification = isset($data['notificationTo']) ? contrexx_addslashes(contrexx_strip_tags($data['notificationTo'])) : '';
        $email_template            = isset($data['emailTemplate']) ? contrexx_input2db($data['emailTemplate']) : 0;
        $registrationExternalLink  = isset($data['registration_external_link']) ? contrexx_input2db($data['registration_external_link']) : '';
        $registrationExternalFullyBooked = isset($data['registration_external_full_booked']) ? 1 : 0;
        $ticket_sales              = isset($data['ticketSales']) ? intval($data['ticketSales']) : 0;
        $num_seating               = isset($data['numSeating']) ? json_encode(explode(',', $data['numSeating'])) : '';
        $related_hosts             = isset($data['selectedHosts']) ? $data['selectedHosts'] : '';
        $locationType              = isset($data['eventLocationType']) ? (int) $data['eventLocationType'] : $this->arrSettings['placeData'];
        $hostType                  = isset($data['eventHostType']) ? (int) $data['eventHostType'] : $this->arrSettings['placeDataHost'];
        $street                    = isset($data['street']) ? contrexx_input2db(contrexx_strip_tags($data['street'])) : '';
        $zip                       = isset($data['zip']) ? contrexx_input2db(contrexx_strip_tags($data['zip'])) : '';
        $placeWebsite              = isset($data['placeWebsite']) ? contrexx_input2db($data['placeWebsite']) : '';
        $placeLink                 = isset($data['placeLink']) ? contrexx_input2db($data['placeLink']) : '';
        $placePhone                = isset($data['placePhone']) ? contrexx_input2db($data['placePhone']) : '';
        $placeMap                  = isset($data['placeMap']) ? contrexx_input2db($data['placeMap']) : '';
        $update_invitation_sent    = ($send_invitation == 1);

        if (!empty($placeWebsite)) {
            if (!preg_match('%^(?:ftp|http|https):\/\/%', $placeWebsite)) {
                $placeWebsite = "http://".$placeWebsite;
            }
        }

        if (!empty($placeLink)) {
            if (!preg_match('%^(?:ftp|http|https):\/\/%', $placeLink)) {
                $placeLink = "http://".$placeLink;
            }
        }

        if($objInit->mode == 'frontend') {
            $mapUploaderId = isset($_REQUEST[self::MAP_FIELD_KEY])
                             ? contrexx_input2raw($_REQUEST[self::MAP_FIELD_KEY])
                             : '';
            if (!empty($mapUploaderId)) {
                $picture = $this->_handleUpload($mapUploaderId);
                if (!empty($picture)) {
                    $placeMap = $picture;
                }
            }
        }

        $orgStreet = isset($data['organizerStreet']) ? contrexx_input2db($data['organizerStreet']) : '';
        $orgZip    = isset($data['organizerZip']) ? contrexx_input2db($data['organizerZip']) : '';
        $orgWebsite= isset($data['organizerWebsite']) ? contrexx_input2db($data['organizerWebsite']) : '';
        $orgLink   = isset($data['organizerLink']) ? contrexx_input2db($data['organizerLink']) : '';
        $orgPhone  = isset($data['organizerPhone']) ? contrexx_input2db($data['organizerPhone']) : '';
        $orgEmail  = isset($data['organizerEmail']) ? contrexx_input2db($data['organizerEmail']) : '';

        if (!empty($orgWebsite)) {
            if (!preg_match('%^(?:ftp|http|https):\/\/%', $orgWebsite)) {
                $orgWebsite = "http://".$orgWebsite;
            }
        }

        if (!empty($orgLink)) {
            if (!preg_match('%^(?:ftp|http|https):\/\/%', $orgLink)) {
                $orgLink = "http://".$orgLink;
            }
        }

        // create thumb if not exists
        if (!file_exists(\Env::get('cx')->getWebsitePath()."$placeMap.thumb")) {
            $objImage = new \ImageManager();
            $objImage->_createThumb(dirname(\Env::get('cx')->getWebsitePath()."$placeMap")."/", '', basename($placeMap), 180);
        }

        //frontend picture upload & thumbnail creation
        if($objInit->mode == 'frontend') {
            $pictureUploaderId    = isset($_REQUEST[self::PICTURE_FIELD_KEY])
                                    ? contrexx_input2raw($_REQUEST[self::PICTURE_FIELD_KEY])
                                    : '';
            $attachmentUploaderId = isset($_REQUEST[self::ATTACHMENT_FIELD_KEY])
                                    ? contrexx_input2raw($_REQUEST[self::ATTACHMENT_FIELD_KEY])
                                    : '';

            if (!empty($pictureUploaderId)) {
                $picture = $this->_handleUpload($pictureUploaderId);

                if (!empty($picture)) {
                    //delete thumb
                    if (file_exists("{$this->uploadImgPath}$pic.thumb")) {
                        \Cx\Lib\FileSystem\FileSystem::delete_file($this->uploadImgPath."/.$pic.thumb");
                    }

                    //delete image
                    if (file_exists("{$this->uploadImgPath}$pic")) {
                        \Cx\Lib\FileSystem\FileSystem::delete_file($this->uploadImgPath."/.$pic");
                    }

                    $pic = $picture;
                }
            }

            if (!empty($attachmentUploaderId)) {
                $attachment = $this->_handleUpload($attachmentUploaderId);
                if ($attachment) {
                    //delete file
                    if (file_exists("{$this->uploadImgPath}$attach")) {
                        \Cx\Lib\FileSystem\FileSystem::delete_file($this->uploadImgPath."/.$attach");
                    }
                    $attach = $attachment;
                }
            }

        } else {
            // create thumb if not exists
            if (!file_exists(\Env::get('cx')->getWebsitePath()."$pic.thumb")) {
                $objImage = new \ImageManager();
                $objImage->_createThumb(dirname(\Env::get('cx')->getWebsitePath()."$pic")."/", '', basename($pic), 180);
            }
        }

        //series pattern
        $seriesStatus      = isset($data['seriesStatus']) ? intval($data['seriesStatus']) : 0;
        $seriesType        = isset($data['seriesType']) ? intval($data['seriesType']) : 0;
        $seriesIndependent = !empty($data['seriesIndependent']) ? 1 : 0;

        $seriesPatternCount             = 0;
        $seriesPatternWeekday           = 0;
        $seriesPatternDay               = 0;
        $seriesPatternWeek              = 0;
        $seriesPatternMonth             = 0;
        $seriesPatternType              = 0;
        $seriesPatternDouranceType      = 0;
        $seriesPatternEnd               = 0;
        $seriesExeptions                = '';
        $seriesPatternEndDate           = '0000-00-00 00:00:00';

        if($seriesStatus == 1) {
            if(!empty($data['seriesExeptions'])) {
                $exeptions = array();

                foreach($data['seriesExeptions'] as $key => $exeptionDate)  {
                    $exeptions[] = $this->getDbDateTimeFromIntern($this->getDateTime($exeptionDate, 23, 59))->format('Y-m-d');
                }

                sort($exeptions);

                $seriesExeptions = join(",", $exeptions);
            }

            switch($seriesType) {
                case 1;
                    if ($seriesStatus == 1) {
                        $seriesPatternType          = isset($data['seriesDaily']) ? intval($data['seriesDaily']) : 0;
                        if($seriesPatternType == 1) {
                            $seriesPatternWeekday   = 0;
                            $seriesPatternDay       = isset($data['seriesDailyDays']) ? intval($data['seriesDailyDays']) : 0;
                        } else {
                            $seriesPatternWeekday   = "1111100";
                            $seriesPatternDay       = 0;
                        }

                        $seriesPatternWeek          = 0;
                        $seriesPatternMonth         = 0;
                        $seriesPatternCount         = 0;
                    }
                break;
                case 2;
                    if ($seriesStatus == 1) {
                        $seriesPatternWeek          = isset($data['seriesWeeklyWeeks']) ? intval($data['seriesWeeklyWeeks']) : 0;

                        for($i=1; $i <= 7; $i++) {
                            if (isset($data['seriesWeeklyDays'][$i])) {
                                $weekdayPattern .= "1";
                            } else {
                                $weekdayPattern .= "0";
                            }
                        }

                        $seriesPatternWeekday       = $weekdayPattern;

                        $seriesPatternCount         = 0;
                        $seriesPatternDay           = 0;
                        $seriesPatternMonth         = 0;
                        $seriesPatternType          = 0;
                    }
                break;
                case 3;
                    if ($seriesStatus == 1) {
                        $seriesPatternType          = isset($data['seriesMonthly']) ? intval($data['seriesMonthly']) : 0;
                        if($seriesPatternType == 1) {
                            $seriesPatternMonth     = isset($data['seriesMonthlyMonth_1']) ? intval($data['seriesMonthlyMonth_1']) : 0;
                            $seriesPatternDay       = isset($data['seriesMonthlyDay']) ? intval($data['seriesMonthlyDay']) : 0;
                            $seriesPatternWeekday   = 0;
                        } else {
                            $seriesPatternCount     = isset($data['seriesMonthlyDayCount']) ? intval($data['seriesMonthlyDayCount']) : 0;
                            $seriesPatternMonth     = isset($data['seriesMonthlyMonth_2']) ? intval($data['seriesMonthlyMonth_2']) : 0;

                            if ($seriesPatternMonth < 1) {
                                // the increment must be at least once a month, otherwise we will end up in a endless loop in the presence
                                $seriesPatternMonth = 1;
                            }
                            $seriesPatternWeekday   = isset($data['seriesMonthlyWeekday']) ? $data['seriesMonthlyWeekday'] : '';
                            $seriesPatternDay       = 0;
                        }

                        $seriesPatternWeek           = 0;
                    }
                break;
            }

            $seriesPatternDouranceType  = isset($data['seriesDouranceType']) ? intval($data['seriesDouranceType']) : 0;
            switch($seriesPatternDouranceType) {
                case 1:
                    $seriesPatternEnd   = 0;
                break;
                case 2:
                    $seriesPatternEnd   = isset($data['seriesDouranceEvents']) ? intval($data['seriesDouranceEvents']) : 0;
                break;
                case 3:
                    $seriesPatternEndDate = $this->getDbDateTimeFromIntern($this->getDateTime($data['seriesDouranceDate'], 23, 59))->format('Y-m-d H:i:s');
                break;
            }
        }

        $formData = array(
            'type'                          => $type,
            'startdate'                     => $startDate,
            'enddate'                       => $endDate,
            'use_custom_date_display'       => $useCustomDateDisplay,
            'showStartDateList'             => $showStartDateList,
            'showEndDateList'               => $showEndDateList,
            'showStartTimeList'             => $showStartTimeList,
            'showEndTimeList'               => $showEndTimeList,
            'showTimeTypeList'              => $showTimeTypeList,
            'showStartDateDetail'           => $showStartDateDetail,
            'showEndDateDetail'             => $showEndDateDetail,
            'showStartTimeDetail'           => $showStartTimeDetail,
            'showEndTimeDetail'             => $showEndTimeDetail,
            'showTimeTypeDetail'            => $showTimeTypeDetail,
            'google'                        => $google,
            'access'                        => $access,
            'priority'                      => $priority,
            'price'                         => $price,
            'link'                          => $link,
            'pic'                           => $pic,
            'catid'                         => $catId,
            'attach'                        => $attach,
            'place_mediadir_id'             => $placeMediadir,
            'host_mediadir_id'              => $hostMediadir,
            'show_detail_view'              => $showDetailView,
            'show_in'                       => $showIn,
            'invited_groups'                => $invited_groups,
            'invited_mails'                 => $invited_mails,
            'invitation_email_template'     => json_encode($invitationTemplate),
            'registration'                  => $registration,
            'registration_form'             => $registration_form,
            'registration_num'              => $registration_num,
            'registration_notification'     => $registration_notification,
            'email_template'                => json_encode($email_template),
            'registration_external_link'    => $registrationExternalLink,
            'registration_external_fully_booked' => $registrationExternalFullyBooked,
            'ticket_sales'                  => $ticket_sales,
            'num_seating'                   => $num_seating,
            'series_status'                 => $seriesStatus,
            'series_type'                   => $seriesType,
            'series_pattern_count'          => $seriesPatternCount,
            'series_pattern_weekday'        => $seriesPatternWeekday,
            'series_pattern_day'            => $seriesPatternDay,
            'series_pattern_week'           => $seriesPatternWeek,
            'series_pattern_month'          => $seriesPatternMonth,
            'series_pattern_type'           => $seriesPatternType,
            'series_pattern_dourance_type'  => $seriesPatternDouranceType,
            'series_pattern_end'            => $seriesPatternEnd,
            'series_pattern_end_date'       => $seriesPatternEndDate,
            'series_pattern_exceptions'     => $seriesExeptions,
            'independent_series'            => $seriesIndependent,
            'all_day'                       => $allDay,
            'location_type'                 => $locationType,
            'host_type'                     => $hostType,
            'place_id'                      => 0,
            'place_street'                  => $street,
            'place_zip'                     => $zip,
            'place_website'                 => $placeWebsite,
            'place_link'                    => $placeLink,
            'place_phone'                   => $placePhone,
            'place_map'                     => $placeMap,
            'org_street'                    => $orgStreet,
            'org_zip'                       => $orgZip,
            'org_website'                   => $orgWebsite,
            'org_link'                      => $orgLink,
            'org_phone'                     => $orgPhone,
            'org_email'                     => $orgEmail,
            'invitation_sent'               => $update_invitation_sent ? 1 : 0,
        );

        if ($id != 0) {
            $query = \SQL::update("module_{$this->moduleTablePrefix}_event", $formData) ." WHERE id = '$id'";

            $objResult = $objDatabase->Execute($query);

            if ($objResult !== false) {
                $this->id = $id;
                $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_event_field
                                WHERE event_id = '".$id."'";

                $objResult = $objDatabase->Execute($query);

                $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_event_host
                                WHERE event_id = '".$id."'";

                $objResult = $objDatabase->Execute($query);
            } else {
                return false;
            }
        } else {
            $objFWUser  = \FWUser::getFWUserObject();
            $objUser    = $objFWUser->objUser;

            if ($objInit->mode == 'frontend') {
                $status    = 1;
                $confirmed = $this->arrSettings['confirmFrontendEvents'] == 1 ? 0 : 1;
                $author    = $objUser->login() ? intval($objUser->getId()) : 0;
            } else {
                $status    = 0;
                $confirmed = 1;
                $author    = intval($objUser->getId());
            }

            $formData['status']    = $status;
            $formData['confirmed'] = $confirmed;
            $formData['author']    = $author;

            $query = \SQL::insert("module_{$this->moduleTablePrefix}_event", $formData);

            $objResult = $objDatabase->Execute($query);

            if ($objResult !== false) {
                $id = intval($objDatabase->Insert_ID());
                $this->id = $id;
            } else {
                return false;
            }
        }

        if($id != 0) {
            foreach ($data['showIn'] as $key => $langId) {
                $title = contrexx_addslashes(contrexx_strip_tags($data['title'][$langId]));
                $teaser = contrexx_addslashes(contrexx_strip_tags($data['teaser'][$langId]));
                $description = contrexx_addslashes($data['description'][$langId]);
                if ($convertBBCode) {
                    $description = \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForDb($data['description'][$langId], true);
                }
                $redirect = contrexx_addslashes($data['calendar-redirect'][$langId]);

                if($type == 0) {
                    $redirect = '';
                } else {
                    $description = '';
                }
                if (!empty($data['event_place'][$langId])) {
                    $place = contrexx_input2db($data['event_place'][$langId]);
                } else {
                    $place = contrexx_input2db($data['event_place'][0]);
                }
                if (!empty($data['event_place_city'][$langId])) {
                    $placeCity = contrexx_input2db($data['event_place_city'][$langId]);
                } else {
                    $placeCity = contrexx_input2db($data['event_place_city'][0]);
                }
                if (!empty($data['event_place_country'][$langId])) {
                    $placeCountry = contrexx_input2db($data['event_place_country'][$langId]);
                } else {
                    $placeCountry = contrexx_input2db($data['event_place_country'][0]);
                }
                if (!empty($data['event_org_name'][$langId])) {
                    $orgName = contrexx_input2db($data['event_org_name'][$langId]);
                } else {
                    $orgName = contrexx_input2db($data['event_org_name'][0]);
                }
                if (!empty($data['event_org_city'][$langId])) {
                    $orgCity = contrexx_input2db($data['event_org_city'][$langId]);
                } else {
                    $orgCity = contrexx_input2db($data['event_org_city'][0]);
                }
                if (!empty($data['event_org_country'][$langId])) {
                    $orgCountry = contrexx_input2db($data['event_org_country'][$langId]);
                } else {
                    $orgCountry = contrexx_input2db($data['event_org_country'][0]);
                }

                $query = 'INSERT INTO
                            `' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_event_field`
                          SET
                            `event_id`      = ' . contrexx_input2int($id) . ',
                            `lang_id`       = ' . contrexx_input2int($langId) . ',
                            `title`         = "' . $title . '",
                            `teaser`        = "' . $teaser . '",
                            `description`   = "' . $description . '",
                            `redirect`      = "' . $redirect . '",
                            `place`         = "' . $place . '",
                            `place_city`    = "' . $placeCity . '",
                            `place_country` = "' . $placeCountry . '",
                            `org_name`      = "' . $orgName . '",
                            `org_city`      = "' . $orgCity . '",
                            `org_country`   = "' . $orgCountry . '"
                ';

                $objResult = $objDatabase->Execute($query);

                if ($objResult === false) {
                    return false;
                }
            }

            if(!empty($related_hosts)) {
                foreach ($related_hosts as $key => $hostId) {
                    $query = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_event_host
                                      (`host_id`,`event_id`)
                               VALUES ('".intval($hostId)."','".intval($id)."')";

                    $objResult = $objDatabase->Execute($query);
                }
            }
        }

        if ($send_invitation == 1) {
            // TO-DO set form data into $this
            $event          = new CalendarEvent($this->id);
            $objMailManager = new \Cx\Modules\Calendar\Controller\CalendarMailManager();
            foreach ($invitationTemplate as $templateId) {
                $objMailManager->sendMail($event, \Cx\Modules\Calendar\Controller\CalendarMailManager::MAIL_INVITATION, null, $templateId);
            }
        }

        return true;
    }

    function loadEventFromPost($data)
    {
        list($startDate, $strStartTime) = explode(' ', $data['startDate']);
        list($startHour, $startMin)     = explode(':', $strStartTime);

        list($endDate, $strEndTime)     = explode(' ', $data['endDate']);
        list($endHour, $endMin)         = explode(':', $strEndTime);

        //event data
        $startDate     = $this->getDateTime($startDate, intval($startHour), intval($startMin));
        $endDate       = $this->getDateTime($endDate, intval($endHour), intval($endMin));

        $this->startDate = $startDate;
        $this->endDate   = $endDate;

        //series pattern
        $seriesStatus = isset($data['seriesStatus']) ? intval($data['seriesStatus']) : 0;
        $seriesType   = isset($data['seriesType']) ? intval($data['seriesType']) : 0;

        $seriesPatternCount             = 0;
        $seriesPatternWeekday           = 0;
        $seriesPatternDay               = 0;
        $seriesPatternWeek              = 0;
        $seriesPatternMonth             = 0;
        $seriesPatternType              = 0;
        $seriesPatternDouranceType      = 0;
        $seriesPatternEnd               = 0;
        $seriesExeptions = '';

        if($seriesStatus == 1) {

            switch($seriesType) {
                case 1;
                    if ($seriesStatus == 1) {
                        $seriesPatternType          = isset($data['seriesDaily']) ? intval($data['seriesDaily']) : 0;
                        if($seriesPatternType == 1) {
                            $seriesPatternWeekday   = 0;
                            $seriesPatternDay       = isset($data['seriesDailyDays']) ? intval($data['seriesDailyDays']) : 0;
                        } else {
                            $seriesPatternWeekday   = "1111100";
                            $seriesPatternDay       = 0;
                        }

                        $seriesPatternWeek          = 0;
                        $seriesPatternMonth         = 0;
                        $seriesPatternCount         = 0;
                    }
                break;
                case 2;
                    if ($seriesStatus == 1) {
                        $seriesPatternWeek          = isset($data['seriesWeeklyWeeks']) ? intval($data['seriesWeeklyWeeks']) : 0;

                        $weekdayPattern = '';
                        for($i=1; $i <= 7; $i++) {
                            if (isset($data['seriesWeeklyDays'][$i])) {
                                $weekdayPattern .= "1";
                            } else {
                                $weekdayPattern .= "0";
                            }
                        }

                        // To DO: not correct to set day to monday
                        $seriesPatternWeekday       = (int) $weekdayPattern == 0 ? '1000000' : $weekdayPattern;

                        $seriesPatternCount         = 0;
                        $seriesPatternDay           = 0;
                        $seriesPatternMonth         = 0;
                        $seriesPatternType          = 0;
                    }
                break;
                case 3;
                    if ($seriesStatus == 1) {
                        $seriesPatternType          = isset($data['seriesMonthly']) ? intval($data['seriesMonthly']) : 0;
                        if($seriesPatternType == 1) {
                            $seriesPatternMonth     = isset($data['seriesMonthlyMonth_1']) ? intval($data['seriesMonthlyMonth_1']) : 0;
                            $seriesPatternDay       = isset($data['seriesMonthlyDay']) ? intval($data['seriesMonthlyDay']) : 0;
                            $seriesPatternWeekday   = 0;
                        } else {
                            $seriesPatternCount     = isset($data['seriesMonthlyDayCount']) ? intval($data['seriesMonthlyDayCount']) : 0;
                            $seriesPatternMonth     = isset($data['seriesMonthlyMonth_2']) ? intval($data['seriesMonthlyMonth_2']) : 0;

                            if ($seriesPatternMonth < 1) {
                                // the increment must be at least once a month, otherwise we will end up in a endless loop in the presence
                                $seriesPatternMonth = 1;
                            }
                            $seriesPatternWeekday   = isset($data['seriesMonthlyWeekday']) ? $data['seriesMonthlyWeekday'] : '';
                            $seriesPatternDay       = 0;
                        }

                        $seriesPatternWeek           = 0;
                    }
                break;
            }

            $seriesPatternDouranceType  = isset($data['seriesDouranceType']) ? intval($data['seriesDouranceType']) : 0;
            $seriesPatternEndDate = new \DateTime();
            switch($seriesPatternDouranceType) {
                case 1:
                    $seriesPatternEnd   = 0;
                break;
                case 2:
                    $seriesPatternEnd   = isset($data['seriesDouranceEvents']) ? intval($data['seriesDouranceEvents']) : 0;
                break;
                case 3:
                    $seriesPatternEndDate = $this->getDateTime($data['seriesDouranceDate'], 0, 0);
                break;
            }
        }

        $this->seriesData['seriesPatternCount'] = intval($seriesPatternCount);
        $this->seriesData['seriesType'] = intval($seriesType);
        $this->seriesData['seriesPatternCount'] = intval($seriesPatternCount);
        $this->seriesData['seriesPatternWeekday'] = htmlentities($seriesPatternWeekday, ENT_QUOTES, CONTREXX_CHARSET);
        $this->seriesData['seriesPatternDay'] = intval($seriesPatternDay);
        $this->seriesData['seriesPatternWeek'] = intval($seriesPatternWeek);
        $this->seriesData['seriesPatternMonth'] = intval($seriesPatternMonth);
        $this->seriesData['seriesPatternType'] = intval($seriesPatternType);
        $this->seriesData['seriesPatternDouranceType'] = intval($seriesPatternDouranceType);
        $this->seriesData['seriesPatternEnd'] = intval($seriesPatternEnd);
        $this->seriesData['seriesPatternEndDate'] = $seriesPatternEndDate;
        $this->seriesData['seriesPatternBegin'] = 0;
        $this->seriesData['seriesPatternExceptions'] = array();

    }

    /**
     * Delete the event
     *
     * @return boolean true if deleted successfully, false otherwise
     */
    function delete(){
        global $objDatabase;

        $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_event
                   WHERE id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_event_field
                            WHERE event_id = '".intval($this->id)."'";

            $objResult = $objDatabase->Execute($query);
            if ($objResult !== false) {
                $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_event_host
                                WHERE event_id = '".intval($this->id)."'";

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
     * Export the Event with calendar and stop excuting script
     *
     * @return null
     */
    function export(){
        global $_CONFIG;

        //create new calendar
        $objVCalendar = new \vcalendar();
        $objVCalendar->setConfig('unique_id', $_CONFIG['coreGlobalPageTitle']);
        $objVCalendar->setConfig('filename', urlencode($this->title).'.ics'); // set Your unique id
        //$v->setProperty('X-WR-CALNAME', 'Calendar Sample');
        //$v->setProperty('X-WR-CALDESC', 'Calendar Description');
        //$v->setProperty('X-WR-TIMEZONE', 'America/Los_Angeles');
        $objVCalendar->setProperty('X-MS-OLK-FORCEINSPECTOROPEN', 'TRUE');
        $objVCalendar->setProperty('METHOD','PUBLISH');

        // create an event calendar component
        $objVEvent = new \vevent();

        // start
        $startDate   = $this->getUserDateTimeFromIntern($this->startDate);
        $objVEvent->setProperty(
            'dtstart',
            array(
                'year'  => $startDate->format('Y'),
                'month' => $startDate->format('m'),
                'day'   => $startDate->format('d'),
                'hour'  => $startDate->format('H'),
                'min'   => $startDate->format('i'),
                'sec'   => 0
            )
        );

        // end
        $endDate   = $this->getUserDateTimeFromIntern($this->endDate);
        $objVEvent->setProperty(
            'dtend',
            array(
                'year'  => $endDate->format('Y'),
                'month' => $endDate->format('m'),
                'day'   => $endDate->format('d'),
                'hour'  => $endDate->format('H'),
                'min'   => $endDate->format('i'),
                'sec'   => 0
            )
        );

        // place
        if(!empty($this->place)) {
            $objVEvent->setProperty( 'location', html_entity_decode($this->place, ENT_QUOTES, CONTREXX_CHARSET));
        }

        // title
        $objVEvent->setProperty( 'summary', html_entity_decode($this->title, ENT_QUOTES, CONTREXX_CHARSET));

        // description
        $objVEvent->setProperty( 'description', html_entity_decode(strip_tags($this->description), ENT_QUOTES, CONTREXX_CHARSET));

        // organizer
        $objVEvent->setProperty( 'organizer' , $_CONFIG['coreGlobalPageTitle'].' <'.$_CONFIG['coreAdminEmail'].'>');

        // comment
        //$objVEvent->setProperty( 'comment', 'This is a comment' );

        // attendee
        //$objVEvent->setProperty( 'attendee', 'attendee1@icaldomain.net' );

        // ressourcen
        //$objVEvent->setProperty( 'resources', 'COMPUTER PROJECTOR' );

        // series type
        //$objVEvent->setProperty( 'rrule', array( 'FREQ' => 'WEEKLY', 'count' => 4));// weekly, four occasions

        // add event to calendar
        $objVCalendar->setComponent ($objVEvent);

        $objVCalendar->returnCalendar();
        exit;
    }

    /**
     * switch status of the event
     *
     * @return boolean true if status updated, false otherwise
     */
    function switchStatus(){
        global $objDatabase;

        if($this->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_event AS event
                     SET event.status = '".intval($status)."'
                   WHERE event.id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * confirm event
     *
     * @return boolean true if event confirmed, false otherwise
     */
    function confirm(){
        global $objDatabase;

        $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_event AS event
                     SET event.confirmed = '1'
                   WHERE event.id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Handle the calendar image upload
     *
     * @param string $id uploaderId
     *
     * @return string image path
     */
    function _handleUpload($id)
    {
        global $sessionObj;

        if (!isset($sessionObj)) $sessionObj = \cmsSession::getInstance();
        $tmpUploadDir     = $_SESSION->getTempPath().'/'.$id.'/'; //all the files uploaded are in here
        $depositionTarget = $this->uploadImgPath; //target folder
        $pic              = '';

        //move all files
        if(!\Cx\Lib\FileSystem\FileSystem::exists($tmpUploadDir)) {
            return $pic;
        }

        $h = opendir($tmpUploadDir);
        if ($h) {
            while(false !== ($f = readdir($h))) {
                // skip folders and thumbnails
                if($f == '..' || $f == '.' || preg_match("/(?:\.(?:thumb_thumbnail|thumb_medium|thumb_large)\.[^.]+$)|(?:\.thumb)$/i", $f)) {
                    continue;
                }

                //do not overwrite existing files.
                $prefix = '';
                while (file_exists($depositionTarget.$prefix.$f)) {
                    if (empty($prefix)) {
                        $prefix = 0;
                    }
                    $prefix ++;
                }

                // move file
                try {
                    $objFile = new \Cx\Lib\FileSystem\File($tmpUploadDir.$f);
                    $fileInfo = pathinfo($tmpUploadDir.$f);
                    $objFile->move($depositionTarget.$prefix.$f, false);

                    $imageName = $prefix.$f;
                    if (in_array($fileInfo['extension'], array('gif', 'jpg', 'jpeg', 'png'))) {
                        $objImage = new \ImageManager();
                        $objImage->_createThumb($this->uploadImgPath, $this->uploadImgWebPath, $imageName, 180);
                    }
                    $pic = contrexx_input2raw($this->uploadImgWebPath.$imageName);

                    // abort after one file has been fetched, as all event upload
                    // fields do allow a single file only anyway
                    break;
                } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                    \DBG::msg($e->getMessage());
                }
            }
        }

        return $pic;
    }

    /**
     * Used get the event search query
     * From global search module.
     *
     * @param mixed $term Search term
     *
     * @return string search query
     */
    static function getEventSearchQuery($term)
    {
        global $_LANGID;

        $query = "SELECT event.`id` AS `id`,
                         event.`startdate`,
                         field.`title` AS `title`,
                         field.`teaser` AS `teaser`,
                         field.`description` AS content,
                         field.`place` AS place,
                         MATCH (field.`title`, field.`teaser`, field.`description`) AGAINST ('%$term%') AS `score`
                    FROM ".DBPREFIX."module_calendar_event AS event,
                         ".DBPREFIX."module_calendar_event_field AS field
                   WHERE   (event.id = field.event_id AND field.lang_id = '".intval($_LANGID)."')
                       AND event.status = 1
                       AND (   field.title LIKE ('%$term%')
                            OR field.teaser LIKE ('%$term%')
                            OR field.description LIKE ('%$term%')
                            OR field.place LIKE ('%$term%')
                           )";

        return $query;
    }

    /**
     * Loads the location fields from the selected media directory entry
     *
     * @param integer $intMediaDirId  media directory Entry id
     * @param string  $type           place type
     *                                availble options are place or host
     * @return null   it loads the place values based on the media directory Entry id and type
     */
    function loadPlaceFromMediadir($intMediaDirId = 0, $type = 'place')
    {
        $place         = '';
        $place_street  = '';
        $place_zip     = '';
        $place_city    = '';
        $place_country = '';
        $place_website = '';
        $place_phone   = '';

        if (!empty($intMediaDirId)) {
            $objMediadirEntry = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry('MediaDir');
            $objMediadirEntry->getEntries(intval($intMediaDirId));
            //get inputfield object
            $objInputfields = new \Cx\Modules\MediaDir\Controller\MediaDirectoryInputfield($objMediadirEntry->arrEntries[$intMediaDirId]['entryFormId'],false,$objMediadirEntry->arrEntries[$intMediaDirId]['entryTranslationStatus'], 'MediaDir');

            foreach ($objInputfields->arrInputfields as $arrInputfield) {

                $intInputfieldType = intval($arrInputfield['type']);
                if ($intInputfieldType != 16 && $intInputfieldType != 17) {
                    if(!empty($arrInputfield['type'])) {
                        $strType = $arrInputfield['type_name'];
                        $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield".ucfirst($strType);
                        try {
                            $objInputfield = \Cx\Modules\MediaDir\Controller\safeNew($strInputfieldClass,'MediaDir');

                            if(intval($arrInputfield['type_multi_lang']) == 1) {
                                $arrInputfieldContent = $objInputfield->getContent($intMediaDirId, $arrInputfield, $objMediadirEntry->arrEntries[$intMediaDirId]['entryTranslationStatus']);
                            } else {
                                $arrInputfieldContent = $objInputfield->getContent($intMediaDirId, $arrInputfield, null);
                            }

                            switch ($arrInputfield['context_type']) {
                                case 'title':
                                    $place = end($arrInputfieldContent);
                                    break;
                                case 'address':
                                    $place_street = end($arrInputfieldContent);
                                    break;
                                case 'zip':
                                    $place_zip = end($arrInputfieldContent);
                                    break;
                                case 'city':
                                    $place_city = end($arrInputfieldContent);
                                    break;
                                case 'country':
                                    $place_country = end($arrInputfieldContent);
                                    break;
                                case 'website':
                                    $place_website = end($arrInputfieldContent);
                                    break;
                                case 'phone':
                                    $place_phone = end($arrInputfieldContent);
                                    break;
                            }

                        } catch (Exception $error) {
                            echo "Error: ".$error->getMessage();
                        }
                    }
                }
            }
        }

        if ($type == 'place') {
            $this->place         = $place;
            $this->place_street  = $place_street;
            $this->place_zip     = $place_zip;
            $this->place_city    = $place_city;
            $this->place_country = $place_country;
            $this->place_website = $place_website;
            $this->place_phone   = $place_phone;
            $this->place_map     = '';
        } else {
            $this->org_name   = $place;
            $this->org_street = $place_street;
            $this->org_zip    = $place_zip;
            $this->org_city   = $place_city;
            $this->org_country= $place_country;
            $this->org_website= $place_website;
            $this->org_phone  = $place_phone;
            $this->org_email  = '';
        }

    }

    /**
     * Return event place url and its source link
     *
     * @return array place url and its source link
     */
    function loadPlaceLinkFromMediadir($intMediaDirId = 0, $type = 'place')
    {
        global $_LANGID, $_CONFIG;

        $placeUrl       = '';
        $placeUrlSource = '';

        if (!empty($intMediaDirId)) {
            $objMediadirEntry = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry('MediaDir');
            $objMediadirEntry->getEntries(intval($intMediaDirId));

            $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $pages = $pageRepo->findBy(array(
                'cmd'    => contrexx_addslashes('detail'.intval($objMediadirEntry->arrEntries[$intMediaDirId]['entryFormId'])),
                'lang'   => $_LANGID,
                'type'   => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
                'module' => 'MediaDir',
            ));

            if(count($pages)) {
                $strDetailCmd = 'detail'.intval($objMediadirEntry->arrEntries[$intMediaDirId]['entryFormId']);
            } else {
                $strDetailCmd = 'detail';
            }

            $pages = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->getFromModuleCmdByLang('MediaDir', $strDetailCmd);

            $arrActiveFrontendLanguages = \FWLanguage::getActiveFrontendLanguages();
            if (isset($arrActiveFrontendLanguages[FRONTEND_LANG_ID]) && isset($pages[FRONTEND_LANG_ID])) {
                $langId = FRONTEND_LANG_ID;
            } else if (isset($arrActiveFrontendLanguages[BACKEND_LANG_ID]) && isset($pages[BACKEND_LANG_ID])) {
                $langId = BACKEND_LANG_ID;
            } else {
                foreach ($arrActiveFrontendLanguages as $lang) {
                    if (isset($pages[$lang['id']])) {
                        $langId = $lang['id'];
                        break;
                    }
                }
            }

            $url = $pages[$langId]->getUrl(ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET, "?eid={$intMediaDirId}");

            $place          = ($type = 'place') ? $this->place : $this->org_name;
            $placeUrl       = "<a href='".$url."' target='_blank' >". (!empty($place) ? $place : $url) ."</a>";
            $placeUrlSource = $url;
        }

        return array($placeUrl, $placeUrlSource);
    }

    /**
     * Returns the number of free place of the event
     * This will include the seating field of the registration form
     *
     * @return integer
     */
    public function getFreePlaces()
    {
        $this->calculateRegistrationCount();
        return $this->freePlaces;
    }

    /**
     * Returns the registration count of the event
     *
     * @return integer
     */
    public function getRegistrationCount()
    {
        $this->calculateRegistrationCount();
        return $this->registrationCount;
    }

    /**
     * Returns the waitlist registration count of the event
     *
     * @return integer
     */
    public function getWaitlistCount()
    {
        $this->calculateRegistrationCount();
        return $this->waitlistCount;
    }

    /**
     * Returns the cancelled registration count of the event
     *
     * @return integer
     */
    public function getCancellationCount()
    {
        $this->calculateRegistrationCount();
        return $this->cancellationCount;
    }

    /**
     * Calculate the registration count (register, deregister, waitlist) of the event
     *
     * @staticvar boolean $calculated   Flag to check whether the registration is already
     *                                  calculated or not.
     * @return null
     */
    protected function calculateRegistrationCount()
    {
        global $objDatabase, $objInit, $_LANGID;

        if ($this->registrationCalculated) {
            return;
        }

        $isIndependentSeries = $this->seriesStatus && $this->independentSeries;

        $filterEventTime = '';
        if ($objInit->mode != 'backend' && $isIndependentSeries) {
            $filterEventTime = ' AND r.`date` = '. $this->startDate->getTimestamp();
        }

        $queryCountRegistration = 'SELECT
                                        COUNT(1) AS numSubscriber,
                                        r.`type`
                                    FROM
                                        `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration` AS `r`
                                    WHERE
                                        r.`event_id` = '. contrexx_input2int($this->id) .'
                                        '. $filterEventTime .'
                                    GROUP BY
                                        r.`type`';
        $objCountRegistration = $objDatabase->Execute($queryCountRegistration);

        if ($objCountRegistration) {
            while (!$objCountRegistration->EOF) {
                switch ($objCountRegistration->fields['type']) {
                    case 1:
                        $this->registrationCount = (int) $objCountRegistration->fields['numSubscriber'];
                        break;
                    case 2:
                        $this->waitlistCount = (int) $objCountRegistration->fields['numSubscriber'];
                        break;
                    case 0:
                        $this->cancellationCount = (int) $objCountRegistration->fields['numSubscriber'];
                        break;
                }
                $objCountRegistration->MoveNext();
            }
        }

        $seatingOption = $objDatabase->getOne('
            SELECT
                `fn`.`default` AS `seating_option`
            FROM
                `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form` AS `f`
            INNER JOIN
                `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field` AS `ff`
            ON
                `f`.`id` = `ff`.`form`
            INNER JOIN
                `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name` AS `fn`
            ON
                `ff`.`id` = `fn`.`field_id`
            WHERE
                `f`.`id` = '. contrexx_input2int($this->registrationForm) .'
            AND
                `ff`.`type` = "seating"
            ORDER BY CASE `fn`.lang_id
                WHEN '. $_LANGID .' THEN 1
                ELSE 2
                END
        ');

        $reservedSeating = 0;
        if ($seatingOption) {
            $seatingOptionArray = explode(',', $seatingOption);
            $queryRegistrations = '
                SELECT `v`.`value` AS `reserved_seating`
                FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value` AS `v`
                INNER JOIN `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration` AS `r`
                ON `v`.`reg_id` = `r`.`id`
                INNER JOIN `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field` AS `f`
                ON `v`.`field_id` = `f`.`id`
                WHERE `r`.`event_id` = '. contrexx_input2int($this->id) .'
                    '. $filterEventTime .'
                AND `r`.`type` = 1
                AND `f`.`type` = "seating"
            ';
            $objResultRegistrations = $objDatabase->Execute($queryRegistrations);
            if ($objResultRegistrations !== false && $objResultRegistrations->RecordCount()) {
                while (!$objResultRegistrations->EOF) {
                    $selectedSeat     = contrexx_input2int($objResultRegistrations->fields['reserved_seating']) - 1;
                    $reservedSeating += !empty($seatingOptionArray[$selectedSeat]) ? $seatingOptionArray[$selectedSeat] : 1;
                    $objResultRegistrations->MoveNext();
                }
            }
        } else {
            $reservedSeating = $this->registrationCount;
        }

        $freePlaces = intval($this->numSubscriber - $reservedSeating);
        $this->freePlaces = $freePlaces < 0 ? 0 : $freePlaces;

        $this->registrationCalculated = true;
    }

    /**
     * Reset the registration count values.
     */
    public function resetRegistrationCount()
    {
        $this->registrationCount = 0;
        $this->waitlistCount     = 0;
        $this->cancellationCount = 0;
        $this->freePlaces        = 0;
    }

    /**
     * Get unique identifier of event
     *
     * Note: Event reocurrences do share the same unique identifier
     *
     * @return  integer ID of event
     */
    public function getId() {
        return $this->id;
    }

    /**
     * PHP clone, clone the start and end dates on clone
     */
    public function __clone()
    {
        $this->startDate = clone $this->startDate;
        $this->endDate   = clone $this->endDate;
        if ($this->seriesStatus && $this->independentSeries) {
            $this->registrationCalculated = false;
            $this->resetRegistrationCount();
        }
    }
}
