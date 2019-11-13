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
     * @var \Cx\Core\Routing\Url Canonical url
     */
    protected $canonicalUrl = null;

    /**
     * @var MediaDirectory
     */
    protected $mediaDirectory = null;

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
                // we need to re-instanciate MediaDirectory as
                // page content could have changed
                $this->mediaDirectory = new MediaDirectory(
                    $page->getContent(),
                    $this->getName()
                );
                $this->mediaDirectory->pageTitle = $page->getTitle();
                $this->mediaDirectory->metaTitle = $page->getMetatitle();
                $page->setContent($this->mediaDirectory->getPage());
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
                        $categoryId = null;
                        $levelId = null;
                        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
                        $categoryId = 0;
                        if (isset($requestParams['cid'])) {
                            $categoryId = intval($requestParams['cid']);
                        }
                        $levelId = 0;
                        if (isset($requestParams['lid'])) {
                            $levelId = intval($requestParams['lid']);
                        }
                        $config = MediaDirectoryLibrary::fetchMediaDirListConfigFromTemplate(
                            $block,
                            $objTemplate,
                            null,
                            $categoryId,
                            $levelId
                        );
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
        // abort resolving in case pretty-URLs is not in case
        $objMediaDirectoryEntry = new MediaDirectoryEntry($this->getName());
        if (!$objMediaDirectoryEntry->arrSettings['usePrettyUrls']) {
            return;
        }

        $levelId = null;
        $categoryId = null;

        $detailPage = $page;
        $slugCount = count($parts);
        $cmd = $page->getCmd();
        $noParts = false;

        if (empty($parts)) {
            $noParts = true;
        } else {
            // Extract slug part from the end of the requested URL.
            // This might be the slug of an entry, level or category
            $slug = array_pop($parts);
        }

        // fetch category & level from page's CMD in case the requested URL
        // does not contain a category nor a level 
        if (count($parts) == 0) {
            if ($cmd &&
                preg_match('/^\d*-?\d*+$/', $cmd)
            ) {
                $pageArguments = explode('-', $cmd);
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

        // in case the requested URL does not contain any slug-parts
        // there is nothing else for us to do as it is a regular page request
        if ($noParts) {
            // inject level as request arguments from page's CMD
            if ($levelId) {
                $this->cx->getRequest()->getUrl()->setParam('lid', $levelId);
            }

            // inject category as request arguments from page's CMD
            if ($categoryId) {
                $this->cx->getRequest()->getUrl()->setParam('cid', $categoryId);
            }

            return;
        }

        // check if the extracted slug is an entry
        $entryId = $objMediaDirectoryEntry->findOneBySlug($slug, null, $categoryId, $levelId);
        if ($entryId) {
            // in case the requested URL points to an application page of
            // a form, category or level, then we have to manually load
            // the contents of the associated detail application page
            // of the resolved entry
            if (substr($cmd,0,6) != 'detail') {

                // check if the requested URL points to the application page
                // of a form
                $formId = null;
                $formData = $objMediaDirectoryEntry->getFormData();
                foreach ($formData as $arrForm) {
                    if (empty($arrForm['formCmd'])) {
                        continue;
                    }
                    if ($arrForm['formCmd'] == $cmd) {
                        $formId = $arrForm['formId'];
                        break;
                    }
                }

                // The requested URL does not point to the application page
                // of a form. Therefore, we have to identify the form that
                // is associated to the resolved entry
                if (!$formId) {
                    $objMediaDirectoryEntry->getEntries(intval($entryId),null,null,null,null,null,1,null,1);
                    $formDefinition = $objMediaDirectoryEntry->getFormDefinitionOfEntry($entryId);
                    $formId = $formDefinition['formId'];
                }

                // Fetch the entry-detail-application page that matches best
                // to the resolved entry
                $detailPage = $objMediaDirectoryEntry->getApplicationPageByEntry($formId);

                // in case there exists no entry-detail-application page for
                // the resolved entry, we can abort here
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
                // TODO: the system should not access superglobals directly.
                // Instead they should only be accessed by the Request object
                $_GET['cmd']     = $_POST['cmd']     = $_REQUEST['cmd']     = $detailPage->getCmd();
            }

            // inject URL argument eid into the request
            $this->cx->getRequest()->getUrl()->setParam('eid', $entryId);

            // inject level as request arguments from page's CMD
            if ($levelId) {
                $this->cx->getRequest()->getUrl()->setParam('lid', $levelId);
            }

            // inject category as request arguments from page's CMD
            if ($categoryId) {
                $this->cx->getRequest()->getUrl()->setParam('cid', $categoryId);
            }

            // Request does not contain any virtual category or level path.
            // Therefore, we can finish the resolve process here.
            // We've successfully resolved the requested entry.
            if (empty($parts)) {
                return;
            }

            // fetch the next slug part from the requested URL
            // which might be a category or a level
            $slug = array_pop($parts);
        }

        // in case we have not yet identified a category and a level
        // lets check if the requested URL does contain any
        // virtual level or category
        $matchedLevelId = 0;
        $matchedCategoryId = 0;
        while (
            $slug &&
            !($levelId && $categoryId)
        ) {
            // let's check if a level exists by the supplied slug
            if (!$matchedLevelId && $objMediaDirectoryEntry->arrSettings['settingsShowLevels']) {
                $objMediaDirectoryLevel = new MediaDirectoryLevel(null, null, 0, $this->getName());
                $matchedLevelId = $objMediaDirectoryLevel->findOneBySlug($slug);
                if ($matchedLevelId) {
                    $levelId = $matchedLevelId;
                }
            }

            // let's check if a category exists by the supplied slug
            if (!$matchedCategoryId) {
                $objMediaDirectoryCategory = new MediaDirectoryCategory(null, null, 0, $this->getName());
                $matchedCategoryId = $objMediaDirectoryCategory->findOneBySlug($slug);
                if ($matchedCategoryId) {
                    $categoryId = $matchedCategoryId;
                }
            }

            // fetch parent slug (if any is left)
            $slug = array_pop($parts);
        }

        // inject level (URL argument lid) into the request
        if ($levelId) {
            $this->cx->getRequest()->getUrl()->setParam('lid', $levelId);
        }

        // inject category (URL argument cid) into the request
        if ($categoryId) {
            $this->cx->getRequest()->getUrl()->setParam('cid', $categoryId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        // resolve canonical-link
        if (!$this->canonicalUrl) {
            $this->setCanonicalUrl($response);
        }

        $response->setHeader(
            'Link',
            '<' . $this->canonicalUrl->toString() . '>; rel="canonical"'
        );

        $page = $response->getPage();
        if (!$page) {
            return;
        }
        $this->mediaDirectory = new MediaDirectory(
            $page->getContent(),
            $this->getName()
        );
        $this->mediaDirectory->pageTitle = $page->getTitle();
        $this->mediaDirectory->metaTitle = $page->getMetatitle();
        // we need to parse the complete page as the meta info is not set otherwise
        $this->mediaDirectory->getPage();
        if (
            $this->mediaDirectory->getPageTitle() != '' &&
            $this->mediaDirectory->getPageTitle() != $page->getTitle()
        ) {
            $page->setTitle($this->mediaDirectory->getPageTitle());
            $page->setContentTitle($this->mediaDirectory->getPageTitle());
            $page->setMetaTitle($this->mediaDirectory->getPageTitle());
        }
        if ($this->mediaDirectory->getMetaTitle() != '') {
            $page->setMetatitle($this->mediaDirectory->getMetaTitle());
        }
        if ($this->mediaDirectory->getMetaDescription() != '') {
            $page->setMetadesc($this->mediaDirectory->getMetaDescription());
        }
        if ($this->mediaDirectory->getMetaImage() != '') {
            $page->setMetaimage($this->mediaDirectory->getMetaImage());
        }
        if ($this->mediaDirectory->getMetaKeys() != '') {
            $page->setMetakeys($this->mediaDirectory->getMetaKeys());
        }
    }

    protected function setCanonicalUrl(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        // in case of an ESI request, the request URL will be set through Referer-header
        $headers = $response->getRequest()->getHeaders();
        if (isset($headers['Referer'])) {
            $refUrl = new \Cx\Lib\Net\Model\Entity\Url($headers['Referer']);
        } else {
            $refUrl = new \Cx\Lib\Net\Model\Entity\Url($response->getRequest()->getUrl()->toString());
        }

        if ($refUrl->hasParam('eid')) {
            $canonicalUrlArguments = array('eid');
        } else {
            $canonicalUrlArguments = array('cid', 'lid', 'pos');
        }

        // filter out all non-relevant URL arguments
        $params = array_filter(
            $refUrl->getParamArray(),
            function($key) use ($canonicalUrlArguments, $refUrl) {
                if ($key == 'pos' && in_array($key, $canonicalUrlArguments)) {
                    return !empty($refUrl->getParam($key));
                }
                return in_array($key, $canonicalUrlArguments);
            },
            \ARRAY_FILTER_USE_KEY
        );

        $entry = new MediaDirectoryEntry($this->getName());

        // set canonical-link for detail section of entry
        if (isset($params['eid'])) {
            $entryId = intval($params['eid']);
            $entry->getEntries($entryId, null, null, null, null, null, 1, null, 1);
            $this->canonicalUrl = $entry->getAutoSlugPath($entry->arrEntries[$entryId]);
            return;
        }

        // Check if a specific application page does exist for the
        // requested category/level.
        // If so, do use that page as canonical-link

        $levelId = 0;
        if (isset($params['lid'])) {
            $levelId = intval($params['lid']);
        }
        $categoryId = 0;
        if (isset($params['cid'])) {
            $categoryId = intval($params['cid']);
        }

        $url = $entry->getAutoSlugPath(null, $categoryId, $levelId);

        // fallback, set canonical-link to currently resolved page
        if (!$url) {
            $page = $response->getPage();
            $url = \Cx\Core\Routing\Url::fromPage($page, $params);
        }

        $this->canonicalUrl = $url;
    }
}
