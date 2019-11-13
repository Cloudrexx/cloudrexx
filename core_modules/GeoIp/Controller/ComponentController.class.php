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
 * This is the controllers for GeoIp
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_geoip
 * @version     1.0.0
 */

namespace Cx\Core_Modules\GeoIp\Controller;

/**
 * This is the main controller for GeoIp
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_geoip
 * @version     1.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController implements \Cx\Core\Json\JsonAdapter {

    /**
     * List of available locales (as IETF language tags)
     *
     * @var array
     */
    protected $availableLocale = array('de', 'en', 'fr', 'ru', 'es', 'ja', 'pt-BR', 'zh-CN');

    /**
     * Default locale, specified as IETF language tag
     *
     * @var string
     */
    protected $defaultLocale = 'en';

    /**
     * Client record
     *
     * @var \GeoIp2\Model\Country
     */
    protected $clientRecord;

    /**
     * Returns all Controller class names for this component (except this)
     *
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('Backend', 'Default');
    }

    /**
     * Wrapper to __call()
     * Thank you Zend!
     * @return string ComponentName
     */
    public function getName() {
        return parent::getName();
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
        return array('ComponentController');
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array(),
            array(),
            false
        );
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array(
            'getCountryCode',
            'getCountryName',
        );
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return '';
    }

    /**
     * Hook - After resolving the page
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page  The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        $this->getClientRecord();
    }
    
    /**
     * JsonAdapter method to return the country code for current request's IP
     * This is the replacer method for ESI variable $(GEO{'country_code'})
     * @param array $params Request params (none needed)
     * @return string ISO Alpha-2 Country code
     */
    public function getCountryCode($params) {
        //skip the process incase mode is not frontend or GeoIp is deactivated or client record not found
        if (   !$this->isGeoIpEnabled()
            || !$this->getClientRecord()
        ) {
            return '';
        }

        $countryCode = $this->getClientRecord()->country->isoCode;
        return array('content' => $countryCode);
    }
    
    /**
     * JsonAdapter method to return country name
     * @param array $params Request params
     * @return string Country name
     */
    public function getCountryName($params) {
        if (!isset($params['get']) || !isset($params['get']['country'])) {
            return '';
        }
        if (!defined('FRONTEND_LANG_ID')) {
            define('FRONTEND_LANG_ID', $params['get']['lang']);
        }
        $countryCode = $params['get']['country'];
        return array(
            'content' => \Locale::getDisplayRegion(
                '-' . $countryCode,
                \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID)
            ),
        );
    }

    /**
     * Do GeoIp processing after content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page the resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        //skip the process incase mode is not frontend or GeoIp is deactivated or client record not found
        if (   !$this->isGeoIpEnabled()
            || !$this->getClientRecord()
        ) {
            return;
        }
        $cache = $this->getComponent('Cache');

        // TODO: ESI variable should be registered in another way
        $countryCodeEsi = '$(GEO{\'country_code\'})';
        $countryNameEsi = $cache->getEsiContent(
            $this->getName(),
            'getCountryName',
            array(
                'country' => '$(GEO{\'country_code\'})',
            )
        );

        //Parse the country name and code
        $objTemplate = $this->cx->getTemplate();
        $objTemplate->setVariable(array(
            'GEOIP_COUNTRY_NAME' => $countryNameEsi,
            'GEOIP_COUNTRY_CODE' => $countryCodeEsi,
        ));

        //Set the country name and code as cx.variables
        $objJS = \ContrexxJavascript::getInstance();
        $objJS->setVariable(array(
            'countryName'   => trim($cache->internalEsiParsing($countryNameEsi)),
            'countryCode'   => trim($cache->internalEsiParsing($countryCodeEsi)),
        ), 'geoIp');
    }

    /**
     * Check and return status of GeoIp for Frontend
     *
     * @return boolean True|False
     */
    public function isGeoIpEnabled()
    {
        if (   $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND
            && $this->getGeoIpServiceStatus()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get the GeoIp status
     *
     * @return integer GeoIp setting
     */
    public function getGeoIpServiceStatus()
    {
        //Get the GeoIp config option 'serviceStatus'
        \Cx\Core\Setting\Controller\Setting::init('GeoIp', 'config', 'Yaml');
        $serviceStatus = \Cx\Core\Setting\Controller\Setting::getValue('serviceStatus', 'GeoIp');

        return $serviceStatus;
    }

    /**
     * Get the client record
     *
     * @return GeoIp2\Model\Country
     */
    public function getClientRecord() {
        if ($this->clientRecord) {
            return $this->clientRecord;
        }
        
        //skip the process incase mode is not frontend or GeoIp is deactivated
        if (!$this->isGeoIpEnabled()) {
            return null;
        }

        // Get stats controller to get client ip
        $statsComponentContoller = $this->getComponent('Stats');
        if (!$statsComponentContoller) {
            return null;
        }

        //Get the country name and code by using the ipaddress through GeoIp2 library
        $countryDb    = $this->getDirectory().'/Data/GeoLite2-Country.mmdb';
        $activeLocale = \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
        $locale       = in_array($activeLocale, $this->availableLocale) ? $activeLocale : $this->defaultLocale;
        try {
            $reader = new \GeoIp2\Database\Reader($countryDb, array($locale));
            $this->clientRecord = $reader->country($statsComponentContoller->getCounterInstance()->getClientIp());
            return $this->clientRecord;
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            return null;
        }
    }
}
