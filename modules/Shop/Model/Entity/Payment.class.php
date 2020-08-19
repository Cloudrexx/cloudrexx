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
 * Class Payment
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * A payment is used to pay for an order. It contains a payment processor which
 * handle the payment. Additional charges can be defined for a payment. It can
 * be assigned to a zone if it can only be used in a limited way.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Payment extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $processorId;

    /**
     * @var string
     */
    protected $fee = '0.00';

    /**
     * @var string
     */
    protected $freeFrom = '0.00';

    /**
     * @var integer
     */
    protected $ord;

    /**
     * @var boolean
     */
    protected $active = true;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $discountCoupons;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $orders;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\PaymentProcessors
     */
    protected $paymentProcessor;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $zones;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->discountCoupons = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orders = new \Doctrine\Common\Collections\ArrayCollection();
        $this->zones = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set translatable locale
     *
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        if (!is_string($locale) || !strlen($locale)) {
            $this->locale = $locale;
        }
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
     * Set processorId
     *
     * @param integer $processorId
     * @return Payment
     */
    public function setProcessorId($processorId)
    {
        $this->processorId = $processorId;
    }

    /**
     * Get processorId
     *
     * @return integer 
     */
    public function getProcessorId()
    {
        return $this->processorId;
    }

    /**
     * Set fee
     *
     * @param string $fee
     * @return Payment
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }

    /**
     * Get fee
     *
     * @return string 
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set freeFrom
     *
     * @param string $freeFrom
     */
    public function setFreeFrom($freeFrom)
    {
        $this->freeFrom = $freeFrom;
    }

    /**
     * Get freeFrom
     *
     * @return string 
     */
    public function getFreeFrom()
    {
        return $this->freeFrom;
    }

    /**
     * Set ord
     *
     * @param integer $ord
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;
    }

    /**
     * Get ord
     *
     * @return integer 
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add discountCoupon
     *
     * @param \Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon
     */
    public function addDiscountCoupon(\Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon)
    {
        $this->discountCoupons[] = $discountCoupon;
    }

    /**
     * Remove discountCoupon
     *
     * @param \Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon
     */
    public function removeDiscountCoupon(\Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon)
    {
        $this->discountCoupons->removeElement($discountCoupon);
    }

    /**
     * Get discountCoupons
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDiscountCoupons()
    {
        return $this->discountCoupons;
    }

    /**
     * Add order
     *
     * @param \Cx\Modules\Shop\Model\Entity\Orders $order
     */
    public function addOrder(\Cx\Modules\Shop\Model\Entity\Order $order)
    {
        $this->orders[] = $order;
    }

    /**
     * Remove order
     *
     * @param \Cx\Modules\Shop\Model\Entity\Order $order
     */
    public function removeOrder(\Cx\Modules\Shop\Model\Entity\Order $order)
    {
        $this->orders->removeElement($order);
    }

    /**
     * Get orders
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Set paymentProcessor
     *
     * @param \Cx\Modules\Shop\Model\Entity\PaymentProcessor $paymentProcessor
     */
    public function setPaymentProcessor(\Cx\Modules\Shop\Model\Entity\PaymentProcessor $paymentProcessor = null)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Get paymentProcessor
     *
     * @return \Cx\Modules\Shop\Model\Entity\PaymentProcessor
     */
    public function getPaymentProcessor()
    {
        return $this->paymentProcessor;
    }

    /**
     * Add zone
     *
     * @param \Cx\Modules\Shop\Model\Entity\Zone $zone
     */
    public function addZone(\Cx\Modules\Shop\Model\Entity\Zone $zone)
    {
        $this->zones[] = $zone;
    }

    /**
     * Remove zone
     *
     * @param \Cx\Modules\Shop\Model\Entity\Zone $zone
     */
    public function removeZone(\Cx\Modules\Shop\Model\Entity\Zone $zone)
    {
        $this->zones->removeElement($zone);
    }

    /**
     * Get zones
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getZones()
    {
        return $this->zones;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
