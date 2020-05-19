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
 * Main controller for Downloads
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 */

namespace Cx\Modules\Downloads\Controller;

/**
 * Main controller for Downloads
 *
 * @copyright   Cloudrexx AG
 * @author      Thomas Wirz <thomas.wirz@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 */
class DownloadsInternalException extends \Exception {}

/**
 * Main controller for Downloads
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @author      Thomas Wirz <thomas.wirz@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * {@inheritdoc}
     */
    public function getControllerClasses()
    {
        return array('EsiWidget', 'JsonDownloads');
    }

    /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController', 'JsonDownloadsController');
    }

    /**
     * {@inheritdoc}
     */
    public function adjustResponse(\Cx\Core\Routing\Model\Entity\Response $response)
    {
        $page = $response->getPage();
        if (!$page || $page->getModule() !== $this->getName()) {
            return;
        }
        $downloads = new Downloads('');
        $downloads->getPage();
        $pageTitle = $downloads->getPageTitle();

        //Set the Page Title
        if ($pageTitle) {
            $page->setTitle($pageTitle);
            $page->setContentTitle($pageTitle);
            $page->setMetaTitle($pageTitle);
        }

        //Set the page metakeys
        $metaKeys = $downloads->getMetaKeywords();
        if ($metaKeys) {
            $page->setMetakeys($metaKeys);
        }
    }

     /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $subMenuTitle, $objTemplate;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objDownloadsModule = new Downloads(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objDownloadsModule->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();
                $subMenuTitle = $_CORELANG['TXT_DOWNLOADS'];
                $objDownloadsModule = new DownloadsManager();
                $objDownloadsModule->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        // downloads group
        $groups            = Group::getGroups();
        $groupsPlaceholders = $groups->getGroupsPlaceholders();
        $this->registerDownloadsWidgets($groupsPlaceholders, \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::TYPE_PLACEHOLDER);

        // downloads category list
        $categoriesBlocks = Category::getCategoryWidgetNames();
        $this->registerDownloadsWidgets($categoriesBlocks, \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::TYPE_BLOCK);
    }

    /**
     * Register the downloads widgets
     *
     * @param array   $widgets widgets array
     * @param string  $type Widget type
     *
     * @return null
     */
    protected function registerDownloadsWidgets($widgets, $type) {

        $pos = 0;
        if (isset($_GET['pos'])) {
            $pos = intval($_GET['pos']);
        }
        $widgetController = $this->getComponent('Widget');
        foreach ($widgets as $widgetName) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                $widgetName,
                $type,
                '',
                '',
                array('pos' => $pos)
            );
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL
            );
            $widgetController->registerWidget($widget);
        }
    }

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners() {
        $evm = $this->cx->getEvents();
        $eventListener = new \Cx\Modules\Downloads\Model\Event\DownloadsEventListener($this->cx);
        $evm->addEventListener('SearchFindContent', $eventListener);
        $evm->addEventListener('mediasource.load', $eventListener);

        // locale event listener
        $localeLocaleEventListener = new \Cx\Modules\Downloads\Model\Event\LocaleLocaleEventListener($this->cx);
        $evm->addModelListener('postPersist', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
        $evm->addModelListener('preRemove', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
    }

    /**
     * Find Downloads by keyword $searchTerm and return them in a
     * two-dimensional array compatible to be used by Search component.
     *
     * @param   string  $searchTerm The keyword to search by
     * @return  array   Two-dimensional array of Downloads found by keyword
     *                  $searchTerm.
     *                  If integration into search component is disabled or
     *                  no Download matched the giving keyword, then an
     *                  empty array is retured.
     */
    public function getDownloadsForSearchComponent($searchTerm) {
        $result = array();
        $downloadLibrary = new DownloadsLibrary();
        $config = $downloadLibrary->getSettings();
        $download = new Download($config);

        // abort in case downloads shall not be included into the global
        // fulltext search component
        if (!$config['integrate_into_search_component']) {
            return array();
        }

        // check for valid published application page
        $filter = null;
        try {
            $arrCategoryIds = $this->getCategoryFilterForSearchComponent();

            // set category filter if we have to restrict search by
            // any category IDs
            if ($arrCategoryIds) {
                $filter = array('category_id' => $arrCategoryIds);
            }
        } catch (DownloadsInternalException $e) {
            return array();
        }

        // lookup downloads by given keyword
        if (!$download->loadDownloads(
            $filter,
            $searchTerm,
            null,
            null,
            null,
            null,
            $config['list_downloads_current_lang']
        )) {
            return array();
        }

        /**
         * @ignore
         */
        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/PEAR/Download.php');

        $langId = DownloadsLibrary::getOutputLocale()->getId();

        while (!$download->EOF) {
            try {
                $url = DownloadsLibrary::getApplicationUrl(
                    $download->getAssociatedCategoryIds()
                );
            } catch (DownloadsLibraryException $e) {
                $download->next();
                continue;
            }

            // determine link-behaviour
            switch ($config['global_search_linking']) {
                case HTTP_DOWNLOAD_INLINE:
                case HTTP_DOWNLOAD_ATTACHMENT:
                    $url->setParam('disposition', $config['global_search_linking']);
                    $url->setParam('download', $download->getId());
                    break;

                case 'detail':
                default:
                    $url->setParam('id', $download->getId());
                    break;
            }

            $result[] = array(
                'Score'     => 100,
                'Title'     => $download->getName($langId),
                'Content'   => $download->getTrimmedDescription($langId),
                'Link'      => (string) $url,
                'Component' => $this->getName(),
            );
            $download->next();
        }

        return $result;
    }

    /**
     * Get published category IDs (as application pages)
     *
     *
     * @return  array   List of published category IDs.
     *                  An empty array is retured, in case an application 
     *                  page is published that has no category restriction set
     *                  through its CMD.
     * @throws  DownloadsInternalException In case no application page of this
     *                                  component is published
     */
    protected function getCategoryFilterForSearchComponent() {
        \Cx\Core\Setting\Controller\Setting::init('Config', 'site','Yaml');
        $coreListProtectedPages   = \Cx\Core\Setting\Controller\Setting::getValue('coreListProtectedPages','Config');
        $searchVisibleContentOnly = \Cx\Core\Setting\Controller\Setting::getValue('searchVisibleContentOnly','Config');

        // fetch data about existing application pages of this component
        $cmds = array();
        $em = $this->cx->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $pages = $pageRepo->getAllFromModuleCmdByLang($this->getName());
        foreach ($pages as $pagesOfLang) {
            foreach ($pagesOfLang as $page) {
                $cmds[] = $page->getCmd();
            }
        }

        // check if an application page is published
        $cmds = array_unique($cmds);
        $arrCategoryIds = array();
        foreach ($cmds as $cmd) {
            // fetch application page with specific CMD from current locale
            $page = $pageRepo->findOneByModuleCmdLang($this->getName(), $cmd, FRONTEND_LANG_ID);

            // skip if page does not exist in current locale or has not been
            // published
            if (
                !$page ||
                !$page->isActive()
            ) {
                continue;
            }

            // skip invisible page (if excluded from search)
            if (
                $searchVisibleContentOnly == 'on' &&
                !$page->isVisible()
            ) {
                continue;
            }

            // skip protected page (if excluded from search)
            if (
                $coreListProtectedPages == 'off' &&
                $page->isFrontendProtected() &&
                $this->getComponent('Session')->getSession() &&
                !\Permission::checkAccess($page->getFrontendAccessId(), 'dynamic', true)
            ) {
                continue;
            }

            // in case the CMD is an integer, then
            // the integer does represent an ID of category which has to be
            // applied to the search filter
            if (preg_match('/^\d+$/', $cmd)) {
                $arrCategoryIds[] = $cmd;
                continue;
            }

            // in case an application exists that has not set a category-ID as
            // its CMD, then we do not have to restrict the search by one or
            // more specific categories
            return array();
        }

        // if we reached this point and no category-IDs have been fetched 
        // then this means that no application is published
        if (empty($arrCategoryIds)) {
            throw new DownloadsInternalException('Application is not published');
        }

        return $arrCategoryIds;
    }
}
