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
 * Zones
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Zones
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class Zones
{
    /**
     * Text key
     */
    const TEXT_NAME = 'zone_name';

    /**
     * Zones
     * @var   array
     */
    private static $arrZone = null;
    /**
     * Zone-Country relation
     * @var   array
     */
    private static $arrRelation = array();


    /**
     * Initialises all Zones (but no relation)
     * @return  boolean           True on success, false otherwise
     * @static
     */
    static function init()
    {
        global $objDatabase;

        $arrSqlName = \Text::getSqlSnippets('`zone`.`id`',
            FRONTEND_LANG_ID, 'Shop', array('name' => self::TEXT_NAME));
        $query = "
            SELECT `zone`.`id`, `zone`.`active`, ".
                   $arrSqlName['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_zones` AS `zone`".
                   $arrSqlName['join']."
             ORDER BY `name` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        self::$arrZone = array();
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            $strName = $objResult->fields['name'];
            if ($strName === null) {
                $objText = \Text::getById($id, 'Shop', self::TEXT_NAME);
                if ($objText) $strName = $objText->content();
            }
            self::$arrZone[$id] = array(
                'id' => $id,
                'name' => $strName,
                'active' => $objResult->fields['active'],
            );
            $objResult->MoveNext();
        }
        return true;
    }


    /**
     * Returns an array of the available zones
     * @return  array                           The zones array
     * @static
     */
    static function getZoneArray()
    {
        if (is_null(self::$arrZone)) self::init();
        return self::$arrZone;
    }


    /**
     * Returns the Zone-Country relation array
     *
     * This array is of the form
     *  array(
     *    Zone ID => array(Country ID, ... more ...),
     *    ... more ...
     *  )
     * @global  ADOConnection   $objDatabase
     * @return  array                           The relation array
     * @static
     */
    static function getCountryRelationArray()
    {
        global $objDatabase;

        if (empty(static::$arrRelation)) {
//DBG::log("Zones::getCountryRelationArray(): init()ialising");
            $query = "
                SELECT zone_id, country_id
                  FROM ".DBPREFIX."module_shop".MODULE_INDEX."_rel_countries";
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) return false;
            static::$arrRelation = array();
            while (!$objResult->EOF) {
                if (!isset(static::$arrRelation[$objResult->fields['zone_id']])) {
                    static::$arrRelation[$objResult->fields['zone_id']] = array();
                }
                static::$arrRelation[$objResult->fields['zone_id']][] =
                    $objResult->fields['country_id'];
                $objResult->MoveNext();
            }
        }
        return static::$arrRelation;
    }


    /**
     * Returns the Zone ID associated with the given Payment ID, if any
     *
     * This shouldn't happen, but if the Payment isn't associated with any
     * Zone, returns null.
     * @param   integer     $payment_id     The Payment ID
     * @return  integer                     The Zone ID, if any,
     *                                      false on failure, or null if none
     * @static
     */
    static function getZoneIdByPaymentId($payment_id)
    {
        global $objDatabase;

        $query = "
            SELECT r.zone_id
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_rel_payment AS r
              JOIN ".DBPREFIX."module_shop".MODULE_INDEX."_zones AS z
                ON z.id=r.zone_id
             WHERE r.payment_id=$payment_id"; // AND z.active=1
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        if ($objResult->EOF) return null;
        return $objResult->fields['zone_id'];
    }


    /**
     * Stores all changes made to any Zone
     * @return  boolean           True on success, false on failure,
     *                            or null on noop.
     * @static
     */
    static function store_from_post()
    {
        $success = true;
        $changed = false;
        $result = self::deleteZone();
        if (isset($result)) {
            $changed = true;
            $success &= $result;
        }
        $result = self::addZone();
        if (isset($result)) {
            $changed = true;
            $success &= $result;
        }
        $result = self::updateZones();
        if (isset($result)) {
            $changed = true;
            $success &= $result;
        }
        if ($changed) {
            // Reinit after storing, or the user won't see any changes at first
            self::$arrZone = null;
            return $success;
        }
        return null;
    }


    /**
     * Delete Zone
     * @static
     */
    static function deleteZone()
    {
        global $objDatabase;

        if (empty($_GET['delete_zone_id'])) return null;
        $zone_id = $_GET['delete_zone_id'];
        if (is_null(self::$arrZone)) self::init();
        if (empty(self::$arrZone[$zone_id])) return null;
        // Delete Country relations
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_rel_countries
             WHERE zone_id=$zone_id");
        if (!$objResult) return false;
        // Update relations: Move affected Payments and Shipments to Zone "All"
        $objResult = $objDatabase->Execute("
            UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_rel_payment
               SET zone_id=1
             WHERE zone_id=$zone_id");
        if (!$objResult) return false;
        $objResult = $objDatabase->Execute("
            UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_rel_shipper
               SET zone_id=1
             WHERE zone_id=$zone_id");
        if (!$objResult) return false;
        // Delete Zone with Text
        if (!\Text::deleteById($zone_id, 'Shop', self::TEXT_NAME))
            return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_zones
             WHERE id=$zone_id");
        if (!$objResult) return false;

        return true;
    }


    /**
     * Add a new zone
     * @static
     */
    static function addZone()
    {
        global $objDatabase;

        if (empty($_POST['zone_name_new'])) return null;
        $strName = $_POST['zone_name_new'];
        $objResult = $objDatabase->Execute("
            INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_zones (
                active
            ) VALUES (
                ".(isset($_POST['zone_active_new']) ? 1 : 0)."
            )");
        if (!$objResult) return false;
        $zone_id = $objDatabase->Insert_ID();
        if (!\Text::replace($zone_id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME, $strName)) {
            return false;
        }
        if (isset($_POST['selected_countries'])) {
            foreach ($_POST['selected_countries'] as $country_id) {
                $objResult = $objDatabase->Execute("
                    INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_rel_countries (
                        zone_id, country_id
                    ) VALUES (
                        $zone_id, $country_id
                    )");
                if (!$objResult) return false;
            }
        }
        return true;
    }


    /**
     * Updates the Zones with data posted from the form
     * @return  boolean             True on success, false on failure,
     *                              null on noop
     * @static
     */
    static function updateZones()
    {
        global $objDatabase;

        if (   empty($_POST['bzones'])
            || empty($_POST['zone_list'])) return null;
        if (is_null(self::$arrZone)) self::init();
        $changed = false;
        foreach ($_POST['zone_list'] as $zone_id) {
            $name = contrexx_input2raw($_POST['zone_name'][$zone_id]);
            $active = (empty($_POST['zone_active'][$zone_id]) ? 0 : 1);
            $arrCountryId = (empty($_POST['selected_countries'][$zone_id])
                ? array() : $_POST['selected_countries'][$zone_id]);
            sort($arrCountryId);
            $arrCountryId_old = self::getCountryRelationArray();
            // reset loaded relation
            static::$arrRelation = array();
//DBG::log("Zones::updateZones(): Zones: ".var_export($arrCountryId_old, true));
            $arrCountryId_old = (empty($arrCountryId_old[$zone_id])
                ? array() : $arrCountryId_old[$zone_id]);
            sort($arrCountryId_old);
            if (   $name == self::$arrZone[$zone_id]['name']
                && $active == self::$arrZone[$zone_id]['active']
                && $arrCountryId == $arrCountryId_old) {
                continue;
            }
//DBG::log("Zones::updateZones(): Different: name $name == ".self::$arrZone[$zone_id]['name'].", active $active == ".self::$arrZone[$zone_id]['active'].", arrCountryId ".var_export($arrCountryId, true)." == ".var_export($arrCountryId_old, true));
            $objResult = $objDatabase->Execute("
                UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_zones
                   SET active=$active
                 WHERE id=$zone_id");
            if (!$objResult) return false;
            $changed = true;
            if (!\Text::replace($zone_id, FRONTEND_LANG_ID, 'Shop',
                self::TEXT_NAME, $name)) {
                return false;
            }
            $objResult = $objDatabase->Execute("
                DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_rel_countries
                 WHERE zone_id=$zone_id");
            if (!$objResult) return false;
            if (!empty($arrCountryId)) {
                foreach ($arrCountryId as $country_id) {
                    $objResult = $objDatabase->Execute("
                        INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_rel_countries (
                            zone_id, country_id
                        ) VALUES (
                            $zone_id, $country_id
                        )");
                    if (!$objResult) return false;
                }
            }
        }
        if ($changed) return true;
        return null;
    }


    /**
     * Updates the relation entry for the given Shipper and Zone ID
     * @param   integer   $zone_id      The Zone ID
     * @param   integer   $shipper_id   The Shipper ID
     * @return  boolean                 True on success, false otherwise
     * @static
     */
    static function update_shipper_relation($zone_id, $shipper_id)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            REPLACE INTO ".DBPREFIX."module_shop".MODULE_INDEX."_rel_shipper (
               `zone_id`, `shipper_id`
             ) VALUES (
               $zone_id, $shipper_id
             )");
        return (boolean)$objResult;
    }


    /**
     * Returns HTML code for the Zones dropdown menu
     *
     * Includes all Zones.  Backend use only.
     * @param   integer     $selected       The optional preselected Zone ID.
     *                                      Defaults to 0 (zero)
     * @param   string      $name           The optional name attribute value.
     *                                      Defaults to "zone_id".
     * @param   string      $onchange       The optional onchange attribute
     *                                      value.  Defaults to the empty string
     * @return  string                      The HTML dropdown menu code
     */
    static function getMenu($selected=0, $name='zone_id', $onchange='')
    {
        $arrName = self::getNameArray();
        return \Html::getSelect(
            $name, $arrName, $selected, false, $onchange);
    }


    /**
     * Returns an array of Zone names indexed by their respective IDs
     * @param   boolean     $active     Optionally limits results to Zones
     *                                  of the given active status if set.
     *                                  Defaults to null
     * @return  array                   The array of Zone names on success,
     *                                  false otherwise
     */
    static function getNameArray($active=null)
    {
        if (is_null(self::$arrZone)) self::init();
        if (is_null(self::$arrZone)) return false;
        $arrName = array();
        foreach (self::$arrZone as $zone_id => $arrZone) {
            if (isset ($active) && $active != $arrZone['active']) continue;
            $arrName[$zone_id] = $arrZone['name'];
        }
        return $arrName;
    }


    /**
     * Returns the Zone ID for the given shipper ID
     * @param   integer   $shipper_id   The shipper ID
     * @return  integer                 The Zone ID on success, null otherwise
     */
    static function getZoneIdByShipperId($shipper_id)
    {
        global $objDatabase;

        $query = "
            SELECT `relation`.`zone_id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_rel_shipper` AS `relation`
              JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_zones` AS `zone`
                ON `zone`.`id`=`relation`.`zone_id`
             WHERE `zone`.`active`=1
               AND `relation`.`shipper_id`=$shipper_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return self::errorHandler();
        }
        if ($objResult->EOF) {
            return null;
        }
        return $objResult->fields['zone_id'];
    }


    /**
     * Handles database errors
     *
     * Also migrates text fields to the new structure
     * @return  boolean           False.  Always.
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
// Zones
        // Fix the Zone-Payment relation table
        $table_name = DBPREFIX.'module_shop_rel_payment';
        $table_structure = array(
            'zone_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'primary' => true, 'renamefrom' => 'zones_id'),
            'payment_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'primary' => true),
        );
        $table_index = array();
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        // Fix the Text table
        \Text::errorHandler();

        $table_name = DBPREFIX.'module_shop_zones';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true, 'renamefrom' => 'zones_id'),
            'active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1', 'renamefrom' => 'activation_status'),
        );
        $table_index =  array();
        $default_lang_id = \FWLanguage::getDefaultLangId();
        if (\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'zones_name')) {
                // Migrate all Zone names to the Text table first
                \Text::deleteByKey('Shop', self::TEXT_NAME);
                $query = "
                    SELECT `zones_id`, `zones_name`
                      FROM `$table_name`";
                $objResult = \Cx\Lib\UpdateUtil::sql($query);
                if (!$objResult) {
                    throw new \Cx\Lib\Update_DatabaseException(
                        "Failed to query Zone names", $query);
                }
                while (!$objResult->EOF) {
                    $id = $objResult->fields['zones_id'];
                    $name = $objResult->fields['zones_name'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_NAME, $name)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Zone name '$name'");
                    }
                    $objResult->MoveNext();
                }
            }
        }
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        $table_name_old = DBPREFIX.'module_shop_rel_shipment';
        $table_name_new = DBPREFIX.'module_shop_rel_shipper';
        if (   !\Cx\Lib\UpdateUtil::table_exist($table_name_new)
            && \Cx\Lib\UpdateUtil::table_exist($table_name_old)) {
            \Cx\Lib\UpdateUtil::table_rename($table_name_old, $table_name_new);
        }
        $table_structure = array(
            'shipper_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'renamefrom' => 'shipment_id'),
            'zone_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'renamefrom' => 'zones_id'),
        );
        $table_index = array();
        \Cx\Lib\UpdateUtil::table($table_name_new, $table_structure, $table_index);

        $table_name = DBPREFIX.'module_shop_rel_countries';
        $table_structure = array(
            'country_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'renamefrom' => 'countries_id'),
            'zone_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'renamefrom' => 'zones_id'),
        );
        $table_index = array();
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        // Always
        return false;
    }

}
