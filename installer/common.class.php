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
require_once(ASCMS_FRAMEWORK_PATH.'/DBG/DBG.php');
require_once(ASCMS_FRAMEWORK_PATH.'/FileSystem/FileInterface.interface.php');
require_once(ASCMS_FRAMEWORK_PATH.'/FileSystem/FileSystemFile.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/FileSystem/FTPFile.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/FileSystem/File.class.php');
require_once(ASCMS_FRAMEWORK_PATH.'/FileSystem/FileSystem.class.php');

/**
 * Install Wizard Controller
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       $Id:     Exp $
 * @package     cloudrexx
 * @subpackage  installer
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Factory callback for AdoDB NewConnection
 * @deprecated Use Doctrine!
 * @return \Cx\Core\Model\CustomAdodbPdo
 */
function cxupdateAdodbPdoConnectionFactory() {
    require_once '..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'adodb'.DIRECTORY_SEPARATOR.'drivers'.DIRECTORY_SEPARATOR.'adodb-pdo.inc.php';
    require_once '..'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'CustomAdodbPdo.class.php';
    $obj = new \Cx\Core\Model\CustomAdodbPdo(CommonFunctions::$pdo);
    return $obj;
}

/**
 * Install Wizard Controller
 *
 * The Install Wizard
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       $Id:     Exp $
 * @package     cloudrexx
 * @subpackage  installer
 */
class CommonFunctions
{
    public static $pdo = null;
    var $defaultLanguage;
    var $detectedLanguage;
    var $adoDbPath;
    var $ftpTimeout;
    var $ftpPort;
    var $newestVersion;
    var $_ftpFileWinPCRE = '#^()(?:[0-9\-]+)\s+(?:[0-9]{2}:[0-9]{2}(?:AM|PM|))\s+(?:\<DIR\>|[0-9]+|)\s+(.*)$#';
    var $_ftpFileUnixPCRE = '#^(?:([bcdlsp-])[rwxtTsS-]{9})\s+(?:[0-9]+)\s+(?:\S+)\s+(?:\S+)\s+(?:[0-9]+)\s+(?:[A-Z][a-z]+\s+[0-9]{1,2}\s+(?:[0-9]{4}|[0-9]{2}:[0-9]{2}|))\s+(.*)$#';

    function CommonFunctions()
    {
        $this->__construct();
    }

    function __construct() {
        global $defaultLanguage, $arrLanguages;

        $this->defaultLanguage = $defaultLanguage;
        $this->adoDbPath = '..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'adodb'.DIRECTORY_SEPARATOR.'adodb.inc.php';
        $this->ftpTimeout = 5;
        $this->ftpPort = 21;

        // set language
        if (isset($_GET['langId']) && array_key_exists($_GET['langId'], $arrLanguages)) {
            $_SESSION['installer']['langId'] = $_GET['langId'];
        }
    }

    /**
    * init language
    *
    * initialize language array
    *
    * @access   public
    * @global   array   $_ARRLANG
    * @global   string  $basePath
    * @global   string  $language
    */
    function initLanguage() {
        global $_ARRLANG, $basePath, $language;

        $language = $this->_getLanguage();

        require_once($basePath.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$language.'.lang.php');
    }

    /**
    * check language existence
    *
    * check if the language file of the language $language exists
    *
    * @access   private
    * @param    string  $language
    * @global   string  $basePath
    * @return   boolean true if exists, false if not
    */
    function _checkLanguageExistence($language) {
        global $basePath;

        if (file_exists($basePath.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$language.'.lang.php')) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * get language
    *
    * get the language to use
    *
    * @access   private
    * @global   string  $basePath
    * @global   array   $arrLanguages
    * @return   string  language to use
    */
    function _getLanguage() {
        global $basePath, $arrLanguages;

        $language = $this->defaultLanguage;

        if (isset($_SESSION['installer']['langId'])) {
            $language = $arrLanguages[$_SESSION['installer']['langId']]['lang'];
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLanguage = substr(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']),0,2);

            foreach ($arrLanguages as $arrLang) {
                if ($browserLanguage == $arrLang['lang']) {
                    $language;
                    break;
                }
            }
        }

        if ($this->_checkLanguageExistence($language)) {
            return $language;
        } else {
            return $this->defaultLanguage;
        }
    }

    /**
    * get database object
    *
    * return an database connection object. if not already a connection has established, then it will do it
    *
    * @access   private
    * @param    string  $statusMsgError message
    * @global   object  $objDb
    * @global   array   $_ARRLANG
    * @global   string  $dbType
    * @return   mixed   object $objDb on success, false on failure
    */
    function _getDbObject(&$statusMsg, $useDb = true) {
        global $objDb, $_ARRLANG, $dbType, $useUtf8, $ADODB_FETCH_MODE, $ADODB_NEWCONNECTION;

        if (isset($objDb)) {
            return $objDb;
        } else {
            // open db connection
            require_once $this->adoDbPath;

                self::$pdo = new \PDO(
                    'mysql:host='.$_SESSION['installer']['config']['dbHostname'] . ($useDb ? ';dbname=' . $_SESSION['installer']['config']['dbDatabaseName'] : ''),
                    $_SESSION['installer']['config']['dbUsername'],
                    $_SESSION['installer']['config']['dbPassword']
                );
            self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
            $ADODB_NEWCONNECTION = 'cxupdateAdodbPdoConnectionFactory';

            $objDb = ADONewConnection('pdo');

            $errorNo = $objDb->ErrorNo();
            if ($errorNo != 0) {
                if ($errorNo == 1049) {
                    $statusMsg = str_replace("[DATABASE]", $_SESSION['installer']['config']['dbDatabaseName'], $_ARRLANG['TXT_DATABASE_DOES_NOT_EXISTS']."<br />");
                } else {
                    $statusMsg =  $objDb->ErrorMsg();
                }
                unset($objDb);
                return false;
            }
            if ($objDb) {
                // Check for InnoDB
                $res = $objDb->Execute('SHOW ENGINES');
                $engines = array();
                while (!$res->EOF) {
                    $engines[] = $res->fields['Engine'];
                    $res->MoveNext();
                }
                if (!in_array('InnoDB', $engines)) {
                    $statusMsg = $_ARRLANG['TXT_ENGINGE_NOT_SUPPORTED'];
                    return false;
                }

                $this->setTimezone($objDb);

                // Disable STRICT_TRANS_TABLES mode:
                $res = $objDb->Execute('SELECT @@sql_mode');
                if ($res->EOF) {
                    $statusMsg = 'Database mode error';
                    return false;
                }
                $sqlModes = explode(',', $res->fields['@@sql_mode']);
                array_walk($sqlModes, 'trim');
                if (($index = array_search('STRICT_TRANS_TABLES', $sqlModes)) !== false) {
                    unset($sqlModes[$index]);
                }
                if (($index = array_search('STRICT_ALL_TABLES', $sqlModes)) !== false) {
                    unset($sqlModes[$index]);
                }
                if (($index = array_search('TRADITIONAL', $sqlModes)) !== false) {
                    unset($sqlModes[$index]);
                }
                if (($index = array_search('NO_ZERO_DATE', $sqlModes)) !== false) {
                    unset($sqlModes[$index]);
                }
                if (($index = array_search('NO_ZERO_IN_DATE', $sqlModes)) !== false) {
                    unset($sqlModes[$index]);
                }
                $objDb->Execute('SET sql_mode = \'' . implode(',', $sqlModes) . '\'');

                if (($mysqlServerVersion = $this->getMySQLServerVersion()) !== false && !$this->_isNewerVersion($mysqlServerVersion, '4.1')) {
                    if ($objDb->Execute('SET CHARACTER SET '.($useUtf8 ? 'utf8' : 'latin1')) !== false) {
                        return $objDb;
                    }
                } else {
                    return $objDb;
                }

                $statusMsg = $_ARRLANG['TXT_CANNOT_CONNECT_TO_DB_SERVER']."<i>&nbsp;(".$objDb->ErrorMsg().")</i><br />";
                unset($objDb);
            } else {
                $statusMsg = $_ARRLANG['TXT_CANNOT_CONNECT_TO_DB_SERVER']."<i>&nbsp;(".$objDb->ErrorMsg().")</i><br />";
                unset($objDb);
            }
            return false;
        }
    }

    /**
     * Sets the MySQL default timezone
     *
     * @access  private
     * @param   ADONewConnection    $objDatabase
     * @param   string              $timezone
     */
    private function setTimezone($objDatabase, $timezone = '') {
        $arrTimezones = timezone_identifiers_list();

        if (empty($timezone)) {
            $timezone = isset($_SESSION['installer']['config']['timezone']) ? $_SESSION['installer']['config']['timezone'] : '';
        }

        if (array_key_exists($timezone, $arrTimezones)) {
            $timezone = $arrTimezones[$timezone];
        }

        if (($timezone !== '') && (in_array($timezone, $arrTimezones) || ($timezone == 'UTC'))) {
            if (!$objDatabase->Execute('SET TIME_ZONE = "'.addslashes($timezone).'"')) {
                //calculate and set the timezone offset if the mysql timezone tables aren't loaded
                $objDateTimeZone = new DateTimeZone($timezone);
                $objDateTime = new DateTime('now', $objDateTimeZone);
                $offset = $objDateTimeZone->getOffset($objDateTime);
                $offsetHours = round(abs($offset)/3600);
                $offsetMinutes = round((abs($offset)-$offsetHours*3600) / 60);
                $offsetString = ($offset > 0 ? '+' : '-').($offsetHours < 10 ? '0' : '').$offsetHours.':'.($offsetMinutes < 10 ? '0' : '').$offsetMinutes;
                $objDatabase->Execute('SET TIME_ZONE="'.addslashes($offsetString).'"');
            }
        }
    }

    /**
     * Returns all timezones
     *
     * @access      private
     * @global      array       $_ARRLANG
     * @return      string      $options     timezones as HTML <option></option>
     */
    public function getTimezoneOptions()
    {
        global $_ARRLANG;

        $arrTimezoneIdentifiers = timezone_identifiers_list();
        $defaultTimezoneName = @date_default_timezone_get();

        if (isset($_SESSION['installer']['config']['timezone']) && array_key_exists($_SESSION['installer']['config']['timezone'], $arrTimezoneIdentifiers)) {
            $selected = $_SESSION['installer']['config']['timezone'];
        } else if (!isset($_SESSION['installer']['config']['timezone']) &&
                   ($defaultTimezoneId = array_search($defaultTimezoneName, $arrTimezoneIdentifiers)) &&
                   !empty($defaultTimezoneId)
        ) {
            $selected = $defaultTimezoneId;
            $_SESSION['installer']['config']['timezone'] = $defaultTimezoneId;
        } else {
            $selected = -1;
        }
        $options = '<option value="-1"'.($selected == -1 ? ' selected="selected"' : '').'>'.$_ARRLANG['TXT_PLEASE_SELECT'].'</option>';
        foreach ($arrTimezoneIdentifiers as $id => $name) {
            $dateTimeZone = new DateTimeZone($name);
            $dateTime     = new DateTime('now', $dateTimeZone);
            $timeOffset   = $dateTimeZone->getOffset($dateTime);
            $plusOrMinus  = $timeOffset < 0 ? '-' : '+';
            $gmt          = 'GMT ' . $plusOrMinus . ' ' . gmdate('g:i', $timeOffset);
            $options .= '<option value="'.$id.'"'.($selected == $id ? ' selected="selected"' : '').'>'.$name.' ('.$gmt.')'.'</option>';
        }
        return $options;
    }

    /**
    * close Db connection
    *
    * close database connection
    *
    * @access   public
    * @global   object  $objDb
    */
    function closeDbConnection() {
        global $objDb;

        if (isset($objDb)) {
            @$objDb->Close();
        }
    }

    function getMySQLServerVersion()
    {
        $statusMsg = '';

        $objDb = $this->_getDbObject($statusMsg, false);
        if ($objDb === false) {
            return $statusMsg;
        } else {
            $objVersion = $objDb->SelectLimit('SELECT VERSION() AS mysqlversion', 1);
            if ($objVersion !== false && $objVersion->RecordCount() == 1 && preg_match('#^([0-9.]+)#', $objVersion->fields['mysqlversion'], $version)) {
                return $version[1];
            } else {
                return false;
            }
        }
    }

    function _isNewerVersion($installedVersion, $newVersion)
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

    function _getUtf8Collations()
    {
        $arrCollate = array();

        $objDb = $this->_getDbObject($statusMsg);
        if ($objDb === false) {
            return $statusMsg;
        } else {
            $objCollation = $objDb->Execute('SHOW COLLATION');
            if ($objCollation !== false) {
                while (!$objCollation->EOF) {
                    if ($objCollation->fields['Charset'] == 'utf8') {
                        $arrCollate[] = $objCollation->fields['Collation'];
                    }
                    $objCollation->MoveNext();
                }

                return $arrCollate;
            } else {
                return false;
            }
        }
    }

    /**
    * get FTP object
    *
    * return an FTP connection object. if not already a connection has established, then it will do it
    *
    * @access   private
    * @param    string  $statusMsgError message
    * @global   object  $objFtp
    * @global   array   $_ARRLANG
    * @return   mixed   object $objFtp on success, false on failure
    */
    function _getFtpObject(&$statusMsg) {
        global $objFtp, $_ARRLANG;

        if (isset($objFtp)) {
            return $objFtp;
        } else {
            if (isset($_SESSION['installer']['config']['ftpPort'])) {
                $this->ftpPort = $_SESSION['installer']['config']['ftpPort'];
            }

            // open ftp conneciton
            $objFtp = @ftp_connect($_SESSION['installer']['config']['ftpHostname'], $this->ftpPort, $this->ftpTimeout);
            if ($objFtp) {
                // login to ftp server
                if (@ftp_login($objFtp, $_SESSION['installer']['config']['ftpUsername'], $_SESSION['installer']['config']['ftpPassword'])) {
                    return $objFtp;
                } else {
                    @ftp_close($objFtp);
                    unset($objFtp);
                    $statusMsg = $_ARRLANG['TXT_FTP_AUTH_FAILED']."<br />";
                }
            } else {
                unset($objFtp);
                $statusMsg = $_ARRLANG['TXT_CANNOT_CONNECT_TO_FTP_HOST']."<br />";
            }
            return false;
        }
    }

    /**
    * Close FTP connection
    *
    * Close the FTP connection of the object $objFtp
    *
    * @access   public
    * @global   object  $objFtp
    */
    function closeFtpConnection() {
        global $objFtp;

        if (isset($objFtp)) {
            @ftp_close($objFtp);
        }
    }



    function getPHPVersion() {
        return phpversion();
    }

    function checkMysqlVersion($installedMySQLVersion, $requiredVersion = null) {
        global $requiredMySQLVersion;

        $arrInstalledVersion = explode('.', $installedMySQLVersion);
        $arrRequiredVersion = explode('.', empty($requiredVersion) ? $requiredMySQLVersion : $requiredVersion);

        $maxSubVersion = count($arrInstalledVersion) > count($arrRequiredVersion) ? count($arrInstalledVersion) : count($arrRequiredVersion);
        for ($nr = 0; $nr < $maxSubVersion; $nr++) {
            if (!isset($arrRequiredVersion[$nr])) {
                return true;
            } elseif (!isset($arrInstalledVersion[$nr])) {
                return false;
            } elseif ($arrInstalledVersion[$nr] > $arrRequiredVersion[$nr]) {
                return true;
            } elseif ($arrInstalledVersion[$nr] < $arrRequiredVersion[$nr]) {
                return false;
            }
        }

        return true;
    }

    function checkMySQLSupport() {
        if (extension_loaded('mysql')) {
            return true;
        }
        return false;
    }

    function checkPDOSupport() {
        if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
            return true;
        }
        return false;
    }

    /**
    * check FTP support
    *
    * check if the ftp extension is loaded
    *
    * @access   public
    * @return   boolean
    */
    function checkFTPSupport() {
        if (extension_loaded("ftp")) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * check installation status
    *
    * check if the system is already installed
    *
    * @access   public
    * @global   string  $configFile
    * @return   boolean
    */
    function checkInstallationStatus() {
        global $configFile, $_PATHCONFIG;

        $result = @include_once'..'.$configFile;
        if ($result === false) {
            return false;
        } else {
            return (defined('CONTREXX_INSTALLED') && CONTREXX_INSTALLED);
        }
    }

    /**
    * check gd support
    *
    * check for the gd(graphics draw) extension.
    * if it is supported check also if the version is equal or higher to version 2
    *
    * @access   public
    * @return   mixed
    */
    function checkGDSupport() {
        if (extension_loaded("gd")) {
            $arrGdInfo = gd_info();

            preg_match("/[\d\.]+/", $arrGdInfo['GD Version'], $matches);
            if (!empty($matches[0])) {
                return $matches[0];
            }
        }
        return false;
    }

    public function enableApc()
    {
        if (extension_loaded('apc')) {
            if (!ini_get('apc.enabled')) {
                ini_set('apc.enabled', 1);
                if (!ini_get('apc.enabled')) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function getMemoryLimit()
    {
        preg_match('/^\d+/', ini_get('memory_limit'), $memoryLimit);
        return $memoryLimit[0];
    }

    public function checkMemoryLimit($memoryLimit)
    {
        $result = true;
        if ($this->getMemoryLimit() < $memoryLimit) {
            ini_set('memory_limit', $memoryLimit.'M');
            if ($this->getMemoryLimit() < $memoryLimit) {
                $result = false;
            }
        }

        return array(
            'result'    => $result,
            'required'  => $memoryLimit,
            'available' => $this->getMemoryLimit(),
        );
    }

    /**
    * check CMS path
    *
    * check if the cms could be found in the specified path
    *
    * @access   public
    * @global   array   $_ARRLANG
    * @global   array   $arrFiles
    * @return   mixed   true on success, $statusMsg on failure
    */
    function checkCMSPath() {
        global $_ARRLANG, $arrFiles;

        $statusMsg = "";

        if (!ini_get('safe_mode')) {
            if (!file_exists($_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'].'/index.php')) {
                return str_replace("[PATH]", $_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'], $_ARRLANG['TXT_PATH_DOES_NOT_EXIST']);
            } else {
                foreach (array_keys($arrFiles) as $file) {
                    if (!file_exists($_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'].$file)) {
                        $statusMsg .= str_replace("[FILE]", $_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'].$file, $_ARRLANG['TXT_CANNOT_FIND_FIlE']);
                    }
                }
                if (empty($statusMsg)) {
                    return true;
                } else {
                    return $statusMsg;
                }
            }
        } else {
            return true;
        }
    }

    function checkDbConnection($host, $user, $password) {
        global $_ARRLANG, $dbType, $requiredMySQLVersion, $ADODB_FETCH_MODE, $ADODB_NEWCONNECTION;

        require_once $this->adoDbPath;

        try {
            self::$pdo = new \PDO(
                'mysql:host='.$host,
                $user,
                $password
            );
        } catch (\Exception $e) {
            return $_ARRLANG['TXT_CANNOT_CONNECT_TO_DB_SERVER']."<i>&nbsp;(".$e->getMessage().")</i><br />";
        }
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $ADODB_NEWCONNECTION = 'cxupdateAdodbPdoConnectionFactory';

        $db = ADONewConnection('pdo');

        $errorNr = $db->ErrorNo();
        $arrServerInfo = $db->ServerInfo();
        $db->Close();

        if ($errorNr == 0) {
            if ($this->checkMysqlVersion($arrServerInfo['version'])) {
                return true;
            } else {
                return str_replace("[VERSION]", $requiredMySQLVersion, $_ARRLANG['TXT_MYSQL_VERSION_REQUIRED']."<br />")
                    .sprintf($_ARRLANG['TXT_MYSQL_SERVER_VERSION'], $arrServerInfo['version']);
            }
        } else {
            return $_ARRLANG['TXT_CANNOT_CONNECT_TO_DB_SERVER']."<i>&nbsp;(".$db->ErrorMsg().")</i><br />";
        }
    }

    function existDatabase($host, $user, $password, $database) {
        global $_ARRLANG, $dbType, $ADODB_FETCH_MODE, $ADODB_NEWCONNECTION;

        require_once $this->adoDbPath;

        try {
            self::$pdo = new \PDO(
                'mysql:host='.$host . ';dbname=' . $database,
                $user,
                $password
            );
        } catch (\PDOException $e) {
            return false;
        }
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $ADODB_NEWCONNECTION = 'cxupdateAdodbPdoConnectionFactory';

        $db = ADONewConnection('pdo');

        $errorNr = $db->ErrorNo();

        if ($db->IsConnected()) {
            $db->Close();
            return true;
        } else {
            return false;
        }
    }

    function checkFTPConnection() {
        global $_ARRLANG;

        $statusMsg = "";

        $objFtp = $this->_getFtpObject($statusMsg);
        if ($objFtp === false) {
            return $statusMsg;
        } else {
            return true;
        }
    }

    function checkFtpPath() {
        global $_ARRLANG, $arrFiles;

        $statusMsg = "";

        $objFtp = $this->_getFtpObject($statusMsg);
        if ($objFtp === false) {
            return $statusMsg;
        } else {
            if (empty($_SESSION['installer']['config']['ftpPath'])) {
                $_SESSION['installer']['config']['ftpPath'] = @ftp_pwd($objFtp);
            }

            if (!@ftp_chdir($objFtp, $_SESSION['installer']['config']['ftpPath'].$_SESSION['installer']['config']['offsetPath'])) {
                return $_ARRLANG['TXT_FTP_PATH_DOES_NOT_EXISTS']."<br />";
            } else {
                foreach (array_keys($arrFiles) as $file) {
                    if (!@ftp_chdir($objFtp, dirname($_SESSION['installer']['config']['ftpPath'].$_SESSION['installer']['config']['offsetPath'].$file))) {
                        $statusMsg .= str_replace("[DIRECTORY]", dirname($_SESSION['installer']['config']['ftpPath'].$_SESSION['installer']['config']['offsetPath'].$file), $_ARRLANG['TXT_DIRECTORY_ON_FTP_DOES_NOT_EXIST']."<br />");
                    } else {
                        $arrFTPFiles = $this->_getFilesOfFtpDirectory($objFtp);
                        if (!is_array($arrFTPFiles)) {
                            return $arrFTPFiles;
                        }
                        preg_match("/.*\/([\d\D]+)$/", $file, $arrMatches);
                        $checkFile = $arrMatches[1];
                        if (!is_array($arrFTPFiles) || !in_array($checkFile, $arrFTPFiles)) {
                            $statusMsg .= str_replace("[FILE]", $_SESSION['installer']['config']['ftpPath'].$_SESSION['installer']['config']['offsetPath'].$file, $_ARRLANG['TXT_FILE_ON_FTP_DOES_NOT_EXIST']."<br />");
                        }
                    }
                }
                if (empty($statusMsg)) {
                    return true;
                } else {
                    return $statusMsg;
                }
            }
        }
    }

    function _getFilesOfFtpDirectory(&$objFtp)
    {
        $arrDirectories = array();
        if (($fileList = ftp_rawlist($objFtp, ".")) !== false) {
            if (count($fileList) > 0) {
                if ($this->isWindowsFtp($objFtp)) {
                    $pcre = $this->_ftpFileWinPCRE;
                } else {
                    $pcre = $this->_ftpFileUnixPCRE;
                }

                foreach ($fileList as $fileDescription) {
                    if (preg_match($pcre, $fileDescription, $arrFile)) {
                        if ($arrFile[1] == 'l') {
                            $file = substr($arrFile[2], strpos($arrFile[2], '-> ') + 3);
                        } else {
                            $file = $arrFile[2];
                        }
                        if ($file != '.' && $file != '..') {
                            array_push($arrDirectories, $file);
                        }
                    }
                }
            }
        } else {
            return false;
        }
        return $arrDirectories;
    }

    /**
    * is windows ftp
    *
    * checks if the system of the ftp server is windows or unix
    *
    * @access public
    * @param object &$objFtp
    * @return boolean true if is windows, false if not
    */
    function isWindowsFtp(&$objFtp)
    {
        $hostType = @ftp_systype($objFtp);
        if ($hostType !== false) {
            if (preg_match('/win/i', $hostType)) {
                return true;
            } else {
                return false;
            }
        } else {
            return $this->isWindows();
        }
    }

    /**
    * is windows
    *
    * check if the system on which php is runnis is a windows system
    *
    * @access   public
    * @return   boolean
    */
    function isWindows() {
        if (substr(PHP_OS,0,3) == "WIN") {
            return true;
        } else {
            return false;
        }
    }

    function _isNewestVersion($thisVersion, $newestVersion)
    {
        $arrInstalledVersion = explode('.', $thisVersion);
        $arrNewVersion = explode('.', $newestVersion);

        $maxSubVersion = count($arrInstalledVersion) > count($arrNewVersion) ? count($arrInstalledVersion) : count($arrNewVersion);
        for ($nr = 0; $nr < $maxSubVersion; $nr++) {
            if (!isset($arrInstalledVersion[$nr])) {
                return false;
            } elseif (!isset($arrNewVersion[$nr])) {
                return true;
            } elseif ($arrNewVersion[$nr] > $arrInstalledVersion[$nr]) {
                return false;
            } elseif ($arrNewVersion[$nr] < $arrInstalledVersion[$nr]) {
                return true;
            }
        }

        return true;
    }

    /**
    * Check permisssions.
    *
    * Check if the files of the array $arrFiles are writable.
    *
    * @access  public
    * @param   array   $arrFiles
    * @return  mixed   true on success, string with error message on faiure
    */
    function checkPermissions($arrFiles) {
        global $_ARRLANG;

        $statusMessage = '';
        $path = $_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'];

        foreach ($arrFiles as $file => $arrAttributes) {
            if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($path.$file)) {
                $statusMessage = $this->setStatusMessage($path.$file, $statusMessage);
                continue;
            }

            $arrAllFiles = array();
            $arrSubDirs = array();

            if (isset($arrAttributes['sub_dirs']) && $arrAttributes['sub_dirs']) {
                $arrSubDirs = $this->_getSubDirs($file);
                $arrAllFiles = array_merge(array($file), $arrSubDirs);
            } else {
                $arrAllFiles = array($file);
            }

            foreach ($arrAllFiles as $checkFile) {
                if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($path.$checkFile)) {
                    $statusMessage = $this->setStatusMessage($path.$checkFile, $statusMessage);
                }
            }
        }

        if (empty($statusMessage)) {
            return true;
        } else {
            return $statusMessage;
        }
    }

    /**
     * Set the status message of the given directory or file.
     *
     * @param   string  $path
     * @param   string  $statusMessage
     * @return  string  $statusMessage
     */
    private function setStatusMessage($path, $statusMessage)
    {
        global $_ARRLANG;

        if ($this->isWindows()) {
            if (empty($statusMessage)) {
                $statusMessage = $_ARRLANG['TXT_SET_WRITE_PERMISSION_TO_FILES']."<br />";
            }
            $statusMessage .= $path."<br />";
        } else {
            $statusMessage .= $_ARRLANG['TXT_COULD_NOT_CHANGE_PERMISSIONS'].' '.$path."<br />";
        }

        return $statusMessage;
    }

    /**
    * get sub directories
    *
    * get sub directories of the directory $directory
    *
    * @access private
    * @param    string  $directoryDirectory to scan
    * @return   array   $arrDirectories Subdirectories
    */
    function _getSubDirs($directory) {
        $arrDirectories = array();

        $directoryPath = $_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'].$directory;

        $fp = @opendir($directoryPath);
        if ($fp) {
            while ($file = readdir($fp)) {
                $path = $directoryPath.DIRECTORY_SEPARATOR.$file;

                if ($file != "." && $file != ".." && $file != ".svn") {
                    array_push($arrDirectories, $directory.DIRECTORY_SEPARATOR.$file);

                    if (is_dir(realpath($path))) {
                        if (\Cx\Lib\FileSystem\FileSystem::makeWritable($path)) {
                            $arrDirectoriesRec = $this->_getSubDirs($directory.DIRECTORY_SEPARATOR.$file);

                            if (count($arrDirectoriesRec) > 0) {
                                $arrDirectories = array_merge($arrDirectories, $arrDirectoriesRec);
                            }
                        }
                    }
                }
            }
            closedir($fp);
        }
        return $arrDirectories;
    }

    function _getConfigFileTemplate(&$statusMsg) {
        global $configTemplateFile, $_ARRLANG, $useUtf8;

        $str = "";

        $str = @file_get_contents($configTemplateFile);

        if (empty($str)) {
            $statusMsg = str_replace("[FILENAME]", $configTemplateFile, $_ARRLANG['TXT_CANNOT_OPEN_FILE']."<br />");
        }

        //PATHS
         $str = str_replace(
             array("%PATH_ROOT%", "%PATH_ROOT_OFFSET%"),
             array($_SESSION['installer']['config']['documentRoot'], $_SESSION['installer']['config']['offsetPath']),
             $str
         );

        //MySQL
        $arrTimezones = timezone_identifiers_list();
        $str = str_replace(
            array("%DB_HOST%", "%DB_NAME%", "%DB_USER%", "%DB_PASSWORD%", "%DB_TABLE_PREFIX%", "%DB_CHARSET%", "%DB_COLLATION%", "%DB_TIMEZONE%"),
            array($_SESSION['installer']['config']['dbHostname'], $_SESSION['installer']['config']['dbDatabaseName'], $_SESSION['installer']['config']['dbUsername'], $_SESSION['installer']['config']['dbPassword'], $_SESSION['installer']['config']['dbTablePrefix'], 'utf8', $_SESSION['installer']['config']['dbCollation'], $arrTimezones[$_SESSION['installer']['config']['timezone']]),
            $str
        );

        // CHARSET
        $str = str_replace("%CHARSET%", $useUtf8 ? 'UTF-8' : 'ISO-8859-1', $str);

        // COLLATION
        $str = str_replace("%DB_COLLATION%", $_SESSION['installer']['config']['dbCollation'], $str);

        //FTP
        if ($_SESSION['installer']['config']['useFtp']) {
            $str = str_replace(
                array("%FTP_STATUS%", "%FTP_HOST%", "%FTP_PORT%", "%FTP_USER%", "%FTP_PASSWORD%", "%FTP_PATH%"),
                array("true", $_SESSION['installer']['config']['ftpHostname'], (isset($_SESSION['installer']['config']['ftpPort']) ? $_SESSION['installer']['config']['ftpPort'] : $this->ftpPort), $_SESSION['installer']['config']['ftpUsername'], $_SESSION['installer']['config']['ftpPassword'], $_SESSION['installer']['config']['ftpPath']),
                $str
            );
        } else {
            $str = str_replace(
                array("%FTP_STATUS%", "%FTP_HOST%", "%FTP_PORT%", "%FTP_USER%", "%FTP_PASSWORD%", "%FTP_PATH%"),
                array("false", "", $this->ftpPort, "", "", ""),
                $str
            );
        }

        return $str;
    }

    function _getHtaccessFileTemplate($file)
    {
        $pathOffset = $_SESSION['installer']['config']['offsetPath'];

        // in case no offset path is set (cloudrexx runs directly in the document root)
        // then, we must set pathOffset to /. Path offset is used as RewriteBase.
        // Otherwise, an empty RewriteBase would be invalid
        if (empty($pathOffset)) {
            $pathOffset = '/';
        }

        return str_replace(
            array("%PATH_ROOT_OFFSET%"),
            array($pathOffset),
            @file_get_contents($file)
        );
    }

    /**
    * get version template file
    *
    * get the version template file, set the values and return it
    *
    * @access private
    * @return string parsed version template file
    */
    function _getVersionTemplateFile() {
        global $versionTemplateFile, $_CONFIG;

        return str_replace(
            array("%CMS_NAME%","%CMS_VERSION%", "%CMS_STATUS%", "%CMS_EDITION%", "%CMS_CODE_NAME%", "%CMS_RELEASE_DATE%"),
            array($_CONFIG['coreCmsName'], $_CONFIG['coreCmsVersion'], $_CONFIG['coreCmsStatus'], $_CONFIG['coreCmsEdition'], $_CONFIG['coreCmsCodeName'], $_CONFIG['coreCmsReleaseDate']),
            @file_get_contents($versionTemplateFile)
        );
    }

    function createDatabase() {
        global $_ARRLANG, $dbType, $useUtf8, $ADODB_FETCH_MODE, $ADODB_NEWCONNECTION;

        require_once $this->adoDbPath;

        $result = "";

        self::$pdo = new \PDO(
            'mysql:host='.$_SESSION['installer']['config']['dbHostname'],
            $_SESSION['installer']['config']['dbUsername'],
            $_SESSION['installer']['config']['dbPassword']
        );
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $ADODB_NEWCONNECTION = 'cxupdateAdodbPdoConnectionFactory';

        $db = ADONewConnection('pdo');

        $arrServerInfo = $db->ServerInfo();

        $result = @$db->Execute("CREATE DATABASE `".$_SESSION['installer']['config']['dbDatabaseName']."`".($this->checkMysqlVersion($arrServerInfo['version'], '4.1.1') ? " DEFAULT CHARACTER SET ".($useUtf8 ? "utf8 COLLATE ".$_SESSION['installer']['config']['dbCollation'] : "latin1") : null));

        if ($result === false) {
            return $_ARRLANG['TXT_COULD_NOT_CREATE_DATABASE']."<br />";;
        } else {
            @$db->Close();
            return true;
        }
    }

    function setDatabaseCharset()
    {
        global $_ARRLANG, $dbType, $useUtf8, $ADODB_FETCH_MODE, $ADODB_NEWCONNECTION;

        require_once $this->adoDbPath;

        $result = "";

        self::$pdo = new \PDO(
            'mysql:host='.$_SESSION['installer']['config']['dbHostname'],
            $_SESSION['installer']['config']['dbUsername'],
            $_SESSION['installer']['config']['dbPassword']
        );
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $ADODB_NEWCONNECTION = 'cxupdateAdodbPdoConnectionFactory';

        $db = ADONewConnection('pdo');

        $result = @$db->Execute("ALTER DATABASE `".$_SESSION['installer']['config']['dbDatabaseName']."` DEFAULT CHARACTER SET utf8 COLLATE ".$_SESSION['installer']['config']['dbCollation']);

        if ($result === false) {
            return $_ARRLANG['TXT_COULD_NOT_SET_DATABASE_CHARSET']."<br />";;
        } else {
            @$db->Close();
            return true;
        }
    }

    function executeSQLQueries($type)
    {
        global $_ARRLANG, $sqlDumpFile, $dbPrefix, $arrDatabaseTables, $useUtf8;

        $sqlQuery = "";
        $statusMsg = "";
        $dbPrefixRegexp = '#`'.$dbPrefix.'('.implode('|', $arrDatabaseTables).')`#';
        if (empty($_SESSION['installer']['sqlqueries'][$type])) {
            $_SESSION['installer']['sqlqueries'][$type] = 0;
        }

        $objDb = $this->_getDbObject($statusMsg);
        if ($objDb === false) {
            return $statusMsg;
        } else {
            // insert sql dump file
            $sqlDump = $_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'].$sqlDumpFile.'_'.$type.'.sql';

            $fp = @fopen ($sqlDump, "r");
            if ($fp !== false) {
                $currentTimezone = ($objResult = $objDb->Execute('SELECT @@session.time_zone as `current_timezone`')) && $objResult && ($objResult->RecordCount() > 0) ? $objResult->fields['current_timezone'] : '';
                $this->setTimezone($this->_getDbObject($msg), 'UTC');

                $line = 1;
                while (!feof($fp)) {
                    if ($_SESSION['installer']['sqlqueries'][$type] >= $line) {
                        $line++;
                        continue;
                    }
                    $buffer = fgets($fp);
                    if ((substr($buffer,0,1) != "#") && (substr($buffer,0,2) != "--")) {
                        $sqlQuery .= $buffer;
                        if (preg_match("/;[ \t\r\n]*$/", $buffer)) {
                            $sqlQuery = preg_replace($dbPrefixRegexp, '`'.$_SESSION['installer']['config']['dbTablePrefix'].'$1`', $sqlQuery);
                            $sqlQuery = preg_replace('#CONSTRAINT(\s)*`([0-9a-z_]*)`(\s)*FOREIGN KEY#', 'CONSTRAINT FOREIGN KEY', $sqlQuery);
                            $result = @$objDb->Execute($sqlQuery);
                            if ($result === false) {
                                $statusMsg .= "<br />".htmlentities($sqlQuery, ENT_QUOTES, ($useUtf8 ? 'UTF-8' : 'ISO-8859-1'))."<br /> (".$objDb->ErrorMsg().")<br />";
                            }
                            $sqlQuery = "";
                        }
                    }
                    $_SESSION['installer']['sqlqueries'][$type] = $line;
                    $line++;
                }
                unset($_SESSION['installer']['sqlqueries'][$type]);

                $this->setTimezone($this->_getDbObject($msg), $currentTimezone);
            } else {
                return str_replace("[FILENAME]", $sqlDump, $_ARRLANG['TXT_COULD_NOT_READ_SQL_DUMP_FILE']."<br />");
            }
            if (empty($statusMsg)) {
                return true;
            } else {
                return $_ARRLANG['TXT_SQL_QUERY_ERROR'].$statusMsg;
            }
        }
    }

    function createDatabaseTables()
    {
        return $this->executeSQLQueries('structure');
    }

    function insertDatabaseData()
    {
        return $this->executeSQLQueries('data');
    }

    function checkDatabaseTables() {
        global $arrDatabaseTables, $_ARRLANG, $sqlDumpFile, $dbType, $_CONFIG;

        $statusMsg = "";
        $arrTables = array();

        $objDb = $this->_getDbObject($statusMsg);
        if ($objDb === false) {
            return $statusMsg;
        } else {
            $result = $objDb->Execute('SHOW TABLES');//$objDb->metaTablesSQL);
            if ($result) {
                while ($arrResult = $result->FetchRow()) {
                    array_push($arrTables, current($arrResult));
                }
            }
        }

        foreach ($arrDatabaseTables as $table) {
            if (!in_array($_SESSION['installer']['config']['dbTablePrefix'].$table, $arrTables)) {
                $statusMsg .= str_replace("[TABLE]", $table, $_ARRLANG['TXT_TABLE_NOT_AVAILABLE'])."<br />";
            }
        }

        if (empty($statusMsg)) {
            return true;
        } else {
            $statusMsg .= str_replace("[FILEPATH]", $_SESSION['installer']['config']['offsetPath'].str_replace(DIRECTORY_SEPARATOR, '/', $sqlDumpFile).'_structure.sql', $_ARRLANG['TXT_CREATE_DATABAES_TABLE_MANUALLY'])."<br />";
            $statusMsg .= $_ARRLANG['TXT_PRESS_REFRESH_TO_CONTINUE_INSTALLATION'];
            return $statusMsg;
        }
    }

    function createHtaccessFile() {
        global $basePath, $offsetPath, $apacheHtaccessTemplateFile, $apacheHtaccessFile, $iisHtaccessTemplateFile, $iisHtaccessFile, $_ARRLANG, $_CORELANG;

        if (!@include_once(ASCMS_LIBRARY_PATH.'/FRAMEWORK/FWHtAccess.class.php')) {
            die('Unable to load file '.ASCMS_LIBRARY_PATH.'/FRAMEWORK/FWHtAccess.class.php');
        }
        $_CORELANG = $_ARRLANG;

        if ($this->getWebserverSoftware() == 'iis') {
            require_once(ASCMS_LIBRARY_PATH.'/PEAR/File/HtAccess.php');
            $objHtAccess = new File_HtAccess(ASCMS_DOCUMENT_ROOT.$iisHtaccessFile);
            $objHtAccess->setAdditional(explode("\n", $this->_getHtaccessFileTemplate($iisHtaccessTemplateFile)));
            $result = $objHtAccess->save();
            if ($result !== true) {
                return sprintf($_ARRLANG['TXT_NO_WRITE_PERMISSION'], $iisHtaccessFile);
            }
        } else {

            $objFWHtAccess = new FWHtAccess(ASCMS_DOCUMENT_ROOT, ASCMS_PATH_OFFSET);

            $result = $objFWHtAccess->loadHtAccessFile($apacheHtaccessFile);
            if ($result !== true) {
                return $result;
            }

            $objFWHtAccess->setSection("core_routing", explode("\n", $this->_getHtaccessFileTemplate($apacheHtaccessTemplateFile)));
            $result = $objFWHtAccess->write();
            if ($result !== true) {
                return sprintf($_ARRLANG['TXT_NO_WRITE_PERMISSION'], $apacheHtaccessFile);
            }
        }

        return true;
    }

    function createConfigFile() {
        global $configFile, $_ARRLANG;

        $statusMsg = "";

        $configFileContent = $this->_getConfigFileTemplate($statusMsg);
        if (!empty($statusMsg)) {
            return $statusMsg;
        }

        $configFilePath = $_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'].$configFile;

        try {
            $objFile = new \Cx\Lib\FileSystem\File($configFilePath);
            $objFile->touch();
            $objFile->write($configFileContent);
            return true;
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }

        return sprintf($_ARRLANG['TXT_CANNOT_CREATE_FILE']."<br />", $configFilePath);
    }

    function getSystemLanguages() {
        global $dbType;

        $statusMsg = "";
        $arrLanguages = array();

        $objDb = $this->_getDbObject($statusMsg);
        if ($objDb !== false) {
            $query = "SELECT `id`, `lang`, `name`, `is_default` FROM `".$_SESSION['installer']['config']['dbTablePrefix']."languages` ORDER BY `name` DESC";
            $result = $objDb->Execute($query);
        }

        if ($result) {
            while ($arrLanguage = $result->FetchRow()) {
                array_push($arrLanguages, $arrLanguage);
            }
        }
        return $arrLanguages;
    }

    function createAdminAccount() {
        global $dbType, $arrLanguages, $language, $_ARRLANG;

        $statusMsg = "";
        $userLangId = "";

        foreach ($arrLanguages as $langId => $arrLanguage) {
            if ($language == $arrLanguage['lang']) {
                $userLangId = $langId;
                break;
            }
        }

        $objDb = $this->_getDbObject($statusMsg);
        if ($objDb !== false) {
            #$objDb->debug = true;
            $user = new \User();
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."access_users`
                         SET `username` = '".$_SESSION['installer']['account']['username']."',
                             `password` = '" . $user->hashPassword($_SESSION['installer']['account']['password']) . "',
                             `regdate` = '".time()."',
                             `email` = '".$_SESSION['installer']['account']['email']."',
                             `frontend_lang_id` = 1,
                             `backend_lang_id` = '".$userLangId."',
                             `active` = 1
                       WHERE `id` = 1";
            if ($objDb->Execute($query) !== false) {
                $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."access_user_profile`
                             SET `firstname` = '".$_SESSION['installer']['sysConfig']['adminName']."',
                                 `lastname` = ''
                           WHERE `user_id` = 1";
                if ($objDb->Execute($query) !== false) {
                    return true;
                }
            }
        }
        return $_ARRLANG['TXT_COULD_NOT_CREATE_ADMIN_ACCOUNT'];
    }

    /**
    * Validate an E-mail address
    *
    * @param  string  unvalidated email string
    * @return boolean
    * @access public
    */
    function isEmail($email)
    {
        require_once ASCMS_FRAMEWORK_PATH.'/Validator.class.php';
        return FWValidator::isEmail($email);
    }

    function isValidDbPrefix($prefix)
    {
        return preg_match('#^[a-z0-9_]+$#i', $prefix);
    }

    /**
    * set system configuration
    *
    * configure the system
    *
    * @access   public
    */
    function setSystemConfig() {
        global $_ARRLANG, $_CONFIG, $arrLanguages, $language;

        $userLangId = "";
        foreach ($arrLanguages as $langId => $arrLanguage) {
            if ($language == $arrLanguage['lang']) {
                $userLangId = $langId;
                break;
            }
        }

        $statusMsg = "";

        $objDb = $this->_getDbObject($statusMsg);
        if ($objDb === false) {
            return $statusMsg;
        } else {
            // deactivate all languages
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."languages`
                         SET `frontend` = '0', `backend` = '0', `is_default` = 'false'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_DEACTIVATE_UNUSED_LANGUAGES']."<br />";
            }

            // activate german and set it to default
            $query = '
                UPDATE `'.$_SESSION['installer']['config']['dbTablePrefix'].'languages`
                   SET `frontend` = 1, backend = 1, `is_default` = "true"
                 WHERE `lang` = "de"
            ';
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_ACTIVATE_DEFAULT_LANGUAGE']."<br />";
            }

            // activate english
            $query = 'UPDATE `'.$_SESSION['installer']['config']['dbTablePrefix'].'languages`
                         SET `frontend` = "1", `backend` = "1" WHERE `lang` = "en"';
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_ACTIVATE_CURRENT_LANGUAGE']."<br />";
            }

            // set admin email
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."settings`
                         SET `setvalue` = '".$_SESSION['installer']['sysConfig']['adminEmail']."'
                       WHERE `setname` = 'coreAdminEmail'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_ADMIN_EMAIL']."<br />";
            }

            // set admin name
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."settings`
                         SET `setvalue` = '".$_SESSION['installer']['sysConfig']['adminName']."'
                       WHERE `setname` = 'coreAdminName'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_ADMIN_NAME']."<br />";
            }

            if (($arrTables = $objDb->MetaTables('TABLES')) === false) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_GATHER_ALL_DATABASE_TABLES']."<br />";
                return $statusMsg;
            }

            // set access emails
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."access_settings`
                         SET `value` = '".$_SESSION['installer']['sysConfig']['adminEmail']."'
                       WHERE `key` = 'notification_address'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_ADMIN_EMAIL']."<br />";
            }
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."access_user_mail`
                         SET `sender_mail` = '".$_SESSION['installer']['sysConfig']['adminEmail']."'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_ADMIN_EMAIL']."<br />";
            }

            // set newsletter emails
            if (in_array($_SESSION['installer']['config']['dbTablePrefix']."module_newsletter_settings", $arrTables)) {
                $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_newsletter_settings`
                             SET `setvalue` = '".$_SESSION['installer']['sysConfig']['adminEmail']."'
                           WHERE `setname` = 'sender_mail' OR `setname` = 'reply_mail' OR `setname` = 'test_mail'";
                if (!@$objDb->Execute($query)) {
                    $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_NEWSLETTER_EMAILS']."<br />";
                }

                // set newsletter name
                $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_newsletter_settings`
                             SET `setvalue` = '".$_SESSION['installer']['sysConfig']['adminName']."'
                           WHERE `setname` = 'sender_name'";
                if (!@$objDb->Execute($query)) {
                    $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_NEWSLETTER_SENDER']."<br />";
                }
            }

            // set contact email
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."settings`
                         SET `setvalue` = '".$_SESSION['installer']['sysConfig']['contactEmail']."'
                       WHERE `setname` = 'contactFormEmail'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_CONTACT_EMAIL']."<br />";
            }

            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_contact_form`
                         SET `mails` = '".$_SESSION['installer']['sysConfig']['contactEmail']."'
                       WHERE `id` = 1";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_CONTACT_EMAIL']."<br />";
            }

            // set domain url
            if (preg_match('#^https?://#', $_SESSION['installer']['sysConfig']['domainURL'])) {
                $statusMsg .= $_ARRLANG['TXT_SET_VALID_DOMAIN_URL'];
            } else {
                if (substr($_SESSION['installer']['sysConfig']['domainURL'], -1) == '/') {
                    $_SESSION['installer']['sysConfig']['domainURL'] = substr($_SESSION['installer']['sysConfig']['domainURL'], 0, -1);
                }

                $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."settings`
                            SET `setvalue` = '".$_SESSION['installer']['sysConfig']['domainURL']."'
                            WHERE `setname` = 'domainUrl'";
                if (!@$objDb->Execute($query)) {
                    $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_DOMAIN_URL']."<br />";
                }
            }

            if (in_array($_SESSION['installer']['config']['dbTablePrefix']."module_shop_config", $arrTables)) {
                // set shop email
                $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_shop_config`
                             SET `value` = '".$_SESSION['installer']['sysConfig']['adminEmail']."'
                           WHERE `name` = 'email' OR `name` = 'confirmation_emails' OR `name` = 'paypal_account_email'";
                if (!@$objDb->Execute($query)) {
                    $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_CONTACT_EMAIL']."<br />";
                }
            }

            if (in_array($_SESSION['installer']['config']['dbTablePrefix']."module_shop_mail_content", $arrTables)) {
                // set shop email
                $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_shop_mail_content`
                             SET `from_mail` = '".$_SESSION['installer']['sysConfig']['adminEmail']."'";
                if (!@$objDb->Execute($query)) {
                    $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_CONTACT_EMAIL']."<br />";
                }
            }

            if (in_array($_SESSION['installer']['config']['dbTablePrefix'].'module_egov_products', $arrTables)) {
                $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_egov_products`
                             SET `product_target_email` = '".$_SESSION['installer']['sysConfig']['adminEmail']."'";
                if (!@$objDb->Execute($query)) {
                    $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_CONTACT_EMAIL']."<br />";
                }
            }

            $_SESSION['installer']['sysConfig']['iid'] = $this->updateCheck();

            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."settings`
                         SET `setvalue` = '".$_SESSION['installer']['sysConfig']['iid']."'
                       WHERE `setname` = 'installationId'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_INSTALLATIONID']."<br />";
            }

            $arrTimezones = timezone_identifiers_list();
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."settings`
                      SET `setvalue` = '".$arrTimezones[$_SESSION['installer']['config']['timezone']]."'
                      WHERE `setname` = 'timezone'";
            if (!@$objDb->Execute($query) || (!isset($_SESSION['installer']['config']['timezone']) && !isset($arrTimezones[$_SESSION['installer']['config']['timezone']]))) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_TIMEZONE']."<br />";
            }

            /*
            // set rss title
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_news_settings`
                         SET `value` = '".$_SESSION['installer']['sysConfig']['rssTitle']."'
                       WHERE `name` = 'news_feed_title'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_RSS_TITLE']."<br />";
            }

            // set rss description
            $query = "UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."module_news_settings`
                         SET `value` = '".$_SESSION['installer']['sysConfig']['rssDescription']."'
                       WHERE `name` = 'news_feed_description'";
            if (!@$objDb->Execute($query)) {
                $statusMsg .= $_ARRLANG['TXT_COULD_NOT_SET_RSS_DESCRIPTION']."<br />";
            }
            */
        }

        if (isset($_SESSION['installer']['config']['cachingByDefault']) && $_SESSION['installer']['config']['cachingByDefault']) {
            // configure caching
            $this->configureCaching();
        }

        if (empty($statusMsg)) {
            return $this->_createSettingsFile();
        } else {
            return $statusMsg;
        }
    }

    /**
     * Configure the Cloudrexx caching so it fits the server configuration
     */
    protected function configureCaching() {
        $_CONFIG = array();

        require_once ASCMS_CORE_MODULE_PATH . '/Cache/Controller/CacheLib.class.php';

        $isInstalled = function($cacheEngine) {
            switch ($cacheEngine) {
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC:
                    return extension_loaded('apc');
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_ZEND_OPCACHE:
                    return extension_loaded('opcache') || extension_loaded('Zend OPcache');
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHE:
                    return extension_loaded('memcache');
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHED:
                    return extension_loaded('memcached');
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE:
                    return extension_loaded('xcache');
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_FILESYSTEM:
                    return true;
            }
        };

        $isConfigured = function($cacheEngine, $user = false) {
            switch ($cacheEngine) {
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC:
                    if ($user) {
                        return ini_get('apc.serializer') == 'php';
                    }
                    return true;
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_ZEND_OPCACHE:
                    // opcache.load_comments no longer exists since PHP7
                    // therefore, ini_get() will return FALSE in case the
                    // php directive does not exist
                    return ini_get('opcache.save_comments') && (ini_get('opcache.load_comments') === false || ini_get('opcache.load_comments'));
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHE:
                    return false;
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHED:
                    return false;
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE:
                    if ($user) {
                        return (
                            ini_get('xcache.var_size') > 0 &&
                            ini_get('xcache.admin.user') &&
                            ini_get('xcache.admin.pass')
                        );
                    }
                    return ini_get('xcache.size') > 0;
                case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_FILESYSTEM:
                    return is_writable(ASCMS_DOCUMENT_ROOT . '/tmp/cache');
            }
        };

        // configure opcaches
        $configureOPCache = function() use($isInstalled, $isConfigured, &$_CONFIG) {
            // APC
            if ($isInstalled(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC) && $isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC)) {
                $_CONFIG['cacheOPCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC;
                return;
            }

            // Disable zend opcache if it is enabled
            // If save_comments is set to TRUE, doctrine2 will not work properly.
            // It is not possible to set a new value for this directive with php.
            if ($isInstalled(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_ZEND_OPCACHE)) {
                ini_set('opcache.save_comments', 1);
                ini_set('opcache.load_comments', 1);
                ini_set('opcache.enable', 1);

                if ($isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_ZEND_OPCACHE)) {
                    $_CONFIG['cacheOPCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_ZEND_OPCACHE;
                    return;
                }
            }

            // XCache
            if ($isInstalled(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE) &&
                $isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE)
            ) {
                $_CONFIG['cacheOPCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE;
                return;
            }
            return false;
        };

        // configure user caches
        $configureUserCache = function() use($isInstalled, $isConfigured, &$_CONFIG) {
            // APC
            if ($isInstalled(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC)) {
                // have to use serializer "php", not "default" due to doctrine2 gedmo tree repository
                ini_set('apc.serializer', 'php');
                if ($isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC, true)) {
                    $_CONFIG['cacheUserCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_APC;
                    return;
                }
            }

            // Memcache
            if ($isInstalled(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHE) && $isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHE)) {
                $_CONFIG['cacheUserCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHE;
                return;
            }

            // Memcached
            if ($isInstalled(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHED) && $isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHED)) {
                $_CONFIG['cacheUserCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHED;
                return;
            }

            // XCache
            if (
                $isInstalled(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE) &&
                $isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE, true)
            ) {
                $_CONFIG['cacheUserCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_XCACHE;
                return;
            }

            // Filesystem
            if ($isConfigured(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_FILESYSTEM)) {
                $_CONFIG['cacheUserCache'] = \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_FILESYSTEM;
                return;
            }
            return false;
        };

        if ($configureOPCache() === false) {
            $_CONFIG['cacheOpStatus'] = 'off';
        } else {
            $_CONFIG['cacheOpStatus'] = 'on';
        }

        if ($configureUserCache() === false) {
            $_CONFIG['cacheDbStatus'] = 'off';
        } else {
            $_CONFIG['cacheDbStatus'] = 'on';
        }

        $objDb = $this->_getDbObject($statusMsg);
        foreach ($_CONFIG as $key => $value) {
            $objDb->Execute("UPDATE `".$_SESSION['installer']['config']['dbTablePrefix']."settings` SET `setvalue` = '".$value."' WHERE `setname` = '".$key."'");
        }
    }

    /**
     * Write all settings into the config-file
     *
     */
    function _createSettingsFile()
    {
        global $_ARRLANG;

        $objDb = $this->_getDbObject($statusMsg);
        if ($objDb === false) {
            return $statusMsg;
        } else {
            $strSettingsFile = $_SESSION['installer']['config']['documentRoot'].$_SESSION['installer']['config']['offsetPath'].'/config/settings.php';

            if (   !\Cx\Lib\FileSystem\FileSystem::touch($strSettingsFile)
                || !\Cx\Lib\FileSystem\FileSystem::makeWritable($strSettingsFile)
            ) {
                return sprintf($_ARRLANG['TXT_SETTINGS_ERROR_WRITABLE'], $strSettingsFile);
            }

            //Header & Footer
                $strHeader  = "<?php\n";
                $strHeader .= "/**\n";
                $strHeader .= "* This file is generated by the \"settings\"-menu in your CMS.\n";
                $strHeader .= "* Do not try to edit it manually!\n";
                $strHeader .= "*/\n\n";

                $strFooter = "\n";

            //Get module-names
                $objResult = $objDb->Execute("SELECT id, name FROM `".$_SESSION['installer']['config']['dbTablePrefix']."modules`");
                if ($objResult->RecordCount() > 0) {
                    while (!$objResult->EOF) {
                        $arrModules[$objResult->fields['id']] = $objResult->fields['name'];
                        $objResult->MoveNext();
                    }
                }

            //Get values
                $objResult = $objDb->Execute("SELECT setname, setmodule, setvalue FROM `".$_SESSION['installer']['config']['dbTablePrefix']."settings` ORDER BY    setmodule ASC, setname ASC");
                $intMaxLen = 0;
                if ($objResult->RecordCount() > 0) {
                    while (!$objResult->EOF) {
                        $intMaxLen = (strlen($objResult->fields['setname']) > $intMaxLen) ? strlen($objResult->fields['setname']) : $intMaxLen;
                        $arrValues[$objResult->fields['setmodule']][$objResult->fields['setname']] = $objResult->fields['setvalue'];
                        $objResult->MoveNext();
                    }
                }
                $intMaxLen += strlen('$_CONFIG[\'\']') + 1; //needed for formatted output

            //Write values
                $data = $strHeader;

                $strBody = '';
                foreach ($arrValues as $intModule => $arrInner) {
                    $strBody .= "/**\n";
                    $strBody .= "* -------------------------------------------------------------------------\n";
                    $strBody .= "* ".ucfirst(isset($arrModules[$intModule]) ? $arrModules[$intModule] : '')."\n";
                    $strBody .= "* -------------------------------------------------------------------------\n";
                    $strBody .= "*/\n";

                    foreach($arrInner as $strName => $strValue) {
                        $strBody .= sprintf("%-".$intMaxLen."s",'$_CONFIG[\''.$strName.'\']');
                        $strBody .= "= ";
                        $strBody .= (is_numeric($strValue) ? $strValue : '"'.str_replace('"', '\"', $strValue).'"').";\n";
                    }
                    $strBody .= "\n";
                }

                $data .= $strBody;
                $data .= $strFooter;

                try {
                    $objFile = new \Cx\Lib\FileSystem\File($strSettingsFile);
                    $objFile->write($data);
                    return true;
                } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                    DBG::msg($e->getMessage());
                }

                return false;
        }
    }

    function _checkOpenbaseDirConfig()
    {
        global $_ARRLANG;

        $openbasedir = @ini_get('open_basedir');
        if (!empty($openbasedir)) {
            if ($this->isWindows()) {
                return true;
            } else {
                if (count(preg_grep('#^/tmp$#', array_map('trim', explode(':', $openbasedir))))) {
                    return true;
                } else {
                    return $_ARRLANG['TXT_OPEN_BASEDIR_TMP_MISSING'];
                }
            }
        } else {
            return true;
        }
    }

    function getFtpDirectoryTree($path) {
        $statusMsg = "";
        $arrDirectories = array();
        $directoryPath = "";

        $arrPaths = explode("/", $path);

        $objFtp = $this->_getFtpObject($statusMsg);
        if ($objFtp === false) {
            return $statusMsg;
        } else {
            for ($directoryId = 0; $directoryId < count($arrPaths); $directoryId++) {
                $arrDirectories[$directoryId] = array();
                $arrDirectoryTree = array();

                if ($directoryId != 0) {
                    @ftp_chdir($objFtp, $arrPaths[$directoryId]);
                }
                $directoryPath .= $arrPaths[$directoryId].'/';

                $arrDirectoryTree = $this->_getFilesOfFtpDirectory($objFtp);
                if (!is_array($arrDirectoryTree)) {
                    return $arrDirectoryTree;
                }

                foreach ($arrDirectoryTree as $file) {
                    if (@ftp_chdir($objFtp, $file)) {
                        $arrDirectory = array(
                            'name'  => $file,
                            'path'  => $directoryPath,
                            'style' => "padding-left: ".($directoryId*15)."px;"
                        );
                        array_push($arrDirectories[$directoryId], $arrDirectory);
                        @ftp_chdir($objFtp, '..');
                    }
                }
            }
        }
        return $arrDirectories;
    }

    public function getWebserverSoftware()
    {
        return !empty($_SERVER['SERVER_SOFTWARE']) && stristr($_SERVER['SERVER_SOFTWARE'], 'apache') ? 'apache' : (stristr($_SERVER['SERVER_SOFTWARE'], 'iis') ? 'iis' : '');;
    }

    function updateCheck() {
        global $_CONFIG, $objDb;

        $version = "";
        $ip = "";
        $serverName = "";
        $iid = "";

        if (!isset($_SESSION['installer']['updateCheck']) || !$_SESSION['installer']['updateCheck']) {
            if (isset($_SESSION['installer']['sysConfig']['domainURL'])) {
                $serverName = $_SESSION['installer']['sysConfig']['domainURL'];
            }
            else if (isset($_SERVER['SERVER_NAME'])) {
                $serverName = $_SERVER['SERVER_NAME'];
            }
            if (isset($_SERVER['SERVER_ADDR'])) {
                $ip = $_SERVER['SERVER_ADDR'];
            }

            $v = $_CONFIG['coreCmsVersion'] . $_CONFIG['coreCmsStatus'];
            $url = base64_decode('d3d3LmNvbnRyZXh4LmNvbQ==');
            $file = base64_decode("L3VwZGF0ZWNlbnRlci9pbmRleC5waHA=").'?host='.$serverName.$_SESSION['installer']['config']['offsetPath'].'&ip='.$ip.'&version='.$v.'&edition='.$_CONFIG['coreCmsEdition'];
            require_once(ASCMS_LIBRARY_PATH.'/PEAR/HTTP/Request2.php');
            $request = new \HTTP_Request2('http://'.$url.$file, \HTTP_Request2::METHOD_GET);
            try {
                $objResponse = $request->send();
                if ($objResponse->getStatus() == 200) {
                    $iid = $objResponse->getBody();
                    $_SESSION['installer']['updateCheck'] = true;
                } else {
                    $_SESSION['installer']['updateCheckImage']="<img src='".$url."' width='1' height='1' />";
                }
            } catch (HTTP_Request2_Exception $objException) {
                $_SESSION['installer']['updateCheckImage']="<img src='".$url."' width='1' height='1' />";
            }
            return $iid;
        }
    }

    function getNewestVersion() {
        $xml_parser = @xml_parser_create();
        @xml_set_object($xml_parser,$this);
        @xml_set_element_handler($xml_parser,"_xmlVersionStartTag","_xmlVersionEndTag");
        @xml_set_character_data_handler($xml_parser, "_xmlVersionCharacterData");
        @xml_parse($xml_parser,file_get_contents(base64_decode('aHR0cDovL3d3dy5jb250cmV4eC5jb20vdXBkYXRlY2VudGVyL3ZlcnNpb24ueG1s')));
        return $this->newestVersion;
    }

    function _xmlVersionStartTag($parser,$name,$attrs) {
        global $xmlVersionTag;

        $xmlVersionTag = $name;
    }

    function _xmlVersionCharacterData($parser, $data) {
        global $xmlVersionTag;

        if (empty($this->newestVersion) && $xmlVersionTag == "VERSION") {
            $this->newestVersion = $data;
        }
    }

    function _xmlVersionEndTag($parser,$name) {
    }
}
