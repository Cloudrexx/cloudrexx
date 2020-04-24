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
 * Represents a template widget that displays the same string everytime
 * Usage:
 * ```php
 * $this->getComponent('Widget')->registerWidget(
 *     new \Cx\Core_Modules\Widget\Model\Entity\FinalStringWidget(
 *         $this->getSystemComponentController(),
 *         'FOO',
 *         'bar'
 *     )
 * );
 * ```
 * The above example replaces Sigma placeholder "FOO" by string "bar"
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
class FinalStringWidget extends Widget {

    /**
     * String to display
     * @var string
     */
    protected $string;

    /**
     * Instanciates a new widget
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $component Component registering this widget
     * @param string $name Name of this widget
     * @param string $string String to display
     */
    public function __construct($component, $name, $string) {
        parent::__construct($component, $name);
        $this->string = $string;
    }

    /**
     * Returns the string this widget displays
     * @return string String this widget displays
     */
    public function getString() {
        return $this->string;
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
    public function internalParse($template, $response, $targetComponent, $targetEntity, $targetId, $params = array()) {
        return $this->getString();
    }

    /**
     * Clears all cache files for this Widget (if any)
     * Since FinalStringWidget is uncached, there's nothing to do here
     */
    public function clearCache() {}
}
