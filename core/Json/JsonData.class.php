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
 * JSON Interface to Cloudrexx
 * @copyright   Cloudrexx AG
 * @author      Florian Schuetz <florian.schuetz@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */

namespace Cx\Core\Json;
use \Cx\Core\Json\Adapter\JsonNode;
use \Cx\Core\Json\Adapter\JsonPage;
use \Cx\Core\Json\Adapter\JsonContentManager;

/**
 * JSON Interface to Cloudrexx Doctrine Database
 *
 * @api
 * @copyright   Cloudrexx AG
 * @author      Florian Schuetz <florian.schuetz@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */
class JsonData {
    /**
     * List of adapter class names.
     * @deprecated Use component framework instead (SystemComponentController->getControllersAccessableByJson())
     * @var array List of adapter class names
     */
    protected static $adapter_classes = array(
        '\\Cx\\Core\\Json\\Adapter\\User' => array(
            'JsonUser',
        ),
        '\\Cx\\Modules\\Crm\\Controller' => array(
            'JsonCrm',
        ),
    );

    /**
     * List of adapters to use (they have to implement the JsonAdapter interface)
     * @var Array List of JsonAdapters
     */
    protected static $adapters = array();
    /**
     * Session id for request which we got from the login request
     * @var string $sessionId
     */
    protected $sessionId = null;

    /**
     * Constructor, loads adapter classes
     * @author Michael Ritter <michael.ritter@comvation.com>
     */
    public function __construct() {
        if (count(static::$adapters)) {
            return;
        }

        foreach (self::$adapter_classes as $ns=>$adapters) {
            foreach ($adapters as $adapter) {
                $this->loadAdapter($adapter, $ns);
            }
        }
    }

    /**
     * @deprecated Use component framework instead (SystemComponentController->getControllersAccessableByJson())
     */
    public static function addAdapter($className, $namespace = '\\') {
        if (!$className) {
            return;
        }
        if (is_array($className)) {
            foreach ($className as $class) {
                self::addAdapter($class, $namespace);
            }
            return;
        }
        self::$adapter_classes[$namespace][] = $className;
    }

    /**
     * Adds an adapter accessable by JSON requests.
     *
     * Either specify a fully qualified classname, or a classname and the containing
     * namespace separatly
     * @todo Adapter loading could be optimized
     * @param string $className Fully qualified or class name located in $namespace
     * @param string $namespace (optional) Namespace for non fully qualified class name
     * @throws \Exception if JsonAdapter interface is not implemented or controller can not be found
     */
    public function loadAdapter($className, $namespace = '') {
        if (substr($className, 0, 1) == '\\') {
            $adapter = $className;
        } else {
            $adapter = $namespace . '\\' . $className;
        }

        // check if its an adapter!
        if ($adapter instanceof \Cx\Core\Json\JsonAdapter) {
            throw new \Exception('Tried to load class as JsonAdapter, but interface is not implemented: "' . $adapter . '"');
        }

        // load specified controller
        $matches = array();
        preg_match('/\\\\?Cx\\\\(?:Core|Core_Modules|Modules|modules)\\\\([^\\\\]*)/', $adapter, $matches);
        $possibleComponentName = '';
        if (isset($matches[1])) {
            $possibleComponentName = $matches[1];
        }
        $nsParts = explode('\\', $adapter);
        $controllerClass = end($nsParts);

        // legacy adapter
        if (in_array($possibleComponentName, array('Json', 'Crm'))) {
            $this->loadLegacyAdapter($adapter);
            return;
        }

        $em = \Env::get('cx')->getDb()->getEntityManager();
        $componentRepo = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $component = $componentRepo->findOneBy(array('name'=>$possibleComponentName));
        if (!$component) {
            $this->loadLegacyAdapter($adapter);
            return;
            //throw new \Exception('JsonAdapter component could not be found: "' . $adapter . '"');
        }
        $object = $component->getController(preg_replace('/Controller$/', '', $controllerClass));
        if (!$object) {
            $this->loadLegacyAdapter($adapter, $component);
            return;
            //throw new \Exception('JsonAdapter controller could not be found: "' . $adapter . '"');
        }
        static::$adapters[$object->getName()] = $object;
    }

    /**
     * @deprecated: This load adapter in a way they shouldn't be loaded
     */
    protected function loadLegacyAdapter($adapter, $component = null) {
        \DBG::msg('Loading legacy JsonAdapter: ' . $adapter);
        if ($component) {
            $object = new $adapter($component->getSystemComponent(), \Env::get('cx'));
        } else {
            $object = new $adapter();
        }
        \Env::get('init')->loadLanguageData($object->getName());
        static::$adapters[$object->getName()] = $object;
    }

    /**
     * Passes JSON data to the particular adapter and returns the result
     * Called from index.php when section is 'jsondata'
     *
     * @author Florian Schuetz <florian.schuetz@comvation.com>
     * @author Michael Ritter <michael.ritter@comvation.com>
     * @param String $adapter Adapter name
     * @param String $method Method name
     * @param Array $arguments Arguments to pass, first dimension indexes are "response", "get" (optional) and "post" (optional)
     * @param boolean $setContentType (optional) If true (default) the content type is set to application/json
     * @return String JSON data to return to client
     */
    public function jsondata($adapter, $method, $arguments = array(), $setContentType = true) {
        $data = $this->data($adapter, $method, $arguments);
        $arguments['response']->setAbstractContent($data);
        if ($data['status'] != 'success' && $arguments['response']->getCode() == 200) {
            $arguments['response']->setCode(500);
        }
        return $this->json($arguments['response'], $setContentType);
    }

    /**
     * Parses a Response into JSON
     * @param \Cx\Core\Routing\Model\Entity\Response $response Data to JSONify
     * @param boolean $setContentType (optional) If true (NOT default) the content type is set to application/json
     * @return String JSON data to return to client
     */
    public function json(\Cx\Core\Routing\Model\Entity\Response $response, $setContentType = false) {
        $response->setParser($this->getParser());
        $parsedContent = $response->getParsedContent();
        if ($setContentType) {
            // Disabling CSRF protection. That's no problem as long as we
            // only return associative arrays or objects!
            // https://mycomvation.com/wiki/index.php/Contrexx_Security#CSRF
            // Search for a better way to disable CSRF!
            ini_set('url_rewriter.tags', '');
            header('Content-Type: ' . $response->getContentType());
        }
        return $parsedContent;
    }

    /**
     * Returns the parser used to parse JSON
     * Parser is either a callback function which accepts an instance of
     * \Cx\Core\Routing\Model\Entity\Response as first argument or an object with a
     * parse(\Cx\Core\Routing\Model\Entity\Response $response) method.
     * @return Object|callable Parser
     */
    public function getParser() {
        return function($response) {
            $response->setContentType('application/json');
            return json_encode($response->getAbstractContent());
        };
    }

    /**
     * This method can be used to parse data to JSON format
     * @param array $data Data to be parsed
     * @return string JSON encoded data
     */
    public function parse(array $data) {
        $response = new \Cx\Core\Routing\Model\Entity\Response($data);
        $response->setParser($this->getParser());
        return $response->getParsedContent();
    }

    /**
     * Checks whether an adapter or an adapter's method exists
     *
     * @param string $adapterName Adapter name to check for
     * @param string $methodName (optional) Method name to check for
     * @return boolean True if adapter or adapter's method exists, false otherwise
     */
    public function hasAdapterAndMethod($adapterName, $methodName = '') {
        $adapterExists = isset(static::$adapters[$adapterName]);
        if (empty($methodName) || !$adapterExists) {
            return $adapterExists;
        }
        $adapter = static::$adapters[$adapterName];
        $methods = $adapter->getAccessableMethods();
        // $methods has two possible formats: value can be a permission
        return isset($methods[$methodName]) || in_array($methodName, $methods);
    }

    /**
     * Passes JSON data to the particular adapter and returns the result
     * Called from jsondata() or any part of Cloudrexx
     * @author Michael Ritter <michael.ritter@comvation.com>
     * @param String $adapter Adapter name
     * @param String $method Method name
     * @param Array $arguments Arguments to pass, first dimension indexes are "response", "get" (optional) and "post" (optional)
     * @return array Data to use for further processing
     */
    public function data($adapter, $method, $arguments = array()) {
        global $_ARRAYLANG;

        if (!isset(static::$adapters[$adapter])) {
            return $this->getErrorData('No such adapter');
        }
        $adapter = static::$adapters[$adapter];
        $methods = $adapter->getAccessableMethods();
        $realMethod = '';

        /*
         * $adapter->getAccessableMethods() might return two type of arrays
         * Format 1: array('method1', 'method2')
         * Format 2: array('method1' => new \Cx\Core_Modules\Access\Model\Entity\Permission())
         */
        foreach ($methods as $methodName => $methodValue) {
            if ($methodValue instanceof \Cx\Core_Modules\Access\Model\Entity\Permission) {
                $realMethod = ($methodName == $method) ? $method : '';
            } elseif ($methodValue == $method) {
                $realMethod = $method;
            }

            if (!empty($realMethod)) {
                break;
            }
        }

        if ($realMethod == '') {
            return $this->getErrorData('No such method: ' . $method);
        }
        //permission checks
        $objPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission();
        $defaultPermission = $adapter->getDefaultPermissions();
        if (!empty($methods[$method]) && ($methods[$method] instanceof \Cx\Core_Modules\Access\Model\Entity\Permission)) {
            $objPermission = $methods[$method];
        } else if (!empty ($defaultPermission) && ($defaultPermission instanceof \Cx\Core_Modules\Access\Model\Entity\Permission)) {
            $objPermission = $defaultPermission;
        }

        if ($objPermission && ($objPermission instanceof \Cx\Core_Modules\Access\Model\Entity\Permission)) {
            if (!$objPermission->hasAccess($arguments)) {
                $backend = \Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND;
                if (!\FWUser::getFWUserObject()->objUser->login($backend)) {
                    // $_ARRAYLANG data is not load in HEAD request
                    if (!isset($_ARRAYLANG['TXT_LOGIN_NOAUTH_JSON'])) {
                        $_ARRAYLANG['TXT_LOGIN_NOAUTH_JSON'] = 'Session expired';
                    }
                    return $this->getErrorData(
                        $_ARRAYLANG['TXT_LOGIN_NOAUTH_JSON']
                    );
                }
                return $this->getErrorData('JsonData-request to method ' . $realMethod . ' of adapter ' . $adapter->getName() . ' has been rejected by not complying to the permission requirements of the requested method.');
            }
        }

        if (!isset($arguments['response'])) {
            $arguments['response'] = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
        }

        try {
            $data = call_user_func(array($adapter, $realMethod), $arguments);
            return array(
                'status'  => 'success',
                'data'    => $data,
                'message' => $adapter->getMessagesAsString()
            );
        } catch (\Exception $e) {
            //die($e->getTraceAsString());
            return $this->getErrorData($e->getMessage());
        }
    }

    public function setSessionId($sessionId) {
        $this->sessionId = $sessionId;
    }

    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * Fetches a json response via HTTP request
     * @todo Support cookies (to allow login and similiar features)
     * @param string $url URL to get json from
     * @param array $data (optional) HTTP post data
     * @param boolean $secure (optional) Wheter to verify peer using SSL or not, default false
     * @param string $certificateFile (optional) Local certificate file for non public SSL certificates
     * @param array $httpAuth Set an optional HTTP Authentication method and supply its login credentials.
     *              The supplied array must comply with the following structure:
     * <pre class="brush: php">
     *              $httpAuth = array(
     *                  'httpAuthMethod'   => 'none|basic|disgest',
     *                  'httpAuthUsername' => '<username>',
     *                  'httpAuthPassword' => '<password>',
     *              );
     * </pre>
     * @param array $files Key is the POST field name, value is the file path
     * @param boolean $sendJson Whether to encode data as JSON, default false
     * @return stdClass|boolean Decoded JSON on success, false otherwise
     */
    public function getJson(
        $url, $data = array(),
        $secure = false,
        $certificateFile = '',
        $httpAuth=array(),
        $files = array(),
        $sendJson = false
    ) {
        $request = new \HTTP_Request2($url, \HTTP_Request2::METHOD_POST);

        if (!empty($httpAuth)) {
            switch($httpAuth['httpAuthMethod']) {
                case 'basic':
                    $request->setAuth(
                        $httpAuth['httpAuthUsername'],
                        $httpAuth['httpAuthPassword'],
                        \HTTP_Request2::AUTH_BASIC
                    );
                    break;
                case 'disgest':
                    $request->setAuth(
                        $httpAuth['httpAuthUsername'],
                        $httpAuth['httpAuthPassword'],
                        \HTTP_Request2::AUTH_DIGEST
                    );
                    break;
                case 'none':
                default:
                    break;
            }
        }

        if ($sendJson) {
            $request->setHeader(
                'Content-Type',
                'application/json'
            );
            $request->setBody(json_encode($data));
        } else {
            foreach ($data as $name=>$value) {
                $request->addPostParameter($name, $value);
            }
        }

        if (!empty($files)) {
            foreach ($files as $fieldId => $file) {
                $request->addUpload($fieldId, $file);
            }
        }

        if ($this->sessionId !== null) {
            $request->addCookie(session_name(), $this->sessionId);
        }
        $request->setConfig(array(
            // disable ssl peer verification
            'ssl_verify_host' => $secure,
            'ssl_verify_peer' => $secure,
            // follow HTTP redirect
            'follow_redirects' => true,
            // resend original request to new location
            'strict_redirects' => true,
        ));
        $response = $request->send();
        //echo '<pre>';var_dump($response->getBody());echo '<br /><br />';
        $cookies = $response->getCookies();
        foreach ($cookies as &$cookie) {
            if ($cookie['name'] === session_name()) {
                $this->sessionId = $cookie['value'];
                break;
            }
        }
        if ($response->getStatus() != 200) {
            \DBG::msg(
                __METHOD__.' Request failed! Status: '.$response->getStatus()
            );
            \DBG::msg('URL: '.$url);
            \DBG::dump($data);
            \DBG::dump($response->getBody());
            return false;
        }

        $body = json_decode($response->getBody());
        if ($body === NULL) {
            \DBG::msg(__METHOD__.' failed!');
            \DBG::dump($response->getBody());
        }
        return $body;
    }

    /**
     * Returns the JSON code for a error message
     * @param String $message HTML encoded message
     * @author Michael Ritter <michael.ritter@comvation.com>
     * @return array Data for JSON response
     */
    public function getErrorData($message) {
        return array(
            'status' => 'error',
            'message' => $message
        );
    }
}
