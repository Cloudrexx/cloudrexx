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
 * Class Order
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Entity;

/**
 * Class Order
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class Order extends \Cx\Model\Base\EntityBase {
    /**
     *
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $contactId
     */
    protected $contactId;

    /**
     * @var \Cx\Modules\Crm\Model\Entity\Currency
     */
    protected $currency;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection $subscriptions
     */
    protected $subscriptions;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection $invoices
     */
    protected $invoices;

    /**
     * Constructor
     */
    public function __construct() {
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->invoices = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set the contactId
     *
     * @param integer $contactId
     */
    public function setContactId($contactId) {
        $this->contactId = $contactId;
    }

    /**
     * get the contactId
     *
     * @return integer $contactId
     */
    public function getContactId() {
        return $this->contactId;
    }

    /**
     * Set currency
     *
     * @param \Cx\Modules\Crm\Model\Entity\Currency $currency
     */
    public function setCurrency(\Cx\Modules\Crm\Model\Entity\Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * Get currency
     *
     * @return \Cx\Modules\Crm\Model\Entity\Currency $currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Add the subscription
     *
     * @param \Cx\Modules\Order\Model\Entity\Subscription $subscription
     */
    public function addSubscription(Subscription $subscription) {
        $this->subscriptions[] = $subscription;
    }

    /**
     * Get the subscription
     *
     * @return \Cx\Modules\Order\Model\Entity\subscription $subscriptions
     */
    public function getSubscriptions() {
        return $this->subscriptions;
    }

    /**
     * Set the subscription
     *
     * @param object $subscriptions
     */
    public function setSubscriptions($subscriptions) {
        $this->subscriptions = $subscriptions;
    }

    public function createSubscription($product, $subscriptionOptions) {
        $subscription = new Subscription($product, $subscriptionOptions);
        $subscription->setOrder($this, true);
        \Env::get('em')->persist($subscription);
        $this->addSubscription($subscription);

        return $subscription;
    }

    /**
     * Get the Invoices
     *
     * @return array
     */
    public function getInvoices() {
        return $this->invoices;
    }

    /**
     * Set the invoices
     *
     * @param array $invoices
     */
    public function setInvoices($invoices) {
        $this->invoices = $invoices;
    }

    /**
     *
     * @param \Cx\Modules\Order\Model\Entity\Invoice $invoice
     */
    public function addInvoice(Invoice $invoice) {
        $this->invoices[] = $invoice;
    }

    public function complete() {
        foreach ($this->subscriptions as $subscription) {
            $subscription->payComplete();
        }
    }
    /**
     * Add Invoice to the subscriptions
     *
     */
    public function billSubscriptions() {
        $subscriptions = array();
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getPaymentState() == $subscription::PAYMENT_OPEN ||
                ($subscription->getPaymentState() == $subscription::PAYMENT_RENEWAL && $subscription->getRenewalDate()->getTimestamp() <= time())) {
                $subscriptions[] = $subscription;
            }
        }
        if(empty($subscriptions)) {
            return;
        }
        //Create New Invoice
        $invoice = new Invoice();

        foreach ($subscriptions as $subscription) {
            //Create New Invoice Item
            $invoiceItem = new InvoiceItem();
            //Add InvoiceItem::$description to Subscription::getProductEntity()
            $invoiceItem->setDescription($subscription->getProduct()->getName() . ' (' . $subscription->getProductEntity() . ')');
            //Add InvoiceItem::$price to Subscription::getPaymentAmount()
            $invoiceItem->setPrice($subscription->getPaymentAmount());

            \Env::get('em')->persist($invoiceItem);
            //Attached to the created invoice
            $invoice->addInvoiceItem($invoiceItem);
        }
        $invoice->setOrder($this);
        \Env::get('em')->persist($invoice);
        //Attached to the order
        $this->addInvoice($invoice);
    }
    /**
     *
     * @return array all associated Invoices that have attribute \Cx\Modules\Order\Model\Entity\Invoice::$paid set to false.
     */
    public function getUnpaidInvoices() {
        $invoices = array();
        foreach ($this->invoices as $invoice) {
            if(!$invoice->getPaid()) {
                $invoices[] = $invoice;
            }
        }
        return $invoices;

    }
}
