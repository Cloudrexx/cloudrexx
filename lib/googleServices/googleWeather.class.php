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
 * Google Weather
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * Google Weather
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class googleWeather
{
    private $weatherLanguage = 'de';
    private $weatherLocation = 'Thun';
    private $weatherId = 'googleWeather';
    private $weatherClass;
    private $weatherForecastDays = 4;
    private $weatherShowTitle = true;

    /**
     * Constructor
     */
    function __construct()
    {
        //nothing...
    }



    function setWeatherId($id)
    {
        $this->weatherId = 'id="'.$id.'"';
    }



    function setWeatherStyleClass($class)
    {
        $this->weatherClass = 'class="'.$class.'"';
    }



    function setWeatherShowTitle($status)
    {
        $this->weatherShowTitle = $status;
    }



    function setWeatherForecastDays($days)
    {
        if(intval($days) > 4) {
            $days = 4;
        }
        $this->weatherForecastDays = intval($days);
    }



    function setWeatherLanguage($langId) {
        $arrLang = array(
            1 =>  'de',
            2 =>  'en',
            3 =>  'fr',
            4 =>  'it',
            5 =>  'dk',
            6 =>  'ru',
        );

        if(key_exists(intval($langId), $arrLang)) {
            $this->weatherLanguage = $arrLang[intval($langId)];
        } else {
            $this->weatherLanguage = $arrLang[2];
        }
    }



    function setWeatherLocation($location) {
        $location = urldecode($location);
        $replace = array('' => '&Auml;', '' => '&Ouml;', '' => '&Uuml;', '' => '&auml;', '' => '&ouml;', '' => '&uuml;');

        foreach ($replace as $key => $val) {
            $location = str_replace($key, $val, $location);
        }

        $location = urlencode($location);

        $this->weatherLocation = $location;
    }



    private function loadWeatherData() {
        $url     = 'http://www.google.com/ig/api?hl=' . $this->weatherLanguage . '&weather=' . $this->weatherLocation;
        $file    = @file_get_contents($url);
        $file    = utf8_encode($file);
        $googleWeather = simplexml_load_string($file);

        unset($url, $file);

        $data             = array();
        $data['current']  = array();
        $data['forecast'] = array();


        if (isset($googleWeather->weather->forecast_information)) {
            $data['city']                     = htmlentities($googleWeather->weather->forecast_information->postal_code->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
            $data['current']['temp']          = htmlentities($googleWeather->weather->current_conditions->temp_c->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
            $data['current']['humidity']      = htmlentities($googleWeather->weather->current_conditions->humidity->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
            $data['current']['wind']          = htmlentities($googleWeather->weather->current_conditions->wind_condition->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);

            for($i=0; $i < $this->weatherForecastDays; $i++){
                $data['forecast'][$i]['day']       = htmlentities($googleWeather->weather->forecast_conditions[$i]->day_of_week->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
                $data['forecast'][$i]['minTemp']   = htmlentities($googleWeather->weather->forecast_conditions[$i]->low->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
                $data['forecast'][$i]['maxTemp']   = htmlentities($googleWeather->weather->forecast_conditions[$i]->high->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
                $data['forecast'][$i]['icon']      = htmlentities($googleWeather->weather->forecast_conditions[$i]->icon->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
                $data['forecast'][$i]['condition'] = htmlentities($googleWeather->weather->forecast_conditions[$i]->condition->attributes()->data,  ENT_QUOTES, CONTREXX_CHARSET);
            }

            unset($googleWeather);

            return $data;
        } else {
            return false;
        }
    }



    function getWeather() {
        $weatherData = self::loadWeatherData();

        if($weatherData !== false) {
            for($i=0; $i < $this->weatherForecastDays; $i++){
                $width = round(100/$this->weatherForecastDays,0);
                $forecast .= "
                    <div style=\"float: left; width: ".$width."%; height: auto;\">
                        <b>".$weatherData['forecast'][$i]['day']."</b><br />
                        <img src=\"http://www.google.com/".$weatherData['forecast'][$i]['icon']."\" width=\"40\" height=\"40\" alt=\"".$weatherData['forecast'][$i]['day']."\" /><br />
                        ".$weatherData['forecast'][$i]['maxTemp']."&deg; | ".$weatherData['forecast'][$i]['minTemp']."&deg;
                    </div>
                ";
            }

            if ($this->weatherShowTitle) {
                $title = "<h2>".$this->weatherLocation."</h2>";
            } else {
                $title = null;
            }

            $weather = "
                <div id=\"".$this->weatherId."\" ".$this->weatherClass.">
                    ".$title."
                    <div class=\"current\" style=\"float: left; width: 40%; height: auto;\">
                        ".$weatherData['current']['temp']."&deg;C<br /><br />
                        ".$weatherData['current']['humidity']."<br />
                        ".$weatherData['current']['wind']."
                    </div>
                    <div class=\"forecast\" style=\"float: left; width: 60%; height: auto;\">
                        ".$forecast."
                    </div>

                </div>
            ";

        } else {
            $weather = "<div ".$this->weatherId." class=\"error\">no Weaterdata for ".$this->weatherLocation."</i>";
        }

        return $weather;
    }
}
