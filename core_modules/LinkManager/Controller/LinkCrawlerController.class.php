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
 * LinkCrawlerController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Controller;

/**
 * LinkCrawlerControllerException
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class LinkCrawlerControllerException extends \Exception {}

/**
 * Using the class LinkCrawlerController to find all the links and its status in the site.
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

class LinkCrawlerController extends \Cx\Core\Core\Model\Entity\Controller {

    const TYPE_CONTENT     = 'content';
    const RUN_STATUS_INCOMPLETE = 'incomplete';
    const RUN_STATUS_COMPLETED = 'completed';
    const RUN_STATUS_RUNNING = 'running';

    /**
     * Em instance
     * @var \Doctrine\ORM\EntityManager em
     */
    private $em           = null;

    /**
     * Page instance
     * @var \Cx\Core\ContentManager\Model\Entity\Page $pageRepo
     */
    private $pageRepo     = null;

    /**
     * CrawlerRepository instance
     * @var \Cx\Core_Modules\LinkManager\Model\Repository\CrawlerRepository $crawlerRepo
     */
    private $crawlerRepo  = null;

    /**
     * LinkRepository instance
     * @var \Cx\Core_Modules\LinkManager\Model\Repository\LinkRepository $linkRepo
     */
    private $linkRepo     = null;

    /**
     * HistoryRepository instance
     * @var \Cx\Core_Modules\LinkManager\Model\Repository\HistoryRepository $historyRepo
     */
    private $historyRepo  = null;

    /**
     * language id
     * @var integer
     */
    private $langId       = null;

    /**
     * language name
     * @var string
     */
    private $langName     = null;

    /**
     * link array
     * @var array
     */
    private $linkArray    = array();

    /**
     * Constructor
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController
     * @param \Cx\Core\Core\Controller\Cx                          $cx
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx)
    {
        parent::__construct($systemComponentController, $cx);
    }

    /**
     * Load the Crawler
     *
     * @param integer $langId
     * @param string $langName
     */
    public function loadCrawler($langId, $langName)
    {
        $this->langId    = $langId;
        $this->langName  = $langName;
        $this->em        = \Env::get('em');
        if ($this->em) {
            $this->pageRepo    = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $this->crawlerRepo = $this->em->getRepository('Cx\Core_Modules\LinkManager\Model\Entity\Crawler');
            $this->linkRepo    = $this->em->getRepository('Cx\Core_Modules\LinkManager\Model\Entity\Link');
            $this->historyRepo = $this->em->getRepository('Cx\Core_Modules\LinkManager\Model\Entity\History');
        }

        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/SimpleHtmlDom.php');

        // checks if there are some incomplete crawling entries,
        // which have still a status of "running"
        $this->changeRunningCrawlingToIncomplete();
        // start crawler
        $this->crawlerSpider();
    }

    /**
     * Crawler spider -> crawl all the links present in the sitemap file.
     *
     * @return null
     */
    public function crawlerSpider()
    {
        try {
            //initialize
            $runStartTime = new \DateTime('now');
            $inputArray = array(
                'lang'             => contrexx_raw2db($this->langId),
                'startTime'        => $runStartTime,
                'endTime'          => $runStartTime,
                'totalLinks'       => 0,
                'totalBrokenLinks' => 0,
                'runStatus'        => contrexx_raw2db(self::RUN_STATUS_RUNNING)
            );
            $lastInsertedRunId = $this->modifyCrawler($inputArray);

            $request     = new \HTTP_Request2();
            $sitemapPath = ASCMS_DOCUMENT_ROOT.'/sitemap_'.$this->langName.'.xml';
            if (file_exists($sitemapPath)) {
                $sitemapXml = simplexml_load_file($sitemapPath);
                foreach($sitemapXml->children() as $child) {
                    foreach($child as $value) {
                        if ($value->getName() == 'loc') {
                            $page = $this->isModulePage((string) $value);
                            if ($page && $page->getType() == self::TYPE_CONTENT) {
                                $this->initializeScript((string) $value, $request, $page->getId());
                                $this->checkMemoryLimit($lastInsertedRunId);
                                //$this->checkTimeoutLimit($lastInsertedRunId);
                            }
                        }
                    }
                }
            } else {
                $this->updateCrawlerStatus($lastInsertedRunId, self::RUN_STATUS_INCOMPLETE);
                \DBG::log('No sitemap found for language '.$this->langName.'. Please save a page so the sitemap can be build.');
                return;
            }

            //move the uncalled links from link table to history table
            $this->updateHistory($this->langId, $lastInsertedRunId);
            //get the total links and total broken links
            $totalLinks       = $this->linkRepo->getLinksCountByLang($runStartTime->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME), $this->langId);
            $totalBrokenLinks = $this->linkRepo->brokenLinkCountByLang($this->langId);

            //save the run details
            $crawlerRuns = $this->crawlerRepo->findOneBy(array('id' => $lastInsertedRunId));
            if ($crawlerRuns) {
                $inputArray = array(
                    'lang'              => contrexx_raw2db($this->langId),
                    'startTime'         => $runStartTime,
                    'totalLinks'        => contrexx_raw2db($totalLinks),
                    'totalBrokenLinks'  => contrexx_raw2db($totalBrokenLinks),
                    'runStatus'         => contrexx_raw2db(self::RUN_STATUS_COMPLETED)
                );
                $crawlerRuns->updateEndTime();
                $this->modifyCrawler($inputArray, $crawlerRuns);
            }

        } catch (\Exception $error) {
            $this->updateCrawlerStatus('', self::RUN_STATUS_INCOMPLETE);
            die('Error occurred'. $error);
        }

    }

    /**
     * Move the not detected links in link table to history table
     *
     * @param integer $lang         language id
     * @param integer $currentRunId current crawler run id
     *
     * @return null
     */
    public function updateHistory($lang, $currentRunId)
    {
        try {
            if (empty($lang) || empty($currentRunId)) {
                return;
            }
            $objCrawler = $this->crawlerRepo->findOneBy(array('id' => $currentRunId));
            if ($objCrawler) {
                $nonDetectedLinks = $this->linkRepo->getNonDetectedLinks($objCrawler->getStartTime()->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME), $lang);
                if ($nonDetectedLinks && $nonDetectedLinks->count() > 0) {
                    foreach ($nonDetectedLinks As $nonDetectedLink) {
                        //move the modified link to history table
                        $historyInputValues = array(
                            'lang'              => $nonDetectedLink->getLang(),
                            'requestedPath'     => $nonDetectedLink->getRequestedPath(),
                            'refererPath'       => $nonDetectedLink->getRefererPath(),
                            'leadPath'          => $nonDetectedLink->getLeadPath(),
                            'linkStatusCode'    => $nonDetectedLink->getLinkStatusCode(),
                            'entryTitle'        => $nonDetectedLink->getEntryTitle(),
                            'moduleName'        => $nonDetectedLink->getModuleName(),
                            'moduleAction'      => $nonDetectedLink->getModuleAction(),
                            'moduleParams'      => $nonDetectedLink->getModuleParams(),
                            'detectedTime'      => $nonDetectedLink->getDetectedTime(),
                            'flagStatus'        => $nonDetectedLink->getFlagStatus(),
                            'linkStatus'        => $nonDetectedLink->getLinkStatus(),
                            'linkRecheck'       => $nonDetectedLink->getLinkRecheck(),
                            'updatedBy'         => $nonDetectedLink->getUpdatedBy(),
                            'requestedLinkType' => $nonDetectedLink->getRequestedLinkType(),
                            'brokenLinkText'    => $nonDetectedLink->getBrokenLinkText()
                        );
                        $this->modifyHistory($historyInputValues);

                        //removed from link table after moved to history table
                        $this->em->remove($nonDetectedLink);
                        $this->em->flush();
                    }
                }
            } else {
                return;
            }
        } catch(\Exception $error) {
            $this->updateCrawlerStatus('', self::RUN_STATUS_INCOMPLETE);
            die("Error occured:" . $error);
        }
    }

    /**
     * Crawling initialization script
     *
     * @global object $objInit
     *
     * @param string         $url         lead url
     * @param \HTTP_Request2 $request     http_request object
     * @param integer        $referPageId lead page id
     *
     * @return null
     */
    public function initializeScript($url, \HTTP_Request2 $request, $referPageId)
    {
        global $objInit;

        $_ARRAYLANG = $objInit->loadLanguageData('LinkManager');

        $refererUrlResponse = $this->checkUrlStatus($url, $request);
        $this->storeUrlInfos($request, $url, $url, 0, $referPageId, $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_LINK']);
        if ($refererUrlResponse) {
            $refererUrlBody = $refererUrlResponse->getBody();
            $html           = \str_get_html($refererUrlBody);

            if ($html) {
                //First check the page content href and src
                foreach ($html->find(ASCMS_LINKMANAGER_CONTENT_HREF_QUERY) As $element) {
                    $aHref = \Cx\Core_Modules\LinkManager\Controller\Url::checkPath($element->href, $url);
                    if (!empty($aHref) && $this->isLinkExists($aHref, true)) {
                        $linkText = $element->plaintext ? $element->plaintext : $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_LINK'];
                        $this->storeUrlInfos($request, $aHref, $url, 0, $referPageId, $linkText);
                    }
                }
                foreach ($html->find(ASCMS_LINKMANAGER_CONTENT_IMG_QUERY) As $element) {
                    if (preg_match('#\.(jpg|jpeg|gif|png)$# i', $element->src)) {
                        $imgSrc = \Cx\Core_Modules\LinkManager\Controller\Url::checkPath($element->src, null);
                        if (!empty($imgSrc) && $this->isLinkExists($imgSrc, true)) {
                            $this->storeUrlInfos($request, $imgSrc, $url, 1, $referPageId, $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_IMAGE']);
                        }
                    }
                }
                //remove the page content
                $objPageContent = $html->find(ASCMS_LINKMANAGER_CONTENT_PAGE_QUERY, 0);
                $objPageContent->outertext = '';
                $html = \str_get_html($html->outertext);
                //remove the navigation menu
                $objNavigation = $html->find(ASCMS_LINKMANAGER_NAVIGATION_QUERY, 0);
                $objNavigation->outertext = '';
                $html = \str_get_html($html->outertext);
                // Find all images
                foreach($html->find('img') as $element) {
                    if (preg_match('#\.(jpg|jpeg|gif|png)$# i', $element->src)) {
                        $imgSrc = \Cx\Core_Modules\LinkManager\Controller\Url::checkPath($element->src, null);
                        if (!empty($imgSrc) && $this->isLinkExists($imgSrc)) {
                            $this->storeUrlInfos($request, $imgSrc, $url, 1, $referPageId, $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_IMAGE']);
                        }
                    }
                }

                // Find all links
                foreach($html->find('a') as $element) {
                    $aHref = \Cx\Core_Modules\LinkManager\Controller\Url::checkPath($element->href, $url);
                    if (!empty($aHref) && $this->isLinkExists($aHref)) {
                        $linkText = $element->plaintext ? $element->plaintext : $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_LINK'];
                        $this->storeUrlInfos($request, $aHref, $url, 0, $referPageId, $linkText);
                    }
                }
            }
        } else {
            return;
        }
    }

    /**
     * Check url status
     *
     * @param string         $url     requested page url
     * @param \HTTP_Request2 $request http_request object
     *
     * @return object
     */
    public function checkUrlStatus($url, \HTTP_Request2 $request)
    {
        try {
            $request->setUrl($url);
            // ignore ssl issues
            // otherwise, cloudrexx does not activate 'https' when the server doesn't have an ssl certificate installed
            $request->setConfig(array(
                'ssl_verify_peer'  => false,
                'ssl_verify_host'  => false,
                'follow_redirects' => true,
            ));
            return $request->send();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Check the url is already exist or not
     *
     * @param string  $url    requested link
     * @param boolean $return need to check the link exist or not
     *
     * @return boolean
     */
    public function isLinkExists($url, $return = false)
    {
        if (empty($url)) {
            return;
        }

        if ($return) {
            $this->linkArray[] = $url;
            return true;
        }

        //check the same link already exist or not. if exist means initialize to check next link
        if (!in_array($url, $this->linkArray)) {
            $this->linkArray[] = $url;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Store each crawl result to database
     *
     * @global array $_CONFIG
     *
     * @param \HTTP_Request2 $request          http_request2() object
     * @param String         $requestedUrl     the requested url
     * @param String         $refererUrl       the lead url
     * @param Boolean        $image            the requested url is image or not
     * @param Integer        $referPageId      the lead url page id
     * @param String         $requestedUrlText the requested url text
     *
     * @return null
     */
    public function storeUrlInfos(\HTTP_Request2 $request, $requestedUrl, $refererUrl, $image, $referPageId, $requestedUrlText)
    {
        global $_CONFIG;

        try {
            $request->setUrl($requestedUrl);
            // ignore ssl issues
            // otherwise, cloudrexx does not activate 'https' when the server doesn't have an ssl certificate installed
            $request->setConfig(array(
                'ssl_verify_peer'  => false,
                'ssl_verify_host'  => false,
                'follow_redirects' => true,
            ));
            $response   = $request->send();
            $urlStatus  = $response->getStatus();
        } catch (\Exception $e) {
            $response = true;
            $urlStatus = preg_match('#^[mailto:|javascript:]# i', $requestedUrl) ? 200 : 0;
        }

        if ($response) {

            $internalFlag = \Cx\Core_Modules\LinkManager\Controller\Url::isInternalUrl($requestedUrl);
            $flagStatus   = ($urlStatus == '200') ? 1 : 0;
            $linkType     = $internalFlag ? 'internal' : 'external';

            //find the entry name, module name, action and parameter
            if ($linkType == 'internal') {
                list($entryTitle, $moduleName, $moduleAction, $moduleParams) = $this->getModuleDetails($requestedUrl, $refererUrl, $image);
            } else {
                $objRefererUrl = $this->isModulePage($refererUrl);
                if ($objRefererUrl)
                    $entryTitle    = $objRefererUrl->getTitle();
                $moduleName   = '';
                $moduleAction = '';
                $moduleParams = '';
            }

            if (!empty($referPageId)) {
                $backendReferUrl = ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/cadmin/index.php?cmd=ContentManager&page='.$referPageId;
            }
            //save the link
            $linkInputValues = array(
                'lang'              => contrexx_raw2db($this->langId),
                'requestedPath'     => contrexx_raw2db($requestedUrl),
                'refererPath'       => contrexx_raw2db($refererUrl),
                'leadPath'          => contrexx_raw2db($backendReferUrl),
                'linkStatusCode'    => contrexx_raw2db($urlStatus),
                'entryTitle'        => contrexx_raw2db($entryTitle),
                'moduleName'        => contrexx_raw2db($moduleName),
                'moduleAction'      => contrexx_raw2db($moduleAction),
                'moduleParams'      => contrexx_raw2db($moduleParams),
                'detectedTime'      => new \DateTime('now'),
                'flagStatus'        => contrexx_raw2db($flagStatus),
                'linkStatus'        => 0,
                'linkRecheck'       => 0,
                'updatedBy'         => 0,
                'requestedLinkType' => contrexx_raw2db($linkType),
                'brokenLinkText'    => contrexx_raw2db($requestedUrlText)
            );

            $linkAlreadyExist = $this->linkRepo->findOneBy(array('requestedPath' => $requestedUrl));
            if ($linkAlreadyExist && $linkAlreadyExist->getRefererPath() == $refererUrl) {
                if ($linkAlreadyExist->getLinkStatusCode() != $urlStatus) {
                    //move the modified link to history table
                    $historyInputValues = array(
                        'lang'              => $linkAlreadyExist->getLang(),
                        'requestedPath'     => $linkAlreadyExist->getRequestedPath(),
                        'refererPath'       => $linkAlreadyExist->getRefererPath(),
                        'leadPath'          => $linkAlreadyExist->getLeadPath(),
                        'linkStatusCode'    => $linkAlreadyExist->getLinkStatusCode(),
                        'entryTitle'        => $linkAlreadyExist->getEntryTitle(),
                        'moduleName'        => $linkAlreadyExist->getModuleName(),
                        'moduleAction'      => $linkAlreadyExist->getModuleAction(),
                        'moduleParams'      => $linkAlreadyExist->getModuleParams(),
                        'detectedTime'      => $linkAlreadyExist->getDetectedTime(),
                        'flagStatus'        => $linkAlreadyExist->getFlagStatus(),
                        'linkStatus'        => $linkAlreadyExist->getLinkStatus(),
                        'linkRecheck'       => $linkAlreadyExist->getLinkRecheck(),
                        'updatedBy'         => $linkAlreadyExist->getUpdatedBy(),
                        'requestedLinkType' => $linkAlreadyExist->getRequestedLinkType(),
                        'brokenLinkText'    => $linkAlreadyExist->getBrokenLinkText()
                    );
                    $this->modifyHistory($historyInputValues);
                }
                //add the modified link to the link table
                $this->modifyLink($linkInputValues, $linkAlreadyExist);
            } else {
                //add the link to link table
                $this->modifyLink($linkInputValues);
            }
        } else {
            return;
        }
    }

    /**
     * get the module details(Modulename, module action, parameters and title)
     *
     * @param string  $requestedUrl requested link
     * @param string  $refererUrl   lead link
     * @param integer $image        requested link is image(1) or link(0)
     *
     * @return Array
     */
    public function getModuleDetails($requestedUrl, $refererUrl, $image)
    {
        $matches      = array();
        $moduleName   = '';
        $moduleAction = '';
        $moduleParams = '';
        $page         = $this->isModulePage($requestedUrl);
        preg_match('#\?(.*)#', $requestedUrl, $matches);
        if ($page) {
            $moduleName   = $page->getModule();
            $moduleAction = $page->getCmd();
            $moduleParams = array_key_exists('1', $matches) ? $matches[1] : '';
        } elseif (array_key_exists('1', $matches)) {
            $pathArray = explode('&', $matches[1]);
            foreach ($pathArray As $key => $val) {
                $pathVal = explode('=', $val);
                if ($pathVal[0] == 'section') {
                    if (!empty($pathVal[1])) {
                        $moduleName = $pathVal[1];
                        unset($pathArray[$key]);
                    }
                } else if ($pathVal[0] == 'cmd') {
                    if (!empty($pathVal[1])) {
                        $moduleAction = $pathVal[1];
                        unset($pathArray[$key]);
                    }
                }
            }
            $moduleParams = implode('&', $pathArray);
        }

        $objRefererUrl = $this->isModulePage($refererUrl);
        $entryTitle    = $objRefererUrl->getTitle();
        //for image or pdf
        if (($image || preg_match('#\.(pdf)$# i', $requestedUrl)) && $objRefererUrl) {
            $moduleAction = $objRefererUrl->getCmd();
            $mdlName      = $objRefererUrl->getModule();
            $moduleName   = empty($mdlName) ? 'ContentManager' : $mdlName;
        }

        return array($entryTitle, $moduleName, $moduleAction, $moduleParams);
    }

    /**
     * Add and edit the crawler details
     *
     * @param array $inputArray
     * @param \Cx\Core_Modules\LinkManager\Model\Entity\Crawler $crawler
     *
     * @return integer
     */
    public function modifyCrawler(array $inputArray = array(), $crawler = '')
    {
        try {
            if (empty($inputArray)) {
                return;
            }

            if (empty($crawler)) {
                $crawler  = new \Cx\Core_Modules\LinkManager\Model\Entity\Crawler;
            }
            $crawler->updateFromArray($inputArray);

            $this->em->persist($crawler);
            $this->em->flush();

            return $crawler->getId();
        } catch (\Exception $e) {
            $this->updateCrawlerStatus('', self::RUN_STATUS_INCOMPLETE);
            die('Crawler Query ERROR!'.$e);
        }
    }

    /**
     * Add and edit the link details
     *
     * @param array $inputArray
     * @param \Cx\Core_Modules\LinkManager\Model\Entity\Link $link
     */
    public function modifyLink(array $inputArray = array(), $link = '')
    {
        try {
            if (empty($inputArray)) {
                return;
            }

            if (empty($link)) {
                $link = new \Cx\Core_Modules\LinkManager\Model\Entity\Link;
            }
            $link->updateFromArray($inputArray);

            $this->em->persist($link);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->updateCrawlerStatus('', self::RUN_STATUS_INCOMPLETE);
            die('Link Query ERROR!'.$e);
        }
    }

    /**
     * Add and Edit the history link details
     *
     * @param array $inputArray
     * @param \Cx\Core_Modules\LinkManager\Model\Entity\History $history
     */
    public function modifyHistory(array $inputArray = array(), $history = '')
    {
        try {
            if (empty($inputArray)) {
                return;
            }

            if (empty($history)) {
                $history = new \Cx\Core_Modules\LinkManager\Model\Entity\History;
            }
            $history->updateFromArray($inputArray);

            $this->em->persist($history);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->updateCrawlerStatus('', self::RUN_STATUS_INCOMPLETE);
            die('History Query ERROR!'.$e);
        }
    }

    /**
     * Check if the page is module or content page
     *
     * @param string $url requested url
     *
     * @return boolean|object
     */
    public function isModulePage($url)
    {
        try {
            $url  = new \Cx\Core\Routing\Url($url);
            $path = $url->getSuggestedTargetPath();
        } catch (\Exception $e) {
            $path = '';
        }
        $result = $this->pageRepo->getPagesAtPath($this->langName.'/'.$path, null, $this->langId, false, \Cx\Core\ContentManager\Model\Repository\PageRepository::SEARCH_MODE_PAGES_ONLY);
        if ($result['page']) {
            return $result['page'];
        }
        return false;
    }

    /**
     * Checking memory limit
     *
     * @staticvar integer $memoryLimit
     * @staticvar integer $MiB2
     *
     * @param integer $crawlerId
     *
     * @return boolean
     */
    function checkMemoryLimit($crawlerId)
    {
        static $memoryLimit, $MiB2;

        if (!isset($memoryLimit)) {
            $memoryLimit = \FWSystem::getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
            if (empty($memoryLimit)) {
                // set default php memory limit of 8MiBytes
                $memoryLimit = 8*pow(1024, 2);
            }
            $MiB2 = 2 * pow(1024, 2);
        }
        $potentialRequiredMemory = memory_get_usage() + $MiB2;
        if ($potentialRequiredMemory > $memoryLimit) {
            // try to set a higher memory_limit
            if (!@ini_set('memory_limit', $potentialRequiredMemory)) {
                $this->updateCrawlerStatus($crawlerId, self::RUN_STATUS_INCOMPLETE);
                die('The link spider script is interrupted due to insufficient memory is available.');
            }
        }
        return true;
    }

    /**
     * Checking the timeout limit
     *
     * @staticvar integer $timeLimit
     *
     * @param integer $crawlerId
     *
     * @return boolean
     */
    function checkTimeoutLimit($crawlerId)
    {
        static $timeLimit;

        if (!$timeLimit) {
            $timeLimit = ini_get('max_execution_time');
        }

        if (!empty($timeLimit)) {
            $timeoutTime = PROCESS_TIME + $timeLimit;
        }

        if ($timeoutTime > time()) {
            return true;
        } else {
            $this->updateCrawlerStatus($crawlerId, self::RUN_STATUS_INCOMPLETE);
            die('The link spider script was interrupted because the maximum allowable script execution time has been reached.');
        }

    }

    /**
     * update the crawler status
     *
     * @param integer $id     current crawler run id
     * @param string  $status current crawler run status
     *
     * @return boolean
     */
    public function updateCrawlerStatus($id, $status)
    {
        if (empty($id)) {
            $objCrawler = $this->crawlerRepo->getLastRunByLang($this->langId);
        } else {
            $objCrawler = $this->crawlerRepo->findOneBy(array('id' => $id));
        }

        if ($objCrawler) {
            $totalLinks  = $this->linkRepo->getLinksCountByLang($objCrawler->getStartTime()->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME), $this->langId);
            $totalBrokenLinks = $this->linkRepo->getDetectedBrokenLinksCount($objCrawler->getStartTime()->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME), $this->langId);
            $objCrawler->updateEndTime();
            $objCrawler->setRunStatus($status);
            $objCrawler->setTotalLinks($totalLinks);
            $objCrawler->setTotalBrokenLinks($totalBrokenLinks);
            $this->em->persist($objCrawler);
            $this->em->flush();
            return true;
        }
    }

    /**
     * Checks before the crawler is triggerd, for crawling entries,
     * which aren't running anymore, but still have a status of "running".
     * The Status of those entries will be changed to "incomplete"
     */
    private function changeRunningCrawlingToIncomplete(){
        $crawlings =  $this->crawlerRepo->findBy(array('runStatus' => self::RUN_STATUS_RUNNING));

        if($crawlings && count($crawlings) > 0){
            foreach($crawlings as $crawlRun) {
                $crawlRun->setRunStatus(self::RUN_STATUS_INCOMPLETE);
                $this->em->persist($crawlRun);
            }
            $this->em->flush();
        }
    }
}
