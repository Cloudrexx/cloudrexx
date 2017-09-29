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
 * This is the controllers for the component
 *
 * @copyright   Cloudrexx AG
 * @author      Sebastian Brand <sebastian.brand@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 * @version     1.0.0
 */

namespace Cx\Core\Wysiwyg\Controller;

use Cx\Core\Wysiwyg\Model\Event\WysiwygEventListener;

/**
 * This is the main controller for the component
 *
 * @copyright   Cloudrexx AG
 * @author      Sebastian Brand <sebastian.brand@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 * @version     1.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController implements \Cx\Core\Event\Model\Entity\EventListener {

    /**
     * Add the event listener
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        $evm = \Cx\Core\Core\Controller\Cx::instanciate()->getEvents();
        $evm->addEventListener('wysiwygCssReload', $this);
    }

    /**
     * This function controlls the events from the eventListener
     *
     * @param string $eventName Name of the event
     * @param array $eventArgs Arguments of the event
     */
    public function onEvent($eventName, array $eventArgs) {
        switch ($eventName) {
            case 'wysiwygCssReload':
                $skinId = $eventArgs[0]['skin'];
                $result = $eventArgs[1];

                foreach ($this->getCustomCSSVariables($skinId) as $key => $val) {
                    $result[$key] = $val;
                }
                break;
            default:
                break;
        }
    }

    /**
     * Returns all Controller class names for this component (except this)
     *
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('Backend', 'Toolbar');
    }

    /**
     * find all wysiwyg templates and return it in the correct format for the ckeditor
     *
     * @return json All wysiwyg templates where active in json format
     */
    public function getWysiwygTempaltes() {
        $em = $this->cx->getDb()->getEntityManager();
        $repo = $em->getRepository('Cx\Core\Wysiwyg\Model\Entity\WysiwygTemplate');
        $allWysiwyg = $repo->findBy(array('active'=>'1'));
        $containerArr = array();
        foreach ($allWysiwyg as $wysiwyg) {
            $containerArr[] = array(
                'title' => $wysiwyg->getTitle(),
                'image' => $wysiwyg->getImagePath(),
                'description' => $wysiwyg->getDescription(),
                'html' => $wysiwyg->getHtmlContent(),
            );
        }

        return json_encode($containerArr);
    }


    /**
     * find all custom css variables and return an array with the values
     *
     * @param integer $skinId skin id, default is 0
     * @return array List with needed wysiwyg options
     */
    public function getCustomCSSVariables($skinId) {
        $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
        $skin = '';
        $content = '';
        $cssArr = array();
        $ymlOption = array();
        $componentData = array();
        \Cx\Core\Setting\Controller\Setting::init('Wysiwyg', 'config', 'Yaml');
        if (!\Cx\Core\Setting\Controller\Setting::isDefined('specificStylesheet')
            && !\Cx\Core\Setting\Controller\Setting::add('specificStylesheet', '0', ++$i, \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1', 'config')
        ){
            throw new \Exception("Failed to add new configuration option");
        }
        if (!\Cx\Core\Setting\Controller\Setting::isDefined('replaceActualContents')
            && !\Cx\Core\Setting\Controller\Setting::add('replaceActualContents', '0', ++$i, \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1', 'config')
        ){
            throw new \Exception("Failed to add new configuration option");
        }

        //0 is default theme so you dont must change the themefolder
        if(!empty($skinId)){
            $skin = $themeRepo->findById($skinId)->getFoldername();
            $componentData = $themeRepo->findById($skinId)->getComponentData();
        } else {
            $skin = $themeRepo->getDefaultTheme()->getFoldername();
            $componentData = $themeRepo->getDefaultTheme()->getComponentData();
        }

        if(\Cx\Core\Setting\Controller\Setting::getValue('specificStylesheet','Wysiwyg')){
            $path = $this->cx->getClassLoader()->getFilePath($this->cx->getCodeBaseThemesPath() . '/' . $skin . '/index.html');
            if ($path) {
                $content = file_get_contents($path);
                $cssArr = \JS::findCSS($content);
            }
        }

        if(!empty($componentData['rendering']['wysiwyg'])){
            $ymlOption = $componentData['rendering']['wysiwyg'];
        }

        if (!empty($ymlOption['css'])) {
            if ($this->cx->getClassLoader()->getFilePath($this->cx->getCodeBaseThemesPath() . '/' . $skin . '/' . $ymlOption['css'])) {
                $cssArr[] = $this->cx->getWebsiteOffsetPath() . '/' . $skin . '/' . $ymlOption['css'];
            }
        }

        return array(
            'css' => $cssArr,
            'bodyClass' => !empty($ymlOption['bodyClass'])?$ymlOption['bodyClass']:'',
            'bodyId' => !empty($ymlOption['bodyId'])?$ymlOption['bodyId']:'',
        );
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
    public function registerEventListeners() {
        $eventListener = new WysiwygEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);
    }

    /**
     * Get the Toolbar of the given type
     *
     * Returns the toolbar of the desired based on the restricted of functions 
     * according to user group and default setting
     * @param string    $type   Type of desired Toolbar (one of the following:
     *                          small, full, frontendEditingContent,
     *                          frontendEditingTitle or bbcode)
     * @return string           Toolbar of the desired type based on the
     *                          restrictions according to user group and default
     *                          setting
     */
    public function getToolbar($type = 'Full') {
        $toolbarController = $this->getController('Toolbar');
        return $toolbarController->getToolbar($type);
    }

    /**
     * Get the buttons that shall be removed or unchecked
     * @return string
     * @internal param bool|false $buttonsOnly If set, returns only the buttons
     *                                      no config.removedButtons prefix
     * @internal param bool|false $isAccess If set, removes the prefix
     *                                      config.removedButtons from the string
     */
    public function getRemovedButtons() {
        $toolbarController = $this->getController('Toolbar');
        $buttons = $toolbarController->getRemovedButtons();
        return $buttons;
    }
}
