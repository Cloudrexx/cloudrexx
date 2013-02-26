<?php

/**
 * initFrontend
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  core_frontend
 * @todo        Capitalize all class names in project
 */

// Try to enable APC
$apcEnabled = false;
if (extension_loaded('apc')) {
    if (ini_get('apc.enabled')) {
        $apcEnabled = true;
    } else {
        ini_set('apc.enabled', 1);
        if (ini_get('apc.enabled')) {
            $apcEnabled = true;
        }
    }
}

// Disable eAccelerator if active
if (extension_loaded('eaccelerator')) {
    ini_set('eaccelerator.enable', 0);
    ini_set('eaccelerator.optimizer', 0);
}

// Try to set required memory_limit if not enough
preg_match('/^\d+/', ini_get('memory_limit'), $memoryLimit);
if ($apcEnabled) {
    if ($memoryLimit[0] < 32) {
        ini_set('memory_limit', '32M');
    }
} else {
    if ($memoryLimit[0] < 48) {
        ini_set('memory_limit', '48M');
    }
}

//iconv_set_encoding('output_encoding', 'utf-8');
//iconv_set_encoding('input_encoding', 'utf-8');
//iconv_set_encoding('internal_encoding', 'utf-8');

$starttime = explode(' ', microtime());

// Makes code analyzer warnings go away
$loggableListener = null;

/**
 * This needs to be initialized before loading config/doctrine.php
 * Because we overwrite the Gedmo model (so we need to load our model
 * before doctrine loads the Gedmo one)
 */
require_once(ASCMS_CORE_PATH.'/ClassLoader/ClassLoader.class.php');
$cl = new \Cx\Core\ClassLoader\ClassLoader(ASCMS_DOCUMENT_ROOT, true, $customizing);

/**
 * Environment repository
 */
require_once($cl->getFilePath(ASCMS_CORE_PATH.'/Env.class.php'));
\Env::set('ClassLoader', $cl);

/**
 * Doctrine configuration
 * Loaded after installer redirect (not configured before installer)
 */
$incDoctrineStatus = include_once($cl->getFilePath(ASCMS_PATH.ASCMS_PATH_OFFSET.'/config/doctrine.php'));

if ($incDoctrineStatus === false) {
    die('System halted: Unable to load basic configuration!');
}

// Check if system is running
if ($_CONFIG['systemStatus'] != 'on') {
    print file_get_contents(ASCMS_DOCUMENT_ROOT.'/offline.html');
    die(1);
}
Env::set('config', $_CONFIG);
Env::set('ftpConfig', $_FTPCONFIG);

/**
 * Include all the required files.
 */
$cl->loadFile(ASCMS_CORE_PATH.'/API.php');
// Temporary fix until all GET operation requests will be replaced by POSTs
CSRF::setFrontendMode();

// Initialize database object
$errorMsg = '';
/**
 * Database object
 * @global ADONewConnection $objDatabase
 */
$objDatabase = getDatabaseObject($errorMsg);
Env::set('db', $objDatabase);
Env::set('pageguard', new PageGuard($objDatabase));

if (!$objDatabase) {
    die(
        'Database error.'.
        ($errorMsg != '' ? "<br />Message: $errorMsg" : '')
    );
}

DBG::set_adodb_debug_mode();

createModuleConversionTables();
// Initialize base system
$objInit = new InitCMS('frontend', Env::em());
Env::set('init', $objInit);

// make sure license data is up to date (this updates active available modules)
$license = \Cx\Core_Modules\License\License::getCached($_CONFIG, $objDatabase);
$oldState = $license->getState();
$license->check();
if ($oldState != $license->getState()) {
    $license->save(new \settingsManager(), $objDatabase);
}
if ($license->isFrontendLocked()) {
    print file_get_contents(ASCMS_DOCUMENT_ROOT.'/offline.html');
    die(1);
}

$request = !empty($_GET['__cap']) ? $_GET['__cap'] : '';
$url = \Cx\Core\Routing\Url::fromCapturedRequest($request, ASCMS_PATH_OFFSET, $_GET);
$resolver = new \Cx\Core\Routing\Resolver($url, null, Env::em(), null, null);
\Env::set('Resolver', $resolver);
$aliaspage = $resolver->resolveAlias();

$_LANGID = '';
$redirectToCorrectLanguageDir = function() use ($url, &$_LANGID, $_CONFIG) {
    $url->setLangDir(\FWLanguage::getLanguageCodeById($_LANGID));

    CSRF::header('Location: '.$url);
    exit;
};

if ($aliaspage != null) {
    $_LANGID = $aliaspage->getTargetLangId();
} else {
    /**
     * Frontend language ID
     * @global integer $_LANGID
     * @todo    Globally replace this with either the FRONTEND_LANG_ID, or LANG_ID constant
     */
    $_LANGID = $objInit->getFallbackFrontendLangId();

    //try to find the language in the url
    $extractedLanguage = 0;

    $extractedLanguage = \FWLanguage::getLanguageIdByCode($url->getLangDir());
    $activeLanguages = \FWLanguage::getActiveFrontendLanguages();
    if (!in_array($extractedLanguage, array_keys($activeLanguages))) {
        $_LANGID = \FWLanguage::getDefaultLangId();
        $redirectToCorrectLanguageDir();
    }
    if (!$extractedLanguage) {
        $redirectToCorrectLanguageDir();
    }
    //only set langid according to url if the user has not explicitly requested a language change.
    if(!isset($_REQUEST['setLang'])) {
        $_LANGID = $extractedLanguage;
    }
    else if($_LANGID != $extractedLanguage) { //the user wants to change the language, but we're still inside the wrong language directory.
        $redirectToCorrectLanguageDir();
    }
}
if (!$license->isInLegalComponents('fulllanguage') && $_LANGID != \FWLanguage::getDefaultLangId()) {
    $_LANGID = \FWLanguage::getDefaultLangId();
    $redirectToCorrectLanguageDir();
}

// Post-2.1
$objInit->setFrontendLangId($_LANGID);
define('FRONTEND_LANG_ID', $_LANGID);
define('LANG_ID', $_LANGID);

//expose the virtual language directory to the rest of the cms
//please do not access this variable directly, use Env::get().
$virtualLanguageDirectory = '/'.$url->getLangDir();
Env::set('virtualLanguageDirectory', $virtualLanguageDirectory);

// TODO: this constanst used to be located in config/set_constants.php, but needed to be relocated to this very place,
// because it depends on Env::get('virtualLanguageDirectory').
// Find an other solution; probably best is to replace CONTREXX_SCRIPT_PATH by a prettier method
define('CONTREXX_SCRIPT_PATH',
        ASCMS_PATH_OFFSET.
        Env::get('virtualLanguageDirectory').
        '/'.
        CONTREXX_DIRECTORY_INDEX);

// Caching-System
/**
 * Include the cache module.  The cache is initialized right afterwards.
 */
$objCache = new Cache();
$objCache->startCache();


// Load interface language data
/**
 * Core language data
 * @global array $_CORELANG
 */
$_CORELANG = $objInit->loadLanguageData('core');


// Webapp Intrusion Detection System
$objSecurity = new Security;
$_GET = $objSecurity->detectIntrusion($_GET);
$_POST = $objSecurity->detectIntrusion($_POST);
$_COOKIE = $objSecurity->detectIntrusion($_COOKIE);
$_REQUEST = $objSecurity->detectIntrusion($_REQUEST);


// initialize objects
/**
 * Template object
 * @global \Cx\Core\Html\Sigma $objTemplate
 */
$objTemplate = new \Cx\Core\Html\Sigma(ASCMS_THEMES_PATH);
$objTemplate->setErrorHandling(PEAR_ERROR_DIE);


$section = isset($_REQUEST['section']) ? $_REQUEST['section'] : '';
$command = isset($_REQUEST['cmd']) ? contrexx_addslashes($_REQUEST['cmd']) : '';
$page    = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
$history = isset($_REQUEST['history']) ? intval($_REQUEST['history']) : 0;

if ($section == 'upload') {//handle uploads separately, since they have no content
    $sessionObj = new cmsSession();
    if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/upload/index.class.php'))
        die ($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
    $objUploadModule = new Upload();
    $objUploadModule->getPage();
    //execution never reaches this point
}
if ($section == 'captcha') {
    /*
     * Captcha Module
     *
     * Generates no output, requests are answered by a die()
     * @since   2.1.5
     */
    FWCaptcha::getInstance()->getPage();
}
if ($section == 'frontendEditing') {
    $sessionObj = new cmsSession();
    $objFrontendEditing = new frontendEditing(Env::em());
    $objFrontendEditing->performAction();
}
if ($section == 'jsondata') {
// TODO: handle expired sessions in any xhr callers.
    $json = new \Cx\Core\Json\JsonData();
// TODO: Verify that the arguments are actually present!
    $adapter = contrexx_input2raw($_GET['object']);
    $method = contrexx_input2raw($_GET['act']);
// TODO: Replace arguments by something reasonable
    $arguments = array('get' => $_GET, 'post' => $_POST);
    echo $json->jsondata($adapter, $method, $arguments);
    die();
}
if ($section == "newsletter") {
    if (Newsletter::isTrackLink()) {
        //handle link tracker from newsletter, since user should be redirected to the link url
        /*
         * Newsletter Module
         *
         * Generates no output, requests are answered by a redirect to foreign site
         *
         */
        Newsletter::trackLink();
        //execution should never reach this point, but let's be safe and call exit anyway
        exit;
    } elseif ($command == 'displayInBrowser') {
        Newsletter::displayInBrowser();
        //execution should never reach this point, but let's be safe and call exit anyway
        exit;
    }

    // regular newsletter request (like subscribing, profile management, etc).
    // must not abort by an exit call here!
}


// Initialize page meta
$page = null;
$pageAccessId = 0;
$page_protected = 0;
$page_protected = $page_redirect = $pageId = $themesPages =
$page_content = $page_template = $page_title = $page_metatitle =
$page_catname = $page_keywords = $page_desc = $page_robots =
$pageCssName = $page_modified = null;

function setModuleIndexAndReturnPlainSection($section) {
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

    return $plainSection;
}


// If standalone is set, then we will not have to initialize/load any content page related stuff
$isRegularPageRequest = !isset($_REQUEST['standalone']) || $_REQUEST['standalone'] == 'false';

// Get instance of FWUser object
$objFWUser = FWUser::getFWUserObject();

// Regular page request
if ($isRegularPageRequest) {
// TODO: history (empty($history) ? )
    if (isset($_GET['pagePreview']) && $_GET['pagePreview'] == 1 && empty($sessionObj)) {
        $sessionObj = new cmsSession();
    }
    $resolver->init($url, FRONTEND_LANG_ID, Env::em(), ASCMS_PATH_OFFSET.Env::get('virtualLanguageDirectory'), FWLanguage::getFallbackLanguageArray());
    try {
        $resolver->resolve();
        $page = $resolver->getPage();
// TODO: should this check (for type 'application') moved to \Cx\Core\ContentManager\Model\Doctrine\Entity\Page::getCmd()|getModule() ?
        // only set $section and $command if the requested page is an application
        $command = $resolver->getCmd();
        $section = $resolver->getSection();
    }
    catch (\Cx\Core\Routing\ResolverException $e) {
        try {
            $resolver->legacyResolve($url, $section, $command);
            $page = $resolver->getPage();
            $command = $resolver->getCmd();
            $section = $resolver->getSection();
        } catch(\Cx\Core\Routing\ResolverException $e) {
            // legacy resolving also failed.
            // provoke a 404
            $page = null;
        }
    }

    if(!$page || !$page->isActive() ||
            (!empty($section) && !$license->isInLegalFrontendComponents($section))) {
        //fallback for inexistant error page
        if($section == 'error') {
            // If the error module is not installed, show this
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        }
        else {
            //page not found, redirect to error page.
            header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('error'));
            exit;
        }
    }

// TODO: question: what do we need this for? I think there is no need for this (had been added in r15026)
    //legacy: re-populate cmd and section into $_GET
    $_GET['cmd'] = $command;
    $_GET['section'] = $section;
// END of TODO question

    //check whether the page is active
    $now = new DateTime('now');
    $start = $page->getStart();
    $end = $page->getEnd();

    $pageId = $page->getId();

    //access: frontend access id for default requests
    $pageAccessId = $page->getFrontendAccessId();
    //revert the page if a history param has been given
    if($history) {
        //access: backend access id for history requests
        $pageAccessId = $page->getBackendAccessId();
        $logRepo = Env::em()->getRepository('Gedmo\Loggable\Entity\LogEntry');
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
        if ($section == 'error') {
            // If the error module is not installed, show this
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        }
        CSRF::header('Location: index.php?section=error&id=404');
        exit;
        }*/


    $objInit->setCustomizedTheme($page->getSkin(), $page->getCustomContent());

    $themesPages = $objInit->getTemplates();

    //replace the {NODE_<ID>_<LANG>}- placeholders
    LinkGenerator::parseTemplate($themesPages);

    // Frontend Editing: content has to be replaced with preview code if needed.
    $page_content = $page->getContent();
    if ($_CONFIG['frontendEditingStatus'] == 'on') {
        if (!empty($_REQUEST['frontEditing'])) {
            $themesPages['index']   = '{CONTENT_FILE}';
            $themesPages['content'] = '{CONTENT_TEXT}';
            $themesPages['home']    = '{CONTENT_TEXT}';

            if (isset($_POST['previewContent'])) {
                $page_content = preg_replace('/\[\[([A-Z0-9_-]+)\]\]/', '{\\1}',
                                    html_entity_decode(stripslashes($_POST['previewContent']),
                                        ENT_QUOTES, CONTREXX_CHARSET));
            }
        } else {
            $page_content = '<div id="fe_PreviewContent">'. $page_content.'</div>';
        }
    }

    $page_catname = contrexx_raw2xhtml($page->getTitle());

    $page_title     = contrexx_raw2xhtml($page->getContentTitle());
    $page_metatitle = contrexx_raw2xhtml($page->getMetatitle());
    $page_keywords  = contrexx_raw2xhtml($page->getMetakeys());
    $page_robots    = contrexx_raw2xhtml($page->getMetarobots());
    $pageCssName    = $page->getCssName();
    $page_desc      = contrexx_raw2xhtml($page->getMetadesc());
//TODO: analyze those, take action.
    //$page_redirect  = $objResult->fields['redirect'];
    //$page_protected = $objResult->fields['protected'];
    $page_protected = $page->isFrontendProtected();

    //$page_access_id = $objResult->fields['frontend_access_id'];
    $page_template  = $themesPages['content'];
    $page_modified  = $page->getUpdatedAt()->getTimestamp();

    // Authentification for protected pages
    // This is only done for regular page requests ($isRegularPageRequest == TRUE)
    //
    $resolver->checkPageFrontendProtection($page, $history);

//TODO: history
}

// TODO: refactor system to be able to remove this backward compatibility
// Backwards compatibility for code pre Contrexx 3.0 (update)
$_GET['cmd']     = $_POST['cmd']     = $_REQUEST['cmd']     = $command;
$_GET['section'] = $_POST['section'] = $_REQUEST['section'] = $section;
$plainSection = setModuleIndexAndReturnPlainSection($section);

// Load interface language data
/**
 * Module specific data
 * @global array $_ARRAYLANG
 */
$_ARRAYLANG = $objInit->loadLanguageData($plainSection);


// Handle requests that have been requested using the url-modificator 'standalone'
if (!$isRegularPageRequest) {
    // ATTENTION: These requests are not protected by the content manager
    //            and must therefore be authorized by the calling component itself!
    switch ($plainSection) {
        case 'immo':
            /** @ignore */
            if (!$cl->loadFile(ASCMS_MODULE_PATH.'/immo/index.class.php'))
                die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
            $objImmo = new Immo('');
            $objImmo->getPage();
            break;
    }

    // Force execution stop of standalone scripts (if not already happend as intended by the requested component)
    exit;
}


// Initialize the navigation
$objNavbar = new Navigation($pageId, $page);


// Start page or default page for no section
if ($section == 'home') {
    if (!$objInit->hasCustomContent()){
        $page_template = $themesPages['home'];}
    else
        $page_template = $themesPages['content'];
}


// Initialize counter and track search engine robot
$objCounter = new statsLibrary();
$objCounter->checkForSpider();


// TODO: Move this code to each module and call them through a well defined API
////////////////////////////////////////////////////////////////////////////////
//// START: GLOBAL CONTENT MODULE FUNCTIONS ////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
// Set Blocks
if ($_CONFIG['blockStatus'] == '1') {
    /** @ignore */
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/block/index.class.php')) {
        block::setBlocks($page_content, $page);
        block::setBlocks($themesPages, $page);
// TODO: this call in unhappy, becase the content/home template already gets parsed just the line above
        block::setBlocks($page_template, $page);
    }
}

// make the replacements for the data module
if ($_CONFIG['dataUseModule'] && $cl->loadFile(ASCMS_MODULE_PATH.'/data/dataBlocks.class.php')) {
    $lang = $objInit->loadLanguageData('data');
    $dataBlocks = new dataBlocks($lang);
    $page_content = $dataBlocks->replace($page_content);
    $themesPages = $dataBlocks->replace($themesPages);
    $page_template = $dataBlocks->replace($page_template);
}

$arrMatches = array();
// Set news teasers
if ($_CONFIG['newsTeasersStatus'] == '1') {
    // set news teasers in the content
    if (preg_match_all('/{TEASERS_([0-9A-Z_-]+)}/', $page_content, $arrMatches)) {
        /** @ignore */
        if ($cl->loadFile(ASCMS_CORE_MODULE_PATH.'/news/lib/teasers.class.php')) {
            $objTeasers = new Teasers();
            $objTeasers->setTeaserFrames($arrMatches[1], $page_content);
        }
    }
    // set news teasers in the page design
    if (preg_match_all('/{TEASERS_([0-9A-Z_-]+)}/', $page_template, $arrMatches)) {
        /** @ignore */
        if ($cl->loadFile(ASCMS_CORE_MODULE_PATH.'/news/lib/teasers.class.php')) {
            $objTeasers = new Teasers();
            $objTeasers->setTeaserFrames($arrMatches[1], $page_template);
        }
    }
    // set news teasers in the website design
    if (preg_match_all('/{TEASERS_([0-9A-Z_-]+)}/', $themesPages['index'], $arrMatches)) {
        /** @ignore */
        if ($cl->loadFile(ASCMS_CORE_MODULE_PATH.'/news/lib/teasers.class.php')) {
            $objTeasers = new Teasers();
            $objTeasers->setTeaserFrames($arrMatches[1], $themesPages['index']);
        }
    }
}
// Set download groups
if (preg_match_all('/{DOWNLOADS_GROUP_([0-9]+)}/', $page_content, $arrMatches)) {
    /** @ignore */
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/downloads/lib/downloadsLib.class.php')) {
        $objDownloadLib = new DownloadsLibrary();
        $objDownloadLib->setGroups($arrMatches[1], $page_content);
    }
}

//--------------------------------------------------------
// Parse the download block 'downloads_category_#ID_list'
//--------------------------------------------------------
$downloadBlock = preg_replace_callback(
    "/<!--\s+BEGIN\s+downloads_category_(\d+)_list\s+-->(.*)<!--\s+END\s+downloads_category_\g1_list\s+-->/s",
    function($matches) {
        \Env::get('init')->loadLanguageData('downloads');
        if (isset($matches[0])) {
            $objDownloadsModule = new downloads($matches[0], array('category' => $matches[1]));
            return $objDownloadsModule->getPage();
        }
    },
    $page_content);
$page_content = $downloadBlock;

// Set NewsML messages
if ($_CONFIG['feedNewsMLStatus'] == '1') {
    if (preg_match_all('/{NEWSML_([0-9A-Z_-]+)}/', $page_content, $arrMatches)) {
        /** @ignore */
        if ($cl->loadFile(ASCMS_MODULE_PATH.'/feed/newsML.class.php')) {
            $objNewsML = new NewsML();
            $objNewsML->setNews($arrMatches[1], $page_content);
        }
    }
    if (preg_match_all('/{NEWSML_([0-9A-Z_-]+)}/', $page_template, $arrMatches)) {
        /** @ignore */
        if ($cl->loadFile(ASCMS_MODULE_PATH.'/feed/newsML.class.php')) {
            $objNewsML = new NewsML();
            $objNewsML->setNews($arrMatches[1], $page_template);
        }
    }
    if (preg_match_all('/{NEWSML_([0-9A-Z_-]+)}/', $themesPages['index'], $arrMatches)) {
        /** @ignore */
        if ($cl->loadFile(ASCMS_MODULE_PATH.'/feed/newsML.class.php')) {
            $objNewsML = new NewsML();
            $objNewsML->setNews($arrMatches[1], $themesPages['index']);
        }
    }
}


// Set popups
if (preg_match('/{POPUP_JS_FUNCTION}/', $themesPages['index'])) {
    /** @ignore */
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/popup/index.class.php')) {
        $objPopup = new popup();
        if (preg_match('/{POPUP}/', $themesPages['index'])) {
            $objPopup->setPopup($themesPages['index'], $page->getNode()->getId());
        }
        $objPopup->_setJS($themesPages['index']);
    }
}


// Get Headlines
$modulespath = ASCMS_CORE_MODULE_PATH.'/news/lib/headlines.class.php';
$headlinesNewsPlaceholder = '{HEADLINES_FILE}';
if (   file_exists($modulespath)
    && (   strpos($page_content, $headlinesNewsPlaceholder) !== false
        || strpos($themesPages['index'], $headlinesNewsPlaceholder) !== false
        || strpos($themesPages['sidebar'], $headlinesNewsPlaceholder) !== false
        || strpos($page_template, $headlinesNewsPlaceholder) !== false)
) {
    $newsHeadlinesObj = new newsHeadlines($themesPages['headlines']);
    $homeHeadlines = $newsHeadlinesObj->getHomeHeadlines();
    $page_content           = str_replace($headlinesNewsPlaceholder, $homeHeadlines, $page_content);
    $themesPages['index']   = str_replace($headlinesNewsPlaceholder, $homeHeadlines, $themesPages['index']);
    $themesPages['sidebar'] = str_replace($headlinesNewsPlaceholder, $homeHeadlines, $themesPages['sidebar']);
    $page_template          = str_replace($headlinesNewsPlaceholder, $homeHeadlines, $page_template);
}


// Get Top news
$modulespath = ASCMS_CORE_MODULE_PATH.'/news/lib/top_news.class.php';
$topNewsPlaceholder = '{TOP_NEWS_FILE}';
if (   file_exists($modulespath)
    && (   strpos($page_content, $topNewsPlaceholder) !== false
        || strpos($themesPages['index'], $topNewsPlaceholder) !== false
        || strpos($themesPages['sidebar'], $topNewsPlaceholder) !== false
        || strpos($page_template, $topNewsPlaceholder) !== false)
) {
    $newsTopObj = new newsTop($themesPages['top_news']);
    $homeTopNews = $newsTopObj->getHomeTopNews();
    $page_content           = str_replace($topNewsPlaceholder, $homeTopNews, $page_content);
    $themesPages['index']   = str_replace($topNewsPlaceholder, $homeTopNews, $themesPages['index']);
    $themesPages['sidebar'] = str_replace($topNewsPlaceholder, $homeTopNews, $themesPages['sidebar']);
    $page_template          = str_replace($topNewsPlaceholder, $homeTopNews, $page_template);
}


// Get Calendar Events
$modulespath = ASCMS_MODULE_PATH.'/calendar/headlines.class.php';
$eventsPlaceholder = '{EVENTS_FILE}';
if (   MODULE_INDEX < 2
    && $_CONFIG['calendarheadlines']
    && (   strpos($page_content, $eventsPlaceholder) !== false
        || strpos($themesPages['index'], $eventsPlaceholder) !== false
        || strpos($themesPages['sidebar'], $eventsPlaceholder) !== false
        || strpos($page_template, $eventsPlaceholder) !== false)
    && file_exists($modulespath)  
) {
    $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('calendar'));
    $calHeadlinesObj = new calHeadlines($themesPages['calendar_headlines']);
    $calHeadlines = $calHeadlinesObj->getHeadlines();
    $page_content           = str_replace($eventsPlaceholder, $calHeadlines, $page_content);
    $themesPages['index']   = str_replace($eventsPlaceholder, $calHeadlines, $themesPages['index']);
    $themesPages['sidebar'] = str_replace($eventsPlaceholder, $calHeadlines, $themesPages['sidebar']);
    $page_template          = str_replace($eventsPlaceholder, $calHeadlines, $page_template);
}

// Get Calendar Box Three
if ($cl->loadFile(ASCMS_MODULE_PATH.'/calendar/headlines.class.php')) {
    $calHeadlinesObj2 = new calHeadlines($themesPages['calendar_headlines']);
    $calHeadlineThreeBoxes = $calHeadlinesObj2->showThreeBoxes();
    if (preg_match('/{CALENDAR_HEADLINES}/', $page_content)) {
        $page_content = str_replace('{CALENDAR_HEADLINES}', $calHeadlineThreeBoxes, $page_content);
    }
    if (preg_match('/{CALENDAR_HEADLINES}/', $page_template)) {
        $page_template = str_replace('{CALENDAR_HEADLINES}', $calHeadlineThreeBoxes, $page_template);
    }
    if (preg_match('/{CALENDAR_HEADLINES}/', $themesPages['index'])) {
        $themesPages['index'] = str_replace('{CALENDAR_HEADLINES}', $calHeadlineThreeBoxes, $themesPages['index']);
    }
    if (preg_match('/{CALENDAR_HEADLINES}/', $themesPages['sidebar'])) {
        $themesPages['sidebar'] = str_replace('{CALENDAR_HEADLINES}', $calHeadlineThreeBoxes, $themesPages['sidebar']);
    }
}


// Get immo headline
$modulespath = ASCMS_MODULE_PATH.'/immo/headlines/index.class.php';
if (file_exists($modulespath)) {
    $immoHeadlines = new immoHeadlines($themesPages['immo']);
    $immoHomeHeadlines = $immoHeadlines->getHeadlines();
    $page_content = str_replace('{IMMO_FILE}', $immoHomeHeadlines, $page_content);
    $themesPages['index'] = str_replace('{IMMO_FILE}', $immoHomeHeadlines, $themesPages['index']);
    $page_template = str_replace('{IMMO_FILE}', $immoHomeHeadlines, $page_template);
}


// get Newsletter
/** @ignore */
if ($cl->loadFile(ASCMS_MODULE_PATH.'/newsletter/index.class.php')) {
    $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('newsletter'));
    $newsletter = new newsletter('');
    if (preg_match('/{NEWSLETTER_BLOCK}/', $page_content)) {
        $newsletter->setBlock($page_content);
    }
    if (preg_match('/{NEWSLETTER_BLOCK}/', $page_template)) {
        $newsletter->setBlock($page_template);
    }
    if (preg_match('/{NEWSLETTER_BLOCK}/', $themesPages['index'])) {
        $newsletter->setBlock($themesPages['index']);
    }
}


// get knowledge content
if (MODULE_INDEX < 2 && !empty($_CONFIG['useKnowledgePlaceholders'])) {
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/knowledge/interface.class.php')) {

        $knowledgeInterface = new KnowledgeInterface();
        if (preg_match('/{KNOWLEDGE_[A-Za-z0-9_]+}/i', $page_content)) {
            $knowledgeInterface->parse($page_content);
        }
        if (preg_match('/{KNOWLEDGE_[A-Za-z0-9_]+}/i', $page_template)) {
            $knowledgeInterface->parse($page_template);
        }
        if (preg_match('/{KNOWLEDGE_[A-Za-z0-9_]+}/i', $themesPages['index'])) {
            $knowledgeInterface->parse($themesPages['index']);
        }
    }
}


// get Directory Homecontent
if ($_CONFIG['directoryHomeContent'] == '1') {
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/directory/homeContent.class.php')) {

        $dirc = $themesPages['directory_content'];
        if (preg_match('/{DIRECTORY_FILE}/', $page_content)) {
            $page_content = str_replace('{DIRECTORY_FILE}', dirHomeContent::getObj($dirc)->getContent(), $page_content);
        }
        if (preg_match('/{DIRECTORY_FILE}/', $page_template)) {
            $page_template = str_replace('{DIRECTORY_FILE}', dirHomeContent::getObj($dirc)->getContent(), $page_template);
        }
        if (preg_match('/{DIRECTORY_FILE}/', $themesPages['index'])) {
            $themesPages['index'] = str_replace('{DIRECTORY_FILE}', dirHomeContent::getObj($dirc)->getContent(), $themesPages['index']);
        }
    }
}


// get + replace forum latest entries content
if ($_CONFIG['forumHomeContent'] == '1') {
    /** @ignore */
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/forum/homeContent.class.php')) {
        $forumHomeContentInPageContent = false;
        $forumHomeContentInPageTemplate = false;
        $forumHomeContentInThemesPage = false;
        if (strpos($page_content, '{FORUM_FILE}') !== false) {
            $forumHomeContentInPageContent = true;
        }
        if (strpos($page_template, '{FORUM_FILE}') !== false) {
            $forumHomeContentInPageTemplate = true;
        }
        if (strpos($themesPages['index'], '{FORUM_FILE}') !== false) {
            $forumHomeContentInThemesPage = true;
        }
        $homeForumContent = '';
        if ($forumHomeContentInPageContent || $forumHomeContentInPageTemplate || $forumHomeContentInThemesPage) {
            $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('forum'));
            $objForum = new ForumHomeContent($themesPages['forum_content']);
            $homeForumContent = $objForum->getContent();
        }
        if ($forumHomeContentInPageContent) {
            $page_content = str_replace('{FORUM_FILE}', $homeForumContent, $page_content);
        }
        if ($forumHomeContentInPageTemplate) {
            $page_template = str_replace('{FORUM_FILE}', $homeForumContent, $page_template);
        }
        if ($forumHomeContentInThemesPage) {
            $themesPages['index'] = str_replace('{FORUM_FILE}', $homeForumContent, $themesPages['index']);
        }
    }
}


// get + replace forum tagcloud
if (!empty($_CONFIG['forumTagContent'])) {
    /** @ignore */
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/forum/homeContent.class.php')) {
        $objForumHome = new ForumHomeContent();
        //Forum-TagCloud
        $forumHomeTagCloudInContent = $objForumHome->searchKeywordInContent('FORUM_TAG_CLOUD', $page_content);
        $forumHomeTagCloudInTemplate = $objForumHome->searchKeywordInContent('FORUM_TAG_CLOUD', $page_template);
        $forumHomeTagCloudInTheme = $objForumHome->searchKeywordInContent('FORUM_TAG_CLOUD', $themesPages['index']);
        $forumHomeTagCloudInSidebar = $objForumHome->searchKeywordInContent('FORUM_TAG_CLOUD', $themesPages['sidebar']);
        if (   $forumHomeTagCloudInContent
            || $forumHomeTagCloudInTemplate
            || $forumHomeTagCloudInTheme
            || $forumHomeTagCloudInSidebar
        ) {
            $strTagCloudSource = $objForumHome->getHomeTagCloud();
            $page_content = $objForumHome->fillVariableIfActivated('FORUM_TAG_CLOUD', $strTagCloudSource, $page_content, $forumHomeTagCloudInContent);
            $page_template = $objForumHome->fillVariableIfActivated('FORUM_TAG_CLOUD', $strTagCloudSource, $page_template, $forumHomeTagCloudInTemplate);
            $themesPages['index'] = $objForumHome->fillVariableIfActivated('FORUM_TAG_CLOUD', $strTagCloudSource, $themesPages['index'], $forumHomeTagCloudInTheme);
            $themesPages['sidebar'] = $objForumHome->fillVariableIfActivated('FORUM_TAG_CLOUD', $strTagCloudSource, $themesPages['sidebar'], $forumHomeTagCloudInSidebar);
        }
    }
}


// Get Gallery-Images (Latest, Random)
/** @ignore */
if ($cl->loadFile(ASCMS_MODULE_PATH.'/gallery/homeContent.class.php')) {
    $objGalleryHome = new GalleryHomeContent();
    if ($objGalleryHome->checkRandom()) {
        if (preg_match('/{GALLERY_RANDOM}/', $page_content)) {
            $page_content = str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), $page_content);
        }
        if (preg_match('/{GALLERY_RANDOM}/', $page_template))  {
            $page_template = str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), $page_template);
        }
        if (preg_match('/{GALLERY_RANDOM}/', $themesPages['index'])) {
            $themesPages['index'] = str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), $themesPages['index']);
        }
        if (preg_match('/{GALLERY_RANDOM}/', $themesPages['sidebar'])) {
            $themesPages['sidebar'] = str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), $themesPages['sidebar']);
        }
    }
    if ($objGalleryHome->checkLatest()) {
        $latestImage = $objGalleryHome->getLastImage();
        if (preg_match('/{GALLERY_LATEST}/', $page_content)) {
            $page_content = str_replace('{GALLERY_LATEST}', $latestImage, $page_content);
        }
        if (preg_match('/{GALLERY_LATEST}/', $page_template)) {
            $page_template = str_replace('{GALLERY_LATEST}', $latestImage, $page_template);
        }
        if (preg_match('/{GALLERY_LATEST}/', $themesPages['index'])) {
            $themesPages['index'] = str_replace('{GALLERY_LATEST}', $latestImage, $themesPages['index']);
        }
        if (preg_match('/{GALLERY_LATEST}/', $themesPages['sidebar'])) {
            $themesPages['sidebar'] = str_replace('{GALLERY_LATEST}', $latestImage, $themesPages['sidebar']);
        }
    }
}


// get latest podcast entries
$podcastFirstBlock = false;
$podcastContent = null;
if (!empty($_CONFIG['podcastHomeContent'])) {
    /** @ignore */
    if ($cl->loadFile(ASCMS_MODULE_PATH.'/podcast/homeContent.class.php')) {
        $podcastHomeContentInPageContent = false;
        $podcastHomeContentInPageTemplate = false;
        $podcastHomeContentInThemesPage = false;
        if (strpos($page_content, '{PODCAST_FILE}') !== false) {
            $podcastHomeContentInPageContent = true;
        }
        if (strpos($page_template, '{PODCAST_FILE}') !== false) {
            $podcastHomeContentInPageTemplate = true;
        }
        if (strpos($themesPages['index'], '{PODCAST_FILE}') !== false) {
            $podcastHomeContentInThemesPage = true;
        }
        if (   $podcastHomeContentInPageContent
            || $podcastHomeContentInPageTemplate
            || $podcastHomeContentInThemesPage) {
            $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('podcast'));
            $objPodcast = new podcastHomeContent($themesPages['podcast_content']);
            $podcastContent = $objPodcast->getContent();
            if ($podcastHomeContentInPageContent) {
                $page_content = str_replace('{PODCAST_FILE}', $podcastContent, $page_content);
            }
            if ($podcastHomeContentInPageTemplate) {
                $page_template = str_replace('{PODCAST_FILE}', $podcastContent, $page_template);
            }
            if ($podcastHomeContentInThemesPage) {
                $podcastFirstBlock = false;
                if (strpos($_SERVER['REQUEST_URI'], 'section=podcast')){
                    $podcastBlockPos = strpos($themesPages['index'], '{PODCAST_FILE}');
                    $contentPos = strpos($themesPages['index'], '{CONTENT_FILE}');
                    $podcastFirstBlock = $podcastBlockPos < $contentPos ? true : false;
                }
                $themesPages['index'] = str_replace('{PODCAST_FILE}',
                    $objPodcast->getContent($podcastFirstBlock), $themesPages['index']);
            }
        }
    }
}


// get voting
/** @ignore */
if ($cl->loadFile(ASCMS_MODULE_PATH.'/voting/index.class.php')) {
    $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('voting'));
//  if ($objTemplate->blockExists('voting_result')) {
//      $objTemplate->_blocks['voting_result'] = setVotingResult($objTemplate->_blocks['voting_result']);
//  }
    if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', $themesPages['sidebar'], $arrMatches)) {
        $themesPages['sidebar'] = preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), $themesPages['sidebar']);
    }
    if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', $themesPages['index'], $arrMatches)) {
        $themesPages['index'] = preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), $themesPages['index']);
    }
    if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', $page_content, $arrMatches)) {
        $page_content = preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), $page_content);
    }
    if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', $page_template, $arrMatches)) {
        $page_template = preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), $page_template);
    }
}


// Get content for the blog-module.
/** @ignore */
if ($cl->loadFile(ASCMS_MODULE_PATH.'/blog/homeContent.class.php')) {
    $objBlogHome = new BlogHomeContent($themesPages['blog_content']);
    if ($objBlogHome->blockFunktionIsActivated()) {
        //Blog-File
        $blogHomeContentInContent = $objBlogHome->searchKeywordInContent('BLOG_FILE', $page_content);
        $blogHomeContentInTemplate = $objBlogHome->searchKeywordInContent('BLOG_FILE', $page_template);
        $blogHomeContentInTheme = $objBlogHome->searchKeywordInContent('BLOG_FILE', $themesPages['index']);
        $blogHomeContentInSidebar = $objBlogHome->searchKeywordInContent('BLOG_FILE', $themesPages['sidebar']);
        if ($blogHomeContentInContent || $blogHomeContentInTemplate || $blogHomeContentInTheme || $blogHomeContentInSidebar) {
            $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('blog'));
            $strContentSource = $objBlogHome->getLatestEntries();
            $page_content = $objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, $page_content, $blogHomeContentInContent);
            $page_template = $objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, $page_template, $blogHomeContentInTemplate);
            $themesPages['index'] = $objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, $themesPages['index'], $blogHomeContentInTheme);
            $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, $themesPages['sidebar'], $blogHomeContentInSidebar);
        }
        //Blog-Calendar
        $blogHomeCalendarInContent = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', $page_content);
        $blogHomeCalendarInTemplate = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', $page_template);
        $blogHomeCalendarInTheme = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', $themesPages['index']);
        $blogHomeCalendarInSidebar = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', $themesPages['sidebar']);
        if ($blogHomeCalendarInContent || $blogHomeCalendarInTemplate || $blogHomeCalendarInTheme || $blogHomeCalendarInSidebar) {
            $strCalendarSource = $objBlogHome->getHomeCalendar();
            $page_content = $objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, $page_content, $blogHomeCalendarInContent);
            $page_template = $objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, $page_template, $blogHomeCalendarInTemplate);
            $themesPages['index'] = $objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, $themesPages['index'], $blogHomeCalendarInTheme);
            $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, $themesPages['sidebar'], $blogHomeCalendarInSidebar);
        }
        //Blog-TagCloud
        $blogHomeTagCloudInContent = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', $page_content);
        $blogHomeTagCloudInTemplate = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', $page_template);
        $blogHomeTagCloudInTheme = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', $themesPages['index']);
        $blogHomeTagCloudInSidebar = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', $themesPages['sidebar']);
        if ($blogHomeTagCloudInContent || $blogHomeTagCloudInTemplate || $blogHomeTagCloudInTheme || $blogHomeTagCloudInSidebar) {
            $strTagCloudSource = $objBlogHome->getHomeTagCloud();
            $page_content = $objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, $page_content, $blogHomeTagCloudInContent);
            $page_template = $objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, $page_template, $blogHomeTagCloudInTemplate);
            $themesPages['index'] = $objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, $themesPages['index'], $blogHomeTagCloudInTheme);
            $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, $themesPages['sidebar'], $blogHomeTagCloudInSidebar);
        }
        //Blog-TagHitlist
        $blogHomeTagHitlistInContent = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', $page_content);
        $blogHomeTagHitlistInTemplate = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', $page_template);
        $blogHomeTagHitlistInTheme = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', $themesPages['index']);
        $blogHomeTagHitlistInSidebar = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', $themesPages['sidebar']);
        if ($blogHomeTagHitlistInContent || $blogHomeTagHitlistInTemplate || $blogHomeTagHitlistInTheme || $blogHomeTagHitlistInSidebar) {
            $strTagHitlistSource = $objBlogHome->getHomeTagHitlist();
            $page_content = $objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, $page_content, $blogHomeTagHitlistInContent);
            $page_template = $objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, $page_template, $blogHomeTagHitlistInTemplate);
            $themesPages['index'] = $objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, $themesPages['index'], $blogHomeTagHitlistInTheme);
            $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, $themesPages['sidebar'], $blogHomeTagHitlistInSidebar);
        }
        //Blog-Categories (Select)
        $blogHomeCategorySelectInContent = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $page_content);
        $blogHomeCategorySelectInTemplate = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $page_template);
        $blogHomeCategorySelectInTheme = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $themesPages['index']);
        $blogHomeCategorySelectInSidebar = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $themesPages['sidebar']);
        if ($blogHomeCategorySelectInContent || $blogHomeCategorySelectInTemplate || $blogHomeCategorySelectInTheme || $blogHomeCategorySelectInSidebar) {
            $strCategoriesSelect = $objBlogHome->getHomeCategoriesSelect();
            $page_content = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, $page_content, $blogHomeCategorySelectInContent);
            $page_template = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, $page_template, $blogHomeCategorySelectInTemplate);
            $themesPages['index'] = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, $themesPages['index'], $blogHomeCategorySelectInTheme);
            $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, $themesPages['sidebar'], $blogHomeCategorySelectInSidebar);
        }
        //Blog-Categories (List)
        $blogHomeCategoryListInContent = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', $page_content);
        $blogHomeCategoryListInTemplate = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', $page_template);
        $blogHomeCategoryListInTheme = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', $themesPages['index']);
        $blogHomeCategoryListInSidebar = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', $themesPages['sidebar']);
        if ($blogHomeCategoryListInContent || $blogHomeCategoryListInTemplate || $blogHomeCategoryListInTheme || $blogHomeCategoryListInSidebar) {
            $strCategoriesList = $objBlogHome->getHomeCategoriesList();
            $page_content = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, $page_content, $blogHomeCategoryListInContent);
            $page_template = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, $page_template, $blogHomeCategoryListInTemplate);
            $themesPages['index'] = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, $themesPages['index'], $blogHomeCategoryListInTheme);
            $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, $themesPages['sidebar'], $blogHomeCategoryListInSidebar);
        }
    }
}

// Media directory: set placeholders I
/** @ignore */
if ($cl->loadFile(ASCMS_MODULE_PATH.'/mediadir/placeholders.class.php')) {
    $objMadiadirPlaceholders = new mediaDirectoryPlaceholders();
    // Level/Category Navbar
    if (preg_match('/{MEDIADIR_NAVBAR}/', $page_content)) {
        $page_content = str_replace('{MEDIADIR_NAVBAR}', $objMadiadirPlaceholders->getNavigationPlacholder(), $page_content);
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
    if (preg_match('/{MEDIADIR_LATEST}/', $page_content)) {
        $page_content = str_replace('{MEDIADIR_LATEST}', $objMadiadirPlaceholders->getLatestPlacholder(), $page_content);
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
}
////////////////////////////////////////////////////////////////////////////////
//// END: GLOBAL CONTENT MODULE FUNCTIONS //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

$objTemplate->setTemplate($themesPages['index']);
$objTemplate->addBlock('CONTENT_FILE', 'page_template', $page_template);
$objNavbar->setLanguagePlaceholders($page, $url, $objTemplate);

// Set global content variables.
$page_content = str_replace('{PAGE_URL}',        htmlspecialchars($objInit->getPageUri()), $page_content);
$page_content = str_replace('{STANDARD_URL}',    $objInit->getUriBy('smallscreen', 0),     $page_content);
$page_content = str_replace('{MOBILE_URL}',      $objInit->getUriBy('smallscreen', 1),     $page_content);
$page_content = str_replace('{PRINT_URL}',       $objInit->getUriBy('printview', 1),       $page_content);
$page_content = str_replace('{PDF_URL}',         $objInit->getUriBy('pdfview', 1),         $page_content);
$page_content = str_replace('{APP_URL}',         $objInit->getUriBy('appview', 1),         $page_content);
$page_content = str_replace('{LOGOUT_URL}',      $objInit->getUriBy('section', 'logout'),  $page_content);
$page_content = str_replace('{TITLE}',           $page_title, $page_content);
$page_content = str_replace('{CONTACT_EMAIL}',   isset($_CONFIG['contactFormEmail']) ? contrexx_raw2xhtml($_CONFIG['contactFormEmail']) : '', $page_content);
$page_content = str_replace('{CONTACT_COMPANY}', isset($_CONFIG['contactCompany'])   ? contrexx_raw2xhtml($_CONFIG['contactCompany'])   : '', $page_content);
$page_content = str_replace('{CONTACT_ADDRESS}', isset($_CONFIG['contactAddress'])   ? contrexx_raw2xhtml($_CONFIG['contactAddress'])   : '', $page_content);
$page_content = str_replace('{CONTACT_ZIP}',     isset($_CONFIG['contactZip'])       ? contrexx_raw2xhtml($_CONFIG['contactZip'])       : '', $page_content);
$page_content = str_replace('{CONTACT_PLACE}',   isset($_CONFIG['contactPlace'])     ? contrexx_raw2xhtml($_CONFIG['contactPlace'])     : '', $page_content);
$page_content = str_replace('{CONTACT_COUNTRY}', isset($_CONFIG['contactCountry'])   ? contrexx_raw2xhtml($_CONFIG['contactCountry'])   : '', $page_content);
$page_content = str_replace('{CONTACT_PHONE}',   isset($_CONFIG['contactPhone'])     ? contrexx_raw2xhtml($_CONFIG['contactPhone'])     : '', $page_content);
$page_content = str_replace('{CONTACT_FAX}',     isset($_CONFIG['contactFax'])       ? contrexx_raw2xhtml($_CONFIG['contactFax'])       : '', $page_content);

// ACCESS: parse access_logged_in[1-9] and access_logged_out[1-9] blocks
FWUser::parseLoggedInOutBlocks($page_content);

//replace the {NODE_<ID>_<LANG>}- placeholders
LinkGenerator::parseTemplate($page_content);

$boolShop = false;
$moduleStyleFile = null;
// start module switches
switch ($plainSection) {
    case 'access':
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/access/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objAccess = new Access($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objAccess->getPage($page_metatitle, $page_title));
        break;

    case 'login':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/login/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        if (!isset($sessionObj) || !is_object($sessionObj)) $sessionObj = new cmsSession();
        $objLogin = new Login($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objLogin->getContent());
        break;

    case 'nettools':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/nettools/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objNetTools = new NetTools($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objNetTools->getPage());
        break;

    case 'shop':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/shop/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objTemplate->setVariable('CONTENT_TEXT', Shop::getPage($page_content));
        $boolShop = true;
        break;

    case 'news':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/news/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $newsObj= new news($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $newsObj->getNewsPage());
        $newsObj->getPageTitle($page_title);
        // Set the meta page description to the teaser text if displaying news details
        $teaser = $newsObj->getTeaser();
        if ($teaser !== null) //news details, else getTeaser would return null
            $page_desc = $teaser;
        $page_title = $newsObj->newsTitle;
        $page_metatitle = $page_title;
        break;

    case 'livecam':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/livecam/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objLivecam = new Livecam($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objLivecam->getPage());
        break;

    case 'guestbook':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/guestbook/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objGuestbook = new Guestbook($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objGuestbook->getPage());
        break;

    case 'memberdir':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/memberdir/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objMemberDir = new memberDir($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objMemberDir->getPage());
        break;

    case 'data':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/data/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        //if (!isset($sessionObj) || !is_object($sessionObj)) $sessionObj = new cmsSession();
        #if (!isset($objAuth) || !is_object($objAuth)) $objAuth = &new Auth($type = 'frontend');

        $objData = new Data($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objData->getPage());
        break;

    case 'download':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/download/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objDownload = new Download($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objDownload->getPage());
        break;

    case 'recommend':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/recommend/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objRecommend = new Recommend($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objRecommend->getPage());
        break;

    case 'ecard':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/ecard/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objEcard = new Ecard($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objEcard->getPage());
        break;

    case 'tools':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/tools/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objTools = new Tools($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objTools->getPage());
        break;

    case 'dataviewer':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/dataviewer/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objDataviewer = new Dataviewer($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objDataviewer->getPage());
        break;

    case 'docsys':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/docsys/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $docSysObj= new docSys($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $docSysObj->getDocSysPage());
        $docSysObj->getPageTitle($page_title);
        $page_title = $docSysObj->docSysTitle;
        $page_metatitle = $docSysObj->docSysTitle;
        break;

    case 'search':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/search/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $pos = (isset($_GET['pos'])) ? intval($_GET['pos']) : '';
        $objTemplate->setVariable('CONTENT_TEXT', search_getSearchPage($pos, $page_content, $license));
        unset($pos);
        break;

    case 'contact':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/contact/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $contactObj = new Contact($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $contactObj->getContactPage());
        $moduleStyleFile = ASCMS_CORE_MODULE_WEB_PATH.'/contact/frontend_style.css';
        break;

    case 'ids':
        $objTemplate->setVariable('CONTENT_TEXT', $page_content);
        break;

    case 'sitemap':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/sitemap/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $sitemap = new sitemap($page_content, $license);
        $objTemplate->setVariable('CONTENT_TEXT', $sitemap->getSitemapContent());
        break;

    case 'media':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_MODULE_PATH.'/media/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objMedia = new MediaManager($page_content, $plainSection.MODULE_INDEX);
        $objTemplate->setVariable('CONTENT_TEXT', $objMedia->getMediaPage());
        break;

    case 'newsletter':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/newsletter/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $newsletter = new newsletter($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $newsletter->getPage());
        break;

    case 'gallery':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/gallery/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objGallery = new Gallery($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objGallery->getPage());

        $topGalleryName = $objGallery->getTopGalleryName();
        if ($topGalleryName) {
            $page_title = $topGalleryName;
            $page_metatitle = $topGalleryName;
        }
        break;

    case 'voting':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/voting/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objTemplate->setVariable('CONTENT_TEXT', votingShowCurrent($page_content));
        break;

    case 'feed':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/feed/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objFeed = new feed($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objFeed->getFeedPage());
        break;

    case 'immo':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/immo/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objImmo = new Immo($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objImmo->getPage());
        if (!empty($_GET['cmd']) && $_GET['cmd'] == 'showObj') {
            $page_title = $objImmo->getPageTitle($page_title);
            $page_metatitle = $page_title;
        }
        break;

    case 'calendar':
        define('CALENDAR_MANDATE', MODULE_INDEX);
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/calendar'.MODULE_INDEX.'/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objCalendar = new Calendar($page_content, MODULE_INDEX);
        $objTemplate->setVariable('CONTENT_TEXT', $objCalendar->getCalendarPage());
        if ($objCalendar->pageTitle) {
            $page_metatitle = $objCalendar->pageTitle;
            $page_title = $objCalendar->pageTitle;
        }
        break;

    case 'reservation':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/reservation/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
            $objReservationModule = new reservations($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objReservationModule->getPage());
        $moduleStyleFile = ASCMS_MODULE_WEB_PATH.'/reservation/frontend_style.css';
        break;

    case 'directory':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/directory/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $directory = new rssDirectory($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $directory->getPage());
        $directory_pagetitle = $directory->getPageTitle();
        if (!empty($directory_pagetitle)) {
            $page_metatitle = $directory_pagetitle;
            $page_title = $directory_pagetitle;
        }
        if ($_GET['cmd'] == 'detail' && isset($_GET['id'])) {
            $objTemplate->setVariable(array(
                'DIRECTORY_ENTRY_ID' => intval($_GET['id']),
            ));
        }
        break;

    case 'market':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/market/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $market = new Market($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $market->getPage());
        break;

    case 'podcast':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/podcast/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objPodcast = new podcast($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objPodcast->getPage($podcastFirstBlock));
        break;

    case 'forum':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/forum/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objForum = new Forum($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objForum->getPage());
//        $moduleStyleFile = 'modules/forum/css/frontend_style.css';
        break;

    case 'blog':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/blog/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objBlog = new Blog($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objBlog->getPage());
        break;

    case 'knowledge':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/knowledge/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objKnowledge = new Knowledge($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objKnowledge->getPage());
        if (!empty($objKnowledge->pageTitle)) {
            $page_title = $objKnowledge->pageTitle;
            $page_metatitle = $objKnowledge->pageTitle;
        }
        break;

    case 'jobs':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/jobs/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $jobsObj= new jobs($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $jobsObj->getJobsPage());
        $jobsObj->getPageTitle($page_title);
        $page_title = $jobsObj->jobsTitle;
        $page_metatitle = $jobsObj->jobsTitle;
        break;

    case 'error':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_CORE_PATH.'/error.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $errorObj = new error($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $errorObj->getErrorPage());
        break;

    case 'egov':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/egov/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objEgov = new eGov($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objEgov->getPage());
        break;

    case 'support':
        /**
         * Support System Module
         * @author  Reto Kohli <reto.kohli@comvation.com>
         * @since   1.2.0
         * @version 0.0.1 alpha
         */
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/support/index.class.php'))
            die ($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objSupport = new support($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objSupport->getPage());
        break;

    case 'partners':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/partners/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objPartners = new PartnersFrontend($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objPartners->getPage());
        break;

    case 'u2u':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/u2u/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objU2u = new u2u($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objU2u->getPage($page_metatitle, $page_title));
        break;

    case 'auction':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/auction/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $auction = new Auction($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $auction->getPage());
        break;

    case 'downloads':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/downloads/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objDownloadsModule = new downloads($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objDownloadsModule->getPage());
        $downloads_pagetitle = $objDownloadsModule->getPageTitle();
        if ($downloads_pagetitle) {
            $page_metatitle = $downloads_pagetitle;
            $page_title = $downloads_pagetitle;
        }
        break;

    case 'printshop':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/printshop/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objPrintshopModule = new Printshop($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objPrintshopModule->getPage());
        $page_metatitle .= ' '.$objPrintshopModule->getPageTitle();
        $page_title = '';
        break;


    case 'mediadir':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/mediadir/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objMediaDirectory = new mediaDirectory($page_content);
        $objMediaDirectory->pageTitle = $page_title;
        $objMediaDirectory->metaTitle = $page_metatitle;
        $objTemplate->setVariable('CONTENT_TEXT', $objMediaDirectory->getPage());
        if ($objMediaDirectory->getPageTitle() != '') {
            $page_title = $objMediaDirectory->getPageTitle();
        }
        if ($objMediaDirectory->getMetaTitle() != '') {
            $page_metatitle = $objMediaDirectory->getMetaTitle();
        }
        break;

    case 'checkout':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/checkout/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objCheckout = new Checkout($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objCheckout->getPage());
        break;
    case 'filesharing':
        /** @ignore */
        if (!$cl->loadFile(ASCMS_MODULE_PATH.'/filesharing/index.class.php'))
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objFileshare = new Filesharing($page_content);
        $objTemplate->setVariable('CONTENT_TEXT', $objFileshare->getPage());
        break;

    default:
        $objTemplate->setVariable('CONTENT_TEXT', $page_content);
}

// Show the Shop navbar in the Shop, or on every page if configured to do so
if (!$boolShop
// Optionally limit to the first instance
// && MODULE_INDEX == ''
) {
    SettingDb::init('shop', 'config');
    if (SettingDb::getValue('shopnavbar_on_all_pages')) {
        Shop::init();
        Shop::setNavbar();
        $boolShop = true;
    }
}

// Calendar
// print_r($objTemplate->getPlaceholderList());
if (MODULE_INDEX < 2) {
    $calendarCheck1 = $objTemplate->placeholderExists('CALENDAR');
    $calendarCheck2 = $objTemplate->placeholderExists('CALENDAR_EVENTS');
    if (   ($calendarCheck1 || $calendarCheck2)
        /** @ignore */
        && $cl->loadFile(ASCMS_MODULE_PATH.'/calendar/HomeCalendar.class.php')) {
        $objHomeCalendar = new HomeCalendar();
        if (!empty($calendarCheck1)) {
            $objTemplate->setVariable('CALENDAR', $objHomeCalendar->getHomeCalendar());
        }
        if (!empty($calendarCheck2)) {
            $objTemplate->setVariable('CALENDAR_EVENTS', $objHomeCalendar->getHomeCalendarEvents());
        }
    }
}


// Directory Show Latest
//$directoryCheck = $objTemplate->blockExists('directoryLatest_row_1');
$directoryCheck = array();
for ($i = 1; $i <= 10; $i++) {
    if ($objTemplate->blockExists('directoryLatest_row_'.$i)) {
        array_push($directoryCheck, $i);
    }
}
if (   !empty($directoryCheck)
    /** @ignore */
    && $cl->loadFile(ASCMS_MODULE_PATH.'/directory/index.class.php')) {
    $objDirectory = new rssDirectory('');
    if (!empty($directoryCheck)) {
        $objTemplate->setVariable('TXT_DIRECTORY_LATEST', $_CORELANG['TXT_DIRECTORY_LATEST']);
           $objDirectory->getBlockLatest($directoryCheck);
    }
}


// Market Show Latest
$marketCheck = $objTemplate->blockExists('marketLatest');
if (   $marketCheck
    /** @ignore */
    && $cl->loadFile(ASCMS_MODULE_PATH.'/market/index.class.php')) {
    $objMarket = new Market('');
    $objTemplate->setVariable('TXT_MARKET_LATEST', $_CORELANG['TXT_MARKET_LATEST']);
    $objMarket->getBlockLatest();
}


// Set banner variables
$objBanner = null;
if (   $_CONFIG['bannerStatus']
       /** @ignore */
    && $cl->loadFile(ASCMS_CORE_MODULE_PATH.'/banner/index.class.php')) {
    $objBanner = new Banner();
    $objTemplate->setVariable(array(
        'BANNER_GROUP_1' => $objBanner->getBannerCode(1, $page->getNode()->getId()),
        'BANNER_GROUP_2' => $objBanner->getBannerCode(2, $page->getNode()->getId()),
        'BANNER_GROUP_3' => $objBanner->getBannerCode(3, $page->getNode()->getId()),
        'BANNER_GROUP_4' => $objBanner->getBannerCode(4, $page->getNode()->getId()),
        'BANNER_GROUP_5' => $objBanner->getBannerCode(5, $page->getNode()->getId()),
        'BANNER_GROUP_6' => $objBanner->getBannerCode(6, $page->getNode()->getId()),
        'BANNER_GROUP_7' => $objBanner->getBannerCode(7, $page->getNode()->getId()),
        'BANNER_GROUP_8' => $objBanner->getBannerCode(8, $page->getNode()->getId()),
        'BANNER_GROUP_9' => $objBanner->getBannerCode(9, $page->getNode()->getId()),
        'BANNER_GROUP_10' => $objBanner->getBannerCode(10, $page->getNode()->getId()),
    ));
    if (isset($_REQUEST['bannerId'])) {
        $objBanner->updateClicks(intval($_REQUEST['bannerId']));
    }
}

// Media directory: Set placeholders II (latest / headline)
$mediadirCheck = array();
for ($i = 1; $i <= 10; ++$i) {
    if ($objTemplate->blockExists('mediadirLatest_row_'.$i)){
        array_push($mediadirCheck, $i);
    }
}
if (   $mediadirCheck
    /** @ignore */
    && $cl->loadFile(ASCMS_MODULE_PATH.'/mediadir/index.class.php')) {
    $objMediadir = new mediaDirectory('');
    $objTemplate->setVariable('TXT_MEDIADIR_LATEST', $_CORELANG['TXT_DIRECTORY_LATEST']);
    $objMediadir->getHeadlines($mediadirCheck);
}


// Frontend Editing: prepare needed code-fragments
$strFeInclude = $strFeLink = $strFeContent = null;
if ($_CONFIG['frontendEditingStatus'] == 'on') {
    $strFeInclude   = frontendEditingLib::getIncludeCode();
    $strFeLink      = frontendEditingLib::getLinkCode($pageId);
    $strFeContent   = frontendEditingLib::getContentCode($pageId);
}


// set global template variables
$objTemplate->setVariable(array(
    'CHARSET'                        => $objInit->getFrontendLangCharset(),
    'TITLE'                          => $page_title,
    'METATITLE'                      => $page_metatitle,
    'NAVTITLE'                       => $page_catname,
    'GLOBAL_TITLE'                   => $_CONFIG['coreGlobalPageTitle'],
    'DOMAIN_URL'                     => $_CONFIG['domainUrl'],
    'PATH_OFFSET'                    => ASCMS_PATH_OFFSET,
    'BASE_URL'                       => ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET,
    'METAKEYS'                       => $page_keywords,
    'METADESC'                       => $page_desc,
    'METAROBOTS'                     => $page_robots,
    'CONTENT_TITLE'                  => '<span id="fe_PreviewTitle">'.$page_title.'</span>',
    'CSS_NAME'                       => $pageCssName,
    'STANDARD_URL'                   => $objInit->getUriBy('smallscreen', 0),
    'MOBILE_URL'                     => $objInit->getUriBy('smallscreen', 1),
    'PRINT_URL'                      => $objInit->getUriBy('printview', 1),
    'PDF_URL'                        => $objInit->getUriBy('pdfview', 1),
    'APP_URL'                        => $objInit->getUriBy('appview', 1),
    'LOGOUT_URL'                     => $objInit->getUriBy('section', 'logout'),
    'PAGE_URL'                       => htmlspecialchars($objInit->getPageUri()),
    'CURRENT_URL'                    => $objInit->getCurrentPageUri(),
    'DATE'                           => showFormattedDate(),
    'TIME'                           => date('H:i', time()),
    'NAVTREE'                        => $objNavbar->getTrail(),
    'SUBNAVBAR_FILE'                 => $objNavbar->getSubnavigation($themesPages['subnavbar'], $license,$boolShop),
    'SUBNAVBAR2_FILE'                => $objNavbar->getSubnavigation($themesPages['subnavbar2'], $license,$boolShop),
    'SUBNAVBAR3_FILE'                => $objNavbar->getSubnavigation($themesPages['subnavbar3'], $license,$boolShop),
    'NAVBAR_FILE'                    => $objNavbar->getNavigation($themesPages['navbar'], $license, $boolShop),
    'NAVBAR2_FILE'                   => $objNavbar->getNavigation($themesPages['navbar2'], $license, $boolShop),
    'NAVBAR3_FILE'                   => $objNavbar->getNavigation($themesPages['navbar3'], $license, $boolShop),
    'ONLINE_USERS'                   => $objCounter->getOnlineUsers(),
    'VISITOR_NUMBER'                 => $objCounter->getVisitorNumber(),
    'COUNTER'                        => $objCounter->getCounterTag(),
    'BANNER'                         => isset($objBanner) ? $objBanner->getBannerJS() : '',
    'VERSION'                        => contrexx_raw2xhtml($_CONFIG['coreCmsName']),
    'LANGUAGE_NAVBAR'                => $objNavbar->getFrontendLangNavigation($page, $url),
    'LANGUAGE_NAVBAR_SHORT'          => $objNavbar->getFrontendLangNavigation($page, $url, true),
    'ACTIVE_LANGUAGE_NAME'           => $objInit->getFrontendLangName(),
    'RANDOM'                         => md5(microtime()),
    'TXT_SEARCH'                     => $_CORELANG['TXT_SEARCH'],
    'MODULE_INDEX'                   => MODULE_INDEX,
    'LOGIN_INCLUDE'                  => isset($strFeInclude) ? $strFeInclude : '',
    'LOGIN_URL'                      => isset($strFeLink) ? $strFeLink : '',
    'LOGIN_CONTENT'                  => isset($strFeContent) ? $strFeContent : '',
    'JAVASCRIPT'                     => 'javascript_inserting_here',
    'TXT_CORE_LAST_MODIFIED_PAGE'    => $_CORELANG['TXT_CORE_LAST_MODIFIED_PAGE'],
    'LAST_MODIFIED_PAGE'             => date(ASCMS_DATE_FORMAT_DATE, $page_modified),
    'CONTACT_EMAIL'                  => isset($_CONFIG['contactFormEmail']) ? contrexx_raw2xhtml($_CONFIG['contactFormEmail']) : '',
    'CONTACT_COMPANY'                => isset($_CONFIG['contactCompany'])   ? contrexx_raw2xhtml($_CONFIG['contactCompany'])   : '',
    'CONTACT_ADDRESS'                => isset($_CONFIG['contactAddress'])   ? contrexx_raw2xhtml($_CONFIG['contactAddress'])   : '',
    'CONTACT_ZIP'                    => isset($_CONFIG['contactZip'])       ? contrexx_raw2xhtml($_CONFIG['contactZip'])       : '',
    'CONTACT_PLACE'                  => isset($_CONFIG['contactPlace'])     ? contrexx_raw2xhtml($_CONFIG['contactPlace'])     : '',
    'CONTACT_COUNTRY'                => isset($_CONFIG['contactCountry'])   ? contrexx_raw2xhtml($_CONFIG['contactCountry'])   : '',
    'CONTACT_PHONE'                  => isset($_CONFIG['contactPhone'])     ? contrexx_raw2xhtml($_CONFIG['contactPhone'])     : '',
    'CONTACT_FAX'                    => isset($_CONFIG['contactFax'])       ? contrexx_raw2xhtml($_CONFIG['contactFax'])       : '',
    'FACEBOOK_LIKE_IFRAME'           => '<div id="fb-root"></div>
                                         <script type="text/javascript">
                                             (function(d, s, id) {
                                                 var js, fjs = d.getElementsByTagName(s)[0];
                                                 if (d.getElementById(id)) return;
                                                 js = d.createElement(s); js.id = id;
                                                 js.src = "//connect.facebook.net/de_DE/all.js#xfbml=1";
                                                 fjs.parentNode.insertBefore(js, fjs);
                                             }(document, \'script\', \'facebook-jssdk\'));
                                         </script>
                                         <div class="fb-like" data-href="http://'.$_CONFIG['domainUrl'].$objInit->getCurrentPageUri().'" data-send="false" data-layout="button_count" data-show-faces="false" data-font="segoe ui"></div>',
    'GOOGLE_PLUSONE'                 => '<div class="g-plusone" data-href="http://'.$_CONFIG['domainUrl'].$objInit->getCurrentPageUri().'"></div>
                                         <script type="text/javascript">
                                             window.___gcfg = {lang: \'de\'};

                                             (function() {
                                                 var po = document.createElement(\'script\'); po.type = \'text/javascript\'; po.async = true;
                                                 po.src = \'https://apis.google.com/js/plusone.js\';
                                                 var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(po, s);
                                             })();
                                         </script>',
    'GOOGLE_ANALYTICS'               => '<script type="text/javascript">
                                             var _gaq = _gaq || [];
                                             _gaq.push([\'_setAccount\', \''.(isset($_CONFIG['googleAnalyticsTrackingId']) ? contrexx_raw2xhtml($_CONFIG['googleAnalyticsTrackingId']) : '').'\']);
                                             _gaq.push([\'_trackPageview\']);

                                             (function() {
                                                 var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;
                                                 ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';
                                                 var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);
                                             })();
                                         </script>',
));

// ACCESS: parse access_logged_in[1-9] and access_logged_out[1-9] blocks
FWUser::parseLoggedInOutBlocks($objTemplate);

// currently online users
$objAccessBlocks = false;
if ($objTemplate->blockExists('access_currently_online_member_list')) {
    if (    FWUser::showCurrentlyOnlineUsers()
        && (    $objTemplate->blockExists('access_currently_online_female_members')
            ||  $objTemplate->blockExists('access_currently_online_male_members')
            ||  $objTemplate->blockExists('access_currently_online_members'))) {
        if ($cl->loadFile(ASCMS_CORE_MODULE_PATH.'/access/lib/blocks.class.php'))
            $objAccessBlocks = new Access_Blocks();
        if ($objTemplate->blockExists('access_currently_online_female_members'))
            $objAccessBlocks->setCurrentlyOnlineUsers('female');
        if ($objTemplate->blockExists('access_currently_online_male_members'))
            $objAccessBlocks->setCurrentlyOnlineUsers('male');
        if ($objTemplate->blockExists('access_currently_online_members'))
            $objAccessBlocks->setCurrentlyOnlineUsers();
    } else {
        $objTemplate->hideBlock('access_currently_online_member_list');
    }
}

// last active users
if ($objTemplate->blockExists('access_last_active_member_list')) {
    if (    FWUser::showLastActivUsers()
        && (    $objTemplate->blockExists('access_last_active_female_members')
            ||  $objTemplate->blockExists('access_last_active_male_members')
            ||  $objTemplate->blockExists('access_last_active_members'))) {
        if (   !$objAccessBlocks
            && $cl->loadFile(ASCMS_CORE_MODULE_PATH.'/access/lib/blocks.class.php'))
            $objAccessBlocks = new Access_Blocks();
        if ($objTemplate->blockExists('access_last_active_female_members'))
            $objAccessBlocks->setLastActiveUsers('female');
        if ($objTemplate->blockExists('access_last_active_male_members'))
            $objAccessBlocks->setLastActiveUsers('male');
        if ($objTemplate->blockExists('access_last_active_members'))
            $objAccessBlocks->setLastActiveUsers();
    } else {
        $objTemplate->hideBlock('access_last_active_member_list');
    }
}

// latest registered users
if ($objTemplate->blockExists('access_latest_registered_member_list')) {
    if (    FWUser::showLatestRegisteredUsers()
        && (    $objTemplate->blockExists('access_latest_registered_female_members')
            ||  $objTemplate->blockExists('access_latest_registered_male_members')
            ||  $objTemplate->blockExists('access_latest_registered_members'))) {
        if (   !$objAccessBlocks
            && $cl->loadFile(ASCMS_CORE_MODULE_PATH.'/access/lib/blocks.class.php'))
            $objAccessBlocks = new Access_Blocks();
        if ($objTemplate->blockExists('access_latest_registered_female_members'))
            $objAccessBlocks->setLatestRegisteredUsers('female');
        if ($objTemplate->blockExists('access_latest_registered_male_members'))
            $objAccessBlocks->setLatestRegisteredUsers('male');
        if ($objTemplate->blockExists('access_latest_registered_members'))
            $objAccessBlocks->setLatestRegisteredUsers();
    } else {
        $objTemplate->hideBlock('access_latest_registered_member_list');
    }
}

// birthday users
if ($objTemplate->blockExists('access_birthday_member_list')) {
    if (    FWUser::showBirthdayUsers()
        && (    $objTemplate->blockExists('access_birthday_female_members')
            ||  $objTemplate->blockExists('access_birthday_male_members')
            ||  $objTemplate->blockExists('access_birthday_members'))) {
        if (   !$objAccessBlocks
            && $cl->loadFile(ASCMS_CORE_MODULE_PATH.'/access/lib/blocks.class.php'))
            $objAccessBlocks = new Access_Blocks();
        if ($objAccessBlocks->isSomeonesBirthdayToday()) {
            if ($objTemplate->blockExists('access_birthday_female_members'))
                $objAccessBlocks->setBirthdayUsers('female');
            if ($objTemplate->blockExists('access_birthday_male_members'))
                $objAccessBlocks->setBirthdayUsers('male');
            if ($objTemplate->blockExists('access_birthday_members'))
                $objAccessBlocks->setBirthdayUsers();
            $objTemplate->touchBlock('access_birthday_member_list');
        } else {
            $objTemplate->hideBlock('access_birthday_member_list');
        }
    } else {
        $objTemplate->hideBlock('access_birthday_member_list');
    }
}


// parse system
$parsingtime = explode(' ', microtime());
$time = round(((float)$parsingtime[0] + (float)$parsingtime[1]) - ((float)$starttime[0] + (float)$starttime[1]), 5);
$objTemplate->setVariable('PARSING_TIME', $time);

$themesPages['sidebar'] = str_replace('{STANDARD_URL}',    $objInit->getUriBy('smallscreen', 0),    $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{MOBILE_URL}',      $objInit->getUriBy('smallscreen', 1),    $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{PRINT_URL}',       $objInit->getUriBy('printview', 1),      $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{PDF_URL}',         $objInit->getUriBy('pdfview', 1),        $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{APP_URL}',         $objInit->getUriBy('appview', 1),        $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{LOGOUT_URL}',      $objInit->getUriBy('section', 'logout'), $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_EMAIL}',   isset($_CONFIG['contactFormEmail']) ? contrexx_raw2xhtml($_CONFIG['contactFormEmail']) : '', $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_COMPANY}', isset($_CONFIG['contactCompany'])   ? contrexx_raw2xhtml($_CONFIG['contactCompany'])   : '', $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_ADDRESS}', isset($_CONFIG['contactAddress'])   ? contrexx_raw2xhtml($_CONFIG['contactAddress'])   : '', $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_ZIP}',     isset($_CONFIG['contactZip'])       ? contrexx_raw2xhtml($_CONFIG['contactZip'])       : '', $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_PLACE}',   isset($_CONFIG['contactPlace'])     ? contrexx_raw2xhtml($_CONFIG['contactPlace'])     : '', $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_COUNTRY}', isset($_CONFIG['contactCountry'])   ? contrexx_raw2xhtml($_CONFIG['contactCountry'])   : '', $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_PHONE}',   isset($_CONFIG['contactPhone'])     ? contrexx_raw2xhtml($_CONFIG['contactPhone'])     : '', $themesPages['sidebar']);
$themesPages['sidebar'] = str_replace('{CONTACT_FAX}',     isset($_CONFIG['contactFax'])       ? contrexx_raw2xhtml($_CONFIG['contactFax'])       : '', $themesPages['sidebar']);

$objTemplate->setVariable(array(
    'SIDEBAR_FILE' => $themesPages['sidebar'],
    'JAVASCRIPT_FILE' => $themesPages['javascript'],
    'BUILDIN_STYLE_FILE' => $themesPages['buildin_style'],
    'DATE_YEAR' => date('Y'),
    'DATE_MONTH' => date('m'),
    'DATE_DAY' => date('d'),
    'DATE_TIME' => date('H:i'),
    'BUILDIN_STYLE_FILE' => $themesPages['buildin_style'],
    'JAVASCRIPT_LIGHTBOX' =>
        '<script type="text/javascript" src="lib/lightbox/javascript/mootools.js"></script>
        <script type="text/javascript" src="lib/lightbox/javascript/slimbox.js"></script>',
    'JAVASCRIPT_MOBILE_DETECTOR' =>
        '<script type="text/javascript" src="lib/mobiledetector.js"></script>',
));

if (!empty($moduleStyleFile))
    $objTemplate->setVariable(
        'STYLE_FILE',
        "<link rel=\"stylesheet\" href=\"$moduleStyleFile\" type=\"text/css\" media=\"screen, projection\" />"
    );

if (isset($_GET['pdfview']) && intval($_GET['pdfview']) == 1) {
    $cl->loadFile(ASCMS_CORE_PATH.'/pdf.class.php');
    $objPDF          = new PDF();
    $objPDF->title   = $page_title.(empty($page_title) ? null : '.pdf');
    $objPDF->content = $objTemplate->get();
    $objPDF->Create();
    exit;
}

//enable gzip compressing of the output - up to 75% smaller responses!
//commented out because of certain php.inis generating a
//WARNING: ob_start(): output handler 'ob_gzhandler' cannot be used after 'URL-Rewriter
//ob_start("ob_gzhandler");

// fetch the parsed webpage
$endcode = $objTemplate->get();

/**
 * Get all javascripts in the code, replace them with nothing, and register the js file
 * to the javascript lib. This is because we don't want something twice, and there could be
 * a theme that requires a javascript, which then could be used by a module too and therefore would
 * be loaded twice.
 */
/* Finds all uncommented script tags, strips them out of the HTML and
 * stores them internally so we can put them in the placeholder later
 * (see JS::getCode() below)
 */
JS::findJavascripts($endcode);
/*
 * Proposal:  Use this
 *     $endcode = preg_replace_callback('/<script\s.*?src=(["\'])(.*?)(\1).*?\/?>(?:<\/script>)?/i', array('JS', 'registerFromRegex'), $endcode);
 * and change JS::registerFromRegex to use index 2
 */
// i know this is ugly, but is there another way
$endcode = str_replace('javascript_inserting_here', JS::getCode(), $endcode);

// do a final replacement of all those node-urls ({NODE_<ID>_<LANG>}- placeholders) that haven't been captured earlier
$endcode = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $endcode);
LinkGenerator::parseTemplate($endcode);

// replace links from before contrexx 3
$ls = new LinkSanitizer(
    ASCMS_PATH_OFFSET.Env::get('virtualLanguageDirectory').'/',
    $endcode);
$endcode = $ls->replace();

echo $endcode;

$objCache->endCache();
