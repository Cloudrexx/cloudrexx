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
 * Main controller for Egov
 *
 * @copyright  cloudrexx
 * @author     Project Team SS4U <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_egov
 */

namespace Cx\Modules\Egov\Controller;

/**
 * Main controller for Egov
 *
 * @copyright  cloudrexx
 * @author     Project Team SS4U <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_egov
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
     * Load the component Egov.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objTemplate, $_CORELANG, $subMenuTitle;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objEgov = new Egov(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objEgov->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(109, 'static');
                $subMenuTitle = $_CORELANG['TXT_EGOVERNMENT'];
                $objEgov = new EgovManager();
                $objEgov->getPage();
                break;

            default:
                break;
        }
    }

}
