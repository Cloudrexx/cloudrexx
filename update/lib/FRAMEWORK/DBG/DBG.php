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
 * Debugging
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      David Vogt <david.vogt@comvation.com>
 * @version     3.0.0
 * @since       2.1.3
 * @package     cloudrexx
 * @subpackage  lib_dbg
 */

// Basic flags
define('DBG_NONE',              0);
define('DBG_PHP',               1<<0);
define('DBG_ADODB',             1<<1);
define('DBG_ADODB_TRACE',       1<<2);
define('DBG_ADODB_CHANGE',      1<<3);
define('DBG_ADODB_ERROR',       1<<4);
define('DBG_DOCTRINE',          1<<5);
define('DBG_DOCTRINE_TRACE',    1<<6);
define('DBG_DOCTRINE_CHANGE',   1<<7);
define('DBG_DOCTRINE_ERROR',    1<<8);
define('DBG_DB',                DBG_ADODB | DBG_DOCTRINE);
define('DBG_DB_TRACE',          DBG_ADODB_TRACE | DBG_DOCTRINE_TRACE);
define('DBG_DB_CHANGE',         DBG_ADODB_CHANGE | DBG_DOCTRINE_CHANGE);
define('DBG_DB_ERROR',          DBG_ADODB_ERROR | DBG_DOCTRINE_ERROR);
define('DBG_LOG_FILE',          1<<9);
define('DBG_LOG_FIREPHP',       1<<10);
define('DBG_LOG',               1<<11);
// Full debugging (quite pointless really)
define('DBG_ALL',
      DBG_PHP
    | DBG_DB | DBG_DB_TRACE | DBG_DB_ERROR | DBG_DB_CHANGE
    | DBG_LOG_FILE | DBG_LOG_FIREPHP
    | DBG_LOG);
// Common debugging modes (add more as required)
define('DBG_ERROR_FIREPHP',
      DBG_PHP | DBG_DB_ERROR | DBG_LOG_FIREPHP);
define('DBG_DB_FIREPHP',
      DBG_PHP | DBG_DB | DBG_LOG_FIREPHP);

DBG::deactivate();

/**
 * Debugging
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      David Vogt <david.vogt@comvation.com>
 * @version     3.0.0
 * @since       2.1.3
 * @package     cloudrexx
 * @subpackage    lib_dbg
 */
class DBG
{
    private static $dbg_fh = null;
    private static $fileskiplength = 0;
    private static $enable_msg   = null;
    private static $enable_trace = null;
    private static $enable_dump  = null;
    private static $enable_time  = null;
    private static $firephp      = null;
    private static $log_file     = null;
    private static $log_firephp  = null;
    private static $log_adodb    = null;
    private static $log_php      = 0;
    private static $last_time    = null;
    private static $start_time   = null;
    private static $mode         = 0;
    private static $sql_query_cache = null;


    public function __construct()
    {
        throw new Exception('This is a static class! No need to create an object!');
    }


    /**
     * Activates debugging according to the bits given in $mode
     *
     * See the constants defined early in this file.
     * An empty $mode defaults to
     *  DBG_ALL & ~DBG_LOG_FILE & ~DBG_LOG_FIREPHP
     * @param   integer     $mode       The optional debugging mode bits
     */
    public static function activate($mode = null)
    {
        if (!self::$fileskiplength) {
            self::$fileskiplength = strlen(dirname(dirname(dirname(dirname(__FILE__))))) + 1;
        }
        if ($mode === DBG_NONE) {
            self::$mode = DBG_NONE;
        } elseif ($mode === null) {
            self::$mode = (DBG_ALL & ~DBG_LOG_FILE & ~DBG_LOG_FIREPHP) | DBG_LOG;
        } else {
            self::$mode = self::$mode | $mode | DBG_LOG;
        }
        self::__internal__setup();
    }


    /**
     * Deactivates debugging according to the bits given in $mode
     *
     * See the constants defined early in this file.
     * An empty $mode defaults to DBG_ALL, thus disabling debugging completely
     * @param   integer     $mode       The optional debugging mode bits
     */
    public static function deactivate($mode = null)
    {
        if (empty($mode)) {
            self::$mode = DBG_NONE;
        } else {
            self::$mode = self::$mode  & ~$mode;
        }
        self::__internal__setup();
    }


    /**
     * Set up debugging
     *
     * Called by both {@see activate()} and {@see deactivate()}
     */
    public static function __internal__setup()
    {
        // log to file dbg.log
        if (self::$mode & DBG_LOG_FILE) {
            self::enable_file();
        } else {
            self::disable_file();
        }
        // log to FirePHP
        if (self::$mode & DBG_LOG_FIREPHP) {
            self::enable_firephp();
        } else {
            self::disable_firephp();
        }
        // log mysql queries
        if ((self::$mode & DBG_ADODB) || (self::$mode & DBG_ADODB_TRACE) || (self::$mode & DBG_ADODB_CHANGE) || (self::$mode & DBG_ADODB_ERROR)) {
            self::enable_adodb();
        } else {
            self::disable_adodb_debug();
        }
        // log doctrine sql queries
        if (self::$mode & DBG_DOCTRINE || (self::$mode & DBG_DOCTRINE_TRACE) || (self::$mode & DBG_DOCTRINE_CHANGE) || (self::$mode & DBG_DOCTRINE_ERROR)) {
            // No need to do anything here. \Cx\Lib\DBG\DoctrineSQLLogger handles this using \DBG::getMode()
        } else {
            // No need to do anything here. \Cx\Lib\DBG\DoctrineSQLLogger handles this using \DBG::getMode()
        }
        // log php warnings/erros/notices...
        if (self::$mode & DBG_PHP) {
            self::enable_error_reporting();
        } else {
            self::disable_error_reporting();
        }
        // output log messages
        if (self::$mode & DBG_LOG) {
            self::enable_all();
        } else {
            self::disable_all();
        }
    }


    /**
     * Returns the current debugging mode bits
     * @return  integer         The debugging mode bits
     */
    public static function getMode()
    {
        return self::$mode;
    }


    /**
     * Enables logging to a file
     *
     * Disables logging to FirePHP in turn.
     */
    private static function enable_file()
    {
        if (self::$log_file) return;
        // disable firephp first
        self::disable_firephp();
// DO NOT OVERRIDE DEFAULT BEHAVIOR FROM INSIDE THE CLASS!
// Call a method to do this from the outside.
//        self::setup('dbg.log', 'w');
        if (self::setup('dbg.log')) {
            self::$log_file = true;
        }
    }


    /**
     * Disables logging to a file
     */
    private static function disable_file()
    {
        if (!self::$log_file) return;
        self::$log_file = false;
        self::$dbg_fh = null;
        restore_error_handler();
    }


    /**
     * Enables logging to FirePHP
     *
     * Disables logging to a file in turn.
     */
    private static function enable_firephp()
    {
        if (self::$log_firephp) return;
        $file = $line = '';
        if (headers_sent($file, $line)) {
            trigger_error("Can't activate FirePHP! Headers already sent in $file on line $line'", E_USER_NOTICE);
            return;
        }
        // FirePHP overrides file logging
        self::disable_file();
        ob_start();
        if (!isset(self::$firephp)) {
            if (!include_once(dirname(dirname(dirname(__FILE__))).'/firephp/FirePHP.class.php')) {
                return;
            }
            self::$firephp = FirePHP::getInstance(true);
        }
        self::$firephp->registerErrorHandler(false);
        self::$firephp->setEnabled(true);
        self::$log_firephp = true;
    }


    /**
     * Disables logging to FirePHP
     */
    private static function disable_firephp()
    {
        if (!self::$log_firephp) return;
        self::$firephp->setEnabled(false);
        self::$log_firephp = false;
        ob_end_clean();
        restore_error_handler();
    }


    static function enable_all()
    {
        self::enable_msg  ();
        self::enable_trace();
        self::enable_dump ();
        self::enable_time ();
    }


    static function disable_all()
    {
        self::disable_msg();
        self::disable_trace();
        self::disable_dump();
        self::disable_time();
    }


    static function time($comment = '')
    {
        if (self::$enable_time) {
            $t = self::$last_time;
            self::$last_time = microtime(true);
            $diff_last  = round(self::$last_time - $t, 5);
            $diff_start = round(self::$last_time - self::$start_time, 5);
            $callers = debug_backtrace();
            $f = self::_cleanfile($callers[0]['file']);
            $l = $callers[0]['line'];
            $d = date('H:i:s');
            self::_log("TIME AT: $f:$l $d (diff: $diff_last, startdiff: $diff_start)".(!empty($comment) ? ' -- '.$comment : ''), 'info');
        }
    }


    /**
     * Sets up logging to a file
     *
     * On each successive call, this will close the current log file handle
     * if already open.
     * If logging to FirePHP is enabled, it will then return true.
     * Otherwise, the log file will be opened using the current parameter
     * values
     * @param   string  $file   The file name
     * @param   string  $mode   The access mode (as with {@see fopen()})
     * @return  boolean         True
     * @todo    The result of calling fopen should be verified and be
     *          reflected in the return value
     */
    static function setup($file, $mode='a')
    {
        if (self::$log_firephp) return true; //no need to setup ressources, we're using firephp
        $suffix = '';
        /*$nr = 0;
        while (file_exists($file.$suffix)) {
            $suffix = '.'.++$nr;
        }*/
        if ($file == 'php://output') {
            self::$dbg_fh = fopen($file, $mode);
            if (self::$dbg_fh) {
                return true;
            } else {
                return false;
            }
        } elseif (class_exists('\Cx\Lib\FileSystem\File')) {
            try {
                self::$dbg_fh = new \Cx\Lib\FileSystem\File(ASCMS_DOCUMENT_ROOT.'/update/'.$file.$suffix);
                self::$dbg_fh->forceAccessMode(\Cx\Lib\FileSystem\File::PHP_ACCESS);
                self::$dbg_fh->touch();
                if (self::$dbg_fh->makeWritable()) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                return false;
            }
        } else {
            self::$dbg_fh = fopen(ASCMS_DOCUMENT_ROOT.'/update/'.$file.$suffix, $mode);
            if (self::$dbg_fh) {
                return true;
            } else {
                return false;
            }
        }
    }


    static function enable_trace()
    {
        if (self::$enable_trace) return;
        //self::_log('--- ENABLING TRACE');
        self::$enable_trace = 1;
    }

    static function set_adodb_debug_mode()
    {
        if (self::getMode() & DBG_ADODB_TRACE) {
            self::enable_adodb_debug(true);
        } elseif (self::getMode() & DBG_ADODB || self::getMode() & DBG_ADODB_ERROR) {
            self::enable_adodb_debug();
        } else {
            self::disable_adodb_debug();
        }
    }

    // Redirect ADODB output to us instead of STDOUT.
    static function enable_adodb()
    {
        if (!self::$log_adodb) {
            if (!(self::$mode & DBG_LOG_FILE)) self::setup('php://output');
            if (!defined('ADODB_OUTP')) define('ADODB_OUTP', 'DBG_log_adodb');
            self::$log_adodb = true;
        }
        self::enable_adodb_debug();
    }


    static function enable_adodb_debug($flagTrace=false)
    {
        global $objDatabase;

        if (!isset($objDatabase)) return;

        $objDatabase->debug = 1;
    }


    static function disable_adodb_debug()
    {
        global $objDatabase;

        if (!isset($objDatabase)) return;
        $objDatabase->debug = 0;
        self::$log_adodb = false;
    }

    static function disable_trace()
    {
        if (!self::$enable_trace) return;
        //self::_log('--- DISABLING TRACE');
        self::$enable_trace = 0;
    }


    static function enable_time()
    {
        if (!self::$enable_time) {
            //self::_log('--- ENABLING TIME');
            self::$enable_time = 1;
            self::$start_time = microtime(true);
            self::$last_time  = microtime(true);
        }
    }


    static function disable_time()
    {
        if (!self::$enable_time) return;
        //self::_log('--- DISABLING TIME');
        self::$enable_time = 0;
    }


    static function enable_dump()
    {
        if (self::$enable_dump) return;
        //self::_log('--- ENABLING DUMP');
        self::$enable_dump = 1;
    }


    static function disable_dump()
    {
        if (!self::$enable_dump) return;
        //self::_log('--- DISABLING DUMP');
        self::$enable_dump = 0;
    }


    static function enable_msg()
    {
        if (self::$enable_msg) return;
        //self::_log('--- ENABLING MSG');
        self::$enable_msg = 1;
    }


    static function disable_msg()
    {
        if (!self::$enable_msg) return;
        //self::_log('--- DISABLING MSG');
        self::$enable_msg = 0;
    }


    static function enable_error_reporting()
    {
        self::$log_php =
            E_ALL
// Suppress all deprecated warnings
// (disable this line and fix all warnings before release!)
//          & ~E_DEPRECATED
// Enable strict warnings
// (enable this line and fix all warnings before release!)
          | E_STRICT
        ;
        error_reporting(self::$log_php);
        ini_set('display_errors', 1);
        if (!self::$firephp) {
            set_error_handler('DBG::phpErrorHandler');
        } else {
            self::$firephp->setPHPLogging(self::$log_php);
        }
    }


    static function disable_error_reporting()
    {
        self::$log_php = 0;
        error_reporting(0);
        ini_set('display_errors', 0);

        if (self::$firephp) {
            self::$firephp->setPHPLogging(self::$log_php);
        }
    }


    static function _cleanfile($f)
    {
        return substr($f, self::$fileskiplength);
    }


    static function trace($level=0)
    {
        if (self::$enable_trace) {
            $callers = debug_backtrace();
            $f = self::_cleanfile($callers[$level]['file']);
            $l = $callers[$level]['line'];
            self::_log("TRACE:  $f : $l");
        }
    }


    static function calltrace()
    {
        if (self::$enable_trace) {
            $level = 1;
            $callers = debug_backtrace();
            $c = isset($callers[$level]['class']) ? $callers[$level]['class'] : null;
            $f = $callers[$level]['function'];
            self::trace($level);
            $sf = self::_cleanfile($callers[$level]['file']);
            $sl = $callers[$level]['line'];
            self::_log("        ".(empty($c) ? $f : "$c::$f")." FROM $sf : $sl");
        }
    }


    static function dump($val)
    {
        if (!self::$enable_dump) return;

        self::_escapeDoctrineDump($val);

        if (self::$log_firephp) {
            self::$firephp->log($val);
            return;
        }
        if ($val === null) {
            $out = 'NULL';
        } else {
            $out = var_export($val, true);
        }
        $out = str_replace("\n", "\n        ", $out);
        if (!self::$log_file) {
            // we're logging directly to the browser
            // can't use contrexx_raw2xhtml() here, because it might not
            // have been loaded till now
            self::_log('DUMP:   <p><pre>'.htmlentities($out, ENT_QUOTES, CONTREXX_CHARSET).'</pre></p>');
        } else {
            self::_log('DUMP:   '.$out);
        }
    }

    private static function _escapeDoctrineDump(&$val)
    {
        if ($val instanceof \Cx\Model\Base\EntityBase) {
            $val = \Doctrine\Common\Util\Debug::export($val, 2);
        } else if (is_array($val)) {
            foreach ($val as $entry) {
                self::_escapeDoctrineDump($entry);
            }
        }
    }

    static function stack()
    {
        if (self::$enable_trace) {
            if (!self::$log_file && !self::$log_firephp) echo '<pre>';
            $callers = debug_backtrace();

            // remove call to this method (DBG::stack())
            array_shift($callers);

            self::_log("TRACE:  === STACKTRACE BEGIN ===");
            $err = error_reporting(E_ALL ^ E_NOTICE);
            foreach ($callers as $c) {
                $file  = (isset($c['file']) ? self::_cleanfile($c['file']) : 'n/a');
                $line  = (isset ($c['line']) ? $c['line'] : 'n/a');
                $class = isset($c['class']) ? $c['class'] : null;
                $func  = $c['function'];
                self::_log("        $file : $line (".(empty($class) ? $func : "$class::$func").")");
            }
            error_reporting($err);
            self::_log("        === STACKTRACE END ====");
            if (!self::$log_file && !self::$log_firephp) echo '</pre>';
        }
    }


    static function msg($message)
    {
        if (!self::$enable_msg) return;

        self::_log('MSG: '.$message);
    }


    /**
     * This method is only used if logging to a file
     * @param unknown_type $errno
     * @param unknown_type $errstr
     * @param unknown_type $errfile
     * @param unknown_type $errline
     */
    public static function phpErrorHandler($errno, $errstr, $errfile, $errline)
    {
        $suppressed = '';
        if (self::$log_php & $errno) {
            if (!error_reporting()) {
                $suppressed = ' (suppressed by script)';
            }
            $type = $errno;
            switch ($errno) {
                case E_ERROR:
                    $type = 'FATAL ERROR';
                    break;
                case E_WARNING:
                    $type = 'WARNING';
                    break;
                case E_PARSE:
                    $type = 'PARSE ERROR';
                    break;
                case E_NOTICE:
                    $type = 'NOTICE';
                    break;
                case E_CORE_ERROR:
                    $type = 'E_CORE_ERROR';
                    break;
                case E_CORE_WARNING:
                    $type = 'E_CORE_WARNING';
                    break;
                case E_COMPILE_ERROR:
                    $type = 'E_COMPILE_ERROR';
                    break;
                case E_COMPILE_WARNING:
                    $type = 'E_COMPILE_WARNING';
                    break;
                case E_USER_ERROR:
                    $type = 'E_USER_ERROR';
                    break;
                case E_USER_WARNING:
                    $type = 'E_USER_WARNING';
                    break;
                case E_USER_NOTICE:
                    $type = 'E_USER_NOTICE';
                    break;
                case E_STRICT:
                    $type = 'STRICT';
                    break;
                case E_RECOVERABLE_ERROR:
                    $type = 'E_RECOVERABLE_ERROR';
                    break;
                case E_DEPRECATED:
                    $type = 'E_DEPRECATED';
                    break;
                case E_USER_DEPRECATED:
                    $type = 'E_USER_DEPRECATED';
                    break;
            }
            if (self::$log_file) {
                self::_log("PHP: $type$suppressed: $errstr in $errfile on line $errline");
            }
            if (!self::$log_file) {
                self::_log("PHP: <strong>$type</strong>$suppressed: $errstr in <strong>$errfile</strong> on line <strong>$errline</strong>");
            }
        }
    }


    static function log($text, $firephp_action='log', $additional_args=null)
    {
        if (!self::$enable_msg) return;

        self::_log('LOG: '.$text, $firephp_action, $additional_args);
    }


    private static function _log($text, $firephp_action='log', $additional_args=null)
    {
        if (self::$log_firephp
            && method_exists(self::$firephp, $firephp_action)) {
            self::$firephp->$firephp_action($additional_args, $text);
        } elseif (self::$log_file) {
            // this constant might not exist when updating from older versions
            if (defined('ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME')) {
                $dateFormat = ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME;
            } else {
                $dateFormat = 'Y-m-d H:i:s';
            }
            if (self::$dbg_fh instanceof \Cx\Lib\FileSystem\File) {
                self::$dbg_fh->append(
    // TODO: Add some flag to enable/disable timestamps
                    date($dateFormat).' '.
                    $text."\n");
            } else {
                fputs(self::$dbg_fh,
                    date($dateFormat).' '.
                    $text."\n");
            }
        } else {
            echo $text.'<br />'."\r\n";
            // force log message output
            if (ob_get_level()) {
                ob_flush();
            }
        }
    }


    public static function setSQLQueryCache($msg)
    {
        self::$sql_query_cache = $msg;
    }


    public static function getSQLQueryCache()
    {
        return self::$sql_query_cache;
    }

    public static function logSQL($sql, $forceOutput = false)
    {
        $error = preg_match('#^[0-9]+:#', $sql);

        if ($error) {
            if (self::$mode & DBG_DB_ERROR || self::$mode & DBG_DB) {
                self::logSQL(self::getSQLQueryCache(), true);
            }
            $status = 'error';
        } else {
            $status = preg_match('#^(UPDATE|DELETE|INSERT|ALTER)#', $sql) ? 'info' : 'log';
        }

        self::setSQLQueryCache($sql);

        if (!$forceOutput) {
            switch ($status) {
                case 'info':
                    if (   !(self::$mode & DBG_DB_CHANGE)
                        && !(self::$mode & DBG_DB)
                    ) {
                        return;
                    }
                    break;
                case 'error':
                    if (   !(self::$mode & DBG_DB_ERROR)
                        && !(self::$mode & DBG_DB)
                    ) {
                        return;
                    }
                    break;
                default:
                    if (!(self::$mode & DBG_DB)) {
                        return;
                    }
                    break;
            }
        }
        if (!self::$log_file && !self::$log_firephp) {
            // can't use contrexx_raw2xhtml() here, because it might not
            // have been loaded till now
            $sql = htmlentities($sql, ENT_QUOTES, CONTREXX_CHARSET);
        }

        self::_log('SQL: '.$sql, $status);

        if (!$forceOutput && self::$mode & DBG_DB_TRACE) {
            self::stack();
        }
    }
}

function DBG_log_adodb($msg)
{
    if (strpos($msg, 'password') !== false) {
        DBG::logSQL('*LOGIN (query suppressed)*');
        return;
    }

    $msg = trim(html_entity_decode(strip_tags($msg), ENT_QUOTES, CONTREXX_CHARSET));
    $sql = preg_replace('#^\(mysql\):\s*#', '', $msg);
    DBG::logSQL($sql);
}
