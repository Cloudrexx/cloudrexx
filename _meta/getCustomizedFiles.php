<?php
/**
 * List the customized files of the current installation
 * 
 *  ********************************************
 *  IMPORTANT :
 *  ********************************************
 *   It should be run from the root directory of the contrexx installation
 */

//
// Baby, turn me on!  B-)
//
define('_DEBUG', 0);
define('SCRIPT_TIME', time());
define('SCRIPT_TIMEOUT_TIME', SCRIPT_TIME + 55);

$arrConfig = array(
  'cmsRequiredPHP'   => '5.3',
  'cmsRequiredMySQL' => '5.0',  
);

/**
 * Please don't mess with the code below.
 */
if (_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

$_CONFIG = array();
$_SYSCONFIG = array();

$path = dirname(__FILE__);
$currentVersion = '';

global $_PATHCONFIG;
// Config files
//require_once(dirname(UPDATE_PATH) . '/config/configuration.php');
$configContents = file_get_contents($path . '/config/configuration.php');
// remove require_once dirname(__FILE__).'/set_constants.php';
$configContents = preg_replace('#<\?(?:php)?#', '', $configContents);
$configContents = preg_replace('#require_once (?:[/\(\)_a-zA-Z\\.\']+);#', '', $configContents);
eval($configContents);

$customizingDetection = new customizingDetection();
$customizingDetection->fixPaths($_PATHCONFIG['ascms_root'], $_PATHCONFIG['ascms_root_offset']);

$_PATHCONFIG['ascms_installation_root'] = $_PATHCONFIG['ascms_root'];
$_PATHCONFIG['ascms_installation_offset'] = $_PATHCONFIG['ascms_root_offset'];

$incSettingsStatus = include_once($path . '/config/settings.php');
$incSettingsStatus = include_once($path . '/config/set_constants.php');

if (!isset($_CONFIG['useCustomizings'])) {
    $_CONFIG['useCustomizings'] = 'off';
}
if (!defined('ASCMS_CUSTOMIZING_PATH')) {
    define('ASCMS_CUSTOMIZING_PATH', '');
}

// Contrexx 3.0.0+ do not have a version.php. The information is included in settings.php
if (!isset($_CONFIG['coreCmsVersion']) && file_exists(ASCMS_DOCUMENT_ROOT . '/config/version.php')) {
    require_once(ASCMS_DOCUMENT_ROOT . '/config/version.php');
    // contrexx version below 1.1.0, does not have $_CONFIG so get it from $_SYSCONFIG
    $_CONFIG = array_merge($_CONFIG, $_SYSCONFIG);
}

$objDb = new MySQL($_DBCONFIG['database'], $_DBCONFIG['user'], $_DBCONFIG['password'], $_DBCONFIG['host']);

if (!empty($objDb->lastError)) {
    die($objDb->lastError);
}

session_start();
$_SESSION['contrexx']['getCustomizingFinished'] = !empty($_SESSION['contrexx']['getCustomizingFinished'])
                                                  ? $_SESSION['contrexx']['getCustomizingFinished']
                                                  : false;
$_SESSION['contrexx']['max_execution_time']     = !empty($_SESSION['contrexx']['max_execution_time'])
                                                  ? $_SESSION['contrexx']['max_execution_time']
                                                  : @ini_get('max_execution_time');
$_SESSION['contrexx']['customizedFiles']        = isset($_SESSION['contrexx']['customizedFiles'])
                                                  ? $_SESSION['contrexx']['customizedFiles']
                                                  : array();
$_SESSION['contrexx']['nonSystemFiles']         = isset($_SESSION['contrexx']['nonSystemFiles'])
                                                  ? $_SESSION['contrexx']['nonSystemFiles']
                                                  : array();
$_SESSION['contrexx']['additionalSystemFiles']  = isset($_SESSION['contrexx']['additionalSystemFiles'])
                                                  ? $_SESSION['contrexx']['additionalSystemFiles']
                                                  : array();
$_SESSION['contrexx']['oldSystemFiles']         = isset($_SESSION['contrexx']['oldSystemFiles'])
                                                  ? $_SESSION['contrexx']['oldSystemFiles']
                                                  : array();

$allVersionMd5List = array(
    '0.9.md5', '1.0.0.md5', '1.0.1.md5', '1.0.2.md5', '1.0.3.md5', '1.0.4.md5',
    '1.0.5.md5', '1.0.6.md5', '1.0.7.md5', '1.0.8.md5', '1.0.9.md5', '1.0.9.1.md5',
    '1.0.9.2.md5', '1.0.9.3.md5', '1.0.9.4.md5', '1.0.9.4.1.md5', '1.0.9.5.md5',
    '1.0.9.6.md5', '1.0.9.7.md5', '1.0.9.8.md5', '1.0.9.9.md5', '1.0.9.9.1.md5',
    '1.0.9.10.md5', '1.0.9.10.1.md5', '1.1.0.md5', '1.1.1.md5', '1.1.2.md5',
    '1.1.3.md5', '1.2.0.md5', '1.2.1.md5', '1.2.2.md5', '1.2.3.md5', '2.0.0.md5',
    '2.0.1.md5', '2.0.2.md5', '2.1.0.md5', '2.1.1.md5', '2.1.2.md5', '2.1.3.md5',
    '2.1.4.md5', '2.1.5.md5', '2.2.0.md5', '2.2.1.md5', '2.2.2.md5', '2.2.3.md5',
    '2.2.4.md5', '2.2.5.md5', '2.2.6.md5', '3.0.0.md5', '3.0.0_RC1.md5',
    '3.0.0_RC2.md5', '3.0.0.1.md5', '3.0.1.md5', '3.0.2.md5', '3.0.3.md5',
    '3.0.4.md5', '3.1.0.md5', '3.1.1.md5', '3.2.0.md5', '4.0.0.md5'
);

$allVersionInstallationDir = array(
    'admin',
    'config',
    'core',
    'core_modules',
    'editor',
    'lang',
    'lib',
    'modules',
    'webcam',
    'cadmin',
    'model'
);

$arrMd5SumsOfCxFiles = array();

$exceptionDirs = array(
    '/cache',
    '/installer',
    '/tmp',
    '/themes',
    '/media',
    '/images',
    '/feed',
    '/update'
);
$exceptionFiles = array(
    '/images/favicon.ico',
    '/robots.txt',
    '/sitemap.xml',
    '/README.txt',
    '/config/version.php',
    '/config/settings.php',
    '/getCustomizedFiles.php',
    '/config/configuration.php'
);

/***********************************************
 * 
 * INCLUDE CLASS 
 * 
 ***********************************************/

class customizingDetection {
    private static $customizingServer = "http://updatesrv1.contrexx.com/md5_checksum/";

    public $output;
    
    public function setOutPut($msg, $type) {
        $class = '';
        switch ($type) {
            case 'error':
                $class = "class='error'";
                break;
            default:
                break;
        }
        
        $this->output = "<div $class>" . $msg . "</div>";
    }

    public function startProgress() {
        if (empty($_SESSION['contrexx']['getCustomizingFinished'])) {
            $detectCustomizingStatus = $this->detectCustomizing();
            if ($detectCustomizingStatus !== true) {
                if ($detectCustomizingStatus === 'timeout') {
                    $output  = "<h1>Interruption in process</h1>";
                    $output .= "The File detection process is interrupted due to insufficient memory is available. <br /> Press the <span style='font-weight: bold;'> Check website for customizings ... </span> to proceed with the process. The process is thereby resumes where it left off.";
                    $output .= '<div class="right">';
                    $output .= '&nbsp;&nbsp;<input type="submit" name="getCustomization" value="Check website for customizings" />';
                    $output .= '</div>';
                }
                if ($detectCustomizingStatus === 'serverNotConnect') {
                    $output  = '<div>';
                    $output .= 'The server could not be connected.';
                    $output .= '</div>';
                }
                
                $this->setOutPut($output, 'error');
                return;
            }
        }
        $this->getCustomizedFiles();
    }

    public function getCustomizedFiles()
    {
        $output = '';
        $customizedFiles       = isset($_SESSION['contrexx']['customizedFiles'])
                                 ? $_SESSION['contrexx']['customizedFiles']
                                 : '';
        $oldSystemFiles        = isset($_SESSION['contrexx']['oldSystemFiles'])
                                 ? $_SESSION['contrexx']['oldSystemFiles']
                                 : '';
        $nonSystemFiles        = isset($_SESSION['contrexx']['nonSystemFiles'])
                                 ? $_SESSION['contrexx']['nonSystemFiles']
                                 : '';
        $additionalSystemFiles = isset($_SESSION['contrexx']['additionalSystemFiles'])
                                 ? $_SESSION['contrexx']['additionalSystemFiles']
                                 : '';

        if (   empty($customizedFiles)
            && empty($oldSystemFiles)
            && empty($nonSystemFiles)
            && empty($additionalSystemFiles)
        ) {
            $output = '<p class="success"><strong><i>No Files are modified in this installation.!</p>';
            $this->setOutPut($output, 'detectionCompleted');
            return;
        }

        $output .= '<div id="tabs"> <ul>';
        $output .= '<li class="tabMenu"><a href="#tabs-1">Customized System Files</a></li>';
        $output .= '<li class="tabMenu"><a href="#tabs-2">Old System System Files</a></li>';
        $output .= '<li class="tabMenu"><a href="#tabs-3" >Additional System Files</a></li>';
        $output .= '<li class="tabMenu"><a href="#tabs-4" >Non System Files</a></li>';
        $output .= '</ul>';

        // Customized files list
        $output .= '<div id="tabs-1" class="fileList">';
        $output .= $this->checkDetectedList($customizedFiles);

        // Previous version files list
        $output .= '<div id="tabs-2" class="fileList">';
        $output .= $this->checkDetectedList($oldSystemFiles);

        // Non installation file under the (config, core, core_modules, lang, lib, model, modules) directories
        $output .= '<div id="tabs-3" class="fileList">';
        $output .= $this->checkDetectedList($additionalSystemFiles);

        // Non installation file other than the (config, core, core_modules, lang, lib, model, modules) directories
        $output .= '<div id="tabs-4" class="fileList">';
        $output .= $this->checkDetectedList($nonSystemFiles);
        
        //Download Button
        $output .= '</div><div class="right">';
        $output .= '&nbsp;&nbsp;<button  onclick = "downloadSectionFiles();" id="downloadCustomizedFiles"><span >Download modified files</span></button>';
        $output .= '<br /></div>';
        // Javascript code for tab. It will initialize when user click the check website for customizing button
        $output .= <<<CODE
<script type="text/javascript">
    jQuery(document).ready(function() {
           jQuery("#tabs").tabs();
    });
    
    function downloadSectionFiles(fileCnt, sectionCnt, status)
    {
         var thisObj         = jQuery('#downloadCustomizedFiles');
         var addedFileCnt    = fileCnt != undefined ? fileCnt : 0;
         var addedSectionCnt = sectionCnt != undefined ? sectionCnt : 0;
         if (status == 'success') {
            window.location.replace('?sendDownloadRequest=1&downloadFile=1');
            thisObj
                .attr('disabled', false)
                .find('span').removeClass('loader')
                    .html('Download modified files');
            return false;
         }

         $.ajax({
            url : '?sendDownloadRequest=1&addedFileCount='+addedFileCnt+'&addedSectionCount='+addedSectionCnt,
            dataType: 'json',
            beforeSend: function() {
                thisObj
                    .attr('disabled', true)
                    .find('span').addClass('loader')
                        .html('Downloading modified files');
            },
            success : function(res) {
                var fileCount = res.fileCount != undefined ? res.fileCount : 0;
                var sectionCount = res.sectionCount != undefined ? res.sectionCount : 0;
                downloadSectionFiles(fileCount, sectionCount, res.status);
            }
        });
    }
</script>
CODE;

        $this->setOutPut($output, 'detectionCompleted');
    }
    
    public function checkDetectedList($list) {
        $result = !empty($list)
                  ? $this->getFormattedOutput($list)
                  : 'There is no files found';
        $result .= '</div>';
        return $result;
    }
    
    public function getFormattedOutput($files) {
        return utf8_encode(implode('<br />',
            array_map(function ($v, $k) {
                $value = is_array($v)
                         ? $v['file']." (Version_".$v['version'].")"
                         : $v;
                return $k + 1 . '. &nbsp; ' . $value;
            }, $files, array_keys($files))
        ));
    }

    public function loadMd5SumOfOriginalCxFiles() {
        global $objDb,$arrMd5SumsOfCxFiles, $_CONFIG, $_DBCONFIG, $currentVersion;
        
        if ($_CONFIG['coreCmsVersion'] == '3.0.0') {
            
            $resultRc1 = $objDb->ExecuteSQL('SELECT `target` FROM `'.$_DBCONFIG['tablePrefix'].'backend_areas` WHERE `area_id` = 186');
            $resultRc2 = $objDb->ExecuteSQL('SELECT `order_id` FROM `'.$_DBCONFIG['tablePrefix'].'backend_areas` WHERE `area_id` = 2');

            if ($resultRc1['target'] != '_blank') {
                $filename = $_CONFIG['coreCmsVersion'].'_RC1.md5';
            } elseif ($resultRc2['order_id'] != 6) {
                $filename = $_CONFIG['coreCmsVersion'].'_RC2.md5';
            }
            
        } else {
            $filename = $_CONFIG['coreCmsVersion'].'.md5';
        }
        
        $currentVersion = $filename;
        $arrMd5SumsOfCxFiles = $this->getMd5SumResult($filename);
        
        if (!$arrMd5SumsOfCxFiles) {
            return false;
        }
        return true;
    }
    
    public function getMd5SumResult($filename)
    {
        $md5Sum     = $this->getMd5Sum($filename);
        if (empty($md5Sum)) {
            return false;
        }
        
        $md5SumsOfCxFiles = array();
        $list = explode("\n", $md5Sum);
        
        foreach ($list as $entry) {
            $entry = trim($entry);
            if (empty($entry)) {
                continue;
            }
            
            list($file, $md5sum, $rawMd5Sum) = explode('|', $entry);
            if (!isset($md5SumsOfCxFiles[$file])) {
                $md5SumsOfCxFiles[$file] = array();
            }
            $md5SumsOfCxFiles[$file][] = array(
                'md5_sum'     => $md5sum,
                'raw_md5_sum' => $rawMd5Sum
            );
        }
        
        return $md5SumsOfCxFiles;
    }

    public function getMd5Sum($filename)
    {
        $tempMd5SumDir = $this->getTempPath().'/md5Sums';
        if (!file_exists($tempMd5SumDir)) {
            mkdir($tempMd5SumDir, 0777);
        }
        
        $md5SumFile = $tempMd5SumDir.'/'.$filename;
        if (file_exists($md5SumFile)) {
            return file_get_contents($md5SumFile);
        }
        
        $filename = self::$customizingServer . $filename;
        $request = new HTTP_Request($filename);
        $request->sendRequest();
        if ($request->getResponseCode() != 200) {
            return false;
        }
        
        $md5Sum = $request->getResponseBody();
        $md5SumFile = fopen($md5SumFile, "w");
        fwrite($md5SumFile, $md5Sum);
        fclose($md5SumFile);
        chmod($md5SumFile, 0777);
        return $md5Sum;
    }
    
    public function detectCustomizing()
    {
        if (!$this->loadMd5SumOfOriginalCxFiles()) {
            return 'serverNotConnect';
        }
        
        $customizingResponse = $this->readDirs(ASCMS_DOCUMENT_ROOT);
        if ($customizingResponse === true) {
            $_SESSION['contrexx']['getCustomizingFinished'] = 1;
        }
        return $customizingResponse;
    }

    public function readDirs($path) 
    {
        global $_CONFIG, $exceptionFiles, $exceptionDirs;
        static $checkedFileIndex = 0;

        $customizingOn  = (version_compare($_CONFIG['coreCmsVersion'], '3.0.0') >= 0) &&  $_CONFIG['useCustomizings'] == 'on';
        
        if (in_array(str_replace(ASCMS_DOCUMENT_ROOT, '', $path), $exceptionDirs)) {
            return;
        }
        
        foreach ($this->getFileDirArray($path, false) as $filePath) {
            $fileName = str_replace(ASCMS_DOCUMENT_ROOT . '/', '', $filePath);
            if ($customizingOn && file_exists(ASCMS_CUSTOMIZING_PATH . '/' . $fileName)) {
                continue;
            }
            
            if (empty($fileName) || in_array('/' . $fileName, $exceptionFiles)) {
                continue;
            }
            
            if (!$this->checkMemoryLimit() || !$this->checkTimeoutLimit()) {
                $_SESSION['contrexx']['checkedFileIndex'] = $checkedFileIndex;
                return 'timeout';
            }
            $checkedFileIndex++;
            if (isset($_SESSION['contrexx']['checkedFileIndex']) && $checkedFileIndex <= $_SESSION['contrexx']['checkedFileIndex']) {
                continue;
            }
            
            $customizingFile     = false;
            if ($customizingOn) {
                $fileName        = str_replace(str_replace(ASCMS_DOCUMENT_ROOT . '/', '', ASCMS_CUSTOMIZING_PATH . '/'), '', $fileName);
                $customizingFile = substr($filePath, strlen(ASCMS_DOCUMENT_ROOT . '/'));                     
            }
            
            if (!$this->verifyMd5SumOfFile($filePath, $fileName)) {
                $_SESSION['contrexx']['customizedFiles'][] = $customizingFile ? $customizingFile : $fileName;
            }
        }
        foreach ($this->getFileDirArray($path) as $folder) {
            if (!$customizingOn &&  $folder == ASCMS_CUSTOMIZING_PATH) {
                continue;
            }
            if ($this->readDirs($folder) === 'timeout') {
                return 'timeout';
            }
        }

        return true;
        
    }
    
    public function getFileDirArray($path, $folderOnly = true)
    {
        $fileArray = $folderOnly
                     ? glob("$path/*", GLOB_ONLYDIR|GLOB_NOSORT)
                     : glob("$path/*.*", GLOB_BRACE);
        return $fileArray ? $fileArray : array();
    }
    
    public function getOlderVersion($cxFileName)
    {
        global $allVersionMd5List, $currentVersion;
        
        $previousVersions = array_slice($allVersionMd5List, 0, array_search($currentVersion, $allVersionMd5List));
        foreach ($previousVersions as $version) {            
            if ($this->isFileExistsInVersion($version, $cxFileName)) {
                return $version;
            }
        }
        
        return false;
    }
    
    public function isFileExistsInVersion($version, $fileName)
    {
        $md5Sum = $this->getMd5SumResult($version);
        if (!$md5Sum || !array_key_exists($fileName, $md5Sum)) {
            return false;
        }
        return true;
    }

    public function verifyMd5SumOfFile($file, $cxFilePath)
    {
        global $arrMd5SumsOfCxFiles, $allVersionInstallationDir;

        if (!file_exists($file)) {
            return true;
        }
        
        // file did not present in current version,
        if (!isset($arrMd5SumsOfCxFiles[$cxFilePath])) {
            $exceptionDir = explode('/', $cxFilePath);
            if (in_array($exceptionDir[0], $allVersionInstallationDir)) {
                $olderVersion = $this->getOlderVersion($cxFilePath);
                if ($olderVersion) {
                    $_SESSION['contrexx']['oldSystemFiles'][] = array(
                        'file'    => $cxFilePath,
                        'version' => $olderVersion
                    );
                    return true;
                }
            }
            
            if (!preg_match("/^(config|core|core_modules|lang|lib|model|modules)\/.*/", $cxFilePath)) {
                $_SESSION['contrexx']['nonSystemFiles'][] = $cxFilePath;
            } else {
                $_SESSION['contrexx']['additionalSystemFiles'][] = $cxFilePath;
            }
            return true;
        }
        
        foreach ($arrMd5SumsOfCxFiles[$cxFilePath] as $validMd5Sum) {
            if (   md5_file($file) == $validMd5Sum['md5_sum']
                || md5(preg_replace('/\s/u', '', file_get_contents($file))) == $validMd5Sum['raw_md5_sum']
            ) {
                return true;
            }
        }

        return false;
    }
    
    public function getTempPath()
    {
        if (defined('ASCMS_TEMP_PATH')) {
            return ASCMS_TEMP_PATH;            
        }
        
        $tmpFolder = dirname(__FILE__) .'/tmp'; 
        if (is_dir($tmpFolder)) {
            return $tmpFolder;
        } else {
           if (!file_exists($tmpFolder)) {
               if (mkdir($tmpFolder, 0777)) {
                   return $tmpFolder;
               } else {
                   die('Could not create temp directory');
               }
           } else {
               die('Could not create temp directory');
           }
        }
    }
    
    public function checkMemoryLimit()
    {
        static $memoryLimit, $MiB2;
        
        if (!isset($memoryLimit)) {
            $memoryLimit = $this->getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
            if (empty($memoryLimit)) {
                // set default php memory limit of 8MiBytes
                $memoryLimit = 8*pow(1024, 2);
            }
            $MiB2 = 2 * pow(1024, 2);
        }
        
        $potentialRequiredMemory = memory_get_usage() + $MiB2;
        if ($potentialRequiredMemory > $memoryLimit) {
            // try to set a higher memory_limit
            if (!@ini_set('memory_limit', $potentialRequiredMemory)) {
                return false;
            }
        }
        return true;
    }
    
    public function checkTimeoutLimit()
    {
        $timeoutTime = SCRIPT_TIMEOUT_TIME;
        if (!empty($_SESSION['contrexx']['max_execution_time'])) {
            $timeoutTime = SCRIPT_TIME + $_SESSION['contrexx']['max_execution_time'];
        }

        if ($timeoutTime > time()) {
            return true;
        }

        return false;
    }

    /**
     * Return the literal size $literalSize in bytes.
     *
     * @param string $literalSize
     * @return integer
     */
    public function getBytesOfLiteralSizeFormat($literalSize)
    {
        $subpatterns = array();
        if (preg_match('#^([0-9.]+)\s*(gb?|mb?|kb?|bytes?|)?$#i', $literalSize, $subpatterns)) {
            $bytes = $subpatterns[1];
            switch (strtolower($subpatterns[2][0])) {
                case 'g':
                    $bytes *= 1024;

                case 'm':
                    $bytes *= 1024;

                case 'k':
                    $bytes *= 1024;
            }

            return $bytes;
        } else {
            return 0;
        }
    }
    
    public function checkPHPVersion($requiredVersion)
    {
        if (preg_match('#(?:[0-9]+\.?)+#', phpversion(), $arrMatch)) {
            return !$this->_isNewerVersion($arrMatch[0], $requiredVersion);
        } else {
            return false;
        }
    }
    
    /**
     * Check for newer version
     *
     * Returns TRUE if $newVersion has a higher version number than $installedVersion.
     * ($newVersion > $installedVersion)
	 *
	 * @param string $installedVersion
	 * @param string $newVersion
	 * @return boolean
     */
    public function _isNewerVersion($installedVersion, $newVersion)
    {
        $arrInstalledVersion = explode('.', $installedVersion);
        $arrNewVersion = explode('.', $newVersion);
        $maxSubVersion = count($arrInstalledVersion) > count($arrNewVersion) ? count($arrInstalledVersion) : count($arrNewVersion);
        for ($nr = 0; $nr < $maxSubVersion; $nr++) {
            if (!isset($arrInstalledVersion[$nr])) {
                return true;
            } elseif (!isset($arrNewVersion[$nr])) {
                return false;
            } elseif ($arrNewVersion[$nr] > $arrInstalledVersion[$nr]) {
                return true;
            } elseif ($arrNewVersion[$nr] < $arrInstalledVersion[$nr]) {
                return false;
            }
        }
        
        return false;
    }
    
    public function getMySQLServerVersion()
    {
        global $objDb;
        
        $version = array();
        $objVersion = $objDb->ExecuteSQL('SELECT VERSION() AS mysqlversion');
        if (   $objVersion !== false 
            && count($objVersion) == 1 
            && preg_match('#^([0-9.]+)#', $objVersion['mysqlversion'], $version)
        ) {
            return $version[1];
        }
        
        return false;
    }

    public function checkMySQLVersion($requiredVersion)
    {
        $installedVersion = self::getMySQLServerVersion();
        if (!$installedVersion) {
            return false;
        }
        return !$this->_isNewerVersion($installedVersion, $requiredVersion);
    }
    
    /**
    * Sets the parameters to the correct path values
    * @param string $documentRoot Document root for this vHost
    * @param string $rootOffset Document root offset for this installation
    */
   public function fixPaths(&$documentRoot, &$rootOffset) {
       // calculate correct offset path
       // turning '/myoffset/somefile.php' into '/myoffset'
       $rootOffset = '';
       $directories = explode('/', $_SERVER['SCRIPT_NAME']);
       for ($i = 0; $i < count($directories) - 1; $i++) {
           if ($directories[$i] !== '') {
               $rootOffset .= '/'.$directories[$i];
           }
       }

       // fix wrong offset if another file than index.php was requested
       // turning '/myoffset/core_module/somemodule' into '/myoffset'
       $fileRoot = dirname(__FILE__);
       $nonOffset = preg_replace('#' . preg_quote($fileRoot) . '#', '', realpath($_SERVER['SCRIPT_FILENAME']));
       $nonOffsetParts = preg_split('#[/\\\\]#', $nonOffset);
       end($nonOffsetParts);
       unset($nonOffsetParts[key($nonOffsetParts)]);
       $nonOffset = implode('/', $nonOffsetParts);
       $rootOffset = preg_replace('#' . preg_quote($nonOffset) . '#', '', $rootOffset);

       // calculate correct document root
       // turning '/var/www/myoffset' into '/var/www'
       $documentRoot = '';
       $arrMatches = array();
       $scriptPath = str_replace('\\', '/', dirname(__FILE__));   
       if (preg_match("/(.*)(?:\/[\d\D]*){2}$/", $scriptPath, $arrMatches) == 1) {       
           $scriptPath = $arrMatches[0];
       }
       if (preg_match("#(.*)". preg_quote($rootOffset) ."#", $scriptPath, $arrMatches) == 1) {
           $documentRoot = $arrMatches[1];
       }   
    }
    
    public function downloadCustomizedFiles()
    {
        global $_CONFIG;
        
        if (!function_exists('gzopen')) {
          echo "Abort : Missing zlib extensions.<a href='" . basename(__FILE__) ."'>Click here</a>";
          exit();
        }
        
        if (   !empty($_SESSION['contrexx']['getCustomizingFinished'])
            && (!empty($_SESSION['contrexx']['customizedFiles'])
            || !empty($_SESSION['contrexx']['nonSystemFiles'])
            || !empty($_SESSION['contrexx']['additionalSystemFiles'])
            || !empty($_SESSION['contrexx']['oldSystemFiles']))
        ) {
            $addedFilesCount   = isset($_GET['addedFileCount']) ? $_GET['addedFileCount'] : 0;
            $addedSectionCount = isset($_GET['addedSectionCount']) ? $_GET['addedSectionCount'] : 0;
            $downloadFile      = isset($_GET['downloadFile']) ? $_GET['downloadFile'] : 0;
            $zipArchive        = $this->getTempPath() . "/customizing-". $_SERVER['HTTP_HOST'] .".zip";
            if (!empty($downloadFile)) {
                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=".basename($zipArchive));
                header("Content-Length: " . filesize($zipArchive));

                readfile($zipArchive);
                exit;
            }
            
            $archive = new PclZip($zipArchive);
            if (empty($addedFilesCount) && empty($addedSectionCount)) {
                if (file_exists($zipArchive)) {
                    @unlink($zipArchive);
                }
                
                $archive->create(
                    array(
                        array(
                            PCLZIP_ATT_FILE_NAME    => 'info.txt',
                            PCLZIP_ATT_FILE_CONTENT => 'Installed version : '. $_CONFIG['coreCmsVersion'] . PHP_EOL . 'Domain of website : http://'. $_SERVER['HTTP_HOST'] 
                        )
                    )
                );
            }
            
            $customizingSectionLists = array(
                'customizedFiles',
                'nonSystemFiles',
                'additionalSystemFiles',
                'oldSystemFiles'
            );
            
            $sectionCount = 0;
            foreach ($customizingSectionLists as $section) {
                if ($sectionCount < $addedSectionCount) {
                    $sectionCount++;
                    continue;
                }
                
                $status = $this->getAddedFilesStatus($archive, $section, $addedFilesCount);
                if (is_array($status) && $status['status'] == 'error') {
                    echo json_encode(array_merge($status, array('sectionCount' => $sectionCount)));
                    exit();
                }
                $addedFilesCount = 0;
                $sectionCount++;
            }
            
            echo json_encode(array('status' => 'success'));
            exit();
            
        } else {
            echo "Customizing Not Found.<a href='" . basename(__FILE__) ."'>Click here</a>";
            die;
        }
    }
    
    public function getAddedFilesStatus($archive, $section, $addedFilesCount)
    {
        global $path;
        
        if (   empty($archive)
            || empty($section)
            || empty($_SESSION['contrexx'][$section])
        ) {
            return false;
        }
        
        $filesCount = 0;
        $filesArray = !empty($addedFilesCount) 
                      ? array_slice(
                            $_SESSION['contrexx'][$section], 
                            $addedFilesCount, 
                            count($_SESSION['contrexx'][$section]) - $filesCount
                        )
                      : $_SESSION['contrexx'][$section];
        foreach ($filesArray as $file) {
            if (!$this->checkMemoryLimit() || !$this->checkTimeoutLimit()) {
                return array('status' => 'error', 'fileCount' => $addedFilesCount + $filesCount);
            }

            $filePath = is_array($file) ? $file['file'] : $file;
            
            $archive->add(
                $path.'/'. $filePath,
                PCLZIP_OPT_ADD_PATH,
                $section,
                PCLZIP_OPT_REMOVE_PATH,
                $path
            );
            $filesCount++;
        }
        return true;
    }
}


/*
 *  Copyright (C) 2012
 *     Ed Rackham (http://github.com/a1phanumeric/PHP-MySQL-Class)
 *  Changes to Version 0.8.1 copyright (C) 2013
 *	Christopher Harms (http://github.com/neurotroph)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// MySQL Class v0.8.1
class MySQL {

    // Base variables
    var $hostname; // MySQL Hostname
    var $username; // MySQL Username
    var $password; // MySQL Password
    var $database; // MySQL Database
    var $databaseLink;  // Database Connection Link

    /*     * ******************
     * Class Constructor *
     * ****************** */

    function __construct($database, $username, $password, $hostname = 'localhost', $port = 3306) {

        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->hostname = $hostname;

        $this->databaseLink = new PDO('mysql:host=' . $this->hostname . ';dbname=' . $this->database, $this->username, $this->password);
        $this->databaseLink->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Executes MySQL query
    function ExecuteSQL($query) {
        $objResult = $this->databaseLink->prepare($query);
        $objResult->execute();
        if ($objResult->rowCount() > 0) {
            return  $objResult->rowCount() == 1
                    ? $objResult->fetch(PDO::FETCH_ASSOC)
                    : $objResult->fetchAll();
        }
        
        return true;
        $this->databaseLink->close();
    }
}

/**
 * PEAR, the PHP Extension and Application Repository
 *
 * PEAR class and PEAR_Error class
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   pear
 * @package    PEAR
 * @author     Sterling Hughes <sterling@php.net>
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: PEAR.php,v 1.96 2005/09/21 00:12:35 cellog Exp $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**#@+
 * ERROR constants
 */
define('PEAR_ERROR_RETURN',     1);
define('PEAR_ERROR_PRINT',      2);
define('PEAR_ERROR_TRIGGER',    4);
define('PEAR_ERROR_DIE',        8);
define('PEAR_ERROR_CALLBACK',  16);
/**
 * WARNING: obsolete
 * @deprecated
 */
define('PEAR_ERROR_EXCEPTION', 32);
/**#@-*/
define('PEAR_ZE2', (function_exists('version_compare') &&
                    version_compare(zend_version(), "2-dev", "ge")));

if (substr(PHP_OS, 0, 3) == 'WIN') {
    define('OS_WINDOWS', true);
    define('OS_UNIX',    false);
    define('PEAR_OS',    'Windows');
} else {
    define('OS_WINDOWS', false);
    define('OS_UNIX',    true);
    define('PEAR_OS',    'Unix'); // blatant assumption
}

// instant backwards compatibility
if (!defined('PATH_SEPARATOR')) {
    if (OS_WINDOWS) {
        define('PATH_SEPARATOR', ';');
    } else {
        define('PATH_SEPARATOR', ':');
    }
}

$GLOBALS['_PEAR_default_error_mode']     = PEAR_ERROR_RETURN;
$GLOBALS['_PEAR_default_error_options']  = E_USER_NOTICE;
$GLOBALS['_PEAR_destructor_object_list'] = array();
$GLOBALS['_PEAR_shutdown_funcs']         = array();
$GLOBALS['_PEAR_error_handler_stack']    = array();

@ini_set('track_errors', true);

/**
 * Base class for other PEAR classes.  Provides rudimentary
 * emulation of destructors.
 *
 * If you want a destructor in your class, inherit PEAR and make a
 * destructor method called _yourclassname (same name as the
 * constructor, but with a "_" prefix).  Also, in your constructor you
 * have to call the PEAR constructor: $this->PEAR();.
 * The destructor method will be called without parameters.  Note that
 * at in some SAPI implementations (such as Apache), any output during
 * the request shutdown (in which destructors are called) seems to be
 * discarded.  If you need to get any debug information from your
 * destructor, use error_log(), syslog() or something similar.
 *
 * IMPORTANT! To use the emulated destructors you need to create the
 * objects by reference: $obj =& new PEAR_child;
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: 1.4.3
 * @link       http://pear.php.net/package/PEAR
 * @see        PEAR_Error
 * @since      Class available since PHP 4.0.2
 * @link        http://pear.php.net/manual/en/core.pear.php#core.pear.pear
 */
class PEAR
{
    // {{{ properties

    /**
     * Whether to enable internal debug messages.
     *
     * @var     bool
     * @access  private
     */
    var $_debug = false;

    /**
     * Default error mode for this object.
     *
     * @var     int
     * @access  private
     */
    var $_default_error_mode = null;

    /**
     * Default error options used for this object when error mode
     * is PEAR_ERROR_TRIGGER.
     *
     * @var     int
     * @access  private
     */
    var $_default_error_options = null;

    /**
     * Default error handler (callback) for this object, if error mode is
     * PEAR_ERROR_CALLBACK.
     *
     * @var     string
     * @access  private
     */
    var $_default_error_handler = '';

    /**
     * Which class to use for error objects.
     *
     * @var     string
     * @access  private
     */
    var $_error_class = 'PEAR_Error';

    /**
     * An array of expected errors.
     *
     * @var     array
     * @access  private
     */
    var $_expected_errors = array();

    // }}}

    // {{{ constructor

    /**
     * Constructor.  Registers this object in
     * $_PEAR_destructor_object_list for destructor emulation if a
     * destructor object exists.
     *
     * @param string $error_class  (optional) which class to use for
     *        error objects, defaults to PEAR_Error.
     * @access public
     * @return void
     */
    function PEAR($error_class = null)
    {
        $classname = strtolower(get_class($this));
        if ($this->_debug) {
            print "PEAR constructor called, class=$classname\n";
        }
        if ($error_class !== null) {
            $this->_error_class = $error_class;
        }
        while ($classname && strcasecmp($classname, "pear")) {
            $destructor = "_$classname";
            if (method_exists($this, $destructor)) {
                global $_PEAR_destructor_object_list;
                $_PEAR_destructor_object_list[] = &$this;
                if (!isset($GLOBALS['_PEAR_SHUTDOWN_REGISTERED'])) {
                    register_shutdown_function("_PEAR_call_destructors");
                    $GLOBALS['_PEAR_SHUTDOWN_REGISTERED'] = true;
                }
                break;
            } else {
                $classname = get_parent_class($classname);
            }
        }
    }

    // }}}
    // {{{ destructor

    /**
     * Destructor (the emulated type of...).  Does nothing right now,
     * but is included for forward compatibility, so subclass
     * destructors should always call it.
     *
     * See the note in the class desciption about output from
     * destructors.
     *
     * @access public
     * @return void
     */
    function _PEAR() {
        if ($this->_debug) {
            printf("PEAR destructor called, class=%s\n", strtolower(get_class($this)));
        }
    }

    // }}}
    // {{{ getStaticProperty()

    /**
    * If you have a class that's mostly/entirely static, and you need static
    * properties, you can use this method to simulate them. Eg. in your method(s)
    * do this: $myVar = &PEAR::getStaticProperty('myclass', 'myVar');
    * You MUST use a reference, or they will not persist!
    *
    * @access public
    * @param  string $class  The calling classname, to prevent clashes
    * @param  string $var    The variable to retrieve.
    * @return mixed   A reference to the variable. If not set it will be
    *                 auto initialised to NULL.
    */
    function &getStaticProperty($class, $var)
    {
        static $properties;
        return $properties[$class][$var];
    }

    // }}}
    // {{{ registerShutdownFunc()

    /**
    * Use this function to register a shutdown method for static
    * classes.
    *
    * @access public
    * @param  mixed $func  The function name (or array of class/method) to call
    * @param  mixed $args  The arguments to pass to the function
    * @return void
    */
    function registerShutdownFunc($func, $args = array())
    {
        $GLOBALS['_PEAR_shutdown_funcs'][] = array($func, $args);
    }

    // }}}
    // {{{ isError()

    /**
     * Tell whether a value is a PEAR error.
     *
     * @param   mixed $data   the value to test
     * @param   int   $code   if $data is an error object, return true
     *                        only if $code is a string and
     *                        $obj->getMessage() == $code or
     *                        $code is an integer and $obj->getCode() == $code
     * @access  public
     * @return  bool    true if parameter is an error
     */
    function isError($data, $code = null)
    {
        if (is_a($data, 'PEAR_Error')) {
            if (is_null($code)) {
                return true;
            } elseif (is_string($code)) {
                return $data->getMessage() == $code;
            } else {
                return $data->getCode() == $code;
            }
        }
        return false;
    }

    // }}}
    // {{{ setErrorHandling()

    /**
     * Sets how errors generated by this object should be handled.
     * Can be invoked both in objects and statically.  If called
     * statically, setErrorHandling sets the default behaviour for all
     * PEAR objects.  If called in an object, setErrorHandling sets
     * the default behaviour for that object.
     *
     * @param int $mode
     *        One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
     *        PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
     *        PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION.
     *
     * @param mixed $options
     *        When $mode is PEAR_ERROR_TRIGGER, this is the error level (one
     *        of E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
     *
     *        When $mode is PEAR_ERROR_CALLBACK, this parameter is expected
     *        to be the callback function or method.  A callback
     *        function is a string with the name of the function, a
     *        callback method is an array of two elements: the element
     *        at index 0 is the object, and the element at index 1 is
     *        the name of the method to call in the object.
     *
     *        When $mode is PEAR_ERROR_PRINT or PEAR_ERROR_DIE, this is
     *        a printf format string used when printing the error
     *        message.
     *
     * @access public
     * @return void
     * @see PEAR_ERROR_RETURN
     * @see PEAR_ERROR_PRINT
     * @see PEAR_ERROR_TRIGGER
     * @see PEAR_ERROR_DIE
     * @see PEAR_ERROR_CALLBACK
     * @see PEAR_ERROR_EXCEPTION
     *
     * @since PHP 4.0.5
     */

    function setErrorHandling($mode = null, $options = null)
    {
        if (isset($this) && is_a($this, 'PEAR')) {
            $setmode     = &$this->_default_error_mode;
            $setoptions  = &$this->_default_error_options;
        } else {
            $setmode     = &$GLOBALS['_PEAR_default_error_mode'];
            $setoptions  = &$GLOBALS['_PEAR_default_error_options'];
        }

        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $setmode = $mode;
                $setoptions = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $setmode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $setoptions = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
    }

    // }}}
    // {{{ expectError()

    /**
     * This method is used to tell which errors you expect to get.
     * Expected errors are always returned with error mode
     * PEAR_ERROR_RETURN.  Expected error codes are stored in a stack,
     * and this method pushes a new element onto it.  The list of
     * expected errors are in effect until they are popped off the
     * stack with the popExpect() method.
     *
     * Note that this method can not be called statically
     *
     * @param mixed $code a single error code or an array of error codes to expect
     *
     * @return int     the new depth of the "expected errors" stack
     * @access public
     */
    function expectError($code = '*')
    {
        if (is_array($code)) {
            array_push($this->_expected_errors, $code);
        } else {
            array_push($this->_expected_errors, array($code));
        }
        return sizeof($this->_expected_errors);
    }

    // }}}
    // {{{ popExpect()

    /**
     * This method pops one element off the expected error codes
     * stack.
     *
     * @return array   the list of error codes that were popped
     */
    function popExpect()
    {
        return array_pop($this->_expected_errors);
    }

    // }}}
    // {{{ _checkDelExpect()

    /**
     * This method checks unsets an error code if available
     *
     * @param mixed error code
     * @return bool true if the error code was unset, false otherwise
     * @access private
     * @since PHP 4.3.0
     */
    function _checkDelExpect($error_code)
    {
        $deleted = false;

        foreach ($this->_expected_errors AS $key => $error_array) {
            if (in_array($error_code, $error_array)) {
                unset($this->_expected_errors[$key][array_search($error_code, $error_array)]);
                $deleted = true;
            }

            // clean up empty arrays
            if (0 == count($this->_expected_errors[$key])) {
                unset($this->_expected_errors[$key]);
            }
        }
        return $deleted;
    }

    // }}}
    // {{{ delExpect()

    /**
     * This method deletes all occurences of the specified element from
     * the expected error codes stack.
     *
     * @param  mixed $error_code error code that should be deleted
     * @return mixed list of error codes that were deleted or error
     * @access public
     * @since PHP 4.3.0
     */
    function delExpect($error_code)
    {
        $deleted = false;

        if ((is_array($error_code) && (0 != count($error_code)))) {
            // $error_code is a non-empty array here;
            // we walk through it trying to unset all
            // values
            foreach($error_code as $key => $error) {
                if ($this->_checkDelExpect($error)) {
                    $deleted =  true;
                } else {
                    $deleted = false;
                }
            }
            return $deleted ? true : PEAR::raiseError("The expected error you submitted does not exist"); // IMPROVE ME
        } elseif (!empty($error_code)) {
            // $error_code comes alone, trying to unset it
            if ($this->_checkDelExpect($error_code)) {
                return true;
            } else {
                return PEAR::raiseError("The expected error you submitted does not exist"); // IMPROVE ME
            }
        } else {
            // $error_code is empty
            return PEAR::raiseError("The expected error you submitted is empty"); // IMPROVE ME
        }
    }

    // }}}
    // {{{ raiseError()

    /**
     * This method is a wrapper that returns an instance of the
     * configured error class with this object's default error
     * handling applied.  If the $mode and $options parameters are not
     * specified, the object's defaults are used.
     *
     * @param mixed $message a text error message or a PEAR error object
     *
     * @param int $code      a numeric error code (it is up to your class
     *                  to define these if you want to use codes)
     *
     * @param int $mode      One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
     *                  PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
     *                  PEAR_ERROR_CALLBACK, PEAR_ERROR_EXCEPTION.
     *
     * @param mixed $options If $mode is PEAR_ERROR_TRIGGER, this parameter
     *                  specifies the PHP-internal error level (one of
     *                  E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
     *                  If $mode is PEAR_ERROR_CALLBACK, this
     *                  parameter specifies the callback function or
     *                  method.  In other error modes this parameter
     *                  is ignored.
     *
     * @param string $userinfo If you need to pass along for example debug
     *                  information, this parameter is meant for that.
     *
     * @param string $error_class The returned error object will be
     *                  instantiated from this class, if specified.
     *
     * @param bool $skipmsg If true, raiseError will only pass error codes,
     *                  the error message parameter will be dropped.
     *
     * @access public
     * @return object   a PEAR error object
     * @see PEAR::setErrorHandling
     * @since PHP 4.0.5
     */
    function &raiseError($message = null,
                         $code = null,
                         $mode = null,
                         $options = null,
                         $userinfo = null,
                         $error_class = null,
                         $skipmsg = false)
    {
        // The error is yet a PEAR error object
        if (is_object($message)) {
            $code        = $message->getCode();
            $userinfo    = $message->getUserInfo();
            $error_class = $message->getType();
            $message->error_message_prefix = '';
            $message     = $message->getMessage();
        }

        if (isset($this) && isset($this->_expected_errors) && sizeof($this->_expected_errors) > 0 && sizeof($exp = end($this->_expected_errors))) {
            if ($exp[0] == "*" ||
                (is_int(reset($exp)) && in_array($code, $exp)) ||
                (is_string(reset($exp)) && in_array($message, $exp))) {
                $mode = PEAR_ERROR_RETURN;
            }
        }
        // No mode given, try global ones
        if ($mode === null) {
            // Class error handler
            if (isset($this) && isset($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            // Global error handler
            } elseif (isset($GLOBALS['_PEAR_default_error_mode'])) {
                $mode    = $GLOBALS['_PEAR_default_error_mode'];
                $options = $GLOBALS['_PEAR_default_error_options'];
            }
        }

        if ($error_class !== null) {
            $ec = $error_class;
        } elseif (isset($this) && isset($this->_error_class)) {
            $ec = $this->_error_class;
        } else {
            $ec = 'PEAR_Error';
        }
        if ($skipmsg) {
            $a = new $ec($code, $mode, $options, $userinfo);
            return $a;
        } else {
            $a = new $ec($message, $code, $mode, $options, $userinfo);
            return $a;
        }
    }

    // }}}
    // {{{ throwError()

    /**
     * Simpler form of raiseError with fewer options.  In most cases
     * message, code and userinfo are enough.
     *
     * @param string $message
     *
     */
    function &throwError($message = null,
                         $code = null,
                         $userinfo = null)
    {
        if (isset($this) && is_a($this, 'PEAR')) {
            $a = &$this->raiseError($message, $code, null, null, $userinfo);
            return $a;
        } else {
            $a = &PEAR::raiseError($message, $code, null, null, $userinfo);
            return $a;
        }
    }

    // }}}
    function staticPushErrorHandling($mode, $options = null)
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        $def_mode    = &$GLOBALS['_PEAR_default_error_mode'];
        $def_options = &$GLOBALS['_PEAR_default_error_options'];
        $stack[] = array($def_mode, $def_options);
        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $def_mode = $mode;
                $def_options = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $def_mode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $def_options = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
        $stack[] = array($mode, $options);
        return true;
    }

    function staticPopErrorHandling()
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        $setmode     = &$GLOBALS['_PEAR_default_error_mode'];
        $setoptions  = &$GLOBALS['_PEAR_default_error_options'];
        array_pop($stack);
        list($mode, $options) = $stack[sizeof($stack) - 1];
        array_pop($stack);
        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $setmode = $mode;
                $setoptions = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $setmode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $setoptions = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
        return true;
    }

    // {{{ pushErrorHandling()

    /**
     * Push a new error handler on top of the error handler options stack. With this
     * you can easily override the actual error handler for some code and restore
     * it later with popErrorHandling.
     *
     * @param mixed $mode (same as setErrorHandling)
     * @param mixed $options (same as setErrorHandling)
     *
     * @return bool Always true
     *
     * @see PEAR::setErrorHandling
     */
    function pushErrorHandling($mode, $options = null)
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        if (isset($this) && is_a($this, 'PEAR')) {
            $def_mode    = &$this->_default_error_mode;
            $def_options = &$this->_default_error_options;
        } else {
            $def_mode    = &$GLOBALS['_PEAR_default_error_mode'];
            $def_options = &$GLOBALS['_PEAR_default_error_options'];
        }
        $stack[] = array($def_mode, $def_options);

        if (isset($this) && is_a($this, 'PEAR')) {
            $this->setErrorHandling($mode, $options);
        } else {
            PEAR::setErrorHandling($mode, $options);
        }
        $stack[] = array($mode, $options);
        return true;
    }

    // }}}
    // {{{ popErrorHandling()

    /**
    * Pop the last error handler used
    *
    * @return bool Always true
    *
    * @see PEAR::pushErrorHandling
    */
    function popErrorHandling()
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        array_pop($stack);
        list($mode, $options) = $stack[sizeof($stack) - 1];
        array_pop($stack);
        if (isset($this) && is_a($this, 'PEAR')) {
            $this->setErrorHandling($mode, $options);
        } else {
            PEAR::setErrorHandling($mode, $options);
        }
        return true;
    }

    // }}}
    // {{{ loadExtension()

    /**
    * OS independant PHP extension load. Remember to take care
    * on the correct extension name for case sensitive OSes.
    *
    * @param string $ext The extension name
    * @return bool Success or not on the dl() call
    */
    function loadExtension($ext)
    {
        if (!extension_loaded($ext)) {
            // if either returns true dl() will produce a FATAL error, stop that
            if ((ini_get('enable_dl') != 1) || (ini_get('safe_mode') == 1)) {
                return false;
            }
            if (OS_WINDOWS) {
                $suffix = '.dll';
            } elseif (PHP_OS == 'HP-UX') {
                $suffix = '.sl';
            } elseif (PHP_OS == 'AIX') {
                $suffix = '.a';
            } elseif (PHP_OS == 'OSX') {
                $suffix = '.bundle';
            } else {
                $suffix = '.so';
            }
            return @dl('php_'.$ext.$suffix) || @dl($ext.$suffix);
        }
        return true;
    }

    // }}}
}

// {{{ _PEAR_call_destructors()

function _PEAR_call_destructors()
{
    global $_PEAR_destructor_object_list;
    if (is_array($_PEAR_destructor_object_list) &&
        sizeof($_PEAR_destructor_object_list))
    {
        reset($_PEAR_destructor_object_list);
        if (@PEAR::getStaticProperty('PEAR', 'destructlifo')) {
            $_PEAR_destructor_object_list = array_reverse($_PEAR_destructor_object_list);
        }
        while (list($k, $objref) = each($_PEAR_destructor_object_list)) {
            $classname = get_class($objref);
            while ($classname) {
                $destructor = "_$classname";
                if (method_exists($objref, $destructor)) {
                    $objref->$destructor();
                    break;
                } else {
                    $classname = get_parent_class($classname);
                }
            }
        }
        // Empty the object list to ensure that destructors are
        // not called more than once.
        $_PEAR_destructor_object_list = array();
    }

    // Now call the shutdown functions
    if (is_array($GLOBALS['_PEAR_shutdown_funcs']) AND !empty($GLOBALS['_PEAR_shutdown_funcs'])) {
        foreach ($GLOBALS['_PEAR_shutdown_funcs'] as $value) {
            call_user_func_array($value[0], $value[1]);
        }
    }
}

// }}}
/**
 * Standard PEAR error class for PHP 4
 *
 * This class is supserseded by {@link PEAR_Exception} in PHP 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Gregory Beaver <cellog@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: 1.4.3
 * @link       http://pear.php.net/manual/en/core.pear.pear-error.php
 * @see        PEAR::raiseError(), PEAR::throwError()
 * @since      Class available since PHP 4.0.2
 */
class PEAR_Error
{
    // {{{ properties

    var $error_message_prefix = '';
    var $mode                 = PEAR_ERROR_RETURN;
    var $level                = E_USER_NOTICE;
    var $code                 = -1;
    var $message              = '';
    var $userinfo             = '';
    var $backtrace            = null;

    // }}}
    // {{{ constructor

    /**
     * PEAR_Error constructor
     *
     * @param string $message  message
     *
     * @param int $code     (optional) error code
     *
     * @param int $mode     (optional) error mode, one of: PEAR_ERROR_RETURN,
     * PEAR_ERROR_PRINT, PEAR_ERROR_DIE, PEAR_ERROR_TRIGGER,
     * PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION
     *
     * @param mixed $options   (optional) error level, _OR_ in the case of
     * PEAR_ERROR_CALLBACK, the callback function or object/method
     * tuple.
     *
     * @param string $userinfo (optional) additional user/debug info
     *
     * @access public
     *
     */
    function PEAR_Error($message = 'unknown error', $code = null,
                        $mode = null, $options = null, $userinfo = null)
    {
        if ($mode === null) {
            $mode = PEAR_ERROR_RETURN;
        }
        $this->message   = $message;
        $this->code      = $code;
        $this->mode      = $mode;
        $this->userinfo  = $userinfo;
        if (function_exists("debug_backtrace")) {
            if (@!PEAR::getStaticProperty('PEAR_Error', 'skiptrace')) {
                $this->backtrace = debug_backtrace();
            }
        }
        if ($mode & PEAR_ERROR_CALLBACK) {
            $this->level = E_USER_NOTICE;
            $this->callback = $options;
        } else {
            if ($options === null) {
                $options = E_USER_NOTICE;
            }
            $this->level = $options;
            $this->callback = null;
        }
        if ($this->mode & PEAR_ERROR_PRINT) {
            if (is_null($options) || is_int($options)) {
                $format = "%s";
            } else {
                $format = $options;
            }
            printf($format, $this->getMessage());
        }
        if ($this->mode & PEAR_ERROR_TRIGGER) {
            trigger_error($this->getMessage(), $this->level);
        }
        if ($this->mode & PEAR_ERROR_DIE) {
            $msg = $this->getMessage();
            if (is_null($options) || is_int($options)) {
                $format = "%s";
                if (substr($msg, -1) != "\n") {
                    $msg .= "\n";
                }
            } else {
                $format = $options;
            }
            die(sprintf($format, $msg));
        }
        if ($this->mode & PEAR_ERROR_CALLBACK) {
            if (is_callable($this->callback)) {
                call_user_func($this->callback, $this);
            }
        }
        if ($this->mode & PEAR_ERROR_EXCEPTION) {
            trigger_error("PEAR_ERROR_EXCEPTION is obsolete, use class PEAR_Exception for exceptions", E_USER_WARNING);
            eval('$e = new Exception($this->message, $this->code);throw($e);');
        }
    }

    // }}}
    // {{{ getMode()

    /**
     * Get the error mode from an error object.
     *
     * @return int error mode
     * @access public
     */
    function getMode() {
        return $this->mode;
    }

    // }}}
    // {{{ getCallback()

    /**
     * Get the callback function/method from an error object.
     *
     * @return mixed callback function or object/method array
     * @access public
     */
    function getCallback() {
        return $this->callback;
    }

    // }}}
    // {{{ getMessage()


    /**
     * Get the error message from an error object.
     *
     * @return  string  full error message
     * @access public
     */
    function getMessage()
    {
        return ($this->error_message_prefix . $this->message);
    }


    // }}}
    // {{{ getCode()

    /**
     * Get error code from an error object
     *
     * @return int error code
     * @access public
     */
     function getCode()
     {
        return $this->code;
     }

    // }}}
    // {{{ getType()

    /**
     * Get the name of this error/exception.
     *
     * @return string error/exception name (type)
     * @access public
     */
    function getType()
    {
        return get_class($this);
    }

    // }}}
    // {{{ getUserInfo()

    /**
     * Get additional user-supplied information.
     *
     * @return string user-supplied information
     * @access public
     */
    function getUserInfo()
    {
        return $this->userinfo;
    }

    // }}}
    // {{{ getDebugInfo()

    /**
     * Get additional debug information supplied by the application.
     *
     * @return string debug information
     * @access public
     */
    function getDebugInfo()
    {
        return $this->getUserInfo();
    }

    // }}}
    // {{{ getBacktrace()

    /**
     * Get the call backtrace from where the error was generated.
     * Supported with PHP 4.3.0 or newer.
     *
     * @param int $frame (optional) what frame to fetch
     * @return array Backtrace, or NULL if not available.
     * @access public
     */
    function getBacktrace($frame = null)
    {
        if (defined('PEAR_IGNORE_BACKTRACE')) {
            return null;
        }
        if ($frame === null) {
            return $this->backtrace;
        }
        return $this->backtrace[$frame];
    }

    // }}}
    // {{{ addUserInfo()

    function addUserInfo($info)
    {
        if (empty($this->userinfo)) {
            $this->userinfo = $info;
        } else {
            $this->userinfo .= " ** $info";
        }
    }

    // }}}
    // {{{ toString()

    /**
     * Make a string representation of this object.
     *
     * @return string a string with an object summary
     * @access public
     */
    function toString() {
        $modes = array();
        $levels = array(E_USER_NOTICE  => 'notice',
                        E_USER_WARNING => 'warning',
                        E_USER_ERROR   => 'error');
        if ($this->mode & PEAR_ERROR_CALLBACK) {
            if (is_array($this->callback)) {
                $callback = (is_object($this->callback[0]) ?
                    strtolower(get_class($this->callback[0])) :
                    $this->callback[0]) . '::' .
                    $this->callback[1];
            } else {
                $callback = $this->callback;
            }
            return sprintf('[%s: message="%s" code=%d mode=callback '.
                           'callback=%s prefix="%s" info="%s"]',
                           strtolower(get_class($this)), $this->message, $this->code,
                           $callback, $this->error_message_prefix,
                           $this->userinfo);
        }
        if ($this->mode & PEAR_ERROR_PRINT) {
            $modes[] = 'print';
        }
        if ($this->mode & PEAR_ERROR_TRIGGER) {
            $modes[] = 'trigger';
        }
        if ($this->mode & PEAR_ERROR_DIE) {
            $modes[] = 'die';
        }
        if ($this->mode & PEAR_ERROR_RETURN) {
            $modes[] = 'return';
        }
        return sprintf('[%s: message="%s" code=%d mode=%s level=%s '.
                       'prefix="%s" info="%s"]',
                       strtolower(get_class($this)), $this->message, $this->code,
                       implode("|", $modes), $levels[$this->level],
                       $this->error_message_prefix,
                       $this->userinfo);
    }

    // }}}
}

/**
 * Net_Socket
 *
 * PHP Version 4
 *
 * Copyright (c) 1997-2013 The PHP Group
 *
 * This source file is subject to version 2.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available at through the world-wide-web at
 * http://www.php.net/license/2_02.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * Authors: Stig Bakken <ssb@php.net>
 *          Chuck Hagenbuch <chuck@horde.org>
 *
 * @category  Net
 * @package   Net_Socket
 * @author    Stig Bakken <ssb@php.net>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @copyright 1997-2003 The PHP Group
 * @license   http://www.php.net/license/2_02.txt PHP 2.02
 * @link      http://pear.php.net/packages/Net_Socket
 */

define('NET_SOCKET_READ', 1);
define('NET_SOCKET_WRITE', 2);
define('NET_SOCKET_ERROR', 4);

/**
 * Generalized Socket class.
 *
 * @category  Net
 * @package   Net_Socket
 * @author    Stig Bakken <ssb@php.net>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @copyright 1997-2003 The PHP Group
 * @license   http://www.php.net/license/2_02.txt PHP 2.02
 * @link      http://pear.php.net/packages/Net_Socket
 */
class Net_Socket extends PEAR
{
    /**
     * Socket file pointer.
     * @var resource $fp
     */
    var $fp = null;

    /**
     * Whether the socket is blocking. Defaults to true.
     * @var boolean $blocking
     */
    var $blocking = true;

    /**
     * Whether the socket is persistent. Defaults to false.
     * @var boolean $persistent
     */
    var $persistent = false;

    /**
     * The IP address to connect to.
     * @var string $addr
     */
    var $addr = '';

    /**
     * The port number to connect to.
     * @var integer $port
     */
    var $port = 0;

    /**
     * Number of seconds to wait on socket operations before assuming
     * there's no more data. Defaults to no timeout.
     * @var integer|float $timeout
     */
    var $timeout = null;

    /**
     * Number of bytes to read at a time in readLine() and
     * readAll(). Defaults to 2048.
     * @var integer $lineLength
     */
    var $lineLength = 2048;

    /**
     * The string to use as a newline terminator. Usually "\r\n" or "\n".
     * @var string $newline
     */
    var $newline = "\r\n";

    /**
     * Connect to the specified port. If called when the socket is
     * already connected, it disconnects and connects again.
     *
     * @param string  $addr       IP address or host name (may be with protocol prefix).
     * @param integer $port       TCP port number.
     * @param boolean $persistent (optional) Whether the connection is
     *                            persistent (kept open between requests
     *                            by the web server).
     * @param integer $timeout    (optional) Connection socket timeout.
     * @param array   $options    See options for stream_context_create.
     *
     * @access public
     *
     * @return boolean|PEAR_Error  True on success or a PEAR_Error on failure.
     */
    function connect($addr, $port = 0, $persistent = null,
                     $timeout = null, $options = null)
    {
        if (is_resource($this->fp)) {
            @fclose($this->fp);
            $this->fp = null;
        }

        if (!$addr) {
            return $this->raiseError('$addr cannot be empty');
        } else if (strspn($addr, ':.0123456789') == strlen($addr)) {
            $this->addr = strpos($addr, ':') !== false ? '['.$addr.']' : $addr;
        } else {
            $this->addr = $addr;
        }

        $this->port = $port % 65536;

        if ($persistent !== null) {
            $this->persistent = $persistent;
        }

        $openfunc = $this->persistent ? 'pfsockopen' : 'fsockopen';
        $errno    = 0;
        $errstr   = '';

        $old_track_errors = @ini_set('track_errors', 1);

        if ($timeout <= 0) {
            $timeout = @ini_get('default_socket_timeout');
        }

        if ($options && function_exists('stream_context_create')) {
            $context = stream_context_create($options);

            // Since PHP 5 fsockopen doesn't allow context specification
            if (function_exists('stream_socket_client')) {
                $flags = STREAM_CLIENT_CONNECT;

                if ($this->persistent) {
                    $flags = STREAM_CLIENT_PERSISTENT;
                }

                $addr = $this->addr . ':' . $this->port;
                $fp   = stream_socket_client($addr, $errno, $errstr,
                                             $timeout, $flags, $context);
            } else {
                $fp = @$openfunc($this->addr, $this->port, $errno,
                                 $errstr, $timeout, $context);
            }
        } else {
            $fp = @$openfunc($this->addr, $this->port, $errno, $errstr, $timeout);
        }

        if (!$fp) {
            if ($errno == 0 && !strlen($errstr) && isset($php_errormsg)) {
                $errstr = $php_errormsg;
            }
            @ini_set('track_errors', $old_track_errors);
            return $this->raiseError($errstr, $errno);
        }

        @ini_set('track_errors', $old_track_errors);
        $this->fp = $fp;
        $this->setTimeout();
        return $this->setBlocking($this->blocking);
    }

    /**
     * Disconnects from the peer, closes the socket.
     *
     * @access public
     * @return mixed true on success or a PEAR_Error instance otherwise
     */
    function disconnect()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        @fclose($this->fp);
        $this->fp = null;
        return true;
    }

    /**
     * Set the newline character/sequence to use.
     *
     * @param string $newline  Newline character(s)
     * @return boolean True
     */
    function setNewline($newline)
    {
        $this->newline = $newline;
        return true;
    }

    /**
     * Find out if the socket is in blocking mode.
     *
     * @access public
     * @return boolean  The current blocking mode.
     */
    function isBlocking()
    {
        return $this->blocking;
    }

    /**
     * Sets whether the socket connection should be blocking or
     * not. A read call to a non-blocking socket will return immediately
     * if there is no data available, whereas it will block until there
     * is data for blocking sockets.
     *
     * @param boolean $mode True for blocking sockets, false for nonblocking.
     *
     * @access public
     * @return mixed true on success or a PEAR_Error instance otherwise
     */
    function setBlocking($mode)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $this->blocking = $mode;
        stream_set_blocking($this->fp, (int)$this->blocking);
        return true;
    }

    /**
     * Sets the timeout value on socket descriptor,
     * expressed in the sum of seconds and microseconds
     *
     * @param integer $seconds      Seconds.
     * @param integer $microseconds Microseconds, optional.
     *
     * @access public
     * @return mixed True on success or false on failure or
     *               a PEAR_Error instance when not connected
     */
    function setTimeout($seconds = null, $microseconds = null)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        if ($seconds === null && $microseconds === null) {
            $seconds      = (int) $this->timeout;
            $microseconds = (int) (($this->timeout - $seconds) * 1000000);
        } else {
            $this->timeout = $seconds + $microseconds/1000000;
        }

        if ($this->timeout > 0) {
            return stream_set_timeout($this->fp, (int) $seconds, (int) $microseconds);
        }
        else {
            return false;
        }
    }

    /**
     * Sets the file buffering size on the stream.
     * See php's stream_set_write_buffer for more information.
     *
     * @param integer $size Write buffer size.
     *
     * @access public
     * @return mixed on success or an PEAR_Error object otherwise
     */
    function setWriteBuffer($size)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $returned = stream_set_write_buffer($this->fp, $size);
        if ($returned == 0) {
            return true;
        }
        return $this->raiseError('Cannot set write buffer.');
    }

    /**
     * Returns information about an existing socket resource.
     * Currently returns four entries in the result array:
     *
     * <p>
     * timed_out (bool) - The socket timed out waiting for data<br>
     * blocked (bool) - The socket was blocked<br>
     * eof (bool) - Indicates EOF event<br>
     * unread_bytes (int) - Number of bytes left in the socket buffer<br>
     * </p>
     *
     * @access public
     * @return mixed Array containing information about existing socket
     *               resource or a PEAR_Error instance otherwise
     */
    function getStatus()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return stream_get_meta_data($this->fp);
    }

    /**
     * Get a specified line of data
     *
     * @param int $size Reading ends when size - 1 bytes have been read,
     *                  or a newline or an EOF (whichever comes first).
     *                  If no size is specified, it will keep reading from
     *                  the stream until it reaches the end of the line.
     *
     * @access public
     * @return mixed $size bytes of data from the socket, or a PEAR_Error if
     *         not connected. If an error occurs, FALSE is returned.
     */
    function gets($size = null)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        if (is_null($size)) {
            return @fgets($this->fp);
        } else {
            return @fgets($this->fp, $size);
        }
    }

    /**
     * Read a specified amount of data. This is guaranteed to return,
     * and has the added benefit of getting everything in one fread()
     * chunk; if you know the size of the data you're getting
     * beforehand, this is definitely the way to go.
     *
     * @param integer $size The number of bytes to read from the socket.
     *
     * @access public
     * @return $size bytes of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    function read($size)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return @fread($this->fp, $size);
    }

    /**
     * Write a specified amount of data.
     *
     * @param string  $data      Data to write.
     * @param integer $blocksize Amount of data to write at once.
     *                           NULL means all at once.
     *
     * @access public
     * @return mixed If the socket is not connected, returns an instance of
     *               PEAR_Error.
     *               If the write succeeds, returns the number of bytes written.
     *               If the write fails, returns false.
     *               If the socket times out, returns an instance of PEAR_Error.
     */
    function write($data, $blocksize = null)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        if (is_null($blocksize) && !OS_WINDOWS) {
            $written = @fwrite($this->fp, $data);

            // Check for timeout or lost connection
            if (!$written) {
                $meta_data = $this->getStatus();

                if (!is_array($meta_data)) {
                    return $meta_data; // PEAR_Error
                }

                if (!empty($meta_data['timed_out'])) {
                    return $this->raiseError('timed out');
                }
            }

            return $written;
        } else {
            if (is_null($blocksize)) {
                $blocksize = 1024;
            }

            $pos  = 0;
            $size = strlen($data);
            while ($pos < $size) {
                $written = @fwrite($this->fp, substr($data, $pos, $blocksize));

                // Check for timeout or lost connection
                if (!$written) {
                    $meta_data = $this->getStatus();

                    if (!is_array($meta_data)) {
                        return $meta_data; // PEAR_Error
                    }

                    if (!empty($meta_data['timed_out'])) {
                        return $this->raiseError('timed out');
                    }

                    return $written;
                }

                $pos += $written;
            }

            return $pos;
        }
    }

    /**
     * Write a line of data to the socket, followed by a trailing newline.
     *
     * @param string $data Data to write
     *
     * @access public
     * @return mixed fwrite() result, or PEAR_Error when not connected
     */
    function writeLine($data)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return fwrite($this->fp, $data . $this->newline);
    }

    /**
     * Tests for end-of-file on a socket descriptor.
     *
     * Also returns true if the socket is disconnected.
     *
     * @access public
     * @return bool
     */
    function eof()
    {
        return (!is_resource($this->fp) || feof($this->fp));
    }

    /**
     * Reads a byte of data
     *
     * @access public
     * @return 1 byte of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    function readByte()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return ord(@fread($this->fp, 1));
    }

    /**
     * Reads a word of data
     *
     * @access public
     * @return 1 word of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    function readWord()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 2);
        return (ord($buf[0]) + (ord($buf[1]) << 8));
    }

    /**
     * Reads an int of data
     *
     * @access public
     * @return integer  1 int of data from the socket, or a PEAR_Error if
     *                  not connected.
     */
    function readInt()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 4);
        return (ord($buf[0]) + (ord($buf[1]) << 8) +
                (ord($buf[2]) << 16) + (ord($buf[3]) << 24));
    }

    /**
     * Reads a zero-terminated string of data
     *
     * @access public
     * @return string, or a PEAR_Error if
     *         not connected.
     */
    function readString()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $string = '';
        while (($char = @fread($this->fp, 1)) != "\x00") {
            $string .= $char;
        }
        return $string;
    }

    /**
     * Reads an IP Address and returns it in a dot formatted string
     *
     * @access public
     * @return Dot formatted string, or a PEAR_Error if
     *         not connected.
     */
    function readIPAddress()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 4);
        return sprintf('%d.%d.%d.%d', ord($buf[0]), ord($buf[1]),
                       ord($buf[2]), ord($buf[3]));
    }

    /**
     * Read until either the end of the socket or a newline, whichever
     * comes first. Strips the trailing newline from the returned data.
     *
     * @access public
     * @return All available data up to a newline, without that
     *         newline, or until the end of the socket, or a PEAR_Error if
     *         not connected.
     */
    function readLine()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $line = '';

        $timeout = time() + $this->timeout;

        while (!feof($this->fp) && (!$this->timeout || time() < $timeout)) {
            $line .= @fgets($this->fp, $this->lineLength);
            if (substr($line, -1) == "\n") {
                return rtrim($line, $this->newline);
            }
        }
        return $line;
    }

    /**
     * Read until the socket closes, or until there is no more data in
     * the inner PHP buffer. If the inner buffer is empty, in blocking
     * mode we wait for at least 1 byte of data. Therefore, in
     * blocking mode, if there is no data at all to be read, this
     * function will never exit (unless the socket is closed on the
     * remote end).
     *
     * @access public
     *
     * @return string  All data until the socket closes, or a PEAR_Error if
     *                 not connected.
     */
    function readAll()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $data = '';
        while (!feof($this->fp)) {
            $data .= @fread($this->fp, $this->lineLength);
        }
        return $data;
    }

    /**
     * Runs the equivalent of the select() system call on the socket
     * with a timeout specified by tv_sec and tv_usec.
     *
     * @param integer $state   Which of read/write/error to check for.
     * @param integer $tv_sec  Number of seconds for timeout.
     * @param integer $tv_usec Number of microseconds for timeout.
     *
     * @access public
     * @return False if select fails, integer describing which of read/write/error
     *         are ready, or PEAR_Error if not connected.
     */
    function select($state, $tv_sec, $tv_usec = 0)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $read   = null;
        $write  = null;
        $except = null;
        if ($state & NET_SOCKET_READ) {
            $read[] = $this->fp;
        }
        if ($state & NET_SOCKET_WRITE) {
            $write[] = $this->fp;
        }
        if ($state & NET_SOCKET_ERROR) {
            $except[] = $this->fp;
        }
        if (false === ($sr = stream_select($read, $write, $except,
                                          $tv_sec, $tv_usec))) {
            return false;
        }

        $result = 0;
        if (count($read)) {
            $result |= NET_SOCKET_READ;
        }
        if (count($write)) {
            $result |= NET_SOCKET_WRITE;
        }
        if (count($except)) {
            $result |= NET_SOCKET_ERROR;
        }
        return $result;
    }

    /**
     * Turns encryption on/off on a connected socket.
     *
     * @param bool    $enabled Set this parameter to true to enable encryption
     *                         and false to disable encryption.
     * @param integer $type    Type of encryption. See stream_socket_enable_crypto()
     *                         for values.
     *
     * @see    http://se.php.net/manual/en/function.stream-socket-enable-crypto.php
     * @access public
     * @return false on error, true on success and 0 if there isn't enough data
     *         and the user should try again (non-blocking sockets only).
     *         A PEAR_Error object is returned if the socket is not
     *         connected
     */
    function enableCrypto($enabled, $type)
    {
        if (version_compare(phpversion(), "5.1.0", ">=")) {
            if (!is_resource($this->fp)) {
                return $this->raiseError('not connected');
            }
            return @stream_socket_enable_crypto($this->fp, $enabled, $type);
        } else {
            $msg = 'Net_Socket::enableCrypto() requires php version >= 5.1.0';
            return $this->raiseError($msg);
        }
    }

}

// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2004, Richard Heyes                                |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard at php net>                            |
// +-----------------------------------------------------------------------+
//
// $Id: URL.php,v 1.49 2007/06/28 14:43:07 davidc Exp $
//
// Net_URL Class


class Net_URL
{
    var $options = array('encode_query_keys' => false);
    /**
    * Full url
    * @var string
    */
    var $url;

    /**
    * Protocol
    * @var string
    */
    var $protocol;

    /**
    * Username
    * @var string
    */
    var $username;

    /**
    * Password
    * @var string
    */
    var $password;

    /**
    * Host
    * @var string
    */
    var $host;

    /**
    * Port
    * @var integer
    */
    var $port;

    /**
    * Path
    * @var string
    */
    var $path;

    /**
    * Query string
    * @var array
    */
    var $querystring;

    /**
    * Anchor
    * @var string
    */
    var $anchor;

    /**
    * Whether to use []
    * @var bool
    */
    var $useBrackets;

    /**
    * PHP5 Constructor
    *
    * Parses the given url and stores the various parts
    * Defaults are used in certain cases
    *
    * @param string $url         Optional URL
    * @param bool   $useBrackets Whether to use square brackets when
    *                            multiple querystrings with the same name
    *                            exist
    */
    function __construct($url = null, $useBrackets = true)
    {
        $this->url = $url;
        $this->useBrackets = $useBrackets;

        $this->initialize();
    }

    function initialize()
    {
        $HTTP_SERVER_VARS  = !empty($_SERVER) ? $_SERVER : $GLOBALS['HTTP_SERVER_VARS'];

        $this->user        = '';
        $this->pass        = '';
        $this->host        = '';
        $this->port        = 80;
        $this->path        = '';
        $this->querystring = array();
        $this->anchor      = '';

        // Only use defaults if not an absolute URL given
        if (!preg_match('/^[a-z0-9]+:\/\//i', $this->url)) {
            $this->protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');

            /**
            * Figure out host/port
            */
            if (!empty($HTTP_SERVER_VARS['HTTP_HOST']) && 
                preg_match('/^(.*)(:([0-9]+))?$/U', $HTTP_SERVER_VARS['HTTP_HOST'], $matches)) 
            {
                $host = $matches[1];
                if (!empty($matches[3])) {
                    $port = $matches[3];
                } else {
                    $port = $this->getStandardPort($this->protocol);
                }
            }

            $this->user        = '';
            $this->pass        = '';
            $this->host        = !empty($host) ? $host : (isset($HTTP_SERVER_VARS['SERVER_NAME']) ? $HTTP_SERVER_VARS['SERVER_NAME'] : 'localhost');
            $this->port        = !empty($port) ? $port : (isset($HTTP_SERVER_VARS['SERVER_PORT']) ? $HTTP_SERVER_VARS['SERVER_PORT'] : $this->getStandardPort($this->protocol));
            $this->path        = !empty($HTTP_SERVER_VARS['PHP_SELF']) ? $HTTP_SERVER_VARS['PHP_SELF'] : '/';
            $this->querystring = isset($HTTP_SERVER_VARS['QUERY_STRING']) ? $this->_parseRawQuerystring($HTTP_SERVER_VARS['QUERY_STRING']) : null;
            $this->anchor      = '';
        }

        // Parse the url and store the various parts
        if (!empty($this->url)) {
            $urlinfo = parse_url($this->url);

            // Default querystring
            $this->querystring = array();

            foreach ($urlinfo as $key => $value) {
                switch ($key) {
                    case 'scheme':
                        $this->protocol = $value;
                        $this->port     = $this->getStandardPort($value);
                        break;

                    case 'user':
                    case 'pass':
                    case 'host':
                    case 'port':
                        $this->$key = $value;
                        break;

                    case 'path':
                        if ($value{0} == '/') {
                            $this->path = $value;
                        } else {
                            $path = dirname($this->path) == DIRECTORY_SEPARATOR ? '' : dirname($this->path);
                            $this->path = sprintf('%s/%s', $path, $value);
                        }
                        break;

                    case 'query':
                        $this->querystring = $this->_parseRawQueryString($value);
                        break;

                    case 'fragment':
                        $this->anchor = $value;
                        break;
                }
            }
        }
    }
    /**
    * Returns full url
    *
    * @return string Full url
    * @access public
    */
    function getURL()
    {
        $querystring = $this->getQueryString();

        $this->url = $this->protocol . '://'
                   . $this->user . (!empty($this->pass) ? ':' : '')
                   . $this->pass . (!empty($this->user) ? '@' : '')
                   . $this->host . ($this->port == $this->getStandardPort($this->protocol) ? '' : ':' . $this->port)
                   . $this->path
                   . (!empty($querystring) ? '?' . $querystring : '')
                   . (!empty($this->anchor) ? '#' . $this->anchor : '');

        return $this->url;
    }

    /**
    * Adds or updates a querystring item (URL parameter).
    * Automatically encodes parameters with rawurlencode() if $preencoded
    *  is false.
    * You can pass an array to $value, it gets mapped via [] in the URL if
    * $this->useBrackets is activated.
    *
    * @param  string $name       Name of item
    * @param  string $value      Value of item
    * @param  bool   $preencoded Whether value is urlencoded or not, default = not
    * @access public
    */
    function addQueryString($name, $value, $preencoded = false)
    {
        if ($this->getOption('encode_query_keys')) {
            $name = rawurlencode($name);
        }

        if ($preencoded) {
            $this->querystring[$name] = $value;
        } else {
            $this->querystring[$name] = is_array($value) ? array_map('rawurlencode', $value): rawurlencode($value);
        }
    }

    /**
    * Removes a querystring item
    *
    * @param  string $name Name of item
    * @access public
    */
    function removeQueryString($name)
    {
        if ($this->getOption('encode_query_keys')) {
            $name = rawurlencode($name);
        }

        if (isset($this->querystring[$name])) {
            unset($this->querystring[$name]);
        }
    }

    /**
    * Sets the querystring to literally what you supply
    *
    * @param  string $querystring The querystring data. Should be of the format foo=bar&x=y etc
    * @access public
    */
    function addRawQueryString($querystring)
    {
        $this->querystring = $this->_parseRawQueryString($querystring);
    }

    /**
    * Returns flat querystring
    *
    * @return string Querystring
    * @access public
    */
    function getQueryString()
    {
        if (!empty($this->querystring)) {
            foreach ($this->querystring as $name => $value) {
                // Encode var name
                $name = rawurlencode($name);

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $querystring[] = $this->useBrackets ? sprintf('%s[%s]=%s', $name, $k, $v) : ($name . '=' . $v);
                    }
                } elseif (!is_null($value)) {
                    $querystring[] = $name . '=' . $value;
                } else {
                    $querystring[] = $name;
                }
            }
            $querystring = implode(ini_get('arg_separator.output'), $querystring);
        } else {
            $querystring = '';
        }

        return $querystring;
    }

    /**
    * Parses raw querystring and returns an array of it
    *
    * @param  string  $querystring The querystring to parse
    * @return array                An array of the querystring data
    * @access private
    */
    function _parseRawQuerystring($querystring)
    {
        $parts  = preg_split('/[' . preg_quote(ini_get('arg_separator.input'), '/') . ']/', $querystring, -1, PREG_SPLIT_NO_EMPTY);
        $return = array();

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                $value = substr($part, strpos($part, '=') + 1);
                $key   = substr($part, 0, strpos($part, '='));
            } else {
                $value = null;
                $key   = $part;
            }

            if (!$this->getOption('encode_query_keys')) {
                $key = rawurldecode($key);
            }

            if (preg_match('#^(.*)\[([0-9a-z_-]*)\]#i', $key, $matches)) {
                $key = $matches[1];
                $idx = $matches[2];

                // Ensure is an array
                if (empty($return[$key]) || !is_array($return[$key])) {
                    $return[$key] = array();
                }

                // Add data
                if ($idx === '') {
                    $return[$key][] = $value;
                } else {
                    $return[$key][$idx] = $value;
                }
            } elseif (!$this->useBrackets AND !empty($return[$key])) {
                $return[$key]   = (array)$return[$key];
                $return[$key][] = $value;
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
    * Resolves //, ../ and ./ from a path and returns
    * the result. Eg:
    *
    * /foo/bar/../boo.php    => /foo/boo.php
    * /foo/bar/../../boo.php => /boo.php
    * /foo/bar/.././/boo.php => /foo/boo.php
    *
    * This method can also be called statically.
    *
    * @param  string $path URL path to resolve
    * @return string      The result
    */
    function resolvePath($path)
    {
        $path = explode('/', str_replace('//', '/', $path));

        for ($i=0; $i<count($path); $i++) {
            if ($path[$i] == '.') {
                unset($path[$i]);
                $path = array_values($path);
                $i--;

            } elseif ($path[$i] == '..' AND ($i > 1 OR ($i == 1 AND $path[0] != '') ) ) {
                unset($path[$i]);
                unset($path[$i-1]);
                $path = array_values($path);
                $i -= 2;

            } elseif ($path[$i] == '..' AND $i == 1 AND $path[0] == '') {
                unset($path[$i]);
                $path = array_values($path);
                $i--;

            } else {
                continue;
            }
        }

        return implode('/', $path);
    }

    /**
    * Returns the standard port number for a protocol
    *
    * @param  string  $scheme The protocol to lookup
    * @return integer         Port number or NULL if no scheme matches
    *
    * @author Philippe Jausions <Philippe.Jausions@11abacus.com>
    */
    function getStandardPort($scheme)
    {
        switch (strtolower($scheme)) {
            case 'http':    return 80;
            case 'https':   return 443;
            case 'ftp':     return 21;
            case 'imap':    return 143;
            case 'imaps':   return 993;
            case 'pop3':    return 110;
            case 'pop3s':   return 995;
            default:        return null;
       }
    }

    /**
    * Forces the URL to a particular protocol
    *
    * @param string  $protocol Protocol to force the URL to
    * @param integer $port     Optional port (standard port is used by default)
    */
    function setProtocol($protocol, $port = null)
    {
        $this->protocol = $protocol;
        $this->port     = is_null($port) ? $this->getStandardPort($protocol) : $port;
    }

    /**
     * Set an option
     *
     * This function set an option
     * to be used thorough the script.
     *
     * @access public
     * @param  string $optionName  The optionname to set
     * @param  string $value       The value of this option.
     */
    function setOption($optionName, $value)
    {
        if (!array_key_exists($optionName, $this->options)) {
            return false;
        }

        $this->options[$optionName] = $value;
        $this->initialize();
    }

    /**
     * Get an option
     *
     * This function gets an option
     * from the $this->options array
     * and return it's value.
     *
     * @access public
     * @param  string $opionName  The name of the option to retrieve
     * @see    $this->options
     */
    function getOption($optionName)
    {
        if (!isset($this->options[$optionName])) {
            return false;
        }

        return $this->options[$optionName];
    }

}

/**
 * Class for performing HTTP requests
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2002-2007, Richard Heyes
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2002-2007 Richard Heyes
 * @license     http://opensource.org/licenses/bsd-license.php New BSD License
 * @version     CVS: $Id: Request.php,v 1.63 2008/10/11 11:07:10 avb Exp $
 * @link        http://pear.php.net/package/HTTP_Request/
 */

/**#@+
 * Constants for HTTP request methods
 */
define('HTTP_REQUEST_METHOD_GET',     'GET',     true);
define('HTTP_REQUEST_METHOD_HEAD',    'HEAD',    true);
define('HTTP_REQUEST_METHOD_POST',    'POST',    true);
define('HTTP_REQUEST_METHOD_PUT',     'PUT',     true);
define('HTTP_REQUEST_METHOD_DELETE',  'DELETE',  true);
define('HTTP_REQUEST_METHOD_OPTIONS', 'OPTIONS', true);
define('HTTP_REQUEST_METHOD_TRACE',   'TRACE',   true);
/**#@-*/

/**#@+
 * Constants for HTTP request error codes
 */
define('HTTP_REQUEST_ERROR_FILE',             1);
define('HTTP_REQUEST_ERROR_URL',              2);
define('HTTP_REQUEST_ERROR_PROXY',            4);
define('HTTP_REQUEST_ERROR_REDIRECTS',        8);
define('HTTP_REQUEST_ERROR_RESPONSE',        16);
define('HTTP_REQUEST_ERROR_GZIP_METHOD',     32);
define('HTTP_REQUEST_ERROR_GZIP_READ',       64);
define('HTTP_REQUEST_ERROR_GZIP_DATA',      128);
define('HTTP_REQUEST_ERROR_GZIP_CRC',       256);
/**#@-*/

/**#@+
 * Constants for HTTP protocol versions
 */
define('HTTP_REQUEST_HTTP_VER_1_0', '1.0', true);
define('HTTP_REQUEST_HTTP_VER_1_1', '1.1', true);
/**#@-*/

if (extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload'))) {
   /**
    * Whether string functions are overloaded by their mbstring equivalents
    */
    define('HTTP_REQUEST_MBSTRING', true);
} else {
   /**
    * @ignore
    */
    define('HTTP_REQUEST_MBSTRING', false);
}

/**
 * Class for performing HTTP requests
 *
 * Simple example (fetches yahoo.com and displays it):
 * <code>
 * $a = &new HTTP_Request('http://www.yahoo.com/');
 * $a->sendRequest();
 * echo $a->getResponseBody();
 * </code>
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Request
{
   /**#@+
    * @access private
    */
    /**
    * Instance of Net_URL
    * @var Net_URL
    */
    var $_url;

    /**
    * Type of request
    * @var string
    */
    var $_method;

    /**
    * HTTP Version
    * @var string
    */
    var $_http;

    /**
    * Request headers
    * @var array
    */
    var $_requestHeaders;

    /**
    * Basic Auth Username
    * @var string
    */
    var $_user;

    /**
    * Basic Auth Password
    * @var string
    */
    var $_pass;

    /**
    * Socket object
    * @var Net_Socket
    */
    var $_sock;

    /**
    * Proxy server
    * @var string
    */
    var $_proxy_host;

    /**
    * Proxy port
    * @var integer
    */
    var $_proxy_port;

    /**
    * Proxy username
    * @var string
    */
    var $_proxy_user;

    /**
    * Proxy password
    * @var string
    */
    var $_proxy_pass;

    /**
    * Post data
    * @var array
    */
    var $_postData;

   /**
    * Request body
    * @var string
    */
    var $_body;

   /**
    * A list of methods that MUST NOT have a request body, per RFC 2616
    * @var array
    */
    var $_bodyDisallowed = array('TRACE');

   /**
    * Methods having defined semantics for request body
    *
    * Content-Length header (indicating that the body follows, section 4.3 of
    * RFC 2616) will be sent for these methods even if no body was added
    *
    * @var array
    */
    var $_bodyRequired = array('POST', 'PUT');

   /**
    * Files to post
    * @var array
    */
    var $_postFiles = array();

    /**
    * Connection timeout.
    * @var float
    */
    var $_timeout;

    /**
    * HTTP_Response object
    * @var HTTP_Response
    */
    var $_response;

    /**
    * Whether to allow redirects
    * @var boolean
    */
    var $_allowRedirects;

    /**
    * Maximum redirects allowed
    * @var integer
    */
    var $_maxRedirects;

    /**
    * Current number of redirects
    * @var integer
    */
    var $_redirects;

   /**
    * Whether to append brackets [] to array variables
    * @var bool
    */
    var $_useBrackets = true;

   /**
    * Attached listeners
    * @var array
    */
    var $_listeners = array();

   /**
    * Whether to save response body in response object property
    * @var bool
    */
    var $_saveBody = true;

   /**
    * Timeout for reading from socket (array(seconds, microseconds))
    * @var array
    */
    var $_readTimeout = null;

   /**
    * Options to pass to Net_Socket::connect. See stream_context_create
    * @var array
    */
    var $_socketOptions = null;
   /**#@-*/

    /**
    * Constructor
    *
    * Sets up the object
    * @param    string  The url to fetch/access
    * @param    array   Associative array of parameters which can have the following keys:
    * <ul>
    *   <li>method         - Method to use, GET, POST etc (string)</li>
    *   <li>http           - HTTP Version to use, 1.0 or 1.1 (string)</li>
    *   <li>user           - Basic Auth username (string)</li>
    *   <li>pass           - Basic Auth password (string)</li>
    *   <li>proxy_host     - Proxy server host (string)</li>
    *   <li>proxy_port     - Proxy server port (integer)</li>
    *   <li>proxy_user     - Proxy auth username (string)</li>
    *   <li>proxy_pass     - Proxy auth password (string)</li>
    *   <li>timeout        - Connection timeout in seconds (float)</li>
    *   <li>allowRedirects - Whether to follow redirects or not (bool)</li>
    *   <li>maxRedirects   - Max number of redirects to follow (integer)</li>
    *   <li>useBrackets    - Whether to append [] to array variable names (bool)</li>
    *   <li>saveBody       - Whether to save response body in response object property (bool)</li>
    *   <li>readTimeout    - Timeout for reading / writing data over the socket (array (seconds, microseconds))</li>
    *   <li>socketOptions  - Options to pass to Net_Socket object (array)</li>
    * </ul>
    * @access public
    */
    function HTTP_Request($url = '', $params = array())
    {
        $this->_method         =  HTTP_REQUEST_METHOD_GET;
        $this->_http           =  HTTP_REQUEST_HTTP_VER_1_1;
        $this->_requestHeaders = array();
        $this->_postData       = array();
        $this->_body           = null;

        $this->_user = null;
        $this->_pass = null;

        $this->_proxy_host = null;
        $this->_proxy_port = null;
        $this->_proxy_user = null;
        $this->_proxy_pass = null;

        $this->_allowRedirects = false;
        $this->_maxRedirects   = 3;
        $this->_redirects      = 0;

        $this->_timeout  = null;
        $this->_response = null;

        foreach ($params as $key => $value) {
            $this->{'_' . $key} = $value;
        }

        if (!empty($url)) {
            $this->setURL($url);
        }

        // Default useragent
        $this->addHeader('User-Agent', 'PEAR HTTP_Request class ( http://pear.php.net/ )');

        // We don't do keep-alives by default
        $this->addHeader('Connection', 'close');

        // Basic authentication
        if (!empty($this->_user)) {
            $this->addHeader('Authorization', 'Basic ' . base64_encode($this->_user . ':' . $this->_pass));
        }

        // Proxy authentication (see bug #5913)
        if (!empty($this->_proxy_user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($this->_proxy_user . ':' . $this->_proxy_pass));
        }

        // Use gzip encoding if possible
        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && extension_loaded('zlib')) {
            $this->addHeader('Accept-Encoding', 'gzip');
        }
    }

    /**
    * Generates a Host header for HTTP/1.1 requests
    *
    * @access private
    * @return string
    */
    function _generateHostHeader()
    {
        if ($this->_url->port != 80 AND strcasecmp($this->_url->protocol, 'http') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port != 443 AND strcasecmp($this->_url->protocol, 'https') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port == 443 AND strcasecmp($this->_url->protocol, 'https') == 0 AND strpos($this->_url->url, ':443') !== false) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } else {
            $host = $this->_url->host;
        }

        return $host;
    }

    /**
    * Resets the object to its initial state (DEPRECATED).
    * Takes the same parameters as the constructor.
    *
    * @param  string $url    The url to be requested
    * @param  array  $params Associative array of parameters
    *                        (see constructor for details)
    * @access public
    * @deprecated deprecated since 1.2, call the constructor if this is necessary
    */
    function reset($url, $params = array())
    {
        $this->HTTP_Request($url, $params);
    }

    /**
    * Sets the URL to be requested
    *
    * @param  string The url to be requested
    * @access public
    */
    function setURL($url)
    {
        $this->_url = new Net_URL($url, $this->_useBrackets);

        if (!empty($this->_url->user) || !empty($this->_url->pass)) {
            $this->setBasicAuth($this->_url->user, $this->_url->pass);
        }

        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http) {
            $this->addHeader('Host', $this->_generateHostHeader());
        }

        // set '/' instead of empty path rather than check later (see bug #8662)
        if (empty($this->_url->path)) {
            $this->_url->path = '/';
        }
    }

   /**
    * Returns the current request URL
    *
    * @return   string  Current request URL
    * @access   public
    */
    function getUrl()
    {
        return empty($this->_url)? '': $this->_url->getUrl();
    }

    /**
    * Sets a proxy to be used
    *
    * @param string     Proxy host
    * @param int        Proxy port
    * @param string     Proxy username
    * @param string     Proxy password
    * @access public
    */
    function setProxy($host, $port = 8080, $user = null, $pass = null)
    {
        $this->_proxy_host = $host;
        $this->_proxy_port = $port;
        $this->_proxy_user = $user;
        $this->_proxy_pass = $pass;

        if (!empty($user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
        }
    }

    /**
    * Sets basic authentication parameters
    *
    * @param string     Username
    * @param string     Password
    */
    function setBasicAuth($user, $pass)
    {
        $this->_user = $user;
        $this->_pass = $pass;

        $this->addHeader('Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
    }

    /**
    * Sets the method to be used, GET, POST etc.
    *
    * @param string     Method to use. Use the defined constants for this
    * @access public
    */
    function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
    * Sets the HTTP version to use, 1.0 or 1.1
    *
    * @param string     Version to use. Use the defined constants for this
    * @access public
    */
    function setHttpVer($http)
    {
        $this->_http = $http;
    }

    /**
    * Adds a request header
    *
    * @param string     Header name
    * @param string     Header value
    * @access public
    */
    function addHeader($name, $value)
    {
        $this->_requestHeaders[strtolower($name)] = $value;
    }

    /**
    * Removes a request header
    *
    * @param string     Header name to remove
    * @access public
    */
    function removeHeader($name)
    {
        if (isset($this->_requestHeaders[strtolower($name)])) {
            unset($this->_requestHeaders[strtolower($name)]);
        }
    }

    /**
    * Adds a querystring parameter
    *
    * @param string     Querystring parameter name
    * @param string     Querystring parameter value
    * @param bool       Whether the value is already urlencoded or not, default = not
    * @access public
    */
    function addQueryString($name, $value, $preencoded = false)
    {
        $this->_url->addQueryString($name, $value, $preencoded);
    }

    /**
    * Sets the querystring to literally what you supply
    *
    * @param string     The querystring data. Should be of the format foo=bar&x=y etc
    * @param bool       Whether data is already urlencoded or not, default = already encoded
    * @access public
    */
    function addRawQueryString($querystring, $preencoded = true)
    {
        $this->_url->addRawQueryString($querystring, $preencoded);
    }

    /**
    * Adds postdata items
    *
    * @param string     Post data name
    * @param string     Post data value
    * @param bool       Whether data is already urlencoded or not, default = not
    * @access public
    */
    function addPostData($name, $value, $preencoded = false)
    {
        if ($preencoded) {
            $this->_postData[$name] = $value;
        } else {
            $this->_postData[$name] = $this->_arrayMapRecursive('urlencode', $value);
        }
    }

   /**
    * Recursively applies the callback function to the value
    *
    * @param    mixed   Callback function
    * @param    mixed   Value to process
    * @access   private
    * @return   mixed   Processed value
    */
    function _arrayMapRecursive($callback, $value)
    {
        if (!is_array($value)) {
            return call_user_func($callback, $value);
        } else {
            $map = array();
            foreach ($value as $k => $v) {
                $map[$k] = $this->_arrayMapRecursive($callback, $v);
            }
            return $map;
        }
    }

   /**
    * Adds a file to form-based file upload
    *
    * Used to emulate file upload via a HTML form. The method also sets
    * Content-Type of HTTP request to 'multipart/form-data'.
    *
    * If you just want to send the contents of a file as the body of HTTP
    * request you should use setBody() method.
    *
    * @access public
    * @param  string    name of file-upload field
    * @param  mixed     file name(s)
    * @param  mixed     content-type(s) of file(s) being uploaded
    * @return bool      true on success
    * @throws PEAR_Error
    */
    function addFile($inputName, $fileName, $contentType = 'application/octet-stream')
    {
        if (!is_array($fileName) && !is_readable($fileName)) {
            return PEAR::raiseError("File '{$fileName}' is not readable", HTTP_REQUEST_ERROR_FILE);
        } elseif (is_array($fileName)) {
            foreach ($fileName as $name) {
                if (!is_readable($name)) {
                    return PEAR::raiseError("File '{$name}' is not readable", HTTP_REQUEST_ERROR_FILE);
                }
            }
        }
        $this->addHeader('Content-Type', 'multipart/form-data');
        $this->_postFiles[$inputName] = array(
            'name' => $fileName,
            'type' => $contentType
        );
        return true;
    }

    /**
    * Adds raw postdata (DEPRECATED)
    *
    * @param string     The data
    * @param bool       Whether data is preencoded or not, default = already encoded
    * @access public
    * @deprecated       deprecated since 1.3.0, method setBody() should be used instead
    */
    function addRawPostData($postdata, $preencoded = true)
    {
        $this->_body = $preencoded ? $postdata : urlencode($postdata);
    }

   /**
    * Sets the request body (for POST, PUT and similar requests)
    *
    * @param    string  Request body
    * @access   public
    */
    function setBody($body)
    {
        $this->_body = $body;
    }

    /**
    * Clears any postdata that has been added (DEPRECATED).
    *
    * Useful for multiple request scenarios.
    *
    * @access public
    * @deprecated deprecated since 1.2
    */
    function clearPostData()
    {
        $this->_postData = null;
    }

    /**
    * Appends a cookie to "Cookie:" header
    *
    * @param string $name cookie name
    * @param string $value cookie value
    * @access public
    */
    function addCookie($name, $value)
    {
        $cookies = isset($this->_requestHeaders['cookie']) ? $this->_requestHeaders['cookie']. '; ' : '';
        $this->addHeader('Cookie', $cookies . $name . '=' . $value);
    }

    /**
    * Clears any cookies that have been added (DEPRECATED).
    *
    * Useful for multiple request scenarios
    *
    * @access public
    * @deprecated deprecated since 1.2
    */
    function clearCookies()
    {
        $this->removeHeader('Cookie');
    }

    /**
    * Sends the request
    *
    * @access public
    * @param  bool   Whether to store response body in Response object property,
    *                set this to false if downloading a LARGE file and using a Listener
    * @return mixed  PEAR error on error, true otherwise
    */
    function sendRequest($saveBody = true)
    {
        if (!is_a($this->_url, 'Net_URL')) {
            return PEAR::raiseError('No URL given', HTTP_REQUEST_ERROR_URL);
        }

        $host = isset($this->_proxy_host) ? $this->_proxy_host : $this->_url->host;
        $port = isset($this->_proxy_port) ? $this->_proxy_port : $this->_url->port;

        if (strcasecmp($this->_url->protocol, 'https') == 0) {
            // Bug #14127, don't try connecting to HTTPS sites without OpenSSL
            if (version_compare(PHP_VERSION, '4.3.0', '<') || !extension_loaded('openssl')) {
                return PEAR::raiseError('Need PHP 4.3.0 or later with OpenSSL support for https:// requests',
                                        HTTP_REQUEST_ERROR_URL);
            } elseif (isset($this->_proxy_host)) {
                return PEAR::raiseError('HTTPS proxies are not supported', HTTP_REQUEST_ERROR_PROXY);
            }
            $host = 'ssl://' . $host;
        }

        // magic quotes may fuck up file uploads and chunked response processing
        $magicQuotes = ini_get('magic_quotes_runtime');
        ini_set('magic_quotes_runtime', false);

        // RFC 2068, section 19.7.1: A client MUST NOT send the Keep-Alive
        // connection token to a proxy server...
        if (isset($this->_proxy_host) && !empty($this->_requestHeaders['connection']) &&
            'Keep-Alive' == $this->_requestHeaders['connection'])
        {
            $this->removeHeader('connection');
        }

        $keepAlive = (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && empty($this->_requestHeaders['connection'])) ||
                     (!empty($this->_requestHeaders['connection']) && 'Keep-Alive' == $this->_requestHeaders['connection']);
        $sockets   = &PEAR::getStaticProperty('HTTP_Request', 'sockets');
        $sockKey   = $host . ':' . $port;
        unset($this->_sock);

        // There is a connected socket in the "static" property?
        if ($keepAlive && !empty($sockets[$sockKey]) &&
            !empty($sockets[$sockKey]->fp))
        {
            $this->_sock = $sockets[$sockKey];
            $err = null;
        } else {
            $this->_notify('connect');
            $this->_sock = new Net_Socket();
            $err = $this->_sock->connect($host, $port, null, $this->_timeout, $this->_socketOptions);
        }
        PEAR::isError($err) or $err = $this->_sock->write($this->_buildRequest());

        if (!PEAR::isError($err)) {
            if (!empty($this->_readTimeout)) {
                $this->_sock->setTimeout($this->_readTimeout[0], $this->_readTimeout[1]);
            }

            $this->_notify('sentRequest');

            // Read the response
            $this->_response = new HTTP_Response($this->_sock, $this->_listeners);
            $err = $this->_response->process(
                $this->_saveBody && $saveBody,
                HTTP_REQUEST_METHOD_HEAD != $this->_method
            );

            if ($keepAlive) {
                $keepAlive = (isset($this->_response->_headers['content-length'])
                              || (isset($this->_response->_headers['transfer-encoding'])
                                  && strtolower($this->_response->_headers['transfer-encoding']) == 'chunked'));
                if ($keepAlive) {
                    if (isset($this->_response->_headers['connection'])) {
                        $keepAlive = strtolower($this->_response->_headers['connection']) == 'keep-alive';
                    } else {
                        $keepAlive = 'HTTP/'.HTTP_REQUEST_HTTP_VER_1_1 == $this->_response->_protocol;
                    }
                }
            }
        }

        ini_set('magic_quotes_runtime', $magicQuotes);

        if (PEAR::isError($err)) {
            return $err;
        }

        if (!$keepAlive) {
            $this->disconnect();
        // Store the connected socket in "static" property
        } elseif (empty($sockets[$sockKey]) || empty($sockets[$sockKey]->fp)) {
            $sockets[$sockKey] = $this->_sock;
        }

        // Check for redirection
        if (    $this->_allowRedirects
            AND $this->_redirects <= $this->_maxRedirects
            AND $this->getResponseCode() > 300
            AND $this->getResponseCode() < 399
            AND !empty($this->_response->_headers['location'])) {


            $redirect = $this->_response->_headers['location'];

            // Absolute URL
            if (preg_match('/^https?:\/\//i', $redirect)) {
                $this->_url = new Net_URL($redirect);
                $this->addHeader('Host', $this->_generateHostHeader());
            // Absolute path
            } elseif ($redirect{0} == '/') {
                $this->_url->path = $redirect;

            // Relative path
            } elseif (substr($redirect, 0, 3) == '../' OR substr($redirect, 0, 2) == './') {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $redirect = Net_URL::resolvePath($redirect);
                $this->_url->path = $redirect;

            // Filename, no path
            } else {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $this->_url->path = $redirect;
            }

            $this->_redirects++;
            return $this->sendRequest($saveBody);

        // Too many redirects
        } elseif ($this->_allowRedirects AND $this->_redirects > $this->_maxRedirects) {
            return PEAR::raiseError('Too many redirects', HTTP_REQUEST_ERROR_REDIRECTS);
        }

        return true;
    }

    /**
     * Disconnect the socket, if connected. Only useful if using Keep-Alive.
     *
     * @access public
     */
    function disconnect()
    {
        if (!empty($this->_sock) && !empty($this->_sock->fp)) {
            $this->_notify('disconnect');
            $this->_sock->disconnect();
        }
    }

    /**
    * Returns the response code
    *
    * @access public
    * @return mixed     Response code, false if not set
    */
    function getResponseCode()
    {
        return isset($this->_response->_code) ? $this->_response->_code : false;
    }

    /**
    * Returns the response reason phrase
    *
    * @access public
    * @return mixed     Response reason phrase, false if not set
    */
    function getResponseReason()
    {
        return isset($this->_response->_reason) ? $this->_response->_reason : false;
    }

    /**
    * Returns either the named header or all if no name given
    *
    * @access public
    * @param string     The header name to return, do not set to get all headers
    * @return mixed     either the value of $headername (false if header is not present)
    *                   or an array of all headers
    */
    function getResponseHeader($headername = null)
    {
        if (!isset($headername)) {
            return isset($this->_response->_headers)? $this->_response->_headers: array();
        } else {
            $headername = strtolower($headername);
            return isset($this->_response->_headers[$headername]) ? $this->_response->_headers[$headername] : false;
        }
    }

    /**
    * Returns the body of the response
    *
    * @access public
    * @return mixed     response body, false if not set
    */
    function getResponseBody()
    {
        return isset($this->_response->_body) ? $this->_response->_body : false;
    }

    /**
    * Returns cookies set in response
    *
    * @access public
    * @return mixed     array of response cookies, false if none are present
    */
    function getResponseCookies()
    {
        return isset($this->_response->_cookies) ? $this->_response->_cookies : false;
    }

    /**
    * Builds the request string
    *
    * @access private
    * @return string The request string
    */
    function _buildRequest()
    {
        $separator = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $querystring = ($querystring = $this->_url->getQueryString()) ? '?' . $querystring : '';
        ini_set('arg_separator.output', $separator);

        $host = isset($this->_proxy_host) ? $this->_url->protocol . '://' . $this->_url->host : '';
        $port = (isset($this->_proxy_host) AND $this->_url->port != 80) ? ':' . $this->_url->port : '';
        $path = $this->_url->path . $querystring;
        $url  = $host . $port . $path;

        if (!strlen($url)) {
            $url = '/';
        }

        $request = $this->_method . ' ' . $url . ' HTTP/' . $this->_http . "\r\n";

        if (in_array($this->_method, $this->_bodyDisallowed) ||
            (0 == strlen($this->_body) && (HTTP_REQUEST_METHOD_POST != $this->_method ||
             (empty($this->_postData) && empty($this->_postFiles)))))
        {
            $this->removeHeader('Content-Type');
        } else {
            if (empty($this->_requestHeaders['content-type'])) {
                // Add default content-type
                $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            } elseif ('multipart/form-data' == $this->_requestHeaders['content-type']) {
                $boundary = 'HTTP_Request_' . md5(uniqid('request') . microtime());
                $this->addHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
            }
        }

        // Request Headers
        if (!empty($this->_requestHeaders)) {
            foreach ($this->_requestHeaders as $name => $value) {
                $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
                $request      .= $canonicalName . ': ' . $value . "\r\n";
            }
        }

        // Method does not allow a body, simply add a final CRLF
        if (in_array($this->_method, $this->_bodyDisallowed)) {

            $request .= "\r\n";

        // Post data if it's an array
        } elseif (HTTP_REQUEST_METHOD_POST == $this->_method &&
                  (!empty($this->_postData) || !empty($this->_postFiles))) {

            // "normal" POST request
            if (!isset($boundary)) {
                $postdata = implode('&', array_map(
                    create_function('$a', 'return $a[0] . \'=\' . $a[1];'),
                    $this->_flattenArray('', $this->_postData)
                ));

            // multipart request, probably with file uploads
            } else {
                $postdata = '';
                if (!empty($this->_postData)) {
                    $flatData = $this->_flattenArray('', $this->_postData);
                    foreach ($flatData as $item) {
                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $item[0] . '"';
                        $postdata .= "\r\n\r\n" . urldecode($item[1]) . "\r\n";
                    }
                }
                foreach ($this->_postFiles as $name => $value) {
                    if (is_array($value['name'])) {
                        $varname       = $name . ($this->_useBrackets? '[]': '');
                    } else {
                        $varname       = $name;
                        $value['name'] = array($value['name']);
                    }
                    foreach ($value['name'] as $key => $filename) {
                        $fp       = fopen($filename, 'r');
                        $basename = basename($filename);
                        $type     = is_array($value['type'])? @$value['type'][$key]: $value['type'];

                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $varname . '"; filename="' . $basename . '"';
                        $postdata .= "\r\nContent-Type: " . $type;
                        $postdata .= "\r\n\r\n" . fread($fp, filesize($filename)) . "\r\n";
                        fclose($fp);
                    }
                }
                $postdata .= '--' . $boundary . "--\r\n";
            }
            $request .= 'Content-Length: ' .
                        (HTTP_REQUEST_MBSTRING? mb_strlen($postdata, 'iso-8859-1'): strlen($postdata)) .
                        "\r\n\r\n";
            $request .= $postdata;

        // Explicitly set request body
        } elseif (0 < strlen($this->_body)) {

            $request .= 'Content-Length: ' .
                        (HTTP_REQUEST_MBSTRING? mb_strlen($this->_body, 'iso-8859-1'): strlen($this->_body)) .
                        "\r\n\r\n";
            $request .= $this->_body;

        // No body: send a Content-Length header nonetheless (request #12900),
        // but do that only for methods that require a body (bug #14740)
        } else {

            if (in_array($this->_method, $this->_bodyRequired)) {
                $request .= "Content-Length: 0\r\n";
            }
            $request .= "\r\n";
        }

        return $request;
    }

   /**
    * Helper function to change the (probably multidimensional) associative array
    * into the simple one.
    *
    * @param    string  name for item
    * @param    mixed   item's values
    * @return   array   array with the following items: array('item name', 'item value');
    * @access   private
    */
    function _flattenArray($name, $values)
    {
        if (!is_array($values)) {
            return array(array($name, $values));
        } else {
            $ret = array();
            foreach ($values as $k => $v) {
                if (empty($name)) {
                    $newName = $k;
                } elseif ($this->_useBrackets) {
                    $newName = $name . '[' . $k . ']';
                } else {
                    $newName = $name;
                }
                $ret = array_merge($ret, $this->_flattenArray($newName, $v));
            }
            return $ret;
        }
    }


   /**
    * Adds a Listener to the list of listeners that are notified of
    * the object's events
    *
    * Events sent by HTTP_Request object
    * - 'connect': on connection to server
    * - 'sentRequest': after the request was sent
    * - 'disconnect': on disconnection from server
    *
    * Events sent by HTTP_Response object
    * - 'gotHeaders': after receiving response headers (headers are passed in $data)
    * - 'tick': on receiving a part of response body (the part is passed in $data)
    * - 'gzTick': on receiving a gzip-encoded part of response body (ditto)
    * - 'gotBody': after receiving the response body (passes the decoded body in $data if it was gzipped)
    *
    * @param    HTTP_Request_Listener   listener to attach
    * @return   boolean                 whether the listener was successfully attached
    * @access   public
    */
    function attach(&$listener)
    {
        if (!is_a($listener, 'HTTP_Request_Listener')) {
            return false;
        }
        $this->_listeners[$listener->getId()] =& $listener;
        return true;
    }


   /**
    * Removes a Listener from the list of listeners
    *
    * @param    HTTP_Request_Listener   listener to detach
    * @return   boolean                 whether the listener was successfully detached
    * @access   public
    */
    function detach(&$listener)
    {
        if (!is_a($listener, 'HTTP_Request_Listener') ||
            !isset($this->_listeners[$listener->getId()])) {
            return false;
        }
        unset($this->_listeners[$listener->getId()]);
        return true;
    }


   /**
    * Notifies all registered listeners of an event.
    *
    * @param    string  Event name
    * @param    mixed   Additional data
    * @access   private
    * @see      HTTP_Request::attach()
    */
    function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }
}


/**
 * Response class to complement the Request class
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Response
{
    /**
    * Socket object
    * @var Net_Socket
    */
    var $_sock;

    /**
    * Protocol
    * @var string
    */
    var $_protocol;

    /**
    * Return code
    * @var string
    */
    var $_code;

    /**
    * Response reason phrase
    * @var string
    */
    var $_reason;

    /**
    * Response headers
    * @var array
    */
    var $_headers;

    /**
    * Cookies set in response
    * @var array
    */
    var $_cookies;

    /**
    * Response body
    * @var string
    */
    var $_body = '';

   /**
    * Used by _readChunked(): remaining length of the current chunk
    * @var string
    */
    var $_chunkLength = 0;

   /**
    * Attached listeners
    * @var array
    */
    var $_listeners = array();

   /**
    * Bytes left to read from message-body
    * @var null|int
    */
    var $_toRead;

    /**
    * Constructor
    *
    * @param  Net_Socket    socket to read the response from
    * @param  array         listeners attached to request
    */
    function HTTP_Response(&$sock, &$listeners)
    {
        $this->_sock      =& $sock;
        $this->_listeners =& $listeners;
    }


   /**
    * Processes a HTTP response
    *
    * This extracts response code, headers, cookies and decodes body if it
    * was encoded in some way
    *
    * @access public
    * @param  bool      Whether to store response body in object property, set
    *                   this to false if downloading a LARGE file and using a Listener.
    *                   This is assumed to be true if body is gzip-encoded.
    * @param  bool      Whether the response can actually have a message-body.
    *                   Will be set to false for HEAD requests.
    * @throws PEAR_Error
    * @return mixed     true on success, PEAR_Error in case of malformed response
    */
    function process($saveBody = true, $canHaveBody = true)
    {
        do {
            $line = $this->_sock->readLine();
            if (!preg_match('!^(HTTP/\d\.\d) (\d{3})(?: (.+))?!', $line, $s)) {
                return PEAR::raiseError('Malformed response', HTTP_REQUEST_ERROR_RESPONSE);
            } else {
                $this->_protocol = $s[1];
                $this->_code     = intval($s[2]);
                $this->_reason   = empty($s[3])? null: $s[3];
            }
            while ('' !== ($header = $this->_sock->readLine())) {
                $this->_processHeader($header);
            }
        } while (100 == $this->_code);

        $this->_notify('gotHeaders', $this->_headers);

        // RFC 2616, section 4.4:
        // 1. Any response message which "MUST NOT" include a message-body ...
        // is always terminated by the first empty line after the header fields
        // 3. ... If a message is received with both a
        // Transfer-Encoding header field and a Content-Length header field,
        // the latter MUST be ignored.
        $canHaveBody = $canHaveBody && $this->_code >= 200 &&
                       $this->_code != 204 && $this->_code != 304;

        // If response body is present, read it and decode
        $chunked = isset($this->_headers['transfer-encoding']) && ('chunked' == $this->_headers['transfer-encoding']);
        $gzipped = isset($this->_headers['content-encoding']) && ('gzip' == $this->_headers['content-encoding']);
        $hasBody = false;
        if ($canHaveBody && ($chunked || !isset($this->_headers['content-length']) ||
                0 != $this->_headers['content-length']))
        {
            if ($chunked || !isset($this->_headers['content-length'])) {
                $this->_toRead = null;
            } else {
                $this->_toRead = $this->_headers['content-length'];
            }
            while (!$this->_sock->eof() && (is_null($this->_toRead) || 0 < $this->_toRead)) {
                if ($chunked) {
                    $data = $this->_readChunked();
                } elseif (is_null($this->_toRead)) {
                    $data = $this->_sock->read(4096);
                } else {
                    $data = $this->_sock->read(min(4096, $this->_toRead));
                    $this->_toRead -= HTTP_REQUEST_MBSTRING? mb_strlen($data, 'iso-8859-1'): strlen($data);
                }
                if ('' == $data && (!$this->_chunkLength || $this->_sock->eof())) {
                    break;
                } else {
                    $hasBody = true;
                    if ($saveBody || $gzipped) {
                        $this->_body .= $data;
                    }
                    $this->_notify($gzipped? 'gzTick': 'tick', $data);
                }
            }
        }

        if ($hasBody) {
            // Uncompress the body if needed
            if ($gzipped) {
                $body = $this->_decodeGzip($this->_body);
                if (PEAR::isError($body)) {
                    return $body;
                }
                $this->_body = $body;
                $this->_notify('gotBody', $this->_body);
            } else {
                $this->_notify('gotBody');
            }
        }
        return true;
    }


   /**
    * Processes the response header
    *
    * @access private
    * @param  string    HTTP header
    */
    function _processHeader($header)
    {
        if (false === strpos($header, ':')) {
            return;
        }
        list($headername, $headervalue) = explode(':', $header, 2);
        $headername  = strtolower($headername);
        $headervalue = ltrim($headervalue);

        if ('set-cookie' != $headername) {
            if (isset($this->_headers[$headername])) {
                $this->_headers[$headername] .= ',' . $headervalue;
            } else {
                $this->_headers[$headername]  = $headervalue;
            }
        } else {
            $this->_parseCookie($headervalue);
        }
    }


   /**
    * Parse a Set-Cookie header to fill $_cookies array
    *
    * @access private
    * @param  string    value of Set-Cookie header
    */
    function _parseCookie($headervalue)
    {
        $cookie = array(
            'expires' => null,
            'domain'  => null,
            'path'    => null,
            'secure'  => false
        );

        // Only a name=value pair
        if (!strpos($headervalue, ';')) {
            $pos = strpos($headervalue, '=');
            $cookie['name']  = trim(substr($headervalue, 0, $pos));
            $cookie['value'] = trim(substr($headervalue, $pos + 1));

        // Some optional parameters are supplied
        } else {
            $elements = explode(';', $headervalue);
            $pos = strpos($elements[0], '=');
            $cookie['name']  = trim(substr($elements[0], 0, $pos));
            $cookie['value'] = trim(substr($elements[0], $pos + 1));

            for ($i = 1; $i < count($elements); $i++) {
                if (false === strpos($elements[$i], '=')) {
                    $elName  = trim($elements[$i]);
                    $elValue = null;
                } else {
                    list ($elName, $elValue) = array_map('trim', explode('=', $elements[$i]));
                }
                $elName = strtolower($elName);
                if ('secure' == $elName) {
                    $cookie['secure'] = true;
                } elseif ('expires' == $elName) {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ('path' == $elName || 'domain' == $elName) {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        $this->_cookies[] = $cookie;
    }


   /**
    * Read a part of response body encoded with chunked Transfer-Encoding
    *
    * @access private
    * @return string
    */
    function _readChunked()
    {
        // at start of the next chunk?
        if (0 == $this->_chunkLength) {
            $line = $this->_sock->readLine();
            if (preg_match('/^([0-9a-f]+)/i', $line, $matches)) {
                $this->_chunkLength = hexdec($matches[1]);
                // Chunk with zero length indicates the end
                if (0 == $this->_chunkLength) {
                    $this->_sock->readLine(); // make this an eof()
                    return '';
                }
            } else {
                return '';
            }
        }
        $data = $this->_sock->read($this->_chunkLength);
        $this->_chunkLength -= HTTP_REQUEST_MBSTRING? mb_strlen($data, 'iso-8859-1'): strlen($data);
        if (0 == $this->_chunkLength) {
            $this->_sock->readLine(); // Trailing CRLF
        }
        return $data;
    }


   /**
    * Notifies all registered listeners of an event.
    *
    * @param    string  Event name
    * @param    mixed   Additional data
    * @access   private
    * @see HTTP_Request::_notify()
    */
    function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }


   /**
    * Decodes the message-body encoded by gzip
    *
    * The real decoding work is done by gzinflate() built-in function, this
    * method only parses the header and checks data for compliance with
    * RFC 1952
    *
    * @access   private
    * @param    string  gzip-encoded data
    * @return   string  decoded data
    */
    function _decodeGzip($data)
    {
        if (HTTP_REQUEST_MBSTRING) {
            $oldEncoding = mb_internal_encoding();
            mb_internal_encoding('iso-8859-1');
        }
        $length = strlen($data);
        // If it doesn't look like gzip-encoded data, don't bother
        if (18 > $length || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            return $data;
        }
        $method = ord(substr($data, 2, 1));
        if (8 != $method) {
            return PEAR::raiseError('_decodeGzip(): unknown compression method', HTTP_REQUEST_ERROR_GZIP_METHOD);
        }
        $flags = ord(substr($data, 3, 1));
        if ($flags & 224) {
            return PEAR::raiseError('_decodeGzip(): reserved bits are set', HTTP_REQUEST_ERROR_GZIP_DATA);
        }

        // header is 10 bytes minimum. may be longer, though.
        $headerLength = 10;
        // extra fields, need to skip 'em
        if ($flags & 4) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $extraLength = unpack('v', substr($data, 10, 2));
            if ($length - $headerLength - 2 - $extraLength[1] < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $extraLength[1] + 2;
        }
        // file name, need to skip that
        if ($flags & 8) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $filenameLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $filenameLength || $length - $headerLength - $filenameLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $filenameLength + 1;
        }
        // comment, need to skip that also
        if ($flags & 16) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $commentLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $commentLength || $length - $headerLength - $commentLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $commentLength + 1;
        }
        // have a CRC for header. let's check
        if ($flags & 1) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $crcReal   = 0xffff & crc32(substr($data, 0, $headerLength));
            $crcStored = unpack('v', substr($data, $headerLength, 2));
            if ($crcReal != $crcStored[1]) {
                return PEAR::raiseError('_decodeGzip(): header CRC check failed', HTTP_REQUEST_ERROR_GZIP_CRC);
            }
            $headerLength += 2;
        }
        // unpacked data CRC and size at the end of encoded data
        $tmp = unpack('V2', substr($data, -8));
        $dataCrc  = $tmp[1];
        $dataSize = $tmp[2];

        // finally, call the gzinflate() function
        // don't pass $dataSize to gzinflate, see bugs #13135, #14370
        $unpacked = gzinflate(substr($data, $headerLength, -8));
        if (false === $unpacked) {
            return PEAR::raiseError('_decodeGzip(): gzinflate() call failed', HTTP_REQUEST_ERROR_GZIP_READ);
        } elseif ($dataSize != strlen($unpacked)) {
            return PEAR::raiseError('_decodeGzip(): data size check failed', HTTP_REQUEST_ERROR_GZIP_READ);
        } elseif ((0xffffffff & $dataCrc) != (0xffffffff & crc32($unpacked))) {
            return PEAR::raiseError('_decodeGzip(): data CRC check failed', HTTP_REQUEST_ERROR_GZIP_CRC);
        }
        if (HTTP_REQUEST_MBSTRING) {
            mb_internal_encoding($oldEncoding);
        }
        return $unpacked;
    }
} // End class HTTP_Response

// --------------------------------------------------------------------------------
// PhpConcept Library - Zip Module 2.8.2
// --------------------------------------------------------------------------------
// License GNU/LGPL - Vincent Blavet - August 2009
// http://www.phpconcept.net
// --------------------------------------------------------------------------------
//
// Presentation :
//   PclZip is a PHP library that manage ZIP archives.
//   So far tests show that archives generated by PclZip are readable by
//   WinZip application and other tools.
//
// Description :
//   See readme.txt and http://www.phpconcept.net
//
// Warning :
//   This library and the associated files are non commercial, non professional
//   work.
//   It should not have unexpected results. However if any damage is caused by
//   this software the author can not be responsible.
//   The use of this software is at the risk of the user.
//
// --------------------------------------------------------------------------------
// $Id: pclzip.lib.php,v 1.60 2009/09/30 21:01:04 vblavet Exp $
// --------------------------------------------------------------------------------

  // ----- Constants
  if (!defined('PCLZIP_READ_BLOCK_SIZE')) {
    define( 'PCLZIP_READ_BLOCK_SIZE', 2048 );
  }
  
  // ----- File list separator
  // In version 1.x of PclZip, the separator for file list is a space
  // (which is not a very smart choice, specifically for windows paths !).
  // A better separator should be a comma (,). This constant gives you the
  // abilty to change that.
  // However notice that changing this value, may have impact on existing
  // scripts, using space separated filenames.
  // Recommanded values for compatibility with older versions :
  //define( 'PCLZIP_SEPARATOR', ' ' );
  // Recommanded values for smart separation of filenames.
  if (!defined('PCLZIP_SEPARATOR')) {
    define( 'PCLZIP_SEPARATOR', ',' );
  }

  // ----- Error configuration
  // 0 : PclZip Class integrated error handling
  // 1 : PclError external library error handling. By enabling this
  //     you must ensure that you have included PclError library.
  // [2,...] : reserved for futur use
  if (!defined('PCLZIP_ERROR_EXTERNAL')) {
    define( 'PCLZIP_ERROR_EXTERNAL', 0 );
  }

  // ----- Optional static temporary directory
  //       By default temporary files are generated in the script current
  //       path.
  //       If defined :
  //       - MUST BE terminated by a '/'.
  //       - MUST be a valid, already created directory
  //       Samples :
  // define( 'PCLZIP_TEMPORARY_DIR', '/temp/' );
  // define( 'PCLZIP_TEMPORARY_DIR', 'C:/Temp/' );
  if (!defined('PCLZIP_TEMPORARY_DIR')) {
    define( 'PCLZIP_TEMPORARY_DIR', '' );
  }

  // ----- Optional threshold ratio for use of temporary files
  //       Pclzip sense the size of the file to add/extract and decide to
  //       use or not temporary file. The algorythm is looking for 
  //       memory_limit of PHP and apply a ratio.
  //       threshold = memory_limit * ratio.
  //       Recommended values are under 0.5. Default 0.47.
  //       Samples :
  // define( 'PCLZIP_TEMPORARY_FILE_RATIO', 0.5 );
  if (!defined('PCLZIP_TEMPORARY_FILE_RATIO')) {
    define( 'PCLZIP_TEMPORARY_FILE_RATIO', 0.47 );
  }

// --------------------------------------------------------------------------------
// ***** UNDER THIS LINE NOTHING NEEDS TO BE MODIFIED *****
// --------------------------------------------------------------------------------

  // ----- Global variables
  $g_pclzip_version = "2.8.2";

  // ----- Error codes
  //   -1 : Unable to open file in binary write mode
  //   -2 : Unable to open file in binary read mode
  //   -3 : Invalid parameters
  //   -4 : File does not exist
  //   -5 : Filename is too long (max. 255)
  //   -6 : Not a valid zip file
  //   -7 : Invalid extracted file size
  //   -8 : Unable to create directory
  //   -9 : Invalid archive extension
  //  -10 : Invalid archive format
  //  -11 : Unable to delete file (unlink)
  //  -12 : Unable to rename file (rename)
  //  -13 : Invalid header checksum
  //  -14 : Invalid archive size
  define( 'PCLZIP_ERR_USER_ABORTED', 2 );
  define( 'PCLZIP_ERR_NO_ERROR', 0 );
  define( 'PCLZIP_ERR_WRITE_OPEN_FAIL', -1 );
  define( 'PCLZIP_ERR_READ_OPEN_FAIL', -2 );
  define( 'PCLZIP_ERR_INVALID_PARAMETER', -3 );
  define( 'PCLZIP_ERR_MISSING_FILE', -4 );
  define( 'PCLZIP_ERR_FILENAME_TOO_LONG', -5 );
  define( 'PCLZIP_ERR_INVALID_ZIP', -6 );
  define( 'PCLZIP_ERR_BAD_EXTRACTED_FILE', -7 );
  define( 'PCLZIP_ERR_DIR_CREATE_FAIL', -8 );
  define( 'PCLZIP_ERR_BAD_EXTENSION', -9 );
  define( 'PCLZIP_ERR_BAD_FORMAT', -10 );
  define( 'PCLZIP_ERR_DELETE_FILE_FAIL', -11 );
  define( 'PCLZIP_ERR_RENAME_FILE_FAIL', -12 );
  define( 'PCLZIP_ERR_BAD_CHECKSUM', -13 );
  define( 'PCLZIP_ERR_INVALID_ARCHIVE_ZIP', -14 );
  define( 'PCLZIP_ERR_MISSING_OPTION_VALUE', -15 );
  define( 'PCLZIP_ERR_INVALID_OPTION_VALUE', -16 );
  define( 'PCLZIP_ERR_ALREADY_A_DIRECTORY', -17 );
  define( 'PCLZIP_ERR_UNSUPPORTED_COMPRESSION', -18 );
  define( 'PCLZIP_ERR_UNSUPPORTED_ENCRYPTION', -19 );
  define( 'PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE', -20 );
  define( 'PCLZIP_ERR_DIRECTORY_RESTRICTION', -21 );

  // ----- Options values
  define( 'PCLZIP_OPT_PATH', 77001 );
  define( 'PCLZIP_OPT_ADD_PATH', 77002 );
  define( 'PCLZIP_OPT_REMOVE_PATH', 77003 );
  define( 'PCLZIP_OPT_REMOVE_ALL_PATH', 77004 );
  define( 'PCLZIP_OPT_SET_CHMOD', 77005 );
  define( 'PCLZIP_OPT_EXTRACT_AS_STRING', 77006 );
  define( 'PCLZIP_OPT_NO_COMPRESSION', 77007 );
  define( 'PCLZIP_OPT_BY_NAME', 77008 );
  define( 'PCLZIP_OPT_BY_INDEX', 77009 );
  define( 'PCLZIP_OPT_BY_EREG', 77010 );
  define( 'PCLZIP_OPT_BY_PREG', 77011 );
  define( 'PCLZIP_OPT_COMMENT', 77012 );
  define( 'PCLZIP_OPT_ADD_COMMENT', 77013 );
  define( 'PCLZIP_OPT_PREPEND_COMMENT', 77014 );
  define( 'PCLZIP_OPT_EXTRACT_IN_OUTPUT', 77015 );
  define( 'PCLZIP_OPT_REPLACE_NEWER', 77016 );
  define( 'PCLZIP_OPT_STOP_ON_ERROR', 77017 );
  // Having big trouble with crypt. Need to multiply 2 long int
  // which is not correctly supported by PHP ...
  //define( 'PCLZIP_OPT_CRYPT', 77018 );
  define( 'PCLZIP_OPT_EXTRACT_DIR_RESTRICTION', 77019 );
  define( 'PCLZIP_OPT_TEMP_FILE_THRESHOLD', 77020 );
  define( 'PCLZIP_OPT_ADD_TEMP_FILE_THRESHOLD', 77020 ); // alias
  define( 'PCLZIP_OPT_TEMP_FILE_ON', 77021 );
  define( 'PCLZIP_OPT_ADD_TEMP_FILE_ON', 77021 ); // alias
  define( 'PCLZIP_OPT_TEMP_FILE_OFF', 77022 );
  define( 'PCLZIP_OPT_ADD_TEMP_FILE_OFF', 77022 ); // alias
  
  // ----- File description attributes
  define( 'PCLZIP_ATT_FILE_NAME', 79001 );
  define( 'PCLZIP_ATT_FILE_NEW_SHORT_NAME', 79002 );
  define( 'PCLZIP_ATT_FILE_NEW_FULL_NAME', 79003 );
  define( 'PCLZIP_ATT_FILE_MTIME', 79004 );
  define( 'PCLZIP_ATT_FILE_CONTENT', 79005 );
  define( 'PCLZIP_ATT_FILE_COMMENT', 79006 );

  // ----- Call backs values
  define( 'PCLZIP_CB_PRE_EXTRACT', 78001 );
  define( 'PCLZIP_CB_POST_EXTRACT', 78002 );
  define( 'PCLZIP_CB_PRE_ADD', 78003 );
  define( 'PCLZIP_CB_POST_ADD', 78004 );
  /* For futur use
  define( 'PCLZIP_CB_PRE_LIST', 78005 );
  define( 'PCLZIP_CB_POST_LIST', 78006 );
  define( 'PCLZIP_CB_PRE_DELETE', 78007 );
  define( 'PCLZIP_CB_POST_DELETE', 78008 );
  */

  // --------------------------------------------------------------------------------
  // Class : PclZip
  // Description :
  //   PclZip is the class that represent a Zip archive.
  //   The public methods allow the manipulation of the archive.
  // Attributes :
  //   Attributes must not be accessed directly.
  // Methods :
  //   PclZip() : Object creator
  //   create() : Creates the Zip archive
  //   listContent() : List the content of the Zip archive
  //   extract() : Extract the content of the archive
  //   properties() : List the properties of the archive
  // --------------------------------------------------------------------------------
  class PclZip
  {
    // ----- Filename of the zip file
    var $zipname = '';

    // ----- File descriptor of the zip file
    var $zip_fd = 0;

    // ----- Internal error handling
    var $error_code = 1;
    var $error_string = '';
    
    // ----- Current status of the magic_quotes_runtime
    // This value store the php configuration for magic_quotes
    // The class can then disable the magic_quotes and reset it after
    var $magic_quotes_status;

  // --------------------------------------------------------------------------------
  // Function : PclZip()
  // Description :
  //   Creates a PclZip object and set the name of the associated Zip archive
  //   filename.
  //   Note that no real action is taken, if the archive does not exist it is not
  //   created. Use create() for that.
  // --------------------------------------------------------------------------------
  function PclZip($p_zipname)
  {

    // ----- Tests the zlib
    if (!function_exists('gzopen'))
    {
      die('Abort '.basename(__FILE__).' : Missing zlib extensions');
    }

    // ----- Set the attributes
    $this->zipname = $p_zipname;
    $this->zip_fd = 0;
    $this->magic_quotes_status = -1;

    // ----- Return
    return;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   create($p_filelist, $p_add_dir="", $p_remove_dir="")
  //   create($p_filelist, $p_option, $p_option_value, ...)
  // Description :
  //   This method supports two different synopsis. The first one is historical.
  //   This method creates a Zip Archive. The Zip file is created in the
  //   filesystem. The files and directories indicated in $p_filelist
  //   are added in the archive. See the parameters description for the
  //   supported format of $p_filelist.
  //   When a directory is in the list, the directory and its content is added
  //   in the archive.
  //   In this synopsis, the function takes an optional variable list of
  //   options. See bellow the supported options.
  // Parameters :
  //   $p_filelist : An array containing file or directory names, or
  //                 a string containing one filename or one directory name, or
  //                 a string containing a list of filenames and/or directory
  //                 names separated by spaces.
  //   $p_add_dir : A path to add before the real path of the archived file,
  //                in order to have it memorized in the archive.
  //   $p_remove_dir : A path to remove from the real path of the file to archive,
  //                   in order to have a shorter path memorized in the archive.
  //                   When $p_add_dir and $p_remove_dir are set, $p_remove_dir
  //                   is removed first, before $p_add_dir is added.
  // Options :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_OPT_COMMENT :
  //   PCLZIP_CB_PRE_ADD :
  //   PCLZIP_CB_POST_ADD :
  // Return Values :
  //   0 on failure,
  //   The list of the added files, with a status of the add action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function create($p_filelist)
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Set default values
    $v_options = array();
    $v_options[PCLZIP_OPT_NO_COMPRESSION] = FALSE;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Look for arguments
    if ($v_size > 1) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Remove from the options list the first argument
      array_shift($v_arg_list);
      $v_size--;

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_ADD => 'optional',
                                                   PCLZIP_CB_POST_ADD => 'optional',
                                                   PCLZIP_OPT_NO_COMPRESSION => 'optional',
                                                   PCLZIP_OPT_COMMENT => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
                                                   //, PCLZIP_OPT_CRYPT => 'optional'
                                             ));
        if ($v_result != 1) {
          return 0;
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_options[PCLZIP_OPT_ADD_PATH] = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_options[PCLZIP_OPT_REMOVE_PATH] = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER,
		                       "Invalid number / type of arguments");
          return 0;
        }
      }
    }
    
    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Init
    $v_string_list = array();
    $v_att_list = array();
    $v_filedescr_list = array();
    $p_result_list = array();
    
    // ----- Look if the $p_filelist is really an array
    if (is_array($p_filelist)) {
    
      // ----- Look if the first element is also an array
      //       This will mean that this is a file description entry
      if (isset($p_filelist[0]) && is_array($p_filelist[0])) {
        $v_att_list = $p_filelist;
      }
      
      // ----- The list is a list of string names
      else {
        $v_string_list = $p_filelist;
      }
    }

    // ----- Look if the $p_filelist is a string
    else if (is_string($p_filelist)) {
      // ----- Create a list from the string
      $v_string_list = explode(PCLZIP_SEPARATOR, $p_filelist);
    }

    // ----- Invalid variable type for $p_filelist
    else {
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type p_filelist");
      return 0;
    }
    
    // ----- Reformat the string list
    if (sizeof($v_string_list) != 0) {
      foreach ($v_string_list as $v_string) {
        if ($v_string != '') {
          $v_att_list[][PCLZIP_ATT_FILE_NAME] = $v_string;
        }
        else {
        }
      }
    }
    
    // ----- For each file in the list check the attributes
    $v_supported_attributes
    = array ( PCLZIP_ATT_FILE_NAME => 'mandatory'
             ,PCLZIP_ATT_FILE_NEW_SHORT_NAME => 'optional'
             ,PCLZIP_ATT_FILE_NEW_FULL_NAME => 'optional'
             ,PCLZIP_ATT_FILE_MTIME => 'optional'
             ,PCLZIP_ATT_FILE_CONTENT => 'optional'
             ,PCLZIP_ATT_FILE_COMMENT => 'optional'
						);
    foreach ($v_att_list as $v_entry) {
      $v_result = $this->privFileDescrParseAtt($v_entry,
                                               $v_filedescr_list[],
                                               $v_options,
                                               $v_supported_attributes);
      if ($v_result != 1) {
        return 0;
      }
    }

    // ----- Expand the filelist (expand directories)
    $v_result = $this->privFileDescrExpand($v_filedescr_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Call the create fct
    $v_result = $this->privCreate($v_filedescr_list, $p_result_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Return
    return $p_result_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   add($p_filelist, $p_add_dir="", $p_remove_dir="")
  //   add($p_filelist, $p_option, $p_option_value, ...)
  // Description :
  //   This method supports two synopsis. The first one is historical.
  //   This methods add the list of files in an existing archive.
  //   If a file with the same name already exists, it is added at the end of the
  //   archive, the first one is still present.
  //   If the archive does not exist, it is created.
  // Parameters :
  //   $p_filelist : An array containing file or directory names, or
  //                 a string containing one filename or one directory name, or
  //                 a string containing a list of filenames and/or directory
  //                 names separated by spaces.
  //   $p_add_dir : A path to add before the real path of the archived file,
  //                in order to have it memorized in the archive.
  //   $p_remove_dir : A path to remove from the real path of the file to archive,
  //                   in order to have a shorter path memorized in the archive.
  //                   When $p_add_dir and $p_remove_dir are set, $p_remove_dir
  //                   is removed first, before $p_add_dir is added.
  // Options :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_OPT_COMMENT :
  //   PCLZIP_OPT_ADD_COMMENT :
  //   PCLZIP_OPT_PREPEND_COMMENT :
  //   PCLZIP_CB_PRE_ADD :
  //   PCLZIP_CB_POST_ADD :
  // Return Values :
  //   0 on failure,
  //   The list of the added files, with a status of the add action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function add($p_filelist)
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Set default values
    $v_options = array();
    $v_options[PCLZIP_OPT_NO_COMPRESSION] = FALSE;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Look for arguments
    if ($v_size > 1) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Remove form the options list the first argument
      array_shift($v_arg_list);
      $v_size--;

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_ADD => 'optional',
                                                   PCLZIP_CB_POST_ADD => 'optional',
                                                   PCLZIP_OPT_NO_COMPRESSION => 'optional',
                                                   PCLZIP_OPT_COMMENT => 'optional',
                                                   PCLZIP_OPT_ADD_COMMENT => 'optional',
                                                   PCLZIP_OPT_PREPEND_COMMENT => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
                                                   //, PCLZIP_OPT_CRYPT => 'optional'
												   ));
        if ($v_result != 1) {
          return 0;
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_options[PCLZIP_OPT_ADD_PATH] = $v_add_path = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_options[PCLZIP_OPT_REMOVE_PATH] = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid number / type of arguments");

          // ----- Return
          return 0;
        }
      }
    }

    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Init
    $v_string_list = array();
    $v_att_list = array();
    $v_filedescr_list = array();
    $p_result_list = array();
    
    // ----- Look if the $p_filelist is really an array
    if (is_array($p_filelist)) {
    
      // ----- Look if the first element is also an array
      //       This will mean that this is a file description entry
      if (isset($p_filelist[0]) && is_array($p_filelist[0])) {
        $v_att_list = $p_filelist;
      }
      
      // ----- The list is a list of string names
      else {
        $v_string_list = $p_filelist;
      }
    }

    // ----- Look if the $p_filelist is a string
    else if (is_string($p_filelist)) {
      // ----- Create a list from the string
      $v_string_list = explode(PCLZIP_SEPARATOR, $p_filelist);
    }

    // ----- Invalid variable type for $p_filelist
    else {
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type '".gettype($p_filelist)."' for p_filelist");
      return 0;
    }
    
    // ----- Reformat the string list
    if (sizeof($v_string_list) != 0) {
      foreach ($v_string_list as $v_string) {
        $v_att_list[][PCLZIP_ATT_FILE_NAME] = $v_string;
      }
    }
    
    // ----- For each file in the list check the attributes
    $v_supported_attributes
    = array ( PCLZIP_ATT_FILE_NAME => 'mandatory'
             ,PCLZIP_ATT_FILE_NEW_SHORT_NAME => 'optional'
             ,PCLZIP_ATT_FILE_NEW_FULL_NAME => 'optional'
             ,PCLZIP_ATT_FILE_MTIME => 'optional'
             ,PCLZIP_ATT_FILE_CONTENT => 'optional'
             ,PCLZIP_ATT_FILE_COMMENT => 'optional'
						);
    foreach ($v_att_list as $v_entry) {
      $v_result = $this->privFileDescrParseAtt($v_entry,
                                               $v_filedescr_list[],
                                               $v_options,
                                               $v_supported_attributes);
      if ($v_result != 1) {
        return 0;
      }
    }

    // ----- Expand the filelist (expand directories)
    $v_result = $this->privFileDescrExpand($v_filedescr_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Call the create fct
    $v_result = $this->privAdd($v_filedescr_list, $p_result_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Return
    return $p_result_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : listContent()
  // Description :
  //   This public method, gives the list of the files and directories, with their
  //   properties.
  //   The properties of each entries in the list are (used also in other functions) :
  //     filename : Name of the file. For a create or add action it is the filename
  //                given by the user. For an extract function it is the filename
  //                of the extracted file.
  //     stored_filename : Name of the file / directory stored in the archive.
  //     size : Size of the stored file.
  //     compressed_size : Size of the file's data compressed in the archive
  //                       (without the headers overhead)
  //     mtime : Last known modification date of the file (UNIX timestamp)
  //     comment : Comment associated with the file
  //     folder : true | false
  //     index : index of the file in the archive
  //     status : status of the action (depending of the action) :
  //              Values are :
  //                ok : OK !
  //                filtered : the file / dir is not extracted (filtered by user)
  //                already_a_directory : the file can not be extracted because a
  //                                      directory with the same name already exists
  //                write_protected : the file can not be extracted because a file
  //                                  with the same name already exists and is
  //                                  write protected
  //                newer_exist : the file was not extracted because a newer file exists
  //                path_creation_fail : the file is not extracted because the folder
  //                                     does not exist and can not be created
  //                write_error : the file was not extracted because there was a
  //                              error while writing the file
  //                read_error : the file was not extracted because there was a error
  //                             while reading the file
  //                invalid_header : the file was not extracted because of an archive
  //                                 format error (bad file header)
  //   Note that each time a method can continue operating when there
  //   is an action error on a file, the error is only logged in the file status.
  // Return Values :
  //   0 on an unrecoverable failure,
  //   The list of the files in the archive.
  // --------------------------------------------------------------------------------
  function listContent()
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Call the extracting fct
    $p_list = array();
    if (($v_result = $this->privList($p_list)) != 1)
    {
      unset($p_list);
      return(0);
    }

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   extract($p_path="./", $p_remove_path="")
  //   extract([$p_option, $p_option_value, ...])
  // Description :
  //   This method supports two synopsis. The first one is historical.
  //   This method extract all the files / directories from the archive to the
  //   folder indicated in $p_path.
  //   If you want to ignore the 'root' part of path of the memorized files
  //   you can indicate this in the optional $p_remove_path parameter.
  //   By default, if a newer file with the same name already exists, the
  //   file is not extracted.
  //
  //   If both PCLZIP_OPT_PATH and PCLZIP_OPT_ADD_PATH aoptions
  //   are used, the path indicated in PCLZIP_OPT_ADD_PATH is append
  //   at the end of the path value of PCLZIP_OPT_PATH.
  // Parameters :
  //   $p_path : Path where the files and directories are to be extracted
  //   $p_remove_path : First part ('root' part) of the memorized path
  //                    (if any similar) to remove while extracting.
  // Options :
  //   PCLZIP_OPT_PATH :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_CB_PRE_EXTRACT :
  //   PCLZIP_CB_POST_EXTRACT :
  // Return Values :
  //   0 or a negative value on failure,
  //   The list of the extracted files, with a status of the action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function extract()
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Set default values
    $v_options = array();
//    $v_path = "./";
    $v_path = '';
    $v_remove_path = "";
    $v_remove_all_path = false;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Default values for option
    $v_options[PCLZIP_OPT_EXTRACT_AS_STRING] = FALSE;

    // ----- Look for arguments
    if ($v_size > 0) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_EXTRACT => 'optional',
                                                   PCLZIP_CB_POST_EXTRACT => 'optional',
                                                   PCLZIP_OPT_SET_CHMOD => 'optional',
                                                   PCLZIP_OPT_BY_NAME => 'optional',
                                                   PCLZIP_OPT_BY_EREG => 'optional',
                                                   PCLZIP_OPT_BY_PREG => 'optional',
                                                   PCLZIP_OPT_BY_INDEX => 'optional',
                                                   PCLZIP_OPT_EXTRACT_AS_STRING => 'optional',
                                                   PCLZIP_OPT_EXTRACT_IN_OUTPUT => 'optional',
                                                   PCLZIP_OPT_REPLACE_NEWER => 'optional'
                                                   ,PCLZIP_OPT_STOP_ON_ERROR => 'optional'
                                                   ,PCLZIP_OPT_EXTRACT_DIR_RESTRICTION => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
												    ));
        if ($v_result != 1) {
          return 0;
        }

        // ----- Set the arguments
        if (isset($v_options[PCLZIP_OPT_PATH])) {
          $v_path = $v_options[PCLZIP_OPT_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_PATH])) {
          $v_remove_path = $v_options[PCLZIP_OPT_REMOVE_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_ALL_PATH])) {
          $v_remove_all_path = $v_options[PCLZIP_OPT_REMOVE_ALL_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_ADD_PATH])) {
          // ----- Check for '/' in last path char
          if ((strlen($v_path) > 0) && (substr($v_path, -1) != '/')) {
            $v_path .= '/';
          }
          $v_path .= $v_options[PCLZIP_OPT_ADD_PATH];
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_path = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_remove_path = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid number / type of arguments");

          // ----- Return
          return 0;
        }
      }
    }

    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Trace

    // ----- Call the extracting fct
    $p_list = array();
    $v_result = $this->privExtractByRule($p_list, $v_path, $v_remove_path,
	                                     $v_remove_all_path, $v_options);
    if ($v_result < 1) {
      unset($p_list);
      return(0);
    }

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------


  // --------------------------------------------------------------------------------
  // Function :
  //   extractByIndex($p_index, $p_path="./", $p_remove_path="")
  //   extractByIndex($p_index, [$p_option, $p_option_value, ...])
  // Description :
  //   This method supports two synopsis. The first one is historical.
  //   This method is doing a partial extract of the archive.
  //   The extracted files or folders are identified by their index in the
  //   archive (from 0 to n).
  //   Note that if the index identify a folder, only the folder entry is
  //   extracted, not all the files included in the archive.
  // Parameters :
  //   $p_index : A single index (integer) or a string of indexes of files to
  //              extract. The form of the string is "0,4-6,8-12" with only numbers
  //              and '-' for range or ',' to separate ranges. No spaces or ';'
  //              are allowed.
  //   $p_path : Path where the files and directories are to be extracted
  //   $p_remove_path : First part ('root' part) of the memorized path
  //                    (if any similar) to remove while extracting.
  // Options :
  //   PCLZIP_OPT_PATH :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_OPT_EXTRACT_AS_STRING : The files are extracted as strings and
  //     not as files.
  //     The resulting content is in a new field 'content' in the file
  //     structure.
  //     This option must be used alone (any other options are ignored).
  //   PCLZIP_CB_PRE_EXTRACT :
  //   PCLZIP_CB_POST_EXTRACT :
  // Return Values :
  //   0 on failure,
  //   The list of the extracted files, with a status of the action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  //function extractByIndex($p_index, options...)
  function extractByIndex($p_index)
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Set default values
    $v_options = array();
//    $v_path = "./";
    $v_path = '';
    $v_remove_path = "";
    $v_remove_all_path = false;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Default values for option
    $v_options[PCLZIP_OPT_EXTRACT_AS_STRING] = FALSE;

    // ----- Look for arguments
    if ($v_size > 1) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Remove form the options list the first argument
      array_shift($v_arg_list);
      $v_size--;

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_EXTRACT_AS_STRING => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_EXTRACT => 'optional',
                                                   PCLZIP_CB_POST_EXTRACT => 'optional',
                                                   PCLZIP_OPT_SET_CHMOD => 'optional',
                                                   PCLZIP_OPT_REPLACE_NEWER => 'optional'
                                                   ,PCLZIP_OPT_STOP_ON_ERROR => 'optional'
                                                   ,PCLZIP_OPT_EXTRACT_DIR_RESTRICTION => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
												   ));
        if ($v_result != 1) {
          return 0;
        }

        // ----- Set the arguments
        if (isset($v_options[PCLZIP_OPT_PATH])) {
          $v_path = $v_options[PCLZIP_OPT_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_PATH])) {
          $v_remove_path = $v_options[PCLZIP_OPT_REMOVE_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_ALL_PATH])) {
          $v_remove_all_path = $v_options[PCLZIP_OPT_REMOVE_ALL_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_ADD_PATH])) {
          // ----- Check for '/' in last path char
          if ((strlen($v_path) > 0) && (substr($v_path, -1) != '/')) {
            $v_path .= '/';
          }
          $v_path .= $v_options[PCLZIP_OPT_ADD_PATH];
        }
        if (!isset($v_options[PCLZIP_OPT_EXTRACT_AS_STRING])) {
          $v_options[PCLZIP_OPT_EXTRACT_AS_STRING] = FALSE;
        }
        else {
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_path = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_remove_path = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid number / type of arguments");

          // ----- Return
          return 0;
        }
      }
    }

    // ----- Trace

    // ----- Trick
    // Here I want to reuse extractByRule(), so I need to parse the $p_index
    // with privParseOptions()
    $v_arg_trick = array (PCLZIP_OPT_BY_INDEX, $p_index);
    $v_options_trick = array();
    $v_result = $this->privParseOptions($v_arg_trick, sizeof($v_arg_trick), $v_options_trick,
                                        array (PCLZIP_OPT_BY_INDEX => 'optional' ));
    if ($v_result != 1) {
        return 0;
    }
    $v_options[PCLZIP_OPT_BY_INDEX] = $v_options_trick[PCLZIP_OPT_BY_INDEX];

    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Call the extracting fct
    if (($v_result = $this->privExtractByRule($p_list, $v_path, $v_remove_path, $v_remove_all_path, $v_options)) < 1) {
        return(0);
    }

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   delete([$p_option, $p_option_value, ...])
  // Description :
  //   This method removes files from the archive.
  //   If no parameters are given, then all the archive is emptied.
  // Parameters :
  //   None or optional arguments.
  // Options :
  //   PCLZIP_OPT_BY_INDEX :
  //   PCLZIP_OPT_BY_NAME :
  //   PCLZIP_OPT_BY_EREG : 
  //   PCLZIP_OPT_BY_PREG :
  // Return Values :
  //   0 on failure,
  //   The list of the files which are still present in the archive.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function delete()
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Set default values
    $v_options = array();

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Look for arguments
    if ($v_size > 0) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Parse the options
      $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                        array (PCLZIP_OPT_BY_NAME => 'optional',
                                               PCLZIP_OPT_BY_EREG => 'optional',
                                               PCLZIP_OPT_BY_PREG => 'optional',
                                               PCLZIP_OPT_BY_INDEX => 'optional' ));
      if ($v_result != 1) {
          return 0;
      }
    }

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Call the delete fct
    $v_list = array();
    if (($v_result = $this->privDeleteByRule($v_list, $v_options)) != 1) {
      $this->privSwapBackMagicQuotes();
      unset($v_list);
      return(0);
    }

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : deleteByIndex()
  // Description :
  //   ***** Deprecated *****
  //   delete(PCLZIP_OPT_BY_INDEX, $p_index) should be prefered.
  // --------------------------------------------------------------------------------
  function deleteByIndex($p_index)
  {
    
    $p_list = $this->delete(PCLZIP_OPT_BY_INDEX, $p_index);

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : properties()
  // Description :
  //   This method gives the properties of the archive.
  //   The properties are :
  //     nb : Number of files in the archive
  //     comment : Comment associated with the archive file
  //     status : not_exist, ok
  // Parameters :
  //   None
  // Return Values :
  //   0 on failure,
  //   An array with the archive properties.
  // --------------------------------------------------------------------------------
  function properties()
  {

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      $this->privSwapBackMagicQuotes();
      return(0);
    }

    // ----- Default properties
    $v_prop = array();
    $v_prop['comment'] = '';
    $v_prop['nb'] = 0;
    $v_prop['status'] = 'not_exist';

    // ----- Look if file exists
    if (@is_file($this->zipname))
    {
      // ----- Open the zip file
      if (($this->zip_fd = @fopen($this->zipname, 'rb')) == 0)
      {
        $this->privSwapBackMagicQuotes();
        
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \''.$this->zipname.'\' in binary read mode');

        // ----- Return
        return 0;
      }

      // ----- Read the central directory informations
      $v_central_dir = array();
      if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
      {
        $this->privSwapBackMagicQuotes();
        return 0;
      }

      // ----- Close the zip file
      $this->privCloseFd();

      // ----- Set the user attributes
      $v_prop['comment'] = $v_central_dir['comment'];
      $v_prop['nb'] = $v_central_dir['entries'];
      $v_prop['status'] = 'ok';
    }

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_prop;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : duplicate()
  // Description :
  //   This method creates an archive by copying the content of an other one. If
  //   the archive already exist, it is replaced by the new one without any warning.
  // Parameters :
  //   $p_archive : The filename of a valid archive, or
  //                a valid PclZip object.
  // Return Values :
  //   1 on success.
  //   0 or a negative value on error (error code).
  // --------------------------------------------------------------------------------
  function duplicate($p_archive)
  {
    $v_result = 1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Look if the $p_archive is a PclZip object
    if ((is_object($p_archive)) && (get_class($p_archive) == 'pclzip'))
    {

      // ----- Duplicate the archive
      $v_result = $this->privDuplicate($p_archive->zipname);
    }

    // ----- Look if the $p_archive is a string (so a filename)
    else if (is_string($p_archive))
    {

      // ----- Check that $p_archive is a valid zip file
      // TBC : Should also check the archive format
      if (!is_file($p_archive)) {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "No file with filename '".$p_archive."'");
        $v_result = PCLZIP_ERR_MISSING_FILE;
      }
      else {
        // ----- Duplicate the archive
        $v_result = $this->privDuplicate($p_archive);
      }
    }

    // ----- Invalid variable
    else
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type p_archive_to_add");
      $v_result = PCLZIP_ERR_INVALID_PARAMETER;
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : merge()
  // Description :
  //   This method merge the $p_archive_to_add archive at the end of the current
  //   one ($this).
  //   If the archive ($this) does not exist, the merge becomes a duplicate.
  //   If the $p_archive_to_add archive does not exist, the merge is a success.
  // Parameters :
  //   $p_archive_to_add : It can be directly the filename of a valid zip archive,
  //                       or a PclZip object archive.
  // Return Values :
  //   1 on success,
  //   0 or negative values on error (see below).
  // --------------------------------------------------------------------------------
  function merge($p_archive_to_add)
  {
    $v_result = 1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Look if the $p_archive_to_add is a PclZip object
    if ((is_object($p_archive_to_add)) && (get_class($p_archive_to_add) == 'pclzip'))
    {

      // ----- Merge the archive
      $v_result = $this->privMerge($p_archive_to_add);
    }

    // ----- Look if the $p_archive_to_add is a string (so a filename)
    else if (is_string($p_archive_to_add))
    {

      // ----- Create a temporary archive
      $v_object_archive = new PclZip($p_archive_to_add);

      // ----- Merge the archive
      $v_result = $this->privMerge($v_object_archive);
    }

    // ----- Invalid variable
    else
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type p_archive_to_add");
      $v_result = PCLZIP_ERR_INVALID_PARAMETER;
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------



  // --------------------------------------------------------------------------------
  // Function : errorCode()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function errorCode()
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      return(PclErrorCode());
    }
    else {
      return($this->error_code);
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : errorName()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function errorName($p_with_code=false)
  {
    $v_name = array ( PCLZIP_ERR_NO_ERROR => 'PCLZIP_ERR_NO_ERROR',
                      PCLZIP_ERR_WRITE_OPEN_FAIL => 'PCLZIP_ERR_WRITE_OPEN_FAIL',
                      PCLZIP_ERR_READ_OPEN_FAIL => 'PCLZIP_ERR_READ_OPEN_FAIL',
                      PCLZIP_ERR_INVALID_PARAMETER => 'PCLZIP_ERR_INVALID_PARAMETER',
                      PCLZIP_ERR_MISSING_FILE => 'PCLZIP_ERR_MISSING_FILE',
                      PCLZIP_ERR_FILENAME_TOO_LONG => 'PCLZIP_ERR_FILENAME_TOO_LONG',
                      PCLZIP_ERR_INVALID_ZIP => 'PCLZIP_ERR_INVALID_ZIP',
                      PCLZIP_ERR_BAD_EXTRACTED_FILE => 'PCLZIP_ERR_BAD_EXTRACTED_FILE',
                      PCLZIP_ERR_DIR_CREATE_FAIL => 'PCLZIP_ERR_DIR_CREATE_FAIL',
                      PCLZIP_ERR_BAD_EXTENSION => 'PCLZIP_ERR_BAD_EXTENSION',
                      PCLZIP_ERR_BAD_FORMAT => 'PCLZIP_ERR_BAD_FORMAT',
                      PCLZIP_ERR_DELETE_FILE_FAIL => 'PCLZIP_ERR_DELETE_FILE_FAIL',
                      PCLZIP_ERR_RENAME_FILE_FAIL => 'PCLZIP_ERR_RENAME_FILE_FAIL',
                      PCLZIP_ERR_BAD_CHECKSUM => 'PCLZIP_ERR_BAD_CHECKSUM',
                      PCLZIP_ERR_INVALID_ARCHIVE_ZIP => 'PCLZIP_ERR_INVALID_ARCHIVE_ZIP',
                      PCLZIP_ERR_MISSING_OPTION_VALUE => 'PCLZIP_ERR_MISSING_OPTION_VALUE',
                      PCLZIP_ERR_INVALID_OPTION_VALUE => 'PCLZIP_ERR_INVALID_OPTION_VALUE',
                      PCLZIP_ERR_UNSUPPORTED_COMPRESSION => 'PCLZIP_ERR_UNSUPPORTED_COMPRESSION',
                      PCLZIP_ERR_UNSUPPORTED_ENCRYPTION => 'PCLZIP_ERR_UNSUPPORTED_ENCRYPTION'
                      ,PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE => 'PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE'
                      ,PCLZIP_ERR_DIRECTORY_RESTRICTION => 'PCLZIP_ERR_DIRECTORY_RESTRICTION'
                    );

    if (isset($v_name[$this->error_code])) {
      $v_value = $v_name[$this->error_code];
    }
    else {
      $v_value = 'NoName';
    }

    if ($p_with_code) {
      return($v_value.' ('.$this->error_code.')');
    }
    else {
      return($v_value);
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : errorInfo()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function errorInfo($p_full=false)
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      return(PclErrorString());
    }
    else {
      if ($p_full) {
        return($this->errorName(true)." : ".$this->error_string);
      }
      else {
        return($this->error_string." [code ".$this->error_code."]");
      }
    }
  }
  // --------------------------------------------------------------------------------


// --------------------------------------------------------------------------------
// ***** UNDER THIS LINE ARE DEFINED PRIVATE INTERNAL FUNCTIONS *****
// *****                                                        *****
// *****       THESES FUNCTIONS MUST NOT BE USED DIRECTLY       *****
// --------------------------------------------------------------------------------



  // --------------------------------------------------------------------------------
  // Function : privCheckFormat()
  // Description :
  //   This method check that the archive exists and is a valid zip archive.
  //   Several level of check exists. (futur)
  // Parameters :
  //   $p_level : Level of check. Default 0.
  //              0 : Check the first bytes (magic codes) (default value))
  //              1 : 0 + Check the central directory (futur)
  //              2 : 1 + Check each file header (futur)
  // Return Values :
  //   true on success,
  //   false on error, the error code is set.
  // --------------------------------------------------------------------------------
  function privCheckFormat($p_level=0)
  {
    $v_result = true;

	// ----- Reset the file system cache
    clearstatcache();

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Look if the file exits
    if (!is_file($this->zipname)) {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "Missing archive file '".$this->zipname."'");
      return(false);
    }

    // ----- Check that the file is readeable
    if (!is_readable($this->zipname)) {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, "Unable to read archive '".$this->zipname."'");
      return(false);
    }

    // ----- Check the magic code
    // TBC

    // ----- Check the central header
    // TBC

    // ----- Check each file header
    // TBC

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privParseOptions()
  // Description :
  //   This internal methods reads the variable list of arguments ($p_options_list,
  //   $p_size) and generate an array with the options and values ($v_result_list).
  //   $v_requested_options contains the options that can be present and those that
  //   must be present.
  //   $v_requested_options is an array, with the option value as key, and 'optional',
  //   or 'mandatory' as value.
  // Parameters :
  //   See above.
  // Return Values :
  //   1 on success.
  //   0 on failure.
  // --------------------------------------------------------------------------------
  function privParseOptions(&$p_options_list, $p_size, &$v_result_list, $v_requested_options=false)
  {
    $v_result=1;
    
    // ----- Read the options
    $i=0;
    while ($i<$p_size) {

      // ----- Check if the option is supported
      if (!isset($v_requested_options[$p_options_list[$i]])) {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid optional parameter '".$p_options_list[$i]."' for this method");

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Look for next option
      switch ($p_options_list[$i]) {
        // ----- Look for options that request a path value
        case PCLZIP_OPT_PATH :
        case PCLZIP_OPT_REMOVE_PATH :
        case PCLZIP_OPT_ADD_PATH :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_result_list[$p_options_list[$i]] = PclZipUtilTranslateWinPath($p_options_list[$i+1], FALSE);
          $i++;
        break;

        case PCLZIP_OPT_TEMP_FILE_THRESHOLD :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
            return PclZip::errorCode();
          }
          
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_OFF])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_OFF'");
            return PclZip::errorCode();
          }
          
          // ----- Check the value
          $v_value = $p_options_list[$i+1];
          if ((!is_integer($v_value)) || ($v_value<0)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Integer expected for option '".PclZipUtilOptionText($p_options_list[$i])."'");
            return PclZip::errorCode();
          }

          // ----- Get the value (and convert it in bytes)
          $v_result_list[$p_options_list[$i]] = $v_value*1048576;
          $i++;
        break;

        case PCLZIP_OPT_TEMP_FILE_ON :
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_OFF])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_OFF'");
            return PclZip::errorCode();
          }
          
          $v_result_list[$p_options_list[$i]] = true;
        break;

        case PCLZIP_OPT_TEMP_FILE_OFF :
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_ON])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_ON'");
            return PclZip::errorCode();
          }
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_THRESHOLD])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_THRESHOLD'");
            return PclZip::errorCode();
          }
          
          $v_result_list[$p_options_list[$i]] = true;
        break;

        case PCLZIP_OPT_EXTRACT_DIR_RESTRICTION :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (   is_string($p_options_list[$i+1])
              && ($p_options_list[$i+1] != '')) {
            $v_result_list[$p_options_list[$i]] = PclZipUtilTranslateWinPath($p_options_list[$i+1], FALSE);
            $i++;
          }
          else {
          }
        break;

        // ----- Look for options that request an array of string for value
        case PCLZIP_OPT_BY_NAME :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (is_string($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]][0] = $p_options_list[$i+1];
          }
          else if (is_array($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Wrong parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }
          $i++;
        break;

        // ----- Look for options that request an EREG or PREG expression
        case PCLZIP_OPT_BY_EREG :
          // ereg() is deprecated starting with PHP 5.3. Move PCLZIP_OPT_BY_EREG
          // to PCLZIP_OPT_BY_PREG
          $p_options_list[$i] = PCLZIP_OPT_BY_PREG;
        case PCLZIP_OPT_BY_PREG :
        //case PCLZIP_OPT_CRYPT :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (is_string($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Wrong parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }
          $i++;
        break;

        // ----- Look for options that takes a string
        case PCLZIP_OPT_COMMENT :
        case PCLZIP_OPT_ADD_COMMENT :
        case PCLZIP_OPT_PREPEND_COMMENT :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE,
			                     "Missing parameter value for option '"
								 .PclZipUtilOptionText($p_options_list[$i])
								 ."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (is_string($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE,
			                     "Wrong parameter value for option '"
								 .PclZipUtilOptionText($p_options_list[$i])
								 ."'");

            // ----- Return
            return PclZip::errorCode();
          }
          $i++;
        break;

        // ----- Look for options that request an array of index
        case PCLZIP_OPT_BY_INDEX :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_work_list = array();
          if (is_string($p_options_list[$i+1])) {

              // ----- Remove spaces
              $p_options_list[$i+1] = strtr($p_options_list[$i+1], ' ', '');

              // ----- Parse items
              $v_work_list = explode(",", $p_options_list[$i+1]);
          }
          else if (is_integer($p_options_list[$i+1])) {
              $v_work_list[0] = $p_options_list[$i+1].'-'.$p_options_list[$i+1];
          }
          else if (is_array($p_options_list[$i+1])) {
              $v_work_list = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Value must be integer, string or array for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }
          
          // ----- Reduce the index list
          // each index item in the list must be a couple with a start and
          // an end value : [0,3], [5-5], [8-10], ...
          // ----- Check the format of each item
          $v_sort_flag=false;
          $v_sort_value=0;
          for ($j=0; $j<sizeof($v_work_list); $j++) {
              // ----- Explode the item
              $v_item_list = explode("-", $v_work_list[$j]);
              $v_size_item_list = sizeof($v_item_list);
              
              // ----- TBC : Here we might check that each item is a
              // real integer ...
              
              // ----- Look for single value
              if ($v_size_item_list == 1) {
                  // ----- Set the option value
                  $v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];
                  $v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[0];
              }
              elseif ($v_size_item_list == 2) {
                  // ----- Set the option value
                  $v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];
                  $v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[1];
              }
              else {
                  // ----- Error log
                  PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Too many values in index range for option '".PclZipUtilOptionText($p_options_list[$i])."'");

                  // ----- Return
                  return PclZip::errorCode();
              }


              // ----- Look for list sort
              if ($v_result_list[$p_options_list[$i]][$j]['start'] < $v_sort_value) {
                  $v_sort_flag=true;

                  // ----- TBC : An automatic sort should be writen ...
                  // ----- Error log
                  PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Invalid order of index range for option '".PclZipUtilOptionText($p_options_list[$i])."'");

                  // ----- Return
                  return PclZip::errorCode();
              }
              $v_sort_value = $v_result_list[$p_options_list[$i]][$j]['start'];
          }
          
          // ----- Sort the items
          if ($v_sort_flag) {
              // TBC : To Be Completed
          }

          // ----- Next option
          $i++;
        break;

        // ----- Look for options that request no value
        case PCLZIP_OPT_REMOVE_ALL_PATH :
        case PCLZIP_OPT_EXTRACT_AS_STRING :
        case PCLZIP_OPT_NO_COMPRESSION :
        case PCLZIP_OPT_EXTRACT_IN_OUTPUT :
        case PCLZIP_OPT_REPLACE_NEWER :
        case PCLZIP_OPT_STOP_ON_ERROR :
          $v_result_list[$p_options_list[$i]] = true;
        break;

        // ----- Look for options that request an octal value
        case PCLZIP_OPT_SET_CHMOD :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          $i++;
        break;

        // ----- Look for options that request a call-back
        case PCLZIP_CB_PRE_EXTRACT :
        case PCLZIP_CB_POST_EXTRACT :
        case PCLZIP_CB_PRE_ADD :
        case PCLZIP_CB_POST_ADD :
        /* for futur use
        case PCLZIP_CB_PRE_DELETE :
        case PCLZIP_CB_POST_DELETE :
        case PCLZIP_CB_PRE_LIST :
        case PCLZIP_CB_POST_LIST :
        */
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_function_name = $p_options_list[$i+1];

          // ----- Check that the value is a valid existing function
          if (!function_exists($v_function_name)) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Function '".$v_function_name."()' is not an existing function for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Set the attribute
          $v_result_list[$p_options_list[$i]] = $v_function_name;
          $i++;
        break;

        default :
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER,
		                       "Unknown parameter '"
							   .$p_options_list[$i]."'");

          // ----- Return
          return PclZip::errorCode();
      }

      // ----- Next options
      $i++;
    }

    // ----- Look for mandatory options
    if ($v_requested_options !== false) {
      for ($key=reset($v_requested_options); $key=key($v_requested_options); $key=next($v_requested_options)) {
        // ----- Look for mandatory option
        if ($v_requested_options[$key] == 'mandatory') {
          // ----- Look if present
          if (!isset($v_result_list[$key])) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Missing mandatory parameter ".PclZipUtilOptionText($key)."(".$key.")");

            // ----- Return
            return PclZip::errorCode();
          }
        }
      }
    }
    
    // ----- Look for default values
    if (!isset($v_result_list[PCLZIP_OPT_TEMP_FILE_THRESHOLD])) {
      
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privOptionDefaultThreshold()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privOptionDefaultThreshold(&$p_options)
  {
    $v_result=1;
    
    if (isset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD])
        || isset($p_options[PCLZIP_OPT_TEMP_FILE_OFF])) {
      return $v_result;
    }
    
    // ----- Get 'memory_limit' configuration value
    $v_memory_limit = ini_get('memory_limit');
    $v_memory_limit = trim($v_memory_limit);
    $last = strtolower(substr($v_memory_limit, -1));
 
    if($last == 'g')
        //$v_memory_limit = $v_memory_limit*1024*1024*1024;
        $v_memory_limit = $v_memory_limit*1073741824;
    if($last == 'm')
        //$v_memory_limit = $v_memory_limit*1024*1024;
        $v_memory_limit = $v_memory_limit*1048576;
    if($last == 'k')
        $v_memory_limit = $v_memory_limit*1024;
            
    $p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] = floor($v_memory_limit*PCLZIP_TEMPORARY_FILE_RATIO);
    

    // ----- Sanity check : No threshold if value lower than 1M
    if ($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] < 1048576) {
      unset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD]);
    }
          
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privFileDescrParseAtt()
  // Description :
  // Parameters :
  // Return Values :
  //   1 on success.
  //   0 on failure.
  // --------------------------------------------------------------------------------
  function privFileDescrParseAtt(&$p_file_list, &$p_filedescr, $v_options, $v_requested_options=false)
  {
    $v_result=1;
    
    // ----- For each file in the list check the attributes
    foreach ($p_file_list as $v_key => $v_value) {
    
      // ----- Check if the option is supported
      if (!isset($v_requested_options[$v_key])) {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid file attribute '".$v_key."' for this file");

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Look for attribute
      switch ($v_key) {
        case PCLZIP_ATT_FILE_NAME :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['filename'] = PclZipUtilPathReduction($v_value);
          
          if ($p_filedescr['filename'] == '') {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid empty filename for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

        break;

        case PCLZIP_ATT_FILE_NEW_SHORT_NAME :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['new_short_name'] = PclZipUtilPathReduction($v_value);

          if ($p_filedescr['new_short_name'] == '') {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid empty short filename for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }
        break;

        case PCLZIP_ATT_FILE_NEW_FULL_NAME :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['new_full_name'] = PclZipUtilPathReduction($v_value);

          if ($p_filedescr['new_full_name'] == '') {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid empty full filename for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }
        break;

        // ----- Look for options that takes a string
        case PCLZIP_ATT_FILE_COMMENT :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['comment'] = $v_value;
        break;

        case PCLZIP_ATT_FILE_MTIME :
          if (!is_integer($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". Integer expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['mtime'] = $v_value;
        break;

        case PCLZIP_ATT_FILE_CONTENT :
          $p_filedescr['content'] = $v_value;
        break;

        default :
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER,
		                           "Unknown parameter '".$v_key."'");

          // ----- Return
          return PclZip::errorCode();
      }

      // ----- Look for mandatory options
      if ($v_requested_options !== false) {
        for ($key=reset($v_requested_options); $key=key($v_requested_options); $key=next($v_requested_options)) {
          // ----- Look for mandatory option
          if ($v_requested_options[$key] == 'mandatory') {
            // ----- Look if present
            if (!isset($p_file_list[$key])) {
              PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Missing mandatory parameter ".PclZipUtilOptionText($key)."(".$key.")");
              return PclZip::errorCode();
            }
          }
        }
      }
    
    // end foreach
    }
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privFileDescrExpand()
  // Description :
  //   This method look for each item of the list to see if its a file, a folder
  //   or a string to be added as file. For any other type of files (link, other)
  //   just ignore the item.
  //   Then prepare the information that will be stored for that file.
  //   When its a folder, expand the folder with all the files that are in that 
  //   folder (recursively).
  // Parameters :
  // Return Values :
  //   1 on success.
  //   0 on failure.
  // --------------------------------------------------------------------------------
  function privFileDescrExpand(&$p_filedescr_list, &$p_options)
  {
    $v_result=1;
    
    // ----- Create a result list
    $v_result_list = array();
    
    // ----- Look each entry
    for ($i=0; $i<sizeof($p_filedescr_list); $i++) {
      
      // ----- Get filedescr
      $v_descr = $p_filedescr_list[$i];
      
      // ----- Reduce the filename
      $v_descr['filename'] = PclZipUtilTranslateWinPath($v_descr['filename'], false);
      $v_descr['filename'] = PclZipUtilPathReduction($v_descr['filename']);
      
      // ----- Look for real file or folder
      if (file_exists($v_descr['filename'])) {
        if (@is_file($v_descr['filename'])) {
          $v_descr['type'] = 'file';
        }
        else if (@is_dir($v_descr['filename'])) {
          $v_descr['type'] = 'folder';
        }
        else if (@is_link($v_descr['filename'])) {
          // skip
          continue;
        }
        else {
          // skip
          continue;
        }
      }
      
      // ----- Look for string added as file
      else if (isset($v_descr['content'])) {
        $v_descr['type'] = 'virtual_file';
      }
      
      // ----- Missing file
      else {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "File '".$v_descr['filename']."' does not exist");

        // ----- Return
        return PclZip::errorCode();
      }
      
      // ----- Calculate the stored filename
      $this->privCalculateStoredFilename($v_descr, $p_options);
      
      // ----- Add the descriptor in result list
      $v_result_list[sizeof($v_result_list)] = $v_descr;
      
      // ----- Look for folder
      if ($v_descr['type'] == 'folder') {
        // ----- List of items in folder
        $v_dirlist_descr = array();
        $v_dirlist_nb = 0;
        if ($v_folder_handler = @opendir($v_descr['filename'])) {
          while (($v_item_handler = @readdir($v_folder_handler)) !== false) {

            // ----- Skip '.' and '..'
            if (($v_item_handler == '.') || ($v_item_handler == '..')) {
                continue;
            }
            
            // ----- Compose the full filename
            $v_dirlist_descr[$v_dirlist_nb]['filename'] = $v_descr['filename'].'/'.$v_item_handler;
            
            // ----- Look for different stored filename
            // Because the name of the folder was changed, the name of the
            // files/sub-folders also change
            if (($v_descr['stored_filename'] != $v_descr['filename'])
                 && (!isset($p_options[PCLZIP_OPT_REMOVE_ALL_PATH]))) {
              if ($v_descr['stored_filename'] != '') {
                $v_dirlist_descr[$v_dirlist_nb]['new_full_name'] = $v_descr['stored_filename'].'/'.$v_item_handler;
              }
              else {
                $v_dirlist_descr[$v_dirlist_nb]['new_full_name'] = $v_item_handler;
              }
            }
      
            $v_dirlist_nb++;
          }
          
          @closedir($v_folder_handler);
        }
        else {
          // TBC : unable to open folder in read mode
        }
        
        // ----- Expand each element of the list
        if ($v_dirlist_nb != 0) {
          // ----- Expand
          if (($v_result = $this->privFileDescrExpand($v_dirlist_descr, $p_options)) != 1) {
            return $v_result;
          }
          
          // ----- Concat the resulting list
          $v_result_list = array_merge($v_result_list, $v_dirlist_descr);
        }
        else {
        }
          
        // ----- Free local array
        unset($v_dirlist_descr);
      }
    }
    
    // ----- Get the result list
    $p_filedescr_list = $v_result_list;

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCreate()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privCreate($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_list_detail = array();
    
    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Open the file in write mode
    if (($v_result = $this->privOpenFd('wb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Add the list of files
    $v_result = $this->privAddList($p_filedescr_list, $p_result_list, $p_options);

    // ----- Close
    $this->privCloseFd();

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAdd()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAdd($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_list_detail = array();

    // ----- Look if the archive exists or is empty
    if ((!is_file($this->zipname)) || (filesize($this->zipname) == 0))
    {

      // ----- Do a create
      $v_result = $this->privCreate($p_filedescr_list, $p_result_list, $p_options);

      // ----- Return
      return $v_result;
    }
    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('rb')) != 1)
    {
      // ----- Magic quotes trick
      $this->privSwapBackMagicQuotes();

      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privCloseFd();
      $this->privSwapBackMagicQuotes();
      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($this->zip_fd);

    // ----- Creates a temporay file
    $v_zip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.tmp';

    // ----- Open the temporary file in write mode
    if (($v_zip_temp_fd = @fopen($v_zip_temp_name, 'wb')) == 0)
    {
      $this->privCloseFd();
      $this->privSwapBackMagicQuotes();

      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_zip_temp_name.'\' in binary write mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Copy the files from the archive to the temporary file
    // TBC : Here I should better append the file and go back to erase the central dir
    $v_size = $v_central_dir['offset'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($this->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Swap the file descriptor
    // Here is a trick : I swap the temporary fd with the zip fd, in order to use
    // the following methods on the temporary fil and not the real archive
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Add the files
    $v_header_list = array();
    if (($v_result = $this->privAddFileList($p_filedescr_list, $v_header_list, $p_options)) != 1)
    {
      fclose($v_zip_temp_fd);
      $this->privCloseFd();
      @unlink($v_zip_temp_name);
      $this->privSwapBackMagicQuotes();

      // ----- Return
      return $v_result;
    }

    // ----- Store the offset of the central dir
    $v_offset = @ftell($this->zip_fd);

    // ----- Copy the block of file headers from the old archive
    $v_size = $v_central_dir['size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($v_zip_temp_fd, $v_read_size);
      @fwrite($this->zip_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Create the Central Dir files header
    for ($i=0, $v_count=0; $i<sizeof($v_header_list); $i++)
    {
      // ----- Create the file header
      if ($v_header_list[$i]['status'] == 'ok') {
        if (($v_result = $this->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
          fclose($v_zip_temp_fd);
          $this->privCloseFd();
          @unlink($v_zip_temp_name);
          $this->privSwapBackMagicQuotes();

          // ----- Return
          return $v_result;
        }
        $v_count++;
      }

      // ----- Transform the header to a 'usable' info
      $this->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
    }

    // ----- Zip file comment
    $v_comment = $v_central_dir['comment'];
    if (isset($p_options[PCLZIP_OPT_COMMENT])) {
      $v_comment = $p_options[PCLZIP_OPT_COMMENT];
    }
    if (isset($p_options[PCLZIP_OPT_ADD_COMMENT])) {
      $v_comment = $v_comment.$p_options[PCLZIP_OPT_ADD_COMMENT];
    }
    if (isset($p_options[PCLZIP_OPT_PREPEND_COMMENT])) {
      $v_comment = $p_options[PCLZIP_OPT_PREPEND_COMMENT].$v_comment;
    }

    // ----- Calculate the size of the central header
    $v_size = @ftell($this->zip_fd)-$v_offset;

    // ----- Create the central dir footer
    if (($v_result = $this->privWriteCentralHeader($v_count+$v_central_dir['entries'], $v_size, $v_offset, $v_comment)) != 1)
    {
      // ----- Reset the file list
      unset($v_header_list);
      $this->privSwapBackMagicQuotes();

      // ----- Return
      return $v_result;
    }

    // ----- Swap back the file descriptor
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Close
    $this->privCloseFd();

    // ----- Close the temporary file
    @fclose($v_zip_temp_fd);

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Delete the zip file
    // TBC : I should test the result ...
    @unlink($this->zipname);

    // ----- Rename the temporary file
    // TBC : I should test the result ...
    //@rename($v_zip_temp_name, $this->zipname);
    PclZipUtilRename($v_zip_temp_name, $this->zipname);

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privOpenFd()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privOpenFd($p_mode)
  {
    $v_result=1;

    // ----- Look if already open
    if ($this->zip_fd != 0)
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Zip file \''.$this->zipname.'\' already open');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Open the zip file
    if (($this->zip_fd = @fopen($this->zipname, $p_mode)) == 0)
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \''.$this->zipname.'\' in '.$p_mode.' mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCloseFd()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privCloseFd()
  {
    $v_result=1;

    if ($this->zip_fd != 0)
      @fclose($this->zip_fd);
    $this->zip_fd = 0;

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddList()
  // Description :
  //   $p_add_dir and $p_remove_dir will give the ability to memorize a path which is
  //   different from the real path of the file. This is usefull if you want to have PclTar
  //   running in any directory, and memorize relative path from an other directory.
  // Parameters :
  //   $p_list : An array containing the file or directory names to add in the tar
  //   $p_result_list : list of added files with their properties (specially the status field)
  //   $p_add_dir : Path to add in the filename path archived
  //   $p_remove_dir : Path to remove in the filename path archived
  // Return Values :
  // --------------------------------------------------------------------------------
//  function privAddList($p_list, &$p_result_list, $p_add_dir, $p_remove_dir, $p_remove_all_dir, &$p_options)
  function privAddList($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;

    // ----- Add the files
    $v_header_list = array();
    if (($v_result = $this->privAddFileList($p_filedescr_list, $v_header_list, $p_options)) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Store the offset of the central dir
    $v_offset = @ftell($this->zip_fd);

    // ----- Create the Central Dir files header
    for ($i=0,$v_count=0; $i<sizeof($v_header_list); $i++)
    {
      // ----- Create the file header
      if ($v_header_list[$i]['status'] == 'ok') {
        if (($v_result = $this->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
          // ----- Return
          return $v_result;
        }
        $v_count++;
      }

      // ----- Transform the header to a 'usable' info
      $this->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
    }

    // ----- Zip file comment
    $v_comment = '';
    if (isset($p_options[PCLZIP_OPT_COMMENT])) {
      $v_comment = $p_options[PCLZIP_OPT_COMMENT];
    }

    // ----- Calculate the size of the central header
    $v_size = @ftell($this->zip_fd)-$v_offset;

    // ----- Create the central dir footer
    if (($v_result = $this->privWriteCentralHeader($v_count, $v_size, $v_offset, $v_comment)) != 1)
    {
      // ----- Reset the file list
      unset($v_header_list);

      // ----- Return
      return $v_result;
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddFileList()
  // Description :
  // Parameters :
  //   $p_filedescr_list : An array containing the file description 
  //                      or directory names to add in the zip
  //   $p_result_list : list of added files with their properties (specially the status field)
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAddFileList($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_header = array();

    // ----- Recuperate the current number of elt in list
    $v_nb = sizeof($p_result_list);

    // ----- Loop on the files
    for ($j=0; ($j<sizeof($p_filedescr_list)) && ($v_result==1); $j++) {
      // ----- Format the filename
      $p_filedescr_list[$j]['filename']
      = PclZipUtilTranslateWinPath($p_filedescr_list[$j]['filename'], false);
      

      // ----- Skip empty file names
      // TBC : Can this be possible ? not checked in DescrParseAtt ?
      if ($p_filedescr_list[$j]['filename'] == "") {
        continue;
      }

      // ----- Check the filename
      if (   ($p_filedescr_list[$j]['type'] != 'virtual_file')
          && (!file_exists($p_filedescr_list[$j]['filename']))) {
        PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "File '".$p_filedescr_list[$j]['filename']."' does not exist");
        return PclZip::errorCode();
      }

      // ----- Look if it is a file or a dir with no all path remove option
      // or a dir with all its path removed
//      if (   (is_file($p_filedescr_list[$j]['filename']))
//          || (   is_dir($p_filedescr_list[$j]['filename'])
      if (   ($p_filedescr_list[$j]['type'] == 'file')
          || ($p_filedescr_list[$j]['type'] == 'virtual_file')
          || (   ($p_filedescr_list[$j]['type'] == 'folder')
              && (   !isset($p_options[PCLZIP_OPT_REMOVE_ALL_PATH])
                  || !$p_options[PCLZIP_OPT_REMOVE_ALL_PATH]))
          ) {

        // ----- Add the file
        $v_result = $this->privAddFile($p_filedescr_list[$j], $v_header,
                                       $p_options);
        if ($v_result != 1) {
          return $v_result;
        }

        // ----- Store the file infos
        $p_result_list[$v_nb++] = $v_header;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddFile()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAddFile($p_filedescr, &$p_header, &$p_options)
  {
    $v_result=1;
    
    // ----- Working variable
    $p_filename = $p_filedescr['filename'];

    // TBC : Already done in the fileAtt check ... ?
    if ($p_filename == "") {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid file list parameter (invalid or empty list)");

      // ----- Return
      return PclZip::errorCode();
    }
  
    // ----- Look for a stored different filename 
    /* TBC : Removed
    if (isset($p_filedescr['stored_filename'])) {
      $v_stored_filename = $p_filedescr['stored_filename'];
    }
    else {
      $v_stored_filename = $p_filedescr['stored_filename'];
    }
    */

    // ----- Set the file properties
    clearstatcache();
    $p_header['version'] = 20;
    $p_header['version_extracted'] = 10;
    $p_header['flag'] = 0;
    $p_header['compression'] = 0;
    $p_header['crc'] = 0;
    $p_header['compressed_size'] = 0;
    $p_header['filename_len'] = strlen($p_filename);
    $p_header['extra_len'] = 0;
    $p_header['disk'] = 0;
    $p_header['internal'] = 0;
    $p_header['offset'] = 0;
    $p_header['filename'] = $p_filename;
// TBC : Removed    $p_header['stored_filename'] = $v_stored_filename;
    $p_header['stored_filename'] = $p_filedescr['stored_filename'];
    $p_header['extra'] = '';
    $p_header['status'] = 'ok';
    $p_header['index'] = -1;

    // ----- Look for regular file
    if ($p_filedescr['type']=='file') {
      $p_header['external'] = 0x00000000;
      $p_header['size'] = filesize($p_filename);
    }
    
    // ----- Look for regular folder
    else if ($p_filedescr['type']=='folder') {
      $p_header['external'] = 0x00000010;
      $p_header['mtime'] = filemtime($p_filename);
      $p_header['size'] = filesize($p_filename);
    }
    
    // ----- Look for virtual file
    else if ($p_filedescr['type'] == 'virtual_file') {
      $p_header['external'] = 0x00000000;
      $p_header['size'] = strlen($p_filedescr['content']);
    }
    

    // ----- Look for filetime
    if (isset($p_filedescr['mtime'])) {
      $p_header['mtime'] = $p_filedescr['mtime'];
    }
    else if ($p_filedescr['type'] == 'virtual_file') {
      $p_header['mtime'] = time();
    }
    else {
      $p_header['mtime'] = filemtime($p_filename);
    }

    // ------ Look for file comment
    if (isset($p_filedescr['comment'])) {
      $p_header['comment_len'] = strlen($p_filedescr['comment']);
      $p_header['comment'] = $p_filedescr['comment'];
    }
    else {
      $p_header['comment_len'] = 0;
      $p_header['comment'] = '';
    }

    // ----- Look for pre-add callback
    if (isset($p_options[PCLZIP_CB_PRE_ADD])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_header, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_ADD].'(PCLZIP_CB_PRE_ADD, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_ADD](PCLZIP_CB_PRE_ADD, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_header['status'] = "skipped";
        $v_result = 1;
      }

      // ----- Update the informations
      // Only some fields can be modified
      if ($p_header['stored_filename'] != $v_local_header['stored_filename']) {
        $p_header['stored_filename'] = PclZipUtilPathReduction($v_local_header['stored_filename']);
      }
    }

    // ----- Look for empty stored filename
    if ($p_header['stored_filename'] == "") {
      $p_header['status'] = "filtered";
    }
    
    // ----- Check the path length
    if (strlen($p_header['stored_filename']) > 0xFF) {
      $p_header['status'] = 'filename_too_long';
    }

    // ----- Look if no error, or file not skipped
    if ($p_header['status'] == 'ok') {

      // ----- Look for a file
      if ($p_filedescr['type'] == 'file') {
        // ----- Look for using temporary file to zip
        if ( (!isset($p_options[PCLZIP_OPT_TEMP_FILE_OFF])) 
            && (isset($p_options[PCLZIP_OPT_TEMP_FILE_ON])
                || (isset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD])
                    && ($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] <= $p_header['size'])) ) ) {
          $v_result = $this->privAddFileUsingTempFile($p_filedescr, $p_header, $p_options);
          if ($v_result < PCLZIP_ERR_NO_ERROR) {
            return $v_result;
          }
        }
        
        // ----- Use "in memory" zip algo
        else {

        // ----- Open the source file
        if (($v_file = @fopen($p_filename, "rb")) == 0) {
          PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, "Unable to open file '$p_filename' in binary read mode");
          return PclZip::errorCode();
        }

        // ----- Read the file content
        $v_content = @fread($v_file, $p_header['size']);

        // ----- Close the file
        @fclose($v_file);

        // ----- Calculate the CRC
        $p_header['crc'] = @crc32($v_content);
        
        // ----- Look for no compression
        if ($p_options[PCLZIP_OPT_NO_COMPRESSION]) {
          // ----- Set header parameters
          $p_header['compressed_size'] = $p_header['size'];
          $p_header['compression'] = 0;
        }
        
        // ----- Look for normal compression
        else {
          // ----- Compress the content
          $v_content = @gzdeflate($v_content);

          // ----- Set header parameters
          $p_header['compressed_size'] = strlen($v_content);
          $p_header['compression'] = 8;
        }
        
        // ----- Call the header generation
        if (($v_result = $this->privWriteFileHeader($p_header)) != 1) {
          @fclose($v_file);
          return $v_result;
        }

        // ----- Write the compressed (or not) content
        @fwrite($this->zip_fd, $v_content, $p_header['compressed_size']);

        }

      }

      // ----- Look for a virtual file (a file from string)
      else if ($p_filedescr['type'] == 'virtual_file') {
          
        $v_content = $p_filedescr['content'];

        // ----- Calculate the CRC
        $p_header['crc'] = @crc32($v_content);
        
        // ----- Look for no compression
        if ($p_options[PCLZIP_OPT_NO_COMPRESSION]) {
          // ----- Set header parameters
          $p_header['compressed_size'] = $p_header['size'];
          $p_header['compression'] = 0;
        }
        
        // ----- Look for normal compression
        else {
          // ----- Compress the content
          $v_content = @gzdeflate($v_content);

          // ----- Set header parameters
          $p_header['compressed_size'] = strlen($v_content);
          $p_header['compression'] = 8;
        }
        
        // ----- Call the header generation
        if (($v_result = $this->privWriteFileHeader($p_header)) != 1) {
          @fclose($v_file);
          return $v_result;
        }

        // ----- Write the compressed (or not) content
        @fwrite($this->zip_fd, $v_content, $p_header['compressed_size']);
      }

      // ----- Look for a directory
      else if ($p_filedescr['type'] == 'folder') {
        // ----- Look for directory last '/'
        if (@substr($p_header['stored_filename'], -1) != '/') {
          $p_header['stored_filename'] .= '/';
        }

        // ----- Set the file properties
        $p_header['size'] = 0;
        //$p_header['external'] = 0x41FF0010;   // Value for a folder : to be checked
        $p_header['external'] = 0x00000010;   // Value for a folder : to be checked

        // ----- Call the header generation
        if (($v_result = $this->privWriteFileHeader($p_header)) != 1)
        {
          return $v_result;
        }
      }
    }

    // ----- Look for post-add callback
    if (isset($p_options[PCLZIP_CB_POST_ADD])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_header, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_ADD].'(PCLZIP_CB_POST_ADD, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_ADD](PCLZIP_CB_POST_ADD, $v_local_header);
      if ($v_result == 0) {
        // ----- Ignored
        $v_result = 1;
      }

      // ----- Update the informations
      // Nothing can be modified
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddFileUsingTempFile()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAddFileUsingTempFile($p_filedescr, &$p_header, &$p_options)
  {
    $v_result=PCLZIP_ERR_NO_ERROR;
    
    // ----- Working variable
    $p_filename = $p_filedescr['filename'];


    // ----- Open the source file
    if (($v_file = @fopen($p_filename, "rb")) == 0) {
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, "Unable to open file '$p_filename' in binary read mode");
      return PclZip::errorCode();
    }

    // ----- Creates a compressed temporary file
    $v_gzip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.gz';
    if (($v_file_compressed = @gzopen($v_gzip_temp_name, "wb")) == 0) {
      fclose($v_file);
      PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary write mode');
      return PclZip::errorCode();
    }

    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    $v_size = filesize($p_filename);
    while ($v_size != 0) {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($v_file, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @gzputs($v_file_compressed, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Close the file
    @fclose($v_file);
    @gzclose($v_file_compressed);

    // ----- Check the minimum file size
    if (filesize($v_gzip_temp_name) < 18) {
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'gzip temporary file \''.$v_gzip_temp_name.'\' has invalid filesize - should be minimum 18 bytes');
      return PclZip::errorCode();
    }

    // ----- Extract the compressed attributes
    if (($v_file_compressed = @fopen($v_gzip_temp_name, "rb")) == 0) {
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary read mode');
      return PclZip::errorCode();
    }

    // ----- Read the gzip file header
    $v_binary_data = @fread($v_file_compressed, 10);
    $v_data_header = unpack('a1id1/a1id2/a1cm/a1flag/Vmtime/a1xfl/a1os', $v_binary_data);

    // ----- Check some parameters
    $v_data_header['os'] = bin2hex($v_data_header['os']);

    // ----- Read the gzip file footer
    @fseek($v_file_compressed, filesize($v_gzip_temp_name)-8);
    $v_binary_data = @fread($v_file_compressed, 8);
    $v_data_footer = unpack('Vcrc/Vcompressed_size', $v_binary_data);

    // ----- Set the attributes
    $p_header['compression'] = ord($v_data_header['cm']);
    //$p_header['mtime'] = $v_data_header['mtime'];
    $p_header['crc'] = $v_data_footer['crc'];
    $p_header['compressed_size'] = filesize($v_gzip_temp_name)-18;

    // ----- Close the file
    @fclose($v_file_compressed);

    // ----- Call the header generation
    if (($v_result = $this->privWriteFileHeader($p_header)) != 1) {
      return $v_result;
    }

    // ----- Add the compressed data
    if (($v_file_compressed = @fopen($v_gzip_temp_name, "rb")) == 0)
    {
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary read mode');
      return PclZip::errorCode();
    }

    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    fseek($v_file_compressed, 10);
    $v_size = $p_header['compressed_size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($v_file_compressed, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @fwrite($this->zip_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Close the file
    @fclose($v_file_compressed);

    // ----- Unlink the temporary file
    @unlink($v_gzip_temp_name);
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCalculateStoredFilename()
  // Description :
  //   Based on file descriptor properties and global options, this method
  //   calculate the filename that will be stored in the archive.
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privCalculateStoredFilename(&$p_filedescr, &$p_options)
  {
    $v_result=1;
    
    // ----- Working variables
    $p_filename = $p_filedescr['filename'];
    if (isset($p_options[PCLZIP_OPT_ADD_PATH])) {
      $p_add_dir = $p_options[PCLZIP_OPT_ADD_PATH];
    }
    else {
      $p_add_dir = '';
    }
    if (isset($p_options[PCLZIP_OPT_REMOVE_PATH])) {
      $p_remove_dir = $p_options[PCLZIP_OPT_REMOVE_PATH];
    }
    else {
      $p_remove_dir = '';
    }
    if (isset($p_options[PCLZIP_OPT_REMOVE_ALL_PATH])) {
      $p_remove_all_dir = $p_options[PCLZIP_OPT_REMOVE_ALL_PATH];
    }
    else {
      $p_remove_all_dir = 0;
    }


    // ----- Look for full name change
    if (isset($p_filedescr['new_full_name'])) {
      // ----- Remove drive letter if any
      $v_stored_filename = PclZipUtilTranslateWinPath($p_filedescr['new_full_name']);
    }
    
    // ----- Look for path and/or short name change
    else {

      // ----- Look for short name change
      // Its when we cahnge just the filename but not the path
      if (isset($p_filedescr['new_short_name'])) {
        $v_path_info = pathinfo($p_filename);
        $v_dir = '';
        if ($v_path_info['dirname'] != '') {
          $v_dir = $v_path_info['dirname'].'/';
        }
        $v_stored_filename = $v_dir.$p_filedescr['new_short_name'];
      }
      else {
        // ----- Calculate the stored filename
        $v_stored_filename = $p_filename;
      }

      // ----- Look for all path to remove
      if ($p_remove_all_dir) {
        $v_stored_filename = basename($p_filename);
      }
      // ----- Look for partial path remove
      else if ($p_remove_dir != "") {
        if (substr($p_remove_dir, -1) != '/')
          $p_remove_dir .= "/";

        if (   (substr($p_filename, 0, 2) == "./")
            || (substr($p_remove_dir, 0, 2) == "./")) {
            
          if (   (substr($p_filename, 0, 2) == "./")
              && (substr($p_remove_dir, 0, 2) != "./")) {
            $p_remove_dir = "./".$p_remove_dir;
          }
          if (   (substr($p_filename, 0, 2) != "./")
              && (substr($p_remove_dir, 0, 2) == "./")) {
            $p_remove_dir = substr($p_remove_dir, 2);
          }
        }

        $v_compare = PclZipUtilPathInclusion($p_remove_dir,
                                             $v_stored_filename);
        if ($v_compare > 0) {
          if ($v_compare == 2) {
            $v_stored_filename = "";
          }
          else {
            $v_stored_filename = substr($v_stored_filename,
                                        strlen($p_remove_dir));
          }
        }
      }
      
      // ----- Remove drive letter if any
      $v_stored_filename = PclZipUtilTranslateWinPath($v_stored_filename);
      
      // ----- Look for path to add
      if ($p_add_dir != "") {
        if (substr($p_add_dir, -1) == "/")
          $v_stored_filename = $p_add_dir.$v_stored_filename;
        else
          $v_stored_filename = $p_add_dir."/".$v_stored_filename;
      }
    }

    // ----- Filename (reduce the path of stored name)
    $v_stored_filename = PclZipUtilPathReduction($v_stored_filename);
    $p_filedescr['stored_filename'] = $v_stored_filename;
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privWriteFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privWriteFileHeader(&$p_header)
  {
    $v_result=1;

    // ----- Store the offset position of the file
    $p_header['offset'] = ftell($this->zip_fd);

    // ----- Transform UNIX mtime to DOS format mdate/mtime
    $v_date = getdate($p_header['mtime']);
    $v_mtime = ($v_date['hours']<<11) + ($v_date['minutes']<<5) + $v_date['seconds']/2;
    $v_mdate = (($v_date['year']-1980)<<9) + ($v_date['mon']<<5) + $v_date['mday'];

    // ----- Packed data
    $v_binary_data = pack("VvvvvvVVVvv", 0x04034b50,
	                      $p_header['version_extracted'], $p_header['flag'],
                          $p_header['compression'], $v_mtime, $v_mdate,
                          $p_header['crc'], $p_header['compressed_size'],
						  $p_header['size'],
                          strlen($p_header['stored_filename']),
						  $p_header['extra_len']);

    // ----- Write the first 148 bytes of the header in the archive
    fputs($this->zip_fd, $v_binary_data, 30);

    // ----- Write the variable fields
    if (strlen($p_header['stored_filename']) != 0)
    {
      fputs($this->zip_fd, $p_header['stored_filename'], strlen($p_header['stored_filename']));
    }
    if ($p_header['extra_len'] != 0)
    {
      fputs($this->zip_fd, $p_header['extra'], $p_header['extra_len']);
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privWriteCentralFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privWriteCentralFileHeader(&$p_header)
  {
    $v_result=1;

    // TBC
    //for(reset($p_header); $key = key($p_header); next($p_header)) {
    //}

    // ----- Transform UNIX mtime to DOS format mdate/mtime
    $v_date = getdate($p_header['mtime']);
    $v_mtime = ($v_date['hours']<<11) + ($v_date['minutes']<<5) + $v_date['seconds']/2;
    $v_mdate = (($v_date['year']-1980)<<9) + ($v_date['mon']<<5) + $v_date['mday'];


    // ----- Packed data
    $v_binary_data = pack("VvvvvvvVVVvvvvvVV", 0x02014b50,
	                      $p_header['version'], $p_header['version_extracted'],
                          $p_header['flag'], $p_header['compression'],
						  $v_mtime, $v_mdate, $p_header['crc'],
                          $p_header['compressed_size'], $p_header['size'],
                          strlen($p_header['stored_filename']),
						  $p_header['extra_len'], $p_header['comment_len'],
                          $p_header['disk'], $p_header['internal'],
						  $p_header['external'], $p_header['offset']);

    // ----- Write the 42 bytes of the header in the zip file
    fputs($this->zip_fd, $v_binary_data, 46);

    // ----- Write the variable fields
    if (strlen($p_header['stored_filename']) != 0)
    {
      fputs($this->zip_fd, $p_header['stored_filename'], strlen($p_header['stored_filename']));
    }
    if ($p_header['extra_len'] != 0)
    {
      fputs($this->zip_fd, $p_header['extra'], $p_header['extra_len']);
    }
    if ($p_header['comment_len'] != 0)
    {
      fputs($this->zip_fd, $p_header['comment'], $p_header['comment_len']);
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privWriteCentralHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privWriteCentralHeader($p_nb_entries, $p_size, $p_offset, $p_comment)
  {
    $v_result=1;

    // ----- Packed data
    $v_binary_data = pack("VvvvvVVv", 0x06054b50, 0, 0, $p_nb_entries,
	                      $p_nb_entries, $p_size,
						  $p_offset, strlen($p_comment));

    // ----- Write the 22 bytes of the header in the zip file
    fputs($this->zip_fd, $v_binary_data, 22);

    // ----- Write the variable fields
    if (strlen($p_comment) != 0)
    {
      fputs($this->zip_fd, $p_comment, strlen($p_comment));
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privList()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privList(&$p_list)
  {
    $v_result=1;

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Open the zip file
    if (($this->zip_fd = @fopen($this->zipname, 'rb')) == 0)
    {
      // ----- Magic quotes trick
      $this->privSwapBackMagicQuotes();
      
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \''.$this->zipname.'\' in binary read mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privSwapBackMagicQuotes();
      return $v_result;
    }

    // ----- Go to beginning of Central Dir
    @rewind($this->zip_fd);
    if (@fseek($this->zip_fd, $v_central_dir['offset']))
    {
      $this->privSwapBackMagicQuotes();

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read each entry
    for ($i=0; $i<$v_central_dir['entries']; $i++)
    {
      // ----- Read the file header
      if (($v_result = $this->privReadCentralFileHeader($v_header)) != 1)
      {
        $this->privSwapBackMagicQuotes();
        return $v_result;
      }
      $v_header['index'] = $i;

      // ----- Get the only interesting attributes
      $this->privConvertHeader2FileInfo($v_header, $p_list[$i]);
      unset($v_header);
    }

    // ----- Close the zip file
    $this->privCloseFd();

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privConvertHeader2FileInfo()
  // Description :
  //   This function takes the file informations from the central directory
  //   entries and extract the interesting parameters that will be given back.
  //   The resulting file infos are set in the array $p_info
  //     $p_info['filename'] : Filename with full path. Given by user (add),
  //                           extracted in the filesystem (extract).
  //     $p_info['stored_filename'] : Stored filename in the archive.
  //     $p_info['size'] = Size of the file.
  //     $p_info['compressed_size'] = Compressed size of the file.
  //     $p_info['mtime'] = Last modification date of the file.
  //     $p_info['comment'] = Comment associated with the file.
  //     $p_info['folder'] = true/false : indicates if the entry is a folder or not.
  //     $p_info['status'] = status of the action on the file.
  //     $p_info['crc'] = CRC of the file content.
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privConvertHeader2FileInfo($p_header, &$p_info)
  {
    $v_result=1;

    // ----- Get the interesting attributes
    $v_temp_path = PclZipUtilPathReduction($p_header['filename']);
    $p_info['filename'] = $v_temp_path;
    $v_temp_path = PclZipUtilPathReduction($p_header['stored_filename']);
    $p_info['stored_filename'] = $v_temp_path;
    $p_info['size'] = $p_header['size'];
    $p_info['compressed_size'] = $p_header['compressed_size'];
    $p_info['mtime'] = $p_header['mtime'];
    $p_info['comment'] = $p_header['comment'];
    $p_info['folder'] = (($p_header['external']&0x00000010)==0x00000010);
    $p_info['index'] = $p_header['index'];
    $p_info['status'] = $p_header['status'];
    $p_info['crc'] = $p_header['crc'];

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractByRule()
  // Description :
  //   Extract a file or directory depending of rules (by index, by name, ...)
  // Parameters :
  //   $p_file_list : An array where will be placed the properties of each
  //                  extracted file
  //   $p_path : Path to add while writing the extracted files
  //   $p_remove_path : Path to remove (from the file memorized path) while writing the
  //                    extracted files. If the path does not match the file path,
  //                    the file is extracted with its memorized path.
  //                    $p_remove_path does not apply to 'list' mode.
  //                    $p_path and $p_remove_path are commulative.
  // Return Values :
  //   1 on success,0 or less on error (see error code list)
  // --------------------------------------------------------------------------------
  function privExtractByRule(&$p_file_list, $p_path, $p_remove_path, $p_remove_all_path, &$p_options)
  {
    $v_result=1;

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Check the path
    if (   ($p_path == "")
	    || (   (substr($p_path, 0, 1) != "/")
		    && (substr($p_path, 0, 3) != "../")
			&& (substr($p_path,1,2)!=":/")))
      $p_path = "./".$p_path;

    // ----- Reduce the path last (and duplicated) '/'
    if (($p_path != "./") && ($p_path != "/"))
    {
      // ----- Look for the path end '/'
      while (substr($p_path, -1) == "/")
      {
        $p_path = substr($p_path, 0, strlen($p_path)-1);
      }
    }

    // ----- Look for path to remove format (should end by /)
    if (($p_remove_path != "") && (substr($p_remove_path, -1) != '/'))
    {
      $p_remove_path .= '/';
    }
    $p_remove_path_size = strlen($p_remove_path);

    // ----- Open the zip file
    if (($v_result = $this->privOpenFd('rb')) != 1)
    {
      $this->privSwapBackMagicQuotes();
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      // ----- Close the zip file
      $this->privCloseFd();
      $this->privSwapBackMagicQuotes();

      return $v_result;
    }

    // ----- Start at beginning of Central Dir
    $v_pos_entry = $v_central_dir['offset'];

    // ----- Read each entry
    $j_start = 0;
    for ($i=0, $v_nb_extracted=0; $i<$v_central_dir['entries']; $i++)
    {

      // ----- Read next Central dir entry
      @rewind($this->zip_fd);
      if (@fseek($this->zip_fd, $v_pos_entry))
      {
        // ----- Close the zip file
        $this->privCloseFd();
        $this->privSwapBackMagicQuotes();

        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Read the file header
      $v_header = array();
      if (($v_result = $this->privReadCentralFileHeader($v_header)) != 1)
      {
        // ----- Close the zip file
        $this->privCloseFd();
        $this->privSwapBackMagicQuotes();

        return $v_result;
      }

      // ----- Store the index
      $v_header['index'] = $i;

      // ----- Store the file position
      $v_pos_entry = ftell($this->zip_fd);

      // ----- Look for the specific extract rules
      $v_extract = false;

      // ----- Look for extract by name rule
      if (   (isset($p_options[PCLZIP_OPT_BY_NAME]))
          && ($p_options[PCLZIP_OPT_BY_NAME] != 0)) {

          // ----- Look if the filename is in the list
          for ($j=0; ($j<sizeof($p_options[PCLZIP_OPT_BY_NAME])) && (!$v_extract); $j++) {

              // ----- Look for a directory
              if (substr($p_options[PCLZIP_OPT_BY_NAME][$j], -1) == "/") {

                  // ----- Look if the directory is in the filename path
                  if (   (strlen($v_header['stored_filename']) > strlen($p_options[PCLZIP_OPT_BY_NAME][$j]))
                      && (substr($v_header['stored_filename'], 0, strlen($p_options[PCLZIP_OPT_BY_NAME][$j])) == $p_options[PCLZIP_OPT_BY_NAME][$j])) {
                      $v_extract = true;
                  }
              }
              // ----- Look for a filename
              elseif ($v_header['stored_filename'] == $p_options[PCLZIP_OPT_BY_NAME][$j]) {
                  $v_extract = true;
              }
          }
      }

      // ----- Look for extract by ereg rule
      // ereg() is deprecated with PHP 5.3
      /* 
      else if (   (isset($p_options[PCLZIP_OPT_BY_EREG]))
               && ($p_options[PCLZIP_OPT_BY_EREG] != "")) {

          if (ereg($p_options[PCLZIP_OPT_BY_EREG], $v_header['stored_filename'])) {
              $v_extract = true;
          }
      }
      */

      // ----- Look for extract by preg rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_PREG]))
               && ($p_options[PCLZIP_OPT_BY_PREG] != "")) {

          if (preg_match($p_options[PCLZIP_OPT_BY_PREG], $v_header['stored_filename'])) {
              $v_extract = true;
          }
      }

      // ----- Look for extract by index rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_INDEX]))
               && ($p_options[PCLZIP_OPT_BY_INDEX] != 0)) {
          
          // ----- Look if the index is in the list
          for ($j=$j_start; ($j<sizeof($p_options[PCLZIP_OPT_BY_INDEX])) && (!$v_extract); $j++) {

              if (($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['start']) && ($i<=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end'])) {
                  $v_extract = true;
              }
              if ($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end']) {
                  $j_start = $j+1;
              }

              if ($p_options[PCLZIP_OPT_BY_INDEX][$j]['start']>$i) {
                  break;
              }
          }
      }

      // ----- Look for no rule, which means extract all the archive
      else {
          $v_extract = true;
      }

	  // ----- Check compression method
	  if (   ($v_extract)
	      && (   ($v_header['compression'] != 8)
		      && ($v_header['compression'] != 0))) {
          $v_header['status'] = 'unsupported_compression';

          // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
          if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		      && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

              $this->privSwapBackMagicQuotes();
              
              PclZip::privErrorLog(PCLZIP_ERR_UNSUPPORTED_COMPRESSION,
			                       "Filename '".$v_header['stored_filename']."' is "
				  	    	  	   ."compressed by an unsupported compression "
				  	    	  	   ."method (".$v_header['compression'].") ");

              return PclZip::errorCode();
		  }
	  }
	  
	  // ----- Check encrypted files
	  if (($v_extract) && (($v_header['flag'] & 1) == 1)) {
          $v_header['status'] = 'unsupported_encryption';

          // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
          if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		      && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

              $this->privSwapBackMagicQuotes();

              PclZip::privErrorLog(PCLZIP_ERR_UNSUPPORTED_ENCRYPTION,
			                       "Unsupported encryption for "
				  	    	  	   ." filename '".$v_header['stored_filename']
								   ."'");

              return PclZip::errorCode();
		  }
    }

      // ----- Look for real extraction
      if (($v_extract) && ($v_header['status'] != 'ok')) {
          $v_result = $this->privConvertHeader2FileInfo($v_header,
		                                        $p_file_list[$v_nb_extracted++]);
          if ($v_result != 1) {
              $this->privCloseFd();
              $this->privSwapBackMagicQuotes();
              return $v_result;
          }

          $v_extract = false;
      }
      
      // ----- Look for real extraction
      if ($v_extract)
      {

        // ----- Go to the file position
        @rewind($this->zip_fd);
        if (@fseek($this->zip_fd, $v_header['offset']))
        {
          // ----- Close the zip file
          $this->privCloseFd();

          $this->privSwapBackMagicQuotes();

          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

          // ----- Return
          return PclZip::errorCode();
        }

        // ----- Look for extraction as string
        if ($p_options[PCLZIP_OPT_EXTRACT_AS_STRING]) {

          $v_string = '';

          // ----- Extracting the file
          $v_result1 = $this->privExtractFileAsString($v_header, $v_string, $p_options);
          if ($v_result1 < 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result1;
          }

          // ----- Get the only interesting attributes
          if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted])) != 1)
          {
            // ----- Close the zip file
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();

            return $v_result;
          }

          // ----- Set the file content
          $p_file_list[$v_nb_extracted]['content'] = $v_string;

          // ----- Next extracted file
          $v_nb_extracted++;
          
          // ----- Look for user callback abort
          if ($v_result1 == 2) {
          	break;
          }
        }
        // ----- Look for extraction in standard output
        elseif (   (isset($p_options[PCLZIP_OPT_EXTRACT_IN_OUTPUT]))
		        && ($p_options[PCLZIP_OPT_EXTRACT_IN_OUTPUT])) {
          // ----- Extracting the file in standard output
          $v_result1 = $this->privExtractFileInOutput($v_header, $p_options);
          if ($v_result1 < 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result1;
          }

          // ----- Get the only interesting attributes
          if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result;
          }

          // ----- Look for user callback abort
          if ($v_result1 == 2) {
          	break;
          }
        }
        // ----- Look for normal extraction
        else {
          // ----- Extracting the file
          $v_result1 = $this->privExtractFile($v_header,
		                                      $p_path, $p_remove_path,
											  $p_remove_all_path,
											  $p_options);
          if ($v_result1 < 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result1;
          }

          // ----- Get the only interesting attributes
          if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1)
          {
            // ----- Close the zip file
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();

            return $v_result;
          }

          // ----- Look for user callback abort
          if ($v_result1 == 2) {
          	break;
          }
        }
      }
    }

    // ----- Close the zip file
    $this->privCloseFd();
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFile()
  // Description :
  // Parameters :
  // Return Values :
  //
  // 1 : ... ?
  // PCLZIP_ERR_USER_ABORTED(2) : User ask for extraction stop in callback
  // --------------------------------------------------------------------------------
  function privExtractFile(&$p_entry, $p_path, $p_remove_path, $p_remove_all_path, &$p_options)
  {
    $v_result=1;

    // ----- Read the file header
    if (($v_result = $this->privReadFileHeader($v_header)) != 1)
    {
      // ----- Return
      return $v_result;
    }


    // ----- Check that the file header is coherent with $p_entry info
    if ($this->privCheckFileHeaders($v_header, $p_entry) != 1) {
        // TBC
    }

    // ----- Look for all path to remove
    if ($p_remove_all_path == true) {
        // ----- Look for folder entry that not need to be extracted
        if (($p_entry['external']&0x00000010)==0x00000010) {

            $p_entry['status'] = "filtered";

            return $v_result;
        }

        // ----- Get the basename of the path
        $p_entry['filename'] = basename($p_entry['filename']);
    }

    // ----- Look for path to remove
    else if ($p_remove_path != "")
    {
      if (PclZipUtilPathInclusion($p_remove_path, $p_entry['filename']) == 2)
      {

        // ----- Change the file status
        $p_entry['status'] = "filtered";

        // ----- Return
        return $v_result;
      }

      $p_remove_path_size = strlen($p_remove_path);
      if (substr($p_entry['filename'], 0, $p_remove_path_size) == $p_remove_path)
      {

        // ----- Remove the path
        $p_entry['filename'] = substr($p_entry['filename'], $p_remove_path_size);

      }
    }

    // ----- Add the path
    if ($p_path != '') {
      $p_entry['filename'] = $p_path."/".$p_entry['filename'];
    }
    
    // ----- Check a base_dir_restriction
    if (isset($p_options[PCLZIP_OPT_EXTRACT_DIR_RESTRICTION])) {
      $v_inclusion
      = PclZipUtilPathInclusion($p_options[PCLZIP_OPT_EXTRACT_DIR_RESTRICTION],
                                $p_entry['filename']); 
      if ($v_inclusion == 0) {

        PclZip::privErrorLog(PCLZIP_ERR_DIRECTORY_RESTRICTION,
			                     "Filename '".$p_entry['filename']."' is "
								 ."outside PCLZIP_OPT_EXTRACT_DIR_RESTRICTION");

        return PclZip::errorCode();
      }
    }

    // ----- Look for pre-extract callback
    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_EXTRACT](PCLZIP_CB_PRE_EXTRACT, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_entry['status'] = "skipped";
        $v_result = 1;
      }
      
      // ----- Look for abort result
      if ($v_result == 2) {
        // ----- This status is internal and will be changed in 'skipped'
        $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }

      // ----- Update the informations
      // Only some fields can be modified
      $p_entry['filename'] = $v_local_header['filename'];
    }


    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

    // ----- Look for specific actions while the file exist
    if (file_exists($p_entry['filename']))
    {

      // ----- Look if file is a directory
      if (is_dir($p_entry['filename']))
      {

        // ----- Change the file status
        $p_entry['status'] = "already_a_directory";
        
        // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
        // For historical reason first PclZip implementation does not stop
        // when this kind of error occurs.
        if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		    && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

            PclZip::privErrorLog(PCLZIP_ERR_ALREADY_A_DIRECTORY,
			                     "Filename '".$p_entry['filename']."' is "
								 ."already used by an existing directory");

            return PclZip::errorCode();
		    }
      }
      // ----- Look if file is write protected
      else if (!is_writeable($p_entry['filename']))
      {

        // ----- Change the file status
        $p_entry['status'] = "write_protected";

        // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
        // For historical reason first PclZip implementation does not stop
        // when this kind of error occurs.
        if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		    && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

            PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL,
			                     "Filename '".$p_entry['filename']."' exists "
								 ."and is write protected");

            return PclZip::errorCode();
		    }
      }

      // ----- Look if the extracted file is older
      else if (filemtime($p_entry['filename']) > $p_entry['mtime'])
      {
        // ----- Change the file status
        if (   (isset($p_options[PCLZIP_OPT_REPLACE_NEWER]))
		    && ($p_options[PCLZIP_OPT_REPLACE_NEWER]===true)) {
	  	  }
		    else {
            $p_entry['status'] = "newer_exist";

            // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
            // For historical reason first PclZip implementation does not stop
            // when this kind of error occurs.
            if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		        && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

                PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL,
			             "Newer version of '".$p_entry['filename']."' exists "
					    ."and option PCLZIP_OPT_REPLACE_NEWER is not selected");

                return PclZip::errorCode();
		      }
		    }
      }
      else {
      }
    }

    // ----- Check the directory availability and create it if necessary
    else {
      if ((($p_entry['external']&0x00000010)==0x00000010) || (substr($p_entry['filename'], -1) == '/'))
        $v_dir_to_check = $p_entry['filename'];
      else if (!strstr($p_entry['filename'], "/"))
        $v_dir_to_check = "";
      else
        $v_dir_to_check = dirname($p_entry['filename']);

        if (($v_result = $this->privDirCheck($v_dir_to_check, (($p_entry['external']&0x00000010)==0x00000010))) != 1) {
  
          // ----- Change the file status
          $p_entry['status'] = "path_creation_fail";
  
          // ----- Return
          //return $v_result;
          $v_result = 1;
        }
      }
    }

    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

      // ----- Do the extraction (if not a folder)
      if (!(($p_entry['external']&0x00000010)==0x00000010))
      {
        // ----- Look for not compressed file
        if ($p_entry['compression'] == 0) {

    		  // ----- Opening destination file
          if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0)
          {

            // ----- Change the file status
            $p_entry['status'] = "write_error";

            // ----- Return
            return $v_result;
          }


          // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
          $v_size = $p_entry['compressed_size'];
          while ($v_size != 0)
          {
            $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
            $v_buffer = @fread($this->zip_fd, $v_read_size);
            /* Try to speed up the code
            $v_binary_data = pack('a'.$v_read_size, $v_buffer);
            @fwrite($v_dest_file, $v_binary_data, $v_read_size);
            */
            @fwrite($v_dest_file, $v_buffer, $v_read_size);            
            $v_size -= $v_read_size;
          }

          // ----- Closing the destination file
          fclose($v_dest_file);

          // ----- Change the file mtime
          touch($p_entry['filename'], $p_entry['mtime']);
          

        }
        else {
          // ----- TBC
          // Need to be finished
          if (($p_entry['flag'] & 1) == 1) {
            PclZip::privErrorLog(PCLZIP_ERR_UNSUPPORTED_ENCRYPTION, 'File \''.$p_entry['filename'].'\' is encrypted. Encrypted files are not supported.');
            return PclZip::errorCode();
          }


          // ----- Look for using temporary file to unzip
          if ( (!isset($p_options[PCLZIP_OPT_TEMP_FILE_OFF])) 
              && (isset($p_options[PCLZIP_OPT_TEMP_FILE_ON])
                  || (isset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD])
                      && ($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] <= $p_entry['size'])) ) ) {
            $v_result = $this->privExtractFileUsingTempFile($p_entry, $p_options);
            if ($v_result < PCLZIP_ERR_NO_ERROR) {
              return $v_result;
            }
          }
          
          // ----- Look for extract in memory
          else {

          
            // ----- Read the compressed file in a buffer (one shot)
            $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);
            
            // ----- Decompress the file
            $v_file_content = @gzinflate($v_buffer);
            unset($v_buffer);
            if ($v_file_content === FALSE) {
  
              // ----- Change the file status
              // TBC
              $p_entry['status'] = "error";
              
              return $v_result;
            }
            
            // ----- Opening destination file
            if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0) {
  
              // ----- Change the file status
              $p_entry['status'] = "write_error";
  
              return $v_result;
            }
  
            // ----- Write the uncompressed data
            @fwrite($v_dest_file, $v_file_content, $p_entry['size']);
            unset($v_file_content);
  
            // ----- Closing the destination file
            @fclose($v_dest_file);
            
          }

          // ----- Change the file mtime
          @touch($p_entry['filename'], $p_entry['mtime']);
        }

        // ----- Look for chmod option
        if (isset($p_options[PCLZIP_OPT_SET_CHMOD])) {

          // ----- Change the mode of the file
          @chmod($p_entry['filename'], $p_options[PCLZIP_OPT_SET_CHMOD]);
        }

      }
    }

  	// ----- Change abort status
  	if ($p_entry['status'] == "aborted") {
        $p_entry['status'] = "skipped";
  	}
	
    // ----- Look for post-extract callback
    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_EXTRACT](PCLZIP_CB_POST_EXTRACT, $v_local_header);

      // ----- Look for abort result
      if ($v_result == 2) {
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFileUsingTempFile()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privExtractFileUsingTempFile(&$p_entry, &$p_options)
  {
    $v_result=1;
        
    // ----- Creates a temporary file
    $v_gzip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.gz';
    if (($v_dest_file = @fopen($v_gzip_temp_name, "wb")) == 0) {
      fclose($v_file);
      PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary write mode');
      return PclZip::errorCode();
    }


    // ----- Write gz file format header
    $v_binary_data = pack('va1a1Va1a1', 0x8b1f, Chr($p_entry['compression']), Chr(0x00), time(), Chr(0x00), Chr(3));
    @fwrite($v_dest_file, $v_binary_data, 10);

    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    $v_size = $p_entry['compressed_size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($this->zip_fd, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @fwrite($v_dest_file, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Write gz file format footer
    $v_binary_data = pack('VV', $p_entry['crc'], $p_entry['size']);
    @fwrite($v_dest_file, $v_binary_data, 8);

    // ----- Close the temporary file
    @fclose($v_dest_file);

    // ----- Opening destination file
    if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0) {
      $p_entry['status'] = "write_error";
      return $v_result;
    }

    // ----- Open the temporary gz file
    if (($v_src_file = @gzopen($v_gzip_temp_name, 'rb')) == 0) {
      @fclose($v_dest_file);
      $p_entry['status'] = "read_error";
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary read mode');
      return PclZip::errorCode();
    }


    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    $v_size = $p_entry['size'];
    while ($v_size != 0) {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @gzread($v_src_file, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @fwrite($v_dest_file, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }
    @fclose($v_dest_file);
    @gzclose($v_src_file);

    // ----- Delete the temporary file
    @unlink($v_gzip_temp_name);
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFileInOutput()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privExtractFileInOutput(&$p_entry, &$p_options)
  {
    $v_result=1;

    // ----- Read the file header
    if (($v_result = $this->privReadFileHeader($v_header)) != 1) {
      return $v_result;
    }


    // ----- Check that the file header is coherent with $p_entry info
    if ($this->privCheckFileHeaders($v_header, $p_entry) != 1) {
        // TBC
    }

    // ----- Look for pre-extract callback
    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_EXTRACT](PCLZIP_CB_PRE_EXTRACT, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_entry['status'] = "skipped";
        $v_result = 1;
      }

      // ----- Look for abort result
      if ($v_result == 2) {
        // ----- This status is internal and will be changed in 'skipped'
        $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }

      // ----- Update the informations
      // Only some fields can be modified
      $p_entry['filename'] = $v_local_header['filename'];
    }

    // ----- Trace

    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

      // ----- Do the extraction (if not a folder)
      if (!(($p_entry['external']&0x00000010)==0x00000010)) {
        // ----- Look for not compressed file
        if ($p_entry['compressed_size'] == $p_entry['size']) {

          // ----- Read the file in a buffer (one shot)
          $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);

          // ----- Send the file to the output
          echo $v_buffer;
          unset($v_buffer);
        }
        else {

          // ----- Read the compressed file in a buffer (one shot)
          $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);
          
          // ----- Decompress the file
          $v_file_content = gzinflate($v_buffer);
          unset($v_buffer);

          // ----- Send the file to the output
          echo $v_file_content;
          unset($v_file_content);
        }
      }
    }

	// ----- Change abort status
	if ($p_entry['status'] == "aborted") {
      $p_entry['status'] = "skipped";
	}

    // ----- Look for post-extract callback
    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_EXTRACT](PCLZIP_CB_POST_EXTRACT, $v_local_header);

      // ----- Look for abort result
      if ($v_result == 2) {
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }

    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFileAsString()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privExtractFileAsString(&$p_entry, &$p_string, &$p_options)
  {
    $v_result=1;

    // ----- Read the file header
    $v_header = array();
    if (($v_result = $this->privReadFileHeader($v_header)) != 1)
    {
      // ----- Return
      return $v_result;
    }


    // ----- Check that the file header is coherent with $p_entry info
    if ($this->privCheckFileHeaders($v_header, $p_entry) != 1) {
        // TBC
    }

    // ----- Look for pre-extract callback
    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_EXTRACT](PCLZIP_CB_PRE_EXTRACT, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_entry['status'] = "skipped";
        $v_result = 1;
      }
      
      // ----- Look for abort result
      if ($v_result == 2) {
        // ----- This status is internal and will be changed in 'skipped'
        $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }

      // ----- Update the informations
      // Only some fields can be modified
      $p_entry['filename'] = $v_local_header['filename'];
    }


    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

      // ----- Do the extraction (if not a folder)
      if (!(($p_entry['external']&0x00000010)==0x00000010)) {
        // ----- Look for not compressed file
  //      if ($p_entry['compressed_size'] == $p_entry['size'])
        if ($p_entry['compression'] == 0) {
  
          // ----- Reading the file
          $p_string = @fread($this->zip_fd, $p_entry['compressed_size']);
        }
        else {
  
          // ----- Reading the file
          $v_data = @fread($this->zip_fd, $p_entry['compressed_size']);
          
          // ----- Decompress the file
          if (($p_string = @gzinflate($v_data)) === FALSE) {
              // TBC
          }
        }
  
        // ----- Trace
      }
      else {
          // TBC : error : can not extract a folder in a string
      }
      
    }

  	// ----- Change abort status
  	if ($p_entry['status'] == "aborted") {
        $p_entry['status'] = "skipped";
  	}
	
    // ----- Look for post-extract callback
    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);
      
      // ----- Swap the content to header
      $v_local_header['content'] = $p_string;
      $p_string = '';

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_EXTRACT](PCLZIP_CB_POST_EXTRACT, $v_local_header);

      // ----- Swap back the content to header
      $p_string = $v_local_header['content'];
      unset($v_local_header['content']);

      // ----- Look for abort result
      if ($v_result == 2) {
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privReadFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privReadFileHeader(&$p_header)
  {
    $v_result=1;

    // ----- Read the 4 bytes signature
    $v_binary_data = @fread($this->zip_fd, 4);
    $v_data = unpack('Vid', $v_binary_data);

    // ----- Check signature
    if ($v_data['id'] != 0x04034b50)
    {

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read the first 42 bytes of the header
    $v_binary_data = fread($this->zip_fd, 26);

    // ----- Look for invalid block size
    if (strlen($v_binary_data) != 26)
    {
      $p_header['filename'] = "";
      $p_header['status'] = "invalid_header";

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid block size : ".strlen($v_binary_data));

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Extract the values
    $v_data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $v_binary_data);

    // ----- Get filename
    $p_header['filename'] = fread($this->zip_fd, $v_data['filename_len']);

    // ----- Get extra_fields
    if ($v_data['extra_len'] != 0) {
      $p_header['extra'] = fread($this->zip_fd, $v_data['extra_len']);
    }
    else {
      $p_header['extra'] = '';
    }

    // ----- Extract properties
    $p_header['version_extracted'] = $v_data['version'];
    $p_header['compression'] = $v_data['compression'];
    $p_header['size'] = $v_data['size'];
    $p_header['compressed_size'] = $v_data['compressed_size'];
    $p_header['crc'] = $v_data['crc'];
    $p_header['flag'] = $v_data['flag'];
    $p_header['filename_len'] = $v_data['filename_len'];

    // ----- Recuperate date in UNIX format
    $p_header['mdate'] = $v_data['mdate'];
    $p_header['mtime'] = $v_data['mtime'];
    if ($p_header['mdate'] && $p_header['mtime'])
    {
      // ----- Extract time
      $v_hour = ($p_header['mtime'] & 0xF800) >> 11;
      $v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
      $v_seconde = ($p_header['mtime'] & 0x001F)*2;

      // ----- Extract date
      $v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
      $v_month = ($p_header['mdate'] & 0x01E0) >> 5;
      $v_day = $p_header['mdate'] & 0x001F;

      // ----- Get UNIX date format
      $p_header['mtime'] = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);

    }
    else
    {
      $p_header['mtime'] = time();
    }

    // TBC
    //for(reset($v_data); $key = key($v_data); next($v_data)) {
    //}

    // ----- Set the stored filename
    $p_header['stored_filename'] = $p_header['filename'];

    // ----- Set the status field
    $p_header['status'] = "ok";

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privReadCentralFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privReadCentralFileHeader(&$p_header)
  {
    $v_result=1;

    // ----- Read the 4 bytes signature
    $v_binary_data = @fread($this->zip_fd, 4);
    $v_data = unpack('Vid', $v_binary_data);

    // ----- Check signature
    if ($v_data['id'] != 0x02014b50)
    {

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read the first 42 bytes of the header
    $v_binary_data = fread($this->zip_fd, 42);

    // ----- Look for invalid block size
    if (strlen($v_binary_data) != 42)
    {
      $p_header['filename'] = "";
      $p_header['status'] = "invalid_header";

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid block size : ".strlen($v_binary_data));

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Extract the values
    $p_header = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $v_binary_data);

    // ----- Get filename
    if ($p_header['filename_len'] != 0)
      $p_header['filename'] = fread($this->zip_fd, $p_header['filename_len']);
    else
      $p_header['filename'] = '';

    // ----- Get extra
    if ($p_header['extra_len'] != 0)
      $p_header['extra'] = fread($this->zip_fd, $p_header['extra_len']);
    else
      $p_header['extra'] = '';

    // ----- Get comment
    if ($p_header['comment_len'] != 0)
      $p_header['comment'] = fread($this->zip_fd, $p_header['comment_len']);
    else
      $p_header['comment'] = '';

    // ----- Extract properties

    // ----- Recuperate date in UNIX format
    //if ($p_header['mdate'] && $p_header['mtime'])
    // TBC : bug : this was ignoring time with 0/0/0
    if (1)
    {
      // ----- Extract time
      $v_hour = ($p_header['mtime'] & 0xF800) >> 11;
      $v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
      $v_seconde = ($p_header['mtime'] & 0x001F)*2;

      // ----- Extract date
      $v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
      $v_month = ($p_header['mdate'] & 0x01E0) >> 5;
      $v_day = $p_header['mdate'] & 0x001F;

      // ----- Get UNIX date format
      $p_header['mtime'] = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);

    }
    else
    {
      $p_header['mtime'] = time();
    }

    // ----- Set the stored filename
    $p_header['stored_filename'] = $p_header['filename'];

    // ----- Set default status to ok
    $p_header['status'] = 'ok';

    // ----- Look if it is a directory
    if (substr($p_header['filename'], -1) == '/') {
      //$p_header['external'] = 0x41FF0010;
      $p_header['external'] = 0x00000010;
    }


    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCheckFileHeaders()
  // Description :
  // Parameters :
  // Return Values :
  //   1 on success,
  //   0 on error;
  // --------------------------------------------------------------------------------
  function privCheckFileHeaders(&$p_local_header, &$p_central_header)
  {
    $v_result=1;

  	// ----- Check the static values
  	// TBC
  	if ($p_local_header['filename'] != $p_central_header['filename']) {
  	}
  	if ($p_local_header['version_extracted'] != $p_central_header['version_extracted']) {
  	}
  	if ($p_local_header['flag'] != $p_central_header['flag']) {
  	}
  	if ($p_local_header['compression'] != $p_central_header['compression']) {
  	}
  	if ($p_local_header['mtime'] != $p_central_header['mtime']) {
  	}
  	if ($p_local_header['filename_len'] != $p_central_header['filename_len']) {
  	}
  
  	// ----- Look for flag bit 3
  	if (($p_local_header['flag'] & 8) == 8) {
          $p_local_header['size'] = $p_central_header['size'];
          $p_local_header['compressed_size'] = $p_central_header['compressed_size'];
          $p_local_header['crc'] = $p_central_header['crc'];
  	}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privReadEndCentralDir()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privReadEndCentralDir(&$p_central_dir)
  {
    $v_result=1;

    // ----- Go to the end of the zip file
    $v_size = filesize($this->zipname);
    @fseek($this->zip_fd, $v_size);
    if (@ftell($this->zip_fd) != $v_size)
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to go to the end of the archive \''.$this->zipname.'\'');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- First try : look if this is an archive with no commentaries (most of the time)
    // in this case the end of central dir is at 22 bytes of the file end
    $v_found = 0;
    if ($v_size > 26) {
      @fseek($this->zip_fd, $v_size-22);
      if (($v_pos = @ftell($this->zip_fd)) != ($v_size-22))
      {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to seek back to the middle of the archive \''.$this->zipname.'\'');

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Read for bytes
      $v_binary_data = @fread($this->zip_fd, 4);
      $v_data = @unpack('Vid', $v_binary_data);

      // ----- Check signature
      if ($v_data['id'] == 0x06054b50) {
        $v_found = 1;
      }

      $v_pos = ftell($this->zip_fd);
    }

    // ----- Go back to the maximum possible size of the Central Dir End Record
    if (!$v_found) {
      $v_maximum_size = 65557; // 0xFFFF + 22;
      if ($v_maximum_size > $v_size)
        $v_maximum_size = $v_size;
      @fseek($this->zip_fd, $v_size-$v_maximum_size);
      if (@ftell($this->zip_fd) != ($v_size-$v_maximum_size))
      {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to seek back to the middle of the archive \''.$this->zipname.'\'');

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Read byte per byte in order to find the signature
      $v_pos = ftell($this->zip_fd);
      $v_bytes = 0x00000000;
      while ($v_pos < $v_size)
      {
        // ----- Read a byte
        $v_byte = @fread($this->zip_fd, 1);

        // -----  Add the byte
        //$v_bytes = ($v_bytes << 8) | Ord($v_byte);
        // Note we mask the old value down such that once shifted we can never end up with more than a 32bit number 
        // Otherwise on systems where we have 64bit integers the check below for the magic number will fail. 
        $v_bytes = ( ($v_bytes & 0xFFFFFF) << 8) | Ord($v_byte); 

        // ----- Compare the bytes
        if ($v_bytes == 0x504b0506)
        {
          $v_pos++;
          break;
        }

        $v_pos++;
      }

      // ----- Look if not found end of central dir
      if ($v_pos == $v_size)
      {

        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Unable to find End of Central Dir Record signature");

        // ----- Return
        return PclZip::errorCode();
      }
    }

    // ----- Read the first 18 bytes of the header
    $v_binary_data = fread($this->zip_fd, 18);

    // ----- Look for invalid block size
    if (strlen($v_binary_data) != 18)
    {

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid End of Central Dir Record size : ".strlen($v_binary_data));

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Extract the values
    $v_data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $v_binary_data);

    // ----- Check the global size
    if (($v_pos + $v_data['comment_size'] + 18) != $v_size) {

	  // ----- Removed in release 2.2 see readme file
	  // The check of the file size is a little too strict.
	  // Some bugs where found when a zip is encrypted/decrypted with 'crypt'.
	  // While decrypted, zip has training 0 bytes
	  if (0) {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT,
	                       'The central dir is not at the end of the archive.'
						   .' Some trailing bytes exists after the archive.');

      // ----- Return
      return PclZip::errorCode();
	  }
    }

    // ----- Get comment
    if ($v_data['comment_size'] != 0) {
      $p_central_dir['comment'] = fread($this->zip_fd, $v_data['comment_size']);
    }
    else
      $p_central_dir['comment'] = '';

    $p_central_dir['entries'] = $v_data['entries'];
    $p_central_dir['disk_entries'] = $v_data['disk_entries'];
    $p_central_dir['offset'] = $v_data['offset'];
    $p_central_dir['size'] = $v_data['size'];
    $p_central_dir['disk'] = $v_data['disk'];
    $p_central_dir['disk_start'] = $v_data['disk_start'];

    // TBC
    //for(reset($p_central_dir); $key = key($p_central_dir); next($p_central_dir)) {
    //}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDeleteByRule()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privDeleteByRule(&$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_list_detail = array();

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('rb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privCloseFd();
      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($this->zip_fd);

    // ----- Scan all the files
    // ----- Start at beginning of Central Dir
    $v_pos_entry = $v_central_dir['offset'];
    @rewind($this->zip_fd);
    if (@fseek($this->zip_fd, $v_pos_entry))
    {
      // ----- Close the zip file
      $this->privCloseFd();

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read each entry
    $v_header_list = array();
    $j_start = 0;
    for ($i=0, $v_nb_extracted=0; $i<$v_central_dir['entries']; $i++)
    {

      // ----- Read the file header
      $v_header_list[$v_nb_extracted] = array();
      if (($v_result = $this->privReadCentralFileHeader($v_header_list[$v_nb_extracted])) != 1)
      {
        // ----- Close the zip file
        $this->privCloseFd();

        return $v_result;
      }


      // ----- Store the index
      $v_header_list[$v_nb_extracted]['index'] = $i;

      // ----- Look for the specific extract rules
      $v_found = false;

      // ----- Look for extract by name rule
      if (   (isset($p_options[PCLZIP_OPT_BY_NAME]))
          && ($p_options[PCLZIP_OPT_BY_NAME] != 0)) {

          // ----- Look if the filename is in the list
          for ($j=0; ($j<sizeof($p_options[PCLZIP_OPT_BY_NAME])) && (!$v_found); $j++) {

              // ----- Look for a directory
              if (substr($p_options[PCLZIP_OPT_BY_NAME][$j], -1) == "/") {

                  // ----- Look if the directory is in the filename path
                  if (   (strlen($v_header_list[$v_nb_extracted]['stored_filename']) > strlen($p_options[PCLZIP_OPT_BY_NAME][$j]))
                      && (substr($v_header_list[$v_nb_extracted]['stored_filename'], 0, strlen($p_options[PCLZIP_OPT_BY_NAME][$j])) == $p_options[PCLZIP_OPT_BY_NAME][$j])) {
                      $v_found = true;
                  }
                  elseif (   (($v_header_list[$v_nb_extracted]['external']&0x00000010)==0x00000010) /* Indicates a folder */
                          && ($v_header_list[$v_nb_extracted]['stored_filename'].'/' == $p_options[PCLZIP_OPT_BY_NAME][$j])) {
                      $v_found = true;
                  }
              }
              // ----- Look for a filename
              elseif ($v_header_list[$v_nb_extracted]['stored_filename'] == $p_options[PCLZIP_OPT_BY_NAME][$j]) {
                  $v_found = true;
              }
          }
      }

      // ----- Look for extract by ereg rule
      // ereg() is deprecated with PHP 5.3
      /*
      else if (   (isset($p_options[PCLZIP_OPT_BY_EREG]))
               && ($p_options[PCLZIP_OPT_BY_EREG] != "")) {

          if (ereg($p_options[PCLZIP_OPT_BY_EREG], $v_header_list[$v_nb_extracted]['stored_filename'])) {
              $v_found = true;
          }
      }
      */

      // ----- Look for extract by preg rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_PREG]))
               && ($p_options[PCLZIP_OPT_BY_PREG] != "")) {

          if (preg_match($p_options[PCLZIP_OPT_BY_PREG], $v_header_list[$v_nb_extracted]['stored_filename'])) {
              $v_found = true;
          }
      }

      // ----- Look for extract by index rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_INDEX]))
               && ($p_options[PCLZIP_OPT_BY_INDEX] != 0)) {

          // ----- Look if the index is in the list
          for ($j=$j_start; ($j<sizeof($p_options[PCLZIP_OPT_BY_INDEX])) && (!$v_found); $j++) {

              if (($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['start']) && ($i<=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end'])) {
                  $v_found = true;
              }
              if ($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end']) {
                  $j_start = $j+1;
              }

              if ($p_options[PCLZIP_OPT_BY_INDEX][$j]['start']>$i) {
                  break;
              }
          }
      }
      else {
      	$v_found = true;
      }

      // ----- Look for deletion
      if ($v_found)
      {
        unset($v_header_list[$v_nb_extracted]);
      }
      else
      {
        $v_nb_extracted++;
      }
    }

    // ----- Look if something need to be deleted
    if ($v_nb_extracted > 0) {

        // ----- Creates a temporay file
        $v_zip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.tmp';

        // ----- Creates a temporary zip archive
        $v_temp_zip = new PclZip($v_zip_temp_name);

        // ----- Open the temporary zip file in write mode
        if (($v_result = $v_temp_zip->privOpenFd('wb')) != 1) {
            $this->privCloseFd();

            // ----- Return
            return $v_result;
        }

        // ----- Look which file need to be kept
        for ($i=0; $i<sizeof($v_header_list); $i++) {

            // ----- Calculate the position of the header
            @rewind($this->zip_fd);
            if (@fseek($this->zip_fd,  $v_header_list[$i]['offset'])) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Error log
                PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

                // ----- Return
                return PclZip::errorCode();
            }

            // ----- Read the file header
            $v_local_header = array();
            if (($v_result = $this->privReadFileHeader($v_local_header)) != 1) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }
            
            // ----- Check that local file header is same as central file header
            if ($this->privCheckFileHeaders($v_local_header,
			                                $v_header_list[$i]) != 1) {
                // TBC
            }
            unset($v_local_header);

            // ----- Write the file header
            if (($v_result = $v_temp_zip->privWriteFileHeader($v_header_list[$i])) != 1) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }

            // ----- Read/write the data block
            if (($v_result = PclZipUtilCopyBlock($this->zip_fd, $v_temp_zip->zip_fd, $v_header_list[$i]['compressed_size'])) != 1) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }
        }

        // ----- Store the offset of the central dir
        $v_offset = @ftell($v_temp_zip->zip_fd);

        // ----- Re-Create the Central Dir files header
        for ($i=0; $i<sizeof($v_header_list); $i++) {
            // ----- Create the file header
            if (($v_result = $v_temp_zip->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
                $v_temp_zip->privCloseFd();
                $this->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }

            // ----- Transform the header to a 'usable' info
            $v_temp_zip->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
        }


        // ----- Zip file comment
        $v_comment = '';
        if (isset($p_options[PCLZIP_OPT_COMMENT])) {
          $v_comment = $p_options[PCLZIP_OPT_COMMENT];
        }

        // ----- Calculate the size of the central header
        $v_size = @ftell($v_temp_zip->zip_fd)-$v_offset;

        // ----- Create the central dir footer
        if (($v_result = $v_temp_zip->privWriteCentralHeader(sizeof($v_header_list), $v_size, $v_offset, $v_comment)) != 1) {
            // ----- Reset the file list
            unset($v_header_list);
            $v_temp_zip->privCloseFd();
            $this->privCloseFd();
            @unlink($v_zip_temp_name);

            // ----- Return
            return $v_result;
        }

        // ----- Close
        $v_temp_zip->privCloseFd();
        $this->privCloseFd();

        // ----- Delete the zip file
        // TBC : I should test the result ...
        @unlink($this->zipname);

        // ----- Rename the temporary file
        // TBC : I should test the result ...
        //@rename($v_zip_temp_name, $this->zipname);
        PclZipUtilRename($v_zip_temp_name, $this->zipname);
    
        // ----- Destroy the temporary archive
        unset($v_temp_zip);
    }
    
    // ----- Remove every files : reset the file
    else if ($v_central_dir['entries'] != 0) {
        $this->privCloseFd();

        if (($v_result = $this->privOpenFd('wb')) != 1) {
          return $v_result;
        }

        if (($v_result = $this->privWriteCentralHeader(0, 0, 0, '')) != 1) {
          return $v_result;
        }

        $this->privCloseFd();
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDirCheck()
  // Description :
  //   Check if a directory exists, if not it creates it and all the parents directory
  //   which may be useful.
  // Parameters :
  //   $p_dir : Directory path to check.
  // Return Values :
  //    1 : OK
  //   -1 : Unable to create directory
  // --------------------------------------------------------------------------------
  function privDirCheck($p_dir, $p_is_dir=false)
  {
    $v_result = 1;


    // ----- Remove the final '/'
    if (($p_is_dir) && (substr($p_dir, -1)=='/'))
    {
      $p_dir = substr($p_dir, 0, strlen($p_dir)-1);
    }

    // ----- Check the directory availability
    if ((is_dir($p_dir)) || ($p_dir == ""))
    {
      return 1;
    }

    // ----- Extract parent directory
    $p_parent_dir = dirname($p_dir);

    // ----- Just a check
    if ($p_parent_dir != $p_dir)
    {
      // ----- Look for parent directory
      if ($p_parent_dir != "")
      {
        if (($v_result = $this->privDirCheck($p_parent_dir)) != 1)
        {
          return $v_result;
        }
      }
    }

    // ----- Create the directory
    if (!@mkdir($p_dir, 0777))
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_DIR_CREATE_FAIL, "Unable to create directory '$p_dir'");

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privMerge()
  // Description :
  //   If $p_archive_to_add does not exist, the function exit with a success result.
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privMerge(&$p_archive_to_add)
  {
    $v_result=1;

    // ----- Look if the archive_to_add exists
    if (!is_file($p_archive_to_add->zipname))
    {

      // ----- Nothing to merge, so merge is a success
      $v_result = 1;

      // ----- Return
      return $v_result;
    }

    // ----- Look if the archive exists
    if (!is_file($this->zipname))
    {

      // ----- Do a duplicate
      $v_result = $this->privDuplicate($p_archive_to_add->zipname);

      // ----- Return
      return $v_result;
    }

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('rb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privCloseFd();
      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($this->zip_fd);

    // ----- Open the archive_to_add file
    if (($v_result=$p_archive_to_add->privOpenFd('rb')) != 1)
    {
      $this->privCloseFd();

      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir_to_add = array();
    if (($v_result = $p_archive_to_add->privReadEndCentralDir($v_central_dir_to_add)) != 1)
    {
      $this->privCloseFd();
      $p_archive_to_add->privCloseFd();

      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($p_archive_to_add->zip_fd);

    // ----- Creates a temporay file
    $v_zip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.tmp';

    // ----- Open the temporary file in write mode
    if (($v_zip_temp_fd = @fopen($v_zip_temp_name, 'wb')) == 0)
    {
      $this->privCloseFd();
      $p_archive_to_add->privCloseFd();

      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_zip_temp_name.'\' in binary write mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Copy the files from the archive to the temporary file
    // TBC : Here I should better append the file and go back to erase the central dir
    $v_size = $v_central_dir['offset'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($this->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Copy the files from the archive_to_add into the temporary file
    $v_size = $v_central_dir_to_add['offset'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($p_archive_to_add->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Store the offset of the central dir
    $v_offset = @ftell($v_zip_temp_fd);

    // ----- Copy the block of file headers from the old archive
    $v_size = $v_central_dir['size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($this->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Copy the block of file headers from the archive_to_add
    $v_size = $v_central_dir_to_add['size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($p_archive_to_add->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Merge the file comments
    $v_comment = $v_central_dir['comment'].' '.$v_central_dir_to_add['comment'];

    // ----- Calculate the size of the (new) central header
    $v_size = @ftell($v_zip_temp_fd)-$v_offset;

    // ----- Swap the file descriptor
    // Here is a trick : I swap the temporary fd with the zip fd, in order to use
    // the following methods on the temporary fil and not the real archive fd
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Create the central dir footer
    if (($v_result = $this->privWriteCentralHeader($v_central_dir['entries']+$v_central_dir_to_add['entries'], $v_size, $v_offset, $v_comment)) != 1)
    {
      $this->privCloseFd();
      $p_archive_to_add->privCloseFd();
      @fclose($v_zip_temp_fd);
      $this->zip_fd = null;

      // ----- Reset the file list
      unset($v_header_list);

      // ----- Return
      return $v_result;
    }

    // ----- Swap back the file descriptor
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Close
    $this->privCloseFd();
    $p_archive_to_add->privCloseFd();

    // ----- Close the temporary file
    @fclose($v_zip_temp_fd);

    // ----- Delete the zip file
    // TBC : I should test the result ...
    @unlink($this->zipname);

    // ----- Rename the temporary file
    // TBC : I should test the result ...
    //@rename($v_zip_temp_name, $this->zipname);
    PclZipUtilRename($v_zip_temp_name, $this->zipname);

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDuplicate()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privDuplicate($p_archive_filename)
  {
    $v_result=1;

    // ----- Look if the $p_archive_filename exists
    if (!is_file($p_archive_filename))
    {

      // ----- Nothing to duplicate, so duplicate is a success.
      $v_result = 1;

      // ----- Return
      return $v_result;
    }

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('wb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Open the temporary file in write mode
    if (($v_zip_temp_fd = @fopen($p_archive_filename, 'rb')) == 0)
    {
      $this->privCloseFd();

      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive file \''.$p_archive_filename.'\' in binary write mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Copy the files from the archive to the temporary file
    // TBC : Here I should better append the file and go back to erase the central dir
    $v_size = filesize($p_archive_filename);
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($v_zip_temp_fd, $v_read_size);
      @fwrite($this->zip_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Close
    $this->privCloseFd();

    // ----- Close the temporary file
    @fclose($v_zip_temp_fd);

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privErrorLog()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privErrorLog($p_error_code=0, $p_error_string='')
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      PclError($p_error_code, $p_error_string);
    }
    else {
      $this->error_code = $p_error_code;
      $this->error_string = $p_error_string;
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privErrorReset()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privErrorReset()
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      PclErrorReset();
    }
    else {
      $this->error_code = 0;
      $this->error_string = '';
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDisableMagicQuotes()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privDisableMagicQuotes()
  {
    $v_result=1;

    // ----- Look if function exists
    if (   (!function_exists("get_magic_quotes_runtime"))
	    || (!function_exists("set_magic_quotes_runtime"))) {
      return $v_result;
	}

    // ----- Look if already done
    if ($this->magic_quotes_status != -1) {
      return $v_result;
	}

	// ----- Get and memorize the magic_quote value
	$this->magic_quotes_status = @get_magic_quotes_runtime();

	// ----- Disable magic_quotes
	if ($this->magic_quotes_status == 1) {
	  @set_magic_quotes_runtime(0);
	}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privSwapBackMagicQuotes()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privSwapBackMagicQuotes()
  {
    $v_result=1;

    // ----- Look if function exists
    if (   (!function_exists("get_magic_quotes_runtime"))
	    || (!function_exists("set_magic_quotes_runtime"))) {
      return $v_result;
	}

    // ----- Look if something to do
    if ($this->magic_quotes_status != -1) {
      return $v_result;
	}

	// ----- Swap back magic_quotes
	if ($this->magic_quotes_status == 1) {
  	  @set_magic_quotes_runtime($this->magic_quotes_status);
	}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  }
  // End of class
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilPathReduction()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function PclZipUtilPathReduction($p_dir)
  {
    $v_result = "";

    // ----- Look for not empty path
    if ($p_dir != "") {
      // ----- Explode path by directory names
      $v_list = explode("/", $p_dir);

      // ----- Study directories from last to first
      $v_skip = 0;
      for ($i=sizeof($v_list)-1; $i>=0; $i--) {
        // ----- Look for current path
        if ($v_list[$i] == ".") {
          // ----- Ignore this directory
          // Should be the first $i=0, but no check is done
        }
        else if ($v_list[$i] == "..") {
		  $v_skip++;
        }
        else if ($v_list[$i] == "") {
		  // ----- First '/' i.e. root slash
		  if ($i == 0) {
            $v_result = "/".$v_result;
		    if ($v_skip > 0) {
		        // ----- It is an invalid path, so the path is not modified
		        // TBC
		        $v_result = $p_dir;
                $v_skip = 0;
		    }
		  }
		  // ----- Last '/' i.e. indicates a directory
		  else if ($i == (sizeof($v_list)-1)) {
            $v_result = $v_list[$i];
		  }
		  // ----- Double '/' inside the path
		  else {
            // ----- Ignore only the double '//' in path,
            // but not the first and last '/'
		  }
        }
        else {
		  // ----- Look for item to skip
		  if ($v_skip > 0) {
		    $v_skip--;
		  }
		  else {
            $v_result = $v_list[$i].($i!=(sizeof($v_list)-1)?"/".$v_result:"");
		  }
        }
      }
      
      // ----- Look for skip
      if ($v_skip > 0) {
        while ($v_skip > 0) {
            $v_result = '../'.$v_result;
            $v_skip--;
        }
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilPathInclusion()
  // Description :
  //   This function indicates if the path $p_path is under the $p_dir tree. Or,
  //   said in an other way, if the file or sub-dir $p_path is inside the dir
  //   $p_dir.
  //   The function indicates also if the path is exactly the same as the dir.
  //   This function supports path with duplicated '/' like '//', but does not
  //   support '.' or '..' statements.
  // Parameters :
  // Return Values :
  //   0 if $p_path is not inside directory $p_dir
  //   1 if $p_path is inside directory $p_dir
  //   2 if $p_path is exactly the same as $p_dir
  // --------------------------------------------------------------------------------
  function PclZipUtilPathInclusion($p_dir, $p_path)
  {
    $v_result = 1;
    
    // ----- Look for path beginning by ./
    if (   ($p_dir == '.')
        || ((strlen($p_dir) >=2) && (substr($p_dir, 0, 2) == './'))) {
      $p_dir = PclZipUtilTranslateWinPath(getcwd(), FALSE).'/'.substr($p_dir, 1);
    }
    if (   ($p_path == '.')
        || ((strlen($p_path) >=2) && (substr($p_path, 0, 2) == './'))) {
      $p_path = PclZipUtilTranslateWinPath(getcwd(), FALSE).'/'.substr($p_path, 1);
    }

    // ----- Explode dir and path by directory separator
    $v_list_dir = explode("/", $p_dir);
    $v_list_dir_size = sizeof($v_list_dir);
    $v_list_path = explode("/", $p_path);
    $v_list_path_size = sizeof($v_list_path);

    // ----- Study directories paths
    $i = 0;
    $j = 0;
    while (($i < $v_list_dir_size) && ($j < $v_list_path_size) && ($v_result)) {

      // ----- Look for empty dir (path reduction)
      if ($v_list_dir[$i] == '') {
        $i++;
        continue;
      }
      if ($v_list_path[$j] == '') {
        $j++;
        continue;
      }

      // ----- Compare the items
      if (($v_list_dir[$i] != $v_list_path[$j]) && ($v_list_dir[$i] != '') && ( $v_list_path[$j] != ''))  {
        $v_result = 0;
      }

      // ----- Next items
      $i++;
      $j++;
    }

    // ----- Look if everything seems to be the same
    if ($v_result) {
      // ----- Skip all the empty items
      while (($j < $v_list_path_size) && ($v_list_path[$j] == '')) $j++;
      while (($i < $v_list_dir_size) && ($v_list_dir[$i] == '')) $i++;

      if (($i >= $v_list_dir_size) && ($j >= $v_list_path_size)) {
        // ----- There are exactly the same
        $v_result = 2;
      }
      else if ($i < $v_list_dir_size) {
        // ----- The path is shorter than the dir
        $v_result = 0;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilCopyBlock()
  // Description :
  // Parameters :
  //   $p_mode : read/write compression mode
  //             0 : src & dest normal
  //             1 : src gzip, dest normal
  //             2 : src normal, dest gzip
  //             3 : src & dest gzip
  // Return Values :
  // --------------------------------------------------------------------------------
  function PclZipUtilCopyBlock($p_src, $p_dest, $p_size, $p_mode=0)
  {
    $v_result = 1;

    if ($p_mode==0)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @fread($p_src, $v_read_size);
        @fwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }
    else if ($p_mode==1)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @gzread($p_src, $v_read_size);
        @fwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }
    else if ($p_mode==2)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @fread($p_src, $v_read_size);
        @gzwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }
    else if ($p_mode==3)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @gzread($p_src, $v_read_size);
        @gzwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilRename()
  // Description :
  //   This function tries to do a simple rename() function. If it fails, it
  //   tries to copy the $p_src file in a new $p_dest file and then unlink the
  //   first one.
  // Parameters :
  //   $p_src : Old filename
  //   $p_dest : New filename
  // Return Values :
  //   1 on success, 0 on failure.
  // --------------------------------------------------------------------------------
  function PclZipUtilRename($p_src, $p_dest)
  {
    $v_result = 1;

    // ----- Try to rename the files
    if (!@rename($p_src, $p_dest)) {

      // ----- Try to copy & unlink the src
      if (!@copy($p_src, $p_dest)) {
        $v_result = 0;
      }
      else if (!@unlink($p_src)) {
        $v_result = 0;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilOptionText()
  // Description :
  //   Translate option value in text. Mainly for debug purpose.
  // Parameters :
  //   $p_option : the option value.
  // Return Values :
  //   The option text value.
  // --------------------------------------------------------------------------------
  function PclZipUtilOptionText($p_option)
  {
    
    $v_list = get_defined_constants();
    for (reset($v_list); $v_key = key($v_list); next($v_list)) {
	    $v_prefix = substr($v_key, 0, 10);
	    if ((   ($v_prefix == 'PCLZIP_OPT')
           || ($v_prefix == 'PCLZIP_CB_')
           || ($v_prefix == 'PCLZIP_ATT'))
	        && ($v_list[$v_key] == $p_option)) {
        return $v_key;
	    }
    }
    
    $v_result = 'Unknown';

    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilTranslateWinPath()
  // Description :
  //   Translate windows path by replacing '\' by '/' and optionally removing
  //   drive letter.
  // Parameters :
  //   $p_path : path to translate.
  //   $p_remove_disk_letter : true | false
  // Return Values :
  //   The path translated.
  // --------------------------------------------------------------------------------
  function PclZipUtilTranslateWinPath($p_path, $p_remove_disk_letter=true)
  {
    if (stristr(php_uname(), 'windows')) {
      // ----- Look for potential disk letter
      if (($p_remove_disk_letter) && (($v_position = strpos($p_path, ':')) != false)) {
          $p_path = substr($p_path, $v_position+1);
      }
      // ----- Change potential windows directory separator
      if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0,1) == '\\')) {
          $p_path = strtr($p_path, '\\', '/');
      }
    }
    return $p_path;
  }
  // --------------------------------------------------------------------------------

  
  
  
/*********************************************
 * 
 * START CODING
 * 
 *********************************************/



if (isset($_GET['ajax'])) {
    $response = array();
    
    $response['content'] = '';
    
    // Copy cx files to the root directory
    if (empty($_SESSION['contrexx']['getCustomizingFinished'])) {
        $customizingDetection->startProgress();
    } else {
        $customizingDetection->getCustomizedFiles();
    }

    $response['content'] = $customizingDetection->output;
    
    echo json_encode($response);
    die();
}

if (isset($_GET['sendDownloadRequest'])) {
    $customizingDetection->downloadCustomizedFiles();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>Contrexx Maintenance</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="robots" content="noindex" />
        <meta name="robots" content="nofollow" />
        <meta name="robots" content="noarchive" />
        <meta http-equiv="pragma" content="no-cache" />
        <meta http-equiv="cache-control" content="no-cache" />
        <style type="text/css">
            /* http://meyerweb.com/eric/tools/css/reset/ 
                v2.0 | 20110126
                License: none (public domain)
             */

             html, body, div, span, applet, object, iframe,
             h1, h2, h3, h4, h5, h6, p, blockquote, pre,
             a, abbr, acronym, address, big, cite, code,
             del, dfn, em, img, ins, kbd, q, s, samp,
             small, strike, strong, sub, sup, tt, var,
             b, u, i, center,
             dl, dt, dd, ol, ul, li,
             fieldset, form, label, legend,
             table, caption, tbody, tfoot, thead, tr, th, td,
             article, aside, canvas, details, embed, 
             figure, figcaption, footer, header, hgroup, 
             menu, nav, output, ruby, section, summary,
             time, mark, audio, video {
                     margin: 0;
                     padding: 0;
                     border: 0;
                     font-size: 100%;
                     font: inherit;
                     vertical-align: baseline;
             }
             /* HTML5 display-role reset for older browsers */
             article, aside, details, figcaption, figure, 
             footer, header, hgroup, menu, nav, section {
                     display: block;
             }
             body {line-height: 1;}
             ol, ul {list-style: none;}
             blockquote, q {quotes: none;}
             blockquote:before, blockquote:after,
             q:before, q:after {content: '';content: none;}
             table {border-collapse: collapse;border-spacing: 0;}
             .content-wraper {margin: 100px auto 120px auto;width: 960px;min-height: 500px;padding: 15px 15px 67px 15px;border: 1px solid #e8e8e8;}
             h1 {margin: 15px 0 10px 0;font-size: 24px;font-weight: normal;}
             h1.first {margin: 0 0 15px 0;}
             table.adminlist {background: #ffffff;}
             table, table tr, table th, table td {float: none;height: auto;background: transparent;padding: 0;border: 0;margin: 0;outline: 0;vertical-align: top;text-align: left;empty-cells: show;border-collapse: collapse;}
             table.adminlist th {height: 25px;line-height: 25px;background: #0a85c8;padding: 0 5px;font-weight: bold;color: #ffffff;}
            table.adminlist td {height: 20px;line-height: 20px;padding: 3px 3px 2px 3px;border-bottom: #e5e5e5 1px solid;vertical-align: center;}
            .failed {color: red;font-weight: bold;}
            .successfull {color: #008000;font-weight: bold;}
            .right {float: right;}
            .left {float: left;} 
            .width-100 {width: 100%;}
            .success {background: #DFF0D8; padding: 10px;}
            .error {color: #B94A48;background: #F2DEDE;}
            .ajax-response > div {padding: 8px;margin: 0 0 15px 0;overflow: auto;}           
            #ajaxloader {display: none;width: 30px;height: 30px;margin: 0 auto;border: 8px solid #000;border-right-color: transparent;border-radius: 50%;box-shadow: 0 0 25px 2px #ccc;-webkit-animation: spin 1s linear infinite;-moz-animation: spin 1s linear infinite;-ms-animation: spin 1s linear infinite;-o-animation: spin 1s linear infinite;animation: spin 1s linear infinite;}
            #ajaxloader {border-bottom-color: transparent;}
            #ajaxloader::after {display: block;content: " ";width: 9px;height: 9px;border: 6px solid #000;margin: 4px;border-radius: 50%;}
            #ajaxloader::after {width: 13px;height: 13px;margin: 1px;border-width: 8px;border-top-color: transparent;border-left-color: transparent;}
            
            @-webkit-keyframes spin { from { -webkit-transform: rotate(0deg); opacity: 0.4; } 50%  { -webkit-transform: rotate(180deg); opacity: 1; } to   { -webkit-transform: rotate(360deg); opacity: 0.4; } }
            @-moz-keyframes spin { from { -moz-transform: rotate(0deg); opacity: 0.4; } 50%  { -moz-transform: rotate(180deg); opacity: 1; } to   { -moz-transform: rotate(360deg); opacity: 0.4; } }
            @-ms-keyframes spin { from { -ms-transform: rotate(0deg); opacity: 0.4; } 50%  { -ms-transform: rotate(180deg); opacity: 1; } to   { -ms-transform: rotate(360deg); opacity: 0.4; } }
            @-o-keyframes spin { from { -o-transform: rotate(0deg); opacity: 0.4; } 50%  { -o-transform: rotate(180deg); opacity: 1; } to   { -o-transform: rotate(360deg); opacity: 0.4; } }
            @keyframes spin { from { transform: rotate(0deg); opacity: 0.2; } 50%  { transform: rotate(180deg); opacity: 1; } to   { transform: rotate(360deg); opacity: 0.2; } }

            span.loader:before {
                content: " ";
                overflow: hidden;
                width: 13px;
                height: 13px;
                border-radius: 50%;
                float: left;
                margin-right: 4px;
                border: 2px dotted #000;
                -webkit-animation: spin 1.7s infinite ease;
                animation: spin 1.7s infinite ease;
              }
            #tabs {float: left; width: 100%; padding: 0px; margin-bottom: 10px;   background: #DFF0D8 !important;}
            .tabMenu.ui-state-active {background: #DFF0D8 !important; }
            .tabMenu {font-size: small;}
            .fileList {font-size: smaller; line-height: 20px;}
            .tabMenu.ui-state-active a {font-weight: bold;}
        </style>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css"></link>
        <script src="//code.jquery.com/jquery-1.10.2.js"></script>
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <script type="text/javascript">
            $(function(){
               $("#getCustomizing").submit(function(){
                   $.ajax({
                       url : '?ajax=1&process=getCustomizing',
                       dataType : 'json',
                       beforeSend : function() {
                           $('#ajaxloader').show();
                           $('.process-update').remove();
                       },
                       success : function(res) {
                           //console.log(res.content);
                           $('.ajax-response').html(res.content);                               
                           $('#ajaxloader').hide();                                                   
                       }
                   });
                   return false;
               });
            });
        </script>
    </head>
    <body>
        <div class="content-wraper">
            <h1 class="head-title">Get customized files</h1>
            <br />
            <form id="getCustomizing" action="" onSubmit="return false;">                            
                <table class="adminlist" cellpadding="3" cellspacing="0" width="100%">
                    <tr>
                        <th colspan="2">Installation</th>
                    </tr>
                    <tr>
                        <td width="20%;">Installed version</td>
                        <td> <?php echo $_CONFIG['coreCmsVersion']; ?></span></td>
                    </tr>
                    <tr>
                        <td>Installation Root</td>
                        <td> <?php echo ASCMS_DOCUMENT_ROOT; ?></span></td>
                    </tr>
                    <tr rowspan="2">
                        <td colspan="2"></td>
                    </tr>                
                    <tr>
                        <th colspan="2">PHP</th>
                    </tr>
                    <tr>
                        <td>PHP Version </td>
                        <td> <span class="<?php echo $customizingDetection->checkPHPVersion($arrConfig['cmsRequiredPHP']) ? 'successfull' : 'failed'; ?>"><?php echo phpversion(); ?></span></td>
                    </tr>
                    <tr rowspan="2">
                        <td colspan="2"></td>
                    </tr>                
                    <tr>
                        <th colspan="2">MySQL</th>
                    </tr>
                    <tr>
                        <td>MySQL Version </td>
                        <td> <span class="<?php echo $customizingDetection->checkMySQLVersion($arrConfig['cmsRequiredMySQL']) ? 'successfull' : 'failed'; ?>"><?php echo $customizingDetection->getMySQLServerVersion($arrConfig['cmsRequiredMySQL']); ?></span></td>
                    </tr>                              
                </table>
                <br />
                <br />
                <div class="ajax-response"></div>
                <div id="ajaxloader"></div>
                <div class="process-update right">
                    <input type="submit" name="getCustomization" value="Check website for customizings" />
                </div>
            </form>
        </div>
    </body>
</html>
