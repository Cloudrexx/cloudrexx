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
 * Gallery
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     v1.1.0
 * @package     cloudrexx
 * @subpackage  module_gallery
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Gallery\Controller;
use Cx\Core_Modules\Uploader\Model\Entity\Uploader;
use Cx\Lib\FileSystem\FileSystem;

/**
 * Gallery
 *
 * Class to manage the gallery of the CMS
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     v1.1.0
 * @package     cloudrexx
 * @subpackage  module_gallery
 */
class GalleryManager extends GalleryLibrary
{
    var $_objTpl;
    var $strErrMessage = '';
    var $strOkMessage = '';
    var $arrSettings = array();
    var $strImagePath;
    var $strImageWebPath;
    var $strThumbnailPath;
    var $strThumbnailWebPath;
    var $strImportPath;
    var $intLangId;
    var $strPageTitle;
    var $importFiles;
    var $boolGifEnabled = false;
    var $boolJpgEnabled = false;
    var $boolPngEnabled = false;
    var $intMaxEntries = 50;


    private $act = '';

    /**
     * Constructor    -> Create the menu and copy the template
     *
     * @global    array
     * @global    \Cx\Core\Html\Sigma
     * @global    InitCMS
     */
    function __construct()
    {
        global $_ARRAYLANG, $objTemplate, $objInit;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Gallery/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        \JS::registerCSS( substr(ASCMS_MODULE_FOLDER.'/Gallery/View/Style/main.css', 1));

        $this->intLangId=$objInit->userFrontendLangId;

        $this->strImagePath = ASCMS_GALLERY_PATH . '/';
        $this->strImageWebPath = ASCMS_GALLERY_WEB_PATH . '/';
        $this->strThumbnailPath = ASCMS_GALLERY_THUMBNAIL_PATH . '/';
        $this->strThumbnailWebPath = ASCMS_GALLERY_THUMBNAIL_WEB_PATH . '/';
        $this->strImportPath = ASCMS_GALLERY_IMPORT_PATH. '/';
        $this->importWebPath = ASCMS_GALLERY_IMPORT_WEB_PATH. '/';

        if (imagetypes() & IMG_GIF) {
           $this->boolGifEnabled = true;
        }

        if (imagetypes() &  IMG_JPG) {
           $this->boolJpgEnabled = true;
        }

        if (imagetypes() & IMG_PNG) {
           $this->boolPngEnabled = true;
        }

        $this->getSettings();
        $this->checkImages();

        parent::__construct();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION','
            <a href="index.php?cmd=Gallery" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'].'</a>
            <a href="index.php?cmd=Gallery&amp;act=new_cat" class="'.($this->act == 'new_cat' ? 'active' : '').'">'.$_ARRAYLANG['TXT_GALLERY_MENU_NEW_CATEGORY'].'</a>
            <a href="index.php?cmd=Gallery&amp;act=upload_form" class="'.($this->act == 'upload_form' ? 'active' : '').'">'.$_ARRAYLANG['TXT_GALLERY_MENU_UPLOAD'].'</a>
            <a href="index.php?cmd=Gallery&amp;act=import_picture" class="'.($this->act == 'import_picture' ? 'active' : '').'">'.$_ARRAYLANG['TXT_GALLERY_MENU_IMPORT'].'</a>
            <a href="index.php?cmd=Gallery&amp;act=validate_form&amp;type='.$this->arrSettings['validation_standard_type'].'" class="'.($this->act == 'validate_form' ? 'active' : '').'">'.$_ARRAYLANG['TXT_GALLERY_MENU_VALIDATE'].'</a>
            <a href="index.php?cmd=Gallery&amp;act=settings" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_ARRAYLANG['TXT_GALLERY_MENU_SETTINGS'].'</a>');
    }


    /**
     * Determine the page to display and call the appropriate methods
     * @global    \Cx\Core\Html\Sigma
     * @global    array
     */
    function getPage()
    {
        global $objTemplate, $_ARRAYLANG;

        if (!isset($_GET['act'])) {
            $_GET['act']='';
        }
        switch ($_GET['act']) {
            case 'new_cat':
                \Permission::checkAccess(66, 'static');
                $this->newCategory();
            break;
            case 'insert_category':
                $this->insertCategory();
                $this->overview();
            break;
            case 'sort_categories':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'];
                $this->saveCategorySorting();
                $this->overview();
            break;
            case 'category_actions':
                if (isset($_POST['sorting'])) {
                    $this->saveImagesSorting();
                } else {
                    switch ($_POST['frmShowImages_MultiAction']) {
                        case 'delete':
                            if (isset($_POST['selectedImageId'])) {
                                foreach($_POST['selectedImageId'] as $intPicId) {
                                    $this->deleteImage($intPicId);
                                    $this->statusMessage = $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_DELETED'];
                                }
                            }
                        break;
                        case 'activate':
                            if (isset($_POST['selectedImageId'])) {
                                foreach($_POST['selectedImageId'] as $intPicId) {
                                    $this->activatePicture($intPicId, "activate");
                                }
                            }
                        break;
                        case 'deactivate':
                            if (isset($_POST['selectedImageId'])) {
                                foreach($_POST['selectedImageId'] as $intPicId) {
                                    $this->activatePicture($intPicId, "deactivate");
                                }
                            }
                        break;
                        case 'reset':
                            if (isset($_POST['selectedImageId'])) {
                                foreach($_POST['selectedImageId'] as $intPicId) {
                                    $this->resetPicture($intPicId);
                                }
                            }
                        break;
                        case 'move':
                            if (isset($_POST['selectedImageId']) && intval($_POST['frmShowImages_MultiAction_Move']) != 0) {
                                foreach($_POST['selectedImageId'] as $intPicId) {
                                    $this->changeCategoryOfPicture($intPicId,intval($_POST['frmShowImages_MultiAction_Move']));
                                }
                            }
                        break;
                        default: //do nothing
                    }
                }
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'];
                $this->showCategoryDetails(intval($_GET['catid']));
            break;
            case 'activate_category':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'];
                $this->activateCategory(intval($_GET['id']));
                $this->overview();
            break;
            case 'delete_category':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'];
                $this->deleteCategory(intval($_GET['id']));
                $this->overview();
            break;
            case 'edit_category':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_EDIT'];
                $this->editCategory(intval($_GET['id']));
            break;
            case 'update_category':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'];
                $this->updateCategory(intval($_GET['id']));
                $this->overview();
            break;
            case 'cat_details':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $this->showCategoryDetails(intval($_GET['id']));
            break;
            case 'activate_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $this->activatePicture(intval($_GET['id']));
                $this->showCategoryDetails(intval($_GET['gid']));
            break;
            case 'category_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $this->setCategoryImage(intval($_GET['id']));
                $this->showCategoryDetails(intval($_GET['gid']));
            break;
            case 'reset_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $this->resetPicture(intval($_GET['id']));
                $this->showCategoryDetails(intval($_GET['gid']));
            break;
            case 'delete_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $this->deleteImage(intval($_GET['id']));
                $this->showCategoryDetails(intval($_GET['gid']));
            break;
            case 'change_category_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $this->changeCategoryOfPicture(intval($_GET['id']),intval($_GET['catid']));
                $this->showCategoryDetails(intval($_GET['catid']));
            break;
            case 'rotate_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $this->rotatePicture(intval($_GET['id']));
                $this->showCategoryDetails(intval($_GET['catid']));
            break;
            case 'edit_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_PICTURE_DETAILS'];
                $this->showEditPicture();
            break;
            case 'update_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_CATEGORY_DETAILS'];
                $intCatId = $this->updatePicture(); //returns the groupid
                $this->showCategoryDetails($intCatId);
            break;
            case 'upload_form':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_UPLOAD_FORM'];
                \Permission::checkAccess(67, 'static');
                $this->showUploadForm();
            break;
            case 'validate_form':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_VALIDATE'];
                \Permission::checkAccess(69, 'static');
                $this->showValidateForm();

            break;
            case 'validate_single_picture':
                $this->reloadSingleValidate();
                $this->showValidateForm();
            break;
            case 'validate_all_pictures':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_VALIDATE'];
                $this->reloadAllValidate();
                $this->showValidateForm();
            break;
            case 'settings':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_SETTINGS'];
                \Permission::checkAccess(70, 'static');
                $this->showSettings();
            break;
            case 'save_settings':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_SETTINGS'];
                $this->saveSettings();
                $this->showSettings();
            break;
            case 'delete_valid_image':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_VALIDATE'];
                $this->deleteImage(intval($_GET['id']));
                $this->showValidateForm();
                break;
            case 'import_picture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_IMPORT_PICTURES'];
                \Permission::checkAccess(68, 'static');
                $this->importPicture();
            break;
            case 'importFromFolder':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_IMPORT_PICTURES'];
                $this->importFromFolder();
                $this->importPicture();
                $this->showValidateForm();
            break;
            case 'deleteImportPicture':
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_IMPORT_PICTURES'];
                $this->deleteImportPicture();
                $this->importPicture();
            break;
            case 'delete_comment':
                $this->deleteComment($_GET['comId']);
                $_GET['active'] = 'comment';
                $this->showEditPicture();
            break;
            case 'delete_vote':
                $this->deleteVote($_GET['voteId']);
                $_GET['active'] = 'voting';
                $this->showEditPicture();
            break;
            case 'edit_comment':
                $this->strPageTitle = $_ARRAYLANG['TXT_COMMENT_EDIT'];
                $this->showEditComment($_GET['id']);
            break;
            case 'save_comment':
                $this->updateComment();
                $_GET['id'] = intval($_POST['frmEditComment_PicId']);
                $_GET['active'] = 'comment';
                $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_PICTURE_DETAILS'];
                $this->showEditPicture();
            break;
            case 'comment_actions':
                switch ($_POST['frmShowComments_MultiAction'])
                {
                    case 'delete':
                        foreach($_POST['selectedCommentsId'] as $intCommentId) {
                            $this->deleteComment($intCommentId);
                        }
                        $this->statusMessage = $_ARRAYLANG['TXT_GALLERY_COMMENT_DELETES_DONE'];
                    break;
                    default: //do nothing
                }
                $this->showEditPicture();
            break;
            case 'cat_multi_action':
                switch ($_POST['frmShowGallery_MultiAction']) {
                    case 'delete':
                        if (isset($_POST['selectedCategoryId'])) {
                            foreach($_POST['selectedCategoryId'] as $intCatId) {
                                $this->deleteCategory($intCatId);
                            }
                        }
                    break;
                    case 'activate':
                        if (isset($_POST['selectedCategoryId'])) {
                            foreach($_POST['selectedCategoryId'] as $intCatId) {
                                $this->activateCategory($intCatId, "activate");
                            }
                        }
                        break;
                    case 'deactivate':
                        if (isset($_POST['selectedCategoryId'])) {
                            foreach($_POST['selectedCategoryId'] as $intCatId) {
                                $this->activateCategory($intCatId, "deactivate");
                            }
                        }
                    break;
                    default: //do nothing
                }
                $this->overview();
            break;
            default:
                \Permission::checkAccess(65, 'static');
                $this->overview();
                break;
        }
        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->strPageTitle,
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    /**
     * Checks if the file is still there, removes dead links
     *
     * @global    ADONewConnection
     */
    function checkImages()
    {
        global $objDatabase;
        $objResult = $objDatabase->Execute('    SELECT     path,
                                                        id
                                                FROM     '.DBPREFIX.'module_gallery_pictures
                                                WHERE     validated="1"');
        while (!$objResult->EOF) {
            if (!is_file($this->strThumbnailPath.$objResult->fields['path']) ||
                !is_file($this->strImagePath.$objResult->fields['path'])) {
                    $arrayPicToDel[$objResult->fields['id']] = $objResult->fields['path'];
                }
            $objResult->MoveNext();
        }
        if (isset($arrayPicToDel)) {
            foreach ($arrayPicToDel as $id => $path) {
                if (is_file($this->strThumbnailPath.$path)) {
                   @unlink($this->strThumbnailPath.$path);
                }
                if (is_file($this->strImagePath.$path)) {
                    @unlink($this->strImagePath.$path);
                }
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_gallery_pictures WHERE id=".$id);
            }
        }
    }


    /**
     * Shows the overview of the gallery
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @global    integer
     */
    function overview()
    {
        global $objDatabase, $_ARRAYLANG;


        $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'];
        $this->_objTpl->loadTemplateFile('module_gallery_overview.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_DELETE_CATEGORY_MSG'   => $_ARRAYLANG['TXT_GALLERY_DELETE_CATEGORY_MESSAGE'],
            'TXT_DELETE_CATEGORY_ALL'   => $_ARRAYLANG['TXT_GALLERY_DELETE_ALL_CATEGORY_MESSAGE'],
            'TXT_NAME'                  => $_ARRAYLANG['TXT_GALLERY_GALLERYNAME'],
            'TXT_DESC'                  => $_ARRAYLANG['TXT_GALLERY_OVERVIEW_DESCRIPTION'],
            'TXT_IMAGECOUNT'            => $_ARRAYLANG['TXT_IMAGE_COUNT'],
            'TXT_SPACE'                 => $_ARRAYLANG['TXT_GALLERY_SPACE'],
            'TXT_BUTTON_SAVESORT'       => $_ARRAYLANG['TXT_GALLERY_BUTTON_SAVE_SORT'],
            'TXT_IMG_EDIT_ALT'          => $_ARRAYLANG['TXT_EDIT'],
            'TXT_IMG_DEL_ALT'           => $_ARRAYLANG['TXT_DELETE'],
            'TXT_STATUS'                => $_ARRAYLANG['TXT_STATUS'],
            'TXT_ACTION'                => $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_ACTION'],
            'TXT_SELECT_ALL'            => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'          => $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_SUBMIT_SELECT'         => $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_SELECT'],
            'TXT_SUBMIT_DELETE'         => $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_DELETE'],
            'TXT_SUBMIT_ACTIVATE'       => $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_ACTIVATE'],
            'TXT_SUBMIT_DEACTIVATE'     => $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_DEACTIVATE'],
        ));

        $objResult = $objDatabase->Execute('SELECT     id
                       FROM '.DBPREFIX.'module_gallery_categories');
        if ($objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                $arrImageSize[$objResult->fields['id']] = '';
                $arrImageCount[$objResult->fields['id']] = '';
                $objResult->MoveNext();
            }

            foreach (array_keys($arrImageSize) as $intKey) {
                $objResult = $objDatabase->Execute('SELECT     path
                                                   FROM     '.DBPREFIX.'module_gallery_pictures
                                                   WHERE     catid='.$intKey);
                $arrImageCount[$intKey] = $objResult->RecordCount();
                while (!$objResult->EOF) {
                    $arrImageSize[$intKey] = $arrImageSize[$intKey] + filesize($this->strImagePath.$objResult->fields['path']);
                    $objResult->MoveNext();
                }
                $arrImageSize[$intKey] = round($arrImageSize[$intKey] / 1024,2);
            }
        }

        //get the gallery name by current activate lang id
        $arrCategoryName = $this->getCategoryNameByLang();

        $objResult = $objDatabase->Execute('SELECT         id
                                            FROM         '.DBPREFIX.'module_gallery_categories
                                            WHERE         pid=0
                                            ORDER BY     sorting');
        // there are no entries in the database
        if ($objResult->RecordCount() == 0) {
            $this->_objTpl->hideBlock('showCategories');
        } else {
            // there are entries in the database
            while (!$objResult->EOF) {
                $arrMaincats[$objResult->fields['id']] = '';
                $objResult->MoveNext();
            }
            $intRowCounter = 0;
// TODO: Unused
//            $objFWUser = \FWUser::getFWUserObject();
            foreach (array_keys($arrMaincats) as $intMainKey) {
                $objResult = $objDatabase->Execute('SELECT     sorting,
                                                            status, backendProtected, backend_access_id
                                                    FROM     '.DBPREFIX.'module_gallery_categories
                                                            WHERE id='.$intMainKey);

                $intRowColor = ($intRowCounter % 2 == 0) ? 0 : 1;
                $strFolderIcon = ($objResult->fields['status'] == 0) ? 'led_red' : 'led_green';

                if ($objResult->fields['backendProtected']) {
                    try {
                        $allowed = ($this->checkAccess($objResult->fields['backend_access_id'])) ? true : false;
                    } catch (DatabaseError $e) {
                        $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
                        $this->strErrMessage .= $e;
                        return;
                    }
                } else {
                    $allowed = true;
                }

                $this->_objTpl->setVariable(array(
                    'OVERVIEW_ROWCLASS'         => $intRowColor,
                    'OVERVIEW_SUBCATEGORY'      => '',
                    'OVERVIEW_ID'               => $intMainKey,
                    'OVERVIEW_ICON'             => $strFolderIcon,
                    'OVERVIEW_SORTING'          => $objResult->fields['sorting'],
                    'OVERVIEW_NAME'             => ($arrImageCount[$intMainKey]>0) ? '<a href="index.php?cmd=Gallery&amp;act=cat_details&amp;id='.$intMainKey.'" target="_self">'.$arrCategoryName[$intMainKey]['name'].'</a>' :  $arrCategoryName[$intMainKey]['name'],
                    'OVERVIEW_DESCRIPTION'      => $arrCategoryName[$intMainKey]['desc'],
                    'OVERVIEW_COUNT_IMAGES'     => $arrImageCount[$intMainKey],
                    'OVERVIEW_IMAGE_SIZE'       => $arrImageSize[$intMainKey],
                    'OVERVIEW_ACTION_DISPLAY'   => (!$allowed) ? "style=\"display: none;\"" : ""
                ));

                $this->_objTpl->parse('showCategories');
                $intRowCounter++;

                //Showing Subcategories
                $subCategorySpace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                $this->showSubCategories($intMainKey, $arrCategoryName, $arrImageCount, $arrImageSize, $subCategorySpace, $subCategorySpace);
            }
        }
    }

    /**
     * Show the Sub Categories
     *
     * @global ADONewConnection $objDatabase
     * @param integer $parentId
     * @param array $arrCategoryName
     * @param array $arrImageCount
     * @param array $arrImageSize
     * @param string $subCategorySpace
     *
     * @return boolean
     */
    function showSubCategories($parentId, $arrCategoryName, $arrImageCount, $arrImageSize, $subCategorySpace) {
        global $objDatabase;

        if (empty($parentId)) {
            return;
        }

        $objSubCategory = $objDatabase->Execute('SELECT `id`,
                                                        `sorting`,
                                                        `status`
                                                       FROM `'.DBPREFIX.'module_gallery_categories`
                                                        WHERE `pid` = ' . $parentId . '
                                                        ORDER BY `sorting` ASC');
        if ($objSubCategory && $objSubCategory->RecordCount() != 0) {
            // there are subcategories in the database
            $intRowCounter = 0;
            while (!$objSubCategory->EOF) {
                $intRowColor = ($intRowCounter % 2 == 0) ? 0 : 1;
                $strFolderIcon = ($objSubCategory->fields['status'] == 0) ? 'led_red' : 'led_green';

                $this->_objTpl->setVariable(array(
                    'OVERVIEW_ROWCLASS'     =>    $intRowColor,
                    'OVERVIEW_SUBCATEGORY'  =>    $subCategorySpace,
                    'OVERVIEW_ID'           =>    $objSubCategory->fields['id'],
                    'OVERVIEW_ICON'         =>    $strFolderIcon,
                    'OVERVIEW_SORTING'      =>    $objSubCategory->fields['sorting'],
                    'OVERVIEW_NAME'         =>    ($arrImageCount[$parentId]>0) ? '<a href="index.php?cmd=Gallery&amp;act=cat_details&amp;id='.$objSubCategory->fields['id'].'" target="_self">'.$arrCategoryName[$objSubCategory->fields['id']]['name'].'</a>' :  $arrCategoryName[$objSubCategory->fields['id']]['name'],
                    'OVERVIEW_DESCRIPTION'  =>    $arrCategoryName[$objSubCategory->fields['id']]['desc'],
                    'OVERVIEW_COUNT_IMAGES' =>    $arrImageCount[$objSubCategory->fields['id']],
                    'OVERVIEW_IMAGE_SIZE'   =>    $arrImageSize[$objSubCategory->fields['id']]
                ));
                $this->_objTpl->parse('showCategories');
                $intRowCounter++;

                //Showing third level sub categories
                $this->showSubCategories($objSubCategory->fields['id'], $arrCategoryName, $arrImageCount, $arrImageSize, $subCategorySpace . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');

                $objSubCategory->MoveNext();
            }
        }
        return;
    }

    /**
     * Get the Category Name by Language
     *
     * @global ADONewConnection $objDatabase
     *
     * @return boolean|array
     */
    function getCategoryNameByLang() {
        global $objDatabase;

        $objSubResult = $objDatabase->Execute('SELECT `name`, `value`, `gallery_id`
                                                    FROM `' . DBPREFIX . 'module_gallery_language`
                                                        WHERE `lang_id` = ' . $this->intLangId . '
                                                            ORDER BY `name` ASC');
        if ($objSubResult && $objSubResult->RecordCount() > 0) {
            $arrCategoryName = array();
            while (!$objSubResult->EOF) {
                $arrCategoryName[$objSubResult->fields['gallery_id']][$objSubResult->fields['name']] = $objSubResult->fields['value'];
                $objSubResult->MoveNext();
            }
            return $arrCategoryName;
        }
        return false;
    }

    /**
     * Shows the 'Insert new category'-Form
     *
     * @global    ADONewConnection
     * @global    array
     */
    function newCategory()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_NEW_CATEGORY'];
        $this->_objTpl->loadTemplateFile('module_gallery_edit_category.html',true,true);

        $this->_objTpl->setVariable(array(
            'TXT_TITLE'                     =>  $_ARRAYLANG['TXT_GALLERY_MENU_NEW_CATEGORY'],
            'TXT_NAME'                      =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_NAME'],
            'TXT_EXTENDED'                  =>  $_ARRAYLANG['TXT_GALLERY_EXTENDED'],
            'TXT_CATEGORYTYPE'              =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_TYPE'],
            'TXT_CATEGORYTYPE_NEW'          =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_TYPE_NEW'],
            'TXT_CATEGORYTYPE_SUB'          =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_TYPE_SUB'],
            'TXT_DESCRIPTION'               =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_DESCRIPTION'],
            'TXT_STATUS'                    =>  $_ARRAYLANG['TXT_STATUS'],
            'TXT_STATUS_ON'                 =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_ON'],
            'TXT_STATUS_OFF'                =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_OFF'],
            'TXT_COMMENT'                   =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_COMMENT'],
            'TXT_VOTING'                    =>  $_ARRAYLANG['TXT_GALLERY_CATEGORY_VOTING'],
            'TXT_BUTTON_SUBMIT'             =>  $_ARRAYLANG['TXT_GALLERY_BUTTON_SAVE_SORT'],
            'TXT_TAB_FRONTEND_ACCESS'       =>  $_ARRAYLANG['TXT_FRONTEND_ACCESS'],
            'TXT_TAB_GENERAL'               =>  $_ARRAYLANG['TXT_TAB_GENERAL'],
            'TXT_FRONTEND_ACCESS'           =>  $_ARRAYLANG['TXT_FRONTEND_ACCESS'],
            'TXT_PUBLIC_ACCESS'             =>  $_ARRAYLANG['TXT_PUBLIC_ACCESS'],
            'TXT_RESTRICTED_ACCESS'         =>  $_ARRAYLANG['TXT_RESTRICTED_ACCESS'],
            'TXT_TAB_BACKEND_ACCESS'        =>  $_ARRAYLANG['TXT_BACKEND_ACCESS'],
            'TXT_BACKEND_ACCESS'            =>  $_ARRAYLANG['TXT_BACKEND_ACCESS'],
            'TXT_RESTRICTED_ACCESS_BACKEND' =>  $_ARRAYLANG['TXT_RESTRICTED_ACCESS_BACKEND'],
            'TXT_NO_RESTRICTIONS'           =>  $_ARRAYLANG['TXT_NO_RESTRICTIONS']
        ));

        // get the groups for the permission boxes
        $arrExistingFrontendGroups = $this->sql->getAllGroups();
        $existingFrontendGroups = "";
        foreach ($arrExistingFrontendGroups as $id => $name) {
            $existingFrontendGroups .= '<option value="'.$id.'">'.$name."</option>\n";
        }

        $arrExistingBackendGroups = $this->sql->getAllGroups("backend");
        $existingBackendGroups = "";
        foreach ($arrExistingBackendGroups as $id => $name) {
            $existingBackendGroups .= "<option value=\"".$id."\">".$name."</option>\n";
        }

        $this->_objTpl->setVariable(array(
            'VALUE_FRONTEND_EXISTING_GROUPS'    => $existingFrontendGroups,
            'VALUE_BACKEND_EXISTING_GROUPS'     => $existingBackendGroups,
            'FRONTEND_MAPPING_DISPLAY'          => "none",
            'BACKEND_MAPPING_DISPLAY'           => "none",
            'PUBLIC_ACCESS_CHECKED_FRONTEND'    => "checked=\"checked\"",
            'PUBLIC_ACCESS_CHECKED_BACKEND'     => "checked=\"checked\"",
            'VALUE_TYPE_MAIN'                   => 'checked=\"checked\"',
            'VALUE_STATE_OFF'                    => 'checked=\"checked\"',
            'VALUE_COMMENT_OFF'                  => 'checked=\"checked\"',
            'VALUE_VOTING_OFF'                   => 'checked=\"checked\"',
            'FORM_ACT'                          => 'insert_category'
        ));

        $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
        if (!empty($frontendLanguages)) {
            foreach ($frontendLanguages as $frontendLanguage) {
                $this->_objTpl->setVariable(array(
                    'NAMEFIELDS_LID'         =>    $frontendLanguage['id'],
                    'DESCFIELDS_LID'         =>    $frontendLanguage['id'],
                    'NAMEFIELDS_LANGUAGE'    =>    $frontendLanguage['name'],
                    'DESCFIELDS_LANGUAGE'    =>    $frontendLanguage['name']
                ));
                $this->_objTpl->parse('showNameFields');
                $this->_objTpl->parse('showDescFields');
            }
        } else {
            $this->_objTpl->hideBlock('showNameFields');
        }

        // parse the category dropdown
        try {
            $this->parseCategoryDropdown(-1, true, "showCategories", 0, 0, true);
        } catch (DatabaseError $e) {
            $this->_objTpl->hideBlock('showCategories');
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }
    }


    /**
     * Parse a category dropdown recursively. If you want nothing selected, you should
     * pass -1 for the selected value.
     *
     * @param int $selected
     * @param int $disabled
     */
    private function parseCategoryDropdown($selected=-1, $disabled=false, $name="showCategories", $parent_id=0, $level=0, $parseSubCategories = true)
    {
// TODO: Unused
//        $objFWuser = \FWUser::getFWUserObject();
        $categories = $this->sql->getCategoriesArray($this->intLangId, $parent_id);

        if ($disabled) {
            $this->_objTpl->setVariable('CAT_DROPDOWN_DISABLED', ' disabled="disabled"');
        }
        foreach ($categories as $cat) {
            // check if we have access to this category
            if ($cat['backendProtected']) {
                $allowed = $this->checkAccess($cat['backend_access_id']);
            } else {
                $allowed = true;
            }
            if ($allowed) {
                $this->_objTpl->setVariable(array(
                    'CAT_DROPDOWN_VALUE'    => $cat['id'],
                    'CAT_DROPDOWN_NAME'     => $cat['name'],
                    'CAT_DROPDOWN_SELECTED' => ($cat['id'] == $selected ? ' selected="selected"' : ''),
                    'CAT_DROPDOWN_INDENT'   => str_repeat('...', $level)
                ));
            }
            $this->_objTpl->parse($name);
            // parse subcategories when available
            if ($parseSubCategories) {
                $this->parseCategoryDropdown($selected, $disabled, $name, $cat['id'], $level+1, true);
            }
        }
    }


    /**
     * Inserts a new category into the database
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function insertCategory()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_OVERVIEW'];

        $pid = (   isset ($_POST['category_type'])
                && $_POST['category_type'] == "main"
                ? 0
                : (isset ($_POST['select_category_id'])
                    ? intval($_POST['select_category_id']) : 0));
        if ($pid > 0) {
            if (!$this->checkCategoryAccess($pid)) {
                return;
            }
        }

        $status = $_POST['category_status'];
        $comment = $_POST['category_comment'];
        $voting = $_POST['category_voting'];
        $frontendProtected = $_POST['category_protected_frontend'];
        $backendProtected = $_POST['category_protected_backend'];
        $frontend_access_id = $frontendProtected ? ++$_CONFIG['lastAccessId'] : 0;
        $backend_access_id = $backendProtected ? ++$_CONFIG['lastAccessId'] : 0;

        try {
            $galId = $this->sql->insertNewCategory($pid, $status, $comment, $voting, $frontendProtected, $backendProtected, $frontend_access_id, $backend_access_id);
            // set new privileges if wanted
            if ($_POST['category_protected_frontend'] && isset($_POST['assignedFrontendGroups'])) {
                foreach ($_POST['assignedFrontendGroups'] as $group) {
                    $this->sql->insertAccessId($frontend_access_id, $group);
                }
            }

            if ($_POST['category_protected_backend'] && isset($_POST['assignedBackendGroups'])) {
                foreach ($_POST['assignedBackendGroups'] as $group) {
                    $this->sql->insertAccessId($backend_access_id, $group);
                }
            }

            $this->updateAccessId($_CONFIG['lastAccessId']);
        } catch (DatabaseError $e) {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }

        foreach ($_POST as $strKey => $strValue) {
            if (preg_match("/^(category_name_|category_desc_)/", $strKey)) {
                $arrExplode = explode('_',$strKey);
                $arrValues[$arrExplode[2]][$arrExplode[1]] = htmlspecialchars(strip_tags($strValue), ENT_QUOTES, CONTREXX_CHARSET);
            }
        }

        // this stays until more about the database layout is known
        foreach ($arrValues as $intLangId => $arrInner) {
            if (empty($arrInner['name'])) {
                $arrInner['name'] = $_ARRAYLANG['TXT_GALLERY_CATEGORY_NO_NAME'];
            }

            $objDatabase->Execute('    INSERT
                                    INTO    '.DBPREFIX.'module_gallery_language
                                    SET        gallery_id='.$galId.',
                                            lang_id='.$intLangId.',
                                            name="name",
                                            value="'.$arrInner['name'].'"
                                ');
            $objDatabase->Execute('    INSERT
                                    INTO    '.DBPREFIX.'module_gallery_language
                                    SET        gallery_id='.$galId.',
                                            lang_id='.$intLangId.',
                                            name="desc",
                                            value="'.$arrInner['desc'].'"
                                ');
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_MESSAGE_CATEGORY_INSERTED'];
    }


    /**
     * Saves the category sorting
     *
     * @global    ADONewConnection
     * @global    array
     */
    function saveCategorySorting()
    {
        global $objDatabase, $_ARRAYLANG;

        foreach ($_POST as $intKey => $strValue) {
            if (substr($intKey,0,13) == 'sortingSystem') {
                // this POST-Var is sortingSystem-Var
                $intCatId = substr($intKey,13);
                   $objDatabase->Execute('    UPDATE '.DBPREFIX.'module_gallery_categories
                                        SET     sorting='.intval($strValue).'
                                        WHERE     id='.intval($intCatId));
            }
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_SORTING_SAVED' ];
    }


    /**
     * Saves the image sorting
     *
     * @global    ADONewConnection
     * @global    array
     */
    function saveImagesSorting()
    {
        global $objDatabase,$_ARRAYLANG;

        foreach ($_POST as $intKey => $strValue) {
            if (substr($intKey,0,13) == 'sortingSystem') {
                $intImgId = substr($intKey,13);
                $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_pictures
                                        SET     sorting='.intval($strValue).'
                                        WHERE     id='.intval($intImgId));
            }
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_IMAGE_SORTING_SAVED'];
    }


    /**
     * Activates or inactivates a category
     *
     * @global    ADONewConnection
     * @param    integer        $intCategoryId
     */
    function activateCategory($intCategoryId, $act=NULL)
    {
        global $objDatabase;

        if ($act == "activate") {
            $intNewStatus = 1;
        } elseif ($act == "deactivate") {
            $intNewStatus = 0;
        } else {
            $objResult = $objDatabase->Execute('SELECT     status
                                                FROM     '.DBPREFIX.'module_gallery_categories
                                                WHERE     id='.$intCategoryId);

            if ($objResult->fields['status'] == 0) {
                $intNewStatus = 1;
            } else {
                $intNewStatus = 0;
            }
        }

        $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_categories
                                SET     status="'.$intNewStatus.'"
                                WHERE     id='.$intCategoryId);
    }


    /**
     * Delete a Category
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @param    integer $intCategoryId
     */
    function deleteCategory($intCategoryId)
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $intCategoryId = intval($intCategoryId);
        try {
            if (!$this->checkCategoryAccess($intCategoryId)) {
                return;
            }
        } catch (DatabaseError $e) {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }

        $objResult = $objDatabase->Execute('SELECT     id
                                            FROM     '.DBPREFIX.'module_gallery_categories
                                            WHERE     pid='.$intCategoryId);
        while (!$objResult->EOF) {
            $arrCats[$objResult->fields['id']] = $objResult->fields['id'];
            $objResult->MoveNext();
        }
        $arrCats[$intCategoryId] = $intCategoryId;

        foreach ($arrCats as $strValue) {
            $objResult = $objDatabase->Execute('SELECT     id,
                                                        path
                                                FROM     '.DBPREFIX.'module_gallery_pictures
                                                WHERE     catid='.$strValue);
            while (!$objResult->EOF) {
                @unlink($this->strThumbnailPath.$objResult->fields['path']);
                @unlink($this->strImagePath.$objResult->fields['path']);

                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_gallery_votes
                                        WHERE    picid='.$objResult->fields['id'].'
                                    ');
                $objDatabase->Execute('    DELETE
                                        FROM    '.DBPREFIX.'module_gallery_comments
                                        WHERE    picid='.$objResult->fields['id'].'
                                    ');
                $objResult->MoveNext();
            }

            $objDatabase->Execute('    DELETE
                                    FROM     '.DBPREFIX.'module_gallery_pictures
                                    WHERE     catid='.$strValue);

            $objDatabase->Execute('    DELETE
                                    FROM     '.DBPREFIX.'module_gallery_categories
                                    WHERE     id='.$strValue);

            $objDatabase->Execute('    DELETE
                                    FROM    '.DBPREFIX.'module_gallery_language
                                    WHERE    gallery_id='.$strValue.'
                                ');
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_CATEGORY_DELETED'];
    }


    /**
     * Shows the "Edit-Category"-Form
     *
     * @global    ADONewConnection
     * @global    array
     * @param    integer        $intCategoryId
     */
    function editCategory($intCategoryId)
    {
        global $objDatabase, $_ARRAYLANG;

        // check access
        try {
            if (!$this->checkCategoryAccess($intCategoryId)) {
                $this->overview();
                return;
            }
        } catch (DatabaseError $e) {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }
        $this->_objTpl->loadTemplateFile('module_gallery_edit_category.html',true,true);
        $this->_objTpl->setVariable(array(
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_GALLERY_MENU_EDIT_CATEGORY'],
            'TXT_NAME'                      => $_ARRAYLANG['TXT_GALLERY_CATEGORY_NAME'],
            'TXT_EXTENDED'                  => $_ARRAYLANG['TXT_GALLERY_EXTENDED'],
            'TXT_CATEGORYTYPE'              => $_ARRAYLANG['TXT_GALLERY_CATEGORY_TYPE'],
            'TXT_CATEGORYTYPE_NEW'          => $_ARRAYLANG['TXT_GALLERY_CATEGORY_TYPE_NEW'],
            'TXT_CATEGORYTYPE_SUB'          => $_ARRAYLANG['TXT_GALLERY_CATEGORY_TYPE_SUB'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_GALLERY_CATEGORY_DESCRIPTION'],
            'TXT_STATUS'                    => $_ARRAYLANG['TXT_STATUS'],
            'TXT_STATUS_ON'                 => $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_ON'],
            'TXT_STATUS_OFF'                => $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_OFF'],
            'TXT_COMMENT'                   => $_ARRAYLANG['TXT_GALLERY_CATEGORY_COMMENT'],
            'TXT_VOTING'                    => $_ARRAYLANG['TXT_GALLERY_CATEGORY_VOTING'],
            'TXT_BUTTON_SUBMIT'             => $_ARRAYLANG['TXT_GALLERY_BUTTON_SAVE_CATEGORY'],
            'TXT_TAB_FRONTEND_ACCESS'       => $_ARRAYLANG['TXT_FRONTEND_ACCESS'],
            'TXT_TAB_GENERAL'               => $_ARRAYLANG['TXT_TAB_GENERAL'],
            'TXT_FRONTEND_ACCESS'           => $_ARRAYLANG['TXT_FRONTEND_ACCESS'],
            'TXT_PUBLIC_ACCESS'             => $_ARRAYLANG['TXT_PUBLIC_ACCESS'],
            'TXT_RESTRICTED_ACCESS'         => $_ARRAYLANG['TXT_RESTRICTED_ACCESS'],
            'FORM_ACT'                      => 'update_category',
            'TXT_TAB_BACKEND_ACCESS'        => $_ARRAYLANG['TXT_BACKEND_ACCESS'],
            'TXT_BACKEND_ACCESS'            => $_ARRAYLANG['TXT_BACKEND_ACCESS'],
            'TXT_RESTRICTED_ACCESS_BACKEND' => $_ARRAYLANG['TXT_RESTRICTED_ACCESS_BACKEND'],
            'TXT_NO_RESTRICTIONS'           => $_ARRAYLANG['TXT_NO_RESTRICTIONS']
        ));

        $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
        if (!empty($frontendLanguages)) {
            foreach ($frontendLanguages as $frontendLanguage) {
                $objSubResult = $objDatabase->Execute('    SELECT        name,
                                                                    value
                                                        FROM        '.DBPREFIX.'module_gallery_language
                                                        WHERE        gallery_id='.intval($intCategoryId).' AND
                                                                    lang_id='.$frontendLanguage['id'].'
                                                        ORDER BY    name ASC
                                                    ');
                unset($arrCategoryLang);
                while (!$objSubResult->EOF) {
                    $arrCategoryLang[$objSubResult->fields['name']] = $objSubResult->fields['value'];
                    $objSubResult->MoveNext();
                }

                $this->_objTpl->setVariable(array(
                    'NAMEFIELDS_LID'        =>    $frontendLanguage['id'],
                    'DESCFIELDS_LID'        =>    $frontendLanguage['id'],
                    'NAMEFIELDS_LANGUAGE'    =>    $frontendLanguage['name'],
                    'DESCFIELDS_LANGUAGE'    =>    $frontendLanguage['name'],
                    'NAMEFIELDS_VALUE'        =>    $arrCategoryLang['name'],
                    'DESCFIELDS_VALUE'        =>    $arrCategoryLang['desc']
                ));

                $this->_objTpl->parse('showNameFields');
                $this->_objTpl->parse('showDescFields');

                if ($frontendLanguage['is_default'] == 'true') {
                    $strNameDefault = $arrCategoryLang['name'];
                    $strDescDefault = $arrCategoryLang['desc'];
                }
            }
        } else {
            $this->_objTpl->hideBlock('showNameFields');
        }

        try {
            list($existingFrontendGroups, $assignedFrontendGroups) = $this->getGroupLists($intCategoryId, 'frontend');
            list($existingBackendGroups, $assignedBackendGroups) = $this->getGroupLists($intCategoryId, 'backend');
        } catch (DatabaseError $e) {
            echo $e;
        }

        $objResult = $objDatabase->Execute('SELECT     pid,
                                                    status,
                                                    comment,
                                                    voting,
                                                    frontendProtected,
                                                    backendProtected
                                            FROM     '.DBPREFIX.'module_gallery_categories
                                            WHERE     id='.$intCategoryId);

        $this->_objTpl->setVariable(array(
            'VALUE_ID'                              => $intCategoryId,
            'VALUE_NAME'                            => $strNameDefault,
            'VALUE_DESC'                            => $strDescDefault,
            'VALUE_FRONTEND_EXISTING_GROUPS'        => $existingFrontendGroups,
            'VALUE_FRONTEND_ASSIGNED_GROUPS'        => $assignedFrontendGroups,
            'VALUE_BACKEND_EXISTING_GROUPS'         => $existingBackendGroups,
            'VALUE_BACKEND_ASSIGNED_GROUPS'         => $assignedBackendGroups,
            'FRONTEND_MAPPING_DISPLAY'              => ($objResult->fields['frontendProtected']) ? "block" : "none",
            'BACKEND_MAPPING_DISPLAY'               => ($objResult->fields['backendProtected']) ? "block" : "none",
            'PUBLIC_ACCESS_CHECKED_FRONTEND'        => ($objResult->fields['frontendProtected']) ? "" : "checked=\"checked\"",
            'PUBLIC_ACCESS_CHECKED_BACKEND'         => ($objResult->fields['backendProtected']) ? "" : "checked=\"checked\"",
            'RESTRICTED_ACCESS_CHECKED_FRONTEND'    => ($objResult->fields['frontendProtected']) ? "checked=\"checked\"" : "",
            'RESTRICTED_ACCESS_CHECKED_BACKEND'     => ($objResult->fields['backendProtected']) ? "checked=\"checked\"" : ""
        ));

        $pid = $objResult->fields['pid'];
        if ($objResult->fields['pid'] == 0) {
              $this->_objTpl->setVariable(array(
                'VALUE_TYPE_MAIN'            =>    'checked',
                'VALUE_TYPE_SUB'            =>    '',
                'VALUE_MAINCAT_SELECTION'    =>     'disabled'
            ));
        } else {
              $this->_objTpl->setVariable(array(
                'VALUE_TYPE_MAIN'            =>    '',
                'VALUE_TYPE_SUB'            =>    'checked',
                'VALUE_MAINCAT_SELECTION'    =>     ''
            ));
        }
        if ($objResult->fields['status'] == 0) {
             $this->_objTpl->setVariable(array(
                'VALUE_STATE_ON'    =>    '',
                'VALUE_STATE_OFF'    =>    'checked'
            ));
        } else {
             $this->_objTpl->setVariable(array(
                'VALUE_STATE_ON'    =>    'checked',
                'VALUE_STATE_OFF'    =>    ''
            ));
        }
        if ($objResult->fields['comment'] == 0) {
             $this->_objTpl->setVariable(array(
                'VALUE_COMMENT_ON'    =>    '',
                'VALUE_COMMENT_OFF'    =>    'checked'
            ));
        } else {
             $this->_objTpl->setVariable(array(
                'VALUE_COMMENT_ON'    =>    'checked',
                'VALUE_COMMENT_OFF'    =>    ''
            ));
        }
        if ($objResult->fields['voting'] == 0) {
             $this->_objTpl->setVariable(array(
                'VALUE_VOTING_ON'    =>    '',
                'VALUE_VOTING_OFF'    =>    'checked'
            ));
        } else {
             $this->_objTpl->setVariable(array(
                'VALUE_VOTING_ON'    =>    'checked',
                'VALUE_VOTING_OFF'    =>    ''
            ));
        }
        $intCategoryPid = $objResult->fields['pid'];

        $objResult = $objDatabase->Execute('SELECT         id
                                            FROM         '.DBPREFIX.'module_gallery_categories
                                            WHERE         pid=0 AND
                                                        id != '.$intCategoryId.'
                                            ORDER BY     sorting ASC');
        if ($objResult->RecordCount() == 0) { // no rows
            $this->_objTpl->hideBlock('showCategories');
        } else {
            $objFWUser = \FWUser::getFWUserObject();
            while (!$objResult->EOF) {
                $objSubResult = $objDatabase->Execute('    SELECT        value
                                                        FROM        '.DBPREFIX.'module_gallery_language
                                                        WHERE        gallery_id='.$objResult->fields['id'].' AND
                                                                    lang_id='.$this->intLangId.' AND
                                                                    name="name"
                                                    ');
                $this->_objTpl->setVariable(array(
                        'NEW_CATEGORY_ID'    =>    $objResult->fields['id'],
                        'NEW_CATEGORY_NAME'    =>    $objSubResult->fields['value']
                ));

                if ($objResult->fields['id'] == $intCategoryPid) {
                    $this->_objTpl->setVariable('VALUE_SELECTED','selected');
                } else {
                    $this->_objTpl->setVariable('VALUE_SELECTED','');
                }

                $this->_objTpl->parse('showCategories');
                $objResult->MoveNext();
            }
        }

        try {
            $this->parseCategoryDropdown($intCategoryPid, ($pid == 0) ? true : false,"showCategories",0,0, true);
        } catch (DatabaseError $e) {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }
    }


    /**
     * Updates an category in the database
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @param    integer        $intCategoryId
     */
    function updateCategory($categoryId)
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $categoryId = intval($categoryId);
        foreach ($_POST as $strKey => $strValue) {
            if (preg_match('/^(category\_name\_|category\_desc\_)/', $strKey)) {
                $arrExplode = explode('_',$strKey);
                $arrValues[$arrExplode[2]][$arrExplode[1]] = htmlspecialchars(strip_tags($strValue), ENT_QUOTES, CONTREXX_CHARSET);
            }
        }

        if (!isset($_POST['category_status'])) {
            return false;
        }
        $insertStatus = $_POST['category_status'];
        $insertComment = $_POST['category_comment'];
        $insertVoting = $_POST['category_voting'];
        $insertVoting = $_POST['category_voting'];
        $insertFrontendProtected = $_POST['category_protected_frontend'];
        $insertBackendProtected = $_POST['category_protected_backend'];

        try {
            // check access
            $insertPid =
                (   isset ($_POST['category_type'])
                 && $_POST['category_type'] == 'main'
                    ? 0
                    : (isset ($_POST['select_category_id'])
                        ? intval($_POST['select_category_id']) : 0));
            if ($insertPid > 0) {
                if (!$this->checkCategoryAccess($insertPid)) {
                    return;
                }
            }

            list($frontend_access_id) = $this->sql->getPrivileges($categoryId, "frontend");
            list($backend_access_id) = $this->sql->getPrivileges($categoryId, "backend");

            // set privileges
            if ($insertFrontendProtected) {
                if (empty($frontend_access_id)) {
                    $frontend_access_id = ++$_CONFIG['lastAccessId'];
                    $this->updateAccessId($_CONFIG['lastAccessId']);
                } else {
                    $this->sql->deleteAccessIds($frontend_access_id);
                }
                if (isset($_POST['assignedFrontendGroups'])) {
                    foreach ($_POST['assignedFrontendGroups'] as $group) {
                        $this->sql->insertAccessId($frontend_access_id, $group);
                    }
                }
            } else {
                if (!empty($frontend_access_id)) {
                    $this->sql->deleteAccessIds($frontend_access_id);
                    $frontend_access_id = 0;
                }
            }

            if ($insertBackendProtected) {
                if (empty($backend_access_id)) {
                    $backend_access_id = ++$_CONFIG['lastAccessId'];
                    $this->updateAccessId($_CONFIG['lastAccessId']);
                } else {
                    $this->sql->deleteAccessIds($backend_access_id);
                }
                if (isset($_POST['assignedBackendGroups'])) {
                    foreach ($_POST['assignedBackendGroups'] as $group) {
                        $this->sql->insertAccessId($backend_access_id, $group);
                    }
                }
            } else {
                if (!empty($backend_access_id)) {
                    $this->sql->deleteAccessIds($backend_access_id);
                    $backend_access_id = 0;
                }
            }

            // Update the category
            $this->sql->updateCategory($categoryId, $insertPid, $insertStatus, $insertComment, $insertVoting, $insertFrontendProtected, $insertBackendProtected, $frontend_access_id, $backend_access_id);

            /*foreach ($arrValues as $langId => $values) {
                if (empty($arrInner['name'])) {
                    // set standard category name if none is given
                    $arrInner['name'] = $_ARRAYLANG['TXT_GALLERY_CATEGORY_NO_NAME'];
                }
            }*/

        } catch (DatabaseError $e) {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }

        // this stays until further information about the database layout is known
        foreach ($arrValues as $intLangId => $arrInner)
        {
            if (empty($arrInner['name'])) {
                $arrInner['name'] = $_ARRAYLANG['TXT_GALLERY_CATEGORY_NO_NAME'];
            }

            $objDatabase->Execute(' UPDATE    '.DBPREFIX.'module_gallery_language
                                    SET       value="'.$arrInner['name'].'"
                                    WHERE     gallery_id='.intval($categoryId).' AND
                                              lang_id='.$intLangId.' AND
                                              name="name"
                                    LIMIT     1
                                ');

            $objDatabase->Execute(' UPDATE    '.DBPREFIX.'module_gallery_language
                                    SET       value="'.$arrInner['desc'].'"
                                    WHERE     gallery_id='.intval($categoryId).' AND
                                              lang_id='.$intLangId.' AND
                                              name="desc"
                                    LIMIT     1
                                ');
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_CATEGORY_UPDATED'];
    }


    /**
     * Shows the category-details-page
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function showCategoryDetails($intCatId)
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        try {
            if (!$this->checkCategoryAccess($intCatId)) {
                $this->overview();
                return;
            }
        } catch (DatabaseError $e) {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }

        \JS::activate('shadowbox');
        $objFWUser = \FWUser::getFWUserObject();

        $this->_objTpl->loadTemplateFile('module_gallery_category_details.html', true, true);
        $this->_objTpl->setGlobalVariable(array(
            'TXT_TITLE_NAME'                =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_NAME'],
            'TXT_TITLE_ORDER'               =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_ORDER'],
            'TXT_TITLE_ACTION'              =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_ACTION'],
            'TXT_TITLE_ATTR'                =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_ATTR'],
            'TXT_TITLE_OTHER'               =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_OTHER'],
            'TXT_TITLE_CATEGORY'            =>    $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_ACTIVATE_PICTURE'          =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_DE_ACTIVATE'],
            'TXT_SET_CATIMG'                =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SET_CATIMG'],
            'TXT_RESET_PICTURE'             =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_RESET_PICTURE_MSG'],
            'TXT_DELETE_PICTURE'            =>    $_ARRAYLANG['TXT_GALLERY_DELETE_IMAGE_MSG'],
            'TXT_DELETE_PICTURE_ALL'        =>    $_ARRAYLANG['TXT_GALLERY_DELETE_IMAGE_ALL_MSG'],
            'TXT_IMG_ROTATE_ALT'            =>    $_ARRAYLANG['TXT_GALLERY_ROTATE'],
            'TXT_IMG_RESET_ALT'             =>    $_ARRAYLANG['TXT_RESET'],
            'TXT_IMG_EDIT_ALT'              =>    $_ARRAYLANG['TXT_EDIT'],
            'TXT_IMG_DELETE_ALT'            =>    $_ARRAYLANG['TXT_DELETE'],
            'TXT_BUTTON_SAVESORT'           =>    $_ARRAYLANG['TXT_GALLERY_BUTTON_SAVE_SORT'],
            'TXT_ORIG_CAPT'                 =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_ORIG_CAPT'],
            'TXT_THUMB_CAPT'                =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_THUMB_CAPT'],
            'TXT_THUMB_QUALITY_CAPT'        =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_THUMB_QUALITY_CAPT'],
            'TXT_SELECT_ALL'                =>    $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'              =>    $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_SUBMIT_SELECT'             =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_SELECT'],
            'TXT_SUBMIT_DELETE'             =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_DELETE'],
            'TXT_SUBMIT_ACTIVATE'           =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_ACTIVATE'],
            'TXT_SUBMIT_DEACTIVATE'         =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_DEACTIVATE'],
            'TXT_SUBMIT_RESET'              =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_RESET'],
            'TXT_SUBMIT_MOVE'               =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_MOVE'],
        ));

        $objResult = $objDatabase->Execute('SELECT     value
                                            FROM     '.DBPREFIX.'module_gallery_language
                                            WHERE     gallery_id='.intval($intCatId).' AND
                                                    lang_id='.$this->intLangId.' AND
                                                    name="desc"
                                        ');
        $strCategoryComment = $objResult->fields['value'];

        $objResult = $objDatabase->Execute('SELECT     comment,
                                                    voting
                                            FROM     '.DBPREFIX.'module_gallery_categories
                                            WHERE     id='.$intCatId);
        $boolComment = $objResult->fields['comment'];
        $boolVoting = $objResult->fields['voting'];

        $this->_objTpl->setGlobalVariable(array(
            'CATEGORY_NAME'    => $strCategoryComment,
            'CATEGORY_ID'    => $intCatId
        ));

        $selectQuery = 'FROM         '.DBPREFIX.'module_gallery_pictures
                                            WHERE         catid='.$intCatId.' AND
                                                        validated="1"
                                            ORDER BY     sorting ASC,
                                                        id ASC';


        $objCount = $objDatabase->SelectLimit('SELECT count(id) AS picCount '.$selectQuery, 1);
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        if ($objCount !== false && $objCount->fields['picCount'] > $_CONFIG['corePagingLimit']) {
            $this->_objTpl->setVariable('GALLERY_PAGING', '<br />'.getPaging($objCount->fields['picCount'], $pos, '&cmd=Gallery&act=cat_details&id='.$intCatId, 'bilder'));
        }
        $objResult = $objDatabase->SelectLimit('SELECT         id '.$selectQuery, $_CONFIG['corePagingLimit'], $pos);

        if ($objResult->RecordCount() == 0) {
            $this->_objTpl->hideBlock('showImages');
        } else {
            while (!$objResult->EOF) {
                $arrImages[$objResult->fields['id']] = $objResult->fields['id'];
                $objResult->MoveNext();
            }

            $intRowCounter = 0;
            $this->_objTpl->setCurrentBlock('showImages');
            foreach ($arrImages as $strValue) {
                $objResult = $objDatabase->Execute('SELECT     *
                                                    FROM     '.DBPREFIX.'module_gallery_pictures
                                                    WHERE     id='.$strValue);
                $objSubResult = $objDatabase->Execute('    SELECT    name
                                                        FROM    '.DBPREFIX.'module_gallery_language_pics
                                                        WHERE    picture_id='.$objResult->fields['id'].' AND
                                                                lang_id='.FRONTEND_LANG_ID.'
                                                        LIMIT    1
                                                    ');

                if ($objResult->fields['catimg'] == '1') {
                    $this->_objTpl->setVariable('IMAGES_ROWCLASS','rowWarn');
                } else {
                    if ($intRowCounter % 2 == 0) {
                        $this->_objTpl->setVariable('IMAGES_ROWCLASS','row0');
                    } else {
                        $this->_objTpl->setVariable('IMAGES_ROWCLASS','row1');
                    }
                }
                ++$intRowCounter;


                if ($objResult->fields['status'] == '0') {
                    $outputActiveIcon = 'led_red.gif';
                } else {
                    $outputActiveIcon = 'led_green.gif';
                }

                if ($objResult->fields['catimg'] == '0') {
                    $outputCatimgIcon = 'preview_grey.gif';
                } else {
                    $outputCatimgIcon = 'preview.gif';
                }

                if ($objResult->fields['link'] == '') {
                    // the linkfield is empty
                    $outputLinkIconS = '<!--';
                    $outputLinkIconE = '-->';
                } else {
                    $outputLinkIconS = '';
                    $outputLinkIconE = '';
                }

                $intOutputId = $objResult->fields['id'];
                $strOutputSorting = $objResult->fields['sorting'];

                $arrOrigFileInfo        = getimagesize($this->strImagePath.$objResult->fields['path']);
                $arrThumbFileInfo       = getimagesize($this->strThumbnailPath.$objResult->fields['path']);

                $strOutputThumbpath     = $this->strThumbnailWebPath.$objResult->fields['path'];
                $strOutputOrigpath      = $this->strImageWebPath.$objResult->fields['path'];
                $strOutputName          = $objSubResult->fields['name'];
                $strOutputLastedit      = date('d.m.Y',$objResult->fields['lastedit']);
                $strOutputOrigReso      = $arrOrigFileInfo[0].'x'.$arrOrigFileInfo[1];
                $strOutputOrigWidth     = $arrOrigFileInfo[0]+20;
                $strOutputOrigHeight    = $arrOrigFileInfo[1]+25;
                $strOutputOrigSize      = round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2);
                $strOutputThumbReso     = $arrThumbFileInfo[0].'x'.$arrThumbFileInfo[1];
                $strOutputThumbSize     = round(filesize($this->strThumbnailPath.$objResult->fields['path'])/1024,2);

                if ($objResult->fields['size_type'] == 'abs') {
                    $strOutputTypeMethod = $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_ABS'];
                    $strOutputTypeSize = $objResult->fields['quality'].'%';
                } else {
                    $strOutputTypeMethod = $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_PER'].'&nbsp;:'.$objResult->fields['size_proz'];
                    $strOutputTypeSize = $objResult->fields['quality'].'%';
                }

                if ($this->arrSettings['show_comments'] == 'on' && $boolComment) {
                    $objSubResult = $objDatabase->Execute('    SELECT    id
                                                            FROM    '.DBPREFIX.'module_gallery_comments
                                                            WHERE    picid='.$objResult->fields['id'].'
                                                        ');
                    $strOutputCommentCount = $objSubResult->RecordCount().' '.$_ARRAYLANG['TXT_GALLERY_COMMENTS'].'<br />';
                } else {
                    // show nothing
                    $strOutputCommentCount = "";
                }

                if ($this->arrSettings['show_voting'] == 'on' && $boolVoting) {
                    $objSubResult = $objDatabase->Execute('    SELECT    mark
                                                            FROM    '.DBPREFIX.'module_gallery_votes
                                                            WHERE    picid='.$objResult->fields['id'].'
                                                        ');
                    $strOutputVotingCount = $objSubResult->RecordCount().' '.$_ARRAYLANG['TXT_GALLERY_RATING'];
                    if ($strOutputVotingCount > 0) {
                            $intMark = 0;
                        while (!$objSubResult->EOF) {
                            $intMark = $intMark + $objSubResult->fields['mark'];
                            $objSubResult->MoveNext();
                        }
                        $outputVotingAverage = ', &Oslash; '.number_format(round($intMark / $strOutputVotingCount,1),1,'.','\'');
                    } else {
                        $outputVotingAverage = ', &Oslash; 0.0';
                    }
                } else {
                    // show nothing
                    $strOutputVotingCount = "";
                    $outputVotingAverage = "";
                }

                // parse the dropdown for the categories
                try {
                    $this->parseCategoryDropdown($intCatId, false);
                } catch (DatabaseError $e) {
                    $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
                    $this->strErrMessage .= $e;
                    return;
                }

                // remove last part (file name ending) if any
                $imageNameParts = explode('.', $strOutputName);
                if (count($imageNameParts) > 1) {
                    end($imageNameParts);
                    unset($imageNameParts[key($imageNameParts)]);
                    $strOutputName = implode('.', $imageNameParts);
                }

                $this->_objTpl->setVariable(array(
                    'IMAGES_ID'                     =>    contrexx_raw2xhtml($intOutputId),
                    'IMAGES_THUMB_PATH'             =>    contrexx_raw2encodedUrl($strOutputThumbpath),
                    'IMAGE_ORIG_PATH'               =>    contrexx_raw2encodedUrl($strOutputOrigpath),
                    'IMAGES_ACTIVE_ICON'            =>    contrexx_raw2xhtml($outputActiveIcon),
                    'IMAGES_CATIMG_ICON'            =>    contrexx_raw2xhtml($outputCatimgIcon),
                    'IMAGES_HIDE_LINKICON_S'        =>    $outputLinkIconS,
                    'IMAGES_HIDE_LINKICON_E'        =>    $outputLinkIconE,
                    'IMAGES_NAME'                   =>    contrexx_raw2xhtml($strOutputName),
                    'IMAGES_LASTEDIT'               =>    contrexx_raw2xhtml($strOutputLastedit),
                    'IMAGES_ORIG_RESO'              =>    contrexx_raw2xhtml($strOutputOrigReso),
                    'IMAGES_ORIG_WIDTH'             =>    contrexx_raw2xhtml($strOutputOrigWidth),
                    'IMAGES_ORIG_HEIGHT'            =>    contrexx_raw2xhtml($strOutputOrigHeight),
                    'IMAGES_ORIG_SIZE'              =>    contrexx_raw2xhtml($strOutputOrigSize),
                    'IMAGES_THUMB_RESO'             =>    contrexx_raw2xhtml($strOutputThumbReso),
                    'IMAGES_THUMB_SIZE'             =>    contrexx_raw2xhtml($strOutputThumbSize),
                    'IMAGES_TYPE_METHOD'            =>    contrexx_raw2xhtml($strOutputTypeMethod),
                    'IMAGES_TYPE_SIZE'              =>    contrexx_raw2xhtml($strOutputTypeSize),
                    'IMAGES_SORTING'                =>    contrexx_raw2xhtml($strOutputSorting),
                    'IMAGES_COMMENT_COUNT'          =>    contrexx_raw2xhtml($strOutputCommentCount),
                    'IMAGES_VOTING_COUNT'           =>    contrexx_raw2xhtml($strOutputVotingCount),
                    'IMAGES_VOTING_AVERAGE'         =>    contrexx_raw2xhtml($outputVotingAverage),
                    'CLEAR_IMAGE_CATCH'             =>    time(),
                ));
                $this->_objTpl->parseCurrentBlock();
            }
            $this->parseCategoryDropdown($intCatId, false, 'showCategoriesMultiAction');
        }
    }


    /**
     * Activate / Inactivate a picture
     *
     * @global    ADONewConnection
     * @param    integer        $intImageId
     */
    function activatePicture($intImageId, $act=NULL)
    {
        global $objDatabase;

        $intImageId = intval($intImageId);

        if ($act == "activate") {
            $intNewStatus = 1;
        } elseif ($act == "deactivate") {
            $intNewStatus = 0;
        } else {
            $objResult = $objDatabase->Execute('SELECT     status
                                                FROM     '.DBPREFIX.'module_gallery_pictures
                                                WHERE     id='.$intImageId);
            if ($objResult->fields['status'] == '0') {
                $intNewStatus = 1;
            } else {
                $intNewStatus = 0;
            }
        }

        $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_pictures
                                SET     status="'.$intNewStatus.'"
                                WHERE     id='.$intImageId);
    }


    /**
     * Reset the picture and move it back to the validation part
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @param    integer        $intImageId
     */
    function resetPicture($intImageId)
    {
        global $objDatabase,$_CONFIG,$_ARRAYLANG;

        $intImageId = intval($intImageId);

        $objResult = $objDatabase->Execute('SELECT     path
                                            FROM     '.DBPREFIX.'module_gallery_pictures
                                            WHERE     id='.$intImageId);

        unlink($this->strThumbnailPath.$objResult->fields['path']);
         $objDatabase->Execute('    UPDATE '.DBPREFIX.'module_gallery_pictures
                                SET     validated="0",
                                        lastedit='.time().',
                                        catid=0
                                WHERE     id='.$intImageId);

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_gallery_votes
                                WHERE    picid='.$intImageId.'
                            ');
        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_gallery_comments
                                WHERE    picid='.$intImageId.'
                            ');

        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_PICTURE_RESET'];
    }


    /**
     * Changes the Category of an Image
     *
     * @global    ADONewConnection
     * @global    array
     * @param    integer        $intImageId
     * @param    integer        $intNewCatId
     */
    function changeCategoryOfPicture($intImageId, $intNewCatId)
    {
        global $objDatabase,$_ARRAYLANG;

        // check if the user is allowed to move a picture to this category
        try {
            $id = $this->sql->getPictureCategory($intImageId);
            if (!$this->checkCategoryAccess($intNewCatId)) {
                $_GET['catid'] = $id;
                return;
            }
        } catch (DatabaseError $e) {
            echo "error";
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }

        $intImageId = intval($intImageId);
        $intNewCatId = intval($intNewCatId);

        $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_gallery_pictures
                                SET     catid='.$intNewCatId.',
                                        lastedit='.time().'
                                WHERE   id='.$intImageId);
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_PICTURE_CATEGORY_CHANGED'];
    }


    /**
     * Shows the edit-form for a picture
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function showEditPicture()
    {
        global $objDatabase,$_ARRAYLANG,$_CONFIG;

        $intPid = intval($_GET['id']);
        $objFWUser = \FWUser::getFWUserObject();

        $this->_objTpl->loadTemplateFile('module_gallery_edit_image.html',true,true);
        $this->_objTpl->setVariable(array(
            'TXT_TAB_SETTINGS'            =>    $_ARRAYLANG['TXT_GALLERY_MENU_PICTURE_DETAILS'],
            'TXT_TAB_COMMENTS'            =>    $_ARRAYLANG['TXT_TAB_COMMENTS'],
            'TXT_TAB_VOTES'                =>    $_ARRAYLANG['TXT_TAB_VOTES'],
            'TXT_TITLE'                    =>    $_ARRAYLANG['TXT_GALLERY_MENU_PICTURE_DETAILS'],
            'TXT_IMAGENAME'                =>    $_ARRAYLANG['TXT_IMAGE_NAME'],
            'TXT_IMAGELINKNAME'            =>    $_ARRAYLANG['TXT_IMAGE_LINK_NAME'],
            'TXT_LINKNAME_HELP'            =>    $_ARRAYLANG['TXT_IMAGE_LINK_HELP'],
            'TXT_IMAGELINK'                =>    $_ARRAYLANG['TXT_IMAGE_LINK'],
            'TXT_SIZESHOW'                =>    $_ARRAYLANG['TXT_IMAGE_SIZE_SHOW'],
            'TXT_BUTTON_SUBMIT'            =>    $_ARRAYLANG['TXT_GALLERY_BUTTON_SAVE_CATEGORY'],
            'TXT_COMMENT_TITLE'            =>    $_ARRAYLANG['TXT_COMMENT_SHOW_TITLE'],
            'TXT_COMMENT_EDIT'            =>    $_ARRAYLANG['TXT_COMMENT_EDIT'],
            'TXT_COMMENT_DELETE'        =>    $_ARRAYLANG['TXT_COMMENT_DELETE'],
            'TXT_COMMENT_DELETE_JS'        =>    $_ARRAYLANG['TXT_COMMENT_DELETE_JS'],
            'TXT_COMMENT_SELECT_ALL'    =>    $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_COMMENT_DESELECT_ALL'    =>    $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_COMMENT_SELECT_ACT'    =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_SELECT'],
            'TXT_COMMENT_DELETE_ALL'    =>    $_ARRAYLANG['TXT_GALLERY_CAT_DETAILS_SUBMIT_DELETE'],
            'TXT_VOTING_TITLE'            =>    $_ARRAYLANG['TXT_GALLERY_RATING'],
            'TXT_VOTING_DELETE_JS'        =>    $_ARRAYLANG['TXT_VOTING_DELETE_JS'],
            'TXT_COMMENT_DELETE_ALL_JS'    =>    $_ARRAYLANG['TXT_VOTING_DELETE_ALL_JS'],
            'TXT_EXTENDED'                =>    $_ARRAYLANG['TXT_GALLERY_EXTENDED']
        ));

        $objResult = $objDatabase->Execute('    SELECT        '.DBPREFIX.'module_gallery_categories.comment,
                                                            '.DBPREFIX.'module_gallery_categories.voting,
                                                            '.DBPREFIX.'module_gallery_pictures.id AS pID
                                                FROM        '.DBPREFIX.'module_gallery_pictures
                                                LEFT JOIN    '.DBPREFIX.'module_gallery_categories
                                                ON            '.DBPREFIX.'module_gallery_pictures.catid='.DBPREFIX.'module_gallery_categories.id
                                                HAVING        pID='.$intPid.'
                                                LIMIT        1
                                            ');
        $boolComment     = $objResult->fields['comment'];
        $boolVoting        = $objResult->fields['voting'];

        if ($this->arrSettings['show_comments'] == 'off' || $boolComment == 0) {
            $this->_objTpl->hideBlock('tabComment');
            if ($_GET['active'] == 'comment') {
                $_GET['active'] = 'default';
            }
        }

        if ($this->arrSettings['show_voting'] == 'off' || $boolVoting == 0) {
            $this->_objTpl->hideBlock('tabVoting');
            if ($_GET['active'] == 'voting') {
                $_GET['active'] = 'default';
            }
        }

        switch ($_GET['active']) {
            case 'comment':
                $this->_objTpl->SetVariable(array(    'TAB_ACTIVE_SETTINGS'    =>    '',
                                                    'TAB_ACTIVE_COMMENTS'    =>    'active',
                                                    'TAB_ACTIVE_VOTES'        =>    '',
                                                    'TAB_DISPLAY_SETTINGS'    =>    'none',
                                                    'TAB_DISPLAY_COMMENTS'    =>    'block',
                                                    'TAB_DISPLAY_VOTES'        =>    'none'
                                            ));
            break;
            case 'voting':
                $this->_objTpl->SetVariable(array(    'TAB_ACTIVE_SETTINGS'    =>    '',
                                                    'TAB_ACTIVE_COMMENTS'    =>    '',
                                                    'TAB_ACTIVE_VOTES'        =>    'active',
                                                    'TAB_DISPLAY_SETTINGS'    =>    'none',
                                                    'TAB_DISPLAY_COMMENTS'    =>    'none',
                                                    'TAB_DISPLAY_VOTES'        =>    'block'
                                            ));
            break;
            default:
                $this->_objTpl->SetVariable(array(    'TAB_ACTIVE_SETTINGS'    =>    'active',
                                                    'TAB_ACTIVE_COMMENTS'    =>    '',
                                                    'TAB_ACTIVE_VOTES'        =>    '',
                                                    'TAB_DISPLAY_SETTINGS'    =>    'block',
                                                    'TAB_DISPLAY_COMMENTS'    =>    'none',
                                                    'TAB_DISPLAY_VOTES'        =>    'none'
                                            ));
        }

        $objResult = $objDatabase->Execute('SELECT     path,
                                                    link,
                                                    size_show
                                            FROM     '.DBPREFIX.'module_gallery_pictures
                                            WHERE     id='.$intPid);

        $objSubResult = $objDatabase->Execute('    SELECT    name,
                                                        `desc`
                                                FROM    '.DBPREFIX.'module_gallery_language_pics
                                                WHERE    picture_id='.$intPid.' AND
                                                        lang_id='.$this->intLangId.'
                                                LIMIT    1');

        // Hide "Show image size" checbox when the settings option "Show image size" is not set
        if ($this->arrSettings['show_image_size'] != 'on') {
            $this->_objTpl->hideBlock('showImageSize');
        }

        $boolSizeShow = ($objResult->fields['size_show'] == '1') ? 'checked' : '';

        $this->_objTpl->setVariable(array(
            'VALUE_THUMB_PATH'         => $this->strThumbnailWebPath.$objResult->fields['path'],
            'VALUE_NAME'            => $objSubResult->fields['name'],
            'VALUE_LINKNAME'        => $objSubResult->fields['desc'],
            'VALUE_LINK'            => $objResult->fields['link'],
            'VALUE_SIZESHOW_CHECKED'=> $boolSizeShow,
            'VALUE_ID'                => $intPid
        ));

        $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
        if (!empty($frontendLanguages)) {
            foreach ($frontendLanguages as $frontendLanguage) {
                $objSubResult = $objDatabase->Execute('    SELECT    name,
                                                                `desc`
                                                        FROM    '.DBPREFIX.'module_gallery_language_pics
                                                        WHERE    picture_id='.$intPid.' AND
                                                                lang_id='.$frontendLanguage['id'].'
                                                        LIMIT    1');
                $this->_objTpl->setVariable(array(
                    'NAMEFIELDS_VALUE'        =>    $objSubResult->fields['name'],
                    'NAMEFIELDS_LID'        =>    $frontendLanguage['id'],
                    'NAMEFIELDS_LANGUAGE'    =>    $frontendLanguage['name'],
            ));
                $this->_objTpl->setVariable(array(
                    'DESCFIELDS_VALUE'        =>    $objSubResult->fields['desc'],
                    'DESCFIELDS_LID'        =>    $frontendLanguage['id'],
                    'DESCFIELDS_LANGUAGE'    =>    $frontendLanguage['name'],
            ));
            $this->_objTpl->parse('showNameFields');
            $this->_objTpl->parse('showDescFields');
            }
        } else {
            $this->_objTpl->hideBlock('showNameFields');
            $this->_objTpl->hideBlock('showDescFields');
        }

    //comments
        $objResult = $objDatabase->Execute('    SELECT        id,
                                                            date,
                                                            name,
                                                            email,
                                                            www,
                                                            comment
                                                FROM        '.DBPREFIX.'module_gallery_comments
                                                WHERE        picid='.$intPid.'
                                                ORDER BY    date ASC
                                            ');
        $this->_objTpl->SetVariable('COMMENTS_COUNT',$objResult->RecordCount());

        if ($objResult->RecordCount() > 0) {
            $i=1;
            while (!$objResult->EOF) {
                $this->_objTpl->SetVariable(array(    'COMMENTS_ROWCLASS'    =>    ($i % 2)+1,
                                                    'COMMENTS_ID'        =>    $objResult->fields['id'],
                                                    'COMMENTS_DATE'        =>    date('d.m.Y',$objResult->fields['date']),
                                                    'COMMENTS_NAME'        =>    $objResult->fields['name'],
                                                    'COMMENTS_EMAIL'    =>    $objResult->fields['email'],
                                                    'COMMENTS_WWW'        =>    $objResult->fields['www'],
                                                    'COMMENTS_TEXT'        =>    nl2br($objResult->fields['comment'])
                                            ));
                $this->_objTpl->parse('showComments');
                $objResult->MoveNext();
                $i++;
            }
        } else {
            $this->_objTpl->hideBlock('showComments');
        }

    //voting
        $objResult = $objDatabase->Execute('    SELECT        id,
                                                            mark
                                                FROM        '.DBPREFIX.'module_gallery_votes
                                                WHERE        picid='.$intPid.'
                                            ');
         if ($objResult->RecordCount() > 0) {
            $intMark = 0;
            $intTotal = $objResult->RecordCount();
            while (!$objResult->EOF) {
                $intMark = $intMark + $objResult->fields['mark'];
                $objResult->MoveNext();
            }
        }
        $this->_objTpl->SetVariable(array(    'VOTING_COUNT'        =>    intval($intTotal),
                                            'VOTING_AVERAGE'    =>    number_format(@round($intMark / $intTotal,1),1,'.','\'')
                                        ));
        /** start paging **/
        $strPaging = getPaging($objResult->RecordCount(), intval($_GET['pos']),'&cmd=Gallery&act=edit_picture&active=voting&id='.intval($_GET['id']), '<b>'.$_ARRAYLANG['TXT_TAB_VOTES'].'</b>', true);
        $this->_objTpl->setVariable('VOTING_PAGING', $strPaging);
        /** end paging **/
        $objResult = $objDatabase->SelectLimit('SELECT        id,
                                                            date,
                                                            mark
                                                FROM        '.DBPREFIX.'module_gallery_votes
                                                WHERE        picid='.$intPid.'
                                                ORDER BY    date ASC',
                                                intval($_CONFIG['corePagingLimit']),
                                                intval($_GET['pos']));

         if ($objResult->RecordCount() > 0) {
             $i=1;
            while (!$objResult->EOF) {
                $this->_objTpl->SetVariable(array(    'VOTES_ROWCLASS'    =>    ($i % 2)+1,
                                                    'VOTES_ID'            =>    $objResult->fields['id'],
                                                    'VOTES_DATE'        =>    date('d.m.Y',$objResult->fields['date']),
                                                    'VOTES_MARK'        =>    $objResult->fields['mark']
                                            ));
                $this->_objTpl->parse('showVotes');
                $objResult->MoveNext();
                $i++;
            }
        } else {
            $this->_objTpl->hideBlock('showVotes');
        }
    }


    /**
     * Updates the name of a picture
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function updatePicture()
    {
        global $objDatabase,$_ARRAYLANG,$_CONFIG;

        $intPicId = intval($_GET['id']);

        foreach ($_POST as $strKey => $strValue) {
            if (substr($strKey,0,strlen('pictureName_')) == 'pictureName_') {
                $arrExplode = explode('_',$strKey,2);
                if (empty($strValue)) {
                    $strValue = $_ARRAYLANG['TXT_GALLERY_CATEGORY_NO_NAME'];
                } else {
                    $strValue = get_magic_quotes_gpc() ? strip_tags($strValue) : addslashes(strip_tags($strValue));
                }
                $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_language_pics
                                        SET     name="'.$strValue.'"
                                        WHERE     picture_id='.$intPicId.' AND
                                                lang_id='.intval($arrExplode[1]).'
                                        LIMIT    1');
            }

            if (substr($strKey,0,strlen('pictureDesc_')) == 'pictureDesc_') {
                $arrExplode = explode('_',$strKey,2);

                $strValue = get_magic_quotes_gpc() ? strip_tags($strValue) : addslashes(strip_tags($strValue));

                $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_language_pics
                                        SET     `desc`="'.$strValue.'"
                                        WHERE     picture_id='.$intPicId.' AND
                                                lang_id='.intval($arrExplode[1]).'
                                        LIMIT    1');
            }
        }

        $_POST['pictureLink'] = get_magic_quotes_gpc() ? strip_tags($_POST['pictureLink']) : addslashes(strip_tags($_POST['pictureLink']));

        $boolSizeShow = isset($_POST['pictureSizeShow']) ? 1 : 0;
        $objResult = $objDatabase->Execute('UPDATE     '.DBPREFIX.'module_gallery_pictures
                                               SET     link="'.$_POST['pictureLink'].'",
                                                      size_show="'.$boolSizeShow.'",
                                                       lastedit='.time().'
                                            WHERE     id='.$intPicId);

        $objResult = $objDatabase->Execute('SELECT     catid
                                              FROM     '.DBPREFIX.'module_gallery_pictures
                                            WHERE     id='.$intPicId);
        return $objResult->fields['catid'];
    }


    /**
     * Shows the settings-page
     *
     * @global    ADONewConnection
     * @global    array
     */
    function showSettings()
    {
         global $objDatabase,$_ARRAYLANG;

         $this->_objTpl->loadTemplateFile('module_gallery_settings.html',true,true);
        $this->_objTpl->setVariable(array(
            'TXT_TITLE'                                =>    $_ARRAYLANG['TXT_GALLERY_MENU_SETTINGS'],
            'TXT_SETTINGS_IMAGENAME_SHOW'            =>    $_ARRAYLANG['TXT_SHOW_PICTURE_NAME'],
            'TXT_SETTINGS_COMMENTS_SHOW'            =>    $_ARRAYLANG['TXT_SETTINGS_COMMENTS'],
            'TXT_SETTINGS_VOTING_SHOW'                =>    $_ARRAYLANG['TXT_SETTINGS_VOTING'],
            'TXT_SETTINGS_LAST_SHOW'                =>    $_ARRAYLANG['TXT_GALLERY_SETTINGS_HOME_LAST'],
            'TXT_SETTINGS_RANDOM_SHOW'                =>    $_ARRAYLANG['TXT_GALLERY_SETTINGS_HOME_RANDOM'],
            'TXT_SETTINGS_UPLOAD'                    =>    $_ARRAYLANG['TXT_MAX_IMAGES_UPLOAD'],
            'TXT_SETTINGS_VALIDATION_TYPE'            =>    $_ARRAYLANG['TXT_VALIDATION_STANDARD_TYPE'],
            'TXT_SETTINGS_VALIDATION_ALL'            =>    $_ARRAYLANG['TXT_SETTINGS_VALIDATION_ALL'],
            'TXT_SETTINGS_VALIDATION_SINGLE'        =>    $_ARRAYLANG['TXT_SETTINGS_VALIDATION_SINGLE'],
            'TXT_SETTINGS_VALIDATION_LIMIT'            =>    $_ARRAYLANG['TXT_VALIDATION_SHOW_LIMIT'],
            'TXT_SETTINGS_THUMB_TYPE'                =>    $_ARRAYLANG['TXT_STANDARD_SIZE_TYPE_THUMBNAILS'],
            'TXT_SETTINGS_THUMB_ABS'                =>    $_ARRAYLANG['TXT_SETTINGS_THUMB_ABS'],
            'TXT_SETTINGS_THUMB_PROZ'                =>    $_ARRAYLANG['TXT_SETTINGS_THUMB_PROZ'],
            'TXT_SETTINGS_THUMB_ABS_WIDTH'            =>    $_ARRAYLANG['TXT_SETTINGS_THUMB_ABS_WIDTH'],
            'TXT_SETTINGS_THUMB_ABS_HEIGHT'            =>    $_ARRAYLANG['TXT_SETTINGS_THUMB_ABS_HEIGHT'],
            'TXT_SETTINGS_THUMB_PROZ_DESC'            =>    $_ARRAYLANG['TXT_SETTINGS_THUMB_PROZ_DESC'],
            'TXT_SETTINGS_QUALITY'                    =>    $_ARRAYLANG['TXT_SETTINGS_QUALITY'],
            'TXT_STANDARD_QUALITY_UPLOADED_PICS'    =>    $_ARRAYLANG['TXT_STANDARD_QUALITY_UPLOADED_PICS'],
            'TXT_GALLERY_QUALITY'                            =>    $_ARRAYLANG['TXT_GALLERY_QUALITY'],
            'TXT_BUTTON_SUBMIT'                        =>    $_ARRAYLANG['TXT_GALLERY_BUTTON_SAVE_SORT'],
            'TXT_GALLERY_SETTINGS_POPUP_ENABLED'    =>    $_ARRAYLANG['TXT_GALLERY_SETTINGS_POPUP_ENABLED'],
            'TXT_GALLERY_SETTINGS_IMAGE_WIDTH'        =>    $_ARRAYLANG['TXT_GALLERY_SETTINGS_IMAGE_WIDTH'],
            'TXT_GALLERY_SETTINGS_PAGING'            =>    $_ARRAYLANG['TXT_GALLERY_SETTINGS_PAGING'],
            'TXT_SETTINGS_HEADER_TYPE'              =>    $_ARRAYLANG['TXT_SETTINGS_HEADER_TYPE'],
            'TXT_SETTINGS_HEADER_HIERARCHY'         =>    $_ARRAYLANG['TXT_SETTINGS_HEADER_HIERARCHY'],
            'TXT_SETTINGS_HEADER_FLAT'              =>    $_ARRAYLANG['TXT_SETTINGS_HEADER_FLAT'],
            'TXT_SETTINGS_IMAGENAME_EXT_SHOW'       =>    $_ARRAYLANG['TXT_SETTINGS_IMAGENAME_EXT_SHOW'],
            'TXT_GALLERY_SLIDE_SHOW'                =>    $_ARRAYLANG['TXT_GALLERY_SLIDE_SHOW'],
            'TXT_GALLERY_SLIDE_SHOW_SECONDS'        =>    $_ARRAYLANG['TXT_GALLERY_SLIDE_SHOW_SECONDS'],
            'TXT_GALLERY_SINGLE_IMAGE_VIEW'         =>    $_ARRAYLANG['TXT_GALLERY_SINGLE_IMAGE_VIEW'] ,
            'TXT_GALLERY_SHOW_FILE_NAME'            =>    $_ARRAYLANG['TXT_GALLERY_SHOW_FILE_NAME'],
            'TXT_IMAGE_SIZE_SHOW'                   =>    $_ARRAYLANG['TXT_IMAGE_SIZE_SHOW']

            ));

        $objResult = $objDatabase->Execute('SELECT         *
                                            FROM         '.DBPREFIX.'module_gallery_settings
                                            ORDER BY     id');

        while ($objResult && !$objResult->EOF) {
            $strValue = '';

            switch ($objResult->fields['name']) {
                case 'show_names':
                case 'show_comments':
                case 'show_voting':
                case 'show_latest':
                case 'show_random':
                case 'show_ext':
                case 'show_image_size':
                    if ($objResult->fields['value'] == 'on') {
                        $strValue = 'checked';
                    }
                    $this->_objTpl->SetVariable('SETTINGS_VALUE_'.strtoupper($objResult->fields['name']),$strValue);
                    break;
                case 'slide_show':
                    if ($objResult->fields['value'] == 'slideshow') {
                        $this->_objTpl->SetVariable('SETTINGS_VALUE_SLIDE_SHOW', 'checked="checked"');
                    }else{
                        $this->_objTpl->SetVariable('SETTINGS_VALUE_NORMAL_VIEW', 'checked="checked"');
                    }
                    break;
                case 'enable_popups':
                    if ($objResult->fields['value'] != 'on') {
                        $this->_objTpl->SetVariable(array(
                            'SLIDE_SHOW_BLOCK'                  => 'display: none',
                        ));
                    } else {
                        $strValue = 'checked';
                    }
                    $this->_objTpl->SetVariable('SETTINGS_VALUE_'.strtoupper($objResult->fields['name']),$strValue);
                    break;
                case 'standard_size_type':
                    if ($objResult->fields['value'] == 'abs')     {
                        $this->_objTpl->SetVariable(array(    'SETTINGS_VALUE_THUMB_TYPE_ABS'        =>    'checked',
                                                            'SETTINGS_VALUE_THUMB_TYPE_PROZ'    =>    ''));
                    } else {
                         $this->_objTpl->SetVariable(array(    'SETTINGS_VALUE_THUMB_TYPE_ABS'        =>    '',
                                                            'SETTINGS_VALUE_THUMB_TYPE_PROZ'    =>    'checked'));
                    }
                    break;
                case 'validation_standard_type':
                    if ($objResult->fields['value'] == 'all')     {
                        $this->_objTpl->SetVariable(array(    'SETTINGS_VALUE_VALIDATION_TYPE_ALL'    =>    'checked',
                                                            'SETTINGS_VALUE_VALIDATION_TYPE_SINGLE'    =>    ''));
                    } else {
                         $this->_objTpl->SetVariable(array(    'SETTINGS_VALUE_VALIDATION_TYPE_ALL'    =>    '',
                                                            'SETTINGS_VALUE_VALIDATION_TYPE_SINGLE'    =>    'checked'));
                    }
                    break;
                case 'header_type':
                    if ($objResult->fields['value'] == 'hierarchy')     {
                        $this->_objTpl->SetVariable(array( 'SETTINGS_VALUE_HEADER_HIERARCHY' => 'checked',
                                                           'SETTINGS_VALUE_HEADER_FLAT'      => ''));
                    } else {
                        $this->_objTpl->SetVariable(array( 'SETTINGS_VALUE_HEADER_HIERARCHY' => '',
                                                           'SETTINGS_VALUE_HEADER_FLAT'      => 'checked'));
                    }
                    break;
                case 'show_file_name':
                    if ($objResult->fields['value'] == 'on') {
                        $checked = "checked='checked'";
                        $display = "display: block";
                    } else {
                        $checked = "";
                        $display = "display: none";
                    }
                    $this->_objTpl->setVariable(array(
                        'SETTINGS_VALUE_SHOW_FILE_NAME' => $checked,
                        'EXTENSION_SHOW_BLOCK'          => $display
                    ));
                    break;
                default: //integer value
                    $this->_objTpl->SetVariable('SETTINGS_VALUE_'.strtoupper($objResult->fields['name']),$objResult->fields['value']);
            }
            $objResult->MoveNext();
        }
    }


    /**
     * Save the settings for the gallery
     *
     * @global    ADONewConnection
     * @global    array
     */
    function saveSettings()
    {
        global $objDatabase,$_ARRAYLANG;

        if ($_POST['standard_size_type'] != 'proz' && $_POST['standard_size_type'] != 'abs') {
            // the submitted category isnt allowed, so set the standardvalue 'proz'
            $_POST['standard_size_type'] = 'proz';
        }
        if ($_POST['standard_height_abs'] > 2000) {
            $_POST['standard_height_abs'] = 2000;
        }
        if ($_POST['standard_width_abs'] > 2000) {
            $_POST['standard_width_abs'] = 2000;
        }
        if ($_POST['validation_standard_type'] != 'all' && $_POST['validation_standard_type'] != 'single') {
            // the submitted standard type is not correct
            $_POST['validation_standard_type'] = 'all';
        }
        if ($_POST['show_names'] != 'on') {
            // the value is not allowed, reset to off
            $_POST['show_names'] = 'off';
        }
        if ($_POST['show_comments'] != 'on') {
            //the value is not allowed, set it to 'off'
            $_POST['show_comments'] = 'off';
        }
          if ($_POST['show_voting'] != 'on') {
            //the value is not allowed, set it to 'off'
            $_POST['show_voting'] = 'off';
        }
        if ($_POST['enable_popups'] != 'on') {
            //the value is not allowed, set it to 'off'
            $_POST['enable_popups'] = 'off';
        }
        if ($_POST['show_latest'] != 'on') {
            //the value is not allowed, set it to 'off'
            $_POST['show_latest'] = 'off';
        }
        if ($_POST['show_random'] != 'on') {
            //the value is not allowed, set it to 'off'
            $_POST['show_random'] = 'off';
        }
        if ($_POST['image_width'] <= 0) {
            $_POST['image_width'] = 2000;
        }
        if (intval($_POST['quality']) > 100 || intval($_POST['quality']) <= 0) {
            // the value shouldn't be above 100 otherwise the image becomes larger
            $_POST['quality'] = 95;
        }
         if (intval($_POST['standard_quality']) > 100 || intval($_POST['standard_quality']) <= 0) {
            // the value shouldn't be above 95 otherwise the image becomes larger
            $_POST['standard_quality'] = 95;
        }
// neu
        if ($_POST['show_ext'] != 'on') {
            //the value is not allowed, set it to 'off'
            $_POST['show_ext'] = 'off';
        }
        if ($_POST['header_type'] != 'hierarchy' && $_POST['header_type'] != 'flat') {
            //the value is not allowed, set it to 'hierarchy'
            $_POST['header_type'] = 'hierarchy';
        }

        if (empty($_POST['show_file_name']) || $_POST['show_file_name'] != 'on') {
            $_POST['show_file_name'] = "off";
        }

        if ($_POST['show_image_size'] != 'on') {
            // the value is not allowed, reset to off
            $_POST['show_image_size'] = 'off';
        }

        foreach ($_POST as $strKey => $strValue) {
            $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_settings
                                    SET     value="'.contrexx_addslashes($strValue).'"
                                    WHERE     name="'.contrexx_addslashes($strKey).'"');
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_SETTINGS_SAVED'];
    }


    /**
     * Shows the UploadForm
     *
     * @global    ADONewConnection
     * @global    array
     */
    function showUploadForm()
    {
        global $objDatabase,$_ARRAYLANG, $_CORELANG;

        /**
         * Uploader button handling
         */
        //paths we want to remember for handling the uploaded files
        $paths = array(
            'path' => ASCMS_GALLERY_PATH,
            'webPath' => ASCMS_GALLERY_WEB_PATH
        );

        $uploader = new Uploader();
        $uploader->setFinishedCallback(array(ASCMS_MODULE_PATH.'/Gallery/Controller/GalleryManager.class.php', '\Cx\Modules\Gallery\Controller\GalleryManager', 'uploadFinished'));
        $uploader->setData($paths);
        $uploader->addClass('uploadbutton');
        $uploader->setOptions(array(
            'allowed-extensions' => array('jpg', 'gif', 'png')
        ));

        $uploader->setCallback('finishedGalleryUpload');
        //set instance name to combo_uploader so we are able to catch the instance with js
//        $comboUp->setJsInstanceName('exposed_combo_uploader');
        $redirectUrl = \Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?cmd=Gallery&act=validate_form');

        $this->_objTpl->loadTemplateFile('module_gallery_upload_images.html', true, true);
        $this->_objTpl->setVariable(array(
//              'COMBO_UPLOADER_CODE' => $comboUp->getXHtml(true),
                'COMBO_UPLOADER_CODE' => $uploader->getXHtml($_ARRAYLANG['TXT_GALLERY_MENU_UPLOAD_FORM_SUBMIT']),
              'REDIRECT_URL'        => $redirectUrl
        ));
        //end of uploader button handling

        //get enabled filetypes
        $strEnabledTypes = '';
        if ($this->boolGifEnabled == true) {
            $strEnabledTypes .= 'GIF ';
        }

        if ($this->boolJpgEnabled == true) {
            $strEnabledTypes .= 'JPG ';
        }

        if ($this->boolPngEnabled == true) {
            $strEnabledTypes .= 'PNG ';
        }

        $objResult = $objDatabase->Execute('SELECT         *
                                                    FROM         '.DBPREFIX.'module_gallery_pictures
                                                    WHERE         validated="0"
                                                    ORDER BY     lastedit ASC');
        while (!$objResult->EOF) {
            $this->_objTpl->setVariable('GALLERY_UPLOADED_IMAGE', '<img src="'.$this->strImageWebPath.$objResult->fields['path'].'" class="galleryImageVisible" />');
            $this->_objTpl->parse('uploadedImages');
            $objResult->MoveNext();
        }

        if ($objResult->_numOfRows == 0)
        {
            $this->_objTpl->setVariable('GALLERY_ALREADY_UPLOADED_IMAGES', 'confirmImages');
        }

        $this->_objTpl->setVariable(array(
                'TXT_TITLE' => $_ARRAYLANG['TXT_GALLERY_MENU_UPLOAD_FORM'],
                'TXT_GALLERY_UPLOAD_CONFIRM_IMAGES' => $_ARRAYLANG['TXT_GALLERY_UPLOAD_CONFIRM_IMAGES'],
                'TXT_GALLERY_UPLOAD_IMAGES' => $_ARRAYLANG['TXT_GALLERY_UPLOAD_IMAGES'],
                'TXT_GALLERY_UPLOAD_HELP' => $_ARRAYLANG['TXT_GALLERY_UPLOAD_HELP'],
                'TXT_IMAGENUMBER' => $_ARRAYLANG['TXT_GALLERY_UPLOAD_FORM_IMAGE_NUMBER'],
                'TXT_ENABLED_IMAGE_TYPE' =>
                    $_ARRAYLANG['TXT_GALLERY_FORMAT_SUPPORT'] . ' '
                    . $strEnabledTypes . '. '
                    . $_ARRAYLANG['TXT_GALLERY_NO_UPLOAD'],
                'TXT_BUTTON_SUBMIT' => $_ARRAYLANG['TXT_GALLERY_MENU_UPLOAD_FORM_SUBMIT']
        ));
    }


    /**
     * Upload the submitted images
     *
     * @global    ADONewConnection
     * @global  array
     * @global  array
     * @param   string        $tempPath
     * @param   array        $paths
     * @param   integer        $uploadId
     */
    public static function uploadFinished($tempPath, $tempWebPath, $paths, $uploadId, $fileInfos, $response) {

        global $objDatabase, $_ARRAYLANG, $_CONFIG, $objInit;
        $lang = $objInit->loadLanguageData('Gallery');
        $objGallery = new GalleryManager();

        $path = $paths['path'];
        $webPath = $paths['webPath'];

        //we remember the names of the uploaded files here. they are stored in the session afterwards,
        //so we can later display them highlighted.
        $arrFiles = array();

        //get allowed file types
        $arrAllowedFileTypes = array();
        if (imagetypes() & IMG_GIF) { $arrAllowedFileTypes[] = 'gif'; }
        if (imagetypes() & IMG_JPG) { $arrAllowedFileTypes[] = 'jpg'; $arrAllowedFileTypes[] = 'jpeg'; }
        if (imagetypes() & IMG_PNG) { $arrAllowedFileTypes[] = 'png'; }

        //rename files, delete unwanted
        $arrFilesToRename = array(); //used to remember the files we need to rename

        $file = str_replace($tempPath.'/', '',$fileInfos['path']);


        $info = pathinfo($file);

        //delete unwanted files
        if(!in_array(strtolower($info['extension']), $arrAllowedFileTypes)) {
            unlink($tempPath.'/'.$file);
            return;
        }

        //width of the image is wider than the allowed value. Show Error.
        $arrImageSize = getimagesize($tempPath.'/'.$file);
        if (intval($arrImageSize[0]) > intval($objGallery->arrSettings['image_width'])) {
             @unlink($tempPath.'/'.$file);
             throw new \Exception(
                 str_replace(
                     '{WIDTH}',
                     $objGallery->arrSettings['image_width'],
                     $lang['TXT_GALLERY_UPLOAD_ERROR_WIDTH']
                 )
             );
        }

        //check if file needs to be renamed
        $newName = \Cx\Lib\FileSystem\FileSystem::replaceCharacters($file);
        if (self::fileExists($path.'/'.$newName, false)) {
            $info     = pathinfo($newName);
            $exte     = $info['extension'];
            $exte     = (!empty($exte)) ? '.'.$exte : '';
            $part1    = $info['filename'];
            if (empty($_REQUEST['uploadForceOverwrite']) || !intval($_REQUEST['uploadForceOverwrite'] > 0)) {
                $newName = $part1.'_'.time().$exte;
            }
        }

        //if the name has changed, the file needs to be renamed afterwards
        if ($newName != $file) {
            $arrFilesToRename[$file] = $newName;
            array_push($arrFiles, $newName);
        }

        //create entry in the database for the uploaded image
        self::insertImage($objGallery, $newName, $newName);

        //rename files where needed
        foreach($arrFilesToRename as $oldName => $newName){
            rename($tempPath.'/'.$oldName, $tempPath.'/'.$newName);
        }

        /* unwanted files have been deleted, unallowed filenames corrected.
           we can now simply return the desired target path, as only valid
           files are present in $tempPath */
        return array($path, $webPath, $newName);
    }


    /**
     * Insert image into the database
     *
     * @global    ADONewConnection
     * @global    array
     * @param    string        $strImagePath
     * @param    integer        $imageName
     */
    public static function insertImage($objGallery, $strImagePath,$imageName) {
        global $objDatabase,$_CONFIG;

        $arrImageInfo     = getimagesize($objGallery->strImagePath.$strImagePath);

        $intOldWidth     = $arrImageInfo[0];
        $intOldHeight     = $arrImageInfo[1];
        $intNewWidth     = intval($objGallery->arrSettings['standard_width_abs']);
        $intNewHeight     = intval($objGallery->arrSettings['standard_height_abs']);

        if ($intNewWidth == 0) {
            // exception if width and height or 0!
            if ($intNewHeight == 0) {
                $objGallery->arrSettings['standard_height_abs'] = 100;
                $intNewHeight = $objGallery->arrSettings['standard_height_abs'];
            }
            $intNewWidth = round(($intOldWidth * $intNewHeight) / $intOldHeight,0);
        } else if ($intNewHeight == 0){
            $intNewHeight = round(($intOldHeight * $intNewWidth) / $intOldWidth,0);
        }

        //the link="" is needed as the column has no default value and mysql
        //doesn't allow to specify one for text columns.
        $query = '    INSERT
                                INTO    '.DBPREFIX.'module_gallery_pictures
                                SET     link="",
                                        path="'.contrexx_raw2db($strImagePath).'",
                                        lastedit="'.time().'",
                                        quality="'.$objGallery->arrSettings['standard_quality'].'",
                                        size_type="'.$objGallery->arrSettings['standard_size_type'].'",
                                        size_proz="'.$objGallery->arrSettings['standard_size_proz'].'",
                                        size_abs_h="'.$intNewHeight.'",
                                        size_abs_w="'.$intNewWidth.'"';
        $objDatabase->Execute($query);

        $intPictureId = $objDatabase->insert_id();
        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $objResult = $objDatabase->Execute('INSERT INTO '.DBPREFIX.'module_gallery_language_pics
                                               (picture_id, lang_id, name)
                                            VALUES ('.$intPictureId.', ' . $frontendLanguage['id'] . ', "'.contrexx_raw2db($imageName).'")');
        }
    }


    /**
     * Shows the validation-page
     *
     * @global    array
     * @global    ADONewConnection
     * @global    array
     */
    function showValidateForm()
    {
        global $_ARRAYLANG, $objDatabase, $_CONFIG;

        \JS::activate('jquery');

        $this->_objTpl->loadTemplateFile('module_gallery_validate_main.html',true,true);
         $this->_objTpl->setVariable(array(
            'TXT_TITLE'                        => $_ARRAYLANG['TXT_GALLERY_MENU_VALIDATE'],
            'TXT_NOTVALIDATED'                 => $_ARRAYLANG['TXT_GALLERY_VALIDATE_NOT_VALIDATED'],
            'TXT_VALIDATE_METHOD_SINGLE'       => $_ARRAYLANG['TXT_GALLERY_VALIDATE_METHOD_SINGLE'],
            'TXT_VALIDATE_METHOD_ALL'          => $_ARRAYLANG['TXT_GALLERY_VALIDATE_METHOD_ALL'],
        ));

        if (!isset($_GET['type'])) {
            $_GET['type'] = $this->arrSettings['validation_standard_type'];
        }

        switch ($_GET['type']) {
            case 'all':
                $this->_objTpl->setVariable(array(
                    'VALIDATE_METHOD_SINGLE_SELECTED'    => '',
                    'VALIDATE_METHOD_ALL_SELECTED'        =>    'checked'
                ));
            break;
            default:
                $this->_objTpl->setVariable(array(
                    'VALIDATE_METHOD_SINGLE_SELECTED'    => 'checked',
                    'VALIDATE_METHOD_ALL_SELECTED'        =>    ''
                ));
        }

        $objResult = $objDatabase->Execute('SELECT     id
                                            FROM     '.DBPREFIX.'module_gallery_pictures
                                            WHERE     validated="0"');
        $this->_objTpl->setVariable('VALIDATE_NOTVALIDATEDPICS',$objResult->RecordCount());

        if ($objResult->RecordCount() > 0) {
            // only a single picture
            $objFWUser = \FWUser::getFWUserObject();

            if ($_GET['type'] == 'single') {
                $this->_objTpl->loadTemplateFile('module_gallery_validate_details_single.html',true,true);
                $this->_objTpl->setVariable(array(
                    'TXT_DETAILS_TITLE'                     => $_ARRAYLANG['TXT_GALLERY_VALIDATE_PICTURE_DETAILS'],
                    'TXT_DETAILS_NAME'                      => $_ARRAYLANG['TXT_GALLERY_VALIDATE_NAME'],
                    'TXT_DETAILS_UPLOADDATE'                => $_ARRAYLANG['TXT_GALLERY_VALIDATE_UPLOAD_DATE'],
                    'TXT_DETAILS_CATEGORY'                  => $_ARRAYLANG['TXT_GALLERY_VALIDATE_CATEGORY'],
                    'TXT_DETAILS_CATEGORYSELECT'            => $_ARRAYLANG['TXT_GALLERY_VALIDATE_CATEGORY_SELECT'],
                    'TXT_DETAILS_ACTIVE'                    => $_ARRAYLANG['TXT_GALLERY_VALIDATE_ACTIVE'],
                    'TXT_DETAILS_SIZE_ORIG'                 => $_ARRAYLANG['TXT_GALLERY_VALIDATE_SIZE_ORG'],
                    'TXT_DETAILS_HEIGHT_WIDTH_ORIG'         => $_ARRAYLANG['TXT_GALLERY_VALIDATE_HEIGHT_WIDTH_ORIG'],
                    'TXT_DETAILS_SIZE_THUMB'                => $_ARRAYLANG['TXT_GALLERY_VALIDATE_SIZE_THUMB'],
                    'TXT_DETAILS_HEIGHT_WIDTH_THUMB'        => $_ARRAYLANG['TXT_GALLERY_VALIDATE_HEIGHT_WIDTH_THUMB'],
                    'TXT_DETAILS_NEW_SIZE_THUMB'            => $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE'],
                    'TXT_DETAILS_NEW_QUALITY_THUMB'         => $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_QUALITY'],
                    'TXT_DETAILS_THUMB_SIZE_ABS'            => $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_ABS'],
                    'TXT_DETAILS_THUMB_SIZE_PROZ'           => $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_PER'],
                    'TXT_DETAILS_THUMB_SIZE_ABS_WIDTH'      => $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_ABS_WIDTH'],
                    'TXT_DETAILS_THUMB_SIZE_ABS_HEIGHT'     => $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_ABS_HEIGHT'],
                    'TXT_DETAILS_THUMB_PREVIEW'             => $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_PREVIEW'],
                    'TXT_DETAILS_BUTTON_UPDATE'             => $_ARRAYLANG['TXT_GALLERY_VALIDATE_BUTTON_UPDATE'],
                    'TXT_DETAILS_BUTTON_SUBMIT'             => $_ARRAYLANG['TXT_GALLERY_VALIDATE_BUTTON_SUBMIT'],
                    'TXT_DETAILS_BUTTON_DELETE'             => $_ARRAYLANG['TXT_GALLERY_VALIDATE_BUTTON_DELETE'],
                    'TXT_EXTENDED'                          => $_ARRAYLANG['TXT_GALLERY_EXTENDED']
                ));

                $objResult = $objDatabase->Execute('SELECT         *
                                                    FROM         '.DBPREFIX.'module_gallery_pictures
                                                    WHERE         validated="0"
                                                    ORDER BY     lastedit ASC');

                $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
                if (!empty($frontendLanguages)) {
                    foreach ($frontendLanguages as $frontendLanguage) {
                        $objSubSubResult = $objDatabase->Execute('  SELECT   name
                                                                    FROM     '.DBPREFIX.'module_gallery_language_pics
                                                                    WHERE    picture_id='.$objResult->fields['id'].' AND
                                                                             lang_id='.$frontendLanguage['id'].'
                                                                    LIMIT    1');
                        $this->_objTpl->setVariable(array(
                            'NAMEFIELDS_VALUE'          =>    $objSubSubResult->fields['name'],
                            'NAMEFIELDS_LID'            =>    $frontendLanguage['id'],
                            'NAMEFIELDS_LANGUAGE'       =>    $frontendLanguage['name'],
                        ));
                        $this->_objTpl->parse('showNameFields');
                    }
                } else {
                    $this->_objTpl->hideBlock('showNameFields');
                }

                $objSubResult = $objDatabase->Execute(' SELECT  name
                                                        FROM    '.DBPREFIX.'module_gallery_language_pics
                                                        WHERE   picture_id='.$objResult->fields['id'].' AND
                                                                lang_id='.$this->intLangId.'
                                                        LIMIT   1
                                                    ');

                $strDetailsActive     = ($objResult->fields['status'] == '1') ? 'checked' : '';
// TODO: Unused
//                $intImageCatId         = $objResult->fields['catid'];
                $arrImageInfos         = getimagesize($this->strImagePath.$objResult->fields['path']);

                $this->_objTpl->setCurrentBlock('showThumbSize');
                $boolCheckerThumbFileSize = false;
                for ($i = 5; $i <= 100; $i=$i+5) {
                    $this->_objTpl->setVariable('THUMB_SIZE_VALUE',$i);
                    if ($i >= $objResult->fields['size_proz'] && !$boolCheckerThumbFileSize) {
                        $this->_objTpl->setVariable('THUMB_SIZE_SELECTED','selected');
                        $boolCheckerThumbFileSize = true;
                    } else {
                        $this->_objTpl->setVariable('THUMB_SIZE_SELECTED','');
                    }
                    $this->_objTpl->parseCurrentBlock();
                }

                $this->_objTpl->setCurrentBlock('showThumbQuality');
                $boolCheckerThumbFileQuality = false;
                for ($i = 5; $i <= 100; $i=$i+5)
                {
                    $this->_objTpl->setVariable('THUMB_QUALITY_VALUE',$i);
                    if ($i >= $objResult->fields['quality'] && !$boolCheckerThumbFileQuality) {
                        $this->_objTpl->setVariable('THUMB_QUALITY_SELECTED','selected');
                        $boolCheckerThumbFileQuality = true;
                    } else {
                        $this->_objTpl->setVariable('THUMB_QUALITY_SELECTED','');
                    }
                    $this->_objTpl->parseCurrentBlock();
                }

                //create a thumbnail, but first delete all temporary files!
                $handleDirectory = opendir($this->strThumbnailPath);
                $strFile = readdir ($handleDirectory);
                while ($strFile) {
                    if (substr($strFile,0,5) == 'temp_') {
                        unlink($this->strThumbnailPath.$strFile);
                    }
                    $strFile = readdir ($handleDirectory);
                }
                closedir($handleDirectory);

                srand ((double)microtime()*1000000);
                $intNewThumbRandValue = rand(); // create a random value for the picture

                $this->_objTpl->setVariable(array(
                        'DETAILS_SIZE_ABS_WIDTH_VALUE'    =>    $objResult->fields['size_abs_w'],
                        'DETAILS_SIZE_ABS_HEIGHT_VALUE'    =>    $objResult->fields['size_abs_h'],
                        ));

                if ($objResult->fields['size_type'] == 'proz')
                { // the image sizes are proportional
                    $this->_objTpl->setVariable(array(
                        'DETAILS_SIZE_SELECTION_PROZ'           =>    'checked',
                        'DETAILS_SIZE_SELECTION_ABS'            =>    '',
                        'DETAILS_SIZE_ABS_WIDTH_DISABLED'       =>    'disabled',
                        'DETAILS_SIZE_ABS_HEIGHT_DISABLED'      =>    'disabled',
                        'DETAILS_SIZE_ABS_PROP_DISABLED'        =>    'disabled',
                        'DETAILS_SIZE_PROZ_WIDTH_DISABLED'      =>    '',
                    ));

                    $intNewThumWidth     = floor(($objResult->fields['size_proz']/100) * $arrImageInfos[0]);
                    $intNewThumbHeight     = floor(($objResult->fields['size_proz']/100) * $arrImageInfos[1]);
                    $intNewThumbQuality = $objResult->fields['quality'];
                }
                else
                {
                    $this->_objTpl->setVariable(array(
                        'DETAILS_SIZE_SELECTION_PROZ'           =>    '',
                        'DETAILS_SIZE_SELECTION_ABS'            =>    'checked',
                        'DETAILS_SIZE_ABS_WIDTH_DISABLED'       =>    '',
                        'DETAILS_SIZE_ABS_HEIGHT_DISABLED'      =>    '',
                        'DETAILS_SIZE_PROZ_WIDTH_DISABLED'      =>    'disabled',
                        'DETAILS_SIZE_ABS_PROP_DISABLED'        =>    ''
                    ));

                    $intNewThumWidth     = $objResult->fields['size_abs_w'];
                    $intNewThumbHeight     = $objResult->fields['size_abs_h'];
                    $intNewThumbQuality = $objResult->fields['quality'];
                }

                //create thumb
                $this->createImages_JPG_GIF_PNG($this->strImagePath, $this->strThumbnailPath, $objResult->fields['path'], "temp_".$intNewThumbRandValue."_".$objResult->fields['path'], $intNewThumWidth, $intNewThumbHeight, $intNewThumbQuality);

                $this->_objTpl->setVariable(array(
                    'DETAILS_ID'                        =>     $objResult->fields['id'],
                    'DETAILS_NAME'                      =>    $frontendLanguage['name'],
                    'DETAILS_UPLOADDATE'                =>    date('d.m.Y - h:i:s',$objResult->fields['lastedit']),
                    'DETAILS_ACTIVE_SELECTED'           =>    $strDetailsActive,
                    'DETAILS_SIZE_ORIG'                 =>    round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2),
                    'DETAILS_SIZE_THUMB'                =>    round(filesize($this->strThumbnailPath.'temp_'.$intNewThumbRandValue.'_'.$objResult->fields['path'])/1024,2),
                    'DETAILS_WIDTH_ORIG'                =>    $arrImageInfos[0],
                    'DETAILS_HEIGHT_ORIG'               =>    $arrImageInfos[1],
                    'DETAILS_WIDTH_THUMB'               =>    $intNewThumWidth,
                    'DETAILS_HEIGHT_THUMB'              =>    $intNewThumbHeight,
                    'DETAILS_THUMB_PREVIEW_PATH'        =>    $this->strThumbnailWebPath.'temp_'.$intNewThumbRandValue.'_'.$objResult->fields['path']
                ));

                try {
                    $this->parseCategoryDropdown();
                } catch (DatabaseError $e) {
                    $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
                    $this->strErrMessage .= $e;
                    return;
                }
            } else {
                // multiple pictures on one page
                $this->_objTpl->loadTemplateFile('module_gallery_validate_details_all.html',true,true);
                $this->_objTpl->setVariable(array(
                    'TXT_TITLE_NAME'                    =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_NAME'],
                    'TXT_TITLE_UPLOADDATE'              =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_UPLOAD_DATE'],
                    'TXT_TITLE_SIZE_O'                  =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_SIZE_ORG'],
                    'TXT_TITLE_RESO_O'                  =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_HEIGHT_WIDTH_ORIG'],
                    'TXT_TITLE_SIZE_T'                  =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_SIZE_THUMB'],
                    'TXT_TITLE_RESO_T'                  =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_HEIGHT_WIDTH_THUMB'],
                    'TXT_TITLE_CATEGORY'                =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_CATEGORY'],
                    'TXT_TITLE_ACTIVE'                  =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_ACTIVE'],
                    'TXT_DELETE_IMAGE_MSG'              =>    $_ARRAYLANG['TXT_GALLERY_DELETE_IMAGE_MSG'],
                    'TXT_WRONG_CATEGORIES_MSG'          =>    $_ARRAYLANG['TXT_GALLERY_WRONG_CATEGORIES_MSG'],
                    'TXT_SETTINGS_FILENAME'             =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_ALL_SET_FILENAME'],
                    'TXT_SETTINGS_TITLE'                =>    $_ARRAYLANG['TXT_SETTINGS'],
                    'TXT_SETTINGS_ACTIVE'               =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_ALL_SET_ACTIVE'],
                    'TXT_SETTINGS_CATEGORY'             =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_ALL_SET_CATEGORY'],
                    'TXT_SETTINGS_THUMBSIZE_ABS'        =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_ABS'],
                    'TXT_SETTINGS_THUMBSIZE_PROZ'       =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_PER'],
                    'TXT_SETTINGS_TITLE_THUMBSIZE'      =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE'],
                    'TXT_SETTINGS_THUMBSIZE_ABS_WIDTH'  =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_ABS_WIDTH'],
                    'TXT_SETTINGS_THUMBSIZE_ABS_HEIGHT' =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_SIZE_ABS_HEIGHT'],
                    'TXT_SETTINGS_TITLE_THUMBQUALITY'   =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_THUMB_QUALITY'],
                    'TXT_DETAILS_BUTTON_UPDATE'         =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_BUTTON_UPDATE'],
                    'TXT_DETAILS_BUTTON_SUBMIT'         =>    $_ARRAYLANG['TXT_GALLERY_VALIDATE_BUTTON_SUBMIT'],
                ));
                $this->_objTpl->setGlobalVariable('TXT_DETAILS_CATEGORYSELECT', $_ARRAYLANG['TXT_GALLERY_VALIDATE_CATEGORY_SELECT']);
                if ($this->arrSettings['standard_size_type'] == 'abs')
                {
                    $this->_objTpl->setVariable( array(
                        'SETTINGS_SELECTED_ABS'             =>  'checked',
                        'SETTINGS_SELECTED_PROZ'            =>  '',
                        'SETTINGS_THUMBSIZE_ABS_WIDTH_DIS'  =>  '',
                        'SETTINGS_THUMBSIZE_ABS_HEIGHT_DIS' =>  '',
                        'SETTINGS_THUMBSIZE_PROZ_DIS'       =>  'disabled',
                    ));
                }
                else
                {
                    $this->_objTpl->setVariable( array(
                        'SETTINGS_SELECTED_ABS'             =>    '',
                        'SETTINGS_SELECTED_PROZ'            =>    'checked',
                        'SETTINGS_THUMBSIZE_ABS_WIDTH_DIS'  =>    'disabled',
                        'SETTINGS_THUMBSIZE_ABS_HEIGHT_DIS' =>    'disabled',
                        'SETTINGS_THUMBSIZE_PROZ_DIS'       =>    '',
                    ));
                }
                $this->_objTpl->setVariable( array(
                    'SETTINGS_THUMBSIZE_ABS_WIDTH'    =>    $this->arrSettings['standard_width_abs'],
                    'SETTINGS_THUMBSIZE_ABS_HEIGHT'    =>    $this->arrSettings['standard_height_abs']
                ));
                try {
                    $this->parseCategoryDropdown();
                } catch (DatabaseError $e) {
                    $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
                    $this->strErrMessage .= $e;
                    return;
                }
                // here start the quality-dropdown
                $this->_objTpl->setCurrentBlock('showThumbQuality');

                for ($i = 5; $i <= 100; $i=$i+5)
                {
                    $this->_objTpl->setVariable('THUMB_QUALITY_VALUE',$i);
                    if ($i >= $this->arrSettings['standard_quality'] && !$boolCheckerThumbFileQuality)
                    {
                        $this->_objTpl->setVariable('THUMB_QUALITY_SELECTED','selected');
                        $boolCheckerThumbFileQuality = true;
                    }
                    else
                    {
                        $this->_objTpl->setVariable('THUMB_QUALITY_SELECTED','');
                    }
                    $this->_objTpl->parseCurrentBlock();
                }
                // here ends the quality-dropdown
                // here starts the thumbsize-proz dropdown
                $this->_objTpl->setCurrentBlock('showThumbSizeProz');
                $boolCheckerThumbFileSize = false;
                for ($i = 5; $i <= 100; $i=$i+5)
                {
                    $this->_objTpl->setVariable('THUMB_SIZE_VALUE',$i);
                    if ($i >= $this->arrSettings['standard_size_proz'] && !$boolCheckerThumbFileSize)
                    {
                        $this->_objTpl->setVariable('THUMB_SIZE_SELECTED','selected');
                        $boolCheckerThumbFileSize = true;
                    } else {
                        $this->_objTpl->setVariable('THUMB_SIZE_SELECTED','');
                    }
                    $this->_objTpl->parseCurrentBlock();
                }
                // here ends the thumbsize-proz dropdown
                $handleDirectory = opendir($this->strThumbnailPath);
                $strFile = readdir($handleDirectory);
                while ($strFile) {
                    if (substr($strFile,0,5) == 'temp_') {
                        unlink($this->strThumbnailPath.$strFile);
                    }
                    $strFile = readdir($handleDirectory);
                }
                closedir($handleDirectory);
                srand((double)microtime()*1000000);

                $objResult = $objDatabase->SelectLimit('SELECT         *
                                                        FROM         '.DBPREFIX.'module_gallery_pictures
                                                        WHERE         validated="0"
                                                        ORDER BY     lastedit ASC',
                                                        $this->arrSettings['validation_show_limit']);

                while (!$objResult->EOF) {
                    $objSubResult = $objDatabase->Execute('    SELECT        lang_id,
                                                                        name
                                                            FROM        '.DBPREFIX.'module_gallery_language_pics
                                                            WHERE        picture_id='.$objResult->fields['id'].'
                                                            ORDER BY    lang_id ASC
                                                        ');
                    while (!$objSubResult->EOF) {
                        $arrNames[$objResult->fields['id']][$objSubResult->fields['lang_id']] = $objSubResult->fields['name'];
                        $objSubResult->MoveNext();
                    }
                    $arrFileInfo = getimagesize($this->strImagePath.$objResult->fields['path']);

                    $arrImageCounter[$objResult->fields['id']]                 = $objResult->fields['id'];
                    $arrImageInfo[$objResult->fields['id']]['name']         = contrexx_raw2xhtml($arrNames[$objResult->fields['id']][$this->intLangId]);
                    $arrImageInfo[$objResult->fields['id']]['random_path']     = $this->strThumbnailWebPath.'temp_'.rand().'_'.$objResult->fields['path'];
                    $arrImageInfo[$objResult->fields['id']]['uploadtime']     = date('d.m.Y',$objResult->fields['lastedit']);
                    $arrImageInfo[$objResult->fields['id']]['size_o']         = round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2);
                    $arrImageInfo[$objResult->fields['id']]['reso_o']         = $arrFileInfo[0].'x'.$arrFileInfo[1];

                    if ($objResult->fields['size_type'] == 'proz') { // the image sizes are proportional
                        $intNewThumWidth         = floor(($objResult->fields['size_proz']/100) * $arrFileInfo[0]);
                        $intNewThumbHeight     = floor(($objResult->fields['size_proz']/100) * $arrFileInfo[1]);
                        $intNewThumbQuality     = $objResult->fields['quality'];
                    } else { // the image sizes are absolute
                        $intNewThumWidth         = $objResult->fields['size_abs_w'];
                        $intNewThumbHeight     = $objResult->fields['size_abs_h'];
                        $intNewThumbQuality     = $objResult->fields['quality'];
                    }
                    //create thumb
                    $this->createImages_JPG_GIF_PNG($this->strImagePath, ASCMS_GALLERY_THUMBNAIL_PATH.'/', $objResult->fields['path'], basename($arrImageInfo[$objResult->fields['id']]['random_path']), $intNewThumWidth, $intNewThumbHeight, $intNewThumbQuality);
                    $arrFileInfo = getimagesize(ASCMS_PATH.$arrImageInfo[$objResult->fields['id']]['random_path']);
                    $arrImageInfo[$objResult->fields['id']]['size_t'] = round(filesize(ASCMS_PATH.$arrImageInfo[$objResult->fields['id']]['random_path'])/1024,2);
                    $arrImageInfo[$objResult->fields['id']]['reso_t'] = $arrFileInfo[0].'x'.$arrFileInfo[1];
                    $objResult->MoveNext();
                }

                $intRowColor = 0;
                foreach ($arrImageCounter as $intIdKey) {
                    $this->_objTpl->setVariable(array(
                        'IMAGES_ROWCLASS'           => ($intRowColor % 2),
                        'IMAGES_ID'                 => $intIdKey,
                        'IMAGES_THUMB_SOURCE'       => $arrImageInfo[$intIdKey]['random_path'],
                        'IMAGES_NAME'               => $arrImageInfo[$intIdKey]['name'],
                        'IMAGES_UPLOADDATE'         => $arrImageInfo[$intIdKey]['uploadtime'],
                        'IMAGES_SIZE_O'             => $arrImageInfo[$intIdKey]['size_o'],
                        'IMAGES_RESO_O'             => $arrImageInfo[$intIdKey]['reso_o'],
                        'IMAGES_SIZE_T'             => $arrImageInfo[$intIdKey]['size_t'],
                        'IMAGES_RESO_T'             => $arrImageInfo[$intIdKey]['reso_t'],
                        'TXT_EXTENDED'              => $_ARRAYLANG['TXT_GALLERY_EXTENDED']
                    ));

                    $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
                    if (!empty($frontendLanguages)) {
                        foreach ($frontendLanguages as $frontendLanguage) {
                            $this->_objTpl->setVariable(array(
                                'NAMEFIELDS_IMID'       => $intIdKey,
                                'NAMEFIELDS_IMVALUE'    => $arrNames[$intIdKey][$frontendLanguage['id']],
                                'NAMEFIELDS_LID'        => $frontendLanguage['id'],
                                'NAMEFIELDS_LANGUAGE'   => $frontendLanguage['name'],
                            ));
                            $this->_objTpl->parse('showNameFields');
                        }
                    } else {
                        $this->_objTpl->hideBlock('showNameFields');
                    }

                    //blocks 1 and 2 were removed
                    for ($i=3;$i<=7;$i++) {
                        $this->_objTpl->setVariable('JS_IMAGE_ID'.$i,$intIdKey);
                        $this->_objTpl->parse('javascriptBlock'.$i);
                    }

                    try {
                        $this->parseCategoryDropdown(-1, true, "showCategoriesPerImage");
                    } catch (DatabaseError $e) {
                        $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
                        $this->strErrMessage .= $e;
                        return;
                    }
                    $this->_objTpl->parse('showImages');

                    $intTempImageId .= $intIdKey.','; //needed some lines below
                    $intRowColor++;
                }
                $intTempImageId = substr($intTempImageId,0,strlen($intTempImageId)-1);
                $this->_objTpl->setVariable('HIDDEN_ALL_IMAGES_IDS',$intTempImageId);
            }
        } else {
            $this->_objTpl->setVariable('VALIDATE_SHOWDETAILS','');
        }
    }


    /**
     * Reload the validation page / Validated a picture / delete a picture
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function reloadSingleValidate()
    {
        global $objDatabase, $_ARRAYLANG,$_CONFIG;

        $this->strPageTitle = $_ARRAYLANG['TXT_GALLERY_MENU_VALIDATE'];

        if ($_POST['validate_active'] == 1) {
            $intInsertStatus = 1;
        } else {
            $intInsertStatus = 0;
        }

        foreach ($_POST as $strKey => $strValue)
        {
            if (substr($strKey,0,strlen('imageName_')) == 'imageName_') {
                //language var
                $arrExplode = explode('_',$strKey,2);
                if (empty($strValue)) {
                    $strValue = $_ARRAYLANG['TXT_GALLERY_CATEGORY_NO_NAME'];
                } else {
                    $strValue = get_magic_quotes_gpc() ? strip_tags($strValue) : addslashes(strip_tags($strValue));
                }

                $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_language_pics
                                        SET     name="'.$strValue.'"
                                        WHERE     picture_id='.intval($_POST['validate_id']).' AND
                                                lang_id='.intval($arrExplode[1]).'
                                        LIMIT    1');
            }
        }

        if (!isset($_POST['validate_thumb_size']))
        { // no value, fill in the standard
            $_POST['validate_thumb_size'] = $this->arrSettings['standard_size_proz'];
        }
        if (!isset($_POST['validate_thumb_size_abs_height']))
        {// no value, fill in the standard
            $objResult = $objDatabase->Execute('SELECT     path
                                                FROM     '.DBPREFIX.'module_gallery_pictures
                                                WHERE     id='.intval($_POST['validate_id']));

            $arrImageInfo     = getimagesize($this->strImagePath.$objResult->fields['path']);
            $intOldWidth     = $arrImageInfo[0];
            $intOldHeight     = $arrImageInfo[1];
            $intNewWidth     = $this->arrSettings['standard_width_abs'];
            $intNewHeight     = $this->arrSettings['standard_height_abs'];

            if ($intNewWidth == 0) {
                $_POST['validate_thumb_size_abs_width'] = round(($intOldWidth * $intNewHeight) / $intOldHeight,0);
                $_POST['validate_thumb_size_abs_height'] = $intNewHeight;
            } else {
                $_POST['validate_thumb_size_abs_width']    = $intNewWidth;
                $_POST['validate_thumb_size_abs_height'] = round(($intOldHeight * $intNewWidth) / $intOldWidth,0);
            }
        }
    //update button
        if (isset($_POST['update_button'])) {
            // the user clicked on the update button
            $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_pictures
                                    SET     catid='.intval($_POST['validate_category']).',
                                            status="'.$intInsertStatus.'",
                                            name="'.contrexx_raw2db($_POST['validate_name']).'",
                                            size_type="'.addslashes($_POST['validate_thumb_size_selection']).'",
                                            size_proz='.intval($_POST['validate_thumb_size']).',
                                            size_abs_h='.intval($_POST['validate_thumb_size_abs_height']).',
                                            size_abs_w='.intval($_POST['validate_thumb_size_abs_width']).',
                                            quality='.intval($_POST['validate_thumb_quality']).'
                                    WHERE     id='.intval($_POST['validate_id']));
            $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_THUMBNAIL_UPDATED'];

        }
    //submit button
        if (isset($_POST['submit_button'])) {
            // the user validated the picture
            if ($_POST['validate_category'] != 0) {
                $objResult = $objDatabase->Execute('UPDATE     '.DBPREFIX.'module_gallery_pictures
                                                    SET     catid='.intval($_POST['validate_category']).',
                                                            validated="1",
                                                            status="'.$intInsertStatus.'",
                                                            lastedit='.time().',
                                                            size_type="'.addslashes($_POST['validate_thumb_size_selection']).'",
                                                            size_proz='.intval($_POST['validate_thumb_size']).',
                                                            size_abs_h='.intval($_POST['validate_thumb_size_abs_height']).',
                                                            size_abs_w='.intval($_POST['validate_thumb_size_abs_width']).',
                                                            quality='.intval($_POST['validate_thumb_quality']).'
                                                    WHERE     id='.intval($_POST['validate_id']));

                // and now create the final thumbnail
                $objResult = $objDatabase->Execute('SELECT     path,
                                                            size_type,
                                                            size_proz,
                                                            size_abs_h,
                                                            size_abs_w,
                                                            quality
                                                    FROM     '.DBPREFIX.'module_gallery_pictures
                                                    WHERE     id='.intval($_POST['validate_id']));

                $strOrgPath     = $this->strImagePath.$objResult->fields['path'];
                $strThumbPath     = $this->strThumbnailPath.$objResult->fields['path'];
                $arrImageInfos     = getimagesize($strOrgPath);

                if ($objResult->fields['size_type'] == 'proz') {
                    $intNewThumWidth     = floor(($objResult->fields['size_proz']/100) * $arrImageInfos[0]);
                    $intNewThumbHeight     = floor(($objResult->fields['size_proz']/100) * $arrImageInfos[1]);
                    $intNewThumbQuality = $objResult->fields['quality'];
                } else {
                    $intNewThumWidth     = $objResult->fields['size_abs_w'];
                    $intNewThumbHeight     = $objResult->fields['size_abs_h'];
                    $intNewThumbQuality = $objResult->fields['quality'];
                }

                //create thumb
// TODO: $strFileOld, $strFileNew are not initialized
$strFileOld = '';
$strFileNew = '';
                $this->createImages_JPG_GIF_PNG($strOrgPath, $strThumbPath, $strFileOld, $strFileNew, $intNewThumWidth, $intNewThumbHeight, $intNewThumbQuality);
                $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_THUMBNAIL_VALIDATED'];
            } else { // no category was selected, save the values and show an error message
                $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_pictures
                                        SET     catid='.intval($_POST['validate_category']).',
                                                status="'.$intInsertStatus.'",
                                                name="'.$_POST['validate_name'].'",
                                                size_type="'.addslashes($_POST['validate_thumb_size_selection']).'",
                                                size_proz='.intval($_POST['validate_thumb_size']).',
                                                size_abs_h='.intval($_POST['validate_thumb_size_abs_height']).',
                                                size_abs_w='.intval($_POST['validate_thumb_size_abs_width']).',
                                                quality='.intval($_POST['validate_thumb_quality']).'
                                        WHERE     id='.intval($_POST['validate_id']));

                $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_THUMBNAIL_NO_CATEGORY'];
            }
        }
    //delete button
        if (isset($_POST['delete_button'])) { // the user wants to delete this picture
            $objDatabase->Execute('    DELETE
                                    FROM     '.DBPREFIX.'module_gallery_pictures
                                    WHERE     id='.intval($_POST['validate_id']));
            $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_PICTURE_DELETED'];
        }
    }


    /**
     * Reload the validation page / Validated a picture / delete a picture
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function reloadAllValidate()
    {
        global $objDatabase,$_ARRAYLANG,$_CONFIG;

        $arrId = explode(',',$_POST['settingsAllImagesIds']);

        if ($_POST['settingsFilename'] != 1) { // update the filenames
            foreach ($_POST as $strKey => $strValue)
            {
                if (substr($strKey,0,strlen('imageName_')) == 'imageName_') {
                    //language var
                    $arrExplode = explode('_',$strKey,3);
                    $strValue = get_magic_quotes_gpc() ? strip_tags($strValue) : addslashes(strip_tags($strValue));
                    $objDatabase->Execute(' UPDATE     '.DBPREFIX.'module_gallery_language_pics
                                            SET     name="'.$strValue.'"
                                            WHERE     picture_id='.intval($arrExplode[1]).' AND
                                                    lang_id='.intval($arrExplode[2]).'
                                            LIMIT    1');
                }
            }
        }

        if ($_POST['settingsActive'] != 1) {
            // set the selected ones to active
            foreach ($arrId as $strValue)
            {
                $objDatabase->Execute(' UPDATE     '.DBPREFIX.'module_gallery_pictures
                                        SET     status="'.intval($_POST['imageActive'.$strValue]).'"
                                        WHERE     id='.intval($strValue));
            }
        }
        /////////////////////////////
        try {
            if ($_POST['settingsCategory'] != 1) {
                // the user definies the category by himself
                foreach ($arrId as $strValue) {
                    $catid = $_POST['imageCategory'.$strValue];
                    if (!$this->checkCategoryAccess($catid)) {
                        return;
                    }
                    $objDatabase->Execute(' UPDATE     '.DBPREFIX.'module_gallery_pictures
                                            SET     catid='.intval($_POST['imageCategory'.$strValue]).'
                                            WHERE     id='.intval($strValue));
                }
            } else { // all in the same group
                $catid = $_POST['imageCategoryAll'];
                if (!$this->checkCategoryAccess($catid)) {
                    return;
                }
                foreach ($arrId as $strValue) {
                    $objDatabase->Execute(' UPDATE     '.DBPREFIX.'module_gallery_pictures
                                            SET     catid='.intval($_POST['imageCategoryAll']).'
                                            WHERE     id='.intval($strValue));
                }
            }
        } catch (DatabaseError $e) {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_DATABASE_ERROR'];
            $this->strErrMessage .= $e;
            return;
        }
        /////////////

        if (isset($_POST['update_button'])) {
            // the user clicked on the update button, now i have to calculate the new values sizes for the database
            foreach ($arrId as $strValue) {
                $objResult = $objDatabase->Execute('SELECT     path
                                                    FROM     '.DBPREFIX.'module_gallery_pictures
                                                    WHERE     id='.intval($strValue));

                $arrImageInfo = getimagesize($this->strImagePath.$objResult->fields['path']);

                $intOldWidth = $arrImageInfo[0];
                $intOldHeight = $arrImageInfo[1];

                if (isset($_POST['settingsThumbSizeAbsWidth'])) {
                    $intNewWidth = $_POST['settingsThumbSizeAbsWidth'];
                } else {
                    $intNewWidth = $this->arrSettings['standard_width_abs'];
                }

                if (isset($_POST['settingsThumbSizeAbsHeight'])) {
                    $intNewHeight = $_POST['settingsThumbSizeAbsHeight'];
                } else {
                    $intNewHeight = $this->arrSettings['standard_height_abs'];
                }

                $intInsertQuality     = intval($_POST['settingsThumbQuality']);
                $strInsertType         = $_POST['settingsThumbSize'];

                if ($intNewWidth == 0) {
                    if($intNewHeight == 0)
                        $intNewHeight = $this->arrSettings['standard_height_abs'];
                    if($intNewHeight == 0) //set a standard value if the settings default to 0
                        $intNewHeight = 100;
                    $intNewWidth     = round(($intOldWidth * $intNewHeight) / $intOldHeight,0);
                } else if ($intNewHeight == 0){
                    $intNewHeight     = round(($intOldHeight * $intNewWidth) / $intOldWidth,0);
                }

                if ($_POST['settingsThumbSize'] == 'abs') {
                    $insertSizeProz = $this->arrSettings['standard_size_proz'];
                } else {
                    $insertSizeProz = $_POST['settingsThumbSizeProz'];
                }
                $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_pictures
                                        SET     size_type="'.addslashes($strInsertType).'",
                                                size_proz='.intval($insertSizeProz).',
                                                size_abs_h='.intval($intNewHeight).',
                                                size_abs_w='.intval($intNewWidth).',
                                                quality='.intval($intInsertQuality).'
                                        WHERE     id='.intval($strValue));
            }
        } else {
            // the user clicked on the insert button
            foreach ($arrId as $strValue) {

                $objDatabase->Execute('    UPDATE     '.DBPREFIX.'module_gallery_pictures
                                        SET     validated="1",
                                                lastedit='.time().'
                                        WHERE     id='.intval($strValue));

                // and now create the final thumbnail
                $objResult = $objDatabase->Execute('SELECT     path,
                                                            size_type,
                                                            size_proz,
                                                            size_abs_h,
                                                            size_abs_w,
                                                            quality
                                                    FROM     '.DBPREFIX.'module_gallery_pictures
                                                    WHERE     id='.intval($strValue));

                $strOrgPath     = $this->strImagePath.$objResult->fields['path'];
                $strThumbPath     = $this->strThumbnailPath.$objResult->fields['path'];
                $arrImageInfos     = getimagesize($strOrgPath);

                if ($objResult->fields['size_type'] == 'proz') {
                    $intNewThumWidth     = round(($objResult->fields['size_proz']/100) * $arrImageInfos[0],0);
                    $intNewThumbHeight     = round(($objResult->fields['size_proz']/100) * $arrImageInfos[1],0);
                    $intNewThumbQuality = $objResult->fields['quality'];
                } else {
                    $intNewThumWidth     = $objResult->fields['size_abs_w'];
                    $intNewThumbHeight     = $objResult->fields['size_abs_h'];
                    $intNewThumbQuality = $objResult->fields['quality'];
                }


                //create thumb
// TODO: $strFileOld, $strFileNew are not initialized
$strFileOld = '';
$strFileNew = '';
                $this->createImages_JPG_GIF_PNG($strOrgPath, $strThumbPath, $strFileOld, $strFileNew, $intNewThumWidth, $intNewThumbHeight, $intNewThumbQuality);
                $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_THUMBNAIL_VALIDATED'];
            }
        }
    }


    /**
     * Deletes an Image from the database
     *
     * @global    ADONewConnection
     * @global    array
     * @param     integer        $intImageId: Id of the image which should be delted
     */
    function deleteImage($intImageId)
    {
        global $objDatabase, $_ARRAYLANG;

        $intImageId = intval($intImageId);

        $objResult = $objDatabase->Execute('SELECT     path
                                            FROM     '.DBPREFIX.'module_gallery_pictures
                                            WHERE     id='.$intImageId);

        if (is_file($this->strImagePath.$objResult->fields['path'])) {
            @unlink($this->strImagePath.$objResult->fields['path']);
        }

         if (is_file($this->strThumbnailPath.$objResult->fields['path'])) {
            @unlink($this->strThumbnailPath.$objResult->fields['path']);
        }

        $objDatabase->Execute('    DELETE
                                FROM     '.DBPREFIX.'module_gallery_pictures
                                WHERE     id='.$intImageId);

        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_gallery_votes
                                WHERE    picid='.$intImageId.'
                            ');
        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_gallery_comments
                                WHERE    picid='.$intImageId.'
                            ');
        $objDatabase->Execute('    DELETE
                                FROM    '.DBPREFIX.'module_gallery_language_pics
                                WHERE    picture_id='.$intImageId.'
                            ');
        $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_STATUS_MESSAGE_PICTURE_DELETED'];
    }


    /**
     * Rotates an image clockwise by 90�
     *
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function rotatePicture($intImageId)
    {
        global $objDatabase,$_ARRAYLANG,$_CONFIG;

        if(!function_exists('imagerotate')){
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_GD_LIB_NOT_INSTALLED'];
            return false;
        }

        $objResult = $objDatabase->Execute('SELECT     path,
                                                    quality,
                                                    size_type,
                                                    size_proz,
                                                    size_abs_h,
                                                    size_abs_w
                                            FROM     '.DBPREFIX.'module_gallery_pictures
                                            WHERE     id='.$intImageId);

        $strImagename   = $objResult->fields['path'];
        $strOrgPath     = $this->strImagePath;
        $strWebpath     = $this->strImageWebPath;
        $strThumbPath   = $this->strThumbnailPath;
        $strThumbWebpath= $this->strThumbnailWebPath;

        $objImage = new \ImageManager();
        $objImage->loadImage($strOrgPath.$strImagename);

        //Rotate the clockwise
        if($objImage->rotateImage(270)){

            //To save the Rotated image
            if ($objImage->saveNewImage($strOrgPath . $strImagename, true)) {
                \Cx\Lib\FileSystem\FileSystem::delete_file($strThumbPath . $strImagename);
            }

            $imageSize      = $objImage->_getImageSize($strOrgPath . $strImagename);
            $intOldWidth    = $imageSize[0];
            $intOldHeight   = $imageSize[1];
            if ($objResult->fields['size_type'] == 'abs') {
                $intNewWidth    = intval($this->arrSettings['standard_width_abs']);
                $intNewHeight   = intval($this->arrSettings['standard_height_abs']);

                if ($intNewWidth == 0) {
                    // exception if width and height or 0!
                    if ($intNewHeight == 0) {
                        $intNewHeight = 100;
                    }
                    $intNewWidth = round(($intOldWidth * $intNewHeight) / $intOldHeight, 0);
                } else if ($intNewHeight == 0) {
                    $intNewHeight = round(($intOldHeight * $intNewWidth) / $intOldWidth, 0);
                }
            } else {
                $intNewWidth  = round(($objResult->fields['size_proz'] / 100) * $intOldWidth, 0);
                $intNewHeight = round(($objResult->fields['size_proz'] / 100) * $intOldHeight, 0);
            }

            //Resize the Rotated image
            if ($this->createImages_JPG_GIF_PNG($strOrgPath, $strThumbPath, $strImagename, $strImagename, $intNewWidth, $intNewHeight, $objResult->fields['quality'])) {
                if ($objResult->fields['size_type'] == 'abs') {
                    $objDatabase->Execute('    UPDATE     ' . DBPREFIX . 'module_gallery_pictures
                                    SET     size_abs_h=' . $intNewHeight . ',
                                            size_abs_w=' . $intNewWidth . '
                                    WHERE     id=' . $intImageId);
                }
            }
        }
    }


    /**
     * import Pictures (requires chmod 777 in folder gallery_import)
     *
     * @global    array
     * @global    array
     */
    function importPicture()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->loadTemplateFile('module_gallery_import_pictures.html',true,true);

        //get enabled filetypes
        $arrEnabledTypes = array();

        if ($this->boolGifEnabled == true) {
            $arrEnabledTypes[] = 1;
        }

        if ($this->boolJpgEnabled == true) {
            $arrEnabledTypes[] = 2;
        }

        if ($this->boolPngEnabled == true) {
            $arrEnabledTypes[] = 3;
        }

        //get images
        $o = 0;
        $handleDirectory = @opendir($this->strImportPath);
        $strName = @readdir($handleDirectory);
        while ($strName) {
            if ($strName != '.' && $strName != '..') {
                if (is_file($this->strImportPath . $strName) && $o < $this->intMaxEntries) {
                    $arrFileInfo=getimagesize($this->strImportPath.$strName);
                    $this->importFiles['name'][] = contrexx_raw2xhtml($strName);
                    $this->importFiles['size'][] = $this->_getSize($this->strImportPath.$strName);
                    $this->importFiles['type'][] = $this->_getType($this->strImportPath.$strName);
                    $this->importFiles['typeEnabled'][] = $arrFileInfo[2];
                    $this->importFiles['height'][] = $arrFileInfo[1];
                    $this->importFiles['width'][] = $arrFileInfo[0];
                    ++$o;
                }
            }
            $strName = @readdir($handleDirectory);
        }
        @closedir($handleDirectory);
        clearstatcache();

         $this->_objTpl->setVariable(array(
            'TXT_TITLE'                        =>    $_ARRAYLANG['TXT_GALLERY_MENU_UPLOAD_FORM'],
            'TXT_FILENAME'                        =>    $_ARRAYLANG['TXT_GALLERY_FILE_NAME'],
            'TXT_FILESIZE'                        =>    $_CORELANG['TXT_SIZE'],
            'TXT_FILETYPE'                        =>    $_ARRAYLANG['TXT_GALLERY_FILE_TYPE'],
            'TXT_IMAGENUMBER'                =>    $_ARRAYLANG['TXT_GALLERY_UPLOAD_FORM_IMAGE_NUMBER'],
            'TXT_IMPORT_MAKE_SELECTION'        =>    $_ARRAYLANG['TXT_GALLERY_MAKE_SELECTION'],
            'TXT_IMPORT_DELETE_PICTURE'        =>  $_ARRAYLANG['TXT_GALLERY_REALY_DELETE'],
            'TXT_MAX_PICTURES'                =>  $_ARRAYLANG['TXT_GALLERY_MAX_PICTURES'].' '.$this->intMaxEntries.' '.$_ARRAYLANG['TXT_GALLERY_COUNT_PICTURES'].$o,
            'IMPORT_IMAGE_TREE_NAV_MAIN'    => 'http://'.$_SERVER['HTTP_HOST'].$this->importWebPath
        ));

        // empty dir or php safe mode restriction
        if ($o==0 || !@opendir($this->strImportPath)) {
            if (!@opendir($this->strImportPath)) {
                $strTempMessage = $_ARRAYLANG['TXT_GALLERY_IMPORT_ERR_SAFEMODE'];
            } else {
                $strTempMessage = $_ARRAYLANG['TXT_GALLERY_IMPORT_ERR_DIR'];
            }

            $this->_objTpl->setVariable(array(
                'TXT_IMPORT_IMAGE_DIR_EMPTY'   => $strTempMessage,
            ));
            $this->_objTpl->parse('importEmptyDirectory');
        }
        // not empty dir (select action)
        else {
            //importDirectoryTree
            $i=0;
            $this->_objTpl->setCurrentBlock('importDirectoryTree');

            asort($this->importFiles['name']);

            foreach($this->importFiles['name'] as $intPicKey => $strPicName) {
                $intRowClass = ($i % 2) ? 'row2' : 'row1';
                if (in_array($this->importFiles['typeEnabled'][$intPicKey], $arrEnabledTypes)) {
                    $this->_objTpl->setVariable(array(
                        'IMPORT_IMAGE_DIR_TREE_ROW'    =>    $intRowClass,
                        'IMPORT_IMAGE_NAME'            =>    $strPicName,
                        'IMPORT_IMAGE_HEIGHT'        =>    $this->importFiles['height'][$intPicKey]+25,
                        'IMPORT_IMAGE_WIDTH'        =>    $this->importFiles['width'][$intPicKey]+20,
                        'IMPORT_IMAGE_PATH'            =>    $this->importWebPath.$strPicName,
                        'IMPORT_IMAGE_TYP'            =>    $this->importFiles['type'][$intPicKey],
                        'IMPORT_IMAGE_SIZE'            =>    $this->importFiles['size'][$intPicKey],
                        'IMPORT_IMAGE_KEY'            =>    $intPicKey,
                        'IMPORT_IMAGE_CHECKBOX'        =>  '<input type="checkbox" title="Select '.$strPicName.'" name="formSelected[]" value="'.$strPicName.'" />',
                    ));
                } else {
                    $this->_objTpl->setVariable(array(
                        'IMPORT_IMAGE_DIR_TREE_ROW'        =>    $intRowClass,
                        'IMPORT_IMAGE_NAME'                =>    $strPicName,
                        'IMPORT_IMAGE_HEIGHT'            =>    $this->importFiles['height'][$intPicKey]+25,
                        'IMPORT_IMAGE_WIDTH'            =>    $this->importFiles['width'][$intPicKey]+20,
                        'IMPORT_IMAGE_PATH'                =>    $this->importWebPath.$strPicName,
                        'IMPORT_IMAGE_TYP'                =>    $this->importFiles['type'][$intPicKey],
                        'IMPORT_IMAGE_SIZE'                =>    $this->importFiles['size'][$intPicKey],
                        'IMPORT_IMAGE_KEY'                =>    $intPicKey,
                        'IMPORT_IMAGE_CHECKBOX'            =>  '&nbsp;',
                        'IMPORT_IMAGE_NAME_ATTRIBUT'    =>    '<i><font color=#ff0000>('.$_ARRAYLANG['TXT_GALLERY_TYPE_NOT_SUPPORTED'].')</font></i>'
                    ));
                }
                $i++;
                $this->_objTpl->parseCurrentBlock('importDirectoryTree');
            }

            $this->_objTpl->setVariable(array(
                'TXT_IMPORT_IMAGE_SELECT_ACTION'      => $_ARRAYLANG['TXT_GALLERY_CHOOSE_ACTION'],
                'TXT_IMPORT_IMAGE_IMPORT'             => $_ARRAYLANG['TXT_GALLERY_IMPORT'],
                'TXT_IMPORT_IMAGE_DELETE'             => $_ARRAYLANG['TXT_GALLERY_DELETE']

            ));
            $this->_objTpl->parse('importSelectAction');
        }
    }


    /**
     * Get filesize
     *
     * @param    string        $strFile
     * @return     integer        $intSize: Size of the file
     */
    function _getSize($strFile)
    {
        if (is_file($strFile)) {
            if (@filesize($strFile)) {
                $intSize = filesize($strFile);
            }
        }

        (!isset($intSize) or empty($intSize)) ? $intSize = '0' : '';

        return $intSize;
    }


    /**
     * Get filetype
     *
     * @param    string        $strFile
     * @return     string        $strType: Type of the file
     */
    function _getType($strFile)
    {
        if (is_file($strFile)) {
            $info = pathinfo($strFile);
            $strType = strtoupper($info['extension']);
        }

        if (is_dir($strFile)) {
            $strType = '[folder]';
        }

        (!isset($strType) or empty($strType)) ? $strType = '-' : '';

        return $strType;
    }


    /**
     * Import selected pictures from folder
     *
     */
    function importFromFolder()
    {
        foreach($_POST['formSelected'] as $strPicName) {
            $this->movePicture($strPicName);
        }
    }


    /**
     * Move pictures from gallery_import to gallery
     *
     * @param    string        $strFile
     */
    function movePicture($strFile) {
        global $objDatabase, $_ARRAYLANG;

        //check if file exists
        $boolChecker = false;

        $strImportedImageName = \Cx\Lib\FileSystem\FileSystem::replaceCharacters($strFile);
        while ($boolChecker == false) {
            if (self::fileExists($this->strImagePath.$strImportedImageName, false)) {
                $info     = pathinfo($strImportedImageName);
                $exte     = $info['extension'];
                $exte     = (!empty($exte)) ? '.'.$exte : '';
                $part1    = $info['filename'];
                $strImportedImageName = $part1.'_'.time().$exte;
            } else {
                $boolChecker = true;
            }
        }



        // gets the quality
        $objResult = $objDatabase->Execute('SELECT     value
                                            FROM     '.DBPREFIX.'module_gallery_settings
                                              WHERE     name = "quality"');
        $intQuality = intval($objResult->fields['value']);
        $intSize    = getimagesize($this->strImportPath.$strFile);
        $intWidth    = $intSize[0];
        $intHeight    = $intSize[1];

        if ($intWidth > intval($this->arrSettings['image_width'])) {
            //Image-Width was bigger than the allowed value. Show Error.
            $this->strErrMessage = str_replace('{WIDTH}',$this->arrSettings['image_width'],$_ARRAYLANG['TXT_GALLERY_UPLOAD_ERROR_WIDTH']);
            return;
        } else {
            $this->createImages_JPG_GIF_PNG($this->strImportPath, $this->strImagePath, $strFile, $strImportedImageName, $intWidth, $intHeight, $intQuality);

            //insert image in db
            $strDatabasePath = $strImportedImageName;
            self::insertImage($this, $strDatabasePath,$strImportedImageName);

            //delete imported images
            if (file_exists($this->strImagePath.$strImportedImageName)) {
               unlink($this->strImportPath.$strFile);
            }
        }
    }


    /**
     * Delete selected pictures
     */
    function deleteImportPicture()
    {
        if (!isset($_GET['pic'])) {
            foreach($_POST['formSelected'] as $strPicName) {
                unlink($this->strImportPath.$strPicName);
            }
        } else {
            unlink($this->strImportPath.$_GET['pic']);
        }
    }


    /**
     * Create an Image
     * @param     string        $strPathOld: The old path of the image
     * @param     string        $strPathNew: The new path for the created image
     * @param     string        $strFileOld: The name of the old file
     * @param     string        $strFileNew: The name of the new file
     * @param     integer        $intNewWidth: Width of the new image
     * @param     integer        $intNewHeight: Height of the new image
     * @param     integer        $intQuality: Quality of the new image
     */
    function createImages_JPG_GIF_PNG($strPathOld, $strPathNew, $strFileOld, $strFileNew, $intNewWidth, $intNewHeight, $intQuality)
    {
        global $_ARRAYLANG;

        //TODO: sometimes, strings are passed... this is a workaround
        $intNewWidth = intval($intNewWidth);
        $intNewHeight = intval($intNewHeight);
        //copy image
        $intSize    = getimagesize($strPathOld.$strFileOld); //ermittelt die Gr��e des Bildes
        $intWidth    = $intSize[0]; //die Breite des Bildes
        $intHeight    = $intSize[1]; //die H�he des Bildes
        $strType    = $intSize[2]; //type des Bildes

        if (file_exists($strPathNew.$strFileNew)) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($strPathNew.$strFileNew);
        } else {
            try {
                $objFile = new \Cx\Lib\FileSystem\File($strPathNew.$strFileNew);
                $objFile->touch();
                $objFile->makeWritable();
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
            }
        }
// TODO: Unfortunately, the functions imagegif(), imagejpeg() and imagepng() can't use the Cloudrexx FileSystem wrapper,
//       therefore we need to set the global write access image files.
//       This issue might be solved by using the output-buffer and write the image manually afterwards.
//
//       IMPORTANT: In case something went wrong (see bug #1441) and the path $strPathNew.$strFileNew refers to a directory
//       we must abort the operation here, otherwise we would remove the execution flag on a directory, which would
//       cause to remove any browsing access to the directory.
        if (is_dir($strPathNew.$strFileNew)) {
            return false;
        }
        \Cx\Lib\FileSystem\FileSystem::chmod($strPathNew.$strFileNew, 0666);//\Cx\Lib\FileSystem\FileSystem::CHMOD_FILE);

        //fix cases of zeroes
        if ($intNewWidth == 0) {
            if($intNewHeight == 0)
                $intNewHeight = $this->arrSettings['standard_height_abs'];
            if($intNewHeight == 0) //set a standard value if the settings default to 0
                $intNewHeight = 100;
            $intNewWidth     = round(($intWidth * $intNewHeight) / $intHeight,0);
        } else if ($intNewHeight == 0){
            $intNewHeight     = round(($intHeight * $intNewWidth) / $intWidth,0);
        }

        $objSystem = new \FWSystem();
        if ($objSystem === false) {
            return false;
        }

        if (is_array($intSize)) {
            $memoryLimit = $objSystem->getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
            // a $memoryLimit of zero means that there is no limit. so let's try it and hope that the host system has enough memory
            if (!empty($memoryLimit)) {
               $potentialRequiredMemory = $intSize[0] * $intSize[1] * ($intSize['bits']/8) * $intSize['channels'] * 1.8 * 2;
                if (function_exists('memory_get_usage')) {
                    $potentialRequiredMemory += memory_get_usage();
                } else {
                    // add a default of 10 MBytes
                    $potentialRequiredMemory += 10*pow(1024, 2);
                }

                if ($potentialRequiredMemory > $memoryLimit) {
                    // try to set a higher memory_limit
                    @ini_set('memory_limit', $potentialRequiredMemory);
                    $curr_limit = $objSystem->getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
                    if ($curr_limit < $potentialRequiredMemory) {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }

        $imageManager = new \ImageManager();

        // load raw image
        if (!$imageManager->loadImage($strPathOld.$strFileOld)) {
            return false;
        }

        // calculate the scale ratios
        $rationWidth = $imageManager->orgImageWidth / $intNewWidth;
        $rationHeight = $imageManager->orgImageHeight / $intNewHeight;

        // crop the image to new dimension
        if ($rationWidth < $rationHeight) {
            $imageManager->orgImageHeight = $imageManager->orgImageHeight / $rationHeight * $rationWidth;
        } else {
            $imageManager->orgImageWidth = $imageManager->orgImageWidth / $rationWidth * $rationHeight;
        }

        // scale the image to thumbnail-size
        if (!$imageManager->resizeImage(
            $intNewWidth,
            $intNewHeight,
            $this->arrSettings['standard_quality']
        )) {
            return false;
        }
        return $imageManager->saveNewImage($strPathNew.$strFileNew, true);
    }


    /**
     * Delete a comment of a picture
     * @global     ADONewConnection
     * @global     array
     * @param     integer        $intComId: The id of the comment which should be deleted
     */
    function deleteComment($intComId)
    {
        global $objDatabase,$_ARRAYLANG;

        $intComId = intval($intComId);

        if ($intComId != 0) {
            $objDatabase->Execute('    DELETE
                                    FROM    '.DBPREFIX.'module_gallery_comments
                                    WHERE    id='.$intComId.'
                                    LIMIT    1
                                ');
            $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_COMMENT_DELETE_DONE'];
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_COMMENT_DELETE_ERROR'];
        }
    }


    /**
     * Delete a voting of a picture
     * @global     ADONewConnection
     * @global     array
     * @param     integer        $intComId: The id of the comment which should be deleted
     */
    function deleteVote($intVoteId)
    {
        global $objDatabase,$_ARRAYLANG;

        $intVoteId = intval($intVoteId);

        if ($intVoteId != 0) {
            $objDatabase->Execute('    DELETE
                                    FROM    '.DBPREFIX.'module_gallery_votes
                                    WHERE    id='.$intVoteId.'
                                    LIMIT    1
                                ');
            $this->strOkMessage = $_ARRAYLANG['TXT_GALLERY_VOTE_DELETE_DONE'];
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_VOTE_DELETE_ERROR'];
        }
    }


    /**
     * Edit a comment of a picture
     * @global     ADONewConnection
     * @global     array
     * @param     integer        $intCommentId: The comment with this id will be shown in the form
     */
    function showEditComment($intCommentId)
    {
        global $objDatabase,$_ARRAYLANG;

        $intCommentId = intval($intCommentId);

        $this->_objTpl->loadTemplateFile('module_gallery_edit_comment.html',true,true);
        $this->_objTpl->SetVariable(array(    'TXT_COMMENT_EDIT_TITLE'    =>    $_ARRAYLANG['TXT_COMMENT_EDIT'],
                                            'TXT_COMMENT_EDIT_DATE'        =>    $_ARRAYLANG['TXT_COMMENT_EDIT_DATE'],
                                            'TXT_COMMENT_EDIT_NAME'        =>    $_ARRAYLANG['TXT_COMMENT_EDIT_NAME'],
                                            'TXT_COMMENT_EDIT_EMAIL'    =>    $_ARRAYLANG['TXT_COMMENT_EDIT_EMAIL'],
                                            'TXT_COMMENT_EDIT_HOMEPAGE'    =>    $_ARRAYLANG['TXT_COMMENT_EDIT_HOMEPAGE'],
                                            'TXT_COMMENT_EDIT_TEXT'        =>    $_ARRAYLANG['TXT_COMMENT_EDIT_TEXT'],
                                            'TXT_COMMENT_EDIT_SUBMIT'    =>    $_ARRAYLANG['TXT_COMMENT_EDIT_SUBMIT']
                                    ));

        $objResult = $objDatabase->Execute('    SELECT    picid,
                                                        `date`,
                                                        name,
                                                        email,
                                                        www,
                                                        comment
                                                FROM    '.DBPREFIX.'module_gallery_comments
                                                WHERE    id='.$intCommentId.'
                                                LIMIT    1
                                            ');
        $this->_objTpl->SetVariable(array(    'VALUE_COMMENT_EDIT_ID'            =>    $intCommentId,
                                            'VALUE_COMMENT_EDIT_PICID'        =>    $objResult->fields['picid'],
                                            'VALUE_COMMENT_EDIT_DATE'        =>    date('d.m.Y',$objResult->fields['date']),
                                            'VALUE_COMMENT_EDIT_NAME'        =>    $objResult->fields['name'],
                                            'VALUE_COMMENT_EDIT_EMAIL'        =>    $objResult->fields['email'],
                                            'VALUE_COMMENT_EDIT_HOMEPAGE'    =>    $objResult->fields['www'],
                                            'VALUE_COMMENT_EDIT_TEXT'        =>    $objResult->fields['comment']
                                    ));
    }


    /**
     * Save all changes done to a comment
     *
     * @global     ADONewConnection
     * @global     array
     */
    function updateComment()
    {
        global $objDatabase,  $_ARRAYLANG;

        $intCommentId     = intval($_POST['frmEditComment_Id']);
        $strName         = htmlspecialchars(strip_tags($_POST['frmEditComment_Name']), ENT_QUOTES, CONTREXX_CHARSET);
        $strEMail        = htmlspecialchars(strip_tags($_POST['frmEditComment_Email']), ENT_QUOTES, CONTREXX_CHARSET);
        $strWWW            = htmlspecialchars(strip_tags($_POST['frmEditComment_Homepage']), ENT_QUOTES, CONTREXX_CHARSET);
        $strComment     = htmlspecialchars(strip_tags($_POST['frmEditComment_Text']), ENT_QUOTES, CONTREXX_CHARSET);

        if (!empty($strWWW) && $strWWW != 'http://') {
            if (substr($strWWW,0,7) != 'http://') {
                $strWWW = 'http://'.$strWWW;
            }
        } else {
            $strWWW = '';
        }

        if ($intCommentId != 0 &&
            !empty($strName) &&
            !empty($strComment))
        {
            $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_gallery_comments
                                    SET        name="'.$strName.'",
                                            email="'.$strEMail.'",
                                            www="'.$strWWW.'",
                                            comment="'.$strComment.'"
                                    WHERE    id='.$intCommentId.'
                                    LIMIT    1'
                                );

            $this->strOkMessage = $_ARRAYLANG['TXT_COMMENT_EDIT_SAVED'];
        }
    }


    /**
     * Define an images as "category-image". This image will be shown in the category-selection.
     * @global     ADONewConnection
     * @param    integer        $intImgId
     */
    function setCategoryImage($intImgId)
    {
        global $objDatabase;

        $intImgId = intval($intImgId);
        if ($intImgId > 0) {
            $objResult = $objDatabase->Execute('SELECT    catid
                                                FROM    '.DBPREFIX.'module_gallery_pictures
                                                WHERE    id='.$intImgId.'
                                                LIMIT    1
                                            ');
            if ($objResult->RecordCount() == 1) {
                $intCatId = intval($objResult->fields['catid']);

                $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_gallery_pictures
                                        SET        catimg="0"
                                        WHERE    catid='.$intCatId
                                    );
                $objDatabase->Execute('    UPDATE    '.DBPREFIX.'module_gallery_pictures
                                        SET        catimg="1"
                                        WHERE    id='.$intImgId
                                    );
            }
        }

    }


    /**
     * Returns an array of strings containing dropdown menu options with
     * existing (but not assigned) and assigned access groups for the
     * category with the given ID.
     * @param   integer   $id
     * @param   string    $type
     * @return  array
     */
    private function getGroupLists($id, $type="frontend")
    {
        $accessGroups = $this->sql->getAccessGroups($type, false, $id);
        $allGroups = $this->sql->getAllGroups($type);
        $assignedGroups = "";
        $existingGroups = "";
        foreach ($allGroups as $id => $name) {
            if (in_array($id, $accessGroups)) {
                $assignedGroups .= '<option value="'.$id.'">'.$name."</option>\n";
            } else {
                $existingGroups .= '<option value="'.$id.'">'.$name."</option>\n";
            }
        }
        return Array($existingGroups, $assignedGroups);
    }


    /**
     * Check the access to the category with the given ID
     * @param   integer   $id
     * @return  boolean
     */
    private function checkCategoryAccess($id)
    {
        global $_ARRAYLANG;

        list($access_id, $protected) = $this->sql->getPrivileges($id, "backend");
        if ($protected) {
            if (!$this->checkAccess($access_id)) {
                $this->strErrMessage = $_ARRAYLANG['TXT_GALLERY_CATEGORY_STATUS_MESSAGE_ACCESS_ERROR'];
                return false;
            }
        }

        return true;
    }


    /**
     * Check access
     * @param int $access_id
     * @return int
     */
    private function checkAccess($access_id)
    {
        $objFWUser = \FWUser::getFWUserObject();
        if ($objFWUser->objUser->getAdminStatus()) {
            return true;
        }

        $accessGroups = $this->sql->getAccessGroups("backend", $access_id);
        $userGroups = $objFWUser->objUser->getAssociatedGroupIds();
        foreach ($accessGroups as $group) {
            if (in_array(intval($group),$userGroups)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Save the last access id
     * @param intval $id
     */
    private function updateAccessId($id)
    {
        \Cx\Core\Setting\Controller\Setting::init('Config', 'core','Yaml');
        if (isset($id)) {
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('lastAccessId')) {
                \Cx\Core\Setting\Controller\Setting::add('lastAccessId', $id, 1, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, '', 'core');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('lastAccessId', $id);
                \Cx\Core\Setting\Controller\Setting::update('lastAccessId');
            }
        }
    }

    public static function fileExists($fileName, $caseSensitive = true) {
        if (file_exists($fileName)) {
            return $fileName;
        }
        if ($caseSensitive) {
            return false;
        }

        // Handle case insensitive requests
        $directoryName     = dirname($fileName);
        $fileArray         = glob($directoryName . '/*', GLOB_NOSORT);
        $fileNameLowerCase = strtolower($fileName);
        foreach ($fileArray as $file) {
            if (strtolower($file) == $fileNameLowerCase) {
                return true;
            }
        }
        return false;
    }
}
