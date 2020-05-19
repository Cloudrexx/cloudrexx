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
 *
 * This module will get all the docSys pages
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_docsys
 */
class DocSys extends DocSysLibrary
{

    var $docSysTitle;
    var $langId;
    var $dateFormat = 'd.m.Y';
    var $dateLongFormat = 'H:i:s d.m.Y';
    var $_objTpl;

    // CONSTRUCTOR
    function __construct($pageContent)
    {
        global $_LANGID;
        $this->pageContent = $pageContent;
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->langId = $_LANGID;
    }

    // GET PAGE
    function getdocSysPage()
    {
        if (!isset($_REQUEST['cmd'])) {
            $_REQUEST['cmd'] = '';
        }
        switch ($_REQUEST['cmd']) {
            case 'details':
                return $this->getDetails();
            default:
                return $this->getTitles();
        }
    }

    /**
     * Entry details
     * @global     array
     * @global     ADONewConnection
     * @global     array
     * @return    string    parsed content
     */
    function getDetails()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent);
        // Global module index for clones
        $this->_objTpl->setGlobalVariable('MODULE_INDEX', MODULE_INDEX);
        $id = intval($_GET['id']);
        if ($id <= 0) {
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ?section=DocSys" . MODULE_INDEX);
            exit;
        }
        $query = "
            SELECT id, source, changelog, url1, url2,
                   text, date, changelog, title, author
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . "
             WHERE status = 1
               AND id = $id
               AND lang=$this->langId
               AND (startdate<=" . time() . " OR startdate=0)
               AND (enddate>=" . time() . " OR enddate=0)";
        $objResult = $objDatabase->SelectLimit($query, 1);
        while ($objResult && !$objResult->EOF) {
            $lastUpdate = stripslashes($objResult->fields['changelog']);
            $date = stripslashes($objResult->fields['date']);
            $source = stripslashes($objResult->fields['source']);
            $url1 = stripslashes($objResult->fields['url1']);
            $url2 = stripslashes($objResult->fields['url2']);
            $docUrl = "";
            $docSource = "";
            $docLastUpdate = "";
            if (!empty($url1)) {
                $docUrl = $_ARRAYLANG['TXT_IMPORTANT_HYPERLINKS'] . '<br /><a target="new" href="' . $url1 . '" title="' . $url1 . '">' . $url1 . '</a><br />';
            }
            if (!empty($url2)) {
                $docUrl .= '<a target="new" href="' . $url2 . '">' . $url2 . '</a><br />';
            }
            if (!empty($source)) {
                $docSource = $_ARRAYLANG['TXT_SOURCE'] . '<br /><a target="new" href="' . $source . '" title="' . $source . '">' . $source . '</a><br />';
            }
            if (!empty($lastUpdate) AND $lastUpdate != $date) {
                $docLastUpdate = $_ARRAYLANG['TXT_LAST_UPDATE'] . "<br />" . date(ASCMS_DATE_FORMAT,
                        $lastUpdate);
            }
            $title = $objResult->fields['title'];
            $this->_objTpl->setVariable(array(
                'DOCSYS_DATE' => date(ASCMS_DATE_FORMAT, $date),
                'DOCSYS_TITLE' => stripslashes($title),
                'DOCSYS_AUTHOR' => stripslashes($objResult->fields['author']),
                'DOCSYS_TEXT' => stripslashes($objResult->fields['text']),
                'DOCSYS_LASTUPDATE' => $docLastUpdate,
                'DOCSYS_SOURCE' => $docSource,
                'DOCSYS_URL' => $docUrl));
            $objResult->MoveNext();
        }
        $this->docSysTitle = strip_tags(stripslashes($title));
        return $this->_objTpl->get();
    }

    /**
     * Gets the list with the headlines
     * @global    array
     * @global    ADONewConnection
     * @global    array
     * @return    string    parsed content
     */
    function getTitles()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        $selectedId = null;
        $paging = "";
        $pos = (isset($_GET['pos']) ? intval($_GET['pos']) : 0);
        if (!isset($_REQUEST['cmd'])) {
            $_REQUEST['cmd'] = '';
        }
        $this->_objTpl->setTemplate($this->pageContent);
        $this->_objTpl->setGlobalVariable('MODULE_INDEX', MODULE_INDEX);
        $sortType = '';
        if (!empty($_REQUEST['category'])) {
            $selectedId = intval($_REQUEST['category']);
            $query = "
                SELECT `sort_style`
                  FROM `" . DBPREFIX . "module_docsys" . MODULE_INDEX . "_categories`
                 WHERE `catid`=$selectedId";
            $objRS = $objDatabase->SelectLimit($query, 1);
            if (!$objRS) {
                die('database error. ' . $objDatabase->ErrorMsg());
            }
            $sortType = $objRS->fields['sort_style'];
        }
        $this->_objTpl->setVariable("DOCSYS_NO_CATEGORY",
            $_ARRAYLANG['TXT_CATEGORY']);
        $this->_objTpl->setVariable("DOCSYS_CAT_MENU",
            $this->getCategoryMenu($this->langId, array($selectedId)));
        $this->_objTpl->setVariable("TXT_PERFORM", $_ARRAYLANG['TXT_PERFORM']);
        $count = $this->countOverviewEntries($selectedId);
        $entries = $this->getOverviewTitles($pos, $selectedId, $sortType);
        if ($count > intval($_CONFIG['corePagingLimit'])) {
            $paging = getPaging($count, $pos, "&section=DocSys" . MODULE_INDEX,
                $_ARRAYLANG['TXT_DOCUMENTS'], true);
        }
        $this->_objTpl->setVariable("DOCSYS_PAGING", $paging);
        if ($count >= 1) {
            $row = 1;
            foreach ($entries as $entry) {
                $cmd =
                    (empty($_REQUEST['cmd']) ? '' : $_REQUEST['cmd'] . '_').
                    'details';
                $this->_objTpl->setVariable(array(
                    'DOCSYS_STYLE' => ($row++) % 2 + 1,
                    'DOCSYS_LONG_DATE' => date($this->dateLongFormat,
                        $entry['date']),
                    'DOCSYS_DATE' => date($this->dateFormat, $entry['date']),
                    'DOCSYS_LINK' => "<a href=\"" . CONTREXX_SCRIPT_PATH .
                        "?section=DocSys" . MODULE_INDEX . "&amp;cmd=$cmd&amp;id=" .
                        $entry['id'] . "\" title=\"" . contrexx_raw2xhtml($entry['title']) .
                        "\">" . contrexx_raw2xhtml($entry['title']) . "</a>",
                    'DOCSYS_CATEGORY' => contrexx_raw2xhtml(
                        current($entry['categories'])),
                    'DOCSYS_AUTHOR' => contrexx_raw2xhtml($entry['author']),
                ));
                $this->_objTpl->parse("row");
            }
            if ($this->_objTpl->blockExists('table')) {
                $this->_objTpl->parse("table");
            }
            if ($this->_objTpl->blockExists('nothing_found')) {
                $this->_objTpl->hideBlock("nothing_found");
            }
        } else {
            /*$this->_objTpl->setVariable(array(
                'DOCSYS_STYLE'      => 1,
                'DOCSYS_DATE'       => "",
                'DOCSYS_LINK'       => "",
                'DOCSYS_CATEGORY'   => $_ARRAYLANG['TXT_NO_DOCUMENTS_FOUND']
            ));
            $this->_objTpl->parse("row");*/
            $this->_objTpl->setVariable(array(
                "TXT_NO_DOCUMENTS_FOUND" => $_ARRAYLANG['TXT_NO_DOCUMENTS_FOUND'],
            ));
            if ($this->_objTpl->blockExists('nothing_found')) {
                $this->_objTpl->parse("nothing_found");
            }
            if ($this->_objTpl->blockExists('table')) {
                $this->_objTpl->hideBlock("table");
            }
        }
        return $this->_objTpl->get();
    }

}
