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
 * Market
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_market
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Market\Controller;

/**
 * Market
 *
 * Demo market class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_market
 */
class MarketManager extends MarketLibrary
{

    var $_objTpl;
    var $_pageTitle;
    var $strErrMessage = '';
    var $strOkMessage = '';
    var $categories = array();
    var $entries = array();
    var $mediaPath;
    var $mediaWebPath;
    var $settings;

    /**
    * Constructor
    */
    function Market()
    {
        $this->__construct();
    }

    private $act = '';

    /**
    * PHP5 constructor
    *
    * @global array
    * @global array
    * @global HTML_Teplate_Sigma
    */
    function __construct() {

        global $_ARRAYLANG, $_CORELANG, $objTemplate;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Market/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $this->mediaPath = $cx->getWebsiteMediaMarketPath() . '/';
        $this->mediaWebPath = $cx->getWebsiteMediaMarketWebPath() . '/';
        $this->settings = $this->getSettings();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG, $_CORELANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION", "
            <a href='index.php?cmd=Market' class='".($this->act == '' ? 'active' : '')."'>".$_CORELANG['TXT_OVERVIEW']."</a>
            <a href='index.php?cmd=Market&amp;act=addCategorie' class='".($this->act == 'addCategorie' ? 'active' : '')."'>".$_CORELANG['TXT_NEW_CATEGORY']."</a>
            <a href='index.php?cmd=Market&amp;act=addEntry' class='".($this->act == 'addEntry' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEW_ENTRY']."</a>
            <a href='index.php?cmd=Market&amp;act=entries' class='".($this->act == 'entries' ? 'active' : '')."'>".$_ARRAYLANG['TXT_ENTRIES']."</a>
            <a href='index.php?cmd=Market&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_CORELANG['TXT_SETTINGS']."</a>");
    }

    /**
    * Set the backend page
    *
    * @access public
    * @global \Cx\Core\Html\Sigma
    */
    function getPage() {

        global $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act']="";
        }

        switch ($_GET['act']) {
            case 'addCategorie':
                \Permission::checkAccess(98, 'static');
                $this->addCategory();
            break;
            case 'editCategorie':
                \Permission::checkAccess(98, 'static');
                $this->editCategorie();
            break;
            case 'statusCategorie':
                \Permission::checkAccess(98, 'static');
                $this->statusCategorie();
                $this->overview();
            break;
            case 'deleteCategorie':
                \Permission::checkAccess(98, 'static');
                $this->deleteCategorie();
                $this->overview();
            break;
            case 'sortCategorie':
                \Permission::checkAccess(98, 'static');
                $this->sortCategorie();
                $this->overview();
            break;
            case 'addEntry':
                \Permission::checkAccess(98, 'static');
                $this->addEntry();
            break;
            case 'statusEntry':
                \Permission::checkAccess(98, 'static');
                $this->statusEntry();
                $this->entries();
            break;
            case 'deleteEntry':
                \Permission::checkAccess(98, 'static');
                $this->deleteEntry();
                $this->entries();
            break;
            case 'editEntry':
                \Permission::checkAccess(98, 'static');
                $this->editEntry();
            break;
            case 'copyEntry':
                \Permission::checkAccess(98, 'static');
                $this->editEntry(true);
            break;
            case 'entries':
                \Permission::checkAccess(98, 'static');
                $this->entries();
            break;
            case 'settings':
                \Permission::checkAccess(98, 'static');
                $this->sysSettings();
            break;
            default:
                \Permission::checkAccess(98, 'static');
                $this->overview();
            break;
        }


        $objTemplate->setVariable(array(
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT'                => $this->_objTpl->get(),
            'CONTENT_TITLE'                => $this->_pageTitle,
        ));
        $this->act = $_REQUEST['act'];
        $this->setNavigation();
        return $this->_objTpl->get();
    }


    /**
    * create categorie overview
    *
    * @access public
    * @global array
    * @global array
    */
    function overview()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_pageTitle = $_CORELANG['TXT_OVERVIEW'];
        $this->_objTpl->loadTemplateFile('module_market_overview.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_IMG_EDIT'                =>    $_CORELANG['TXT_EDIT'],
            'TXT_IMG_DEL'                =>    $_CORELANG['TXT_DELETE'],
            'TXT_STATUS'                =>    $_CORELANG['TXT_STATUS'],
            'TXT_NAME'                    =>    $_CORELANG['TXT_NAME'],
            'TXT_DESC'                    =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_ENTRIES_COUNT'            =>    $_ARRAYLANG['TXT_MARKET_AD_COUNT'],
            'TXT_ACTION'                =>    $_ARRAYLANG['TXT_MARKET_ACTION'],
            'TXT_SELECT_ACTION'            =>    $_CORELANG['TXT_SUBMIT_SELECT'],
            'TXT_SAVE_ORDER'            =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_SELECT_ALL'            =>    $_CORELANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'            =>    $_CORELANG['TXT_DESELECT_ALL'],
            'TXT_DELETE'                =>    $_CORELANG['TXT_DELETE'],
            'TXT_ACTIVATE'                =>    $_ARRAYLANG['TXT_MARKET_ACTIVATE'],
            'TXT_DEACTIVATE'            =>    $_ARRAYLANG['TXT_MARKET_DEACTIVATE'],
            'TXT_DELETE_CATEGORY'        =>    $_ARRAYLANG['TXT_MARKET_DELETE_ENTRIES']
        ));

        $this->getCategories();

        $i = 0;
        foreach (array_keys($this->categories) as $catId) {
            $this->categories[$catId]['status'] == 1 ? $led = 'led_green' : $led = 'led_red';
            $this->_objTpl->setVariable(array(
              'CAT_NAME'                =>    $this->categories[$catId]['name'],
              'CAT_ID'                =>    $catId,
              'CAT_DESCRIPTION'        =>    $this->categories[$catId]['description'],
              'CAT_COUNT_ENTRIES'        =>    $this->countEntries($catId),
              'CAT_SORTING'            =>    $this->categories[$catId]['order'],
              'CAT_ICON'                =>    $led,
              'CAT_ROWCLASS'            =>    (++$i % 2 ? 2 : 1),
              'CAT_STATUS'            =>    $this->categories[$catId]['status'],
            ));
            $this->_objTpl->parse('showCategories');
        }
    }



    /**
    * change status from categories
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function statusCategorie() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->getCategories();

        if (isset($_POST['selectedCategoryId'])) {
            foreach ($_POST['selectedCategoryId'] as $catId) {
                $id = $catId;
                $_POST['frmShowCategories_MultiAction'] == 'activate' ? $newStatus = 1 : $newStatus = 0;
                $objResult = $objDatabase->Execute('UPDATE '.DBPREFIX.'module_market_categories SET status = '.$newStatus.' WHERE id = '.$id.'');
                   if ($objResult !== false) {
                    $this->_statusMessage = $_ARRAYLANG['TXT_MARKET_STATUS_CHANGED'];
                   }else{
                       $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
                   }
            }
        }elseif ($_GET['id']) {
            $array = explode(',',$_GET['id']);
            $id     = $array[0];
            $status =  $array[1];

            $status == 1 ? $newStatus = 0 : $newStatus = 1;
            $objResult = $objDatabase->Execute('UPDATE '.DBPREFIX.'module_market_categories SET status = '.$newStatus.' WHERE id = '.$id.'');
               if ($objResult !== false) {
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_STATUS_CHANGED'];
               }else{
                   $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
               }
        }
    }


    /**
    * delete categories
    *
    * @access public
    * @global ADONewConnection
    * @global array
    */
    function deleteCategorie() {
        global $objDatabase, $_ARRAYLANG;

        $arrDelete = array();
        $i = 0;

        if (isset($_POST['selectedCategoryId'])) {
            foreach ($_POST['selectedCategoryId'] as $catId) {
                $arrDelete[$i] = $catId;
                $i++;
            }
        }elseif ($_GET['id']) {
            $arrDelete[$i] = $_GET['id'];
        }

           foreach ($arrDelete as $catId) {
            $objResult = $objDatabase->Execute('SELECT id FROM '.DBPREFIX.'module_market WHERE catid = '.$catId.'');
            if ($objResult !== false) {
                if ($objResult->RecordCount() >= 1) {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MARKET_CATEGORY_DELETE_ERROR'];
                }else{
                    $objResultDel = $objDatabase->Execute('DELETE FROM '.DBPREFIX.'module_market_categories WHERE id = '.$catId.'');
                    if ($objResultDel !== false) {
                        $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_CATEGORY_DELETE_SUCCESS'];
                    }else{
// TODO: $_CORELANG is not available here
//                        $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
                    }
                }
            }
        }
    }


    /**
    * set sort order for categories
    *
    * @access public
    * @global ADONewConnection
    * @global array
    */
    function sortCategorie() {
        global $objDatabase, $_ARRAYLANG;

        foreach ($_POST['sortCategory'] as $catId => $catSort) {
            $objDatabase->Execute('UPDATE '.DBPREFIX.'module_market_categories SET displayorder = '.$catSort.' WHERE id = '.$catId.'');
        }

        $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_CATEGORY_SORTING_UPDATED'];
    }


    /**
    * add a categorie
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function addCategory() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_pageTitle = $_CORELANG['TXT_NEW_CATEGORY'];
        $this->_objTpl->loadTemplateFile('module_market_category.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_TITLE'                =>    $_CORELANG['TXT_NEW_CATEGORY'],
            'TXT_NAME'                =>    $_CORELANG['TXT_NAME'],
            'TXT_DESCRIPTION'        =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_SAVE'                =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_STATUS'            =>    $_CORELANG['TXT_STATUS'],
            'TXT_STATUS_ON'            =>    $_ARRAYLANG['TXT_MARKET_ACTIVATE'],
            'TXT_STATUS_OFF'        =>    $_ARRAYLANG['TXT_MARKET_DEACTIVATE'],
            'TXT_FIELDS_REQUIRED'    =>    $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_FILL_FIELDS']
        ));

        $this->_objTpl->setVariable(array(
            'FORM_ACTION'            =>    "addCategorie",
            'CAT_STATUS_ON'            =>    "checked"
        ));

        if (isset($_POST['submitCat'])) {
            $objResult = $objDatabase->Execute("INSERT INTO ".DBPREFIX."module_market_categories SET
                                  name='".$_POST['name']."',
                                  description='".$_POST['description']."',
                                displayorder='0',
                                  status='".$_POST['status']."'");

            if ($objResult !== false) {
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_SUCCESS'];
            } else {
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
    }


    /**
    * edit a categories
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function editCategorie() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_pageTitle = $_ARRAYLANG['TXT_MARKET_CATEGORY_EDIT'];
        $this->_objTpl->loadTemplateFile('module_market_category.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_TITLE'                =>    $_ARRAYLANG['TXT_MARKET_CATEGORY_EDIT'],
            'TXT_NAME'                =>    $_CORELANG['TXT_NAME'],
            'TXT_DESCRIPTION'        =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_SAVE'                =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_STATUS'            =>    $_CORELANG['TXT_STATUS'],
            'TXT_STATUS_ON'            =>    $_CORELANG['TXT_ACTIVATED'],
            'TXT_STATUS_OFF'        =>    $_CORELANG['TXT_DEACTIVATED'],
            'TXT_FIELDS_REQUIRED'    =>    $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_FILL_FIELDS'],
            'TXT_STATUS_ON'         =>    $_ARRAYLANG['TXT_MARKET_ACTIVATE'],
            'TXT_STATUS_OFF'        =>    $_ARRAYLANG['TXT_MARKET_DEACTIVATE'],

        ));

        $this->_objTpl->setVariable(array(
            'FORM_ACTION'            =>    "editCategorie",
        ));

        if (isset($_REQUEST['id'])) {
            $catId = $_REQUEST['id'];
            $objResult = $objDatabase->Execute('SELECT name, description, status FROM '.DBPREFIX.'module_market_categories WHERE id = '.$catId.' LIMIT 1');
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    if ($objResult->fields['status'] == 1 ) {
                        $catStatusOn     = 'checked';
                        $catStatusOFF    = '';
                    }else{
                        $catStatusOn     = '';
                        $catStatusOFF    = 'checked';
                    }
                    $this->_objTpl->setVariable(array(
                        'CAT_ID'                =>    $catId,
                        'CAT_NAME'                =>    $objResult->fields['name'],
                        'CAT_DESCRIPTION'        =>    $objResult->fields['description'],
                        'CAT_STATUS_ON'            =>    $catStatusOn,
                        'CAT_STATUS_OFF'        =>    $catStatusOFF,
                    ));

                       $objResult->MoveNext();
                   }
            }
        }else{
            $this->overview();
        }

        if (isset($_POST['submitCat'])) {
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_categories SET name = '".$_POST['name']."', status = '".$_POST['status']."', description = '".$_POST['description']."' WHERE id = '".$_POST['id']."'");
            if ($objResult !== false) {
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_CATEGORY_EDIT_SUCCESSFULL'];
                $this->overview();
            }else{
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
    }



    /**
    * show market entries
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function entries() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_pageTitle = $_ARRAYLANG['TXT_ENTRIES'];
        $this->_objTpl->loadTemplateFile('module_market_entries.html',true,true);
        if (isset($_POST['market_store_order'])) {
            foreach ($_POST['entrySortNr'] as $sortEntryId => $sortNr) {
                $objDatabase->Execute('UPDATE `'.DBPREFIX.'module_market` SET `sort_id` = '.intval($sortNr).' WHERE `id` = '.intval($sortEntryId));
            }
        }

        if (!isset($_GET['catid'])) {
            $where     = '';
            $like     = '';
            $sortId    = '';
        }else{
            $where     = 'catid';
            $like     = intval($_GET['catid']);
            $sortId    = '&amp;catid='.$_GET['catid'];
        }

        if (isset($_POST['term'])) {
            $where     = 'title';
            $like     = "'%".contrexx_addslashes($_POST['term'])."%' OR description LIKE '%".contrexx_addslashes($_POST['term'])."%' OR id LIKE '%".intval($_POST['term'])."%'";
        }

        if (!isset($_SESSION['market'])) {
            $_SESSION['market'] = array();
        }

        // Sort
        if (empty ($_SESSION['market']['sort'])) {
            $_SESSION['market']['sort'] = 'title ASC';
        }
        if (isset($_GET['sort'])) {
            switch ($_GET['sort']) {
                case 'title':
                    $_SESSION['market']['sort'] =
                        ($_SESSION['market']['sort'] == "title DESC"
                            ? "title ASC" : "title DESC");
                    break;
                case 'type':
                    $_SESSION['market']['sort'] =
                        ($_SESSION['market']['sort'] == "type DESC"
                            ? "type ASC" : "type DESC");
                    break;
                case 'status':
                    $_SESSION['market']['sort'] =
                        ($_SESSION['market']['sort'] == "status DESC"
                            ? "status ASC" : "status DESC");
                    break;
                case 'addedby':
                    $_SESSION['market']['sort'] =
                        ($_SESSION['market']['sort'] == "userid DESC"
                            ? "userid ASC" : "userid DESC");
                    break;
                case 'regdate':
                    $_SESSION['market']['sort'] =
                        ($_SESSION['market']['sort'] == "regdate DESC"
                            ? "regdate ASC" : "regdate DESC");
                    break;
                case 'id':
                    $_SESSION['market']['sort'] =
                        ($_SESSION['market']['sort'] == "id DESC"
                            ? "id ASC" : "id DESC");
                    break;
            }
        }

        $this->_objTpl->setVariable(array(
            'TXT_CORE_MARKET_TITLE'     => $_CORELANG['TXT_CORE_MARKET_TITLE'],
            'TXT_CORE_FILTER'           => $_CORELANG['TXT_CORE_FILTER'],
            'TXT_IMG_EDIT'                =>    $_CORELANG['TXT_EDIT'],
            'TXT_IMG_DEL'                =>    $_CORELANG['TXT_DELETE'],
            'TXT_IMG_COPY'                =>    $_CORELANG['TXT_COPY'],
            'TXT_STATUS'                =>    $_CORELANG['TXT_STATUS'],
            'TXT_CORE_SORTING_ORDER'    =>    $_CORELANG['TXT_CORE_SORTING_ORDER'],
            'TXT_DATE'                    =>    $_CORELANG['TXT_DATE'],
            'TXT_MARKET_TITLE'          =>    $_ARRAYLANG['TXT_MARKET_TITLE'],
            'TXT_DESC'                    =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_ACTION'                =>    $_ARRAYLANG['TXT_MARKET_ACTION'],
            'TXT_TYP'                    =>    $_CORELANG['TXT_TYPE'],
            'TXT_ADDEDBY'                =>    $_ARRAYLANG['TXT_MARKET_ADDEDBY'],
            'TXT_SELECT_ACTION'            =>    $_CORELANG['TXT_SUBMIT_SELECT'],
            'TXT_SAVE_ORDER'            =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_SELECT_ALL'            =>    $_CORELANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'            =>    $_CORELANG['TXT_DESELECT_ALL'],
            'TXT_DELETE'                =>    $_CORELANG['TXT_DELETE'],
            'TXT_ACTIVATE'                =>    $_ARRAYLANG['TXT_MARKET_ACTIVATE'],
            'TXT_DEACTIVATE'            =>    $_ARRAYLANG['TXT_MARKET_DEACTIVATE'],
            'TXT_DELETE_CATEGORY'        =>    $_ARRAYLANG['TXT_MARKET_DELETE_ENTRIES'],
            'ENTRY_SORT_ID'                =>  $sortId,
            'TXT_DELETE_ENTRY'            =>  $_ARRAYLANG['TXT_MARKET_DELETE_ENTRIES'],
            'TXT_SEARCH'                =>    $_ARRAYLANG['TXT_SEARCH'],
        ));

        $this->getEntries($_SESSION['market']['sort'], $where, $like);

        if (count($this->entries) != 0) {
               $i = 0;
               foreach (array_keys($this->entries) as $entryId) {
                   $this->entries[$entryId]['status'] == 1 ? $led = 'led_green' : $led = 'led_red';
                   $this->entries[$entryId]['type'] == 'offer' ? $type = $_ARRAYLANG['TXT_MARKET_OFFER'] : $type = $_ARRAYLANG['TXT_MARKET_SEARCH'];
                   $i%2 ? $row = 2 : $row = 1;
                   $objResult = $objDatabase->Execute('SELECT username FROM '.DBPREFIX.'access_users WHERE id = '.$this->entries[$entryId]['userid'].' LIMIT 1');
                if ($objResult !== false) {
                    $addedby = $objResult->fields['username'];
                }

                $this->entries[$entryId]['regdate'] == '' ? $date = 'KEY: '.$this->entries[$entryId]['regkey'] : $date = date("d.m.Y", $this->entries[$entryId]['regdate']);

                   $this->_objTpl->setVariable(array(
                    'ENTRY_TITLE'            =>    $this->entries[$entryId]['title'],
                    'ENTRY_DATE'            =>    $date,
                    'ENTRY_ID'                =>    $entryId,
                    'ENTRY_SORT_ORDER'          => $this->entries[$entryId]['sort_id'],
                    'ENTRY_DESCRIPTION'        =>    $this->entries[$entryId]['description'],
                    'ENTRY_TYPE'            =>    $type,
                    'ENTRY_ADDEDBY'            =>    $addedby,
                    'ENTRY_ICON'            =>    $led,
                    'ENTRY_ROWCLASS'        =>    $row,
                    'ENTRY_SORT_ID_STATUS'    =>  $sortId,
                    'ENTRY_STATUS'            =>    $this->entries[$entryId]['status'],
                ));

                $i++;
                   $this->_objTpl->parse('showEntries');
               }
        }else{
            $this->_objTpl->hideBlock('showEntries');
            $this->strErrMessage = $_ARRAYLANG['TXT_MARKET_NO_ENTRIES_FOUND'];
        }
    }


    /**
    * add a entree
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function addEntry() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_pageTitle = $_ARRAYLANG['TXT_NEW_ENTRY'];
        $this->_objTpl->loadTemplateFile('module_market_entry.html',true,true);

        $objFWUser = \FWUser::getFWUserObject();

        $this->getCategories();
        $categories = '';
        foreach (array_keys($this->categories) as $catId) {
            $categories .= '<option value="'.$catId.'">'.$this->categories[$catId]['name'].'</option>';
        }

        if ($this->settings['maxdayStatus'] == 1) {
            $daysOnline = '';
            for($x = $this->settings['maxday']; $x >= 1; $x--) {
                $daysOnline .= '<option value="'.$x.'">'.$x.'</option>';
            }

            $daysJS = 'if (days.value == "") {
                            errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_MARKET_DURATION'].'\n";
                       }
                       ';
        }
        //initialize and get uploader object
        $uploader = $this->getUploader();

        $this->_objTpl->setVariable(array(
            'TXT_TITLE'                        =>    $_ARRAYLANG['TXT_NEW_ENTRY'],
            'TXT_NAME'                        =>    $_CORELANG['TXT_NAME'],
            'TXT_MARKET_EMAIL'              => $_ARRAYLANG['TXT_MARKET_EMAIL'],
            'TXT_TITLE_ENTRY'                =>    $_ARRAYLANG['TXT_MARKET_TITLE'],
            'TXT_MARKET_COLOR'                 =>        $_ARRAYLANG['TXT_MARKET_COLOR'],
            'TXT_DESCRIPTION'                =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_SAVE'                        =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_FIELDS_REQUIRED'            =>    $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_FILL_FIELDS'],
            'TXT_THOSE_FIELDS_ARE_EMPTY'    =>    $_ARRAYLANG['TXT_MARKET_FIELDS_NOT_CORRECT'],
            'TXT_PICTURE'                    =>    $_ARRAYLANG['TXT_MARKET_IMAGE'],
            'TXT_CATEGORIE'                    =>    $_CORELANG['TXT_CATEGORY'],
            'TXT_PRICE'                        =>    $_ARRAYLANG['TXT_MARKET_PRICE'].$this->settings['currency'],
            'TXT_TYPE'                        =>    $_CORELANG['TXT_TYPE'],
            'TXT_OFFER'                        =>    $_ARRAYLANG['TXT_MARKET_OFFER'],
            'TXT_SEARCH'                    =>    $_ARRAYLANG['TXT_MARKET_SEARCH'],
            'TXT_FOR_FREE'                    =>    $_ARRAYLANG['TXT_MARKET_FREE'],
            'TXT_AGREEMENT'                    =>    $_ARRAYLANG['TXT_MARKET_ARRANGEMENT'],
            'TXT_END_DATE'                    =>    $_ARRAYLANG['TXT_MARKET_DURATION'],
            'END_DATE_JS'                    =>    $daysJS,
            'TXT_ADDED_BY'                    =>    $_ARRAYLANG['TXT_MARKET_ADDEDBY'],
            'TXT_USER_DETAIL'                =>    $_ARRAYLANG['TXT_MARKET_USERDETAILS'],
            'TXT_DETAIL_SHOW'                =>    $_ARRAYLANG['TXT_MARKET_SHOW_IN_ADVERTISEMENT'],
            'TXT_DETAIL_HIDE'                =>    $_ARRAYLANG['TXT_MARKET_NO_SHOW_IN_ADVERTISEMENT'],
            'TXT_PREMIUM'                    =>    $_ARRAYLANG['TXT_MARKET_MARK_ADVERTISEMENT'],
            'TXT_MARKET_CHOOSE_FILE'         => $_ARRAYLANG['TXT_MARKET_CHOOSE_FILE'],
            'MARKET_UPLOADER_CODE'           => $uploader->getXHtml(),
            'MARKET_UPLOADER_ID'             => $uploader->getId()
        ));

        if ($this->settings['maxdayStatus'] != 1) {
            $this->_objTpl->hideBlock('end_date_dropdown');
        }

        $objResult = $objDatabase->Execute("SELECT id, name, value FROM ".DBPREFIX."module_market_spez_fields WHERE lang_id = '1' AND active='1' ORDER BY id DESC");
          if ($objResult !== false) {
            $i = 0;
            while (!$objResult->EOF) {
                $this->_objTpl->setCurrentBlock('spez_fields');

                ($i % 2)? $class = "row1" : $class = "row2";
                $input = '<input type="text" name="spez_'.$objResult->fields['id'].'" style="width: 300px;" maxlength="100">';

                // initialize variables
                $this->_objTpl->setVariable(array(
                    'SPEZ_FIELD_ROW_CLASS'        => $class,
                    'TXT_SPEZ_FIELD_NAME'        => $objResult->fields['value'],
                    'SPEZ_FIELD_INPUT'          => $input,
                ));

                $this->_objTpl->parse('spez_fields');
                $i++;
                $objResult->MoveNext();
            }
          }

        $this->_objTpl->setVariable(array(
            'FORM_ACTION'            => "addEntry",
            'CATEGORIES'            => $categories,
            'ENTRY_ADDEDBY'            => htmlentities($objFWUser->objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET),
            'ENTRY_USERDETAILS_ON'    => "checked",
            'ENTRY_TYPE_OFFER'        => "checked",
            'DAYS_ONLINE'            => $daysOnline
        ));

        if (isset($_POST['submitEntry'])) {
            $this->insertEntry('1');
        }
    }


    /**
    * change status from entries
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function statusEntry() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $arrStatus = array();
        $i=0;
        $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));

        if (isset($_POST['selectedEntryId'])) {
            foreach ($_POST['selectedEntryId'] as $entryId) {
                $_POST['frmShowEntries_MultiAction'] == 'activate' ? $newStatus = 0 : $newStatus = 1;

                $arrStatus[$i] = $entryId.",".$newStatus;
                $i++;
            }
        }elseif ($_GET['id']) {
            $arrStatus[$i] = $_GET['id'];
        }

        foreach ($arrStatus as $entryId) {
            $array         = explode(',',$entryId);
            $id         = $array[0];
            $status     = $array[1];

            if ($status == 0) {
                $objResultDate = $objDatabase->Execute('SELECT regdate FROM '.DBPREFIX.'module_market WHERE id = '.$id.' LIMIT 1');
                if ($objResultDate !== false) {
                    $regdate = $objResultDate->fields['regdate'];
                }

                if ($regdate == '') {
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market SET status='1', regdate = '".$today."', regkey='' WHERE id = '".$id."'");
                }else{
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market SET status='1' WHERE id = '".$id."'");
                }

                $this->sendMail($id);
            }else{
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market SET status='0' WHERE id = '".$id."'");
            }
        }

        if ($objResult !== false) {
            $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_STATUS_CHANGED'];
           }else{
           $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
           }
    }




    /**
    * delete a entree
    */
    function deleteEntry() {
        $arrDelete = array();
        $i = 0;

        if (isset($_POST['selectedEntryId'])) {
            foreach ($_POST['selectedEntryId'] as $entryId) {
                $arrDelete[$i] = $entryId;
                $i++;
            }
        }elseif ($_GET['id']) {
            $arrDelete[$i] = $_GET['id'];
        }

        $this->removeEntry($arrDelete);
    }


    /**
    * edit a entree
    *
    * @access public
    * @global ADONewConnction
    * @global array
    * @global array
    */
    function editEntry($copy = false) {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_pageTitle = $copy ? $_ARRAYLANG['TXT_COPY_ADVERTISEMENT'] : $_ARRAYLANG['TXT_EDIT_ADVERTISEMENT'];
        $this->_objTpl->loadTemplateFile('module_market_entry.html',true,true);
        //initialize and get uploader object
        $uploader = $this->getUploader();
        $this->_objTpl->setVariable(array(
            'TXT_TITLE'                        =>    $_ARRAYLANG['TXT_EDIT_ADVERTISEMENT'],
            'TXT_TITLE_ENTRY'                =>    $_ARRAYLANG['TXT_MARKET_TITLE'],
            'TXT_NAME'                        =>    $_CORELANG['TXT_NAME'],
            'TXT_E-MAIL'                    =>    $_CORELANG['TXT_EMAIL'],
            'TXT_MARKET_EMAIL'              => $_ARRAYLANG['TXT_MARKET_EMAIL'],
            'TXT_MARKET_COLOR'              =>        $_ARRAYLANG['TXT_MARKET_COLOR'],
            'TXT_DESCRIPTION'                =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_SAVE'                        =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_FIELDS_REQUIRED'            =>    $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_FILL_FIELDS'],
            'TXT_THOSE_FIELDS_ARE_EMPTY'    =>    $_ARRAYLANG['TXT_MARKET_FIELDS_NOT_CORRECT'],
            'TXT_PICTURE'                    =>    $_ARRAYLANG['TXT_MARKET_IMAGE'],
            'TXT_CATEGORIE'                    =>    $_CORELANG['TXT_CATEGORY'],
            'TXT_PRICE'                        =>    $_ARRAYLANG['TXT_MARKET_PRICE']." ".$this->settings['currency'],
            'TXT_TYPE'                        =>    $_CORELANG['TXT_TYPE'],
            'TXT_OFFER'                        =>    $_ARRAYLANG['TXT_MARKET_OFFER'],
            'TXT_SEARCH'                    =>    $_ARRAYLANG['TXT_MARKET_SEARCH'],
            'TXT_FOR_FREE'                    =>    $_ARRAYLANG['TXT_MARKET_FREE'],
            'TXT_AGREEMENT'                    =>    $_ARRAYLANG['TXT_MARKET_ARRANGEMENT'],
            'TXT_END_DATE'                    =>    $_ARRAYLANG['TXT_MARKET_DURATION'],
            'TXT_ADDED_BY'                    =>    $_ARRAYLANG['TXT_MARKET_ADDEDBY'],
            'TXT_ADDED_BY'                    =>    $_ARRAYLANG['TXT_MARKET_ADDEDBY'],
            'TXT_USER_DETAIL'                =>    $_ARRAYLANG['TXT_MARKET_USERDETAILS'],
            'TXT_DETAIL_SHOW'                =>    $_ARRAYLANG['TXT_MARKET_SHOW_IN_ADVERTISEMENT'],
            'TXT_DETAIL_HIDE'                =>    $_ARRAYLANG['TXT_MARKET_NO_SHOW_IN_ADVERTISEMENT'],
            'TXT_PREMIUM'                    =>    $_ARRAYLANG['TXT_MARKET_MARK_ADVERTISEMENT'],
            'FORM_ACTION'                    =>    $copy ? "addEntry" : "editEntry",
            'TXT_DAYS'                        =>    $_ARRAYLANG['TXT_MARKET_DAYS'],
            'TXT_MARKET_CHOOSE_FILE'         =>  $_ARRAYLANG['TXT_MARKET_CHOOSE_FILE'],
            'MARKET_UPLOADER_CODE'           => $uploader->getXHtml(),
            'MARKET_UPLOADER_ID'             => $uploader->getId()
        ));

        if (isset($_REQUEST['id'])) {
            $specFields = $this->getSpecialFieldsQueryPart($objDatabase);
            $entryId = $_REQUEST['id'];
            $objResult = $objDatabase->Execute('SELECT type, title, color, description, premium, picture, catid, price, regdate, enddate, userid, name, email, userdetails, ' . $specFields . ' FROM '.DBPREFIX.'module_market WHERE id = '.$entryId.' LIMIT 1');
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    //entry type
                    if ($objResult->fields['type'] == 'offer') {
                        $offer     = 'checked';
                        $search    = '';
                    }else{
                        $offer     = '';
                        $search    = 'checked';
                    }
                    //entry premium
                    if ($objResult->fields['premium'] == '1') {
                        $premium     = 'checked';
                    }else{
                        $premium    = '';
                    }
                    //entry price
                    if ($objResult->fields['price'] == 'forfree') {
                        $forfree     = 'checked';
                        $price         = '';
                        $agreement     = '';
                    }elseif ($objResult->fields['price'] == 'agreement') {
                        $agreement    = 'checked';
                        $price         = '';
                        $forfree     = '';
                    }else{
                        $price         = $objResult->fields['price'];
                        $forfree     = '';
                        $agreement     = '';
                    }
                    //entry user
                    $objResultUser = $objDatabase->Execute('SELECT username FROM '.DBPREFIX.'access_users WHERE id = '.$objResult->fields['userid'].' LIMIT 1');
                    if ($objResultUser !== false) {
                        $addedby = $objResultUser->fields['username'];
                    }
                    //entry userdetails
                    if ($objResult->fields['userdetails'] == '1') {
                        $userdetailsOn         = 'checked';
                        $userdetailsOff     = '';
                    }else{
                        $userdetailsOn         = '';
                        $userdetailsOff     = 'checked';
                    }
                    //entry picture
                    if ($objResult->fields['picture'] != '') {
                        $picture         = '<img src="'.$this->mediaWebPath.'pictures/'.$objResult->fields['picture'].'" border="0" alt="" /><br /><br />';
                    }else{
                        $picture         = '<img src="'.$this->mediaWebPath.'pictures/no_picture.gif" border="0" alt="" /><br /><br />';
                    }
                    //entry category
                    $this->getCategories();
                    $categories     = '';
                    $checked         = '';
                    foreach (array_keys($this->categories) as $catId) {
                        $catId == $objResult->fields['catid'] ? $checked = 'selected' : $checked = '';
                        $categories .= '<option value="'.$catId.'" '.$checked.'>'.$this->categories[$catId]['name'].'</option>';
                    }



                    //rest days
                    $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
                    $tempDays     = date("d", $today);
                    $tempMonth     = date("m", $today);
                    $tempYear     = date("Y", $today);
                    $tempDate = '';

                    $x=0;
                    while ($objResult->fields['enddate'] >= $tempDate):
                        $x++;
                        $tempDate  = mktime(0, 0, 0, $tempMonth, $tempDays+$x,  $tempYear);
                    endwhile;


                    $restDays = $x-1;
                    $daysOnline = '';
                    for($x = $this->settings['maxday']; $x >= 0; $x--) {
                        $restDays == $x ? $selected = 'selected' : $selected = '';
                        $daysOnline .= '<option value="'.$x.'" '.$selected.'>'.$x.'</option>';
                    }

                    //spez fields
                    $objSpezFields = $objDatabase->Execute("SELECT id, name, value FROM ".DBPREFIX."module_market_spez_fields WHERE lang_id = '1' AND active='1' ORDER BY id DESC");
                    if ($objSpezFields !== false) {
                        $i = 0;
                        while (!$objSpezFields->EOF) {
                            ($i % 2)? $class = "row1" : $class = "row2";
                            $input = '<input type="text" name="spez_'.$objSpezFields->fields['id'].'" value="'.$objResult->fields[$objSpezFields->fields['name']].'" style="width: 300px;" maxlength="100">';
                            // initialize variables
                            $this->_objTpl->setVariable(array(
                                'SPEZ_FIELD_ROW_CLASS'        => $class,
                                'TXT_SPEZ_FIELD_NAME'        => $objSpezFields->fields['value'],
                                'SPEZ_FIELD_INPUT'          => $input,
                            ));
                            $this->_objTpl->parse('spez_fields');
                            $i++;
                            $objSpezFields->MoveNext();
                        }
                    }

                    $this->_objTpl->setVariable(array(
                        'ENTRY_ID'                    =>    $entryId,
                        'ENTRY_TYPE_OFFER'            =>    $offer,
                        'ENTRY_TYPE_SEARCH'            =>    $search,
                        'ENTRY_TITLE'                =>    $objResult->fields['title'],
                        'ENTRY_COLOR'                =>    $objResult->fields['color'],
                        'ENTRY_DESCRIPTION'            =>    $objResult->fields['description'],
                        'ENTRY_PICTURE'                =>    $picture,
                        'ENTRY_PICTURE_OLD'            =>    $objResult->fields['picture'],
                        'CATEGORIES'                =>    $categories,
                        'ENTRY_PRICE'                =>    $price,
                        'ENTRY_FOR_FREE'            =>    $forfree,
                        'ENTRY_AGREEMENT'            =>    $agreement,
                        'ENTRY_PREMIUM'                =>    $premium,
                        'ENTRY_ADDEDBY'                =>    $addedby,
                        'ENTRY_ADDEDBY_ID'            =>    $objResult->fields['userid'],
                        'ENTRY_USERDETAILS_ON'        =>    $userdetailsOn,
                        'ENTRY_USERDETAILS_OFF'        =>    $userdetailsOff,
                        'DAYS_ONLINE'                =>    $daysOnline,
                        'ENTRY_NAME'                =>    $objResult->fields['name'],
                        'ENTRY_E-MAIL'                =>    $objResult->fields['email'],
                    ));
                       $objResult->MoveNext();
                   }
            }
        }else{
            $this->entries();
        }

        if (isset($_POST['submitEntry'])) {
            if ($copy) {
                return $this->insertEntry(1);
            }
            if ($_POST['uploadImage'] != "") {
                $picture = $this->uploadPicture();
/* TODO: Never used
                if ($picture != "error") {
                    $objFile = new \File();
                    $status = $objFile->delFile($this->mediaPath, $this->mediaWebPath, "pictures/".$_POST['picOld']);
                }
*/
            } else {
                $picture = $_POST['picOld'];
            }

            if ($picture != "error") {
                if ($_POST['forfree'] == 1) {
                    $price = "forfree";
                }elseif ($_POST['agreement'] == 1) {
                    $price = "agreement";
                }else{
                    $price = $_POST['price'];
                }

                $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
                $tempDays     = date("d");
                $tempMonth     = date("m");
                $tempYear     = date("Y");
                $enddate  = mktime(0, 0, 0, $tempMonth, $tempDays+$_POST['days'],  $tempYear);

                $specFields = $this->getSpecialFieldsQueryPart($objDatabase, $_POST);
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market SET
                                    type='".contrexx_addslashes($_POST['type'])."',
                                      title='".contrexx_addslashes($_POST['title'])."',
                                      color='".contrexx_addslashes($_POST['color'])."',
                                      description='".contrexx_addslashes($_POST['description'])."',
                                    premium='".contrexx_addslashes($_POST['premium'])."',
                                      picture='".$picture."',
                                      catid='".contrexx_addslashes($_POST['cat'])."',
                                      price='".$price."',
                                      enddate='".$enddate."',
                                      userid='".contrexx_addslashes($_POST['userid'])."',
                                      name='".contrexx_addslashes($_POST['name'])."',
                                      email='".contrexx_addslashes($_POST['email'])."',
                                      " . $specFields . ",
                                      userdetails='".contrexx_addslashes($_POST['userdetails'])."'
                                      WHERE id='".intval($_POST['id'])."'");

                if ($objResult !== false) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_EDIT_SUCCESSFULL'];
                    $this->entries();
                }else{
                    $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
                }
            }else{
                $this->strErrMessage = $_ARRAYLANG['TXT_MARKET_IMAGE_UPLOAD_ERROR'];
            }
        }
    }

    /**
    * show system settings
    *
    * @access public
    * @global array
    * @global array
    */
    function sysSettings()
    {
        global $_ARRAYLANG, $_CORELANG;
        $this->_objTpl->loadTemplateFile('module_market_settings.html',true,true);
        $this->_pageTitle = $_CORELANG['TXT_SETTINGS'];

        $this->_objTpl->setGlobalVariable(array(
            'TXT_SYSTEM'      => $_CORELANG['TXT_SETTINGS_MENU_SYSTEM'],
            'TXT_EMAIL'       => $_ARRAYLANG['TXT_MARKET_VALIDATION_EMAIL'],
            'TXT_EMAIL_CODE'  => $_ARRAYLANG['TXT_MARKET_CLEARING_EMAIL'],
            'TXT_SPEZ_FIELDS' => $_ARRAYLANG['TXT_MARKET_SPEZ_FIELDS'],
        ));

        if (!isset($_GET['tpl'])) {
            $_GET['tpl'] = "";
        }

        switch ($_GET['tpl']) {
            case 'system':
                $this->systemSettings();
                break;
            case 'email':
                $this->mailSettings();
                break;
            case 'email_code':
                $this->mail_codeSettings();
                break;
            case 'spez_fields':
                $this->spezfieldsSettings();
                break;
            default:
                $this->systemSettings();
                break;
        }

        $this->_objTpl->parse('requests_block');
    }

    /**
    * show settings for spezial fields
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function spezfieldsSettings() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_market_settings_spez_fields.html');

        $this->_objTpl->setVariable(array(
            'TXT_TITLE'               => $_ARRAYLANG['TXT_MARKET_SPEZ_FIELDS'],
            'TXT_STATUS'              => $_CORELANG['TXT_STATUS'],
            'TXT_NAME'                => $_CORELANG['TXT_NAME'],
            'TXT_SAVE'                => $_ARRAYLANG['TXT_SAVE'],
            'TXT_TYPE'                => $_CORELANG['TXT_TYPE'],
            'TXT_PLACEHOLDER_TITLE'   => $_ARRAYLANG['TXT_MARKET_PLACEHOLDER_TITLE'],
            'TXT_PLACEHOLDER_CONTENT' => $_ARRAYLANG['TXT_MARKET_PLACEHOLDER_CONTENT'],
        ));

        $i=0;

          $objResult = $objDatabase->Execute("SELECT id, name, value, type, active FROM ".DBPREFIX."module_market_spez_fields WHERE lang_id = '1' ORDER BY active DESC");
          if ($objResult !== false) {
            while (!$objResult->EOF) {
                $this->_objTpl->setCurrentBlock('spez_fields');

                ($i % 2)? $class = "row2" : $class = "row1";
                ($objResult->fields['active'] == 1)? $status = 'checked="checked"' : $status = "";
                ($objResult->fields['type'] == 1)? $type = "Textfeld" : $type = "Mehrzeiliges Textfeld";

                // initialize variables
                $this->_objTpl->setVariable(array(
                    'SPEZ_FIELD_ROWCLASS' => $class,
                    'SPEZ_FIELD_ID'       => $objResult->fields['id'],
                    'SPEZ_FIELD_TITLE'    => $objResult->fields['value'],
                    'SPEZ_FIELD_NAME'     => $objResult->fields['name'],
                    'SPEZ_FIELD_STATUS'   => $status,
                    'SPEZ_FIELD_TYPE'     => $type,
                    'PLACEHOLDER_TITLE'   => "[[TXT_MARKET_".strtoupper($objResult->fields['name'])."]]",
                    'PLACEHOLDER_CONTENT' => "[[MARKET_".strtoupper($objResult->fields['name'])."]]",
                ));
                $this->_objTpl->parseCurrentBlock('spez_fields');
                $i++;
                $objResult->MoveNext();
            }
          }

          if (isset($_POST['submitSettings'])) {
            foreach ($_POST['setname'] as $id => $name) {
                $name        = contrexx_addslashes($name);
                $value        = contrexx_addslashes($_POST['settitle'][$id]);
                $status        = contrexx_addslashes($_POST['setstatus'][$id]);

                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_spez_fields SET value='".$value."', active='".$status."' WHERE name='".$name."'");
            }

            if ($objResult !== false) {
                \Cx\Core\Csrf\Controller\Csrf::header('Location: ?cmd=Market&act=settings&tpl=spez_fields');
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_SETTINGS_UPDATED'];
            }else{
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
    }


    /**
    * show system settings
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function systemSettings() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_market_settings_system.html');

        $this->_objTpl->setVariable(array(
            'TXT_TITLE'       => $_CORELANG['TXT_SETTINGS'],
            'TXT_DESCRIPTION' => $_CORELANG['TXT_DESCRIPTION'],
            'TXT_VALUE'       => $_CORELANG['TXT_VALUE'],
            'TXT_SAVE'        => $_ARRAYLANG['TXT_SAVE'],
        ));

        //get settings
        $i=0;
          $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_market_settings ORDER BY type ASC");
          if ($objResult !== false) {
            while (!$objResult->EOF) {
                $this->_objTpl->setCurrentBlock('settings');
                if ($objResult->fields['type']== 1) {
                    $setValueField = "<input type=\"text\" name=\"setvalue[".$objResult->fields['id']."]\" value=\"".$objResult->fields['value']."\" style='width: 300px;' maxlength='250'>";
                }elseif ($objResult->fields['type']== 2) {
                    if ($objResult->fields['value'] == 1) {
                        $true = "checked";
                        $false = "";
                    }else{
                        $false = "checked";
                        $true = "";
                    }
                    $setValueField = "<input type=\"radio\" name=\"setvalue[".$objResult->fields['id']."]\" value=\"1\" ".$true.">&nbsp;Aktiviert&nbsp;<input type=\"radio\" name=\"setvalue[".$objResult->fields['id']."]\" value=\"0\"".$false.">&nbsp;Deaktiviert&nbsp;";
                } else {
                     $setValueField = "<textarea name=\"setvalue[".$objResult->fields['id']."]\" rows='5' style='width: 300px;'>".$objResult->fields['value']."</textarea>";
                }

                ($i % 2)? $class = "row2" : $class = "row1";

                // initialize variables
                $this->_objTpl->setVariable(array(
                    'SETTINGS_ROWCLASS'        => $class,
                    'SETTINGS_SETVALUE'        => $setValueField,
                    'SETTINGS_DESCRIPTION'  => $_ARRAYLANG[$objResult->fields['description']],
                ));
                $this->_objTpl->parseCurrentBlock('settings');
                $i++;
                $objResult->MoveNext();
            }
          }

          if (isset($_POST['submitSettings'])) {

            foreach ($_POST['setvalue'] as $id => $value) {
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_settings SET value='".contrexx_addslashes($value)."' WHERE id=".intval($id));

                if ($id == 11) {
                    if ($value == '0') {
                        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_mail SET mailto='admin' WHERE id='2'");
                    } else {
                        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_mail SET mailto='advertiser' WHERE id='2'");
                    }
                }
            }

            if ($objResult !== false) {
                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?cmd=Market&act=settings');
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_SETTINGS_UPDATED'];
            }else{
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
    }


    /**
    * show settings for mail
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    * @global array
    */
    function mailSettings() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_market_settings_mail.html');

        //get content
        $objResult = $objDatabase->Execute("SELECT title, content, active, mailcc FROM ".DBPREFIX."module_market_mail WHERE id = '1'");
          if ($objResult !== false) {
            while (!$objResult->EOF) {
                $mailContent     = $objResult->fields['content'];
                $mailTitle         = $objResult->fields['title'];
                $mailCC         = $objResult->fields['mailcc'];
                $mailActive         = $objResult->fields['active'];
                $objResult->MoveNext();
            }
          }
          $mailActive == 1 ? $checked = 'checked' : $checked = '';

        $this->_objTpl->setVariable(array(
            'TXT_SAVE'        => $_ARRAYLANG['TXT_SAVE'],
            'TXT_EMAIL_TITLE' => $_CORELANG['TXT_EMAIL'],
            'TXT_PLACEHOLDER' => $_ARRAYLANG['TXT_MARKET_PLACEHOLDER'],
            'TXT_SETTINGS'    => $_CORELANG['TXT_SETTINGS'],
            'TXT_MAIL_ON'     => $_ARRAYLANG['TXT_MARKET_ACTIVATE_VALIDATION_EMAIL'],
            'TXT_MAIL_CC'     => $_ARRAYLANG['TXT_MARKET_ADDITIONAL_RECIPENT'],
            'TXT_CONTENT'     => $_CORELANG['TXT_CONTENT'],
            'TXT_SUBJECT'     => $_ARRAYLANG['TXT_MARKET_SUBJECT'],
            'TXT_TEXT'        => $_ARRAYLANG['TXT_MARKET_TEXT'],
            'TXT_URL'         => $_ARRAYLANG['TXT_MARKET_URL'],
            'TXT_LINK'        => $_ARRAYLANG['TXT_MARKET_LINK'],
            'TXT_NAME'        => $_CORELANG['TXT_NAME'],
            'TXT_USERNAME'    => $_CORELANG['TXT_USERNAME'],
            'TXT_ID'          => $_ARRAYLANG['TXT_MARKET_ADVERTISEMENT_ID'],
            'TXT_TITLE'       => $_ARRAYLANG['TXT_MARKET_ADVERTISEMENT_TITLE'],
            'TXT_DATE'        => $_CORELANG['TXT_DATE'],
            'MAIL_CONTENT'    => $mailContent,
            'MAIL_TITLE'      => $mailTitle,
            'MAIL_CC'         => $mailCC,
            'MAIL_ON'         => $checked,
        ));

        $this->_objTpl->setVariable(array(
            'MAIL_CONTENT' => $mailContent,
            'MAIL_TITLE'   => $mailTitle,
            'MAIL_CC'      => $mailCC,
            'MAIL_ON'      => $checked,
        ));

        if (isset($_POST['submitSettings'])) {
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_mail SET title='".$_POST['mailTitle']."', content='".$_POST['mailContent']."', mailcc='".$_POST['mailCC']."', active='".$_POST['mailOn']."' WHERE id='1'");
            if ($objResult !== false) {
                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?cmd=Market&act=settings&tpl=email');
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_SETTINGS_UPDATED'];
            }else{
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
    }

    /**
    * show settings for mail code
    *
    * @access public
    * @global ADONewConnection
    * @global array
    * @global array
    */
    function mail_codeSettings() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_market_settings_mail_code.html');

        //get content
        $objResult = $objDatabase->Execute("SELECT title, content, mailto, mailcc FROM ".DBPREFIX."module_market_mail WHERE id = '2'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $mailContent         = $objResult->fields['content'];
                $mailTitle             = $objResult->fields['title'];
                $mailTo             = $objResult->fields['mailto'];
                $mailCC                = $objResult->fields['mailcc'];
                $objResult->MoveNext();
            }
        }

        $mailTo == 'admin' ? $admin = 'checked' : $admin = '';
        $mailTo == 'advertiser' || $mailTo == '' ? $advertiser = 'checked' : $advertiser = '';

        $this->_objTpl->setVariable(array(
            'TXT_SAVE'        => $_ARRAYLANG['TXT_SAVE'],
            'TXT_EMAIL_TITLE' => $_CORELANG['TXT_EMAIL'],
            'TXT_PLACEHOLDER' => $_ARRAYLANG['TXT_MARKET_PLACEHOLDER'],
            'TXT_SETTINGS'    => $_CORELANG['TXT_SETTINGS'],
            'TXT_MAIL_CC'     => $_ARRAYLANG['TXT_MARKET_ADDITIONAL_RECIPENT'],
            'TXT_MAIL_TO'     => $_ARRAYLANG['TXT_MARKET_CODE_CLEARING_CODE'],
            'TXT_CONTENT'     => $_CORELANG['TXT_CONTENT'],
            'TXT_SUBJECT'     => $_ARRAYLANG['TXT_MARKET_SUBJECT'],
            'TXT_TEXT'        => $_ARRAYLANG['TXT_MARKET_TEXT'],
            'TXT_URL'         => $_ARRAYLANG['TXT_MARKET_URL'],
            'TXT_CODE'        => $_ARRAYLANG['TXT_MARKET_CODE_CLEARINGCODE'],
            'TXT_NAME'        => $_CORELANG['TXT_NAME'],
            'TXT_USERNAME'    => $_CORELANG['TXT_USERNAME'],
            'TXT_ID'          => $_ARRAYLANG['TXT_MARKET_ADVERTISEMENT_ID'],
            'TXT_TITLE'       => $_ARRAYLANG['TXT_MARKET_ADVERTISEMENT_TITLE'],
            'TXT_DATE'        => $_CORELANG['TXT_DATE'],
            'TXT_ADMIN'       => $_CORELANG['TXT_ADMIN_STATUS'],
            'TXT_ADVERTISER'  => $_ARRAYLANG['TXT_MARKET_ADVERTISER']
        ));

        $this->_objTpl->setVariable(array(
            'MAIL_CONTENT'                 => $mailContent,
            'MAIL_TITLE'                   => $mailTitle,
            'MAIL_TO_ADVERTISER'          => $advertiser,
            'MAIL_TO_ADMIN'              => $admin,
            'MAIL_CC'                      => $mailCC
        ));

        if (isset($_POST['submitSettings'])) {
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_mail SET title='".$_POST['mailTitle']."', content='".$_POST['mailContent']."', mailcc='".$_POST['mailCC']."', active='1', mailto='".$_POST['mailTo']."' WHERE id='2'");

            if ($_POST['mailTo'] == 'admin') {
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_settings SET value='0' WHERE id='11'");
            } else {
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market_settings SET value='1' WHERE id='11'");
            }

            if ($objResult !== false) {
                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?cmd=Market&act=settings&tpl=email_code');
                $this->strOkMessage = $_ARRAYLANG['TXT_MARKET_SETTINGS_UPDATED'];
            }else{
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
    }
}
