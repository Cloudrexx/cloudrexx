<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * Users can be assigned to groups.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
namespace Cx\Core\User\Model\Entity;

/**
 * Users can be assigned to groups.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
class Group extends \Cx\Model\Base\EntityBase {

    /**
     * User account type frontend
     */
    const TYPE_FRONTEND = 'frontend';

    /**
     * User account type backend
     */
    const TYPE_BACKEND = 'backend';

    /**
     * @var integer Id of the group
     */
    protected $id;

    /**
     * @var string Name of the group
     */
    protected $name = '';

    /**
     * @var string A description for the use of the group
     */
    protected $description = '';

    /**
     * @var boolean Whether group is active
     */
    protected $active = 1;

    /**
     * @var string Defines where the group can act
     *     possibilities: frontend, backend
     */
    protected $type = self::TYPE_FRONTEND;

    /**
     * @var string The page on which the users of this group should land after
     *     the user has logged in
     */
    protected $homepage = '';

    /**
     * @var integer Toolbar Profile ID which contains the permissions of the
     *     WYSIWYG editor
     */
    protected $toolbar = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection Assigned users
     */
    protected $users;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get groupId
     *
     * @return integer Id of the group
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getId()
     */
    public function getGroupId()
    {
        return $this->getId();
    }

    /**
     * Get id
     *
     * @return integer Id of the group
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set groupName
     *
     * @param string $groupName
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::setName()
     */
    public function setGroupName($groupName)
    {
        $this->setName($groupName);
    }

    /**
     * Set name
     *
     * @param string $name group name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get groupName
     *
     * @return string Name of the group
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getName()
     */
    public function getGroupName()
    {
        return $this->getName();
    }

    /**
     * Get name
     *
     * @return string Name of the group
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set groupDescription
     *
     * @param string $groupDescription
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::setDescription()
     */
    public function setGroupDescription($groupDescription)
    {
        $this->setDescription($groupDescription);
    }

    /**
     * Set description
     *
     * @param string $description group description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get groupDescription
     *
     * @return string Description of the group
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getDescription()
     */
    public function getGroupDescription()
    {
        return $this->getDescription();
    }

    /**
     * Get description
     *
     * @return string Description of the group
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::setActive()
     */
    public function setIsActive($isActive)
    {
        $this->setActive($isActive);
    }

    /**
     * Set whether group is active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get isActive
     *
     * @return boolean Whether group is active
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getActive()
     */
    public function getIsActive()
    {
        return $this->getActive();
    }

    /**
     * Whether group is active
     *
     * This does exactly the same as isActive, but this method is necessary for doctrine mapping
     *
     * @return boolean $active Whether group is active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Whether group is active.
     *
     * This does exactly the same as getActive, but this method name is more intuitive
     *
     * @return boolean $active Whether group is active
     */
    public function isActive()
    {
        return $this->getActive();
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
     * @return string Where the group can act
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set homepage
     *
     * @param string $homepage
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;
    }

    /**
     * Get homepage
     *
     * @return string Node of the content page where the user is redirected to
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * Set toolbar
     *
     * @param integer $toolbar
     */
    public function setToolbar($toolbar)
    {
        $this->toolbar = $toolbar;
    }

    /**
     * Get toolbar
     *
     * @return integer Get toolbar profile id
     */
    public function getToolbar()
    {
        return $this->toolbar;
    }

    /**
     * Add user
     *
     * @param \Cx\Core\User\Model\Entity\User $user
     */
    public function addUser(\Cx\Core\User\Model\Entity\User $user)
    {
        $this->users[] = $user;
    }

    /**
     * Remove user
     *
     * @param \Cx\Core\User\Model\Entity\User $user
     */
    public function removeUser(\Cx\Core\User\Model\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get user
     *
     * @return \Doctrine\Common\Collections\Collection Assigned users
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getUsers()
     */
    public function getUser()
    {
        return $this->getUsers();
    }

    /**
     * Get user
     *
     * @return \Doctrine\Common\Collections\Collection Assigned users
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
