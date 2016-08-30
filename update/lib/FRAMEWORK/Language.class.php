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
 * Framework language
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.3.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Framework language
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.3.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class FWLanguage
{
    private static $arrLanguages = null;

    /**
     * ID of the default language
     *
     * @var integer
     * @access private
     */
    private static $defaultLangId;


    /**
     * Loads the language config from the database
     *
     * This used to be in __construct but is also
     * called from core/language.class.php to reload
     * the config, so core/settings.class.php can
     * rewrite .htaccess (virtual lang dirs).
     */
    static function init()
    {
        global $_CONFIG, $objDatabase;

        $objResult = $objDatabase->Execute("
            SELECT id, lang, name, charset, themesid,
                   frontend, backend, is_default
              FROM ".DBPREFIX."languages
             ORDER BY id ASC");
        if ($objResult) {
            // setting full to true for update
            $full = true;
            while (!$objResult->EOF) {
                self::$arrLanguages[$objResult->fields['id']] = array(
                    'id'         => $objResult->fields['id'],
                    'lang'       => $objResult->fields['lang'],
                    'name'       => $objResult->fields['name'],
                    'charset'    => $objResult->fields['charset'],
                    'themesid'   => $objResult->fields['themesid'],
                    'frontend'   => $objResult->fields['frontend'],
                    'backend'    => $objResult->fields['backend'],
                    'is_default' => $objResult->fields['is_default'],
                    'fallback'   => '',
                );
                if (!$full && $objResult->fields['is_default'] != 'true') {
                    self::$arrLanguages[$objResult->fields['id']]['frontend'] = 0;
                    self::$arrLanguages[$objResult->fields['id']]['backend'] = 0;
                }
                if ($objResult->fields['is_default'] == 'true') {
                    self::$defaultLangId = $objResult->fields['id'];
                }
                $objResult->MoveNext();
            }
        }
    }


    /**
     * Returns an array of active language names, indexed by language ID
     * @param   string  $mode     'frontend' or 'backend' languages.
     *                            Defaults to 'frontend'
     * @return  array             The array of enabled language names
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getNameArray($mode='frontend')
    {
        if (!isset(self::$arrLanguages)) self::init();
        $arrName = array();
        foreach (self::$arrLanguages as $lang_id => $arrLanguage) {
            if (empty($arrLanguage[$mode])) continue;
            $arrName[$lang_id] = $arrLanguage['name'];
        }
        return $arrName;
    }


    /**
     * Returns an array of active language IDs
     *
     * Note that the array returned contains the language ID both as
     * key and value, for your convenience.
     * @param   string  $mode     'frontend' or 'backend' languages.
     *                            Defaults to 'frontend'
     * @return  array             The array of enabled language IDs
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getIdArray($mode='frontend')
    {
        if (!isset(self::$arrLanguages)) self::init();
        $arrId = array();
        foreach (self::$arrLanguages as $lang_id => $arrLanguage) {
            if (empty($arrLanguage[$mode])) continue;
            $arrId[$lang_id] = $lang_id;
        }
        return $arrId;
    }


    /**
     * Returns the ID of the default language
     * @return integer Language ID
     */
    static function getDefaultLangId()
    {
        if (empty(self::$defaultLangId)) {
            self::init();
        }
        return self::$defaultLangId;
    }


    /**
     * Returns the complete language data
     * @see     FWLanguage()
     * @return  array           The language data
     * @access  public
     */
    static function getLanguageArray()
    {
        if (empty(self::$arrLanguages)) self::init();
        return self::$arrLanguages;
    }


    /**
     * Return only the languages active in the frontend
     * @author     Stefan Heinemann <sh@adfinis.com>
     * @return     array
     */
    public static function getActiveFrontendLanguages()
    {
        if (empty(self::$arrLanguages)) {
            self::init();
        }
        $arr = array();
        foreach (self::$arrLanguages as $id => $lang) {
            if ($lang['frontend']) {
                $arr[$id] = $lang;
            }
        }
        return $arr;
    }


    /**
     * Returns single language related fields
     *
     * Access language data by specifying the language ID and the index
     * as initialized by {@link FWLanguage()}.
     * @return  mixed           Language data field content
     * @access  public
     */
    static function getLanguageParameter($id, $index)
    {
        if (empty(self::$arrLanguages)) self::init();
        return (isset(self::$arrLanguages[$id][$index])
            ? self::$arrLanguages[$id][$index] : false);
    }


    /**
     * Returns HTML code to display a language selection dropdown menu.
     *
     * Does only contain the <select> tag pair if the optional $menuName
     * is specified and evaluates to a true value.
     * @param   integer $selectedId The optional preselected language ID
     * @param   string  $menuName   The optional menu name
     * @param   string  $onchange   The optional onchange code
     * @return  string              The dropdown menu HTML code
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @todo    Use the Html class instead
     */
    static function getMenu($selectedId=0, $menuName='', $onchange='')
    {
        $menu = self::getMenuoptions($selectedId, true);
        if ($menuName) {
            $menu = "<select id='$menuName' name='$menuName'".
                    ($onchange ? ' onchange="'.$onchange.'"' : '').
                    ">\n$menu</select>\n";
        }
        return $menu;
    }


    /**
     * Returns HTML code to display a language selection dropdown menu
     * for the active frontend languages only.
     *
     * Does only contain the <select> tag pair if the optional $menuName
     * is specified and evaluates to a true value.
     * Frontend use only.
     * @param   integer $selectedId The optional preselected language ID
     * @param   string  $menuName   The optional menu name
     * @param   string  $onchange   The optional onchange code
     * @return  string              The dropdown menu HTML code
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @todo    Use the Html class instead
     */
    static function getMenuActiveOnly($selectedId=0, $menuName='', $onchange='')
    {
        $menu = self::getMenuoptions($selectedId, false);
        if ($menuName) {
            $menu = "<select id='$menuName' name='$menuName'".
                    ($onchange ? ' onchange="'.$onchange.'"' : '').
                    ">\n$menu</select>\n";
        }
//echo("getMenu(select=$selectedId, name=$menuName, onchange=$onchange): made menu: ".htmlentities($menu)."<br />");
        return $menu;
    }


    /**
     * Returns HTML code for the language menu options
     * @param   integer $selectedId   The optional preselected language ID
     * @param   boolean $flagInactive If true, all languages are added,
     *                                only the active ones otherwise
     * @return  string                The menu options HTML code
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @todo    Use the Html class instead
     */
    static function getMenuoptions($selectedId=0, $flagInactive=false)
    {
        if (empty(self::$arrLanguages)) self::init();
        $menuoptions = '';
        foreach (self::$arrLanguages as $id => $arrLanguage) {
            // Skip inactive ones if desired
            if (!$flagInactive && empty($arrLanguage['frontend']))
                continue;
            $menuoptions .=
                "<option value='$id'".
                ($selectedId == $id ? ' selected="selected"' : '').
                ">{$arrLanguage['name']}</option>\n";
        }
        return $menuoptions;
    }


    /**
     * Return the language ID for the ISO 639-1 code specified.
     *
     * If the code cannot be found, returns the default language.
     * If that isn't set either, returns the first language encountered.
     * If none can be found, returns null.
     * Note that you can supply the complete string from the Accept-Language
     * HTTP header.  This method will take care of chopping it into pieces
     * and trying to pick a suitable language.
     * However, it will not pick the most suitable one according to RFC2616,
     * but only returns the first language that fits.
     * @static
     * @param   string    $langCode         The ISO 639-1 language code
     * @return  mixed                       The language ID on success,
     *                                      null otherwise
     * @global  ADONewConnection
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getLangIdByIso639_1($langCode)
    {
        global $objDatabase;

        // Don't bother if the "code" looks like an ID already
        if (is_numeric($langCode)) return $langCode;

        // Something like "fr; q=1.0, en-gb; q=0.5"
        $arrLangCode = preg_split('/,\s*/', $langCode);
        $strLangCode = "'".join("','",
            preg_replace('/(?:-\w+)?(?:;\s*q(?:\=\d?\.?\d*)?)?/i',
                '', $arrLangCode))."'";
        $objResult = $objDatabase->Execute("
            SELECT id
              FROM ".DBPREFIX."languages
             WHERE lang IN ($strLangCode)
               AND frontend=1");
        if ($objResult && $objResult->RecordCount()) {
            return $objResult->fields['id'];
        }
        // The code was not found.  Pick the default.
        $objResult = $objDatabase->Execute("
            SELECT id
              FROM ".DBPREFIX."languages
             WHERE is_default='true'
               AND frontend=1");
        if ($objResult && $objResult->RecordCount()) {
            return $objResult->fields['id'];
        }
        // Still nothing.  Pick the first frontend language available.
        $objResult = $objDatabase->Execute("
            SELECT id
              FROM ".DBPREFIX."languages
             WHERE frontend=1");
        if ($objResult && $objResult->RecordCount()) {
            return $objResult->fields['id'];
        }
        // Pick the first language.
        $objResult = $objDatabase->Execute("
            SELECT id
              FROM ".DBPREFIX."languages
             WHERE frontend=1");
        if ($objResult && $objResult->RecordCount()) {
            return $objResult->fields['id'];
        }
        // Give up.
        return null;
    }


    /**
     * Return the language code from the database for the given ID
     *
     * Returns false on failure, or if the ID is invalid
     * @param   integer $langId         The language ID
     * @return  mixed                   The two letter code, or false
     * @static
     */
    static function getLanguageCodeById($langId)
    {
        if (empty(self::$arrLanguages)) self::init();
        return self::getLanguageParameter($langId, 'lang');
    }


    /**
     * Return the language ID for the given code
     *
     * Returns false on failure, or if the code is unknown
     * @param   string                    The two letter code
     * @return  integer   $langId         The language ID, or false
     * @static
     */
    static function getLanguageIdByCode($code)
    {
        if (empty(self::$arrLanguages)) self::init();
        foreach (self::$arrLanguages as $id => $arrLanguage) {
            if ($arrLanguage['lang'] == $code) return $id;
        }
        return false;
    }


    /**
     * Return the fallback language ID for the given ID
     *
     * Returns false on failure, or if the ID is invalid
     * @param   integer $langId         The language ID
     * @return  integer   $langId         The language ID, or false
     * @static
     */
    static function getFallbackLanguageIdById($langId)
    {
        if (empty(self::$arrLanguages)) self::init();
        if ($langId == self::getDefaultLangId()) return false;
        $fallback_lang = self::getLanguageParameter($langId, 'fallback');
        if ($fallback_lang == 0) $fallback_lang = intval(self::getDefaultLangId());;
        if ($langId == $fallback_lang) return false;
        return $fallback_lang;
    }

    /**
     * Builds an array mapping language ids to fallback language ids.
     *
     * @return array ( language id => fallback language id )
     */
    static function getFallbackLanguageArray() {
        global $objDatabase;
        $ret = array();

        $defaultLangId = intval(self::getDefaultLangId());

        $query = "SELECT id, fallback FROM ".DBPREFIX."languages where fallback IS NOT NULL";
        $rs = $objDatabase->Execute($query);

        while(!$rs->EOF) {
            $langId = intval($rs->fields['id']);
            $fallbackLangId = intval($rs->fields['fallback']);

            //explicitly overwrite null (default) with the default language id
            if($fallbackLangId === 0) {
                $fallbackLangId = $defaultLangId;
            }

            if ($langId == $fallbackLangId || $langId == self::getDefaultLangId()) {
                $fallbackLangId = false;
            }
            $ret[$langId] = $fallbackLangId;
            $rs->MoveNext();
        }

        return $ret;
    }
}
