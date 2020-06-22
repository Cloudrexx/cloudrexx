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
 * This is the locale component controller
 *
 * @copyright   Cloudrexx AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */

namespace Cx\Core\Locale\Controller;

/**
 * This is the locale component controller
 *
 * @copyright   Cloudrexx AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('Backend','JsonLocale');
    }

    /**
     * Do something after all active components are loaded
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     */
    public function postComponentLoad() {
        global $objInit;
        // Initialize base system for language and theme
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $mode = $this->cx->getMode();
        } else {
            $mode = \Cx\Core\Core\Controller\Cx::MODE_BACKEND;
        }
        // TODO: Get rid of InitCMS class
        $objInit = new \InitCMS($mode, \Env::get('em'));
        \Env::set('init', $objInit);
    }

    /**
     * Returns a list of JsonAdapter class names
     *
     * The array values might be a class name without namespace. In that case
     * the namespace \Cx\{component_type}\{component_name}\Controller is used.
     * If the array value starts with a backslash, no namespace is added.
     *
     * Avoid calculation of anything, just return an array!
     * @return array List of ComponentController classes
     */
    public function getControllersAccessableByJson() {
        return array('JsonLocaleController');
    }

    /**
     * Register the Event listeners
     */
    public function registerEventListeners() {
        // locale event listener
        $evm = $this->cx->getEvents();
        $eventListener = new \Cx\Core\Locale\Model\Event\LocaleEventListener($this->cx);
        $evm->addModelListener(\Doctrine\ORM\Events::onFlush, 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $eventListener);
    }

    /**
     * Returns locale data as used by selectBestLocale()
     *
     * Locale data has the following structure:
     *  array(
     *      'DefaultFrontendLocaleId' => <defaultLocaleId>
     *      'Hashtables' => array(
     *          'IdByCode' => array(<localeShortForm> => <localeId>),
     *          'CodeByCountry' => array(
     *              <countryAlpha2> => array(<localeShortForm),
     *          )
     *      )
     *  )
     * @return array Locale data
     */
    public function getLocaleData() {
        global $_CONFIG;

        $em = $this->cx->getDb()->getEntityManager();
        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');
        $data = array(
            'DefaultFrontendLocaleId' => 0,
            'Hashtables' => array(
                // locale -> id
                // i.e.: de-CH -> 3
                'IdByCode' => array(),
                // country -> locale
                // i.e.: CH -> de-CH
                'CodeByCountry' => array(),
                // locale -> language
                // i.e.: de-CH -> de
                'Iso1ByCode' => array(),
            ),
        );
        foreach ($localeRepo->findAll() as $locale) {
            if ($locale->getId() == $_CONFIG['defaultLocaleId']) {
                $data['DefaultFrontendLocaleId'] = $locale->getId();
            }
            $localeCode = $locale->getShortForm();
            $data['Hashtables']['IdByCode'][$localeCode] = $locale->getId();
            $data['Hashtables']['Iso1ByCode'][$localeCode] =
                $locale->getIso1()->getIso1();
            if ($locale->getCountry()) {
                $countryCode = $locale->getCountry()->getAlpha2();
                if (!isset($data['Hashtables']['CodeByCountry'][$countryCode])) {
                    $data['Hashtables']['CodeByCountry'][$countryCode] = array();
                }
                $data['Hashtables']['CodeByCountry'][$countryCode][] = $localeCode;
            }
        }
        if ($data['DefaultFrontendLocaleId'] == 0) {
            return array();
        }
        return $data;
    }

    /**
     * Returns the locale ID best matching the client's request
     *
     * If no match can be found, returns the default locale ID.
     * @param \Cx\Core\Core\Controller\Cx $cx
     * @param array $localeData
     * @return int Locale ID
     */
    public static function selectBestLocale(
        \Cx\Core\Core\Controller\Cx $cx,
        array $localeData
    ) {
        global $_CONFIG;

        if (
            !isset($_CONFIG['languageDetection']) ||
            $_CONFIG['languageDetection'] == 'off'
        ) {
            return $localeData['DefaultFrontendLocaleId'];
        }

        // Try to find best locale with GeoIp
        $geoIp = $cx->getComponent('GeoIp');
        if (
            $geoIp &&
            $geoIp->isGeoIpEnabled() &&
            $bestLang = static::selectLocaleByGeoIp($geoIp, $localeData)
        ) {
            return $bestLang;
        }

        // No locale found with GeoIp. Try by HTTP header
        if ($bestLang = static::selectLocaleByHttp($localeData)) {
            return $bestLang;
        }

        // No locale found, return default one
        return $localeData['DefaultFrontendLocaleId'];
    }

    /**
     * Finds the best matching Locale ID by country
     *
     * Finds all locales by a country, detected with GeoIp,
     * and then checks if one of them matches any of the browser languages
     * If no browser language matches, the first found locale is returned.
     * If no locale is found, 0 is returned.
     * @param mixed $geoIp
     * @param mixed $localeData
     * @return int Locale ID or 0
     */
    protected static function selectLocaleByGeoIp($geoIp, $localeData) {
        // get country code
        $country = $geoIp->getCountryCode(null);
        if (!$country || !$countryCode = $country['content']) {
            return 0;
        }

        // find locales with found country code
        // This only returns locales with a country
        if (!isset($localeData['Hashtables']['CodeByCountry'][$countryCode])) {
            return 0;
        }
        $localeCodesByCountry = $localeData['Hashtables']['CodeByCountry'][$countryCode];
        if (!count($localeCodesByCountry)) {
            return 0;
        }

        // check if combination of country code and browser lang exists
        // This finds inexact matches like "de-DE" for browser lang "de"
        $acceptedLanguages = array_keys(static::getClientAcceptedLanguages());
        foreach ($acceptedLanguages as $acceptedLanguage) {
            foreach ($localeCodesByCountry as $localeCode) {
                if ($localeData['Hashtables']['Iso1ByCode'][$localeCode] == $acceptedLanguage) {
                    return $localeData['Hashtables']['IdByCode'][$localeCode];
                }
            }
        }

        // No combination found, return the first (most relevant) one
        // This implicitly finds exact matches like "de-DE" for browser lang "de-DE"
        return $localeData['Hashtables']['IdByCode'][reset($localeCodesByCountry)];
    }

    /**
     * Tries to find a locale by the browser language
     *
     * Loops over the client accepted languages (ordered by relevance)
     * and checks for existing locale.
     * For full locales with language and country (e.g "en-US")
     * it strips it and tries to find a locale with the lang code only
     * @param array $localeData
     * @return int Locale ID or 0
     */
    protected static function selectLocaleByHttp(array $localeData) {
        $arrAcceptedLanguages = static::getClientAcceptedLanguages();
        $strippedMatch = 0;
        foreach (array_keys($arrAcceptedLanguages) as $language) {
            // check for full match
            if (isset($localeData['Hashtables']['IdByCode'][$language])) {
                return $localeData['Hashtables']['IdByCode'][$language];
            } else if (!$strippedMatch) {
                // stripped lang: e.g 'en-US' becomes 'en'
                if ($pos = strpos($language, '-')) {
                    $language = substr($language, 0, $pos);
                }
                // check for existence of stripped language
                if (
                    // only check for actual stripped languages
                    $pos &&
                    isset($localeData['Hashtables']['IdByCode'][$language])
                ) {
                    $strippedMatch = $localeData['Hashtables']['IdByCode'][$language];
                }
            }
        }
        // No match with full locale or geoip, try to return stripped match
        if ($strippedMatch) {
            return $strippedMatch;
        }
        return 0;
    }

    /**
     * Returns an array with the accepted languages as keys and their
     * quality as values
     * @return  array
     */
    protected static function getClientAcceptedLanguages() {
        $arrLanguages = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) : array();
        $arrAcceptedLanguages = array();
        $q = 1;
        foreach ($arrLanguages as $languageString) {
            $arrLanguage = explode(';q=', trim($languageString));
            $language = trim($arrLanguage[0]);
            $quality = isset($arrLanguage[1]) ? trim($arrLanguage[1]) : $q;
            isset($arrLanguage[1]) ? $q = trim($arrLanguage[1]) : '';
            $q -= 0.1;
            $arrAcceptedLanguages[$language] = (float) $quality;
        }
        arsort($arrAcceptedLanguages, SORT_NUMERIC);

        return $arrAcceptedLanguages;
    }
}
