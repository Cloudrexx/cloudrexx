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
 * Wrapper class for the recursive array
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     3.1.2
 * @package     cloudrexx
 * @subpackage  core
 */

namespace Cx\Core\Model;

/**
 * Wrapper class for the recursive array
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core
 *
 * @see         /core/session.class.php
 */
class RecursiveArrayAccess implements \ArrayAccess, \Countable, \Iterator {

    /**
     * Internal data array.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Path of the current array
     *
     * @var string
     */
    protected $offset;

    /**
     * Callable funtion on offsetSet
     *
     * @var callable
     */
    protected $callableOnSet;
    /**
     * Callable funtion on offsetGet
     *
     * @var callable
     */
    protected $callableOnGet;

    /**
     * Callable funtion on offsetUnset
     *
     * @var callable
     */
    protected $callableOnUnset;

    /**
     * Callable function on callableOnValidateKey
     *
     * @var callable
     */
    protected $callableOnValidateKey;

    /**
     *
     *
     * @var integer
     */
    protected $id;

    /**
     *
     * @var type
     */
    protected $parentId;

    /**
     * Default object constructor.
     *
     * @param array $data
     */
    protected function __construct($data, $offset = '', $parentId = 0, $callableOnSet = null, $callableOnGet = null, $callableOnUnset = null, $callableOnValidateKey = null)
    {
        $this->offset   = $offset;
        $this->parentId = intval($parentId);

        $this->callableOnSet   = $callableOnSet;
        $this->callableOnGet   = $callableOnGet;
        $this->callableOnUnset = $callableOnUnset;
        $this->callableOnValidateKey = $callableOnValidateKey;

        if ($this->callableOnUnset)
            call_user_func($this->callableOnUnset, $this->offset, $this->parentId);
        if ($this->callableOnSet)
            call_user_func($this->callableOnSet, $this);

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this[$key] = $value;
            }
        }
    }

    /**
     * Output the data as a multidimensional array.
     *
     * @return array
     */
    public function toArray() {
        $data = $this->data;
        foreach ($data as $key => $value) {
            if ($value instanceof self) {
                $data[$key] = $value->toArray();
            }
        }
        return $data;
    }

    /**
     * check a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     *
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset) {
        if ($this->callableOnGet) {
            return call_user_func($this->callableOnGet, $offset, $this);
        } else {
            return isset($this->data[$offset]) ? $this->data[$offset] : null;
        }
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     *
     * @return null
     */
    public function offsetSet($offset, $data, $callableOnSet = null, $callableOnGet = null, $callableOnUnset = null, $callableOnValidateKey = null) {

        if ($callableOnValidateKey) {
            $this->callableOnValidateKey = $callableOnValidateKey;
        }

        if ($this->callableOnValidateKey) {
            call_user_func($this->callableOnValidateKey, $offset);
        }

        if ($offset === null) {
            $offset = count($this->data);
        }

        if ($callableOnSet) {
            $this->callableOnSet = $callableOnSet;
        }
        if ($callableOnGet) {
            $this->callableOnGet = $callableOnGet;
        }
        if ($callableOnUnset) {
            $this->callableOnUnset = $callableOnUnset;
        }

        if ( is_array( $data ) ) {
            $data = new self(
                            $data,
                            $offset,
                            $this->id,
                            isset($this->callableOnSet) ? $this->callableOnSet : null,
                            isset($this->callableOnGet) ? $this->callableOnGet : null,
                            isset($this->callableOnUnset) ? $this->callableOnUnset : null,
                            isset($this->callableOnValidateKey) ? $this->callableOnValidateKey : null
                    );
        } else if (isset($this->data[$offset]) && is_object($this->data[$offset]) && is_a($this->data[$offset], __CLASS__)) {
            $this->offsetUnset($offset);
        }

        $this->data[$offset] = $data;

        if ($this->callableOnSet) {
            call_user_func($this->callableOnSet, $this);
        }
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset to unset.
     *
     * @return null
     */
    public function offsetUnset($offset) {
        if ($this->callableOnUnset)
            call_user_func($this->callableOnUnset, $offset, $this->id);

        unset($this->data[$offset]);

        if ($this->callableOnSet)
            call_user_func($this->callableOnSet, $this);
    }

    /********************************/
    /*   Iterator Implementation    */
    /********************************/

    /**
     * Current position of the array.
     *
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return mixed
     */
    public function current() {
        return current($this->data);
    }

    /**
     * Key of the current element.
     *
     * @link http://php.net/manual/en/iterator.key.php
     *
     * @return mixed
     */
    public function key() {
        return key($this->data);
    }

    /**
     * Move the internal point of the container array to the next item
     *
     * @link http://php.net/manual/en/iterator.next.php
     *
     * @return void
     */
    public function next() {
        next($this->data);
    }

    /**
     * Rewind the internal point of the container array.
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void
     */
    public function rewind() {
        reset($this->data);
    }

    /**
     * Is the current key valid?
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return bool
     */
    public function valid() {
        return $this->offsetExists($this->key());
    }

    /**********************************/
    /*    Countable Implementation    */
    /**********************************/

    /**
     * Get the count of elements in the container array.
     *
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int
     */
    public function count() {
        return count($this->data);
    }
}
