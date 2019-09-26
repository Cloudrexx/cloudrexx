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
 * Main controller for Privacy
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_privacy
 */

namespace Cx\Core_Modules\Privacy\Controller;

/**
 * Main controller for Privacy
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_privacy
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * {@inheritdoc}
     */
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array('EsiWidget');
    }

    /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson() {
        return array('EsiWidgetController');
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $widgetController = $this->getComponent('Widget');
        $widgetController->registerWidget(
            // TODO: Set correct ESI variables
            new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                'COOKIE_NOTE'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        if (
            \Cx\Core\Setting\Controller\Setting::getValue(
                'cookieNote',
                'Config'
            ) != 'on'
        ) {
            return;
        }
        \JS::registerCode(
            'var cookieNoteTtl = "' .
            \Cx\Core\Setting\Controller\Setting::getValue(
                'cookieNoteTtl',
                'Config'
            ) . '";'
        );
        \JS::registerCSS(substr($this->getDirectory(false, true) . '/View/Style/Frontend.css', 1));
        \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/Frontend.js', 1));
        $this->cx->getTemplate()->_blocks['__global__'] = preg_replace(
            '#</body[^>]*>#',
            '{COOKIE_NOTE}\\0',
            $this->cx->getTemplate()->_blocks['__global__']
        );
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {}
}
