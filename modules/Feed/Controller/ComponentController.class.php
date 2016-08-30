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
 * Main controller for Feed
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_feed
 */

namespace Cx\Modules\Feed\Controller;

/**
 * Main controller for Feed
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_feed
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
                $objFeed = new Feed(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objFeed->getFeedPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:

                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(27, 'static');
                $subMenuTitle = $_CORELANG['TXT_NEWS_SYNDICATION'];
                $objFeed = new FeedManager();
                $objFeed->getFeedPage();
                break;

            default:
                break;
        }
    }

    /*
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page The resolved page
     */

    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {

        global $_CONFIG, $objNewsML, $arrMatches, $page_template, $themesPages, $cl;

        // Set NewsML messages
        if ($_CONFIG['feedNewsMLStatus'] == '1') {
            if (preg_match_all('/{NEWSML_([0-9A-Z_-]+)}/', \Env::get('cx')->getPage()->getContent(), $arrMatches)) {
                /** @ignore */
                if ($cl->loadFile(\Env::get('cx')->getCodeBaseModulePath() . '/Feed/Controller/NewsML.class.php')) {
                    $objNewsML = new NewsML();
                    $objNewsML->setNews($arrMatches[1], \Env::get('cx')->getPage()->getContent());
                }
            }
            if (preg_match_all('/{NEWSML_([0-9A-Z_-]+)}/', $page_template, $arrMatches)) {
                /** @ignore */
                if ($cl->loadFile(\Env::get('cx')->getCodeBaseModulePath() . '/Feed/Controller/NewsML.class.php')) {
                    $objNewsML = new NewsML();
                    $objNewsML->setNews($arrMatches[1], $page_template);
                }
            }
            if (preg_match_all('/{NEWSML_([0-9A-Z_-]+)}/', $themesPages['index'], $arrMatches)) {
                /** @ignore */
                if ($cl->loadFile(\Env::get('cx')->getCodeBaseModulePath() . '/Feed/Controller/NewsML.class.php')) {
                    $objNewsML = new NewsML();
                    $objNewsML->setNews($arrMatches[1], $themesPages['index']);
                }
            }
        }
    }

}
