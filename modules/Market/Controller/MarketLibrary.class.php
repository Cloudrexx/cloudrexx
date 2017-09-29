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
 * Market library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_market
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Market\Controller;

/**
 * Market library
 *
 * External functions for the market
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_market
 * @todo        Edit PHP DocBlocks!
 */
class MarketLibrary
{
    /**
     * @var array specialFields
     * @access protected
     */
    protected $specialFields = array();

    function getCategories()
    {
        global $objDatabase;

        $this->settings = $this->getSettings();

        if  ($this->settings['indexview']['value'] == 1) {
            $order = "name";
        } else {
            $order = "displayorder";
        }

        $objResultCategories = $objDatabase->Execute('SELECT * FROM '.DBPREFIX.'module_market_categories ORDER BY '.$order.'');
           if ($objResultCategories !== false){
               while (!$objResultCategories->EOF) {
                   $this->categories[$objResultCategories->fields['id']]['id']                 = $objResultCategories->fields['id'];
                   $this->categories[$objResultCategories->fields['id']]['name']                 = $objResultCategories->fields['name'];
                   $this->categories[$objResultCategories->fields['id']]['description']         = $objResultCategories->fields['description'];
                   $this->categories[$objResultCategories->fields['id']]['order']                 = $objResultCategories->fields['displayorder'];
                   $this->categories[$objResultCategories->fields['id']]['status']             = $objResultCategories->fields['status'];
                   $objResultCategories->MoveNext();
               }
           }
    }



    function getEntries($orderBy, $where, $like) {

        global $objDatabase;

        if($orderBy != ''){
            $orderBy = 'ORDER BY '.contrexx_addslashes($orderBy);
        }

        if($where != '' && $like != ''){
            $where = "WHERE ".contrexx_input2db($where)." LIKE ".contrexx_input2db($like);
        }
        $specFieldCount = $objDatabase->Execute("SELECT COUNT(*) AS `count` FROM `" . DBPREFIX . "module_market_spez_fields`");
        $specFieldCount = $specFieldCount->fields['count'];
        $objResultEntries = $objDatabase->Execute('SELECT * FROM '.DBPREFIX.'module_market '.$where.' '.$orderBy);
           if ($objResultEntries !== false){
               while (!$objResultEntries->EOF) {
                   $this->entries[$objResultEntries->fields['id']]['id']                 = $objResultEntries->fields['id'];
                   $this->entries[$objResultEntries->fields['id']]['type']             = $objResultEntries->fields['type'];
                   $this->entries[$objResultEntries->fields['id']]['title']             = $objResultEntries->fields['title'];
                   $this->entries[$objResultEntries->fields['id']]['color']             = $objResultEntries->fields['color'];
                   $this->entries[$objResultEntries->fields['id']]['description']         = $objResultEntries->fields['description'];
                   $this->entries[$objResultEntries->fields['id']]['premium']             = $objResultEntries->fields['premium'];
                   $this->entries[$objResultEntries->fields['id']]['picture']             = $objResultEntries->fields['picture'];
                   $this->entries[$objResultEntries->fields['id']]['catid']             = $objResultEntries->fields['catid'];
                   $this->entries[$objResultEntries->fields['id']]['price']             = $objResultEntries->fields['price'];
                   $this->entries[$objResultEntries->fields['id']]['regdate']             = $objResultEntries->fields['regdate'];
                   $this->entries[$objResultEntries->fields['id']]['enddate']             = $objResultEntries->fields['enddate'];
                   $this->entries[$objResultEntries->fields['id']]['userid']             = $objResultEntries->fields['userid'];
                   $this->entries[$objResultEntries->fields['id']]['name']             = $objResultEntries->fields['name'];
                   $this->entries[$objResultEntries->fields['id']]['email']             = $objResultEntries->fields['email'];
                   $this->entries[$objResultEntries->fields['id']]['userdetails']         = $objResultEntries->fields['userdetails'];
                   $this->entries[$objResultEntries->fields['id']]['status']             = $objResultEntries->fields['status'];
                   $this->entries[$objResultEntries->fields['id']]['regkey']             = $objResultEntries->fields['regkey'];
                   $this->entries[$objResultEntries->fields['id']]['sort_id']             = $objResultEntries->fields['sort_id'];
                   for ($i = 1; $i <= $specFieldCount; ++$i) {
                       $this->entries[$objResultEntries->fields['id']]['spez_field_' . $i]      = $objResultEntries->fields['spez_field_' . $i];
                   }
                   $objResultEntries->MoveNext();
               }
           }
    }



    function countEntries($catId) {

        global $objDatabase;

        $objResultCount = $objDatabase->Execute('SELECT id FROM '.DBPREFIX.'module_market WHERE catid = '.contrexx_addslashes($catId).' AND status =1');
        if($objResultCount !== false){
            $count = $objResultCount->RecordCount();
        }

        return $count;
    }



    function getSettings(){
        global $objDatabase;

        //get settings
        $objResult = $objDatabase->Execute("SELECT name, value, type FROM ".DBPREFIX."module_market_settings");
        if($objResult !== false){
            while(!$objResult->EOF){
                $settings[$objResult->fields['name']] = $objResult->fields['value'];
                $objResult->MoveNext();
            }
        }

        return $settings;
    }

    /**
     * Insert the advertisement entry
     *
     * @param integer $backend
     *
     * @return null
     */

    public function insertEntry($backend){
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $settings = $this->getSettings();

        if (!$backend && $settings['useTerms'] && !isset($_POST['confirm'])) {
            $this->strErrMessage = $_ARRAYLANG['TXT_MARKET_CONFIRM_TERMS'];
            return;
        }

        if ($_POST['uploadImage'] != "") {
            $picture = $this->uploadPicture();
        } elseif (isset($_POST['picOld'])) {
            $picture = $this->copyPicture($_POST['picOld']);
        } else{
            $picture = "";
        }

        if($picture != "error"){
            if($_POST['forfree'] == 1){
                $price = "forfree";
            }elseif($_POST['agreement'] == 1){
                $price = "agreement";
            }else{
                $price = contrexx_addslashes($_POST['price']);
            }

            $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
            $tempDays     = date("d");
            $tempMonth     = date("m");
            $tempYear     = date("Y");
            $enddate  = mktime(0, 0, 0, $tempMonth, $tempDays+$_POST['days'],  $tempYear);

            if($backend == 1){
                $status     = '1';
                $regdate    = $today;
                $key        = '';
            }else{
                $status     = '0';
                $regdate    = '';
                $rand          = rand(10, 99);
                $key        = md5($rand.$today);
                $key        = substr($key,0 ,6);
            }

            $objFWUser = \FWUser::getFWUserObject();
            $specFields = $this->getSpecialFieldsQueryPart($objDatabase, $_POST);
            $objResult = $objDatabase->Execute("INSERT INTO ".DBPREFIX."module_market SET
                                type='".contrexx_addslashes($_POST['type'])."',
                                  title='".contrexx_addslashes($_POST['title'])."',
                                  color='".contrexx_addslashes($_POST['color'])."',
                                  description='".contrexx_addslashes($_POST['description'])."',
                                premium='".contrexx_addslashes($_POST['premium'])."',
                                  picture='".contrexx_addslashes($picture)."',
                                  catid='".contrexx_addslashes($_POST['cat'])."',
                                  price='".$price."',
                                  regdate='".$regdate."',
                                  enddate='".$enddate."',
                                  userid='".($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0)."',
                                  name='".contrexx_addslashes($_POST['name'])."',
                                  email='".contrexx_addslashes($_POST['email'])."',
                                  userdetails='".contrexx_addslashes($_POST['userdetails'])."', ".
                                  $specFields.",
                                  regkey='".$key."',
                                  status='".$status."'");

            if($objResult !== false){
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_ADD_SUCCESS'];
                if($backend == 0 && $settings['confirmFrontend']){
                    $entryId = $objDatabase->Insert_ID();
                    $this->sendCodeMail($entryId);
                    return $entryId;
                }
            }else{
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }else{
            $this->strErrMessage = $_ARRAYLANG['TXT_MARKET_IMAGE_UPLOAD_ERROR'];
        }
    }



    function sendCodeMail($entryId){

        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        //entrydata
        $objResult = $objDatabase->Execute("SELECT id, title, name, userid, email, regkey FROM ".DBPREFIX."module_market WHERE id='".contrexx_addslashes($entryId)."' LIMIT 1");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $entryMail            = $objResult->fields['email'];
                $entryName            = $objResult->fields['name'];
                $entryTitle            = $objResult->fields['title'];
                $entryUserid        = $objResult->fields['userid'];
                $entryKey            = $objResult->fields['regkey'];
                $objResult->MoveNext();
            };
        }

        //assesuserdata
        $objResult = $objDatabase->Execute("SELECT email, username FROM ".DBPREFIX."access_users WHERE id='".$entryUserid."' LIMIT 1");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $userUsername        = $objResult->fields['username'];
                $objResult->MoveNext();
            };
        }

        //get mail content n title
        $objResult = $objDatabase->Execute("SELECT title, content, active, mailcc, mailto FROM ".DBPREFIX."module_market_mail WHERE id='2'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $mailTitle            = $objResult->fields['title'];
                $mailContent        = $objResult->fields['content'];
                $mailCC                = $objResult->fields['mailcc'];
                $mailTo                = $objResult->fields['mailto'];
                $mailOn                = $objResult->fields['active'];
                $objResult->MoveNext();
            };
        }


        $array = explode('; ',$mailCC);
        $url    = $_SERVER['SERVER_NAME'].ASCMS_PATH_OFFSET;
        $now     = date(ASCMS_DATE_FORMAT);

        //replase placeholder
        $array_1 = array('[[EMAIL]]', '[[NAME]]', '[[TITLE]]', '[[ID]]', '[[CODE]]', '[[URL]]', '[[DATE]]', '[[USERNAME]]');
        $array_2 = array($entryMail, $entryName, $entryTitle, $entryId, $entryKey, $url, $now, $userUsername);


        for($x = 0; $x < 8; $x++){
          $mailTitle = str_replace($array_1[$x], $array_2[$x], $mailTitle);
          $mailContent = str_replace($array_1[$x], $array_2[$x], $mailContent);
        }

        //create mail
        $fromName    = $_CONFIG['coreAdminName']." - ".$url;
        $fromMail    = $_CONFIG['coreAdminEmail'];
        $subject     = $mailTitle;
        $message     = $mailContent;

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $objMail->SetFrom($fromMail, $fromName);
        $objMail->Subject = $subject;
        $objMail->IsHTML(false);
        $objMail->Body = $message;

        if ($mailTo == 'admin') {
            $addressee = $fromMail;
        } else {
            $addressee = $entryMail;
        }

        if ($mailOn == 1) {
            $objMail->AddAddress($addressee);
            $objMail->Send();
            $objMail->ClearAddresses();
        }

        // Email message
        foreach ($array as $toCC) {
            // Email message
            if (!empty($toCC)) {
                $objMail->AddAddress($toCC);
                $objMail->Send();
                $objMail->ClearAddresses();
            }
        }
    }


    function sendMail($entryId){

        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        //entrydata
        $objResult = $objDatabase->Execute("SELECT id, title, name, userid, email FROM ".DBPREFIX."module_market WHERE id='".contrexx_addslashes($entryId)."' LIMIT 1");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $entryMail            = $objResult->fields['email'];
                $entryName            = $objResult->fields['name'];
                $entryTitle            = $objResult->fields['title'];
                $entryUserid        = $objResult->fields['userid'];
                $objResult->MoveNext();
            };
        }

        //assesuserdata
        $objResult = $objDatabase->Execute("SELECT email, username FROM ".DBPREFIX."access_users WHERE id='".$entryUserid."' LIMIT 1");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
// TODO: Never used
//                $userMail            = $objResult->fields['email'];
                $userUsername        = $objResult->fields['username'];
                $objResult->MoveNext();
            };
        }

        //get mail content n title
        $objResult = $objDatabase->Execute("SELECT title, content, active, mailcc FROM ".DBPREFIX."module_market_mail WHERE id='1'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $mailTitle        = $objResult->fields['title'];
                $mailContent    = $objResult->fields['content'];
                $mailCC            = $objResult->fields['mailcc'];
                $mailOn            = $objResult->fields['active'];
                $objResult->MoveNext();
            };
        }


        if($mailOn == 1){
            $array = explode('; ',$mailCC);
            $url    = $_SERVER['SERVER_NAME'].ASCMS_PATH_OFFSET;
            $link    = "http://".$url."/index.php?section=Market&cmd=detail&id=".$entryId;
            $now     = date(ASCMS_DATE_FORMAT);

            //replase placeholder
            $array_1 = array('[[EMAIL]]', '[[NAME]]', '[[TITLE]]', '[[ID]]', '[[LINK]]', '[[URL]]', '[[DATE]]', '[[USERNAME]]');
            $array_2 = array($entryMail, $entryName, $entryTitle, $entryId, $link, $url, $now, $userUsername);


            for($x = 0; $x < 8; $x++){
              $mailTitle = str_replace($array_1[$x], $array_2[$x], $mailTitle);
              $mailContent = str_replace($array_1[$x], $array_2[$x], $mailContent);
            }

            //create mail
            $to         = $entryMail;
            $fromName    = $_CONFIG['coreAdminName']." - ".$url;
            $fromMail    = $_CONFIG['coreAdminEmail'];
            $subject     = $mailTitle;
            $message     = $mailContent;

            $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

            $objMail->SetFrom($fromMail, $fromName);
            $objMail->Subject = $subject;
            $objMail->IsHTML(false);
            $objMail->Body = $message;
            $objMail->AddAddress($to);
            $objMail->Send();
            $objMail->ClearAddresses();

            foreach ($array as $toCC) {
                // Email message
                if (!empty($toCC)) {
                    $objMail->AddAddress($toCC);
                    $objMail->Send();
                    $objMail->ClearAddresses();
                }
            }
        }
    }

    /**
     * Move the uploaded image to destination path from the temp path
     *
     * @return mixed $status | false
     */

    public function uploadPicture()
    {

        $status = "";
        $path   = "pictures/";

        //check file array
        $uploaderId = isset($_POST['marketUploaderId'])
                      ? contrexx_input2raw($_POST['marketUploaderId'])
                      : 0;
        $fileName   = isset($_POST['uploadImage'])
                      ? contrexx_input2raw($_POST['uploadImage'])
                      : 0;
        if (empty($uploaderId) || empty($fileName)) {
            return false;
        }
        //get file info
        $cx  = \Cx\Core\Core\Controller\Cx::instanciate();
        $objSession = $cx->getComponent('Session')->getSession();
        $tmpFile    = $objSession->getTempPath() . '/' . $uploaderId . '/' . $fileName;

        if (!\Cx\Lib\FileSystem\FileSystem::exists($tmpFile)) {
            return false;
        }

        if ($fileName != '' && \FWValidator::is_file_ending_harmless($fileName)) {
            //check extension
            $info = pathinfo($fileName);
            $exte = $info['extension'];
            $exte = (!empty($exte)) ? '.' . $exte : '';
            $part1 = substr($fileName, 0, strlen($fileName) - strlen($exte));
            $rand = rand(10, 99);
            $fileName = md5($rand . $fileName) . $exte;

            //check file
// TODO: $x is not defined
            $x = 0;
            if (file_exists($this->mediaPath . $path . $fileName)) {
                $fileName = $rand . $part1 . '_' . (time() + $x) . $exte;
                $fileName = md5($fileName) . $exte;
            }

            //Move the uploaded file to the path specified in the variable $this->mediaPath
            try {
                $objFile = new \Cx\Lib\FileSystem\File($tmpFile);
                if ($objFile->move($this->mediaPath . $path . $fileName, false)) {
                    $objFile = new \File();
                    $objFile->setChmod($this->mediaPath, $this->mediaWebPath, $path . $fileName);
                    $status = $fileName;
                } else {
                    $status = "error";
                }
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
            }
        } else {
            $status = "error";
        }

        return $status;
    }

    function copyPicture($fileName)
    {
        $fileNameOri = $fileName;

        if (!empty($fileName)) {
            $path            = "pictures/";

            $info     = pathinfo($fileName);
            $exte     = $info['extension'];
            $exte     = (!empty($exte)) ? '.' . $exte : '';
            $part1    = substr($fileName, 0, strlen($fileName) - strlen($exte));
            $rand      = rand(10, 99);
            $fileName = md5($rand.$fileName).$exte;

            //check file
            // TODO: $x is not defined
            $x = 0;
            if(file_exists($this->mediaPath.$path.$fileName)){
                $fileName = $rand.$part1 . '_' . (time() + $x) . $exte;
                $fileName = md5($fileName).$exte;
            }

            $objFile = new \File();
            $objFile->copyFile($this->mediaPath.$path, $fileNameOri, $this->mediaPath.$path, $fileName);
            $objFile->setChmod($this->mediaPath, $this->mediaWebPath, $path.$fileName);
            return $fileName;
        } else {
            return '';
        }
    }

    function removeEntry($array){

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        foreach($array as $entryId) {
               $status = "";
               $objResult = $objDatabase->Execute('SELECT picture FROM '.DBPREFIX.'module_market WHERE id = '.$entryId.' LIMIT 1');
            if($objResult !== false){
                $picture = $objResult->fields['picture'];
            }

            if($picture != ''){
                $objFile = new \File();
                $status = $objFile->delFile($this->mediaPath, $this->mediaWebPath, "pictures/".$picture);
            }

            if($status != "error"){
                $objResultDel = $objDatabase->Execute('DELETE FROM '.DBPREFIX.'module_market WHERE id = '.$entryId.'');
                if($objResultDel !== false){
                    $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_DELETE_SUCCESS'];
                }else{
                    $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
                }
            }else{
                $this->strErrMessage = $_ARRAYLANG['TXT_MARKET_IMAGE_DELETE_ERROR'];
            }
        }
    }

    /**
     * Get the uploader object
     *
     * @return \Cx\Core_Modules\Uploader\Model\Entity\Uploader
     */
    public function getUploader() {
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        //set instance name so we are able to catch the instance with js
        $uploader->setCallback('marketUploaderCallback');
        $uploader->setOptions(array(
            'id' => 'marketUploader',
            'allowed-extensions' => array('jpg', 'jpeg', 'png', 'gif'),
            'data-upload-limit' => 1,
            'style' => 'display:none'
        ));
        return $uploader;
    }

    /**
     * Get the string to select, insert, update or compare special fields
     * without a leading or trailing decimal point
     *
     * In case of comparison the chaining operator will be added in front of
     * every special field
     * @param \ADOConnection    $dbCon              The database connection
     * @param array             $data               The data to insert or
     *                                              update the entry
     * @param string            $comparator         Relational operator
     *                                              i.e. LIKE
     * @param string            $compareValue       Value to use with relational
     *                                              operator
     * @param string            $chainingOperator   Operator to chain
     *                                              comparisons i.e. OR
     * @return string
     */
    protected function getSpecialFieldsQueryPart($dbCon, $data = null, $comparator = '', $compareValue = '', $chainingOperator = ',')
    {
        $specialFields = array();
        // get amount of special fields
        $specialFieldCount = count($this->specialFields);
        if (empty($specialFieldCount)) {
            $specialFieldCount = $dbCon->Execute("SELECT COUNT(*) AS `count` FROM `" . DBPREFIX . "module_market_spez_fields` WHERE `lang_id` = 1");
            $specialFieldCount = $specialFieldCount->fields['count'];
        }
        for ($i = 1; $i <= $specialFieldCount; ++$i) {
            $value = '';
            // Data needs to  be updated or inserted
            if (!empty($data)) {
                $value = '=\'' . contrexx_input2db($data['spez_' . $i]) . '\'';
            }
            // Special fields are used in WHERE or similar comparison statements
            if (
                 empty($data) &&
                !empty($comparator) &&
                !empty($compareValue)
            ) {
                $value = ' ' . $comparator . ' ' . $compareValue;
            }
            $specialFields[] = 'spez_field_' . $i . $value;
        }
        $specialFields = join(' '.$chainingOperator.' ', $specialFields);
        return $specialFields;
    }

    /**
     * Get an array with placeholders and their respective values for parsing
     * the special fields
     *
     * Array structure:
     * array() {
     *      'TXT_MARKET_SPEZ_FIELD_[SPEZ_FIELD_ID] => name stored in db,
     *      'MARKET_SPEZ_FIELD_[SPEZ_FIELD_ID] => value stored in entry,
     *      [...]
     * }
     * @param $dbCon    \ADOConnection          Database connection
     * @param $template \HTML_Template_Sigma    The template
     * @param $entries  array                   entry|entries which have special
     *                                          field values
     * @param $id       int                     id of entry which special values
     *                                          shall be parsed
     * @param string    $type                   Which placeholders shall be
     *                                          returned either txt, val or both
     *                                          defaults to both
     * @return array
     */
    protected function parseSpecialFields($dbCon, $template, $entries, $id = 0, $type = 'both') {
        // get the spez fields
        if (empty($this->specialFields)) {
            $objResult = $dbCon->Execute("SELECT id, value FROM ".DBPREFIX."module_market_spez_fields WHERE lang_id = '1'");
            if ($objResult !== false) {
                while(!$objResult->EOF) {
                    $this->specialFields[$objResult->fields['id']] = $objResult->fields['value'];
                    $objResult->MoveNext();
                }
            }
        }

        $specialVariables = array();
        if (isset($this->specialFields)) {
            foreach ($this->specialFields as $specialFieldId => $value) {
                if ($type == 'both' || $type = 'txt') {
                    $txtKey = 'TXT_MARKET_SPEZ_FIELD_' . $specialFieldId;
                    $specialVariables[$txtKey] = $value;
                }
                if ($type == 'both' || $type == 'val') {
                    $valueKey = 'MARKET_SPEZ_FIELD_' . $specialFieldId;
                    $entryKey = 'spez_field_' . $specialFieldId;
                    if (count($entries) > 1 || !empty($id)) {
                        $specialVariables[$valueKey] = $entries[$id][$entryKey];
                    } else {
                        $specialVariables[$valueKey] = $entries[$entryKey];
                    }
                }
            }
        }
        $template->setVariable($specialVariables);
    }

}
