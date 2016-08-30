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
 * Data Set
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */

namespace Cx\Core_Modules\Listing\Model\Entity;

/**
 * Data Set Exception
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
class DataSetException extends \Exception {}

/**
 * Data Set
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
class DataSet implements \Iterator {
    protected static $yamlInterface = null;
    protected $data = array();
    protected $dataType = 'array';

    /**
     * Identifier is used as a kind of description for the DataSet. For example: If you want to save an array with
     * frontend users in a DataSet you can name the identifier something like 'frontendUser'
     * This is used for the ViewGenerator, so you can have separated options for all DataSets
     *
     * @access protected
     * @var $identifier
     */
    protected $identifier = '';

    // TODO: DataSet must be extended, that it can handle objects
    public function __construct($data = array(), callable $converter = null) {
        if (!count($data)) {
            return;
        }
        if (is_callable($converter)) {
            $this->data = $converter($data);
        } else {
            $this->data = $this->convert($data);
        }
    }

    /**
     * Set data-attribute $key to $value
     */
    public function add($key, $value, &$convertedData = null) {
        if ($convertedData === null) {
            $convertedData = &$this->data;
        }
        if (is_array($value)) {
            foreach ($value as $attribute=>$property) {
                $convertedData[$key][$attribute] = $property;
            }
        } else if (is_object($value)) {
            if ($this->dataType == 'array') {
                $this->dataType = get_class($value);
            }
            $convertedObject = $this->convertObject($value, $key);
            $convertedData[$key] = $convertedObject;
        } else {
             throw new DataSetException('Supplied argument could not be converted to DataSet');
        }
    }

    /**
     * Try to remove the declared key from the dataset
     * @param string $key
     * @throws DataSetException
     */
    public function remove($key) {
        if (!isset($this->data[$key])) {
            throw new DataSetException('Could not remove the declared key because the key does not exist in the DataSet');
        }
        unset($this->data[$key]);
    }

    /**
     * Appends the data of passed $dataSet to the current object and rewinds it
     * @param \Cx\Core_Modules\Listing\Model\Entity\DataSet $dataSet DataSet of which its data should be appended
     */
    public function join(\Cx\Core_Modules\Listing\Model\Entity\DataSet $dataSet) {
        foreach ($dataSet as $data) {
            $this->data[] = $data;
        }
        $this->rewind();
    }

    protected function convert($data) {
        $convertedData = array();
        if (is_array($data)) {
            foreach ($data as $key=>$value) {
                $this->add($key, $value, $convertedData);
            }
        } else if (is_object($data)) {
            $this->dataType = get_class($data);
            $convertedData = $this->convertObject($data);
        } else {
            throw new DataSetException('Supplied argument could not be converted to DataSet');
        }
        return $convertedData;
    }

    protected function convertObject($object, &$key) {
        $data = array();
        if ($object instanceof \Cx\Model\Base\EntityBase) {
            $em = \Env::get('em');
            $identifiers = $em->getClassMetadata(get_class($object))->getIdentifierValues($object);
            if (is_array($identifiers)) {
                $identifiers = implode('/', $identifiers);
            }
            $key = $identifiers;
            foreach ($em->getClassMetadata(get_class($object))->getColumnNames() as $column) {
                $field = $em->getClassMetadata(get_class($object))->getFieldName($column);
                $value = $em->getClassMetadata(get_class($object))->getFieldValue($object, $field);
                if ($value instanceof \DateTime) {
                    $value = $value->format('d.M.Y H:i:s');
                } elseif (is_array($value)) {
                    $value = serialize($value);
                }
                $data[$field] = $value;
            }
            $associationMappings = $em->getClassMetadata(get_class($object))->getAssociationMappings();
            foreach ($associationMappings as $field => $associationMapping) {
                $classMethods = get_class_methods($object);
                $methodNameToFetchAssociation = 'get'.ucfirst($field);
                if (in_array($methodNameToFetchAssociation, $classMethods)) {
                    $data[$field] = $object->$methodNameToFetchAssociation();
                }
            }
            $data['virtual'] = $object->isVirtual();
            return $data;
        }
        foreach ($object as $attribute => $property) {
            if (is_object($property)) {
                $data[$attribute] = $this->convertObject($property, $key);
            } else {
                $data[$attribute] = $property;
            }
        }
        return $data;
    }

    protected static function getYamlInterface() {
        if (empty(self::$yamlInterface)) {
            self::$yamlInterface = new \Cx\Core_Modules\Listing\Model\Entity\YamlInterface();
        }
        return self::$yamlInterface;
    }

    public function toYaml() {
        return $this->export(self::getYamlInterface());
    }

    public static function import(\Cx\Core_Modules\Listing\Model\Entity\Importable $importInterface, $content) {
        try {
            return new static($importInterface->import($content));
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new DataSetException('Importing data through supplied interface failed!');
        }
    }

    /**
     *
     * @param Cx\Core_Modules\Listing\Model\ImportInterface $importInterface
     * @param type $filename
     * @throws \Cx\Lib\FileSystem\FileSystemException
     * @return type
     */
    public static function importFromFile(\Cx\Core_Modules\Listing\Model\Entity\Importable $importInterface, $filename) {
        try {
            $objFile = new \Cx\Lib\FileSystem\File($filename);
            return self::import($importInterface, $objFile->getData());
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
            throw new DataSetException("Failed to load data from file $filename!");
        }
    }

    public function export(\Cx\Core_Modules\Listing\Model\Entity\Exportable $exportInterface) {
        try {
            return $exportInterface->export($this->data);
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new DataSetException('Exporting data to selected interface failed!');
        }
    }

    /**
     *
     * @param Cx\Core_Modules\Listing\Model\ExportInterface $exportInterface
     * @param type $filename
     * @throws \Cx\Lib\FileSystem\FileSystemException
     */
    public function exportToFile(\Cx\Core_Modules\Listing\Model\Entity\Exportable $exportInterface, $filename) {
        try {
            $objFile = new \Cx\Lib\FileSystem\File($filename);
            $objFile->touch();
            $objFile->write($this->export($exportInterface));
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
            throw new DataSetException("Failed to export data to file $filename!");
        }
    }

    /**
     *
     * @param type $filename
     * @throws \Cx\Lib\FileSystem\FileSystemException
     */
    public function save($filename) {
        $this->exportToFile($this->getYamlInterface(), $filename);
    }

    public static function fromYaml($data) {
        return self::import(self::getYamlInterface(), $data);
    }

    /**
     *
     * @param type $filename
     * @throws \Cx\Lib\FileSystem\FileSystemException
     * @return type
     */
    public static function load($filename) {
        return self::importFromFile(self::getYamlInterface(), $filename);
    }

    public function getDataType() {
        return $this->dataType;
    }

    /**
     * This function sets the DataType of an DataSet
     *
     * @access public
     * @param string $dataType
     */
    public function setDataType($dataType) {
        $this->dataType = $dataType;
    }

    public function entryExists($key) {
        return isset($this->data[$key]);
    }

    public function getEntry($key) {
        if (!$this->entryExists($key)) {
            throw new DataSetException('No such entry');
        }
        return $this->data[$key];
    }

    public function toArray() {
        if (count($this->data) == 1) {
            return current($this->data);
        }
        return $this->data;
    }

    public function current() {
        return current($this->data);
    }

    public function next() {
        return next($this->data);
    }

    public function key() {
        return key($this->data);
    }

    public function valid() {
        return current($this->data);
    }

    public function rewind() {
        return reset($this->data);
    }

    public function count() {
        return $this->size();
    }

    public function length() {
        return $this->size();
    }

    public function size() {
        return count($this->data);
    }

    public function limit($length, $offset) {
        $i = 0;
        $result = new static();
        foreach ($this as $key=>$value) {
            if ($i < $offset || $i >= ($offset + $length)) {
                $i++;
                continue;
            }
            $result->add($key, $value);
            $i++;
        }
        return $result;
    }

    /**
     * Sort this DataSet by the fields and in the order specified
     *
     * $order has the following syntax:
     * array(
     *     {fieldname} => SORT_ASC|SORT_DESC,
     *     [{fieldname2} => SORT_ASC|SORT_DESC,
     *     [...]]
     * )
     * @param array $order Fields and order to sort
     * @return DataSet Sorted DataSet (new instance)
     */
    public function sort($order) {
        $data = $this->data;

        $dateTimeTools = new \DateTimeTools();
        uasort($data, function($a, $b) use($order, $dateTimeTools) {
            $diff = 1;
            $orderMultiplier = 1;
            foreach ($order as $sortField => $sortOrder) {
                $orderMultiplier = $sortOrder == SORT_ASC ? 1 : -1;
                $termOne = $dateTimeTools->isValidDate($a[$sortField]) ? strtotime($a[$sortField]) : $a[$sortField];
                $termTwo = $dateTimeTools->isValidDate($b[$sortField]) ? strtotime($b[$sortField]) : $b[$sortField];
                $diff    = $termOne < $termTwo;
                if ($termOne !== $termTwo) {
                    return ($diff ? -1 : 1) * $orderMultiplier;
                }
            }
            return ($diff ? -1 : 1) * $orderMultiplier;
        });

        return new static($data);
    }

    /**
     * Tell if the supplied argument is iterable
     * @todo Rethink this method, DataSet is always iterable, this is a general helper method
     * @param mixed $var Variable to test iterability
     * @return boolean True if $var is iterable, false otherwise
     */
    private function is_iterable($var) {
        return (is_array($var) || $var instanceof Traversable || $var instanceof stdClass);
    }

    /**
     * Returns a flipped version of this DataSet
     * @param array $arr Array to flip
     * @return DataSet Flipped DataSet (new instance)
     */
    public function flip() {
        $result = array();

        foreach ($this as $key => $subarr) {
            if (!$this->is_iterable($subarr)) {
                $result[0][$key] = $subarr;
                continue;
            }
            foreach ($subarr as $subkey => $subvalue) {
                 $result[$subkey][$key] = $subvalue;
            }
        }

        return new static($result);
    }

    /**
     * Sort the columns after the given array.
     * Not defined columns are sorted after the default
     * @param type $orderArr Array with the new order
     */
    public function sortColumns($orderArr) {
        foreach ($this->data as $key => $val) {
            $sortedData = array();
            foreach ($orderArr as $orderKey => $orderVal) {
                if(array_key_exists($orderVal, $val)){
                    $sortedData[$orderVal] = $val[$orderVal];
                } else {
                    unset($orderArr[$orderKey]);
                }
            }
            $this->data[$key] = array_merge($sortedData, $val);
        }
    }

    /**
     * This function returns the identifier of the DataSet
     *
     * @access public
     * @return string the identifier
     */
    public function getIdentifier(){
        return $this->identifier;
    }

    /**
     * This function sets the identifier of the DataSet
     *
     * @access public
     * @param string $identifier the identifier of the DataSet
     */
    public function setIdentifier($identifier){
        $this->identifier = $identifier;
    }
}
