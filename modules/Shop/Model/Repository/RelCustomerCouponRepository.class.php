<?php

namespace Cx\Modules\Shop\Model\Repository;

/**
 * RelCustomerCouponRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RelCustomerCouponRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Returns the count of the uses for the given code
     *
     * The optional $customer_id limits the result to the uses of that
     * Customer.
     * Returns 0 (zero) for codes not present in the relation (yet).
     * @param   string    $code           code of coupon
     * @param   integer   $customer_id    The optional Customer ID
     * @return  mixed                     The number of uses of the code
     *                                    on success, false otherwise
     */
    public function getUsedCount($code, $customerId = 0)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('sum(rcc.count) as uses')
            ->from($this->_entityName, 'rcc')
            ->where($qb->expr()->eq('rcc.code', '?1'))
            ->setParameter(1, $code);
        if (!empty($customerId)) {
            $qb->andWhere($qb->expr()->eq('rcc.customerId', '?2'))
                ->setParameter(2, $customerId);
        }

        if (!empty($qb->getQuery()->getArrayResult()[0]['uses'])) {
            return $qb->getQuery()->getArrayResult()[0]['uses'];
        }
        return 0;
    }


    /**
     * Returns the discount amount used with this Coupon
     *
     * The optional $customer_id and $order_id limit the result to the uses
     * of that Customer and Order.
     * Returns 0 (zero) for Coupons that have not been used with the given
     * parameters, and thus are not present in the relation.
     * @param   integer   $customer_id    The optional Customer ID
     * @param   integer   $order_id       The optional Order ID
     * @return  mixed                     The amount used with this Coupon
     *                                    on success, false otherwise
     */
    public function getUsedAmount($code, $customer_id=NULL, $order_id=NULL)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('sum(rcc.amount) as amount')
            ->from($this->_entityName, 'rcc')
            ->where($qb->expr()->eq('rcc.code', '?1'))
            ->setParameter(1, $code);
        if (!empty($customer_id)) {
            $qb->andWhere($qb->expr()->eq('rcc.customerId', '?2'))
                ->setParameter(2, $customer_id);
        }
        if (!empty($order_id)) {
            $qb->andWhere($qb->expr()->eq('rcc.orderId', '?3'))
                ->setParameter(3, $order_id);
        }

        if (!empty($qb->getQuery()->getResult()[0]['amount'])) {
            // The Coupon has been used for so much already
            return $qb->getQuery()->getResult()[0]['amount'];
        }
        return 0;
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
     * @param   integer   $order_id         The Order ID
     * @param   integer   $customer_id      The Customer ID
     * @param   double    $amount           The Order- or the Product amount
     *                                      (if $this->product_id is non-empty)
     * @param   integer   $uses             The redeem count.  Set to 0 (zero)
     *                                      when storing the Order, omit or
     *                                      set to 1 (one) when redeeming
     *                                      Defaults to 1.
     * @return  Coupon                      The Coupon on success,
     *                                      false otherwise
     */
    public function redeem($code, $order_id, $customer_id, $amount, $uses=1)
    {
        $customerCoupon = $this->findOneBy(
            array(
                'code' => $code,
                'orderId' => $order_id,
                'customerId' => $customer_id
            )
        );
        if (empty($customerCoupon)) {
            $order = $this->_em->find('Cx\Modules\Shop\Model\Entity\Order', $order_id);
            $customer = $this->_em->find('Cx\Core\User\Model\Entity\User', $customer_id);

            $customerCoupon = new \Cx\Modules\Shop\Model\Entity\RelCustomerCoupon();
            $customerCoupon->setCode($code);
            $customerCoupon->setOrder($order);
            $customerCoupon->setOrderId($order_id);
            $customerCoupon->setCustomer($customer);
            $customerCoupon->setCustomerId($customer_id);
            $customerCoupon->setAmount($amount);
        }

        $customerCoupon->setCount($uses);
        $this->_em->persist($customerCoupon);
        $this->_em->flush();

        return $customerCoupon;
    }


}
