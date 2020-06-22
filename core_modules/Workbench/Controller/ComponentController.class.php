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
 * ComponentController for Workbench
 *
 * Loads backend view Controllers and adds warning message
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Controller;

/**
 * ComponentController for Workbench
 *
 * Loads backend view Controllers and adds warning message
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * getControllerClasses
     *
     * @return type
     */
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    public function getCommandsForCommandMode() {
        return array('workbench', 'wb');
    }

    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'workbench':
                return 'Development framework';
            case 'wb':
                return 'Shortcut alias for `workbench`';
        }
    }

    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        new \Cx\Core_Modules\Workbench\Model\Entity\ConsoleInterface(array_merge(array($command), $arguments), $this->cx);
    }

    /**
     * Loads backend view Controllers (BETA)
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page
     * @todo YAML assistant
     * @todo Cx/Module sandbox
     * @todo Language var checker (/translation helper)
     * @todo Component analysis (/testing)
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        $objTemplate = $this->cx->getTemplate();
        $cachedRoot = $this->cx->getTemplate()->getRoot();
        $this->cx->getTemplate()->setRoot(ASCMS_CORE_PATH . '/Core/View/Template/Backend');
        $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'ContentMaster.html');
        $this->cx->getTemplate()->setRoot($cachedRoot);
        $_ARRAYLANG = \Env::get('init')->loadLanguageData($this->getName());

        // Initialize
        if (!isset($_GET['act'])) {
            $_GET['act'] = '';
        }
        $cmd = explode('/', $_GET['act']);
        if (!isset($cmd[0])) {
            $cmd[0] = 'development';
        }
        $controller = $cmd[0];
        if (!isset($cmd[1])) {
            $cmd[1] = '';
        }
        $act = $cmd[1];

        // Load controller specific things
        switch ($controller) {
            case 'sandbox':
                // The following code is for sandbox only:
                if ($act == '') {
                    $act = 'dql';
                }
                $navEntries = array(
                    'index.php?cmd=Workbench&amp;act=sandbox/dql' => 'DQL',
                    'index.php?cmd=Workbench&amp;act=sandbox/php' => 'PHP',
                );
                $objTemplate->setVariable('ADMIN_CONTENT', new Sandbox($_ARRAYLANG, $act, $_POST));
                break;
            case 'development':
                if ($act == '') {
                    $act = 'yaml';
                }
            default:
                $navEntries = array(
                    'index.php?cmd=Workbench&amp;act=development/yaml' => 'YAML',
                    'index.php?cmd=Workbench&amp;act=development/components' => 'Components',
                );
                $objTemplate->setVariable('ADMIN_CONTENT', new Toolbox($_ARRAYLANG, $act, $_POST));
                break;
        }

        // set tabs
        $navigation = new \Cx\Core\Html\Sigma(ASCMS_CORE_PATH . '/Core/View/Template/Backend');
        $navigation->loadTemplateFile('Navigation.html');
        foreach ($navEntries as $href=>$title) {
            $navigation->setVariable(array(
                'HREF' => $href,
                'TITLE' => $title,
            ));
            if (strtolower($title) == $act) {
                $navigation->touchBlock('tab_active');
            }
            $navigation->parse('tab_entry');
        }
        $objTemplate->setVariable('CONTENT_NAVIGATION', $navigation->get());
    }

    /**
     * Add the warning banner
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        \JS::registerJS('core_modules/Workbench/View/Script/Warning.js');
        $objTemplate = $this->cx->getTemplate();
        $warning = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH . '/Workbench/View/Template/Backend');
        $warning->loadTemplateFile('Warning.html');
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            \JS::registerCSS('core_modules/Workbench/View/Style/WarningBackend.css');
            $objTemplate->_blocks['__global__'] = preg_replace('/<div id="container"[^>]*>/', '\\0' . $warning->get(), $objTemplate->_blocks['__global__']);
        } else {
            \JS::registerCSS('core_modules/Workbench/View/Style/WarningFrontend.css');
            $objTemplate->_blocks['__global__'] = preg_replace('/<body[^>]*>/', '\\0' . $warning->get(), $objTemplate->_blocks['__global__']);
        }
    }
}
