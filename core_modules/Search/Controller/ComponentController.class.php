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
 * Main controller for Search
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_search
 */

namespace Cx\Core_Modules\Search\Controller;

/**
 * Internal Search Exception
 *
 * @copyright   Cloudrexx AG
 * @author      Thomas Wirz <thomas.wirz@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_search
 */
class SearchInternalException extends \Exception {}

/**
 * Main controller for Search
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_search
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getCommandsForCommandMode()
    {
        return array('Search');
    }

    /**
     * {@inheritDoc}
     */
    public function getCommandDescription($command, $short = false) {
        if ($short) {
            return 'Lookup data by keyword';
        }
        return 'Search term=<keyword> [nodeId=<node-id>]';
    }

    /**
     * {@inheritDoc}
     */
    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        if ($command == 'Search') {
            $this->executeCommandSearch($arguments);
        }
    }

    /**
     * Register your events here
     *
     * Do not do anything else here than list statements like
     * $this->cx->getEvents()->addEvent($eventName);
     */
    public function registerEvents()
    {
        $eventHandlerInstance = $this->cx->getEvents();
        $eventHandlerInstance->addEvent('SearchFindContent');
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $objTemplate, $_CORELANG, $act;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $pos = (isset($_GET['pos'])) ? intval($_GET['pos']) : '';
                $objSearch = new \Cx\Core_Modules\Search\Controller\Search($page);
                \Env::get('cx')->getPage()->setContent($objSearch->getPage($pos, \Env::get('cx')->getPage()->getContent()));
                break;
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $subMenuTitle = $_CORELANG['TXT_SEARCH'];
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $cachedRoot = $this->cx->getTemplate()->getRoot();
                $this->cx->getTemplate()->setRoot($this->getDirectory() . '/View/Template/Backend');

                $objSearchManager = new \Cx\Core_Modules\Search\Controller\SearchManager($act, $objTemplate, $this->cx->getLicense());
                $objSearchManager->getPage();

                $this->cx->getTemplate()->setRoot($cachedRoot);
                break;
            default:
                break;
        }
    }

    /**
     * Execute search
     *
     * Lookup system for data matching a specific keyword.
     * Specify the keyword as array-key 'term' to param
     * $arguments.
     * Filter the result by a specific branch of the content tree
     * by setting the ID of a content node as array-key 'nodeId'
     * to param $arguments.
     *
     * @param $arguments array  Array of commend arguments
     */
    public function executeCommandSearch($arguments)
    {
        // fetch the published application page
        try {
            $page = $this->getSearchApplicationPage();
        } catch (SearchInternalException $e) {
            // Component is not published in ContentManager.
            // Let's abort
            echo json_encode(array());
            exit;
        }

        // limit the result to a content branch,
        // but only in case no restriction has already been set for the actual
        // application
        if (
            empty($page->getCmd()) &&
            !empty($arguments['nodeId'])
        ) {
            // set type and module in case page is a fallback-page
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule($this->getName());

            // restrict search result to specific branch 
            $page->setCmd('[[NODE_' . intval($arguments['nodeId']) . ']]');
        }

        $term               = isset($arguments['term']) ? contrexx_input2raw($arguments['term']) : '';
        $arraySearchResults = array();
        if (strlen($term) < 3) {
            echo json_encode(array());
            exit;
        }

        // get passed options
        $options = array();
        if (
            !empty($arguments['options']) &&
            is_array($arguments['options'])
        ) {
            $options = $arguments['options'];
        }

        $search = new \Cx\Core_Modules\Search\Controller\Search($page);
        $arraySearchResults = $search->getSearchResult($term, $options);

        echo json_encode($arraySearchResults);
        exit;
    }

    /**
     * Get published application page of this component
     *
     * @return  \Cx\Core\ContentManager\Model\Entity\Page   The published
     *                                                      application page of
     *                                                      this component
     * @throws  SearchInternalException In case no application page of this
     *                                  component is published
     */
    protected function getSearchApplicationPage() {
        // fetch data about existing application pages of this component
        $cmds = array('');
        $em = $this->cx->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $pages = $pageRepo->getAllFromModuleCmdByLang($this->getName());
        foreach ($pages as $pagesOfLang) {
            foreach ($pagesOfLang as $page) {
                $cmds[] = $page->getCmd();
            }
        }

        // check if an application page is published
        $cmds = array_unique($cmds);
        foreach ($cmds as $cmd) {
            $page = $pageRepo->findOneByModuleCmdLang($this->getName(), $cmd, FRONTEND_LANG_ID);
            if (
                $page &&
                $page->isActive()
            ) {
                return $page;
            }
        }

        throw new SearchInternalException('Application is not published');
    }
}
