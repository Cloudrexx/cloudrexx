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
 * EntityInterface Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */

namespace Cx\Core_Modules\Listing\Model\Entity;

/**
 * EntityInterface Class
 *
 * This class used to convert the entity objects into array and array
 * into entity objects
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
class EntityInterface implements Exportable, Importable
{

    protected $entityClass;

    /**
     * constructor
     */
    public function __construct()
    {

    }

    /**
     * This function is used to convert the array into entity objects.
     *
     * @param  array $data
     *
     * @return array return as entity object array
     */
    public function export($data)
    {
        if (empty($data)) {
            return;
        }

        $em = \Env::get('em');
        $entityClassMetaData    = $em->getClassMetadata($this->entityClass);
        $repository             = $em->getRepository($this->entityClass);
        $entities    = array();

        foreach ($data as $entityArray) {

            $entityObj = null;
            $primaryKeyName = $entityClassMetaData->getSingleIdentifierFieldName();

            if (!empty($entityArray[$primaryKeyName])) {
                $entityObj = $repository->findOneBy(array($primaryKeyName => $entityArray[$primaryKeyName]));
            }

            if (!$entityObj) {
                $entityObj = new $this->entityClass();
            }
            //get the association mappings
            $associationMappings = $entityClassMetaData->getAssociationMappings();
            //class methods
            $classMethods = get_class_methods($entityObj);

            foreach ($entityArray as $entityField => $entityValue) {
                $associationObj = null;

                if (!in_array('set' . ucfirst($entityField), $classMethods)) {
                    continue;
                }

                if ($entityClassMetaData->isSingleValuedAssociation($entityField)) {

                    $targetEntity = $associationMappings[$entityField]['targetEntity'];

                    $mappingEntityField = $em->getClassMetadata($targetEntity)->getSingleIdentifierFieldName();
                    //check the association entity
                    $mappingEntityValue = is_array($entityValue)
                                            ? (isset($entityValue[$mappingEntityField]) ? $entityValue[$mappingEntityField] : 0)
                                            : $entityValue;
                    if (!\FWValidator::isEmpty($mappingEntityValue)) {
                        $associationObj = $em->getRepository($targetEntity)->findOneBy(array($mappingEntityField => $mappingEntityValue));
                    }

                    if (!$associationObj) {
                        $associationObj = new $targetEntity();
                    }
                    if(is_array($entityValue)){
                        foreach ($entityValue as $method => $value) {
                            $associationObj->{'set' . ucfirst($method)}($value);
                        }
                    } else {
                        $entityObj->{'set' . ucfirst($entityField)}($associationObj);
                    }

                    $entityValue = $associationObj;
                }

                //checks if the string a serialized array
                if(\FWValidator::is_serialized($entityValue)) {
                    $entityValue = unserialize($entityValue);
                }

                $entityObj->{'set' . ucfirst($entityField)}($entityValue);

            }
            $entities[] = $entityObj;
        }
        return $entities;
    }

    /**
     * set the entity class
     *
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * This function is used to convert the entity object into array.
     *
     * @param  mixed Single or array of entity objects
     *
     * @return array  return as array
     */
    public function import($data)
    {
        // convert data into a array if its not an array
        if (!is_array($data)) {
            $data = array($data);
        }

        //create array from objects
        $resultArr = array();
        foreach ($data as $object) {

            if (!is_object($object) && !$object instanceof \Cx\Model\Base\EntityBase) {
                return;
            }

            $em = \Env::get('em');
            $associationEntityColumns = array();

            $entityClassMetaData = $em->getClassMetadata(get_class($object));
            $associationMappings = $entityClassMetaData->getAssociationMappings();
            if (!empty($associationMappings)) {
                foreach ($associationMappings as $field => $associationMapping) {
                    if ($entityClassMetaData->isSingleValuedAssociation($field) && in_array('get' . ucfirst($field), get_class_methods($object))) {
                        $associationObject = $object->{'get' . ucfirst($field)}();
                        if ($associationObject) {
                            //get association columns
                            $associationEntityColumn = $this->getColumnNamesByEntity($associationObject);
                            $associationEntityColumns[$field] = !\FWValidator::isEmpty($associationEntityColumn) ? $associationEntityColumn : '';
                        }
                    }
                }
            }
            //get entity columns
            $entityColumns = $this->getColumnNamesByEntity($object);
            $resultData = array_merge($entityColumns, $associationEntityColumns);
            $resultData['virtual'] = $object->isVirtual();

            $resultArr[] = $resultData;
        }

        return $resultArr;
    }

    /**
     * get column name and values form the given entity object
     *
     * @param object $entityObject
     *
     * @return array
     */
    public function getColumnNamesByEntity($entityObject)
    {
        $data = array();
        $entityClassMetaData  =  \Env::get('em')->getClassMetadata(get_class($entityObject));
        foreach ($entityClassMetaData->getColumnNames() as $column) {
            $field = $entityClassMetaData->getFieldName($column);
            $value = $entityClassMetaData->getFieldValue($entityObject, $field);
            if ($value instanceof \DateTime) {
                $value = $value->format('d.M.Y H:i:s');
            } elseif (is_array($value)) {
                $value = serialize($value);
            }
            $data[$field] = $value;
        }
        return $data;
    }

}
