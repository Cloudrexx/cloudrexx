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
 * Shipment class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Useful methods to handle everything related to shipments
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */
class Shipment
{
    /**
     * Text keys
     */
    const TEXT_NAME = 'shipper_name';

    /**
     * Array of active shippers
     * @static
     * @var     array
     * @access  private
     */
    private static $arrShippers  = null;

    /**
     * Array of active shipment conditions
     * @static
     * @var     array
     * @access  private
     */
    private static $arrShipments = null;


    /**
     * Initialize shippers and shipment conditions
     *
     * Use $all=true for the backend settings.
     * Reads the shipping options from the shipper (s) and shipment_cost (c)
     * tables.  For each shipper, creates array entries like:
     * arrShippers[s.id] = array (
     *      name => s.name,
     *      status => s.status
     * )
     * arrShipments[s.id][c.id] = array (
     *      max_weight => c.max_weight,
     *      free_from => c.free_from,
     *      fee => c.fee
     * )
     * Note that the table module_shop_shipment has been replaced by
     * module_shop_shipper (id, name, status) and
     * module_shop_shipment_cost (id, shipper_id, max_weight, fee, free_from)
     * as of version 1.1.
     * @global  ADONewConnection
     * @param   boolean   $all        If true, includes inactive records.
     *                                Defaults to false.
     * @return  void
     * @since   1.1
     */
    static function init($all=false)
    {
        global $objDatabase;

//DBG::log("Shipment::init(): language ID ".FRONTEND_LANG_ID);

        $arrSqlName = \Text::getSqlSnippets(
            '`shipper`.`id`', FRONTEND_LANG_ID, 'Shop',
            array('name' => self::TEXT_NAME));
        $objResult = $objDatabase->Execute("
            SELECT `shipper`.`id`, `shipper`.`active`, ".
            $arrSqlName['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_shipper` AS `shipper`".
            $arrSqlName['join'].
             ($all ? '' : ' WHERE `shipper`.`active`=1')."
             ORDER BY `shipper`.`id` ASC");
        if (!$objResult) return self::errorHandler();
        while (!$objResult->EOF) {
            $shipper_id = $objResult->fields['id'];
            $strName = $objResult->fields['name'];
            // Replace Text in a missing language by another, if available
            if ($strName === null) {
                $objText = \Text::getById(
                    $shipper_id, 'Shop', self::TEXT_NAME);
                if ($objText) $strName = $objText->content();
            }
            self::$arrShippers[$shipper_id] = array(
                'id' => $objResult->fields['id'],
                'name' => $strName,
                'active' => $objResult->fields['active'],
            );
            $objResult->MoveNext();
        }
        // Now get the associated shipment conditions from shipment_cost
        $objResult = $objDatabase->Execute("
            SELECT `shipment`.`id`, `shipment`.`shipper_id`,
                   `shipment`.`max_weight`, `shipment`.`fee`, `shipment`.`free_from`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_shipment_cost` AS `shipment`
             INNER JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_shipper` AS `shipper`
                ON `shipper`.`id`=`shipper_id`");
        if (!$objResult) return self::errorHandler();
        while (!$objResult->EOF) {
            self::$arrShipments[$objResult->fields['shipper_id']]
                    [$objResult->fields['id']] = array(
                'max_weight' => Weight::getWeightString($objResult->fields['max_weight']),
                'free_from' => $objResult->fields['free_from'],
                'fee' => $objResult->fields['fee'],
            );
            $objResult->MoveNext();
        }
        return true;
    }


    /**
     * Clears the Shippers and Shipments stored in the class
     *
     * Call this after updating the database.  The data will be reinitialized
     * on demand.
     */
    static function reset()
    {
        self::$arrShippers = null;
        self::$arrShipments = null;
    }


    /**
     * Returns the name of the shipper with the given ID
     * @static
     * @param   integer   $shipperId  The shipper ID
     * @return  string                The shipper name
     */
    static function getShipperName($shipperId)
    {
        if (empty($shipperId)) return '';
        if (is_null(self::$arrShippers)) self::init(true);
        if (empty(self::$arrShippers[$shipperId])) return '';
        return self::$arrShippers[$shipperId]['name'];
    }


    /**
     * Access method.  Returns the arrShippers array.
     *
     * See {@link init()}.
     * @param   boolean   $all      Include inactive Shippers if true.
     *                              Defaults to false.
     * @return  array               The array of shippers
     * @static
     */
    static function getShippersArray($all=false)
    {
        if (is_null(self::$arrShippers)) self::init($all);
        return self::$arrShippers;
    }


    /**
     * Access method.  Returns the arrShipments array.
     *
     * See {@link Shipment()}.
     * @return  array               The array of shipments
     * @static
     */
    static function getShipmentsArray()
    {
        if (is_null(self::$arrShipments)) self::init(true);
        return self::$arrShipments;
    }


    /**
     * Returns the shipment arrays (shippers and shipment costs) in JavaScript
     * syntax.
     *
     * Backend use only.
     * @static
     * @return  string              The Shipment arrays definition
     */
    static function getJSArrays()
    {
        if (is_null(self::$arrShipments)) self::init(true);
        // Set up shipment cost javascript arrays
        // Shippers are not needed for calculating the shipment costs
        //$strJsArrays = "arrShippers = new Array();\narrShipments = new Array();\n";
        $strJsArrays = "arrShipments = new Array();\n";
        // Insert shippers by id
        foreach (array_keys(self::$arrShippers) as $shipper_id) {
            //$strJsArrays .= "arrShippers[$shipper_id] = new Array('".
            //    self::$arrShippers[$shipper_id]['name']."', ".
            //    self::$arrShippers[$shipper_id]['status'].");\n";
            // Insert shipments by shipper id
            $strJsArrays .= "arrShipments[$shipper_id] = new Array();\n";
            $i = 0;
            if (isset(self::$arrShipments[$shipper_id])) {
                foreach (self::$arrShipments[$shipper_id] as $shipment_id => $arrShipment) {
                    $strJsArrays .=
                        "arrShipments[$shipper_id][".$i++."] = new Array('$shipment_id', '".
                        $arrShipment['max_weight']."', '".   // string
                        Currency::getCurrencyPrice($arrShipment['free_from'])."', '".
                        Currency::getCurrencyPrice($arrShipment['fee'])."');\n";
                }
            }
        }
        return $strJsArrays;
    }


    /**
     * Returns an array of shipper ids relevant for the country specified by
     * the argument $countryId.
     * @internal Note that s.shipment_id below now associates with shipper.id
     * @param   integer $countryId      The optional country ID
     * @return  array                   Array of shipment IDs on success,
     *                                  false otherwise
     * @static
     */
    static function getCountriesRelatedShippingIdArray($countryId=0)
    {
        global $objDatabase;

        if (empty(self::$arrShippers)) self::init();
//DBG::log("Shipment::getCountriesRelatedShippingIdArray($countryId): Shippers: ".var_export(self::$arrShippers, true));
//DBG::activate(DBG_ADODB);
        $query = "
            SELECT DISTINCT `relation`.`shipper_id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_rel_countries` AS `country`
              JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_zones` AS `zone`
                ON `country`.`zone_id`=`zone`.`id`
              JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_rel_shipper` AS `relation`
                ON `zone`.`id`=`relation`.`zone_id`
              JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_shipper` AS `shipper`
                ON `relation`.`shipper_id`=`shipper`.`id`
             WHERE `zone`.`active`=1
               AND `shipper`.`active`=1".
              ($countryId ? " AND `country`.`country_id`=$countryId" : '');
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
//DBG::deactivate(DBG_ADODB);
        $arrShipperId = array();
        while ($objResult && !$objResult->EOF) {
            $shipper = $objResult->fields['shipper_id'];
            if (isset(self::$arrShippers[$shipper])) {
                $arrShipperId[] = $shipper;
//DBG::log("Shipment::getCountriesRelatedShippingIdArray($countryId): Shipper ID $shipper OK");
            }
            $objResult->MoveNext();
        }
        return $arrShipperId;
    }


    /**
     * Returns the shipper dropdown menu string.
     *
     * For the admin zone (order edit page), you *MUST* specify $onchange,
     * so that both the onchange call and the <select> tag are added.
     *? For use in the user zone (shop, frontend), you *MUST NOT* specify the
     *? $onchange call to update the
     *?
     * The entry with ID $selectedId will have the selected attribute added,
     * if found.  If the $onchange string is specified and non-null, it will be
     * inserted into the <select> string as the onchange attribute value.
     * @param   string  $selectedId     Optional preselected shipment ID
     * @param   string  $onchange       Optional onchange javascript callback
     * @return  string                  Dropdown menu string
     * @global  array
     * @static
     */
    static function getShipperMenu($countryId=0, $selectedId=0, $onchange="")
    {
        global $_ARRAYLANG;

        if (empty(self::$arrShippers)) self::init();
        $arrId = self::getCountriesRelatedShippingIdArray($countryId);
        if (empty($arrId))
            return $_ARRAYLANG['TXT_SHOP_SHIPMENT_NONE_FOR_COUNTRY'];
/*
         // If no shipment has been chosen yet, select the first.
        if (empty($selectedId)) {
            $selectedId = current($arrId);
        }
*/
        foreach ($arrId as $key => $shipper_id) {
            // Only show suitable shipments in the menu if the user is on the payment page,
            // check the availability of the shipment in her country,
            // and verify that the shipper will be able to handle the freight.
            if (empty($_REQUEST['cmd']) || $_REQUEST['cmd'] != 'payment') {
                continue;
            }
            if (self::calculateShipmentPrice(
                    $shipper_id,
                    $_SESSION['shop']['cart']['total_price'],
                    $_SESSION['shop']['cart']['total_weight']) < 0) {
                unset($arrId[$key]);
            }
        }
        if (empty($arrId)) {
            return $_ARRAYLANG['TXT_SHOP_SHIPMENT_TOO_HEAVY'];
        }
        if (count($arrId) == 1) {
            return
                htmlentities(
                    self::$arrShippers[$shipper_id]['name'],
                    ENT_QUOTES, CONTREXX_CHARSET).
                '<input type="hidden" name="shipperId"'.
                ' value="'.current($arrId).'" />'."\n";
        }
        $menu =
// TODO: Because the value posted from the form is not currently verified,
// but simply replaced by the default (first available) shipper ID if empty
// anyway, there is no use in showing this dummy option.
//            (empty($selectedId)
//                ? '<option value="0" selected="selected">'.
//                  $_ARRAYLANG['TXT_SHOP_SHIPMENT_PLEASE_SELECT'].
//                  "</option>\n"
//                :
            ''
//            )
            ;
        foreach ($arrId as $shipper_id) {
            $menu .=
                '<option value="'.$shipper_id.'"'.
                ($shipper_id==intval($selectedId) ? ' selected="selected"' : '').
                '>'.self::$arrShippers[$shipper_id]['name']."</option>\n";
        }
        if ($onchange) {
            $menu =
                '<select name="shipperId" id="shipperId"
                onchange="'.$onchange.'">'.
                $menu.
                '</select>';
        }
        return $menu;
    }


    /**
     * Deletes a Shipper from the database
     *
     * Deletes related Text, shipment cost, and zone relation records as well.
     * @param   integer     $shipper_id     The Shipper ID
     * @return  boolean                     True on success, false on failure,
     *                                      null on noop.
     * @static
     * @access  private
     */
    private static function _delete_shipper($shipper_id)
    {
        global $objDatabase;

        if (empty(self::$arrShippers)) self::init();
        if (empty(self::$arrShippers[$shipper_id])) return null;
        if (!\Text::deleteById($shipper_id, 'Shop', self::TEXT_NAME))
            return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_shipment_cost
             WHERE shipper_id=".$shipper_id);
        if (!$objResult) return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_rel_shipper
             WHERE shipper_id=".$shipper_id);
        if (!$objResult) return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_shipper
             WHERE id=".$shipper_id);
        if (!$objResult) return false;

        return true;
    }


    /**
     * Deletes a Shipment entry from the database
     * @param   integer     $shipment_id    The Shipment ID
     * @return  boolean                     True on success, false otherwise
     * @static
     * @access  private
     */
    private static function _delete_shipment($shipment_id)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_shipment_cost`
             WHERE `id`=$shipment_id");
        return (boolean)$objResult;
    }


    /**
     * Adds a Shipper to the database
     * @param   string  $name       The Shipper name
     * @param   boolean $active     If true, the Shipper is made active.
     *                              Defaults to false
     * @return  integer             The ID of the new Shipper on success,
     *                              false otherwise
     * @static
     * @access  private
     */
    private function _add_shipper($name, $active=false)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            INSERT INTO `".DBPREFIX."module_shop".MODULE_INDEX."_shipper` (
                `active`
            ) VALUES (
                ".($active ? 1 : 0)."
            )");
        if (!$objResult) return false;
        $id = $objDatabase->Insert_ID();
        if (!\Text::replace($id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME, $name)) {
            return false;
        }
        return $id;
    }


    /**
     * Adds a Shipment entry to the database
     * @param   integer $shipper_id            The associated Shipper ID
     * @param   double  $fee            The fee for delivery
     * @param   double  $free_from      The minimum order value to get a free delivery
     * @param   integer $max_weight     The maximum weight of the delivery
     * @return  boolean                 True on success, false otherwise
     * @static
     * @access  private
     */
    private static function _add_shipment($shipper_id, $fee, $free_from, $max_weight)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            INSERT INTO `".DBPREFIX."module_shop".MODULE_INDEX."_shipment_cost` (
                `shipper_id`, `fee`, `free_from`, `max_weight`
            ) VALUES (
                $shipper_id, $fee, $free_from, $max_weight
            )");
        return (boolean)$objResult;
    }


    /**
     * Deletes the Shipper with its ID present in $_GET['delete_shipper_id'],
     * if any
     * @return  boolean                 True on success, false on failure,
     *                                  or null on noop
     */
    static function delete_shipper()
    {
        if (empty($_GET['delete_shipper_id'])) return null;
        return self::_delete_shipper(intval($_GET['delete_shipper_id']));
    }


    /**
     * Deletes the Shipment with its ID present in $_GET['delete_shipment_id'],
     * if any
     * @return  boolean                 True on success, false on failure,
     *                                  or null on noop
     * @static
     */
    static function delete_shipment()
    {
        if (empty($_GET['delete_shipment_id'])) return null;
        return self::_delete_shipment(intval($_GET['delete_shipment_id']));
    }


    /**
     * Adds a new shipper
     *
     * Backend use only.
     * @return  boolean                 True on success, false on failure,
     *                                  or null on noop
     * @static
     */
    static function add_shipper()
    {
        if (empty($_POST['bshipper_add']) || empty($_POST['name_new'])) return null;
        $shipper_id = self::_add_shipper(
            trim(strip_tags(contrexx_input2raw($_POST['name_new']))),
            !empty($_POST['active_new']),
            intval($_POST['zone_id_new']));
        if (!$shipper_id) return false;
        return Zones::update_shipper_relation(
            intval($_POST['zone_id_new']), $shipper_id);
    }


    /**
     * Adds new shipment conditions
     *
     * Backend use only.
     * @return  boolean                 True on success, false on failure,
     *                                  or null on noop
     * @static
     */
    static function add_shipments()
    {
        if (empty($_POST['bshipment'])) return null;
        $success = true;
        $changed = false;
        // check whether form fields contain valid new values
        // at least one of them must be non-zero!
        foreach ($_POST['max_weight_new'] as $shipper_id => $value) {
            if (   (isset($value) && $value > 0)
                || (   isset($_POST['fee_new'][$shipper_id])
                    && $_POST['fee_new'][$shipper_id] > 0)
                || (   isset($_POST['free_from_new'][$shipper_id])
                    && $_POST['free_from_new'][$shipper_id] > 0)
            ) {
                $changed = true;
                $success = $success && self::_add_shipment(
                    $shipper_id,
                    floatval($_POST['fee_new'][$shipper_id]),
                    floatval($_POST['free_from_new'][$shipper_id]),
                    Weight::getWeight($value)
                );
            }
        }
        if ($changed) return $success;
        return null;
    }


    /**
     * Updates shippers and shipments that have been changed in the form
     *
     * Backend use only.
     * @return  boolean                     True on success, false an failure,
     *                                      null on noop.
     * @static
     */
    static function update_shipments_from_post()
    {
        if (empty($_POST['bshipment'])) return null;
        $success = true;
        $changed = false;
        // Update all shipment conditions
        if (!empty($_POST['max_weight'])) {
            foreach ($_POST['max_weight'] as $shipment_id => $max_weight) {
                $max_weight = Weight::getWeight(contrexx_input2raw($max_weight));
                $shipper_id = intval($_POST['sid'][$shipment_id]);
                $fee = floatval($_POST['fee'][$shipment_id]);
                $free_from = floatval($_POST['free_from'][$shipment_id]);
                if (   $max_weight == Weight::getWeight(self::$arrShipments[$shipper_id][$shipment_id]['max_weight'])
                    && $free_from == self::$arrShipments[$shipper_id][$shipment_id]['free_from']
                    && $fee == self::$arrShipments[$shipper_id][$shipment_id]['fee']) {
                    continue;
                }
//DBG::log("Shipment::update_shipments_from_post(): max_weight $max_weight == ".self::$arrShipments[$shipper_id][$shipment_id]['max_weight'].", free_from $free_from == ".self::$arrShipments[$shipper_id][$shipment_id]['free_from'].", fee $fee == ".self::$arrShipments[$shipper_id][$shipment_id]['fee']);
                $changed = true;
                $success &= self::_update_shipment(
                    $shipment_id, $shipper_id, $fee, $free_from, $max_weight);
            }
        }
        foreach ($_POST['shipper_name'] as $shipper_id => $shipper_name) {
            $shipper_name = contrexx_input2raw($shipper_name);
            $active = !empty($_POST['active'][$shipper_id]);
            $zone_id = intval($_POST['zone_id'][$shipper_id]);
            $zone_id_old = Zones::getZoneIdByShipperId($shipper_id);
            if (   $shipper_name == self::$arrShippers[$shipper_id]['name']
                && $active == self::$arrShippers[$shipper_id]['active']
                && $zone_id == $zone_id_old) {
                continue;
            }
            $changed = true;
            $success &= self::_update_shipper($shipper_id, $active);
            $success &= self::_rename_shipper(
                $shipper_id, $shipper_name);
            $success &= Zones::update_shipper_relation($zone_id, $shipper_id);
        }
        if ($changed) return $success;
        return null;
    }


    /**
     * Update a Shipment entry
     * @param   integer $shipment_id    The Shipment ID
     * @param   integer $shipper_id     The associated Shipper ID
     * @param   double  $fee            The fee for delivery
     * @param   double  $free_from      The minimum order value to get a free delivery
     * @param   integer $max_weight     The maximum weight of the delivery
     * @return  boolean                 True on success, false otherwise
     * @static
     * @access  private
     */
    private static function _update_shipment($shipment_id, $shipper_id, $fee, $free_from, $max_weight)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            UPDATE `".DBPREFIX."module_shop".MODULE_INDEX."_shipment_cost`
               SET `shipper_id`=$shipper_id,
                   `fee`=$fee,
                   `free_from`=$free_from,
                   `max_weight`=$max_weight
             WHERE `id`=$shipment_id");
        return (boolean)$objResult;
    }


    /**
     * Update the Shipper active status
     * @param   integer $svalue     The ID of the Shipper
     * @param   boolean $active     If true, the Shipper is made active.
     *                              Defaults to false
     * @return  boolean             True on success, false otherwise
     * @static
     * @access  private
     */
    private static function _update_shipper($shipper_id, $active=false)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            UPDATE `".DBPREFIX."module_shop".MODULE_INDEX."_shipper`
               SET `active`=".($active ? 1 : 0)."
             WHERE `id`=$shipper_id");
        return (boolean)$objResult;
    }


    /**
     * Update the Shipper name in the current frontend language
     * @param   integer $svalue     The ID of the Shipper
     * @param   string  $name       The new Shipper name
     * @return  boolean             True on success, false otherwise
     * @static
     * @access  private
     */
    private static function _rename_shipper($shipper_id, $name)
    {
        if (empty(self::$arrShippers)) self::init();
        if (!\Text::replace($shipper_id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME, $name)) {
            return false;
        }
        return true;
    }


    /**
     * Calculate the shipment price for the given Shipper ID, order price and
     * total weight.
     *
     * Returns the shipment price in default currency, or -1 if there is any kind
     * of problem with the shipment conditions.
     * The weight is converted from string using {@link Weight::getWeight()}
     * to make sure that grams are used.
     * Note: You have to convert the returned value to the customers' currency
     * using {@link Currency::getCurrencyPrice()}!
     * @param   integer $shipperId  The Shipper ID
     * @param   double  $price      The total order price
     * @param   integer $weight     The total order weight in grams.
     * @return  double              The cost for shipping in the default
     *                              currency, or -1.
     * @static
     */
    static function calculateShipmentPrice($shipperId, $price, $weight)
    {
        if (empty(self::$arrShippers)) self::init();
        // Are there conditions available from this shipper?
        // Otherwise, don't even try to find one. return
        if (!isset(self::$arrShipments[$shipperId])) return -1;
        // check shipments available by this shipper
        $arrShipment = self::$arrShipments[$shipperId];
        // Find the best match for the current order weight and shipment cost.
        // Arbitrary upper limit - we *SHOULD* be able to find one that's lower!
        // We'll just try to find the cheapest way to handle the delivery.
        $lowest_cost = 1e100;
        // Temporary shipment cost
        $fee = 0;
        // Found flag is set to the index of a suitable shipment, if encountered below.
        // If the flag stays at -1, there is no way to deliver it!
        $found = -1;
        // Try all the available shipments
        // (see Shipment.class.php::getJSArrays())
        foreach ($arrShipment as $shipment_id => $conditions) {
            $free_from = $conditions['free_from'];
            $max_weight = Weight::getWeight($conditions['max_weight']);
            // Get the shipment conditions that are closest to our order:
            // We have to make sure the maximum weight is big enough for the order,
            // or that it's unspecified (don't care)
            if (($max_weight > 0 && $weight <= $max_weight) || $max_weight == 0) {
                // If free_from is set, the order amount has to be higher than that
                // in order to get the shipping for free.
                if ($free_from > 0 && $price >= $free_from) {
                    // We're well within the weight limit, and the order is also expensive
                    // enough to get a free shipping.
                    $fee = '0.00';
                } else {
                    // Either the order amount is too low, or free_from is unset, or zero,
                    // so the shipping has to be paid for in any case.
                    $fee = $conditions['fee'];
                }
                // We found a kind of shipment that can handle the order, but maybe
                // it's too expensive. - keep the cheapest way to deliver it
                if ($fee < $lowest_cost) {
                    // Found a cheaper one. keep the index.
                    $found = $shipment_id;
                    $lowest_cost = $fee;
                }
            }
        }
        if ($found > 0) {
            // After checking all the shipments, we found the lowest cost for the
            // given weight and order price. - update the shipping cost
            return $lowest_cost;
        }
        // Cannot find suitable shipment conditions for the selected shipper.
        return -1;
    }


    /**
     * Returns an array containing all the active shipment conditions.
     *
     * The array has the form
     *  array(
     *    Shipper name => array(
     *      'countries' => array(
     *        country ID => Country name, [...]
     *      ),
     *      'conditions' => array(
     *        Maximum weight => array(
     *          'max_weight' => maximum weight (formatted, or "unlimited"),
     *          'free_from' => no charge lower limit (amount),
     *          'fee' => shipping fee (amount),
     *        ),
     *        [... more ...]
     *      ),
     *    ),
     *    [... more ...]
     *  )
     * Countries are ordered by ascending names.
     * Conditions are ordered by ascending maximum weight.
     * @global  ADONewConnection  $objDatabase
     * @global  array   $_ARRAYLANG
     * @return  array             Countries and conditions array on success,
     *                            false otherwise
     * @static
     */
    static function getShipmentConditions()
    {
        global $objDatabase, $_ARRAYLANG;

        if (empty(self::$arrShippers)) self::init();

        // Get shippers and associated countries (via zones).
        // Make an array(shipper_name => array( array(country, ...), array(conditions) )
        // where the countries are listed as strings of their names,
        // and the conditions look like: array(max_weight, free_from, fee)

        // Return this
        $arrResult = array();
        foreach (self::$arrShippers as $shipper_id => $shipper) {
            // Get countries covered by this shipper
            $arrSqlName = \Cx\Core\Country\Controller\Country::getSqlSnippets();
            $objResult = $objDatabase->Execute("
                SELECT DISTINCT `country`.`id`,".
                       $arrSqlName['field']."
                  FROM `".DBPREFIX."module_shop".MODULE_INDEX."_shipper` AS `shipper`
                 INNER JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_rel_shipper` AS `rel_shipper`
                    ON `shipper`.`id`=`rel_shipper`.`shipper_id`
                 INNER JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_zones` AS `zone`
                    ON `rel_shipper`.`zone_id`=`zone`.`id`
                 INNER JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_rel_countries` AS `rel_country`
                    ON `zone`.`id`=`rel_country`.`zone_id`
                 INNER JOIN `".DBPREFIX."core_country` AS `country`
                    ON `rel_country`.`country_id`=`country`.`id`".
                       $arrSqlName['join']."
                 WHERE `shipper`.`id`=?
                   AND `zone`.`active`=1
                   AND `shipper`.`active`=1
                   AND `country`.`active`=1
                 ORDER BY ".$arrSqlName['alias']['name']." ASC",
                $shipper_id);
            if (!$objResult) return self::errorHandler();
            $arrCountries = array();
            while (!$objResult->EOF) {
                $country_id = $objResult->fields['id'];
                $strName = $objResult->fields['name'];
                if (is_null($strName)) {
                    $objText = \Text::getById($country_id, 'Shop', self::TEXT_NAME);
                    if ($objText) $strName = $objText->content();
                }
                $arrCountries[$country_id] = $strName;
                $objResult->MoveNext();
            }
            // Now add the conditions, and order them by weight
            $arrConditions = array();
            foreach (self::$arrShipments[$shipper_id] as $arrCond) {
                $arrConditions[$arrCond['max_weight']] = array(
                    'max_weight' => ($arrCond['max_weight'] > 0
                        ? $arrCond['max_weight']
                        : $_ARRAYLANG['TXT_SHOP_WEIGHT_UNLIMITED']
                    ),
                    'free_from' => ($arrCond['free_from'] > 0
                        ? $arrCond['free_from']
                        : '-'
                    ),
                    'fee' => ($arrCond['fee'] > 0
                        ? $arrCond['fee']
                        : $_ARRAYLANG['TXT_SHOP_COST_FREE']
                    ),
                );
            }
            krsort($arrConditions);
            $arrResult[$shipper['name']] = array(
                'countries' => $arrCountries,
                'conditions' => $arrConditions,
            );
        }
        return $arrResult;
    }


    /**
     * Get the shipper name for the ID given
     * @static
     * @global  ADONewConnection
     * @param   integer   $shipperId      The shipper ID
     * @return  mixed                     The shipper name on success,
     *                                    false otherwise
     * @since   1.2.1
     */
    static function getNameById($shipperId)
    {
        if (empty(self::$arrShippers)) self::init();
        return self::$arrShippers[$shipperId]['name'];
    }


    /**
     * Handles database errors
     *
     * Also migrates old names to the new structure
     * @return  boolean         False.  Always.
     * @static
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
// Shipment
        static $break = false;
        if ($break) {
            die("
                Shipment::errorHandler(): Recursion detected while handling an error.<br /><br />
                This should not happen.  We are very sorry for the inconvenience.<br />
                Please contact customer support: helpdesk@comvation.com");
        }
        $break = true;

//die("Shipment::errorHandler(): Disabled!<br />");

        // Fix the Zones table first
        Zones::errorHandler();

        $table_name = DBPREFIX.'module_shop_shipper';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'ord' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
            'active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'renamefrom' => 'status'),
        );
        $table_index = array();
        $default_lang_id = \FWLanguage::getDefaultLangId();
        if (\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'name')) {
                \Text::deleteByKey('Shop', self::TEXT_NAME);
                $query = "
                    SELECT `id`, `name`
                      FROM `$table_name`";
                $objResult = \Cx\Lib\UpdateUtil::sql($query);
                if (!$objResult) {
                    throw new \Cx\Lib\Update_DatabaseException(
                        "Failed to query names", $query);
                }
                while (!$objResult->EOF) {
                    $id = $objResult->fields['id'];
                    $name = $objResult->fields['name'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_NAME, $name)) {
                    throw new \Cx\Lib\Update_DatabaseException(
                        "Failed to migrate name '$name'");
                    }
                    $objResult->MoveNext();
                }
            }
        }
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        $table_name = DBPREFIX.'module_shop_shipment_cost';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'shipper_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
            'max_weight' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'fee' => array('type' => 'DECIMAL(9,2)', 'unsigned' => true, 'notnull' => false, 'default' => null, 'renamefrom' => 'cost'),
            'free_from' => array('type' => 'DECIMAL(9,2)', 'unsigned' => true, 'notnull' => false, 'default' => null, 'renamefrom' => 'price_free'),
        );
        $table_index = array();
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        // Always!
        return false;
    }

}
