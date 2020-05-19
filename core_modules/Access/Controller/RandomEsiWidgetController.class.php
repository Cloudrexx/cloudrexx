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

namespace Cx\Core_Modules\Access\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
class RandomEsiWidgetController extends \Cx\Core_Modules\Widget\Controller\RandomEsiWidgetController {

    /**
     * Returns a list of ESI request infos that are to be randomized
     *
     * Each returned entry consists of an array like:
     * array(
     *     <adapterName>,
     *     <adapterMethod>,
     *     <params>,
     * )
     * @param \Cx\Core_Modules\Widget\Model\Entity\Widget $widget The RandomEsiWidget
     * @param array $params ESI request params
     * @param \Cx\Core\Html\Sigma Widget template
     * @return array List of URLs
     */
    public function getRandomEsiWidgetContentInfos($widget, $params, $template) {
        $userIds = array();

        // filter active users
        $filter = array('active' => true);
        
        // filter users by group association
        $groupFilter = AccessBlocks::fetchGroupFilter($template, $widget->getName());
        if ($groupFilter) {
            $filter['group_id'] = $groupFilter;
        }

        // fetch users
        $objUser = \FWUser::getFWUserObject()->objUser->getUsers($filter);

        if (!$objUser) {
            \DBG::msg(__METHOD__ . ': failed to fetch users');
            return array();
        }

        while (!$objUser->EOF) {
            $userIds[] = $objUser->getId();
            $objUser->next();
        }
        
        // foreach user, get ESI infos:
        $esiInfos = array();
        foreach ($userIds as $userId) {
            // adapter name, adapter method, params
            $esiInfos[] = array(
                $this->getName(),
                'getWidget',
                array(
                    'name' => 'access_user',
                    'id' => $userId,
                    'page' => $params['get']['page'],
                    'theme' => $params['get']['theme'],
                    'channel' => $params['get']['channel'],
                    'targetComponent' => $params['get']['targetComponent'],
                    'targetEntity' => $params['get']['targetEntity'],
                    'targetId' => $params['get']['targetId'],
                ),
            );
        }
        return $esiInfos;
    }

    /**
     * Parses a widget
     * @param string $name Widget name
     * @param \Cx\Core\Html\Sigma Widget template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Current response
     * @param array $params Array of params
     */
    public function parseWidget($name, $template, $response, $params) {
        if ($name == 'access_user') {
            // get origin template and parse it instead
            $subTemplate = $this->getComponent('Widget')->getWidgetContent(
                'access_random_users',
                $params['theme'],
                $params['page'],
                $params['targetComponent'],
                $params['targetEntity'],
                $params['targetId'],
                $params['channel']
            );

            $objUser = \FWUser::getFWUserObject()->objUser->getUser($params['id']);

            $objAccessBlocks = new AccessBlocks($subTemplate);
            $objAccessBlocks->parseBasePlaceholders($objUser);

            $template->setVariable($name, $subTemplate->get());
        }
    }
}
