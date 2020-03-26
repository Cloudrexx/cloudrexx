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
 * u2uLibrary
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Raveendran.L
 * @package     cloudrexx
 * @subpackage  module_u2u
 */

namespace Cx\Modules\U2u\Controller;

/**
 * u2uLibrary
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Raveendran.L
 * @package     cloudrexx
 * @subpackage  module_u2u
 */
class U2uLibrary {

    var $_boolInnoDb = false;
    var $_intLanguageId;
    var $_intCurrentUserId;
    var $_arrSettings           = array();
    var $_arrLanguages          = array();
       var $_arrlistLevel = null;
       var $PaginactionCount;
       var $orderedResults,$orderofResult;
    var $paginationCount,$counter;

    /**
    * Constructor
    *
    */
    function __construct()  {
        $this->setDatabaseEngine();
        $this->_arrSettings     = $this->createSettingsArray();
        $this->_arrLanguages    = $this->createLanguageArray();
    }


    /**
     * Reads out the used database engine and sets the local variable.
     * @global      array       $objDatabase
     */
    function setDatabaseEngine() {
        global $objDatabase;

        $objMetaResult = $objDatabase->Execute('SHOW TABLE STATUS LIKE "'.DBPREFIX.'module_u2u_settings"');
        if (preg_match('/.*innodb.*/i', $objMetaResult->fields['Engine'])) {
            $this->_boolInnoDb = true;
        }
    }

    /**
     *
     * Selects the username with the id of the user who has loggged on.
     * @global      $objDatabase
     */
    function getUserID($userName) {
        global $objDatabase;

        $arrSettings = \User_Setting::getSettings();
        $where = array();
        $where[] = '`email` = "' . $userName . '"';
        if ($arrSettings['use_usernames']['status']) {
            $where[] = '`username` = "' . $userName . '"';
        }

        $userName = contrexx_addslashes($userName);
        $selUserID  = 'SELECT id FROM '.DBPREFIX.'access_users
                       WHERE (' . implode(' OR ', $where) . ') AND
                       active=1';
        $objResult = $objDatabase->Execute($selUserID);
        while (!$objResult->EOF) {
          $ID=$objResult->fields['id'];
          $objResult->MoveNext();
        }
        return $ID;
    }

    /**
     *
     * creates the Array of messages for the users..
     * @global      $objDatabase
     */
    function createEntryDetails($userID, $pos) {

        global $objDatabase,$_CONFIG,$_ARRAYLANG;

        $userID = intval($userID);
        /**
          *Checks the Messages is the notification or the Messages..
          *if it is a notification we have make the condition as the Messages should show (not opened only)
          */

        if($_REQUEST["cmd"]=="inbox") {
             $whereCondition ="";
             $pagingText="<b>".$_ARRAYLANG['TXT_INBOX_PAGING']."</b>";
        }
        if($_REQUEST["cmd"]=="notification") {
            $whereCondition =' AND sentMsg.mesage_open_status="0"';
            $pagingText="<b>".$_ARRAYLANG['TXT_NOTIFICATION_PAGING']."</b>";
        }

        $selMessage ='SELECT
                        Log.message_text,
                        Log.message_title,
                        Log.message_id,
                        sentMsg.userid,
                        sentMsg.date_time
                        FROM
                        '.DBPREFIX.'module_u2u_sent_messages sentMsg,
                        '.DBPREFIX.'module_u2u_message_log Log,
                        '.DBPREFIX.'access_users User

                        WHERE
                        sentMsg.receiver_id='.$userID.' AND
                        User.id=sentMsg.userid AND
                        User.active!=0 AND
                        Log.message_id=sentMsg.message_id'.$whereCondition.'
                        ORDER BY sentMsg.date_time DESC';
      $objResult = $objDatabase->Execute($selMessage);

      $count = $objResult->RecordCount();
      $this->counter=$count;

      $paging = getPaging($count, $pos, "&section=U2u&cmd=".$_REQUEST['cmd'],$pagingText, true);

      $selMessage ='SELECT
                        Log.message_text,
                        Log.message_title,
                        Log.message_id,
                        sentMsg.userid,
                        sentMsg.date_time
                        FROM
                        '.DBPREFIX.'module_u2u_sent_messages sentMsg,
                        '.DBPREFIX.'module_u2u_message_log Log,
                        '.DBPREFIX.'access_users User

                        WHERE
                        sentMsg.receiver_id='.$userID.' AND
                        User.id=sentMsg.userid AND
                        User.active!=0 AND
                        Log.message_id=sentMsg.message_id'.$whereCondition.'
                        ORDER BY sentMsg.date_time DESC';

       $objResult = $objDatabase->SelectLimit($selMessage, $_CONFIG['corePagingLimit'], $pos);
       $this->paginationCount=$paging;

       $arrMessage = array();
       while (!$objResult->EOF) {
          $userName = $this->_getName($objResult->fields['userid']);
          $messageID=$objResult->fields['message_id'];
          $arrMessage[$messageID] = array();
          $arrMessage[$messageID]["message"]        =   $objResult->fields['message_text'];
          $arrMessage[$messageID]["message_title"]  =   $objResult->fields['message_title'];
          $arrMessage[$messageID]["username"]       =   $userName ? $userName['username'] : 'N/A';
          $arrMessage[$messageID]["date_time"]      =   $objResult->fields['date_time'];
          $objResult->MoveNext();
        }

       return $arrMessage;
    }

    /**
     *
     * creates the Array of messages for the users whom they sent...
     * @global      $objDatabase
     */
    function createEntryDetailsOutbox($userID, $pos) {
        global $objDatabase,$_CONFIG,$_ARRAYLANG;

        $userID = intval($userID);
        $selMessage ='SELECT
                        Log.message_text,
                        Log.message_title,
                        Log.message_id,
                        User.username,
                        sentMsg.userid,
                        sentMsg.date_time
                        FROM
                        '.DBPREFIX.'module_u2u_sent_messages sentMsg,
                        '.DBPREFIX.'module_u2u_message_log Log,
                        '.DBPREFIX.'access_users User

                        WHERE
                        sentMsg.userid='.$userID.' AND
                        User.id=sentMsg.receiver_id AND
                        User.active!=0 AND
                        Log.message_id=sentMsg.message_id AND
                        sentMsg.mesage_open_status="0"
                        ORDER BY sentMsg.date_time DESC';
      //$pos = intval($_GET['pos']);
      $objResult = $objDatabase->Execute($selMessage);
      $count = $objResult->RecordCount();
      $this->counter=$count;
      $paging = getPaging($count, $pos, "&section=U2u&cmd=outbox", "<b>".$_ARRAYLANG['TXT_OUTBOX_PAGING']."</b>", true);

      $selMessage ='SELECT
                        Log.message_text,
                        Log.message_title,
                        Log.message_id,
                        User.username,
                        sentMsg.userid,
                        sentMsg.date_time
                        FROM
                        '.DBPREFIX.'module_u2u_sent_messages sentMsg,
                        '.DBPREFIX.'module_u2u_message_log Log,
                        '.DBPREFIX.'access_users User

                        WHERE
                        sentMsg.userid='.$userID.' AND
                        User.id=sentMsg.receiver_id AND
                        User.active!=0 AND
                        Log.message_id=sentMsg.message_id AND
                        sentMsg.mesage_open_status="0"
                        ORDER BY sentMsg.date_time DESC';


      $objResult = $objDatabase->SelectLimit($selMessage, $_CONFIG['corePagingLimit'], $pos);
      $this->paginationCount=$paging;
      //$objResult = $objDatabase->Execute($selMessage);

      $arrMessage = array();
       while (!$objResult->EOF) {
          $messageID=$objResult->fields['message_id'];
          $arrMessage[$messageID] = array();
          $arrMessage[$messageID]["message"]        =   $objResult->fields['message_text'];
          $arrMessage[$messageID]["message_title"]  =   $objResult->fields['message_title'];
          $arrMessage[$messageID]["username"]       =   $objResult->fields['username'];
          $arrMessage[$messageID]["date_time"]      =   $objResult->fields['date_time'];
          $objResult->MoveNext();
       }
       return $arrMessage;
    }

    /**
     *
     * Calculating the Total number of new messages for the Private notifications..
     * @global      $objDatabase
     */
    function notificationEntryMessage($userID) {

        global $objDatabase;

        $userID = intval($userID);
        $selMessageCount='SELECT
                        COUNT(Log.message_id) AS numberofEntries
                        FROM
                        '.DBPREFIX.'module_u2u_sent_messages sentMsg,
                        '.DBPREFIX.'module_u2u_message_log Log,
                        '.DBPREFIX.'access_users User

                        WHERE
                        sentMsg.receiver_id='.$userID.' AND
                        User.id=sentMsg.userid AND
                        Log.message_id=sentMsg.message_id
                        AND sentMsg.mesage_open_status="0"
                        AND User.active!=0
                        ORDER BY sentMsg.date_time';
        $objResult = $objDatabase->Execute($selMessageCount);
        $newMessages=$objResult->fields['numberofEntries'];
        return $newMessages;
    }

    /**
     *
     * Returns the Messages with the array and updating the message open status
     * @global      $objDatabase
     */
    function createEntryShowMessage($messageID) {
        global $objDatabase;

        $messageID = intval($messageID);
        if($_REQUEST["status"]=="outboxmsg" || !empty($_REQUEST['send'])) {
            $whereCondition=' AND User.id=sentMsg.receiver_id';
        } else {
            $whereCondition=' AND User.id=sentMsg.userid';
        }

        /**Select the message from the Database... */
        $selShowMessage ='SELECT
                        Log.message_text,
                        Log.message_title,
                        Log.message_id,
                        User.regdate,
                        sentMsg.userid,
                        sentMsg.date_time
                        FROM
                        '.DBPREFIX.'module_u2u_sent_messages sentMsg,
                        '.DBPREFIX.'module_u2u_message_log Log,
                        '.DBPREFIX.'access_users User
                        WHERE
                        Log.message_id='.$messageID.' AND
                        sentMsg.message_id=Log.message_id
                        AND User.active!=0'.$whereCondition.' ORDER BY sentMsg.date_time DESC';
        $objResult = $objDatabase->Execute($selShowMessage);

        /**Updating the Message Open status.. */
        if(empty($_REQUEST["status"]) && empty($_REQUEST['send'])) {
            $updateStatus ='UPDATE
                          '.DBPREFIX.'module_u2u_sent_messages
                           SET  mesage_open_status="1"
                           WHERE message_id="'.$messageID.'"
                          ';
            $objUpdate = $objDatabase->Execute($updateStatus);
        }

        while (!$objResult->EOF) {
            $userName = $this->_getName($objResult->fields['userid']);
            $arrShowMessage["message"]           =   $objResult->fields['message_text'];
            $arrShowMessage["message_title"]     =   $objResult->fields['message_title'];
            $arrShowMessage["username"]          =   $userName ? $userName['username'] : 'N/A';
            $arrShowMessage["registerd_date"]    =  date('Y-m-d',$objResult->fields['regdate']);
            $arrShowMessage["date_time"]         =   $objResult->fields['date_time'];
            $objResult->MoveNext();
        }
        return $arrShowMessage;
    }

    /**
     *
     * Deletes the Messages from the table from the message log
     * @global      $objDatabase
     */
    function deleteMsg($id) {
        global $objDatabase;
        $id = intval($id);
        $delMessage = 'DELETE  from '.DBPREFIX.'module_u2u_message_log WHERE message_id='.$id.'';
        $objUpdate = $objDatabase->Execute($delMessage);
        return $objUpdate;
    }

    /**
     *
     * Gets the Maximum posting size
     * @global      $objDatabase
     */
    function _getMaxPostingDetails()  {
        global $objDatabase;

        $settingQuery='SELECT value from '.DBPREFIX.'module_u2u_settings WHERE name="max_posting_size"';
        $objResult= $objDatabase->Execute($settingQuery);
        $arrShowSettings["max_posting_size"] = $objResult->fields['value'];
        return $arrShowSettings;
    }

    /**
     *
     * Gets the Maximum Posting Chars on the Messages..
     * @global      $objDatabase
     */
    function _getMaxCharDetails() {
        global $objDatabase;

        $settingQuery='SELECT value from '.DBPREFIX.'module_u2u_settings WHERE name="max_posting_chars"';
        $objResult=$objDatabase->Execute($settingQuery);
        $arrShowSettings['max_posting_chars'] = $objResult->fields['value'];
        return $arrShowSettings;
    }

    /**
     *
     * Gets the Maximum posting Entries of the Users..
     * @global      $objDatabase
     */
    function _getMaxpostings($id)  {
        global $objDatabase;

        $id = intval($id);
        $settingQuery        = 'SELECT count(*) AS numberOfEntries from '.DBPREFIX.'module_u2u_sent_messages WHERE userid='.$id.'';
        $objResult           = $objDatabase->Execute($settingQuery);
        $arrShowSettings     = $objResult->fields['numberOfEntries'];
        return $arrShowSettings;
    }

    /**
     *
     * Gets the Email subject Specified by the admin..
     * @global      $objDatabase
     */
    function _getEmailSubjectDetails() {
        global $objDatabase;

        $settingQuery='SELECT value from '.DBPREFIX.'module_u2u_settings WHERE name="subject"';
        $objResult=$objDatabase->Execute($settingQuery);
        $arrShowSettings['subject'] = $objResult->fields['value'];
        return $arrShowSettings;
    }

    /**
     *
     * Gets the Email From address details Specified by the admin..
     * @global      $objDatabase
     */
    function _getEmailFromDetails() {
        global $objDatabase;

        $settingQuery='SELECT value from '.DBPREFIX.'module_u2u_settings WHERE name="from"';
        $objResult=$objDatabase->Execute($settingQuery);
        $arrShowSettings['from'] = $objResult->fields['value'];
        return $arrShowSettings;
    }

    /**
     *
     * Gets the Email Message content details Specified by the admin..
     * @global      $objDatabase
     */
    function _getEmailMessageDetails(){
        global $objDatabase;

        $settingQuery='SELECT value from '.DBPREFIX.'module_u2u_settings WHERE name="email_message"';
        $objResult=$objDatabase->Execute($settingQuery);
        $arrShowSettings['email_message']        =$objResult->fields['value'];
        return $arrShowSettings;
    }

    /**
     *
     * Gets the Status of the Users..
     * @global      $objDatabase
     */
    function _getStatus($userID) {

        global $objDatabase;
        $userID = intval($userID);
        $selStatusUser      = 'select u2u_active from '.DBPREFIX.'access_users
                              where id="'.$userID.'" and active="1"';
        $objResult          = $objDatabase->Execute($selStatusUser);
        $countStatus        = $objResult->RecordCount();
        return $countStatus;

    }

    /**
     *
     * Gets the Email of the Users..
     * @global      $objDatabase
     */
    function _getEmail($id) {
        global $objDatabase;

        $id = intval($id);
        $emailQuery='SELECT email from '.DBPREFIX.'access_users WHERE id='.$id.'';
        $objResult=$objDatabase->Execute($emailQuery);
        $arrShowEmail['email']        =$objResult->fields['email'];
        return $arrShowEmail;
    }

     /**
     *
     * Gets the Name of the Users..
     * @global      $objDatabase
     */
    function _getName($id) {
        $id = intval($id);
        $objUser = \FWUser::getFWUserObject()->objUser->getUser($id);
        if (!$objUser) {
            return false;
        }
        return array('username' => $objUser->getUsername());
    }

     /**
     *
     * Gets the City of the Users..
     * @global      $objDatabase
     */
    function _getCity($id) {
        global $objDatabase;

        $id = intval($id);
        $cityQuery='SELECT city from '.DBPREFIX.'access_user_profile WHERE user_id='.$id.'';
        $objResult=$objDatabase->Execute($cityQuery);
        $arrShowcity['city']        =$objResult->fields['city'];
        return $arrShowcity;
    }

     /**
     *
     * Gets the Website Address of the Users..
     * @global      $objDatabase
     */
    function _getSite($id) {
        global $objDatabase;

        $id = intval($id);
        $siteQuery='SELECT website from '.DBPREFIX.'access_user_profile WHERE user_id='.$id.'';
        $objResult=$objDatabase->Execute($siteQuery);
        $arrShowsite['website'] = $objResult->fields['website'];
        return $arrShowsite;
    }

     /**
     *
     * Gets the Address list members of the Users..
     * @global      $objDatabase
     */
    function _getBuddyNames($id) {
        global $objDatabase;

        $id = intval($id);
        $buddiesQuery='SELECT buddies_id from '.DBPREFIX.'module_u2u_address_list WHERE user_id='.$id.'';
        $objResult=$objDatabase->Execute($buddiesQuery);
        while (!$objResult->EOF) {
              $arrShowbuddies[] = $objResult->fields['buddies_id'];
              $objResult->MoveNext();
        }
       //print_r($arrShowbuddies);
        return $arrShowbuddies;
    }

    /**
     * Get the User IDs of the currently logged-in user's buddies
     *
     * @global ADONewConnection
     */
    static function getIdsOfBuddies()
    {
        global $objDatabase;
        static $arrIds;

        if (!isset($arrIds)) {
            $arrIds = array();

            $objFWUser = \FWUser::getFWUserObject();
            if ($objFWUser->objUser->login()) {
                $objResult = $objDatabase->Execute('SELECT `buddies_id` FROM `'.DBPREFIX.'module_u2u_address_list` WHERE `user_id` = '.$objFWUser->objUser->getId());
                if ($objResult !== false) {
                    while (!$objResult->EOF) {
                        $arrIds[] = $objResult->fields['buddies_id'];
                        $objResult->MoveNext();
                    }
                }
            }
        }

        return $arrIds;
    }
}
?>
