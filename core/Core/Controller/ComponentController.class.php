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


namespace Cx\Core\Core\Controller;

class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    const CLI_SCRIPT_NAME = './cx ';

    /**
     * {@inheritdoc}
     */
    public function getControllerClasses()
    {
        return array('EsiWidget');
    }

    /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController');
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $widgetController = $this->getComponent('Widget');
        $widgetController->registerWidget(
            new \Cx\Core_Modules\Widget\Model\Entity\FinalStringWidget(
                $this,
                'PATH_OFFSET',
                $this->cx->getCodeBaseOffsetPath()
            )
        );

        foreach (array('BASE_URL', 'VERSION') as $widgetName) {
            $widgetController->registerWidget(
                new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                    $this,
                    $widgetName
                )
            );
        }
    }

    public function getCommandsForCommandMode() {
        $cliOnlyPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array(),
            array('cli'),
            false
        );
        return array(
            'help' => new \Cx\Core_Modules\Access\Model\Entity\Permission(
                array(),
                array('get', 'post', 'cli', 'head', 'put', 'delete', 'patch', 'options'),
                false
            ),
            'status' => $cliOnlyPermission,
            'diff' => $cliOnlyPermission,
            'version',
            'install' => $cliOnlyPermission,
            'activate' => $cliOnlyPermission,
            'deactivate' => $cliOnlyPermission,
            'cleanTempFiles' => $cliOnlyPermission,
        );
    }

    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'help':
                if ($short) {
                    return 'Shows help for commands';
                }
                return '(todo)';
                break;
            case 'status':
                if ($short) {
                    return 'Shows customized files';
                }
                return '(todo)';
                break;
            case 'diff':
                if ($short) {
                    return 'Diffs customized files';
                }
                return '(todo)';
                break;
            case 'version':
                if ($short) {
                    return 'Displays info about the version of Cloudrexx';
                }
                return '(todo)';
                break;
            case 'install':
                if ($short) {
                    return 'Installs a component from zip';
                }
                return 'Installs a component from a zip file. Usage:

./cx install <path_to_zip_package>';
                break;
            case 'activate':
                if ($short) {
                    return 'Activates a component';
                }
                return 'Activates a component which is present in file system. Usage:

./cx activate <component_type> <component_name>';
                break;
            case 'deactivate':
                if ($short) {
                    return 'Deactivates a component';
                }
                return 'Deactivates a component. Usage:

./cx deactivate <component_type> <component_name>';
                break;
            case 'cleanTempFiles':
                if ($short) {
                    return 'Cleans up no longer used publicly accesible temp files';
                }
                return 'Cleans up no longer used publicly accesible temp files. Usage:

./cx cleanTempFiles';
                break;
        }
        return '';
    }

    public function executeCommand($command, $arguments, $dataArguments = array())
    {

        switch ($command) {
            case 'help':
                $commands = $this->cx->getCommands();
                if (count($arguments)) {
                    if (isset($commands[current($arguments)])) {
                        echo $commands[current($arguments)]->getCommandDescription(
                            current($arguments),
                            false
                        ) . "\n";
                        return;
                    } else {
                        echo "No such command\n";
                    }
                }
                echo 'Cloudrexx command mode help.

';
                //if (count($arguments))
                echo 'Synopsis: cx(.bat) <command> [<parameter>]

Use »cx(.bat) help <command>« for more info about a command

Available commands:

';
                $commandPerComponent = array();
                foreach ($commands as $command=>$component) {
                    if (!isset($commandPerComponent[$component->getName()])) {
                        $commandPerComponent[$component->getName()] = array();
                    }
                    $commandPerComponent[$component->getName()][$command] = $component;
                }
                foreach ($commandPerComponent as $componentName=>$commands) {
                    $component = current($commands);
                    echo $component->getType() . ' "' . $componentName . '"
';
                    foreach ($commands as $command=>$component) {
                        echo "\t" . $command . ' - ' . $component->getCommandDescription($command, true) . '
';
                    }
                }
                break;
            case 'status':
                // prepare file list
                    // if no argument given:
                        // check complete installation
                    // if one argument given:
                        // treat as path
                    // if two arguments given:
                        // treat as component type and name
                // foreach file in file list
                $files = array('/var/www/CxTrunk/index2.php');
                $fileCount = array(
                    'customized' => 0,
                    'irregular' => 0,
                    'unused' => 0,
                    'deleted' => 0,
                    'normal' => 0,
                );
                foreach ($files as $file) {
                    $fileState = $this->getFileState($file);
                    $fileCount[$fileState]++;
                    if ($fileState == 'normal') {
                        continue;
                    }
                    echo ' ' . substr($fileState, -1) . '  ' . $file . "\r\n";
                }
                $summary = array();
                foreach ($fileCount as $type=>$count) {
                    $summary[] = $count . ' files ' . $type;
                }
                echo implode(', ', $summary);
                break;
            case 'diff':
                // prepare file list
                    // if no argument given:
                        // check complete installation
                    // if one argument given:
                        // treat as path
                    // if two arguments given:
                        // treat as component type and name
                foreach ($files as $file) {
                    $fileState = $this->getFileState($file);
                    if ($fileState != 'customized') {
                        continue;
                    }
                    // execute diff command for file
                }
                break;
            case 'version':
                global $_CONFIG;
                echo $_CONFIG['coreCmsName'] . ' ' .' ' .  $_CONFIG['coreCmsEdition'] . ' \'' . $_CONFIG['coreCmsCodeName'] . '\' ' . $_CONFIG['coreCmsVersion'] . ' ' . $_CONFIG['coreCmsStatus'];
                break;
            case 'install':
                echo "BETA!!\r\n";
                try {
                    $component = new \Cx\Core\Core\Model\Entity\ReflectionComponent($arguments[0]);
                    $component->install();
                } catch (\BadMethodCallException $e) {
                    echo 'Error: ' . $e->getMessage();
                }
                break;
            case 'activate':
                $component = new \Cx\Core\Core\Model\Entity\ReflectionComponent($arguments[1], $arguments[0]);
                $component->activate();
                echo 'Done';
                break;
            case 'deactivate':
                $component = new \Cx\Core\Core\Model\Entity\ReflectionComponent($arguments[1], $arguments[0]);
                $component->deactivate();
                echo 'Done';
                break;
            case 'cleanTempFiles':
                $basePath = $this->cx->getWebsitePublicTempPath();

                // step 1: delete all files older than XY
                $di = new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS);
                $fi = new \RecursiveCallbackFilterIterator($di, function($file, $key, $iterator) {
                    if ($iterator->hasChildren()) {
                        return true;
                    }
                    return new \DateTime('@' . $file->getMTime()) < new \DateTime('1 hours ago');
                });

                foreach (new \RecursiveIteratorIterator($fi) as $file) {
                    \Cx\Lib\FileSystem\FileSystem::delete_file($file->getRealPath());
                }

                // step 2: delete all empty directories
                $fi = new \RecursiveCallbackFilterIterator($di, function($file, $key, $iterator) {
                    if ($iterator->hasChildren()) {
                        return true;
                    }
                    return false;
                });
                foreach (new \RecursiveIteratorIterator($fi, \RecursiveIteratorIterator::SELF_FIRST) as $file) {
                    $file = new \SplFileObject($file->getRealPath());
                    if (!$file->hasChildren()) {
                        \Cx\Lib\FileSystem\FileSystem::delete_folder($file->getRealPath());
                    }
                }
                break;
        }
        echo '
';
    }

    public function getFileState($file) {
        if (!file_exists($file)) {
            return 'deleted';
        }
        // get path relative to Cloudrexx root
        // md5sum not matching
            // return 'irregular';
        // exists in customizing
            // matches md5sum
                // return 'unused';
            // else return 'customized';
        return 'normal';
    }
    
    /**
     * Executes a command (in CLI command mode) asynchronously
     * @param string $command Command mode command name to execute
     * @param array $arguments List of strings as arguments for the command
     * @throws \Exception If an argument or the command name contains any other characters than a-z, A-Z and 0-9
     * @throws \Exception If we're running on windows
     * @todo: Add support for Windows environment (http://stackoverflow.com/questions/26876728/execute-php-script-from-php-page-asynchronously-in-windows-system)
     */
    public function execAsync($command, $arguments) {
        array_unshift($arguments, $command);
        foreach ($arguments as $argument) {
            if (!preg_match('/^[a-z0-9]+$/i', $argument)) {
                throw new \Exception('Invalid argument');
            }
        }
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            throw new \Exception('This function does not yet work on windows systems!');
        }
        // todo: ' &' should not be here and instead be a flag for cx (./cx -async <params>)
        // todo: we should allow overriding the call using event system (for cloud)
        $command = static::CLI_SCRIPT_NAME . implode(' ', $arguments) . ' > /dev/null 2>&1 &';
        exec($command);
    }

    /**
     * Returns a publicly readable folder name with a unique random name
     *
     * Its intended use is for asynchronously generated files that need to be
     * readable by a user.
     * @todo: This should be part of a component "Temp" of type "core_module"
     *          which registers a MediaSource and returns a folder object. This
     *          includes the CLI command to clean up no longer needed folders.
     * @return string Unique absolute publicly readable folder path
     */
    public function getPublicUserTempFolder() {
        $basePath = $this->cx->getWebsitePublicTempPath();
        $folderName = '';
        do {
            $folderName = substr(
                str_replace(
                    ['+', '/', '='],
                    '',
                    base64_encode(random_bytes(32))
                ),
                0,
                32
            );
            $path = $basePath . '/' . $folderName . '/';
        } while (file_exists($path));
        \Cx\Lib\FileSystem\FileSystem::make_folder($path);
        return $path;
    }
}
