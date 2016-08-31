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
 * Class InvoiceItem
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Entity;

/**
 * Class InvoiceItem
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class InvoiceItem extends \Cx\Model\Base\EntityBase {
    /**
     *
     * @var integer $id
     */
    protected $id;
    /**
     *
     * @var decimal $price
     */
    protected $price;
    /**
     *
     * @var string $description
     */
    protected $description;

    /**
     * @var \Cx\Modules\Order\Model\Entity\Invoice $invoice
     */
    protected $invoice;

    /**
     * Constructor
     */
    public function __construct() {
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
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }
    /**
     * Get the price
     *
     * @return decimal
     */
    public function getPrice() {
        return $this->price;
    }
    /**
     * Set the price
     *
     * @param decimal $price
     */
    public function setPrice($price) {
        $this->price = $price;
    }
    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
    /**
     * Set the description
     *
     * @param decimal $description
     */
    public function setDescription($description) {
        $this->description = $description;
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
}
