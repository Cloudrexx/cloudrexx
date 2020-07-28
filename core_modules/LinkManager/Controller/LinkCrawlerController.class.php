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
    const NAVIGATION_QUERY = '//*[contains(@id,\'navigation\') or contains(@class, \'navigation\')]';

    /**
     * constant MiB2 2megabytes
     */
    const MiB2 = 2097152;

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
     * @var int
     */
    protected $memoryLimit;

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

            $crawler = new \Cx\Core_Modules\LinkManager\Model\Entity\Crawler();
            $crawler->setLang($this->langId);
            $crawler->setStartTime($runStartTime);
            $crawler->setEndTime($runStartTime);
            $crawler->setTotalLinks(0);
            $crawler->setTotalBrokenLinks(0);
            $crawler->setRunStatus(self::RUN_STATUS_RUNNING);
            $this->em->persist($crawler);
            $this->em->flush();

            //If the sitemap file not exists for the $langName then return
            $sitemapPath = ASCMS_DOCUMENT_ROOT.'/sitemap_'.$this->langName.'.xml';
            if (!file_exists($sitemapPath)) {
                $this->updateCrawlerStatus($crawler, self::RUN_STATUS_INCOMPLETE);
                \DBG::log('No sitemap found for language '.$this->langName.'. Please save a page so the sitemap can be build.');
                return;
            }

            //Read the sitemap file and get all the static page urls
            $sitemapXml = simplexml_load_file($sitemapPath);
            foreach($sitemapXml->children() as $child) {
                foreach($child as $value) {
                    if ($value->getName() !== 'loc') {
                        continue;
                    }
                    $page = $this->getPageByUrl((string) $value);
                    if (!$page || $page->getType() !== self::TYPE_CONTENT) {
                        continue;
                    }
                    $this->initializeScript((string) $value, $page->getId());
                    if (!$this->checkMemoryLimit(self::MiB2)) {
                        $this->updateCrawlerStatus($crawler, self::RUN_STATUS_INCOMPLETE);
                        die(); // memory limit exceeded
                    }
                }
            }

            //move the uncalled links from link table to history table
            $this->moveOldLinksToHistory($runStartTime);
            //get the total links and total broken links 
            $totalLinks       = $this->linkRepo->getLinksCountByLang($runStartTime->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME), $this->langId);
            $totalBrokenLinks = $this->linkRepo->brokenLinkCountByLang($this->langId);

            $crawler->updateEndTime();
            $crawler->setTotalLinks($totalLinks);
            $crawler->setTotalBrokenLinks($totalBrokenLinks);
            $crawler->setRunStatus(self::RUN_STATUS_COMPLETED);
            $this->em->flush();
        } catch (\Exception $error) {
            $this->updateCrawlerStatus('', self::RUN_STATUS_INCOMPLETE);
            die('Error occurred'. $error);
        }
    }

    /**
     * Crawling initialization script
     *
     * @param string  $url         lead url
     * @param integer $referPageId lead page id
     *
     * @return null
     */
    public function initializeScript($url, $referPageId)
    {
        global $_ARRAYLANG;

        //store the $url details
        $this->storeUrlInfos($url, $url, $referPageId, $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_LINK']);

        //Fetch all the links('a' and 'img')
        $links = $this->getPageLinks($url);
        foreach ($links as $link => $linkText) {
            $this->storeUrlInfos($link, $url, $referPageId, $linkText);
        }
    }

    /**
     * Copy the non-detected links from link table to history table
     *
     * @param \DateTime $startTime
     *
     * @return null
     */
    public function moveOldLinksToHistory(\DateTime $startTime)
    {
        try {
            //Get all the non-detected links by crawler start time and lang
            $links = $this->linkRepo
                          ->getNonDetectedLinks($startTime->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME), $this->langId);
            //If there is no link detected then return
            if (!$links) {
                return;
            }

            foreach ($links as $link) {
                $this->copyLinkToHistory($link);
                //removed from link table after moved to history table
                $this->em->remove($link);
            }
            $this->em->flush();
        } catch(\Exception $error) {
            $this->updateCrawlerStatus('', self::RUN_STATUS_INCOMPLETE);
            die("Error occured:" . $error);
        }
    }

    /**
     * Get all the page links from the given url
     *
     * @param string $url referer page url
     *
     * @return array array of the links
     */
    public function getPageLinks($url)
    {
        global $_ARRAYLANG;

        //If the argument $url is empty then return
        if (empty($url)) {
            return;
        }

        $urlResponse = $this->getUrlResponse($url);
        //If the referer page response is empty, return
        if (!($urlResponse instanceof \HTTP_Request2_Response)) {
            return;
        }

        $htmlDom = new \DOMDocument();
        libxml_use_internal_errors(true);
        //If loadHTML fails, then return
        if (!$htmlDom->loadHTML($urlResponse->getBody())) {
            return;
        }
        libxml_use_internal_errors(false);
        $htmlXpath = new \DOMXPath($htmlDom);

        //remove the navigation menu
        $navHtml = $htmlXpath->query(self::NAVIGATION_QUERY);
        if ($navHtml instanceof \DOMNodeList && $navHtml->length) {
            foreach ($navHtml as $navNodes) {
                $navNodes->parentNode->removeChild($navNodes);
            }
        }

        $links = array();
        $imagesAndLinks = $htmlXpath->query('//img | //a');
        if (!($imagesAndLinks instanceof \DOMNodeList) || !($imagesAndLinks->length)) {
            return $links;
        }

        foreach ($imagesAndLinks as $domElement) {
            $isImage = $domElement->tagName == 'img';
            $urlPath = $domElement->getAttribute( $isImage ? 'src' : 'href' );
            if ($isImage && !preg_match('#\.(jpg|jpeg|gif|png)$# i', $urlPath)) {
                continue;
            }
            $fixPath = $this->getController('Url')->checkPath($urlPath, $url);
            if ($this->isLinkProcessed($fixPath)) {
                continue;
            }
            $tagValue = $isImage ? $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_IMAGE']
                                 : $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_LINK'];
            if (!$isImage && $domElement->nodeValue) {
                $tagValue = $domElement->nodeValue;
            }
            $links[$fixPath] = $tagValue;
        }

        return $links;
    }

    /**
     * Check the url is already processed
     *
     * @param string $url requested link
     *
     * @return boolean
     */
    public function isLinkProcessed($url)
    {
        if (empty($url) || in_array($url, $this->linkArray)) {
            return true;
        }
        $this->linkArray[] = $url;
        return false;
    }

    /**
     * Store each crawl result to database
     *
     * @param String  $requestedUrl     the requested url
     * @param String  $refererUrl       the lead url
     * @param Integer $referPageId      the lead url page id
     * @param String  $requestedUrlText the requested url text
     *
     * @return null
     */
    public function storeUrlInfos($requestedUrl, $refererUrl, $referPageId, $requestedUrlText)
    {
        global $_CONFIG;

        $urlStatus = $this->getUrlStatus($requestedUrl);

        //Check the requested url is internal or not
        $isInternalLink = $this->getController('Url')->isInternalUrl($requestedUrl);

        //find the entry name, module name, action and parameter
        $moduleName = $moduleAction = $moduleParams = '';
        if ($isInternalLink) {
            list($entryTitle, $moduleName, $moduleAction, $moduleParams) = $this->getModuleDetails($requestedUrl, $refererUrl);
        } else {
            $objRefererUrl = $this->getPageByUrl($refererUrl);
            $entryTitle    = $objRefererUrl ? $objRefererUrl->getTitle() : '';
        }

        //Get the backend referer url by referPageId
        if (!empty($referPageId)) {
            $backendReferUrl = ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/cadmin/index.php?cmd=ContentManager&page='.$referPageId;
        }

        $link = $this->linkRepo->findOneBy(array('requestedPath' => $requestedUrl));
        if (   $link
            && $link->getRefererPath() == $refererUrl
            && $link->getLinkStatusCode() != $urlStatus
        ) {
            $this->copyLinkToHistory($link);
        }

        $persist = false;
        if (!$link) {
            $link    = new \Cx\Core_Modules\LinkManager\Model\Entity\Link();
            $persist = true;
        }

        $link->setLang($this->langId);
        $link->setBrokenLinkText($requestedUrlText);
        $link->setRefererPath($refererUrl);
        $link->setRequestedPath($requestedUrl);
        $link->setLeadPath($backendReferUrl);
        $link->setEntryTitle($entryTitle);
        $link->setDetectedTime(new \DateTime('now'));

        $link->setUpdatedBy(0);
        $link->setLinkStatusCode($urlStatus);
        $link->setFlagStatus(($urlStatus == 200) ? 1 : 0);
        $link->setLinkRecheck(0);
        $link->setRequestedLinkType($isInternalLink ? 'internal' : 'external');
        $link->setLinkStatus(0);

        if ($persist) {
            $this->em->persist($link);
        }
        $this->em->flush();
    }

    /**
     * get the module details(Modulename, module action, parameters and title)
     *
     * @param string  $requestedUrl requested link
     * @param string  $refererUrl   lead link
     * 
     * @return Array
     */
    public function getModuleDetails($requestedUrl, $refererUrl)
    {
        $matches      = array();
        $moduleName   = '';
        $moduleAction = '';
        $moduleParams = '';
        $page         = $this->getPageByUrl($requestedUrl);
        preg_match('#\?(.*)#', $requestedUrl, $matches); 
        if ($page) {
            $moduleName   = $page->getModule();
            $moduleAction = $page->getCmd();
            $moduleParams = array_key_exists('1', $matches) ? $matches[1] : '';
        } elseif (array_key_exists('1', $matches)) {
            $pathArray = explode('&', $matches[1]);
            foreach ($pathArray as $key => $val) {
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

        $objRefererUrl = $this->getPageByUrl($refererUrl);
        $entryTitle    = $objRefererUrl ? $objRefererUrl->getTitle() : '';
        //for image or pdf
        if ((preg_match('#\.(jpg|jpeg|gif|png|pdf)$# i', $requestedUrl)) && $objRefererUrl) {
            $moduleAction = $objRefererUrl->getCmd();
            $mdlName      = $objRefererUrl->getModule();
            $moduleName   = empty($mdlName) ? 'ContentManager' : $mdlName;
        }

        return array($entryTitle, $moduleName, $moduleAction, $moduleParams);
    }

    /**
     * Get the page object by url
     * 
     * @param string $url requested url
     *
     * @return boolean|object
     */
    public function getPageByUrl($url)
    {
        $objUrl  = new \Cx\Core\Routing\Url($url);
        $result  = $this->pageRepo
                        ->getPagesAtPath(
                            $this->langName . '/' . $objUrl->getSuggestedTargetPath(),
                            null,
                            $this->langId,
                            false
                        );
        if ($result['page']) {
            return $result['page'];
        }

        return false;
    }

    /**
     * Checking memory limit
     * 
     * @param type $requiredMemoryLimit required memory limit
     * 
     * @return boolean
     */
    function checkMemoryLimit($requiredMemoryLimit)
    {
        if (empty($this->memoryLimit)) {
            $memoryLimit = \FWSystem::getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
            //if memory limit is empty then set default php memory limit of 8MiBytes
            $this->memoryLimit = !empty($memoryLimit) ? $memoryLimit : self::MiB2 * 4;
        }

        $potentialRequiredMemory = memory_get_usage() + $requiredMemoryLimit;
        if ($potentialRequiredMemory > $this->memoryLimit) {
            // try to set a higher memory_limit
            if (!@ini_set('memory_limit', $potentialRequiredMemory)) {
                \DBG::log('The link spider script is interrupted due to insufficient memory is available.');
                return false;
            }
        }

        return true;
    }

    /**
     * Update the crawler status
     *
     * @param \Cx\Core_Modules\LinkManager\Model\Entity\Crawler $crawler Crawler instance
     * @param string                                            $status  Crawler status
     */
    public function updateCrawlerStatus(\Cx\Core_Modules\LinkManager\Model\Entity\Crawler $crawler, $status)
    {
        $crawlerStartTime = $crawler->getStartTime()->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME);
        //Update the crawler's totalLinks, totalBrokenLinks and status
        $totalLinks       = $this->linkRepo->getLinksCountByLang($crawlerStartTime, $this->langId);
        $totalBrokenLinks = $this->linkRepo->getDetectedBrokenLinksCount($crawlerStartTime, $this->langId);
        $crawler->updateEndTime();
        $crawler->setRunStatus($status);
        $crawler->setTotalLinks($totalLinks);
        $crawler->setTotalBrokenLinks($totalBrokenLinks);
        $this->em->flush();
    }

    /**
     * Copy the Link instance to history
     *
     * @param \Cx\Core_Modules\LinkManager\Model\Entity\Link    $link
     * @param boolean                                           $flushToDb
     */
    public function copyLinkToHistory(\Cx\Core_Modules\LinkManager\Model\Entity\Link $link, $flushToDb = false)
    {
        $history = new \Cx\Core_Modules\LinkManager\Model\Entity\History();

        $historyMetaData = $this->em->getClassMetadata(get_class($history));
        $linkMetaData    = $this->em->getClassMetadata(get_class($link));
        $primaryField    = $historyMetaData->getSingleIdentifierFieldName();
        $historyMethods  = get_class_methods($history);
        foreach ($historyMetaData->getColumnNames() as $column) {
            if ($primaryField == $column) {
                continue;
            }
            $field        = $historyMetaData->getFieldName($column);
            $value        = $linkMetaData->getFieldValue($link, $field);
            $toMethodName = 'set'.ucfirst($column);
            if (in_array($toMethodName, $historyMethods)) {
                $history->{$toMethodName}($value);
            }
        }
        $this->em->persist($history);
        if ($flushToDb) {
            $this->em->flush();
        }
    }

    /**
     * Checks before the crawler is triggerd, for crawling entries,
     * which aren't running anymore, but still have a status of "running".
     * The Status of those entries will be changed to "incomplete"
     */
    protected function changeRunningCrawlingToIncomplete()
    {
        $crawlings = $this->crawlerRepo->findBy(array('runStatus' => self::RUN_STATUS_RUNNING));

        if (!$crawlings || !count($crawlings)) {
            return;
        }

        foreach($crawlings as $crawlRun) {
            $crawlRun->setRunStatus(self::RUN_STATUS_INCOMPLETE);
        }
        $this->em->flush();
    }
}
