<?php

/**
 * SitemapPageTree
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  core_pagetree
 */

namespace Cx\Core\PageTree;

/**
 * SitemapPageTree
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
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
