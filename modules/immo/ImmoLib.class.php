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
 * Immo
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_immo
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Immo
 *
 * Immo module backend
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_immo
 */
class ImmoLib{

    var $_objTpl;


    var $arrFields = array(
       'price'             => 'Verkaufspreis',
       'short_desc'        => 'Kurzbeschreibung',
       'extras'            => 'Extras Übersicht',
    );

    var $arrPriceFields = array(
       'Verkaufspreis', 'Verkaufspreis EHP', 'Amtlicher Wert',
       'Amtlicher Wert EHP', 'Eigenmietwert Direkte Bundessteuer',
       'Eigenmietwert EHP', 'Gebäudeversicherung', 'Heiz- und NK, jährlich',
       'Erneuerungsfonds, jährlich', 'Eigenmietwert Kantons-/ Gemeindesteuer',
       'Handänderungskosten, 2.7% v. VP (Notariatskosten, Staatsabgaben, Grundbuchgebühren)'
    );

    var $arrFieldLayout = array(
       'standortplan'  =>
            array(  'googlemap'),
       'vorzüge'       =>
            array(  'Vorzüge Detailansicht'),
       'gebäudedaten'  =>
            array(  'Baujahr',
                    'Grundstücksfläche',
                    'Gesamtwohnfläche',
                    'Nettowohnfläche',
                    'Amtlicher Wert',
                    'Amtlicher Wert EHP',
                    'Eigenmietwert Kantons-/ Gemeindesteuer',
                    'Eigenmietwert Direkte Bundessteuer',
                    'Eigenmietwert EHP',
                    'Gebäudeversicherung',
                    'Heiz- und NK, jährlich',
                    'Erneuerungsfonds, jährlich',
                    'Weitere Informationen'),
        'zusatzfelder'  =>
            array(  'Zustand',
                    'Aussicht',
                    'Schulen'),
        'angebot'       =>
            array(  'Verkaufspreis',
                    'Verkaufspreis EHP',
                    'Handänderungskosten, 2.7% v. VP (Notariatskosten, Staatsabgaben, Grundbuchgebühren)'),
        'pläne'         =>
            array(  'Verkaufsdokumentation',
                    'Detailangaben',
                    'Angaben Wohnfläche',
                    'Grundriss EG',
                    'Grundriss 1. OG',
                    'Grundriss 2. OG',
                    'Grundriss DG',
                    'Grundriss 1. UG',
                    'Situationsplan')
    );

    /**
     * Name of the theme directory, which shall be used. (see templates/frontend_images_viewer.html for example)
     *
     * @var string
     */
    var $_styleName = 'immo';


    /**
     * available categories for the search mask
     *
     * @var array
     */
    var $categories = array('alle Häuser', 'alle Wohnungen', 'Einfamilienhaus', 'Reihenendhaus',
                            'Doppelhaus', 'Mehrfamilienhaus', 'Bauernhaus', 'Stadthaus', 'alle Objekte');

    /**
     * Array holding all the fields information
     *
     * @var array
     */
    var $fieldNames = array();


    /**
     * array of available languages
     * key         -> id
     * value     -> _ARRAYLANG language variablename
     * @var array
     */
    var $languages = array();


    /**
     * framework file object
     *
     * @var object
     */
    var $_objFile;


    /**
     * number of languages
     *
     * @var int
     */
    var $langCount = 0;


    /**
     * holds the field ID of the last field found by ImmoLib::_getFieldFromText()
     *
     * @var int
     */
    var $_currFieldID;


    /**
     * relative path to standard image in image-fields
     *
     * @var str
     */
    var $noImage = '../core/Core/View/Media/icons/.gif';


    /**
     * Array with the settings values
     */
    var $arrSettings;


    /**
     * standard line break (windows)
     *
     * @var string (escaped)
     */
    var $_lineBreak = "\r\n";


    /**
     * Array holding number of field per type
     * $_fieldCount[$type]['count']
     *
     * @var array
     */
    var $_fieldCount;


    /**
     * Fields used in basic Data (i.e. which should not be displayed in any text-, img-, or link-rows)
     *
     * @var unknown_type
     */
    var $_usedFields = array('Kopfzeile', 'Adresse', 'Ort', 'Preis', 'Beschreibung', 'Headline',
                            'Aufzählung1',    'Aufzählung2', 'Aufzählung3', 'Übersichtsbild', 'Link auf Homepage', 'Anzahl Zimmer' );

    /**
    * Constructor
    */
    function ImmoLib()
    {
        $this->__construct();
    }

    /**
    * PHP5 constructor
    */
    function __construct()
    {

        define('DS', DIRECTORY_SEPARATOR);
        $this->_getLanguages();
        $this->_getSettings();
    }

    /**
     * Get Field Names
     *
     * Generates an array with field names
     *
     * @param $immoID ID of the object (only fetches rows of that object, all if omitted)
     * @param $count whether fieldtypes shall be count, if bigger than 0 (also specifies the frontend language)
     */
    function _getFieldNames($immoID = 0, $count = 0)
    {
        global $objDatabase;

        $objRS = $objDatabase->Execute("    SELECT id, field_id, lang_id, name
                                            FROM ".DBPREFIX."module_immo_fieldname");
        $allNames = array();
        if ($objRS != false) {
            while (!$objRS->EOF) {
                $allNames[] = array(
                    "id"        => $objRS->fields['id'],
                    "field_id"    => $objRS->fields['field_id'],
                    "lang_id"    => $objRS->fields['lang_id'],
                    "name"        => $objRS->fields['name']
                );
                $objRS->MoveNext();
            }
        }
        unset($objRS);
        $objRS = $objDatabase->Execute("SELECT id, type, `order`, `mandatory`
                                        FROM ".DBPREFIX."module_immo_field
                                        ORDER BY `order`");
        if($objRS !== false){
            while(!$objRS->EOF){
                $names = array();
                foreach($allNames as $key => $name) {
                    if ($name['field_id'] == $objRS->fields['id']) {
                        $names[$name['lang_id']] = $name['name'];
                    }
                }

                foreach ($this->languages as $langID => $language) {
                    $query = "    SELECT  id, immo_id, lang_id, field_id, fieldvalue, active
                                FROM ".DBPREFIX."module_immo_content
                                WHERE field_id = ".$objRS->fields['id']."
                                AND lang_id = $langID";
                    $query .= ($immoID > 0) ? " AND immo_id = $immoID LIMIT 1" : ' LIMIT 1';
                    $objRSContent = $objDatabase->Execute($query);
                    if($objRSContent !== false){
                        $content[$langID] = $objRSContent->fields['fieldvalue'];
                    }
                }

                $content['active'] = $objRSContent->fields['active'];

                $img = ($immoID > 0) ? $this->_getImageInfo($objRS->fields['id'], $immoID) : array('uri' => '');
                if($count > 0 && $content['active'] == 1 && trim($names[$count]) != '' && !in_array($names[$count] ,$this->_usedFields)){
                    switch($objRS->fields['type']){
                           case 'text':
                        case 'textarea':
                        case 'digits_only':
                        case 'price':
                            $this->_fieldCount['text']++;
                        break;

                        case 'img':
                            $this->_fieldCount['img']++;
                        break;

                        default:
                        break;
                    }
                }
                $this->fieldNames[$objRS->fields['id']] = array(
                    "type"        => $objRS->fields['type'],
                    "order"        => $objRS->fields['order'],
                    "names"        => $names,
                    'content'    => $content,
                    'img'        => $img['uri'],
                    'mandatory' => $objRS->fields['mandatory']
                );
                //print_r($this->fieldNames[$objRS->fields['id']]);
                $objRS->MoveNext();
            }
        }
    }

    function _getImageInfo($fieldID, $immoID)
    {
        global $objDatabase;

        $query = "    SELECT id, field_id, uri
                    FROM ".DBPREFIX."module_immo_image
                    WHERE field_id = $fieldID
                    AND immo_id = $immoID
                    LIMIT 1";
        $objRS = $objDatabase->Execute($query);
        if($objRS !== false){
            return $objRS->fields;
        }
    }


    /**
     * build array with languages
     * key         -> id
     * value    -> _ARRAYLANG language variablename
     *
     * @return unknown
     */

    function _getLanguages()
    {
        global $objDatabase;
        $query = "    SELECT id, language
                        FROM ".DBPREFIX."module_immo_languages";
        $objRS = $objDatabase->Execute($query);
        if($objRS !== false){
            while(!$objRS->EOF){
                $this->languages[$objRS->fields['id']] = $objRS->fields['language'];
                $this->langCount++;
                $objRS->MoveNext();
            }
            return true;
        }
        return false;
    }

    /**
     * return field attributes by text
     *
     * @param string $str name of the field to fetch
     * @param string $type type of return: content, names, img, active, key
     * @return ID on success, false on failure
     */

    function _getFieldFromText($str, $type = 'content')
    {
        array_walk($this->fieldNames, array($this, '_searchField'), $str);
        if($type == 'content'){
            return $this->fieldNames[$this->_currFieldID]['content'][$this->frontLang];
        }else if ($type == 'names'){
            return $this->fieldNames[$this->_currFieldID]['names'][$this->frontLang];
        }else if ($type == 'img'){
            return $this->fieldNames[$this->_currFieldID]['img'];
        }else if ($type == 'active'){
            return $this->fieldNames[$this->_currFieldID]['content']['active'];
        }else if ($type == 'key'){
            return $this->_currFieldID;
        }
        return false;
    }

    /**
     * search field and set key on match
     *
     * @param array $field
     * @param int $key
     * @param string $fieldName
     */
    function _searchField($field, $key, $fieldName)
    {
        if(trim(strtolower($field['names'][1])) == trim(strtolower($fieldName))){
            $this->_currFieldID = $key;
        }
    }

    function _getSettings()
    {
        global $objDatabase;
        $this->arrSettings = array();
        $query = "  SELECT `setname`, `setvalue`
                    FROM ".DBPREFIX."module_immo_settings
                    WHERE `status` = '1'";
        $objResult = $objDatabase->Execute($query);
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrSettings[$objResult->fields['setname']] = $objResult->fields['setvalue'];
                $objResult->MoveNext();
            }
        }
    }

    /**
     * check if $_CONFIG['domainUrl'] has a subdomain
     * return domainUrl plus subdomain, if domainUrl has no subdomain
     * otherwise return domainUrl minus subdomain
     *
     * @param  string domain
     * @return string domain2
     */

    function _getDomain($domain)
    {
        $dparts = explode(".", $domain);
        switch(count($dparts)){
            case 1:
                return false;
            break;
            case 2:
                return 'www.'.$domain;
            break;
            case 3:
                if($dparts[0] == 'www'){
                    return substr($domain, 4);
                }
            break;
            default:
                return false;
            break;
        }
    }

    function arrStrToLower(&$item, $key)
    {
       $item = strtolower($item);
    }

    function filterImmoType($var)
    {
        if (substr($var, 0, 20) == "TXT_IMMO_OBJECTTYPE_") {
            return true;
        }
        return false;
    }
}


?>
