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
class YamlSettingEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
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
                    break;

                case 'domainUrl':
                    $arrMatch = array();
                    if (preg_match('#^https?://(.*)$#', $value, $arrMatch)) {
                        $value = $arrMatch[1];
                    }
                    $value = htmlspecialchars($value, ENT_QUOTES, CONTREXX_CHARSET);
                    $objSetting->setValue($value);
                    break;

                case 'forceProtocolFrontend':
                    if ($_CONFIG['forceProtocolFrontend'] != $value) {
                        if (!\Cx\Core\Config\Controller\Config::checkAccessibility($value)) {
                            $value = 'none';
                        }
                        $objSetting->setValue($value);
                    }
                    break;

                case 'forceProtocolBackend':
                    if ($_CONFIG['forceProtocolBackend'] != $value) {
                        if (!\Cx\Core\Config\Controller\Config::checkAccessibility($value)) {
                            $value = 'none';
                        }
                        $objSetting->setValue($value);
                    }
                    break;

                case 'forceDomainUrl':
                    $useHttps = $_CONFIG['forceProtocolBackend'] == 'https';
                    $protocol = 'http';
                    if ($useHttps == 'https') {
                        $protocol = 'https';
                    }
                    $value = \Cx\Core\Config\Controller\Config::checkAccessibility($protocol) ? $value : 'off';
                    $objSetting->setValue($value);
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
        \DBG::msg(__METHOD__);
        if ($eventName == 'postFlush') {
            if (isset($eventArgs[1]) && !preg_match('#\b(Config.yml)\b#', $eventArgs[1])) {
                return false;
            }
        }
        $this->$eventName(current($eventArgs));
    }
}
