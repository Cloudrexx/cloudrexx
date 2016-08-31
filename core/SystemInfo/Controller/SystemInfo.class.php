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
 * Settings
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  core_systeminfo
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core\SystemInfo\Controller;

/**
 * Settings
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  core_systeminfo
 */
class SystemInfo
{
    var $pageTitle="";
    var $statusMessage="";

    var $conn_id;                           // current Connections ID
    var $login_result;                       // current Login

    var $ftp_is_activated;                 // FTP is activated ( true/false )
    var $ftpHost;                          // FTP Host
    var $ftpUserName;                      // FTP User Name
    var $ftpUserPass;                      // FTP Password
    var $ftpDirectory;                     // FTP start directory (htdocs)

    var $saveMode;                           // save_mode is true/false
    var $is_php5;

    var $ftpConnection;
    var $ftpSupport;

    private $act = '';

    /**
     * Constructor
     * @access public
     */
    function __construct() {}

    public function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION', '
            <a href="index.php?cmd=SystemInfo" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_OVERVIEW'].'</a>
            <a href="index.php?cmd=SystemInfo&amp;act=phpinfo" class="'.($this->act == 'phpinfo' ? 'active' : '').'">'.$_ARRAYLANG['TXT_PHP_INFO'].'</a>
            <a href="index.php?cmd=NetTools&amp;tpl=whois" class="'.($this->act == 'whois' ? 'active' : '').'">'.$_ARRAYLANG['TXT_CORE_WHOIS'].'</a>
            <a href="index.php?cmd=NetTools&amp;tpl=lookup" class="'.($this->act == 'lookup' ? 'active' : '').'">'.$_ARRAYLANG['TXT_LOOKUP'].'</a>
            <a href="index.php?cmd=NetTools&amp;tpl=mxlookup" class="'.($this->act == 'mxlookup' ? 'active' : '').'">'.$_ARRAYLANG['TXT_MX_LOOKUP'].'</a>'.
            (!ini_get("safe_mode") ? '<a href="index.php?cmd=NetTools&amp;tpl=ping" class="'.($this->act == 'ping' ? 'active' : '').'">'.$_ARRAYLANG['TXT_PING'].'</a>' : '').'
            <a href="index.php?cmd=NetTools&amp;tpl=port" class="'.($this->act == 'port' ? 'active' : '').'">'.$_ARRAYLANG['TXT_CHECK_PORT'].'</a>
        ');
    }

    /**
     * Sets the requested content
     *
     * @global  array   Core language
     * @global  mixed   Template
     * @return  void
     */
    function getPage() {
        global $_ARRAYLANG, $objTemplate;

        if(!isset($_GET['act'])) {
            $_GET['act']="";
        }

        switch($_GET['act']) {
            case "phpinfo":
                $objTemplate->addBlockfile('ADMIN_CONTENT', 'server_phpinfo', 'server_phpinfo.html');
                $this->pageTitle = $_ARRAYLANG['TXT_PHP_INFO'];
                $this->showPHPInfo();
            break;

            case "ftp":
                $this->ftpSettings();
            break;

            default:
                $objTemplate->addBlockfile('ADMIN_CONTENT', 'server_settings', 'server_settings.html');
                $this->pageTitle = $_ARRAYLANG['TXT_OVERVIEW'];
                $this->showServerInfo();
            break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'    => $this->pageTitle,
            'CONTENT_STATUS_MESSAGE'    => $this->statusMessage,
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    /**
     * Set the server information
     */
    function showServerInfo() {
        global $_CONFIG, $objTemplate;

        $objTemplate->setVariable(array(
            'ADMIN_CMS_NAME'           => htmlentities($_CONFIG['coreCmsName'], ENT_QUOTES, CONTREXX_CHARSET),
            'ADMIN_CMS_VERSION'        => str_replace(' Service Pack 0', '', preg_replace('#^(\d+\.\d+)\.(\d+)$#', '$1 Service Pack $2', $_CONFIG['coreCmsVersion'])),
            'MYADMIN_DB_VERSION'       => \Env::get('cx')->getDb()->getPdoConnection()->getAttribute(\PDO::ATTR_SERVER_VERSION),
            'MYADMIN_PHP_VERSION'      => @phpversion(),
            'MYADMIN_WEBSERVER'        => $this->getServerSoftware(),
            'MYADMIN_PHP_BUILDON'      => @php_uname(),
            'MYADMIN_PHP_INTERFACE'    => @php_sapi_name(),
            'MYADMIN_ZEND_VERSION'     => @zend_version()
        ));
    }


    /**
     * Sets information about the PHP version in use
     * @return void
     */
    function showPHPInfo() {
        global $objTemplate;
        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
        $phpinfo = ob_get_contents();
        ob_end_clean();
        preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpinfo, $output);
        $output = preg_replace('#<table#', '<table class="adminlist"', $output[1][0]);
        $output = preg_replace('#(\w),(\w)#', '\1, \2', $output);
        //$output = preg_replace('#border="0" cellpadding="3" width="600"#', 'border="0" cellspacing="0" cellpadding="4" width="600"', $output);
        $output = preg_replace('#<hr />#', '', $output);
        $objTemplate->setVariable('SERVER_PHPINFO',$output);
    }


    /**
     * Sets information about the server
     * @return string  Server information
     */
    function getServerSoftware()
    {
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            return $_SERVER['SERVER_SOFTWARE'];
        } else if (($sf = getenv('SERVER_SOFTWARE'))) {
            return $sf;
        } else {
            return 'n/a';
        }
    }

    /**
     * Sets information about the FTP Configuration
     * @access   public
     * @global  array   Configuration
     * @global  array   Core language
     * @global  array   FTP configuration
     * @global  mixed   Template
     */
    function ftpSettings()
    {
        global $_FTPCONFIG, $objTemplate;

        $this->ftp_is_activated = $_FTPCONFIG['is_activated'];
        $this->ftpHost = $_FTPCONFIG['host'];
        $this->ftpUserName = $_FTPCONFIG['username'];
        $this->ftpUserPass = $_FTPCONFIG['password'];
        $this->ftpDirectory = $_FTPCONFIG['path'];
        $this->saveMode = @ini_get('safe_mode');
        $this->is_php5 = (phpversion()<5) ? false : true;

        //crate baseconnection
        $this->conn_id = @ftp_connect($this->ftpHost);

        //logon with user and password
        $this->login_result = @ftp_login($this->conn_id, $this->ftpUserName, $this->ftpUserPass);

        $this->checkSettings();

        if($this->ftp_is_activated == true){
            $is_activated = "activated";
        }else{
            $is_activated = "not activated";
        }

        // initialize variables
        $objTemplate->addBlockfile('ADMIN_CONTENT', 'checkFTP_settings', 'checkFTP_settings.html');
        $this->pageTitle = "FTP Einstellungen";
        $objTemplate->setVariable(array(
            'TXT_FTP_SETTINGS'           =>         "FTP Einstellungen (config)",
            'TXT_FTP_CHECK'               =>         "FTP Prüfen",
            'TXT_FTP_IS_ACTIVATED'      =>         "Aktiviert",
            'TXT_FTP_HOST'               =>        "Host",
            'TXT_FTP_USER'               =>        "Username",
            'TXT_FTP_PASSWORD'           =>        "Passwort",
            'TXT_FTP_PATH'               =>        "Pfad",
            'FTP_IS_ACTIVATED'          =>         $is_activated,
            'FTP_HOST'                   =>        $this->ftpHost,
            'FTP_USER'                   =>        $this->ftpUserName,
            'FTP_PATH'                   =>        $this->ftpDirectory,
            'TXT_WEBSERVER'               =>         "Webserver",
            'WEBSERVER'                   =>         $this->getServerSoftware(),
            'TXT_FTP_CONNECTION'           =>         "Verbindung",
            'FTP_CONNECTION'               =>         $this->ftpConnection,
            'TXT_SAVE_MODE'               =>         "SaveMode",
            'SAVE_MODE'                   =>         $this->saveMode
        ));

        $structure = $this->getServerStructure(".");

        /*print_r("<pre>");
        print_r($structure);
        print_r("</pre>");*/

        $objTemplate->setVariable('FTP_SERVER_ROOT',"root");

        foreach($structure as $dirKey => $dirName){
            $i++;
            $class = ($i % 2) ? 'row2' : 'row1';
            $array = explode(" ",$dirName);
            $x = count($array)-1;
            $dirName = $array[$x];

            if($dirName != ".." && $dirName != "."){
                if(is_dir($dirName) == true) {
                    echo $dirName;
                }

                $structure2 = $this->getServerStructure($dirName);

                $objTemplate->setVariable(array(
                    'FTP_ROW_CLASS'    => $class,
                    'FTP_ROW_TEST'=> $dirName,
                    'FTP_ROW_KEY' => $dirKey
                    ));
                $objTemplate->parse("importDirectoryTree");
            }
        }
    }


    /**
     * Check FTP settings and correct the saveMode setting
     * @todo What is the 'saveMode' field good for?
     */
    function checkSettings()
    {
        //check connection
        if ((!$this->conn_id) || (!$this->login_result)) {
            $this->ftpConnection = "Konnte keine Verbindung herstellen";
            //$this->ftpSupport = "FTP wird nicht unterstützt";
        } else {
            $this->ftpConnection = "Verbindung konnte erfolgreich hergestellt werden";
            //$this->ftpSupport = "FTP wird unterstützt";
        }

        if($this->saveMode == true){
            $this->saveMode = "on";
        } else {
            $this->saveMode = "off";
        }
    }


    /**
     * Returns the raw directory contents
     * @param   string  $dir    Directory
     * @return  string          Directory contents
     */
    function getServerStructure($dir)
    {
        return ftp_rawlist($this->conn_id, $dir);
    }
}
