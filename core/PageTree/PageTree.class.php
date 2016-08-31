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
 * PageTree
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */

namespace Cx\Core\PageTree;

/**
 * Base class for all kinds of trees such as Sitemaps and Navigation.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */
abstract class PageTree {
    protected static $virtualPagesAdded = false;
    protected $lang = null;
    protected $rootNode = null;
    protected $depth = null;
    protected $em = null;
    protected $license = null;
    protected $currentPage = null;
    protected $pageIdsAtCurrentPath = array();
    protected $currentPageOnRootNode = false;
    protected $currentPagePath = null;
    protected $pageRepo = null;
    protected $skipInvisible = true;
    protected $considerLogin = true;
    protected $skipInactive;

    /**
     * Consider seo enabled pages alone
     *
     * @var Boolean
     */
    protected $considerSeoEnabledOnly;

    /**
     * @param $entityManager the doctrine em
     * @param \Cx\Core_Modules\License\License $license License used to check if a module is allowed in frontend
     * @param int $maxDepth maximum depth to fetch, 0 means everything
     * @param \Cx\Core\ContentManager\Model\Entity\Node $rootNode node to use as root
     * @param int $lang the language
     * @param \Cx\Core\ContentManager\Model\Entity\Page $currentPage if set, renderElement() will receive a correctly set $current flag.
     * @param bool $skipInvisible value to skip invisible pages
     * @param bool $considerLogin value to consider whether the user is logged in or not
     * @param boolean $considerSeoEnabledOnly   Consider seo enabled pages alone
     */
    public function __construct($entityManager, $license, $maxDepth = 0, $rootNode = null, $lang = null, $currentPage = null, $skipInvisible = true, $considerLogin = true, $skipInactive = true, $considerSeoEnabledOnly = false) {
        $this->lang = $lang;
        $this->depth = $maxDepth;
        $this->em = $entityManager;
        $this->license = $license;
        $this->rootNode = $rootNode;
        $this->currentPage = $currentPage;
        $this->skipInvisible = $skipInvisible;
        $this->considerLogin = $considerLogin;
        $this->skipInactive = $skipInactive;
        $this->considerSeoEnabledOnly = $considerSeoEnabledOnly;
        $pageI = $currentPage;
        while ($pageI) {
            $this->pageIdsAtCurrentPath[] = $pageI->getId();
            try {
                $pageI = $pageI->getParent();
            } catch (\Cx\Core\ContentManager\Model\Entity\PageException $e) {
                $pageI = null;
            }
        }
        $this->startLevel = 1;
        $this->startPath = '';
        $this->pageRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $this->nodeRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        if (!$this->rootNode) {
            $this->rootNode = $this->nodeRepo->getRoot();
        }
        $this->init();
    }

    /**
     * returns the string representation of the tree.
     *
     * @return string
     */
    public function render() {
        $content = $this->preRender($this->lang);
        $content .= $this->renderHeader($this->lang);
        $this->bytes = memory_get_peak_usage();
        $content .= $this->internalRender($this->rootNode, $this->currentPageOnRootNode);
//echo 'PageTree2(' . get_class($this) . '): ' . formatBytes(memory_get_peak_usage()-$this->bytes) . '<br />';
        $content .= $this->renderFooter($this->lang);
        $content .= $this->postRender($this->lang);
        return $content;
    }

    /**
     * @todo Virtual pages!
     * @param type $nodes
     * @param type $level
     * @param type $dontDescend
     */
    private function internalRender($node, $dontDescend = false) {
        global $_CONFIG;
        $content = '';
        $nodeStack = array();
        array_push($nodeStack, $node);

        $q = $this->em->createQuery("SELECT n FROM Cx\Core\ContentManager\Model\Entity\Node n JOIN n.pages p WHERE p.type != 'alias' AND n.lft > ?1 AND n.rgt < ?2 AND p.lang = ?3 ORDER BY n.lft  ASC");

        $q->setParameter(1, $node->getLft());
        $q->setParameter(2, $node->getRgt());
        $q->setParameter(3, $this->lang);

        $children = $q->getResult();

        $nodeArray = array();
        foreach ($children as $child) {
            $nodeArray[$child->getParent()->getId()][] = $child;
        }

        $lastLevel = $this->getLastLevel();
        while (count($nodeStack)) {
            $node = array_pop($nodeStack);
            if (is_callable($node)) {
                $content .= $node();
                continue;
            }
            $page = $node->getPage($this->lang);
            if (
                ($node->getLvl() == $lastLevel && $lastLevel > 0) ||
                (
                    !$this->getFullNavigation() && // don't show full navigation
                    $page && // don't be on root node
                    !in_array($page->getId(), $this->pageIdsAtCurrentPath) // I am not on active page
                )
            ) {
                // hide children
                $children = array();
            } else {
                $children = isset($nodeArray[$node->getId()]) ? $nodeArray[$node->getId()] : array();
            }

            $page = $node->getPage($this->lang);
            $hasChilds = false;
            if ($this->skipInvisible) {
                if (!$page || ($page->isVisible() && $page->isActive())) {
                    foreach ($children as $child) {
                        $childPage = $child->getPage($this->lang);
                        if ($childPage && $childPage->isVisible() && $childPage->isActive()) {
                            $hasChilds = true;
                            break;
                        }
                    }
                }
            } else {
                if (!$page || (!$this->skipInactive || $page->isActive())) {
                    foreach ($children as $child) {
                        $childPage = $child->getPage($this->lang);
                        if ($childPage && $childPage->isActive()) {
                            $hasChilds = true;
                            break;
                        }
                    }
                }
            }

            //if page is protected, protected pages are hidden and user has not
            //sufficent permissions, then hide all child elements from this page
            if($page && $page->isFrontendProtected() && $_CONFIG['coreListProtectedPages'] != 'on' &&
               !(
                    \Permission::checkAccess($page->getFrontendAccessId(), 'dynamic', true) &&
                    $this->considerLogin
                )
            ){
                $hasChilds = false;
            }

            if ($hasChilds && !$dontDescend) {
                // add preRenderLevel to stack
                $pageTree = $this;
                $level = $node->getLvl() + 1;
                $lang = $this->lang;
                array_push($nodeStack, function() use($pageTree, &$content, $level, $lang, $node) {
                    return $pageTree->postRenderLevel($level, $lang, $node);
                });
                // add children to stack
                $children = array_reverse($children, true);
                foreach ($children as $child) {
                    array_push($nodeStack, $child);
                }
                // add postRenderLevel to stack
                array_push($nodeStack, function() use($pageTree, &$content, $level, $lang, $node) {
                    return $pageTree->preRenderLevel($level, $lang, $node);
                });
            }

            if (
                   !$page
                || ($this->skipInvisible && !$page->isVisible())
                || ($this->skipInactive && !$page->isActive())
                || ($this->considerSeoEnabledOnly && !$page->getMetarobots())
            ) {
                continue;
            }

            try {
                $parentPage = $page->getParent();
                // if parent is invisible or unpublished and parent node is not start node
                if ($parentPage &&
                    (
                        ($this->skipInvisible && !$parentPage->isVisible()) ||
                        ($this->skipInactive && !$parentPage->isActive())
                    ) &&
                    $page->getNode()->getParent()->getId() != $this->rootNode->getId()
                ) {
                    continue;
                }
            } catch (\Cx\Core\ContentManager\Model\Entity\PageException $e) {
                // if parent page does not exist, parent is root
            }


            // if page is protected, user has not sufficent permissions and protected pages are hidden
            if ($page->isFrontendProtected() && $_CONFIG['coreListProtectedPages'] != 'on' &&
                !(
                    \Permission::checkAccess($page->getFrontendAccessId(), 'dynamic', true) &&
                    $this->considerLogin
                )
            ) {
                continue;
            }

            if ($page->getModule() != '' && !$this->license->isInLegalFrontendComponents($page->getModule())) {
                continue;
            }

            // prepare data for element
            $current = in_array($page->getId(), $this->pageIdsAtCurrentPath);

            $href = $page->getPath();
            if (isset($_GET['pagePreview']) && $_GET['pagePreview'] == 1) {
                $href .= '?pagePreview=1';
            }

            $bytes = memory_get_peak_usage();
            $content .= $this->preRenderElement($node->getLvl(), $hasChilds, $this->lang, $page);
            $content .= $this->renderElement($page->getTitle(), $node->getLvl(), $hasChilds, $this->lang, $href, $current, $page);
            $content .= $this->postRenderElement($node->getLvl(), $hasChilds, $this->lang, $page);
            $bytes = memory_get_peak_usage()-$bytes;
            $this->bytes = $this->bytes + $bytes;
        }
        return $content;
    }

    /**
     * Tells wheter $pathToPage is in the active branch
     * @param String $pathToPage
     * @return boolean True if active, false otherwise
     */
    public function isPagePathActive($pathToPage) {
        if ($pathToPage == '') {
            return false;
        }

        $pathToPage = str_replace('//', '/', $pathToPage . '/');
        return substr($this->currentPagePath . '/', 0, strlen($pathToPage)) == $pathToPage;
    }

    public function setVirtualLanguageDirectory($dir) {
        $this->virtualLanguageDirectory = $dir;
    }

    protected abstract function preRenderElement($level, $hasChilds, $lang, $page);
    /**
     * Override this to do your representation of the tree.
     *
     * @param string $title
     * @param int $level 0-based level of the element
     * @param boolean $hasChilds are there children of this element? if yes, they will be processed in the subsequent calls.
     * @param int $lang language id
     * @param string $path path to this element, e.g. '/CatA/CatB'
     * @param boolean $current if a $currentPage has been specified, this will be set to true if either a parent element of the current element or the current element itself is rendered.
     *
     * @return string your string representation of the element.
     */
    protected abstract function renderElement($title, $level, $hasChilds, $lang, $path, $current, $page);

    protected abstract function postRenderElement($level, $hasChilds, $lang, $page);

    public abstract function preRenderLevel($level, $lang, $parentNode);

    public abstract function postRenderLevel($level, $lang, $parentNode);

    protected abstract function renderHeader($lang);

    protected abstract function renderFooter($lang);

    protected abstract function preRender($lang);

    protected abstract function postRender($lang);

    /**
     * Called on construction. Override if you do not want to override the ctor.
     */
    protected abstract function init();

    protected function getFirstLevel() {
        return 1; // show from first level
    }

    protected function getLastLevel() {
        return 0; // show all levels
    }

    protected function getFullNavigation() {
        return false;
    }
}
