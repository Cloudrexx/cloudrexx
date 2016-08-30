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
 * Class Invoice
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Entity;

class InvoiceException extends \Exception {}

/**
 * Class Invoice
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class Invoice extends \Cx\Model\Base\EntityBase {
    /**
     *
     * @var integer $id
     */
    protected $id;

    /**
     * @var Cx\Modules\Order\Model\Entity\Order
     */
    protected $order;

    /**
     * @var Cx\Modules\Order\Model\Entity\Payment
     */
    protected $payments;

    /**
     * @var Cx\Modules\Order\Model\Entity\InvoiceItem
     */
    protected $invoiceItems;


    /**
     *
     * @var integer $paid
     */
    protected $paid;

    /**
     * Constructor
     */
    public function __construct() {
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->invoiceItems = new \Doctrine\Common\Collections\ArrayCollection();
        $this->paid = false;
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
     * Get the order
     *
     * @return \Cx\Modules\Order\Model\Entity\Order $order
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * Set the order
     *
     * @param \Cx\Modules\Order\Model\Entity\Order $order
     */
    public function setOrder(Order $order) {
        $this->order = $order;
    }

    /**
     * Add the payment
     *
     * @param \Cx\Modules\Order\Model\Entity\Payment $payment
     */
    public function addPayment(Payment $payment) {
        $payment->setInvoice($this);
        $this->payments[] = $payment;

        if($payment->getAmount() == $this->getAmount()) {
            $this->paid = true;
        }

        if($payment->getAmount() > $this->getAmount()) {
            throw new InvoiceException('Amount of payment must not be greater than invoice amount');
        }

    }

    /**
     *
     * @return \Cx\Modules\Order\Model\Entity\Payment $payments
     */
    public function getPayments() {
        return $this->payments;
    }

    /**
     * Set the payment
     *
     * @param object $payments
     */
    public function setPayments($payments) {
        $this->payments = $payments;
    }

    /**
     * Get the invoiceItems
     *
     * @return \Cx\Modules\Order\Model\Entity\InvoiceItem $invoiceItems
     */
    public function getInvoiceItems() {
        return $this->invoiceItems;
    }

    /**
     * Set the invoiceItems
     *
     * @param object $invoiceItems
     */
    public function setInvoiceItems($invoiceItems) {
        $this->invoiceItems = $invoiceItems;
    }

    /**
     * Add the invoiceItem
     *
     * @param \Cx\Modules\Order\Model\Entity\InvoiceItem $invoiceItem
     */
    public function addInvoiceItem(InvoiceItem $invoiceItem) {
        $invoiceItem->setInvoice($this);
        $this->invoiceItems[] = $invoiceItem;
    }

    /**
     * Get Sum of all the associated \Cx\Modules\Order\Model\Entity\InvoiceItem::$price
     *
     * @return decimal the sum of all associated \Cx\Modules\Order\Model\Entity\InvoiceItem::$price
     */
    public function getAmount() {
        $totalInvoiceItemPrice = 0;
        foreach($this->invoiceItems as $invoiceItem) {
            $totalInvoiceItemPrice += $invoiceItem->getPrice();
        }
        return $totalInvoiceItemPrice;
    }

    /**
     * Get the paid
     *
     * @return boolean $paid
     */
    public function getPaid() {
        return $this->paid;
    }
}
