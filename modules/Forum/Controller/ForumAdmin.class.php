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
 * Forum
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_forum
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Forum\Controller;

/**
 * Forum
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_forum
 */
class ForumAdmin extends ForumLibrary {

    var $_objTpl;
    var $_strPageTitle    = '';
    var $_strErrMessage = '';
    var $_strOkMessage     = '';

    private $act = '';

    /**
     * Constructor    -> Create the module-menu and an internal template-object
     * @global    InitCMS
     * @global    \Cx\Core\Html\Sigma
     * @global    array
     */
    function __construct()
    {
        global $objInit;
        parent::__construct();
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Forum/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_intLangId = $objInit->userFrontendLangId;
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable(
            'CONTENT_NAVIGATION',
            '<a href="?cmd=Forum&amp;act=category" class="'.(in_array($this->act, array('category', '')) ? 'active' : '').'">'.$_ARRAYLANG['TXT_FORUM_MENU_CATEGORIES'].'</a>
            <a href="?cmd=Forum&amp;act=settings" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_ARRAYLANG['TXT_FORUM_MENU_SETTINGS'].'</a>
        ');
    }


    /**
    * Perform the right operation depending on the $_GET-params
    *
    * @global     \Cx\Core\Html\Sigma
    */
    function getPage() {
        global $objTemplate;

        if(!isset($_GET['act'])) {
            $_GET['act']='';
        }

        switch($_GET['act']){
            case 'category':
                \Permission::checkAccess(107, 'static');
                $this->showCategoryOverview();
                break;

            case 'category_status':
                    \Permission::checkAccess(107, 'static');
                    $this->setCategoryStatus($_GET['id']);
                    $this->showCategoryOverview();
                break;

            case 'category_sorting':
                    \Permission::checkAccess(107, 'static');
                    $this->saveCategorySorting();
                    $this->showCategoryOverview();
                break;

            case 'category_delete':
                    \Permission::checkAccess(107, 'static');
                    $this->deleteCategory($_GET['id']);
                    $this->showCategoryOverview();
                break;

            case 'category_multiaction':
                    \Permission::checkAccess(107, 'static');
                    $this->doCategoryMultiAction();
                    $this->showCategoryOverview();
                break;

            case 'category_add':
                    \Permission::checkAccess(107, 'static');
                    $this->addCategory();
                    $this->showCategoryOverview();;
                break;

            case 'category_edit':
                    \Permission::checkAccess(107, 'static');
                    $this->editCategory($_GET['id']);
                break;

            case 'category_update':
                    \Permission::checkAccess(107,'static');
                    $this->updateCategory();
                    $this->showCategoryOverview();
                break;

            case 'category_access':
                    \Permission::checkAccess(107,'static');
                    $this->editCategoryAccess($_GET['id']);
                break;

            case 'category_access_update':
                    \Permission::checkAccess(107,'static');
                    $this->updateCategoryAccess();
                    $this->showCategoryOverview();
                break;

            case 'settings':
                    \Permission::checkAccess(108, 'static');
                    $this->showSettings();
                break;

            case 'settings_update':
                    \Permission::checkAccess(108, 'static');
                    $this->updateSettings();
                    $this->showSettings();
                break;

            default:
                \Permission::checkAccess(107, 'static');
                $this->showCategoryOverview();
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'                => $this->_strPageTitle,
            'CONTENT_OK_MESSAGE'        => $this->_strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->_strErrMessage,
            'ADMIN_CONTENT'                => $this->_objTpl->get()
        ));

        $this->act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
        $this->setNavigation();
    }


    /**
     * Show an overview of all forums (categories)
     *
     * @global     array
     */
    function showCategoryOverview() {
        global $_ARRAYLANG;

        $this->_strPageTitle = $_ARRAYLANG['TXT_FORUM_MENU_CATEGORIES'];
        $this->_objTpl->loadTemplateFile('module_forum_category_overview.html',true,true);

    //Show categories
        $this->_objTpl->setVariable(array(
            'TXT_TITLE_OVERVIEW'         =>    $_ARRAYLANG['TXT_FORUM_MENU_CATEGORIES'],
            'TXT_SUBTITLE_STATUS'         =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_STATUS'],
            'TXT_SUBTITLE_NAME'         =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_NAME'],
            'TXT_SUBTITLE_DESC'         =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_DESCRIPTION'],
            'TXT_SUBTITLE_LANGUAGES'    =>    count($this->_arrLanguages) > 1 ? $_ARRAYLANG['TXT_FORUM_CATEGORY_LANGUAGES'] : '',
            'TXT_SUBTITLE_POSTINGS'     =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_POSTINGS'],
            'TXT_SUBTITLE_LASTPOST'     =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_LASTPOST'],
            'TXT_SUBTITLE_ACTIONS'         =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACTIONS'],
            'TXT_JS_DELETE_MSG'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_JS_DELETE_MSG'],
            'TXT_JS_DELETE_ALL_MSG'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_JS_DELETE_ALL_MSG'],
            'TXT_BUTTON_SAVESORT'        =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_SELECT_ALL'            =>    $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'            =>    $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_SUBMIT_SELECT'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_MULTIACTION_SELECT'],
            'TXT_SUBMIT_DELETE'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_MULTIACTION_DELETE'],
            'TXT_SUBMIT_ACTIVATE'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_MULTIACTION_ACTIVATE'],
            'TXT_SUBMIT_DEACTIVATE'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_MULTIACTION_DEACTIVATE'],
           ));

           $arrForums = $this->createForumArray();

           if (count($arrForums) > 0) {
               //there are categories in database
               $index = 1;
               foreach ($arrForums as $intCounter => $arrValues) {
                   $this->_objTpl->setVariable(array(
                    'TXT_IMGALT_CHANGE_STATUS'    =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_CHANGE_STATUS'],
                    'TXT_IMGALT_EDIT'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_EDIT'],
                    'TXT_IMGALT_ACCESS'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS'],
                    'TXT_IMGALT_DELETE'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_DELETE']
                ));

                $strLanguages = '';
                    $langState = array();
                    if (is_array($arrValues['languages'])) {
                        foreach ($arrValues['languages'] as $intLangId => $arrTranslations) {
                            $langState[$intLangId] = 'active';
                        }
                    }
                $strLanguages = count($this->_arrLanguages) > 1 ? \Html::getLanguageIcons($langState, 'index.php?cmd=Forum&amp;act=category_edit&amp;id=' . $arrValues['id']) : '';

                $row = $index % 2 ? 'row2' : 'row1';
                $this->_objTpl->setVariable(array(
                       'CATEGORY_ROWCLASS'            => $row,
                       'CATEGORY_ID'                =>    $arrValues['id'],
                       'CATEGORY_STATUS_ICON'        =>    ($arrValues['status'] == 1) ? 'led_green' : 'led_red',
                       'CATEGORY_TYPE_ICON'        =>    ($arrValues['level'] == 0) ? '<img src="'.ASCMS_MODULE_WEB_PATH.'/Forum/View/Media/folder.gif" border="0" alt="'.$arrValues['name'].'" />' : '<img src="'.ASCMS_MODULE_WEB_PATH.'/Forum/View/Media/comment.gif" border="0" alt="'.$arrValues['name'].'" />',
                       'FORUM_TOOLTIP_TEXT'        =>    $strLanguages,
                       'FORUM_LANGUAGES'            =>   $strLanguages,
                       'CATEGORY_SPACER'            =>    '<img src="../core/Core/View/Media/icons/pixel.gif" border="0" width="'.(intval($arrValues['level'])*20).'" height="1" />',
                       'CATEGORY_ORDERID'            =>    $arrValues['order_id'],
                       'CATEGORY_NAME'                =>    $arrValues['name'],
                       'CATEGORY_DESC'                =>    $arrValues['description'],
                       'CATEGORY_LANGUAGES'        =>    $strLanguages,
                       'CATEGORY_POSTCOUNT'        =>    $arrValues['post_count'],
                       'CATEGORY_LASTPOST'            =>    $arrValues['last_post_str'].(!empty($arrValues['last_post_date']) ? ', '.$arrValues['last_post_date'] : '')
                   ));
                   $this->_objTpl->parse('showCategories');
                   $index++;
               }

               $this->_objTpl->hideBlock('noCategories');
           } else {
               //no categories have been found
               $this->_objTpl->setVariable(array(
                   'TXT_NO_CATEGORIES_FOUND'    =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_NONE_FOUND'],
               ));
               $this->_objTpl->parse('noCategories');
               $this->_objTpl->hideBlock('showCategories');
           }


     //show "add category"-Form
        $this->_objTpl->setVariable(array(
            'TXT_TITLE_ADD_CATEGORY'                =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_CATEGORY'],
            'TXT_CATEGORY_ADD_CATEGORY_NAME'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_NAME'],
            'TXT_CATEGORY_ADD_CATEGORY_DESC'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_DESC'],
            'TXT_CATEGORY_ADD_CATEGORY_LANGUAGES'    =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_LANGUAGES'],
            'TXT_CATEGORY_ADD_EXTENDED'                =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_EXTENDED'],
            'TXT_CATEGORY_ADD_CATEGORY_BUTTON'        =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_CATEGORY_STATUS'                    =>    $_ARRAYLANG['TXT_CATEGORY_STATUS'],
           ));

           if (count($this->_arrLanguages) >= 1) {
               $intCounter = 0;
               $arrLanguages = array();

               foreach ($this->_arrLanguages as $intLangId => $arrValues) {
                   if (!isset($arrLanguages[$intCounter % 3])) {
                       $arrLanguages[$intCounter % 3] = '';
                   }

                   $checkboxHtml = '<input checked="checked" type="checkbox" name="frmAddCategory_Languages[]" value="'.$intLangId.'" />'.$arrValues['long'].' ['.$arrValues['short'].']<br />';
                   $arrLanguages[$intCounter % 3] .= $checkboxHtml;
                   $this->_objTpl->setVariable(array(
                       'CATEGORY_ADD_NAME_LANGID'    =>    $intLangId,
                       'CATEGORY_ADD_DESC_LANGID'    =>    $intLangId,
                       'FORUM_ADD_NAME_LANGID'        =>    $intLangId,
                       'FORUM_ADD_DESC_LANGID'        =>    $intLangId,
                       'CATEGORY_ADD_NAME_LANG'    =>    $arrValues['long'].' ['.$arrValues['short'].']',
                       'CATEGORY_ADD_DESC_LANG'    =>    $arrValues['long'].' ['.$arrValues['short'].']',
                       'FORUM_ADD_NAME_LANG'        =>    $arrValues['long'].' ['.$arrValues['short'].']',
                       'FORUM_ADD_DESC_LANG'        =>    $arrValues['long'].' ['.$arrValues['short'].']'
                   ));
                   $this->_objTpl->parse('categoryNameFields');
                   $this->_objTpl->parse('categoryDescFields');
                   $this->_objTpl->parse('forumNameFields');
                   $this->_objTpl->parse('forumDescFields');

                   ++$intCounter;
               }

               $this->_objTpl->setVariable(array(
                   'CATEGORY_ADD_LANGUAGES_1' => isset($arrLanguages[0])
                       ? $arrLanguages[0] : '',
                   'CATEGORY_ADD_LANGUAGES_2' => isset($arrLanguages[1])
                       ? $arrLanguages[1] : '',
                   'CATEGORY_ADD_LANGUAGES_3' => isset($arrLanguages[2])
                       ? $arrLanguages[2] : '',
               ));
           }

     //show "add forum"-form
        $this->_objTpl->setVariable(array(
            'TXT_TITLE_ADD_FORUM'                =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_FORUM'],
            'TXT_CATEGORY_ADD_FORUM_PARCAT'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_PARCAT'],
            'TXT_CATEGORY_ADD_FORUM_NAME'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_NAME'],
            'TXT_CATEGORY_ADD_FORUM_DESC'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_DESC'],
            'TXT_CATEGORY_ADD_FORUM_INHERIT'    =>    $_ARRAYLANG['TXT_CATEGORY_ADD_FORUM_INHERIT'],
            'TXT_FORUM_ADD_EXTENDED'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_EXTENDED'],
            'TXT_CATEGORY_ADD_FORUM_BUTTON'        =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_CATEGORY_STATUS'                =>    $_ARRAYLANG['TXT_CATEGORY_STATUS'],
           ));
           $this->_objTpl->setVariable('CATEGORY_FORUM_ADD_DROPDOWN',$this->createForumDD('frmAddCategory_ParentId',0,'onchange="markCheckboxes(this.options[this.selectedIndex].value);"', null, false, true));

           foreach ($this->_arrTranslations as $intCatId => $arrInner) {
               $strLanguages = '';
               foreach ($arrInner as $intLangId => $arrTranslations) {
                   $strLanguages .= $intLangId.',';
               }
               $strLanguages = substr($strLanguages,0,-1);

            $this->_objTpl->setVariable(array(
                'FORUM_ADD_PARCAT_ID'        =>    $intCatId,
                'FORUM_ADD_PARCAT_VALUES'    =>    $strLanguages
               ));
               $this->_objTpl->parse('forumAllowedParcats');
           }
    }


    /**
     * Change the "status"-flag of a category. If 2nd parameter is empty, the current status will be inverted.
     *
     * @global     ADONewConnection
     * @global     array
     * @param    integer        $intCatId: The status of the category with this id will be inverted
     * @param     integer        $intNewStatus: The category will be set to 0 (inactive) or 1 (active).
     */
    function setCategoryStatus($intCatId,$intNewStatus='') {
        global $objDatabase, $_ARRAYLANG;

        $intCatId = intval($intCatId);

        if ($intCatId != 0) {
            if ($intNewStatus == '') {
                $objResult = $objDatabase->Execute('SELECT    status
                                                    FROM    '.DBPREFIX.'module_forum_categories
                                                    WHERE    id='.$intCatId.'
                                                    LIMIT    1
                                                ');
                if ($objResult->RecordCount() == 1) {
                    if (intval($objResult->fields['status']) == 0) {
                        $intNewStatus = 1;
                    } else {
                        $intNewStatus = 0;
                    }
                } else {
                    return false; //no category found, return false
                }
            } else {
                $intNewStatus = intval($intNewStatus);
            }

            $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_forum_categories
                                    SET        status="'.$intNewStatus.'"
                                    WHERE    id='.$intCatId.'
                                    LIMIT    1
                                ');
            $this->_strOkMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_STATUS_UPDATED'];
//            $objCache = new \CacheManager();
//            $objCache->deleteAllFiles();
        }
    }


    /**
     * Delete a category (and all its subcategories) and postings of those categories. The function is used recursive.
     *
     * @global     ADONewConnection
     * @global     array
     * @param     integer     $intCatId: The category with this id will be deleted
     */
    function deleteCategory($intCatId) {
        global $objDatabase, $_ARRAYLANG;

        $intCatId = intval($intCatId);

        $objResult = $objDatabase->Execute('SELECT    id
                                            FROM    '.DBPREFIX.'module_forum_categories
                                            WHERE    parent_id = '.$intCatId.'
                                        ');
        if ($objResult->RecordCount() > 0) {
            while(!$objResult->EOF) {
                $query = "    SELECT `thread_id` FROM `".DBPREFIX."module_forum_postings` WHERE `category_id` = ".$objResult->fields['id']." AND `prev_post_id` = 0";
                if(($objRS = $objDatabase->Execute($query)) !== false){
                    while(!$objRS->EOF){
                        $query = '    DELETE FROM `'.DBPREFIX.'module_forum_notification`
                                    WHERE `thread_id` = '.$objRS->fields['thread_id'];
                        if($objDatabase->Execute($query) === false){
                            die('Database error: '.$objDatabase->ErrorMsg());
                        }
                        $objRS->MoveNext();
                    }
                }

                $this->deleteCategory($objResult->fields['id']);    //recursive step for subcategories
                $objResult->MoveNext();
            }
        }

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_forum_categories
                                WHERE    id = '.$intCatId.'
                                LIMIT    1
                            ');

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_forum_categories_lang
                                WHERE    category_id='.$intCatId.'
                            ');

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_forum_statistics
                                WHERE    category_id = '.$intCatId.'
                                LIMIT    1
                            ');

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_forum_access
                                WHERE    category_id = '.$intCatId.'
                            ');

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_forum_postings
                                WHERE    category_id = '.$intCatId.'
                            ');

        $this->_strOkMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_DELETED'];
//        $objCache = new \CacheManager();
//        $objCache->deleteAllFiles();
    }


    /**
     * Save the sorting of categories/forums
     *
     * @global     ADONewConnection
     * @global     array
     */
    function saveCategorySorting() {
        global $objDatabase, $_ARRAYLANG;

        if (is_array($_POST)) {
            foreach($_POST as $strKey => $strValue) { //sortingSystem2
                if (substr($strKey,0,13) == 'sortingSystem') {
                    $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_forum_categories
                                            SET        order_id='.$strValue.'
                                            WHERE    id='.substr($strKey,13).'
                                            LIMIT    1
                                        ');
                }
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_SORTING_UPDATED'];
//            $objCache = new \CacheManager();
//            $objCache->deleteAllFiles();
        }
    }


    /**
     * Perform all "multi action" for all selected categories.
     *
     */
    function doCategoryMultiAction() {
        if (is_array($_POST['selectedCategoryId'])) {
            foreach ($_POST['selectedCategoryId'] as $intKey => $intCatId) {
                switch ($_POST['frmShowCategories_MultiAction']) {
                    case 'activate':
                            $this->setCategoryStatus($intCatId,1);
                        break;
                    case 'deactivate':
                            $this->setCategoryStatus($intCatId,0);
                        break;
                    case 'delete':
                            $this->deleteCategory($intCatId);
                        break;
                }
            }
        }
    }


    /**
     * Add a new category / forum to database.
     *
     * @global     ADONewConnection
     * @global     array
     */
    function addCategory() {
        global $objDatabase, $_ARRAYLANG;

        $intParentId     = intval($_POST['frmAddCategory_ParentId']);
        $boolInherit    = intval($_POST['frmAddCategory_Inherit']);
        $boolStatus        = intval($_POST['frmAddCategory_Status']) > 0 ? 1 : 0;
        if (is_array($_POST['frmAddCategory_Languages'])) {
            foreach ($_POST['frmAddCategory_Languages'] as $intKey => $intLangId) {
                $arrTranslations[$intLangId] = array(    'name'    =>    addslashes(strip_tags($_POST['frmAddCategory_Name_'.$intLangId])),
                                                        'desc'    =>    addslashes(strip_tags($_POST['frmAddCategory_Desc_'.$intLangId]))
                                                    );
            }
        }

        if (is_array($arrTranslations)) {
            $objDatabase->Execute('    INSERT
                                    INTO    '.DBPREFIX.'module_forum_categories
                                    SET        parent_id='.$intParentId.',
                                            order_id=99,
                                            status="'.$boolStatus.'"
                                ');

            $intInsertedId = $objDatabase->insert_id();

            foreach ($arrTranslations as $intLangId => $arrValues) {
                  $objDatabase->Execute('    INSERT
                                        INTO    '.DBPREFIX.'module_forum_categories_lang
                                        SET        category_id='.$intInsertedId.',
                                                lang_id='.$intLangId.',
                                                name="'.$arrValues['name'].'",
                                                description="'.$arrValues['desc'].'"
                                    ');
            }

            if ($boolInherit == 1 && $intParentId != 0) {
                $arrRights = $this->createAccessArray($intParentId);
                if (count($arrRights) > 0) {
                    foreach ($arrRights as $intGroupId => $arrValues) {
                        $this->saveRights($intInsertedId,$intGroupId,$arrValues,false);
                    }
                }

            }

            if ($intParentId != 0) {
                $objDatabase->Execute('    INSERT
                                        INTO    '.DBPREFIX.'module_forum_statistics
                                        SET        category_id='.$intInsertedId.',
                                                thread_count=0,
                                                post_count=0,
                                                last_post_id=0
                                    ');
            }

            $this->_arrTranslations = $this->createTranslationArray();
            $this->_strOkMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_SUCCESS'];
//            $objCache = new \CacheManager();
//            $objCache->deleteAllFiles();
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_ERROR'];
        }
    }


    /**
     * Edit an existing category / forum with the id in the parameter
     *
     * @global     ADONewConnection
     * @global     array
     * @param    integer        $intCategoryId: The category / forum with this id should be edited
     */
    function editCategory($intCategoryId) {
        global $objDatabase, $_ARRAYLANG;

        $this->_strPageTitle = $_ARRAYLANG['TXT_FORUM_CATEGORY_EDIT'];
        $this->_objTpl->loadTemplateFile('module_forum_category_edit.html',true,true);
        $this->_objTpl->setVariable(array(
            'TXT_TITLE_EDIT_CATEGORY'                =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_EDIT'],
            'TXT_CATEGORY_ADD_FORUM_PARCAT'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_PARCAT'],
            'TXT_CATEGORY_EDIT_CATEGORY_NAME'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_NAME'],
            'TXT_CATEGORY_EDIT_CATEGORY_DESC'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ADD_DESC'],
            'TXT_CATEGORY_EDIT_CATEGORY_LANGUAGES'    =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_LANGUAGES'],
            'TXT_CATEGORY_EDIT_EXTENDED'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_EXTENDED'],
            'TXT_CATEGORY_EDIT_CATEGORY_BUTTON'        =>    $_ARRAYLANG['TXT_SAVE'],
           ));

           $intCategoryId = intval($intCategoryId);

        $objResult = $objDatabase->Execute('SELECT    parent_id
                                            FROM    '.DBPREFIX.'module_forum_categories
                                            WHERE    id = '.$intCategoryId.'
                                            LIMIT    1
                                        ');
        if ($objResult->RecordCount() == 1) {
            $intParentId     = intval($objResult->fields['parent_id']);

            $this->_objTpl->setVariable(array(
                'VALUE_CATEGORY_ID' => $intCategoryId,
                'VALUE_NAME'        => isset($this->_arrTranslations[$intCategoryId][$this->_intLangId]) ? $this->_arrTranslations[$intCategoryId][$this->_intLangId]['name'] : '',
                'VALUE_DESC'        => isset($this->_arrTranslations[$intCategoryId][$this->_intLangId]) ? $this->_arrTranslations[$intCategoryId][$this->_intLangId]['desc'] : '',
            ));

            if ($intParentId == 0) {
                //category
                $this->_objTpl->setVariable('VALUE_CATEGORY_NOPARCAT',0);
                $this->_objTpl->parse('isCategory');
                $this->_objTpl->hideBlock('isForum');

            } else {
                //forum
                $this->_objTpl->setVariable('VALUE_CATEGORY_DD',$this->createForumDD('frmUpdateCategory_ParentId',$intParentId,'onchange="markCheckboxes(this.options[this.selectedIndex].value);"', '', false));

                   foreach ($this->_arrTranslations as $intCatId => $arrInner) {
                       $strLanguages = '';
                       foreach ($arrInner as $intLangId => $arrTranslations) {
                           $strLanguages .= $intLangId.',';
                       }
                       $strLanguages = substr($strLanguages,0,-1);

                    $this->_objTpl->setVariable(array(
                        'FORUM_EDIT_PARCAT_ID'        =>    $intCatId,
                        'FORUM_EDIT_PARCAT_VALUES'    =>    $strLanguages
                       ));
                       $this->_objTpl->parse('forumAllowedParcats');
                   }

                $this->_objTpl->parse('isForum');
                $this->_objTpl->hideBlock('isCategory');
            }

               if (count($this->_arrLanguages) > 0) {
                   $intCounter = 0;
                   $arrLanguages = array();

                   foreach ($this->_arrLanguages as $intLangId => $arrValues) {
                       $strChecked   = (array_key_exists($intLangId, $this->_arrTranslations[$intCategoryId])) ? 'checked' : '';
                       $strDisabled  = ($intParentId == 0) ? '' : ((!array_key_exists($intLangId, $this->_arrTranslations[$intParentId])) ? 'disabled="disabled"' : '');
                       if (!isset($arrLanguages[$intCounter % 3])) {
                           $arrLanguages[$intCounter % 3] = '';
                       }

                       $checkboxHtml = '<input type="checkbox" name="frmUpdateCategory_Languages[]" value="'.$intLangId.'" '.$strDisabled.' '.$strChecked.' />'.$arrValues['long'].' ['.$arrValues['short'].']<br />';
                       $arrLanguages[$intCounter % 3] .=  $checkboxHtml;
                       $this->_objTpl->setVariable(array(
                           'CATEGORY_EDIT_NAME_LANGID' => $intLangId,
                           'CATEGORY_EDIT_DESC_LANGID' => $intLangId,
                           'CATEGORY_EDIT_NAME_LANG'   => $arrValues['long'].' ['.$arrValues['short'].']',
                           'CATEGORY_EDIT_DESC_LANG'   => $arrValues['long'].' ['.$arrValues['short'].']',
                           'CATEGORY_EDIT_NAME_VALUE'  => isset($this->_arrTranslations[$intCategoryId][$intLangId]) ? $this->_arrTranslations[$intCategoryId][$intLangId]['name'] : '',
                           'CATEGORY_EDIT_DESC_VALUE'  => isset($this->_arrTranslations[$intCategoryId][$intLangId]) ? $this->_arrTranslations[$intCategoryId][$intLangId]['desc'] : '',
                       ));
                       $this->_objTpl->parse('categoryNameFields');
                       $this->_objTpl->parse('categoryDescFields');

                       ++$intCounter;
                   }

                   $this->_objTpl->setVariable(array(
                       'CATEGORY_EDIT_LANGUAGES_1' => isset($arrLanguages[0])
                           ? $arrLanguages[0] : '',
                       'CATEGORY_EDIT_LANGUAGES_2' => isset($arrLanguages[1])
                           ? $arrLanguages[1] : '',
                       'CATEGORY_EDIT_LANGUAGES_3' => isset($arrLanguages[2])
                           ? $arrLanguages[2] : '',
                   ));
               }


        } else {
            //no category with this id, redirect
            \Cx\Core\Csrf\Controller\Csrf::header("location: index.php?cmd=Forum");
        }

    }


    /**
     * Update dataset of a category / forum.
     *
     */
    function updateCategory() {
        global $objDatabase, $_ARRAYLANG;

        $intCategoryId     = intval($_POST['frmUpdateCategory_CategoryId']);
        $intParentId     = intval($_POST['frmUpdateCategory_ParentId']);

        $arrActiveLanguages = array();
        foreach ($this->_arrLanguages as $intLangId => $arrLangValues) {
            $arrActiveLanguages[$intLangId] = false;
        }

        if (is_array($_POST['frmUpdateCategory_Languages'])) {
            foreach ($_POST['frmUpdateCategory_Languages'] as $intKey => $intLangId) {
                $arrTranslations[$intLangId] = array(    'name'    =>    addslashes(strip_tags($_POST['frmUpdateCategory_Name_'.$intLangId])),
                                                        'desc'    =>    addslashes(strip_tags($_POST['frmUpdateCategory_Desc_'.$intLangId]))
                                                    );
                $arrActiveLanguages[$intLangId] = true;
            }
        }

         if (is_array($arrTranslations) && $intCategoryId != 0 && $this->checkParentCategory($intCategoryId,$intParentId)) {
            $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_forum_categories
                                    SET        parent_id='.$intParentId.'
                                    WHERE    id='.$intCategoryId.'
                                    LIMIT    1
                                ');

            $objDatabase->Execute('    DELETE
                                    FROM    '.DBPREFIX.'module_forum_categories_lang
                                    WHERE    category_id='.$intCategoryId.'
                                ');

            $this->deleteSubcatLanguages($intCategoryId, $arrActiveLanguages);

            foreach ($arrTranslations as $intLangId => $arrValues) {
                  $objDatabase->Execute('    INSERT
                                        INTO    '.DBPREFIX.'module_forum_categories_lang
                                        SET        category_id='.$intCategoryId.',
                                                lang_id='.$intLangId.',
                                                name="'.$arrValues['name'].'",
                                                description="'.$arrValues['desc'].'"
                                    ');
            }

            $this->_arrTranslations = $this->createTranslationArray();
            $this->_strOkMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_UPDATE_OK'];
//            $objCache = new \CacheManager();
//            $objCache->deleteAllFiles();
        } else {
            //no languages have been selected, show error
            $this->_strErrMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_UPDATE_ERROR'];
        }


    }


    /**
     * This functions checks if the new parent-category isn't a subcategory of the category.
     *
     * @param    integer        $intCategoryId: id of the category which should be checked
     * @param    integer        $intNewParentId: id of the new parent-category
     * @return    boolean        true: everthing is okay, false: you shouldn't allow this
     */
    function checkParentCategory($intCategoryId, $intNewParentId) {
        global $objDatabase;

        if ($intCategoryId == $intNewParentId) {
            return false;
        }

        $intCategoryId = intval($intCategoryId);
        $objResult = $objDatabase->Execute('SELECT    id
                                            FROM    '.DBPREFIX.'module_forum_categories
                                            WHERE    parent_id='.$intCategoryId.'
                                        ');
        while (!$objResult->EOF) {
            if (!$this->checkParentCategory($objResult->fields['id'],$intNewParentId)) {
                return false;
            }
            $objResult->MoveNext();
        }

        return true;
    }


    function deleteSubcatLanguages($intParentId, $arrLanguages) {
        global $objDatabase;

        $intParentId = intval($intParentId);

        if ($intParentId > 0 && is_array($arrLanguages)) {
            $objResult = $objDatabase->Execute('SELECT    id
                                                FROM    '.DBPREFIX.'module_forum_categories
                                                WHERE    parent_id='.$intParentId.'
                                            ');
            while (!$objResult->EOF) {

                foreach ($arrLanguages as $intLangId => $boolActive) {
                    if (!$boolActive) {
                        $objDatabase->Execute('    DELETE
                                                FROM    '.DBPREFIX.'module_forum_categories_lang
                                                WHERE    category_id='.$objResult->fields['id'].' AND
                                                        lang_id='.$intLangId.'
                                                LIMIT    1
                                            ');
                    }
                }
                $this->deleteSubcatLanguages($objResult->fields['id'],$arrLanguages);
                $objResult->MoveNext();
            }
        }
//        $objCache = new \CacheManager();
//        $objCache->deleteAllFiles();
    }


    /**
     * Show "access rights"-form for a selected category.
     *
     * @global    ADONewConnection
     * @global     array
     * @param    integer        $intCategoryId: The category / forum with this id should be edited
     */
    function editCategoryAccess($intCategoryId) {
        global $objDatabase, $_ARRAYLANG;

        $this->_strPageTitle = $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS'];
        $this->_objTpl->loadTemplateFile('module_forum_category_access.html',true,true);
        $this->_objTpl->setGlobalVariable(array(
            'TXT_FORUM_SELECT_ALL'            =>    $_ARRAYLANG['TXT_FORUM_SELECT_ALL'],
            'TXT_FORUM_DESELECT_ALL'        =>    $_ARRAYLANG['TXT_FORUM_DESELECT_ALL'],
        ));
        $this->_objTpl->setVariable(array(
            'TXT_TITLE_CATEGORY_ACCESS'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS'],
            'TXT_FORUM_ANONYMOUS'            =>    $_ARRAYLANG['TXT_FORUM_ANONYMOUS_GROUP_NAME'],
            'TXT_SUBTITLE_GROUPNAME'        =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_GROUPNAME'],
            'TXT_SUBTITLE_READ'                =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_READ'],
            'TXT_SUBTITLE_WRITE'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_WRITE'],
            'TXT_SUBTITLE_EDIT'                =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_EDIT'],
            'TXT_SUBTITLE_DELETE'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_DELETE'],
            'TXT_SUBTITLE_MOVE'                =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_MOVE'],
            'TXT_SUBTITLE_CLOSE'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_CLOSE'],
            'TXT_SUBTITLE_STICKY'            =>    $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_STICKY'],
            'TXT_CATEGORY_ACCESS_BEQUEATH'    =>    $_ARRAYLANG['TXT_CATEGORY_ACCESS_BEQUEATH'],
            'TXT_CATEGORY_ACCESS_BUTTON'    =>    $_ARRAYLANG['TXT_SAVE']
           ));

           $intCategoryId = intval($intCategoryId);

           if (is_array($this->_arrTranslations[$intCategoryId])) {
               $this->_objTpl->setVariable(array(
                   'VALUE_CATEGORY_ID'        =>    $intCategoryId,
                   'VALUE_CATEGORY_NAME'    =>    $this->_arrTranslations[$intCategoryId][$this->_intLangId]['name']
               ));

               $arrAccessRights = $this->createAccessArray($intCategoryId);
               if (count($arrAccessRights) > 0) {
                   $intCounter = 0;
                   foreach($arrAccessRights as $intGroupId =>    $arrValues) {
                       $this->_objTpl->setVariable(array(
                           'VALUE_ROWCLASS'    =>    ($intCounter % 2)+1,
                           'VALUE_GROUP_ID'    =>    $intGroupId,
                           'VALUE_GROUPNAME'    =>    $arrValues['name'],
                           'VALUE_READ'        =>    ($arrValues['read']   == 1) ? 'checked="checked"' : '',
                           'VALUE_WRITE'        =>    ($arrValues['write']  == 1) ? 'checked="checked"' : '',
                           'VALUE_EDIT'        =>    ($arrValues['edit']   == 1) ? 'checked="checked"' : '',
                           'VALUE_DELETE'        =>    ($arrValues['delete'] == 1) ? 'checked="checked"' : '',
                           'VALUE_MOVE'        =>    ($arrValues['move']   == 1) ? 'checked="checked"' : '',
                           'VALUE_CLOSE'        =>    ($arrValues['close']  == 1) ? 'checked="checked"' : '',
                           'VALUE_STICKY'        =>    ($arrValues['sticky'] == 1) ? 'checked="checked"' : '',

                       ));

                       $this->_objTpl->parse('showRights');
                       ++$intCounter;
                   }
                   $this->_objTpl->setVariable(array(
                       'TXT_CATEGORY_GLOBAL_RIGHTS'     => $_ARRAYLANG['TXT_CATEGORY_GLOBAL_RIGHTS'],
                   ));
                   $this->_objTpl->hideBlock('noGroupsMessage');
               } else {
                   //no userrights existing, hide block
                   $this->_objTpl->setVariable('TXT_CATEGORY_ACCESS_NOGROUPS',$_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_NOGROUPS']);
                   $this->_objTpl->parse('noGroupsMessage');

                   $this->_objTpl->hideBlock('showRights');
                   $this->_objTpl->hideBlock('noGroupsHide_1');
                   $this->_objTpl->hideBlock('noGroupsHide_2');
               }
           } else {
               //wrong id, redirect
               \Cx\Core\Csrf\Controller\Csrf::header("location: index.php?cmd=Forum");
           }
    }


    /**
     * This function collects and filter all information from the "edit-access"-form.
     *
     * @global     ADONewConnection
     * @global     array
     */
    function updateCategoryAccess() {
        global $objDatabase, $_ARRAYLANG;
        $intCategoryId     = intval($_POST['frmCategoryAccess_CategoryId']);
        $boolBequeath    = intval($_POST['frmCategoryAccess_Bequeath']);

        foreach ($_POST as $strKey => $strValue) {
            $arrExplode = explode('_',$strKey);
            if (count($arrExplode) == 3) {
                if ($arrExplode[2] >= 0) {
                    //Filter selection-boxes, they should be saved :-)
                    $arrRights[$arrExplode[2]][strtolower($arrExplode[1])] = 1;
                }
            }
        }

           foreach($arrRights as $intGroupId => $arrRights) {
            $this->saveRights($intCategoryId,$intGroupId,$arrRights,$boolBequeath);
        }
//        $objCache = new CacheManager();
//        $objCache->deleteAllFiles();
        $this->_strOkMessage = $_ARRAYLANG['TXT_FORUM_CATEGORY_ACCESS_UPDATED'];
    }


    /**
     * Save access rights for a given group in a given board.
     *
     * @param    integer        $intCatId: The access rights will be set for the category with this id
     * @param    integer        $intGroupId: The access rights will be set for the frontend-group with this id
     * @param    array        $arrRights: an array containing the new rights. (Example: 'read' => 1, 'write' => 1, 'edit' => 0, ...)
     * @param    bool        $boolBequeath: If the paramet is set to true, subcategories will inherit the rights from the parent cat.
     */
    function saveRights($intCatId,$intGroupId,$arrRights,$boolBequeath = false) {
        global $objDatabase;

        $intCatId     = intval($intCatId);
        $intGroupId = intval($intGroupId);

        if ($boolBequeath) {
            $objResult = $objDatabase->Execute('SELECT        id
                                                FROM        '.DBPREFIX.'module_forum_categories
                                                WHERE        parent_id='.$intCatId.'
                                                ORDER BY     id ASC
                                            ');
            while (!$objResult->EOF) {
                $this->saveRights($objResult->fields['id'],$intGroupId,$arrRights,$boolBequeath);
                $objResult->MoveNext();
            }
        }

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_forum_access
                                WHERE    category_id='.$intCatId.' AND
                                        group_id='.$intGroupId.'
                                LIMIT    1
                            ');

        $objDatabase->Execute('    INSERT
                                INTO    '.DBPREFIX.'module_forum_access
                                SET        '.DBPREFIX.'module_forum_access.category_id='.$intCatId.',
                                        '.DBPREFIX.'module_forum_access.group_id='.$intGroupId.',
                                        '.DBPREFIX.'module_forum_access.read="'.intval($arrRights['read']).'",
                                        '.DBPREFIX.'module_forum_access.write="'.intval($arrRights['write']).'",
                                        '.DBPREFIX.'module_forum_access.edit="'.intval($arrRights['edit']).'",
                                        '.DBPREFIX.'module_forum_access.delete="'.intval($arrRights['delete']).'",
                                        '.DBPREFIX.'module_forum_access.move="'.intval($arrRights['move']).'",
                                        '.DBPREFIX.'module_forum_access.close="'.intval($arrRights['close']).'",
                                        '.DBPREFIX.'module_forum_access.sticky="'.intval($arrRights['sticky']).'"'
                            );
    }


    /**
     * Show settings.
     *
     */
    function showSettings() {
        global $_ARRAYLANG, $_CONFIG;
        $this->_strPageTitle = $_ARRAYLANG['TXT_FORUM_MENU_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_forum_settings.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_TITLE_GENERAL'                     =>    $_ARRAYLANG['TXT_FORUM_SETTINGS_GENERAL'],
            'TXT_GENERAL_THREAD_PAGING'             =>    $_ARRAYLANG['TXT_FORUM_SETTINGS_THREAD_PAGING'],
            'TXT_GENERAL_THREAD_PAGING_HELP'        =>    $_ARRAYLANG['TXT_FORUM_SETTINGS_THREAD_PAGING_HELP'],
            'TXT_GENERAL_POSTING_PAGING'            =>    $_ARRAYLANG['TXT_FORUM_SETTINGS_POSTING_PAGING'],
            'TXT_GENERAL_POSTING_PAGING_HELP'       =>    $_ARRAYLANG['TXT_FORUM_SETTINGS_POSTING_PAGING_HELP'],
            'TXT_LATEST_ENTRIES_COUNT'              =>    $_ARRAYLANG['TXT_LATEST_ENTRIES_COUNT'],
            'TXT_LATEST_ENTRIES_COUNT_HELP'         =>    $_ARRAYLANG['TXT_LATEST_ENTRIES_COUNT_HELP'],
            'TXT_BUTTON_SAVE'                       =>    $_ARRAYLANG['TXT_SAVE'],
            'TXT_FORUM_BLOCK_TEMPLATE'              =>    $_ARRAYLANG['TXT_FORUM_BLOCK_TEMPLATE'],
            'TXT_FORUM_TEMPLATE'                    =>    $_ARRAYLANG['TXT_FORUM_TEMPLATE'],
            'TXT_BLOCK_TEMPLATE_HELP'               =>    $_ARRAYLANG['TXT_BLOCK_TEMPLATE_HELP'],
            'TXT_FORUM_PLACEHOLDERS'                =>    $_ARRAYLANG['TXT_FORUM_PLACEHOLDERS'],
            'TXT_FORUM_CLICK_TO_INSERT'             =>    $_ARRAYLANG['TXT_FORUM_CLICK_TO_INSERT'],
            'TXT_FORUM_CLICK_VARIABLE_TO_INSERT'    =>    $_ARRAYLANG['TXT_FORUM_CLICK_VARIABLE_TO_INSERT'],

            'TXT_FORUM_LATEST_ENTRIES'              =>    sprintf($_ARRAYLANG['TXT_FORUM_LATEST_ENTRIES'], $this->_arrSettings['latest_entries_count']),
            'TXT_FORUM_OVERVIEW_FORUM'              =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'],
            'TXT_FORUM_THREAD_STARTER'              =>    $_ARRAYLANG['TXT_FORUM_THREAD_STARTER'],
            'TXT_FORUM_FORUM_NAME'                  =>    $_ARRAYLANG['TXT_FORUM_FORUM_NAME'],
            'TXT_FORUM_POST_COUNT'                  =>    $_ARRAYLANG['TXT_FORUM_POST_COUNT'],
            'TXT_FORUM_POST_COUNT_INFO'             =>    $_ARRAYLANG['TXT_FORUM_POST_COUNT_INFO'],
            'TXT_FORUM_THREAD_CREATE_DATE'          =>    $_ARRAYLANG['TXT_FORUM_THREAD_CREATE_DATE'],
            'TXT_FORUM_ROWCLASS'                    =>    $_ARRAYLANG['TXT_FORUM_ROWCLASS'],
            'TXT_FORUM_THREAD_NAME'                 =>    $_ARRAYLANG['TXT_FORUM_THREAD_NAME'],
            'TXT_FORUM_THREAD'                      =>    $_ARRAYLANG['TXT_FORUM_THREAD'],
            'TXT_FORUM_THREAD_STRATER'              =>    $_ARRAYLANG['TXT_FORUM_THREAD_STRATER'],
            'TXT_FORUM_THREAD_CREATE_DATE'          =>    $_ARRAYLANG['TXT_FORUM_THREAD_CREATE_DATE'],
            'TXT_FORUM_THREAD_CREATE_DATE_INFO'     =>    $_ARRAYLANG['TXT_FORUM_THREAD_CREATE_DATE_INFO'],
            'TXT_FORUM_HOMECONTENT_USAGE'           =>    $_ARRAYLANG['TXT_FORUM_HOMECONTENT_USAGE'],
            'TXT_FORUM_HOMECONTENT_USAGE_TEXT'      =>    $_ARRAYLANG['TXT_FORUM_HOMECONTENT_USAGE_TEXT'],
            'TXT_FORUM_EMAIL_TEMPLATE_SUBJECT'      =>    $_ARRAYLANG['TXT_FORUM_EMAIL_TEMPLATE_SUBJECT'],
            'TXT_FORUM_LATEST_SUBJECT'              =>    $_ARRAYLANG['TXT_FORUM_LATEST_SUBJECT'],
            'TXT_FORUM_LATEST_MESSAGE'              =>    $_ARRAYLANG['TXT_FORUM_LATEST_MESSAGE'],
            'TXT_FORUM_USERNAME'                    =>    $_ARRAYLANG['TXT_FORUM_USERNAME'],
            'TXT_FORUM_THREAD_URL'                  =>    $_ARRAYLANG['TXT_FORUM_THREAD_URL'],
            'TXT_FORUM_SETTINGS'                    =>    $_ARRAYLANG['TXT_FORUM_SETTINGS'],
            'TXT_FORUM_SHOW_HOME_CONTENT'           =>    $_ARRAYLANG['TXT_FORUM_SHOW_HOME_CONTENT'],
            'TXT_FORUM_ACTIVATE'                    =>    $_ARRAYLANG['TXT_FORUM_ACTIVATE'],
            'TXT_FORUM_DEACTIVATE'                  =>    $_ARRAYLANG['TXT_FORUM_DEACTIVATE'],
            'TXT_FORUM_EMAIL_TEMPLATE'              =>    $_ARRAYLANG['TXT_FORUM_EMAIL_TEMPLATE'],
            'TXT_FORUM_EMAIL_NOTIFICATION'          =>    $_ARRAYLANG['TXT_FORUM_EMAIL_NOTIFICATION'],
            'TXT_FORUM_NOTIFICATION_TEMPLATE_HELP'  => $_ARRAYLANG['TXT_FORUM_NOTIFICATION_TEMPLATE_HELP'],
            'TXT_FORUM_EMAIL_TEMPLATE_FROM_EMAIL'   => $_ARRAYLANG['TXT_FORUM_EMAIL_TEMPLATE_FROM_EMAIL'],
            'TXT_FORUM_EMAIL_TEMPLATE_FROM_NAME'    =>    $_ARRAYLANG['TXT_FORUM_EMAIL_TEMPLATE_FROM_NAME'],
            'TXT_FORUM_SHOW_TAG_CONTENT'            =>    $_ARRAYLANG['TXT_FORUM_SHOW_TAG_CONTENT'],
            'TXT_FORUM_TAG_COUNT'                   =>    $_ARRAYLANG['TXT_FORUM_TAG_COUNT'],
            'TXT_FORUM_TAG_COUNT_HELP'              =>    $_ARRAYLANG['TXT_FORUM_TAG_COUNT_HELP'],
            'TXT_FORUM_WYSIWYG_EDITOR'              =>    $_ARRAYLANG['TXT_FORUM_WYSIWYG_EDITOR'],
            'TXT_FORUM_WYSIWYG_EDITOR_HELP'         =>    $_ARRAYLANG['TXT_FORUM_WYSIWYG_EDITOR_HELP'],
            'TXT_FORUM_BANNED_WORDS'                =>    $_ARRAYLANG['TXT_FORUM_BANNED_WORDS'],
            'TXT_FORUM_BANNED_WORDS_HELP'           =>    $_ARRAYLANG['TXT_FORUM_BANNED_WORDS_HELP'],
            'TXT_FORUM_LATEST_POST_PER_THREAD'      =>    $_ARRAYLANG['TXT_FORUM_LATEST_POST_PER_THREAD'],
            'TXT_FORUM_LATEST_POST_PER_THREAD_HELP' =>    $_ARRAYLANG['TXT_FORUM_LATEST_POST_PER_THREAD_HELP'],
            'TXT_FORUM_ALLOWED_EXTENSIONS'          =>    $_ARRAYLANG['TXT_FORUM_ALLOWED_EXTENSIONS'],
            'TXT_FORUM_ALLOWED_EXTENSIONS_HELP'     =>    $_ARRAYLANG['TXT_FORUM_ALLOWED_EXTENSIONS_HELP'],
            'TXT_FORUM_INSERT_AT_POSITION'          =>    $_ARRAYLANG['TXT_FORUM_INSERT_AT_POSITION'],
            'FORUM_SHOW_CONTENT_'.$_CONFIG['forumHomeContent']    =>  'checked="checked"',
            'FORUM_SHOW_TAG_CONTENT_'.$_CONFIG['forumTagContent']    =>  'checked="checked"',

           ));
           $this->_objTpl->setVariable(array(
               'SETTINGS_THREAD_PAGING'             =>    $this->_arrSettings['thread_paging'],
               'SETTINGS_POSTING_PAGING'            =>    $this->_arrSettings['posting_paging'],
               'SETTINGS_LATEST_ENTRIES_COUNT'      =>    $this->_arrSettings['latest_entries_count'],
               'SETTINGS_BLOCK_TEMPLATE'            =>    $this->_arrSettings['block_template'],
               'SETTINGS_NOTIFICATION_TEMPLATE'     =>    $this->_arrSettings['notification_template'],
               'SETTINGS_NOTIFICATION_SUBJECT'      =>    $this->_arrSettings['notification_subject'],
               'SETTINGS_NOTIFICATION_FROM_EMAIL'   =>    $this->_arrSettings['notification_from_email'],
               'SETTINGS_NOTIFICATION_FROM_NAME'    =>    $this->_arrSettings['notification_from_name'],
               'FORUM_SETTINGS_BANNED_WORDS'        =>    implode(',', $this->_arrSettings['banned_words']),
               'FORUM_SETTINGS_TAG_COUNT'           =>    $this->_arrSettings['tag_count'],
               'FORUM_SETTINGS_WYSIWYG_EDITOR_'
               .$this->_arrSettings['wysiwyg_editor']            =>    'checked="checked"',
               'FORUM_LATEST_POST_PER_THREAD_'
               .$this->_arrSettings['latest_post_per_thread']    =>    'checked="checked"',
               'FORUM_ALLOWED_EXTENSIONS'           =>    $this->_arrSettings['allowed_extensions'],
           ));
    }


    /**
     * Validate and save new settings.
     *
     * @global    ADONewConnection
     * @global     array
     * @global     array
     */
    function updateSettings() {
        global $objDatabase, $_ARRAYLANG;
        //update settings table and write new settings file for /config
        if (isset($_POST['set_homecontent_submit'])){
            //update settings
            \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
            if (isset($_POST['setHomeContent'])) {
                $setHomeContent = intval($_POST['setHomeContent']);
                if (!\Cx\Core\Setting\Controller\Setting::isDefined('forumHomeContent')) {
                    \Cx\Core\Setting\Controller\Setting::add('forumHomeContent', $setHomeContent, 1, \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
                } else {
                    \Cx\Core\Setting\Controller\Setting::set('forumHomeContent', $setHomeContent);
                    \Cx\Core\Setting\Controller\Setting::update('forumHomeContent');
                }
            }
            if (isset($_POST['setTagContent'])) {
                $forumTagContent = intval($_POST['setTagContent']);
                if (!\Cx\Core\Setting\Controller\Setting::isDefined('forumTagContent')) {
                    \Cx\Core\Setting\Controller\Setting::add('forumTagContent', $forumTagContent, 1, \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
                } else {
                    \Cx\Core\Setting\Controller\Setting::set('forumTagContent', $forumTagContent);
                    \Cx\Core\Setting\Controller\Setting::update('forumTagContent');
                }
            }
        }

        foreach($_POST['setvalue'] as $intSetId => $strSetValue) {
            switch ($intSetId) {
                case 1:
                    $strSetValue = (intval($strSetValue) == 0) ? $this->_arrSettings['thread_paging'] : intval($strSetValue);
                    break;
                case 2:
                    $strSetValue = (intval($strSetValue) == 0) ? $this->_arrSettings['posting_paging'] : intval($strSetValue);
                    break;
                case 3:
                    $strSetValue = (intval($strSetValue) == 0) ? $this->_arrSettings['latest_entries_count'] : intval($strSetValue);
                    break;
                default:
            }
            $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_forum_settings
                                    SET        value="'.addslashes($strSetValue).'"
                                    WHERE    id='.intval($intSetId).'
                                    LIMIT    1');
        }
        $this->_arrSettings     = $this->createSettingsArray();
//        $objCache = new \CacheManager();
//        $objCache->deleteAllFiles();
        $this->_strOkMessage     = $_ARRAYLANG['TXT_FORUM_SETTINGS_UPDATE_OK'];
    }
}
