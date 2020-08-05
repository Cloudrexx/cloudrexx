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
 * Event listener for all order events
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Event;

/**
 * Event listener for all order events
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class OrderEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{
    /**
     * Send mail if param sendMail is true
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args arguments for
     *                                                     postUpdate
     *
     * @throws \Exception
     */
    public function postUpdate(\Doctrine\ORM\Event\LifecycleEventArgs $args)
    {
        if (
            $this->cx->getRequest()->hasParam('sendMail', false) &&
            filter_var(
                $this->cx->getRequest()->getParam('sendMail', false),
                FILTER_VALIDATE_BOOLEAN
            )
        ) {
            \Cx\Modules\Shop\Controller\ShopManager::sendProcessedMail(
                $args->getEntity()->getId()
            );
        }
    }
}