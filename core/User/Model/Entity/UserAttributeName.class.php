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
 * Name assigned to the attributes.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
namespace Cx\Core\User\Model\Entity;

/**
 * Name assigned to the attributes.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 *
 * @OA\Schema(
 *     description="In this model we can set the name for the attribues that are default or custom. Those names can be set for every desired language that will get to be used for the website.",
 *     title="UserAttributeName model",
 * )
 */
class UserAttributeName extends \Cx\Model\Base\EntityBase {
    /**
     * @OA\Property(
     *     format="int",
     *     description="Attribute name, Id",
     *     title="ID",
     * )
     *
     * @var integer
     */
    protected $id;

    /**
     * @OA\Property(
     *     format="int",
     *     description="Attribute Id",
     *     title="AttributeId",
     * )
     *
     * @var integer
     */
    protected $attributeId = 0;

    /**
     * @OA\Property(
     *     format="int",
     *     description="The default language for the user as it was defined",
     *     title="Language",
     *     default="0",
     * )
     *
     * @var integer
     */
    protected $langId = 0;

    /**
     * @OA\Property(
     *     format="int",
     *     description="Determins the order the attributes get listed, starting at 1",
     *     title="Order",
     *     default="0",
     * )
     *
     * @var integer
     */
    protected $order = 0;

    /**
     * @OA\Property(
     *     description="Name of attribute",
     *     title="Name",
     *     maximum=255,
     * )
     *
     * @var string
     */
    protected $name = '';

    /**
     * @OA\Property(
     *     description="List of the attributes of a user, identified by the Id",
     *     title="User attributes",
     *     type="object",
     *     additionalProperties={
     *         "ref"="#/components/schemas/UserAttribute"
     *     }
     * )
     *
     * @var \Cx\Core\User\Model\Entity\UserAttribute
     */
    protected $userAttribute;

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
     * Set attributeId
     *
     * @param integer $attributeId
     */
    public function setAttributeId($attributeId)
    {
        $this->attributeId = $attributeId;
    }

    /**
     * Get attributeId
     *
     * @return integer
     */
    public function getAttributeId()
    {
        return $this->attributeId;
    }

    /**
     * Set langId
     *
     * @param integer $langId
     */
    public function setLangId($langId)
    {
        $this->langId = $langId;
    }

    /**
     * Get langId
     *
     * @return integer 
     */
    public function getLangId()
    {
        return $this->langId;
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
     * Set order
     *
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Get order
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set userAttribute
     *
     * @param \Cx\Core\User\Model\Entity\UserAttribute $userAttribute
     */
    public function setUserAttribute(\Cx\Core\User\Model\Entity\UserAttribute $userAttribute)
    {
        $this->userAttribute = $userAttribute;
    }

    /**
     * Get userAttribute
     *
     * @return \Cx\Core\User\Model\Entity\UserAttribute 
     */
    public function getUserAttribute()
    {
        return $this->userAttribute;
    }
}
