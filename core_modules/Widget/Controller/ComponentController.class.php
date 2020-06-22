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
 * Main controller for Widget handler
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Controller;

/**
 * Main controller for Widget handler
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * List of widgets
     *
     * @var array
     */
    protected $widgets = array();

    /**
     * get controller classes
     *
     * @return array
     */
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Do something before content is loaded from DB
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        $template = new \Cx\Core_Modules\Widget\Model\Entity\Sigma('', '', $page);
        $template->setTemplate($page->getContent());
        $this->parseWidgets($template, 'ContentManager', 'Page', $page->getId());
        $page->setContent($template->get());
    }

    /**
     * Do something before a module is loaded
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentParse(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        // We do this here again:
        // This is only triggered for application pages. The template file for
        // application pages is not yet loaded in preContentLoad().
        $template = new \Cx\Core_Modules\Widget\Model\Entity\Sigma('', '', $page);
        $template->setTemplate($page->getContent());
        $this->parseWidgets($template, 'ContentManager', 'Page', $page->getId());
        $page->setContent($template->get());
    }

    /**
     * Do something before main template gets parsed
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\Html\Sigma                       $template   The main template
     */
    public function preFinalize(\Cx\Core\Html\Sigma $template) {
        $this->parseWidgets(
            $template,
            'View',
            'Theme',
            \Env::get('init')->getCurrentThemeId()
        );
    }

    /**
     * Registers a Widget
     *
     * @param \Cx\Core_Modules\Widget\Model\Entity\Widget $widget Widget to add
     */
    public function registerWidget($widget) {
        $this->widgets[$widget->getName()] = $widget;
    }

    /**
     * Parses the widgets on a template
     *
     * @param \HTML_Template_Sigma $template Template to parse widgets into
     * @param string $targetComponent Parse target component name
     * @param string $targetEntity Parse target entity name
     * @param string $targetId Parse target entity ID
     * @param array $excludedWidgets List of widget names that shall not be parsed
     */
    public function parseWidgets($template, $targetComponent, $targetEntity, $targetId, $excludedWidgets = array()) {
        foreach ($this->widgets as $widget) {
            if (in_array($widget->getName(), $excludedWidgets)) {
                continue;
            }
            $widget->parse(
                $template,
                null,
                $targetComponent,
                $targetEntity,
                $targetId,
                $excludedWidgets
            );
        }
    }

    /**
     * Returns the list of registered widgets
     * @return array List of \Cx\Core_Modules\Widget\Entity\Widget objects
     */
    public function getWidgets() {
        return $this->widgets;
    }

    /**
     * Returns a Widget by name
     * @throws \Exception If the requested Widget is not registered
     * @return \Cx\Core_Modules\Widget\Model\Entity\Widget Requested Widget
     */
    public function getWidget($name) {
        if (!isset($this->widgets[$name])) {
            throw new \Exception('No such widget');
        }
        return $this->widgets[$name];
    }

    /**
     * Looks up the template content of a widget
     * @param string $widgetName Name of the widget to get content for
     * @param \Cx\Core\View\Model\Entity\Theme $theme Theme to get Widget content for
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to get Widget content for
     * @param string $targetComponent Parse target component name
     * @param string $targetEntity Parse target entity name
     * @param string $targetId Parse target entity ID
     * @param string $channel Channel identifier
     * @return \Cx\Core\Html\Sigma Widget content as template
     */
    public function getWidgetContent($widgetName, $theme, $page, $targetComponent, $targetEntity, $targetId, $channel) {
        // Since version number is not yet defined (XY), we do not check this yet
        if (false) {//version_compare($theme->getVersionNumber(), 'XY' '>=') {
            // load theme file contents:
            // /themes/<theme>/<widgetComponentType>/<widgetComponentName>/Widget/<widgetName>/<targetComponentName>/<targetEntityName>/<targetId>.html
            return;
        }
        $parseTarget = $this->getParseTarget(
            $targetComponent,
            $targetEntity,
            $targetId
        );
        return $parseTarget->getWidgetContent($widgetName, $theme, $page, $channel);
    }

    /**
     * Returns the parse target entity
     * @param string $componentName Parse target component name
     * @param string $entityName Parse target entity name
     * @param string $entityId Parse target entity id
     * @return \Cx\Model\Base\EntityBase Parse target entity
     */
    protected function getParseTarget($componentName, $entityName, $entityId) {
        if (!isset($this->cache)) {
            $this->cache = array();
        }
        if (
            isset($this->cache[$entityName]) &&
            isset($this->cache[$entityName][$entityId])
        ) {
            return $this->cache[$entityName][$entityId];
        }
        // the following IF block can be dropped as soon as Block is a Doctrine entity
        if ($componentName == 'Block' && $entityName == 'Block') {
            $target = new \Cx\Modules\Block\Model\Entity\Block($entityId);
            $this->cache[$entityName][$entityId] = $target;
            return $target;
        }
        $em = $this->cx->getDb()->getEntityManager();
        $component = $this->getComponent($componentName);
        if (!$component) {
            throw new \Exception('Component not found: "' . $componentName . '"');
        }
        $entityNs = $component->getNamespace() . '\\Model\\Entity\\' . $entityName;
        $findMethod = 'find';
        try {
            $repo = $em->getRepository($entityNs);
        } catch (\Doctrine\Common\Persistence\Mapping\MappingException $e) {
            // This catch block can be dropped as soon as there's a possibility
            // to load non-doctrine entities via EM
            $repoClass = $component->getNamespace() . '\\Model\\Repository\\' . $entityName . 'Repository';
            $repo = new $repoClass();
            $findMethod = 'findById';
        }
        $target = $repo->$findMethod($entityId);
        if (!is_a($target, $this->getNamespace() . '\Model\Entity\WidgetParseTarget')) {
            throw new \Exception('Invalid parse target specified');
        }
        $this->cache[$entityName][$entityId] = $target;
        return $target;
    }

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners()
    {
        $eventListener = new \Cx\Core_Modules\Widget\Model\Event\WidgetEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('clearEsiCache', $eventListener);
    }

    /**
     * Encodes a string so it can be used as an URL argument
     * Currently uses a variant of RFC-4648:
     * Compared to RFC-4648 this replaces "_" by "." in order to
     * allow usage of encoded string in Cloudrexx cache files which are
     * delimited by "_".
     * @param string $string String to encode
     * @return string Encoded string
     */
    public function encode($string) {
        return strtr(base64_encode($string), '+/', '-.');
    }

    /**
     * Decodes a string which was encoded using $this->encode()
     * Currently uses a variant of RFC-4648:
     * Compared to RFC-4648 this replaces "_" by "." in order to
     * allow usage of encoded string in Cloudrexx cache files which are
     * delimited by "_".
     * @param string $string String to decode
     * @return string Decoded string
     */
    public function decode($string) {
        return base64_decode(strtr($string, '-.', '+/'));
    }
}
