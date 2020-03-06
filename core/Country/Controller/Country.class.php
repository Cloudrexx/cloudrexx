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
 * Core Country and Region class
 *
 * @version     3.0.0
 * @since       3.0.0
 * @package     cloudrexx
 * @subpackage  core_country
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @todo        Test!
 */
namespace Cx\Core\Country\Controller;
/**
 * Country helper methods
 *
 * @version     3.0.0
 * @since       3.0.0
 * @package     cloudrexx
 * @subpackage  core_country
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */
class Country
{
    /**
     * Text key
     */
    const TEXT_NAME = 'core_country_name';

    /**
     * Array of all countries
     * @var     array
     * @access  private
     * @see     init()
     */
    private static $arrCountries = null;


    /**
     * Initialises the class array of Countries
     *
     * Calls {@see getArray()} to accomplish this.
     * @param   integer   $lang_id      The optional language ID.
     *                                  Defaults to the FRONTEND_LANG_ID
     *                                  if empty
     * @return  void
     */
    static function init($lang_id=null)
    {
        self::$arrCountries = self::getArray($lang_id);
    }


    /**
     * Returns an array of Country arrays
     *
     * The array created is of the form
     *  array(
     *    country ID => array(
     *      'id'           => country ID,
     *      'name'         => country name,
     *      'alpha2'       => alpha-2 (two letter) code,
     *      'alpha3'       => alpha-3 (three letter) code,
     *      'active'       => boolean,
     *      'ord'          => ordinal value,
     *    ),
     *    ... more ...
     *  )
     * Notes:
     *  - The Countries are returned in the current frontend language
     *    as set in FRONTEND_LANG_ID, except if the optional $lang_id
     *    argument is not empty.
     *  - Empty arguments are set to their default values, which are:
     *    - $lang_id: The current value of the FRONTEND_LANG_ID constant
     * @global  ADONewConnection  $objDatabase
     * @param   integer   $lang_id          The optional language ID
     * @return  array                       The Country array on success,
     *                                      false otherwise
     */
    static function getArray($lang_id=null) {
        global $objDatabase;

        $lang_id = (int)$lang_id;
        if (empty($lang_id)) $lang_id = FRONTEND_LANG_ID;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $locale = $cx->getDb()->getEntityManager()->find(
            'Cx\Core\Locale\Model\Entity\Locale',
            $lang_id
        );

        $query = "
            SELECT `country`.`id`,
                   `country`.`alpha2`, `country`.`alpha3`,
                   `country`.`ord`,
                   `country`.`active`
              FROM ".DBPREFIX."core_country AS `country`";
        $objResult = $objDatabase->SelectLimit($query);
        if (!$objResult) return self::errorHandler();

        $arrCountries = array();
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            $strName = \Locale::getDisplayRegion(
                // 'und_' stands for 'Undetermined language' of a region
                // refer to https://www.unicode.org/reports/tr35/tr35-29.html#Unknown_or_Invalid_Identifiers
                'und_' . $objResult->fields['alpha2'],
                $locale->getIso1()->getIso1()
            );
            $arrCountries[$id] = array(
                'id'     => $id,
                'name'   => $strName,
                'ord'    => $objResult->fields['ord'],
                'alpha2' => $objResult->fields['alpha2'],
                'alpha3' => $objResult->fields['alpha3'],
                'active' => $objResult->fields['active'],
            );
            $objResult->MoveNext();
        }
        return $arrCountries;
    }


    /**
     * Returns an array of Country data for the given ID
     *
     * The array created is of the form
     *  array(
     *    'id'           => country ID,
     *    'name'         => country name,
     *    'alpha2'       => alpha-2 (two letter) code,
     *    'alpha3'       => alpha-3 (three letter) code,
     *    'active'       => boolean,
     *    'ord'          => ordinal value,
     *  ),
     * The Country is returned in the current frontend language
     * as set in FRONTEND_LANG_ID, except if the optional $lang_id
     * argument is not empty.
     * @global  ADONewConnection  $objDatabase
     * @param   integer   $country_id       The Country ID
     * @param   integer   $lang_id          The optional language ID
     * @return  array                       The Country array on success,
     *                                      false otherwise
     */
    static function getById($country_id, $lang_id=null)
    {
        global $objDatabase;

        $lang_id = (int)$lang_id;
        if (empty($lang_id)) {
//die("Country::getById(): ERROR: Empty language ID");
            $lang_id = FRONTEND_LANG_ID;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $locale = $cx->getDb()->getEntityManager()->find(
            'Cx\Core\Locale\Model\Entity\Locale',
            $lang_id
        );

        $query = "
            SELECT `country`.`alpha2`, `country`.`alpha3`,
                   `country`.`ord`,
                   `country`.`active`
              FROM ".DBPREFIX."core_country AS `country`
             WHERE `country`.`id`=$country_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
// Disabled, as this method is called by errorHandler() as well!
//            return self::errorHandler();
            return false;
        }
        if ($objResult->EOF) return false;

        $strName = \Locale::getDisplayRegion(
            // 'und_' stands for 'Undetermined language' of a region
            // refer to https://www.unicode.org/reports/tr35/tr35-29.html#Unknown_or_Invalid_Identifiers
            'und_' . $objResult->fields['alpha2'],
            $locale->getIso1()->getIso1()
        );

        return array(
            'id'     => $country_id,
            'name'   => $strName,
            'ord'    => $objResult->fields['ord'],
            'alpha2' => $objResult->fields['alpha2'],
            'alpha3' => $objResult->fields['alpha3'],
            'active' => $objResult->fields['active'],
        );
    }

    /**
     * Returns an array of Country data for the given Name
     *
     * The array created is of the form
     *  array(
     *    'id'           => country ID,
     *    'name'         => country name,
     *    'alpha2'       => alpha-2 (two letter) code,
     *    'alpha3'       => alpha-3 (three letter) code,
     *    'active'       => boolean,
     *    'ord'          => ordinal value,
     *  ),
     * The Country is returned in the current frontend language
     * as set in FRONTEND_LANG_ID, except if the optional $lang_id
     * argument is not empty.
     * @global  ADONewConnection  $objDatabase
     * @param   string    $country_name     The Country name
     * @param   integer   $lang_id          The optional language ID
     * @return  array                       The Country array on success,
     *                                      false otherwise
     */
    static function getByName($country_name, $lang_id=null)
    {
        $lang_id = (int)$lang_id;
        if (empty($lang_id)) {
            $lang_id = FRONTEND_LANG_ID;
        }
        $countries = static::getArray($lang_id);
        foreach ($countries as $country) {
            if (strtolower($country['name']) == strtolower($country_name)) {
                return $country;
            }
        }

        return false;
    }

    /**
     * Returns matched array of Countries data for the given Name
     *
     * The array created is of the form
     *  array(
     *    'id'           => country ID,
     *    'name'         => country name,
     *    'alpha2'       => alpha-2 (two letter) code,
     *    'alpha3'       => alpha-3 (three letter) code,
     *    'active'       => boolean,
     *    'ord'          => ordinal value,
     *  ),
     * The Countries are returned in the current frontend language
     * as set in FRONTEND_LANG_ID, except if the optional $lang_id
     * argument is not empty.
     *
     * @param   string    $term     The search term to get countries
     * @param   integer   $lang_id  The optional language ID
     * @return  array               The Country array on success,
     *                              false otherwise
     */
    static function searchByName($term, $lang_id = null)
    {
        $lang_id = contrexx_input2int($lang_id);
        if (empty($lang_id)) {
            $lang_id = FRONTEND_LANG_ID;
        }
        $countries = static::getArray($lang_id);

        $matches = array();
        foreach ($countries as $country) {
            if (
                strpos(
                    strtolower($country['name']),
                    strtolower($term)
                ) !== false ||
                strpos(
                    strtolower($country['alpha2']),
                    strtolower($term)
                ) !== false ||
                strpos(
                    strtolower($country['alpha3']),
                    strtolower($term)
                ) !== false
            ) {
                $matches[] = $country;
            }
        }

        return $matches;
    }

    /**
     * Returns the current number of Country records present in the database
     * @return  integer           The number of records on success,
     *                            false otherwise.
     */
    static function getRecordcount()
    {
        global $objDatabase;

        $query = "
            SELECT COUNT(*) AS `numof_records`
              FROM ".DBPREFIX."core_country";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) return self::errorHandler();
        return $objResult->fields['numof_records'];
    }


    /**
     * Returns the ID of the Country specified by its alpha2 code
     * @param   string    $alpha2   The alpha2 code
     * @return  integer             The Country ID on success, null otherwise
     */
    static function getIdByAlpha2($alpha2)
    {
        global $objDatabase;

        $query = "
            SELECT `country`.`id`
              FROM ".DBPREFIX."core_country AS `country`
             WHERE `alpha2`='".addslashes($alpha2)."'";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if ($objResult->EOF) return null;
        return $objResult->fields['id'];
    }


    /**
     * Returns the array of all active country names, indexed by their ID
     *
     * If the optional $lang_id parameter is empty, the FRONTEND_LANG_ID
     * constant's value is used instead.
     * @param   boolean   $active     If true, only active Countries are included,
     *                                all Countries otherwise.
     *                                Defaults to false
     * @param   integer   $lang_id    The optional language ID.
     *                                Defaults to the FRONTEND_LANG_ID
     *                                if empty
     * @return  array                 The country names array on success,
     *                                false otherwise
     */
    static function getNameArray($active=true, $lang_id=null)
    {
        static $arrName = null;

        if (is_null(self::$arrCountries)) self::init($lang_id);
        if (is_null($arrName)) {
            $arrName = array();
            foreach (self::$arrCountries as $id => $arrCountry) {
                if ($active && empty($arrCountry['active'])) continue;
                $arrName[$id] = $arrCountry['name'];
            }
//die("Names: ".var_export($arrName, true));
        }
        return $arrName;
    }


    /**
     * Returns the name of the country selected by its ID
     *
     * If a country with the given ID does not exist, returns the empty string.
     * @param   integer   $country_id     The country ID
     * @return  string                    The country name, or the empty string
     * @static
     */
    static function getNameById($country_id)
    {
        if (is_null(self::$arrCountries)) self::init();
        if (isset(self::$arrCountries[$country_id]))
            return self::$arrCountries[$country_id]['name'];
        return '';
    }


    /**
     * Returns the ISO 2 code of the country selected by its ID
     *
     * If a country with the given ID does not exist, returns the empty string.
     * @param   integer   $country_id     The country ID
     * @return  string                    The ISO 2 code, or the empty string
     * @static
     */
    static function getAlpha2ById($country_id)
    {
        if (is_null(self::$arrCountries)) self::init();
        if (isset(self::$arrCountries[$country_id]))
            return self::$arrCountries[$country_id]['alpha2'];
        return '';
    }


    /**
     * Returns the ISO 3 code of the country selected by its ID
     *
     * If a country with the given ID does not exist, returns the empty string.
     * @param   integer   $country_id     The country ID
     * @return  string                    The ISO 3 code, or the empty string
     * @static
     */
    static function getAlpha3ById($country_id)
    {
        if (is_null(self::$arrCountries)) self::init();
        if (isset(self::$arrCountries[$country_id]))
            return self::$arrCountries[$country_id]['alpha3'];
        return '';
    }


    /**
     * Returns true if the country selected by its ID is active
     *
     * If a country with the given ID does not exist, returns false.
     * @param   integer   $country_id     The country ID
     * @return  boolean                   True if active, false otherwise
     * @static
     */
    static function isActiveById($country_id)
    {
        if (is_null(self::$arrCountries)) self::init();
        if (isset(self::$arrCountries[$country_id]))
            return self::$arrCountries[$country_id]['active'];
        return '';
    }


    /**
     * Resets the state of the class
     * @return  void
     * @static
     */
    static function reset()
    {
        self::$arrCountries = null;
    }

    /**
     * Returns true if the record for the given ID exists in the database
     *
     * Returns true if the $country_id argument is empty.
     * Returns false both if the ID cannot be found and on failure.
     * @param   integer   $country_id   The Country ID
     * @return  boolean                 True if the Country ID is present,
     *                                  false otherwise.
     */
    static function recordExists($country_id)
    {
        global $objDatabase;

        if (empty($country_id)) {
            return false;
        }
        $objResult = $objDatabase->Execute("
            SELECT 1
              FROM `".DBPREFIX."core_country` AS `country`
             WHERE `country`.`id`=$country_id");
        if (!$objResult) return self::errorHandler();
        return $objResult->EOF;
    }


    /**
     * Stores a Country in the database.
     *
     * Decides whether to call {@see insert()} or {@see update()}
     * by means of calling {@see getById()} if $country_id is valid.
     * Optional values equal to null are ignored and not updated,
     * or inserted with default values.
     * Note, however, that $country_name, $alpha2 and $alpha3 are mandatory
     * and must be non-empty when a new record is to be {@see insert()}ed!
     * @param   string    $alpha2         The ISO 2-character code, or null
     * @param   string    $alpha3         The ISO 3-character code, or null
     * @param   integer   $country_name   The name of the Country, or null
     * @param   integer   $ord            The ordinal value, or null
     * @param   boolean   $active         The active status, or null
     * @param   integer   $country_id     The Country ID, or null
     * @return  boolean                   True on success, false otherwise
     */
    static function store(
        $alpha2=null, $alpha3=null, $lang_id=null, $country_name=null,
        $ord=null, $active=null, $country_id=null
    ) {
        $arrCountry = false;
        if ($country_id) $arrCountry = self::getById($country_id, $lang_id);
        if ($arrCountry) {
//DBG::log("Country::store($alpha2, $alpha3, $lang_id, $country_name, $ord, $active, $country_id): Updating Country ID $country_id, $country_name");
            if (!self::update($country_id, $ord, $active, $alpha2, $alpha3))
                return false;
        } else {
            $country_id = self::insert(
                $alpha2, $alpha3, $ord, $active, $country_id);
            if (!$country_id) {
//DBG::log("Country::store($alpha2, $alpha3, $lang_id, $country_name, $ord, $active, $country_id): Failed to insert Country ID $country_id, $country_name");
                return false;
            }
        }
        // Store the Country name only if it's set
        if ($country_name != '') {
            if (\Text::replace(
                $country_id, $lang_id, 'core', self::TEXT_NAME, $country_name)) {
//DBG::log("Country::store($alpha2, $alpha3, $lang_id, $country_name, $ord, $active, $country_id): Successfully stored Text);
                return true;
            }
        }
//DBG::log("Country::store($alpha2, $alpha3, $lang_id, $country_name, $ord, $active, $country_id): Failed to store Text");
        // TODO: If inserting fails, the record could be rolled back
        return false;
    }


    /**
     * Inserts the Country into the database
     *
     * Note that the Country name is inserted or updated in {@see store()} only.
     * @param   string    $alpha2     The ISO 2-character code
     * @param   string    $alpha3     The ISO 3-character code
     * @param   integer   $ord        The ordinal value, or null
     * @param   boolean   $active     The active status, or null
     * @param   integer   $country_id The Country ID, or null
     * @return  boolean               True on success, false otherwise
     */
    static function insert(
        $alpha2, $alpha3, $ord=null, $active=null, $country_id=null
    ) {
        global $objDatabase;

        if (empty($alpha2) || empty($alpha3)) {
//DBG::log("Country::insert(): Error: Trying to store Country with empty name or alpha code");
            return false;
        }
        $objResult = $objDatabase->Execute("
            INSERT INTO `".DBPREFIX."core_country` (
              `id`,
              `alpha2`,
              `alpha3`,
              `ord`,
              `active`
            ) VALUES (
              ".($country_id ? $country_id : 'NULL').",
              '".addslashes($alpha2)."',
              '".addslashes($alpha3)."',
              ".intval($ord).",
              ".intval($active)."
            )");
        if (!$objResult) return false; //self::errorHandler();
        return ($country_id ? $country_id : $objDatabase->Insert_ID());
    }


    /**
     * Updates the Country in the database
     *
     * Note that it should never be necessary to update the Text ID,
     * so that parameter is not present here.  Call {@see store()}
     * to update the Country name as well.
     * @param   integer   $country_id The Country ID
     * @param   integer   $ord        The ordinal value, or null
     * @param   boolean   $active     The active status, or null
     * @param   string    $alpha2     The ISO 2-character code, or null
     * @param   string    $alpha3     The ISO 3-character code, or null
     * @return  boolean               True on success, false otherwise
     */
    static function update(
        $country_id, $ord=null, $active=null, $alpha2=null, $alpha3=null
    ) {
        global $objDatabase;

        if (empty($country_id)) {
//die("Country::update($country_id, $text_id, $ord, $active, $alpha2, $alpha3): Error: Cannot update without a valid Country ID");
            return false;
        }
        $query = array();
        if (isset($ord))     $query[] = "`ord`=$ord";
        if (isset($active))  $query[] = "`active`=".intval($active);
        if (!empty($alpha2)) $query[] = "`alpha2`='".addslashes($alpha2)."'";
        if (!empty($alpha3)) $query[] = "`alpha3`='".addslashes($alpha3)."'";
        // Something to do?
        if ($query) {
            $objResult = $objDatabase->Execute("
                UPDATE `".DBPREFIX."core_country`
                   SET ".join(', ', $query)."
                 WHERE `id`=$country_id");
            if (!$objResult) return false; //self::errorHandler();
        }
        return true;
    }


    /**
     * Deletes the Country with the given ID from the database
     * @param   integer   $country_id The Country ID
     * @return  boolean               True on success, false otherwise
     */
    static function deleteById($country_id)
    {
        global $objDatabase, $_CORELANG;

        if (is_null(self::$arrCountries)) self::init();
        if (empty(self::$arrCountries[$country_id])) {
//            Message::add($_CORELANG['TXT_CORE_COUNTRY_ERROR_DELETING_NOT_FOUND'];
            return false;
        }
        if (!\Text::deleteById($country_id, 'core', self::TEXT_NAME, 0)) {
            return false;
        }
        $query = "
            DELETE FROM `".DBPREFIX."core_country`
             WHERE `id`=".intval($country_id);
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
//            Message::add($_CORELANG['TXT_CORE_COUNTRY_ERROR_DELETING'];
            return false;
        }
        return true;
    }


    /**
     * Returns the HTML dropdown menu or hidden input field plus name string
     *
     * If there is just one active country, returns a hidden <input> tag with
     * the countries' name appended.  If there are more, returns a dropdown
     * menu with the optional ID preselected and optional onchange method added.
     * @param   string    $menuName   Optional name of the menu,
     *                                defaults to "countryId"
     * @param   string    $selected   Optional selected country ID
     * @param   boolean   $active     Include inactive countries if false.
     *                                Defaults to false
     * @param   string    $onchange   Optional onchange callback function
     * @return  string                The HTML dropdown menu code
     * @static
     */
    static function getMenu(
        $menuName='countryId', $selected='', $active=true, $onchange=''
    ) {
        if (is_null(self::$arrCountries)) self::init();
        if (empty(self::$arrCountries)) return '';
//DBG::log("Country::getMenu(): ".count(self::$arrCountries)." countries");
        if (count(self::$arrCountries) == 1) {
            $arrCountry = current(self::$arrCountries);
            return
                \Html::getHidden($menuName, $arrCountry['id']).
                $arrCountry['name'];
        }
        return \Html::getSelectCustom(
            $menuName, self::getMenuoptions($selected, $active),
            false, $onchange);
    }


    /**
     * Returns the HTML code for the countries dropdown menu options
     * @param   string  $selected     The optional selected Country ID
     * @param   boolean $active       If true, only active countries
     *                                are added to the options, all otherwise.
     * @return  string                The HTML dropdown menu options code
     * @static
     */
    static function getMenuoptions($selected=0, $active=true)
    {
        return \Html::getOptions(self::getNameArray($active), $selected);
    }

    /**
     * Activate the countries whose IDs are listed in the comma separated
     * list of Country IDs
     *
     * Any Country not included in the list is deactivated.
     * @param   string    $strCountryIds    The comma separated list of
     *                                      to-be-active Country IDs
     * @return  boolean                     True on success, false otherwise
     */
    static function activate($strCountryIds)
    {
        global $objDatabase;

        $query = "
            UPDATE ".DBPREFIX."core_country
               SET active=0
             WHERE id NOT IN ($strCountryIds)";
        if (!$objDatabase->Execute($query)) return false;
        self::reset();
        $query = "
            UPDATE ".DBPREFIX."core_country
               SET active=1
             WHERE id IN ($strCountryIds)";
        return (boolean)$objDatabase->Execute($query);
    }


    /**
     * Tries to recreate the database table(s) for the class
     *
     * Should be called whenever there's a problem with the database table.
     * @return  boolean             False.  Always.
     */
    static function errorHandler()
    {
        $table_name = DBPREFIX.'core_country';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'alpha2' => array('type' => 'CHAR(2)', 'notnull' => true, 'default' => ''),
            'alpha3' => array('type' => 'CHAR(3)', 'notnull' => true, 'default' => ''),
            'ord' => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'renamefrom' => 'sort_order'),
            'active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'renamefrom' => 'is_active'),
        );
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure);
        if (\Cx\Lib\UpdateUtil::table_empty($table_name)) {
            \Text::deleteByKey('core', self::TEXT_NAME);
            // Copy the Countries from the Shop module if possible
            if (\Cx\Lib\UpdateUtil::table_exist(DBPREFIX."module_shop_countries")) {
                $query = "
                    SELECT `countries_id`, `countries_name`,
                           `countries_iso_code_2`, `countries_iso_code_3`,
                           `activation_status`
                      FROM ".DBPREFIX."module_shop_countries";
                $objResult = \Cx\Lib\UpdateUtil::sql($query);
                if (!$objResult) {
                    throw new \Cx\Lib\Update_DatabaseException(
                       "Failed to to query Country names", $query);
                }
                $default_lang_id = \FWLanguage::getDefaultLangId();
                while (!$objResult->EOF) {
                    $id = $objResult->fields['countries_id'];
                    $name = $objResult->fields['countries_name'];
                    $alpha2 = $objResult->fields['countries_iso_code_2'];
                    $alpha3 = $objResult->fields['countries_iso_code_3'];
                    $active = $objResult->fields['activation_status'];
                    $ord = 0;
                    if ($id == 14) {
                        // fixing missing name
                        $name = 'Österreich';
                    }
                    if (!self::store($alpha2, $alpha3, $default_lang_id,
                        $name, $ord, $active, $id)
                    ) {
                        throw new \Cx\Lib\Update_DatabaseException(
                           "Failed to to migrate Country '$name'");
                    }
                    $objResult->MoveNext();
                }
                \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_shop_countries');
            }
        }


// USE FOR NEW INSTALLATIONS ONLY!
// These records will lead to inconsistencies with Country references in
// other tables otherwise.
        if (\Cx\Lib\UpdateUtil::table_empty($table_name)) {
            // Add new Country records if available
            if (   file_exists(ASCMS_CORE_PATH.'/countries_iso_3166-2.php')
                && include_once(ASCMS_CORE_PATH.'/countries_iso_3166-2.php')) {
//DBG::log("Country::errorHandler(): Included ISO file");
                $arrCountries = null;
                $ord = 0;
                foreach ($arrCountries as $country_id => $arrCountry) {
                    $name = $arrCountry[0];
                    $alpha2 = $arrCountry[1];
                    $alpha3 = $arrCountry[2];
// Not currently in use:
//                    $numeric = $arrCountry[3];
//                    $iso_full = $arrCountry[4];
                    // English (language ID 2) only!
                    if (!self::store(
                        $alpha2, $alpha3, 2, $name, ++$ord, true, $country_id)
                    ) {
                        throw new \Cx\Lib\Update_DatabaseException(
                           "Failed to to add Country '$name' from ISO file");
                    }
//DBG::log("Country::errorHandler(): Added Country ID $country_id: '$name'");
                }
            }
        }

//DBG::activate(DBG_ADODB);
        // Add more languages from the countries_languages.php file,
        // if present
        $arrCountries = array();
        // $arrCountries is redefined in the file
        if (   file_exists(ASCMS_CORE_PATH.'/countries_languages.php')
            && include_once ASCMS_CORE_PATH.'/countries_languages.php') {
            foreach ($arrCountries as $alpha2 => $arrLanguage) {
//DBG::log("errorHandler: Looking for Alpha-2 $alpha2");
                $country_id = self::getIdByAlpha2($alpha2);
                if (!$country_id) {
// TODO: Fail or not?
                    continue;
                }
                foreach ($arrLanguage as $lang_id => $name) {
                    if (!\Text::replace($country_id, $lang_id, 'core',
                        self::TEXT_NAME, $name)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                           "Failed to to update Country '$name' from languages file");
                    }
//DBG::log("Country::errorHandler(): Added Country ID $country_id: language ID $lang_id");
                }
            }
        }

        \Cx\Core\Setting\Controller\Setting::init('core', 'country');
        \Cx\Core\Setting\Controller\Setting::add('numof_countries_per_page_backend', 30, 101);

        // More to come...

        // Always!
        return false;
    }

}
