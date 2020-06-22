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
 * Main controller for Session
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_session
 */

namespace Cx\Core\Session\Controller;

/**
 * Main controller for Session
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_session
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }
      /**
     * Do something before resolving is done
     *
     * @param \Cx\Core\Routing\Url                      $request    The URL object for this request
     */
    public function preResolve(\Cx\Core\Routing\Url $request) {
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $sessionObj = $this->getSession();
            if (!$sessionObj) {
                return;
            }
            $sessionObj->cmsSessionStatusUpdate('backend');
        }
    }

    /**
     * Returns the current session
     *
     * If the session has not yet been initialized, it will be initialized
     * if $forceInitialization is set to TRUE.
     * Otherwise it will only initialize the session if an existing session
     * can be resumed.
     *
     * @param   boolean $initialize Whether or not to force the initialization
     *                              of a session.
     * @return \Cx\Core\Session\Model\Entity\Session Session instance of
     *                                               current user. If no
     *                                               session is present and
     *                                               session initialization is
     *                                               not forced, then NULL is
     *                                               returned.
     */
    public function getSession($forceInitialization = true) {
        if (
            !\Cx\Core\Session\Model\Entity\Session::sessionExists() &&
            !$forceInitialization
        ) {
            return null;
        }

        return \Cx\Core\Session\Model\Entity\Session::getInstance();
    }

    /**
     * Returns the state of the session
     * @return boolean TRUE if the session has been initialized, otherwise FALSE
     */
    public function isInitialized() {
        return \Cx\Core\Session\Model\Entity\Session::isInitialized();
    }
}
