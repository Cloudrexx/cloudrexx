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
 * News library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * News library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class NewsLibrary
{
    /**
    * NestedSet object
    *
    * @access   protected
    * @var      DB_NestedSet
    */
    protected $objNestedSet;

    /**
    * Id of the nested set root node
    *
    * @access   protected
    * @var      integer
    */
    protected $nestedSetRootId;

    /**
     * Status messages.
     *
     * @var string
     */
    protected $errMsg = array();

    /**
     * Initializes the NestedSet object
     * which is needed to manage the news categories.
     *
     * @access  public
     */
    public function __construct()
    {
        global $objDatabase;

        //nestedSet setup
        $arrTableStructure = array(
            'catid'     => 'id',
            'parent_id' => 'rootid',
            'left_id'   => 'l',
            'right_id'  => 'r',
            'sorting'   => 'norder',
            'level'     => 'level',
        );
        $objNs = new \DB_NestedSet($arrTableStructure);
        $this->objNestedSet = $objNs->factory('ADODB', $objDatabase, $arrTableStructure);
        $this->objNestedSet->setAttr(array(
            'node_table'    => DBPREFIX.'module_news_categories',
            'lock_table'    => DBPREFIX.'module_news_categories_locks',
        ));

        if (count($rootNodes = $this->objNestedSet->getRootNodes()) > 0) {
            foreach ($rootNodes as $rootNode) {
                $this->nestedSetRootId = $rootNode->id;
                break;
            }
        } else {
            // create first entry of sequence table for NestedSet
            $objResult = $objDatabase->SelectLimit("SELECT `id` FROM `".DBPREFIX."module_news_categories_catid`", 1);
            if ($objResult->RecordCount() == 0) {
                $objDatabase->Execute("INSERT INTO `".DBPREFIX."module_news_categories_catid` VALUES (0)");
            }
            $this->nestedSetRootId = $this->objNestedSet->createRootNode(array(), false, false);
        }
    }

    /**
     * Gets the categorie option menu string
     *
     * @global    ADONewConnection
     * @param     string     $lang
     * @param     string     $selectedOption
     * @return    string     $modulesMenu
     */
    function getSettings()
    {
        global $objDatabase;

        $query = "SELECT name, value FROM ".DBPREFIX."module_news_settings";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['name']] = $objResult->fields['value'];
            $objResult->MoveNext();
        }

        // get multilanguage settings (for now only news_feed_title and news_feed_description)
        $query = "SELECT lang_id, name, value FROM ".DBPREFIX."module_news_settings_locale";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['name']][$objResult->fields['lang_id']] = $objResult->fields['value'];
            $objResult->MoveNext();
        }
    }

    /**
     * Generates the formated ul/li of Archive list
     * Used in the template's
     *
     * @return string Formated ul/li of Archive list
     */
    public function getNewsArchiveList() {
        $monthlyStats = $this->getMonthlyNewsStats();

        $html = '';
        if (!empty($monthlyStats)) {
            $newsArchiveLink = \Cx\Core\Routing\Url::fromModuleAndCmd('News', 'archive');

            $html  = '<ul class="news_archive">';
            foreach ($monthlyStats as $key => $value) {
                $html .= '<li><a href="'.$newsArchiveLink.'#'.$key.'" title="'.$value['name'].'">'.$value['name'].'</a></li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Generates the formated ul/li of categories
     * Used in the template's
     *
     * @return string Formated ul/li of categories
     */
    public function getNewsCategories()
    {

        $categoriesLang = $this->getCategoriesLangData();

        return $this->_buildNewsCategories($this->nestedSetRootId, $categoriesLang);
    }

    /**
     * Generates the formated ul/li of categories
     * Used in the template's
     *
     * @return string Formated ul/li of categories
     */
    function _buildNewsCategories($catId, $categoriesLang)
    {
        if ($this->categoryExists($catId)) {

            $category = $this->objNestedSet->pickNode($catId, true);
            if ($catId != $this->nestedSetRootId) {
                $html .= "<li>";

                $newsUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('News');
                $newsUrl->setParam('category', $catId);

                $html .= '<a href="'.$newsUrl.'" title="'.contrexx_raw2xhtml($categoriesLang[$catId][FRONTEND_LANG_ID]).'">'.contrexx_raw2xhtml($categoriesLang[$catId][FRONTEND_LANG_ID]).'</a>';
            }

            $subCategories = $this->objNestedSet->getChildren($catId, true);
            if (!empty($subCategories)) {
                $html .= "<ul class='news_category_lvl_{$category['level']}'>";
                foreach ($subCategories as $subCat) {
                    $html .= $this->_buildNewsCategories($subCat['id'], $categoriesLang);
                }
                $html .= "</ul>";
            }

            if ($catId != $this->nestedSetRootId) {
                $html .= "</li>";
            }
        }

        return $html;
    }

    /**
     * Generates the category menu.
     *
     * @access  protected
     * @param   array or integer    $categories                   categories which have to be listed
     * @param   array               $selectedCategory             selected category
     * @param   array               $hiddenCategories             the categories which shouldn't be shown as option
     * @param   boolean             $onlyCategoriesWithEntries    only categories which have entries
     * @return  string              $options                      html options
     */
    protected function getCategoryMenu(
            $categories,
            $selectedCategory = 0,
            $hiddenCategories = array(),
            $onlyCategoriesWithEntries = false,
            $showLevel = true
    )
    {
        if (empty($categories)) {
            $categories = array($this->nestedSetRootId);
        } else if (!is_array($categories)) {
            $categories = array(intval($categories));
        }

        $nestedSetCategories = $this->getNestedSetCategories($categories);

        if ($onlyCategoriesWithEntries) {
            $hiddenCategories = array_merge($hiddenCategories, $this->getEmptyCategoryIds());
        }

        $levels = array();
        foreach($nestedSetCategories as $category) {
            $levels[] = $category['level'];
        }
        $level = min($levels);

        $categoriesLang = $this->getCategoriesLangData();
        $options = '';
        foreach ($nestedSetCategories as $category) {
            if(in_array($category['id'], $hiddenCategories)) {
                continue;
            }
            $selected = in_array($category['id'], $selectedCategory) ? 'selected="selected"' : '';
            $options .= '<option value="'.$category['id'].'" '.$selected.'>'
                    .($showLevel ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($category['level'] - $level)) : '')
                    .contrexx_raw2xhtml($categoriesLang[$category['id']][
                        \Env::get('cx')->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND ? BACKEND_LANG_ID : FRONTEND_LANG_ID
                    ])
                    .'</option>';
        }

        return $options;
    }

    /**
     * Manipulating the submitted categories from the news Entry form.
     * i)  Update the relationship of the news in the corresponding table
     * ii) Delete the removed categories ids from the news relation table
     *
     * @param type $categoryIds Array of submitted category Ids
     * @param type $newsId      News id for manipulation
     *
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     *
     * @return boolean
     */
    protected function manipulateCategories($categoryIds = array(), $newsId = null)
    {
        global $objDatabase, $_ARRAYLANG;

        $oldNewsCategoryIds = $this->getNewsRelCategories($newsId);

        foreach ($categoryIds as $categoryId) {
            /**
             * Insert the category id with the news id to make the relationship
             * between news and categories
             */
            //Checking category is already related
            if (in_array($categoryId, $oldNewsCategoryIds)) {
                if(($key = array_search($categoryId, $oldNewsCategoryIds)) !== false) {
                    unset($oldNewsCategoryIds[$key]); //Removing from the current list
                }
            } else {
                $insertCategoryRelQuery = 'INSERT INTO `'
                    . DBPREFIX . 'module_news_rel_categories` '
                    . '(`news_id`, `category_id`) '
                    . 'VALUES ('
                    . $newsId . ','
                    . contrexx_raw2db($categoryId)
                    . ')';
                if (!$objDatabase->Execute($insertCategoryRelQuery)) {
                    \DBG::log('Error: While saving the news category relation.');
                    $this->errMsg[] = $_ARRAYLANG['TXT_ERROR_SAVING_NEWS_CATGORY_RELATION'];
                    return false;
                }
            }
        }

        //Delete the relationship of removed categories while editing the news
        if (    !empty($newsId)
            &&  !empty($oldNewsCategoryIds)
        ) {
            $deleteNewsRealtionQuery = 'DELETE FROM `'
                . DBPREFIX . 'module_news_rel_categories` '
                . 'WHERE `news_id` = "'. $newsId . '" '
                . 'AND `category_id` IN ('
                . implode(',', $oldNewsCategoryIds).')';

            if (!$objDatabase->Execute($deleteNewsRealtionQuery)) {
                \DBG::log('Error: While removing the news category relation.');
                $this->errMsg[] = $_ARRAYLANG['TXT_ERROR_DELETE_NEWS_CATGORY_RELATION'];
                return false;
            }
        }
        return true;
    }

    /**
     * Get the news related category by ID
     *
     * @param integer $newsId
     *
     * @global object $objDatabase
     *
     * @return boolean
     */
    public function getNewsRelCategories($newsId)
    {
        global $objDatabase;

        if (empty($newsId)) {
            return array();
        }

        $query = 'SELECT `category_id`
                        FROM `' . DBPREFIX . 'module_news_rel_categories` as tnrc
                            LEFT JOIN `' . DBPREFIX . 'module_news_categories` as tnc
                            ON `tnc`.`catid` = `tnrc`.`category_id`
                        WHERE `news_id` = "' . $newsId . '" ORDER BY `tnc`.`sorting`';

        $objNewsCategories = $objDatabase->Execute($query);

        if (!$objNewsCategories) {
            \DBG::log('No category found in the News ID:' . $newsId);
            return false;
        }

        $categoryIdList = array();
        while (!$objNewsCategories->EOF) {
            $categoryIdList[] = $objNewsCategories->fields['category_id'];
            $objNewsCategories->MoveNext();
        }
        return $categoryIdList;
    }

    /**
     * Get the news ID list based on the category
     *
     * @param integer $categoryId
     *
     * @global object $objDatabase
     *
     * @return mixed boolean|array
     */
    public function getCategoryRelNews($categoryId)
    {
        global $objDatabase;

        if (empty($categoryId)) {
            return false;
        }

        $query = 'SELECT
            `news_id`
            FROM `' . DBPREFIX . 'module_news_rel_categories`
            WHERE `category_id` = "' . $categoryId . '"';

        $objCategoryNewsList = $objDatabase->Execute($query);

        if (!$objCategoryNewsList) {
            \DBG::log('No News entries found on the category ID: ' . $categoryId);
            return false;
        }
        $newsIdList = array();
        while (!$objCategoryNewsList->EOF) {
            $newsIdList[] = $objCategoryNewsList->fields['news_id'];
            $objCategoryNewsList->MoveNext();
        }
        return $newsIdList;
    }

    /**
     * Getting the locale categories
     *
     * @param mixed $categoryIds
     * @param mixed $langIds
     *
     * @global object $objDatabase
     *
     * @return array
     */
    public function getCategoryLocale($categoryIds=null, $langIds=null)
    {
        global $objDatabase;

        $query = 'SELECT `tncl`.`category_id`, '
                      . '`tncl`.`lang_id`, '
                      . '`tncl`.`name` '
               . 'FROM `' . DBPREFIX . 'module_news_categories_locale` as tncl '
               . 'LEFT JOIN `' . DBPREFIX . 'module_news_categories` as tnc '
               . 'ON (`tnc`.`catid` = `tncl`.`category_id`) ';

        $where = array();

        if (!empty($categoryIds)) {
            if (is_array($categoryIds)) {
                $where[] = "`category_id` IN ('"
                        . implode(',', $categoryIds)
                        . "')";
            } else {
                $where[] = "`category_id` ='"
                        . $categoryIds
                        . "'";
            }
        }

        if (!empty($langIds)) {
            if (is_array($langIds)) {
                $where[] = "`lang_id` IN ('"
                        . implode(',', $langIds)
                        . "')";
            } else {
                $where[] = "`lang_id` ='"
                        . $langIds
                        . "'";
            }
        }
        $query .= !empty($where)
            ? ' WHERE ' . implode(' AND ', $where)
            : '';
        $query .= ' ORDER BY `tnc`.`sorting`';
        $objCategoriesLocale = $objDatabase->Execute($query);
        $categoriesLocale = array();

        if ($objCategoriesLocale && $objCategoriesLocale->RecordCount() > 0) {
            while (!$objCategoriesLocale->EOF) {
                $categoriesLocale
                        [$objCategoriesLocale->fields['lang_id']]
                        [$objCategoriesLocale->fields['category_id']]
                        = $objCategoriesLocale->fields['name'];

                $objCategoriesLocale->MoveNext();

            }
        }
        return $categoriesLocale;
    }

    /**
     * Get the categories by News ID
     *
     * @param integer $newsId
     *
     * @global object $objDatabase
     *
     * @return mixed boolean|array
     */
    public function getCategoriesByNewsId($newsId)
    {
        global $objDatabase;

        if (empty($newsId)) {
            return false;
        }

        $query = 'SELECT `tnc`.`catid`, `tncl`.`name` '
                        . 'FROM `'. DBPREFIX . 'module_news_categories` as tnc '
                        . 'LEFT JOIN `' . DBPREFIX . 'module_news_categories_locale` as tncl '
                        . 'ON (`tnc`.`catid` = `tncl`.`category_id`) '
                        . 'LEFT JOIN `' . DBPREFIX . 'module_news_rel_categories` as tnrc '
                        . 'ON (`tncl`.`category_id` = `tnrc`.`category_id`) '
                        . 'WHERE `tnrc`.`news_id` = ' . $newsId . ' AND `tncl`.`lang_id` = ' . FRONTEND_LANG_ID
                        . ' ORDER BY `tnc`.`left_id`';
        $objResult = $objDatabase->Execute($query);

        $arrCategories = array();
        if ($objResult && $objResult->RecordCount() > 0) {
            while(!$objResult->EOF) {
                $arrCategories[$objResult->fields['catid']] = $objResult->fields['name'];
                $objResult->MoveNext();
            }
        }
        return $arrCategories;
    }

    /**
     * Returns an array containing the nested set information
     * for the passed categories and their subcategories
     * (ordered by their left id).
     *
     * @access  protected
     * @param   array or integer    $categories
     * @return  array                               nested set information
     */
    protected function getNestedSetCategories($categories) {
        if (!is_array($categories)) {
            $categories = array(intval($categories));
        }

        $nestedSetCategories = array();
        foreach ($categories as $category) {
            if ($this->categoryExists($category)) {
                if ($category != $this->nestedSetRootId) {
                    $nestedSetCategories[$category] = $this->objNestedSet->pickNode($category, true);
                }
                if ($nodes = $this->objNestedSet->getSubBranch($category, true)) {
                    $nestedSetCategories = $nestedSetCategories + $nodes;
                }
            }
        }

        return $this->sortNestedSetArray($nestedSetCategories);
    }

    /**
     * Returns an array containing the ids of empty categories.
     *
     * @access  protected
     * @global  object     $objDatabase              ADONewConnection
     * @return  array      $arrEmptyCategoryIds      ids of categories without entries
     */
    protected function getEmptyCategoryIds() {
        global $objDatabase;

        $orCatIdNotIn = '';
        if (!empty($_GET['monthFilter']) && preg_match('/^\d{4}(?:_\d{2})?$/', $_GET['monthFilter'])) {
            $monthFilter    = $_GET['monthFilter'];
            $arrMonthFilter = explode('_', $monthFilter);
            $year           = $arrMonthFilter[0];
            $month          = 0;

            if (count($arrMonthFilter) > 1) {
                if ($arrMonthFilter[1] >= 1 && $arrMonthFilter[1] <= 12) {
                    $month = $arrMonthFilter[1];
                }
            }

            if ($month > 0) {
                $daysOfMonth = date("t", mktime(0, 0, 0, $month, 1, $year));
                $whereDate   = 'WHERE `n`.`date` BETWEEN ' . mktime(0, 0, 0, $month, 1, $year) . ' AND ' . mktime(23, 59, 59, $month, $daysOfMonth, $year);
            } else {
                $whereDate   = 'WHERE `n`.`date` BETWEEN ' . mktime(0, 0, 0, 1, 1, $year) . ' AND ' . mktime(23, 59, 59, 12, 31, $year);
            }
            $selectCatIdBetweenDate = '
                SELECT `rc`.`category_id`
                  FROM `' . DBPREFIX . 'module_news_categories` AS `c`
             LEFT JOIN `' . DBPREFIX . 'module_news_rel_categories` AS `rc`
                    ON `c`.`catid` = `rc`.`category_id`
             LEFT JOIN `' . DBPREFIX . 'module_news` AS `n`
                    ON `rc`.`news_id` = `n`.`id`
                   ' . $whereDate . '
              GROUP BY `c`.`catid`
            ';
            $orCatIdNotIn = 'OR (`c`.`catid` NOT IN (' . $selectCatIdBetweenDate . '))';
        }

        $query = '
                SELECT `c`.`catid`
                FROM `contrexx_module_news_categories` `c`
                WHERE `c`.`catid`
                NOT IN (
                    SELECT `rc`.`category_id`
                    FROM `contrexx_module_news_rel_categories` `rc`
                )
                ' . $orCatIdNotIn . '
                GROUP BY `c`.`catid`
        ';

        $objResult = $objDatabase->Execute($query);

        $arrEmptyCategoryIds = array();
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrEmptyCategoryIds[] = $objResult->fields['catid'];
                $objResult->MoveNext();
            }
        }

        return $arrEmptyCategoryIds;
    }

    /**
     * Returns the category ids of a nested set array.
     *
     * @access  protected
     * @param   array           $nestedSet
     * @return  array           $categories
     */
    protected function getCatIdsFromNestedSetArray($nestedSet) {
        $categories = array();
        if (is_array($nestedSet)) {
            foreach ($nestedSet as $node) {
                $categories[] = $node['id'];
            }
        }
        return $categories;
    }

    /**
     * Checks whether the passed category exists.
     *
     * @access  protected
     * @param   integer         $category
     * @return  boolean
     */
    protected function categoryExists($category) {
        if ($this->objNestedSet->pickNode($category)) {
            return true;
        }
        return false;
    }

    /**
     * Sorts the given nested set array by the left id.
     *
     * @access  protected
     * @param   array           $array
     * @return  array           $array
     */
    protected function sortNestedSetArray($array) {
        if (is_array($array)) {
            usort($array, array($this, 'compareNestedSetLeftIds'));
        }
        return $array;
    }

    /**
     * Compares the left id of two nested set nodes.
     *
     * @access  private
     * @param   array       $a
     * @param   array       $b
     * @return  integer
     */
    private function compareNestedSetLeftIds($a, $b) {
        $a = intval($a['l']);
        $b = intval($b['l']);

        if ($a == $b) {
            return 0;
        }

        return $a > $b ? 1 : -1;
    }

    function getTypeMenu($selectedOption='')
    {
        global $objDatabase;
        global $_ARRAYLANG;

        $strMenu = "";
        $query = "SELECT type_id, name FROM ".DBPREFIX."module_news_types_locale WHERE lang_id=".FRONTEND_LANG_ID." ORDER BY name";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $selected = $objResult->fields['type_id'] == $selectedOption ? "selected" : "";
            $strMenu .="<option value=\"".$objResult->fields['type_id']."\" $selected>".contrexx_raw2xhtml($objResult->fields['name'])."</option>\n";
            $objResult->MoveNext();
        }

        return $strMenu;
    }


    /**
     * Get Publisher dropdown options
     *
     * @param integer $selectedOption
     * @param array   $categoryId
     *
     * @return string options string
     */
    protected function getPublisherMenu($selectedOption = '', $categoryId = array())
    {
        global $objDatabase, $objInit;

        $arrNewsPublisher = array();
        $arrPublisher = array();

        $query = "SELECT DISTINCT n.publisher_id
                    FROM ".DBPREFIX."module_news AS n
                    INNER JOIN ".DBPREFIX."module_news_locale AS nl
                    ON nl.news_id = n.id
                    LEFT JOIN ".DBPREFIX."module_news_rel_categories AS nc
                    ON nc.news_id = n.id
                    WHERE  nl.lang_id=".FRONTEND_LANG_ID."
                    AND n.status = 1
                    AND n.publisher_id != 0
                    ".(!empty($categoryId) ? " AND nc.category_id IN (". implode(', ', contrexx_input2int($categoryId)) .")" : '');
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $arrNewsPublisher[] = $objResult->fields['publisher_id'];
            $objResult->MoveNext();
        }

        $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array('id' => $arrNewsPublisher), null, null, array('company', 'lastname', 'firstname'));
        if ($objUser) {
            $showUsername = ($objInit->mode == 'backend');

            while(!$objUser->EOF) {
                $arrPublisher[$objUser->getId()] = \FWUser::getParsedUserTitle($objUser, '', $showUsername);
                $objUser->next();
            }

            asort($arrPublisher);
        }

        $menu = '';
        foreach ($arrPublisher as $publisherId => $publisherTitle) {
            $selected = $publisherId == $selectedOption ? 'selected="selected"' : '';
            $menu .="<option value=\"$publisherId\" $selected>".contrexx_raw2xhtml($publisherTitle)."</option>\n";
        }

        return $menu;
    }

    /**
     * Get Author dropdown options
     *
     * @param integer $selectedOption
     * @param array   $categoryId
     *
     * @return string options string
     */
    protected function getAuthorMenu($selectedOption = '', $categoryId = array())
    {
        global $objDatabase, $objInit;

        $arrNewsAuthor = array();
        $arrAuthor = array();

        $query = "SELECT DISTINCT n.author_id
                    FROM ".DBPREFIX."module_news AS n
                    INNER JOIN ".DBPREFIX."module_news_locale AS nl
                    ON nl.news_id = n.id
                    LEFT JOIN ".DBPREFIX."module_news_rel_categories AS nc
                    ON nc.news_id = n.id
                    WHERE  nl.lang_id=".FRONTEND_LANG_ID."
                    AND n.status = 1
                    AND n.author_id != 0
                    ".(!empty($categoryId) ? " AND nc.category_id IN (". implode(', ', contrexx_input2int($categoryId)) .")" : '');
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $arrNewsAuthor[] = $objResult->fields['author_id'];
            $objResult->MoveNext();
        }

        $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array('id' => $arrNewsAuthor), null, null, array('company', 'lastname', 'firstname'));
        if ($objUser) {
            $showUsername = ($objInit->mode == 'backend');

            while(!$objUser->EOF) {
                $arrAuthor[$objUser->getId()] = \FWUser::getParsedUserTitle($objUser, '', $showUsername);
                $objUser->next();
            }

            asort($arrAuthor);
        }

        $menu = '';
        foreach ($arrAuthor as $authorId => $authorTitle) {
            $selected = $authorId == $selectedOption ? 'selected="selected"' : '';
            $menu .="<option value=\"$authorId\" $selected>".contrexx_raw2xhtml($authorTitle)."</option>\n";
        }

        return $menu;
    }


    /**
     * Gets only the body content and deleted all the other tags
     *
     * @param     string     $fullContent      HTML-Content with more than BODY
     * @return    string     $content          HTML-Content between BODY-Tag
     */
    function filterBodyTag($fullContent)
    {
        $res=false;
        $posBody=0;
        $posStartBodyContent=0;
        $res=preg_match_all("/<body[^>]*>/i", $fullContent, $arrayMatches);
        if ($res==true) {
            $bodyStartTag = $arrayMatches[0][0];
            // Position des Start-Tags holen
            $posBody = strpos($fullContent, $bodyStartTag, 0);
            // Beginn des Contents ohne Body-Tag berechnen
            $posStartBodyContent = $posBody + strlen($bodyStartTag);
        }
        $posEndTag=strlen($fullContent);
        $res=preg_match_all("/<\/body>/i",$fullContent, $arrayMatches);
        if ($res==true) {
            $bodyEndTag=$arrayMatches[0][0];
            // Position des End-Tags holen
            $posEndTag = strpos($fullContent, $bodyEndTag, 0);
            // Content innerhalb der Body-Tags auslesen
         }
         $content = substr($fullContent, $posStartBodyContent, $posEndTag  - $posStartBodyContent);
         return $content;
    }


    function hasCategories()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT 1 FROM ".DBPREFIX."module_news_categories_locale");
        return $objResult !== false && $objResult->RecordCount();
    }


    /**
     * Get language data (title, text, teaser_text) from database
     * @global ADONewConnection
     * @param  Integer $id
     * @return Array
     */
    function getLangData($id)
    {
        global $objDatabase;

        if (empty($id)) {
            return false;
        }
        $arrLangData = array();
        $objResult = $objDatabase->Execute("SELECT lang_id,
            is_active,
            title,
            text,
            teaser_text
            FROM ".DBPREFIX."module_news_locale
            WHERE news_id = " . intval($id));

        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrLangData[$objResult->fields['lang_id']] = array(
                    'active'      => $objResult->fields['is_active'],
                    'title'       => $objResult->fields['title'],
                    'text'        => $objResult->fields['text'],
                    'teaser_text' => $objResult->fields['teaser_text']
                );
                $objResult->MoveNext();
            }
        }
        return $arrLangData;
    }


    /**
     * Get categories language data
     * @global ADONewConnection
     * @return Array
     */
    function getCategoriesLangData()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT lang_id,
            category_id,
            name
            FROM ".DBPREFIX."module_news_categories_locale");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if (!isset($arrLangData[$objResult->fields['category_id']])) {
                    $arrLangData[$objResult->fields['category_id']] = array();
                }
                $arrLangData[$objResult->fields['category_id']][$objResult->fields['lang_id']] = $objResult->fields['name'];
                $objResult->MoveNext();
            }
        }
        return $arrLangData;
    }


    /**
     * Get types language data
     * @global ADONewConnection
     * @return Array
     */
    function getTypesLangData()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT lang_id,
            type_id,
            name
            FROM ".DBPREFIX."module_news_types_locale");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if (!isset($arrLangData[$objResult->fields['type_id']])) {
                    $arrLangData[$objResult->fields['type_id']] = array();
                }
                $arrLangData[$objResult->fields['type_id']][$objResult->fields['lang_id']] = $objResult->fields['name'];
                $objResult->MoveNext();
            }
        }
        return $arrLangData;
    }

    /**
     * Saving locales after edit news
     * @global ADONewConnection
     * @param Integer $newsId
     * @param Array $newLangData
     * @return Boolean
     */
    protected function storeLocales($newsId, $newLangData)
    {
        global $objDatabase;

        $oldLangData = $this->getLangData($newsId);
        if (count($oldLangData) == 0 || !isset($newsId)) {
            return false;
        }
        $status = true;
        $arrNewLocales = array_diff(array_keys($newLangData['title']), array_keys($oldLangData));
        $arrRemovedLocales = array_diff(array_keys($oldLangData), array_keys($newLangData['title']));
        $arrUpdatedLocales = array_intersect(array_keys($newLangData['title']), array_keys($oldLangData));

        foreach ($arrNewLocales as $langId) {
            if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_news_locale` (`lang_id`, `news_id`, `is_active`,  `title`, `text`, `teaser_text`)
                    VALUES ("   . intval($langId) . ", "
                                . $newsId . ", '"
                                . (isset($newLangData['active'][$langId]) ? contrexx_input2db($newLangData['active'][$langId]) : 0) . "', '"
                                . contrexx_input2db($newLangData['title'][$langId]) . "', '"
                                . $this->filterBodyTag(contrexx_input2db($newLangData['text'][$langId])) . "', '"
                                . (isset($newLangData['teaser_text'][$langId]) ? contrexx_input2db($newLangData['teaser_text'][$langId]) : "") . "')") === false) {
                $status = false;
            }
        }
        foreach ($arrRemovedLocales as $langId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_news_locale` WHERE `news_id` = " . $newsId . " AND `lang_id` = " . $langId) === false) {
                $status = false;
            }
        }
        foreach ($arrUpdatedLocales as $langId) {
            $newLangData['active'][$langId] = isset($newLangData['active'][$langId]) ? 1 : 0;
            $teaserText = isset($newLangData['teaser_text'][$langId]) ? ($newLangData['teaser_text'][$langId] != $oldLangData[$langId]['teaser_text']) : false;
            if ($newLangData['active'][$langId] != $oldLangData[$langId]['active']
            || $newLangData['title'][$langId] != $oldLangData[$langId]['title']
            || $newLangData['text'][$langId] != $oldLangData[$langId]['text']
            || $teaserText ) {
                if ($objDatabase->Execute("UPDATE `".DBPREFIX."module_news_locale` SET
                        `is_active` = '" . contrexx_input2db($newLangData['active'][$langId]) . "',
                        `title` = '" . contrexx_input2db($newLangData['title'][$langId]) . "',
                        " . ($this->arrSettings['news_use_teaser_text'] == 1 ? "`teaser_text` = '" . contrexx_input2db($newLangData['teaser_text'][$langId]) . "'," : "") . "
                        `text` = '" . $this->filterBodyTag(contrexx_input2db($newLangData['text'][$langId])) . "'
                        WHERE `news_id` = " . $newsId . " AND `lang_id` = " . $langId) === false) {
                    $status = false;
                }
            }
        }
        return $status;
    }


    /**
     * Saving categories locales
     * @global ADONewConnection
     * @param Array $newLangData
     * @return Boolean
     */
    protected function storeCategoriesLocales($newLangData)
    {
        global $objDatabase;

        $oldLangData = $this->getCategoriesLangData();
        if (count($oldLangData) == 0) {
            return false;
        }
        $status = true;
        $arrNewLocales = array_diff(array_keys($newLangData[key($newLangData)]), array_keys($oldLangData[key($oldLangData)]));
        $arrRemovedLocales = array_diff(array_keys($oldLangData[key($oldLangData)]), array_keys($newLangData[key($newLangData)]));
        $arrUpdatedLocales = array_intersect(array_keys($newLangData[key($newLangData)]), array_keys($oldLangData[key($oldLangData)]));
        foreach (array_keys($newLangData) as $catId) {
            foreach ($arrNewLocales as $langId) {
                if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_news_categories_locale` (`lang_id`, `category_id`, `name`)
                        VALUES ("   . intval($langId) . ", "
                                    . $catId . ", '"
                                    . contrexx_input2db($newLangData[$catId][$langId]) . "')")
                                    === false) {
                    $status = false;
                }
            }
            foreach ($arrUpdatedLocales as $langId) {
                if ($newLangData[$catId][$langId] != $oldLangData[$catId][$langId] ) {
                    if ($objDatabase->Execute("UPDATE `".DBPREFIX."module_news_categories_locale` SET
                            `name` = '" . contrexx_input2db($newLangData[$catId][$langId]). "'
                            WHERE `category_id` = " . $catId . " AND `lang_id` = " . $langId) === false) {
                        $status = false;
                    }
                }
            }
        }
        foreach ($arrRemovedLocales as $langId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_news_categories_locale` WHERE `lang_id` = " . $langId) === false) {
                $status = false;
            }
        }
        return $status;
    }


    /**
     * Saving types locales
     * @global ADONewConnection
     * @param Array $newLangData
     * @return Boolean
     */
    protected function storeTypesLocales($newLangData)
    {
        global $objDatabase;

        $oldLangData = $this->getTypesLangData();
        if (count($oldLangData) == 0) {
            return false;
        }
        $status = true;
        $arrNewLocales = array_diff(array_keys($newLangData[key($newLangData)]), array_keys($oldLangData[key($oldLangData)]));
        $arrRemovedLocales = array_diff(array_keys($oldLangData[key($oldLangData)]), array_keys($newLangData[key($newLangData)]));
        $arrUpdatedLocales = array_intersect(array_keys($newLangData[key($newLangData)]), array_keys($oldLangData[key($oldLangData)]));
        foreach (array_keys($newLangData) as $typeId) {
            foreach ($arrNewLocales as $langId) {
                if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_news_types_locale` (`lang_id`, `type_id`, `name`)
                        VALUES ("   . intval($langId) . ", "
                                    . $typeId . ", '"
                                    . contrexx_input2db($newLangData[$typeId][$langId]) . "')")
                                    === false) {
                    $status = false;
                }
            }
            foreach ($arrUpdatedLocales as $langId) {
                if ($newLangData[$typeId][$langId] != $oldLangData[$typeId][$langId] ) {
                    if ($objDatabase->Execute("UPDATE `".DBPREFIX."module_news_types_locale` SET
                            `name` = '" . contrexx_input2db($newLangData[$typeId][$langId]). "'
                            WHERE `type_id` = " . $typeId . " AND `lang_id` = " . $langId) === false) {
                        $status = false;
                    }
                }
            }
        }
        foreach ($arrRemovedLocales as $langId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_news_types_locale` WHERE `lang_id` = " . $langId) === false) {
                $status = false;
            }
        }
        return $status;
    }

    /**
     * Saving feed settings locales
     * @global ADONewConnection
     * @param String $newsId
     * @param Array $newLangData
     * @return Boolean
     */
    protected function storeFeedLocales($settingsName, $newLangData)
    {
        global $objDatabase;

        $this->getSettings();
        $oldLangData = $this->arrSettings[$settingsName];
        if (count($oldLangData) == 0) {
            return false;
        }
        $status = true;
        $arrNewLocales = array_diff(array_keys($newLangData), array_keys($oldLangData));
        $arrRemovedLocales = array_diff(array_keys($oldLangData), array_keys($newLangData));
        $arrUpdatedLocales = array_intersect(array_keys($newLangData), array_keys($oldLangData));
        foreach ($arrNewLocales as $langId) {
            if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_news_settings_locale` (`lang_id`, `name`, `value`)
                    VALUES ("   . intval($langId) . ", '"
                                . $settingsName . "', '"
                                . contrexx_input2db($newLangData[$langId]) . "')")
                                === false) {
                $status = false;
            }
        }
        foreach ($arrUpdatedLocales as $langId) {
            if ($newLangData[$langId] != $oldLangData[$langId] ) {
                if ($objDatabase->Execute("UPDATE `".DBPREFIX."module_news_settings_locale` SET
                        `value` = '" . contrexx_input2db($newLangData[$langId]). "'
                        WHERE `name` LIKE '" . $settingsName . "' AND `lang_id` = " . $langId) === false) {
                    $status = false;
                }
            }
        }
        foreach ($arrRemovedLocales as $langId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_news_settings_locale` WHERE `lang_id` = " . $langId) === false) {
                $status = false;
            }
        }
        return $status;
    }


    /**
     * Insert new locales after create news from backend
     * @global ADONewConnection
     * @param Integer $newsId
     * @param Array $newLangData
     * @return Boolean
     */
    function insertLocales($newsId, $newLangData)
    {
        global $objDatabase;

        if (empty($newsId)) {
            return false;
        }
        $status = true;
        $arrLanguages = \FWLanguage::getLanguageArray();
        foreach ($arrLanguages as $langId => $arrLanguage) {
            if ($arrLanguage['frontend'] == 1 && isset($newLangData['active'][$langId])) {
                if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_news_locale` (`lang_id`, `news_id`, `is_active`, `title`, `text`, `teaser_text`)
                        VALUES ("   . intval($langId) . ", "
                                    . $newsId . ", '"
                                    . (isset($newLangData['active'][$langId]) ? 1 : 0) . "', '"
                                    . (isset($newLangData['title'][$langId]) ? contrexx_input2db($newLangData['title'][$langId]) : "") . "', '"
                                    . (isset($newLangData['text'][$langId]) ? $this->filterBodyTag(contrexx_input2db($newLangData['text'][$langId])) : "") . "', '"
                                    . (isset($newLangData['teaser_text'][$langId]) ? contrexx_input2db($newLangData['teaser_text'][$langId]) : "") . "')") === false) {
                    $status = false;
                }
            }
        }
        return $status;
    }


    /**
     * Insert new locales after submit news from frontend
     * One copy for all languages
     * @global ADONewConnection
     * @param Integer   $newsId
     * @param String    $title
     * @param String    $text
     * @param String    $teaser_text
     * @return Boolean
     */
    function submitLocales($newsId, $title, $text, $teaser_text)
    {
        global $objDatabase;

        if (empty($newsId)) {
            return false;
        }
        $status = true;
        $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."languages");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_locale (`lang_id`, `news_id`, `title`, `text`, `teaser_text`)
                    VALUES ("
                        . intval($objResult->fields['id']) . ", "
                        . intval($newsId) . ", '"
                        . contrexx_input2db($title) . "', '"
                        . $this->filterBodyTag(contrexx_input2db($text)) . "', '"
                        . contrexx_input2db($teaser_text) . "')")){
                    $status = false;
                }
                $objResult->MoveNext();
            }
        }
        return $status;
    }

    protected function getHtmlImageTag($src, $alt)
    {
        static $htmlImgTag = '<img src="%1$s" alt="%2$s" />';

        return sprintf($htmlImgTag, contrexx_raw2xhtml($src), $alt);
    }

    public function parseImageThumbnail($imageSource, $thumbnailSource, $altText, $newsUrl)
    {
        $image = '';
        $imageLink = '';
        $source = '';
        $cx     = \Cx\Core\Core\Controller\Cx::instanciate();
        if (!empty($thumbnailSource)) {
            $source = $thumbnailSource;
        } elseif (!empty($imageSource) && file_exists(\ImageManager::getThumbnailFilename($cx->getWebsitePath() .'/' .$imageSource))) {
            $source = \ImageManager::getThumbnailFilename($imageSource);
        } elseif (!empty($imageSource)) {
            $source = $imageSource;
        }

        if (!empty($source)) {
            $image     = self::getHtmlImageTag($source, $altText);
            $imageLink = self::parseLink($newsUrl, $altText, $image);
        }

        return array($image, $imageLink, $source);
    }

    protected static function parseLink($href, $title, $innerHtml, $class=null)
    {
        static $htmlLinkTag = '<a href="%1$s" title="%2$s">%3$s</a>';

        if (empty($href)) return '';

        return sprintf($htmlLinkTag, contrexx_raw2xhtml($href), contrexx_raw2xhtml($title), $innerHtml);
    }

    /**
     * Find the Page based on the category id $cmdId
     *
     * @param string  $cmdName
     * @param array   $cmdId
     * @param string  $cmdSeparator
     * @param string  $module
     * @param integer $lang
     *
     * @return boolean
     */
    protected function findPageById($cmdName, $cmdId, $cmdSeparator=',', $module='News', $lang=FRONTEND_LANG_ID)
    {
        if (empty($cmdId)) {
            return false;
        }

        $qb = \Env::get('em')->createQueryBuilder();
        $qb ->select('p', 'LENGTH(p.cmd) AS length')
            ->from('\Cx\Core\ContentManager\Model\Entity\Page', 'p')
            ->where($qb->expr()->andX(
                $qb->expr()->orX(
                    $qb->expr()->eq('p.cmd', ':cmd1'),
                    $qb->expr()->like('p.cmd', ':cmd2'),
                    $qb->expr()->like('p.cmd', ':cmd3'),
                    $qb->expr()->like('p.cmd', ':cmd4')
                ),
                $qb->expr()->eq('p.type', ':type'),
                $qb->expr()->eq('p.lang', ':lang'),
                $qb->expr()->eq('p.module', ':module')
            ))
            ->orderBy('length', 'ASC')
            ->setMaxResults(1)
            ->setParameters(array(
                'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
                'cmd1' => $cmdName.$cmdId,
                'cmd2' => $cmdName.$cmdId.$cmdSeparator.'%',
                'cmd3' => $cmdName.'%'.$cmdSeparator.$cmdId.$cmdSeparator.'%',
                'cmd4' => $cmdName.'%'.$cmdSeparator.$cmdId,
                'lang' => $lang,
                'module' => $module,
            ));
        $page = $qb->getQuery()->getResult();

        return !empty($page[0][0]) ? $page[0][0] : null;
    }

    /**
     * Searches for cmds having the passed id and
     * returns the cmd of the result set having the lowest length.
     *
     * @access  public
     * @param   string      $cmdName
     * @param   array       $cmdIds
     * @param   string      $cmdSeparator
     * @param   string      $module
     * @param   integer     $lang
     *
     * @return  string      $cmd
     */
    public function findCmdById($cmdName, $cmdIds, $cmdSeparator=',', $module='News', $lang=FRONTEND_LANG_ID)
    {
        //Get the CMD based on the $cmdIds
        foreach ($cmdIds as $cmdId) {
            $page = $this->findPageById($cmdName, $cmdId, $cmdSeparator=',', $module='News', $lang=FRONTEND_LANG_ID);
            if (!empty($page)) {
                return $page->getCmd();
            }
        }

        //Get the CMD based on the parent category of $cmdIds
        foreach ($cmdIds as $cmdId) {
            if (    ($parentCategory = $this->getParentCatId($cmdId))
                &&  ($page = $this->findPageById($cmdName, $parentCategory, $cmdSeparator=',', $module='News', $lang=FRONTEND_LANG_ID))
            ) {
                return $page->getCmd();
            }
        }

        //Get the default News details page CMD
        if ($page = \Env::get('em')->getRepository('\Cx\Core\ContentManager\Model\Entity\Page')->findOneByModuleCmdLang($module, $cmdName, $lang)) {
            // a page having the given cmd name without id was found
            return $page->getCmd();
        }

        return '';
    }

    /**
     * Returns the parent category id of passed category.
     *
     * @access  protected
     * @param   integer                 $category
     * @return  integer or boolean      $cmd
     */
    protected function getParentCatId($category) {
        if (($parent = $this->objNestedSet->getParent($category)) && ($parent->id != $this->nestedSetRootId)) {
            return $parent->id;
        }
        return false;
    }

    /**
     * Returns the news monthly stats by the given filters
     *
     * @access protected
     * @param  array     $categories      category filter
     *
     * @return array     $monthlyStats  Monthly status array
     */
    protected function getMonthlyNewsStats($categories) {
        global $objDatabase, $_CORELANG;

        $categoryFilter = '';
        $monthlyStats = array();
        if (!empty($categories)) {
           $categoryFilter .= ' AND nc.category_id IN ('. implode(', ', contrexx_input2int($categories)) .')';
        }

        $query = '  SELECT      DISTINCT(n.id)   AS id,
                                n.date           AS date,
                                n.teaser_image_path AS teaser_image_path,
                                n.teaser_image_thumbnail_path AS teaser_image_thumbnail_path,
                                n.changelog      AS changelog,
                                n.redirect       AS newsredirect,
                                n.publisher      AS publisher,
                                n.publisher_id   AS publisher_id,
                                n.author         AS author,
                                n.author_id      AS author_id,
                                n.allow_comments AS commentactive,
                                nl.title         AS newstitle,
                                nl.text NOT REGEXP \'^(<br type="_moz" />)?$\' AS newscontent,
                                nl.teaser_text
                    FROM       '.DBPREFIX.'module_news AS n
                    LEFT JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    LEFT JOIN '.DBPREFIX.'module_news_rel_categories AS nc ON nc.news_id = n.id
                    WHERE       n.validated = "1"
                                AND n.status = 1
                                AND nl.lang_id = '.FRONTEND_LANG_ID.'
                                AND nl.is_active=1
                                AND (n.startdate <="' . date('Y-m-d H:i:s') . '" OR n.startdate="0000-00-00 00:00:00")
                                AND (n.enddate >="' . date('Y-m-d H:i:s') . '" OR n.enddate="0000-00-00 00:00:00")
                                '.$categoryFilter.'
                                ' .($this->arrSettings['news_message_protection'] == "1" && !\Permission::hasAllAccess() ? (
                                ($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login() ?
                                    ' AND (frontend_access_id IN ('.implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).') OR userid = '.$objFWUser->objUser->getId().') '
                                    :   ' AND frontend_access_id=0 ')
                                :   '')
                    .'ORDER BY date DESC';

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            $arrMonthTxt = explode(',', $_CORELANG['TXT_MONTH_ARRAY']);
            while (!$objResult->EOF) {
                $filterDate = $objResult->fields['date'];
                $newsYear = date('Y', $filterDate);
                $newsMonth = date('m', $filterDate);
                if (!isset($monthlyStats[$newsYear.'_'.$newsMonth])) {
                    $monthlyStats[$newsYear.'_'.$newsMonth]['name'] = $arrMonthTxt[date('n', $filterDate) - 1].' '.$newsYear;
                }
                $monthlyStats[$newsYear.'_'.$newsMonth]['news'][] = $objResult->fields;
                $objResult->MoveNext();
            }
        }

        return $monthlyStats;
    }

    /**
     * Parses a user's account and profile data specified by $userId.
     * If the \Cx\Core\Html\Sigma template block specified by $blockName
     * exists, then the user's data will be parsed inside this block.
     * Otherwise, it will try to parse a template variable by the same
     * name. For instance, if $blockName is set to news_publisher,
     * it will first try to parse the template block news_publisher,
     * if unable it will parse the template variable NEWS_PUBLISHER.
     *
     * @param   object  Template object \Cx\Core\Html\Sigma
     * @param   integer User-ID
     * @param   string  User name/title that shall be used as fallback,
     *                  if no user account specified by $userId could be found
     * @param   string  Name of the \Cx\Core\Html\Sigma template block to parse.
     *                  For instance if you have a block like:
     *                      <!-- BEGIN/END news_publisher -->
     *                  set $blockName to:
     *                      news_publisher
     */
    public static function parseUserAccountData($objTpl, $userId, $userTitle, $blockName)
    {
        $placeholderName = strtoupper($blockName);

        if ($userId && $objUser = \FWUser::getFWUserObject()->objUser->getUser($userId)) {
            if ($objTpl->blockExists($blockName)) {
                // fill the template block user (i.e. news_publisher) with the user account's data
                $objTpl->setVariable(array(
                    $placeholderName.'_ID'          => $objUser->getId(),
                    $placeholderName.'_USERNAME'    => contrexx_raw2xhtml($objUser->getUsername())
                ));

                $objAccessLib = new \Cx\Core_Modules\Access\Controller\AccessLib($objTpl);
                $objAccessLib->setModulePrefix($placeholderName.'_');
                $objAccessLib->setAttributeNamePrefix($blockName.'_profile_attribute');

                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                    $objAccessLib->parseAttribute($objUser, $objAttribute->getId(), 0, false, FALSE, false, false, false);
                    $objUser->objAttribute->next();
                }
            } elseif ($objTpl->placeholderExists($placeholderName)) {
                // fill the placeholder (i.e. NEWS_PUBLISHER) with the user title
                $userTitle = \FWUser::getParsedUserTitle($userId);
                $objTpl->setVariable($placeholderName, contrexx_raw2xhtml($userTitle));
            }
        } elseif (!empty($userTitle)) {
            if ($objTpl->blockExists($blockName)) {
                // replace template block (i.e. news_publisher) by the user title
                $objTpl->replaceBlock($blockName, contrexx_raw2xhtml($userTitle));
            } elseif ($objTpl->placeholderExists($placeholderName)) {
                // fill the placeholder (i.e. NEWS_PUBLISHER) with the user title
                $objTpl->setVariable($placeholderName, contrexx_raw2xhtml($userTitle));
            }
        }
    }

    /**
     * Prepend the array by the given values
     *
     * @param array $categoryIds
     * @param array $priorityIds
     *
     * @return mixed boolean|array
     */
    protected static function sortCategoryIdByPriorityId($categoryIds = array(), $priorityIds = array())
    {
        if (empty($categoryIds)) {
            return false;
        }

        if (empty($priorityIds)) {
            return $categoryIds;
        }

        foreach ($categoryIds as $key => $categoryId) {
            if (in_array($categoryId, $priorityIds)) {
                unset($categoryIds[$key]);
                array_unshift($categoryIds, $categoryId);
            }
        }
        return $categoryIds;
    }

    /**
     * Parse the Image Block for thumbnail and detail image
     *
     * @param object $objTpl     Template object \Cx\Core\Html\Sigma
     * @param string $imagePath  Image path(Thumbnail/Detail Image)
     * @param string $altText    News  title
     * @param string $newsUrl    News  url
     * @param string $block      Block name
     */
    public static function parseImageBlock($objTpl, $imagePath, $altText, $newsUrl, $block)
    {
        if (!empty($imagePath)) {
            $image          = self::getHtmlImageTag($imagePath, $altText);
            $imgLink        = self::parseLink($newsUrl, $altText, $image);
            $imgPlaceholder = strtoupper($block);

            $objTpl->setVariable(array(
                'NEWS_' . $imgPlaceholder           => $image,
                'NEWS_' . $imgPlaceholder . '_ALT'  => contrexx_raw2xhtml($altText),
                'NEWS_' . $imgPlaceholder . '_LINK' => $imgLink,
                'NEWS_' . $imgPlaceholder . '_SRC'  => contrexx_raw2xhtml($imagePath)
            ));
            if ($objTpl->blockExists('news_' . $block)) {
                $objTpl->parse('news_' . $block);
            }
        } else {
            if ($objTpl->blockExists('news_' . $block)) {
                $objTpl->hideBlock('news_' . $block);
            }
        }

    }


    /**
     * Generate next and previous news links from the current news
     *
     * @global object $objDatabase
     * @global array  $_ARRAYLANG
     *
     * @param object  $objTpl
     *
     * @return null
     */
    public function parseNextAndPreviousLinks($objTpl = null)
    {
        global $objDatabase, $_ARRAYLANG;
        $parentBlock    = 'previousNextLink';
        $previousLink   = 'previousNewsLink';
        $nextLink       = 'nextNewsLink';

        $params = $_GET;

        if (empty($objTpl) || empty($params['newsid']))
        {
            return;
        }
        $newsId = intval($params['newsid']);

        $filterCategory = '';
        $arrCategory    = array();
        $newsFilter     = array();
        $arrAuthors     = array();
        $arrPublishers  = array();
        $arrTypes       = array();

        //Filter by category
        if (isset($params['filterCategory']) && !empty($params['filterCategory'])) {
            $arrCategory = explode(',', $params['filterCategory']);
            if (!empty($arrCategory)) {
                $filterCategory = ' AND (`nc`.`category_id` IN (' . implode(',', contrexx_input2int($arrCategory)) . '))';
            }
        }
        //Filter by author
        if (isset($params['filterAuthor']) && !empty($params['filterAuthor'])) {
            $arrAuthors = explode(',', $params['filterAuthor']);
            if (!empty($arrAuthors)) {
                $newsFilter['author_id'] = $arrAuthors;
            }
        }
        //Filter by publisher
        if (isset($params['filterPublisher']) && !empty($params['filterPublisher'])) {
            $arrPublishers = explode(',', $params['filterPublisher']);
            if (!empty($arrPublishers)) {
                $newsFilter['publisher_id'] = $arrPublishers;
            }
        }
        //Filter by type
        if (isset($params['filterType']) && !empty($params['filterType'])) {
            $arrTypes = explode(',', $params['filterType']);
            if (!empty($arrTypes)) {
                $newsFilter['typeid'] = $arrTypes;
            }
        }
        //Filter by tag
        if (isset($params['filterTag']) && !empty($params['filterTag'])) {
            $searchedTag = $this->getNewsTags(null, contrexx_input2raw($params['filterTag']));
            $searchedTagId = current(array_keys($searchedTag['tagList']));
            if (!empty($searchedTag['newsIds'])) {
                $this->incrementViewingCount($searchedTagId);
                $newsFilter['id'] = $searchedTag['newsIds'];
            }
        }

        $query = "SELECT n.id as currentNewsId,
                        (SELECT t1.id
                            FROM contrexx_module_news t1
                            INNER JOIN  " . DBPREFIX . "module_news_locale AS nl ON nl.news_id = t1.id
                            INNER JOIN " . DBPREFIX . "module_news_rel_categories AS nc ON nc.news_id = t1.id
                            WHERE ((t1.date = n.date AND t1.id < n.id) OR t1.date < n.date) "
                            . $this->getNewsFilterQuery('t1', $newsFilter, $filterCategory) .
                            " ORDER BY t1.date DESC,t1.id DESC LIMIT 1) as previousNewsId,
                        (SELECT t2.id
                            FROM contrexx_module_news t2
                            INNER JOIN  " . DBPREFIX . "module_news_locale AS nl ON nl.news_id = t2.id
                            INNER JOIN " . DBPREFIX . "module_news_rel_categories AS nc ON nc.news_id = t2.id
                            WHERE ((t2.date = n.date AND t2.id > n.id) OR t2.date > n.date) "
                            . $this->getNewsFilterQuery('t2', $newsFilter, $filterCategory) .
                            " ORDER BY t2.date ASC LIMIT 1) as nextNewsId
                    FROM " . DBPREFIX ."module_news n
                    INNER JOIN  " . DBPREFIX . 'module_news_locale AS nl ON nl.news_id = n.id
                    INNER JOIN  '.DBPREFIX.'module_news_rel_categories AS nc ON nc.news_id = n.id
                    WHERE n.id = ' . $newsId . $this->getNewsFilterQuery('n', $newsFilter, $filterCategory)
                    .' GROUP BY n.id '
                    .' ORDER BY n.date DESC';
        $resultArray = $objDatabase->GetRow($query);
        if(empty($resultArray))  {
            return;
        }

        $previousNewsId = $resultArray['previousNewsId'];
        $nextNewsId     = $resultArray['nextNewsId'];
        //previous news
        if (!empty($previousNewsId)) {
            $preNewsDetails = self::getNewsDetailsById($previousNewsId);
            $arrNewsCategories = $this->getCategoriesByNewsId($previousNewsId);
            if ($objTpl->blockExists($previousLink) && !empty($preNewsDetails)) {
                $objTpl->setVariable(
                        array(
                            'TXT_NEWS_PREVIOUS_LINK' => $_ARRAYLANG['TXT_NEWS_PREVIOUS_LINK'],
                            'NEWS_PREVIOUS_TITLE' => $preNewsDetails['newsTitle'],
                            'NEWS_PREVIOUS_LINK' => \Cx\Core\Routing\Url::fromModuleAndCmd(
                                    'news', $this->findCmdById('details', self::sortCategoryIdByPriorityId(array_keys($arrNewsCategories),$arrCategory)),
                                    FRONTEND_LANG_ID, array('newsid' => contrexx_raw2xhtml($preNewsDetails['id']))
                            )
                        )
                );
                $objTpl->touchBlock($previousLink);
            }
        }

        //next news
        if (!empty($nextNewsId)) {
            $nextNewsDetails = self::getNewsDetailsById($nextNewsId);
            $arrNewsCategories = $this->getCategoriesByNewsId($nextNewsId);
            if ($objTpl->blockExists($nextLink) && !empty($nextNewsDetails)) {
                $objTpl->setVariable(
                        array(
                            'TXT_NEWS_NEXT_LINK' => $_ARRAYLANG['TXT_NEWS_NEXT_LINK'],
                            'NEWS_NEXT_TITLE' => $nextNewsDetails['newsTitle'],
                            'NEWS_NEXT_LINK' => \Cx\Core\Routing\Url::fromModuleAndCmd(
                                    'news', $this->findCmdById('details', self::sortCategoryIdByPriorityId(array_keys($arrNewsCategories),$arrCategory)),
                                    FRONTEND_LANG_ID, array('newsid' => contrexx_raw2xhtml($nextNewsDetails['id']))
                            )
                        )
                );
                $objTpl->touchBlock($nextLink);
            }
        }
        if(!empty($previousNewsId) || !empty($nextNewsId)){
            $objTpl->touchBlock($parentBlock);
        }
    }

   /**
    * Get News Filter Condition Query
    *
    * @param string $tableAlias
    * @param array  $filters
    * @param string $filterCategory category filter
    *
    * @return string  sql query
    */
    public function getNewsFilterQuery($tableAlias, $filters, $filterCategory) {
        $filterCondition = " AND $tableAlias.status = 1
                    AND nl.is_active=1
                    AND nl.lang_id=" . FRONTEND_LANG_ID . "
                    AND ($tableAlias.startdate<='" . date('Y-m-d H:i:s') . "' OR $tableAlias.startdate=\"0000-00-00 00:00:00\")
                    AND ($tableAlias.enddate>='" . date('Y-m-d H:i:s') . "' OR $tableAlias.enddate=\"0000-00-00 00:00:00\")"
                . ($this->arrSettings['news_message_protection'] == '1'
                            && !\Permission::hasAllAccess() ? (($objFWUser = \FWUser::getFWUserObject())
                                    && $objFWUser->objUser->login() ? " AND (frontend_access_id IN (" . implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())) . ") OR userid = " . $objFWUser->objUser->getId() . ") " : " AND frontend_access_id=0 ") : ''
                );
        if (!empty($filters)) {
            $additionalFilter = '';
            foreach ($filters as $field => $values) {
                $additionalFilter .= ' AND (`' . $tableAlias . '`.`' . $field . '` IN (' . implode(',', contrexx_input2int($values)) . '))';
            }
            $filterCondition .= $additionalFilter;
        }
        if (!empty($filterCategory)) {
            $filterCondition .= $filterCategory;
        }
        return $filterCondition;
    }

    /**
     * Get news Details by id
     *
     * @global object  $objDatabase
     * @param  integer $id
     *
     * @return array
     */
    public function getNewsDetailsById($id){
        global $objDatabase;
        $query = "SELECT n.id as id,
                         nl.title AS newsTitle
                    FROM " . DBPREFIX . "module_news n
                        INNER JOIN  " . DBPREFIX . 'module_news_locale AS nl ON nl.news_id = n.id
                        WHERE n.id = ' . $id;
        return $objDatabase->GetRow($query);
    }

    /**
     * Getting the realated News
     *
     * @global object $objDatabase
     * @param type $newsId
     * @return boolean
     */
    public function getRelatedNews($newsId)
    {
        global $objDatabase;

        if (empty($newsId)) {
            return array();
        }

        $query = 'SELECT
            `related_news_id`
            FROM `' . DBPREFIX . 'module_news_rel_news`
            WHERE `news_id` = "' . $newsId . '"';

        $objNewsRelatedNews = $objDatabase->Execute($query);

        if (!$objNewsRelatedNews) {
//TODO@  Throw execption or log error message
//DBG::msg("Error Message");
            return array();
        }
        $relatedNewsIds = array();
        while (!$objNewsRelatedNews->EOF) {
            $relatedNewsIds[] = $objNewsRelatedNews->fields['related_news_id'];
            $objNewsRelatedNews->MoveNext();
        }
        return $relatedNewsIds;
    }

    /**
     * Manipulating the submitted related news from the news Entry form.
     * i)  Update the relationship of the news in the corresponding table
     * ii) Delete the removed related news ids from the news relation table
     *
     * @global object $objDatabase
     *
     * @param type $relatedNewsIds Array of submitted related_news Ids
     * @param type $newsId      News id for manipulation
     * @return boolean
     */
    public function manipulateRelatedNews(
            $relatedNewsIds = array(), $newsId = null)
    {
        global $objDatabase, $_ARRAYLANG;
//Delete the relationship of removed related news while editing the news
        if (    !empty($newsId)
            &&  !empty($relatedNewsIds)
        ) {
            $deleteNewsRealtionQuery = 'DELETE FROM `'
                . DBPREFIX . 'module_news_rel_news` '
                . 'WHERE `news_id` = "'. $newsId . '" '
                . 'AND `related_news_id` NOT IN ('
                . implode(',', $relatedNewsIds).')';
            if (!$objDatabase->Execute($deleteNewsRealtionQuery)) {
//TODO@  Throw execption or log error message
                $this->errMsg[] = $_ARRAYLANG['TXT_ERROR_DELETE_RELATED_NEWS_RELATION'];
                return false;
            }
        }
        /**
         * Insert the related news id with the news id to make the relationship
         * between news and related news
         */
        foreach ($relatedNewsIds as $relatedNewsId) {
            $insertRelatedNewsQuery = 'INSERT IGNORE INTO `'
                . DBPREFIX . 'module_news_rel_news` '
                . '(`news_id`, `related_news_id`) '
                . 'VALUES ('
                . $newsId . ','
                . contrexx_raw2db($relatedNewsId)
                . ')';
            if (!$objDatabase->Execute($insertRelatedNewsQuery)) {
//TODO@  Throw execption or log error message
                $this->errMsg[] = $_ARRAYLANG['TXT_ERROR_SAVING_RELATED_NEWS_RELATION'];
                return false;
            }
        }
        return true;
    }
    /**
     * Getting the related news details
     *
     * @global object $objDatabase
     *
     * @param array $relatedNewsIds Array of related news Ids
     * @param type $langIds         Array of languages Ids
     *
     * @return array
     *
     */
    public function getRelatedNewsDetails($relatedNewsIds = array(), $langIds = array())
    {
        global $objDatabase;

        $relatedNewsDetails = array();

        if (!empty($relatedNewsIds)) {
            $query = '
                SELECT      tblNews.`id`,
                            tblNews.userid AS newsuid,
                            tblNews.date   AS newsdate,
                            tblNews.`teaser_image_path`,
                            tblNews.`teaser_image_thumbnail_path`,
                            tblNews.`redirect`,
                            tblNews.`publisher`,
                            tblNews.`publisher_id`,
                            tblNews.`author`,
                            tblNews.`author_id`,
                            tblNews.allow_comments AS commentactive,
                            tblLocale.`lang_id`,
                            tblLocale.`title`,
                            tblLocale.`text`,
                            tblLocale.`teaser_text`,
                            tblLocale.`text` NOT REGEXP "^(<br type\'=_moz\' />)?$"
                                AS newscontent
                FROM        `'.DBPREFIX.'module_news` AS tblNews
                INNER JOIN  `'.DBPREFIX.'module_news_locale` AS tblLocale
                            ON tblLocale.`news_id` = tblNews.`id`
                WHERE       tblLocale.`is_active` = 1
                AND         tblLocale.`lang_id`
                            IN ("' . implode('", "', $langIds) . '")
                AND         tblNews.`id`
                            IN ("' . implode('", "', $relatedNewsIds) . '")';

            $objResults = $objDatabase->Execute($query);
            if (    $objResults
                &&  $objResults->RecordCount() > 0
            ) {
                while (!$objResults->EOF) {
                    $relatedNewsDetails
                        [$objResults->fields['id']]
                        [$objResults->fields['lang_id']] = $objResults->fields;
                    $objResults->MoveNext();
                }
            }
        }
        return $relatedNewsDetails;
    }
    /**
     * Parsing the relalated News tags
     *
     * @global \Cx\Core_Modules\News\Controller\type $_ARRAYLANG
     *
     * @param Object  $objTpl          Template Object
     * @param Array   $relatedNewsIds  Array List of related news Ids
     * @param Integer $langId
     * @param String  $blockName
     *
     * @return null
     */
    public function parseRelatedNewsTags(
        \Cx\Core\Html\Sigma $objTpl,
        $relatedNewsIds = array(),
        $langId = null,
        $blockName = 'related_news'
    )
    {

        if (   empty($relatedNewsIds)
            || !$objTpl->blockExists($blockName)
        ) {
            return;
        }

        $defaultLangId = \FWLanguage::getDefaultLangId();

        //Getting the related news details for the given languages
        $relatedNewsDetails = $this->getRelatedNewsDetails(
            $relatedNewsIds,
            array($langId, $defaultLangId)
        );
        if (empty($relatedNewsDetails)) {
            return;
        }

        foreach ($relatedNewsIds as $relatedNewsId) {

            $currentRelatedDetails = isset($relatedNewsDetails[$relatedNewsId][$langId])
                ? $relatedNewsDetails[$relatedNewsId][$langId]
                : $relatedNewsDetails[$relatedNewsId][$defaultLangId];

            $objTpl->setVariable(
                array(
                    'NEWS_RELATED_NEWS_ID'          => contrexx_raw2xhtml($relatedNewsId),
                    'NEWS_RELATED_NEWS_TITLE'       => contrexx_raw2xhtml($currentRelatedDetails['title']),
                    'NEWS_RELATED_NEWS_TITLE_SHORT' =>
                        (strlen($currentRelatedDetails['title']) > 35)
                            ? substr(strip_tags($currentRelatedDetails['title']), 0, 35) . '...'
                            : strip_tags($currentRelatedDetails['title']),
                )
            );
            $objTpl->parse($blockName);
        }


    }
    /**
     * Parsing the related News
     *
     * @global object $objDatabase
     * @global type   $_ARRAYLANG
     *
     * @param Object    $objTpl     Template Object
     * @param Interger  $newsId     News Id
     * @param Interger  $langId     Language id
     * @param type      $blockName  Block Name
     * @param type      $limit      Limit
     *
     * @return null
     */
    public function parseRelatedNews(
        \Cx\Core\Html\Sigma $objTpl,
        $newsId = null,
        $langId = null,
        $blockName = 'related_news',
        $limit=0
    )
    {
        global $_ARRAYLANG, $objDatabase;

        if (    empty($newsId)
            || !$objTpl->blockExists($blockName)
        ) {
            return;
        }

        //Getting the related news ids
        $relatedNewsIds = $this->getRelatedNews($newsId);

        $defaultLangId = \FWLanguage::getDefaultLangId();

        //Getting the related news details for the given languages
        $relatedNewsDetails = $this->getRelatedNewsDetails(
            $relatedNewsIds,
            array($langId, $defaultLangId)
        );

        if (!empty($relatedNewsDetails)) {
            $defaultImage = \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseCoreModulePath()
                            .'/News/View/Media/default_news_image.png';

            $currentCount = 1;
            foreach ($relatedNewsIds as $relatedNewsId) {

                //If the limit is reached then the loop is stopped
                if (    !empty($limit)
                    &&  $currentCount > $limit
                ) {
                    break;
                }
                /*
                 * Checking the related news is available in the current
                 * acitve front-end language if not available then the default
                 * language details are getting used
                 * Comment/Uncomment the following line if this condition
                 * is required
                 */

                //$currentRelatedDetails = isset($relatedNewsDetails[$relatedNewsId][$langId])
                //    ? $relatedNewsDetails[$relatedNewsId][$langId]
                //    : $relatedNewsDetails[$relatedNewsId][$defaultLangId];

                /*
                 * Checking the related news is available in the current
                 * acitve front-end language if not available then the related
                 * News not listed Comment/Uncomment the following
                 * line if this condition is required
                 */
                $currentRelatedDetails =
                    isset($relatedNewsDetails[$relatedNewsId][$langId])
                        ? $relatedNewsDetails[$relatedNewsId][$langId]
                        : false;
                if (!$currentRelatedDetails) {
                    continue;
                }
                ++$currentCount;

                $categories = $this->getCategoriesByNewsId($relatedNewsId);

                $newsUrl = empty($currentRelatedDetails['redirect'])
                    ? (empty($currentRelatedDetails['newscontent'])
                        ? ''
                        : \Cx\Core\Routing\Url::fromModuleAndCmd(
                            'news',
                            $this->findCmdById('details', array_keys($categories)),
                            FRONTEND_LANG_ID,
                            array('newsid' => $relatedNewsId)
                        )
                    )
                    : $currentRelatedDetails['redirect'];

                $newstitle = $currentRelatedDetails['title'];
                $htmlLink  = self::parseLink(
                    $newsUrl,
                    $newstitle,
                    contrexx_raw2xhtml('[' . $_ARRAYLANG['TXT_NEWS_MORE'] . '...]')
                );
                $htmlLinkTitle = self::parseLink(
                    $newsUrl,
                    $newstitle,
                    contrexx_raw2xhtml($newstitle)
                );

                // in case that the message is a stub,
                // we shall just display the news title instead of a html-a-tag
                // with no href target
                if (empty($htmlLinkTitle)) {
                    $htmlLinkTitle = contrexx_raw2xhtml($newstitle);
                }

                $imagePath =
                    !empty($currentRelatedDetails['teaser_image_path'])
                        ? $currentRelatedDetails['teaser_image_path']
                        : $defaultImage;
                $imageThumbPath =
                    !empty($currentRelatedDetails['teaser_image_thumbnail_path'])
                        ? $currentRelatedDetails['teaser_image_thumbnail_path']
                        : $defaultImage;

                $this->parseImageBlock(
                    $objTpl,
                    $imagePath,
                    $newstitle,
                    $newsUrl,
                    'related_news_image'
                );
                $this->parseImageBlock(
                    $objTpl,
                    $imageThumbPath,
                    $newstitle,
                    $newsUrl,
                    'related_news_image_thumb'
                );

                $author = \FWUser::getParsedUserTitle(
                    $currentRelatedDetails['author_id'],
                    $currentRelatedDetails['author']
                );

                $publisher = \FWUser::getParsedUserTitle(
                    $currentRelatedDetails['publisher_id'],
                    $currentRelatedDetails['publisher']
                );
                $objSubResult = $objDatabase->Execute('
                    SELECT count(`id`) AS `countComments`
                    FROM `'
                    . DBPREFIX . 'module_news_comments`
                    WHERE `newsid` = ' . $relatedNewsId
                );
                $objTpl->setVariable(
                    array(
                        'NEWS_RELATED_NEWS_ID'             => contrexx_raw2xhtml($relatedNewsId),
                        'NEWS_RELATED_NEWS_URL'            => contrexx_raw2xhtml($newsUrl),
                        'NEWS_RELATED_NEWS_LINK'           => $htmlLink,

                        'NEWS_RELATED_NEWS_TITLE'          => contrexx_raw2xhtml($currentRelatedDetails['title']),
                        'NEWS_RELATED_NEWS_TITLE_SHORT'    =>
                            (strlen($currentRelatedDetails['title']) > 35)
                                ? substr(strip_tags($currentRelatedDetails['title']), 0, 35) . '...'
                                : strip_tags($currentRelatedDetails['title']),
                        'NEWS_RELATED_NEWS_TITLE_LINK'     => $htmlLinkTitle,

                        'NEWS_RELATED_NEWS_TEXT'           => $currentRelatedDetails['text'],
                        'NEWS_RELATED_NEWS_TEXT_SHORT'     =>
                            (strlen($currentRelatedDetails['text']) > 250)
                                ? substr(strip_tags($currentRelatedDetails['text']), 0, 247) . '...'
                                : strip_tags($currentRelatedDetails['text']),

                        'NEWS_RELATED_NEWS_TEASER_TEXT'    => nl2br($currentRelatedDetails['teaser_text']),

                        'NEWS_RELATED_NEWS_AUTHOR'         => contrexx_raw2xhtml($author),
                        'NEWS_RELATED_NEWS_PUBLISHER'      => contrexx_raw2xhtml($publisher),
                        'NEWS_RELATED_NEWS_CATEGORY_NAMES' => implode(', ', contrexx_raw2xhtml($categories)),

                        'NEWS_RELATED_NEWS_LONG_DATE'      => date(ASCMS_DATE_FORMAT, $currentRelatedDetails['newsdate']),
                        'NEWS_RELATED_NEWS_DATE'           => date(ASCMS_DATE_FORMAT_DATE, $currentRelatedDetails['newsdate']),
                        'NEWS_RELATED_NEWS_TIME'           => date(ASCMS_DATE_FORMAT_TIME, $currentRelatedDetails['newsdate']),
                        'NEWS_RELATED_NEWS_COUNT_COMMENTS' => ($currentRelatedDetails['commentactive'] && $this->arrSettings['news_comments_activated'])
                            ? contrexx_raw2xhtml($objSubResult->fields['countComments']
                                . ' ' . $_ARRAYLANG['TXT_NEWS_COMMENTS'])
                            : '',
                    )
                );
                if (    !$objSubResult->fields['countComments']
                    ||  !$this->arrSettings['news_comments_activated']
                ) {
                    if ($objTpl->blockExists('related_news_comments_count')) {
                        $objTpl->hideBlock('related_news_comments_count');
                    }
                }
                if (    $this->arrSettings['news_use_teaser_text'] != '1'
                    &&  $objTpl->blockExists('news_use_teaser_text')) {
                    $objTpl->hideBlock('news_use_teaser_text');
                }
                $objTpl->parse($blockName);
            }
            if ($objTpl->blockExists('related_news_block')) {
                $objTpl->setVariable('TXT_NEWS_RELATED_NEWS', $_ARRAYLANG['TXT_NEWS_RELATED_NEWS']);
                $objTpl->touchBlock('related_news_block');
            }
        }
    }

    /**
     * Getting all the stored tags
     *
     * @global object $objDatabase
     *
     * @param Interger $id
     * @param Interger $tag
     *
     * @return boolean|array Array list of tag and its id as key
     * array('id'  => //Id of the tag
     *       'tag' => //Tag value)
     */
    public function getTags($id=null, $tag=null)
    {
        global $objDatabase;

        $query = 'SELECT `id`, `tag`
            FROM `' . DBPREFIX . 'module_news_tags`';

        $where = '';
        //Search with the id or list of ids
        if (    is_array($id)
            &&  !empty($id)
        ) {
            $where .= ' WHERE `id` IN (' .implode(',', $id). ')';
        } elseif (!empty($id)) {
            $where .= ' WHERE `id` = '.intval($id);
        }
        //Search the given tag
        if (!empty($tag)) {
            $where .= (empty($where)) ? ' WHERE ' : ' AND ';
            $where .= '`tag` = "' . $tag . '"';
        }

        $objTags = $objDatabase->Execute($query.$where);

        if (!$objTags) {
//TODO@  Throw execption or log error message
            return array();
        }
        $tagList = array();
        while (!$objTags->EOF) {
            $tagList[$objTags->fields['id']] = $objTags->fields['tag'];
            $objTags->MoveNext();
        }
        return $tagList;
    }
    /**
     * Getting the related news tags with given news id (and|or) tag
     *
     * @global object $objDatabase
     *
     * @param type $newsId News id to get the corresponding related tags
     * @param type $tag    Tag string to search the corresponding tags
     *
     * @return boolean|array Array List of News Related tags
     */
    public function getNewsTags($newsId = null, $tag = null)
    {
        global $objDatabase;
        if (empty($newsId) && empty($tag)) {
            return array();
        }
        $query = 'SELECT
            rt.`news_id` AS newsId,
            rt.`tag_id` AS tagId,
            t.`tag` AS tagName
            FROM `' . DBPREFIX . 'module_news_rel_tags` rt
            LEFT JOIN `' . DBPREFIX . 'module_news_tags` t
            ON rt.`tag_id` = t.`id`';

        $where = '';

        if (!empty($newsId)) {
            $where .=' WHERE rt.`news_id` = "' . $newsId . '"';
        }

        //Search the given tag
        if (!empty($tag)) {
            $where .= (empty($where)) ? ' WHERE ' : ' AND ';
            $where .= 't.`tag` = "' . contrexx_raw2db($tag) . '"';
        }


        $objNewsTags = $objDatabase->Execute($query.$where);

        if (!$objNewsTags) {
//TODO@  Throw execption or log error message
//DBG::msg("Error Message");
            return false;
        }
        $newsTagList = array();
        $newsIdList = array();
        while (!$objNewsTags->EOF) {
            $newsTagList[$objNewsTags->fields['tagId']]
                = $objNewsTags->fields['tagName'];
            $newsIdList[] = $objNewsTags->fields['newsId'];
            $objNewsTags->MoveNext();
        }
        return array(
            'tagList' => $newsTagList,
            'newsIds' => $newsIdList
        );
    }
    /**
     * Add the new tag
     *
     * @global object $objDatabase
     * @param string $tag New Tag to be inserted
     * @return boolean|integer Retrun inserted Tag id and retrun false if
     *                         failed to insert
     */
    public function addTag($tag)
    {
        global $objDatabase, $_ARRAYLANG;

        if (!empty($tag)) {
            $insertQuery = 'INSERT INTO `'
                . DBPREFIX . 'module_news_tags` '
                . '(`tag`) '
                . 'VALUES ("' . contrexx_raw2db($tag) . '")' ;
            if ($objDatabase->Execute($insertQuery)) {
                return $objDatabase->Insert_ID();
            }
        }
//TODO@  Throw execption or log error message
        $this->errMsg[] = $_ARRAYLANG['TXT_ERROR_SAVE_NEWS_TAG'];
        return false;
    }
    /**
     * Manipulating the submitted tags from the news Entry form.
     * i)   Adding the new tag if the tag is not availbale already.
     * ii)  Update the relationship of the news in the corresponding table
     * iii) Delete the removed tags ids from the news relation table
     *
     *
     * @global object $objDatabase
     * @param array $tags   Array of submitted tags
     * @param type $newsId  News id for manipulation
     * @return boolean
     */
    public function manipulateTags(array $tags = array(), $newsId = null)
    {
        global $objDatabase, $_ARRAYLANG;

        $availableTags       = $this->getTags();
        $newsTagDetails      = $this->getNewsTags($newsId);
        $oldNewsTags         = $newsTagDetails['tagList'];
        $availableTagsFliped = array_flip($availableTags);

        foreach ($tags as $tag) {
            if (empty($tag)) {
                continue;
            }
            $tagId = null;
            //Getting the tag Id
            if (array_key_exists($tag, $availableTagsFliped)) {
                //If the tag is already available get the id
                $tagId = $availableTagsFliped[$tag];
            } else {
                //If the tag is not available the insert that tag and get
                //the inserted tag's id
                $tagId = $this->addTag($tag);
            }

            if (empty($tagId)) {
//TODO@  Throw execption or log error message
                return false;
            }
            /**
             * Insert the tag id with the news id to make the relationship
             * between news and tags
             */
            //Checking tag is already related
            if (array_key_exists($tagId, $oldNewsTags)) {
                unset($oldNewsTags[$tagId]); //Removing from the current list
            } else {
                $insertTagRelQuery = 'INSERT IGNORE INTO `'
                    . DBPREFIX . 'module_news_rel_tags` '
                    . '(`news_id`, `tag_id`) '
                    . 'VALUES ('
                    . $newsId . ','
                    . $tagId
                    . ')';
                if (!$objDatabase->Execute($insertTagRelQuery)) {
//TODO@  Throw execption or log error message
                    $this->errMsg[] = $_ARRAYLANG['TXT_ERROR_SAVE_NEWS_TAG_RELATION'];
                    return false;
                }
            }
        }

        //Delete the relationship of removed tags while editing the news
        if (    !empty($newsId)
            &&  !empty($oldNewsTags)
        ) {
            $deleteNewsRealtionQuery = 'DELETE FROM `'
                . DBPREFIX . 'module_news_rel_tags` '
                . 'WHERE `news_id` = "'. $newsId . '" '
                . 'AND `tag_id` IN ('
                . implode(',', array_keys($oldNewsTags)).')';
            if (!$objDatabase->Execute($deleteNewsRealtionQuery)) {
//TODO@  Throw execption or log error message
                    $this->errMsg[] = $_ARRAYLANG['TXT_ERROR_DELETE_NEWS_TAG_RELATION'];

                    return false;
            }
        }
        return true;
    }
    /**
     * Parsing the News tags.
     *
     * @global type $_ARRAYLANG
     * @param type $objTpl
     * @param type $newsId
     */
    public function parseNewsTags(
        $objTpl = null,
        $newsId = null,
        $block='newsTagList'
    )
    {
        global $_ARRAYLANG;

        if (!empty($newsId)) {
            $newsTagDetails = $this->getNewsTags($newsId);
            $newsTags       = $newsTagDetails['tagList'];
        }
        $tags = $this->getTags(array_keys($newsTags));
        if (empty($tags)) {
            if ($objTpl->blockExists('noTags')) {
                $objTpl->setVariable('TXT_NEWS_NO_TAGS_FOUND', $_ARRAYLANG['TXT_NEWS_NO_TAGS_FOUND']);
                $objTpl->showBlock('noTags');
            }
            return;
        }
        $tagCount = count($tags);
        $currentTagCount = 0;
        if (    $objTpl->blockExists($block)
            &&  !empty($tags)
        ) {
            foreach ($tags as $tag) {
                ++$currentTagCount;
                $newsLink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                    'news',
                    '',
                    FRONTEND_LANG_ID,
                    array('tag'=> urlencode($tag))
                );
                $objTpl->setVariable(
                    array(
                        'NEWS_TAG_NAME' => $tag,
                        'NEWS_TAG_LINK' =>
                            '<a class="tags" href="' . $newsLink . '">'
                            . ucfirst($tag)
                            . '</a>'//Including the tag separator
                            . (($currentTagCount < $tagCount) ? ',' : '')
                    )
                );
                $objTpl->parse($block);
            }
            if ($objTpl->blockExists('tagsBlock')) {
                $objTpl->touchBlock('tagsBlock');
            }
        }
    }
    /**
     * Increment the viewing count
     *
     * @global object $objDatabase
     * @param type $tagId
     * @return type
     */
    public function incrementViewingCount($tagId = null)
    {
        global $objDatabase;

        if (empty($tagId)) {
            return;
        }
        //Update the tag using count
        $objDatabase->Execute(
            'UPDATE `'
            . DBPREFIX . 'module_news_tags`
            SET `viewed_count` = `viewed_count`+1
            WHERE `id`=' . $tagId
        );
    }
    /**
     * Retruns most Frequent(Searched|Viewed) tag details.
     *
     * @global object $objDatabase
     * @return boolean|array array(
     *     'id'             => //Tag id
     *     'tag'            => //Tag Value
     *     'maxViewedCount' => //Maximum count of the tag viewed | searched
     * ) | FALSE on query fails
     */
    public function getMostFrequentTag()
    {
        global $objDatabase;
        $query = 'SELECT `id`, `tag`, `viewed_count` AS maxViewedCount
            FROM `' . DBPREFIX . 'module_news_tags`
            ORDER BY `viewed_count` DESC LIMIT 1';
        return $objDatabase->GetRow($query);
    }
    /**
     * Retruns most used tag details
     *
     * @global object $objDatabase
     * @return boolean|array array(
     *     'id'           => //Tag id
     *     'tag'          => //Tag Value
     *     'maxUsedCount' => //Maximum count of the tag used
     * ) | FALSE on query fails
     */
    public function getMostUsedTag()
    {
        global $objDatabase;

        $query = 'SELECT COUNT(`tag_id`) AS maxUsedCount, `tag_id` FROM `'
            . DBPREFIX . 'module_news_rel_tags`
            GROUP BY `tag_id` ORDER BY maxUsedCount DESC LIMIT 1';
        $maxUsedTag = $objDatabase->GetRow($query);
        $tagDetails = $this->getTags($maxUsedTag['tag_id']);
        return array(
            'id'           => $maxUsedTag['tag_id'],
            'tag'          => $tagDetails[$maxUsedTag['tag_id']],
            'maxUsedCount' => $maxUsedTag['maxUsedCount']
        );
    }
    /**
     * Register the JS code for the given input field ID
     *
     * @param type $newsTagId HMTL ID attribute Value of the input field
     */
    public function registerTagJsCode($newsTagId = 'newsTags')
    {
        global $_ARRAYLANG;

        $allNewsTags = $this->getTags();
        $concatedTag = '';
        $tagCount = 0;
        foreach ($allNewsTags as $newsTag) {
            ++$tagCount;
            $concatedTag .= '"' . contrexx_raw2xhtml(addslashes($newsTag)) . '"'
                . (($tagCount != count($allNewsTags)) ? ',' : '') ;
        }
        $newsTagsFormated  = htmlspecialchars_decode($concatedTag);
        $placeholderText = $_ARRAYLANG['TXT_NEWS_ADD_TAGS'];
        $jsCode = <<< EOF
\$J(document).ready(function() {
var encoded = [$newsTagsFormated];
var decoded = [];
\$J.each(encoded, function(key, value){
    decoded.push(\$J("<div/>").html(value).text());
});
\$J("#$newsTagId").tagit({
    fieldName: "newsTags[]",
        availableTags : decoded,
        placeholderText : "$placeholderText",
        allowSpaces : true
    });
});
EOF;
        \JS::registerCode($jsCode);
    }
}
