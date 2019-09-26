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
 * Blog
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */

namespace Cx\Modules\Blog\Controller;

/**
 * BlogAdmin
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */
class BlogManager extends \Cx\Modules\Blog\Controller\BlogLibrary {

    var $_objTpl;
    var $_strPageTitle  = '';
    var $_strErrMessage = '';
    var $_strOkMessage  = '';

    /**
    * Constructor   -> Create the module-menu and an internal template-object
    * @global   InitCMS
    * @global   \Cx\Core\Html\Sigma
    * @global   array
    */
    function __construct()
    {
        global $objInit, $objTemplate, $_CORELANG;

        parent::__construct();
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Blog/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->_intLanguageId = FRONTEND_LANG_ID;

        $objFWUser = \FWUser::getFWUserObject();
        $this->_intCurrentUserId = $objFWUser->objUser->getId();

        $strNavigation = '';

        $isAdmin = $objFWUser->objUser->getAdminStatus();
        //if(in_array(120, $objFWUser->objUser->getStaticPermissionIds()) || $isAdmin) {
            $strNavigation .= '<a href="index.php?cmd=Blog"
                    class="'.($_GET['act'] == '' ? 'active' : '').'">'
                        .$_CORELANG['TXT_BLOG_ENTRY_MANAGE_TITLE'].'</a>';
        //}
        if(in_array(121, $objFWUser->objUser->getStaticPermissionIds()) || $isAdmin) {
            $strNavigation .= '<a href="index.php?cmd=Blog&amp;act=addEntry"
                    class="'.(in_array($_GET['act'], array('addEntry', 'editEntry')) ? 'active' : '').'">'
                        .$_CORELANG['TXT_BLOG_ENTRY_ADD_TITLE'].'</a>';
        }
        if(in_array(122, $objFWUser->objUser->getStaticPermissionIds()) || $isAdmin) {
            $strNavigation .= '<a href="index.php?cmd=Blog&amp;act=manageCategory"
                    class="'.(in_array($_GET['act'], array('manageCategory', 'manageCategory')) ? 'active' : '').'">'
                        .$_CORELANG['TXT_BLOG_CATEGORY_MANAGE_TITLE'].'</a>';
        }
        if(in_array(125, $objFWUser->objUser->getStaticPermissionIds()) || $isAdmin) {
            $strNavigation .= '<a href="index.php?cmd=Blog&amp;act=networks"
                    class="'.(in_array($_GET['act'], array('networks', 'editNetwork')) ? 'active' : '').'">'
                        .$_CORELANG['TXT_BLOG_NETWORKS_TITLE'].'</a>';
        }
        if(in_array(124, $objFWUser->objUser->getStaticPermissionIds()) || $isAdmin) {
            $strNavigation .= '<a href="index.php?cmd=Blog&amp;act=settings"
                    class="'.(in_array($_GET['act'], array('settings'))? 'active' : '').'">'
                        .$_CORELANG['TXT_BLOG_SETTINGS_TITLE'].'</a>';
        }

        $objTemplate->setVariable('CONTENT_NAVIGATION', $strNavigation);
    }


    /**
    * Perform the right operation depending on the $_GET-params
    *
    * @global   \Cx\Core\Html\Sigma
    */
    function getPage() {
        global $objTemplate;

        if(!isset($_GET['act'])) {
            $_GET['act']='';
        }

        switch($_GET['act']){
            case 'addEntry':
                \Permission::checkAccess(121, 'static');
                $this->addEntry();
            break;
            case 'insertEntry':
                \Permission::checkAccess(121, 'static');
                $this->insertEntry();
                $this->showEntries();
                break;
            case 'deleteEntry':
                \Permission::checkAccess(120, 'static');
                $this->deleteEntry($_GET['id']);
                $this->showEntries();
                break;
            case 'editEntry':
                //\Permission::checkAccess(120, 'static');
                $this->editEntry($_GET['id']);
                break;
            case 'updateEntry':
                \Permission::checkAccess(120, 'static');
                $this->updateEntry();
                $this->showEntries();
                break;
            case 'multiactionEntry':
                \Permission::checkAccess(120, 'static');
                $this->doEntryMultiAction($_POST['frmShowEntries_MultiAction']);
                $this->showEntries();
                break;
            case 'showVoting':
                \Permission::checkAccess(120, 'static');
                $this->showVoting($_GET['id'],'stats');
                break;
            case 'deleteVoting':
                \Permission::checkAccess(120, 'static');
                $intEntryId = $this->deleteVoting($_GET['id']);
                $this->showVoting($intEntryId,'details');
                break;
            case 'multiactionVoting':
                \Permission::checkAccess(120, 'static');
                $intEntryId = $this->doVotingMultiAction($_POST['frmShowDetails_MultiAction']);
                $this->showVoting($intEntryId,'details');
                break;
            case 'showComments':
                \Permission::checkAccess(120, 'static');
                $this->showComments($_GET['id']);
                break;
            case 'editComment':
                \Permission::checkAccess(120, 'static');
                $this->editComment($_GET['id']);
                break;
            case 'updateComment':
                \Permission::checkAccess(120, 'static');
                $intEntryId = $this->updateCommement();
                $this->showComments($intEntryId);
                break;
            case 'commentStatus':
                \Permission::checkAccess(120, 'static');
                $intEntryId = $this->invertCommentStatus($_GET['id']);
                $this->showComments($intEntryId);
                break;
            case 'deleteComment':
                \Permission::checkAccess(120, 'static');
                $intEntryId = $this->deleteComment($_GET['id']);
                $this->showComments($intEntryId);
                break;
            case 'multiactionComment':
                \Permission::checkAccess(120, 'static');
                $intEntryId = $this->doCommentMultiAction($_POST['frmShowComments_MultiAction']);
                $this->showComments($intEntryId);
                break;
            case 'manageCategory':
                \Permission::checkAccess(122, 'static');
                $this->showCategories();
                break;
            case 'insertCategory':
                \Permission::checkAccess(123, 'static');
                $this->insertCategory();
                $this->showCategories();
                break;
            case 'editCategory':
                \Permission::checkAccess(122, 'static');
                $this->editCategory($_GET['id']);
                break;
            case 'updateCategory':
                \Permission::checkAccess(122, 'static');
                $this->updateCategory();
                $this->showCategories();
                break;
            case 'deleteCategory':
                \Permission::checkAccess(122, 'static');
                $this->deleteCategory($_GET['id']);
                $this->showCategories();
                break;
            case 'multiactionCategory':
                \Permission::checkAccess(122, 'static');
                $this->doCategoryMultiAction($_POST['frmShowCategories_MultiAction']);
                $this->showCategories();
                break;
            case 'networks':
                \Permission::checkAccess(125, 'static');
                $this->showNetworks();
                break;
            case 'insertNetwork':
                \Permission::checkAccess(125, 'static');
                $this->insertNetwork();
                $this->showNetworks();
                break;
            case 'editNetwork':
                \Permission::checkAccess(125, 'static');
                $this->editNetwork($_GET['id']);
                break;
            case 'updateNetwork':
                \Permission::checkAccess(125, 'static');
                $this->updateNetwork();
                $this->showNetworks();
                break;
            case 'deleteNetwork';
                \Permission::checkAccess(125, 'static');
                $this->deleteNetwork($_GET['id']);
                $this->showNetworks();
                break;
            case 'multiactionNetwork':
                \Permission::checkAccess(125, 'static');
                $this->doNetworkMultiAction($_POST['frmShowNetworks_MultiAction']);
                $this->showNetworks();
                break;
            case 'settings':
                $this->showSettings();
                break;
            default:
                //\Permission::checkAccess(120, 'static');
                $this->showEntries();

        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->_strPageTitle,
            'CONTENT_OK_MESSAGE'        => $this->_strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->_strErrMessage,
            'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));
    }


    /**
     * Shows the categories-page of the blog-module.
     *
     * @global  array
     * @global  array
     */
    function showCategories() {
        global $_CORELANG, $_ARRAYLANG;

        $this->_strPageTitle = $_CORELANG['TXT_BLOG_CATEGORY_MANAGE_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_categories.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_OVERVIEW_TITLE'                =>  $_CORELANG['TXT_BLOG_CATEGORY_MANAGE_TITLE'],
            'TXT_OVERVIEW_SUBTITLE_NAME'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'],
            'TXT_OVERVIEW_SUBTITLE_ACTIVE'      =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIVE_LANGUAGES'],
            'TXT_OVERVIEW_SUBTITLE_ACTIONS'     =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'],
            'TXT_OVERVIEW_DELETE_CATEGORY_JS'   =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_JS'],
            'TXT_OVERVIEW_MARKED'               =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_OVERVIEW_SELECT_ALL'           =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_OVERVIEW_DESELECT_ALL'         =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_OVERVIEW_SUBMIT_SELECT'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_OVERVIEW_SUBMIT_DELETE'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'],
            'TXT_OVERVIEW_SUBMIT_DELETE_JS'     =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE_JS'],
            'TXT_ADD_TITLE'                     =>  $_CORELANG['TXT_BLOG_CATEGORY_ADD_TITLE'],
            'TXT_ADD_NAME'                      =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'],
            'TXT_ADD_EXTENDED'                  =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_EXTENDED'],
            'TXT_ADD_LANGUAGES'                 =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_ADD_SUBMIT'                    =>  $_ARRAYLANG['TXT_BLOG_SAVE']
        ));

        $intPagingPosition = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;

        //Show Categories
        $arrCategories = $this->createCategoryArray($intPagingPosition, $this->getPagingLimit());

        if (count($arrCategories) > 0) {
            $intRowClass = 1;

            foreach ($arrCategories as $intCategoryId => $arrLanguages) {

                $this->_objTpl->setVariable(array(
                    'TXT_OVERVIEW_IMGALT_MESSAGES'      =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ASSIGNED_MESSAGES'],
                    'TXT_OVERVIEW_IMGALT_EDIT'          =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_TITLE'],
                    'TXT_OVERVIEW_IMGALT_DELETE'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_TITLE']
                ));

                $strActivatedLanguages = '';
                foreach($arrLanguages as $intLanguageId => $arrValues) {
                    if ($arrValues['is_active'] == 1 && array_key_exists($intLanguageId,$this->_arrLanguages)) {
                        $strActivatedLanguages .= $this->_arrLanguages[$intLanguageId]['long'].' ['.$this->_arrLanguages[$intLanguageId]['short'].'], ';
                    }
                }
                $strActivatedLanguages = substr($strActivatedLanguages,0,-2);

                $this->_objTpl->setVariable(array(
                    'OVERVIEW_CATEGORY_ROWCLASS'    =>  ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                    'OVERVIEW_CATEGORY_ID'          =>  $intCategoryId,
                    'OVERVIEW_CATEGORY_NAME'        =>  $arrLanguages[$this->_intLanguageId]['name'],
                    'OVERVIEW_CATEGORY_LANGUAGES'   =>  $strActivatedLanguages
                ));

                $this->_objTpl->parse('showCategories');
                $intRowClass++;
            }

            //Show paging if needed
            if ($this->countCategories() > $this->getPagingLimit()) {
                $strPaging = getPaging($this->countCategories(), $intPagingPosition, '&cmd=Blog&act=manageCategory', '<strong>'.$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES'].'</strong>', true, $this->getPagingLimit());
                $this->_objTpl->setVariable('OVERVIEW_PAGING', $strPaging);
            }
        } else {
            $this->_objTpl->setVariable('TXT_OVERVIEW_NO_CATEGORIES_FOUND',$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_NO_CATEGORIES']);
            $this->_objTpl->parse('noCategories');
        }

        //Show Add-Category Form
        if (count($this->_arrLanguages) > 0) {
            $intCounter = 0;
            $arrLanguages = array(0 => '', 1 => '', 2 => '');

            foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                $arrLanguages[$intCounter%3] .= '<input checked="checked" type="checkbox" name="frmAddCategory_Languages[]" value="'.$intLanguageId.'" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';

                $this->_objTpl->setVariable(array(
                    'ADD_NAME_LANGID'   =>  $intLanguageId,
                    'ADD_NAME_LANG'     =>  $arrTranslations['long'].' ['.$arrTranslations['short'].']'
                ));

                $this->_objTpl->parse('addCategoryNameFields');

                ++$intCounter;
            }

            $this->_objTpl->setVariable(array(
                'ADD_LANGUAGES_1'   =>  $arrLanguages[0],
                'ADD_LANGUAGES_2'   =>  $arrLanguages[1],
                'ADD_LANGUAGES_3'   =>  $arrLanguages[2]
            ));
        }
    }


    /**
     * Adds a new category to the database. Collected data in POST is checked for valid values.
     *
     * @global  ADONewConnection
     * @global  array
     */
    function insertCategory() {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_POST['frmAddCategory_Languages']) && is_array($_POST['frmAddCategory_Languages'])) {
            //Get next category-id
            $objResult = $objDatabase->Execute('SELECT      MAX(category_id) AS currentId
                                                FROM        '.DBPREFIX.'module_blog_categories
                                                ORDER BY    category_id DESC
                                            ');
            $intNextCategoryId = ($objResult->RecordCount() == 1) ? $objResult->fields['currentId'] + 1 : 1;

            //Collect data
            $arrValues = array();
            foreach ($_POST as $strKey => $strValue) {
                if (substr($strKey,0,strlen('frmAddCategory_Name_')) == 'frmAddCategory_Name_') {
                    $intLanguageId = intval(substr($strKey,strlen('frmAddCategory_Name_')));
                    $arrValues[$intLanguageId] = array( 'name'      => contrexx_addslashes(strip_tags($strValue)),
                                                        'is_active' => intval(in_array($intLanguageId,$_POST['frmAddCategory_Languages']))
                                                    );
                }
            }

            foreach ($arrValues as $intLanguageId => $arrCategoryValues) {
                $objDatabase->Execute(' INSERT INTO `'.DBPREFIX.'module_blog_categories`
                                        SET `category_id` = '.$intNextCategoryId.',
                                            `lang_id` = '.$intLanguageId.',
                                            `is_active` = "'.$arrCategoryValues['is_active'].'",
                                            `name` = "'.$arrCategoryValues['name'].'"
                                    ');
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_ERROR_ACTIVE'];
        }
    }


    /**
     * Removes a category from the database.
     *
     * @param   integer     $intCategoryId: This category will be deleted by the function.
     * @global  array
     * @global  ADONewConnection
     */
    function deleteCategory($intCategoryId) {
        global $_ARRAYLANG, $objDatabase;

        $intCategoryId = intval($intCategoryId);

        if ($intCategoryId > 0) {
            $objDatabase->Execute(' DELETE
                                    FROM '.DBPREFIX.'module_blog_categories
                                    WHERE `category_id` = '.$intCategoryId.'
                                ');

            if (!$this->_boolInnoDb) {
                $objDatabase->Execute(' DELETE
                                        FROM '.DBPREFIX.'module_blog_message_to_category
                                        WHERE `category_id` = '.$intCategoryId.'
                                    ');
            }

            $this->writeMessageRSS();
            $this->writeCategoryRSS();

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_ERROR'];
        }
    }


    /**
     * Performs the action for the dropdown-selection on the category page. The behaviour depends on the parameter.
     *
     * @param   string      $strAction: the action passed by the formular.
     */
    function doCategoryMultiAction($strAction='') {
        switch ($strAction) {
            case 'delete':
                foreach($_POST['selectedCategoryId'] as $intCategoryId) {
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
     * @global  array
     * @global  array
     * @global  ADONewConnection
     * @param   integer     $intCategoryId: The category with this id will be loaded into the form.
     */
    function editCategory($intCategoryId) {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $this->_strPageTitle = $_CORELANG['TXT_BLOG_CATEGORY_MANAGE_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_categories_edit.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_TITLE'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_TITLE'],
            'TXT_EDIT_NAME'         =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'],
            'TXT_EDIT_EXTENDED'     =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_EXTENDED'],
            'TXT_EDIT_LANGUAGES'    =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_EDIT_SUBMIT'       =>  $_ARRAYLANG['TXT_BLOG_SAVE']
        ));

        $intCategoryId = intval($intCategoryId);
        $arrCategories = $this->createCategoryArray();

        if (array_key_exists($intCategoryId,$arrCategories)) {

            $intCounter = 0;
            $arrLanguages = array(0 => '', 1 => '', 2 => '');

            foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                $arrLanguages[$intCounter%3] .= '<input '.(($arrCategories[$intCategoryId][$intLanguageId]['is_active'] == 1) ? 'checked="checked"' : '').' type="checkbox" name="frmEditCategory_Languages[]" value="'.$intLanguageId.'" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';

                $this->_objTpl->setVariable(array(
                    'EDIT_NAME_LANGID'  =>  $intLanguageId,
                    'EDIT_NAME_LANG'    =>  $arrTranslations['long'].' ['.$arrTranslations['short'].']',
                    'EDIT_NAME_VALUE'   =>  $arrCategories[$intCategoryId][$intLanguageId]['name']
                ));

                $this->_objTpl->parse('editCategoryNameFields');

                ++$intCounter;
            }

            $this->_objTpl->setVariable(array(
                'EDIT_CATEGORY_ID'  =>  $intCategoryId,
                'EDIT_NAME'         =>  $arrCategories[$intCategoryId][$this->_intLanguageId]['name'],
                'EDIT_LANGUAGES_1'  =>  $arrLanguages[0],
                'EDIT_LANGUAGES_2'  =>  $arrLanguages[1],
                'EDIT_LANGUAGES_3'  =>  $arrLanguages[2]
            ));
        } else {
            //Wrong category-id
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_ERROR_ID'];
        }
    }


    /**
     * Updates an existing category.
     *
     * @global  array
     * @global  ADONewConnection
     */
    function updateCategory() {
        global $_ARRAYLANG, $objDatabase;

        if (isset($_POST['frmEditCategory_Languages']) && is_array($_POST['frmEditCategory_Languages'])) {
            $intCategoryId = intval($_POST['frmEditCategory_Id']);

            //Collect active-languages
            foreach ($_POST['frmEditCategory_Languages'] as $intLanguageId) {
                $arrActiveLanguages[$intLanguageId] = true;
            }

            //Collect names & check for existing database-entry
            foreach ($_POST as $strKey => $strValue) {
                if (substr($strKey,0,strlen('frmEditCategory_Name_')) == 'frmEditCategory_Name_') {
                    $intLanguageId = substr($strKey,strlen('frmEditCategory_Name_'));

                    $objResult = $objDatabase->Execute('SELECT name
                                                        FROM    '.DBPREFIX.'module_blog_categories
                                                        WHERE   `category_id` = '.$intCategoryId.' AND
                                                                `lang_id` = '.$intLanguageId.'
                                                        LIMIT   1
                                                    ');

                    if ($objResult->RecordCount() == 0) {
                        //We have to create a new entry first
                        $objDatabase->Execute(' INSERT
                                                INTO    `'.DBPREFIX.'module_blog_categories`
                                                SET     `category_id` = '.$intCategoryId.',
                                                        `lang_id` = '.$intLanguageId.',
                                                        `is_active` = "'.(array_key_exists($intLanguageId,$arrActiveLanguages) ? '1' : '0').'",
                                                        `name` = "'.contrexx_addslashes(strip_tags($strValue)).'"
                                            ');
                    } else {
                        //We can update the existing entry
                        $objDatabase->Execute(' UPDATE  `'.DBPREFIX.'module_blog_categories`
                                                SET     `is_active` = "'.(array_key_exists($intLanguageId,$arrActiveLanguages) ? '1' : '0').'",
                                                        `name` = "'.contrexx_addslashes(strip_tags($strValue)).'"
                                                WHERE   `category_id` = '.$intCategoryId.' AND
                                                        `lang_id` = '.$intLanguageId.'
                                                LIMIT   1
                                            ');
                    }

                }
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_ERROR_ACTIVE'];
        }
    }



    /**
     * Shows an overview of all entries.
     *
     * @global  array
     * @global  array
     */
    function showEntries() {
        global $_CORELANG, $_ARRAYLANG;

        $this->_strPageTitle = $_CORELANG['TXT_BLOG_ENTRY_MANAGE_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_entries.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_ENTRIES_TITLE'                 =>  $_CORELANG['TXT_BLOG_ENTRY_MANAGE_TITLE'],
            'TXT_ENTRIES_SUBTITLE_DATE'         =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_DATE'],
            'TXT_ENTRIES_SUBTITLE_SUBJECT'      =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'],
            'TXT_ENTRIES_SUBTITLE_LANGUAGES'    =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_ENTRIES_SUBTITLE_HITS'         =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_HITS'],
            'TXT_ENTRIES_SUBTITLE_COMMENTS'     =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'],
            'TXT_ENTRIES_SUBTITLE_VOTES'        =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'],
            'TXT_ENTRIES_SUBTITLE_USER'         =>  $_CORELANG['TXT_USER'],
            'TXT_ENTRIES_SUBTITLE_EDITED'       =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_UPDATED'],
            'TXT_ENTRIES_SUBTITLE_ACTIONS'      =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'],
            'TXT_ENTRIES_DELETE_ENTRY_JS'       =>  $_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_JS'],
            'TXT_ENTRIES_MARKED'                =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_ENTRIES_SELECT_ALL'            =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_ENTRIES_DESELECT_ALL'          =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_ENTRIES_SUBMIT_SELECT'         =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_ENTRIES_SUBMIT_DELETE'         =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'],
            'TXT_ENTRIES_SUBMIT_DELETE_JS'      =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_SUBMIT_DELETE_JS']
        ));

        $intSelectedCategory = (isset($_GET['catId'])) ? intval($_GET['catId']) : 0;
        $intPagingPosition = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;

        $objFWUser = \FWUser::getFWUserObject();
        $this->_intCurrentUserId = $objFWUser->objUser->getId();

        $arrEntries = $this->createEntryArray(0, $intPagingPosition, $this->getPagingLimit());



        if (count($arrEntries) > 0) {
            $intRowClass = 1;

            foreach ($arrEntries as $intEntryId => $arrEntryValues) {

                if ($intSelectedCategory > 0) {
                    //Filter for a specific category. If the category doesn't match: skip.
                    if (!$this->categoryMatches($intSelectedCategory, $arrEntryValues['categories'][$this->_intLanguageId])) {
                        continue;
                    }
                }

                $this->_objTpl->setVariable(array(
                    'TXT_IMGALT_EDIT'       =>  $_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_TITLE'],
                    'TXT_IMGALT_DELETE'     =>  $_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_TITLE']
                ));

                //Check active languages
                $strActiveLanguages = '';
                if (count(\FWLanguage::getActiveFrontendLanguages()) > 1) {
                    $langState = array();
                    foreach ($arrEntryValues['translation'] as $intLangId => $arrEntryTranslations) {
                        if ($arrEntryTranslations['is_active'] && key_exists($intLangId,$this->_arrLanguages)) {
                            $langState[$intLangId] = 'active';
                        }
                    }
                    $strActiveLanguages = \Html::getLanguageIcons($langState, 'index.php?cmd=Blog&amp;act=editEntry&amp;id=' . $intEntryId . '&amp;langId=%1$d');
                    $this->_objTpl->touchBlock('txt_languages_block');
                } else {
                    $this->_objTpl->hideBlock('txt_languages_block');
                }

                $this->_objTpl->setVariable(array(
                    'ENTRY_ROWCLASS'        =>  ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                    'ENTRY_ID'              =>  $intEntryId,
                    'ENTRY_DATE'            =>  $arrEntryValues['time_created'],
                    'ENTRY_EDITED'          =>  $arrEntryValues['time_edited'],
                    'ENTRY_SUBJECT'         =>  $arrEntryValues['subject'],
                    'ENTRY_LANGUAGES'       =>  $strActiveLanguages,
                    'ENTRY_HITS'            =>  $arrEntryValues['hits'],
                    'ENTRY_COMMENTS'        =>  $arrEntryValues['comments'].'&nbsp;'.$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'],
                    'ENTRY_VOTES'           =>  '&#216;&nbsp;'.$arrEntryValues['votes_avg'].'&nbsp;/&nbsp;'.$arrEntryValues['votes'].' '.$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTES'],
                    'ENTRY_USER'            =>  $arrEntryValues['user_name']
                ));

                $this->_objTpl->parse('showEntries');

                $intRowClass++;
            }

            //Show paging if needed
            if ($this->countEntries() > $this->getPagingLimit()) {
                $strPaging = getPaging($this->countEntries(), $intPagingPosition, '&cmd=Blog', '<strong>'.$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_PAGING'].'</strong>', true, $this->getPagingLimit());
                $this->_objTpl->setVariable('ENTRIES_PAGING', $strPaging);
            }
        } else {
            $this->_objTpl->setVariable('TXT_ENTRIES_NO_ENTRIES_FOUND', $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_NO_ENTRIES']);
            $this->_objTpl->parse('noEntries');
        }
    }



    /**
     * Shows the "Add Entry" page.
     *
     * @global  array
     * @global  array
     * @global  array
     * @global  FWLanguage
     */
    function addEntry()
    {
        global $_CORELANG, $_ARRAYLANG;

        $this->_strPageTitle = $_CORELANG['TXT_BLOG_ENTRY_ADD_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_entries_edit.html',true,true);

        $options = array(
            'type'                      => 'button',
            'views'                     => 'filebrowser',
            'startmediatype'            => 'blog',
            'id'                        => 'mediabrowser_button',
            'style'                     => 'display:none'
        );
        $mediaBrowser = self::getMediaBrowserButton($_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'], $options, 'blogSetUrl');

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_LANGUAGES'    =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_EDIT_SUBMIT'       =>  $_ARRAYLANG['TXT_BLOG_SAVE'],
            'BLOG_MEDIABROWSER_BUTTON' => $mediaBrowser,
        ));

        $arrCategories = $this->createCategoryArray();

        //Show language-selection
        if (count($this->_arrLanguages) > 0) {
            $intLanguageCounter = 0;
            $arrLanguages = array(0 => '', 1 => '', 2 => '');
            $strJsTabToDiv = '';

            foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {

                $arrLanguages[$intLanguageCounter%3] .= '<input checked="checked" type="checkbox" name="frmEditEntry_Languages[]" value="'.$intLanguageId.'" onclick="switchBoxAndTab(this, \'addEntry_'.$arrTranslations['long'].'\');" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';

                $strJsTabToDiv .= 'arrTabToDiv["addEntry_'.$arrTranslations['long'].'"] = "'.$arrTranslations['long'].'";'."\n";

                //Parse the TABS at the top of the language-selection
                $this->_objTpl->setVariable(array(
                    'TABS_LINK_ID'          =>  'addEntry_'.$arrTranslations['long'],
                    'TABS_DIV_ID'           =>  $arrTranslations['long'],
                    'TABS_CLASS'            =>  ($intLanguageCounter == 0) ? 'active' : 'inactive',
                    'TABS_DISPLAY_STYLE'    =>  'display: inline;',
                    'TABS_NAME'             =>  $arrTranslations['long']

                ));
                $this->_objTpl->parse('showLanguageTabs');

                //Parse the DIVS for every language
                $this->_objTpl->setVariable(array(
                    'TXT_DIV_SUBJECT'       =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'],
                    'TXT_DIV_KEYWORDS'      =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'],
                    'TXT_DIV_IMAGE'         =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE'],
                    'TXT_DIV_IMAGE_BROWSE'  =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'],
                    'TXT_DIV_CATEGORIES'    =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES']
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

                $this->_objTpl->setVariable(array(
                    'DIV_ID'            =>  $arrTranslations['long'],
                    'DIV_LANGUAGE_ID'   =>  $intLanguageId,
                    'DIV_DISPLAY_STYLE' =>  ($intLanguageCounter == 0) ? 'display: block;' : 'display: none;',
                    'DIV_TITLE'         =>  $arrTranslations['long'],
                    'DIV_CATEGORIES_1'  =>  $arrCategoriesContent[0],
                    'DIV_CATEGORIES_2'  =>  $arrCategoriesContent[1],
                    'DIV_CATEGORIES_3'  =>  $arrCategoriesContent[2],
                    'DIV_CONTENT'       =>  new \Cx\Core\Wysiwyg\Wysiwyg('frmEditEntry_Content_'.$intLanguageId, null, 'full', $intLanguageId),
                ));
                $this->_objTpl->parse('showLanguageDivs');

                ++$intLanguageCounter;
            }

            $this->_objTpl->setVariable(array(
                'EDIT_POST_ACTION'      =>  '?cmd=Blog&amp;act=insertEntry',
                'EDIT_MESSAGE_ID'       =>  0,
                'EDIT_LANGUAGES_1'      =>  $arrLanguages[0],
                'EDIT_LANGUAGES_2'      =>  $arrLanguages[1],
                'EDIT_LANGUAGES_3'      =>  $arrLanguages[2],
                'EDIT_JS_TAB_TO_DIV'    =>  $strJsTabToDiv
            ));
        }
    }



    /**
     * Adds a new entry to the database. Collected data in POST is checked for valid values.
     *
     * @global  array
     * @global  ADONewConnection
     */
    function insertEntry() {
        global $_ARRAYLANG, $objDatabase;

        if (isset($_POST['frmEditEntry_Languages']) && is_array($_POST['frmEditEntry_Languages'])) {

            //Create entry with general-information for all languages
            $objDatabase->Execute(' INSERT INTO '.DBPREFIX.'module_blog_messages
                                    SET `user_id` = '.$this->_intCurrentUserId.',
                                        `time_created` = '.time().',
                                        `time_edited` = '.time().',
                                        `hits` = 0
                                ');
            $intMessageId = $objDatabase->insert_id();
            $this->insertEntryData($intMessageId);

            $this->writeMessageRSS();
            $this->writeCategoryRSS();

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_ERROR_LANGUAGES'];
        }
    }



    /**
     * This function is used by the "insertEntry()" and "updateEntry()" function. It collects all values from
     * $_POST and creates the new entries in the database. This function was extracted from original source to be as
     * DRY/SPOT as possible.
     *
     * @global  ADONewConnection
     * @param   integer     $intMessageId: This is the id of the message which the new values will be linked to.
     */
    function insertEntryData($intMessageId) {
        global $objDatabase;

        $intMessageId = intval($intMessageId);

        //Collect data for every language
        $arrValues = array();
        foreach (array_keys($_POST) as $strKey) {
            if (substr($strKey,0,strlen('frmEditEntry_Subject_')) == 'frmEditEntry_Subject_') {
                $intLanguageId = intval(substr($strKey,strlen('frmEditEntry_Subject_')));
                $arrValues[$intLanguageId] = array( 'subject'       => contrexx_addslashes(strip_tags($_POST['frmEditEntry_Subject_'.$intLanguageId])),
                                                    'keywords'      => contrexx_addslashes(strip_tags($_POST['frmEditEntry_Keywords_'.$intLanguageId])),
                                                    'content'       => contrexx_addslashes($_POST['frmEditEntry_Content_'.$intLanguageId]),
                                                    'is_active'     => intval(in_array($intLanguageId, $_POST['frmEditEntry_Languages'])),
                                                    'categories'    => (isset($_POST['frmEditEntry_Categories_'.$intLanguageId])) ? $_POST['frmEditEntry_Categories_'.$intLanguageId] : array(),
                                                    'image'         => contrexx_addslashes(strip_tags($_POST['frmEditEntry_Image_'.$intLanguageId]))
                                                );
            }
        }

        //Insert collected data
        foreach ($arrValues as $intLanguageId => $arrEntryValues) {
            $objDatabase->Execute(' INSERT INTO '.DBPREFIX.'module_blog_messages_lang
                                    SET `message_id` = '.$intMessageId.',
                                        `lang_id` = '.$intLanguageId.',
                                        `is_active` = "'.$arrEntryValues['is_active'].'",
                                        `subject` = "'.$arrEntryValues['subject'].'",
                                        `content` = "'.$arrEntryValues['content'].'",
                                        `tags` = "'.$arrEntryValues['keywords'].'",
                                        `image` = "'.$arrEntryValues['image'].'"
                                ');

            //Assign message to categories
            if (is_array($arrEntryValues['categories'])) {
                foreach ($arrEntryValues['categories'] as $intCategoryId) {
                    $objDatabase->Execute(' INSERT INTO '.DBPREFIX.'module_blog_message_to_category
                                            SET `message_id` = '.$intMessageId.',
                                                `category_id` = '.$intCategoryId.',
                                                `lang_id` = '.$intLanguageId.'
                                        ');
                }
            }
        }
    }


    /**
     * Shows the "Edit Entry" page.
     *
     * @global  array
     * @global  array
     * @global  array
     * @global  FWLanguage
     * @param   integer     $intEntryId: The values of this entry will be loaded into the form.
     */
    function editEntry($intEntryId)
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $count = $objDatabase->Execute('SELECT message_id
                                        FROM '.DBPREFIX.'module_blog_messages
                                        WHERE message_id = "'.$intEntryId.'"');
        if($count->RecordCount() != 1) {
            \Permission::noAccess();
        }


        $this->_strPageTitle = $_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_entries_edit.html',true,true);

        $options = array(
            'type'                      => 'button',
            'views'                     => 'filebrowser',
            'startmediatype'            => 'blog',
            'id'                        => 'mediabrowser_button',
            'style'                     => 'display:none'
        );
        $mediaBrowser = self::getMediaBrowserButton($_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'], $options, 'blogSetUrl');

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_LANGUAGES'    =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_EDIT_SUBMIT'       =>  $_ARRAYLANG['TXT_BLOG_SAVE'],
            'BLOG_MEDIABROWSER_BUTTON' => $mediaBrowser,
        ));

        $arrCategories = $this->createCategoryArray();
        $arrEntries = $this->createEntryArray();

        $intEntryId = intval($intEntryId);

        $forcedLanguage = null;
        if (isset($_GET['langId']) && in_array(contrexx_input2raw($_GET['langId']), \FWLanguage::getIdArray())) {
            $forcedLanguage = contrexx_input2raw($_GET['langId']);
        }

        if ($intEntryId > 0 && key_exists($intEntryId,$arrEntries)) {
            if (count($this->_arrLanguages) > 0) {
                $intLanguageCounter = 0;
                $boolFirstLanguage = true;
                $arrLanguages = array(0 => '', 1 => '', 2 => '');
                $strJsTabToDiv = '';

                foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {

                    $boolLanguageIsActive = $arrEntries[$intEntryId]['translation'][$intLanguageId]['is_active'];
                    if (!$boolLanguageIsActive && $forcedLanguage == $intLanguageId) {
                        $boolLanguageIsActive = true;
                    }

                    $arrLanguages[$intLanguageCounter%3] .= '<input '.(($boolLanguageIsActive) ? 'checked="checked"' : '').' type="checkbox" name="frmEditEntry_Languages[]" value="'.$intLanguageId.'" onclick="switchBoxAndTab(this, \'addEntry_'.$arrTranslations['long'].'\');" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';
                    $strJsTabToDiv .= 'arrTabToDiv["addEntry_'.$arrTranslations['long'].'"] = "'.$arrTranslations['long'].'";'."\n";

                    $activeTab = $boolFirstLanguage;
                    if ($forcedLanguage) {
                        $activeTab = $forcedLanguage == $intLanguageId;
                    }

                    //Parse the TABS at the top of the language-selection
                    $this->_objTpl->setVariable(array(
                        'TABS_LINK_ID'          =>  'addEntry_'.$arrTranslations['long'],
                        'TABS_DIV_ID'           =>  $arrTranslations['long'],
                        'TABS_CLASS'            =>  ($activeTab && $boolLanguageIsActive) ? 'active' : 'inactive',
                        'TABS_DISPLAY_STYLE'    =>  ($boolLanguageIsActive) ? 'display: inline;' : 'display: none;',
                        'TABS_NAME'             =>  $arrTranslations['long']

                    ));
                    $this->_objTpl->parse('showLanguageTabs');

                    //Parse the DIVS for every language
                    $this->_objTpl->setVariable(array(
                        'TXT_DIV_SUBJECT'       =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'],
                        'TXT_DIV_KEYWORDS'      =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'],
                        'TXT_DIV_IMAGE'         =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE'],
                        'TXT_DIV_IMAGE_BROWSE'  =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'],
                        'TXT_DIV_CATEGORIES'    =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES']
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

                    $this->_objTpl->setVariable(array(
                        'DIV_ID'            =>  $arrTranslations['long'],
                        'DIV_LANGUAGE_ID'   =>  $intLanguageId,
                        'DIV_DISPLAY_STYLE' =>  ($boolFirstLanguage && $boolLanguageIsActive) ? 'display: block;' : 'display: none;',
                        'DIV_TITLE'         =>  $arrTranslations['long'],
                        'DIV_SUBJECT'       =>  $arrEntries[$intEntryId]['translation'][$intLanguageId]['subject'],
                        'DIV_KEYWORDS'      =>  $arrEntries[$intEntryId]['translation'][$intLanguageId]['tags'],
                        'DIV_IMAGE'         =>  $arrEntries[$intEntryId]['translation'][$intLanguageId]['image'],
                        'DIV_CATEGORIES_1'  =>  $arrCategoriesContent[0],
                        'DIV_CATEGORIES_2'  =>  $arrCategoriesContent[1],
                        'DIV_CATEGORIES_3'  =>  $arrCategoriesContent[2],
                        'DIV_CONTENT'       =>  new \Cx\Core\Wysiwyg\Wysiwyg('frmEditEntry_Content_'.$intLanguageId, $arrEntries[$intEntryId]['translation'][$intLanguageId]['content'], 'full', $intLanguageId),
                    ));

                    $this->_objTpl->parse('showLanguageDivs');

                    if ($boolLanguageIsActive) {
                        $boolFirstLanguage = false;
                    }

                    ++$intLanguageCounter;
                }

                $this->_objTpl->setVariable(array(
                    'EDIT_POST_ACTION'      =>  '?cmd=Blog&amp;act=updateEntry',
                    'EDIT_MESSAGE_ID'       =>  $intEntryId,
                    'EDIT_LANGUAGES_1'      =>  $arrLanguages[0],
                    'EDIT_LANGUAGES_2'      =>  $arrLanguages[1],
                    'EDIT_LANGUAGES_3'      =>  $arrLanguages[2],
                    'EDIT_JS_TAB_TO_DIV'    =>  $strJsTabToDiv
                ));
            }
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'];
        }
    }



    /**
     * Collects and validates all values from the edit-entry-form. Updates values in database.
     *
     * @global  array
     * @global  ADONewConnection
     */
    function updateEntry() {
        global $_ARRAYLANG, $objDatabase;

        $intMessageId = intval($_POST['frmEditCategory_MessageId']);

        if (isset($_POST['frmEditEntry_Languages']) && is_array($_POST['frmEditEntry_Languages']) && $intMessageId > 0) {

            //Update general info
            $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_blog_messages
                                    SET     `user_id` = '.$this->_intCurrentUserId.',
                                            `time_edited` = '.time().'
                                    WHERE   message_id='.$intMessageId.'
                                    LIMIT   1
                                ');


            //Remove existing data for all languages
            $objDatabase->Execute(' DELETE
                                    FROM    '.DBPREFIX.'module_blog_messages_lang
                                    WHERE   message_id='.$intMessageId.'
                                ');

            $objDatabase->Execute(' DELETE
                                    FROM    '.DBPREFIX.'module_blog_message_to_category
                                    WHERE   message_id='.$intMessageId.'
                                ');

            //Now insert new data
            $this->insertEntryData($intMessageId);

            $this->writeMessageRSS();
            $this->writeCategoryRSS();

            $this->_strOkMessage =  $_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_ERROR_LANGUAGES'];
        }
    }



    /**
     * Removes the entry with id = $intEntry from database.
     *
     * @global  array
     * @global  ADONewConnection
     */
    function deleteEntry($intEntryId) {
        global $_ARRAYLANG, $objDatabase;

        $intEntryId = intval($intEntryId);

        if ($intEntryId > 0) {


                $objDatabase->Execute(' DELETE
                                        FROM    '.DBPREFIX.'module_blog_messages
                                        WHERE   message_id='.$intEntryId.'
                                        LIMIT   1
                                    ');

            if (!$this->_boolInnoDb) {
                $objDatabase->Execute(' DELETE
                                        FROM    '.DBPREFIX.'module_blog_messages_lang
                                        WHERE   message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute(' DELETE
                                        FROM    '.DBPREFIX.'module_blog_message_to_category
                                        WHERE   message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute(' DELETE
                                        FROM    '.DBPREFIX.'module_blog_message_to_category
                                        WHERE   message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute(' DELETE
                                        FROM    '.DBPREFIX.'module_blog_votes
                                        WHERE   message_id='.$intEntryId.'
                                    ');

                $objDatabase->Execute(' DELETE
                                        FROM    '.DBPREFIX.'module_blog_comments
                                        WHERE   message_id='.$intEntryId.'
                                    ');
            }

            $this->writeMessageRSS();
            $this->writeCategoryRSS();
            $this->writeCommentRSS();

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_ERROR_ID'];
        }
    }


    /**
     * Performs the action for the dropdown-selection on the entry page. The behaviour depends on the parameter.
     *
     * @param   string      $strAction: the action passed by the formular.
     */
    function doEntryMultiAction($strAction='') {
        switch ($strAction) {
            case 'delete':
                foreach($_POST['selectedEntriesId'] as $intEntryId) {
                    $this->deleteEntry($intEntryId);
                }
                break;
            default:
                //do nothing!
        }
    }


    /**
     * Shows the "votes for entry" page.
     *
     * @global  array
     * @global  array
     * @global  ADONewConnection
     * @param   integer     $intEntryId: The values of this entry will be loaded into the form.
     * @param   string      $strActiveTab: Set's the currently active tab. Valid values are: stats, details.
     */
    function showVoting($intEntryId, $strActiveTab='stats') {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $this->_strPageTitle = $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_entries_voting.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_VOTES_TITLE'               =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_TITLE'],
            'TXT_VOTES_COUNT'               =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_COUNT'],
            'TXT_VOTES_AVG'                 =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_AVG'],
            'TXT_STATISTICS_TITLE'          =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS'],
            'TXT_DETAILS_TITLE'             =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'],
            'TXT_DETAILS_DATE'              =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'],
            'TXT_DETAILS_IP'                =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_IP'],
            'TXT_DETAILS_VOTE'              =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'],
            'TXT_DETAILS_ACTION'            =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'],
            'TXT_DETAILS_DELETE_JS'         =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_JS'],
            'TXT_DETAILS_MARKED'            =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_DETAILS_SELECT_ALL'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_DETAILS_DESELECT_ALL'      =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_DETAILS_SUBMIT_SELECT'     =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_DETAILS_SUBMIT_DELETE'     =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'],
            'TXT_DETAILS_SUBMIT_DELETE_JS'  =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_SUBMIT_DELETE_JS'],
            'TXT_BUTTON_BACK'               =>  ucfirst($_CORELANG['TXT_BACK'])
        ));

        $intPagingPosition = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $strActiveTab = (isset($_GET['pos'])) ? 'details' : $strActiveTab;

        switch ($strActiveTab) {
            case 'details':
                $this->_objTpl->setVariable(array(
                    'TABS_STATS_CLASS'      =>  'inactive',
                    'TABS_DETAILS_CLASS'    =>  'active',
                    'DIVS_STATS_STYLE'      =>  'none',
                    'DIVS_DETAILS_STYLE'    =>  'block',
                ));
                break;
            default: //stats
                $this->_objTpl->setVariable(array(
                    'TABS_STATS_CLASS'      =>  'active',
                    'TABS_DETAILS_CLASS'    =>  'inactive',
                    'DIVS_STATS_STYLE'      =>  'block',
                    'DIVS_DETAILS_STYLE'    =>  'none',
                ));
        }

        $intEntryId = intval($intEntryId);
        $arrEntries = $this->createEntryArray();

        if ($intEntryId > 0 && key_exists($intEntryId,$arrEntries)) {

            $this->_objTpl->setVariable(array(
                'VOTES_SUBJECT'     =>  $arrEntries[$intEntryId]['subject'],
                'VOTES_COUNT'       =>  $arrEntries[$intEntryId]['votes'],
                'VOTES_AVG'         =>  $arrEntries[$intEntryId]['votes_avg']
            ));

            //Collect all votes for this language
            $objVoteResult = $objDatabase->Execute('SELECT      vote_id
                                                    FROM        '.DBPREFIX.'module_blog_votes
                                                    WHERE       message_id='.$intEntryId.'
                                                ');

            if ($objVoteResult->RecordCount() > 0) {
                //Prepare an empty array for statistics
                $arrVotes = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0);
                $intMaxWidth = 400;

                for ($i = 10; $i >= 1; $i--) {
                    $objVoteResult = $objDatabase->Execute('SELECT  COUNT(vote_id) AS countedVotes
                                                            FROM    '.DBPREFIX.'module_blog_votes
                                                            WHERE   message_id='.$intEntryId.' AND
                                                                    vote="'.$i.'"
                                                            LIMIT   1
                                                        ');
                    $arrVotes[$i] = $objVoteResult->fields['countedVotes'];
                    $dblPercentage = $arrVotes[$i] / $arrEntries[$intEntryId]['votes'];

                    $this->_objTpl->setVariable(array(
                        'STATS_ROWCLASS'    =>  ($i % 2 == 0) ? 'row2' : 'row1',
                        'STATS_MARK'        =>  $i,
                        'STATS_GRAPH'       =>  ($intMaxWidth * $dblPercentage > 0) ? ($intMaxWidth * $dblPercentage) : 2,
                        'STATS_PERCENT'     =>  number_format($dblPercentage * 100,2,'.','').'%',
                        'STATS_COUNT'       =>  $arrVotes[$i]
                    ));

                    $this->_objTpl->parse('showStatistics');
                }

                //Now show detailled statistics
                $objVoteResult = $objDatabase->Execute('SELECT      vote_id,
                                                                    vote,
                                                                    time_voted,
                                                                    ip_address
                                                        FROM        '.DBPREFIX.'module_blog_votes
                                                        WHERE       message_id='.$intEntryId.'
                                                        ORDER BY    time_voted DESC, vote_id DESC
                                                        LIMIT       '.$intPagingPosition.','.$this->getPagingLimit().'
                                                    ');

                $intRowClass = 1;

                while (!$objVoteResult->EOF) {
                    $this->_objTpl->setVariable(array(
                        'DETAILS_ROWCLASS'  =>  ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                        'DETAILS_VOTE_ID'   =>  $objVoteResult->fields['vote_id'],
                        'DETAILS_DATE'      =>  date(ASCMS_DATE_FORMAT,$objVoteResult->fields['time_voted']),
                        'DETAILS_IP'        =>  $objVoteResult->fields['ip_address'],
                        'DETAILS_VOTE'      =>  $objVoteResult->fields['vote'],
                    ));

                    $this->_objTpl->parse('showDetails');

                    $objVoteResult->MoveNext();
                    ++$intRowClass;
                }

                //Show paging if needed
                if ($this->countVotings($intEntryId) > $this->getPagingLimit()) {
                    $strPaging = getPaging($this->countVotings($intEntryId), $intPagingPosition, '&amp;cmd=Blog&amp;act=showVoting&amp;id='.$intEntryId, '<strong>'.$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'].'</strong>', true, $this->getPagingLimit());
                    $this->_objTpl->setVariable('DETAILS_PAGING', $strPaging);
                }

            } else {
                $this->_objTpl->setVariable('TXT_STATISTICS_NONE', $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS_NONE']);
                $this->_objTpl->setVariable('TXT_DETAILS_NONE', $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS_NONE']);

                $this->_objTpl->parse('noStatistics');
                $this->_objTpl->parse('noDetails');
            }

        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'];
        }
    }


    /**
     * Removes a voting from database.
     *
     * @global  array
     * @global  ADONewConnection
     * @param   integer     $intVotingId: The vote with this id will be deleted.
     * @return  integer     $intEntryId: the vote was assigned to the message with this id.
     */
    function deleteVoting($intVotingId) {
        global $_ARRAYLANG, $objDatabase;

        $intVotingId = intval($intVotingId);

        $objVoteResult = $objDatabase->Execute('SELECT      message_id
                                                FROM        '.DBPREFIX.'module_blog_votes
                                                WHERE       vote_id='.$intVotingId.'
                                                LIMIT       1
                                            ');

        if ($intVotingId == 0 || $objVoteResult->RecordCount() != 1) {
            return 0;
        }

        $objDatabase->Execute(' DELETE
                                FROM        '.DBPREFIX.'module_blog_votes
                                WHERE       vote_id='.$intVotingId.'
                                LIMIT       1
                            ');

        $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_SUCCESSFULL'];

        return $objVoteResult->fields['message_id'];
    }



    /**
     * Performs the action for the dropdown-selection on the voting page. The behaviour depends on the parameter.
     *
     * @param   string      $strAction: the action passed by the formular.
     * @return  integer     It returns the message_id of the modified votes.
     */
    function doVotingMultiAction($strAction='') {
        $intMessageId = 0;

        switch ($strAction) {
            case 'delete':
                foreach($_POST['selectedVotingsId'] as $intVotingId) {
                    $intMessageId = $this->deleteVoting($intVotingId);
                }
                break;
            default:
                //do nothing!
        }

        return $intMessageId;
    }



    /**
     * Shows all existing comments of the entry with the id $intEntryId.
     *
     * @global  array
     * @global  array
     * @global  ADONewConnection
     * @param   integer     $intEntryId: The comments of this entry will shown.
     */
    function showComments($intEntryId)
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $this->_strPageTitle = $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'];
        $this->_objTpl->loadTemplateFile('module_blog_entries_comments.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_COMMENTS_TITLE'                =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'],
            'TXT_COMMENTS_DATE'                 =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'],
            'TXT_COMMENTS_SUBJECT'              =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'],
            'TXT_COMMENTS_CONTENT'              =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENT'],
            'TXT_COMMENTS_LANGUAGE'             =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_LANGUAGE'],
            'TXT_COMMENTS_USER'                 =>  $_CORELANG['TXT_USER'],
            'TXT_COMMENTS_ACTION'               =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'],
            'TXT_COMMENTS_MARKED'               =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_COMMENTS_SELECT_ALL'           =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_COMMENTS_DESELECT_ALL'         =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_COMMENTS_SUBMIT_SELECT'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_COMMENTS_SUBMIT_ACTIVATE'      =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTIVATE'],
            'TXT_COMMENTS_SUBMIT_DEACTIVATE'    =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DEACTIVATE'],
            'TXT_COMMENTS_SUBMIT_DELETE'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'],
            'TXT_COMMENTS_SUBMIT_DELETE_JS'     =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS_ALL'],
            'TXT_COMMENTS_DELETE_JS'            =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS'],
            'TXT_COMMENTS_BUTTON_BACK'          =>  ucfirst($_CORELANG['TXT_BACK'])
        ));

        $intEntryId = intval($intEntryId);
        $intPagingPosition = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;

        if ($intEntryId > 0) {

// TODO: $arrEntries is not defined
//            @$this->_objTpl->setVariable('COMMENTS_SUBJECT', $arrEntries[$intEntryId]['subject']);

            $objCommentsResult = $objDatabase->Execute('SELECT      comment_id,
                                                                    lang_id,
                                                                    is_active,
                                                                    time_created,
                                                                    user_id,
                                                                    user_name,
                                                                    subject,
                                                                    comment
                                                        FROM        '.DBPREFIX.'module_blog_comments
                                                        WHERE       message_id='.$intEntryId.'
                                                        ORDER BY    time_created DESC, comment_id DESC
                                                        LIMIT       '.$intPagingPosition.','.$this->getPagingLimit().'
                                                    ');

            if ($objCommentsResult->RecordCount() > 0) {
                $objFWUser = \FWUser::getFWUserObject();
                $intRowClass = 1;

                while(!$objCommentsResult->EOF) {

                    $this->_objTpl->setVariable(array(
                        'TXT_IMGALT_STATUS'     =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_STATUS'],
                        'TXT_IMGALT_EDIT'       =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'],
                        'TXT_IMGALT_DELETE'     =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE']
                    ));

                    $strComment = \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput($objCommentsResult->fields['comment']);
                    $strComment = (strlen($strComment) > 60) ? substr($strComment,0,60).' ...' : $strComment;

                    $this->_objTpl->setVariable(array(
                        'COMMENT_ROWCLASS'      =>  ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                        'COMMENT_ID'            =>  $objCommentsResult->fields['comment_id'],
                        'COMMENT_STATUS_ICON'   =>  ($objCommentsResult->fields['is_active'] == 1) ? 'led_green' : 'led_red',
                        'COMMENT_DATE'          =>  date(ASCMS_DATE_FORMAT,$objCommentsResult->fields['time_created']),
                        'COMMENT_SUBJECT'       =>  htmlentities(stripslashes($objCommentsResult->fields['subject']),ENT_QUOTES, CONTREXX_CHARSET),
                        'COMMENT_CONTENT'       =>  stripslashes($strComment),
                        'COMMENT_LANGUAGE'      =>  $this->_arrLanguages[$objCommentsResult->fields['lang_id']]['long'],
                        'COMMENT_USER'          =>  ($objCommentsResult->fields['user_id'] != 0 && ($objUser = $objFWUser->objUser->getUser($objCommentsResult->fields['user_id'])) !== false) ? ('<a href="index.php?cmd=Access&amp;act=user&amp;tpl=modify&amp;id='.$objCommentsResult->fields['user_id'].'" title="'.htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET).'">'.htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET).'</a>') : htmlentities(stripslashes($objCommentsResult->fields['user_name']),ENT_QUOTES, CONTREXX_CHARSET),
                    ));

                    $this->_objTpl->parse('showComments');
                    $objCommentsResult->MoveNext();
                    ++$intRowClass;
                }

                //Show paging if needed
                if ($this->countComments($intEntryId) > $this->getPagingLimit()) {
                    $strPaging = getPaging($this->countComments($intEntryId), $intPagingPosition, '&amp;cmd=Blog&amp;act=showComments&amp;id='.$intEntryId, '<strong>'.$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'].'</strong>', true, $this->getPagingLimit());
                    $this->_objTpl->setVariable('COMMENTS_PAGING', $strPaging);
                }
            } else {
                $this->_objTpl->setVariable('TXT_COMMENTS_NONE',$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_NONE']);
                $this->_objTpl->parse('noComments');
            }
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'];
        }
    }




    /**
     * Removes a comment from database.
     *
     * @global  array
     * @global  ADONewConnection
     * @param   integer     $intCommentId: The comment with this id will be deleted.
     * @return  integer     $intEntryId: the comment was assigned to the message with this id.
     */
    function deleteComment($intCommentId) {
        global $_ARRAYLANG, $objDatabase;

        $intCommentId = intval($intCommentId);

        $objCommentResult = $objDatabase->Execute(' SELECT      message_id
                                                    FROM        '.DBPREFIX.'module_blog_comments
                                                    WHERE       comment_id='.$intCommentId.'
                                                    LIMIT       1
                                                ');

        if ($intCommentId == 0 || $objCommentResult->RecordCount() != 1) {
            return 0;
        }

        $objDatabase->Execute(' DELETE
                                FROM        '.DBPREFIX.'module_blog_comments
                                WHERE       comment_id='.$intCommentId.'
                                LIMIT       1
                            ');

        $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_SUCCESSFULL'];

        $this->writeCommentRSS();

        return $objCommentResult->fields['message_id'];
    }



    /**
     * Inverts the status of the comment with the id $intCommentId.
     *
     * @global  ADONewConnection
     * @param   integer     $intCommentId: the status of this comment will be inverted.
     * @return  integer     The function returns the id of the message which this comment belongs to.
     */
    function invertCommentStatus($intCommentId) {
        global $objDatabase;

        $intCommentId = intval($intCommentId);

        $objCommentResult = $objDatabase->Execute(' SELECT  is_active,
                                                            message_id
                                                    FROM    '.DBPREFIX.'module_blog_comments
                                                    WHERE   comment_id='.$intCommentId.'
                                                    LIMIT   1
                                                ');

        $intNewStatus = ($objCommentResult->fields['is_active'] == 0) ? 1 : 0;
        $intMessageId = intval($objCommentResult->fields['message_id']);

        $objCommentResult = $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_blog_comments
                                                    SET     is_active="'.$intNewStatus.'"
                                                    WHERE   comment_id='.$intCommentId.'
                                                    LIMIT   1
                                                ');

        $this->writeCommentRSS();

        return $intMessageId;
    }



    /**
     * Performs the action for the dropdown-selection on the comment page. The behaviour depends on the parameter.
     *
     * @param   string      $strAction: the action passed by the formular.
     * @return  integer     It returns the message_id of the modified comments.
     */
    function doCommentMultiAction($strAction='')
    {
        $intMessageId = 0;
        foreach($_POST['selectedCommentsId'] as $intCommentId) {
            switch ($strAction) {
                case 'activate':
                case 'deactivate':
                        $intMessageId = $this->invertCommentStatus($intCommentId);
                    break;
                case 'delete':
                        $intMessageId = $this->deleteComment($intCommentId);
                    break;
                default:
                    //do nothing!
            }
        }
        return $intMessageId;
    }


    /**
     * Shows the edit-form for an comment.
     * @global  array
     * @global  array
     * @global  ADONewConnection
     * @param   integer     $intCommentId: the values of this comment will be loaded into the form
     */
    function editComment($intCommentId)
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $this->_strPageTitle = $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'];
        $this->_objTpl->loadTemplateFile('module_blog_entries_comments_edit.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_COMMENT_EDIT_TITLE'        =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'],
            'TXT_COMMENT_EDIT_DATE'         =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'],
            'TXT_COMMENT_EDIT_IP'           =>  $_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_IP'],
            'TXT_COMMENT_EDIT_USER_STATUS'  =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS'],
            'TXT_COMMENT_EDIT_USER_NAME'    =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_NAME'],
            'TXT_COMMENT_EDIT_USER_MAIL'    =>  $_CORELANG['TXT_EMAIL'],
            'TXT_COMMENT_EDIT_USER_WWW'     =>  $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_WWW'],
            'TXT_COMMENT_EDIT_SUBJECT'      =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'],
            'TXT_COMMENT_EDIT_CONTENT'      =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENT'],
            'TXT_COMMENT_EDIT_SUBMIT'       =>  $_ARRAYLANG['TXT_BLOG_SAVE']
        ));

        $intCommentId = intval($intCommentId);

        $objCommentResult = $objDatabase->Execute(' SELECT  is_active,
                                                            time_created,
                                                            ip_address,
                                                            user_id,
                                                            user_name,
                                                            user_mail,
                                                            user_www,
                                                            subject,
                                                            comment
                                                    FROM    '.DBPREFIX.'module_blog_comments
                                                    WHERE   comment_id='.$intCommentId.'
                                                    LIMIT   1
                                                ');

        if ($intCommentId > 0 && $objCommentResult->RecordCount() == 1) {

            if ($objCommentResult->fields['user_id'] == 0) {
                $strUserStatus = $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_UNREGISTERED'];
                $strUserName = '<input type="text" name="frmEditComment_UserName" value="'.htmlentities(stripslashes($objCommentResult->fields['user_name']),ENT_QUOTES, CONTREXX_CHARSET).'" maxlength="50" style="width:30%;" />';
                $strUserMail = '<input type="text" name="frmEditComment_UserMail" value="'.htmlentities(stripslashes($objCommentResult->fields['user_mail']),ENT_QUOTES, CONTREXX_CHARSET).'" maxlength="250" style="width:30%;" />';
                $strUserWWW = '<input type="text" name="frmEditComment_UserWWW" value="'.htmlentities(stripslashes($objCommentResult->fields['user_www']),ENT_QUOTES, CONTREXX_CHARSET).'" maxlength="255" style="width:30%;" />';

                $strUserMailIcon = '';
                if (!empty($objCommentResult->fields['user_mail'])) {
                    $strTempMail = htmlentities(stripslashes($objCommentResult->fields['user_mail']),ENT_QUOTES, CONTREXX_CHARSET);
                    $strUserMailIcon = '<a href="mailto:'.$strTempMail.'" title="'.$strTempMail.'"><img src="../core/Core/View/Media/icons/email.gif" border="0" alt="'.$strTempMail.'" title="'.$strTempMail.'" style="margin-bottom: -3px;" /></a>';
                }

                $strUserWWWIcon = '';
                if (!empty($objCommentResult->fields['user_www'])) {
                    $strTempUrl = htmlentities(stripslashes($objCommentResult->fields['user_www']),ENT_QUOTES, CONTREXX_CHARSET);
                    $strUserWWWIcon = '<a href="'.$strTempUrl.'" target="_blank" title="'.$strTempUrl.'"><img src="../core/Core/View/Media/icons/home.gif" border="0" alt="'.$strTempUrl.'" title="'.$strTempUrl.'" style="margin-bottom: -3px;" /></a>';
                }
            } else {
                $objFWUser = \FWUser::getFWUserObject();

                $strUserStatus = $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_REGISTERED'];
                if ($objUser = $objFWUser->objUser->getUser($objCommentResult->fields['user_id'])) {
                    $strUserName = '<a href="index.php?cmd=Access&amp;act=user&amp;tpl=modify&amp;id='.$objCommentResult->fields['user_id'].'" title="'.htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET).'">'.htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET).'</a>';
                    $strUserMail = htmlentities($objUser->getEmail(), ENT_QUOTES, CONTREXX_CHARSET);
                    $strUserWWW = htmlentities($objUser->getProfileAttribute('website'), ENT_QUOTES, CONTREXX_CHARSET);
                } else {
                    $strUserName = $_ARRAYLANG['TXT_BLOG_ANONYMOUS'];
                    $strUserMail = '';
                    $strUserWWW = '';
                }

                $strUserMailIcon = '';
                if (!empty($strUserMail)) {
                    $strUserMailIcon = '<a href="mailto:'.$strUserMail.'" title="'.$strUserMail.'"><img src="../core/Core/View/Media/icons/email.gif" border="0" alt="'.$strUserMail.'" title="'.$strUserMail.'" style="margin-bottom: -4px;" /></a>';
                }

                $strUserWWWIcon = '';
                if (!empty($strUserWWW)) {
                    $strUserWWWIcon = '<a href="'.$strUserWWW.'" target="_blank" title="'.$strUserWWW.'"><img src="../core/Core/View/Media/icons/home.gif" border="0" alt="'.$strUserWWW.'" title="'.$strUserWWW.'" style="margin-bottom: -4px;" /></a>';
                }
            }

            $this->_objTpl->setVariable(array(
                'COMMENT_EDIT_ID'       =>  $intCommentId,
                'COMMENT_EDIT_DATE'     =>  date(ASCMS_DATE_FORMAT,$objCommentResult->fields['time_created']),
                'COMMENT_EDIT_IP'       =>  $objCommentResult->fields['ip_address'],
                'COMMENT_USER_STATUS'   =>  $strUserStatus,
                'COMMENT_USER_NAME'     =>  $strUserName,
                'COMMENT_USER_MAIL'     =>  $strUserMail.'&nbsp;'.$strUserMailIcon,
                'COMMENT_USER_WWW'      =>  $strUserWWW.'&nbsp;'.$strUserWWWIcon,
                'COMMENT_SUBJECT'       =>  htmlentities(stripslashes($objCommentResult->fields['subject']), ENT_QUOTES, CONTREXX_CHARSET),
                //'COMMENT_CONTENT'     =>  htmlentities(stripslashes($objCommentResult->fields['comment']), ENT_QUOTES, CONTREXX_CHARSET),
                'COMMENT_CONTENT'       =>  new \Cx\Core\Wysiwyg\Wysiwyg('frmEditComment_Content', $objCommentResult->fields['comment'], 'bbcode')
            ));
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_ERROR'];
        }
    }



    /**
     * Validates and saves all values from $_POST into the database.
     *
     * @global  ADONewConnection
     * @global  array
     * @return  integer     The function returns the id of the message which the updated comment belongs to.
     */
    function updateCommement() {
        global $objDatabase, $_ARRAYLANG;

        //Collect values for both modes: registred and unregistered user
        $intCommentId = intval($_POST['frmEditComment_Id']);
        $strSubject = contrexx_addslashes($_POST['frmEditComment_Subject']);
        $strComment = contrexx_addslashes($_POST['frmEditComment_Content']);

        //Check for valid comment-id
        $objCommentResult = $objDatabase->Execute(' SELECT  message_id
                                                    FROM    '.DBPREFIX.'module_blog_comments
                                                    WHERE   comment_id='.$intCommentId.'
                                                    LIMIT   1
                                                ');

        if ($objCommentResult->RecordCount() == 0) {
            //Wrong id, show error and return
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_ERROR'];
            return 0;
        }

        //Update values for both modes
        $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_blog_comments
                                SET     subject="'.$strSubject.'",
                                        comment="'.$strComment.'"
                                WHERE   comment_id='.$intCommentId.'
                                LIMIT   1
                            ');

        //Check for an unregistered user, collect user-data and write them into the database
        if (key_exists('frmEditComment_UserName',$_POST)) {

            //Create validator-object
            $objValidator = new \FWValidator();

            $strUserName = contrexx_addslashes($_POST['frmEditComment_UserName']);
            $strUserMail = contrexx_addslashes($_POST['frmEditComment_UserMail']);
            $strUserWWW = $_POST['frmEditComment_UserWWW'];
            $strUserWWW = $objValidator->getUrl($strUserWWW);
            $strUserWWW = contrexx_addslashes($strUserWWW);

            $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_blog_comments
                                    SET     user_name="'.$strUserName.'",
                                            user_mail="'.$strUserMail.'",
                                            user_www="'.$strUserWWW.'"
                                    WHERE   comment_id='.$intCommentId.'
                                    LIMIT   1
                                ');
        }

        $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_SUCCESSFULL'];

        $this->writeCommentRSS();

        return $objCommentResult->fields['message_id'];
    }



    /**
     * Shows the placeholder-page of the blog-module. Contains all usable Variables for the blog.html-File.
     *
     * @access  private
     * @global  array   $_CORELANG
     * @global  array   $_ARRAYLANG
     */
    private function showSettingsPlaceholders() {
        global $_CORELANG, $_ARRAYLANG;

        $this->_strPageTitle = $_CORELANG['TXT_BLOG_BLOCK_TITLE'];
        $this->_objTpl->addBlockfile('BLOG_SETTINGS_CONTENT', 'settings_content', 'module_blog_settings_placeholders.html');

        if ($this->_arrSettings['blog_block_activated'] == 0) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_BLOCK_ERROR_DEACTIVATED'];
        }

        $this->_objTpl->setVariable(array(
            'TXT_TEXT'                          =>  $_ARRAYLANG['TXT_BLOG_BLOCK_TEXT'],
            'TXT_CONTENT'                       =>  $_ARRAYLANG['TXT_BLOG_BLOCK_CONTENT'],
            'TXT_EXAMPLE'                       =>  $_ARRAYLANG['TXT_BLOG_BLOCK_EXAMPLE'],
            'TXT_GENERAL_TITLE'                 =>  $_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TITLE'],
            'TXT_GENERAL_CALENDAR'              =>  $_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CALENDAR'],
            'TXT_GENERAL_CATEGORIES_SELECT'     =>  $_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_SELECT'],
            'TXT_GENERAL_CATEGORIES_LIST'       =>  $_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_LIST'],
            'TXT_GENERAL_TAGCLOUD'              =>  $_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGCLOUD'],
            'TXT_GENERAL_TAGHITLIST'            =>  $_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGHITLIST'],
            'TXT_ENTRY_TITLE'                   =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_TITLE'],
            'TXT_ENTRY_TEXT_CATEGORIES'         =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES'],
            'TXT_ENTRY_TEXT_TAGS'               =>  $_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'],
            'TXT_ENTRY_TEXT_VOTINGS'            =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'],
            'TXT_ENTRY_TEXT_COMMENTS'           =>  $_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'],
            'TXT_ENTRY_TEXT_LINK'               =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_LINK'],
            'TXT_ENTRY_CONTENT_ROW'             =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROW'],
            'TXT_ENTRY_CONTENT_ROWCLASS'        =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROWCLASS'],
            'TXT_ENTRY_CONTENT_ID'              =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ID'],
            'TXT_ENTRY_CONTENT_DATE'            =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_DATE'],
            'TXT_ENTRY_CONTENT_POSTEDBY'        =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_POSTEDBY'],
            'TXT_ENTRY_CONTENT_SUBJECT'         =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_SUBJECT'],
            'TXT_ENTRY_CONTENT_INTRODUCTION'    =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_INTRODUCTION'],
            'TXT_ENTRY_CONTENT_CONTENT'         =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CONTENT'],
            'TXT_ENTRY_CONTENT_AUTHOR_ID'       =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_ID'],
            'TXT_ENTRY_CONTENT_AUTHOR_NAME'     =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_NAME'],
            'TXT_ENTRY_CONTENT_CATEGORIES'      =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CATEGORIES'],
            'TXT_ENTRY_CONTENT_TAGS'            =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_TAGS'],
            'TXT_ENTRY_CONTENT_COMMENTS'        =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COMMENTS'],
            'TXT_ENTRY_CONTENT_VOTING'          =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_VOTING'],
            'TXT_ENTRY_CONTENT_STARS'           =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_STARS'],
            'TXT_ENTRY_CONTENT_LINK'            =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_LINK'],
            'TXT_ENTRY_CONTENT_IMAGE'           =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_IMAGE'],
            'TXT_CATEGORY_TITLE'                =>  $_ARRAYLANG['TXT_BLOG_BLOCK_CATEGORY_TITLE'],
            'TXT_CATEGORY_CONTENT_ID'           =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ID'],
            'TXT_CATEGORY_CONTENT_NAME'         =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_NAME'],
            'TXT_CATEGORY_CONTENT_COUNT'        =>  $_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COUNT'],
            'TXT_USAGE_TITLE'                   =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE'],
            'TXT_USAGE_HELP'                    =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE_HELP']
        ));
        $this->_objTpl->parse('settings_content');
    }



    /**
     * Shows an overview of all socializing networks which are currently existing in the database. In the second tab
     * an "add network"-form is shown.
     *
     * @global  array
     * @global  array
     */
    function showNetworks() {
        global $_CORELANG, $_ARRAYLANG;

        $this->_strPageTitle = $_CORELANG['TXT_BLOG_NETWORKS_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_networks.html',true,true);

        //Show existing networks
        $this->_objTpl->setVariable(array(
            'TXT_OVERVIEW_TITLE'                =>  $_CORELANG['TXT_BLOG_NETWORKS_TITLE'],
            'TXT_OVERVIEW_SUBTITLE_NAME'        =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'],
            'TXT_OVERVIEW_SUBTITLE_URL'         =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'],
            'TXT_OVERVIEW_SUBTITLE_LANGUAGES'   =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_OVERVIEW_SUBTITLE_ACTIONS'     =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'],
            'TXT_OVERVIEW_DELETE_NETWORK_JS'    =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_JS'],
            'TXT_OVERVIEW_MARKED'               =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_OVERVIEW_SELECT_ALL'           =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_OVERVIEW_DESELECT_ALL'         =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_OVERVIEW_SUBMIT_SELECT'        =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_OVERVIEW_SUBMIT_DELETE'        =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE'],
            'TXT_OVERVIEW_SUBMIT_DELETE_JS'     =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE_JS']
        ));

        $intPagingPosition = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $arrNetworks = $this->createNetworkArray($intPagingPosition, $this->getPagingLimit());

        if (count($arrNetworks) > 0) {
            $intEntryCounter = 0;

            foreach ($arrNetworks as $intNetworkId => $arrNetworkValues) {
                $this->_objTpl->setVariable(array(
                    'TXT_OVERVIEW_IMGALT_EDIT'          =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'],
                    'TXT_OVERVIEW_IMGALT_DELETE'        =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_TITLE']
                ));

                $strActivatedLanguages = '';
                foreach($arrNetworkValues['status'] as $intLanguageId => $intStatus) {
                    if ($intStatus == 1 && array_key_exists($intLanguageId,$this->_arrLanguages)) {
                        $strActivatedLanguages .= $this->_arrLanguages[$intLanguageId]['long'].' ['.$this->_arrLanguages[$intLanguageId]['short'].'], ';
                    }
                }
                $strActivatedLanguages = substr($strActivatedLanguages,0,-2);

                $this->_objTpl->setVariable(array(
                    'OVERVIEW_NETWORK_ROWCLASS'         =>  ($intEntryCounter % 2 == 1) ? 'row1' : 'row2',
                    'OVERVIEW_NETWORK_ID'               =>  $intNetworkId,
                    'OVERVIEW_NETWORK_ICON'             =>  '<img src="../'.$arrNetworkValues['icon'].'" title="'.$arrNetworkValues['name'].'" alt="'.$arrNetworkValues['name'].'" />',
                    'OVERVIEW_NETWORK_NAME'             =>  $arrNetworkValues['name'],
                    'OVERVIEW_NETWORK_URL'              =>  $arrNetworkValues['submit'],
                    'OVERVIEW_NETWORK_LANGUAGES'        =>  $strActivatedLanguages
                ));

                ++$intEntryCounter;
                $this->_objTpl->parse('showNetworks');
            }

            //Show paging if needed
            if ($this->countNetworks() > $this->getPagingLimit()) {
                $strPaging = getPaging($this->countNetworks(), $intPagingPosition, '&amp;cmd=Blog&amp;act=networks', '<strong>'.$_ARRAYLANG['TXT_BLOG_NETWORKS'].'</strong>', true, $this->getPagingLimit());
                $this->_objTpl->setVariable('OVERVIEW_PAGING', $strPaging);
            }
        } else {
            $this->_objTpl->setVariable('TXT_OVERVIEW_NO_NETWORKS_FOUND', $_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_NONE']);
            $this->_objTpl->parse('noNetworks');
        }

        //Show ADD form
        //Get media browser code.
        $options = array(
                        'type'                      => 'button',
                        'id'                        => 'mediabrowser_button',
                        'views'                     => 'filebrowser',
                        'startmediatype'            => 'blog'
                    );
        $mediaBrowser = self::getMediaBrowserButton($_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_BROWSE'], $options, 'blogSetUrl');

        $this->_objTpl->setVariable(array(
            'TXT_ADD_TITLE'                     =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_TITLE'],
            'TXT_ADD_NAME'                      =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'],
            'TXT_ADD_WWW'                       =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_WWW'],
            'TXT_ADD_SUBMIT_URL'                =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'],
            'TXT_ADD_ICON'                      =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_ICON'],
            'TXT_ADD_LANGUAGES'                 =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_ADD_SUBMIT'                    =>  $_ARRAYLANG['TXT_BLOG_SAVE'],
            'BLOG_MEDIABROWSER_BUTTON'          =>  $mediaBrowser
        ));

        if (count($this->_arrLanguages) > 0) {
            $intCounter = 0;
            $arrLanguages = array(0 => '', 1 => '', 2 => '');

            foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                $arrLanguages[$intCounter%3] .= '<input checked="checked" type="checkbox" name="frmAddNetwork_Languages[]" value="'.$intLanguageId.'" />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';
                ++$intCounter;
            }

            $this->_objTpl->setVariable(array(
                'ADD_LANGUAGES_1'   =>  $arrLanguages[0],
                'ADD_LANGUAGES_2'   =>  $arrLanguages[1],
                'ADD_LANGUAGES_3'   =>  $arrLanguages[2]
            ));
        }
    }



    /**
     * Inserts a new socializing network into to the database. If fields aren't set properly an error message is
     * returned.
     *
     * @global  ADONewConnection
     * @global  array
     *
     */
    function insertNetwork() {
        global $objDatabase, $_ARRAYLANG;

        $strName        = contrexx_addslashes($_POST['frmAddNetwork_Name']);
        $strWWW         = contrexx_addslashes($_POST['frmAddNetwork_WWW']);
        $strSubmitUrl   = contrexx_addslashes($_POST['frmAddNetwork_SubmitUrl']);
        $strIcon        = contrexx_addslashes($_POST['frmAddNetwork_Icon']);
        $arrLanguages   = (!empty($_POST['frmAddNetwork_Languages'])) ? $_POST['frmAddNetwork_Languages'] : array();

        if (!empty($strName) && !empty($strSubmitUrl)) {

            $objValidator = new \FWValidator();

            $strWWW         = $objValidator->getUrl($strWWW);
            $strSubmitUrl   = $objValidator->getUrl($strSubmitUrl);

            $objDatabase->Execute(' INSERT
                                    INTO    '.DBPREFIX.'module_blog_networks
                                    SET     name="'.$strName.'",
                                            url="'.$strWWW.'",
                                            url_link="'.$strSubmitUrl.'",
                                            icon="'.$strIcon.'"
                                ');

            $intNetworkId = $objDatabase->insert_id();

            if (is_array($arrLanguages) && count($arrLanguages) > 0) {
                foreach ($arrLanguages as $intLanguageId) {
                    $objDatabase->Execute(' INSERT
                                            INTO    '.DBPREFIX.'module_blog_networks_lang
                                            SET     network_id='.$intNetworkId.',
                                                    lang_id='.$intLanguageId.'
                                        ');
                }
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_ERROR'];
        }
    }


    /**
     * Shows the edit-page for the network with the id $intNetworkId. If there is no entry with this id an error
     * message will be shown.
     *
     * @global  array
     * @global  array
     */
    function editNetwork($intNetworkId) {
        global $_CORELANG, $_ARRAYLANG;

        $this->_strPageTitle = $_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'];
        $this->_objTpl->loadTemplateFile('module_blog_networks_edit.html',true,true);

        //Get media browser code.
        $options = array(
                        'type'                      => 'button',
                        'id'                        => 'mediabrowser_button',
                        'views'                     => 'filebrowser',
                        'startmediatype'            => 'blog'
                    );
        $mediaBrowser = self::getMediaBrowserButton($_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_BROWSE'], $options, 'blogSetUrl');

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_TITLE'        =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'],
            'TXT_EDIT_NAME'         =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'],
            'TXT_EDIT_WWW'          =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_WWW'],
            'TXT_EDIT_SUBMIT_URL'   =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'],
            'TXT_EDIT_ICON'         =>  $_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_ICON'],
            'TXT_EDIT_LANGUAGES'    =>  $_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'],
            'TXT_EDIT_SUBMIT'       =>  $_ARRAYLANG['TXT_BLOG_SAVE'],
            'BLOG_MEDIABROWSER_BUTTON' => $mediaBrowser,
        ));

        $intNetworkId = intval($intNetworkId);
        $arrNetworks = $this->createNetworkArray();

        if ($intNetworkId > 0 && key_exists($intNetworkId, $arrNetworks)) {
            $this->_objTpl->setVariable(array(
                'EDIT_ID'   =>  $intNetworkId,
                'EDIT_NAME' =>  $arrNetworks[$intNetworkId]['name'],
                'EDIT_WWW'  =>  $arrNetworks[$intNetworkId]['www'],
                'EDIT_URL'  =>  $arrNetworks[$intNetworkId]['submit'],
                'EDIT_ICON' =>  $arrNetworks[$intNetworkId]['icon'],
            ));

            if (count($this->_arrLanguages) > 0) {
                $intCounter = 0;
                $arrLanguages = array(0 => '', 1 => '', 2 => '');

                foreach($this->_arrLanguages as $intLanguageId => $arrTranslations) {
                    $arrLanguages[$intCounter%3] .= '<input type="checkbox" name="frmAddNetwork_Languages[]" value="'.$intLanguageId.'" '.(key_exists($intLanguageId, $arrNetworks[$intNetworkId]['status']) ? 'checked="checked"' : '').' />'.$arrTranslations['long'].' ['.$arrTranslations['short'].']<br />';
                    ++$intCounter;
                }

                $this->_objTpl->setVariable(array(
                    'EDIT_LANGUAGES_1'  =>  $arrLanguages[0],
                    'EDIT_LANGUAGES_2'  =>  $arrLanguages[1],
                    'EDIT_LANGUAGES_3'  =>  $arrLanguages[2]
                ));
            }
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_ERROR'];
        }
    }


    /**
     * Updates the values for an existing network.
     *
     * @global  ADONewConnection
     * @global  array
     */
    function updateNetwork() {
        global $objDatabase, $_ARRAYLANG;

        $intNetworkId   = intval($_POST['frmEditNetwork_Id']);
        $strName        = contrexx_addslashes($_POST['frmEditNetwork_Name']);
        $strWWW         = contrexx_addslashes($_POST['frmEditNetwork_WWW']);
        $strSubmitUrl   = contrexx_addslashes($_POST['frmEditNetwork_SubmitUrl']);
        $strIcon        = contrexx_addslashes($_POST['frmEditNetwork_Icon']);
        $arrLanguages   = $_POST['frmAddNetwork_Languages'];

        if ($intNetworkId > 0 && !empty($strName) && !empty($strSubmitUrl)) {

            $objValidator = new \FWValidator();

            $strWWW         = $objValidator->getUrl($strWWW);
            $strSubmitUrl   = $objValidator->getUrl($strSubmitUrl);

            $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_blog_networks
                                    SET     name="'.$strName.'",
                                            url="'.$strWWW.'",
                                            url_link="'.$strSubmitUrl.'",
                                            icon="'.$strIcon.'"
                                    WHERE   network_id='.$intNetworkId.'
                                    LIMIT   1
                                ');

            $objDatabase->Execute(' DELETE
                                    FROM '.DBPREFIX.'module_blog_networks_lang
                                    WHERE `network_id` = '.$intNetworkId.'
                                ');

            if (is_array($arrLanguages) && count($arrLanguages) > 0) {
                foreach ($arrLanguages as $intLanguageId) {
                    $objDatabase->Execute(' INSERT
                                            INTO    '.DBPREFIX.'module_blog_networks_lang
                                            SET     network_id='.$intNetworkId.',
                                                    lang_id='.$intLanguageId.'
                                        ');
                }
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_ERROR'];
        }
    }



    /**
     * Removes a socializing network from the database.
     *
     * @param   integer     $intNetworkId: This network will be removed.
     * @global  array
     * @global  ADONewConnection
     */
    function deleteNetwork($intNetworkId) {
        global $_ARRAYLANG, $objDatabase;

        $intNetworkId = intval($intNetworkId);

        if ($intNetworkId > 0) {
            $objDatabase->Execute(' DELETE
                                    FROM '.DBPREFIX.'module_blog_networks
                                    WHERE `network_id` = '.$intNetworkId.'
                                ');

            if (!$this->_boolInnoDb) {
                $objDatabase->Execute(' DELETE
                                        FROM '.DBPREFIX.'module_blog_networks_lang
                                        WHERE `network_id` = '.$intNetworkId.'
                                    ');
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_SUCCESSFULL'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_ERROR'];
        }
    }




    /**
     * Performs the action for the dropdown-selection on the network page. The behaviour depends on the parameter.
     *
     * @param   string      $strAction: the action passed by the formular.
     */
    function doNetworkMultiAction($strAction='') {
        switch ($strAction) {
            case 'delete':
                foreach($_POST['selectedNetworksId'] as $intNetworkId) {
                    $this->deleteNetwork($intNetworkId);
                }
                break;
            default:
                //do nothing!
        }
    }



    /**ToDo:
     * Loads subnavbar level 2
     *
     * @access  private
     * @global  array   $_CORELANG
     */
    private function showSettings()
    {
        global $_CORELANG;

        $this->pageTitle = $_CORELANG['TXT_CORE_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_blog_settings.html', true, true);
        $this->_objTpl->setVariable(array(
            'TXT_CORE_GENERAL'      => $_CORELANG['TXT_CORE_GENERAL'],
            'TXT_CORE_PLACEHOLDERS' => $_CORELANG['TXT_CORE_PLACEHOLDERS'],
        ));

        switch (!empty($_GET['tpl']) ? $_GET['tpl'] : '') {
            case 'showGeneral':
                \Permission::checkAccess(124, 'static');
                $this->showSettingsGeneral();
                break;
            case 'saveGeneral':
                \Permission::checkAccess(124, 'static');
                $this->saveSettingsGeneral();
                $this->showSettingsGeneral();
                break;
            case 'showPlaceholders':
                $this->showSettingsPlaceholders();
                break;
            default:
                \Permission::checkAccess(124, 'static');
                $this->showSettingsGeneral();
                break;
        }
    }



    /**
     * Shows the settings-page of the blog-module.
     *
     * @global  array               $_CORELANG
     * @global  array               $_ARRAYLANG
     * @global  ADONewConnection    $objDatabase
     */
    function showSettingsGeneral()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $this->_strPageTitle = $_CORELANG['TXT_BLOG_SETTINGS_TITLE'];
        $this->_objTpl->addBlockfile('BLOG_SETTINGS_CONTENT', 'settings_content', 'module_blog_settings_general.html');

        $this->_objTpl->setVariable(array(
            'TXT_GENERAL_TITLE'                         =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_TITLE'],
            'TXT_GENERAL_INTRODUCTION'                  =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION'],
            'TXT_GENERAL_INTRODUCTION_HELP'             =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION_HELP'],
            'TXT_COMMENTS_TITLE'                        =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TITLE'],
            'TXT_COMMENTS_ALLOW'                        =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW'],
            'TXT_COMMENTS_ALLOW_HELP'                   =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_HELP'],
            'TXT_COMMENTS_ALLOW_ANONYMOUS'              =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'],
            'TXT_COMMENTS_ALLOW_ANONYMOUS_HELP'         =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'],
            'TXT_COMMENTS_AUTO_ACTIVATE'                =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE'],
            'TXT_COMMENTS_AUTO_ACTIVATE_HELP'           =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'],
            'TXT_COMMENTS_NOTIFICATION'                 =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION'],
            'TXT_COMMENTS_NOTIFICATION_HELP'            =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION_HELP'],
            'TXT_COMMENTS_TIMEOUT'                      =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT'],
            'TXT_COMMENTS_TIMEOUT_HELP'                 =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT_HELP'],
            'TXT_COMMENTS_EDITOR'                       =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR'],
            'TXT_COMMENTS_EDITOR_HELP'                  =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_HELP'],
            'TXT_COMMENTS_EDITOR_WYSIWYG'               =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_WYSIWYG'],
            'TXT_COMMENTS_EDITOR_TEXTAREA'              =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_TEXTAREA'],
            'TXT_VOTING_TITLE'                          =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_TITLE'],
            'TXT_VOTING_ALLOW'                          =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW'],
            'TXT_VOTING_ALLOW_HELP'                     =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW_HELP'],
            'TXT_TAG_TITLE'                             =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_TITLE'],
            'TXT_TAG_HITLIST'                           =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST'],
            'TXT_TAG_HITLIST_HELP'                      =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST_HELP'],
            'TXT_RSS_TITLE'                             =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_TITLE'],
            'TXT_RSS_ACTIVATE'                          =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE'],
            'TXT_RSS_ACTIVATE_HELP'                     =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE_HELP'],
            'TXT_RSS_MESSAGES'                          =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES'],
            'TXT_RSS_MESSAGES_HELP'                     =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES_HELP'],
            'TXT_RSS_COMMENTS'                          =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS'],
            'TXT_RSS_COMMENTS_HELP'                     =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS_HELP'],
            'TXT_BLOCK_TITLE'                           =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_TITLE'],
            'TXT_BLOCK_ACTIVATE'                        =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE'],
            'TXT_BLOCK_ACTIVATE_HELP'                   =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE_HELP'],
            'TXT_BLOCK_MESSAGES'                        =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES'],
            'TXT_BLOCK_MESSAGES_HELP'                   =>  $_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES_HELP'],
            'TXT_ACTIVATED'                             =>  $_CORELANG['TXT_ACTIVATED'],
            'TXT_DEACTIVATED'                           =>  $_CORELANG['TXT_DEACTIVATED'],
            'TXT_BUTTON_SAVE'                           =>  $_ARRAYLANG['TXT_BLOG_SAVE']
        ));

        $this->_objTpl->setVariable(array(
            'BLOG_SETTINGS_GENERAL_INTRODUCTION'            =>  intval($this->_arrSettings['blog_general_introduction']),
            'BLOG_SETTINGS_COMMENTS_ALLOW_ON'               =>  ($this->_arrSettings['blog_comments_activated'] == '1') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_ALLOW_OFF'              =>  ($this->_arrSettings['blog_comments_activated'] == '0') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_ON'     =>  ($this->_arrSettings['blog_comments_anonymous'] == '1') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_OFF'    =>  ($this->_arrSettings['blog_comments_anonymous'] == '0') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_ON'       =>  ($this->_arrSettings['blog_comments_autoactivate'] == '1') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_OFF'      =>  ($this->_arrSettings['blog_comments_autoactivate'] == '0') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_NOTIFICATION_ON'        =>  ($this->_arrSettings['blog_comments_notification'] == '1') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_NOTIFICATION_OFF'       =>  ($this->_arrSettings['blog_comments_notification'] == '0') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_TIMEOUT'                =>  intval($this->_arrSettings['blog_comments_timeout']),
            'BLOG_SETTINGS_COMMENTS_EDITOR_WYSIWYG'         =>  ($this->_arrSettings['blog_comments_editor'] == 'wysiwyg') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_COMMENTS_EDITOR_TEXTAREA'        =>  ($this->_arrSettings['blog_comments_editor'] == 'textarea') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_VOTING_ALLOW_ON'                 =>  ($this->_arrSettings['blog_voting_activated'] == '1') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_VOTING_ALLOW_OFF'                =>  ($this->_arrSettings['blog_voting_activated'] == '0') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_TAG_HITLIST'                     =>  intval($this->_arrSettings['blog_tags_hitlist']),
            'BLOG_SETTINGS_RSS_ACTIVATE_ON'                 =>  ($this->_arrSettings['blog_rss_activated'] == '1') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_RSS_ACTIVATE_OFF'                =>  ($this->_arrSettings['blog_rss_activated'] == '0') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_RSS_MESSAGES'                    =>  intval($this->_arrSettings['blog_rss_messages']),
            'BLOG_SETTINGS_RSS_COMMENTS'                    =>  intval($this->_arrSettings['blog_rss_comments']),
            'BLOG_SETTINGS_BLOCK_ACTIVATE_ON'               =>  ($this->_arrSettings['blog_block_activated'] == '1') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_BLOCK_ACTIVATE_OFF'              =>  ($this->_arrSettings['blog_block_activated'] == '0') ? 'checked="checked"' : '',
            'BLOG_SETTINGS_BLOCK_MESSAGES'                  =>  intval($this->_arrSettings['blog_block_messages']),
        ));
        $this->_objTpl->parse('settings_content');
    }



    /**
     * Validate and save the settings from $_POST into the database.
     *
     * @global  ADONewConnection    $objDatabase
     * @global  array               $_ARRAYLANG
     */
    function saveSettingsGeneral()
    {
        global $objDatabase, $_ARRAYLANG;

        //On-Off-Settings can only be 0 or 1.
        $arrOnOffValues = array('frmSettings_CommentsAllow'             =>  'blog_comments_activated',
                                'frmSettings_CommentsAllowAnonymous'    =>  'blog_comments_anonymous',
                                'frmSettings_CommentsAutoActivate'      =>  'blog_comments_autoactivate',
                                'frmSettings_CommentsNotification'      =>  'blog_comments_notification',
                                'frmSettings_VotingAllow'               =>  'blog_voting_activated',
                                'frmSettings_BlockActivated'            =>  'blog_block_activated',
                                'frmSettings_RssActivated'              =>  'blog_rss_activated'
                            );

        //Integer-Settings [0 .. infinite]
        $arrIntegerValues = array(  'frmSettings_CommentsTimeout'           =>  'blog_comments_timeout',
                                    'frmSettings_GeneralIntroduction'       =>  'blog_general_introduction',
                                    'frmSettings_BlockNumberOfMessages'     =>  'blog_block_messages',
                                    'frmSettings_RssNumberOfMessages'       =>  'blog_rss_messages',
                                    'frmSettings_RssNumberOfComments'       =>  'blog_rss_comments',
                                    'frmSettings_TagHitlist'                =>  'blog_tags_hitlist'
                            );

        //Enum-Settings, must be a value of a given list
        $arrEnumValues = array( 'frmSettings_CommentsEditor'    =>  'blog_comments_editor');
        $arrEnumPossibilities = array(  'frmSettings_CommentsEditor'    =>  'wysiwyg,textarea');


        foreach ($_POST as $strKey => $strValue) {
                if (key_exists($strKey, $arrOnOffValues)) {
                    $objDatabase->Execute(' UPDATE '.DBPREFIX.'module_blog_settings
                                            SET `value` = "'.intval($strValue).'"
                                            WHERE `name` = "'.$arrOnOffValues[$strKey].'"
                                        ');
                }

                if (key_exists($strKey, $arrIntegerValues)) {
                    $objDatabase->Execute(' UPDATE '.DBPREFIX.'module_blog_settings
                                            SET `value` = "'.abs(intval($strValue)).'"
                                            WHERE `name` = "'.$arrIntegerValues[$strKey].'"
                                        ');
                }

                if (key_exists($strKey, $arrEnumValues)) {
                    $arrSplit = explode(',', $arrEnumPossibilities[$strKey]);

                    if (in_array($strValue, $arrSplit)) {
                        $objDatabase->Execute(' UPDATE '.DBPREFIX.'module_blog_settings
                                                SET `value` = "'.$strValue.'"
                                                WHERE `name` = "'.$arrEnumValues[$strKey].'"
                                            ');
                    }
                }
        }

        $this->_arrSettings = $this->createSettingsArray();

        $this->_strOkMessage = $_ARRAYLANG['TXT_BLOG_SETTINGS_SAVE_SUCCESSFULL'];
    }
}
