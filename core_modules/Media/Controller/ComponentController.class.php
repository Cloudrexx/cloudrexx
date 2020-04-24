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
 * Main controller for Media
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_media
 */

namespace Cx\Core_Modules\Media\Controller;

/**
 * Main controller for Media
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_media
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
        global $_CORELANG, $subMenuTitle, $objTemplate, $plainSection;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objMedia = new Media(\Env::get('cx')->getPage()->getContent(), $plainSection.MODULE_INDEX);
                \Env::get('cx')->getPage()->setContent($objMedia->getMediaPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                $subMenuTitle = $_CORELANG['TXT_MEDIA_MANAGER'];
                $objMedia = new MediaManager();
                $objMedia->getMediaPage();
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
        $evm = $this->cx->getEvents();
        $eventListener = new \Cx\Core_Modules\Media\Model\Event\MediaEventListener($this->cx);
        $evm->addEventListener('SearchFindContent',$eventListener);
        $evm->addEventListener('mediasource.load', $eventListener);
    }

    /**
     * Find Media files by keyword $searchTerm and return them in a
     * two-dimensional array compatible to be used by Search component.
     *
     * @param   string  $searchTerm The keyword to search by
     * @return  array   Two-dimensional array of Media files found by keyword
     *                  $searchTerm.
     */
    public function getMediaForSearchComponent($searchTerm) {

        $media = new MediaLibrary();
        $settings = $media->createSettingsArray();
        $em = \Env::get('cx')->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $paths = array(
            'Media1' => $this->cx->getWebsitePath() . ASCMS_MEDIA1_WEB_PATH . '/',
            'Media2' => $this->cx->getWebsitePath() . ASCMS_MEDIA2_WEB_PATH . '/',
            'Media3' => $this->cx->getWebsitePath() . ASCMS_MEDIA3_WEB_PATH . '/',
            'Media4' => $this->cx->getWebsitePath() . ASCMS_MEDIA4_WEB_PATH . '/'
        );
        $result = array();
        foreach ($paths as $archive => $path) {
            // skip if search functionality is not active
            $settingKey    = strtolower($archive) . '_frontend_search';
            if (
                !isset($settings[$settingKey]) ||
                $settings[$settingKey] != 'on'
            ) {
                continue;
            }

            // only list results in case the associated page of the module is active
            $page = $pageRepo->findOneBy(array(
                'module' => $archive,
                'lang'   => FRONTEND_LANG_ID,
                'type'   => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
            ));

            // skip in case no associated application page does exist or is
            // published
            if (!$page || !$page->isActive()) {
                continue;
            }

            \Cx\Core\Setting\Controller\Setting::init('Config', 'site','Yaml');
            $coreListProtectedPages   = \Cx\Core\Setting\Controller\Setting::getValue('coreListProtectedPages','Config');
            $searchVisibleContentOnly = \Cx\Core\Setting\Controller\Setting::getValue('searchVisibleContentOnly','Config');

            // skip if the application page is invisible
            if (
                $searchVisibleContentOnly == 'on' &&
                !$page->isVisible()
            ) {
                continue;
            }

            // skip if the application page is protected
            if (
                $coreListProtectedPages == 'off' &&
                $page->isFrontendProtected() &&
                $this->getComponent('Session')->getSession() &&
                !\Permission::checkAccess($page->getFrontendAccessId(), 'dynamic', true)
            ) {
                continue;
            }

            $data = array();
            $media->getDirectoryTree($path, $searchTerm, $data, true);
            if (empty($data['file']['name'])) {
                continue;
            }

            foreach ($data['file']['name'] as $idx => $name) {
                if (MediaLibrary::isIllegalFileName($name)) {
                    continue;
                }

                $mediaPath    = $data['file']['path'][$idx] .'/';
                $mediaWebPath = $mediaPath;
                \Cx\Lib\FileSystem\FileSystem::path_relative_to_root($mediaWebPath);
                $mediaWebPath = '/'. $mediaWebPath; // Filesysystem removes the beginning slash(/)

                $url = \Cx\Core\Routing\Url::fromPage($page, array(
                    'path' => $mediaWebPath,
                    'act' => 'download',
                    'file' => $name,
                ));
                if (!$url) {
                    continue;
                }
                $link = $url->toString(false);

                $result[] = array(
                    'Score'     => 100,
                    'Title'     => $name,
                    'Content'   => '',
                    'Link'      => $link,
                    'Component' => $this->getName(),
                );
            }
        }

        return $result;
    }
}
