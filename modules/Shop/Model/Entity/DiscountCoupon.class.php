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
    protected $minimumAmount = '0.00';

    /**
     * @var string
     */
    protected $discountAmount = '0.00';

    /**
     * @var string
     */
    protected $discountRate = '0';

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

    /**
     * Returns the count of the uses for this coupon
     *
     * The optional $customer_id limits the result to the uses of that
     * Customer.
     * Returns 0 (zero) for codes not present in the relation (yet).
     *
     * @param   integer   $customerId    The optional Customer ID
     *
     * @return  mixed                     The number of uses of the code
     *                                    on success, false otherwise
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getUsedCount($customerId = 0)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        return $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\RelCustomerCoupon'
        )->getUsedCount($this->code, $customerId);
    }

    /**
     * Returns the discount amount used with this Coupon
     *
     * The optional $customer_id and $order_id limit the result to the uses
     * of that Customer and Order.
     * Returns 0 (zero) for Coupons that have not been used with the given
     * parameters, and thus are not present in the relation.
     *
     * @param   integer   $customerId    The optional Customer ID
     * @param   integer   $orderId       The optional Order ID
     *
     * @return  mixed                     The amount used with this Coupon
                             on success, false otherwise
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getUsedAmount($customerId = null, $orderId = null)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        return $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\RelCustomerCoupon'
        )->getUsedAmount($this->code, $customerId, $orderId);
    }

    /**
     * Returns the discount amount resulting from applying this Coupon
     *
     * This Coupon may contain either a discount rate or amount.
     * The rate has precedence and is thus applied in case both are set
     * (although this should never happen).
     * If neither is greater than zero, returns 0 (zero).  This also should
     * never happen.
     * If the Coupon has an amount, the sum of all previous redemptions
     * is subtracted first, and the remainder is returned.
     * Note that the value returned is never greater than $amount.
     *
     * @param   float   $amount         The amount
     * @param   integer $customerId     The Customer ID
     *
     * @return  string                  The applicable discount amount
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    function getDiscountAmountOrRate($amount, $customerId=NULL)
    {
        if ($this->getDiscountRate()) {
            return \Cx\Modules\Shop\Controller\CurrencyController::formatPrice(
                $amount * $this->getDiscountRate() / 100
            );
        }
        $amountAvailable = max(
            0,
            $this->getDiscountAmount() - $this->getUsedAmount($customerId)
        );
        return \Cx\Modules\Shop\Controller\CurrencyController::formatPrice(
            min($amount, $amountAvailable)
        );
    }

    /**
     * Redeem the given coupon code
     *
     * Updates the database, if applicable.
     * Mind that you *MUST* decide which amount (Order or Product) to provide:
     *  - the Product amount if the Coupon has a non-empty Product ID, or
     *  - the Order amount otherwise
     * Provide a zero $uses count (but not null!) when you are storing the
     * Order.  Omit it, or set it to 1 (one) when the Order is complete.
     * The latter is usually the case on the success page, after the Customer
     * has returned to the Shop after paying.
     * Mind that the amount cannot be changed once the record has been
     * created, so only the use count will ever be updated.
     * $uses is never interpreted as anything other than 0 or 1!
     *
     * @param   integer   $orderId          The Order ID
     * @param   integer   $customerId       The Customer ID
     * @param   double    $amount           The Order- or the Product amount
     *                                      (if $this->product_id is non-empty)
     * @param   integer   $uses             The redeem count.  Set to 0 (zero)
     *                                      when storing the Order, omit or
     *                                      set to 1 (one) when redeeming
     *                                      Defaults to 1.
     *
     * @return  \Cx\Modules\Shop\Model\Entity\DiscountCoupon Coupon on success,
     *                                                       false otherwise
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function redeem($orderId, $customerId, $amount, $uses=1)
    {
        // Applicable discount amount
        $amount = $this->getDiscountAmountOrRate($amount);
        $uses = intval((boolean)$uses);

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();

        return $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\RelCustomerCoupon'
        )->redeem($this->getCode(), $orderId, $customerId, $amount, $uses);
    }

}
