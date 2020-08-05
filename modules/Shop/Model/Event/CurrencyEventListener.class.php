<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2018
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
 * Event listener for all currency events
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Event;

/**
 * Event listener for all currency events
 *ö
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class CurrencyEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{
    /**
     * Overwrite this method to ensure that only one currency entity is the
     * default entity if the new entity is created.
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
     *
     * @throws \Doctrine\Orm\OptimisticLockException
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     */
    public function prePersist(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs)
    {
        global $_ARRAYLANG;

        if ($this->checkIfCurrencyCodeExists(
            $eventArgs->getEntity()->getCode(),
            $eventArgs->getEntityManager()
        )) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                sprintf(
                    $_ARRAYLANG['TXT_SHOP_CURRENCY_EXISTS'],
                    $eventArgs->getEntity()->getCode()
                )
            );
        }

        if (!empty($eventArgs->getEntity()->getDefault())) {
            $this->setDefaultEntity(
                $eventArgs->getEntity()->getId(),
                $eventArgs->getEntityManager()
            );
        }
    }

    /**
     * Overwrite this method to ensure that only one currency entity is the
     * default entity if the entity is updated.
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
     *
     * @throws \Doctrine\Orm\OptimisticLockException
     */
    public function preUpdate(\Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs)
    {
        if (!empty($eventArgs->getEntity()->getDefault())) {
            $this->setDefaultEntity(
                $eventArgs->getEntity()->getId(),
                $eventArgs->getEntityManager()
            );
        }
    }

    /**
     * Search for a currency and return if it was found
     *
     * @param string                      $code currency code
     * @param \Doctrine\ORM\EntityManager $em associated EntityManager
     * @return bool currency already exists
     */
    protected function checkIfCurrencyCodeExists($code, $em)
    {
        $repo = $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\Currency'
        );
        $existingEntity = $repo->findOneBy(array('code' => $code));

        return !empty($existingEntity);
    }

    /**
     * Set default attribute for all currency entities to false if the handed
     * currency is the new default entity.
     *
     * @param $persistedEntityId int          id of persistent entity
     * @param $em \Doctrine\ORM\EntityManager associated EntityManager
     *
     * @throws \Doctrine\Orm\OptimisticLockException
     */
    protected function setDefaultEntity($persistedEntityId, $em)
    {
        $repo = $em->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Currency'
        );

        foreach ($repo->findAll() as $entity) {
            $default = 0;
            if ($entity->getId() == $persistedEntityId) {
                $default = 1;
            }
            $entity->setDefault($default);
            $em->persist($entity);
        }
    }

}