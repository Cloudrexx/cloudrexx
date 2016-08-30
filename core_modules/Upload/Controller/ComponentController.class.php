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
 * Main controller for Upload
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */

namespace Cx\Core_Modules\Upload\Controller;

/**
 * Main controller for Upload
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * getControllerClasses
     *
     * @return type
     */
    public function getControllerClasses() {
        return array();
    }

    /**
     * Load the component Upload.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objTemplate;
        $objUploadModule = new UploadManager();
        $objUploadModule->getPage();
    }

    /**
     * Do something before resolving is done
     *
     * @param \Cx\Core\Routing\Url $request The URL object for this request
     */
    public function preResolve(\Cx\Core\Routing\Url $request) {
        switch ($this->cx->getMode()) {

            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'Upload') {
                    $_REQUEST['standalone'] = 'true';
                }

            break;
        }
    }

    /**
     * Do something after resolving is done
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $sessionObj;
        switch ($this->cx->getMode()) {

            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'Upload') {
                    if (!isset($sessionObj) || !is_object($sessionObj)) $sessionObj = \cmsSession::getInstance(); // initialize session object
                    $objUploadModule = new Upload();
                    $objUploadModule->getPage();
                    //execution never reaches this point
                }

            break;
        default :
            break;
        }
    }
}
