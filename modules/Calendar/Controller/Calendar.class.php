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
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class Calendar extends CalendarLibrary
{
    /**
     * Event manager object
     *
     * @var object
     */
    private $objEventManager;

    /**
     * Start date
     *
     * Unix timestamp
     *
     * @var integer
     */
    private $startDate;

    /**
     * End date
     * Unix timestamp
     *
     * @var integer
     */
    private $endDate;

    /**
     * Category id
     *
     * @var integer
     */
    private $categoryId;

    /**
     * Search term
     *
     * @var string
     */
    private $searchTerm;

    /**
     * Need authorization
     *
     * @var boolean
     */
    private $needAuth;

    /**
     * Start position
     *
     * @var integer
     */
    private $startPos;

    /**
     * Number of events per  page
     *
     * @var integer
     */
    private $numEvents;

    /**
     * Author name
     *
     * @var string
     */
    private $author;

    /**
     * Sort direction
     *
     * @var string
     */
    private $sortDirection = 'ASC';

    /**
     * APge Title
     *
     * @var string
     */
    public $pageTitle;

    /**
     * meta title
     *
     * @var string
     */
    public $metaTitle;

    /**
     * An id unique per form submission and user.
     * This means an user can submit the same form twice at the same time,
     * and the form gets a different submission id for each submit.
     * @var integer
     */
    protected $submissionId = 0;

    /**
     * Event Box count
     *
     * @var integer
     */
    public $boxCount = 3;

    /**
     * When using the ID of a category, we will
     * simulate as if cmd=category has been requested
     *
     * @var boolean
     */
    protected $simulateCategoryView = false;

    /**
     * Constructor
     *
     * @global array $_ARRAYLANG
     * @global object $objTemplate
     * @param string $pageContent
     */
    function __construct($pageContent)
    {
        global $_ARRAYLANG, $objTemplate;

        parent::__construct('.');
        $this->getSettings();

        $this->pageContent = $pageContent;
    }

    /**
     * Performs the calendar page
     *
     * @return null
     */
    function getCalendarPage()
    {
        $this->loadEventManager();
        $id = !empty($_GET['id']) ? $_GET['id'] : 0 ;

        if(isset($_GET['export'])) {
            $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent(intval($_GET['export']));
            $objEvent->export();
        }

        $cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : null;
        if ($this->simulateCategoryView) {
            $cmd = 'category';
        }

        switch ($cmd) {
            case 'detail':
                if( $id!= null && $_GET['date'] != null) {
                    self::showEvent();
                } else {
                    \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName));
                    exit();
                }
                break;
            case 'register':
            case 'sign':
                self::showRegistrationForm();
                break;
            case 'boxes':
                if (isset($_GET['act']) && $_GET['act'] == "list") {
                    self::boxesEventList();
                } else {
                    self::showThreeBoxes();
                }
                break;
            case 'category':
                self::showCategoryView();
                break;
            case 'add':
                $this->checkAccess('add_event');
                self::modifyEvent();
                break;
            case 'edit':
                $this->checkAccess('edit_event');
                self::modifyEvent(intval($id));
                break;
            case 'my_events':
                $this->checkAccess('my_events');
                self::myEvents();
                break;
            case 'success':
                self::showSuccessPage();
                break;
            case 'list':
            case 'eventlist':
            case 'archive':
            default:
                self::overview();
                break;
        }

        return $this->_objTpl->get();
    }

    /**
     * Loads the event manager
     *
     * @return null
     */
    function loadEventManager()
    {
        $term   = isset($_GET['term']) ? contrexx_input2raw($_GET['term']) : '';
        $from   = isset($_GET['from']) ? contrexx_input2raw($_GET['from']) : '';
        $till   = isset($_GET['till']) ? contrexx_input2raw($_GET['till']) : '';
        $catid  = isset($_GET['catid']) ? contrexx_input2raw($_GET['catid']) : '';
        $cmd    = isset($_GET['cmd']) ? contrexx_input2raw($_GET['cmd']) : '';

        // get startdate
        if (!empty($from)) {
            $this->startDate = $this->getDateTime($from);
        } else if ($cmd == 'archive') {
            $this->startDate = null;
            $this->sortDirection = 'DESC';
        } else {
            $this->startDate = new \DateTime();

            $startDay   = isset($_GET['day']) ? $_GET['day'] : $this->startDate->format('d');
            $startMonth = isset($_GET['month']) ? $_GET['month'] : $this->startDate->format('m');
            $startYear  = isset($_GET['year']) ? $_GET['year'] : $this->startDate->format('Y');

            $this->startDate->setDate($startYear, $startMonth, $startDay);
            $this->startDate->setTime(0, 0, 0);
        }

        // get enddate
        if (!empty($till)) {
            $this->endDate = $this->getDateTime($till);
        } else if ($cmd == 'archive') {
            $this->endDate = new \DateTime();
        } else {
            $this->endDate = new \DateTime();

            $endDay   = isset($_GET['endDay']) ? $_GET['endDay'] : $this->endDate->format('d');
            $endMonth = isset($_GET['endMonth']) ? $_GET['endMonth'] : $this->endDate->format('m');
            $endYear  = isset($_GET['endYear']) ? $_GET['endYear'] : $this->endDate->format('Y');

            $endYear = empty($_GET['endYear']) && empty($_GET['endMonth']) ? $endYear+10: $endYear;

            $this->endDate->setDate($endYear, $endMonth, $endDay);
            $this->endDate->setTime(23, 59, 59);
        }


        // get datepicker-time
        if ((isset($_REQUEST["yearID"]) ||  isset($_REQUEST["monthID"]) || isset($_REQUEST["dayID"])) && $cmd != 'boxes') {

            $this->startDate = new \DateTime();
            $year  = isset($_REQUEST["yearID"]) ? (int) $_REQUEST["yearID"] : $this->startDate->format('Y');
            $month = isset($_REQUEST["monthID"]) ? (int) $_REQUEST["monthID"] : $this->startDate->format('m');
            $day   = isset($_REQUEST["dayID"]) ? (int) $_REQUEST["dayID"] : $this->startDate->format('d');

            $this->startDate->setDate($year, $month, $day);
            $this->startDate->modify("first day of this month");
            $this->startDate->setTime(0, 0, 0);

            $this->endDate = clone $this->startDate;
            // add months for the list view(month view)
            if ((empty($_GET['act']) || $_GET['act'] != 'list') && empty($_REQUEST['dayID'])) {
                $this->endDate->modify("+{$this->boxCount} months");
            }

            $this->endDate->modify("last day of this month");
            $this->endDate->setTime(23, 59, 59);
        } elseif (isset ($_GET["yearID"]) && isset ($_GET["monthID"]) && isset ($_GET["dayID"])) {
            $this->startDate = new \DateTime();

            $year  = isset($_REQUEST["yearID"]) ? (int) $_REQUEST["yearID"] : $this->startDate->format('Y');
            $month = isset($_REQUEST["monthID"]) ? (int) $_REQUEST["monthID"] : $this->startDate->format('m');
            $day   = isset($_REQUEST["dayID"]) ? (int) $_REQUEST["dayID"] : $this->startDate->format('d');

            $this->startDate->setDate($year, $month, $day);
            $this->startDate->setTime(0, 0, 0);
            $this->endDate   = clone $this->startDate;
            $this->endDate->setTime(23, 59, 59);
        }

        // In case $_GET['cmd'] is an integer, then we shall treat it as the
        // ID of a category and switch to category-mode
        if (!empty($cmd) && (string)intval($cmd) == $cmd) {
            $catid = intval($cmd);
            $cmd == 'category';
            $this->simulateCategoryView = true;
        }

        $this->searchTerm = !empty($term) ? contrexx_raw2db($term) : null;
        $this->categoryId = !empty($catid) ? intval($catid) : null;

        if ($cmd == 'boxes' || $cmd == 'category') {
            $this->startPos = 0;
            $this->numEvents = 'n';
        } else if(!isset($_GET['search']) && ($cmd != 'list' && $cmd != 'eventlist' && $cmd != 'archive')) {
            $this->startPos = 0;
            $this->numEvents = $this->arrSettings['numEntrance'];
        } else {
            $this->startPos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
            $this->numEvents = $this->arrSettings['numPaging'];
        }

        if ($cmd == 'detail') {
            $this->startDate = null;
            $this->numEvents = 'n';
        }

        if ($cmd == 'my_events') {
            $objFWUser = \FWUser::getFWUserObject();
            $objUser = $objFWUser->objUser;
            $this->author = intval($objUser->getId());
        } else {
            $this->author = null;
        }
        $this->objEventManager = new \Cx\Modules\Calendar\Controller\CalendarEventManager($this->startDate,$this->endDate,$this->categoryId,$this->searchTerm,true,$this->needAuth,true,$this->startPos,$this->numEvents,$this->sortDirection,true,$this->author);

        if (!in_array($cmd, array('detail', 'register'))) {
            $this->objEventManager->getEventList();
        } else {
            /* if($_GET['external'] == 1 && $this->arrSettings['publicationStatus'] == 1) {
                $this->objEventManager->getExternalEvent(intval($_GET['id']), intval($_GET['date']));
            } else { */
                $eventId = isset($_REQUEST['id']) ? contrexx_input2int($_REQUEST['id']) : 0;
                $date    = isset($_REQUEST['date']) ? contrexx_input2int($_REQUEST['date']) : 0;

                $this->objEventManager->getEvent($eventId, $date);
            /* } */
        }
    }

    /**
     * performs the overview page
     *
     * @return null
     */
    function overview()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $this->getSettings();

        $dateFormat = $this->getDateFormat(1);

        $javascript = <<< EOF
<script language="JavaScript" type="text/javascript">

cx.ready(function() {
    var options = {
        dateFormat: '$dateFormat',
        timeFormat: 'hh:mm'
    };
    cx.jQuery('input[name=from]').datepicker(options);
    cx.jQuery('input[name=till]').datepicker(options);
});

</script>
EOF;
        $objCategoryManager = new \Cx\Modules\Calendar\Controller\CalendarCategoryManager(true);
        $objCategoryManager->getCategoryList();

        $term   = isset($_GET['term']) ? contrexx_input2raw($_GET['term']) : '';
        $from   = isset($_GET['from']) ? contrexx_input2raw($_GET['from']) : '';
        $till   = isset($_GET['till']) ? contrexx_input2raw($_GET['till']) : '';
        $catid  = isset($_GET['catid']) ? contrexx_input2raw($_GET['catid']) : '';
        $search = isset($_GET['search']) ? contrexx_input2raw($_GET['search']) : '';
        $cmd    = isset($_GET['cmd']) ? contrexx_input2raw($_GET['cmd']) : '';
        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_SEARCH_TERM' =>  $_ARRAYLANG['TXT_CALENDAR_KEYWORD'],
            'TXT_'.$this->moduleLangVar.'_FROM' =>  $_ARRAYLANG['TXT_CALENDAR_FROM'],
            'TXT_'.$this->moduleLangVar.'_TILL' =>  $_ARRAYLANG['TXT_CALENDAR_TILL'],
            'TXT_'.$this->moduleLangVar.'_CATEGORY' =>  $_ARRAYLANG['TXT_CALENDAR_CAT'],
            'TXT_'.$this->moduleLangVar.'_SEARCH' =>  $_ARRAYLANG['TXT_CALENDAR_SEARCH'],
            'TXT_'.$this->moduleLangVar.'_OCLOCK' =>  $_ARRAYLANG['TXT_CALENDAR_OCLOCK'],
            'TXT_'.$this->moduleLangVar.'_DATE' =>  $_CORELANG['TXT_DATE'],
            $this->moduleLangVar.'_SEARCH_TERM' => contrexx_raw2xhtml($term),
            $this->moduleLangVar.'_SEARCH_FROM' =>  contrexx_raw2xhtml($from),
            $this->moduleLangVar.'_SEARCH_TILL' => contrexx_raw2xhtml($till),
            $this->moduleLangVar.'_SEARCH_CATEGORIES' =>  $objCategoryManager->getCategoryDropdown(intval($catid), 1),
            $this->moduleLangVar.'_JAVASCRIPT'  => $javascript
        ));
         self::showThreeBoxes();

        if($this->objEventManager->countEvents > $this->arrSettings['numPaging'] && (isset($_GET['search']) || $_GET['cmd'] == 'list' || $_GET['cmd'] == 'eventlist' || $_GET['cmd'] == 'archive')) {
            $pagingCmd = !empty($cmd) ? '&amp;cmd='.  contrexx_raw2xhtml($cmd) : '';
            $pagingCategory = !empty($catid) ? '&amp;catid='.intval($catid) : '';
            $pagingTerm = !empty($term) ? '&amp;term='.  contrexx_raw2xhtml($term) : '';
            $pagingSearch = !empty($search) ? '&amp;search='.  contrexx_raw2xhtml($search) : '';
            $pagingFrom = !empty($from) ? '&amp;from='.  contrexx_raw2xhtml($from) : '';
            $pagingTill = !empty($till) ? '&amp;till='.  contrexx_raw2xhtml($till) : '';


            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_PAGING' =>  getPaging($this->objEventManager->countEvents, $this->startPos, "&section=".$this->moduleName.$pagingCmd.$pagingCategory.$pagingTerm.$pagingSearch.$pagingFrom.$pagingTill, "<b>".$_ARRAYLANG['TXT_CALENDAR_EVENTS']."</b>", true, $this->arrSettings['numPaging']),
            ));
        }

        $this->objEventManager->showEventList($this->_objTpl);
    }

    /**
     * performs the my events page
     *
     * @return null
     */
    function myEvents()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $objCategoryManager = new \Cx\Modules\Calendar\Controller\CalendarCategoryManager(true);
        $objCategoryManager->getCategoryList();

        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_EDIT' =>  $_ARRAYLANG['TXT_CALENDAR_EDIT'],
        ));

        if($this->objEventManager->countEvents > $this->arrSettings['numPaging']) {
            $pagingCmd = !empty($_GET['cmd']) ? '&amp;cmd='.$_GET['cmd'] : '';

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_PAGING' =>  getPaging($this->objEventManager->countEvents, $this->startPos, "&section=".$this->moduleName.$pagingCmd, "<b>".$_ARRAYLANG['TXT_CALENDAR_EVENTS']."</b>", true, $this->arrSettings['numPaging']),
            ));
        }

        $this->objEventManager->showEventList($this->_objTpl);
    }

    /**
     * Add / Edit Event
     *
     * @param integer $eventId Event id
     *
     * @return null
     */
    function modifyEvent($eventId = null)
    {
        global $_ARRAYLANG, $_CORELANG, $_LANGID;
        \JS::activate('cx');
        \JS::activate('jqueryui');

        \JS::registerJS('modules/Calendar/View/Script/Frontend.js');

        $this->getFrontendLanguages();
        $this->getSettings();
        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $showFrom = true;

        $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent();
        $isEventLoaded = false;

        if (isset($_POST['submitFormModifyEvent'])) {
            $arrData = array();
            $arrData = $_POST;

            $arrData['access'] = 0;
            $arrData['priority'] = 3;

            if($objEvent->save($arrData)) {
                $showFrom = false;
                $this->_objTpl->hideBlock('calendarEventModifyForm');
                $this->_objTpl->touchBlock('calendarEventOkMessage');

                // refresh event data after save
                $objEvent->get($objEvent->id);
                $objEvent->getData();
                $isEventLoaded  = true;
                $objMailManager = new \Cx\Modules\Calendar\Controller\CalendarMailManager();
                $objMailManager->sendMail($objEvent, \Cx\Modules\Calendar\Controller\CalendarMailManager::MAIL_NOTFY_NEW_APP);
            } else {
                $this->_objTpl->touchBlock('calendarEventErrMessage');
            }
        }

        if ($eventId && !$isEventLoaded) {
            $objEvent->get($eventId);
            $objEvent->getData();
        }

        $dateFormat = $this->getDateFormat(1);

        $locationType = $this->arrSettings['placeData'] == 3 ? ($eventId != 0 ? $objEvent->locationType : 1) : $this->arrSettings['placeData'];
        $hostType     = $this->arrSettings['placeDataHost'] == 3 ? ($eventId != 0 ? $objEvent->hostType : 1) : $this->arrSettings['placeDataHost'];

        \ContrexxJavascript::getInstance()->setVariable(array(
            'language_id' => \FWLanguage::getDefaultLangId(),
            'active_lang' => implode(',', \FWLanguage::getIdArray()),
        ), 'calendar');

        $javascript = <<< EOF
<script language="JavaScript" type="text/javascript">
var defaultLang = cx.variables.get('language_id', 'calendar');
var activeLang = [cx.variables.get('active_lang', 'calendar')];
cx.ready(function() {
    var options = {
        dateFormat: '$dateFormat',
        timeFormat: 'hh:mm',
        showSecond: false,
        onSelect: function(dateText, inst){
            var startDate = cx.jQuery( ".startDate" ).datetimepicker("getDate");
            var endDate   = cx.jQuery( ".endDate" ).datetimepicker("getDate");

            if ( cx.jQuery( this )[0].id == 'startDate' ) {
                var prevStartDate = cx.jQuery( ".startDate" ).data('prevDate');

                if (cx.jQuery(".all_day").is(':checked')) {
                    prevStartDate.setHours(0, 0, 0);
                    startDate.setHours(0, 0, 0);
                    endDate.setHours(0, 0, 0);
                }

                if (prevStartDate.getTime() != startDate.getTime()) {
                    var timeDiff = Math.abs(endDate.getTime() - prevStartDate.getTime());
                    endDate = new Date(startDate.getTime() + timeDiff);
                    cx.jQuery( ".endDate" ).datetimepicker('setDate', endDate);
                }

            } else if (startDate.getTime() > endDate.getTime()) {
                endDate = new Date(startDate.getTime() + (30*60*1000));
                cx.jQuery(".endDate").datetimepicker('setDate', endDate);
            }

            cx.jQuery( ".startDate" ).data('prevDate', cx.jQuery(".startDate").datetimepicker("getDate"));
            cx.jQuery( ".endDate" ).data('prevDate', cx.jQuery(".endDate").datetimepicker("getDate"));
            cx.jQuery( this ).datetimepicker('refresh');
        }
    };
    cx.jQuery('input[name=startDate]')
        .datetimepicker(options)
        .data('prevDate', cx.jQuery(".startDate").datetimepicker("getDate"));
    cx.jQuery('input[name=endDate]')
        .datetimepicker(options)
        .data('prevDate', cx.jQuery(".endDate").datetimepicker("getDate"));
    if ( \$J(".all_day").is(':checked') ) {
        modifyEvent._handleAllDayEvent( \$J(".all_day") );
    }
    showOrHidePlaceFields('$locationType', 'place');
    showOrHidePlaceFields('$hostType', 'host');
});

</script>
EOF;

        if ($showFrom) {
            try {
                $javascript .= <<< UPLOADER
                {$this->getUploaderCode(self::PICTURE_FIELD_KEY, 'pictureUpload')}
                {$this->getUploaderCode(self::MAP_FIELD_KEY, 'mapUpload')}
                {$this->getUploaderCode(self::ATTACHMENT_FIELD_KEY, 'attachmentUpload', 'uploadFinished', false)}
UPLOADER;
            } catch(Exception $e) {
                \DBG::msg("Error in initializing uploader");
            }
        }

        $this->_objTpl->setGlobalVariable(array(
            $this->moduleLangVar.'_EVENT_LANG_ID'               => $_LANGID,
            $this->moduleLangVar.'_JAVASCRIPT'                  => $javascript,
        ));

        $objCategoryManager = new \Cx\Modules\Calendar\Controller\CalendarCategoryManager(true);
        $objCategoryManager->getCategoryList();

        if ($eventId) {
            $startDate = $objEvent->startDate;
            $endDate   = $objEvent->endDate;
        } else {
            $startDate = new \DateTime();
            $endDate   = new \DateTime();
        }

        $eventStartDate = $this->format2userDateTime($startDate);
        $eventEndDate   = $this->format2userDateTime($endDate);

        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_EVENT'                    => $_ARRAYLANG['TXT_CALENDAR_EVENT'],
            'TXT_'.$this->moduleLangVar.'_EVENT_DETAILS'            => $_ARRAYLANG['TXT_CALENDAR_EVENT_DETAILS'],
            'TXT_'.$this->moduleLangVar.'_SAVE'                     => $_ARRAYLANG['TXT_CALENDAR_SAVE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_START'              => $_ARRAYLANG['TXT_CALENDAR_START'],
            'TXT_'.$this->moduleLangVar.'_EVENT_END'                => $_ARRAYLANG['TXT_CALENDAR_END'],
            'TXT_'.$this->moduleLangVar.'_EVENT_TITLE'              => $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_EXPAND'                   => $_ARRAYLANG['TXT_CALENDAR_EXPAND'],
            'TXT_'.$this->moduleLangVar.'_MINIMIZE'                 => $_ARRAYLANG['TXT_CALENDAR_MINIMIZE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_PLACE'              => $_ARRAYLANG['TXT_CALENDAR_EVENT_PLACE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_STREET'             => $_ARRAYLANG['TXT_CALENDAR_EVENT_STREET'],
            'TXT_'.$this->moduleLangVar.'_EVENT_ZIP'                => $_ARRAYLANG['TXT_CALENDAR_EVENT_ZIP'],
            'TXT_'.$this->moduleLangVar.'_EVENT_CITY'               => $_ARRAYLANG['TXT_CALENDAR_EVENT_CITY'],
            'TXT_'.$this->moduleLangVar.'_EVENT_COUNTRY'            => $_ARRAYLANG['TXT_CALENDAR_EVENT_COUNTRY'],
            'TXT_'.$this->moduleLangVar.'_EVENT_WEBSITE'            => $_ARRAYLANG['TXT_CALENDAR_EVENT_WEBSITE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_PHONE'              => $_ARRAYLANG['TXT_CALENDAR_EVENT_PHONE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_MAP'                => $_ARRAYLANG['TXT_CALENDAR_EVENT_MAP'],
            'TXT_'.$this->moduleLangVar.'_EVENT_USE_GOOGLEMAPS'     => $_ARRAYLANG['TXT_CALENDAR_EVENT_USE_GOOGLEMAPS'],
            'TXT_'.$this->moduleLangVar.'_EVENT_LINK'               => $_ARRAYLANG['TXT_CALENDAR_EVENT_LINK'],
            'TXT_'.$this->moduleLangVar.'_EVENT_EMAIL'              => $_ARRAYLANG['TXT_CALENDAR_EVENT_EMAIL'],
            'TXT_'.$this->moduleLangVar.'_EVENT_PICTURE'            => $_ARRAYLANG['TXT_CALENDAR_EVENT_PICTURE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_ATTACHMENT'         => $_ARRAYLANG['TXT_CALENDAR_EVENT_ATTACHMENT'],
            'TXT_'.$this->moduleLangVar.'_EVENT_CATEGORY'           => $_ARRAYLANG['TXT_CALENDAR_CAT'] ,
            'TXT_'.$this->moduleLangVar.'_EVENT_DESCRIPTION'        => $_ARRAYLANG['TXT_CALENDAR_EVENT_DESCRIPTION'],
            'TXT_'.$this->moduleLangVar.'_PLEASE_CHECK_INPUT'       => $_ARRAYLANG['TXT_CALENDAR_PLEASE_CHECK_INPUT'],
            'TXT_'.$this->moduleLangVar.'_EVENT_HOST'               => $_ARRAYLANG['TXT_CALENDAR_EVENT_HOST'],
            'TXT_'.$this->moduleLangVar.'_EVENT_NAME'               => $_ARRAYLANG['TXT_CALENDAR_EVENT_NAME'],
            'TXT_'.$this->moduleLangVar.'_EVENT_ALL_DAY'            => $_ARRAYLANG['TXT_CALENDAR_EVENT_ALL_DAY'],
            'TXT_'.$this->moduleLangVar.'_LANGUAGE'                 => $_ARRAYLANG['TXT_CALENDAR_LANG'],
            'TXT_'.$this->moduleLangVar.'_EVENT_TYPE'               => $_ARRAYLANG['TXT_CALENDAR_EVENT_TYPE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_TYPE_EVENT'         => $_ARRAYLANG['TXT_CALENDAR_EVENT_TYPE_EVENT'],
            'TXT_'.$this->moduleLangVar.'_EVENT_TYPE_REDIRECT'      => $_ARRAYLANG['TXT_CALENDAR_EVENT_TYPE_REDIRECT'],
            'TXT_'.$this->moduleLangVar.'_EVENT_DESCRIPTION'        => $_ARRAYLANG['TXT_CALENDAR_EVENT_DESCRIPTION'],
            'TXT_'.$this->moduleLangVar.'_EVENT_REDIRECT'           => $_ARRAYLANG['TXT_CALENDAR_EVENT_TYPE_REDIRECT'],
            'TXT_'.$this->moduleLangVar.'_PLACE_DATA_DEFAULT'       => $_ARRAYLANG['TXT_CALENDAR_PLACE_DATA_DEFAULT'],
            'TXT_'.$this->moduleLangVar.'_PLACE_DATA_FROM_MEDIADIR' => $_ARRAYLANG['TXT_CALENDAR_PLACE_DATA_FROM_MEDIADIR'],
            'TXT_'.$this->moduleLangVar.'_PREV'                     => $_ARRAYLANG['TXT_CALENDAR_PREV'],
            'TXT_'.$this->moduleLangVar.'_NEXT'                     => $_ARRAYLANG['TXT_CALENDAR_NEXT'],
            'TXT_'.$this->moduleLangVar.'_MORE'                     => $_ARRAYLANG['TXT_CALENDAR_MORE'],
            'TXT_'.$this->moduleLangVar.'_MINIMIZE'                 => $_ARRAYLANG['TXT_CALENDAR_MINIMIZE'],

            $this->moduleLangVar.'_EVENT_TYPE_EVENT'                => $eventId != 0 ? ($objEvent->type == 0 ? 'selected="selected"' : '') : '',
            $this->moduleLangVar.'_EVENT_TYPE_REDIRECT'             => $eventId != 0 ? ($objEvent->type == 1 ? 'selected="selected"' : '') : '',
            $this->moduleLangVar.'_EVENT_START_DATE'                => $eventStartDate,
            $this->moduleLangVar.'_EVENT_END_DATE'                  => $eventEndDate,
            $this->moduleLangVar.'_EVENT_PICTURE'                   => $objEvent->pic,
            $this->moduleLangVar.'_EVENT_PICTURE_THUMB'             => $objEvent->pic != '' ? '<img src="'.$objEvent->pic.'.thumb" alt="'.$objEvent->title.'" title="'.$objEvent->title.'" />' : '',
            $this->moduleLangVar.'_EVENT_ATTACHMENT'                => $objEvent->attach,
            $this->moduleLangVar.'_EVENT_CATEGORIES'                => $objCategoryManager->getCategoryDropdown(intval($objEvent->catId), 2),
            $this->moduleLangVar.'_EVENT_LINK'                      => $objEvent->link,
            $this->moduleLangVar.'_EVENT_PLACE'                     => $objEvent->place,
            $this->moduleLangVar.'_EVENT_STREET'                    => $objEvent->place_street,
            $this->moduleLangVar.'_EVENT_ZIP'                       => $objEvent->place_zip,
            $this->moduleLangVar.'_EVENT_CITY'                      => $objEvent->place_city,
            $this->moduleLangVar.'_EVENT_COUNTRY'                   => $objEvent->place_country,
            $this->moduleLangVar.'_EVENT_PLACE_WEBSITE'             => $objEvent->place_website,
            $this->moduleLangVar.'_EVENT_PLACE_MAP'                 => $objEvent->place_map,
            $this->moduleLangVar.'_EVENT_PLACE_LINK'                => $objEvent->place_link,
            $this->moduleLangVar.'_EVENT_PLACE_PHONE'               => $objEvent->place_phone,
            $this->moduleLangVar.'_EVENT_MAP'                       => $objEvent->google == 1 ? 'checked="checked"' : '',
            $this->moduleLangVar.'_EVENT_HOST'                      => $objEvent->org_name,
            $this->moduleLangVar.'_EVENT_HOST_ADDRESS'              => $objEvent->org_street,
            $this->moduleLangVar.'_EVENT_HOST_ZIP'                  => $objEvent->org_zip,
            $this->moduleLangVar.'_EVENT_HOST_CITY'                 => $objEvent->org_city,
            $this->moduleLangVar.'_EVENT_HOST_COUNTRY'              => $objEvent->org_country,
            $this->moduleLangVar.'_EVENT_HOST_WEBSITE'              => $objEvent->org_website,
            $this->moduleLangVar.'_EVENT_HOST_LINK'                 => $objEvent->org_link,
            $this->moduleLangVar.'_EVENT_HOST_PHONE'                => $objEvent->org_phone,
            $this->moduleLangVar.'_EVENT_HOST_EMAIL'                => $objEvent->org_email,
            $this->moduleLangVar.'_EVENT_LOCATION_TYPE_MANUAL'      => $eventId != 0 ? ($objEvent->locationType == 1 ? "checked='checked'" : '') : "checked='checked'",
            $this->moduleLangVar.'_EVENT_LOCATION_TYPE_MEDIADIR'    => $eventId != 0 ? ($objEvent->locationType == 2 ? "checked='checked'" : '') : "",
            $this->moduleLangVar.'_EVENT_HOST_TYPE_MANUAL'          => $eventId != 0 ? ($objEvent->hostType == 1 ? "checked='checked'" : '') : "checked='checked'",
            $this->moduleLangVar.'_EVENT_HOST_TYPE_MEDIADIR'        => $eventId != 0 ? ($objEvent->hostType == 2 ? "checked='checked'" : '') : "",

            $this->moduleLangVar.'_EVENT_ID'                        => $eventId,
            $this->moduleLangVar.'_EVENT_ALL_DAY'                   => $eventId != 0 && $objEvent->all_day ? 'checked="checked"' : '',
            $this->moduleLangVar.'_HIDE_ON_SINGLE_LANG'             => count($this->arrFrontendLanguages) == 1 ? "display: none;" : "",
        ));
        $multiLingualFields = array(
            'place',
            'place_city',
            'place_country',
            'org_name',
            'org_city',
            'org_country',
        );
        $isOneActiveLanguage = count($this->arrFrontendLanguages) == 1;
        foreach ($multiLingualFields as $inputField) {
            if ($isOneActiveLanguage) {
                $this->_objTpl->hideBlock('calendar_event_'. $inputField .'_expand');
            } else {
                $this->_objTpl->touchBlock('calendar_event_'. $inputField .'_expand');
            }
        }
        foreach ($this->arrFrontendLanguages as $arrLang) {
            //parse globals
            $this->_objTpl->setGlobalVariable(array(
                $this->moduleLangVar.'_EVENT_LANG_SHORTCUT'     => $arrLang['lang'],
                $this->moduleLangVar.'_EVENT_LANG_ID'           => $arrLang['id'],
                'TXT_'.$this->moduleLangVar.'_EVENT_LANG_NAME'  => $arrLang['name'],
            ));

            //parse "show in" checkboxes
            $arrShowIn = explode(",", $objEvent->showIn);

            $langChecked = false;
            if($eventId != 0) {
                $langChecked = in_array($arrLang['id'], $arrShowIn) ? true : false;
            } else {
                $langChecked = $arrLang['is_default'] == 'true';
            }

            //parse eventTabMenuDescTab
            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_TAB_DISPLAY' => $langChecked ? 'block' : 'none',
                $this->moduleLangVar.'_EVENT_TAB_CLASS'   => '',
            ));

            $this->_objTpl->parse('eventTabMenuDescTab');

            //parse eventDescTab
            $eventTitle       = !empty($objEvent->arrData['title'][$arrLang['id']])
                                ? $objEvent->arrData['title'][$arrLang['id']]
                                : (!empty($objEvent->arrData['redirect'][$_LANGID]) ? $objEvent->arrData['redirect'][$_LANGID] : '');
            $eventDescription = !empty($objEvent->arrData['description'][$arrLang['id']])
                                ? $objEvent->arrData['description'][$arrLang['id']]
                                : '';
            $eventRedirect    = !empty($objEvent->arrData['redirect'][$arrLang['id']])
                                ? $objEvent->arrData['redirect'][$arrLang['id']]
                                : (!empty($objEvent->arrData['redirect'][$_LANGID]) ? $objEvent->arrData['redirect'][$_LANGID] : '');
            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_TAB_DISPLAY'               => $langChecked ? 'block' : 'none',
                $this->moduleLangVar.'_EVENT_TITLE'                     => contrexx_raw2xhtml($eventTitle),
                $this->moduleLangVar.'_EVENT_DESCRIPTION'               => new \Cx\Core\Wysiwyg\Wysiwyg("description[{$arrLang['id']}]",
                                                                                                        contrexx_raw2xhtml($eventDescription),
                                                                                                        $eventId != 0 ? 'small' : 'bbcode'),
                $this->moduleLangVar.'_EVENT_REDIRECT'                  => contrexx_raw2xhtml($eventRedirect),
                $this->moduleLangVar.'_EVENT_TYPE_EVENT_DISPLAY'        => $objEvent->type == 0 ? 'block' : 'none',
                $this->moduleLangVar.'_EVENT_TYPE_REDIRECT_DISPLAY'     => $objEvent->type == 1 ? 'block' : 'none',
            ));

            $this->_objTpl->parse('eventDescTab');
            //parse eventLingualFields
            foreach ($multiLingualFields as $inputField) {
                $this->_objTpl->setVariable(
                    $this->moduleLangVar.'_EVENT_'. strtoupper($inputField) .'_DEFAULT',
                    $eventId != 0 ? $objEvent->{$inputField} : ''
                );
                $this->_objTpl->setVariable(array(
                    $this->moduleLangVar.'_EVENT_VALUE' => !empty($objEvent->arrData[$inputField][$arrLang['id']])
                                                          ? $objEvent->arrData[$inputField][$arrLang['id']]
                                                          : ($eventId != 0 ? $objEvent->{$inputField} : ''),
                ));
                $this->_objTpl->parse('calendar_event_'. $inputField);
            }

            $langChecked = $langChecked ? 'checked="checked"' : '';

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_LANG_CHECKED'  => $langChecked,
            ));

            $this->_objTpl->parse('eventShowIn');

        }
        //parse placeSelect
        if ((int) $this->arrSettings['placeData'] > 1) {
            $objMediadirEntries = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry('MediaDir');
            $objMediadirEntries->getEntries(null,null,null,null,null,null,true,0,'n',null,null,intval($this->arrSettings['placeDataForm']));

            $placeOptions = '<option value="">'.$_ARRAYLANG['TXT_CALENDAR_PLEASE_CHOOSE'].'</option>';

            foreach($objMediadirEntries->arrEntries as $key => $arrEntry) {
                $selectedPlace = ($arrEntry['entryId'] == $objEvent->place_mediadir_id) ? 'selected="selected"' : '';
                $placeOptions .= '<option '.$selectedPlace.' value="'.$arrEntry['entryId'].'">'.$arrEntry['entryFields'][0].'</option>';
            }

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_PLACE_OPTIONS'    => $placeOptions,
            ));
            $this->_objTpl->parse('eventPlaceSelect');

            if ((int) $this->arrSettings['placeData'] == 2) {
                $this->_objTpl->hideBlock('eventPlaceInput');
                $this->_objTpl->hideBlock('eventPlaceTypeRadio');
            } else {
                $this->_objTpl->touchBlock('eventPlaceInput');
                $this->_objTpl->touchBlock('eventPlaceTypeRadio');
            }
        } else {
            $this->_objTpl->touchBlock('eventPlaceInput');
            $this->_objTpl->hideBlock('eventPlaceSelect');
            $this->_objTpl->hideBlock('eventPlaceTypeRadio');
        }

        //parse placeHostSelect
        if ((int) $this->arrSettings['placeDataHost'] > 1) {
            $objMediadirEntries = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry('MediaDir');
            $objMediadirEntries->getEntries(null,null,null,null,null,null,true,0,'n',null,null,intval($this->arrSettings['placeDataHostForm']));

            $placeOptions = '<option value="">'.$_ARRAYLANG['TXT_CALENDAR_PLEASE_CHOOSE'].'</option>';

            foreach($objMediadirEntries->arrEntries as $key => $arrEntry) {
                $selectedPlace = ($arrEntry['entryId'] == $objEvent->host_mediadir_id) ? 'selected="selected"' : '';
                $placeOptions .= '<option '.$selectedPlace.' value="'.$arrEntry['entryId'].'">'.$arrEntry['entryFields'][0].'</option>';
            }

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_EVENT_PLACE_OPTIONS'    => $placeOptions,
            ));
            $this->_objTpl->parse('eventHostSelect');

            if ((int) $this->arrSettings['placeDataHost'] == 2) {
                $this->_objTpl->hideBlock('eventHostInput');
                $this->_objTpl->hideBlock('eventHostTypeRadio');
            } else {
                $this->_objTpl->touchBlock('eventHostInput');
                $this->_objTpl->touchBlock('eventHostTypeRadio');
            }
        } else {
            $this->_objTpl->touchBlock('eventHostInput');
            $this->_objTpl->hideBlock('eventHostSelect');
            $this->_objTpl->hideBlock('eventHostTypeRadio');
        }

    }

    /**
     * Performs the Event details page
     *
     * @return null
     */
    function showEvent()
    {
        global $_ARRAYLANG, $_CORELANG, $_LANGID;

        if (empty($this->objEventManager->eventList)) {
            \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName));
            exit;
        }

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $this->pageTitle = html_entity_decode($this->objEventManager->eventList[0]->title, ENT_QUOTES, CONTREXX_CHARSET);

        $this->_objTpl->setVariable(array(
            'TXT_'.$this->moduleLangVar.'_ATTACHMENT'        =>  $_ARRAYLANG['TXT_CALENDAR_ATTACHMENT'],
            'TXT_'.$this->moduleLangVar.'_THUMBNAIL'         =>  $_ARRAYLANG['TXT_CALENDAR_THUMBNAIL'],
            'TXT_'.$this->moduleLangVar.'_OPTIONS'           =>  $_ARRAYLANG['TXT_CALENDAR_OPTIONS'],
            'TXT_'.$this->moduleLangVar.'_CATEGORY'          =>  $_ARRAYLANG['TXT_CALENDAR_CAT'],
            'TXT_'.$this->moduleLangVar.'_PLACE'             =>  $_ARRAYLANG['TXT_CALENDAR_PLACE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_HOST'        =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_HOST'],
            'TXT_'.$this->moduleLangVar.'_PRIORITY'          =>  $_ARRAYLANG['TXT_CALENDAR_PRIORITY'],
            'TXT_'.$this->moduleLangVar.'_START'             =>  $_ARRAYLANG['TXT_CALENDAR_START'],
            'TXT_'.$this->moduleLangVar.'_END'               =>  $_ARRAYLANG['TXT_CALENDAR_END'],
            'TXT_'.$this->moduleLangVar.'_COMMENT'           =>  $_ARRAYLANG['TXT_CALENDAR_COMMENT'],
            'TXT_'.$this->moduleLangVar.'_OCLOCK'            =>  $_ARRAYLANG['TXT_CALENDAR_OCLOCK'],
            'TXT_'.$this->moduleLangVar.'_EXPORT'            =>  $_ARRAYLANG['TXT_CALENDAR_EXPORT'],
            'TXT_'.$this->moduleLangVar.'_EVENT_PRICE'       =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_PRICE'],
            'TXT_'.$this->moduleLangVar.'_EVENT_FREE_PLACES' =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_FREE_PLACES'],
            'TXT_'.$this->moduleLangVar.'_DATE'              =>  $_CORELANG['TXT_DATE'],
            'TXT_'.$this->moduleLangVar.'_NAME'              =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_NAME'],
            'TXT_'.$this->moduleLangVar.'_LINK'              =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_LINK'],
            'TXT_'.$this->moduleLangVar.'_EVENT'             =>  $_ARRAYLANG['TXT_CALENDAR_EVENT'],
            'TXT_'.$this->moduleLangVar.'_STREET'            =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_STREET'],
            'TXT_'.$this->moduleLangVar.'_ZIP'               =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_ZIP'],
            'TXT_'.$this->moduleLangVar.'_MAP'               =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_MAP'],
            'TXT_'.$this->moduleLangVar.'_HOST'              =>  $_ARRAYLANG['TXT_CALENDAR_HOST'],
            'TXT_'.$this->moduleLangVar.'_MAIL'              =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_EMAIL'],
            'TXT_'.$this->moduleLangVar.'_HOST_NAME'         =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_NAME'],
            'TXT_'.$this->moduleLangVar.'_TITLE'             =>  $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_ACCESS'            =>  $_ARRAYLANG['TXT_CALENDAR_ACCESS'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION'      =>  $_ARRAYLANG['TXT_CALENDAR_REGISTRATION'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_INFO' =>  $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_INFO']
        ));

        $this->objEventManager->showEvent($this->_objTpl, intval($_GET['id']), intval($_GET['date']));
    }

    /**
     * performs the registratio page
     *
     * @return null
     */
    function showRegistrationForm()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $objFWUser      = \FWUser::getFWUserObject();
        $objUser        = $objFWUser->objUser;
        $userId         = intval($objUser->getId());
        $userLogin      = $objUser->login();
        $captchaCheck   = true;

        if(!$userLogin && isset($_POST['submitRegistration'])) {
            $captchaCheck =  \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check();
            if (!$captchaCheck) {
                $this->_objTpl->setVariable(array(
                    'TXT_'.$this->moduleLangVar.'_ERROR' => '<span class="text-danger">'.$_ARRAYLANG['TXT_CALENDAR_INVALID_CAPTCHA_CODE'].'</span>',
                ));
            }
        }

        $objEvent = $this->objEventManager->eventList[0];

        if (empty($objEvent)) {
            \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, ''));
            return;
        }

        if(!$objEvent->status) {
            \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd($this->moduleName, ''));
            return;
        }

        if($objEvent->access == 1 && !\FWUser::getFWUserObject()->objUser->login()){
            $link = base64_encode(CONTREXX_SCRIPT_PATH.'?'.$_SERVER['QUERY_STRING']);
            \Cx\Core\Csrf\Controller\Csrf::redirect(CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
            return;
        }

        $dateForPageTitle = $objEvent->startDate;
        $this->pageTitle = $this->format2userDate($dateForPageTitle)
                            . ": ".html_entity_decode($objEvent->title, ENT_QUOTES, CONTREXX_CHARSET);

        // Only show registration form if event lies in the future
        if(time() <= $objEvent->startDate->getTimestamp()) {
            // Only show registration form if event accepts registrations.
            // Event accepts registrations, if registration is set up and
            //     - no attendee limit is set
            //     - or if there are still free places available
            if (   $objEvent->registration == CalendarEvent::EVENT_REGISTRATION_INTERNAL
                && (   empty($objEvent->numSubscriber)
                    || !\FWValidator::isEmpty($objEvent->getFreePlaces())
                )
            ) {
                $this->_objTpl->setVariable(array(
                    $this->moduleLangVar.'_EVENT_ID'                   =>  intval($_REQUEST['id']),
                    $this->moduleLangVar.'_FORM_ID'                    =>  intval($objEvent->registrationForm),
                    $this->moduleLangVar.'_EVENT_DATE'                 =>  intval($_REQUEST['date']),
                    $this->moduleLangVar.'_USER_ID'                    =>  $userId,
                    'TXT_'.$this->moduleLangVar.'_REGISTRATION_SUBMIT' =>  $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_SUBMIT'],
                ));

                $objFormManager = new \Cx\Modules\Calendar\Controller\CalendarFormManager();
                $objFormManager->setEvent($objEvent);
                $objFormManager->getFormList();
                //$objFormManager->showForm($this->_objTpl,intval($objEvent->registrationForm), 2, $objEvent->ticketSales);
                // Made the ticket sales always true, because ticket functionality currently not implemented
                $objFormManager->showForm($this->_objTpl, intval($objEvent->registrationForm), 2, true);

                /* if ($this->arrSettings['paymentStatus'] == '1' && $objEvent->ticketSales && ($this->arrSettings['paymentBillStatus'] == '1' || $this->arrSettings['paymentYellowpayStatus'] == '1')) {
                    $paymentMethods  = '<select class="calendarSelect" name="paymentMethod">';
                    $paymentMethods .= $this->arrSettings['paymentBillStatus'] == '1' || $objEvent->price == 0 ? '<option value="1">'.$_ARRAYLANG['TXT_CALENDAR_PAYMENT_BILL'].'</option>'  : '';
                    $paymentMethods .= $this->arrSettings['paymentYellowpayStatus'] == '1' && $objEvent->price > 0 ? '<option value="2">'.$_ARRAYLANG['TXT_CALENDAR_PAYMENT_YELLOWPAY'].'</option>' : '';
                    $paymentMethods .= '</select>';

                    $this->_objTpl->setVariable(array(
                        'TXT_'.$this->moduleLangVar.'_PAYMENT_METHOD' => $_ARRAYLANG['TXT_CALENDAR_PAYMENT_METHOD'],
                        $this->moduleLangVar.'_PAYMENT_METHODS'       => $paymentMethods,
                    ));
                    $this->_objTpl->parse('calendarRegistrationPayment');
                } else {
                    $this->_objTpl->hideBlock('calendarRegistrationPayment');
                } */

                if(!$userLogin) {

                    $this->_objTpl->setVariable(array(
                        'TXT_'.$this->moduleLangVar.'_CAPTCHA' => $_CORELANG['TXT_CORE_CAPTCHA'],
                        $this->moduleLangVar.'_CAPTCHA_CODE'   => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
                    ));
                    $this->_objTpl->parse('calendarRegistrationCaptcha');
                } else {
                    $this->_objTpl->hideBlock('calendarRegistrationCaptcha');
                }

                if(isset($_POST['submitRegistration']) && $captchaCheck) {
                    $objRegistration = new \Cx\Modules\Calendar\Controller\CalendarRegistration(intval($_POST['form']));

                    if($objRegistration->save($_POST)) {
                        if ($objRegistration->saveIn == 2) {
                            $status = $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_SUCCESSFULLY_ADDED_WAITLIST'];
                        } else if ($objRegistration->saveIn == 0) {
                            $status =$_ARRAYLANG['TXT_CALENDAR_REGISTRATION_SUCCESSFULLY_ADDED_SIGNOFF'];
                        } else {
                            $status = $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_SUCCESSFULLY_ADDED'];
                            /* if($_POST["paymentMethod"] == 2) {
                                $objRegistration->get($objRegistration->id);
                                $objEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent($objRegistration->eventId);
                                $this->getSettings();
                                $amount  = (int) $objEvent->price * 100;
                                $status .= \Cx\Modules\Calendar\Controller\CalendarPayment::_yellowpay(array("orderID" => $objRegistration->id, "amount" => $amount, "currency" => $this->arrSettings["paymentCurrency"], "language" => "DE"));
                            } */
                        }
                        $this->_objTpl->setVariable(array(
                            $this->moduleLangVar.'_LINK_BACK' =>  '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section='.$this->moduleName.'">'.$_ARRAYLANG['TXT_CALENDAR_BACK'].'</a>',
                            $this->moduleLangVar.'_REGISTRATION_STATUS' =>  $status,
                        ));

                        $this->_objTpl->touchBlock('calendarRegistrationStatus');
                        $this->_objTpl->hideBlock('calendarRegistrationForm');
                    } else {
                        $this->_objTpl->setVariable(array(
                            'TXT_'.$this->moduleLangVar.'_ERROR' => '<span class="text-danger">'.$_ARRAYLANG['TXT_CALENDAR_CHECK_REQUIRED'].'</span>',
                        ));

                        $this->_objTpl->parse('calendarRegistrationForm');
                        $this->_objTpl->hideBlock('calendarRegistrationStatus');
                    }
                } else {
                    $this->_objTpl->parse('calendarRegistrationForm');
                    $this->_objTpl->hideBlock('calendarRegistrationStatus');
                }
            } else {
                $this->_objTpl->setVariable(array(
                    $this->moduleLangVar.'_LINK_BACK' =>  '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section='.$this->moduleName.'">'.$_ARRAYLANG['TXT_CALENDAR_BACK'].'</a>',
                    $this->moduleLangVar.'_REGISTRATION_STATUS' =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_FULLY_BLOCKED'],
                ));

                $this->_objTpl->touchBlock('calendarRegistrationStatus');
                $this->_objTpl->hideBlock('calendarRegistrationForm');
            }
        } else {
            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_LINK_BACK' =>  '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section='.$this->moduleName.'">'.$_ARRAYLANG['TXT_CALENDAR_BACK'].'</a>',
                $this->moduleLangVar.'_REGISTRATION_STATUS' =>  $_ARRAYLANG['TXT_CALENDAR_EVENT_IN_PAST'],
            ));

            $this->_objTpl->touchBlock('calendarRegistrationStatus');
            $this->_objTpl->hideBlock('calendarRegistrationForm');
        }
    }

    /**
     * set the placeholders for the category view
     *
     * @return null
     */
    function showCategoryView()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        // load source code if cmd value is integer
        if ($this->_objTpl->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('Calendar');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->_objTpl->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        $objCategoryManager = new \Cx\Modules\Calendar\Controller\CalendarCategoryManager(true);
        $objCategoryManager->getCategoryList();

        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_SEARCH_TERM' =>  $_ARRAYLANG['TXT_CALENDAR_KEYWORD'],
            'TXT_'.$this->moduleLangVar.'_FROM' =>  $_ARRAYLANG['TXT_CALENDAR_FROM'],
            'TXT_'.$this->moduleLangVar.'_TILL' =>  $_ARRAYLANG['TXT_CALENDAR_TILL'],
            'TXT_'.$this->moduleLangVar.'_CATEGORY' =>  $_ARRAYLANG['TXT_CALENDAR_CAT'],
            'TXT_'.$this->moduleLangVar.'_SEARCH' =>  $_ARRAYLANG['TXT_CALENDAR_SEARCH'],
            'TXT_'.$this->moduleLangVar.'_OCLOCK' =>  $_ARRAYLANG['TXT_CALENDAR_OCLOCK'],
            $this->moduleLangVar.'_SEARCH_TERM' => isset($_GET['term']) ? contrexx_input2xhtml($_GET['term']) : '',
            $this->moduleLangVar.'_SEARCH_FROM' => isset($_GET['from']) ? contrexx_input2xhtml($_GET['from']) : '',
            $this->moduleLangVar.'_SEARCH_TILL' => isset($_GET['till']) ? contrexx_input2xhtml($_GET['till']) : '',
            $this->moduleLangVar.'_SEARCH_CATEGORIES' =>  $objCategoryManager->getCategoryDropdown((isset($_GET['catid']) ? intval($_GET['catid']) : 0), 1)
        ));

        if(isset($this->categoryId)) {
            $objCategory = new \Cx\Modules\Calendar\Controller\CalendarCategory($this->categoryId);
            $this->_objTpl->setGlobalVariable(array(
                $this->moduleLangVar.'_CATEGORY_NAME' =>  $objCategory->name,
            ));

            $this->objEventManager->showEventList($this->_objTpl);

            $this->_objTpl->parse('categoryList');
        } else {
            foreach ($objCategoryManager->categoryList as $key => $objCategory) {
                $objEventManager = new \Cx\Modules\Calendar\Controller\CalendarEventManager($this->startDate,$this->endDate,$objCategory->id,$this->searchTerm,true,$this->needAuth,true,$this->startPos,$this->numEvents);
                $objEventManager->getEventList();

                $objEventManager->showEventList($this->_objTpl);

                $this->_objTpl->setGlobalVariable(array(
                    $this->moduleLangVar.'_CATEGORY_NAME' =>  $objCategory->name,
                ));

                $this->_objTpl->parse('categoryList');
            }
        }
    }

    /**
     * Display the success page
     *
     * @return null
     */
    function showSuccessPage() {
        $this->_objTpl->setTemplate($this->pageContent, true, true);
        if($_REQUEST["handler"] == "yellowpay") {
            $orderId = \Yellowpay::getOrderId();
            $this->getSettings();
            if (\Yellowpay::checkin($this->arrSettings["paymentYellowpayShaOut"])) {
                switch(abs($_REQUEST["result"])) {
                    case 2:
                        // fehler aufgetreten
                        $objRegistration = new \Cx\Modules\Calendar\Controller\CalendarRegistration(null);
                        $objRegistration->delete($orderId);
                        $this->_objTpl->touchBlock("cancelMessage");
                        break;
                    case 1:
                        // erfolgreich
                        $objRegistration = new \Cx\Modules\Calendar\Controller\CalendarRegistration(null);
                        $objRegistration->get($orderId);
                        $objRegistration->setPaid(1);
                        $this->_objTpl->touchBlock("successMessage");
                        break;
                    case 0:
                        // abgebrochen
                        $objRegistration = new \Cx\Modules\Calendar\Controller\CalendarRegistration(null);
                        $objRegistration->delete($orderId);
                        $this->_objTpl->touchBlock("cancelMessage");
                        break;
                    default:
                        \Cx\Core\Csrf\Controller\Csrf::redirect("index.php?section=".$this->moduleName);
                        break;
                }
            } else {
                \Cx\Core\Csrf\Controller\Csrf::redirect("index.php?section=".$this->moduleName);
                return;
            }
        } else {
            \Cx\Core\Csrf\Controller\Csrf::redirect("index.php?section=".$this->moduleName);
            return;
        }
    }

    /**
     * Get uploader code
     *
     * @param string  $fieldKey       uploadFieldKey
     * @param string  $fieldName      uploadFieldName
     * @param string  $uploadCallBack upload callback function
     * @param boolean $allowImageOnly allow only images files
     *
     * @return string uploaderCode
     * @throws \Exception
     */
    protected function getUploaderCode($fieldKey, $fieldName, $uploadCallBack = "uploadFinished", $allowImageOnly = true)
    {
        \cmsSession::getInstance();
        $cx  = \Cx\Core\Core\Controller\Cx::instanciate();
        try {
            $uploader      = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
            $uploaderId    = $uploader->getId();
            $uploadOptions = array(
                'id'     => 'calendarUploader_'.$uploaderId,
                'style'  => 'display: none'
            );
            if ($allowImageOnly) {
                $uploadOptions['allowed-extensions'] = array('gif', 'jpg', 'png', 'jpeg');
            }

            $uploader->setCallback($fieldName.'JsCallback');
            $uploader->setUploadLimit(1);
            $uploader->setOptions($uploadOptions);
            $uploader->setFinishedCallback(array(
                $cx->getCodeBaseModulePath().'/Calendar/Controller/Calendar.class.php',
                '\Cx\Modules\Calendar\Controller\Calendar',
                $uploadCallBack
            ));

            $folderWidget = new \Cx\Core_Modules\MediaBrowser\Model\Entity\FolderWidget($_SESSION->getTempPath().'/'.$uploaderId);
            $this->_objTpl->setVariable( array(
                strtoupper($fieldName).'_WIDGET_CODE'            => $folderWidget->getXHtml(),
                "{$this->moduleLangVar}_". strtoupper($fieldKey) => $uploaderId
            ));

            $strJs = <<<JAVASCRIPT
{$uploader->getXHtml()}
<script type="text/javascript">
    cx.ready(function() {
        //called if user clicks on the field
        jQuery('#$fieldName').bind('click', function() {
            jQuery('#calendarUploader_$uploaderId').trigger('click');
            return false;
        });
    });

//uploader javascript callback function
function {$fieldName}JsCallback(callback) {
        angular.element('#mediaBrowserfolderWidget_{$folderWidget->getId()}').scope().refreshBrowser();
}
</script>
JAVASCRIPT;
            return $strJs;
        } catch (\Exception $e) {
            \DBG::msg('<!-- failed initializing uploader -->');
            throw new \Exception("failed initializing uploader");
        }
    }

    /**
     * Uploader callback function
     *
     * @param string  $tempPath    Temp path
     * @param string  $tempWebPath Temp webpath
     * @param string  $data        post data
     * @param integer $uploadId    upload id
     * @param array   $fileInfos   file infos
     * @param object  $response    Upload api response object
     *
     * @return array $tempPath and $tempWebPath
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {
        // Delete existing files because we need only one file to upload
        if (\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
            foreach (glob($tempPath.'/*') as $file) {
                if (basename($file) == $fileInfos['name']) {
                    continue;
                }
                \Cx\Lib\FileSystem\FileSystem::delete_file($file);
            }
        }

        return array($tempPath, $tempWebPath);
    }

    /**
     * Performs the box view
     *
     * @return null
     */
    function showThreeBoxes()
    {
        global $_ARRAYLANG;

        $objEventManager = new \Cx\Modules\Calendar\Controller\CalendarEventManager($this->startDate,$this->endDate,$this->categoryId,$this->searchTerm,true,$this->needAuth,true,0,'n',$this->sortDirection,true,$this->author);
        $objEventManager->getEventList();
        $this->_objTpl->setTemplate($this->pageContent);
        if ($_REQUEST['cmd'] == 'boxes') {
            $objEventManager->calendarBoxUrl         = \Cx\Core\Routing\Url::fromModuleAndCmd('Calendar', 'boxes')->toString()."?act=list";
            $objEventManager->calendarBoxMonthNavUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('Calendar', 'boxes')->toString();
        } else {
            $objEventManager->calendarBoxUrl         = \Cx\Core\Routing\Url::fromModuleAndCmd('Calendar', '')->toString()."?act=list";
            $objEventManager->calendarBoxMonthNavUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('Calendar', '')->toString();
        }

        if (empty($_GET['catid'])) {
            $catid = 0;
        } else {
            $catid = $_GET['catid'];
        }

        if (isset($_GET['yearID']) && isset($_GET['monthID']) &&  isset($_GET['dayID'])) {
            $day   = $_GET['dayID'];
            $month = $_GET['monthID'];
            $year  = $_GET['yearID'];
        } elseif (isset($_GET['yearID']) && isset($_GET['monthID']) && !isset($_GET['dayID'])) {
            $day   = 0;
            $month = $_GET['monthID'];
            $year  = $_GET['yearID'];
        } elseif (isset($_GET['yearID']) && !isset($_GET['monthID']) && !isset($_GET['dayID'])) {
            $day    = 0;
            $month  = 0;
            $year   = $_GET['yearID'];
        } else {
            $day   = date("d");
            $month = date("m");
            $year  = date("Y");
        }

        $calendarbox = $objEventManager->getBoxes($this->boxCount, $year, $month, $day, $catid);

        $objCategoryManager = new \Cx\Modules\Calendar\Controller\CalendarCategoryManager(true);
        $objCategoryManager->getCategoryList();

        $this->_objTpl->setVariable(array(
            "TXT_{$this->moduleLangVar}_ALL_CAT" => $_ARRAYLANG['TXT_CALENDAR_ALL_CAT'],
            "{$this->moduleLangVar}_BOX"     => $calendarbox,
            "{$this->moduleLangVar}_JAVA_SCRIPT" => $objEventManager->getCalendarBoxJS(),
            "{$this->moduleLangVar}_CATEGORIES"     => $objCategoryManager->getCategoryDropdown($catid, 1),
        ));
    }

    /**
     * Performs the list box view
     *
     * @return null
     */
    function boxesEventList()
    {
        $this->_objTpl->setTemplate($this->pageContent);

        $this->_objTpl->hideBlock("boxes");

        $this->objEventManager->showEventList($this->_objTpl);

    }
}
