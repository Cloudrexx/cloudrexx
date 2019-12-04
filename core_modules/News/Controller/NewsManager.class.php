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
 * News manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 * @todo        Edit PHP DocBlocks!
 *              The news entry management is bulky! It should be rewritten in OOP
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * News manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class NewsManager extends \Cx\Core_Modules\News\Controller\NewsLibrary {
    var $_objTpl;
    var $pageTitle;
    var $pageContent;
    var $strOkMessage;
    var $strErrMessage;
    var $_selectedLang;
    var $langId;
    var $arrSettings = array();
    var $_defaultTickerFileName = 'newsticker.txt';
    var $_arrCharsets = array(
        // European languages
        /*'ASCII',*/
        'ISO-8859-1',
        /*'ISO-8859-2',
        'ISO-8859-3',
        'ISO-8859-4',
        'ISO-8859-5',
        'ISO-8859-7',
        'ISO-8859-9',
        'ISO-8859-10',
        'ISO-8859-13',
        'ISO-8859-14',
        'ISO-8859-15',
        'ISO-8859-16',
        'KOI8-R',
        'KOI8-U',
        'KOI8-RU',
        'CP1250',
        'CP1251',
        'CP1252',
        'CP1253',
        'CP1254',
        'CP1257',
        'CP850',
        'CP866',
        'MacRoman',
        'MacCentralEurope',
        'MacIceland',
        'MacCroatian',
        'MacRomania',
        'MacCyrillic',
        'MacUkraine',
        'MacGreek',
        'MacTurkish',
        'Macintosh',
        // Semitic languages
        'ISO-8859-6',
        'ISO-8859-8',
        'CP1255',
        'CP1256',
        'CP862',
        'MacHebrew',
        'MacArabic',
        // Japanese
        'EUC-JP',
        'SHIFT_JIS',
        'CP932',
        'ISO-2022-JP',
        'ISO-2022-JP-2',
        'ISO-2022-JP-1',
        // Chinese
        'EUC-CN',
        'HZ',
        'GBK',
        'GB18030',
        'EUC-TW',
        'BIG5',
        'CP950',
        'BIG5-HKSCS',
        'ISO-2022-CN',
        'ISO-2022-CN-EXT',
        // Korean
        'EUC-KR',
        'CP949',
        'ISO-2022-KR',
        'JOHAB',
        // Armenian
        'ARMSCII-8',
        // Georgian
        'Georgian-Academy',
        'Georgian-PS',
        // Tajik
        'KOI8-T',
        // Thai
        'TIS-620',
        'CP874',
        'MacThai',
        // Laotian
        'MuleLao-1',
        'CP1133',
        // Vietnamese
        'VISCII',
        'TCVN',
        'CP1258',
        // Platform specifics
        'HP-ROMAN8',
        'NEXTSTEP',
        // Full Unicode*/
        'UTF-8',
        /*'UCS-2',
        'UCS-2BE',
        'UCS-2LE',
        'UCS-4',
        'UCS-4BE',
        'UCS-4LE',
        'UTF-16',
        'UTF-16BE',
        'UTF-16LE',
        'UTF-32',
        'UTF-32BE',
        'UTF-32LE',
        'UTF-7',
        'C99',
        'JAVA',*/
    );

    /**
    * Teaser object
    *
    * @access private
    * @var object
    */
    var $_objTeaser;

    private $act = '';

    /**
    * PHP5 Constructor
    *
    * @access public
    */
    function __construct()
    {
        global  $_ARRAYLANG, $objInit, $objTemplate, $_CONFIG;

        parent::__construct();

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/News/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->_saveSettings();
        $this->langId = $objInit->userFrontendLangId;

        $this->pageTitle = $_ARRAYLANG['TXT_NEWS_MANAGER'];
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG,$_CONFIG;

        if ($this->act == 'manager') {
            $this->act = '';
        }

        $objTemplate->setVariable("CONTENT_NAVIGATION","
            <a href='index.php?cmd=News&amp;act=manager' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEWS_MANAGER']."</a>
            <a href='index.php?cmd=News&amp;act=add' class='".($this->act == 'add' ? 'active' : '')."'>".$_ARRAYLANG['TXT_CREATE_NEWS']."</a>
            <a href='index.php?cmd=News&amp;act=newscat' class='".($this->act == 'newscat' ? 'active' : '')."'>".$_ARRAYLANG['TXT_CATEGORY_MANAGER']."</a>
            ".($this->arrSettings['news_use_types'] == '1' ? "<a href='index.php?cmd=News&amp;act=newstype' class='".($this->act == 'newstype' ? 'active' : '')."'>".$_ARRAYLANG['TXT_TYPES_MANAGER']."</a>" : "")."
            <a href='index.php?cmd=News&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEWS_SETTINGS']."</a>");
            //<a href='index.php?cmd=News&amp;act=ticker' class='".($this->act == 'ticker' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEWS_NEWSTICKER']."</a>
            //".($_CONFIG['newsTeasersStatus'] == '1' ? "<a href='index.php?cmd=News&amp;act=teasers' class='".($this->act == 'teasers' ? 'active' : '')."'>".$_ARRAYLANG['TXT_TEASERS']."</a>" : "")."
    }

    /**
    * Do the requested newsaction
    *
    * @global    \Cx\Core\Html\Sigma
    * @return    string    parsed content
    */
    function getPage()
    {
        global $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act'] = '';
        }

        \JS::activate('jquery');

        switch ($_GET['act']) {
            case 'add':
                $this->add();
                break;

            case 'edit':
                $this->edit();
                break;

            case 'comments':
                $this->comments_list();
                break;

            case 'comments_delete':
                $this->comments_delete();
                $this->comments_list();
                break;

            case 'comment_edit':
                if (isset($_POST['saveComment'])) {
                    if ($this->_comment_validate() && $this->comment_save()) {
                        $this->comments_list();
                    } else {
                        $this->comment_edit();
                    }
                } else {
                    $this->comment_edit();
                }
                break;

            case 'comment_status':
                $this->invertCommentStatus($_GET['commentsId']);
                $this->comments_list();
                break;

            case 'change_comment_status':
                $this->changeCommentStatus();
                $this->comments_list();
                break;

            case 'copy':
                $this->edit(true);
                break;

            case 'delete':
                $this->delete();
                $this->overview();
                break;

            case 'update':
                $this->update();
                break;

            case 'newscat':
                $this->manageCategories();
                break;

            case 'modifycat':
                $this->modifyCategory($_GET['id']);
                break;

            case 'delcat':
                $this->deleteCat();
                $this->manageCategories();
                break;

            case 'newstype':
                $this->manageTypes();
                break;

            case 'deltype':
                $this->deleteType();
                $this->manageTypes();
                break;

            case 'changeStatus':
                $this->changeStatus();
                $this->overview();
                break;

            case 'invertStatus':
                $this->invertStatus($_GET['newsId']);
                $this->overview();
                break;

            case 'settings':
                $this->settings();
                break;

            case 'rss':
                $this->rss();
                break;

            default:
                (intval($this->arrSettings['news_settings_activated'])==0) ? $this->settings() : $this->overview();
                break;
        }

        // drop cache after any modification
        if (
            (
                in_array(
                    $_GET['act'],
                    array(
                        'add',
                        'edit',
                        'comments_delete',
                        'comment_edit',
                        'comment_status',
                        'change_comment_status',
                        'copy',
                        'delete',
                        'update',
                        'modifycat',
                        'delcat',
                        'deltype',
                        'changeStatus',
                        'invertStatus',
                    )
                )
            ) ||
            (
                $_GET['act'] == 'newstype' &&
                (
                    (
                        isset($_POST['addType']) &&
                        $_POST['addType']
                    ) ||
                    (
                        isset($_POST['modType']) &&
                        $_POST['modType']
                    )
                )
            ) ||
            (
                $_GET['act'] == 'settings' &&
                isset($_POST['store'])
            )
        ) {
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('News');
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->pageTitle,
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));

        $this->act = $_GET['act'];
        $this->setNavigation();
    }



    /**
    * List up the news for edit or delete
    *
    * @global    ADONewConnection
    * @global    array
    * @global    array
    * @param     integer   $newsid
    * @param     string    $what
    * @access  private
    * @todo     use SQL_CALC_FOUND_ROWS and drop 'n.validated' in where clause instead of calling same query four times
    */
    function overview()
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        if (!$this->hasCategories()) {
            return $this->manageCategories();
        }

        $query = 'SELECT 1 FROM `'.DBPREFIX.'module_news_locale` WHERE `is_active` = "1"';
        //$query = 'SELECT 1 FROM `'.DBPREFIX.'module_news`';
        $objNewsCount = $objDatabase->SelectLimit($query, 1);
        if ($objNewsCount === false || $objNewsCount->RecordCount() == 0) {
            return $this->add();
        }

        $objFWUser = \FWUser::getFWUserObject();

        // initialize variables
        $paging = "";

        $this->_objTpl->loadTemplateFile('module_news_overview.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_NEWS_MANAGER'];

        $messageNr = 0;
        $validatorNr = 0;
        $monthlyStats = array();
        $dateFilterName = 'date';
        $colspanArchive = 10;
        $colspanInvalidated = 9;

        if ($this->arrSettings['news_use_types'] == 1) {
            $colspanArchive++;
            $colspanInvalidated++;
            $this->_objTpl->setVariable('TXT_NEWS_TYPE', $_ARRAYLANG['TXT_NEWS_TYPE']);
            $this->_objTpl->parse('news_type_label');
        } else {
            $this->_objTpl->hideBlock('news_type_label');
        }

        $this->_objTpl->setVariable(array(
            'TXT_EDIT_NEWS_MESSAGE'      => $_ARRAYLANG['TXT_EDIT_NEWS_MESSAGE'],
            'TXT_EDIT_NEWS_ID'           => $_ARRAYLANG['TXT_EDIT_NEWS_ID'],
            'TXT_ID'                     => $_ARRAYLANG['TXT_ID'],
            'TXT_DATE'                   => $_ARRAYLANG['TXT_DATE'],
            'TXT_TITLE'                  => $_ARRAYLANG['TXT_TITLE'],
            'TXT_VIEW'                   => $_ARRAYLANG['TXT_VIEW'],
            'TXT_USER'                   => $_ARRAYLANG['TXT_USER'],
            'TXT_ACTION'                 => $_ARRAYLANG['TXT_ACTION'],
// TODO: Not in use yet. From r8465@branches/contrexx_2_1
//            'TXT_REPUBLISHING'         => $_ARRAYLANG['TXT_REPUBLISHING'],
            'TXT_CATEGORY'               => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_LANGUAGE'               => $_ARRAYLANG['TXT_LANGUAGE'],
            'COLSPAN_ARCHIVE'            => $colspanArchive,
            'COLSPAN_INVALIDATED'        => $colspanInvalidated,
            'TXT_CONFIRM_DELETE_DATA'    => $_ARRAYLANG['TXT_NEWS_DELETE_CONFIRM'],
            'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_SELECT_ALL'             => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_REMOVE_SELECTION'       => $_ARRAYLANG['TXT_REMOVE_SELECTION'],
            'TXT_DELETE_MARKED'          => $_ARRAYLANG['TXT_DELETE_MARKED'],
            'TXT_MARKED'                 => $_ARRAYLANG['TXT_MARKED'],
            'TXT_ACTIVATE'               => $_ARRAYLANG['TXT_ACTIVATE'],
            'TXT_DEACTIVATE'             => $_ARRAYLANG['TXT_DEACTIVATE'],
            'TXT_STATUS'                 => $_ARRAYLANG['TXT_STATUS'],
            'TXT_CONFIRM_AND_ACTIVATE'   => $_ARRAYLANG['TXT_CONFIRM_AND_ACTIVATE'],
            'TXT_INVALIDATED_ENTRIES'    => $_ARRAYLANG['TXT_INVALIDATED_ENTRIES'],
            'TXT_NEWS_PREVIEW'           => $_ARRAYLANG['TXT_NEWS_PREVIEW'],
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_ARCHIVE'                   => $_ARRAYLANG['TXT_ARCHIVE'],
            'TXT_EDIT'                      => $_ARRAYLANG['TXT_EDIT'],
            'TXT_COPY'                      => $_ARRAYLANG['TXT_COPY'],
            'TXT_DELETE'                    => $_ARRAYLANG['TXT_DELETE'],
            'TXT_LAST_EDIT'                 => $_ARRAYLANG['TXT_LAST_EDIT'],
            'TXT_NEWS_COMMENTS'             => $_ARRAYLANG['TXT_NEWS_COMMENTS'],
            'TXT_NEWS_MESSAGE_PROTECTED'    => $_ARRAYLANG['TXT_NEWS_MESSAGE_PROTECTED'],
            'TXT_NEWS_READ_ALL_ACCESS_DESC' => $_ARRAYLANG['TXT_NEWS_READ_ALL_ACCESS_DESC'],
            'TXT_NEWS_NUMBER_OF_COMMENTS'   => $_ARRAYLANG['TXT_NEWS_NUMBER_OF_COMMENTS'],
        ));

        $selectedCategory = !empty($_GET['categoryFilter']) ? intval($_GET['categoryFilter']) : 0;
        $selectedCategoryNewsIds = array();
        if ($selectedCategory !== 0) {
            $selectedCategoryNewsIds = $this->getCategoryRelNews($selectedCategory);
        }
        $whereCategory = (!empty($selectedCategoryNewsIds))
            ? ' AND `id` IN ('
                . implode(',' , $selectedCategoryNewsIds) . ')'
            : '';

        // month filter
        // archive list
        $monthCountQuery = '
            SELECT `n`.`id`, `n`.`date`, `n`.`changelog`
              FROM `' . DBPREFIX . 'module_news` `n`
             WHERE `validated` = "1"
               ' . $whereCategory . '
               ' . ($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess() ? ' AND (`backend_access_id` IN (' . implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())) . ') OR `userid` = ' . $objFWUser->objUser->getId() . ') ' : '') . '
          ORDER BY `date` DESC
        ';
        $objResult = $objDatabase->Execute($monthCountQuery);
        if ($objResult !== false) {
            $arrMonthTxts = explode(',', $_CORELANG['TXT_MONTH_ARRAY']);

            while (!$objResult->EOF) {
                $filterDate = $objResult->fields[$dateFilterName];
                $newsYear = date('Y', $filterDate);
                $newsMonth = date('m', $filterDate);

                if (!isset($monthlyStats[$newsYear])) {
                    $monthlyStats[$newsYear] = array();
                    $monthlyStats[$newsYear]['name'] = $newsYear;
                }

                if (!isset($monthlyStats[$newsYear . '_' . $newsMonth])) {
                    $monthlyStats[$newsYear . '_' . $newsMonth] = array();
                    $monthlyStats[$newsYear . '_' . $newsMonth]['name'] = $arrMonthTxts[date('n', $filterDate) - 1];
                    $monthlyStats[$newsYear . '_' . $newsMonth]['archive'] = 0;
                }
                $monthlyStats[$newsYear . '_' . $newsMonth]['archive']++;
                $objResult->MoveNext();
            }
        }
        $monthLimitQuery = '';
        $isFilteredByMonth = false;
        if (isset($_GET['monthFilter'])) {
            if (array_key_exists($_GET['monthFilter'], $monthlyStats)) {
                $isFilteredByMonth = true;
                $monthInfo = explode('_', $_GET['monthFilter']);
                $monthLimitQuery = ' AND `' . $dateFilterName . '`';
                if (count($monthInfo) == 1) { // month filter
                    $monthLimitQuery .= ' BETWEEN ' . mktime(0, 0, 0, 1, 1, $monthInfo[0]);
                    $monthLimitQuery .= ' AND ' . mktime(23, 59, 59, 12, 31, $monthInfo[0]);
                } else {
                    $monthLimitQuery .= ' BETWEEN ' . mktime(0, 0, 0, $monthInfo[1], 1, $monthInfo[0]);
                    $monthLimitQuery .= ' AND ' . mktime(23, 59, 59, $monthInfo[1], date('t', mktime(0, 0, 0, $monthInfo[1], 1, $monthInfo[0])), $monthInfo[0]);
                }
            }
        }

        $activeFrontendLangIds = array_keys(\FWLanguage::getActiveFrontendLanguages());

        // set archive list
        $query = '
            SELECT `id`, `date`, `changelog`, `status`, `validated`, `typeid`, `frontend_access_id`, `userid`
              FROM `' . DBPREFIX . 'module_news`
             WHERE `validated` = "1"
               ' . $whereCategory . '
               ' . $monthLimitQuery . '
               ' . ($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess() ? ' AND (`backend_access_id` IN (' . implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())) . ') OR `userid` = ' . $objFWUser->objUser->getId() . ') ' : '') . '
          ORDER BY `date` DESC
        ';
        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            $count = $objResult->RecordCount();

            if (isset($_GET['pos'])) {
                $pos = intval($_GET['pos']);
            } else {
                $pos = 0;
            }

            if ($count>intval($_CONFIG['corePagingLimit'])) {
                $baseUrl = '&cmd=News&show=archive&monthFilter=' . contrexx_input2xhtml($_GET['monthFilter']);
                if (!empty($_GET['categoryFilter'])) {
                    $baseUrl .= '&categoryFilter=' . contrexx_input2xhtml($_GET['categoryFilter']);
                }
                $paging = getPaging($count, $pos, $baseUrl, $_ARRAYLANG['TXT_NEWS_MESSAGES'],true);
            }
            $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);

            $arrNews = array();
            $localeCategories = $this->getCategoryLocale();

            while (!$objResult->EOF) {
                $objLangResult = $objDatabase->Execute('SELECT nl.title as title,
                         nl.lang_id as langid,
                         ntl.name AS typename
                         FROM '.DBPREFIX.'module_news_locale AS nl
                         LEFT JOIN '.DBPREFIX.'module_news_types_locale AS ntl ON ntl.type_id='.$objResult->fields['typeid'].'
                         WHERE nl.news_id='.$objResult->fields['id'].' AND nl.is_active=1 AND nl.lang_id IN (\'' . implode('\',\'', $activeFrontendLangIds) . '\') ORDER BY nl.lang_id ASC');

                if ($objLangResult->RecordCount() > 0) {
                    $newsCategoryIds = $this->getNewsRelCategories($objResult->fields['id']);
                    $newsCategoryIdsFlipped = array_flip($newsCategoryIds);
                    $arrNews[$objResult->fields['id']] = array(
                      'date'               => $objResult->fields['date'],
                      'changelog'          => $objResult->fields['changelog'],
                      'status'             => $objResult->fields['status'],
                      'validated'          => $objResult->fields['validated'],
                      'frontend_access_id' => $objResult->fields['frontend_access_id'],
                      'userid'             => $objResult->fields['userid'],
                      'catIds'             => $newsCategoryIds
                    );
                    while (!$objLangResult->EOF) {
                        $combinedNewsCategories = array_intersect_key(
                            $localeCategories[$objLangResult->fields['langid']],
                            $newsCategoryIdsFlipped
                        );
                        $arrNews[$objResult->fields['id']]['lang'][$objLangResult->fields['langid']] = array(
                            'title' => $objLangResult->fields['title'],
                            'catname' => implode(', ', contrexx_raw2xhtml($combinedNewsCategories)),
                            'typename' => $objLangResult->fields['typename']
                        );
                        $objLangResult->MoveNext();
                    }
                }

                $objResult->MoveNext();
            }
        }

        $count = count($arrNews);
        if ($count<1) {
            $this->_objTpl->hideBlock('newstable');
        } else {
            foreach ($arrNews as $newsId => $news) {

                if (isset($news['lang'][FRONTEND_LANG_ID])) {
                    $selectedInterfaceLanguage = FRONTEND_LANG_ID;
                } elseif (isset($news['lang'][\FWLanguage::getDefaultLangId()])) {
                    $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
                } else {
                    $selectedInterfaceLanguage = key($news['lang']);
                }

                $statusPicture = 'status_red.gif';
                if ($news['status']==1) {
                    $statusPicture = 'status_green.gif';
                }

                ($messageNr % 2) ? $class = 'row2' : $class = 'row1';
                $messageNr++;

                if ($news['userid'] && ($objUser = $objFWUser->objUser->getUser($news['userid']))) {
                    $author = contrexx_raw2xhtml($objUser->getUsername());
                } else {
                    $author = $_ARRAYLANG['TXT_ANONYMOUS'];
                }

// TODO: Not in use yet. From r8465@branches/contrexx_2_1
/*                    require_once('../lib/SocialNetworks.class.php');
                $socialNetworkTemplater = new \SocialNetworks();
                $socialNetworkTemplater->setUrl($_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/index.php?section=News&cmd=details&newsid='.$objResult->fields['id']);*/

                // get comments count
                if ($this->arrSettings['news_comments_activated'] == 1) {
                    $ccResult = $objDatabase->Execute('
                        SELECT COUNT(1) AS `com_num`
                          FROM `'.DBPREFIX.'module_news_comments`
                         WHERE `newsid` = ' . $newsId . '
                    ');

                    if ($ccResult !== false && !empty($ccResult->fields['com_num'])) {
                        $this->_objTpl->setVariable('NEWS_COMMENTS_COUNT', $ccResult->fields['com_num']);
                        $this->_objTpl->parse('news_comments_data');
                    } else {
                        $this->_objTpl->hideBlock('news_comments_data');
                    }
                } else {
                    $this->_objTpl->hideBlock('news_comments_data');
                }

                if ($this->arrSettings['news_use_types'] == 1) {
                    $this->_objTpl->setVariable('NEWS_TYPE', contrexx_raw2xhtml($news['lang'][$selectedInterfaceLanguage]['typename']));
                    $this->_objTpl->parse('news_type_data');
                } else {
                    $this->_objTpl->hideBlock('news_type_data');
                }

                $langString = '';
                if (count(\FWLanguage::getActiveFrontendLanguages()) > 1) {
                    $langState = array();
                    foreach ($news['lang'] as $langId => $langValues) {
                        $langState[$langId] = 'active';
                    }
                    $langString  = \Html::getLanguageIcons($langState, 'index.php?cmd=News&amp;act=edit&amp;newsId=' . $newsId . '&amp;langId=%1$d');
                    $this->_objTpl->touchBlock('txt_languages_block');
                } else {
                    $this->_objTpl->hideBlock('txt_languages_block');
                }

                $previewLink = \Cx\Core\Routing\Url::fromModuleAndCmd('News', $this->findCmdById('details', $news['catIds']), '', array('newsid' => $newsId));
                $previewLink .= '&newsPreview=1';

                $this->_objTpl->setVariable(array(
                    'NEWS_ID'                => $newsId,
                    'NEWS_DATE'              => date(ASCMS_DATE_FORMAT, $news['date']),
                    'NEWS_TITLE'             => contrexx_raw2xhtml($news['lang'][$selectedInterfaceLanguage]['title']),
                    'NEWS_USER'              => $author,
                    'NEWS_CHANGELOG'         => date(ASCMS_DATE_FORMAT, $news['changelog']),
                    'NEWS_LIST_PARSING'      => $paging,
                    'NEWS_CLASS'             => $class,
                    'NEWS_CATEGORY'          => $news['lang'][$selectedInterfaceLanguage]['catname'],
                    'NEWS_STATUS'            => $news['status'],
                    'NEWS_STATUS_PICTURE'    => $statusPicture,
                    'NEWS_LANGUAGES'         => $langString,
// TODO: Not in use yet. From r8465@branches/contrexx_2_1
//                        'NEWS_FACEBOOK_SHARE_BUTTON'  => $socialNetworkTemplater->getFacebookShareButton()
                    'NEWS_PREVIEW_LINK_HREF' => $previewLink,
                ));

                $this->_objTpl->setVariable(array(
                    'NEWS_ACTIVATE'         =>  $_ARRAYLANG['TXT_ACTIVATE'],
                    'NEWS_DEACTIVATE'       =>  $_ARRAYLANG['TXT_DEACTIVATE']
                ));

                if ($this->arrSettings['news_message_protection'] == '1' && $news['frontend_access_id']) {
                    $this->_objTpl->touchBlock('news_message_protected_icon');
                    $this->_objTpl->hideBlock('news_message_not_protected_icon');
                } else {
                    $this->_objTpl->touchBlock('news_message_not_protected_icon');
                    $this->_objTpl->hideBlock('news_message_protected_icon');
                }

                $this->_objTpl->parse('newsrow');
            }
        }

        // set unvalidated list
        $query = "SELECT n.id AS id,
                 n.date AS date,
                 n.changelog AS changelog,
                 n.status AS status,
                 n.validated AS validated,
                 n.typeid AS typeid,
                 n.frontend_access_id,
                 n.userid
                 FROM ".DBPREFIX."module_news AS n
                 WHERE n.validated='0'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult != false) {
            $count = $objResult->RecordCount();
            if (isset($_GET['show']) && $_GET['show'] == 'archive' && isset($_GET['pos'])) {
                $pos = 0;
            } else {
                $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
            }

            if ($count>intval($_CONFIG['corePagingLimit'])) {
                $paging = getPaging($count, $pos, '&amp;cmd=News', $_ARRAYLANG['TXT_NEWS_MESSAGES'],true);
            } else {
                $paging = '';
            }
            $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);

            $arrNews = array();

            while (!$objResult->EOF) {
                $arrNews[$objResult->fields['id']] = array(
                  'date'               => $objResult->fields['date'],
                  'changelog'          => $objResult->fields['changelog'],
                  'status'             => $objResult->fields['status'],
                  'validated'          => $objResult->fields['validated'],
                  'frontend_access_id' => $objResult->fields['frontend_access_id'],
                  'userid'             => $objResult->fields['userid']
                );

                $objLangResult = $objDatabase->Execute('SELECT nl.title as title,
                         nl.lang_id as langid,
                         ntl.name AS typename
                         FROM '.DBPREFIX.'module_news_locale AS nl
                         LEFT JOIN '.DBPREFIX.'module_news_types_locale AS ntl ON ntl.type_id='.$objResult->fields['typeid'].'
                         WHERE nl.news_id='.$objResult->fields['id'].' AND nl.is_active=1 ORDER BY nl.lang_id ASC');
                $newsCategoryIds = $this->getNewsRelCategories($objResult->fields['id']);
                $newsCategoryIdsFlipped = array_flip($newsCategoryIds);
                while (!$objLangResult->EOF) {
                    $combinedNewsCategories = array_intersect_key(
                        $localeCategories[$objLangResult->fields['langid']],
                        $newsCategoryIdsFlipped
                    );
                    $arrNews[$objResult->fields['id']]['lang'][$objLangResult->fields['langid']] = array(
                        'title' => $objLangResult->fields['title'],
                        'catname' => implode(', ', contrexx_raw2xhtml($combinedNewsCategories)),
                        'typename' => $objLangResult->fields['typename']
                    );
                    $objLangResult->MoveNext();
                }

                $objResult->MoveNext();
            }
        }

        $count = count($arrNews);
        if ($count<1) {
            $this->_objTpl->hideBlock('news_tabmenu');
            $this->_objTpl->hideBlock('news_validator');
            $this->_objTpl->setVariable('NEWS_ARCHIVE_DISPLAY_STATUS', 'block');
        } else {
            if (isset($_GET['show']) && $_GET['show'] == 'archive') {
                $this->_objTpl->setVariable(array(
                    'NEWS_ARCHIVE_DISPLAY_STATUS'       => 'block',
                    'NEWS_UNVALIDATED_DISPLAY_STATUS'   => 'none',
                    'NEWS_ARCHIVE_TAB_CALSS'            => 'class="active"',
                    'NEWS_UNVALIDATED_TAB_CALSS'        => ''
                ));
            } else {
                $this->_objTpl->setVariable(array(
                    'NEWS_ARCHIVE_DISPLAY_STATUS'       => 'none',
                    'NEWS_UNVALIDATED_DISPLAY_STATUS'   => 'block',
                    'NEWS_ARCHIVE_TAB_CALSS'            => '',
                    'NEWS_UNVALIDATED_TAB_CALSS'        => 'class="active"'
                ));
            }

            $this->_objTpl->setVariable(array(
                'NEWS_LIST_UNVALIDATED_PARSING'     => $paging,
            ));

            $this->_objTpl->touchBlock('news_tabmenu');

            foreach ($arrNews as $newsId => $news) {
                ($validatorNr % 2) ? $class = 'row2' : $class = 'row1';
                $validatorNr++;

                $statusPicture = 'status_red.gif';
                if ($news['status']==1) {
                    $statusPicture = 'status_green.gif';
                }

                if ($news['userid'] && ($objUser = $objFWUser->objUser->getUser($news['userid']))) {
                    $author = contrexx_raw2xhtml($objUser->getUsername());
                } else {
                    $author = $_ARRAYLANG['TXT_ANONYMOUS'];
                }

                if (isset($news['lang'][FRONTEND_LANG_ID])) {
                    $selectedInterfaceLanguage = FRONTEND_LANG_ID;
                } elseif (isset($news['lang'][\FWLanguage::getDefaultLangId()])) {
                    $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
                } else {
                    $selectedInterfaceLanguage = key($news['lang']);
                }

                $langString = '';
                if (count(\FWLanguage::getActiveFrontendLanguages()) > 1) {
                    $langState = array();
                    foreach ($news['lang'] as $langId => $langValues) {
                        $langState[$langId] = 'active';
                    }
                    $langString = \Html::getLanguageIcons($langState, 'index.php?cmd=News&amp;act=edit&amp;newsId=' . $newsId . '&amp;langId=%1$d');
                    $this->_objTpl->touchBlock('txt_languages_block_invalidated');
                } else {
                    $this->_objTpl->hideBlock('txt_languages_block_invalidated');
                }

                $this->_objTpl->setVariable(array(
                    'NEWS_ID'               => $newsId,
                    'NEWS_DATE'             => date(ASCMS_DATE_FORMAT, $news['date']),
                    'NEWS_TITLE'            => contrexx_raw2xhtml($news['lang'][$selectedInterfaceLanguage]['title']),
                    'NEWS_USER'             => $author,
                    'NEWS_CHANGELOG'        => date(ASCMS_DATE_FORMAT, $news['changelog']),
                    'NEWS_CLASS'            => $class,
                    'NEWS_CATEGORY'         => $news['lang'][$selectedInterfaceLanguage]['catname'],
                    'NEWS_STATUS'           => $news['status'],
                    'NEWS_STATUS_PICTURE'   => $statusPicture,
                    'NEWS_LANGUAGES'        => $langString,
                ));

                $this->_objTpl->parse('news_validator_row');
            }
        }

        $this->_objTpl->setVariable('NEWS_CATEGORY_OPTIONS', $this->getCategoryMenu($this->nestedSetRootId, array($selectedCategory), array(), true));

        // month/year filter
        if (!empty($monthlyStats)) {
            foreach ($monthlyStats as $key => $value){
                $this->_objTpl->setVariable(array(
                    'NEWS_MONTH_NAME'           => (isset($value['archive'])) ? '&nbsp;&nbsp;' . $value['name'] . '(' . $value['archive'] . ')' : $value['name'],
                    'NEWS_MONTH_KEY'            => $key,
                    'NEWS_MONTH_SELECTED'       => (isset($_GET['monthFilter']) && $_GET['monthFilter'] == $key) ? 'selected="selected"' : '',
                ));
                $this->_objTpl->parse('month_navigation_item');
            }
        }
    }


    /**
     * Takes a date in the format dd.mm.yyyy hh:mm and returns it's representation as mktime()-timestamp.
     *
     * @param $value string
     * @return long timestamp
     */
    function dateFromInput($value) {
        if($value === null || $value === '') //not set POST-param passed, return null for the other functions to know this
            return null;
        $arrDate = array();
        if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})\s*([0-9]{1,2})\:([0-9]{1,2}):([0-9]{1,2})/', $value, $arrDate)) {
            return mktime(intval($arrDate[4]), intval($arrDate[5]), intval($arrDate[6]), intval($arrDate[2]), intval($arrDate[1]), intval($arrDate[3]));
        } else {
            return time();
        }
    }


    /**
     * Takes a mktime()-timestamp and formats it as dd.mm.yyyy hh:mm
     *
     * @param $value long timestamp
     * @return string
     */
    function valueFromDate($value = 0) {
        if ($value === null //user provided no POST
            || $value === '0'
            || $value === 0) //empty date field
            return ''; //make an empty date
        if ($value) {
            return date(ASCMS_DATE_FORMAT_DATETIME, $value);
        } else {
            return date(ASCMS_DATE_FORMAT_DATETIME);
        }
    }


    /**
     * Takes a mktime()-timestamp and formats it as yyyy-mm-dd hh:mm:00 for insertion in db.
     *
     * @param $value long timestamp
     * @return string
     */
    function dbFromDate($value) {
        if($value !== null) {
            return date('"Y-m-d H:i:00"', $value);
        }
        else {
            return 'DEFAULT';
        }
    }


    /**
    * adds a news entry
    *
    * @global   array
    * @global   array
    * @global   ADONewConnection
    */
    function add()
    {
        global $_ARRAYLANG, $_CONFIG, $objDatabase, $objInit;

        \JS::activate('cx');

        if (!empty($this->arrSettings['news_use_tags'])) {
            \JS::activate('tag-it');
        }
        $newsTagId = 'newsTags';

        \FWUser::getUserLiveSearch();

        if (!empty($this->arrSettings['use_related_news'])) {
            \ContrexxJavascript::getInstance()->setVariable(
                'noResultsMsg',
                $_ARRAYLANG['TXT_NEWS_NOT_FOUND'],
                'News/RelatedSearch'
            );
            \JS::registerJS('core_modules/News/View/Script/RelatedSearch.js');
            \JS::registerCss('core_modules/News/View/Style/RelatedSearch.css');
        }

        if (!$this->hasCategories()) {
            return $this->manageCategories();
        }

        $objFWUser = \FWUser::getFWUserObject();
        $locales = array(
            'active'        => !empty($_POST['newsManagerLanguages']) ? $_POST['newsManagerLanguages'] : array(),
            'title'         => !empty($_POST['newsTitle']) ? $_POST['newsTitle'] : array(),
            'text'          => !empty($_POST['news_text']) ? $_POST['news_text'] : array(),
            'teaser_text'   => !empty($_POST['newsTeaserText']) ? $_POST['newsTeaserText'] : array()
        );

        if (count(\FWLanguage::getActiveFrontendLanguages()) == 1) {
            $locales['active'] = \FWLanguage::getActiveFrontendLanguages();
        }

        $date = strtotime('now');
        if (isset($_POST['newsDate']) && !empty($_POST['newsDate'])) {
            $date = $this->dateFromInput($_POST['newsDate']);
        }

        $newsredirect           = !empty($_POST['newsRedirect']) && $_POST['newsTypeRadio'] == 'redirect' ? contrexx_input2raw($_POST['newsRedirect']) : '';
        $newssource             = !empty($_POST['newsSource']) ? \FWValidator::getUrl(contrexx_input2raw($_POST['newsSource'])) : '';
        $newsurl1               = !empty($_POST['newsUrl1']) ? \FWValidator::getUrl(contrexx_input2raw($_POST['newsUrl1'])) : '';
        $newsurl2               = !empty($_POST['newsUrl2']) ? \FWValidator::getUrl(contrexx_input2raw($_POST['newsUrl2'])) : '';
        $newsCategories         = !empty($_POST['newsCat']) ? contrexx_input2raw($_POST['newsCat']) : array();
        $newstype               = !empty($_POST['newsType']) ? intval($_POST['newsType']) : 0;
        $newsPublisherName      = !empty($_POST['newsPublisherName']) ? contrexx_input2raw($_POST['newsPublisherName']) : '';
        $newsAuthorName         = !empty($_POST['newsAuthorName']) ? contrexx_input2raw($_POST['newsAuthorName']) : '';
        $newsPublisherId        = !empty($_POST['newsPublisherId']) ? contrexx_input2raw($_POST['newsPublisherId']) : '0';
        $newsAuthorId           = !empty($_POST['newsAuthorId']) ? contrexx_input2raw($_POST['newsAuthorId']) : '0';
        $userid                 = $objFWUser->objUser->getId();
        if (isset($_POST['startDate']) && isset($_POST['endDate'])) {
            $startDate          = $this->dateFromInput($_POST['startDate']);
            $endDate            = $this->dateFromInput($_POST['endDate']);
        }
        $status                 = !empty($_POST['status']) ? intval($_POST['status']) : 0;
        $newsTeaserOnly         = !empty($_POST['newsUseOnlyTeaser']) ? intval($_POST['newsUseOnlyTeaser']) : 0;
        $newsTeaserImagePath    = !empty($_POST['newsTeaserImagePath']) ? contrexx_input2raw($_POST['newsTeaserImagePath']) : '';
        $newsTeaserImageThumbnailPath = !empty($_POST['newsTeaserImageThumbnailPath']) ? contrexx_input2raw($_POST['newsTeaserImageThumbnailPath']) : '';
        $newsTeaserShowLink     = isset($_POST['newsTeaserShowLink']) ? intval($_POST['newsTeaserShowLink']) : intval(!count($_POST));
        $newsTeaserFrames       = '';
        $arrNewsTeaserFrames    = array();
        $newsFrontendAccess     = !empty($_POST['news_read_access']);
        $newsFrontendGroups     = $newsFrontendAccess && isset($_POST['news_read_access_associated_groups']) && is_array($_POST['news_read_access_associated_groups']) ? array_map('intval', $_POST['news_read_access_associated_groups']) : array();
        $newsBackendAccess      = !empty($_POST['news_modify_access']);
        $newsBackendGroups      = $newsBackendAccess && isset($_POST['news_modify_access_associated_groups']) && is_array($_POST['news_modify_access_associated_groups']) ? array_map('intval', $_POST['news_modify_access_associated_groups']) : array();
        $newsCommentActive      = !empty($_POST['allowComment']) ? intval($_POST['allowComment']) : 0;
        $newsScheduledActive    = !empty($_POST['newsScheduled']) ? intval($_POST['newsScheduled']) : 0;
        $relatedNewsIds         = !empty($_POST['newsRelatedNews']) ? contrexx_input2int($_POST['newsRelatedNews']) : array();
        $enableRelatedNews      = intval(!empty($_POST['newsEnableRelatedNews']));
        $newsTags               = !empty($_POST['newsTags']) ? contrexx_input2raw($_POST['newsTags']) : array();
        $enableTags             = !empty($_POST['enableTags']) ? intval($_POST['enableTags']) : 0;
        $redirectNewWindow      = !empty($_POST['redirect_new_window']) ? intval($_POST['redirect_new_window']) : 0;

        if (isset($_POST['newsTeaserFramesAsso']) && count($_POST['newsTeaserFramesAsso'])>0) {
            foreach ($_POST['newsTeaserFramesAsso'] as $frameId) {
                $arrNewsTeaserFrames[] = intval($frameId);
                intval($frameId) > 0 ? $newsTeaserFrames .= ';'.intval($frameId) : false;
            }
        } else {
            $arrNewsDefaultTeasers = explode(';', $this->arrSettings['news_default_teasers']);
            foreach ($arrNewsDefaultTeasers as $frameId) {
                $arrNewsTeaserFrames[] = intval($frameId);
                intval($frameId) > 0 ? $newsTeaserFrames .= ';'.intval($frameId) : false;
            }
        }

        if ($this->arrSettings['news_message_protection'] == '1' && $newsFrontendAccess) {
            if ($this->arrSettings['news_message_protection_restricted'] == '1' && !\Permission::hasAllAccess()) {
                $arrUserGroupIds = $objFWUser->objUser->getAssociatedGroupIds();

                $newsFrontendGroups = array_intersect($newsFrontendGroups, $arrUserGroupIds);
            }

            $newsFrontendAccessId = \Permission::createNewDynamicAccessId();
            if (count($newsFrontendGroups)) {
                \Permission::setAccess($newsFrontendAccessId, 'dynamic', $newsFrontendGroups);
            }
        } else {
            $newsFrontendAccessId = 0;
        }

        if ($this->arrSettings['news_message_protection'] == '1' && $newsBackendAccess) {
            if ($this->arrSettings['news_message_protection_restricted'] == '1' && !\Permission::hasAllAccess()) {
                $arrUserGroupIds = $objFWUser->objUser->getAssociatedGroupIds();

                $newsBackendGroups = array_intersect($newsBackendGroups, $arrUserGroupIds);
            }

            $newsBackendAccessId = \Permission::createNewDynamicAccessId();
            if (count($newsBackendGroups)) {
                \Permission::setAccess($newsBackendAccessId, 'dynamic', $newsBackendGroups);
            }
        } else {
            $newsBackendAccessId = 0;
        }

        $objFWUser->objUser->getDynamicPermissionIds(true);
        if (!empty($_POST) && !empty($locales['active'])) {
            if ($this->validateNews($locales, $newsCategories)) {
                // Set start and date as NULL if newsScheduled checkbox is not checked
                if ($newsScheduledActive == 0) {
                    $startDate = NULL;
                    $endDate   = NULL;
                }

                $objResult = $objDatabase->Execute('INSERT
                                            INTO '.DBPREFIX.'module_news
                                            SET date="'.$date.'",
                                                redirect="'.$newsredirect.'",
                                                source="'.$newssource.'",
                                                url1="'.$newsurl1.'",
                                                url2="'.$newsurl2.'",
                                                typeid="'.$newstype.'",
                                                publisher="'.contrexx_raw2db($newsPublisherName).'",
                                                publisher_id='.intval($newsPublisherId).',
                                                author="'.contrexx_raw2db($newsAuthorName).'",
                                                author_id='.intval($newsAuthorId).',
                                                startdate='.$this->dbFromDate($startDate).',
                                                enddate='.$this->dbFromDate($endDate).',
                                                status='.$status.',
                                                validated="1",
                                                frontend_access_id="'.$newsFrontendAccessId.'",
                                                backend_access_id="'.$newsBackendAccessId.'",
                                                teaser_only="'.$newsTeaserOnly.'",
                                                teaser_frames="'.$newsTeaserFrames.'",
                                                teaser_show_link="'.$newsTeaserShowLink.'",
                                                teaser_image_path="'.$newsTeaserImagePath.'",
                                                teaser_image_thumbnail_path="'.$newsTeaserImageThumbnailPath.'",
                                                userid='.$userid.',
                                                changelog="'.$date.'",
                                                allow_comments='.$newsCommentActive.',
                                                enable_related_news='.$enableRelatedNews.',
                                                enable_tags='.$enableTags.',
                                                redirect_new_window='.$redirectNewWindow
                                        );

                if ($objResult !== false) {
                    $ins_id = $objDatabase->Insert_ID();
                    // store locales
                    if (    !$this->insertLocales($ins_id, $locales)
                        ||  !$this->manipulateCategories($newsCategories, $ins_id)
                        ||  !$this->manipulateRelatedNews($relatedNewsIds, $ins_id)
                        ||  !$this->manipulateTags($newsTags, $ins_id)
                    ) {
                        $this->strErrMessage = empty($this->errMsg)
                            ? $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']
                            : implode('<br>', $this->errMsg);
                    } else {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL'];
                        $this->createRSS();
                        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                        $cx->getEvents()->triggerEvent(
                            'clearEsiCache',
                            array(
                                'Widget',
                                $this->getNewsGlobalPlaceholderNames()
                            )
                        );
                        unset($_POST);
                        return $this->overview();
                    }
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                }
            }
        }

        $this->_objTpl->loadTemplateFile('module_news_modify.html');
        $this->pageTitle = $_ARRAYLANG['TXT_CREATE_NEWS'];

        $objTeaser = new \Cx\Core_Modules\News\Controller\Teasers(true);

        $frameIds = '';
        $associatedFrameIds = '';
        foreach ($objTeaser->arrTeaserFrameNames as $frameName => $frameId) {
            if (in_array($frameId, $arrNewsTeaserFrames)) {
                $associatedFrameIds .= "<option value=\"".$frameId."\">".contrexx_raw2xhtml($frameName)."</option>\n";
            } else {
                $frameIds .= "<option value=\"".$frameId."\">".contrexx_raw2xhtml($frameName)."</option>\n";
            }
        }

        // languages
        $arrLanguages = \FWLanguage::getLanguageArray();
        $this->_objTpl->setVariable('NEWS_DEFAULT_LANG', contrexx_raw2xhtml(\FWLanguage::getLanguageParameter(\FWLanguage::getDefaultLangId(), 'name')));

        if (count($arrLanguages) > 0) {
            $intLanguageCounter = 0;
            $arrActiveLang            = array(0 => '', 1 => '', 2 => '');
            $strJsTabToDiv      = '';

            foreach($arrLanguages as $intId => $arrLanguage) {
                if ($arrLanguage['frontend'] == 1) {
                    $intLanguageId = $arrLanguage['id'];
                    $boolLanguageIsActive = ($intLanguageId == $objInit->userFrontendLangId) ? true : false;

                    $arrActiveLang[$intLanguageCounter%3] .= '<input id="languagebar_'.$intLanguageId.'" class="langCheckboxes" '.(($arrLanguage['is_default'] == 'true' || isset($_POST['newsManagerLanguages'][$intLanguageId])) ? 'checked="checked"' : '').' type="checkbox" name="newsManagerLanguages['.$intLanguageId.']" value="1" onclick="switchBoxAndTab(this, \'news_lang_tab_'.$intLanguageId.'\');" /><label for="languagebar_'.$intLanguageId.'">'.$arrLanguage['name'].' ['.$arrLanguage['lang'].']</label><br />';
                    ++$intLanguageCounter;
                }
            }

            $this->_objTpl->setVariable(array(
                'TXT_LANGUAGE'              => $_ARRAYLANG['TXT_LANGUAGE'],
                'EDIT_LANGUAGES_1'          => $arrActiveLang[0],
                'EDIT_LANGUAGES_2'          => $arrActiveLang[1],
                'EDIT_LANGUAGES_3'          => $arrActiveLang[2]
            ));
        }

        foreach ($arrLanguages as $langId => $arrLanguage) {
            if ($arrLanguage['frontend'] == 1) {
                // parse tabs
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_LANG_DISPLAY_STATUS'  => $arrLanguage['is_default'] == 'true' ? 'active' : 'inactive',
                    'NEWS_LANG_DISPLAY_STYLE'   => $arrLanguage['is_default'] == 'true' || isset($_POST['newsManagerLanguages'][$langId]) ? 'inline' : 'none',
                    'NEWS_LANG_NAME'            => contrexx_raw2xhtml($arrLanguage['name'])
                ));
                $this->_objTpl->parse('news_lang_list');

                // parse title
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_TITLE'                => !empty($_POST['newsTitle'][$langId]) ? contrexx_input2xhtml($_POST['newsTitle'][$langId]) : '',
                    'NEWS_TITLE_DISPLAY'        => $arrLanguage['is_default'] == 'true' ? 'block' : 'none'
                ));
                $this->_objTpl->parse('news_title_list');

                // parse teaser text
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_TEASER_TEXT'          => !empty($_POST['newsTeaserText'][$langId]) ? contrexx_input2xhtml($_POST['newsTeaserText'][$langId]) : '',
                    'NEWS_TEASER_TEXT_LENGTH'   => !empty($_POST['newsTeaserText'][$langId]) ? strlen(contrexx_input2raw($_POST['newsTeaserText'][$langId])) : 0,
                    'NEWS_TITLE_DISPLAY'        => $arrLanguage['is_default'] == 'true' ? 'block' : 'none'
                ));
                $this->_objTpl->parse('news_teaser_text_list');

                // parse text
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_TEXT'                 => !empty($_POST['news_text'][$langId]) ? contrexx_input2xhtml($_POST['news_text'][$langId]) : ''
                ));
                $this->_objTpl->parse('news_text_list');
            }
        }

        if ($intLanguageCounter == 1) {
            $this->_objTpl->setVariable('NEWS_LANG_TAB_DISPLAY_STYLE', 'none');
            $this->_objTpl->hideBlock('news_language_checkboxes');
        }

        if ($this->arrSettings['news_use_teaser_text'] != 1) {
            $this->_objTpl->hideBlock('news_use_teaser_text');
        }

        $catrow = 'row2';
        $news_type_menu = '';
        if($this->arrSettings['news_use_types'] == 1) {
          $catrow = 'row1';
          $news_type_menu = "<tr class=\"row2\">\n<td nowrap=\"nowrap\">{$_ARRAYLANG['TXT_NEWS_TYPE']}</td><td><select name=\"newsType\"><option value=\"0\">{$_ARRAYLANG['TXT_NO_TYPE']}</option>".$this->getTypeMenu($newstype)."</select></td></tr>";
        }

        // Activate Comments
        $news_comment = '';
        if($this->arrSettings['news_comments_activated'] == 1) {
          $commentsChecked = ((!empty($_POST) && empty($newsCommentActive)) ? '' : 'checked="checked"');

          $this->_objTpl->setVariable(array(
              'TXT_NEWS_ALLOW_COMMENTS'   => $_ARRAYLANG['TXT_NEWS_ALLOW_COMMENTS'],
              'NEWS_COMMENT_CHECKED'      => $commentsChecked,
          ));
        } else {
            $this->_objTpl->hideBlock('news_allow_comments_option');
        }

        $this->_objTpl->setGlobalVariable(array(
            'TXT_CATEGORY_SELECT'           => $_ARRAYLANG['TXT_CATEGORY_SELECT'],
            'TXT_NEWS_MESSAGE'              => $_ARRAYLANG['TXT_NEWS_MESSAGE'],
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_TITLE'],
            'TXT_CATEGORY'                  => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_NEWS_AUTHOR'               => $_ARRAYLANG['TXT_NEWS_AUTHOR'],
            'TXT_NEWS_PUBLISHER'            => $_ARRAYLANG['TXT_NEWS_PUBLISHER'],
            'TXT_CORE_SEARCH_USER'          => $_ARRAYLANG['TXT_CORE_SEARCH_USER'],
            'TXT_LANGUAGE'                  => $_ARRAYLANG['TXT_LANGUAGE'],
            'NEWS_FORM_CAT_ROW'             => $catrow,
            'NEWS_TYPE_MENU'                => $news_type_menu,
            'TXT_EXTERNAL_SOURCE'           => $_ARRAYLANG['TXT_EXTERNAL_SOURCE'],
            'TXT_NEWS_SOURCE'               => $_ARRAYLANG['TXT_NEWS_SOURCE'],
            'TXT_NEWS_LINK1'                => $_ARRAYLANG['TXT_NEWS_LINK1'],
            'TXT_NEWS_LINK2'                => $_ARRAYLANG['TXT_NEWS_LINK2'],
            'TXT_NEWS_NEWS_CONTENT'         => $_ARRAYLANG['TXT_NEWS_NEWS_CONTENT'],
            'TXT_DATE'                      => $_ARRAYLANG['TXT_DATE'],
            'TXT_PUBLISHING'                => $_ARRAYLANG['TXT_PUBLISHING'],
            'TXT_SCHEDULED_PUBLICATION'     => $_ARRAYLANG['TXT_SCHEDULED_PUBLICATION'],
            'TXT_STARTDATE'                 => $_ARRAYLANG['TXT_STARTDATE'],
            'TXT_ENDDATE'                   => $_ARRAYLANG['TXT_ENDDATE'],
            'TXT_ACTIVE'                    => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_HEADLINES'                 => $_ARRAYLANG['TXT_HEADLINES'],
            'TXT_TOPNEWS'                   => $_ARRAYLANG['TXT_TOPNEWS'],
            'TXT_TEASERS'                   => $_ARRAYLANG['TXT_TEASERS'],
            'TXT_NEWS_TEASER_TEXT'          => $_ARRAYLANG['TXT_NEWS_TEASER_TEXT'],
            'TXT_IMAGE'                     => $_ARRAYLANG['TXT_IMAGE'],
            'TXT_NEWS_THUMBNAIL'            => $_ARRAYLANG['TXT_NEWS_THUMBNAIL'],
            'TXT_BROWSE'                    => $_ARRAYLANG['TXT_BROWSE'],
            'TXT_NUMBER_OF_CHARS'           => $_ARRAYLANG['TXT_NUMBER_OF_CHARS'],
            'TXT_TEASER_SHOW_NEWS_LINK'     => $_ARRAYLANG['TXT_TEASER_SHOW_NEWS_LINK'],
            'TXT_NEWS_MESSAGE_TYPE'         => $_ARRAYLANG['TXT_NEWS_MESSAGE_TYPE'],
            'TXT_NEWS_TYPE_REDIRECT'        => $_ARRAYLANG['TXT_NEWS_REDIRECT_TITLE'],
            'TXT_NEWS_TYPE_REDIRECT_HELP'   => $_ARRAYLANG['TXT_NEWS_TYPE_REDIRECT_HELP'],
            'TXT_NEWS_TYPE_DEFAULT'         => $_ARRAYLANG['TXT_NEWS_TYPE_DEFAULT'],
            'TXT_NEWS_DEFINE_LINK_ALT_TEXT' => $_ARRAYLANG['TXT_NEWS_DEFINE_LINK_ALT_TEXT'],
            'TXT_NEWS_INSERT_LINK'          => $_ARRAYLANG['TXT_NEWS_INSERT_LINK'],
            'TXT_NEWS_BASIC_DATA'           => $_ARRAYLANG['TXT_BASIC_DATA'],
            'TXT_NEWS_MORE_OPTIONS'         => $_ARRAYLANG['TXT_MORE_OPTIONS'],
            'TXT_NEWS_PERMISSIONS'          => $_ARRAYLANG['TXT_NEWS_PERMISSIONS'],
            'TXT_NEWS_READ_ACCESS'          => $_ARRAYLANG['TXT_NEWS_READ_ACCESS'],
            'TXT_NEWS_MODIFY_ACCESS'        => $_ARRAYLANG['TXT_NEWS_MODIFY_ACCESS'],
            'TXT_NEWS_AVAILABLE_USER_GROUPS'=> $_ARRAYLANG['TXT_NEWS_AVAILABLE_USER_GROUPS'],
            'TXT_NEWS_ASSIGNED_USER_GROUPS' => $_ARRAYLANG['TXT_NEWS_ASSIGNED_USER_GROUPS'],
            'TXT_NEWS_CHECK_ALL'            => $_ARRAYLANG['TXT_NEWS_CHECK_ALL'],
            'TXT_NEWS_UNCHECK_ALL'          => $_ARRAYLANG['TXT_NEWS_UNCHECK_ALL'],
            'TXT_NEWS_READ_ALL_ACCESS_DESC' => $_ARRAYLANG['TXT_NEWS_READ_ALL_ACCESS_DESC'],
            'TXT_NEWS_READ_SELECTED_ACCESS_DESC'    => $_ARRAYLANG['TXT_NEWS_READ_SELECTED_ACCESS_DESC'],
            'TXT_NEWS_MODIFY_ALL_ACCESS_DESC'       => $_ARRAYLANG['TXT_NEWS_MODIFY_ALL_ACCESS_DESC'],
            'TXT_NEWS_MODIFY_SELECTED_ACCESS_DESC'  => $_ARRAYLANG['TXT_NEWS_MODIFY_SELECTED_ACCESS_DESC'],
            'TXT_NEWS_RELATED_NEWS'                 => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS'],
            'TXT_NEWS_INCLUDE_NEWS'                 => $_ARRAYLANG['TXT_NEWS_INCLUDE_NEWS'],
            'TXT_NEWS_INCLUDE_RELATED_NEWS_DESC'    => $_ARRAYLANG['TXT_NEWS_INCLUDE_RELATED_NEWS_DESC'],
            'TXT_NEWS_SEARCH_INFO'              => $_ARRAYLANG['TXT_NEWS_SEARCH_INFO'],
            'TXT_NEWS_SEARCH_PLACEHOLDER'       => $_ARRAYLANG['TXT_NEWS_SEARCH_PLACEHOLDER'],
            'TXT_NEWS_REDIRECT_NEW_WINDOW'       => $_ARRAYLANG['TXT_NEWS_REDIRECT_NEW_WINDOW'],
            'TXT_NEWS_REDIRECT_NEW_WINDOW_HELP'  => $_ARRAYLANG['TXT_NEWS_REDIRECT_NEW_WINDOW_HELP'],

            'TXT_NEWS_TAGS'             => $_ARRAYLANG['TXT_NEWS_TAGS'],
            'TXT_NEWS_TAGS_ENABLE'      => $_ARRAYLANG['TXT_NEWS_TAGS_ENABLE'],
            'NEWS_TAGS_ENABLED_CHECKED' => 'checked="checked"',
            'NEWS_TAG_ID'               => $newsTagId,
        ));
        if (!empty($this->arrSettings['use_related_news'])) {
            try {
                $relatedNews = $this->getRelatedNews(0, $relatedNewsIds, false);
                // parse related news
                while (!$relatedNews->EOF) {
                    $relatedNewsTitle = $relatedNews->fields['newstitle'];
                    $relatedNewsTitleShort = strip_tags($relatedNewsTitle);
                    if (strlen($relatedNewsTitleShort) > 35) {
                        $relatedNewsTitleShort = substr($relatedNewsTitleShort, 0, 35) . '...';
                    }
                    $this->_objTpl->setVariable(
                        array(
                            'NEWS_RELATED_NEWS_ID'          => $relatedNews->fields['newsid'],
                            'NEWS_RELATED_NEWS_TITLE'       => contrexx_raw2xhtml($relatedNewsTitle),
                            'NEWS_RELATED_NEWS_TITLE_SHORT' => contrexx_raw2xhtml($relatedNewsTitleShort),
                        )
                    );
                    $relatedNews->MoveNext();
                    $this->_objTpl->parse('news_related_news');
                }
            } catch (NewsLibraryException $e) {
                $this->_objTpl->hideBlock('news_related_news');
            }

            $this->_objTpl->touchBlock('news_related_news_container');
        } else {
            $this->_objTpl->hideBlock('news_related_news_container');
        }
        //Parsing the saved tags
        if (!empty($this->arrSettings['news_use_tags'])) {
            $this->registerTagJsCode();
            if (    $this->_objTpl->blockExists('news_tags')
                &&  !empty($newsTags)
            ) {
                foreach ($newsTags as $newsTag) {
                    $this->_objTpl->setVariable('NEWS_TAGS', contrexx_raw2xhtml($newsTag));
                    $this->_objTpl->parse('news_tags');
                }
            }
            $this->_objTpl->touchBlock('news_tags_container');
        } else {
            $this->_objTpl->hideBlock('news_tags_container');
        }
         $this->_objTpl->setVariable(array(
            'NEWS_TEXT_PREVIEW'             => new \Cx\Core\Wysiwyg\Wysiwyg('newsText', !empty($locales['text'][\FWLanguage::getDefaultLangId()]) ? $locales['text'][\FWLanguage::getDefaultLangId()] : '', 'full'),
            'NEWS_REDIRECT'                 => contrexx_raw2xhtml($newsredirect),
            'NEWS_FORM_ACTION'              => 'add',
            'NEWS_STORED_FORM_ACTION'       => 'add',
            'NEWS_STATUS'                   => (empty($_POST) || $status == 1) ? 'checked="checked"' : '',
            'NEWS_SCHEDULED_DISPLAY'        => $newsScheduledActive == 0 ? 'display:none;' : 'display:block',
            'NEWS_ID'                       => '0',
            'NEWS_PUBLISHER_ID'             => '0',
            'NEWS_AUTHOR_ID'                => '0',
            'NEWS_TOP_TITLE'                => $_ARRAYLANG['TXT_CREATE_NEWS'],
            'NEWS_CAT_MENU'                 => $this->getCategoryMenu($this->nestedSetRootId, $newsCategories),
            'NEWS_STARTDATE'                => isset($startDate) ? $this->valueFromDate($startDate) : '',
            'NEWS_ENDDATE'                  => isset($endDate) ? $this->valueFromDate($endDate): '',
            'NEWS_DATE'                     => date('Y-m-d H:i:s'),
            'NEWS_CREATE_DATE'              => $this->valueFromDate(),
            'NEWS_SOURCE'                   => contrexx_raw2xhtml($newssource),
            'NEWS_URL1'                     => contrexx_raw2xhtml($newsurl1),
            'NEWS_URL2'                     => contrexx_raw2xhtml($newsurl2),
            'NEWS_SUBMIT_NAME_TEXT'         => $_ARRAYLANG['TXT_STORE'],
            'NEWS_TEASER_SHOW_LINK_CHECKED' => $newsTeaserShowLink ? 'checked="checked"' : '',
            'NEWS_TYPE_SELECTION_CONTENT'   => empty($newsredirect) ? 'style="display: block;"' : 'style="display: none"',
            'NEWS_TYPE_SELECTION_REDIRECT'  => empty($newsredirect) ? 'style="display: none;"' : 'style="display: block"',
            'NEWS_TYPE_CHECKED_CONTENT'     => empty($newsredirect) ? 'checked="checked"' : '',
            'NEWS_TYPE_CHECKED_REDIRECT'    => empty($newsredirect) ? '' : 'checked="checked"',
            'NEWS_TEASER_IMAGE_PATH'        => contrexx_raw2xhtml($newsTeaserImagePath),
            'NEWS_TEASER_IMAGE_THUMBNAIL_PATH' => contrexx_raw2xhtml($newsTeaserImageThumbnailPath),
            'NEWS_MEDIA_BROWSER_BROWSE_BUTTON' => self::getMediaBrowserButton(array(
                                                                                'type'  => 'button',
                                                                                'id'    => 'newsMediaBrowser',
                                                                                'style' => 'display:none;'
                                                                              ),'SetUrl'),
            'NEWS_RELATED_NEWS_ENABLED_CHECKED' => $enableRelatedNews ? 'checked="checked"' : '',
        ));

        if ($_CONFIG['newsTeasersStatus'] == '1') {
            $this->_objTpl->parse('newsTeaserOptions');
            $this->_objTpl->setVariable(array(
                'TXT_USAGE'                     => $_ARRAYLANG['TXT_USAGE'],
                'TXT_USE_ONLY_TEASER_TEXT'      => $_ARRAYLANG['TXT_USE_ONLY_TEASER_TEXT'],
                'TXT_TEASER_TEASER_BOXES'       => $_ARRAYLANG['TXT_TEASER_TEASER_BOXES'],
                'TXT_AVAILABLE_BOXES'           => $_ARRAYLANG['TXT_AVAILABLE_BOXES'],
                'TXT_SELECT_ALL'                => $_ARRAYLANG['TXT_SELECT_ALL'],
                'TXT_DESELECT_ALL'              => $_ARRAYLANG['TXT_DESELECT_ALL'],
                'TXT_ASSOCIATED_BOXES'          => $_ARRAYLANG['TXT_ASSOCIATED_BOXES'],
                'NEWS_HEADLINES_TEASERS_TXT'    => $_ARRAYLANG['TXT_HEADLINES'].' / '.$_ARRAYLANG['TXT_TEASERS'],
                'NEWS_USE_ONLY_TEASER_CHECKED'  => $newsTeaserOnly ? 'checked="checked"' : '',
                'NEWS_TEASER_FRAMES'            => $frameIds,
                'NEWS_TEASER_ASSOCIATED_FRAMES' => $associatedFrameIds
            ));
        } else {
            $this->_objTpl->hideBlock('newsTeaserOptions');
            $this->_objTpl->setVariable('NEWS_HEADLINES_TEASERS_TXT', $_ARRAYLANG['TXT_HEADLINES']);
        }

        if ($this->arrSettings['news_message_protection'] == '1') {
            if ($this->arrSettings['news_message_protection_restricted'] == '1') {
                $userGroupIds = $objFWUser->objUser->getAssociatedGroupIds();
            }

            $readAccessGroups = '';
            $modifyAccessGroups = '';
            $objGroup = $objFWUser->objGroup->getGroups();
            if ($objGroup) {
                while (!$objGroup->EOF) {
                    if (   \Permission::hasAllAccess()
                        || $this->arrSettings['news_message_protection_restricted'] != '1'
                        || in_array($objGroup->getId(), $userGroupIds)
                    ) {
                        ${$objGroup->getType() == 'frontend' ? 'readAccessGroups' : 'modifyAccessGroups'} .= '<option value="'.$objGroup->getId().'">'.contrexx_raw2xhtml($objGroup->getName()).'</option>';
                    }
                    $objGroup->next();
                }
            }

            $this->_objTpl->setVariable(array(
                'NEWS_READ_ACCESS_NOT_ASSOCIATED_GROUPS'    => $readAccessGroups,
                'NEWS_READ_ACCESS_ASSOCIATED_GROUPS'        => '',
                'NEWS_READ_ACCESS_ALL_CHECKED'              => 'checked="checked"',
                'NEWS_READ_ACCESS_DISPLAY'                  => 'none',
                'NEWS_MODIFY_ACCESS_NOT_ASSOCIATED_GROUPS'  => $modifyAccessGroups,
                'NEWS_MODIFY_ACCESS_ASSOCIATED_GROUPS'      => '',
                'NEWS_MODIFY_ACCESS_ALL_CHECKED'            => 'checked="checked"',
                'NEWS_MODIFY_ACCESS_DISPLAY'                => 'none'
            ));

            $this->_objTpl->touchBlock('news_permission_tab');
        } else {
            $this->_objTpl->hideBlock('news_permission_tab');
        }
    }

    /**
    * Deletes a news entry
    *
    * @global    ADONewConnection
    * @global    array
    * @return    -
    */
    function delete()
    {
        global $objDatabase, $_ARRAYLANG;

        //have we deleted a news entry?
        $entryDeleted=false;

        $newsId = '';
        if (isset($_GET['newsId'])) {
            $newsId = intval($_GET['newsId']);
            if (    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news WHERE id = ".$newsId) !== false
                &&  $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_comments WHERE newsid = ".$newsId) !== false
                &&  $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_locale WHERE news_id = ".$newsId) !== false
                &&  $objDatabase->Execute("
                        DELETE
                        FROM ". DBPREFIX . "module_news_rel_categories
                        WHERE news_id = " . contrexx_input2db($newsId)
                    ) !== false
                &&  $objDatabase->Execute("
                        DELETE
                        FROM ". DBPREFIX . "module_news_rel_news
                        WHERE news_id = " . contrexx_input2db($newsId)
                    ) !== false
                &&  $objDatabase->Execute("
                        DELETE
                        FROM " . DBPREFIX . "module_news_rel_tags
                        WHERE news_id = ". contrexx_input2db($newsId)
                    ) !== false
            ) {
                $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                $this->createRSS();
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                $cx->getEvents()->triggerEvent(
                    'clearEsiCache',
                    array(
                        'Widget',
                        $this->getNewsGlobalPlaceholderNames()
                    )
                );
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            }
            $entryDeleted=true;
        }

        if (isset($_POST['selectedNewsId']) && is_array($_POST['selectedNewsId'])) {
            foreach ($_POST['selectedNewsId'] AS $value) {
                if (!empty($value)) {
                    if (    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news WHERE id = ".intval($value)) !== false
                        &&  $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_comments WHERE newsid = ".intval($value)) !== false
                        &&  $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_locale WHERE news_id = ".intval($value)) !== false
                        &&  $objDatabase->Execute("
                                DELETE
                                FROM " . DBPREFIX . "module_news_rel_categories
                                WHERE news_id = ". contrexx_input2db($value)
                            ) !== false
                        &&  $objDatabase->Execute("
                                DELETE
                                FROM ". DBPREFIX . "module_news_rel_news
                                WHERE news_id = " . contrexx_input2db($value)
                            ) !== false
                        &&  $objDatabase->Execute("
                                DELETE
                                FROM " . DBPREFIX . "module_news_rel_tags
                                WHERE news_id = ". contrexx_input2db($value)
                            ) !== false
                    ) {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                        $this->createRSS();
                        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                        $cx->getEvents()->triggerEvent(
                            'clearEsiCache',
                            array(
                                'Widget',
                                $this->getNewsGlobalPlaceholderNames()
                            )
                        );
                    } else {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    }
                }
                $entryDeleted=true;
            }
        } elseif (isset($_POST['selectedUnvalidatedNewsId']) && is_array($_POST['selectedUnvalidatedNewsId'])) {
            foreach ($_POST['selectedUnvalidatedNewsId'] AS $value) {
                if (!empty($value)) {
                    if (    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news WHERE id = ".intval($value)) !== false
                        &&  $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_comments WHERE newsid = ".intval($value)) !== false
                        &&  $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_locale WHERE news_id = ".intval($value)) !== false
                        &&  $objDatabase->Execute("
                                DELETE
                                FROM " . DBPREFIX . "module_news_rel_categories
                                WHERE news_id = ". contrexx_input2db($value)
                            ) !== false
                        &&  $objDatabase->Execute("
                                DELETE
                                FROM ". DBPREFIX . "module_news_rel_news
                                WHERE news_id = " . contrexx_input2db($value)
                            ) !== false
                        &&  $objDatabase->Execute("
                                DELETE
                                FROM " . DBPREFIX . "module_news_rel_tags
                                WHERE news_id = ". contrexx_input2db($value)
                            ) !== false
                    ) {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                        $this->createRSS();
                        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                        $cx->getEvents()->triggerEvent(
                            'clearEsiCache',
                            array(
                                'Widget',
                                $this->getNewsGlobalPlaceholderNames()
                            )
                        );
                    } else {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    }
                }
                $entryDeleted=true;
            }
        }
        if(!$entryDeleted)
            $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_NOTICE_NOTHING_SELECTED'];
    }


    /**
    * Edit the news, or if $copy is true, it copies an entry
    *
    * @global    ADONewConnection
    * @global    array
    * @global    array
    * @param     string     $pageContent
    */
    function edit($copy = false)
    {
        global $_ARRAYLANG, $_CONFIG;

        \JS::activate('cx');
        if (!empty($this->arrSettings['news_use_tags'])) {
            \JS::activate('tag-it');
        }
        $newsTagId = 'newsTags';

        \FWUser::getUserLiveSearch();

        if (!$this->hasCategories()) {
            return $this->manageCategories();
        }

        $objFWUser = \FWUser::getFWUserObject();

        $status = '';

        $this->_objTpl->loadTemplateFile('module_news_modify.html',true,true);
        $this->pageTitle = (($copy) ? $_ARRAYLANG['TXT_CREATE_NEWS'] : $_ARRAYLANG['TXT_EDIT_NEWS_CONTENT']);

        $catrow = 'row2';
        if($this->arrSettings['news_use_types'] == 1) {
          $catrow = 'row1';

        }

        $this->_objTpl->setGlobalVariable(array(
            'TXT_CATEGORY_SELECT'           => $_ARRAYLANG['TXT_CATEGORY_SELECT'],
            'TXT_COPY'                      => $_ARRAYLANG['TXT_NEWS_COPY'],
            'TXT_NEWS_MESSAGE'              => $_ARRAYLANG['TXT_NEWS_MESSAGE'],
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_TITLE'],
            'TXT_CATEGORY'                  => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_NEWS_AUTHOR'               => $_ARRAYLANG['TXT_NEWS_AUTHOR'],
            'TXT_NEWS_PUBLISHER'            => $_ARRAYLANG['TXT_NEWS_PUBLISHER'],
            'TXT_CORE_SEARCH_USER'          => $_ARRAYLANG['TXT_CORE_SEARCH_USER'],
            'NEWS_FORM_CAT_ROW'             => $catrow,
            'TXT_NEWS_TYPE'                 => $_ARRAYLANG['TXT_NEWS_TYPE'],
            'TXT_EXTERNAL_SOURCE'           => $_ARRAYLANG['TXT_EXTERNAL_SOURCE'],
            'TXT_NEWS_SOURCE'               => $_ARRAYLANG['TXT_NEWS_SOURCE'],
            'TXT_NEWS_LINK1'                => $_ARRAYLANG['TXT_NEWS_LINK1'],
            'TXT_NEWS_LINK2'                => $_ARRAYLANG['TXT_NEWS_LINK2'],
            'TXT_NEWS_REDIRECT_NEW_WINDOW'      => $_ARRAYLANG['TXT_NEWS_REDIRECT_NEW_WINDOW'],
            'TXT_NEWS_REDIRECT_NEW_WINDOW_HELP' => $_ARRAYLANG['TXT_NEWS_REDIRECT_NEW_WINDOW_HELP'],
            'TXT_NEWS_NEWS_CONTENT'         => $_ARRAYLANG['TXT_NEWS_NEWS_CONTENT'],
            'TXT_PUBLISHING'                => $_ARRAYLANG['TXT_PUBLISHING'],
            'TXT_STARTDATE'                 => $_ARRAYLANG['TXT_STARTDATE'],
            'TXT_ENDDATE'                   => $_ARRAYLANG['TXT_ENDDATE'],
            'TXT_OPTIONAL'                  => $_ARRAYLANG['TXT_OPTIONAL'],
            'TXT_ACTIVE'                    => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_SCHEDULED_PUBLICATION'     => $_ARRAYLANG['TXT_SCHEDULED_PUBLICATION'],
            'TXT_DATE'                      => $_ARRAYLANG['TXT_DATE'],
            'TXT_HEADLINES'                 => $_ARRAYLANG['TXT_HEADLINES'],
            'TXT_TOPNEWS'                   => $_ARRAYLANG['TXT_TOPNEWS'],
            'TXT_TEASERS'                   => $_ARRAYLANG['TXT_TEASERS'],
            'TXT_NEWS_TEASER_TEXT'          => $_ARRAYLANG['TXT_NEWS_TEASER_TEXT'],
            'TXT_IMAGE'                     => $_ARRAYLANG['TXT_IMAGE'],
            'TXT_NEWS_THUMBNAIL'            => $_ARRAYLANG['TXT_NEWS_THUMBNAIL'],
            'TXT_BROWSE'                    => $_ARRAYLANG['TXT_BROWSE'],
            'TXT_NUMBER_OF_CHARS'           => $_ARRAYLANG['TXT_NUMBER_OF_CHARS'],
            'TXT_TEASER_SHOW_NEWS_LINK'     => $_ARRAYLANG['TXT_TEASER_SHOW_NEWS_LINK'],
            'TXT_NEWS_DEFINE_LINK_ALT_TEXT' => $_ARRAYLANG['TXT_NEWS_DEFINE_LINK_ALT_TEXT'],
            'TXT_NEWS_INSERT_LINK'          => $_ARRAYLANG['TXT_NEWS_INSERT_LINK'],
            'TXT_NEWS_REDIRECT_TITLE'       => $_ARRAYLANG['TXT_NEWS_REDIRECT_TITLE'],
            'TXT_NEWS_MESSAGE_TYPE'         => $_ARRAYLANG['TXT_NEWS_MESSAGE_TYPE'],
            'TXT_NEWS_TYPE_REDIRECT'        => $_ARRAYLANG['TXT_NEWS_REDIRECT_TITLE'],
            'TXT_NEWS_TYPE_REDIRECT_HELP'   => $_ARRAYLANG['TXT_NEWS_TYPE_REDIRECT_HELP'],
            'TXT_NEWS_TYPE_DEFAULT'         => $_ARRAYLANG['TXT_NEWS_TYPE_DEFAULT'],
            'TXT_NEWS_BASIC_DATA'           => $_ARRAYLANG['TXT_BASIC_DATA'],
            'TXT_NEWS_MORE_OPTIONS'         => $_ARRAYLANG['TXT_MORE_OPTIONS'],
            'TXT_NEWS_PERMISSIONS'          => $_ARRAYLANG['TXT_NEWS_PERMISSIONS'],
            'TXT_NEWS_READ_ACCESS'          => $_ARRAYLANG['TXT_NEWS_READ_ACCESS'],
            'TXT_NEWS_MODIFY_ACCESS'        => $_ARRAYLANG['TXT_NEWS_MODIFY_ACCESS'],
            'TXT_NEWS_AVAILABLE_USER_GROUPS'    => $_ARRAYLANG['TXT_NEWS_AVAILABLE_USER_GROUPS'],
            'TXT_NEWS_ASSIGNED_USER_GROUPS' => $_ARRAYLANG['TXT_NEWS_ASSIGNED_USER_GROUPS'],
            'TXT_NEWS_CHECK_ALL'            => $_ARRAYLANG['TXT_NEWS_CHECK_ALL'],
            'TXT_NEWS_UNCHECK_ALL'          => $_ARRAYLANG['TXT_NEWS_UNCHECK_ALL'],
            'TXT_NEWS_READ_ALL_ACCESS_DESC' => $_ARRAYLANG['TXT_NEWS_READ_ALL_ACCESS_DESC'],

            'TXT_NEWS_RELATED_NEWS'              => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS'],
            'TXT_NEWS_INCLUDE_NEWS'              => $_ARRAYLANG['TXT_NEWS_INCLUDE_NEWS'],
            'TXT_NEWS_INCLUDE_RELATED_NEWS_DESC' => $_ARRAYLANG['TXT_NEWS_INCLUDE_RELATED_NEWS_DESC'],
            'TXT_NEWS_SEARCH_INFO'          => $_ARRAYLANG['TXT_NEWS_SEARCH_INFO'],
            'TXT_NEWS_SEARCH_PLACEHOLDER'   => $_ARRAYLANG['TXT_NEWS_SEARCH_PLACEHOLDER'],

            'TXT_NEWS_READ_SELECTED_ACCESS_DESC'    => $_ARRAYLANG['TXT_NEWS_READ_SELECTED_ACCESS_DESC'],
            'TXT_NEWS_AVAILABLE_USER_GROUPS'        => $_ARRAYLANG['TXT_NEWS_AVAILABLE_USER_GROUPS'],
            'TXT_NEWS_ASSIGNED_USER_GROUPS'         => $_ARRAYLANG['TXT_NEWS_ASSIGNED_USER_GROUPS'],
            'TXT_NEWS_MODIFY_ALL_ACCESS_DESC'       => $_ARRAYLANG['TXT_NEWS_MODIFY_ALL_ACCESS_DESC'],
            'TXT_NEWS_MODIFY_SELECTED_ACCESS_DESC'  => $_ARRAYLANG['TXT_NEWS_MODIFY_SELECTED_ACCESS_DESC'],

            'TXT_NEWS_TAGS'             => $_ARRAYLANG['TXT_NEWS_TAGS'],
            'TXT_NEWS_TAGS_ENABLE'      => $_ARRAYLANG['TXT_NEWS_TAGS_ENABLE'],
            'NEWS_TAG_ID'               => $newsTagId,
        ));

        $newsid = intval($_REQUEST['newsId']);
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objResult = $cx->getDb()->getAdoDb()->SelectLimit("SELECT  typeid,
                                                        date,
                                                        id,
                                                        redirect,
                                                        source,
                                                        url1,
                                                        url2,
                                                        publisher,
                                                        publisher_id,
                                                        author,
                                                        author_id,
                                                        startdate,
                                                        enddate,
                                                        status,
                                                        userid,
                                                        frontend_access_id,
                                                        backend_access_id,
                                                        teaser_only,
                                                        teaser_show_link,
                                                        teaser_image_path,
                                                        teaser_image_thumbnail_path,
                                                        allow_comments,
                                                        enable_related_news,
                                                        redirect_new_window,
                                                        enable_tags
                                                FROM    ".DBPREFIX."module_news
                                                WHERE   id = '".$newsid."'", 1);

        if (
            $objResult === false ||
            $objResult->EOF
        ) {
            $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_ENTRY_NOT_FOUND'];
            $this->overview();
            return;
        }

        // verify access permission
        if (
            $this->arrSettings['news_message_protection'] &&
            !\Permission::hasAllAccess() &&
            $objResult->fields['backend_access_id'] &&
            !\Permission::checkAccess($objResult->fields['backend_access_id'], 'dynamic', true) &&
            $objResult->fields['userid'] != $objFWUser->objUser->getId()
        ) {
            $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_ENTRY_NOT_FOUND'];
            $this->overview();
            return;
        }
        
        $newsType=$objResult->fields['typeid'];
        $id = $objResult->fields['id'];
        $arrLanguages = \FWLanguage::getLanguageArray();
        $langData = $this->getLangData($id);
        $newsComment = $objResult->fields['allow_comments'];

        $newsAuthorName = $objResult->fields['author'];
        $newsAuthorId = $objResult->fields['author_id'];
        $newsPublisherName = $objResult->fields['publisher'];
        $newsPublisherId = $objResult->fields['publisher_id'];

        if ($newsPublisherId != 0 && ($objUser = $objFWUser->objUser->getUser($newsPublisherId))) {
            $newsPublisherName = \FWUser::getParsedUserTitle($objUser);
        } else {
            $newsPublisherId = 0;
        }
        if ($newsAuthorId != 0 && ($objUser = $objFWUser->objUser->getUser($newsAuthorId))) {
            $newsAuthorName = \FWUser::getParsedUserTitle($objUser);
        } else {
            $newsAuthorId = 0;
        }
        $active_lang        = array();

        $activeLanguage = null;
        if (isset($_GET['langId']) && in_array($_GET['langId'], \FWLanguage::getIdArray())) {
            if (!in_array($_GET['langId'], $active_lang)) {
                $active_lang[] = contrexx_input2raw($_GET['langId']);
            }
            $activeLanguage = contrexx_input2raw($_GET['langId']);
        }

        if (count($arrLanguages) > 0) {
            $intLanguageCounter = 0;
            $arrActiveLang      = array(0 => '', 1 => '', 2 => '');
            $strJsTabToDiv      = '';

            $query = "SELECT `lang_id` FROM `".DBPREFIX."module_news_locale`
                            WHERE `news_id` = ".$newsid."
                            AND `is_active` = '1'";
            $activeLangResult = $cx->getDb()->getAdoDb()->Execute($query);
            while (!$activeLangResult->EOF) {
                $active_lang[] = $activeLangResult->fields['lang_id'];
                $activeLangResult->MoveNext();
            }

            if (isset($_POST['newsManagerLanguages'])) {
                $active_lang = array_keys($_POST['newsManagerLanguages']);
            }

            foreach($arrLanguages as $intId => $arrLanguage) {
                if ($arrLanguage['frontend'] == 1) {
                    $intLanguageId = $arrLanguage['id'];

                    $arrActiveLang[$intLanguageCounter%3] .= '<input id="languagebar_'.$intLanguageId.'" class="langCheckboxes" '.((in_array($intLanguageId, $active_lang)) ? 'checked="checked"' : '').' type="checkbox" name="newsManagerLanguages['.$intLanguageId.']" value="1" onclick="switchBoxAndTab(this, \'news_lang_tab_'.$intLanguageId.'\');" /><label for="languagebar_'.$intLanguageId.'">'.$arrLanguage['name'].' ['.$arrLanguage['lang'].']</label><br />';
                    ++$intLanguageCounter;
                }
            }

            $this->_objTpl->setVariable(array(
                'TXT_LANGUAGE'              => $_ARRAYLANG['TXT_LANGUAGE'],
                'EDIT_LANGUAGES_1'          => $arrActiveLang[0],
                'EDIT_LANGUAGES_2'          => $arrActiveLang[1],
                'EDIT_LANGUAGES_3'          => $arrActiveLang[2]
            ));
        }

        $first = true;

        if (!$activeLanguage) {
            $activeLanguage = current($active_lang);
        }

        foreach ($arrLanguages as $langId => $arrLanguage) {
            if ($arrLanguage['frontend'] == 1) {

                $isActive = isset($langData[$langId]) && ($langData[$langId]['active'] == 1);
                $display = $langId == $activeLanguage;

                // parse tabs
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_LANG_DISPLAY_STATUS'  => $display ? 'active' : 'inactive',
                    'NEWS_LANG_DISPLAY_STYLE'   => in_array($arrLanguage['id'], $active_lang) ? 'inline' : 'none',
                    'NEWS_LANG_NAME'            => contrexx_raw2xhtml($arrLanguage['name'])
                ));
                $this->_objTpl->parse('news_lang_list');

                // parse title
                $title = isset($_POST['newsTitle'][$langId]) ? contrexx_input2raw($_POST['newsTitle'][$langId]) : '';
                if (empty($title)) {
                    $title = isset($langData[$langId]['title']) ? $langData[$langId]['title'] : '';
                }
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_TITLE'                => contrexx_raw2xhtml($title),
                    'NEWS_TITLE_DISPLAY'        => $display ? 'block' : 'none',
                ));
                $this->_objTpl->parse('news_title_list');

                // parse teaser text
                $teaserText = isset($_POST['newsTeaserText'][$langId]) ? contrexx_input2raw($_POST['newsTeaserText'][$langId]) : null;
                if (!isset($teaserText)) {
                    $teaserText = isset($langData[$langId]['teaser_text']) ? $langData[$langId]['teaser_text'] : '';
                }
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_TEASER_TEXT'          => contrexx_raw2xhtml($teaserText),
                    'NEWS_TEASER_TEXT_LENGTH'   => !empty($teaserText) ? strlen($teaserText) : 0,
                    'NEWS_TITLE_DISPLAY'        => $display ? 'block' : 'none',
                ));
                $this->_objTpl->parse('news_teaser_text_list');

                // parse text
                $text = isset($_POST['news_text'][$langId]) ? $_POST['news_text'][$langId] : null;
                if (!isset($text)) {
                    $text = isset($langData[$langId]['text']) ? $langData[$langId]['text'] : '';
                }
                $this->_objTpl->setVariable(array(
                    'NEWS_LANG_ID'              => $langId,
                    'NEWS_TEXT'                 => contrexx_raw2xhtml($text),
                ));
                $this->_objTpl->parse('news_text_list');

                if ($display) {
                    $selectedLangId = $langId;
                    $newsText       = contrexx_raw2xhtml($text);
                    $first          = false;
                }
            }
        }

        if ($intLanguageCounter == 1) {
            $this->_objTpl->setVariable('NEWS_LANG_TAB_DISPLAY_STYLE', 'none');
            $this->_objTpl->hideBlock('news_language_checkboxes');
        }

        $this->_objTpl->setVariable('NEWS_DEFAULT_LANG', contrexx_raw2xhtml(\FWLanguage::getLanguageParameter($selectedLangId, 'name')));

        if ($this->arrSettings['news_use_teaser_text'] != 1) {
            $this->_objTpl->hideBlock('news_use_teaser_text');
        }

        $teaserShowLink = $objResult->fields['teaser_show_link'];

        if ($objResult->fields['status']==1) {
            $status = 'checked="checked"';
        }

        $startDate = ($objResult->fields['startdate'] !== '0000-00-00 00:00:00') ? strtotime($objResult->fields['startdate']) : 0;
        $endDate = ($objResult->fields['enddate'] !== '0000-00-00 00:00:00') ? strtotime($objResult->fields['enddate']) : 0;

        if (!empty($startDate) || !empty($endDate)) {
            $this->_objTpl->setVariable(array(
                'NEWS_SCHEDULED'         => 'checked="checked"',
                'NEWS_SCHEDULED_DISPLAY' => 'display: block;'
            ));
        } else {
            $this->_objTpl->setVariable('NEWS_SCHEDULED_DISPLAY','display: none;');
        }

        if (empty($objResult->fields['redirect'])) {
            $this->_objTpl->setVariable(array(
                'NEWS_TYPE_SELECTION_CONTENT'   => 'style="display: block;"',
                'NEWS_TYPE_SELECTION_REDIRECT'  => 'style="display: none;"',
                'NEWS_TYPE_CHECKED_CONTENT'     => 'checked="checked"',
                'NEWS_TYPE_CHECKED_REDIRECT'    => ''
            ));
        } else {
            $this->_objTpl->setVariable(array(
                'NEWS_TYPE_SELECTION_CONTENT'   => 'style="display: none;"',
                'NEWS_TYPE_SELECTION_REDIRECT'  => 'style="display: block;"',
                'NEWS_TYPE_CHECKED_CONTENT'     => '',
                'NEWS_TYPE_CHECKED_REDIRECT'    => 'checked="checked"'
            ));
        }

        $objTeaser = new \Cx\Core_Modules\News\Controller\Teasers(true);

        $frameIds = '';
        $associatedFrameIds = '';
        $arrAssociatedFrameIds = array();
        if (isset($objTeaser->arrTeasers[$newsid])) {
            $arrAssociatedFrameIds = explode(';', $objTeaser->arrTeasers[$newsid]['teaser_frames']);
        }
        foreach ($arrAssociatedFrameIds as $teaserFrameId) {
            if (empty($teaserFrameId)) {
                continue;
            }
            $associatedFrameIds .= "<option value=\"".$teaserFrameId."\">".$objTeaser->arrTeaserFrames[$teaserFrameId]['name']."</option>\n";
        }
        foreach ($objTeaser->arrTeaserFrameNames as $frameName => $frameId) {
            if (!in_array($frameId, $arrAssociatedFrameIds)) {
                $frameIds .= "<option value=\"".$frameId."\">".$frameName."</option>\n";
            }
        }

        $this->_objTpl->setVariable(array(
            'NEWS_ID'                       => (($copy) ? '' : $id),
            'NEWS_STORED_ID'                => (($copy) ? '' : $id),
            'NEWS_TEXT_PREVIEW'             => new \Cx\Core\Wysiwyg\Wysiwyg('newsText', $newsText, 'full'),
            'NEWS_REDIRECT'                 => contrexx_raw2xhtml($objResult->fields['redirect']),
            'NEWS_SOURCE'                   => contrexx_raw2xhtml($objResult->fields['source']),
            'NEWS_URL1'                     => contrexx_raw2xhtml($objResult->fields['url1']),
            'NEWS_URL2'                     => contrexx_raw2xhtml($objResult->fields['url2']),
            'NEWS_PUBLISHER_NAME'           => contrexx_raw2xhtml($newsPublisherName),
            'NEWS_PUBLISHER_ID'             => contrexx_raw2xhtml($newsPublisherId),
            'NEWS_AUTHOR_NAME'              => contrexx_raw2xhtml($newsAuthorName),
            'NEWS_AUTHOR_ID'                => contrexx_raw2xhtml($newsAuthorId),
            'NEWS_CREATE_DATE'              => $this->valueFromDate($objResult->fields['date']),
            'NEWS_STARTDATE'                => $this->valueFromDate($startDate),
            'NEWS_ENDDATE'                  => $this->valueFromDate($endDate),
            'NEWS_STATUS'                   => isset($_GET['validate']) ? 'checked="checked"' : $status,
            'NEWS_TEASER_SHOW_LINK_CHECKED' => $teaserShowLink ? 'checked="checked"' : '',
            'NEWS_TEASER_IMAGE_PATH'        => contrexx_raw2xhtml($objResult->fields['teaser_image_path']),
            'NEWS_TEASER_IMAGE_THUMBNAIL_PATH' => contrexx_raw2xhtml($objResult->fields['teaser_image_thumbnail_path']),
            'NEWS_DATE'                     => date('Y-m-d H:i:s'),
            'NEWS_SUBMIT_NAME'              => isset($_GET['validate']) ? 'validate' : 'store',
            'NEWS_SUBMIT_NAME_TEXT'         => isset($_GET['validate']) ? $_ARRAYLANG['TXT_CONFIRM'] : $_ARRAYLANG['TXT_STORE'],
            'NEWS_MEDIA_BROWSER_BROWSE_BUTTON' => self::getMediaBrowserButton(array(
                                                                                'type'  => 'button',
                                                                                'id'    => 'newsMediaBrowser',
                                                                                'style' => 'display:none;'
                                                                              ),'SetUrl'),
            'NEWS_RELATED_NEWS_ENABLED_CHECKED' => !empty($objResult->fields['enable_related_news']) ? 'checked="checked"' : '',
            'NEWS_TAGS_ENABLED_CHECKED' => !empty($objResult->fields['enable_tags']) ? 'checked="checked"' : '',
            'NEWS_REDIRECT_NEW_WINDOW_CHECKED' => $objResult->fields['redirect_new_window'] ? 'checked="checked"' : ''
        ));

        if ($this->arrSettings['news_message_protection'] == '1') {
            if ($this->arrSettings['news_message_protection_restricted'] == '1') {
                $userGroupIds = $objFWUser->objUser->getAssociatedGroupIds();
            }

            if ($objResult->fields['frontend_access_id']) {
                $objFrontendGroups = $objFWUser->objGroup->getGroups(array('dynamic' => $objResult->fields['frontend_access_id']));
                $arrFrontendGroups = $objFrontendGroups ? $objFrontendGroups->getLoadedGroupIds() : array();
            } else {
                $arrFrontendGroups = array();
            }

            if ($objResult->fields['backend_access_id']) {
                $objBackendGroups = $objFWUser->objGroup->getGroups(array('dynamic' => $objResult->fields['backend_access_id']));
                $arrBackendGroups = $objBackendGroups ? $objBackendGroups->getLoadedGroupIds() : array();
            } else {
                $arrBackendGroups = array();
            }

            $readAccessGroups = '';
            $readNotAccessGroups = '';
            $modifyAccessGroups = '';
            $modifyNotAccessGroups = '';
            $objGroup = $objFWUser->objGroup->getGroups();
            if ($objGroup) {
            while (!$objGroup->EOF) {
                ${$objGroup->getType() == 'frontend' ?
                    (in_array($objGroup->getId(), $arrFrontendGroups) ? 'readAccessGroups' : 'readNotAccessGroups')
                  : (in_array($objGroup->getId(), $arrBackendGroups) ? 'modifyAccessGroups' : 'modifyNotAccessGroups')}
                  .= '<option value="'.$objGroup->getId().'"'.(!\Permission::hasAllAccess() && $this->arrSettings['news_message_protection_restricted'] == '1' && !in_array($objGroup->getId(), $userGroupIds) ? ' disabled="disabled"' : '').'>'.htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</option>';
                $objGroup->next();
                }
            }

            $this->_objTpl->setVariable(array(
                'NEWS_READ_ACCESS_NOT_ASSOCIATED_GROUPS'    => $readNotAccessGroups,
                'NEWS_READ_ACCESS_ASSOCIATED_GROUPS'        => $readAccessGroups,
                'NEWS_READ_ACCESS_ALL_CHECKED'              => $objResult->fields['frontend_access_id'] ? '' : 'checked="checked"',
                'NEWS_READ_ACCESS_SELECTED_CHECKED'         => $objResult->fields['frontend_access_id'] ? 'checked="checked"' : '',
                'NEWS_READ_ACCESS_DISPLAY'                  => $objResult->fields['frontend_access_id'] ? '' : 'none',
                'NEWS_MODIFY_ACCESS_NOT_ASSOCIATED_GROUPS'  => $modifyNotAccessGroups,
                'NEWS_MODIFY_ACCESS_ASSOCIATED_GROUPS'      => $modifyAccessGroups,
                'NEWS_MODIFY_ACCESS_ALL_CHECKED'            => $objResult->fields['backend_access_id'] ? '' : 'checked="checked"',
                'NEWS_MODIFY_ACCESS_SELECTED_CHECKED'       => $objResult->fields['backend_access_id'] ? 'checked="checked"' : '',
                'NEWS_MODIFY_ACCESS_DISPLAY'                => $objResult->fields['backend_access_id'] ? '' : 'none',
            ));

            $this->_objTpl->touchBlock('news_permission_tab');
        } else {
            $this->_objTpl->hideBlock('news_permission_tab');
        }
        //Customized News Module Improvements
        if (!empty($this->arrSettings['news_use_tags'])) {
            $this->registerTagJsCode();
            $newsTagDetails = $this->getNewsTags($id);
            $newsTags       = $newsTagDetails['tagList'];
            if ($this->_objTpl->blockExists('news_tags')) {
                foreach ($newsTags as $newsTag) {
                    $this->_objTpl->setVariable('NEWS_TAGS', contrexx_raw2xhtml($newsTag));
                    $this->_objTpl->parse('news_tags');
                }
            }
            $this->_objTpl->touchBlock('news_tags_container');
        } else {
            $this->_objTpl->hideBlock('news_tags_container');
        }
        if (!empty($this->arrSettings['use_related_news'])) {
            \ContrexxJavascript::getInstance()->setVariable(
                'noResultsMsg',
                $_ARRAYLANG['TXT_NEWS_NOT_FOUND'],
                'News/RelatedSearch'
            );
            \JS::registerJS('core_modules/News/View/Script/RelatedSearch.js');
            \JS::registerCss('core_modules/News/View/Style/RelatedSearch.css');

            // fetch related news
            $relatedNewsIds = array();
            if (isset($_POST['newsRelatedNews'])) {
                $relatedNewsIds = contrexx_input2raw($_POST['newsRelatedNews']);
            }
            try {
                $relatedNews = $this->getRelatedNews($id, $relatedNewsIds, false);
                // parse related news
                while (!$relatedNews->EOF) {
                    $relatedNewsTitle = $relatedNews->fields['newstitle'];
                    $relatedNewsTitleShort = strip_tags($relatedNewsTitle);
                    if (strlen($relatedNewsTitleShort) > 35) {
                        $relatedNewsTitleShort = substr($relatedNewsTitleShort, 0, 35) . '...';
                    }
                    $this->_objTpl->setVariable(
                        array(
                            'NEWS_RELATED_NEWS_ID'          => $relatedNews->fields['newsid'],
                            'NEWS_RELATED_NEWS_TITLE'       => contrexx_raw2xhtml($relatedNewsTitle),
                            'NEWS_RELATED_NEWS_TITLE_SHORT' => contrexx_raw2xhtml($relatedNewsTitleShort),
                        )
                    );
                    $relatedNews->MoveNext();
                    $this->_objTpl->parse('news_related_news');
                }
            } catch (NewsLibraryException $e) {
                $this->_objTpl->hideBlock('news_related_news');
            }

            $this->_objTpl->touchBlock('news_related_news_container');
        } else {
            $this->_objTpl->hideBlock('news_related_news_container');
        }

        if ($_CONFIG['newsTeasersStatus'] == '1') {
            $this->_objTpl->parse('newsTeaserOptions');
            $this->_objTpl->setVariable(array(
                'TXT_USAGE'                     => $_ARRAYLANG['TXT_USAGE'],
                'TXT_USE_ONLY_TEASER_TEXT'      => $_ARRAYLANG['TXT_USE_ONLY_TEASER_TEXT'],
                'TXT_TEASER_TEASER_BOXES'       => $_ARRAYLANG['TXT_TEASER_TEASER_BOXES'],
                'TXT_AVAILABLE_BOXES'           => $_ARRAYLANG['TXT_AVAILABLE_BOXES'],
                'TXT_SELECT_ALL'                => $_ARRAYLANG['TXT_SELECT_ALL'],
                'TXT_DESELECT_ALL'              => $_ARRAYLANG['TXT_DESELECT_ALL'],
                'TXT_ASSOCIATED_BOXES'          => $_ARRAYLANG['TXT_ASSOCIATED_BOXES'],
                'NEWS_HEADLINES_TEASERS_TXT'    => $_ARRAYLANG['TXT_HEADLINES'].' / '.$_ARRAYLANG['TXT_TEASERS'],
                'NEWS_USE_ONLY_TEASER_CHECKED'  => $objResult->fields['teaser_only'] == '1' ? 'checked="checked"' : '',
                'NEWS_TEASER_FRAMES'            => $frameIds,
                'NEWS_TEASER_ASSOCIATED_FRAMES' => $associatedFrameIds
            ));
        } else {
            $this->_objTpl->hideBlock('newsTeaserOptions');
            $this->_objTpl->setVariable('NEWS_HEADLINES_TEASERS_TXT', $_ARRAYLANG['TXT_HEADLINES']);
        }

        $categoryRelatedToNews = $this->getNewsRelCategories($id);
        $this->_objTpl->setVariable('NEWS_CAT_MENU',$this->getCategoryMenu($this->nestedSetRootId, $categoryRelatedToNews));

        $news_type_menu = '';
        if($this->arrSettings['news_use_types'] == 1) {
          $news_type_menu = "<tr class=\"row2\">\n<td nowrap=\"nowrap\">{$_ARRAYLANG['TXT_NEWS_TYPE']}</td><td><select name=\"newsType\"><option value=\"0\">{$_ARRAYLANG['TXT_NO_TYPE']}</option>".$this->getTypeMenu($newsType)."</select></td></tr>";

        }

        // Activate Comments
        $news_comment = '';
        if($this->arrSettings['news_comments_activated'] == 1) {
          $commentsChecked = ($newsComment == 1 ? 'checked="checked"' : '');

          $this->_objTpl->setVariable(array(
              'TXT_NEWS_ALLOW_COMMENTS'   => $_ARRAYLANG['TXT_NEWS_ALLOW_COMMENTS'],
              'NEWS_COMMENT_CHECKED'      => $commentsChecked,
          ));
        } else {
            $this->_objTpl->hideBlock('news_allow_comments_option');
        }

        $this->_objTpl->setVariable('NEWS_TYPE_MENU', $news_type_menu);
        $this->_objTpl->setVariable('NEWS_FORM_ACTION',(($copy) ? 'add' : 'update'));
        $this->_objTpl->setVariable('NEWS_STORED_FORM_ACTION','update');
        $this->_objTpl->setVariable('NEWS_TOP_TITLE',$_ARRAYLANG['TXT_EDIT_NEWS_CONTENT']);
    }


    /**
     * List up news comments for edit or delete
     *
     * @global    ADONewConnection
     * @global    array    langData
     * @global    array config
     * @param     integer   $newsid
     *
     */
    function comments_list()
    {
// TODO: Check and refactor if required
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $paging = '';
        $pos    = 0;
        $i      = 0;

        if (isset($_GET['pos'])) {
            $pos = intval($_GET['pos']);
        }

        $this->_objTpl->loadTemplateFile('module_news_comments_list.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_NEWS_COMMENT_LIST'];
        $newsid = intval($_REQUEST['newsId']);

        $this->_objTpl->setVariable(array(
            'TXT_NEWS_COMMENTS'               => $_ARRAYLANG['TXT_NEWS_COMMENTS'],
            'TXT_NEWS_COMMENT_DATE'           => $_ARRAYLANG['TXT_NEWS_COMMENT_DATE'],
            'TXT_NEWS_COMMENT_TITLE'          => $_ARRAYLANG['TXT_TITLE'],
            'TXT_NEWS_COMMENT_ACTION'         => $_ARRAYLANG['TXT_ACTION'],
            'TXT_NEWS_COMMENT_MESSAGE'        => $_ARRAYLANG['TXT_NEWS_COMMENT'],
            'TXT_NEWS_COMMENT_CONFIRM_DELETE' => $_ARRAYLANG['TXT_NEWS_COMMENT_CONFIRM_DELETE'],
            'TXT_ACTION_IS_IRREVERSIBLE'      => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_NEWS_COMMENT_AUTHOR'         => $_ARRAYLANG['TXT_USER'],
            'TXT_NEWS_COMMENT_SELECT_ALL'     => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_NEWS_COMMENT_REMOVE_SELECTION' => $_ARRAYLANG['TXT_REMOVE_SELECTION'],
            'TXT_NEWS_COMMENT_DELETE_MARKED'  => $_ARRAYLANG['TXT_DELETE_MARKED'],
            'TXT_NEWS_COMMENT_ACTIVATE'       => $_ARRAYLANG['TXT_NEWS_COMMENT_ACTIVATE'],
            'TXT_NEWS_COMMENT_DEACTIVATE'     => $_ARRAYLANG['TXT_NEWS_COMMENT_DEACTIVATE'],
            'TXT_NEWS_COMMENT_MARKED'         => $_ARRAYLANG['TXT_MARKED'],
            'TXT_NEWS_COMMENT_BUTTON_BACK'    => $_ARRAYLANG['TXT_NEWS_COMMENT_BACK'],
            'TXT_NEWS_COMMENTS_LIST_FOR'      => $_ARRAYLANG['TXT_NEWS_COMMENTS_LIST_FOR'],
            'CUR_NEWS_ID'                     => $newsid,
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWS_COMMENT_IMGALT_STATUS'=> $_ARRAYLANG['TXT_NEWS_COMMENTS_STATUS'],
            'TXT_NEWS_COMMENT_DELETE'       => $_ARRAYLANG['TXT_DELETE'],
            'TXT_NEWS_COMMENT_EDIT'         => $_ARRAYLANG['TXT_EDIT'],
        ));

        // get comments list
        $query = '  SELECT      id               AS commentid,
                                title            AS commenttitle,
                                date             AS commentdate,
                                poster_name      AS commentauthor,
                                text             AS commentmessage,
                                userid           AS userid,
                                is_active
                    FROM        '.DBPREFIX.'module_news_comments
                    WHERE       newsid = '.$newsid.'
                    ORDER BY    date DESC';

        /***start paging ****/
        $comResult = $objDatabase->Execute($query);
        $count = $comResult->RecordCount();

        if ($count <= $pos) {
            $dif = floor(($pos - $count) / intval($_CONFIG['corePagingLimit']));
            $pos -= ($dif + 1) * intval($_CONFIG['corePagingLimit']);
        }
        $this->_objTpl->setGlobalVariable(array(
            'POSITION'    => $pos
        ));
        if ($count > intval($_CONFIG['corePagingLimit'])) {
            $paging = getPaging($count, $pos, 'cmd=News&amp;act=comments&amp;newsId='.$newsid, 'Comments', true);
        }
        $this->_objTpl->setVariable('COMMENTS_PAGING', $paging);
        $comResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);
        /*** end paging ***/

        if ($count >= 1) {
            while (!$comResult->EOF) {
                ($i % 2) ? $class  = 'row2' : $class  = 'row1';
                $commentsautor = '';
                if ($comResult->fields['userid'] == 0) {
                    $commentsautor = contrexx_raw2xhtml($comResult->fields['commentauthor']);
                } else {
                    $objFWUser = \FWUser::getFWUserObject();
                    if ($objUser = $objFWUser->objUser->getUser($comResult->fields['userid'])) {
                        $commentsautor = '<a href="index.php?cmd=Access&amp;act=user&amp;tpl=modify&amp;id='.$comResult->fields['userid'].'" title="'.contrexx_raw2xhtml($objUser->getUsername()).'">'.contrexx_raw2xhtml($objUser->getUsername()).'</a>';
                    } else {
                        $commentsautor = $_ARRAYLANG['TXT_NEWS_COMMENT_ANONYMOUS'];
                    }
                    $strUserName .= '<input type="hidden" name="cAuthorID" value="' . $comResult->fields['userid'] . '"/>';
                }
                $commentstitle = contrexx_raw2xhtml($comResult->fields['commenttitle']);
                $commentmessage = contrexx_raw2xhtml($comResult->fields['commentmessage']);
                $commentmessage = (strlen($commentmessage) > 60) ? substr($commentmessage,0,60).' ...' : $commentmessage;

                $this->_objTpl->setVariable(array(
                    'COMMENT_CSS'    => $class,
                    'COMMENT_ID'     => intval($comResult->fields['commentid']),
                    'COMMENT_STATUS_ICON' => ($comResult->fields['is_active'] == 1) ? 'led_green' : 'led_red',
                    'COMMENT_TITLE'  => $commentstitle,
                    'COMMENT_DATE'   => date(ASCMS_DATE_FORMAT, $comResult->fields['commentdate']),
                    'COMMENT_AUTHOR' => $commentsautor,
                    'COMMENT_MESSAGE'=> $commentmessage,
                    'NEWS_ID'        => $newsid
                ));
                $this->_objTpl->parse('commentsrow');
                $i++;
                $comResult->MoveNext();
            }
            $this->_objTpl->hideBlock('nocomments');
        } else {
            $this->_objTpl->hideBlock('yescomments');
            $this->_objTpl->setVariable(array(
                'TXT_COMMENTS_NONE'    => $_ARRAYLANG['TXT_NEWS_NO_COMMENTS_FOUND']
            ));
        }
    }


    /**
     * Remove single comment or several comments from database.
     * @global    ADONewConnection
     * @global    array langData
     * @global    array config
     * @param     integer commentsId -- to remove single comment
     * @param     array selectedCommentsId -- to remove several comments
     *
     */
    function comments_delete()
    {
// TODO: Check and refactor if required
        global $objDatabase, $_ARRAYLANG;

        $commentsId = '';
        // remove single comment
        if (isset($_GET['commentsId'])) {
            $commentsId = intval($_GET['commentsId']);
            if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_comments WHERE id = ".$commentsId) !== false) {
                $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }

        // remove several comments
        if (isset($_POST['selectedCommentsId']) && is_array($_POST['selectedCommentsId'])) {
            foreach ($_POST['selectedCommentsId'] as $value) {
                if (!empty($value)) {
                    if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_comments WHERE id = ".intval($value)) !== false) {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                    } else {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    }
                }
            }
        }
    }

    /**
     * Validate edit comment form
     * (check all fields to be not empty)
     * @global    array langData
     *
     * @return    bool (true -- if all OK, false -- if errors)
     */
    function _comment_validate()
    {
// TODO: Check and refactor if required
        global $_ARRAYLANG;

        if (!isset($_REQUEST['cAuthorID']) && (!isset($_REQUEST['cAuthor']) || $_REQUEST['cAuthor'] == '')) {
            $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_COMMENT_NOT_VALID_AUTHOR'];
        } elseif (!isset($_REQUEST['cTitle']) || $_REQUEST['cTitle'] == '') {
            $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_COMMENT_NOT_VALID_TITLE'];
        } elseif (!isset($_REQUEST['cMessage']) || $_REQUEST['cMessage'] == '') {
            $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_COMMENT_NOT_VALID_MESSAGE'];
        }

        if (empty($this->strErrMessage)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Save comment
     * @global    ADONewConnection
     * @global    array langData
     *
     * @return    bool (true -- is comment saved successfuly, false -- otherwise)
     */
    function comment_save()
    {
// TODO: Check and refactor if required
        global $objDatabase, $_ARRAYLANG;

        $comment_title = contrexx_input2db($_POST['cTitle']);
        $comment_message = contrexx_input2db($_POST['cMessage']);
        $set_author = '';
        if (!isset($_POST['cAuthorID'])) {
            $set_author = ", poster_name = '" . contrexx_input2db($_POST['cAuthor']) ."'";
        }
        $commentId = intval($_REQUEST['commentsId']);

        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_comments SET
            title = '$comment_title',
            text = '$comment_message'
            $set_author
            WHERE id = $commentId"
        );

        if ($objResult === false) {
            $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_COMMENT_SAVING_ERROR'];
            return false;
        } else {
            $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_COMMENT_SAVED'];
            return true;
        }
    }

    /**
     * Prepare edit comment form
     * @global    ADONewConnection
     * @global    array langData
     * @param     integer commentsId
     * @param     integer newsId
     */
    function comment_edit()
    {
// TODO: Check and refactor if required
        global $objDatabase, $_ARRAYLANG;

        $commentID = intval($_REQUEST['commentsId']);
        $newsid = intval($_REQUEST['newsId']);
        $this->_objTpl->loadTemplateFile('module_news_comment_edit.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_NEWS_COMMENT_EDIT_TITLE'];

        $query = "SELECT title, text, poster_name, date, ip_address, userid FROM ".DBPREFIX."module_news_comments WHERE id = ".$commentID;
        $comResult = $objDatabase->Execute($query);

        $strUserName = '';
        if ($comResult->fields['userid'] == 0) {
            $strUserName = '<input type="text" name="cAuthor" value="'.(isset($_REQUEST['cAuthor']) ? contrexx_input2xhtml($_REQUEST['cAuthor']) : contrexx_raw2xhtml($comResult->fields['poster_name'])).'" maxlength="50" style="width:30%;" />';
        } else {
            $objFWUser = \FWUser::getFWUserObject();
            if ($objUser = $objFWUser->objUser->getUser($comResult->fields['userid'])) {
                $strUserName = '<a href="index.php?cmd=Access&amp;act=user&amp;tpl=modify&amp;id='.$comResult->fields['userid'].'" title="'.contrexx_raw2xhtml($objUser->getUsername()).'">'.contrexx_raw2xhtml($objUser->getUsername()).'</a>';
            } else {
                $strUserName = $_ARRAYLANG['TXT_NEWS_COMMENT_ANONYMOUS'];
            }
            $strUserName .= '<input type="hidden" name="cAuthorID" value="' . $comResult->fields['userid'] . '"/>';
        }

        $commenttitle = contrexx_raw2xhtml($comResult->fields['title']);
        $commentmessage = contrexx_raw2xhtml($comResult->fields['text']);

        $this->_objTpl->setVariable(array(
            'COMMENT_TITLE'   => isset($_REQUEST['cTitle']) ? contrexx_input2xhtml($_REQUEST['cTitle']) : $commenttitle,
            'COMMENT_MESSAGE' => isset($_REQUEST['cMessage']) ? contrexx_input2xhtml($_REQUEST['cMessage']) : $commentmessage,
            'COMMENT_AUTHOR'  => $strUserName,
            'COMMENT_DATE'    => date(ASCMS_DATE_FORMAT, $comResult->fields['date']),
            'COMMENT_IP'      => $comResult->fields['ip_address'],
            'NEWS_ID' => $newsid,
            'TXT_NEWS_COMMENT_EDIT_TITLE'  => $_ARRAYLANG['TXT_NEWS_COMMENT_EDIT_TITLE'],
            'TXT_NEWS_COMMENT_EDIT_DATE'   => $_ARRAYLANG['TXT_NEWS_COMMENT_DATE'],
            'TXT_NEWS_COMMENT_EDIT_IP'     => $_ARRAYLANG['TXT_NEWS_COMMENT_IP'],
            'TXT_NEWS_COMMENT_EDIT_AUTHOR' => $_ARRAYLANG['TXT_NEWS_COMMENT_AUTHOR'],
            'TXT_NEWS_COMMENT_TITLE'       => $_ARRAYLANG['TXT_TITLE'],
            'TXT_NEWS_COMMENT_MESSAGE'     => $_ARRAYLANG['TXT_NEWS_COMMENT'],
            'TXT_NEWS_COMMENT_SAVE'        => $_ARRAYLANG['TXT_STORE'],
        ));
    }


    /**
     * Inverts the status of the news comment with the id $intCommentId.
     *
     * @global  ADONewConnection
     * @param   integer     $intCommentId: the status of this comment will be inverted.
     */
    function invertCommentStatus($intCommentId)
    {
// TODO: Check and refactor if required
        global $objDatabase;

        $intCommentId = intval($intCommentId);
        $objCommentResult = $objDatabase->Execute(' SELECT  is_active
                                                    FROM    '.DBPREFIX.'module_news_comments
                                                    WHERE   id='.$intCommentId.'
                                                    LIMIT   1');

        $intNewStatus = $objCommentResult->fields['is_active'] == 0 ? 1 : 0;

        $objCommentResult = $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_news_comments
                                                    SET     is_active="'.$intNewStatus.'"
                                                    WHERE   id='.$intCommentId.'
                                                    LIMIT   1');
    }

    /**
    * Update news
    *
    * @global    ADONewConnection
    * @global    array
    * @global    array
    * @param     integer   $newsid
    * @return    boolean   result
    */
    function update()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        if (!$this->hasCategories()) {
            return $this->manageCategories();
        }

        if (isset($_POST['newsId'])) {
            $objFWUser = \FWUser::getFWUserObject();

            $id = intval($_POST['newsId']);
            $userId = $objFWUser->objUser->getId();
            $changelog = time();

            $date = $this->dateFromInput($_POST['newsDate']);

            $redirect   = !empty($_POST['newsRedirect']) && $_POST['newsTypeRadio'] == 'redirect' ? contrexx_strip_tags($_POST['newsRedirect']) : '';

            $source                 = \FWValidator::getUrl(contrexx_strip_tags($_POST['newsSource']));
            $url1                   = \FWValidator::getUrl(contrexx_strip_tags($_POST['newsUrl1']));
            $url2                   = \FWValidator::getUrl(contrexx_strip_tags($_POST['newsUrl2']));
            $newsPublisherName      = !empty($_POST['newsPublisherName']) ? contrexx_input2raw($_POST['newsPublisherName']) : '';
            $newsAuthorName         = !empty($_POST['newsAuthorName']) ? contrexx_input2raw($_POST['newsAuthorName']) : '';
            $newsPublisherId        = !empty($_POST['newsPublisherId']) ? contrexx_input2raw($_POST['newsPublisherId']) : '0';
            $newsAuthorId           = !empty($_POST['newsAuthorId']) ? contrexx_input2raw($_POST['newsAuthorId']) : '0';
            $newsCategories         = !empty($_POST['newsCat']) ? contrexx_input2raw($_POST['newsCat']) : array();
            $typeId                 = !empty($_POST['newsType']) ? intval($_POST['newsType']) : 0;
            $newsScheduledActive    = !empty($_POST['newsScheduled']) ? intval($_POST['newsScheduled']) : 0;
            $redirectNewWindow      = !empty($_POST['redirect_new_window']);

            $status     = empty($_POST['status']) ? $status = 0 : intval($_POST['status']);

            $newsTeaserOnly = isset($_POST['newsUseOnlyTeaser']) ? intval($_POST['newsUseOnlyTeaser']) : 0;
            $newsTeaserShowLink = isset($_POST['newsTeaserShowLink']) ? intval($_POST['newsTeaserShowLink']) : 0;

            $newsTeaserImagePath = contrexx_addslashes($_POST['newsTeaserImagePath']);
            $newsTeaserImageThumbnailPath = contrexx_addslashes($_POST['newsTeaserImageThumbnailPath']);
            $newsTeaserFrames = '';

            $newsComments     = !empty($_POST['allowComment']) ? intval($_POST['allowComment']) : 0;

            if (isset($_POST['newsTeaserFramesAsso']) && count($_POST['newsTeaserFramesAsso'])>0) {
                foreach ($_POST['newsTeaserFramesAsso'] as $frameId) {
                    intval($frameId) > 0 ? $newsTeaserFrames .= ';'.intval($frameId) : false;
                }
            }

            $startDate      = $this->dateFromInput($_POST['startDate']);
            $endDate        = $this->dateFromInput($_POST['endDate']);

            $newsFrontendAccess     = !empty($_POST['news_read_access']);
            $newsFrontendGroups     = $newsFrontendAccess && isset($_POST['news_read_access_associated_groups']) && is_array($_POST['news_read_access_associated_groups']) ? array_map('intval', $_POST['news_read_access_associated_groups']) : array();
            $newsBackendAccess     = !empty($_POST['news_modify_access']);
            $newsBackendGroups     = $newsBackendAccess && isset($_POST['news_modify_access_associated_groups']) && is_array($_POST['news_modify_access_associated_groups']) ? array_map('intval', $_POST['news_modify_access_associated_groups']) : array();

            $objResult = $objDatabase->SelectLimit('SELECT `frontend_access_id`, `backend_access_id`, `userid` FROM `'.DBPREFIX.'module_news` WHERE `id` = '.$id, 1);
            if ($objResult && $objResult->RecordCount() == 1) {
                $newsFrontendAccessId = $objResult->fields['frontend_access_id'];
                $newsBackendAccessId = $objResult->fields['backend_access_id'];
                $newsUserId = $objResult->fields['userid'];
            } else {
                $newsFrontendAccessId = 0;
                $newsBackendAccessId = 0;
                $newsUserId = 0;
            }

            if ($this->arrSettings['news_message_protection'] == '1') {
                if ($newsBackendAccessId && !\Permission::hasAllAccess() && !\Permission::checkAccess($newsBackendAccessId, 'dynamic', true) && $newsUserId != $objFWUser->objUser->getId()) {
                    return false;
                }

                if ($newsFrontendAccess) {
                    if ($newsFrontendAccessId) {
                        $objGroup = $objFWUser->objGroup->getGroups(array('dynamic' => $newsFrontendAccessId));
                        $arrFormerFrontendGroupIds = $objGroup ? $objGroup->getLoadedGroupIds() : array();

                        $arrNewGroups = array_diff($newsFrontendGroups, $arrFormerFrontendGroupIds);
                        $arrRemovedGroups = array_diff($arrFormerFrontendGroupIds, $newsFrontendGroups);

                        if ($this->arrSettings['news_message_protection_restricted'] == '1' && !\Permission::hasAllAccess()) {
                            $arrUserGroupIds = $objFWUser->objUser->getAssociatedGroupIds();

                            $arrUnknownNewGroups = array_diff($arrNewGroups, $arrUserGroupIds);
                            foreach ($arrUnknownNewGroups as $groupId) {
                                if (!in_array($groupId, $arrFormerFrontendGroupIds)) {
                                    unset($arrNewGroups[array_search($groupId, $arrNewGroups)]);
                                }
                            }

                            $arrUnknownRemovedGroups = array_diff($arrRemovedGroups, $arrUserGroupIds);
                            foreach ($arrUnknownRemovedGroups as $groupId) {
                                if (in_array($groupId, $arrFormerFrontendGroupIds)) {
                                    unset($arrRemovedGroups[array_search($groupId, $arrRemovedGroups)]);
                                }
                            }
                        }

                        if (count($arrRemovedGroups)) {
                            \Permission::removeAccess($newsFrontendAccessId, 'dynamic', $arrRemovedGroups);
                        }
                        if (count($arrNewGroups)) {
                            \Permission::setAccess($newsFrontendAccessId, 'dynamic', $arrNewGroups);
                        }
                    } else {
                        if ($this->arrSettings['news_message_protection_restricted'] == '1' && !\Permission::hasAllAccess()) {
                            $arrUserGroupIds = $objFWUser->objUser->getAssociatedGroupIds();

                            $newsFrontendGroups = array_intersect($newsFrontendGroups, $arrUserGroupIds);
                        }

                        $newsFrontendAccessId = \Permission::createNewDynamicAccessId();
                        if (count($newsFrontendGroups)) {
                            \Permission::setAccess($newsFrontendAccessId, 'dynamic', $newsFrontendGroups);
                        }
                    }
                } else {
                    if ($newsFrontendAccessId) {
                        \Permission::removeAccess($newsFrontendAccessId, 'dynamic');
                    }
                    $newsFrontendAccessId = 0;
                }

                if ($newsBackendAccess) {
                    if ($newsBackendAccessId) {
                        $objGroup = $objFWUser->objGroup->getGroups(array('dynamic' => $newsBackendAccessId));
                        $arrFormerBackendGroupIds = $objGroup ? $objGroup->getLoadedGroupIds() : array();

                        $arrNewGroups = array_diff($newsBackendGroups, $arrFormerBackendGroupIds);
                        $arrRemovedGroups = array_diff($arrFormerBackendGroupIds, $newsBackendGroups);

                        if ($this->arrSettings['news_message_protection_restricted'] == '1' && !\Permission::hasAllAccess()) {
                            $arrUserGroupIds = $objFWUser->objUser->getAssociatedGroupIds();

                            $arrUnknownNewGroups = array_diff($arrNewGroups, $arrUserGroupIds);
                            foreach ($arrUnknownNewGroups as $groupId) {
                                if (!in_array($groupId, $arrFormerBackendGroupIds)) {
                                    unset($arrNewGroups[array_search($groupId, $arrNewGroups)]);
                                }
                            }

                            $arrUnknownRemovedGroups = array_diff($arrRemovedGroups, $arrUserGroupIds);
                            foreach ($arrUnknownRemovedGroups as $groupId) {
                                if (in_array($groupId, $arrFormerBackendGroupIds)) {
                                    unset($arrRemovedGroups[array_search($groupId, $arrRemovedGroups)]);
                                }
                            }
                        }

                        if (count($arrRemovedGroups)) {
                            \Permission::removeAccess($newsBackendAccessId, 'dynamic', $arrRemovedGroups);
                        }
                        if (count($arrNewGroups)) {
                            \Permission::setAccess($newsBackendAccessId, 'dynamic', $arrNewGroups);
                        }
                    } else {
                        if ($this->arrSettings['news_message_protection_restricted'] == '1' && !\Permission::hasAllAccess()) {
                            $arrUserGroupIds = $objFWUser->objUser->getAssociatedGroupIds();

                            $newsBackendGroups = array_intersect($newsBackendGroups, $arrUserGroupIds);
                        }

                        $newsBackendAccessId = \Permission::createNewDynamicAccessId();
                        if (count($newsBackendGroups)) {
                            \Permission::setAccess($newsBackendAccessId, 'dynamic', $newsBackendGroups);
                        }
                    }
                } else {
                    if ($newsBackendAccessId) {
                        \Permission::removeAccess($newsBackendAccessId, 'dynamic');
                    }
                    $newsBackendAccessId = 0;
                }
            }

            $objFWUser->objUser->getDynamicPermissionIds(true);

            // find out original user's id
            $orig_user_sql = "
                SELECT userid
                FROM ".DBPREFIX."module_news
                WHERE id = '$id'
            ";
            $orig_user_rs = $objDatabase->Execute($orig_user_sql);
            if ($orig_user_rs == false) {
                \DBG::msg("We're in trouble! sql failure: $orig_user_sql");
            }
            else {
                $orig_userid = $orig_user_rs->fields['userid'];
            }
            $set_userid = $orig_userid ? $orig_userid : $userId;

            // $finishednewstext = $newstext.'<br>'.$_ARRAYLANG['TXT_LAST_EDIT'].': '.$date;
            $activeLanguages = isset($_POST['newsManagerLanguages']) ? $_POST['newsManagerLanguages'] : array();
            if (count(\FWLanguage::getActiveFrontendLanguages()) == 1) {
                $activeLanguages = \FWLanguage::getActiveFrontendLanguages();
            }
            $locales = array(
                'active'        => $activeLanguages,
                'title'         => $_POST['newsTitle'],
                'text'          => $_POST['news_text'],
                'teaser_text'   => isset($_POST['newsTeaserText']) ? $_POST['newsTeaserText'] : array()
            );

            if (!$this->validateNews($locales, $newsCategories)) {
                return $this->edit();
            }

            // store locales
            $localesSaving = $this->storeLocales($id, $locales);

            $categoryManipulation = $this->manipulateCategories($newsCategories, $id);

            $relatedNewsIds = array();
            if (!empty($_POST['newsRelatedNews'])) {
                $relatedNewsIds  = contrexx_input2int($_POST['newsRelatedNews']);
            }
            $enableRelatedNews = intval(!empty($_POST['newsEnableRelatedNews']));
            $relatedNewsManipulation = $this->manipulateRelatedNews($relatedNewsIds, $id);

            $newsTags = !empty($_POST['newsTags'])
                ? contrexx_input2raw($_POST['newsTags'])
                : array();
            $enableTags = !empty($_POST['enableTags'])
                ? intval($_POST['enableTags'])
                : 0;
            //Update Tags
            $tagManipulation = $this->manipulateTags($newsTags, $id);


            // Set start and end dates as NULL if newsScheduled checkbox is not checked
            if ($newsScheduledActive == 0) {
                $startDate = NULL;
                $endDate   = NULL;
            }
            $objResult = $objDatabase->Execute("UPDATE  ".DBPREFIX."module_news
                                                SET     date='".$date."',
                                                        redirect='".$redirect."',
                                                        source='".$source."',
                                                        url1='".$url1."',
                                                        url2='".$url2."',
                                                        publisher='".contrexx_raw2db($newsPublisherName)."',
                                                        publisher_id=".intval($newsPublisherId).",
                                                        author='".contrexx_raw2db($newsAuthorName)."',
                                                        author_id=".intval($newsAuthorId).",
                                                        typeid='".$typeId."',
                                                        userid = '".$set_userid."',
                                                        status = '".$status."',
                                                        ".(isset($_POST['validate']) ? "validated='1'," : "")."
                                                        startdate = ".$this->dbFromDate($startDate).",
                                                        enddate = ".$this->dbFromDate($endDate).",
                                                        frontend_access_id = '".$newsFrontendAccessId."',
                                                        backend_access_id = '".$newsBackendAccessId."',
                                                        ".($_CONFIG['newsTeasersStatus'] == '1' ? "teaser_only = '".$newsTeaserOnly."',
                                                        teaser_frames = '".$newsTeaserFrames."'," : "")."
                                                        teaser_show_link = ".$newsTeaserShowLink.",
                                                        teaser_image_path = '".$newsTeaserImagePath."',
                                                        teaser_image_thumbnail_path = '".$newsTeaserImageThumbnailPath."',
                                                        changelog = '".$changelog."',
                                                        allow_comments = '".$newsComments."',
                                                        enable_related_news='".$enableRelatedNews."',
                                                        enable_tags='".$enableTags."',
                                                        redirect_new_window='".$redirectNewWindow."'
                                                WHERE   id = '".$id."'");
           if (     $objResult === false
               ||   $localesSaving === false
               ||   $categoryManipulation === false
               ||   $relatedNewsManipulation === false
               ||   $tagManipulation === false
           ){
                $this->strErrMessage = empty($this->errMsg)
                    ? $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']
                    : implode('<br>', $this->errMsg);
           } else {
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                $cx->getEvents()->triggerEvent(
                    'clearEsiCache',
                    array(
                        'Widget',
                        $this->getNewsGlobalPlaceholderNames()
                    )
                );
               $this->createRSS();
               $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
           }
        }
        return $this->overview();
    }


    /**
     * Change status of multiple messages
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @param     integer   $newsid
     * @return    boolean   result
     */
    function changeStatus()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        //have we modified any news entry? (validated, (de)activated)
        $entryModified = false;

        if (isset($_POST['deactivate']) AND !empty($_POST['deactivate'])) {
            $status = 0;
        }
        if (isset($_POST['activate']) AND !empty($_POST['activate'])) {
            $status = 1;
        }

        //(de)activate news where ticked
        if (isset($status)) {
            if (isset($_POST['selectedNewsId']) && is_array($_POST['selectedNewsId'])) {
                foreach ($_POST['selectedNewsId'] as $value) {
                    if (!empty($value)) {
                        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_news SET status = '".$status."' WHERE id = '".intval($value)."'");
                        $entryModified = true;
                    }
                    if ($objResult === false) {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    } else {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
                        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                        $cx->getEvents()->triggerEvent(
                            'clearEsiCache',
                            array(
                                'Widget',
                                $this->getNewsGlobalPlaceholderNames()
                            )
                        );
                    }
                }
            }
        }

        //validate unvalidated news where ticks set
        if (isset($_POST['validate']) && isset($_POST['selectedUnvalidatedNewsId']) && is_array($_POST['selectedUnvalidatedNewsId'])) {
            foreach ($_POST['selectedUnvalidatedNewsId'] as $value) {
                $objDatabase->Execute("UPDATE ".DBPREFIX."module_news SET status=1, validated='1' WHERE id=".intval($value));
                $entryModified = true;
            }
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array(
                    'Widget',
                    $this->getNewsGlobalPlaceholderNames()
                )
            );
        }

        if(!$entryModified)
            $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_NOTICE_NOTHING_SELECTED'];

        $this->createRSS();
    }


    /**
     * Change status of multiple news comments
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @param     integer   $commensids
     */
    function changeCommentStatus()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        if (isset($_POST['deactivate']) && !empty($_POST['deactivate'])) {
            $status = 0;
        }
        if (isset($_POST['activate']) && !empty($_POST['activate'])) {
            $status = 1;
        }
        if (isset($status)) {
            if (isset($_POST['selectedCommentsId']) && is_array($_POST['selectedCommentsId'])) {
                foreach ($_POST['selectedCommentsId'] as $value) {
                    if (!empty($value)) {
                        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_comments SET is_active = '".$status."' WHERE id = '".intval($value)."'");
                    }
                    if ($objResult === false) {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    } else {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
                    }
                }
            }
        }
    }


    /**
     * Invert status of a single message
     * @global  ADONewConnection
     * @global  array
     * @param   integer     $intNewsId
     */
    function invertStatus($intNewsId) {
        global $objDatabase,$_ARRAYLANG;

        $intNewsId = intval($intNewsId);

        if ($intNewsId != 0) {
            $objResult = $objDatabase->Execute('SELECT  status
                                                FROM    '.DBPREFIX.'module_news
                                                WHERE   id='.$intNewsId.'
                                                LIMIT   1
                                            ');
            if ($objResult->RecordCount() == 1) {
                $intNewStatus = ($objResult->fields['status'] == 0) ? 1 : 0;
                $setDate = '';
                if ($intNewStatus == 1) {
                    $setDate = ", date = '" . time() . "', changelog = '" . time() . "' ";
                }
                $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_news
                                        SET     status="'.$intNewStatus.'"
                                        ' . $setDate . '
                                        WHERE   id='.$intNewsId.'
                                        LIMIT   1
                                    ');
                $this->createRSS();

                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                $cx->getEvents()->triggerEvent(
                    'clearEsiCache',
                    array(
                        'Widget',
                        $this->getNewsGlobalPlaceholderNames()
                    )
                );
                 $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
            }
        }
    }


    /**
     * Add or edit the news categories
     *
     * @access  private
     */
    private function manageCategories()
    {
        global $objDatabase, $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        \JS::registerJS(substr(
            $cx->getCodeBaseCoreModuleWebPath() .
                '/News/View/Script/Backend.js',
            1
        ));
        \JS::registerCSS(substr(
            $cx->getCodeBaseCoreModuleWebPath() .
                '/News/View/Style/Backend.css',
            1
        ));

        $this->_objTpl->loadTemplateFile('module_news_category.html', true, true);
        $this->pageTitle = $_ARRAYLANG['TXT_CATEGORY_MANAGER'];

        $this->_objTpl->setVariable(array(
            'TXT_ADD_NEW_CATEGORY'                       => $_ARRAYLANG['TXT_ADD_NEW_CATEGORY'],
            'TXT_NEWS_NEW_CATEGORY'                      => $_ARRAYLANG['TXT_NEWS_NEW_CATEGORY'],
            'TXT_NAME'                                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_ADD'                                    => $_ARRAYLANG['TXT_ADD'],
            'TXT_CATEGORY_LIST'                          => $_ARRAYLANG['TXT_CATEGORY_LIST'],
            'TXT_ID'                                     => $_ARRAYLANG['TXT_ID'],
            'TXT_NEWS_CATEGORY_ORDER'                    => $_ARRAYLANG['TXT_NEWS_CATEGORY_ORDER'],
            'TXT_ACTION'                                 => $_ARRAYLANG['TXT_ACTION'],
            'TXT_ACCEPT_CHANGES'                         => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_CONFIRM_DELETE_WITH_SUBENTRIES'         => $_ARRAYLANG['TXT_CONFIRM_DELETE_WITH_SUBENTRIES'],
            'TXT_ACTION_IS_IRREVERSIBLE'                 => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK' => $_ARRAYLANG['TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK'],
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_SAVE'          => $_ARRAYLANG['TXT_SAVE'],
            'TXT_EDIT'          => $_ARRAYLANG['TXT_EDIT'],
            'TXT_DELETE'        => $_ARRAYLANG['TXT_DELETE'],
            'TXT_NEWS_EXTENDED' => $_ARRAYLANG['TXT_NEWS_EXTENDED'],
            'TXT_NEWS_SWITCH_VISIBILITY' =>
                $_ARRAYLANG['TXT_NEWS_SWITCH_VISIBILITY'],
        ));

        // Add a new category
        if (isset($_POST['addCat']) && ($_POST['addCat']==true)) {
            $catName = contrexx_input2db(trim($_POST['newCategorieName']));
            $catParentId = !empty($_POST['newCategorieParentId']) ? intval($_POST['newCategorieParentId']) : $this->nestedSetRootId;

            if (empty($catName)) {
                $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_CATEGORY_ADD_ERROR_EMPTY'];
            } else {
                $status = true;

                // set new auto increment for sequence table for nested set
                // if you find another way that the sequence table does not update ID while moving node, so change this code
                $objResult = $objDatabase->SelectLimit("SELECT MAX(`catid`) AS `maxId` FROM `".DBPREFIX."module_news_categories`", 1);
                if ($objResult !== false) {
                    $objResult2 = $objDatabase->Execute("SELECT `id` FROM `" . DBPREFIX . "module_news_categories_catid`");
                    if ($objResult2 !== false && $objResult2->fields['id'] > $objResult->fields['maxId']) {
                        $objDatabase->Execute("UPDATE `" . DBPREFIX . "module_news_categories_catid` SET `id` = '" . contrexx_raw2db($objResult->fields['maxId']) . "'");
                    }
                }

                if (!$catId = $this->objNestedSet->createSubNode($catParentId, array())) {
                    $status = false;
                } else {
                    $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
                    foreach($frontendLanguages as $language) {
                        if ($objDatabase->Execute('INSERT INTO `' . DBPREFIX . 'module_news_categories_locale` (`lang_id`, `category_id`, `name`)
                                               VALUES ("'.$language['id'].'", "' . $catId . '", "' . $catName . '")') === false
                        ) {
                            $status = false;
                        }
                    }
                }

                if ($status) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                }
            }
        }

        // Change sorting
        $newSorting = isset($_POST['newsCatSorting']) ? contrexx_input2raw($_POST['newsCatSorting']) : array();
        if (is_array($newSorting) && !empty($newSorting)) {
            asort($newSorting);
            foreach($newSorting as $catId => $catSort) {
                $this->objNestedSet->moveTree($catId, $this->objNestedSet->getParent($catId)->id, NESE_MOVE_BELOW);
            }
            \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
        }

        // List all categories
        $arrCatLangData   = $this->getCategoriesData();
        $firstLevel       = 2;
        $levelSpacingLeft = 20;

        $i = 0;
        if (count($nodes = $this->objNestedSet->getSubBranch($this->nestedSetRootId, true)) > 0) {
            $nodes = $this->sortNestedSetArray($nodes);
            foreach ($nodes as $node) {
                $level    = $node['level']-$firstLevel;
                $cssStyle = (($i++ % 2) == 0) ? 'row2' : 'row1';
                $sort     = ($node['level'] == 2) ? $node['norder'] - 1 : $node['norder']; // don't count the root node

                if (count($this->objNestedSet->getParents($node['id'])) > 1) {
                    $this->_objTpl->touchBlock('categoryHasParent');
                } else {
                    $this->_objTpl->hideBlock('categoryHasParent');
                }

                $name = contrexx_raw2xhtml(
                    $arrCatLangData[$node['id']]['lang'][
                        \FWLanguage::getDefaultLangId()
                    ]
                );
                $iconClass = '';
                if (!$arrCatLangData[$node['id']]['display']) {
                    $iconClass = 'hidden';
                }
                $this->_objTpl->setVariable(array(
                    'NEWS_ROWCLASS'       => $cssStyle,
                    'NEWS_CAT_ID'         => $node['id'],
                    'NEWS_LEVEL_SPACING'  => $level*$levelSpacingLeft,
                    'NEWS_CAT_NAME'       => $name,
                    'NEWS_CAT_SORT'       => $sort,
                    'NEWS_CAT_ICON_CLASS' => $iconClass,
                ));
                $this->_objTpl->parse('newsRow');
            };
        }

        $this->_objTpl->setVariable(array(
            'NEWS_CATEGORIES' => $this->getCategoryMenu(array()),
        ));
    }

    /**
     * @param int $id
     */
    protected function modifyCategory($id = null) {
        global $objDatabase, $_ARRAYLANG;
        $manageCategoriesLink = 'index.php?cmd=News&act=newscat';

        // cast input id to integer and check whether the id is zero or not
        $id = intval($id);
        if ($id == 0) {
            \Cx\Core\Csrf\Controller\Csrf::redirect($manageCategoriesLink);
            exit;
        }

        // check whether the category exists or not
        $objResult = $objDatabase->SelectLimit("SELECT `catid`, `parent_id` FROM `".DBPREFIX."module_news_categories` WHERE `catid` = " . $id);
        if ($objResult->RecordCount() == 0) {
            \Cx\Core\Csrf\Controller\Csrf::redirect($manageCategoriesLink);
            exit;
        }

        // load template
        $this->_objTpl->loadTemplateFile('module_news_category_modify.html', true, true);
        $this->pageTitle = $_ARRAYLANG['TXT_EDIT_CATEGORY'];

        // validate form inputs and save the changes
        if(isset($_POST['submit'])) {
            if(!isset($_POST['newsCatParentId']) || $_POST['newsCatParentId'] == $id) {
            } else {
                $catParentId = intval($_POST['newsCatParentId']);
                if($catParentId == 0) {
                    $catParentId = $this->nestedSetRootId;
                }
                if($this->objNestedSet->getParent($id)->id != $catParentId) {
                    // move the node under the parent node id
                    $this->objNestedSet->moveTree($id, $catParentId, NESE_MOVE_BELOW);
                }
            }

            // write the new locale data to database
            $status = $this->storeCategoriesLocales($_POST['newsCatName']);
            if(!$status) {
                \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            } else {
                \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
            }
        }

        // get language data from categories
        $categories = $this->getCategoriesData();
        $categoryLangData = $categories[$id]['lang'];

        // get languages which are active
        $arrLanguages = \FWLanguage::getActiveFrontendLanguages();

        // parse category name list for each activated frontend language
        foreach($arrLanguages as $langId => $languageName) {
            $this->_objTpl->setVariable(array(
                'NEWS_CAT_LANG_ID' => $langId,
                'NEWS_CAT_NAME_VALUE' => contrexx_raw2xhtml($categoryLangData[$langId]),
                'NEWS_CAT_LANG_NAME' => $languageName['name'],
            ));
            $this->_objTpl->parse('category_name_list');
        }

        // get parent category from this category
        $parentCategoryNode = $this->objNestedSet->getParent($id);

        // set global variables
        $this->_objTpl->setGlobalVariable(array(
            'NEWS_CAT_ID' => $id,
            'NEWS_CAT_NAME' => $categoryLangData[FRONTEND_LANG_ID],
        ));

        // set variables
        $childrenNodes = $this->objNestedSet->getChildren($id, true);
        $childrenNodeIds = array();
        foreach($childrenNodes as $childrenNode) {
            $childrenNodeIds[] = $childrenNode['id'];
        }
        $this->_objTpl->setVariable(array(
            'NEWS_CAT_CATEGORIES' => $this->getCategoryMenu($this->nestedSetRootId, array($parentCategoryNode->id), array_merge(array($id), $childrenNodeIds)),

        ));

        // set language variables
        $this->_objTpl->setVariable(array(
            'TXT_SAVE' => $_ARRAYLANG['TXT_SAVE'],
            'TXT_NAME' => $_ARRAYLANG['TXT_NAME'],
            'TXT_EDIT_CATEGORY' => $_ARRAYLANG['TXT_EDIT_CATEGORY'],
            'TXT_NEWS_EXTENDED' => $_ARRAYLANG['TXT_NEWS_EXTENDED'],
            'TXT_NEWS_PARENT_CATEGORY' => $_ARRAYLANG['TXT_NEWS_PARENT_CATEGORY'],
            'TXT_NEWS_NEW_MAIN_CATEGORY' => $_ARRAYLANG['TXT_NEWS_NEW_MAIN_CATEGORY'],
        ));
    }


    /**
     * Delete a category with its entries and subcategories
     *
     * @access  private
     * @param   int  $catId Id of the Category
     * @return  boolean true on success, false otherwise
     */
    private function deleteCat($catId = null)
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_GET['catId']) || $catId !== null) {
            $catId = $catId == null ? intval($_GET['catId']) : $catId;
            $subcats = $this->objNestedSet->getSubBranch($catId, true);
            if (count($subcats) > 0) {
                foreach($subcats as $subcat){
                    if(!$this->deleteCat(intval($subcat['id']))){
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                        return false;
                    }
                }
            }

            if (    $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'module_news_categories_locale` WHERE `category_id`='.$catId) !== false
                &&  $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'module_news_rel_categories` WHERE `category_id`='.$catId) !== false
                &&  $this->objNestedSet->deleteNode($catId) !== false
            ) {
                $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                return true;
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                return false;
            }
        }
        return false;
    }


    /**
     * Add or edit the news types
     * @global    ADONewConnection
     * @global    array
     * @param     string     $pageContent
     */
    function manageTypes()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_news_type.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_TYPES_MANAGER'];

        $this->_objTpl->setVariable(array(
            'TXT_ADD_NEW_TYPE'                           => $_ARRAYLANG['TXT_ADD_NEW_TYPE'],
            'TXT_NAME'                                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_ADD'                                    => $_ARRAYLANG['TXT_ADD'],
            'TXT_TYPE_LIST'                              => $_ARRAYLANG['TXT_TYPE_LIST'],
            'TXT_ID'                                     => $_ARRAYLANG['TXT_ID'],
            'TXT_ACTION'                                 => $_ARRAYLANG['TXT_ACTION'],
            'TXT_ACCEPT_CHANGES'                         => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_CONFIRM_DELETE_DATA'                    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE'                 => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK' => $_ARRAYLANG['TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK'],
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_DELETE'            => $_ARRAYLANG['TXT_DELETE'],
            'TXT_NEWS_EXTENDED'     => $_ARRAYLANG['TXT_NEWS_EXTENDED']
        ));

        // Add a new type
        if (isset($_POST['addType']) && ($_POST['addType']==true)) {
            $typeName = contrexx_input2db(trim($_POST['newTypeName']));

            if(empty($typeName)){
                $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_TYPE_ADD_ERROR_EMPTY'];
            }
            else {
                $status = true;
                if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_types () VALUES ()") === false) {
                    $status = false;
                } else {
                    $typeId = $objDatabase->Insert_ID();
                    $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
                    foreach($frontendLanguages as $language) {
                        if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_types_locale
                                                           (lang_id, type_id, name)
                                                           VALUES ('" . $language['id'] . "', '$typeId', '$typeName')") === false) {
                            $status = false;
                        }
                    }
                }
                if ($status) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                }
            }
        }

        // Modify a new type
        if (isset($_POST['modType']) && ($_POST['modType']==true)) {
            if ($this->storeTypesLocales($_POST['newsTypeName'])) {
                $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }

        $objResult = $objDatabase->Execute("SELECT type_id
                      FROM ".DBPREFIX."module_news_types_locale
                  GROUP BY type_id
                  ORDER BY type_id asc");

        $arrLanguages = \FWLanguage::getLanguageArray();
        $typeLangData = $this->getTypesLangData();

        $i=0;
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $cssStyle = (($i++ % 2) == 0) ? 'row2' : 'row1';
                foreach ($arrLanguages as $langId => $arrLanguage) {
                    $this->_objTpl->setVariable(array(
                        'NEWS_TYPE_LANG_NAME'   => contrexx_raw2xhtml($arrLanguage['name']),
                        'NEWS_TYPE_NAME_VALUE'  => contrexx_raw2xhtml($typeLangData[$objResult->fields['type_id']][$langId]),
                        'NEWS_TYPE_LANG_ID'     => $langId,
                        'NEWS_TYPE_ID'          => $objResult->fields['type_id'],
                    ));
                    $this->_objTpl->parse('type_name_list');
                }

                $this->_objTpl->setVariable(array(
                    'NEWS_ROWCLASS' => $cssStyle,
                    'NEWS_TYPE_ID'   => $objResult->fields['type_id'],
                    'NEWS_TYPE_NAME' => contrexx_raw2xhtml($typeLangData[$objResult->fields['type_id']][\FWLanguage::getDefaultLangId()])
                ));
                $this->_objTpl->parse('newsRow');

                $objResult->MoveNext();
            };
        }
    }


    /**
     * Delete the news types
     * @global    ADONewConnection
     * @global    array
     * @param     string     $pageContent
     */
    function deleteType()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_GET['typeId'])) {
            $typeId=intval($_GET['typeId']);
            $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_news WHERE typeid=".$typeId);

            if ($objResult !== false) {
                if (!$objResult->EOF) {
                     $this->strErrMessage = $_ARRAYLANG['TXT_TYPE_NOT_DELETED_BECAUSE_IN_USE'];
                }
                else {
                    if ($objDatabase->Execute(
                                             "DELETE tblC, tblL
                                              FROM ".DBPREFIX."module_news_types AS tblC
                                              INNER JOIN ".DBPREFIX."module_news_types_locale AS tblL ON tblL.type_id=tblC.typeid
                                              WHERE tblC.typeid=".$typeId
                                              ) !== false
                    ) {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                    } else {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    }
                }
            }
        }
    }


    /**
     * Create the RSS-Feed
     */
    function createRSS()
    {
        global $_CONFIG, $objDatabase, $_FRONTEND_LANGID;


        // languages
        $arrLanguages = \FWLanguage::getLanguageArray();
        if (!count($arrLanguages)) {
            return;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $feedPath = $cx->getWebsiteFeedPath();

        if (!$this->arrSettings['news_feed_status']) {
            foreach ($arrLanguages as $LangId => $arrLanguage) {
                if ($arrLanguage['frontend'] == 1) {
                    $rssFeedPaths = array(
                        $feedPath . '/news_'.\FWLanguage::getLanguageParameter($LangId, 'lang').'.xml',
                        $feedPath . '/news_headlines_'.\FWLanguage::getLanguageParameter($LangId, 'lang').'.xml',
                        $feedPath . '/news_'.\FWLanguage::getLanguageParameter($LangId, 'lang').'.js',
                    );
                    foreach ($rssFeedPaths as $rssFeedPath) {
                        if (!file_exists($rssFeedPath)) {
                            continue;
                        }
                        unlink($rssFeedPath);
                    }
                }
            }
            return;
        }

        // TODO: setting the proper protocol should be done in Url::fromPage()
        $protocol = '';
        $config = \Env::get('config');
        if ($config['forceProtocolFrontend'] != 'none') {
            $protocol = $config['forceProtocolFrontend'];
        }

        $categoryDetails = $this->getCategoryLocale(null, array_keys($arrLanguages));
        foreach ($arrLanguages as $LangId => $arrLanguage) {
            $objRSSWriter = new \RSSWriter();

            $query = "
                SELECT      tblNews.id,
                            tblNews.date,
                            tblNews.redirect,
                            tblNews.source,
                            tblNews.teaser_frames AS teaser_frames,
                            tblLocale.lang_id,
                            tblLocale.title,
                            tblLocale.text,
                            tblLocale.teaser_text
                FROM        ".DBPREFIX."module_news AS tblNews
                INNER JOIN  ".DBPREFIX."module_news_locale AS tblLocale ON tblLocale.news_id = tblNews.id
                WHERE       tblNews.status=1
                    AND     tblLocale.is_active = 1
                    AND     tblLocale.lang_id = " . $LangId . "
                    AND     (tblNews.startdate <= '".date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME)."' OR tblNews.startdate = '0000-00-00 00:00:00')
                    AND     (tblNews.enddate >= '".date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME)."' OR tblNews.enddate = '0000-00-00 00:00:00')"
                    .($this->arrSettings['news_message_protection'] == '1' ? " AND tblNews.frontend_access_id=0 " : '')
                            ." ORDER BY tblNews.date DESC";

            $arrNews = array();
            if (($objResult = $objDatabase->SelectLimit($query, 20)) !== false && $objResult->RecordCount() > 0) {
                while (!$objResult->EOF) {
                    if (empty($objRSSWriter->channelLastBuildDate)) {
                        $objRSSWriter->channelLastBuildDate = date('r', $objResult->fields['date']);
                    }
                    $teaserText = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $objResult->fields['teaser_text']);
                    $text = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $objResult->fields['text']);
                    $redirect = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $objResult->fields['redirect']);
                    \LinkGenerator::parseTemplate($teaserText, true);
                    \LinkGenerator::parseTemplate($text, true);
                    \LinkGenerator::parseTemplate($redirect, true);
                    $arrNews[$objResult->fields['id']] = array(
                        'date'          => $objResult->fields['date'],
                        'title'         => $objResult->fields['title'],
                        'text'          => empty($redirect) ? (!empty($teaserText) ? nl2br($teaserText).'<br /><br />' : '').$text : (!empty($teaserText) ? nl2br($teaserText) : ''),
                        'redirect'      => $redirect,
                        'source'        => $objResult->fields['source'],
                        'teaser_frames' => explode(';', $objResult->fields['teaser_frames']),
                        'categoryIds'    => $this->getNewsRelCategories($objResult->fields['id'])
                    );
                    $objResult->MoveNext();
                }
            } else {
                continue;
            }

            $objRSSWriter->characterEncoding = CONTREXX_CHARSET;
            $objRSSWriter->channelTitle = contrexx_raw2xml($this->arrSettings['news_feed_title'][$LangId]);
            $objRSSWriter->channelDescription = contrexx_raw2xml($this->arrSettings['news_feed_description'][$LangId]);
            $objRSSWriter->channelLink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                'News',
                '',
                $LangId,
                array(),
                $protocol
            )->toString();
            $objRSSWriter->channelLanguage = \FWLanguage::getLanguageParameter($LangId, 'lang');
            $objRSSWriter->channelCopyright = 'Copyright '.date('Y').', http://'.$_CONFIG['domainUrl'];

            if (!empty($this->arrSettings['news_feed_image'])) {
                $channelImageUrl = \Cx\Core\Routing\Url::fromDocumentRoot(
                    array(),
                    '',
                    $protocol
                );
                $channelImageUrl->setMode('backend');
                $channelImageUrl->setPath(substr(
                    $this->arrSettings['news_feed_image'],
                    strlen(
                        \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath()
                    ) + 1
                ));
                $objRSSWriter->channelImageUrl = $channelImageUrl->toString(true);
                $objRSSWriter->channelImageTitle = $objRSSWriter->channelTitle;
                $objRSSWriter->channelImageLink = $objRSSWriter->channelLink;
            }

            $objRSSWriter->channelWebMaster = $_CONFIG['coreAdminEmail']
                . ' (' . $_CONFIG['coreAdminName'] . ')';
            // create rss feed
            $objRSSWriter->xmlDocumentPath = $feedPath . '/news_'.\FWLanguage::getLanguageParameter($LangId, 'lang').'.xml';
            foreach ($arrNews as $newsId => $arrNewsItem) {
                list($cmdDetail, $categories) = $this->getRssNewsLinks($LangId, $arrNewsItem['categoryIds'], $categoryDetails[$LangId]);

                $itemUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
                    'News',
                    $cmdDetail,
                    $LangId,
                    array(
                        'newsid' => $newsId,
                    ),
                    $protocol
                );
                if (isset($arrNewsItem['teaser_frames'][0])) {
                    $itemUrl->setParam('teaserId', $arrNewsItem['teaser_frames'][0]);
                }

                if (empty($arrNewsItem['redirect'])) {
                    $link = contrexx_raw2xml($itemUrl->toString());
                } else {
                    $link = contrexx_raw2xml($arrNewsItem['redirect']);
                }

                $objRSSWriter->addItem(
                    contrexx_raw2xml($arrNewsItem['title']),
                    $link,
                    contrexx_raw2xml($arrNewsItem['text']),
                    '',
                    $categories,
                    '',
                    '',
                    array('guid' => contrexx_raw2xml($itemUrl->toString())),
                    $arrNewsItem['date'],
                    array('url' => contrexx_raw2xml($arrNewsItem['source']), 'title' => contrexx_raw2xml($arrNewsItem['title']))
                );
            }
            $objRSSWriter->write();

            // create headlines rss feed
            $objRSSWriter->removeItems();
            $objRSSWriter->xmlDocumentPath = $feedPath . '/news_headlines_'.\FWLanguage::getLanguageParameter($LangId, 'lang').'.xml';
            foreach ($arrNews as $newsId => $arrNewsItem) {
                list($cmdDetail, $categories) = $this->getRssNewsLinks($LangId, $arrNewsItem['categoryIds'], $categoryDetails[$LangId]);

                $itemUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
                    'News',
                    $cmdDetail,
                    $LangId,
                    array(
                        'newsid' => $newsId,
                    ),
                    $protocol
                );
                if (isset($arrNewsItem['teaser_frames'][0])) {
                    $itemUrl->setParam('teaserId', $arrNewsItem['teaser_frames'][0]);
                }

                if (empty($arrNewsItem['redirect'])) {
                    $link = contrexx_raw2xml($itemUrl->toString());
                } else {
                    $link = contrexx_raw2xml($arrNewsItem['redirect']);
                }

                $objRSSWriter->addItem(
                    contrexx_raw2xml($arrNewsItem['title']),
                    $link,
                    '',
                    '',
                    $categories,
                    '',
                    '',
                    array('guid' => contrexx_raw2xml($itemUrl->toString())),
                    $arrNewsItem['date']
                );
            }
            $objRSSWriter->write();

            $objRSSWriter->feedType = 'js';
            $objRSSWriter->xmlDocumentPath = $feedPath . '/news_'.\FWLanguage::getLanguageParameter($LangId, 'lang').'.js';
            $objRSSWriter->write();

            if (count($objRSSWriter->arrErrorMsg) > 0) {
                $this->strErrMessage .= implode('<br />', $objRSSWriter->arrErrorMsg);
            }
            if (count($objRSSWriter->arrWarningMsg) > 0) {
                $this->strErrMessage .= implode('<br />', $objRSSWriter->arrWarningMsg);
            }
        }
    }

    /**
     * Get the RSS News links
     *
     * @param integer $langId
     * @param array   $newsCategoryIds list of category Ids
     *
     * @global array $_CONFIG
     *
     */
    public function getRssNewsLinks($langId, $newsCategoryIds, $categoryDetails)
    {
        global $_CONFIG;

        $categories = array();
        $cmdDetail = '';
        foreach ($newsCategoryIds as $newsCategoryId) {
            $currentCmdDetail = $this->findCmdById('details', array($newsCategoryId));
            $cmdDetail = !empty($currentCmdDetail) ? $currentCmdDetail : $cmdDetail;
            $cmdOverview = $this->findCmdById('', array($newsCategoryId));

            $overviewUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
                'News',
                $cmdOverview,
                $langId,
                array(
                    'category' => $newsCategoryId,
                )
            );
            $categories[] = array(
                'domain' => contrexx_raw2xml($overviewUrl->toString()),
                'title' => contrexx_raw2xml($categoryDetails[$newsCategoryId])
            );
        }
        return array($cmdDetail, $categories);
    }

    /**
     * Save the news settings
     * @access private
     * @global ADONewConnection
     * @global array
     * @global array
     * @see createRSS()
     */
    function _saveSettings()
    {
        global $objDatabase, $_CONFIG, $_ARRAYLANG;

        // Store settings
        if (isset($_GET['act']) && $_GET['act'] == 'settings' && isset($_POST['store'])) {
            // save multilanguage news_feed_title and news_feed_description
            $this->storeFeedLocales('news_feed_title', $_POST['newsFeedTitle']);
            $this->storeFeedLocales('news_feed_description', $_POST['newsFeedDescription']);
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings
                              SET value='".intval(!empty($_POST['newsFeedStatus']))."'
                            WHERE name = 'news_feed_status'");

            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings
                              SET value='".contrexx_input2db($_POST['newsFeedImage'])."'
                            WHERE name='news_feed_image'");

            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings
                              SET value='".intval($_POST['headlinesLimit'])."'
                            WHERE name = 'news_headlines_limit'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".intval($_POST['recentNewsMessageLimit'])."' WHERE name = 'recent_news_message_limit'");
            // Notify-user. 0 = disabled.
            $this->_store_settings_item('news_notify_user', intval($_POST['newsNotifySelectedUser']));
            // Notify-Group. 0 = disabled.
            $this->_store_settings_item('news_notify_group', intval($_POST['newsNotifySelectedGroup']));
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='1' WHERE name = 'news_settings_activated'");
            $submitNews = isset($_POST['newsSubmitNews']) ? intval($_POST['newsSubmitNews']) : 0;
            $submitNewsCommunity = isset($_POST['newsSubmitOnlyCommunity']) ? intval($_POST['newsSubmitOnlyCommunity']) : 0;
            $activateSubmittedNews = isset($_POST['newsActivateSubmittedNews']) ? intval($_POST['newsActivateSubmittedNews']) : 0;
            $newsCommentsAllow = isset($_POST['newsCommentsAllow']) ? intval($_POST['newsCommentsAllow']) : 0;
            $newsCommentsAllowAnonymous = isset($_POST['newsCommentsAllowAnonymous']) ? intval($_POST['newsCommentsAllowAnonymous']) : 0;
            $newsCommentsAutoActivate = isset($_POST['newsCommentsAutoActivate']) ? intval($_POST['newsCommentsAutoActivate']) : 0;
            $newsCommentsNotification = isset($_POST['newsCommentsNotification']) ? intval($_POST['newsCommentsNotification']) : 0;
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$submitNews."' WHERE name='news_submit_news'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$submitNewsCommunity."' WHERE name='news_submit_only_community'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$activateSubmittedNews."' WHERE name='news_activate_submitted_news'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsMessageProtection'])."' WHERE name='news_message_protection'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsMessageProtectionRestricted'])."' WHERE name='news_message_protection_restricted'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$newsCommentsAllow."' WHERE name='news_comments_activated'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$newsCommentsAllowAnonymous."' WHERE name='news_comments_anonymous'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$newsCommentsAutoActivate."' WHERE name='news_comments_autoactivate'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$newsCommentsNotification."' WHERE name='news_comments_notification'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".(!empty($_POST['newsCommentsTimeout']) ? abs(intval($_POST['newsCommentsTimeout'])) : 30)."' WHERE name='news_comments_timeout'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsUseTop'])."' WHERE name='news_use_top'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsUseTeaserText'])."' WHERE name = 'news_use_teaser_text'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsUseTags'])."' WHERE name = 'news_use_tags'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['useRelatedNews'])."' WHERE name = 'use_related_news'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsUsePreviousNextLink'])."' WHERE name = 'use_previous_next_news_link'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsUseTypes'])."' WHERE name = 'news_use_types'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsUseTop'])."' WHERE name='news_use_top'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".(!empty($_POST['newsTopDays']) ? intval($_POST['newsTopDays']) : 10)."' WHERE name = 'news_top_days'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".(!empty($_POST['newsTopLimit']) ? intval($_POST['newsTopLimit']) : 10)."' WHERE name = 'news_top_limit'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsUseThumbnails'])."' WHERE name = 'use_thumbnails'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".!empty($_POST['newsLoginRedirect'])."' WHERE name = 'login_redirect'");

            $newsFilterPublisher =  isset($_POST['newsFilterPublisher']) ? intval($_POST['newsFilterPublisher']) : 0;
            $newsFilterAuthor    =  isset($_POST['newsFilterAuthor']) ? intval($_POST['newsFilterAuthor']) : 0;

            $assignedPublisherGroups =  (isset($_POST['newsAssignedPublisherGroups']) && $newsFilterPublisher) ? implode(',', contrexx_input2db($_POST['newsAssignedPublisherGroups'])) : 0;
            $assignedAuthorGroups    =  (isset($_POST['newsAssignedAuthorGroups']) && $newsFilterAuthor) ? implode(',', contrexx_input2db($_POST['newsAssignedAuthorGroups'])) : 0;

            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$assignedPublisherGroups."' WHERE name = 'news_assigned_publisher_groups'");
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='".$assignedAuthorGroups."' WHERE name = 'news_assigned_author_groups'");

            // save default teasers
            $defaultTeasers = array();
            if (isset($_POST['newsDefaultTeaserSelected'])) {
                foreach ($_POST['newsDefaultTeaserSelected'] as $key => $value) {
                    if (!empty($value)) {
                        $defaultTeasers[] = intval($key);
                    }
                }
            }
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_news_settings SET value='" . implode(";", $defaultTeasers) . "' WHERE name='news_default_teasers'");
            \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
            $newsUseTeasers = isset($_POST['newsUseTeasers']) ? intval($_POST['newsUseTeasers']) : 0;
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('newsTeasersStatus')) {
                \Cx\Core\Setting\Controller\Setting::add('newsTeasersStatus', $newsUseTeasers, 1, \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('newsTeasersStatus', $newsUseTeasers );
                \Cx\Core\Setting\Controller\Setting::update('newsTeasersStatus');
            }
            $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_SETTINGS_SAVED'];
            $this->getSettings();
            $this->createRSS();
        }
    }


    function _store_settings_item($name_in_db, $value)
    {
        global $objDatabase;

        $objDatabase->Execute("
            UPDATE ".DBPREFIX."module_news_settings
            SET    value = '$value'
            WHERE  name  = '$name_in_db';"
        );
        if ($objDatabase->Affected_Rows() == 0) {
            $objDatabase->Execute("
                DELETE FROM ".DBPREFIX."module_news_settings
                WHERE name = '$name_in_db';"
            );
            $objDatabase->Execute("
                INSERT INTO ".DBPREFIX."module_news_settings (name,          value)
                VALUES                                       ('$name_in_db', '$value');"
            );
        }
    }

    /**
     * Loads subnavbar level 2
     *
     * @access  private
     * @global  array   $_CORELANG
     */
    function settings()
    {
        global $_CORELANG, $_ARRAYLANG, $_CONFIG;

        $this->pageTitle = $_CORELANG['TXT_CORE_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_news_settings.html', true, true);
        $this->_objTpl->setVariable(array(
            'TXT_CORE_GENERAL'          => $_CORELANG['TXT_CORE_GENERAL'],
            'TXT_CORE_PLACEHOLDERS'     => $_CORELANG['TXT_CORE_PLACEHOLDERS'],
            'TXT_NEWS_NEWSTICKER'       => $_ARRAYLANG['TXT_NEWS_NEWSTICKER'],
            'TXT_NEWS_CREATE_TICKER'    => $_ARRAYLANG['TXT_NEWS_CREATE_TICKER'],
            'TXT_TEASER_TEASER_BOXES'   => $_ARRAYLANG['TXT_TEASER_TEASER_BOXES'],
            'TXT_TEASER_BOX_TEMPLATES'  => $_ARRAYLANG['TXT_TEASER_BOX_TEMPLATES'],
        ));
        if ($_CONFIG['newsTeasersStatus'] != '1') {
            $this->_objTpl->hideBlock('module_news_teaser');
        }

        switch (!empty($_GET['tpl']) ? $_GET['tpl'] : '') {
            case 'general':
                $this->settingsGeneral();
                break;
            case 'placeholders':
                $this->settingsPlaceholders();
                break;
            case 'ticker':
                $this->_ticker();
                break;
            case 'addticker':
                $_REQUEST['tpl2'] = 'modify';
                $this->_ticker();
                break;
            case 'teasers':
                $this->_teasers();
                break;
            case 'teasertemplates':
                $_REQUEST['tpl2'] = 'frameTemplates';
                $this->_teasers();
                break;
            default:
                $this->settingsGeneral();
                break;
        }
    }

    /**
     * Loads and saves general settings
     *
     * @access  private
     * @global  array   $_ARRAYLANG
     * @global  array   $_CORELANG
     * @global  array   $_CONFIG
     * @global  object  $objDatabase
     */
    private function settingsGeneral()
    {
        global $objDatabase, $_CORELANG, $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->addBlockfile('NEWS_SETTINGS_CONTENT', 'settings_content', 'module_news_settings_general.html');
        // Show settings
        $langShort = \FWLanguage::getLanguageParameter($this->langId, 'lang');
        if ($langShort) {
            $newsFeedPath =  'http://'.$_SERVER['SERVER_NAME'].ASCMS_FEED_WEB_PATH.'/news_headlines_'.$langShort.'.xml';
        }
        if (intval($this->arrSettings['news_feed_status'])==1) {
            $status = 'checked="checked"';
            $icon = "<a href='".$newsFeedPath."' target='_blank' title='".$newsFeedPath."'><img src='".ASCMS_CORE_MODULE_WEB_PATH."/News/View/Media/rss.gif' border='0' alt='".$newsFeedPath."' /></a>";
        } else {
            $status ='';
            $icon ='';
        }
        // set language vars
        $arrLanguages = \FWLanguage::getLanguageArray();
        $newsFeedTitle = '';
        $newsFeedDescription = '';
        foreach ($arrLanguages as $langId => $arrLanguage) {
            if ($arrLanguage['is_default'] == 'true') {
                $newsFeedTitle = $this->arrSettings['news_feed_title'][$langId];
                $newsFeedDescription = $this->arrSettings['news_feed_description'][$langId];
            }
            $this->_objTpl->setVariable(array(
                'NEWS_FEED_LANG_NAME'   => contrexx_raw2xhtml($arrLanguage['name']),
                'NEWS_FEED_TITLE_VALUE'  => contrexx_raw2xhtml($this->arrSettings['news_feed_title'][$langId]),
                'NEWS_FEED_TITLE_LANG_ID'     => $langId
            ));
            $this->_objTpl->parse('news_feed_title_list');
            $this->_objTpl->setVariable(array(
                'NEWS_FEED_LANG_NAME'   => contrexx_raw2xhtml($arrLanguage['name']),
                'NEWS_FEED_DESCRIPTION_VALUE'  => contrexx_raw2xhtml($this->arrSettings['news_feed_description'][$langId]),
                'NEWS_FEED_DESCRIPTION_LANG_ID'     => $langId
            ));
            $this->_objTpl->parse('news_feed_description_list');
        }

        $assignedAuthorGroups = '';
        $existingAuthorGroups = '';
        $assignedPublisherGroups = '';
        $existingPublisherGroups = '';
        $availableUserGroups = $this->_getAllUserGroups();
        $arrAssignedAuthorGroups = explode(',',$this->arrSettings['news_assigned_author_groups']);
        $arrAssignedPublisherGroups = explode(',',$this->arrSettings['news_assigned_publisher_groups']);
        foreach ($availableUserGroups as $id => $name) {
            if (in_array($id, $arrAssignedAuthorGroups)) {
                $assignedAuthorGroups .= '<option value="'.$id.'">'.$name."</option>\n";
            } else {
                $existingAuthorGroups .= '<option value="'.$id.'">'.$name."</option>\n";
            }
        }
        foreach ($availableUserGroups as $id => $name) {
            if (in_array($id, $arrAssignedPublisherGroups)) {
                $assignedPublisherGroups .= '<option value="'.$id.'">'.$name."</option>\n";
            } else {
                $existingPublisherGroups .= '<option value="'.$id.'">'.$name."</option>\n";
            }
        }

        $this->_objTpl->setVariable(array(
            'NEWS_FEED_TITLE'                       => contrexx_raw2xhtml($newsFeedTitle),
            'NEWS_FEED_STATUS'                      => $status,
            'NEWS_FEED_ICON'                        => $icon,
            'NEWS_FEED_DESCRIPTION'                 => contrexx_raw2xhtml($newsFeedDescription),
            'NEWS_FEED_IMAGE'                       => contrexx_raw2xhtml($this->arrSettings['news_feed_image']),
            'NEWS_HEADLINES_LIMIT'                  =>(intval($this->arrSettings['news_headlines_limit'])),
            'NEWS_RECENT_MESSAGES_LIMIT'            => (intval($this->arrSettings['recent_news_message_limit'])),
            'NEWS_FEED_PATH'                        => $newsFeedPath,
            'NEWS_SUBMIT_NEWS'                      => $this->arrSettings['news_submit_news'] == '1' ? 'checked="checked"' : '',
            'NEWS_SUBMIT_NEWS_CONFIGURATION_DISPLAY'=> $this->arrSettings['news_submit_news'] == '1' ? '' : 'none',
            'NEWS_SUBMIT_ONLY_COMMUNITY'            => $this->arrSettings['news_submit_only_community'] == '1' ? 'checked="checked"' : '',
            'NEWS_ACTIVATE_SUBMITTED_NEWS'          => $this->arrSettings['news_activate_submitted_news'] == '1' ? 'checked="checked"' : '',
            'NEWS_USE_TEASERS_CHECKED'              => $_CONFIG['newsTeasersStatus'] == '1' ? 'checked="checked"' : '',
            'NEWS_USE_TEASERS_CONFIGURATION_DISPLAY'=> $_CONFIG['newsTeasersStatus'] == '1' ? '' : 'none',
            'NEWS_USE_TEASER_TEXT_CHECKED'          => $this->arrSettings['news_use_teaser_text'] == '1' ? 'checked="checked"' : '',
            'NEWS_USE_TYPES_CHECKED'                => $this->arrSettings['news_use_types'] == '1' ? 'checked="checked"' : '',
            'NEWS_USE_RELATED_NEWS_CHECKED'         => $this->arrSettings['use_related_news'] == '1' ? 'checked="checked"' : '',
            'NEWS_USE_PREVIOUS_NEXT_LINK_CHECKED'   => $this->arrSettings['use_previous_next_news_link'] == '1' ? 'checked="checked"' : '',
            'NEWS_USE_TAGS_CHECKED'                 => $this->arrSettings['news_use_tags'] == '1' ? 'checked="checked"' : '',
            'NEWS_USE_THUMBNAILS'                   => $this->arrSettings['use_thumbnails'] == '1' ? 'checked="checked"' : '',
            'NEWS_LOGIN_REDIRECT'                   => $this->arrSettings['login_redirect'] == '1' ? 'checked="checked"' : '',
            'TXT_STORE'                             => $_ARRAYLANG['TXT_STORE'],
            'TXT_NAME'                              => $_ARRAYLANG['TXT_NAME'],
            'TXT_VALUE'                             => $_ARRAYLANG['TXT_VALUE'],
            'TXT_NEWS_SETTINGS'                     => $_ARRAYLANG['TXT_NEWS_SETTINGS'],
            'TXT_NEWS_FEED_STATUS'                  => $_ARRAYLANG['TXT_NEWS_FEED_STATUS'],
            'TXT_NEWS_FEED_TITLE'                   => $_ARRAYLANG['TXT_NEWS_FEED_TITLE'],
            'TXT_NEWS_FEED_DESCRIPTION'             => $_ARRAYLANG['TXT_NEWS_FEED_DESCRIPTION'],
            'TXT_NEWS_FEED_IMAGE'                   => $_ARRAYLANG['TXT_NEWS_FEED_IMAGE'],
            'TXT_BROWSE'                            => $_ARRAYLANG['TXT_BROWSE'],
            'TXT_NEWS_HEADLINES_LIMIT'              => $_ARRAYLANG['TXT_NEWS_HEADLINES_LIMIT'],
            'TXT_NEWS_SETTINGS_SAVED'               => $_ARRAYLANG['TXT_NEWS_SETTINGS_SAVED'],
            'TXT_SUBMIT_NEWS'                       => $_ARRAYLANG['TXT_SUBMIT_NEWS'],
            'TXT_ALLOW_USERS_SUBMIT_NEWS'           => $_ARRAYLANG['TXT_ALLOW_USERS_SUBMIT_NEWS'],
            'TXT_ALLOW_ONLY_MEMBERS_SUBMIT_NEWS'    => $_ARRAYLANG['TXT_ALLOW_ONLY_MEMBERS_SUBMIT_NEWS'],
            'TXT_AUTO_ACTIVATE_SUBMITTED_NEWS'      => $_ARRAYLANG['TXT_AUTO_ACTIVATE_SUBMITTED_NEWS'],
            'TXT_USE_TEASERS'                       => $_ARRAYLANG['TXT_USE_TEASERS'],
            'TXT_USE_TEASER_TEXT'                   => $_ARRAYLANG['TXT_USE_TEASER_TEXT'],
            'TXT_NEWS_SETTINGS_USE_TYPES'           => $_ARRAYLANG['TXT_NEWS_SETTINGS_USE_TYPES'],
            'TXT_NEWS_USE_TAGS'                     => $_ARRAYLANG['TXT_NEWS_USE_TAGS'],
            'TXT_NEWS_SETTINGS_USE_RELATED_NEWS'    => $_ARRAYLANG['TXT_NEWS_SETTINGS_USE_RELATED_NEWS'],
            'TXT_NEWS_SETTINGS_USE_THUMBNAILS'      => $_ARRAYLANG['TXT_NEWS_SETTINGS_USE_THUMBNAILS'],
            'TXT_NEWS_SETTINGS_USE_COMMENTS'        => $_ARRAYLANG['TXT_NEWS_SETTINGS_USE_COMMENTS'],
            'TXT_NOTIFY_GROUP'                      => $_ARRAYLANG['TXT_NOTIFY_GROUP'],
            'TXT_NOTIFY_USER'                       => $_ARRAYLANG['TXT_NOTIFY_USER'],
            'TXT_DEACTIVATE'                        => $_ARRAYLANG['TXT_DEACTIVATE'],
            'NEWS_NOTIFY_GROUP_LIST'                => $this->_generate_notify_group_list(),
            'NEWS_NOTIFY_USER_LIST'                 => $this->_generate_notify_user_list(),
            'TXT_NEWS_PROTECTION'                   => $_ARRAYLANG['TXT_NEWS_PROTECTION'],
            'TXT_NEWS_ACTIVE'                       => $_ARRAYLANG['TXT_NEWS_ACTIVE'],
            'TXT_NEWS_MESSAGE_PROTECTION_RESTRICTED'    => $_ARRAYLANG['TXT_NEWS_MESSAGE_PROTECTION_RESTRICTED'],
            'NEWS_MESSAGE_PROTECTION_CHECKED'       => $this->arrSettings['news_message_protection'] == '1' ? 'checked="checked"' : '',
            'NEWS_MESSAGE_PROTECTION_RESTRICTED_DISPLAY'    => $this->arrSettings['news_message_protection'] == '1' ? '' : 'none',
            'NEWS_MESSAGE_PROTECTION_RESTRICTED_CHECKED'    => $this->arrSettings['news_message_protection_restricted'] == '1' ? 'checked="checked"' : '',
            'NEWS_SETTINGS_COMMENTS_STATUS_CHECKED'       =>  ($this->arrSettings['news_comments_activated'] == '1') ? 'checked="checked"' : '',
            'NEWS_COMMENTS_STATUS_STYLE'                    => $this->arrSettings['news_comments_activated'] == '1' ? '' : 'none',
            'NEWS_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_CHECKED'     =>  ($this->arrSettings['news_comments_anonymous'] == '1') ? 'checked="checked"' : '',
            'NEWS_SETTINGS_COMMENTS_AUTO_ACTIVATE_CHECKED'       =>  ($this->arrSettings['news_comments_autoactivate'] == '1') ? 'checked="checked"' : '',
            'NEWS_SETTINGS_COMMENTS_NOTIFICATION_CHECKED'        =>  ($this->arrSettings['news_comments_notification'] == '1') ? 'checked="checked"' : '',
            'NEWS_SETTINGS_COMMENTS_TIMEOUT'                =>  intval($this->arrSettings['news_comments_timeout']),
            'TXT_NEWS_COMMENTS'                     => $_ARRAYLANG['TXT_NEWS_COMMENTS'],
            'TXT_NEWS_SETTINGS_COMMENTS_ALLOW_HELP'          => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_ALLOW_HELP'],
            'TXT_NEWS_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'     => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'],
            'TXT_NEWS_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'=> $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'],
            'TXT_NEWS_SETTINGS_COMMENTS_AUTO_ACTIVATE'       => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_AUTO_ACTIVATE'],
            'TXT_NEWS_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'  => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'],
            'TXT_NEWS_SETTINGS_COMMENTS_NOTIFICATION'        => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_NOTIFICATION'],
            'TXT_NEWS_SETTINGS_COMMENTS_NOTIFICATION_HELP'   => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_NOTIFICATION_HELP'],
            'TXT_NEWS_SETTINGS_COMMENTS_TIMEOUT'             => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_TIMEOUT'],
            'TXT_NEWS_SETTINGS_COMMENTS_TIMEOUT_HELP'        => $_ARRAYLANG['TXT_NEWS_SETTINGS_COMMENTS_TIMEOUT_HELP'],
            'TXT_NEWS_SETTINGS_RECENT_MESSAGES_LIMIT_HELP'   => $_ARRAYLANG['TXT_NEWS_SETTINGS_RECENT_MESSAGES_LIMIT_HELP'],
            'TXT_NEWS_USE_PREVIOUS_NEXT_LINK'                => $_ARRAYLANG['TXT_NEWS_USE_PREVIOUS_NEXT_LINK'],
            'TXT_NEWS_SETTINGS_LOGIN_REDIRECT'               => $_ARRAYLANG['TXT_NEWS_SETTINGS_LOGIN_REDIRECT'],
            'TXT_NEWS_SETTINGS_LOGIN_REDIRECT_HELP'          => $_ARRAYLANG['TXT_NEWS_SETTINGS_LOGIN_REDIRECT_HELP'],
            'TXT_NEWS_DEFAULT_TEASERS'       => $_ARRAYLANG['TXT_NEWS_DEFAULT_TEASERS'],
            'TXT_NEWS_DEFAULT_TEASERS_HELP'       => $_ARRAYLANG['TXT_NEWS_DEFAULT_TEASERS_HELP'],
            'TXT_NEWS_EXTENDED'                     => $_ARRAYLANG['TXT_NEWS_EXTENDED'],
            'TXT_NEWS_TOP'                          => $_ARRAYLANG['TXT_NEWS_TOP'],
            'TXT_NEWS_TOP_LABEL'                    => $_ARRAYLANG['TXT_NEWS_TOP_LABEL'],
            'TXT_NEWS_TOP_DAYS'                     => $_ARRAYLANG['TXT_NEWS_TOP_DAYS'],
            'TXT_NEWS_TOP_LIMIT'                    => $_ARRAYLANG['TXT_NEWS_TOP_LIMIT'],
            'TXT_NEWS_AUTHOR_SELECTION'             => $_ARRAYLANG['TXT_NEWS_AUTHOR_SELECTION'],
            'TXT_NEWS_PUBLISHER_SELECTION'          => $_ARRAYLANG['TXT_NEWS_PUBLISHER_SELECTION'],
            'TXT_NEWS_LIST_ALL'                     => $_ARRAYLANG['TXT_NEWS_LIST_ALL'],
            'TXT_NEWS_LIST_SELECTED'                => $_ARRAYLANG['TXT_NEWS_LIST_SELECTED'],
            'TXT_NEWS_AVAILABLE_GROUPS'             => $_ARRAYLANG['TXT_NEWS_AVAILABLE_GROUPS'],
            'TXT_NEWS_ASSIGNED_GROUPS'              => $_ARRAYLANG['TXT_NEWS_ASSIGNED_GROUPS'],
            'TXT_NEWS_CHECK_ALL'                    => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_NEWS_UNCHECK_ALL'                  => $_ARRAYLANG['TXT_NEWS_UNCHECK_ALL'],
            'TXT_NEWS_AVAILABLE_GROUPS'             => $_ARRAYLANG['TXT_NEWS_AVAILABLE_GROUPS'],
            'TXT_NEWS_ASSIGNED_GROUPS'              => $_ARRAYLANG['TXT_NEWS_ASSIGNED_GROUPS'],
            'TXT_NEWS_RECENT_MESSAGES_LIMIT'        => $_ARRAYLANG['TXT_NEWS_RECENT_MESSAGES_LIMIT'],
            'NEWS_FILTER_AUTHOR_ACTIVE'             => ($this->arrSettings['news_assigned_author_groups']) ? 'checked="checked"' : '',
            'NEWS_FILTER_AUTHOR_INACTIVE'           => ($this->arrSettings['news_assigned_author_groups']) ? '' : 'checked="checked"',
            'NEWS_FILTER_AUTHOR_DISPLAY'            => ($this->arrSettings['news_assigned_author_groups']) ? 'block' : 'none',
            'NEWS_FILTER_PUBLISHER_ACTIVE'          => ($this->arrSettings['news_assigned_publisher_groups']) ? 'checked="checked"' : '',
            'NEWS_FILTER_PUBLISHER_INACTIVE'        => ($this->arrSettings['news_assigned_publisher_groups']) ? '' : 'checked="checked"',
            'NEWS_FILTER_PUBLISHER_DISPLAY'         => ($this->arrSettings['news_assigned_publisher_groups']) ? 'block' : 'none',
            'NEWS_EXISTING_AUTHOR_GROUPS'           => $existingAuthorGroups,
            'NEWS_ASSIGNED_AUTHOR_GROUPS'           => $assignedAuthorGroups,
            'NEWS_EXISTING_PUBLISHER_GROUPS'        => $existingPublisherGroups,
            'NEWS_ASSIGNED_PUBLISHER_GROUPS'        => $assignedPublisherGroups,
            'NEWS_USE_TOP_CHECKED'                  => $this->arrSettings['news_use_top'] == '1' ? 'checked="checked"' : '',
            'NEWS_MESSAGE_TOP_DISPLAY'              => $this->arrSettings['news_use_top'] == '1' ? '' : 'none',
            'NEWS_TOP_DAYS'                         =>(intval($this->arrSettings['news_top_days'])),
            'NEWS_TOP_LIMIT'                        =>(intval($this->arrSettings['news_top_limit'])),
            'NEWS_MEDIA_BROWSER_BROWSE_BUTTON'      => self::getMediaBrowserButton(array(
                                                                                    'type'             => 'button',
                                                                                    'id'               => 'newsFeedImage',
                                                                                    'views'            => 'filebrowser',
                                                                                    'style'            => 'width:110px;'
                                                                                   ),'SetUrl')
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_ACTIVATED'                         =>  $_CORELANG['TXT_ACTIVATED'],
            'TXT_DEACTIVATED'                       =>  $_CORELANG['TXT_DEACTIVATED'],
        ));

        // get list of all teasers
        $objTeaser = new \Cx\Core_Modules\News\Controller\Teasers(true);
        $arrNewsDefaultTeasers = explode(';', $this->arrSettings['news_default_teasers']);
        $frameIds = '';
        foreach ($objTeaser->arrTeaserFrameNames as $frameName => $frameId) {
            $this->_objTpl->setVariable(array(
                'NEWS_TEASER_NAME'      => contrexx_raw2xhtml($frameName),
                'NEWS_TEASER_ID'        => $frameId,
                'NEWS_TEASER_CHECKED'   => (in_array($frameId, $arrNewsDefaultTeasers)) ? 'checked="checked"' : '',
            ));
            $this->_objTpl->parse('defaultTeasers');
        }

        $this->_objTpl->parse('settings_content');
    }

    /**
     * Shows placeholders
     *
     * @access  private
     * @global  array   $_CORELANG
     * @global  array   $_ARRAYLANG
     */
    private function settingsPlaceholders() {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->addBlockfile('NEWS_SETTINGS_CONTENT', 'settings_content', 'module_news_settings_placeholders.html');
        $this->_objTpl->setVariable(array(
            'TXT_PERFORM'                                               => $_ARRAYLANG['TXT_PERFORM'],
            'TXT_CATEGORY'                                              => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_NEWS_TYPE'                                             => $_ARRAYLANG['TXT_NEWS_TYPE'],
            'TXT_DATE'                                                  => $_ARRAYLANG['TXT_DATE'],
            'TXT_TITLE'                                                 => $_ARRAYLANG['TXT_TITLE'],
            'TXT_NEWS_SITE'                                             => $_ARRAYLANG['TXT_NEWS_SITE'],
            'TXT_NEWS_MESSAGE'                                          => $_ARRAYLANG['TXT_NEWS_MESSAGE'],
            'TXT_HEADLINES'                                             => $_ARRAYLANG['TXT_HEADLINES'],
            'TXT_TOPNEWS'                                               => $_ARRAYLANG['TXT_TOPNEWS'],
            'TXT_TEASERS'                                               => $_ARRAYLANG['TXT_TEASERS'],
            'TXT_USAGE'                                                 => $_ARRAYLANG['TXT_USAGE'],
            'TXT_NEWS_PLACEHOLLDERS_USAGE_TEXT'                         => $_ARRAYLANG['TXT_NEWS_PLACEHOLLDERS_USAGE_TEXT'],
            'TXT_PLACEHOLDER_LIST'                                      => $_ARRAYLANG['TXT_PLACEHOLDER_LIST'],
            'TXT_LANGUAGE_VARIABLES'                                    => $_ARRAYLANG['TXT_LANGUAGE_VARIABLES'],
            'TXT_GENERAL'                                               => $_ARRAYLANG['TXT_GENERAL'],
            'TXT_NEWS_PAGIN_DESCRIPTION'                                => $_ARRAYLANG['TXT_NEWS_PAGIN_DESCRIPTION'],
            'TXT_NEWS_NO_CATEGORY_DESCRIPTION'                          => $_ARRAYLANG['TXT_NEWS_NO_CATEGORY_DESCRIPTION'],
            'TXT_NEWS_NO_TYPE_DESCRIPTION'                              => $_ARRAYLANG['TXT_NEWS_NO_TYPE_DESCRIPTION'],
            'TXT_NEWS_CAT_DROPDOWNMENU_DESCRIPTION'                     => $_ARRAYLANG['TXT_NEWS_CAT_DROPDOWNMENU_DESCRIPTION'],
            'TXT_NEWS_TYPE_DROPDOWNMENU_DESCRIPTION'                    => $_ARRAYLANG['TXT_NEWS_TYPE_DROPDOWNMENU_DESCRIPTION'],
            'TXT_NEWS_DATE_DESCRIPTION'                                 => $_ARRAYLANG['TXT_NEWS_DATE_DESCRIPTION'],
            'TXT_NEWS_TIME_DESCRIPTION'                                 => $_ARRAYLANG['TXT_NEWS_TIME_DESCRIPTION'],
            'TXT_NEWS_LONG_DATE_DESCRIPTION'                            => $_ARRAYLANG['TXT_NEWS_LONG_DATE_DESCRIPTION'],
            'TXT_NEWS_LINK_TITLE_DESCRIPTION'                           => $_ARRAYLANG['TXT_NEWS_LINK_TITLE_DESCRIPTION'],
            'TXT_NEWS_LINK_URL_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_LINK_URL_DESCRIPTION'],
            'TXT_NEWS_PUBLISHER_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_PUBLISHER_DESCRIPTION'],
            'TXT_NEWS_AUTHOR_DESCRIPTION'                               => $_ARRAYLANG['TXT_NEWS_AUTHOR_DESCRIPTION'],
            'TXT_NEWS_LINK_DESCRIPTION'                                 => $_ARRAYLANG['TXT_NEWS_LINK_DESCRIPTION'],
            'TXT_NEWS_CATEGORY_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_CATEGORY_DESCRIPTION'],
            'TXT_NEWS_TYPE_DESCRIPTION'                                 => $_ARRAYLANG['TXT_NEWS_TYPE_DESCRIPTION'],
            'TXT_NEWS_CSS_DESCRIPTION'                                  => $_ARRAYLANG['TXT_NEWS_CSS_DESCRIPTION'],
            'TXT_NEWSROW_DESCRIPTION'                                   => $_ARRAYLANG['TXT_NEWSROW_DESCRIPTION'],
            'TXT_NEWS_ARCHIVE_ROW_DESCRIPTION'                          => $_ARRAYLANG['TXT_NEWS_ARCHIVE_ROW_DESCRIPTION'],
            'TXT_NEWS_ARCHIVE_MONTH_KEY_DESCRIPTION'                    => $_ARRAYLANG['TXT_NEWS_ARCHIVE_MONTH_KEY_DESCRIPTION'],
            'TXT_NEWS_ARCHIVE_MONTH_NAME_DESCRIPTION'                   => $_ARRAYLANG['TXT_NEWS_ARCHIVE_MONTH_NAME_DESCRIPTION'],
            'TXT_NEWS_ARCHIVE_MONTH_COUNT_DESCRIPTION'                  => $_ARRAYLANG['TXT_NEWS_ARCHIVE_MONTH_COUNT_DESCRIPTION'],
            'TXT_EXAMPLE'                                               => $_ARRAYLANG['TXT_EXAMPLE'],
            'TXT_NEWS_MESSAGES'                                         => $_ARRAYLANG['TXT_NEWS_MESSAGES'],
            'TXT_NEWS_DETAILS_PLACEHOLLDERS_USAGE'                      => $_ARRAYLANG['TXT_NEWS_DETAILS_PLACEHOLLDERS_USAGE'],
            'TXT_NEWS_TITLE_DESCRIPTION'                                => $_ARRAYLANG['TXT_NEWS_TITLE_DESCRIPTION'],
            'TXT_NEWS_TEXT_DESCRIPTION'                                 => $_ARRAYLANG['TXT_NEWS_TEXT_DESCRIPTION'],
            'TXT_NEWS_URL_DESCRIPTION'                                  => $_ARRAYLANG['TXT_NEWS_URL_DESCRIPTION'],
            'TXT_PUBLISHED_ON'                                          => $_ARRAYLANG['TXT_PUBLISHED_ON'],
            'TXT_HEADLINE_PLACEHOLDERS_USAGE'                           => $_ARRAYLANG['TXT_HEADLINE_PLACEHOLDERS_USAGE'],
            'TXT_NEWS_IMAGE_PATH_DESCRIPTION'                           => $_ARRAYLANG['TXT_NEWS_IMAGE_PATH_DESCRIPTION'],
            'TXT_NEWS_THUMBNAIL_PATH_DESCRIPTION'                       => $_ARRAYLANG['TXT_NEWS_THUMBNAIL_PATH_DESCRIPTION'],
            'TXT_NEWS_TEXT_DESCRIPTION'                                 => $_ARRAYLANG['TXT_NEWS_TEXT_DESCRIPTION'],
            'TXT_MORE_NEWS'                                             => $_CORELANG['TXT_MORE_NEWS'],
            'TXT_TEASER_PLACEHOLLDERS_USAGE'                            => $_ARRAYLANG['TXT_TEASER_PLACEHOLLDERS_USAGE'],
            'TXT_NEWS_CATEGORIES'                                       => $_ARRAYLANG['TXT_NEWS_CATEGORIES'],
            'TXT_NEWS_CATEGORIES_PLACEHOLDERS_USAGE'                    => $_ARRAYLANG['TXT_NEWS_CATEGORIES_PLACEHOLDERS_USAGE'],
            'TXT_NEWS_ARCHIVE_LIST'                                     => $_ARRAYLANG['TXT_NEWS_ARCHIVE_LIST'],
            'TXT_NEWS_ARCHIVE_LIST_PLACEHOLDERS_USAGE'                  => $_ARRAYLANG['TXT_NEWS_ARCHIVE_LIST_PLACEHOLDERS_USAGE'],
            'TXT_NEWS_RECENT_COMMENTS'                                  => $_ARRAYLANG['TXT_NEWS_RECENT_COMMENTS'],
            'TXT_NEWS_RECENT_COMMENTS_PLACEHOLDERS_USAGE'               => $_ARRAYLANG['TXT_NEWS_RECENT_COMMENTS_PLACEHOLDERS_USAGE'],
            'TXT_NEWS_COMMENTS_CSS_DESCRIPTION'                         => $_ARRAYLANG['TXT_NEWS_COMMENTS_CSS_DESCRIPTION'],
            'TXT_NEWS_COMMENTS_TITLE_DESCRIPTION'                       => $_ARRAYLANG['TXT_NEWS_COMMENTS_TITLE_DESCRIPTION'],
            'TXT_NEWS_COMMENTS_MESSAGE_DESCRIPTION'                     => $_ARRAYLANG['TXT_NEWS_COMMENTS_MESSAGE_DESCRIPTION'],
            'TXT_NEWS_COMMENTS_LONG_DATE_DESCRIPTION'                   => $_ARRAYLANG['TXT_NEWS_COMMENTS_LONG_DATE_DESCRIPTION'],
            'TXT_NEWS_COMMENTS_DATE_DESCRIPTION'                        => $_ARRAYLANG['TXT_NEWS_COMMENTS_DATE_DESCRIPTION'],
            'TXT_NEWS_COMMENTS_TIME_DESCRIPTION'                        => $_ARRAYLANG['TXT_NEWS_COMMENTS_TIME_DESCRIPTION'],
            'TXT_NEWS_COMMENT_LINK_DESCRIPTION'                         => $_ARRAYLANG['TXT_NEWS_COMMENT_LINK_DESCRIPTION'],
            'TXT_NEWS_COMMENT_URL_DESCRIPTION'                          => $_ARRAYLANG['TXT_NEWS_COMMENT_URL_DESCRIPTION'],
            'TXT_NEWS_LASTUPDATE_DESCRIPTION'                           => $_ARRAYLANG['TXT_NEWS_LASTUPDATE_DESCRIPTION'],
            'TXT_NEWS_SOURCE_DESCRIPTION'                               => $_ARRAYLANG['TXT_NEWS_SOURCE_DESCRIPTION'],
            'TXT_NEWS_IMAGE_DESCRIPTION'                                => $_ARRAYLANG['TXT_NEWS_IMAGE_DESCRIPTION'],
            'TXT_NEWS_ID_DESCRIPTION'                                   => $_ARRAYLANG['TXT_NEWS_ID_DESCRIPTION'],
            'TXT_NEWS_CATEGORY_ID_DESCRIPTION'                          => $_ARRAYLANG['TXT_NEWS_CATEGORY_ID_DESCRIPTION'],
            'TXT_NEWS_IMAGE_LINK_DESCRIPTION'                           => $_ARRAYLANG['TXT_NEWS_IMAGE_LINK_DESCRIPTION'],
            'TXT_NEWS_COUNT_COMMENTS_DESCRIPTION'                       => $_ARRAYLANG['TXT_NEWS_COUNT_COMMENTS_DESCRIPTION'],
            'TXT_NEWS_IMAGE_SRC_DESCRIPTION'                            => $_ARRAYLANG['TXT_NEWS_IMAGE_SRC_DESCRIPTION'],
            'TXT_NEWS_CATEGORY_NAME_DESCRIPTION'                        => $_ARRAYLANG['TXT_NEWS_CATEGORY_NAME_DESCRIPTION'],
            'TXT_NEWS_TYPE_NAME_DESCRIPTION'                            => $_ARRAYLANG['TXT_NEWS_TYPE_NAME_DESCRIPTION'],
            'TXT_NEWS_IMAGE_ROW_DESCRIPTION'                            => $_ARRAYLANG['TXT_NEWS_IMAGE_ROW_DESCRIPTION'],
            'TXT_NEWS_ARCHIVE_IMAGE_ROW_DESCRIPTION'                    => $_ARRAYLANG['TXT_NEWS_ARCHIVE_IMAGE_ROW_DESCRIPTION'],
            'TXT_NEWS_TEASER_TEXT_DESCRIPTION'                          => $_ARRAYLANG['TXT_NEWS_TEASER_TEXT_DESCRIPTION'],
            'TXT_NEWS_AUTHOR_DESCRIPTION'                               => $_ARRAYLANG['TXT_NEWS_AUTHOR_DESCRIPTION'],
            'TXT_TOP_NEWS_PLACEHOLDERS_USAGE'                           => $_ARRAYLANG['TXT_TOP_NEWS_PLACEHOLDERS_USAGE'],
            'TXT_NEWS_COMMENTS'                                         => $_ARRAYLANG['TXT_NEWS_COMMENTS'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_POSTER'                     => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_POSTER'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_TIME'                       => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_TIME'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_TITLE'                      => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_LIST'                       => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_LIST'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_LIST_TITLE'                 => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_LIST_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT'                            => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_USER'                       => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_USER'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_DATE'                       => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_DATE'],
            'TXT_NEWS_COMMENT_BLOCK_COMMENT_TEXT'                       => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_COMMENT_TEXT'],
            'TXT_NEWS_COMMENT_BLOCK_NO_COMMENT'                         => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_NO_COMMENT'],
            'TXT_NEWS_COMMENT_BLOCK_NO_COMMENT_TITLE'                   => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_NO_COMMENT_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT'                        => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE'                  => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_ERROR'                  => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_ERROR'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_ERROR_TITLE'            => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_ERROR_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE_TITLE'            => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_VALUE_TITLE'            => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_VALUE_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE_COMMENT'          => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE_COMMENT'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_VALUE_COMMENT'          => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_VALUE_COMMENT'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_NAME'                   => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_NAME'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_NAME_TITLE'             => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_NAME_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_NAME_VALUE'             => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_NAME_VALUE'],
            'TXT_NEWS_COMMENT_BLOCK_CONTACT_FORM_CAPTCHA'               => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_CONTACT_FORM_CAPTCHA'],
            'TXT_NEWS_COMMENT_BLOCK_CONTACT_FORM_CAPTCHA_TITLE'         => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_CONTACT_FORM_CAPTCHA_TITLE'],
            'TXT_NEWS_COMMENT_BLOCK_CONTACT_FORM_CAPTCHA_CODE'          => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_CONTACT_FORM_CAPTCHA_CODE'],
            'TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE_BUTTON'           => $_ARRAYLANG['TXT_NEWS_COMMENT_BLOCK_ADD_COMMENT_TITLE_BUTTON'],
            'TXT_NEWS_IMAGE_DETAIL_ROW_DESCRIPTION'                     => $_ARRAYLANG['TXT_NEWS_IMAGE_DETAIL_ROW_DESCRIPTION'],
            'TXT_NEWS_IMAGE_THUMBNAIL_ROW_DESCRIPTION'                  => $_ARRAYLANG['TXT_NEWS_IMAGE_THUMBNAIL_ROW_DESCRIPTION'],
            'TXT_NEWS_IMAGE_DETAIL_DESCRIPTION'                         => $_ARRAYLANG['TXT_NEWS_IMAGE_DETAIL_DESCRIPTION'],
            'TXT_NEWS_IMAGE_DETAIL_LINK_DESCRIPTION'                    => $_ARRAYLANG['TXT_NEWS_IMAGE_DETAIL_LINK_DESCRIPTION'],
            'TXT_NEWS_IMAGE_DETAIL_SRC_DESCRIPTION'                     => $_ARRAYLANG['TXT_NEWS_IMAGE_DETAIL_SRC_DESCRIPTION'],
            'TXT_NEWS_IMAGE_THUMBNAIL_DESCRIPTION'                      => $_ARRAYLANG['TXT_NEWS_IMAGE_THUMBNAIL_DESCRIPTION'],
            'TXT_NEWS_IMAGE_THUMBNAIL_LINK_DESCRIPTION'                 => $_ARRAYLANG['TXT_NEWS_IMAGE_THUMBNAIL_LINK_DESCRIPTION'],
            'TXT_NEWS_IMAGE_THUMBNAIL_SRC_DESCRIPTION'                  => $_ARRAYLANG['TXT_NEWS_IMAGE_THUMBNAIL_SRC_DESCRIPTION'],
            'TXT_NEWS_ARCHIVE_IMAGE_DETAIL_ROW_DESCRIPTION'             => $_ARRAYLANG['TXT_NEWS_ARCHIVE_IMAGE_DETAIL_ROW_DESCRIPTION'],
            'TXT_NEWS_ARCHIVE_IMAGE_THUMBNAIL_ROW_DESCRIPTION'          => $_ARRAYLANG['TXT_NEWS_ARCHIVE_IMAGE_THUMBNAIL_ROW_DESCRIPTION'],
            'TXT_NEWS_HEADLINES_ROW_DESCRIPTION'                        => $_ARRAYLANG['TXT_NEWS_HEADLINES_ROW_DESCRIPTION'],
            'TXT_NEWS_PREVIOUS_NEXT_LINKS'                              => $_ARRAYLANG['TXT_NEWS_PREVIOUS_NEXT_LINKS'],
            'TXT_NEWS_PREVIOUS_LINK_DESCRITION'                         => $_ARRAYLANG['TXT_NEWS_PREVIOUS_LINK_DESCRITION'],
            'TXT_NEWS_PREVIOUS_TITLE_DESCRITION'                        => $_ARRAYLANG['TXT_NEWS_PREVIOUS_TITLE_DESCRITION'],
            'TXT_NEWS_NEXT_LINK_DESCRITION'                             => $_ARRAYLANG['TXT_NEWS_NEXT_LINK_DESCRITION'],
            'TXT_NEWS_NEXT_TITLE_DESCRITION'                            => $_ARRAYLANG['TXT_NEWS_NEXT_TITLE_DESCRITION'],
            'TXT_NEWS_PREVIOUS_NEXT_LINK_ROW_DESCRIPTION'               => $_ARRAYLANG['TXT_NEWS_PREVIOUS_NEXT_LINK_ROW_DESCRIPTION'],
            'TXT_NEWS_USER_ID_DESCRIPTION'                              => $_ARRAYLANG['TXT_NEWS_USER_ID_DESCRIPTION'],
            'TXT_NEWS_USERNAME_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_USERNAME_DESCRIPTION'],
            'TXT_NEWS_USER_PROFILE_ATTRIBUTE_DESCRIPTION'               => $_ARRAYLANG['TXT_NEWS_USER_PROFILE_ATTRIBUTE_DESCRIPTION'],
            'TXT_NEWS_TEASER_TEXT_ROW_DESCRIPTION'                      => $_ARRAYLANG['TXT_NEWS_TEASER_TEXT_ROW_DESCRIPTION'],
            'TXT_NEWS_NEWS_COMMENT_COUNT_ROW_DESCRIPTION'               => $_ARRAYLANG['TXT_NEWS_NEWS_COMMENT_COUNT_ROW_DESCRIPTION'],
            'TXT_NEWS_TEXT_ROW_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_TEXT_ROW_DESCRIPTION'],
            'TXT_NEWS_REDIRECT_ROW_DESCRIPTION'                         => $_ARRAYLANG['TXT_NEWS_REDIRECT_ROW_DESCRIPTION'],
            'TXT_NEWS_TAGS'                                             => $_ARRAYLANG['TXT_NEWS_TAGS'],
            'TXT_NEWS_TAGS_ROW_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_TAGS_ROW_DESCRIPTION'],
            'TXT_NEWS_TAG_NAME_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_TAG_NAME_DESCRIPTION'],
            'TXT_NEWS_TAG_LINK_DESCRIPTION'                             => $_ARRAYLANG['TXT_NEWS_TAG_LINK_DESCRIPTION'],
            'TXT_NEWS_NO_TAGS_FOUND'                                    => $_ARRAYLANG['TXT_NEWS_NO_TAGS_FOUND'],
            'TXT_NEWS_PREVIOUS_SRC_DESCRITION'                          => $_ARRAYLANG['TXT_NEWS_PREVIOUS_SRC_DESCRITION'],
            'TXT_NEWS_NEXT_SRC_DESCRITION'                              => $_ARRAYLANG['TXT_NEWS_NEXT_SRC_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS'                                     => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS'],
            'TXT_NEWS_RELATED_NEWS_BLOCK_DESCRITION'                    => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_BLOCK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_BLOCK_TITLE_DESCRITION'              => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_BLOCK_TITLE_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_DETAIL_BLOCK_DESCRITION'             => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_DETAIL_BLOCK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_ID_DESCRITION'                       => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_ID_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_URL_DESCRITION'                      => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_URL_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_LINK_DESCRITION'                     => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_LINK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TITLE_DESCRITION'                    => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TITLE_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TITLE_SHORT_DESCRITION'              => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TITLE_SHORT_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TITLE_LINK_DESCRITION'               => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TITLE_LINK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TEXT_DESCRITION'                     => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TEXT_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TEXT_SHORT_DESCRITION'               => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TEXT_SHORT_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_AUTHOR_DESCRITION'                   => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_AUTHOR_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_PUBLISHER_DESCRITION'                => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_PUBLISHER_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_CATEGORY_NAMES_DESCRITION'           => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_CATEGORY_NAMES_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_LONG_DATE_DESCRITION'                => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_LONG_DATE_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_DATE_DESCRITION'                     => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_DATE_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TIME_DESCRITION'                     => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TIME_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_COMMENTS_BLOCK_DESCRITION'           => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_COMMENTS_BLOCK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_COMMENTS_DESCRITION'                 => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_COMMENTS_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TEASER_TEXT_BLOCK_DESCRITION'        => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TEASER_TEXT_BLOCK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_TEASER_TEXT_DESCRITION'              => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_TEASER_TEXT_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_BLOCK_DESCRITION'              => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_BLOCK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_DESCRITION'                    => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_TITLE_DESCRITION'              => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_TITLE_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_LINK_DESCRITION'               => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_LINK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_SRC_DESCRITION'                => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_SRC_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_BLOCK_DESCRITION'        => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_BLOCK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_DESCRITION'              => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_TITLE_DESCRITION'        => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_TITLE_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_LINK_DESCRITION'         => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_LINK_DESCRITION'],
            'TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_SRC_DESCRITION'          => $_ARRAYLANG['TXT_NEWS_RELATED_NEWS_IMAGE_THUMB_SRC_DESCRITION'],
        ));
        $this->_objTpl->parse('settings_content');
    }

    function _getAllUserGroups() {
        $userGroups = array();
        $objGroup = \FWUser::getFWUserObject()->objGroup->getGroups();
        if ($objGroup) {
            while (!$objGroup->EOF) {
                $userGroups[$objGroup->getId()] = $objGroup->getName();
                $objGroup->next();
            }
        }
        return $userGroups;
    }

    function _generate_notify_group_list()
    {
        $active_grp = $this->arrSettings['news_notify_group'];
        if (!empty($_POST['newsNotifySelectedGroup'])) {
            $active_grp = intval($_POST['newsNotifySelectedGroup']);
        }
        return $this->_generate_notify_list('group', $active_grp);
    }
    function _generate_notify_user_list() {
        $active_user= $this->arrSettings['news_notify_user'];
        if (!empty($_POST['newsNotifySelectedUser'])) {
            $active_user = intval($_POST['newsNotifySelectedUser']);
        }
        return $this->_generate_notify_list('user', $active_user);
    }


    /**
     * Generates a list of <option> lines, including a "disable" entry on top of the list.
     * For this to work, the function needs to know the following parameters:
     * @param id_col    The column of the table that contains the "value" part of the option
     * @param label_col The column that will be displayed to the user.
     * @param table     The table from where to select data. without pefix!
     * @param active_id The id which should be pre-selected.
     */
    function _generate_notify_list($type, $active_id)
    {
        global $_ARRAYLANG, $objDatabase;
        $res = array();

        $none_selected = $active_id == 0 ? 'selected' : '';
        $res[] = "<option value=\"0\" $none_selected>(".$_ARRAYLANG['TXT_DEACTIVATE'].")</option>";

        $objFWUser = \FWUser::getFWUserObject();
        if ($type == 'user') {
            $objData = $objFWUser->objUser->getUsers(null, null, array('username' => 'desc'), array('id', 'username', 'firstname', 'lastname'));
        } else {
            $objData = $objFWUser->objGroup->getGroups(null, array('group_name' => 'desc'), array('group_id', 'group_name', 'group_description'));
        }

        while (!$objData->EOF) {
            $id       = $objData->getId();
            $name     = $type == 'user' ? "{$objData->getUsername()} ({$objData->getProfileAttribute('firstname')} {$objData->getProfileAttribute('lastname')})" : "{$objData->getName()} ({$objData->getDescription()})";
            $selected = $id == $active_id ? 'selected' : '';

            $res[] = "<option value=\"$id\" $selected>$name</option>";
            $objData->next();
        }

        return join("\n\t", $res);
    }


    function _ticker()
    {
        global $_ARRAYLANG;

        $this->_objTpl->addBlockfile('NEWS_SETTINGS_CONTENT', 'settings_content', 'module_news_ticker.html');

        $this->_objTpl->setVariable(array(
            'TXT_NEWS_OVERVIEW'         => $_ARRAYLANG['TXT_NEWS_OVERVIEW'],
            'TXT_NEWS_CREATE_TICKER'    => $_ARRAYLANG['TXT_NEWS_CREATE_TICKER']
        ));

        $tpl = !empty($_REQUEST['tpl2']) ? $_REQUEST['tpl2'] : '';
        switch ($tpl) {
            case 'modify':
                $this->_modifyTicker();
                break;

            case 'delete':
                $this->_deleteTicker();

            default:
                $this->_tickerOverview();
                break;
        }
    }


    function _deleteTicker()
    {
        global $objDatabase, $_ARRAYLANG;

        $arrIds = array();
        $status = true;

        if (isset($_POST['news_ticker_id']) && is_array($_POST['news_ticker_id'])) {
            foreach ($_POST['news_ticker_id'] as $id) {
                array_push($arrIds, intval($id));
            }
        } elseif (!empty($_GET['id'])) {
            array_push($arrIds, intval($_GET['id']));
        }

        foreach ($arrIds as $id) {
            $arrTicker = $this->_getTicker($id);
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_news_ticker` WHERE `id` = ".$id) === false) {
                $status = false;
            } else {
                @unlink(\Env::get('cx')->getWebsiteFeedPath().'/'.$arrTicker['name']);
            }
        }

        if ($status) {
            if (count($arrIds) > 1) {
                $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_TICKERS_SCCESSFULLY_DELETED'];
            } elseif (count($arrIds) == 1) {
                $this->strOkMessage = sprintf($_ARRAYLANG['TXT_NEWS_TICKER_SUCCESSFULLY_DELETED'], $arrTicker['name']);
            }
        } else {
            if (count($arrIds) > 1) {
                $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_TICKERS_FAILED_DELETE'];
            } else {
                $this->strErrMessage = sprintf($_ARRAYLANG['TXT_NEWS_TICKER_FAILED_DELETE'], $arrTicker['name']);
            }
        }

        return $status;
    }


    function _modifyTicker()
    {
        global $_ARRAYLANG, $objDatabase;

        $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
        $defaultCharset = CONTREXX_CHARSET;
        if ($arrTicker = $this->_getTicker($id)) {
            $this->pageTitle = $_ARRAYLANG['TXT_NEWS_MODIFY_TICKER'];
            $name = $arrTicker['name'];
            $charset = $arrTicker['charset'];
            $urlencode = $arrTicker['urlencode'];
            $prefix = $arrTicker['prefix'];
        } else {
            $id = 0;
            $this->pageTitle = $_ARRAYLANG['TXT_NEWS_CREATE_TICKER'];
            $name = '';
            $charset = $defaultCharset;
            $content = '';
            $urlencode = 0;
            $prefix = '';
        }

        if (isset($_POST['news_save_ticker'])) {
            $newName = isset($_POST['news_ticker_filename']) ? contrexx_stripslashes(trim($_POST['news_ticker_filename'])) : '';
            $charset = isset($_POST['news_ticker_charset']) ? addslashes($_POST['news_ticker_charset']) : '';
            $content = isset($_POST['news_ticker_content']) ? contrexx_stripslashes($_POST['news_ticker_content']) : '';
            $urlencode = isset($_POST['news_ticker_urlencode']) ? intval($_POST['news_ticker_urlencode']) : 0;
            $prefix = isset($_POST['news_ticker_prefix']) ? contrexx_stripslashes($_POST['news_ticker_prefix']) : '';

            if (!empty($newName)) {
                if ($name != $newName && file_exists(\Env::get('cx')->getWebsiteFeedPath().'/'.$newName)) {
                    $this->strErrMessage = sprintf($_ARRAYLANG['TXT_NEWS_FILE_DOES_ALREADY_EXIST'], htmlentities($newName, ENT_QUOTES, CONTREXX_CHARSET), \Env::get('cx')->getWebsiteFeedPath()).'<br />';
                    $this->strErrMessage .= $_ARRAYLANG['TXT_NEWS_SELECT_OTHER_FILENAME'];
                } elseif ($name != $newName && !@touch(\Env::get('cx')->getWebsiteFeedPath().'/'.$newName)) {
                    $this->strErrMessage = sprintf($_ARRAYLANG['TXT_NEWS_COULD_NOT_ATTACH_FILE'], htmlentities($newName, ENT_QUOTES, CONTREXX_CHARSET), \Env::get('cx')->getWebsiteFeedPath().'/').'<br />';
                    $this->strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWS_SET_CHMOD'], \Env::get('cx')->getWebsiteFeedPath().'/');
                } else {
                    if ($objDatabase->Execute(($id > 0 ? "UPDATE" : "INSERT INTO")." `".DBPREFIX."module_news_ticker` SET `name` = '".addslashes($newName)."', `charset` = '".addslashes($charset)."', `urlencode` = ".$urlencode.", `prefix` = '".addslashes($prefix)."'".($id > 0 ?" WHERE `id` = ".$id : ''))) {

                        $objFile = new \File();
                        $objFile->setChmod(\Env::get('cx')->getWebsiteFeedPath(), ASCMS_FEED_WEB_PATH, $newName);

                        $fpTicker = @fopen(\Env::get('cx')->getWebsiteFeedPath().'/'.$newName, 'wb');
                        if ($fpTicker) {
                            if ($defaultCharset != $charset) {
                                $content = iconv($defaultCharset, $charset, $content);
                                $prefix = iconv($defaultCharset, $charset, $prefix);
                            }
                            $content2w = $prefix.($urlencode ? rawurlencode($content) : $content);
                            if (@fwrite($fpTicker, $content2w) !== false) {
                                $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_NEWSTICKER_SUCCESSFULLY_UPDATED'];
                                if ($name != $newName && file_exists(\Env::get('cx')->getWebsiteFeedPath().'/'.$name)) {
                                    @unlink(\Env::get('cx')->getWebsiteFeedPath().'/'.$name);
                                }
                                return $this->_tickerOverview();
                            } else {
                                $this->strErrMessage = sprintf($_ARRAYLANG['TXT_NEWS_UNABLE_TO_UPDATE_FILE'], htmlentities($newName, ENT_QUOTES, CONTREXX_CHARSET), \Env::get('cx')->getWebsiteFeedPath().'/').'<br />';
                                $this->strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWS_SET_CHMOD'], \Env::get('cx')->getWebsiteFeedPath().'/'.$newName);
                            }
                        } else {
                            $this->strErrMessage = sprintf($_ARRAYLANG['TXT_NEWS_FILE_DOES_NOT_EXIST'], \Env::get('cx')->getWebsiteFeedPath().'/'.$newName);
                        }
                    } else {
                        $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_UNABLE_TO_RENAME_NEWSTICKER'];
                        @unlink(\Env::get('cx')->getWebsiteFeedPath().'/'.$newName);
                    }
                }
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_YOU_MUST_SET_FILENAME'];
            }

            $name = $newName;
        } elseif ($id > 0) {
            if (!file_exists(\Env::get('cx')->getWebsiteFeedPath().'/'.$name) && !@touch(\Env::get('cx')->getWebsiteFeedPath().'/'.$name)) {
                $this->strErrMessage = sprintf($_ARRAYLANG['TXT_NEWS_COULD_NOT_ATTACH_FILE'], htmlentities($name, ENT_QUOTES, CONTREXX_CHARSET), \Env::get('cx')->getWebsiteFeedPath().'/').'<br />';
                $this->strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWS_SET_CHMOD'], \Env::get('cx')->getWebsiteFeedPath().'/');
            } else {
                $content = file_get_contents(\Env::get('cx')->getWebsiteFeedPath().'/'.$name);
                if (!empty($prefix) && strpos($content, $prefix) === 0) {
                    $content = substr($content, strlen($prefix));
                }
                if ($urlencode) {
                    $content = rawurldecode($content);
                }
                if ($charset != $defaultCharset) {
                    $content = iconv($charset, $defaultCharset, $content);
                    $prefix = iconv($charset, $defaultCharset, $prefix);
                }
            }
        }

        $this->_objTpl->addBlockfile('NEWS_TICKER_TEMPLATE', 'module_news_ticker_modify', 'module_news_ticker_modify.html');

        $this->_objTpl->setVariable(array(
            'TXT_NEWS_FILENAME'             => $_ARRAYLANG['TXT_NEWS_FILENAME'],
            'TXT_NEWS_MODIFY_FILENAME'      => $_ARRAYLANG['TXT_NEWS_MODIFY_FILENAME'],
            'TXT_NEWS_CONTENT'              => $_ARRAYLANG['TXT_NEWS_CONTENT'],
            'TXT_NEWS_CHARSET'              => $_ARRAYLANG['TXT_NEWS_CHARSET'],
            'TXT_NEWS_SAVE'                 => $_ARRAYLANG['TXT_NEWS_SAVE'],
            'TXT_NEWS_CANCEL'               => $_ARRAYLANG['TXT_NEWS_CANCEL'],
            'TXT_NEWS_URL_ENCODING'         => $_ARRAYLANG['TXT_NEWS_URL_ENCODING'],
            'TXT_NEWS_URL_ENCODING_TXT'     => $_ARRAYLANG['TXT_NEWS_URL_ENCODING_TXT'],
            'TXT_NEWS_PREFIX'               => $_ARRAYLANG['TXT_NEWS_PREFIX'],
            'TXT_NEWS_TICKER_PREFIX_MSG'    => $_ARRAYLANG['TXT_NEWS_TICKER_PREFIX_MSG'],
            'TXT_NEWS_GENERAL'              => $_ARRAYLANG['TXT_NEWS_GENERAL'],
            'TXT_NEWS_ADVANCED'             => $_ARRAYLANG['TXT_NEWS_ADVANCED']
        ));

        $this->_objTpl->setVariable(array(
            'NEWS_MODIFY_TITLE_TXT'     => $id > 0 ? $_ARRAYLANG['TXT_NEWS_MODIFY_TICKER'] : $_ARRAYLANG['TXT_NEWS_CREATE_TICKER'],
            'NEWS_TICKER_ID'            => $id,
            'NEWS_TICKER_FILENAME'      => htmlentities($name, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWS_TICKER_CHARSET_MENU'  => $this->_getCharsetMenu($charset, 'name="news_ticker_charset"'),
            'NEWS_TICKER_CONTENT'       => htmlentities($content, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWS_TICKER_URLENCODE'     => $urlencode ? 'checked="checked"' : '',
            'NEWS_TICKER_POS'           => $pos,
            'NEWS_TICKER_PREFIX'        => $prefix
        ));

        $this->_objTpl->parse('module_news_ticker_modify');
    }


    function _getCharsetMenu($selectedCharset, $attrs = '')
    {
        $menu = '<select'.(!empty($attrs) ? ' '.$attrs : '').">\n";
        foreach ($this->_arrCharsets as $charset) {
            $menu .= "<option".($charset == $selectedCharset ? ' selected="selected"' : '')." value=\"".$charset."\">".$charset."</option>\n";
        }
        $menu .= "</select>\n";

        return $menu;
    }


    function _tickerOverview()
    {
        global $_ARRAYLANG, $_CONFIG;

        $this->pageTitle = $_ARRAYLANG['TXT_NEWS_TICKERS'];

        $paging = '';
        $pos = isset($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
        $count = $this->_tickerCount();

        $this->_objTpl->addBlockfile('NEWS_TICKER_TEMPLATE', 'module_news_ticker_list', 'module_news_ticker_list.html');

        $this->_objTpl->setVariable(array(
            'TXT_NEWS_TICKERS'                  => $_ARRAYLANG['TXT_NEWS_TICKERS'],
            'TXT_NEWS_TICKER'                   => $_ARRAYLANG['TXT_NEWS_TICKER'],
            'TXT_NEWS_CONTENT'                  => $_ARRAYLANG['TXT_NEWS_CONTENT'],
            'TXT_NEWS_CHARSET'                  => $_ARRAYLANG['TXT_NEWS_CHARSET'],
            'TXT_NEWS_FUNCTIONS'                => $_ARRAYLANG['TXT_NEWS_FUNCTIONS'],
            'TXT_NEWS_CONFIRM_DELETE_TICKER'    => $_ARRAYLANG['TXT_NEWS_CONFIRM_DELETE_TICKER'],
            'TXT_NEWS_ACTION_IS_IRREVERSIBLE'   => $_ARRAYLANG['TXT_NEWS_ACTION_IS_IRREVERSIBLE']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWS_SHOW_TICKER_FILE' => $_ARRAYLANG['TXT_NEWS_SHOW_TICKER_FILE'],
            'TXT_NEWS_MODIFY_TICKER'    => $_ARRAYLANG['TXT_NEWS_MODIFY_TICKER'],
            'NEWS_TICKER_POS'           => $pos
        ));

        if ($count > $_CONFIG['corePagingLimit']) {
            $paging = getPaging($count, $pos, '&amp;cmd=News&amp;act=ticker', 'Ticker');
        }

        $displayCharset = CONTREXX_CHARSET;

        $arrTickers = $this->_getTickers($pos);
        if (count($arrTickers) > 0) {
            $nr = 0;
            foreach ($arrTickers as $tickerId => $arrTicker) {
                if (!file_exists(\Env::get('cx')->getWebsiteFeedPath().'/'.$arrTicker['name']) && !@touch(\Env::get('cx')->getWebsiteFeedPath().'/'.$arrTicker['name'])) {
                    $this->strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWS_COULD_NOT_ATTACH_FILE'], htmlentities($arrTicker['name'], ENT_QUOTES, CONTREXX_CHARSET), \Env::get('cx')->getWebsiteFeedPath().'/').'<br />';
                    $this->strErrMessage .= sprintf($_ARRAYLANG['TXT_NEWS_SET_CHMOD'], \Env::get('cx')->getWebsiteFeedPath().'/');
                } else {
                    $content = file_get_contents(\Env::get('cx')->getWebsiteFeedPath().'/'.$arrTicker['name']);
                    if (!empty($arrTicker['prefix']) && strpos($content, $arrTicker['prefix']) === 0) {
                        $content = substr($content, strlen($arrTicker['prefix']));
                    }
                    if ($arrTicker['urlencode']) {
                        $content = rawurldecode($content);
                    }
                    $content = iconv($arrTicker['charset'], $displayCharset, $content);


                    if (strlen($content) > 100) {
                        $content = substr($content, 0, 100).'...';
                    }
                }

                $this->_objTpl->setVariable(array(
                    'NEWS_TICKER_ID'            => $tickerId,
                    'NEWS_TICKER_NAME'          => htmlentities($arrTicker['name'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWS_TICKER_ADDRESS'       => ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_FEED_WEB_PATH.'/'.htmlentities($arrTicker['name'], ENT_QUOTES, CONTREXX_CHARSET),
                    'NEWS_TICKER_CONTENT'       => !empty($content) ? htmlentities($content, ENT_QUOTES, CONTREXX_CHARSET) : '&nbsp;',
                    'NEWS_TICKER_CHARSET'       => $arrTicker['charset'],
                    'NEWS_TICKER_ROW_CLASS'     => $nr % 2 ? 1 : 2,
                    'NEWS_DELETE_TICKER_TXT'    => sprintf($_ARRAYLANG['TXT_NEWS_DELETE_TICKER'], htmlentities($arrTicker['name'], ENT_QUOTES, CONTREXX_CHARSET))
                ));
                $nr++;

                $this->_objTpl->parse('news_ticker_list');
            }

            $this->_objTpl->setVariable(array(
                'TXT_NEWS_CHECK_ALL'                    => $_ARRAYLANG['TXT_NEWS_CHECK_ALL'],
                'TXT_NEWS_UNCHECK_ALL'                  => $_ARRAYLANG['TXT_NEWS_UNCHECK_ALL'],
                'TXT_NEWS_WITH_SELECTED'                => $_ARRAYLANG['TXT_NEWS_WITH_SELECTED'],
                'TXT_NEWS_DELETE'                       => $_ARRAYLANG['TXT_NEWS_DELETE'],
                'TXT_NEWS_CREATE_TICKER'                => $_ARRAYLANG['TXT_NEWS_CREATE_TICKER'],
                'TXT_NEWS_CONFIRM_DELETE_TICKERS_MSG'   => $_ARRAYLANG['TXT_NEWS_CONFIRM_DELETE_TICKERS_MSG']
            ));

            $this->_objTpl->parse('news_ticker_data');
            $this->_objTpl->hideBlock('news_ticker_no_data');
            $this->_objTpl->touchBlock('news_ticker_multi_select_action');

            if (!empty($paging)) {
                $this->_objTpl->setVariable('PAGING', "<br />\n".$paging);
            }
        } else {
            $this->_objTpl->setVariable('TXT_NEWS_NO_TICKER_AVAILABLE', $_ARRAYLANG['TXT_NEWS_NO_TICKER_AVAILABLE']);

            $this->_objTpl->parse('news_ticker_no_data');
            $this->_objTpl->hideBlock('news_ticker_data');
            $this->_objTpl->hideBlock('news_ticker_multi_select_action');
        }

        $this->_objTpl->parse('module_news_ticker_list');
    }


    function _getTickers($offSet = 0)
    {
        global $objDatabase, $_CONFIG;

        $arrTickers = array();
        $query = "SELECT `id`, `name`, `charset`, `urlencode`, `prefix` FROM `".DBPREFIX."module_news_ticker` ORDER BY `name`";
        $objTicker = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $offSet);
        if ($objTicker !== false) {
            while (!$objTicker->EOF) {
                $arrTickers[$objTicker->fields['id']] = array(
                    'name'      => $objTicker->fields['name'],
                    'charset'   => $objTicker->fields['charset'],
                    'urlencode' => $objTicker->fields['urlencode'],
                    'prefix'    => $objTicker->fields['prefix']
                );
                $objTicker->MoveNext();
            }
        }

        return $arrTickers;
    }


    function _getTicker($id)
    {
        global $objDatabase;

        $objTicker = $objDatabase->SelectLimit("SELECT `name`, `charset`, `urlencode`, `prefix` FROM `".DBPREFIX."module_news_ticker` WHERE `id` = ".$id, 1);
        if ($objTicker !== false && $objTicker->RecordCount() == 1) {
            return (array(
                'name'      => $objTicker->fields['name'],
                'charset'   => $objTicker->fields['charset'],
                'urlencode' => $objTicker->fields['urlencode'],
                'prefix'    => $objTicker->fields['prefix']
            ));
        } else {
            return false;
        }
    }


    function _tickerCount()
    {
        global $objDatabase;

        $objCount = $objDatabase->SelectLimit('SELECT SUM(1) AS `count` FROM `'.DBPREFIX.'module_news_ticker`', 1);
        if ($objCount !== false) {
            return $objCount->fields['count'];
        } else {
            return false;
        }
    }


    function _teasers()
    {
        global $_ARRAYLANG;

        $this->_objTeaser = new \Cx\Core_Modules\News\Controller\Teasers(true);

        $this->_objTpl->addBlockfile('NEWS_SETTINGS_CONTENT', 'settings_content', 'module_news_teasers.html');

        $this->_objTpl->setGlobalVariable(array(
            'TXT_TEASER_TEASER_BOXES'       => $_ARRAYLANG['TXT_TEASER_TEASER_BOXES'],
            'TXT_TEASER_BOX_TEMPLATES'      => $_ARRAYLANG['TXT_TEASER_BOX_TEMPLATES']
        ));

        if (!isset($_REQUEST['tpl2'])) {
            $_REQUEST['tpl2'] = '';
        }

        switch ($_REQUEST['tpl2']) {
        case 'teasers':
            $this->_showTeasers();
            break;

        case 'frames':
            $this->_showTeaserFrames();
            break;

        case 'editFrame':
            $this->_editTeaserFrame();
            break;

        case 'deleteFrame':
            $this->_deleteTeaserFrame();
            $this->_showTeaserFrames();
            break;

        case 'updateFrame':
            $this->_updateTeaserFrame();
            break;

        case 'frameTemplates':
            $this->_showTeaserFrameTemplates();
            break;

        case 'editFrameTemplate':
            $this->_editTeaserFrameTemplate();
            break;

        case 'updateFrameTemplate':
            $this->_updateTeaserFrameTemplate();
            break;

        case 'deleteFrameTemplate':
            $this->_deleteTeaserFrameTeamplate();
            $this->_showTeaserFrameTemplates();
            break;

        default:
            $this->_showTeaserFrames();
            break;
        }
    }


    /**
     * Deletes a teaser frame template
     * @access private
     * @see Teaser::deleteTeaserFrameTemplate()
     */
    private function _deleteTeaserFrameTeamplate()
    {
        $templateId = intval($_GET['id']);

        $result = $this->_objTeaser->deleteTeaserFrameTeamplte($templateId);
        if ($result !== false && $result !== true) {
            $this->strOkMessage .= $result;
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array(
                    'Widget',
                    $this->getNewsGlobalPlaceholderNames()
                )
            );
        }

        $this->_objTeaser = new \Cx\Core_Modules\News\Controller\Teasers(true);
    }


    function _showTeaserFrameTemplates()
    {
        global $_ARRAYLANG;

        if (count($this->_objTeaser->arrTeaserFrameTemplates) > 0) {
            $this->pageTitle = $_ARRAYLANG['TXT_TEASER_BOX_TEMPLATES'];
            $this->_objTpl->addBlockFile('NEWS_TEASERS_FILE', 'news_teaser_frame_templates', 'module_news_teasers_frame_templates.html');
            $this->_objTpl->setVariable(array(
                'TXT_DESCRIPTION'                   => $_ARRAYLANG['TXT_DESCRIPTION'],
                'TXT_FUNCTIONS'                     => $_ARRAYLANG['TXT_FUNCTIONS'],
                'TXT_ADD_TEMPLATE'                  => $_ARRAYLANG['TXT_ADD_TEMPLATE'],
                'TXT_CONFIRM_DELETE_BOX_TPL_TEXT'   => $_ARRAYLANG['TXT_CONFIRM_DELETE_BOX_TPL_TEXT']
            ));
            $this->_objTpl->setGlobalVariable(array(
                'TXT_EDIT_BOX_TEMPLATE' => $_ARRAYLANG['TXT_EDIT_BOX_TEMPLATE'],
                'TXT_DELETE_TEMPLATE'       => $_ARRAYLANG['TXT_DELETE_TEMPLATE']
            ));
            $rowNr = 0;
            foreach ($this->_objTeaser->arrTeaserFrameTemplates as $id => $arrTeaserFrameTemplate) {
                $this->_objTpl->setVariable(array(
                    'NEWS_TEASER_FRAME_TPL_ROW_CLASS'   => $rowNr % 2 == 0 ? 'row1' : 'row2',
                    'NEWS_TEASER_FRAME_TPL_ID'          => $id,
                    'NEWS_TEASER_FRAME_TPL_DESCRIPTION' => htmlspecialchars($arrTeaserFrameTemplate['description'], ENT_QUOTES, CONTREXX_CHARSET)
                ));
                $this->_objTpl->parse('news_teaser_frame_template_list');
                $rowNr++;
            }
            $this->_objTpl->parse('news_teaser_frame_templates');
        } else {
            $this->_editTeaserFrameTemplate();
        }
    }


    function _editTeaserFrameTemplate()
    {
        global $_ARRAYLANG;

        $this->_objTpl->addBlockFile('NEWS_TEASERS_FILE', 'news_teaser_modify_frame_templates', 'module_news_teasers_modify_frame_template.html');
        // get teaser frame template id
        if (isset($_GET['templateId'])) {
            $templateId = intval($_GET['templateId']);
        } else {
            $templateId = 0;
        }
        // set teaser frame template description
        if (isset($_POST['teaserFrameTplDescription'])) {
            $templateDescription = htmlentities(contrexx_strip_tags($_POST['teaserFrameTplDescription']), ENT_QUOTES, CONTREXX_CHARSET);
        } elseif (isset($this->_objTeaser->arrTeaserFrameTemplates[$templateId]['description'])) {
            $templateDescription = $this->_objTeaser->arrTeaserFrameTemplates[$templateId]['description'];
        } else {
            $templateDescription = '';
        }
        // set wysiwyg or html mode
        if (isset($_GET['source'])) {
            $sourceCode = intval($_GET['source']);
        } elseif (isset($this->_objTeaser->arrTeaserFrameTemplates[$templateId]['source_code_mode'])) {
            $sourceCode = $this->_objTeaser->arrTeaserFrameTemplates[$templateId]['source_code_mode'];
        } else {
            $sourceCode = 0;
        }
        if (isset($_POST['teaserFrameTplHtml'])) {
            $templateHtml = $_POST['teaserFrameTplHtml'];
        } elseif (isset($this->_objTeaser->arrTeaserFrameTemplates[$templateId])) {
            $templateHtml = $this->_objTeaser->arrTeaserFrameTemplates[$templateId]['html'];
        } else {
            $templateHtml = '';
        }
        $templateHtml = preg_replace('/\{([A-Za-z0-9_]*?)\}/', '[[\\1]]', $templateHtml);
        $templateHtml = contrexx_raw2xhtml($templateHtml);
        $this->pageTitle = $templateId != 0 ? $_ARRAYLANG['TXT_EDIT_BOX_TEMPLATE'] : $_ARRAYLANG['TXT_ADD_BOX_TEMPLATE'];
        $this->_objTpl->setVariable(array(
            'TXT_PLACEHOLDER_DIRECTORY' => $_ARRAYLANG['TXT_PLACEHOLDER_DIRECTORY'],
            'TXT_DESCRIPTION'           => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_SOURCE_CODE_MODE'      => $_ARRAYLANG['TXT_SOURCE_CODE_MODE'],
            'TXT_CANCEL'            => $_ARRAYLANG['TXT_CANCEL'],
            'TXT_SAVE'              => $_ARRAYLANG['TXT_SAVE'],
            'TXT_PLACEHOLDER_LIST'      => $_ARRAYLANG['TXT_PLACEHOLDER_LIST'],
            'TXT_EXAMPLE'               => $_ARRAYLANG['TXT_EXAMPLE'],
            'TXT_BOX_TEMPLATE'      => $_ARRAYLANG['TXT_BOX_TEMPLATE'],
            'TXT_NEWS_LINK_DESCRIPTION' => $_ARRAYLANG['TXT_NEWS_LINK_DESCRIPTION'],
            'TXT_CATEGORY'  => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_NEWS_TYPE'  => $_ARRAYLANG['TXT_NEWS_TYPE'],
            'TXT_NEWS_DATE_DESCRIPTION'     => $_ARRAYLANG['TXT_NEWS_DATE_DESCRIPTION'],
            'TXT_NEWS_TITLE_DESCRIPTION'    => $_ARRAYLANG['TXT_NEWS_TITLE_DESCRIPTION'],
            'TXT_NEWS_IMAGE_PATH_DESCRIPTION'   => $_ARRAYLANG['TXT_NEWS_IMAGE_PATH_DESCRIPTION'],
            'TXT_TEASER_ROW_DESCRIPTION'        => $_ARRAYLANG['TXT_TEASER_ROW_DESCRIPTION'],
            'TXT_CONTINUE'                      => $_ARRAYLANG['TXT_CONTINUE'],
            'TXT_NEWS_LONG_DATE_DESCRIPTION'          => $_ARRAYLANG['TXT_NEWS_LONG_DATE_DESCRIPTION'],
            'TXT_NEWS_TIME_DESCRIPTION'               => $_ARRAYLANG['TXT_NEWS_TIME_DESCRIPTION'],
            'TXT_NEWS_TEASER_TEXT_DESCRIPTION'        => $_ARRAYLANG['TXT_NEWS_TEASER_TEXT_DESCRIPTION'],
            'TXT_NEWS_TEXT_DESCRIPTION'               => $_ARRAYLANG['TXT_NEWS_TEXT_DESCRIPTION'],
            'TXT_NEWS_AUTHOR_DESCRIPTION'             => $_ARRAYLANG['TXT_NEWS_AUTHOR_DESCRIPTION'],
            'TXT_NEWS_EXT_LINK_DESCRIPTION'           => $_ARRAYLANG['TXT_NEWS_EXT_LINK_DESCRIPTION'],
            'TXT_NEWS_TEASER_LINK_ROW_DESCRIPTION'    => $_ARRAYLANG['TXT_NEWS_TEASER_LINK_ROW_DESCRIPTION'],
        ));
        $this->_objTpl->setVariable(array(
            'NEWS_TEASER_FRAME_TPL_ID'              => $templateId,
            'NEWS_TEASER_FRAME_TPL_DESCRIPTION'     => htmlentities($templateDescription, ENT_QUOTES, CONTREXX_CHARSET),
            'NEWS_TEASER_FRAME_TEMPLATE_WYSIWYG'    => $sourceCode ? '<textarea name="teaserFrameTplHtml" style="width: 100%; height: 450px;">' . $templateHtml . '</textarea>' : new \Cx\Core\Wysiwyg\Wysiwyg('teaserFrameTplHtml', $templateHtml, 'full'),
            'NEWS_TEASER_FRAME_TPL_SOURCE_CHECKED'  => $sourceCode ? 'checked="checked"' : '',
            'NEWS_TEASER_TITLE_TXT'                 => $templateId != 0 ? $_ARRAYLANG['TXT_EDIT_BOX_TEMPLATE'] : $_ARRAYLANG['TXT_ADD_BOX_TEMPLATE']
        ));
        $this->_objTpl->parse('news_teaser_modify_frame_templates');
    }


    function _updateTeaserFrameTemplate()
    {
        global $_ARRAYLANG;

        if (isset($_POST['saveTeaserFrameTemplate']) && isset($_GET['templateId']) && isset($_POST['teaserFrameTplDescription']) && isset($_POST['teaserFrameTplHtml'])) {
            $templateId = intval($_GET['templateId']);
            $sourceCodeMode = isset($_POST['teaserFrameTplSource']) ? intval($_POST['teaserFrameTplSource']) : 0;
            $templateDescription = contrexx_strip_tags($_POST['teaserFrameTplDescription']);

        $tmp = trim($templateDescription);
            if (empty($tmp)) {
                $this->strErrMessage .= $_ARRAYLANG['TXT_SET_TEMPLATE_DESCRIPTION_TEXT'];
                $this->_editTeaserFrameTemplate();
                return;
            }
            $templateHtml = contrexx_addslashes($_POST['teaserFrameTplHtml']);
            $templateHtml = preg_replace('/\[\[([A-Za-z0-9_]*?)\]\]/', '{\\1}', $templateHtml);
            if ($templateId != 0) {
                $this->_objTeaser->updateTeaserFrameTemplate($templateId, $templateDescription, $templateHtml, $sourceCodeMode);
            } else {
                $this->_objTeaser->addTeaserFrameTemplate($templateDescription, $templateHtml, $sourceCodeMode);
            }
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array(
                    'Widget',
                    $this->getNewsGlobalPlaceholderNames()
                )
            );
            $this->_objTeaser->initializeTeaserFrameTemplates($templateId);
            $this->_showTeaserFrameTemplates();
        } elseif (isset($_POST['cancel'])) {
            $this->_showTeaserFrameTemplates();
        } else {
            $this->_editTeaserFrameTemplate();
        }
    }


    function _deleteTeaserFrame()
    {
        global $_ARRAYLANG;

        $frameId = intval($_GET['id']);
        if ($this->_objTeaser->deleteTeaserFrame($frameId)) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array(
                    'Widget',
                    $this->getNewsGlobalPlaceholderNames()
                )
            );
            $this->_objTeaser->initializeTeaserFrames();
            $this->strOkMessage .= $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
        } else {
            $this->strErrMessage .= $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
        }
    }


    function _showTeaserFrames()
    {
        global $_ARRAYLANG;

        $this->pageTitle = $_ARRAYLANG['TXT_TEASER_TEASER_BOXES'];
        $this->_objTeaser->initializeTeaserFrames();
        $arrTeaserFrames = $this->_objTeaser->arrTeaserFrames;
        if (count($this->_objTeaser->arrTeaserFrames) > 0) {
            $this->_objTpl->addBlockFile('NEWS_TEASERS_FILE', 'news_teasers_block', 'module_news_teasers_frames.html');
            $rowNr = 1;
            $this->_objTpl->setVariable(array(
                'TXT_BOX_NAME'                  => $_ARRAYLANG['TXT_BOX_NAME'],
                'TXT_PLACEHOLDER'                   => $_ARRAYLANG['TXT_PLACEHOLDER'],
                'TXT_BOX_TEMPLATE'              => $_ARRAYLANG['TXT_BOX_TEMPLATE'],
                'TXT_FUNCTIONS'                     => $_ARRAYLANG['TXT_FUNCTIONS'],
                'TXT_ADD_BOX'                       => $_ARRAYLANG['TXT_ADD_BOX'],
                'TXT_CONFIRM_DELETE_TEASER_BOX' => $_ARRAYLANG['TXT_CONFIRM_DELETE_TEASER_BOX']
            ));
            $this->_objTpl->setGlobalVariable(array(
                'TXT_SHOW_TEASER_BOX'               => $_ARRAYLANG['TXT_SHOW_TEASER_BOX'],
                'TXT_EDIT_BOX_TEMPLATE'         => $_ARRAYLANG['TXT_EDIT_BOX_TEMPLATE'],
                'TXT_EDIT_TEASER_BOX'               => $_ARRAYLANG['TXT_EDIT_TEASER_BOX'],
                'TXT_DELETE_TEASER_BOX'         => $_ARRAYLANG['TXT_DELETE_TEASER_BOX']
            ));
            foreach ($arrTeaserFrames as $teaserFrameId => $arrTeaserFrame) {
                $this->_objTpl->setVariable(array(
                    'NEWS_TEASER_FRAME_ROW_CLASS'               => $rowNr % 2 == 0 ? 'row1' : 'row2',
                    'NEWS_TEASER_FRAME_NAME'                    => $arrTeaserFrame['name'],
                    'NEWS_TEASER_FRAME_TEMPLATE_PLACEHOLDER'    => '[[TEASERS_'.strtoupper($arrTeaserFrame['name']).']]',
                    'NEWS_TEASER_FRAME_ID'                  => $teaserFrameId,
                    'NEWS_TEASER_FRAME_TPL_NAME'                    => $this->_objTeaser->arrTeaserFrameTemplates[$arrTeaserFrame['frame_template_id']]['description'],
                    'NEWS_TEASER_FRAME_TPL_ID'                  => $arrTeaserFrame['frame_template_id']
                ));
                $this->_objTpl->parse('news_teaser_frames_list');
                $rowNr++;
            }
            $this->_objTpl->parse('news_teasers_block');
        } else {
            $this->_editTeaserFrame();
        }
    }


    function _showTeaserFrame()
    {
        $this->_objTpl->addBlockFile('NEWS_TEASERS_FILE', 'news_teasers_block', 'module_news_teasers_show_frame.html');
        $teaserFrameId = intval($_REQUEST['frameId']);
        $this->_objTpl->setVariable('NEWS_TEASER_FRAME', $this->_objTeaser->getTeaserFrame($teaserFrameId));
        $this->_objTpl->parse('news_teasers_block');
    }


    function _updateTeaserFrame()
    {
        global $_ARRAYLANG;

        if (isset($_POST['saveTeaserFrame']) && isset($_GET['frameId']) && isset($_POST['teaserFrameName']) && isset($_POST['teaserFrameTemplateId'])) {
            $id = intval($_GET['frameId']);
            $name = preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['teaserFrameName']);
            $name = contrexx_strip_tags($name);
            $templateId = intval($_POST['teaserFrameTemplateId']);
            if (empty($name)) {
                $this->strErrMessage .= $_ARRAYLANG['TXT_SET_FRMAE_NAME_TEXT'];
                $this->_editTeaserFrame();
                return;
            } elseif (!$this->_objTeaser->isUniqueFrameName($id, $name)) {
                $this->strErrMessage .= $_ARRAYLANG['TXT_BOX_NAME_EXISTS_TEXT'];
                $this->_editTeaserFrame();
                return;
            }
            if ($id != 0) {
                $this->_objTeaser->updateTeaserFrame($id, $templateId, $name);
                $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_TEASER_BOX_UPDATED'];
            } else {
                $this->_objTeaser->addTeaserFrame($id, $templateId, $name);
                $this->strOkMessage = $_ARRAYLANG['TXT_NEWS_TEASER_BOX_ADDED'];
            }
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array(
                    'Widget',
                    $this->getNewsGlobalPlaceholderNames()
                )
            );
            $this->_objTeaser->initializeTeaserFrames($id);
            $this->_showTeaserFrames();
        } elseif (isset($_POST['cancel']) && isset($_GET['frameId']) && ($_GET['frameId'] == 0)) {
            $this->_showTeaserFrames();
        } elseif (isset($_POST['cancel']) && isset($_GET['frameId'])) {
            $this->_showTeaserFrame();
        } else {
            $this->_editTeaserFrame();
        }
    }


    function _editTeaserFrame()
    {
        global $_ARRAYLANG;

        $this->_objTpl->addBlockFile('NEWS_TEASERS_FILE', 'news_teasers_block', 'module_news_teasers_modify_frame.html');
        $this->_objTpl->setVariable(array(
            'TXT_BOX_NAME'      => $_ARRAYLANG['TXT_BOX_NAME'],
            'TXT_BOX_TEMPLATE'  => $_ARRAYLANG['TXT_BOX_TEMPLATE'],
            'TXT_CANCEL'            => $_ARRAYLANG['TXT_CANCEL'],
            'TXT_SAVE'              => $_ARRAYLANG['TXT_SAVE']
        ));
        // get teaser frame id
        if (isset($_GET['frameId'])) {
            $teaserFrameId = intval($_GET['frameId']);
        } else {
            $teaserFrameId = 0;
        }
        // set teaser frame name
        if (isset($_POST['teaserFrameName'])) {
            $teaserFrameName = preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['teaserFrameName']);
            $teaserFrameName = htmlentities(contrexx_strip_tags($teaserFrameName), ENT_QUOTES, CONTREXX_CHARSET);
        } elseif (isset($this->_objTeaser->arrTeaserFrames[$teaserFrameId])) {
            $teaserFrameName = $this->_objTeaser->arrTeaserFrames[$teaserFrameId]['name'];
        } else {
            $teaserFrameName = '';
        }
        // set teaser frame template
        if (isset($_POST['teaserFrameTemplateId'])) {
            $teaserFrameTemplateId = intval($_POST['teaserFrameTemplateId']);
        } elseif (isset($this->_objTeaser->arrTeaserFrames[$teaserFrameId])) {
            $teaserFrameTemplateId = $this->_objTeaser->arrTeaserFrames[$teaserFrameId]['frame_template_id'];
        } else {
            $teaserFrameTemplateId = $this->_objTeaser->getFirstTeaserFrameTemplateId();
        }
        $this->pageTitle = $teaserFrameId != 0 ? $_ARRAYLANG['TXT_EDIT_TEASER_BOX'] : $_ARRAYLANG['TXT_ADD_TEASER_BOX'];
        $this->_objTpl->setVariable(array(
            'NEWS_TEASER_FRAME_ID'              => $teaserFrameId,
            'NEWS_TEASER_FRAME_NAME'            => $teaserFrameName,
            'NEWS_TEASER_FRAME_TEMPLATE_MENU'   => $this->_objTeaser->getTeaserFrameTemplateMenu($teaserFrameTemplateId),
            'NEWS_TEASER_FRAME_PREVIEW'         => $this->_objTeaser->_getTeaserFrame($teaserFrameId, $teaserFrameTemplateId),
            'NEWS_TEASER_TITLE_TXT'             => $teaserFrameId != 0 ? $_ARRAYLANG['TXT_EDIT_TEASER_BOX'] : $_ARRAYLANG['TXT_ADD_TEASER_BOX']
        ));
        $this->_objTpl->parse('news_teasers_block');
    }

    /**
     * Validate the News entries While Add/Edit News
     *
     * @param array $locales
     * @param array $newsCategories
     *
     * @global array $_ARRAYLANG
     *
     * @return boolean
     */
    function validateNews($locales, $newsCategories)
    {
        global $_ARRAYLANG;

        $validationStatus = true;
        //Validating Title
        if (!empty($locales['active'])) {
            foreach ($locales['active'] as $activeLanguage => $value ) {
                $title = trim($locales['title'][$activeLanguage]);
                if (empty($title)) {
                    $this->strErrMessage = $_ARRAYLANG['TXT_NEWS_ERR_SAVING_FORM']
                        .'. '. $_ARRAYLANG['TXT_NEWS_NO_TITLE'];
                    $validationStatus = false;
                }
            }
        }
        //Validating Categories
        if (empty($newsCategories)) {
            $this->strErrMessage = ($validationStatus)
                ? $_ARRAYLANG['TXT_NEWS_ERR_SAVING_FORM']
                    . '. ' . $_ARRAYLANG['TXT_NEWS_NO_CATEGORY']
                : $_ARRAYLANG['TXT_NEWS_ERR_SAVING_FORM']
                    .'. '. $_ARRAYLANG['TXT_NEWS_NO_TITLE_AND_CATEGORY'];
            $validationStatus = false;
        }
        return $validationStatus;
    }

    /**
     * Get the MediaBrowser button
     *
     * @global array $_ARRAYLANG
     *
     * @param string $options      mediabrowser options
     * @param string $callBackName callback javascript function name
     *
     * @return mixed boolean|string
     */
    public static function getMediaBrowserButton($options, $callBackName = '') {
        if (empty($options)) {
            return false;
        }

        global $_ARRAYLANG;

        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions($options);
        if (!empty($callBackName)) {
            $mediaBrowser->setCallback($callBackName);
        }

        return $mediaBrowser->getXHtml($_ARRAYLANG['TXT_BROWSE']);
    }
}
