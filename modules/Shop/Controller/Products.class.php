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
    /**
     * Text keys
     */
    const TEXT_NAME  = 'product_name';
    const TEXT_SHORT = 'product_short';
    const TEXT_LONG  = 'product_long';
    const TEXT_CODE  = 'product_code';
    const TEXT_URI   = 'product_uri';
    const TEXT_KEYS  = 'product_keys';

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
                'name' => Products::TEXT_NAME,
                'code' => Products::TEXT_CODE,
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
            || $flagSpecialoffer === \Cx\Modules\Shop\Controller\ProductController::DEFAULT_VIEW_LASTFIVE) {
            // Select last five (or so) products added to the database
// TODO: Extend for searching for most recently modified Products
            $limit = ($flagLastFive === true ? 5 : $flagLastFive);
            $queryOrder = ' ORDER BY `id` DESC';
            $queryCount = "SELECT $limit AS `numof_products`";
        } else {
            // Build standard full featured query
            $querySpecialOffer =
                (   $flagSpecialoffer === \Cx\Modules\Shop\Controller\ProductController::DEFAULT_VIEW_DISCOUNTS
                 || $flagSpecialoffer === true // Old behavior!
                  ? ' AND `product`.`discount_active`=1'
                  : ($flagSpecialoffer === \Cx\Modules\Shop\Controller\ProductController::DEFAULT_VIEW_MARKED
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
                        'short' => Products::TEXT_SHORT,
                        'long' => Products::TEXT_LONG,
                        'keys' => Products::TEXT_KEYS,
                        'uri' => Products::TEXT_URI,
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
     * Handles database errors
     *
     * Also migrates text fields to the new structure
     * @return  boolean         False.  Always.
     * @static
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
// Product
        // Fix the Text, Discount, and Manufacturer tables first
        \Text::errorHandler();
//        Discount::errorHandler(); // Called by Customer::errorHandler();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $manufacturer = $cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Manufacturer'
        );
        $manufacturer->errorHandler();

        $table_name = DBPREFIX.'module_shop_products';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true),
            'normalprice' => array('type' => 'DECIMAL(9,2)', 'default' => '0.00'),
            'resellerprice' => array('type' => 'DECIMAL(9,2)', 'default' => '0.00'),
            'discountprice' => array('type' => 'DECIMAL(9,2)', 'default' => '0.00'),
            'discount_active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'is_special_offer'),
            'stock' => array('type' => 'INT(10)', 'default' => '10'),
            'stock_visible' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1', 'renamefrom' => 'stock_visibility'),
            'active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1', 'renamefrom' => 'status'),
            'b2b' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1'),
            'b2c' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1'),
            'date_start' => array('type' => 'TIMESTAMP', 'default' => '0000-00-00 00:00:00', 'renamefrom' => 'startdate'),
            'date_end' => array('type' => 'TIMESTAMP', 'default' => '0000-00-00 00:00:00', 'renamefrom' => 'enddate'),
            'weight' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'vat_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'manufacturer_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null, 'renamefrom' => 'manufacturer'),
            'group_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'article_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'ord' => array('type' => 'INT(10)', 'default' => '0', 'renamefrom' => 'sort_order'),
            'distribution' => array('type' => 'VARCHAR(16)', 'default' => '', 'renamefrom' => 'handler'),
            'picture' => array('type' => 'VARCHAR(4096)', 'notnull' => false, 'default' => null),
            'flags' => array('type' => 'VARCHAR(4096)', 'notnull' => false, 'default' => null),
            'minimum_order_quantity' => array('type' => 'INT(10)', 'unsigned' => false, 'default' => '0'),
// Obsolete:
//`property1` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
//`property2` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
//`manufacturer_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        );
        $table_index =  array(
            'group_id' => array('fields' => array('group_id')),
            'article_id' => array('fields' => array('article_id')),
            'flags' => array('fields' => array('flags'), 'type' => 'FULLTEXT', ),
        );
        $default_lang_id = \FWLanguage::getDefaultLangId();
        if (\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'title')) {
                // Migrate all Product strings to the Text table first
                \Text::deleteByKey('Shop', self::TEXT_NAME);
                \Text::deleteByKey('Shop', self::TEXT_SHORT);
                \Text::deleteByKey('Shop', self::TEXT_LONG);
                \Text::deleteByKey('Shop', self::TEXT_CODE);
                \Text::deleteByKey('Shop', self::TEXT_URI);
                \Text::deleteByKey('Shop', self::TEXT_KEYS);
                $query = "
                    SELECT `id`, `title`, `shortdesc`, `description`,
                           `product_id`, `external_link`, `keywords`
                      FROM `$table_name`";
                $objResult = \Cx\Lib\UpdateUtil::sql($query);
                if (!$objResult) {
                    throw new \Cx\Lib\Update_DatabaseException(
                        "Failed to query Product strings", $query);
                }
                while (!$objResult->EOF) {
                    $id = $objResult->fields['id'];
                    $name = $objResult->fields['title'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_NAME, $name)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product name '$name'");
                    }
                    $short = $objResult->fields['shortdesc'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_SHORT, $short)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product short '$short'");
                    }
                    $long = $objResult->fields['description'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_LONG, $long)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product long '$long'");
                    }
                    $code = $objResult->fields['product_id'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_CODE, $code)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product code '$code'");
                    }
                    $uri = $objResult->fields['external_link'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_URI, $uri)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product uri '$uri'");
                    }
                    $keys = $objResult->fields['keywords'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_KEYS, $keys)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product keys '$keys'");
                    }
                    $objResult->MoveNext();
                }
            }
        }
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        // Also fix Customer and some related tables
        Customer::errorHandler();

        // Always
        return false;
    }
}
