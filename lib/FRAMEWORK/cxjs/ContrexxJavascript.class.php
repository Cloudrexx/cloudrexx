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
 * ContrexxJavascript
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_cxjs
 */

/**
 * ContrexxJavascriptException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_cxjs
 */
class ContrexxJavascriptException extends Exception {}

/**
 * @ignore
 */
require_once ASCMS_FRAMEWORK_PATH.'/cxjs/ContrexxJavascriptI18n.class.php';

/**
 * This class configures the ContrexxJavascript-object
 * (referred to as 'cx-object' in the comments)
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_cxjs
 * @todo this can be cached
 */
class ContrexxJavascript {
    /**
     * "Multiton" pattern instances
     * The last instance is used
     * @var array
     */
    protected static $instances = array();
    
    /**
     * Returns the current instance
     *
     * @param boolean $forceNew
     * @return \ContrexxJavascript Current instance
     */
    public static function getInstance($forceNew = false, $parseForMainTemplate = true)
    {
        if (!count(static::$instances) || $forceNew) {
            static::$instances[] = new static($parseForMainTemplate);
        }
        return end(static::$instances);
    }

    /**
     * Adds a new instance to the stack. This instance will be used as current
     * instance.
     *
     * @param \ContrexxJavascript $instance (optional) If left empty a new instance will be created
     */
    public static function push($instance = null, $parseForMainTemplate = true) {
        if ($instance) {
            static::$instances[] = $instance;
            return;
        }
        static::getInstance(true, $parseForMainTemplate);
    }

    /**
     * Drops the current instance from the stack and returns it
     * @return \ContrexxJavascript
     */
    public static function pop() {
        return array_pop(static::$instances);
    }

    private function __construct($parseForMainTemplate = true)
    {
        global $objInit;

        if (!$parseForMainTemplate) {
            return;
        }

        $backOrFrontend = $objInit->mode;
// TODO: Unused
//        global $objFWUser;
//        $langId;
        if($backOrFrontend == "frontend")
            $langCode = FWLanguage::getLanguageCodeById($objInit->getFrontendLangId());
        else //backend
            $langCode = FWLanguage::getBackendLanguageCodeById($objInit->getBackendLangId());

        $this->setVariable(
            array(
                'path' => ASCMS_PATH_OFFSET . '/' . $langCode . '/',
                'basePath' => ASCMS_PATH_OFFSET . '/',
                'cadminPath' => \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteBackendPath() . '/',
                'mode' => $objInit->mode,
                'language' => $langCode,
                'csrf' => \Cx\Core\Csrf\Controller\Csrf::code(),
                'charReplaceList' => \Cx\Core\LanguageManager\Controller\ComponentController::$REPLACEMENT_CHARLIST,
                'themeId'   => \Env::get('init')->getCurrentThemeId(),
                'themeFolder' => \Env::get('init')->getCurrentThemesPath(),
            ),
            'contrexx'
        );

        //let i18n set it's variables
        $i18n = new ContrexxJavascriptI18n($langCode);
        $i18n->variablesTo($this);
    }

    /**
     * Holds the variables that are to passed to the cx-object
     * @var Array ( 'scope' => array('name' => 'val', ...), ... )
     */
    protected $variables = array();

    /**
     * sets the variable $name to $value
     * @param mixed $key string on single value, else array('key' => val)
     * @param mixed $value value (mandatory) on single value, else scope (optional)
     * @param mixed $scope scope (mandatory) on single value, else unused
     */
    public function setVariable($key, $value, $scope=null) {
        //if the scope parameter is not set, we're dealing with one of the following cases:
        //a) the key parameter is an array of multiple key-value pairs
        //   => in this case, the scope is in parameter value
        //b) no scope was specified
        //   => in this case, we use the default scope 'global'
        //c) a) and b) occur

        $multipleValues = is_array($key);
        if(is_null($scope)) {
            if($multipleValues)
                $scope = $value;
            if(!$scope)
                $scope = 'global';
        }
        //create scope if it doesn't exist
        if(!isset($this->variables[$scope])) {
            $this->variables[$scope] = array();
        }

        if(!$multipleValues) {
            $this->variables[$scope][$key] = $value;
        }
        else {
          //$targetScope = $this->variables[$scope];
            foreach($key as $k => $v) {
                $this->variables[$scope][$k] = $v;
            }
        }
    }

    /**
     * generates all javascript needed to configure the cx-object
     */
    public function initJs() {
        if (!count($this->variables)) {
            return '';
        }
        $js = $this->constructJs();
        $js .= $this->variableConfigJs();
        $js .= 'cx.internal.setCxInitialized();';
        return $js;
    }

    /**
     * generates all javascript needed to construct the cx-object
     */
    protected function constructJs() {
        $js = '';
        //$js .= 'cx = new ContrexxJs();';
        return $js;
    }

    /**
     * generates the javascript needed to set all variables on the cx-object
     */
    protected function variableConfigJs() {
        $js='';
        foreach($this->variables as $scope => $variables) {
            $js  .= 'cx.variables.set(';
            $js .= json_encode($variables);
            $js .= ",'$scope');\n";
        }
        return $js;
    }
}
