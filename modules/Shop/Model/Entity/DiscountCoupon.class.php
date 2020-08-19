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
 * Class DiscountCoupon
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * With the discount coupon, an order can be discounted. This coupon can be
 * restricted to a product, payment, customer or minimum amount.  The code must
 * have at least 6 digits and be unique.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class DiscountCoupon extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $id;
    /**
     * @var string
     */
    protected $code;

    /**
     * @var integer
     */
    protected $customerId;

    /**
     * @var integer
     */
    protected $paymentId;

    /**
     * @var integer
     */
    protected $productId;

    /**
     * @var integer
     */
    protected $startTime;

    /**
     * @var integer
     */
    protected $endTime;

    /**
     * @var integer
     */
    protected $uses;

    /**
     * @var boolean
     */
    protected $global;

    /**
     * @var string
     */
    protected $minimumAmount;

    /**
     * @var string
     */
    protected $discountAmount;

    /**
     * @var string
     */
    protected $discountRate;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Payment
     */
    protected $payment;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Product
     */
    protected $product;

    /**
     * @var \Cx\Core\User\Model\Entity\User
     */
    protected $customer;

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Set code
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set customerId
     *
     * @param integer $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * Get customerId
     *
     * @return integer 
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Set paymentId
     *
     * @param integer $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * Get paymentId
     *
     * @return integer 
     */
    public function getPaymentId()
    {
        return $this->paymentId;
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
     * Set startTime
     *
     * @param integer $startTime
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * Get startTime
     *
     * @return integer 
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * Get endTime
     *
     * @return integer 
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set uses
     *
     * @param integer $uses
     */
    public function setUses($uses)
    {
        $this->uses = $uses;
    }

    /**
     * Get uses
     *
     * @return integer 
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * Set global
     *
     * @param boolean $global
     */
    public function setGlobal($global)
    {
        $this->global = $global;
    }

    /**
     * Get global
     *
     * @return boolean 
     */
    public function getGlobal()
    {
        return $this->global;
    }

    /**
     * Set minimumAmount
     *
     * @param string $minimumAmount
     */
    public function setMinimumAmount($minimumAmount)
    {
        $this->minimumAmount = $minimumAmount;
    }

    /**
     * Get minimumAmount
     *
     * @return string 
     */
    public function getMinimumAmount()
    {
        return $this->minimumAmount;
    }

    /**
     * Set discountAmount
     *
     * @param string $discountAmount
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;
    }

    /**
     * Get discountAmount
     *
     * @return string 
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Set discountRate
     *
     * @param string $discountRate
     */
    public function setDiscountRate($discountRate)
    {
        $this->discountRate = $discountRate;
    }

    /**
     * Get discountRate
     *
     * @return string 
     */
    public function getDiscountRate()
    {
        return $this->discountRate;
    }

    /**
     * Set payment
     *
     * @param \Cx\Modules\Shop\Model\Entity\Payment $payment
     */
    public function setPayment(\Cx\Modules\Shop\Model\Entity\Payment $payment = null)
    {
        $this->payment = $payment;
    }

    /**
     * Get payment
     *
     * @return \Cx\Modules\Shop\Model\Entity\Payment 
     */
    public function getPayment()
    {
        return $this->payment;
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

    /**
     * Set customer
     *
     * @param \Cx\Core\User\Model\Entity\User $customer
     */
    public function setCustomer(\Cx\Core\User\Model\Entity\User $customer = null)
    {
        $this->customer = $customer;
    }

    /**
     * Get customer
     *
     * @return \Cx\Core\User\Model\Entity\User
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
