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
 * Cx\Core\User\Model\Entity\UserAttributeValue
 */
class UserAttributeValue extends \Cx\Model\Base\EntityBase {
    
    /**
     * The attribute this value belongs to
     *
     * @var \Cx\Core\User\Model\Entity\UserAttribute
     */
    protected $attributeId;

    /**
     * The attribute this value belongs to
     *
     * @var \Cx\Core\User\Model\Entity\UserAttribute
     */
    protected $attribute;
    
    /**
     * The user this value belongs to
     *
     * @var \Cx\Core\User\Model\Entity\User
     */
    protected $userId;

    /**
     * The user this value belongs to
     *
     * @var \Cx\Core\User\Model\Entity\User
     */
    protected $user;

    /**
     * Version number
     *
     * @var integer
     */
    protected $history;

    /**
     * The value
     *
     * @var string
     */
    protected $value;

    /**
     * Returns the associated attribute
     * @return \Cx\Core\User\Model\Entity\UserAttribute
     */
    public function getAttribute() {
        return $this->attribute;
    }

    /**
     * Sets the associated attribute
     * @param \Cx\Core\User\Model\Entity\UserAttribute $attribute
     */
    public function setAttribute($attribute) {
        $this->attribute = $attribute;
    }

    /**
     * Returns the associated attribute
     * @return \Cx\Core\User\Model\Entity\UserAttribute
     */
    public function getAttributeId() {
        return $this->attributeId;
    }

    /**
     * Sets the associated attribute
     * @param \Cx\Core\User\Model\Entity\UserAttribute $attribute
     */
    public function setAttributeId($attribute) {
        $this->attributeId = $attribute;
    }

    /**
     * Returns the associated user
     * @return \Cx\Core\User\Model\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Sets the associated user
     * @param \Cx\Core\User\Model\Entity\User $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * Returns the associated user
     * @return \Cx\Core\User\Model\Entity\User
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * Sets the associated user
     * @param \Cx\Core\User\Model\Entity\User $user
     */
    public function setUserId($user) {
        $this->userId = $user;
    }

    /**
     * Returns the version ID
     * @return integer
     */
    public function getHistory() {
        return $this->history;
    }

    /**
     * Sets the version ID
     * @param integer $history
     */
    public function setHistory($history) {
        $this->history = $history;
    }

    /**
     * Returns the value
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Sets the value
     * @param string $value
     */
    public function setValue($value) {
        $this->value = $value;
    }
}
