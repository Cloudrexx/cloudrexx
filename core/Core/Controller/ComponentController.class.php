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

    public function getCommandsForCommandMode() {
        return array('help', 'status', 'diff', 'version', 'info', 'install', 'uninstall');
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

cx(.bat) install {path to zip package}';
                break;
            case 'uninstall':
                if ($short) {
                    return 'Uninstalls a component';
                }
                return 'Uninstalls the specified component. Usage:

cx(.bat) uninstall [core|core_module|module|lib|theme] {component name}';
                break;
        }
        return '';
    }

    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        
        switch ($command) {
            case 'help':
                echo 'Cloudrexx command mode help.

';
                //if (count($arguments))
                echo 'Synopsis: cx(.bat) <command> [<parameter>]

Use »cx(.bat) help <command>« for more info about a command

Available commands:

';
                $commands = $this->cx->getCommands();
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
                    $component = new \Cx\Core\Core\Model\Entity\ReflectionComponent($arguments[1]);
                    $component->install();
                } catch (\BadMethodCallException $e) {
                    echo 'Error: ' . $e->getMessage();
                }
                break;
            case 'uninstall':
                echo "TODO!!\r\n";
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
}

