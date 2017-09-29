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
 * Handles all components, including legacy ones.
 *
 * This is a wrapper class for SystemComponentRepository and LegacyComponentHandler
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v3.1.0
 */

namespace Cx\Core\Core\Controller;

/**
 * ComponentException is thrown for legacy components without an exception in LegacyComponentHandler
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v3.1.0
 */
class ComponentException extends \Exception {}

/**
 * Handles all components, including legacy ones.
 *
 * This is a wrapper class for SystemComponentRepository and LegacyComponentHandler
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v3.1.0
 */
class ComponentHandler {

    /**
     * Legacy code for old components
     * @var LegacyContentHandler
     */
    private $legacyComponentHandler;

    /**
     * Are we in frontend or backend mode?
     * @var boolean
     */
    private $frontend;

    /**
     * Repository of SystemComponents
     * @var \Cx\Core\Core\Model\Repository\SystemComponentRepository
     */
    protected $systemComponentRepo;

    /**
     * Available (and legal by license) components
     * This list should be written in constructor (read from license). This
     * does not work by now since license has different component names.
     * @var array
     */
    private $components = array(
        'ComponentHandler',
    );

    /**
     * Instanciates a new ComponentHandler
     * @todo Read component list from license (see $this->components for why we didn't do that yet)
     * @param \Cx\Core_Modules\License\License $license Current license
     * @param boolean $frontend Wheter we are in frontend mode or not
     * @param \Doctrine\ORM\EntityManager $em Doctrine entity manager
     * @param array $preLoadedComponents An array containing the preloaded components
     */
    public function __construct(\Cx\Core_Modules\License\License $license, $frontend, \Doctrine\ORM\EntityManager $em, $preLoadedComponents) {
        $this->legacyComponentHandler = new LegacyComponentHandler();
        $this->frontend = $frontend;
        //$this->components = $license->getLegalComponentsList();
        $this->systemComponentRepo = $em->getRepository('Cx\\Core\\Core\\Model\\Entity\\SystemComponent');
        $this->systemComponentRepo->setPreLoadedComponents($preLoadedComponents);
        $this->systemComponentRepo->findAll();

        $this->callRegisterEventsHooks();
        $this->callRegisterEventListenersHooks();
    }

    /**
     * Wheter the component with the supplied name is a legacy one or not
     * @param string $componentName Name of the component to check
     * @return boolean True if it's a legacy component, false otherwise
     */
    public function isLegacyComponent($componentName) {
        return !$this->systemComponentRepo->findOneBy(array('name'=>$componentName));
    }

    /**
     * Checks for existance of legacy exception and executes it if available
     * @param String $action The action to be executed
     * @param String $componentName Name of the component to execute the action
     * @return boolean True if legacy has an exception for this action and component
     */
    private function checkLegacy($action, $componentName) {
        if ($this->legacyComponentHandler->executeException($this->frontend, $action, $componentName) === false) {
            return false;
        }
        return true;
    }

    /**
     * Calls hook scripts on legacy and non-legacy components after they are loaded
     */
    public function callPostComponentLoadHooks() {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('postComponentLoad', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPostComponentLoadHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legay components after initialization
     */
    public function callPostInitHooks() {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('postInit', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPostInitHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components to register events
     * @param string $mode (optional) One of 'all', 'proper' and 'legacy', default is 'all'
     */
    public function callRegisterEventsHooks($mode = 'all') {
        if ($mode != 'all' && $mode != 'proper') {
            return;
        }
        $this->systemComponentRepo->callRegisterEventsHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components to register event listeners
     * @param string $mode (optional) One of 'all', 'proper' and 'legacy', default is 'all'
     */
    public function callRegisterEventListenersHooks($mode = 'all') {
        if ($mode != 'all' && $mode != 'proper') {
            return;
        }
        $this->systemComponentRepo->callRegisterEventListenersHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components before resolving
     * @param string $mode (optional) One of 'all', 'proper' and 'legacy', default is 'all'
     */
    public function callPreResolveHooks($mode = 'all') {
        if ($mode == 'all' || $mode == 'legacy') {
            foreach ($this->components as $componentName) {
                if ($this->checkLegacy('preResolve', $componentName)) {
                    continue;
                }
            }
        }
        if ($mode == 'all' || $mode == 'proper') {
            $this->systemComponentRepo->callPreResolveHooks();
        }
    }

    /**
     * Calls hook scripts on legacy and non-legacy components after resolving
     * @param string $mode (optional) One of 'all', 'proper' and 'legacy', default is 'all'
     */
    public function callPostResolveHooks($mode = 'all') {
        if ($mode == 'all' || $mode == 'legacy') {
            foreach ($this->components as $componentName) {
                if ($this->checkLegacy('postResolve', $componentName)) {
                    continue;
                }
            }
        }
        if ($mode == 'all' || $mode == 'proper') {
            $this->systemComponentRepo->callPostResolveHooks();
        }
    }

    /**
     * Calls hook scripts to adjust response object (this is newer than legacy)
     * @param \Cx\Core\Routing\Model\Entity\Response $response Current response
     */
    public function callAdjustResponseHooks($response) {
        $this->systemComponentRepo->callAdjustResponseHooks($response);
    }

    /**
     * Calls hook scripts on legacy and non-legacy components before loading content
     */
    public function callPreContentLoadHooks() {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('preContentLoad', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPreContentLoadHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components before loading module content
     */
    public function callPreContentParseHooks() {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('preContentParse', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPreContentParseHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components after loading module content
     */
    public function callPostContentParseHooks() {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('postContentParse', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPostContentParseHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components after loading content
     */
    public function callPostContentLoadHooks() {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('postContentLoad', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPostContentLoadHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components before finalizing
     */
    public function callPreFinalizeHooks() {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('preFinalize', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPreFinalizeHooks();
    }

    /**
     * Calls hook scripts on legacy and non-legacy components after finalizing
     * @param string $endcode The cx endcode passed by reference
     */
    public function callPostFinalizeHooks(&$endcode) {
        foreach ($this->components as $componentName) {
            if ($this->checkLegacy('postFinalize', $componentName)) {
                continue;
            }
        }
        $this->systemComponentRepo->callPostFinalizeHooks($endcode);
    }

    /**
     * Load the component with the name specified (legacy or not)
     * @param \Cx\Core\Core\Controller\Cx $cx Main class instance
     * @param string $componentName Name of component to load
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     * @return null
     * @throws ComponentException For legacy components without a load entry in LegacyClassLoader
     */
    public function loadComponent(\Cx\Core\Core\Controller\Cx $cx, $componentName, \Cx\Core\ContentManager\Model\Entity\Page $page = null) {
        if ($this->checkLegacy('load', $componentName)) {
            \DBG::msg('This is a legacy component (' . $componentName . '), load via LegacyComponentHandler');
            return;
        }
        $component = $this->systemComponentRepo->findOneBy(array('name'=>$componentName));
        if (!$component) {
            \DBG::msg('This is an ugly legacy component (' . $componentName . '), load via LegacyComponentHandler');
            \DBG::msg('Add an exception for this component in LegacyComponentHandler!');
            throw new ComponentException('This is an ugly legacy component(' . $componentName . '), load via LegacyComponentHandler!');
        }
        $component->load($page);
        //\DBG::msg('<b>WELL, THIS IS ONE NICE COMPONENT!</b>');
    }
}
