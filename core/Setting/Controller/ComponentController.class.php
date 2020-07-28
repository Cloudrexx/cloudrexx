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
 * Main controller for Setting
 *
 * @author Michael Ritter <michael.ritter@comvation.com>
 * @package cloudrexx
 * @subpackage core_setting
 */

namespace Cx\Core\Setting\Controller;

/**
 * Main controller for Setting
 *
 * @author Michael Ritter <michael.ritter@comvation.com>
 * @package cloudrexx
 * @subpackage core_setting
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

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

    /**
     * Returns a list of command mode commands provided by this component
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array(
            'Setting' => new \Cx\Core_Modules\Access\Model\Entity\Permission(
                array(),
                array('cli'),
                false
            ),
        );
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        if ($command != 'Setting') {
            return '';
        }
        if ($short) {
            return 'Allows to show, add, edit and remove settings';
        }
        return 'Setting list <component> [-group=<group>] [-engine=<engine>] [-repository=<repository>]
Setting get <component> [-group=<group>] [-engine=<engine>] [-repository=<repository>] <name>
Setting set <component> [-group=<group>] [-engine=<engine>] [-repository=<repository>] <name> <value>
Setting add <component> [-group=<group>] [-engine=<engine>] [-repository=<repository>]<name> <value> <ord> <type> <values>
Setting delete <component> [-group=<group>] [-engine=<engine>] [-repository=<repository>] <name>';
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     * @see getCommandsForCommandMode()
     * @param string $command Name of command to execute
     * @param array $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        switch ($command) {
            case 'Setting':
                if (count($arguments) < 2) {
                    echo 'Not enough arguments';
                    return;
                }
                $subCommand = array_shift($arguments);
                $component = array_shift($arguments);
                $group = null;
                if (isset($arguments['-group'])) {
                    $group = $arguments['-group'];
                    unset($arguments['-group']);
                }
                $engine = 'Database';
                if (isset($arguments['-engine'])) {
                    $engine = $arguments['-engine'];
                    unset($arguments['-engine']);
                }
                $repository = null;
                if (isset($arguments['-repository'])) {
                    $repository = $arguments['-repository'];
                    unset($arguments['-repository']);
                }
                \Cx\Core\Setting\Controller\Setting::init(
                    $component,
                    $group,
                    $engine,
                    $repository
                );
                switch ($subCommand) {
                    case 'list':
                        $data = \Cx\Core\Setting\Controller\Setting::getArray(
                            $component,
                            $group
                        );
                        break;
                    case 'get':
                        if (!count($arguments)) {
                            echo 'Not enough arguments';
                            return;
                        }
                        $data = \Cx\Core\Setting\Controller\Setting::getValue(
                            array_shift($arguments)
                        );
                        break;
                    case 'set':
                        if (count($arguments) < 2) {
                            echo 'Not enough arguments';
                            return;
                        }
                        $name = array_shift($arguments);
                        \Cx\Core\Setting\Controller\Setting::set(
                            $name,
                            array_shift($arguments)
                        );
                        $data = \Cx\Core\Setting\Controller\Setting::update(
                            $name
                        );
                        break;
                    case 'add':
                        if (count($arguments) < 5) {
                            echo 'Not enough arguments';
                            return;
                        }
                        $data = \Cx\Core\Setting\Controller\Setting::add(
                            array_shift($arguments),
                            array_shift($arguments),
                            array_shift($arguments),
                            array_shift($arguments),
                            array_shift($arguments),
                            $group
                        );
                        break;
                    case 'delete':
                        if (!count($arguments)) {
                            echo 'Not enough arguments';
                            return;
                        }
                        $data = \Cx\Core\Setting\Controller\Setting::delete(
                            array_shift($arguments),
                            $group
                        );
                        break;
                    default:
                        echo 'Illegal syntax';
                        return;
                }
                $hydrator = $this->getComponent('DataAccess')->getController(
                    'CliOutput'
                );
                echo $hydrator->parse(
                    array(
                        'status' => 'success',
                        'data' => $data,
                    )
                );
                break;
        }
    }
}
