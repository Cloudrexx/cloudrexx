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
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Edit PHP DocBlocks!
 */

use \Cx\Core\Model\RecursiveArrayAccess as RecursiveArrayAccess;

/**
 * Session
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Leandro Nery <nery@astalavista.com>
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core
 */
class cmsSession extends RecursiveArrayAccess {

    /**
     * class instance
     *
     * @var object
     */
    public static $instance;

    /**
     * session id
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

    /*
     * Get instance of the class from the out side world
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new static();
            $_SESSION = self::$instance;

            // read the session data
            $_SESSION->readData();

            //earliest possible point to set debugging according to session.
            $_SESSION->restoreDebuggingParams();

            $_SESSION->cmsSessionExpand();
        }

        return self::$instance;
    }

    /**
     * Return true if the session is initialized and false otherwise.
     *
     * @return boolean true if the session is initialized and false otherwise.
     */
    public static function isInitialized()
    {
        if (!isset(self::$instance))
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
        return self::VARIABLE_KEY_MAX_LENGTH;
    }

    /**
     * Default object constructor.
     */
    protected function __construct()
    {

        if (ini_get('session.auto_start')) {
            session_destroy();
        }

        register_shutdown_function(array(& $this, 'releaseLocks'));

            $this->initDatabase();
            $this->initRememberMe();
            $this->initSessionLifetime();

        if (session_set_save_handler(
            array(& $this, 'cmsSessionOpen'),
            array(& $this, 'cmsSessionClose'),
            array(& $this, 'cmsSessionRead'),
            array(& $this, 'cmsSessionWrite'),
            array(& $this, 'cmsSessionDestroy'),
            array(& $this, 'cmsSessionGc')))
        {
            session_start();

        } else {
            $this->cmsSessionError();
        }
    }

    /**
     * It release all created locks
     */
    function releaseLocks() {
        // release all locks
        if (!empty($this->locks)) {
            foreach (array_keys($this->locks) as $lockKey) {
                if ($this->data[$lockKey]) {
                    $sessionValue = $this->data[$lockKey];
                    if (is_a($sessionValue, 'Cx\Core\Model\RecursiveArrayAccess')) {
                        self::updateToDb($sessionValue);
                    } else {
                        $serializedValue = contrexx_input2db(serialize($sessionValue));
                        $query = 'INSERT INTO
                                        '. DBPREFIX .'session_variable
                                    SET
                                    `parent_id` = "0",
                                    `sessionid` = "'. $_SESSION->sessionid .'",
                                    `key` = "'. contrexx_input2db($lockKey) .'",
                                    `value` = "'. $serializedValue .'"
                                  ON DUPLICATE KEY UPDATE
                                     `value` = "'. $serializedValue .'"';

                        \Env::get('db')->Execute($query);
                    }
                }

                $this->releaseLock($lockKey);
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
        if ($this->discardChanges) return true;

        $query = "UPDATE " . DBPREFIX . "sessions SET lastupdated = '" . time() . "' WHERE sessionid = '" . $_SESSION->sessionid . "'";

        \Env::get('db')->Execute($query);
    }


    /**
     * Read the data from database and assign it into $_SESSION array
     */
    function readData() {
        $this->data = self::getDataFromKey(0);
        $this->callableOnUnset = array('\cmsSession', 'removeFromSession');
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
                    `sessionid` = '{$_SESSION->sessionid}'
                  AND
                    `parent_id` = '$varId'";

        $objResult = \Env::get('db')->Execute($query);

        $data = array();
        if ($objResult !== false && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                $dataKey   = $objResult->fields['key'];
                if ($objResult->fields['value'] === '') {
                    $data[$dataKey]       = new RecursiveArrayAccess(null, $dataKey, $varId);
                    $data[$dataKey]->id   = $objResult->fields['id'];
                    $data[$dataKey]->data = self::getDataFromKey($objResult->fields['id']);
                    $data[$dataKey]->callableOnUnset = array('\cmsSession', 'removeFromSession');
                    $data[$dataKey]->callableOnSanitizeKey = array('\cmsSession', 'validateSessionKeyLength');
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
     * @access  private
     */
    private function initDatabase()
    {
        $this->setAdodbDebugMode();
    }

    /**
     * Sets the database debug mode.
     *
     * @access  private
     */
    private function setAdodbDebugMode()
    {
        if (DBG::getMode() & DBG_ADODB_TRACE) {
            \Env::get('db')->debug = 99;
        } elseif (DBG::getMode() & DBG_ADODB || DBG::getMode() & DBG_ADODB_ERROR) {
            \Env::get('db')->debug = 1;
        } else {
            \Env::get('db')->debug = 0;
        }
    }

    /**
     * Expands debugging behaviour with behaviour stored in session if specified and active.
     *
     * @access  private
     */
    private function restoreDebuggingParams()
    {
        if (isset($_SESSION['debugging']) && $_SESSION['debugging']) {
            DBG::activate(DBG::getMode() | $_SESSION['debugging_flags']);
        }
    }

    /**
     * Initializes the status of remember me.
     *
     * @access  private
     */
    private function initRememberMe()
    {
        $sessionId = !empty($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : null;
        if (isset($_POST['remember_me'])) {
            $this->rememberMe = true;
            if ($this->sessionExists($sessionId)) {//remember me status for new sessions will be stored in cmsSessionRead() (when creating the appropriate db entry)
                $objResult = \Env::get('db')->Execute('UPDATE `' . DBPREFIX . 'sessions` SET `remember_me` = 1 WHERE `sessionid` = "' . contrexx_input2db($sessionId) . '"');
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
     * Checks if the passed session exists.
     *
     * @access  private
     * @param   string      $session
     * @return  boolean
     */
    private function sessionExists($sessionId) {
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
     * @access  private
     */
    private function initSessionLifetime()
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
    function cmsSessionExpand()
    {
        // Reset the expiration time upon page load
        $ses = session_name();
        if (isset($_COOKIE[$ses])) {
            $expirationTime = ($this->lifetime > 0 ? $this->lifetime + time() : 0);
            setcookie($ses, $_COOKIE[$ses], $expirationTime, '/');
        }
    }

    /**
     * Callable method on session open
     *
     * @return boolean
     */
    function cmsSessionOpen($aSavaPath, $aSessionName)
    {
        $this->cmsSessionGc();
        return true;
    }

    /**
     * Callable on session close
     *
     * @return boolean
     */
    function cmsSessionClose()
    {
        return true;
    }

    /**
     * Callable on session read
     *
     * @param type $aKey
     * @return string
     */
    function cmsSessionRead( $aKey )
    {

        $this->sessionid = $aKey;
        $this->sessionPath = ASCMS_TEMP_WEB_PATH . '/' . $this->sessionPathPrefix . $this->sessionid;

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
     * @param type $aKey
     * @param type $aVal
     * @return boolean
     */
    function cmsSessionWrite($aKey, $aVal) {

        return true;
    }

    /**
     * Callable on session destroy
     *
     * @param type $aKey
     * @param type $destroyCookie
     * @return boolean
     */
    function cmsSessionDestroy($aKey, $destroyCookie = true) {
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

        return true;
    }

    /**
     * Destroy session by given user id
     *
     * @param integer $userId
     * @return boolean
     */
    function cmsSessionDestroyByUserId($userId) {
        $objResult = \Env::get('db')->Execute('SELECT `sessionid` FROM `' . DBPREFIX . 'sessions` WHERE `user_id` = ' . intval($userId));
        if ($objResult) {
            while (!$objResult->EOF) {
                if ($objResult->fields['sessionid'] != $this->sessionid) {
                    $this->cmsSessionDestroy($objResult->fields['sessionid'], false);
                }
                $objResult->MoveNext();
            }
        }

        return true;
    }

    /**
     * Clear expired session
     *
     * @return boolean
     */
    function cmsSessionGc() {
        \Env::get('db')->Execute('DELETE FROM `' . DBPREFIX . 'sessions` WHERE ((`remember_me` = 0) AND (`lastupdated` < ' . (time() - $this->defaultLifetime) . '))');
        \Env::get('db')->Execute('DELETE FROM `' . DBPREFIX . 'sessions` WHERE ((`remember_me` = 1) AND (`lastupdated` < ' . (time() - $this->defaultLifetimeRememberMe) . '))');
        return true;
    }

    /**
     * Update the user id of the current session
     *
     * @param integer $userId
     * @return boolean
     */
    function cmsSessionUserUpdate($userId=0)
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
    function cmsSessionStatusUpdate($status = "") {
        $this->status = $status;
        $query = "UPDATE " . DBPREFIX . "sessions SET status ='" . $status . "' WHERE sessionid = '" . $this->sessionid . "'";
        \Env::get('db')->Execute($query);
        return true;
    }

    /**
     * Callable on session error
     */
    function cmsSessionError() {
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

        return ASCMS_PATH . $this->sessionPath;
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
    }

    /**
     * Return's mysql lock name
     *
     * @param string $key lock key
     *
     * @return string lock name
     */
    static function getLockName($key)
    {
        global $_DBCONFIG;

        // MySQL 5.7.5 and later enforces a maximum length on lock names of 64 characters. Previously, no limit was enforced.
        return md5($_DBCONFIG['database'] . DBPREFIX . $_SESSION->sessionid) .md5($key);
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
        unset($_SESSION->locks[$key]);
        \Env::get('db')->Execute('SELECT RELEASE_LOCK("' . self::getLockName($key) . '")');
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
    public function offsetSet($offset, $data) {
        self::validateSessionKeyLength($offset);

        if (!isset($_SESSION->locks[$offset])) {
            $_SESSION->locks[$offset] = 1;
            self::getLock(self::getLockName($offset), self::$sessionLockTime);
        }
        parent::offsetSet($offset, $data, null, null, array('\cmsSession', 'removeFromSession'), array('\cmsSession', 'validateSessionKeyLength'));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset) {
        return self::getFromDb($offset, $this);
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
                    `sessionid` = '{$_SESSION->sessionid}'
                  AND
                    `parent_id` = '" . intval($keyId) ."'";
        $objResult = \Env::get('db')->Execute($query);

        if ($objResult && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                self::removeKeyFromDb($objResult->fields['id']);
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
     */
    public static function getFromDb($offset, $arrObj) {
        if (isset($arrObj->data[$offset])) {
            if (!isset($_SESSION->locks[$offset])) {
                $_SESSION->locks[$offset] = 1;
                self::getLock(self::getLockName($offset), self::$sessionLockTime);

                $query = 'SELECT
                            `id`,
                            `value`
                          FROM
                            `'. DBPREFIX .'session_variable`
                          WHERE
                            `sessionid` = "'. $_SESSION->sessionid .'"
                          AND
                            `parent_id` = "'. intval($arrObj->id).'"
                          AND
                            `key` = "'. contrexx_input2db($offset) .'"
                          LIMIT 0, 1';
                $objResult = \Env::get('db')->Execute($query);

                if ($objResult && $objResult->RecordCount()) {
                    if ($objResult->fields['value'] === '') {
                        $data       = new RecursiveArrayAccess(null, $offset, $arrObj->id);
                        $data->id   = $objResult->fields['id'];
                        $data->data = self::getDataFromKey($objResult->fields['id']);
                        $data->callableOnUnset = array('\cmsSession', 'removeFromSession');
                        $data->callableOnSanitizeKey = array('\cmsSession', 'validateSessionKeyLength');

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
     * @param object $arrObj session object array
     */
    public static function updateToDb($arrObj) {

        if (empty($arrObj->id) && (string) $arrObj->offset != '') {
            $query = 'INSERT INTO
                            '. DBPREFIX .'session_variable
                        SET
                        `parent_id` = "'. intval($arrObj->parentId) .'",
                        `sessionid` = "'. $_SESSION->sessionid .'",
                        `key` = "'. contrexx_input2db($arrObj->offset) .'",
                        `value` = ""';
            \Env::get('db')->Execute($query);

            $arrObj->id = \Env::get('db')->Insert_ID();
        }

        foreach ($arrObj->data as $key => $value) {

            if (is_a($value, 'Cx\Core\Model\RecursiveArrayAccess')) {
                $serializedValue = '';
            } else {
                $serializedValue = contrexx_input2db(serialize($value));
            }

            $query = 'INSERT INTO
                            '. DBPREFIX .'session_variable
                        SET
                        `parent_id` = "'. intval($arrObj->id) .'",
                        `sessionid` = "'. $_SESSION->sessionid .'",
                        `key` = "'. contrexx_input2db($key) .'",
                        `value` = "'. $serializedValue .'"
                      ON DUPLICATE KEY UPDATE
                         `value` = "'. $serializedValue .'"';

            \Env::get('db')->Execute($query);

            if (is_a($value, 'Cx\Core\Model\RecursiveArrayAccess')) {
                $value->parentId = intval($arrObj->id);
                self::updateToDb($value);
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
                    `sessionid` = '{$_SESSION->sessionid}'
                  AND
                    `parent_id` = '". intval($parentId) ."'
                  AND
                    `key` = '". contrexx_input2db($offset) ."'";

        $objResult = \Env::get('db')->Execute($query);

        if ($objResult && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                self::removeKeyFromDb($objResult->fields['id']);
                $objResult->MoveNext();
            }
        }
    }

    /**
     * Ensure that the used parameter name complies with the session
     * restrictions defined for variable keys, as the parameter name
     * is being used as a sesison-variable-key.
     * @param string $sessionKey The name of the session-variable-key used to store the current paging position.
     * @return string $sessionKey The sanitized session-variable-key.
     */
    public static function validateSessionKeyLength($sessionKey)
    {

        // Important: As the parameter name is used as a session-variable-key,
        // it must not exceed the allowed session-variable-key-length.
        if (strlen($sessionKey) > self::getVariableKeyMaxLength()) {
            throw new \Exception('Session variable key must be less than '. self::VARIABLE_KEY_MAX_LENGTH.' But given '. strlen($sessionKey));
        }

        return true;
    }
}
