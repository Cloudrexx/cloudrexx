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
 * NestedNavigationPageTree
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */

namespace Cx\Core\PageTree;

/**
 * NestedNavigationPageTree
 *
 * Build nested navigation menu with unordered list
 * if [[nested_navigation]] is placed in navbar.
 * Formatting should be done with CSS.
 * Tags (ul and li) are inserted by the code.
 *
 * Navigation can be restricted to specific levels with the tag [[levels_AB]],
 * where A and B can take following values:
 *    starting level A: [1-9]
 *    ending level B: [1-9], [+] or [];
 *              [+]: any level starting from A;
 *              [] : just level A;
 *    examples: [[levels_24]] means navigation levels 2 to 4;
 *              [[levels_3+]] means any navigation levels starting from 3;
 *              [[levels_1]] means navigation level 1 only;
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */
class NestedNavigationPageTree extends SigmaPageTree {
    const CssPrefix = "menu_level_";
    const StyleNameActive = "active";
    const StyleNameNormal = "inactive";

    protected $levelFrom = 1;
    protected $levelTo = 0; //0 means unbounded
    protected $navigationIds = array();
    protected $listCompleteTree = false;

    protected $lastLevel = 0; //level of last item, used to remember how much closing tags we need.

    protected $branchNodeIds = array(); //holds all ids of the $currentPage's Node and it's parents

    public function __construct($entityManager, $license, $maxDepth = 0, $activeNode = null, $lang = null, $currentPage = null) {
        parent::__construct($entityManager, $license, $maxDepth, $activeNode, $lang, $currentPage, false);
        $this->activeNode = $activeNode;
        if (!$this->activeNode) {
            $this->activeNode = \Env::get('em')->getRepository('\Cx\Core\ContentManager\Model\Entity\Node')->getRoot();
        }

        //go up the branch and collect all node ids. used later in renderElement().
        $node = $currentPage->getNode();
        while ($node) {
            $this->branchNodeIds[] = $node->getId();
            $node = $node->getParent();
        }
    }

    protected function getFirstLevel() {
        $match = array();
        if (preg_match('/levels_([1-9])([1-9\+]*)(_full|_branch)?/', trim($this->template->_blocks['nested_navigation']), $match)) {
            return intval($match[1]);
        }
        return 1;
    }

    protected function getLastLevel() {
        $match = array();
        if (preg_match('/levels_([1-9])([1-9\+]*)(_full|_branch)?/', trim($this->template->_blocks['nested_navigation']), $match)) {
            if($match[2] != '+')
                return intval($match[2]);
        }
        return 0;
    }

    protected function getFullNavigation() {
        $match = array();
        if (preg_match('/levels_([1-9])([1-9\+]*)(_full|_branch)?/', trim($this->template->_blocks['nested_navigation']), $match)) {
            if(isset($match[3]))
                return true;
        }
        return false;
    }

    protected function realPreRender($lang) {
        // checks which levels to use
        // default is 1+ (all)
        $match = array();
        if (preg_match('/levels_([1-9])([1-9\+]*)(_full|_branch)?/', trim($this->template->_blocks['nested_navigation']), $match)) {
            $this->levelFrom = $match[1];
            if($match[2] != '+')
                $this->levelTo = intval($match[2]);
            $this->listCompleteTree = !empty($match[3]);
        }
    }

    protected function renderElement($title, $level, $hasChilds, $lang, $path, $current, $page) {
        //make sure the page to render is inside our branch
        if (!$this->isParentNodeInsideCurrentBranch($page->getNode())) {
            return '';
        }

        //are we inside the layer bounds?
        if (!$this->isLevelInsideLayerBound($level)) {
            return '';
        }

        if (!$page->isVisible()) {
            return '';
        }
        $node = $page->getNode();
        reset($this->branchNodeIds);
        while ($node && $node->getId() != $this->activeNode->getId()) {
            if ($node->getPage(FRONTEND_LANG_ID) && !$node->getPage(FRONTEND_LANG_ID)->isVisible()) {
                return '';
            }
            $node = $node->getParent();
        }

        if (!isset($this->navigationIds[$level]))
            $this->navigationIds[$level] = 0;
        else
            $this->navigationIds[$level]++;

        $block = trim($this->template->_blocks['level']);

        $output = "  <li>".$block;

        //check if we need to close any <ul>'s
        $this->lastLevel = $level;

        $style = $current ? self::StyleNameActive : self::StyleNameNormal;
        $output = str_replace('{NAME}', contrexx_raw2xhtml($title), $output);
        $output = str_replace('<li>', '<li class="'.$style.'">', $output);
        $output = str_replace('{URL}', \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath().$this->virtualLanguageDirectory.contrexx_raw2encodedUrl($path), $output);
        $linkTarget = $page->getLinkTarget();
        $output = str_replace('{TARGET}', empty($linkTarget) ? '_self' : $linkTarget, $output);
        $output = str_replace('{CSS_NAME}',  $page->getCssNavName(), $output);
        $output = str_replace('{PAGE_ID}', $page->getId(), $output);
        $output = str_replace('{PAGE_NODE_ID}', $page->getNode()->getId(), $output);
        $output = str_replace('{NAVIGATION_ID}', $this->navigationIds[$level], $output);

        return $output;
    }
    protected function postRenderElement($level, $hasChilds, $lang, $page)
    {
        //make sure the node to render is inside our branch
        if (!$this->isParentNodeInsideCurrentBranch($page->getNode())) {
            return '';
        }

        //are we inside the layer bounds?
        if (!$this->isLevelInsideLayerBound($level)) {
            return '';
        }

        if (!$page->isVisible()) {
            return '';
        }
        $node = $page->getNode();
        reset($this->branchNodeIds);
        while ($node && $node->getId() != $this->activeNode->getId()) {
            if ($node->getPage(FRONTEND_LANG_ID) && !$node->getPage(FRONTEND_LANG_ID)->isVisible()) {
                return '';
            }
            $node = $node->getParent();
        }

        $output = '';
        if (!$hasChilds || !$this->isLevelInsideLayerBound($level + 1)) {
                $output .= "  </li>\n";
        }

        return $output;
    }

    public function preRenderLevel($level, $lang, $parentNode) {
        //make sure the node to render is inside our branch
        if (!$this->isNodeInsideCurrentBranch($parentNode)) {
            return '';
        }

        //are we inside the layer bounds?
        if (!$this->isLevelInsideLayerBound($level)) {
            return '';
        }

        $node = $parentNode;
        reset($this->branchNodeIds);
        while ($node && $node->getId() != $this->activeNode->getId()) {
            if ($node->getPage(FRONTEND_LANG_ID) && !$node->getPage(FRONTEND_LANG_ID)->isVisible()) {
                return '';
            }
            $node = $node->getParent();
        }

        $visibleChildren = false;
        foreach ($parentNode->getChildren() as $child) {
            if ($child->getPage(FRONTEND_LANG_ID) && $child->getPage(FRONTEND_LANG_ID)->isVisible()) {
                $visibleChildren = true;
                break;
            }
        }
        if (!$visibleChildren) {
            return '';
        }
        return "\n" . '<ul class="'.self::CssPrefix.$level.'">'."\n";
    }

    public function postRenderLevel($level, $lang, $parentNode) {
        //make sure the node to render is inside our branch
        if (!$this->isNodeInsideCurrentBranch($parentNode)) {
            return '';
        }

        //are we inside the layer bounds?
        if (!$this->isLevelInsideLayerBound($level)) {
            return '';
        }
        if ($level == $this->levelFrom) {
            return '</ul>' . "\n";
        }

        $node = $parentNode;
        reset($this->branchNodeIds);
        while ($node && $node->getId() != $this->activeNode->getId()) {
            if ($node->getPage(FRONTEND_LANG_ID) && !$node->getPage(FRONTEND_LANG_ID)->isVisible()) {
                return '';
            }
            $node = $node->getParent();
        }

        $visibleChildren = false;
        foreach ($parentNode->getChildren() as $child) {
            if ($child->getPage(FRONTEND_LANG_ID) && $child->getPage(FRONTEND_LANG_ID)->isVisible()) {
                $visibleChildren = true;
                break;
            }
        }
        if (!$visibleChildren) {
            return '';
        }
        return '</ul>' . "\n" . '</li>'."\n";
    }

    private function isNodeInsideCurrentBranch($node)
    {
        $match = array();
        $branch = false;
        if (preg_match('/levels_([1-9])([1-9\+]*)(_branch)?/', trim($this->template->_blocks['nested_navigation']), $match)) {
            if (isset($match[3])) {
                $branch = true;
            }
        }

        if ($branch) {
            while ($node->getLvl() >= $this->getFirstLevel() && $node->getParent()) {
                $node = $node->getParent();
            }
        } else if ($this->listCompleteTree) {
            return true;
        }

        return in_array($node->getId(), $this->branchNodeIds);
    }

    private function isParentNodeInsideCurrentBranch($node)
    {
        if (!$node->getParent()) {
            return true;
        }
        return $this->isNodeInsideCurrentBranch($node->getParent());
    }

    private function isLevelInsideLayerBound($level)
    {
        return    $level >= $this->levelFrom
               && (   $level <= $this->levelTo
                   || $this->levelTo == 0);
    }

    protected function renderHeader($lang) {}

    protected function renderFooter($lang) {}

    protected function preRenderElement($level, $hasChilds, $lang, $page) {}

    protected function postRender($lang) {}

    /**
     * Called on construction. Override if you do not want to override the ctor.
     */
    protected function init() {}
}
