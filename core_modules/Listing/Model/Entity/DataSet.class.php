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
 * On import and export from and to files the contents will be cached.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
class DataSet extends \Cx\Model\Base\EntityBase implements \Iterator {
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

    /**
     * List of options
     * @var array
     */
    protected $options = array();

    /**
     * Constructor for DataSet
     *
     * @param array $data (optional) Array of data to convert and store
     * @param callable $converter (optional) Custom data converter
     * @param array $options (optional): Options for conversion
     * @todo: DataSet must be extended, that it can handle objects
     */
    public function __construct($data = array(), callable $converter = null, $options = array()) {
        if (!count($data)) {
            return;
        }
        $this->options = $options;
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
                if (!isset($convertedData[$key])) {
                    $convertedData[$key] = array();
                }
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
     * Add column to each entry
     *
     * @param $column string title of column
     */
    public function addColumn($column)
    {
        foreach ($this->data as $key=>$data) {
            $this->data[$key][$column] = '';
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

    /**
     * Default conversion of objects
     * @param Object $object Object to convert
     * @param string $key (Reference) Object key, might get replaced by object's ID
     * @param array $forbiddenClasses (Optional) List of classes to skip recursion of
     * @param string $prefix (Optional) Path to current object (see
     *                          \Cx\Core\DataSource\Model\Entity\DoctrineRepository)
     * @return array Converted data
     */
    protected function convertObject(
        $object,
        &$key,
        $forbiddenClasses = array('Doctrine\ORM\PersistentCollection'),
        $prefix = 'x.'
    ) {
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
                    $value = $value->format(ASCMS_DATE_FORMAT_DATETIME);
                } elseif (is_array($value)) {
                    $value = serialize($value);
                }
                $data[$field] = $value;
            }
            $associationMappings = $em->getClassMetadata(get_class($object))->getAssociationMappings();
            // loop over all relations of this entity
            foreach ($associationMappings as $field => $associationMapping) {
                $classMethods = get_class_methods($object);
                $methodBaseName = \Doctrine\Common\Inflector\Inflector::classify(
                    $field
                );
                $methodNameToFetchAssociation = 'get' . $methodBaseName;
                // stop if getter does not exist
                if (!in_array($methodNameToFetchAssociation, $classMethods)) {
                    continue;
                }
                $data[$field] = $object->$methodNameToFetchAssociation();
                // stop if recursion is not configured or target is not an object
                if (
                    !isset($this->options['recursions'][$prefix.$field]) ||
                    !is_object($data[$field])
                ) {
                    // If we don't recurse, we want to know about this field.
                    // If we recurse, it's misleading to show this field as
                    // an empty array even if it might have content.
                    if (count($this->options['recursions'])) {
                        unset($data[$field]);
                    }
                    continue;
                }
                $className = $associationMapping['targetEntity'];
                // stop if entity is already parsed
                if (
                    in_array(
                        $className,
                        $forbiddenClasses
                    )
                ) {
                    unset($data[$field]);
                    continue;
                }
                $foo = '';
                $data[$field] = $this->convertObject(
                    $data[$field],
                    $foo,
                    array_merge(
                        $forbiddenClasses,
                        array($className)
                    ),
                    $prefix . $field . '.'
                );
                // if a relation is empty, it's to be an empty object!
                if (!count($data[$field])) {
                    $data[$field] = new \stdClass();
                }
            }
            if (
                !isset($this->options['skipVirtual']) ||
                !$this->options['skipVirtual']
            ) {
                $data['virtual'] = $object->isVirtual();
            }
            return $data;
        }
        foreach ($object as $attribute => $property) {
            if (is_object($property)) {
                // if $object is an array collection, $attribute will be numeric
                if ($object instanceof \Doctrine\Common\Collections\Collection) {
                    // index collection entries by their "identifying field"
                    $attribute = $property->getKeyAsString();
                } else {
                    $prefix .= $attribute . '.';
                }
                $data[$attribute] = $this->convertObject(
                    $property,
                    $key,
                    array_merge(
                        $forbiddenClasses,
                        array(get_class($property))
                    ),
                    $prefix
                );
            } else {
                $data[$attribute] = $property;
            }
        }
        return $data;
    }

    protected static function getYamlInterface() {
        if (empty(static::$yamlInterface)) {
            static::$yamlInterface = new \Cx\Core_Modules\Listing\Model\Entity\YamlInterface();
        }
        return static::$yamlInterface;
    }

    public function toYaml() {
        return $this->export(static::getYamlInterface());
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
     * Imports a DataSet from a file using an import interface
     *
     * @param Cx\Core_Modules\Listing\Model\Entity\Importable $importInterface
     * @param string $filename
     * @param boolean $useCache Wether to try to load the file from cache or not
     * @throws \Cx\Lib\FileSystem\FileSystemException
     * @return \Cx\Core_Modules\Listing\Model\Entity\DataSet
     */
    public static function importFromFile(\Cx\Core_Modules\Listing\Model\Entity\Importable $importInterface, $filename, $useCache = true) {
        if ($useCache) {
            $cache = \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache');
            if (!$cache) {
                $useCache = false;
            }
        }
        if ($useCache) {
            // try to load imported from cache
            $objImport = $cache->fetch($filename);
            if ($objImport) {
                return $objImport;
            }
        }
        try {
            $objFile = new \Cx\Lib\FileSystem\File($filename);
            $objImport = static::import($importInterface, $objFile->getData());
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
            throw new DataSetException("Failed to load data from file $filename!");
        }
        if ($useCache) { // store imported to cache
            $cache->save($filename, $objImport);
        }
        return $objImport;
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
     * Exports a DataSet to a file using an export interface
     *
     * @param Cx\Core_Modules\Listing\Model\Entity\Exportable $exportInterface
     * @param string $filename
     * @param boolean $useCache
     * @throws \Cx\Lib\FileSystem\FileSystemException
     */
    public function exportToFile(\Cx\Core_Modules\Listing\Model\Entity\Exportable $exportInterface, $filename, $useCache = true) {
        try {
            $objFile = new \Cx\Lib\FileSystem\File($filename);
            $objFile->touch();
            $export = $this->export($exportInterface);
            $objFile->write($export);
            // delete old key from cache, to reload it on the next import
            if ($useCache) {
                $cache = \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache');
                if (!$cache) {
                    throw new DataSetException('Cache component not available at this stage!');
                }
                $cache->delete($filename);
            }
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
        return static::import(static::getYamlInterface(), $data);
    }

    /**
     *
     * @param string $filename
     * @param boolean $useCache Wether to try to load the file from cache or not
     * @throws \Cx\Lib\FileSystem\FileSystemException
     * @return type
     */
    public static function load($filename, $useCache = true) {
        return static::importFromFile(static::getYamlInterface(), $filename, $useCache);
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

    /**
     * Returns a list of all elements data of this DataSet as an array
     *
     * If there is only one entry in this DataSet, the entry is returned directly
     * instead of returning a list with just one element. If
     * $allowToReturnElement is set to false a list with just one element is
     * returned.
     * @param bool $allowToReturnElement (optional) If set to false this
     *                                      method always returns a list of
     *                                      elements.
     * @return array List of elements as an array
     */
    public function toArray($allowToReturnElement = true) {
        if ($allowToReturnElement && count($this->data) == 1) {
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
                if (!isset($result[0])) {
                    $result[0] = array();
                }
                $result[0][$key] = $subarr;
                continue;
            }
            foreach ($subarr as $subkey => $subvalue) {
                if (!isset($result[$subkey])) {
                    $result[$subkey] = array();
                }
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
     * Filters entries of this DataSet
     * @param callable $filterFunction
     */
    public function filter(callable $filterFunction) {
        foreach ($this->data as $key=>$entry) {
            if (!$filterFunction($entry)) {
                unset($this->data[$key]);
            }
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
