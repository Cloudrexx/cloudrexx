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
 * CrawlerResultController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Controller;

/**
 *
 * CrawlerResultController for displaying the broken links found in the latest link crawler result.
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class CrawlerResultController extends \Cx\Core\Core\Model\Entity\Controller {

    /**
     * Em instance
     * @var \Doctrine\ORM\EntityManager em
     */
    protected $em;

    /**
     * Sigma template instance
     * @var Cx\Core\Html\Sigma  $template
     */
    protected $template;

    /**
     * LinkRepository instance
     * @var \Cx\Core_Modules\LinkManager\Model\Repository\LinkRepository $linkRepository
     */
    protected $linkRepository;

    /**
     * CrawlerRepository instance
     * @var \Cx\Core_Modules\LinkManager\Model\Repository\CrawlerRepository $crawlerRepository
     */
    protected $crawlerRepository;

    /**
     * module name
     * @var string $moduleName
     */
    protected $moduleName = 'LinkManager';

    /**
     * module name for language placeholder
     * @var string $moduleNameLang
     */
    protected $moduleNameLang = 'LINKMANAGER';

    /**
     * Controller for the Backend Crawler Result views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     * @param string                                               $submenu                   the submenu name
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        //check the user permission
        \Permission::checkAccess(1031, 'static');

        parent::__construct($systemComponentController, $cx);
        $this->em                = $this->cx->getDb()->getEntityManager();
        $this->linkRepository    = $this->em->getRepository('Cx\Core_Modules\LinkManager\Model\Entity\Link');
        $this->crawlerRepository = $this->em->getRepository('Cx\Core_Modules\LinkManager\Model\Entity\Crawler');

        //register backend js
        \JS::registerJS('core_modules/LinkManager/View/Script/LinkManagerBackend.js');
        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/SimpleHtmlDom.php');
    }

     /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showCrawlerResult();
    }

    /**
     * Show the last run's crawler result
     *
     * @global array $_ARRAYLANG
     */
    public function showCrawlerResult()
    {
        global $_ARRAYLANG;

        \JS::activate('cx');
        $objCx = \ContrexxJavascript::getInstance();
        $objCx->setVariable(array(
            'updateSuccessMsg'  => $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_UPDATE_SUCCESS_MSG'],
            'loadingLabel'      => $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LABEL_LOADING']
        ), 'LinkManager');

        if (isset($_POST['checkAgain'])) {
            $this->recheckSelectedLinks();
        }

        //show crawler results
        //get parameters
        $pos = isset($_GET['pos']) ? $_GET['pos'] : 0;
        //set the settings value from DB
        \Cx\Core\Setting\Controller\Setting::init('LinkManager', 'config');
        $pageLimit = \Cx\Core\Setting\Controller\Setting::getValue('entriesPerPage', 'LinkManager');
        $parameter = './index.php?cmd='.$this->moduleName.'&act=crawlerResult';
        $this->template->setVariable('ENTRIES_PAGING', \Paging::get($parameter, $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LINKS'], $this->linkRepository->brokenLinkCount(), $pageLimit, true, $pos, 'pos'));
        $brokenLinks = $this->linkRepository->getBrokenLinks($pos, $pageLimit);

        $i = 1;
        $objUser = new \Cx\Core_Modules\LinkManager\Controller\User();
        if ($brokenLinks && $brokenLinks->count() > 0) {
            foreach ($brokenLinks As $brokenLink) {
                $this->template->setVariable(array(
                    $this->moduleNameLang.'_BROKEN_LINK_ID'          => contrexx_raw2xhtml($brokenLink->getId()),
                    $this->moduleNameLang.'_BROKEN_LINK_IMAGE'       => $brokenLink->getBrokenLinkText() == $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_IMAGE'] ? 'brokenImage' : 'brokenLinkImage',
                    $this->moduleNameLang.'_BROKEN_LINK_TEXT'        => $brokenLink->getBrokenLinkText(),
                    $this->moduleNameLang.'_BROKEN_LINK_URL'         => contrexx_raw2xhtml($brokenLink->getRequestedPath()),
                    $this->moduleNameLang.'_BROKEN_LINK_REFERER'     => contrexx_raw2xhtml($brokenLink->getLeadPath()).'&pos='.$pos.'&csrf='.\Cx\Core\Csrf\Controller\Csrf::code(),
                    $this->moduleNameLang.'_BROKEN_LINK_MODULE_NAME' => contrexx_raw2xhtml($brokenLink->getModuleName()),
                    $this->moduleNameLang.'_BROKEN_LINK_ENTRY_TITLE' => contrexx_raw2xhtml($brokenLink->getEntryTitle()),
                    $this->moduleNameLang.'_BROKEN_LINK_STATUS_CODE' => $brokenLink->getLinkStatusCode() == 0 ? $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NON_EXISTING_DOMAIN'] : contrexx_raw2xhtml($brokenLink->getLinkStatusCode()),
                    $this->moduleNameLang.'_BROKEN_LINK_STATUS'      => $brokenLink->getLinkStatus() ? $brokenLink->getLinkStatus() : 0,
                    $this->moduleNameLang.'_BROKEN_LINK_STATUS_CHECKED' => $brokenLink->getLinkStatus() ? 'checked' : '',
                    $this->moduleNameLang.'_BROKEN_LINK_DETECTED'    => \Cx\Core_Modules\LinkManager\Controller\DateTime::formattedDateAndTime($brokenLink->getDetectedTime()),
                    $this->moduleNameLang.'_BROKEN_LINK_UPDATED_BY'  => $brokenLink->getUpdatedBy() ? contrexx_raw2xhtml($objUser->getUpdatedUserName($brokenLink->getUpdatedBy(), 0)) : '',
                    $this->moduleNameLang.'_CRAWLER_BROKEN_LINK'     => ($brokenLink->getLinkRecheck() && $brokenLink->getLinkStatus()) ? 'brokenLink' : '',
                    $this->moduleNameLang.'_CRAWLER_RUN_ROW'         => 'row'.(++$i % 2 + 1),
                ));
                $this->template->parse($this->moduleName.'CrawlerResultList');
            }
            $this->template->hideBlock('LinkManagerNoCrawlerResultFound');
        } else {
            $this->template->touchBlock('LinkManagerNoCrawlerResultFound');
        }
    }

    /**
     * Recheck the selected links status
     *
     * @global array $_ARRAYLANG
     *
     * @return null
     */
    public function recheckSelectedLinks()
    {
        global $_ARRAYLANG;

        $selectedIds = isset($_POST['selected']) ? $_POST['selected'] : '';

        $links = $this->linkRepository->getSelectedLinks($selectedIds);
        if (!$links) {
            $links = array();
        }
        $request = new \HTTP_Request2();
        $pageLinks = array();
        foreach ($links As $link) {
            if (!in_array($link->getEntryTitle(), $pageLinks)) {
                $pageLinks[] = $link->getEntryTitle();
                ${$link->getEntryTitle()} = array();
                try {
                    $request->setUrl($link->getRefererPath());
                    $request->setConfig(array(
                        'ssl_verify_peer' => false,
                        'ssl_verify_host'  => false,
                        'follow_redirects' => true,
                    ));
                    $response = $request->send();
                    $html     = \str_get_html($response->getBody());
                } catch(\Exception $e) {
                    $html = false;
                }

                if (!$html) {
                    continue;
                } else {
                    //remove the navigation menu
                    $objNavigation = $html->find('ul#navigation, ul.navigation',0);
                    $objNavigation->outertext = '';
                    $html = \str_get_html($html->outertext);

                    // Find all images
                    foreach($html->find('img') as $element) {
                        if (preg_match('#\.(jpg|jpeg|gif|png)$# i', $element->src)) {
                            $imgSrc = \Cx\Core_Modules\LinkManager\Controller\Url::checkPath($element->src, null);
                            if (!empty($imgSrc)) {
                                ${$link->getEntryTitle()}[$imgSrc] = $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_IMAGE'];
                            }
                        }
                    }
                    // Find all links
                    foreach($html->find('a') as $element) {
                        $aHref = \Cx\Core_Modules\LinkManager\Controller\Url::checkPath($element->href, $link->getRefererPath());
                        if (!empty($aHref)) {
                            $linkText = $element->plaintext ? $element->plaintext : $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_LINK'];
                            ${$link->getEntryTitle()}[$aHref] = $linkText;
                        }
                    }
                }
            }

            if (!array_key_exists($link->getRequestedPath(), ${$link->getEntryTitle()})) {
                $linkInputValues = array(
                    'lang'         => $link->getLang(),
                    'refererPath'  => $link->getRefererPath(),
                    'leadPath'     => $link->getLeadPath(),
                    'entryTitle'   => $link->getEntryTitle(),
                    'detectedTime' => $link->getDetectedTime(),
                    'updatedBy'    => 0,
                );
                $this->recheckPage(${$link->getEntryTitle()}, $linkInputValues, $request);
                $this->em->remove($link);
            } else {
                try {
                    $request->setUrl($link->getRequestedPath());
                    $response  = $request->send();
                    $urlStatus = $response->getStatus();
                } catch (\Exception $e) {
                    $urlStatus = 0;
                }
                if ($urlStatus == '200') {
                    $this->em->remove($link);
                } else {
                    $link->setLinkStatusCode($urlStatus);
                    $link->setLinkRecheck(true);
                }
            }

            $this->em->persist($link);
            $this->em->flush();
        }

        //update the broken links count in crawler table
        foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
            $lastRunByLang = $this->crawlerRepository->getLastRunByLang($lang['id']);
            $brokenLinkCnt = $this->linkRepository->brokenLinkCountByLang($lang['id']);
            if ($lastRunByLang) {
                $lastRunByLang->setTotalBrokenLinks($brokenLinkCnt);
                $this->em->persist($lastRunByLang);
            }
        }
        $this->em->flush();

        \Message::ok($_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_SUCCESS_MSG']);
    }

    /**
     * recheck all the links in the page and update those links status code
     *
     * @param array          $recheckLinks rechecking links
     * @param array          $inputArray   default input values
     * @param \HTTP_Request2 $request      http_request object
     */
    public function recheckPage($recheckLinks, $inputArray, \HTTP_Request2 $request)
    {
        if (is_array($recheckLinks) && $request) {
            foreach ($recheckLinks As $link => $text) {
                $linkAlreadyExist = $this->linkRepository->getLinkByPath($link);
                if (!$linkAlreadyExist) {
                    try {
                        $request->setUrl($link);
                        $request->setConfig(array(
                            'ssl_verify_peer' => false,
                            'ssl_verify_host'  => false,
                            'follow_redirects' => true,
                        ));
                        $response     = $request->send();
                        $urlStatus    = $response->getStatus();
                    } catch (\Exception $e) {
                        $urlStatus = preg_match('#^[mailto:|javascript:]# i', $link) ? 200 : 0;
                    }

                    $objFWUser    = \FWUser::getFWUserObject();
                    $internalFlag = \Cx\Core_Modules\LinkManager\Controller\Url::isInternalUrl($link);
                    $flagStatus   = ($urlStatus == '200') ? 1 : 0;
                    $linkType     = $internalFlag ? 'internal' : 'external';

                    $inputArray['requestedPath']     = contrexx_raw2db($link);
                    $inputArray['linkStatusCode']    = contrexx_raw2db($urlStatus);
                    $inputArray['flagStatus']        = contrexx_raw2db($flagStatus);
                    $inputArray['linkRecheck']       = 1;
                    $inputArray['requestedLinkType'] = contrexx_raw2db($linkType);
                    $inputArray['linkStatus']        = 1;
                    $inputArray['updatedBy']         = contrexx_raw2db($objFWUser->objUser->getId());
                    $inputArray['moduleName']        = '';
                    $inputArray['moduleAction']      = '';
                    $inputArray['moduleParams']      = '';
                    $inputArray['brokenLinkText']    = contrexx_raw2db($text);

                    $this->modifyLink($inputArray);
                }
            }
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
        } catch(\Exception $error) {
            die('Link Query ERROR!'.$error);
        }
    }
}
