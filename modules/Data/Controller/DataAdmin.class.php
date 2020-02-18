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
 * Data
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_data
 *
 * THIS FILE HAS TO BE OPENED WITH UTF-8 ENCODING
 */

namespace Cx\Modules\Data\Controller;

$_ARRAYLANG['TXT_DIV_THUMBNAIL_TYPE'] = "Thumbnail des Bildes verwenden";
/**
 * DataAdmin
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_data
 */
class DataAdmin extends \Cx\Modules\Data\Controller\DataLibrary {

    var $_objTpl;
    var $_strPageTitle    = '';
    var $_strErrMessage = '';
    var $_strOkMessage     = '';

    var $current_cat_id = 0;

    /**
    * Constructor-Fix for non PHP5-Servers
    *
    */
    function DataAdmin() {
        $this->__construct();
    }

    private $act = '';

    /**
    * Constructor    -> Create the module-menu and an internal template-object
    *
    * @global   InitCMS
    * @global    \Cx\Core\Html\Sigma
    * @global    array
    */
    function __construct() {
        global $objInit, $objTemplate, $_ARRAYLANG;

        parent::__construct();
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Data/View/Template/Backend');
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

         $this->_intLanguageId = $objInit->userFrontendLangId;
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION','
            <a href="?cmd=Data" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_TITLE'].'</a>
            <a href="?cmd=Data&amp;act=addEntry" class="'.($this->act == 'addEntry' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DATA_ENTRY_ADD_TITLE'].'</a>
            <a href="?cmd=Data&amp;act=manageCategory" class="'.($this->act == 'manageCategory' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_TITLE'].'</a>
            <a href="?cmd=Data&amp;act=settings" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DATA_SETTINGS_TITLE'].'</a>
                                                    ');
    }


    /**
    * Perform the right operation depending on the $_GET-params
    *
    * @global     \Cx\Core\Html\Sigma
    */
    function getPage() {
        global /*$objPerm,*/ $objTemplate;

        if(!isset($_GET['act'])) {
            $_GET['act']='';
        }

        switch($_GET['act']){
            case 'addEntry':
                /*$objPerm->checkAccess(121, 'static');*/
                $this->addEntry();
                break;
            case 'insertEntry':
                /*$objPerm->checkAccess(121, 'static');*/
                $this->insertEntry();
                $this->showEntries();
                break;
            case 'deleteEntry':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->deleteEntry($_GET['id']);
                $this->showEntries();
                break;
            case 'editEntry':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->editEntry($_GET['id']);
                break;
            case 'copyEntry':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->editEntry($_GET['id'], true);
                break;
            case 'updateEntry':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->updateEntry();
                $this->showEntries();
                break;
            case 'switchEntryState':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->switchEntryState();
                break;
            case 'multiactionEntry':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->doEntryMultiAction($_POST['frmShowEntries_MultiAction']);
                $this->showEntries();
                break;
            case 'manageCategory':
                /*$objPerm->checkAccess(122, 'static');*/
                $this->showCategories();
                break;
            case 'insertCategory':
                /*$objPerm->checkAccess(123, 'static');*/
                $this->insertCategory();
                $this->showCategories();
                break;
            case 'editCategory':
                /*$objPerm->checkAccess(122, 'static');*/
                $this->editCategory($_GET['id']);
                break;
            case 'updateCategory':
                /*$objPerm->checkAccess(122, 'static');*/
                $this->updateCategory();
                $this->showCategories();
                break;
            case 'deleteCategory':
                /*$objPerm->checkAccess(122, 'static');*/
                $this->deleteCategory($_GET['id']);
                $this->showCategories();
                break;
            case 'multiactionCategory':
                /*$objPerm->checkAccess(122, 'static');*/
                $this->doCategoryMultiAction($_POST['frmShowCategories_MultiAction']);
                $this->showCategories();
                break;
            case 'switchCategoryState':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->switchCategoryState();
                break;
            case 'settings':
                /*$objPerm->checkAccess(124, 'static');*/
                $this->showSettings();
                break;
            case 'saveSettings':
                /*$objPerm->checkAccess(124, 'static');*/
                $this->saveSettings();
                $this->showSettings();
                break;
            case 'saveEntryOrder':
                /*$objPerm->checkAccess(120, 'static');*/
                $this->saveEntryOrder();
                break;
            case 'saveCategoryOrder':
                /*$objPerm->checkAccess(122, 'static');*/
                $this->saveCategoryOrder();
                break;
            default:
                /*$objPerm->checkAccess(120, 'static');*/
                $this->showEntries();
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'                => $this->_strPageTitle,
            'CONTENT_OK_MESSAGE'        => $this->_strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->_strErrMessage,
            'ADMIN_CONTENT'                => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    /**
     * Shows the categories-page of the data-module.
     *
     * @global    array
     * @global     ADONewConnection
     */
    function showCategories() {
        global $_ARRAYLANG, $objDatabase;

        \JS::activate('jqueryui');

        $this->_strPageTitle = $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_TITLE'];
        $this->_objTpl->loadTemplateFile('module_data_categories.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_OVERVIEW_TITLE'                =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_TITLE'],
            'TXT_OVERVIEW_SUBTITLE_NAME'        =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_NAME'],
            'TXT_OVERVIEW_SUBTITLE_ACTIVE'      =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_ACTIVE_LANGUAGES'],
            'TXT_OVERVIEW_SUBTITLE_ACTIONS'     =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_ACTIONS'],
            'TXT_OVERVIEW_DELETE_CATEGORY_JS'   =>  $_ARRAYLANG['TXT_DATA_CATEGORY_DELETE_JS'],
            'TXT_OVERVIEW_MARKED'               =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_OVERVIEW_SELECT_ALL'           =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_OVERVIEW_DESELECT_ALL'         =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_OVERVIEW_SUBMIT_SELECT'        =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_OVERVIEW_SUBMIT_DELETE'        =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_DELETE'],
            'TXT_OVERVIEW_SUBMIT_DELETE_JS'     =>  $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_DELETE_JS'],
            'TXT_ADD_TITLE'                     =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_TITLE'],
            'TXT_ADD_NAME'                      =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_NAME'],
            'TXT_ADD_EXTENDED'                  =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_EXTENDED'],
            'TXT_ADD_LANGUAGES'                 =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_LANGUAGES'],
            'TXT_ADD_SUBMIT'                    =>  $_ARRAYLANG['TXT_SAVE'],
            'TXT_PLACEHOLDERS'                  =>  $_ARRAYLANG['TXT_DATA_PLACEHOLDER'],
            'TXT_PARENT_CAT'                    =>  $_ARRAYLANG['TXT_DATA_PARENT_CAT'],
            "TXT_TOP_LEVEL"                     =>  $_ARRAYLANG['TXT_TOP_LEVEL'],
            "TXT_FRONTEND_PAGE"                 =>  $_ARRAYLANG['TXT_FRONTEND_PAGE'],
            'TXT_CONTENT_PAGE'                  =>  $_ARRAYLANG['TXT_DATA_SETTINGS_CONTENT_PAGE'],
            'TXT_BOX'                           =>  $_ARRAYLANG['TXT_DATA_SETTINGS_BOX'],
            'TXT_GENERAL_ACTION'                =>  $_ARRAYLANG['TXT_DATA_SETTINGS_ACTION'],
            'TXT_SUBCATEGORIES'                 =>  $_ARRAYLANG['TXT_SUBCATEGORIES'],
            'TXT_BOX_WIDTH'                     =>  $_ARRAYLANG['TXT_DATA_OVERLAY_WIDTH'],
            'TXT_BOX_HEIGHT'                    =>  $_ARRAYLANG['TXT_DATA_OVERLAY_HEIGHT'],
            'TXT_TEMPLATE'                      =>  $_ARRAYLANG['TXT_TEMPLATE'],
            'CAT_TEMPLATE'                      =>  contrexx_raw2xhtml( $this->_arrSettings['data_template_category']),
            'TXT_DISPLAY_MODE'                  =>  $_ARRAYLANG['TXT_DISPLAY_MODE']
        ));

        //Show Categories
        $arrCategories = $this->createCategoryArray();

        if (count($arrCategories) > 0) {
            $catTree = $this->buildCatTree($arrCategories, 0);
            $this->parseCategoryLevel($catTree, $arrCategories, 0);
            $this->parseCategoryDropdown($catTree, $arrCategories, 0, 0, $this->_intLanguageId);
        } else {
            $this->_objTpl->setVariable('TXT_OVERVIEW_NO_CATEGORIES_FOUND',$_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_NO_CATEGORIES']);
            $this->_objTpl->parse('noCategories');
        }

        //Show Add-Category Form
        if (count($this->_arrLanguages) > 0) {
            $intCounter = 0;
            $arrLanguages = array(0 => '', 1 => '', 2 => '');

            foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                $arrLanguages[$intCounter%3] .= '<input checked="checked" type="checkbox" name="frmAddCategory_Languages[]" value="'.$intLanguageId.'" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';

                $this->_objTpl->setVariable(array(
                    'ADD_NAME_LANGID'   =>    $intLanguageId,
                    'ADD_NAME_LANG'     =>    $arrTranslations['long'].' ['.$arrTranslations['short'].']'
                ));

                $this->_objTpl->parse('addCategoryNameFields');

                ++$intCounter;
            }

            $objResult = $objDatabase->Execute('SELECT        MAX(category_id) AS currentId
                                                FROM        '.DBPREFIX.'module_data_categories
                                                ORDER BY    category_id DESC
                                            ');
            $intNextCategoryId = ($objResult->RecordCount() == 1) ? $objResult->fields['currentId'] + 1 : 1;

            $this->_objTpl->setVariable(array(
                'ADD_LANGUAGES_1'     => $arrLanguages[0],
                'ADD_LANGUAGES_2'     => $arrLanguages[1],
                'ADD_LANGUAGES_3'     => $arrLanguages[2],
                'TXT_PLACEHOLDER'     => $_ARRAYLANG['TXT_DATA_PLACEHOLDER'],
                'PLACEHOLDER'         => "CAT_".$intNextCategoryId,
                'PAGE_SELECT_DISPLAY' => "none"
            ));

            // show the frontend pages
            $frontPages = $this->getFrontendPages();
            foreach ($frontPages as $pageId => $pageVal) {
                $pageName =  $pageVal['name']." (cmd: ".$pageVal['cmd'].")";
                $this->_objTpl->setVariable(array(
                    "FRONTEND_PAGE"     => $pageName,
                    "FRONTEND_PAGE_ID"  => $pageVal['cmd']
                ));
                $this->_objTpl->parse("frontendPage");
            }
        }
    }

    /**
     * Get all frontend pages that have to do with this module
     *
     * @return unknown
     */
    function getFrontendPages()
    {
        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $pages = $pageRepo->findBy(array(
            'module' => 'Data',
            'lang' => $this->_intLanguageId,
            'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
        ));
        $pagesArr = array();
        foreach ($pages as $page) {
            $pagesArr[] = array(
                'id'    => $page->getId(),
                'name'  => $page->getTitle(),
                'cmd'   => $page->getCmd(),
            );
        }
        return $pagesArr;
    }

    /**
     * This function is for recursive parsing of the categories view
     *
     * @param unknown_type $tree
     * @param unknown_type $arrCategories
     * @param unknown_type $level
     * @param unknown_type $intRowClass
     * @return unknown
     */
    function parseCategoryLevel($tree, $arrCategories, $level, $intRowClass=1)
    {
        global $_ARRAYLANG;

        foreach ($tree as $key => $subcats) {
            $this->_objTpl->setVariable(array(
                'TXT_OVERVIEW_IMGALT_MESSAGES'        =>    $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_ASSIGNED_MESSAGES'],
                'TXT_OVERVIEW_IMGALT_EDIT'            =>    $_ARRAYLANG['TXT_DATA_CATEGORY_EDIT_TITLE'],
                'TXT_OVERVIEW_IMGALT_DELETE'        =>    $_ARRAYLANG['TXT_DATA_CATEGORY_DELETE_TITLE']
            ));

            $arrLanguages = $arrCategories[$key];

            // make string with active languages
            $strActivatedLanguages = '';
            foreach($arrLanguages as $intLanguageId => $arrValues) {
                if ($arrValues['is_active'] == 1 && array_key_exists($intLanguageId,$this->_arrLanguages)) {
                    $strActivatedLanguages .= $this->_arrLanguages[$intLanguageId]['long'].' ['.$this->_arrLanguages[$intLanguageId]['short'].'], ';
                }
            }

            $strActivatedLanguages = substr($strActivatedLanguages,0,-2);

            if ($arrCategories[$key]['action'] == "overlaybox") {
                $display = $_ARRAYLANG['TXT_DATA_SETTINGS_BOX'];
            } elseif ($arrCategories[$key]['action'] == "content") {
                $display = $_ARRAYLANG['TXT_DATA_SETTINGS_CONTENT_PAGE'];
            } else {
                $display = $_ARRAYLANG['TXT_SUBCATEGORIES'];
            }
            $this->_objTpl->setVariable(array(
                'OVERVIEW_CATEGORY_ROWCLASS'    =>    ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                'OVERVIEW_CATEGORY_ID'            =>    $key,
                'OVERVIEW_CATEGORY_NAME'        =>    $arrLanguages[$this->_intLanguageId]['name'],
                'OVERVIEW_CATEGORY_LANGUAGES'    =>    $strActivatedLanguages,
                'OVERVIEW_CATEGORY_PLACEHOLDER' =>  $arrLanguages['placeholder'],
                'OVERVIEW_CATEGORY_DISPLAY'     =>  $display,
                'PARENT_OPT_LABEL'              =>  $arrLanguages[$this->_intLanguageId]['name'],
                'PARENT_OPT_VALUE'              =>  $key,
                'INDENT'                        =>  ($level > 0) ?  ($level-1) * 15 : 0,
                'PARENT_INDENT'                 =>  str_repeat("...", $level),
                'ACTIVE_LED'                    =>  ($arrCategories[$key]['active']) ? "green" : "red",
                'ACTIVE_STATE'                  =>  ($arrCategories[$key]['active']) ? 0 : 1,
            ));

            if ($level > 0) {
                $this->_objTpl->touchBlock("arrow");
                $this->_objTpl->parse("arrow");
            }

            $children = $this->getAllChildren($key, $tree[$key]);

            if (!isset($arrCategories[$key]['parent_id'])) {
                //echo "\n\n########## HAD TO TAKE ZERO ########\n\n";
            }

            $this->_objTpl->setGlobalVariable(array(
                "CATID"         => $key,
                "PARENT_ID"     =>  !isset($arrCategories[$key]['parent_id'])
                                    ? $arrCategories[$key]['parent_id']
                                    : 0,
                "LEVEL"         => $level
            ));

            foreach ($children as $child) {
                $this->_objTpl->setVariable("CHILD_ID", $child);
                $this->_objTpl->parse("set_child");
            }

            $this->_objTpl->parse('showCategories');
            $this->_objTpl->parse("addCategoryDropDown");
            $intRowClass++;

            if (count($subcats) > 0) {
                $intRowClass = $this->parseCategoryLevel($subcats, $arrCategories, $level+1, $intRowClass);
            }
        }

        return $intRowClass;
    }

    /**
     * Get a list of a category's children
     *
     * @param unknown_type $id
     * @param unknown_type $catTree
     * @return unknown
     */
    function getAllChildren($id, $catTree)
    {
        $children = array();

        foreach ($catTree as $key => $value) {
            $children[] = $key;
            if (count($value) > 0) {
                $children = array_merge($children, $this->getAllChildren($key, $value));
            }
        }

        return $children;
    }


    /**
     * Adds a new category to the database. Collected
     * data in POST is checked for valid values.
     *
     * @global     ADONewConnection
     * @global  array
     */
    function insertCategory() {
        global $objDatabase, $_ARRAYLANG;
        //DBG::activate(DBG_ADODB);

        if (isset($_POST['frmAddCategory_Languages']) && is_array($_POST['frmAddCategory_Languages'])) {
            //Get next category-id
            $objResult = $objDatabase->Execute('SELECT        MAX(category_id) AS currentId
                                                FROM        '.DBPREFIX.'module_data_categories
                                                ORDER BY    category_id DESC
                                            ');
            $intNextCategoryId = ($objResult->RecordCount() == 1) ? $objResult->fields['currentId'] + 1 : 1;

            //Collect data
            $arrValues = array();
            foreach ($_POST as $strKey => $strValue) {
                /*
                echo "------------------------------------ BEGIN ------------------------------------\n";
                echo "$strKey :" .($strValue, true);
                echo "\n------------------------------------ END ------------------------------------\n\n\n\n";
                 */
                // what the fuck is this for?
                if (substr($strKey,0,strlen('frmAddCategory_Name_')) == 'frmAddCategory_Name_') {
                    $intLanguageId = intval(substr($strKey,strlen('frmAddCategory_Name_')));
                    $arrValues[$intLanguageId] = array(    'name'            => contrexx_addslashes(strip_tags($strValue)),
                                                        'is_active'       => intval(in_array($intLanguageId,$_POST['frmAddCategory_Languages'])),
                                                        'parent_id'    => intval($_POST['frmParentcategory']),
                                                        'cmd'          => intval($_POST['frmFrontendPage']),
                                                        'action'       => in_array($_POST['frmSettings_action'], array('overlaybox', 'content', 'subcategories')) ? $_POST['frmSettings_action'] : 'overlaybox',
                                                        'box_width'    => intval($_POST['frmBoxwidth']),
                                                        'box_height'   => intval($_POST['frmBoxheight']),
                                                        'template'     => contrexx_addslashes($_POST['frmTemplate'])
                                                    );
                }
            }

            foreach ($arrValues as $intLanguageId => $arrCategoryValues) {
                $objDatabase->Execute('    INSERT INTO `'.DBPREFIX.'module_data_categories`
                                        SET    `category_id` = '.$intNextCategoryId.',
                                            `lang_id` = '.$intLanguageId.',
                                            `is_active` = "'.$arrCategoryValues['is_active'].'",
                                            `parent_id` = "'.$arrCategoryValues['parent_id'].'",
                                            `name` = "'.$arrCategoryValues['name'].'",
                                            `cmd` = "'.$arrCategoryValues['cmd'].'",
                                            `action` = "'.$arrCategoryValues['action'].'",
                                            `box_width` = "'.$arrCategoryValues['box_width'].'",
                                            `box_height` = "'.$arrCategoryValues['box_height'].'",
                                            `template` = "'.$arrCategoryValues['template'].'"
                                    ');
            }

            // insert placeholder
            if (isset($_POST['frmPlaceholder'])) {
                $placeholder = $this->_formatPlaceholder($_POST['frmPlaceholder']);
                $query = "INSERT INTO ".DBPREFIX."module_data_placeholders
                          (type, ref_id, placeholder)
                          VALUES
                          ('cat', ".$intNextCategoryId.", '".contrexx_input2db($placeholder)."')";
                $objDatabase->Execute($query);
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_ERROR_ACTIVE'];
        }
        \DBG::deactivate(DBG_ADODB);
    }


    /**
     * Removes a category from the database.
     *
     * @param     integer        $intCategoryId: This category will be deleted by the function.
     * @global     array
     * @global     ADONewConnection
     */
    function deleteCategory($intCategoryId) {
        global $_ARRAYLANG, $objDatabase;

        $intCategoryId = intval($intCategoryId);

        if ($intCategoryId > 0) {
            $objDatabase->Execute('    DELETE
                                    FROM '.DBPREFIX.'module_data_categories
                                    WHERE `category_id` = '.$intCategoryId.'
                                ');

            if (!$this->_boolInnoDb) {
                $objDatabase->Execute('    DELETE
                                        FROM '.DBPREFIX.'module_data_message_to_category
                                        WHERE `category_id` = '.$intCategoryId.'
                                    ');
            }

            $objDatabase->Execute("   DELETE FROM ".DBPREFIX."module_data_placeholders
                                      WHERE ref_id = ".$intCategoryId);

            $this->writeMessageRSS();
            $this->writeCategoryRSS();

            $this->_strOkMessage = $_ARRAYLANG['TXT_DATA_CATEGORY_DELETE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_CATEGORY_DELETE_ERROR'];
        }
    }


    /**
     * Performs the action for the dropdown-selection on the category page. The behaviour depends on the parameter.
     *
     * @param    string        $strAction: the action passed by the formular.
     */
    function doCategoryMultiAction($strAction='') {
        switch ($strAction) {
            case 'delete':
                foreach($_POST['selectedCategoryId'] as $intKey => $intCategoryId) {
                    $this->deleteCategory($intCategoryId);
                }
                break;
            default:
                //do nothing!
        }
    }


    /**
     * Shows the edit-page for a specific category.
     *
     * @global    array
     * @global     ADONewConnection
     * @param     integer        $intCategoryId: The category with this id will be loaded into the form.
     */
    function editCategory($intCategoryId) {
        global $_ARRAYLANG, $objDatabase;

        $this->_strPageTitle = $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_TITLE'];
        $this->_objTpl->loadTemplateFile('module_data_categories_edit.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_TITLE'        =>  $_ARRAYLANG['TXT_DATA_CATEGORY_EDIT_TITLE'],
            'TXT_EDIT_NAME'         =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_NAME'],
            'TXT_EDIT_EXTENDED'     =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_EXTENDED'],
            'TXT_EDIT_LANGUAGES'    =>  $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_LANGUAGES'],
            'TXT_EDIT_SUBMIT'       =>  $_ARRAYLANG['TXT_SAVE'],
            'TXT_PARENT_CAT'        =>  $_ARRAYLANG['TXT_DATA_PARENT_CAT'],
            'TXT_TOP_LEVEL'         =>  $_ARRAYLANG['TXT_TOP_LEVEL'],
            "TXT_FRONTEND_PAGE"     =>  $_ARRAYLANG['TXT_FRONTEND_PAGE'],
            'TXT_CONTENT_PAGE'      =>  $_ARRAYLANG['TXT_DATA_SETTINGS_CONTENT_PAGE'],
            'TXT_BOX'               =>  $_ARRAYLANG['TXT_DATA_SETTINGS_BOX'],
            'TXT_GENERAL_ACTION'    =>  $_ARRAYLANG['TXT_DATA_SETTINGS_ACTION'],
            'TXT_SUBCATEGORIES'     =>  $_ARRAYLANG['TXT_SUBCATEGORIES'],
            'TXT_PLACEHOLDER'       =>  $_ARRAYLANG['TXT_DATA_PLACEHOLDER'],
            'TXT_BOX_WIDTH'         =>  $_ARRAYLANG['TXT_DATA_OVERLAY_WIDTH'],
            'TXT_BOX_HEIGHT'        =>  $_ARRAYLANG['TXT_DATA_OVERLAY_HEIGHT'],
            'TXT_TEMPLATE'          =>  $_ARRAYLANG['TXT_TEMPLATE']
        ));

        $intCategoryId = intval($intCategoryId);

        $this->current_cat_id = $intCategoryId;

        $arrCategories = $this->createCategoryArray();
        $ie = (preg_match("/MSIE (6|7)/", $_SERVER['HTTP_USER_AGENT'])) ? true : false;

        if (array_key_exists($intCategoryId, $arrCategories)) {
            $intCounter = 0;
            $arrLanguages = array(0 => '', 1 => '', 2 => '');

            $catTree = $this->buildCatTree($arrCategories, 0);

            foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                $arrLanguages[$intCounter%3] .= '
                    <input '.(($arrCategories[$intCategoryId][$intLanguageId]['is_active'] == 1) ? 'checked="checked"' : '').'
                        type="checkbox" name="frmEditCategory_Languages[]"
                        value="'.$intLanguageId.'" />'.
                    $arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';

                $this->_objTpl->setVariable(array(
                    'EDIT_NAME_LANGID'    =>    $intLanguageId,
                    'EDIT_NAME_LANG'    =>    $arrTranslations['long'].' ['.$arrTranslations['short'].']',
                    'EDIT_NAME_VALUE'    =>    $arrCategories[$intCategoryId][$intLanguageId]['name']
                ));

                $this->_objTpl->parse('editCategoryNameFields');

                $this->parseCategoryDropdown($catTree, $arrCategories, $intCategoryId, 0, $intLanguageId);

                ++$intCounter;
           }

           $this->_objTpl->setVariable(array(
               'EDIT_CATEGORY_ID'        => $intCategoryId,
               'EDIT_NAME'               => $arrCategories[$intCategoryId][$this->_intLanguageId]['name'],
               'EDIT_LANGUAGES_1'        => $arrLanguages[0],
               'EDIT_LANGUAGES_2'        => $arrLanguages[1],
               'EDIT_LANGUAGES_3'        => $arrLanguages[2],
               "PLACEHOLDER"             => $arrCategories[$intCategoryId]['placeholder'],
               'PAGE_SELECT_DISPLAY'     => ($arrCategories[$intCategoryId]['action'] == "content") ? (($ie) ? "block" : "table-row") : "none",
               'ACTION_SELECTED_BOX'     => ($arrCategories[$intCategoryId]['action'] == "overlaybox") ? "selected=\"selected\"" : "",
               'ACTION_SELECTED_CONTENT' => ($arrCategories[$intCategoryId]['action'] == "content") ? "selected=\"selected\"" : "",
               'ACTION_SELECTED_SUBCATS' => ($arrCategories[$intCategoryId]['action'] == "subcategories") ? "selected=\"selected\"" : "",
               'PAGE_BOX_WIDTH_DISPLAY'  => ($arrCategories[$intCategoryId]['action'] == "overlaybox") ? (($ie) ? "block" : "table-row") : "none",
               'PAGE_BOX_HEIGHT_DISPLAY' => ($arrCategories[$intCategoryId]['action'] == "overlaybox") ? (($ie) ? "block" : "table-row") : "none",
               'BOX_WIDTH'               => $arrCategories[$intCategoryId]['box_width'],
               'BOX_HEIGHT'              => $arrCategories[$intCategoryId]['box_height'],
               'CAT_TEMPLATE'            => contrexx_raw2xhtml($arrCategories[$intCategoryId]['template'])
           ));
        } else {
            //Wrong category-id
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_CATEGORY_EDIT_ERROR_ID'];
        }

        // show the frontend pages
        $frontPages = $this->getFrontendPages();
        foreach ($frontPages as $pageId => $pageVal) {
           $pageName =  $pageVal['name']." (cmd: ".$pageVal['cmd'].")";
           $this->_objTpl->setVariable(array(
               "FRONTEND_PAGE"             => $pageName,
               "FRONTEND_PAGE_ID"          => $pageVal['cmd'],
               "FRONTEND_PAGE_SELECTED"    =>
                   ($pageVal['cmd'] == $arrCategories[$intCategoryId]['cmd'])
                   ? "selected=\"selected\""
                   : ""
           ));
           $this->_objTpl->parse("frontendPage");
        }
    }

    /**
     * This function is for recursive parsing of the categories for a dropdown
     *
     * @param unknown_type $categoryTree
     * @param unknown_type $arrCategories
     * @param unknown_type $level
     */
    function parseCategoryDropdown($categoryTree, $arrCategories, $select, $level, $lang, $parent = true)
    {
    // this used to expect an int value, only allowing entries to belong to a single category. this way we
    // continue to support legacy calls but add support for multiple categories in callers aware of that. -fs
    if (intval($select) == $select) {
        $select = array($select);
    }

        foreach ($categoryTree as $key => $value) {
            $selected = false;
            if ($select > 0) {
                if ($parent) {
                    if ($key == $arrCategories[$select[0]]['parent_id']) {
                        $selected = true;
                    }
                } else {
                    if ($key == $select) {
                        $selected = true;
                    }
                }
            }

            // Don't show our own id as selectable parent. This just causes trouble.
            // also, we don't want sub-categories of our's to be shown.
            // this would require reordering of the categories to avoid
            // having a loop.
            if ($this->current_cat_id and $key == $this->current_cat_id)
                continue;

            $this->_objTpl->setVariable(array(
                "CATEGORY_OPT_LABEL"      => $arrCategories[$key][$lang]['name'],
                "CATEGORY_OPT_VALUE"      => $key,
                "CATEGORY_OPT_SELECTED"   => ($selected) ? "selected=\"selected\"" : "",
                "CATEGORY_OPT_INDENT"         =>  str_repeat("...", $level)
            ));

            $this->_objTpl->parse("addCategoryDropDown");


            if (count($value) > 0) {
                $this->parseCategoryDropdown($value, $arrCategories, $select, $level+1, $lang, $parent);
            }

        }
    }

    function parseCategorySelector($categoryTree, $arrCategories, $select, $level, $lang, $parent = true, $stack = '')
    {
    // this used to expect an int value, only allowing entries to belong to a single category. this way we
    // continue to support legacy calls but add support for multiple categories in callers aware of that. -fs
    if (intval($select) == $select) {
        $select = array($select);
    }

        foreach ($categoryTree as $key => $value) {
            $selected = false;
            if ($select > 0) {
                if (in_array($key, $select)) {
                    $selected = true;
                }
            }

            // Don't show our own id as selectable parent. This just causes trouble.
            // also, we don't want sub-categories of our's to be shown.
            // this would require reordering of the categories to avoid
            // having a loop.
            if (($this->current_cat_id and $key == $this->current_cat_id) OR strlen($arrCategories[$key][$lang]['name']) < 1)
                continue;

            $this->_objTpl->setVariable(array(
                "CATEGORY_OPT_LABEL"      => $arrCategories[$key][$lang]['name'],
                "CATEGORY_OPT_VALUE"      => $key,
                "CATEGORY_OPT_SELECTED"   => "",
                "CATEGORY_OPT_INDENT"         =>  $stack
            ));

            if ($selected) {
                $this->_objTpl->parse("assignedCategories");
            }
            else {
                $this->_objTpl->parse("availableCategories");
            }

            if (count($value) > 0) {
                $stack .= $arrCategories[$key][$lang]['name'].' &raquo; ';
                $this->parseCategorySelector($value, $arrCategories, $select, $level+1, $lang, $parent, $stack);
            }

        }
    }


    /**
     * Updates an existing category.
     *
     * @global     array
     * @global     ADONewConnection
     */
    function updateCategory() {
        global $_ARRAYLANG, $objDatabase;

        if (isset($_POST['frmEditCategory_Languages']) && is_array($_POST['frmEditCategory_Languages'])) {
             $intCategoryId = intval($_POST['frmEditCategory_Id']);

             //Collect active-languages
            foreach ($_POST['frmEditCategory_Languages'] as $intKey => $intLanguageId) {
                $arrActiveLanguages[$intLanguageId] = true;
            }

            //Collect names & check for existing database-entry
            foreach ($_POST as $strKey => $strValue) {

                if (substr($strKey,0,strlen('frmEditCategory_Name_')) == 'frmEditCategory_Name_') {
                    $intLanguageId = intval(substr($strKey,strlen('frmEditCategory_Name_')));

                    $objResult = $objDatabase->Execute('SELECT name
                                                        FROM    '.DBPREFIX.'module_data_categories
                                                        WHERE    `category_id` = '.$intCategoryId.' AND
                                                                `lang_id` = '.$intLanguageId.'
                                                        LIMIT    1
                                                    ');
                    if ($objResult->RecordCount() == 0) {
                        //We have to create a new entry first
                        $objDatabase->Execute("    INSERT
                                                INTO    `".DBPREFIX."module_data_categories`
                                                SET        `category_id` = ".$intCategoryId.",
                                                        `lang_id` = ".$intLanguageId.",
                                                        `is_active` = '".(array_key_exists($intLanguageId,$arrActiveLanguages) ? "1" : "0")."',
                                                        `parent_id` = ".intval($_POST["frmParentcategory"]).",
                                                        `name` = '".contrexx_addslashes(strip_tags($strValue))."',
                                                        `cmd` = '".intval($_POST["frmFrontendPage"])."',
                                                        `action` = '".(in_array($_POST['frmSettings_action'], array('overlaybox', 'content', 'subcategories')) ? $_POST['frmSettings_action'] : 'overlaybox')."',
                                                        `box_height` = '".intval($_POST["frmBoxheight"])."',
                                                        `box_width` = '".intval($_POST['frmBoxwidth'])."',
                                                        `template` = '".contrexx_raw2db($_POST['frmTemplate'])."
                                            ");
                    } else {
                        //We can update the existing entry
                        $objDatabase->Execute("    UPDATE    `".DBPREFIX."module_data_categories`
                                                SET        `is_active` = '".(array_key_exists($intLanguageId,$arrActiveLanguages) ? "1" : "0")."',
                                                        `name` = '".contrexx_addslashes(strip_tags($strValue))."',
                                                        `parent_id` = ".intval($_POST["frmParentcategory"]).",
                                                        `cmd` = '".intval($_POST["frmFrontendPage"])."',
                                                        `action` = '".(in_array($_POST['frmSettings_action'], array('overlaybox', 'content', 'subcategories')) ? $_POST['frmSettings_action'] : 'overlaybox')."',
                                                        `box_height` = '".intval($_POST['frmBoxheight'])."',
                                                        `box_width` = '".intval($_POST["frmBoxwidth"])."',
                                                        `template` = '".contrexx_raw2db($_POST['frmTemplate'])."'
                                                WHERE    `category_id` = ".$intCategoryId." AND
                                                        `lang_id` = ".$intLanguageId."
                                                LIMIT    1
                                            ");
                    }
                }
            }

            if (isset($_POST['frmPlaceholder'])) {
                $query = "DELETE FROM ".DBPREFIX."module_data_placeholders
                              WHERE ref_id = ".$intCategoryId;
                $objDatabase->Execute($query);
                $placeholder = $this->_formatPlaceholder($_POST['frmPlaceholder']);
                $query = "INSERT INTO ".DBPREFIX."module_data_placeholders
                          (type, ref_id, placeholder)
                          VALUES
                          ('cat', ".$intCategoryId.", '".contrexx_input2db($placeholder)."')";
                $objDatabase->Execute($query);
                $err = $objDatabase->ErrorNo();
                if ($err == 1062) {
                    $placeholder .= rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9); // not very beautiful...
                    $objDatabase->Execute(" INSERT INTO ".DBPREFIX."module_data_placeholders
                                            (`type`, `ref_id`, `placeholder`)
                                            VALUES
                                            ('entry', ".$intCategoryId.",
                                            '".$placeholder."')");
                }
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_DATA_CATEGORY_UPDATE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_CATEGORY_UPDATE_ERROR_ACTIVE'];
        }
    }



    /**
     * Shows an overview of all entries.
     *
     * @global    array
     */
    function showEntries()
    {
        global $_ARRAYLANG;

        \JS::activate('jqueryui');

        $intSelectedCategory = (isset($_GET['catId'])) ? intval($_GET['catId']) : 0;
        $intPagingPosition = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;

        $this->_strPageTitle = $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_TITLE'];
        $this->_objTpl->loadTemplateFile('module_data_entries.html',true,true);

        $this->arrEntries = $this->createEntryArray(0);


        // show categories
        $arrCategories = $this->createCategoryArray();
        $catTree = $this->buildCatTree($arrCategories);
        $row = 2;
        $this->_objTpl->setVariable(array(
                "CATEGORY_NAME"             =>  $_ARRAYLANG['TXT_DATA_SHOW_ALL'],
                "CATEGORY_ID"               =>  0,
                "CATEGORY_INDENT"           =>  5,
                "CATEGORY_ROW"              =>  ($intSelectedCategory == 0) ? 3 : $row++,
                "CATEGORY_AMOUNT_OF_ENTRIES" => $this->getAmountOfEntries(0)
            ));
        $this->_objTpl->parse("category_row");
        $this->parseOverviewCategories($catTree, $arrCategories, $intSelectedCategory, 0, $row);

        $this->_objTpl->setVariable(array(
            'TXT_ENTRIES_TITLE'                    =>    $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_TITLE'],
            'TITLE_CATEGORY'                    =>  ($intSelectedCategory == 0) ? $_ARRAYLANG['TXT_DATA_ALL'] : $arrCategories[$intSelectedCategory][$this->_intLanguageId]['name'],
            'TXT_ENTRIES_SUBTITLE_DATE'            =>    $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_DATE'],
            'TXT_ENTRIES_SUBTITLE_SUBJECT'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_SUBJECT'],
            'TXT_ENTRIES_SUBTITLE_PLACEHOLDER'  =>  $_ARRAYLANG['TXT_DATA_PLACEHOLDER'],
            'TXT_ENTRIES_SUBTITLE_LANGUAGES'    =>    $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_LANGUAGES'],
            'TXT_ENTRIES_SUBTITLE_HITS'            =>    $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_HITS'],
            'TXT_ENTRIES_SUBTITLE_COMMENTS'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_COMMENTS'],
            'TXT_ENTRIES_SUBTITLE_VOTES'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_VOTE'],
            'TXT_ENTRIES_SUBTITLE_USER'            =>    $_ARRAYLANG['TXT_USER'],
            'TXT_ENTRIES_SUBTITLE_EDITED'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_UPDATED'],
            'TXT_ENTRIES_SUBTITLE_ACTIONS'        =>    $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_ACTIONS'],
            'TXT_ENTRIES_DELETE_ENTRY_JS'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_DELETE_JS'],
            'TXT_ENTRIES_MARKED'                =>    $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_ENTRIES_SELECT_ALL'            =>    $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_ENTRIES_DESELECT_ALL'            =>    $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_ENTRIES_SUBMIT_SELECT'            =>    $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_ENTRIES_SUBMIT_DELETE'            =>    $_ARRAYLANG['TXT_DATA_CATEGORY_MANAGE_SUBMIT_DELETE'],
               'TXT_ENTRIES_SUBMIT_DELETE_JS'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_SUBMIT_DELETE_JS'],
               'TXT_ENTRIES_SUBTITLE_CATEGORY'     =>  $_ARRAYLANG['TXT_DATA_CATEGORY'],
               'TXT_ENTRIES_SUBTITLE_MODE'         =>  $_ARRAYLANG['TXT_DATA_ENTRY_MODE']
           ));

           if ($intSelectedCategory == 0) {
               $this->sortEntriesAlphabetical();
           }

           if (count($this->arrEntries) > 0) {
               $intRowClass = 1;

               foreach ($this->arrEntries as $intEntryId => $arrEntryValues) {
                   if ($intSelectedCategory > 0) {
                       //Filter for a specific category. If the category doesn't match: skip.
                       if (!$this->categoryMatches($intSelectedCategory, $arrEntryValues['categories'][$this->_intLanguageId])) {
                           continue;
                       }
                   }

                   $this->_objTpl->setVariable(array(
                       'TXT_IMGALT_EDIT'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_EDIT_TITLE'],
                       'TXT_IMGALT_COPY'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_COPY_TITLE'],
                       'TXT_IMGALT_DELETE'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_DELETE_TITLE']
                   ));

                   //Check active languages
                   $strActiveLanguages = '';
                   foreach ($arrEntryValues['translation'] as $intLangId => $arrEntryTranslations) {
                       if ($arrEntryTranslations['is_active'] && key_exists($intLangId,$this->_arrLanguages)) {
                           $strActiveLanguages .= '['.$this->_arrLanguages[$intLangId]['short'].']&nbsp;&nbsp;';
                       }
                   }
                   $strActiveLanguages = substr($strActiveLanguages,0,-12);

                   $this->_objTpl->setGlobalVariable("ENTRY_ID", $intEntryId);

                $category_keys = array_keys($arrEntryValues['categories'][$this->_intLanguageId]);
                if ($arrEntryValues['mode'] == "normal") {
                    $mode = $_ARRAYLANG['TXT_DATA_ENTRY_MODE_NORMAL'];
                } else {
                    $mode = $_ARRAYLANG['TXT_DATA_ENTRY_MODE_FORWARD'];
                }

                // List multiple categories -fs
                $catList = '';
                $separator = ', ';
                foreach ($category_keys as $k) {
                    $catName = $arrCategories[$k][$this->_intLanguageId]['name'];

                    // if a catId is set, we'll want to highlight that particular category (and have it
                    // displayed first)
                    if ($k == $intSelectedCategory) {
                        $catList = '<strong>'.$catName.'</strong>'.$separator.$catList;
                    }
                    else {
                        $catList .= $catName.$separator;
                    }
                }
                // cut off the last ", ".
                $catList = substr($catList, 0, -2);


                   $this->_objTpl->setVariable(array(
                       'ENTRY_ROWCLASS'        =>    ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                       'ENTRY_ID'                =>    $intEntryId,
                       'ENTRY_DATE'            =>    $arrEntryValues['time_created'],
                       'ENTRY_EDITED'            =>    $arrEntryValues['time_edited'],
                       'ENTRY_SUBJECT'            =>    $arrEntryValues['subject'],
                       'ENTRY_PLACEHOLDER'     =>  $arrEntryValues['placeholder'],
                       'ENTRY_LANGUAGES'        =>    $strActiveLanguages,
                       'ENTRY_HITS'            =>    $arrEntryValues['hits'],
                       //'ENTRY_COMMENTS'        =>    $arrEntryValues['comments'].'&nbsp;'.$_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_COMMENTS'],
                       //'ENTRY_VOTES'            =>    '&#216;&nbsp;'.$arrEntryValues['votes_avg'].'&nbsp;/&nbsp;'.$arrEntryValues['votes'].' '.$_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_VOTES'],
                       //'ENTRY_USER'            =>    $arrEntryValues['user_name'],
                       'ACTIVE_LED'            =>  ($arrEntryValues['active']) ? "green" : "red",
                       'ACTIVE_STATE'          =>  ($arrEntryValues['active']) ? 0 : 1,
                       'ENTRY_CATEGORY'        =>  $catList, //$arrCategories[$category_keys[0]][$this->_intLanguageId]['name'],
                       'ENTRY_MODE'            =>  $mode
                   ));

                   if ($intSelectedCategory == 0) {
                       $this->_objTpl->hideBlock("sort_buttons");
                   } else {
                       $this->_objTpl->touchBlock("sort_buttons");
                   }

                   $this->_objTpl->parse('showEntries');

                   $intRowClass++;
               }

               //Show paging if needed
               /*
               if ($this->countEntries() > $this->getPagingLimit()) {
                   $strPaging = getPaging($this->countEntries(), $intPagingPosition, "&amp;cmd=Data&amp;catId=".$intSelectedCategory, '<strong>'.$_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_PAGING'].'</strong>', true, $this->getPagingLimit());
                   $this->_objTpl->setVariable('ENTRIES_PAGING', $strPaging);
               }*/
           } else {
               $this->_objTpl->setVariable('TXT_ENTRIES_NO_ENTRIES_FOUND', $_ARRAYLANG['TXT_DATA_ENTRY_MANAGE_NO_ENTRIES']);
               $this->_objTpl->parse('noEntries');
           }
    }

    /**
     * Sort all entries in alphabetical order
     *
     */
    function sortEntriesAlphabetical()
    {
        $assoc = array();
        foreach ($this->arrEntries as $key => $entry ) {
            $assoc[$entry['translation'][$this->_intLanguageId]['subject']] = $key;
        }

        ksort($assoc);
        $newEntries = array();
        foreach ($assoc as $key) {
            $newEntries[$key] = $this->arrEntries[$key];
        }
        $this->arrEntries = $newEntries;
    }

    /**
     * This is for recursive category parsing
     *
     * @param array $categoryTree
     * @param array $arrCategories
     * @param int $currentCat
     * @param int $level
     * @param int $row
     * @return int
     */
    function parseOverviewCategories($categoryTree, $arrCategories, $currentCat=0, $level=0, $row=1)
    {
        foreach ($categoryTree as $key => $value) {
            $fullName = $arrCategories[$key][$this->_intLanguageId]['name'];
            $name = (strlen($fullName)+$level*2 > 23) ? substr($fullName, 0, 20-($level*2))."..." : $fullName;
            $amount = $this->getAmountOfEntries($key, $arrCategories);
            $this->_objTpl->setVariable(array(
                "CATEGORY_NAME"                 => $name,
                "CATEGORY_FULL_NAME"            => $fullName,
                "CATEGORY_ID"                   => $key,
                "CATEGORY_INDENT"               =>  5 + (($level > 0) ? ($level-1) * 15 : 0),
                "CATEGORY_ROW"                  => ($currentCat == $key) ? 3 : (($row % 2 == 0) ? 2 : 1),
                "CATEGORY_AMOUNT_OF_ENTRIES"    => $amount,
            ));

            if ($level > 0) {
                $this->_objTpl->touchBlock("arrow");
                $this->_objTpl->parse("arrow");
            }

            $this->_objTpl->parse("category_row");

            $row++;
            if (count($value) > 0) {
                $row = $this->parseOverviewCategories($value, $arrCategories, $currentCat, $level+1, $row);
            }
        }
        return $row;
    }

    /**
     * Count the entries of a category
     *
     * @param int $id
     * @return int
     */
    function getAmountOfEntries($id)
    {
        $amount = 0;
        foreach ($this->arrEntries as $entry) {
            if ($id == 0  || $this->categoryMatches($id, $entry['categories'][$this->_intLanguageId])) {
                $amount++;
            }
        }
        return $amount;
    }

    /**
     * Get the MediaBrowser button
     *
     * @global array $_ARRAYLANG
     * @return string
     */
    public static function getMediaBrowserButton() {
        global $_ARRAYLANG;

        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setCallback('mbCallback');
        $mediaBrowser->setOptions(array(
                                    'type'             => 'button',
                                    'id'               => 'mediabrowser_button',
                                    'style'            => 'display: none;'
                                ));

        return $mediaBrowser->getXHtml($_ARRAYLANG['TXT_CORE_CM_BROWSE']);
    }

    /**
     * Shows the "Add Entry" page.
     *
     * @global     array
     * @global  ADONewConnection
     */
    function addEntry() {
        global $_ARRAYLANG, $objDatabase;

        \JS::activate('jqueryui');
        \JS::activate('prototype');

        $this->_strPageTitle = $_ARRAYLANG['TXT_DATA_ENTRY_ADD_TITLE'];
        $this->_objTpl->loadTemplateFile('module_data_entries_edit.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_LANGUAGES'    =>    $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_LANGUAGES'],
            'TXT_ADD_ENTRY'         =>  $_ARRAYLANG['TXT_DATA_ENTRY_ADD_TITLE'],
            'TXT_EDIT_SUBMIT'        =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_TOP_LEVEL'         =>  $_ARRAYLANG['TXT_TOP_LEVEL'],
            'TXT_ADV_SETTINGS'      =>  $_ARRAYLANG['TXT_ADV_SETTINGS'],
            'TXT_DIV_MODE'          =>  $_ARRAYLANG['TXT_DATA_ENTRY_MODE'],
            'TXT_DIV_MODE_NORMAL'   =>  $_ARRAYLANG['TXT_DATA_ENTRY_MODE_NORMAL'],
            'TXT_DIV_MODE_FORWARD'  =>  $_ARRAYLANG['TXT_DATA_ENTRY_MODE_FORWARD'],
            'TXT_REDIRECT_HELP'     =>  htmlspecialchars($_ARRAYLANG['TXT_REDIRECT_HELP'], ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_DIV_ACTIVATE_RELEASE_TIME' =>  $_ARRAYLANG['TXT_ACTIVATE_RELEASE_TIME'],
            'TXT_DIV_RELEASE_TIME'          =>  $_ARRAYLANG['TXT_RELEASE_TIME'],
            'TXT_ENDDATE_ENDLESS'           =>  $_ARRAYLANG['TXT_ENDLESS'],
            'DIV_MODE_NORMAL_CHECKED'       => "checked=\"checked\"",
            'DISPLAY_RELEASE_TIME'          => "none",
            'RADIO_RELEASE_TIME_CHECKED'    => "",
            'RELEASE_DATE'                  => date("Y-m-d"),
            'RELEASE_DATE_END'              => date("Y-m-d"),
            'RELEASE_HOUR_OPTIONS'          => $this->getTimeOptions(23, 0),
            'RELEASE_MINUTES_OPTIONS'       => $this->getTimeOptions(59, 0),
            'RELEASE_HOUR_OPTIONS_END'      => $this->getTimeOptions(23, 12),
            'RELEASE_MINUTES_OPTIONS_END'   => $this->getTimeOptions(23, 0),
            'ENDLESS_CHECKED'               => "checked=\"checked\"",
            'RELEASE_DISPLAY'               => "disabled=\"disabled\"",
            'RELEASE_COLOR'                 => "gray",
            'MEDIABROWSER_BUTTON'           => self::getMediaBrowserButton()
));

        $arrCategories = $this->createCategoryArray();

        $catTree = $this->buildCatTree($arrCategories);

        //Show language-selection
        if (count($this->_arrLanguages) > 0) {
            $intLanguageCounter = 0;
               $arrLanguages = array(0 => '', 1 => '', 2 => '');
               $strJsTabToDiv = '';

            foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                $this->parseCategorySelector($catTree, $arrCategories, 0, 0, $intLanguageId);
                   $arrLanguages[$intLanguageCounter%3] .= '<input checked="checked" type="checkbox" name="frmEditEntry_Languages[]" value="'.$intLanguageId.'" onclick="switchBoxAndTab(this, \'addEntry_'.$arrTranslations['long'].'\');" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';

                $strJsTabToDiv .= 'arrTabToDiv["addEntry_'.$arrTranslations['long'].'"] = "'.$arrTranslations['long'].'";'."\n";

                //Parse the TABS at the top of the language-selection
                $this->_objTpl->setVariable(array(
                    'TABS_LINK_ID'            =>    'addEntry_'.$arrTranslations['long'],
                    'TABS_DIV_ID'            =>    $arrTranslations['long'],
                    'TABS_CLASS'            =>    ($intLanguageCounter == 0) ? 'active' : 'inactive',
                    'TABS_DISPLAY_STYLE'    =>    'display: inline;',
                    'TABS_NAME'                =>    $arrTranslations['long']

                ));
                $this->_objTpl->parse('showLanguageTabs');

                //Parse the DIVS for every language
                $this->_objTpl->setVariable(array(
                    'TXT_DIV_SUBJECT'               => $_ARRAYLANG['TXT_DATA_ENTRY_ADD_SUBJECT'],
                    'TXT_DIV_IMAGE'                 => $_ARRAYLANG['TXT_DATA_ENTRY_ADD_IMAGE'],
                    'TXT_DIV_IMAGE_BROWSE'          => $_ARRAYLANG['TXT_DATA_ENTRY_ADD_IMAGE_BROWSE'],
                    'TXT_DIV_THUMBNAIL'             => $_ARRAYLANG['TXT_DATA_ENTRY_ADD_THUMBNAIL'],
                    'TXT_DIV_THUMBNAIL_BROWSE'      => $_ARRAYLANG['TXT_DATA_ENTRY_ADD_THUMBNAIL_BROWSE'],
                    'TXT_DIV_THUMBNAIL_TYPE'        => $_ARRAYLANG['TXT_DIV_THUMBNAIL_TYPE'],
                    'TXT_DATA_THUMBNAIL_ORI_DESC'   => $_ARRAYLANG['TXT_DATA_THUMBNAIL_ORI_DESC'],
                    'TXT_DATA_THUMBNAIL_DIFF_DESC'  => $_ARRAYLANG['TXT_DATA_THUMBNAIL_DIFF_DESC'],
                    'TXT_DATA_THUMBNAIL_SIZE'       => $_ARRAYLANG['TXT_DATA_THUMBNAIL_SIZE'],
                    'THUMBNAIL_METHOD_ORI_CHECKED'  => 'checked="checked"',
                    'THUMBNAIL_ORI_WIDTH'           => 80,
                    'THUMBNAIL_ORI_HEIGHT'          => 80,
                    'DIV_THUMBNAIL_DIFF_DISPLAY'    => 'none',
                    'TXT_DATA_WIDTH'                => $_ARRAYLANG['TXT_DATA_WIDTH'],
                    'TXT_DATA_HEIGHT'               => $_ARRAYLANG['TXT_DATA_HEIGHT'],
                    'TXT_DIV_CATEGORIES'    =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_CATEGORIES'],
                    'TXT_DIV_PLACEHOLDER'   =>  $_ARRAYLANG['TXT_DATA_PLACEHOLDER'],
                    'TXT_DIV_ATTACHMENT'    =>  $_ARRAYLANG['TXT_DATA_ATTACHMENT'],
                    'TXT_DIV_ATTACHMENT_DESC' =>  $_ARRAYLANG['TXT_DATA_ATTACHMENT_DESC'],
                    'TXT_FORWARD_URL'       =>  $_ARRAYLANG['TXT_FORWARD_URL'],
                    'DISPLAY_FORWARD_URL'   =>  "none",
                    'TXT_TARGET_WINDOW'     =>  $_ARRAYLANG['TXT_TARGET_WINDOW'],
                    'TXT_TARGET_BLANK'      =>  $_ARRAYLANG['TXT_TARGET_BLANK'],
                    'TXT_TARGET_PARENT'     =>  $_ARRAYLANG['TXT_TARGET_PARENT'],
                    'TXT_TARGET_SELF'       =>  $_ARRAYLANG['TXT_TARGET_SELF'],
                    'TXT_TARGET_TOP'        =>  $_ARRAYLANG['TXT_TARGET_TOP']

                ));

                //Filter out active categories for this language
                $intCategoriesCounter = 0;
                $arrCategoriesContent = array(0 => '', 1 => '', 2 => '');
                foreach ($arrCategories as $intCategoryId => $arrCategoryValues) {
                    if ($arrCategoryValues[$intLanguageId]['is_active']) {
                        $arrCategoriesContent[$intCategoriesCounter%3] .= '<input type="checkbox" name="frmEditEntry_Categories_'.$intLanguageId.'[]" value="'.$intCategoryId.'" />'.$arrCategoryValues[$intLanguageId]['name'].'<br />';
                        ++$intCategoriesCounter;
                    }
                }

                //$objRs = $objDatabase->Execute("SHOW TABLE STATUS WHERE name = '".DBPREFIX."module_data_messages'");
                $objRs = $objDatabase->Execute("SHOW TABLE STATUS");
                while (!$objRs->EOF) {
                    if ($objRs->fields['Name'] == DBPREFIX."module_data_messages") {
                        $autoIncrement = $objRs->fields['Auto_increment'];
                    }
                    $objRs->MoveNext();
                }

                $this->_objTpl->setVariable(array(
                    'DIV_ID'            =>    $arrTranslations['long'],
                    'DIV_LANGUAGE_ID'    =>    $intLanguageId,
                    'DIV_DISPLAY_STYLE'    =>    ($intLanguageCounter == 0) ? 'display: block;' : 'display: none;',
                    'DIV_TITLE'            =>    $arrTranslations['long'],
                    'DIV_CATEGORIES_1'    =>    $arrCategoriesContent[0],
                    'DIV_CATEGORIES_2'    =>    $arrCategoriesContent[1],
                    'DIV_CATEGORIES_3'    =>    $arrCategoriesContent[2],
                    'DIV_CONTENT'        =>    new \Cx\Core\Wysiwyg\Wysiwyg('frmEditEntry_Content_'.$intLanguageId, null, 'full'),
                    'DIV_PLACEHOLDER'   =>  "DETAIL_".$autoIncrement
                ));
                $this->_objTpl->parse('showLanguageDivs');

                ++$intLanguageCounter;
            }

               $this->_objTpl->setVariable(array(
                   'EDIT_POST_ACTION'        =>    '?cmd=Data&amp;act=insertEntry',
                   'EDIT_MESSAGE_ID'        =>    0,
                   'EDIT_LANGUAGES_1'        =>    $arrLanguages[0],
                   'EDIT_LANGUAGES_2'        =>    $arrLanguages[1],
                   'EDIT_LANGUAGES_3'        =>    $arrLanguages[2],
                   'EDIT_JS_TAB_TO_DIV'    =>    $strJsTabToDiv
               ));
        }
    }



    /**
     * Adds a new entry to the database. Collected data in POST is checked for valid values.
     *
     * @global     array
     * @global     ADONewConnection
     */
    function insertEntry() {
        global $_ARRAYLANG, $objDatabase;

        if (isset($_POST['frmEditEntry_Languages']) && is_array($_POST['frmEditEntry_Languages'])) {

            $objRs = $objDatabase->Execute(" SELECT MAX(sort) AS sort FROM ".DBPREFIX."module_data_messages");
            $sort = $objRs->fields['sort']+1;

            $mode = (isset($_POST['frmEditEntry_Mode'])) ? contrexx_addslashes($_POST['frmEditEntry_Mode']) : "normal";

            // the release times
            if (isset($_POST['release_time_activated'])) {
                if (isset($_POST['endless'])) {
                    // no end
                    $release_time_end = 0;
                } else {
                    $endDateParts = explode("-", $_POST['release_date_end']);
                    $hour = intval($_POST['release_hour_end']);
                    $minute = intval($_POST['release_minute_end']);
                    $day = intval($endDateParts[2]);
                    $month = intval($endDateParts[1]);
                    $year = intval($endDateParts[0]);
                    $release_time_end = mktime($hour, $minute, 0, $month, $day, $year);
                }

                $dateParts = explode("-", $_POST['release_date']);
                $hour = intval($_POST['release_hour']);
                $minute = intval($_POST['release_minute']);
                $day = intval($dateParts[2]);
                $month = intval($dateParts[1]);
                $year = intval($dateParts[0]);
                $release_time = mktime($hour, $minute, 0, $month, $day, $year);
            } else {
                // no release time associated
                $release_time = 0;
                $release_time_end = 0;
            }

            //Create entry with general-information for all languages
            $objDatabase->Execute('    INSERT INTO '.DBPREFIX.'module_data_messages
                                    SET `time_created` = '.time().',
                                        `time_edited` = '.time().',
                                        `hits` = 0,
                                        `mode` = "'.$mode.'",
                                        `sort` = '.$sort.',
                                        `release_time` = '.$release_time.',
                                        `release_time_end`  = '.$release_time_end);
            $intMessageId = $objDatabase->insert_id();
            $this->insertEntryData($intMessageId);

            $this->writeMessageRSS();
            $this->writeCategoryRSS();

            $this->_strOkMessage = $_ARRAYLANG['TXT_DATA_ENTRY_ADD_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_ENTRY_ADD_ERROR_LANGUAGES'];
        }
    }



    /**
     * This function is used by the "insertEntry()" and "updateEntry()" function. It collects all values from
     * $_POST and creates the new entries in the database. This function was extracted from original source to be as
     * DRY/SPOT as possible.
     *
     * @global     ADONewConnection
     * @param    integer        $intMessageId: This is the id of the message which the new values will be linked to.
     */
    function insertEntryData($intMessageId) {
        global $objDatabase;

        $intMessageId = intval($intMessageId);
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        //Collect data for every language
        $arrValues = array();
        foreach ($_POST as $strKey => $strValue) {
            if (substr($strKey,0,strlen('frmEditEntry_Subject_')) == 'frmEditEntry_Subject_') {
                $intLanguageId = intval(substr($strKey,strlen('frmEditEntry_Subject_')));
                $arrValues[$intLanguageId] = array(
                    'subject'           => contrexx_addslashes(strip_tags($_POST['frmEditEntry_Subject_'.$intLanguageId])),
                    'content'           => contrexx_addslashes($_POST['frmEditEntry_Content_'.$intLanguageId]),
                    'is_active'         => intval(in_array($intLanguageId, $_POST['frmEditEntry_Languages'])),
                    //'categories'      => (isset($_POST['frmEditEntry_Categories_'.$intLanguageId])) ? $_POST['frmEditEntry_Categories_'.$intLanguageId] : array(),
                    'categories'        => (isset($_POST['assignedBlocks_'.$intLanguageId])) ? array($_POST['assignedBlocks_'.$intLanguageId]) : array(),
                    'image'             => contrexx_addslashes(strip_tags($_POST['frmEditEntry_Image_'.$intLanguageId])),
                    'thumbnail'         => isset($_POST['frmEditEntry_Thumbnail_Method_'.$intLanguageId]) && $_POST['frmEditEntry_Thumbnail_Method_'.$intLanguageId] == 'different' ? contrexx_addslashes(strip_tags($_POST['frmEditEntry_Thumbnail_'.$intLanguageId])) : '',
                    'thumbnail_type'    => isset($_POST['frmEditEntry_Thumbnail_Type_'.$intLanguageId]) && $_POST['frmEditEntry_Thumbnail_Type_'.$intLanguageId] == '1' ? 'thumbnail' : 'original',
                    'thumbnail_width'   => isset($_POST['frmEditEntry_Thumbnail_Method_'.$intLanguageId]) && $_POST['frmEditEntry_Thumbnail_Method_'.$intLanguageId] == 'original' ? intval($_POST['frmEditEntry_Thumbnail_Width_'.$intLanguageId]) : 0,
                    'thumbnail_height'  => isset($_POST['frmEditEntry_Thumbnail_Method_'.$intLanguageId]) && $_POST['frmEditEntry_Thumbnail_Method_'.$intLanguageId] == 'original' ? intval($_POST['frmEditEntry_Thumbnail_Height_'.$intLanguageId]) : 0,
                    'attachment'        => contrexx_addslashes($_POST['frmEditEntry_Attachment_'.$intLanguageId]),
                    'attachment_desc'   => contrexx_addslashes($_POST['frmEditEntry_Attachment_Desc_'.$intLanguageId]),
                    'forward_url'       => contrexx_addslashes($_POST['frmEditEntry_ForwardUrl_'.$intLanguageId]),
                    'forward_target'    => contrexx_addslashes($_POST['frmEditEntry_ForwardTarget_'.$intLanguageId]),
                );
            }
        }

        //save the placeholder
        if (isset($_POST['frmEditEntry_Placeholder'])) {
            $placeholder = $this->_formatPlaceholder($_POST['frmEditEntry_Placeholder']);
            $objDatabase->Execute("  INSERT INTO ".DBPREFIX."module_data_placeholders
                                     (`type`, `ref_id`, `placeholder`)
                                     VALUES
                                     ('entry', ".$intMessageId.",
                                      '".contrexx_input2db($placeholder)."')"
                                 );
          $err = $objDatabase->ErrorNo();
          if ($err == 1062) {  //duplicate entry error
              $placeholder .= rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9); // not very beautiful...
              $objDatabase->Execute(" INSERT INTO ".DBPREFIX."module_data_placeholders
                                      (`type`, `ref_id`, `placeholder`)
                                      VALUES
                                      ('entry', ".$intMessageId.",
                                      '".$placeholder."')");
          }
        }

        //Insert collected data
        foreach ($arrValues as $intLanguageId => $arrEntryValues) {
            $objDatabase->Execute('    INSERT INTO '.DBPREFIX.'module_data_messages_lang
                                    SET    `message_id` = '.$intMessageId.',
                                        `lang_id` = '.$intLanguageId.',
                                        `is_active` = "'.$arrEntryValues['is_active'].'",
                                        `subject` = "'.$arrEntryValues['subject'].'",
                                        `content` = "'.$arrEntryValues['content'].'",
                                        `image` = "'.$arrEntryValues['image'].'",
                                        `thumbnail` = "'.$arrEntryValues['thumbnail'].'",
                                        `thumbnail_type` = "'.$arrEntryValues['thumbnail_type'].'",
                                        `thumbnail_width` = "'.$arrEntryValues['thumbnail_width'].'",
                                        `thumbnail_height` = "'.$arrEntryValues['thumbnail_height'].'",
                                        `attachment` = "'.$arrEntryValues['attachment'].'",
                                        `attachment_description` = "'.$arrEntryValues['attachment_desc'].'",
                                        `forward_url` = "'.$arrEntryValues['forward_url'].'",
                                        `forward_target` = "'.$arrEntryValues['forward_target'].'"
                                ');

            //Assign message to categories
            if (is_array($arrEntryValues['categories'])) {
                foreach ($arrEntryValues['categories'][0] as $intKey => $intCategoryId) {
                    $objDatabase->Execute('    INSERT INTO '.DBPREFIX.'module_data_message_to_category
                                            SET `message_id` = '.$intMessageId.',
                                                `category_id` = '.intval($intCategoryId).',
                                                `lang_id` = '.$intLanguageId.'
                                        ');
                    if ($intLanguageId == $this->_intLanguageId) {
                       $_GET['catId'] = $intCategoryId;
                    }
                }
            }

            // create thumbnail if required
            if (empty($arrEntryValues['thumbnail'])) {
                if (!isset($objImage)) {
                    $objImage = new \ImageManager();
                }
                $strPath = dirname($cx->getWebsitePath().$arrEntryValues['image']).'/';
                $strWebPath = substr($strPath, strlen($cx->getWebsiteOffsetPath()));
                $file = basename($arrEntryValues['image']);

                $objImage->_createThumbWhq(
                        $strPath,
                        $strWebPath,
                        $file,
                        $arrEntryValues['thumbnail_width'],
                        $arrEntryValues['thumbnail_height'],
                        90,
                        '.thumb',
                        $cx->getWebsiteImagesDataPath() . '/',
                        $cx->getWebsiteImagesDataWebPath() . '/',
                        $intMessageId.'_'.$intLanguageId.'_'.$file
                );
            }
        }
    }


    /**
     * Shows the "Edit Entry" page.
     *
     * @global     array
     * @param     integer        $intEntryId: The values of this entry will be loaded into the form.
     */
    function editEntry($intEntryId, $copy = false) {
        global $_ARRAYLANG;

        \JS::activate('jqueryui');
        \JS::activate('prototype');

        $this->_strPageTitle = $copy ? $_ARRAYLANG['TXT_DATA_ENTRY_COPY_TITLE'] : $_ARRAYLANG['TXT_DATA_ENTRY_EDIT_TITLE'];
        $this->_objTpl->loadTemplateFile('module_data_entries_edit.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_LANGUAGES'    =>    $_ARRAYLANG['TXT_DATA_CATEGORY_ADD_LANGUAGES'],
            'TXT_EDIT_SUBMIT'        =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_ADV_SETTINGS'      =>  $_ARRAYLANG['TXT_ADV_SETTINGS'],
            'TXT_DIV_MODE'          =>  $_ARRAYLANG['TXT_DATA_ENTRY_MODE'],
            'TXT_DIV_MODE_NORMAL'   =>  $_ARRAYLANG['TXT_DATA_ENTRY_MODE_NORMAL'],
            'TXT_DIV_MODE_FORWARD'  =>  $_ARRAYLANG['TXT_DATA_ENTRY_MODE_FORWARD'],
            'TXT_DIV_ACTIVATE_RELEASE_TIME' =>  $_ARRAYLANG['TXT_ACTIVATE_RELEASE_TIME'],
            'TXT_DIV_RELEASE_TIME'  =>  $_ARRAYLANG['TXT_RELEASE_TIME'],
            'TXT_ENDDATE_ENDLESS'   =>  $_ARRAYLANG['TXT_ENDLESS'],
            'MEDIABROWSER_BUTTON'   =>  self::getMediaBrowserButton()
        ));

        $arrCategories = $this->createCategoryArray();
        $arrEntries = $this->createEntryArray();

        $intEntryId = intval($intEntryId);

        $catTree = $this->buildCatTree($arrCategories);
        $catKeys = array_keys($arrEntries[$intEntryId]['categories'][$this->_intLanguageId]);
        $categories = $catKeys;
        $ie = (preg_match("/MSIE (6|7)/", $_SERVER['HTTP_USER_AGENT'])) ? true : false;


        $release_date = $arrEntries[$intEntryId]['release_time'];
        $release_date_end = $arrEntries[$intEntryId]['release_time_end'];
        $this->_objTpl->setVariable(array(
            'DIV_MODE_NORMAL_CHECKED'       => ($arrEntries[$intEntryId]['mode'] == "normal") ? "checked=\"checked\"" : "",
            'DIV_MODE_FORWARD_CHECKED'      => ($arrEntries[$intEntryId]['mode'] == "forward") ? "checked=\"checked\"" : "",
            'DISPLAY_RELEASE_TIME'          => ($release_date) ? (($ie) ? "block" : "table-row") : "none",
            'RADIO_RELEASE_TIME_CHECKED'    => ($release_date) ? "checked=\"checked\"" : "",
            'RELEASE_DATE'                  => ($release_date == 0) ? date("Y-m-d") : date("Y-m-d", $release_date),
            'RELEASE_DATE_END'              => ($release_date_end == 0) ? date("Y-m-d") : date("Y-m-d", $release_date_end),
            'RELEASE_HOUR_OPTIONS'          => $this->getTimeOptions(23, ($release_date == 0) ? "00" : date("H", $release_date)),
            'RELEASE_MINUTES_OPTIONS'       => $this->getTimeOptions(59, ($release_date == 0) ? "00" : date("i", $release_date)),
            'RELEASE_HOUR_OPTIONS_END'      => $this->getTimeOptions(23, ($release_date_end == 0) ? "12" : date("H", $release_date_end)),
            'RELEASE_MINUTES_OPTIONS_END'   => $this->getTimeOptions(59, ($release_date_end == 0) ? "00" : date("i", $release_date_end)),
            'ENDLESS_CHECKED'               => ($release_date_end == 0) ? "checked=\"checked\"" : "",
            'RELEASE_DISPLAY'               => ($release_date_end == 0) ? "disabled=\"disabled\"" : "",
            'RELEASE_COLOR'                 => ($release_date_end == 0) ? "gray" : "black"
        ));

        if ($intEntryId > 0 && key_exists($intEntryId,$arrEntries)) {
            if (count($this->_arrLanguages) > 0) {
                $intLanguageCounter = 0;
                $boolFirstLanguage = true;
                   $arrLanguages = array(0 => '', 1 => '', 2 => '');
                   $strJsTabToDiv = '';

                foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                    $this->parseCategorySelector($catTree, $arrCategories, $categories, 0, $intLanguageId, false);

                    $boolLanguageIsActive = $arrEntries[$intEntryId]['translation'][$intLanguageId]['is_active'];

                       $arrLanguages[$intLanguageCounter%3] .= '<input '.(($boolLanguageIsActive) ? 'checked="checked"' : '').' type="checkbox" name="frmEditEntry_Languages[]" value="'.$intLanguageId.'" onclick="switchBoxAndTab(this, \'addEntry_'.$arrTranslations['long'].'\');" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';
                    $strJsTabToDiv .= 'arrTabToDiv["addEntry_'.$arrTranslations['long'].'"] = "'.$arrTranslations['long'].'";'."\n";

                    //Parse the TABS at the top of the language-selection
                    $this->_objTpl->setVariable(array(
                        'TABS_LINK_ID'            =>    'addEntry_'.$arrTranslations['long'],
                        'TABS_DIV_ID'            =>    $arrTranslations['long'],
                        'TABS_CLASS'            =>    ($boolFirstLanguage && $boolLanguageIsActive) ? 'active' : 'inactive',
                        'TABS_DISPLAY_STYLE'    =>    ($boolLanguageIsActive) ? 'display: inline;' : 'display: none;',
                        'TABS_NAME'                =>    $arrTranslations['long'],
                    ));
                    $this->_objTpl->parse('showLanguageTabs');

                    //Parse the DIVS for every language
                    $this->_objTpl->setVariable(array(
                        'TXT_DIV_SUBJECT'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_SUBJECT'],
                        'TXT_DIV_IMAGE'            =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_IMAGE'],
                        'TXT_DIV_THUMBNAIL'        =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_THUMBNAIL'],
                        'TXT_DIV_IMAGE_BROWSE'    =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_IMAGE_BROWSE'],
                        'TXT_DIV_THUMBNAIL_BROWSE'    =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_THUMBNAIL_BROWSE'],
                        'TXT_DATA_THUMBNAIL_ORI_DESC'   => $_ARRAYLANG['TXT_DATA_THUMBNAIL_ORI_DESC'],
                        'TXT_DATA_THUMBNAIL_DIFF_DESC'  => $_ARRAYLANG['TXT_DATA_THUMBNAIL_DIFF_DESC'],
                        'TXT_DATA_THUMBNAIL_SIZE'       => $_ARRAYLANG['TXT_DATA_THUMBNAIL_SIZE'],
                        'THUMBNAIL_METHOD_ORI_CHECKED'  => 'checked="checked"',
                        'DIV_THUMBNAIL_DIFF_DISPLAY'    => 'none',
                        'TXT_DATA_WIDTH'                => $_ARRAYLANG['TXT_DATA_WIDTH'],
                        'TXT_DATA_HEIGHT'               => $_ARRAYLANG['TXT_DATA_HEIGHT'],
                        'TXT_DIV_CATEGORIES'    =>    $_ARRAYLANG['TXT_DATA_ENTRY_ADD_CATEGORIES'],
                        'TXT_DIV_PLACEHOLDER'   =>  $_ARRAYLANG['TXT_DATA_PLACEHOLDER'],
                        'TXT_DIV_ATTACHMENT'    =>  $_ARRAYLANG['TXT_DATA_ATTACHMENT'],
                        'TXT_DIV_ATTACHMENT_DESC' =>  $_ARRAYLANG['TXT_DATA_ATTACHMENT_DESC'],
                        'TXT_FORWARD_URL'       =>  $_ARRAYLANG['TXT_FORWARD_URL'],
                        'TXT_REDIRECT_HELP'     =>  htmlspecialchars($_ARRAYLANG['TXT_REDIRECT_HELP'], ENT_QUOTES, CONTREXX_CHARSET),
                        'TXT_TARGET_WINDOW'     =>  $_ARRAYLANG['TXT_TARGET_WINDOW'],
                        'DISPLAY_FORWARD_URL'   => ($arrEntries[$intEntryId]['mode'] == "forward") ? (($ie) ? "block" : "table-row") : "none",
                        'TXT_TARGET_BLANK'      =>  $_ARRAYLANG['TXT_TARGET_BLANK'],
                        'TXT_TARGET_PARENT'     =>  $_ARRAYLANG['TXT_TARGET_PARENT'],
                        'TXT_TARGET_SELF'       =>  $_ARRAYLANG['TXT_TARGET_SELF'],
                        'TXT_TARGET_TOP'        =>  $_ARRAYLANG['TXT_TARGET_TOP']
                    ));

                    //Filter out active categories for this language
                    $intCategoriesCounter = 0;
                    $arrCategoriesContent = array(0 => '', 1 => '', 2 => '');
                    foreach ($arrCategories as $intCategoryId => $arrCategoryValues) {
                        if ($arrCategoryValues[$intLanguageId]['is_active']) {
                            $arrCategoriesContent[$intCategoriesCounter%3] .= '<input type="checkbox" name="frmEditEntry_Categories_'.$intLanguageId.'[]" value="'.$intCategoryId.'" '.(key_exists($intCategoryId, $arrEntries[$intEntryId]['categories'][$intLanguageId]) ? 'checked="checked"' : '').' />'.$arrCategoryValues[$intLanguageId]['name'].'<br />';
                            ++$intCategoriesCounter;
                        }
                    }

                    $selected = "selected=\"selected\"";

                    $this->_objTpl->setVariable(array(
                        'DIV_ID'            =>    $arrTranslations['long'],
                        'DIV_LANGUAGE_ID'    =>    $intLanguageId,
                        'DIV_DISPLAY_STYLE'    =>    ($boolFirstLanguage && $boolLanguageIsActive) ? 'display: block;' : 'display: none;',
                        'DIV_TITLE'            =>    $arrTranslations['long'],
                        'DIV_SUBJECT'        =>    $arrEntries[$intEntryId]['translation'][$intLanguageId]['subject'],
                        'DIV_IMAGE'            =>    $arrEntries[$intEntryId]['translation'][$intLanguageId]['image'],
                        'DIV_THUMBNAIL'        =>    $arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail'],
                        'THUMBNAIL_ORI_WIDTH' => $arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail_width'],
                        'THUMBNAIL_ORI_HEIGHT' => $arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail_height'],
                        'THUMBNAIL_METHOD_ORI_CHECKED' => empty($arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail']) ? 'checked="checked"' : '',
                        'THUMBNAIL_METHOD_DIFF_CHECKED' => !empty($arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail']) ? 'checked="checked"' : '',
                        'DIV_THUMBNAIL_ORI_DISPLAY' => empty($arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail']) ? '' : 'none',
                        'DIV_THUMBNAIL_DIFF_DISPLAY' => !empty($arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail']) ? '' : 'none',
                        'DIV_THUMBNAIL_'.(!empty($arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail']) ? 'DIFF' : 'ORI').'_DISPLAY' => '',
                        'DIV_THUMBNAIL_TYPE_CHECKED' => ($arrEntries[$intEntryId]['translation'][$intLanguageId]['thumbnail_type'] == 'thumbnail') ? 'checked=\"checked\"' : '',
                        'DIV_CATEGORIES_1'    =>    $arrCategoriesContent[0],
                        'DIV_CATEGORIES_2'    =>    $arrCategoriesContent[1],
                        'DIV_CATEGORIES_3'    =>    $arrCategoriesContent[2],
                        'DIV_CONTENT'        =>    new \Cx\Core\Wysiwyg\Wysiwyg('frmEditEntry_Content_'.$intLanguageId, $arrEntries[$intEntryId]['translation'][$intLanguageId]['content'], 'full'),
                        'DIV_PLACEHOLDER'   =>  $arrEntries[$intEntryId]['placeholder'],
                        'DIV_ATTACHMENT'    =>  $arrEntries[$intEntryId]['translation'][$intLanguageId]['attachment'],
                        'DIV_ATTACHMENT_DESC'    =>  $arrEntries[$intEntryId]['translation'][$intLanguageId]['attachment_desc'],
                        'DIV_FORWARD_URL'   =>  $arrEntries[$intEntryId]['translation'][$intLanguageId]['forward_url'],
                        'TARGET_BLANK_SELECTED'    => ($arrEntries[$intEntryId]['translation'][$intLanguageId]['forward_target'] == "_blank") ? $selected : "",
                        'TARGET_PARENT_SELECTED'   => ($arrEntries[$intEntryId]['translation'][$intLanguageId]['forward_target'] == "_parent") ? $selected : "",
                        'TARGET_SELF_SELECTED'     => ($arrEntries[$intEntryId]['translation'][$intLanguageId]['forward_target'] == "_self") ? $selected : "",
                        'TARGET_TOP_SELECTED'      => ($arrEntries[$intEntryId]['translation'][$intLanguageId]['forward_target'] == "_top") ? $selected : "",
                        'TARGET_NOTHING_SELECTED'  => (empty($arrEntries[$intEntryId]['translation'][$intLanguageId]['forward_target'])) ? $selected : ""
                    ));

                    $this->_objTpl->parse('showLanguageDivs');

                    if ($boolLanguageIsActive) {
                        $boolFirstLanguage = false;
                    }

                    ++$intLanguageCounter;
                }

                   $this->_objTpl->setVariable(array(
                       'EDIT_POST_ACTION'        =>    '?cmd=Data&amp;act='.($copy ? 'insertEntry' : 'updateEntry'),
                       'EDIT_MESSAGE_ID'        =>    $copy ? 0 : $intEntryId,
                       'EDIT_LANGUAGES_1'        =>    $arrLanguages[0],
                       'EDIT_LANGUAGES_2'        =>    $arrLanguages[1],
                       'EDIT_LANGUAGES_3'        =>    $arrLanguages[2],
                       'EDIT_JS_TAB_TO_DIV'    =>    $strJsTabToDiv
                   ));
            }
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_ENTRY_EDIT_ERROR_ID'];
        }
    }



    /**
     * Collects and validates all values from the edit-entry-form. Updates values in database.
     *
     * @global     array
     * @global     ADONewConnection
     */
    function updateEntry() {
        global $_ARRAYLANG, $objDatabase;

        $intMessageId = intval($_POST['frmEditCategory_MessageId']);

        if (isset($_POST['frmEditEntry_Languages']) && is_array($_POST['frmEditEntry_Languages']) && $intMessageId > 0) {

            $mode = (isset($_POST['frmEditEntry_Mode'])) ? contrexx_addslashes($_POST['frmEditEntry_Mode']) : "normal";

            // the release times
            if (isset($_POST['release_time_activated'])) {
                if (isset($_POST['endless'])) {
                    // no end
                    $release_time_end = 0;
                } else {
                    $endDateParts = explode("-", $_POST['release_date_end']);
                    $hour = intval($_POST['release_hour_end']);
                    $minute = intval($_POST['release_minute_end']);
                    $day = intval($endDateParts[2]);
                    $month = intval($endDateParts[1]);
                    $year = intval($endDateParts[0]);
                    $release_time_end = mktime($hour, $minute, 0, $month, $day, $year);
                }

                $dateParts = explode("-", $_POST['release_date']);
                $hour = intval($_POST['release_hour']);
                $minute = intval($_POST['release_minute']);
                $day = intval($dateParts[2]);
                $month = intval($dateParts[1]);
                $year = intval($dateParts[0]);
                $release_time = mktime($hour, $minute, 0, $month, $day, $year);
            } else {
                // no release time associated
                $release_time = 0;
                $release_time_end = 0;
            }

            //Update general info
            $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_data_messages
                                    SET     `time_edited` = '.time().',
                                            `mode` = "'.$mode.'",
                                            `release_time` = "'.$release_time.'",
                                            `release_time_end` = "'.$release_time_end.'"
                                    WHERE    message_id='.$intMessageId.'
                                    LIMIT    1
                                ');


            //Remove existing data for all languages
            $objDatabase->Execute('    DELETE
                                    FROM    '.DBPREFIX.'module_data_messages_lang
                                    WHERE    message_id='.$intMessageId.'
                                ');

            $objDatabase->Execute('    DELETE
                                    FROM    '.DBPREFIX.'module_data_message_to_category
                                    WHERE    message_id='.$intMessageId.'
                                ');

            $objDatabase->Execute(" DELETE FROM ".DBPREFIX."module_data_placeholders
                                    WHERE   ref_id = ".$intMessageId);
            //Now insert new data
            $this->insertEntryData($intMessageId);

            $this->writeMessageRSS();
            $this->writeCategoryRSS();

            $this->_strOkMessage =  $_ARRAYLANG['TXT_DATA_ENTRY_UPDATE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_ENTRY_UPDATE_ERROR_LANGUAGES'];
        }
    }

    /**
     * Switch the state of an entry (active or inactive)
     * This function is called through ajax, hence the 'die' at the end.
     */
    function switchEntryState()
    {
        global $objDatabase;

        if (!isset($_GET['id']) && !isset($_GET['switchTo'])) {
            die();
        }

        $id = intval($_GET['id']);
        $switchTo = intval($_GET['switchTo']);

        $query = "  UPDATE ".DBPREFIX."module_data_messages
                    SET active = '".$switchTo."'
                    WHERE message_id = ".$id;
        $objDatabase->Execute($query);
        die();
    }

    /**
     * Switch the state of a category (active or inactive)
     * This function is called through ajax, hence the 'die' at the end.
     */
    function switchCategoryState()
    {
        global $objDatabase;

        if (!isset($_GET['id']) && !isset($_GET['switchTo'])) {
            die();
        }

        $id = intval($_GET['id']);
        $switchTo = intval($_GET['switchTo']);
        echo $id." ";
        echo $switchTo;

        $query = "  UPDATE ".DBPREFIX."module_data_categories
                    SET active = '".$switchTo."'
                    WHERE category_id = ".$id;
        $objDatabase->Execute($query);

        die();
    }



    /**
     * Removes the entry with id = $intEntry from database.
     *
     * @global     array
     * @global     ADONewConnection
     */
    function deleteEntry($intEntryId) {
        global $_ARRAYLANG, $objDatabase;

        $intEntryId = intval($intEntryId);
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if ($intEntryId > 0) {


                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_data_messages
                                        WHERE    message_id='.$intEntryId.'
                                        LIMIT    1
                                    ');

            if (!$this->_boolInnoDb) {
                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_data_messages_lang
                                        WHERE    message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_data_message_to_category
                                        WHERE    message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_data_message_to_category
                                        WHERE    message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_data_votes
                                        WHERE    message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_data_comments
                                        WHERE    message_id='.$intEntryId.'
                                    ');
            }

            $objDatabase->Execute("   DELETE FROM ".DBPREFIX."module_data_placeholders
                                      WHERE ref_id = ".$intEntryId);

            $objFile = new \File();
            foreach (glob($cx->getWebsiteImagesDataPath() . '/' . $intEntryId . '_*') as $image) {
                $objFile->delFile($cx->getWebsiteImagesDataPath() . '/', $cx->getWebsiteImagesDataWebPath() . '/', basename($image));
            }

            $this->writeMessageRSS();
            $this->writeCategoryRSS();
            $this->writeCommentRSS();

            $this->_strOkMessage = $_ARRAYLANG['TXT_DATA_ENTRY_DELETE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_DATA_ENTRY_DELETE_ERROR_ID'];
        }
    }


    /**
     * Performs the action for the dropdown-selection on the entry page. The behaviour depends on the parameter.
     *
     * @param    string        $strAction: the action passed by the formular.
     */
    function doEntryMultiAction($strAction='') {
        switch ($strAction) {
            case 'delete':
                foreach($_POST['selectedEntriesId'] as $intKey => $intEntryId) {
                    $this->deleteEntry($intEntryId);
                }
                break;
            default:
                //do nothing!
        }
    }

    /**
     * Shows the settings-page of the data-module.
     *
     * @global     array
     * @global  ADONewConnection
     */
    function showSettings() {
        global $_ARRAYLANG, $objDatabase;

        $this->_strPageTitle = $_ARRAYLANG['TXT_DATA_SETTINGS_TITLE'];
        $this->_objTpl->loadTemplateFile('module_data_settings.html',true,true);

        \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');

        if (\Cx\Core\Setting\Controller\Setting::getValue('dataUseModule')) {
            $useDatalist = 1;
        } else {
            $useDatalist = 0;
        }

        $ie = (preg_match("/MSIE (6|7)/", $_SERVER['HTTP_USER_AGENT'])) ? true : false;

        $this->_objTpl->setVariable(array(
            'TXT_GENERAL_TITLE'                            =>    $_ARRAYLANG['TXT_DATA_SETTINGS_GENERAL_TITLE'],
            'TXT_GENERAL_INTRODUCTION'                    =>    $_ARRAYLANG['TXT_DATA_SETTINGS_GENERAL_INTRODUCTION'],
            'TXT_GENERAL_INTRODUCTION_HELP'                =>    $_ARRAYLANG['TXT_DATA_SETTINGS_GENERAL_INTRODUCTION_HELP'],
            'TXT_COMMENTS_EDITOR_TEXTAREA'                =>    $_ARRAYLANG['TXT_DATA_SETTINGS_COMMENTS_EDITOR_TEXTAREA'],
            'TXT_TAG_TITLE'                                =>    $_ARRAYLANG['TXT_DATA_SETTINGS_TAG_TITLE'],
            'TXT_TAG_HITLIST'                            =>    $_ARRAYLANG['TXT_DATA_SETTINGS_TAG_HITLIST'],
            'TXT_TAG_HITLIST_HELP'                        =>    $_ARRAYLANG['TXT_DATA_SETTINGS_TAG_HITLIST_HELP'],
            'TXT_BLOCK_TITLE'                            =>    $_ARRAYLANG['TXT_DATA_SETTINGS_BLOCK_TITLE'],
            'TXT_BLOCK_ACTIVATE'                        =>    $_ARRAYLANG['TXT_DATA_SETTINGS_BLOCK_ACTIVATE'],
            'TXT_BLOCK_ACTIVATE_HELP'                    =>    $_ARRAYLANG['TXT_DATA_SETTINGS_BLOCK_ACTIVATE_HELP'],
            'TXT_BLOCK_MESSAGES'                        =>    $_ARRAYLANG['TXT_DATA_SETTINGS_BLOCK_MESSAGES'],
            'TXT_BLOCK_MESSAGES_HELP'                    =>    $_ARRAYLANG['TXT_DATA_SETTINGS_BLOCK_MESSAGES_HELP'],
            'TXT_ACTIVATED'                                =>    $_ARRAYLANG['TXT_ACTIVATED'],
            'TXT_DEACTIVATED'                            =>    $_ARRAYLANG['TXT_DEACTIVATED'],
            'TXT_BUTTON_SAVE'                            =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_GENERAL_TEMPLATE_CATEGORY'             =>  $_ARRAYLANG['TXT_DATA_TEMPLATE_CATEGORY'],
            'TXT_GENERAL_TEMPLATE_ENTRY'                =>  $_ARRAYLANG['TXT_DATA_TEMPLATE_ENTRY'],
            'TXT_GENERAL_TEMPLATE_SHADOWBOX'            =>  $_ARRAYLANG['TXT_DATA_SETTINGS_SHADOWBOX_TEMPLATE'],
            'TXT_USE_DATALIST'                          =>  $_ARRAYLANG['TXT_DATA_SETTINGS_USE_DATALIST'],
            'TXT_CONTENT_PAGE'                          =>  $_ARRAYLANG['TXT_DATA_SETTINGS_CONTENT_PAGE'],
            'TXT_BOX'                                   =>  $_ARRAYLANG['TXT_DATA_SETTINGS_BOX'],
            'TXT_GENERAL_ACTION'                        =>  $_ARRAYLANG['TXT_DATA_SETTINGS_ACTION'],
            "TXT_FRONTEND_PAGE"                         =>  $_ARRAYLANG['TXT_FRONTEND_PAGE'],
            "TXT_GENERAL_ACTION_HELP"                     => htmlentities($_ARRAYLANG['TXT_GENERAL_ACTION_HELP']),
            'TXT_GENERAL_BOX_WIDTH'                     =>  $_ARRAYLANG['TXT_GENERAL_BOX_WIDTH'],
            'TXT_GENERAL_BOX_HEIGHT'                    =>  $_ARRAYLANG['TXT_GENERAL_BOX_HEIGHT']
        ));

        $this->_objTpl->setVariable(array(
            'DATA_SETTINGS_GENERAL_INTRODUCTION'            =>    intval($this->_arrSettings['data_general_introduction']),
            'DATA_SETTINGS_TAG_HITLIST'                        =>    intval($this->_arrSettings['data_tags_hitlist']),
            'DATA_SETTINGS_BLOCK_ACTIVATE_ON'                =>    ($this->_arrSettings['data_block_activated'] == '1') ? 'checked="checked"' : '',
            'DATA_SETTINGS_BLOCK_ACTIVATE_OFF'                =>    ($this->_arrSettings['data_block_activated'] == '0') ? 'checked="checked"' : '',
            'DATA_SETTINGS_BLOCK_MESSAGES'                    =>    intval($this->_arrSettings['data_block_messages']),
            'DATA_SETTINGS_TEMPLATE_CATEGORY'               =>  contrexx_raw2xhtml($this->_arrSettings['data_template_category']),
            'DATA_SETTINGS_TEMPLATE_ENTRY'                  =>  contrexx_raw2xhtml($this->_arrSettings['data_template_entry']),
            'DATA_SETTINGS_TEMPLATE_SHADOWBOX'              =>  contrexx_raw2xhtml($this->_arrSettings['data_template_shadowbox']),
               'USE_DATALIST_CHECKED'                          =>  ($useDatalist) ? "checked=\"checked\"" : "",
               'TXT_PLACEHOLDER'                               =>  $_ARRAYLANG['TXT_DATA_PLACEHOLDER'],
               'PAGE_SELECT_DISPLAY'                           =>  ($this->_arrSettings['data_entry_action'] == "content") ? (($ie) ? "block" : "table-row") : "none",
            'ACTION_SELECTED_BOX'                           => ($this->_arrSettings['data_entry_action'] == "overlaybox") ? "selected=\"selected\"" : "",
               'ACTION_SELECTED_CONTENT'                       => ($this->_arrSettings['data_entry_action'] == "content") ? "selected=\"selected\"" : "",
               'DATA_SETTINGS_BOX_WIDTH'                       => intval($this->_arrSettings['data_shadowbox_width']),
               'DATA_SETTINGS_BOX_HEIGHT'                      => intval($this->_arrSettings['data_shadowbox_height']),
        ));


       // show the frontend pages
       $frontPages = $this->getFrontendPages();
       foreach ($frontPages as $pageId => $pageVal) {
           $pageName =  $pageVal['name']." (cmd: ".$pageVal['cmd'].")";
           $this->_objTpl->setVariable(array(
               "FRONTEND_PAGE"             => $pageName,
               "FRONTEND_PAGE_ID"          => $pageVal['cmd'],
               "FRONTEND_PAGE_SELECTED"    => ($pageVal['cmd'] == $this->_arrSettings['data_target_cmd']) ? "selected=\"selected\"" : ""
           ));
           $this->_objTpl->parse("frontendPage");
       }
    }



    /**
     * Validate and save the settings from $_POST into the database.
     *
     * @global    ADONewConnection
     * @global     array
     */
    function saveSettings() {
        global $objDatabase, $_ARRAYLANG;


        //On-Off-Settings can only be 0 or 1.
        $arrOnOffValues = array('frmSettings_CommentsAllow'                =>    'data_comments_activated',
                                'frmSettings_CommentsAllowAnonymous'    =>    'data_comments_anonymous',
                                'frmSettings_CommentsAutoActivate'        =>    'data_comments_autoactivate',
                                'frmSettings_CommentsNotification'        =>    'data_comments_notification',
                                'frmSettings_VotingAllow'                =>    'data_voting_activated',
                                'frmSettings_BlockActivated'            =>    'data_block_activated',
                                'frmSettings_RssActivated'                =>    'data_rss_activated'
                            );

        //Integer-Settings [0 .. infinite]
        $arrIntegerValues = array(    'frmSettings_CommentsTimeout'            =>    'data_comments_timeout',
                                    'frmSettings_GeneralIntroduction'        =>    'data_general_introduction',
                                    'frmSettings_BlockNumberOfMessages'        =>    'data_block_messages',
                                    'frmSettings_RssNumberOfMessages'        =>    'data_rss_messages',
                                    'frmSettings_RssNumberOfComments'        =>    'data_rss_comments',
                                    'frmSettings_TagHitlist'                =>    'data_tags_hitlist',
                                    'frmSettings_frontendPage'              =>  'data_target_cmd',
                                    'frmSettings_shadowbox_width'            =>  'data_shadowbox_width',
                                    'frmSettings_shadowbox_height'           =>  'data_shadowbox_height'
                            );

        // String Settings
        $arrTextValues = array(   'frmSettings_templateCategory'          => 'data_template_category',
                                    'frmSettings_templateEntry'             => 'data_template_entry',
                                    'frmSettings_templateShadowbox'       => 'data_template_shadowbox'
                                );

        //Enum-Settings, must be a value of a given list
           $arrEnumValues = array(    'frmSettings_CommentsEditor'    =>    'data_comments_editor',
                                   'frmSettings_action'            =>  'data_entry_action',);
           $arrEnumPossibilities = array(    'frmSettings_CommentsEditor'    =>    'wysiwyg,textarea',
                                           'frmSettings_action'    =>  'content,overlaybox');



        foreach ($_POST as $strKey => $strValue) {
                if (key_exists($strKey, $arrOnOffValues)) {
                    $objDatabase->Execute('    UPDATE '.DBPREFIX.'module_data_settings
                                            SET `value` = "'.intval($strValue).'"
                                            WHERE `name` = "'.$arrOnOffValues[$strKey].'"
                                        ');
                }

                if (key_exists($strKey, $arrIntegerValues)) {
                    $objDatabase->Execute('    UPDATE '.DBPREFIX.'module_data_settings
                                            SET `value` = "'.abs(intval($strValue)).'"
                                            WHERE `name` = "'.$arrIntegerValues[$strKey].'"
                                        ');
                }

                if (key_exists($strKey, $arrEnumValues)) {
                    $arrSplit = explode(',', $arrEnumPossibilities[$strKey]);

                    if (in_array($strValue, $arrSplit)) {
                        $objDatabase->Execute('    UPDATE '.DBPREFIX.'module_data_settings
                                                SET `value` = "'.$strValue.'"
                                                WHERE `name` = "'.$arrEnumValues[$strKey].'"
                                            ');
                    }
                }

                if (key_exists($strKey, $arrTextValues)) {
                    $objDatabase->Execute(" UPDATE ".DBPREFIX."module_data_settings
                                            SET `value` = '".contrexx_addslashes(trim($strValue))."'
                                            WHERE `name`= '".$arrTextValues[$strKey]."'
                                            ");
                }

        }

        \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
        \Cx\Core\Setting\Controller\Setting::set('dataUseModule', isset($_POST['frmSettings_useDatalist'])?1:0);
        \Cx\Core\Setting\Controller\Setting::storeFromPost();

        $this->_arrSettings = $this->createSettingsArray();

        $this->_strOkMessage = $_ARRAYLANG['TXT_DATA_SETTINGS_SAVE_SUCCESSFULL'];
    }

    /**
     * Format the placeholder
     *
     * Replace some stuff in the placeholder
     * @param string $str
     * @return string
     */
    function _formatPlaceholder($str)
    {
        return strtoupper(str_replace(array(" ", "ö", "ä", "ü"), array("_", "oe", "ae", "ue"), $str));
    }

    /**
     * Save the order of the entries
     *
     * Is called through ajax
     */
    function saveEntryOrder()
    {
        global $objDatabase;

        if ($_POST['entries']) {
            $entries = contrexx_input2db($_POST['entries']);
            foreach ($entries as $sort => $value) {
                $sort++;
                $id = explode('_', $value);
                $query = "UPDATE `".DBPREFIX."module_data_messages`
                          SET `sort` = ".$sort."
                          WHERE `message_id` = ".$id[1];
                $objDatabase->Execute($query);
            }
        } else {
            header("HTTP/1.0 500 Internal Server Error");
            return;
        }

    }

    /**
     * Save the order of categories
     *
     * Is called through ajax
     */
    function saveCategoryOrder()
    {
        global $objDatabase;

        if ($_POST['categories']) {
            $categories = contrexx_input2db($_POST['categories']);
            foreach ($categories as $sort => $value) {
                $sort++;
                $id = explode('_', $value);
                $query = "UPDATE ".DBPREFIX."module_data_categories
                          SET `sort` = ".$sort."
                          WHERE `category_id` = ".$id[1];
                $objDatabase->Execute($query);
            }
        } else {
            header("HTTP/1.0 500 Internal Server Error");
            return;
        }
    }

    private function getTimeOptions($amount = 12, $select = 0)
    {
        $retval = "";
        for ($i = 0; $i <= $amount; $i++) {
            $selected = ($i == $select) ? "selected=\"selected\"" : "";
            $retval .= "<option value=\"".$i."\" ".$selected.">".sprintf("%02d", $i)."</option>";
        }
        return $retval;
    }
}
