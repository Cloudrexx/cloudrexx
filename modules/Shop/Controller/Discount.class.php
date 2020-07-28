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
 * Discount
 *
 * Optional calculation of discounts in the Shop.
 * Note: This is to be customized for individual online shops.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.ch>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Discount
 *
 * Processes many kinds of discounts - as long as you can express the
 * rules in the terms used here.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.ch>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class Discount
{
    /**
     * Text keys
     */
    const TEXT_NAME_GROUP_COUNT = 'discount_group_name';
    const TEXT_UNIT_GROUP_COUNT = 'discount_group_unit';
    const TEXT_NAME_GROUP_ARTICLE = 'discount_group_article';
    const TEXT_NAME_GROUP_CUSTOMER = 'discount_group_customer';

    /**
     * Tells for each group if different products using this discount group can
     * be summarized to apply the discount.
     * @var array
     */
    protected static $arrDiscountIsCumulative = null;

    /**
     * Array of count type discount group names
     * @var   array
     */
    protected static $arrDiscountCountName = null;

    /**
     * Array of count type discount group units
     * @var   array
     */
    protected static $arrDiscountCountRate = null;

    /**
     * Array of Customer groups
     * @var   array
     */
    protected static $arrCustomerGroup = null;

    /**
     * Array of Article groups
     * @var   array
     */
    protected static $arrArticleGroup = null;

    /**
     * Array of Article/Customer group discount rates
     * @var   array
     */
    protected static $arrDiscountRateCustomer = null;

    /**
     * Initializes all static Discount data
     * @return  boolean             True on success, false otherwise
     */
    static function init()
    {
        global $objDatabase;

        $arrSql = \Text::getSqlSnippets('`discount`.`id`',
            FRONTEND_LANG_ID, 'Shop', array(
                'name' => self::TEXT_NAME_GROUP_COUNT,
                'unit' => self::TEXT_UNIT_GROUP_COUNT));
        $query = "
            SELECT `discount`.`id`, `discount`.`cumulative`, ".$arrSql['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_discountgroup_count_name` AS `discount`
                   ".$arrSql['join']."
             ORDER BY `discount`.`id` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        self::$arrDiscountCountName = array();
        static::$arrDiscountIsCumulative = array();
        while (!$objResult->EOF) {
            $group_id = $objResult->fields['id'];
            $strName = $objResult->fields['name'];
            if (is_null($strName)) {
                $strName = \Text::getById($group_id, 'Shop',
                    self::TEXT_NAME_GROUP_COUNT)->content();
            }
            $strUnit = $objResult->fields['unit'];
            if (is_null($strUnit)) {
                $strUnit = \Text::getById($group_id, 'Shop',
                    self::TEXT_UNIT_GROUP_COUNT)->content();
            }
            self::$arrDiscountCountName[$group_id] = array(
                'name' => $strName,
                'unit' => $strUnit,
            );
            $isCumulative = (bool) $objResult->fields['cumulative'];
            static::$arrDiscountIsCumulative[$group_id] = $isCumulative;
            $objResult->MoveNext();
        }

        // Note that the ordering is significant here.
        // Some methods rely on it to find the applicable rate.
        $query = "
            SELECT `group_id`, `count`, `rate`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_discountgroup_count_rate`
             ORDER by `count` DESC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        self::$arrDiscountCountRate = array();
        while (!$objResult->EOF) {
            self::$arrDiscountCountRate[$objResult->fields['group_id']]
                [$objResult->fields['count']] = $objResult->fields['rate'];
            $objResult->MoveNext();
        }

        $arrSqlName = \Text::getSqlSnippets(
            '`discount`.`id`', FRONTEND_LANG_ID, 'Shop',
            array('customer' => self::TEXT_NAME_GROUP_CUSTOMER));
        $query = "
            SELECT `discount`.`id`, ".$arrSqlName['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_customer_group` AS `discount`
                   ".$arrSqlName['join']."
             ORDER BY `discount`.`id` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        self::$arrCustomerGroup = array();
        while (!$objResult->EOF) {
            $group_id = $objResult->fields['id'];
            $strName = $objResult->fields['customer'];
            if (is_null($strName)) {
                $strName = \Text::getById($group_id, 'Shop',
                    self::TEXT_NAME_GROUP_CUSTOMER)->content();
            }
            self::$arrCustomerGroup[$group_id] = array(
                'name' => $strName,
            );
            $objResult->MoveNext();
        }

        $arrSqlName = \Text::getSqlSnippets(
            '`discount`.`id`', FRONTEND_LANG_ID, 'Shop',
            array('article' => self::TEXT_NAME_GROUP_ARTICLE));
        $query = "
            SELECT `discount`.`id`, ".$arrSqlName['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_article_group` AS `discount`
                   ".$arrSqlName['join']."
             ORDER BY `discount`.`id` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        self::$arrArticleGroup = array();
        while (!$objResult->EOF) {
            $group_id = $objResult->fields['id'];
            $strName = $objResult->fields['article'];
            if (is_null($strName)) {
                $strName = \Text::getById($group_id, 'Shop',
                    self::TEXT_NAME_GROUP_ARTICLE)->content();
            }
            self::$arrArticleGroup[$group_id] = array(
                'name' => $strName,
            );
            $objResult->MoveNext();
        }
//DBG::log("Discount::init(): Made \$arrArticleGroup: ".var_export(self::$arrArticleGroup, true));
        $query = "
            SELECT `customer_group_id`, `article_group_id`, `rate`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_rel_discount_group`";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        self::$arrDiscountRateCustomer = array();
        while (!$objResult->EOF) {
            self::$arrDiscountRateCustomer[$objResult->fields['customer_group_id']]
                [$objResult->fields['article_group_id']] =
                    $objResult->fields['rate'];
            $objResult->MoveNext();
        }
        return true;
    }


    /**
     * Flushes all static Discount data
     * @return  void
     */
    static function flush()
    {
        self::$arrDiscountCountName = null;
        self::$arrDiscountCountRate = null;
        self::$arrCustomerGroup = null;
        self::$arrArticleGroup = null;
        self::$arrDiscountRateCustomer = null;
    }


    /**
     * Returns the HTML dropdown menu options with all of the
     * count type discount names plus a neutral option ("none")
     *
     * Backend use only.
     * @param   integer   $selectedId   The optional preselected ID
     * @return  string                  The HTML dropdown menu options
     *                                  on success, false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getMenuOptionsGroupCount($selectedId=0)
    {
        global $_ARRAYLANG;

        if (is_null(self::$arrDiscountCountName)) self::init();
        $arrName = array();
        foreach (self::$arrDiscountCountName as $group_id => $arrGroup) {
            $arrName[$group_id] = $arrGroup['name'].' ('.$arrGroup['unit'].')';
        }
        return \Html::getOptions(
            array(
                0 => $_ARRAYLANG['TXT_SHOP_DISCOUNT_GROUP_NONE']
            ) + $arrName, $selectedId);
    }


    /**
     * Returns an array with all the count type discount names
     * indexed by their ID.
     *
     * Backend use only.
     * @return  array                   The discount name array on success,
     *                                  null otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getDiscountCountArray()
    {
        if (is_null(self::$arrDiscountCountName)) self::init();
        return self::$arrDiscountCountName;
    }


    /**
     * Returns an array with all counts and rates for the count type
     * discount selected by its ID.
     *
     * Backend use only.
     * Note that on success, the array returned contains at least one entry,
     * namely that for "no discount".
     * @param   integer   $group_id     The count type discount group ID
     * @return  array                   The array with counts and rates
     *                                  on success, null otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getDiscountCountRateArray($group_id)
    {
        if (empty($group_id)) return null;
        if (is_null(self::$arrDiscountCountRate)) self::init();
        if (isset (self::$arrDiscountCountRate[$group_id])) {
            return self::$arrDiscountCountRate[$group_id];
        }
        return null;
    }

    /**
     * Tells whether a discount group is cumulative for all its products
     * @param   integer   $group_id     The discount group ID
     * @return boolean True if $group_id counts order limit accross products
     */
    public static function isDiscountCumulative($group_id) {
        if (empty($group_id)) return null;
        if (is_null(static::$arrDiscountIsCumulative)) self::init();
        return static::$arrDiscountIsCumulative[$group_id];
    }

    /**
     * Returns the number of products to calculate the discount
     * @param   integer   $group_id     The discount group ID
     * @param   integer   $count        The number of Products
     * @return int Number of relevant items
     */
    protected static function getItemCountForGroup($group_id, $count=1) {
        // if group is not cumulative:
        if (!static::isDiscountCumulative($group_id)) {
            return $count;
        }
        // find number of products in cart matching supplied group id
        // count them
        $products = Cart::get_products_array();
        $count = 0;
        foreach ($products as $productArr) {
            $product = Product::getById($productArr['id']);
            if ($product->group_id() != $group_id) {
                continue;
            }
            $count += $productArr['quantity'];
        }
        return $count;
    }

    /**
     * Determine the product discount rate for the discount group with
     * the given ID and the given count.
     *
     * Frontend use only.
     * @param   integer   $group_id     The discount group ID
     * @param   integer   $count        The number of Products
     * @return  float                   The discount rate in percent
     *                                  to be applied, if any,
     *                                  0 (zero) otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getDiscountRateCount($group_id, $count=1)
    {
        $count = static::getItemCountForGroup($group_id, $count);
        // Unknown group ID.  No discount.
        if (empty($group_id)) return 0;
        if (is_null(self::$arrDiscountCountRate)) self::init();
        // Unknown group, or no counts defined.  No discount.
        if (empty(self::$arrDiscountCountRate[$group_id])) return 0;
        // Mind that the order of the elements is significant; they must
        // be ordered by descending count.  See init().
        foreach (self::$arrDiscountCountRate[$group_id] as $count_min => $rate) {
            if ($count >= $count_min) return $rate;
        }
        // Quantity too small.  No discount.
        return 0;
    }


    /**
     * Returns the unit used for the count type discount group
     * with the given ID
     * @param   integer   $group_id   The count type discount group ID
     * @return  string                The unit used for this group on success,
     *                                the empty string otherwise
     */
    static function getUnit($group_id)
    {
        $group_id = intval($group_id);
        if (empty($group_id)) return '';
        if (is_null(self::$arrDiscountCountName)) self::init();
        return (isset(self::$arrDiscountCountName[$group_id])
            ? self::$arrDiscountCountName[$group_id]['unit']
            : '');
    }


    /**
     * Store the count type discount settings
     *
     * Backend use only.
     * @param   integer   $group_id   The ID of the discount group,
     *                                if known, or 0 (zero)
     * @param   integer   $groupType  Discount type
     * @param   string    $groupName  The group name
     * @param   string    $groupUnit  The group unit
     * @param   array     $arrCount   The array of minimum counts
     * @param   array     $arrRate    The array of discount rates,
     *                                in percent, corresponding to
     *                                the elements of the count array
     * @return  boolean               True on success, false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function storeDiscountCount(
        $group_id, $groupType, $groupName, $groupUnit, $arrCount, $arrRate
    ) {
        global $objDatabase, $_ARRAYLANG;

        if (is_null(self::$arrDiscountCountName)) self::init();
        $group_id = intval($group_id);
        $query = "
            REPLACE INTO `".DBPREFIX."module_shop".MODULE_INDEX."_discountgroup_count_name` (
                `id`,
                `cumulative`
            ) VALUES (
                " . ($group_id ? $group_id : 'NULL') . ",
                $groupType
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if (empty($group_id)) {
            $group_id = $objDatabase->Insert_Id();
        }
        if (!\Text::replace($group_id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME_GROUP_COUNT, $groupName)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_ERROR_STORING']);
        }
        if (!\Text::replace($group_id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_UNIT_GROUP_COUNT, $groupUnit)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_ERROR_STORING']);
        }
        // Remove old counts and rates
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_discountgroup_count_rate`
             WHERE `group_id`=$group_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        // Insert new counts and rates
        foreach ($arrCount as $index => $count) {
            $rate = $arrRate[$index];
            if ($count <= 0 || $rate <= 0) continue;
            $query = "
                INSERT INTO `".DBPREFIX."module_shop".MODULE_INDEX."_discountgroup_count_rate` (
                    `group_id`, `count`, `rate`
                ) VALUES (
                    $group_id, $count, $rate
                )";
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) {
                return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_ERROR_STORING']);
            }
        }
        return \Message::ok($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_STORED_SUCCESSFULLY']);
    }


    /**
     * Delete the count type discount group seleted by its ID from the database
     *
     * Backend use only.
     * @param   integer   $group_id     The discount group ID
     * @return  boolean                 True on success, false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteDiscountCount($group_id)
    {
        global $objDatabase, $_ARRAYLANG;

        if (empty($group_id)) return false;
        if (is_null(self::$arrDiscountCountName)) self::init();
        if (empty(self::$arrDiscountCountName[$group_id])) return true;
        // Remove counts and rates
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_discountgroup_count_rate`
             WHERE `group_id`=$group_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        // Remove the group
        if (!\Text::deleteById($group_id, 'Shop', self::TEXT_NAME_GROUP_COUNT)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_ERROR_DELETING']);
        }
        if (!\Text::deleteById($group_id, 'Shop', self::TEXT_UNIT_GROUP_COUNT)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_ERROR_DELETING']);
        }
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_discountgroup_count_name`
             WHERE `id`=$group_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_ERROR_DELETING']);
        }
        return \Message::ok($_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_DELETED_SUCCESSFULLY']);
    }


    /**
     * Returns the HTML dropdown menu options with all of the
     * customer group names
     *
     * Backend use only.
     * @param   integer   $selectedId   The optional preselected ID
     * @return  string                  The HTML dropdown menu options
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getMenuOptionsGroupCustomer($selectedId=0)
    {
        global $_ARRAYLANG;

        if (is_null(self::$arrCustomerGroup)) self::init();
        return \Html::getOptions(
            array(
                0 => $_ARRAYLANG['TXT_SHOP_DISCOUNT_GROUP_NONE']
            ) + self::getCustomerGroupNameArray(), $selectedId);
    }


    /**
     * Returns the HTML dropdown menu options with all of the
     * article group names, plus a null option prepended
     *
     * Backend use only.
     * @param   integer   $selectedId   The optional preselected ID
     * @return  string                  The HTML dropdown menu options
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getMenuOptionsGroupArticle($selectedId=0)
    {
        global $_ARRAYLANG;
        static $arrArticleGroupName = null;

//DBG::log("Discount::getMenuOptionsGroupArticle($selectedId): Entered");
        if (is_null(self::$arrArticleGroup)) self::init();
        if (is_null($arrArticleGroupName)) {
            $arrArticleGroupName = array();
            foreach (self::$arrArticleGroup as $id => $arrArticleGroup) {
                $arrArticleGroupName[$id] = $arrArticleGroup['name'];
//DBG::log("Discount::getMenuOptionsGroupArticle($selectedId): Adding ID $id => {$arrArticleGroup['name']}");
            }
        }
        return \Html::getOptions(
            array(0 => $_ARRAYLANG['TXT_SHOP_DISCOUNT_GROUP_NONE'], )
          + $arrArticleGroupName, $selectedId);
    }


    /**
     * Returns an array with all the customer group names
     * indexed by their ID
     *
     * Backend use only.
     * @return  array                 The group name array on success,
     *                                null otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getCustomerGroupArray()
    {
        if (is_null(self::$arrCustomerGroup)) self::init();
        return self::$arrCustomerGroup;
    }


    /**
     * Returns an array with all the article group names indexed by their ID
     *
     * Backend use only.
     * @return  array                 The group name array on success,
     *                                null otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getArticleGroupArray()
    {
        if (is_null(self::$arrArticleGroup)) self::init();
        return self::$arrArticleGroup;
    }


    /**
     * Returns an array with all the customer/article type discount rates.
     *
     * The array has the structure
     *  array(
     *    customerGroupId => array(
     *      articleGroupId => discountRate,
     *      ...
     *    ),
     *    ...
     *  );
     * @return  array                 The discount rate array on success,
     *                                null otherwise
     * @static
     */
    static function getDiscountRateCustomerArray()
    {
        if (is_null(self::$arrDiscountRateCustomer)) self::init();
        return self::$arrDiscountRateCustomer;
    }


    /**
     * Returns the customer/article type discount rate to be applied
     * for the given group IDs
     *
     * Frontend use only.
     * @param   integer   $groupCustomerId    The customer group ID
     * @param   integer   $groupArticleId     The article group ID
     * @return  float                         The discount rate, if applicable,
     *                                        0 (zero) otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getDiscountRateCustomer($groupCustomerId, $groupArticleId)
    {
        if (is_null(self::$arrDiscountRateCustomer)) self::init();
        $groupCustomerId = intval($groupCustomerId);
        $groupArticleId = intval($groupArticleId);
        if (isset(self::$arrDiscountRateCustomer[$groupCustomerId][$groupArticleId])) {
            return self::$arrDiscountRateCustomer[$groupCustomerId][$groupArticleId];
        }
        return 0;
    }


    /**
     * Returns a string with the customer group name
     * for the given ID
     *
     * Backend use only.
     * @param   integer   $group_id     The Customer group ID
     * @return  string                  The group name on success,
     *                                  the string for "none" otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getCustomerGroupName($group_id)
    {
        global $_ARRAYLANG;

        if (is_null(self::$arrCustomerGroup)) self::init();
        if (isset(self::$arrCustomerGroup[$group_id])) {
            return self::$arrCustomerGroup[$group_id]['name'];
        }
        return $_ARRAYLANG['TXT_SHOP_DISCOUNT_GROUP_NONE'];
    }


    /**
     * Returns an array with the customer group names, indexed by ID
     *
     * Backend use only.
     * Note that the array returned may be empty.
     * @return  array                   The group name array on success,
     *                                  null otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getCustomerGroupNameArray()
    {
        if (is_null(self::$arrCustomerGroup)) self::init();
        $arrGroupname = array();
        foreach (self::$arrCustomerGroup as $id => $arrGroup) {
            $arrGroupname[$id] = $arrGroup['name'];
        }
        return $arrGroupname;
    }


    /**
     * Store the customer/article group discounts in the database.
     *
     * Backend use only.
     * The array argument has the structure
     *  array(
     *    customerGroupId => array(
     *      articleGroupId => discountRate,
     *      ...
     *    ),
     *    ...
     *  );
     * @param   array     $arrDiscountRate  The array of discount rates
     * @return  boolean                     True on success, false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function storeDiscountCustomer($arrDiscountRate)
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "
            TRUNCATE TABLE `".DBPREFIX."module_shop".MODULE_INDEX."_rel_discount_group`";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_ERROR_STORING']);
        }
        foreach ($arrDiscountRate as $groupCustomerId => $arrArticleRow) {
            foreach ($arrArticleRow as $groupArticleId => $rate) {
                // No need to insert invalid and "no discount" records.
                if ($rate <= 0) continue;
                // Insert
                $query = "
                    INSERT INTO `".DBPREFIX."module_shop".MODULE_INDEX."_rel_discount_group` (
                        `customer_group_id`, `article_group_id`, `rate`
                    ) VALUES (
                        $groupCustomerId, $groupArticleId, $rate
                    )";
                $objResult = $objDatabase->Execute($query);
                if (!$objResult) {
                    return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_ERROR_STORING']);
                }
            }
        }
        return \Message::ok($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_STORED_SUCCESSFULLY']);
    }


    /**
     * Store a customer group in the database
     * @param   string    $groupName    The group name
     * @param   integer   $group_id     The optional group ID
     * @return  integer                 The (new) group ID on success,
     *                                  false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function storeCustomerGroup($groupName, $group_id=0)
    {
        global $objDatabase, $_ARRAYLANG;

        if (is_null(self::$arrCustomerGroup)) self::init();
        $group_id = intval($group_id);
        $query = "
            REPLACE INTO `".DBPREFIX."module_shop".MODULE_INDEX."_customer_group` (
                `id`
            ) VALUES (
                " . ($group_id ? $group_id : 'NULL') . "
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_GROUP_ERROR_STORING']);
        }
        if (empty($group_id)) {
            $group_id = $objDatabase->Insert_Id();
        }
        if (!\Text::replace($group_id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME_GROUP_CUSTOMER, $groupName)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_GROUP_ERROR_STORING']);
        }
        \Message::ok($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_GROUP_STORED_SUCCESSFULLY']);
        return $group_id;
    }


    /**
     * Store an article group in the database
     * @param   string    $groupName    The group name
     * @param   integer   $group_id     The optional group ID
     * @return  integer                 The (new) group ID on success,
     *                                  false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function storeArticleGroup($groupName, $group_id=0)
    {
        global $objDatabase, $_ARRAYLANG;

        if (is_null(self::$arrArticleGroup)) self::init();
        $group_id = intval($group_id);
        $query = "
            REPLACE INTO `".DBPREFIX."module_shop".MODULE_INDEX."_article_group` (
                `id`
            ) VALUES (
                " . ($group_id ? $group_id : 'NULL') . "
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_ARTICLE_GROUP_ERROR_STORING']);
        }
        if (empty($group_id)) {
            $group_id = $objDatabase->Insert_Id();
        }
        if (!\Text::replace($group_id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME_GROUP_ARTICLE, $groupName)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_ARTICLE_GROUP_ERROR_STORING']);
        }
        \Message::ok($_ARRAYLANG['TXT_SHOP_DISCOUNT_ARTICLE_GROUP_STORED_SUCCESSFULLY']);
        return $group_id;
    }


    /**
     * Delete the customer group from the database
     *
     * Backend use only.
     * @param   integer   $group_id     The group ID
     * @return  boolean                 True on success, false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteCustomerGroup($group_id)
    {
        global $objDatabase, $_ARRAYLANG;

        if (empty($group_id)) return false;
        if (is_null(self::$arrCustomerGroup)) self::init();
        if (empty(self::$arrCustomerGroup[$group_id])) return true;
        // Remove related rates
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_rel_discount_group`
             WHERE `customer_group_id`=$group_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_GROUP_ERROR_DELETING']);
        }
        // Remove the group
        if (!\Text::deleteById($group_id, 'Shop',
            self::TEXT_NAME_GROUP_CUSTOMER)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_GROUP_ERROR_DELETING']);
        }
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_customer_group`
             WHERE `id`=$group_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_GROUP_ERROR_DELETING']);
        }
        return \Message::ok($_ARRAYLANG['TXT_SHOP_DISCOUNT_CUSTOMER_GROUP_DELETED_SUCCESSFULLY']);
    }


    /**
     * Delete the article group from the database
     *
     * Backend use only.
     * @param   integer   $group_id     The group ID
     * @return  boolean                 True on success, false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteArticleGroup($group_id)
    {
        global $objDatabase, $_ARRAYLANG;

        if (empty($group_id)) return false;
        if (is_null(self::$arrArticleGroup)) self::init();
        if (empty(self::$arrArticleGroup[$group_id])) return true;
        // Remove related rates
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_rel_discount_group`
             WHERE `article_group_id`=$group_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        // Remove the group
        if (!\Text::deleteById(
            $group_id, 'Shop', self::TEXT_NAME_GROUP_ARTICLE)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_ARTICLE_GROUP_ERROR_DELETING']);
        }
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_article_group`
             WHERE `id`=$group_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DISCOUNT_ARTICLE_GROUP_ERROR_DELETING']);
        }
        return \Message::ok($_ARRAYLANG['TXT_SHOP_DISCOUNT_ARTICLE_GROUP_DELETED_SUCCESSFULLY']);
    }


    /**
     * Tries to fix any database problems
     * @return  boolean           False.  Always.
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
//die("Discount::errorHandler(): Disabled!<br />");
// Discount
        \Text::errorHandler();

        $table_name = DBPREFIX.'module_shop_article_group';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
        );
        $table_index = array();
//\DBG::activate(DBG_DB);
        if (!\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        }
        $default_lang_id = \FWLanguage::getDefaultLangId();
        if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'name')) {
            \Text::deleteByKey('Shop', self::TEXT_NAME_GROUP_ARTICLE);
            $query = "
                SELECT `id`, `name`
                  FROM `$table_name`";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            if (!$objResult) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to query article group names", $query);
            }
            while (!$objResult->EOF) {
                $group_id = $objResult->fields['id'];
                $name = $objResult->fields['name'];
                if (!\Text::replace($group_id, $default_lang_id, 'Shop',
                    self::TEXT_NAME_GROUP_ARTICLE, $name)) {
                    throw new \Cx\Lib\Update_DatabaseException(
                       "Failed to migrate article group names");
                }
                $objResult->MoveNext();
            }
            \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        }

        $table_name = DBPREFIX.'module_shop_customer_group';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
        );
        $table_index = array();
        if (!\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        }
        if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'name')) {
            \Text::deleteByKey('Shop', self::TEXT_NAME_GROUP_CUSTOMER);
            $query = "
                SELECT `id`, `name`
                  FROM `$table_name`";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            if (!$objResult) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to query customer group names", $query);
            }
            while (!$objResult->EOF) {
                $group_id = $objResult->fields['id'];
                $name = $objResult->fields['name'];
                if (!\Text::replace($group_id, $default_lang_id, 'Shop',
                    self::TEXT_NAME_GROUP_CUSTOMER, $name)) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to migrate customer group names");
                }
                $objResult->MoveNext();
            }
            \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        }

        $table_name = DBPREFIX.'module_shop_rel_discount_group';
        $table_structure = array(
            'customer_group_id' => array('type' => 'int(10)', 'unsigned' => true, 'notnull' => true, 'default' => 0, 'primary' => true),
            'article_group_id' => array('type' => 'int(10)', 'unsigned' => true, 'notnull' => true, 'default' => 0, 'primary' => true),
            'rate' => array('type' => 'decimal(9,2)', 'notnull' => true, 'default' => '0.00'),
        );
        $table_index = array();
        if (!\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        }

        $table_name = DBPREFIX.'module_shop_discountgroup_count_name';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
        );
        $table_index = array();
        if (!\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        }
        if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'name')) {
            \Text::deleteByKey('Shop', self::TEXT_NAME_GROUP_COUNT);
            \Text::deleteByKey('Shop', self::TEXT_UNIT_GROUP_COUNT);
            $query = "
                SELECT `id`, `name`, `unit`
                  FROM `$table_name`";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            if (!$objResult) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to query count group names", $query);
            }
            while (!$objResult->EOF) {
                $group_id = $objResult->fields['id'];
                $name = $objResult->fields['name'];
                $unit = $objResult->fields['unit'];
                if (!\Text::replace($group_id, $default_lang_id, 'Shop',
                    self::TEXT_NAME_GROUP_COUNT, $name)) {
                    throw new \Cx\Lib\Update_DatabaseException(
                       "Failed to migrate count group names");
                }
                if (!\Text::replace($group_id, $default_lang_id, 'Shop',
                    self::TEXT_UNIT_GROUP_COUNT, $unit)) {
                    throw new \Cx\Lib\Update_DatabaseException(
                       "Failed to migrate count group units");
                }
                $objResult->MoveNext();
            }
            \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        }

        $table_name = DBPREFIX.'module_shop_discountgroup_count_rate';
        $table_structure = array(
            'group_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => 0, 'primary' => true),
            'count' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => 1, 'primary' => true),
            'rate' => array('type' => 'DECIMAL(5,2)', 'unsigned' => true, 'notnull' => true, 'default' => '0.00'),
        );
        $table_index = array();
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        // Always
        return false;
    }

}
