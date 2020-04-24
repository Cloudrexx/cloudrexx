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
 * Statistics
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version 1.0.3
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\Stats\Controller;

// detect system location
$depth = 4;
if (strpos(__FILE__, 'customizing/') !== false) {
    // this files resides within the customizing directory, therefore we'll have to strip
    // out one directory more than usually
    $depth++;
}
if (strpos(__FILE__, 'codeBases/') !== false) {
    // this files resides in a codeBase directory, therefore we'll have to strip
    // out two directory more than usually
    $depth += 2;
}
$contrexx_path = dirname(__FILE__, $depth);

/**
 * @ignore
 */
require_once($contrexx_path . '/core/Core/init.php');
$cx = init('minimal');

// those variables get defined in Data/spiders.inc.php, Data/referers.inc.php, Data/banned.inc.php
global $objDatabase, $objDb, $arrBannedWords, $arrRobots;
$objDatabase = $cx->getDb()->getAdoDb();
$objDb = $objDatabase;

// the following includes are required, because they only contain a array definition
// that can't be loaded automatically by the classloader
/**
 * @ignore
 */
$cx->getClassLoader()->loadFile($cx->getCoreModuleFolderName().'/Stats/Data/spiders.inc.php');

/**
 * @ignore
 */
$cx->getClassLoader()->loadFile($cx->getCoreModuleFolderName().'/Stats/Data/referers.inc.php');

/**
 * @ignore
 */
$cx->getClassLoader()->loadFile($cx->getCoreModuleFolderName().'/Stats/Data/banned.inc.php');


$counter = new \Cx\Core_Modules\Stats\Controller\Counter($arrRobots, $arrBannedWords, $cx);

/**
 * Counter
 *
 * This class counts unique users, page visits, set client infos, referer
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.3
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 */
class Counter
{
    public $currentTime = 0;
    public $md5Id = 0;
    public $requestedUrl = "";

    public $spiderAgent = false;
    public $totUsersMonth=0;
    public $totPageViewsMonth=0;

    public $screenResolution = "";
    public $colorDepth = 0;
    public $javascriptEnabled = 0;
    public $pageId = 0;

    public $isNewVisitor = false;

    public $arrBannedWords = array();
    public $arrSpider = array();

    public $arrConfig = array();
    public $arrClient = array();
    public $arrProxy = array();

    public $currentDate = 0;

    public $referer = "";
    public $refererBlocked = false;
    public $externalSearchTerm = "";

    public $searchTerm = "";
    public $mobilePhone = "";

    protected $cx;

    /**
    * Constructor
    *
    * Set the requested url,client infos,onlineusers and count users and spiders
    *
     * @param array $arrRobots
     * @param array $arrBannedWords
    */
    public function __construct($arrRobots, $arrBannedWords, $cx)
    {
        global $_GET;

        $this->cx = $cx;
        $this->_initConfiguration();

        // make statistics only if they were activated
        if ($this->arrConfig['make_statistics']['status']) {
            //set spider and banned words
            $this->arrSpider = $arrRobots;
            $this->arrBannedWords = $arrBannedWords;

            $this->_checkCallMethod();
            $this->_getRequestedUrl();
            $this->_getClientInfos();

            //if referer is blocked, don't count him....
            if (!$this->refererBlocked) {
                // count spider
                if (!$this->spiderAgent) {
                    // count visitor
                    $this->_countVisitor();

                    // if visitor was counted, then make statistics
                    if ($this->isNewVisitor) {

                        // generate visitor statistics
                        $this->_makeStatistics(DBPREFIX.'stats_visitors_summary');

                        // count host
                        if ($this->arrClient['hostname'] != "" && $this->arrConfig['count_hostname']['status']) {
                            $this->_countHostname();
                        }

                        // count country
                        if ($this->arrClient['country'] != "" && $this->arrConfig['count_country']['status']) {
                            $this->_countCountry();
                        }

                        // count browser
                        if ($this->arrConfig['count_browser']['status']) {
                            $this->_countBrowser();
                        }

                        // count operating system
                        if ($this->arrConfig['count_operating_system']['status']) {
                            $this->_countOperatingSystem();
                        }

                        // count screen resolution
                        if ($this->arrConfig['count_screen_resolution']['status'] && $this->javascriptEnabled) {
                            $this->_countScreenResolution();
                        }

                        // count colour depth
                        if ($this->arrConfig['count_colour_depth']['status'] && $this->javascriptEnabled) {
                            $this->_countColourDepth();
                        }

                        // count javascript
                        if ($this->arrConfig['count_javascript']['status']) {
                            $this->_countJavascript();
                        }
                    }

                    // count request
                    $this->_countRequest();

                    // count referer
                    if ($this->arrConfig['count_referer']['status'] && !empty($this->referer)) {
                        $this->_countReferer();
                    }

                    // count internal search term
                    if ($this->arrConfig['count_search_terms']['status'] && strlen($this->searchTerm)>0) {
                        $this->_countSearchquery($this->searchTerm, '0');
                    }

                    // count external search term
                    if ($this->arrConfig['count_search_terms']['status'] && strlen($this->externalSearchTerm)>0) {
                        $this->_countSearchquery($this->externalSearchTerm, '1');
                    }
                }
            }
        }

        // output content-type based on request method (javascript / image-tag)
        $mode = isset($_GET['mode']) ? $_GET['mode'] : '';
        if ($mode == 'script') {
            header('Content-Type: application/javascript');
        } else {
            // output Transparent 1x1 GIF pixel
            header('Content-Type: image/gif');
            echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
        }

        exit;
    }

    /**
    * Initialize configuration
    *
    * Initialize the configuration for the counter and statistics
    *
    * @global    ADONewConnection
    */
    function _initConfiguration() {
        global $objDb;

        $query = "SELECT `name`, `value`, `status` FROM `".DBPREFIX."stats_config`";
        $result = $objDb->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $this->arrConfig[$arrResult['name']] = array('value' => $arrResult['value'], 'status' => $arrResult['status']);
            }
        }
        $this->currentTime = time();
        $this->currentDate = date('d-m-Y');
    }

    /**
    * Check call method
    *
    * Check if the client support javascript and get display informations from the client
    */
    function _checkCallMethod() {
        if (isset($_GET['mode'])) {
            $mode = $_GET['mode'];
            if ($mode == "script") {
                $this->pageId = intval($_GET['pageId']);
                $this->screenResolution = !empty($_GET['screen']) ? (get_magic_quotes_gpc() ? $_GET['screen'] : addslashes($_GET['screen'])) : '';
                $this->colorDepth = !empty($_GET['color_depth']) ? intval($_GET['color_depth']) : 0;
                $this->javascriptEnabled = 1;
            }
        }
        if (isset($_GET['searchTerm']) && !empty($_GET['searchTerm'])) {
            $this->searchTerm = urldecode($_GET['searchTerm']);
        }
    }

    /**
    * Get requested page
    *
    * Get the requested page from the $_GET vars. If a session has started, this var will be blocked
    * and not in the uri
    *
    */
    function _getRequestedUrl() {
        $uriString="";
        $isAlias = false;

        if (isset($_SERVER['HTTP_REFERER']) && preg_match("/".$_SERVER['HTTP_HOST']."/",$_SERVER['HTTP_REFERER'])) {
            if (strpos($_SERVER['HTTP_REFERER'], '?') === false) {
                $completeUriString = substr(strrchr($_SERVER['HTTP_REFERER'], "/"),1);
                $isAlias = true;
            } else {
                //check domain and return GET-String after ?
                $completeUriString = substr(strstr($_SERVER['HTTP_REFERER'], "?"),1);
            }

            //creates an array for each GET-pair
            $arrUriGets = explode("&", $completeUriString);

            foreach ($arrUriGets AS $elem) {
                //avoid multiple entries for same request:
                //check if Session-ID is traced by url (cookies are disabled)
                //also skip the csrf and caching parameter (they are appended by the backend's preview link)
                if (!preg_match("/(PHPSESSID|csrf|caching)/",$elem)) {
                    if ($elem != "") {
                        $uriString .="&".$elem;
                    }
                }
            }
        }

        foreach ($this->arrBannedWords as $blockElem) {
            $blockElem = trim($blockElem);
            //some blocked words in get-Vars?
            if (preg_match("=".$blockElem."=",$uriString) && ($blockElem <>"")) {
                $uriString = "";
            }
        }

        if ($uriString == "") { // only uninteresting vars in uri (faked?)
            $this->requestedUrl = "/index.php";
        } elseif ($isAlias) {
            $this->requestedUrl = "/".addslashes(substr($uriString,1));
        } else {
            $this->requestedUrl = "/index.php?".(get_magic_quotes_gpc() ? substr($uriString,1) : addslashes(substr($uriString,1)));
        }
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

        $this->arrClient['language'] = htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE'], ENT_QUOTES, CONTREXX_CHARSET);

        $this->_getProxyInformations(); // get also the client ip

        $net = $this->cx->getComponent('Net');
        $this->arrClient['host'] = $net->getHostByAddr($this->arrClient['ip']);
        if ($this->arrClient['host'] == $this->arrClient['ip']) { // is remote host available?
            $this->arrClient['host'] = '';
        } else {
            $arrStrHost = explode(".",$this->arrClient['host']);
            if (count($arrStrHost)>=3) {
                $this->arrClient['hostname'] = $arrStrHost[count($arrStrHost)-2].'.'.$arrStrHost[count($arrStrHost)-1];
                $this->arrClient['country'] = $arrStrHost[count($arrStrHost)-1];
            } else {
                $this->arrClient['hostname'] = "";
                $this->arrClient['country'] = "";
            }
        }

        $this->_getReferer();
        $this->_checkForSpider();
        $this->_checkMobilePhone();

        // Anonymize if necessary
        if ($this->arrConfig['exclude_identifying_info']['status']) {
            // Exclude the least significant part from the hostname or ip address
            $this->arrClient['ip'] = preg_replace('/[0-9a-fA-F]+$/', '*', $this->arrClient['ip']);
            $this->arrClient['host'] = preg_replace('/^[a-zA-Z0-9-_]+\./', '*.', $this->arrClient['host']);
        }

        $this->md5Id = md5($this->arrClient['ip'].$this->arrClient['useragent'].$this->arrClient['language'].$this->arrProxy['ip'].$this->arrProxy['host']);
    }

    /**
    * Get proxy informations
    *
    * Determines if a proxy is used or not. If so, then proxy information are colleted
    */
    function _getProxyInformations() {
        if (isset($_SERVER['HTTP_VIA']) && $_SERVER['HTTP_VIA']) { // client does use a proxy
            $this->arrProxy['ip'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
            $net = $this->cx->getComponent('Net');
            $this->arrProxy['host'] = $net->getHostByAddr($this->arrProxy['ip']);
            $proxyUseragent = trim(addslashes(urldecode(strstr($_SERVER['HTTP_VIA'],' '))));
            $startPos = strpos($proxyUseragent,"(");
            $this->arrProxy['useragent'] = substr($proxyUseragent,$startPos+1);
            $endPos=strpos($this->arrProxy['useragent'],")");
            $this->arrProxy['useragent'] = substr($this->arrProxy['useragent'],0,$endPos-1);

            if ($this->arrProxy['host'] == $this->arrProxy['ip']) { // no hostname found, try to take it out from useragent-infos
                $endPos = strpos($proxyUseragent,"(");
                $this->arrProxy['host'] = substr($proxyUseragent,0,$endPos);
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->arrClient['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if ($_SERVER['HTTP_X_FORWARDED_FOR'] == $_SERVER['HTTP_VIA']) {
                    $this->arrProxy['type'] = 2; // Simple Anonymous Proxy
                } else {
                    $this->arrProxy['type'] = 1; // Transparent or Distorting Proxy
                }
            } else {
                $this->arrProxy['type'] = 3; // High Anonymous Proxy
                if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $this->arrClient['ip'] = $_SERVER['HTTP_CLIENT_IP'];
                } else {
                    $this->arrClient['ip'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
                }
            }
        } else { // Client does not use proxy
            $this->arrClient['ip'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
            $this->arrProxy['type'] = 0; // No proxy
            $this->arrProxy['ip'] = "";
            $this->arrProxy['host'] = "";
            $this->arrProxy['useragent'] = "";
        }

        foreach ($this->arrClient as &$client) {
            $client = contrexx_raw2db(strip_tags($client));
        }
        foreach ($this->arrProxy as &$proxy) {
            $proxy = contrexx_raw2db(strip_tags($proxy));
        }
    }

    /**
    * Get referer
    *
    * Get the referer of the request
    */
    function _getReferer() {
        global $arrBannedReferers;

        if (isset($_GET['referer']) && !preg_match("/".$_SERVER['HTTP_HOST']."/",$_GET['referer'])) {
            $referer = urldecode($_GET['referer']);

            // get external search term
            $this->_getExternalSearchQuery($referer);

            $referer = strtolower(htmlspecialchars($referer, ENT_QUOTES, CONTREXX_CHARSET));
            foreach ($arrBannedReferers as $refBlock) {
                $refblock = trim($refBlock);
                if (preg_match("=".$refblock."=",$referer) && ($refblock <> '')) {
                    $this->refererBlocked = true;
                    break;
                }
            }

            if ((strlen($referer) >= 12) && (preg_match("/http:/",$referer)) && (!preg_match("/unknown/i",$referer)) && ($this->refererBlocked == false)) {
                $this->referer = $referer;
            }
        }
    }

    /**
    * get external search query
    *
    * extracts the search term of a query that was typed in at a search machine
    *
    * @access    private
    * @param    string    $referer
    * @global    array    $arrReferers
    */
    function _getExternalSearchQuery($referer)
    {
        global $arrReferers;

        $arrMatches = array();
        foreach ($arrReferers as $refererRegExp) {
            if (preg_match($refererRegExp, $referer, $arrMatches)) {
                $this->externalSearchTerm = urldecode($arrMatches[1]);
            }
        }
    }

    /**
    * Count visitor
    *
    * Count the visitor if he was not here within the reload block time, otherwise update this timestamp to now
    *
    * @global    ADONewConnection
    * @return    boolean  result
    */
    function _countVisitor() {
        global $objDb;

        $query = "SELECT `timestamp` FROM `".DBPREFIX."stats_visitors` WHERE `sid` = '".$this->md5Id."'";
        $objResult = $objDb->Execute($query);
        if ($objResult->RecordCount() == 1) {
            if ($objResult->fields['timestamp'] < $this->currentTime - $this->arrConfig['reload_block_time']['value']) {
                $this->isNewVisitor = true;
            }
            $query = "UPDATE `".DBPREFIX."stats_visitors` SET `timestamp` = '".$this->currentTime."' WHERE `sid` = '".$this->md5Id."'";
            $objDb->Execute($query);
        } else {
            $this->isNewVisitor = true;
            $query = "INSERT INTO `".DBPREFIX."stats_visitors` (
                                  `sid`,
                                  `timestamp`,
                                  `client_ip`,
                                  `client_host`,
                                  `client_useragent`,
                                  `proxy_ip`,
                                  `proxy_host`,
                                  `proxy_useragent`
                                  ) VALUES (
                                  '".$this->md5Id."',
                                  '".$this->currentTime."',
                                  '".$this->arrClient['ip']."',
                                  '".$this->arrClient['host']."',
                                  '".$this->arrClient['useragent']."',
                                  '".$this->arrProxy['ip']."',
                                  '".$this->arrProxy['host']."',
                                  '".$this->arrProxy['useragent']."')";
            $objDb->Execute($query);
        }
    }

    /**
    * Count host
    *
    * Count the host name
    *
    * @access    private
    * @global    ADONewConnection
    */
    function _countHostname() {
        global $objDb;

        $query = "UPDATE `".DBPREFIX."stats_hostname` SET `count` = `count` + 1 WHERE `hostname` = '".strtolower(substr($this->arrClient['hostname'],0,255))."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_hostname` (`hostname`, `count`) VALUES ('".strtolower(substr($this->arrClient['hostname'],0,255))."', 1)";
            $objDb->Execute($query);
        }
    }

    /**
    * Count country
    *
    * Count the country
    *
    * @access    private
    * @global    ADONewConnection
    */
    function _countCountry() {
        global $objDb;

        $query = "UPDATE `".DBPREFIX."stats_country` SET `count` = `count` + 1 WHERE `country` = '".strtolower(substr($this->arrClient['country'],0,100))."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_country` (`country`, `count`) VALUES ('".strtolower(substr($this->arrClient['country'],0,100))."', 1)";
            $objDb->Execute($query);
        }
    }

    function _checkMobilePhone() {
        // list in mobile-useragents.inc is outdated -> mobile operating systems are now listed in operatingsystems.inc
        return false;

        // check for mobilephone
        /*$fp = fopen('Data/mobile-useragents.inc',"r");
        while (true) {
            $line = fgets($fp);
            if ($line === false) break;
            $arrUserAgent = explode("\t",$line);
            if (!strcasecmp(trim($this->arrClient['useragent']),trim($arrUserAgent[2]))) {
                $this->mobilePhone = $arrUserAgent[0].' '.$arrUserAgent[1];
                break;
            }
        }
        fclose($fp);*/
    }

    /**
    * Count browser
    *
    * Count the browser type/version
    *
    * @global   ADONewConnection
    * @see    _getBrowser()
    */
    function _countBrowser(){
        global $objDb;

        $browser = !empty($this->mobilePhone) ? $this->mobilePhone : $this->_getBrowser();
        $query = "UPDATE `".DBPREFIX."stats_browser` SET `count` = `count` + 1 WHERE `name` = '".substr($browser,0,255)."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_browser` (`name`, `count`) VALUES ('".substr($browser,0,255)."', 1)";
            $objDb->Execute($query);
        }
    }

    /**
    * Get browser name
    *
    * Read out the browser name from the user agent and returns it
    *
    * @return    string    browser name
    */
    function _getBrowser()
    {
        global $arrBrowserRegExps, $arrBrowserNames;

        $userAgent = $this->arrClient['useragent'];
        $arrBrowserRegExps = array();
        $arrBrowserNames = array();
        $arrBrowser = array();
        $this->cx->getClassLoader()->loadFile($this->cx->getCoreModuleFolderName().'/Stats/Data/useragents.inc.php');

        if (!empty($arrBrowserRegExps)) {
            foreach ($arrBrowserRegExps as $browserRegExp) {
                if (preg_match($browserRegExp, $userAgent, $arrBrowser)) {
                    if (isset($arrBrowserNames[$arrBrowser[1]])) {
                        $arrBrowser[1] = $arrBrowserNames[$arrBrowser[1]];
                    }
                    return $arrBrowser[1].' '.$arrBrowser[2];
                    break;
                }
            }
        }
        return '';
    }

    /**
    * Count operating system
    *
    * Count the operating system of the client
    *
    * @global    ADONewConnection
    * @see     _getOperatingSytem()
    */
    function _countOperatingSystem(){
        global $objDb;

        $operatingSystem = !empty($this->mobilePhone) ? $this->mobilePhone : $this->_getOperatingSystem();
        $query = "UPDATE `".DBPREFIX."stats_operatingsystem` SET `count` = `count` + 1 WHERE `name` = '".substr($operatingSystem,0,255)."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_operatingsystem` (`name`, `count`) VALUES ('".substr($operatingSystem,0,255)."', 1)";
            $objDb->Execute($query);
        }
    }

    /**
    * Get operating system name
    *
    * Read out the operating sytem name from the user agent and returns it
    * @return    string    operating system name
    */
    function _getOperatingSystem()
    {
        global $arrOperatingSystems;

        $operationgSystem = '';
        $userAgent = $this->arrClient['useragent'];
        $arrOperatingSystems = array();
        $this->cx->getClassLoader()->loadFile($this->cx->getCoreModuleFolderName().'/Stats/Data/operatingsystems.inc.php');

        if (!empty($arrOperatingSystems)) {
            foreach ($arrOperatingSystems as $arrOperatingSystem) {
                if (preg_match($arrOperatingSystem['regExp'], $userAgent)) {
                    $operationgSystem = $arrOperatingSystem['name'];
                    break;
                }
            }
        }
        return $operationgSystem;
    }

    function _countScreenResolution() {
        global $objDb;

        $query = "UPDATE `".DBPREFIX."stats_screenresolution` SET `count` = `count` + 1 WHERE `resolution` = '".$this->screenResolution."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_screenresolution` (`resolution`, `count`) VALUES ('".$this->screenResolution."', 1)";
            $objDb->Execute($query);
        }
    }

    function _countColourDepth() {
        global $objDb;

        $query = "UPDATE `".DBPREFIX."stats_colourdepth` SET `count` = `count` + 1 WHERE `depth` = '".$this->colorDepth."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "INSERT INTO `".DBPREFIX."stats_colourdepth` (`depth`, `count`) VALUES ('".$this->colorDepth."', 1)";
            $objDb->Execute($query);
        }
    }

    function _countJavascript() {
        global $objDb;

        $objDb->Execute('UPDATE `'.DBPREFIX.'stats_javascript` SET `count` = `count`+1 WHERE `support` = "'.$this->javascriptEnabled.'"');
        if ($objDb->Affected_Rows() == 0) {
            $objDb->Execute('INSERT INTO `'.DBPREFIX.'stats_javascript` (`support`, `count`) VALUES ("'.$this->javascriptEnabled.'", 1)');
        }
    }


    /**
    * Count request
    *
    * @global   ADONewConnection
    * @see      _makeStatistics()
    */
    function _countRequest()
    {
        global $objDb;

        $page = \Env::get('em')->getRepository('\Cx\Core\ContentManager\Model\Entity\Page')->findOneBy(array('id' => $this->pageId));
        if (!$page) {
            return;
        }
        $url = \Cx\Core\Routing\Url::fromPage($page);
        if ($page) {
            $objDb->Execute('
                UPDATE `'.DBPREFIX.'stats_requests`
                SET `visits` = `visits`+1, `page` = "'.substr('/'.$url->getLangDir().'/'.$url->getPath(), 0, 255).'", `pageTitle` = "'.$page->getTitle().'", `sid` = "'.$this->md5Id.'", `timestamp` = '.$this->currentTime.'
                WHERE `pageId` = '.$this->pageId.' AND ((`sid` != "'.$this->md5Id.'") OR (`timestamp` <= '.($this->currentTime - $this->arrConfig['reload_block_time']['value']).'))
            ');
            if ($objDb->Affected_Rows() == 0) {
                // this is allowed to fail if page was visited with same sid
                try {
                    $objDb->Execute('
                        INSERT INTO `'.DBPREFIX.'stats_requests` (`sid`, `pageId`, `page`, `timestamp`, `visits`, `pageTitle`)
                        VALUES ("'.$this->md5Id.'", '.$this->pageId.', "'.substr('/'.$url->getLangDir().'/'.$url->getPath(), 0, 255).'", '.$this->currentTime.', 1, "'.$page->getTitle().'")
                    ');
                } catch (\PDOException $e) {}
            }
        }
        $this->_makeStatistics(DBPREFIX.'stats_requests_summary');
    }

    /**
    * Make statistics
    *
    * Makes the requests and the visitors statistics, depending on what was specified in the $dbTable variable
    *
    * @access    private
    * @param    string    $dbTable    The table name which should be used (either DBPREFIX.'stats_visitors_summary' or DBPREFIX.'stats_requests_summary')
    * @return    boolean    false if the table $dbTable isn't valid, otherwise true
    */
    function _makeStatistics($dbTable) {
        global $objDb;

        $arrTables = array(DBPREFIX.'stats_visitors_summary', DBPREFIX.'stats_requests_summary');

        if (!in_array($dbTable,$arrTables)) {
            return false;
        }

        $arrStats = array(
            'hour'    => array(
                'id'        => 0,
                'timestamp'    => mktime(date('H'),0,0,date('m'),date('d'),date('Y'))
                ),
            'day'    => array(
                'id'        => 0,
                'timestamp'    => mktime(0,0,0,date('m'),date('d'),date('Y'))
                ),
            'month'    => array(
                'id'        => 0,
                'timestamp'    => mktime(0,0,0,date('m'),1,date('Y'))
                ),
            'year'    => array(
                'id'        => 0,
                'timestamp'    => mktime(0,0,0,1,1,date('Y'))
                )
        );

        $insertValues = "";
        $updateValues = "";

        // get stats
        $query = "SELECT `id`, `type`
                    FROM `".$dbTable."`
                   WHERE (`type` = 'hour' AND `timestamp` >= '".$arrStats['hour']['timestamp']."')
                      OR (`type` = 'day' AND `timestamp` >= '".$arrStats['day']['timestamp']."')
                      OR (`type` = 'month' AND `timestamp` >= '".$arrStats['month']['timestamp']."')
                      OR (`type` = 'year' AND `timestamp` >= '".$arrStats['year']['timestamp']."')";
        $result = $objDb->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrStats[$arrResult['type']]['id'] = $arrResult['id'];
            }
        }

        // generate sql queries
        foreach ($arrStats as $type => $arrValues) {
            if ($arrValues['id'] == 0) {
                $insertValues .= "('".$type."', '".($arrStats[$type]['timestamp'])."', 1),";
            } else {
                $updateValues .= "(`type` = '".$type."' AND `timestamp` >= '".$arrStats[$type]['timestamp']."') OR ";
            }
        }

        // update stats
        if (strlen($updateValues)>0) {
            $query = "UPDATE `".$dbTable."`
                         SET `count` = `count` + 1
                       WHERE ".substr($updateValues,0,strlen($updateValues)-3);
            $objDb->Execute($query);
        }
        if (strlen($insertValues)>0) {
            $query = "INSERT INTO `".$dbTable."` (`type`, `timestamp`, `count`) VALUES ".substr($insertValues,0,strlen($insertValues)-1);
            $objDb->Execute($query);
        }

        return true;
    }

    /**
    * Check for spider
    *
    * Check if the user agent is a spider
    */
    function _checkForSpider() {
        foreach ($this->arrSpider as $spider) {
            $spiderName = trim($spider);
            if (preg_match("=".$spiderName."=",$this->arrClient['useragent'])) {
                $this->spiderAgent = true;
                break;
            }
        }
    }

    function _countSearchquery($searchTerm, $external) {
        global $objDb;
        $searchTerm = addslashes(substr($searchTerm,0,100));
        $query = "UPDATE `".DBPREFIX."stats_search` SET `count` = `count` + 1, `sid` = '".$this->md5Id."' WHERE `name` = '".$searchTerm."' AND `sid` != '".$this->md5Id."' AND `external` = '".$external."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "SELECT `id` FROM `".DBPREFIX."stats_search` WHERE `name` = '".$searchTerm."' AND `external` = '".$external."'";
            $objDb->Execute($query);
            if ($objDb->Affected_Rows() == 0) {
                $query = "INSERT INTO `".DBPREFIX."stats_search` (`name`, `count`, `sid`, `external`) VALUES ('".$searchTerm."', 1, '".$this->md5Id."', '".$external."')";
                $objDb->Execute($query);
            }
        }
    }

    function _countReferer() {
        global $objDb;

        $query = "UPDATE `".DBPREFIX."stats_referer` SET `count` = `count` + 1, `timestamp` = '".$this->currentTime."', `sid` = '".$this->md5Id."' WHERE `uri` = '".substr($this->referer,0,255)."' AND `sid` != '".$this->md5Id."'";
        $objDb->Execute($query);
        if ($objDb->Affected_Rows() == 0) {
            $query = "SELECT `id` FROM `".DBPREFIX."stats_referer` WHERE `uri` = '".substr($this->referer,0,255)."'";
            $objDb->Execute($query);
            if ($objDb->Affected_Rows() == 0) {
                $query = "INSERT INTO `".DBPREFIX."stats_referer` (`uri`, `timestamp`, `count`, `sid`) VALUES ('".substr($this->referer,0,255)."', '".$this->currentTime."', 1, '".$this->md5Id."')";
                $objDb->Execute($query);
            }
        }
    }
}
