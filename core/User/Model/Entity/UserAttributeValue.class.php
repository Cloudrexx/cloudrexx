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
 * Values assigned to the attributes.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
namespace Cx\Core\User\Model\Entity;

/**
 * Values assigned to the attributes.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 *
 * @OA\Schema(
 *     description="This represents the value of an attribute and user.",
 *     title="UserAttributeValue",
 * )
 */
class UserAttributeValue extends \Cx\Model\Base\EntityBase {
    /**
     * @OA\Property(
     *     format="int",
     *     description="Multiple versions of an attribute value can be stored by using different numbers for history.",
     *     title="History",
     *     default="0"
     * )
     *
     * @var integer $history
     */
    protected $history = 0;

    /**
     * @OA\Property(
     *     description="This field contains the value of the related Attribute for the related User.",
     *     title="Value",
     * )
     *
     * @var string $value
     */
    protected $value;

    /**
     * @OA\Property(
     *     description="Lists the user object of the given ID. To POST we use a ID of a existing user, for example 1.
     *                  If the request is to GET, the result consists of the user object of the given ID,
     *                  for example ID, Name etc.",
     *     title="User",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/User"
     *     }
     * )
     *
     * @var \Cx\Core\User\Model\Entity\User $user
     */
    protected $user;

    /**
     * @OA\Property(
     *     description="Lists the user attribute object of the parent, every attribute that has a parent is a menu
     *                  option. If the request intends to POST we need to pass the ID, if  the request is to GET we get
     *                  the user attribute of the given ID. For example the ID 1.",
     *     title="User attribute names",
     *     type="object",
     *     additionalProperties={
     *         "$ref"="#/components/schemas/UserAttribute"
     *     }
     * )
     *
     * @var \Cx\Core\User\Model\Entity\UserAttribute $userAttribute
     */
    protected $userAttribute;

    /**
     * Set history
     *
     * @param integer $history
     */
    public function setHistory($history)
    {
        $this->history = $history;
    }

    /**
     * Get history
     *
     * @return integer $history
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Set value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get value
     *
     * @return string $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set user
     *
     * @param \Cx\Core\User\Model\Entity\User $user
     */
    public function setUser(\Cx\Core\User\Model\Entity\User $user)
    {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return \Cx\Core\User\Model\Entity\User $user
     */
    public function getUser()
    {
        return $this->user;
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
     * @return \Cx\Core\User\Model\Entity\UserAttribute $userAttribute
     */
    public function getUserAttribute()
    {
        return $this->userAttribute;
    }
}
