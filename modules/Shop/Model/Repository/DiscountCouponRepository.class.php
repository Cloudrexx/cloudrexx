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
 * Discount Coupon Repository
 * Used for custom repository methods.
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Repository;

/**
 * Discount Coupon Repository
 * Used for custom repository methods.
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
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
     * @param string $code                              code to search
     * @param \Cx\Core\User\Model\Entity\User $customer customer to search
     *
     * @return \Cx\Modules\Shop\Model\Entity\DiscountCoupon found coupon
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
     *
     * @param   string    $code           The coupon code
     * @param   double    $orderAmount    The order amount
     * @param   integer   $customerId     The Customer ID
     * @param   integer   $productId      The Product ID
     * @param   integer   $paymentId      The Payment ID
     *
     * @return  \Cx\Modules\Shop\Model\Entity\DiscountCoupon The matching Coupon
     *                             on success, false on error, or null otherwise
     */
    public function available(
        $code, $orderAmount, $customerId=null, $productId=null, $paymentId=null
    ) {
        global $_ARRAYLANG;

        // See if the code exists and is still valid
        $objCoupon = $this->findOneBy(array('code' => $code));
        if ($objCoupon === false) {
            return false;
        }
        if (!$objCoupon) return null;
        // Verify "ownership" first.  No point in setting status messages
        // that are inappropriate for other users.
        if ($objCoupon->getCustomerId()
            && $objCoupon->getCustomerId() != intval($customerId)) {

            return null;
        }
        if ($objCoupon->getProductId() != intval($productId)) {
            if ($objCoupon->getProductId()) {
                if (!$this->hasMessage(
                    'TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PRODUCT'
                )) {
                    \Message::information(
                        $_ARRAYLANG[
                            'TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PRODUCT'
                        ]
                    );
                }
            }
            return null;
        }
        if ($objCoupon->getPaymentId()
            && $objCoupon->getPaymentId() != intval($paymentId)) {
            if (!$this->hasMessage(
                'TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PAYMENT'
            )) {
                \Message::information(
                    $_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_FOR_THIS_PAYMENT']
                );
            }
            return null;
        }
        if ($objCoupon->getStartTime()
            && $objCoupon->getStartTime() > time()) {
            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_YET')) {
                \Message::information(
                    $_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_YET']
                );
            }
            return null;
        }
        if ($objCoupon->getEndTime()
            && $objCoupon->getEndTime() < time()) {
            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_ALREADY')) {
                \Message::information(
                    $_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_ALREADY']
                );
            }
            return null;
        }
        // Deduct amounts already redeemed
        if (floatval($objCoupon->getDiscountAmount()) > 0 &&
            $objCoupon->getUsedAmount($customerId) >=
            $objCoupon->getDiscountAmount()
        ) {
            return null;
        }
        if ($objCoupon->getMinimumAmount() > floatval($orderAmount)) {

            if (!$this->hasMessage('TXT_SHOP_COUPON_UNAVAILABLE_FOR_AMOUNT')) {
                \Message::information(
                    sprintf(
                        $_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_FOR_AMOUNT'],
                        $objCoupon->getMinimumAmount(),
                        \Cx\Modules\Shop\Controller\CurrencyController
                            ::getActiveCurrencyCode()
                    )
                );
            }
            return null;
        }

        // Unlimited uses
        if ($objCoupon->getUses() > 1e9) return $objCoupon;

        // Deduct the number of times the Coupon has been redeemed already:
        // - If the Coupon's customer_id is empty, subtract all uses
        // - Otherwise, subtract the current customer's uses only
        $objCoupon->setUses(
            $objCoupon->getUses()
            - $objCoupon->getUsedCount(
                ($objCoupon->getCustomerId() ? $customerId : null)
            )
        );
        if ($objCoupon->getUses() <= 0) {

            if (!$this->hasMessage(
                'TXT_SHOP_COUPON_UNAVAILABLE_CAUSE_USED_UP'
            )) {
                \Message::information(
                    $_ARRAYLANG['TXT_SHOP_COUPON_UNAVAILABLE_CAUSE_USED_UP']
                );
            }
            return null;
        }
        return $objCoupon;
    }

    /**
     * This ensures that for every message type, only the first one is shown
     * @todo Move this behavior to Message class
     *
     * @param string $type Type name
     *
     * @return boolean Whether we already had such a message or not
     */
    protected function hasMessage($type)
    {
        $hasMessage = (
            isset(self::$hasMessage[$type]) && self::$hasMessage[$type]
        );
        self::$hasMessage[$type] = true;
        return $hasMessage;
    }

    /**
     * Returns the number of Coupons defined
     *
     * @todo    If the $active parameter value is set, limit the number to
     * Coupons of the given status (not implemented yet)
     *
     * @return  integer               The number of Coupons
     */
    public function count_available()//$active=true)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('count(dc)')->from($this->_entityName, 'dc');

        if (!empty($qb->getQuery()->getResult()[0][1])) {
            // The Coupon has been used for so much already
            return $qb->getQuery()->getResult()[0][1];
        }
        return 0;
    }
}
