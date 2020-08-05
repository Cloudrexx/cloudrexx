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
 * Shop Product Categories
 *
 * Various helper methods for displaying stuff
 * @copyright   CLOUDREXX CMS - ASTALAVISTA IT AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

define('SHOP_CATEGORY_IMAGE_PATH',      \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopPath() . '/');
define('SHOP_CATEGORY_IMAGE_WEB_PATH',  \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath() . '/');


/**
 * Shop Categories
 *
 * Helper class
 * @copyright   CLOUDREXX CMS - ASTALAVISTA IT AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class ShopCategories
{
    /**
     * ShopCategory Tree array
     * @var     array
     * @access  private
     */
    private static $arrCategory = null;
    /**
     * The trail from the root (0, zero) to the selected ShopCategory.
     *
     * See {@link getTrailArray()} for details.
     * @var     array
     */
    private static $arrTrail = null;
    /**
     * OBSOLETE
     * ShopCategory array index
     * @var     array
     * @access  private
    private static $arrCategoryIndex;
     */
    /**
     * OBSOLETE
     * Virtual ShopCategory Tree array
     * @var     array
     * @access  private
    private static $arrCategoryVirtual = null;
     */
    /**
     * OBSOLETE
     * Virtual ShopCategory array index
     * @var     array
     * @access  private
    private static $arrCategoryVirtualIndex = null;
     */


    /**
     * Returns an array representing a tree of ShopCategories,
     * not including the root chosen.
     *
     * See {@link ShopCategories::getTreeArray()} for a detailed explanation
     * of the array structure.
     * @param   boolean $flagFull           If true, the full tree is built,
     *                                      only the parts visible for
     *                                      $selected_id otherwise.
     *                                      Defaults to false.
     * @param   boolean $active             Only return ShopCategories
     *                                      with active == true if true.
     *                                      Defaults to false.
     * @param   boolean $flagVirtual        If true, also returns the virtual
     *                                      content of ShopCategories marked
     *                                      as virtual.  Defaults to false.
     * @param   integer $selected_id        The optional selected ShopCategory
     *                                      ID.  If set and greater than zero,
     *                                      only the ShopCategories needed
     *                                      to display the Shop page are
     *                                      returned.
     * @param   integer $parent_id          The optional root ShopCategories ID.
     *                                      Defaults to 0 (zero).
     * @param   integer $maxlevel           The optional maximum nesting level.
     *                                      0 (zero) means all.
     *                                      Defaults to 0 (zero).
     * @return  array                       The array of ShopCategories on
     *                                      success, false on failure.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getTreeArray(
        $flagFull=false, $active=true, $flagVirtual=true,
        $selected_id=0, $parent_id=0, $maxlevel=0
    ) {
        // Return the same array if it's already been initialized
// OPEN: This won't work for the shopnavbar, as the categories menu is
// built first.
// This needs to be able to detect whether the arguments are the same.
//        if (is_array(self::$arrCategory))
//            return self::$arrCategory;
        // Otherwise, initialize it now
        if (self::buildTreeArray(
            $flagFull, $active, $flagVirtual,
            $selected_id, $parent_id, $maxlevel
        )) return self::$arrCategory;
        // It failed, probably due to a value of $selected_id that doesn't
        // exist.  Retry without it.
        if ($selected_id > 0)
            return self::buildTreeArray(
                $flagFull, $active, $flagVirtual,
                0, $parent_id, $maxlevel
            );
        // If that didn't help...
        return false;
    }


    /**
     * Builds the $arrCategory array as returned by
     * {@link ShopCategories::getTreeArray()}, representing a tree of
     * ShopCategories, not including the root chosen.
     *
     * The resulting array looks like:
     * array(
     *    'id => ShopCategory ID
     *    'name' => Category name,
     *    'shortDescription' => Category short description,
     *    'description' => Category description,
     *    'parent_id' => parent ID
     *    'ord' => order value,
     *    'active' => active flag (boolean),
     *    'picture' => 'picture name',
     *    'flags' => 'Category flags' (string),
     *    'virtual' => virtual flag status (boolean),
     *    'level' => nesting level,
     * ),
     * ... more parents
     * Note that this includes the virtual ShopCategories and their children.
     * @param   boolean $flagFull           If true, the full tree is built,
     *                                      only the parts visible for
     *                                      $selected_id otherwise.
     *                                      Defaults to false.
     * @param   boolean $active             Only return ShopCategories
     *                                      with active == true if true.
     *                                      Defaults to true.
     * @param   boolean $flagVirtual        If true, also returns the virtual
     *                                      content of ShopCategories marked
     *                                      as virtual.  Defaults to false.
     * @param   integer $selected_id        The optional selected ShopCategory
     *                                      ID.  If set and greater than zero,
     *                                      only the ShopCategories needed
     *                                      to display the Shop page are
     *                                      returned.
     * @param   integer $parent_id          The optional root ShopCategories ID.
     *                                      Defaults to 0 (zero).
     * @param   integer $maxlevel           The optional maximum nesting level.
     *                                      0 (zero) means all.
     *                                      Defaults to 0 (zero).
     * @return  boolean                     True on success, false otherwise.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function buildTreeArray(
        $flagFull=false, $active=true, $flagVirtual=true,
        $selected_id=0, $parent_id=0, $maxlevel=0
    ) {
        self::$arrCategory = array();
//        self::$arrCategoryIndex = array();
        // Set up the trail from the root (0, zero) to the selected ShopCategory
        if (!self::buildTrailArray($selected_id)) {
//die("Failed to build trail");
            return false;
        }
        if (!self::buildTreeArrayRecursive(
            $flagFull, $active, $flagVirtual,
            $selected_id, $parent_id, $maxlevel
        )) {
//die("Failed to build tree");
            return false;
        }
        return true;
    }


    /**
     * Recursively builds the $arrCategory array as returned by
     * {@link ShopCategories::getTreeArray()}.
     *
     * See {@link buildTreeArray()} for details.
     * @param   boolean $flagFull           If true, the full tree is built,
     *                                      only the parts visible for
     *                                      $selected_id otherwise.
     *                                      Defaults to false.
     * @param   boolean $active             Only return ShopCategories
     *                                      with active == true if true.
     *                                      Defaults to true.
     * @param   boolean $flagVirtual        If true, also returns the virtual
     *                                      content of ShopCategories marked
     *                                      as virtual.  Defaults to false.
     * @param   integer $selected_id        The optional selected ShopCategory
     *                                      ID.  If set and greater than zero,
     *                                      only the ShopCategories needed
     *                                      to display the Shop page are
     *                                      returned.
     * @param   integer $parent_id          The optional root ShopCategories ID.
     *                                      Defaults to 0 (zero).
     * @param   integer $maxlevel           The optional maximum nesting level.
     *                                      0 (zero) means all.
     *                                      Defaults to 0 (zero).
     * @param   integer $level              The optional nesting level,
     *                                      initially 0.
     *                                      Defaults to 0 (zero).
     * @return  boolean                     True on success, false otherwise.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function buildTreeArrayRecursive(
        $flagFull=false, $active=true, $flagVirtual=true,
        $selected_id=0, $parent_id=0, $maxlevel=0, $level=0
    ) {
        // Get the ShopCategories's children
        $arrCategory = ShopCategories::getChildCategoriesById(
            $parent_id, $active, $flagVirtual);
        // Has there been an error?
        if ($arrCategory === false) return false;
        foreach ($arrCategory as $objCategory) {
            $id = $objCategory->id();
//            $index = count(self::$arrCategory);
//            self::$arrCategory[$index] = array(
            self::$arrCategory[$id] = array(
                'id' => $id,
                'name' => $objCategory->name(),
                'shortDescription' => $objCategory->shortDescription(),
                'description' => $objCategory->description(),
                'parent_id' => $objCategory->parent_id(),
                'ord' => $objCategory->ord(),
                'active' => $objCategory->active(),
                'picture' => $objCategory->picture(),
                'flags' => $objCategory->flags(),
                'virtual' => $objCategory->virtual(),
                'level' => $level,
            );
//            self::$arrCategoryIndex[$id] = $index;
            // Get the grandchildren if
            // - the maximum depth has not been exceeded and
            // - the full list has been requested, or the current ShopCategory
            //   is an ancestor of the selected one or the selected itself.
            if (   ($maxlevel == 0 || $level < $maxlevel)
                && ($flagFull || in_array($id, self::$arrTrail))
                && (!$objCategory->virtual() || $flagVirtual)) {
                self::buildTreeArrayRecursive(
                    $flagFull, $active, $flagVirtual,
                    $selected_id, $id, $maxlevel, $level+1
                );
            }
        }
        return true;
    }


    /**
     * Returns a string listing all ShopCategory IDs contained within the
     * subtree starting with the ShopCategory with ID $parent_id.
     *
     * This string is used to limit the range of Product searches.
     * The IDs are comma separated, ready to be used in an SQL query.
     * @param   integer $parent_id          The optional root ShopCategories ID.
     *                                      Defaults to 0 (zero).
     * @param   boolean $active             Only return ShopCategories
     *                                      with active == true if true.
     *                                      Defaults to true.
     * @return  string                      The ShopCategory ID list
     *                                      on success, false otherwise.
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getSearchCategoryIdString($parent_id=0, $active=true)
    {
        global $objDatabase;

        $strIdList = '';
        $tempList = $parent_id;
        while (1) {
            // Get the ShopCategories' children
            $query = "
               SELECT `id`
                 FROM `".DBPREFIX."module_shop".MODULE_INDEX."_categories`
                 WHERE `parent_id` ". (empty($parent_id) ? 'IS NULL' : 'IN(' . (
                     $tempList
                 ) . ')') .
                ($active ? ' AND `active`=1' : '')."
                ORDER BY `ord` ASC";
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) {
                return ShopCategory::errorHandler();
            }
            $strIdList .= ($strIdList ? ',' : '').$tempList;
            if ($objResult->EOF) {
                return $strIdList;
            }
            $tempList = '';
            while (!$objResult->EOF) {
                $tempList .=
                    ($tempList ? ',' : '').
                    $objResult->fields['id'];
                $objResult->MoveNext();
            }
        }
    }


    /**
     * Returns the ShopCategories ID trail array.
     *
     * See {@link ShopCategories::getTrailArray()} for details on
     * the array structure.
     * @param   integer $selected_id        The selected ShopCategory ID.
     * @return  mixed                       The array of ShopCategory IDs
     *                                      on success, false on failure.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getTrailArray($selected_id=0)
    {
        // Return the same array if it's already been initialized
        if (is_array(self::$arrTrail)) return self::$arrTrail;
        // Otherwise, initialize it now
        if (!self::buildTrailArray($selected_id)) return false;
        return self::$arrCategory;
    }


    /**
     * Build the ShopCategories ID trail array.
     *
     * Sets up an array of ShopCategories IDs of the $shopCategoryId,
     * and all its ancestors.
     * @param   integer   $shopCategoryId   The ShopCategories ID
     * @return  mixed                       The array of all ancestor
     *                                      ShopCategories on success,
     *                                      false otherwise.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function buildTrailArray($shopCategoryId)
    {
        self::$arrTrail = array($shopCategoryId);
        while ($shopCategoryId != 0) {
            $objCategory = ShopCategory::getById($shopCategoryId, FRONTEND_LANG_ID);
            if (!$objCategory) {
                // Probably due to an illegal or unknown ID.
                // Use a dummy array so the work can go on anyway.
                self::$arrTrail = array(0, $shopCategoryId);
                return false;
            }
            $shopCategoryId = $objCategory->parent_id();
            self::$arrTrail[] = $shopCategoryId;
        }
        self::$arrTrail = array_reverse(self::$arrTrail);
        return true;
    }


    /**
     * Invalidate the current state of the arrShopCategory array and its
     * index.
     *
     * Do this after changing the database tables or in order to get
     * a different subset of the Shop Categories the next time
     * {@link ShopCategories::getTreeArray()} is called.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function invalidateTreeArray()
    {
        self::$arrCategory = null;
//        self::$arrCategoryIndex = false;
    }


    /**
     * Returns the number of elements in the ShopCategory array of this object.
     *
     * If the array has not been initialized before, boolean false is returned.
     * @return  mixed                       The element count, or false.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getTreeNodeCount()
    {
        if (!is_array(self::$arrCategory)) {
            return false;
        }
        return count(self::$arrCategory);
    }


    /**
     * Returns the array of ShopCategory data for the given ID.
     *
     * If the ShopCategory array is not initialized, or if an invalid ID
     * is provided, returns null.
     * @param   integer     $id         The ShopCategory ID
     * @return  array                   The ShopCategory data array on
     *                                  success, null otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getArrayById($id)
    {
        return (empty(self::$arrCategory[$id]) ? null : self::$arrCategory[$id]);
    }


    /**
     * Deletes the Category with the given ID
     *
     * Does not delete subcategories nor pictures.
     * Use {@see delete()} for that.
     * If the Category doesn't exist to begin with, returns null.
     * @param   integer     $id         The ShopCategory ID
     * @return  boolean                 True on success, false on failure,
     *                                  or null otherwise
     */
    static function deleteById($id, $flagDeleteImages=false)
    {
        $objCategory = ShopCategory::getById($id);
        if ($objCategory === null) return null;
        return $objCategory->delete($flagDeleteImages);
    }


    /**
     * Toggles the Category status for the given ID
     *
     * If the Category doesn't exist to begin with, returns null.
     * @param   integer     $id         The ShopCategory ID
     * @return  boolean                 True on success, false on failure,
     *                                  or null otherwise
     */
    static function toggleStatusById($id)
    {
        $objCategory = ShopCategory::getById($id);
        if ($objCategory === null) return null;
        $objCategory->active(!$objCategory->active());
        return $objCategory->update();
    }


    /**
     * Delete all ShopCategories from the database.
     *
     * Also removes associated subcategories and Products.
     * Images will only be erased from the disc if the optional
     * $flagDeleteImages parameter evaluates to true.
     * @param   boolean $flagDeleteImages   Delete associated image files if
     *                          true.  Defaults to false
     * @return  boolean         True on success, false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteAll($flagDeleteImages=false)
    {
        $arrChildCategoryId = self::getChildCategoryIdArray(0, false);
        foreach ($arrChildCategoryId as $id) {
            // Delete siblings and Products as well; delete images if desired.
            if (self::deleteById($id, $flagDeleteImages) === false) return false;
        }
        return true;
    }


    /**
     * Returns the best match for the Category Image
     *
     * If there is an image specified for the Category itself, its name
     * is returned.  Otherwise, the Products in the Category are searched
     * for a valid image, and if one can be found, its name is returned.
     * If neither can be found, the same process is repeated with all
     * subcategories.
     * If no image could be found at all, returns the empty string.
     * @param   integer $catId          The ShopCategory to search
     * @param   boolean $active         Only consider active Categories if true
     * @return  string                  The product thumbnail path on success,
     *                                  the empty string otherwise.
     * @global  ADONewConnection
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getPictureById($catId=0, $active=true)
    {
        global $objDatabase;

        // Look for an image in child Categories
        $query = "
            SELECT `picture`, `id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_categories`
              WHERE `parent_id` ". empty($parent_id) ? 'IS NULL' : '='.$catId ."
               AND `picture`!=''
          ORDER BY `ord` ASC";
        $objResult = $objDatabase->Execute($query);
        if ($objResult && $objResult->RecordCount() > 0) {
            // Got a picture
            $imageName = $objResult->fields['picture'];
            return $imageName;
        }
        // Otherwise, look for images in Products within the children
        $arrChildCategoryId =
            self::getChildCategoryIdArray($catId, $active);
        foreach ($arrChildCategoryId as $catId) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $productRepo = $cx->getDb()->getEntityManager()->getRepository(
                'Cx\Modules\Shop\Model\Entity\Product'
            );
            $imageName = $productRepo->getPictureByCategoryId($catId);
            if ($imageName) return $imageName;
        }

        // No picture there either, try the subcategories
        foreach ($arrChildCategoryId as $catId) {
            $imageName = self::getPictureById($catId);
            if ($imageName) return $imageName;
        }
        // No more subcategories, no picture -- give up
        return '';
    }


    /**
     * Returns an array of children of the ShopCategories
     * with ID $parent_id.
     *
     * Note that for virtual ShopCategories, this will include their children.
     * @param   integer $parent_id          The parent ShopCategories ID
     * @param   boolean $active             Only return ShopCategories with
     *                                      active==1 if true.
     *                                      Defaults to false.
     * @return  array                       An array of ShopCategories objects
     *                                      on success, false on failure.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getChildCategoriesById(
        $parent_id=0,
        $active=true, $flagVirtual=true
    ) {
        $arrChildShopCategoriesId =
            ShopCategories::getChildCategoryIdArray(
                $parent_id, $active, $flagVirtual
            );
        if (!is_array($arrChildShopCategoriesId)) return false;
        $arrShopCategories = array();
        foreach ($arrChildShopCategoriesId as $id) {
            $objCategory = ShopCategory::getById($id);
            if (!$objCategory) continue;
            $arrShopCategories[$id] = $objCategory;
        }
        return $arrShopCategories;
    }


    /**
     * Select an array of ShopCategories matching the wildcards
     * from the database.
     *
     * Uses the values of $objCategory  as pattern for the match.
     * Empty values will be ignored.  Tests for identity of the fields,
     * except with the name (pattern match) and the flags (matching records
     * must contain (at least) all of the flags present in the pattern).
     * @return  array                   Array of the resulting
     *                                  Shop Category objects
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getByWildcard($objCategory)
    {
        global $objDatabase;

        $arrSql = \Text::getSqlSnippets('`category`.`id`',
            FRONTEND_LANG_ID, 'Shop',
            array(
                'name' => ShopCategory::TEXT_NAME,
                'shortDescription' => ShopCategory::TEXT_SHORT_DESCRIPTION,
                'description' => ShopCategory::TEXT_DESCRIPTION,
            )
        );
        $query = "
            SELECT `category`.`id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_categories` AS `category`".
             $arrSql['join']."
             WHERE 1 ".
        (!empty($objCategory->id)
            ? " AND id=$objCategory->id" : '').
        (!empty($objCategory->name)
            ? " AND ".$arrSql['alias'][ShopCategory::TEXT_NAME].
              " LIKE '%".addslashes($objCategory->name)."%'"
            : '').
        (!empty($objCategory->shortDescription)
            ? " AND ".$arrSql['alias'][ShopCategory::TEXT_SHORT_DESCRIPTION].
              " LIKE '%".addslashes($objCategory->shortDescription)."%'"
            : '').
        (!empty($objCategory->description)
            ? " AND ".$arrSql['alias'][ShopCategory::TEXT_DESCRIPTION].
              " LIKE '%".addslashes($objCategory->description)."%'"
            : '').
        (!empty($objCategory->parent_id)
            ? " AND parentid=$objCategory->parent_id" : '').
        (isset($objCategory->active)
            ? " AND active=".($objCategory->active ? 1 : 0) : '').
        (!empty($objCategory->ord)
            ? " AND ord=$objCategory->ord" : '').
        (!empty($objCategory->picture)
            ? " AND picture LIKE '%".addslashes($objCategory->picture)."%'" : '');
        $flags = explode(' ', $objCategory->flags);
        foreach ($flags as $flag) {
            $query .= " AND flags LIKE '%$flag%'";
        }
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrCategories = array();
        while (!$objResult->EOF) {
            $objCategory =
                ShopCategory::getById($objResult->fields['id']);
            if (!$objCategory) continue;
            $arrCategories[] = $objCategory;
            $objResult->MoveNext();
        }
        return $arrCategories;
   }


    /**
     * Returns the HTML code for a dropdown menu listing all ShopCategories.
     *
     * The <select> tag pair
     * with the menu name will be included, plus an option for the root
     * ShopCategory.
     * @global  array
     * @param   integer     $selected_id    The selected ShopCategories ID
     * @param   string      $name           The optional menu name,
     *                                      defaults to 'catId'.
     * @return  string                      The HTML dropdown menu code
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getMenu(
        $selected_id=0, $name='catId', $active=true
    ) {
        global $_ARRAYLANG;

        return
            "<select name='$name'>".
            "<option value='0'>{$_ARRAYLANG['TXT_ALL_PRODUCT_GROUPS']}</option>".
            self::getMenuoptions($selected_id, $active).
            "</select>";
    }


    /**
     * Returns the HTML code for a dropdown menu listing all ShopCategories.
     *
     * The <select> tag pair is not included, nor the option for the root
     * ShopCategory.
     * @param   integer $selected_id    The optional selected ShopCategories ID.
     * @param   boolean $active         If true, only active ShopCategories
     *                                  are included, all otherwise.
     * @param   integer $maxlevel       The maximum nesting level,
     *                                  defaults to 0 (zero), meaning all.
     * @param   boolean $include_none   Include an option for "no selected"
     *                                  category if true.  Defaults to false
     * @return  string                  The HTML code with all <option> tags,
     *                                  or the empty string on failure.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getMenuoptions(
        $selected_id=0, $active=true, $maxlevel=0, $include_none=false
    ) {
        global $_ARRAYLANG;

// OPEN: Implement this in a way so that both the Shopnavbar and the Shopmenu
// can be set up using only one call to buildTreeArray().
// Unfortunately, the set of records used is not identical in both cases.
//        if (!self::$arrCategory) {
        self::buildTreeArray(
            true, $active, true, $selected_id, 0, $maxlevel
        );
//        }
        // Check whether the ShopCategory with the selected ID is missing
        // in the index (and thus in the tree as well)
        $trailIndex = count(self::$arrTrail);
        while (   $selected_id > 0
               && $trailIndex > 0
//               && !isset(self::$arrCategoryIndex[$selected_id])
               && !isset(self::$arrCategory[$selected_id])
        ) {
            // So we choose its highest level ancestor present.
            $selected_id = self::$arrTrail[--$trailIndex];
        }
        $strMenu =
            ($include_none
              ? '<option value="0">'.
                $_ARRAYLANG['TXT_SHOP_CATEGORY_ALL'].
                '</option>'
              : '');
        foreach (self::$arrCategory as $arrCategory) {
            $level = $arrCategory['level'];
            $id    = $arrCategory['id'];
            $name  = $arrCategory['name'];
            $strMenu .=
                "<option value='$id'".
                // I dunno why, but the comparison "$selected_id == $id"
                // failed for some reason -- sometimes.
                // A little arithmetic would solve that, however.
                ($selected_id == $id ? \Html::ATTRIBUTE_SELECTED : '').'>'.
                ($level
                    ? str_repeat('&nbsp;', 3*($level-1)).'+&nbsp;'
                    : '').
// NOTE: This used to fail sometimes when UTF8 was used.
// Should be thoroughly tested.
                (empty($name)
                  ? '&nbsp;'
                  : htmlentities($name, ENT_QUOTES, CONTREXX_CHARSET)).
                "</option>\n";
        }
        return $strMenu;
    }


    /**
     * Returns an array of IDs of children of this ShopCategory.
     *
     * Note that this includes virtual children of ShopCategories,
     * if applicable.
     * @param   integer $parent_id          The parent Shop Category ID.
     * @param   boolean $active             Only return ShopCategories with
     *                                      active 1 if true.
     *                                      Defaults to false.
     * @return  array                       An array of ShopCategory IDs
     *                                      on success, false otherwise.
     * @global  ADONewConnection    $objDatabase  Database connection
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getChildCategoryIdArray($parent_id, $active=true)
    {
        global $objDatabase;

        $parent_id = max(null, intval($parent_id));
        $objResult = $objDatabase->Execute("
           SELECT `id`
             FROM `".DBPREFIX."module_shop".MODULE_INDEX."_categories`
            WHERE `parent_id` ". (is_null($parent_id) ? 'IS NULL' : '=?') .
            ($active ? ' AND `active`=1' : '')."
            ORDER BY `ord` ASC",
            array($parent_id));
        // Query flags: OR flags LIKE '%parent:$parent_id%'
        if (!$objResult) return ShopCategory::errorHandler();
        $arrChildShopCategoryId = array();
        while (!$objResult->EOF) {
            $arrChildShopCategoryId[] = $objResult->fields['id'];
            $objResult->MoveNext();
        }
        return $arrChildShopCategoryId;
    }


    /**
     * Returns the ShopCategory with the given parent ID and the given name,
     * if found.
     *
     * Returns false if the query fails, or if more than one child Category of
     * that name is be found.
     * If no such Category is encountered, returns null.
     * @param   string      $strName        The root ShopCategory name
     * @param   integer     $parent_id      The parent ShopCategory Id,
     *                                      may be 0 (zero) to search the roots.
     *                                      Ignored if null.
     * @param   boolean     $active         If true, only active ShopCategories
     *                                      are considered.
     * @return  mixed                       The ShopCategory on success,
     *                                      null if none found,
     *                                      false otherwise.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getChildNamed($strName, $parent_id=null, $active=true)
    {
        global $objDatabase;

        $arrSqlName = \Text::getSqlSnippets('`category`.`id`', FRONTEND_LANG_ID,
            'Shop', array('name' => ShopCategory::TEXT_NAME));
//DBG::log("getChildNamed($strName, $parent_id, $active): SQL: ".var_export($arrSqlName, true));
//DBG::log("getChildNamed(): JOIN: ".var_export($arrSqlName['join'], true));
        $parent_id = max(0, intval($parent_id));
        // Qquery flags: OR flags LIKE '%parent:$parent_id%'
        $objResult = $objDatabase->Execute("
           SELECT `category`.`id`
             FROM `".DBPREFIX."module_shop".MODULE_INDEX."_categories` AS `category`".
            $arrSqlName['join']."
            WHERE ".$arrSqlName['alias']['name']."='".addslashes($strName)."'".
            ($active ? ' AND `active`=1' : '').
            (is_null($parent_id) ? '' : " AND `parent_id`=$parent_id")."
            ORDER BY `ord` ASC");
        if (!$objResult) return ShopCategory::errorHandler();
        if ($objResult->RecordCount() > 1) return false;
        if ($objResult->EOF) return null;
        return ShopCategory::getById($objResult->fields['id']);
    }


    /**
     * Returns the parent category ID of the ShopCategory specified by its ID,
     *
     * If the ID given corresponds to a top level category,
     * 0 (zero) is returned, as there is no parent.
     * If the ID cannot be found, boolean false is returned.
     * @param   integer $shopCategoryId The ShopCategory ID
     * @return  mixed                   The parent category ID,
     *                                  or boolean false on failure.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getParentCategoryId($shopCategoryId)
    {
        $arrCategory = self::getArrayById($shopCategoryId);
        if (!$arrCategory) return false;
        return $arrCategory['parent_id'];
    }


    /**
     * Get the next ShopCategories ID after $shopCategoryId according to
     * the sorting order.
     * @param   integer $shopCategoryId     The ShopCategories ID
     * @return  integer                     The next ShopCategories ID
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getNextShopCategoriesId($shopCategoryId=0)
    {
        // Get the parent ShopCategories ID
        $parentShopCategoryId =
            ShopCategories::getParentCategoryId($shopCategoryId);
        if (!$parentShopCategoryId) {
            $parentShopCategoryId = 0;
        }
        // Get the IDs of all active children
        $arrChildShopCategoriesId =
            ShopCategories::getChildCategoryIdArray($parentShopCategoryId, true);
        return
            (isset($arrChildShopCategoriesId[
                array_search($parentShopCategoryId, $arrChildShopCategoriesId)+1
             ])
                ? $arrChildShopCategoriesId[
                    array_search($parentShopCategoryId, $arrChildShopCategoriesId)+1
                  ]
                : $arrChildShopCategoriesId[0]
            );
    }


    /**
     * Returns an array with the names of all ShopCategories marked as virtual.
     *
     * Note that the names are ordered according to the sorting order field.
     * @return  array               The array of virtual ShopCategory names
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getVirtualCategoryNameArray()
    {
        global $objDatabase;

        $arrSqlName = \Text::getSqlSnippets('`category`.`id`',
            FRONTEND_LANG_ID, 'Shop',
            array('name' => ShopCategory::TEXT_NAME));
        $query = "
           SELECT `category`.`id`, ".
            $arrSqlName['field']."
             FROM `".DBPREFIX."module_shop".MODULE_INDEX."_categories` AS `category`".
            $arrSqlName['join']."
            WHERE flags LIKE '%__VIRTUAL__%'
            ORDER BY ord ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrVirtual = array();
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            $strName = $objResult->fields['name'];
            // Replace Text in a missing language by another, if available
            if ($strName === null) {
                $objText = \Text::getById($id, 'Shop', ShopCategory::TEXT_NAME);
                if ($objText)
                    $strName = $objText->content();
            }
            $arrVirtual[$id] = $strName;
            $objResult->MoveNext();
        }
        return $arrVirtual;
    }


    /**
     * Returns an array with the IDs and names of all ShopCategories marked
     * as virtual.
     *
     * The array structure is
     *  array(
     *      ID => Category name,
     *      ... more ...
     *  )
     * Note that the array elements are ordered according to the
     * ordinal value.
     * @return  array               The array of virtual ShopCategory IDs/names
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getVirtualCategoryIdNameArray()
    {
        global $objDatabase;

        $arrSqlName = \Text::getSqlSnippets('`categories`.`id`',
            FRONTEND_LANG_ID, 'Shop',
            array('name' => ShopCategory::TEXT_NAME));
        $query = "
            SELECT `category`.`id`, ".
            $arrSqlName['field']."
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_categories AS `category`".
            $arrSqlName['join']."
             WHERE `category`.`flags` LIKE '%__VIRTUAL__%'
             ORDER BY `category`.`ord` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrVirtual = array();
        while (!$objResult->EOF) {
            $arrVirtual[$objResult->fields['id']] =
                $objResult->fields['name'];
            $objResult->MoveNext();
        }
        return $arrVirtual;
    }


    /**
     * Returns a string with HTML code to display the virtual ShopCategory
     * selection checkboxes.
     * @param   string      $strFlags       The Product Flags
     * @return  string                      The HTML checkboxes string
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getVirtualCategoriesSelectionForFlags($strFlags)
    {
        $arrName = self::getVirtualCategoryIdNameArray();
        $arrChecked = array();
        foreach ($arrName as $id => $name) {
            if (ShopCategory::testFlag2($name, $strFlags)) $arrChecked[] = $id;
        }
        return \Html::getCheckboxGroup('shopFlags',
            $arrName, $arrName, $arrChecked, false, '', '<br />');
    }


    /**
     * Create thumbnail and update the corresponding ShopCategory records
     *
     * Scans the ShopCategories with the given IDs.  If a non-empty picture
     * string with a reasonable extension is encountered, determines whether
     * the corresponding thumbnail is available and up to date or not.
     * If not, tries to load the file and to create a thumbnail.
     * Note that only single file names are supported!
     * Also note that this method returns a string with information about
     * problems that were encountered.
     * It skips records which contain no or invalid image
     * names, thumbnails that cannot be created, and records which refuse
     * to be updated!
     * The reasoning behind this is that this method is currently only called
     * from within some {@link _import()} methods.  The focus lies on importing;
     * whether or not thumbnails can be created is secondary, as the
     * process can be repeated if there is a problem.
     * @param   integer     $id         The ShopCategory ID
     * @param   integer     $maxWidth   The maximum thubnail width
     * @param   integer     $maxHeight  The maximum thubnail height
     * @param   integer     $quality    The thumbnail quality
     * @return  string                  Empty string on success, a string
     *                                  with error messages otherwise.
     * @global  array
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function makeThumbnailById($id, $maxWidth=120, $maxHeight=80, $quality=90)
    {
/*
    Note: The size and quality parameters should be taken from the
          settings as follows:
    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_width','Shop'),
    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_height','Shop'),
    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_quality','Shop')
*/
        global $_ARRAYLANG;

        if ($id <= 0) {
            return sprintf($_ARRAYLANG['TXT_SHOP_INVALID_CATEGORY_ID'], $id);
        }
        $objCategory = ShopCategory::getById($id, LANGID);
        if (!$objCategory) {
            return sprintf($_ARRAYLANG['TXT_SHOP_INVALID_CATEGORY_ID'], $id);
        }
        $imageName = $objCategory->picture();
        $imagePath = SHOP_CATEGORY_IMAGE_PATH.'/'.$imageName;
        // Only try to create thumbs from entries that contain a
        // plain text file name (i.e. from an import)
        if (   $imageName == ''
            || !preg_match('/\.(?:jpe?g|gif|png)$/', $imageName)) {
            return sprintf(
                $_ARRAYLANG['TXT_SHOP_UNSUPPORTED_IMAGE_FORMAT'],
                $id, $imageName
            );
        }
        // If the picture is missing, skip it.
        if (!file_exists($imagePath)) {
            return sprintf(
                $_ARRAYLANG['TXT_SHOP_MISSING_CATEGORY_IMAGES'],
                $id, $imageName
            );
        }
        // If the thumbnail exists and is newer than the picture,
        // don't create it again.
        $thumb_name = ImageManager::getThumbnailFilename($imagePath);
        if (file_exists($thumb_name)
         && filemtime($thumb_name) > filemtime($imagePath)) {
            return '';
        }
        // Already included by the Shop.
        $objImageManager = new \ImageManager();
        // Create thumbnail.
        // Deleting the old thumb beforehand is integrated into
        // _createThumbWhq().
        if (!$objImageManager->_createThumbWhq(
            SHOP_CATEGORY_IMAGE_PATH.'/',
            SHOP_CATEGORY_IMAGE_WEB_PATH.'/',
            $imageName,
            $maxWidth, $maxHeight, $quality
        )) {
            return sprintf(
                $_ARRAYLANG['TXT_SHOP_ERROR_CREATING_CATEGORY_THUMBNAIL'],
                $id
            );
        }
        return '';
    }


    /**
     * Returns an array of Category names indexed by their respective IDs
     * @param   boolean   $activeonly   If true, only active categories are
     *                                  included in the array
     * @return  array                   The array of Category names
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getNameArray($activeonly=false)
    {
//DBG::log("getNameArray():  Categories:<br />".var_export(self::$arrCategory, true)."<br />");
        if (empty(self::$arrCategory))
            self::buildTreeArray(true, $activeonly);
//DBG::log("getNameArray():  Categories:<br />".var_export(self::$arrCategory, true)."<br />");
        $arrName = array();
        foreach (self::$arrCategory as $arrCategory) {
            $arrName[$arrCategory['id']] = $arrCategory['name'];
        }
        return $arrName;
    }

}
