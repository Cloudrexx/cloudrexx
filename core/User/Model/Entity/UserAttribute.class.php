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


namespace Cx\Core\User\Model\Entity;

/**
 * Cx\Core\User\Model\Entity\UserAttribute
 */
class UserAttribute extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var string $mandatory
     */
    private $mandatory;

    /**
     * @var string $sortType
     */
    private $sortType;

    /**
     * @var integer $orderId
     */
    private $orderId;

    /**
     * @var string $accessSpecial
     */
    private $accessSpecial;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userAttributeName;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $userAttributeValue;

    /**
     * @var \Cx\Core\User\Model\Entity\UserAttribute
     */
    private $children;

    /**
     * @var \Cx\Core_Modules\Access\Model\Entity\AccessId
     */
    private $accessId;

    /**
     * @var \Cx\Core\User\Model\Entity\UserProfile
     */
    private $userProfile;

    /**
     * @var Cx\Core_Modules\Access\Model\Entity\AccessId
     */
    protected $readAccessId;

    public function __construct()
    {
        $this->parent = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userAttributeName = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userAttributeValue = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userProfile = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set mandatory
     *
     * @param string $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * Get mandatory
     *
     * @return string $mandatory
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Set sortType
     *
     * @param string $sortType
     */
    public function setSortType($sortType)
    {
        $this->sortType = $sortType;
    }

    /**
     * Get sortType
     *
     * @return string $sortType
     */
    public function getSortType()
    {
        return $this->sortType;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Get orderId
     *
     * @return integer $orderId
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set accessSpecial
     *
     * @param string $accessSpecial
     */
    public function setAccessSpecial($accessSpecial)
    {
        $this->accessSpecial = $accessSpecial;
    }

    /**
     * Get accessSpecial
     *
     * @return string $accessSpecial
     */
    public function getAccessSpecial()
    {
        return $this->accessSpecial;
    }

    /**
     * Add parent
     *
     * @param \Cx\Core\User\Model\Entity\UserAttribute $parent
     */
    public function addParent(\Cx\Core\User\Model\Entity\UserAttribute $parent)
    {
        $this->parent[] = $parent;
    }

    /**
     * Remove parent
     *
     * @param \Cx\Core\User\Model\Entity\UserAttribute $parent
     */
    public function removeParent(\Cx\Core\User\Model\Entity\UserAttribute $parent)
    {
        $this->parent->removeElement($parent);
    }

    /**
     * Get parent
     *
     * @return \Doctrine\Common\Collections\Collection $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add userAttributeName
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName
     */
    public function addUserAttributeName(\Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName)
    {
        $this->userAttributeName[] = $userAttributeName;
    }

    /**
     * Remove userAttributeName
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName
     */
    public function removeUserAttributeName(\Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName)
    {
        $this->userAttributeName->removeElement($userAttributeName);
    }

    /**
     * Get userAttributeName
     *
     * @return \Doctrine\Common\Collections\Collection $userAttributeName
     */
    public function getUserAttributeName()
    {
        return $this->userAttributeName;
    }

    /**
     * Add userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function addUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValue[] = $userAttributeValue;
    }

    /**
     * Remove userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function removeUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValue->removeElement($userAttributeValue);
    }

    /**
     * Get userAttributeValue
     *
     * @return \Doctrine\Common\Collections\Collection $userAttributeValue
     */
    public function getUserAttributeValue()
    {
        return $this->userAttributeValue;
    }

    /**
     * Set children
     *
     * @param \Cx\Core\User\Model\Entity\UserAttribute $children
     */
    public function setChildren(\Cx\Core\User\Model\Entity\UserAttribute $children)
    {
        $this->children = $children;
    }

    /**
     * Get children
     *
     * @return \Cx\Core\User\Model\Entity\UserAttribute $children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set accessId
     *
     * @param \Cx\Core_Modules\Access\Model\Entity\AccessId $accessId
     */
    public function setAccessId(\Cx\Core_Modules\Access\Model\Entity\AccessId $accessId)
    {
        $this->accessId = $accessId;
    }

    /**
     * Get accessId
     *
     * @return \Cx\Core_Modules\Access\Model\Entity\AccessId $accessId
     */
    public function getAccessId()
    {
        return $this->accessId;
    }

    /**
     * Add userProfile
     *
     * @param \Cx\Core\User\Model\Entity\UserProfile $userProfile
     */
    public function addUserProfile(\Cx\Core\User\Model\Entity\UserProfile $userProfile)
    {
        $this->userProfile[] = $userProfile;
    }

    /**
     * Get userProfile
     *
     * @return \Doctrine\Common\Collections\Collection $userProfile
     */
    public function getUserProfile()
    {
        return $this->userProfile;
    }

    /**
     * Set readAccessId
     *
     * @param \Cx\Core_Modules\Access\Model\Entity\AccessId $readAccessId
     */
    public function setReadAccessId(\Cx\Core_Modules\Access\Model\Entity\AccessId $readAccessId)
    {
        $this->readAccessId = $readAccessId;
    }

    /**
     * Get readAccessId
     *
     * @return \Cx\Core_Modules\Access\Model\Entity\AccessId $readAccessId
     */
    public function getReadAccessId()
    {
        return $this->readAccessId;
    }
}
