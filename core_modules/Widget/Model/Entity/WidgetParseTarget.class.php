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
 * Represents an entity which has content that can contain widgets
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Model\Entity;

/**
 * Represents an entity which has content that can contain widgets
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
abstract class WidgetParseTarget extends \Cx\Core\View\Model\Entity\ParseTarget {

    /**
     * Returns the content for a widget
     * @param string $widgetName
     * @param \Cx\Core\View\Model\Entity\Theme $theme Theme which is parsed
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page which is parsed
     * @return \Cx\Core\Html\Sigma Widget content
     */
    public function getWidgetContent($widgetName, $theme, $page, $channel) {
        $template = $this->getContentTemplateForWidget(
            $widgetName,
            $page->getLang(),
            $page,
            $channel
        );
        if (
            $template->placeholderExists($widgetName) ||
            !$template->blockExists($widgetName)
        ) {
            return $template;
        }
        $widgetTemplate = new \Cx\Core\Html\Sigma();
        $widgetTemplate->setTemplate($template->getUnparsedBlock($widgetName));
        return $widgetTemplate;
    }

    /**
     * Returns the template in which the widget can be used
     * @param string $widgetName Name of the Widget to get template for
     * @param int $langId Language ID
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Current page
     * @param string $channel Current channel
     * @return \Cx\Core\Html\Sigma Template which may contain the widget
     */
    protected function getContentTemplateForWidget($widgetName, $langId, $page, $channel) {
        $getter = 'get' . ucfirst($this->getWidgetContentAttributeName($widgetName));
        $content = $this->$getter($langId, $channel);
        $template = new \Cx\Core\Html\Sigma();
        $template->setTemplate($content);
        return $template;
    }

    /**
     * Returns the name of the attribute which contains content that may contain a widget
     * @param string $widgetName
     * @return string Attribute name
     */
    protected abstract function getWidgetContentAttributeName($widgetName);
}
