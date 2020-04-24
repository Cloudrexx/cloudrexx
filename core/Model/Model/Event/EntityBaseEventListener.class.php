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
 * Event listener to ensure data integrity
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Model\Event;

/**
 * Entity base exception
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */
class EntityBaseException extends \Exception { }

/**
 * Event listener to ensure data integrity
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */
class EntityBaseEventListener implements \Cx\Core\Event\Model\Entity\EventListener {

    /**
     * @var array List of entities to re-merge with EM
     */
    protected $virtualEntities = array();

    /**
     * Triggered on any model event
     * Will listen to 'model/onFlush' only. First entry in $eventArgs is the
     * doctrine EventArgs object.
     * @param string $eventName Internal event name
     * @param array $eventArgs List of event arguments
     */
    public function onEvent($eventName, array $eventArgs) {
        $em = current($eventArgs)->getEntityManager();
        switch ($eventName) {
            case 'model/onFlush':
                $uow = $em->getUnitOfWork();
                $this->checkEntities($uow->getScheduledEntityInsertions(), $em);
                $this->checkEntities($uow->getScheduledEntityUpdates(), $em);
                break;
            case 'model/postFlush':
                $this->reApplyVirtualEntityChangesets($em);
                break;
        }
    }
    
    /**
     * Checks a list of entities for their capability to be persisted
     * Checks all EntityBase derivated entities for being valid
     * ($entity->validate()) and not virtual.
     * @param array $entities List of entities to check
     * @param \Cx\Core\Model\Controller\EntityManager EntityManager
     */
    protected function checkEntities($entities, $em) {
        foreach ($entities AS $entity) {
            if (!is_a($entity, '\Cx\Model\Base\EntityBase')) {
                continue;
            }
            $entity->validate();
            if ($entity->isVirtual()) {
                $this->virtualEntities[] = $entity;
                $em->detach($entity);
            }
        }
    }

    /**
     * Merges virtual entity that were detached during checkEntities()
     * @param \Cx\Core\Model\Controller\EntityManager EntityManager
     */
    protected function reAddVirtualEntities($em) {
        foreach ($this->virtualEntities as $entity) {
            $em->merge($entity);
        }
        $this->virtualEntities = array();
    }
}

