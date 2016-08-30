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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Leandro Nery <nery@astalavista.com>
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Edit PHP DocBlocks!
 */

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
class cmsSession
{
    var $sessionid;
    var $status;
    private $sessionPath;
    private $sessionPathPrefix = 'session_';
    var $userId;
    var $_objDb;
    private $compatibilityMode;
    private $lifetime;
    private $defaultLifetime;

    function __construct($status='')
    {
        global $_CONFIG;

        if (ini_get('session.auto_start')) {
            session_destroy();
        }

        $this->status = $status;
        $this->initDatabase();
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

            //earliest possible point to set debugging according to session.
            $this->restoreDebuggingParams();

            $this->cmsSessionExpand();
        } else {
            $this->cmsSessionError();
        }
    }

    /**
     * Initializes the database.
     *
     * @access  private
     */
    private function initDatabase()
    {
        $errorMsg = '';
        $this->_objDb = getDatabaseObject($errorMsg, true);

        $this->setAdodbDebugMode();
        $this->compatibilityMode = ($arrColumns = $this->_objDb->MetaColumnNames(DBPREFIX.'sessions')) && in_array('username', $arrColumns);
    }

    /**
     * Sets the database debug mode.
     *
     * @access  private
     */
    private function setAdodbDebugMode()
    {
        if (DBG::getMode() & DBG_ADODB_TRACE) {
            $this->_objDb->debug = 99;
        } elseif (DBG::getMode() & DBG_ADODB || DBG::getMode() & DBG_ADODB_ERROR) {
            $this->_objDb->debug = 1;
        } else {
            $this->_objDb->debug = 0;
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
     * Checks if the passed session exists.
     *
     * @access  private
     * @param   string      $session
     * @return  boolean
     */
    private function sessionExists($sessionId)
    {
        $objResult = $this->_objDb->Execute('SELECT 1 FROM `'.DBPREFIX.'sessions` WHERE `sessionid` = "'.contrexx_input2db($sessionId).'"');
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
        $this->lifetime = $this->defaultLifetime;
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

    function cmsSessionOpen($aSavaPath, $aSessionName)
    {
        $this->cmsSessionGc();
        return true;
    }

    function cmsSessionClose()
    {
        return true;
    }

    function cmsSessionRead( $aKey )
    {
        $this->sessionid = $aKey;
        $this->sessionPath = ASCMS_TEMP_WEB_PATH.'/'.$this->sessionPathPrefix.$this->sessionid;

        $objResult = $this->_objDb->Execute('SELECT `datavalue`, `'.($this->compatibilityMode ? 'username' : 'user_id').'`, `status` FROM `'.DBPREFIX.'sessions` WHERE `sessionid` = "'.$aKey.'"');
        if ($objResult !== false) {
            if ($objResult->RecordCount() == 1) {
                $this->userId = $objResult->fields[($this->compatibilityMode ? 'username' : 'user_id')];
                $this->status = $objResult->fields['status'];
                return $objResult->fields['datavalue'];
            } else {
                $this->_objDb->Execute('
                    INSERT INTO `'.DBPREFIX.'sessions` (`sessionid`, `startdate`, `lastupdated`, `status`, `'.($this->compatibilityMode ? 'username' : 'user_id').'`)
                    VALUES ("'.$aKey.'", "'.time().'", "'.time().'", "'.($this->status).'", "'.intval($this->userId).'")
                ');
                return '';
           }
        }
    }

    function cmsSessionWrite( $aKey, $aVal )
    {
        $aVal = addslashes( $aVal );
        $query = "UPDATE ".DBPREFIX."sessions SET datavalue = '".$aVal."', lastupdated = '".time()."' WHERE sessionid = '".$aKey."'";
        $this->_objDb->Execute($query);
        return true;
    }

    function cmsSessionDestroy( $aKey )
    {
        $query = "DELETE FROM ".DBPREFIX."sessions WHERE sessionid = '".$aKey."'";
        $this->_objDb->Execute($query);

        if (\Cx\Lib\FileSystem\FileSystem::exists($this->sessionPath)) {
            \Cx\Lib\FileSystem\FileSystem::delete_folder($this->sessionPath, true);
        }

        return true;
    }

    function cmsSessionDestroyByUserId($userId)
    {
        $objResult = $this->_objDb->Execute('SELECT `sessionid` FROM `'.DBPREFIX.'sessions` WHERE `'.($this->compatibilityMode ? 'username' : 'user_id').'` = '.intval($userId));
        if ($objResult) {
            while (!$objResult->EOF) {
                if ($objResult->fields['sessionid'] != $this->sessionid) {
                    $this->cmsSessionDestroy($objResult->fields['sessionid']);
                }
                $objResult->MoveNext();
            }
        }

        return true;
    }

    function cmsSessionGc()
    {
        $this->_objDb->Execute('DELETE FROM `'.DBPREFIX.'sessions` WHERE `lastupdated` < '.(time()-$this->defaultLifetime));
        return true;
    }

    function cmsSessionUserUpdate($userId=0)
    {
        $this->userId = $userId;
        $query = "UPDATE ".DBPREFIX."sessions SET user_id ='".$userId."' WHERE sessionid = '".$this->sessionid."'";
        if ($this->compatibilityMode) {
           $query = "UPDATE ".DBPREFIX."sessions SET username ='".$userId."' WHERE sessionid = '".$this->sessionid."'";
        }
        $this->_objDb->Execute($query);
        return true;
    }

    function cmsSessionStatusUpdate($status="")
    {
        $this->status=$status;
        $query = "UPDATE ".DBPREFIX."sessions SET status ='".$status."' WHERE sessionid = '".$this->sessionid."'";
        $this->_objDb->Execute($query);
        return true;
    }

    function cmsSessionError() {
        die ("Session Handler Error");
    }

    public function getTempPath()
    {
        $this->cleanTempPaths();

        if (!\Cx\Lib\FileSystem\FileSystem::make_folder($this->sessionPath)) {
            return false;
        }

        if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($this->sessionPath)) {
            return false;
        }

        return ASCMS_PATH.$this->sessionPath;
    }

    /**
     * Gets a web temp path.
     * This path is needed to work with the File-class from the framework.
     *
     * @return string
     */
    public function getWebTempPath() {
        $tp = $this->getTempPath();
        if(!$tp)
            return false;
        return $this->sessionPath;
    }

    public function cleanTempPaths()
    {
        $dirs = array();
        if ($dh = opendir(ASCMS_TEMP_PATH)) {
            while (($file = readdir($dh)) !== false) {
                if (is_dir(ASCMS_TEMP_PATH.'/'.$file)) {
                    $dirs[] = $file;
                }
            }
            closedir($dh);
        }

        $sessionPaths = preg_grep('#^'.$this->sessionPathPrefix.'[0-9A-F]{32}$#i', $dirs);
        $sessions = array();
        $query = 'SELECT `sessionid` FROM `'.DBPREFIX.'sessions`';
        $objResult = $this->_objDb->Execute($query);
        while (!$objResult->EOF) {
            $sessions[] = $objResult->fields['sessionid'];
            $objResult->MoveNext();
        }

        foreach ($sessionPaths as $sessionPath) {
            if (!in_array(substr($sessionPath, strlen($this->sessionPathPrefix)), $sessions)) {
                \Cx\Lib\FileSystem\FileSystem::delete_folder(ASCMS_TEMP_WEB_PATH.'/'.$sessionPath, true);
            }
        }
    }
}
