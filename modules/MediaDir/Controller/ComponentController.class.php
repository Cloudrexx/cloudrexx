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
 * Main controller for MediaDir
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */

namespace Cx\Modules\MediaDir\Controller;
use Cx\Modules\MediaDir\Model\Event\MediaDirEventListener;

/**
 * Main controller for MediaDir
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * @var \Cx\Core\ContentManager\Model\Entity\Page Canonical page
     */
    protected $canonicalPage = null;

    /**
     * {@inheritDoc}
     */
    public function getControllerClasses()
    {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array('EsiWidget');
    }

    /**
     * {@inheritDoc}
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController');
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
                $objMediaDirectory = new MediaDirectory(\Env::get('cx')->getPage()->getContent(), $this->getName());
                $objMediaDirectory->pageTitle = \Env::get('cx')->getPage()->getTitle();
                $pageMetaTitle = \Env::get('cx')->getPage()->getMetatitle();
                $objMediaDirectory->metaTitle = $pageMetaTitle;
                \Env::get('cx')->getPage()->setContent($objMediaDirectory->getPage());
                if ($objMediaDirectory->getPageTitle() != '' && $objMediaDirectory->getPageTitle() != \Env::get('cx')->getPage()->getTitle()) {
                    \Env::get('cx')->getPage()->setTitle($objMediaDirectory->getPageTitle());
                    \Env::get('cx')->getPage()->setContentTitle($objMediaDirectory->getPageTitle());
                    \Env::get('cx')->getPage()->setMetaTitle($objMediaDirectory->getPageTitle());
                }
                if ($objMediaDirectory->getMetaTitle() != '') {
                    \Env::get('cx')->getPage()->setMetatitle($objMediaDirectory->getMetaTitle());
                }
                if ($objMediaDirectory->getMetaDescription() != '') {
                    \Env::get('cx')->getPage()->setMetadesc($objMediaDirectory->getMetaDescription());
                }
                if ($objMediaDirectory->getMetaImage() != '') {
                    \Env::get('cx')->getPage()->setMetaimage($objMediaDirectory->getMetaImage());
                }
                if ($objMediaDirectory->getMetaKeys() != '') {
                    \Env::get('cx')->getPage()->setMetakeys($objMediaDirectory->getMetaKeys());
                }

                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:

                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();
                \Permission::checkAccess(153, 'static');
                $subMenuTitle = $_CORELANG['TXT_MEDIADIR_MODULE'];
                $objMediaDirectory = new MediaDirectoryManager($this->getName());
                $objMediaDirectory->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * Do something after content is loaded from DB
     *
     * @todo: Move this functionality to the method adjustResponse()
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page The resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        global $objTemplate;

        if (
            $this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND ||
            !$objTemplate->blockExists('mediadirNavtree')
        ) {
            return;
        }

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
        if (isset($requestParams['cid'])) {
            $categoryId = contrexx_input2int($requestParams['cid']);
        }
        if (isset($requestParams['lid'])) {
            $levelId = contrexx_input2int($requestParams['lid']);
        }
        $objMediadir = new MediaDirectory('', $this->getName());
        $objMediadir->setMetaTitle($categoryId, $levelId);
        if ($objMediadir->getMetaTitle() != '') {
            $page->setMetatitle($page->getTitle() . $objMediadir->getMetaTitle());
        }
    }

    /**
     * Do something after system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     *
     * @param \Cx\Core\Core\Controller\Cx $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $params        = array();
        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
        if (isset($requestParams['lid'])) {
            $params['lid'] = $requestParams['lid'];
        }
        if (isset($requestParams['cid'])) {
            $params['cid'] = $requestParams['cid'];
        }

        // Parse widgets for Placeholders and Template Blocks
        // placeholders: Show Level/Category Navbar and Latest Entries
        // template blocks:
        // mediadirLatest, mediadirList, mediadirNavtree
        // mediadirLatest_row_1_1 to mediadirLatest_row_10_10
        $mediaDirLib = new MediaDirectoryLibrary('.', $this->getName());
        $widgetNames = $mediaDirLib->getWidgetNamesAffectedByEntityChange();
        $this->parseWidgets($widgetNames, $params);
    }

    /**
     * Parse widgets
     *
     * @param array $widgetNames          array of widget names
     * @param array $additionalParameters array of additional parameters
     */
    protected function parseWidgets($widgetNames, $additionalParameters)
    {
        if (empty($widgetNames)) {
            return;
        }

        $widgetController = $this->getComponent('Widget');
        foreach ($widgetNames as $widgetName) {
            // Use additional params if the widget name is
            // either 'mediadirNavtree' or 'MEDIADIR_NAVBAR'
            $parameter = array();
            if (
                in_array(
                    $widgetName,
                    array('mediadirNavtree', 'MEDIADIR_NAVBAR')
                )
            ) {
                $parameter = $additionalParameters;
            }

            // Identify if the current widget is Template block or Placeholder
            $widgetType = \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK;
            if (
                in_array(
                    $widgetName,
                    array('MEDIADIR_NAVBAR', 'MEDIADIR_LATEST')
                )
            ) {
                $widgetType = \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_PLACEHOLDER;
            }

            // Create and Register the widget in Widget Component
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                $widgetName,
                $widgetType,
                '',
                '',
                $parameter
            );
            if ($widgetType === \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_PLACEHOLDER) {
                $widget->setEsiVariable(
                    \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                    \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL
                );
            }
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
        $eventListener = new MediaDirEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('SearchFindContent',$eventListener);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);
    }

    /**
     * Called for additional, component specific resolving
     * 
     * If /en/Path/to/Page is the path to a page for this component
     * a request like /en/Path/to/Page/with/some/parameters will
     * give an array like array('with', 'some', 'parameters') for $parts
     * 
     * This may be used to redirect to another page
     * @param array $parts List of additional path parts
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved virtual page
     */
    public function resolve($parts, $page) {
        if (empty($parts)) {
            $this->setCanonicalPage($page);
            return;
        }

        $objMediaDirectoryEntry = new MediaDirectoryEntry($this->getName());
        if (!$objMediaDirectoryEntry->arrSettings['usePrettyUrls']) {
            return;
        }

        $levelId = null;
        $categoryId = null;

        $detailPage = $page;
        $slugCount = count($parts);
        $cmd = $page->getCmd();
        $slug = array_pop($parts);

        // fetch category & level from page's CMD
        if (count($parts) == 0) {
            if ($page->getCmd()) {
                $pageArguments = explode('-', $page->getCmd());
                if (count($pageArguments) == 2) {
                    $levelId = $pageArguments[0];
                    $categoryId = $pageArguments[1];
                } elseif (count($pageArguments) && $objMediaDirectoryEntry->arrSettings['settingsShowLevels']) {
                    $levelId = $pageArguments[0];
                } elseif (count($pageArguments)) {
                    $categoryId = $pageArguments[0];
                }
            }
        }

        // detect entry
        $name = $objMediaDirectoryEntry->getNameFromSlug($slug);
        $entryId = $objMediaDirectoryEntry->findOneByName($name, null, $categoryId, $levelId);
        if ($entryId) {
            if (substr($cmd,0,6) != 'detail') {
                $formId = null;
                $formData = $objMediaDirectoryEntry->getFormData();
                foreach ($formData as $arrForm) {
                    if ($arrForm['formCmd'] == $cmd) {
                        $formId= $arrForm['formId'];
                        break;
                    }
                }

                if (!$formId) {
                    $objMediaDirectoryEntry->getEntries(intval($entryId),null,null,null,null,null,1,null,1);
                    $formDefinition = $objMediaDirectoryEntry->getFormDefinitionOfEntry($entryId);
                    $formId = $formDefinition['formId'];
                }

                $detailPage = $objMediaDirectoryEntry->getApplicationPageByEntry($formId);
                if (!$detailPage) {
                    return;
                }
                // TODO: we need an other method that does also load the additional infos (template, css, etc.)
                //       this new method must also be used for symlink pages
                $page->setContentOf($detailPage, true);


                // ------------------------------------------------------------
                // ------------------------------------------------------------
                // TODO: this code snipped is taken from \Cx\Core\Routing\Resolver
                //       the relevant code in the Resolver should be moved further down in the resolving process
                //       so that the following code snipped can be omitted
                global $themesPages, $page_template;

                \Env::get('init')->setCustomizedTheme($page->getSkin(), $page->getCustomContent(), $page->getUseSkinForAllChannels());

                $themesPages = \Env::get('init')->getTemplates($page);

                //replace the {NODE_<ID>_<LANG>}- placeholders
                \LinkGenerator::parseTemplate($themesPages);

                //$page_access_id = $objResult->fields['frontend_access_id'];
                $page_template  = $themesPages['content'];
                // END TODO
                // ------------------------------------------------------------
                // ------------------------------------------------------------


                //$page->getFallbackContentFrom($detailPage);
                $_GET['cmd']     = $_POST['cmd']     = $_REQUEST['cmd']     = $detailPage->getCmd();
            }

            $this->cx->getRequest()->getUrl()->setParam('eid', $entryId);

            // inject level & category as request arguments from page's CMD
            if ($levelId) {
                $this->cx->getRequest()->getUrl()->setParam('lid', $levelId);
            }
            if ($categoryId) {
                $this->cx->getRequest()->getUrl()->setParam('cid', $categoryId);
            }

            if (empty($parts)) {
                $this->setCanonicalPage($detailPage);
                return;
            }

            $slug = array_pop($parts);
        }

        // detect level and/or category
        while ($slug && (!$levelId || !$categoryId)) {
            // let's check if a category exists by the supplied slug
            if (!$levelId && $objMediaDirectoryEntry->arrSettings['settingsShowLevels']) {
                $objMediaDirectoryLevel = new MediaDirectoryLevel(null, null, 0, $this->getName());
                $levelId = $objMediaDirectoryLevel->findOneBySlug($slug);
                if ($levelId) {
                    $this->cx->getRequest()->getUrl()->setParam('lid', $levelId);
                }
            }

            // let's check if a category exists by the supplied slug
            if (!$categoryId) {
                $objMediaDirectoryCategory = new MediaDirectoryCategory(null, null, 0, $this->getName());
                $categoryId = $objMediaDirectoryCategory->findOneBySlug($slug);
                if ($categoryId) {
                    $this->cx->getRequest()->getUrl()->setParam('cid', $categoryId);
                }
            }

            $slug = array_pop($parts);
        }

        if ($levelId || $categoryId) {
            $this->setCanonicalPage($detailPage);
        }
    }

    /**
     * Sets the canonical page
     * @param \Cx\Core\ContentManager\Model\Entity\Page $canonicalPage Canonical page
     */
    protected function setCanonicalPage($canonicalPage) {
        $this->canonicalPage = $canonicalPage;
    }
    
    /**
     * Do something with a Response object
     * You may do page alterations here (like changing the metatitle)
     * You may do response alterations here (like set headers)
     * PLEASE MAKE SURE THIS METHOD IS MOCKABLE. IT MAY ONLY INTERACT WITH
     * resolve() HOOK.
     *
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object to adjust
     */
    public function adjustResponse(\Cx\Core\Routing\Model\Entity\Response $response) {
        $canonicalUrlArguments = array('eid', 'cid', 'lid', 'preview', 'pos');
        if (in_array('eid', array_keys($response->getRequest()->getUrl()->getParamArray()))) {
            $canonicalUrlArguments = array_filter($canonicalUrlArguments, function($key) {return !in_array($key, array('cid', 'lid'));});
        }

        $params = array();

        // filter out all non-relevant URL arguments
        /*$params = array_filter(
            $this->cx->getRequest()->getUrl()->getParamArray(),
            function($key) {return in_array($key, $canonicalUrlArguments);},
            \ARRAY_FILTER_USE_KEY
        );*/

        foreach ($response->getRequest()->getUrl()->getParamArray() as $key => $value) {
            if (!in_array($key, $canonicalUrlArguments)) {
                continue;
            }
            $params[$key] = $value;
        }

        $canonicalUrl = \Cx\Core\Routing\Url::fromPage($this->canonicalPage, $params);
        $response->setHeader(
            'Link',
            '<' . $canonicalUrl->toString() . '>; rel="canonical"'
        );
    }
}
