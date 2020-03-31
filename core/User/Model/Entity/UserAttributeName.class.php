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
 *     description="In this model we can set the name for the attributes per frontend Locale.",
 *     title="UserAttributeName model",
 * )
 */
class UserAttributeName extends \Cx\Model\Base\EntityBase {
    /**
     * @OA\Property(
     *     format="int",
     *     description="Unique identifier of the attribute name.",
     *     title="Attribute Name ID",
     * )
     *
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $attributeId = 0;

    /**
     * @OA\Property(
     *     format="int",
     *     description="The default locale ID. This default can be set in the administraton of the website",
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
     *     description="Determines the order the attribute children get listed, starting at 0.",
     *     title="Order",
     *     default="0",
     * )
     *
     * @var integer
     */
    protected $order = 0;

    /**
     * @OA\Property(
     *     description="Name of attribute.",
     *     title="Name",
     *     maximum=255,
     * )
     *
     * @var string
     */
    protected $name = '';

    /**
     * @OA\Property(
     *     description="Lists the user attribute objects. If the request intends to write we need to pass the ID, if  the request is to read we get the user attribute object of the given ID. For example the ID   1.",
     *     title="User attributes",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttribute"
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
