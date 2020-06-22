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
     * @var string Widget is a simple placeholder
     */
    const TYPE_PLACEHOLDER = 'placeholder';

    /**
     * @var string Widget is a block that can be parsed
     */
    const TYPE_BLOCK = 'block';

    /**
     * @var string Widget is a placeholder that has params and triggers a callback
     */
    const TYPE_CALLBACK = 'callback';

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
     * Whether this widget represents a template block, placeholder or callback
     * @var string
     */
    protected $type;

    /**
     * Custom parse target to use for sub-widgets
     * @var \Cx\Core_Modules\Widget\Model\Entity\WidgetParseTarget
     */
    protected $customParseTarget = null;

    /**
     * Instanciates a new widget
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $component Component registering this widget
     * @param string $name Name of this widget
     * @param string $type (optional) Whether this widget represents a template placeholder, block or callback, default: placeholder
     */
    public function __construct($component, $name, $type = self::TYPE_PLACEHOLDER) {
        $this->component = $component;
        $this->name = $name;
        $this->type = $type;
        if ($this->getType() == static::TYPE_CALLBACK) {
            \Cx\Core\Html\Sigma::addCallbackPlaceholder(
                strtolower($this->getName()),
                function() {
                    $args = func_get_args();
                    $template = array_shift($args);
                    if (!$template) {
                        throw new \Exception('Wrong argument list for callback for widget "' . $this->getName() . '"');
                    }
                    if (!$template->getParseTarget()) {
                        throw new \Exception('In order to use widgets of type "callback" you need to set a parse target to your Sigma template');
                    }
                    // Since we parse callback Widgets as placeholders we
                    // need to supply an appropriate template
                    $parseTemplate = new \Cx\Core\Html\Sigma();
                    $parseTemplate->setTemplate('{' . $this->getName() . '}');
                    $this->parse(
                        $parseTemplate,
                        $this->cx->getResponse(),
                        $template->getParseTarget()->getSystemComponent()->getName(),
                        get_class($template->getParseTarget()),
                        $template->getParseTarget()->getId(),
                        $args
                    );
                    return $parseTemplate->get();
                }
            );
        }
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
        if ($this->getType() == static::TYPE_BLOCK) {
            return strtolower($this->name);
        }
        return strtoupper($this->name);
    }

    /**
     * Returns whether this widget represents a template placeholder, block or callback
     * @return string $type Whether this widget represents a template placeholder, block or callback
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Parses this widget into $template
     * Depending on the type, the passed params and return value are different:
     * TYPE_CALLBACK:
     *  $params are the callback's params
     *  $excludeWidgets is always empty
     *  return value is the content of this widget
     * TYPE_PLACEHOLDER:
     *  $params are always empty
     *  $excludedWidgets is the list of Widgets we already recursed through
     *  return value is the content of this widget
     * TYPE_BLOCK
     *  $params are always empty
     *  $excludedWidgets is the list of Widgets we already recursed through
     *  return value is empty string (/unused)
     * @param \HTML_Template_Sigma $template Template to parse this widget into
     * @param \Cx\Core\Routing\Model\Entity\Reponse $response Current response object
     * @param string $targetComponent Parse target component name
     * @param string $targetEntity Parse target entity name
     * @param string $targetId Parse target entity ID
     * @param array $params (optional) List of params for widgets of type 'callback'
     * @param array $excludedWidgets (optional) List of widget names that shall not be parsed
     */
    public function parse($template, $response, $targetComponent, $targetEntity, $targetId, $arguments = array(), $excludedWidgets = array()) {
        // Disable parsing of widgets in backend pending further notice
        // See CLX-1674
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            return;
        }

        switch ($this->getType()) {
            case static::TYPE_CALLBACK:
            case static::TYPE_PLACEHOLDER:
                if (!$template->placeholderExists($this->getName())) {
                    return;
                }
                $content = $this->internalParse(
                    $template,
                    $response,
                    $targetComponent,
                    $targetEntity,
                    $targetId,
                    $arguments
                );
                \LinkGenerator::parseTemplate($content);
                $template->setVariable(
                    $this->getName(),
                    $content
                );
                break;
            case static::TYPE_BLOCK:
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
                break;
            default:
                throw new \Exception('No such widget type for widget "' . $this->getName() . '" of component "' . $this->getRegisteringComponent()->getName() . '"');
                break;
        }
    }

    /**
     * Tells whether this widget has a custom parse target
     * @return boolean True if this widget has a custom parse target
     */
    public function hasCustomParseTarget() {
        return $this->customParseTarget != null;
    }

    /**
     * Returns this widget's custom parse target
     * @return WidgetParseTarget Widget parse target
     */
    public function getCustomParseTarget() {
        return $this->customParseTarget;
    }

    /**
     * Sets this widget's parse target
     * @param WidgetParseTarget $parseTarget Widget parse target for subwidgets
     */
    public function setCustomParseTarget($parseTarget) {
        $this->customParseTarget = $parseTarget;
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
     * @param array $params (optional) List of params for widgets of type 'callback'
     * @return string Replacement for widgets without content, NULL otherwise
     */
    public abstract function internalParse($template, $response, $targetComponent, $targetEntity, $targetId, $params = array());

    /**
     * Clears all cache files for this Widget (if any)
     */
    public abstract function clearCache();
}
