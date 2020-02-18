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
 * Sync
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */

namespace Cx\Core_Modules\Sync\Model\Entity;

/**
 * Sync
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
class Sync extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $toUri
     */
    protected $toUri;

    /**
     * @var string $apiKey
     */
    protected $apiKey;

    /**
     * @var boolean $active
     */
    protected $active;

    /**
     * @var \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess
     */
    protected $dataAccess;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $relations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $hostEntities;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $changes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $originChanges;
    
    protected $oldHostEntities = array();

    public function __construct()
    {
        $this->relations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->hostEntities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->changes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->originChanges = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set toUri
     *
     * @param string $toUri
     */
    public function setToUri($toUri) {
        $this->toUri = $toUri;
    }

    /**
     * Get toUri
     *
     * @return string
     */
    public function getToUri($entityIndexData = array()) {
        if (!count($entityIndexData)) {
            return $this->toUri();
        }
        
        $indexData = implode('/', $entityIndexData);
        
        //<domain>(/<offset>)/api/sync/<apiVersion>/<outputModule>/<dataSource>/<parameters>[(?apikey=<apikey>(&<options>))|?<options>]
        //<domain>(/<offset>)/api/sync/<apiVersion>/<outputModule>/[[DATA_SOURCE]]/[[INDEX_DATA]][(?apikey=[[API_KEY]](&<options>))|?<options>]
        $uri = $this->toUri;
        $uri = str_replace('[[DATA_SOURCE]]', $this->getDataAccess()->getName(), $uri);
        $uri = str_replace('[[API_KEY]]', $this->getApiKey(), $uri);
        $uri = str_replace('[[INDEX_DATA]]', $indexData, $uri);
        return $uri;
    }

    /**
     * Set apiKey
     *
     * @param string $apiKey
     */
    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Get apiKey
     *
     * @return string
     */
    public function getApiKey() {
        return $this->apiKey;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active) {
        $this->active = $active;
    }
    
    public function setTempActive($tempActive) {
        $this->tempActive = $tempActive;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive() {
        return $this->active || $this->tempActive;
    }

    /**
     * Set dataAccess
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess
     */
    public function setDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }

    /**
     * Get dataAccess
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess
     */
    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    /**
     * Add relation
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Relation $relation
     */
    public function addRelation(\Cx\Core_Modules\Sync\Model\Entity\Relation $relation)
    {
        $this->relations[] = $relation;
    }

    /**
     * Remove relations
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Relation $relations
     */
    public function removeRelation(\Cx\Core_Modules\Sync\Model\Entity\Relation $relations)
    {
        $this->relations->removeElement($relations);
    }

    /**
     * Get relations
     *
     * @return \Doctrine\Common\Collections\Collection $relations
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Set Relations
     *
     * @param array $relations
     */
    public function setRelations($relations)
    {
        $this->relations[] = $relations;
    }

    /**
     * Add hostEntity
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntity
     */
    public function addHostEntity(\Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntity)
    {
        $this->hostEntities[] = $hostEntity;
    }

    /**
     * Remove hostEntities
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntities
     */
    public function removeHostEntity(\Cx\Core_Modules\Sync\Model\Entity\HostEntity $hostEntities)
    {
        $this->hostEntities->removeElement($hostEntities);
    }

    /**
     * Get hostEntities
     *
     * @return \Doctrine\Common\Collections\Collection $hostEntities
     */
    public function getHostEntities()
    {
        return $this->hostEntities;
    }
    
    // Customizing for old Calendar sync config:
    public function getHostEntitiesIncludingLegacy($cached = true) {
        if ($cached && isset($this->cachedHostEntities)) {
            return $this->cachedHostEntities;
        }
        $hostEntities = array();
        
        foreach ($this->getHostEntities() as $hostEntity) {
            $hostEntities[] = array(
                'entityId' => $hostEntity->getEntityId(),
                'host' => $hostEntity->getHost(),
            );
        }
        
        //if we're syncing Events:
        if (
            $this->getDataAccess()->getDataSource()->getIdentifier() !=
            'Cx\\Modules\\Calendar\\Model\\Entity\\Event'
        ) {
            return $hostEntities;
        }
        
        //load all contrexx_module_calendar_rel_event_host joined to contrexx_module_calendar_host
        global $objDatabase;
        $query = '
            SELECT
                `host`.`uri`,
                `host_entity`.`event_id`
            FROM
                `' . DBPREFIX . 'module_calendar_rel_event_host` AS `host_entity`
            JOIN
                `' . DBPREFIX . 'module_calendar_host` AS `host`
            ON
                `host_entity`.`host_id` = `host`.`id`
            WHERE
                `host`.`status` = 0
        ';
        $results = $objDatabase->Execute($query);
        if (!$results || $results->EOF) {
            return $hostEntities;
        }
        $em = $this->cx->getDb()->getEntityManager();
        $hostRepo = $em->getRepository('Cx\Core_Modules\Sync\Model\Entity\Host');
        while (!$results->EOF) {
            $host = $hostRepo->findOneBy(array(
                'host' => $results->fields['uri'],
            ));
            if (!$host) {
                $results->moveNext();
                continue;
            }
            $hostEntities[] = array(
                'entityId' => $results->fields['event_id'],
                'host' => $host,
            );
            
            $results->moveNext();
        }
        $this->cachedHostEntities = $hostEntities;
        return $hostEntities;
    }
    
    public function setOldHostEntitiesIncludingLegacy($hostEntities) {
        $this->oldHostEntities = $hostEntities;
    }
    
    public function getOldHostEntitiesIncludingLegacy() {
        return $this->oldHostEntities;
    }
    // end customizing
    
    protected function isEntityInHostEntity($entityIndexData, $hostEntity) {
        // is there a host we should sync this entity to?
        if (!$hostEntity['host']->getActive()) {
            return false;
        }
        if (
            $hostEntity['entityId'] != '*' &&
            $hostEntity['entityId'] != current($entityIndexData)
        ) {
            return false;
        }
        return true;
    }
    
    public function getRemovedHosts($entityIndexData) {
        $removedHosts = array();
        
        foreach ($this->oldHostEntities as $oldHostEntity) {
            // if oldHostEntity was not or still is in hostEntities
            if (!$this->isEntityInHostEntity($entityIndexData, $oldHostEntity)) {
                continue;
            }
            foreach ($this->getHostEntitiesIncludingLegacy() as $hostEntity) {
                if ($this->isEntityInHostEntity($entityIndexData, $hostEntity)) {
                    continue;
                }
                if (in_array($oldHostEntity['host'], $removedHosts)) {
                    continue;
                }
                // else we should remove the entity from this host
                $removedHosts[] = $oldHostEntity['host'];
            }
        }
        return $removedHosts;
    }

    /**
     * Set hostEntities
     *
     * @param array $hostEntities
     */
    public function setHostEntities($hostEntities)
    {
        $this->hostEntities[] = $hostEntities;
    }

    /**
     * Add change
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Change $change
     */
    public function addChange(\Cx\Core_Modules\Sync\Model\Entity\Change $change)
    {
        $this->changes[] = $change;
    }

    /**
     * Remove changes
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Change $changes
     */
    public function removeChange(\Cx\Core_Modules\Sync\Model\Entity\Change $changes)
    {
        $this->changes->removeElement($changes);
    }

    /**
     * Get changes
     *
     * @return \Doctrine\Common\Collections\Collection $changes
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set Changes
     *
     * @param array $changes
     */
    public function setChanges($changes)
    {
        $this->changes[] = $changes;
    }

    /**
     * Add originChanges
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Change $originChanges
     * @return Sync
     */
    public function addOriginChange(\Cx\Core_Modules\Sync\Model\Entity\Change $originChanges)
    {
        $this->originChanges[] = $originChanges;

        return $this;
    }

    /**
     * Remove originChanges
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Change $originChanges
     */
    public function removeOriginChange(\Cx\Core_Modules\Sync\Model\Entity\Change $originChanges)
    {
        $this->originChanges->removeElement($originChanges);
    }

    /**
     * Get originChanges
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOriginChanges()
    {
        return $this->originChanges;
    }
    
    public function calculateRelations($spooler, $eventType, $entityClassName, $entityIndexData, $entity) {
        $changeCondition = 'forward';
        if ($eventType == 'delete') {
            $changeCondition = 'both';
        }
        
        $this->calculateChangeset($spooler, $eventType, $entityClassName, $entityIndexData, $entity, $changeCondition);
        
        if ($eventType != 'delete') {
            $this->calculateChangeset($spooler, 'delete', $entityClassName, $entityIndexData, $entity, 'delete');
        }
    }
    
    protected function calculateChangeset($spooler, $eventType, $entityClassName, $entityIndexData, $entity, $changeCondition) {
        $changes = new \Cx\Core_Modules\Sync\Model\Entity\Changeset(
            $entityIndexData,
            $entityClassName,
            $entity,
            $eventType,
            $this,
            $changeCondition
        );
        
        foreach ($changes->getChanges() as $change) {
            // add all related (old and current) hosts
            //$change->setHosts($this->getRelatedHosts($entityIndexData));
            
            // if it's not a DELETE change
            if ($change->getEventType() != 'delete') {
                // add contents
                $change->setContents($this->calculateContent($change));
            }
        }
        
        $spooler->addChangeset($changes);
    }
    
    public function getRelatedHosts($entityIndexData) {
        $relatedHosts = array();
        foreach ($this->getOldHostEntitiesIncludingLegacy() as $hostEntity) {
            if (in_array($hostEntity['host'], $relatedHosts)) {
                continue;
            }
            if (!$this->isEntityInHostEntity($entityIndexData, $hostEntity)) {
                continue;
            }
            $relatedHosts[] = $hostEntity['host'];
        }
        foreach ($this->getHostEntitiesIncludingLegacy(false) as $hostEntity) {
            if (in_array($hostEntity['host'], $relatedHosts)) {
                continue;
            }
            if (!$this->isEntityInHostEntity($entityIndexData, $hostEntity)) {
                continue;
            }
            $relatedHosts[] = $hostEntity['host'];
        }
        foreach ($this->getRemovedHosts($entityIndexData) as $host) {
            if (in_array($host, $relatedHosts)) {
                continue;
            }
            $relatedHosts[] = $host;
        }
        return $relatedHosts;
    }
    
    protected function calculateContent($change) {
        $entity = $change->getEntity();
        // Entity is not set on all changesets
        if (!$entity) {
            return $change->getContents();
        }

        // customizing for current calendar:
        if (
            get_class($entity) == 'Cx\\Modules\\Calendar\\Model\\Entity\\Event'
        ) {
            // from CalendarEventManager:
            if ($entity->getRegistration() == \Cx\Modules\Calendar\Controller\CalendarEvent::EVENT_REGISTRATION_INTERNAL) {
                // set registration to "extern" and registration uri to our host:
                $entity->setRegistration(
                    \Cx\Modules\Calendar\Controller\CalendarEvent::EVENT_REGISTRATION_EXTERNAL
                );
                
                // we use the legacy URL format in order to have dynamic language
                $url = 'http://bpw.ch/?section=Calendar&cmd=register&id=' . $entity->getId() . '&date=[[SERIES_ELEMENT_STARTDATE]]';
                $entity->setRegistrationExternalLink($url);
                
                // from CalendarEventManager:
                $fullyBooked = true;
                $calendarEvent = new \Cx\Modules\Calendar\Controller\CalendarEvent($entity->getId());
                if (
                    empty($calendarEvent->numSubscriber) ||
                    !\FWValidator::isEmpty($calendarEvent->getFreePlaces())
                ) {
                    $fullyBooked = false;
                }
                $entity->setRegistrationExternalFullyBooked($fullyBooked);
            }
            
            $entity->setPlaceMap($this->makeLinkAbsolute($entity->getPlaceMap(), true));
            $entity->setPic($this->makeLinkAbsolute($entity->getPic(), true));
            $entity->setAttach($this->makeLinkAbsolute($entity->getAttach(), true));
        } else if (
            get_class($entity) == 'Cx\\Modules\\Calendar\\Model\\Entity\\EventField'
        ) {
            $entity->setDescription($this->makeLinkAbsolute($entity->getDescription()));
            $entity->setRedirect($this->makeLinkAbsolute($entity->getRedirect(), true));
        }
        // end customizing
        
        $em = $this->cx->getDb()->getEntityManager();
        if (substr(get_class($entity), 0, 16) == 'Cx\Model\Proxies') {
            $em->refresh($entity);
            $entityClassName = $em->getClassMetadata(get_class($entity))->name;
        }
        $content = array();
        $metaData = $em->getClassMetadata(get_class($entity));
        $primaryKeyNames = $metaData->getIdentifierFieldNames(); // get the name of primary key in database table
        foreach ($metaData->getColumnNames() as $column) {
            $field = $metaData->getFieldName($column);
            if (in_array($field, $primaryKeyNames)) {
                //continue;
            }
            $content[$field] = $metaData->getFieldValue($entity, $field);
            if (is_object($content[$field]) && get_class($content[$field]) == 'DateTime') {
                $content[$field] = $content[$field]->format(DATE_ATOM);
            }
        }
        $associationMappings = $metaData->getAssociationMappings();
        $classMethods = get_class_methods($entity);
        foreach ($associationMappings as $field => $associationMapping) {
            if (   $metaData->isSingleValuedAssociation($field)
                && in_array('set'.ucfirst($field), $classMethods)
            ) {
                if ($metaData->getFieldValue($entity, $field)) {
                    $foreignEntity = $metaData->getFieldValue($entity, $field);
                    $indexData = $this->getComponentController()->getEntityIndexData($foreignEntity);
                    $content[$field] = implode('/', $indexData);
                    continue;
                }
                $content[$field]= new $associationMapping['targetEntity']();
            } elseif ($metaData->isCollectionValuedAssociation($field)) {
                $content[$field]= new $associationMapping['targetEntity']();
            }
        }
        return $content;
    }
    
    protected function makeLinkAbsolute($input, $simpleField = false) {
        $input = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $input);
        $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
        \LinkGenerator::parseTemplate($input, true, $domainRepo->getMainDomain());
        $domain = 'http://bpw.ch';
        if ($simpleField && !empty($input) && substr($input, 0, strlen($domain)) != $domain) {
            $input = $domain . $input;
        }
        if (!$simpleField) {
            // this replaces image paths...
            $allImg = array();
            preg_match_all('/(?:src|href|action)="([^"]*)"/', $input, $allImg, PREG_PATTERN_ORDER);
            $size = sizeof($allImg[1]);
            
            $i = 0;
            while ($i < $size) {
                $URLforReplace = $allImg[1][$i];
                
                $replaceUrl = new \Cx\Core\Routing\Url($URLforReplace, true);
                if ($replaceUrl->isInternal()) {
                    $ReplaceWith = $replaceUrl->toString();
                } else {
                    $ReplaceWith = $URLforReplace;
                }
                
                $input = str_replace('"'.$URLforReplace.'"', '"'.$ReplaceWith.'"', $input);
                $i++;
            }
        }
        return $input;
    }
}
