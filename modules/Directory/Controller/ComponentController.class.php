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
 * Main controller for Directory
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_directory
 */

namespace Cx\Modules\Directory\Controller;

/**
 * Main controller for Directory
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_directory
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
                $objDirectory = new Directory(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objDirectory->getPage());
                if ($_GET['cmd'] == 'detail' && isset($_GET['id'])) {
                    $objTemplate->setVariable(array(
                        'DIRECTORY_ENTRY_ID' => intval($_GET['id']),
                    ));
                }
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:

                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                $subMenuTitle = $_CORELANG['TXT_DIRECTORY_MODULE'];
                $objDirectoryManager = new DirectoryManager();
                $objDirectoryManager->getPage();
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

        global $_CONFIG, $cl, $dirc, $themesPages, $page_template, $themesPages;

        // get Directory Homecontent
        if ($_CONFIG['directoryHomeContent'] == '1') {
            if ($cl->loadFile(ASCMS_MODULE_PATH . '/Directory/Controller/DirHomeContent.class.php')) {

                $dirc = $themesPages['directory_content'];
                if (preg_match('/{DIRECTORY_FILE}/', \Env::get('cx')->getPage()->getContent())) {
                    \Env::get('cx')->getPage()->setContent(str_replace('{DIRECTORY_FILE}', DirHomeContent::getObj($dirc)->getContent(), \Env::get('cx')->getPage()->getContent()));
                }
                if (preg_match('/{DIRECTORY_FILE}/', $page_template)) {
                    $page_template = str_replace('{DIRECTORY_FILE}', DirHomeContent::getObj($dirc)->getContent(), $page_template);
                }
                if (preg_match('/{DIRECTORY_FILE}/', $themesPages['index'])) {
                    $themesPages['index'] = str_replace('{DIRECTORY_FILE}', DirHomeContent::getObj($dirc)->getContent(), $themesPages['index']);
                }
            }
        }
    }

    /**
     * Do something after content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {

        global $directoryCheck, $objTemplate, $cl, $objDirectory, $_CORELANG;

        // Directory Show Latest
        $directoryCheck = array();
        for ($i = 1; $i <= 10; $i++) {
            if ($objTemplate->blockExists('directoryLatest_row_' . $i)) {
                array_push($directoryCheck, $i);
            }
        }
        if (!empty($directoryCheck)
                /** @ignore */ && $cl->loadFile(ASCMS_MODULE_PATH . '/Directory/Controller/Directory.class.php')) {
            $objDirectory = new Directory('');
            if (!empty($directoryCheck)) {
                $objTemplate->setVariable('TXT_DIRECTORY_LATEST', $_CORELANG['TXT_DIRECTORY_LATEST']);
                $objDirectory->getBlockLatest($directoryCheck);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function registerEventListeners()
    {
        $evm               = $this->cx->getEvents();
        $directoryListener = new \Cx\Modules\Directory\Model\Event\DirectoryEventListener();
        $evm->addEventListener('SearchFindContent', $directoryListener);
    }

    /**
     * {@inheritdoc}
     */
    public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        $page = $response->getPage();
        if (
            !$page ||
            $page->getModule() !== $this->getName()
        ) {
            return;
        }

        $objDirectory = new Directory('');
        $directory_pagetitle = $objDirectory->getPageTitle();
        //Set the Page Title
        if (!empty($directory_pagetitle)) {
            $page->setTitle($directory_pagetitle);
            $page->setContentTitle($directory_pagetitle);
            $page->setMetaTitle($directory_pagetitle);
        }
    }
}
