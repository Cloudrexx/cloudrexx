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
 * Class Orders
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * An Order contains all information from the customer as well as all ordered
 * products. It contains also the shipper and payment.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Order extends \Cx\Model\Base\EntityBase {

    const USERNAME_PREFIX = 'shop_customer';

    /**
     * Folder name for (image) file uploads in the Shop
     *
     * Note that this is prepended with the document root when necessary.
     */
    const UPLOAD_FOLDER = 'media/Shop/upload/';

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $customerId;

    /**
     * @var integer
     */
    protected $currencyId;

    /**
     * @var string
     */
    protected $sum = '0.00';

    /**
     * @var \DateTime
     */
    protected $dateTime = '0000-00-00 00:00:00';

    /**
     * @var boolean
     */
    protected $status;

    /**
     * @var string
     */
    protected $gender;

    /**
     * @var string
     */
    protected $company;

    /**
     * @var string
     */
    protected $firstname;

    /**
     * @var string
     */
    protected $lastname;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $zip;

    /**
     * @var integer
     */
    protected $countryId;

    /**
     * @var string
     */
    protected $phone;

    /**
     * @var string
     */
    protected $vatAmount = '0.00';

    /**
     * @var string
     */
    protected $shipmentAmount = '0.00';

    /**
     * @var integer
     */
    protected $shipmentId;

    /**
     * @var integer
     */
    protected $paymentId;

    /**
     * @var string
     */
    protected $paymentAmount = '0.00';

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var integer
     */
    protected $langId;

    /**
     * @var string
     */
    protected $note;

    /**
     * @var \DateTime
     */
    protected $modifiedOn;

    /**
     * @var string
     */
    protected $modifiedBy;

    /**
     * @var string
     */
    protected $billingGender;

    /**
     * @var string
     */
    protected $billingCompany;

    /**
     * @var string
     */
    protected $billingFirstname;

    /**
     * @var string
     */
    protected $billingLastname;

    /**
     * @var string
     */
    protected $billingAddress;

    /**
     * @var string
     */
    protected $billingCity;

    /**
     * @var string
     */
    protected $billingZip;

    /**
     * @var integer
     */
    protected $billingCountryId;

    /**
     * @var string
     */
    protected $billingPhone;

    /**
     * @var string
     */
    protected $billingFax;

    /**
     * @var string
     */
    protected $billingEmail;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $lsvs;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $orderItems;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $relCustomerCoupons;

    /**
     * @var \Cx\Core\Locale\Model\Entity\Locale
     */
    protected $lang;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Currency
     */
    protected $currency;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Shipper
     */
    protected $shipper;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Payment
     */
    protected $payment;

    /**
     * @var \Cx\Core\User\Model\Entity\User
     */
    protected $customer;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lsvs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orderItems = new \Doctrine\Common\Collections\ArrayCollection();
        $this->relCustomerCoupons = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set customerId
     *
     * @param integer $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * Get customerId
     *
     * @return integer 
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Set currencyId
     *
     * @param integer $currencyId
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    /**
     * Get currencyId
     *
     * @return integer 
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * Set sum
     *
     * @param string $sum
     */
    public function setSum($sum)
    {
        $this->sum = $sum;
    }

    /**
     * Get sum
     *
     * @return string 
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * Set dateTime
     *
     * @param \DateTime $dateTime
     * @return Orders
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * Get dateTime
     *
     * @return \DateTime 
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Orders
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set gender
     *
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * Get gender
     *
     * @return string 
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set company
     *
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Get company
     *
     * @return string 
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set zip
     *
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * Get zip
     *
     * @return string 
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set countryId
     *
     * @param integer $countryId
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
    }

    /**
     * Get countryId
     *
     * @return integer 
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * Set phone
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set vatAmount
     *
     * @param string $vatAmount
     */
    public function setVatAmount($vatAmount)
    {
        $this->vatAmount = $vatAmount;
    }

    /**
     * Get vatAmount
     *
     * @return string 
     */
    public function getVatAmount()
    {
        return $this->vatAmount;
    }

    /**
     * Set shipmentAmount
     *
     * @param string $shipmentAmount
     */
    public function setShipmentAmount($shipmentAmount)
    {
        $this->shipmentAmount = $shipmentAmount;
    }

    /**
     * Get shipmentAmount
     *
     * @return string 
     */
    public function getShipmentAmount()
    {
        return $this->shipmentAmount;
    }

    /**
     * Set shipmentId
     *
     * @param integer $shipmentId
     */
    public function setShipmentId($shipmentId)
    {
        $this->shipmentId = $shipmentId;
    }

    /**
     * Get shipmentId
     *
     * @return integer 
     */
    public function getShipmentId()
    {
        return $this->shipmentId;
    }

    /**
     * Set paymentId
     *
     * @param integer $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * Get paymentId
     *
     * @return integer 
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * Set paymentAmount
     *
     * @param string $paymentAmount
     */
    public function setPaymentAmount($paymentAmount)
    {
        $this->paymentAmount = $paymentAmount;
    }

    /**
     * Get paymentAmount
     *
     * @return string 
     */
    public function getPaymentAmount()
    {
        return $this->paymentAmount;
    }

    /**
     * Set ip
     *
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set langId
     *
     * @param integer $langId
     */
    public function setLangId($langId)
    {
        $this->langId = $langId;
    }

    /**
     * Get langId
     *
     * @return integer 
     */
    public function getLangId()
    {
        return $this->langId;
    }

    /**
     * Set note
     *
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set modifiedOn
     *
     * @param \DateTime $modifiedOn
     */
    public function setModifiedOn($modifiedOn)
    {
        $this->modifiedOn = $modifiedOn;
    }

    /**
     * Get modifiedOn
     *
     * @return \DateTime 
     */
    public function getModifiedOn()
    {
        return $this->modifiedOn;
    }

    /**
     * Set modifiedBy
     *
     * @param string $modifiedBy
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;
    }

    /**
     * Get modifiedBy
     *
     * @return string 
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set billingGender
     *
     * @param string $billingGender
     */
    public function setBillingGender($billingGender)
    {
        $this->billingGender = $billingGender;
    }

    /**
     * Get billingGender
     *
     * @return string 
     */
    public function getBillingGender()
    {
        return $this->billingGender;
    }

    /**
     * Set billingCompany
     *
     * @param string $billingCompany
     */
    public function setBillingCompany($billingCompany)
    {
        $this->billingCompany = $billingCompany;
    }

    /**
     * Get billingCompany
     *
     * @return string 
     */
    public function getBillingCompany()
    {
        return $this->billingCompany;
    }

    /**
     * Set billingFirstname
     *
     * @param string $billingFirstname
     */
    public function setBillingFirstname($billingFirstname)
    {
        $this->billingFirstname = $billingFirstname;
    }

    /**
     * Get billingFirstname
     *
     * @return string
     */
    public function getBillingFirstname()
    {
        return $this->billingFirstname;
    }

    /**
     * Set billingLastname
     *
     * @param string $billingLastname
     */
    public function setBillingLastname($billingLastname)
    {
        $this->billingLastname = $billingLastname;
    }

    /**
     * Get billingLastname
     *
     * @return string 
     */
    public function getBillingLastname()
    {
        return $this->billingLastname;
    }

    /**
     * Set billingAddress
     *
     * @param string $billingAddress
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;
    }

    /**
     * Get billingAddress
     *
     * @return string 
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * Set billingCity
     *
     * @param string $billingCity
     */
    public function setBillingCity($billingCity)
    {
        $this->billingCity = $billingCity;
    }

    /**
     * Get billingCity
     *
     * @return string 
     */
    public function getBillingCity()
    {
        return $this->billingCity;
    }

    /**
     * Set billingZip
     *
     * @param string $billingZip
     */
    public function setBillingZip($billingZip)
    {
        $this->billingZip = $billingZip;
    }

    /**
     * Get billingZip
     *
     * @return string 
     */
    public function getBillingZip()
    {
        return $this->billingZip;
    }

    /**
     * Set billingCountryId
     *
     * @param integer $billingCountryId
     */
    public function setBillingCountryId($billingCountryId)
    {
        $this->billingCountryId = $billingCountryId;
    }

    /**
     * Get billingCountryId
     *
     * @return integer 
     */
    public function getBillingCountryId()
    {
        return $this->billingCountryId;
    }

    /**
     * Set billingPhone
     *
     * @param string $billingPhone
     */
    public function setBillingPhone($billingPhone)
    {
        $this->billingPhone = $billingPhone;
    }

    /**
     * Get billingPhone
     *
     * @return string 
     */
    public function getBillingPhone()
    {
        return $this->billingPhone;
    }

    /**
     * Set billingFax
     *
     * @param string $billingFax
     */
    public function setBillingFax($billingFax)
    {
        $this->billingFax = $billingFax;
    }

    /**
     * Get billingFax
     *
     * @return string 
     */
    public function getBillingFax()
    {
        return $this->billingFax;
    }

    /**
     * Set billingEmail
     *
     * @param string $billingEmail
     */
    public function setBillingEmail($billingEmail)
    {
        $this->billingEmail = $billingEmail;
    }

    /**
     * Get billingEmail
     *
     * @return string 
     */
    public function getBillingEmail()
    {
        return $this->billingEmail;
    }

    /**
     * Add lsv
     *
     * @param \Cx\Modules\Shop\Model\Entity\Lsv $lsv
     */
    public function addLsv(\Cx\Modules\Shop\Model\Entity\Lsv $lsv)
    {
        $this->lsvs[] = $lsv;
    }

    /**
     * Remove lsv
     *
     * @param \Cx\Modules\Shop\Model\Entity\Lsv $lsv
     */
    public function removeLsv(\Cx\Modules\Shop\Model\Entity\Lsv $lsv)
    {
        $this->lsvs->removeElement($lsv);
    }

    /**
     * Get lsvs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLsvs()
    {
        return $this->lsvs;
    }

    /**
     * Add orderItem
     *
     * @param \Cx\Modules\Shop\Model\Entity\OrderItem $orderItem
     */
    public function addOrderItem(\Cx\Modules\Shop\Model\Entity\OrderItem $orderItem)
    {
        $this->orderItems[] = $orderItem;
    }

    /**
     * Remove orderItem
     *
     * @param \Cx\Modules\Shop\Model\Entity\OrderItem $orderItem
     */
    public function removeOrderItem(\Cx\Modules\Shop\Model\Entity\OrderItem $orderItem)
    {
        $this->orderItems->removeElement($orderItem);
    }

    /**
     * Get orderItems
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderItems()
    {
        return $this->orderItems;
    }

    /**
     * Add relCustomerCoupon
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelCustomerCoupon $relCustomerCoupon
     */
    public function addRelCustomerCoupon(\Cx\Modules\Shop\Model\Entity\RelCustomerCoupon $relCustomerCoupon)
    {
        $this->relCustomerCoupons[] = $relCustomerCoupon;
    }

    /**
     * Remove relCustomerCoupon
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelCustomerCoupon $relCustomerCoupon
     */
    public function removeRelCustomerCoupon(\Cx\Modules\Shop\Model\Entity\RelCustomerCoupon $relCustomerCoupon)
    {
        $this->relCustomerCoupons->removeElement($relCustomerCoupon);
    }

    /**
     * Get relCustomerCoupons
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRelCustomerCoupons()
    {
        return $this->relCustomerCoupons;
    }

    /**
     * Set currencies
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $lang
     */
    public function setLang(\Cx\Core\Locale\Model\Entity\Locale $lang = null)
    {
        $this->lang = $lang;
    }

    /**
     * Get lang
     *
     * @return \Cx\Core\Locale\Model\Entity\Locale
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set currency
     *
     * @param \Cx\Modules\Shop\Model\Entity\Currency $currency
     */
    public function setCurrency(\Cx\Modules\Shop\Model\Entity\Currency $currency = null)
    {
        $this->currency = $currency;
    }

    /**
     * Get currency
     *
     * @return \Cx\Modules\Shop\Model\Entity\Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set shipper
     *
     * @param \Cx\Modules\Shop\Model\Entity\Shipper $shipper
     */
    public function setShipper(\Cx\Modules\Shop\Model\Entity\Shipper $shipper = null)
    {
        $this->shipper = $shipper;
    }

    /**
     * Get shipper
     *
     * @return \Cx\Modules\Shop\Model\Entity\Shipper 
     */
    public function getShipper()
    {
        return $this->shipper;
    }

    /**
     * Set payment
     *
     * @param \Cx\Modules\Shop\Model\Entity\Payment $payment
     */
    public function setPayment(\Cx\Modules\Shop\Model\Entity\Payment $payment = null)
    {
        $this->payment = $payment;
    }

    /**
     * Get payment
     *
     * @return \Cx\Modules\Shop\Model\Entity\Payment 
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set customer
     *
     * @param \Cx\Core\User\Model\Entity\User $customer
     */
    public function setCustomer(\Cx\Core\User\Model\Entity\User $customer = null)
    {
        $this->customer = $customer;
    }

    /**
     * Get customer
     *
     * @return \Cx\Core\User\Model\Entity\User
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Returns an array of items contained in this Order
     * @global  ADONewConnection    $objDatabase
     * @global  array               $_ARRAYLANG
     * @return  array                               The items array on success,
     *                                              false otherwise
     * @todo    Let items be handled by their own class
     */
    function getItems($withHtmlNotation = true)
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "
            SELECT `id`, `product_id`, `product_name`,
                   `price`, `quantity`, `vat_rate`, `weight`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_order_items`
             WHERE `order_id`=?";
        $objResult = $objDatabase->Execute($query, array($this->getId()));
        if (!$objResult) {
            return self::errorHandler();
        }
        $arrProductOptions = $this->getOptionArray($withHtmlNotation);
        $items = array();
        while (!$objResult->EOF) {
            $item_id = $objResult->fields['id'];
            $product_id = $objResult->fields['product_id'];
            $name = $objResult->fields['product_name'];
            $price = $objResult->fields['price'];
            $quantity = $objResult->fields['quantity'];
            $vat_rate = $objResult->fields['vat_rate'];
            // Get missing product details
            $objProduct = \Cx\Modules\Shop\Controller\Product::getById($product_id);
            if (!$objProduct) {
                \Message::warning(sprintf(
                    $_ARRAYLANG['TXT_SHOP_PRODUCT_NOT_FOUND'], $product_id));
                $objProduct = new Product('', 0, $name, '', $price,
                    0, 0, 0, $product_id);
            }
            $code = $objProduct->code();
            $distribution = $objProduct->distribution();
            $vat_id = $objProduct->vat_id();
            $weight = '0';
            if ($distribution != 'download') {
                $weight = $objResult->fields['weight'];
            }
            $item = array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'name' => $name,
                'price' => $price,
                'item_id' => $item_id,
                'code' => $code,
                'vat_id' => $vat_id,
                'vat_rate' => $vat_rate,
                'weight' => $weight,
                'attributes' => array(),
            );
            if (isset($arrProductOptions[$item_id])) {
                $item['attributes'] = $arrProductOptions[$item_id];
            }
            $items[] = $item;
            $objResult->MoveNext();
        }
        return $items;
    }


    /**
     * Returns an array of Attributes and chosen options for this Order
     *
     * Options for uploads are linked to their respective files
     * The array looks like this:
     *  array(
     *    item ID => array(
     *      "Attribute name" => array(
     *        Attribute ID => array
     *          'name' => "option name",
     *          'price' => "price",
     *         ),
     *       [... more ...]
     *      ),
     *    ),
     *    [... more ...]
     *  )
     * Note that the array may be empty.
     * @return  array           The Attribute/option array on success,
     *                          null otherwise
     */
    function getOptionArray($withHtmlNotation = true)
    {
        global $objDatabase;

        $query = "
            SELECT `attribute`.`id`, `attribute`.`item_id`, `attribute`.`attribute_name`,
                   `attribute`.`option_name`, `attribute`.`price`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_order_attributes` AS `attribute`
              JOIN `".DBPREFIX."module_shop".MODULE_INDEX."_order_items` AS `item`
                ON `attribute`.`item_id`=`item`.`id`
             WHERE `item`.`order_id`=".$this->getId()."
             ORDER BY `attribute`.`attribute_name` ASC, `attribute`.`option_name` ASC";
        $objResult = $objDatabase->Execute($query);
        $arrProductOptions = array();
        while (!$objResult->EOF) {
            $option_full = $objResult->fields['option_name'];
            $option = \Cx\Modules\Shop\Controller\ShopLibrary::stripUniqidFromFilename($option_full);
            $path = Order::UPLOAD_FOLDER.$option_full;
            // Link option names to uploaded files
            if (   $option != $option_full
                && \File::exists($path)) {
                if ($withHtmlNotation) {
                    $option =
                        '<a href="'.$path.'" target="uploadFile">'.$option.'</a>';
                }
            }
            $id = $objResult->fields['id'];
            $price = $objResult->fields['price'];
            $arrProductOptions[$objResult->fields['item_id']]
            [$objResult->fields['attribute_name']][$id] = array(
                'name' => $option,
                'price' => $price,
            );
            $objResult->MoveNext();
        }
        return $arrProductOptions;
    }


    /**
     * Inserts a single item into the database
     *
     * Note that all parameters are mandatory.
     * All of $order_id, $product_id, and $quantity must be greater than zero.
     * The $weight must not be negative.
     * If there are no options, set $arrOptions to the empty array.
     * Sets an error Message in case there is anything wrong.
     * @global  ADONewConnection    $objDatabase
     * @global  array   $_ARRAYLANG
     * @param   integer $order_id       The Order ID
     * @param   integer $product_id     The Product ID
     * @param   string  $name           The item name
     * @param   float   $price          The item price (one unit)
     * @param   integer $quantity       The quantity (in units)
     * @param   float   $vat_rate       The applicable VAT rate
     * @param   integer $weight         The item weight (in grams, one unit)
     * @param   array   $arrOptions     The array of selected options
     * @return  boolean                 True on success, false otherwise
     * @static
     */
    public function insertItem($product, $name, $price, $quantity,
                               $vat_rate, $weight, $arrOptions
    ) {
        global $_ARRAYLANG;

        $product_id = intval($product->getId());
        if ($product_id <= 0) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_ORDER_ITEM_ERROR_INVALID_PRODUCT_ID']);
        }
        $quantity = intval($quantity);
        if ($quantity <= 0) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_ORDER_ITEM_ERROR_INVALID_QUANTITY']);
        }
        $weight = intval($weight);
        if ($weight < 0) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_ORDER_ITEM_ERROR_INVALID_WEIGHT']);
        }

        $orderItem = new \Cx\Modules\Shop\Model\Entity\OrderItem();
        $orderItem->setOrderId($this->getId());
        $orderItem->setOrder($this);
        $orderItem->setProduct($product);
        $orderItem->setProductId($product->getId());
        $orderItem->setPrice($price);
        $orderItem->setProductName($name);
        $orderItem->setQuantity($quantity);
        $orderItem->setWeight($weight);
        $orderItem->setVatRate($vat_rate);

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $em->persist($orderItem);

        foreach ($arrOptions as $attribute_id => $arrOptionIds) {
            $orderItem->insertAttribute($attribute_id, $arrOptionIds);
        }
        $em->persist($orderItem);
        $this->addOrderItem($orderItem);
    }

}
