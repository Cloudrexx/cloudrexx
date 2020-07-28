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
 * Class Podcast Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.1
 * @package     cloudrexx
 * @subpackage  module_podcast
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Podcast\Controller;
/**
 * Backend for the Podcast module
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.1
 * @package     cloudrexx
 * @subpackage  module_podcast
 * @todo        Edit PHP DocBlocks!
 */
class PodcastManager extends PodcastLib
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
    * Error status message
    *
    * @access private
    * @var string
    */
    var $_strErrMessage = '';

   /**
    * Ok status message
    *
    * @access private
    * @var string
    */
    var $_strOkMessage = '';

    private $act = '';

    /**
    * PHP5 constructor
    *
    * @global \Cx\Core\Html\Sigma
    * @global array
    */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG;

        $this->_objTpl = new \Cx\Core\Html\Sigma(\Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath().'/Podcast/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->_youTubeIdRegex = '#.*[\?&]v=('.$this->_youTubeAllowedCharacters.'{'.$this->_youTubeIdLength.'}).*#';
        parent::__construct();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION", "
            <a href='index.php?cmd=Podcast' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_PODCAST_MEDIA']."</a>
            <a href='index.php?cmd=Podcast&amp;act=selectSource' class='".($this->act == 'selectSource' ? 'active' : '')."'>".$_ARRAYLANG['TXT_PODCAST_ADD_MEDIUM']."</a>
            <a href='index.php?cmd=Podcast&amp;act=categories' class='".($this->act == 'categories' ? 'active' : '')."'>".$_ARRAYLANG['TXT_PODCAST_CATEGORIES']."</a>
            <a href='index.php?cmd=Podcast&amp;act=templates' class='".($this->act == 'templates' ? 'active' : '')."'>".$_ARRAYLANG['TXT_PODCAST_TEMPLATES']."</a>
            <a href='index.php?cmd=Podcast&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_PODCAST_SETTINGS']."</a>"
                                                        );
    }

    /**
    * Set the backend page
    *
    * @access public
    * @global \Cx\Core\Html\Sigma
    * @global array
    * @global ADONewConnection
    */
    function getPage()
    {
        global $objTemplate, $_ARRAYLANG;

        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        }

        switch ($_REQUEST['act']) {
            case 'showMedium':
                $this->_showMedium();
                break;

            case 'selectSource':
                $this->_selectMediumSource();
                break;

            case 'modifyMedium':
                $this->_modifyMedium();
                break;

            case 'deleteMedium':
                $this->_deleteMediumProcess();
                break;

            case 'getHtml':
                $this->_getHtml();
                break;

            case 'categories':
                $this->_categories();
                break;

            case 'modifyCategory':
                $this->_modifyCategory();
                break;

            case 'deleteCategory':
                $this->_deleteCategoryProcess();
                break;

            case 'templates':
                $this->_templates();
                break;

            case 'modifyTemplate':
                $this->_modifyTemplate();
                break;

            case 'deleteTemplate':
                $this->_deleteTemplateProcess();
                break;

            case 'settings':
                $this->_settings();
                break;

            case 'media':
            default:
                $this->_media();
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

    function _media()
    {
        global $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->loadTemplatefile('module_podcast_media.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_PODCAST_MEDIA'];

        $this->_objTpl->setVariable(array(
            'TXT_PODCAST_STATUS'                    => $_ARRAYLANG['TXT_PODCAST_STATUS'],
            'TXT_PODCAST_TITLE'                     => $_ARRAYLANG['TXT_PODCAST_TITLE'],
            'TXT_PODCAST_AUTHOR'                    => $_ARRAYLANG['TXT_PODCAST_AUTHOR'],
            'TXT_PODCAST_DATE'                      => $_ARRAYLANG['TXT_PODCAST_DATE'],
            'TXT_PODCAST_TEMPLATE'                  => $_ARRAYLANG['TXT_PODCAST_TEMPLATE'],
            'TXT_PODCAST_FUNCTIONS'                 => $_ARRAYLANG['TXT_PODCAST_FUNCTIONS'],
            'TXT_PODCAST_ADD_MEDIUM'                => $_ARRAYLANG['TXT_PODCAST_ADD_MEDIUM'],
            'TXT_PODCAST_CONFIRM_DELETE_MEDIUM_MSG' => $_ARRAYLANG['TXT_PODCAST_CONFIRM_DELETE_MEDIUM_MSG'],
            'TXT_PODCAST_OPERATION_IRREVERSIBLE'    => $_ARRAYLANG['TXT_PODCAST_OPERATION_IRREVERSIBLE'],
            'TXT_PODCAST_CHECK_ALL'                 => $_ARRAYLANG['TXT_PODCAST_CHECK_ALL'],
            'TXT_PODCAST_UNCHECK_ALL'               => $_ARRAYLANG['TXT_PODCAST_UNCHECK_ALL'],
            'TXT_PODCAST_WITH_SELECTED'             => $_ARRAYLANG['TXT_PODCAST_WITH_SELECTED'],
            'TXT_PODCAST_DELETE'                    => $_ARRAYLANG['TXT_PODCAST_DELETE'],
            'TXT_PODCAST_CONFIRM_DELETE_MEDIA_MSG'  => $_ARRAYLANG['TXT_PODCAST_CONFIRM_DELETE_MEDIA_MSG'],
            'TXT_PODCAST_SHOW_MEDIUM'               => $_ARRAYLANG['TXT_PODCAST_SHOW_MEDIUM']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_PODCAST_SHOW_HTML_SOURCE_CODE' => $_ARRAYLANG['TXT_PODCAST_SHOW_HTML_SOURCE_CODE'],
            'TXT_PODCAST_MODIFY_MEDIUM'         => $_ARRAYLANG['TXT_PODCAST_MODIFY_MEDIUM'],
            'TXT_PODCAST_DELETE_MEDIUM'         => $_ARRAYLANG['TXT_PODCAST_DELETE_MEDIUM']
        ));

        $rowNr = 0;
        $paging = "";
        $categoryId = false;
        $arrCategory = false;

        if (isset($_GET['categoryId']) && ($arrCategory = $this->_getCategory(intval($_GET['categoryId']))) !== false) {
            $categoryId = intval($_GET['categoryId']);
            $this->_objTpl->setVariable('PODCAST_MEDIA_TITLE_TXT', sprintf($_ARRAYLANG['TXT_PODCAST_MEDIA_OF_CATEGORY'], $arrCategory['title']));
        } else {
            $this->_objTpl->setVariable('PODCAST_MEDIA_TITLE_TXT',$_ARRAYLANG['TXT_PODCAST_MEDIA']);
        }

        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $arrMedia = $this->_getMedia($categoryId, false, $_CONFIG['corePagingLimit'], $pos);
        $mediaCount = $this->_getMediaCount($categoryId);

        if ($mediaCount > $_CONFIG['corePagingLimit']) {
            $paging = getPaging($mediaCount, $pos, '&cmd=Podcast&categoryId='.$categoryId, $_ARRAYLANG['TXT_PODCAST_MEDIA']);
            $this->_objTpl->setVariable('PODCAST_PAGING', $paging."<br /><br />\n");
        }

        if ($mediaCount > 0) {
            $arrTemplates = $this->_getTemplates();

            foreach ($arrMedia as $mediumId => $arrMedium) {
                $this->_objTpl->setVariable(array(
                    'PODCAST_ROW_CLASS'         => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'PODCAST_MEDIUM_ID'         => $mediumId,
                    'PODCAST_MEDIUM_STATUS_IMG' => $arrMedium['status'] == 1 ? 'led_green.gif' : 'led_red.gif',
                    'PODCAST_MEDIUM_STATUS_TXT' => $arrMedium['status'] == 1 ? $_ARRAYLANG['TXT_PODCAST_ACTIVE'] : $_ARRAYLANG['TXT_PODCAST_INACTIVE'],
                    'PODCAST_MEDIUM_DATE'       => date(ASCMS_DATE_FORMAT, $arrMedium['date_added']),
                    'PODCAST_MEDIUM_TITLE'      => htmlentities($arrMedium['title'], ENT_QUOTES, CONTREXX_CHARSET),
                    'PODCAST_MEDIUM_AUTHOR'     => !empty($arrMedium['author']) ? htmlentities($arrMedium['author'], ENT_QUOTES, CONTREXX_CHARSET) : '-',
                    'PODCAST_MEDIUM_TEMPLATE'   => htmlentities($arrTemplates[$arrMedium['template_id']]['description'], ENT_QUOTES, CONTREXX_CHARSET)
                ));
                $this->_objTpl->parse('podcast_media_list');
                $rowNr++;
            }

            $this->_objTpl->hideBlock('podcast_media_no_data');
            $this->_objTpl->touchBlock('podcast_media_data');
            $this->_objTpl->touchBlock('podcast_media_multi_select_action');
        } else {
            if ($arrCategory) {
                $this->_objTpl->setVariable('PODCAST_EMPTY_CATEGORY_MSG_TXT', sprintf($_ARRAYLANG['TXT_PODCAST_EMPTY_CATEGORY_MSG'], $arrCategory['title']));
            } else {
                $this->_objTpl->setVariable('PODCAST_EMPTY_CATEGORY_MSG_TXT', 'Die Medien Bibliothek ist leer!');
            }
            $this->_objTpl->touchBlock('podcast_media_no_data');
            $this->_objTpl->hideBlock('podcast_media_data');
            $this->_objTpl->hideBlock('podcast_media_multi_select_action');
        }

        if ($mediaCount > 0 || $categoryId) {
            $this->_objTpl->setVariable('PODCAST_CATEGORY_MENU', $this->_getCategoriesMenu($categoryId, 'onchange="window.location.href=\'index.php?cmd=Podcast&amp;'.\Cx\Core\Csrf\Controller\Csrf::param().'&amp;categoryId=\'+this.value"'));
            $this->_objTpl->touchBlock('podcast_category_menu');
        } else {
            $this->_objTpl->hideBlock('podcast_category_menu');
        }
    }

    function _showMedium()
    {
        global $_ARRAYLANG;

        $mediumId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (($arrMedium = $this->_getMedium($mediumId)) === false) {
            return $this->_media();
        }

        $this->_objTpl->loadTemplatefile('module_podcast_show_medium.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_PODCAST_SHOW_MEDIUM'];

        $this->_objTpl->setVariable(array(
            'TXT_PODCAST_MEDIUM'    => $_ARRAYLANG['TXT_PODCAST_MEDIUM'],
            'TXT_PODCAST_BACK'      => $_ARRAYLANG['TXT_PODCAST_BACK']
        ));

        $arrTemplate = $this->_getTemplate($arrMedium['template_id']);
        $this->_objTpl->setVariable(array(
            'PODCAST_MEDIUM_TITLE'          => $arrMedium['title'],
            'PODCAST_MEDIUM_INCLUDE_CODE'   => $this->_getHtmlTag($arrMedium, $arrTemplate['template'])
        ));
    }

    function _deleteMediumProcess()
    {
        global $_ARRAYLANG;

        $arrRemoveMediumIds = array();
        $deleteStatus = true;

        if (isset($_POST['podcast_medium_selected']) && is_array($_POST['podcast_medium_selected'])) {
            foreach ($_POST['podcast_medium_selected'] as $mediumId) {
                array_push($arrRemoveMediumIds, intval($mediumId));
            }
        } elseif (isset($_GET['id'])) {
            array_push($arrRemoveMediumIds, intval($_GET['id']));
        }

        if (count($arrRemoveMediumIds) > 0) {
            foreach ($arrRemoveMediumIds as $mediumId) {
                if (($arrMedium = $this->_getMedium($mediumId)) !== false) {
                    if (!$this->_deleteMedium($mediumId)) {
                        $deleteStatus = false;
                    }
                }
            }

            if ($deleteStatus) {
                if (count($arrRemoveMediumIds) > 1) {
                    $this->_strOkMessage = $_ARRAYLANG['TXT_PODCAST_DELETE_MEDIA_SUCCESSFULL_MSG'];
                } else {
                    $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_PODCAST_DELETE_MEDIUM_SUCCESSFULL_MSG'], $arrMedium['title']);
                }

                $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                $cacheManager->deleteSingleFile($pageId);
                $this->_createRSS();
            } else {
                if (count($arrRemoveMediumIds) > 1) {
                    $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_DELETE_MEDIA_FAILED_MSG'];
                } else {
                    $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_PODCAST_DELETE_MEDIUM_FAILED_MSG'], $arrMedium['title']);
                }
            }
        }

        return $this->_media();
    }

    function _categories()
    {
        global $_ARRAYLANG, $_CONFIG;

        $categoryCount = $this->_getCategoriesCount();
        if ($categoryCount == 0) {
            return $this->_modifyCategory();
        }

        $rowNr = 0;
        $this->_pageTitle = $_ARRAYLANG['TXT_PODCAST_CATEGORIES'];
        $this->_objTpl->loadTemplatefile('module_podcast_categories.html');

        if ($categoryCount > $_CONFIG['corePagingLimit']) {
            $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
            $paging = getPaging($categoryCount, $pos, '&cmd=Podcast&act=categories', $_ARRAYLANG['TXT_PODCAST_CATEGORIES']);
            $this->_objTpl->setVariable('PODCAST_PAGING', $paging."<br /><br />\n");
        }

        $this->_objTpl->setVariable(array(
            'TXT_PODCAST_CATEGORIES'                    => $_ARRAYLANG['TXT_PODCAST_CATEGORIES'],
            'TXT_PODCAST_STATUS'                        => $_ARRAYLANG['TXT_PODCAST_STATUS'],
            'TXT_PODCAST_TITLE'                         => $_ARRAYLANG['TXT_PODCAST_TITLE'],
            'TXT_PODCAST_DESCRIPTION'                   => $_ARRAYLANG['TXT_PODCAST_DESCRIPTION'],
            'TXT_PODCAST_MEDIA_COUNT'                   => $_ARRAYLANG['TXT_PODCAST_MEDIA_COUNT'],
            'TXT_PODCAST_FUNCTIONS'                     => $_ARRAYLANG['TXT_PODCAST_FUNCTIONS'],
            'TXT_PODCAST_ADD_NEW_CATEGORY'              => $_ARRAYLANG['TXT_PODCAST_ADD_NEW_CATEGORY'],
            'TXT_PODCAST_CONFIRM_DELETE_CATEGORY_MSG'   => $_ARRAYLANG['TXT_PODCAST_CONFIRM_DELETE_CATEGORY_MSG'],
            'TXT_PODCAST_OPERATION_IRREVERSIBLE'        => $_ARRAYLANG['TXT_PODCAST_OPERATION_IRREVERSIBLE']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_PODCAST_MODIFY_CATEGORY'   => $_ARRAYLANG['TXT_PODCAST_MODIFY_CATEGORY'],
            'TXT_PODCAST_DELETE_CATEGORY'   => $_ARRAYLANG['TXT_PODCAST_DELETE_CATEGORY']
        ));

        $arrCategories = $this->_getCategories(false, true);
        foreach ($arrCategories as $categoryId => $arrCategory) {
            $mediaCount = $this->_getMediaCount($categoryId);

            $this->_objTpl->setVariable(array(
                'PODCAST_ROW_CLASS'                 => $rowNr % 2 == 1 ? 'row1' : 'row2',
                'PODCAST_CATEGORY_ID'               => $categoryId,
                'PODCAST_CATEGORY_STATUS_IMG'       => $arrCategory['status'] == 1 ? 'led_green.gif' : 'led_red.gif',
                'PODCAST_CATEGORY_STATUS_TXT'       => $arrCategory['status'] == 1 ? $_ARRAYLANG['TXT_PODCAST_ACTIVE'] : $_ARRAYLANG['TXT_PODCAST_INACTIVE'],
                'PODCAST_CATEGORY_TITLE'            => htmlentities($arrCategory['title'], ENT_QUOTES, CONTREXX_CHARSET),
                'PODCAST_CATEGORY_DESCRIPTION'      => htmlentities($arrCategory['description'], ENT_QUOTES, CONTREXX_CHARSET),
                'PODCAST_CATEGORY_DESCRIPTION_CUT'  => htmlentities(strlen($arrCategory['description']) > 50 ? substr($arrCategory['description'],0, 47).'...' : $arrCategory['description'], ENT_QUOTES, CONTREXX_CHARSET),
                'PODCAST_CATEGORY_MEDIA_COUNT'      => $mediaCount > 0 ? '<a href="index.php?cmd=Podcast&amp;categoryId='.$categoryId.'" title="'.sprintf($_ARRAYLANG['TXT_PODCAST_SHOW_MEDIA_OF_CATEGORY'], $arrCategory['title']).'">'.$mediaCount.'</a>' : '-'
            ));
            $this->_objTpl->parse('podcast_categories_list');
            $rowNr++;
        }
    }

    function _modifyCategory()
    {
        global $_ARRAYLANG;

        $categoryId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $categoryTitle = '';
        $categoryDescription = '';
        $categoryAssociatedLangIds = array();
        $categoryStatus = 0;
        $saveStatus = true;

        if (isset($_POST['podcast_category_save'])) {
            if (isset($_POST['podcast_category_title'])) {
                $categoryTitle = trim($_POST['podcast_category_title']);
            }
            if (isset($_POST['podcast_category_description'])) {
                $categoryDescription = trim($_POST['podcast_category_description']);
            }

            if (isset($_POST['podcast_category_associated_language'])) {
                foreach ($_POST['podcast_category_associated_language'] as $langId => $status) {
                    if (intval($status) == 1) {
                        array_push($categoryAssociatedLangIds, intval($langId));
                    }
                }
            }

            $categoryStatus = isset($_POST['podcast_category_status']) ? intval($_POST['podcast_category_status']) : 0;

            if (empty($categoryTitle)) {
                $saveStatus = false;
                $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_EMPTY_CATEGORY_TITLE_MSG'];
            } elseif (!$this->_isUniqueCategoryTitle($categoryTitle, $categoryId)) {
                $saveStatus = false;
                $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_DUPLICATE_CATEGORY_TITLE_MSG'];
            }

            if ($saveStatus) {
                if ($categoryId > 0) {
                    if ($this->_updateCategory($categoryId, $categoryTitle, $categoryDescription, $categoryAssociatedLangIds, $categoryStatus)) {
                        $this->_strOkMessage = $_ARRAYLANG['TXT_PODCAST_CATEGORY_UPDATED_SUCCESSFULL'];
                        $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                        $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                        $cacheManager->deleteSingleFile($pageId);
                        $this->_createRSS();
                        return $this->_categories();
                    } else {
                        $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_CATEGORY_UPDATED_FAILED'];
                    }
                } else {
                    if ($this->_addCategory($categoryTitle, $categoryDescription, $categoryAssociatedLangIds, $categoryStatus)) {
                        $this->_strOkMessage = $_ARRAYLANG['TXT_PODCAST_CATEGORY_CREATED_SUCCESSFULL'];
                        $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                        $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                        $cacheManager->deleteSingleFile($pageId);
                        $this->_createRSS();
                        return $this->_categories();
                    } else {
                        $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_CATEGORY_CREATED_FAILED'];
                    }
                }
            }
        } elseif ($categoryId > 0 && ($arrCategory = $this->_getCategory($categoryId)) !== false) {
            $categoryTitle = &$arrCategory['title'];
            $categoryDescription = &$arrCategory['description'];
            $categoryAssociatedLangIds = $this->_getLangIdsOfCategory($categoryId);
            $categoryStatus = &$arrCategory['status'];
        }

        $this->_pageTitle = $categoryId > 0 ? $_ARRAYLANG['TXT_PODCAST_MODIFY_CATEGORY'] : $_ARRAYLANG['TXT_PODCAST_ADD_NEW_CATEGORY'];
        $this->_objTpl->loadTemplatefile('module_podcast_modify_category.html');

        $this->_objTpl->setVariable(array(
            'TXT_PODCAST_TITLE'                 => $_ARRAYLANG['TXT_PODCAST_TITLE'],
            'TXT_PODCAST_DESCRIPTION'           => $_ARRAYLANG['TXT_PODCAST_DESCRIPTION'],
            'TXT_PODCAST_STATUS'                => $_ARRAYLANG['TXT_PODCAST_STATUS'],
            'TXT_PODCAST_ACTIVE'                => $_ARRAYLANG['TXT_PODCAST_ACTIVE'],
            'TXT_PODCAST_SAVE'                  => $_ARRAYLANG['TXT_PODCAST_SAVE'],
            'TXT_PODCAST_BACK'                  => $_ARRAYLANG['TXT_PODCAST_BACK'],
            'TXT_PODCAST_FRONTEND_LANGUAGES'    => $_ARRAYLANG['TXT_PODCAST_FRONTEND_LANGUAGES']
        ));

        $this->_objTpl->setVariable(array(
            'PODCAST_CATEGORY_ID'           => $categoryId,
            'PODCAST_CATEGORY_MODIFY_TITLE' => $categoryId > 0 ? $_ARRAYLANG['TXT_PODCAST_MODIFY_CATEGORY'] : $_ARRAYLANG['TXT_PODCAST_ADD_NEW_CATEGORY'],
            'PODCAST_CATEGORY_TITLE'        => htmlentities($categoryTitle, ENT_QUOTES, CONTREXX_CHARSET),
            'PODCAST_CATEGORY_DESCRIPTION'  => htmlentities($categoryDescription, ENT_QUOTES, CONTREXX_CHARSET),
            'PODCAST_CATEGORY_STATUS'       => $categoryStatus == 1 ? 'checked="checked"' : ''
        ));

        $arrLanguages = \FWLanguage::getLanguageArray();
        $langNr = 0;

        foreach ($arrLanguages as $langId => $arrLanguage) {
            $column = $langNr % 3;

            $this->_objTpl->setVariable(array(
                'PODCAST_LANG_ID'                   => $langId,
                'PODCAST_LANG_ASSOCIATED'           => in_array($langId, $categoryAssociatedLangIds) ? 'checked="checked"' : '',
                'PODCAST_LANG_NAME'                 => $arrLanguage['name'].' ('.$arrLanguage['lang'].')'
            ));
            $this->_objTpl->parse('podcast_category_associated_language_'.$column);

            $langNr++;
        }
    }

    function _deleteCategoryProcess()
    {
        global $_ARRAYLANG;

        $categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (($arrCategory = $this->_getCategory($categoryId)) !== false) {
            if ($this->_getMediaCount($categoryId) == 0) {
                if ($this->_deleteCategory($categoryId)) {
                    $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_PODCAST_DELETE_CATEGORY_SUCCESSFULL_MSG'], $arrCategory['title']);
                    $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                    $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                    $cacheManager->deleteSingleFile($pageId);
                    $this->_createRSS();
                } else {
                    $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_PODCAST_DELETE_CATEGORY_FAILED_MSG'], $arrCategory['title']);
                }
            } else {
                $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_PODCAST_CATEGORY_STILL_IN_USE_MSG'], $arrCategory['title']);
            }
        }

        return $this->_categories();
    }

    function _templates()
    {
        global $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->loadTemplatefile('module_podcast_templates.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_PODCAST_TEMPLATES'];

        $limitPos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        if (($templateCount = $this->_getTemplateCount()) > $_CONFIG['corePagingLimit']) {
            $paging = getPaging($templateCount, $limitPos, '&cmd=Podcast&act=templates', $_ARRAYLANG['TXT_PODCAST_TEMPLATES']);

            $this->_objTpl->setVariable('PODCAST_PAGING', $paging."<br /><br />\n");
        }

        $arrTemplates = $this->_getTemplates(true, $limitPos);
        if (count($arrTemplates) > 0) {
            $this->_objTpl->setVariable(array(
                'TXT_PODCAST_TEMPLATES'                 => $_ARRAYLANG['TXT_PODCAST_TEMPLATES'],
                'TXT_PODCAST_DESCRIPTION'               => $_ARRAYLANG['TXT_PODCAST_DESCRIPTION'],
                'TXT_PODCAST_FUNCTIONS'                 => $_ARRAYLANG['TXT_PODCAST_FUNCTIONS'],
                'TXT_PODCAST_ADD_NEW_TEMPLATE'          => $_ARRAYLANG['TXT_PODCAST_ADD_NEW_TEMPLATE'],
                'TXT_PODCAST_CONFIRM_DELETE_TEMPLATE'   => $_ARRAYLANG['TXT_PODCAST_CONFIRM_DELETE_TEMPLATE'],
                'TXT_PODCAST_OPERATION_IRREVERSIBLE'    => $_ARRAYLANG['TXT_PODCAST_OPERATION_IRREVERSIBLE']
            ));

            $this->_objTpl->setGlobalVariable(array(
                'TXT_PODCAST_MODIFY_TEMPLATE'   => $_ARRAYLANG['TXT_PODCAST_MODIFY_TEMPLATE'],
                'TXT_PODCAST_DELETE_TEMPLATE'   => $_ARRAYLANG['TXT_PODCAST_DELETE_TEMPLATE']
            ));

            $rowNr = 0;
            foreach ($arrTemplates as $templateId => $arrTemplate) {
                $this->_objTpl->setVariable(array(
                    'PODCAST_TEMPLATE_ID'           => $templateId,
                    'PODCAST_TEMPLATE_DESCRIPTION'  => htmlentities($arrTemplate['description'], ENT_QUOTES, CONTREXX_CHARSET),
                    'PODCAST_ROW_CLASS'             => $rowNr % 2 == 1 ? 'row1' : 'row2'
                ));
                $rowNr++;

                $this->_objTpl->parse('podcast_templates');
            }

        } else {
            $this->_modifyTemplate();
        }

    }

    function _modifyTemplate()
    {
        global $_ARRAYLANG;

        $templateId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $description = '';
        $template = '';
        $extensions = '';
        $saveStatus = true;

        if (isset($_POST['podcast_template_save'])) {
            if (isset($_POST['podcast_template_description'])) {
                $description = trim($_POST['podcast_template_description']);
            }

            if (isset($_POST['podcast_template_template'])) {
                $template = $_POST['podcast_template_template'];
            }

            if (isset($_POST['podcast_template_file_extensions'])) {
                $arrCleanedExtensions = array();
                $arrExtensions = explode(',', $_POST['podcast_template_file_extensions']);
                foreach ($arrExtensions as $extension) {
                    $extension = trim($extension);
                    if (preg_match('/^[a-z0-9_-]*$/i', $extension)) {
                        array_push($arrCleanedExtensions, $extension);
                    }
                }
                $extensions = implode(', ', $arrCleanedExtensions);
            }

            if (empty($description)) {
                $saveStatus = false;
                $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_DEFINE_TEMPLATE_DESCRIPTION'];
            } elseif (!$this->_isUniqueTemplateDescription($templateId, $description)) {
                $saveStatus = false;
                $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_PODCAST_UNIQUE_TEMPLATE_DESCRIPTION_MSG'], $description);
            }

            if ($saveStatus) {
                if ($templateId > 0 ) {
                    if ($this->_updateTemplate($templateId, $description, $template, $extensions)) {
                        $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_PODCAST_TEMPLATE_UPDATED_SUCCESSFULL'], $description);
                        $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                        $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                        $cacheManager->deleteSingleFile($pageId);
                        $this->_createRSS();
                        return $this->_templates();
                    } else {
                        $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_TEMPLATE_UPDATED_FAILED'];
                    }
                } else {
                    if ($this->_addTemplate($description, $template, $extensions)) {
                        $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_PODCAST_TEMPLATE_ADDED_SUCCESSFULL'], $description);
                        $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                        $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                        $cacheManager->deleteSingleFile($pageId);
                        $this->_createRSS();
                        return $this->_templates();
                    } else {
                        $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_TEMPLATE_ADDED_FAILED'];
                    }
                }
            }
        } elseif ($templateId > 0 && ($arrTemplate = $this->_getTemplate($templateId)) !== false) {
            $description = $arrTemplate['description'];
            $template = $arrTemplate['template'];
            $extensions = $arrTemplate['extensions'];
        }

        $this->_objTpl->loadTemplatefile('module_podcast_modify_template.html');
        $this->_pageTitle = $templateId > 0 ? $_ARRAYLANG['TXT_PODCAST_MODIFY_TEMPLATE'] : $_ARRAYLANG['TXT_PODCAST_ADD_NEW_TEMPLATE'];

        $this->_objTpl->setVariable(array(
            'TXT_PODCAST_DESCRIPTION'       => $_ARRAYLANG['TXT_PODCAST_DESCRIPTION'],
            'TXT_PODCAST_TEMPLATE'          => $_ARRAYLANG['TXT_PODCAST_TEMPLATE'],
            'TXT_PODCAST_FILE_EXTENSIONS'   => $_ARRAYLANG['TXT_PODCAST_FILE_EXTENSIONS'],
            'TXT_PODCAST_BACK'              => $_ARRAYLANG['TXT_PODCAST_BACK'],
            'TXT_PODCAST_SAVE'              => $_ARRAYLANG['TXT_PODCAST_SAVE']
        ));

        $this->_objTpl->setVariable(array(
            'PODCAST_TEMPLATE_ID'               => $templateId,
            'PODCAST_TEMPLATE_DESCRIPTION'      => htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET),
            'PODCAST_TEMPLATE_TEMPLATE'         => htmlentities($template, ENT_QUOTES, CONTREXX_CHARSET),
            'PODCAST_TEMPLATE_FILE_EXTENSIONS'  => $extensions,
            'PODCAST_TEMPLATE_MODIFY_TITLE'     => $templateId > 0 ? $_ARRAYLANG['TXT_PODCAST_MODIFY_TEMPLATE'] : $_ARRAYLANG['TXT_PODCAST_ADD_NEW_TEMPLATE']
        ));
    }

    function _deleteTemplateProcess()
    {
        global $_ARRAYLANG;

        $templateId = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (($arrTemplate = $this->_getTemplate($templateId)) !== false) {
            if (!$this->_isTemplateInUse($templateId)) {
                if ($this ->_deleteTemplate($templateId)) {
                    $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_PODCAST_TEMPLATE_DELETED_SUCCESSFULL'], $arrTemplate['description']);
                    $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                    $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                    $cacheManager->deleteSingleFile($pageId);
                    $this->_createRSS();
                } else {
                    $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_PODCAST_TEMPLATE_DELETED_FAILURE'], $arrTemplate['description']);
                }
            } else {
                $this->_strErrMessage = sprintf($_ARRAYLANG['TXT_PODCAST_TEMPLATE_STILL_IN_USE_MSG'], $arrTemplate['description']);
            }
        }

        $this->_templates();
    }

    function _getHtml()
    {
        global $_ARRAYLANG;

        $mediumId = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (($arrMedium = $this->_getMedium($mediumId)) === false) {
            return $this->_media();
        }

        $arrTemplate = $this->_getTemplate($arrMedium['template_id']);

        $this->_objTpl->loadTemplatefile('module_podcast_medium_source_code.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_PODCAST_SOURCE_CODE'];

        $this->_objTpl->setVariable(array(
            'TXT_PODCAST_BACK'          => $_ARRAYLANG['TXT_PODCAST_BACK'],
            'TXT_PODCAST_SELECT_ALL'    => $_ARRAYLANG['TXT_PODCAST_SELECT_ALL']
        ));

        $this->_objTpl->setVariable(array(
            'PODCAST_HTML_SOURCE_CODE_OF_MEDIUM_TXT'    => sprintf($_ARRAYLANG['TXT_PODCAST_SOURCE_CODE_OF_MEDIUM'], $arrMedium['title']),
            'PODCAST_MEDIUM_SOURCE_CODE'                => $this->_getHtmlTag($arrMedium, $arrTemplate['template'])
        ));

    }

    function _settings()
    {
        global $_ARRAYLANG, $_CONFIG;

        $arrSettingsTabs = array("general", "block");
        $defaultTab = 'general';
        $selectedTab = !empty($_POST['podcast_settings_tab']) ? strtolower($_POST['podcast_settings_tab']) : $defaultTab;

        $this->_objTpl->loadTemplatefile('module_podcast_settings.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_PODCAST_SETTINGS'];

        $this->_objTpl->setVariable(array(
            'TXT_PODCAST_SETTINGS'              => $_ARRAYLANG['TXT_PODCAST_SETTINGS'],
            'TXT_PODCAST_STANDARD_DIMENSIONS'   => $_ARRAYLANG['TXT_PODCAST_STANDARD_DIMENSIONS'],
            'TXT_PODCAST_PIXEL_WIDTH'           => $_ARRAYLANG['TXT_PODCAST_PIXEL_WIDTH'],
            'TXT_PODCAST_PIXEL_HEIGHT'          => $_ARRAYLANG['TXT_PODCAST_PIXEL_HEIGHT'],
            'TXT_PODCAST_LATEST_MEDIA_COUNT'    => $_ARRAYLANG['TXT_PODCAST_LATEST_MEDIA_COUNT'],
            'TXT_PODCAST_FEED_TITLE'            => $_ARRAYLANG['TXT_PODCAST_FEED_TITLE'],
            'TXT_PODCAST_FEED_DESCRIPTION'      => $_ARRAYLANG['TXT_PODCAST_FEED_DESCRIPTION'],
            'TXT_PODCAST_FEED_IMAGE'            => $_ARRAYLANG['TXT_PODCAST_FEED_IMAGE'],
            'TXT_PODCAST_FEED_LINK'             => $_ARRAYLANG['TXT_PODCAST_FEED_LINK'],
            'TXT_PODCAST_SAVE'                  => $_ARRAYLANG['TXT_PODCAST_SAVE'],
            'TXT_PODCAST_PLACEHOLDERS'          => $_ARRAYLANG['TXT_PODCAST_PLACEHOLDERS'],
            'TXT_PODCAST_GENERAL'               => $_ARRAYLANG['TXT_PODCAST_GENERAL'],
            'TXT_PODCAST_BLOCK_TEMPLATE'        => $_ARRAYLANG['TXT_PODCAST_BLOCK_TEMPLATE'],
            'TXT_PODCAST_BLOCK_SETTINGS'        => $_ARRAYLANG['TXT_PODCAST_BLOCK_SETTINGS'],
            'TXT_PODCAST_SHOW_HOME_CONTENT'     => $_ARRAYLANG['TXT_PODCAST_SHOW_HOME_CONTENT'],
            'TXT_PODCAST_DEACTIVATE'            => $_ARRAYLANG['TXT_PODCAST_DEACTIVATE'],
            'TXT_PODCAST_ACTIVATE'              => $_ARRAYLANG['TXT_PODCAST_ACTIVATE'],
            'TXT_PODCAST_HOMECONTENT_USAGE'     => $_ARRAYLANG['TXT_PODCAST_HOMECONTENT_USAGE'],
            'TXT_PODCAST_HOMECONTENT_USAGE_TEXT'=> $_ARRAYLANG['TXT_PODCAST_HOMECONTENT_USAGE_TEXT'],
            'TXT_PODCAST_CATEGORIES'            => $_ARRAYLANG['TXT_PODCAST_CATEGORIES'],
            'TXT_PODCAST_THUMB_MAX_SIZE'        => $_ARRAYLANG['TXT_PODCAST_THUMB_MAX_SIZE'],
            'TXT_PODCAST_THUMB_MAX_SIZE_HOMECONTENT' => $_ARRAYLANG['TXT_PODCAST_THUMB_MAX_SIZE_HOMECONTENT'],
            'TXT_PODCAST_PIXEL'                 => $_ARRAYLANG['TXT_PODCAST_PIXEL'],
            'TXT_PODCAST_PLAY'                  => $_ARRAYLANG['TXT_PODCAST_PLAY'],
            'TXT_PODCAST_MEDIA_DATE'            => $_ARRAYLANG['TXT_PODCAST_MEDIA_DATE'],
            'TXT_PODCAST_MEDIA_TITLE'           => $_ARRAYLANG['TXT_PODCAST_MEDIA_TITLE'],
            'TXT_PODCAST_MEDIA_PLAYLENGTH'      => $_ARRAYLANG['TXT_PODCAST_MEDIA_PLAYLENGTH'],
            'TXT_PODCAST_MEDIA_ID'              => $_ARRAYLANG['TXT_PODCAST_MEDIA_ID'],
            'TXT_PODCAST_MEDIA_VIEWS_COUNT'     => $_ARRAYLANG['TXT_PODCAST_MEDIA_VIEWS_COUNT'],
            'TXT_PODCAST_MEDIA_VIEWS'           => $_ARRAYLANG['TXT_PODCAST_MEDIA_VIEWS'],
            'TXT_PODCAST_MEDIA_AUTHOR'          => $_ARRAYLANG['TXT_PODCAST_MEDIA_AUTHOR'],
            'TXT_PODCAST_MEDIA_SHORT_PLAYLENGTH'=> $_ARRAYLANG['TXT_PODCAST_MEDIA_SHORT_PLAYLENGTH'],
            'TXT_PODCAST_MEDIA_PLAYLENGTH'      => $_ARRAYLANG['TXT_PODCAST_MEDIA_PLAYLENGTH'],
            'TXT_PODCAST_MEDIA_URL'             => $_ARRAYLANG['TXT_PODCAST_MEDIA_URL'],
            'TXT_PODCAST_MEDIA_THUMBNAIL'       => $_ARRAYLANG['TXT_PODCAST_MEDIA_THUMBNAIL'],
            'TXT_PODCAST_MEDIA_SHORT_DATE'      => $_ARRAYLANG['TXT_PODCAST_MEDIA_SHORT_DATE'],
            'TXT_PODCAST_MEDIA_DESCRIPTION'     => $_ARRAYLANG['TXT_PODCAST_MEDIA_DESCRIPTION'],
            'TXT_PODCAST_AUTO_VALIDATE'         => $_ARRAYLANG['TXT_PODCAST_AUTO_VALIDATE'],
            'PODCAST_BROWSE'                    => self::getMediaBrowserButton(
                                                            $_ARRAYLANG['TXT_PODCAST_BROWSE'],
                                                            array(
                                                                'views' => 'filebrowser',
                                                                'type' => 'button',
                                                                'style'=> 'width:110px;'
                                                            ),
                                                            'mediaBrowserCallback'
                                                    ),
        ));

        if (isset($_POST['podcast_save_settings'])) {
            $arrNewSettings['auto_validate'] = $_POST['podcast_settings_auto_validate'] > 0 ? 1 : 0;

            if (!empty($_POST['podcast_settings_default_width'])) {
                $arrNewSettings['default_width'] = intval($_POST['podcast_settings_default_width']);
            }
            if (!empty($_POST['podcast_settings_default_height'])) {
                $arrNewSettings['default_height'] = intval($_POST['podcast_settings_default_height']);
            }

            $arrNewSettings['latest_media_count'] = !empty($_POST['podcast_settings_latest_media_count']) && intval($_POST['podcast_settings_latest_media_count']) > 0 ? intval($_POST['podcast_settings_latest_media_count']) : 1;
            $arrNewSettings['thumb_max_size'] = !empty($_POST['podcast_settings_thumb_max_size']) && intval($_POST['podcast_settings_thumb_max_size']) > 0 ? intval($_POST['podcast_settings_thumb_max_size']) : 50;
            $arrNewSettings['thumb_max_size_homecontent'] = !empty($_POST['podcast_settings_thumb_max_size_homecontent']) && intval($_POST['podcast_settings_thumb_max_size_homecontent']) > 0 ? intval($_POST['podcast_settings_thumb_max_size_homecontent']) : 50;

            $arrNewSettings['feed_title'] = isset($_POST['podcast_settings_feed_title']) ? $_POST['podcast_settings_feed_title'] : '';
            $arrNewSettings['feed_description'] = isset($_POST['podcast_settings_feed_description']) ? $_POST['podcast_settings_feed_description'] : '';
            $arrNewSettings['feed_image'] = isset($_POST['podcast_settings_feed_image']) ? $_POST['podcast_settings_feed_image'] : '';

            if ($this->_updateSettings($arrNewSettings) && $this->_updateHomeContentSettings()) {
                $this->_createRSS();
                $this->_strOkMessage = $_ARRAYLANG['TXT_PODCAST_UPDATE_SETTINGS_SUCCESSFULL'];
            } else {
                $this->_strErrMessage = $_ARRAYLANG['TXT_PODCAST_UPDATE_SETTINGS_FAILED'];
            }
        }

        $this->_objTpl->setVariable(array(
            'PODCAST_SETTINGS_DEFAULT_WIDTH'                => $this->_arrSettings['default_width'],
            'PODCAST_SETTINGS_DEFAULT_HEIGHT'               => $this->_arrSettings['default_height'],
            'PODCAST_SETTINGS_LATEST_MEDIA_COUNT'           => $this->_arrSettings['latest_media_count'],
            'PODCAST_SETTINGS_THUMB_MAX_SIZE'               => $this->_arrSettings['thumb_max_size'],
            'PODCAST_SETTINGS_THUMB_MAX_SIZE_HOMECONTENT'   => $this->_arrSettings['thumb_max_size_homecontent'],
            'PODCAST_SETTINGS_SHOW_HOMECONTENT_'.
            $_CONFIG['podcastHomeContent']                  => 'checked="checked"',
            'PODCAST_SETTINGS_AUTO_VALIDATE_'.
            $this->_arrSettings['auto_validate']            => 'checked="checked"',
            'PODCAST_SETTINGS_FEED_TITLE'                   => $this->_arrSettings['feed_title'],
            'PODCAST_SETTINGS_FEED_DESCRIPTION'             => $this->_arrSettings['feed_description'],
            'PODCAST_SETTINGS_FEED_IMAGE'                   => $this->_arrSettings['feed_image'],
            'PODCAST_SETTINGS_TAB'                          => $selectedTab,
            'PODCAST_SETTINGS_FEED_URL'                     => ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteFeedWebPath().'/podcast.xml'
        ));

        if(!in_array($selectedTab, $arrSettingsTabs)){
            $selectedTab = $defaultTab;
        }
        foreach ($arrSettingsTabs as $tab) {
            $this->_objTpl->setVariable(array(
                'PODCAST_SETTINGS_'.strtoupper($tab).'_DIV_DISPLAY' =>  sprintf('style="display: %s;"', ($selectedTab == $tab ? 'block' : 'none')),
                'PODCAST_SETTINGS_'.strtoupper($tab).'_TAB_CLASS'   =>  $selectedTab == $tab ? 'class="active"' : '',
            ));
        }
        $mediumCategories = array();

        if (isset($_POST['podcast_save_settings'])) {

            $arrPostCategories = !empty($_POST['podcast_medium_associated_category']) ? $_POST['podcast_medium_associated_category'] : array();
            foreach ($arrPostCategories as $categoryId => $status) {
                if (intval($status) == 1) {
                    array_push($mediumCategories, intval($categoryId));
                }
            }
            $this->_setHomecontentCategories($mediumCategories);
        } else {
            $mediumCategories = $this->_getHomecontentCategories();
        }

        $arrCategories = $this->_getCategories();
        $categoryNr = 0;
        $arrLanguages = \FWLanguage::getLanguageArray();

        foreach ($arrCategories as $categoryId => $arrCategory) {
            $column = $categoryNr % 3;
            $arrCatLangIds = $this->_getLangIdsOfCategory($categoryId);
            array_walk(
                $arrCatLangIds,
                function (&$cat, $k, $arrLanguages) {
                    $cat = $arrLanguages[$cat]['lang'];
                },
                $arrLanguages
            );
            $arrCategory['title'] .= ' ('.implode(', ', $arrCatLangIds).')';
            $this->_objTpl->setVariable(array(
                'PODCAST_CATEGORY_ID'                   => $categoryId,
                'PODCAST_CATEGORY_ASSOCIATED'           => in_array($categoryId, $mediumCategories) ? 'checked="checked"' : '',
                'PODCAST_SHOW_MEDIA_OF_CATEGORY_TXT'    => sprintf($_ARRAYLANG['TXT_PODCAST_SHOW_MEDIA_OF_CATEGORY'], $arrCategory['title']),
                'PODCAST_CATEGORY_NAME'                 => $arrCategory['title']
            ));
            $this->_objTpl->parse('podcast_medium_associated_category_'.$column);
            $categoryNr++;
        }
    }

    function _updateHomeContentSettings()
    {
        \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
        $status = false;
        if (isset($_POST['setHomeContent'])) {
            $setHomeContent = intval($_POST['setHomeContent']);
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('podcastHomeContent')) {
                $status = \Cx\Core\Setting\Controller\Setting::add('podcastHomeContent', $setHomeContent, 1,
                    \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('podcastHomeContent', $setHomeContent);
                $status = \Cx\Core\Setting\Controller\Setting::update('podcastHomeContent');
            }
        }
        return $status;
    }
}
?>
