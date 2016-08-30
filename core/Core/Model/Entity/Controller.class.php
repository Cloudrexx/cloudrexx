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
 * This is the superclass for all Controller classes
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
namespace Cx\Core\Core\Model\Entity;

/**
 * This is the superclass for all Controller classes
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
abstract class Controller {

    /**
     * Main class instance
     * @var \Cx\Core\Core\Controller\Cx
     */
    protected $cx = null;

    /**
     * SystemComponentController for this Component
     * @var \Cx\Core\Core\Model\Entity\SystemComponentController
     */
    private $systemComponentController = null;

    /**
     * Creates new controller
     * @param SystemComponentController $systemComponentController Main controller for this system component
     * @param \Cx\Core\Core\Controller\Cx $cx Main class instance
     */
    public function __construct(SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        $this->cx = $cx;
        $this->systemComponentController = $systemComponentController;
        $this->systemComponentController->registerController($this);
    }

    /**
     * Returns the main controller
     * @return SystemComponentController Main controller for this system component
     */
    public function getSystemComponentController() {
        return $this->systemComponentController;
    }

    /**
     * Route methods like getName(), getType(), getDirectory(), etc.
     * @param string $methodName Name of method to call
     * @param array $arguments List of arguments for the method to call
     * @return mixed Return value of the method to call
     */
    public function __call($methodName, $arguments) {
        return call_user_func_array(array($this->systemComponentController, $methodName), $arguments);
    }
}
