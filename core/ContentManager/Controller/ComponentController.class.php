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
 * Main controller for ContentManager
 *
 * At the moment, this is just an empty ComponentController in order to load
 * YAML files via component framework
 * @author Michael Ritter <michael.ritter@comvation.com>
 * @package cloudrexx
 * @subpackage core_contentmanager
 */

namespace Cx\Core\ContentManager\Controller;

/**
 * Main controller for ContentManager
 *
 * At the moment, this is ComponentController is just used to load
 * YAML files and JsonAdapters via component framework
 * @author Michael Ritter <michael.ritter@comvation.com>
 * @package cloudrexx
 * @subpackage core_contentmanager
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponent $systemComponent, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponent, $cx);
        $evm = $cx->getEvents();
        $pageListener = new \Cx\Core\ContentManager\Model\Event\PageEventListener();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Core\\ContentManager\\Model\\Entity\\Page', $pageListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'Cx\\Core\\ContentManager\\Model\\Entity\\Page', $pageListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Core\\ContentManager\\Model\\Entity\\Page', $pageListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postUpdate, 'Cx\\Core\\ContentManager\\Model\\Entity\\Page', $pageListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preRemove, 'Cx\\Core\\ContentManager\\Model\\Entity\\Page', $pageListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postRemove, 'Cx\\Core\\ContentManager\\Model\\Entity\\Page', $pageListener);
        $evm->addModelListener(\Doctrine\ORM\Events::onFlush, 'Cx\\Core\\ContentManager\\Model\\Entity\\Page', $pageListener);

        $nodeListener = new \Cx\Core\ContentManager\Model\Event\NodeEventListener();
        $evm->addModelListener(\Doctrine\ORM\Events::preRemove, 'Cx\\Core\\ContentManager\\Model\\Entity\\Node', $nodeListener);
        $evm->addModelListener(\Doctrine\ORM\Events::onFlush, 'Cx\\Core\\ContentManager\\Model\\Entity\\Node', $nodeListener);

        $evm->addModelListener(\Doctrine\ORM\Events::onFlush, 'Cx\\Core\\ContentManager\\Model\\Entity\\LogEntry', new \Cx\Core\ContentManager\Model\Event\LogEntryEventListener());
    }

    public function preResolve(\Cx\Core\Routing\Url $request) {
        $evm = \Cx\Core\Core\Controller\Cx::instanciate()->getEvents();
        $evm->addEvent('wysiwygCssReload');
    }

    /**
     * get controller classes
     *
     * @return array
     */
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }
    public function getControllersAccessableByJson() {
        return array(
            'JsonNode', 'JsonPage', 'JsonContentManager',
        );
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objTemplate, $objDatabase, $objInit, $act, $subMenuTitle, $_ARRAYLANG;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:

                // @todo: This should be set by SystemComponentBackendController
                $subMenuTitle = $_ARRAYLANG['TXT_CONTENT_MANAGER'];

                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $cachedRoot = $this->cx->getTemplate()->getRoot();
                $this->cx->getTemplate()->setRoot($this->getDirectory() . '/View/Template/Backend');

                \Permission::checkAccess(6, 'static');
                $cm = new ContentManager($act, $objTemplate, $objDatabase, $objInit);
                $cm->getPage();

                $this->cx->getTemplate()->setRoot($cachedRoot);
                break;
        }
    }

    /**
     * Do something for search the content
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentParse(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        $this->cx->getEvents()->addEventListener('SearchFindContent', new \Cx\Core\ContentManager\Model\Event\PageEventListener());
   }
}
