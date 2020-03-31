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
 * Attributes that contain informations about the users.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 *
 * @OA\Schema(
 *     description="A UserAttribute is a property of a user. More atttributes can be added, to expand a user profile.",
 *     title="UserAttribute model",
 * )
 */
class UserAttribute extends \Cx\Model\Base\EntityBase {
    /**
     * @OA\Property(
     *     format="int",
     *     description="Unique identifier for the Attribute. ",
     *     title="Attribute ID",
     * )
     *
     * @var integer
     */
    protected $id;

    /**
     * @OA\Property(
     *     description="Define the type of the attribute. There are following choices: Text for one line and textarea for a multiline textbox. Mail for a email address. Uri to link a website. Date for having a datepicker. Image to get a upload choice. Checkbox to mark the attribute as checkbox. Menu for a dropdown-menu. Menu_option to specify a attributes for the menu. History to setup the possibility for the history.",
     *     title="User attribute Type",
     *     enum={
     *          "text",
     *          "textarea",
     *          "mail",
     *          "uri",
     *          "date",
     *          "image",
     *          "checkbox",
     *          "menu",
     *          "menu_option",
     *          "group",
     *          "frame",
     *          "history",
     *      }
     * )
     *
     * @var enum_user_userattribute_type
     */
    protected $type = 'text';

    /**
     * @OA\Property(
     *     format="boolean",
     *     description="Set true if we want this field to be mandatory.",
     *     title="Mandatory",
     *     default="0",
     * )
     *
     * @var boolean
     */
    protected $mandatory = '0';

    /**
     * @OA\Property(
     *     description="Define how the child attributes should be sorted. Child attributes are for example Dear Mr. / Dear Mrs. of the attrubute title. We can order these in three ways: Ascending, descending and custom. If set to custom the attributes will be sorted after the same pattern as the order.",
     *     title="User attribute, sorting type",
     *     enum={"asc", "desc", "custom"},
     *     default="asc",
     * )
     *
     * @var enum_user_userattribute_sorttype
     */
    protected $sortType = 'asc';

    /**
     * @OA\Property(
     *     format="int",
     *     description="Defines the order the attributes get listed. If no special order is set (default) the id is used to define the order.",
     *     title="Order ID",
     *     default="0",
     * )
     *
     * @var integer
     */
    protected $orderId = 0;

    /**
     * @OA\Property(
     *     description="Set a special privilege for users that do not have editing rights from assigned group. Menu_select_higher: Only entries above the currently selected in the list may be chosen. Menu_select_lower: Only entries below the currently selected in the list may be chosen. [Documentation](https://wiki.cloudrexx.com/Development_Permissions).",
     *     title="User attribute, special access",
     *     enum={"menu_select_higher", "menu_select_lower"},
     * )
     *
     * @var enum_user_userattribute_accessspecial
     */
    protected $accessSpecial = '';

    /**
     * @OA\Property(
     *     format="int",
     *     description="Permissions are handled using access IDs. There are two types: Static (restrict the access to functions and sections - mostly backend) and dynamic (restrict the access to content data - content pages, categories, documents, etc.). [Documentation](https://wiki.cloudrexx.com/Development_Permissions).",
     *     title="Access ID",
     * )
     *
     * @var integer
     */
    protected $accessId;

    /**
     * @OA\Property(
     *     format="int",
     *     description="Unique identifier for reading access. There are two types: Static (restrict the access to functions and sections - mostly backend) and dynamic (restrict the access to content data - content pages, categories, documents, etc.).[Dokumentation](https://wiki.cloudrexx.com/Development_Permissions).",
     *     title="Access ID read",
     * )
     *
     * @var integer
     */
    protected $readAccessId;

    /**
     * @OA\Property(
     *     description="Lists the user attribute object of the parent, every attribute that has a parent is a menu option. If the request intends to write we need to pass the ID, if  the request is to read we get the user attribute of the given ID. For example the ID 1.",
     *     title="Parent",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttribute"
     *     }
     * )
     *
     * @var \Cx\Core\User\Model\Entity\UserAttribute
     */
    protected $parent;

    /**
     * @OA\Property(
     *     description="List of all attributes with the associated name and language. Every attribute can have more than one language it is translated in. If the request intends to write we need to pass the ID, if  the request is to read we get the attribute name of the given ID. For example the ID 1.",
     *     title="User attribute name",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttributeName"
     *     }
     * )
     *
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $userAttributeNames;

    /**
     * @OA\Property(
     *     description="List of all attributes that are assigned to the user. We have a key that's a serialized id from fields of the primary key in the corresponding order: <attributeId>/<userId>/<historyId> Example: 1/1/0. For writing requests we need to pass the serialized key in the order as before, if we want to read we use the serialized key with the desired ID's like the example given.",
     *     title="User attribute value",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttributeValue"
     *     }
     * )
     *
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $userAttributeValues;

    /**
     * @OA\Property(
     *     description="List of the child attributes of the fields with menu options. If the request intends to write we need to pass the ID, if  the request is to read we get the attribute names of children with the respective attribute ID. For example the ID 1.",
     *     title="Children",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttributeName"
     *     }
     * )
     *
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $children;

    /**
     * @var boolean
     */
    protected $default;

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
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param enum_user_userattribute_type $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return enum_user_userattribute_type 
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
     * @return boolean
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Set sortType
     *
     * @param enum_user_userattribute_sorttype $sortType
     */
    public function setSortType($sortType)
    {
        $this->sortType = $sortType;
    }

    /**
     * Get sortType
     *
     * @return enum_user_userattribute_sorttype 
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
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set accessSpecial
     *
     * @param enum_user_userattribute_accessspecial $accessSpecial
     */
    public function setAccessSpecial($accessSpecial)
    {
        $this->accessSpecial = $accessSpecial;
    }

    /**
     * Get accessSpecial
     *
     * @return enum_user_userattribute_accessspecial 
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
     * @return integer 
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
     * @return integer 
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
     * @return boolean
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
     * @return boolean
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
     * @return \Doctrine\Common\Collections\Collection 
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
     * @return \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection
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
     * @return \Cx\Core\User\Model\Entity\UserAttribute 
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
     * @return boolean
     */
    public function checkReadPermission()
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
     * @return string
     */
    function getDataType()
    {
        return $this->arrTypes[$this->getType()]['data_type'];
    }
}
