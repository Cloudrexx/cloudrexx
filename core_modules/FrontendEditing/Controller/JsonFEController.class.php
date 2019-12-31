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
 * Class FrontendController
 *
 * This is the frontend controller for the frontend editing.
 * This adds the necessary javascripts and toolbars
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_frontendediting
 * @version     1.0.0
 */

namespace Cx\Core_Modules\FrontendEditing\Controller;

/**
 * Class FrontendController
 *
 * This is the frontend controller for the frontend editing.
 * This adds the necessary javascripts and toolbars
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_frontendediting
 * @version     1.0.0
 */
class JsonFEController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter
{
    public function getName() {
        return 'FE';
    }
    
    public function getMessagesAsString() {
        return '';
    }
    
    public function getAccessableMethods() {
        return array(
            'getToggleButton' => new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), false),
        );
    }
    
    public function getDefaultPermissions() {
        return null;
    }
    
    public function getToggleButton($params) {
        if (
            !$this->frontendEditingIsActive(true, false) ||
            !isset($params['get']) ||
            !isset($params['get']['lang'])
        ) {
            return array('content' => '');
        }
        // load FE lang vars
        $lang = \Env::get('init')->getComponentSpecificLanguageData(
            parent::getName(),
            true,
            \FWLanguage::getLanguageIdByCode($params['get']['lang'])
        );
        // load ToolbarCache.html template
        $template = new \Cx\Core\Html\Sigma($this->getDirectory(false) . '/View/Template/Generic');
        $template->loadTemplateFile('ToolbarCache.html');
        // parse template
        $template->setVariable($lang);
        return array('content' => $template->get());
    }
}
