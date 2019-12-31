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
 * @ignore
 */
require_once(ASCMS_FRAMEWORK_PATH.'/Validator.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/User/User_Setting_Mail.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/User/User_Setting.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/FWUser.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/User/User_Profile.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/User/User.class.php');

/**
 * Install Wizard Controller
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       $Id:     Exp $
 * @package     cloudrexx
 * @subpackage  installer
 * @todo        Edit PHP DocBlocks!
 */
class Installer
{
    var $colorSuccessfully = "#00ff00";
    var $colorFailed = "#ff0000";

    var $configGeneral = false;
    var $configTimezone = false;
    var $configDb = false;
    var $configFtp = false;

    var $tabIndex = 1;
    var $inputFocus = "";

    var $arrStatusMsg = array();

    var $arrDirectoryTree = array();
    var $arrDirectoryPaths = array();
    var $directoryTreeRowClassNr = 0;

    var $step;
    var $unattended;


    var $arrSteps = array(
        0 => array(
            'step'  => 'welcome',
            'text'  => 'TXT_WELCOME'
            ),
        1 => array(
            'step'  => 'license',
            'text'  => 'TXT_LICENSE'
            ),
        2 => array(
            'step'  => 'requirements',
            'text'  => 'TXT_REQUIREMENTS'
            ),
        3 => array(
            'step'  => 'configuration',
            'text'  => 'TXT_CONFIGURATION'
            ),
        4 => array(
            'step'  => 'installation',
            'text'  => 'TXT_INSTALLATION'
            ),
        5 => array(
            'step'  => 'systemConfig',
            'text'  => 'TXT_SYSTEM_CONFIGURATION'
            ),
        6 => array(
            'step'  => 'adminAccount',
            'text'  => 'TXT_ADMIN_ACCOUNT'
            ),
        7 => array(
            'step'  => 'termination',
            'text'  => 'TXT_TERMINATION'
            )
    );

    function Installer() {
        $this->__construct();
    }


    function __construct() {
        global $objCommon;

        // check if system is installed
        if ($objCommon->checkInstallationStatus()) {
            header('Location: ../index.php');
            exit;
        }

        $this->unattended = !empty($_SERVER['argv'][1]);

        // set step
        if (isset($_GET['cancel'])) {
            unset($_POST);
            unset($_SESSION);
            session_destroy();
            session_start();
        } elseif (isset($_GET['back'])) {
            $_SESSION['installer']['step']--;
            unset($_POST);
        }

        if (!isset($_SESSION['installer']['started'])) {
            $_SESSION['installer']['step'] = 0;
        } elseif (isset($_GET['stepId'])) {
            $_SESSION['installer']['step'] = intval($_GET['stepId']);
        }

        if ($_SESSION['installer']['step'] < 0) {
            $_SESSION['installer']['step'] = 0;
        }
    }

    function getNavigation() {
        global $objTpl, $_ARRLANG, $arrLanguages, $language, $templatePath;

        $useDefaultLanguage = true;

        $objTpl->addBlockfile('NAVIGATION', 'NAVIGATION_BLOCK', "navigation.html");

        foreach ($this->arrSteps as $stepId => $arrStep) {
            if ($stepId < $_SESSION['installer']['step']) {
                $cssClass = 'done';
            } else if ($stepId == $_SESSION['installer']['step']) {
                $cssClass = 'active';
            } else {
                $cssClass = 'upcoming';
            }

            $objTpl->setVariable(array(
                'CLASS' => $cssClass,
                'STEP_NAME' => $_ARRLANG[$arrStep['text']],
                'STEP_URL' => $stepId <= $_SESSION['installer']['step'] ? 'index.php?stepId='.$stepId : 'javascript:void(0);',
            ));
            $objTpl->parse('navtree');
        }

        // set language menu
        foreach ($arrLanguages as $arrLanguage) {
            if (strtolower($language) == strtolower($arrLanguage['lang'])) {
                $useDefaultLanguage = false;
                break;
            }
        }

        foreach ($arrLanguages as $langId => $arrLanguage) {
            $selected = "";

            if (isset($_SESSION['installer']['langId'])) {
                if ($langId == $_SESSION['installer']['langId']) {
                    $selected = "selected=\"selected\"";
                }
            } elseif ($useDefaultLanguage) {
                if ($arrLanguage['is_default'] == "true") {
                    $selected = "selected=\"selected\"";
                }
            } elseif ($language == $arrLanguage['lang']) {
                $selected = "selected=\"selected\"";
            }

            $objTpl->setVariable(array(
                'LANGID'    => $langId,
                'SELECTED'  => $selected,
                'LANGUAGE'  => $arrLanguage['name']
            ));
            $objTpl->parse('languageOptions');
        }

        $objTpl->parse('NAVIGATION_BLOCK');
    }

    function getContentNavigation() {
        global $objTpl, $_ARRLANG;

        $navigationBar = "";

        $objTpl->addBlockfile('CONTENT_NAVIGATION', 'CONTENT_NAVIGATION_BLOCK', 'content_navigation.html');

        if ($_SESSION['installer']['step'] == 0) {
            $navigationBar .= "<input type=\"submit\" name=\"next\" value=\"".$_ARRLANG['TXT_NEXT']."\" tabindex=\"".$this->_getTabIndex()."\" />";
        } elseif ($_SESSION['installer']['step'] != count($this->arrSteps)-1) {
            $navigationBar = "<input type=\"button\" name=\"cancel\" value=\"".$_ARRLANG['TXT_CANCEL']."\" onclick=\"window.location.href='index.php?cancel';\" tabindex=\"".$this->_getTabIndex()."\" />&nbsp;";
            $navigationBar .= "<input type=\"button\" name=\"back\" value=\"".$_ARRLANG['TXT_BACK']."\" onclick=\"window.location.href='index.php?back';\" tabindex=\"".$this->_getTabIndex()."\" />&nbsp;";
            $navigationBar .= "<input type=\"submit\" name=\"next\" value=\"".$_ARRLANG['TXT_NEXT']."\" tabindex=\"".$this->_getTabIndex()."\" />";
        }

        $objTpl->setVariable(array(
            'NAVIGATION_BAR' => $navigationBar
        ));
    }

    function checkOptions() {
        if (!empty($_SESSION['installer']['config']['useFtp'])) {
            global $objCommon;

            $ftpConfig = array(
                    'is_activated'  => $_SESSION['installer']['config']['useFtp'],
                    'host'          => $_SESSION['installer']['config']['ftpHostname'],
                    'port'          => isset($_SESSION['installer']['config']['ftpPort']) ? $_SESSION['installer']['config']['ftpPort'] : $objCommon->ftpPort,
                    'username'      => $_SESSION['installer']['config']['ftpUsername'],
                    'password'      => $_SESSION['installer']['config']['ftpPassword'],
                    'path'          => $_SESSION['installer']['config']['ftpPath'],
            );
            \Env::set('ftpConfig', $ftpConfig);
        }

        switch ($this->arrSteps[$_SESSION['installer']['step']]['step']) {
            case 'welcome':
                $this->_checkWelcome();
                break;

            case 'license':
                $this->_checkLicense();
                break;

            case 'requirements':
                $this->_checkRequirements();
                break;

            case 'configuration':
                $this->_checkConfiguration();
                break;

            case 'installation':
                $this->_checkInstallation();
                break;

            case 'systemConfig':
                $this->_checkSystemConfig();
                break;

            case 'adminAccount':
                $this->_checkAdminAccount();
                break;
        }
    }

    function getPage() {
        global $objTpl, $objCommon, $_ARRLANG;

        $page = "";

        if (isset($_REQUEST['page'])) {
            $page = $_REQUEST['page'];
        }

        switch ($page) {
            case 'help':
                $this->_getHelpPage();
                $objTpl->setVariable('TITLE', $_ARRLANG['TXT_HELP']);
                break;

            case 'phpinfo':
                phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT | INFO_VARIABLES);
                exit;
                break;

            default:
                switch ($this->arrSteps[$_SESSION['installer']['step']]['step']) {
                    case 'welcome':
                        $this->_getWelcomePage();
                        break;

                    case 'license':
                        $this->_getLicensePage();
                        break;

                    case 'requirements':
                        $this->_getRequirementsPage();
                        break;

                    case 'configuration':
                        $this->_getConfigurationPage();
                        break;

                    case 'installation':
                        $this->_getInstallationPage();
                        break;

                    case 'systemConfig':
                        $this->_getSystemConfigPage();
                        break;

                    case 'adminAccount':
                        $this->_getAdminAccountPage();
                        break;

                    case 'termination':
                        $this->_showTermination();
                        break;
                }
                $objTpl->setVariable('TITLE', $_ARRLANG[$this->arrSteps[$_SESSION['installer']['step']]['text']]);
        }

        $objCommon->closeDbConnection();
        $objCommon->closeFtpConnection();
    }

    /**
    * check welcome
    *
    * set next step to active if the next button was pressed
    *
    * @access   private
    */
    function _checkWelcome() {
        if (isset($_POST['next'])) {
            $_SESSION['installer']['step']++;
        }
    }

    /**
    * get welcome page
    *
    * show the welcome page
    *
    * @access   private
    * @global   mixed   $objTpl
    * @global   mixed   $objCommon
    * @global   string  $language
    * @global   array   $arrLanguages
    * @global   array   $_CONFIG
    * @global   array   $_ARRLANG
    * @global   sring   $contrexxURI
    */
    function _getWelcomePage() {
        global $objTpl, $objCommon, $language, $arrLanguages, $_CONFIG, $_ARRLANG, $contrexxURI;

        if (!isset($_SESSION['installer']['started'])) {
            $_SESSION['installer']['started'] = true;
        }

        // load content
        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', 'welcome.html');

        $welcomeMsg = str_replace("[VERSION]", str_replace(' Service Pack 0', '', preg_replace('#^(\d+\.\d+)\.(\d+)$#', '$1 Service Pack $2', $_CONFIG['coreCmsVersion'])), $_ARRLANG['TXT_WELCOME_MSG']);
        $welcomeMsg = str_replace("[NAME]", $_CONFIG['coreCmsName'], $welcomeMsg);

        $objTpl->setVariable(array(
            'WELCOME_MSG' => $welcomeMsg
        ));

        $newestVersion = $objCommon->getNewestVersion();
        if (!$objCommon->_isNewestVersion($_CONFIG['coreCmsVersion'], $newestVersion)) {
            $versionMsg = str_replace("[VERSION]", str_replace(' Service Pack 0', '', preg_replace('#^(\d+\.\d+)\.(\d+)$#', '$1 Service Pack $2', $newestVersion)), $_ARRLANG['TXT_NEW_VERSION']);
            $versionMsg = str_replace("[NAME]", "<a href=\"".$contrexxURI."\" target=\"_blank\">".$_CONFIG['coreCmsName']."</a>", $versionMsg);
            $objTpl->setVariable(array(
                'NEW_VERSION_TEXT' => "<br />".$versionMsg
            ));
            $objTpl->parse('newVersion');
        } else {
            $objTpl->hideBlock('newVersion');
        }
    }

    /**
    * check license
    *
    * go to next step if the license was accepted
    *
    * @access   private
    */
    function _checkLicense() {
        global $_ARRLANG;

        $this->arrStatusMsg['license'] = "";

        if (isset($_POST['next'])) {
            if (isset($_POST['license']) && $_POST['license'] == "accepted") {
                $_SESSION['installer']['license'] = true;
            } else {
                $this->arrStatusMsg['license'] = $_ARRLANG['TXT_MUST_ACCEPT_LICENCE'];
                $_SESSION['installer']['license'] = false;
            }

            if ($_SESSION['installer']['license']) {
                $_SESSION['installer']['step']++;
            }
        }
    }

    /**
    * get license page
    *
    * show the license
    *
    * @access   private
    * @global   mixed   $objTpl
    * @global   string  $licenseFileCommerce
    * @global   string  $licenseFileOpenSource
    * @global   array   $_CONFIG
    */
    function _getLicensePage() {
        global $objTpl, $licenseFileCommerce, $licenseFileOpenSource, $_CONFIG;

        // load content tempalte
        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', "license.html");

        // get license
        if ($_CONFIG['coreCmsEdition'] != "OpenSource") {
            $licenseFile = $licenseFileCommerce;
        } else {
            $licenseFile = $licenseFileOpenSource;
        }
        $license = @file_get_contents($licenseFile);

        // set template variables
        $objTpl->setVariable(array(
            'LICENSE'   => nl2br(preg_replace('/^([0-9]\.[0-9]?\s[^\n]+)\n$/im', '<strong>\1</strong>', $license)),
            'CHECKED'   => (isset($_SESSION['installer']['license']) && $_SESSION['installer']['license']) ? "checked=\"checked\"" : ""
        ));

        if (isset($this->arrStatusMsg) && !empty($this->arrStatusMsg['license'])) {
            $objTpl->setVariable(array(
                'STATUS_MSG' => $this->arrStatusMsg['license']
            ));
            $objTpl->parse('statusMsg');
        } else {
            $objTpl->hideBlock('statusMsg');
        }
    }

    /**
    * check requirements
    *
    * check if the system does full fit the requirements
    *
    * @access   private
    * @global   object  $objCommon
    * @global   double  $requiredGDVersion
    * @global   double  $requiredPHPVersion
    */
    function _checkRequirements() {
        global $objCommon, $requiredGDVersion, $requiredPHPVersion;

        if (isset($_POST['next'])) {
            // get sytem informations
            $phpVersion = $objCommon->getPHPVersion();
            $mysqlSupport = $objCommon->checkMySQLSupport();
            $pdoSupport = $objCommon->checkPDOSupport();
            $gdVersion = $objCommon->checkGDSupport();

            if (!$objCommon->isWindows() && ini_get('safe_mode')) {
                $ftpSupport = $objCommon->checkFTPSupport();
            } else {
                $ftpSupport = true;
            }

            if ($objCommon->getWebserverSoftware() == 'iis') {
                $iisUrlRewriteModuleSupport = isset($_SERVER['IIS_UrlRewriteModule']);
            } else {
                $iisUrlRewriteModuleSupport = true;
            }

            if (($phpVersion >= $requiredPHPVersion || isset($_POST['ignore_php_requirement'])) && $mysqlSupport && $pdoSupport && ($gdVersion >= $requiredGDVersion) && $ftpSupport && $iisUrlRewriteModuleSupport) {
                $_SESSION['installer']['step']++;
            }
        }
    }

    /**
    * get requirements page
    *
    * show the requirements and if the system fits them
    *
    * @access   private
    * @global   object  $objCommon
    * @global   object  $objTpl
    * @global   array   $_ARRLANG
    * @global   double  $requiredGDVersion
    * @global   double  $requiredPHPVersion
    */
    function _getRequirementsPage() {
        global $objCommon, $objTpl, $_ARRLANG, $requiredPHPVersion, $requiredGDVersion, $_CONFIG;

        $this->arrStatusMsg['php'] = "";
        $this->arrStatusMsg['extensions'] = "";
        $this->arrStatusMsg['config'] = "";

        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', "requirements.html");

        // get sytem informations
        $phpVersion   = $objCommon->getPHPVersion();
        $mysqlSupport = $objCommon->checkMySQLSupport();
        $pdoSupport   = $objCommon->checkPDOSupport();
        $gdVersion    = $objCommon->checkGDSupport();
        $ftpSupport   = $objCommon->checkFTPSupport();
        $apcSupport   = $objCommon->enableApc();

        if ($apcSupport) {
            $memoryLimit = $objCommon->checkMemoryLimit(32);
        } else {
            $memoryLimit = $objCommon->checkMemoryLimit(48);
        }

        if ($phpVersion < $requiredPHPVersion) {
            $this->arrStatusMsg['php'] .= str_replace("[VERSION]", $requiredPHPVersion, $_ARRLANG['TXT_PHP_VERSION_REQUIRED']."<br /><br />".$_ARRLANG['TXT_IGNORE_PHP_REQUIREMENT']."<br />"
                ."<input type=\"checkbox\" name=\"ignore_php_requirement\" id=\"ignore_php_requirement\" value=\"1\" style=\"vertical-align:middle;border-color:#FFCCCC;background-color:#FFCCCC;margin-left:0px;padding-left:0px;\" />"
                ."<label for=\"ignore_php_requirement\">".$_ARRLANG['TXT_ACCEPT_NO_SLA']."</label>"
            );
        }
        if (!$mysqlSupport) {
            $this->arrStatusMsg['extensions'] .= $_ARRLANG['TXT_MYSQL_SUPPORT_REQUIRED']."<br />";
        }
        if (!$pdoSupport) {
            $this->arrStatusMsg['extensions'] .= $_ARRLANG['TXT_PDO_SUPPORT_REQUIRED']."<br />";
        }
        if ($gdVersion < $requiredGDVersion) {
            $this->arrStatusMsg['extensions'] .= str_replace("[VERSION]", $requiredGDVersion, $_ARRLANG['TXT_GD_VERSION_REQUIRED']."<br />");
        }

        if (!$objCommon->isWindows() && ini_get('safe_mode')) {
            if (!$ftpSupport) {
                $this->arrStatusMsg['extensions'] .= $_ARRLANG['TXT_FTP_SUPPORT_REQUIRED']."<br />";
            }
        }

        // set template variables
        $objTpl->setVariable(array(
            'PHP_REQUIRED_VERION'    => $_ARRLANG['TXT_PHP_VERSION'].' >= '.$requiredPHPVersion,
            'PHP_VERSION'            => $phpVersion,
            'PHP_VERSION_CLASS'      => $phpVersion >= $requiredPHPVersion ? 'successful' : 'failed',
            'MYSQL_SUPPORT'          => $mysqlSupport ? $_ARRLANG['TXT_YES'] : $_ARRLANG['TXT_NO'],
            'MYSQL_SUPPORT_CLASS'    => $mysqlSupport ? 'successful' : 'failed',
            'PDO_SUPPORT'            => $pdoSupport ? $_ARRLANG['TXT_YES'] : $_ARRLANG['TXT_NO'],
            'PDO_SUPPORT_CLASS'      => $pdoSupport ? 'successful' : 'failed',
            'GD_REQUIRED_VERSION'    => $_ARRLANG['TXT_GD_VERSION'].' >= '.$requiredGDVersion,
            'GD_VERSION'             => $gdVersion,
            'GD_VERSION_CLASS'       => $gdVersion >= $requiredGDVersion ? 'successful' : 'failed',
            'FTP_SUPPORT'            => $ftpSupport ? $_ARRLANG['TXT_YES'] : $_ARRLANG['TXT_NO'],
            'FTP_SUPPORT_CLASS'      => $ftpSupport ? 'successful' : 'failed',
            'APC_SUPPORT'            => $apcSupport ? $_ARRLANG['TXT_ON'] : $_ARRLANG['TXT_OFF'],
            'APC_SUPPORT_CLASS'      => $apcSupport ? 'successful' : 'warning',
            'TXT_MEMORY_LIMIT'       => $_ARRLANG['TXT_MEMORY_LIMIT'].' (>= '.$memoryLimit['required'].'M)',
            'MEMORY_LIMIT'           => $memoryLimit['available'].'M',
            'MEMORY_LIMIT_CLASS'     => $memoryLimit['result'] ? 'successful' : 'failed',
        ));

        if ($objCommon->getWebserverSoftware() == 'iis') {
            $iisUrlRewriteModuleSupport = isset($_SERVER['IIS_UrlRewriteModule']);
            $objTpl->setVariable(array(
                'TXT_IIS_URL_REWRITE_MODULE_SUPPORT'    => $_ARRLANG['TXT_IIS_URL_REWRITE_MODULE_SUPPORT'],
                'IIS_URL_REWRITE_MODULE_SUPPORT'        => $iisUrlRewriteModuleSupport ? $_ARRLANG['TXT_YES'] : $_ARRLANG['TXT_NO'],
                'IIS_URL_REWRITE_MODULE_CLASS'          => $iisUrlRewriteModuleSupport ? 'successful' : 'failed',
            ));
            $objTpl->parse('iis_url_rewrite_module');
        } else {
            $objTpl->hideBlock('iis_url_rewrite_module');
        }

        // set php error message
        if (!empty($this->arrStatusMsg['php'])) {
            $objTpl->setVariable(array(
                'PHP_ERROR_MSG' => $this->arrStatusMsg['php']
            ));
            $objTpl->parse('phpErrorMsg');
        } else {
            $objTpl->hideBlock('phpErrorMsg');
        }

        // set extensions error message
        if (!empty($this->arrStatusMsg['extensions'])) {
            $objTpl->setVariable(array(
                'EXTENSIONS_ERROR_MSG' => $this->arrStatusMsg['extensions']
            ));
            $objTpl->parse('extensionsErrorMsg');
        } else {
            $objTpl->hideBlock('extensionsErrorMsg');
        }
    }

    /**
    * check configuration
    *
    * check if the configuration is valid
    *
    * @access   private
    * @global   object  $objCommon
    * @global   array   $_ARRLANG
    */
    function _checkConfiguration() {
        global $objCommon, $_ARRLANG, $useUtf8;

        $statusGeneral = true;
        $statusDb = true;
        $statusFtp = true;

        $this->arrStatusMsg['general'] = "";
        $this->arrStatusMsg['database'] = "";
        $this->arrStatusMsg['ftp'] = "";
        $this->arrStatusMsg['ftpPath'] = "";

        // set configuration values set by the user
        if (isset($_POST['configuration'])) {
            if (isset($_POST['ftpPathConfig'])) {
                // set the ftp path
                if (isset($_POST['ftpPath']) && is_array($_POST['ftpPath'])) {
                    $ftpPath = urldecode($_POST['ftpPath'][count($_POST['ftpPath'])-1]);
                    preg_match("=(.*)".$_SESSION['installer']['config']['offsetPath']."=", $ftpPath, $arrMatches);
                    if (!empty($arrMatches[1])) {
                        $_SESSION['installer']['config']['ftpPath'] = $arrMatches[1];
                    } else {
                        $_SESSION['installer']['config']['ftpPath'] = $ftpPath;
                    }
                }
            } else {
                // check general configuration options
                if (!$_SESSION['installer']['config']['general'] && (!isset($_SESSION['installer']['setPermissions']) || !$_SESSION['installer']['setPermissions'])) {
                    // get options
                    if (get_magic_quotes_gpc()) {
                        $_POST['documentRoot'] = stripslashes($_POST['documentRoot']);
                        $_POST['offsetPath'] = stripslashes($_POST['offsetPath']);
                    }

                    $_POST['documentRoot']  = preg_replace("/"."\\".DIRECTORY_SEPARATOR."*$/", "", $_POST['documentRoot']);
                    $_POST['offsetPath']  = preg_replace("/"."\\".DIRECTORY_SEPARATOR."*$/", "", $_POST['offsetPath']);

                    if (!isset($_SESSION['installer']['config']['documentRoot']) || $_POST['documentRoot'] != $_SESSION['installer']['config']['documentRoot']
                    || !isset($_SESSION['installer']['config']['offsetPath']) || $_POST['offsetPath'] != $_SESSION['installer']['config']['offsetPath']
                    ) {
                        $_SESSION['installer']['config']['documentRoot'] = $_POST['documentRoot'];
                        $_SESSION['installer']['config']['offsetPath'] = $_POST['offsetPath'];
                    }

                    // check if the needed configuration values are set
                    if (!isset($_SESSION['installer']['config']['documentRoot']) || empty($_SESSION['installer']['config']['documentRoot'])) {
                        $this->arrStatusMsg['general'] .= $_ARRLANG['TXT_DOCUMENT_ROOT_NEEDED']."<br />";
                        $statusGeneral = false;
                    }
                }

                if (empty($_SESSION['installer']['config']['timezone']) || ($_POST['timezone'] != $_SESSION['installer']['config']['timezone'])) {
                    $_SESSION['installer']['config']['timezone'] = get_magic_quotes_gpc() ? stripslashes($_POST['timezone']) : $_POST['timezone'];
                }

                // check database configuration options
                if (!isset($_SESSION['installer']['checkDatabaseTables']) || !$_SESSION['installer']['checkDatabaseTables']) {
                    // get options
                    if (get_magic_quotes_gpc()) {
                        $_POST['dbHostname'] = stripslashes($_POST['dbHostname']);
                        $_POST['dbUsername'] = stripslashes($_POST['dbUsername']);
                        $_POST['dbPassword'] = stripslashes($_POST['dbPassword']);
                        $_POST['dbDatabaseName'] = stripslashes($_POST['dbDatabaseName']);
                        $_POST['dbTablePrefix'] = stripslashes($_POST['dbTablePrefix']);
                        if (isset($_POST['dbCollation'])) {
                            $_POST['dbCollation'] = stripslashes($_POST['dbCollation']);
                        }
                    }

                    if (!isset($_POST['createDatabase'])) {
                        $_POST['createDatabase'] = 0;
                    }

                    // check if there were any configuration changes made
                    if (!isset($_SESSION['installer']['config']['dbHostname']) || $_POST['dbHostname'] != $_SESSION['installer']['config']['dbHostname']
                        || !isset($_SESSION['installer']['config']['dbUsername']) || $_POST['dbUsername'] != $_SESSION['installer']['config']['dbUsername']
                        || !isset($_SESSION['installer']['config']['dbPassword']) || $_POST['dbPassword'] != $_SESSION['installer']['config']['dbPassword']
                        || !isset($_SESSION['installer']['config']['dbDatabaseName']) || $_POST['dbDatabaseName'] != $_SESSION['installer']['config']['dbDatabaseName']
                        || !isset($_SESSION['installer']['config']['dbTablePrefix']) || $_POST['dbTablePrefix'] != $_SESSION['installer']['config']['dbTablePrefix']
                        || !isset($_SESSION['installer']['config']['createDatabase']) || ((boolean) $_POST['createDatabase']) != $_SESSION['installer']['config']['createDatabase']
                        || ($useUtf8 && (!isset($_SESSION['installer']['config']['dbCollation']) || $_POST['dbCollation'] != $_SESSION['installer']['config']['dbCollation']))
                        ) {

                        $_SESSION['installer']['config']['dbHostname'] = $_POST['dbHostname'];
                        $_SESSION['installer']['config']['dbUsername'] = $_POST['dbUsername'];
                        $_SESSION['installer']['config']['dbPassword'] = $_POST['dbPassword'];
                        $_SESSION['installer']['config']['dbDatabaseName'] = trim($_POST['dbDatabaseName']);
                        $_SESSION['installer']['config']['dbTablePrefix'] = trim($_POST['dbTablePrefix']);
                        $_SESSION['installer']['config']['createDatabase'] = (boolean) $_POST['createDatabase'];
                        if (isset($_POST['dbCollation'])) {
                            $_SESSION['installer']['config']['dbCollation'] = $_POST['dbCollation'];
                        }
                    }

                    // check if the needed configuration values are set
                    if (!isset($_SESSION['installer']['config']['dbHostname']) || empty($_SESSION['installer']['config']['dbHostname'])) {
                        $this->arrStatusMsg['database'] .= $_ARRLANG['TXT_DB_HOSTNAME_NEEDED']."<br />";
                        $statusDb = false;
                    }
                    if (!isset($_SESSION['installer']['config']['dbUsername']) || empty($_SESSION['installer']['config']['dbUsername'])) {
                        $this->arrStatusMsg['database'] .= $_ARRLANG['TXT_DB_USERNAME_NEEDED']."<br />";
                        $statusDb = false;
                    }
                    if (!isset($_SESSION['installer']['config']['dbDatabaseName']) || empty($_SESSION['installer']['config']['dbDatabaseName'])) {
                        $this->arrStatusMsg['database'] .= $_ARRLANG['TXT_DB_DATABASE_NEEDED']."<br />";
                        $statusDb = false;
                    }
                    if (!isset($_SESSION['installer']['config']['dbTablePrefix']) || empty($_SESSION['installer']['config']['dbTablePrefix'])) {
                        $this->arrStatusMsg['database'] .= $_ARRLANG['TXT_DB_TABLE_PREFIX_NEEDED']."<br />";
                        $statusDb = false;
                    } elseif (!$objCommon->isValidDbPrefix($_SESSION['installer']['config']['dbTablePrefix'])) {
                        $this->arrStatusMsg['database'] .= $_ARRLANG['TXT_DB_TABLE_PREFIX_INVALID'].'<br />';
                        $statusDb = false;
                    }
                }

                // check ftp configuration options
                if (!isset($_SESSION['installer']['setPermissions']) || !$_SESSION['installer']['setPermissions']) {
                    // get ftp configuration options
                    if (get_magic_quotes_gpc()) {
                        $_POST['ftpHostname'] = stripslashes($_POST['ftpHostname']);
                        $_POST['ftpUsername'] = stripslashes($_POST['ftpUsername']);
                        $_POST['ftpPassword'] = stripslashes($_POST['ftpPassword']);
                    }
                    if ($_SESSION['installer']['config']['forceFtp']) {
                        $_POST['useFtp'] = 1;
                    } elseif (!isset($_POST['useFtp'])) {
                        $_POST['useFtp'] = 0;
                    }

                    $_POST['ftpHostname'] = preg_replace(
                        '/^\w*:\/\//', '', $_POST['ftpHostname']);
                    if ($ftpPortPos = intval(strpos($_POST['ftpHostname'], ":"))) {
                        if (($ftpPort = intval(substr($_POST['ftpHostname'],$ftpPortPos+1))) != 0) {
                            $_SESSION['installer']['config']['ftpPort'] = $ftpPort;
                        } else {
                            unset($_SESSION['installer']['config']['ftpPort']);
                        }
                        $_POST['ftpHostname'] = substr($_POST['ftpHostname'],0,$ftpPortPos);
                    } elseif (isset($_SESSION['installer']['config']['ftpPort'])) {
                        unset($_SESSION['installer']['config']['ftpPort']);
                    }

                    // check if there were any configuration changes made
                    if (($_POST['useFtp'] != $_SESSION['installer']['config']['useFtp'])
                        || ($_SESSION['installer']['config']['useFtp']
                        && (!isset($_SESSION['installer']['config']['ftpHostname']) || $_POST['ftpHostname'] != $_SESSION['installer']['config']['ftpHostname']
                        || !isset($_SESSION['installer']['config']['ftpUsername']) || $_POST['ftpUsername'] != $_SESSION['installer']['config']['ftpUsername']
                        || !isset($_SESSION['installer']['config']['ftpPassword']) || $_POST['ftpPassword'] != $_SESSION['installer']['config']['ftpPassword']
                        )))
                    {
                        $_SESSION['installer']['config']['useFtp'] = (boolean) $_POST['useFtp'];
                        if ($_SESSION['installer']['config']['useFtp']) {
                            $_SESSION['installer']['config']['ftpHostname'] = $_POST['ftpHostname'];
                            $_SESSION['installer']['config']['ftpUsername'] = $_POST['ftpUsername'];
                            $_SESSION['installer']['config']['ftpPassword'] = $_POST['ftpPassword'];
                            if (!isset($_SESSION['installer']['config']['ftpPath'])) {
                                $_SESSION['installer']['config']['ftpPath'] = "";
                            }
                        }
                    }

                    // check if the needed configuration values are set
                    if ($_SESSION['installer']['config']['useFtp']
                        && (!isset($_SESSION['installer']['config']['ftpHostname']) || empty($_SESSION['installer']['config']['ftpHostname']))
                        )
                    {
                        $this->arrStatusMsg['ftp'] .= $_ARRLANG['TXT_FTP_HOSTNAME_NEEDED']."<br />";
                        $statusFtp = false;
                    }
                    if ($_SESSION['installer']['config']['useFtp']
                        && (!isset($_SESSION['installer']['config']['ftpUsername']) || empty($_SESSION['installer']['config']['ftpUsername']))
                        )
                    {
                        $this->arrStatusMsg['ftp'] .= $_ARRLANG['TXT_FTP_USERNAME_NEEDED']."<br />";
                        $statusFtp = false;
                    }
                }
            }

            // check general configuration
            if ($statusGeneral) {
                $this->_checkConfigGeneral();
                $_SESSION['installer']['config']['general'] = $this->configGeneral;
            }

            // check timezone selection
            if (isset($_SESSION['installer']['config']['timezone']) && array_key_exists($_SESSION['installer']['config']['timezone'], timezone_identifiers_list())) {
                $this->configTimezone = true;
            } else {
                $this->configTimezone = false;
                $this->arrStatusMsg['general'] .= $_ARRLANG['TXT_INVALID_TIMEZONE'].'<br />';
            }

            // check database configuration
            if ($statusDb) {
                $this->_checkConfigDatabase();
            }

            // check ftp configuration
            if ($statusFtp) {
                $this->_checkConfigFTP();
            }

            if (isset($_POST['cachingConfig']) && empty($_POST['cachingConfigDefault'])) {
                $_SESSION['installer']['config']['cachingByDefault'] = false;
            } else {
                $_SESSION['installer']['config']['cachingByDefault'] = true;
            }
            if (!isset($_POST['ftpPathConfig']) && $this->configGeneral && $this->configTimezone && $this->configDb && $this->configFtp) {
                $_SESSION['installer']['config']['status'] = true;
                $_SESSION['installer']['step']++;
            } else {
                $_SESSION['installer']['config']['status'] = false;
            }
        }
    }

    /**
    * check general configuration
    *
    *
    *
    * @access   private
    * @global   object  $objCommon
    */
    function _checkConfigGeneral() {
        global $objCommon;

        if (isset($_SESSION['installer']['setPermissions']) && $_SESSION['installer']['setPermissions']) {
            $this->configGeneral = true;
        } else {
            if (isset($_SESSION['installer']['config']['documentRoot'])    && isset($_SESSION['installer']['config']['offsetPath'])) {
                $result = $objCommon->checkCMSPath();
                if ($result !== true) {
                    $this->arrStatusMsg['general'] .= $result;
                    $this->configGeneral = false;
                } else {
                    $this->configGeneral = true;
                }
            } else {
                $this->configGeneral = false;
            }
        }
    }

    /**
    * check database configuration
    *
    * check if the database configuration is useable
    *
    * @access   private
    * @global   object  $objCommon
    * @global   array   $_ARRLANG
    */
    function _checkConfigDatabase() {
        global $objCommon, $_ARRLANG, $useUtf8;

        if (isset($_SESSION['installer']['createDatabaseTables']) && $_SESSION['installer']['createDatabaseTables']) {
            $this->configDb = true;
        } else {
            if (!isset($_SESSION['installer']['config']['dbHostname'])
                || !isset($_SESSION['installer']['config']['dbUsername'])
                || !isset($_SESSION['installer']['config']['dbPassword'])
                || !isset($_SESSION['installer']['config']['dbDatabaseName'])
                || !isset($_SESSION['installer']['config']['dbTablePrefix'])
                || $useUtf8 && !isset($_SESSION['installer']['config']['dbCollation']))
            {
                $this->configDb = false;
            } else {
                $host = $_SESSION['installer']['config']['dbHostname'];
                $username = $_SESSION['installer']['config']['dbUsername'];
                $password = $_SESSION['installer']['config']['dbPassword'];
                $db = $_SESSION['installer']['config']['dbDatabaseName'];

                // test database connection
                $result = $objCommon->checkDbConnection($host, $username, $password);
                if ($result !== true) {
                    $this->arrStatusMsg['database'] .= $result;
                    $this->configDb = false;
                } else {
                    if ($_SESSION['installer']['config']['createDatabase']) {
                        $result = $objCommon->existDatabase($host, $username, $password, $db);
                        if ($result) {
                            $this->arrStatusMsg['database'] .= str_replace("[DATABASE]", $db, $_ARRLANG['TXT_DATABASE_ALREADY_EXISTS']."<br />");
                            $this->configDb = false;
                        } else {
                            $this->configDb = true;
                        }
                    } else {
                        $result = $objCommon->existDatabase($host, $username, $password, $db);
                        if (!$result) {
                            $this->arrStatusMsg['database'] .= str_replace("[DATABASE]", $db, $_ARRLANG['TXT_DATABASE_DOES_NOT_EXISTS']."<br />");
                            $this->configDb = false;
                        } else {
                            $this->configDb = true;
                        }
                    }
                }
            }
        }
    }

    /**
    * check ftp configuration
    *
    * check if it can establish an connection to the ftp server and if it can find the
    * cms on the ftp server
    *
    * @access   private
    * @global   object  $objCommon
    */
    function _checkConfigFTP() {
        global $objCommon;

        if (isset($_SESSION['installer']['setPermissions']) && $_SESSION['installer']['setPermissions']) {
            $this->configFtp = true;
        } else {
            if ($_SESSION['installer']['config']['useFtp']) {
                // test ftp connection
                $result = $objCommon->checkFTPConnection();
                if ($result !== true) {
                    $this->arrStatusMsg['ftp'] .= $result;
                    $this->configFtp = false;
                } else {
                    $result = $objCommon->checkFtpPath();
                    if ($result !== true) {
                        $_SESSION['installer']['config']['setFtpPath'] = true;
                        if (isset($_POST['ftpPathConfig'])) {
                            $this->arrStatusMsg['ftpPath'] .= $result;
                        }
                        $this->configFtp = false;
                    } else {
                        $_SESSION['installer']['config']['setFtpPath'] = false;
                        $this->configFtp = true;
                    }
                }
            } else {
                $this->configFtp = true;
            }
        }
    }


    function _createDirectoryTree($id) {
        global $objTpl, $templatePath;

        if (isset($this->arrDirectoryTree[$id]) && is_array($this->arrDirectoryTree[$id])) {
            foreach ($this->arrDirectoryTree[$id] as $arrDirectory) {
                $selected = false;

                if (isset($this->arrDirectoryPaths[$id+1]) && ($arrDirectory['name'] == $this->arrDirectoryPaths[$id+1])) {
                    $selected = true;
                }

                $inputId = str_replace(" ", "_", $arrDirectory['name']);

                $objTpl->setVariable(array(
                    'FTP_DIRECTORY_ROW_CLASS'   => $this->directoryTreeRowClassNr % 2 ? "row2" : "row1",
                    'FTP_DIRECTORY'             => "<a class=\"".($selected ? "text-selected" : "text")."\" href=\"index.php?ftpPath=".urlencode($arrDirectory['path'].$arrDirectory['name'])."\"><input onclick=\"javascript:location.href='index.php?ftpPath=".urlencode($arrDirectory['path'].$arrDirectory['name'])."'\" type=\"radio\" name=\"ftpPath[".$id."]\" id=\"".urlencode($inputId.$id)."\" value=\"".urlencode($arrDirectory['path'].$arrDirectory['name'])."\" ".($selected ? "checked=\"checked\"" : "")." /><label for=\"".urlencode($inputId.$id)."\"><img src=\"".$templatePath."images/icons/".($selected ? "folder_closed.gif" : "folder_closed.gif")."\" width=\"16\" height=\"16\" border=\"0\" />&nbsp;".$arrDirectory['name']."</label></a>",
                    'FTP_DIRECTORY_STYLE'       => $arrDirectory['style']
                ));
                $objTpl->parse('ftpDirectory');

                $this->directoryTreeRowClassNr++;

                if ($selected) {
                    if (count($this->arrDirectoryPaths)>$id) {
                        $newId = $id + 1;
                        $this->_createDirectoryTree($newId);
                    }
                }
            }
        }
    }

    /**
    * get configuration page
    *
    * show the configuration for the installation
    *
    * @access   private
    * @global   object  $objTpl
    * @global   array   $_ARRLANG
    * @global   object  $objCommon
    */
    function _getConfigurationPage() {
        global $objTpl, $_ARRLANG, $objCommon, $templatePath, $arrDefaultConfig, $_CONFIG, $useUtf8;

        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
            $protocol = "http://";
        } else {
            $protocol = "https://";
        }
        $serverName = $protocol.$_SERVER['SERVER_NAME'];

        $offsetPath = "";
        $documentRoot = "";

        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', "configuration.html");

        if (isset($_SESSION['installer']['config']['setFtpPath']) && $_SESSION['installer']['config']['setFtpPath']) {
            if (isset($_GET['ftpPath'])) {
                $path = urldecode($_GET['ftpPath']);
            } elseif (isset($_SESSION['installer']['config']['ftpPath']) && !empty($_SESSION['installer']['config']['ftpPath'])) {
                $path = $_SESSION['installer']['config']['ftpPath'];
            } else {
                $path = "";
            }

            $this->arrDirectoryTree = $objCommon->getFtpDirectoryTree($path);
            if (is_array($this->arrDirectoryTree)) {
                $this->arrDirectoryPaths = explode("/", $path);
                $this->_createDirectoryTree(0);
            } elseif (($result = $objCommon->_checkOpenbaseDirConfig()) !== true) {
                $_SESSION['installer']['config']['setFtpPath'] = false;
                $_SESSION['installer']['config']['useFtp'] = false;
                $this->arrStatusMsg['ftpPath'] = $result;
            } else {
                $this->arrStatusMsg['ftpPath'] = $this->arrDirectoryTree;
            }

            if (!empty($this->arrStatusMsg['ftpPath'])) {
                $objTpl->setVariable(array(
                    'FTP_PATH_ERROR_MSG' => $this->arrStatusMsg['ftpPath']
                ));
                $objTpl->parse('ftpPathErrorMsg');

                if ($objCommon->_checkOpenbaseDirConfig() !== true) {
                    $objTpl->hideBlock('ftpDirectoryTree');
                }
            } else {
                $objTpl->hideBlock('ftpPathErrorMsg');
                $objTpl->parse('ftpDirectoryTree');
            }

            $objTpl->parse('ftpPathConfig');
            $objTpl->hideBlock('general');
            $objTpl->hideBlock('database');
            $objTpl->hideBlock('ftp');
            $objTpl->hideBlock('caching');
        } else {
            // get offset path
            if (isset($_SESSION['installer']['config']['offsetPath'])) {
                $offsetPath = $_SESSION['installer']['config']['offsetPath'];
            } else {
                $arrDirectories = explode('/', $_SERVER['SCRIPT_NAME']);
                for ($i = 0;$i < count($arrDirectories)-2;$i++) {
                    if ($arrDirectories[$i] !== '') {
                        $offsetPath .= '/'.$arrDirectories[$i];
                    }
                }
                $_SESSION['installer']['config']['offsetPath'] = $offsetPath;
            }

            // get document root path
            if (isset($_SESSION['installer']['config']['documentRoot'])) {
                $documentRoot = $_SESSION['installer']['config']['documentRoot'];
            } else {
                $scriptPath = str_replace('\\', '/', __FILE__);
                if (preg_match("/(.*)(?:\/[\d\D]*){2}$/", $scriptPath, $arrMatches) == 1) {
                    $scriptPath = $arrMatches[1];
                }

                if (preg_match("#(.*)".preg_replace(array('#\\\#', '#\^#', '#\$#', '#\.#', '#\[#', '#\]#', '#\|#', '#\(#', '#\)#', '#\?#', '#\*#', '#\+#', '#\{#', '#\}#'), '\\\\$0', $offsetPath)."#", $scriptPath, $arrMatches) == 1) {
                    $documentRoot = $arrMatches[1];
                }
                $_SESSION['installer']['config']['documentRoot'] = $documentRoot;
            }

            if (!isset($_SESSION['installer']['config']['general'])) {
                $_SESSION['installer']['config']['general'] = true;
            }

            if (!$_SESSION['installer']['config']['general'] && (!isset($_SESSION['installer']['setPermissions']) || !$_SESSION['installer']['setPermissions'])) {
                $documentRoot = "<input type=\"text\" name=\"documentRoot\" value=\"".$documentRoot."\" tabindex=\"".$this->_getTabIndex()."\" />&nbsp;<span class=\"icon-info tooltip-trigger\"></span><span class=\"tooltip-message\">".$_ARRLANG['TXT_DOCUMENT_ROOT_DESCRIPTION']."</span>";
                $offsetPath = "<input class=\"textBox\" name=\"offsetPath\" value=\"".$offsetPath."\" tabindex=\"".$this->_getTabIndex()."\" />&nbsp;<span class=\"icon-info tooltip-trigger\"></span><span class=\"tooltip-message\">".$_ARRLANG['OFFSET_PATH_DESCRIPTION']."</span>";
                $objTpl->setVariable('OFFSET_PATH_DESCRIPTION', str_replace("[NAME]", $_CONFIG['coreCmsName'], $_ARRLANG['TXT_OFFSET_PATH_DESCRIPTION']));
            }

            if (isset($_SESSION['installer']['checkDatabaseTables']) && $_SESSION['installer']['checkDatabaseTables']) {
                $dbHostname = $_SESSION['installer']['config']['dbHostname'];
                $dbCreateDb = $_SESSION['installer']['config']['createDatabase'] ? "&nbsp;(".$_ARRLANG['TXT_CREATE_DATABASE'].")" : "";
                $dbUsername = $_SESSION['installer']['config']['dbUsername'];
                $dbPassword = sprintf("%'*".strlen($_SESSION['installer']['config']['dbPassword'])."s","");
                $dbDatabaseName = $_SESSION['installer']['config']['dbDatabaseName'];
                $dbTablePrefix = $_SESSION['installer']['config']['dbTablePrefix'];
                $dbCollation = !empty($_SESSION['installer']['config']['dbCollation']) ? $_SESSION['installer']['config']['dbCollation'] : '';

                $timezones = timezone_identifiers_list();
                $timezone = $timezones[$_SESSION['installer']['config']['timezone']];
                $dateTimeZone = new DateTimeZone($timezone);
                $dateTime     = new DateTime('now', $dateTimeZone);
                $timeOffset   = $dateTimeZone->getOffset($dateTime);
                $plusOrMinus  = $timeOffset < 0 ? '-' : '+';
                $gmt          = 'GMT ' . $plusOrMinus . ' ' . gmdate('g:i', $timeOffset);
                $timezone .= ' (' . $gmt . ')<input type="hidden" name="timezone" value="' . $_SESSION['installer']['config']['timezone'] . '" />';
            } else {
                if (!isset($_SESSION['installer']['config']['createDatabase'])) {
                    $_SESSION['installer']['config']['createDatabase'] = false;
                }

                $dbHostname = "<input type=\"text\" name=\"dbHostname\" value=\"".(isset($_SESSION['installer']['config']['dbHostname']) ? $_SESSION['installer']['config']['dbHostname'] : $arrDefaultConfig['dbHostname'])."\" tabindex=\"".$this->_getTabIndex()."\" />";
                $dbDatabaseName = "<input type=\"text\" name=\"dbDatabaseName\" value=\"".(isset($_SESSION['installer']['config']['dbDatabaseName']) ? $_SESSION['installer']['config']['dbDatabaseName'] : $arrDefaultConfig['dbDatabaseName'])."\" tabindex=\"".$this->_getTabIndex()."\" />";
                $dbCreateDb = "&nbsp;<input type=\"checkbox\" name=\"createDatabase\" id=\"createDatabase\" value=\"1\"".($_SESSION['installer']['config']['createDatabase'] ? "checked=\"checked\"" : "")."\" tabindex=\"".$this->_getTabIndex()."\" />&nbsp;<label for=\"createDatabase\">".$_ARRLANG['TXT_CREATE_DATABASE']."</label>";
                $dbUsername = "<input type=\"text\" name=\"dbUsername\" value=\"".(isset($_SESSION['installer']['config']['dbUsername']) ? $_SESSION['installer']['config']['dbUsername'] : $arrDefaultConfig['dbUsername'])."\" tabindex=\"".$this->_getTabIndex()."\" />";
                $dbPassword = "<input type=\"password\" name=\"dbPassword\" value=\"".(isset($_SESSION['installer']['config']['dbPassword']) ? $_SESSION['installer']['config']['dbPassword'] : $arrDefaultConfig['dbPassword'])."\" tabindex=\"".$this->_getTabIndex()."\" />";
                $dbTablePrefix = "<input type=\"text\" name=\"dbTablePrefix\" value=\"".(isset($_SESSION['installer']['config']['dbTablePrefix']) ? $_SESSION['installer']['config']['dbTablePrefix'] : $arrDefaultConfig['dbTablePrefix'])."\" tabindex=\"".$this->_getTabIndex()."\" />&nbsp;<span class=\"icon-info tooltip-trigger\"></span><span class=\"tooltip-message\">".$_ARRLANG['TXT_DB_TABLE_PREFIX_INVALID']."</span>";
                $timezone = '<select name="timezone">'.$objCommon->getTimezoneOptions().'</select>';

                if ($useUtf8 && !empty($_SESSION['installer']['config']['dbHostname']) && !empty($_SESSION['installer']['config']['dbUsername'])) {
                    if (($result = $objCommon->checkDbConnection($_SESSION['installer']['config']['dbHostname'], $_SESSION['installer']['config']['dbUsername'], $_SESSION['installer']['config']['dbPassword'])) === true) {
                        $mysqlServerVersion = $objCommon->getMySQLServerVersion();
                        if ($mysqlServerVersion && !$objCommon->_isNewerVersion($mysqlServerVersion, '4.1') && ($arrCollate = $objCommon->_getUtf8Collations()) !== false && count($arrCollate)) {
                            $selectedCollation = !empty($_SESSION['installer']['config']['dbCollation']) ? $_SESSION['installer']['config']['dbCollation'] : 'utf8_unicode_ci';
                            $dbCollation = '<select name="dbCollation">';
                            foreach ($arrCollate as $collate) {
                                $dbCollation .= '<option value="'.$collate.($collate == $selectedCollation ? '" selected="selected' : '').'">'.$collate.'</option>';
                            }
                            $dbCollation .= '</select>';
                            $dbCollation .= '&nbsp;<span class="icon-info tooltip-trigger"></span><span class="tooltip-message">'.$_ARRLANG['TXT_DB_COLLATION_DESCRIPTION'].'</span>';
                        } else {
                            $this->arrStatusMsg['database'] = $_ARRLANG['TXT_NO_DB_UTF8_SUPPORT_MSG'];
                        }
                    } else {
                        $this->arrStatusMsg['database'] = $result;
                    }
                }
            }

            // set general and database configuration options
            $objTpl->setVariable(array(
                'DOCUMENT_ROOT'     => $documentRoot,
                'OFFSET_PATH'       => $serverName.(empty($offsetPath) ? "&nbsp;" : $offsetPath),
                'TIMEZONE_OPTIONS'  => $timezone,
                'DB_HOSTNAME'       => $dbHostname,
                'CREATE_DB'         => $dbCreateDb,
                'DB_USERNAME'       => (empty($dbUsername) ? "&nbsp;" : $dbUsername),
                'DB_PASSWORD'       => (empty($dbPassword) ? "&nbsp;" : $dbPassword),
                'DB_DATABASE_NAME'  => $dbDatabaseName,
                'DB_TABLE_PREFIX'   => (empty($dbTablePrefix) ? "&nbsp;" : $dbTablePrefix),
            ));

            if ($useUtf8
             && isset ($_SESSION['installer']['config']['dbHostname'])
             && isset ($_SESSION['installer']['config']['dbUsername'])
             && isset ($_SESSION['installer']['config']['dbPassword'])
             && $objCommon->checkDbConnection(
                $_SESSION['installer']['config']['dbHostname'],
                $_SESSION['installer']['config']['dbUsername'],
                $_SESSION['installer']['config']['dbPassword']) === true) {
                $objTpl->setVariable('DB_CONNECTION_COLLATION', $dbCollation);
                $objTpl->parse('database_collation');
            } else {
                $objTpl->hideBlock('database_collation');
            }

            // set general error message
            if (isset($this->arrStatusMsg['general']) && !empty($this->arrStatusMsg['general'])) {
                $objTpl->setVariable(array(
                    'GENERAL_ERROR_MSG'    => $this->arrStatusMsg['general']
                ));
                $objTpl->parse('generalErrorMsg');
            } else {
                $objTpl->hideBlock('generalErrorMsg');
            }

            // set database error message
            if (isset($this->arrStatusMsg['database']) && !empty($this->arrStatusMsg['database'])) {
                $objTpl->setVariable(array(
                    'DATABASE_ERROR_MSG'    => $this->arrStatusMsg['database']
                ));
                $objTpl->parse('databaseErrorMsg');
            } else {
                $objTpl->hideBlock('databaseErrorMsg');
            }

            // set ftp configuration options
            if ($objCommon->checkFTPSupport()) {
                if (!isset($_SESSION['installer']['config']['useFtp'])) {
                    if (!$objCommon->isWindows()) {
                        $_SESSION['installer']['config']['useFtp'] = true;
                    } else {
                        $_SESSION['installer']['config']['useFtp'] = false;
                    }
                }

                if (!$objCommon->isWindows() && ini_get('safe_mode') && $objCommon->_checkOpenbaseDirConfig) {
                    $_SESSION['installer']['config']['forceFtp'] = true;
                } else {
                    $_SESSION['installer']['config']['forceFtp'] = false;
                }

                if (isset($_SESSION['installer']['setPermissions']) && $_SESSION['installer']['setPermissions']) {
                    if ($_SESSION['installer']['config']['useFtp']) {
                        $ftpHostname = $_SESSION['installer']['config']['ftpHostname'].(isset($_SESSION['installer']['config']['ftpPort']) ? ":".$_SESSION['installer']['config']['ftpPort'] : "");
                        $ftpUsername = $_SESSION['installer']['config']['ftpUsername'];
                        $ftpPassword = sprintf("%'*".strlen($_SESSION['installer']['config']['ftpPassword'])."s","");
                        $ftpPath = $_SESSION['installer']['config']['ftpPath'];

                        $objTpl->setVariable(array(
                            'FTP_HOSTNAME'  => $ftpHostname,
                            'FTP_USERNAME'  => (empty($ftpUsername) ? "&nbsp;" : $ftpUsername),
                            'FTP_PASSWORD'  => (empty($ftpPassword) ? "&nbsp;" : $ftpPassword),
                            'FTP_PATH'      => (empty($ftpPath) ? "&nbsp;" : $ftpPath),
                        ));

                        $objTpl->parse('ftpPath');
                    } else {
                        $objTpl->hideBlock('ftp');
                    }
                } else {
                    $useFtp = "<label for=\"useFtp\">".$_ARRLANG['TXT_USE_FTP']."</label>&nbsp;<input type=\"checkbox\" name=\"useFtp\" id=\"useFtp\" value=\"1\" ".($_SESSION['installer']['config']['useFtp'] ? "checked=\"checked\"" : "")." ".($_SESSION['installer']['config']['forceFtp'] ? "disabled=\"disabled\"" : "")." tabindex=\"".$this->_getTabIndex()."\" />&nbsp;<span class=\"icon-info tooltip-trigger\"></span><span class=\"tooltip-message\">".$_ARRLANG['TXT_FTP_DESCRIPTION']."</span>";
                    $ftpHostname = "<input type=\"text\" name=\"ftpHostname\" value=\"".(isset($_SESSION['installer']['config']['ftpHostname']) ? $_SESSION['installer']['config']['ftpHostname'] : $arrDefaultConfig['ftpHostname']).(isset($_SESSION['installer']['config']['ftpPort']) ? ":".$_SESSION['installer']['config']['ftpPort'] : "")."\" tabindex=\"".$this->_getTabIndex()."\" />";
                    $ftpUsername = "<input type=\"text\" name=\"ftpUsername\" value=\"".(isset($_SESSION['installer']['config']['ftpUsername']) ? $_SESSION['installer']['config']['ftpUsername'] : $arrDefaultConfig['ftpUsername'])."\" tabindex=\"".$this->_getTabIndex()."\" />";
                    $ftpPassword = "<input type=\"password\" name=\"ftpPassword\" value=\"".(isset($_SESSION['installer']['config']['ftpPassword']) ? $_SESSION['installer']['config']['ftpPassword'] : $arrDefaultConfig['ftpPassword'])."\" tabindex=\"".$this->_getTabIndex()."\" />";

                    $objTpl->setVariable(array(
                        'USE_FTP'       => $useFtp,
                        'FTP_HOSTNAME'  => $ftpHostname,
                        'FTP_USERNAME'  => $ftpUsername,
                        'FTP_PASSWORD'  => $ftpPassword
                    ));

                    if (isset($_SESSION['installer']['config']['setFtpPath']) && $_SESSION['installer']['config']['setFtpPath'] === false) {
                        $objTpl->setVariable(array(
                            'FTP_PATH' => $_SESSION['installer']['config']['ftpPath']
                        ));
                        $objTpl->parse('ftpPath');
                    } else {
                        $objTpl->hideBlock('ftpPath');
                    }

                    // set ftp error message
                    if (isset($this->arrStatusMsg['ftp']) && !empty($this->arrStatusMsg['ftp'])) {
                        $objTpl->setVariable(array(
                            'FTP_ERROR_MSG' => $this->arrStatusMsg['ftp']
                        ));
                        $objTpl->parse('ftpErrorMsg');
                    } else {
                        $objTpl->hideBlock('ftpErrorMsg');
                    }
                }

            } else {
                $objTpl->hideBlock('ftp');
            }
            $objTpl->hideBlock('ftpPathConfig');

            // caching configuration
            $objTpl->setVariable(array(
                'TXT_CACHING' => $_ARRLANG['TXT_CACHING'],
                'TXT_CACHING_ACTIVATE_BY_DEFAULT' => $_ARRLANG['TXT_CACHING_ACTIVATE_BY_DEFAULT'],
                'CACHING_CHECKED' =>
                    !isset($_SESSION['installer']['config']['cachingByDefault']) ||
                    $_SESSION['installer']['config']['cachingByDefault'] ? 'checked="checked"' : '',
            ));
            $objTpl->parse('caching');
        }
    }

    function _getTabIndex() {
        $tabIndex = $this->tabIndex;
        $this->tabIndex++;

        return $tabIndex;
    }

    /**
    * check installation
    *
    * go to the next step if the permissions were set, the database was created, the database tables were created and the config and version file were written
    *
    * @access   private
    */
    function _checkInstallation() {
        if (isset($_POST['next'])) {
            if (isset($_SESSION['installer']['setPermissions']) && $_SESSION['installer']['setPermissions'] == true
                && (!$_SESSION['installer']['config']['createDatabase'] || ($_SESSION['installer']['config']['createDatabase'] && (isset($_SESSION['installer']['createDatabase']) && $_SESSION['installer']['createDatabase'])))
                && isset($_SESSION['installer']['createDatabaseTables']) && $_SESSION['installer']['createDatabaseTables']
                && isset($_SESSION['installer']['checkDatabaseTables']) && $_SESSION['installer']['checkDatabaseTables']
                && isset($_SESSION['installer']['insertDatabaseData']) && $_SESSION['installer']['insertDatabaseData'])
            {
                $_SESSION['installer']['step']++;
            }
        }
    }

    /**
    * get installation page
    *
    * show the installation processes
    *
    * @access   private
    * @global   object  $objCommon
    * @global   object  $objTpl
    * @global   array   $_ARRLANG
    * @see _setPermissions(), _createDatabase(), _createDatabaseTables(), _checkDatabaseTables(), _insertDatabaseData(), _setInstallationStatus()
    */
    function _getInstallationPage() {
        global $objCommon, $_ARRLANG, $objTpl, $useUtf8;

        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', "installation.html");

        // set permissions
        $result = $this->_setPermissions();
        $this->_setInstallationStatus($result, $_ARRLANG['TXT_SET_PERMISSIONS']);

        // create database
        if ($result === true && isset($_SESSION['installer']['config']['createDatabase']) && $_SESSION['installer']['config']['createDatabase'] == true) {
            $result = $this->_createDatabase();
            $this->_setInstallationStatus($result, $_ARRLANG['TXT_CREATE_DATABASE']);
        } elseif ($result === true && $useUtf8) {
            $result = $this->_alterDatabase();
            $this->_setInstallationStatus($result, $_ARRLANG['TXT_CONFIG_DATABASE']);
        }

        $databaseAlreadyCreated = isset($_SESSION['installer']['checkDatabaseTables']) && $_SESSION['installer']['checkDatabaseTables'];

        // create database tables
        if ($result === true) {
            $result = $this->_createDatabaseTables();
            $this->_setInstallationStatus($result, $_ARRLANG['TXT_CREATE_DATABASE_TABLES']);
        }

        // check database structure
        if ($result === true) {
            $result = $this->_checkDatabaseTables();
            $this->_setInstallationStatus($result, $_ARRLANG['TXT_CHECK_DATABASE_TABLES']);
        }

        if (!$databaseAlreadyCreated && $result === true) {
            // make a break after the database has been created and show an alert box message
            $objTpl->setVariable('MESSAGE', $_ARRLANG['TXT_DATABASE_CREATION_COMPLETE']);
            $objTpl->parse('installer_alert_box');
            return;
        }

        // insert database data
        if ($result === true) {
            $result = $this->_insertDatabaseData();
            $this->_setInstallationStatus($result, $_ARRLANG['TXT_INSERT_DATABASE_DATA']);
        }

        // create htaccess file
        if ($result === true) {
            $result = $this->_createHtaccessFile();
            $msg = $objCommon->getWebserverSoftware() == 'iis' ? $_ARRLANG['TXT_CREATE_IIS_HTACCESS_FILE'] : $_ARRLANG['TXT_CREATE_APACHE_HTACCESS_FILE'];
            $this->_setInstallationStatus($result, $msg);
        }
    }

    /**
    * set permissions
    *
    * set write permissions to files
    *
    * @access   private
    * @global   object  $objCommon
    * @global   array   $arrFiles
    * @return   mixed   true on success, error message on failure
    */
    function _setPermissions() {
        global $objCommon, $arrFiles;

        if (isset($_SESSION['installer']['setPermissions']) && $_SESSION['installer']['setPermissions']) {
            return true;
        } else {
            $result = $objCommon->checkPermissions($arrFiles);
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['setPermissions'] = true;
                return true;
            }
        }
    }

    /**
    * create database
    *
    * create the database
    *
    * @access   private
    * @global   object  $objCommon
    * @return   mixed   true on success, error message on failure
    */
    function _createDatabase() {
        global $objCommon;

        if (isset($_SESSION['installer']['createDatabase']) && $_SESSION['installer']['createDatabase']) {
            return true;
        } else {
            $result = $objCommon->createDatabase();
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['createDatabase'] = true;
                return true;
            }
        }
    }

    function _alterDatabase() {
        global $objCommon;

        if (isset($_SESSION['installer']['alterDatabase']) && $_SESSION['installer']['alterDatabase']) {
            return true;
        } else {
            $result = $objCommon->setDatabaseCharset();
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['alterDatabase'] = true;
                return true;
            }
        }
    }

    /**
    * create database tables
    *
    * create the database tables
    *
    * @access   private
    * @global   object  $objCommon
    * @return   mixed   true on success, error message on failure
    */
    function _createDatabaseTables() {
        global $objCommon;

        if (isset($_SESSION['installer']['createDatabaseTables']) && $_SESSION['installer']['createDatabaseTables']) {
            return true;
        } else {
            $result = $objCommon->createDatabaseTables();
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['createDatabaseTables'] = true;
                return true;
            }
        }
    }

    /**
    * check database tables
    *
    * check if all database tables were created
    *
    * @access   private
    * @global   object  $objCommon
    * @return   mixed   true on success, error message on failure
    */
    function _checkDatabaseTables() {
        global $objCommon;

        if (isset($_SESSION['installer']['checkDatabaseTables']) && $_SESSION['installer']['checkDatabaseTables']) {
            return true;
        } else {
            $result = $objCommon->checkDatabaseTables();
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['checkDatabaseTables'] = true;
                return true;
            }
        }
    }

    /**
    * insert data into database
    *
    * insert standard/demo data into the database
    *
    * @access   private
    * @global   object  $objCommon
    * @return   mixed   true on success, error message on failure
    */
    function _insertDatabaseData() {
        global $objCommon;

        if (isset($_SESSION['installer']['insertDatabaseData']) && $_SESSION['installer']['insertDatabaseData']) {
            return true;
        } else {
            $result = $objCommon->insertDatabaseData();
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['insertDatabaseData'] = true;
                return true;
            }
        }
    }

        /**
         * create htaccess file
         *
         * @access      private
         * @global      object  $objCommon
         * @return      mixed   true on success, error message on failure
         */
        function _createHtaccessFile() {
            global $objCommon;

            if (isset($_SESSION['installer']['createHtaccessFile']) && $_SESSION['installer']['createHtaccessFile']) {
                return true;
            } else {
                $result = $objCommon->createHtaccessFile();
                if ($result !== true) {
                    return $result;
                } else {
                    $_SESSION['installer']['createHtaccessFile'] = true;
                    return true;
                }
            }
        }

    /**
    * create config file
    *
    * create the config file
    *
    * @access   private
    * @global   object  $objCommon
    * @return   mixed   true on success, error message on failure
    */
    function _createConfigFile() {
        global $objCommon;

        if (isset($_SESSION['installer']['createConfigFile']) && $_SESSION['installer']['createConfigFile']) {
            return true;
        } else {
            $result = $objCommon->createConfigFile();
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['createConfigFile'] = true;
                return true;
            }
        }
    }

    /**
    * create version file
    *
    * create the version file
    *
    * @access   private
    * @global   object  $objCommon
    * @return   mixed   true on success, error message on failure
    */
    function _createVersionFile() {
        global $objCommon;

        if (isset($_SESSION['installer']['createVersionFile']) && $_SESSION['installer']['createVersionFile']) {
            return true;
        } else {
            $result = $objCommon->createVersionFile();
            if ($result !== true) {
                return $result;
            } else {
                $_SESSION['installer']['createVersionFile'] = true;
                return true;
            }
        }
    }

    /**
    * set installation status
    *
    * set whether if the process was successfully or has failed
    *
    * @access   private
    * @global   object  $objTpl
    * @global   array   $_ARRLANG
    */
    function _setInstallationStatus(&$result, $process) {
        global $_ARRLANG, $objTpl, $templatePath;

        if ($result === true) {
            $objTpl->setVariable(array(
                'PROCESS'   => $process,
                'STATUS'    => "<img src=\"".$templatePath."images/icons/success.gif\" width=\"16\" height=\"16\" alt=\"".$_ARRLANG['TXT_SUCCESSFULLY']."\" title=\"".$_ARRLANG['TXT_SUCCESSFULLY']."\" />"
            ));
            $objTpl->parse('installationProcess');
        } else {
            $objTpl->setVariable(array(
                'ERROR_MSG' => $result
            ));
            $objTpl->parse('errorMsg');

            $objTpl->setVariable(array(
                'PROCESS'   => $process,
                'STATUS'    => "<img src=\"".$templatePath."images/icons/fail.gif\" width=\"16\" height=\"16\" alt=\"".$_ARRLANG['TXT_FAILED']."\" title=\"".$_ARRLANG['TXT_FAILED']."\" />"
            ));
            $objTpl->parse('installationProcess');
        }
    }

    /**
    * check system configuration
    *
    * check if the system configuration if useable
    *
    * @access   private
    * @global   array   $_ARRLANG
    * @global   object  $objCommon
    */
    function _checkSystemConfig() {
        global $_ARRLANG, $objCommon;

        $status = true;
        $changed = false;

        $this->arrStatusMsg['global'] = "";
        $this->arrStatusMsg['general'] = "";
        $this->arrStatusMsg['news'] = "";
        $this->arrStatusMsg['contact'] = "";

        if (isset($_POST['sysConfig'])) {
            if (!isset($_SESSION['installer']['sysConfig']['adminEmail']) || $_POST['adminEmail'] != $_SESSION['installer']['sysConfig']['adminEmail']
                || !isset($_SESSION['installer']['sysConfig']['adminName']) || $_POST['adminName'] != $_SESSION['installer']['sysConfig']['adminName']
                /*|| !isset($_SESSION['installer']['sysConfig']['rssTitle']) || $_POST['rssTitle'] != $_SESSION['installer']['sysConfig']['rssTitle']
                || !isset($_SESSION['installer']['sysConfig']['rssDescription']) || $_POST['rssDescription'] != $_SESSION['installer']['sysConfig']['rssDescription']*/
                || !isset($_SESSION['installer']['sysConfig']['contactEmail']) || $_POST['contactEmail'] != $_SESSION['installer']['sysConfig']['contactEmail']
                || !isset($_SESSION['installer']['sysConfig']['domainURL']) || $_POST['domainURL'] != $_SESSION['installer']['sysConfig']['domainURL']
            ) {
                $_SESSION['installer']['sysConfig']['adminEmail'] = trim($_POST['adminEmail']);
                $_SESSION['installer']['sysConfig']['adminName'] = trim($_POST['adminName']);
/*              $_SESSION['installer']['sysConfig']['rssTitle'] = $_POST['rssTitle'];
                $_SESSION['installer']['sysConfig']['rssDescription'] = $_POST['rssDescription'];*/
                $_SESSION['installer']['sysConfig']['contactEmail'] = trim($_POST['contactEmail']);
                $_SESSION['installer']['sysConfig']['domainURL'] = trim($_POST['domainURL']);
                $changed = true;
            }

            if (empty($_SESSION['installer']['sysConfig']['adminEmail'])
                || empty($_SESSION['installer']['sysConfig']['adminName'])
                /*|| empty($_SESSION['installer']['sysConfig']['rssTitle'])
                || empty($_SESSION['installer']['sysConfig']['rssDescription'])*/
                || empty($_SESSION['installer']['sysConfig']['contactEmail'])
                || empty($_SESSION['installer']['sysConfig']['domainURL'])
            ) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_FILL_OUT_ALL_FIELDS']."<br />";
                $status = false;
            } else {
                if (!$objCommon->isEmail($_SESSION['installer']['sysConfig']['adminEmail'])) {
                    $this->arrStatusMsg['general'] .= $_ARRLANG['TXT_SET_EMAIL']."<br />";
                    $status = false;
                }
                if (!$objCommon->isEmail($_SESSION['installer']['sysConfig']['contactEmail'])) {
                    $this->arrStatusMsg['contact'] .= $_ARRLANG['TXT_SET_EMAIL']."<br />";
                    $status = false;
                }
            }

            if ($status && isset($_SESSION['installer']['sysConfig']['adminEmail'])
                && isset($_SESSION['installer']['sysConfig']['adminName'])
              /*&& isset($_SESSION['installer']['sysConfig']['rssTitle'])
                && isset($_SESSION['installer']['sysConfig']['rssDescription'])*/
                && isset($_SESSION['installer']['sysConfig']['contactEmail'])
                && isset($_SESSION['installer']['sysConfig']['domainURL'])
            ) {
                if ($changed || !$_SESSION['installer']['sysConfig']['status']) {
                    $result = $objCommon->setSystemConfig();
                    if ($result !== true) {
                        $this->arrStatusMsg['global'] .= $result;
                        $_SESSION['installer']['sysConfig']['status'] = false;
                    } else {
                        $_SESSION['installer']['sysConfig']['status'] = true;
                        $_SESSION['installer']['step']++;
                    }
                } else {
                    $_SESSION['installer']['step']++;
                }
            } else {
                $_SESSION['installer']['sysConfig']['status'] = false;
            }
        }
    }

    /**
    * get system configuration page
    *
    * show the system configuration page
    *
    * @access   private
    * @global   object  $objTpl
    */
    function _getSystemConfigPage() {
        global $objTpl;

        $objTpl->addBlockFile('CONTENT', 'CONTENT_BLOCK', 'system_config.html');

        $objTpl->setVariable(array(
            'ADMIN_EMAIL'       => isset($_SESSION['installer']['sysConfig']['adminEmail']) ? $_SESSION['installer']['sysConfig']['adminEmail'] : '',
            'ADMIN_NAME'        => isset($_SESSION['installer']['sysConfig']['adminName']) ? $_SESSION['installer']['sysConfig']['adminName'] : '',
            'DOMAIN_URL'        => isset($_SESSION['installer']['sysConfig']['domainURL']) ? $_SESSION['installer']['sysConfig']['domainURL'] : $_SERVER['SERVER_NAME'],
          /*'RSS_TITLE'         => isset($_SESSION['installer']['sysConfig']['rssTitle']) ? $_SESSION['installer']['sysConfig']['rssTitle'] : '',
            'RSS_DESCRIPTION'   => isset($_SESSION['installer']['sysConfig']['rssDescription']) ? $_SESSION['installer']['sysConfig']['rssDescription'] : '',*/
            'CONTACT_EMAIL'     => isset($_SESSION['installer']['sysConfig']['contactEmail']) ? $_SESSION['installer']['sysConfig']['contactEmail'] : '',
        ));

        $this->tabIndex = 4;

        // set global error message
        if (!empty($this->arrStatusMsg['global'])) {
            $objTpl->setVariable(array(
                'GLOBAL_ERROR_MSG' => $this->arrStatusMsg['global']
            ));
            $objTpl->parse('globalErrorMsg');
        } else {
            $objTpl->hideBlock('globalErrorMsg');
        }

        // set general error message
        if (!empty($this->arrStatusMsg['general'])) {
            $objTpl->setVariable(array(
                'GENERAL_ERROR_MSG' => $this->arrStatusMsg['general']
            ));
            $objTpl->parse('generalErrorMsg');
        } else {
            $objTpl->hideBlock('generalErrorMsg');
        }

        // set contact error message
        if (!empty($this->arrStatusMsg['contact'])) {
            $objTpl->setVariable(array(
                'CONTACT_ERROR_MSG' => $this->arrStatusMsg['contact']
            ));
            $objTpl->parse('contactErrorMsg');
        } else {
            $objTpl->hideBlock('contactErrorMsg');
        }
    }

    /**
    * check admin account
    *
    * check if the values set by the user are valid to create the administrator account
    *
    * @access   private
    * @global   object  $objCommon
    * @global   array   $_ARRLANG
    */
    function _checkAdminAccount() {
        global $_ARRLANG, $objCommon;

        $status = true;
        $changed = false;

        $this->arrStatusMsg['global'] = "";

        if (isset($_POST['adminAccount'])) {
            if (!isset($_SESSION['installer']['account']['username'])
                || !isset($_SESSION['installer']['account']['password']) || $_POST['password'] != $_SESSION['installer']['account']['password']
                || !isset($_SESSION['installer']['account']['rePassword']) || $_POST['rePassword'] != $_SESSION['installer']['account']['rePassword']
                || !isset($_SESSION['installer']['account']['email']) || $_POST['email'] != $_SESSION['installer']['account']['email']
                || !isset($_SESSION['installer']['account']['reEmail']) || $_POST['reEmail'] != $_SESSION['installer']['account']['reEmail'])
            {
                $_SESSION['installer']['account']['username'] = $_POST['email'];
                $_SESSION['installer']['account']['password'] = $_POST['password'];
                $_SESSION['installer']['account']['rePassword'] = $_POST['rePassword'];
                $_SESSION['installer']['account']['email'] = $_POST['email'];
                $_SESSION['installer']['account']['reEmail'] = $_POST['reEmail'];
                $changed = true;
            }

            if (!isset($_SESSION['installer']['account']['username']) || empty($_SESSION['installer']['account']['username'])) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_SET_USERNAME']."<br />";
                $status = false;
            } elseif (!User::isValidUsername($_SESSION['installer']['account']['username'])) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_INVALID_USERNAME']."<br />";
                $status = false;
            }
            if (!isset($_SESSION['installer']['account']['password']) || empty($_SESSION['installer']['account']['password'])) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_SET_PASSWORD']."<br />";
                $status = false;
            } elseif ($_SESSION['installer']['account']['password'] == $_SESSION['installer']['account']['username']) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_PASSWORD_LIKE_USERNAME']."<br />";
                $status = false;
            } elseif (strlen($_SESSION['installer']['account']['password']) < 6) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_PASSWORD_LENGTH']."<br />";
                $status = false;
            } elseif (!isset($_SESSION['installer']['account']['rePassword']) || ($_SESSION['installer']['account']['rePassword'] != $_SESSION['installer']['account']['password'])) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_PASSWORD_NOT_VERIFIED']."<br />";
                $status = false;
            }
            if (!isset($_SESSION['installer']['account']['email']) || empty($_SESSION['installer']['account']['email']) || !$objCommon->isEmail($_SESSION['installer']['account']['email'])) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_SET_EMAIL']."<br />";
                $status = false;
            } elseif (!isset($_SESSION['installer']['account']['reEmail']) || ($_SESSION['installer']['account']['reEmail'] != $_SESSION['installer']['account']['email'])) {
                $this->arrStatusMsg['global'] .= $_ARRLANG['TXT_EMAIL_NOT_VERIFIED']."<br />";
                $status = false;
            }

            if ($status) {
                if ($changed
                 || empty ($_SESSION['installer']['account']['status'])) {
                    $result = $objCommon->createAdminAccount();
                    if ($result !== true) {
                        $this->arrStatusMsg['global'] .= $result;
                        $_SESSION['installer']['account']['status'] = false;
                    } else {
                        $_SESSION['installer']['account']['status'] = true;
                        $_SESSION['installer']['step']++;
                    }
                } else {
                    $_SESSION['installer']['step']++;
                }
            } else {
                $_SESSION['installer']['account']['status'] = false;
            }
        }
    }

    /**
    * get admin account page
    *
    * get the administrator account create page
    *
    * @access private
    * @global object    $objTpl
    * @global array     $_ARRLANG
    */
    function _getAdminAccountPage() {
        global $objTpl, $_ARRLANG, $objCommon, $language;

        // load template file
        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', "admin_account.html");

        $useDefaultLanguage = true;

        $objTpl->setVariable(array(
            'USERNAME' => isset($_SESSION['installer']['account']['username']) ? $_SESSION['installer']['account']['username'] : "",
            'PASSWORD' => isset($_SESSION['installer']['account']['password']) ? $_SESSION['installer']['account']['password'] : "",
            'REPASSWORD' => isset($_SESSION['installer']['account']['rePassword']) ? $_SESSION['installer']['account']['rePassword'] : "",
            'EMAIL' => isset($_SESSION['installer']['account']['email']) ? $_SESSION['installer']['account']['email'] : $_SESSION['installer']['sysConfig']['adminEmail'],
            'REEMAIL' => isset($_SESSION['installer']['account']['reEmail']) ? $_SESSION['installer']['account']['reEmail'] : $_SESSION['installer']['sysConfig']['adminEmail']
        ));

        $this->tabIndex = 6;

        // set error message
        if (!empty($this->arrStatusMsg['global'])) {
            $objTpl->setVariable(array(
                'ERROR_MSG' => $this->arrStatusMsg['global']
            ));
            $objTpl->parse('errorMsg');
        } else {
            $objTpl->hideBlock('errorMsg');
        }
    }

    function _showTermination() {
        global $objTpl, $_ARRLANG, $_CONFIG, $_DBCONFIG, $objCommon, $basePath, $documentRoot;

        // load template file
        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', "termination.html");

        $result = $this->_createConfigFile();
        if ($result !== true) {
            $objTpl->setVariable(array(
                'ERROR_MSG' => $result
            ));
            $objTpl->parse('errorMsg');
            $objTpl->hideBlock('termination');
        } else {
            $objCommon->updateCheck();

            $objTpl->hideBlock('errorMsg');

            $port = intval($_SERVER['SERVER_PORT']);
            if ($port != 80) {
                $port = ':'.$port;
            } else {
                $port = '';
            }

            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                $protocol = "http://";
            } else {
                $protocol = "https://";
            }
            $webUrl = $protocol.$_SESSION['installer']['sysConfig']['domainURL'].$port.$_SESSION['installer']['config']['offsetPath'].'/';
            $adminUrl = $protocol.$_SESSION['installer']['sysConfig']['domainURL'].$port.$_SESSION['installer']['config']['offsetPath'].'/cadmin/';

            $congratulationsMsg = $_ARRLANG['TXT_CONGRATULATIONS_MESSAGE'];
            $congratulationsMsg = str_replace("[VERSION]", $_CONFIG['coreCmsVersion'], $congratulationsMsg);
            $congratulationsMsg = str_replace("[EDITION]", $_CONFIG['coreCmsEdition'], $congratulationsMsg);

            $internetSiteMsg = $_ARRLANG['TXT_INTERNET_SITE_MESSAGE'];
            $internetSiteMsg = str_replace("[WEB_URL]", "<a href=\"".$webUrl."\" target=\"_blank\" title=\"".$_ARRLANG['TXT_INTERNET_SITE_FOR_VISITORS']."\">".$webUrl."</a>", $internetSiteMsg);

            $adminSiteMsg = $_ARRLANG['TXT_ADMIN_SITE_MESSAGE'];
            $adminSiteMsg = str_replace("[ADMIN_URL]", "<a href=\"".$adminUrl."\" target=\"_blank\" title=\"".$_ARRLANG['TXT_ADMIN_SITE']."\">".$adminUrl."</a>", $adminSiteMsg);

            $objTpl->setVariable(array(
                'TXT_LOGIN_CREDENTIAL'      => $_ARRLANG['TXT_LOGIN_EMAIL'],
                'CONGRATULATIONS_MESSAGE'   => $congratulationsMsg,
                'INTERNET_SITE_MESSAGE'     => $internetSiteMsg,
                'ADMIN_SITE_MESSAGE'        => $adminSiteMsg,
                'USERNAME'                  => $_SESSION['installer']['account']['username'],
                'PASSWORD'                  => $_SESSION['installer']['account']['password'],
                'HTML_IMAGE_CODE'           => $_SESSION['installer']['updateCheckImage']
            ));

            $objTpl->parse('termination');

            // overwrite current DBCONFIG with the new data, so the database can be loaded correct in this request
            $_DBCONFIG['host'] = $_SESSION['installer']['config']['dbHostname'];
            $_DBCONFIG['database'] = $_SESSION['installer']['config']['dbDatabaseName'];
            $_DBCONFIG['user'] = $_SESSION['installer']['config']['dbUsername'];
            $_DBCONFIG['password'] = $_SESSION['installer']['config']['dbPassword'];
            $_DBCONFIG['tablePrefix'] = $_SESSION['installer']['config']['dbTablePrefix'];

            @session_destroy();
            // clear cx in env, because from now on we use the core Cx and not longer the InstallerCx
            \Env::clear('cx');
            // we will now initialize a new session and will login the administrator (userID = 1).
            // this is required to allow the License system (versioncheck.php) to update
            // the license section template
            // We might have some overhead, since versioncheck.php does more or less the same again
            $documentRoot = realpath(dirname($basePath));
            require_once($documentRoot.'/core/Core/init.php');
            $cx = init('minimal', false);

            $sessionObj = $cx->getComponent('Session')->getSession();

            $userId = 1;
            $_SESSION->cmsSessionUserUpdate($userId);

            $_GET['force'] = 'true';
            $_GET['silent'] = 'true';
            require_once($documentRoot.'/core_modules/License/versioncheck.php');
        }
    }

    function _getHelpPage() {
        global $objTpl, $_ARRLANG, $_CONFIG, $supportEmail, $forumURI, $supportURI;

        // load template file
        $objTpl->addBlockfile('CONTENT', 'CONTENT_BLOCK', "help.html");

        $helpMsg = $_ARRLANG['TXT_HELP_MSG'];
        $helpMsg = str_replace("[NAME]", $_CONFIG['coreCmsName'], $helpMsg);
        $helpMsg = str_replace("[EMAIL]", '<a href="mailto:'.$supportEmail.'?subject=Contrexx%20Installation">'.$supportEmail.'</a>', $helpMsg);
        $helpMsg = str_replace("[PHPINFO]", '<a href="'.$_SERVER['PHP_SELF'].'?page=phpinfo" target="_blank">'.$_ARRLANG['TXT_PHP_INFO'].'</a>', $helpMsg);
        $helpMsg = str_replace("[FORUM]", '<a href="'.$forumURI.'" target="_blank">'.$_ARRLANG['TXT_FORUM'].'</a>', $helpMsg);
        $helpMsg = str_replace("[SUPPORT]", '<a href="'.$supportURI.'" target="_blank">'.$_ARRLANG['TXT_SUPPORT'].'</a>', $helpMsg);
        $objTpl->setVariable('HELP_MSG', $helpMsg);

    }
}
