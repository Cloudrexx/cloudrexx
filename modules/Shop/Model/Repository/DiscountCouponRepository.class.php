<?php

namespace Cx\Modules\Shop\Model\Repository;

/**
 * DiscountCouponRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DiscountCouponRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * This ensures that only one error message per type is shown
     * @var array
     */
    protected static $hasMessage = array();

    /**
     * Get DiscountCoupon by code and customer
     *
     * @param $code
     * @param $customer
     * @return \Cx\Modules\Shop\Model\Entity\DiscountCoupon
     */
    public function getCouponByCodeAndCustomer($code, $customer)
    {
        $coupon = $this->findOneBy(
            array('code' => $code, 'customer' => $customer)
        );

        if (!empty($coupon)) {
            return $coupon;
        }
        return new \Cx\Modules\Shop\Model\Entity\DiscountCoupon();
    }

    /**
     * Verifies the coupon code and returns the first matching one
     *
     * If the code is valid, returns the Coupon.
     * If the code is unknown, or limited and already exhausted, returns false.
     * Also note that no counter is changed upon verification; to update
     * a coupon after use see {@see redeem()}.
     * Use {@see getByOrderId()} to get a (used) Coupon that was used in
     * conjunction with any partivcular Order, without any verification.
     * @param   string    $code           The coupon code
     * @param   double    $order_amount   The order amount
     * @param   integer   $customer_id    The Customer ID
     * @param   integer   $product_id     The Product ID
     * @param   integer   $payment_id     The Payment ID
     * @return  Coupon                    The matching Coupon on success,
     *                                    false on error, or null otherwise
     */
    public function available($code, $order_amount,
                              $customer_id=null, $product_id=null, $payment_id=null
    ) {
        global $_ARRAYLANG;

        // See if the code exists and is still valid
        $objCoupon = $this->findOneBy(array('code' => $code));
        if ($objCoupon === false) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): ERROR getting the Coupon");
            return false;
        }
        if (!$objCoupon) return null;
        // Verify "ownership" first.  No point in setting status messages
        // that are inappropriate for other users.
        if ($objCoupon->getCustomerId()
            && $objCoupon->getCustomerId() != intval($customer_id)) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Wrong Customer ID");
            return null;
        }
        if ($objCoupon->getProductId() != intval($product_id)) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Wrong Product ID, need ".$objCoupon->product_id);
            if ($objCoupon->getProductId()) {
                if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PRODUCT')) {
                    \Message::information($_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PRODUCT']);
                }
            }
            return null;
        }
        if ($objCoupon->getPaymentId()
            && $objCoupon->getPaymentId() != intval($payment_id)) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Wrong Payment ID");
            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PAYMENT')) {
                \Message::information($_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PAYMENT']);
            }
            return null;
        }
        if ($objCoupon->getStartTime()
            && $objCoupon->getStartTime() > time()) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Not valid yet");
            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_YET')) {
                \Message::information($_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_YET']);
            }
            return null;
        }
        if ($objCoupon->getEndTime()
            && $objCoupon->getEndTime() < time()) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): No longer valid");
            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_ALREADY')) {
                \Message::information($_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_ALREADY']);
            }
            return null;
        }
        // Deduct amounts already redeemed
        if (   floatval($objCoupon->getDiscountAmount()) > 0
            && $objCoupon->getUsedAmount($customer_id) >= $objCoupon->getDiscountAmount()) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Deduct amounts redeemed");
            return null;
        }
        if ($objCoupon->getMinimumAmount() > floatval($order_amount)) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Order amount too low");
            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_FOR_AMOUNT')) {
                \Message::information(sprintf(
                    $_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_FOR_AMOUNT'],
                    $objCoupon->getMinimumAmount(), \Cx\Modules\Shop\Controller\CurrencyController::getActiveCurrencyCode()));
            }
            return null;
        }
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Found ".(var_export($objCoupon, true)));
        // Unlimited uses
        if ($objCoupon->getUses() > 1e9) return $objCoupon;

        // Deduct the number of times the Coupon has been redeemed already:
        // - If the Coupon's customer_id is empty, subtract all uses
        // - Otherwise, subtract the current customer's uses only
        $objCoupon->setUses(
            $objCoupon->getUses()
            - $objCoupon->getUsedCount(
                ($objCoupon->getCustomerId()
                    ? $customer_id : null)));
        if ($objCoupon->getUses() <= 0) {
//DBG::log("Coupon::available($code, $order_amount, $customer_id, $product_id, $payment_id): Fully redeemed");
            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_CAUSE_USED_UP')) {
                \Message::information($_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_CAUSE_USED_UP']);
            }
            return null;
        }
        return $objCoupon;
    }

    /**
     * This ensures that for every message type, only the first one is shown
     * @todo Move this behavior to Message class
     * @param string $type Type name
     * @return boolean Whether we already had such a message or not
     */
    protected function hasMessage($type) {
        $hasMessage = (isset(self::$hasMessage[$type]) && self::$hasMessage[$type]);
        self::$hasMessage[$type] = true;
        return $hasMessage;
    }

    /**
     * Returns the number of Coupons defined
     *
     * @todo    If the $active parameter value is set, limit the number to
     * Coupons of the given status (not implemented yet)
     * @return  integer               The number of Coupons
     */
    public function count_available()//$active=true)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->_entityName);

        if (!empty($qb->getQuery()->getResult()[1])) {
            // The Coupon has been used for so much already
            return $qb->getQuery()->getResult()[1];
        }
        return 0;
    }

}
