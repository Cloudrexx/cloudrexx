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
 * Feed
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Paulo M. Santos <pmsantos@astalavista.net>
 * @package     cloudrexx
 * @subpackage  module_feed
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Feed\Controller;

/**
 * Includes
 */
\Env::get('ClassLoader')->loadFile( \Env::get('cx')->getCodeBaseLibraryPath() . '/PEAR/XML/RSS.class.php');

/**
 * Feed
 *
 * Manage CMS news feed
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Paulo M. Santos <pmsantos@astalavista.net>
 * @package     cloudrexx
 * @subpackage  module_feed
 */
class FeedManager extends FeedLibrary
{
    /**
     * @var    \Cx\Core\Html\Sigma
     */
    public $_objTpl;
    public $pageTitle;
    public $feedpath;
    /**
     * @var    NewsML
     */
    public $_objNewsML;

    private $act = '';

    function __construct()
    {
        global  $_ARRAYLANG, $objTemplate, $_CONFIG;

        $this->_objTpl = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseModulePath().'/Feed/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        if (isset($_GET['act']) && $_GET['act'] == 'settings' && isset($_POST['save'])) {
            $this->_saveSettings();
        }

        //feed path
        $this->feedpath = \Env::get('cx')->getWebsiteFeedPath() . '/';
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG, $_CONFIG;

        // links
        $objTemplate->setVariable("CONTENT_NAVIGATION", "
            <a href='?cmd=Feed' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_FEED_NEWS_FEED']."</a>
            ".($_CONFIG['feedNewsMLStatus'] == '1' ? "<a href='?cmd=Feed&amp;act=newsML' class='".($this->act == 'newsML' ? 'active' : '')."'>NewsML</a>" : "")."
            <a href='?cmd=Feed&amp;act=category' class='".($this->act == 'category' ? 'active' : '')."'>".$_ARRAYLANG['TXT_FEED_CATEGORIES']."</a>
            <a href='?cmd=Feed&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_FEED_SETTINGS']."</a>
        ");
    }

    // GET PAGE
    function getFeedPage()
    {
        global $_ARRAYLANG, $objTemplate;

        if (!isset($_GET['act'])){
            $_GET['act'] = '';
        }

        switch($_GET['act']){
            case 'edit':
                $this->_objTpl->loadTemplateFile('module_feed_edit.html', true, true);
                $this->pageTitle = $_ARRAYLANG['TXT_FEED_EDIT_NEWS_FEED'];
                $this->showEdit();
                break;
            case 'category':
                $this->_objTpl->loadTemplateFile('module_feed_category.html', true, true);
                $this->pageTitle = $_ARRAYLANG['TXT_FEED_CATEGORIES'];
                $this->showCategory();
                break;
            case 'catedit':
                $this->_objTpl->loadTemplateFile('module_feed_category_edit.html', true, true);
                $this->pageTitle = $_ARRAYLANG['TXT_FEED_EDIT_CATEGORIES'];
                $this->showCatEdit();
                break;
            case 'newsML':
                $this->_objNewsML = new NewsML(true);
                $this->_showNewsML();
                break;
            case 'settings':
                $this->_objTpl->loadTemplateFile('module_feed_settings.html');
                $this->pageTitle = $_ARRAYLANG['TXT_FEED_SETTINGs'];
                $this->_showSettings();
                break;
            default:
                $this->_objTpl->loadTemplateFile('module_feed.html', true, true);
                $this->pageTitle = $_ARRAYLANG['TXT_FEED_NEWS_FEED'];
                $this->showNews();
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE' => $this->pageTitle,
            'CONTENT_OK_MESSAGE' => isset($_SESSION['strOkMessage']) ? $_SESSION['strOkMessage'] : '',
            'CONTENT_STATUS_MESSAGE' => isset($_SESSION['strErrMessage']) ? $_SESSION['strErrMessage'] : '',
        ));

        unset($_SESSION['strOkMessage']);
        unset($_SESSION['strErrMessage']);

        $this->act = $_REQUEST['act'];
        $this->setNavigation();

        $objTemplate->setVariable('ADMIN_CONTENT', $this->_objTpl->get());
    }

    function _showSettings()
    {
        global $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->setVariable(array(
            'TXT_FEED_SETTINGS' => $_ARRAYLANG['TXT_FEED_SETTINGS'],
            'TXT_FEED_USE_NEWSML' => $_ARRAYLANG['TXT_FEED_USE_NEWSML'],
            'TXT_FEED_SAVE' => $_ARRAYLANG['TXT_FEED_SAVE'],
            'FEED_USE_NEWSML_CHECKED' => $_CONFIG['feedNewsMLStatus'] == '1' ? 'checked="checked"' : ''
        ));
    }

    function _saveSettings() {
        global $_CORELANG;

        $feedNewsMLStatus = isset($_POST['feedSettingsUseNewsML']) ? intval($_POST['feedSettingsUseNewsML']) : 0;

        \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
        if (isset($feedNewsMLStatus)) {
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('feedNewsMLStatus')) {
                \Cx\Core\Setting\Controller\Setting::add('feedNewsMLStatus', $feedNewsMLStatus, 1, \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('feedNewsMLStatus', $feedNewsMLStatus);
                \Cx\Core\Setting\Controller\Setting::update('feedNewsMLStatus');
            }
        }

        $_SESSION['strOkMessage'] = $_CORELANG['TXT_SETTINGS_UPDATED'];
    }

    function _showNewsML()
    {
        if (!isset($_REQUEST['tpl'])) {
            if (isset($_POST['newsMLAddCategory'])) {
                $_REQUEST['tpl'] = "editCategory";
            } elseif (isset($_POST['newsMLDeleteCategories'])) {
                $_REQUEST['tpl'] = "deleteCategories";
            } else {
                $_REQUEST['tpl'] = '';
            }
        }

        switch ($_REQUEST['tpl']) {
        case 'details':
            $this->_newsMLDetails();
            break;

        case 'editCategory':
            $this->_newsMLEditCategory();
            break;

        case 'deleteDocument':
            $this->_newsMLDeleteDocument();
            $this->_newsMLDetails();
            break;

        case 'deleteDocuments':
            $this->_newsMLDeleteDocuments();
            $this->_newsMLDetails();
            break;

        case 'deleteCategory':
            $this->_newsMLDeleteCategory();
            $this->_newsMLOverview();
            break;

        case 'deleteCategories':
            $this->_newsMLDeleteCategories();
            $this->_newsMLOverview();
            break;

        case 'updateCategory':
            $this->_newsMLSaveCategory();

        default:
            $this->_newsMLOverview();
            break;
        }
    }


    /**
     * Delete a newsML category
     * @access private
     * @global object $_ARRAYLANG
     */
    function _newsMLDeleteCategory()
    {
        global $_ARRAYLANG;

        $categoryId = isset($_GET['categoryId']) ? intval($_GET['categoryId']) : 0;
        if ($categoryId != 0) {
            if ($this->_objNewsML->deleteCategory($categoryId)) {
                $_SESSION['strOkMessage'] .= $_ARRAYLANG['TXT_FEED_CATEGORY_SUCCESSFULLY_DELETED']."<br />";
                $this->_objNewsML->initCategories();
            } else {
                $_SESSION['strErrMessage'] .= str_replace('%CATEGORY%', $this->_objNewsML->arrCategories[$categoryId]['name'], $_ARRAYLANG['TXT_FEED_CATEGORY_COULD_NOT_BE_DELETED']."<br />");
            }
            $_SESSION['strErrMessage'] .= str_replace('%CATEGORY%', $this->_objNewsML->arrCategories[$categoryId]['name'], $_ARRAYLANG['TXT_FEED_CATEGORY_COULD_NOT_BE_DELETED']."<br />");
        }
    }


    function _newsMLDeleteCategories()
    {
        global $_ARRAYLANG;

        if (isset($_POST['selectedNewsMLCatId']) && is_array($_POST['selectedNewsMLCatId']) && count($_POST['selectedNewsMLCatId'])>0) {
            $status = true;
            foreach ($_POST['selectedNewsMLCatId'] as $categoryId) {
                $categoryId = intval($categoryId);
                if ($categoryId != 0) {
                    if (!$this->_objNewsML->deleteCategory($categoryId)) {
                        $_SESSION['strErrMessage'] .= str_replace('%CATEGORY%', $this->_objNewsML->arrCategories[$categoryId]['name'], $_ARRAYLANG['TXT_FEED_CATEGORY_COULD_NOT_BE_DELETED']."<br />");
                        $status = false;
                    }
                }
            }

            if ($status) {
                if (count($_POST['selectedNewsMLCatId'])>1) {
                    $_SESSION['strOkMessage'] .= $_ARRAYLANG['TXT_FEED_CATEGORIES_SUCCESSFULLY_DELETED']."<br />";
                } else {
                    $_SESSION['strOkMessage'] .= $_ARRAYLANG['TXT_FEED_CATEGORY_SUCCESSFULLY_DELETED']."<br />";
                }
            }
            $this->_objNewsML->initCategories();
        }
    }


    function _newsMLEditCategory()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->loadTemplateFile('module_feed_newsml_modify_category.html');
        $this->pageTitle = "NewsML";

        $arrWeekDays = explode(',', $_CORELANG['TXT_DAY_ARRAY']);
        $arrMonths = explode(',', $_CORELANG['TXT_MONTH_ARRAY']);

        $this->_objTpl->setVariable(array(
            'FEED_CATEGORY_TITLE' => $_ARRAYLANG['TXT_FEED_EDIT_CAT'],
            'TXT_FEED_SHOW_PICTURES' => $_ARRAYLANG['TXT_FEED_SHOW_PICTURES'],
            'TXT_FEED_YES' => $_ARRAYLANG['TXT_FEED_YES'],
            'TXT_FEED_NO' => $_ARRAYLANG['TXT_FEED_NO'],
            'TXT_FEED_NAME' => $_ARRAYLANG['TXT_FEED_NAME'],
            'TXT_FEED_NEWSML_PROVIDER' => $_ARRAYLANG['TXT_FEED_NEWSML_PROVIDER'],
            'TXT_FEED_NEWSML_SUBJECT_CODES' => $_ARRAYLANG['TXT_FEED_THEMES'],
            'TXT_FEED_NEWSML_MSG_COUNT' => $_ARRAYLANG['TXT_FEED_NUMBER_OF_NEWS_MSGS'],
            'TXT_FEED_LAYOUT' => $_ARRAYLANG['TXT_FEED_LAYOUT'],
            'TXT_FEED_STORE' => $_ARRAYLANG['TXT_SAVE'],
            'TXT_FEED_SUBJECT_CODES_SEPARATED' => $_ARRAYLANG['TXT_FEED_SUBJECT_CODES_SEPARATED'],
            'TXT_FEED_BACK' => $_ARRAYLANG['TXT_FEED_BACK'],
            'TXT_FEED_PLACEHOLDERS' => $_ARRAYLANG['TXT_FEED_PLACEHOLDERS'],
            'TXT_FEED_PLACEHOLDER' => $_ARRAYLANG['TXT_FEED_PLACEHOLDER'],
            'TXT_FEED_DESCRIPTION' => $_ARRAYLANG['TXT_FEED_DESCRIPTION'],
            'TXT_FEED_DATE' => $_ARRAYLANG['TXT_FEED_DATE'],
            'TXT_FEED_TITLE' => $_ARRAYLANG['TXT_FEED_TITLE'],
            'TXT_FEED_ID_OF_NEWS_MSG' => $_ARRAYLANG['TXT_FEED_ID_OF_NEWS_MSG'],
            'TXT_FEED_CONTENT_OF_NEWS_MSG' => $_ARRAYLANG['TXT_FEED_CONTENT_OF_NEWS_MSG'],
            'TXT_FEED_AVAILABILITY_OF_PLACEHOLDERS' => $_ARRAYLANG['TXT_FEED_AVAILABILITY_OF_PLACEHOLDERS'],
            'FEED_DATE' => $arrWeekDays[date('w')].', '.date('j').'. '.$arrMonths[date('n')-1].' '.date('Y').' / '.date('G:i').' h',
            'FEED_LONG_DATE' => date(ASCMS_DATE_FORMAT),
            'FEED_SHORT_DATE' => date(ASCMS_DATE_FORMAT_DATE)
        ));

        $categoryId = intval($_REQUEST['categoryId']);
        if (isset($this->_objNewsML->arrCategories[$categoryId])) {
            $this->_objTpl->setVariable(array(
                'FEED_NEWSML_CATEGORY_ID' => $categoryId,
                'FEED_NEWSML_CATEGORY_NAME' => $this->_objNewsML->arrCategories[$categoryId]['name'],
                'FEED_NEWSML_PROVIDER_MENU' => $this->_objNewsML->getProviderMenu($categoryId, 'name="feedNewsMLProviderId" style="width:300px;"'),
                'FEED_NEWSML_SUBJECT_CODES_MENU' => $this->_objNewsML->getSubjectCodesMenu($categoryId, 'name="feedNewsMLSubjectCode" style="width:300px;" onchange="document.getElementById(\'feedNewsMLSubjectBoxExclusive\').style.display=(this.value == \'all\' ? \'none\' : \'block\')"'),
                'FEED_NEWSML_SUBJECT_CODES_STYLE' => $this->_objNewsML->arrCategories[$categoryId]['showSubjectCodes'] == 'all' ? "none" : "block",
                'FEED_NEWSML_SUBJECT_CODES' => implode(',', $this->_objNewsML->arrCategories[$categoryId]['subjectCodes']),
                'FEED_NEWSML_CATEGORY_MSG_COUNT' => $this->_objNewsML->arrCategories[$categoryId]['limit'],
                'FEED_NEWSML_CATEGORY_TEMPLATE' => htmlentities(preg_replace('/\{([A-Za-z0-9_]*?)\}/', '[[\\1]]', $this->_objNewsML->arrCategories[$categoryId]['template']), ENT_QUOTES, CONTREXX_CHARSET),
                'FEED_NEWSML_SHOW_PICS_YES' => $this->_objNewsML->arrCategories[$categoryId]['showPics'] == '1' ? 'checked="checked"' : '',
                'FEED_NEWSML_SHOW_PICS_NO' => $this->_objNewsML->arrCategories[$categoryId]['showPics'] == '1' ? '' : 'checked="checked"'
            ));
        } elseif ($categoryId == 0) {
            $this->_objTpl->setVariable(array(
                'FEED_NEWSML_PROVIDER_MENU' => $this->_objNewsML->getProviderMenu($categoryId, 'name="feedNewsMLProviderId" style="width:300px;"'),
                'FEED_NEWSML_SUBJECT_CODES_MENU' => $this->_objNewsML->getSubjectCodesMenu(0, 'name="feedNewsMLSubjectCode" style="width:300px;" onchange="document.getElementById(\'feedNewsMLSubjectBoxExclusive\').style.display=(this.value == \'all\' ? \'none\' : \'block\')"'),
                'FEED_NEWSML_SUBJECT_CODES_STYLE' => "none",
                'FEED_NEWSML_CATEGORY_MSG_COUNT' => $this->_objNewsML->standardMessageCount,
                'FEED_NEWSML_SHOW_PICS_YES' => 'checked="checked"',
                'FEED_NEWSML_SHOW_PICS_NO' => ''
            ));
        } else {
            $this->_newsMLOverview();
        }
    }

    /**
     * Delete a NewsML document
     * @access private
     * @global $_ARRAYLANG
     */
    function _newsMLDeleteDocument()
    {
        global $_ARRAYLANG;

        $id = intval($_GET['publicIdentifier']);
        if ($this->_objNewsML->deleteDocument($id)) {
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_NEWS_MSG_DELETED_SUCCESSFULLY'];
        } else {
            $arrNewsMLDocuments = $this->_objNewsML->getDocuments($id);
            $_SESSION['strErrMessage'] = str_replace('%NAME%', $arrNewsMLDocuments[$id]['headline'], $_ARRAYLANG['TXT_FEED_COULD_NOT_DELETE_NEWS_MSG']);
        }
    }


    /**
     * Delete NewsML documents
     * @access private
     * @global $_ARRAYLANG
     */
    function _newsMLDeleteDocuments()
    {
        global $_ARRAYLANG;

        if (isset($_POST['selectedNewsMLDocId']) && is_array($_POST['selectedNewsMLDocId']) && count($_POST['selectedNewsMLDocId'])) {
            $status = true;
            foreach ($_POST['selectedNewsMLDocId'] as $id) {
                $id = intval($id);
                $arrNewsMLDocuments = $this->_objNewsML->getDocuments($id);
                if ($id != 0) {
                    if (!$this->_objNewsML->deleteDocument($id)) {
                        $_SESSION['strErrMessage'] .= str_replace('%NAME%', $arrNewsMLDocuments[$id]['headline'], $_ARRAYLANG['TXT_FEED_COULD_NOT_DELETE_NEWS_MSG']);
                        $status = false;
                    }
                }
            }
            if ($status) {
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_NEWS_MSGS_DELETED_SUCCESSFULLY'];
            }
        }
    }

    function _newsMLDetails()
    {
        global $_ARRAYLANG, $_CONFIG;

        if (isset($_REQUEST['providerId']) && isset($this->_objNewsML->arrCategories[$_REQUEST['providerId']])) {
            $paging = "";
            $providerId = intval($_REQUEST['providerId']);
            $this->pageTitle = 'NewsML';
            $this->_objTpl->loadTemplateFile('module_feed_newsml_details.html');
            $this->_objTpl->setVariable('FEED_NEWSML_TITLE', str_replace('%NAME%', $this->_objNewsML->arrCategories[$providerId]['name'], $_ARRAYLANG['TXT_FEED_NEWS_MSG_OF']));
            $this->_objNewsML->readDocuments($providerId);
            $arrNewsMLDocuments = $this->_objNewsML->getDocuments($providerId);
            if (count($arrNewsMLDocuments)>0) {
                $rowNr = 0;

                if (count($arrNewsMLDocuments)>intval($_CONFIG['corePagingLimit'])) {
                    if (isset($_GET['pos'])) {
                        $pos = intval($_GET['pos']);
                    } else {
                        $pos = 0;
                    }
                    $paging = $_ARRAYLANG['TXT_FEED_NEWS_MESSAGES'].' '.getPaging(count($arrNewsMLDocuments), $pos, "&cmd=Feed&act=newsML&tpl=details&providerId=".$providerId, $_ARRAYLANG['TXT_NEWS_MESSAGES'],true);
                } else {
                    $pos = 0;
                }

                $this->_objTpl->setVariable(array(
                    'TXT_FEED_MARKED' => $_ARRAYLANG['TXT_FEED_MARKED'],
                    'TXT_FEED_MARK_ALL' => $_ARRAYLANG['TXT_FEED_MARK_ALL'],
                    'TXT_FEED_REMOVE_CHOICE' => $_ARRAYLANG['TXT_FEED_REMOVE_CHOICE'],
                    'TXT_FEED_DELETE_MARKED' => $_ARRAYLANG['TXT_FEED_DELETE_MARKED'],
                    'TXT_FEED_BACK' => $_ARRAYLANG['TXT_FEED_BACK'],
                    'TXT_FEED_TITLE' => $_ARRAYLANG['TXT_FEED_TITLE'],
                    'TXT_FEED_DATE' => $_ARRAYLANG['TXT_FEED_DATE'],
                    'TXT_FEED_FUNCTIONS' => $_ARRAYLANG['TXT_FEED_FUNCTIONS'],
                    'TXT_FEED_ACTION_COULD_NOT_BE_UNDONE' => $_ARRAYLANG['TXT_FEED_ACTION_COULD_NOT_BE_UNDONE'],
                    'TXT_CONFIRM_DELETE_NEWS_MSG' => $_ARRAYLANG['TXT_CONFIRM_DELETE_NEWS_MSG'],
                    'TXT_CONFIRM_DELETE_NEWS_MSGS' => $_ARRAYLANG['TXT_CONFIRM_DELETE_NEWS_MSGS']
                ));

                $this->_objTpl->setGlobalVariable(array(
                    'FEED_NEWSML_PROVIDERID' => $providerId,
                    'TXT_FEED_SHOW_NEWS_MSG' => $_ARRAYLANG['TXT_FEED_SHOW_NEWS_MSG'],
                    'TXT_FEED_DELETE_NEWS_MSG' => $_ARRAYLANG['TXT_FEED_DELETE_NEWS_MSG']
                ));

                foreach ($arrNewsMLDocuments as $newsMLDocumentId => $arrNewsMLDocument) {
                    if ($rowNr>=$pos && $rowNr<($pos+intval($_CONFIG['corePagingLimit']))) {
                        $this->_objTpl->setVariable(array(
                            'FEED_NEWSML_ID' => $newsMLDocumentId,
                            'FEED_NEWSML_CATID' => $providerId,
                            'FEED_NEWSML_LIST_ROW_CLASS' => $rowNr % 2 == 0 ? "row2" : "row1",
                            'FEED_NEWSML_TITLE' => $arrNewsMLDocument['headLine'],
                            'FEED_NEWSML_DATE' => date(ASCMS_DATE_FORMAT, $arrNewsMLDocument['thisRevisionDate']),
                            'FEED_NEWSML_RANK' => $arrNewsMLDocument['urgency']
                        ));
                        $this->_objTpl->parse('feed_newsml_list');
                    }

                    $rowNr++;
                }

                $this->_objTpl->touchBlock('feed_newsml_data');
                $this->_objTpl->hideBlock('feed_newsml_nodata');
            } else {
                $this->_objTpl->setVariable(array(
                    'TXT_FEED_NO_NEWS_MSGS_PRESENT' => $_ARRAYLANG['TXT_FEED_NO_NEWS_MSGS_PRESENT'],
                    'TXT_FEED_BACK' => $_ARRAYLANG['TXT_FEED_BACK']
                ));

                $this->_objTpl->touchBlock('feed_newsml_nodata');
                $this->_objTpl->hideBlock('feed_newsml_data');
            }
            $this->_objTpl->setVariable('FEED_NEWSML_LIST_PARSING', $paging);
        } else {
            $this->_newsMLOverview();
        }
    }

    /**
     * Add or update a newsML category
     * @access private
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     */
    function _newsMLSaveCategory()
    {
        global $_ARRAYLANG;

        if (isset($_POST['save'])) {
            $categoryId = isset($_GET['categoryId']) ? intval($_GET['categoryId']) : 0;
            $categoryName = isset($_POST['feedNewsMLCategoryName']) ? preg_replace('/[^a-zA-Z0-9-_\s]+/', '', $_POST['feedNewsMLCategoryName']) : '';
            $providerId = isset($_POST['feedNewsMLProviderId']) ? intval($_POST['feedNewsMLProviderId']) : 0;
            $arrSubjectCodeMethods = array('all', 'only', 'exclude');
            $subjectCodeMethod = (isset($_POST['feedNewsMLSubjectCode']) && in_array($_POST['feedNewsMLSubjectCode'], $arrSubjectCodeMethods)) ? $_POST['feedNewsMLSubjectCode'] : 'all';
            $arrTmpSubjectCodes = isset($_POST['feedNewsMLSubjects']) ? explode(',', $_POST['feedNewsMLSubjects']) : array();
            $arrSubjectCodes = array();
            foreach ($arrTmpSubjectCodes as $subjectCode) {
                array_push($arrSubjectCodes, intval($subjectCode));
            }
            $msgCount = isset($_POST['feedNewsMLCategoryMsgCount']) ? intval($_POST['feedNewsMLCategoryMsgCount']) : $this->_objNewsML->standardMessageCount;
            $showPics = isset($_POST['feedNewsMLCategoryShowPics']) ? intval($_POST['feedNewsMLCategoryShowPics']) : 0;
            $templateHtml = isset($_POST['feedNewsMLCategoryTemplate']) ? contrexx_addslashes($_POST['feedNewsMLCategoryTemplate']) : '';
            $templateHtml = preg_replace('/\[\[([A-Za-z0-9_]*?)\]\]/', '{\\1}', $templateHtml);

            if ($categoryId != 0) {
                if ($this->_objNewsML->updateCategory($categoryId, $providerId, $categoryName, $arrSubjectCodes, $subjectCodeMethod, $templateHtml, $msgCount, $showPics) === false) {
                    $_SESSION['strErrMessage'] .= $_ARRAYLANG['TXT_FEED_CATEGORY_COULD_NOT_BE_UPDATED']."<br />";
                }
            } else {
                if ($this->_objNewsML->addCategory($providerId, $categoryName, $arrSubjectCodes, $subjectCodeMethod, $templateHtml, $msgCount, $showPics) === false) {
                    $_SESSION['strErrMessage'] .= $_ARRAYLANG['TXT_FEED_COULD_NOT_ADD_CATEGORY']."<br />";
                }
            }
            $this->_objNewsML->initCategories();
        }
    }

    /**
     * Show NewsML categories page
     * @access private
     * @global object $objDatabase
     */
    function _newsMLOverview()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_feed_newsml_overview.html');
        $this->pageTitle = 'NewsML';

        $rowNr = 0;

        $this->_objTpl->setVariable(array(
            'TXT_FEED_MARKED' => $_ARRAYLANG['TXT_FEED_MARKED'],
            'TXT_FEED_MARK_ALL' => $_ARRAYLANG['TXT_FEED_MARK_ALL'],
            'TXT_FEED_REMOVE_CHOICE' => $_ARRAYLANG['TXT_FEED_REMOVE_CHOICE'],
            'TXT_FEED_DELETE_MARKED' => $_ARRAYLANG['TXT_FEED_DELETE_MARKED'],
            'TXT_FEED_NEWSML_CATEGORIES' => $_ARRAYLANG['TXT_FEED_NEWSML_CATEGORIES'],
            'TXT_FEED_CATEGORY' => $_ARRAYLANG['TXT_FEED_CATEGORY'],
            'TXT_FEED_TEMPLATE_PLACEHOLDER' => $_ARRAYLANG['TXT_FEED_TEMPLATE_PLACEHOLDER'],
            'TXT_FEED_NEWSML_PROVIDER' => $_ARRAYLANG['TXT_FEED_NEWSML_PROVIDER'],
            'TXT_FEED_FUNCTIONS' => $_ARRAYLANG['TXT_FEED_FUNCTIONS'],
            'TXT_FEED_SHOW_DETAILS' => $_ARRAYLANG['TXT_FEED_SHOW_DETAILS'],
            'TXT_FEED_EDIT_CATEGORY' => $_ARRAYLANG['TXT_FEED_EDIT_CATEGORY'],
            'TXT_FEED_INSERT_CATEGORY' => $_ARRAYLANG['TXT_FEED_INSERT_CATEGORY'],
            'TXT_FEED_INFO' => $_ARRAYLANG['TXT_FEED_INFO'],
            'TXT_FEED_WHAT_IS_NEWSML' => $_ARRAYLANG['TXT_FEED_WHAT_IS_NEWSML'],
            'TXT_FEED_NEWSML_DESCRIPTION' => $_ARRAYLANG['TXT_FEED_NEWSML_DESCRIPTION'],
            'TXT_FEED_CONFIRM_DELETE_CATEGORY' => $_ARRAYLANG['TXT_FEED_CONFIRM_DELETE_CATEGORY'],
            'TXT_FEED_CONFIRM_DELETE_CATEGORIES' => $_ARRAYLANG['TXT_FEED_CONFIRM_DELETE_CATEGORIES'],
            'TXT_FEED_ACTION_COULD_NOT_BE_UNDONE' => $_ARRAYLANG['TXT_FEED_ACTION_COULD_NOT_BE_UNDONE']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_FEED_SHOW_DETAILS' => $_ARRAYLANG['TXT_FEED_SHOW_DETAILS'],
            'TXT_FEED_EDIT_CATEGORY' => $_ARRAYLANG['TXT_FEED_EDIT_CATEGORY'],
            'TXT_FEED_DELETE_CATEGORY' => $_ARRAYLANG['TXT_FEED_DELETE_CATEGORY']
        ));

        if (empty($this->_objNewsML->arrCategories)) {
            $this->_objTpl->hideBlock('feed_newsml_list');
            return;
        }
        foreach ($this->_objNewsML->arrCategories as $newsMLProviderId => $arrNewsMLProvider) {
            $this->_objTpl->setVariable(array(
                'FEED_NEWSML_CATEGORY_ID' => $newsMLProviderId,
                'FEED_NEWSML_ID' => $newsMLProviderId,
                'FEED_NEWSML_LIST_ROW_CLASS' => (++$rowNr % 2 ? 'row1' : 'row2'),
                'FEED_NEWSML_NAME' => $arrNewsMLProvider['name'],
                'FEED_NEWSML_PLACEHOLDER' => 'NEWSML_'.strtoupper(preg_replace('/\s/', '_', $arrNewsMLProvider['name'])),
                'FEED_NEWSML_PROVIDER' => $arrNewsMLProvider['providerName']
            ));
            $this->_objTpl->parse('feed_newsml_list');
        }
    }


    function showNews()
    {
        global $objDatabase, $_ARRAYLANG;

        //refresh
        if (isset($_GET['ref']) and $_GET['ref'] == 1 and isset($_GET['id']) and $_GET['id'] != ''){
            $id   = intval($_GET['id']);
            $time = time();
            $this->showNewsRefresh($id, $time, $this->feedpath);
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_REFRESH_NEWS_FEED'];
            $this->goToReplace('');
            die;
        }
        //preview
        if (isset($_GET['show']) and $_GET['show'] == 1 and isset($_GET['id']) and $_GET['id'] != ''){
            $id = intval($_GET['id']);
            $this->showNewsPreview($id);
            die;
        }
        //new
        if (isset($_GET['new']) and $_GET['new'] == 1){
            if ($_POST['form_category'] != '' and $_POST['form_name'] != '' and $_POST['form_articles'] != '' and $_POST['form_articles'] < 51 and $_POST['form_cache'] != '' and $_POST['form_image'] != '' and $_POST['form_status'] != '')
            {
                if ($_POST['form_file_name'] != '0' and $_POST['form_link'] == '' or $_POST['form_file_name'] == '0' and $_POST['form_link'] != ''){
                    $category  = intval($_POST['form_category']);
                    $name      = get_magic_quotes_gpc() ? strip_tags($_POST['form_name']) : addslashes(strip_tags($_POST['form_name']));
                    if ($_POST['form_file_name'] != '0'){
                        $link     = '';
                        $filename = get_magic_quotes_gpc() ? strip_tags($_POST['form_file_name']) : addslashes(strip_tags($_POST['form_file_name']));
                    }else{
                        $link     = get_magic_quotes_gpc() ? strip_tags($_POST['form_link']) : addslashes(strip_tags($_POST['form_link']));
                        $filename = '';
                    }
                    $articles  = intval($_POST['form_articles']);
                    $cache     = intval($_POST['form_cache']);
                    $time      = time();
                    $image     = intval($_POST['form_image']);
                    $status    = intval($_POST['form_status']);
                    $this->showNewsNew($category, $name, $link, $filename, $articles, $cache, $time, $image, $status);
                    $_SESSION['feedCategorySort'] = $category;
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SUCCESSFULL_NEWS'];;
                }else{
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_FILL_IN_ALL'];
                }
            }else{
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_FILL_IN_ALL'];
            }

            $this->goToReplace('');
            die;
        }

        //sortbycategory
        if (isset($_GET['sort']) and $_GET['sort'] != 0){
            $_SESSION['feedCategorySort'] = $_GET['sort'];
            $this->goToReplace('');
            die;
        }elseif (isset($_GET['sort']) and $_GET['sort'] == 0){
            unset($_SESSION['feedCategorySort']);
            $this->goToReplace('');
            die;
        }

        if (isset($_GET['chg']) and $_GET['chg'] == 1 and isset($_POST['form_selected']) and is_array($_POST['form_selected'])){
            //delete
            if ($_POST['form_delete'] != ''){
                $ids = $_POST['form_selected'];
                $this->showNewsDelete($ids);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_DELETED_NEWS'];
                $this->goToReplace('');
                die;
            }
            //changestatus
            if ($_POST['form_activate'] != '' or $_POST['form_deactivate'] != ''){
                $ids = $_POST['form_selected'];
                if ($_POST['form_activate'] != ''){
                    $this->showNewsChange($ids, 1);
                }
                if ($_POST['form_deactivate'] != ''){
                    $this->showNewsChange($ids, 0);
                }
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_STATUS'];
                $this->goToReplace('');
                die;
            }
        }

        //sort
        if (isset($_GET['chg']) and $_GET['chg'] == 1 and $_POST['form_sort'] != ''){
            $ids = $_POST['form_id'];
            $pos = $_POST['form_pos'];
            $this->showNewsChangePos($ids, $pos);
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SORT'];
            $this->goToReplace('');
            die;
        }
        if (isset($_GET['chg'])){
            $ids = $_POST['form_id'];
            $pos = $_POST['form_pos'];
            $this->showNewsChangePos($ids, $pos);
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SORT'];
            $this->goToReplace('');
            die;
        }

        //categories
        $query = "SELECT id,
                           name
                      FROM ".DBPREFIX."module_feed_category
                  ORDER BY pos";
        $objResult = $objDatabase->Execute($query);

        while (!$objResult->EOF) {
            $this->_objTpl->setVariable(array(
                'FEED_CATEGORY_ID' => $objResult->fields['id'],
                'FEED_CATEGORY' => $objResult->fields['name']
            ));
            $this->_objTpl->parse('feed_table_option');
            $objResult->MoveNext();
        }

        $query = "SELECT id,
                           name
                      FROM ".DBPREFIX."module_feed_category
                  ORDER BY pos";
        $objResult = $objDatabase->Execute($query);

        //categories for show only
        while (!$objResult->EOF) {
            $query = "SELECT subid
                           FROM ".DBPREFIX."module_feed_news
                          WHERE subid = '".$objResult->fields['id']."'";
            $objResult2 = $objDatabase->Execute($query);

            if ($objResult2->RecordCount() != 0){
                $selected = '';
                if (   isset($_SESSION['feedCategorySort'])
                    && $_SESSION['feedCategorySort'] == $objResult->fields['id']) {
                    $selected = ' selected="selected"';
                }

                $this->_objTpl->setVariable(array(
                    'FEED_CATEGORY_ID' => $objResult->fields['id'],
                    'FEED_SELECTED' => $selected,
                    'FEED_CATEGORY' => $objResult->fields['name']
                ));
                $this->_objTpl->parse('feed_category_option');
            }
            $objResult->MoveNext();
        }

        //directory
        $filename = array();
        $dir = opendir ($this->feedpath);
        $file = readdir($dir);
        while ($file !== false) {
            if ($file != '.'
                 && $file != '..' and $file != 'index.html' and substr($file, 0, 5) != 'feed_'){
                  $filename[] = $file;
            }
            $file = readdir($dir);
        }
        closedir($dir);

        asort($filename);
        foreach($filename as $file){
            $this->_objTpl->setVariable('FEED_NAME', $file);
            $this->_objTpl->parse('feed_table_option_name');
        }

        //lang
        $to_lang    = '';
        $to_lang[0] = '';

        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $to_lang[$frontendLanguage['id']] = $frontendLanguage['lang'];
        }

        //table
        if (!isset($_SESSION['feedCategorySort']) or $_SESSION['feedCategorySort'] == 0){
            $query = "SELECT id,
                               name,
                               lang
                          FROM ".DBPREFIX."module_feed_category
                      ORDER BY pos";
            $objResult = $objDatabase->Execute($query);
        } else {
            $query = "SELECT id,
                               name,
                               lang
                          FROM ".DBPREFIX."module_feed_category
                         WHERE id = '".$_SESSION['feedCategorySort']."'
                      ORDER BY pos";
            $objResult = $objDatabase->Execute($query);

            $query = "SELECT id
                           FROM ".DBPREFIX."module_feed_news
                          WHERE subid = '".$_SESSION['feedCategorySort']."'";
            $objResult2 = $objDatabase->Execute($query);

            if ($objResult2->RecordCount() == 0){
                unset($_SESSION['feedCategorySort']);
                $query = "SELECT id,
                                   name,
                                   lang
                              FROM ".DBPREFIX."module_feed_category
                          ORDER BY pos";
                $objResult = $objDatabase->Execute($query);
            }
        }

        $i             = 0;
        $total_records = 0;
        while (!$objResult->EOF) {
            $query =     "SELECT id,
                                name,
                                articles,
                                cache,
                  FROM_UNIXTIME(time, '%H:%i - %d.%m.%Y') AS time,
                                status,
                                pos
                           FROM ".DBPREFIX."module_feed_news
                          WHERE subid = '".$objResult->fields['id']."'
                       ORDER BY pos";
            $objResult2 = $objDatabase->Execute($query);

            $total_records = $total_records + $objResult->RecordCount();

            while (!$objResult2->EOF) {
                ($i % 2)                 ? $class  = 'row1'   : $class  = 'row2';
                ($objResult2->fields['status'] == 1) ? $status = 'green'  : $status = 'red';

                $this->_objTpl->setVariable(array(
                    'FEED_CLASS' => $class,
                    'FEED_STATUS' => $status,
                    'FEED_ID' => $objResult2->fields['id'],
                    'FEED_POS' => $objResult2->fields['pos'],
                    'FEED_NAME' => $objResult2->fields['name'],
                    'FEED_LANG' => $to_lang[$objResult->fields['lang']],
                    'FEED_CATEGORY' => $objResult->fields['name'],
                    'FEED_ARTICLE' => $objResult2->fields['articles'],
                    'FEED_CACHE' => $objResult2->fields['cache'],
                    'FEED_TIME' => $objResult2->fields['time'],
                    'TXT_FEED_EDIT' => $_ARRAYLANG['TXT_FEED_EDIT'],
                    'TXT_FEED_UPDATE' => $_ARRAYLANG['TXT_FEED_UPDATE'],
                    'TXT_FEED_PREVIEW' => $_ARRAYLANG['TXT_FEED_PREVIEW']
                ));

                $this->_objTpl->parse('feed_table_row');
                $objResult2->MoveNext();
                $i++;
            }
            $objResult->MoveNext();
        }

        $this->_objTpl->setVariable('FEED_TOTAL_RECORDS', $total_records);

        //make visible
        if ($i > 0)
        {
            $this->_objTpl->setVariable(array(
                'FEED_RECORDS_HIDDEN' => '&nbsp;',
                'TXT_FEED_MARK_ALL' => $_ARRAYLANG['TXT_FEED_MARK_ALL'],
                'TXT_FEED_REMOVE_CHOICE' => $_ARRAYLANG['TXT_FEED_REMOVE_CHOICE'],
                'TXT_FEED_SELECT_OPERATION' => $_ARRAYLANG['TXT_FEED_SELECT_OPERATION'],
                'TXT_FEED_SAVE_SORTING' => $_ARRAYLANG['TXT_FEED_SAVE_SORTING'],
                'TXT_FEED_ACTIVATE_NEWS_FEED' => $_ARRAYLANG['TXT_FEED_ACTIVATE_NEWS_FEED'],
                'TXT_FEED_DEACTIVATE_NEWS_FEED' => $_ARRAYLANG['TXT_FEED_DEACTIVATE_NEWS_FEED'],
                'TXT_FEED_DELETE_NEWS_FEED' => $_ARRAYLANG['TXT_FEED_DELETE_NEWS_FEED']
            ));
            $this->_objTpl->parse('feed_table_hidden');
        }

        //parse $_ARRAYLANG
        $this->_objTpl->setVariable(array(
            'TXT_FEED_INSERT_NEW_FEED' => $_ARRAYLANG['TXT_FEED_INSERT_NEW_FEED'],
            'TXT_FEED_CATEGORY' => $_ARRAYLANG['TXT_FEED_CATEGORY'],
            'TXT_FEED_CHOOSE_CATEGORY' => $_ARRAYLANG['TXT_FEED_CHOOSE_CATEGORY'],
            'TXT_FEED_NAME' => $_ARRAYLANG['TXT_FEED_NAME'],
            'TXT_FEED_LINK' => $_ARRAYLANG['TXT_FEED_LINK'],
            'TXT_FEED_FILE_NAME' => $_ARRAYLANG['TXT_FEED_FILE_NAME'],
            'TXT_FEED_CHOOSE_FILE_NAME' => $_ARRAYLANG['TXT_FEED_CHOOSE_FILE_NAME'],
            'TXT_FEED_NUMBER_ARTICLES' => $_ARRAYLANG['TXT_FEED_NUMBER_ARTICLES'],
            'TXT_FEED_CACHE_TIME' => $_ARRAYLANG['TXT_FEED_CACHE_TIME'],
            'TXT_FEED_SHOW_LOGO' => $_ARRAYLANG['TXT_FEED_SHOW_LOGO'],
            'TXT_FEED_NO' => $_ARRAYLANG['TXT_FEED_NO'],
            'TXT_FEED_YES' => $_ARRAYLANG['TXT_FEED_YES'],
            'TXT_FEED_STATUS' => $_ARRAYLANG['TXT_FEED_STATUS'],
            'TXT_FEED_INACTIVE' => $_ARRAYLANG['TXT_FEED_INACTIVE'],
            'TXT_FEED_ACTIVE' => $_ARRAYLANG['TXT_FEED_ACTIVE'],
            'TXT_FEED_SAVE' => $_ARRAYLANG['TXT_FEED_SAVE'],
            'TXT_FEED_SORTING' => $_ARRAYLANG['TXT_FEED_SORTING'],
            'TXT_FEED_STATUS' => $_ARRAYLANG['TXT_FEED_STATUS'],
            'TXT_FEED_ID' => $_ARRAYLANG['TXT_FEED_ID'],
            'TXT_FEED_NEWS_NAME' => $_ARRAYLANG['TXT_FEED_NEWS_NAME'],
            'TXT_FEED_LANGUAGE' => $_ARRAYLANG['TXT_FEED_LANGUAGE'],
            'TXT_FEED_ALL_CATEGORIES' => $_ARRAYLANG['TXT_FEED_ALL_CATEGORIES'],
            'TXT_FEED_ARTICLE' => $_ARRAYLANG['TXT_FEED_ARTICLE'],
            'TXT_FEED_CACHE_TIME' => $_ARRAYLANG['TXT_FEED_CACHE_TIME'],
            'TXT_FEED_LAST_UPDATE' => $_ARRAYLANG['TXT_FEED_LAST_UPDATE'],
            'TXT_FEED_FORMCHECK_CATEGORY' => $_ARRAYLANG['TXT_FEED_FORMCHECK_CATEGORY'],
            'TXT_FEED_FORMCHECK_NAME' => $_ARRAYLANG['TXT_FEED_FORMCHECK_NAME'],
            'TXT_FEED_FORMCHECK_LINK_FILE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_LINK_FILE'],
            'TXT_FEED_FORMCHECK_ARTICLES' => $_ARRAYLANG['TXT_FEED_FORMCHECK_ARTICLES'],
            'TXT_FEED_FORMCHECK_CACHE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_CACHE'],
            'TXT_FEED_FORMCHECK_IMAGE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_IMAGE'],
            'TXT_FEED_FORMCHECK_STATUS' => $_ARRAYLANG['TXT_FEED_FORMCHECK_STATUS'],
            'TXT_FEED_DELETE_CONFIRM' => $_ARRAYLANG['TXT_FEED_DELETE_CONFIRM'],
            'TXT_FEED_NO_SELECT_OPERATION' => $_ARRAYLANG['TXT_FEED_NO_SELECT_OPERATION']
        ));
    }


    function showNewsPreview($id)
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "SELECT filename,
             FROM_UNIXTIME(time, '%d. %M %Y, %H:%i') AS time
                      FROM ".DBPREFIX."module_feed_news
                     WHERE id = '".$id."'";
        $objResult = $objDatabase->Execute($query);

        $filename = $this->feedpath.$objResult->fields['filename'];

        //rss class
        $rss = new \XML_RSS($filename);
        $rss->parse();

        //channel info
        $info = $rss->getChannelInfo();
        echo "<b>".strip_tags($info['title'])."</b><br />";
        echo $_ARRAYLANG['TXT_FEED_LAST_UPDATE'].": ".$objResult->fields['time']."<br />";

        //image
        foreach($rss->getImages() as $img) {
            if ($img['url'] != '') {
                echo '<img src="'.strip_tags($img['url']).'" /><br />';
            }
        }

        echo '<br />';
        echo '<i>'.$_ARRAYLANG['TXT_FEED_MESSAGE_IMPORTANT'].'</i><br />';

        //items
        foreach ($rss->getItems() as $value) {
            echo '<li>'.strip_tags($value['title']).'</li>';
        }
    }


    function showNewsNew($category, $name, $link, $filename, $articles, $cache, $time, $image, $status)
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "SELECT id
                      FROM ".DBPREFIX."module_feed_news
              WHERE BINARY name = '".$name."'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount() == 0){
            if ($link != ''){
                //copy
                $filename = "feed_".$time."_".basename($link);
                if (!copy($link, $this->feedpath.$filename)){
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_LINK_NO_NEWS'];
                    $this->goToReplace('');
                    die;
                }

                /*$str = file_get_contents($link);
                $fp = fopen($this->feedpath.$filename, "w");
                fwrite($fp, $str);
                fclose($fp);*/

                //rss class
                $rss = new \XML_RSS($this->feedpath.$filename);
                $rss->parse();
                $content = '';
                foreach($rss->getStructure() as $array){
                    $content .= $array;
                }
                if ($content == ''){
                    unlink($this->feedpath.$filename);
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_LINK_NO_NEWS'];
                    $this->goToReplace('');
                    die;
                }
            }

            //add new
            $query = "INSERT INTO ".DBPREFIX."module_feed_news
                                SET subid = '".$category."',
                                    name = '".$name."',
                                    link = '".$link."',
                                    filename = '".$filename."',
                                    articles = '".$articles."',
                                    cache = '".$cache."',
                                    status = '".$status."',
                                    time = '".$time."',
                                    image = '".$image."'";
            $objResult = $objDatabase->Execute($query);
        } else{
            $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_EXISTING_NEWS'];
            $this->goToReplace('');
            die;
        }
    }


    function showNewsDelete($ids)
    {
        global $objDatabase;

        foreach($ids as $id){
            $query = "SELECT id,
                               link,
                               filename
                          FROM ".DBPREFIX."module_feed_news
                         WHERE id = '".intval($id)."'";
            $objResult = $objDatabase->Execute($query);

            $link     = $objResult->fields['link'];
            $filename = $objResult->fields['filename'];

            if ($link != '') {
                @unlink($this->feedpath.$filename);
            }

            $query = "DELETE FROM ".DBPREFIX."module_feed_news
                         WHERE id = '".intval($id)."'";
            $objDatabase->Execute($query);
        }

        $query = "SELECT id
                      FROM ".DBPREFIX."module_feed_news";
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount() == 0){
            $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_feed_news");
        }
    }


    function showNewsChange($ids, $to)
    {
        global $objDatabase;

        foreach($ids as $id){
            $query = "UPDATE ".DBPREFIX."module_feed_news
                           SET status = '".$to."'
                         WHERE id = '".intval($id)."'";
            $objDatabase->Execute($query);
        }
    }


    function showNewsChangePos($ids, $pos)
    {
        global $objDatabase;

        for($x = 0; $x < count($ids); $x++){
            $query = "UPDATE ".DBPREFIX."module_feed_news
                           SET pos = '".intval($pos[$x])."'
                         WHERE id = '".intval($ids[$x])."'";
            $objDatabase->Execute($query);
        }
    }


    function showEdit()
    {
        global $objDatabase, $_ARRAYLANG;

        // check
        if (!isset($_GET['set'])){
            if (!isset($_GET['id']) or $_GET['id'] == ''){
                $this->goToReplace('');
                die;
            }
        }

        //set
        if (isset($_GET['set']) and $_GET['set'] == 1)
        {
            if ($_POST['form_id'] != '' and $_POST['form_category'] != '' and $_POST['form_name'] != '' and $_POST['form_articles'] != '' and $_POST['form_articles'] < 51 and $_POST['form_cache'] != '' and $_POST['form_image'] != '' and $_POST['form_status'] != '')
            {
                if ($_POST['form_link'] != '' and $_POST['form_file_name'] == '0' or $_POST['form_link'] == '' and $_POST['form_file_name'] != '0')
                {
                    $id       = intval($_POST['form_id']);
                    $subid    = intval($_POST['form_category']);
                    $name     = get_magic_quotes_gpc() ? strip_tags($_POST['form_name']) : addslashes(strip_tags($_POST['form_name']));
                    if ($_POST['form_file_name'] != '0')
                    {
                        $link     = '';
                        $filename = get_magic_quotes_gpc() ? strip_tags($_POST['form_file_name']) : addslashes(strip_tags($_POST['form_file_name']));
                    }
                    else
                    {
                        $link     = get_magic_quotes_gpc() ? strip_tags($_POST['form_link']) : addslashes(strip_tags($_POST['form_link']));
                        $filename = '';
                    }
                    $articles = intval($_POST['form_articles']);
                    $cache    = intval($_POST['form_cache']);
                    $time     = time();
                    $image    = intval($_POST['form_image']);
                    $status   = intval($_POST['form_status']);

                    $this->showEditSetNew($id, $subid, $name, $link, $filename, $articles, $cache, $time, $image, $status);
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SUCCESSFULL_EDIT_NEWS'];
                    $this->goToReplace('');
                    die;
                }
                else
                {
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_FILL_IN_ALL'];
                    $this->goToReplace('&act=edit&id='.$_POST['form_id']);
                    die;
                }
            }
            else
            {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_FILL_IN_ALL'];
                $this->goToReplace('&act=edit&id='.$_POST['form_id']);
                die;
            }
        }

        $query = "SELECT id,
                           subid,
                           name,
                           link,
                           filename,
                           articles,
                           cache,
                           image,
                           status
                      FROM ".DBPREFIX."module_feed_news
                     WHERE id = '".intval($_GET['id'])."'";
        $objResult = $objDatabase->Execute($query);

        $id        = $objResult->fields['id'];
        $subid     = $objResult->fields['subid'];
        $name      = $objResult->fields['name'];
        $link      = $objResult->fields['link'];
        $filename  = $objResult->fields['filename'];
        $articles  = $objResult->fields['articles'];
        $cache     = $objResult->fields['cache'];
        $image     = $objResult->fields['image'];
        $status    = $objResult->fields['status'];

        if ($image == 0) {
            $status_img0 = ' selected';
            $status_img1 = '';
        } else {
            $status_img0 = '';
            $status_img1 = ' selected';
        }

        if ($status == 0) {
            $status0 = ' selected';
            $status1 = '';
        } else {
            $status0 = '';
            $status1 = ' selected';
        }

        $this->_objTpl->setVariable(array(
            'FEED_ID' => $id,
// Undefined
//            'FEED_POS' => $pos,
            'FEED_NAME' => $name,
            'FEED_LINK' => $link,
            'FEED_ARTICLES' => $articles,
            'FEED_CACHE' => $cache,
            'FEED_IMG_STATUS0' => $status_img0,
            'FEED_IMG_STATUS1' => $status_img1,
            'FEED_STATUS0' => $status0,
            'FEED_STATUS1' => $status1
        ));

        // category
        $query = "SELECT id,
                           name
                      FROM ".DBPREFIX."module_feed_category
                  ORDER BY pos";
        $objResult = $objDatabase->Execute($query);

        while(!$objResult->EOF) {
            if ($subid == $objResult->fields['id']) {
                $selected = ' selected';
            } else {
                $selected = '';
            }

            $this->_objTpl->setVariable(array(
                'FEED_CATEGORY_ID' => $objResult->fields['id'],
                'FEED_CATEGORY_SELECTED' => $selected,
                'FEED_CATEGORY' => $objResult->fields['name']
            ));
            $this->_objTpl->parse('feed_table_option');
            $objResult->MoveNext();
        }

        //filename
        $allfiles = array();
        $dir = opendir ($this->feedpath);
        $file = readdir($dir);
        while($file !== false) {
            if ($file != '.' and $file != '..' and $file != 'index.html' and substr($file, 0, 5) != 'feed_') {
                  $allfiles[] = $file;
            }
            $file = readdir($dir);
        }
        closedir($dir);

        asort($allfiles);
        foreach($allfiles as $file)
        {
            $status = '';
            if ($filename == $file)
            {
                $status = ' selected';
            }

            $this->_objTpl->setVariable(array(
                'FEED_FILE' => $file,
                'FEED_FILE_SELECTED' => $status
            ));
            $this->_objTpl->parse('feed_table_option_name');
        }

        //parse $_ARRAYLANG
        $this->_objTpl->setVariable(array(
            'TXT_FEED_EDIT_NEWS_FEED' => $_ARRAYLANG['TXT_FEED_EDIT_NEWS_FEED'],
            'TXT_FEED_CATEGORY' => $_ARRAYLANG['TXT_FEED_CATEGORY'],
            'TXT_FEED_NAME' => $_ARRAYLANG['TXT_FEED_NAME'],
            'TXT_FEED_LINK' => $_ARRAYLANG['TXT_FEED_LINK'],
            'TXT_FEED_FILE_NAME' => $_ARRAYLANG['TXT_FEED_FILE_NAME'],
            'TXT_FEED_CHOOSE_FILE_NAME' => $_ARRAYLANG['TXT_FEED_CHOOSE_FILE_NAME'],
            'TXT_FEED_NUMBER_ARTICLES' => $_ARRAYLANG['TXT_FEED_NUMBER_ARTICLES'],
            'TXT_FEED_CACHE_TIME' => $_ARRAYLANG['TXT_FEED_CACHE_TIME'],
            'TXT_FEED_SHOW_LOGO' => $_ARRAYLANG['TXT_FEED_SHOW_LOGO'],
            'TXT_FEED_NO' => $_ARRAYLANG['TXT_FEED_NO'],
            'TXT_FEED_YES' => $_ARRAYLANG['TXT_FEED_YES'],
            'TXT_FEED_STATUS' => $_ARRAYLANG['TXT_FEED_STATUS'],
            'TXT_FEED_INACTIVE' => $_ARRAYLANG['TXT_FEED_INACTIVE'],
            'TXT_FEED_ACTIVE' => $_ARRAYLANG['TXT_FEED_ACTIVE'],
            'TXT_FEED_RESET' => $_ARRAYLANG['TXT_FEED_RESET'],
            'TXT_FEED_SAVE' => $_ARRAYLANG['TXT_FEED_SAVE'],
            'TXT_FEED_FORMCHECK_ERROR_INTERN' => $_ARRAYLANG['TXT_FEED_FORMCHECK_ERROR_INTERN'],
            'TXT_FEED_FORMCHECK_CATEGORY' => $_ARRAYLANG['TXT_FEED_FORMCHECK_CATEGORY'],
            'TXT_FEED_FORMCHECK_NAME' => $_ARRAYLANG['TXT_FEED_FORMCHECK_NAME'],
            'TXT_FEED_FORMCHECK_LINK_FILE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_LINK_FILE'],
            'TXT_FEED_FORMCHECK_ARTICLES' => $_ARRAYLANG['TXT_FEED_FORMCHECK_ARTICLES'],
            'TXT_FEED_FORMCHECK_CACHE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_CACHE'],
            'TXT_FEED_FORMCHECK_IMAGE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_IMAGE'],
            'TXT_FEED_FORMCHECK_STATUS' => $_ARRAYLANG['TXT_FEED_FORMCHECK_STATUS']
        ));
    }


    function showEditSetNew($id, $subid, $name, $link, $filename, $articles, $cache, $time, $image, $status)
    {
        global $objDatabase, $_ARRAYLANG;

        //delete old #01
        $query = "SELECT link,
                           filename
                      FROM ".DBPREFIX."module_feed_news
                     WHERE id = '".$id."'";
        $objResult = $objDatabase->Execute($query);

        $old_link     = $objResult->fields['link'];
        $old_filename = $objResult->fields['filename'];

        //new
        $query = "SELECT id
                      FROM ".DBPREFIX."module_feed_news
              WHERE BINARY name = '".$name."'
                       AND id <> '".$id."'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount() == 0) {
            if ($link != '') {
                $filename = "feed_".$time."_".basename($link);
                if (!copy($link, $this->feedpath.$filename)) {
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_NEWS_FEED'];
                    $this->goToReplace('&act=edit&id='.$id);
                    die;
                }

                //rss class
                $rss = new \XML_RSS($this->feedpath.$filename);
                $rss->parse();
                $content = '';

                foreach($rss->getStructure() as $array) {
                    $content .= $array;
                }
                if ($content == '') {
                    unlink($this->feedpath.$filename);
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_NEWS_FEED'];
                    $this->goToReplace('&act=edit&id='.$id);
                    die;
                }
            }

            $query = "UPDATE ".DBPREFIX."module_feed_news
                           SET subid = '".$subid."',
                               name = '".$name."',
                               link = '".$link."',
                               filename = '".$filename."',
                               articles = '".$articles."',
                               cache = '".$cache."',
                               time = '".$time."',
                               image = '".$image."',
                               status = '".$status."'
                         WHERE id = '".$id."'";
            $objResult = $objDatabase->Execute($query);
        } else {
            $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_EXISTING_NEWS'];
            $this->goToReplace('&act=edit&id='.$id);
            die;
        }

        //delete old #02
        if ($old_link != '') {
            if (!unlink($this->feedpath.$old_filename)) {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_DELETE'];
                $this->goToReplace('');
                die;
            }
        }
    }


    function showCategory()
    {
        global $objDatabase, $_ARRAYLANG, $_LANGID, $_CONFIG;

        unset($_SESSION['feedCategorySort']);

        //new
        if (isset($_GET['new']) and $_GET['new'] == 1) {
            if (isset($_POST['form_name']) and isset($_POST['form_lang']) and isset($_POST['form_status'])) {
                $name   = CONTREXX_ESCAPE_GPC ? strip_tags($_POST['form_name']) : addslashes(strip_tags($_POST['form_name']));
                $lang   = intval($_POST['form_lang']);
                $status = intval($_POST['form_status']);
                $time   = time();
                $this->showCategoryNew($name, $lang, $status, $time);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SUCCESSFULL_CAT'];
            } else {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_FILL_IN_ALL'];
            }

            $this->goToReplace('&act=category');
            die;
        }
        if (isset($_GET['chg']) and $_GET['chg'] == 1 and isset($_POST['form_selected']) and is_array($_POST['form_selected'])) {
            //discharge
            if ($_POST['form_discharge'] != '') {
                $ids = $_POST['form_selected'];
                $this->showCategoryDischarge($ids);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SUCCESSFUL_DISCHARGE'];
                $this->goToReplace('&act=category');
                die;
            }
            //delete
            if ($_POST['form_delete'] != '') {
                $ids = $_POST['form_selected'];
                $this->showCategoryDelete($ids);
                $this->goToReplace('&act=category');
                die;
            }
            //changestatus
            if ($_POST['form_activate'] != '' or $_POST['form_deactivate'] != '') {
                $ids = $_POST['form_selected'];
                if ($_POST['form_activate'] != '') {
                    $this->showCategoryChange($ids, 1);
                }
                if ($_POST['form_deactivate'] != '') {
                    $this->showCategoryChange($ids, 0);
                }
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_STATUS'];
                $this->goToReplace('&act=category');
                die;
            }
        }
        //sort
        if (isset($_GET['chg']) and $_GET['chg'] == 1 and $_POST['form_sort'] != '') {
            $ids = $_POST['form_id'];
            $pos = $_POST['form_pos'];
            $this->showCategoryChangePos($ids, $pos);
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SORT'];
            $this->goToReplace('&act=category');
            die;
        }

        //lang
        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $selected = '';
            if ($_LANGID == $frontendLanguage['id']) {
                $selected = ' selected';
            }

            $this->_objTpl->setVariable(array(
                'FEED_LANG_ID' => $frontendLanguage['id'],
                'FEED_LANG_SELECTED' => $selected,
                'FEED_LANG_NAME' => $frontendLanguage['name']
            ));
            $this->_objTpl->parse('feed_lang');
        }

        //table
        $query = "SELECT id,
                         name,
                         status,
                         FROM_UNIXTIME(time, '%H:%i - %d.%m.%Y') AS time,
                         lang,
                         pos
                    FROM ".DBPREFIX."module_feed_category
                ORDER BY pos";

        //paging
        $objResult = $objDatabase->Execute($query);
        $count = $objResult->RecordCount();

        if (isset($_GET['pos'])) {
            $pos = intval($_GET['pos']);
        } else {
            $pos = 0;
        }

        if (!is_numeric($pos)) {
            $pos = 0;
        }
        if ($count > intval($_CONFIG['corePagingLimit'])) {
            $paging = getPaging($count, $pos, "&cmd=Feed&act=category", "<b>".$_ARRAYLANG['TXT_FEED_ENTRIES']."</b>", true);
        } else {
            $paging = '';
        }

        $pagingLimit = intval($_CONFIG['corePagingLimit']);

        $objResult = $objDatabase->SelectLimit($query, $pagingLimit, $pos);

        //

        $total_records = $objResult->RecordCount();
        $this->_objTpl->setVariable(array(
            'TOTAL_RECORDS' => $total_records
        ));

        $i = 0;
        while(!$objResult->EOF) {
            ($i % 2)                ? $class  = 'row1'  : $class  = 'row2';
            ($objResult->fields['status'] == 1) ? $status = 'green' : $status = 'red';
            //records
            $query = "
                SELECT COUNT(*) AS `numof_records`
                  FROM ".DBPREFIX."module_feed_news
                 WHERE subid = '".$objResult->fields['id']."'";
            $objResult2 = $objDatabase->Execute($query);
            $records = $objResult2->fields['numof_records'];

            //parser
            $this->_objTpl->setVariable(array(
                'FEED_CLASS' => $class,
                'FEED_POS' => $objResult->fields['pos'],
                'FEED_STATUS' => $status,
                'FEED_ID' => $objResult->fields['id'],
                'FEED_NAME' => $objResult->fields['name'],
                'FEED_LANG' => \FWLanguage::getLanguageParameter($objResult->fields['lang'], 'name'),
                'FEED_TIME' => $objResult->fields['time'],
                'FEED_RECORDS' => $records,
                'TXT_FEED_EDIT' => $_ARRAYLANG['TXT_FEED_EDIT']
            ));
            $this->_objTpl->parse('feed_table_row');
            $objResult->MoveNext();
            $i++;
        }

        //make visible
        if ($i > 0) {
            $this->_objTpl->setVariable(array(
                'FEED_RECORDS_HIDDEN' => '&nbsp;',
                'TXT_FEED_MARK_ALL' => $_ARRAYLANG['TXT_FEED_MARK_ALL'],
                'TXT_FEED_REMOVE_CHOICE' => $_ARRAYLANG['TXT_FEED_REMOVE_CHOICE'],
                'TXT_FEED_SELECT_OPERATION' => $_ARRAYLANG['TXT_FEED_SELECT_OPERATION'],
                'TXT_FEED_SAVE_SORTING' => $_ARRAYLANG['TXT_FEED_SAVE_SORTING'],
                'TXT_FEED_ACTIVATE_CAT' => $_ARRAYLANG['TXT_FEED_ACTIVATE_CAT'],
                'TXT_FEED_DEACTIVATE_CAT' => $_ARRAYLANG['TXT_FEED_DEACTIVATE_CAT'],
                'TXT_FEED_DELETE_RECORDS' => $_ARRAYLANG['TXT_FEED_DELETE_RECORDS'],
                'TXT_FEED_DELETE_CAT' => $_ARRAYLANG['TXT_FEED_DELETE_CAT']
            ));
            $this->_objTpl->parse('feed_table_hidden');
        }

        //

        $this->_objTpl->setVariable(array(
            'FEED_CATEGORY_PAGING' => $paging
        ));

        //parse $_ARRAYLANG
        $this->_objTpl->setVariable(array(
            'TXT_FEED_INSERT_CATEGORY' => $_ARRAYLANG['TXT_FEED_INSERT_CATEGORY'],
            'TXT_FEED_NAME' => $_ARRAYLANG['TXT_FEED_NAME'],
            'TXT_FEED_LANGUAGE' => $_ARRAYLANG['TXT_FEED_LANGUAGE'],
            'TXT_FEED_STATUS' => $_ARRAYLANG['TXT_FEED_STATUS'],
            'TXT_FEED_INACTIVE' => $_ARRAYLANG['TXT_FEED_INACTIVE'],
            'TXT_FEED_ACTIVE' => $_ARRAYLANG['TXT_FEED_ACTIVE'],
            'TXT_FEED_SAVE' => $_ARRAYLANG['TXT_FEED_SAVE'],
            'TXT_FEED_SORTING' => $_ARRAYLANG['TXT_FEED_SORTING'],
            'TXT_FEED_STATUS' => $_ARRAYLANG['TXT_FEED_STATUS'],
            'TXT_FEED_ID' => $_ARRAYLANG['TXT_FEED_ID'],
            'TXT_FEED_CAT_NAME' => $_ARRAYLANG['TXT_FEED_CAT_NAME'],
            'TXT_FEED_LANGUAGE' => $_ARRAYLANG['TXT_FEED_LANGUAGE'],
            'TXT_FEED_RECORDS' => $_ARRAYLANG['TXT_FEED_RECORDS'],
            'TXT_FEED_BUILT_EDITED' => $_ARRAYLANG['TXT_FEED_BUILT_EDITED'],
            'TXT_FEED_FORMCHECK_NAME' => $_ARRAYLANG['TXT_FEED_FORMCHECK_NAME'],
            'TXT_FEED_FORMCHECK_LANGUAGE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_LANGUAGE'],
            'TXT_FEED_FORMCHECK_STATUS' => $_ARRAYLANG['TXT_FEED_FORMCHECK_STATUS'],
            'TXT_FEED_DELETE_RECORDS_CONFIRM' => $_ARRAYLANG['TXT_FEED_DELETE_RECORDS_CONFIRM'],
            'TXT_FEED_DELETE_CONFIRM' => $_ARRAYLANG['TXT_FEED_DELETE_CONFIRM'],
            'TXT_FEED_NO_SELECT_OPERATION' => $_ARRAYLANG['TXT_FEED_NO_SELECT_OPERATION']
        ));
    }


    function showCategoryNew($name, $lang, $status, $time)
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "SELECT id
                      FROM ".DBPREFIX."module_feed_category
              WHERE BINARY name = '".$name."'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount() == 0) {

            $query = "INSERT INTO ".DBPREFIX."module_feed_category
                                SET name = '".$name."',
                                    lang = '".$lang."',
                                    status = '".$status."',
                                    time = '".$time."'";
            $objResult = $objDatabase->Execute($query);
        } else {
            $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_EXISTING_CAT'];
            $this->goToReplace('&act=category');
            die;
        }
    }


    function showCategoryDischarge($ids)
    {
        global $objDatabase, $_ARRAYLANG;

        foreach($ids as $id) {
            $query = "SELECT id,
                               link,
                               filename
                          FROM ".DBPREFIX."module_feed_news
                         WHERE subid = '".intval($id)."'";
            $objResult = $objDatabase->Execute($query);

            while(!$objResult->EOF) {
                $link     = $objResult->fields['link'];
                $filename = $objResult->fields['filename'];

                if ($link != '') {
                    if (!unlink($this->feedpath.$filename)) {
                        $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_DELETE'];
                        $this->goToReplace('&act=category');
                        die;
                    }
                }
                $objResult->MoveNext();
            }
            $query = "DELETE FROM ".DBPREFIX."module_feed_news
                              WHERE subid = '".intval($id)."'";
            $objDatabase->Execute($query);
        }

        $query = "SELECT id
                      FROM ".DBPREFIX."module_feed_news";
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount() == 0) {
            $query = "DELETE FROM ".DBPREFIX."module_feed_news";
        }
    }


    function showCategoryDelete($ids)
    {
        global $objDatabase, $_ARRAYLANG;
        $y = 0;

        foreach($ids as $id) {
            $query = "SELECT subid
                      FROM ".DBPREFIX."module_feed_news
                     WHERE subid = '".intval($id)."'";
            $objResult = $objDatabase->Execute($query);

            if ($objResult->RecordCount() > 0) {
                $y++;
            } else {
                $query = "DELETE FROM ".DBPREFIX."module_feed_category
                          WHERE id = '".intval($id)."'";
                $objDatabase->Execute($query);
            }
        }

        $query = "SELECT id
                      FROM ".DBPREFIX."module_feed_category";
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount() == 0) {
            $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_feed_category");
        }

        if ($y == 0) {
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SUCCESSFUL_DELETE'];
        } else {
            $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_UNSUCCESSFUL_DELETE'];
        }
    }


    function showCategoryChange($ids, $to)
    {
        global $objDatabase;

        foreach($ids as $id) {
            $query = "UPDATE ".DBPREFIX."module_feed_category
                           SET status = '".intval($to)."'
                         WHERE id = '".intval($id)."'";
            $objDatabase->Execute($query);
        }
    }


    function showCategoryChangePos($ids, $pos)
    {
        global $objDatabase;

        for($x = 0; $x < count($ids); $x++) {
            $query = "UPDATE ".DBPREFIX."module_feed_category
                           SET pos = '".intval($pos[$x])."'
                         WHERE id = '".intval($ids[$x])."'";
            $objDatabase->Execute($query);
        }
    }


    function showCatEdit()
    {
        global $objDatabase, $_ARRAYLANG;

        // check
        if (!isset($_GET['set'])) {
            if (!isset($_GET['id']) or $_GET['id'] == '') {
                $this->goToReplace('&act=category');
                die;
            }
        }

        //set
        if (isset($_GET['set']) and $_GET['set'] == 1) {
            if ($_POST['form_id'] != '' and $_POST['form_name'] != '' and $_POST['form_status'] != '' and $_POST['form_lang'] != '') {
                $id       = intval($_POST['form_id']);
                $name     = CONTREXX_ESCAPE_GPC ? strip_tags($_POST['form_name']) : addslashes(strip_tags($_POST['form_name']));
                $status   = intval($_POST['form_status']);
                $time     = time();
                $lang     = intval($_POST['form_lang']);

                $this->showCatEditSet($id, $name, $status, $time, $lang);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_SUCCESSFUL_EDIT_CAT'];
                $this->goToReplace('&act=category');
                die;
            } else {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_FILL_IN_ALL'];
                $this->goToReplace('&act=catedit&id='.$_POST['form_id']);
                die;
            }
        }

        $query = "SELECT id,
                           name,
                           status,
                           lang
                      FROM ".DBPREFIX."module_feed_category
                     WHERE id = '".intval($_GET['id'])."'";
        $objResult = $objDatabase->Execute($query);

        $id        = $objResult->fields['id'];
        $name      = $objResult->fields['name'];
        $status    = $objResult->fields['status'];
        $lang      = $objResult->fields['lang'];

        if ($status == 0) {
            $status0 = ' selected';
            $status1 = '';
        } else {
            $status0 = '';
            $status1 = ' selected';
        }

        $this->_objTpl->setVariable(array(
            'FEED_ID' => $id,
            'FEED_NAME' => $name,
// Undefined
//            'FEED_LINK' => $link,
            'FEED_STATUS0' => $status0,
            'FEED_STATUS1' => $status1
        ));

        //lang
        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $selected = '';
            if ($lang == $frontendLanguage['id']) {
                $selected = ' selected';
            }

            $this->_objTpl->setVariable(array(
                'FEED_LANG_ID' => $frontendLanguage['id'],
                'FEED_LANG_SELECTED' => $selected,
                'FEED_LANG_NAME' => $frontendLanguage['name']
            ));
            $this->_objTpl->parse('feed_lang');
            $objResult->MoveNext();
        }

        //parse $_ARRAYLANG
        $this->_objTpl->setVariable(array(
            'TXT_FEED_EDIT_CAT' => $_ARRAYLANG['TXT_FEED_EDIT_CAT'],
            'TXT_FEED_NAME' => $_ARRAYLANG['TXT_FEED_NAME'],
            'TXT_FEED_LANGUAGE' => $_ARRAYLANG['TXT_FEED_LANGUAGE'],
            'TXT_FEED_STATUS' => $_ARRAYLANG['TXT_FEED_STATUS'],
            'TXT_FEED_INACTIVE' => $_ARRAYLANG['TXT_FEED_INACTIVE'],
            'TXT_FEED_ACTIVE' => $_ARRAYLANG['TXT_FEED_ACTIVE'],
            'TXT_FEED_RESET' => $_ARRAYLANG['TXT_FEED_RESET'],
            'TXT_FEED_SAVE' => $_ARRAYLANG['TXT_FEED_SAVE'],
            'TXT_FEED_FORMCHECK_NAME' => $_ARRAYLANG['TXT_FEED_FORMCHECK_NAME'],
            'TXT_FEED_FORMCHECK_LANGUAGE' => $_ARRAYLANG['TXT_FEED_FORMCHECK_LANGUAGE'],
            'TXT_FEED_FORMCHECK_STATUS' => $_ARRAYLANG['TXT_FEED_FORMCHECK_STATUS']
        ));
    }


    function showCatEditSet($id, $name, $status, $time, $lang)
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "SELECT id
                      FROM ".DBPREFIX."module_feed_category
              WHERE BINARY name = '".$name."'
                       AND id <> '".$id."'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult->RecordCount() == 0) {
            $query = "UPDATE ".DBPREFIX."module_feed_category
                           SET name = '".$name."',
                               status ='".$status."',
                               time = '".$time."',
                               lang = '".$lang."'
                         WHERE id = '".$id."'";
            $objResult = $objDatabase->Execute($query);
        } else {
            $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_FEED_MESSAGE_ERROR_EXISTING_CAT'];
            $this->goToReplace('&act=catedit&id='.$id);
            die;
        }
    }


    function goToReplace($add)
    {
        \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?cmd=Feed".$add);
    }

}

?>
