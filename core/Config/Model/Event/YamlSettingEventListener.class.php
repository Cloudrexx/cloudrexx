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
 * YamlSettingEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_config
 */

namespace Cx\Core\Config\Model\Event;

/**
 * YamlSettingEventListenerException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_config
 */
class YamlSettingEventListenerException extends \Exception {}

/**
 * YamlSettingEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_config
 */
class YamlSettingEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener {
    public function preUpdate($eventArgs) {
        global $_CONFIG,$_ARRAYLANG;
        try {
            $objSetting = $eventArgs->getEntity();
            $value = $objSetting->getValue();

            switch ($objSetting->getName()) {
                case 'timezone':
                    if (!in_array($value, timezone_identifiers_list())) {
                        \Message::add($_ARRAYLANG['TXT_CORE_TIMEZONE_INVALID'], \Message::CLASS_ERROR);
                        throw new YamlSettingEventListenerException($_ARRAYLANG['TXT_CORE_TIMEZONE_INVALID']);
                    }

                    if ($value != $_CONFIG[$objSetting->getName()]) {
                        //clear cache
                         $widgetNames = array(
                            'DATE',
                        );
                        $this->cx->getEvents()->triggerEvent(
                            'clearEsiCache', array('Widget',  $widgetNames)
                        );
                    }
                    break;

                case 'mainDomainId':
                    if ($_CONFIG['mainDomainId'] != $value && $_CONFIG['forceDomainUrl'] == 'on') {
                        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
                        $objMainDomain = $domainRepository->findOneBy(array('id' => $value));
                        if ($objMainDomain) {
                            $domainUrl = $objMainDomain->getName();
                            $protocol = 'http';
                            if ($_CONFIG['forceProtocolBackend'] != 'none') {
                                $protocol = $_CONFIG['forceProtocolBackend'];
                            }
                            if (
                                php_sapi_name() != 'cli' &&
                                !\Cx\Core\Config\Controller\Config::checkAccessibility($protocol, $domainUrl)
                            ) {
                                \Message::add(sprintf($_ARRAYLANG['TXT_CONFIG_UNABLE_TO_SET_MAINDOMAIN'], $domainUrl), \Message::CLASS_ERROR);
                                $objSetting->setValue($_CONFIG['mainDomainId']);
                            } else {
                                $this->getComponent('Cache')->deleteNonPagePageCache();
                            }
                        }
                    }
                    break;

                case 'forceProtocolFrontend':
                    if ($_CONFIG['forceProtocolFrontend'] != $value) {
                        if (
                            php_sapi_name() != 'cli' &&
                            !\Cx\Core\Config\Controller\Config::checkAccessibility($value)
                        ) {
                            $domainAddr = $value . '://' . $_CONFIG['domainUrl'] . '/';
                            \Message::add(sprintf($_ARRAYLANG['TXT_CONFIG_UNABLE_TO_SET_PROTOCOL'], $domainAddr), \Message::CLASS_ERROR);
                            $objSetting->setValue('none');
                        }
                    }
                    if ($_CONFIG[$objSetting->getName()] != $objSetting->getValue()) {
                        $this->getComponent('Cache')->deleteNonPagePageCache();
                    }
                    break;

                case 'forceProtocolBackend':
                    if ($_CONFIG['forceProtocolBackend'] != $value) {
                        if (
                            php_sapi_name() != 'cli' &&
                            !\Cx\Core\Config\Controller\Config::checkAccessibility($value)
                        ) {
                            $domainAddr = $value . '://' . $_CONFIG['domainUrl'] . '/';
                            \Message::add(sprintf($_ARRAYLANG['TXT_CONFIG_UNABLE_TO_SET_PROTOCOL'], $domainAddr), \Message::CLASS_ERROR);
                            $objSetting->setValue('none');
                        }
                    }
                    break;

                case 'forceDomainUrl':
                    if ($value == 'off') {
                        break;
                    }
                    $useHttps = $_CONFIG['forceProtocolBackend'] == 'https';
                    $protocol = 'http';
                    if ($useHttps == 'https') {
                        $protocol = 'https';
                    }
                    if (
                        php_sapi_name() != 'cli' &&
                        !\Cx\Core\Config\Controller\Config::checkAccessibility($protocol)
                    ) {
                        \Message::add($_ARRAYLANG['TXT_CONFIG_UNABLE_TO_FORCE_MAINDOMAIN'], \Message::CLASS_ERROR);
                        $objSetting->setValue('off');
                    }
                    if ($_CONFIG[$objSetting->getName()] != $objSetting->getValue()) {
                        $this->getComponent('Cache')->deleteNonPagePageCache();
                    }
                    break;
                
                case 'cacheReverseProxy':
                case 'cacheProxyCacheConfig':
                    if ($value != $_CONFIG[$objSetting->getName()]) {
                        // drop reverse proxy cache
                        \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearReverseProxyCache('*');
                    }
                    break;
                
                case 'cacheSsiOutput':
                case 'cacheSsiType':
                case 'cacheSsiProcessorConfig':
                    if ($value != $_CONFIG[$objSetting->getName()]) {
                        // drop esi/ssi cache
                        \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCache();
                    }

                case 'googleMapsAPIKey':
                case 'coreGlobalPageTitle':
                    $settings = array(
                        'googleMapsAPIKey'    => 'GOOGLE_MAPS_API_KEY',
                        'coreGlobalPageTitle' => 'GLOBAL_TITLE'
                    );
                    $settingName = $objSetting->getName();
                    if ($value !== $_CONFIG[$settingName]) {
                        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                        $cx->getEvents()->triggerEvent(
                            'clearEsiCache',
                            array('Widget', $settings[$settingName])
                        );
                    }
                    break;
                case 'defaultMetaimage':
                    if ($value != $_CONFIG[$objSetting->getName()]) {
                        // drop esi/ssi cache
                        $this->cx->getEvents()->triggerEvent(
                            'clearEsiCache',
                            array('Widget', 'METAIMAGE')
                        );
                    }
                    break;
                case 'useVirtualLanguageDirectories':
                    if ($value == 'on' || $value == $_CONFIG[$objSetting->getName()]) {
                        break;
                    }

                    $locale = $this->cx->getDb()->getEntityManager()
                        ->getRepository('\Cx\Core\Locale\Model\Entity\Locale')
                        ->findAll();
                    // Set strong tag to the text
                    $strongText = new \Cx\Core\Html\Model\Entity\HtmlElement('strong');
                    $strongText->addChild(
                        new \Cx\Core\Html\Model\Entity\TextElement(
                            $_ARRAYLANG['TXT_CORE_CONFIG_USEVIRTUALLANGUAGEDIRECTORIES']
                        )
                    );

                    if ($value === 'off' && count($locale) > 1) {
                        \Message::error(
                            sprintf(
                                $_ARRAYLANG['TXT_CONFIG_UNABLE_TO_SET_USEVIRTUALLANGUAGEDIRECTORIES'],
                                $strongText
                            )
                        );
                        $objSetting->setValue('on');
                    }
                    break;
                default :
                    break;
            }
        } catch (YamlSettingEventListenerException $e) {
            \DBG::msg($e->getMessage());
        }
    }

    public function postFlush($eventArgs) {
        try {
            \Cx\Core\Config\Controller\Config::updatePhpCache();
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }
    }

    public function onEvent($eventName, array $eventArgs) {
        if ($eventName == 'postFlush') {
            if (
                !isset($eventArgs[1]) ||
                (isset($eventArgs[1]) && !preg_match('#\b(Config.yml)\b#', $eventArgs[1]))
            ) {
                return false;
            }
        }
        \DBG::msg(__METHOD__ . ': '. $eventName);
        $this->$eventName(current($eventArgs));
    }
}
