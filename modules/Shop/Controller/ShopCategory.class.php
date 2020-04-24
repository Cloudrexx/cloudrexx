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
 * Shop Product Category
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Container for Products in the Shop.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class ShopCategory
{
    /**
     * Text keys
     */
    const TEXT_NAME = 'category_name';
    const TEXT_SHORT_DESCRIPTION = 'category_short_description';
    const TEXT_DESCRIPTION = 'category_description';

    /**
     * @var     integer     $id         ShopCategory ID
     * @access  private
     */
    private $id = null;
    /**
     * @var     integer     $parent_id   Parent ShopCategory ID
     * @access  private
     */
    private $parent_id = 0;
    /**
     * @var     string      $name       ShopCategory name
     * @access  private
     */
    private $name = '';
    /**
     * @var     string      $shortDescription    ShopCategory short description
     */
    protected $shortDescription = '';
    /**
     * @var     string      $description    ShopCategory description
     * @access  private
     */
    private $description = '';
    /**
     * @var     boolean     $active     Active status of the ShopCategory
     * @access  private
     */
    private $active = 1;
    /**
     * @var     integer     $ord    Ordinal value of the ShopCategory
     * @access  private
     */
    private $ord = 0;
    /**
     * @var     string      $picture    ShopCategory picture name
     * @access  private
     */
    private $picture = '';
    /**
     * @var     string      $flags      ShopCategory flags
     * @access  private
     */
    private $flags = '';


    /**
     * Create a ShopCategory
     *
     * If the optional argument $category_id is greater than zero, the corresponding
     * category is updated.  Otherwise, a new category is created.
     * @access  public
     * @param   string  $name           The category name
     * @param   string  $description    The optional category description
     * @param   string  $shortDescription The optional category short description
     * @param   integer $parent_id      The optional parent ID of the category
     * @param   integer $active         The optional active status category.
     *                                  Defaults to null (unset)
     * @param   integer $ord            The optional ordinal value
     * @param   integer $id             The optional category ID
     * @return  ShopCategory            The ShopCategory
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function __construct(
        $name, $shortDescription=null,$description=null, $parent_id=0,
        $active=null, $ord=0, $id=0
    ) {
        $this->id = intval($id);
        // Use access methods here, various checks included.
        $this->name($name);
        $this->shortDescription($shortDescription);
        $this->description($description);
        $this->parent_id($parent_id);
        $this->active($active);
        $this->ord($ord);
    }


    /**
     * Returns the ShopCategory ID
     * @return  integer             The ShopCategory ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function id()
    {
        return $this->id;
    }

    /**
     * Sets the name, if present, and returns the current value
     * @param   string  $name       The optional name
     * @return  string              The current name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function name($name=null)
    {
        if (isset($name)) {
            $this->name = trim(strip_tags($name));
        }
        return $this->name;
    }

    /**
     * Sets the short description, if present, and returns the current value
     * @param   string  $description  The optional short description
     * @return  string                The current short description
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function shortDescription($shortDescription=null)
    {
        if (isset($shortDescription)) {
            $this->shortDescription = trim($shortDescription);
        }
        return $this->shortDescription;
    }

    /**
     * Sets the description, if present, and returns the current value
     * @param   string  $description  The optional description
     * @return  string                The current description
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function description($description=null)
    {
        if (isset($description)) {
            $this->description = trim($description);
        }
        return $this->description;
    }

    /**
     * Sets the parent ID, if present, and returns the current value.
     *
     * If the given parent ID equals the own ID, does not change the present
     * value, and returns null.
     * @param   integer   $parent_id  The optional parent ID
     * @return  integer               The current parent ID, or null
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function parent_id($parent_id=null)
    {
        if (isset($parent_id)) {
            $parent_id = intval($parent_id);
            if ($this->id && $parent_id == $this->id) return null;
            $this->parent_id = $parent_id;
        }
        return $this->parent_id;
    }

    /**
     * Sets the active status, if present, and returns the current value
     * @param   boolean   $active     The optional active status
     * @return  boolean               The active status
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function active($active=null)
    {
        if (isset($active)) {
            $this->active = (boolean)$active;
        }
        return $this->active;
    }

    /**
     * Sets the ordinal, if present, and returns the current value
     * @param   integer   $ord      The optional ordinal value
     * @return  integer             The current ordinal value
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function ord($ord=null)
    {
        if (isset($ord)) {
            $this->ord = max(intval($ord), 0);
        }
        return $this->ord;
    }

    /**
     * Sets the picture file name, if present, and returns the current value
     *
     * The $picture parameter value is not tested in any way, so please
     * watch your step.
     * @param   string    $picture    The optional picture file name
     * @return  string                The current picture file name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function picture($picture=null)
    {
        if (isset($picture)) {
            $this->picture = $picture;
        }
        return $this->picture;
    }

    /**
     * Sets the flags, if present, and returns the current value
     * @param   string    $flags    The optional flags
     * @return  string              The current flags
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function flags($flags=null)
    {
        if (isset($flags)) {
            $this->flags = $flags;
        }
        return $this->flags;
    }

    /**
     * Adds a flag
     *
     * Note that the match is case insensitive.
     * @param   string    $flag     The flag to be added
     * @return  boolean             Boolean true if the flags were accepted
     *                              or already present, false otherwise
     *                              (always true for the time being).
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function addFlag($flag)
    {
        if (!$this->testFlag($flag)) {
            $this->flags .= ' '.$flag;
        }
        return true;
    }
    /**
     * Removes a flag
     *
     * Note that the match is case insensitive.
     * @param   string    $flag     The flag to be removed
     * @return  boolean             Boolean true if the flags could be removed
     *                              or wasn't present, false otherwise
     *                              (always true for the time being).
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function removeFlag($flag)
    {
        $this->flags = trim(preg_replace(
            '/(?:^|\s)'.preg_quote($flag, '/').'(?:\s|\=\S*]|$)/i', ' ',
            $this->flags));
        return true;
    }
    /**
     * Tests for a match with the ShopCategory flags.
     *
     * Note that the match is case insensitive.
     * @param   string    $flag     The ShopCategory flag to test
     * @return  boolean             Boolean true if the flag is set,
     *                              false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function testFlag($flag)
    {
        return self::testFlag2($flag, $this->flags);
    }
    /**
     * Tests for a match with the flags in the string.
     *
     * Note that the match is case insensitive.
     * @param   string    $flag     The ShopCategory flag to test
     * @param   string              The ShopCategory flags
     * @return  boolean             Boolean true if the flag is set,
     *                              false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function testFlag2($flag, $flags)
    {
        return preg_match(
            '/(?:^|\s)'.preg_quote($flag, '/').'(?:\s|\=|$)/i',
            $flags);
    }

    /**
     * Sets the virtual flag if the argument evaluates to any boolean
     * value, and returns the current state
     * @param   boolean   $virtual  The optional virtual flag
     * @return  boolean             True if the ShopCategory is virtual,
     *                              false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function virtual($virtual=null)
    {
        if (isset($virtual)) {
            if ($virtual) {
                $this->addFlag('__VIRTUAL__');
            } else {
                $this->removeFlag('__VIRTUAL__');
            }
        }
        return $this->testFlag('__VIRTUAL__');
    }


    /**
     * Tests whether a record with the ID of this object is already present
     * in the database.
     * @return  boolean                 True if it exists, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function recordExists()
    {
        global $objDatabase;

        $query = "
            SELECT 1
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_categories
             WHERE id=$this->id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return false;
        }
        if ($objResult->EOF) {
            return false;
        }
        return true;
    }


    /**
     * Clones the ShopCategory
     *
     * Note that this does NOT create a copy in any way, but simply clears
     * the ShopCategory ID.  Upon storing this object, a new ID is created.
     * @param   boolean   $flagRecursive      Clone subcategories if true
     * @param   boolean   $flagWithProducts   Clone contained Products if true
     * @return  void
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function makeClone($flagRecursive=false, $flagWithProducts=false)
    {
        $oldId = $this->id;
        $this->id = 0;
        $this->store();
        $newId = $this->id;
        if ($flagRecursive) {
            foreach (ShopCategories::getChildCategoriesById($oldId)
                    as $objCategory) {
                $objCategory->makeClone($flagRecursive, $flagWithProducts);
                $objCategory->parent_id($newId);
                if (!$objCategory->store()) {
                    return false;
                }
            }
        }
        if ($flagWithProducts) {
            foreach (Products::getByShopCategory($oldId) as $objProduct) {
                $objProduct->makeClone();
                $objProduct->category_id($newId);
                if (!$objProduct->store()) {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Stores the ShopCategory object in the database.
     *
     * Either updates (id > 0) or inserts (id == 0) the object.
     * @return  boolean     True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function store()
    {
        // Empty names are invalid
        if ($this->name == '') return false;
        if ($this->recordExists()) {
            if (!$this->update()) return false;
        } else {
            if (!$this->insert()) return false;
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME, $this->name)) {
            return false;
        }
        if ($this->shortDescription == '') {
            // Delete empty short description record (current language only)
            if (!\Text::deleteById(
                $this->id, 'Shop', self::TEXT_SHORT_DESCRIPTION, FRONTEND_LANG_ID))
                return false;
        } else {
            if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
                self::TEXT_SHORT_DESCRIPTION, $this->shortDescription)) {
                return false;
            }
        }
        if ($this->description == '') {
            // Delete empty description record (current language only)
            if (!\Text::deleteById(
                $this->id, 'Shop', self::TEXT_DESCRIPTION, FRONTEND_LANG_ID))
                return false;
        } else {
            if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
                self::TEXT_DESCRIPTION, $this->description)) {
                return false;
            }
        }
        return true;
    }


    /**
     * Updates this ShopCategory in the database.
     * Returns the result of the query.
     * @return  boolean                 True on success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function update()
    {
        global $objDatabase;

        $query = "
            UPDATE `".DBPREFIX."module_shop".MODULE_INDEX."_categories`
              SET `parent_id`=$this->parent_id,
                  `active`=".($this->active ? 1 : 0).",
                  `ord`=$this->ord,
                  `picture`='".addslashes($this->picture)."',
                  `flags`='".addslashes($this->flags)."'
            WHERE `id`=$this->id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        return true;
    }


    /**
     * Inserts this ShopCategory into the database.
     *
     * On success, updates this objects' Category ID.
     * Uses the ID stored in this object, if greater than zero.
     * @return  boolean                 True on success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function insert()
    {
        global $objDatabase;

        $query = "
            INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_categories (
                `parent_id`, `active`, `ord`,
                `picture`, `flags`
                ".($this->id ? ', id' : '')."
            ) VALUES (
                $this->parent_id, ".($this->active ? 1 : 0).", $this->ord,
                '".addslashes($this->picture)."', '".addslashes($this->flags)."'
                ".($this->id ? ", $this->id" : '')."
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return false;
        }
        $this->id = $objDatabase->Insert_ID();
        return true;
    }


    /**
     * Deletes this ShopCategory from the database.
     *
     * Also removes associated subcategories and Products.
     * Images will only be erased from the disc if the optional
     * $flagDeleteImages parameter evaluates to true.
     * @return  boolean                 True on success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function delete($flagDeleteImages=false)
    {
        global $objDatabase;

        // Delete Products and images
        if (Products::deleteByShopCategory($this->id, $flagDeleteImages) === false) {
            return false;
        }
        // Delete subcategories
        foreach ($this->getChildCategories() as $subCategory) {
            if (!$subCategory->delete($flagDeleteImages)) {
                return false;
            }
        }
// TEST: Delete pictures, if requested
        if ($flagDeleteImages) {
            \File::delete_file($this->picture());
        }
        // Delete Text
        \Text::deleteById($this->id(), 'Shop', self::TEXT_NAME);
        \Text::deleteById($this->id(), 'Shop', self::TEXT_SHORT_DESCRIPTION);
        \Text::deleteById($this->id(), 'Shop', self::TEXT_DESCRIPTION);
        // Delete Category
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_categories
            WHERE id=$this->id");
        if (!$objResult) {
            return false;
        }

        return true;
    }


    /**
     * Looks for and deletes the sub-ShopCategory named $name
     * contained by the ShopCategory specified by $parent_id.
     *
     * The child's name must be unambiguous, or the method will fail.
     * @param   integer   $category_id  The parent ShopCategory ID
     * @param   string    $name         The ShopCategory name to delete
     * @return  boolean                 True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function deleteChildNamed($parent_id, $name)
    {
        $objCategory = new ShopCategory($name, $parent_id, '', '', '');
        $arrChild = $objCategory->getByWildcard();
        if (is_array($arrChild) && count($arrChild) == 1) {
            return $arrChild[0]->delete();
        }
        return false;
    }


    /**
     * Returns a ShopCategory selected by its ID from the database.
     *
     * Returns null if the Category does not exist.
     * @static
     * @param   integer       $category_id  The Shop Category ID
     * @return  ShopCategory                The Shop Category object on success,
     *                                      false on failure, or null otherwise.
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getById($category_id)
    {
        global $objDatabase;

        $category_id = intval($category_id);
        if ($category_id <= 0) return null;
        $arrSql = \Text::getSqlSnippets('`category`.`id`',
            FRONTEND_LANG_ID, 'Shop',
            array(
                'name' => self::TEXT_NAME,
                'shortDescription' => self::TEXT_SHORT_DESCRIPTION,
                'description' => self::TEXT_DESCRIPTION,
            )
        );
        $query = "
            SELECT `category`.`id`,
                   `category`.`parent_id`,
                   `category`.`active`,
                   `category`.`ord`,
                   `category`.`picture`,
                   `category`.`flags`, ".
                   $arrSql['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_categories` AS `category`".
                   $arrSql['join']."
             WHERE `category`.`id`=$category_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if ($objResult->EOF) return null;
        $id = $objResult->fields['id'];
        $strName = $objResult->fields['name'];
        if ($strName === null) {
            $objText = \Text::getById($id, 'Shop', self::TEXT_NAME);
            if ($objText) $strName = $objText->content();
        }
        $strShortDescription = $objResult->fields['shortDescription'];
        if ($strShortDescription === null) {
            $objText = \Text::getById($id, 'Shop', self::TEXT_SHORT_DESCRIPTION);
            if ($objText) $strShortDescription = $objText->content();
        }
        $strDescription = $objResult->fields['description'];
        if ($strDescription === null) {
            $objText = \Text::getById($id, 'Shop', self::TEXT_DESCRIPTION);
            if ($objText) $strDescription = $objText->content();
        }
//DBG::log("ShopCategory::getById($category_id): Loaded '$strName' / '$strDescription'");
        $objCategory = new ShopCategory(
            $strName,
            $strShortDescription,
            $strDescription,
            $objResult->fields['parent_id'],
            $objResult->fields['active'],
            $objResult->fields['ord'],
            $category_id
        );
        $objCategory->picture($objResult->fields['picture']);
        $objCategory->flags($objResult->fields['flags']);
        return $objCategory;
    }


    /**
     * Returns an array of this ShopCategory's children from the database.
     * @param   boolean $active     Only return ShopCategories with
     *                              active==1 if true.  Defaults to false.
     * @return  mixed                       An array of ShopCategory objects
     *                                      on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getChildCategories($active=false)
    {
        if ($this->id <= 0) return false;
        return ShopCategories::getChildCategoriesById(
            $this->id, $active
        );
    }


    /**
     * Returns an array of all IDs of children ShopCateries.
     * @return  mixed                   Array of the resulting Shop Category
     *                                  IDs on success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getChildrenIdArray()
    {
        global $objDatabase;

        $query = "
            SELECT id
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_categories
             WHERE parent_id=$this->id
          ORDER BY ord ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrShopCategoryID = array();
        while (!$objResult->EOF) {
            $arrShopCategoryID[] = $objResult->fields['id'];
            $objResult->MoveNext();
        }
        return $arrShopCategoryID;
   }


    /**
     * Returns the child ShopCategory of this with the given name, if found.
     *
     * Returns false if the query fails, or if no child ShopCategory of
     * that name can be found.
     * //Note that if there are two or more children of the same name (and with
     * //active status, if $active is true), a warning will be echo()ed.
     * //This is by design.
     * @static
     * @param   string      $strName        The child ShopCategory name
     * @param   boolean     $active         If true, only active ShopCategories
     *                                      are considered.
     * @return  mixed                       The ShopCategory on success,
     *                                      false otherwise.
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @global  array
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getChildNamed($strName, $active=true)
    {
        global $objDatabase;

        $query = "
           SELECT id
             FROM ".DBPREFIX."module_shop".MODULE_INDEX."_categories
            WHERE ".($active ? 'active=1 AND' : '')."
                  parent_id=$this->parent_id AND
                  catname='".addslashes($strName)."'
            ORDER BY ord ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
//        if ($objResult->RecordCount() > 1) echo("ShopCategory::getChildNamed($strName, $active): ".$_ARRAYLANG['TXT_SHOP_WARNING_MULTIPLE_CATEGORIES_WITH_SAME_NAME'].'<br />');
        if (!$objResult->EOF)
            return ShopCategory::getById($objResult->fields['id']);
        return false;
    }


    /**
     * Returns an array representing a tree of ShopCategories,
     * not including the root chosen.
     *
     * The resulting array looks like:
     * array(
     *   parent ID => array(
     *     child ID => array(
     *       'ord' => val,
     *       'active' => val,
     *       'level' => val,
     *     ),
     *     ... more children
     *   ),
     *   ... more parents
     * )
     * @static
     * @param   integer $parent_id          The optional root ShopCategory ID.
     *                                      Defaults to 0 (zero).
     * @param   boolean $active             Only return ShopCategories
     *                                      with active==1 if true.
     *                                      Defaults to false.
     * @param   integer $level              Optional nesting level, initially 0.
     *                                      Defaults to 0 (zero).
     * @return  array                       The array of ShopCategories,
     *                                      or false on failure.
     */
    static function getCategoryTree($parent_id=0, $active=false, $level=0)
    {
        // Get the ShopCategory's children
        $arrChildShopCategories =
            ShopCategory::getChildCategoriesById(
                $parent_id, $active
            );
        // has there been an error?
        if ($arrChildShopCategories === false) {
            return false;
        }
        // initialize root tree
        $arrCategoryTree = array();
        // local parent subtree
        $arrCategoryTree[$parent_id] = array();
        // the local parent's children
        foreach ($arrChildShopCategories as $objChildShopCategory) {
            $childCategoryId = $objChildShopCategory->id();
            $arrCategoryTree[$parent_id][$childCategoryId] = array(
                'ord' => $objChildShopCategory->ord(),
                'active' => $objChildShopCategory->active(),
                'level' => $level,
            );
            // get the grandchildren
            foreach (ShopCategory::getCategoryTree(
                        $childCategoryId, $active, $level+1
                    ) as $subCategoryId => $objSubCategory) {
                $arrCategoryTree[$subCategoryId] = $objSubCategory;
            };
        }
        return $arrCategoryTree;
    }


    /**
     * Returns the HTML code for a dropdown menu listing all ShopCategories.
     *
     * If the optional menu name string is non-empty, the <select> tag pair
     * with the menu name will be included, plus an option for the root
     * ShopCategory.  Otherwise, only the <option> tag list is returned.
     * @static
     * @global  array       $_ARRAYLANG     Language array
     * @param   integer     $selectedid     The selected ShopCategory ID
     * @param   string      $name           The optional menu name
     * @return  string                      The HTML dropdown menu code
     */
    static function getShopCategoryMenuHierarchic($selected=0, $name='catId')
    {
        global $_ARRAYLANG;

        $result =
            ShopCategory::getShopCategoryMenuHierarchicRecurse(0, 0, $selected);
        if ($name) {
            $result =
                "<select name='$name'>".
                "<option value='0'>{$_ARRAYLANG['TXT_ALL_PRODUCT_GROUPS']}</option>".
                "$result</select>";
        }
        return $result;
    }


    /**
     * Builds the ShopCategory menu recursively.
     *
     * Do not call this directly, use {@link getShopCategoryMenuHierarchic()}
     * instead.
     * @static
     * @param    integer  $parent_id    The parent ShopCategory ID.
     * @param    integer  $level        The nesting level.
     *                                  Should start at 0 (zero).
     * @param    integer  $selected     The optional selected ShopCategory ID.
     * @return   string                 The HTML code with all <option> tags,
     *                                  or the empty string on failure.
     */
    static function getShopCategoryMenuHierarchicRecurse(
        $parent_id, $level, $selected=0
    ) {
        $arrChildShopCategories =
            ShopCategory::getChildCategoriesById($parent_id);
        if (   !is_array($arrChildShopCategories
            || empty($arrChildShopCategories))) {
            return '';
        }
        $result = '';
        foreach ($arrChildShopCategories as $objCategory) {
            $id   = $objCategory->id();
            $name = $objCategory->name();
            $result .=
                "<option value='$id'".
                ($selected == $id ? \Html::ATTRIBUTE_SELECTED : '').
                '>'.str_repeat('.', $level*3).
                htmlentities($name).
                "</option>\n";
            if ($id != $parent_id) {
                $result .=
                    ShopCategory::getShopCategoryMenuHierarchicRecurse(
                        $id, $level+1, $selected);
            }
        }
        return $result;
    }


    /**
     * Returns the parent category ID, or 0 (zero)
     *
     * If the ID given corresponds to a top level category,
     * 0 (zero) is returned, as there is no parent.
     * If the ID cannot be found, 0 (zero) is returned as well.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @param   integer $intCategoryId  The category ID
     * @return  integer                 The parent category ID,
     *                                  or 0 (zero) on failure.
     * @static
     */
    static function getParentCategoryId($intCategoryId)
    {
        global $objDatabase;

        $query = "
            SELECT parent_id
              FROM ".DBPREFIX."module_shop_categories
             WHERE id=$intCategoryId
          ORDER BY ord ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) return 0;
        return $objResult->fields['parent_id'];
    }


    /**
     * Returns the ShopCategory ID following $shopCategoryId according to
     * the sorting order.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @param   integer $shopCategoryId     The ShopCategory ID
     * @return  integer                     The next ShopCategory ID
     * @static
     */
    static function getNextShopCategoryId($shopCategoryId=0)
    {
        // Get the parent ShopCategory ID
        $parentShopCategoryId =
            self::getParentCategoryId($shopCategoryId);
        // Get the IDs of all active children
        $arrChildShopCategoryId =
            ShopCategories::getChildCategoryIdArray($parentShopCategoryId, true);
        $index = array_search($parentShopCategoryId, $arrChildShopCategoryId);
        if (   $index === false
            || empty($arrChildShopCategoryId[$index+1])) {
            $index = -1;
        }
        return $arrChildShopCategoryId[$index+1];
    }


    /**
     * Handles any kind of database error
     * @throws  Cx\Lib\Update_DatabaseException
     * @return  boolean                 False.  Always.
     */
    static function errorHandler()
    {
// ShopCategory
        // Fix the Text and Settings table first
        \Text::errorHandler();
        ShopSettings::errorHandler();

        $default_lang_id = \FWLanguage::getDefaultLangId();
        $table_name = DBPREFIX.'module_shop_categories';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true, 'renamefrom' => 'catid'),
            'parent_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'parentid'),
            'ord' => array('type' => 'INT(5)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'catsorting'),
            'active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1', 'renamefrom' => 'catstatus'),
            'picture' => array('type' => 'VARCHAR(255)', 'default' => ''),
            'flags' => array('type' => 'VARCHAR(255)', 'default' => ''),
        );
        $table_index =  array(
            'flags' => array('fields' => 'flags', 'type' => 'FULLTEXT'),
        );
        if (\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'catname')) {
                // Migrate all ShopCategory names to the Text table first
                \Text::deleteByKey('Shop', self::TEXT_NAME);
                \Text::deleteByKey('Shop', self::TEXT_SHORT_DESCRIPTION);
                \Text::deleteByKey('Shop', self::TEXT_DESCRIPTION);
                $query = "
                    SELECT `catid`, `catname`
                      FROM `$table_name`";
                $objResult = \Cx\Lib\UpdateUtil::sql($query);
                if (!$objResult) {
                    throw new \Cx\Lib\Update_DatabaseException(
                        "Failed to query ShopCategory names");
                }
                while (!$objResult->EOF) {
                    $id = $objResult->fields['catid'];
                    $name = $objResult->fields['catname'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_NAME, $name)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate ShopCategory name '$name'");
                    }
                    $objResult->MoveNext();
                }
            }
        }
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        // Always
        return false;
    }

}
