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
 * Main controller for DocSys
 *
 * @copyright  cloudrexx
 * @author     Project Team SS4U <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_docsys
 */

namespace Cx\Modules\DocSys\Controller;

/**
 * Main controller for DocSys
 *
 * @copyright  cloudrexx
 * @author     Project Team SS4U <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_docsys
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
     * Load the component DocSys.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objTemplate, $_CORELANG, $subMenuTitle;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $docSysObj= new DocSys(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($docSysObj->getDocSysPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(11, 'static');
                $subMenuTitle = $_CORELANG['TXT_DOC_SYS_MANAGER'];
                $objDocSys = new DocSysManager();
                $objDocSys->getDocSysPage();
                break;

            default:
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function registerEventListeners()
    {
        $evm            = $this->cx->getEvents();
        $docSysListener = new \Cx\Modules\DocSys\Model\Event\DocSysEventListener();
        $evm->addEventListener('SearchFindContent', $docSysListener);
    }

    /**
     * {@inheritdoc}
     */
    public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        $page = $response->getPage();
        if (
            !$page ||
            $page->getModule() !== $this->getName() ||
            $page->getCmd() !== 'details'
        ) {
            return;
        }

        $docSysObj = new DocSys('');
        $docSysObj->getDetails();
        //Set the Page Title
        $page->setTitle($docSysObj->docSysTitle);
        $page->setContentTitle($docSysObj->docSysTitle);
        $page->setMetaTitle($docSysObj->docSysTitle);
    }
}
