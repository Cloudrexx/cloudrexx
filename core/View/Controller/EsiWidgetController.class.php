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

namespace Cx\Core\View\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_view
 */
class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController {
    /**
     * Parses a widget
     * @param string $name Widget name
     * @param \Cx\Core\Html\Sigma Widget template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Current response
     * @param array $params Array of params
     */
    public function parseWidget($name, $template, $response, $params) {
        if (!isset($params['ref'])) {
            throw new \InvalidArgumentException('Missing GET argument "ref"');
        }
        switch ($name) {
            case 'STANDARD_URL':
            case 'MOBILE_URL':
            case 'PRINT_URL':
            case 'PDF_URL':
            case 'APP_URL':
                $active = 1;
                $view = strtolower(substr($name, 0, -4));
                if ($view == 'standard' || $view == 'mobile') {
                    if ($view == 'standard') {
                        $active = 0;
                    }
                    $view = 'smallscreen';
                } else {
                    $view .= 'view';
                }
                $url = new \Cx\Core\Routing\Url($params['ref']);
                $url->setParam($view, $active);
                $template->setVariable(
                    $name,
                    contrexx_raw2xhtml($url)
                );
                break;
        }
    }
}
