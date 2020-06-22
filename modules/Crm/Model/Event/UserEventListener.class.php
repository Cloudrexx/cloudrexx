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
 * UserEventListener

 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Modules\Crm\Model\Event;

/**
 * UserEventListenerException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_crm
 */
class UserEventListenerException extends \Exception {}

/**
 * UserEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_crm
 */
class UserEventListener implements \Cx\Core\Event\Model\Entity\EventListener {

    public function postUpdate($eventArgs) {
        $objUser = $eventArgs->getEntity();
        $crmId = $objUser->getCrmUserId();
        if (empty($crmId)) {
            return;
        }

        $objUser->objAttribute->first();
        $arrProfile = array();
        while (!$objUser->objAttribute->EOF) {
            $arrProfile[$objUser->objAttribute->getId()] = array();
            $arrProfile[$objUser->objAttribute->getId()][] = $objUser->getProfileAttribute($objUser->objAttribute->getId());
            $objUser->objAttribute->next();
        }

        $objCrmLib = new \Cx\Modules\Crm\Controller\CrmLibrary('Crm');
        $objCrmLib->setContactPersonProfile($arrProfile, $objUser->getId(), $objUser->getFrontendLanguage());
    }

    public function onEvent($eventName, array $eventArgs) {
        $this->$eventName(current($eventArgs));
    }
}
