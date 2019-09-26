<?php

/**
 * Contrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Comvation AG 2007-2015
 * @version   Contrexx 4.0
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
 * "Contrexx" is a registered trademark of Comvation AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Main controller for Html
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  core_html
 */

namespace Cx\Core\Html\Controller;

/**
 * This class is used as controller for core html. It is also a SystemComponentController
 * Its used to handle json request to ViewGenerator and FormGenerator
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  core_html
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * Returns all Controller class names for this component (except this)
     *
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array('ViewGeneratorJson');
    }

    /**
     * Returns a list of JsonAdapter class names
     *
     * @return array List of ComponentController classes
     */
    public function getControllersAccessableByJson() {
        return array('ViewGeneratorJsonController');
    }

    /**
     * @{inheritdoc}
     */
    public function registerEvents() {
        $evm = $this->cx->getEvents();
        $evm->addEvent($this->getName() . '.ViewGenerator:initialize');
    }

    /**
     * Adds a request param set to the whitelist
     *
     * This manages entries in a whitelist in the session. It is used to
     * temporarily grant a permission.
     * Permission is granted until session destroy.
     * @see getWhitelistPermission() for how to check if something is in whitelist
     * @todo It might make sense to add a scope param and move this to a more
     *          generic location.
     * @param string $method Identifier for the whitelist
     * @param array $getArguments Whitelisted GET arguments
     * @param array $postArguments Whitelisted POST arguments
     */
    public function whitelistParamSet($method, $getArguments, $postArguments = array()) {
        // check whether this is already whitelisted
        if ($this->getController('ViewGeneratorJson')->checkWhitelistPermission(
            array(
                'get' => array_merge(
                    array(0 => $method),
                    $getArguments
                ),
                'post' => $postArguments,
            )
        )) {
            return;
        }
        // initialize session indexes
        if (!isset($_SESSION['vg'])) {
            $_SESSION['vg'] = array();
        }
        if (!isset($_SESSION['vg']['whitelist'])) {
            $_SESSION['vg']['whitelist'] = array();
        }
        if (!isset($_SESSION['vg']['whitelist'][$method])) {
            $_SESSION['vg']['whitelist'][$method] = array();
        }
        // add entry to whitelist
        $_SESSION['vg']['whitelist'][$method][] = array(
            'get' => $getArguments,
            'post' => $postArguments,
        );
    }

    /**
     * Returns the permission object to check a whitelist
     *
     * @see whitelistParamSet() for how to add entries to the whitelist
     * @see ViewGeneratorJsonController::checkWhitelistPermission() for
     *          whitelist check.
     * @param string $method Identifier for the whitelist
     * @return \Cx\Core_Modules\Access\Model\Entity\Permission Permission object
     */
    public function getWhitelistPermission($method) {
        return new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array('http', 'https'),
            array('post', 'get'),
            false,
            array(),
            array(),
            new \Cx\Core_Modules\Access\Model\Entity\Callback(array(
                'Html',
                'checkWhitelistPermission',
                array($method),
                array()
            ))
        );
    }
}
