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
 * JSON Adapter for Cx\Core\ContentManager\Model\Entity\Page
 * @copyright   Cloudrexx AG
 * @author      Florian Schuetz <florian.schuetz@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */

namespace Cx\Core\ContentManager\Controller;
use \Cx\Core\Json\JsonAdapter;

/**
 * JSON Adapter for Cx\Core\ContentManager\Model\Entity\Page
 * @copyright   Cloudrexx AG
 * @author      Florian Schuetz <florian.schuetz@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */
class JsonPage implements JsonAdapter {

    private $em        = null;
    private $db        = null;
    private $pageRepo  = null;
    private $nodeRepo  = null;
    private $logRepo   = null;
    private $fallbacks = array();
    private $multipleSetState = null;

    public $messages;

    /**
     * Constructor
     */
    function __construct() {
        $this->em = \Env::get('em');
        $this->db = \Env::get('db');
        if ($this->em) {
            $this->pageRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $this->nodeRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
            $this->logRepo  = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\LogEntry');
        }
        $this->messages = array();
        $this->tz       = new \DateTimeZone('Europe/Berlin');

        $fallback_lang_codes = \FWLanguage::getFallbackLanguageArray();
        $active_langs = \FWLanguage::getActiveFrontendLanguages();

        // get all active languages and their fallbacks
        foreach ($active_langs as $lang) {
            $this->fallbacks[\FWLanguage::getLanguageCodeById($lang['id'])] = ((array_key_exists($lang['id'], $fallback_lang_codes)) ? \FWLanguage::getLanguageCodeById($fallback_lang_codes[$lang['id']]) : null);
        }
    }

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'page';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @author Michael Ritter <michael.ritter@comvation.com>
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array(
            'get',
            'set',
            'multipleSet',
            'setPagePreview',
            'getHistoryTable',
            'getAccessData',
            'getPathByTarget',
            'isBroken',
            'loadApplicationTemplate'
        );
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return implode("<br />", $this->messages);
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Sends data to the client
     * @todo Clean up usage of $param
     * @param Array $params Client parameters
     * @return Array Page as array
     * @throws Exception If the page could not be found
     */
    public function get($params) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) ||
                !\Permission::checkAccess(35, 'static', true)) {
            throw new \Exception($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }

        if (isset($params['get']) && isset($params['get']['history'])) {
            $params['get']['history'] = contrexx_input2raw($params['get']['history']);
        }

        // pages can be requested in two ways:
        // by page id               - default for existing pages
        // by node id + lang        - to translate an existing page into a new language, assigned to the same node
        if (!empty($params['get']['page'])) {
            $page = $this->pageRepo->find($params['get']['page']);
        }

        if (isset($page)) {
            // Page access check
            if ($page->isBackendProtected() &&
                    !\Permission::checkAccess($page->getBackendAccessId(), 'dynamic', true)) {
                throw new \Cx\Core\ContentManager\Model\Entity\PageException($_CORELANG['TXT_CORE_CM_READ_DENIED']);
            }

            // load an older revision if asked to do so:
            if (isset($params['get']['history'])) {
                $this->logRepo->revert($page, $params['get']['history']);
            }
            // load the draft revision if one is available and we're not loading historic data:
            else if ($page->getEditingStatus() == 'hasDraft' || $page->getEditingStatus() == 'hasDraftWaiting') {
                $this->logRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\LogEntry');
                $availableRevisions = $this->logRepo->getLogEntries($page, true, 2);
                $this->logRepo->revert($page, $availableRevisions[1]->getVersion());
            }

            $pageArray = $this->getPageArray($page);
        } else if (!empty($params['get']['node']) && !empty($params['get']['lang'])) {
            $node = $this->nodeRepo->find($params['get']['node']);
            $pageArray = $this->getFallbackPageArray($node, $params['get']['lang']);
        } else {
            throw new \Exception('cannot find that page');
        }
        $additionalArguments = new \Cx\Core\Model\RecursiveArrayAccess(array());
        //\Env::get('cx');
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $evm = $cx->getEvents();
        $evm->triggerEvent('wysiwygCssReload', array($pageArray, $additionalArguments));
        $pageArray['wysiwygCssReload'] = $additionalArguments->toArray();
        return $pageArray;
    }

    /**
     * Handles request from the client
     * @todo Clean up usage of $param and $_GET
     * @global Array $_CORELANG Core language data
     * @param Array $params Client parameters
     * @return type
     */
    public function set($params) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) || !\Permission::checkAccess(35, 'static', true)) {
            throw new \Exception($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }

        $newPage = false;
        $reload  = false;
        $pg      = \Env::get('pageguard');

        $dataPost  = !empty($params['post'])   ? $params['post']   : array();
        $pageArray = !empty($dataPost['page']) ? $dataPost['page'] : array(); // Only set in the editing mode.

        $pageId = !empty($pageArray['id'])    ? intval($pageArray['id'])   : (!empty($dataPost['pageId']) ? intval($dataPost['pageId']) : 0);
        $nodeId = !empty($pageArray['node'])  ? intval($pageArray['node']) : (!empty($dataPost['nodeId']) ? intval($dataPost['nodeId']) : 0);
        $lang   = !empty($pageArray['lang'])  ? contrexx_input2raw($pageArray['lang'])  : (!empty($dataPost['lang']) ? contrexx_input2raw($dataPost['lang']) : \FWLanguage::getLanguageCodeById(\FWLanguage::getDefaultLangId()));
        $action = !empty($dataPost['action']) ? contrexx_input2raw($dataPost['action']) : '';

        if (!empty($pageArray)) {
            if (!empty($pageArray['target']) && !empty($pageArray['target_protocol'])) {
                $pageArray['target'] = $pageArray['target_protocol'] . $pageArray['target'];
            } elseif (empty($pageArray['target']) && !empty($pageArray['target_protocol'])) {
                $pageArray['target'] = '';
            }
            $validatedPageArray = $this->validatePageArray($pageArray);
        }

        $this->em->getConnection()->beginTransaction();
        try {

            // UPDATE
            if (!empty($pageId)) {
                // If we got a page id, the page already exists and can be updated.
                $page = $this->pageRepo->find($pageId, 0, null, false);
                $node = $page->getNode();

            // TRANSLATE
            } else if (!empty($nodeId) && !empty($lang)) {
                // We are translating the page.
                $node = $this->nodeRepo->find($nodeId);
                $page = $node->translatePage(true, \FWLanguage::getLanguageIdByCode($lang));
                $page->setNodeIdShadowed($node->getId());

                $newPage = true;
                $reload  = true;

            // CREATE
            } else if (empty($pageId) && !empty($lang)) {
                if (!\Permission::checkAccess(5, 'static', true)) {
                    throw new \Exception($_CORELANG['TXT_CORE_CM_CREATION_DENIED']);
                }

                // Create a new node/page combination.
                $node = new \Cx\Core\ContentManager\Model\Entity\Node();

                // CREATE WITHIN
                if (isset($dataPost['parent_node'])) {
                    $parentNode = $this->nodeRepo->find($dataPost['parent_node']);
                    if (!$parentNode) {
                        $parentNode = $this->nodeRepo->getRoot();
                    }
                    $node->setParent($parentNode);
                    $parentNode->addChildren($node);

                    // add parent node to ID, so the node containing the new page is opened
                    $openNodes = array();
                    if (isset($_COOKIE[\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN])) {
                        $openNodes = explode(',', $_COOKIE[\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN]);
                    }
                    if ($openNodes == array(0=>'')) {
                        $openNodes = array();
                    }
                    if (!in_array('#node_' . $parentNode->getId(), $openNodes)) {
                        $openNodes[] = '#node_' . $parentNode->getId();
                    }
                    setcookie(\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN, implode(',', $openNodes), 0, \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath() . '/');

                    $this->em->persist($node);
                    $this->em->flush();

                // CREATE
                } else {
                    $node->setParent($this->nodeRepo->getRoot());
                    $this->nodeRepo->getRoot()->addChildren($node);

                    $this->em->persist($node);
                    $this->em->flush();
                }

                $page = new \Cx\Core\ContentManager\Model\Entity\Page();
                $page->setNode($node);
                $node->addPage($page);
                $page->setNodeIdShadowed($node->getId());
                $page->setLang(\FWLanguage::getLanguageIdByCode($lang));
                $page->setUpdatedBy(
                    \FWUser::getFWUserObject()->objUser->getUsername()
                );

                $newPage = true;
                $reload  = true;
            } else {
                throw new \Exception('Page cannot be created. There are too little information.');
            }

            // Page access check
            if ($page->isBackendProtected() &&
                    !\Permission::checkAccess($page->getBackendAccessId(), 'dynamic', true)) {
                throw new \Cx\Core\ContentManager\Model\Entity\PageException('Not allowed to read page');
            }

            if (!empty($pageArray)) {
                $page->updateFromArray($validatedPageArray);
            }

            if (!empty($action)) {
                switch ($action) {
                    case 'activate':
                    case 'publish':
                        $page->setActive(true);
                        break;
                    case 'deactivate':
                        $page->setActive(false);
                        break;
                    case 'show':
                        $page->setDisplay(true);
                        break;
                    case 'hide':
                        $page->setDisplay(false);
                        break;
                    case 'protect':
                        $page->setFrontendProtection(true);
                        break;
                    case 'unprotect':
                        $page->setFrontendProtection(false);
                        break;
                    case 'lock':
                        $page->setBackendProtection(true);
                        break;
                    case 'unlock':
                        $page->setBackendProtection(false);
                        break;
                }

                if (($action != 'publish') && !$page->isDraft()) {
                    $action = 'publish';
                }
            }

            $page->setUpdatedAtToNow();
            $page->validate();

            if (!empty($pageArray) && $newPage) {
                // Make sure page has an ID
                $this->em->persist($page);
                $this->em->flush();
            }

            // Permissions are only updated in the editing mode.
            if (!empty($pageArray)) {
                if ($action == 'publish') {
                    if (\Permission::checkAccess(36, 'static', true)) {
                        if ($page->isFrontendProtected()) {
                            // remove all
                            \Permission::removeAccess($page->getFrontendAccessId(), 'dynamic');
                            if (isset($dataPost['frontendGroups'])) {
                                // set new
                                $pg->setAssignedGroupIds($page, $dataPost['frontendGroups'], true);
                            }
                        }
                        if ($page->isBackendProtected()) {
                            // remove all
                            $groupIds = $pg->getAssignedGroupIds($page, false);
                            \Permission::removeAccess($page->getBackendAccessId(), 'dynamic');
                            if (isset($dataPost['backendGroups'])) {
                                // set new
                                $pg->setAssignedGroupIds($page, $dataPost['backendGroups'], false);
                            }
                            if ($page->isBackendProtected() &&
                                !\Permission::checkAccess($page->getBackendAccessId(), 'dynamic', true)) {
                                if (!count($groupIds)) {
                                    $page->setBackendProtection(false);
                                } else {
                                    $pg->setAssignedGroupIds($page, $groupIds, false);
                                }
                            }
                        }
                    }
                }
            }

            // Block associations are only updated in the editing mode.
            if (!empty($pageArray) && empty($dataPost['ignoreBlocks'])) {
                if (!isset($dataPost['pageBlocks'])) {
                    $dataPost['pageBlocks'] = array();
                }
                $page->setRelatedBlocks($dataPost['pageBlocks']);
            }

            $draftUpdateLog = null;
            $liveUpdateLog = null;
            $updatingDraft = false;
            if (($action == 'publish') && \Permission::checkAccess(78, 'static', true)) {
                // User w/permission clicked save&publish. we should either publish the page or submit the draft for approval.
                if ($page->getEditingStatus() == 'hasDraftWaiting') {
                    $reload = true;
                }

                if ($page->getEditingStatus() != '') {
                    $logEntries = $this->logRepo->getLogEntries($page, false, 1);
                    $this->em->remove($logEntries[0]);
                }

                $page->setEditingStatus('');
                $this->messages[] = $_CORELANG['TXT_CORE_SAVED'];
            } else {
                // User clicked save [as draft], so let's do that.
                $updatingDraft = $page->getEditingStatus() != '' ? true : false;

                if ($action == 'publish') {
                    // User w/o publish permission clicked save&publish. submit it as a draft.
                    $page->setEditingStatus('hasDraftWaiting');
                    $this->messages[] = $_CORELANG['TXT_CORE_DRAFT_SUBMITTED'];
                } else {
                    if ($page->getEditingStatus() == 'hasDraftWaiting' && \Permission::checkAccess(78, 'static', true)) {
                        $reload = true;
                    }
                    $page->setEditingStatus('hasDraft');
                    $this->messages[] = $_CORELANG['TXT_CORE_SAVED_AS_DRAFT'];
                }

                // Gedmo-loggable generates a LogEntry (i.e. revision) on persist, so we'll have to 
                // store the draft first, then revert the current version to what it previously was.
                // In the end, we'll have the current [published] version properly stored as a page
                // and the draft version stored as a gedmo LogEntry.

                $this->em->persist($page);
                // Gedmo hooks in on persist/flush, so we unfortunately need to flush our em in
                // order to get a clean set of logEntries.
                $this->em->flush();
                $logEntries = $this->logRepo->getLogEntries($page, false, 2);
                // Revert to the published version.
                $cachedEditingStatus = $page->getEditingStatus();
                $this->logRepo->revert($page, $logEntries[1]->getVersion());
                $page->setEditingStatus($cachedEditingStatus);

                switch ($action) {
                    case 'activate':
                    case 'publish':
                        $page->setActive(true);
                        break;
                    case 'deactivate':
                        $page->setActive(false);
                        break;
                    case 'show':
                        $page->setDisplay(true);
                        break;
                    case 'hide':
                        $page->setDisplay(false);
                        break;
                    case 'protect':
                        $page->setFrontendProtection(true);
                        break;
                    case 'unprotect':
                        $page->setFrontendProtection(false);
                        break;
                    case 'lock':
                        $page->setBackendProtection(true);
                        break;
                    case 'unlock':
                        $page->setBackendProtection(false);
                        break;
                }

                $this->em->persist($page);

                // Gedmo auto-logs slightly too much data. clean up unnecessary revisions:
                if ($updatingDraft) {
                    $this->em->flush();

                    $logEntries = $this->logRepo->getLogEntries($page, true, 3);
                    $currentLog = $logEntries[1];
                    $currentLogData = $currentLog->getData();
                    $currentLogData['editingStatus'] = $page->getEditingStatus();
                    $currentLog->setData($currentLogData);
                    $this->em->persist($currentLog);

                    $liveUpdateLog = $logEntries[2];
                    $this->em->remove($logEntries[2]);
                }
            }

            $this->em->persist($page);

            if ((isset($dataPost['inheritFrontendAccess']) && $dataPost['inheritFrontendAccess'] == 'on')
                    || (isset($dataPost['inheritBackendAccess']) && $dataPost['inheritBackendAccess'] == 'on')
                    || (isset($dataPost['inheritSkin']) && $dataPost['inheritSkin'] == 'on')
                    || (isset($dataPost['inheritCustomContent']) && $dataPost['inheritCustomContent'] == 'on')
                    || (isset($dataPost['inheritCssName']) && $dataPost['inheritCssName'] == 'on')
                    || (isset($dataPost['inheritCssNavName']) && $dataPost['inheritCssNavName'] == 'on')
                    || (isset($dataPost['inheritCaching']) && $dataPost['inheritCaching'] == 'on')
            ) {
                $pageStack = $page->getChildren();
                while (count($pageStack)) {
                    $currentPage = array_pop($pageStack);
                    foreach ($currentPage->getChildren() as $child) {
                        array_push($pageStack, $child);
                    }
                    if (isset($dataPost['inheritFrontendAccess']) && $dataPost['inheritFrontendAccess'] == 'on'/*frontendprotection*/) {
                        $reload = true;
                        $page->copyProtection($currentPage, true);
                    }
                    if (isset($dataPost['inheritBackendAccess']) && $dataPost['inheritBackendAccess'] == 'on'/*backendprotection*/) {
                        $reload = true;
                        $page->copyProtection($currentPage, false);
                    }
                    if (isset($dataPost['inheritSkin']) && $dataPost['inheritSkin'] == 'on'/*theme*/) {
                        $currentPage->setSkin($page->getSkin());
                        if (!empty($pageArray['useSkinForAllChannels'])) {
                            $currentPage->setUseSkinForAllChannels(1);
                        }
                    }
                    if (isset($dataPost['inheritCustomContent']) && $dataPost['inheritCustomContent'] == 'on'/*customContent*/) {
                        $currentPage->setCustomContent($page->getCustomContent());
                        if (!empty($pageArray['useCustomContentForAllChannels'])) {
                            $currentPage->setUseCustomContentForAllChannels(1);
                        }
                    }
                    if (isset($dataPost['inheritCssName']) && $dataPost['inheritCssName'] == 'on'/*cssName*/) {
                        $currentPage->setCssName($page->getCssName());
                    }
                    if (isset($dataPost['inheritCssNavName']) && $dataPost['inheritCssNavName'] == 'on'/*cssNavName*/) {
                        $currentPage->setCssNavName($page->getCssNavName());
                    }
                    if (isset($dataPost['inheritCaching']) && $dataPost['inheritCaching'] == 'on'/*caching*/) {
                        $currentPage->setCaching($page->getCaching());
                    }
                    $this->em->persist($currentPage);
                }
            }

            $this->em->flush();

            // bug fix #2279
            // could not save alias after running $this->em->clear()
            // Aliases are only updated in the editing mode.
            if (!empty($pageArray)) {
                // Only users with publish rights can create aliases.
                if (\Permission::checkAccess(115, 'static', true) && \Permission::checkAccess(78, 'static', true)) {
                    // Aliases are updated after persist.
                    $data = array();
                    $data['alias'] = $pageArray['alias'];
                    $aliases = $page->getAliases();
                    $page->updateFromArray($data);
                    if ($aliases != $page->getAliases()) {
                        $reload = true;
                    }
                } else {
                    // Users without permission shouldn't see the aliasses anyway
                    //$this->messages[] = $_CORELANG['TXT_CORE_ALIAS_CREATION_DENIED'];
                }
            }

            // this fixes log version number skipping
            $this->em->clear();
            $logs = $this->logRepo->getLogEntries($page, true, 2);
            $this->em->persist($logs[0]);

            if ($updatingDraft) {
                $data = $logs[1]->getData();
                if (!empty($action) && $draftUpdateLog) {
                    $data = $draftUpdateLog->getData();
                }
                $data['editingStatus'] = 'hasDraft';
                if ($action == 'publish' && !\Permission::checkAccess(78, 'static', true)) {
                    $data['editingStatus'] = 'hasDraftWaiting';
                }
                switch ($action) {
                    case 'activate':
                        $data['active'] = true;
                        break;
                    case 'deactivate':
                        $data['active'] = false;
                        break;
                    case 'show':
                        $data['display'] = true;
                        break;
                    case 'hide':
                        $data['display'] = false;
                        break;
                    case 'protect':
                        $data['protection'] = $data['protection'] | FRONTEND_PROTECTION;
                        break;
                    case 'unprotect':
                        $data['protection'] = $data['protection'] & ~FRONTEND_PROTECTION;
                        break;
                    case 'lock':
                        $data['protection'] = $data['protection'] | BACKEND_PROTECTION;
                        break;
                    case 'unlock':
                        $data['protection'] = $data['protection'] & ~BACKEND_PROTECTION;
                        break;
                }
                $logs[1]->setData($data);

                if (!empty($action) && $action != 'publish') {
                    $data = $logs[0]->getData();
                    if ($liveUpdateLog) {
                        $data = $liveUpdateLog->getData();
                    }
                    switch ($action) {
                        case 'activate':
                            $data['active'] = true;
                            break;
                        case 'deactivate':
                            $data['active'] = false;
                            break;
                        case 'show':
                            $data['display'] = true;
                            break;
                        case 'hide':
                            $data['display'] = false;
                            break;
                        case 'protect':
                            $data['protection'] = $data['protection'] | FRONTEND_PROTECTION;
                            break;
                        case 'unprotect':
                            $data['protection'] = $data['protection'] & ~FRONTEND_PROTECTION;
                            break;
                        case 'lock':
                            $data['protection'] = $data['protection'] | BACKEND_PROTECTION;
                            break;
                        case 'unlock':
                            $data['protection'] = $data['protection'] & ~BACKEND_PROTECTION;
                            break;
                    }
                    $logs[0]->setData($data);
                }

                $this->em->persist($logs[0]);
                $this->em->persist($logs[1]);
                $this->em->flush();
            }
            $this->em->getConnection()->commit();

            // Drop cache of our page and all pages pointing to our page
            $pageIdsToDropCache = $this->pageRepo->getPagesPointingTo($page);
            $pageIdsToDropCache[] = $page->getId();

            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cacheComponent = $cx->getComponent('Cache');
            foreach ($pageIdsToDropCache as $pageIdToDropCache) {
                $cacheComponent->deleteSingleFile($pageIdToDropCache);
            }
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }

        // get version
        // if it is a draft, don't take the last one
        $version = $page->getVersion()->getVersion();
        if ($page->isDraft()) {
            $version--;
        }

        return array(
            'reload' => $reload,
            'id'     => $page->getId(),
            'version'=> $version,
            'node'   => $page->getNode()->getId(),
            'lang'   => \FWLanguage::getLanguageCodeById($page->getLang()),
        );
    }

    /**
     * Sets multiple pages.
     *
     * @param  array  $params  Client parameters.
     */
    public function multipleSet($params) {
        register_shutdown_function(array($this, 'multipleSetShutdown'));

        $post = $params['post'];
        $data = array('post' => array());
        $data['post']['lang']   = $post['lang'];
        $data['post']['action'] = $post['action'];
        $recursive = (isset($params['get']['recursive']) && $params['get']['recursive'] == 'true');
        $requestedOffset = (isset($params['get']['offset']) ? $params['get']['offset'] : 0);
        $return = array();

        $nodeIdStack = $post['nodes'];
        $this->multipleSetState = array('state' => 'running', 'offset' => 0, 'stack' => $nodeIdStack);
        $offset = 0;
        while (count($nodeIdStack)) {
            $nodeId = array_pop($nodeIdStack);
            $data['post']['nodeId'] = $nodeId;
            $node = $this->nodeRepo->findOneById($nodeId);
            if ($recursive) {
                foreach ($node->getChildren() as $child) {
                    array_push($nodeIdStack, $child->getId());
                }
            }
            $offset++;
            if ($requestedOffset > $offset) {
                continue;
            }
            $page = $node->getPage(\FWLanguage::getLanguageIdByCode($post['lang']));
            switch ($data['post']['action']) {
                case 'activate':
                    if ($page->isActive()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                case 'deactivate':
                    if (!$page->isActive()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                case 'show':
                    if ($page->isVisible()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                case 'hide':
                    if (!$page->isVisible()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                case 'protect':
                    if ($page->isFrontendProtected()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                case 'unprotect':
                    if (!$page->isFrontendProtected()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                case 'lock':
                    if ($page->isBackendProtected()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                case 'unlock':
                    if (!$page->isBackendProtected()) {
                        // we have that already, continue
                        continue 2;
                    }
                    break;
                default:
                    // just set what we got, continue
                    break;
            }
            if (!empty($page)) {
                $data['post']['pageId'] = $page->getId();
            } else {
                $data['post']['pageId'] = 0;
            }
            $result = $this->set($data);
            if (   isset($post['currentNodeId'])
                && isset($post['lang'])
                && $result['node'] == $post['currentNodeId']
                && $result['lang'] == $post['lang']
            ) {
                $return['id'] = $result['id'];
            }
            $this->multipleSetState['offset']++;
        }
        $this->multipleSetState = null;
        unset($nodeIdStack);

        return $return;
    }

    public function multipleSetShutdown() {
        if ($this->multipleSetState) {
            $this->multipleSetState['state'] = 'timeout';
            $jd = new \Cx\Core\Json\JsonData();
            echo $jd->json(
                new \Cx\Core\Routing\Model\Entity\Response($this->multipleSetState)
            );
        }
    }

    /**
     * Sets the page object in the session and returns the link to the page (frontend).
     * @param   array  $params
     * @return  array  [link]     The link to the page (frontend).
     */
    public function setPagePreview($params) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) || !\Permission::checkAccess(35, 'static', true)) {
            throw new \Exception($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }

        $page = $this->validatePageArray($params['post']['page']);
        $page['pageId'] = $params['post']['page']['id'];
        $page['lang'] = $params['post']['page']['lang'];

        $_SESSION['page'] = $page;
    }

    /**
     * Returns a validated page array.
     *
     * @param   array  $page
     * @return  array  $output
     */
    private function validatePageArray($page) {
        $fields = array(
            'type' => array('type' => 'String'),
            'name' => array('type' => 'String', 'required' => true, 'map_to' => 'title'),
            'title' => array('type' => 'String', 'required' => true, 'map_to' => 'contentTitle'),
            // the model can take advantage of proper NULLing, so this needn't be set
            //                  'scheduled_publishing' => array('type' => 'boolean'),
            'start' => array('type' => 'DateTime', 'require' => 'scheduled_publishing'),
            'end' => array('type' => 'DateTime', 'require' => 'scheduled_publishing'),
            'metatitle' => array('type' => 'String'),
            'metakeys' => array('type' => 'String'),
            'metadesc' => array('type' => 'String'),
            'metarobots' => array('type' => 'boolean'),
            'metaimage' => array('type' => 'String'),
            'content' => array('type' => 'String'),
            'sourceMode' => array('type' => 'boolean'),
            'protection_frontend' => array('type' => 'boolean', 'map_to' => 'frontendProtection'),
            'protection_backend' => array('type' => 'boolean', 'map_to' => 'backendProtection'),
            'application' => array('type' => 'String', 'map_to' => 'module'),
            'area' => array('type' => 'String', 'map_to' => 'cmd'),
            'target' => array('type' => 'String'),
            'link_target' => array('type' => 'String', 'map_to' => 'linkTarget'),
            'slug' => array('type' => 'String'),
            'caching' => array('type' => 'boolean'),
            'skin' => array('type' => 'integer'),
            'useSkinForAllChannels' => array('type' => 'integer'),
            'customContent' => array('type' => 'String'),
            'useCustomContentForAllChannels' => array('type' => 'integer'),
            'applicationTemplate' => array('type' => 'String'),
            'useCustomApplicationTemplateForAllChannels' => array('type' => 'integer'),
            'cssName' => array('type' => 'String'),
            'cssNavName' => array('type' => 'String'),
        );

        $output = array();

        foreach ($fields as $field => $meta) {
            $target = isset($meta['map_to']) ? $meta['map_to'] : $field;

            if (isset($page[$field])) {
                $page[$field] = contrexx_input2raw($page[$field]);
            } else {
                $page[$field] = null;
            }

            if (isset($meta['required']) && $meta['required'] === true) {
                if ($page[$field] == '') {
                    throw new \Cx\Core\ContentManager\Model\Entity\PageException('Required field (' . $field . ') not set!');
                }
            }

            switch ($meta['type']) {
                case 'boolean':
                    // checkboxes and radiobuttons by default aren't submitted
                    // unless checked or selected. in cm.html they are prefixed
                    // with an input type=hidden value=off, so we always get a
                    // value this is required for Page#updateFromArray to work.
                    if ($page[$field] == 'on') {
                        $value = true;
                    } else if ($page[$field] == 'off') {
                        $value = false;
                    }
                    break;
                case 'DateTime':
                    try {
                        $value = new \DateTime($page[$field], $this->tz);
                    } catch (\Exception $e) {
                        $value = new \DateTime('0000-00-00 00:00', $this->tz);
                    }
                    break;
                case 'integer':
                    $value = intval($page[$field]);
                    break;
                case 'String':
                    $value = $page[$field];
                    break;
            }
            if (isset($meta['require']) && isset($page[$meta['require']]) && $page[$meta['require']] == 'off') {
                $value = null;
            }

            $output[$target] = $value;
        }

        //TODO: should we allow filter/callback fns in field processing above?
        $output['content'] = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $output['content']);

        return $output;
    }

    /**
     * Get the page logs
     *
     * @param array $params Arguments passed from JsonData
     *
     * @throws \Exception
     */
    public function getHistoryTable($params)
    {
        global $_CORELANG, $_CONFIG;

        if (empty($params['get']['page'])) {
            throw new \Exception('please provide a pageId');
        }

        //(I) get the right page
        $page = $this->pageRepo->findOneById($params['get']['page']);

        if (!$page) {
            throw new \Exception('could not find page with id '.$params['get']['page']);
        }

        //(II) build the table with headers
        $table = new \BackendTable(array('width' => '100%'));
        $table->setAutoGrow(true);

        $hideDrafts = !isset($params['get']['hideDrafts']) || $params['get']['hideDrafts'] == 'on';
        $hideDraftsHtml = '<input type="checkbox" name="hideDrafts" value="on" id="hideDrafts"' . ($hideDrafts ? ' checked="checked"' : '') . ' /><label for="hideDrafts">' . $_CORELANG['TXT_CORE_CM_HISTORY_HIDE_DRAFTS'] . '</label>';

        $table->setHeaderContents(0, 0, $_CORELANG['TXT_WORKFLOW'], array('width' => '130px', 'colspan' => '2'));
        $table->setHeaderContents(0, 2, $hideDraftsHtml, array('style' => 'text-align: right;', 'class' => 'hideDrafts', 'colspan' => '2'));

        //(III) collect page informations - path, virtual language directory
        $path         = $this->pageRepo->getPath($page);
        $langDir      = \FWLanguage::getLanguageCodeById($page->getLang());
        //(V) add the history entries
        // Paging:
        $offset          =  0;
        $numberOfEntries = $_CONFIG['corePagingLimit'];
        if (!empty($params['get']['pos'])) {
            $offset = $params['get']['pos'];
        }
        if (!empty($params['get']['limit'])) {
            $numberOfEntries = $params['get']['limit'];
        }
        $logs = $this->logRepo->getLogEntries(
            $page,
            true,
            $numberOfEntries,
            $offset
        );
        $logsCount  = $this->logRepo->getLogEntriesCount($page);
        $tableRowId = 1;
        $i          = 0;
        $first      = true;
        session_write_close();
        foreach ($logs as $log) {
            $version = $log->getVersion();
            $this->logRepo->revert($page, $version);

            // is a log of a draft state
            if ($page->isDraft()) {
                if ($offset == 0 && $first) {
                    // skip first draft log
                    // set flag to false
                    $first = false;
                    continue;
                }
                if ($hideDrafts && $i != 0) {
                    // drafts should be hidden if it is not the first log
                    continue;
                }
            }
            // set flag to false
            $first = false;

            try {
                $page->setUpdatedAt($log->getLoggedAt());
                $user = json_decode($log->getUsername());

                $this->addHistoryEntries($page, $user->{'name'}, $table, $tableRowId, $version, $langDir . '/' . $path);
                $tableRowId++;
            } catch (\Gedmo\Exception\UnexpectedValueException $e) {

            }
            $i++;
        }
        // Add paging widget:
        $paging = '<div id="history_paging">' . getPaging(
            $logsCount,
            $offset,
            '?cmd=ContentManager&page=' . $page->getId() . '&tab=history',
            $_CORELANG['TXT_CORE_CM_HISTORY_ENTRIES'],
            true,
            $numberOfEntries
        ) . '</div>';

        //(VI) render
        die($table->toHtml() . $paging);
    }

    private function addHistoryEntries($page, $username, \BackendTable $table, $row, $version = '', $path = '') {
        global $_ARRAYLANG;

        $dateString  = $page->getUpdatedAt()->format(ASCMS_DATE_FORMAT_DATETIME);
        $historyLink = ASCMS_PATH_OFFSET . '/' . $path . '?history=' . $version;
        $tableStyle  = '';

        $editingStatus = $page->getEditingStatus();

        $functions  = '<a id="preview_'.$version.'" class="historyPreview" title="' . $_ARRAYLANG['TXT_CORE_PREVIEW'] . '" href="' . $historyLink . '" target="_blank" '.$tableStyle.'>' . $_ARRAYLANG['TXT_CORE_PREVIEW'] . '</a>';
        $functions .= '<a id="load_'.$version.'" class="historyLoad" title="' . $_ARRAYLANG['TXT_CORE_LOAD'] . '" href="javascript:loadHistoryVersion('.$version.')" '.$tableStyle.'>' . $_ARRAYLANG['TXT_CORE_LOAD'] . '</a>';

        /**
         * @todo This is a copy from JsonNode, move this snippet to its own method
         */
        if ($page->isActive()) {
            if ($editingStatus == 'hasDraft') {
                $publishingStatus = 'published draft';
            } else if ($editingStatus == 'hasDraftWaiting') {
                $publishingStatus = 'published draft waiting';
            } else {
                $publishingStatus = 'published';
            }
        } else {
            if ($editingStatus == 'hasDraft') {
                $publishingStatus = 'unpublished draft';
            } else if ($editingStatus == 'hasDraftWaiting') {
                $publishingStatus = 'unpublished draft waiting';
            } else {
                $publishingStatus = 'unpublished';
            }
        }
        if ($page->isBackendProtected() &&
                !\Permission::checkAccess($page->getBackendAccessId(), 'dynamic', true)) {
            $publishingStatus .= ' locked';
        }

        // set style
        $table->setCellAttributes($row, 0, array('style' => 'white-space: nowrap;'));
        $table->setCellAttributes($row, 1, array('style' => 'white-space: nowrap;'));

        // set content
        $table->setCellContents($row, 0, $dateString);
        $table->setCellContents($row, 1, '<div class="jstree-icon publishing ' . $publishingStatus . '" /><div class="name">' . $page->getTitle() . '</div>');
        $table->setCellContents($row, 2, $username);
        $table->setCellContents($row, 3, $functions);
        return true;
    }

    /**
     * Returns the access data array
     * @param type $page Unused
     * @return array Access data
     */
    public function getAccessData($page = null) {
        // TODO: add functionality for $page!=null (see below), DRY up #getFallbackPageArray

        $pg = \Env::get('pageguard');

        $accessData = array();

        $accessData['frontend'] = array('groups' => $pg->getGroups(true), 'assignedGroups' => array());
        $accessData['backend'] = array('groups' => $pg->getGroups(false), 'assignedGroups' => array());

        return $accessData;
    }

    /**
     * Returns the array representation of a fallback page
     * @param   \Cx\Core\ContentManager\Model\Entity\Node $node Node to get the page of
     * @param   string  $lang  Language code to get the fallback of
     * @return  array   Array  representing a fallback page
     */
    private function getFallbackPageArray($node, $lang) {
        $page = null;
        foreach ($node->getPages() as $page) {
            if ($page->getLang() == \FWLanguage::getLanguageIdByCode($this->fallbacks[$lang])) {
                break;
            }
        }

        if (!$page) {
            throw new \Cx\Core\ContentManager\Model\Entity\NodeException('Node (id '.$node->getId().') has no pages.');
        }

        // Access Permissions
        $pg = \Env::get('pageguard');
        $accessData = array();
        $accessData['frontend'] = array('groups' => $pg->getGroups(true), 'assignedGroups' => $pg->getAssignedGroupIds($page, true));
        $accessData['backend'] = array('groups' => $pg->getGroups(false), 'assignedGroups' => $pg->getAssignedGroupIds($page, false));

        $pageArray = array(
            // Editor Meta
            'id' => 0,
            'lang' => $lang,
            'node' => $node->getId(),
            'type' => ($this->fallbacks[$lang] ? 'fallback' : 'content'),
            // Page Tab
            'name' => $page->getTitle(),
            'title' => $page->getContentTitle(),
            // Metadata
            'metatitle' => $page->getMetatitle(),
            'metadesc' => $page->getMetadesc(),
            'metakeys' => $page->getMetakeys(),
            'metarobots' => $page->getMetarobots(),
            'metaimage' => $page->getMetaimage(),
            // Access Permissions
            'frontend_protection' => $page->isFrontendProtected(),
            'backend_protection' => $page->isBackendProtected(),
            'accessData' => $accessData,
            // Advanced Settings
            'slug' => $page->getSlug(),
            'sourceMode' => false,
            'sourceMode' => $page->getSourceMode(),
        );

        return $pageArray;
    }

    /**
     * Creates the page array representation of a page
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to "arrayify"
     * @return Array Array representing a page
     */
    private function getPageArray($page) {
        // Scheduled Publishing
        $n = new \DateTime(null, $this->tz);
        if ($page->getStart() && $page->getEnd()) {
            $scheduled_publishing = true;
            $start = $page->getStart()->format('d.m.Y H:i');
            $end = $page->getEnd()->format('d.m.Y H:i');
        } else {
            $scheduled_publishing = false;
            $start = $n->format('d.m.Y H:i');
            $end = $n->format('d.m.Y H:i');
        }

        // Access Permissions
        $pg = \Env::get('pageguard');
        $accessData = array();
        $accessData['frontend'] = array('groups' => $pg->getGroups(true), 'assignedGroups' => $pg->getAssignedGroupIds($page, true));
        $accessData['backend'] = array('groups' => $pg->getGroups(false), 'assignedGroups' => $pg->getAssignedGroupIds($page, false));

        try {
            $parentPath = substr($page->getParent()->getPath(), 1) . '/';
        } catch (\Cx\Core\ContentManager\Model\Entity\PageException $e) {
            $parentPath = '';
        }

        $pageArray = array(
            // Editor Meta
            'id' => $page->getId(),
            'lang' => \FWLanguage::getLanguageCodeById($page->getLang()),
            'node' => $page->getNode()->getId(),
            // Page Tab
            'name' => $page->getTitle(),
            'title' => $page->getContentTitle(),
            'type' => $page->getType(),
            'target' => $page->getTarget(),
            'target_path' => $this->getPathByTarget(array('get' => array('target' => $page->getTarget()))),
            'module' => $page->getModule(),
            'area' => $page->getCmd(),
            'scheduled_publishing' => $scheduled_publishing,
            'start' => $start,
            'end' => $end,
            'content' => preg_replace('/{([A-Z0-9_-]+)}/', '[[\\1]]', $page->getContent()),
            'sourceMode' => $page->getSourceMode(),
            // Metadata
            'metatitle' => $page->getMetatitle(),
            'metadesc' => $page->getMetadesc(),
            'metakeys' => $page->getMetakeys(),
            'metarobots' => $page->getMetarobots(),
            'metaimage' => $page->getMetaimage(),
            // Access Permissions
            'frontend_protection' => $page->isFrontendProtected(),
            'backend_protection' => $page->isBackendProtected(),
            'accessData' => $accessData,
            // Advanced Settings
            'skin' => $page->getSkin(),
            'useSkinForAllChannels' => $page->getUseSkinForAllChannels(),
            'customContent' => $page->getCustomContent(),
            'useCustomContentForAllChannels' => $page->getUseCustomContentForAllChannels(),
            'applicationTemplate' => $page->getApplicationTemplate(),
            'useCustomApplicationTemplateForAllChannels' => $page->getUseCustomApplicationTemplateForAllChannels(),
            'cssName' => $page->getCssName(),
            'cssNavName' => $page->getCssNavName(),
            'caching' => $page->getCaching(),
            'linkTarget' => $page->getLinkTarget(),
            'slug' => $page->getSlug(),
            'aliases' => $this->getAliasArray($page),
            'editingStatus' => $page->getEditingStatus(),
            'parentPath' => $parentPath,
            'assignedBlocks' => $this->getBlocks($page),
            'historyId' => $page->getVersion()->getVersion(),
                /* 'display'       =>  $page->getDisplay(),
                  'active'        =>  $page->getActive(),
                  'updatedAt'     =>  $page->getUpdatedAt(), */
        );

        return $pageArray;
    }

    private function getBlocks($page) {
        $blocks = $page->getRelatedBlocks();
        $data = array();
        foreach ($blocks as $id=>$block) {
            $data[] = $id;
        }
        return $data;
    }

    /**
     * Returns an array of alias slugs
     * @param Cx\Core\ContentManager\Model\Entity\Page $page Page to get the aliases of
     * @return Array<String>
     */
    private function getAliasArray($page) {
        $pages = $page->getAliases();
        $aliases = array();
        foreach ($pages as $alias) {
            $aliases[] = $alias->getSlug();
        }
        return $aliases;
    }

    /**
     * Returns the page path of the given target (node placeholder).
     * If the target page doesn't exist, the path of the error page will be returned.
     *
     * @param   array   $arguments
     * @return  string  $path
     */
    public function getPathByTarget($arguments) {
        global $_CONFIG;

        $target = contrexx_input2raw($arguments['get']['target']);

        if (!\FWValidator::hasProto($target)) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setTarget($target);

            if ($page->isTargetInternal()) {
                $target = str_replace(array('[[', ']]'), array('{', '}'), $target);
                \LinkGenerator::parseTemplate($target);
            } elseif (ASCMS_PATH_OFFSET == '' || strpos($target, ASCMS_PATH_OFFSET) === false) {
                if (!isset($target[0]) || $target[0] !== '/') {
                    $target = '/' . $target;
                }
                $target = ASCMS_PATH_OFFSET . $target;
            }

            $target = ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . $target;
        }

        return $target;
    }

    /**
     * Checks if the passed page or its redirect target page is broken.
     *
     * @param   array   $arguments
     * @return  boolean
     */
    public function isBroken($arguments) {
        if (isset($arguments['get']['pageId'])) {
            if (!\Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->find($arguments['get']['pageId'])) {
                return true;
            }
        }

        if (isset($arguments['get']['pageRedirectPlaceholder'])) {
            $filePath = \Env::get('cx')->getClassLoader()->getFilePath($arguments['get']['pageRedirectPlaceholder']);
            //do not break the page, if the redirection shows to a valid file
            if (is_file($filePath)) {
                return false;
            }
            try {
                //break the page, when the file or the placeholder does not exist
                if (!\Cx\Core\Routing\NodePlaceholder::fromPlaceholder($arguments['get']['pageRedirectPlaceholder'])->getPage()) {
                    return true;
                }
            } catch (\Cx\Core\Routing\NodePlaceholderException $e) {
                return true;
            }
        }

        return false;
    }

    /**
     * load the application template based on the application, cmd and theme name
     *
     * @return array
     */
    public function loadApplicationTemplate() {

        $application = isset($_GET['app']) ? contrexx_input2raw($_GET['app']) : '';
        $section     = preg_match('#^Media\d+# i', $application) ? 'Media' : $application;
        $cmd         = (isset($_GET['area']) && $_GET['area'] != '') ? contrexx_input2raw($_GET['area']) : 'default';
        $template    = isset($_GET['template']) ? contrexx_input2raw($_GET['template']) : '';
        $result      = array(
            'files'  => array(),
            'area'   => '',
            'path'   => ''
        );

        $themeRepo   = new \Cx\Core\View\Model\Repository\ThemeRepository();
        if (!empty($section)) {
            if ($template > 0) {
                $theme = $themeRepo->findOneBy(array('id' => $template));
            } else {
                $theme = $themeRepo->getDefaultTheme();
            }
            $themeFolderName  = $theme->getFoldername();
            $moduleFolderName = contrexx_isCoreModule($application) ? 'core_modules' : 'modules';
            $themePath        = \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()->getFilePath(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteThemesPath().'/'.$themeFolderName.'/'.$moduleFolderName.'/'.$section.'/Template/Frontend');
            if (file_exists($themePath)) {
                $templateFiles    = $this->readDirs($themePath);
                foreach ($templateFiles As $templateFile) {
                    if (preg_match('#^'.ucfirst($cmd).'_custom_# i', $templateFile) && preg_match('#\.html# i', $templateFile)) {
                        $result['files'][] = $templateFile;
                    }
                }
            }
            $result['area'] = ucfirst($cmd);
            $result['path'] = '/themes/'.$themeFolderName.'/'.$moduleFolderName.'/'.$section.'/Template/Frontend';

            $additionalArguments = new \Cx\Core\Model\RecursiveArrayAccess(array());
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $evm = $cx->getEvents();
            $evm->triggerEvent('wysiwygCssReload', array(array('skin'=>$template), $additionalArguments));
            $result['wysiwygCssReload'] = $additionalArguments->toArray();
        }
        return $result;
    }

    /**
     * read the files under the given path directory
     *
     * @param string $path
     *
     * @return array
     */
    public function readDirs($path) {

        $result = array();
        foreach (glob("$path/*.*", GLOB_BRACE) as $filename) {
            $file_name = str_replace($path . '/', '', $filename);
            $result[] = $file_name;
        }

        return $result;
    }
}
