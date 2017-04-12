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
 * Main controller for Gallery
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_gallery
 */

namespace Cx\Modules\Gallery\Controller;

/**
 * Main controller for Gallery
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_gallery
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * {@inheritdoc}
     */
    public function getControllerClasses()
    {
        return array('EsiWidget');
    }

    /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController');
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
                $objGallery = new Gallery(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objGallery->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();
                \Permission::checkAccess(12, 'static');
                $subMenuTitle = $_CORELANG['TXT_GALLERY_TITLE'];
                $objGalleryManager = new GalleryManager();
                $objGalleryManager->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $widgetController = $this->getComponent('Widget');

        foreach (array('GALLERY_LATEST', 'GALLERY_RANDOM') as $widgetName) {
            if ($widgetName === 'GALLERY_RANDOM') {
                $gallery     = new GalleryHomeContent();

                // @todo: Replace the following IF as soon as a widget can be
                // set inactive
                if (!$gallery->checkRandom()) {
                    continue;
                }
                $pictureIds = $gallery->getPictureIdsForRandomizer();
                $randomParams = array();
                foreach ($pictureIds as $picId) {
                    $randomParams[] = array(
                        'picId' => $picId,
                    );
                }
                $widget = new \Cx\Core_Modules\Widget\Model\Entity\RandomEsiWidget(
                    $this,
                    $widgetName,
                    $randomParams
                );
            } else {
                $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                    $this,
                    $widgetName
                );
            }
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL
            );
            $widgetController->registerWidget($widget);
        }
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
        $eventListener = new \Cx\Modules\Gallery\Model\Event\GalleryEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('SearchFindContent', $eventListener);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);
    }

}
