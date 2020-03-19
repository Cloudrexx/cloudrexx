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
 * Event listener for users
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Core\User\Model\Event;

/**
 * Event listener for users
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class UserEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{
    public function preUpdate(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs)
    {
        global $objInit, $_ARRAYLANG;

        //get the language interface text
        $langData   = $objInit->loadLanguageData('User');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        $entity = $eventArgs->getEntity();

        // Prevent the user from deactivating himself
        $user = \FWUser::getFWUserObject()->objUser;
        if (!$entity->getActive() && $user->getId() == $entity->getId()) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_CORE_USER_NO_USER_WITH_SAME_ID']
            );
        }


    }
}
