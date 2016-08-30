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
 * Main controller for Search
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_search
 */

namespace Cx\Core_Modules\Search\Controller;

/**
 * Main controller for Search
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_search
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Register your events here
     *
     * Do not do anything else here than list statements like
     * $this->cx->getEvents()->addEvent($eventName);
     */
    public function registerEvents()
    {
        $eventHandlerInstance = $this->cx->getEvents();
        $eventHandlerInstance->addEvent('SearchFindContent');
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $objTemplate, $_CORELANG, $act;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $pos = (isset($_GET['pos'])) ? intval($_GET['pos']) : '';
                $objSearch = new \Cx\Core_Modules\Search\Controller\Search($page);
                \Env::get('cx')->getPage()->setContent($objSearch->getPage($pos, \Env::get('cx')->getPage()->getContent()));
                break;
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $subMenuTitle = $_CORELANG['TXT_SEARCH'];
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $cachedRoot = $this->cx->getTemplate()->getRoot();
                $this->cx->getTemplate()->setRoot($this->getDirectory() . '/View/Template/Backend');

                $objSearchManager = new \Cx\Core_Modules\Search\Controller\SearchManager($act, $objTemplate, $this->cx->getLicense());
                $objSearchManager->getPage();

                $this->cx->getTemplate()->setRoot($cachedRoot);
                break;
            default:
                break;
        }
    }
}
