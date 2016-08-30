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
 * CrmCrmContactEventListener

 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Event;

/**
 * CrmCrmContactEventListenerException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class CrmCrmContactEventListenerException extends \Exception {}

/**
 * CrmCrmContactEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class CrmCrmContactEventListener implements \Cx\Core\Event\Model\Entity\EventListener {

    /**
     * preRemove event
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
     */
    public function preRemove($eventArgs) {
        global $objInit, $_ARRAYLANG;

        $langData = $objInit->loadLanguageData('Order');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        $em = $eventArgs->getEntityManager();
        $crmEntity = $eventArgs->getEntity();
        if ($crmEntity->contactType == 2) {
            $orderRepo = $em->getRepository('\Cx\Modules\Order\Model\Entity\Order');
            if ($orderRepo->hasOrderByCrmId($crmEntity->id)) {
                throw new \Cx\Core\Error\Model\Entity\ShinyException($_ARRAYLANG['TXT_MODULE_ORDER_DELETE_USER_ERROR_MSG']);
            }
        }

    }

    public function onEvent($eventName, array $eventArgs) {
        $this->$eventName(current($eventArgs));
    }
}
