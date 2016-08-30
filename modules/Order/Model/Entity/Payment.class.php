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
 * Class Payment
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Entity;

/**
 * Class Invoice
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class Payment extends \Cx\Model\Base\EntityBase {
    /**
     *
     * @var integer $id
     */
    protected $id;

    /**
     * @var \Cx\Modules\Order\Model\Entity\Invoice $invoice
     */
    protected $invoice;

    /**
     *
     * @var DateTime $date
     */
    protected $date;

    /**
     *
     * @var double $amount
     */
    protected $amount;

    /**
     *
     * @var string $transactionReference
     */
    protected $transactionReference;

    /**
     *
     * @var string $handler
     */
    protected $handler;

    /**
     *
     * @var array $transactionData
     */
    protected $transactionData = array();

    const HANDLER_CASH = 'cash';
    const HANDLER_PAYREXX = 'payrexx';


    /**
     * Constructor
     */
    public function __construct() {
        $this->date = new \DateTime();
        $this->handler = self::HANDLER_CASH;
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
     * Get the transaction reference
     * @return  string  The transaction reference of the payment
     */
    public function getTransactionReference() {
        return $this->transactionReference;
    }

    /**
     * Set the transaction reference
     * @param   string  $transactionReference The transaction reference to set
     */
    public function setTransactionReference($transactionReference) {
        $this->transactionReference = $transactionReference;
    }

    /**
     * Get the payment amount
     * @return double
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * Set the payment amount
     * @param   double $amount The amount the payment to set to
     */
    public function setAmount($amount) {
        $this->amount = $amount;
    }

    /**
     * Get the payment date of the payment
     * @return  DateTime    The date the payment was made
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set the date of the payment
     * @param   DateTime    The date the payment was made
     */
    public function setDate($date) {
        $this->date = $date;
    }

    /**
     * Get the invoice
     *
     * @return \Cx\Modules\Order\Model\Entity\Invoice $invoice
     */
    public function getInvoice() {
        return $this->invoice;
    }

    /**
     * Set the invoice
     *
     * @param \Cx\Modules\Order\Model\Entity\Invoice $invoice
     */
    public function setInvoice(Invoice $invoice) {
        $this->invoice = $invoice;
    }

    /**
     * Get the handler
     *
     * @return string $handler
     */
    public function getHandler() {
        return $this->handler;
    }

    /**
     * Set the handler
     *
     * @param string $handler
     */
    public function setHandler($handler) {
        $this->handler = $handler;
    }

    /**
     * Set the transactionData
     *
     * @param array $transactionData transactionData
     */
    public function setTransactionData($transactionData)
    {
        $this->transactionData = $transactionData;
    }

    /**
     * Get transactionData
     *
     * @return array
     */
    public function getTransactionData()
    {
        return $this->transactionData;
    }
}
