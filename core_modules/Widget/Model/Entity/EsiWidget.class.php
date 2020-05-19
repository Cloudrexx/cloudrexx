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
 * Represents a template widget that is handled by ESI
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Model\Entity;

/**
 * Represents a template widget that is handled by ESI
 * Usage:
 * ```php
 * $this->getComponent('Widget')->registerWidget(
 *     new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
 *         $this->getSystemComponentController(),
 *         'FOO'
 *     )
 * );
 * ```
 * The above example replaces Sigma placeholder "FOO" by return value of
 * JsonAdapter method "getWidget" of JsonAdapter named after $this->getName()
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
class EsiWidget extends Widget {

    /**
     * This will just make a most-of-the-time-working setup and should not
     * be used on production without checking first.
     * @const int Index for ESI auto-configuration
     */
    const ESI_VAR_ID_AUTOCONF = -1;

    /**
     * @const int Index for ESI variable for page
     */
    const ESI_VAR_ID_PAGE = 1;

    /**
     * @const string Name of ESI variable for page
     */
    const ESI_VAR_NAME_PAGE = 'page';

    /**
     * @const int Index for ESI variable for locale
     */
    const ESI_VAR_ID_LOCALE = 2;

    /**
     * @const string Name of ESI variable for locale
     */
    const ESI_VAR_NAME_LOCALE = 'locale';

    /**
     * @const int Index for ESI variable for theme
     */
    const ESI_VAR_ID_THEME = 4;

    /**
     * @const string Name of ESI variable for theme
     */
    const ESI_VAR_NAME_THEME = 'theme';

    /**
     * @const int Index for ESI variable for channel
     */
    const ESI_VAR_ID_CHANNEL = 8;

    /**
     * @const string Name of ESI variable for channel
     */
    const ESI_VAR_NAME_CHANNEL = 'channel';

    /**
     * @const int Index for ESI variable for user
     */
    const ESI_VAR_ID_USER = 16;

    /**
     * @const string Name of ESI variable for user
     */
    const ESI_VAR_NAME_USER = 'user';

    /**
     * @const int Index for ESI variable for currency
     */
    const ESI_VAR_ID_CURRENCY = 32;

    /**
     * @const string Name of ESI variable for currency
     */
    const ESI_VAR_NAME_CURRENCY = 'currency';

    /**
     * @const int Index for ESI variable for country
     */
    const ESI_VAR_ID_COUNTRY = 64;

    /**
     * @const string Name of ESI variable for country
     */
    const ESI_VAR_NAME_COUNTRY = 'country';

    /**
     * @const int Index for ESI variable for additional path parts
     */
    const ESI_VAR_ID_PATH = 128;

    /**
     * @const string Name of ESI variable for additional path parts
     */
    const ESI_VAR_NAME_PATH = 'path';

    /**
     * @const int Index for ESI variable for query string
     */
    const ESI_VAR_ID_QUERY = 256;

    /**
     * @const string Name of ESI variable for query string
     */
    const ESI_VAR_NAME_QUERY = 'query';

    /**
     * ESI variables configured to be sent for this Widget
     *
     * @var int Combination of the constants
     */
    protected $esiVariables = self::ESI_VAR_ID_AUTOCONF;

    /**
     * Instanciates a new widget
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $component Component registering this widget
     * @param string $name Name of this widget
     * @param string $type (optional) Whether this widget represents a template placeholder, block or callback, default: placeholder
     * @param string $jsonAdapterName (optional) Name of the JsonAdapter to call. If not specified, $component->getName() is used
     * @param string $jsonMethodName (optional) Name of the JsonAdapter method to call. If not specified, "getWidget" is used
     * @param array $jsonParams (optional) Params to pass on JsonAdapter call. If not specified, a default list is used, see getEsiParams()
     */
    public function __construct($component, $name, $type = self::TYPE_PLACEHOLDER, $jsonAdapterName = '', $jsonMethodName = '', $jsonParams = array()) {
        parent::__construct($component, $name, $type);
        $this->jsonAdapterName = $jsonAdapterName;
        $this->jsonMethodName = $jsonMethodName;
        $this->jsonParams = $jsonParams;
    }

    /**
     * Returns the name of the JsonAdapter to call
     * @return string JsonAdapter name
     */
    public function getJsonAdapterName() {
        if (empty($this->jsonAdapterName)) {
            return $this->getRegisteringComponent()->getName() . 'Widget';
        }
        return $this->jsonAdapterName;
    }

    /**
     * Returns the name of the JsonAdapter method to call
     * @return string JsonAdapter method name
     */
    public function getJsonMethodName() {
        if (empty($this->jsonMethodName)) {
            return 'getWidget';
        }
        return $this->jsonMethodName;
    }

    /*
     * Really parses this widget into $template
     * If this Widget has no content, the replacement can simply be returned
     * as string. Otherwise the replacement must be done in $template.
     * @param \HTML_Template_Sigma $template Template to parse this widget into
     * @param \Cx\Core\Routing\Model\Entity\Reponse $response Current response object
     * @param string $targetComponent Parse target component name
     * @param string $targetEntity Parse target entity name
     * @param string $targetId Parse target entity ID
     * @param array $params (optional) List of params for widgets of type 'callback'
     * @return string Replacement for widgets without content, NULL otherwise
     */
    public function internalParse($template, $response, $targetComponent, $targetEntity, $targetId, $params = array()) {
        $esiContent = $this->getComponent('Cache')->getEsiContent(
            $this->getJsonAdapterName(),
            $this->getJsonMethodName(),
            array_merge(
                $params,
                $this->getEsiParams($targetComponent, $targetEntity, $targetId)
            )
        );
        if ($this->getType() != static::TYPE_BLOCK) {
            return $esiContent;
        }
        $template->replaceBlock($this->getName(), $esiContent);
        $template->touchBlock($this->getName());
    }

    /**
     * Returns the ESI variables configured to be send for this Widget
     * Return value is a combination of othered constants
     * @return int Bitwise-OR-combined values
     */
    public function getEsiVariables() {
        if ($this->esiVariables == static::ESI_VAR_ID_AUTOCONF) {
            $this->esiVariables = 0;
            $this->esiVariables |= static::ESI_VAR_ID_PAGE;
            $this->esiVariables |= static::ESI_VAR_ID_LOCALE;
            if ($this->getType() == static::TYPE_BLOCK) {
                $this->esiVariables |= static::ESI_VAR_ID_THEME;
                $this->esiVariables |= static::ESI_VAR_ID_CHANNEL;
            }
        }
        return $this->esiVariables;
    }

    /**
     * Sets the ESI variables configured to be sent for this widget
     * Simple setter, see getEsiVariables() for format definition
     * @param int $variables Bitwise-OR-combined values
     */
    public function setEsiVariables($variables) {
        $this->esiVariables = $variables;
    }

    /**
     * Sets the ESI variable as active
     * @param int $variables Variable identifier
     */
    public function setEsiVariable($variableId) {
        $this->setEsiVariables(
            $this->getEsiVariables() | $variableId
        );
    }

    /**
     * Sets the ESI variable as inactive
     * @param int $variables Variable identifier
     */
    public function unsetEsiVariable($variableId) {
        $this->setEsiVariables(
            $this->getEsiVariables() & ~$variableId
        );
    }

    /**
     * Tells wheter the given ESI variable is configured to be sent for this Widget
     * @param int $variables Variable identifier
     */
    public function isEsiVariableActive($variableId) {
        return ($this->getEsiVariables() & $variableId);
    }

    /**
     * Returns the params for the JsonAdapter call
     * If you add an ESI variable core_module Cache needs to be updated as well:
     * - Controller\CacheLib (multiple times)
     * - Model\Entity\ReverseProxyCloudrexx::globDrop()
     * @param string $targetComponent Parse target component name
     * @param string $targetEntity Parse target entity name
     * @param string $targetId Parse target entity ID
     * @return array List of params
     */
    protected function getEsiParams($targetComponent, $targetEntity, $targetId) {
        $esiParams = array();
        $baseParams = array(
            'name' => $this->getName(),
            'targetComponent' => $targetComponent,
            'targetEntity' => $targetEntity,
            'targetId' => $targetId,
        );
        if ($targetComponent == 'View' && $targetEntity == 'Theme') {
            $this->setEsiVariable(static::ESI_VAR_ID_THEME);
            $this->setEsiVariable(static::ESI_VAR_ID_CHANNEL);
        }
        // This should be set at a central place (Cache?)
        $esiVars = array(
            static::ESI_VAR_ID_PAGE => static::ESI_VAR_NAME_PAGE,
            static::ESI_VAR_ID_LOCALE => static::ESI_VAR_NAME_LOCALE,
            static::ESI_VAR_ID_THEME => static::ESI_VAR_NAME_THEME,
            static::ESI_VAR_ID_CHANNEL => static::ESI_VAR_NAME_CHANNEL,
            static::ESI_VAR_ID_USER => static::ESI_VAR_NAME_USER,
            static::ESI_VAR_ID_CURRENCY => static::ESI_VAR_NAME_CURRENCY,
            static::ESI_VAR_ID_COUNTRY => static::ESI_VAR_NAME_COUNTRY,
            static::ESI_VAR_ID_PATH => static::ESI_VAR_NAME_PATH,
            static::ESI_VAR_ID_QUERY => static::ESI_VAR_NAME_QUERY,
        );
        foreach ($esiVars as $esiVarId=>$esiVarName) {
            if (!$this->isEsiVariableActive($esiVarId)) {
                continue;
            }
            $esiVarValue = '';
            if (isset($_GET[$esiVarName])) {
                $esiVarValue = contrexx_input2raw($_GET[$esiVarName]);
            } else {
                // These values should come from a central place (ESI var registry?)
                switch ($esiVarName) {
                    case static::ESI_VAR_NAME_PAGE:
                        $esiVarValue = $this->cx->getPage()->getId();
                        break;
                    case static::ESI_VAR_NAME_LOCALE:
                        $locale = $this->cx->getDb()->getEntityManager()->find(
                            'Cx\Core\Locale\Model\Entity\Locale',
                            $this->cx->getPage()->getLang()
                        );
                        if (!$locale) {
                            break;
                        }
                        $esiVarValue = $locale->getShortForm();
                        break;
                    case static::ESI_VAR_NAME_THEME:
                        $esiVarValue = \Env::get('init')->getCurrentThemeId();
                        break;
                    case static::ESI_VAR_NAME_CHANNEL:
                        $esiVarValue = \Env::get('init')->getCurrentChannel();
                        break;
                    case static::ESI_VAR_NAME_USER:
                        $esiVarValue = '$(HTTP_COOKIE{\'PHPSESSID\'})';
                        break;
                    case static::ESI_VAR_NAME_CURRENCY:
                        $esiVarValue = \Cx\Modules\Shop\Controller\Currency::getActiveCurrencySymbol();
                        break;
                    case static::ESI_VAR_NAME_COUNTRY:
                        $esiVarValue = $this->getComponent(
                            'GeoIp'
                        )->getCountryCode(array());
                        break;
                    case static::ESI_VAR_NAME_PATH:
                        $esiVarValue = $this->getComponent('Widget')->encode(
                            \Env::get('Resolver')->getAdditionalPath()
                        );
                        break;
                    case static::ESI_VAR_NAME_QUERY:
                        $params = $this->cx->getRequest()->getUrl()->getParamArray();
                        unset($params['__cap']);
                        unset($params['section']);
                        unset($params['cmd']);
                        $esiVarValue = $this->getComponent('Widget')->encode(
                            http_build_query($params, '', '&')
                        );
                        break;
                }
            }
            if (empty($esiVarValue)) {
                continue;
            }
            $esiParams[$esiVarName] = $esiVarValue;
        }
        $params = array_merge($this->jsonParams, $esiParams, $baseParams);
        return $params;
    }

    /**
     * Clears all cache files for this Widget (if any)
     */
    public function clearCache() {
        $this->getComponent('Cache')->clearSsiCachePage(
            $this->getJsonAdapterName(),
            $this->getJsonMethodName(),
            array_merge(
                $this->jsonParams,
                array(
                    'name' => $this->getName(),
                )
            )
        );
    }
}
