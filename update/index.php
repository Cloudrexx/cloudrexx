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
 * Cloudrexx Update System
 *
 * This class is used to update the system to a newer version of Cloudrexx.
 *
 * @copyright   Cloudrexx WMS - Cloudrexx AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  update
 */

$php = phpversion();
if ($php < '5.3') {
    die('Das Cloudrexx CMS ben&ouml;tigt mindestens PHP in der Version 5.3.<br />Auf Ihrem System l&auml;uft PHP '.$php);
}

// Debugging include
require_once dirname(__FILE__).'/lib/FRAMEWORK/DBG/DBG.php';

// Check effective maximum execution time
if (!empty($_GET['check_timeout'])) {
    $timeout = time() + 55;
    while ($timeout > time()) {}
    die('1');
}

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

// Set frontend language id to German
define('FRONTEND_LANG_ID', 1);

// Update configuration
require_once(dirname(__FILE__).'/config/configuration.php');

global $_PATHCONFIG;
// Config files
//require_once(dirname(UPDATE_PATH) . '/config/configuration.php');
$configContents = file_get_contents(dirname(UPDATE_PATH) . '/config/configuration.php');
// remove require_once dirname(__FILE__).'/set_constants.php';
$configContents = preg_replace('#<\?(?:php)?#', '', $configContents);
$configContents = preg_replace('#require_once (?:[/\(\)_a-zA-Z\\.\']+);#', '', $configContents);
eval($configContents);

fixPaths($_PATHCONFIG['ascms_root'], $_PATHCONFIG['ascms_root_offset']);

$_PATHCONFIG['ascms_installation_root'] = $_PATHCONFIG['ascms_root'];
$_PATHCONFIG['ascms_installation_offset'] = $_PATHCONFIG['ascms_root_offset'];

$incSettingsStatus = include_once(dirname(UPDATE_PATH) . '/config/settings.php');
$incSettingsStatus = include_once(dirname(UPDATE_PATH) . '/config/set_constants.php');

if (!isset($_CONFIG['useCustomizings'])) {
    $_CONFIG['useCustomizings'] = 'off';
}
if (!defined('ASCMS_CUSTOMIZING_PATH')) {
    define('ASCMS_CUSTOMIZING_PATH', '');
}

// Contrexx 3.0.0+ do not have a version.php. The information is included in settings.php
if (!isset($_CONFIG['coreCmsVersion']) && file_exists(ASCMS_DOCUMENT_ROOT . '/config/version.php')) {
    require_once(ASCMS_DOCUMENT_ROOT . '/config/version.php');
}

// Check if the system is installed
if (!defined('CONTREXX_INSTALLED') || !CONTREXX_INSTALLED) {
    header('Location: ../installer/index.php');
    exit;
} else if ($incSettingsStatus === false) {
    die('System halted: Unable to load basic configuration!');
}

require_once(UPDATE_CORE . '/Env.class.php');

Env::set('config', $_CONFIG);
Env::set('dbconfig', $_DBCONFIG);
Env::set('ftpConfig', $_FTPCONFIG);

// Library and core files
require_once(UPDATE_CORE . '/database.php');
require_once(UPDATE_CORE . '/validator.inc.php');
require_once(UPDATE_CORE . '/Init.class.php');
require_once(UPDATE_CORE . '/Model/RecursiveArrayAccess.class.php');

require_once(UPDATE_LIB . '/PEAR/HTML/Template/Sigma/Sigma.php');
require_once(UPDATE_LIB . '/adodb/adodb.inc.php');
require_once(UPDATE_LIB . '/FRAMEWORK/Language.class.php');
require_once(UPDATE_LIB . '/FRAMEWORK/cxjs/ContrexxJavascript.class.php');
require_once(UPDATE_LIB . '/FRAMEWORK/Javascript.class.php');

// Update files
require_once(UPDATE_PATH . '/ContrexxUpdate.class.php');
require_once(UPDATE_LIB . '/FRAMEWORK/UpdateUtil.class.php');

$objDatabase = getDatabaseObject($errorMsg);
if (!$objDatabase) {
    die($errorMsg);
}
Env::set('db', $objDatabase);

if (!\Cx\Lib\UpdateUtil::table_exist(DBPREFIX.'session_variable')) {
    require_once(UPDATE_CORE . '/session.class.php');
    // Start session
    $sessionObj = new cmsSession();
} else {
    require_once(UPDATE_CORE . '/session32.class.php');
    $sessionObj = \cmsSession::getInstance();
}
$sessionObj->cmsSessionStatusUpdate('backend');

// Initialize base system
$objInit = new InitCMS('update', \Env::get('em'));
Env::set('init', $objInit);

JS::activate('cx');
JS::activate('jquery-tools');
JS::registerJS('lib/contrexxUpdate.php');
JS::registerJS('lib/javascript/html2dom.js');

// Debugging
try {
    // load file classes
    require_once dirname(__FILE__).'/lib/FRAMEWORK/FileSystem/FileInterface.interface.php';
    require_once dirname(__FILE__).'/lib/FRAMEWORK/FileSystem/FileSystem.class.php';
    require_once dirname(__FILE__).'/lib/FRAMEWORK/FileSystem/FileSystemFile.class.php';
    require_once dirname(__FILE__).'/lib/FRAMEWORK/FileSystem/FTPFile.class.php';
    require_once dirname(__FILE__).'/lib/FRAMEWORK/FileSystem/File.class.php';
    activateDebugging();
} catch (\Exception $e) {
    // don't handle this exception here because we can't print a nice error message
}

require_once(UPDATE_CORE . '/cache/cache.class.php');
global $objCache;

$objCache = new \Cx\Core_Modules\Cache\Controller\Cache();

// Start update
$objUpdate = new ContrexxUpdate();
// $_CORELANG has been initialized by the constructor of ContrexxUpdate()
// add language variables of core (will be used by FWUser)
$_CORELANG = array_merge($objInit->loadLanguageData('core'), $_CORELANG);
$output = $objUpdate->getPage();
JS::findJavascripts($output);
$output = str_replace('javascript_inserting_here', JS::getCode(), $output);

die($output);

function fixPaths(&$documentRoot, &$rootOffset) {
    // calculate correct offset path
    // turning '/myoffset/somefile.php' into '/myoffset'
    /*$rootOffset = '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $directories = explode('/', $scriptName);
    for ($i = 0; $i < count($directories) - 2; $i++) {
        if ($directories[$i] !== '') {
            $rootOffset .= '/'.$directories[$i];
        }
    }*/

    // fix wrong offset if another file than index.php was requested
    // turning '/myoffset/core_module/somemodule' into '/myoffset'
    $fileRoot = dirname(dirname(__FILE__));
    $fileRoot = str_replace('\\', '/', $fileRoot);
    $nonOffset = preg_replace('#' . preg_quote($fileRoot) . '#', '', $_SERVER['SCRIPT_NAME']);
    $nonOffset = str_replace('\\', '/', $nonOffset);
    $nonOffsetParts = explode('/', $nonOffset);
    end($nonOffsetParts);
    unset($nonOffsetParts[key($nonOffsetParts)]);
    end($nonOffsetParts);
    unset($nonOffsetParts[key($nonOffsetParts)]);
    $nonOffset = implode('/', $nonOffsetParts);
    $rootOffset = $nonOffset;//preg_replace('#' . $nonOffset . '#', '', $rootOffset);

    $documentRoot = preg_replace('#' . $rootOffset . '#', '', $fileRoot);
    $documentRoot = str_replace('\\', '/', $documentRoot);
    $rootOffset = preg_replace('#' . $documentRoot . '#', '', $rootOffset);

    /*echo $documentRoot;
    // calculate correct document root
    // turning '/var/www/myoffset' into '/var/www'
    $documentRoot = '';
    $arrMatches = array();
    $scriptPath = str_replace('\\', '/', dirname(dirname(__FILE__)));
    if (preg_match("/(.*)(?:\/[\d\D]*){2}$/", $scriptPath, $arrMatches) == 1) {
        $scriptPath = $arrMatches[1];
    }
    if (preg_match("#(.*)".preg_replace(array('#\\\#', '#\^#', '#\$#', '#\.#', '#\[#', '#\]#', '#\|#', '#\(#', '#\)#', '#\?#', '#\*#', '#\+#', '#\{#', '#\}#'), '\\\\$0', $rootOffset)."#", $scriptPath, $arrMatches) == 1) {
        $documentRoot = $arrMatches[1];
    }*/
}
