<?php declare(strict_types=1);

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
 * A callback of any kind
 *
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_module_access
 */

namespace Cx\Core_Modules\Access\Model\Entity;

/**
 * Exception thrown if an invalid action is performed with this callback.
 * An example would be trying to persist an inline function.
 *
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_module_access
 */
class CallbackException extends \Exception {}

/**
 * This class represents a callback function.
 *
 * This class supports three different kinds of callbacks:
 * INLINE:
 * An inline function. Note that these cannot be persisted!
 * <pre class="brush: php">
 *  $callback = new \Cx\Core_Modules\Access\Model\Entity\Callback(
 *      function($a) { return 2 * $a; }
 *  );
 *  $callback(2);
 * </pre>
 * REFERENCE:
 * A PHP-style function or method reference.
 * <pre class="brush: php">
 *  $callback = new \Cx\Core_Modules\Access\Model\Entity\Callback(
 *      array('Cx\Core\Html\Controller\ViewGenerator', 'getVgSearchUrl')
 *  );
 *  $callback(0, 'foo');
 * </pre>
 * JSON:
 * A JsonAdapter method. Params can be extended (but not overwritten) on invoke.
 * <pre class="brush: php">
 *  $callback = \Cx\Core_Modules\Access\Model\Entity\Callback::fromJsonAdapter(
 *      'page',
 *      'get',
 *      array('page'=>1)
 *  );
 *  return $callback(array('lang' => 'en'));
 * </pre>
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_module_access
 */
class Callback extends \Cx\Model\Base\EntityBase {

    /**
     * @var int Inline function
     */
    const TYPE_INLINE = 1;

    /**
     * @var int PHP-style referenced function
     */
    const TYPE_REFERENCE = 2;

    /**
     * @var int JsonAdapter method
     */
    const TYPE_JSON = 3;

    /**
     * @var int Type of this callback (see constants)
     */
    protected $type = 0;

    /**
     * @var array|callable Info about the callback function
     */
    protected $callbackInfo;

    /**
     * Creates a callback for a JsonAdapter
     * @param string $adapterName Name of the JsonAdapter
     * @param string $adapterMethod Name of the adapter's method
     * @param array $arguments (optional) "GET" arguments to pass
     * @param array $dataArguments (optional) "POST" arguments to pass
     * @throws CallbackException If no such adapter or method exists
     * @return self Callback instance for the given JsonAdapter method
     */
    public static function fromJsonAdapter(string $adapterName, string $adapterMethod, $arguments = array(), $dataArguments = array()) {
        $json = new \Cx\Core\Json\JsonData();
        if (!$json->hasAdapterAndMethod($adapterName, $adapterMethod)) {
            throw new CallbackException('No valid callback specified');
        }
        return new static(array(
            $adapterName,
            $adapterMethod,
            $arguments,
            $dataArguments,
        ));
    }

    /**
     * Creates a new callback from callback function info
     *
     * $callbackInfo can be an inline function, a PHP-style reference to a
     * function or method or a reference to a JsonAdapter. References to
     * JsonAdapters have the following format:
     * array(
     *  <adapterName>,
     *  <methodName>,
     *  [<getParamsAsArray],
     *  [<postParamsAsArray]
     * )
     * @param array|callable $callbackInfo
     */
    public function __construct($callbackInfo) {
        $this->callbackInfo = $callbackInfo;
        $this->findType();
    }

    /**
     * Returns the information about a callback
     * 
     * See class and constructor DocBlock for more info
     * @return array Callback info
     */
    public function getCallbackInfo() {
        return $this->callbackInfo;
    }

    /**
     * Returns the type detected by analyzing $callbackInfo
     * @return int Type (see constants)
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Tells whether this callback can be serialized
     *
     * @return boolean True for all types except "inline"
     */
    public function isSerializable() {
        return $this->type == static::TYPE_JSON;
    }

    /**
     * Sets $this->type by analyzing $this->callbackInfo
     */
    protected function findType() {
        if (!$this->callbackInfo) {
            throw new CallbackException('No valid callback specified');
        }
        // Note: intended "self"!
        if ($this->callbackInfo instanceof self) {
            throw new CallbackException('No valid callback specified');
        }
        if (is_callable($this->callbackInfo)) {
            if (is_array($this->callbackInfo) || is_string($this->callbackInfo)) {
                // PHP-style reference
                $this->type = static::TYPE_REFERENCE;
            } else {
                // Inline function
                $this->type = static::TYPE_INLINE;
            }
        } else if (is_array($this->callbackInfo)) {
            // JsonAdapter method
            if (
                !isset($this->callbackInfo[0]) ||
                !isset($this->callbackInfo[1]) ||
                count($this->callbackInfo) < 2 ||
                count($this->callbackInfo) > 4 ||
                (
                    count($this->callbackInfo) > 2 &&
                    !is_array($this->callbackInfo[2])
                ) ||
                (
                    count($this->callbackInfo) > 3 &&
                    !is_array($this->callbackInfo[3])
                )
            ) {
                throw new CallbackException('No valid callback specified');
            }
            $this->type = static::TYPE_JSON;
        } else {
            throw new CallbackException('No valid callback specified');
        }
    }

    /**
     * Calls the method or function specified by this Callback
     * @param mixed $args,... Arguments as defined by the specified callback
     *                          method or function. JsonAdapter methods accept
     *                          zero, one or two arguments. Both arguments
     *                          (if specified) need to be arrays. The first is
     *                          used as GET, the second as POST params. Params
     *                          specified in the Callback's definition cannot
     *                          be overwritten this way.
     * @throws CallbackException If a JsonAdapter returns any status other than "success"
     * @return mixed Return value of the callback method or function
     */
    public function __invoke(...$args) {
        switch ($this->type) {
            case static::TYPE_REFERENCE:
            case static::TYPE_INLINE:
                return call_user_func_array($this->callbackInfo, func_get_args());
                break;
            case static::TYPE_JSON:
                $params = array('get' => array(), 'post' => array());
                if (isset($args[0]) && is_array($args[0])) {
                    $params['get'] = $args[0];
                }
                if (isset($args[1]) && is_array($args[1])) {
                    $params['post'] = $args[1];
                }
                if (isset($this->callbackInfo[2])) {
                    $params['get'] = array_merge($params['get'], $this->callbackInfo[2]);
                }
                if (isset($this->callbackInfo[3])) {
                    $params['post'] = array_merge($params['post'], $this->callbackInfo[2]);
                }
                $json = new \Cx\Core\Json\JsonData();
                $data = $json->data(
                    $this->callbackInfo[0],
                    $this->callbackInfo[1],
                    $params
                );
                if (
                    !isset($data['status']) ||
                    $data['status'] != 'success' ||
                    !isset($data['data'])
                ) {
                    $msg = 'Callback execution failed';
                    if (!empty($data['message'])) {
                        $msg .= ': "' . $data['message'] . '"';
                    }
                    throw new CallbackException($msg);
                }
                return $data['data'];
                break;
        }
    }

    /**
     * Advises PHP to only serialize $this->callbackInfo.
     *
     * Does nothing if this Callback is not serializable (see isSerializable())
     * @return array List of properties to serialize
     */
    public function __sleep() {
        if (!$this->isSerializable()) {
            return array();
        }
        return array('callbackInfo');
    }

    /**
     * Recreates $this->type on wakeup
     */
    public function __wakeup() {
        $this->findType();
    }
}
