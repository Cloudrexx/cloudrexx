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
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 */

namespace Cx\Core_Modules\MediaBrowser\Model\Entity;


class MediaBrowserPageTree extends \Cx\Core\PageTree\PageTree
{
    protected $return = array();

    protected function preRenderElement($level, $hasChilds, $lang, $page) {
        // TODO: Implement preRenderElement() method.
    }

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
    protected function renderElement(
        $title, $level, $hasChilds, $lang, $path, $current, $page
    ) {
        $url = (string)\Cx\Core\Routing\NodePlaceholder::fromNode(
            $page->getNode(),
            null,
            array()
        );
        $pages = $page->getNode()->getPages();
        $titles = array();
        $locales = array();
        foreach ($pages as $page) {
            $locale = \FWLanguage::getLanguageCodeById($page->getLang());
            $titles[$locale] = $page->getTitle();
            $nodePlaceholder = (string)\Cx\Core\Routing\NodePlaceholder::fromNode(
                $page->getNode(),
                $page->getLang(),
                array()
            );
            $locales[$locale] = array(
                'url'   => $page->getPath(),
                'node'  => $nodePlaceholder,
                'name'  => $page->getTitle(),
            );
        }
        $this->return[] = array(
            'click' =>
                "javascript:{setUrl('$url',null,null,'"
                . \FWLanguage::getLanguageCodeById(
                    BACKEND_LANG_ID
                )
                . $path . "','page')}",
            'name' => $titles,
            'extension' => 'Html',
            'level' => $level - 1,
            'url' => $path,
            'node' => $url,
            'localization' => $locales,
        );
    }

    protected function postRenderElement($level, $hasChilds, $lang, $page) {
        // TODO: Implement postRenderElement() method.
    }

    public function preRenderLevel($level, $lang, $parentNode) {
        // TODO: Implement preRenderLevel() method.
    }

    public function postRenderLevel($level, $lang, $parentNode) {
        // TODO: Implement postRenderLevel() method.
    }

    protected function renderHeader($lang) {
        // TODO: Implement renderHeader() method.
    }

    protected function renderFooter($lang) {
        // TODO: Implement renderFooter() method.
    }

    protected function preRender($lang) {
        // TODO: Implement preRender() method.
    }

    protected function postRender($lang) {
        // TODO: Implement postRender() method.
    }

    public function getFlatTree() {
        return $this->return;
    }

    /**
     * Called on construction. Override if you do not want to override the ctor.
     */
    protected function init() {
        // TODO: Implement init() method.
    }

    protected function getFullNavigation() {
        return true;
    }
}
