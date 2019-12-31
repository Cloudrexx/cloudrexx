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
 * Relation
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */

namespace Cx\Core_Modules\Sync\Model\Entity;

/**
 * Relation
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
class Relation extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $lft
     */
    protected $lft;

    /**
     * @var integer $rgt
     */
    protected $rgt;

    /**
     * @var integer $lvl
     */
    protected $lvl;

    /**
     * @var string $localFieldName
     */
    protected $localFieldName;

    /**
     * @var integer $doSync
     */
    protected $doSync;

    /**
     * @var integer $defaultEntityId
     */
    protected $defaultEntityId;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $children;

    /**
     * @var \Cx\Core_Modules\Sync\Model\Entity\Relation
     */
    protected $parent;

    /**
     * @var \Cx\Core_Modules\Sync\Model\Entity\Sync
     */
    protected $relatedSync;

    /**
     * @var \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess
     */
    protected $foreignDataAccess;

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set localFieldName
     *
     * @param string $localFieldName
     */
    public function setLocalFieldName($localFieldName)
    {
        $this->localFieldName = $localFieldName;
    }

    /**
     * Get localFieldName
     *
     * @return string $localFieldName
     */
    public function getLocalFieldName()
    {
        return $this->localFieldName;
    }

    /**
     * Set doSync
     *
     * @param integer $doSync
     */
    public function setDoSync($doSync)
    {
        $this->doSync = $doSync;
    }

    /**
     * Get doSync
     *
     * @return integer $doSync
     */
    public function getDoSync()
    {
        return $this->doSync;
    }

    /**
     * Set defaultEntityId
     *
     * @param integer $defaultEntityId
     */
    public function setDefaultEntityId($defaultEntityId)
    {
        $this->defaultEntityId = $defaultEntityId;
    }

    /**
     * Get defaultEntityId
     *
     * @return integer $defaultEntityId
     */
    public function getDefaultEntityId()
    {
        return $this->defaultEntityId;
    }

    /**
     * Add children
     *
     * @param Cx\Core_Modules\Sync\Model\Entity\Relation $children
     */
    public function addChildren(\Cx\Core_Modules\Sync\Model\Entity\Relation $children)
    {
        $this->children[] = $children;
    }

    /**
     * Add children
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Relation $children
     * @return Relation
     */
    public function addChild(\Cx\Core_Modules\Sync\Model\Entity\Relation $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Relation $children
     */
    public function removeChild(\Cx\Core_Modules\Sync\Model\Entity\Relation $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Set children
     *
     * @param array
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection $children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Relation $parent
     */
    public function setParent(\Cx\Core_Modules\Sync\Model\Entity\Relation $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent
     *
     * @return \Cx\Core_Modules\Sync\Model\Entity\Relation $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set relatedSync
     *
     * @param \Cx\Core_Modules\Sync\Model\Entity\Sync $relatedSync
     */
    public function setRelatedSync(\Cx\Core_Modules\Sync\Model\Entity\Sync $relatedSync)
    {
        $this->relatedSync = $relatedSync;
    }

    /**
     * Get relatedSync
     *
     * @return \Cx\Core_Modules\Sync\Model\Entity\Sync $relatedSync
     */
    public function getRelatedSync()
    {
        return $this->relatedSync;
    }

    /**
     * Set foreignDataAccess
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $foreignDataAccess
     */
    public function setForeignDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $foreignDataAccess)
    {
        $this->foreignDataAccess = $foreignDataAccess;
    }

    /**
     * Get foreignDataAccess
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $foreignDataAccess
     */
    public function getForeignDataAccess()
    {
        return $this->foreignDataAccess;
    }
}
