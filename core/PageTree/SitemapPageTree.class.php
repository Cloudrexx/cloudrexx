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
 * SitemapPageTree
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */

namespace Cx\Core\PageTree;

/**
 * SitemapPageTree
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */
class SitemapPageTree extends SigmaPageTree {
    protected $spacer = null;
    const cssPrefix = "sitemap_level";
    const subTagStart = "<ul>";
    const subTagEnd = "</ul>";

    /**
     * Override the constructor from the PageTree
     * @see Cx\Core\PageTree::__construct()
     * @param type $entityManager
     * @param type $license
     * @param type $maxDepth
     * @param type $rootNode
     * @param type $lang
     * @param type $currentPage
     * @param type $skipInvisible
     * @param type $considerLogin
     */
    public function __construct($entityManager, $license, $maxDepth = 0, $rootNode = null,
                                $lang = null, $currentPage = null, $skipInvisible = true,
                                $considerLogin = false
    ) {
        parent::__construct($entityManager, $license, $maxDepth, $rootNode, $lang,
                            $currentPage, $skipInvisible, $considerLogin);
    }

    protected function renderHeader($lang) {
    }

    protected function renderElement($title, $level, $hasChilds, $lang, $path, $current, $page) {
        $width = $level*25;
        $spacer = "<img src='".ASCMS_CORE_MODULE_FOLDER."/Sitemap/View/Media/spacer.gif' width='$width' height='12' alt='' />";
        $linkTarget = $page->getLinkTarget();
        $this->template->setVariable(array(
            'STYLE'     => self::cssPrefix .'_' . $level,
            'SPACER'    => $spacer,
            'NAME'      => $title,
            'TARGET'    => empty($linkTarget) ? '_self' : $linkTarget,
            'URL'       => \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath().$this->virtualLanguageDirectory.$path
        ));

        $this->template->parse('sitemap');
    }

    public function preRenderLevel($level, $lang, $parentNode) {}

    public function postRenderLevel($level, $lang, $parentNode) {}

    protected function renderFooter($lang) {
    }

    protected function init() {

    }

    protected function postRender($lang) {

    }

    protected function postRenderElement($level, $hasChilds, $lang, $page) {

    }

    protected function realPreRender($lang) {

    }

    protected function preRenderElement($level, $hasChilds, $lang, $page) {

    }

    protected function getFullNavigation(){
        return true;
    }
}
