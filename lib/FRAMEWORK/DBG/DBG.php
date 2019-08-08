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
define('DBG_LOG_MEMORY',        1<<11);
define('DBG_LOG',               1<<12);
define('DBG_PROFILE',           1<<13);
// Full debugging (quite pointless really)
define('DBG_ALL',
      DBG_PHP
    | DBG_DB | DBG_DB_TRACE | DBG_DB_ERROR | DBG_DB_CHANGE
    | DBG_LOG_FILE | DBG_LOG_FIREPHP | DBG_LOG_MEMORY
    | DBG_LOG | DBG_PROFILE);
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
    private static $log_memory   = null;
    private static $log_adodb    = null;
    private static $log_php      = 0;
    private static $last_time    = null;
    private static $start_time   = null;
    private static $mode         = 0;
    private static $sql_query_cache = null;
    private static $memory_logs = array();
    protected static $enable_profiling = 0;
    protected static $logPrefix = '';
    protected static $logHash= '';


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
        // generate a hash to be used for associating all logs to the same request
        if (empty(self::$logHash)) {
            self::$logHash = base_convert(microtime(), 10, 36);
        }

        if (!self::$fileskiplength) {
            self::$fileskiplength = strlen(dirname(dirname(dirname(dirname(__FILE__))))) + 1;
        }
        $oldMode = self::$mode;
        if (self::$mode === DBG_NONE) {
            // activate DBG_LOG by default
            self::$mode = DBG_LOG;
        }
        if ($mode === DBG_NONE) {
            self::$mode = DBG_NONE;
        } elseif ($mode === null) {
            self::$mode = self::$mode | (DBG_ALL & ~DBG_LOG_FILE & ~DBG_LOG_FIREPHP);
        } else {
            self::$mode = self::$mode | $mode;
        }
        self::__internal__setup();
        if ($mode !== DBG_NONE) {
            if ($oldMode === DBG_NONE) {
                self::log('DBG enabled ('.self::getActivatedFlagsAsString().')');
                self::stack();
            } else {
                self::log('DBG mode changed ('.self::getActivatedFlagsAsString().')');
            }
        }
    }

    public static function getActivatedFlagsAsString() {
        $constants = get_defined_constants(true);
        $userConstants = array_keys($constants['user']);
        $flags = array_filter(
            $userConstants,
            function($constant){
                return    strpos($constant, 'DBG_') === 0
                       && constant($constant)
                       && (\DBG::getMode() & constant($constant)) === constant($constant);
            }
        );
        return join(' | ', $flags);
    }

    public static function activateIf($condition, $mode = null) {
        if (
            (!is_callable($condition) && $condition) ||
            (is_callable($condition) && $condition())
        ) {
            static::activate($mode);
        }
    }

    public static function isIp($ip) {
        return $_SERVER['REMOTE_ADDR'] == $ip;
    }

    public static function hasCookie($cookieName) {
        return isset($_COOKIE[$cookieName]);
    }

    public static function hasCookieValue($cookieName, $cookieValue) {
        if (!static::hasCookie($cookieName)) {
            return false;
        }
        return $_COOKIE[$cookieName] == $cookieValue;
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
        if ($mode === DBG_NONE) {
            self::log('DBG disabled ('.self::getActivatedFlagsAsString().')');
            self::stack();
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
        // log to memory
        if (self::$mode & DBG_LOG_MEMORY) {
            self::enable_memory();
        } else {
            self::disable_memory();
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

        // set profiling mode
        if (self::$mode & DBG_PROFILE) {
            self::enable_profiling();
        } else {
            self::disable_profiling();
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
     * Disables logging to FirePHP & memory in turn.
     */
    private static function enable_file()
    {
        if (self::$log_file) return;
        // disable firephp first
        self::disable_firephp();
        self::disable_memory();
// DO NOT OVERRIDE DEFAULT BEHAVIOR FROM INSIDE THE CLASS!
// Call a method to do this from the outside.
//        self::setup('dbg.log', 'w');
        if (self::setup(dirname(__FILE__, 4) . '/tmp/log/dbg.log')) {
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
        // FirePHP overrides file & memory logging
        self::disable_file();
        self::disable_memory();
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


    /**
     * Enable profiling
     */
    protected static function enable_profiling()
    {
        if (self::$enable_profiling) return;

        self::$enable_profiling = true;
        self::enable_time();
    }


    /**
     * Disables profiling
     */
    protected static function disable_profiling()
    {
        if (!self::$enable_profiling) return;

        self::disable_time();
        self::$enable_profiling = false;
    }


    /**
     * Enables logging to memory
     *
     * Disables logging to a file and firephp in turn.
     */
    private static function enable_memory()
    {
        if (self::$log_memory) return;
        // FirePHP overrides file logging
        self::disable_file();
        self::disable_firephp();
        self::$log_memory = true;
    }


    /**
     * Disables logging to memory
     */
    private static function disable_memory()
    {
        if (!self::$log_memory) return;
        self::$log_memory = false;
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
            self::_log("TIME AT: $f:$l $d (diff: $diff_last, startdiff: $diff_start)".(!empty($comment) ? ' -- '.$comment : ''), 'info', null, false);
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
        if (self::$log_memory) return true; //no need to setup ressources, we're using memory
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
                self::$dbg_fh = new \Cx\Lib\FileSystem\File($file.$suffix);
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
            self::$dbg_fh = fopen($file.$suffix, $mode);
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
            self::_log("TRACE:  $f : $l", 'log', null, false);
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
        global $_CONFIG;

        if (!self::$enable_dump) return;

        self::_escapeDoctrineDump($val);

        if (self::$log_firephp) {
            self::$firephp->log($val);
            return;
        }
        if ($val === null) {
            $out = 'NULL';
        } else {
            $out = stripslashes(var_export($val, true));
        }
        $out = str_replace("\n", "\n        ", $out);
        if (!self::$log_file && !self::$log_memory && php_sapi_name() != 'cli') {
            // we're logging directly to the browser
            // can't use contrexx_raw2xhtml() here, because it might not
            // have been loaded till now
            self::_log(
                'DUMP:   <p><pre>' . htmlentities(
                    $out,
                    ENT_QUOTES,
                    $_CONFIG['coreCharacterEncoding']
                ) . '</pre></p>'
            );
        } else {
            self::_log('DUMP:   '.$out);
        }
    }

    private static function _escapeDoctrineDump(&$val)
    {
        // TODO: implement own dump-method that is able to handle recursive references
        if (is_object($val)) {
            $val = \Doctrine\Common\Util\Debug::export($val, 2);
        } else if (is_array($val)) {
            foreach ($val as &$entry) {
                self::_escapeDoctrineDump($entry);
            }
        }
    }

    static function stack()
    {
        if (self::$enable_trace) {
            if (!self::$log_file && !self::$log_firephp && !self::$log_memory) echo '<pre>';
            $callers = debug_backtrace();

            // remove call to this method (DBG::stack())
            array_shift($callers);

            self::_log("TRACE:  === STACKTRACE BEGIN ===", 'log', null, false);
            $err = error_reporting(E_ALL ^ E_NOTICE);
            foreach ($callers as $c) {
                $file  = (isset($c['file']) ? self::_cleanfile($c['file']) : 'n/a');
                $line  = (isset ($c['line']) ? $c['line'] : 'n/a');
                $class = isset($c['class']) ? $c['class'] : null;
                $func  = $c['function'];
                self::_log("        $file : $line (".(empty($class) ? $func : "$class::$func").")", 'log', null, false);
            }
            error_reporting($err);
            self::_log("        === STACKTRACE END ====", 'log', null, false);
            if (!self::$log_file && !self::$log_firephp && !self::$log_memory) echo '</pre>';
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
            if (self::$log_file || self::$log_memory) {
                self::_log("PHP: $type$suppressed: $errstr in $errfile on line $errline");
            } else {
                self::_log("PHP: <strong>$type</strong>$suppressed: $errstr in <strong>$errfile</strong> on line <strong>$errline</strong>");
            }

            // Catch infinite loop produced by var_export()
            if ($errstr == 'var_export does not handle circular references') {
                self::log('Cancelled script execution to prevent memory overflow caused by var_export()');
                self::stack();
                exit;
            }
        }
    }

    /**
     * Writes the last line of a request to the log
     * @param \Cx\Core\Core\Controlller\Cx $cx Cx instance of the request
     * @param bool $cached Whether this request is answered from cache
     * @param string $outputModule (optional) Name of the output module
     */
    public static function writeFinishLine(
        \Cx\Core\Core\Controller\Cx $cx,
        bool $cached,
        string $outputModule = ''
    ) {
        $requestInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $requestIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $requestIpParts = explode('.', $requestIp);
        end($requestIpParts);
        $requestIpParts[key($requestIpParts)] = '[...]';
        $requestIp = implode('.', $requestIpParts);
        $requestHost = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : $requestIp;
        $requestUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $cachedStr = $cached ? 'cached' : 'uncached';
        $userHash = '';
        $stats = $cx->getComponent(
            'Stats'
        );
        if ($stats) {
            $counter = $stats->getCounterInstance();
            if ($counter) {
                $userHash = $counter->getUniqueUserId();
            }
        }

        // fetch parsed response code
        if ($cx->getResponse()) {
            $httpResponseCode = $cx->getResponse()->getCode();
        } else {
            // as fallback fetch response code from set headers
            $httpResponseCode = http_response_code();
        }

        register_shutdown_function(
            function() use (
                $cx,
                $requestInfo,
                $requestIp,
                $requestHost,
                $requestUserAgent,
                $cachedStr,
                $userHash,
                $outputModule,
                $httpResponseCode
            ) {
                $parsingTime = $cx->stopTimer();
                $format = '(Cx: %1$s) Request parsing completed after %2$s "%3$s" "%4$s" "%5$s" "%6$s" "%7$s" "%8$s" "%9$s" "%10$s" "%11$s"';
                $log = sprintf(
                    $format,
                    $cx->getId(),
                    $parsingTime,
                    $cachedStr,
                    $requestInfo,
                    $requestIp,
                    $requestHost,
                    $requestUserAgent,
                    memory_get_peak_usage(true),
                    $userHash,
                    $outputModule,
                    $httpResponseCode
                );
                \DBG::log($log);
            }
        );
    }

    static function log($text, $firephp_action='log', $additional_args=null)
    {
        if (!self::$enable_msg) return;

        self::_log('LOG: '.$text, $firephp_action, $additional_args);
    }


    private static function _log($text, $firephp_action='log', $additional_args=null, $profile=true)
    {
        if (!self::isLogWorthy($text)) return;

        if ($profile && self::$enable_profiling) {
            self::time();
        }

        if (self::$logPrefix !== '') {
            $text = '"(' . self::$logPrefix . ' - ' . self::$logHash . ')" ' . $text;
        } else {
            $text = '"(' . self::$logHash . ')" ' . $text;
        }

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
        } elseif (self::$log_memory) {
            // this constant might not exist when updating from older versions
            if (defined('ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME')) {
                $dateFormat = ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME;
            } else {
                $dateFormat = 'Y-m-d H:i:s';
            }
            self::$memory_logs[] = date($dateFormat).' '.$text;
        } else {
            if (php_sapi_name() == 'cli') {
                echo $text . PHP_EOL;
            } else {
                echo $text . '<br />';
            }
            // force log message output
            if (ob_get_level()) {
                ob_flush();
            }
        }
    }


    public static function getMemoryLogs() {
        return self::$memory_logs;
    }


    private static function isLogWorthy($text) {
        $unworthLogs = array();
        //$unworthLogs = array('File', 'FTPFile', 'YamlRepository');
        if (empty($unworthLogs)) {
            return true;
        }
        return !preg_match('/^MSG: ('.join('|', $unworthLogs).')[^:]*:/', $text);
    }

    public static function appendLogs($logs) {
        if (self::$mode & DBG_LOG_MEMORY) {
            self::$memory_logs = array_merge(self::$memory_logs, $logs);
        } else {
            if (!self::$enable_msg) return;

            foreach ($logs as $log) {
                self::_log($log);
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
        global $_CONFIG;

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
        if (
            !self::$log_file &&
            !self::$log_firephp &&
            !self::$log_memory &&
            php_sapi_name() != 'cli'
        ) {
            // can't use contrexx_raw2xhtml() here, because it might not
            // have been loaded till now
            $sql = htmlentities(
                $sql,
                ENT_QUOTES,
                $_CONFIG['coreCharacterEncoding']
            );
        }

        self::_log('SQL: '.$sql, $status);

        if (!$forceOutput && self::$mode & DBG_DB_TRACE) {
            self::stack();
        }
    }

    /**
     * Set a text that will be put in front of all log messages
     *
     * @param   string  $prefix The text that shall be put in front of log messages
     */
    public static function setLogPrefix($prefix = '') {
        self::$logPrefix = $prefix;
    }

    /**
     * Reset the text that is being put in front of log messags
     */
    public static function resetLogPrefix() {
        self::setLogPrefix();
    }
}

function DBG_log_adodb($msg)
{
    global $_CONFIG;

    if (strpos($msg, 'password') !== false) {
        DBG::logSQL('*LOGIN (query suppressed)*');
        return;
    }

    $msg = trim(
        html_entity_decode(
            strip_tags($msg),
            ENT_QUOTES,
            $_CONFIG['coreCharacterEncoding']
        )
    );
    $sql = preg_replace('#^\([^\)]+\):\s*#', '', $msg);
    DBG::logSQL($sql);
}
