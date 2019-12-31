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
 * News library Exception
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class NewsLibraryException extends \Exception {};

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
     * Cached value of setting option use_thumbnails
     *
     * @var boolean
     */
    static $useThumbnails;

    public $newsMetaKeys = '';

    /**
     * Holds localized data of available types
     *
     * @var array
     */
    protected $arrTypeData = array();

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

        $this->getSettings();
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
     * If there are any news with scheduled publishing $nextUpdateDate will
     * contain the date when the next news changes its publishing state.
     * If there are are no news with scheduled publishing $nextUpdateDate will
     * be null.
     * @param integer $langId Language id
     * @param \DateTime $nextUpdateDate (reference) DateTime of the next change
     * @return string Formated ul/li of Archive list
     */
    public function getNewsArchiveList($langId = null, &$nextUpdateDate = null)
    {
        $monthlyStats = $this->getMonthlyNewsStats(array(), $langId, $nextUpdateDate);

        $html = '';
        if (!empty($monthlyStats)) {
            $newsArchiveLink = \Cx\Core\Routing\Url::fromModuleAndCmd('News', 'archive');

            $html  = '<ul class="news_archive">';
            foreach ($monthlyStats as $key => $value) {
                $redirectNewWindow = !empty($value['redirect']) && !empty($value['redirectNewWindow']);
                $linkTarget = $redirectNewWindow ? '_blank' : '_self';
                $html .= '<li><a href="' . $newsArchiveLink . '#' . $key . '" title="' . $value['name'] . '" target="' . $linkTarget . '">' . $value['name'] . '</a></li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Generates the formated ul/li of categories
     * Used in the template's
     *
     * @param   \Cx\Core\Html\Sigma $template   Template object to be parsed
     * @param integer $langId Language id
     * @param integer $categoryId ID of category to highlight
     *
     * @return string Formated ul/li of categories
     */
    public function getNewsCategories($template = null, $langId = null, $categoryId = 0)
    {
        $categoriesLang = $this->getCategoriesData();
        return $this->_buildNewsCategories($template, $this->nestedSetRootId, $categoriesLang, $langId, $categoryId);
    }

    /**
     * Generates the formated ul/li of categories
     * Used in the template's
     *
     * @param \Cx\Core\Sigma    $template   Template object to be parsed
     * @param integer   $catId          Category id
     * @param array     $categoriesLang Category locale
     * @param integer   $langId         Language id
     * @param integer $categoryId ID of category to highlight
     *
     * @return string Formated ul/li of categories
     */
    function _buildNewsCategories(
        $template,
        $catId,
        $categoriesLang,
        $langId = null,
        $categoryId = 0
    ) {
        if (!$this->categoryExists($catId)) {
            return;
        }

        if ($langId === null) {
            $langId = FRONTEND_LANG_ID;
        }

        $category = $this->objNestedSet->pickNode($catId, true);
        $category['url'] = null;
        $category['title'] = '';

        if ($catId != $this->nestedSetRootId) {
            $newsUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('News');
            $newsUrl->setParam('category', $catId);
            $category['url'] = $newsUrl;
            $category['title'] = contrexx_raw2xhtml(
                $categoriesLang[$catId]['lang'][$langId]
            );

            $this->parseNewsCategoryWidgetBlock(
                $template,
                'news_category_widget_item_open',
                $category,
                $categoryId
            );

            $this->parseNewsCategoryWidgetBlock(
                $template,
                'news_category_widget_item_content',
                $category,
                $categoryId
            );
        }

        $subCategories = $this->objNestedSet->getChildren($catId, true);
        if (!empty($subCategories)) {
            $this->parseNewsCategoryWidgetBlock(
                $template,
                'news_category_widget_list_open',
                $category,
                $categoryId
            );
            foreach ($subCategories as $subCat) {
                $this->_buildNewsCategories(
                    $template,
                    $subCat['id'],
                    $categoriesLang,
                    $langId,
                    $categoryId
                );
            }
            $this->parseNewsCategoryWidgetBlock(
                $template,
                'news_category_widget_list_close',
                $category,
                $categoryId
            );
        }

        if ($catId != $this->nestedSetRootId) {
            $this->parseNewsCategoryWidgetBlock(
                $template,
                'news_category_widget_item_close',
                $category,
                $categoryId
            );
        }

        if ($catId != $this->nestedSetRootId) {
            return;
        }

        return $template->get();
    }

    /**
     * Parse element of category widget block
     *
     * The element identified by $block will be parsed in template $template.
     * All other elements (blocks) in the template will be hidden.
     *
     * @param   \Cx\Core\Html\Sigma $template   Template object to parse
     * @param   string  $block  Name of block to parse
     * @param   array   $category   Category data as array
     * @param   integer $categoryId ID of category to highlight
     */
    protected function parseNewsCategoryWidgetBlock(
        $template,
        $block,
        $category,
        $categoryId
    ) {
        $blocks = array(
            'news_category_widget_list_open',
            'news_category_widget_item_open',
            'news_category_widget_item_content',
            'news_category_widget_item_close',
            'news_category_widget_list_close',
        );

        foreach ($blocks as $element) {
            if (!$template->blockExists($element)) {
                continue;
            }

            // parse selected list element
            if ($element == $block) {
                $template->setVariable(array(
                    'NEWS_CATEGORY_ID'      => $category['id'],
                    'NEWS_CATEGORY_TITLE'   => $category['title'],
                    'NEWS_CATEGORY_LEVEL'   => $category['level'],
                    'NEWS_CATEGORY_URL'     => $category['url'],
                ));

                if ($category['id'] == $categoryId) {
                    if ($template->blockExists($element . '_active')) {
                        $template->touchBlock($element . '_active');
                    }
                    if ($template->blockExists($element . '_inactive')) {
                        $template->hideblock($element . '_inactive');
                    }
                } else {
                    if ($template->blockExists($element . '_active')) {
                        $template->hideBlock($element . '_active');
                    }
                    if ($template->blockExists($element . '_inactive')) {
                        $template->touchBlock($element . '_inactive');
                    }
                }

                $template->touchBlock($element);
                continue;
            }

            // hide all other list elements
            $template->hideBlock($element);
        }

        $template->parse('news_category_widget');
    }

    /**
     * Generates the category menu.
     *
     * @access  protected
     * @param   array or integer    $categories                   categories which have to be listed
     * @param   array               $selectedCategory             selected category
     * @param   array               $hiddenCategories             the categories which shouldn't be shown as option
     * @param   boolean             $onlyCategoriesWithEntries    only categories which have entries
     * @param   boolean             $showLevel  Whether or not to visualy
     *                              show the hierarchy as indent
     * @param   boolean             Whether or not to list hidden categories
     * @return  string              $options                      html options
     */
    protected function getCategoryMenu(
            $categories,
            $selectedCategory = array(),
            $hiddenCategories = array(),
            $onlyCategoriesWithEntries = false,
            $showLevel = true,
            $includeHidden = true
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

        $categoriesLang = $this->getCategoriesData();
        $options = '';

        foreach ($nestedSetCategories as $category) {
            if(in_array($category['id'], $hiddenCategories)) {
                continue;
            }

            // hide hidden categories
            if (
                !$includeHidden &&
                !$categoriesLang[$category['id']]['display'] &&
                // exception: selected categories shall always get listed
                !in_array($category['id'], $selectedCategory)
            ) {
                continue;
            }

            $selected = in_array($category['id'], $selectedCategory) ? 'selected="selected"' : '';
            $options .= '<option value="'.$category['id'].'" '.$selected.'>'
                    .($showLevel ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($category['level'] - $level)) : '')
                    .contrexx_raw2xhtml(
                        $categoriesLang[$category['id']]['lang'][
                            FRONTEND_LANG_ID
                        ]
                    )
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
                $where[] = '`lang_id` IN ('
                        . implode(',', $langIds)
                        . ')';
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
     * @param   array   IDs of categories to fetch even if they're hidden
     *
     * @global object $objDatabase
     *
     * @return mixed boolean|array
     */
    public function getCategoriesByNewsId($newsId, $selectHidden = array())
    {
        global $objDatabase;

        if (empty($newsId)) {
            return false;
        }

        $query = 'SELECT `tnc`.`catid`, `tncl`.`name`, `tnc`.`display` '
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
                // skip hidden categories, except for hidden categories
                // mentioned in $selectHidden
                if (
                    !$objResult->fields['display'] &&
                    !in_array($objResult->fields['catid'], $selectHidden)
                ) {
                    $objResult->MoveNext();
                    continue;
                }

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
     * Get categories data
     * @return Array
     */
    protected function getCategoriesData()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();

        $objResult = $db->Execute("SELECT lang_id,
            category_id,
            name,
            display
            FROM ".DBPREFIX."module_news_categories AS c
            INNER JOIN ".DBPREFIX."module_news_categories_locale AS l
            ON l.category_id = c.catid
        ");
        $arrLangData = array();
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if (!isset($arrLangData[$objResult->fields['category_id']])) {
                    $arrLangData[$objResult->fields['category_id']] = array(
                        'lang' => array(),
                        'display' => $objResult->fields['display'],
                    );
                }
                $arrLangData[$objResult->fields['category_id']]['lang'][
                    $objResult->fields['lang_id']
                ] = $objResult->fields['name'];
                $objResult->MoveNext();
            }
        }
        return $arrLangData;
    }

    /**
     * Get name of a type
     *
     * @param   integer $id ID of type to get name from
     * @return  string  Name of type identified by $id.
     *                  If type identified by $id is unknown,
     *                  then an empty string is returned.
     */
    protected function getTypeNameById($id) {
        if (!$this->arrSettings['news_use_types']) {
            return '';
        }

        if (empty($id)) {
            return '';
        }

        if (!count($this->arrTypeData)) {
            $this->initTypesLangData();
        }

        if (!isset($this->arrTypeData[$id])) {
            return '';
        }

        if (!isset($this->arrTypeData[$id][FRONTEND_LANG_ID])) {   
            return '';
        }

        return $this->arrTypeData[$id][FRONTEND_LANG_ID];
    }

    /**
     * Fetch type localization and store them in a local member variable
     */
    protected function initTypesLangData() {
        if (!$this->arrSettings['news_use_types']) {
            return;
        }

        $this->arrTypeData = array();
        $this->arrTypeData = $this->getTypesLangData();
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

        $oldLangData = $this->getCategoriesData();
        if (count($oldLangData) == 0) {
            return false;
        }
        $status = true;
        $arrNewLocales = array_diff(
            array_keys($newLangData[key($newLangData)]),
            array_keys($oldLangData[key($oldLangData)]['lang'])
        );
        $arrRemovedLocales = array_diff(
            array_keys($oldLangData[key($oldLangData)]['lang']),
            array_keys($newLangData[key($newLangData)])
        );
        $arrUpdatedLocales = array_intersect(
            array_keys($newLangData[key($newLangData)]),
            array_keys($oldLangData[key($oldLangData)]['lang'])
        );
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
                if (
                    $newLangData[$catId][$langId]
                    != $oldLangData[$catId]['lang'][$langId]
                ) {
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
        $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();
        foreach ($frontendLanguages as $language) {
            if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_locale (`lang_id`, `news_id`, `title`, `text`, `teaser_text`)
                    VALUES ("
                . intval($language['id']) . ", "
                . intval($newsId) . ", '"
                . contrexx_input2db($title) . "', '"
                . $this->filterBodyTag(contrexx_input2db($text)) . "', '"
                . contrexx_input2db($teaser_text) . "')")){
                $status = false;
            }
        }
        return $status;
    }

    public function parseImageThumbnail($imageSource, $thumbnailSource, $altText, $newsUrl)
    {
        $image = '';
        $imageLink = '';
        $source = '';
        $cx     = \Cx\Core\Core\Controller\Cx::instanciate();

        if (!isset(static::$useThumbnails)) {
            static::$useThumbnails = false;
            $query = 'SELECT value FROM `' . DBPREFIX . 'module_news_settings` WHERE `name` = \'use_thumbnails\'';
            $db = $cx->getDb()->getAdoDb();
            $objResult = $db->SelectLimit($query, 1);
            if ($objResult !== false && $objResult->RecordCount()) {
                static::$useThumbnails = $objResult->fields['value'];
            }
        }

        if (!empty($thumbnailSource)) {
            $source = $thumbnailSource;
        } elseif (!empty($imageSource) && static::$useThumbnails && file_exists(\ImageManager::getThumbnailFilename($cx->getWebsitePath() .'/' .$imageSource))) {
            $source = \ImageManager::getThumbnailFilename($imageSource);
        } elseif (!empty($imageSource)) {
            $source = $imageSource;
        }

        if (!empty($source)) {
            $image     = \Html::getImageByPath($source, 'alt="' . contrexx_raw2xhtml($altText) . '"');
            $imageLink = self::parseLink($newsUrl, $altText, $image);
        }

        return array($image, $imageLink, $source);
    }

    protected static function parseLink($href, $title, $innerHtml, $target=null)
    {
        if (empty($href)) return '';

        $targetAttribute = '';
        if ($target == 1) {
            $targetAttribute = 'target="_blank"';
        }
        $htmlLinkTag = '<a href="%1$s" title="%2$s" ' . $targetAttribute . '>%3$s</a>';

        return sprintf($htmlLinkTag, contrexx_raw2xhtml($href), contrexx_raw2xhtml($title), $innerHtml, $target);
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
     * If there are any news with scheduled publishing $nextUpdateDate will
     * contain the date when the next news changes its publishing state.
     * If there are are no news with scheduled publishing $nextUpdateDate will
     * be null.
     * @access protected
     * @param  array     $categories      category filter
     * @param  integer   $langId          Language id
     * @param \DateTime $nextUpdateDate (reference) DateTime of the next change
     * @return array     $monthlyStats  Monthly status array
     */
    protected function getMonthlyNewsStats($categories, $langId = null, &$nextUpdateDate = null)
    {
        global $objDatabase, $_CORELANG;

        $categoryFilter = '';
        $monthlyStats = array();
        if (!empty($categories)) {
           $categoryFilter .= ' AND nc.category_id IN ('. implode(', ', contrexx_input2int($categories)) .')';
        }

        if ($langId === null) {
            $langId = FRONTEND_LANG_ID;
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
                                n.redirect_new_window AS redirectNewWindow,
                                n.startdate,
                                n.enddate,
                                nl.title         AS newstitle,
                                nl.text NOT REGEXP \'^(<br type="_moz" />)?$\' AS newscontent,
                                nl.teaser_text
                    FROM       '.DBPREFIX.'module_news AS n
                    LEFT JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    LEFT JOIN '.DBPREFIX.'module_news_rel_categories AS nc ON nc.news_id = n.id
                    WHERE       n.validated = "1"
                                AND n.status = 1
                                AND nl.lang_id = '. contrexx_input2int($langId) .'
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

        $nextUpdateDate = null;
        if ($objResult !== false) {
            $arrMonthTxt = explode(',', $_CORELANG['TXT_MONTH_ARRAY']);
            while (!$objResult->EOF) {
                if (
                    $objResult->fields['startdate'] != '0000-00-00 00:00:00' &&
                    $objResult->fields['enddate'] != '0000-00-00 00:00:00'
                ) {
                    $startDate = new \DateTime($objResult->fields['startdate']);
                    $endDate = new \DateTime($objResult->fields['enddate']);
                    if (
                        $endDate > new \DateTime() &&
                        (
                            !$nextUpdateDate ||
                            $endDate < $nextUpdateDate
                        )
                    ) {
                        $nextUpdateDate = $endDate;
                    }
                    if (
                        $startDate > new \DateTime() &&
                        (
                            !$nextUpdateDate ||
                            $startDate < $nextUpdateDate
                        )
                    ) {
                        $nextUpdateDate = $startDate;
                    }
                }

                $filterDate = $objResult->fields['date'];
                $newsYear = date('Y', $filterDate);
                $newsMonth = date('m', $filterDate);
                if (!isset($monthlyStats[$newsYear.'_'.$newsMonth])) {
                    $monthlyStats[$newsYear . '_' . $newsMonth] = array(
                        'name' => $arrMonthTxt[date('n', $filterDate) - 1].' '.$newsYear,
                        'news' => array(),
                    );
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
    public static function parseImageBlock($objTpl, $imagePath, $altText, $newsUrl, $block, $templatePrefix = '')
    {
        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        if (!empty($imagePath)) {
            $image          = \Html::getImageByPath($imagePath, 'alt="' . contrexx_raw2xhtml($altText) . '"');
            $imgLink        = self::parseLink($newsUrl, $altText, $image);
            $imgPlaceholder = strtoupper($block);

            $objTpl->setVariable(array(
                $templateVariablePrefix . 'NEWS_' . $imgPlaceholder           => $image,
                $templateVariablePrefix . 'NEWS_' . $imgPlaceholder . '_ALT'  => contrexx_raw2xhtml($altText),
                $templateVariablePrefix . 'NEWS_' . $imgPlaceholder . '_LINK' => $imgLink,
                $templateVariablePrefix . 'NEWS_' . $imgPlaceholder . '_LINK_URL' => contrexx_raw2xhtml($newsUrl),
                $templateVariablePrefix . 'NEWS_' . $imgPlaceholder . '_SRC'  => contrexx_raw2xhtml($imagePath),
            ));
            if ($objTpl->blockExists($templateBlockPrefix . 'news_' . $block)) {
                $objTpl->parse($templateBlockPrefix . 'news_' . $block);
            }
        } else {
            if ($objTpl->blockExists($templateBlockPrefix . 'news_' . $block)) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_' . $block);
            }
        }

    }

    /**
     * Generate the next and previous news links from the current news
     *
     * @param \Cx\Core\Html\Sigma $objTpl template object
     *
     * @return null
     */
    public function parseNextAndPreviousLinks(
        \Cx\Core\Html\Sigma $objTpl,
        $selectedCategories = array()
    ) {
        global $objDatabase, $_ARRAYLANG;

        $parentBlock    = 'news_details_previous_next_links';
        $previousLink   = 'news_details_previous_link';
        $nextLink       = 'news_details_next_link';

        $params = $_GET;

        if (empty($params['newsid'])) {
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
            if (!empty($searchedTag['newsIds'])) {
                $this->incrementViewingCount(array_keys($searchedTag['tagList']));
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
            $arrNewsCategories = $this->getCategoriesByNewsId(
                $previousNewsId,
                $selectedCategories
            );
            if ($objTpl->blockExists($previousLink) && !empty($preNewsDetails)) {
                $newsTitle    = contrexx_raw2xhtml($preNewsDetails['newsTitle']);
                $newsSrc      = \Cx\Core\Routing\Url::fromModuleAndCmd(
                                'News', $this->findCmdById('details', self::sortCategoryIdByPriorityId(array_keys($arrNewsCategories),$arrCategory)),
                                FRONTEND_LANG_ID, array('newsid' => contrexx_raw2xhtml($preNewsDetails['id'])));
                $aLinkContent = '<span class=\'news-link-label news-link-label-prev\'><small>&larr;</small>' . $_ARRAYLANG['TXT_NEWS_PREVIOUS_LINK'] . '</span>'
                                . '<span class=\'news-title-label news-title-label-prev\'>' . $newsTitle . '</span>';
                $objTpl->setVariable(
                        array(
                            'NEWS_PREVIOUS_TITLE'    => $newsTitle,
                            'NEWS_PREVIOUS_SRC'      => $newsSrc,
                            'NEWS_PREVIOUS_LINK'     => \Html::getLink($newsSrc, $aLinkContent, null, 'title="' . $newsTitle . '"')
                        )
                );
                $objTpl->touchBlock($previousLink);
            }
        }

        //next news
        if (!empty($nextNewsId)) {
            $nextNewsDetails = self::getNewsDetailsById($nextNewsId);
            $arrNewsCategories = $this->getCategoriesByNewsId(
                $nextNewsId,
                $selectedCategories
            );
            if ($objTpl->blockExists($nextLink) && !empty($nextNewsDetails)) {
                $newsTitle    = contrexx_raw2xhtml($nextNewsDetails['newsTitle']);
                $newsSrc      = \Cx\Core\Routing\Url::fromModuleAndCmd(
                                'News', $this->findCmdById('details', self::sortCategoryIdByPriorityId(array_keys($arrNewsCategories),$arrCategory)),
                                FRONTEND_LANG_ID, array('newsid' => contrexx_raw2xhtml($nextNewsDetails['id'])));
                $aLinkContent = '<span class=\'news-link-label news-link-label-next\'><small>&rarr;</small>' . $_ARRAYLANG['TXT_NEWS_NEXT_LINK'] . '</span>'
                                . '<span class=\'news-title-label news-title-label-next\'>' . $newsTitle . '</span>';
                $objTpl->setVariable(
                        array(
                            'NEWS_NEXT_TITLE'    => $newsTitle,
                            'NEWS_NEXT_SRC'      => $newsSrc,
                            'NEWS_NEXT_LINK'     => \Html::getLink($newsSrc, $aLinkContent, null, 'title="' . $newsTitle . '"')
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
     * @param type $newsId
     * @return boolean
     */
    protected function getRelatedNews(
        $newsId = 0,
        $additionalRelatedNewsIds = array(),
        $withArticleData = true
    ) {
        $relatedNewsIds = array();
        if ($newsId) {
            $relatedNewsIds = $this->getRelatedNewsIds($newsId);
        }
        if ($additionalRelatedNewsIds) {
            $relatedNewsIds = array_unique(
                array_merge(
                    $relatedNewsIds,
                    $additionalRelatedNewsIds
                )
            );
        }

        if (!$relatedNewsIds) {
            throw new NewsLibraryException('No related news');
        }

        // filter by access level
        $protection = '';
        if (
            $this->arrSettings['news_message_protection'] == '1' &&
            !\Permission::hasAllAccess()
        ) {
            $objFWUser = \FWUser::getFWUserObject();
            if (
                $objFWUser &&
                $objFWUser->objUser->login()
            ) {
                $protection = 'AND (frontend_access_id IN ('.
                    implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).
                    ') OR userid='.$objFWUser->objUser->getId().')';
            } else {
                $protection = 'AND frontend_access_id=0';
            }
        }

        $newsDataFields = '';
        $newsDataFilter = '';
        if ($withArticleData) {
            $newsDataFields = '     ,
                                    n.userid            AS newsuid,
                                    n.date              AS newsdate,
                                    n.typeid,
                                    n.teaser_image_path,
                                    n.teaser_image_thumbnail_path,
                                    n.redirect,
                                    n.publisher,
                                    n.publisher_id,
                                    n.author,
                                    n.author_id,
                                    n.allow_comments    AS commentactive,
                                    n.redirect_new_window AS redirectNewWindow,
                                    n.enable_tags,
                                    n.changelog,
                                    n.source,
                                    n.url1,
                                    n.url2,
                                    nl.text NOT REGEXP \'^(<br type="_moz" />)?$\' AS newscontent,
                                    nl.text AS text,
                                    nl.teaser_text
            ';
            $newsDataFilter = '
                AND status = 1
                AND (n.startdate<=\''.date('Y-m-d H:i:s').'\' OR n.startdate="0000-00-00 00:00:00")
                AND (n.enddate>=\''.date('Y-m-d H:i:s').'\' OR n.enddate="0000-00-00 00:00:00")
            ';
        }
        
        $query = '  SELECT      n.id                AS newsid,
                                nl.title            AS newstitle
                    ' . $newsDataFields . '
                    FROM        '.DBPREFIX.'module_news AS n
                    INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    WHERE       
                                n.id IN (' . join(',', $relatedNewsIds) . ')
                            AND nl.is_active=1
                            AND nl.lang_id='.FRONTEND_LANG_ID.'
                                ' . $newsDataFilter . '
                                ' . $protection;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $result = $cx->getDb()->getAdoDb()->query(
            $query
        );

        if (
            $result === false ||
            $result->EOF
        ) {
            throw new NewsLibraryException('No related news');
        }

        return $result;
    }

    protected function getRelatedNewsIds($newsId) {
        $query = 'SELECT
            `related_news_id`
            FROM `' . DBPREFIX . 'module_news_rel_news`
            WHERE `news_id` = ' . $newsId;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $result = $cx->getDb()->getAdoDb()->query(
            $query
        );

        if (
            $result === false ||
            $result->EOF
        ) {
            return array();
        }

        $relatedNewsIds = array();
        while (!$result->EOF) {
            $relatedNewsIds[] = $result->fields['related_news_id'];
            $result->MoveNext();
        }

        return $relatedNewsIds;
    }

    /**
     * Manipulating the submitted related news from the news Entry form.
     * i)  Update the relationship of the news in the corresponding table
     * ii) Delete the removed related news ids from the news relation table
     *
     * @param type $relatedNewsIds Array of submitted related_news Ids
     * @param type $newsId      News id for manipulation
     * @return  boolean Returns TRUE
     */
    protected function manipulateRelatedNews(
        $relatedNewsIds = array(),
        $newsId = null)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();

        if (    !empty($newsId)
            &&  !empty($relatedNewsIds)
        ) {
            $deleteNewsRealtionQuery = 'DELETE FROM `'
                . DBPREFIX . 'module_news_rel_news` '
                . 'WHERE `news_id` = "'. $newsId . '" '
                . 'AND `related_news_id` NOT IN ('
                . implode(',', $relatedNewsIds).')';
            if (!$db->Execute($deleteNewsRealtionQuery)) {
                //TODO: Handle issue
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
                . $relatedNewsId
                . ')';
            if (!$db->Execute($insertRelatedNewsQuery)) {
                //TODO: Handle issue
            }
        }

        return true;
    }

    /**
     * Parsing related News
     *
     * @global type   $_ARRAYLANG
     *
     * @param \Cx\Core\Html\Sigma    $objTpl     Template Object
     * @param Interger  $newsId     News Id
     */
    protected function parseRelatedNews(
        \Cx\Core\Html\Sigma $objTpl,
        $newsId = null,
        $selectedCategories = array()
    ) {
        global $_ARRAYLANG;

        if (!$objTpl->blockExists('news_details_related_news_container')) {
            return;
        }

        if (empty($newsId)) {
            $objTpl->hideBlock('news_details_related_news_container');
            return;
        }

        // fetch related news data
        try {
            $relatedNews = $this->getRelatedNews($newsId);
        } catch (NewsLibraryException $e) {
            $objTpl->hideBlock('news_details_related_news_container');
            return;
        }

        $objTpl->setVariable('TXT_NEWS_RELATED_NEWS', $_ARRAYLANG['TXT_NEWS_RELATED_NEWS']);

        // parse related news articles
        $i = 0;
        while (!$relatedNews->EOF) {
            $arrNewsCategories = $this->getCategoriesByNewsId(
                $relatedNews->fields['newsid'],
                $selectedCategories
            );
            $newsUrl = '';
            if (!empty($relatedNews->fields['redirect'])) {
                $newsUrl = $relatedNews->fields['redirect'];
            } elseif (!empty($relatedNews->fields['newscontent'])) {
                $newsUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
                    'News',
                    $this->findCmdById(
                        'details',
                        array_keys($arrNewsCategories)
                    ),
                    FRONTEND_LANG_ID,
                    array('newsid' => $relatedNews->fields['newsid'])
                );
            }

            // Parse all the news placeholders
            $this->parseNewsPlaceholders(
                $objTpl,
                $relatedNews,
                $newsUrl,
                'news_related_',
                $selectedCategories
            );

            $objTpl->setVariable(array(
               'NEWS_RELATED_NEWS_CSS'            => 'row'.($i % 2 + 1),
            ));

            $objTpl->parse('news_details_related_news');
            $i++;
            $relatedNews->MoveNext();
        }

        $objTpl->parse('news_details_related_news_container');
    }

    /**
     * Getting all the stored tags
     *
     * @global object $objDatabase
     * @param integer $id  Tag id
     * @param integer $tag Tag name
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

        $where = array();
        //Search with the id or list of ids
        if (!empty($id)) {
            if (is_array($id)) {
                $where[] = ' `id` IN (' .implode(', ', contrexx_input2int($id)). ')';
            } else {
                $where[] = ' `id` = '. contrexx_input2int($id);
            }
        }

        //Search the given tag
        if (!empty($tag)) {
            $where[] = ' `tag` = "' . contrexx_raw2db($tag) . '"';
        }

        $sqlWhere = !empty($where) ? ' WHERE '. implode(' AND ', $where) : '';
        $objTags = $objDatabase->Execute($query . $sqlWhere);

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
     * Get the news IDs and tags using news id (and|or) tag
     *
     * @global object $objDatabase
     * @param integer $newsId News id to get the corresponding related tags
     * @param array   $tags   Tag string to search the corresponding tags
     *
     * @return boolean|array Array List of News Related tags
     */
    public function getNewsTags($newsId = null, $tags = array())
    {
        global $objDatabase;
        if (empty($newsId) && empty($tags)) {
            return array();
        }
        $query = 'SELECT
            rt.`news_id` AS newsId,
            rt.`tag_id` AS tagId,
            t.`tag` AS tagName
                  FROM 
                    `' . DBPREFIX . 'module_news_rel_tags` rt
                  LEFT JOIN 
                    `' . DBPREFIX . 'module_news_tags` t
            ON rt.`tag_id` = t.`id`';

        $where = array();

        if (!empty($newsId)) {
            $where[] =' rt.`news_id` = "' . contrexx_input2int($newsId) . '"';
        }

        //Search the given tag
        if (!empty($tags) && is_array($tags)) {
            $where[] = ' (t.`tag` = "' . implode('" OR t.`tag` =  "', contrexx_raw2db($tags)) . '")';
        }

        $sqlWhere = !empty($where) ? ' WHERE '. implode(' AND ', $where) : '';
        $objNewsTags = $objDatabase->Execute($query . $sqlWhere);

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
     * Save new tag into database
     *
     * @global object $objDatabase
     * @param string $tag Tag name to insert
     * 
     * @return boolean|integer Retrun inserted Tag id or retrun false if
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
        $this->errMsg[] = $_ARRAYLANG['TXT_NEWS_ERROR_SAVE_NEWS_TAG'];
        return false;
    }

    /**
     * Manipulating the submitted tags from the news Entry form.
     * i)   Adding the new tag if the tag is not availbale already.
     * ii)  Update the relationship of the news in the corresponding table
     * iii) Delete the removed tags ids from the news relation table
     *
     * @global object $objDatabase
     * @param array $tags   Array of submitted tags
     * @param integer $newsId  News id for manipulation
     * @return boolean true when tags stored, false otherwise
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
                    . contrexx_input2int($newsId) . ','
                    . contrexx_raw2db($tagId)
                    . ')';
                if (!$objDatabase->Execute($insertTagRelQuery)) {
//TODO@  Throw execption or log error message
                    $this->errMsg[] = $_ARRAYLANG['TXT_NEWS_ERROR_SAVE_NEWS_TAG_RELATION'];
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
                . 'WHERE `news_id` = "'. contrexx_input2int($newsId) . '" '
                . 'AND `tag_id` IN ('
                . implode(', ', contrexx_raw2db(array_keys($oldNewsTags))) .')';
            if (!$objDatabase->Execute($deleteNewsRealtionQuery)) {
//TODO@  Throw execption or log error message
                    $this->errMsg[] = $_ARRAYLANG['TXT_NEWS_ERROR_DELETE_NEWS_TAG_RELATION'];

                    return false;
            }
        }
        return true;
    }

    /**
     * Parsing the News tags.
     *
     * @global type $_ARRAYLANG
     * @param \Cx\Core\Html\Sigma $objTpl       Template object
     * @param integer             $newsId       News id
     * @param string              $block        Name of the block to parse the news tags
     * @param boolean             $setMetaKeys  Set the tags as $this->newsMetaKeys when it is true 
     */
    public function parseNewsTags(
        \Cx\Core\Html\Sigma $objTpl,
        $newsId = null,
        $block       ='news_tag_list',
        $setMetaKeys = false,
        $templatePrefix = ''
    ) {
        global $_ARRAYLANG;

        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        $tags = $newsTags = array();
        if (!empty($newsId)) {
            $newsTagDetails = $this->getNewsTags($newsId);
            $newsTags       = $newsTagDetails['tagList'];
        }
        if (!empty($newsId) && !empty($newsTags)) {
            $tags = $this->getTags(array_keys($newsTags));
        }
        if (empty($tags)) {
            if ($objTpl->blockExists($templateBlockPrefix . 'news_no_tags')) {
                $objTpl->setVariable($templateVariablePrefix . 'TXT_NEWS_NO_TAGS_FOUND', $_ARRAYLANG['TXT_NEWS_NO_TAGS_FOUND']);
                $objTpl->touchBlock($templateBlockPrefix . 'news_no_tags');
            }
            return;
        }
        if ($setMetaKeys) {
            $this->newsMetaKeys = implode(',', $tags);
        }
        $tagCount = count($tags);
        $currentTagCount = 0;
        if (    $objTpl->blockExists($templateBlockPrefix . $block)
            &&  !empty($tags)
        ) {
            foreach ($tags as $tag) {
                ++$currentTagCount;
                $newsLink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                    'News',
                    '',
                    FRONTEND_LANG_ID,
                    array('tag'=> urlencode($tag))
                );
                $objTpl->setVariable(
                    array(
                        $templateVariablePrefix . 'NEWS_TAG_NAME' => contrexx_raw2xhtml($tag),
                        $templateVariablePrefix . 'NEWS_TAG_LINK' =>
                            '<a class="tags" href="' . $newsLink . '">'
                            . contrexx_raw2xhtml(ucfirst($tag))
                            . '</a>'//Including the tag separator
                            . (($currentTagCount < $tagCount) ? ',' : '')
                    )
                );
                $objTpl->parse($templateBlockPrefix . $block);
            }
            if ($objTpl->blockExists($templateBlockPrefix . 'news_tags_container')) {
                $objTpl->touchBlock($templateBlockPrefix . 'news_tags_container');
            }
        }
    }

    /**
     * Increment the tags viewing count
     *
     * @global object $objDatabase
     * @param array $tagIds tag ids
     * @return null
     */
    public function incrementViewingCount($tagIds = array())
    {
        global $objDatabase;

        if (empty($tagIds) || !is_array($tagIds)) {
            return;
        }
        //Update the tag using count
        $objDatabase->Execute(
            'UPDATE `'
            . DBPREFIX . 'module_news_tags`
            SET `viewed_count` = `viewed_count`+1
            WHERE `id` IN (' . implode(', ', contrexx_input2int($tagIds)) . ')'
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
        $newsTagsFormated  = '"' . implode('", "', contrexx_raw2xhtml(array_map('addslashes', $allNewsTags))) . '"';
        $placeholderText = $_ARRAYLANG['TXT_NEWS_ADD_TAGS'];
        $jsCode = <<< EOF
cx.jQuery(document).ready(function() {
var encoded = [$newsTagsFormated];
var decoded = [];
cx.jQuery.each(encoded, function(key, value){
    decoded.push(\$J("<div/>").html(value).text());
});
cx.jQuery("#$newsTagId").tagit({
    fieldName: "newsTags[]",
        availableTags : decoded,
        placeholderText : "$placeholderText",
        allowSpaces : true,
        afterTagAdded: function(event, object) {
            var tagDecoded = cx.jQuery("<div/>").html(object.tagLabel).text();
            object.tag.find('input').val(tagDecoded);
        }
    });
});
EOF;
        \JS::registerCode($jsCode);
    }

    /**
     * Lists all active comments of the news message specified by $messageId
     *
     * @param object  $objTpl            Template object \Cx\Core\Html\Sigma
     * @param integer $messageId         News message-ID
     * @param integer $newsCommentActive Status of news comment activation
     *
     * @return null
     */
    public function parseCommentsOfMessage($objTpl, $messageId, $newsCommentActive, $templatePrefix = '')
    {
        global $objDatabase, $_ARRAYLANG;

        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        // abort if template block is missing
        if (!$objTpl->blockExists($templateBlockPrefix . 'news_comments')) {
            return;
        }

        // abort if commenting system is not active
        if (!$this->arrSettings['news_comments_activated']) {
            $objTpl->hideBlock($templateBlockPrefix . 'news_comments');
            return;
        }

        // abort if comment deactivated for this news
        if (!$newsCommentActive) {
            $objTpl->hideBlock($templateBlockPrefix . 'news_comments');
            return;
        }

        $query = '  SELECT      `title`,
                                `date`,
                                `poster_name`,
                                `userid`,
                                `text`
                    FROM        `'.DBPREFIX.'module_news_comments`
                    WHERE       `newsid` = '.$messageId.' AND `is_active` = "1"
                    ORDER BY    `date` DESC';

        $objResult = $objDatabase->Execute($query);

        // no comments for this message found
        if (!$objResult || $objResult->EOF) {
            if ($objTpl->blockExists($templateBlockPrefix . 'news_no_comment')) {
                $objTpl->setVariable($templateVariablePrefix . 'TXT_NEWS_COMMENTS_NONE_EXISTING', $_ARRAYLANG['TXT_NEWS_COMMENTS_NONE_EXISTING']);
                $objTpl->parse($templateBlockPrefix . 'news_no_comment');
            }

            $objTpl->hideBlock($templateBlockPrefix . 'news_comment_list');
            $objTpl->parse($templateBlockPrefix . 'news_comments');

            return;
        }

// TODO: Add AJAX-based paging
        /*$count = $objResult->RecordCount();
        if ($count > intval($_CONFIG['corePagingLimit'])) {
            $paging = getPaging($count, $pos, '&amp;section=News&amp;cmd=details&amp;newsid='.$messageId, $_ARRAYLANG['TXT_NEWS_COMMENTS'], true);
        }
        $objTpl->setVariable('COMMENTS_PAGING', $paging);*/

        $i = 0;
        while (!$objResult->EOF) {
            self::parseUserAccountData($objTpl, $objResult->fields['userid'], $objResult->fields['poster_name'], $templateBlockPrefix . 'news_comments_poster');

            $objTpl->setVariable(array(
               $templateVariablePrefix . 'NEWS_COMMENTS_CSS'          => 'row'.($i % 2 + 1),
               $templateVariablePrefix . 'NEWS_COMMENTS_TITLE'        => contrexx_raw2xhtml($objResult->fields['title']),
               $templateVariablePrefix . 'NEWS_COMMENTS_MESSAGE'      => nl2br(contrexx_raw2xhtml($objResult->fields['text'])),
               $templateVariablePrefix . 'NEWS_COMMENTS_LONG_DATE'    => date(ASCMS_DATE_FORMAT, $objResult->fields['date']),
               $templateVariablePrefix . 'NEWS_COMMENTS_DATE'         => date(ASCMS_DATE_FORMAT_DATE, $objResult->fields['date']),
               $templateVariablePrefix . 'NEWS_COMMENTS_TIME'         => date(ASCMS_DATE_FORMAT_TIME, $objResult->fields['date']),
               $templateVariablePrefix . 'NEWS_COMMENTS_TIMESTAMP'    => $objResult->fields['date'],
            ));

            $objTpl->parse($templateBlockPrefix . 'news_comment');
            $i++;
            $objResult->MoveNext();
        }

        $objTpl->parse($templateBlockPrefix . 'news_comment_list');
        $objTpl->hideBlock($templateBlockPrefix . 'news_no_comment');
    }

    /**
     * Validates the submitted comment data and writes it to the databse if valid.
     * Additionally, a notification is send out to the administration about the comment
     * by e-mail (only if the corresponding configuration option is set to do so).
     *
     * @param object  $objTpl            Template object \Cx\Core\Html\Sigma
     * @param integer $newsMessageId     News message ID for which the comment shall be stored
     * @param string  $newsMessageTitle  Title of the news message for which the comment shall be stored.
     *                                   The title will be used in the notification e-mail
     *                                   {@link NewsLibrary::storeMessageComment()}
     * @param integer $newsCommentActive Status of news comment activation
     *
     * @return null
     */
    public function parseMessageCommentForm($objTpl, $newsMessageId, $newsMessageTitle, $newsCommentActive, $templatePrefix = '')
    {
        global $_CORELANG, $_ARRAYLANG;

        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        // abort if template block is missing
        if (!$objTpl->blockExists($templateBlockPrefix . 'news_add_comment')) {
            return;
        }

        // abort if comment system is deactivated
        if (!$this->arrSettings['news_comments_activated']) {
            $objTpl->hideBlock($templateBlockPrefix . 'news_add_comment');
            return;
        }

        // abort if comment deactivated for this news
        if (!$newsCommentActive) {
            $objTpl->hideBlock($templateBlockPrefix . 'news_add_comment');
            return;
        }

        // abort if request is unauthorized
        if (   $this->arrSettings['news_comments_anonymous'] == '0'
            && !\FWUser::getFWUserObject()->objUser->login()
        ) {
            $objTpl->hideBlock($templateBlockPrefix . 'news_add_comment');
            return;
        }

        $name = '';
        $title = '';
        $message = '';
        $error = '';

        $arrData = $this->fetchSubmittedCommentData();
        if ($arrData) {
            $name    = $arrData['name'];
            $title   = $arrData['title'];
            $message = $arrData['message'];
            list($status, $error) = $this->storeMessageComment($newsMessageId, $newsMessageTitle, $name, $title, $message);

            // new comment added successfully
            if ($status) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_add_comment');
                return;
            }
        }

        \JS::activate('cx');

        // create submit from
        if (\FWUser::getFWUserObject()->objUser->login()) {
            $objTpl->hideBlock($templateBlockPrefix . 'news_add_comment_name');
            $objTpl->hideBlock($templateBlockPrefix . 'news_add_comment_captcha');
        } else {
            // Anonymous guests must enter their name as well as validate a CAPTCHA

            $objTpl->setVariable(array(
                $templateVariablePrefix . 'NEWS_COMMENT_NAME' => contrexx_raw2xhtml($name),
                $templateVariablePrefix . 'TXT_NEWS_NAME'     => $_ARRAYLANG['TXT_NEWS_NAME'],
            ));
            $objTpl->parse($templateBlockPrefix . 'news_add_comment_name');

            // parse CAPTCHA
            $objTpl->setVariable(array(
                $templateVariablePrefix . 'TXT_NEWS_CAPTCHA'          => $_CORELANG['TXT_CORE_CAPTCHA'],
                $templateVariablePrefix . 'NEWS_COMMENT_CAPTCHA_CODE' => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
            ));
            $objTpl->parse($templateBlockPrefix . 'news_add_comment_captcha');
        }

        $objTpl->setVariable(array(
            $templateVariablePrefix . 'NEWS_ID'               => $newsMessageId,
            $templateVariablePrefix . 'NEWS_ADD_COMMENT_ERROR'=> $error,
            $templateVariablePrefix . 'NEWS_COMMENT_TITLE'    => contrexx_raw2xhtml($title),
            $templateVariablePrefix . 'NEWS_COMMENT_MESSAGE'  => contrexx_raw2xhtml($message),
            $templateVariablePrefix . 'TXT_NEWS_ADD_COMMENT'  => $_ARRAYLANG['TXT_NEWS_ADD_COMMENT'],
            $templateVariablePrefix . 'TXT_NEWS_TITLE'        => $_ARRAYLANG['TXT_NEWS_TITLE'],
            $templateVariablePrefix . 'TXT_NEWS_COMMENT'      => $_ARRAYLANG['TXT_NEWS_COMMENT'],
            $templateVariablePrefix . 'TXT_NEWS_ADD'          => $_ARRAYLANG['TXT_NEWS_ADD'],
            $templateVariablePrefix . 'TXT_NEWS_WRITE_COMMENT'=> $_ARRAYLANG['TXT_NEWS_WRITE_COMMENT'],
        ));

        $objTpl->parse($templateBlockPrefix . 'news_add_comment');
    }

    /**
     * Fetch news comment data that has been submitted via POST
     * and return it as array with three elements.
     * Where the first element is the name of the poster (if poster is anonymous),
     * the second is the title of the comment and the third is the comment
     * message by it self.
     *
     * @return array
     */
    public function fetchSubmittedCommentData()
    {
        // only proceed if the user did submit any data
        if (!isset($_POST['news_add_comment'])) {
            return false;
        }

        $arrData = array(
            'name'    => '',
            'title'   => '',
            'message' => '',
        );

        if (isset($_POST['news_comment_name'])) {
            $arrData['name'] = contrexx_input2raw(trim($_POST['news_comment_name']));
        }

        if (isset($_POST['news_comment_title'])) {
            $arrData['title'] = contrexx_input2raw(trim($_POST['news_comment_title']));
        }

        if (isset($_POST['news_comment_message'])) {
            $arrData['message'] = contrexx_input2raw(trim($_POST['news_comment_message']));
        }

        return $arrData;
    }

    /**
     * Validates the submitted comment data and writes it to the databse if valid.
     * Additionally, a notification is send out to the administration about the comment
     * by e-mail (only if the corresponding configuration option is set to do so).
     *
     * @param   integer News message ID for which the comment shall be stored
     * @param   string  Title of the news message for which the comment shall be stored.
     *                  The title will be used in the notification e-mail
     * @param   string  The poster's name of the comment
     * @param   string  The comment's title
     * @param   string  The comment's message text
     * @global    ADONewConnection
     * @global    array
     * @global    array
     * @global    array
     * @return  array   Returns an array of two elements. The first is either TRUE on success or FALSE on failure.
     *                  The second element contains an error message on failure.
     */
    public function storeMessageComment($newsMessageId, $newsMessageTitle, $name, $title, $message)
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        if (!isset($_SESSION['news'])) {
            $_SESSION['news'] = array();
            $_SESSION['news']['comments'] = array();
        }

        // just comment
        if ($this->checkForCommentFlooding($newsMessageId)) {
            return array(
                false,
                sprintf($_ARRAYLANG['TXT_NEWS_COMMENT_INTERVAL_MSG'],
                        //DateTimeTool::getLiteralStringOfSeconds($this->arrSettings['news_comments_timeout'])),
                        $this->arrSettings['news_comments_timeout']),
            );
        }

        if (empty($title)) {
            return array(false, $_ARRAYLANG['TXT_NEWS_MISSING_COMMENT_TITLE']);
        }

        if (empty($message)) {
            return array(false, $_ARRAYLANG['TXT_NEWS_MISSING_COMMENT_MESSAGE']);
        }


        $date = time();
        $userId = 0;
        if (\FWUser::getFWUserObject()->objUser->login()) {
            $userId = \FWUser::getFWUserObject()->objUser->getId();
            $name = \FWUser::getParsedUserTitle($userId);
        } elseif ($this->arrSettings['news_comments_anonymous'] == '1') {
            // deny comment if the poster did not specify his name
            if (empty($name)) {
                return array(false, $_ARRAYLANG['TXT_NEWS_POSTER_NAME_MISSING']);
            }

            // check CAPTCHA for anonymous posters
            if (!\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
                return array(false, null);
            }
        } else {
            // Anonymous comments are not allowed
            return array(false, null);
        }

        $isActive  = $this->arrSettings['news_comments_autoactivate'];
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $ipAddress = $cx->getComponent(
            'Stats'
        )->getCounterInstance()->getUniqueUserId();

        $objResult = $objDatabase->Execute("
            INSERT INTO `".DBPREFIX."module_news_comments`
                    SET `title` = '".contrexx_raw2db($title)."',
                        `text` = '".contrexx_raw2db($message)."',
                        `newsid` = '".contrexx_raw2db($newsMessageId)."',
                        `date` = '".contrexx_raw2db($date)."',
                        `poster_name` = '".contrexx_raw2db($name)."',
                        `userid` = '".contrexx_raw2db($userId)."',
                        `ip_address` = '".contrexx_raw2db($ipAddress)."',
                        `is_active` = '".contrexx_raw2db($isActive)."'");
        if (!$objResult) {
            return array(false, $_ARRAYLANG['TXT_NEWS_COMMENT_SAVE_ERROR']);
        }

        /* Prevent comment flooding from same user:
           Either user is authenticated or had to validate a CAPTCHA.
           In either way, a Cloudrexx session had been initialized,
           therefore we are able to use the $_SESSION to log this comment */
        $_SESSION['news']['comments'][$newsMessageId] = $date;

        // Don't send a notification e-mail to the administrator
        if (!$this->arrSettings['news_comments_notification']) {
            return array(true, null);
        }

        // Send a notification e-mail to administrator
        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreGlobalPageTitle']);
        $objMail->IsHTML(false);
        $objMail->Subject   = sprintf($_ARRAYLANG['TXT_NEWS_COMMENT_NOTIFICATION_MAIL_SUBJECT'], $newsMessageTitle);

        $manageCommentsUrl = \Cx\Core\Routing\Url::fromDocumentRoot(array(
            'cmd' => 'News',
            'act' => 'comments',
            'newsId' => $newsMessageId,
        ));
        $manageCommentsUrl->setPath(
            substr(
                \Cx\Core\Core\Controller\Cx::instanciate()->getBackendFolderName(),
                1
            ) .
            '/index.php'
        );
        $manageCommentsUrl->setMode('backend');
        $manageCommentsUrl = $manageCommentsUrl->toString();

        $activateCommentTxt = $this->arrSettings['news_comments_autoactivate']
                              ? ''
                              : sprintf($_ARRAYLANG['TXT_NEWS_COMMENT_NOTIFICATION_MAIL_LINK'], $manageCommentsUrl);
        $objMail->Body      = sprintf($_ARRAYLANG['TXT_NEWS_COMMENT_NOTIFICATION_MAIL_BODY'],
                                      $_CONFIG['domainUrl'],
                                      $newsMessageTitle,
                                      \FWUser::getParsedUserTitle($userId, $name),
                                      $title,
                                      nl2br($message),
                                      $activateCommentTxt);
        $objMail->AddAddress($_CONFIG['coreAdminEmail']);
        if (!$objMail->Send()) {
            \DBG::msg('Sending of notification e-mail failed');
            //DBG::stack();
        }

        return array(true, null);
    }

    /**
     * Check if the current user has already written a comment within
     * the definied timeout-time set by news_comments_timeout.
     *
     * @param   integer News message-ID
     * @global  object
     * @return  boolean TRUE, if the user hast just written a comment before.
     */
    public function checkForCommentFlooding($newsMessageId)
    {
        global $objDatabase;

        //Check cookie first
        if (!empty($_SESSION['news']['comments'][$newsMessageId])) {
            $intLastCommentTime = intval($_SESSION['news']['comments'][$newsMessageId]);
            if (time() < $intLastCommentTime + intval($this->arrSettings['news_comments_timeout'])) {
                //The current system-time is smaller than the time in the session plus timeout-time, so the user just submitted a comment
                return true;
            }
        }

        //Now check database (make sure the user didn't delete the cookie
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $ipAddress = $cx->getComponent(
            'Stats'
        )->getCounterInstance()->getUniqueUserId();
        $objResult = $objDatabase->SelectLimit('
            SELECT
                1
            FROM
                `' . DBPREFIX . 'module_news_comments`
            WHERE
                `ip_address` = "' . $ipAddress . '" AND
                `date` > ' . (time() - intval($this->arrSettings['news_comments_timeout']))
        );
        if ($objResult && !$objResult->EOF) {
            return true;
        }

        //Nothing found, i guess the user didn't comment within the timeout-period.
        return false;
    }

    /**
     * Get the News important/source link
     *
     * @param string $linkSource
     * @return string
     */
    public function getNewsLink($linkSource)
    {
        if (empty($linkSource)) {
            return '';
        }

        static $linkSourceTag = '<a target="_blank" href="%1$s" title="%1$s">%2$s</a>';

        $strSource = $linkSource;
        if (strlen($strSource) > 40) {
            $strSource = substr($strSource, 0, 26) . '...' . substr($strSource, (strrpos($strSource, '.')));
        }
        return sprintf($linkSourceTag, $linkSource, contrexx_raw2xhtml($strSource));
    }

    /**
     * Parse the news placeholders
     *
     * @param object $objTpl       Template object \Cx\Core\Html\Sigma
     * @param array  $objResult    Result Array
     * @param string $newsUrl      News Url
     * @return string
     */
    public function parseNewsPlaceholders(
        $objTpl,
        $objResult,
        $newsUrl,
        $templatePrefix = '',
        $selectedCategories = array()
    ) {
        global $_ARRAYLANG;

        $newsid = $objResult->fields['newsid'];

        if (empty($newsid)) {
            return;
        }

        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        $newstitle            = !empty($objResult->fields['newstitle']) ? $objResult->fields['newstitle'] : '';
        $newsCommentActive    = !empty($objResult->fields['commentactive']) ? $objResult->fields['commentactive'] : '';
        $source               = !empty($objResult->fields['source']) ? $objResult->fields['source'] : '';
        $url1                 = !empty($objResult->fields['url1']) ? $objResult->fields['url1'] : '';
        $url2                 = !empty($objResult->fields['url2']) ? $objResult->fields['url2'] : '';
        $text                 = !empty($objResult->fields['text']) ? $objResult->fields['text'] : '';
        $redirect             = !empty($objResult->fields['redirect']) ? $objResult->fields['redirect'] : '';
        $newsLastUpdate       = !empty($objResult->fields['changelog'])
                                    ? $_ARRAYLANG['TXT_LAST_UPDATE'].'<br />' . date(ASCMS_DATE_FORMAT, $objResult->fields['changelog'])
                                    : '';
        $newsTeaser           = '';
        $arrNewsCategories = $this->getCategoriesByNewsId(
            $newsid,
            $selectedCategories
        );

        if ($this->arrSettings['news_use_teaser_text']) {
            $newsTeaser = nl2br($objResult->fields['teaser_text']);
            \LinkGenerator::parseTemplate($newsTeaser);
        }

        $redirectNewWindow = !empty($objResult->fields['redirect']) && !empty($objResult->fields['redirectNewWindow']);
        $htmlLink = self::parseLink($newsUrl, $newstitle, contrexx_raw2xhtml('[' . $_ARRAYLANG['TXT_NEWS_MORE'] . '...]'), $redirectNewWindow);
        $htmlLinkTitle = self::parseLink($newsUrl, $newstitle, contrexx_raw2xhtml($newstitle), $redirectNewWindow);
        $linkTarget = $redirectNewWindow ? '_blank' : '_self';
        // in case that the message is a stub, we shall just display the news title instead of a html-a-tag with no href target
        if (empty($htmlLinkTitle)) {
            $htmlLinkTitle = contrexx_raw2xhtml($newstitle);
        }

        if (    empty($arrNewsCategories)
            &&  $objTpl->blockExists($templateBlockPrefix . 'newsCategories')) {
            $objTpl->hideBlock($templateBlockPrefix . 'newsCategories');
        }

        // Parse the Category list
        $this->parseCategoryList($objTpl, $arrNewsCategories, $templatePrefix);

        $author    = \FWUser::getParsedUserTitle($objResult->fields['author_id'], $objResult->fields['author']);
        $publisher = \FWUser::getParsedUserTitle($objResult->fields['publisher_id'], $objResult->fields['publisher']);

        $objTpl->setVariable(array(
           $templateVariablePrefix . 'NEWS_ID'             => $newsid,
           $templateVariablePrefix . 'NEWS_TEASER'         => $this->arrSettings['news_use_teaser_text'] ? nl2br($objResult->fields['teaser_text']) : '',
           $templateVariablePrefix . 'NEWS_TEASER_TEXT'    => $newsTeaser,
           $templateVariablePrefix . 'NEWS_LASTUPDATE'     => $newsLastUpdate,
           $templateVariablePrefix . 'NEWS_TITLE'          => contrexx_raw2xhtml($newstitle),
           $templateVariablePrefix . 'NEWS_LONG_DATE'      => date(ASCMS_DATE_FORMAT, $objResult->fields['newsdate']),
           $templateVariablePrefix . 'NEWS_DATE'           => date(ASCMS_DATE_FORMAT_DATE, $objResult->fields['newsdate']),
           $templateVariablePrefix . 'NEWS_TIME'           => date(ASCMS_DATE_FORMAT_TIME, $objResult->fields['newsdate']),
           $templateVariablePrefix . 'NEWS_TIMESTAMP'      => $objResult->fields['newsdate'],
           $templateVariablePrefix . 'NEWS_LINK_TITLE'     => $htmlLinkTitle,
           $templateVariablePrefix . 'NEWS_LINK'           => $htmlLink,
           $templateVariablePrefix . 'NEWS_LINK_URL'       => contrexx_raw2xhtml($newsUrl),
           $templateVariablePrefix . 'NEWS_LINK_TARGET'    => $linkTarget,
           $templateVariablePrefix . 'NEWS_CATEGORY'       => implode(', ', contrexx_raw2xhtml($arrNewsCategories)),
           $templateVariablePrefix . 'NEWS_CATEGORY_NAME'  => implode(', ', contrexx_raw2xhtml($arrNewsCategories)),
           $templateVariablePrefix . 'NEWS_TYPE_ID'        => $objResult->fields['typeid'],
           $templateVariablePrefix . 'NEWS_TYPE_NAME'      => contrexx_raw2xhtml($this->getTypeNameById($objResult->fields['typeid'])),
           $templateVariablePrefix . 'NEWS_PUBLISHER'      => contrexx_raw2xhtml($publisher),
           $templateVariablePrefix . 'NEWS_AUTHOR'         => contrexx_raw2xhtml($author),

           // Backward compatibility for templates pre 3.0
           $templateVariablePrefix . 'HEADLINE_ID'       => $newsid,
           $templateVariablePrefix . 'HEADLINE_DATE'     => date(ASCMS_DATE_FORMAT_DATE, $objResult->fields['newsdate']),
           $templateVariablePrefix . 'HEADLINE_TEXT'     => $newsTeaser,
           $templateVariablePrefix . 'HEADLINE_LINK'     => $htmlLinkTitle,
           $templateVariablePrefix . 'HEADLINE_AUTHOR'   => contrexx_raw2xhtml($author),
        ));

        // parse detail link
        if ($objTpl->blockExists($templateBlockPrefix . 'news_url')) {
            if (empty($newsUrl)) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_url');
            } else {
                $objTpl->touchBlock($templateBlockPrefix . 'news_url');
            }
        }

        // parse 'combined' external link
        $newsUrlLink = '';
        if (!empty($url1)) {
            $newsUrlLink = $_ARRAYLANG['TXT_IMPORTANT_HYPERLINKS'] . '<br />' . $this->getNewsLink($url1) . '<br />';
        }
        if (!empty($url2)) {
            $newsUrlLink .= $this->getNewsLink($url2).'<br />';
        }
        $objTpl->setVariable(
            $templateVariablePrefix . 'NEWS_URL',
            $newsUrlLink
        );

        // parse external source
        $newsSourceLink = '';
        $newsSource = '';
        if (!empty($source)) {
            $newsSourceLink = $this->getNewsLink($source);
            $newsSource = $_ARRAYLANG['TXT_NEWS_SOURCE'] . '<br />'. $newsSourceLink . '<br />';
        }
        $objTpl->setVariable(array(
            'TXT_' . $templateVariablePrefix . 'NEWS_SOURCE' =>
                $_ARRAYLANG['TXT_NEWS_SOURCE'],
            $templateVariablePrefix . 'NEWS_SOURCE'     => $newsSource,
            $templateVariablePrefix . 'NEWS_SOURCE_LINK'=> $newsSourceLink,
            $templateVariablePrefix . 'NEWS_SOURCE_SRC' => $source,
        ));
        if ($objTpl->blockExists($templateBlockPrefix . 'news_source')) {
            if (empty($source)) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_source');
            } else {
                $objTpl->touchBlock($templateBlockPrefix . 'news_source');
            }
        }

        // parse external link 1
        $objTpl->setVariable(array(
            'TXT_' . $templateVariablePrefix . 'NEWS_LINK1' =>
                $_ARRAYLANG['TXT_NEWS_LINK1'],
            $templateVariablePrefix . 'NEWS_LINK1_SRC' =>
                $url1,
        ));
        if ($objTpl->blockExists($templateBlockPrefix . 'news_link1')) {
            if (empty($url1)) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_link1');
            } else {
                $objTpl->touchBlock($templateBlockPrefix . 'news_link1');
            }
        }

        // parse external link 2
        $objTpl->setVariable(array(
            'TXT_' . $templateVariablePrefix . 'NEWS_LINK2' =>
                $_ARRAYLANG['TXT_NEWS_LINK2'],
            $templateVariablePrefix . 'NEWS_LINK2_SRC' =>
                $url2,
        ));
        if ($objTpl->blockExists($templateBlockPrefix . 'news_link2')) {
            if (empty($url2)) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_link2');
            } else {
                $objTpl->touchBlock($templateBlockPrefix . 'news_link2');
            }
        }

        // hide teaser container if the use of teasers has been deactivated
        if (
            $this->arrSettings['news_use_teaser_text'] != '1' &&
            $objTpl->blockExists($templateBlockPrefix . 'news_use_teaser_text')
        ) {
            $objTpl->hideBlock($templateBlockPrefix . 'news_use_teaser_text');
        }

        // Parse the news comments count
        $this->parseNewsCommentsCount($objTpl, $newsid, $newsCommentActive, $templatePrefix);
        // The news_text block will be hidden if the news is set to redirect type
        $this->showNewsTextOrRedirectLink($objTpl, $text, $redirect, $templatePrefix);
        // Parse the author account data
        self::parseUserAccountData($objTpl, $objResult->fields['author_id'], $objResult->fields['author'], $templateBlockPrefix . 'news_author');
        // Parse the publisher account data
        self::parseUserAccountData($objTpl, $objResult->fields['publisher_id'], $objResult->fields['publisher'], $templateBlockPrefix . 'news_publisher');
        // Parse the message comment form
        $this->parseMessageCommentForm($objTpl, $newsid, $newstitle, $newsCommentActive, $templatePrefix);
        // Parse the comments of the message
        $this->parseCommentsOfMessage($objTpl, $newsid, $newsCommentActive, $templatePrefix);

        // Parse the image block
        list($image, $htmlLinkImage, $imageSource) = self::parseImageThumbnail($objResult->fields['teaser_image_path'],
                                                                               $objResult->fields['teaser_image_thumbnail_path'],
                                                                               $newstitle,
                                                                               $newsUrl);
        if (!empty($image)) {
            $objTpl->setVariable(array(
                $templateVariablePrefix . 'NEWS_IMAGE_ID'            => $newsid,
                $templateVariablePrefix . 'NEWS_IMAGE'               => $image,
                $templateVariablePrefix . 'NEWS_IMAGE_SRC'           => contrexx_raw2xhtml($imageSource),
                $templateVariablePrefix . 'NEWS_IMAGE_ALT'           => contrexx_raw2xhtml($newstitle),
                $templateVariablePrefix . 'NEWS_IMAGE_LINK'          => $htmlLinkImage,
                $templateVariablePrefix . 'NEWS_IMAGE_LINK_URL'      => contrexx_raw2xhtml($newsUrl),

                // Backward compatibility for templates pre 3.0
                $templateVariablePrefix . 'HEADLINE_IMAGE_PATH'     => contrexx_raw2xhtml($objResult->fields['teaser_image_path']),
                $templateVariablePrefix . 'HEADLINE_THUMBNAIL_PATH' => contrexx_raw2xhtml($imageSource),
            ));

            if ($objTpl->blockExists($templateBlockPrefix . 'news_image')) {
                $objTpl->parse($templateBlockPrefix . 'news_image');
            }
            if ($objTpl->blockExists($templateBlockPrefix . 'news_no_image')) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_no_image');
            }
        } else {
            if ($objTpl->blockExists($templateBlockPrefix . 'news_image')) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_image');
            }
            if ($objTpl->blockExists($templateBlockPrefix . 'news_no_image')) {
                $objTpl->touchBlock($templateBlockPrefix . 'news_no_image');
            }
        }

        self::parseImageBlock($objTpl, $objResult->fields['teaser_image_thumbnail_path'], $newstitle, $newsUrl, 'image_thumbnail', $templatePrefix);
        self::parseImageBlock($objTpl, $objResult->fields['teaser_image_path'], $newstitle, $newsUrl, 'image_detail', $templatePrefix);

        // Parse the tagsBlock, This block exist only if the 'Use tags' is active
        if (   !empty($this->arrSettings['news_use_tags'])
            && !empty($objResult->fields['enable_tags'])
        ) {
            $this->parseNewsTags($objTpl, $newsid, 'news_tag_list', false, $templatePrefix);
        }
    }

    /**
     * Show News Text content or Redirect link
     *
     * @param object $objTpl       Template object \Cx\Core\Html\Sigma
     * @param string $text         Text news content
     * @param string $redirect     News redirect link
     */
    public function showNewsTextOrRedirectLink($objTpl, $text, $redirect, $templatePrefix = '')
    {
        global $_ARRAYLANG;

        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        // The news_text block will be hidden if the news is set to redirect type
        if (empty($redirect)) {
            $text = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $text);
            \LinkGenerator::parseTemplate($text);
            $objTpl->setVariable($templateVariablePrefix . 'NEWS_TEXT', $text);

            // parse short html version of news text,
            // but only if placeholder is present, as the parsing costs
            // a lot of time
            if ($objTpl->placeholderExists($templateVariablePrefix . 'NEWS_TEXT_SHORT')) {
                // cut html in length by maximum 200 output characters
                $shortText = $text;
                \FWValidator::cutHtmlByDisplayLength($shortText, 200, ' ...');

                $objTpl->setVariable(
                    $templateVariablePrefix . 'NEWS_TEXT_SHORT', $shortText
                );
            }

            if ($objTpl->blockExists($templateBlockPrefix . 'news_text')) {
                $objTpl->parse($templateBlockPrefix . 'news_text');
            }
            if ($objTpl->blockExists($templateBlockPrefix . 'news_redirect')) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_redirect');
            }
        } else {
            if (\FWValidator::isUri($redirect)) {
                $redirectName = preg_replace('#^https?://#', '', $redirect);
                //} elseif (\FWValidator::isEmail($redirect)) {
                //$redirectName
            } else {
                $redirectName = basename($redirect);
            }

            $objTpl->setVariable(array(
                $templateVariablePrefix . 'TXT_NEWS_REDIRECT_INSTRUCTION' => $_ARRAYLANG['TXT_NEWS_REDIRECT_INSTRUCTION'],
                $templateVariablePrefix . 'NEWS_REDIRECT_URL'             => $redirect,
                $templateVariablePrefix . 'NEWS_REDIRECT_NAME'            => contrexx_raw2xhtml($redirectName),
            ));
            if ($objTpl->blockExists($templateBlockPrefix . 'news_redirect')) {
                $objTpl->parse($templateBlockPrefix . 'news_redirect');
            }
            if ($objTpl->blockExists($templateBlockPrefix . 'news_text')) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_text');
            }
        }
    }

    /**
     * Parse the category list
     *
     * @param object $objTpl          Template object \Cx\Core\Html\Sigma
     * @param array  $newsCategories  News categories array by its news message id
     */
    public function parseCategoryList($objTpl, $newsCategories, $templatePrefix = '')
    {
        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        if (!empty($newsCategories) && $objTpl->blockExists($templateBlockPrefix . 'news_category_list')) {
            foreach ($newsCategories as $catId => $catTitle) {

                $url = null;
                try {
                    $url = \Cx\Core\Routing\Url::fromModuleAndCmd('News', $catId, '', array(), '', false);
                } catch (\Cx\Core\Routing\UrlException $e) {}
                if (!$url) {
                    try {
                        $url = \Cx\Core\Routing\Url::fromModuleAndCmd('News', '', '', array(), '', false);
                    } catch (\Cx\Core\Routing\UrlException $e) {}
                }

                $objTpl->setVariable(array(
                    $templateVariablePrefix . 'NEWS_CATEGORY_TITLE'   => contrexx_raw2xhtml($catTitle),
                    $templateVariablePrefix . 'NEWS_CATEGORY_ID'      => contrexx_input2int($catId),
                    $templateVariablePrefix . 'NEWS_CATEGORY_URL'      => contrexx_raw2xhtml($url),
                ));
                if ($objTpl->blockExists($templateBlockPrefix . 'news_category_url')) {
                    if ($url) {
                        $objTpl->touchBlock($templateBlockPrefix . 'news_category_url');
                    } else {
                        $objTpl->hideBlock($templateBlockPrefix . 'news_category_url');
                    }
                }
                $objTpl->parse($templateBlockPrefix . 'news_category');
            }
        }
    }

    /**
     * Parse the news comments count
     *
     * @param object  $objTpl            Template object \Cx\Core\Html\Sigma
     * @param integer $newsid            News message ID
     * @param integer $newsCommentActive News comment active
     */
    public function parseNewsCommentsCount($objTpl, $newsid, $newsCommentActive, $templatePrefix = '')
    {
        global $objDatabase, $_ARRAYLANG;

        if (empty($newsid)) {
            return;
        }

        $templateVariablePrefix = strtoupper($templatePrefix);
        $templateBlockPrefix = strtolower($templatePrefix);

        $objSubResult = $objDatabase->Execute('SELECT count(`id`) AS `countComments` FROM `'.DBPREFIX.'module_news_comments` WHERE `newsid` = '. $newsid);
        $countComment =  ($newsCommentActive && $this->arrSettings['news_comments_activated'])
                            ?  contrexx_raw2xhtml($objSubResult->fields['countComments'] . ' ' . $_ARRAYLANG['TXT_NEWS_COMMENTS'])
                            : '';
        $objTpl->setVariable($templateVariablePrefix . 'NEWS_COUNT_COMMENTS', $countComment);

        if (!$newsCommentActive || !$this->arrSettings['news_comments_activated']) {
            if ($objTpl->blockExists($templateBlockPrefix . 'news_comments_count')) {
                $objTpl->hideBlock($templateBlockPrefix . 'news_comments_count');
            }
        }
    }

    /**
     * Get all the News global placeholder names
     *
     * @return array
     */
    public function getNewsGlobalPlaceholderNames()
    {
        $placeholders = array(
            'TOP_NEWS_FILE',
            'NEWS_CATEGORIES',
            'NEWS_ARCHIVES',
            'NEWS_RECENT_COMMENTS_FILE'
        );

        // Get Headlines placeholders
        for ($i = 1; $i <= 10; $i++) {
            $id = '';
            if ($i > 1) {
                $id = $i;
            }
            $placeholders[] = 'HEADLINES' . $id . '_FILE';
        }

        // Set news teasers
        $teaser      = new Teasers();
        $teaserNames = array_flip($teaser->arrTeaserFrameNames);
        if (empty($teaserNames)) {
            return $placeholders;
        }

        foreach ($teaserNames as $teaserName) {
            $placeholders[] = 'TEASERS_' . strtoupper($teaserName);
        }

        return $placeholders;
    }

    public function parseTagCloud($template, $langId) {
        // STEP 1: Fetch base data

        // filter by access level
        $protection = '';
        if (
            $this->arrSettings['news_message_protection'] == '1' &&
            !\Permission::hasAllAccess()
        ) {
            $objFWUser = \FWUser::getFWUserObject();
            if (
                $objFWUser &&
                $objFWUser->objUser->login()
            ) {
                $protection = 'AND (`news`.frontend_access_id IN ('.
                    implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).
                    ') OR `news`.userid='.$objFWUser->objUser->getId().')';
            } else {
                $protection = 'AND `news`.frontend_access_id=0';
            }
        }

        // filter by category
        $category = '';
        $categoryJoin = '';
        $categoryId = null;
        $includeSubCategories = false;
        $catMatches = null;
        $catIds = array();
        if (
            preg_match(
                '/\{CATEGORY_([0-9]+)(_FULL)?\}/',
                $template->_blocks['news_tag_cloud'],
                $catMatches
            )
        ) {
            $categoryId = $catMatches[1];
            $includeSubCategories = !empty($catMatches[2]);
        }
        if ($categoryId && $includeSubCategories) {
            $catIds = $this->getCatIdsFromNestedSetArray($this->getNestedSetCategories($categoryId));
        } elseif ($categoryId) {
            $catIds = array($categoryId);
        }
        if ($catIds) {
            $category = 'AND `category`.category_id IN (' . join(',', $catIds) . ')';
            $categoryJoin = '
                INNER JOIN
                    `' . DBPREFIX . 'module_news_rel_categories` AS `category`
                ON
                    `category`.news_id = `locale`.news_id
            ';
        }

        $query = '
            SELECT
                `tags`.`id`,
                `tags`.`tag`,
                `tags`.`viewed_count`,
                COUNT(`newstags`.`news_id`) AS `usages`
            FROM
                `' . DBPREFIX . 'module_news_tags` AS `tags`
            INNER JOIN
                `' . DBPREFIX . 'module_news_rel_tags` AS `newstags`
            ON
                `newstags`.`tag_id` = `tags`.`id`
            INNER JOIN
                `' . DBPREFIX . 'module_news` AS `news`
            ON
                `news`.`id` = `newstags`.`news_id`
            INNER JOIN
                `' . DBPREFIX . 'module_news_locale` AS `locale`
            ON
                `locale`.news_id = `news`.id
            ' . $categoryJoin . '
            WHERE
                `news`.status = 1
            ' . $category . '
            AND
                `locale`.lang_id=' . $langId . '
            AND
                `locale`.is_active = 1
            AND (`news`.startdate<=\'' . date('Y-m-d H:i:s') . '\' OR `news`.startdate=\'0000-00-00 00:00:00\')
            AND (`news`.enddate>=\'' . date('Y-m-d H:i:s') . '\' OR `news`.enddate=\'0000-00-00 00:00:00\')
            ' . $protection . '
            GROUP BY
                `tags`.`id`
            ORDER BY
                `tags`.`tag`
        ';

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $result = $cx->getDb()->getAdoDb()->query(
            $query
        );

        if (!$result || $result->EOF) {
            $template->hideBlock('news_tag_cloud');
            return;
        }

        // STEP 2: Count sums

        $tagData = array();
        while (!$result->EOF) {
            $tagData[] = array(
                'name' => $result->fields['tag'],
                'viewed_count' => $result->fields['viewed_count'],
                'usages' => $result->fields['usages'],
            );
            $result->MoveNext();
        }
        // TODO: Check if those can be generated by query
        $totalCount = array_sum(array_column($tagData, 'viewed_count'));
        $totalUsages = array_sum(array_column($tagData, 'usages'));

        // STEP 3: Calculate tag weight values

        $viewCountFactor = 1;
        if ($totalCount) {
            $viewCountFactor = 1;// / $totalCount;
        }
        $usagesFactor = 1;
        if ($totalUsages) {
            $usagesFactor = 5;// / $totalUsages;
        }
        $tagValues = array();
        // TODO: Check if those can be generated by query
        foreach ($tagData as $tag) {
            $tagValues[$tag['name']] = 1 +
                $tag['viewed_count'] * $viewCountFactor +
                $tag['usages'] * $usagesFactor;
        }
        $i = 0;
        asort($tagValues);
        foreach ($tagValues as $tag=>$value) {
            $tagValues[$tag] = $i;
            $i++;
        }
        uksort($tagValues, function() { return rand() > getrandmax() / 2; });

        // calculate meta infos to tag values
        // TODO: This could be calculated in query
        $lowestValue = min($tagValues);
        $highestValue = max($tagValues);
        $highestOffset = $highestValue - $lowestValue;
        $tagCount = count($tagValues);

        // STEP 4: Generate output

        $cssClasses = array(
            'newsTagCloudSmallest', // first quarter of tags
            'newsTagCloudSmall', // second quarter of tags
            'newsTagCloudMedium', // third quarter of tags
            'newsTagCloudLarge', // fourth quarter of tags
            'newsTagCloudLargest', // tag(s) with highest value
        );

        foreach ($tagValues as $tag => $value) {
            // move tag values to start at 0
            $value = $value - $lowestValue;
            $cssClassIndex = 4;
            if ($value < $highestOffset) {
                $cssClassIndex = floor($value / 4);
            }
            if (!isset($cssClasses[$cssClassIndex])) {
                $cssClassIndex = 0;
            }
            $template->setVariable(array(
                'NEWS_TAG' => $tag,
                'NEWS_TAG_URL_ENCODED' => urlencode($tag),
                'NEWS_TAG_WEIGHT_CLASS' => $cssClasses[$cssClassIndex],
            ));
            $template->parse('news_tag');
        }
        $template->parse('news_tag_cloud');
    }

    /**
     * Fetch ID of latest news article. If $categoryId is specified, then
     * the ID of the latest news article of the category identified by ID
     * $categoryId is returned.
     *
     * @param   integer $categoryId ID of category to fetch the latest news
     *                              article from
     * @return  integer ID of latest news article
     * @throws  NewsLibraryException In case no latest news article was found
     */
    protected function getIdOfLatestNewsArticle($categoryId = 0) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();
        $categorySelect = '';
        if ($categoryId) {
            $categorySelect = 'AND nc.`category_id` = '.intval($categoryId);
        }
        $query = '  SELECT      n.id
                    FROM        '.DBPREFIX.'module_news AS n
                    INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    INNER JOIN '.DBPREFIX.'module_news_rel_categories AS nc ON nc.`news_id` = n.id
                    WHERE       status = 1
                                AND nl.is_active=1
                                AND nl.lang_id='.FRONTEND_LANG_ID.'
                                AND (n.startdate<=\''.date('Y-m-d H:i:s').'\' OR n.startdate="0000-00-00 00:00:00")
                                AND (n.enddate>=\''.date('Y-m-d H:i:s').'\' OR n.enddate="0000-00-00 00:00:00")
                                ' . $categorySelect
                               .($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess() ? (
                                    ($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login() ?
                                        " AND (frontend_access_id IN (".implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).") OR userid = ".$objFWUser->objUser->getId().") "
                                        :   " AND frontend_access_id=0 ")
                                    :   '')
                                .' ORDER BY n.date DESC';
        $result = $db->SelectLimit($query, 1);
        if (
            $result === false ||
            $result->EOF
        ) {
            throw new NewsLibraryException('No latest news available');
        }

        return $result->fields['id'];
    }
}
