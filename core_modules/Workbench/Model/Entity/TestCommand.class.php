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
     * Array of testing folders
     */
    protected $testingFolders = array();

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

        if ($componentType && !$componentName) {
            $this->getTestingFoldersByType($componentType, $useCustomizing);
        } elseif ($componentName) {
            $this->getTestingFoldersByName($componentName, $useCustomizing);
        }

        // get all testing folder when component type or name not specificed
        if (empty($this->testingFolders)) {
            foreach ($arrComponentTypes as $cType) {
                $this->getTestingFoldersByType($cType, $useCustomizing);
            }
        }

        $this->phpUnitPath = ASCMS_LIBRARY_PATH.'/PHPUnit';
        if(!file_exists($this->phpUnitPath)) {
            $this->interface->show("PhpUnit is not found in ". $this->phpUnitPath);
            return;
        }

        if (empty($this->testingFolders)) {
            $this->interface->show("Test cases not found!.\nPlease make sure the test cases are palced inside ([{component_name}|{component_type}])/Testing folder.");
            return;
        }

        // Sort the folders
        asort($this->testingFolders);

        // Needs to change the dir because files might be loaded by its relative path inside PHPUnit
        chdir($this->phpUnitPath);

        if (extension_loaded('xdebug')) {
            xdebug_disable();
        }

        if (strpos('@php_bin@', '@php_bin') === 0) {
            set_include_path($this->phpUnitPath . PATH_SEPARATOR . get_include_path());
        }

        spl_autoload_register(array($this, 'phpunitAutoload'));

        unset($arguments[0]);
        unset($arguments[1]); // unset the arguments
        $command = new \Cx\Core\Model\Controller\PHPUnitTextUICommand();
        $options = array(
            $this->phpUnitPath,
            '--testdox'
        );
        foreach ($arguments as $arg) {
            $options[] = $arg;
        }
        $options[] = $this->testingFolders;

        $_SERVER['argv'] = $argv = $options;
        $_SERVER['argc'] = count($argv);

        $command->run($_SERVER['argv'], false);

        $this->interface->show('Done');
    }

    /**
     * Get the testing folder by given component name
     *
     * @param string $componentName  Component name
     * @param string $useCustomizing use customizing
     *
     * @return null
     */
    private function getTestingFoldersByName($componentName, $useCustomizing)
    {
        $arrComponentTypes = array('core', 'core_module', 'module');

        foreach ($arrComponentTypes as $cType) {
            $componentFolder = $this->getModuleFolder($componentName, $cType, $useCustomizing);
            if ($this->addTestingFolderToArray($componentName, $componentFolder)) {
                break;
            }
        }
    }

    /**
     * Return the testing folders by component type
     *
     * @param  string $componentType Component type
     *
     * @return array Testing folders by given component type
     */
    private function getTestingFoldersByType($componentType, $useCustomizing) {

        $cx = \Env::get('cx');
        $em = $cx->getDb()->getEntityManager();

        // if component type is core then there are possible to have the test files under /core
        // so add that folder too
        if ($componentType == 'core') {
            $this->addTestingFolderToArray('core', $cx->getCodeBaseCorePath());
        }

        $systemComponentRepo = $em->getRepository('Cx\\Core\\Core\\Model\\Entity\\SystemComponent');
        $systemComponents = $systemComponentRepo->findBy(array('type'=>$componentType));

        if (!empty($systemComponents)) {
            foreach ($systemComponents as $systemComponent) {
                $this->addTestingFolderToArray($systemComponent->getName(), $systemComponent->getDirectory($useCustomizing));
            }
        }

        // load the old legacy components. assume core_module, module can only possible
        if (in_array($componentType, array('core_module', 'module'))) {
            static $objModuleChecker = NULL;

            if (!isset($objModuleChecker)) {
                $objModuleChecker = \Cx\Core\ModuleChecker::getInstance(\Env::get('em'), \Env::get('db'), \Env::get('ClassLoader'));
            }

            $arrModules = array();
            switch ($componentType) {
                case 'core_module':
                    $arrModules = $objModuleChecker->getCoreModules();
                    break;
                case 'module':
                    $arrModules = $objModuleChecker->getModules();
                    break;
                default:
                    break;
            }

            foreach ($arrModules as $component) {
                if (!array_key_exists($component, $this->testingFolders)) {
                    $componentFolder = $this->getModuleFolder($component, $componentType, $useCustomizing);
                    $this->addTestingFolderToArray($component, $componentFolder);
                }
            }
        }
    }

    /**
     * Returns module folder name
     *
     * @param string  $componentName     Component Name
     * @param string  $componentType     Component Type
     * @param boolean $allowCustomizing  Check for the customizing folder
     *
     * @return string module folder name
     */
    private function getModuleFolder($componentName, $componentType, $allowCustomizing = true)
    {
        $basepath      = ASCMS_DOCUMENT_ROOT . \Cx\Core\Core\Model\Entity\SystemComponent::getPathForType($componentType);
        $componentPath = $basepath . '/' . $componentName;

        if (!$allowCustomizing) {
            return $componentPath;
        }

        return \Env::get('cx')->getClassLoader()->getFilePath($componentPath);
    }

    /**
     * Added module testing folder to a array
     *
     * @param string $componentName Component name
     * @param string $componentFolder Module Fodler path
     *
     * @return boolean true if added successfully otherwise false
     */
    private function addTestingFolderToArray($componentName, $componentFolder)
    {
        $componentTestingFolder = $componentFolder . ASCMS_TESTING_FOLDER;

        if (!empty($componentFolder) && self::hasTestingFiles($componentTestingFolder) && file_exists($componentFolder) && file_exists($componentTestingFolder)) {
            $this->testingFolders[$componentName] = $componentTestingFolder;
            return true;
        }

        return false;
    }

    /**
     * Return true if the folder has php unit test cases false otherwise
     *
     * @param string $foldername absolute path of the folder to check the testing files
     *
     * @return boolean Return true if the folder has php unit test cases false otherwise
     */
    private static function hasTestingFiles($foldername)
    {
        if (glob($foldername.'/*Test.class.php')) {
            // phpunit test cases should end with Test.php
            return true;
        }
        if (glob($foldername.'/*Test.php')) {
            // phpunit test cases should end with Test.php
            return true;
        }

        foreach (glob($foldername.'/*', GLOB_ONLYDIR) as $folder) {
            return self::hasTestingFiles($folder);
        }

        return false;
    }

    /*
     * Autoload function to load the PHPUnit class files.
     */
    function phpunitAutoload($class)
    {
        require_once $this->phpUnitPath . '/PHPUnit/Util/Filesystem.php';

        if (
               strpos($class, 'PHPUnit_') === 0
            || strpos($class, 'PHP_') === 0
            || strpos($class, 'Text_') === 0
            || strpos($class, 'File_') === 0
            || strpos($class, 'Doctrine') === 0
            || strpos($class, 'SebastianBergmann') === 0
           ) {
           $file = \PHPUnit_Util_Filesystem::classNameToFilename($class);
           if (file_exists($this->phpUnitPath . '/'. $file)) {
               require_once $file;
           }
        }
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
