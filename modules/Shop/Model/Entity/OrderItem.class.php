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
 * Class OrderItems
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * Product of an order, contains the total weight, quantity, price and the
 * product name. This allows the product to be changed, but the price and
 * name are the same as when the order was made.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class OrderItem extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $orderId;

    /**
     * @var integer
     */
    protected $productId;

    /**
     * @var string
     */
    protected $productName;

    /**
     * @var string
     */
    protected $price = '0.00';

    /**
     * @var integer
     */
    protected $quantity;

    /**
     * @var string
     */
    protected $vatRate;

    /**
     * @var integer
     */
    protected $weight;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $orderAttributes;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Order
     */
    protected $order;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Product
     */
    protected $product;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderAttributes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set productId
     *
     * @param integer $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * Get productId
     *
     * @return integer 
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set productName
     *
     * @param string $productName
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;
    }

    /**
     * Get productName
     *
     * @return string 
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * Set price
     *
     * @param string $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
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
     * Set quantity
     *
     * @param integer $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set vatRate
     *
     * @param string $vatRate
     */
    public function setVatRate($vatRate)
    {
        $this->vatRate = $vatRate;
    }

    /**
     * Get vatRate
     *
     * @return string 
     */
    public function getVatRate()
    {
        return $this->vatRate;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Add orderAttribute
     *
     * @param \Cx\Modules\Shop\Model\Entity\OrderAttributes $orderAttribute
     */
    public function addOrderAttribute(\Cx\Modules\Shop\Model\Entity\OrderAttribute $orderAttribute)
    {
        $this->orderAttributes[] = $orderAttribute;
    }

    /**
     * Remove orderAttribute
     *
     * @param \Cx\Modules\Shop\Model\Entity\OrderAttribute $orderAttribute
     */
    public function removeOrderAttribute(\Cx\Modules\Shop\Model\Entity\OrderAttribute $orderAttribute)
    {
        $this->orderAttributes->removeElement($orderAttributes);
    }

    /**
     * Get orderAttributes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderAttributes()
    {
        return $this->orderAttributes;
    }

    /**
     * Set order
     *
     * @param \Cx\Modules\Shop\Model\Entity\Order $order
     */
    public function setOrder(\Cx\Modules\Shop\Model\Entity\Order $order = null)
    {
        $this->order = $order;
    }

    /**
     * Get order
     *
     * @return \Cx\Modules\Shop\Model\Entity\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set product
     *
     * @param \Cx\Modules\Shop\Model\Entity\Product $product
     */
    public function setProduct(\Cx\Modules\Shop\Model\Entity\Product $product = null)
    {
        $this->product = $product;
    }

    /**
     * Get product
     *
     * @return \Cx\Modules\Shop\Model\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
