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

    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
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
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objMadiadirPlaceholders, $page_template, $themesPages;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objMadiadirPlaceholders = new MediaDirectoryPlaceholders($this->getName());
                // Level/Category Navbar
                if (preg_match('/{MEDIADIR_NAVBAR}/', \Env::get('cx')->getPage()->getContent())) {
                    \Env::get('cx')->getPage()->setContent(str_replace('{MEDIADIR_NAVBAR}', $objMadiadirPlaceholders->getNavigationPlacholder(), \Env::get('cx')->getPage()->getContent()));
                }
                if (preg_match('/{MEDIADIR_NAVBAR}/', $page_template)) {
                    $page_template = str_replace('{MEDIADIR_NAVBAR}', $objMadiadirPlaceholders->getNavigationPlacholder(), $page_template);
                }
                if (preg_match('/{MEDIADIR_NAVBAR}/', $themesPages['index'])) {
                    $themesPages['index'] = str_replace('{MEDIADIR_NAVBAR}', $objMadiadirPlaceholders->getNavigationPlacholder(), $themesPages['index']);
                }
                if (preg_match('/{MEDIADIR_NAVBAR}/', $themesPages['sidebar'])) {
                    $themesPages['sidebar'] = str_replace('{MEDIADIR_NAVBAR}', $objMadiadirPlaceholders->getNavigationPlacholder(), $themesPages['sidebar']);
                }
                // Latest Entries
                if (preg_match('/{MEDIADIR_LATEST}/', \Env::get('cx')->getPage()->getContent())) {
                    \Env::get('cx')->getPage()->setContent(str_replace('{MEDIADIR_LATEST}', $objMadiadirPlaceholders->getLatestPlacholder(), \Env::get('cx')->getPage()->getContent()));
                }
                if (preg_match('/{MEDIADIR_LATEST}/', $page_template)) {
                    $page_template = str_replace('{MEDIADIR_LATEST}', $objMadiadirPlaceholders->getLatestPlacholder(), $page_template);
                }
                if (preg_match('/{MEDIADIR_LATEST}/', $themesPages['index'])) {
                    $themesPages['index'] = str_replace('{MEDIADIR_LATEST}', $objMadiadirPlaceholders->getLatestPlacholder(), $themesPages['index']);
                }
                if (preg_match('/{MEDIADIR_LATEST}/', $themesPages['sidebar'])) {
                    $themesPages['sidebar'] = str_replace('{MEDIADIR_LATEST}', $objMadiadirPlaceholders->getLatestPlacholder(), $themesPages['sidebar']);
                }

                break;

            default:
                break;
        }
    }

    /**
     * Do something after content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $mediadirCheck, $objTemplate, $_CORELANG, $objInit;

        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }

        $mediadirCheck = array();
        for ($i = 1; $i <= 10; ++$i) {
            if ($objTemplate->blockExists('mediadirLatest_row_'.$i)){
                array_push($mediadirCheck, $i);
            }
        }
        if ($mediadirCheck || $objTemplate->blockExists('mediadirLatest') || $objTemplate->blockExists('mediadirList') || $objTemplate->blockExists('mediadirNavtree')) {
            $objInit->loadLanguageData('MediaDir');

            $objMediadir = new MediaDirectory('', $this->getName());
            $objTemplate->setVariable('TXT_MEDIADIR_LATEST', $_CORELANG['TXT_DIRECTORY_LATEST']);
        }
        if ($mediadirCheck) {
            $objMediadir->getHeadlines($mediadirCheck);
        }
        if ($objTemplate->blockExists('mediadirLatest')){
            $objMediadirForms = new MediaDirectoryForm(null, 'MediaDir');
            $foundOne = false;
            foreach ($objMediadirForms->getForms() as $key => $arrForm) {
                if ($objTemplate->blockExists('mediadirLatest_form_'.$arrForm['formCmd'])) {
                    $objMediadir->getLatestEntries($key, 'mediadirLatest_form_'.$arrForm['formCmd']);
                    $foundOne = true;
                }
            }
            //for the backward compatibility
            if(!$foundOne) {
                $objMediadir->getLatestEntries();
            }
        }

        // Parse entries of specific form, category and/or level.   
        // Entries are listed in custom set order
        if ($objTemplate->blockExists('mediadirList')) {
            // hold information if a specific block has been parsed
            $foundOne = false;

            // fetch mediadir object data
            $objMediadirForm = new MediaDirectoryForm(null, $this->getName());
            $objMediadirCategory = new MediaDirectoryCategory(null, null, 0, $this->getName());
            $objMediadirLevel = new MediaDirectoryLevel(null, null, 1, $this->getName());

            // put all object data into one array
            $objects = array(
                'form' => array_keys($objMediadirForm->getForms()),
                'category' => array_keys($objMediadirCategory->arrCategories),
                'level' => array_keys($objMediadirLevel->arrLevels),
            );

            // check for form specific entry listing
            foreach ($objects as $objectType => $arrObjectList) {
                foreach ($arrObjectList as $objectId) {
                    // the specific block to parse. I.e.:
                    //    mediadirList_form_3
                    //    mediadirList_category_4
                    //    mediadirList_level_5
                    $block = 'mediadirList_'.$objectType.'_'.$objectId;
                    if ($objTemplate->blockExists($block)) {
                        $config = MediaDirectoryLibrary::fetchMediaDirListConfigFromTemplate($block, $objTemplate);
                        $config['filter'][$objectType] = $objectId;
                        $objMediadir->parseEntries($objTemplate, $block, $config);
                        $foundOne = true;
                    }
                }
            }

            // fallback, no specific block has been parsed
            // -> parse all entries now (use template block mediadirList)
            if(!$foundOne) {
                $objMediadir->parseEntries($objTemplate);
            }
        }
        if ($objTemplate->blockExists('mediadirNavtree')) {
            $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
            $categoryId = 0;
            if (isset($requestParams['cid'])) {
                $categoryId = intval($requestParams['cid']);
            }
            $levelId = 0;
            if (isset($requestParams['lid'])) {
                $levelId = intval($requestParams['lid']);
            }
            $objMediadir->getNavtree($categoryId, $levelId, $objTemplate);
            if ($objMediadir->getMetaTitle() != '') {
                $page->setMetatitle($page->getTitle() . $objMediadir->getMetaTitle());
            }
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
