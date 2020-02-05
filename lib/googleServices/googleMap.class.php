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
 * Google Map
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * Google Map
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class googleMap
{
    private $apiKey;

    private $mapModus = 'overview';
    private $mapDimensions;
    private $mapZoom = 1;
    private $mapCenter = 'var center = new google.maps.LatLng(0, 0)';
    private $mapId = 'googleMap';
    private $mapType = 'ROADMAP';
    private $mapClass;
    private $mapMarkers = array();
    private $mapIndex = 1;

    /**
     * Constructor
     */
    function __construct()
    {
        global $_CONFIG;

        $this->apiKey = $_CONFIG['googleMapsAPIKey'];
    }


    function setMapModus($modus)
    {
        $this->mapModus = $modus;
    }
    
    function setMapIndex($index)
    {
        $this->mapIndex = $index;
    }
    
    function getMapIndex()
    {
        return $this->mapIndex;
    }

    function setMapId($id)
    {
        $this->mapId = $id;
    }

    /**
     * Get the ID of the GoogleMap
     */
    public function getMapId() {
        return $this->mapId;
    }



    function setMapDimensions($width, $height)
    {
        $this->mapDimensions = 'style="width: '.intval($width).'px; height: '.intval($height).'px;"';
    }



    function setMapStyleClass($class)
    {
        $this->mapClass = 'class="'.$class.'"';
    }



    function setMapZoom($zoom)
    {
        $this->mapZoom = intval($zoom);
    }



    function setMapCenter($lon, $lat)
    {
        $this->mapCenter = 'var center = new google.maps.LatLng('.$lat.', '.$lon.')';
    }



    function setMapType($type)
    {
        /*
        Types:
        [0] map
        [1] satellite
        [2] hybrid
        */

        switch ($type) {
            case 1:
                $this->mapType = 'SATELLITE';
                break;
            case 2:
                $this->mapType = 'HYBRID';
                break;
            case 0:
            default:
                $this->mapType = 'ROADMAP';
                break;
        }
    }



    function addMapMarker($id, $lon, $lat, $info = '', $hideInfo=true, $click=null, $mouseover=null, $mouseout=null, $icon=null)
    {
        $this->mapMarkers[$id]['lon'] = $lon;
        $this->mapMarkers[$id]['lat'] = $lat;
        $this->mapMarkers[$id]['info'] = $info;
        $this->mapMarkers[$id]['hideInfo'] = $hideInfo;
        $this->mapMarkers[$id]['click'] = $click;
        $this->mapMarkers[$id]['mouseover'] = $mouseover;
        $this->mapMarkers[$id]['mouseout'] = $mouseout;
        $this->mapMarkers[$id]['icon'] = $icon;
    }


    /**
     * Load the registered map markers into JavaScript
     */
    protected function loadMapMarkers() {
        $markers = array();
        foreach ($this->mapMarkers as $id => $marker) {
            // latitude and longitude can be positive or negative
            if($marker['lon'] != 0 && $marker['lat'] != 0) {
                $markers[$id] = $marker;
            }
        }

        \ContrexxJavascript::getInstance()->setVariable('map_'.$this->mapIndex.'_markers', $markers, $this->mapId);
    }



    function getMap()
    {
        if($this->mapModus == 'search') {
            $map = self::getSearchMap();
        } else {
            $map = self::getOverviewMap();
        }

        return $map;
    }


    private function getOverviewMap()
    {
        \JS::activate('cx');

        $layer = '<div id="'.$this->mapId.'" '.$this->mapClass.' '.$this->mapDimensions.'></div>';

        $this->loadMapMarkers();
        $map = "map_".$this->mapIndex;

        $layer .= <<<EOF
<script src="https://maps.googleapis.com/maps/api/js?key=$this->apiKey&sensor=false&v=3"></script>
<script>
//<![CDATA[
var $map;

cx.ready(function() {
    $map = new google.maps.Map(document.getElementById("$this->mapId"));
    cx.variables.set('map_{$this->mapIndex}', $map, '$this->mapId');
    $this->mapCenter

    $map.setCenter(center);
    $map.setZoom($this->mapZoom);

    $map.setMapTypeId(google.maps.MapTypeId.$this->mapType);
    infoWindow = new google.maps.InfoWindow();
    cx.variables.set('map_{$this->mapIndex}_infoWindow', infoWindow, '$this->mapId');

    var mapMarkers = cx.variables.get('map_{$this->mapIndex}_markers', '$this->mapId');
    for (var key in mapMarkers) {
        if (!mapMarkers.hasOwnProperty(key)) {
            continue;
        }

        mapMarker = mapMarkers[key];
        mapMarker.marker = new google.maps.Marker({
            position: new google.maps.LatLng(mapMarker.lat, mapMarker.lon),
            map: map_$this->mapIndex
        });

        if (mapMarker.click) {
            google.maps.event.addListener(mapMarker.marker, 'click', Function(mapMarker.click));
        }

        if (mapMarker.mouseover) {
            google.maps.event.addListener(mapMarker.marker, 'mouseover', mapMarker.mouseover);
        }

        if (mapMarker.mouseout) {
            google.maps.event.addListener(mapMarker.marker, 'mouseout', mapMarker.mouseout);
        }

        if (!mapMarker.hideInfo) {
            infoWindow.setContent(mapMarker.info);
            infoWindow.open('map_$this->mapIndex', mapMarker.marker);
        }
    }
});
//]]>
</script>
EOF;
        return $layer;
    }

    /**
     * Get a script tag to instantiate a Google Map with search functions. To
     * use the search fields, define a \ContrexxJavascript variable with an array
     * and the search fields IDs. For example:
     * array(
     *     'long' => 'long-search-field-id',
     *     'lat' => 'lat-search-field-id',
     *     'zoom' => 'zoom-search-field-id',
     *     'street' => 'street-search-field-id',
     *     'zip' => 'zip-search-field-id,
     *     'city' => 'city-search-field-id
     * );
     *
     * @return string
     */
    private function getSearchMap()
    {
        \JS::activate('cx');

        $layer = '<div id="'.$this->mapId.'" '.$this->mapClass.' '.$this->mapDimensions.'></div>';

        $this->loadMapMarkers();
        $map = 'map_'.$this->mapIndex;

        $layer .= <<<EOF
<script src="https://maps.googleapis.com/maps/api/js?key=$this->apiKey&sensor=false&v=3"></script>
<script>
//<![CDATA[
var $map;

cx.ready(function() {
    const elZoom = document.getElementById(
        cx.variables.get('map_search_field_ids', 'map_$this->mapIndex').zoom
    );
    const elLon = document.getElementById(
        cx.variables.get('map_search_field_ids', 'map_$this->mapIndex').long
    );
    const elLat = document.getElementById(
        cx.variables.get('map_search_field_ids', 'map_$this->mapIndex').lat
    );

    var $map = new google.maps.Map(document.getElementById("$this->mapId"));
    cx.variables.set('map_{$this->mapIndex}', $map, '$this->mapId');
    $this->mapCenter

    $map.setCenter(center);
    
    $map.setZoom($this->mapZoom);

    $map.setMapTypeId(google.maps.MapTypeId.$this->mapType);

    var mapMarkers = cx.variables.get('map_{$this->mapIndex}_markers', '$this->mapId');
    for (var key in mapMarkers) {
        if (!mapMarkers.hasOwnProperty(key)) {
            continue;
        }

        var mapMarker = mapMarkers[key];
        mapMarker.marker = new google.maps.Marker({
            position: new google.maps.LatLng(mapMarker.lat, mapMarker.lon),
            map: map_$this->mapIndex,
            draggable:true,
            animation: google.maps.Animation.DROP
        });
        
        google.maps.event.addListener(mapMarker.marker, 'dragend', function(event) {
            if (event.latLng.lat()) {
               elLat.value = event.latLng.lat();
            }
            if(event.latLng.lng()){
               elLon.value = event.latLng.lng();
            }
            $map.setCenter(new google.maps.LatLng(event.latLng.lat(), event.latLng.lng()));
        });
        
        // Use the same ID for the search btn as for the marker
        document.getElementById(key).addEventListener('click', function() {
            const elStreet = document.getElementById(
                cx.variables.get('map_search_field_ids', 'map_$this->mapIndex').street
            );
            const elZip = document.getElementById(
                cx.variables.get('map_search_field_ids', 'map_$this->mapIndex').zip
            );
            const elCity = document.getElementById(
                cx.variables.get('map_search_field_ids', 'map_$this->mapIndex').city
            );
            var address =  elStreet.value + " " + elZip.value + " " + elCity.value + " CH";
        
            var geocoder = new google.maps.Geocoder();
            
            if (geocoder) {
                geocoder.geocode( { 'address': address}, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        setPosition(results[0].geometry.location, $map, '$this->mapIndex', mapMarker.marker);
                        $map.setCenter(results[0].geometry.location);
                    }
                });
            }
        });
        
        google.maps.event.addListener($map, "click", function(event) {
            setPosition(event.latLng, $map, '$this->mapIndex', mapMarker.marker);
        });

        // in selection mode, we want only one marker.
        // therefore, do not process any more markers
        break;
    }

    google.maps.event.addListener($map, "idle", function() {
        elZoom.value = $map.getZoom();
    });
    
});

function setPosition(position, map, mapIndex, marker) {
    const elZoom = document.getElementById(
        cx.variables.get('map_search_field_ids', 'map_' + mapIndex).zoom
    );
    const elLon = document.getElementById(
        cx.variables.get('map_search_field_ids', 'map_' + mapIndex).long
    );
    const elLat = document.getElementById(
        cx.variables.get('map_search_field_ids', 'map_' + mapIndex).lat
    );
    if (!marker) {
        marker = new google.maps.Marker({
            map: map
        });
    }
    marker.set('position', position);
    elZoom.value = map.getZoom();
    elLon.value = position.lng();
    elLat.value = position.lat();
}
//]]>
</script>
EOF;
        return $layer;
    }

}
