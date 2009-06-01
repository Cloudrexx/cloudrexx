<?php

/**
 * Modul Admin Index
 *
 * CMS Administration
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Engineering Team
 * @version       $Id:    Exp $
 * @package     contrexx
 * @subpackage  admin
 */

/**
 * Debug level, see lib/DBG.php
 *   DBG_NONE            - Turn debugging off
 *   DBG_PHP             - show PHP errors/warnings/notices
 *   DBG_ADODB           - show ADODB queries
 *   DBG_ADODB_TRACE     - show ADODB queries with backtrace
 *   DBG_LOG_FILE        - DBG: log to file (/dbg.log)
 *   DBG_LOG_FIREPHP     - DBG: log via FirePHP
 *   DBG_ALL             - sets all debug flags
 */
define('_DEBUG', DBG_NONE);
include_once('../lib/DBG.php');

$startTime = explode(' ', microtime());
$adminPage = true;

/**
 * Path, database, FTP configuration settings
 *
 * Initialises global settings array and constants.
 */
include_once('../config/configuration.php');
/**
 * User configuration settings
 *
 * This file is re-created by the CMS itself. It initializes the
 * {@link $_CONFIG[]} global array.
 */
$incSettingsStatus = include_once('../config/settings.php');
/**
 * Version information
 *
 * Adds version information to the {@link $_CONFIG[]} global array.
 */
$incVersionStatus = include_once('../config/version.php');

//-------------------------------------------------------
// Check if system is installed
//-------------------------------------------------------
if (!defined('CONTEXX_INSTALLED') || !CONTEXX_INSTALLED) {
    header("Location: ../installer/index.php");
} elseif ($incSettingsStatus === false || $incVersionStatus === false) {
    die('System halted: Unable to load basic configuration!');
}

require_once('../core/API.php');

//-------------------------------------------------------
// Initialize database object
//-------------------------------------------------------
$strOkMessage = '';
$strErrMessage = '';
$objDatabase = getDatabaseObject($strErrMessage);
if ($objDatabase === false) {
    die('Database error: '.$strErrMessage);
}

if (_DEBUG & DBG_ADODB_TRACE) {
    $objDatabase->debug = 99;
} elseif (_DEBUG & DBG_ADODB) {
    $objDatabase->debug = 1;
} else {
    $objDatabase->debug = 0;
}

//-------------------------------------------------------
// Load settings and configuration
//-------------------------------------------------------

$objInit = new InitCMS('backend');

$sessionObj = new cmsSession();
$sessionObj->cmsSessionStatusUpdate('backend');

$objInit->_initBackendLanguage();
$objInit->getUserFrontendLangId();

$_LANGID = $objInit->getBackendLangId();
$_FRONTEND_LANGID = $objInit->userFrontendLangId;
// Post-2.1
define('FRONTEND_LANG_ID', $_FRONTEND_LANGID);
define('BACKEND_LANG_ID', $_LANGID);
define('LANG_ID', $_LANGID);

//-------------------------------------------------------
// language array for the core system
//-------------------------------------------------------
/**
 * Core language data
 * @ignore
 */
$_CORELANG = $objInit->loadLanguageData('core');

$cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '';

//-------------------------------------------------------
// Load the JS helper class and set the offset
//-------------------------------------------------------
require_once ASCMS_DOCUMENT_ROOT.'/lib/FRAMEWORK/Javascript.class.php';
JS::setOffset('../');


// To clone any module, use an optional integer cmd suffix.
// E.g.: "shop2", "gallery5", etc.
// Mind that you *MUST* copy all necessary database tables, and fix any
// references to that module (section and cmd parameters, database tables)
// using the MODULE_INDEX constant in the right place both in your code
// *and* templates!
// See the Shop module for a working example and instructions on how to
// clone any module.
$arrMatch = array();
$plainCmd = $cmd;
if (preg_match('/^(\D+)(\d+)$/', $cmd, $arrMatch)) {
    // The plain section/module name, used below
    $plainCmd = $arrMatch[1];
}
// The module index.
// Set to the empty string for the first instance (#1),
// and to an integer number of 2 or greater for any clones.
// This guarantees full backward compatibility with old code, templates
// and database tables for the default instance.
$moduleIndex = (empty($arrMatch[2]) ? '' : $arrMatch[2]);
$moduleId = ModuleChecker::getModuleIdByName($plainCmd);
/**
 * @ignore
 */
define('MODULE_INDEX', (intval($moduleIndex) == 0) ? '' : intval($moduleIndex));
define('MODULE_ID', $moduleId);
// Simple way to distinguish any number of cloned modules
// and apply individual access rights.  This offset is added
// to any static access ID before checking it.
$intAccessIdOffset = intval(MODULE_INDEX)*1000;

//-------------------------------------------------------
// language array for all modules
//-------------------------------------------------------
/**
 * Module specific data
 * @ignore
 */
$_ARRAYLANG = $objInit->loadLanguageData($plainCmd);
$_ARRAYLANG = array_merge($_ARRAYLANG, $_CORELANG);

$objTemplate = new HTML_Template_Sigma(ASCMS_ADMIN_TEMPLATE_PATH);
$objTemplate->setErrorHandling(PEAR_ERROR_DIE);

// language object from the Framework
$objLanguage = new FWLanguage();

// Module object
$objModules = new ModuleChecker();

$objFWUser = FWUser::getFWUserObject();

//-------------------------------------------------------
// Authentification start
//-------------------------------------------------------
if (!$objFWUser->objUser->login(true) && !$objFWUser->checkAuth()) {
    $modulespath = ASCMS_CORE_PATH . "/imagecreator.php";
    if (file_exists($modulespath)) require_once($modulespath);
    else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);

    switch ($plainCmd) {
        case "secure":
            $_SESSION['auth']['secid'] = FWUser::mkSECID();
            getSecurityImage($_SESSION['auth']['secid']);
            exit;
        case "lostpw":
            $objTemplate->loadTemplateFile('login_index.html');
            $objTemplate->addBlockfile('CONTENT_FILE', 'CONTENT_BLOCK', 'login_lost_password.html');
            $objTemplate->setVariable('TITLE', $_CORELANG['TXT_RESET_PASSWORD']);

            // set language variables
            $objTemplate->setVariable(array(
                'TXT_LOST_PASSWORD_TEXT'    => $_CORELANG['TXT_LOST_PASSWORD_TEXT'],
                'TXT_EMAIL'                    => $_CORELANG['TXT_EMAIL'],
                'TXT_RESET_PASSWORD'        => $_CORELANG['TXT_RESET_PASSWORD']
            ));

            if (isset($_POST['email'])) {
                $email = contrexx_stripslashes($_POST['email']);

                if (($objFWUser->restorePassword($email))) {
                    $statusMessage = str_replace("%EMAIL%", $email, $_CORELANG['TXT_LOST_PASSWORD_MAIL_SENT']);
                    if ($objTemplate->blockExists('login_lost_password')) {
                        $objTemplate->hideBlock('login_lost_password');
                    }
                } else {
                    $statusMessage = $objFWUser->getErrorMsg();
                }

                $objTemplate->setVariable(array(
                    'LOGIN_STATUS_MESSAGE'        => $statusMessage
                ));
            }
            $objTemplate->show();
            exit;
        case "resetpw":
            $objTemplate->loadTemplateFile('login_index.html');
            $objTemplate->addBlockfile('CONTENT_FILE', 'CONTENT_BLOCK', 'login_reset_password.html');
            $objTemplate->setVariable('TITLE', $_CORELANG['TXT_SET_NEW_PASSWORD']);

            function resetPassword($objTemplate)
            {
                global $_CORELANG, $objFWUser;

                $username = isset($_POST['username']) ? contrexx_stripslashes($_POST['username']) : (isset($_GET['username']) ? contrexx_stripslashes($_GET['username']) : '');
                $restoreKey = isset($_POST['restore_key']) ? contrexx_stripslashes($_POST['restore_key']) : (isset($_GET['restoreKey']) ? contrexx_stripslashes($_GET['restoreKey']) : '');
                $password = isset($_POST['password']) ? trim(contrexx_stripslashes($_POST['password'])) : '';
                $confirmedPassword = isset($_POST['password2']) ? trim(contrexx_stripslashes($_POST['password2'])) : '';
                $statusMessage = '';

                if (isset($_POST['reset_password'])) {
                    if ($objFWUser->resetPassword($username, $restoreKey, $password, $confirmedPassword, true)) {
                        $statusMessage = $_CORELANG['TXT_PASSWORD_CHANGED_SUCCESSFULLY'];
                        if ($objTemplate->blockExists('login_reset_password')) {
                            $objTemplate->hideBlock('login_reset_password');
                        }
                    } else {
                        $statusMessage = $objFWUser->getErrorMsg();

                        $objTemplate->setVariable(array(
                            'TXT_USERNAME'                        => $_CORELANG['TXT_USERNAME'],
                            'TXT_PASSWORD'                        => $_CORELANG['TXT_PASSWORD'],
                            'TXT_VERIFY_PASSWORD'                => $_CORELANG['TXT_VERIFY_PASSWORD'],
                            'TXT_PASSWORD_MINIMAL_CHARACTERS'    => $_CORELANG['TXT_PASSWORD_MINIMAL_CHARACTERS'],
                            'TXT_SET_PASSWORD_TEXT'                => $_CORELANG['TXT_SET_PASSWORD_TEXT'],
                            'TXT_SET_NEW_PASSWORD'                => $_CORELANG['TXT_SET_NEW_PASSWORD'],
                        ));

                        $objTemplate->parse('login_reset_password');
                    }
                } elseif (!$objFWUser->resetPassword($username, $restoreKey, $password, $confirmedPassword)) {
                    $statusMessage = $objFWUser->getErrorMsg();
                    if ($objTemplate->blockExists('login_reset_password')) {
                        $objTemplate->hideBlock('login_reset_password');
                    }
                } else {
                    $objTemplate->setVariable(array(
                        'TXT_USERNAME'                        => $_CORELANG['TXT_USERNAME'],
                        'TXT_PASSWORD'                        => $_CORELANG['TXT_PASSWORD'],
                        'TXT_VERIFY_PASSWORD'                => $_CORELANG['TXT_VERIFY_PASSWORD'],
                        'TXT_PASSWORD_MINIMAL_CHARACTERS'    => $_CORELANG['TXT_PASSWORD_MINIMAL_CHARACTERS'],
                        'TXT_SET_PASSWORD_TEXT'                => $_CORELANG['TXT_SET_PASSWORD_TEXT'],
                        'TXT_SET_NEW_PASSWORD'                => $_CORELANG['TXT_SET_NEW_PASSWORD'],
                    ));

                    $objTemplate->parse('login_reset_password');
                }

                $objTemplate->setVariable(array(
                    'LOGIN_STATUS_MESSAGE'    => $statusMessage,
                    'LOGIN_USERNAME'        => htmlentities($username, ENT_QUOTES, CONTREXX_CHARSET),
                    'LOGIN_RESTORE_KEY'        => htmlentities($restoreKey, ENT_QUOTES, CONTREXX_CHARSET)
                ));
            }

            resetPassword($objTemplate);
            $objTemplate->show();
            exit;
        default:
            if(checkGDExtension()) {
                $loginSecurityCode = '<img src="index.php?cmd=secure" alt="Security Code" title="Security Code"/>';
            } else {
                $_SESSION['auth']['secid'] = strtoupper(substr(md5(microtime()), 0, 4));
                $loginSecurityCode = $_SESSION['auth']['secid'];
            }

            $objTemplate->loadTemplateFile('login_index.html',true,true);
            $objTemplate->addBlockfile('CONTENT_FILE', 'CONTENT_BLOCK', 'login.html');
            $objTemplate->setVariable(array(
                'REDIRECT_URL'            => (!empty($_POST['redirect'])) ? $_POST['redirect'] : basename(getenv('REQUEST_URI')),
                'TXT_SECURITY_CODE'       => $_CORELANG['TXT_SECURITY_CODE'],
                'TXT_ENTER_SECURITY_CODE' => $_CORELANG['TXT_ENTER_SECURITY_CODE'],
                'TXT_USER_NAME'           => $_CORELANG['TXT_USER_NAME'],
                'TXT_PASSWORD'            => $_CORELANG['TXT_PASSWORD'],
                'TXT_LOGIN'               => $_CORELANG['TXT_LOGIN'],
                'TXT_PASSWORD_LOST'            => $_CORELANG['TXT_PASSWORD_LOST'],
                'UID'                     => isset($_COOKIE['username']) ? $_COOKIE['username'] : '',
                'TITLE'                   => $_CORELANG['TXT_LOGIN'],
                'LOGIN_IMAGE'             => $loginSecurityCode,
                'LOGIN_ERROR_MESSAGE'     => $objFWUser->getErrorMsg()
            ));
            $objTemplate->show();
            exit;
    }
}
if (isset($_POST['redirect']) && preg_match('/\.php/',($_POST['redirect']))) {
    $redirect = $_POST['redirect'];
    header("Location: $redirect");
}

//-------------------------------------------------------
// Site start
//-------------------------------------------------------
if (!isset($_REQUEST['standalone']) || $_REQUEST['standalone'] == 'false') {
    $objTemplate->loadTemplateFile('index.html');
    if (Permission::checkAccess(35, 'static', true)) {
    $objTemplate->addBlockfile('QUICKLINKS_CONTENT', 'quicklinks', 'quicklinks.html');
    }
    $objTemplate->setVariable(
        array(
            'TXT_PAGE_ID'        => $_CORELANG['TXT_PAGE_ID'],
            'CONTREXX_CHARSET'    => CONTREXX_CHARSET
        )
    );
    $objTemplate->addBlockfile('CONTENT_OUTPUT', 'content_master', 'content_master.html');
}

switch ($plainCmd) {
    //-------------------------------------------------------
    // access & user management
    //-----------------------------------------------------------------------------------------------
    case "access":
        $modulespath = ASCMS_CORE_MODULE_PATH . "/access/admin.class.php";
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_COMMUNITY'];
        $objAccessManager = new AccessManager();
        $objAccessManager->getPage();
        break;

    //-----------------------------------------------------------------------------------------------
    // e-government
    //-------------------------------------------------------
    case 'egov':
        Permission::checkAccess(109, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/egov/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_EGOVERNMENT'];
        $objEgov = new eGov();
        $objEgov->getPage();
        break;

    //-------------------------------------------------------
    // banner management
    //-------------------------------------------------------
    case 'banner':
        // Permission::checkAccess(??, 'static');
        $modulespath = ASCMS_CORE_MODULE_PATH.'/banner/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_BANNER_ADMINISTRATION'];
        $objBanner = new Banner();
        $objBanner->getPage();
        break;

    //-------------------------------------------------------
    // jobs Module
    //-------------------------------------------------------
    case 'jobs':
        Permission::checkAccess(11, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/jobs/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_JOBS_MANAGER'];
        $objJobs = new jobsManager();
        $objJobs->getJobsPage();
        break;

    //-------------------------------------------------------
    // file browser
    //-------------------------------------------------------
    case 'fileBrowser':
        $modulespath = ASCMS_CORE_MODULE_PATH.'/fileBrowser/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objFileBrowser = new FileBrowser();
        $objFileBrowser->getPage();
        exit;
        break;

    //-------------------------------------------------------
    // file uploader
    //-------------------------------------------------------
    case 'fileUploader':
        $modulespath = ASCMS_MODULE_PATH.'/fileUploader/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $objFileUploader = new FileUploader();
        $objFileUploader->getPage();
        exit;
        break;

    //-------------------------------------------------------
    // feed
    //-------------------------------------------------------
    case 'feed':
        Permission::checkAccess(27, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/feed/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_NEWS_SYNDICATION'];
        $objFeed = new feedManager();
        $objFeed->getFeedPage();
        break;

    //-------------------------------------------------------
    // news-management
    //-------------------------------------------------------
    case 'server':
        Permission::checkAccess(4, 'static');
        $modulespath = ASCMS_CORE_PATH.'/serverSettings.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_SERVER_INFO'];
        $objServer = new serverSettings();
        $objServer->getPage();
        break;

    //-------------------------------------------------------
    // log manager
    //-------------------------------------------------------
    case 'log':
        Permission::checkAccess(18, 'static');
        $modulespath = ASCMS_CORE_PATH.'/log.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_LOG_ADMINISTRATION'];
        $objLogManager = new logmanager();
        $objLogManager->getLogPage();
        break;

    //-------------------------------------------------------
    // Online Shop
    //-------------------------------------------------------
    case 'shop':
        Permission::checkAccess($intAccessIdOffset+13, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/shop/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_SHOP_ADMINISTRATION'];
        $objShopManager = new shopmanager();
        $objShopManager->getShopPage();
        break;

    //-------------------------------------------------------
    // themes: skins
    //-------------------------------------------------------
    case 'skins':
        //Permission::checkAccess(18, 'static');
        $modulespath = ASCMS_CORE_PATH.'/skins.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DESIGN_MANAGEMENT'];
        $objSkins = new skins();
        $objSkins->getPage();
        break;

    //-------------------------------------------------------
    // content management
    //-------------------------------------------------------
    case 'content':
        $modulespath = ASCMS_CORE_PATH.'/ContentManager.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_CONTENT_MANAGER'];
        $objContent = new ContentManager();
        $objContent->getPage();
        break;

    //-------------------------------------------------------
    // content workflow
    //-------------------------------------------------------
    case 'workflow':
        $modulespath = ASCMS_CORE_PATH.'/ContentWorkflow.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_CONTENT_HISTORY'];
        $objWorkflow = new ContentWorkflow();
        $objWorkflow->getPage();
        break;

    //-------------------------------------------------------
    // Document System Module
    //-------------------------------------------------------
    case 'docsys':
        Permission::checkAccess(11, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/docsys/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DOC_SYS_MANAGER'];
        $objDocSys = new docSysManager();
        $objDocSys->getDocSysPage();
        break;

    //-------------------------------------------------------
    // news-management
    //-------------------------------------------------------
    case 'news':
        Permission::checkAccess(10, 'static');
        $modulespath = ASCMS_CORE_MODULE_PATH.'/news/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_NEWS_MANAGER'];
        $objNews = new NewsManager();
        $objNews->getPage();
        break;

    //-------------------------------------------------------
    // contact-management
    //-------------------------------------------------------
    case 'contact':
        // Permission::checkAccess(10, 'static');
        $modulespath = ASCMS_CORE_MODULE_PATH.'/contact/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_CONTACTS'];
        $objContact = new contactManager();
        $objContact->getPage();
        break;

    //-------------------------------------------------------
    // Immo-management
    //-------------------------------------------------------
    case 'immo':
        $modulespath = ASCMS_MODULE_PATH.'/immo/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_IMMO_MANAGEMENT'];
        $objImmo = new Immo();
        $objImmo->getPage();
    break;

    //-------------------------------------------------------
    // Livecam
    //-------------------------------------------------------
    case 'livecam':
        // Permission::checkAccess(9, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/livecam/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_LIVECAM'];
        $objLivecam = new LivecamManager();
        $objLivecam->getPage();
        break;

    //-------------------------------------------------------
    // guestbook
    //-------------------------------------------------------
    case 'guestbook':
        Permission::checkAccess(9, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/guestbook/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_GUESTBOOK'];
        $objGuestbook = new GuestbookManager();
        $objGuestbook->getPage();
        break;


    //-------------------------------------------------------
    // dataviewer
    //-------------------------------------------------------
    case 'dataviewer':
        Permission::checkAccess(9, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/dataviewer/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DATAVIEWER'];
        $objDataviewer = new Dataviewer();
        $objDataviewer->getPage();
        break;


    //-------------------------------------------------------
    // Memberdir
    //-------------------------------------------------------
        case 'memberdir':
            Permission::checkAccess(83, 'static');
            $modulespath = ASCMS_MODULE_PATH.'/memberdir/admin.class.php';
            if (file_exists($modulespath)) require_once($modulespath);
            else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
            $subMenuTitle = $_CORELANG['TXT_MEMBERDIR'];
            $objMemberdir = new MemberDirManager();
            $objMemberdir->getPage();
        break;


    //-------------------------------------------------------
    // Download
    //-------------------------------------------------------
    case 'download':
        Permission::checkAccess(57, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/download/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DOWNLOAD_MANAGER'];
        $objDownload = new DownloadManager();
        $objDownload->getPage();
        break;

    //-------------------------------------------------------
    // media manager
    //-------------------------------------------------------
    case 'media':
        $modulespath = ASCMS_CORE_MODULE_PATH.'/media/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_MEDIA_MANAGER'];
        $objMedia = new MediaManager();
        $objMedia->getMediaPage();
        break;


    //-------------------------------------------------------
    // development
    //-------------------------------------------------------
    case 'development':
        Permission::checkAccess(81, 'static');
        $modulespath = ASCMS_CORE_MODULE_PATH.'/development/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DEVELOPMENT'];
        $objDevelopment = new Development();
        $objDevelopment->getPage();
        break;

    //-------------------------------------------------------
    // database manager
    //-------------------------------------------------------
    case 'dbm':
        $modulespath = ASCMS_CORE_PATH.'/DatabaseManager.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DATABASE_MANAGER'];
        $objDatabaseManager = new DatabaseManager();
        $objDatabaseManager->getPage();
        break;

    //-------------------------------------------------------
    //stats
    //-------------------------------------------------------
    case 'stats':
        Permission::checkAccess(19, 'static');
        $modulespath = ASCMS_CORE_MODULE_PATH.'/stats/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_STATISTIC'];
        $statistic= new stats();
        $statistic->getContent();
        break;

    //-------------------------------------------------------
    // alias
    //-------------------------------------------------------
    case 'alias':
        Permission::checkAccess(115, 'static');
        $modulespath = ASCMS_CORE_MODULE_PATH.'/alias/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_ALIAS_ADMINISTRATION'];
        $objAlias = new AliasAdmin();
        $objAlias->getPage();
        break;

    //-------------------------------------------------------
    // nettools
    //-------------------------------------------------------
    case 'nettools':
        Permission::checkAccess(54, 'static');
        $modulespath = ASCMS_CORE_MODULE_PATH.'/nettools/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_NETWORK_TOOLS'];
        $nettools = new netToolsManager();
        $nettools->getContent();
        break;

    //-------------------------------------------------------
    // newsletter
    //-------------------------------------------------------
    case 'newsletter':
        $modulespath = ASCMS_MODULE_PATH.'/newsletter/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_NEWSLETTER'];
        $objNewsletter = new newsletter();
        $objNewsletter->getPage();
        break;

    //-------------------------------------------------------
    // settings
    //-------------------------------------------------------
    case 'settings':
        Permission::checkAccess(17, 'static');
        $modulespath = ASCMS_CORE_PATH.'/settings.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_SYSTEM_SETTINGS'];
        $objSettings = new settingsManager();
        $objSettings->getPage();
        break;

    //-------------------------------------------------------
    // language management
    //-------------------------------------------------------
    case 'language':
        Permission::checkAccess(22, 'static');
        $modulespath = ASCMS_CORE_PATH.'/language.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_LANGUAGE_SETTINGS'];
        $objLangManager = new LanguageManager();
        $objLangManager->getLanguagePage();
        break;

    //-------------------------------------------------------
    // module manager
    //-------------------------------------------------------
    case 'modulemanager':
        Permission::checkAccess(23, 'static');
        $modulespath = ASCMS_CORE_PATH.'/modulemanager.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_MODULE_MANAGER'];
        $objModuleManager = new modulemanager();
        $objModuleManager->getModulesPage();
        break;

    //-------------------------------------------------------
	// E-Card
    //-------------------------------------------------------
    case 'ecard':
        $modulespath = ASCMS_MODULE_PATH.'/ecard/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
      	$subMenuTitle = $_CORELANG['TXT_ECARD_TITLE'];
        $objEcard = new ecard();
        $objEcard->getPage();
        break;

    //-------------------------------------------------------
    // voting
    //-------------------------------------------------------
    case 'voting':
        Permission::checkAccess(14, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/voting/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_CONTENT_MANAGER'];
        $objvoting = new votingmanager();
        $objvoting->getVotingPage();
        break;

    //-------------------------------------------------------
    // survey
    //-------------------------------------------------------
    case 'survey':
        Permission::checkAccess(111, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/survey/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_SURVEY'];
        $objSurvey = new SurveyAdmin();
        $objSurvey->getPage();
        break;

    //-------------------------------------------------------
    // calendar
    //-------------------------------------------------------
    case 'calendar':
        Permission::checkAccess(16, 'static');
//            $objRs = $objDatabase->Execute('SELECT id FROM '.DBPREFIX.'
//                                            WHERE name = 'calendar.'.$mandate.''');
//            print $objRs->fields['id'];
//            Permission::checkAccess($objRs->fields['id'], 'static');
            $modulespath = ASCMS_MODULE_PATH.'/calendar'.MODULE_INDEX.'/admin.class.php';
        /**
         * @ignore
         */
        define('CALENDAR_MANDATE', MODULE_INDEX);
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_CALENDAR'];
        $objCalendar = new calendarManager();
        $objCalendar->getCalendarPage();
        break;

    case 'reservation':
        $modulespath = ASCMS_MODULE_PATH.'/reservation/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_RESERVATION_MODULE'];
        $objReservationModule = new reservationManager();
        $objReservationModule->getPage();
    break;

    //-------------------------------------------------------
    // Recommend
    //-------------------------------------------------------
    case 'recommend':
        Permission::checkAccess(64, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/recommend/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_RECOMMEND'];
        $objCalendar = new RecommendManager();
        $objCalendar->getPage();
        break;

    //-------------------------------------------------------
    // forum
    //-------------------------------------------------------
    case 'forum':
        Permission::checkAccess(106, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/forum/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_FORUM'];
        $objForum = new ForumAdmin();
        $objForum->getPage();
        break;

    //-------------------------------------------------------
    // thumbnail gallery
    //-------------------------------------------------------
    case 'gallery':
        Permission::checkAccess(12, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/gallery/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_GALLERY_TITLE'];
        $objGallery = new galleryManager();
        $objGallery->getPage();
        break;

    //-------------------------------------------------------
    // themes: directory
    //-------------------------------------------------------
    case 'directory':
        //Permission::checkAccess(18, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/directory/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_LINKS_MODULE_DESCRIPTION'];
        $objDirectory = new rssDirectory();
        $objDirectory->getPage();
        break;

    //-------------------------------------------------------
    // block system
    //-------------------------------------------------------
    case 'block':
        Permission::checkAccess(76, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/block/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_BLOCK_SYSTEM'];
        $objBlock = new blockManager();
        $objBlock->getPage();
        break;

    //-------------------------------------------------------
    // popup system
    //-------------------------------------------------------
    case 'popup':
        Permission::checkAccess(117, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/popup/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_POPUP_SYSTEM'];
        $objPopup = new popupManager();
        $objPopup->getPage();
        break;


    //-------------------------------------------------------
    // market
    //-------------------------------------------------------
    case 'market':
        Permission::checkAccess(98, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/market/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_MARKET_TITLE'];
        $objMarket = new Market();
        $objMarket->getPage();
        break;

    //-----------------------------------------------------------------------------------------------
    // data
    //-----------------------------------------------------------------------------------------------
    case "data":
        Permission::checkAccess(122, 'static'); // ID !!
        $modulespath = ASCMS_MODULE_PATH . "/data/admin.class.php";
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DATA_MODULE'];
        $objData = new DataAdmin();
        $objData->getPage();
        break;

    //-----------------------------------------------------------------------------------------------
    // podcast
    //-------------------------------------------------------
    case 'podcast':
        Permission::checkAccess(87, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/podcast/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_PODCAST'];
        $objPodcast = new podcastManager();
        $objPodcast->getPage();
        break;

    /**
     * Support System Module
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @since   1.2.0
     * @version 0.0.1 alpha
     */
    case 'support':
        Permission::checkAccess(87, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/support/admin.class.php';
        if (file_exists($modulespath)) {
            require_once($modulespath);
        } else {
            die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        }
        $subMenuTitle = $_CORELANG['TXT_SUPPORT_SYSTEM'];
        $objSupport = new Support();
        $objSupport->getPage();
        break;

    /**
     * Blog Module
     * @author  Thomas Kaelin <thomas.kaelin@comvation.com>
     * @since   1.2.0
     * @version 1.0
     */
    case 'blog':
        Permission::checkAccess(119, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/blog/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_BLOG_MODULE'];
        $objBlog = new BlogAdmin();
        $objBlog->getPage();
        break;

    /**
     * Knowledge Module
     * @author  Stefan Heinemann <sh@comvation.com>
     * @since   2.1.0
     * @version 1.0
     */
    case 'knowledge':
        Permission::checkAccess(129, 'static');
        $modulespath = ASCMS_MODULE_PATH . '/knowledge/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_KNOWLEDGE'];
        $objKnowledge = &new KnowledgeAdmin();
        $objKnowledge->getPage();
        break;

    /**
     * U2U Module
     * @author  comvation <info@comvation.com>
     * @since   2.1.0
     * @version 1.0
     */
    case 'u2u':
    	Permission::checkAccess(141, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/u2u/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_U2U_MODULE'];
        $objU2u = new u2uAdmin();
        $objU2u->getPage();
        break;

    /**
     * Partners Module
     * @author  comvation <info@comvation.com>
     * @since   2.0.1
     * @version 1.0
     */
    case 'partners':
    	Permission::checkAccess(140, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/partners/admin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle  = $_CORELANG['TXT_PARTNERS_MODULE'];
        $objPartner = new PartnersAdmin();
        $objPartner->getPage();
        break;

    /**
     * Auction Module
     * @author  comvation <info@comvation.com>
     * @since   2.0.1
     * @version 1.0
     */
    case 'auction':
        Permission::checkAccess(143, 'static');
        $modulespath = ASCMS_MODULE_PATH.'/auction/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_AUCTION_TITLE'];
        $objAuction = new Auction();
        $objAuction->getPage();
        break;


    //-------------------------------------------------------
    // access denied
    //-------------------------------------------------------
    case 'noaccess':
        //Temporary no-acces-file and comment
        $subMenuTitle=$_CORELANG['TXT_ACCESS_DENIED'];
        $objTemplate->setVariable(array(
        'CONTENT_TITLE'                => $_CORELANG['TXT_ACCESS_DENIED'],
        'CONTENT_NAVIGATION'        => htmlentities($_CONFIG['coreCmsName'], ENT_QUOTES, CONTREXX_CHARSET),
        'CONTENT_STATUS_MESSAGE'    => '',
        'ADMIN_CONTENT'          =>
            '<img src="images/stop_hand.gif" alt="" /><br /><br />'.
            $_CORELANG['TXT_ACCESS_DENIED_DESCRIPTION']
        ));
        break;

    //-------------------------------------------------------
    // logout
    //-------------------------------------------------------
    case 'logout':
        $objFWUser->logout();
        exit;
        break;

    //-------------------------------------------------------
    // downloads
    //-------------------------------------------------------
    case 'downloads':
        $modulespath = ASCMS_MODULE_PATH.'/downloads/admin.class.php';
        if (file_exists($modulespath)) require_once($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_DOWNLOADS'];
        $objDownloadsModule = new downloads();
        $objDownloadsModule->getPage();
    break;

    //-------------------------------------------------------
    // show default admin page
    //-------------------------------------------------------
    default:
        $modulespath = ASCMS_CORE_PATH.'/myAdmin.class.php';
        if (file_exists($modulespath)) include($modulespath);
        else die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
        $subMenuTitle = $_CORELANG['TXT_ADMINISTRATION_INDEX'];
        $objAdminNav = new myAdminManager();
        $objAdminNav->getPage();
        break;
}

//-------------------------------------------------------
// page parsing
//-------------------------------------------------------
$finishTime = explode(' ', microtime());
$parsingTime = round(((float)$finishTime[0] + (float)$finishTime[1]) - ((float)$startTime[0] + (float)$startTime[1]), 5);

$objAdminNav = new adminMenu();
$objAdminNav->getAdminNavbar();
$objTemplate->setVariable(array(
'SUB_MENU_TITLE' => $subMenuTitle,
'FRONTEND_LANG_MENU' => $objInit->getUserFrontendLangMenu(),
'TXT_GENERATED_IN' => $_CORELANG['TXT_GENERATED_IN'],
'TXT_SECONDS' => $_CORELANG['TXT_SECONDS'],
'TXT_LOGOUT_WARNING' => $_CORELANG['TXT_LOGOUT_WARNING'],
'PARSING_TIME'=> $parsingTime,
'LOGGED_NAME' => htmlentities($objFWUser->objUser->getProfileAttribute('firstname').' '.$objFWUser->objUser->getProfileAttribute('lastname'), ENT_QUOTES, CONTREXX_CHARSET),
'TXT_LOGGED_IN_AS' => $_CORELANG['TXT_LOGGED_IN_AS'],
'TXT_LOG_OUT' => $_CORELANG['TXT_LOG_OUT'],
    'CONTENT_WYSIWYG_CODE' => get_wysiwyg_code(),
    // Mind: The module index is not used in any non-module template
    // for the time being, but is provided for future use and convenience.
    'MODULE_INDEX'         => MODULE_INDEX,
    'JAVASCRIPT'            => JS::getCode()
));

if (isset($objTemplate->_variables['CONTENT_STATUS_MESSAGE']) && !empty($objTemplate->_variables['CONTENT_STATUS_MESSAGE'])) {
    $objTemplate->_variables['CONTENT_STATUS_MESSAGE'] =
        '<div id="alertbox" style="overflow:auto">'.
        $objTemplate->_variables['CONTENT_STATUS_MESSAGE'].'</div><br />';
}

if (!empty($objTemplate->_variables['CONTENT_OK_MESSAGE'])) {
    if (!isset($objTemplate->_variables['CONTENT_STATUS_MESSAGE'])) {
        $objTemplate->_variables['CONTENT_STATUS_MESSAGE'] = '';
    }
    $objTemplate->_variables['CONTENT_STATUS_MESSAGE'] .=
        '<div id="okbox" style="overflow:auto">'.
        $objTemplate->_variables['CONTENT_OK_MESSAGE'].'</div><br />';
}

if (!empty($objTemplate->_variables['CONTENT_WARNING_MESSAGE'])) {
    $objTemplate->_variables['CONTENT_STATUS_MESSAGE'] .=
        '<div class="warningbox" style="overflow: auto">'.
        $objTemplate->_variables['CONTENT_WARNING_MESSAGE'].'</div><br />';
}

// Style parsing
if (file_exists(ASCMS_ADMIN_TEMPLATE_PATH.'/css/'.$cmd.'.css')) {
    // check if there's a css file in the core section
    $objTemplate->setVariable('ADD_STYLE_URL', ASCMS_ADMIN_TEMPLATE_WEB_PATH.'/css/'.$cmd.'.css');
    $objTemplate->parse('additional_style');
} elseif (file_exists(ASCMS_MODULE_PATH.'/'.$cmd.'/template/backend.css')) {
    // of maybe in the current module directory
    $objTemplate->setVariable('ADD_STYLE_URL', ASCMS_MODULE_WEB_PATH.'/'.$cmd.'/template/backend.css');
    $objTemplate->parse('additional_style');
} elseif (file_exists(ASCMS_CORE_MODULE_PATH.'/'.$cmd.'/template/backend.css')) {
    // or in the core module directory
    $objTemplate->setVariable('ADD_STYLE_URL', ASCMS_CORE_MODULE_WEB_PATH.'/'.$cmd.'/template/backend.css');
    $objTemplate->parse('additional_style');
} else {
    $objTemplate->hideBlock('additional_style');
}

$objTemplate->show();

?>
