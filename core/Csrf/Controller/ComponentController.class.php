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
 * Main controller for Csrf
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_csrf
 */

namespace Cx\Core\Csrf\Controller;

/**
 * Main controller for Csrf
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_csrf
 */


class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController
{
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Do something after resolving is done
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $plainCmd, $cmd, $_CORELANG;


        // CSRF code needs to be even in the login form. otherwise, we
        // could not do a super-generic check later.. NOTE: do NOT move
        // this above the "new cmsSession" line!
        Csrf::add_code();

        // CSRF protection.
        // Note that we only do the check as long as there's no
        // cmd given; this is so we can reload the main screen if
        // the check has failed somehow.
        // The CSRF code needn't to be checked in the login module
        // because the user isn't logged in at this point.
        if (!empty($plainCmd) && !empty($cmd) and !in_array($plainCmd, array('Login', 'Home'))) {
            // Since language initialization in in the same hook as this
            // and we cannot define the order of module-processing,
            // we need to check if language is already initialized:
            if (!is_array($_CORELANG) || !count($_CORELANG)) {
                $objInit = \Env::get('init');
                $objInit->_initBackendLanguage();
                $_CORELANG = $objInit->loadLanguageData('core');
            }
            Csrf::check_code();
        }

    }
    /**
     * Do something after content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objTemplate;

        Csrf::add_placeholder($objTemplate);

    }
}
