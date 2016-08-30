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
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
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

                $topGalleryName = $objGallery->getTopGalleryName();
                if ($topGalleryName) {
                    \Env::get('cx')->getPage()->setTitle($topGalleryName);
                    \Env::get('cx')->getPage()->setContentTitle($topGalleryName);
                    \Env::get('cx')->getPage()->setMetaTitle($topGalleryName);
                }

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
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $page_template, $themesPages, $latestImage;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objGalleryHome = new GalleryHomeContent();
                if ($objGalleryHome->checkRandom()) {
                    if (preg_match('/{GALLERY_RANDOM}/', \Env::get('cx')->getPage()->getContent())) {
                        \Env::get('cx')->getPage()->setContent(str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), \Env::get('cx')->getPage()->getContent()));
                    }
                    if (preg_match('/{GALLERY_RANDOM}/', $page_template))  {
                        $page_template = str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), $page_template);
                    }
                    if (preg_match('/{GALLERY_RANDOM}/', $themesPages['index'])) {
                        $themesPages['index'] = str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), $themesPages['index']);
                    }
                    if (preg_match('/{GALLERY_RANDOM}/', $themesPages['sidebar'])) {
                        $themesPages['sidebar'] = str_replace('{GALLERY_RANDOM}', $objGalleryHome->getRandomImage(), $themesPages['sidebar']);
                    }
                }
                if ($objGalleryHome->checkLatest()) {
                    $latestImage = $objGalleryHome->getLastImage();
                    if (preg_match('/{GALLERY_LATEST}/', \Env::get('cx')->getPage()->getContent())) {
                        \Env::get('cx')->getPage()->setContent(str_replace('{GALLERY_LATEST}', $latestImage, \Env::get('cx')->getPage()->getContent()));
                    }
                    if (preg_match('/{GALLERY_LATEST}/', $page_template)) {
                        $page_template = str_replace('{GALLERY_LATEST}', $latestImage, $page_template);
                    }
                    if (preg_match('/{GALLERY_LATEST}/', $themesPages['index'])) {
                        $themesPages['index'] = str_replace('{GALLERY_LATEST}', $latestImage, $themesPages['index']);
                    }
                    if (preg_match('/{GALLERY_LATEST}/', $themesPages['sidebar'])) {
                        $themesPages['sidebar'] = str_replace('{GALLERY_LATEST}', $latestImage, $themesPages['sidebar']);
                    }
                }
                break;
            default:
                break;
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
