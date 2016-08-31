<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
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
 * Class Subscription
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Entity;

class SubscriptionException extends \Exception {}

/**
 * Class Subscription
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class Subscription extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var Cx\Modules\Order\Model\Entity\Order
     */
    protected $order;

    /**
     * @var Cx\Modules\Pim\Model\Entity\Product
     */
    protected $product;

    protected $subscriptionDate = null;
    protected $expirationDate = null;
    protected $productEntityId = null;
    protected $productEntity = null;
    protected $paymentAmount = null;
    protected $paymentState;
    protected $renewalUnit = null;
    protected $renewalQuantifier = null;
    protected $renewalDate = null;
    protected $externalSubscriptionId = null;
    protected $description = null;
    protected $note = null;

    /**
     *
     * @var string $state
     */
    protected $state;

    /**
     *
     * @var datetime $terminationDate
     */
    protected $terminationDate;

    const PAYMENT_OPEN = 'open';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_RENEWAL = 'renewal';
    const STATE_ACTIVE      = 'active';
    const STATE_INACTIVE    = 'inactive';
    const STATE_TERMINATED  = 'terminated';
    const STATE_CANCELLED  = 'cancelled';

    /**
     * Constructor
     */
    public function __construct($product, $options) {
        // Important:
        // A subscription must always have a valid $product.
        // The following exception is for the sole purpose of making this class
        // compatible with the \Cx\Core\Html\Controller\ViewGenerator library
        // for autogenerating user-interfaces.
        if (!$product) {
            return;
        }

        $this->subscriptionDate = new \DateTime();
        $this->product = $product;
        $this->setProductEntity($product->getNewEntityForSale($options));

        $objCurrency = null;
        if ($this->getOrder()) {
            $objCurrency = $this->getOrder()->getCurrency();
        } else {
            $defaultCurrencyId = \Cx\Modules\Crm\Controller\CrmLibrary::getDefaultCurrencyId();
            $objCurrency = $defaultCurrencyId ? \Env::get('em')->getRepository('Cx\Modules\Crm\Model\Entity\Currency')->findOneById($defaultCurrencyId) : null;
        }

        $this->paymentAmount = $product->getPaymentAmount($options['renewalUnit'], $options['renewalQuantifier'], $objCurrency);
        $this->paymentState = self::PAYMENT_OPEN;
        if ($product->isExpirable()) {
            $this->expirationDate = $product->getExpirationDate($options['renewalUnit'], $options['renewalQuantifier']);
        }
        if ($product->isRenewable()) {
            list($this->renewalUnit, $this->renewalQuantifier) = $product->getRenewalDefinition($options['renewalUnit'], $options['renewalQuantifier']);
            $this->renewalDate = $product->getRenewalDate($this->renewalUnit, $this->renewalQuantifier);
        }
        if (isset($options['description'])) {
            $this->setDescription($options['description']);
        }
        //Set state initialized to active.
        $this->state = self::STATE_ACTIVE;
    }

    /**
     * Get the id
     *
     * @return integer $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the id
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Set the order
     *
     * @param \Cx\Modules\Order\Model\Entity\Order $order
     */
    public function setOrder(Order $order, $updatePaymentAmount = false) {
        $this->order = $order;
        if (!$this->getProduct()) {
            return;
        }
        if ($updatePaymentAmount) {
            $this->paymentAmount = $this->getProduct()->getPaymentAmount($this->getRenewalUnit(), $this->getRenewalQuantifier(), $order->getCurrency());
        }
    }

    /**
     * Get the order
     *
     * @return \Cx\Modules\Order\Model\Entity\Order $order
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * Set the product
     *
     * @param integer $product
     */
    public function setProduct($product) {
        $this->product = $product;
    }

    /**
     * Get the product
     *
     * @return integer $product
     */
    public function getProduct() {
        return $this->product;
    }

    public function getExpirationDate() {
        return $this->expirationDate;
    }

    public function setExpirationDate($expirationDate) {
        $this->expirationDate = $expirationDate;
    }

    public function getProductEntityId() {
        return $this->productEntityId;
    }

    public function setProductEntityId($productEntityId) {
        $this->productEntityId = $productEntityId;
    }

    public function getProductEntity() {
        if (!$this->productEntity && $this->productEntityId && $this->product) {
            $this->productEntity = $this->product->getEntityById($this->productEntityId);
        }
        return $this->productEntity;
    }

    public function setProductEntity($productEntity) {
        $this->productEntity = $productEntity;
        $this->productEntityId = null;
        if ($productEntity instanceof \Cx\Model\Base\EntityBase) {
            $entityIdKey = \Env::get('em')->getClassMetadata(get_class($productEntity))->getSingleIdentifierFieldName();
            $this->productEntityId = $productEntity->{'get'.ucfirst($entityIdKey)}();
        }
    }

    public function getPaymentAmount() {
        return $this->paymentAmount;
    }

    public function setPaymentAmount($paymentAmount) {
        $this->paymentAmount = $paymentAmount;
    }

    public function getPaymentState() {
        return $this->paymentState;
    }

    public function setPaymentState($paymentState) {
        $this->paymentState = $paymentState;
    }

    public function getRenewalUnit() {
        return $this->renewalUnit;
    }

    public function setRenewalUnit($renewalUnit) {
        $this->renewalUnit = $renewalUnit;
    }

    public function getRenewalQuantifier() {
        return $this->renewalQuantifier;
    }

    public function setRenewalQuantifier($renewalQuantifier) {
        $this->renewalQuantifier = $renewalQuantifier;
    }

    public function getRenewalDate() {
        return $this->renewalDate;
    }

    public function setRenewalDate($renewalDate) {
        $this->renewalDate = $renewalDate;
    }

    /**
     * Getter for $description
     *
     * @return string $description
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Setter for $description
     *
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Getter for $note
     *
     * @return string $note
     */
    public function getNote() {
        return $this->note;
    }

    /**
     * Setter for $note
     *
     * @param string $note
     */
    public function setNote($note) {
        $this->note = $note;
    }

    /**
     * Get the externalSubscriptionId
     *
     * @return integer
     */
    public function getExternalSubscriptionId() {
        return $this->externalSubscriptionId;
    }

    /**
     * Set the externalSubscriptionId
     *
     * @param integer $externalSubscriptionId
     */
    public function setExternalSubscriptionId($externalSubscriptionId) {
        $this->externalSubscriptionId = $externalSubscriptionId;
    }

    public function payComplete() {
        if ($this->getProduct()->isRenewable()) {
            // update renewal period and date
            list($this->renewalUnit, $this->renewalQuantifier) = $this->getProduct()->getRenewalDefinition($this->renewalUnit, $this->renewalQuantifier);
            $renewalDate = $this->getProduct()->getRenewalDate($this->renewalUnit, $this->renewalQuantifier);
            $this->setRenewalDate($renewalDate);
            $this->setPaymentState(self::PAYMENT_RENEWAL);
        } else {
            $this->setPaymentState(self::PAYMENT_PAID);
        }

        \Env::get('cx')->getEvents()->triggerEvent('model/payComplete', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
    }
    /**
     * Get the date time object of subscription date
     *
     * @return object date time object of subscription date
     */
    public function getSubscriptionDate() {
        return $this->subscriptionDate;
    }
    /**
     * Set the date time object of subscription date
     *
     * @param object date time object of subscription date
     */
    public function setSubscriptionDate($subscriptionDate) {
        $this->subscriptionDate = $subscriptionDate;
    }

    /**
     * Get the state
     *
     * @return string state of the subscription
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the state
     *
     * @param string $state state of the subscription
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get the subscription's termination date
     *
     * @return object date time object of termination date
     */
    public function getTerminationDate()
    {
        return $this->terminationDate;
    }

    /**
     * Set the subscription's termination date
     *
     * @param object $terminationDate date time object of termination date
     */
    public function setTerminationDate($terminationDate)
    {
        $this->terminationDate = $terminationDate;
    }

    /**
     * Change Subscription State to Terminate.
     *
     * @throws WebsiteException
     */
    public function terminate()
    {
        global $_ARRAYLANG;

        if ($this->externalSubscriptionId) {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            $instanceName  = \Cx\Core\Setting\Controller\Setting::getValue('payrexxAccount','MultiSite');
            $apiSecret     = \Cx\Core\Setting\Controller\Setting::getValue('payrexxApiSecret','MultiSite');
            if(empty($instanceName) || empty($apiSecret)) {
                return;
            }
            $payrexx = new \Payrexx\Payrexx($instanceName, $apiSecret);

            $subscription = new \Payrexx\Models\Request\Subscription();
            $subscription->setId($this->externalSubscriptionId);
            try {
                $response = $payrexx->cancel($subscription);
                if ((isset($response['status']) && $response['status'] != 'success')
                        || (isset($response['data']['status']) && $response['data']['status'] != 'cancelled')) {
                    throw new SubscriptionException($_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_PAYREXX_CANCEL_FAILED']);
                }
            } catch (\Payrexx\PayrexxException $e) {
                throw new SubscriptionException($e->getMessage());
            }
        }
        //set state terminated.
        $this->setState(self::STATE_TERMINATED);
        //Set current date/time
        $this->setTerminationDate(new \DateTime());
        //Trigger the model event terminated on the subscription's product entity.
        \Env::get('cx')->getEvents()->triggerEvent('model/terminated', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
    }
}
