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
 * Stats Library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version 1.0
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\Stats\Controller;
/**
 * Stats Library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version 1.0
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 */
class StatsLibrary
{
    public $totalVisitors = 0;
    public $totalRequests = 0;
    public $arrBrowsers = array();
    public $browserSum = 0;
    public $arrSupportJavaScript = array();
    public $supportJavaScriptSum = 0;
    public $arrOperatingSystems = array();
    public $operatingSystemsSum = 0;
    public $arrScreenResolutions = array();
    public $screenResolutionSum = 0;
    public $arrColourDepths = array();
    public $colourDepthSum = 0;
    public $arrMostViewedPages = array();
    public $mostViewedPagesSum = 0;
    public $arrIndexedPages = array();
    public $arrSpiders = array();
    public $arrVisitorsDetails = array();
    public $arrVisitors = array();
    public $arrRequests = array();
    public $arrLastReferer = array();
    public $arrTopReferer = array();
    public $arrHostnames = array();
    public $hostnamesSum = 0;
    public $arrCountries = array();
    public $countriesSum = 0;
    public $arrCountryNames = array();
    public $arrConfig = array();
    public $arrSearchTerms = array();
    public $pagingLimit = "";
    public $pagingLimitVisitorDetails = "";
    public $spiderAgent = false;
    public $arrClient = array();
    public $arrProxy = array();
    public $md5Id = 0;
    public $currentTime = 0;

    /**
     * Instance of cloudrexx
     *
     * @var object \Cx\Core\Core\Controller\Cx
     */
    protected $cx;

    function __construct() {
        $this->cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $this->_initConfiguration();
    }


    /**
    * Initialize the configuration for the counter and statistics
    * @global    ADONewConnection
    */
    function _initConfiguration()
    {
        global $objDatabase;

        $query = "SELECT name, value, `status` FROM ".DBPREFIX."stats_config";
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrConfig[$objResult->fields['name']] = array('value' => $objResult->fields['value'], 'status' => $objResult->fields['status']);
                $objResult->MoveNext();
            }
        }

        $this->pagingLimit = "LIMIT ".$this->arrConfig['paging_limit']['value']."";
        $this->pagingLimitVisitorDetails = "LIMIT ".$this->arrConfig['paging_limit_visitor_details']['value']."";
        $this->currentTime = time();
    }


    /**
    * Creates the tag to call the counter
    * @access   public
    * @global   integer $pageId
    * @return   string  $counterTag The counter tag
    */
    function getCounterTag()
    {
        global $pageId;

        $counterTag = '';

        if ($this->arrConfig['make_statistics']['status']) {
            // don't activate jquery if not necessary due to performance
            //JS::activate('jquery');
            $searchTerm = '';
            $searchTermPlain = '';
            $searchTermNoScript = '';
            if (isset($_REQUEST['term']) && !empty($_REQUEST['term']) && $_REQUEST['section'] == "Search") {
                $searchTerm = "&amp;searchTerm=".urlencode($_REQUEST['term'])."' + '";
                $searchTermNoScript = "&amp;searchTerm=".urlencode($_REQUEST['term']);
                $searchTermPlain = contrexx_addslashes($_REQUEST['term']);
            }

            $ascms_core_module_web_path = $this->cx->getCodeBaseCoreModuleWebPath();
            $counterTag = file_get_contents(dirname(dirname(__FILE__)).'/Data/stats_script.html');
            $replaces = array(
                '[CORE_MODULE_URL]' => $ascms_core_module_web_path,
                '[PAGEID]'          => $pageId,
                '[SEARCHTERM]'      => $searchTerm,
                '[SEARCHTERM_NOSCRIPT]'=> $searchTermNoScript,
                '[SEARCHTERM_PLAIN]'=> $searchTermPlain,
                '[REFERER]'         => '$url_encode($(HTTP_REFERER))',
            );
            foreach ($replaces as $from => $to) {
                $counterTag = str_replace($from, $to, $counterTag);
            }
        }
        return $counterTag;
    }


    /**
    * Check if the user agent is a spider
    */
    function checkForSpider()
    {
        global $arrRobots;

        if ($this->arrConfig['count_spiders']['status']) {
            $arrRobots = array();
            $this->cx->getClassLoader()->loadFile($this->cx->getCoreModuleFolderName().'/Stats/Data/spiders.inc.php');
            $useragent =  htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, CONTREXX_CHARSET);
            $spiderAgent = false;
            foreach ($arrRobots as $spider) {
                $spiderName = trim($spider);
                if (preg_match("=".$spiderName."=",$useragent)) {
                    $spiderAgent = true;
                    break;
                }
            }
            if ($spiderAgent) {
                $this->_countSpider($useragent);
            }
        }
    }


    /**
    * count every spider visit
    * @global   ADONewConnection
    * @global   integer
    * @return   boolean  result
    */
    function _countSpider($useragent)
    {
        global $objDatabase, $pageId;

        if (isset($_SERVER['HTTP_VIA']) && $_SERVER['HTTP_VIA']) { // spider does use a proxy
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $spiderIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $spiderIp = $_SERVER['HTTP_CLIENT_IP'];
                } else {
                    $spiderIp = $_SERVER['REMOTE_ADDR'];
                }
            }
        } else { // spider does not use proxy
            $spiderIp = $_SERVER['REMOTE_ADDR'];
        }

        $net = $this->cx->getComponent('Net');
        $spiderHost = $net->getHostByAddr($spiderIp);
        if ($spiderHost == $spiderIp) {
           $spiderHost = '';
        }

        $requestedUrl = $this->_getRequestedUrl();

        // update statistics of the indexed page by spiders
        $query = "UPDATE `".DBPREFIX."stats_spiders`
                 Set `last_indexed` = '".time()."',
                     `count` = `count` + 1,
                     `spider_useragent` = '".$useragent."',
                     `spider_ip` = '".$spiderIp."',
                     `spider_host` = '".$spiderHost."'
                   WHERE `page` = '".$requestedUrl."'";
        $objDatabase->Execute($query);
        if ($objDatabase->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_spiders` (
                        `last_indexed`,
                        `page`,
                        `pageId`,
                        `count`,
                        `spider_useragent`,
                        `spider_ip`,
                        `spider_host`
                        ) VALUES (
                        '".time()."',
                        '".$requestedUrl."',
                        '".$pageId."',
                        1,
                        '".$useragent."',
                        '".$spiderIp."',
                        '".$spiderHost."
                        ')";
            $objDatabase->Execute($query);
        }

        // update summery statistics of the spiders
        $query = "UPDATE `".DBPREFIX."stats_spiders_summary` SET `count` = `count` + 1, `timestamp` = '".time()."' WHERE `name` = '".substr($useragent,0,255)."'";
        $objDatabase->Execute($query);

        if ($objDatabase->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_spiders_summary` (`name`, `timestamp`, `count`) VALUES ('".substr($useragent,0,255)."', '".time()."', 1)";
            $objDatabase->Execute($query);
        }
    }

    /**
    * Get requested page
    *
    * Get the requested page from the $_GET vars. If a session has started, this var will be blocked
    * and not in the uri
    *
    */
    function _getRequestedUrl()
    {
        global $arrBannedWords;

        $arrBannedWords = array();
        $this->cx->getClassLoader()->loadFile($this->cx->getCoreModuleFolderName().'/Stats/Data/banned.inc.php');
        $uriString="";

        $completeUriString = substr(strstr($_SERVER['REQUEST_URI'], "?"),1);
        //creates an array for each GET-pair
        $arrUriGets = explode("&", $completeUriString);

        foreach ($arrUriGets AS $elem) {
            //check if Session-ID is traced by url (cookies are disabled)
            if (!preg_match("/PHPSESSID/",$elem)) {
                if ($elem != "") {
                    $uriString .="&".$elem;
                }
            }
        }

        if (count($arrBannedWords)) {
            foreach ($arrBannedWords as $blockElem) {
                $blockElem = trim($blockElem);
                //some blocked words in get-Vars?
                if (preg_match("=".$blockElem."=",$uriString) && ($blockElem <>"")) {
                    $uriString = "";
                }
            }
        }

        if ($uriString == "") { // only uninteresting vars in uri (faked?)
            return "/index.php";
        } else {
            return "/index.php?" . contrexx_input2db(substr($uriString, 1));
        }
    }

    /**
    * Remove outdated entries
    *
    * Remove outdated entries of the database
    * @global    ADONewConnection
    */
    function _removeOutdatedEntries()
    {
        global $objDatabase;

        // remove outdated visitor entries
        $query = "DELETE FROM ".DBPREFIX."stats_visitors WHERE `timestamp` < '".(mktime(0,0,0,date('m'),date('d'),date('Y'))-($this->arrConfig['online_timeout']['status'] ? $this->arrConfig['online_timeout']['value'] : 0))."'";
        $objDatabase->Execute($query);

        // remove outdated request entries
        if ($this->arrConfig['remove_requests']['status']) {
            $query = "DELETE FROM ".DBPREFIX."stats_requests WHERE `timestamp` < '".$this->arrConfig['remove_requests']['value']."'";
            $objDatabase->Execute($query);
        }

        /*// delete outdated visitor summary entries
        $query = "DELETE FROM `".DBPREFIX."stats_visitors_summary`
                   WHERE (`type` = 'hour' AND `timestamp` < '".mktime(0,0,0,date('m'),date('d'),date('Y'))."')
                      OR (`type` = 'day' AND `timestamp` < '".mktime(0,0,0,date('m'),1,date('Y'))."')
                      OR (`type` = 'month' AND `timestamp` < '".mktime(0,0,0,1,1,date('Y'))."')";
        $objDatabase->Execute($query);

        // delete outdated requests summary entries
        $query = "DELETE FROM `".DBPREFIX."stats_requests_summary`
                   WHERE (`type` = 'hour' AND `timestamp` < '".mktime(0,0,0,date('m'),date('d'),date('Y'))."')
                      OR (`type` = 'day' AND `timestamp` < '".mktime(0,0,0,date('m'),1,date('Y'))."')
                      OR (`type` = 'month' AND `timestamp` < '".mktime(0,0,0,1,1,date('Y'))."')";
        $objDatabase->Execute($query);*/
    }

    function _initVisitorDetails()
    {
        global $objDatabase;

        $query = "SELECT client_ip, client_host, client_useragent, proxy_ip, proxy_host, proxy_useragent, `timestamp`
                    FROM ".DBPREFIX."stats_visitors
                    ORDER BY `timestamp` DESC
                    ".$this->pagingLimitVisitorDetails."";
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $arrVisitor = array(
                                    'client_ip' => $objResult->fields['client_ip'],
                                    'client_host'   => $objResult->fields['client_host'],
                                    'client_useragent'  => $objResult->fields['client_useragent'],
                                    'proxy_ip'          => $objResult->fields['proxy_ip'],
                                    'proxy_host'        => $objResult->fields['proxy_host'],
                                    'proxy_useragent'   => $objResult->fields['proxy_useragent'],
                                    'timestamp'         => $objResult->fields['timestamp'],
                                    'last_request'      => date(ASCMS_DATE_FORMAT, $objResult->fields['timestamp'])
                                    );
                array_push($this->arrVisitorsDetails,$arrVisitor);
                $objResult->MoveNext();
            }
            if (isset($this->arrVisitorsDetails[count($this->arrVisitorsDetails)-1])) {
                $lastTimestamp = $this->arrVisitorsDetails[count($this->arrVisitorsDetails)-1]['timestamp'];
            }
        }

        if (isset($lastTimestamp)) {
            // remove outdated visitor entries
            $query = "DELETE FROM ".DBPREFIX."stats_visitors WHERE `timestamp` < '".$lastTimestamp."'";
            $objDatabase->Execute($query);
        }
    }

    /**
    * Initialize today statistics
    *
    * Initialize the visitor and request statistics from today
    *
    * @access    private
    * @global    ADONewConnection
    * @see    _removeOutdatedEntries()
    */
    function _initStatisticsToday() {
        global $objDatabase;

        // remove outdated visitor and request entries in the summary
        $this->_removeOutdatedEntries();

        // get statistics
        $query = "SELECT `timestamp`, `count`
            FROM `".DBPREFIX."stats_visitors_summary`
            WHERE `type` = 'hour' AND `count` > 0 AND `timestamp` >= '".(time()-86400)."'";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $hour = date('H', $objResult->fields['timestamp']);
            $this->arrRequests[$hour]['visitors'] = $objResult->fields['count'];
            $this->totalVisitors += $objResult->fields['count'];
            $objResult->MoveNext();
        }

        $query = "SELECT `timestamp`, `count`
            FROM `".DBPREFIX."stats_requests_summary`
            WHERE `type` = 'hour' AND `count` > 0 AND `timestamp` >= '".(time()-86400)."'";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $hour = date('H', $objResult->fields['timestamp']);
            $this->arrRequests[$hour]['requests'] = $objResult->fields['count'];
            $this->totalRequests += $objResult->fields['count'];
            $objResult->MoveNext();
        }

        $query = "SELECT client_ip, client_host, client_useragent, proxy_ip, proxy_host, proxy_useragent, `timestamp`
                    FROM ".DBPREFIX."stats_visitors WHERE `timestamp` > '".mktime(0,0,0,date('m'),date('d'),date('Y'))."'
                    ORDER BY `timestamp` DESC
                    ".$this->pagingLimit."";
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $arrVisitor = array(
                                    'client_ip' => $objResult->fields['client_ip'],
                                    'client_host'   => $objResult->fields['client_host'],
                                    'client_useragent'  => $objResult->fields['client_useragent'],
                                    'proxy_ip'          => $objResult->fields['proxy_ip'],
                                    'proxy_host'        => $objResult->fields['proxy_host'],
                                    'proxy_useragent'   => $objResult->fields['proxy_useragent'],
                                    'last_request'      => date('H:i:s', $objResult->fields['timestamp'])
                                    );
                array_push($this->arrVisitorsDetails,$arrVisitor);
                $objResult->MoveNext();
            }
        }
    }

    function _initStatisticsDays() {
        global $objDatabase;

        // remove outdated visitor and request entries in the summary
        $this->_removeOutdatedEntries();

        // get statistics
        $query = "SELECT `timestamp`, `count`
                    FROM `".DBPREFIX."stats_visitors_summary`
                           WHERE `type` = 'day' AND `count` > 0 AND `timestamp` >= '".
                            mktime(
                                0,
                                0,
                                0,
                                $previousMonth = date('m') == 1 ? 12 : date('m') - 1,
                                date('d') == date('t', mktime(0, 0, 0, $previousMonth, 0, $previousYear = (date('m') == 1 ? date('Y') -1 : date('Y')))) ? date('d') + 1 : 1,
                                $previousYear
                            )."'";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $day = date('j', $objResult->fields['timestamp']);
            $this->arrRequests[$day]['visitors'] = $objResult->fields['count'];
            $this->totalVisitors += $objResult->fields['count'];
            $objResult->MoveNext();
        }

        $query = "SELECT `timestamp`, `count`
                    FROM `".DBPREFIX."stats_requests_summary`
                           WHERE `type` = 'day' AND `count` > 0 AND `timestamp` >= '".
                            mktime(
                                0,
                                0,
                                0,
                                $previousMonth = date('m') == 1 ? 12 : date('m') - 1,
                                date('d') == date('t', mktime(0, 0, 0, $previousMonth, 0, $previousYear = (date('m') == 1 ? date('Y') -1 : date('Y')))) ? date('d') + 1 : 1,
                                $previousYear
                            )."'";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $day = date('j', $objResult->fields['timestamp']);
            $this->arrRequests[$day]['requests'] = $objResult->fields['count'];
            $this->totalRequests += $objResult->fields['count'];
            $objResult->MoveNext();
        }
    }


    function _initStatisticsMonths()
    {
        global $objDatabase;

        // remove outdated visitor and request entries in the summary
        $this->_removeOutdatedEntries();

        // get statistics
        $query = "SELECT `timestamp`, `count`
            FROM `".DBPREFIX."stats_visitors_summary`
            WHERE `type` = 'month' AND `count` > 0 AND `timestamp` >= '".mktime(0, 0, 0, date('m'), null, date('Y')-2)."'";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $year  = date('y', $objResult->fields['timestamp']);
            $month = date('n', $objResult->fields['timestamp']);
            $this->arrRequests[$year][$month]['visitors'] = $objResult->fields['count'];
            $this->totalVisitors += $objResult->fields['count'];
            $objResult->MoveNext();
        }

        $query = "SELECT `timestamp`, `count`
            FROM `".DBPREFIX."stats_requests_summary`
            WHERE `type` = 'month' AND `count` > 0 AND `timestamp` >= '".mktime(0, 0, 0, date('m'), null, date('Y')-2)."'";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $year  = date('y', $objResult->fields['timestamp']);
            $month = date('n', $objResult->fields['timestamp']);
            $this->arrRequests[$year][$month]['requests'] = $objResult->fields['count'];
            $this->totalRequests += $objResult->fields['count'];
            $objResult->MoveNext();
        }
    }

    function _initStatisticsYears() {
        global $objDatabase;

        // remove outdated visitor and request entries in the summary
        $this->_removeOutdatedEntries();

        // get statistics
        $query = "SELECT `timestamp`, `count`
            FROM `".DBPREFIX."stats_visitors_summary`
            WHERE `type` = 'year'
            ORDER BY `timestamp`";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $year = date('Y', $objResult->fields['timestamp']);
            $this->arrRequests[$year]['visitors'] = $objResult->fields['count'];
            $this->totalVisitors += $objResult->fields['count'];
            $objResult->MoveNext();
        }

        $query = "SELECT `timestamp`, `count`
            FROM `".DBPREFIX."stats_requests_summary`
            WHERE `type` = 'year'
            ORDER BY `timestamp`";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $year = date('Y', $objResult->fields['timestamp']);
            $this->arrRequests[$year]['requests'] = $objResult->fields['count'];
            $this->totalRequests +=$objResult->fields['count'];
            $objResult->MoveNext();
        }
        ksort($this->arrRequests);
    }

    function _initMostViewedPages() {
        global $objDatabase;

        $query = "
            SELECT `pageId`, `pageTitle`, `page`, SUM(`visits`) as `visits`, `timestamp`
            FROM `".DBPREFIX."stats_requests`
            GROUP BY `pageId`
            ORDER BY `visits` DESC, `timestamp` DESC
            ".$this->pagingLimit;

        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $arrPage = array(
                    'id' => $objResult->fields['pageId'],
                    'page' => $objResult->fields['page'],
                    'title' => $objResult->fields['pageTitle'],
                    'requests' => $objResult->fields['visits'],
                    'last_request' => date('d-m-Y H:i:s', $objResult->fields['timestamp'])
                );
                array_push($this->arrMostViewedPages, $arrPage);
                $this->mostViewedPagesSum += $objResult->fields['visits'];
                $objResult->MoveNext();
            }
        }
        if ($objResult->RecordCount() == $this->arrConfig['paging_limit']['value']) {
            $query = "SELECT SUM(`visits`) AS `visits_sum` FROM `".DBPREFIX."stats_requests`";
            if (($objResult = $objDatabase->Execute($query))) {
                if (!$objResult->EOF) {
                    $this->mostViewedPagesSum = $objResult->fields['visits_sum'];
                }
            }
        }
    }

    function _initSpiders() {
        global $objDatabase;

        $query = "SELECT `last_indexed`,
                         `page`,
                         `pageId`,
                         `count`,
                         `spider_useragent`,
                         `spider_ip`,
                         `spider_host`
                    FROM `".DBPREFIX."stats_spiders`
                   ORDER BY `last_indexed` DESC
                   ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                // get page from repo
                $crit = array(
                    'id' => $objResult->fields['pageId'],
                );
                $page = current(\Env::get('em')->getRepository('\Cx\Core\ContentManager\Model\Entity\Page')->findBy($crit));
                if ($page) {
                    $objResult->fields['title'] = $page->getTitle();
                    $arrIndexedPage = array(
                        'last_indexed'      => date('d-m-Y H:i:s', $objResult->fields['last_indexed']),
                        'page'              => $objResult->fields['page'],
                        'title'             => strlen($objResult->fields['title'])>0 ? $objResult->fields['title'] : "No title",
                        'count'             => $objResult->fields['count'],
                        'spider_useragent'  => $objResult->fields['spider_useragent'],
                        'spider_ip'         => $objResult->fields['spider_ip'],
                        'spider_host'       => $objResult->fields['spider_host']
                    );
                    array_push($this->arrIndexedPages, $arrIndexedPage);
                }
                $objResult->MoveNext();
            }
        }

        $query = "SELECT name, `timestamp`, `count`
                    FROM ".DBPREFIX."stats_spiders_summary
                ORDER BY `count` DESC, `timestamp` DESC
                   ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $arrSpider = array(
                    'name'          => $objResult->fields['name'],
                    'count'         => $objResult->fields['count'],
                    'last_indexed'  => date('d-m-Y H:i:s', $objResult->fields['timestamp'])
                );
                array_push($this->arrSpiders, $arrSpider);
                $objResult->MoveNext();
            }
        }
    }

    /**
    * Get online users
    *
    * Get the number of the online users
    *
    * @access    public
    * @global    ADONewConnection
    * @return    integer    $onlineUsers
    */
    function getOnlineUsers() {
        global $objDatabase;

        $onlineUsers = 0;

        if ($this->arrConfig['online_timeout']['status']) {
            $query = "SELECT SUM(1) AS `online_users` FROM `".DBPREFIX."stats_visitors` WHERE `timestamp` > '".(time()-$this->arrConfig['online_timeout']['value'])."'";
            if (($objResult = $objDatabase->query($query))) {
                if (!$objResult->EOF) {
                    $onlineUsers = $objResult->fields['online_users'];
                    $objResult->MoveNext();
                }
            }
        }
        return $onlineUsers;
    }

    /**
    * Get visitor number
    *
    * Get the number of the current visitor
    *
    * @access public
    * @global ADONewConnection
    * @return mixed visitor number on success, false on failure
    */
    function getVisitorNumber()
    {
        global $objDatabase;

        if ($this->arrConfig['make_statistics']['status'] && $this->arrConfig['count_visitor_number']['status']) {
            $isNewUser = true;

            // check if it is a new user
            $this->_getClientInfos();

            $objVisitors = $objDatabase->SelectLimit("SELECT id FROM `".DBPREFIX."stats_visitors` WHERE `sid` = '".$this->md5Id."' AND `timestamp` >= '".($this->currentTime - $this->arrConfig['reload_block_time']['value'])."'", 1);
            if ($objVisitors !== false) {
                if ($objVisitors->RecordCount() == 1) {
                    $isNewUser = false;
                }
            }

            $objVisitors = $objDatabase->Execute("SELECT sum(`count`) as `number` FROM ".DBPREFIX."stats_visitors_summary WHERE `type`='year' GROUP BY `type`");
            if ($objVisitors !== false) {
                if ($isNewUser) {
                    return $objVisitors->fields['number']+1;
                } else {
                    return $objVisitors->fields['number'];
                }
            }
        }
        return false;
    }

    /**
     * Get unique user id
     * @return unique user id as md5-string
     */
    public function getUniqueUserId()
    {
        if (!$this->md5Id) {
            $this->_getClientInfos();
        }

        return $this->md5Id;
    }

    /**
     * Get the HTTP client's IP address
     *
     * IMPORTANT: Do not store the return value of this method in order to
     * comply with the data privacy act GDPR!
     * In case the client is connecting through a proxy,
     * this method will try the fetch the client's original
     * IP address and will return that one instead of the
     * proxy's IP address.
     *
     * @return  string  IP address of client
     */
    public function getClientIp()
    {
        if (!isset($this->arrClient['ip'])) {
            $this->_getClientInfos();
        }

        return $this->arrClient['ip'];
    }

    /**
    * Get client informations
    *
    * Get the clientinfos like useragent, langugage, ip, proxy, host and referer
    *
    * @see    _getProxyInformations(), _getReferer(), _checkForSpider()
    * @return    boolean  result
    */
    function _getClientInfos()
    {
        $this->arrClient['useragent'] = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, CONTREXX_CHARSET);
        if (stristr($this->arrClient['useragent'],"phpinfo")) {
            $this->arrClient['useragent'] = "<b>p_h_p_i_n_f_o() Possible Hacking Attack</b>";
        }

        $this->arrClient['language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                                        ? htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE'], ENT_QUOTES, CONTREXX_CHARSET) : '';
        $this->_getProxyInformations(); // get also the client ip

        $this->md5Id = md5($this->arrClient['ip'].$this->arrClient['useragent'].$this->arrClient['language'].$this->arrProxy['ip'].$this->arrProxy['host']);
    }


    /**
    * Get proxy informations
    *
    * Determines if a proxy is used or not. If so, then proxy information are colleted
    */
    function _getProxyInformations() {
        if (isset($_SERVER['HTTP_VIA']) && $_SERVER['HTTP_VIA']) { // client does use a proxy
            $this->arrProxy['ip'] = $_SERVER['REMOTE_ADDR'];
            $net = $this->cx->getComponent('Net');
            $this->arrProxy['host'] = $net->getHostByAddr($this->arrProxy['ip']);
            $proxyUseragent = trim(addslashes(urldecode(strstr($_SERVER['HTTP_VIA'],' '))));
            $startPos = strpos($proxyUseragent,"(");
            $this->arrProxy['useragent'] = substr($proxyUseragent,$startPos+1);
            $endPos=strpos($this->arrProxy['useragent'],")");

            if ($this->arrProxy['host'] == $this->arrProxy['ip']) { // no hostname found, try to take it out from useragent-infos
                $endPos = strpos($proxyUseragent,"(");
                $this->arrProxy['host'] = substr($proxyUseragent,0,$endPos);
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->arrClient['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $this->arrClient['ip'] = $_SERVER['HTTP_CLIENT_IP'];
                } else {
                    $this->arrClient['ip'] = $_SERVER['REMOTE_ADDR'];
                }
            }
        } else { // Client does not use proxy
            $this->arrClient['ip'] = $_SERVER['REMOTE_ADDR'];
            $this->arrProxy['ip'] = "";
            $this->arrProxy['host'] = "";
        }
    }


    /**
    * return the image with the procentualy width or height
    *
    * @param    float   $maxWidth   width if the image is 100% in widht
    * @param    float   $maxHeight  height if the image is 100% in height
    * @param    float   $percWidth  procentualy width
    * @param    float   $percHeight procentualy height
    * @param    string  choose the image for the percentbar
    * @param    string  $title  title and alternative text
    * @return   string  the path to the image with procentualy width and height
    */
    function _makePercentBar($maxWidth, $maxHeight, $percWidth, $percHeight, $gif, $title = '')
    {
        $imgWidth = round((($maxWidth * $percWidth) / 100),0);
        $imgHeight = round((($maxHeight * $percHeight) / 100),0);

        $imgPath = '..'. $this->cx->getCodeBaseCoreModuleWebPath() .'/Stats/View/Media/'.($maxWidth > $maxHeight ? $gif : $gif.'_v').'.gif';
        return "<img src=\"$imgPath\" height=\"$imgHeight\" width=\"$imgWidth\" title=\"$title\" alt=\"$title\" />";
    }


    function _initSearchTerms()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT `name`, `count`, `external` FROM `".DBPREFIX."stats_search` WHERE `external`='0' ORDER BY `count` DESC, `name` ".$this->pagingLimit);
        if ($objResult !== false) {
            $this->arrSearchTerms['internal'] = array();
            while (!$objResult->EOF) {
                array_push($this->arrSearchTerms['internal'], array(
                    'name'  => stripslashes($objResult->fields['name']),
                    'count' => $objResult->fields['count']
                ));
                $objResult->MoveNext();
            }
        }

        $objResult = $objDatabase->Execute("SELECT `name`, `count`, `external` FROM `".DBPREFIX."stats_search` WHERE `external`='1' ORDER BY `count` DESC, `name` ".$this->pagingLimit);
        if ($objResult !== false) {
            $this->arrSearchTerms['external'] = array();
            while (!$objResult->EOF) {
                array_push($this->arrSearchTerms['external'], array(
                    'name'  => stripslashes($objResult->fields['name']),
                    'count' => $objResult->fields['count']
                ));
                $objResult->MoveNext();
            }
        }

        $objResult = $objDatabase->Execute("SELECT `name`, SUM(`count`) AS totalCount, `external` FROM `".DBPREFIX."stats_search` GROUP BY `name` ORDER BY totalCount DESC, `name` ".$this->pagingLimit);
        if ($objResult !== false) {
            $this->arrSearchTerms['summary'] = array();
            while (!$objResult->EOF) {
                array_push($this->arrSearchTerms['summary'], array(
                    'name'  => stripslashes($objResult->fields['name']),
                    'count' => $objResult->fields['totalCount']
                ));
                $objResult->MoveNext();
            }
        }
    }

    function _initClientStatistics()
    {
        global $objDatabase;

        // get browser statistics
        $query = "SELECT `name`, `count` FROM `".DBPREFIX."stats_browser`
                ORDER BY `count` DESC
                ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrBrowsers[$objResult->fields['name']] = $objResult->fields['count'];
                $this->browserSum += $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }
        if ($objResult->RecordCount() == $this->arrConfig['paging_limit']['value']) {
            $query = "SELECT SUM(`count`) AS `count_sum` FROM `".DBPREFIX."stats_browser`";
            if (($objResult = $objDatabase->Execute($query))) {
                if (!$objResult->EOF) {
                    $this->browserSum = $objResult->fields['count_sum'];
                }
            }
        }

        // get javascript statistics
        $query = "SELECT `support`, `count` FROM `".DBPREFIX."stats_javascript`
                   ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrSupportJavaScript[$objResult->fields['support']] = $objResult->fields['count'];
                $this->supportJavaScriptSum += $objResult->fields['count'];
                $objResult->MoveNext();
            };
        }

        // get operating system statistics
        $query = "SELECT `name`, `count` FROM `".DBPREFIX."stats_operatingsystem`
                ORDER BY `count` DESC
                   ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrOperatingSystems[$objResult->fields['name']] = $objResult->fields['count'];
                $this->operatingSystemsSum += $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }
        if ($objResult->RecordCount() == $this->arrConfig['paging_limit']['value']) {
            $query = "SELECT SUM(`count`) AS `count_sum` FROM `".DBPREFIX."stats_operatingsystem`";
            if (($objResult = $objDatabase->Execute($query))) {
                if (!$objResult->EOF) {
                    $this->operatingSystemsSum = $objResult->fields['count_sum'];
                }
            }
        }

        // get screen resolution statistics
        $query = "SELECT `resolution`, `count` FROM `".DBPREFIX."stats_screenresolution`
                ORDER BY `count` DESC
                   ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrScreenResolutions[$objResult->fields['resolution']] = $objResult->fields['count'];
                $this->screenResolutionSum += $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }
         if ($objResult->RecordCount() == $this->arrConfig['paging_limit']['value']) {
            $query = "SELECT SUM(`count`) AS `count_sum` FROM `".DBPREFIX."stats_screenresolution`";
            if (($objResult = $objDatabase->Execute($query))) {
                if (!$objResult->EOF) {
                    $this->screenResolutionSum = $objResult->fields['count_sum'];
                }
            }
        }

        // get colour depth statistics
        $query = "SELECT `depth`, `count` FROM `".DBPREFIX."stats_colourdepth`
                ORDER BY `count` DESC
                   ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrColourDepths[$objResult->fields['depth']] = $objResult->fields['count'];
                $this->colourDepthSum += $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }
        if ($objResult->RecordCount() == $this->arrConfig['paging_limit']['value']) {
            $query = "SELECT SUM(`count`) AS `count_sum` FROM `".DBPREFIX."stats_colourdepth`";
            if (($objResult = $objDatabase->Execute($query))) {
                if (!$objResult->EOF) {
                    $this->colourDepthSum = $objResult->fields['count_sum'];
                }
            }
        }

        // get hostname statistics
        $query = "SELECT `hostname`, `count` FROM `".DBPREFIX."stats_hostname`
                ORDER BY `count` DESC
                    ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrHostnames[$objResult->fields['hostname']] = $objResult->fields['count'];
                $this->hostnamesSum += $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }
        if ($objResult->RecordCount() == $this->arrConfig['paging_limit']['value']) {
            $query = "SELECT SUM(`count`) AS `count_sum` FROM `".DBPREFIX."stats_hostname`";
            if (($objResult = $objDatabase->Execute($query))) {
                if (!$objResult->EOF) {
                    $this->hostnamesSum = $objResult->fields['count_sum'];
                }
            }
        }

        // get coutry of origin statistics
        $query = "SELECT `country`, `count` FROM `".DBPREFIX."stats_country`
                ORDER BY `count` DESC
                    ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $this->arrCountries[$objResult->fields['country']] = $objResult->fields['count'];
                $this->countriesSum += $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }
        if ($objResult->RecordCount() == $this->arrConfig['paging_limit']['value']) {
            $query = "SELECT SUM(`count`) AS `count_sum` FROM `".DBPREFIX."stats_country`";
            if (($objResult = $objDatabase->Execute($query))) {
                if (!$objResult->EOF) {
                    $this->countriesSum = $objResult->fields['count_sum'];
                }
            }
        }
    }

    /**
    * Init referer
    *
    * Initialize the referer statistics
    *
    * @access    private
    * @global    ADONewConnection
    */
    function _initReferer() {
        global $objDatabase;

        $query = "SELECT `uri`, `timestamp` FROM `".DBPREFIX."stats_referer`
                ORDER BY `timestamp` DESC
                    ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $lastRefer = array(
                    'uri'       => $objResult->fields['uri'],
                    'timestamp' => $objResult->fields['timestamp']
                );
                array_push($this->arrLastReferer, $lastRefer);
                $objResult->MoveNext();
            }
        }
        $query = "SELECT `uri`, `count` FROM `".DBPREFIX."stats_referer`
                ORDER BY `count` DESC, `uri` ASC
                    ".$this->pagingLimit;
        if (($objResult = $objDatabase->Execute($query))) {
            while (!$objResult->EOF) {
                $topRefer = array(
                    'uri'       => $objResult->fields['uri'],
                    'count'     => $objResult->fields['count']
                );
                array_push($this->arrTopReferer, $topRefer);
                $objResult->MoveNext();
            }
        }
    }

    /**
    * Save settings
    *
    * Save the statistic settings
    *
    * @access    private
    * @global    ADONewConnection
    * @see    _initConfiguration()
    */
    function _saveSettings() {
        global $objDatabase, $_ARRAYLANG;

        $statusMessage = "";

        $makeStatistics = (boolean) isset($_POST['options']['make_statistics']) ? $_POST['options']['make_statistics'] : 0;

        if ($makeStatistics) {
            $countRequests = (boolean) isset($_POST['options']['count_requests']) ? $_POST['options']['count_requests'] : 0;
            $removeRequestsStatus = (boolean) isset($_POST['options']['remove_requests_status']) ? $_POST['options']['remove_requests_status'] : 0;
            $removeRequests = (int) isset($_POST['options']['remove_requests']) ? $_POST['options']['remove_requests'] : $this->arrConfig['remove_requests']['value'];
            $countReferer = (boolean) isset($_POST['options']['count_referer']) ? $_POST['options']['count_referer'] : 0;
            $countHostname = (boolean) isset($_POST['options']['count_hostname']) ? $_POST['options']['count_hostname'] : 0;
            $countCountry = (boolean) isset($_POST['options']['count_country']) ? $_POST['options']['count_country'] : 0;
            $countBrowser = (boolean) isset($_POST['options']['count_browser']) ? $_POST['options']['count_browser'] : 0;
            $countOS = (boolean) isset($_POST['options']['count_operating_system']) ? $_POST['options']['count_operating_system'] : 0;
            $countSpiders = (boolean) isset($_POST['options']['count_spiders']) ? $_POST['options']['count_spiders'] : 0;
            $countSearchTerms = (boolean) isset($_POST['options']['count_search_terms']) ? $_POST['options']['count_search_terms'] : 0;
            $countResolution = (boolean) isset($_POST['options']['count_screen_resolution']) ? $_POST['options']['count_screen_resolution'] : 0;
            $countColour = (boolean) isset($_POST['options']['count_colour_depth']) ? $_POST['options']['count_colour_depth'] : 0;
            $countJavascript = (boolean) isset($_POST['options']['count_javascript']) ? $_POST['options']['count_javascript'] : 0;
            $excludeIdentifyingInfo = (boolean) isset($_POST['options']['exclude_identifying_info']) ? $_POST['options']['exclude_identifying_info'] : 0;
            $onlineTimeoutStatus = (boolean) isset($_POST['options']['online_timeout_status']) ? $_POST['options']['online_timeout_status'] : 0;
            $countVisitorNumber = (boolean) isset($_POST['options']['count_visitor_number']) ? $_POST['options']['count_visitor_number'] : 0;
            $onlineTimeout = (int) isset($_POST['options']['online_timeout']) ? $_POST['options']['online_timeout'] : $this->arrConfig['online_timeout']['value'];
            $reloadBlockTime = (int) isset($_POST['options']['reload_block_time']) ? $_POST['options']['reload_block_time'] : $this->arrConfig['reload_block_time']['value'];
            $pagingLimit = (int) isset($_POST['options']['paging_limit']) ? $_POST['options']['paging_limit'] : $this->arrConfig['paging_limit']['value'];
            $pagingLimitVisitorDetails = (int) isset($_POST['options']['paging_limit_visitor_details']) ? $_POST['options']['paging_limit_visitor_details'] : $this->arrConfig['paging_limit_visitor_details']['value'];

            if ($countRequests != $this->arrConfig['count_requests']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countRequests." WHERE `name` = 'count_requests'";
                $objDatabase->Execute($query);
            }

            if ($removeRequestsStatus != $this->arrConfig['remove_requests']['status']) {
                if (!$removeRequestsStatus) {
                    $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = 0 WHERE `name` = 'remove_requests'";
                    $objDatabase->Execute($query);
                } else {
                    if ($removeRequests < 86400 || $removeRequests > 31536000) {
                        $statusMessage .= $_ARRAYLANG['TXT_REMOVE_REQUESTS_TIMEOUT'].': '.$_ARRAYLANG['TXT_VALUE_IS_NOT_IN_RANGE'];
                    } else {
                        $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = 1, `value` = ".$removeRequests." WHERE `name` = 'remove_requests'";
                        $objDatabase->Execute($query);
                    }
                }
            } else {
                if ($removeRequests < 86400 || $removeRequests > 31536000) {
                    $statusMessage .= $_ARRAYLANG['TXT_REMOVE_REQUESTS_TIMEOUT'].': '.$_ARRAYLANG['TXT_VALUE_IS_NOT_IN_RANGE'];
                } else {
                    $query = "UPDATE `".DBPREFIX."stats_config` SET `value` = ".$removeRequests." WHERE `name` = 'remove_requests'";
                    $objDatabase->Execute($query);
                }
            }

            if ($onlineTimeoutStatus != $this->arrConfig['online_timeout']['status']) {
                // status wurde ge�ndert
                if (!$onlineTimeoutStatus) {
                    // deaktiviert
                    $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = 0 WHERE `name` = 'online_timeout'";
                    $objDatabase->Execute($query);
                } else {
                    // aktiviert
                    if ($onlineTimeout < 60 || $onlineTimeout > 3600) {
                        $statusMessage .= $_ARRAYLANG['TXT_ONLINE_TIMEOUT'].': '.$_ARRAYLANG['TXT_VALUE_IS_NOT_IN_RANGE'];
                    } else {
                        $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = 1, `value` = ".$onlineTimeout." WHERE `name` = 'online_timeout'";
                        $objDatabase->Execute($query);
                    }
                }
            } else {
                if ($onlineTimeout < 60 || $onlineTimeout > 3600) {
                    $statusMessage .= $_ARRAYLANG['TXT_ONLINE_TIMEOUT'].': '.$_ARRAYLANG['TXT_VALUE_IS_NOT_IN_RANGE'];
                } else {
                    $query = "UPDATE `".DBPREFIX."stats_config` SET `value` = ".$onlineTimeout." WHERE `name` = 'online_timeout'";
                    $objDatabase->Execute($query);
                }
            }


            if ($pagingLimit != $this->arrConfig['paging_limit']['value']) {
                if ($pagingLimit < 1 || $pagingLimit > 100) {
                    $statusMessage .= $_ARRAYLANG['TXT_PAGING_LIMIT'].': '.$_ARRAYLANG['TXT_VALUE_IS_NOT_IN_RANGE'];
                } else {
                    $query = "UPDATE `".DBPREFIX."stats_config` SET `value` = ".$pagingLimit." WHERE `name` = 'paging_limit'";
                }
                $objDatabase->Execute($query);
            }

            if ($pagingLimitVisitorDetails != $this->arrConfig['paging_limit_visitor_details']['value']) {
                if ($pagingLimitVisitorDetails < 1 || $pagingLimitVisitorDetails > 1000) {
                    $statusMessage .= $_ARRAYLANG['TXT_PAGING_LIMIT_VISITOR_DETAILS'].': '.$_ARRAYLANG['TXT_VALUE_IS_NOT_IN_RANGE'];
                } else {
                    $query = "UPDATE `".DBPREFIX."stats_config` SET `value` = ".$pagingLimitVisitorDetails." WHERE `name` = 'paging_limit_visitor_details'";
                }
                $objDatabase->Execute($query);
            }

            if ($reloadBlockTime != $this->arrConfig['reload_block_time']['value']) {
                if ($reloadBlockTime < 1800 || $reloadBlockTime > 86400) {
                    $statusMessage .= $_ARRAYLANG['TXT_RELOAD_BLOCK_TIMEOUT'].': '.$_ARRAYLANG['TXT_VALUE_IS_NOT_IN_RANGE'];
                } else {
                    $query = "UPDATE `".DBPREFIX."stats_config` SET `value` = ".$reloadBlockTime." WHERE `name` = 'reload_block_time'";
                    $objDatabase->Execute($query);
                }
            }


            if ($makeStatistics != $this->arrConfig['make_statistics']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$makeStatistics." WHERE `name` = 'make_statistics'";
                $objDatabase->Execute($query);
            }
            if ($countReferer != $this->arrConfig['count_referer']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countReferer." WHERE `name` = 'count_referer'";
                $objDatabase->Execute($query);
            }
            if ($countHostname != $this->arrConfig['count_hostname']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countHostname." WHERE `name` = 'count_hostname'";
                $objDatabase->Execute($query);
            }
            if ($countCountry != $this->arrConfig['count_country']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countCountry." WHERE `name` = 'count_country'";
                $objDatabase->Execute($query);
            }
            if ($countBrowser != $this->arrConfig['count_browser']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countBrowser." WHERE `name` = 'count_browser'";
                $objDatabase->Execute($query);
            }
            if ($countOS != $this->arrConfig['count_operating_system']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countOS." WHERE `name` = 'count_operating_system'";
                $objDatabase->Execute($query);
            }
            if ($countSpiders != $this->arrConfig['count_spiders']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countSpiders." WHERE `name` = 'count_spiders'";
                $objDatabase->Execute($query);
            }
            if ($countSearchTerms != $this->arrConfig['count_search_terms']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countSearchTerms." WHERE `name` = 'count_search_terms'";
                $objDatabase->Execute($query);
            }
            if ($countResolution != $this->arrConfig['count_screen_resolution']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countResolution." WHERE `name` = 'count_screen_resolution'";
                $objDatabase->Execute($query);
            }
            if ($countColour != $this->arrConfig['count_colour_depth']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countColour." WHERE `name` = 'count_colour_depth'";
                $objDatabase->Execute($query);
            }
            if ($countJavascript != $this->arrConfig['count_javascript']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countJavascript." WHERE `name` = 'count_javascript'";
                $objDatabase->Execute($query);
            }
            if ($excludeIdentifyingInfo != $this->arrConfig['exclude_identifying_info']['status']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$excludeIdentifyingInfo." WHERE `name` = 'exclude_identifying_info'";
                $objDatabase->Execute($query);
            }
            if ($countVisitorNumber != $this->arrConfig['count_visitor_number']) {
                $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = ".$countVisitorNumber." WHERE `name` = 'count_visitor_number'";
                $objDatabase->Execute($query);
            }
        } else {
            $query = "UPDATE `".DBPREFIX."stats_config` SET `status` = 0 WHERE `name` = 'make_statistics'";
            $objDatabase->Execute($query);
        }

        // reinitialize configuration
        $this->_initConfiguration();
        return $statusMessage;
    }

    function _deleteStatistics() {
        global $objDatabase, $_ARRAYLANG;

        $statusMessage = "";

        if (isset($_POST['statistics'])) {
            foreach ($_POST['statistics'] as $type) {
                switch ($type) {
                    case 'requests':
                        $query = "DELETE FROM `".DBPREFIX."stats_visitors_summary`";
                        if ($objDatabase->Execute($query)) {
                            $query = "DELETE FROM `".DBPREFIX."stats_requests_summary`";
                            if ($objDatabase->Execute($query)) {

                                $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_VISITORS_AND_PAGE_VIEWS']."<br />";
                                break;
                            }
                        }
                        $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_VISITORS_AND_PAGE_VIEWS']."<br />";
                        break;

                    case 'visitors':
                        $query = "DELETE FROM `".DBPREFIX."stats_visitors`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_VISITOR_DETAIL_FROM_TODAY']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_VISITOR_DETAIL_FROM_TODAY']."<br />";
                        }
                        break;

                    case 'hostname':
                        $query = "DELETE FROM `".DBPREFIX."stats_hostname`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_DOMAIN']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_DOMAIN']."<br />";
                        }
                        break;

                    case 'country':
                        $query = "DELETE FROM `".DBPREFIX."stats_country`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_COUNTRIES_OF_ORIGIN']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_COUNTRIES_OF_ORIGIN']."<br />";
                        }
                        break;

                    case 'mvp':
                        $query = "DELETE FROM `".DBPREFIX."stats_requests`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_MOST_POPULAR_PAGES']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_MOST_POPULAR_PAGES']."<br />";
                        }
                        break;

                    case 'referer':
                        $query = "DELETE FROM `".DBPREFIX."stats_referer`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_REFERER']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_REFERER']."<br />";
                        }
                        break;

                    case 'browsers':
                        $query = "DELETE FROM `".DBPREFIX."stats_browser`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_BROWSERS']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_BROWSERS']."<br />";
                        }
                        break;

                    case 'os':
                        $query = "DELETE FROM `".DBPREFIX."stats_operatingsystem`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_OPERATING_SYSTEMS']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_OPERATING_SYSTEMS']."<br />";
                        }
                        break;

                    case 'search':
                        $query = "DELETE FROM `".DBPREFIX."stats_search`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_SEARCH_TERMS']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_SEARCH_TERMS']."<br />";
                        }
                        break;

                    case 'spiders':
                        $query = "DELETE FROM `".DBPREFIX."stats_spiders_summary`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_SEARCH_ENGINES']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_SEARCH_ENGINES']."<br />";
                        }
                        break;

                    case 'indexedPages':
                        $query = "DELETE FROM `".DBPREFIX."stats_spiders`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_INDEXED_PAGES']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_INDEXED_PAGES']."<br />";
                        }
                        break;

                    case 'resolution':
                        $query = "DELETE FROM `".DBPREFIX."stats_screenresolution`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_SCREEN_RESOLUTION']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_SCREEN_RESOLUTION']."<br />";
                        }
                        break;

                    case 'colour':
                        $query = "DELETE FROM `".DBPREFIX."stats_colourdepth`";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_COLOUR_DEPTH']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_COLOUR_DEPTH']."<br />";
                        }
                        break;

                    case 'javascript':
                        $query = "UPDATE `".DBPREFIX."stats_javascript` SET `count` = 0";
                        if ($objDatabase->Execute($query)) {
                            $statusMessage .= "".$_ARRAYLANG['TXT_STATISTIC_DELETED_SUCCESSFULLY']." ".$_ARRAYLANG['TXT_JAVASCRIPT_SUPPORT']."<br />";
                        } else {
                            $statusMessage .= "".$_ARRAYLANG['TXT_COULD_NOT_DELETE_STATISTIC']." ".$_ARRAYLANG['TXT_JAVASCRIPT_SUPPORT']."<br />";
                        }
                        break;
                }
            }
        }
        return $statusMessage;
    }


    /**
     * Get javascript code for the jqplot chart.
     *
     * @param   string  $id
     * @param   array   $data
     * @return  string  $code
     */
    protected function getChartJavascriptBy($id, $data, $renderer = '')
    {
        global $_ARRAYLANG;

        \JS::activate('jquery-jqplot');

        $ticks    = json_encode($data['ticks']);
        $dates    = json_encode($data['dates']);
        $visitors = json_encode($data['visitors']);
        $requests = json_encode($data['requests']);

        if ($renderer == 'bar') {
            $seriesDefault = '
                stackSeries: true,
                seriesDefaults: {
                    renderer: $J.jqplot.BarRenderer,
                    rendererOptions: {
                        barWidth: 100
                    }
                },
            ';
        } else {
            $seriesDefault = '
                seriesDefaults: {
                    lineWidth: 2,
                    markerOptions: {
                        size: 6
                    }
                },
            ';
        }


        $code = '
            <!--[if lt IE 9]>
            <script type="text/javascript" src="../lib/javascript/jquery/plugins/jqplot/excanvas.min.js"></script>
            <![endif]-->
            <script type="text/javascript">
            $J(document).ready(function() {
                var ticks        = '.$ticks.';
                var dates        = '.$dates.';
                var visitors     = '.$visitors.';
                var requests     = '.$requests.';
                var labels       = [\''.$_ARRAYLANG['TXT_VISITOR'].'\', \''.$_ARRAYLANG['TXT_PAGE_VIEWS'].'\'];
                var data         = new Array();
                data[\'dates\']  = dates;
                data[\'labels\'] = labels;

                var plot = $J.jqplot(\''.$id.'\', [visitors, requests], {
                    '.$seriesDefault.'
                    seriesColors: [\'#4EAA09\', \'#0C90D0\'],
                    grid: {
                        gridLineColor: \'#EFEFEF\',
                        background: \'#FFFFFF\',
                        borderColor: \'#EFEFEF\',
                        shadow: false
                    },
                    legend: {
                        show: true,
                        labels: labels,
                        placement: \'insideGrid\'
                    },
                    axes: {
                        xaxis: {
                            renderer: $J.jqplot.CategoryAxisRenderer,
                            tickRenderer: $J.jqplot.CanvasAxisTickRenderer,
                            ticks: ticks,
                            tickOptions: {
                              angle: -90,
                              fontSize: \'10px\',
                              showGridline: false
                            }
                        }
                    },
                    highlighter: {
                        data: data,
                        show: true,
                        showMarker: false,
                        tooltipAxes: \'data\',
                        tooltipLocation:  \'n\'
                    }
                });
            });
            </script>
        ';

        return $code;
    }


    /**
     * Get hourly statistics data.
     *
     * @param   string  $param  (hour, day, month or year)
     * @return  array(
     *              ticks,
     *              dates,
     *              visitors,
     *              requests
     *          )
     */
    protected function getStatsDataBy($param)
    {
        global $_CORELANG, $objDatabase;

        $arrRange = $this->getStatsRangeBy($param);

        $arrMonths   = explode(',', $_CORELANG['TXT_MONTH_ARRAY']);
        $arrDays     = explode(',', $_CORELANG['TXT_DAY_ARRAY']);
        $arrDays[7]  = $arrDays[0];
        unset($arrDays[0]);

        $statsData   = $this->getStatsDataSummaryBy($param);
        $arrVisitors = $statsData['visitors'];
        $arrRequests = $statsData['requests'];

        $ticks       = array();
        $visitors    = array();
        $requests    = array();
        $i = 1;

        foreach ($arrRange as $unit => $date) {
            $ticks[]   = $date['tick'];
            $timestamp = $date['timestamp'];

            switch ($param) {
                case 'day':
                    $dates[$i]  = $arrDays[date('N', $timestamp)].', '.date('j', $timestamp).'. '.$arrMonths[date('n', $timestamp) - 1].' '.date('Y', $timestamp);
                    break;
                case 'month':
                    $dates[$i]  = $arrMonths[date('n', $timestamp) - 1].' '.date('Y', $timestamp);
                    break;
                case 'year':
                    $dates[$i]  = date('Y', $timestamp);
                    break;
                case 'hour':
                default:
                    $dates[$i]  = date('H', $timestamp).':00';
                    break;
            }

            $visitors[] = isset($arrVisitors[$unit]) ? intval($arrVisitors[$unit]) : 0;
            $requests[] = isset($arrRequests[$unit]) ? intval($arrRequests[$unit]) : 0;

            $i++;
        }

        return array(
            'ticks'    => $ticks,
            'dates'    => $dates,
            'visitors' => $visitors,
            'requests' => $requests,
        );
    }


    /**
     * Get statistics range by parameter.
     *
     * @param   string  $param  (hour, day, month or year)
     * @return  array   $arrRange
     */
    private function getStatsRangeBy($param)
    {
        global $objDatabase;

        switch ($param) {
            case 'day':
                $rangeStart = date('j', strtotime('last month')) + 1;
                $rangeStart = $rangeStart > 31 ? 1 : $rangeStart;
                $rangeEnd   = date('j');
                $arrRange   = array();

                if ($rangeStart >= $rangeEnd) {
                    $timestamp = strtotime('last month');
                    $first     = range($rangeStart, date('t', $timestamp));
                    $month     = date('M', $timestamp);
                    $year      = date('Y', $timestamp);
                    foreach ($first as $day) {
                        $arrRange[$day] = array(
                            'tick'      => $day . ' ' . $month,
                            'timestamp' => strtotime($day . ' ' . $month . ' ' . $year),
                        );
                    }

                    $second = range(1, $rangeEnd);
                    $month  = date('M');
                    $year   = date('Y');
                    foreach ($second as $day) {
                        $arrRange[$day] = array(
                            'tick'      => $day . ' ' . $month,
                            'timestamp' => strtotime($day . ' ' . $month . ' ' . $year),
                        );
                    }
                } else {
                    $arrDays = range($rangeStart, $rangeEnd);
                    $month = date('M');
                    $year  = date('Y');
                    foreach ($arrDays as $day) {
                        $arrRange[$day] = array(
                            'tick'      => $day . ' ' . $month,
                            'timestamp' => strtotime($day . ' ' . $month . ' ' . $year),
                        );
                    }
                }
                break;
            case 'month':
                $rangeStart = date('n', strtotime('-23 months'));
                $rangeEnd   = date('n');
                $arrRange   = array();

                if ($rangeStart != 1) { // Range starts 2 years ago
                    $first = range($rangeStart, 12);
                    $year  = date('Y', strtotime('-2 years'));
                    foreach ($first as $month) {
                        $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
                        $month     = date('m', mktime(0, 0, 0, $month, 1, $year));
                        $arrRange[$month . '-' . $year] = array(
                            'tick'      => $monthName . ' ' . $year,
                            'timestamp' => strtotime($monthName . ' ' . $year),
                        );
                    }
                }

                $second = range(1, 12);
                $year   = date('Y', strtotime('last year'));
                foreach ($second as $month) {
                    $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
                    $month     = date('m', mktime(0, 0, 0, $month, 1, $year));
                    $arrRange[$month . '-' . $year] = array(
                        'tick'      => $monthName . ' ' . $year,
                        'timestamp' => strtotime($monthName . ' ' . $year),
                    );
                }

                $third = range(1, $rangeEnd);
                $year  = date('Y');
                foreach ($third as $month) {
                    $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
                    $month     = date('m', mktime(0, 0, 0, $month, 1, $year));
                    $arrRange[$month . '-' . $year] = array(
                        'tick'      => $monthName . ' ' . $year,
                        'timestamp' => strtotime($monthName . ' ' . $year),
                    );
                }
                break;
            case 'year':
                $query = '
                    SELECT `timestamp`
                    FROM `'.DBPREFIX.'stats_visitors_summary`
                    WHERE `type` = "year"
                ';
                $objResult = $objDatabase->Execute($query);
                $arrRange = array();

                if ($objResult !== false) {
                    while (!$objResult->EOF) {
                        $year = date('Y', $objResult->fields['timestamp']);
                        $arrRange[$year] = array(
                            'tick'      => $year,
                            'timestamp' => $objResult->fields['timestamp'],
                        );
                        $objResult->MoveNext();
                    }
                    ksort($arrRange);
                }

                if (empty($arrRange)) {
                    $year = date('Y');
                    $arrRange[$year] = array(
                        'tick'      => $year,
                        'timestamp' => strtotime('now'),
                    );
                }
                break;
            case 'hour':
            default:
                $rangeStart = date('G', strtotime('-23 hours'));
                $rangeEnd   = date('G');
                $arrRange   = array();

                if ($rangeStart != 0) {
                    $first     = range($rangeStart, 23);
                    $timestamp = strtotime('-1 day');
                    $day       = date('j', $timestamp);
                    $month     = date('M', $timestamp);
                    $year      = date('Y', $timestamp);
                    foreach ($first as $hour) {
                        $hour = date('H', mktime($hour));
                        $arrRange[$hour] = array(
                            'tick'      => $hour . ':00',
                            'timestamp' => strtotime(
                                $day . ' ' . $month . ' ' . $year . ' ' . $hour . ':00'
                            ),
                        );
                    }

                    $second = range(0, $rangeEnd);
                    $day    = date('j');
                    $month  = date('M');
                    $year   = date('Y');
                    foreach ($second as $hour) {
                        $hour = date('H', mktime($hour));
                        $arrRange[$hour] = array(
                            'tick'      => $hour.':00',
                            'timestamp' => strtotime(
                                $day . ' ' . $month . ' ' . $year .' '. $hour . ':00'
                            ),
                        );
                    }
                } else {
                    $arrHours = range($rangeStart, $rangeEnd);
                    $day      = date('j');
                    $month    = date('M');
                    $year     = date('Y');
                    foreach ($arrHours as $hour) {
                        $hour = date('H', mktime($hour));
                        $arrRange[$hour] = array(
                            'tick'      => $hour.':00',
                            'timestamp' => strtotime(
                                $day . ' ' . $month . ' ' . $year . ' ' . $hour . ':00'
                            ),
                        );
                    }
                }
                break;
        }

        return $arrRange;
    }


    /**
     * Get visitors and requests data.
     *
     * @param   string  $param  (hour, day, month or year)
     * @return  array(
     *              visitors,
     *              requests
     *          )
     */
    private function getStatsDataSummaryBy($param)
    {
        global $objDatabase;

        $select    = '';
        $timestamp = '';

        switch ($param) {
            case 'day':
                $format = 'j';
                $timestamp = '
                    AND `timestamp` >= "'.
                    mktime(
                        0, 0, 0,
                        $previousMonth = date('m') == 1 ? 12 : date('m') - 1,
                        date('d') == date('t', mktime(0, 0, 0, $previousMonth, 0, $previousYear = (date('m') == 1 ? date('Y') - 1 : date('Y')))) ? date('d') + 1 : 1,
                        $previousYear
                    ).'"
                ';
                break;
            case 'month':
                $format = 'm';
                $timestamp = '
                    AND `timestamp` >= "'.
                    strtotime('-23 months').'"
                ';
                break;
            case 'year':
                $format = 'Y';
                break;
            case 'hour':
            default:
                $format = 'H';
                $timestamp = '
                    AND `timestamp` >= "'.
                    strtotime('-23 hours').'"
                ';
                break;
        }

        $query = '
            SELECT `timestamp`, `count`
            FROM `'.DBPREFIX.'stats_visitors_summary`
            WHERE `type` = "'.$param.'"
            '.$timestamp.'
        ';
        $objResult = $objDatabase->Execute($query);

        $arrVisitors = array();
        if ($objResult !== false) {
            if ($param == 'month') {
                while (!$objResult->EOF) {
                    $month = date($format, $objResult->fields['timestamp']);
                    $year  = date('Y', $objResult->fields['timestamp']);
                    $arrVisitors[$month.'-'.$year] = $objResult->fields['count'];
                    $objResult->MoveNext();
                }
            } else {
                while (!$objResult->EOF) {
                    $key = date($format, $objResult->fields['timestamp']);
                    $arrVisitors[$key] = $objResult->fields['count'];
                    $objResult->MoveNext();
                }
            }
        }

        $query = '
            SELECT `timestamp`, `count`
            FROM `'.DBPREFIX.'stats_requests_summary`
            WHERE `type` = "'.$param.'"
            '.$timestamp.'
        ';
        $objResult = $objDatabase->Execute($query);

        $arrRequests = array();
        if ($objResult !== false) {
            if ($param == 'month') {
                while (!$objResult->EOF) {
                    $month = date($format, $objResult->fields['timestamp']);
                    $year  = date('Y', $objResult->fields['timestamp']);
                    $arrRequests[$month.'-'.$year] = $objResult->fields['count'];
                    $objResult->MoveNext();
                }
            } else {
                while (!$objResult->EOF) {
                    $key = date($format, $objResult->fields['timestamp']);
                    $arrRequests[$key] = $objResult->fields['count'];
                    $objResult->MoveNext();
                }
            }
        }

        return array(
            'visitors' => $arrVisitors,
            'requests' => $arrRequests,
        );
    }
}
