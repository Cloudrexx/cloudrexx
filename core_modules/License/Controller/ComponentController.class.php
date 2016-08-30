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
 * Main controller for License
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_license
 */

namespace Cx\Core_Modules\License\Controller;

/**
 * Main controller for License
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_license
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    public function getControllerClasses() {
// Return an empty array here to let the component handler know that there
// does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {

        global $_CORELANG, $objTemplate, $objDatabase, $act;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(177, 'static');
                $config = \Env::get('config');
                $objLicense = new \Cx\Core_Modules\License\LicenseManager($act, $objTemplate, $_CORELANG, $config, $objDatabase);
                $objLicense->getPage($_POST, $_CORELANG);
                break;
            default:
                break;
        }
    }

    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        require_once($this->getDirectory() . '/versioncheck.php');
        die();
    }

    /**
     * Do something before resolving is done
     *
     * @param \Cx\Core\Routing\Url                      $request    The URL object for this request
     */
    public function preResolve(\Cx\Core\Routing\Url $request) {
        global $objDatabase;

        $config = \Env::get('config');
        $license = \Cx\Core_Modules\License\License::getCached($config, $objDatabase);

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                // make sure license data is up to date (this updates active available modules)
                // @todo move to core_module license

                $oldState = $license->getState();
                $license->check();
                if ($oldState != $license->getState()) {
                    $license->save($objDatabase);
                }
                if ($license->isFrontendLocked()) {
                    // Since throwing an exception now results in showing offline.html, we can simply do
                    throw new \Exception('Frontend locked by license');
                }
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:

                $objTemplate = $this->cx->getTemplate();
                if ($objTemplate->blockExists('upgradable')) {
                    if ($license->isUpgradable()) {
                        $objTemplate->touchBlock('upgradable');
                    } else {
                        $objTemplate->hideBlock('upgradable');
                    }
                }
                break;

            default:
                break;
        }
    }

    /**
     * Do something after resolving is done
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {
// TODO: Deactivated license check for now. Implement new behavior.
        return true;

        global $plainCmd, $objDatabase, $_CORELANG, $_LANGID, $section;

        $license = \Cx\Core_Modules\License\License::getCached(\Env::get('config'), $objDatabase);

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                if (!($license->isInLegalComponents('fulllanguage')) && $_LANGID != \FWLanguage::getDefaultLangId()) {
                    $_LANGID = \FWLanguage::getDefaultLangId();
                    \Env::get('Resolver')->redirectToCorrectLanguageDir();
                }

                if (!empty($section) && !$license->isInLegalFrontendComponents($section)) {
                    if ($section == 'Error') {
                        // If the error module is not installed, show this
                        die($_CORELANG['TXT_THIS_MODULE_DOESNT_EXISTS']);
                    } else {
                        //page not found, redirect to error page.
                        \Cx\Core\Csrf\Controller\Csrf::header('Location: ' . \Cx\Core\Routing\Url::fromModuleAndCmd('Error'));
                        exit;
                    }
                }
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                // check if the requested module is active:
                if (!in_array($plainCmd, array('Login', 'noaccess', ''))) {
                    $query = '
                                SELECT
                                    modules.is_licensed
                                FROM
                                    ' . DBPREFIX . 'modules AS modules,
                                    ' . DBPREFIX . 'backend_areas AS areas
                                WHERE
                                    areas.module_id = modules.id
                                    AND (
                                        areas.uri LIKE "%cmd=' . contrexx_raw2db($plainCmd) . '&%"
                                        OR areas.uri LIKE "%cmd=' . contrexx_raw2db($plainCmd) . '"
                                    )
                            ';
                    $res = $objDatabase->Execute($query);
                    if (!$res->fields['is_licensed']) {
                        $plainCmd = in_array('LicenseManager', \Env::get('cx')->getLicense()->getLegalComponentsList()) ?'License' : 'Home' ;
                    }
                }

                // If logged in
                if (\Env::get('cx')->getUser()->objUser->login(true)) {
                    $license->check();
                    if ($license->getState() == \Cx\Core_Modules\License\License::LICENSE_NOK) {
                        $plainCmd = in_array('LicenseManager', \Env::get('cx')->getLicense()->getLegalComponentsList()) ?'License' : 'Home' ;
                        $license->save($objDatabase);
                    }
                    $lc = \Cx\Core_Modules\License\LicenseCommunicator::getInstance(\Env::get('config'));
                    $lc->addJsUpdateCode($_CORELANG, $license, $plainCmd == 'License');
                }
                break;

            default:
                break;
        }
    }

    /**
     * Returns a list of command mode commands provided by this component
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array('licup');
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        return 'Updates Cloudrexx license';
    }
}
