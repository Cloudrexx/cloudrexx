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
 * YAML Repository
 *
 * A entity repository with its storage being in a YAML-file.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Controller;

/**
 * YAML Repository Exception
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */
class YamlRepositoryException extends \Exception {};

/**
 * YAML Repository
 *
 * A entity repository with its storage being in a YAML-file.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 * @todo        Make thread safe. The current implementation of YamlRepository
 *              does not take the case into account if the repository gets
 *              modified by a parallel operation. Changes made by a parallel
 *              operation will be discarded,merged or overwritten. The behavior
 *              is unknown!
 */
class YamlRepository implements \Countable {
    /**
     * Absolute path to the YAML-repository
     * @var string
     */
    protected $repositoryPath;

    /**
     * All entities currently loaded/attached to the repository
     * @var array
     */
    protected $entities;

    /**
     * The original set of entities in the repository
     * @var array
     */
    protected $originalEntitiesFromRepository;

    /**
     * All entities currently added to the repository
     * @var array
     */
    protected $addedEntities = array();

    /**
     * All entities currently removed from the repository
     * @var array
     */
    protected $removedEntities = array();

    /**
     * All entities currently modified in the repository
     * @var array
     */
    protected $updatedEntities = array();

    /**
     * Auto-increment value used for the primary identifier
     * when adding a new entity to the repository
     * @var integer
     */
    protected $entityAutoIncrement;

    /**
     * Primary identifier attribute of the entity model
     * @var string
     */
    protected $entityIdentifier;

    /**
     * Definition of the unique key attributes of the entity model
     * @var array
     */
    protected $entityUniqueKeys;

    /**
     * Initialize a YAML-Repository.
     * Load the entities stored in the file specified by $repositoryPath.
     * @param   string  $repositoryPath Absolute path in the file system to the YAML-repository
     */
    public function __construct($repositoryPath) {
        if (empty($repositoryPath)) {
            throw new YamlRepositoryException('No repository specified!');
       }

        $this->repositoryPath = $repositoryPath;
        $this->load();
    }

    /**
     * Tells wheter an entity is managed by this repo or not
     * @param \Cx\Model\Base\EntityBase $entity Entity to test
     * @return boolean True if given entity is managed by this repo, false otherwise
     */
    public function isManaged(\Cx\Model\Base\EntityBase $entity) {
        return in_array($entity, $this->entities);
    }

    /**
     * Reset the repository. Unload all data.
     */
    protected function reset() {
        $this->entities = array();
        $this->originalEntitiesFromRepository = array();
        $this->entityAutoIncrement = 1;
        $this->entityIdentifier = null;
        $this->entityUniqueKeys = array();
    }

    /**
     * Load repository from file system specified by $this->repositoryPath
     */
    protected function load() {
        $this->reset();

        if(!$this->fileExistsAndNotEmpty($this->repositoryPath)) return false;

        list($meta, $entities) = $this->loadData();

        $this->entityAutoIncrement = $meta['auto_increment'];
        $this->entityIdentifier = $meta['identifier'];
        $this->entityUniqueKeys = $meta['unique_keys'];

        if (!$entities) {
            \DBG::msg("YamlRepository: Empty repository $this->repositoryPath loaded.");
            return;
        }

        $this->entities = $entities;
        foreach ($this->entities as $identifier => $entity) {
            $this->originalEntitiesFromRepository[$identifier] = clone $entity;
        }
    }

    protected function loadData() {
        $entities = array();
        $repository = \Cx\Core_Modules\Listing\Model\Entity\DataSet::load($this->repositoryPath);
        if (!$repository->entryExists('meta')) {
            throw new YamlRepositoryException("Unable to load repository $this->repositoryPath. Repository is missing meta description!");
        }
        $meta = $repository->getEntry('meta');
        if ($repository->entryExists('data')) {
            foreach ($repository->getEntry('data') as $entity) {
                if (!($entity instanceof \Cx\Core\Model\Model\Entity\YamlEntity)) {
                    throw new YamlRepositoryException("Unable to load repository $this->repositoryPath. Repository contains invalid entity: ".serialize($entity));
                }
                $id = $this->getIdentifierOfEntity($entity, $meta['identifier']);
                $entities[$id] = $entity;
            }
        }

        return array($meta, $entities);
    }

    /**
     * Return all entities from repository
     */
    public function findAll() {
        return $this->entities;
    }

     /**
     * Return single entity specified by primary identifier $id
     * @param   mixed   Value of primary identifier
     * @return  YamlEntity  Object from repository identified by primary identifier $id
     */
    public function find($id) {
        // as YamlRepository does not support composite-keys,
        // we have to check if $id is an array (=> composite-key)
        // and if so, simply fetch the first element of the array
        // which will then be our primary key
        if (is_array($id)) {
            $id = current($id);
        }
        if (isset($this->entities[$id])) {
            return $this->entities[$id];
        }

        throw new YamlRepositoryException("No entity found by $this->entityIdentifier = $id!");
    }

    /**
     * Return an array of entities matching the criterias specified by $criteria.
     * @param   array   $criteria Arguments to filter the returned entities by
     * @return  array   Array containing the objects from the repository matching the filter criterias
     */
    public function findBy(array $criteria = array()) {
        $matchedEntities = array();

        foreach ($this->entities as $entity) {
            foreach ($criteria as $key => $value) {
                if (call_user_func(array($entity, "get".ucfirst($key))) != $value) {
                    continue 2;
                }
            }
            $matchedEntities[] = $entity;
        }

        return $matchedEntities;
    }

    public function findOneBy(array $criteria = array()) {
        $matchedEntities = $this->findBy($criteria);
        if (isset($matchedEntities[0])) {
            return $matchedEntities[0];
        }
        return null;
    }

    /**
     * Add new entity to the repository
     * Method {@see flush()} must triggered to persist the entity in the repository.
     * @param   YamlEntity  Entity to add to the repository
     */
    public function add($entity) {
        if (!($entity instanceof \Cx\Core\Model\Model\Entity\YamlEntity)) {
            throw new YamlRepositoryException("Entity must be of type YamlEntity!");
        }

        // check if entity already has an ID and if so if there is already
        // an other entity present in the repository with the same ID.
        $id = $this->getIdentifierOfEntity($entity);
        if ($id && isset($this->entities[$id])) {
            throw new YamlRepositoryException("Unable to add new entity to repository. A entity by the specified primary identifier {$this->getIdentifierOfEntity($entity)} is already present in the repository.");
        } else {
            // assign new ID to entity
            call_user_func(array($entity, "set".ucfirst($this->entityIdentifier)), $this->getNewEntityIdentifier());
        }
        $this->validate($entity);
        $this->entities[$this->getIdentifierOfEntity($entity)] = $entity;

        if (!$entity->isVirtual()) {
            $this->addedEntities[] = $entity;
            \Env::get('cx')->getEvents()->triggerEvent('model/prePersist', array(new \Doctrine\ORM\Event\LifecycleEventArgs($entity, \Env::get('em'))));
        }
    }

    /**
     * Remove entity from repository.
     * Entity will not be removed until the method {@see flush()} has been triggered.
     * @param   YamlEntity $entity The entity to remove from the repository.
     */
    public function remove($entity) {
        unset($this->entities[$this->getIdentifierOfEntity($entity)]);
        $this->removedEntities[] = $entity;
        \Env::get('cx')->getEvents()->triggerEvent('model/preRemove', array(new \Doctrine\ORM\Event\LifecycleEventArgs($entity, \Env::get('em'))));
    }

    /**
     * Flush the current state of the repository into the file system.
     */
    public function flush() {
        if (\Env::get('cx')->getMode() == \Cx\Core\Core\Controller\Cx::MODE_MINIMAL) {
            \DBG::msg('WARNING: '.__METHOD__.'() initialized in Cx mode "'.\Cx\Core\Core\Controller\Cx::MODE_MINIMAL.'". EventListeners have not yet been registered. This might break the system! Flushing a repository at this point is highly unadvisable!');
        }

        $entitiesToPersist = array();
        foreach ($this->entities as $entity) {
            // Validation must be done before checking for virtual entities.
            // As even virtual entities must comply with the unique-key restrictions.
            $this->validate($entity);
            if ($entity->isVirtual()) {
                if(!isset($this->originalEntitiesFromRepository[$this->getIdentifierOfEntity($entity)])) {
                    continue;
                }
            }
            if (isset($this->originalEntitiesFromRepository[$this->getIdentifierOfEntity($entity)])) {
                if ($this->originalEntitiesFromRepository[$this->getIdentifierOfEntity($entity)] != $entity) {
                    $this->updatedEntities[] = $entity;
                }
            }
            $entitiesToPersist[$this->getIdentifierOfEntity($entity)] = $entity;
        }

        foreach ($this->updatedEntities as $entity) {
            \Env::get('cx')->getEvents()->triggerEvent('model/preUpdate', array(new \Doctrine\ORM\Event\LifecycleEventArgs($entity, \Env::get('em'))));
        }

        $this->prepareFile($this->repositoryPath, $this->entityUniqueKeys, $this->entityIdentifier);
        $dataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet();
        $dataSet->add('data', $entitiesToPersist);
        $dataSet->add('meta', $this->getMetaDefinition());
        $dataSet->save($this->repositoryPath);

        // triger post-events
        // apply the same order of event-triggers as doctrine does:
        // 1. postPersist
        // 2. postUpdate
        // 3. postRemove
        foreach ($this->addedEntities as $entity) {
            \Env::get('cx')->getEvents()->triggerEvent('model/postPersist', array(new \Doctrine\ORM\Event\LifecycleEventArgs($entity, \Env::get('em'))));
        }
        foreach ($this->updatedEntities as $entity) {
            \Env::get('cx')->getEvents()->triggerEvent('model/postUpdate', array(new \Doctrine\ORM\Event\LifecycleEventArgs($entity, \Env::get('em'))));
        }
        foreach ($this->removedEntities as $entity) {
            \Env::get('cx')->getEvents()->triggerEvent('model/postRemove', array(new \Doctrine\ORM\Event\LifecycleEventArgs($entity, \Env::get('em'))));
        }

        //truncate the variables
        $this->addedEntities = array();
        $this->updatedEntities = array();
        $this->removedEntities = array();

        \Env::get('cx')->getEvents()->triggerEvent('model/postFlush', array(new \Doctrine\ORM\Event\OnFlushEventArgs(\Env::get('em')), $this->repositoryPath));
    }

    /**
     * Generate meta definition to be stored in the repository
     * @return array    Meta definition
     */
    protected function getMetaDefinition() {
        return array(
            'auto_increment'    => $this->entityAutoIncrement,
            'identifier'        => $this->entityIdentifier,
            'unique_keys'       => $this->entityUniqueKeys,
        );
    }

    /**
     * Validate the entity to comply with any unique-key constraints
     * @param   YamlEntity The entity to validate
     */
    protected function validate($entity) {
        foreach ($this->entityUniqueKeys as $uniqueKey) {
            $value = call_user_func(array($entity, "get".ucfirst($uniqueKey)));
            $matchedEntities = $this->findBy(array($uniqueKey => $value));

            // if no other entities use the same uniqueKey then we're good
            if (!count($matchedEntities)) {
                continue;
            }
            if (count($matchedEntities) == 1) {
                // it's fine in case the entity with the same uniqueKey is actually the entity we are validating
                if ($this->getIdentifierOfEntity(current($matchedEntities)) == $this->getIdentifierOfEntity($entity)) {
                    continue;
                }
            }

            throw new YamlRepositoryException("Attribute $uniqueKey ($value) of entity #{$this->getIdentifierOfEntity($entity)} must be unique!");
        }
    }

    /**
     * Generate and get a new primary identifier
     * @return integer New primary identifier
     */
    protected function getNewEntityIdentifier() {
        return ++$this->entityAutoIncrement;
    }

    /**
     * Get the primary identifier of an entity
     * @param   YamlEntity $entity The entity of which to get the primary identifier from
     * @return  integer The primary identifier of the supplied entity
     */
    protected function getIdentifierOfEntity($entity, $identifierKey = null) {
        if (!$identifierKey) {
            $identifierKey = $this->entityIdentifier;
        }
        return call_user_func(array($entity, "get".ucfirst($identifierKey)));
    }

    /**
     * Checks if file exists, if not - creates new one with metadata
     *
     * @param string $filename
     * @param array $unique_keys
     * @param string $identifier
     * @return boolean
     * @throws \Cx\Core\Setting\Controller\SettingException
     */
    protected function prepareFile($filename, $unique_keys = array(), $identifier) {

        if($this->fileExistsAndNotEmpty($filename)) return true;
        \DBG::log('Creating new file');
        try {
            $file = new \Cx\Lib\FileSystem\File($filename);
            $file->touch();
            $data = trim($file->getData());
            if(empty($data)) {
                $inidata =
                    "meta:\n" .
                    "   auto_increment: 1\n";
                if(!empty($identifier)) {
                    $inidata .= "   identifier: $identifier\n";
                }
                if(!empty($unique_keys)) {
                    $inidata .= "   unique_keys:\n";
                    foreach($unique_keys as $key) {
                        $inidata .= "        - $key";
                    }
                }
                $file->write($inidata);
            }
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::log('EX ' . $e->getMessage());
            throw new \Cx\Core\Setting\Controller\YamlRepositoryException($e->getMessage());
        }
    }

    /**
     * Checks if file exists and is not empty
     *
     * @param string $filename
     * @return bool
     */
    protected function fileExistsAndNotEmpty($filename) {
        return (file_exists($filename) && filesize($filename) > 0);
    }

    /**
     * Returns the total count of entities
     * @see http://php.net/manual/en/class.countable.php
     * @return int Total count of entities
     */
    public function count() {
        return count($this->entities);
    }
}
