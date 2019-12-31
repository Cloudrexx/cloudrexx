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
 * Shop Product Attribute class
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Test!
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Product Attribute
 *
 * These may be associated with zero or more Products.
 * Each attribute consists of a name part
 * (module_shop_attribute) and zero or more value parts (module_shop_option).
 * Each of the values can be associated with an arbitrary number of Products
 * by inserting the respective record into the relations table
 * module_shop_products_attributes.
 * The type determines the kind of relation between a Product and the attribute
 * values, that is, whether it is optional or mandatory, and whether single
 * or multiple attributes may be chosen at a time.  See {@link ?} for details.
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @todo        Test!
 */
class Attribute
{
    /**
     * Text keys
     */
    const TEXT_ATTRIBUTE_NAME = 'attribute_name';
    const TEXT_OPTION_NAME = 'option_name';

    /**
     * Attribute type constants
     *
     * Note that you need to update methods like
     * Attributes::getDisplayTypeMenu() when you add another
     * type here.
     */
    const TYPE_MENU_OPTIONAL          =  0;
    const TYPE_RADIOBUTTON            =  1;
    const TYPE_CHECKBOX               =  2;
    const TYPE_MENU_MANDATORY         =  3;
    const TYPE_TEXT_OPTIONAL          =  4;
    const TYPE_TEXT_MANDATORY         =  5;
    const TYPE_UPLOAD_OPTIONAL        =  6;
    const TYPE_UPLOAD_MANDATORY       =  7;
    const TYPE_TEXTAREA_OPTIONAL      =  8;
    const TYPE_TEXTAREA_MANDATORY     =  9;
    const TYPE_EMAIL_OPTIONAL         = 10;
    const TYPE_EMAIL_MANDATORY        = 11;
    const TYPE_URL_OPTIONAL           = 12;
    const TYPE_URL_MANDATORY          = 13;
    const TYPE_DATE_OPTIONAL          = 14;
    const TYPE_DATE_MANDATORY         = 15;
    const TYPE_NUMBER_INT_OPTIONAL    = 16;
    const TYPE_NUMBER_INT_MANDATORY   = 17;
    const TYPE_NUMBER_FLOAT_OPTIONAL  = 18;
    const TYPE_NUMBER_FLOAT_MANDATORY = 19;
    // Keep this up to date!
    const TYPE_COUNT                  = 20;

    /**
     * The available Attribute types
     *
     * Listed in the dropdown menu in this order.
     * Note that only types listed in this array are available for selecting
     * in the shop backend!
     * Format is
     *  array(
     *    Type => Language entry postfix,
     *    ... more ...
     *  )
     * @var     array
     */
    static $arrType = array(
        self::TYPE_MENU_OPTIONAL => 'TYPE_MENU_OPTIONAL',
        self::TYPE_RADIOBUTTON => 'TYPE_RADIOBUTTON',
        self::TYPE_CHECKBOX => 'TYPE_CHECKBOX',
        self::TYPE_MENU_MANDATORY => 'TYPE_MENU_MANDATORY',
        self::TYPE_TEXT_OPTIONAL => 'TYPE_TEXT_OPTIONAL',
        self::TYPE_TEXT_MANDATORY => 'TYPE_TEXT_MANDATORY',
// TODO: Disabled FTTB.  Feature for 3.1.0
// TODO: Include upload path with corresponding backend setting
// TODO: Set form type for multipart
        self::TYPE_UPLOAD_OPTIONAL => 'TYPE_UPLOAD_OPTIONAL',
        self::TYPE_UPLOAD_MANDATORY => 'TYPE_UPLOAD_MANDATORY',
        self::TYPE_TEXTAREA_OPTIONAL => 'TYPE_TEXTAREA_OPTIONAL',
        self::TYPE_TEXTAREA_MANDATORY => 'TYPE_TEXTAREA_MANDATORY',
        self::TYPE_EMAIL_OPTIONAL => 'TYPE_EMAIL_OPTIONAL',
        self::TYPE_EMAIL_MANDATORY => 'TYPE_EMAIL_MANDATORY',
        self::TYPE_URL_OPTIONAL => 'TYPE_URL_OPTIONAL',
        self::TYPE_URL_MANDATORY => 'TYPE_URL_MANDATORY',
        self::TYPE_DATE_OPTIONAL => 'TYPE_DATE_OPTIONAL',
        self::TYPE_DATE_MANDATORY => 'TYPE_DATE_MANDATORY',
        self::TYPE_NUMBER_INT_OPTIONAL => 'TYPE_NUMBER_INT_OPTIONAL',
        self::TYPE_NUMBER_INT_MANDATORY => 'TYPE_NUMBER_INT_MANDATORY',
        self::TYPE_NUMBER_FLOAT_OPTIONAL => 'TYPE_NUMBER_FLOAT_OPTIONAL',
        self::TYPE_NUMBER_FLOAT_MANDATORY => 'TYPE_NUMBER_FLOAT_MANDATORY',
    );

    /**
     * The Attribute ID
     * @var integer
     */
    private $id = 0;
    /**
     * The associated Product ID, if any, or false
     * @var   mixed
     */
    private $product_id = false;
    /**
     * The Attribute name
     * @var string
     */
    private $name = '';
    /**
     * The Attribute type
     * @var integer
     */
    private $type = 0;
    /**
     * The array of Options
     * @var array
     */
    private $arrValues = false;
    /**
     * The array of Product Attribute relations
     * @var array;
     */
    private $arrRelation = false;
    /**
     * Sorting order
     *
     * Only used by our friend, the Product class
     * @var integer
     */
    private $order;


    /**
     * Constructor
     * @param   integer   $type         The type of the Attribute
     * @param   integer   $id           The optional Attribute ID
     * @param   integer   $product_id   The optional Product ID
     */
    function __construct($name, $type, $id=0, $product_id=false)
    {
        $this->name = $name;
        $this->setType($type);
        $this->id = $id;
        $this->product_id = $product_id;
        if ($id)
            $this->arrValues =
                Attributes::getOptionArrayByAttributeId($id);
        if ($product_id)
            $this->arrRelation = Attributes::getRelationArray($product_id);
    }


    /**
     * Get the name
     * @return  string                              The name
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getName()
    {
        return $this->name;
    }
    /**
     * Set the Attribute name
     *
     * Empty name arguments are ignored.
     * @param   string    $name              The Attribute name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function setName($name)
    {
        if (!$name) return;
        $this->name = trim(strip_tags($name));
    }

    /**
     * Get the Attribute type
     * @return  integer                 The Attribute type
     */
    function getType()
    {
        return $this->type;
    }
    /**
     * Set the Attribute type
     * @param   integer                 The Attribute type
     */
    function setType($type)
    {
        if (   $type >= self::TYPE_MENU_OPTIONAL
            && $type <  self::TYPE_COUNT) {
            $this->type = intval($type);
        }
    }

    /**
     * Get the Attribute ID
     * @return  integer                 The Attribute ID
     */
    function getId()
    {
        return $this->id;
    }
    /**
     * Set the Attribute ID -- NOT ALLOWED
     */

    /**
     * Get the Attribute sorting order
     *
     * Note that this is *SHOULD* only be set by our friend,
     * the Product object.
     * So if you have a Attribute not actually associated to
     * a Product, you *SHOULD* always get a return value of boolean false.
     * @return  integer                 The Attribute sorting order,
     *                                  or false if not applicable.
     */
    function getOrder()
    {
        return (isset($this->order) ? $this->order : false);
    }
    /**
     * Set the Attribute sorting order.
     *
     * Note that you can only set this to a valid integer value,
     * not reset to false or even unset state.
     * This *SHOULD* only be set if the Attribute is indeed associated
     * with a Product, as this value will only be stored in the
     * relations table module_shop_products_attributes.
     * @param   integer                 The Attribute sorting order
     */
    function setOrder($order)
    {
        if (is_integer($order)) $this->order = intval($order);
    }

    /**
     * Returns an array of values for this Attribute.
     *
     * If the array has not been initialized, the method tries to
     * do so from the database.
     * The array has the form
     *  array(
     *    option ID => array(
     *      'id' => option ID,
     *      'attribute_id' => Attribute ID,
     *      'value' => value name,
     *      'price' => price,
     *    ),
     *    ... more ...
     *  );
     * For relations to the associated Product, if any, see
     * {@link getRelationArray}.
     * @access  public
     * @return  array                       Array of Options
     *                                      upon success, false otherwise.
     * @global  ADONewConnection
     */
    function getOptionArray()
    {
        if (!is_array($this->arrValues))
            $this->arrValues = Attributes::getOptionArrayByAttributeId($this->id);
        return $this->arrValues;
    }
    /**
     * Set the option array -- NOT ALLOWED
     * Use addOption()/deleteValueById() instead.
     */


    /**
     * Add an option
     *
     * The values' ID is set when the record is stored.
     * @param   string  $value      The value description
     * @param   float   $price      The value price
     * @param   integer $order      The value order, only applicable when
     *                              associated with a Product
     * @return  boolean             True on success, false otherwise
     */
    function addOption($value, $price, $order=0)
    {
        if (   $this->type == self::TYPE_UPLOAD_OPTIONAL
            || $this->type == self::TYPE_UPLOAD_MANDATORY
            || $this->type == self::TYPE_TEXT_OPTIONAL
            || $this->type == self::TYPE_TEXT_MANDATORY
            || $this->type == self::TYPE_TEXTAREA_OPTIONAL
            || $this->type == self::TYPE_TEXTAREA_MANDATORY
            || $this->type == self::TYPE_EMAIL_OPTIONAL
            || $this->type == self::TYPE_EMAIL_MANDATORY
            || $this->type == self::TYPE_URL_OPTIONAL
            || $this->type == self::TYPE_URL_MANDATORY
            || $this->type == self::TYPE_DATE_OPTIONAL
            || $this->type == self::TYPE_DATE_MANDATORY
            || $this->type == self::TYPE_NUMBER_INT_OPTIONAL
            || $this->type == self::TYPE_NUMBER_INT_MANDATORY
            || $this->type == self::TYPE_NUMBER_FLOAT_OPTIONAL
            || $this->type == self::TYPE_NUMBER_FLOAT_MANDATORY
        ) {
            // These types can have exactly one value
            $this->arrValues = array(
                array(
                    'value' => $value,
                    'price' => $price,
                    'order' => $order,
                )
            );
            return true;
        }
        // Any other types can have an arbitrary number of values
        $this->arrValues[] = array(
            'value' => $value,
            'price' => $price,
            'order' => $order,
        );
        return true;
    }


    /**
     * Update an option in this object
     *
     * The option is only stored together with the object in {@link store()}
     * @param   integer   $option_id  The option ID
     * @param   string    $value      The descriptive name
     * @param   float     $price      The price
     * @param   integer   $order      The order of the value, only applicable
     *                                when associated with a Product
     * @return  boolean               True on success, false otherwise
     */
    function changeValue($option_id, $value, $price, $order=0)
    {
        $this->arrValues[$option_id]['value'] = $value;
        $this->arrValues[$option_id]['price'] = $price;
        $this->arrValues[$option_id]['order'] = $order;
        // Insert into database, and update ID
        //return $this->updateValue($this->arrValues[$option_id]);
    }


    /**
     * Remove the option with the given ID from this Attribute
     * @param   integer     $option_id      The option ID
     * @return  boolean                     True on success, false otherwise
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function deleteValueById($option_id)
    {
        global $objDatabase;

        // Anything to be removed?
        if (empty($this->arrValues[$option_id])) return true;
        // Remove relations to Products
        $query = "
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_rel_product_attribute
             WHERE option_id=$option_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        // Remove Text records
        if (!\Text::deleteById($option_id, 'Shop', self::TEXT_OPTION_NAME))
            return false;
        // Remove the value
        $query = "
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_option
             WHERE id=$option_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        unset($this->arrValues[$option_id]);
        return true;
    }


    /**
     * Deletes the Attribute from the database.
     *
     * Includes both the name and all of the value entries related to it.
     * As a consequence, all relations to Products referring to the deleted
     * entries are deleted, too.  See {@link Product::arrAttribute(sp?)}.
     * Keep in mind that any Products currently held in memory may cause
     * inconsistencies!
     * @return  boolean                     True on success, false otherwise.
     * @global  ADONewConnection  $objDatabase    Database connection object
     */
    function delete()
    {
        global $objDatabase;

        // Delete references to products first
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_rel_product_attribute`
             WHERE `option_id` IN (
                SELECT `id`
                  FROM `".DBPREFIX."module_shop".MODULE_INDEX."_option`
                 WHERE `attribute_id`=$this->id)";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;

        // Delete values' Text records
        foreach (array_keys($this->arrValues) as $id) {
            if (!\Text::deleteById($id, 'Shop', self::TEXT_OPTION_NAME)) {
//DBG::log("Attribute::delete(): Error deleting Text for Option ID $id");
                return false;
            }
        }
        // Delete option
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_option`
             WHERE `attribute_id`=$this->id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        // Delete names' Text records
        if (!\Text::deleteById($this->id, 'Shop', self::TEXT_ATTRIBUTE_NAME)) {
//DBG::log("Attribute::delete(): Error deleting Text for Attribute ID $id");
            return false;
        }
        // Delete Attribute
        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_attribute`
             WHERE `id`=$this->id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;

        return true;
    }


    /**
     * Stores the Attribute object in the database.
     *
     * Either updates or inserts the record.
     * Also stores the associated Text records.
     * @return  boolean     True on success, false otherwise
     */
    function store()
    {
        if ($this->id && $this->recordExists()) {
            if (!$this->update()) return false;
//DBG::log("Attribute::store(): Updated ID $this->id");
        } else {
            $this->id = 0;
            if (!$this->insert()) return false;
//DBG::log("Attribute::store(): Inserted ID $this->id");
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID,
            'Shop', self::TEXT_ATTRIBUTE_NAME, $this->name)) {
//DBG::log("Attribute::store(): Error replacing Text $this->name (lang ID ".FRONTEND_LANG_ID);
            return false;
        }
        return $this->storeValues();
    }


    /**
     * Returns true if the record for this objects' ID exists,
     * false otherwise
     * @return  boolean                     True if the record exists,
     *                                      false otherwise
     * @global  ADONewConnection  $objDatabase
     */
    function recordExists()
    {
        global $objDatabase;

        $query = "
            SELECT 1
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_attribute
             WHERE id=$this->id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) return false;
        return true;
    }


    /**
     * Updates the Attribute object in the database.
     *
     * Note that this neither updates the associated Text nor
     * the values records.  Call {@link store()} for that.
     * @return  boolean                     True on success, false otherwise
     * @global  ADONewConnection  $objDatabase
     */
    function update()
    {
        global $objDatabase;

        $query = "
            UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_attribute
               SET type=$this->type
             WHERE id=$this->id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        return true;
    }


    /**
     * Inserts the Attribute object into the database.
     *
     * Note that this neither updates the associated Text nor
     * the values records.  Call {@link store()} for that.
     * @return  boolean                     True on success, false otherwise
     * @global  ADONewConnection
     */
    function insert()
    {
        global $objDatabase;

        $query = "
            INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_attribute (
                type
            ) VALUES (
                $this->type
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $this->id = $objDatabase->Insert_ID();
        return true;
    }


    /**
     * Store the Attibute value records in the database
     * @return  boolean                     True on success, false otherwise
     * @global  ADONewConnection
     */
    function storeValues()
    {
        // Mind: value entries in the array may be new and have to
        // be inserted, even though the object itself has got a valid ID!
        foreach ($this->arrValues as $arrValue) {
            $option_id = (empty($arrValue['id']) ? 0 : $arrValue['id']);
            if ($option_id && $this->recordExistsValue($option_id)) {
                if (!$this->updateValue($arrValue)) return false;
            } else {
                if (!$this->insertValue($arrValue)) return false;
            }
            // Store Text
            if (!\Text::replace($arrValue['id'], FRONTEND_LANG_ID, 'Shop',
                self::TEXT_OPTION_NAME, $arrValue['value']))
                return false;
            // Note that the array index and the option ID stored
            // in $arrValue['id'] are only identical for value
            // records already present in the database.
            // If the value was just added to the array, the array index
            // is just that -- an array index, and its $arrValue['id'] is empty.
        }
        return true;
    }


    /**
     * Update the Attibute value record in the database
     *
     * Note that associated Text records are not changed here,
     * call {@see storeValues()} with the value array for this.
     * @param   array       $arrValue       The value array
     * @return  boolean                     True on success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     */
    function updateValue($arrValue)
    {
        global $objDatabase;

        $query = "
            UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_option
               SET attribute_id=$this->id,
                   price=".floatval($arrValue['price'])."
             WHERE id=".$arrValue['id'];
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        return true;
    }


    /**
     * Insert a new option into the database.
     *
     * Updates the values' ID upon success.
     * Note that associated Text records are not changed here,
     * call {@see storeValues()} with the value array for this.
     * @access  private
     * @param   array       $arrValue       The value array, by reference
     * @return  boolean                     True on success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     */
    function insertValue(&$arrValue)
    {
        global $objDatabase;

        $query = "
            INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_option (
                attribute_id, price
            ) VALUES (
                $this->id, ".floatval($arrValue['price'])."
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;
        $arrValue['id'] = $objDatabase->Insert_ID();
        return true;
    }


    /**
     * Returns boolean true if the option record with the
     * given ID exists in the database table, false otherwise
     * @param   integer     $option_id      The option ID
     * @return  boolean                     True if the record exists,
     *                                      false otherwise
     * @static
     */
    static function recordExistsValue($option_id)
    {
        global $objDatabase;

        $query = "
            SELECT 1
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_option
             WHERE id=$option_id";
        $objResult = $objDatabase->Execute($query);
        if ($objResult && $objResult->RecordCount()) return true;
        return false;
    }


    /**
     * Returns a new Attribute queried by its Attribute ID from
     * the database.
     * @param   integer     $id        The Attribute ID
     * @return  Attribute            The Attribute object
     * @global  ADONewConnection
     * @static
     */
    static function getById($id)
    {
        $arrName = Attributes::getArrayById($id);
        if ($arrName === false) return false;
        $objAttribute = new self(
            $arrName['name'], $arrName['type'], $id
        );
        return $objAttribute;
    }


    /**
     * Returns a new Attribute queried by one of its option IDs from
     * the database.
     * @param   integer     $option_id    The option ID
     * @static
     */
    static function getByOptionId($option_id)
    {
        // Get the associated Attribute ID
        $attribute_id = Attribute::getIdByOptionId($option_id);
        return Attribute::getById($attribute_id);
    }


    /**
     * Return the name of the Attribute selected by its ID
     * from the database.
     * @param   integer     $nameId         The Attribute ID
     * @return  mixed                       The Attribute name on
     *                                      success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @static
     */
    static function getNameById($nameId)
    {
        return \Text::getById($nameId, 'Shop',
            self::TEXT_ATTRIBUTE_NAME, FRONTEND_LANG_ID)->content();
    }


    /**
     * Returns the Attribute ID associated with the given option ID in the
     * value table.
     * @static
     * @param   integer     $option_id      The option ID
     * @return  integer                     The associated Attribute ID
     * @global  ADONewConnection
     */
    static function getIdByOptionId($option_id)
    {
        global $objDatabase;

        $query = "
            SELECT attribute_id
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_option
             WHERE id=$option_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->RecordCount() != 1)
            return false;
        return $objResult->fields['attribute_id'];
    }


    /**
     * Return the option ID corresponding to the given value name,
     * if found, false otherwise.
     *
     * If there is more than one value of the same name, only the
     * first ID found is returned, with no guarantee that it will
     * always return the same.
     * This method is awkwardly named because of the equally awkward
     * names given to the database fields.
     * @param   string      $value          The option name
     * @return  integer                     The first matching option ID found,
     *                                      or false.
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @static
     */
    static function getValueIdByName($value)
    {
        global $objDatabase;

        $arrSqlValue = \Text::getSqlSnippets(
            '`option`.`id`', FRONTEND_LANG_ID, 'Shop',
            array('name' => self::TEXT_OPTION_NAME));
        $query = "
            SELECT `id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_option` AS `option`".
            $arrSqlValue['join']."
             WHERE `name`='".addslashes($value)."'";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) return false;
        return $objResult->fields['id'];
    }


    /**
     * Returns a string representation of the Attribute
     * @return  string
     */
    function toString()
    {
        $string = "ID: $this->id, name: $this->name, type: $this->type<br />  values:<br />";
        foreach ($this->arrValues as $value) {
            $string .=
                "    id: ".  $value['id'].
                ", value: ". $value['value'].
                ", price: ". $value['price'].
                "<br />";
        }
        return $string;
    }


    /**
     * Returns a regular expression for the verification of options
     *
     * The regex returned depends on the value of the $type parameter.
     * Mind that the regex is also applicable to some optional types!
     * For types that need not be verified, the empty string is returned.
     * @param   integer     $type       The Attribute type
     * @return  string                  The regex
     */
    static function getVerificationRegex($type)
    {
        switch ($type) {
            case self::TYPE_TEXT_MANDATORY:
            case self::TYPE_TEXTAREA_MANDATORY:
                return '.+';
// TODO: Improve the regex for file names
            case self::TYPE_UPLOAD_OPTIONAL:
                return '(^$|^.+\..+$)';
            case self::TYPE_UPLOAD_MANDATORY:
                return '^.+\..+$';
            case self::TYPE_EMAIL_OPTIONAL:
                return '(^$|^'.\FWValidator::REGEX_EMAIL.'$)';
            case self::TYPE_EMAIL_MANDATORY:
                return '^'.\FWValidator::REGEX_EMAIL.'$';
            case self::TYPE_URL_OPTIONAL:
                return '(^$|^'.\FWValidator::REGEX_URI.'$)';
            case self::TYPE_URL_MANDATORY:
                return '^'.\FWValidator::REGEX_URI.'$';
            // Note: The date regex is defined based on the value of the
            // ASCMS_DATE_FORMAT_DATE constant and may thus be localized.
            case self::TYPE_DATE_OPTIONAL:
                return
                    '(^$|^'.
                    \DateTimeTools::getRegexForDateFormat(ASCMS_DATE_FORMAT_DATE).
                    '$)';
            case self::TYPE_DATE_MANDATORY:
                return
                    '^'.
                    \DateTimeTools::getRegexForDateFormat(ASCMS_DATE_FORMAT_DATE).
                    '$';
            // Note: Number formats are somewhat arbitrary and should be defined
            // more closely resembling IEEE standards (or whatever).
            case self::TYPE_NUMBER_INT_OPTIONAL:
                return '^\d{0,10}$';
            case self::TYPE_NUMBER_INT_MANDATORY:
                return '^\d{1,10}$';
            case self::TYPE_NUMBER_FLOAT_OPTIONAL:
                return '^\d{0,10}[\d\.]?\d*$';
            case self::TYPE_NUMBER_FLOAT_MANDATORY:
                return '^\d{0,10}[\d\.]\d*$';
            // Not applicable:
            //self::TYPE_MENU_OPTIONAL
            //self::TYPE_RADIOBUTTON
            //self::TYPE_CHECKBOX
            //self::TYPE_MENU_MANDATORY
            //self::TYPE_TEXT_OPTIONAL
            //self::TYPE_TEXTAREA_OPTIONAL
        }
        return '';
    }


    /**
     * Handles database errors
     *
     * Also migrates old ProductAttribute to new Attribute structures,
     * including Text records.
     * @return  boolean   false       Always!
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
// Attribute
        $default_lang_id = \FWLanguage::getDefaultLangId();
        $table_name_old = DBPREFIX.'module_shop_products_attributes_name';
        $table_name_new = DBPREFIX.'module_shop_attribute';
        if (\Cx\Lib\UpdateUtil::table_exist($table_name_new)) {
            \Cx\Lib\UpdateUtil::drop_table($table_name_old);
        } else {
            $table_structure = array(
                'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true),
                'type' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1', 'renamefrom' => 'display_type'),
            );
            $table_index =  array();
            if (\Cx\Lib\UpdateUtil::table_exist($table_name_old)) {
                if (\Cx\Lib\UpdateUtil::column_exist($table_name_old, 'name')) {
                    // Migrate all Product strings to the Text table first
                    \Text::deleteByKey('Shop', self::TEXT_ATTRIBUTE_NAME);
                    $query = "
                        SELECT `id`, `name`
                          FROM `$table_name_old`";
                    $objResult = \Cx\Lib\UpdateUtil::sql($query);
                    if (!$objResult) {
                        throw new \Cx\Lib\Update_DatabaseException(
                           "Failed to to query Attribute names", $query);
                    }
                    while (!$objResult->EOF) {
                        $id = $objResult->fields['id'];
                        $name = $objResult->fields['name'];
                        if (!\Text::replace($id, $default_lang_id, 'Shop',
                            self::TEXT_ATTRIBUTE_NAME, $name)) {
                            throw new \Cx\Lib\Update_DatabaseException(
                               "Failed to migrate Attribute name '$name'");
                        }
                        $objResult->MoveNext();
                    }
                }
            }
//DBG::activate(DBG_ADODB);
            \Cx\Lib\UpdateUtil::table($table_name_old, $table_structure, $table_index);
            if (!\Cx\Lib\UpdateUtil::table_rename($table_name_old, $table_name_new)) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to rename Attribute table");
            }
        }

        $table_name_old = DBPREFIX.'module_shop_products_attributes_value';
        $table_name_new = DBPREFIX.'module_shop_option';
        if (\Cx\Lib\UpdateUtil::table_exist($table_name_new)) {
            \Cx\Lib\UpdateUtil::drop_table($table_name_old);
        } else {
            $table_structure = array(
                'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true),
                'attribute_id' => array('type' => 'INT(10)', 'unsigned' => true, 'renamefrom' => 'name_id'),
                'price' => array('type' => 'DECIMAL(9,2)', 'default' => '0.00'),
            );
            $table_index =  array();
            if (\Cx\Lib\UpdateUtil::table_exist($table_name_old)) {
                if (\Cx\Lib\UpdateUtil::column_exist($table_name_old, 'value')) {
                    // Migrate all Product strings to the Text table first
                    \Text::deleteByKey('Shop', self::TEXT_OPTION_NAME);
                    $query = "
                        SELECT `id`, `value`
                          FROM `$table_name_old`";
                    $objResult = \Cx\Lib\UpdateUtil::sql($query);
                    if (!$objResult) {
                        throw new \Cx\Lib\Update_DatabaseException(
                           "Failed to to query option names", $query);
                    }
                    while (!$objResult->EOF) {
                        $id = $objResult->fields['id'];
                        $name = $objResult->fields['value'];
                        if (!\Text::replace($id, $default_lang_id,
                            'Shop', self::TEXT_OPTION_NAME, $name)) {
                            throw new \Cx\Lib\Update_DatabaseException(
                               "Failed to to migrate option Text '$name'");
                        }
                        $objResult->MoveNext();
                    }
                }
            }
            \Cx\Lib\UpdateUtil::table($table_name_old, $table_structure, $table_index);
            if (!\Cx\Lib\UpdateUtil::table_rename($table_name_old, $table_name_new)) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to rename Option table");
            }
        }

        $table_name_old = DBPREFIX.'module_shop_products_attributes';
        $table_name_new = DBPREFIX.'module_shop_rel_product_attribute';
        if (\Cx\Lib\UpdateUtil::table_exist($table_name_new)) {
            \Cx\Lib\UpdateUtil::drop_table($table_name_old);
        } else {
            $table_structure = array(
                'product_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'primary' => true),
                'option_id' => array('type' => 'INT(10)', 'unsigned' => true, 'primary' => true, 'renamefrom' => 'attributes_value_id'),
                'ord' => array('type' => 'INT(10)', 'default' => '0', 'renamefrom' => 'sort_id'),
            );
            $table_index =  array();
            \Cx\Lib\UpdateUtil::table($table_name_old, $table_structure, $table_index);
            if (!\Cx\Lib\UpdateUtil::table_rename($table_name_old, $table_name_new)) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to rename Product-Attribute relation table $table_name_old to $table_name_new");
            }
        }

        // Always
        return false;
    }

}
