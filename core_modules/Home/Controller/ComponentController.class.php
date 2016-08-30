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
 * Main controller for Home
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_home
 */

namespace Cx\Core_Modules\Home\Controller;

/**
 * Main controller for Home
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_home
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

     /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $subMenuTitle, $objTemplate;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $cachedRoot = $this->cx->getTemplate()->getRoot();
                $this->cx->getTemplate()->setRoot($this->getDirectory() . '/View/Template/Backend');

                $objTemplate->setVariable('CONTAINER_DASHBOARD_CLASS', 'dashboard');
                $objFWUser = \FWUser::getFWUserObject();
                $subMenuTitle = $_CORELANG['TXT_WELCOME_MESSAGE'].", <a href='index.php?cmd=Access&amp;act=user&amp;tpl=modify&amp;id=".$objFWUser->objUser->getId()."' title='".$objFWUser->objUser->getId()."'>".($objFWUser->objUser->getProfileAttribute('firstname') || $objFWUser->objUser->getProfileAttribute('lastname') ? htmlentities($objFWUser->objUser->getProfileAttribute('firstname'), ENT_QUOTES, CONTREXX_CHARSET).' '.htmlentities($objFWUser->objUser->getProfileAttribute('lastname'), ENT_QUOTES, CONTREXX_CHARSET) : htmlentities($objFWUser->objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET))."</a>";
                $objAdminNav = new Home();
                $objAdminNav->getPage();

                $this->cx->getTemplate()->setRoot($cachedRoot);
                break;
        }
    }
}
