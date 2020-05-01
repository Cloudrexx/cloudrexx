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
 *     title="UserAttribute",
 * )
 */
class UserAttribute extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {

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
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    protected $locale;

    /**
     * @OA\Property(
     *     format="int",
     *     description="Unique identifier for the Attribute. ",
     *     title="Attribute ID",
     * )
     *
     * @var integer $id
     */
    protected $id;

    /**
     * @OA\Property(
     *     description="Define the type of the attribute. There are following choices:
     * ***
     * **text**
     *
     * a one line Textbox
     * * * *
     * **textarea**
     *
     * a multiline textbox
     * * * *
     * **mail**
     *
     * a email address
     * * * *
     * **uri**
     *
     * to link a website
     * * * *
     * **date**
     *
     * to show a datepicker
     * * * *
     * **image**
     *
     * to get a upload choice
     * * * *
     * **checkbox**
     *
     * to mark the attribute as checkbox
     * * * *
     * **menu**
     *
     * displays a dropdown-menu
     * * * *
     * **group**
     *
     * add a associated group
     * * * *
     * **menu_option**
     *
     * to specify a attributes for the menu
     * * * *
     * **history**
     *
     * to setup the possibility for the history
     * * * *",
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
     * @var \Cx\Core\Model\Data\Enum\User\UserAttribute\Type $type
     */
    protected $type = self::TYPE_TEXT;

    /**
     * @OA\Property(
     *     format="string",
     *     description="The name of the user attribute.",
     *     title="Name",
     *     default=" ' ' ",
     * )
     * @var string
     */
    protected $name = '';

    /**
     * @OA\Property(
     *     format="boolean",
     *     description="If this is set to true if this field should be mandatory.",
     *     title="Mandatory",
     *     default="false",
     * )
     *
     * @var boolean $mandatory
     */
    protected $mandatory = false;

    /**
     * @OA\Property(
     *     description="Define how the child attributes should be sorted. Child attributes are for example Dear Mr. /
     *                  Dear Mrs. of the attrubute title. We can order these in three ways: Ascending,
     *                  descending and custom.
     *                  If set to custom the attributes will be sorted after the same pattern as the order.",
     *     title="User attribute, sorting type",
     *     enum={"asc", "desc", "custom"},
     *     default="asc",
     * )
     *
     * @var \Cx\Core\Model\Data\Enum\User\UserAttribute\SortType $sortType
     */
    protected $sortType = self::SORT_TYPE_ASC;

    /**
     * @OA\Property(
     *     format="int",
     *     description="Defines the order the attributes get listed. If no special order is set (default)
     *                  the ID is used to define the order.",
     *     title="Order ID",
     *     default="0",
     * )
     *
     * @var integer $orderId
     */
    protected $orderId = 0;

    /**
     * @OA\Property(
     *     description="A special privilege for users that do not have editing rights from the assigned group can
     *                  be set with this attribute. [Documentation](https://wiki.cloudrexx.com/Development_Permissions)
     *                  1.  Menu_select_higher: Only entries above the currently selected in the list may be chosen.
     *                  2.  Menu_select_lower: Only entries below the currently selected in the list may be chosen.",
     *     title="User attribute, special access",
     *     enum={"menu_select_higher", "menu_select_lower"},
     * )
     *
     * @var \Cx\Core\Model\Data\Enum\User\UserAttribute\AccessSpecial $accessSpecial
     */
    protected $accessSpecial = self::ACCESS_SPECIAL_NONE;

    /**
     * @OA\Property(
     *     format="int",
     *     description="Permissions are handled using access IDs. There are two types:
     *                  1.  Static (restrict the access to functions and sections - mostly backend).
     *                  2.  Dynamic (restrict the access to content data - content pages, categories, documents, etc.).
     *
     *     [Documentation](https://wiki.cloudrexx.com/Development_Permissions)",
     *     title="Access ID",
     * )
     *
     * @var integer $accessId
     */
    protected $accessId;

    /**
     * @OA\Property(
     *     format="int",
     *     description="Unique identifier for reading access. There are two types:
     *                  1.  Static (restrict the access to functions and sections - mostly backend).
     *                  2.  Dynamic (restrict the access to content data - content pages, categories, documents, etc.).
     *
     *     [Documentation](https://wiki.cloudrexx.com/Development_Permissions)",
     *     title="Access ID read",
     * )
     *
     * @var integer $readAccessId
     */
    protected $readAccessId;

    /**
     * @OA\Property(
     *     description="Lists the user attribute object of the parent. Every attribute that has a parent is a menu
     *                  option. If the request intends to POST we need to pass the ID, if  the request is to GET we
     *                  get the user attribute of the given ID. For example the ID 1.",
     *     title="Parent",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttribute"
     *     }
     * )
     *
     * @var \Cx\Core\User\Model\Entity\UserAttribute $parent
     */
    protected $parent;

    /**
     * @OA\Property(
     *     description="List of all attributes that are assigned to the user. We have a key that's a serialized
     *                  ID from fields of the primary key in the corresponding order: attributeId / userId / historyId
     *                  Example: 1/1/0. For POST requests we need to pass the serialized key in the order as before,
     *                  if we want to GET we use the serialized key with the desired ID's like the example given.
     *                  Locale can be used for this property.",
     *     title="User attribute value.",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttributeValue"
     *     }
     * )
     *
     * @var \Doctrine\Common\Collections\Collection $userAttributeValues
     */
    protected $userAttributeValues;

    /**
     * @OA\Property(
     *     description="List of the child attributes of the fields with menu options. If the request intends to POST
     *                  we need to pass the ID, if  the request is to GET we get the attribute names of children
     *                  with the respective attribute ID. For example the ID 1.",
     *     title="Children",
     *     type="object",
     * )
     *
     * @var \Doctrine\Common\Collections\Collection $children
     */
    protected $children;

    /**
     * @var boolean $default
     */
    protected $default;

    /**
     * @var array[] $arrTypes
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
        $this->userAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set translatable locale
     *
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        if (is_numeric($locale)) {
            $localeEntity = $this->cx->getDb()->getEntityManager()->getRepository(
                'Cx\Core\Locale\Model\Entity\Locale'
            )->find($locale);
            if ($localeEntity) {
                $locale = $localeEntity->getIso1()->getIso1();
            } else {
                $locale = 'de';
            }
        }
        $this->locale = $locale;
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
     * @param \Cx\Core\Model\Data\Enum\User\UserAttribute\Type $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return \Cx\Core\Model\Data\Enum\User\UserAttribute\Type $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @return boolean $mandatory
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Set sortType
     *
     * @param \Cx\Core\Model\Data\Enum\User\UserAttribute\SortType $sortType
     */
    public function setSortType($sortType)
    {
        $this->sortType = $sortType;
    }

    /**
     * Get sortType
     *
     * @return \Cx\Core\Model\Data\Enum\User\UserAttribute\SortType $sortType
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
     * @param \Cx\Core\Model\Data\Enum\User\UserAttribute\AccessSpecial $accessSpecial
     */
    public function setAccessSpecial($accessSpecial)
    {
        $this->accessSpecial = $accessSpecial;
    }

    /**
     * Get accessSpecial
     *
     * @return \Cx\Core\Model\Data\Enum\User\UserAttribute\AccessSpecial $accessSpecial
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
     * @return integer $accessId
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
     * @return integer $readAccessId
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
     * @return boolean $default
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
     * @return boolean $default
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
     * @return \Doctrine\Common\Collections\Collection $child
     */
    public function getChildren()
    {
        return $this->children;
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
     * @return \Doctrine\Common\Collections\Collection $userAttributeValues
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
     * @return \Cx\Core\User\Model\Entity\UserAttribute $parent
     */
    public function getParent()
    {
        return $this->parent;
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
