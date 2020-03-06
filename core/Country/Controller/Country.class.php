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
 * @since       3.0.0
 * @package     cloudrexx
 * @subpackage  core_country
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @author      Thomas Wirz <thomas.wirz@cloudrexx.com>
 */
namespace Cx\Core\Country\Controller;
/**
 * Country helper methods
 *
 * @since       3.0.0
 * @package     cloudrexx
 * @subpackage  core_country
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @author      Thomas Wirz <thomas.wirz@cloudrexx.com>
 */
class Country
{
    /**
     * Array of all countries
     * @var     array
     * @see     init()
     */
    protected static $arrCountries = array();

    /**
     * Localized lists of countries
     * @var array
     */
    protected static $arrLocales = array();

    /**
     * Initialises the class array of Countries
     *
     * The array created is of the form
     *  array(
     *    country ID => array(
     *      'id'           => country ID,
     *      'alpha2'       => alpha-2 (two letter) code,
     *      'alpha3'       => alpha-3 (three letter) code,
     *      'ord'          => ordinal value,
     *    ),
     *    ... more ...
     *  )
     */
    protected static function init() {
        global $objDatabase;

        $query = "
            SELECT `country`.`id`,
                   `country`.`alpha2`, `country`.`alpha3`,
                   `country`.`ord`,
              FROM ".DBPREFIX."core_country AS `country`";
        $objResult = $objDatabase->SelectLimit($query);
        if (!$objResult) return self::errorHandler();

        static::$arrCountries = array();
        while (!$objResult->EOF) {

            $id = $objResult->fields['id'];
            static::$arrCountries[$id] = array(
                'id'     => $id,
                'ord'    => $objResult->fields['ord'],
                'alpha2' => $objResult->fields['alpha2'],
                'alpha3' => $objResult->fields['alpha3'],
            );
            $objResult->MoveNext();
        }
    }

    /**
     * Returns an array of Country arrays
     *
     * The array returned has the following structure
     *  array(
     *    country ID => array(
     *      'id'           => country ID,
     *      'name'         => country name,
     *      'alpha2'       => alpha-2 (two letter) code,
     *      'alpha3'       => alpha-3 (three letter) code,
     *      'ord'          => ordinal value,
     *    ),
     *    ... more ...
     *  )
     * Notes:
     *  - The Countries are returned in the current locale of the interface,
     *    except if the optional $langId argument is set.
     * @param   integer   $langId           The optional language ID
     * @return  array                       The Country array on success,
     *                                      an empty array otherwise
     */
    static function getData($langId = 0) {
        $langId = (int)$langId;

        $cxMode = \Cx\Core\Core\Controller\Cx::instanciate()->getMode();
        if ($cxMode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            if (empty($langId)) {
                $langId = LANG_ID;
            }
            $iso1 = \FWLanguage::getBackendLanguageCodeById($langId);
        } else {
            if (empty($langId)) {
                $langId = FRONTEND_LANG_ID;
            }
            $iso1 = \FWLanguage::getLanguageCodeById($langId);
        }

        if (empty(static::$arrCountries)) {
            static::init();
        }

        if (isset(static::$arrLocales[$iso1])) {
            return static::$arrLocales[$iso1];
        }
        static::$arrLocales[$iso1] = array();

        foreach (static::$arrCountries as $country) {
            $name = \Locale::getDisplayRegion(
                // 'und_' stands for 'Undetermined language' of a region
                // refer to https://www.unicode.org/reports/tr35/tr35-29.html#Unknown_or_Invalid_Identifiers
                'und_' . $country['alpha2'],
                $iso1
            );

            $country['name'] = $name;
            static::$arrLocales[$iso1][$country['id']] = $country;
        }

        return static::$arrLocales[$iso1];
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
     *    'ord'          => ordinal value,
     *  ),
     * The Country is returned in the current language,
     * except if the optional $lang_id argument is not empty.
     *
     * @param   integer   $country_id       The Country ID
     * @param   integer   $lang_id          The optional language ID
     * @return  array                       The Country array on success,
     *                                      false otherwise
     */
    public static function getById($country_id, $lang_id = 0)
    {
        $countries = static::getData($lang_id);
        if (isset($countries[$country_id])) {
            return $countries[$country_id];
        }

        return false;
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
     *    'ord'          => ordinal value,
     *  ),
     * The Country is returned in the current language,
     * except if the optional $lang_id argument is not empty.
     *
     * @global  ADONewConnection  $objDatabase
     * @param   string    $country_name     The Country name
     * @param   integer   $lang_id          The optional language ID
     * @return  array                       The Country array on success,
     *                                      false otherwise
     */
    static function getByName($country_name, $lang_id = 0)
    {
        $countries = static::getData($lang_id);
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
     *    'ord'          => ordinal value,
     *  ),
     * The Countries are returned in the current language,
     * except if the optional $lang_id argument is not empty.
     *
     * @param   string    $term     The search term to get countries
     * @param   integer   $lang_id  The optional language ID
     * @return  array               The Country array on success,
     *                              false otherwise
     */
    static function searchByName($term, $lang_id = 0)
    {
        $countries = static::getData($lang_id);

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
     * Returns the ID of the Country specified by its alpha2 code
     * @param   string    $alpha2   The alpha2 code
     * @return  integer             The Country ID on success, null otherwise
     */
    static function getIdByAlpha2($alpha2)
    {
        $countries = static::getData();
        foreach ($countries as $country) {
            if (strtolower($country['alpha2']) == strtolower($alpha2)) {
                return $country['id'];
            }
        }

        return null;
    }


    /**
     * Returns the array of all country names, indexed by their ID
     *
     * @param   integer   $lang_id    Language ID of language the countries
     *                                should be returned in. If not set, then
     *                                the language locale of the current request
     *                                is used.
     * @return  array                 The country names array on success,
     *                                false otherwise
     */
    static function getNameArray($lang_id = 0)
    {
        $data = static::getData($lang_id);

        $arrName = array();
        foreach ($data as $id => $arrCountry) {
            $arrName[$id] = $arrCountry['name'];
        }
        return $arrName;
    }


    /**
     * Returns the name of the country selected by its ID
     *
     * If a country with the given ID does not exist, returns the empty string.
     * @param   integer   $country_id     The country ID
     * @return  string                    The country name, or the empty string
     */
    static function getNameById($country_id)
    {
        $country = static::getById($country_id);
        if (!$country) {
            return '';
        }
        return $country['name'];
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
        $country = static::getById($country_id);
        if (!$country) {
            return '';
        }
        return $country['alpha2'];
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
        $country = static::getById($country_id);
        if (!$country) {
            return '';
        }
        return $country['alpha3'];
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
     * Returns the HTML dropdown menu
     *
     * Returns a dropdown menu with the optional ID preselected and optional
     * onchange method added.
     * @param   string    $menuName   Optional name of the menu,
     *                                defaults to "countryId"
     * @param   string    $selected   Optional selected country ID
     * @param   string    $onchange   Optional onchange callback function
     * @return  string                The HTML dropdown menu code
     * @static
     */
    static function getMenu(
        $menuName='countryId', $selected='', $onchange=''
    ) {
        $countries = static::getData();
        if (empty($countries)) {
            return '';
        }

        return \Html::getSelectCustom(
            $menuName, self::getMenuoptions($selected),
            false, $onchange);
    }


    /**
     * Returns the HTML code for the countries dropdown menu options
     * @param   string  $selected     The optional selected Country ID
     *                                are added to the options, all otherwise.
     * @return  string                The HTML dropdown menu options code
     * @static
     */
    static function getMenuoptions($selected=0)
    {
        return \Html::getOptions(self::getNameArray(), $selected);
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
        );
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure);

        if (\Cx\Lib\UpdateUtil::table_empty($table_name)) {
            if (\Cx\Lib\UpdateUtil::table_exist(DBPREFIX."module_shop_countries")) {
                \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_shop_countries');
            }
            // TODO: init country list
        }

        // Always!
        return false;
    }
}
