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
 * Main controller for Component Manager
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_componentmanager
 */

namespace Cx\Core\ComponentManager\Controller;

/**
 * Main controller for Component Manager
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_componentmanager
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * getControllerClasses
     *
     * @return type
     */
    public function getControllerClasses() {
        return array();
    }

     /**
     * Load the component Component Manager.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_ARRAYLANG, $subMenuTitle;
        $subMenuTitle = $_ARRAYLANG['TXT_MODULE_MANAGER'];

        \Permission::checkAccess(23, 'static');
        $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
        $cachedRoot = $this->cx->getTemplate()->getRoot();
        $this->cx->getTemplate()->setRoot($this->getDirectory() . '/View/Template/Backend');

        $objComponentManager = new \Cx\Core\ComponentManager\Controller\ComponentManager();
        $objComponentManager->getModulesPage();

        $this->cx->getTemplate()->setRoot($cachedRoot);
    }

}
