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
 * Main controller for Podcast
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_podcast
 */

namespace Cx\Modules\Podcast\Controller;

/**
 * Main controller for Podcast
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_podcast
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
     * Load the component Podcast.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $objTemplate, $_CORELANG;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objPodcast = new Podcast(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objPodcast->getPage($podcastFirstBlock));
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(87, 'static');
                $subMenuTitle = $_CORELANG['TXT_PODCAST'];
                $objPodcast = new PodcastManager();
                $objPodcast->getPage();
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
        global $podcastFirstBlock, $podcastContent, $_CONFIG, $cl, $podcastHomeContentInPageContent, $podcastHomeContentInPageTemplate, $podcastHomeContentInThemesPage, $page_template, $themesPages, $_ARRAYLANG, $objInit, $objPodcast, $podcastBlockPos, $contentPos;
        // get latest podcast entries
        $podcastFirstBlock = false;
        $podcastContent = null;
        if (!empty($_CONFIG['podcastHomeContent'])) {
            /** @ignore */
            if ($cl->loadFile(ASCMS_MODULE_PATH.'/Podcast/Controller/PodcastHomeContent.class.php')) {
                $podcastHomeContentInPageContent = false;
                $podcastHomeContentInPageTemplate = false;
                $podcastHomeContentInThemesPage = false;
                if (strpos(\Env::get('cx')->getPage()->getContent(), '{PODCAST_FILE}') !== false) {
                    $podcastHomeContentInPageContent = true;
                }
                if (strpos($page_template, '{PODCAST_FILE}') !== false) {
                    $podcastHomeContentInPageTemplate = true;
                }
                if (strpos($themesPages['index'], '{PODCAST_FILE}') !== false) {
                    $podcastHomeContentInThemesPage = true;
                }
                if ($podcastHomeContentInPageContent || $podcastHomeContentInPageTemplate || $podcastHomeContentInThemesPage) {
                    $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('Podcast'));
                    $objPodcast = new PodcastHomeContent($themesPages['podcast_content']);
                    $podcastContent = $objPodcast->getContent();
                    if ($podcastHomeContentInPageContent) {
                        \Env::get('cx')->getPage()->setContent(str_replace('{PODCAST_FILE}', $podcastContent, \Env::get('cx')->getPage()->getContent()));
                    }
                    if ($podcastHomeContentInPageTemplate) {
                        $page_template = str_replace('{PODCAST_FILE}', $podcastContent, $page_template);
                    }
                    if ($podcastHomeContentInThemesPage) {
                        $podcastFirstBlock = false;
                        if (strpos($_SERVER['REQUEST_URI'], 'section=Podcast')) {
                            $podcastBlockPos = strpos($themesPages['index'], '{PODCAST_FILE}');
                            $contentPos = strpos($themesPages['index'], '{CONTENT_FILE}');
                            $podcastFirstBlock = $podcastBlockPos < $contentPos ? true : false;
                        }
                        $themesPages['index'] = str_replace('{PODCAST_FILE}',
                        $objPodcast->getContent($podcastFirstBlock), $themesPages['index']);
                    }
                }
            }
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
        $eventListener = new \Cx\Modules\Podcast\Model\Event\PodcastEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('SearchFindContent', $eventListener);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);
    }
}
