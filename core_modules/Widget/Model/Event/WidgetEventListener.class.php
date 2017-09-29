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
 * Class WidgetEventListener
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_widget
 * @version     1.0.0
 */

namespace Cx\Core_Modules\Widget\Model\Event;

/**
 * Class WidgetEventListener
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_widget
 * @version     1.0.0
 */
class WidgetEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{

    /**
     * Access event listener for clearing esi cache
     *
     * @param array $eventArgs
     *
     * return null
     */
    public function clearEsiCache($eventArgs)
    {
        if (empty($eventArgs) || $eventArgs[0] != 'Widget') {
            return;
        }

        $widgetNames = isset($eventArgs[1]) ? $eventArgs[1] : array();
        $widgets     = $this->cx->getComponent('Widget')->getWidgets();

        if (empty($widgets)) {
            return;
        }

        if (empty($widgetNames) || !is_array($widgetNames)) {
            foreach ($widgets as $widget) {
                $widget->clearCache();
            }
            return;
        }

        foreach ($widgets as $widget) {
            if (in_array($widget->getName(), $widgetNames)) {
                $widget->clearCache();
            }
        }
    }

    /**
     * OnEvent
     *
     * @param string $eventName event name
     * @param array  $eventArgs event arguments
     */
    public function onEvent($eventName, array $eventArgs)
    {
        $this->$eventName($eventArgs);
    }
}
