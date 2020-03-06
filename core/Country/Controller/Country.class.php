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
            if (\Cx\Lib\UpdateUtil::table_exist(DBPREFIX."module_shop_countries")) {
                \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_shop_countries');
            }
            // TODO: init country list
        }

        \Cx\Core\Setting\Controller\Setting::init('core', 'country');
        \Cx\Core\Setting\Controller\Setting::add('numof_countries_per_page_backend', 30, 101);

        // Always!
        return false;
    }
}
