<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * Event listener for user attributes
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Core\User\Model\Event;

/**
 * Event listener for user attributes
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class UserAttributeEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{
    /**
     * Add userAttributeValue for each user
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postPersist(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $attr = $eventArgs->getEntity();

        $users = $em->getRepository(
            'Cx\Core\User\Model\Entity\User'
        )->findAll();

        foreach ($users as $user) {
            $value = new \Cx\Core\User\Model\Entity\UserAttributeValue();
            $value->setValue('');
            $value->setUserAttribute($attr);
            $value->setUser($user);
            $em->persist($value);
        }
        $em->flush();
    }
}
