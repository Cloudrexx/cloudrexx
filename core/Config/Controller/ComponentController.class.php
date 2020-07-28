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
 * Main controller for Config
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_config
 */

namespace Cx\Core\Config\Controller;

/**
 * Main controller for Config
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_config
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * {@inheritdoc}
     */
    public function getControllerClasses()
    {
        return array('EsiWidget');
    }

    /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController');
    }

    /**
     * Do something after all active components are loaded
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     */
    public function postComponentLoad() {
        // initial load of all config settings
        \Cx\Core\Setting\Controller\Setting::init('Config', null, 'Yaml', null, \Cx\Core\Setting\Controller\Setting::REPOPULATE);
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandsForCommandMode() {
        return array('config');
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'config':
                if ($short) {
                    return 'Allows (re-)initialization of base configuration';
                }
                return $this->getCommandDescription($command, true) . '

    Usage: ./cx config init [--force]';
            default:
                return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        switch ($command) {
            case 'config':
                $force = current($arguments) == '--force';
                \Cx\Core\Config\Controller\Config::init(null, $force);
                echo 'Done' . PHP_EOL;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $widgetController = $this->getComponent('Widget');
        foreach (
            array(
                'GLOBAL_TITLE',
                'DOMAIN_URL',
                'GOOGLE_MAPS_API_KEY'
            ) as $widgetName
        ) {
            $widgetController->registerWidget(
                new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                    $this,
                    $widgetName
                )
            );
        }
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $_ARRAYLANG;
        $subMenuTitle = $_ARRAYLANG['TXT_SYSTEM_SETTINGS'];

        $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
        $cachedRoot = $this->cx->getTemplate()->getRoot();
        $this->cx->getTemplate()->setRoot($this->getDirectory() . '/View/Template/Backend');

        \Permission::checkAccess(17, 'static');
        $objConfig = new \Cx\Core\Config\Controller\Config();
        $objConfig->getPage();

        $this->cx->getTemplate()->setRoot($cachedRoot);
    }

    public function registerEventListeners() {
        static::registerYamlSettingEventListener($this->cx);
    }

    public static function registerYamlSettingEventListener($cx) {
        $evm = $cx->getEvents();
        $yamlSettingEventListener = new \Cx\Core\Config\Model\Event\YamlSettingEventListener($cx);
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Core\\Setting\\Model\\Entity\\YamlSetting', $yamlSettingEventListener);
        $evm->addModelListener('postFlush', 'Cx\\Core\\Setting\\Model\\Entity\\YamlSetting', $yamlSettingEventListener);
    }
}
