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
 * Main controller for Data
 *
 * @copyright  cloudrexx
 * @author     Project Team SS4U <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_data
 */

namespace Cx\Modules\Data\Controller;

/**
 * Main controller for Data
 *
 * @copyright  cloudrexx
 * @author     Project Team SS4U <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_data
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * getControllerClasses
     *
     * @return type
     */
    public function getControllerClasses() {
        return array();
    }

     /**
     * Load the component Data.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $objTemplate, $_CORELANG;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objData = new \Cx\Modules\Data\Controller\Data(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objData->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(146, 'static'); // ID !!
                $subMenuTitle = $_CORELANG['TXT_DATA_MODULE'];
                $objData = new \Cx\Modules\Data\Controller\DataAdmin();
                $objData->getPage();
                break;

            default:
                break;
        }
    }

    /**
    * Do something before content is loaded from DB
    *
    * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
    */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CONFIG, $cl, $lang, $objInit, $dataBlocks, $lang, $dataBlocks, $themesPages, $page_template;
        // Initialize counter and track search engine robot
        \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');

        if (\Cx\Core\Setting\Controller\Setting::getValue('dataUseModule') && $cl->loadFile(ASCMS_MODULE_PATH.'/Data/Controller/DataBlocks.class.php')) {
            $lang = $objInit->loadLanguageData('Data');
            $dataBlocks = new \Cx\Modules\Data\Controller\DataBlocks($lang);
            \Env::get('cx')->getPage()->setContent($dataBlocks->replace(\Env::get('cx')->getPage()->getContent()));
            $themesPages = $dataBlocks->replace($themesPages);
            $page_template = $dataBlocks->replace($page_template);
        }

    }

}
