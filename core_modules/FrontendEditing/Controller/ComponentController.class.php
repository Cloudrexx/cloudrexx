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
 * Class ComponentController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_frontendediting
 * @version     1.0.0
 */

namespace Cx\Core_Modules\FrontendEditing\Controller;

/**
 * Class ComponentController
 *
 * The main controller for the frontend editing
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_frontendediting
 * @version     1.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController
{

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('Frontend', 'JsonFE');
    }
    
    public function getControllersAccessableByJson() {
        return array('JsonFEController');
    }

    /**
     * Checks whether the frontend editing is active or not
     *
     * The frontend editing is deactivated for application pages except the home page
     *
     * @return bool
     */
    public function frontendEditingIsActive($checkPermission = true, $checkMode = true)
    {
        global $_CONFIG;

        // check frontend editing status in settings and don't show frontend editing on mobile phone
        if (
            \Cx\Core\Setting\Controller\Setting::getValue(
                'frontendEditingStatus',
                'Config'
            ) != 'on'
        ) {
            return false;
        }

        // get current request`s parameters
        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();

        if ($checkMode) {
            // check whether the cloudrexx is in frontend mode, a content page exists and it is no pagePreview
            if (
                $this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND ||
                !$this->cx->getPage() ||
                isset($requestParams['pagePreview'])
            ) {
                return false;
            }
        }

        if (!$checkPermission) {
            return true;
        }

        // check permission
        // if the user don't have permission to edit pages or edit blocks,
        // disable the frontend editing
        if ($this->userHasPermissionToEditPage($checkMode) ||
            $this->userHasPermissionToEditBlocks()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check the permission for editing pages
     *
     * @return boolean TRUE if the user has permission to edit pages
     */
    protected function userHasPermissionToEditPage($checkMode = true) {
        return  \Permission::checkAccess(6, 'static', true) &&
                \Permission::checkAccess(35, 'static', true) &&
                (
                    !$checkMode ||
                    (
                        !$this->cx->getPage()->isBackendProtected() ||
                        \Permission::checkAccess(
                            $this->cx->getPage()->getBackendAccessId(),
                            'dynamic',
                            true
                        )
                    )
               );
    }

    /**
     * Check the permission to edit blocks
     *
     * @return boolean TRUE if the user has permission to edit blocks
     */
    protected function userHasPermissionToEditBlocks() {
        return \Permission::checkAccess(76, 'static', true);
    }

    /**
     * Make the block editable, add the necessary html containers
     * for the frontend editing
     *
     * @param integer $id the id of the block
     * @param string $output the html output of the block
     */
    public function prepareBlock($id, &$output) {
        if (!$this->frontendEditingIsActive() ||
            !$this->userHasPermissionToEditBlocks()) {
            return;
        }
        $output = '<div class="fe_block" id="fe_block_' . $id . '" data-id="' . $id . '" contenteditable="false">' . $output . '</div>';
    }

    /**
     * Add the necessary divs for the inline editing around the content and around the title
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        // Is frontend editing active?
        if (
            !$this->frontendEditingIsActive() ||
            !$this->userHasPermissionToEditPage()
        ) {
            return;
        }

        $componentTemplate = new \Cx\Core\Html\Sigma(
            $this->cx->getCodeBaseCoreModulePath() . '/' . $this->getName() .
            '/View/Template/Generic'
        );
        $componentTemplate->setErrorHandling(PEAR_ERROR_DIE);

        // add div around content
        // not used at the moment, because we have no proper way to "not parse" blocks in content and
        // it should only print a div around the content without parsing the content at this time
//        $componentTemplate->loadTemplateFile('ContentDiv.html');
//        $componentTemplate->setVariable('CONTENT', $page->getContent());
//        $page->setContent($componentTemplate->get());
        $page->setContent('<div id="fe_content">' . $page->getContent() . '</div>');
    }

    /**
     * When the frontend editing is active for this page init the frontend editing
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function preFinalize(\Cx\Core\Html\Sigma $template)
    {
        // Is frontend editing active?
        if (
            !$this->frontendEditingIsActive() ||
            !isset($_COOKIE['fe_toolbar']) ||
            $_COOKIE['fe_toolbar'] != 'true'
        ) {
            if ($this->frontendEditingIsActive(false)) {
                // if all that makes FE inactive is the login, then parse ESI!
                $template->_blocks['__global__'] = preg_replace(
                    '/<body[^>]*>/',
                    '\\0' . $this->getComponent('Cache')->getEsiContent(
                        'FE',
                        'getToggleButton',
                        array(
                            'user' => '$(HTTP_COOKIE{\'PHPSESSID\'})',
                            'lang' => \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
                        )
                    ),
                    $template->_blocks['__global__']
                );
                return;
            }
            return;
        }

        $frontendEditing = new \Cx\Core_Modules\FrontendEditing\Controller\FrontendController($this, $this->cx);
        $frontendEditing->initFrontendEditing($this);
    }

    /**
     * {@inheritdoc}
     */
    public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        // Is frontend editing active?
        if (
            !$this->frontendEditingIsActive() ||
            !$this->userHasPermissionToEditPage()
        ) {
            return;
        }

        $page              = $response->getPage();
        $componentTemplate = new \Cx\Core\Html\Sigma(
            $this->cx->getCodeBaseCoreModulePath() . '/' . $this->getName() .
            '/View/Template/Generic'
        );
        $componentTemplate->setErrorHandling(PEAR_ERROR_DIE);
        // add div around the title
        $componentTemplate->loadTemplateFile('TitleDiv.html');
        $componentTemplate->setVariable('TITLE', $page->getContentTitle());
        $page->setContentTitle($componentTemplate->get());
    }
}
