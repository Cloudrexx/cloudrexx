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
 * u2u
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Raveendran.L
 * @package     cloudrexx
 * @subpackage  module_u2u
 */

namespace Cx\Modules\U2u\Controller;

/**
 * u2u
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Raveendran.L
 * @package     cloudrexx
 * @subpackage  module_u2u
 */
class U2u extends U2uLibrary
{
    /**
    * Template object
    *
    * @access private
    * @var object
    */
    var $_objTpl,$strMessages;

    private $arrStatusMsg = array('ok' => array(), 'error' => array());


    /**
    * PHP5 constructor
    *
    * @global object $objTemplate
    * @global array $_ARRAYLANG
    */
    function __construct($pageContent) {

        $this->_intLanguageId = intval($_LANGID);
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($pageContent);
    }




    /**
    * Get content page
    *
    * @access public
    */
    function getPage()    {
        if (isset($_GET['act'])) {
            $action = $_GET['act'];
        } else if(isset($_GET['cmd'])) {
            $action = $_GET['cmd'];
        } else {
            $action = '';
        }

        $objFWUser = \FWUser::getFWUserObject();
        if (!$objFWUser->objUser->login()) {
            $link = base64_encode(CONTREXX_SCRIPT_PATH.'?'.$_SERVER['QUERY_STRING']);
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
            exit;
        }

        switch ($action) {
            case 'message':
               // Permission::checkAccess(59, 'static');
                $this->insertMessages();
                break;
            case 'notification':
                 //Permission::checkAccess(59, 'static');
                $this->shownotification();
                break;
            case 'show':
                 //Permission::checkAccess(59, 'static');
                $this->showMessage();
                break;
            case 'forwardandreply':
                // Permission::checkAccess(59, 'static');
                $this->forwardreplyMessage();
                break;
            case 'sendmsg':
                // Permission::checkAccess(59, 'static');
                $this->sendMsg();
                break;
            case 'inbox':
                 //Permission::checkAccess(59, 'static');
                $this->listMessages();
                break;
            case 'outbox':
                $this->showEntriesOutbox();
                break;
            case 'addAddress':
                $this->addAddress();
                break;

            case 'delAddress':
                $this->delAddress();
                $this->buddiesList();
                break;


            case 'buddiesList':
                $this->buddiesList();
                break;


            default:
                 //Permission::checkAccess(59, 'static');
                $this->showEntries();
                break;
        }



         return $this->_objTpl->get();
    }

    /**
     * Show the form which is used to send the Messages..
     *
     * @global   $_ARRAYLANG $objDatabase
     */
    function showEntries() {
            global $_ARRAYLANG,$objDatabase;

            $objFWUser = \FWUser::getFWUserObject();
            $id = $objFWUser->objUser->getId();


            //echo "user".$objFWUser->objUser->login();
            //Permission::checkAccess($objFWUser->objUser->login());

            $newMessageCount=$this->notificationEntryMessage($objFWUser->objUser->getId());
            if($newMessageCount>0) {

                $this->_objTpl->setVariable(array(
                    'TXT_PRIVATE_NOTIFICATION_LINK' => $_ARRAYLANG['TXT_PRIVATE_NOTIFICATION_LINK'],
                    'TXT_NOTIFICATION_TOTAL_COUNT'  => $newMessageCount
                ));


            }
            else {

                $this->_objTpl->hideBlock('u2u_private_notification_message_link');
            }

            $strMessageInputHTML = new \Cx\Core\Wysiwyg\Wysiwyg('private_message', $this->strMessages, 'bbcode');

            $this->_objTpl->setVariable(array(
                'TXT_SEND_PRIVATE_MESSAGE'           =>  $_ARRAYLANG['TXT_SEND_PRIVATE_MESSAGE'],
                'MESSAGE_INPUT'                         =>     $strMessageInputHTML,
                'TXT_RECEPIENTS'                     =>  $_ARRAYLANG['TXT_RECEPIENTS'],
                'TXT_RECEPIENTS_USERNAME'            =>  $_ARRAYLANG['TXT_RECEPIENTS_USERNAME'],
                'TXT_BCC_RECEPIENTS_USERNAME'        =>  $_ARRAYLANG['TXT_BCC_RECEPIENTS_USERNAME'],
                'TXT_MESSAGE_CONTENT'                =>  $_ARRAYLANG['TXT_MESSAGE_CONTENT'],
                'TXT_MESSAGE_TITLE'                  =>  $_ARRAYLANG['TXT_MESSAGE_TITLE'],
                'TXT_U2U_SELECT_BUDDY'               =>  $_ARRAYLANG['TXT_U2U_SELECT_BUDDY'],
                'TXT_U2U_SEND_MESSAGE_USERS'         =>  $_ARRAYLANG['TXT_U2U_SEND_MESSAGE_USERS'],
                'TXT_U2U_SUBMIT_MESSAGE'             =>  $_ARRAYLANG['TXT_U2U_SUBMIT_MESSAGE'],
                'TXT_U2U_PREVIEW_MESSAGE'            =>  $_ARRAYLANG['TXT_U2U_PREVIEW_MESSAGE'],
                'TXT_U2U_BCC'                        =>  $_ARRAYLANG['TXT_U2U_BCC'],
                'TXT_U2U_ENTER_NAME'                 =>  $_ARRAYLANG['TXT_U2U_ENTER_NAME']
            ));

          $buddyNames=$this->_getBuddyNames($id);
          foreach($buddyNames as $buddyKey => $buddyValue) {
                if($id!=$buddyValue) {
                $name=$this->_getName($buddyValue);
                if (!$name) {
                    continue;
                }
                $this->_objTpl->setVariable('TXT_U2U_OPTION_VALUE', $name['username']);
                $this->_objTpl->parse('u2u_user_drop_down');
                }
          }

    }

    /**
     * List of Messages for the users on the inbox...
     *
     * @global   $_ARRAYLANG $objDatabase $_CORELANG;
     */
    function listMessages() {
       global $_ARRAYLANG, $objDatabase,$_CORELANG,$_CONFIG;

       $objFWUser = \FWUser::getFWUserObject();
       if($_REQUEST["frmShowEntries_MultiAction"]=="delete") {
            $selectedEntries=$_REQUEST["selectedEntriesId"];
            for($i=0;$i<count($selectedEntries);$i++) {
                $this->deleteMsg($selectedEntries[$i]);
            }
            $this->_objTpl->setVariable(array(
                'PRIVATE_MESSAGE_DELETE_SUCCESS'  =>  $_ARRAYLANG['PRIVATE_MESSAGE_DELETE_SUCCESS']
            ));
        }
        $pos=intval($_GET['pos']);
        $notification=$this->createEntryDetails($objFWUser->objUser->getId(),$pos);
        if(empty($notification)) {
            $this->_objTpl->setVariable(array(
                'PRIVATE_MESSAGE_NO_ENTRIES'        => $_ARRAYLANG['PRIVATE_MESSAGE_NO_ENTRIES']
            ));
            $this->_objTpl->hideBlock('headSection');
            $this->_objTpl->hideBlock('DeleteSection');
       } else {
            $this->_objTpl->hideBlock('noEntries');
       }

       foreach($notification as $MsgID=>$messageItem) {
            $this->_objTpl->setVariable(array(
                'PRIVATE_MESSAGE_TITLE'        => $messageItem["message_title"],
                'PRIVATE_MESSAGE_TEXT'         => $messageItem["message"],
                'PRIVATE_MESSAGE_ID'           => $MsgID,
                'MESSAGE_AUTHOR_NAME'          => $messageItem["username"],
                'MESSAGE_SENT_DATE'            => $messageItem["date_time"],
                'ROW_CLASS'                     => $i % 2 == 0 ? "row1" : "row2",
            ));
            $this->_objTpl->parse('privatemessage');
            if($_CONFIG['corePagingLimit'] < $this->counter) {
                $this->_objTpl->setVariable('INBOX_PAGING',$this->paginationCount);
            }
            $i++;
        }

        $this->_objTpl->setVariable(array(
            'TXT_NOTIFICATION_MESSAGE'           =>  $_ARRAYLANG['TXT_NOTIFICATION_PRIVATE_MESSAGE'],
            'TXT_ENTRIES_SELECT_ALL'             =>  $_ARRAYLANG['TXT_ENTRIES_SELECT_ALL'],
            'TXT_ENTRIES_DESELECT_ALL'           =>  $_ARRAYLANG['TXT_ENTRIES_DESELECT_ALL'],
            'TXT_ENTRIES_SUBMIT_SELECT'          =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_SELECT'],
            'TXT_ENTRIES_SUBMIT_DELETE_JS'       =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_DELETE_JS'],
            'TXT_ENTRIES_SUBMIT_DELETE'          =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_DELETE']
        ));
    }

       /**
     * List of Messages for the users on the Outbox...
     *
     * @global   $_ARRAYLANG $objDatabase $_CORELANG;
     */
    function showEntriesOutbox() {

        global $_ARRAYLANG, $objDatabase,$_CORELANG,$_CONFIG;
        $objFWUser = \FWUser::getFWUserObject();
        if($_REQUEST["frmShowEntries_MultiAction"]=="delete") {
              $selectedEntries=$_REQUEST["selectedEntriesId"];
              for($i=0;$i<count($selectedEntries);$i++) {
                    $this->deleteMsg($selectedEntries[$i]);
              }
              $this->_objTpl->setVariable(array(
                  'PRIVATE_MESSAGE_DELETE_SUCCESS'  =>  $_ARRAYLANG['PRIVATE_MESSAGE_DELETE_SUCCESS']
              ));
        }
        $pos = intval($_GET['pos']);
        $notification=$this->createEntryDetailsOutbox($objFWUser->objUser->getId(),$pos);
        if(empty($notification)) {
            $this->_objTpl->setVariable(array(
                 'PRIVATE_MESSAGE_NO_ENTRIES'        => $_ARRAYLANG['PRIVATE_MESSAGE_NO_ENTRIES']
            ));
            $this->_objTpl->hideBlock('headSection');
            $this->_objTpl->hideBlock('DeleteSection');
       } else {
            $this->_objTpl->hideBlock('noEntries');
       }

       foreach($notification as $MsgID=>$messageItem) {
            $this->_objTpl->setVariable(array(
                'PRIVATE_MESSAGE_TITLE'        => $messageItem["message_title"],
                'PRIVATE_MESSAGE_TEXT'         => $messageItem["message"],
                'PRIVATE_MESSAGE_ID'           => $MsgID,
                'MESSAGE_AUTHOR_NAME'          => $messageItem["username"],
                'MESSAGE_SENT_DATE'            => $messageItem["date_time"],
                'ROW_CLASS'                     => $i % 2 == 0 ? "row1" : "row2",
            ));
            $i++;
            $this->_objTpl->parse('privatemessage');
       }

       $this->_objTpl->setVariable(array(
            'TXT_NOTIFICATION_MESSAGE'           =>  $_ARRAYLANG['TXT_NOTIFICATION_PRIVATE_MESSAGE'],
            'TXT_ENTRIES_SELECT_ALL'             =>  $_ARRAYLANG['TXT_ENTRIES_SELECT_ALL'],
            'TXT_ENTRIES_DESELECT_ALL'           =>  $_ARRAYLANG['TXT_ENTRIES_DESELECT_ALL'],
            'TXT_ENTRIES_SUBMIT_SELECT'          =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_SELECT'],
            'TXT_ENTRIES_SUBMIT_DELETE_JS'       =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_DELETE_JS'],
            'TXT_ENTRIES_SUBMIT_DELETE'          =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_DELETE']
        ));
        if($_CONFIG['corePagingLimit'] < $this->counter) {
            $this->_objTpl->setVariable("OUTBOX_PAGING", $this->paginationCount);
        }
    }

    /**
     * Used to add the address in the address book..
     *
     * @global   $_ARRAYLANG $objDatabase $_CORELANG
     */
    function addAddress() {
         global $_ARRAYLANG, $objDatabase, $_CORELANG;
         $objFWUser = \FWUser::getFWUserObject();
         $id=$objFWUser->objUser->getId();
         $buddies_id = intval($_REQUEST['id']);
         $query = 'SELECT 1 FROM '.DBPREFIX.'module_u2u_address_list WHERE user_id="'.$id.'" AND buddies_id="'.$buddies_id.'"';
         $objRS = $objDatabase->SelectLimit($query, 1);
         if($objRS->RecordCount() > 0){
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Access&cmd=members");
            die();
         }
         $query='REPLACE INTO '.DBPREFIX.'module_u2u_address_list  (
                                         user_id ,
                                         buddies_id
                                         )
                                         VALUES ('.$id.','.$buddies_id.')';
         $objDatabase->Execute($query);
         \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Access&cmd=members");
         die();
    }

    /**
     * List the members in the Address book(buddies list)....
     *
     * @global   $_ARRAYLANG $objDatabase $_CORELANG
     */
    function buddiesList() {
        global $_ARRAYLANG, $objDatabase, $_CORELANG,$_CONFIG;
        $pos=intval($_GET['pos']);
        $objFWUser = \FWUser::getFWUserObject();
        $id=$objFWUser->objUser->getId();
        if($_REQUEST['id']!="") {
            $buddy_id=intval($_REQUEST['id']);
            $delQuery='DELETE from '.DBPREFIX.'module_u2u_address_list WHERE buddies_id='.$buddy_id.' AND user_id='.$id.'';
            $objDatabase->Execute($delQuery)or die("DB error in ".__FILE__.":".__LINE__);
            $this->_objTpl->setVariable(array(
                'TXT_U2U_DELETE_CONFIRM' => $_ARRAYLANG['TXT_U2U_DELETED_SUCCESSFULLY'],
            ));
        }
        $selQuery='SELECT user_id,buddies_id from '.DBPREFIX.'module_u2u_address_list WHERE user_id='.$id.'';
        $objResult=$objDatabase->Execute($selQuery);
        $count = $objResult->RecordCount();
        $paging = getPaging($count, $pos, "&section=U2u&cmd=buddiesList", "<b>".$_ARRAYLANG['TXT_ADDRESS_BOOK_PAGING']."</b>", true);
        $selQuery='SELECT user_id,buddies_id from '.DBPREFIX.'module_u2u_address_list WHERE user_id='.$id.'';
        $objResult=$objDatabase->SelectLimit($selQuery, $_CONFIG['corePagingLimit'], $pos);

        if(($objResult->fields['user_id']!="")) {
             $this->_objTpl->hideBlock('address_empty');
        } else {
            $this->_objTpl->setVariable(array(
                'ADDRESS_BOOK_NO_ENTRIES'        => $_ARRAYLANG['TXT_U2U_NO_ADDRESS']
            ));
            $this->_objTpl->hideBlock('delete_information');
        }

        $this->_objTpl->setGlobalVariable(array(
            'TXT_U2U_ADDRESS_BOOK'           =>  $_ARRAYLANG['TXT_U2U_ADDRESS_BOOK'],
            'TXT_U2U_NICK_NAME'              =>  $_ARRAYLANG['TXT_U2U_NICK_NAME'],
            'TXT_U2U_CITY'                   =>  $_ARRAYLANG['TXT_U2U_CITY'],
            'TXT_U2U_EMAIL'                  =>  $_ARRAYLANG['TXT_U2U_EMAIL'],
            'TXT_U2U_ACTIONS'                =>  $_ARRAYLANG['TXT_U2U_ACTIONS'],
            'TXT_U2U_DETAILS'                =>  $_ARRAYLANG['TXT_U2U_DETAILS'],
            'TXT_U2U_DELETE'                 =>  $_ARRAYLANG['TXT_U2U_DELETE'],
            'TXT_U2U_HOME_SITE'              =>  $_ARRAYLANG['TXT_U2U_HOME_SITE'],
            'TXT_SEND_PRIVATE_MESSAGE'       =>  $_ARRAYLANG['TXT_SEND_PRIVATE_MESSAGE'],
        ));

        foreach($objResult as $objId => $objValue) {
            $userName   = $this->_getName($objValue['buddies_id']);
            if (!$userName) {
                continue;
            }
            $userEmail  = $this->_getEmail($objValue['buddies_id']);
            $userCity   = $this->_getCity($objValue['buddies_id']);
            $userSite   = $this->_getSite($objValue['buddies_id']);
            if($userSite['website']=="") {
                $imgPath = "modules/U2u/View/Media/home_disabled.gif";
            } else {
                $imgPath = "modules/U2u/View/Media/home.gif";
                $userSite['website']="href='".$userSite['website']."'";
            }

            $strEmail = '&nbsp;';
            $objUser = $objFWUser->objUser->getUser($objValue['buddies_id']);
            if (in_array($objUser->getEmailAccess(), array('everyone', 'members_only'))){
                $strEmail = $userEmail['email'];
            }

            $this->_objTpl->setVariable(array(
                   'TXT_U2U_ADDRESS_NAME'           =>  $userName ['username'],
                   'TXT_U2U_ADDRESS_CITY'           =>  $userCity ['city'],
                   'TXT_U2U_ADDRESS_EMAIL'          =>  $strEmail,
                   'TXT_U2U_ADDRESS_BUDDIES_ID'     =>  $objValue['buddies_id'],
                   'TXT_U2U_BUDDY_SITE'             =>  $userSite['website'],
                   'TXT_U2U_IMG_PATH'               =>  $imgPath,
                   'ROW_CLASS'                         => $i % 2 == 0 ? "row1" : "row2",
            ));
            $this->_objTpl->parse('address_list');
            $i++;
      }
      if($_CONFIG['corePagingLimit'] < $count) {
          $this->_objTpl->setVariable("ADDRESS_BOOK_PAGINATION",$paging);
      }
    }

    /**
     * Delete the members in the Address book(buddies list)....
     *
     * @global   $_ARRAYLANG $objDatabase $_CORELANG
     */
    function delAddress() {
        global $_ARRAYLANG, $objDatabase, $_CORELANG;
        $objFWUser = \FWUser::getFWUserObject();
        $id=$objFWUser->objUser->getId();
        if($_REQUEST['id']!="") {
            $buddy_id=intval($_REQUEST['id']);
            $delQuery='DELETE from '.DBPREFIX.'module_u2u_address_list WHERE buddies_id='.$buddy_id.' AND user_id='.$id.'';
            $objDatabase->Execute($delQuery) or die("DB error in ".__FILE__.":".__LINE__);
            //$this->buddiesList();
          //  $this->_objTpl->setVariable(array(
            //     'TXT_U2U_DELETE_CONFIRM' = "deleted successfully"
          //));
        }
//        $this->buddiesList();
    }

    /**
     * Used to send a message...
     *
     * @global   $_ARRAYLANG $objDatabase $_CORELANG
     */
    function sendMsg() {
        global $_ARRAYLANG, $objDatabase,$_CORELANG;

        if (!empty($_REQUEST['id'])) {
            $userName = $this->_getName($_REQUEST['id']);
            $_ARRAYLANG["u2u_message_user_name"] = $userName['username'];
            $this->_objTpl->setVariable(array(
                'TXT_RECEPIENT'                 => $_ARRAYLANG["u2u_message_user_name"]
            ));
        }
        $this->showEntries();
    }

    /**
     * Forwarding and reply to the Messages.
     *
     * @global   $_ARRAYLANG  $objDatabase $_CORELANG
     */
    function forwardreplyMessage()  {
        global $_ARRAYLANG, $objDatabase,$_CORELANG;

        if(!empty($_REQUEST['msg_id'])) {
           $messageID=intval($_REQUEST['msg_id']);
        }
        if(!empty($_REQUEST['forward'])) {
           $arrForwardMessage=$this->createEntryShowMessage($messageID);
           $this->strMessages=$arrForwardMessage["message"];
           $this->_objTpl->setVariable(array(
               'TXT_PRIVATE_MESSAGE_TITLE'     => "FW: ".$arrForwardMessage["message_title"]
           ));
        } else if(!empty($_REQUEST['reply'])) {
           $arrForwardMessage=$this->createEntryShowMessage($messageID);
           $this->strMessages=$arrForwardMessage["message"];
           $this->_objTpl->setVariable(array(
                'TXT_RECEPIENT'                 => $arrForwardMessage["username"],
                'TXT_PRIVATE_MESSAGE_TITLE'     => "RE: ".$arrForwardMessage["message_title"]
           ));
        } else if(!empty($_REQUEST['send'])) {
           $arrForwardMessage=$this->createEntryShowMessage($messageID);
           $this->strMessages=$arrForwardMessage["message"];
           $this->_objTpl->setVariable(array(
                'TXT_RECEPIENT'                 => $arrForwardMessage["username"],
                'TXT_PRIVATE_MESSAGE_TITLE'     => $arrForwardMessage["message_title"]
           ));
        } else {
          $replyMsg=$this->deleteMsg($messageID);
          $this->arrStatusMsg['ok'][]=$_ARRAYLANG['TXT_U2U_DELETED_SUCCESSFULLY'];//"Deleted Successfully";//$_ARRAYLANG['TXT_U2U_ENTRY_ADD_SUCCESS_MESSAGE'];
          $this->_objTpl->setVariable('U2U_SEND_MESSAGE', implode('<br />', $this->arrStatusMsg['ok']));
          $this->_objTpl->parse('u2u_send_confirm_success');
          $this->_objTpl->hideBlock('u2u_send_confirm_error');
        }
        $this->showEntries();
    }

    /**
     * Shows the unread notification
     *
     * @global   $_ARRAYLANG  $objDatabase $_CORELANG
     */
    function shownotification() {
       global $_ARRAYLANG, $objDatabase,$_CORELANG,$_CONFIG;

       $objFWUser = \FWUser::getFWUserObject();
       if($_REQUEST["frmShowEntries_MultiAction"]=="delete") {
            $selectedEntries=$_REQUEST["selectedEntriesId"];
            for($i=0;$i<count($selectedEntries);$i++) {
                 $this->deleteMsg($selectedEntries[$i]);
            }
            $this->_objTpl->setVariable(array(
                'PRIVATE_MESSAGE_DELETE_SUCCESS'  =>$_ARRAYLANG['PRIVATE_MESSAGE_DELETE_SUCCESS']
            ));
       }
       $pos=intval($_GET['pos']);
       $notification=$this->createEntryDetails($objFWUser->objUser->getId(),$pos);

       if(empty($notification)) {

            $this->_objTpl->setVariable(array(
               'PRIVATE_MESSAGE_NO_ENTRIES'        => $_ARRAYLANG['PRIVATE_MESSAGE_NO_ENTRIES']
            ));
            $this->_objTpl->hideBlock('headSection');
            $this->_objTpl->hideBlock('DeleteSection');
       } else {
            $this->_objTpl->hideBlock('noEntries');
       }

       foreach($notification as $MsgID=>$messageItem) {
            $this->_objTpl->setVariable(array(
                'PRIVATE_MESSAGE_TITLE'        => $messageItem["message_title"],
                'PRIVATE_MESSAGE_TEXT'         => $messageItem["message"],
                'PRIVATE_MESSAGE_ID'           => $MsgID,
                'MESSAGE_AUTHOR_NAME'          => $messageItem["username"],
                'MESSAGE_SENT_DATE'            => $messageItem["date_time"],
                'ROW_CLASS'                     => $i % 2 == 0 ? "row1" : "row2",
            ));
            $i++;
            $this->_objTpl->parse('privatemessage');
       }
       $this->_objTpl->setVariable(array(
            'TXT_NOTIFICATION_MESSAGE'           =>  $_ARRAYLANG['TXT_NEW_NOTIFICATION_PRIVATE_MESSAGE'],
            'TXT_ENTRIES_SELECT_ALL'             =>  $_ARRAYLANG['TXT_ENTRIES_SELECT_ALL'],
            'TXT_ENTRIES_DESELECT_ALL'           =>  $_ARRAYLANG['TXT_ENTRIES_DESELECT_ALL'],
            'TXT_ENTRIES_SUBMIT_SELECT'          =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_SELECT'],
            'TXT_ENTRIES_SUBMIT_DELETE_JS'       =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_DELETE_JS'],
            'TXT_ENTRIES_SUBMIT_DELETE'          =>  $_ARRAYLANG['TXT_ENTRIES_SUBMIT_DELETE']
        ));
       if($_CONFIG['corePagingLimit'] < $this->counter) {
            $this->_objTpl->setVariable('NOTIFICATION_PAGING',$this->paginationCount);
       }
    }

    /**
     * Inserts the Messages into the Databases.
     * Performs the Validations..
     *
     * @global   $_ARRAYLANG  $objDatabase $_CORELANG
     */
    function insertMessages() {
           global $_ARRAYLANG, $objDatabase,$_CORELANG,$_CONFIG;

           if (!isset($_REQUEST['private_message'])) {
               return false;
           }

           $errArray = array();
           $_REQUEST['private_message'] = \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForDb($_REQUEST['private_message']);
        $this->strMessages=$_REQUEST['private_message'];
        /**
        * For display the preview***
        */
        if($_REQUEST['rcpt_name']!="") {
             $recpName=$_REQUEST['rcpt_name'];
        } else {
             $recpName=$_REQUEST['recipients'];
        }
       if($_REQUEST['preview']!="") {
            $this->_objTpl->setVariable(array(
                        'TXT_U2U_PREVIEW_MESSAGE'                    => $_ARRAYLANG['TXT_U2U_PREVIEW_MESSAGE'],
                        'TXT_U2U_PREVIEW_HEADER'                     => $_REQUEST['title'],
                        'TXT_U2U_PREVIEW_SUBJECT'                    => \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput($_REQUEST['private_message']),
                        'TXT_U2U_PREVIEW_WEBSITE'                    => '',//'http://'.$_CONFIG['domainUrl'],
                        'TXT_RECEPIENT'                              => $recpName,//$_REQUEST['recipients'],
                        'TXT_PRIVATE_MESSAGE_TITLE'                  => $_REQUEST['title'],
                        'TXT_BCC'                                    => $_REQUEST['bcc'],
                ));
                $this->showEntries();
        } else {
            $this->_objTpl->hideBlock('u2u_preview_message');
               $objFWUser = \FWUser::getFWUserObject();
            $arrayRecepient=$this->arrayMerge();
            $arrayRecepients=array_unique($arrayRecepient);
            $Private_Message=$_REQUEST['private_message'];
            $settingMaxChars               =  $this->_getMaxCharDetails();
                         $max_chars                     =  $settingMaxChars['max_posting_chars'];
                         $db_settings_max_postings      =  $this->_getMaxPostingDetails();
                         $db_settings_max_postings_value=  $db_settings_max_postings['max_posting_size'];
                         $Posters_id                    =  $objFWUser->objUser->getId();
                         $settingMaxPosting             =  $this->_getMaxpostings($Posters_id);
                         $statusU2UActive               =  $this->_getStatus($objFWUser->objUser->getId());

            if(count($arrayRecepients)>10) {
                  $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_U2U_RECEPIENTS_EXCEED_ERROR'];
                  $errorMessage = true;
            }
            elseif($settingMaxPosting >= $db_settings_max_postings_value) {

                        $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_MAX_POSTING_SIZE_EXCEEDS'];//$_ARRAYLANG['TXT_U2U_ENTRY_ADD_ERROR_TITLE'];
                        $this->_objTpl->setVariable(array(
                            'TXT_RECEPIENT'            => $recpName,//$_REQUEST['recipients'],
                            'TXT_BCC'                  => $_REQUEST['bcc'],
                            'TXT_PRIVATE_MESSAGE_TITLE' => $_REQUEST['title']
                        ));
                        $errorMessage = true;
            } elseif(empty($_REQUEST['title'])) {
                        $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_U2U_ENTRY_ADD_ERROR_TITLE'];
                        $this->_objTpl->setVariable(array(
                            'TXT_RECEPIENT'            => $recpName,
                            'TXT_BCC'                  => $_REQUEST['bcc']
                        ));
                        $errorMessage = true;
            } elseif(empty($Private_Message)) {
                        $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_U2U_ENTRY_ADD_ERROR_MESSAGE'];
                        $this->_objTpl->setVariable(array(
                            'TXT_RECEPIENT'                => $recpName,
                            'TXT_BCC'                       => $_REQUEST['bcc'],
                            'TXT_PRIVATE_MESSAGE_TITLE'     => $_REQUEST['title']
                        ));
                        $errorMessage = true;
            } elseif(strlen($Private_Message) >= $max_chars )  {
                        $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_PRIVATE_EXCEEDED'].$max_chars;//$_ARRAYLANG['TXT_U2U_ENTRY_ADD_ERROR_MESSAGE'];
                        $this->_objTpl->setVariable(array(
                            'TXT_RECEPIENT'                 => $recpName,
                            'TXT_BCC'                       => $_REQUEST['bcc'],
                            'TXT_PRIVATE_MESSAGE_TITLE'     => $_REQUEST['title']
                        ));
                        $errorMessage = true;
            } elseif(count($arrayRecepients)==0) {
                $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_PLS_ENTER_USERNAME'];//$_ARRAYLANG['TXT_PRIVATE_EXCEEDED'].$max_chars;//$_ARRAYLANG['TXT_U2U_ENTRY_ADD_ERROR_MESSAGE'];
                $this->_objTpl->setVariable(array(
                   'TXT_PRIVATE_MESSAGE_TITLE'     => $_REQUEST['title']
                ));
                $errorMessage = true;
            } else {
                foreach ($arrayRecepients as $user) {
                    $ID = $this->getUserID($user);
                    if(empty($ID)) {
                        $errorString=str_replace("[userName]",$user,$_ARRAYLANG['TXT_U2U_ENTRY_ADD_ERROR_EMAIL']);
                        $this->arrStatusMsg['error'][] = $errorString;
                        $errorMessage = true;
                    } elseif($statusU2UActive==0) {
                       $errorString=str_replace("[userName]",$user,$_ARRAYLANG['TXT_U2U_STATUS_DISABLED_ERROR']);
                       $this->arrStatusMsg['error'][] = $errorString;
                       $errorMessage = true;
                    } else {
                        $errArray[0] = array();
                        $errArray[0]['receipents_userid']  =  $ID;
                        $errArray[0]['sending_userid']     =  $objFWUser->objUser->getId();
                        $errArray[0]['title']              =  contrexx_addslashes(strip_tags(trim(htmlentities($_REQUEST['title'],ENT_QUOTES,CONTREXX_CHARSET))));
                        $errArray[0]['private_message']    =  $_REQUEST['private_message'];
                        $this->insertEntryDataMessage($errArray);
                        $this->arrStatusMsg['ok'][]=$_ARRAYLANG['TXT_U2U_ENTRY_ADD_SUCCESS_MESSAGE'];
                        $successVar=1;
                        $this->strMessages="";
                        //Send notification to users
                        $this->sendNotificationMail($objFWUser->objUser->getId(), $ID);
                    }
                }
            }
            if ($errorMessage == true) {
                $this->_objTpl->setVariable('U2U_SEND_MESSAGE', implode('<br />', $this->arrStatusMsg['error']));
                $this->_objTpl->parse('u2u_send_confirm_error');
            }
            if($successVar==1)
            {
                $this->_objTpl->setVariable('U2U_SEND_MESSAGE', $_ARRAYLANG['TXT_U2U_ENTRY_ADD_SUCCESS_MESSAGE']);
                $this->_objTpl->parse('u2u_send_confirm_success');
            }
            // $this->_objTpl->hideBlock('u2u_send_confirm_error');
               $this->showEntries();
        }
    }

    /**
     * send notification email
     *
     */
    function sendNotificationMail($fromId, $toId) {
        global $_CONFIG;

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $strName = $this->_getName($fromId);
        $strReceiverName = $this->_getName($toId);
        $toEmail=$this->_getEmail($toId);

        $from            = $this->_getEmailFromDetails();
        $subject         = $this->_getEmailSubjectDetails();
        $messageContent  = $this->_getEmailMessageDetails();

        $strMailSubject     = str_replace(  array('[senderName]',       '[receiverName]',             '[domainName]'),
                                            array($strName['username'], $strReceiverName['username'], $_CONFIG['domainUrl']),
                                            $subject['subject']);

        $strMailBody     = str_replace(  array('[senderName]',       '[receiverName]',             '[domainName]'),
                                         array($strName['username'], $strReceiverName['username'], $_CONFIG['domainUrl']),
                                         $messageContent['email_message']);
        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $from['from']);
        $objMail->AddAddress($toEmail['email']);
        $objMail->Subject     = $strMailSubject;//$strMailSubject;
        $objMail->IsHTML(true);
        $objMail->Body        = $strMailBody;
        $objMail->Send();
    }

    /**
     * Merge the array of Bcc as well Receipents
     * explodes the array by semicolon
     */
    function arrayMerge() {
        $string1 = $_REQUEST['recipients'];
        $string2 = $_REQUEST['bcc'];
        if($_REQUEST['rcpt_name'] !=  "") {
            $exp1[]=$_REQUEST['rcpt_name'];
        } else {
            if((!empty($string1)) && (!empty($string2))) {
                $exp1 = explode(";",$string1);
                $count = count($exp1);
                if($exp1[$count-1]=="") {
                    array_pop($exp1);
                }
                $exp2 = explode(";",$string2);
                $count = count($exp2);
                if($exp2[$count-1]=="") {
                    array_pop($exp2);
                }
                $exp1 = array_merge($exp1,$exp2);
            } else if(!empty($string1)) {
                $exp1 = explode(";",$string1);
                $count = count($exp1);
                if($exp1[$count-1]=="") {
                    array_pop($exp1);
                }
            } else {
                $exp1=explode(";",$string2);
                $count=count($exp1);
                if($exp1[$count-1]=="") {
                   array_pop($exp1);
                }
            }
        }
        return $exp1;
    }

    /**
     * Store the values of the messages into the Databases..
     *
     * @global   $_ARRAYLANG  $objDatabase $_CORELANG
     */
    function insertEntryDataMessage($errArray) {
        global $_ARRAYLANG, $objDatabase,$_CORELANG;

        foreach($errArray as $userID => $strValue) {
           $insMessageLog      = 'INSERT
                                   INTO    `'.DBPREFIX.'module_u2u_message_log`
                                   SET  `message_title` = "'.$errArray[$userID]['title'].'",
                                        `message_text`  = "'.$strValue["private_message"].'"';
           $objDatabase->Execute($insMessageLog);
           $messageID = $objDatabase->insert_id();
           $insSentMessages    = 'INSERT
                                   INTO    `'.DBPREFIX.'module_u2u_sent_messages`
                                   SET      `userid`                =   '.$strValue["sending_userid"].',
                                            `message_id`            =   '.$messageID.',
                                            `receiver_id`           =   '.$errArray[$userID]['receipents_userid'].',
                                            `mesage_open_status`    =    "0",
                                            `date_time`             =   "'.date("Y-m-d,h:i:s ").'"';
            $executeMessages=$objDatabase->Execute($insSentMessages);
            $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_U2U_ENTRY_ADD_SUCCESS'];
        }
    }

    /**
     * Show the message when the user clicks on the notification
     *
     * @global   $_ARRAYLANG  $objDatabase $_CORELANG
     */
    function showMessage() {
        global $_ARRAYLANG, $objDatabase,$_CORELANG;

        if(!empty($_GET["msgID"])) {
            $messageID=$_GET["msgID"];
        }
        $arrMessage=$this->createEntryShowMessage($messageID);
        if($_REQUEST['status']=="outboxmsg") {
            $this->_objTpl->setVariable(array(
                                          'PRIVATE_MESSAGE_ID'                 =>  $messageID,
                                          'PRIVATE_MESSAGE_TITLE'              =>  $arrMessage["message_title"],
                                          'PRIVATE_MESSAGE_TEXT'               =>  \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput($arrMessage["message"]),
                                          'U2U_USER_JOINED'                    =>  $arrMessage["registerd_date"],
                                          'MESSAGE_AUTHOR_NAME'                =>  $arrMessage["username"],
                                          'MESSAGE_SENT_DATE'                  =>  $arrMessage["date_time"],
                                          'TXT_PRIVATE_MESSAGE'                =>  $_ARRAYLANG['TXT_U2U_PRIVATE_MESSAGE'],
                                          'TXT_U2U_ENTRY_ADD_SUCCESS_MESSAGE'  =>  $_ARRAYLANG['TXT_U2U_ENTRY_ADD_SUCCESS_MESSAGE'],
                                          'TXT_U2U_AUTHOR'                     =>  $_ARRAYLANG['TXT_U2U_RECEIPENT_USER_OUTBOX'],
                                          'TXT_U2U_MESSAGE_SENT_DATE'          =>  $_ARRAYLANG['TXT_U2U_MESSAGE_SENT_DATE'],
                                          'TXT_U2U_OUTBOX_SEND_MESSAGE'        =>  $_ARRAYLANG['TXT_U2U_OUTBOX_SEND_MESSAGE'],
                                          'TXT_U2U_USER_JOINED_DATE'           =>  $_ARRAYLANG['TXT_U2U_USER_JOINED_DATE'],
                                          'TXT_U2U_DELETE_THIS_MESSAGE'        =>  $_ARRAYLANG['TXT_U2U_DELETE_THIS_MESSAGE'],
                                          'TXT_U2U_DELETE_MESSAGE'             =>  $_ARRAYLANG['TXT_U2U_DELETE_MESSAGE'],
                                          'TXT_U2U_DELETE_STRING'              =>  $_ARRAYLANG['TXT_U2U_DELETE_STRING']
          ));
          $this->_objTpl->hideBlock('showForwardandReply');
       } else {
        $this->_objTpl->setVariable(array(
                                          'PRIVATE_MESSAGE_ID'                 =>  $messageID,
                                          'PRIVATE_MESSAGE_TITLE'              =>  $arrMessage["message_title"],
                                          'PRIVATE_MESSAGE_TEXT'               =>  \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput($arrMessage["message"]),
                                          'U2U_USER_JOINED'                    =>  $arrMessage["registerd_date"],
                                          'MESSAGE_AUTHOR_NAME'                =>  $arrMessage["username"],
                                          'MESSAGE_SENT_DATE'                  =>  $arrMessage["date_time"],
                                          'TXT_PRIVATE_MESSAGE'                =>  $_ARRAYLANG['TXT_U2U_PRIVATE_MESSAGE'],
                                          'TXT_U2U_ENTRY_ADD_SUCCESS_MESSAGE'  =>  $_ARRAYLANG['TXT_U2U_ENTRY_ADD_SUCCESS_MESSAGE'],
                                          'TXT_U2U_AUTHOR'                     =>  $_ARRAYLANG['TXT_U2U_AUTHOR'],
                                          'TXT_U2U_MESSAGE_SENT_DATE'          =>  $_ARRAYLANG['TXT_U2U_MESSAGE_SENT_DATE'],
                                          'TXT_U2U_USER_JOINED_DATE'           =>  $_ARRAYLANG['TXT_U2U_USER_JOINED_DATE'],
                                          'TXT_U2U_DELETE_THIS_MESSAGE'        =>  $_ARRAYLANG['TXT_U2U_DELETE_THIS_MESSAGE'],
                                          'TXT_U2U_DELETE_MESSAGE'             =>  $_ARRAYLANG['TXT_U2U_DELETE_MESSAGE'],
                                          'TXT_U2U_DELETE_STRING'              =>  $_ARRAYLANG['TXT_U2U_DELETE_STRING'],
                                          'TXT_U2U_FORWARD'                    =>  $_ARRAYLANG['TXT_U2U_FORWARD'],
                                          'TXT_U2U_REPLY'                      =>  $_ARRAYLANG['TXT_U2U_REPLY']

       ));
       $this->_objTpl->hideBlock('sendMessageOutbox');
      }
    }
}
?>
