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
 * Class Document System
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  module_docsys
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\DocSys\Controller;
/**
 * Class Document System
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  module_docsys
 * @todo        Edit PHP DocBlocks!
 */
class DocSysLibrary
{

    /**
     * Gets the categorie option menu string
     * @global    ADONewConnection
     * @param     string     $lang
     * @param     string     $selectedOption
     * @return    string     $modulesMenu
     */
    function getCategoryMenu($langId, $selectedCatIds = array())
    {
        global $objDatabase;

        $strMenu = "";
        $query = "
            SELECT catid, name
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_categories
             WHERE lang=$langId
             ORDER BY catid";
        $objResult = $objDatabase->Execute($query);
        while ($objResult && !$objResult->EOF) {
            $selected = "";
            if (array_search($objResult->fields['catid'], $selectedCatIds) !== false) {
                $selected = "selected";
            }
            $strMenu .= "
                <option value=\"" . $objResult->fields['catid'] . "\" $selected>" .
                stripslashes($objResult->fields['name']) . "</option>\n";
            $objResult->MoveNext();
        }
        return $strMenu;
    }

    /**
     * Get all categories of a entry
     * @param int $id
     * @author Stefan Heinemann <sh@comvation.com>
     */
    protected function getCategories($id)
    {
        global $objDatabase;

        $id = intval($id);
        $query = "
            SELECT category
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_entry_category
             WHERE entry=$id";
        $objResult = $objDatabase->Execute($query);
        if ($objResult === false) {
            return false;
        }
        $retval = array();
        if ($objResult->RecordCount()) {
            while (!$objResult->EOF) {
                $retval[] = $objResult->fields['category'];
                $objResult->MoveNext();
            }
        }
        return $retval;
    }

    /**
     * Return all entries with their category names and user name
     * @param int $pos the position to start with (vor paging)
     * @return array
     * @author Stefan Heinemann <sh@comvation.com>
     */
    protected function getAllEntries($pos)
    {
        global $objDatabase, $_CONFIG;

        $query = "
            SELECT entry.id, entry.date, entry.author, entry.title,
                   entry.status, entry.changelog, users.username
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . " AS entry
              LEFT JOIN " . DBPREFIX . "access_users as users ON entry.userid=users.id
             WHERE entry.lang=$this->langId
             ORDER BY entry.id";
        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'],
            intval($pos));
        if (!$objResult) {
            return false;
        }
        $retval = array();
        while (!$objResult->EOF) {
            $retval[$objResult->fields['id']] = array(
                "id" => $objResult->fields['id'],
                "date" => $objResult->fields['date'],
                "author" => $objResult->fields['author'],
                "title" => $objResult->fields['title'],
                "status" => $objResult->fields['status'],
                "changelog" => $objResult->fields['changelog'],
                "username" => $objResult->fields['username'],
            );
            $objResult->MoveNext();
        }
        $query = "
            SELECT entry.id, cat.name
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . " AS entry
              LEFT JOIN " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_entry_category AS joined
                ON entry.id=joined.entry
              LEFT JOIN " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_categories AS cat
                ON joined.category=cat.catid
             WHERE entry.lang=$this->langId
                 AND entry.id IN (".join(',', array_keys($retval)).")
             ORDER BY entry.id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return false;
        }
        while (!$objResult->EOF) {
            $retval[$objResult->fields['id']]['categories'][] =
                $objResult->fields['name'];
            $objResult->MoveNext();
        }
        return $retval;
    }

    /**
     * Count all entries (for paging)
     * @return int
     * @author Stefan Heinemann <sh@comvation.com>
     */
    protected function countAllEntries()
    {
        global $objDatabase;

        $query = "
            SELECT COUNT(id) AS count
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . "
             WHERE lang=$this->langId";
        $objResult = $objDatabase->Execute($query);
        if ($objResult === false) {
            return false;
        }
        return intval($objResult->fields['count']);
    }

    /**
     * Assign categories to an entry
     * @param int $entry
     * @param array $categories Array of integers of the categories' ids
     * @return boolean Success
     * @author Stefan Heinemann <sh@comvation.com>
     */
    protected function assignCategories($entry, $categories)
    {
        global $objDatabase;

        $entry = intval($entry);
        $err = false;
        foreach ($categories as $cat) {
            $query = "
                INSERT INTO " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_entry_category (
                    entry, category
                ) VALUES (
                    $entry, " . intval($cat) . "
                )";
            if (!$objDatabase->Execute($query)) {
                $err = true;
            }
        }
        return !$err;
    }

    /**
     * Remove an entry's categories
     * @param int $entry
     * @return boolean
     * @author Stefan Heinemann <sh@comvation.com>
     */
    protected function removeCategories($entry)
    {
        global $objDatabase;

        $entry = intval($entry);
        $query = "
            DELETE FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_entry_category
             WHERE entry=$entry";
        return $objDatabase->Execute($query);
    }

    /**
     * Get the entries for the frontend overview, according to the selected category
     * @param int $pos Position for limiting/paging
     * @param int $category The category to be shown
     * @param string $sortType Some sorting stuff
     * @return bool/array An array with entries on success, else the boolean false
     * @author Stefan Heinemann <sh@comvation.com>
     * @see docSys::getTitles
     */
    protected function getOverviewTitles($pos = 0, $category = null,
        $sortType = null)
    {
        global $objDatabase, $_CONFIG;

        if (isset($category) && !isset($sortType)) {
            throw new Exception("second argument needed");
        }
        $query = "
            SELECT DISTINCT entry.date, entry.id, entry.title, entry.author
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . " AS entry
              LEFT JOIN " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_entry_category AS j
                ON entry.id=j.entry
             WHERE entry.lang=$this->langId
               AND (startdate<=" . time() . " OR startdate=0)
               AND (enddate>=" . time() . " OR enddate=0)
               AND status = 1";
        if (isset($category)) {
            $category = intval($category);
            $query .= " AND j.category=$category";
            switch ($sortType) {
                case 'alpha':
                    $query .= " ORDER BY entry.title";
                    break;
                case 'date':
                    $query .= " ORDER BY entry.date DESC";
                    break;
                case 'date_alpha':
                    $query .= " ORDER BY DATE_FORMAT( FROM_UNIXTIME( `date` ) , '%Y%j' ) DESC, entry.title";
                    break;
                default:
                    $query .= " ORDER BY entry.date DESC";
            }
        } else {
            $query .= " ORDER BY entry.date DESC";
        }
        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'],
            $pos);
        if (!$objResult) {
            return false;
        }
        $retval = array();
        while (!$objResult->EOF) {
            $retval[$objResult->fields['id']] = array(
                "id" => $objResult->fields['id'],
                "date" => $objResult->fields['date'],
                "title" => $objResult->fields['title'],
                "author" => $objResult->fields['author'],
            );
            $objResult->MoveNext();
        }
        $query = "
            SELECT entry.id, cat.name
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . " AS entry
              LEFT JOIN " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_entry_category AS j
                ON entry.id=j.entry
              LEFT JOIN " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_categories AS cat
                ON j.category=cat.catid
             WHERE entry.lang=$this->langId
               AND entry.id IN (".join(',', array_keys($retval)).")".
            ($category ? " AND j.category=$category" : '');
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return false;
        }
        while (!$objResult->EOF) {
            $retval[$objResult->fields['id']]['categories'][] =
                $objResult->fields['name'];
            $objResult->MoveNext();
        }
        return $retval;
    }

    /**
     * Count the entries for a specific category ID.
     *
     * If no or an emtpy category ID is given (0), all entries ar counted.
     * This is used for paging.
     * @param int $category
     * @return int/boolean Amount of entries on success, else false
     * @author Stefan Heinemann <sh@comvation.com>
     * @see docSys::getTitles
     */
    protected function countOverviewEntries($category = null)
    {
        global $objDatabase;

        if (!isset($category)) {
            return $this->countAllEntries();
        }
        $category = intval($category);
        $query = "
            SELECT COUNT(id) AS count
              FROM " . DBPREFIX . "module_docsys" . MODULE_INDEX . " AS e
              LEFT JOIN " . DBPREFIX . "module_docsys" . MODULE_INDEX . "_entry_category as j
                ON e.id=j.entry
             WHERE j.category=$category
             AND `status` = 1";
        $objResult = $objDatabase->Execute($query);
        if ($objResult === false) {
            return false;
        }
        return intval($objResult->fields['count']);
    }

}
