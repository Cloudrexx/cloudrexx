<?php

/**
 * Node
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  model_contentmanager
 */

namespace Cx\Core\ContentManager\Model\Doctrine\Entity;

/**
 * NodeException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  model_contentmanager
 */
class NodeException extends \Exception {}

/**
 * Node
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  model_contentmanager
 */
class Node extends \Cx\Model\Base\EntityBase
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $lft
     */
    private $lft;

    /**
     * @var integer $rgt
     */
    private $rgt;

    /**
     * @var integer $lvl
     */
    private $lvl;

    /**
     * @var Cx\Core\ContentManager\Model\Doctrine\Entity\Node
     */
    private $children;

    /**
     * @var Cx\Core\ContentManager\Model\Doctrine\Entity\Page
     */
    private $pages;

    /**
     * @var Cx\Core\ContentManager\Model\Doctrine\Entity\Node
     */
    private $parent;

    private static $instanceCounter = 0;
    private $instance = 0;

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();      

        //instance counter to provide unique ids
        $this->instance = ++self::$instanceCounter;
    }

    /**
     * Returns an unique identifier that is usable even if 
     * no id is set yet.
     * The Cx\Model\Events\PageEventListener uses this.
     *
     * @return string
     */
    public function getUniqueIdentifier() {
        $id = $this->getId();
        if($id)
            return ''.$id;
        else
            return 'i'.$this->instance;
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     */
    public function setLft($lft)
    {
        $this->lft = $lft;
    }

    /**
     * Get lft
     *
     * @return integer $lft
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    }

    /**
     * Get rgt
     *
     * @return integer $rgt
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
    }

    /**
     * Get lvl
     *
     * @return integer $lvl
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Add children
     *
     * @param Cx\Core\ContentManager\Model\Doctrine\Entity\Node $children
     */
    public function addChildren(\Cx\Core\ContentManager\Model\Doctrine\Entity\Node $children)
    {
        $this->children[] = $children;
    }

    public function addParsedChild(\Cx\Core\ContentManager\Model\Doctrine\Entity\Node $child)
    {
        $this->children[] = $child;
    }
    

    /**
     * Get children
     *
     * @return Doctrine\Common\Collections\Collection $children
     */
    public function getChildren($lang = null)
    {
        return $this->children;

    }

    /**
     * Add a page
     *
     * @param Cx\Core\ContentManager\Model\Doctrine\Entity\Page $page
     */
    public function addPage(\Cx\Core\ContentManager\Model\Doctrine\Entity\Page $page)
    {
        $this->pages[] = $page;
    }

    /**
     * Get pages
     *
     * @return Doctrine\Common\Collections\Collection $pages
     */
    public function getPages($inactive_langs = false, $aliases = false)
    {
        if ($inactive_langs) {
            return $this->pages;
        }
        $activeLangs = \FWLanguage::getActiveFrontendLanguages();
        $pages = array();
        foreach ($this->pages as $page) {
            if (in_array($page->getLang(), array_keys($activeLangs)) || ($aliases && $page->getLang() == 0)) {
                $pages[] = $page;
            }
        }
        return $pages;
    }


    public function getPagesByLang($inactive_langs = false)
    {
        $pages = $this->getPages($inactive_langs);
        $result = array();

        foreach($pages as $page){
            $result[$page->getLang()] = $page;
        }

        return $result;
    }

    /**
     * Get a certain Page 
     *
     * @param integer $lang
     * @return \Cx\Core\ContentManager\Model\Doctrine\Entity\Page
     */
    public function getPage($lang)
    {
        $pages = $this->getPages(true);

        foreach($pages as $page){
            if($page->getLang() == $lang) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Set parent
     *
     * @param Cx\Core\ContentManager\Model\Doctrine\Entity\Node $parent
     */
    public function setParent(\Cx\Core\ContentManager\Model\Doctrine\Entity\Node $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent
     *
     * @return Cx\Core\ContentManager\Model\Doctrine\Entity\Node $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @prePersist
     */
    public function validate()
    {
        //workaround, this method is regenerated each time
        parent::validate(); 
    }

    /**
     * Check whether the current user has access to this node.
     *
     * @param boolean $frontend whether front- or backend. defaults to frontend
     * @return boolean
     */
    public function hasAccessByUserId($frontend = true) {
        $type = 'node_' . ($frontend ? 'frontend' : 'backend');
        return Permission::checkAccess($this->id, $type, true);        
    }
    
    /**
     * Creates a translated page in this node
     *
     * Does not flush EntityManager.
     *
     * @param boolean $activate whether the new page should be activated
     * @param int $targetLang target language id
     * @returns \Cx\Core\ContentManager\Model\Doctrine\Entity\Page the copy
     */
    public function translatePage($activate, $targetLang) {
        $type = \Cx\Core\ContentManager\Model\Doctrine\Entity\Page::TYPE_FALLBACK;
        
        $fallback_language = \FWLanguage::getFallbackLanguageIdById($targetLang);
        $defaultLang = \FWLanguage::getDefaultLangId();
        
        // copy the corresponding language version (if there is one)
        if ($fallback_language && $this->getPage($fallback_language)) {
            $pageToTranslate = $this->getPage($fallback_language);
        
        // find best page to copy if no corresponding language version is present
        } else {
            if ($this->getPage($defaultLang)) {
                $pageToTranslate = $this->getPage($defaultLang);
            } else {
                $pages = $this->getPages();
                $pageToTranslate = $pages[0];
            }
            if (!$fallback_language) {
                $type = \Cx\Core\ContentManager\Model\Doctrine\Entity\Page::TYPE_CONTENT;
            }
        }
        
        // copy page following redirects
        $page = $pageToTranslate->copyToLang(
                $targetLang,
                true,   // includeContent
                true,   // includeModuleAndCmd
                true,   // includeName
                true,   // includeMetaData
                true,   // includeProtection
                false,  // followRedirects
                true    // followFallbacks
        );
        $page->setActive($activate);
        $page->setType($type);
        
        $page->setupPath($targetLang);
        
        return $page;
    }
}
