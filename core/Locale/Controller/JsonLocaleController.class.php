<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2016
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
 * JSON Adapter for Cx\Core\Locale\Model\Entity\Locale
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */

namespace Cx\Core\Locale\Controller;

/**
 * JSON Adapter for Cx\Core\Locale\Model\Entity\Locale
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */
class JsonLocaleController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter {

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return parent::getName();
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('getGeneratedLabel','getPlaceholderDefaultValue');
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return '';
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Generates a label according to iso1 and alpha2 code
     * using the php Locale class
     *
     * @param array $arguments The arguments
     * @return string Language (Country), e.g. English (United States)
     */
    public function getGeneratedLabel($arguments) {
        $parameters = $arguments['get'];
        if (!isset($parameters['iso1'])) {
            return '';
        }
        $languageName = \Locale::getDisplayLanguage($parameters['iso1'], $parameters['iso1']);
        if (!$parameters['alpha2'] || $parameters['alpha2'] == 'NULL') {
            return $languageName;
        }
        $countryName = \Locale::getDisplayRegion('und_' . $parameters['alpha2'], $parameters['iso1']);
        return $countryName . ' (' . $languageName . ')';
    }

    /**
     * Gets the default value of a language placeholder according to the args
     * @param $arguments The arguments
     * @return string The default value of the placeholder or empty string
     */
    public function getPlaceholderDefaultValue($arguments) {
        $parameters = $arguments['get'];
        $languageData = \Env::get('init')->getComponentSpecificLanguageDataByCode(
            trim($parameters['componentName']),
            $parameters['frontend'] == 'true' ? true : false,
            trim($parameters['languageCode']),
            false
        );
        $placeholderName = trim($parameters['placeholderName']);
        if (isset($languageData[$placeholderName])) {
            return $languageData[$placeholderName];
        }
        return '';
    }

}
