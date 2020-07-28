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
class Category
{
    /**
     * ID of loaded category
     *
     * @var integer
     * @access private
     */
    protected $id;

    /**
     * Active status of category
     *
     * @var boolean
     * @access private
     */
    private $is_active;

    private $parent_id;
    private $visibility;
    private $owner_id;
    private $image;
    private $order;
    private $deletable_by_owner;
    private $modify_access_by_owner;

    private $read_access_id;
    private $read_protected;
    private $read_groups;

    private $add_subcategories_access_id;
    private $add_subcategories_protected;
    private $add_subcategories_groups;

    private $manage_subcategories_access_id;
    private $manage_subcategories_protected;
    private $manage_subcategories_groups;

    private $add_files_access_id;
    private $add_files_protected;
    private $add_files_groups;

    private $manage_files_access_id;
    private $manage_files_protected;
    private $manage_files_groups;

    private $arrPermissionDependencies = array(
        'read' => array(
            'add_subcategories' => array(
                'manage_subcategories' => null
            ),
            'add_files' => array(
                'manage_files' => null
            )
        )
    );

    protected $arrPermissionTypes = array(
        'read',
        'add_subcategories',
        'manage_subcategories',
        'add_files',
        'manage_files'
    );

    protected $set_permissions_recursive;
    private $permission_set;
    private $names;
    private $descriptions;

    private $downloads;
    private $downloads_count;

    private $arrAttributes = array(
        'core' => array(
            'id'                                => 'int',
            'is_active'                         => 'int',
            'parent_id'                         => 'int',
            'visibility'                        => 'int',
            'owner_id'                          => 'int',
            'image'                             => 'string',
            'order'                             => 'int',
            'deletable_by_owner'                => 'int',
            'modify_access_by_owner'            => 'int',
            'read_access_id'                    => 'int',
            'add_subcategories_access_id'       => 'int',
            'manage_subcategories_access_id'    => 'int',
            'add_files_access_id'               => 'int',
            'manage_files_access_id'            => 'int'
           ),
        'locale' => array(
            'name'                              => 'string',
            'description'                       => 'string'
         )
    );

    private $isFrontendMode;

    /**
     * Contains the number of currently loaded categories
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
     * Array which holds all loaded categories for later usage
     *
     * @var array
     * @access protected
     */
    protected $arrLoadedCategories = array();

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
     * Clean category metadata
     *
     * Reset all category metadata for a new category.
     */
    private function clean()
    {
        $objFWUser = \FWUser::getFWUserObject();

        $this->id = 0;
        $this->is_active = 1;
        $this->parent_id = 0;
        $this->visibility = 1;
        $this->owner_id = $objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0;
        $this->image = '';
        $this->order = 0;
        $this->deletable_by_owner = 1;
        $this->modify_access_by_owner = 1;
        $this->read_access_id = 0;
        $this->read_protected = false;
        $this->read_groups = null;
        $this->add_subcategories_access_id = 0;
        $this->add_subcategories_protected = true;
        $this->add_subcategories_groups = null;
        $this->manage_subcategories_access_id = 0;
        $this->manage_subcategories_protected = true;
        $this->manage_subcategories_groups = null;
        $this->add_files_access_id = 0;
        $this->add_files_protected = true;
        $this->add_files_groups = null;
        $this->manage_files_access_id = 0;
        $this->manage_files_protected = true;
        $this->manage_files_groups = null;
        $this->set_permissions_recursive = false;
        $this->names = array();
        $this->descriptions = array();
        $this->downloads = null;
        $this->downloads_count = null;
        $this->permission_set = false;
        $this->EOF = true;
    }

    /**
     * Delete the current loaded category
     *
     * @return boolean
     */
    public function delete($recursive = false)
    {
        global $objDatabase, $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();

        if (// the category is a main category => only managers are allowed to delete the category
            !$this->parent_id && !\Permission::checkAccess(143, 'static', true)
            // the category isn't a main category and...
            || $this->parent_id && (
                !\Permission::checkAccess(143, 'static', true)
                // ...the owner has the permission to delete it by himself
                && (!$this->getDeletableByOwner() || !$objFWUser->objUser->login() || $this->owner_id != $objFWUser->objUser->getId())
                // ...or the user has the right the delete subcategories of the current parent category
                && (
                    !($objParentCategory = Category::getCategory($this->parent_id))
                    || (!\Permission::checkAccess($objParentCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
                        && !$objFWUser->objUser->login()|| $objParentCategory->getOwnerId() != $objFWUser->objUser->getId()
                    )
                )
            )
        ) {
            $this->error_msg[] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_NO_PERM_DEL_CATEGORY'], htmlentities($this->getName(), ENT_QUOTES, CONTREXX_CHARSET));
            return false;
        }

        if ($this->hasSubcategories()) {
            $objSubcategory = Category::getCategories(array('parent_id' => $this->getId()));
            while (!$objSubcategory->EOF) {
                if ($recursive) {
                    if (!$objSubcategory->delete(true)) {
                        return false;
                    }
                } else {
                    $objSubcategory->setParentId($this->parent_id);
                    if (!$objSubcategory->store()) {
                        return false;
                    }
                }

                $objSubcategory->next();
            }
        }

        foreach ($this->arrPermissionTypes as $type) {
            \Permission::removeAccess($this->{$type.'_access_id'}, 'dynamic');
        }

        if ($objDatabase->Execute(
            'DELETE tblC, tblL, tblR
            FROM `'.DBPREFIX.'module_downloads_category` AS tblC
            LEFT JOIN `'.DBPREFIX.'module_downloads_category_locale` AS tblL ON tblL.`category_id` = tblC.`id`
            LEFT JOIN `'.DBPREFIX.'module_downloads_rel_download_category` AS tblR ON tblR.`category_id` = tblC.`id`
            WHERE tblC.`id` = '.$this->id) !== false
        ) {
            //clear Esi Cache
            DownloadsLibrary::clearEsiCache();
            return true;
        } else {
            $this->error_msg[] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_DELETE_FAILED'], '<strong>'.htmlentities($this->name, ENT_QUOTES, CONTREXX_CHARSET).'</strong>');
        }

        return false;
    }

    /**
     * Load first category
     *
     */
    public function first()
    {
        if (reset($this->arrLoadedCategories) === false || !$this->load(key($this->arrLoadedCategories))) {
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

    public function getParentId()
    {
        return $this->parent_id;
    }

    public function getVisibility()
    {
        return $this->visibility;
    }

    public function getOwnerId()
    {
        return $this->owner_id;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getDeletableByOwner()
    {
        return $this->deletable_by_owner;
    }

    public function getModifyAccessByOwner()
    {
        return $this->modify_access_by_owner;
    }

    public function hasToSetPermissionsRecursive()
    {
        return $this->set_permissions_recursive;
    }

    /**
     * Get associated downloads count
     *
     * @param boolean $listDownloadsOfCurrentLanguage If true get Downloads count of current language
     *                                                otherwise get Downloads count of all language
     */
    public function getAssociatedDownloadsCount(
        $listDownloadsOfCurrentLanguage = false
    ) {
        if (!isset($this->downloads_count)) {
            $this->loadAssociatedDownloadsCount($listDownloadsOfCurrentLanguage);
        }
        return $this->downloads_count;
    }

    /**
     * Load associated downloads and get its count
     *
     * @param boolean $listDownloadsOfCurrentLanguage If true get Downloads count of current language
     *                                                otherwise get Downloads count of all language
     */
    private function loadAssociatedDownloadsCount($listDownloadsOfCurrentLanguage)
    {
        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        $arrCategoryIds = array_keys($this->arrLoadedCategories);
        $localeJoinQry = '';
        $localeCondition = '';
        if ($listDownloadsOfCurrentLanguage) {
            $localeJoinQry =
                ' INNER JOIN `' . DBPREFIX . 'module_downloads_download_locale` as tblL
                ON tblL.`download_id` = tblD.`id` ';
            $localeCondition = ' AND tblL.`lang_id` = ' . DownloadsLibrary::getOutputLocale()->getId() . ' ';
        }
        $objResult = $objDatabase->Execute('
            SELECT  tblR.`category_id`,
                    COUNT(1) AS `count`
            FROM    `'.DBPREFIX.'module_downloads_rel_download_category` AS tblR
            '.($this->isFrontendMode || !\Permission::checkAccess(143, 'static', true) ? 'INNER JOIN `'.DBPREFIX.'module_downloads_download` AS tblD ON tblD.`id` = tblR.`download_id`' : '').'
            ' . $localeJoinQry . '
            WHERE   tblR.`category_id` IN ('.implode(',', $arrCategoryIds).')
                    '.($this->isFrontendMode ? 'AND tblD.`is_active` = 1' : '').'
                    ' . $localeCondition . '
                    '.($this->isFrontendMode ? 'AND (tblD.`expiration` = 0 || tblD.`expiration` > '.time().')' : '').'
                    '.($this->isFrontendMode || !\Permission::checkAccess(143, 'static', true) ?
                            'AND (tblD.`visibility` = 1'.(
                                $objFWUser->objUser->login() ?
                                    ' OR tblD.`owner_id` = '.$objFWUser->objUser->getId()
                                    .(count($objFWUser->objUser->getDynamicPermissionIds()) ? ' OR tblD.`access_id` IN ('.implode(', ', $objFWUser->objUser->getDynamicPermissionIds()).')' : '')
                                :   '').')'
                            :   '').'
            GROUP BY tblR.`category_id`');

        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrLoadedCategories[$objResult->fields['category_id']]['downloads_count'] = intval($objResult->fields['count']);
                $objResult->MoveNext();
            }
        }

        $length = count($arrCategoryIds);
        for ($i = 0; $i < $length; $i++) {
            if (!isset($this->arrLoadedCategories[$arrCategoryIds[$i]]['downloads_count'])) {
                $this->arrLoadedCategories[$arrCategoryIds[$i]]['downloads_count'] = 0;
            }
        }

        $this->downloads_count = $this->arrLoadedCategories[$this->id]['downloads_count'];
    }

    public function getAssociatedDownloadIds()
    {
        if (!isset($this->downloads)) {
            $this->loadDownloadAssociations();
        }
        return $this->downloads;
    }

    private function loadDownloadAssociations()
    {
        global $objDatabase;

        $this->downloads = array();
        $arrSubCategories = array($this->id);
// TODO: Only load downloads of subcategories if we're not modifying a category
        //$arrSubCategories = array_merge($arrSubCategories, $this->getSubCategories());
        foreach ($arrSubCategories as &$value) {
            $value = '`category_id` = '.$value;
        }
        $objResult = $objDatabase->Execute('SELECT `download_id`, `order` FROM `'.DBPREFIX.'module_downloads_rel_download_category` WHERE ('.implode(' OR ', $arrSubCategories).')');
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->downloads[$objResult->fields['download_id']] = intval($objResult->fields['order']);
                $objResult->MoveNext();
            }
        }
    }

    public function hasSubcategories($parentId = 0)
    {
        global $objDatabase;

        $parentId = empty($parentId) ? $this->id : $parentId;

        $objResult = $objDatabase->SelectLimit('SELECT 1 FROM `'.DBPREFIX.'module_downloads_category` WHERE `parent_id` = '.$parentId, 1);

        return intval(!$objResult || $objResult->RecordCount());
    }

    /**
     * Returns all children of the given category.
     *
     * @acces   private
     * @param   integer     $parentId
     */
    private function getSubCategories($parentId)
    {
        global $objDatabase;

        $parentId = empty($parentId) ? $this->id : $parentId;

        $arrSubCategories = array();
        if ($this->hasSubCategories($parentId)) {
            $objResult = $objDatabase->Execute('SELECT `id` FROM `'.DBPREFIX.'module_downloads_category` WHERE `parent_id` = '.intval($parentId));
            if ($objResult && ($objResult->RecordCount() > 0)) {
                while (!$objResult->EOF) {
                    $id = $objResult->fields['id'];
                    $arrSubCategories[] = $id;
                    if ($this->hasSubCategories($id)) {
                        $arrSubCategories = array_merge($arrSubCategories, $this->getSubCategories($id));
                    }
                    $objResult->MoveNext();
                }
            }
        }
        return $arrSubCategories;
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
                `category_id`,
                `lang_id`,
                `name`,
                `description`
            FROM `'.DBPREFIX.'module_downloads_category_locale`
            WHERE `category_id` IN ('.implode(',', array_keys($this->arrLoadedCategories)).')');
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrLoadedCategories[$objResult->fields['category_id']]['names'][$objResult->fields['lang_id']] = $objResult->fields['name'];
                $this->arrLoadedCategories[$objResult->fields['category_id']]['descriptions'][$objResult->fields['lang_id']] = $objResult->fields['description'];

                $objResult->MoveNext();
            }

            $this->names = isset($this->arrLoadedCategories[$this->id]['names']) ? $this->arrLoadedCategories[$this->id]['names'] : null;
            $this->descriptions = isset($this->arrLoadedCategories[$this->id]['descriptions']) ? $this->arrLoadedCategories[$this->id]['descriptions'] : null;
        }
    }

    public function getDescription($langId = 0)
    {
        if (!isset($this->descriptions)) {
            $this->loadLocales();
        }
        if (!$langId) {
            $langId = DownloadsLibrary::getOutputLocale()->getId();
        }
        return isset($this->descriptions[$langId]) ? $this->descriptions[$langId] : '';
    }

    public function getFilteredSearchCategoryCount()
    {
        return $this->filtered_search_count;
    }

    public static function getCategory($id)
    {
        $objCategory = new Category();

        $objCategory->load($id);
        return $objCategory;
    }

    public static function getCategories($filter = null, $search = null, $arrSort = null, $arrAttributes = null, $limit = null, $offset = null)
    {
        $objCategory = new Category();

        $objCategory->loadCategories($filter, $search, $arrSort, $arrAttributes, $limit, $offset);
        return $objCategory;
    }

    /**
     * Load category data
     *
     * Get meta data of category from database
     * and put them into the analogous class variables.
     *
     * @param integer $id
     * @return unknown
     */
    private function load($id)
    {
        $arrDebugBackTrace = debug_backtrace();
        if (!in_array($arrDebugBackTrace[1]['function'], array('getCategory', 'first','next'))) {
            die("Category->load(): Illegal method call in {$arrDebugBackTrace[0]['file']} on line {$arrDebugBackTrace[0]['line']}!");
        }

        if ($id) {
            if (!isset($this->arrLoadedCategories[$id])) {
                return $this->loadCategories($id);
            } else {
                $this->id = $id;
                $this->is_active = isset($this->arrLoadedCategories[$id]['is_active']) ? $this->arrLoadedCategories[$id]['is_active'] : 1;
                $this->parent_id = isset($this->arrLoadedCategories[$id]['parent_id']) ? $this->arrLoadedCategories[$id]['parent_id'] : 0;
                $this->visibility = isset($this->arrLoadedCategories[$id]['visibility']) ? $this->arrLoadedCategories[$id]['visibility'] : 1;
                $this->owner_id = isset($this->arrLoadedCategories[$id]['owner_id']) ? $this->arrLoadedCategories[$id]['owner_id'] : 0;
                $this->image = isset($this->arrLoadedCategories[$id]['image']) ? $this->arrLoadedCategories[$id]['image'] : '';
                $this->order = isset($this->arrLoadedCategories[$id]['order']) ? $this->arrLoadedCategories[$id]['order'] : 0;
                $this->deletable_by_owner = isset($this->arrLoadedCategories[$id]['deletable_by_owner']) ? $this->arrLoadedCategories[$id]['deletable_by_owner'] : 1;
                $this->modify_access_by_owner = isset($this->arrLoadedCategories[$id]['modify_access_by_owner']) ? $this->arrLoadedCategories[$id]['modify_access_by_owner'] : 1;
                $this->read_access_id = isset($this->arrLoadedCategories[$id]['read_access_id']) ? $this->arrLoadedCategories[$id]['read_access_id'] : 0;
                $this->read_protected = (bool) $this->read_access_id;
                $this->read_groups = null;
                $this->add_subcategories_access_id = isset($this->arrLoadedCategories[$id]['add_subcategories_access_id']) ? $this->arrLoadedCategories[$id]['add_subcategories_access_id'] : 0;
                $this->add_subcategories_protected = (bool) $this->add_subcategories_access_id;
                $this->add_subcdategories_groups = null;
                $this->manage_subcategories_access_id = isset($this->arrLoadedCategories[$id]['manage_subcategories_access_id']) ? $this->arrLoadedCategories[$id]['manage_subcategories_access_id'] : 0;
                $this->manage_subcategories_protected = (bool) $this->manage_subcategories_access_id;
                $this->manage_subcategories_groups = null;
                $this->add_files_access_id = isset($this->arrLoadedCategories[$id]['add_files_access_id']) ? $this->arrLoadedCategories[$id]['add_files_access_id'] : 0;
                $this->add_files_protected = (bool) $this->add_files_access_id;
                $this->add_files_groups = null;
                $this->manage_files_access_id = isset($this->arrLoadedCategories[$id]['manage_files_access_id']) ? $this->arrLoadedCategories[$id]['manage_files_access_id'] : 0;
                $this->manage_files_protected = (bool) $this->manage_files_access_id;
                $this->manage_files_groups = null;
                $this->set_permissions_recursive = false;
                $this->names = isset($this->arrLoadedCategories[$id]['names']) ? $this->arrLoadedCategories[$id]['names'] : null;
                $this->descriptions = isset($this->arrLoadedCategories[$id]['descriptions']) ? $this->arrLoadedCategories[$id]['descriptions'] : null;
                $this->downloads = isset($this->arrLoadedCategories[$id]['downloads']) ? $this->arrLoadedCategories[$id]['downloads'] : null;
                $this->downloads_count = isset($this->arrLoadedCategories[$id]['downloads_count']) ? $this->arrLoadedCategories[$id]['downloads_count'] : null;
                $this->permission_set = false;
                $this->EOF = false;
                return true;
            }
        } else {
            $this->clean();
        }
    }

    private function loadCategories($filter = null, $search = null, $arrSort = null, $arrAttributes = null, $limit = null, $offset = null)
    {
        global $objDatabase;

        $arrDebugBackTrace = debug_backtrace();
        if (!in_array($arrDebugBackTrace[1]['function'], array('load', 'getCategories'))) {
            die("Category->loadCategories(): Illegal method call in {$arrDebugBackTrace[0]['file']} on line {$arrDebugBackTrace[0]['line']}!");
        }

        $this->arrLoadedCategories = array();
        $arrSelectCoreExpressions = array();
        //$arrSelectLocaleExpressions = array();
        $this->filtered_search_count = 0;
        $sqlCondition = array();

        // set filter
        if (isset($filter) && is_array($filter) && count($filter) || !empty($search)) {
            $sqlCondition = $this->getFilteredCategoryIdList($filter, $search);
        } elseif (!empty($filter)) {
            $sqlCondition['tables'] = array('core');
            $sqlCondition['conditions'] = array('tblC.`id` = '.intval($filter));
            $limit = 1;
        }

        // set sort order
        if (!($arrQuery = $this->setSortedCategoryIdList($arrSort, $sqlCondition, $limit, $offset))) {
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

        $query = 'SELECT tblC.`'.implode('`, tblC.`', $arrSelectCoreExpressions).'`'
            //.(count($arrSelectLocaleExpressions) ? ', tblL.`'.implode('`, tblL.`', $arrSelectLocaleExpressions).'`' : '')
            .'FROM `'.DBPREFIX.'module_downloads_category` AS tblC'
            .(/*count($arrSelectLocaleExpressions) || */$arrQuery['tables']['locale'] ? ' INNER JOIN `'.DBPREFIX.'module_downloads_category_locale` AS tblL ON tblL.`category_id` = tblC.`id`' : '')
            .($arrQuery['tables']['localeSort'] ? ' INNER JOIN `'.DBPREFIX.'module_downloads_category_locale` AS tblLS ON tblLS.`category_id` = tblC.`id`' : '')
            .(count($arrQuery['conditions']) ? ' WHERE '.implode(' AND ', $arrQuery['conditions']) : '')
            .' GROUP BY tblC.`id`'
            .(count($arrQuery['sort']) ? ' ORDER BY '.implode(', ', $arrQuery['sort']) : '');

        if (empty($limit)) {
            $objCategory = $objDatabase->Execute($query);
        } else {
            $objCategory = $objDatabase->SelectLimit($query, $limit, $offset);
        };

        if ($objCategory !== false && $objCategory->RecordCount() > 0) {
            while (!$objCategory->EOF) {
                foreach ($objCategory->fields as $attributeId => $value) {
                    $this->arrLoadedCategories[$objCategory->fields['id']][$attributeId] = $value;
                }
                $objCategory->MoveNext();
            }

            $this->first();
            return true;
        } else {
            $this->clean();
            return false;
        }
    }

    private function getFilteredCategoryIdList($arrFilter = null, $search = null)
    {
        $arrCategoryIds = array();
        $arrConditions = array();
        $arrSearchConditions = array();
        $tblLocales = false;

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
            }
        }

        // parse access permissions
        if (!\Permission::checkAccess(143, 'static', true)) {
            $objFWUser = \FWUser::getFWUserObject();

            // category access
            $arrConditions[] = '(tblC.`visibility` = 1'.(
                $objFWUser->objUser->login() ?
                    ' OR tblC.`owner_id` = '.$objFWUser->objUser->getId()
                    .(count($objFWUser->objUser->getDynamicPermissionIds()) ? ' OR tblC.`read_access_id` IN ('.implode(', ', $objFWUser->objUser->getDynamicPermissionIds()).')' : '')
                : '').')';
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
                $table = $type == 'core' ? 'tblC' : 'tblL';

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
        $arrAttribute = array('name', 'description');
        foreach ($arrAttribute as $attribute) {
            $arrConditions[] = "(tblL.`".$attribute."` LIKE '%".(is_array($search) ? implode("%' OR tblL.`".$attribute."` LIKE '%", array_map('addslashes', $search)) : addslashes($search))."%')";
        }

        return $arrConditions;
    }

    private function setSortedCategoryIdList($arrSort, $sqlCondition = null, $limit = null, $offset = null)
    {
        global $objDatabase;

        $arrCustomSelection = array();
        $joinLocaleTbl = false;
        $joinLocaleSortTbl = false;
        $arrCategoryIds = array();
        $arrSortExpressions = array();
        $nr = 0;

        if (!empty($sqlCondition)) {
            if (isset($sqlCondition['tables'])) {
                if (in_array('locale', $sqlCondition['tables'])) {
                    $joinLocaleTbl = true;
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
                        $arrSortExpressions[] = 'tblC.`'.$attribute.'` '.$direction;
                    } elseif (isset($this->arrAttributes['locale'][$attribute])) {
                        $arrSortExpressions[] = 'tblLS.`'.$attribute.'` '.$direction;
                        $arrCustomSelection[] = 'tblLS.`lang_id` = ' . DownloadsLibrary::getOutputLocale()->getId();
                        $joinLocaleSortTbl = true;
                    }
                } elseif ($attribute == 'special') {
                    $arrSortExpressions[] = $direction;
                }
            }
        }

        $query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT tblC.`id`
            FROM `'.DBPREFIX.'module_downloads_category` AS tblC'
            .($joinLocaleTbl ? ' INNER JOIN `'.DBPREFIX.'module_downloads_category_locale` AS tblL ON tblL.`category_id` = tblC.`id`' : '')
            .($joinLocaleSortTbl ? ' INNER JOIN `'.DBPREFIX.'module_downloads_category_locale` AS tblLS ON tblLS.`category_id` = tblC.`id`' : '')
            .(count($arrCustomSelection) ? ' WHERE '.implode(' AND ', $arrCustomSelection) : '')
            .(count($arrSortExpressions) ? ' ORDER BY '.implode(', ', $arrSortExpressions) : '');

        if (empty($limit)) {
            $objCategoryId = $objDatabase->Execute($query);
            $this->filtered_search_count = $objCategoryId->RecordCount();
        } else {
            $objCategoryId = $objDatabase->SelectLimit($query, $limit, intval($offset));
            $objCategoryCount = $objDatabase->Execute('SELECT FOUND_ROWS()');
            $this->filtered_search_count = $objCategoryCount->fields['FOUND_ROWS()'];
        }

        if ($objCategoryId !== false) {
            while (!$objCategoryId->EOF) {
                $arrCategoryIds[$objCategoryId->fields['id']] = array();
                $objCategoryId->MoveNext();
            }
        }

        $this->arrLoadedCategories = $arrCategoryIds;

        if (!count($arrCategoryIds)) {
            return false;
        }

        return array(
            'tables' => array(
                'locale'    => $joinLocaleTbl,
                'localeSort'=> $joinLocaleSortTbl
            ),
            'conditions'    => $arrCustomSelection,
            'sort'          => $arrSortExpressions
        );
    }

    public function reset()
    {
        $this->clean();
    }


    /**
     * Load next category
     *
     */
    public function next()
    {
        if (next($this->arrLoadedCategories) === false || !$this->load(key($this->arrLoadedCategories))) {
            $this->EOF = true;
        }
    }

    /**
     * Store category
     *
     * This stores the metadata of the category to the database.
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

        $objParentCategory = Category::getCategory($this->parent_id);
        if (!\Permission::checkAccess(143, 'static', true)
            // the user isn't the owner of the category
            && (!$this->id || (($objFWUser = \FWUser::getFWUserObject()) == false || !$objFWUser->objUser->login() || $this->owner_id != $objFWUser->objUser->getId()))
            && (
                // updating a category
                $this->id && (
                    // trying to update a main category -> this is prohibited
                    !$objParentCategory->getId()
                    // trying to update a subcategory
                    || $objParentCategory->getId() && (
                        // updating subcategories of the parent category is restricted
                        $objParentCategory->getManageSubcategoriesAccessId()
                        // the user doesn't have enough permissions
                        && !\Permission::checkAccess($objParentCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
                        // the user isn't the owner of the parent category
                        && (($objFWUser = \FWUser::getFWUserObject()) == false || !$objFWUser->objUser->login() || $objParentCategory->getOwnerId() != $objFWUser->objUser->getId())
                    )
                )
                // adding a new category
                || !$this->id && (
                   // trying to add a new main category -> this is prohibited
                    !$objParentCategory->getId()
                    // trying to add a subcategory
                    || $objParentCategory->getId() && (
                        // adding subcategories to the parent category is restricted
                        $objParentCategory->getAddSubcategoriesAccessId()
                        // the user doesn't have enough permissions
                        && !\Permission::checkAccess($objParentCategory->getAddSubcategoriesAccessId(), 'dynamic', true)
                        // the user isn't the owner of the parent category
                        && (($objFWUser = \FWUser::getFWUserObject()) == false || !$objFWUser->objUser->login() || $objParentCategory->getOwnerId() != $objFWUser->objUser->getId())
                    )
                )
            )
        ) {
            $this->error_msg[] = $objParentCategory->getId() ? ($this->id ? sprintf($_ARRAYLANG['TXT_DOWNLOADS_UPDATE_CATEGORY_PROHIBITED'], htmlentities($this->getName(), ENT_QUOTES, CONTREXX_CHARSET)) : sprintf($_ARRAYLANG['TXT_DOWNLOADS_ADD_SUBCATEGORY_TO_CATEGORY_PROHIBITED'], htmlentities($this->getName(), ENT_QUOTES, CONTREXX_CHARSET))) : $_ARRAYLANG['TXT_DOWNLOADS_ADD_MAIN_CATEGORY_PROHIBITED'];
            return false;
        }

        if ($this->id) {
            if ($objDatabase->Execute("
                UPDATE `".DBPREFIX."module_downloads_category`
                SET
                    `is_active` = ".intval($this->is_active).",
                    `parent_id` = ".intval($this->parent_id).",
                    `visibility` = ".intval($this->visibility).",
                    `owner_id` = ".intval($this->owner_id).",
                    `image` = '".addslashes($this->image)."',
                    `order` = ".intval($this->order).",
                    `deletable_by_owner` = ".intval($this->deletable_by_owner).",
                    `modify_access_by_owner` = ".intval($this->modify_access_by_owner)."
                WHERE `id` = ".$this->id
            ) === false) {
                $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_FAILED_UPDATE_CATEGORY'];
                return false;
            }
        } else {
            if ($objDatabase->Execute("
                INSERT INTO `".DBPREFIX."module_downloads_category` (
                    `is_active`,
                    `parent_id`,
                    `visibility`,
                    `owner_id`,
                    `image`,
                    `order`,
                    `deletable_by_owner`,
                    `modify_access_by_owner`
                ) VALUES (
                    ".intval($this->is_active).",
                    ".intval($this->parent_id).",
                    ".intval($this->visibility).",
                    ".intval($this->owner_id).",
                    '".addslashes($this->image)."',
                    ".intval($this->order).",
                    ".intval($this->deletable_by_owner).",
                    ".intval($this->modify_access_by_owner)."
                )") !== false) {
                $this->id = $objDatabase->Insert_ID();
            } else {
                $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_FAILED_ADD_CATEGORY'];
                return false;
            }
        }

        if (isset($this->names) && !$this->storeLocales()) {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_COULD_NOT_STORE_LOCALES'];
            return false;
        }

        if (!$this->storeDownloadAssociations()) {
            return false;
        }

        if (!$this->storePermissions()) {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_COULD_NOT_STORE_PERMISSIONS'];
            return false;
        }

        $objFWUser = \FWUser::getFWUserObject();
        $objFWUser->objUser->getDynamicPermissionIds(true);

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

        $objOldLocales = $objDatabase->Execute('SELECT `lang_id`, `name`, `description` FROM `'.DBPREFIX.'module_downloads_category_locale` WHERE `category_id` = '.$this->id);
        if ($objOldLocales !== false) {
            while (!$objOldLocales->EOF) {
                $arrOldLocales[$objOldLocales->fields['lang_id']] = array(
                    'name'          => $objOldLocales->fields['name'],
                    'description'   => $objOldLocales->fields['description']
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
            if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_downloads_category_locale` (`lang_id`, `category_id`, `name`, `description`) VALUES (".$langId.", ".$this->id.", '".addslashes($this->names[$langId])."', '".addslashes($this->descriptions[$langId])."')") === false) {
                $status = false;
            }
        }

        foreach ($arrRemovedLocales as $langId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_downloads_category_locale` WHERE `category_id` = ".$this->id." AND `lang_id` = ".$langId) === false) {
                $status = false;
            }
        }

        foreach ($arrUpdatedLocales as $langId) {
            if ($this->names[$langId] != $arrOldLocales[$langId]['name'] || $this->descriptions[$langId] != $arrOldLocales[$langId]['description']) {
                if ($objDatabase->Execute("UPDATE `".DBPREFIX."module_downloads_category_locale` SET `name` = '".addslashes($this->names[$langId])."', `description` = '".addslashes($this->descriptions[$langId])."' WHERE `category_id` = ".$this->id." AND `lang_id` = ".$langId) === false) {
                    $status = false;
                }
            }
        }
        return $status;
    }

    public function storeDownloadAssociations()
    {
        global $objDatabase;

        $arrOldDownloads = array();
        $status = true;
        $userId = ($objFWUser = \FWUser::getFWUserObject()) == true && $objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0;
        $objDownload = new Download();

        if (!isset($this->downloads)) {
            $this->loadDownloadAssociations();
        }
        $arrDownloads  = array_keys($this->downloads);

        $objOldDownloads = $objDatabase->Execute('SELECT `download_id` FROM `'.DBPREFIX.'module_downloads_rel_download_category` WHERE `category_id` = '.$this->id);
        if ($objOldDownloads !== false) {
            while (!$objOldDownloads->EOF) {
                $arrOldDownloads[] = $objOldDownloads->fields['download_id'];
                $objOldDownloads->MoveNext();
            }
        } else {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_COULD_NOT_STORE_DOWNLOAD_ASSOCIATIONS'];
            return false;
        }

        if (\Permission::checkAccess(143, 'static', true)
            || !$this->getAddFilesAccessId()
            || \Permission::checkAccess($this->getAddFilesAccessId(), 'dynamic', true)
            || $this->getOwnerId() == $userId
        ) {
            $arrNewDownloads = array_diff($arrDownloads, $arrOldDownloads);
        } else {
            $arrNewDownloads = array();
        }

        $removePermission = \Permission::checkAccess(143, 'static', true)
            || !$this->getManageFilesAccessId()
            || \Permission::checkAccess($this->getManageFilesAccessId(), 'dynamic', true)
            || $this->getOwnerId() == $userId;
        $arrRemovedDownloads = array_diff($arrOldDownloads, $arrDownloads);

        foreach ($arrRemovedDownloads as $downloadId) {
            $objDownload->load($downloadId);
            if (!$removePermission && !$objDownload->EOF && $objDownload->getOwnerId() != $userId) {
                unset($arrRemovedDownloads[array_search($downloadId, $arrRemovedDownloads)]);
            }
        }

        foreach ($arrNewDownloads as $downloadId) {
            if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."module_downloads_rel_download_category` (`category_id`, `download_id`, `order`) VALUES (".$this->id.", ".$downloadId.", ( SELECT `order` FROM `".DBPREFIX."module_downloads_download` WHERE `id` = ".$downloadId."))") === false) {
                $status = false;
            }
        }

        foreach ($arrRemovedDownloads as $downloadId) {
            if ($objDatabase->Execute("DELETE FROM `".DBPREFIX."module_downloads_rel_download_category` WHERE `category_id` = ".$this->id." AND `download_id` = ".$downloadId) === false) {
                $status = false;
            }
        }
        if (!$status) {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_COULD_NOT_STORE_DOWNLOAD_ASSOCIATIONS'];
        }

        return $status;
    }

    private function storePermissions()
    {
        global $objDatabase;

        if (!$this->permission_set) {
            return true;
        }

        $status = true;
        foreach ($this->arrPermissionTypes as $type) {
            if ($this->{$type.'_protected'}) {
                // set protection
                if ($this->{$type.'_access_id'} || $this->{$type.'_access_id'} = \Permission::createNewDynamicAccessId()) {
                    \Permission::removeAccess($this->{$type.'_access_id'}, 'dynamic');
                    if (count($this->{$type.'_groups'})) {
                        \Permission::setAccess($this->{$type.'_access_id'}, 'dynamic', $this->{$type.'_groups'});
                    }
                } else {
                    // remove protection due that no new access-ID could have been created
                    $this->{$type.'_access_id'} = 0;
                    $status = false;
                }
            } elseif ($this->{$type.'_access_id'}) {
                // remove protection
                \Permission::removeAccess($this->{$type.'_access_id'}, 'dynamic');
                $this->{$type.'_access_id'} = 0;
            }
        }

        if (!$status) {
            return false;
        }

        if ($objDatabase->Execute("
            UPDATE `".DBPREFIX."module_downloads_category`
            SET
                `read_access_id` = ".intval($this->read_access_id).",
                `add_subcategories_access_id` = ".intval($this->add_subcategories_access_id).",
                `manage_subcategories_access_id` = ".intval($this->manage_subcategories_access_id).",
                `add_files_access_id` = ".intval($this->add_files_access_id).",
                `manage_files_access_id` = ".intval($this->manage_files_access_id)."
            WHERE `id` = ".$this->id
        ) === false) {
            return false;
        } else {
            if ($this->set_permissions_recursive) {
                foreach ($this->arrPermissionTypes as $type) {
                    $arrPermissions[$type] = array(
                        'protected' => $this->{$type.'_protected'},
                        'groups'    => $this->{$type.'_groups'}
                    );
                }

                $objSubcategory = Category::getCategories(array('parent_id' => $this->getId()));
                while (!$objSubcategory->EOF) {
                    $objSubcategory->setPermissionsRecursive(true);
                    $objSubcategory->setPermissions($arrPermissions);
                    $objSubcategory->setVisibility($this->visibility);
                    $objSubcategory->store();
                    $objSubcategory->next();
                }
            }

            return true;
        }
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


    /**
     * Check if the category is a subcategory of a specific category
     */
    public function check4Subcategory($categoryId)
    {
        $objCategory = Category::getCategory($this->getParentId());
        while (!$objCategory->EOF) {
            if ($objCategory->getId() == $categoryId) {
                return true;
            }
            $objCategory = Category::getCategory($objCategory->getParentId());
        }
        return false;
    }


    public function setParentId($parentId)
    {
        global $_ARRAYLANG;

        if ($this->parent_id == $parentId) {
            return true;
        }

        // check if the user is allowed to change the parent id
        if ($this->parent_id) {
            $objParentCategory = Category::getCategory($this->parent_id);
            if (!\Permission::checkAccess(143, 'static', true)
                && $objParentCategory->getManageSubcategoriesAccessId()
                && !\Permission::checkAccess($objParentCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
                && (($objFWUser = \FWUser::getFWUserObject()) == false || !$objFWUser->objUser->login() || $objParentCategory->getOwnerId() != $objFWUser->objUser->getId())
            ) {
                $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_CHANGE_PARENT_CATEGORY_PROHIBITED'];
                return false;
            }
        }

        // check if the user is allowed to use the desired category as a parent id
        $objParentCategory = Category::getCategory($parentId);
        if (!$objParentCategory->EOF || \Permission::checkAccess(143, 'static', true)) {
            if (!\Permission::checkAccess(143, 'static', true)
                && $objParentCategory->getAddSubcategoriesAccessId()
                && !\Permission::checkAccess($objParentCategory->getAddSubcategoriesAccessId(), 'dynamic', true)
                && (($objFWUser = \FWUser::getFWUserObject()) == false || !$objFWUser->objUser->login() || $objParentCategory->getOwnerId() != $objFWUser->objUser->getId())
            ) {
                $this->error_msg[] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_ADD_SUBCATEGORY_TO_CATEGORY_PROHIBITED'], htmlentities($objParentCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET));
                return false;
            }
        } else {
            $this->error_msg[] = $_ARRAYLANG['TXT_DOWNLOADS_ADD_MAIN_CATEGORY_PROHIBITED'];
            return false;
        }

        if ($objParentCategory->check4Subcategory($this->getId())) {
            return false;
        }

        $this->parent_id = $parentId;
        return true;
    }

    public function setActiveStatus($active)
    {
        $this->is_active = $active;
    }

    public function setOwner($userId)
    {
        $this->owner_id = $userId;
    }

    public function setOrder($orderNr)
    {
        $this->order = $orderNr;
    }

    public function setDeletableByOwner($deletable)
    {
        $this->deletable_by_owner = $deletable;
    }

    public function setModifyAccessByOwner($modifyAccess)
    {
        $this->modify_access_by_owner = $modifyAccess;
    }

    public function setImage($path)
    {
        $this->image = $path;
    }

    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    public function setNames($arrNames)
    {
        $this->names = $arrNames;
    }

    public function setDescriptions($arrDescriptions)
    {
        $this->descriptions = $arrDescriptions;
    }

    public function setDownloads($arrDownloads)
    {
        $this->downloads = count($arrDownloads) ? array_combine($arrDownloads, array_pad(array(), count($arrDownloads), 0)) : array();
    }

    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    private function resolvePermissionDependencies($arrCategoryPermissions, $arrPermissionDependencies, $parentPermission = null, $protected = false)
    {
        foreach($arrPermissionDependencies as $permission => $arrDependendPermissions) {
            $arrCategoryPermissions[$permission]['protected'] = $arrCategoryPermissions[$permission]['protected'] || $protected;
            if (is_array($arrDependendPermissions)) {
                $arrCategoryPermissions = $this->resolvePermissionDependencies($arrCategoryPermissions, $arrDependendPermissions, $permission, $arrCategoryPermissions[$permission]['protected']);
            }
            if (isset($arrCategoryPermissions[$parentPermission]) && $arrCategoryPermissions[$parentPermission]['protected']) {
                $arrCategoryPermissions[$parentPermission]['groups'] = array_unique(array_merge($arrCategoryPermissions[$parentPermission]['groups'], $arrCategoryPermissions[$permission]['groups']));
            }
        }

        return $arrCategoryPermissions;
    }

    public function setPermissions($arrPermissions)
    {
        $arrPermissions = $this->resolvePermissionDependencies($arrPermissions, $this->arrPermissionDependencies);

        foreach ($arrPermissions as $permission => $arrPermission) {
            $this->{$permission.'_protected'} = $arrPermission['protected'];
            $this->{$permission.'_groups'} = $this->{$permission.'_protected'} ? $arrPermission['groups'] : array();
        }

        $this->permission_set = true;
    }

    public function setPermissionsRecursive($recursive)
    {
        $this->set_permissions_recursive = $recursive;
    }

    public function getPermissions()
    {
        $objFWUser = \FWUser::getFWUserObject();

        $arrPermissions = array();
        foreach ($this->arrPermissionTypes as $type) {
            if (isset($this->{$type.'_groups'})) {
                $arrGroups = $this->{$type.'_groups'};
            } else {
                $objGroup = $objFWUser->objGroup->getGroups(array('dynamic' => $this->{$type.'_access_id'}));
                $arrGroups = $objGroup->getLoadedGroupIds();
            }

            $arrPermissions[$type] = array(
                'protected'             => $this->{$type.'_protected'},
                'groups'                => $arrGroups,
                'associated_groups'     => array(),
                'not_associated_groups' => array()
            );

        }

        return $arrPermissions;
    }

    public function updateDownloadOrder($arrDownloadOrder)
    {
        global $objDatabase, $_ARRAYLANG;

        $arrFailedDownloads = array();
        $objDownload = new Download();

        foreach ($arrDownloadOrder as $downloadId => $order) {
            if ($objDatabase->Execute('UPDATE `'.DBPREFIX.'module_downloads_rel_download_category` SET `order` = '.intval($order).' WHERE `download_id` = '.$downloadId.' AND `category_id` = '.$this->getId()) === false) {
                $objDownload->load($downloadId);
                if (!$objDownload->EOF) {
                    $arrFailedDownloads[] = htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET);
                }
            }
        }

        if (count($arrFailedDownloads)) {
            $this->error_msg[] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_ORDER_SET_FAILED'], implode(', ', $arrFailedDownloads));
            return false;
        } else {
            //clear Esi Cache
            DownloadsLibrary::clearEsiCache();
            return true;
        }
    }

    public function getReadAccessId()
    {
        return $this->read_access_id;
    }

    public function getAddSubcategoriesAccessId()
    {
        return $this->add_subcategories_access_id;
    }

    public function getManageSubcategoriesAccessId()
    {
        return $this->manage_subcategories_access_id;
    }

    public function getAddFilesAccessId()
    {
        return $this->add_files_access_id;
    }

    public function getManageFilesAccessId()
    {
        return $this->manage_files_access_id;
    }

    /**
     * Return a list of all available category widgets
     *
     * @return array    List of category widget names
     */
    public static function getCategoryWidgetNames() {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();
        $arrCategoryIds = array();

        $result = $db->Execute('SELECT id FROM `' . DBPREFIX . 'module_downloads_category`');
        if ($result !== false) {
            while (!$result->EOF) {
                $arrCategoryIds[] = $result->fields['id'];
                $result->MoveNext();
            }
        }

        // add entry 0 to allow overview widget functionality
        $arrCategoryIds[] = 0;

        return preg_replace(
            '/\d+/',
            'downloads_category_$0_list',
            $arrCategoryIds
        );
    }
}
