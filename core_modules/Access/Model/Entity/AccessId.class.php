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


namespace Cx\Core_Modules\Access\Model\Entity;

/**
 * Cx\Core_Modules\Access\Model\Entity\AccessId
 */
class AccessId extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $entity_class_name
     */
    private $entity_class_name;

    /**
     * @var string $entity_class_id
     */
    private $entity_class_id;

    /**
     * @var Cx\Core\User\Model\Entity\UserAttribute
     */
    private $contrexxAccessUserAttribute;

    /**
     * @var Cx\Core\User\Model\Entity\UserAttribute
     */
    private $contrexxAccessUserAttributeRead;

    /**
     * @var Cx\Core\User\Model\Entity\CoreAttribute
     */
    private $coreAttribute;

    /**
     * @var Cx\Core\User\Model\Entity\CoreAttribute
     */
    private $coreAttributeRead;

    /**
     * @var Cx\Core\User\Model\Entity\Group
     */
    private $group2;

    /**
     * @var Cx\Core\User\Model\Entity\Group
     */
    private $group;

    public function __construct()
    {
        $this->contrexxAccessUserAttribute = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contrexxAccessUserAttributeRead = new \Doctrine\Common\Collections\ArrayCollection();
        $this->coreAttribute = new \Doctrine\Common\Collections\ArrayCollection();
        $this->coreAttributeRead = new \Doctrine\Common\Collections\ArrayCollection();
        $this->group2 = new \Doctrine\Common\Collections\ArrayCollection();
        $this->group = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set entity_class_name
     *
     * @param string $entityClassName
     */
    public function setEntityClassName($entityClassName)
    {
        $this->entity_class_name = $entityClassName;
    }

    /**
     * Get entity_class_name
     *
     * @return string $entityClassName
     */
    public function getEntityClassName()
    {
        return $this->entity_class_name;
    }

    /**
     * Set entity_class_id
     *
     * @param string $entityClassId
     */
    public function setEntityClassId($entityClassId)
    {
        $this->entity_class_id = $entityClassId;
    }

    /**
     * Get entity_class_id
     *
     * @return string $entityClassId
     */
    public function getEntityClassId()
    {
        return $this->entity_class_id;
    }

    /**
     * Add contrexxAccessUserAttribute
     *
     * @param Cx\Core\User\Model\Entity\UserAttribute $contrexxAccessUserAttribute
     */
    public function addContrexxAccessUserAttribute(\Cx\Core\User\Model\Entity\UserAttribute $contrexxAccessUserAttribute)
    {
        $this->contrexxAccessUserAttribute[] = $contrexxAccessUserAttribute;
    }

    /**
     * Get contrexxAccessUserAttribute
     *
     * @return Doctrine\Common\Collections\Collection $contrexxAccessUserAttribute
     */
    public function getContrexxAccessUserAttribute()
    {
        return $this->contrexxAccessUserAttribute;
    }

    /**
     * Add coreAttribute
     *
     * @param Cx\Core\User\Model\Entity\CoreAttribute $coreAttribute
     */
    public function addCoreAttribute(\Cx\Core\User\Model\Entity\CoreAttribute $coreAttribute)
    {
        $this->coreAttribute[] = $coreAttribute;
    }

    /**
     * Get coreAttribute
     *
     * @return Doctrine\Common\Collections\Collection $coreAttribute
     */
    public function getCoreAttribute()
    {
        return $this->coreAttribute;
    }

    /**
     * Add group2
     *
     * @param Cx\Core\User\Model\Entity\Group $group2
     */
    public function addGroup2(\Cx\Core\User\Model\Entity\Group $group2)
    {
        $this->group2[] = $group2;
    }

    /**
     * Get group2
     *
     * @return Doctrine\Common\Collections\Collection $group2
     */
    public function getGroup2()
    {
        return $this->group2;
    }

    /**
     * Add group
     *
     * @param Cx\Core\User\Model\Entity\Group $group
     */
    public function addGroup(\Cx\Core\User\Model\Entity\Group $group)
    {
        $this->group[] = $group;
    }

    /**
     * Get group
     *
     * @return Doctrine\Common\Collections\Collection $group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Add contrexxAccessUserAttributeRead
     *
     * @param Cx\Core\User\Model\Entity\UserAttribute $contrexxAccessUserAttributeRead
     */
    public function addContrexxAccessUserAttributeRead(\Cx\Core\User\Model\Entity\UserAttribute $contrexxAccessUserAttributeRead)
    {
        $this->contrexxAccessUserAttributeRead[] = $contrexxAccessUserAttributeRead;
    }

    /**
     * Get contrexxAccessUserAttributeRead
     *
     * @return Doctrine\Common\Collections\Collection $contrexxAccessUserAttribute
     */
    public function getContrexxAccessUserAttributeRead()
    {
        return $this->contrexxAccessUserAttributeRead;
    }

    /**
     * Add coreAttributeRead
     *
     * @param Cx\Core\User\Model\Entity\CoreAttribute $coreAttributeRead
     */
    public function addCoreAttributeRead(\Cx\Core\User\Model\Entity\CoreAttribute $coreAttributeRead)
    {
        $this->coreAttributeRead[] = $coreAttributeRead;
    }

    /**
     * Get coreAttributeRead
     *
     * @return Doctrine\Common\Collections\Collection $coreAttribute
     */
    public function getCoreAttributeRead()
    {
        return $this->coreAttributeRead;
    }

}
