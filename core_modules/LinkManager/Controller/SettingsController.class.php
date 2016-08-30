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
 * SettingsController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Controller;

/**
 * The class SettingsController for setting the entries count per page
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class SettingsController extends \Cx\Core\Core\Model\Entity\Controller {

    /**
     * Em instance
     * @var \Doctrine\ORM\EntityManager em
     */
    protected $em;

    /**
     * Sigma template instance
     * @var Cx\Core\Html\Sigma  $template
     */
    protected $template;

    /**
     * module name
     * @var string $moduleName
     */
    protected $moduleName = 'LinkManager';

    /**
     * module name for language placeholder
     * @var string $moduleNameLang
     */
    protected $moduleNameLang = 'LINKMANAGER';


    /**
     * Controller for the Backend Settings views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     * @param string                                               $submenu                   the submenu name
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        //check the user permission
        \Permission::checkAccess(1032, 'static');

        parent::__construct($systemComponentController, $cx);
        $this->em       = $this->cx->getDb()->getEntityManager();
    }

    /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showDefault();
    }

    /**
     * Show the general setting options
     *
     * @global array $_ARRAYLANG
     */
    public function showDefault()
    {
        global $_ARRAYLANG;

        \Cx\Core\Setting\Controller\Setting::init('LinkManager', 'config');
        //get post values
        $settings = isset($_POST['setting']) ? $_POST['setting'] : array();
        if (isset($_POST['save'])) {
            $includeFromSave = array('entriesPerPage');
            foreach($settings As $settingName => $settingValue) {
                if (in_array($settingName, $includeFromSave)) {
                    \Cx\Core\Setting\Controller\Setting::set($settingName, $settingValue);
                    \Cx\Core\Setting\Controller\Setting::update($settingName);
                    \Message::ok($_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_SUCCESS_MSG']);
                }
            }
        }

        //get the settings values from DB
        $this->template->setVariable(array(
            $this->moduleNameLang.'_ENTRIES_PER_PAGE'   => \Cx\Core\Setting\Controller\Setting::getValue('entriesPerPage', 'LinkManager')
        ));
    }
}
