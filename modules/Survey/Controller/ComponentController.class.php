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
 * Main controller for Survey
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_survey
 */

namespace Cx\Modules\Survey\Controller;

/**
 * Main controller for Survey
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_survey
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    public function getControllersAccessableByJson() {
        return array('JsonSurvey');
    }

    /**
     * {@inheritdoc}
     */
    public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        $page   = $response->getPage();
        if (!$page ||
            $page->getModule() !== $this->getName() ||
            !in_array(
                $page->getCmd(),
                array(
                    '',
                    'surveybyId',
                    'surveypreview',
                    'questionpreview',
                    'homesurvey'
                )
            )
        ) {
            return;
        }

        $survey = new Survey('');
        $title  = $survey->getPageTitle();
        if (!$title) {
            return;
        }
        $page->setTitle($title);
        $page->setMetatitle($title);
    }
     /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $subMenuTitle, $objTemplate;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objSurvey = new Survey(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objSurvey->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(111, 'static');
                $subMenuTitle = $_CORELANG['TXT_SURVEY'];
                $objSurvey = new SurveyManager();
                $objSurvey->getPage();
                break;

            default:
                break;
        }
    }
}
