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
 * Digital Asset Management
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 */
namespace Cx\Modules\Downloads\Controller;
/**
 * Digital Asset Management Category
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 */
class Group
{
    /**
     * ID of loaded group
     *
     * @var integer
     * @access private
     */
    protected $id;

    /**
     * Active status of group
     *
     * @var boolean
     * @access private
     */
    private $is_active;

    private $names;

    private $info_page;

    private $categories;
    private $categories_count;

    private $type;
    private $source;

    private $arrTypes = array('file', 'url');
    private $defaultType = 'file';

    private $arrAttributes = array(
        'core' => array(
            'id'                                => 'int',
            'is_active'                         => 'int',
            'info_page'                         => 'string',
            'type'                              => 'string'
           ),
        'locale' => array(
            'name'                              => 'string'
         ),
        'category' => array(
            'category_id'                       => 'int'
        )
    );

    private $placeholder = 'DOWNLOADS_GROUP_%s';

    private $isFrontendMode;

    /**
     * Contains the number of currently loaded groups
     *
     * @var integer
     * @access private
     */
    private $filtered_search_count = 0;

    /**
     * @access public
     */
    public $EOF;

    /**
     * Array which holds all loaded groups for later usage
     *
     * @var array
     * @access protected
     */
    protected $arrLoadedGroups = array();

    /**
     * Contains the message if an error occurs
     * @var string
     */
    public $error_msg = array();

    public function __construct()
    {
        global $objInit;

        $this->isFrontendMode = $objInit->mode == 'frontend';
        $this->clean();
    }

    /**
     * Clean group metadata
     *
     * Reset all group metadata for a new group.
     */
    private function clean()
    {
        $this->id = 0;
        $this->is_active = 1;
        $this->type = $this->defaultType;
        $this->info_page = '';
        $this->names = array();
        $this->categories = null;
        $this->categories_count = null;
        $this->EOF = true;
    }

    /**
     * Delete the current loaded group
     *
     * @return boolean
     */
    public function delete()
    {
        global $objDatabase, $_ARRAYLANG;

        \Permission::checkAccess(142, 'static');

        if ($objDatabase->Execute(
            'DELETE tblG, tblL, tblR
            FROM `'.DBPREFIX.'module_downloads_group` AS tblG
            LEFT JOIN `'.DBPREFIX.'module_downloads_group_locale` AS tblL ON tblL.`group_id` = tblG.`id`
            LEFT JOIN `'.DBPREFIX.'module_downloads_rel_group_category` AS tblR ON tblR.`group_id` = tblG.`id`
            WHERE tblG.`id` = '.$this->id) !== false
        ) {
            //clear Esi Cache
            DownloadsLibrary::clearEsiCache();
            return true;
        } else {
            $this->error_msg[] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_GROUP_DELETE_FAILED'], '<strong>'.htmlentities($this->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</strong>');
        }

        return false;
    }

    /**
     * Load first group
     *
     */
    public function first()
    {
        if (reset($this->arrLoadedGroups) === false || !$this->load(key($this->arrLoadedGroups))) {
            $this->EOF = true;
        } else {
            $this->EOF = false;
        }
    }

    public function getActiveStatus()
    {
        return $this->is_active;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getInfoPage()
    {
        return $this->info_page;
    }

    public function getAssociatedCategoriesCount()
    {
        if (!isset($this->categories_count)) {
            $this->loadAssociatedCategoriesCount();
        }
        return $this->downloads_count;
    }

    public function getPlaceholder()
    {
        return sprintf($this->placeholder, $this->id);
    }

    private function loadAssociatedCategoriesCount()
    {
        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        $arrGroupIds = array_keys($this->arrLoadedGroups);
        $objResult = $objDatabase->Execute('
            SELECT  tblR.`group_id`,
                    COUNT(1) AS `count`
            FROM    `'.DBPREFIX.'module_downloads_rel_group_category` AS tblR
            '.($this->isFrontendMode ? 'INNER JOIN `'.DBPREFIX.'module_downloads_category` AS tblC ON tblC.`id` = tblR.`category_id`' : '').'
            WHERE   tblR.`group_id` IN ('.implode(',', $arrGroupIds).')
                    '.($this->isFrontendMode ? 'AND tblC.`is_active` = 1' : '').'
                    '.($this->isFrontendMode ?
                            'AND (tblC.`visibility` = 1'.(
                                $objFWUser->objUser->login() ?
                                    ' OR tblC.`owner_id` = '.$objFWUser->objUser->getId()
                                    .(count($objFWUser->objUser->getDynamicPermissionIds()) ? ' OR tblC.`read_access_id` IN ('.implode(', ', $objFWUser->objUser->getDynamicPermissionIds()).')' : '')
                                :   '').')'
                            :   '').'
            GROUP BY tblR.`group_id`');

        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrLoadedGroups[$objResult->fields['group_id']]['categories_count'] = $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }

        $length = count($arrGroupIds);
        for ($i = 0; $i < $length; $i++) {
            if (!isset($this->arrLoadedGroups[$arrGroupIds[$i]]['categories_count'])) {
                $this->arrLoadedGroups[$arrGroupIds[$i]]['categories_count'] = 0;
            }
        }

        $this->categories_count = $this->arrLoadedGroups[$this->id]['categories_count'];
    }

    public function getAssociatedCategoryIds()
    {
        if (!isset($this->categories)) {
            $this->loadCategoryAssociations();
        }
        return $this->categories;
    }

    private function loadCategoryAssociations()
    {
        global $objDatabase;

        $this->categories= array();
        $objResult = $objDatabase->Execute('SELECT `category_id` FROM `'.DBPREFIX.'module_downloads_rel_group_category` WHERE `group_id` = '.$this->id);
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->categories[] = $objResult->fields['category_id'];
                $objResult->MoveNext();
            }
        }
    }

    public function getName($langId = 0)
    {
        if (!isset($this->names)) {
            $this->loadLocales();
        }
        if (!$langId) {
            $langId = DownloadsLibrary::getOutputLocale()->getId();
        }
        return isset($this->names[$langId]) ? $this->names[$langId] : '';
    }

    public function loadLocales()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute('
            SELECT
                `group_id`,
                `lang_id`,
                `name`
            FROM `'.DBPREFIX.'module_downloads_group_locale`
            WHERE `group_id` IN ('.implode(',', array_keys($this->arrLoadedGroups)).')');
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrLoadedGroups[$objResult->fields['group_id']]['names'][$objResult->fields['lang_id']] = $objResult->fields['name'];
                $objResult->MoveNext();
            }

            $this->names = isset($this->arrLoadedGroups[$this->id]['names']) ? $this->arrLoadedGroups[$this->id]['names'] : null;
        }
    }

    public function getFilteredSearchGroupCount()
    {
        return $this->filtered_search_count;
    }

    public static function getGroup($id)
    {
        $objGroup = new Group();

        $objGroup->load($id);
        return $objGroup;
    }

    public static function getGroups($filter = null, $search = null, $arrSort = null, $arrAttributes = null, $limit = null, $offset = null)
    {
        $objGroup = new Group();

        $objGroup->loadGroups($filter, $search, $arrSort, $arrAttributes, $limit, $offset);
        return $objGroup;
    }

    /**
     * Load group data
     *
     * Get meta data of group from database
     * and put them into the analogous class variables.
     *
     * @param integer $id
     * @return unknown
     */
    private function load($id)
    {
        $arrDebugBackTrace = debug_backtrace();
        if (!in_array($arrDebugBackTrace[1]['function'], array('getGroup', 'first','next'))) {
            die("Group->load(): Illegal method call in {$arrDebugBackTrace[0]['file']} on line {$arrDebugBackTrace[0]['line']}!");
        }

        if ($id) {
            if (!isset($this->arrLoadedGroups[$id])) {
                return $this->loadGroups($id);
            } else {
                $this->id = $id;
                $this->is_active = isset($this->arrLoadedGroups[$id]['is_active']) ? $this->arrLoadedGroups[$id]['is_active'] : 1;
                $this->type = isset($this->arrLoadedGroups[$id]['type']) ? $this->arrLoadedGroups[$id]['type'] : $this->defaultType;
                $this->info_page = isset($this->arrLoadedGroups[$id]['info_page']) ? $this->arrLoadedGroups[$id]['info_page'] : '';
                $this->names = isset($this->arrLoadedGroups[$id]['names']) ? $this->arrLoadedGroups[$id]['names'] : null;
                $this->categories = isset($this->arrLoadedGroups[$id]['categories']) ? $this->arrLoadedGroups[$id]['categories'] : null;
                $this->categories_count = isset($this->arrLoadedGroups[$id]['categories_count']) ? $this->arrLoadedGroups[$id]['categories_count'] : null;
                $this->EOF = false;
                return true;
            }
        } else {
            $this->clean();
        }
    }

    private function loadGroups($filter = null, $search = null, $arrSort = null, $arrAttributes = null, $limit = null, $offset = null)
    {
        global $objDatabase;

        $arrDebugBackTrace = debug_backtrace();
        if (!in_array($arrDebugBackTrace[1]['function'], array('load', 'getGroups'))) {
            die("Category->loadGroups(): Illegal method call in {$arrDebugBackTrace[0]['file']} on line {$arrDebugBackTrace[0]['line']}!");
        }

        $this->arrLoadedGroups = array();
        $arrSelectCoreExpressions = array();
        //$arrSelectLocaleExpressions = array();
        $this->filtered_search_count = 0;
        $sqlCondition = array();

        // set filter
        if (isset($filter) && is_array($filter) && count($filter) || !empty($search)) {
            $sqlCondition = $this->getFilteredGroupIdList($filter, $search);
        } elseif (!empty($filter)) {
            $sqlCondition['tables'] = array('core');
            $sqlCondition['conditions'] = array('tblG.`id` = '.intval($filter));
            $limit = 1;
        }

        // set sort order
        if (!($arrQuery = $this->setSortedGroupIdList($arrSort, $sqlCondition, $limit, $offset))) {
            $this->clean();
            return false;
        }

        // set field list
        if (is_array($arrAttributes)) {
            foreach ($arrAttributes as $attribute) {
                if (isset($this->arrAttributes['core'][$attribute]) && !in_array($attribute, $arrSelectCoreExpressions)) {
                    $arrSelectCoreExpressions[] = $attribute;
                }/* elseif (isset($this->arrAttributes['locale'][$attribute]) && !in_array($attribute, $arrSelectLocaleExpressions)) {
                    $arrSelectLocaleExpressions[] = $attribute;
                }*/
            }

            if (!in_array('id', $arrSelectCoreExpressions)) {
                $arrSelectCoreExpressions[] = 'id';
            }
        } else {
            $arrSelectCoreExpressions = array_keys($this->arrAttributes['core']);
            //$arrSelectLocaleExpressions = array_keys($this->arrAttributes['locale']);
        }

        $query = 'SELECT tblG.`'.implode('`, tblG.`', $arrSelectCoreExpressions).'`'
            .'FROM `'.DBPREFIX.'module_downloads_group` AS tblG'
            .($arrQuery['tables']['locale'] ? ' INNER JOIN `'.DBPREFIX.'module_downloads_group_locale` AS tblL ON tblL.`group_id` = tblG.`id`' : '')
            .($arrQuery['tables']['category'] ? ' INNER JOIN `'.DBPREFIX.'module_downloads_rel_group_category` AS tblR ON tblR.`group_id` = tblG.`id`' : '')
            .(count($arrQuery['conditions']) ? ' WHERE '.implode(' AND ', $arrQuery['conditions']) : '')
            .' GROUP BY tblG.`id`'
            .(count($arrQuery['sort']) ? ' ORDER BY '.implode(', ', $arrQuery['sort']) : '');

        if (empty($limit)) {
            $objGroup = $objDatabase->Execute($query);
        } else {
            $objGroup = $objDatabase->SelectLimit($query, $limit, $offset);
        };

        if ($objGroup !== false && $objGroup->RecordCount() > 0) {
            while (!$objGroup->EOF) {
                foreach ($objGroup->fields as $attributeId => $value) {
                    $this->arrLoadedGroups[$objGroup->fields['id']][$attributeId] = $value;
                }
                $objGroup->MoveNext();
            }

            $this->first();
            return true;
        } else {
            $this->clean();
            return false;
        }
    }

    private function getFilteredGroupIdList($arrFilter = null, $search = null)
    {
        $arrGroupIds = array();
        $arrConditions = array();
        $arrSearchConditions = array();
        $tblLocales = false;
        $tblCategory = false;

        // parse filter
        if (isset($arrFilter) && is_array($arrFilter)) {
            $arrFilterConditions = $this->parseFilterConditions($arrFilter);
            if (
                count($arrFilterConditions) &&
                !empty($arrFilterConditions['conditions']) &&
                !empty($arrFilterConditions['tables'])
            ) {
                $arrConditions[] = implode(' AND ', $arrFilterConditions['conditions']);
                $tblLocales = isset($arrFilterConditions['tables']['locale']);
                $tblCategory = isset($arrFilterConditions['tables']['category']);
            }
        }

        // parse search
        if (!empty($search)) {
            if (count($arrSearchConditions = $this->parseSearchConditions($search))) {
                $arrSearchConditions[] = implode(' OR ', $arrSearchConditions);
                $arrConditions[] = implode(' OR ', $arrSearchConditions);
                $tblLocales = true;
            }
        }

        $arrTables = array();
        if ($tblLocales) {
            $arrTables[] = 'locale';
        }
        if ($tblCategory) {
            $arrTables[] = 'category';
        }

        return array(
            'tables'        => $arrTables,
            'conditions'    => $arrConditions
        );
    }

    public function __clone()
    {
        $this->clean();
    }

    /**
     * Parse filter conditions
     *
     * Generate conditions of the attributes for the SQL WHERE statement.
     * The filter conditions are defined through the two dimensional array $arrFilter.
     * Each key-value pair represents an attribute and its associated condition to which it must fit to.
     * The condition could either be a integer or string depending on the attributes type, or it could be
     * a collection of integers or strings represented in an array.
     *
     * Examples of the filer array:
     *
     * array(
     *      'name' => '%software%',
     * )
     * // will return all categories who's name includes 'software'
     *
     *
     * array(
     *      'name' => array(
     *          'd%',
     *          'e%',
     *          'f%',
     *          'g%'
     *      )
     * )
     * // will return all categories which have a name of which its first letter is and between 'd' to 'g' (case less)
     *
     *
     * array(
     *      'name' => array(
     *          array(
     *              '>' => 'd',
     *              '<' => 'g'
     *          ),
     *          'LIKE'  => 'g%'
     *      )
     * )
     * // same as the preview example but in an other way
     *
     *
     * array(
     *      'is_active' => 1,
     *      'is_visibility' => 1
     * )
     * // will return all categories that are active and visible
     *
     *
     *
     * @param array $arrFilter
     * @return array
     */
    private function parseFilterConditions($arrFilter)
    {
        $arrConditions = array(
            'conditions' => array(),
            'tables'     => array(),
        );
        foreach ($arrFilter as $attribute => $condition) {
            /**
             * $attribute is the attribute like 'is_active' or 'name'
             * $condition is either a simple condition (integer or string) or an condition matrix (array)
             */
            foreach ($this->arrAttributes as $type => $arrAttributes) {
                $table = $type == 'core' ? 'tblG' : ($type == 'category' ? 'tblR' : 'tblL');

                if (isset($arrAttributes[$attribute])) {
                    $arrComparisonOperators = array(
                        'int'       => array('=','<','>'),
                        'string'    => array('!=','<','>', 'REGEXP')
                    );
                    $arrDefaultComparisonOperator = array(
                        'int'       => '=',
                        'string'    => 'LIKE'
                    );
                    $arrEscapeFunction = array(
                        'int'       => 'intval',
                        'string'    => 'addslashes'
                    );

                    if (is_array($condition)) {
                        $arrRestrictions = array();
                        foreach ($condition as $operator => $restriction) {
                            /**
                             * $operator is either a comparison operator ( =, LIKE, <, >) if $restriction is an array or if $restriction is just an integer or a string then its an index which would be useless
                             * $restriction is either a integer or a string or an array which represents a restriction matrix
                             */
                            if (is_array($restriction)) {
                                $arrConditionRestriction = array();
                                foreach ($restriction as $restrictionOperator => $restrictionValue) {
                                    /**
                                     * $restrictionOperator is a comparison operator ( =, <, >)
                                     * $restrictionValue represents the condition
                                     */
                                    $arrConditionRestriction[] = $table.".`{$attribute}` ".(
                                        in_array($restrictionOperator, $arrComparisonOperators[$arrAttributes[$attribute]], true) ?
                                            $restrictionOperator
                                        :   $arrDefaultComparisonOperator[$arrAttributes[$attribute]]
                                    )." '".$arrEscapeFunction[$arrAttributes[$attribute]]($restrictionValue)."'";
                                }
                                $arrRestrictions[] = implode(' AND ', $arrConditionRestriction);
                            } else {
                                $arrRestrictions[] = $table.".`{$attribute}` ".(
                                    in_array($operator, $arrComparisonOperators[$arrAttributes[$attribute]], true) ?
                                        $operator
                                    :   $arrDefaultComparisonOperator[$arrAttributes[$attribute]]
                                )." '".$arrEscapeFunction[$arrAttributes[$attribute]]($restriction)."'";
                            }
                        }
                        $arrConditions['conditions'][] = '(('.implode(') OR (', $arrRestrictions).'))';
                        $arrConditions['tables'][$type] = true;
                    } else {
                        $arrConditions['conditions'][] = "(".$table.".`".$attribute."` ".$arrDefaultComparisonOperator[$arrAttributes[$attribute]]." '".$arrEscapeFunction[$arrAttributes[$attribute]]($condition)."')";
                        $arrConditions['tables'][$type] = true;
                    }
                }
            }
        }

        return $arrConditions;
    }

    private function parseSearchConditions($search)
    {
        $arrConditions = array();
        $arrAttribute = array('name');
        foreach ($arrAttribute as $attribute) {
            $arrConditions[] = "(tblL.`".$attribute."` LIKE '%".(is_array($search) ? implode("%' OR tblL.`".$attribute."` LIKE '%", array_map('addslashes', $search)) : addslashes($search))."%')";
        }

        return $arrConditions;
    }

    private function setSortedGroupIdList($arrSort, $sqlCondition = null, $limit = null, $offset = null)
    {
        global $objDatabase;

        $arrCustomSelection = array();
        $joinLocaleTbl = false;
        $joinCategoryTbl = false;
        $arrCategoryIds = array();
        $arrSortExpressions = array();
        $arrGroupIds = array();
        $nr = 0;

        if (!empty($sqlCondition)) {
            if (isset($sqlCondition['tables'])) {
                if (in_array('locale', $sqlCondition['tables'])) {
                    $joinLocaleTbl = true;
                }
                if (in_array('category', $sqlCondition['tables'])) {
                    $joinCategoryTbl = true;
                }
            }

            if (isset($sqlCondition['conditions']) && count($sqlCondition['conditions'])) {
                $arrCustomSelection = $sqlCondition['conditions'];
            }
        }

        if (is_array($arrSort)) {
            foreach ($arrSort as $attribute => $direction) {
                if (in_array(strtolower($direction), array('asc', 'desc'))) {
                    if (isset($this->arrAttributes['core'][$attribute])) {
                        $arrSortExpressions[] = 'tblG.`'.$attribute.'` '.$direction;
                    } elseif (isset($this->arrAttributes['locale'][$attribute])) {
                        $arrSortExpressions[] = 'tblL.`'.$attribute.'` '.$direction;
                        $joinLocaleTbl = true;
                    }
                } elseif ($attribute == 'special') {
                    $arrSortExpressions[] = $direction;
                }
            }
        }

        $query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT tblG.`id`
            FROM `'.DBPREFIX.'module_downloads_group` AS tblG'
            .($joinLocaleTbl ? ' INNER JOIN `'.DBPREFIX.'module_downloads_group_locale` AS tblL ON tblL.`group_id` = tblG.`id`' : '')
            .($joinCategoryTbl ? ' INNER JOIN `'.DBPREFIX.'module_downloads_rel_group_category` AS tblR ON tblR.`group_id` = tblG.`id`' : '')
            .(count($arrCustomSelection) ? ' WHERE '.implode(' AND ', $arrCustomSelection) : '')
            .(count($arrSortExpressions) ? ' ORDER BY '.implode(', ', $arrSortExpressions) : '');

        if (empty($limit)) {
            $objGroupId = $objDatabase->Execute($query);
            $this->filtered_search_count = $objGroupId->RecordCount();
        } else {
            $objGroupId = $objDatabase->SelectLimit($query, $limit, intval($offset));
            $objGroupCount = $objDatabase->Execute('SELECT FOUND_ROWS()');
            $this->filtered_search_count = $objGroupCount->fields['FOUND_ROWS()'];
        }

        if ($objGroupId !== false) {
            while (!$objGroupId->EOF) {
                $arrGroupIds[$objGroupId->fields['id']] = array();
                $objGroupId->MoveNext();
            }
        }

        $this->arrLoadedGroups = $arrGroupIds;

        if (!count($arrGroupIds)) {
            return false;
        }

        return array(
            'tables' => array(
                'locale'    => $joinLocaleTbl,
                'category'  => $joinCategoryTbl
            ),
            'conditions'    => $arrCustomSelection,
            'sort'          => $arrSortExpressions
        );

        return $arrGroupIds;
    }

    public function reset()
    {
        $this->clean();
    }


    /**
     * Load next group
     *
     */
    public function next()
    {
        if (next($this->arrLoadedGroups) === false || !$this->load(key($this->arrLoadedGroups))) {
            $this->EOF = true;
        }
    }

    /**
     * Store group
     *
     * This stores the metadata of the group to the database.
     *
     * @global ADONewConnection
     * @global array
     * @return boolean
     */
    public function store()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($this->names) && !$this->validateName()) {
            return false;
        }

        \Permission::checkAccess(142, 'static');

        if ($this->id) {
            if ($objDatabase->Execute("
                UPDATE `".DBPREFIX."module_downloads_group`
                SET
                    `is_active` = ".intval($this->is_active).",
                    `type` = '".$this->type."',
                    `info_page` = '".addslashes($this->info_page)."'
                WHERE `id` = ".$this->id
            ) === false) {
                $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_FAILED_UPDATE_GROUP'];
                return false;
            }
        } else {
            if ($objDatabase->Execute("
                INSERT INTO `".DBPREFIX."module_downloads_group` (
                    `is_active`,
                    `type`,
                    `info_page`
                ) VALUES (
                    ".intval($this->is_active).",
                    '".$this->type."',
                    '".addslashes($this->info_page)."'
                )") !== false) {
                $this->id = $objDatabase->Insert_ID();
            } else {
                $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_FAILED_ADD_GROUP'];
                return false;
            }
        }

        if (isset($this->names) && !$this->storeLocales()) {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_COULD_NOT_STORE_LOCALES'];
            return false;
        }

        if (!$this->storeCategoryAssociations()) {
            return false;
        }

        //clear Esi Cache
        DownloadsLibrary::clearEsiCache();
        return true;
    }

    /**
     * Store locales
     *
     * @global ADONewConnection
     * @return boolean TRUE on success, otherwise FALSE
     */
    private function storeLocales()
    {
        global $objDatabase;

        $arrOldLocales = array();
        $status = true;

        $objOldLocales = $objDatabase->Execute('SELECT `lang_id`, `name` FROM `'.DBPREFIX.'module_downloads_group_locale` WHERE `group_id` = '.$this->id);
        if ($objOldLocales !== false) {
            while (!$objOldLocales->EOF) {
                $arrOldLocales[$objOldLocales->fields['lang_id']] = array(
                    'name'          => $objOldLocales->fields['name']
                );
                $objOldLocales->MoveNext();
            }
        } else {
            return false;
        }

        $arrNewLocales = array_diff(array_keys($this->names), array_keys($arrOldLocales));
        $arrRemovedLocales = array_diff(array_keys($arrOldLocales), array_keys($this->names));
        $arrUpdatedLocales = array_intersect(array_keys($this->names), array_keys($arrOldLocales));

        foreach ($arrNewLocales as $langId) {
            if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_downloads_group_locale` (`lang_id`, `group_id`, `name`) VALUES (".$langId.", ".$this->id.", '".addslashes($this->names[$langId])."')") === false) {
                $status = false;
            }
        }

        foreach ($arrRemovedLocales as $langId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_downloads_group_locale` WHERE `group_id` = ".$this->id." AND `lang_id` = ".$langId) === false) {
                $status = false;
            }
        }

        foreach ($arrUpdatedLocales as $langId) {
            if ($this->names[$langId] != $arrOldLocales[$langId]['name']) {
                if ($objDatabase->Execute("UPDATE `".DBPREFIX."module_downloads_group_locale` SET `name` = '".addslashes($this->names[$langId])."' WHERE `group_id` = ".$this->id." AND `lang_id` = ".$langId) === false) {
                    $status = false;
                }
            }
        }
        return $status;
    }

    public function storeCategoryAssociations()
    {
        global $objDatabase;

        $arrOldCategories = array();
        $status = true;

        if (!isset($this->categories)) {
            $this->loadCategoryAssociations();
        }
        $arrCategories = $this->categories;

        $objOldCategories = $objDatabase->Execute('SELECT `category_id` FROM `'.DBPREFIX.'module_downloads_rel_group_category` WHERE `group_id` = '.$this->id);
        if ($objOldCategories !== false) {
            while (!$objOldCategories->EOF) {
                $arrOldCategories[] = $objOldCategories->fields['category_id'];
                $objOldCategories->MoveNext();
            }
        } else {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_COULD_NOT_STORE_CATEGORY_ASSOCIATIONS'];
            return false;
        }

        $arrNewCategories = array_diff($arrCategories, $arrOldCategories);
        $arrRemovedCategories = array_diff($arrOldCategories, $arrCategories);

        foreach ($arrNewCategories as $categoryId) {
            if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_downloads_rel_group_category` (`group_id`, `category_id`) VALUES (".$this->id.", ".$categoryId.")") === false) {
                $status = false;
            }
        }

        foreach ($arrRemovedCategories as $categoryId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_downloads_rel_group_category` WHERE `group_id` = ".$this->id." AND `category_id` = ".$categoryId) === false) {
                $status = false;
            }
        }
        if (!$status) {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_COULD_NOT_STORE_CATEGORY_ASSOCIATIONS'];
        }

        return $status;
    }

    private function validateName()
    {
        global $_ARRAYLANG;

        $arrLanguages = \FWLanguage::getLanguageArray();
        $namesSet = true;
        foreach ($arrLanguages as $langId => $arrLanguage) {
            if (empty($this->names[$langId])) {
                $namesSet = false;
                break;
            }
        }

        if ($namesSet) {
            return true;
        } else {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_EMPTY_NAME_ERROR'];
            return false;
        }
    }

    public function setActiveStatus($active)
    {
        $this->is_active = $active;
    }

    public function setNames($arrNames)
    {
        $this->names = $arrNames;
    }

    public function setCategories($arrCategories)
    {
        $this->categories = count($arrCategories) ? $arrCategories : array();
    }

    public function setInfoPage($infoPage)
    {
        $this->info_page = $infoPage;
    }

    public function setType($type)
    {
        $this->type = in_array($type, $this->arrTypes) ? $type : $this->defaultType;
    }

    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    /**
     * Get Groups placeholders
     *
     * @return array
     */
    public function getGroupsPlaceholders()
    {
        return preg_filter(
            '/^/',
            'DOWNLOADS_GROUP_',
            array_keys($this->arrLoadedGroups)
        );
    }
}
