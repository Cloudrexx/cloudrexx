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
 * DefaultController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Controller;

/**
 * The class DefaultController for display the latest run details and display all the crawler runs
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class DefaultController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Sigma template instance
     * @var Cx\Core\Html\Sigma  $template
     */
    protected $template;

    /**
     * Em instance
     * @var \Doctrine\ORM\EntityManager em
     */
    protected $em;

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
     * DefaultController for the DefaultView
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        //check the user permission
        \Permission::checkAccess(1030, 'static');

        parent::__construct($systemComponentController, $cx);
        $this->em                = $this->cx->getDb()->getEntityManager();
        $this->crawlerRepository = $this->em->getRepository('Cx\Core_Modules\LinkManager\Model\Entity\Crawler');
    }

    /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showCrawlerRuns();
    }
    /**
     * Show all the runs and last runs detail
     *
     * @global array $_ARRAYLANG
     */
    public function showCrawlerRuns()
    {
        global $_ARRAYLANG;

        //show the last runs details
        $lastRunResult = $this->crawlerRepository->getLatestRunDetails();
        if ($lastRunResult) {
            $this->template->setVariable(array(
                $this->moduleNameLang.'_LAST_RUN_STARTTIME'       => \Cx\Core_Modules\LinkManager\Controller\DateTime::formattedDateAndTime($lastRunResult[0]->getStartTime()),
                $this->moduleNameLang.'_LAST_RUN_ENDTIME'         => \Cx\Core_Modules\LinkManager\Controller\DateTime::formattedDateAndTime($lastRunResult[0]->getEndTime()),
                $this->moduleNameLang.'_LAST_RUN_DURATION'        => \Cx\Core_Modules\LinkManager\Controller\DateTime::diffTime($lastRunResult[0]->getStartTime(), $lastRunResult[0]->getEndTime()),
                $this->moduleNameLang.'_LAST_RUN_TOTAL_LINKS'     => $lastRunResult[0]->getTotalLinks(),
                $this->moduleNameLang.'_LAST_RUN_BROKEN_LINKS'    => $lastRunResult[0]->getTotalBrokenLinks(),
                ));
        } else {
            if($this->template->blockExists('showLastRun')){
                $this->template->hideBlock('showLastRun');
            }
        }

        //show Crawler Runs table
        //get parameters
        $pos       = isset($_GET['pos']) ? $_GET['pos'] : 0;
        $langArray = \FWLanguage::getLanguageArray();
        //set the settings value from DB
        \Cx\Core\Setting\Controller\Setting::init('LinkManager', 'config');
        $pageLimit = \Cx\Core\Setting\Controller\Setting::getValue('entriesPerPage', 'LinkManager');
        $parameter = './index.php?cmd='.$this->moduleName;
        $this->template->setVariable('ENTRIES_PAGING', \Paging::get($parameter, $_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LINKS'], $this->crawlerRepository->crawlerEntryCount(), $pageLimit, true, $pos, 'pos'));
        $crawlers = $this->crawlerRepository->getCrawlerRunEntries($pos, $pageLimit);

        $i = 1;
        if ($crawlers && $crawlers->count() > 0) {
            foreach($crawlers As $crawler) {
                $this->template->setVariable(array(
                    $this->moduleNameLang.'_CRAWLER_RUN_ID'            => $crawler->getId(),
                    $this->moduleNameLang.'_CRAWLER_RUN_LANGUAGE'      => $langArray[$crawler->getLang()]['name'],
                    $this->moduleNameLang.'_CRAWLER_RUN_STARTTIME'     => \Cx\Core_Modules\LinkManager\Controller\DateTime::formattedDateAndTime($crawler->getStartTime()),
                    $this->moduleNameLang.'_CRAWLER_RUN_ENDTIME'       => \Cx\Core_Modules\LinkManager\Controller\DateTime::formattedDateAndTime($crawler->getEndTime()),
                    $this->moduleNameLang.'_CRAWLER_RUN_DURATION'      => \Cx\Core_Modules\LinkManager\Controller\DateTime::diffTime($crawler->getStartTime(), $crawler->getEndTime()),
                    $this->moduleNameLang.'_CRAWLER_RUN_TOTAL_LINKS'   => $crawler->getTotalLinks(),
                    $this->moduleNameLang.'_CRAWLER_RUN_BROKEN_LINKS'  => $crawler->getTotalBrokenLinks(),
                    $this->moduleNameLang.'_CRAWLER_RUN_STATUS'        => ucfirst($crawler->getRunStatus()),
                    $this->moduleNameLang.'_CRAWLER_RUN_ROW'           => 'row'.(++$i % 2 + 1),
                ));
                $this->template->parse($this->moduleName.'CrawlerRuns');
            }
            $this->template->hideBlock($this->moduleName.'NoCrawlerRunsFound');
        } else {
            $this->template->touchBlock($this->moduleName.'NoCrawlerRunsFound');
        }
    }

}
