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
 * DocSys
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_docsys
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\DocSys\Controller;
/**
 * DocSys
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_docsys
 */
class DocSysManager extends DocSysLibrary
{

    var $_objTpl;
    var $pageTitle;
    var $pageContent;
    var $strErrMessage = '';
    var $strOkMessage = '';
    var $langId;
    private $act = '';

    /**
     * Constructor
     * @param  string
     * @access public
     */
    function __construct()
    {
        global $_ARRAYLANG, $objInit;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH . '/DocSys/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->pageTitle = $_ARRAYLANG['TXT_DOC_SYS_MANAGER'];
        $this->langId = $objInit->userFrontendLangId;
    }

    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION",
            "<a href='index.php?cmd=DocSys" . MODULE_INDEX . "' class='" .
            ($this->act == '' ? 'active' : '') . "'>" .
            $_ARRAYLANG['TXT_DOC_SYS_MENU_OVERVIEW'] . "</a>
            <a href='index.php?cmd=DocSys" . MODULE_INDEX . "&amp;act=add' class='" .
            ($this->act == 'add' ? 'active' : '') . "'>" .
            $_ARRAYLANG['TXT_CREATE_DOCUMENT'] . "</a>
            <a href='index.php?cmd=DocSys" . MODULE_INDEX . "&amp;act=cat' class='" .
            ($this->act == 'cat' ? 'active' : '') . "'>" .
            $_ARRAYLANG['TXT_CATEGORY_MANAGER'] . "</a>");
    }

    /**
     * Do the requested action
     * @return    string    parsed content
     */
    function getDocSysPage()
    {
        global $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act'] = "";
        }
        switch ($_GET['act']) {
            case "add":
                $this->add();
                // $this->overview();
                break;
            case "edit":
                $this->edit();
                break;
            case "delete":
                $this->delete();
                $this->overview();
                break;
            case "update":
                $this->update();
                $this->overview();
                break;
            case "cat":
                $this->manageCategories();
                break;
            case "delcat":
                $this->deleteCat();
                $this->manageCategories();
                break;
            case "changeStatus":
                $this->changeStatus();
                $this->overview();
                break;
            default:
                $this->overview();
        }
        $objTemplate->setVariable(array(
            'CONTENT_TITLE' => $this->pageTitle,
            'CONTENT_OK_MESSAGE' => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE' => $this->strErrMessage,
            'ADMIN_CONTENT' => $this->_objTpl->get()
        ));
        $this->act = $_GET['act'];
        $this->setNavigation();
    }

    /**
     * Overview
     * @global     ADONewConnection
     * @global     array
     * @global     array
     * @param     integer   $newsid
     * @param     string       $what
     * @return    string    $output
     */
    function overview()
    {
        global $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->loadTemplateFile('module_docsys_list.html', true, true);
        // Global module index for clones
        $this->_objTpl->setGlobalVariable('MODULE_INDEX', MODULE_INDEX);
        $this->pageTitle = $_ARRAYLANG['TXT_DOC_SYS_MANAGER'];
        $this->_objTpl->setGlobalVariable($_ARRAYLANG + array(
            'TXT_EDIT_DOCSYS_MESSAGE' => $_ARRAYLANG['TXT_EDIT_DOCUMENTS'],
            'TXT_EDIT_DOCSYS_ID' => $_ARRAYLANG['TXT_DOCUMENT_ID'],
            'TXT_CONFIRM_DELETE_DATA' => $_ARRAYLANG['TXT_DOCUMENT_DELETE_CONFIRM'],
        ));
        $pos = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $count = $this->countAllEntries();
        $tries = 2;
        while ($tries--) {
            $entries = $this->getAllEntries($pos);
            if ($entries) break;
            $pos = 0;
        }
        $paging = ($count > intval($_CONFIG['corePagingLimit'])
            ? getPaging($count, $pos, '&cmd=DocSys' . MODULE_INDEX,
                $_ARRAYLANG['TXT_DOCSYS_DOCUMENTS'], true) : '');
        $row = 1;
        $this->_objTpl->setCurrentBlock('row');
        if (!$entries) return;
        foreach ($entries as $entry) {
            $this->_objTpl->setVariable(array(
                'DOCSYS_ID' => $entry['id'],
                'DOCSYS_DATE' => date(ASCMS_DATE_FORMAT, $entry['date']),
                'DOCSYS_TITLE' => stripslashes($entry['title']),
                'DOCSYS_AUTHOR' => stripslashes($entry['author']),
                'DOCSYS_USER' => $entry['username'],
                'DOCSYS_CHANGELOG' => date(ASCMS_DATE_FORMAT,
                    $entry['changelog']),
                'DOCSYS_PAGING' => $paging,
                'DOCSYS_CLASS' => ($row++ % 2) + 1,
                'DOCSYS_CATEGORY' => join('<br />', $entry['categories']),
                'DOCSYS_STATUS' => $entry['status'],
                'DOCSYS_STATUS_PICTURE' => ($entry['status'] == 1)
                    ? "status_green.gif" : "status_red.gif",
            ));
            $this->_objTpl->parseCurrentBlock("row");
        }
    }

    function _getSortingDropdown($catID, $sorting = 'alpha')
    {
        global $_ARRAYLANG;
        return '
            <select name="sortStyle[' . $catID . ']">
                <option value="alpha" ' . ($sorting
            == 'alpha' ? 'selected="selected"' : '') . ' >' . $_ARRAYLANG['TXT_DOCSYS_SORTING_ALPHA'] . '</option>
                <option value="date" ' . ($sorting
            == 'date' ? 'selected="selected"' : '') . '>' . $_ARRAYLANG['TXT_DOCSYS_SORTING_DATE'] . '</option>
                <option value="date_alpha" ' . ($sorting
            == 'date_alpha' ? 'selected="selected"' : '') . '>' . $_ARRAYLANG['TXT_DOCSYS_SORTING_DATE_ALPHA'] . '</option>
            </select>
        ';
    }

    /**
     * Add an entry
     * @global    ADONewConnection
     * @global    array
     * @param     integer   $newsid -> the id of the news entry
     * @return    boolean   result
     */
    function add()
    {
        global $_ARRAYLANG;

        \JS::activate('jqueryui');
        $objFWUser = \FWUser::getFWUserObject();
        $this->pageTitle = $_ARRAYLANG['TXT_CREATE_DOCUMENT'];
        $this->_objTpl->loadTemplateFile('module_docsys_modify.html', true, true);
        // Global module index for clones
        $this->_objTpl->setGlobalVariable($_ARRAYLANG + array(
            'MODULE_INDEX' => MODULE_INDEX,
            'TXT_DOCSYS_MESSAGE' => $_ARRAYLANG['TXT_ADD_DOCUMENT'],
            'TXT_DOCSYS_CONTENT' => $_ARRAYLANG['TXT_CONTENT'],
        ));
        $this->_objTpl->setVariable(array(
            'DOCSYS_TEXT' => new \Cx\Core\Wysiwyg\Wysiwyg('docSysText', null, 'full'),
            'DOCSYS_FORM_ACTION' => "add",
            'DOCSYS_STORED_FORM_ACTION' => "add",
            'DOCSYS_STATUS' => "checked='checked'",
            'DOCSYS_ID' => "",
            'DOCSYS_TOP_TITLE' => $_ARRAYLANG['TXT_CREATE_DOCUMENT'],
            'DOCSYS_CAT_MENU' => $this->getCategoryMenu($this->langId),
            'DOCSYS_STARTDATE' => "",
            'DOCSYS_ENDDATE' => "",
            'DOCSYS_DATE' => date(ASCMS_DATE_FORMAT, time()),
            'TXT_AUTHOR' => $_ARRAYLANG['TXT_AUTHOR'],
            'DOCSYS_AUTHOR' => htmlentities($objFWUser->objUser->getUsername(),
                ENT_QUOTES, CONTREXX_CHARSET),
        ));
        if (!empty($_POST['docSysTitle'])) {
            $this->insert();
            $this->createRSS();
        }
    }

    /**
     * Deletes an entry
     * @global     ADONewConnection
     * @global     array
     * @return    -
     */
    function delete()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_GET['id'])) {
            $docSysId = intval($_GET['id']);

            $query = "DELETE FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . " WHERE id = $docSysId";

            if ($objDatabase->Execute($query)) {
                $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                $this->createRSS();
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }

        if (isset($_POST['selectedId']) && is_array($_POST['selectedId'])) {
            foreach ($_POST['selectedId'] as $value) {
                if (!empty($value)) {
                    if ($objDatabase->Execute("DELETE FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . " WHERE id = " . intval($value))) {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                        $this->createRSS();
                    } else {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    }
                }
            }
        }
    }

    /**
     * Edit an entry
     * @global    ADONewConnection
     * @global    array
     * @param     string     $pageContent
     */
    function edit()
    {
        global $objDatabase, $_ARRAYLANG;

        $status = "";
        $this->_objTpl->loadTemplateFile('module_docsys_modify.html', true, true);
        // Global module index for clones
        $this->_objTpl->setGlobalVariable('MODULE_INDEX', MODULE_INDEX);
        $this->pageTitle = $_ARRAYLANG['TXT_EDIT_DOCUMENTS'];

        $this->_objTpl->setVariable(array(
            'TXT_DOCSYS_MESSAGE' => $_ARRAYLANG['TXT_EDIT_DOCUMENTS'],
            'TXT_TITLE' => $_ARRAYLANG['TXT_TITLE'],
            'TXT_CATEGORY' => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_HYPERLINKS' => $_ARRAYLANG['TXT_HYPERLINKS'],
            'TXT_EXTERNAL_SOURCE' => $_ARRAYLANG['TXT_EXTERNAL_SOURCE'],
            'TXT_LINK' => $_ARRAYLANG['TXT_LINK'],
            'TXT_DOCSYS_CONTENT' => $_ARRAYLANG['TXT_CONTENT'],
            'TXT_STORE' => $_ARRAYLANG['TXT_STORE'],
            'TXT_PUBLISHING' => $_ARRAYLANG['TXT_PUBLISHING'],
            'TXT_STARTDATE' => $_ARRAYLANG['TXT_STARTDATE'],
            'TXT_ENDDATE' => $_ARRAYLANG['TXT_ENDDATE'],
            'TXT_OPTIONAL' => $_ARRAYLANG['TXT_OPTIONAL'],
            'TXT_DATE' => $_ARRAYLANG['TXT_DATE'],
            'TXT_ACTIVE' => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_AUTHOR' => $_ARRAYLANG['TXT_AUTHOR'],
        ));
        $id = intval($_REQUEST['id']);
        $query = "SELECT   `lang`,
                           `date`,
                           `id`,
                           `title`,
                           `author`,
                           `text`,
                           `source`,
                           `url1`,
                           `url2`,
                           `startdate`,
                           `enddate`,
                           `status`
                      FROM `" . DBPREFIX . "module_docsys" . MODULE_INDEX . "`
                     WHERE id = '$id'";
        $objResult = $objDatabase->SelectLimit($query, 1);

        if (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            $docSysText = stripslashes($objResult->fields['text']);

            if ($objResult->fields['status'] == 1) {
                $status = "checked";
            }

            $this->_objTpl->setVariable(array(
                'DOCSYS_ID' => $id,
                'DOCSYS_STORED_ID' => $id,
                'DOCSYS_TITLE' => stripslashes(htmlspecialchars($objResult->fields['title'],
                        ENT_QUOTES, CONTREXX_CHARSET)),
                'DOCSYS_AUTHOR' => stripslashes(htmlspecialchars($objResult->fields['author'],
                        ENT_QUOTES, CONTREXX_CHARSET)),
                'DOCSYS_TEXT' => new \Cx\Core\Wysiwyg\Wysiwyg('docSysText',
                    $docSysText, 'full'),
                'DOCSYS_SOURCE' => $objResult->fields['source'],
                'DOCSYS_URL1' => $objResult->fields['url1'],
                'DOCSYS_URL2' => $objResult->fields['url2'],
                'DOCSYS_STARTDATE' => !empty($objResult->fields['startdate']) ? date(ASCMS_DATE_FORMAT,
                        $objResult->fields['startdate']) : '',
                'DOCSYS_ENDDATE' => !empty($objResult->fields['enddate']) ? date(ASCMS_DATE_FORMAT,
                        $objResult->fields['enddate']) : '',
                'DOCSYS_STATUS' => $status,
                'DOCSYS_DATE' => date(ASCMS_DATE_FORMAT,
                    $objResult->fields['date'])
            ));
        }

        $categories = $this->getCategories($id);

        $this->_objTpl->setVariable("DOCSYS_CAT_MENU",
            $this->getCategoryMenu($this->langId, $categories));
        $this->_objTpl->setVariable("DOCSYS_FORM_ACTION", "update");
        $this->_objTpl->setVariable("DOCSYS_STORED_FORM_ACTION", "update");
        $this->_objTpl->setVariable("DOCSYS_TOP_TITLE", $_ARRAYLANG['TXT_EDIT']);
    }

    /**
     * Update an entry
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @return    boolean   result
     */
    function update()
    {
        global $objDatabase, $_ARRAYLANG;

        if (empty($_POST['docSysId'])) {
// TODO: As this method may currently be called due to a bug in Paging::get()
// the error message cannot be shown yet.
//            $this->strErrMessage = $_ARRAYLANG['TXT_DOCSYS_ERROR_MISSING_ID'];
            return;
        }
        $id = intval($_GET['id']);
        $changelog = time();
        $title = (empty($_POST['docSysTitle']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysTitle'])));
//        $title = str_replace("ß", "ss", $title);
        $text = (empty($_POST['docSysText']) ? ''
             : contrexx_input2raw($_POST['docSysText']));
        $text = $this->filterBodyTag($text);
//        $text = str_replace("ß", "ss", $text);
        $author = (empty($_POST['author']) ? ''
             : strip_tags(contrexx_input2raw($_POST['author'])));
        $source = (empty($_POST['docSysSource']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysSource'])));
        $url1 = (empty($_POST['docSysUrl1']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysUrl1'])));
        $url2 = (empty($_POST['docSysUrl2']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysUrl2'])));
        $status = (empty($_POST['status']) ? 0 : 1);
        $startDate = '';
        $arrDate = NULL;
        if (preg_match('/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})\s*([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})/',
                $_POST['startDate'], $arrDate)) {
            $startDate = mktime(intval($arrDate[1]), intval($arrDate[2]),
                intval($arrDate[3]), intval($arrDate[5]),
                intval($arrDate[4]), intval($arrDate[6]));
        }
        $endDate = '';
        if (preg_match('/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})\s*([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})/',
                $_POST['endDate'], $arrDate)) {
            $endDate = mktime(intval($arrDate[1]), intval($arrDate[2]),
                intval($arrDate[3]), intval($arrDate[5]),
                intval($arrDate[4]), intval($arrDate[6]));
        }
        if (!$objDatabase->Execute("
            UPDATE " . DBPREFIX . "module_docsys" . MODULE_INDEX . "
               SET title=?,
                   date=?,
                   author=?,
                   text=?,
                   source=?,
                   url1=?,
                   url2=?,
                   lang=?,
                   userid=?,
                   status=?,
                   startdate=?,
                   enddate=?,
                   changelog=?
             WHERE id=?",
            array($title, $this->_checkDate($_POST['creation_date']),
                $author, $text, $source, $url1, $url2, $this->langId,
                \FWUser::getFWUserObject()->objUser->getId(),
                $status, $startDate, $endDate, $changelog, $id))
        ) {
            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
        } else {
            $this->createRSS();
            $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
        }
        if (!$this->removeCategories($id)) {
            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'] . ": " . $objDatabase->ErrorMsg();
        } else {
            if (!$this->assignCategories($id, $_POST['docSysCat'])) {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'] . ": " . $objDatabase->ErrorMsg();
            }
        }
    }

    /**
     * Change an entry's status
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @param     integer   $newsid
     * @return    boolean   result
     */
    function changeStatus()
    {
        global $objDatabase, $_ARRAYLANG;

        $status = (!empty($_POST['deactivate'])
            ? 0 : (!empty($_POST['activate']) ? 1 : NULL));
        if (isset($status) && !empty($_POST['selectedId'])) {
            foreach ($_POST['selectedId'] as $value) {
                if (!empty($value)) {
                    $retval = $objDatabase->Execute("
                        UPDATE " . DBPREFIX . "module_docsys" . MODULE_INDEX . "
                           SET status='$status'
                         WHERE id=" . intval($value));
                }
                if (!$retval) {
                    $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                } else {
                    $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
                }
            }
        }
    }

    /**
     * checks if date is valid
     * @param string $date
     * @return integer $timestamp
     */
    function _checkDate($date)
    {
        $arrDate = NULL;
        if (preg_match('/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})\s*([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})/',
                $date, $arrDate)) {
            return mktime(intval($arrDate[1]), intval($arrDate[2]),
                intval($arrDate[3]), intval($arrDate[5]), intval($arrDate[4]),
                intval($arrDate[6]));
        } else {
            return time();
        }
    }

    /**
     * Insert a new entry
     * @global    ADONewConnection
     * @global    array
     * @return    boolean   result
     */
    function insert()
    {
        global $objDatabase, $_ARRAYLANG;

        $date = $this->_checkDate($_POST['creation_date']);
        $title = (empty($_POST['docSysTitle']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysTitle'])));
//        $title = str_replace("ß", "ss", $title);
        $text = (empty($_POST['docSysText']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysText'])));
        $text = $this->filterBodyTag($text);
//        $text = str_replace("ß", "ss", $text);
        $author = (empty($_POST['author']) ? ''
             : strip_tags(contrexx_input2raw($_POST['author'])));
        $source = (empty($_POST['docSysSource']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysSource'])));
        $url1 = (empty($_POST['docSysUrl1']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysUrl1'])));
        $url2 = (empty($_POST['docSysUrl2']) ? ''
             : strip_tags(contrexx_input2raw($_POST['docSysUrl2'])));
        $status = (empty($_POST['status']) ? 0 : 1);
        $startDate = '';
        $arrDate = NULL;
        if (preg_match('/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})\s*([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})/',
                $_POST['startDate'], $arrDate)) {
            $startDate = mktime(intval($arrDate[1]), intval($arrDate[2]),
                intval($arrDate[3]), intval($arrDate[5]),
                intval($arrDate[4]), intval($arrDate[6]));
        }
        $endDate = '';
        if (preg_match('/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})\s*([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})/',
                $_POST['endDate'], $arrDate)) {
            $endDate = mktime(intval($arrDate[1]), intval($arrDate[2]),
                intval($arrDate[3]), intval($arrDate[5]),
                intval($arrDate[4]), intval($arrDate[6]));
        }
        if ($objDatabase->Execute("
            INSERT INTO " . DBPREFIX . "module_docsys" . MODULE_INDEX . " (
                `date`, `title`, `author`, `text`, `source`,
                `url1`, `url2`, `lang`, `startdate`, `enddate`,
                `status`, `userid`, `changelog`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )",
            array($date, $title, $author, $text, $source,
                $url1, $url2, $this->langId, $startDate, $endDate,
                $status, \FWUser::getFWUserObject()->objUser->getId(),
                $date))
        ) {
            $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL'];
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
        }
        $id = $objDatabase->Insert_ID();
        if (!$this->assignCategories($id, $_POST['docSysCat'])) {
            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'] . ": " . $objDatabase->ErrorMsg();
        }
        $this->overview();
    }


    /**
     * Add or edit categories
     * @global    ADONewConnection
     * @global    array
     * @param     string     $pageContent
     */
    function manageCategories()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_docsys_category.html', true,
            true);
        // Global module index for clones
        $this->_objTpl->setGlobalVariable('MODULE_INDEX', MODULE_INDEX);
        $this->pageTitle = $_ARRAYLANG['TXT_CATEGORY_MANAGER'];
        $this->_objTpl->setVariable(array(
            'TXT_ADD_NEW_CATEGORY' => $_ARRAYLANG['TXT_ADD_NEW_CATEGORY'],
            'TXT_NAME' => $_ARRAYLANG['TXT_NAME'],
            'TXT_ADD' => $_ARRAYLANG['TXT_ADD'],
            'TXT_CATEGORY_LIST' => $_ARRAYLANG['TXT_CATEGORY_LIST'],
            'TXT_ID' => $_ARRAYLANG['TXT_ID'],
            'TXT_ACTION' => $_ARRAYLANG['TXT_ACTION'],
            'TXT_ACCEPT_CHANGES' => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_CONFIRM_DELETE_DATA' => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK' => $_ARRAYLANG['TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK'],
            'TXT_DOCSYS_SORTING' => $_ARRAYLANG['TXT_DOCSYS_SORTING'],
            'TXT_DOCSYS_SORTTYPE' => $_ARRAYLANG['TXT_DOCSYS_SORTTYPE'],
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_DELETE' => $_ARRAYLANG['TXT_DELETE'],
        ));
        // Add a new category
        if (isset($_POST['addCat']) AND ($_POST['addCat'] == true)) {
            $catName = addslashes(strip_tags($_POST['newCatName']));
            if ($objDatabase->Execute("INSERT INTO " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_categories (name,lang)
                                 VALUES ('$catName','$this->langId')")) {
                $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL'];
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
        // Modify a category
        if (isset($_POST['modCat']) AND ($_POST['modCat'] == true)) {
            foreach ($_POST['catName'] as $id => $name) {
                $name = addslashes(strip_tags($name));
                $id = intval($id);

                $sorting = !empty($_REQUEST['sortStyle'][$id]) ? contrexx_addslashes($_REQUEST['sortStyle'][$id])
                        : 'alpha';

                if ($objDatabase->Execute("UPDATE " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_categories
                                  SET name='$name',
                                      lang='$this->langId',
                                      sort_style='$sorting'
                                WHERE catid=$id")) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                }
            }
        }
        $query = "SELECT `catid`,
                           `name`,
                           `sort_style`
                      FROM `" . DBPREFIX . "module_docsys" . MODULE_INDEX . "_categories`
                     WHERE `lang`='$this->langId'
                  ORDER BY `catid` asc";
        $objResult = $objDatabase->Execute($query);
        $this->_objTpl->setCurrentBlock('row');
        $i = 0;
        while (!$objResult->EOF) {
            $class = (($i % 2) == 0) ? "row1" : "row2";
            $sorting = $objResult->fields['sort_style'];
            $this->_objTpl->setVariable(array(
                'DOCSYS_ROWCLASS' => $class,
                'DOCSYS_CAT_ID' => $objResult->fields['catid'],
                'DOCSYS_CAT_NAME' => stripslashes($objResult->fields['name']),
                'DOCSYS_SORTING_DROPDOWN' => $this->_getSortingDropdown($objResult->fields['catid'],
                    $sorting),
            ));
            $this->_objTpl->parseCurrentBlock('row');
            $i++;
            $objResult->MoveNext();
        };
    }

    /**
     * Delete a category
     * @global    ADONewConnection
     * @global    array      $_ARRAYLANG
     */
    function deleteCat()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_GET['catId'])) {
            $catId = intval($_GET['catId']);
            $objResult = $objDatabase->Execute('SELECT `entry` FROM `' . DBPREFIX . 'module_docsys' . MODULE_INDEX . '_entry_category` WHERE `category`=' . $catId);

            if ($objResult) {
                if ($objResult->RecordCount() == 0) {
                    if ($objDatabase->Execute('DELETE FROM `' . DBPREFIX . 'module_docsys' . MODULE_INDEX . '_categories` WHERE `catid` = ' . $catId)) {
                        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
                    } else {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                    }
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_CATEGORY_NOT_DELETED_BECAUSE_IN_USE'];
                }
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }
    }

    /**
     * Gets only the body content and deleted all the other tags
     * @param     string     $fullContent      HTML-Content with more than BODY
     * @return    string     $content          HTML-Content between BODY-Tag
     */
    function filterBodyTag($fullContent)
    {
        $res = false;
        $posBody = 0;
        $posStartBodyContent = 0;
        $arrayMatches = NULL;
        $res = preg_match_all("/<body[^>]*>/i", $fullContent, $arrayMatches);
        if ($res == true) {
            $bodyStartTag = $arrayMatches[0][0];
            // Position des Start-Tags holen
            $posBody = strpos($fullContent, $bodyStartTag, 0);
            // Beginn des Contents ohne Body-Tag berechnen
            $posStartBodyContent = $posBody + strlen($bodyStartTag);
        }
        $posEndTag = strlen($fullContent);
        $res = preg_match_all("/<\/body>/i", $fullContent, $arrayMatches);
        if ($res == true) {
            $bodyEndTag = $arrayMatches[0][0];
            // Position des End-Tags holen
            $posEndTag = strpos($fullContent, $bodyEndTag, 0);
            // Content innerhalb der Body-Tags auslesen
        }
        $content = substr($fullContent, $posStartBodyContent,
            $posEndTag - $posStartBodyContent);
        return $content;
    }

    /**
     * Create the RSS-Feed
     */
    function createRSS()
    {
        \Env::get('ClassLoader')->loadFile(ASCMS_MODULE_PATH . '/DocSys/Controller/RssFeed.class.php');
        $objRssFeed = new RssFeed();
        $objRssFeed->channelTitle = "Dokumentensystem";
        $objRssFeed->channelDescription = "";
        $objRssFeed->xmlType = "headlines";
        $objRssFeed->createXML();
        $objRssFeed->xmlType = "fulltext";
        $objRssFeed->createXML();
    }

}
