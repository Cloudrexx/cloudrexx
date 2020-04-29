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
 * Attributes that contain informations about the users.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
namespace Cx\Core\User\Model\Entity;

/**
 * Attributes that contain information about the users.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
class UserAttribute extends \Cx\Model\Base\EntityBase {

    /**
     * User attribute type text
     */
    const TYPE_TEXT = 'text';

    /**
     * User attribute type textarea
     */
    const TYPE_TEXTAREA = 'textarea';

    /**
     * User attribute type mail
     */
    const TYPE_MAIL = 'mail';

    /**
     * User attribute type uri
     */
    const TYPE_URI = 'uri';

    /**
     * User attribute type date
     */
    const TYPE_DATE = 'date';

    /**
     * User attribute type image
     */
    const TYPE_IMAGE = 'image';

    /**
     * User attribute type checkbox
     */
    const TYPE_CHECKBOX = 'checkbox';

    /**
     * User attribute type menu
     */
    const TYPE_MENU = 'menu';

    /**
     * User attribute type menu option
     */
    const TYPE_MENU_OPTION = 'menu_option';

    /**
     * User attribute type group
     */
    const TYPE_GROUP = 'group';

    /**
     * User attribute type frame
     */
    const TYPE_FRAME = 'frame';

    /**
     * User attribute type history
     */
    const TYPE_HISTORY = 'history';

    /**
     * User attribute sort type asc
     */
    const SORT_TYPE_ASC = 'asc';

    /**
     * User attribute sort type desc
     */
    const SORT_TYPE_DESC = 'desc';

    /**
     * User attribute sort type custom
     */
    const SORT_TYPE_CUSTOM = 'custom';

    /**
     * User attribute has no special
     */
    const ACCESS_SPECIAL_NONE = '';

    /**
     * Access special only one option in a lower position can be selected
     */
    const ACCESS_SPECIAL_MENU_LOWER = 'menu_select_lower';

    /**
     * Access special only one option in a higher position can be selected
     */
    const ACCESS_SPECIAL_MENU_HIGHER = 'menu_select_higher';

    /**
     * @var integer ID of the user attribute
     */
    protected $id;

    /**
     * @var string The type of the attribute
     *     possibilities: text, textarea, mail, uri, date, image, checkbox,
     *         menu, menu_option, group, frame, history
     */
    protected $type = self::TYPE_TEXT;

    /**
     * @var boolean Whether the attribute is mandatory
     */
    protected $mandatory = false;

    /**
     * @var string How the child attributes should be sorted
     *     possibilities: asc, desc, custom
     */
    protected $sortType = self::SORT_TYPE_ASC;

    /**
     * @var integer the order the attributes get listed
     */
    protected $orderId = 0;

    /**
     * @var string Set a special privilege for users that do not have editing
     *     rights from assigned group
     *     possibilities: menu_select_higher, menu_select_lower
     */
    protected $accessSpecial = self::ACCESS_SPECIAL_NONE;

    /**
     * @var integer Permissions are handled using access IDs. There are two types:
     *     Static (restrict the access to functions and sections - mostly backend)
     *     Dynamic (restrict the access to content data - content pages,
     *     categories, documents, etc.)
     */
    protected $accessId;

    /**
     * @var integer Unique identifier for reading access. There are two types:
     *     Static (restrict the access to functions and sections - mostly backend)
     *     Dynamic (restrict the access to content data - content pages,
     *     categories, documents, etc.)
     */
    protected $readAccessId;

    /**
     * @var \Cx\Core\User\Model\Entity\UserAttribute The associated parent
     *     attribute
     */
    protected $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection Collection of names
     *     associated to this user attribute
     */
    protected $userAttributeNames;

    /**
     * @var \Doctrine\Common\Collections\Collection Collection of values
     *     associated to this user attribute
     */
    protected $userAttributeValues;

    /**
     * @var \Doctrine\Common\Collections\Collection Collection of child
     *     user attributes
     */
    protected $children;

    /**
     * @var boolean Whether the attribute is a default system attribute
     */
    protected $default;

    /**
     * @var array[] Default configuration of the default attributes
     */
    protected $arrTypes = array(
        'text' => array(
            'desc'         => 'TXT_ACCESS_TEXT_FIELD',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => true,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'mail' => array(
            'desc'         => 'TXT_ACCESS_EMAIL_ADDRESS',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'uri' => array(
            'desc'         => 'TXT_ACCESS_WEB_ADDRESS',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'date' => array(
            'desc'         => 'TXT_ACCESS_DATE',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'int',
        ),
        'image' => array(
            'desc'         => 'TXT_ACCESS_IMAGE',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'checkbox' => array(
            'desc'         => 'TXT_ACCESS_CHECKBOX',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'int',
        ),
        'menu' => array(
            'desc'         => 'TXT_ACCESS_MENU',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => true,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array('', 'menu_select_higher', 'menu_select_lower'),
            'data_type'    => 'int',
        ),
        'menu_option' => array(
            'desc'         => 'TXT_ACCESS_MENU_OPTION',
            'parent'       => 'TXT_ACCESS_MENU',
            'mandatory'    => false,
            'children'     => false,
            'multiline'    => false,
            'movable'      => false,
            'protection'   => false,
            'special'      => array(),
            'data_type'    => 'null',
        ),
        'group' => array(
            'desc'         => 'TXT_ACCESS_GROUP',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => false,
            'children'     => true,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => false,
            'special'      => array(),
            'data_type'    => 'array',
        ),
        'frame' => array(
            'desc'         => 'TXT_ACCESS_FRAME',
            'parent'       => 'TXT_ACCESS_GROUP',
            'mandatory'    => false,
            'children'     => true,
            'multiline'    => false,
            'movable'      => false,
            'protection'   => false,
            'special'      => array(),
            'data_type'    => 'array',
        ),
        'history' => array(
            'desc'         => 'TXT_ACCESS_HISTORY',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => false,
            'children'     => true,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'array',
        ),
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userAttributeNames = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer ID of the user attribute
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
     * @return string The type of the attribute
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set mandatory
     *
     * @param boolean $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * Get mandatory
     *
     * @return boolean Whether the attribute is mandatory
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
     * @return integer order ID
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
     * @return string special access privilege
     */
    public function getAccessSpecial()
    {
        return $this->accessSpecial;
    }

    /**
     * Set accessId
     *
     * @param integer $accessId
     */
    public function setAccessId($accessId)
    {
        $this->accessId = $accessId;
    }

    /**
     * Get accessId
     *
     * @return integer accessIDs to manage permissions
     */
    public function getAccessId()
    {
        return $this->accessId;
    }

    /**
     * Set readAccessId
     *
     * @param integer $readAccessId
     */
    public function setReadAccessId($readAccessId)
    {
        $this->readAccessId = $readAccessId;
    }

    /**
     * Get readAccessId
     *
     * @return integer unique identifier for reading access
     */
    public function getReadAccessId()
    {
        return $this->readAccessId;
    }

    /**
     * Set default
     *
     * @param boolean $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Get default
     *
     * This does exactly the same as isDefault, but this method is necessary for doctrine mapping
     *
     * @return boolean Whether attribute is a default system attribute
     */
    public function getDefault()
    {
        return $this->default;
    }


    /**
     * Get default
     *
     * This does exactly the same as getDefault, but this method name is more intuitive
     *
     * @return boolean Whether attribute is a default system attribute
     */
    public function isDefault()
    {
        return $this->getDefault();
    }

    /**
     * Add child
     *
     * @param \Cx\Core\User\Model\Entity\UserAttribute $child
     */
    public function addChild(\Cx\Core\User\Model\Entity\UserAttribute $child)
    {
        $this->children[] = $child;
    }

    /**
     * Remove child
     *
     * @param \Cx\Core\User\Model\Entity\UserAttribute $child
     */
    public function removeChild(\Cx\Core\User\Model\Entity\UserAttribute $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection Collection of child
     *     user attributes
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add userAttributeName
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName
     */
    public function addUserAttributeName(\Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName)
    {
        $this->userAttributeNames[] = $userAttributeName;
    }

    /**
     * Remove userAttributeName
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName
     */
    public function removeUserAttributeName(\Cx\Core\User\Model\Entity\UserAttributeName $userAttributeName)
    {
        $this->userAttributeNames->removeElement($userAttributeName);
    }

    /**
     * Get userAttributeName
     *
     * @return \Doctrine\Common\Collections\Collection Collection of names
     *     associated to this user attribute
     */
    public function getUserAttributeNames()
    {
        return $this->userAttributeNames;
    }

    /**
     * Add userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function addUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValues[] = $userAttributeValue;
    }

    /**
     * Remove userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function removeUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValues->removeElement($userAttributeValue);
    }

    /**
     * Get userAttributeValues
     *
     * @return \Doctrine\Common\Collections\Collection Collection of values
     *     associated to this user attribute
     */
    public function getUserAttributeValues()
    {
        return $this->userAttributeValues;
    }

    /**
     * Set parent
     *
     * @param \Cx\Core\User\Model\Entity\UserAttribute $parent
     */
    public function setParent(\Cx\Core\User\Model\Entity\UserAttribute $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent
     *
     * @return \Cx\Core\User\Model\Entity\UserAttribute the associated parent
     *     attribute
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function getName($langId = 0)
    {
        if (empty($langId) && FRONTEND_LANG_ID) {
            $langId = FRONTEND_LANG_ID;
        } else if (empty($langId)) {
            $langId = 1;
        }

        $crit = \Doctrine\Common\Collections\Criteria::create()->where(
            \Doctrine\Common\Collections\Criteria::expr()->eq(
                'langId',
                $langId
            )
        )->orWhere(
            \Doctrine\Common\Collections\Criteria::expr()->eq(
                'langId',
                0
            )
        );
        $userAttributeName = $this->getUserAttributeNames()->matching(
            $crit
        )->first();

        if (!empty($userAttributeName)) {
            return $userAttributeName->getName();
        }

        return '';
    }

    /**
     * Check the read permission of profile attribute
     *
     * @return boolean $hasReadPermission
     */
    public function hasReadPermission()
    {
        return \Permission::checkAccess(
            $this->getReadAccessId(),
            'static',
            true
        );
    }

    /**
     * Get data type
     *
     * @return string $dataType
     */
    function getDataType()
    {
        return $this->arrTypes[$this->getType()]['data_type'];
    }
}
