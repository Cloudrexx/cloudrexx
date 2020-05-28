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
 * DoctrineRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_datasource
 */

namespace Cx\Core\DataSource\Model\Entity;

/**
 * DoctrineRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_datasource
 */

class DoctrineRepository extends DataSource {

    /**
     * List of operations supported by this DataSource
     * @var array List of operations
     */
    protected $supportedOperations = array('lt', 'gt', 'lte', 'gte', 'in', 'eq', 'not');

    /**
     * Returns a list of field names this DataSource consists of
     * @return array List of field names
     */
    public function listFields() {
        $em = $this->cx->getDb()->getEntityManager();
        $classMetadata = $em->getClassMetadata($this->getIdentifier());
        return array_merge(
            array_keys($classMetadata->getAssociationMappings()),
            $classMetadata->getFieldNames()
        );
    }

    /**
     * Perform initializations
     */
    protected function init() {
        if (!defined('FRONTEND_LANG_ID')) {
            // make sure translatable is properly initialized
            // maybe this should be part of Cx or in a postInit hook
            $this->cx->getDb()->getTranslationListener()->setTranslatableLocale(
                \FWLanguage::getLanguageCodeById(\FWLanguage::getDefaultLangId())
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentifierFieldNames() {
        $em = $this->cx->getDb()->getEntityManager();
        $metaData = $em->getClassMetadata($this->getIdentifier());
        return $metaData->getIdentifierFieldNames();
    }

    /**
     * Gets one or more entries from this DataSource
     *
     * If an argument is not provided, no restriction is made for this argument.
     * So if this is called without any arguments, all entries of this
     * DataSource are returned.
     * If no entry is found, an empty array is returned.
     * @todo test relations with composite key
     * @todo test n:n relations
     * @param array $elementId (optional) field=>value-type condition array identifying an entry
     * @param array $filter (optional) field=>value-type condition array, only supports = for now
     * @param array $order (optional) field=>order-type array, order is either "ASC" or "DESC"
     * @param int $limit (optional) If set, no more than $limit results are returned
     * @param int $offset (optional) Entry to start with
     * @param array $fieldList (optional) Limits the result to the values for the fields in this list
     * @throws \Exception If doctrine repository for this DataSource could not be found
     * @return array Two dimensional array (/table) of results (array($row=>array($fieldName=>$value)))
     */
    public function get(
        $elementId = array(),
        $filter = array(),
        $order = array(),
        $limit = 0,
        $offset = 0,
        $fieldList = array()
    ) {
        $this->init();
        $em = $this->cx->getDb()->getEntityManager();

        $criteria = array();

        // Add filters
        foreach ($filter as $field => $filterExpr) {
            foreach ($filterExpr as $operation=>$value) {
                if (!$this->supportsOperation($operation)) {
                    throw new \InvalidArgumentException(
                        'Operation "' . $operation . '" is not supported'
                    );
                }
                if ($operation == 'in') {
                    $value = explode(',', $value);
                }
                if (!isset($criteria[$field])) {
                    $criteria[$field] = array();
                }
                $criteria[$field][$operation] = $value;
            }
        }

        // Add id to filter (after other filters to prevent override)
        if (isset($elementId) && count($elementId)) {
            foreach ($elementId as $field=>$id) {
                if (empty($id)) {
                    continue;
                }
                $criteria[$field] = array('eq' => $id);
            }
        }

        // $order
        foreach ($order as $field=>$ascdesc) {
            if (
                !in_array($field, $fieldList) ||
                !in_array($ascdesc, array('ASC', 'DESC'))
            ) {
                unset($order[$field]);
            }
        }

        // if recursion is on we recurse for all "to 1" and n:n relations.
        // additionally we recurse for recursions forced by options.
        $configuredRecursions = array();
        if ($this->getOption('recurse')) {
            $configValues = array(
                'forcedRecursions' => array(),
                'skippedRecursions' => array(),
            );
            foreach ($configValues as $config=>&$configValue) {
                $configValue = $this->getOption($config);
                if (!is_array($configValue)) {
                    $configValue = array();
                }
            }
            $configuredRecursions = $this->resolveRecursedRelations(
                $configValues['forcedRecursions'],
                $configValues['skippedRecursions']
            );
        }

        $mappingTable = array();
        $qb = $em->createQueryBuilder();
        // Note: our hydrator takes over the indexing
        $qb->select('x')->from($this->getIdentifier(), 'x');
        // joins
        $i = 1;
        foreach ($configuredRecursions as $property=>$class) {
            $mappedProperty = str_replace(
                array_keys($mappingTable),
                array_values($mappingTable),
                $property
            );
            $mappingTable[$mappedProperty . '.'] = 'x' . $i . '.';

            $qb->addSelect('x' . $i);
            // Note: our hydrator takes over the indexing
            $qb->leftJoin($mappedProperty, 'x' . $i);
            $i++;
        }

        // $filter
        $i = 1;
        $classMetadata = $em->getClassMetadata($this->getIdentifier());
        $associationMappings = $classMetadata->getAssociationMappings();
        foreach ($criteria as $field=>$filterExpr) {
            foreach ($filterExpr as $operation=>$value) {
                $table = 'x.';

                // This allows filters on ManyToMany on the first level
                // TODO: Expand to make it work on all levels
                if (
                    $associationMappings[$field]['type'] == \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_MANY &&
                    isset($mappingTable['x.' . $field . '.'])
                ) {
                    $table = $mappingTable['x.' . $field . '.'];
                    $targetEntity = $associationMappings[$field]['targetEntity'];
                    $distantClassMetadata = $em->getClassMetadata($targetEntity);
                    if ($associationMappings[$field]['isOwningSide']) {
                        $field = current($associationMappings[$field]['relationToTargetKeyColumns']);
                    } else {
                        $field = current(
                            $distantClassMetadata->getAssociationMappings()[
                                $associationMappings[$field]['mappedBy']
                            ]['relationToSourceKeyColumns']
                        );
                    }
                    $field = $distantClassMetadata->fieldNames[$field];
                }

                $qb->andWhere($qb->expr()->$operation($table . $field, '?' . $i));
                $qb->setParameter($i, $value);
                $i++;
            }
        }
        // $order, $limit, $offset
        foreach ($order as $field=>$ascdesc) {
            $qb->orderBy('x.' . $field, $ascdesc);
        }
        // $limit, $offset
        if ($limit) {
            $qb->setMaxResults($limit);
            if ($offset) {
                $qb->setFirstResult($offset);
            }
        }
        $query = $qb->getQuery();
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $result = $query->getResult();

        // $fieldList
        $dataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet(
            $result,
            null,
            array(
                'recursions' => $configuredRecursions,
                'skipVirtual' => true,
                'dateFormatDatetime' => 'c',
                'dateFormatTimestamp' => 'c',
                'dateFormatDate' => ASCMS_DATE_FORMAT_INTERNATIONAL_DATE,
                'dateFormatTime' => ASCMS_DATE_FORMAT_INTERNATIONAL_TIME,
            )
        );

        if (count($fieldList)) {
            $dataFlipped = $dataSet->flip()->toArray(false);
            foreach ($dataFlipped as $key=>$value) {
                if (!in_array($key, $fieldList)) {
                    unset($dataFlipped[$key]);
                }
            }
            $dataSetFlipped = new \Cx\Core_Modules\Listing\Model\Entity\DataSet(
                $dataFlipped
            );
            $dataSet = $dataSetFlipped->flip();
        }

        return $dataSet->toArray(false);
    }

    /**
     * Prepares an array with all relation recursions to do for this DataSource
     *
     * Automatically recurses all "to 1" and n:n reltions
     * @param array $forcedRecursions List of relations to force anyway
     * @param array $skippedRecursions List of relations to not recurse to
     * @param string? $entityClass Fully qualified entity class name to parse relations of
     * @param array? $output Previously generated part of end result
     * @param string? $prefix Prefix for keys in $output
     * @param array? $exclusionList List of fully qualified class names to ignore
     */
    protected function resolveRecursedRelations($forcedRecursions, $skippedRecursions, $entityClass = '', $output = array(), $prefix = 'x.', $exclusionList = array()) {
        if (empty($entityClass)) {
            $entityClass = $this->getIdentifier();
        }
        if (in_array($entityClass, $exclusionList)) {
            return $output;
        }
        $exclusionList[] = $entityClass;
        $em = $this->cx->getDb()->getEntityManager();
        $metaData = $em->getClassMetadata($entityClass);
        // foreach relation
        foreach ($metaData->associationMappings as $relationField => $associationMapping) {
            if (
                in_array($associationMapping['targetEntity'], $exclusionList) &&
                $associationMapping['targetEntity'] != $entityClass
            ) {
                continue;
            }
            // if is "to 1" or n:n or is forced by config
            if (
                (
                    in_array($associationMapping['type'], array(
                        \Doctrine\ORM\Mapping\ClassMetadata::ONE_TO_ONE,
                        \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_ONE,
                        \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_MANY,
                    )) ||
                    in_array(substr($prefix . $relationField, 2), $forcedRecursions)
                ) &&
                !in_array(substr($prefix . $relationField, 2), $skippedRecursions)
            ) {
                // add to array
                $output[$prefix . $relationField] = $associationMapping['targetEntity'];
                // recurse
                $output = $this->resolveRecursedRelations(
                    $forcedRecursions,
                    $skippedRecursions,
                    $associationMapping['targetEntity'],
                    $output,
                    $prefix . $relationField . '.',
                    $exclusionList
                );
            }
        }
        ksort($output);
        return $output;
    }

    /**
     * Adds a new entry to this DataSource
     * @param array $data Field=>value-type array. Not all fields may be required.
     * @throws \Exception If something did not go as planned
     * @return string ID of the new entry
     */
    public function add($data) {
        $this->init();
        $em = $this->cx->getDb()->getEntityManager();
        $entityClass = $this->getIdentifier();
        $entityClassMetadata = $em->getClassMetadata($entityClass);
        $entity = $entityClassMetadata->newInstance();

        $this->setEntityData($entity, $data);

        $em->persist($entity);
        $em->flush();
        return $this->getEntityIndexData($entity);
    }
    
    /**
     * @todo: This method should be elsewhere
     */
    protected function getEntityIndexData($entity) {
        $em = $this->cx->getDb()->getEntityManager();
        $entityClassName = get_class($entity);
        $entityMetaData = $em->getClassMetadata($entityClassName);
        $entityIndexData = array();
        foreach ($entityMetaData->getIdentifierFieldNames() as $field) {
            $entityIndexData[$field] = $entityMetaData->getFieldValue($entity, $field);
        }
        return $entityIndexData;
    }

    /**
     * Updates an existing entry of this DataSource
     * @param array $elementId field=>value-type condition array identifying an entry
     * @param array $data Field=>value-type array. Not all fields are required.
     * @throws \Exception If something did not go as planned
     */
    public function update($elementId, $data) {
        $this->init();
        $em = $this->cx->getDb()->getEntityManager();
        $repo = $this->getRepository();

        $entity = $repo->findOneBy($elementId);

        if (!$entity) {
            throw new \Exception('Entry not found!');
        }

        $this->setEntityData($entity, $data);

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Drops an entry from this DataSource
     * @param array $elementId field=>value-type condition array identifying an entry
     * @throws \Exception If something did not go as planned
     */
    public function remove($elementId) {
        $this->init();
        $em = $this->cx->getDb()->getEntityManager();
        $repo = $this->getRepository();

        $entity = $repo->findOneBy($elementId);

        if (!$entity) {
            throw new \Exception('Entry not found!');
        }

        $em->remove($entity);
        $em->flush();
    }

    /**
     * Returns the repository for this DataSource
     * @return \Doctrine\ORM\EntityRepository Repository for this DataSource
     */
    protected function getRepository() {
        $em = $this->cx->getDb()->getEntityManager();
        $repo = $em->getRepository($this->getIdentifier());

        if (!$repo) {
            throw new \Exception('Repository not found!');
        }

        return $repo;
    }

    /**
     * Sets data for an entity
     * @todo Check relations
     * @param \Cx\Model\Base\EntityBase $entity Entity to set data
     * @param array $data Field=>$value-type array
     */
    protected function setEntityData($entity, $data) {
        $em = $this->cx->getDb()->getEntityManager();
        $entityClassMetadata = $em->getClassMetadata(get_class($entity));
        $primaryKeyNames = $entityClassMetadata->getIdentifierFieldNames(); //get primary key name
        $entityColumnNames = $entityClassMetadata->getColumnNames(); //get the names of all fields

        foreach($entityColumnNames as $column) {
            $name = $entityClassMetadata->getFieldName($column);
            if (/*in_array($name, $primaryKeyNames) ||*/ !isset($data[$name])) {
                continue;
            }

            $fieldDefinition = $entityClassMetadata->getFieldMapping($name);
            switch ($fieldDefinition['type']) {
                case 'datetime':
                case 'timestamp':
                case 'date':
                case 'time':
                    if (!$data[$name]) {
                        // Empty values must be NULL, or Doctrine will fail
                        $data[$name] = null;
                    }
                    if ($data[$name] && !($data[$name] instanceof \DateTime)) {
                        // Throws on invalid input
                        $data[$name] = new \DateTime($data[$name]);
                    }
                    break;
                case 'array':
                    // verify that the value is actually an array -> prevent to store other php data
                    if (!is_array($data[$name])) {
                        $data[$name] = array();
                    }
                    break;
            }

            $fieldSetMethodName = 'set'.preg_replace('/_([a-z])/', '\1', ucfirst($name));
            // set the value as property of the current object, so it is ready to be stored in the database
            $entity->$fieldSetMethodName($data[$name]);
        }
        
        $associationMappings = $entityClassMetadata->getAssociationMappings();
        $classMethods = get_class_methods($entity);
        foreach ($associationMappings as $field => $associationMapping) {
            if (!isset($data[$field])) {
                continue;
            }
            // handle many to many relations
            if ($associationMapping['type'] == \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_MANY) {
                // todo: handle or document this case
                if (is_array($data[$field])) {
                    continue;
                }

                // prepare data
                $foreignEntityIndexes = explode(',', $data[$field]);
                $targetRepo = $em->getRepository($associationMapping['targetEntity']);
                $foreignEntityClassMetadata = $em->getClassMetadata(
                    $associationMapping['targetEntity']
                );
                $primaryKeys = $foreignEntityClassMetadata->getIdentifierFieldNames();
                $addMethod = 'add'.preg_replace('/_([a-z])/', '\1', ucfirst($field));
                $getMethod = 'get'.preg_replace('/_([a-z])/', '\1', ucfirst($field));
                // foreach distant entity
                foreach ($foreignEntityIndexes as $foreignEntityIndex) {
                    // prepare data
                    $foreignEntityIds = explode('/', $foreignEntityIndex);
                    $foreignEntityIndexData = array();
                    foreach ($primaryKeys as $i=>$primaryFieldName) {
                        $foreignEntityIndexData[$primaryFieldName] = $foreignEntityIds[$i];
                    }
                    $this->cx->getEvents()->triggerEvent(
                        'preDistantEntityLoad',
                        array(
                            'targetEntityClassName' => $associationMapping['targetEntity'],
                            'targetId' => &$foreignEntityIndexData,
                        )
                    );
                    // find and add entity
                    $targetEntity = $targetRepo->findOneBy($foreignEntityIndexData);
                    if (!$targetEntity) {
                        throw new \Exception(
                            'Entity not found (' . $associationMapping['targetEntity'] . ' with ID ' . var_export($foreignEntityIndexData, true) . ')'
                        );
                    }
                    $existingAssociatedEntities = $entity->$getMethod();
                    foreach ($existingAssociatedEntities as $existingAssociatedEntity) {
                        if ($targetEntity == $existingAssociatedEntity) {
                            continue 2;
                        }
                    }
                    $entity->$addMethod($targetEntity);
                }
            } else if (
                $entityClassMetadata->isSingleValuedAssociation($field) &&
                in_array('set'.ucfirst($field), $classMethods)
            ) {
                $foreignId = $data[$field];
                if (is_array($foreignId)) {
                    $foreignId = current($foreignId);
                }
                $targetRepo = $em->getRepository($associationMapping['targetEntity']);
                $targetEntity = $targetRepo->findOneBy(array(
                    $associationMapping['joinColumns'][0]['referencedColumnName'] => $foreignId,
                ));
                if (!$targetEntity) {
                    throw new \Exception('Entity not found (' . $associationMapping['targetEntity'] . ' with ID ' . $foreignId . ')');
                }
                $setMethod = 'set'.ucfirst($field);
                $entity->$setMethod($targetEntity);
            }
        }
    }

    /**
     * Check if the DataSource is Gedmo\Loggable.
     *
     * @inheritDoc
     */
    public function isVersionable() : bool {
        return is_a($this->getIdentifier(), '\Gedmo\Loggable\Loggable', true);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentVersion(array $elementId) : int {
        $em = $this->cx->getDb()->getEntityManager();

        $logRepo = $em->getRepository(
            $this->cx->getDb()->getLoggableListener()->getLogEntryClassForEntityClass(
                $this->getIdentifier()
            )
        );

        // Caching seems to be an issue here!
        $em->clear();

        $entity = $logRepo->findOneBy(
            array(
                'objectId' => $elementId,
                'objectClass' => ltrim($this->getIdentifier(), '/'),
            ),
            array(
                'version' => DESC,
            )
        );

        //since we select one entry, we can get the version number of the first entry in our array
        return $entity->getVersion();
    }
}
