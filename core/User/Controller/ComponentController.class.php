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
 * Main controller for User
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 * @version     5.0.3
 */
namespace Cx\Core\User\Controller;

/**
 * Main controller for User
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 * @version     5.0.3
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Get the Controller classes
     *
     * @return array name of the controller classes
     */
    public function getControllerClasses()
    {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array('Frontend', 'Backend', 'Export', 'JsonUser');
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
        return array('JsonUserController');
    }


    /**
     * Returns a list of command mode commands provided by this component
     *
     * @return array List of command names
     */
    public function getCommandsForCommandMode()
    {
        return array(
            'exportUser' => new
            \Cx\Core_Modules\Access\Model\Entity\Permission(
                array('http', 'https'), // allowed protocols
                array(
                    'get',
                    'post',
                    'put',
                    'delete',
                    'trace',
                    'options',
                    'head',
                ),   // allowed methods
                true,  // requires login
                array()
            ),
        );
    }

    /**
     * Returns the description for a command provided by this component
     *
     * @param string  $command The name of the command to fetch the description from
     * @param boolean $short   Wheter to return short or long description
     *
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false)
    {
        switch ($command) {
            case 'exportUser':
                if ($short) {
                    return 'Export all users of a group';
                }
                return 'Export all users of a group in a .csv file';
            default:
                return '';
        }
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     *
     * @param string $command       Name of command to execute
     * @param array  $arguments     List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     *
     * @see getCommandsForCommandMode()
     *
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        try {
            switch ($command) {
                case 'exportUser':
                    if (empty($arguments['groupId']) && empty($arguments['langId'])) {
                        return;
                    }
                    $this->getController('Export')->exportUsers(
                        intval($arguments['groupId']), $arguments['langId']
                    );
                    break;
            }
        } catch (\Exception $e) {
            http_response_code(400); // BAD REQUEST
            echo 'Exception of type "' . get_class($e) . '" with message "' .
                $e->getMessage() . '"';
        }
    }

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
    public function registerEventListeners()
    {
        $userListener
            = new \Cx\Core\User\Model\Event\UserEventListener(
            $this->cx
        );

        $entityClass = 'Cx\\Core\\' . $this->getName()
            . '\\Model\\Entity\\User';
        $this->cx->getEvents()->addModelListener(
            \Doctrine\ORM\Events::preUpdate,
            $entityClass,
            $userListener
        );
    }

}
