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
 * Model event listener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_event
 */

namespace Cx\Core\Event\Model\Entity;

/**
 * Model event listener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_event
 */

class ModelEventListener implements EventListener {
    protected $entityClass = null;
    protected $listener = null;

    public function __construct($event, $entityClass, $listener) {
        if (!is_callable($listener) && !($listener instanceof \Cx\Core\Event\Model\Entity\EventListener)) {
            throw new \Cx\Core\Event\Controller\EventManagerException('Listener must be callable or implement EventListener interface!');
        }
        $this->entityClass = $entityClass;
        $this->listener = $listener;
    }

    public function onEvent($eventName, array $eventArgs) {
        $em = current($eventArgs);
        if (
            $em instanceof \Doctrine\ORM\Event\LifecycleEventArgs &&
            get_class($em->getEntity()) != $this->entityClass &&
            get_class($em->getEntity()) != 'Cx\\Model\\Proxies\\' .
                \Doctrine\Common\Persistence\Proxy::MARKER . '\\' . $this->entityClass
            // Important: the above two get_class() conditions could also be replace by the following:
            // !($eventArgs->getEntity() instanceof $this->entityClass)
            //
            // But this causes unexpected results. In case a model does extend an other model,
            // then all events registered to the base model are inherited by extending model.
            // The latter might not be wanted. Therefore events must explicitly be registered
            // to extending models and thus we shall not use the simplified 'instanceof'
            // check at this point.
        ) {
            return;
        }
        // onFlush has different arguments
        if (
            !is_a(
                $this->entityClass,
                'Cx\Core\Model\Model\Entity\YamlEntity',
                true
            ) &&
            $em instanceof \Doctrine\ORM\Event\OnFlushEventArgs
        ) {
            $em = $em->getEntityManager();
            $uow = $em->getUnitOfWork();
            $entityClasses = array();
            foreach (
                array(
                    'EntityInsertions',
                    'EntityUpdates',
                    'EntityDeletions',
                    'CollectionDeletions',
                    'CollectionUpdates',
                ) as $method
            ) {
                $method = 'getScheduled' . $method;
                $entityClasses += array_unique(array_map('get_class', $uow->$method()));
            }
            $entityClasses = array_unique($entityClasses);
            $proxyClass = 'Cx\\Model\\Proxies\\' .
                \Doctrine\Common\Persistence\Proxy::MARKER . '\\' . $this->entityClass;
            if (
                !in_array($this->entityClass, $entityClasses) &&
                !in_array($proxyClass, $entityClasses)
            ) {
                return;
            }
        }
        $eventName = substr($eventName, 6);
        if (is_callable($this->listener)) {
            $listener = $this->listener;
            $listener($eventName, $eventArgs);
        } else {
            $this->listener->onEvent($eventName, $eventArgs);
        }
    }
}
