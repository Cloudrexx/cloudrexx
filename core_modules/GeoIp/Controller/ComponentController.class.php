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
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

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
     * Hook - After resolving the page
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page  The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        //skip the process incase mode is not frontend or GeoIp is deactivated
        if (!$this->isGeoIpEnabled()) {
            return;
        }

        // Get stats controller to get client ip
        $statsComponentContoller = $this->getComponent('Stats');
        if (!$statsComponentContoller) {
            return;
        }

        //Get the country name and code by using the ipaddress through GeoIp2 library
        $countryDb    = $this->getDirectory().'/Data/GeoLite2-Country.mmdb';
        $activeLocale = \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
        $locale       = in_array($activeLocale, $this->availableLocale) ? $activeLocale : $this->defaultLocale;
        try {
            $reader = new \GeoIp2\Database\Reader($countryDb, array($locale));
            $this->clientRecord = $reader->country($statsComponentContoller->getCounterInstance()->getClientIp());
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            return;
        }

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
            || !$this->clientRecord
        ) {
            return;
        }

        $countryName = $this->clientRecord->country->name;
        $countryCode = $this->clientRecord->country->isoCode;

        //Parse the country name and code
        $objTemplate = $this->cx->getTemplate();
        $objTemplate->setVariable(array(
            'GEOIP_COUNTRY_NAME' => $countryName,
            'GEOIP_COUNTRY_CODE' => $countryCode
        ));

        //Set the country name and code as cx.variables
        $objJS = \ContrexxJavascript::getInstance();
        $objJS->setVariable(array(
            'countryName'   => $countryName,
            'countryCode'   => $countryCode
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
        return $this->clientRecord;
    }
}
