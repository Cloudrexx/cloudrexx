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
 * JsonAdapter Controller to handle EsiWidgets
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
abstract class EsiWidgetController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter {

    /**
     * Returns the internal name used as identifier for this adapter
     * @see \Cx\Core\Json\JsonAdapter::getName()
     * @return string Name of this adapter
     */
    public function getName() {
        return parent::getName() . 'Widget';
    }

    /**
     * Returns all messages as string
     * @see \Cx\Core\Json\JsonAdapter::getMessagesAsString()
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return '';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @see \Cx\Core\Json\JsonAdapter::getMessagesAsString()
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array(
            'getWidget',
        );
    }

    /**
     * Returns default permission as object
     * @see \Cx\Core\Json\JsonAdapter::getMessagesAsString()
     * @return Cx\Core_Modules\Access\Model\Entity\Permission Required permission
     */
    public function getDefaultPermissions() {
        return new \Cx\Core_Modules\Access\Model\Entity\Permission(null, null, false);
    }

    /**
     * Returns the content of a widget
     * @param array $params JsonAdapter parameters
     * @return array Content in an associative array
     */
    public function getWidget($params) {
        $requiredParams = array(
            'theme',
            'page',
            'lang',
            'name',
            'targetComponent',
            'targetEntity',
            'targetId',
            'channel',
        );
        if (isset($params['get'])) {
            $params['get'] = contrexx_input2raw($params['get']);
        } else {
            $params['get'] = array();
        }
        foreach ($requiredParams as $requiredParam) {
            if (!isset($params['get'][$requiredParam])) {
                throw new \InvalidArgumentException('Param "' . $requiredParam . '" not set');
            }
        }

        // ensure that the params can be fetched during internal parsing
        $backupGetParams = $_GET;
        $_GET = $params['get'];

        // resolve widget template
        $widgetContent = '';
        $widget = $this->getComponent('Widget')->getWidget($params['get']['name']);
        if (!$widget->hasContent()) {
            $widgetContent = '{' . $params['get']['name'] . '}';
        } else {
            $widgetTemplate = $this->getComponent('Widget')->getWidgetContent(
                $params['get']['name'],
                $params['get']['theme'],
                $params['get']['page'],
                $params['get']['targetComponent'],
                $params['get']['targetEntity'],
                $params['get']['targetId'],
                $params['get']['channel']
            );
            if ($widgetTemplate->blockExists($params['get']['name'])) {
                $widgetContent = $widgetTemplate->getUnparsedBlock(
                    $params['get']['name']
                );
            }
        }
        $widgetTemplate = new \Cx\Core\Html\Sigma();
        \LinkGenerator::parseTemplate($widgetContent);
        $this->cx->parseGlobalPlaceholders($widgetContent);
        $widgetTemplate->setTemplate($widgetContent);
        $this->getComponent('Widget')->parseWidgets(
            $widgetTemplate,
            $params['get']['targetComponent'],
            $params['get']['targetEntity'],
            $params['get']['targetId'],
            array($params['get']['name'])
        );
        $this->parseWidget(
            $params['get']['name'],
            $widgetTemplate,
            $params['get']['lang']
        );
        $_GET = $backupGetParams;
        $content = $widgetTemplate->get();
        $ls = new \LinkSanitizer(
            $this->cx,
            $this->cx->getWebsiteOffsetPath() . \Env::get('virtualLanguageDirectory') . '/',
            $content
        );
        return array(
            'content' => $ls->replace(),
        );
    }

    /**
     * Parses a widget
     * @param string $name Widget name
     * @param \Cx\Core\Html\Sigma Widget template
     * @param string $locale RFC 3066 locale identifier
     */
    public abstract function parseWidget($name, $template, $locale);
}
