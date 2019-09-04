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
 * Resolver
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */

namespace Cx\Core\Routing;

/**
 * ResolverException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */
class ResolverException extends \Exception {};

/**
 * Takes an URL and tries to find the Page.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */
class Resolver {
    protected $em = null;
    protected $url = null;

    /**
     * @var Cx\Core\Routing\Url
     */
    protected $originalUrl = null;

    /**
     * language id.
     * @var integer
     */
    protected $lang = null;

    /**
     * the page we found.
     * @var Cx\Core\ContentManager\Model\Entity\Page
     */
    protected $page = null;

    /**
     * Aliaspage if we resolve an alias
     * @var Cx\Core\ContentManager\Model\Entity\Page
     */
    protected $aliaspage = null;

    /**
     * @var Cx\Core\ContentManager\Model\Entity\Page
     */
    protected $urlPage = null;

    /**
     * Doctrine Cx\Core\ContentManager\Model\Repository\PageRepository
     */
    protected $pageRepo = null;

    /**
     * Doctrine NodeRepository
     */
    protected $nodeRepo = null;

    /**
     * Remembers if we've come across a redirection while resolving the URL.
     * This allow to properly redirect.
     * @var boolean
     */
    protected $isRedirection = false;

    /**
     * Maps language ids to fallback language ids.
     * @var array ($languageId => $fallbackLanguageId)
     */
    protected $fallbackLanguages = null;

    /**
     * Contains the resolved module name (if any, empty string if none)
     * @var String
     */
    protected $section = '';

    /**
     * Contains the resolved module command (if any, empty string if none)
     * @var String
     */
    protected $command = '';

    /**
     * Remembers if it's a page preview.
     * @var boolean
     */
    protected $pagePreview = 0;

    /**
     * Contains the history id to revert the page to an older version.
     * @var int
     */
    protected $historyId = 0;

    /**
     * Contains the page array from the session.
     * @var array
     */
    protected $sessionPage = array();
    protected $path;

    /**
     * List of headers set by resolver
     * @var array
     */
    protected $headers = array();

    /**
     * Passed path after the resolved page's path
     * @var string
     */
    protected $additionalPath = '';

    /**
     * @param Url $url the url to resolve
     * @param integer $lang the language Id
     * @param $entityManager
     * @param string $pathOffset ASCMS_PATH_OFFSET
     * @param array $fallbackLanguages (languageId => fallbackLanguageId)
     * @param boolean $forceInternalRedirection does not redirect for internal redirections if set to true.
     *                this is used mainly for testing currently.
     *                IMPORTANT: Do insert new parameters before this one if you need to and correct the tests.
     */
    public function __construct($url, $lang, $entityManager, $pathOffset, $fallbackLanguages, $forceInternalRedirection=false) {
        $this->init($url, $lang, $entityManager, $pathOffset, $fallbackLanguages, $forceInternalRedirection);
    }


    /**
     * @param Url $url the url to resolve
     * @param integer $lang the language Id
     * @param $entityManager
     * @param string $pathOffset ASCMS_PATH_OFFSET
     * @param array $fallbackLanguages (languageId => fallbackLanguageId)
     * @param boolean $forceInternalRedirection does not redirect for internal redirections if set to true.
     *                this is used mainly for testing currently.
     *                IMPORTANT: Do insert new parameters before this one if you need to and correct the tests.
     */
    public function init($url, $lang, $entityManager, $pathOffset, $fallbackLanguages, $forceInternalRedirection=false) {
        $this->url = $url;
        $this->originalUrl = clone $url;
        $this->em = $entityManager;
        $this->lang = $lang;
        $this->pathOffset = $pathOffset;
        $this->pageRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $this->nodeRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        $this->logRepo  = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\LogEntry');
        $this->forceInternalRedirection = $forceInternalRedirection;
        $this->fallbackLanguages = $fallbackLanguages;
        $this->pagePreview = !empty($_GET['pagePreview']) && ($_GET['pagePreview'] == 1) ? 1 : 0;
        $this->historyId = !empty($_GET['history']) ? $_GET['history'] : 0;

        // load preview page from session (if requested)
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $session = $cx->getComponent('Session');
        $session->getSession($this->pagePreview);
        if ($session->isInitialized()) {
            $this->sessionPage = !empty($_SESSION['page']) ? $_SESSION['page']->toArray() : array();
        }
    }

    public function resolve() {
        // $this->resolveAlias() also sets $this->page
        $this->aliaspage = $this->resolveAlias();

        if ($this->aliaspage != null) {
            $this->lang = $this->aliaspage->getTargetLangId();
            $this->aliaspage = clone $this->aliaspage;
            $this->aliaspage->setVirtual(true);
        } else {
            // if the current URL points to a file:
            if (
                empty($this->url->getLangDir(true)) &&
                preg_match('/^[^?]*\.[a-z0-9]{2,4}$/', $this->url->toString())
            ) {
                global $url;
                $_GET['id'] = 404;
                $this->url = \Cx\Core\Routing\Url::fromModuleAndCmd('Error', '', \FWLanguage::getDefaultLangId());
                $url = $this->url;
            }

            $this->lang = \Env::get('init')->getFallbackFrontendLangId();

            //try to find the language in the url
            $extractedLanguage = \FWLanguage::getLanguageIdByCode($this->url->getLangDir());
            $activeLanguages = \FWLanguage::getActiveFrontendLanguages();
            if (
                (
                    !\Cx\Core\Routing\Url::isVirtualLanguageDirsActive() &&
                    !empty($this->url->getLangDir(true))
                ) ||
                !$extractedLanguage
            ) {
                $this->redirectToCorrectLanguageDir();
            }
            if (!in_array($extractedLanguage, array_keys($activeLanguages))) {
                $this->lang = \FWLanguage::getDefaultLangId();
                $this->redirectToCorrectLanguageDir();
            }
            //only set langid according to url if the user has not explicitly requested a language change.
            if (!isset($_REQUEST['setLang'])) {
                $this->lang = $extractedLanguage;

            //the user wants to change the language, but we're still inside the wrong language directory.
            } else if($this->lang != $extractedLanguage) {
                $this->redirectToCorrectLanguageDir();
            }
        }

        if ($this->lang) {
            // used for LinkGenerator
            define('FRONTEND_LANG_ID', $this->lang);
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getDb()->getTranslationListener()->setTranslatableLocale(
                \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID)
            );
            // used to load template file
            \Env::get('init')->setFrontendLangId($this->lang);
        }

        global $section, $command, $history, $url, $_CORELANG,
                $page, $pageId, $themesPages,
                $page_template,
                $now, $start, $end, $plainSection;

        $section = isset($_REQUEST['section']) ? $_REQUEST['section'] : '';
        $command = isset($_REQUEST['cmd']) ? contrexx_addslashes($_REQUEST['cmd']) : '';
        $history = isset($_REQUEST['history']) ? intval($_REQUEST['history']) : 0;


        // Initialize page meta
        $page = null;
        $pageAccessId = 0;
        $page_protected = $pageId = $themesPages =
        $page_template = null;

        // TODO: history (empty($history) ? )
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $session = $cx->getComponent('Session');
        if (
            isset($_GET['pagePreview']) &&
            $_GET['pagePreview'] == 1
        ) {
            // resume existing session, but don't
            // initialize a new session
            $session->getSession(false);
        }
        $this->init($url, $this->lang, \Env::get('em'), ASCMS_INSTANCE_OFFSET.\Env::get('virtualLanguageDirectory'), \FWLanguage::getFallbackLanguageArray());
        try {
            $this->resolvePage();
            $page = $this->getPage();
            // TODO: should this check (for type 'application') moved to \Cx\Core\ContentManager\Model\Entity\Page::getCmd()|getModule() ?
            // only set $section and $command if the requested page is an application
            $command = $this->getCmd();
            $section = $this->getSection();
        } catch (\Cx\Core\Routing\ResolverException $e) {
            try {
                $this->legacyResolve($url, $section, $command);
                $page = $this->getPage();
                $command = $this->getCmd();
                $section = $this->getSection();
            } catch(\Cx\Core\Routing\ResolverException $e) {
                // legacy resolving also failed.
                // provoke a 404
                $page = null;
            }
        }

        if(!$page || !$page->isActive()) {
            //fallback for inexistant error page
            if ($section == 'Error') {
                // If the error module is not installed, show this
                die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
            } else {
                //page not found, redirect to error page.
                \Cx\Core\Csrf\Controller\Csrf::header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('Error'));
                exit;
            }
        }

        // TODO: question: what do we need this for? I think there is no need for this (had been added in r15026)
        //legacy: re-populate cmd and section into $_GET
        $_GET['cmd'] = $command;
        $_GET['section'] = $section;
        // END of TODO question

        //check whether the page is active
        $now = new \DateTime('now');
        $start = $page->getStart();
        $end = $page->getEnd();

        $pageId = $page->getId();

        //access: frontend access id for default requests
        $pageAccessId = $page->getFrontendAccessId();
        //revert the page if a history param has been given
        if($history) {
            //access: backend access id for history requests
            $pageAccessId = $page->getBackendAccessId();
            $logRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\LogEntry');
            try {
                $logRepo->revert($page, $history);
            }
            catch(\Gedmo\Exception\UnexpectedValueException $e) {
            }

            $logRepo->revert($page, $history);
        }
        /*
        //404 for inactive pages
        if(($start > $now && $start != null) || ($now > $end && $end != null)) {
            if ($section == 'Error') {
                // If the error module is not installed, show this
                die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
            }
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Error&id=404');
            exit;
        }*/


        \Env::get('init')->setCustomizedTheme($page->getSkin(), $page->getCustomContent(), $page->getUseSkinForAllChannels());

        $themesPages = \Env::get('init')->getTemplates($page);

        //replace the {NODE_<ID>_<LANG>}- placeholders
        \LinkGenerator::parseTemplate($themesPages);

        //TODO: analyze those, take action.
        //$page_protected = $objResult->fields['protected'];
        $page_protected = $page->isFrontendProtected();

        //$page_access_id = $objResult->fields['frontend_access_id'];
        $page_template  = $themesPages['content'];

        // Authentification for protected pages
        $this->checkPageFrontendProtection($page, $history);

        //TODO: history

        // TODO: refactor system to be able to remove this backward compatibility
        // Backwards compatibility for code pre Contrexx 3.0 (update)
        $_GET['cmd']     = $_POST['cmd']     = $_REQUEST['cmd']     = $command;
        $_GET['section'] = $_POST['section'] = $_REQUEST['section'] = $section;
        // the system should directly use $this->url->getParamArray() instead of using the super globals
        $qsArr = $this->url->getParamArray();
        foreach ($qsArr as $qsParam => $qsArgument) {
            $_GET[$qsParam] = $_REQUEST[$qsParam] = $qsArgument;
        }

        // To clone any module, use an optional integer cmd suffix.
        // E.g.: "shop2", "gallery5", etc.
        // Mind that you *MUST* copy all necessary database tables, and fix any
        // references to your module (section and cmd parameters, database tables)
        // using the MODULE_INDEX constant in the right place both in your code
        // *AND* templates!
        // See the Shop module for an example.
        $arrMatch = array();
        if (preg_match('/^(\D+)(\d+)$/', $section, $arrMatch)) {
            // The plain section/module name, used below
            $plainSection = $arrMatch[1];
        } else {
            $plainSection = $section;
        }
        // The module index.
        // An empty or 1 (one) index represents the same (default) module,
        // values 2 (two) and larger represent distinct instances.
        $moduleIndex = (empty($arrMatch[2]) || $arrMatch[2] == 1 ? '' : $arrMatch[2]);
        define('MODULE_INDEX', $moduleIndex);

        // Start page or default page for no section
        if ($section == 'Home') {
            if (!\Env::get('init')->hasCustomContent()){
                $page_template = $themesPages['home'];
            } else {
                $page_template = $themesPages['content'];
            }
        }

        // this is the case for standalone and backend requests
        if (!$this->page) {
            return null;
        }

        $this->page = clone $this->page;
        $this->page->setVirtual();

        // check for further URL parts to resolve
        if (
            $this->page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION
        ) {
            // does this work for fallback(/aliases)?
            $this->additionalPath = substr('/' . $this->originalUrl->getSuggestedTargetPath(), strlen($this->page->getPath()));
            $componentController = $this->em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent')->findOneBy(array('name'=>$this->page->getModule()));
            if ($componentController) {
                $parts = array();
                if (!empty($this->additionalPath)) {
                    $parts = explode('/', substr($this->additionalPath, 1));
                }
                $componentController->resolve($parts, $this->page);
            }
        }
        $canonicalPage = $this->page;
        if (
            $this->urlPage &&
            $this->urlPage->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_SYMLINK
        ) {
            $canonicalPage = $this->pageRepo->getTargetPage($this->urlPage);
        }

        // set canonical page only in case it hasn't been set already
        $linkHeader = preg_grep('/^Link:.*canonical["\']$/', headers_list());
        if ($linkHeader) {
            $linkHeader = current($linkHeader);
            $linkHeaderParts = explode(':', $linkHeader, 2);
            $link = trim($linkHeaderParts[1]);
            $this->headers['Link'] = $link;
        }

        // don't set canonical page when replying with an application page
        // since we can't know which application pages share the same content.
        // TODO: this should be handled by the affected components themself
        if (
            $canonicalPage->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION &&
            !in_array($canonicalPage->getModule(), array('Home', 'Sitemap', 'Agb', 'Imprint', 'Privacy', 'Ids'))
        ) {
            return $this->page;
        }

        if (!$linkHeader) {
            $link = '<' . \Cx\Core\Routing\Url::fromPage($canonicalPage)->toString() . '>; rel="canonical"';
            header('Link: ' . $link);
            $this->headers['Link'] = $link;
        }

        return $this->page;
    }

    /**
     * Checks for alias request
     * @return Page or null
     */
    public function resolveAlias() {
        // This is our alias, if any
        $path = $this->url->getSuggestedTargetPath();
        $this->path = $path;

        //(I) see what the model has for us, aliases only.
        $result = $this->pageRepo->getPagesAtPath($path, null, null, false, \Cx\Core\ContentManager\Model\Repository\PageRepository::SEARCH_MODE_ALIAS_ONLY);

        //(II) sort out errors
        if(!$result) {
            // no alias
            return null;
        }

        if(!$result['page']) {
            // no alias
            return null;
        }
        if (count($result['page']) != 1) {
            throw new ResolverException('Unable to match a single page for this alias (tried path ' . $path . ').');
        }
        $page = current($result['page']);
        if (!$page->isActive()) {
            throw new ResolverException('Alias found, but it is not active.');
        }

        $langDir = $this->url->getLangDir(true);
        $frontendLang = defined('FRONTEND_LANG_ID') ? FRONTEND_LANG_ID : null;

        // In case the request does contain a virtual language directory,
        // we have to ensure that there does not exist a page by the requested path.
        // Pages do have a higher precidence than aliases, if a virtual language directory
        // is set.
        // If the request does not contain a virtual language directory, but
        // the request does contain a slash (/), then the request does most
        // likely point to a page, as an alias can not contain a slash
        // character. In such case, we also have to check if the request might
        // match an actual page.
        if (
            (
                !empty($langDir) ||
                strpos($path, '/')
            ) &&
            $this->pageRepo->getPagesAtPath($langDir.'/'.$path, null, $frontendLang, false, \Cx\Core\ContentManager\Model\Repository\PageRepository::SEARCH_MODE_PAGES_ONLY)
        ) {
            return null;
        }

        $this->page = $page;

        $params = $this->url->getParamArray();
        if (
            (isset($params['external']) && $params['external'] == 'permanent') ||
            ($this->page->isTargetInternal() && preg_match('/[?&]external=permanent/', $this->page->getTarget()))
        ) {
            if ($this->page->isTargetInternal()) {
                if (isset($params['external']) && $params['external'] == 'permanent') {
                    unset($params['external']);
                }
                if (trim($this->page->getTargetQueryString()) != '') {
                    $params = array_merge($params, explode('&', $this->page->getTargetQueryString()));
                }
                try {
                    $target = \Cx\Core\Routing\Url::fromNodeId($this->page->getTargetNodeId(), $this->page->getTargetLangId(), $params);
                } catch (UrlException $e) {
                    \DBG::log($e->getMessage());
                    return null;
                }
            } else {
                $target = $this->page->getTarget();
            }
            // TODO: Cache this redirect. This is not done yet since we would
            // need to drop the complete page cache (since we don't know which
            // is the correct cache file for this redirect)
            header('Location: ' . $target, true, 301);
            if (!$this->page->isTargetInternal()) {
                header('Connection: close');
            }
            exit;
        }

        // If an alias like /de/alias1 was resolved, redirect to /alias1
        if (!empty($langDir)) {
            $correctUrl = \Cx\Core\Routing\Url::fromPage($this->page);
            // get additinal path parts (like /de/alias1/additional/path)
            $splitQuery = explode('?', $this->url->getPath(), 2);
            $pathParts = explode('/', $splitQuery[0]);
            unset($pathParts[0]);
            if (count($pathParts)) {
                $additionalPath = '/' . implode('/', $pathParts);
                $correctUrl->setPath($correctUrl->getPath() . $additionalPath);
            }
            // set query params (like /de/alias1?foo=bar)
            $correctUrl->setParams($this->url->getParamArray());
            // TODO: Cache this redirect. This is not done yet since we would
            // need to drop the complete page cache (since we don't know which
            // is the correct cache file for this redirect)
            header('Location: ' . $correctUrl->toString(), true, 301);
            exit();
        }
        return $this->page;
    }

    public function redirectToCorrectLanguageDir() {
        $this->url->setLangDir(\FWLanguage::getLanguageCodeById($this->lang));
        $this->headers['Location'] = (string) $this->url;
        $emptyString = '';
        \Env::set('Resolver', $this);
        \Env::get('cx')->getComponent('Cache')->postFinalize($emptyString);
        \Cx\Core\Csrf\Controller\Csrf::header('Location: '.$this->url);
        exit;
    }

    /**
     * Does the resolving work, extends $this->url with targetPath and params.
     */
    public function resolvePage($internal = false) {
        // Abort here in case we're handling a legacy request.
        // The legacy request will be handled by $this->legacyResolve().
        // Important: We must check for $internal == FALSE here, to abort the resolving process
        //            only when we're resolving the initial (original) request.
        //            Internal resolving requests might be called by $this->legacyResolve()
        //            and shall therefore be processed.
        if (!$internal && isset($_REQUEST['section'])) {
            throw new ResolverException('Legacy request');
        }

        $path = $this->url->getSuggestedTargetPath();

        if (!$this->page || $internal) {
            if ($this->pagePreview) {
                if (!empty($this->sessionPage) && !empty($this->sessionPage['pageId'])) {
                    $this->getPreviewPage();
                }
            }

            //(I) see what the model has for us
            $langDirPath = '';
            if (\Cx\Core\Routing\Url::isVirtualLanguageDirsActive()) {
                $langDirPath = $this->url->getLangDir().'/';
            }
            $result = $this->pageRepo->getPagesAtPath($langDirPath . $path, null, $this->lang, false, \Cx\Core\ContentManager\Model\Repository\PageRepository::SEARCH_MODE_PAGES_ONLY);
            if (isset($result['page']) && $result['page'] && $this->pagePreview) {
                if (empty($this->sessionPage)) {
                    if (\Permission::checkAccess(6, 'static', true)) {
                        $result['page']->setActive(true);
                        $result['page']->setDisplay(true);
                        if (($result['page']->getEditingStatus() == 'hasDraft') || (($result['page']->getEditingStatus() == 'hasDraftWaiting'))) {
                            $logEntries = $this->logRepo->getLogEntries($result['page']);
                            $this->logRepo->revert($result['page'], $logEntries[1]->getVersion());
                        }
                    }
                }
            }

            //(II) sort out errors
            if(!$result) {
                throw new ResolverException('Unable to locate page (tried path ' . $path .').');
            }

            if(!$result['page']) {
                throw new ResolverException('Unable to locate page for this language. (tried path ' . $path .').');
            }

            if (!$result['page']->isActive()) {
                throw new ResolverException('Page found, but it is not active.');
            }

            // if user has no rights to see this page, we redirect to login
            $this->checkPageFrontendProtection($result['page']);

            // If an older revision was requested, revert to that in-place:
            if (!empty($this->historyId) && \Permission::checkAccess(6, 'static', true)) {
                $this->logRepo->revert($result['page'], $this->historyId);
            }

            //(III) extend our url object with matched path / params
            $this->url->setTargetPath($result['matchedPath'].$result['unmatchedPath']);
            $this->url->setParams($this->url->getSuggestedParams() . $this->url->getSuggestedAnchor());

            $this->page = $result['page'];
        }
        if (!$this->urlPage) {
            $this->urlPage = clone $this->page;
        }
        /*
          the page we found could be a redirection.
          in this case, the URL object is overwritten with the target details and
          resolving starts over again.
         */
        $target = $this->page->getTarget();
        $isRedirection = $this->page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_REDIRECT;
        $isAlias = $this->page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS;

        //handles alias redirections internal / disables external redirection
        $this->forceInternalRedirection = $this->forceInternalRedirection || $isAlias;

        if($target && ($isRedirection || $isAlias)) {
            // Check if page is a internal redirection and if so handle it
            if($this->page->isTargetInternal()) {
//TODO: add check for endless/circular redirection (a -> b -> a -> b ... and more complex)
                $nId = $this->page->getTargetNodeId();
                $lId = $this->page->getTargetLangId();
                $module = $this->page->getTargetModule();
                $cmd = $this->page->getTargetCmd();
                $qs = $this->page->getTargetQueryString();

                $langId = $lId ? $lId : $this->lang;

                // try to find the redirection target page
                if ($nId) {
                    $targetPage = $this->pageRepo->findOneBy(array('node' => $nId, 'lang' => $langId));

                    // revert to default language if we could not retrieve the specified langauge by the redirection.
                    // so lets try to load the redirection of the current language
                    if(!$targetPage) {
                        if($langId != 0) { //make sure we weren't already retrieving the default language
                            $targetPage = $this->pageRepo->findOneBy(array('node' => $nId, 'lang' => $this->lang));
                            $langId = $this->lang;
                        }
                    }
                } else {
                    $targetPage = $this->pageRepo->findOneByModuleCmdLang($module, $cmd, $langId);

                    // in case we were unable to find the requested page, this could mean that we are
                    // trying to retrieve a module page that uses a string with an ID (STRING_ID) as CMD.
                    // therefore, lets try to find the module by using the string in $cmd and INT in $langId as CMD.
                    // in case $langId is really the requested CMD then we will have to set the
                    // resolved language back to our original language $this->lang.
                    if (!$targetPage) {
                        $targetPage = $this->pageRepo->findOneBymoduleCmdLang($module, $cmd.'_'.$langId, $this->lang);
                        if ($targetPage) {
                            $langId = $this->lang;
                        }
                    }

                    // try to retrieve a module page that uses only an ID as CMD.
                    // lets try to find the module by using the INT in $langId as CMD.
                    // in case $langId is really the requested CMD then we will have to set the
                    // resolved language back to our original language $this->lang.
                    if (!$targetPage) {
                        $targetPage = $this->pageRepo->findOneByModuleCmdLang($module, $langId, $this->lang);
                        $langId = $this->lang;
                    }

                    // revert to default language if we could not retrieve the specified langauge by the redirection.
                    // so lets try to load the redirection of the current language
                    if (!$targetPage) {
                        if ($langId != 0) { //make sure we weren't already retrieving the default language
                            $targetPage = $this->pageRepo->findOneByModuleCmdLang($module, $cmd, $this->lang);
                            $langId = $this->lang;
                        }
                    }
                }

                //check whether we have an active page now
                if (!$targetPage || !$targetPage->isActive()) {
                    $this->page = null;
                    return;
                }

                // the redirection page is located within a different language.
                // therefore, we must set $this->lang to the target's language of the redirection.
                // this is required because we will next try to resolve the redirection target
                if ($langId != $this->lang) {
                    $this->lang = $langId;
                    $this->url->setLangDir(\FWLanguage::getLanguageCodeById($langId));
                    $this->pathOffset = ASCMS_INSTANCE_OFFSET;
                }

                $targetPath = substr($targetPage->getPath(), 1);

                $this->url->setTargetPath($targetPath.$qs);
                $this->url->setPath($targetPath.$qs);
                $this->isRedirection = true;
                $this->resolvePage(true);

                if (empty($this->page)) {
                    \DBG::msg(__METHOD__ . ': target page of internal redirection not found/published');
                    \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromModuleAndCmd('Error'));
                }
            } else { //external target - redirect via HTTP redirect
                if (\FWValidator::isUri($target)) {
                    $this->headers['Location'] = $target;
                    $emptyString = '';
                    \Env::set('Resolver', $this);
                    \Env::set('Page', $this->page);
                    // need to set this before Cache::postFinalize()
                    http_response_code(301);
                    \Env::get('cx')->getComponent('Cache')->postFinalize($emptyString);
                    header('Location: ' . $target, true, 301);
                    header('Connection: close');
                    exit;
                } else {
                    if ($target[0] == '/') {
                        $target = substr($target, 1);
                    }
                    $langDir = '';
                    if (!file_exists(ASCMS_INSTANCE_PATH . ASCMS_INSTANCE_OFFSET . '/' . $target)) {
                        $langCode = '';
                        if (\Cx\Core\Routing\Url::isVirtualLanguageDirsActive()) {
                            $langCode = \FWLanguage::getLanguageCodeById($this->lang);
                        }
                        if (!empty($langCode)) {
                            $langDir = '/' . $langCode;
                        }
                    }

                    $target = ASCMS_INSTANCE_OFFSET . $langDir . '/' . $target;
                    $this->headers['Location'] = $target;
                    $emptyString = '';
                    \Env::set('Resolver', $this);
                    \Env::set('Page', $this->page);
                    // need to set this before Cache::postFinalize()
                    http_response_code(301);
                    \Env::get('cx')->getComponent('Cache')->postFinalize($emptyString);
                    header('Location: ' . $target, true, 301);
                    exit;
                }
            }
        }

        //if we followed one or more redirections, the user shall be redirected.
        if ($this->isRedirection && !$this->forceInternalRedirection) {
            $params = $this->url->getSuggestedParams();
            $target = $this->page->getURL($this->pathOffset, array());
            $target->setParams($params);
            $this->headers['Location'] = $target->toString() . $this->url->getSuggestedAnchor();
            $emptyString = '';
            \Env::set('Resolver', $this);
            \Env::set('Page', $this->page);
            // need to set this before Cache::postFinalize()
            http_response_code(301);
            \Env::get('cx')->getComponent('Cache')->postFinalize($emptyString);
            header('Location: ' . $target->toString() . $this->url->getSuggestedAnchor(), true, 301);
            exit;
        }

        // in case the requested page is of type fallback, we will now handle/load this page
        $this->handleFallbackContent($this->page, !$internal);

        // set legacy <section> and <cmd> in case the requested page is an application
        if ($this->page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION
                || $this->page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK) {
            $this->command = $this->page->getCmd();
            $this->section = $this->page->getModule();
        }
    }

    public function legacyResolve($url, $section, $command)
    {
        $objFWUser = \FWUser::getFWUserObject();

        /*
          The Resolver couldn't find a page.
          We're looking at one of the following situations, which are treated in the listed order:
           a) Request for the 'home' page
           b) Legacy request with section / cmd
           c) Request for inexistant page
          We try to locate a module page via cmd and section (if provided).
          If that doesn't work, an error is shown.
        */

        // a: 'home' page
        $urlPointsToHome =    $url->getSuggestedTargetPath() == 'index.php'
                           || $url->getSuggestedTargetPath() == '';
        //    user probably tried requesting the home-page
        if(!$section && $urlPointsToHome) {
            $section = 'Home';
        }
        $this->setSection($section, $command);

        // b(, a): fallback if section and cmd are specified
        if ($section) {
            if ($section == 'logout') {
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                $cx->getComponent('Session')->getSession();
                if ($objFWUser->objUser->login()) {
                    $objFWUser->logout();
                }
            }

            $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            // If the database uses a case insensitive collation, $section needn't be the exact component name to find a page
            $this->page = $pageRepo->findOneByModuleCmdLang($section, $command, FRONTEND_LANG_ID);

            //fallback content
            if (!$this->page) {
                return;
            }

            // make legacy-requests case insensitive works only if database-collation is case insensitive as well
            $this->setSection($this->page->getModule(), $this->page->getCmd());

            $this->checkPageFrontendProtection($this->page);

            $this->handleFallbackContent($this->page);
        }

        // c: inexistant page gets catched below.
    }

    /**
     * Returns the preview page built from the session page array.
     * @return Cx\Core\ContentManager\Model\Entity\Page $page
     */
    private function getPreviewPage() {
        $data = $this->sessionPage;

        $page = $this->pageRepo->findOneById($data['pageId']);
        if (!$page) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $node = new \Cx\Core\ContentManager\Model\Entity\Node();
            $node->setParent($this->nodeRepo->getRoot());
            $node->setLvl(1);
            $this->nodeRepo->getRoot()->addChildren($node);
            $node->addPage($page);
            $page->setNode($node);

            $this->pageRepo->addVirtualPage($page);
        }

        unset($data['pageId']);
        $page->setLang(\FWLanguage::getLanguageIdByCode($data['lang']));
        unset($data['lang']);
        $page->updateFromArray($data);
        $page->setUpdatedAtToNow();
        $page->setActive(true);
        $page->setVirtual(true);
        $page->validate();

        return $page;
    }

    /**
     * Checks whether $page is of type 'fallback'. Loads fallback content if yes.
     * @param Cx\Core\ContentManager\Model\Doctrine $page
     * @param boolean $requestedPage Set to TRUE (default) if the $page passed by $page is the first resolved page (actual requested page)
     * @throws ResolverException
     */
    public function handleFallbackContent($page, $requestedPage = true) {
        //handle untranslated pages - replace them by the right language version.

        // Important: We must reset the modified $page object here.
        // Otherwise the EntityManager holds false information about the page.
        // I.e. type might be 'application' instead of 'fallback'
        // See bug-report #1536
        $page = $this->pageRepo->findOneById($page->getId());

        if (
            $page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK ||
            $page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_SYMLINK
        ) {
            // in case the first resolved page (= original requested page) is a fallback page
            // we must check here if this very page is active.
            // If we miss this check, we would only check if the referenced fallback page is active!
            if ($requestedPage && !$page->isActive()) {
                return;
            }

            // if this page is protected, we do not follow fallback
            $this->checkPageFrontendProtection($page);

            try {
                if ($page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_SYMLINK) {
                    $fallbackPage = $this->pageRepo->getTargetPage($page);
                } else {
                    $fallbackPage = $this->getFallbackPage($page);
                }
            } catch (\Exception $e) {
                \DBG::msg(__METHOD__ . ': '. $e->getMessage());
                //page not found, redirect to error page.
                \Cx\Core\Csrf\Controller\Csrf::header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('Error'));
                exit;
            }

            // due that the fallback is located within a different language
            // we must set $this->lang to the fallback's language.
            // this is required because we will next try to resolve the page
            // that is referenced by the fallback page
            $this->lang = $fallbackPage->getLang();
            $this->url->setLangDir(\FWLanguage::getLanguageCodeById($this->lang));
            $this->url->setSuggestedTargetPath(substr($fallbackPage->getPath(), 1));

            // now lets resolve the page that is referenced by our fallback page
            $this->resolvePage(true);
            // fallback pages use theme options of their target page, symlink use their own
            $page->setContentOf(
                $this->page,
                $page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK
            );

            // Important: We must assigne a copy of $page to $this->path here.
            // Otherwise, the virtual fallback page ($this->page) will also
            // be reset, when we reset (see next command) the original
            // requested page $page.
            $this->page = clone $page;

            // Due to the fallback-resolving, the virtual language directory
            // is currently set to the fallback language. Therefore we must set
            // it back to the language of the original request. Same also applies
            // to $this->lang, which was used to resolv the fallback page(s).
            $this->url->setLangDir(\FWLanguage::getLanguageCodeById($page->getLang()));
            $this->lang = $page->getLang();
        }
    }

    public function getFallbackPage($page) {
        $fallbackPage = null;
        if (isset($this->fallbackLanguages[$page->getLang()])) {
            $langId = $this->fallbackLanguages[$page->getLang()];
            $fallbackPage = $page->getNode()->getPage($langId);
        }
        if (!$fallbackPage) {
            throw new ResolverException('Followed fallback page, but couldn\'t find content of fallback Language');
        }
        return $fallbackPage;
    }

    /**
     * Checks if this page can be displayed in frontend, redirects to login of not
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to check
     * @param int $history (optional) Revision of page to use, 0 means current, default 0
     */
    public function checkPageFrontendProtection($page, $history = 0) {
        $page_protected = $page->isFrontendProtected();
        $pageAccessId = $page->getFrontendAccessId();
        if ($history) {
            $pageAccessId = $page->getBackendAccessId();
        }

        // login pages are unprotected by design
        $checkLogin = array($page);
        while (count($checkLogin)) {
            $currentPage = array_pop($checkLogin);
            if ($currentPage->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK) {
                try {
                    array_push($checkLogin, $this->getFallbackPage($currentPage));
                } catch (ResolverException $e) {}
            }
            if ($currentPage->getModule() == 'Login') {
                return;
            }
        }

        // Authentification for protected pages
        if (($page_protected || $history) &&
            (
               !isset($_REQUEST['section']) ||
                $_REQUEST['section'] != 'Login'
            )
        ) {
            if (\FWUser::getFWUserObject()->objUser->login()) {
                if ($page_protected) {
                    if (!\Permission::checkAccess($pageAccessId, 'dynamic', true)) {
                        $link=base64_encode(\Env::get('cx')->getRequest()->getUrl()->toString());
                        \Cx\Core\Csrf\Controller\Csrf::header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('Login', 'noaccess', '', array('redirect' => $link)));
                        exit;
                    }
                }
                if ($history && !\Permission::checkAccess(78, 'static', true)) {
                    $link=base64_encode(\Env::get('cx')->getRequest()->getUrl()->toString());
                    \Cx\Core\Csrf\Controller\Csrf::header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('Login', 'noaccess', '', array('redirect' => $link)));
                    exit;
                }
            } else {
                if (isset($_GET['redirect'])) {
                    $link = $_GET['redirect'];
                } else {
                    $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                    if ($this->aliaspage) {
                        $link = \Cx\Core\Routing\Url::fromPage(
                            $this->aliaspage,
                            $cx->getRequest()->getUrl()->getParamArray()
                        )->toString();
                    } else {
                        $link = \Env::get('cx')->getRequest()->getUrl()->toString();
                    }
                    $link = base64_encode($link);
                }
                \Cx\Core\Csrf\Controller\Csrf::header(
                    'Location: '. \Cx\Core\Routing\Url::fromModuleAndCmd(
                        'Login',
                        '',
                        '',
                        array('redirect' => $link)
                    )
                );
                exit;
            }
        }
    }

    public function getPage() {
        return $this->page;
    }

    public function getURL() {
        return $this->url;
    }

    /**
     * Returns the resolved module name (if any, empty string if none)
     * @return String Module name
     */
    public function getSection() {
        return $this->section;
    }

    /**
     * Returns the resolved module command (if any, empty string if none)
     * @return String Module command
     */
    public function getCmd() {
        return $this->command;
    }

    /**
     * Sets the value of the resolved module name and command
     * This should not be called from any (core_)module!
     * For legacy requests only!
     *
     * @param String $section Module name
     * @param String $cmd Module command
     * @todo Remove this method as soon as legacy request are no longer possible
     */
    public function setSection($section, $command = '') {
        $this->section = $section;
        $this->command = $command;
    }

    /**
     * Returns the headers set by this class
     * @return array key=>value style array
     */
    public function getHeaders() {
        $response = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
        if ($response->getExpirationDate()) {
            $this->headers['Expires'] = $response->getExpirationDate()->format('r');
        }
        return $this->headers;
    }

    /**
     * Returns the passed path additional to the resolved page's path
     * @return string Offset path without leading slash
     */
    public function getAdditionalPath() {
        return substr($this->additionalPath, 1);
    }
}
