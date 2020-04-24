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
 * Command to access behat command line tools
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Model\Entity;

/**
 * Command to access behat command line tools
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class TestCommand extends Command {

    /**
     * Command name
     * @var string
     */
    protected $name = 'test';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Wrapper for behat command line tools';

    /**
     * Command synopsis
     * @var string
     */
    protected $synopsis = 'workbench(.bat) test ([{component_name}|{component_type}]) ({some_crazy_arguments_for_phpunit})';

    /**
     * Command help text
     * @var string
     */
    protected $help = 'To be defined';

    /**
     * @var string Path to PHPUnit library
     */
    protected $phpUnitPath;

    /**
     * Execute this command
     * @param array $arguments Array of commandline arguments
     */
    public function execute(array $arguments) {
        /*
         * When creating a new component
         *  - cd to component's testing folder
         *  - behat --init
         *  - sample feature file (behat story-syntax)
         *  - behat --snippets
         *
         * To execute tests
         *  - cd to component's testing folder
         *  - behat
         *
         * Create test code
         *  - behat --snippets
         */
        global $argv;

        $systemConfig      = \Env::get('config');
        $useCustomizing    = isset($systemConfig['useCustomizings']) && $systemConfig['useCustomizings'] == 'on';

        $arrComponentTypes = array('core', 'core_module', 'module');

        // check for the component type
        $componentType = (isset($arguments[2]) && in_array($arguments[2], $arrComponentTypes)) ? $arguments[2] : null;
        $componentName = null;
        if (!$componentType) {
            $componentName = !empty($arguments[2]) && $this->isComponent($arguments[2]) ? $arguments[2] : null;
        }

        if ($componentType || $componentName) {
            unset($arguments[2]);
        }

        // check the third parameter it might be component name
        if (!empty($arguments[3]) && $this->isComponent($arguments[3])) {
            $componentName = $arguments[3];
            unset($arguments[3]);
        }

        $path = $this->cx->getCodeBaseDocumentRootPath();
        if ($componentType && !$componentName) {
            // only component type
            try {
                $path .= \Cx\Core\Core\Model\Entity\SystemComponent::getPathForType(
                    $componentType
                );
            } catch (\Cx\Core\Core\Model\Entity\SystemComponentException $e) {
                echo 'Component type "' . $componentType . '" not found' . PHP_EOL;
                return;
            }
        } elseif ($componentName) {
            // component name
            $component = $this->cx->getComponent($componentName);
            if (!$component) {
                echo 'Component "' . $componentName . '" not found' . PHP_EOL;
                return;
            }
            $path = $component->getDirectory($useCustomizing);
        }

        $this->phpUnitPath = ASCMS_LIBRARY_PATH.'/PHPUnit/phpunit/phpunit/src';
        if(!file_exists($this->phpUnitPath)) {
            $this->interface->show("PhpUnit is not found in ". $this->phpUnitPath);
            return;
        }

        // Sort the folders
        asort($this->testingFolders);

        if (extension_loaded('xdebug')) {
            xdebug_disable();
        }

        require_once(ASCMS_LIBRARY_PATH . '/PHPUnit/autoload.php');

        // Need to load session before PHPUnit is loaded. Otherwise session
        // init in a test case will fail.
        $session = $this->cx->getComponent('Session')->getSession();

        unset($arguments[0]);
        unset($arguments[1]); // unset the arguments
        $command = new \PHPUnit\TextUI\Command();
        $options = array(
            $this->phpUnitPath,
            '--testdox',
            '--test-suffix',
            'Test.class.php',
        );
        foreach ($arguments as $arg) {
            $options[] = $arg;
        }
        $options[] = $path;

        $_SERVER['argv'] = $argv = $options;
        $_SERVER['argc'] = count($argv);

        $command->run($_SERVER['argv'], false);

        $this->interface->show('Done');
    }

    /**
     * Check whether component exists or not
     *
     * @param type $componentName
     *
     * @return mixed component object or null
     */
    function isComponent($componentName)
    {
        $cx = \Env::get('cx');
        $em = $cx->getDb()->getEntityManager();

        $componentRepo = $em->getRepository('Cx\\Core\\Core\\Model\\Entity\\SystemComponent');
        $component     = $componentRepo->findOneBy(array('name' => $componentName));

        return $component;
    }
}
