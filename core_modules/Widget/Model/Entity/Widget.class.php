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
 * Represents a template widget
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Model\Entity;

/**
 * Represents a template widget
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
abstract class Widget extends \Cx\Model\Base\EntityBase {

    /**
     * Component which registered this widget
     * @var \Cx\Core\Core\Model\Entity\SystemComponentController
     */
    protected $component;

    /**
     * Name of this widget
     * @var string
     */
    protected $name;

    /**
     * Whether this widget has content (represents a template block)
     * or not (represents a placeholder)
     * @var boolean
     */
    protected $hasContent;

    /**
     * Instanciates a new widget
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $component Component registering this widget
     * @param string $name Name of this widget
     * @param boolean $hasContent Whether this widget has content or not
     */
    public function __construct($component, $name, $hasContent = false) {
        $this->component = $component;
        $this->name = $name;
        $this->hasContent = $hasContent;
    }

    /**
     * Returns the component which registered this widget
     * @return \Cx\Core\Core\Model\Entity\SystemComponentController Registering component
     */
    public function getRegisteringComponent() {
        return $this->component;
    }

    /**
     * Returns the name of this widget
     * @return string Name of this widget
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns whether this widget has content (represents a template block)
     * @return boolean True if this widget has content (represents a template
     *                 block), false otherwise (represents a placeholder)
     */
    public function hasContent() {
        return $this->hasContent;
    }

    /**
     * Parses this widget into $template
     * @param \HTML_Template_Sigma $template Template to parse this widget into
     * @param \Cx\Core\Routing\Model\Entity\Reponse $response Current response object
     * @param string $targetComponent Parse target component name
     * @param string $targetEntity Parse target entity name
     * @param string $targetId Parse target entity ID
     * @param array $excludedWidgets List of widget names that shall not be parsed
     */
    public function parse($template, $response, $targetComponent, $targetEntity, $targetId, $excludedWidgets = array()) {
        // Disable parsing of widgets in backend pending furtzer notice
        // See CLX-1674
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            return;
        }

        if (!$this->hasContent()) {
            if (!$template->placeholderExists($this->getName())) {
                return;
            }
            $content = $this->internalParse(
                $template,
                $response,
                $targetComponent,
                $targetEntity,
                $targetId
            );
            \LinkGenerator::parseTemplate($content);
            $template->setVariable(
                $this->getName(),
                $content
            );
        } else {
            if (!$template->blockExists($this->getName())) {
                return;
            }
            // get widget template
            $widgetHtml = $template->getUnparsedBlock($this->getName());
            \LinkGenerator::parseTemplate($widgetHtml);
            $widgetTemplate = new \Cx\Core_Modules\Widget\Model\Entity\Sigma();
            $widgetTemplate->setTemplate($widgetHtml);

            // parse this widget
            $this->internalParse($widgetTemplate, $response, $targetComponent, $targetEntity, $targetId);

            // recurse:
            $excludedWidgets[] = $this->getName();
            $this->getSystemComponentController()->parseWidgets(
                $widgetTemplate,
                $targetComponent,
                $targetEntity,
                $targetId,
                $excludedWidgets
            );

            // parse blocktemplate in main template
            $parsedContent = $widgetTemplate->get();
            $template->replaceBlock(
                $this->getName(),
                $parsedContent,
                false,
                true
            );
        }
    }

    /**
     * Really parses this widget into $template
     * If this Widget has no content, the replacement can simply be returned
     * as string. Otherwise the replacement must be done in $template.
     * @param \HTML_Template_Sigma $template Template to parse this widget into
     * @param \Cx\Core\Routing\Model\Entity\Reponse $response Current response object
     * @param string $targetComponent Parse target component name
     * @param string $targetEntity Parse target entity name
     * @param string $targetId Parse target entity ID
     * @return string Replacement for widgets without content, NULL otherwise
     */
    public abstract function internalParse($template, $response, $targetComponent, $targetEntity, $targetId);

    /**
     * Clears all cache files for this Widget (if any)
     */
    public abstract function clearCache();
}
