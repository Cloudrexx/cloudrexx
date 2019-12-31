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
 * Media Directory Inputfield Google Map Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield Google Map Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldGoogleMap extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary implements Inputfield
{
    public $arrPlaceholders = array(
        'TXT_MEDIADIR_INPUTFIELD_NAME',
        'MEDIADIR_INPUTFIELD_VALUE',
        'MEDIADIR_INPUTFIELD_LINK',
        'MEDIADIR_INPUTFIELD_LINK_HREF',
        'MEDIADIR_INPUTFIELD_MAP_LAT',
        'MEDIADIR_INPUTFIELD_MAP_LONG',
    );

    private $imagePath;
    private $imageWebPath;

    /**
     * Constructor
     */
    function __construct($name)
    {
        $this->imagePath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesMediaDirPath() . '/';
        $this->imageWebPath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesMediaDirWebPath() . '/';
        parent::__construct('.', $name);
    }

    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase,$_CORELANG, $_ARRAYLANG, $objInit, $_CONFIG;

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                $intId = intval($arrInputfield['id']);
                $strValueStreet = '';
                $strValueCity = '';
                $strValueZip = '';
                parent::getSettings();

                if(isset($intEntryId) && $intEntryId != 0) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                        LIMIT 1
                    ");
                    $strValue  = htmlspecialchars($objInputfieldValue->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                    $arrValues = explode(',', $strValue);

                    $strValueLat = empty($arrValues[0]) ? 0 : $arrValues[0];
                    $strValueLon = empty($arrValues[1]) ? 0 : $arrValues[1];
                    $strValueZoom = empty($arrValues[2]) ? 0 : $arrValues[2];
                    $strValueStreet = empty($arrValues[3]) ? '' : $arrValues[3];
                    $strValueCity = empty($arrValues[4]) ? '' : $arrValues[4];
                    $strValueZip = empty($arrValues[5]) ? '' : $arrValues[5];

                } else {
                    $objSettingsRS = $objDatabase->Execute("SELECT value FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_settings WHERE name='settingsGoogleMapStartposition'");
                    if ($objSettingsRS !== false) {
                        $strValue = htmlspecialchars($objSettingsRS->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                    }
                    $arrValues = explode(',', $strValue);

                    $strValueLat = empty($arrValues[0]) ? 0 : $arrValues[0];
                    $strValueLon = empty($arrValues[1]) ? 0 : $arrValues[1];
                    $strValueZoom = empty($arrValues[2]) ? 0 : $arrValues[2];
                }

                $strMapId       = $this->moduleNameLC.'Inputfield_'.$intId.'_map';
                $strLonId       = $this->moduleNameLC.'Inputfield_'.$intId.'_lon';
                $strLatId       = $this->moduleNameLC.'Inputfield_'.$intId.'_lat';
                $strZoomId      = $this->moduleNameLC.'Inputfield_'.$intId.'_zoom';
                $strStreetId    = $this->moduleNameLC.'Inputfield_'.$intId.'_street';
                $strZipId       = $this->moduleNameLC.'Inputfield_'.$intId.'_zip';
                $strCityId      = $this->moduleNameLC.'Inputfield_'.$intId.'_city';
                $strKey         = $_CONFIG['googleMapsAPIKey'];

                if($objInit->mode == 'backend') {
                    $strInputfield  = '<table cellpadding="0" cellspacing="0" border="0" class="'.$this->moduleNameLC.'TableGoogleMap">';
                    $strInputfield .= '<tr><td style="border: 0px;">'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_STREET'].':&nbsp;&nbsp;</td><td style="border: 0px; padding-bottom: 2px;"><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][street]" id="'.$strStreetId.'" value="'.$strValueStreet.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td style="border: 0px;">'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_CITY'].':&nbsp;&nbsp;</td><td style="border: 0px; padding-bottom: 2px;"><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][place]" id="'.$strZipId.'"  value="'.$strValueZip.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td style="border: 0px;">'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_ZIP'].':&nbsp;&nbsp;</td><td style="border: 0px; padding-bottom: 2px;"><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][zip]" id="'.$strCityId.'" value="'.$strValueCity.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td style="border: 0px;"><br /></td><td style="border: 0px;"><input type="button" onclick="searchAddress();" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][search]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_search" value="'.$_CORELANG['TXT_SEARCH'].'" /></td></tr>';
                    $strInputfield .= '<tr><td style="border: 0px;" coldpan="2"><br /></td></tr>';
                    $strInputfield .= '<tr><td style="border: 0px;">'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_LON'].':&nbsp;&nbsp;</td><td style="border: 0px; padding-bottom: 2px;"><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][lon]" id="'.$strLonId.'"  value="'.$strValueLon.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td style="border: 0px;">'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_LAT'].':&nbsp;&nbsp;</td><td style="border: 0px; padding-bottom: 2px;"><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][lat]" id="'.$strLatId.'" value="'.$strValueLat.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td style="border: 0px;">'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_ZOOM'].':&nbsp;&nbsp;</td><td style="border: 0px; padding-bottom: 2px;"><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][zoom]" id="'.$strZoomId.'" value="'.$strValueZoom.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '</table><br />';
                    $strInputfield .= '<div id="'.$strMapId.'" style="border: solid 1px #0A50A1; width: 418px; height: 300px;"></div>';

                } else {
                    $strInputfield  = '<div class="'.$this->moduleNameLC.'GoogleMap" style="float: left; height: auto ! important;">';
                    $strInputfield .= '<fieldset class="'.$this->moduleNameLC.'FieldsetGoogleMap">';
                    $strInputfield .= '<legend>'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_SEARCH_ADDRESS'].'</legend>';
                    $strInputfield .= '<table cellpadding="0" cellspacing="0" border="0" class="'.$this->moduleNameLC.'TableGoogleMap">';
                    $strInputfield .= '<tr><td>'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_STREET'].':&nbsp;&nbsp;</td><td><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][street]" id="'.$strStreetId.'" class="'.$this->moduleNameLC.'InputfieldGoogleMapLarge" value="" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td>'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_CITY'].':&nbsp;&nbsp;</td><td><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][place]" id="'.$strZipId.'" class="'.$this->moduleNameLC.'InputfieldGoogleMapLarge" value="" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td>'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_ZIP'].':&nbsp;&nbsp;</td><td><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][zip]" id="'.$strCityId.'" class="'.$this->moduleNameLC.'InputfieldGoogleMapSmall" value="" onfocus="this.select();" /><input type="button" onclick="searchAddress();" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][search]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_search" value="'.$_CORELANG['TXT_SEARCH'].'" /></td></tr>';
                    $strInputfield .= '<tr><td coldpan="2"><br /></td></tr>';
                    $strInputfield .= '<tr><td>'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_LON'].':&nbsp;&nbsp;</td><td><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][lon]" id="'.$strLonId.'" class="'.$this->moduleNameLC.'InputfieldGoogleMapLarge" value="'.$strValueLon.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td>'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_LAT'].':&nbsp;&nbsp;</td><td><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][lat]" id="'.$strLatId.'" class="'.$this->moduleNameLC.'InputfieldGoogleMapLarge" value="'.$strValueLat.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '<tr><td>'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLE_MAP_ZOOM'].':&nbsp;&nbsp;</td><td><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][zoom]" id="'.$strZoomId.'" class="'.$this->moduleNameLC.'InputfieldGoogleMapSmall" value="'.$strValueZoom.'" onfocus="this.select();" /></td></tr>';
                    $strInputfield .= '</table>';
                    $strInputfield .= '</fieldset>';
                    $strInputfield .= '</div>';
                    $strInputfield .= '<div class="'.$this->moduleNameLC.'GoogleMap" style="float: left; height: auto ! important;">';
                    $strInputfield .= '<div id="'.$strMapId.'" class="map"></div>';
                    $strInputfield .= '</div>';
                }

                $strInputfield .= <<<EOF
<script src="https://maps.googleapis.com/maps/api/js?key=$strKey&sensor=false&v=3"></script>
<script>
//<![CDATA[
var elZoom, elLon, elLat, elStreet, elZip, elCity;
var map, marker, geocoder, old_marker = null;

function initialize() {
    elZoom = document.getElementById("$strZoomId");
    elLon = document.getElementById("$strLonId");
    elLat = document.getElementById("$strLatId");

    elStreet = document.getElementById("$strStreetId");
    elZip = document.getElementById("$strZipId");
    elCity = document.getElementById("$strCityId");

    map = new google.maps.Map(document.getElementById("$strMapId"));

    map.setCenter(new google.maps.LatLng($strValueLat, $strValueLon));
    map.setZoom($strValueZoom);
    map.setMapTypeId(google.maps.MapTypeId.ROADMAP);

    if($strValueLon != 0 && $strValueLon != 0) {
        marker = new google.maps.Marker({
            map: map,
            draggable:true,
            animation: google.maps.Animation.DROP
        });
        setPosition(new google.maps.LatLng($strValueLat, $strValueLon));

        google.maps.event.addListener(marker, 'dragend', function(event){
            if(event.latLng.lat()){
               elLat.value = event.latLng.lat();
            }
            if(event.latLng.lng()){
               elLon.value = event.latLng.lng();
            }
            map.setCenter(new google.maps.LatLng(event.latLng.lat(), event.latLng.lng()));
        });
    }

    geocoder = new google.maps.Geocoder();

    google.maps.event.addListener(map, "click", function(event) {
        setPosition(event.latLng);
    });

    google.maps.event.addListener(map, "idle", function() {
        elZoom.value = map.getZoom();
    });
}

function searchAddress() {
    var address =  elStreet.value + " " + elZip.value + " " + elCity.value;

    if (geocoder) {
        geocoder.geocode( { 'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                setPosition(results[0].geometry.location);
                map.setCenter(results[0].geometry.location);
            }
        });
    }
}

function setPosition(position) {
    if (!marker) {
        marker = new google.maps.Marker({
            map: map
        });
    }
    marker.setPosition(position);
    elZoom.value = map.getZoom();
    elLon.value = position.lng();
    elLat.value = position.lat();
}

google.maps.event.addDomListener(window, 'load', initialize);
//]]>
</script>
EOF;
                return $strInputfield;

                break;
        }
    }



    function saveInputfield($intInputfieldId, $arrValue, $langId = 0)
    {
        global $objInit;

        $lat  = floatval($arrValue['lat']);
        $lon  = floatval($arrValue['lon']);
        $zoom = floatval($arrValue['zoom']);
        $street = $arrValue['street'];
        $zip = $arrValue['zip'];
        $city = $arrValue['place'];
        $strValue = $lat.','.$lon.','.$zoom.','.$street.','.$zip.','.$city;

        return $strValue;
    }

    function deleteContent($intEntryId, $intIputfieldId)
    {
        global $objDatabase;

        $objDeleteInputfield = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields WHERE `entry_id`='".intval($intEntryId)."' AND  `field_id`='".intval($intIputfieldId)."'");

        if($objDeleteInputfield !== false) {
            return true;
        } else {
            return false;
        }
    }



    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
		global $_ARRAYLANG;

        $strValue = static::getRawData($intEntryId, $arrInputfield, $arrTranslationStatus);
        $strValue = htmlspecialchars($strValue, ENT_QUOTES, CONTREXX_CHARSET);

        $arrValues = explode(',', $strValue);

        $strValueLat = $arrValues[0];
        $strValueLon = $arrValues[1];
        $strValueZoom = $arrValues[2];
        $strValueLink = '<a href="http://maps.google.com/maps?q='.$strValueLat.','.$strValueLon.'" target="_blank">'.$_ARRAYLANG['TXT_MEDIADIR_GOOGLEMAPS_LINK'].'</a>';
        $strValueLinkHref = 'http://maps.google.com/maps?q='.$strValueLat.','.$strValueLon;

        $intId = intval($arrInputfield['id']);

        if(!empty($strValue)) {
            $objGoogleMap = new \googleMap();
            $objGoogleMap->setMapId($this->moduleNameLC.'Inputfield_'.$intId.'_'.$intEntryId.'_map');
            $objGoogleMap->setMapStyleClass('map');
            $objGoogleMap->setMapZoom($strValueZoom);
            $objGoogleMap->setMapCenter($strValueLon, $strValueLat);

            $objGoogleMap->setMapIndex($intId.'_'.$intEntryId);

            $objGoogleMap->addMapMarker($intId, $strValueLon, $strValueLat, null, true);

            $arrContent['TXT_'.$this->moduleLangVar.'_INPUTFIELD_NAME'] = htmlspecialchars($arrInputfield['name'][0], ENT_QUOTES, CONTREXX_CHARSET);
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = $objGoogleMap->getMap();
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_LINK'] = $strValueLink;
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_LINK_HREF'] = $strValueLinkHref;
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_MAP_LAT'] = $strValueLat;
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_MAP_LONG'] = $strValueLon;
        } else {
            $arrContent = null;
        }

        return $arrContent;
    }

    function getRawData($intEntryId, $arrInputfield, $arrTranslationStatus) {
        global $objDatabase;

        $intId = intval($arrInputfield['id']);

        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".$intEntryId."
            LIMIT 1
        ");

        return $objInputfieldValue->fields['value'];
    }


    function getJavascriptCheck()
    {
        parent::getSettings();

        $fieldName = $this->moduleNameLC."Inputfield_";
        $strJavascriptCheck = <<<EOF

            case 'google_map':
                value_lon = document.getElementById('$fieldName' + field + '_lon').value;
                value_lat = document.getElementById('$fieldName' + field + '_lat').value;
                value_zoom = document.getElementById('$fieldName' + field + '_zoom').value;

                if ((value_lon == "" || value_lat == "" || value_zoom == "") && isRequiredGlobal(inputFields[field][1], value)) {
                    isOk = false;
                    if (value_lon == "" && isRequiredGlobal(inputFields[field][1], value)) {
                        document.getElementById('$fieldName' + field + '_lon').style.border = "#ff0000 1px solid";
                    }

                    if (value_lat == "" && isRequiredGlobal(inputFields[field][1], value)) {
                        document.getElementById('$fieldName' + field + '_lat').style.border = "#ff0000 1px solid";
                    }

                    if (value_zoom == "" && isRequiredGlobal(inputFields[field][1], value)) {
                        document.getElementById('$fieldName' + field + '_zoom').style.border = "#ff0000 1px solid";
                    }
                }  else {
                    document.getElementById('$fieldName' + field + '_lon').style.borderColor = '';
                    document.getElementById('$fieldName' + field + '_lat').style.borderColor = '';
                    document.getElementById('$fieldName' + field + '_zoom').style.borderColor = '';
                }

                break;

EOF;
        return $strJavascriptCheck;
    }


    function getFormOnSubmit($intInputfieldId)
    {
        return null;
    }
}
