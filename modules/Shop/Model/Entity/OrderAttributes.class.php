<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2018
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
 * Class OrderAttributes
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This is the option selected by the product options. So that the attribute
 * options can be changed, but the ordered option is still present in the
 * order, there is no relation between product option and this entity.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class OrderAttributes extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $attributeName;

    /**
     * @var string
     */
    protected $optionName;

    /**
     * @var string
     */
    protected $price;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\OrderItems
     */
    protected $orderItems;


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
     * Set itemId
     *
     * @param integer $itemId
     * @return OrderAttributes
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set attributeName
     *
     * @param string $attributeName
     * @return OrderAttributes
     */
    public function setAttributeName($attributeName)
    {
        $this->attributeName = $attributeName;

        return $this;
    }

    /**
     * Get attributeName
     *
     * @return string 
     */
    public function getAttributeName()
    {
        return $this->attributeName;
    }

    /**
     * Set optionName
     *
     * @param string $optionName
     * @return OrderAttributes
     */
    public function setOptionName($optionName)
    {
        $this->optionName = $optionName;

        return $this;
    }

    /**
     * Get optionName
     *
     * @return string 
     */
    public function getOptionName()
    {
        return $this->optionName;
    }

    /**
     * Set price
     *
     * @param string $price
     * @return OrderAttributes
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set orderItems
     *
     * @param \Cx\Modules\Shop\Model\Entity\OrderItems $orderItems
     * @return OrderAttributes
     */
    public function setOrderItems(\Cx\Modules\Shop\Model\Entity\OrderItems $orderItems = null)
    {
        $this->orderItems = $orderItems;

        return $this;
    }

    /**
     * Get orderItems
     *
     * @return \Cx\Modules\Shop\Model\Entity\OrderItems 
     */
    public function getOrderItems()
    {
        return $this->orderItems;
    }
}
