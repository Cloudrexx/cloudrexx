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
 * This is the superclass for all main Controllers for a Component
 *
 * Decorator for SystemComponent
 * Every component needs a SystemComponentController for initialization
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */

namespace Cx\Core\Core\Model\Entity;

/**
 * This is the superclass for all main Controllers for a Component
 *
 * Decorator for SystemComponent
 * Every component needs a SystemComponentController for initialization
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class SystemComponentController extends Controller {
    /**
     * Available controllers
     * @var array List of Controller objects
     */
    private $controllers = array();

    /**
     * Decorated SystemComponent
     * @var \Cx\Core\Core\Model\Entity\SystemComponent
     */
    protected $systemComponent;

    /**
     * Initializes a controller
     * @param \Cx\Core\Core\Model\Entity\SystemComponent $systemComponent SystemComponent to decorate
     * @param \Cx\Core\Core\Controller\Cx                               $cx         The Cloudrexx main class
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponent $systemComponent, \Cx\Core\Core\Controller\Cx $cx) {
        $this->systemComponent = $systemComponent;
        $this->cx = $cx;
    }

    /**
     * Returns the main controller
     * @return SystemComponentController Main controller for this system component
     */
    public function getSystemComponentController() {
        return $this;
    }

    /**
     * Returns the SystemComponent this Controller decorates
     * @return \Cx\Core\Core\Model\Entity\SystemComponent
     */
    public function getSystemComponent() {
        return $this->systemComponent;
    }

    /**
     * Sets the SystemComponent this Controller decorates
     * @return \Cx\Core\Core\Model\Entity\SystemComponent
     */
    public function setSystemComponent($systemComponent) {
        $this->systemComponent = $systemComponent;
    }

    /**
     * Registers a controller instance
     * @param Controller $controller Controller to register
     * @return null
     */
    public function registerController(Controller $controller) {
        if (isset($this->controllers[get_class($controller)])) {
            return;
        }
        $this->controllers[get_class($controller)] = $controller;
    }

    /**
     * Returns a list of controllers
     * @param boolean $loadedOnly (optional) If false, controller that did not register are instanciated, default true
     * @return array List of Controller instances
     */
    public function getControllers($loadedOnly = true) {
        if ($loadedOnly) {
            return $this->controllers;
        }
        foreach ($this->getControllerClasses() as $class) {
            if (isset($this->controllers[$class])) {
                continue;
            }
            // if this is a partial relative class name
            $class = '\\'.$this->getControllerClassName($class);
            new $class($this, $this->cx);
        }
        return $this->getControllers();
    }

    /**
     * This finds the correct FQCN for a controller name
     * @param string $controllerClassShort Short name for controller
     * @return string Fully qualified controller class name
     */
    protected function getControllerClassName($controllerClassShort) {
        $class = $controllerClassShort;
        if (strpos('\\', $class) != 1) {
            if (!$this->cx->getClassLoader()->getFilePath($this->getDirectory(false).'/Controller/'.$class.'Controller.class.php')) {
                $class = '\\Cx\\Core\\Core\\Model\\Entity\\SystemComponent' . $class . 'Controller';
            } else {
                $class = $this->getNamespace() . '\\Controller\\' . $class . 'Controller';
            }
        }
        return $this->adjustFullyQualifiedClassName($class);
    }

    /**
     * Returns a controller instance if one already exists
     * @param $controllerClass Short or FQCN controller name
     * @return \Cx\Core\Core\Model\Entity\Controller Controller instance
     * @throws \Exception if controller exists but cannot be loaded
     */
    public function getController($controllerClass) {
        if (isset($this->controllers[$controllerClass])) {
            return $this->controllers[$controllerClass];
        }

        $classes = $this->getControllerClasses();
        if (!in_array($controllerClass, $classes)) {
            return null;
        }
        $class = '\\' . $this->getControllerClassName($controllerClass);
        new $class($this, $this->cx);

        if (!isset($this->controllers[preg_replace('/^\\\\/', '', $class)])) {
            throw new \Exception('Controller "' . $controllerClass . '" could not be loaded(' . preg_replace('/^\\\\/', '', $class) . ')');
        }

        return $this->controllers[preg_replace('/^\\\\/', '', $class)];
    }

    /**
     * Get component controller object
     *
     * @param string $name  component name
     *
     * @return \Cx\Core\Core\Model\Entity\SystemComponentController
     * The requested component controller or null if no such component exists
     *
     */
    public function getComponent($name)
    {
        if (empty($name)) {
            return null;
        }
        $componentRepo = $this->cx->getDb()->getEntityManager()->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $component     = $componentRepo->findOneBy(array('name' => $name));
        if (!$component) {
            return null;
        }
        return $component->getSystemComponentController();
    }

    /**
     * This makes sure a FQCN does not contain double backslashes
     * @param string $className FQCN of a controller
     * @return string Clean FQCN of a controller
     */
    protected function adjustFullyQualifiedClassName($className) {
        return preg_replace('/^\\\\/', '', $className);
    }

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('Frontend', 'Backend');
    }

    /**
     * Decoration: all methods that are not specified in this or child classes
     * call the corresponding method of the decorated SystemComponent
     * @param string $methodName Name of method to call
     * @param array $arguments List of arguments for the method to call
     * @return mixed Return value of the method to call
     */
    public function __call($methodName, $arguments) {
        return call_user_func_array(array($this->systemComponent, $methodName), $arguments);
    }

    /**
     * Returns a list of JsonAdapter class names
     *
     * The array values might be a class name without namespace. In that case
     * the namespace \Cx\{component_type}\{component_name}\Controller is used.
     * If the array value starts with a backslash, no namespace is added.
     *
     * Avoid calculation of anything, just return an array!
     * @return array List of ComponentController classes
     */
    public function getControllersAccessableByJson() {
        return array();
    }

    /**
     * Returns a list of command mode commands provided by this component
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array();
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        return '';
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     * @see getCommandsForCommandMode()
     * @param string $command Name of command to execute
     * @param array $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {}

    /**
     * Check whether the command has access to execute or not.
     *
     * @param string $command   name of the command to execute
     * @param array  $arguments list of arguments for the command
     *
     * @return boolean
     */
    public function hasAccessToExecuteCommand($command, $arguments) {
        $commands = $this->getCommandsForCommandMode();
        $method = (php_sapi_name() === 'cli') ? array('cli') : null;

        $objPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array(),
            $method,
            false,
            array(),
            array(),
            array()
        );
        if (
            isset($commands[$command]) &&
            $commands[$command] instanceof \Cx\Core_Modules\Access\Model\Entity\Permission
        ) {
            $objPermission = $commands[$command];
        }

        if ($objPermission->hasAccess($arguments)) {
            return true;
        }

        return false;
    }

    /**
     * Do something before system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the preInit-Hook definition
     * file config/preInitHooks.yml.
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function preInit(\Cx\Core\Core\Controller\Cx $cx) {}

    /**
     * Do something after system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx) {}

    /**
     * Do something before component load
     * * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the preComponentLoad-Hook definition
     * file config/preComponentLoadHooks.yml.
     */
    public function preComponentLoad() {}

    /**
     * Do something after all active components are loaded
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     */
    public function postComponentLoad() {}

    /**
     * Register your events here
     *
     * Do not do anything else here than list statements like
     * $this->cx->getEvents()->addEvent($eventName);
     */
    public function registerEvents() {}

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners() {}

    /**
     * Do something before resolving is done
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\Routing\Url                      $request    The URL object for this request
     */
    public function preResolve(\Cx\Core\Routing\Url $request) {}

    /**
     * Called for additional, component specific resolving
     *
     * If /en/Path/to/Page is the path to a page for this component
     * a request like /en/Path/to/Page/with/some/parameters will
     * give an array like array('with', 'some', 'parameters') for $parts
     * PLEASE MAKE SURE THIS METHOD IS MOCKABLE. IT MAY ONLY INTERACT WITH
     * adjustResponse() HOOK.
     *
     * This may be used to redirect to another page
     * @param array $parts List of additional path parts
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved virtual page
     */
    public function resolve($parts, $page) {}

    /**
     * Do something after resolving is done
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {}

    /**
     * Do something before content is loaded from DB
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {}

    /**
     * Do something before a module is loaded
     *
     * This method is called only if any module
     * gets loaded for content parsing
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentParse(\Cx\Core\ContentManager\Model\Entity\Page $page){}

    /**
     * Do something with a Response object
     * You may do page alterations here (like changing the metatitle)
     * You may do response alterations here (like set headers)
     * PLEASE MAKE SURE THIS METHOD IS MOCKABLE. IT MAY ONLY INTERACT WITH
     * resolve() HOOK.
     *
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object to adjust
     */
    public function adjustResponse(\Cx\Core\Routing\Model\Entity\Response $response) {}

    /**
     * Load your component. It is needed for this request.
     *
     * This loads your FrontendController or BackendController depending on the
     * mode Cx runs in. For modes other than frontend and backend, nothing is done.
     * If you you'd like to name your Controllers differently, or have another
     * use case, overwrite this.
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        // These are the modes I know that components can use for content
        $knownModes = array(
            \Cx\Core\Core\Controller\Cx::MODE_FRONTEND => 'Frontend',
            \Cx\Core\Core\Controller\Cx::MODE_BACKEND => 'Backend',
            \Cx\Core\Core\Controller\Cx::MODE_COMMAND => 'Command',
        );

        // Find controller short name for Cx mode
        if (!isset($knownModes[$this->cx->getMode()])) {
            // Unknown mode, something weird just happened:
            // - Is there a new mode defined in Cx-Class?
            // - Did you try to load a component in minimal mode?
            return;
        }

        // Find long controller name for short controller name
        $controllerShort = $knownModes[$this->cx->getMode()];
        if (!in_array($controllerShort, $this->getControllerClasses())) {
            // No such controller for this component
            return;
        }

        // Find controller instance
        $controller = $this->getController($controllerShort);
        if (!$controller) {
            // Controller is listed in controller classes but could not be
            // instanciated. There's something wrong there...
            return;
        }

        // Get content
        $controller->getPage($page);
    }

    /**
     * Do something after a module is loaded
     *
     * This method is called only if any module
     * gets loaded for content parsing
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postContentParse(\Cx\Core\ContentManager\Model\Entity\Page $page) {}

    /**
     * Do something after content is loaded from DB
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {}

    /**
     * Do something before main template gets parsed
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\Html\Sigma                       $template   The main template
     */
    public function preFinalize(\Cx\Core\Html\Sigma $template) {}

    /**
     * Do something after main template got parsed
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * @param string                                    $endcode The processed data to be sent to the client as response
     */
    public function postFinalize(&$endcode) {}
}
