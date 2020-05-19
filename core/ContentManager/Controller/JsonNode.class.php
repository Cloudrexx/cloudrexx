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
 * JSON Adapter for Cx\Core\ContentManager\Model\Entity\Node
 * @copyright   Cloudrexx AG
 * @author      Florian Schuetz <florian.schuetz@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */

namespace Cx\Core\ContentManager\Controller;
use \Cx\Core\Json\JsonAdapter;
use \Cx\Core\ContentManager\Controller\ContentManagerException;

/**
 * JSON Adapter for Cx\Core\ContentManager\Model\Entity\Node
 * @copyright   Cloudrexx AG
 * @author      Florian Schuetz <florian.schuetz@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */
class JsonNode implements JsonAdapter {

    /**
     * Reference to the Doctine EntityManager
     * @var \Doctrine\ORM\EntityManager
     */
    private $em = null;

    /**
     * Reference to the Doctrine NodeRepo
     * @var \Cx\Core\ContentManager\Model\Repository\NodeRepository
     */
    private $nodeRepo = null;

    /**
     * Reference to the Doctrine PageRepo
     * @var \Cx\Core\ContentManager\Model\Repository\PageRepository
     */
    private $pageRepo = null;

    /**
     * Reference to the Doctring LogRepository
     * @var \Cx\Core\ContentManager\Model\Repository\PageLogRepository
     */
    private $logRepo = null;

    /**
     * List of fallback languages
     * @var Array lang=>fallback lang
     */
    private $fallbacks = array();

    /**
     * List of messages
     * @var Array
     */
    private $messages;

    /**
     * List of IDs of deleted nodes
     * @var Array
     */
    protected $deleteBuffer = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->em = \Env::get('em');
        if ($this->em) {
            $this->nodeRepo = $this->em->getRepository('\Cx\Core\ContentManager\Model\Entity\Node');
            $this->pageRepo = $this->em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');
            $this->logRepo  = $this->em->getRepository('\Cx\Core\ContentManager\Model\Entity\LogEntry');
        }
        $this->messages = array();

        $fallback_lang_codes = \FWLanguage::getFallbackLanguageArray();
        $active_langs        = \FWLanguage::getActiveFrontendLanguages();

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
        return 'node';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('getTree', 'delete', 'copy', 'multipleDelete', 'move', 'getPageTitlesTree');
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Returns the Node tree rendered for JS
     * @return String JSON data
     */
    public function getTree($parameters) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) ||
                !\Permission::checkAccess(35, 'static', true)) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }

        $nodeId = 0;
        if (isset($parameters['get']) && isset($parameters['get']['nodeid'])) {
            $nodeId = contrexx_input2raw($parameters['get']['nodeid']);
        }
        if (isset($parameters['get']) && isset($parameters['get']['page'])) {
            $pageId = contrexx_input2raw($parameters['get']['page']);
            $page = $this->pageRepo->findOneBy(array('id' => $pageId));
            if ($page) {
                $node = $page->getNode()->getParent();
                // #node_{id},#node_{id}
                $openNodes = array();
                while ($node && $node->getId() != $nodeId) {
                    $openNodes[] = '#node_' . $node->getId();
                    $node = $node->getParent();
                }
                $openNodes2 = array();
                if (isset($_COOKIE[\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN])) {
                    $openNodes2 = explode(',', $_COOKIE[\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN]);
                }
                if ($openNodes2 == array(0=>'')) {
                    $openNodes2 = array();
                }
                $openNodes = array_merge($openNodes, $openNodes2);
                setcookie(\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN, implode(',', $openNodes), 0, \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath() . '/');
            }
        }

        $recursive = false;
        if ($nodeId == 0 &&
                isset($parameters['get']) &&
                isset($parameters['get']['recursive']) &&
                $parameters['get']['recursive'] == 'true') {
            $recursive = true;
        }
        return $this->renderTree($nodeId, $recursive);
    }

    /**
     * Moves a node.
     *
     * The following arguments are used:
     * id = id of the moved node
     * ref = id of the new parent node
     * position = new position of id as ref's Nth child
     *
     * Data source is in /lib/javascript/jquery/jstree/contrexx.js
     * @param array $arguments Arguments passed from JsonData
     */
    public function move($arguments) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) ||
                !\Permission::checkAccess(35, 'static', true)) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }
        if (!\Permission::checkAccess(160, 'static', true)) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_MOVE_DENIED']);
        }

        $moved_node = $this->nodeRepo->find($arguments['post']['id']);
        $parent_node = $this->nodeRepo->find($arguments['post']['ref']);

        $this->em->getConnection()->beginTransaction();
        try {
            $moved_node->setParent($parent_node);
            $this->em->persist($parent_node);
            $this->em->persist($moved_node);
            $this->em->flush();


            $this->nodeRepo->moveUp($moved_node, true);
            if ($arguments['post']['position']) {
                $this->nodeRepo->moveDown($moved_node, $arguments['post']['position'], true);
            }
            \Env::get('cx')->getEvents()->triggerEvent('model/onFlush', array(new \Doctrine\ORM\Event\LifecycleEventArgs($moved_node, $this->em)));

            foreach ($moved_node->getPages() as $page) {
                $page->setupPath($page->getLang());
                $page->setSlug($page->getSlug());
                $page->validate();
                $this->em->persist($page);
            }

            $this->em->persist($moved_node);
            $this->em->persist($parent_node);

            $this->em->flush();
            $this->em->getConnection()->commit();
            $this->clearCache();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }

        $nodeLevels = array();
        $nodeStack = array();
        array_push($nodeStack, $moved_node);
        while (count($nodeStack)) {
            $currentNode = array_pop($nodeStack);
            $nodeLevels[$currentNode->getId()] = $currentNode->getLvl();
            foreach ($currentNode->getChildren() as $child) {
                array_push($nodeStack, $child);
            }
        }

        return array(
            'nodeLevels' => $nodeLevels,
        );
    }

    public function copy($arguments) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) ||
                !\Permission::checkAccess(35, 'static', true)) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }
        if (!\Permission::checkAccess(53, 'static', true)) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_COPY_DENIED']);
        }

        $node = $this->nodeRepo->find($arguments['get']['id']);
        if (!$node) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_COPY_FAILED']);
        }

        // this is necessary to get the position of the original node
        $sortedLevel = array();
        foreach ($node->getParent()->getChildren() as $levelNode) {
            $sortedLevel[$levelNode->getLft()] = $levelNode;
        }
        ksort($sortedLevel);
        $position = 0;
        foreach ($sortedLevel as $sortedNode) {
            $position++;
            if ($sortedNode == $node) {
                break;
            }
        }

        $this->em->getConnection()->beginTransaction();
        try {
            // copy the node recursively and persist changes
            $newNode = $node->copy(true, null, $position, true);
            $this->em->flush();
            // move the node to correct position
            $this->nodeRepo->moveUp($newNode, true);
            $this->nodeRepo->moveDown($newNode, $position, true);
            $this->em->persist($newNode);

            $this->em->flush();
            $this->em->getConnection()->commit();
            $this->clearCache();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Deletes a node
     * @param array $arguments Arguments passed from JsonData
     */
    public function delete($arguments, $flush = true) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) ||
                !\Permission::checkAccess(35, 'static', true)) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }
        if (!\Permission::checkAccess(26, 'static', true)) {
            throw new ContentManagerException($_CORELANG['TXT_CORE_CM_DELETE_DENIED']);
        }

        $node = $this->nodeRepo->find($arguments['post']['id']);
        if (!$node) {
            return array(
                'action'                => 'delete',
                'deletedCurrentPage'    => false,
            );
        }

        // explicit recursive delete in order to ensure logs get written
        // MOVED code down below to NodeEventListener.class.php @method: preRemove();
        /*$toDelete = array($node);
        while (count($toDelete)) {
            $childNodes = array();
            $currentNode = array_pop($toDelete);

            // if we queued this node already, all subnodes have been queued too already, so skip
            if (!in_array($currentNode->getId(), $this->deleteBuffer)) {
                $childNodes = $currentNode->getChildren();
                $this->deleteBuffer[] = $currentNode->getId();
            }

            if (count($childNodes)) {
                // Node has children -> re-queue node for removal.
                // It will be removed after its children have been removed
                array_push($toDelete, $currentNode);

                // queue children to be removed
                foreach ($childNodes as $child) {
                    array_push($toDelete, $child);
                }

            } else {
                $this->em->remove($currentNode);
            }
        }*/
        $this->em->remove($node);
        if ($flush) {
            $this->em->getConnection()->beginTransaction();
            try {
                $this->em->flush();
                $this->em->clear();
                $this->em->getConnection()->commit();
                $this->clearCache();
            } catch (\Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
        }
        return array(
            'action'                => 'delete',
            'deletedCurrentPage'    => (isset($arguments['post']['currentNodeId']) && $arguments['post']['currentNodeId'] == $arguments['post']['id']),
        );
    }

    /**
     * Deletes multiple nodes.
     *
     * @param  array  $param  Client parameters.
     */
    public function multipleDelete($params) {
        $post   = $params['post'];
        $return = array('action' => 'delete');

        $this->em->getConnection()->beginTransaction();
        try {
            foreach ($post['nodes'] as $nodeId) {
                $data['post']['id'] = $nodeId;
                $this->delete($data, false);
                if ($nodeId == $post['currentNodeId']) {
                    $return['deletedCurrentPage'] = true;
                }
            }
            $this->em->flush();
            $this->em->clear();
            $this->em->getConnection()->commit();
            $this->clearCache();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }

        return $return;
    }

    /**
     * Renders a jsTree friendly representation of the Node tree (in json)
     * @return String JSON data
     */
    private function renderTree($rootNodeId = 0, $recursive = false) {
        if ($rootNodeId == 0) {
            $root = $this->nodeRepo->getRoot();
        } else {
            $root = current($this->nodeRepo->findById($rootNodeId));
        }
        if (!is_object($root)) {
            throw new \Exception('Node not found (#' . $rootNodeId . ')');
        }

        $jsondata = $this->tree_to_jstree_array($root, !$recursive/*, $actions*/);

        return $jsondata;
    }

    /**
     * Converts a tree level to JSON
     * @param Cx\Core\ContentManager\Model\Entity\Node $root Root node of the current level
     * @param Array $logs List of all logs (used to get the username)
     * @return String JSON data
     */
    private function tree_to_jstree_array($root, $flat = false, &$actions = null) {
        $fallback_langs = $this->fallbacks;

        $sorted_tree = array();
        foreach ($root->getChildren() as $node) {
            $sorted_tree[$node->getLft()] = $node;
        }
        ksort($sorted_tree);

        // get open nodes
        $open_nodes = array();
        if (isset($_COOKIE[\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN])) {
            $tmp_open_nodes = explode(',', $_COOKIE[\Cx\Core\ContentManager\Controller\ContentManager::JSTREE_COOKIE_OPEN]);
            foreach ($tmp_open_nodes as $node) {
                $node_id = substr($node, 6);
                $open_nodes[$node_id] = true;
            }
        }

        $output     = array();
        $tree       = array();
        $nodeLevels = array();
        foreach ($sorted_tree as $node) {
            $data       = array();
            $metadata   = array();
            $children   = array();

            // if this node is expanded (toggled)
            $toggled = (isset($open_nodes[$node->getId()]) &&
                        $open_nodes[$node->getId()]);
            if (!$flat || $toggled) {
                $children = $this->tree_to_jstree_array($node, $flat);
            }
            $last_resort = 0;

            $numberOfPages = 0;
            /**
             * I (<michael.ritter@comvation.com> cannot recall the reason why to
             * get alias pages too but I think there was one (probably not a nice one)
             * @todo Write unit tests for CM then try $node->getPages()
             * if the above is done do the following too
             * @todo Replace $numberOfPages by $pages = $node->getPages(), then just count them
             */
            foreach ($node->getPages(false, true) as $page) {
                // don't display aliases in cm's tree
                if ($page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS) {
                    continue 2;
                }
                $numberOfPages++;

                $user = $page->getUpdatedBy();
                $data[\FWLanguage::getLanguageCodeById($page->getLang())] = array(
                    'language' => \FWLanguage::getLanguageCodeById($page->getLang()),
                    'title' => $page->getTitle(),
                    'attr' => array(
                        'id' => $page->getId(),
                        'data-href' => json_encode(
                            array(
                                'slug'       => $page->getSlug(),
                                'path'       => $page->getPath(),
                                'module'     => $page->getModule() . ' ' . $page->getCmd(),
                                'lastupdate' => $page->getUpdatedAt()->format('d.m.Y H:i'),
                                'level'      => $page->getNode()->getLvl(),
                                'user'       => $user,
                            )
                        ),
                        'frontend_access_id' => $page->getFrontendAccessId(),
                        'backend_access_id' => $page->getBackendAccessId(),
                        'protected' => $page->isFrontendProtected(),
                        'locked'    => $page->isBackendProtected(),
                    ),
                );

                $editingStatus = $page->getEditingStatus();
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

                $metadata[$page->getId()] = array(
                    'visibility' => $page->getStatus(),
                    'publishing' => $publishingStatus,
                );
                $last_resort = \FWLanguage::getLanguageCodeById($page->getLang());
            }
            if ($numberOfPages == 0) {
                continue;
            }

            foreach ($fallback_langs as $lang => $fallback) {
                // fallback can be false, array_key_exists does not like booleans
                if (!$fallback) {
                    $fallback = null;
                }
                if (!array_key_exists($lang, $data) && array_key_exists($fallback, $data)) {
                    $data[$lang]['language'] = $lang;
                    $data[$lang]['title'] = $data[$fallback]['title'];

                    if ($data[$fallback]['attr']['id'] == 'broken') {
                        $data[$lang]['attr']['id'] = 'broken';
                    } else {
                        $data[$lang]['attr']['id'] = '0';
                    }
                } else if (!array_key_exists($lang, $data)) {
                    $data[$lang]['language'] = $lang;

                    if (array_key_exists($last_resort, $data)) {
                        $data[$lang]['title']      = $data[$last_resort]['title'];
                        $data[$lang]['attr']['id'] = '0';
                    } else {
                        $data[$lang]['title']      = 'No Title';
                        $data[$lang]['attr']['id'] = 'broken';
                    }
                }

                $metadata[0] = array(
                    'visibility' => 'active',
                    'publishing' => 'unpublished',
                );
                $metadata['broken'] = array(
                    'visibility' => 'broken',
                    'publishing' => 'unpublished',
                );
            }

            $state = array();
            if (count($node->getChildren()) > 0) {
                if ($toggled) {
                    $state = array('state' => 'open');
                } else {
                    $state = array('state' => 'closed');
                }
            }

            $nodeLevels[$node->getId()] = $node->getLvl();
            if (isset($children['nodeLevels'])) {
                $nodeLevels = $nodeLevels + $children['nodeLevels'];
            }

            $tree[] = array_merge(array(
                'attr'     => array(
                    'id'   => 'node_' . $node->getId(),
                    'rel_id'   => $node->getId(),
                ),
                'data'     => array_values($data),
                'children' => isset($children['tree']) ? $children['tree'] : array(),
                'metadata' => $metadata,
            ), $state);
        }
        $output['tree']       = $tree;
        $output['nodeLevels'] = $nodeLevels;
        $output['hasHome']    = array();
        foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
            $page = $this->pageRepo->findOneBy(array(
                'module' => 'Home',
                'cmd' => '',
                'lang' => $lang['id'],
            ));
            $output['hasHome'][$lang['lang']] = ($page ? $page->getId() : false);
        }

        return($output);
    }

    /**
     * Gets the page titles of all languages.
     *
     * @return  array  $tree
     */
    public function getPageTitlesTree()
    {
        $root = $this->nodeRepo->getRoot();
        $tree = $this->buildPageTitlesTree($root);

        return $tree;
    }

    /**
     * Builds a tree with all page titles.
     *
     * @param   array  $root
     */
    protected function buildPageTitlesTree($root)
    {
        $sortedTree = array();
        foreach ($root->getChildren() as $node) {
            $sortedTree[$node->getLft()] = $node;
        }
        ksort($sortedTree);

        $tree     = array();
        $children = array();

        foreach ($sortedTree as $node) {
            $children = $this->buildPageTitlesTree($node);

            $nodeId   = $node->getId();
            $langCode = 0;

            foreach ($node->getPages() as $page) {
                $langCode = \FWLanguage::getLanguageCodeById($page->getLang());
                if (!isset($tree[$nodeId])) {
                    $tree[$nodeId] = array();
                }
                if (!isset($tree[$nodeId][$langCode])) {
                    $tree[$nodeId][$langCode] = array();
                }
                $tree[$nodeId][$langCode]['title'] = $page->getTitle();
                $tree[$nodeId][$langCode]['id'] = $page->getId();
                $tree[$nodeId][$langCode]['level'] = $node->getLvl();
            }

            foreach ($this->fallbacks as $lang => $fallback) {
                $fallback = $fallback ? $fallback : null;
                if (isset($tree[$nodeId]) && !array_key_exists($lang, $tree[$nodeId]) && array_key_exists($fallback, $tree[$nodeId])) {
                    if (!isset($tree[$nodeId][$lang])) {
                        $tree[$nodeId][$lang] = array();
                    }
                    $tree[$nodeId][$lang]['title'] = $tree[$nodeId][$fallback]['title'];
                    $tree[$nodeId][$lang]['level'] = $tree[$nodeId][$fallback]['level'];
                } else if (isset($tree[$nodeId]) && !array_key_exists($lang, $tree[$nodeId])) {
                    if (array_key_exists($langCode, $tree[$nodeId])) {
                        if (!isset($tree[$nodeId][$lang])) {
                            $tree[$nodeId][$lang] = array();
                        }
                        $tree[$nodeId][$lang]['title'] = $tree[$nodeId][$langCode]['title'];
                        $tree[$nodeId][$lang]['level'] = $tree[$nodeId][$langCode]['level'];
                    }
                }
            }

            $tree += $children;
        }

        return $tree;
    }

    /**
     * Drops the complete output cache
     */
    protected function clearCache() {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getComponent('Cache')->clearCache();
    }
}
