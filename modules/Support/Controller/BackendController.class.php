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
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_support
 */

namespace Cx\Modules\Support\Controller;

/**
 * Class SupportException
 */
class SupportException extends \Exception {}

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_support
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * Template object
     */
    protected $template;


    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {
        return array();
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        // this class inherits from Controller, therefore you can get access to
        // Cx like this:
        $this->cx;
        $this->template = $template;
        $act = $cmd[0];

        //support configuration setting
        self::errorHandler();
        $this->connectToController($act);

        \Message::show();
    }

    /**
     * Trigger a controller according the act param from the url
     *
     * @param   string $act
     */
    public function connectToController($act)
    {
        $act = ucfirst($act);
        if (!empty($act)) {
            $controllerName = __NAMESPACE__.'\\'.$act.'Controller';
            if (!$controllerName && !class_exists($controllerName)) {
                return;
            }
            //  instantiate the view specific controller
            $objController = new $controllerName($this->getSystemComponentController(), $this->cx);
        } else {
            // instantiate the default View Controller
            $objController = new DefaultController($this->getSystemComponentController(), $this->cx);
        }
        $objController->parsePage($this->template, array());
    }

    /**
     * Fixes database errors.
     *
     * @global array $_CONFIG
     *
     * @return boolean
     * @throws SupportException
     */
    static function errorHandler() {
        global $_CONFIG;

        try {
            \Cx\Core\Setting\Controller\Setting::init('Support', '', 'Yaml');

            //setup group
            \Cx\Core\Setting\Controller\Setting::init('Support', 'setup', 'Yaml');
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('faqUrl') && !\Cx\Core\Setting\Controller\Setting::add('faqUrl', 'https://www.cloudrexx.com/FAQ', 1, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')) {
                throw new SupportException("Failed to add Setting entry for faq url");
            }
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('recipientMailAddress') && !\Cx\Core\Setting\Controller\Setting::add('recipientMailAddress', $_CONFIG['coreAdminEmail'], 2, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')) {
                throw new SupportException("Failed to add Setting entry for recipient mail address");
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }

        // Always!
        return false;
    }

}
