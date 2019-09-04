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
 * Node
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

namespace Cx\Core\ContentManager\Model\Entity;

/**
 * NodeException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class NodeException extends \Exception {}

/**
 * Node
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class Node extends \Cx\Model\Base\EntityBase implements \Serializable
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $pages;

    /**
     * @var \Cx\Core\ContentManager\Model\Entity\Node
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
     * @param \Cx\Core\ContentManager\Model\Entity\Node $children
     * @return Node
     */
    public function addChild(\Cx\Core\ContentManager\Model\Entity\Node $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Node $children
     */
    public function removeChild(\Cx\Core\ContentManager\Model\Entity\Node $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Add children
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Node $children
     */
    public function addChildren(\Cx\Core\ContentManager\Model\Entity\Node $children)
    {
        $this->children[] = $children;
    }

    public function addParsedChild(\Cx\Core\ContentManager\Model\Entity\Node $child)
    {
        $this->children[] = $child;
    }


    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection $children
     */
    public function getChildren($lang = null)
    {
        $repo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        foreach ($this->children as $i => $child) {
            if (!is_int($child)) continue;
            $this->children[$i] = $repo->find($child);
        }
        return $this->children;

    }

    /**
     * Add a page
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page
     */
    public function addPage(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        $this->pages[] = $page;
    }

    /**
     * Remove pages
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $pages
     */
    public function removePage(\Cx\Core\ContentManager\Model\Entity\Page $pages)
    {
        $this->pages->removeElement($pages);
    }

    /**
     * Get pages
     *
     * @return \Doctrine\Common\Collections\Collection $pages
     */
    public function getPages($inactive_langs = false, $aliases = false)
    {
        $repo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        foreach ($this->pages as $i => $page) {
            if (!is_int($page)) continue;
            $this->pages[$i] = $repo->find($page);
        }
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
     * @return \Cx\Core\ContentManager\Model\Entity\Page
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
     * @param \Cx\Core\ContentManager\Model\Entity\Node $parent
     */
    public function setParent(\Cx\Core\ContentManager\Model\Entity\Node $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent
     *
     * @return \Cx\Core\ContentManager\Model\Entity\Node $parent
     */
    public function getParent()
    {
        if (is_int($this->parent)) {
            $repo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
            $this->parent = $repo->find($this->parent);
        }
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
     * @returns \Cx\Core\ContentManager\Model\Entity\Page the copy
     */
    public function translatePage($activate, $targetLang) {
        $type = \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK;

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
                $type = \Cx\Core\ContentManager\Model\Entity\Page::TYPE_CONTENT;
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
                false,  // includeEditingStatus
                false,  // followRedirects
                true    // followFallbacks
        );
        $page->setActive($activate);
        $page->setType($type);

        $pageToTranslate->setupPath($targetLang);

        return $page;
    }

    /**
     * Creates a copy of this node including its pages
     *
     * @param boolean $recursive (optional) Whether copy all children to the new node or not, default false
     * @param Node    $newParent (optional) New parent node for the copy, default is parent of this
     * @param integer $nodePosition         Node position
     * @param boolean $addCopySuffix        Add the suffix '(Copy)' to the page title if true otherwise not
     * @return \Cx\Core\ContentManager\Model\Entity\Node Copy of this node
     */
    public function copy(
        $recursive = false,
        Node $newParent = null,
        &$nodePosition = 0,
        $addCopySuffix = false
    ) {
        global $_CORELANG;

        $em = $this->cx->getDb()->getEntityManager();
        if (!$newParent) {
            $newParent = $this->getParent();
        }

        $copy = new self();
        $copy->setParent($newParent);
        $em->persist($copy);

        foreach ($this->getPages(true) as $page) {
            $pageCopy = $page->copyToNode($copy, true, true, true, true, true, false);

            $em->persist($pageCopy);
            // Update the $pageCopy title with the suffix '(Copy)' if the $addCopySuffix is true otherwise not
            if ($addCopySuffix) {
                $title = $page->getTitle() . ' (' . $_CORELANG['TXT_CORE_CM_COPY_OF_PAGE'] . ')';
                $i = 1;
                while ($page->titleExists($copy->getParent(), $page->getLang(), $title)) {
                    $i++;
                    if ($page->getLang() == \FWLanguage::getDefaultLangId()) {
                        $nodePosition++;
                    }

                    $title = $page->getTitle() . ' (' . sprintf($_CORELANG['TXT_CORE_CM_COPY_N_OF_PAGE'], $i) . ')';
                }
                $pageCopy->setTitle($title);
            }

            // Copy the draft log of $page to $pageCopy only
            // if the $page editing status is 'hasDraft' or 'hasDraftWaiting'
            if (!$page->isDraft()) {
                continue;
            }

            // Call the flush() method to make the create log for the $pageCopy
            $em->flush();
            // Get the draft log of $page. The draft log is either the latest
            // or the second latest log entry.
            $logRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\LogEntry');
            $logEntriesOfPage = $logRepo->getLogEntries($page, false, 2);
            // fetch second latest log
            $logData = $logEntriesOfPage[1]->getData();
            // check if the second latest log is a draft-log.
            // in case the page has been created directly as draft-log,
            // then the second latest log will not be the draft-log,
            // but the create-log instead.
            // If that is the case, the draft-log is the latest log
            if (
                !isset($logData['editingStatus']) ||
                $logData['editingStatus'] != 'hasDraft'
            ) {
                $logData = $logEntriesOfPage[0]->getData();
            }
            // apply draft data to copied page
            $pageCopy->updateFromArray($logData);
            // Call the flush() method to make the update log for the $pageCopy
            $em->flush();
            // Now revert the $pageCopy to its create log version.
            $cachedPageEditingStatus = $pageCopy->getEditingStatus();
            $logEntriesOfCopyPage    = $logRepo->getLogEntries($pageCopy, false, 1, 1);
            $logRepo->revert($pageCopy, $logEntriesOfCopyPage[0]->getVersion());
            $pageCopy->setEditingStatus($cachedPageEditingStatus);
        }

        if (!$recursive) {
            return $copy;
        }

        foreach ($this->getChildren() as $child) {
            $copy->addParsedChild($child->copy(true, $copy));
        }
        return $copy;
    }

    public function serialize() {
        $parent = $this->getParent();
        $childrenArray = array();
        foreach ($this->children as $child) {
            $childrenArray[] = $child->getId();
        }
        $pagesArray = array();
        foreach ($this->pages as $page) {
            $pagesArray[] = $page->getId();
        }
        return serialize(
            array(
                $this->id,
                $this->lft,
                $this->rgt,
                $this->lvl,
                $parent ? $parent->getId() : null,
                $childrenArray,
                $pagesArray,
            )
        );
    }
    public function unserialize($data) {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();

        //instance counter to provide unique ids
        $this->instance = ++self::$instanceCounter;

        $unserialized = unserialize($data);
        $this->id = $unserialized[0];
        $this->lft = $unserialized[1];
        $this->rgt = $unserialized[2];
        $this->lvl = $unserialized[3];
        if ($unserialized[4]) {
            $this->parent = $unserialized[4];
        }
        foreach ($unserialized[5] as $childId) {
            $this->children[] = $childId;
        }
        foreach ($unserialized[6] as $pageId) {
            $this->pages[] = $pageId;
        }
    }

    /**
     * Check if this node is a child (direct or indirect) of node $parent
     *
     * @param   Node    $parent Node to check if its the parent of this
     *                          instance of
     * @return  boolean Whether this instance is a child of $parent. Whereas
     *                  $parent is not required to be a direct parent of this
     *                  instance.
     */
    public function isChildOf($parent) {
        if (
            // if $this is located within the boundaries
            // of $parent, then $this is a child of $parent
            $parent->getLft() < $this->getLft() &&
            $parent->getRgt() > $this->getRgt() &&
            // this check is just for the case when the tree
            // is broken
            $parent->getLvl() < $this->getLvl()
        ) {
            return true;
        }

        return false;
    }
}
