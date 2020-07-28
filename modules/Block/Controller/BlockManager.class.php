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
 * Block
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.1
 * @package     cloudrexx
 * @subpackage  module_block
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Block\Controller;
use Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser;

/**
 * Block
 *
 * block module class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.1
 * @package     cloudrexx
 * @subpackage  module_block
 */
class BlockManager extends \Cx\Modules\Block\Controller\BlockLibrary
{
    /**
    * Template object
    *
    * @access private
    * @var object
    */
    var $_objTpl;

    /**
    * Page title
    *
    * @access private
    * @var string
    */
    var $_pageTitle;

    /**
    * Okay message
    *
    * @access private
    * @var string
    */
    var $_strOkMessage = '';

    /**
    * error message
    *
    * @access private
    * @var string
    */
    var $_strErrMessage = '';

    /**
     * row class index
     *
     * @var integer
     */
    var $_index = 0;

    private $act = '';

    /**
    * PHP5 constructor
    *
    * @global \Cx\Core\Html\Sigma
    * @global array
    * @global array
    * @global array
    */
    function __construct()
    {
        global $_ARRAYLANG;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Block/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        if (isset($_GET['added']) && $_GET['added'] == 'true') {
            if (!empty($_GET['blockname'])) {
                $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_BLOCK_BLOCK_ADDED_SUCCESSFULLY'], contrexx_raw2xhtml($_GET['blockname']));
            } else {
                $this->_strOkMessage = $_ARRAYLANG['TXT_BLOCK_BLOCK_ADDED_SUCCESSFULLY'];
            }
        }

        if (isset($_GET['modified']) && $_GET['modified'] == 'true') {
            if (!empty($_GET['blockname'])) {
                $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_BLOCK_BLOCK_UPDATED_SUCCESSFULLY'], contrexx_raw2xhtml($_GET['blockname']));
            } else {
                $this->_strOkMessage = $_ARRAYLANG['TXT_BLOCK_BLOCK_UPDATED_SUCCESSFULLY'];
            }
        }

        if (isset($_POST['saveSettings'])) {
            $arrSettings = array(
                'blockStatus'   => isset($_POST['blockUseBlockSystem']) ? intval($_POST['blockUseBlockSystem']) : 0,
                'blockRandom'   => isset($_POST['blockUseBlockRandom']) ? intval($_POST['blockUseBlockRandom']) : 0
            );
            $this->_saveSettings($arrSettings);
            $this->_strOkMessage = $_ARRAYLANG['TXT_SETTINGS_UPDATED'];
        }

    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG, $_CONFIG;

        $objTemplate->setVariable("CONTENT_NAVIGATION", "   "
            .($_CONFIG['blockStatus'] == '1'
                 ? "<a href='index.php?cmd=Block&amp;act=overview' class='".($this->act == '' || $this->act == 'overview' || $this->act == 'del' ? 'active' : '')."'>".$_ARRAYLANG['TXT_BLOCK_OVERVIEW']."</a>
                    <a href='index.php?cmd=Block&amp;act=modify" . (isset($_GET['catId']) ? '&amp;catId=' . contrexx_input2int($_GET['catId']) : '') . "' class='".($this->act == 'modify' ? 'active' : '')."'>".$_ARRAYLANG['TXT_BLOCK_ADD_BLOCK']."</a>"
                 : "")
            ."<a href='index.php?cmd=Block&amp;act=categories' class='".($this->act == 'categories' ? 'active' : '')."'>".$_ARRAYLANG['TXT_BLOCK_CATEGORIES']."</a>"
             ."<a href='index.php?cmd=Block&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_BLOCK_SETTINGS']."</a>");
    }

    /**
    * Get page
    *
    * Get a page of the block system administration
    *
    * @access public
    * @global \Cx\Core\Html\Sigma
    * @global array
    */
    function getPage()
    {
        global $objTemplate, $_CONFIG;

        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        }

        if ($_CONFIG['blockStatus'] != '1') {
            $_REQUEST['act'] = 'settings';
        }

        \JS::activate('jquery');

        switch ($_REQUEST['act']) {
        case 'modify':
            $this->_showModifyBlock();
            break;

        case 'copy':
            $this->_showModifyBlock(true);
            break;

        case 'settings':
            $this->_showSettings();
            break;

        case 'del':
            $this->_delBlock();
            $this->_showOverview();
            break;

        case 'activate':
            $this->_activateBlock();
            $this->_showOverview();
            break;

        case 'deactivate':
            $this->_deactivateBlock();
            $this->_showOverview();
            break;

        case 'global':
            $this->_globalBlock();
            $this->_showOverview();
            break;

        case 'global_off':
            $this->_globalBlockOff();
            $this->_showOverview();
            break;
        case 'categories':
            if(!empty($_POST['frmCategorySubmit'])){
                $this->saveCategory();
            }
            $this->showCategories();
            break;
        case 'editCategory':
            $this->editCategory();
            break;
        case 'deleteCategory':
            $this->deleteCategory();
            $this->showCategories();
            break;
        case 'multiactionCategory':
            $this->doEntryMultiAction($_REQUEST['frmShowCategoriesMultiAction']);
            $this->showCategories();
            break;
        default:
            $this->_showOverview();
            break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->_pageTitle,
            'CONTENT_OK_MESSAGE'        => $this->_strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->_strErrMessage,
            'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }

    /**
    * Show overview
    *
    * Show the blocks overview page
    *
    * @access private
    * @global array
    * @global ADONewConnection
    * @global array
    * @see blockLibrary::getBlocks(), blockLibrary::blockNamePrefix
    */
    function _showOverview()
    {
        global $_ARRAYLANG, $objDatabase, $_CORELANG;

        if (isset($_POST['displaysubmit'])) {
            foreach ($_POST['displayorder'] as $blockId => $value){
                $query = "UPDATE ".DBPREFIX."module_block_blocks SET `order`='".intval($value)."' WHERE id='".intval($blockId)."'";
                $objDatabase->Execute($query);
                // I guess this does not work in this case, but since
                // the current implementation of block cache will be replaced
                // in CLX-1547 we just try:
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCachePage(
                    'Block',
                    'getBlockContent',
                    array(
                        'block' => $blockId,
                    )
                );
            }
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_BLOCK_BLOCKS'];
        $this->_objTpl->loadTemplateFile('module_block_overview.html');

        $catId = !empty($_REQUEST['catId']) ? contrexx_input2int($_REQUEST['catId']) : 0;

        $this->_objTpl->setVariable(array(
            'TXT_BLOCK_BLOCKS'                  => $_ARRAYLANG['TXT_BLOCK_BLOCKS'],
            'TXT_BLOCK_NAME'                    => $_ARRAYLANG['TXT_BLOCK_NAME'],
            'TXT_BLOCK_PLACEHOLDER'             => $_ARRAYLANG['TXT_BLOCK_PLACEHOLDER'],
            'TXT_BLOCK_SUBMIT_SELECT'           => $_ARRAYLANG['TXT_BLOCK_SUBMIT_SELECT'],
            'TXT_BLOCK_SUBMIT_DELETE'           => $_ARRAYLANG['TXT_BLOCK_SUBMIT_DELETE'],
            'TXT_BLOCK_SUBMIT_ACTIVATE'         => $_ARRAYLANG['TXT_BLOCK_SUBMIT_ACTIVATE'],
            'TXT_BLOCK_SUBMIT_DEACTIVATE'       => $_ARRAYLANG['TXT_BLOCK_SUBMIT_DEACTIVATE'],
            'TXT_BLOCK_SUBMIT_GLOBAL'           => $_ARRAYLANG['TXT_BLOCK_SUBMIT_GLOBAL'],
            'TXT_BLOCK_SUBMIT_GLOBAL_OFF'       => $_ARRAYLANG['TXT_BLOCK_SUBMIT_GLOBAL_OFF'],
            'TXT_BLOCK_SELECT_ALL'              => $_ARRAYLANG['TXT_BLOCK_SELECT_ALL'],
            'TXT_BLOCK_DESELECT_ALL'            => $_ARRAYLANG['TXT_BLOCK_DESELECT_ALL'],
            'TXT_BLOCK_PLACEHOLDER'             => $_ARRAYLANG['TXT_BLOCK_PLACEHOLDER'],
            'TXT_BLOCK_FUNCTIONS'               => $_ARRAYLANG['TXT_BLOCK_FUNCTIONS'],
            'TXT_BLOCK_DELETE_SELECTED_BLOCKS'  => $_ARRAYLANG['TXT_BLOCK_DELETE_SELECTED_BLOCKS'],
            'TXT_BLOCK_CONFIRM_DELETE_BLOCK'    => $_ARRAYLANG['TXT_BLOCK_CONFIRM_DELETE_BLOCK'],
            'TXT_SAVE_CHANGES'                  => $_CORELANG['TXT_SAVE_CHANGES'],
            'TXT_BLOCK_OPERATION_IRREVERSIBLE'  => $_ARRAYLANG['TXT_BLOCK_OPERATION_IRREVERSIBLE'],
            'TXT_BLOCK_STATUS'                  => $_ARRAYLANG['TXT_BLOCK_STATUS'],
            'TXT_BLOCK_CATEGORY'                => $_ARRAYLANG['TXT_BLOCK_CATEGORY'],
            'TXT_BLOCK_CATEGORIES_ALL'          => $_ARRAYLANG['TXT_BLOCK_CATEGORIES_ALL'],
            'TXT_BLOCK_ORDER'                   => $_ARRAYLANG['TXT_BLOCK_ORDER'],
            'TXT_BLOCK_LANGUAGE'                => $_ARRAYLANG['TXT_BLOCK_LANGUAGE'],
            'TXT_BLOCK_INCLUSION'               => $_ARRAYLANG['TXT_BLOCK_INCLUSION'],
            'BLOCK_CATEGORIES_DROPDOWN'         => $this->_getCategoriesDropdown($catId),
            'DIRECTORY_INDEX'                   => CONTREXX_DIRECTORY_INDEX,
            'CSRF_PARAM'                        => \Cx\Core\Csrf\Controller\Csrf::param(),
            'BLOCK_FILTER_CATEGORY_ID'          => $catId
        ));

        $arrBlocks = $this->getBlocks($catId);

        if (empty($arrBlocks)) {
            return;
        }

        // create new ContentTree instance
        $objContentTree = new \ContentTree();
        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        $rowNr = 0;
        foreach ($arrBlocks as $blockId => $arrBlock) {
            if ($arrBlock['active'] ==  '1') {
                $status = '<a href="index.php?cmd=Block&amp;act=deactivate&amp;blockId=' . $blockId . '&amp;catId=' . $catId . '" title="'.$_ARRAYLANG['TXT_BLOCK_ACTIVE'].'"><img src="../core/Core/View/Media/icons/led_green.gif" width="13" height="13" border="0" alt="'.$_ARRAYLANG['TXT_BLOCK_ACTIVE'].'" /></a>';
            } else {
                $status = '<a href="index.php?cmd=Block&amp;act=activate&amp;blockId=' . $blockId . '&amp;catId=' . $catId . '" title="'.$_ARRAYLANG['TXT_BLOCK_INACTIVE'].'"><img src="../core/Core/View/Media/icons/led_red.gif" width="13" height="13" border="0" alt="'.$_ARRAYLANG['TXT_BLOCK_INACTIVE'].'" /></a>';
            }

            $blockPlaceholder = $this->blockNamePrefix . $blockId;

            $random1Class = ($arrBlock['random']  ==  1) ? 'active' : '';
            $random2Class = ($arrBlock['random2'] ==  1) ? 'active' : '';
            $random3Class = ($arrBlock['random3'] ==  1) ? 'active' : '';
            $random4Class = ($arrBlock['random4'] ==  1) ? 'active' : '';
            $globalClass  = in_array($arrBlock['global'], array(1, 2)) ? 'active' : '';

            $random1Info = sprintf(($arrBlock['random']  ? $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_INCLUDED'] : $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_EXCLUDED']), '[[BLOCK_RANDOMIZER]]');
            $random2Info = sprintf(($arrBlock['random2'] ? $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_INCLUDED'] : $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_EXCLUDED']), '[[BLOCK_RANDOMIZER2]]');
            $random3Info = sprintf(($arrBlock['random3'] ? $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_INCLUDED'] : $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_EXCLUDED']), '[[BLOCK_RANDOMIZER3]]');
            $random4Info = sprintf(($arrBlock['random4'] ? $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_INCLUDED'] : $_ARRAYLANG['TXT_BLOCK_RANDOM_INFO_EXCLUDED']), '[[BLOCK_RANDOMIZER4]]');

            $lang = array();
            foreach ($arrBlock['lang'] as $langId) {
                $lang[] = \FWLanguage::getLanguageCodeById($langId);
            }
            $langString = implode(', ',$lang);

            $strGlobalSelectedPages = ($arrBlock['global'] == 2)
                                        ? $this->getSelectedPages($blockId, 'global', $objContentTree, $pageRepo)
                                        : '';
            $strDirectSelectedPages = ($arrBlock['direct'] == 1)
                                        ? $this->getSelectedPages($blockId, 'direct', $objContentTree, $pageRepo)
                                        : '';

            $targeting      = $this->loadTargetingSettings($blockId);
            $targetingClass = '';
            $targetingInfo = '';
            if (!empty($targeting)) {
                $targetingClass       = 'active';
                $arrSelectedCountries = array();
                if (!empty($targeting['country']) && !empty($targeting['country']['value'])) {
                    $targetingInfo = $targeting['country']['filter'] == 'include' ? $_ARRAYLANG['TXT_BLOCK_TARGETING_INFO_INCLUDE'] : $_ARRAYLANG['TXT_BLOCK_TARGETING_INFO_EXCLUDE'];
                    foreach ($targeting['country']['value'] as $countryId) {
                        $countryName = \Cx\Core\Country\Controller\Country::getNameById($countryId);
                        if (!empty($countryName)) {
                            $arrSelectedCountries[] = '<li>'.contrexx_raw2xhtml($countryName).'</li>';
                        }
                    }
                }
                if ($arrSelectedCountries) {
                    $targetingInfo .= '<br /><ul>'.implode($arrSelectedCountries).'</ul>';
                }
            }

            $blockDirectInfo = sprintf(($arrBlock['direct'] == 1 ? $_ARRAYLANG['TXT_BLOCK_DIRECT_INFO_SHOW_SELECTED_PAGES'] : $_ARRAYLANG['TXT_BLOCK_DIRECT_INFO_SHOW_ALL_PAGES']), '[['. $blockPlaceholder .']]');
            $this->_objTpl->setVariable(array(
                'BLOCK_ROW_CLASS'             => $rowNr % 2 ? "row1" : "row2",
                'BLOCK_ID'                    => $blockId,
                'BLOCK_RANDOM_1_CLASS'        => $random1Class,
                'BLOCK_RANDOM_2_CLASS'        => $random2Class,
                'BLOCK_RANDOM_3_CLASS'        => $random3Class,
                'BLOCK_RANDOM_4_CLASS'        => $random4Class,
                'BLOCK_RANDOM_1_INFO'         => $random1Info,
                'BLOCK_RANDOM_2_INFO'         => $random2Info,
                'BLOCK_RANDOM_3_INFO'         => $random3Info,
                'BLOCK_RANDOM_4_INFO'         => $random4Info,
                'BLOCK_TARGETING_CLASS'       => $targetingClass,
                'BLOCK_TARGETING_INFO'        => !empty($targeting)
                                                    ? $targetingInfo
                                                    : $_ARRAYLANG['TXT_BLOCK_LOCATION_BASED_DISPLAY_INFO'],
                'BLOCK_GLOBAL_CLASS'          => $globalClass,
                'BLOCK_GLOBAL_INFO'           => ($arrBlock['global'] == 1)
                                                    ? $_ARRAYLANG['TXT_BLOCK_DISPLAY_ALL_PAGE']
                                                    : (($arrBlock['global'] == 2)
                                                            ? $_ARRAYLANG['TXT_BLOCK_DISPLAY_SELECTED_PAGE'] . '<br />' . $strGlobalSelectedPages
                                                            : $_ARRAYLANG['TXT_BLOCK_DISPLAY_GLOBAL_INACTIVE']
                                                      ),
                'BLOCK_CATEGORY_NAME'         => $this->_categoryNames[$arrBlock['cat']],
                'BLOCK_ORDER'                 => $arrBlock['order'],
                'BLOCK_PLACEHOLDER'           => $blockPlaceholder,
                'BLOCK_PLACEHOLDER_INFO'      => ($arrBlock['direct'] == 1)
                                                    ? $blockDirectInfo . '<br />' . $strDirectSelectedPages
                                                    : $blockDirectInfo,
                'BLOCK_NAME'                  => contrexx_raw2xhtml($arrBlock['name']),
                'BLOCK_MODIFY'                => sprintf($_ARRAYLANG['TXT_BLOCK_MODIFY_BLOCK'], contrexx_raw2xhtml($arrBlock['name'])),
                'BLOCK_COPY'                  => sprintf($_ARRAYLANG['TXT_BLOCK_COPY_BLOCK'], contrexx_raw2xhtml($arrBlock['name'])),
                'BLOCK_DELETE'                => sprintf($_ARRAYLANG['TXT_BLOCK_DELETE_BLOCK'], contrexx_raw2xhtml($arrBlock['name'])),
                'BLOCK_STATUS'                => $status,
                'BLOCK_LANGUAGES_NAME'        => $langString,
                'BLOCK_CATEGORY_ID'           => $catId
            ));
            $this->_objTpl->parse('blockBlockList');

            $rowNr ++;
        }
    }

    /**
     * Get selected pages for a block to display in overview page
     * @see $this->_showOverview()
     *
     * @param integer                                                 $blockId            Block id
     * @param string                                                  $placeholder        Placeholder (global, direct)
     * @param \ContentTree                                            $objContentTree     ContentTree instance
     * @param \Cx\Core\ContentManager\Model\Repository\PageRepository $pageRepo           PageRepository instance
     *
     * @return string Return the selected pages as <ul><li></li></ul>
     */
    function getSelectedPages($blockId, $placeholder, \ContentTree $objContentTree, \Cx\Core\ContentManager\Model\Repository\PageRepository $pageRepo)
    {
        $pageLinkTemplate       = '<li><a href="%1$s" target="_blank">%2$s</a></li>';
        $blockAssociatedPageIds = $this->_getAssociatedPageIds($blockId, $placeholder);

        $selectedPages    = array();
        $strSelectedPages = '';
        foreach ($objContentTree->getTree() as $arrData) {
            if (!in_array($arrData['catid'], $blockAssociatedPageIds)) {
                continue;
            }
            $page = $pageRepo->findOneById($arrData['catid']);
            if (!$page) {
                continue;
            }
            $selectedPages[] = sprintf($pageLinkTemplate, \Cx\Core\Routing\Url::fromPage($page)->toString(), contrexx_raw2xhtml($arrData['catname']));
        }
        if ($selectedPages) {
            $strSelectedPages = '<ul>'.implode($selectedPages).'</ul>';
        }
        return $strSelectedPages;
    }

    /**
     * show the categories
     *
     * @global array module language array
     */
    function showCategories()
    {
        global $_ARRAYLANG;

        $catId = !empty($_REQUEST['catId']) ? intval($_REQUEST['catId']) : 0;
        $this->_pageTitle = $_ARRAYLANG['TXT_BLOCK_CATEGORIES'];
        $this->_objTpl->loadTemplateFile('module_block_categories.html');

        $this->_objTpl->setVariable(array(
            'TXT_BLOCK_CATEGORIES'                  => $_ARRAYLANG['TXT_BLOCK_CATEGORIES'],
            'TXT_BLOCK_CATEGORIES_MANAGE'           => $_ARRAYLANG['TXT_BLOCK_CATEGORIES_MANAGE'],
            'TXT_BLOCK_CATEGORIES_ADD'              => $_ARRAYLANG['TXT_BLOCK_CATEGORIES_ADD'],
            'TXT_BLOCK_FUNCTIONS'                   => $_ARRAYLANG['TXT_BLOCK_FUNCTIONS'],
            'TXT_BLOCK_NAME'                        => $_ARRAYLANG['TXT_BLOCK_NAME'],
            'TXT_BLOCK_CATEGORY_SEPERATOR'          => $_ARRAYLANG['TXT_BLOCK_CATEGORY_SEPERATOR'],
            'TXT_BLOCK_PLACEHOLDER'                 => $_ARRAYLANG['TXT_BLOCK_PLACEHOLDER'],
            'TXT_BLOCK_SEPERATOR'                   => $_ARRAYLANG['TXT_BLOCK_SEPERATOR'],
            'TXT_BLOCK_NONE'                        => $_ARRAYLANG['TXT_BLOCK_NONE'],
            'TXT_BLOCK_PARENT'                      => $_ARRAYLANG['TXT_BLOCK_PARENT'],
            'TXT_BLOCK_SELECT_ALL'                  => $_ARRAYLANG['TXT_BLOCK_SELECT_ALL'],
            'TXT_BLOCK_DESELECT_ALL'                => $_ARRAYLANG['TXT_BLOCK_DESELECT_ALL'],
            'TXT_BLOCK_SUBMIT_SELECT'               => $_ARRAYLANG['TXT_BLOCK_SUBMIT_SELECT'],
            'TXT_BLOCK_SUBMIT_DELETE'               => $_ARRAYLANG['TXT_BLOCK_SUBMIT_DELETE'],
            'TXT_BLOCK_NO_CATEGORIES_FOUND'         => $_ARRAYLANG['TXT_BLOCK_NO_CATEGORIES_FOUND'],
            'TXT_BLOCK_OPERATION_IRREVERSIBLE'      => $_ARRAYLANG['TXT_BLOCK_OPERATION_IRREVERSIBLE'],
            'BLOCK_CATEGORIES_PARENT_DROPDOWN'      => $this->_getCategoriesDropdown(),
            'DIRECTORY_INDEX'                       => CONTREXX_DIRECTORY_INDEX,
            'CSRF_KEY'                              => \Cx\Core\Csrf\Controller\Csrf::key(),
            'CSRF_CODE'                             => \Cx\Core\Csrf\Controller\Csrf::code(),
        ));

        $arrCategories = $this->_getCategories(true);
        if(count($arrCategories) == 0){
            $this->_objTpl->touchBlock('noCategories');
            return;
        }

        $this->_objTpl->hideBlock('noCategories');
        $this->_parseCategories($arrCategories[0]);  //first array contains all root categories (parent id 0)
    }

    function deleteCategory()
    {
        global $_ARRAYLANG;

        if($this->_deleteCategory($_REQUEST['id'])){
            $this->_strOkMessage  = $_ARRAYLANG['TXT_BLOCK_CATEGORIES_DELETE_OK'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOCK_CATEGORIES_DELETE_ERROR'];
        }
    }

    /**
     * recursively parse the categories
     *
     * @param array $arrCategories
     * @param integer $level
     * @param integer $index
     */
    function _parseCategories($arrCategories, $level = 0)
    {
        foreach ($arrCategories as $arrCategory) {
            $this->_objTpl->setVariable(array(
                'BLOCK_CATEGORY_ROWCLASS'   => $this->_index++ % 2 == 0 ? 'row1' : 'row2',
                'BLOCK_CATEGORY_ID'         => $arrCategory['id'],
                'BLOCK_CATEGORY_NAME'       => str_repeat('&nbsp;', $level*4).$arrCategory['name'],
                'BLOCK_CATEGORY_PLACEHOLDER'=> 'BLOCK_CAT_' . $arrCategory['id'],
                'BLOCK_CATEGORY_SEPERATOR'  => contrexx_raw2xhtml($arrCategory['seperator']),
            ));

            if(empty($this->_categories[$arrCategory['id']])){
                $this->_objTpl->touchBlock('deleteCategory');
                $this->_objTpl->touchBlock('checkboxCategory');
            } else {
                $this->_objTpl->touchBlock('deleteCategoryEmpty');
            }

            $this->_objTpl->parse('showCategories');
            if(!empty($this->_categories[$arrCategory['id']])){
                $this->_parseCategories($this->_categories[$arrCategory['id']], $level+1);
            }
        }
    }

    /**
     * prepare and show the edit category page
     *
     */
    function editCategory()
    {
        global $_ARRAYLANG, $_CORELANG;

        $catId = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $this->_pageTitle = $_ARRAYLANG['TXT_BLOCK_CATEGORIES_EDIT'];
        $this->_objTpl->loadTemplateFile('module_block_categories_edit.html');

        $arrCategory = $this->_getCategory($_GET['id']);

        $this->_objTpl->setVariable(array(
            'TXT_BLOCK_NAME'                        => $_ARRAYLANG['TXT_BLOCK_NAME'],
            'TXT_BLOCK_SAVE'                        => $_ARRAYLANG['TXT_BLOCK_SAVE'],
            'TXT_BLOCK_PARENT'                      => $_ARRAYLANG['TXT_BLOCK_PARENT'],
            'TXT_BLOCK_NONE'                        => $_ARRAYLANG['TXT_BLOCK_NONE'],
            'TXT_BLOCK_CATEGORIES_EDIT'             => $_ARRAYLANG['TXT_BLOCK_CATEGORIES_EDIT'],
            'TXT_BLOCK_BACK'                        => $_CORELANG['TXT_BACK'],
            'TXT_BLOCK_CATEGORY_SEPERATOR'          => $_ARRAYLANG['TXT_BLOCK_CATEGORY_SEPERATOR'],
            'BLOCK_CATEGORY_ID'                     => $catId,
            'BLOCK_CATEGORIES_PARENT_DROPDOWN'      => $this->_getCategoriesDropdown($arrCategory['parent'], $catId),
            'BLOCK_CATEGORY_NAME'                   => contrexx_raw2xhtml($arrCategory['name']),
            'BLOCK_CATEGORY_SEPERATOR'              => contrexx_raw2xhtml($arrCategory['seperator']),
            'DIRECTORY_INDEX'                       => CONTREXX_DIRECTORY_INDEX,
            'CSRF_PARAM'                            => \Cx\Core\Csrf\Controller\Csrf::param(),
        ));
    }


    /**
    * Performs the action for the dropdown-selection on the entry page.
    *
    * @param string $strAction: the action passed by the formular.
    */
    function doEntryMultiAction($strAction='')
    {
        global $_ARRAYLANG;

        $success = true;
        switch ($strAction) {
            case 'delete':
                foreach($_REQUEST['selectedCategoryId'] as $intEntryId) {
                    if(!$this->_deleteCategory($intEntryId)){
                        $success = false;
                    }
                }
                if(!$success){
                    $this->_strErrMessage = $_ARRAYLANG['TXT_BLOCK_CATEGORIES_DELETE_ERROR'];
                } else {
                    $this->_strOkMessage = $_ARRAYLANG['TXT_BLOCK_CATEGORIES_DELETE_OK'];
                }
                break;
            default:
                //do nothing!
                break;
        }
    }

    /**
     * saves a category
     *
     */
    function saveCategory()
    {
        global $_ARRAYLANG;

        $id         = !empty($_POST['frmCategoryId'    ])    ? $_POST['frmCategoryId'    ]    : 0;
        $parent     = !empty($_POST['frmCategoryParent'])    ? $_POST['frmCategoryParent']    : 0;
        $name       = !empty($_POST['frmCategoryName'  ])    ? $_POST['frmCategoryName'  ]    : '-';
        $seperator  = !empty($_POST['frmCategorySeperator']) ? $_POST['frmCategorySeperator'] : '';
        $order      = !empty($_POST['frmCategoryOrder' ])    ? $_POST['frmCategoryOrder' ]    : 1;
        $status     = !empty($_POST['frmCategoryStatus'])    ? $_POST['frmCategoryStatus']    : 1;

        if($this->_saveCategory($id, $parent, $name, $seperator, $order, $status)){
            $this->_strOkMessage  = $_ARRAYLANG['TXT_BLOCK_CATEGORIES_ADD_OK'];
        } else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_BLOCK_CATEGORIES_ADD_ERROR'];
        }
    }

    /**
     * parse the date and time from the form submit and convert into a timestamp
     *
     * @param string nameprefix of the form fields (${name}Date,${name}Hour,${name}Minute)
     * @return integer timestamps
     */
    function _parseTimestamp($name)
    {
        if (!isset($_POST[$name . 'Date']) ||
                !isset($_POST[$name . 'Hour']) ||
                !isset($_POST[$name . 'Minute'])) {
            return time();
        }
        $date    = $_POST[$name.'Date'];
        $hour    = $_POST[$name.'Hour'];
        $minutes = $_POST[$name.'Minute'];
        $timestamp = strtotime("$date $hour:$minutes:00");

        return $timestamp !== false
            ? $timestamp
            : time();
    }

    /**
     * parses the hours dropdown
     *
     * @param integer $date selects the options according to timestamp $date
     * @return void
     */
    function _parseHours($date)
    {
        $options = array();
        for($hour = 0; $hour <= 23; $hour++){
            $selected = '';
            $hourFmt = sprintf('%02d', $hour);
            if($hourFmt == date('H', $date)){
                $selected = 'selected="selected"';
            }
            $options[] = '<option value="'.$hourFmt.'" '.$selected.'>'.$hourFmt.'</option>';
        }
        return implode('\n', $options);
    }

    /**
     * parses the minutes dropdown
     *
     * @param integer $date selects the options according to timestamp $date
     * @return void
     */
    function _parseMinutes($date)
    {
        $options = array();
        for($minute = 0; $minute <= 59; $minute++){
            $selected = '';
            $minuteFmt = sprintf('%02d', $minute);
            if($minuteFmt == date('i', $date)){
                $selected = 'selected="selected"';
            }
            $options[] = '<option value="'.$minuteFmt.'" '.$selected.'>'.$minuteFmt.'</option>';
        }
        return implode('\n', $options);
    }

    /**
    * Show modify block
    *
    * Show the block modification page
    *
    * @access private
    * @global array
    * @see blockLibrary::_getBlockContent(), blockLibrary::blockNamePrefix
    */
    private function _showModifyBlock($copy = false)
    {
        global $_ARRAYLANG;

        \JS::activate('cx');
        \JS::activate('ckeditor');
        \JS::activate('jqueryui');
        \JS::registerJS('lib/javascript/tag-it/js/tag-it.min.js');
        \JS::registerCss('lib/javascript/tag-it/css/tag-it.css');

        $mediaBrowserCkeditor = new MediaBrowser();
        $mediaBrowserCkeditor->setCallback('ckeditor_image_button');
        $mediaBrowserCkeditor->setOptions(array(
            'id' => 'ckeditor_image_button',
            'type' => 'button',
            'style' => 'display:none'
        ));

        $catId                  = !empty($_GET['catId']) ? contrexx_input2int($_GET['catId']) : 0;
        $blockId                = !empty($_REQUEST['blockId']) ? intval($_REQUEST['blockId']) : 0;
        $blockCat               = 0;
        $blockName              = '';
        $blockStart             = 0;
        $blockEnd               = 0;
        $blockRandom            = 0;
        $blockRandom2           = 0;
        $blockRandom3           = 0;
        $blockRandom4           = 0;
        $blockGlobal            = 0;
        $blockDirect            = 0;
        $blockCategory          = 0;
        $blockWysiwygEditor     = 1;
        $blockContent           = array();
        $blockAssociatedPageIds = array();
        $blockLangActive        = array();
        $blockGlobalAssociatedPageIds   = array();
        $blockDirectAssociatedPageIds   = array();
        $blockCategoryAssociatedPageIds = array();

        $this->_objTpl->loadTemplateFile('module_block_modify.html');

        $this->_objTpl->setGlobalVariable(array(
            'TXT_BLOCK_CONTENT'                 => $_ARRAYLANG['TXT_BLOCK_CONTENT'],
            'TXT_BLOCK_NAME'                    => $_ARRAYLANG['TXT_BLOCK_NAME'],
            'TXT_BLOCK_RANDOM'                  => $_ARRAYLANG['TXT_BLOCK_RANDOM'],
            'TXT_BLOCK_GLOBAL'                  => $_ARRAYLANG['TXT_BLOCK_SHOW_IN_GLOBAL'],
            'TXT_BLOCK_SAVE'                    => $_ARRAYLANG['TXT_BLOCK_SAVE'],
            'TXT_BLOCK_DEACTIVATE'              => $_ARRAYLANG['TXT_BLOCK_DEACTIVATE'],
            'TXT_BLOCK_ACTIVATE'                => $_ARRAYLANG['TXT_BLOCK_ACTIVATE'],
            'TXT_DONT_SHOW_ON_PAGES'            => $_ARRAYLANG['TXT_DONT_SHOW_ON_PAGES'],
            'TXT_SHOW_ON_ALL_PAGES'             => $_ARRAYLANG['TXT_SHOW_ON_ALL_PAGES'],
            'TXT_SHOW_ON_SELECTED_PAGES'        => $_ARRAYLANG['TXT_SHOW_ON_SELECTED_PAGES'],
            'TXT_BLOCK_CATEGORY'                => $_ARRAYLANG['TXT_BLOCK_CATEGORY'],
            'TXT_BLOCK_NONE'                    => $_ARRAYLANG['TXT_BLOCK_NONE'],
            'TXT_BLOCK_SHOW_FROM'               => $_ARRAYLANG['TXT_BLOCK_SHOW_FROM'],
            'TXT_BLOCK_SHOW_UNTIL'              => $_ARRAYLANG['TXT_BLOCK_SHOW_UNTIL'],
            'TXT_BLOCK_SHOW_TIMED'              => $_ARRAYLANG['TXT_BLOCK_SHOW_TIMED'],
            'TXT_BLOCK_SHOW_ALWAYS'             => $_ARRAYLANG['TXT_BLOCK_SHOW_ALWAYS'],
            'TXT_BLOCK_LANG_SHOW'               => $_ARRAYLANG['TXT_BLOCK_SHOW_BLOCK_IN_THIS_LANGUAGE'],
            'TXT_BLOCK_BASIC_DATA'              => $_ARRAYLANG['TXT_BLOCK_BASIC_DATA'],
            'TXT_BLOCK_ADDITIONAL_OPTIONS'      => $_ARRAYLANG['TXT_BLOCK_ADDITIONAL_OPTIONS'],
            'TXT_BLOCK_SELECTED_PAGES'          => $_ARRAYLANG['TXT_BLOCK_SELECTED_PAGES'],
            'TXT_BLOCK_AVAILABLE_PAGES'         => $_ARRAYLANG['TXT_BLOCK_AVAILABLE_PAGES'],
            'TXT_BLOCK_SELECT_ALL'              => $_ARRAYLANG['TXT_BLOCK_SELECT_ALL'],
            'TXT_BLOCK_UNSELECT_ALL'            => $_ARRAYLANG['TXT_BLOCK_UNSELECT_ALL'],
            'TXT_BLOCK_GLOBAL_PLACEHOLDERS'     => $_ARRAYLANG['TXT_BLOCK_GLOBAL_PLACEHOLDERS'],
            'TXT_BLOCK_GLOBAL_PLACEHOLDERS_INFO'=> $_ARRAYLANG['TXT_BLOCK_GLOBAL_PLACEHOLDERS_INFO'],
            'TXT_BLOCK_DIRECT_PLACEHOLDERS'     => $_ARRAYLANG['TXT_BLOCK_DIRECT_PLACEHOLDERS'],
            'TXT_BLOCK_DIRECT_PLACEHOLDERS_INFO'=> $_ARRAYLANG['TXT_BLOCK_DIRECT_PLACEHOLDERS_INFO'],
            'TXT_BLOCK_CATEGORY_PLACEHOLDERS'   => $_ARRAYLANG['TXT_BLOCK_CATEGORY_PLACEHOLDERS'],
            'TXT_BLOCK_CATEGORY_PLACEHOLDERS_INFO'=> $_ARRAYLANG['TXT_BLOCK_CATEGORY_PLACEHOLDERS_INFO'],
            'TXT_BLOCK_DISPLAY_TIME'            => $_ARRAYLANG['TXT_BLOCK_DISPLAY_TIME'],
            'TXT_BLOCK_FORM_DESC'               => $_ARRAYLANG['TXT_BLOCK_CONTENT'],
            'TXT_BLOCK_USE_WYSIWYG_EDITOR'      => $_ARRAYLANG['TXT_BLOCK_USE_WYSIWYG_EDITOR'],
            'TXT_BLOCK_TARGETING'               => $_ARRAYLANG['TXT_BLOCK_TARGETING'],
            'TXT_BLOCK_TARGETING_SHOW_PANE'     => $_ARRAYLANG['TXT_BLOCK_TARGETING_SHOW_PANE'],
            'TXT_BLOCK_TARGETING_ALL_USERS'     => $_ARRAYLANG['TXT_BLOCK_TARGETING_ALL_USERS'],
            'TXT_BLOCK_TARGETING_VISITOR_CONDITION_BELOW' => $_ARRAYLANG['TXT_BLOCK_TARGETING_VISITOR_CONDITION_BELOW'],
            'TXT_BLOCK_TARGETING_INCLUDE'       => $_ARRAYLANG['TXT_BLOCK_TARGETING_INCLUDE'],
            'TXT_BLOCK_TARGETING_EXCLUDE'       => $_ARRAYLANG['TXT_BLOCK_TARGETING_EXCLUDE'],
            'TXT_BLOCK_TARGETING_TYPE_LOCATION' => $_ARRAYLANG['TXT_BLOCK_TARGETING_TYPE_LOCATION'],
            'TXT_BLOCK_TARGETING_GEOIP_DISABLED_WARNING'  => $_ARRAYLANG['TXT_BLOCK_TARGETING_GEOIP_DISABLED_WARNING'],
        ));

        $targetingStatus = isset($_POST['targeting_status']) ? contrexx_input2int($_POST['targeting_status']) : 0;
        $targeting       = array();
        foreach ($this->availableTargeting as $targetingType) {
            $targetingArr = isset($_POST['targeting'][$targetingType]) ? $_POST['targeting'][$targetingType] : array();
            if (empty($targetingArr)) {
                continue;
            }

            $targeting[$targetingType] = array(
                'filter' => !empty($targetingArr['filter']) && in_array($targetingArr['filter'], array('include', 'exclude'))
                              ? contrexx_input2raw($targetingArr['filter'])
                              : 'include',
                'value'  => isset($targetingArr['value']) ? contrexx_input2raw($targetingArr['value']) : array(),
            );
        }

        $categoryParam = !empty($catId) ? '&catId=' . $catId : '';
        if (isset($_POST['block_save_block'])) {
            $blockCat               = !empty($_POST['blockCat']) ? intval($_POST['blockCat']) : 0;
            $blockContent           = isset($_POST['blockFormText_']) ? array_map('contrexx_input2raw', $_POST['blockFormText_']) : array();
            $blockName              = !empty($_POST['blockName']) ? contrexx_input2raw($_POST['blockName']) : $_ARRAYLANG['TXT_BLOCK_NO_NAME'];
            $blockStart             = strtotime($_POST['inputStartDate']);
            $blockEnd               = strtotime($_POST['inputEndDate']);
            $blockRandom            = !empty($_POST['blockRandom']) ? intval($_POST['blockRandom']) : 0;
            $blockRandom2           = !empty($_POST['blockRandom2']) ? intval($_POST['blockRandom2']) : 0;
            $blockRandom3           = !empty($_POST['blockRandom3']) ? intval($_POST['blockRandom3']) : 0;
            $blockRandom4           = !empty($_POST['blockRandom4']) ? intval($_POST['blockRandom4']) : 0;
            $blockWysiwygEditor     = isset($_POST['wysiwyg_editor']) ? 1 : 0;
            $blockLangActive        = isset($_POST['blockFormLanguages']) ? array_map('intval', $_POST['blockFormLanguages']) : array();

            // placeholder configurations
            // global block
            // 0 = not activated , 1 = on all pages , 2 = selected pages
            $blockGlobal            = !empty($_POST['blockGlobal']) ? intval($_POST['blockGlobal']) : 0;
            // direct block and category block placeholders
            // 0 = on all pages , 1 = selected pages
            $blockDirect            = !empty($_POST['blockDirect']) ? intval($_POST['blockDirect']) : 0;
            $blockCategory          = !empty($_POST['blockCategory']) ? intval($_POST['blockCategory']) : 0;
            // block on page relations for each placeholder
            $blockGlobalAssociatedPageIds = isset($_POST['globalSelectedPagesList']) ? array_map('intval', explode(",", $_POST['globalSelectedPagesList'])) : array();
            $blockDirectAssociatedPageIds = isset($_POST['directSelectedPagesList']) ? array_map('intval', explode(",", $_POST['directSelectedPagesList'])) : array();
            $blockCategoryAssociatedPageIds = isset($_POST['categorySelectedPagesList']) ? array_map('intval', explode(",", $_POST['categorySelectedPagesList'])) : array();

            if ($blockId) {
                if ($this->_updateBlock($blockId, $blockCat, $blockContent, $blockName, $blockStart, $blockEnd, $blockRandom, $blockRandom2, $blockRandom3, $blockRandom4, $blockWysiwygEditor, $blockLangActive)) {
                    if ($this->storePlaceholderSettings($blockId, $blockGlobal, $blockDirect, $blockCategory, $blockGlobalAssociatedPageIds, $blockDirectAssociatedPageIds, $blockCategoryAssociatedPageIds)) {
                        $this->storeTargetingSettings($blockId, $targetingStatus, $targeting);
                        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Block&modified=true&blockname=' . $blockName . $categoryParam);
                        exit;
                    }
                }
                $this->_strErrMessage = $_ARRAYLANG['TXT_BLOCK_BLOCK_COULD_NOT_BE_UPDATED'];
            } else {
                if ($blockId = $this->_addBlock($blockCat, $blockContent, $blockName, $blockStart, $blockEnd, $blockRandom, $blockRandom2, $blockRandom3, $blockRandom4, $blockWysiwygEditor, $blockLangActive)) {
                    if ($this->storePlaceholderSettings($blockId, $blockGlobal, $blockDirect, $blockCategory, $blockGlobalAssociatedPageIds, $blockDirectAssociatedPageIds, $blockCategoryAssociatedPageIds)) {
                        $this->storeTargetingSettings($blockId, $targetingStatus, $targeting);
                        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Block&added=true&blockname=' . $blockName . $categoryParam);
                        exit;
                    }
                }
                $this->_strErrMessage = $_ARRAYLANG['TXT_BLOCK_BLOCK_COULD_NOT_BE_ADDED'];
            }
        } elseif (($arrBlock = $this->_getBlock($blockId)) !== false) {
            $blockStart         = $arrBlock['start'];
            $blockEnd           = $arrBlock['end'];
            $blockCat           = $arrBlock['cat'];
            $blockRandom        = $arrBlock['random'];
            $blockRandom2       = $arrBlock['random2'];
            $blockRandom3       = $arrBlock['random3'];
            $blockRandom4       = $arrBlock['random4'];
            $blockWysiwygEditor = $arrBlock['wysiwyg_editor'];
            $blockContent       = $arrBlock['content'];
            $blockLangActive    = $arrBlock['lang_active'];
            $blockName          = $arrBlock['name'];

            $blockGlobal        = $arrBlock['global'];
            $blockDirect        = $arrBlock['direct'];
            $blockCategory      = $arrBlock['category'];

            $blockGlobalAssociatedPageIds   = $this->_getAssociatedPageIds($blockId, 'global');
            $blockDirectAssociatedPageIds   = $this->_getAssociatedPageIds($blockId, 'direct');
            $blockCategoryAssociatedPageIds = $this->_getAssociatedPageIds($blockId, 'category');

            $targeting = $this->loadTargetingSettings($blockId);
            if (!empty($targeting)) {
                $targetingStatus = 1;
            }
        }

        $pageTitle = $blockId != 0 ? sprintf(($copy ? $_ARRAYLANG['TXT_BLOCK_COPY_BLOCK'] : $_ARRAYLANG['TXT_BLOCK_MODIFY_BLOCK']), contrexx_raw2xhtml($blockName)) : $_ARRAYLANG['TXT_BLOCK_ADD_BLOCK'];
        $this->_pageTitle = $pageTitle;

        if ($copy) {
            $blockId = 0;
        }

        $this->_objTpl->setVariable(array(
            'BLOCK_ID'                          => $blockId,
            'BLOCK_MODIFY_TITLE'                => $pageTitle,
            'BLOCK_NAME'                        => contrexx_raw2xhtml($blockName),
            'BLOCK_CATEGORIES_PARENT_DROPDOWN'  => $this->_getCategoriesDropdown($blockCat),
            'BLOCK_START'                       => !empty($blockStart) ? strftime('%Y-%m-%d %H:%M', $blockStart) : $blockStart,
            'BLOCK_END'                         => !empty($blockEnd) ? strftime('%Y-%m-%d %H:%M', $blockEnd) : $blockEnd,
            'BLOCK_WYSIWYG_EDITOR'              => $blockWysiwygEditor == 1 ? 'checked="checked"' : '',
            'BLOCK_FILTER_CATEGORY_ID'          => $catId,

            // random placeholders
            'BLOCK_RANDOM'                      => $blockRandom == '1' ? 'checked="checked"' : '',
            'BLOCK_RANDOM_2'                    => $blockRandom2 == '1' ? 'checked="checked"' : '',
            'BLOCK_RANDOM_3'                    => $blockRandom3 == '1' ? 'checked="checked"' : '',
            'BLOCK_RANDOM_4'                    => $blockRandom4 == '1' ? 'checked="checked"' : '',

            // global block
            'BLOCK_GLOBAL_0'                    => $blockGlobal == '0' ? 'checked="checked"' : '',
            'BLOCK_GLOBAL_1'                    => $blockGlobal == '1' ? 'checked="checked"' : '',
            'BLOCK_GLOBAL_2'                    => $blockGlobal == '2' ? 'checked="checked"' : '',
            'BLOCK_GLOBAL_SHOW_PAGE_SELECTOR'   => $blockGlobal == '2' ? 'block' : 'none',

            // direct block
            'BLOCK_DIRECT_0'                    => $blockDirect == '0' ? 'checked="checked"' : '',
            'BLOCK_DIRECT_1'                    => $blockDirect == '1' ? 'checked="checked"' : '',
            'BLOCK_DIRECT_SHOW_PAGE_SELECTOR'   => $blockDirect == '1' ? 'block' : 'none',

            // category block
            'BLOCK_CATEGORY_0'                  => $blockCategory == '0' ? 'checked="checked"' : '',
            'BLOCK_CATEGORY_1'                  => $blockCategory == '1' ? 'checked="checked"' : '',
            'BLOCK_CATEGORY_SHOW_PAGE_SELECTOR' => $blockCategory == '1' ? 'block' : 'none',

            // mediabrowser
            'BLOCK_WYSIWYG_MEDIABROWSER' => $mediaBrowserCkeditor->getXHtml(),

            // Targeting
            'BLOCK_TARGETING_ALL_USERS'         => $targetingStatus == 0 ? 'checked="checked"' : '',
            'BLOCK_TARGETING_VISITOR_CONDITION_BELOW' => $targetingStatus == 1 ? 'checked="checked"' : '',
            'BLOCK_TARGETING_COUNTRY_INCLUDE'   => !empty($targeting['country']) && $targeting['country']['filter'] == 'include'
                                                    ? 'selected="selected"' : '',
            'BLOCK_TARGETING_COUNTRY_EXCLUDE'   => !empty($targeting['country']) && $targeting['country']['filter'] == 'exclude'
                                                    ? 'selected="selected"' : '',
        ));

        if (!empty($targeting['country']) && !empty($targeting['country']['value'])) {
            foreach ($targeting['country']['value'] as $countryId) {
                $countryName = \Cx\Core\Country\Controller\Country::getNameById($countryId);
                if (empty($countryName)) {
                    continue;
                }
                $this->_objTpl->setVariable(array(
                    'BLOCK_TARGET_COUNTRY_ID'   => contrexx_raw2xhtml($countryId),
                    'BLOCK_TARGET_COUNTRY_NAME' => contrexx_raw2xhtml($countryName),
                ));
                $this->_objTpl->parse('block_targeting_country');
            }
        }

        $jsonData =  new \Cx\Core\Json\JsonData();
        $pageTitlesTree = $jsonData->data('node', 'getPageTitlesTree');
        $pageTitlesTree = $pageTitlesTree['data'];

        $objJs = \ContrexxJavascript::getInstance();

        $blockGlobalPageSelects   = $this->getPageSelections($pageTitlesTree, $blockGlobalAssociatedPageIds);
        $blockDirectPageSelects   = $this->getPageSelections($pageTitlesTree, $blockDirectAssociatedPageIds);
        $blockCategoryPageSelects = $this->getPageSelections($pageTitlesTree, $blockCategoryAssociatedPageIds);

        $objJs->setVariable('globalPagesUnselectedOptions', $jsonData->parse($blockGlobalPageSelects[1]), 'block');
        $objJs->setVariable('globalPagesSelectedOptions', $jsonData->parse($blockGlobalPageSelects[0]), 'block');

        $objJs->setVariable('directPagesUnselectedOptions', $jsonData->parse($blockDirectPageSelects[1]), 'block');
        $objJs->setVariable('directPagesSelectedOptions', $jsonData->parse($blockDirectPageSelects[0]), 'block');

        $objJs->setVariable('categoryPagesUnselectedOptions', $jsonData->parse($blockCategoryPageSelects[1]), 'block');
        $objJs->setVariable('categoryPagesSelectedOptions', $jsonData->parse($blockCategoryPageSelects[0]), 'block');

        $objJs->setVariable('ckeditorconfigpath', \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Wysiwyg')->getConfigPath(), 'block');

        // manually set Wysiwyg variables as the Ckeditor will be
        // loaded manually through JavaScript (and not properly through the
        // component interface)
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        $mediaSourceManager = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getMediaSourceManager();
        $mediaSource        = current($mediaSourceManager->getMediaTypes());
        $mediaSourceDir     = $mediaSource->getDirectory();
        $objJs->setVariable(array(
            'ckeditorUploaderId'   => $uploader->getId(),
            'ckeditorUploaderPath' => $mediaSourceDir[1] . '/'
        ), 'wysiwyg');

        $arrActiveSystemFrontendLanguages = \FWLanguage::getActiveFrontendLanguages();
        $this->parseLanguageOptionsByPlaceholder($arrActiveSystemFrontendLanguages, 'global');
        $this->parseLanguageOptionsByPlaceholder($arrActiveSystemFrontendLanguages, 'direct');
        $this->parseLanguageOptionsByPlaceholder($arrActiveSystemFrontendLanguages, 'category');

        if (count($arrActiveSystemFrontendLanguages) > 0) {
            $intLanguageCounter = 0;
            $arrLanguages       = array(0 => '', 1 => '', 2 => '');
            $strJsTabToDiv      = '';

            foreach($arrActiveSystemFrontendLanguages as $langId => $arrLanguage) {
                $boolLanguageIsActive = $blockId == 0 && $intLanguageCounter == 0 ? true : ((isset($blockLangActive[$langId]) && $blockLangActive[$langId] == 1) ? true : false);

                $arrLanguages[$intLanguageCounter%3] .= '<input id="languagebar_'.$langId.'" '.(($boolLanguageIsActive) ? 'checked="checked"' : '').' type="checkbox" name="blockFormLanguages['.$langId.']" value="1" onclick="switchBoxAndTab(this, \'lang_blockContent_'.$langId.'\');" /><label for="languagebar_'.$langId.'">'.contrexx_raw2xhtml($arrLanguage['name']).' ['.$arrLanguage['lang'].']</label><br />';
                $strJsTabToDiv .= 'arrTabToDiv["lang_blockContent_'.$langId.'"] = "langTab_'.$langId.'";'."\n";
                ++$intLanguageCounter;
            }

            $this->_objTpl->setVariable(array(
                'TXT_BLOCK_LANGUAGE'      => $_ARRAYLANG['TXT_BLOCK_LANGUAGE'],
                'EDIT_LANGUAGES_1'        => $arrLanguages[0],
                'EDIT_LANGUAGES_2'        => $arrLanguages[1],
                'EDIT_LANGUAGES_3'        => $arrLanguages[2],
                'EDIT_JS_TAB_TO_DIV'      => $strJsTabToDiv
            ));
        }

        $arrLanguages = \FWLanguage::getLanguageArray();
        $i=0;
        $activeFlag = 0;
        foreach ($arrLanguages as $langId => $arrLanguage) {

            if($arrLanguage['frontend'] != 1) {
                continue;
            }

            $tmpBlockContent       = isset($blockContent[$langId]) ? $blockContent[$langId] : '';
            $tmpBlockLangActive    = isset($blockLangActive[$langId]) ? $blockLangActive[$langId] : 0;
            $tmpBlockContent       = preg_replace('/\{([A-Z0-9_-]+)\}/', '[[\\1]]' ,$tmpBlockContent);

            if ($blockId != 0) {
                if (!$activeFlag && isset($blockLangActive[$langId])) {
                    $activeClass =  'active';
                    $activeFlag = 1;
                }
            } elseif (!$activeFlag) {
                $activeClass = 'active';
                $activeFlag = 1;
            }

            $this->_objTpl->setVariable(array(
                'BLOCK_LANG_TAB_LANG_ID'        => intval($langId),
                'BLOCK_LANG_TAB_CLASS'          => isset($activeClass) ? $activeClass : '',
                'TXT_BLOCK_LANG_TAB_LANG_NAME'  => contrexx_raw2xhtml($arrLanguage['name']),
                'BLOCK_LANGTAB_DISPLAY'         => $tmpBlockLangActive == 1 ? 'display:inline;' : ($blockId == 0 && $i == 0 ? 'display:inline;' : 'display:none;')
            ));
            $this->_objTpl->parse('block_language_tabs');

            $this->_objTpl->setVariable(array(
                'BLOCK_LANG_ID'                 => intval($langId),
                'BLOCK_CONTENT_TEXT_HIDDEN'     => contrexx_raw2xhtml($tmpBlockContent),
            ));
            $this->_objTpl->parse('block_language_content');
            $activeClass = '';
            $i++;
        }

        if (!$this->getGeoIpComponent() || !$this->getGeoIpComponent()->getGeoIpServiceStatus()) {
            $this->_objTpl->touchBlock('warning_geoip_disabled');
        } else {
            $this->_objTpl->hideBlock('warning_geoip_disabled');
        }
    }

    /**
     * Store the targeting settings in to database
     *
     * @param integer $blockId         Content block id
     * @param integer $targetingStatus status
     * @param array   $targeting       Array settings of targeting to store
     */
    public function storeTargetingSettings($blockId, $targetingStatus, $targeting = array())
    {
        foreach ($this->availableTargeting as $targetingType) {
            if (!$targetingStatus) {
                $this->removeTargetingSetting($blockId, $targetingType);
                continue;
            }
            $targetingArr = isset($targeting[$targetingType]) ? $targeting[$targetingType] : array();
            if (!empty($targetingArr)) {
                $this->storeTargetingSetting($blockId, $targetingArr['filter'], $targetingType, $targetingArr['value']);
            }
        }
    }

    /**
     * Store the targeting setting in to database
     *
     * @param integer $blockId     Content block id
     * @param string  $filter      Targeting filter type (include/exclude)
     * @param string  $type        Targeting type (country)
     * @param array   $arrayValues Target selected option values
     */
    public function storeTargetingSetting($blockId, $filter, $type, $arrayValues = array())
    {
        global $objDatabase;

        $valueString = json_encode($arrayValues);
        $query = 'INSERT INTO
                    `'. DBPREFIX .'module_block_targeting_option`
                  SET
                    `block_id` = "'. contrexx_raw2db($blockId) .'",
                    `filter`   = "'. contrexx_raw2db($filter) .'",
                    `type`     = "'. contrexx_raw2db($type) .'",
                    `value`    = "'. contrexx_raw2db($valueString) .'"
                  ON DUPLICATE KEY UPDATE
                    `filter`   = "'. contrexx_raw2db($filter) .'",
                    `value`    = "'. contrexx_raw2db($valueString) .'"
                 ';
        $objDatabase->Execute($query);
    }

    /**
     * Remove the targeting settings from database
     *
     * @param integer $blockId Content block id
     * @param string  $type    Targeting type (country)
     */
    public function removeTargetingSetting($blockId, $type)
    {
        global $objDatabase;

        $query = 'DELETE FROM
                    `'. DBPREFIX .'module_block_targeting_option`
                  WHERE
                    `block_id` = "'. contrexx_raw2db($blockId) .'"
                  AND
                    `type`     = "'. contrexx_raw2db($type) .'"';
        $objDatabase->Execute($query);
    }

    /**
     * Parse the language options for the placeholder settings
     *
     * @param array $arrActiveSystemFrontendLanguages
     * @param string $placeholder the placeholder
     */
    private function parseLanguageOptionsByPlaceholder($arrActiveSystemFrontendLanguages, $placeholder) {
        foreach ($arrActiveSystemFrontendLanguages as $langId => $arrLanguage) {
            $checked = '';
            if ($langId == FRONTEND_LANG_ID) {
                $checked = 'checked="checked"';
            }
            $this->_objTpl->setVariable(array(
                'BLOCK_PAGES_LANGUAGE_ID'      => $langId,
                'BLOCK_PAGES_LANGUAGE_CODE'    => $arrLanguage['lang'],
                'BLOCK_PAGES_LANGUAGE_NAME'    => $arrLanguage['name'],
                'BLOCK_PAGES_LANGUAGE_CHECKED' => $checked,
            ));
            $this->_objTpl->parse('pages_languages_' . $placeholder);
        }
    }

    /**
     * Get content of select for page selections
     *
     * @param array $pageTitlesTree all nodes
     * @param array $blockAssociatedPageIds the associated page ids
     * @return array the content for the html select
     */
    private function getPageSelections($pageTitlesTree, $blockAssociatedPageIds) {
        $strSelectedPages   = array();
        $strUnselectedPages = array();

        foreach ($pageTitlesTree as $nodeId => $languages) {
            foreach ($languages as $langCode => $pageData) {
                if (!isset($pageData['id'])) {
                    unset($pageTitlesTree[$nodeId][$langCode]);
                    continue;
                }
                $spacer = '';
                for ($i = 1; $i < $pageData['level']; $i++) {
                    $spacer .= '&nbsp;&nbsp;';
                }

                if (in_array($pageData['id'], $blockAssociatedPageIds)) {
                    if (!isset($strSelectedPages[$langCode])) {
                        $strSelectedPages[$langCode] = '';
                    }
                    $strSelectedPages[$langCode]   .= '<option value="'.$pageData['id'].'">'.$spacer.contrexx_raw2xhtml($pageData['title']).' ('.$pageData['id'].') </option>';
                } else {
                    if (!isset($strUnselectedPages[$langCode])) {
                        $strUnselectedPages[$langCode] = '';
                    }
                    $strUnselectedPages[$langCode] .= '<option value="'.$pageData['id'].'">'.$spacer.contrexx_raw2xhtml($pageData['title']).' ('.$pageData['id'].') </option>';
                }
            }
        }
        return array($strSelectedPages, $strUnselectedPages);
    }

    /**
    * del block
    *
    * delete a block
    *
    * @access private
    * @global array
    * @global ADONewConnection
    */
    function _delBlock()
    {
        global $_ARRAYLANG, $objDatabase;

        $arrDelBlocks = array();
        $arrFailedBlock = array();
        $arrBlockNames = array();

        if (isset($_GET['blockId']) && ($blockId = intval($_GET['blockId'])) > 0) {
            $blockId = intval($_GET['blockId']);
            array_push($arrDelBlocks, $blockId);
            $arrBlock = &$this->_getBlock($blockId);
            $arrBlockNames[$blockId] = htmlentities($arrBlock['name'], ENT_QUOTES, CONTREXX_CHARSET);
        } elseif (isset($_POST['selectedBlockId']) && is_array($_POST['selectedBlockId'])) {
            foreach ($_POST['selectedBlockId'] as $blockId) {
                $id = intval($blockId);
                if ($id > 0) {
                    array_push($arrDelBlocks, $id);
                    $arrBlock = &$this->_getBlock($id);
                    $arrBlockNames[$id] = htmlentities($arrBlock['name'], ENT_QUOTES, CONTREXX_CHARSET);
                }
            }
        }

        if (count($arrDelBlocks) > 0) {
            foreach ($arrDelBlocks as $blockId) {
                foreach ($arrDelBlocks as $blockId) {
                    if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_block_rel_lang_content WHERE block_id=".$blockId) === false || $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_block_blocks WHERE id=".$blockId) === false || $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_block_rel_pages WHERE block_id=".$blockId) === false) {
                        array_push($arrFailedBlock, $blockId);
                    }
                }
            }

            if (count($arrFailedBlock) == 1) {
                $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_BLOCK_COULD_NOT_DELETE_BLOCK'], $arrBlockNames[$arrFailedBlock[0]]);
            } elseif (count($arrFailedBlock) > 1) {
                $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_BLOCK_FAILED_TO_DELETE_BLOCKS'], implode(', ', $arrBlockNames));
            } elseif (count($arrDelBlocks) == 1) {
                $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_BLOCK_SUCCESSFULLY_DELETED'], $arrBlockNames[$arrDelBlocks[0]]);
            } else {
                $this->_strOkMessage = $_ARRAYLANG['TXT_BLOCK_BLOCKS_SUCCESSFULLY_DELETED'];
            }
        }
    }

    /**
    * activate block
    *
    * change the status from a block
    *
    * @access private
    * @global array
    * @global ADONewConnection
    */
    function _activateBlock()
    {
        global $_ARRAYLANG, $objDatabase;

        $arrStatusBlocks = isset($_POST['selectedBlockId']) ? $_POST['selectedBlockId'] : null;
        if ($arrStatusBlocks != null) {
            foreach ($arrStatusBlocks as $blockId) {
                $query = "UPDATE ".DBPREFIX."module_block_blocks SET active='1' WHERE id=$blockId";
                $objDatabase->Execute($query);
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCachePage(
                    'Block',
                    'getBlockContent',
                    array(
                        'block' => $blockId,
                    )
                );
            }
        } else if(isset($_GET['blockId'])) {
            $blockId = $_GET['blockId'];
            $query = "UPDATE ".DBPREFIX."module_block_blocks SET active='1' WHERE id=$blockId";
            $objDatabase->Execute($query);
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCachePage(
                'Block',
                'getBlockContent',
                array(
                    'block' => $blockId,
                )
            );
        }

        $categoryParam = isset($_GET['catId']) ? '&catId=' . contrexx_input2int($_GET['catId']) : '';
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Block' . $categoryParam);
    }

    /**
    * deactivate block
    *
    * change the status from a block
    *
    * @access private
    * @global array
    * @global ADONewConnection
    */
    function _deactivateBlock()
    {
        global $objDatabase;

        $arrStatusBlocks = isset($_POST['selectedBlockId']) ? $_POST['selectedBlockId'] : null;
        if ($arrStatusBlocks != null) {
            foreach ($arrStatusBlocks as $blockId){
                $query = "UPDATE ".DBPREFIX."module_block_blocks SET active='0' WHERE id=$blockId";
                $objDatabase->Execute($query);
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCachePage(
                    'Block',
                    'getBlockContent',
                    array(
                        'block' => $blockId,
                    )
                );
            }
        } else if (isset($_GET['blockId'])) {
            $blockId = $_GET['blockId'];
            $query = "UPDATE ".DBPREFIX."module_block_blocks SET active='0' WHERE id=$blockId";
            $objDatabase->Execute($query);
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCachePage(
                'Block',
                'getBlockContent',
                array(
                    'block' => $blockId,
                )
            );
        }

        $categoryParam = isset($_GET['catId']) ? '&catId=' . contrexx_input2int($_GET['catId']) : '';
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Block' . $categoryParam);
    }

    /**
    * add to global
    *
    * change the status from a block
    *
    * @access private
    * @global array
    * @global ADONewConnection
    */
    function _globalBlock()
    {
        global $objDatabase;

        $arrStatusBlocks = isset($_POST['selectedBlockId']) ? $_POST['selectedBlockId'] : null;
        if($arrStatusBlocks != null){
            foreach ($arrStatusBlocks as $blockId){
                $query = "UPDATE ".DBPREFIX."module_block_blocks SET global='1' WHERE id=$blockId";
                $objDatabase->Execute($query);
            }
        }
    }

    /**
    * del the global
    *
    * change the status from a block
    *
    * @access private
    * @global ADONewConnection
    */
    function _globalBlockOff()
    {
        global $objDatabase;

        $arrStatusBlocks = isset($_POST['selectedBlockId']) ? $_POST['selectedBlockId'] : null;
        if($arrStatusBlocks != null){
            foreach ($arrStatusBlocks as $blockId){
                $query = "UPDATE ".DBPREFIX."module_block_blocks SET global='0' WHERE id=".intval($blockId);
                $objDatabase->Execute($query);
            }
        }
    }

    /**
    * Show settings
    *
    * Show the settings page
    *
    * @access private
    * @global array
    * @global array
    * @global ADONewConnection
    */
    function _showSettings()
    {
        global $_ARRAYLANG, $_CONFIG, $objDatabase;

        if (isset($_POST['saveSettings']) && !empty($_POST['blockSettings'])) {
            $blockGlobalSeperator =   isset($_POST['blockSettings']['blockGlobalSeperator'])
                                    ? contrexx_input2db($_POST['blockSettings']['blockGlobalSeperator']) : '';
            $markParsedBlock      = isset($_POST['blockSettings']['markParsedBlock']);
            $query = '
                UPDATE
                    `'. DBPREFIX .'module_block_settings`
                SET
                    `value` = (CASE `name`
                                WHEN "blockGlobalSeperator" THEN "'. $blockGlobalSeperator .'"
                                WHEN "markParsedBlock" THEN "'. $markParsedBlock .'"
                               END)
            ';
            $objDatabase->Execute($query);

            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?cmd=Block&act=settings');
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_BLOCK_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_block_settings.html');

        $this->_objTpl->setVariable(array(
            'TXT_BLOCK_SETTINGS'                        => $_ARRAYLANG['TXT_BLOCK_SETTINGS'],
            'TXT_BLOCK_USE_BLOCK_SYSTEM'                => $_ARRAYLANG['TXT_BLOCK_USE_BLOCK_SYSTEM'],
            'TXT_BLOCK_USE_BLOCK_RANDOM'                => $_ARRAYLANG['TXT_BLOCK_USE_BLOCK_RANDOM'],
            'TXT_BLOCK_USE_BLOCK_RANDOM_PLACEHOLDER'    => $_ARRAYLANG['TXT_BLOCK_USE_BLOCK_RANDOM_PLACEHOLDER'],
            'TXT_PLACEHOLDERS'                          => $_ARRAYLANG['TXT_BLOCK_PLACEHOLDER'],
            'TXT_BLOCK_BLOCK_RANDOM'                    => $_ARRAYLANG['TXT_BLOCK_BLOCK_RANDOM'],
            'TXT_BLOCK_BLOCK_GLOBAL'                    => $_ARRAYLANG['TXT_BLOCK_BLOCK_GLOBAL'],
            'TXT_BLOCK_GLOBAL_SEPERATOR'                => $_ARRAYLANG['TXT_BLOCK_GLOBAL_SEPERATOR'],
            'TXT_BLOCK_GLOBAL_SEPERATOR_INFO'           => $_ARRAYLANG['TXT_BLOCK_GLOBAL_SEPERATOR_INFO'],
            'TXT_BLOCK_MARK_PARSED_BLOCK'               => $_ARRAYLANG['TXT_BLOCK_MARK_PARSED_BLOCK'],
            'TXT_BLOCK_MARK_PARSED_BLOCK_INFO'          => $_ARRAYLANG['TXT_BLOCK_MARK_PARSED_BLOCK_INFO'],
            'TXT_BLOCK_SAVE'                            => $_ARRAYLANG['TXT_BLOCK_SAVE'],
        ));

        $objResult = $objDatabase->Execute('
            SELECT
                `name`,
                `value`
            FROM
                `'. DBPREFIX .'module_block_settings`
            WHERE
                `name` IN ("blockGlobalSeperator", "markParsedBlock")
        ');
        $settings = array();
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $settings[$objResult->fields['name']] = $objResult->fields['value'];
                $objResult->MoveNext();
            }
        }

        $this->_objTpl->setVariable(array(
            'BLOCK_GLOBAL_SEPERATOR'  =>   isset($settings['blockGlobalSeperator'])
                                         ? contrexx_raw2xhtml($settings['blockGlobalSeperator']) : '',
            'BLOCK_MARK_PARSED_BLOCK' =>   !empty($settings['markParsedBlock'])
                                         ? 'checked="checked"' : '',
            'BLOCK_USE_BLOCK_SYSTEM'  => $_CONFIG['blockStatus'] == '1' ? 'checked="checked"' : '',
            'BLOCK_USE_BLOCK_RANDOM'  => $_CONFIG['blockRandom'] == '1' ? 'checked="checked"' : '',
        ));

    }
}
