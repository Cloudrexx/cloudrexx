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
 * Rel Customer Coupon Repository
 * Used for custom repository methods.
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Repository;

/**
 * Rel Customer Coupon Repository
 * Used for custom repository methods.
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class RelCustomerCouponRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Return the use count of the given Coupon
     *
     * This is the number of times the Coupon has already been redeemed.
     * The optional $customerId limits the result to the uses of that
     * Customer, except for "global" Coupons.
     * Returns 0 (zero) for codes not present in the relation (yet).
     * @param   DiscountCoupon  $coupon         The Coupon
     * @param   int             $customerId     The optional Customer ID
     * @return  int                             The number of uses
     */
    public function getUsedCount(
        \Cx\Modules\Shop\Model\Entity\DiscountCoupon $coupon,
        $customerId = 0
    ) {
        $code = $coupon->getCode();
        $qb = $this->_em->createQueryBuilder();
        $qb->select('sum(rcc.count) as uses')
            ->from($this->_entityName, 'rcc')
            ->where($qb->expr()->eq('rcc.code', '?1'))
            ->setParameter(1, $code);
        if ($customerId && !$coupon->getGlobal()) {
            $qb->andWhere($qb->expr()->eq('rcc.customerId', '?2'))
                ->setParameter(2, $customerId);
        }
        // Result is NULL on zero rows
        return (int) $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * Return the discount amount used with the given Coupon
     *
     * The optional $customerId limits the result to the uses of that
     * Customer, except for "global" Coupons.
     * The $orderId limits to that Order, unless empty.
     * Returns 0 (zero) for Coupons that have not been used with the given
     * parameters, and thus are not present in the relation.
     * @param   DiscountCoupon  $coupon         The Coupon
     * @param   int             $customerId     The optional Customer ID
     * @param   int             $orderId        The optional Order ID
     * @return  float                           The amount used
     */
    public function getUsedAmount(
        \Cx\Modules\Shop\Model\Entity\DiscountCoupon $coupon,
        $customerId = 0, $orderId = 0
    ) {
        $code = $coupon->getCode();
        $qb = $this->_em->createQueryBuilder();
        $qb->select('sum(rcc.amount) as amount')
            ->from($this->_entityName, 'rcc')
            ->where($qb->expr()->eq('rcc.code', '?1'))
            ->setParameter(1, $code);
        if ($customerId && !$coupon->getGlobal()) {
            $qb->andWhere($qb->expr()->eq('rcc.customerId', '?2'))
                ->setParameter(2, $customerId);
        }
        if (!empty($orderId)) {
            $qb->andWhere($qb->expr()->eq('rcc.orderId', '?3'))
                ->setParameter(3, $orderId);
        }
        // Result is NULL on zero rows
        return (float) $qb->getQuery()->getSingleScalarResult();
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
     * @param   integer   $orderId          The Order ID
     * @param   integer   $customerId       The Customer ID
     * @param   double    $amount           The Order- or the Product amount
     *                                      (if $this->product_id is non-empty)
     * @param   integer   $uses             The redeem count.  Set to 0 (zero)
     *                                      when storing the Order, omit or
     *                                      set to 1 (one) when redeeming
     *                                      Defaults to 1.
     *
     * @return  \Cx\Modules\Shop\Model\Entity\RelCustomerCoupon Coupon on
     *                                                  success, false otherwise
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function redeem($code, $orderId, $customerId, $amount, $uses=1)
    {
        $customerCoupon = $this->findOneBy(
            array(
                'code' => $code,
                'orderId' => $orderId,
                'customerId' => $customerId
            )
        );
        if (empty($customerCoupon)) {
            $order = $this->_em->find(
                'Cx\Modules\Shop\Model\Entity\Order', $orderId
            );
            $customer = $this->_em->find(
                'Cx\Core\User\Model\Entity\User', $customerId
            );

            $customerCoupon = new \Cx\Modules\Shop\Model\Entity\RelCustomerCoupon();
            $customerCoupon->setCode($code);
            $customerCoupon->setOrder($order);
            $customerCoupon->setOrderId($orderId);
            $customerCoupon->setCustomer($customer);
            $customerCoupon->setCustomerId($customerId);
            $customerCoupon->setAmount($amount);
        }

        $customerCoupon->setCount($uses);
        $this->_em->persist($customerCoupon);
        $this->_em->flush();

        return $customerCoupon;
    }
}
