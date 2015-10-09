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

class ContrexxJavascriptI18nException extends ContrexxJavascriptException {}

require_once UPDATE_LIB . '/FRAMEWORK/cxjs/ContrexxJavascriptI18nProvider.interface.php';
/**
 * This handles i18n for Javascript.
 * @author Severin Räz
 */
class ContrexxJavascriptI18n {
   /**
    * language code used to generate i18n-files' names
    * @var string
    */
    protected $languageCode = 'en';

    public function __construct($languageCode) {
        $this->languageCode = $languageCode;
    }

    /**
     * sets all i18n variables on target
     * @param ContrexxJavascript $target
     * @throws ContrexxJavascriptI18nException
     */
    public function variablesTo($target) {
        $vars = array();
        $providers = scandir(UPDATE_LIB.'/FRAMEWORK/cxjs/i18n');
        foreach($providers as $provider) {
            if($provider[0] == '.') //do not open ., .., and linux hidden directories (.*)
                continue;
            //name as used for the scope ('provider')
            $providerName = substr($provider,0,strpos($provider,'.'));
            //name of the class ('providerProvider')
            $className = ucfirst($providerName.'I18nProvider');
            try {
                require_once 'i18n/'.$provider;
                $providerInst = new $className();
                //set the variables accordingly on cxjs object
                $target->setVariable($providerInst->getVariables($this->languageCode), $providerName);
            }
            catch(Exception $e)
            {
                throw new ContrexxJavascriptI18nException("error parsing i18n module '$provider': " . $e->getMessage());
            }
        }
        return $vars;
    }
}
