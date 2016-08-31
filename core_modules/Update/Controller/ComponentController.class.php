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
 * Class ComponentController
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_update
 */

namespace Cx\Core_Modules\Update\Controller;

/**
 * Class ComponentController
 *
 * The main Update component
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_update
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Get the controller classes
     *
     * @return array array of the controller classes.
     */
    public function getControllerClasses() {
        return array('Update');
    }

    /**
     * postInit
     *
     * @param \Cx\Core\Core\Controller\Cx $cx
     *
     * @return null
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $componentController = $this->getComponent('MultiSite');
        if (!$componentController) {
            return;
        }

        \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'config', 'FileSystem');
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite') != \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE) {
            return;
        }

        $updateFile = $cx->getWebsiteTempPath() . '/Update/' . \Cx\Core_Modules\Update\Model\Repository\DeltaRepository::PENDING_DB_UPDATES_YML;
        if (!file_exists($updateFile)) {
            return;
        }

        $componentController->setCustomerPanelDomainAsMainDomain();

        $updateController = $this->getController('Update');
        $updateController->applyDelta();
    }

}
