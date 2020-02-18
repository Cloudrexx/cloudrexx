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
 * Products helper class
 *
 * @package     cloudrexx
 * @subpackage  module_shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Product helper object
 *
 * Provides methods for accessing sets of Products, displaying menus
 * and the like.
 * @package     cloudrexx
 * @subpackage  module_shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 */
class Products
{
    const DEFAULT_VIEW_NONE = 0;
    const DEFAULT_VIEW_MARKED = 1;
    const DEFAULT_VIEW_DISCOUNTS = 2;
    const DEFAULT_VIEW_LASTFIVE = 3;
    const DEFAULT_VIEW_COUNT = 4;

    /**
     * Sorting order strings according to the corresponding setting
     *
     * Order 1: By order field value ascending, ID descending
     * Order 2: By name ascending, Product ID ascending
     * Order 3: By Product ID ascending, name ascending
     * @var     array
     * @see     Products::getByShopParam()
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    public static $arrProductOrder = array(
        1 => '`product`.`ord` ASC, `id` DESC',
        2 => '`name` ASC, `code` ASC',
        3 => '`code` ASC, `name` ASC',
    );

    /**
     * Returns an array of Product objects sharing the same Product code.
     * @param   string      $customId   The Product code
     * @return  mixed                   The array of matching Product objects
     *                                  on success, false otherwise.
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getByCustomId($customId)
    {
        global $objDatabase;

        if (empty($customId)) return false;
        $query = "
            SELECT `id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_products`
             WHERE `product_id`='$customId'
             ORDER BY `id` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrProduct = array();
        while (!$objResult->EOF) {
            $arrProduct[] = Product::getById($objResult->Fields('id'));
            $objResult->MoveNext();
        }
        return $arrProduct;
    }


    /**
     * Returns an array of Products selected by parameters as available in
     * the Shop.
     *
     * The $count parameter is set to the number of records found.
     * After it returns, it contains the actual number of matching Products.
     * @param   integer     $count          The desired number of Products,
     *                                      by reference.  Set to the actual
     *                                      total matching number.
     * @param   integer     $offset         The Product offset
     * @param   integer|array   $product_id     The optional Product ID,
     *                                      or an array of such
     * @param   integer     $category_id    The ShopCategory ID
     * @param   integer     $manufacturer_id  The Manufacturer ID
     * @param   string      $pattern        A search pattern
     * @param   boolean     $flagSpecialoffer Limit results to special offers
     *                                      if true.  Disabled if either
     *                                      the Product ID, Category ID,
     *                                      Manufacturer ID, or the search
     *                                      pattern is non-empty.
     * @param   boolean     $flagLastFive   Limit results to the last five
     *                                      Products added to the Shop if true.
     *                                      Note: You may specify an integer
     *                                      count as well, this will set the
     *                                      limit accordingly.
     * @param   integer     $orderSetting   The sorting order setting, defaults
     *                                      to the order field value ascending,
     *                                      Product ID descending
     * @param   boolean     $flagIsReseller The reseller status of the
     *                                      current customer, ignored if
     *                                      it's the empty string
     * @param   boolean     $flagShowInactive   Include inactive Products
     *                                      if true.  Backend use only!
     * @return  array                       Array of Product objects,
     *                                      or false if none were found
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getByShopParams(
        &$count, $offset=0,
        $product_id=null, $category_id=null,
        $manufacturer_id=null, $pattern=null,
        $flagSpecialoffer=false, $flagLastFive=false,
        $orderSetting='',
        $flagIsReseller=null,
        $flagShowInactive=false
    ) {
        global $objDatabase, $_CONFIG;

//\DBG::activate(DBG_ADODB_ERROR|DBG_LOG_FIREPHP);

        // Do not show any Products if no selection is made at all
        if (   empty($product_id)
            && empty($category_id)
            && empty($manufacturer_id)
            && empty($pattern)
            && empty($flagSpecialoffer)
            && empty($flagLastFive)
            && empty($flagShowInactive) // Backend only!
        ) {
            $count = 0;
            return array();
        }
// NOTE:
// This was an optimization, but does not (yet) consider the other parameters.
//        if ($product_id) {
//            // Select single Product by ID
//            $objProduct = Product::getById($product_id);
//            // Inactive Products MUST NOT be shown in the frontend
//            if (   $objProduct
//                && ($flagShowInactive || $objProduct->active())) {
//                $count = 1;
//                return array($objProduct);
//            }
//            $count = 0;
//            return false;
//        }
        list($querySelect, $queryCount, $queryTail, $queryOrder) =
            self::getQueryParts(
                $product_id, $category_id, $manufacturer_id, $pattern,
                $flagSpecialoffer, $flagLastFive, $orderSetting,
                $flagIsReseller, $flagShowInactive);
        $limit = ($count > 0
            ? $count
            : (!empty($_CONFIG['corePagingLimit'])
                ? $_CONFIG['corePagingLimit'] : 10));
        $count = 0;
//\DBG::activate(DBG_ADODB);
        $objResult = $objDatabase->SelectLimit(
            $querySelect.$queryTail.$queryOrder, $limit, $offset);
        if (!$objResult) return Product::errorHandler();
//\DBG::deactivate(DBG_ADODB);
        $arrProduct = array();
        while (!$objResult->EOF) {
            $product_id = $objResult->fields['id'];
            $objProduct = Product::getById($product_id);
            if ($objProduct)
                $arrProduct[$product_id] = $objProduct;
            $objResult->MoveNext();
        }
        $objResult = $objDatabase->Execute($queryCount.$queryTail);
        if (!$objResult) return false;
        $count = $objResult->fields['numof_products'];
//\DBG::log("Products::getByShopParams(): Set count to $count");
        return $arrProduct;
    }


    /**
     * Builds and returns a database query for Products
     * @param   integer|array   $product_id         The optional Product ID,
     *                                              an array of such, or null
     * @param   integer|string  $category_id        The optional Category ID,
     *                                              or comma separated list
     * @param   integer         $manufacturer_id    The optional manufacturer ID
     * @param   string          $pattern            The optional search term
     * @param   integer         $flagSpecialoffer   The optional special offer
     *                                              flag
     * @param   boolean         $flagLastFive       The optional "last five"
     *                                              flag
     * @param   integer|string  $orderSetting       The optional order setting,
     *                                              or SQL string
     * @param   boolean         $flagIsReseller     The optional reseller flag
     * @param   boolean         $flagShowInactive   The optional inactive flag
     * @return  string                              The SQL query
     */
    public static function getQueryParts(
        $product_id=null, $category_id=null,
        $manufacturer_id=null, $pattern=null,
        $flagSpecialoffer=false, $flagLastFive=false,
        $orderSetting='',
        $flagIsReseller=null,
        $flagShowInactive=false)
    {
        // The name and code fields may be used for sorting.
        // Include them in the field list in order to introduce the alias
        $arrSql = \Text::getSqlSnippets(
            '`product`.`id`', FRONTEND_LANG_ID, 'Shop',
            array(
                'name' => Product::TEXT_NAME,
                'code' => Product::TEXT_CODE,
            )
        );
        $querySelect = "
            SELECT `product`.`id`, ".$arrSql['field'];
        $queryCount = "SELECT COUNT(*) AS `numof_products`";
        $queryJoin = '
            FROM `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_products` AS `product`
            LEFT JOIN `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_rel_category_product` AS `category_product`
              ON `category_product`.`product_id`=`product`.`id` 
            LEFT JOIN `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_categories` AS `category` 
              ON `category_product`.`category_id`=`category`.`id` '.
            $arrSql['join'];
        $queryWhere = ' WHERE 1'.
            // Limit Products to available and active in the frontend
            ($flagShowInactive
                ? ''
                : ' AND `product`.`active`=1
                    AND (`product`.`stock_visible`=0 OR `product`.`stock`>0)
                    '.($category_id ? '' : 'AND `category`.`active`=1' )/*only check if active when not in category view*/.'
                    AND (
                        `product`.`date_start` <= CURRENT_DATE()
                     OR `product`.`date_start` IS NULL
                    )
                    AND (
                        `product`.`date_end` >= CURRENT_DATE()
                     OR `product`.`date_end` IS NULL
                    )'
            ).
            // Limit Products visible to resellers or non-resellers
            ($flagIsReseller === true
              ? ' AND `b2b`=1'
              : ($flagIsReseller === false ? ' AND `b2c`=1' : ''));

//\DBG::log("Products::getByShopParams(): Ordersetting in: $orderSetting");
        // Legacy order presets: Use as default
        if (   is_numeric($orderSetting)
            && isset(self::$arrProductOrder[$orderSetting]))
            $orderSetting = self::$arrProductOrder[$orderSetting];
//\DBG::log("Products::getByShopParams(): Ordersetting #1: $orderSetting");
        if (empty($orderSetting))
            $orderSetting = self::$arrProductOrder[1];
//\DBG::log("Products::getByShopParams(): Ordersetting #2: $orderSetting");
        // Iff the order is specified as the nonexistent "price" field, substitute
        // that by the correct price field:
        //  - "discountprice" if "discount_active" != 0,
        //  - "resellerprice" if $flagIsReseller != 0, or
        //  - "normalprice" otherwise
        $match = null;
        // Note:  The regex /\\b<field name>(?:\\W+(asc|desc))?\\b/i handles
        // optional backticks and/or whitespace around the field name (\W).
        if (preg_match('/\\bprice(?i:\\W+(asc|desc))?\\b/',
                $orderSetting, $match)) {
            $orderField = ($flagIsReseller ? 'resellerprice' : 'normalprice');
            $orderSetting =
                'IF(`discount_active`=0,`'.$orderField.'`,`discountprice`)'.
                (isset($match[1]) ? ' '.$match[1] : '');
        }
//\DBG::log("Products::getByShopParams(): Ordersetting #3: $orderSetting");
        // The use of the Sorting class does not (yet) support ordering by
        // multiple fields.  In order to keep the ordering stable for
        // identical values, the unique criterion (id DESC) is added.
        $queryOrder = ' ORDER BY '.$orderSetting. ', `id` DESC';
//\DBG::log("Products::getByShopParams(): Ordersetting #4: $orderSetting");
        // Apply ordering by "bestseller".
        // Note that this overrides the previous order, and adds GROUP BY.
        // The additional alias "bestseller" queries the number of units sold.
        if (preg_match('/\\bbestseller\\b/',
                $orderSetting, $match)) {
            $querySelect .=
                ', COUNT(`item`.`id`) AS `bestseller`';
            $queryJoin .=
                ' LEFT JOIN `'.DBPREFIX.'module_shop_order_items` AS `item`
                    ON `product`.`id`=`item`.`product_id`';
            $queryOrder = '
                GROUP BY `product`.`id`
                ORDER BY '.$orderSetting.', `id` DESC';
        }
//\DBG::log("Products::getByShopParams(): Ordersetting #5: $orderSetting");

        $querySpecialOffer = '';
        if (   $flagLastFive
            || $flagSpecialoffer === self::DEFAULT_VIEW_LASTFIVE) {
            // Select last five (or so) products added to the database
// TODO: Extend for searching for most recently modified Products
            $limit = ($flagLastFive === true ? 5 : $flagLastFive);
            $queryOrder = ' ORDER BY `id` DESC';
            $queryCount = "SELECT $limit AS `numof_products`";
        } else {
            // Build standard full featured query
            $querySpecialOffer =
                (   $flagSpecialoffer === self::DEFAULT_VIEW_DISCOUNTS
                 || $flagSpecialoffer === true // Old behavior!
                  ? ' AND `product`.`discount_active`=1'
                  : ($flagSpecialoffer === self::DEFAULT_VIEW_MARKED
                      ? " AND `product`.`flags` LIKE '%__SHOWONSTARTPAGE__%'" : '')
                );
            // Limit by Product ID (unused by getByShopParameters()!)
            if (is_array($product_id)) {
                $queryWhere .=
                    ' AND `product`.`id` IN('.join(',', $product_id).')';
            } elseif ($product_id > 0) {
                $queryWhere .=
                    ' AND `product`.`id`='.$product_id;
            }
            // Limit Products by Manufacturer ID, if any
            if ($manufacturer_id > 0) {
                $queryJoin .= '
                    INNER JOIN `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_manufacturer` AS `m`
                       ON `m`.`id`=`product`.`manufacturer_id`';
                $queryWhere .= ' AND `product`.`manufacturer_id`='.$manufacturer_id;
            }
            // Limit Products by ShopCategory ID or IDs, if any
            // (Pricelists use comma separated values, for example)
            if ($category_id) {
                $queryCategories = '`category_product`.`category_id` IN ('
                    .(is_array($category_id) ? implode(',',$category_id) : $category_id).')';
                $queryWhere .= ' AND ('.$queryCategories.')';
            }
            // Limit Products by search pattern, if any
            if ($pattern != '') {
                $arrSqlPattern = \Text::getSqlSnippets(
                    '`product`.`id`', FRONTEND_LANG_ID, 'Shop',
                    array(
                        'short' => Product::TEXT_SHORT,
                        'long' => Product::TEXT_LONG,
                        'keys' => Product::TEXT_KEYS,
                        'uri' => Product::TEXT_URI,
                    )
                );
                $pattern = contrexx_raw2db($pattern);
// TODO: This is prolly somewhat slow.  Could we use an "index" of sorts?
                $querySelect .=
                    ', '.$arrSqlPattern['field'].
                    ', MATCH ('.$arrSql['alias']['name'].')'.
                    " AGAINST ('%$pattern%') AS `score1`".
                    ', MATCH ('.$arrSqlPattern['alias']['short'].')'.
                    " AGAINST ('%$pattern%') AS `score2`".
                    ', MATCH ('.$arrSqlPattern['alias']['long'].')'.
                    " AGAINST ('%$pattern%') AS `score3`";
                $queryJoin .= $arrSqlPattern['join'];
                $queryWhere .= "
                    AND (   `product`.`id` LIKE '%$pattern%'
                         OR ".$arrSql['alias']['name']." LIKE '%$pattern%'
                         OR ".$arrSql['alias']['code']." LIKE '%$pattern%'
                         OR ".$arrSqlPattern['alias']['long']." LIKE '%$pattern%'
                         OR ".$arrSqlPattern['alias']['short']." LIKE '%$pattern%'
                         OR ".$arrSqlPattern['alias']['keys']." LIKE '%$pattern%')";
            }
        }
//\DBG::log("querySelect $querySelect");//\DBG::log("queryCount $queryCount");\DBG::log("queryJoin $queryJoin");\DBG::log("queryWhere $queryWhere");//\DBG::log("querySpecialOffer $querySpecialOffer");\DBG::log("queryOrder $queryOrder");
        $queryTail = $queryJoin.$queryWhere.$querySpecialOffer;
        return array($querySelect, $queryCount, $queryTail, $queryOrder);
    }


    /**
     * Delete Products from the ShopCategory given by its ID.
     *
     * If deleting one of the Products fails, aborts and returns false
     * immediately without trying to delete the remaining Products.
     * Deleting the ShopCategory after this method failed will most
     * likely result in Product bodies in the database!
     * @param   integer     $category_id        The ShopCategory ID
     * @param   boolean     $flagDeleteImages   Delete images, if true
     * @param   boolean     $recursive          Delete Products from
     *                                          subcategories, if true
     * @return  boolean                         True on success, null on noop,
     *                                          false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteByShopCategory($category_id, $flagDeleteImages=false,
        $recursive=false)
    {
        // Verify that the Category still exists
        $objShopCategory = ShopCategory::getById($category_id);
        if (!$objShopCategory) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Info: Category ID $category_id does not exist");
            return null;
        }

        $arrProductId = Products::getIdArrayByShopCategory($category_id);
        if (!is_array($arrProductId)) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to get Product IDs in that Category");
            return false;
        }
        // Look whether this is within a virtual ShopCategory
        $virtualContainer = '';
        $parent_id = $category_id;
        do {
            $objShopCategory = ShopCategory::getById($parent_id);
            if (!$objShopCategory) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to get parent Category");
                return false;
            }
            if ($objShopCategory->virtual()) {
                // The name of any virtual ShopCategory is used to mark
                // Products within
                $virtualContainer = $objShopCategory->name();
                break;
            }
            $parent_id = $objShopCategory->parent_id();
        } while ($parent_id != 0);

        // Remove the Products in one way or another
        foreach ($arrProductId as $product_id) {
            $objProduct = Product::getById($product_id);
            if (!$objProduct) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to get Product IDs $product_id");
                return false;
            }
            if ($virtualContainer != ''
             && $objProduct->flags() != '') {
                // Virtual ShopCategories and their content depends on
                // the Product objects' flags.
                foreach ($arrProductId as $objProduct) {
                    $objProduct->removeFlag($virtualContainer);
                    if (!Products::changeFlagsByProductCode(
                        $objProduct->code(),
                        $objProduct->flags()
                    )) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to update Product flags for ID ".$objProduct->id());
                        return false;
                    }
                }
            } else {
                // Normal, non-virtual ShopCategory.
                // Remove Products having the same Product code.
                // Don't delete Products having more than one Category assigned.
                // Instead, remove them from the chosen Category only.
                $arrCategoryId = array_flip(
                    preg_split('/\s*,\s*/',
                        $objProduct->category_id(), null,
                        PREG_SPLIT_NO_EMPTY));
                if (count($arrCategoryId) > 1) {
                    unset($arrCategoryId[$category_id]);
                    $objProduct->category_id(
                        join(',', array_keys($arrCategoryId)));
                    if (!$objProduct->store()) {
                        return false;
                    }
                } else {
//                    if (!Products::deleteByCode(
//                        $objProduct->getCode(),
//                        $flagDeleteImages)
//                    ) return false;
                    if (!$objProduct->delete()) return false;
                }
            }
        }
        if ($recursive) {
            $arrCategoryId =
                ShopCategories::getChildCategoriesById($category_id);
            foreach ($arrCategoryId as $category_id) {
                if (!self::deleteByShopCategory(
                    $category_id, $flagDeleteImages, $recursive)) {
\DBG::log("ERROR: Failed to delete Products in Category ID $category_id");
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Delete Products bearing the given Product code from the database.
     * @param   integer     $productCode        The Product code. This *MUST*
     *                                          be non-empty!
     * @param   boolean     $flagDeleteImages   If true, Product images are
     *                                          deleted as well
     * @return  boolean                         True on success, false otherwise
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteByCode($productCode, $flagDeleteImages)
    {
        if (empty($productCode)) return false;
        $arrProduct = Products::getByCustomId($productCode);
        if ($arrProduct === false) return false;
        $result = true;
        foreach ($arrProduct as $objProduct) {
            if (!$objProduct->delete($flagDeleteImages)) $result = false;
        }
        return $result;
    }


    /**
     * Set the active status of all Products for the given IDs
     *
     * Depending on $active, activates (true) or deactivates (false) the
     * Products.
     * If no valid ID is present in $arrId, returns null.
     * @param   array     $arrId    The array of Product IDs
     * @param   boolean   $active   The desired active status
     * @return  boolean             True on success, null on no operation,
     *                              false otherwise
     */
    static function set_active($arrId, $active)
    {
        global $_ARRAYLANG;

        if (empty($arrId) || !is_array($arrId)) return null;
        $success = true;
        foreach ($arrId as $product_id) {
            $objProduct = Product::getById($product_id);
            if (!$objProduct) {
                $success = false;
                continue;
            }
            $objProduct->active($active);
            if (!$objProduct->store()) {
                $success = false;
            }
        }
        if ($success) {
            return \Message::ok(
                $_ARRAYLANG['TXT_SHOP_PRODUCTS_'.
                    ($active ? '' : 'DE').'ACTIVATED']);
        }
        return \Message::error(
                $_ARRAYLANG['TXT_SHOP_PRODUCTS_ERROR_'.
                    ($active ? '' : 'DE').'ACTIVATING']);
    }


    /**
     * Returns an array of Product IDs contained by the given
     * ShopCategory ID.
     *
     * Orders the array by ascending ordinal field value
     * @param   integer   $category_id  The ShopCategory ID
     * @return  mixed                   The array of Product IDs on success,
     *                                  false otherwise.
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getIdArrayByShopCategory($category_id)
    {
        global $objDatabase;

        $category_id = intval($category_id);
        $query = "
            SELECT `product_id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_rel_category_product`
             WHERE  `category_id` = $category_id
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrProductId = array();
        while (!$objResult->EOF) {
            $arrProductId[] = $objResult->fields['product_id'];
            $objResult->MoveNext();
        }
        return $arrProductId;
    }


    /**
     * Returns the first matching picture name found in the Products
     * within the Shop Category given by its ID.
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @param type $category_id
     * @return  string                      The image name, or the
     *                                      empty string.
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getPictureByCategoryId($category_id)
    {
        global $objDatabase;

        $category_id = intval($category_id);
        $query = "
            SELECT `picture`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_products` AS `p`
              LEFT JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_rel_category_product` 
              AS `cp` ON `cp`.`product_id` = `p`.`id`
             WHERE FIND_IN_SET($category_id, `cp`.`category_id`)
               AND `picture`!=''
             ORDER BY `ord` ASC";
        $objResult = $objDatabase->SelectLimit($query, 1);
        if ($objResult && $objResult->RecordCount() > 0) {
            // Got a picture
            $arrImages = Products::get_image_array_from_base64(
                $objResult->fields['picture']);
            $imageName = $arrImages[1]['img'];
            return $imageName;
        }
        // No picture found here
        return '';
    }


    /**
     * Returns an array of ShopCategory IDs containing Products with
     * their flags containing the given string.
     * @param   string  $strName    The name of the flag to match
     * @return  mixed               The array of ShopCategory IDs on success,
     *                              false otherwise.
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getShopCategoryIdArrayByFlag($strName)
    {
        global $objDatabase;

        $query = "
            SELECT DISTINCT `cp`.`category_id`
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_products AS `p`
              LEFT JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_rel_category_product` 
              AS `cp` ON `cp`.`product_id` = `p`.`id`
             WHERE flags LIKE '%$strName%'
          ORDER BY category_id ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrShopCategoryId = array();
        while (!$objResult->EOF) {
            $arrCategoryId = preg_split('/\s*,\s*/',
                $objResult->Fields['catid'], null, PREG_SPLIT_NO_EMPTY);
            foreach ($arrCategoryId as $category_id) {
                $arrShopCategoryId[$category_id] = null;
            }
            $objResult->MoveNext();
        }
        return array_flip($arrShopCategoryId);
    }


    /**
     * Create thumbnails and update the corresponding Product records
     *
     * Scans the Products with the given IDs.  If a non-empty picture string
     * with a reasonable extension is encountered, determines whether
     * the corresponding thumbnail is available and up to date or not.
     * If not, tries to load the file and to create a thumbnail.
     * If it succeeds, it also updates the picture field with the base64
     * encoded entry containing the image width and height.
     * Note that only single file names are supported!
     * Also note that this method returns a string with information about
     * problems that were encountered.
     * It skips records which contain no or invalid image
     * names, thumbnails that cannot be created, and records which refuse
     * to be updated!
     * The reasoning behind this is that this method is currently only called
     * from within some {@link _import()} methods.  The focus lies on importing
     * Products; whether or not thumbnails can be created is secondary, as the
     * process can be repeated if there is a problem.
     * @param   integer     $arrId      The array of Product IDs
     * @return  boolean                 True on success, false on any error
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @global  array
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function makeThumbnailsById($arrId)
    {
        global $_ARRAYLANG;

        if (!is_array($arrId)) return false;
        $error = false;
        $objImageManager = new \ImageManager();
        foreach ($arrId as $product_id) {
            if ($product_id <= 0) {
                \Message::error(sprintf($_ARRAYLANG['TXT_SHOP_INVALID_PRODUCT_ID'], $product_id));
                $error = true;
                continue;
            }
            $objProduct = Product::getById($product_id);
            if (!$objProduct) {
                \Message::error(sprintf($_ARRAYLANG['TXT_SHOP_INVALID_PRODUCT_ID'], $product_id));
                $error = true;
                continue;
            }
            $imageName = $objProduct->pictures();
            $imagePath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopPath() . '/' . $imageName;
            // only try to create thumbs from entries that contain a
            // plain text file name (i.e. from an import)
            if (   $imageName == ''
                || !preg_match('/\.(?:jpg|jpeg|gif|png)$/i', $imageName)) {
                \Message::error(sprintf(
                        $_ARRAYLANG['TXT_SHOP_UNSUPPORTED_IMAGE_FORMAT'],
                        $product_id, $imageName
                    ));
                $error = true;
                continue;
            }
            // if the picture is missing, skip it.
            if (!file_exists($imagePath)) {
                \Message::error(sprintf(
                    $_ARRAYLANG['TXT_SHOP_MISSING_PRODUCT_IMAGE'],
                    $product_id, $imageName));
                $error = true;
                continue;
            }
            $thumbResult = true;
            $width  = 0;
            $height = 0;
            // If the thumbnail exists and is newer than the picture,
            // don't create it again.
            $thumb_name = \ImageManager::getThumbnailFilename($imagePath);
            if (   file_exists($thumb_name)
                && filemtime($thumb_name) > filemtime($imagePath)) {
                //$this->addMessage("Hinweis: Thumbnail fuer Produkt ID '$product_id' existiert bereits");
                // Need the original size to update the record, though
                list($width, $height) =
                    $objImageManager->_getImageSize($imagePath);
            } else {
                // Create thumbnail, get the original size.
                // Deleting the old thumb beforehand is integrated into
                // _createThumbWhq().
                $thumbResult = $objImageManager->_createThumbWhq(
                    \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopPath() . '/',
                    \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath() . '/',
                    $imageName,
                    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_width','Shop'),
                    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_height','Shop'),
                    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_quality','Shop')
                );
                $width  = $objImageManager->orgImageWidth;
                $height = $objImageManager->orgImageHeight;
            }
            // The database needs to be updated, however, as all Products
            // have been imported.
            if ($thumbResult) {
                $shopPicture =
                    base64_encode($imageName).
                    '?'.base64_encode($width).
                    '?'.base64_encode($height).
                    ':??:??';
                $objProduct->pictures($shopPicture);
                $objProduct->store();
            } else {
                \Message::error(sprintf(
                    $_ARRAYLANG['TXT_SHOP_ERROR_CREATING_PRODUCT_THUMBNAIL'],
                    $product_id, $imageName));
                $error = true;
            }
        }
        return $error;
    }


    /**
     * Apply the flags to all Products matching the given Product code
     *
     * Any Product and ShopCategory carrying one or more of the names
     * of any ShopCategory marked as "__VIRTUAL__" is cloned and added
     * to that category.  Those having any such flags removed are deleted
     * from the respective category.  Identical copies of the same Products
     * are recognized by their "product_id" (the Product code).
     *
     * Note that in this current version, only the flags of Products are
     * tested and applied.  Products are cloned and added together with
     * their immediate parent ShopCategories (aka "Article").
     *
     * Thus, all Products within the same "Article" ShopCategory carry the
     * same flags, as does the containing ShopCategory itself.
     * @param   integer     $productCode  The Product code (*NOT* the ID).
     *                                    This must be non-empty!
     * @param   string      $strNewFlags  The new flags for the Product
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function changeFlagsByProductCode($productCode, $strNewFlags)
    {
        if (empty($productCode)) return false;
        // Get all available flags.  These are represented by the names
        // of virtual root ShopCategories.
        $arrVirtual = ShopCategories::getVirtualCategoryNameArray();

        // Get the affected identical Products
        $arrProduct = Products::getByCustomId($productCode);
        // No way we can do anything useful without them.
        if (count($arrProduct) == 0) return false;

        // Get the Product flags.  As they're all the same, we'll use the
        // first one here.
        // Note that this object is used for reference only and is never stored.
        // Its database entry will be updated along the way, however.
        $_objProduct = $arrProduct[0];
        $strOldFlags = $_objProduct->getFlags();
        // Flag indicating whether the article has been cloned already
        // for all new flags set.
        $flagCloned = false;

        // Now apply the changes to all those identical Products, their parent
        // ShopCategories, and all sibling Products within them.
        foreach ($arrProduct as $objProduct) {
            // Get the containing article ShopCategory.
            $category_id = $objProduct->category_id();
            $objArticleCategory = ShopCategory::getById($category_id);
            if (!$objArticleCategory) continue;

            // Get parent (subgroup)
            $objSubGroupCategory =
                ShopCategory::getById($objArticleCategory->parent_id());
            // This should not happen!
            if (!$objSubGroupCategory) continue;
            $subgroupName = $objSubGroupCategory->name();

            // Get grandparent (group, root ShopCategory)
            $objRootCategory =
                ShopCategory::getById($objSubGroupCategory->parent_id());
            if (!$objRootCategory) continue;

            // Apply the new flags to all Products and Article ShopCategories.
            // Update the flags of the original Article ShopCategory first
            $objArticleCategory->flags($strNewFlags);
            $objArticleCategory->store();

            // Get all sibling Products affected by the same flags
            $arrSiblingProducts = Products::getByShopCategory(
                $objArticleCategory->id()
            );

            // Set the new flag set for all Products within the Article
            // ShopCategory.
            foreach ($arrSiblingProducts as $objProduct) {
                $objProduct->flags($strNewFlags);
                $objProduct->store();
            }

            // Check whether this group is affected by the changes.
            // If its name matches one of the flags, the Article and subgroup
            // may have to be removed.
            $strFlag = $objRootCategory->name();
            if (preg_match("/$strFlag/", $strNewFlags))
                // The flag is still there, don't bother.
                continue;

            // Also check whether this is a virtual root ShopCategory.
            if (in_array($strFlag, $arrVirtual)) {
                // It is one of the virtual roots, and the flag is missing.
                // So the Article has to be removed from this group.
                $objArticleCategory->delete();
                $objArticleCategory = false;
                // And if the subgroup happens to contain no more
                // "Article", delete it as well.
                $arrChildren = $objSubGroupCategory->getChildrenIdArray();
                if (count($arrChildren) == 0)
                    $objSubGroupCategory->delete();
                continue;
            }

            // Here, the virtual ShopCategory groups have been processed,
            // the only ones left are the "normal" ShopCategories.
            // Clone one of the Article ShopCategories for each of the
            // new flags set.
            // Already did that?
            if ($flagCloned) continue;

            // Find out what flags have been added.
            foreach ($arrVirtual as $strFlag) {
                // That flag is not present in the new flag set.
                if (!preg_match("/$strFlag/", $strNewFlags)) continue;
                // But it has been before.  The respective branch has
                // been truncated above already.
                if (preg_match("/$strFlag/", $strOldFlags)) continue;

                // That is a new flag for which we have to clone the Article.
                // Get the affected grandparent (group, root ShopCategory)
                $objTargetRootCategory =
                    ShopCategories::getChildNamed($strFlag, 0, false);
                if (!$objTargetRootCategory) continue;
                // Check whether the subgroup exists already
                $objTargetSubGroupCategory =
                    ShopCategories::getChildNamed(
                        $subgroupName, $objTargetRootCategory->id(), false);
                if (!$objTargetSubGroupCategory) {
                    // Nope, add the subgroup.
                    $objSubGroupCategory->makeClone();
                    $objSubGroupCategory->parent_id($objTargetRootCategory->id());
                    $objSubGroupCategory->store();
                    $objTargetSubGroupCategory = $objSubGroupCategory;
                }

                // Check whether the Article ShopCategory exists already
                $objTargetArticleCategory =
                    ShopCategories::getChildNamed(
                        $objArticleCategory->name(),
                        $objTargetSubGroupCategory->id(),
                        false
                    );
                if ($objTargetArticleCategory) {
                    // The Article Category already exists.
                } else {
                    // Nope, clone the "Article" ShopCategory and add it to the
                    // subgroup.  Note that the flags have been set already
                    // and don't need to be changed again here.
                    // Also note that the cloning process includes all content
                    // of the Article ShopCategory, but the flags will remain
                    // unchanged. That's why the flags have already been
                    // changed right at the beginning of the process.
                    $objArticleCategory->makeClone(true, true);
                    $objArticleCategory->parent_id($objTargetSubGroupCategory->id());
                    $objArticleCategory->store();
                    $objTargetArticleCategory = $objArticleCategory;
                }
            } // foreach $arrVirtual
        } // foreach $arrProduct
        // And we're done!
        return true;
    }


    /**
     * Returns an array of image names, widths and heights from
     * the base64 encoded string taken from the database
     *
     * The array returned looks like
     *  array(
     *    1 => array(
     *      'img' => <image1>,
     *      'width' => <image1.width>,
     *      'height' => <image1.height>
     *    ),
     *    2 => array( ... ), // The same as above, three times in total
     *    3 => array( ... ),
     * )
     * @param   string  $base64Str  The base64 encoded image string
     * @return  array               The decoded image array
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function get_image_array_from_base64($base64Str)
    {
        // Pre-init array to avoid "undefined index" notices
        $arrPictures = array(
            1 => array('img' => '', 'width' => 0, 'height' => 0),
            2 => array('img' => '', 'width' => 0, 'height' => 0),
            3 => array('img' => '', 'width' => 0, 'height' => 0)
        );
        if (strpos($base64Str, ':') === false)
            // have to return an array with the desired number of elements
            // and an empty file name in order to show the "dummy" picture(s)
            return $arrPictures;
        $i = 0;
        foreach (explode(':', $base64Str) as $imageData) {
            $shopImage = $shopImage_width = $shopImage_height = null;
            list($shopImage, $shopImage_width, $shopImage_height) = explode('?', $imageData);
            $shopImage        = base64_decode($shopImage);
            $shopImage_width  = base64_decode($shopImage_width);
            $shopImage_height = base64_decode($shopImage_height);
            $arrPictures[++$i] = array(
                'img' => $shopImage,
                'width' => $shopImage_width,
                'height' => $shopImage_height,
            );
        }
        return $arrPictures;
    }


    /**
     * Returns HTML code for dropdown menu options to choose the default
     * view on the Shop starting page.
     *
     * Possible choices are defined by global constants
     * self::DEFAULT_VIEW_* and corresponding language variables.
     * @static
     * @param   integer   $selected     The optional preselected view index
     * @return  string                  The HTML menu options
     */
    static function getDefaultViewMenuoptions($selected='')
    {
        global $_ARRAYLANG;

        $strMenuoptions = '';
        for ($i = 0; $i < self::DEFAULT_VIEW_COUNT; ++$i) {
            $strMenuoptions .=
                "<option value='$i'".
                ($selected == $i ? ' selected="selected"' : '').'>'.
                $_ARRAYLANG['TXT_SHOP_PRODUCT_DEFAULT_VIEW_'.$i].
                "</option>\n";
        }
        return $strMenuoptions;
    }


    static function getJavascriptArray($groupCustomerId=0, $isReseller=false)
    {
        global $objDatabase;

        // create javascript array containing all products;
        // used to update the display when changing the product ID.
        // we need the VAT rate in there as well in order to be able to correctly change the products,
        // and the flag indicating whether the VAT is included in the prices already.
        $strJsArrProduct =
            'var vat_included = '.intval(Vat::isIncluded()).
            ";\nvar arrProducts = new Array();\n";
        $arrSql = \Text::getSqlSnippets('`product`.`id`', FRONTEND_LANG_ID,
            'Shop', array(
                'name' => Product::TEXT_NAME,
                'code' => Product::TEXT_CODE,
            )
        );
        $query = "
            SELECT `product`.`id`,
                   `product`.`resellerprice`, `product`.`normalprice`,
                   `product`.`discountprice`, `product`.`discount_active`,
                   `product`.`weight`, `product`.`vat_id`,
                   `product`.`distribution`,
                   `product`.`group_id`, `product`.`article_id`, ".
                   $arrSql['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_products` AS `product`".
                   $arrSql['join']."
             WHERE `product`.`active`=1";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return Product::errorHandler();
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            $distribution = $objResult->fields['distribution'];
            $strCode = $objResult->fields['code'];
            if ($strCode === null) {
                $strCode = \Text::getById(
                    $id, 'Shop', Product::TEXT_CODE)->content();
            }
            $strName = $objResult->fields['name'];
            if ($strName === null) {
                $strName = \Text::getById(
                    $id, 'Shop', Product::TEXT_NAME)->content();
            }
            $price = $objResult->fields['normalprice'];
            if ($objResult->fields['discount_active']) {
                $price = $objResult->fields['discountprice'];
            } elseif ($isReseller) {
                $price = $objResult->fields['resellerprice'];
            }
            // Determine discounted price from customer and article group matrix
            $discountCustomerRate = Discount::getDiscountRateCustomer(
                $groupCustomerId, $objResult->fields['article_id']
            );
            $price -= $price * $discountCustomerRate * 0.01;
            // Determine prices for various count discounts, if any
            $arrDiscountCountRate = Discount::getDiscountCountRateArray(
                $objResult->fields['group_id']);
//\DBG::log("Products::getJavascriptArray($groupCustomerId, $isReseller): Discount rate array: ".var_export($arrDiscountCountRate, true));
            // Order the counts in reverse, from highest to lowest
            $strJsArrPrice = '';
            if (is_array($arrDiscountCountRate)) {
                foreach ($arrDiscountCountRate as $count => $rate) {
                    // Deduct the customer type discount right away
//\DBG::log("Products::getJavascriptArray(): price $price, rate $rate");
                    $discountPrice = $price - ($price * $rate * 0.01);
                    $strJsArrPrice .=
                        ($strJsArrPrice ? ',' : '').
                        // Count followed by price
                        $count.','.\Cx\Modules\Shop\Controller\CurrencyController::getCurrencyPrice($discountPrice);
                }
            }
            $strJsArrPrice .=
                ($strJsArrPrice ? ',' : '').
                '0,'.\Cx\Modules\Shop\Controller\CurrencyController::getCurrencyPrice($price);
            $strJsArrProduct .=
                'arrProducts['.$id.'] = {'.
                'id:'.$id.','.
                'code:"'.$strCode.'",'.
                'title:"'.htmlspecialchars($strName, ENT_QUOTES, CONTREXX_CHARSET).'",'.
                'percent:'.
                    // Use the VAT rate, not the ID, as it is not modified here
                    Vat::getRate($objResult->fields['vat_id']).','.
                'weight:'.($distribution == 'delivery'
                    ? '"'.Weight::getWeightString($objResult->fields['weight']).'"'
                    : '0' ).','.
//                'group_id:'.intval($objResult->fields['group_id']).','.
//                'article_id:'.intval($objResult->fields['article_id']).','.
                'price:['.$strJsArrPrice."]};\n";
            $objResult->MoveNext();
        }
        return $strJsArrProduct;
    }


    /**
     * Returns a string with HTML options for any menu
     *
     * Includes Products with the given active status only if $active is
     * not null.  The options' values are the Product IDs.
     * The sprintf() format for the options defaults to "%2$s", possible
     * values are:
     *  - %1$u: The Product ID
     *  - %2$s: The Product name
     * @static
     * @param   integer   $selected     The optional preselected Product ID
     * @param   boolean   $active       Optional.  Include active (true) or
     *                                  inactive (false) Products only.
     *                                  Ignored if null.  Defaults to null
     * @param   string    $format       The optional sprintf() format
     * @param   boolean   $showAllOptions Show all options and not only the selected
     * @return  array                   The HTML options string on success,
     *                                  null otherwise
     * @global  ADONewConnection
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getMenuoptions($selected=null, $active=null, $format='%2$s', $showAllOptions = true)
    {
        global $_ARRAYLANG;

        $arrName =
            array(0 => $_ARRAYLANG['TXT_SHOP_PRODUCT_NONE']) +
            self::getNameArray($active, $format);
        if ($arrName === false) return null;

        if ($selected && !$showAllOptions) {
            $arrName = array();
            $product = Product::getById($selected);
            if ($product) {
                $arrName[$product->id()] = $product->name();
            }
        }
        return \Html::getOptions($arrName, $selected);
    }


    /**
     * Returns the HTML dropdown menu options for the product sorting
     * order menu
     * @return    string            The HTML code string
     * @author    Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function getProductSortingMenuoptions()
    {
        global $_ARRAYLANG;

        $arrAvailableOrder = array(
            1 => $_ARRAYLANG['TXT_SHOP_PRODUCT_SORTING_INDIVIDUAL'],
            2 => $_ARRAYLANG['TXT_SHOP_PRODUCT_SORTING_ALPHABETIC'],
            3 => $_ARRAYLANG['TXT_SHOP_PRODUCT_SORTING_PRODUCTCODE'],
        );
        return \Html::getOptions($arrAvailableOrder,
            \Cx\Core\Setting\Controller\Setting::getValue('product_sorting','Shop'));
    }


    /**
     * Returns an array of Product names from the database
     *
     * The array is indexed by the Product ID and ordered by the names
     * and ID, ascending.
     * The names array is kept in this method statically between calls.
     * @static
     * @param   boolean   $activeonly   Optional.  Include active (true) or
     *                                  inactive (false) Products only.
     *                                  Ignored if null.  Defaults to null
     * @param   string    $format       The optional sprintf() format
     * @return  array                   The array of Product names
     *                                  on success, false otherwise
     * @global  ADONewConnection
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getNameArray($activeonly=false, $format='%2$s')
    {
        global $objDatabase;

        $arrSqlName = \Text::getSqlSnippets(
            '`product`.`id`', FRONTEND_LANG_ID, 'Shop',
            array('name' => Product::TEXT_NAME));
        $query = "
            SELECT `product`.`id`, ".
                   $arrSqlName['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_products` AS `product`".
                   $arrSqlName['join'].
            (isset($activeonly)
              ? ' WHERE `product`.`active`='.($activeonly ? 1 : 0) : '')."
             ORDER BY `name` ASC, `product`.`id` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return Product::errorHandler();
        $arrName = array();
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            $strName = $objResult->fields['name'];
            if ($strName === null) {
                $objText = \Text::getById($id, 'Shop', Product::TEXT_NAME);
                if ($objText) $strName = $objText->content();
            }
            $arrName[$objResult->fields['id']] =
                sprintf($format, $id, $strName);
            $objResult->MoveNext();
        }
//\DBG::log("Products::getNameArray(): Made ".var_export($arrName, true));
        return $arrName;
    }

    /**
     * Returns -1, 0, or 1 if the first Product title is smaller, equal to,
     * or greater than the second, respectively
     * @param   Product   $objProduct1    Product #1
     * @param   Product   $objProduct2    Product #2
     * @return  integer                   -1, 0, or 1
     */
    static function cmpTitle($objProduct1, $objProduct2)
    {
        return
            ($objProduct1->getName() == $objProduct2->getName()
              ? 0
              : ($objProduct1->getName() < $objProduct2->getName()
                  ? -1 :  1));
    }


    /**
     * Returns -1, 0, or 1 if the first Product title is smaller, equal to,
     * or greater than the second, respectively
     * @param   Product   $objProduct1    Product #1
     * @param   Product   $objProduct2    Product #2
     * @return  integer                   -1, 0, or 1
     */
    static function cmpPrice($objProduct1, $objProduct2)
    {
        return
            ($objProduct1->getPrice() == $objProduct2->getPrice()
              ? 0
              : ($objProduct1->getPrice() < $objProduct2->getPrice()
                  ? -1
                  :  1));
    }

}
