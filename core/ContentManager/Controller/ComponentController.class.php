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
        return array('EsiWidget');
    }

    /**
     * Get JsonAdapter classes
     *
     * @return array
     */
    public function getControllersAccessableByJson() {
        return array(
            'JsonNode',
            'JsonPage',
            'JsonContentManager',
            'EsiWidgetController'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $widgetController = $this->getComponent('Widget');
        foreach (
            array(
                'TITLE',
                'METATITLE',
                'NAVTITLE',
                'METAKEYS',
                'METADESC',
                'METAROBOTS',
                'METAIMAGE',
                'METAIMAGE_WIDTH',
                'METAIMAGE_HEIGHT',
                'CONTENT_TITLE',
                //'CONTENT_TEXT',
                'CSS_NAME',
                'TXT_CORE_LAST_MODIFIED_PAGE',
                'LAST_MODIFIED_PAGE',
                'CANONICAL_LINK',
            ) as $widgetName
        ) {
            $widgetController->registerWidget(
                new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                    $this,
                    $widgetName
                )
            );
        }
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
     * Registers event listeners
     */
    public function registerEventListeners() {
        $evm = $this->cx->getEvents();
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

        // Event register for search content
        $evm->addEventListener('SearchFindContent', $pageListener);
    }

    /**
     * Get the set canonical-link of this request
     *
     * @param   \Cx\Core\Routing\Model\Entity\Response  $response Response
     *                                          object of current request
     * @return  \Cx\Core\Html\Model\Entity\HtmlElement  Instance of type link
     * @throws  \Exeception In case no canonical-link has been set so far
     */
    public function fetchAlreadySetCanonicalLink($response) {
        $headers = $response->getHeaders();
        $linkHeader = '';
        if (isset($headers['Link'])) {
            $linkHeader = $headers['Link'];
        } else {
            // TODO: as the resolver does itself set his own headers
            // we have to check them as well as fallback.
            // This code code be removed once all headers are only
            // set on the instance of \Cx\Core\Routing\Model\Entity\Response
            $headers = \Env::get('Resolver')->getHeaders();
            if (isset($headers['Link'])) {
                $linkHeader = $headers['Link'];
            }
        }

        if (!preg_match('/^<([^>]+)>;\s+rel="canonical"/', $linkHeader, $matches)) {
            throw new \Exception('no canonical-link header set');
        }

        $canonicalLink = $matches[1];
        $link = new \Cx\Core\Html\Model\Entity\HtmlElement('link');
        $link->setAttribute('rel', 'canonical');
        $link->setAttribute('href', $canonicalLink);
        return $link;
    }
}
