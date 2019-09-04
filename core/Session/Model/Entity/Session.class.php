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
 * Module Session
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Leandro Nery <nery@astalavista.com>
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core_session
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core\Session\Model\Entity;

/**
 * Session
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Leandro Nery <nery@astalavista.com>
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core_session
 */
class Session extends \Cx\Core\Model\RecursiveArrayAccess implements \SessionHandlerInterface {

    /**
     * Instance of class for use in the singelton pattern.
     *
     * @var self
     */
    public static $instance;

    /**
     * The session id.
     *
     * @var string
     */
    public $sessionid;

    /**
     * session status
     * available options (frontend or backend)
     *
     * @var string
     */
    public $status;

    /**
     * User Id of logged user
     *
     * @var integer
     */
    public $userId;

    /**
     * temp session storage path
     *
     * @var string
     */
    private $sessionPath;

    /**
     * session prefix
     *
     * @var string
     */
    private $sessionPathPrefix = 'session_';

    /**
     * session lifetime
     * session will expire after inactivity of given lifetime
     *
     * @var integer
     */
    private $lifetime;

    /**
     * Default life time of server
     * Configurable from $_CONFIG
     *
     * @var integer
     */
    private $defaultLifetime;

    /**
     * Default rememver me time limit
     * Configurable from $_CONFIG
     *
     * @var integer
     */
    private $defaultLifetimeRememberMe;

    /**
     * Remember me
     *
     * @var boolean
     */
    private $rememberMe = false;

    /**
     * Do not write session data into database when its true
     *
     * @var boolean
     */
    private $discardChanges = false;

    /**
     * Created session locks
     *
     * @var array
     */
    private $locks = array();

    /**
     * Session Lock time
     *
     * @var integer
     */
    private static $sessionLockTime = 10;

    /**
     * Maximum allowed length of a session variable key.
     * This maximum length is defined by the associated database field core_session_variable.key.
     * @var integer
     */
    const VARIABLE_KEY_MAX_LENGTH = 100;

    /**
     * @return self
     * Get instance of the class from the out side world
     */
    public static function getInstance()
    {
        try {
            if (!isset(static::$instance)) {
                static::$instance = new static();
                $_SESSION = static::$instance;

                // read the session data
                $_SESSION->readData();

                //earliest possible point to set debugging according to session.
                $_SESSION->restoreDebuggingParams();

                $_SESSION->cmsSessionExpand();
            }

            return static::$instance;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Callable on session destroy
     *
     * @param string $aKey
     * @param boolean $destroyCookie
     * @return boolean
     */
    public function destroy($aKey = "", $destroyCookie = true)
    {
        if (empty($aKey)){
            session_destroy();
            static::$instance = null;
        }
        else {
            $query = "DELETE FROM " . DBPREFIX . "sessions WHERE sessionid = '" . $aKey . "'";
            \Env::get('db')->Execute($query);

            $query = "DELETE FROM " . DBPREFIX . "session_variable WHERE sessionid = '" . $aKey . "'";
            \Env::get('db')->Execute($query);

            if (\Cx\Lib\FileSystem\FileSystem::exists($this->sessionPath)) {
                \Cx\Lib\FileSystem\FileSystem::delete_folder($this->sessionPath, true);
            }

            if ($destroyCookie) {
                setcookie("PHPSESSID", '', time() - 3600, '/');
            }
            // do not write the session data
            $this->discardChanges = true;
            
            // drop user specific ESI cache:
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $esiFiles = glob($cx->getWebsiteTempPath() . '/cache/*u' . $aKey . '*');
            foreach ($esiFiles as $esiFile) {
                try {
                    $file = new \Cx\Lib\FileSystem\File($esiFile);
                    $file->delete();
                } catch (\Cx\Lib\FileSystem\FileSystemException $e) {}
            }
        }
        return true;
    }

    /**
     * Return true if the session is initialized and false otherwise.
     *
     * @return boolean true if the session is initialized and false otherwise.
     */
    public static function isInitialized()
    {
        if (!isset(static::$instance))
        {
            return false;
        }

        return true;
    }

    /**
     * Return the maximum length of a session variable key.
     * @return integer Maximum allowed length of a session variable key.
     */
    public static function getVariableKeyMaxLength()
    {
        return static::VARIABLE_KEY_MAX_LENGTH;
    }

    /**
     * Default object constructor.
     */
    public function __construct() {
        if (ini_get('session.auto_start')) {
            session_destroy();
        }

        register_shutdown_function(array(& $this, 'releaseLocks'));

        $this->initDatabase();
        $this->initRememberMe();
        $this->initSessionLifetime();
        $this->initCookieConfig();

        if (session_set_save_handler($this, true)) {
            session_start();
        } else {
            $this->cmsSessionError();
        }
    }

    /**
     * Initialize session cookie configuration
     *
     * This does set the following:
     * - initial lifetime of the cookie
     * - httpOnly flag
     * - secure flag in case HTTPS is forced in both, back- and frontend
     *
     * @throws \Exception If the cookie is only allowed to be transmitted
     *                    over HTTPS (secure flag), but the request has been
     *                    made over HTTP, then an exception is thrown to
     *                    prevent the disclosure of the session ID over HTTP.
     */
    protected function initCookieConfig() {
        // init config
        $lifetime = $this->lifetime;
        $path = '/';
        $domain = ini_get('session.cookie_domain');
        $secure = false;

        // see https://www.owasp.org/index.php/HttpOnly
        $httponly = true;

        // transfer cookie only over HTTPS if
        // HTTPS has been forced
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $forceProtocolBackend = \Cx\Core\Setting\Controller\Setting::getValue(
            'forceProtocolBackend',
            'Config'
        );
        $forceProtocolFrontend = \Cx\Core\Setting\Controller\Setting::getValue(
            'forceProtocolFrontend',
            'Config'
        );
        // secure flag is only enabled, if HTTPS is beeing forced in both,
        // front- and backend
        if (
            $forceProtocolBackend == 'https' &&
            $forceProtocolFrontend == 'https'
        ) {
            $secure = true;
        }
        // abort session initialization in case session is only allowed
        // over an encrypted connection, but the request has been made
        // over an non-encrypted connection
        if (
            $secure &&
            $cx->getRequest()->getUrl()->getProtocol() != 'https'
        ) {
            throw new \Exception('Unable to initialize session over a non encrypted connection');
        }

        // set cookie config
        session_set_cookie_params(
            $lifetime,
            $path,
            $domain,
            $secure,
            $httponly
        );
    }

    /**
     * It release all created locks
     */
    public function releaseLocks() {
        // release all locks
        if (!empty($this->locks)) {
            foreach (array_keys($this->locks) as $lockKey) {
                if (isset($this->data[$lockKey])) {
                    $sessionValue = $this->data[$lockKey];
                    if (is_a($sessionValue, 'Cx\Core\Model\RecursiveArrayAccess')) {
                        // Do flush session data to database through a transaction.
                        // This will have a great impact on performance.
                        $db = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
                        $db->StartTrans();
                        static::updateToDb($sessionValue);
                        if ($db->HasFailedTrans()) {
                            \DBG::msg('Oops: Unable to flush session data to database. This will result in lost session data!');
                        }
                        $db->CompleteTrans();
                    } else {
                        if ($this->isDirty($lockKey)) {
                            // is_callable() can return true for type array, so we need to check that it is not an array
                            if (!is_array($sessionValue) && !is_string($sessionValue) && is_callable($sessionValue)) {
                                \DBG::dump('Function for session index '. $lockKey .' can not be stored, saving functions in session is not supported. Please use json instead');
                                $this->releaseLock($lockKey);
                                continue;
                            }
                            $serializedValue = contrexx_input2db(serialize($sessionValue));

                            $query = 'INSERT INTO
                                            '. DBPREFIX .'session_variable
                                        SET
                                        `parent_id` = "0",
                                        `sessionid` = "'. static::getInstance()->sessionid .'",
                                        `key` = "'. contrexx_input2db($lockKey) .'",
                                        `value` = "'. $serializedValue .'"
                                      ON DUPLICATE KEY UPDATE
                                         `value` = "'. $serializedValue .'"';
                            \Env::get('db')->Execute($query);

                            $this->releaseLock($lockKey);
                        }
                    }
                }
            }
        }
        $this->updateTimeStamp();
    }

    /**
     * Update the lastupdated timestamp value in database
     */
    protected function updateTimeStamp()
    {
        // Don't write session data to databse.
        // This is used to prevent an unwanted session overwrite by a continuous
        // script request (javascript) that only checks for a certain event to happen.
        if ($this->discardChanges) return;

        $query = "UPDATE " . DBPREFIX . "sessions SET lastupdated = '" . time() . "' WHERE sessionid = '" . $this->sessionid . "'";

        \Env::get('db')->Execute($query);
    }


    /**
     * Read the data from database and assign it into $_SESSION array
     */
    public function readData() {
        $this->data = static::getDataFromKey(0);
        $this->callableOnUnset = array(get_class($this), 'removeFromSession');
    }

    /**
     * Read the data from database using variable id
     *
     * @param integer $varId
     *
     * @return \Cx\Core\Model\RecursiveArrayAccess
     */
    public static function getDataFromKey($varId)
    {
        $query = "SELECT
                    `id`,
                    `key`,
                    `value`,
                    `lastused`
                  FROM
                    `". DBPREFIX ."session_variable`
                  WHERE
                    `sessionid` = '" . static::getInstance()->sessionid . "'
                  AND
                    `parent_id` = '$varId'";

        /** @var $objResult ADORecordSet */
        $objResult = \Env::get('db')->Execute($query);

        $data = array();
        if ($objResult !== false && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                $dataKey   = $objResult->fields['key'];
                if ($objResult->fields['value'] === '') {
                    $data[$dataKey]       = new \Cx\Core\Model\RecursiveArrayAccess(null, $dataKey, $varId);
                    $data[$dataKey]->id   = $objResult->fields['id'];
                    $data[$dataKey]->data = static::getDataFromKey($objResult->fields['id']);
                    $data[$dataKey]->callableOnUnset = array(get_called_class(), 'removeFromSession');
                    $data[$dataKey]->callableOnSanitizeKey = array(get_called_class(), 'validateSessionKeyLength');
                } else {
                    $data[$dataKey] = unserialize($objResult->fields['value']);
                }

                $objResult->MoveNext();
            }
        }

        return $data;
    }

    /**
     * Initializes the database.
     *
     * @access  protected
     */
    protected function initDatabase()
    {
        $this->setAdodbDebugMode();
    }

    /**
     * Sets the database debug mode.
     *
     * @access  protected
     */
    protected function setAdodbDebugMode()
    {
        if (\DBG::getMode() & DBG_ADODB_TRACE) {
            \Env::get('db')->debug = 99;
        } elseif (\DBG::getMode() & DBG_ADODB || \DBG::getMode() & DBG_ADODB_ERROR) {
            \Env::get('db')->debug = 1;
        } else {
            \Env::get('db')->debug = 0;
        }
    }

    /**
     * Expands debugging behaviour with behaviour stored in session if specified and active.
     *
     * @access  protected
     */
    protected function restoreDebuggingParams()
    {
        if (isset($this['debugging']) && $this['debugging']) {
            \DBG::activate(\DBG::getMode() | $this['debugging_flags']);
        }
    }

    /**
     * Initializes the status of remember me.
     *
     * @access  protected
     */
    protected function initRememberMe()
    {
        /** @var $objResult ADORecordSet */
        $sessionId = !empty($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : null;
        if (isset($_POST['remember_me'])) {
            $this->rememberMe = true;
            if (static::sessionExists($sessionId)) {//remember me status for new sessions will be stored in cmsSessionRead() (when creating the appropriate db entry)
                \Env::get('db')->Execute('UPDATE `' . DBPREFIX . 'sessions` SET `remember_me` = 1 WHERE `sessionid` = "' . contrexx_input2db($sessionId) . '"');
            }
        } else {
            $objResult = \Env::get('db')->Execute('SELECT `remember_me` FROM `' . DBPREFIX . 'sessions` WHERE `sessionid` = "' . contrexx_input2db($sessionId) . '"');
            if ($objResult && ($objResult->RecordCount() > 0)) {
                if ($objResult->fields['remember_me'] == 1) {
                    $this->rememberMe = true;
                }
            }
        }
    }

    /**
     * Check if a session exists
     *
     * If a session-ID is passed as $sessionId, then it will check if a session
     * identified by that session-ID is present.
     * Otherwise it will check if a session identified by the session-cookie
     * exists.
     *
     * @param   string     $sessionId   Session-ID to check for
     * @return  boolean TRUE if a session exists. Otherwise FALSE.
     */
    public static function sessionExists($sessionId = '') {
        if (static::isInitialized()) {
            return true;
        }

        if (
            empty($sessionId) &&
            !empty($_COOKIE[session_name()])
        ) {
            $sessionId = $_COOKIE[session_name()];
        }

        if (empty($sessionId)) {
            return false;
        }

        /** @var $objResult ADORecordSet */
        $objResult = \Env::get('db')->Execute('SELECT 1 FROM `' . DBPREFIX . 'sessions` WHERE `sessionid` = "' . contrexx_input2db($sessionId) . '"');
        if ($objResult && ($objResult->RecordCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets the default session lifetimes
     * and lifetime of the current session.
     *
     * @access  protected
     */
    protected function initSessionLifetime()
    {
        global $_CONFIG;

        $this->defaultLifetime = !empty($_CONFIG['sessionLifeTime']) ? intval($_CONFIG['sessionLifeTime']) : 3600;
        $this->defaultLifetimeRememberMe = !empty($_CONFIG['sessionLifeTimeRememberMe']) ? intval($_CONFIG['sessionLifeTimeRememberMe']) : 1209600;

        if ($this->rememberMe) {
            $this->lifetime = $this->defaultLifetimeRememberMe;
        } else {
            $this->lifetime = $this->defaultLifetime;
        }

        @ini_set('session.gc_maxlifetime', $this->lifetime);
    }

    /**
     * expands a running session by @link Session::lifetime seconds.
     * called on pageload.
     */
    public function cmsSessionExpand()
    {
        // Reset the expiration time upon page load
        $ses = session_name();
        if (isset($_COOKIE[$ses])) {
            $expirationTime = ($this->lifetime > 0 ? $this->lifetime + time() : 0);
            setcookie(
                $ses,
                $_COOKIE[$ses],
                $expirationTime,
                '/',
                ini_get('session.cookie_domain'),
                ini_get('session.cookie_secure'),
                ini_get('session.cookie_httponly')
            );
        }
    }

    /**
     * Callable method on session open
     *
     * @param string $save_path
     * @param string $session_id
     *
     * @return bool
     */
    public function open($save_path, $session_id)
    {
        $this->gc(null);
        return true;
    }

    /**
     * Callable on session close
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * Callable on session read
     *
     * @param string $aKey
     * @return string
     */
    public function read( $aKey )
    {
        $this->sessionid = $aKey;
        $this->sessionPath = \Env::get('cx')->getWebsiteTempWebPath() . '/' . $this->sessionPathPrefix . $this->sessionid;
        /** @var $objResult ADORecordSet */
        $objResult = \Env::get('db')->Execute('SELECT `user_id`, `status` FROM `' . DBPREFIX . 'sessions` WHERE `sessionid` = "' . $aKey . '"');
        if ($objResult !== false) {
            if ($objResult->RecordCount() == 1) {
                $this->userId = $objResult->fields['user_id'];
                $this->status = $objResult->fields['status'];
            } else {
                \Env::get('db')->Execute('
                    INSERT INTO `' . DBPREFIX . 'sessions` (`sessionid`, `remember_me`, `startdate`, `lastupdated`, `status`, `user_id`)
                    VALUES ("' . $aKey . '", ' . ($this->rememberMe ? 1 : 0) . ', "' . time() . '", "' . time() . '", "' . $this->status . '", ' . intval($this->userId) . ')
                ');

                return '';
            }
        }

        return '';
    }

    /**
     * Callable on session write
     *
     * @param string $session_id
     * @param string $session_data
     *
     * @return bool
     */
    public function write($session_id, $session_data) {
        return true;
    }


    /**
     * Destroy session by given user id
     *
     * @param integer $userId
     * @return boolean
     */
    public function cmsSessionDestroyByUserId($userId) {
        /** @var $objResult ADORecordSet */
        $objResult = \Env::get('db')->Execute('SELECT `sessionid` FROM `' . DBPREFIX . 'sessions` WHERE `user_id` = ' . intval($userId));
        if ($objResult) {
            while (!$objResult->EOF) {
                if ($objResult->fields['sessionid'] != $this->sessionid) {
                    $this->destroy($objResult->fields['sessionid'], false);
                }
                $objResult->MoveNext();
            }
        }

        return true;
    }

    /**
     * Clear expired session
     *
     * @param int $maxlifetime
     *
     * @return bool
     */
    public function gc($maxlifetime) {
        // clear expired sessions that were once valid
        // note: those two queries might look obsolete when considering
        //       that the below three queries will have the same effect.
        //       However the last query below uses a heavy resource requiring
        //       subquery which can be made lighter by first running those two
        //       queries here
        \Env::get('db')->Execute(
            'DELETE s.*, v.*
             FROM   `' . DBPREFIX . 'sessions` AS s, `' . DBPREFIX . 'session_variable` AS v
             WHERE  s.sessionid = v.sessionid AND (
                           (`s`.`remember_me` = 0 AND `s`.`lastupdated` < ' . (time() - $this->defaultLifetime) . ')
                        OR (`s`.`remember_me` = 1 AND `s`.`lastupdated` < ' . (time() - $this->defaultLifetimeRememberMe) . ')
             )'
        );

        // clear expired sessions that were broken (no valid relation between
        // contrexx_sessions and contrexx_session_variable
        \Env::get('db')->Execute('DELETE FROM `' . DBPREFIX . 'sessions` WHERE `remember_me` = 0 AND `lastupdated` < ' . (time() - $this->defaultLifetime));
        \Env::get('db')->Execute('DELETE FROM `' . DBPREFIX . 'sessions` WHERE `remember_me` = 1 AND `lastupdated` < ' . (time() - $this->defaultLifetimeRememberMe));
        \Env::get('db')->Execute('DELETE FROM `' . DBPREFIX . 'session_variable` WHERE sessionid NOT IN (SELECT sessionid FROM `' . DBPREFIX . 'sessions`)');
        return true;
    }

    /**
     * Update the user id of the current session
     *
     * @param integer $userId
     * @return boolean
     */
    public function cmsSessionUserUpdate($userId=0)
    {
        $this->userId = $userId;
        \Env::get('db')->Execute('UPDATE `' . DBPREFIX . 'sessions` SET `user_id` = ' . $userId . ' WHERE `sessionid` = "' . $this->sessionid . '"');
        return true;
    }

    /**
     * Update user status (frontend or backend)
     *
     * @param string $status
     * @return boolean
     */
    public function cmsSessionStatusUpdate($status = "") {
        $this->status = $status;
        $query = "UPDATE " . DBPREFIX . "sessions SET status ='" . $status . "' WHERE sessionid = '" . $this->sessionid . "'";
        \Env::get('db')->Execute($query);
        return true;
    }

    /**
     * Callable on session error
     */
    public function cmsSessionError() {
        die("Session Handler Error");
    }

    /**
     * Returns current session's temp path
     *
     * @return string
     */
    public function getTempPath()
    {
        $this->cleanTempPaths();

        if (!\Cx\Lib\FileSystem\FileSystem::make_folder($this->sessionPath)) {
            return false;
        }

        if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($this->sessionPath)) {
            return false;
        }

        return \Env::get('cx')->getWebsitePath() . $this->sessionPath;
    }

    /**
     * Gets a web temp path.
     * This path is needed to work with the File-class from the framework.
     *
     * @return string
     */
    public function getWebTempPath() {
        $tp = $this->getTempPath();
        if (!$tp)
            return false;
        return $this->sessionPath;
    }

    /**
     * Clear temp path's which are not in use
     */
    public function cleanTempPaths() {
        $dirs = array();
        if ($dh = opendir(ASCMS_TEMP_PATH)) {
            while (($file = readdir($dh)) !== false) {
                if (is_dir(ASCMS_TEMP_PATH . '/' . $file)) {
                    $dirs[] = $file;
                }
            }
            closedir($dh);
        }

        // depending on the php setting session.hash_function and session.hash_bits_per_character
        // the length of the session-id varies between 22 and 40 characters.
        $sessionPaths = preg_grep('#^' . $this->sessionPathPrefix . '[0-9A-Z,-]{22,40}$#i', $dirs);
        $sessions = array();
        $query = 'SELECT `sessionid` FROM `' . DBPREFIX . 'sessions`';
        /** @var $objResult ADORecordSet */
        $objResult = \Env::get('db')->Execute($query);
        while (!$objResult->EOF) {
            $sessions[] = $objResult->fields['sessionid'];
            $objResult->MoveNext();
        }

        foreach ($sessionPaths as $sessionPath) {
            if (!in_array(substr($sessionPath, strlen($this->sessionPathPrefix)), $sessions)) {
                \Cx\Lib\FileSystem\FileSystem::delete_folder(ASCMS_TEMP_WEB_PATH . '/' . $sessionPath, true);
            }
        }
        
        // drop user specific ESI cache:
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $esiFiles = glob($cx->getWebsiteTempPath() . '/cache/*_u*');
        foreach ($esiFiles as $esiFile) {
            $match = array();
            if (!preg_match('#/[0-9a-f]{32}(?:_[pl][a-z0-9]+){0,2}?_u([a-z0-9]+)(?:_|$)#', $esiFile, $match)) {
                continue;
            }
            if (in_array($match[1], $sessions)) {
                continue;
            }
            try {
                $file = new \Cx\Lib\FileSystem\File($esiFile);
                $file->delete();
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {}
        }
    }

    /**
     * Return's mysql lock name
     *
     * @param string $key lock key
     *
     * @return string lock name
     */
    public static function getLockName($key)
    {
        global $_DBCONFIG;

        // MySQL 5.7.5 and later enforces a maximum length on lock names of 64 characters. Previously, no limit was enforced.
        return md5($_DBCONFIG['database'] . DBPREFIX . static::getInstance()->sessionid) .md5($key);
    }

    /**
     * Create's the lock in database
     *
     * @param string  $lockName Lock name
     * @param integer $lifeTime Lock time
     */
    public static function getLock($lockName, $lifeTime = 60)
    {
        $objLock = \Env::get('db')->Execute('SELECT GET_LOCK("' . $lockName . '", ' . $lifeTime . ')');
        /** @var $objLock ADORecordSet */
        if (!$objLock || $objLock->fields['GET_LOCK("' . $lockName . '", ' . $lifeTime . ')'] != 1) {
            die('Could not obtain session lock!');
        }
    }

    /**
     * Release the mysql lock
     * @param string $key Lock name to released
     */
    public function releaseLock($key)
    {
        unset($this->locks[$key]);
        \Env::get('db')->Execute('SELECT RELEASE_LOCK("' . static::getLockName($key) . '")');
    }

    /**
     * Discard changes made to the $_SESSION-array.
     *
     * If called, this method causes the session not to store
     * any changes made to the $_SESSION-array to the database.
     * Use this method when doing multiple ajax-requests simultaneously
     * to prevent an unwanted session overwrite.
     */
    public function discardChanges() {
        $this->discardChanges = true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $data, $callableOnSet = null, $callableOnGet = null, $callableOnUnset = null, $callableOnValidateKey = null) {
        static::validateSessionKeyLength($offset);

        if (!isset($this->locks[$offset])) {
            $this->locks[$offset] = 1;
            static::getLock(static::getLockName($offset), static::$sessionLockTime);
        }
        parent::offsetSet($offset, $data, null, null, array(get_class($this), 'removeFromSession'), array(get_class($this), 'validateSessionKeyLength'));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset) {
        return static::getFromDb($offset, $this);
    }

    /**
     * Remove the session variable and its sub entries from database by given id
     *
     * @param integer $keyId
     */
    public static function removeKeyFromDb($keyId) {

        $query = "SELECT
                    `id`
                  FROM
                    `". DBPREFIX ."session_variable`
                  WHERE
                    `sessionid` = '" . static::getInstance()->sessionid . "'
                  AND
                    `parent_id` = '" . intval($keyId) ."'";

        /** @var $objResult ADORecordSet */
        $objResult = \Env::get('db')->Execute($query);
        if ($objResult && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                static::removeKeyFromDb($objResult->fields['id']);
                $objResult->MoveNext();
            }
        }

        $query = "DELETE FROM `". DBPREFIX ."session_variable` WHERE id = ". intval($keyId);
        \Env::get('db')->Execute($query);
    }

    /**
     * Get lock and retrive the values from database
     * Callable from Recursive array access class on offsetGet
     *
     * @param string $offset Offset
     * @param object $arrObj object array
     *
     * @return null|RecursiveArrayAccess|string|int Whatever the value of the offset is.
     */
    public static function getFromDb($offset, $arrObj) {
        if (isset($arrObj->data[$offset])) {
            if (!isset(static::getInstance()->locks[$offset])) {
                static::getInstance()->locks[$offset] = 1;
                static::getLock(static::getLockName($offset), static::$sessionLockTime);

                $query = 'SELECT
                            `id`,
                            `value`
                          FROM
                            `'. DBPREFIX .'session_variable`
                          WHERE
                            `sessionid` = "'. static::getInstance()->sessionid .'"
                          AND
                            `parent_id` = "'. intval($arrObj->id).'"
                          AND
                            `key` = "'. contrexx_input2db($offset) .'"
                          LIMIT 0, 1';

                /** @var $objResult ADORecordSet */
                $objResult = \Env::get('db')->Execute($query);

                if ($objResult && $objResult->RecordCount()) {
                    if ($objResult->fields['value'] === '') {
                        $data       = new \Cx\Core\Model\RecursiveArrayAccess(null, $offset, $arrObj->id);
                        $data->id   = $objResult->fields['id'];
                        $data->data = static::getDataFromKey($objResult->fields['id']);
                        $data->callableOnUnset = array(get_called_class(), 'removeFromSession');
                        $data->callableOnSanitizeKey = array(get_called_class(), 'validateSessionKeyLength');

                        $arrObj->data[$offset] = $data;
                    } else {
                        $dataValue = unserialize($objResult->fields['value']);
                        $arrObj->data[$offset] = $dataValue;
                    }
                }
            }

            return $arrObj->data[$offset];
        }
        return null;
    }

    /**
     * Update given object to database
     * Callable from RecursiveArrayAccess class on offsetSet
     *
     * @param RecursiveArrayAccess $recursiveArrayAccess session object array
     */
    public static function updateToDb($recursiveArrayAccess) {
        if (empty($recursiveArrayAccess->id) && (string) $recursiveArrayAccess->offset != '') {
            $query = 'INSERT INTO
                            '. DBPREFIX .'session_variable
                        SET
                        `parent_id` = "'. intval($recursiveArrayAccess->parentId) .'",
                        `sessionid` = "'. static::getInstance()->sessionid .'",
                        `key` = "'. contrexx_input2db($recursiveArrayAccess->offset) .'",
                        `value` = ""';
            \Env::get('db')->Execute($query);

            $recursiveArrayAccess->id = \Env::get('db')->Insert_ID();
        }

        foreach ($recursiveArrayAccess->data as $key => $value) {
            if ($recursiveArrayAccess->isDirty($key)) {
                if (is_a($value, 'Cx\Core\Model\RecursiveArrayAccess')) {
                    $serializedValue = '';
                } else {
                    // is_callable() can return true for type array, so we need to check that it is not an array
                    if (!is_array($value) && !is_string($value) && is_callable($value)) {
                        \DBG::dump('Function for session index '. $key .' can not be stored, saving functions in session is not supported. Please use json instead');
                        continue;
                    }
                    $serializedValue = contrexx_input2db(serialize($value));
                }

                $query = 'INSERT INTO
                                '. DBPREFIX .'session_variable
                            SET
                            `parent_id` = "'. intval($recursiveArrayAccess->id) .'",
                            `sessionid` = "'. static::getInstance()->sessionid .'",
                            `key` = "'. contrexx_input2db($key) .'",
                            `value` = "'. $serializedValue .'"
                          ON DUPLICATE KEY UPDATE
                             `value` = "'. $serializedValue .'"';
                \Env::get('db')->Execute($query);
                if (
                    is_a($value, 'Cx\Core\Model\RecursiveArrayAccess') &&
                    empty($value->id)
                ) {
                    $insertId = \Env::get('db')->Insert_ID();
                    if ($insertId) {
                        $value->id = $insertId;
                    }
                }
            }
            if (is_a($value, 'Cx\Core\Model\RecursiveArrayAccess')) {
                $value->parentId = intval($recursiveArrayAccess->id);
                static::updateToDb($value);
            }
        }
    }

        /**
     * Remove the session key and sub keys by given offset and parent id
     * Callable from RecursiveArrayAccess class on offsetUnset
     *
     * @param string  $offset   session key name
     * @param integer $parentId parent id of the given session offset
     */
    public static function removeFromSession($offset, $parentId) {
        $query = "SELECT
                    `id`
                  FROM
                    `". DBPREFIX ."session_variable`
                  WHERE
                    `sessionid` = '" . static::getInstance()->sessionid . "'
                  AND
                    `parent_id` = '". intval($parentId) ."'
                  AND
                    `key` = '". contrexx_input2db($offset) ."'";

        /** @var $objResult ADORecordSet */
        $objResult = \Env::get('db')->Execute($query);
        if ($objResult && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                static::removeKeyFromDb($objResult->fields['id']);
                $objResult->MoveNext();
            }
        }
    }

    /**
     * Ensure that the used parameter name complies with the session
     * restrictions defined for variable keys, as the parameter name
     * is being used as a sesison-variable-key.
     *
     * @param string $sessionKey The name of the session-variable-key used to store the current paging position.
     *
     * @return boolean
     * @throws Exception
     */
    public static function validateSessionKeyLength($sessionKey)
    {

        // Important: As the parameter name is used as a session-variable-key,
        // it must not exceed the allowed session-variable-key-length.
        if (strlen($sessionKey) > static::getVariableKeyMaxLength()) {
            throw new \Exception('Session variable key must be less than '. static::VARIABLE_KEY_MAX_LENGTH.' But given '. strlen($sessionKey));
        }

        return true;
    }
}
