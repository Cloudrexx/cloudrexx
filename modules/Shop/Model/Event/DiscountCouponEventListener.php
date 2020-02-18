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
 * Event listener for all discount coupon events
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Event;

/**
 * Event listener for all discount coupon events
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class DiscountCouponEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{

    /**
     * Validate discount coupon before persist event
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs arguments of
     *
     *
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     * @throws \Doctrine\ORM\ORMException
     */
    public function prePersist(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs)
    {
        $this->validateCoupon(
            $eventArgs->getEntity(),
            $eventArgs->getEntityManager()
        );
    }

    /**
     * Validate discount coupon before update event
     *
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     * @throws \Doctrine\ORM\ORMException
     */
    public function preUpdate(\Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs)
    {
        $this->validateCoupon(
            $eventArgs->getEntity(),
            $eventArgs->getEntityManager()
        );
    }

    /**
     * Check if coupon is valid
     *
     * @param $coupon \Cx\Modules\Shop\Model\Entity\DiscountCoupon coupon to check
     * @param $em     \Doctrine\ORM\EntityManager                  em of entity
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     * @throws \Doctrine\ORM\ORMException
     */
    protected function validateCoupon($coupon, $em)
    {
        global $_ARRAYLANG;

        // Validate code
        if (empty($coupon->getCode()) || strlen($coupon->getCode()) < 6) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG[
                    'TXT_SHOP_DISCOUNT_COUPON_ERROR_ADDING_INVALID_CODE'
                ]
            );
        }

        $couponRepo = $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\DiscountCoupon'
        );

        $couponWithCode = $couponRepo->getCouponByCodeAndCustomer(
            $coupon->getCode(), $coupon->getCustomer()
        );

        if (
            !empty($couponWithCode->getId()) &&
            $coupon->getId() != $couponWithCode->getId()
        ) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_ERROR_ADDING_CODE_EXISTS']
            );
        }

        // Validate minimum Amount
        $coupon->setMinimumAmount(
            \Cx\Modules\Shop\Controller\CurrencyController::formatPrice(
                max(0, $coupon->getMinimumAmount())
            )
        );

        // Validate rate and amount
        // These all default to zero if invalid
        $coupon->setDiscountRate(max(0, $coupon->getDiscountRate()));

        $coupon->setDiscountAmount(
            \Cx\Modules\Shop\Controller\CurrencyController::formatPrice(
                max(0, $coupon->getDiscountAmount())
            )
        );

        if (
            empty($coupon->getDiscountRate()) &&
            (
                empty($coupon->getDiscountAmount()) ||
                $coupon->getDiscountAmount() == '0.00'
            )
        ) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG[
                    'TXT_SHOP_DISCOUNT_COUPON_ERROR_ADDING_MISSING_RATE_OR_'.
                    'AMOUNT'
                ]
            );
        }

        if (
            $coupon->getDiscountRate() &&
            (
                $coupon->getDiscountAmount() &&
                $coupon->getDiscountAmount() != '0.00'
            )
        ) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG[
                    'TXT_SHOP_DISCOUNT_COUPON_ERROR_ADDING_EITHER_RATE_OR_'.
                    'AMOUNT'
                ]
            );
        }

        // Validate endtime and starttime
        // These must be non-negative integers and default to zero
        $coupon->setStartTime(max(0, intval($coupon->getStartTime())));
        $coupon->setEndTime(max(0, intval($coupon->getEndTime())));
        if ($coupon->getEndTime() && $coupon->getEndTime() < time()) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG[
                    'TXT_SHOP_DISCOUNT_COUPON_ERROR_ADDING_INVALID_END_TIME'
                ]
            );
        }

        // Validate uses
        $coupon->setUses(max(0, intval($coupon->getUses())));
        if (empty($coupon->getUses())) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG[
                    'TXT_SHOP_DISCOUNT_COUPON_ERROR_ADDING_INVALID_USES'
                ]
            );
        }
    }
}