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
 * Host
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */

namespace Cx\Core_Modules\Sync\Model\Entity;

/**
 * Host
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
class Change extends \Cx\Model\Base\EntityBase {
    
    /**
     * @var int
     */
    protected $id;
    
    /**
     * @var \Cx\Core_Modules\Sync\Model\Entity\Sync
     */
    protected $sync;
    
    /**
     * @var \Cx\Core_Modules\Sync\Model\Entity\Host
     */
    protected $host;

    /**
     * @var int
     */
    protected $status;
    
    /**
     * @var string One of "delete", "put", "post"
     */
    protected $eventType;
    
    /**
     * @var array
     */
    protected $entityIndexData;
    
    /**
     * @var string <entityNs><slashDelimitedEntityIndexData>
     */
    protected $entityIdentifier;
    
    /**
     * @var \Cx\Model\Base\EntityBase
     */
    protected $entity = null;
    
    /**
     * @var array Field=>value type array
     */
    protected $contents = array();
    
    /**
     * Create a new change object
     * @param \Cx\Core_Modules\Sync\Model\Entity\Sync $sync Related sync object
     * @param \Cx\Core_Modules\Sync\Model\Entity\Host $host Related host object
     * @param string $eventType One of "delete", "put", "post"
     * @param array $entityIndexData Related entity's index data
     * @param string $entityIdentifier <entityNs><slashDelimitedEntityIndexData>
     * @param array|\Cx\Model\Base\EntityBase $entityOrContents Related entity or Field=>value type array of contents
     */
    public function __construct($sync, $host, $eventType, $entityIndexData, $entityIdentifier, $entityOrContents) {
        $this->sync = $sync;
        $this->host = $host;
        $this->status = 0;
        $this->eventType = $eventType;
        $this->entityIndexData = $entityIndexData;
        $this->entityIdentifier = $entityIdentifier;
        if (is_array($entityOrContents)) {
            $this->contents = $entityOrContents;
        } else {
            $this->entity = $entityOrContents;
        }
    }
    
    /**
     * Sets ID
     * @param int $id New ID
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    /**
     * Gets ID
     * @return int ID
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Sets related sync object
     * @param \Cx\Core_Modules\Sync\Model\Entity\Sync $sync New sync
     */
    public function setSync($sync) {
        $this->sync = $sync;
    }
    
    /**
     * Gets related sync object
     * @return \Cx\Core_Modules\Sync\Model\Entity\Sync Related sync
     */
    public function getSync() {
        return $this->sync;
    }
    
    /**
     * Sets related Host object
     * @param \Cx\Core_Modules\Sync\Model\Entity\Host $host New Host
     */
    public function setHost($host) {
        $this->host = $host;
    }
    
    /**
     * Gets related Host object
     * @return \Cx\Core_Modules\Sync\Model\Entity\Host Related Host
     */
    public function getHost() {
        return $this->host;
    }

    public function setStatus($status) {
        $this->status = $status;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    /**
     * Sets event type
     * @param string $eventType One of "delete", "put", "post"
     */
    public function setEventType($eventType) {
        $this->eventType = $eventType;
    }
    
    /**
     * Gets event type
     * @return string One of "delete", "put", "post"
     */
    public function getEventType() {
        return $this->eventType;
    }
    
    /**
     * Sets related entity's index data
     * @param array $entityIndexData Entity index data
     */
    public function setEntityIndexData($entityIndexData) {
        $this->entityIndexData = $entityIndexData;
    }
    
    /**
     * Gets related entity's index data
     * @return array Entity index data
     */
    public function getEntityIndexData() {
        return $this->entityIndexData;
    }
    
    /**
     * Sets related entity's identifier (<entityNs><slashDelimitedEntityIndexData>)
     * @param string $entityIdentifier Entity identifier
     */
    public function setEntityIdentifier($entityIdentifier) {
        $this->entityIdentifier = $entityIdentifier;
    }
    
    /**
     * Gets related entity's identifier
     * @return string Entity identifier
     */
    public function getEntityIdentifier() {
        return $this->entityIdentifier;
    }
    
    /**
     * Sets related entity
     * @param \Cx\Model\Base\EntityBase $entity New entity
     */
    public function setEntity($entity) {
        $this->entity = $entity;
    }
    
    /**
     * Gets related entity
     * @return \Cx\Model\Base\EntityBase Related entity
     */
    public function getEntity() {
        return $this->entity;
    }
    
    /**
     * Sets request contents
     * @param array $contents Field=>value type array
     */
    public function setContents($contents) {
        $this->contents = $contents;
    }
    
    /**
     * Gets request contents
     * @return array Field=>value type array
     */
    public function getContents() {
        return $this->contents;
    }
}

