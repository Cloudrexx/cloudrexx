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
 * Order Repository
 * Used for custom repository methods
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Repository;

/**
 * Order Repository
 * Used for custom repository methods.
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class OrderRepository extends \Doctrine\ORM\EntityRepository
{
    const usernamePrefix = 'shop_customer';
    const STATUS_PENDING   = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_DELETED   = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_PAID      = 5;
    const STATUS_SHIPPED   = 6;

    const STATUS_MAX = 6;

    /**
     * Get an array with all status values
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return array all status values
     */
    public function getStatusValues()
    {
        global $_ARRAYLANG;

        $statusValues = array();
        for ($i = 0; $i <= $this::STATUS_MAX; $i++) {
            array_push(
                $statusValues,
                $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_' . $i]
            );
        }

        return $statusValues;
    }

    /**
     * Deletes the Order with the given ID
     *
     * @param   integer   $id           The Order ID
     * @param   boolean   $updateStock  True to update stock of the product
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteById($id, $updateStock = false)
    {
        $id = contrexx_input2int($id);
        if (empty($id)) {
            return;
        }

        $order = $this->findOneBy(array('id'=>$id));
        $objUser = \FWUser::getFWUserObject()->objUser;

        if ($order) {
            if ($customer = $objUser->getUser($order->getCustomerId())) {
                $usernamePrefix = \Cx\Modules\Shop\Model\Entity\Order::USERNAME_PREFIX;

                $customerEmail = $usernamePrefix ."_${$id}_%-"
                    . $customer->getEmail();
                $allCustomerWithEmail = $objUser->getUsers(
                    array('email' => $customerEmail)
                );

                foreach ($allCustomerWithEmail as $customerWithEmail) {
                    $customerWithEmail->setActiveStatus(false);
                }
            }

            $order->setStatus($this::STATUS_DELETED);
            $order->setModifiedBy(contrexx_raw2db($objUser->getUsername()));
            $order->setModifiedOn(new \DateTime('now'));

            if ($updateStock) {
                $this->updateStock($order);
            }

            $this->_em->persist($order);
            $this->_em->flush();
        }

    }

    /**
     * Update related product stock
     *
     * @param boolean $increaseStock True to increase stock, false to decrease
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function updateStock($order, $increaseStock = true)
    {
        global $_ARRAYLANG;

        $items = $order->getOrderItems();

        foreach ($items as $item) {
            $product =  $item->getProduct();

            if (!$product) {
                \DBG::log(
                    sprintf(
                        $_ARRAYLANG['TXT_SHOP_PRODUCT_NOT_FOUND'],
                        $product->getId()
                    )
                );
                continue;
            }

            $stock = $product->getStock();
            if ($increaseStock) {
                $stock += $item->getQuantity();
            } else {
                $stock -= $item->getQuantity();
            }

            $product->setStock($stock);
            $this->_em->persist($product);
        }

        $this->_em->flush();
    }

    /**
     * Get first order
     *
     * @return \Cx\Modules\Shop\Model\Entity\Order
     */
    public function getFirstOrder()
    {
        $firstOrder = $this->findOneBy(array(), array('dateTime' => 'ASC'));

        return $firstOrder;
    }

    /**
     * Updates the status of the Order with the given ID
     *
     * If the order exists and has the pending status (status == 0),
     * it is updated according to the payment and distribution type.
     * Note that status other than pending are never changed!
     * If the optional argument $newOrderStatus is set and not pending,
     * the order status is set to that value instead.
     * Returns the new Order status on success.
     * If either the order ID is invalid, or if the update fails, returns
     * the Order status "pending" (zero).
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param   integer $order_id    The ID of the current order
     * @param   integer $newOrderStatus The optional new order status.
     * @param   string  $handler    The Payment type name in use
     *
     * @return  integer             The new order status (may be zero)
     *                              if the order status can be changed
     *                              accordingly, zero otherwise
     * @throws  \Doctrine\ORM\OptimisticLockException
     */
    public function update_status($order_id, $newOrderStatus=0, $handler=NULL)
    {
        global $_ARRAYLANG;

        if (is_null($handler) && isset($_REQUEST['handler'])) {
            $handler = contrexx_input2raw($_REQUEST['handler']);
        }
        $order_id = intval($order_id);
        if ($order_id == 0) {
            return self::STATUS_CANCELLED;
        }

        $order = $this->findOneBy(array('id' => $order_id));
        if (empty($order)) {
            return self::STATUS_CANCELLED;
        }
        $status = $order->getStatus();
        // Never change a non-pending status!
        // Whether a payment was successful or not, the status must be
        // left alone.
        if ($status != self::STATUS_PENDING) {
            // The status of the order is not pending.
            // This may be due to a wrong order ID, a page reload,
            // or a PayPal IPN that has been received already.
            // No order status is changed automatically in these cases!
            // Leave it as it is.
            return $status;
        }
        // Determine and verify the payment handler
        $payment_id = $order->getPaymentId();
//if (!$payment_id) DBG::log("update_status($order_id, $newOrderStatus): Failed to find Payment ID for Order ID $order_id");
        $processor_id = \Cx\Modules\Shop\Controller\Payment::
            getPaymentProcessorId($payment_id);
//if (!$processor_id) DBG::log("update_status($order_id, $newOrderStatus): Failed to find Processor ID for Payment ID $payment_id");
        $processorName = \Cx\Modules\Shop\Controller\PaymentProcessing::
            getPaymentProcessorName($processor_id);
//if (!$processorName) DBG::log("update_status($order_id, $newOrderStatus): Failed to find Processor Name for Processor ID $processor_id");
        // The payment processor *MUST* match the handler returned.
        if (!preg_match("/^$handler/i", $processorName)) {
//DBG::log("update_status($order_id, $newOrderStatus): Mismatching Handlers: Order $processorName, Request ".$_GET['handler']);
            return self::STATUS_CANCELLED;
        }
        // Only if the optional new order status argument is zero,
        // determine the new status automatically.
        if ($newOrderStatus == self::STATUS_PENDING) {
            // The new order status is determined by two properties:
            // - The method of payment (instant/deferred), and
            // - The method of delivery (if any).
            // If the payment takes place instantly (currently, all
            // external payments processors are considered to do so),
            // and there is no delivery needed (because it's all
            // downloads), the order status is switched to 'completed'
            // right away.
            // If only one of these conditions is met, the status is set to
            // 'paid', or 'delivered' respectively.
            // If neither condition is met, the status is set to 'confirmed'.
            $newOrderStatus = self::STATUS_CONFIRMED;
            $processorType =
                \Cx\Modules\Shop\Controller\PaymentProcessing::
                    getCurrentPaymentProcessorType($processor_id);
            $shipmentId = $order->getShipmentId();
            if ($processorType == 'external') {
                // External payment types are considered instant.
                // See $_SESSION['shop']['isInstantPayment'].
                if ($shipmentId == 0) {
                    // instant, download -> completed
                    $newOrderStatus = self::STATUS_COMPLETED;
                } else {
                    // There is a shipper, so this order will bedelivered.
                    // See $_SESSION['shop']['isDelivery'].
                    // instant, delivery -> paid
                    $newOrderStatus = self::STATUS_PAID;
                }
            } else {
                // Internal payment types are considered deferred.
                if ($shipmentId == 0) {
                    // deferred, download -> shipped
                    $newOrderStatus = self::STATUS_SHIPPED;
                }
                //else { deferred, delivery -> confirmed }
            }
        }
        $order->setStatus($newOrderStatus);
        $this->_em->persist($order);
        $this->_em->flush();

        if (   $newOrderStatus == self::STATUS_CONFIRMED
            || $newOrderStatus == self::STATUS_PAID
            || $newOrderStatus == self::STATUS_SHIPPED
            || $newOrderStatus == self::STATUS_COMPLETED) {
            if (
                !\Cx\Modules\Shop\Controller\ShopLibrary::sendConfirmationMail(
                    $order_id
                )
            ) {
                // Note that this message is only shown when the page is
                // displayed, which may be on another request!
                \Message::error($_ARRAYLANG['TXT_SHOP_UNABLE_TO_SEND_EMAIL']);
            }
        }
        // The shopping cart *MUST* be flushed right after this method
        // returns a true value (greater than zero).
        // If the new order status is zero however, the cart may
        // be left alone and the payment process can be tried again.
        return $newOrderStatus;
    }

    /**
    /**
     * Returns an array with all placeholders and their values to be
     * replaced in any shop mailtemplate for the given order ID.
     *
     * You only have to set the 'substitution' index value of your MailTemplate
     * array to the array returned.
     * Customer data is not included here.
     * See {@see Customer::getSubstitutionArray()}.
     * Note that this method is now mostly independent of the current session.
     * The language of the mail template is determined by the browser
     * language range stored with the order.
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param integer $order_id        The order ID
     * @param boolean $create_accounts If true, creates User accounts
     *                                 and Coupon codes.  Defaults to true
     * @param boolean $updateStock     If the stock is to be updated
     *
     * @return array                 The array with placeholders as keys
     *                               and values from the order on success,
     *                               false otherwise
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getSubstitutionArray($order_id, $create_accounts=true, $updateStock=true)
    {
        global $_ARRAYLANG;
        /*
                    $_ARRAYLANG['TXT_SHOP_URI_FOR_DOWNLOAD'].":\r\n".
                    'http://'.$_SERVER['SERVER_NAME'].
                    "/index.php?section=download\r\n";
        */
        $objOrder = $this->find($order_id);
        if (!$objOrder) {
            // Order not found
            return false;
        }

        $currency = $objOrder->getCurrency();

        $lang_id = $objOrder->getLangId();
        $status = $objOrder->getStatus();
        $customer_id = $objOrder->getCustomerId();
        $customer = \Cx\Modules\Shop\Controller\Customer::getById($customer_id);
        $payment_id = $objOrder->getPaymentId();
        $shipment_id = $objOrder->getShipmentId();
        $arrSubstitution = array (
            'CUSTOMER_COUNTRY_ID' => $objOrder->getBillingCountryId(),
            'LANG_ID' => $lang_id,
            'NOW' => date(ASCMS_DATE_FORMAT_DATETIME),
            'TODAY' => date(ASCMS_DATE_FORMAT_DATE),
//            'DATE' => date(ASCMS_DATE_FORMAT_DATE, strtotime($objOrder->date_time())),
            'ORDER_ID' => $order_id,
            'ORDER_ID_CUSTOM' =>
                \Cx\Modules\Shop\Controller\ShopLibrary::getCustomOrderId(
                    $order_id
                ),
// TODO: Use proper localized date formats
            'ORDER_DATE' =>
                date(
                    ASCMS_DATE_FORMAT_DATE,
                    strtotime($objOrder->getDateTime()->format('d.m.y'))
                ),
            'ORDER_TIME' =>
                date(
                    ASCMS_DATE_FORMAT_TIME,
                    strtotime($objOrder->getDateTime()->format('d.m.y'))
                ),
            'ORDER_STATUS_ID' => $status,
            'ORDER_STATUS' => $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_'.$status],
            'MODIFIED' =>
                date(
                    ASCMS_DATE_FORMAT_DATETIME,
                    strtotime($objOrder->getModifiedOn())
                ),
            'REMARKS' => $objOrder->getNote(),
            'ORDER_SUM' => sprintf('% 9.2f', $objOrder->getSum()),
            'CURRENCY' => $currency->getCode(),
        );
        $arrSubstitution += $customer->getSubstitutionArray();
        if ($shipment_id) {
            $arrSubstitution += array (
                'SHIPMENT' => array(0 => array(
                    'SHIPMENT_NAME' => sprintf(
                        '%-40s',
                        \Cx\Modules\Shop\Controller\Shipment::getShipperName(
                            $shipment_id
                        )
                    ),
                    'SHIPMENT_PRICE' => sprintf(
                        '% 9.2f', $objOrder->getShipmentAmount()
                    ),
                )),
// Unused
//                'SHIPMENT_ID' => $objOrder->shipment_id(),
                'SHIPPING_ADDRESS' => array(0 => array(
                    'SHIPPING_COMPANY' => $objOrder->getCompany(),
                    'SHIPPING_TITLE' =>
                        $_ARRAYLANG['TXT_SHOP_'.strtoupper(
                            $objOrder->getGender()
                        )],
                    'SHIPPING_FIRSTNAME' => $objOrder->getFirstname(),
                    'SHIPPING_LASTNAME' => $objOrder->getLastname(),
                    'SHIPPING_ADDRESS' => $objOrder->getAddress(),
                    'SHIPPING_ZIP' => $objOrder->getZip(),
                    'SHIPPING_CITY' => $objOrder->getCity(),
                    'SHIPPING_COUNTRY_ID' => $objOrder->getCountryId(),
                    'SHIPPING_COUNTRY' =>
                        \Cx\Core\Country\Controller\Country::getNameById(
                            $objOrder->getCountryId()
                        ),
                    'SHIPPING_PHONE' => $objOrder->getPhone(),
                )),
            );
        }
        if ($payment_id) {
            $arrSubstitution += array (
                'PAYMENT' => array(0 => array(
                    'PAYMENT_NAME' => sprintf(
                        '%-40s',
                        \Cx\Modules\Shop\Controller\Payment::getNameById(
                            $payment_id
                        )
                    ),
                    'PAYMENT_PRICE' => sprintf(
                        '% 9.2f',
                        $objOrder->getPaymentAmount()
                    ),
                )),
            );
        }
        $arrItems = $objOrder->getItems();
        if (!$arrItems) {
            \Message::warning($_ARRAYLANG['TXT_SHOP_ORDER_WARNING_NO_ITEM']);
        }
        // Deduct Coupon discounts, either from each Product price, or
        // from the items total.  Mind that the Coupon has already been
        // stored with the Order, but not redeemed yet.  This is done
        // in this method, but only if $create_accounts is true.
        $coupon_code = NULL;
        $coupon_amount = 0;
        $customerCouponRepo = $this->_em->getRepository(
            'Cx\Modules\Shop\Model\Entity\RelCustomerCoupon'
        );
        $couponRepo = $this->_em->getRepository(
            'Cx\Modules\Shop\Model\Entity\DiscountCoupon'
        );
        $objCustomerCoupon = $customerCouponRepo->findOneBy(
            array('orderId' => $order_id)
        );

        if ($objCustomerCoupon) {
            $coupon_code = $objCustomerCoupon->getCode();
        }
        $orderItemCount = 0;
        $total_item_price = 0;
        // Suppress Coupon messages (see Coupon::available())
        \Message::save();
        foreach ($arrItems as $item) {
            $product_id = $item['product_id'];
            $objProduct = \Cx\Modules\Shop\Controller\Product::getById(
                $product_id
            );
            if (!$objProduct) {
//die("Product ID $product_id not found");
                continue;
            }
//DBG::log("Orders::getSubstitutionArray(): Item: Product ID $product_id");
            $product_name = substr($item['name'], 0, 40);
            $item_price = $item['price'];
            $quantity = $item['quantity'];
// TODO: Add individual VAT rates for Products
//            $orderItemVatPercent = $objResultItem->fields['vat_percent'];
            if ($updateStock) {
                // Decrease the Product stock count,
                // applies to "real", shipped goods only
                $objProduct->decreaseStock($quantity);
            }
            $product_code = $objProduct->code();
            // Pick the order items attributes
            $str_options = '';
            $optionList = array();
            // Any attributes?
            if ($item['attributes']) {
                $str_options = '  '; // '[';
                $attribute_name_previous = '';
                foreach (
                    $item['attributes'] as $attribute_name => $arrAttribute
                ) {
                    $optionValues = array();
//DBG::log("Attribute /$attribute_name/ => ".var_export($arrAttribute, true));
// NOTE: The option price is optional and may be left out
                    foreach ($arrAttribute as $arrOption) {
                        $option = array();
                        $option_name = $arrOption['name'];
                        $option_price = $arrOption['price'];
                        $item_price += $option_price;
                        // Recognize the names of uploaded files,
                        // verify their presence and use the original name
                        $option_name_stripped =
                            \Cx\Modules\Shop\Controller\ShopLibrary::
                                stripUniqidFromFilename($option_name);
                        $path = \Cx\Modules\Shop\Model\Entity\Order::
                                UPLOAD_FOLDER
                            . $option_name;
                        if (   $option_name != $option_name_stripped
                            && \File::exists($path)) {
                            $option_name = $option_name_stripped;
                        }
                        if ($attribute_name != $attribute_name_previous) {
                            if ($attribute_name_previous) {
                                $str_options .= '; ';
                            }
                            $str_options .= $attribute_name.': '.$option_name;
                            $attribute_name_previous = $attribute_name;
                        } else {
                            $str_options .= ', '.$option_name;
                        }
                        $option['PRODUCT_OPTIONS_VALUE'] = $option_name;
// TODO: Add proper formatting with sprintf() and language entries
                        if ($option_price != 0) {
                            $str_options .=
                                ' './/' ('.
                                \Cx\Modules\Shop\Controller\CurrencyController::
                                formatPrice($option_price)
                                . ' ' .
                                \Cx\Modules\Shop\Controller\CurrencyController::
                                    getActiveCurrencyCode()
//                                .')'
                            ;
                            $option['PRODUCT_OPTIONS_PRICE'] =
                                \Cx\Modules\Shop\Controller\CurrencyController::
                                    formatPrice($option_price);
                            $option['PRODUCT_OPTIONS_CURRENCY'] =
                                \Cx\Modules\Shop\Controller\CurrencyController::
                                    getActiveCurrencyCode();
                        }
                        $optionValues[] = $option;
                    }
                    $optionList[] = array(
                        'PRODUCT_OPTIONS_NAME' => $attribute_name,
                        'PRODUCT_OPTIONS_VALUES' => $optionValues,
                    );
                }
//                $str_options .= ']';
            }
            // Product details
            $arrProduct = array(
                'PRODUCT_ID' => $product_id,
                'PRODUCT_CODE' => $product_code,
                'PRODUCT_QUANTITY' => $quantity,
                'PRODUCT_TITLE' => $product_name,
                'PRODUCT_OPTIONS' => $str_options,
                'PRODUCT_OPTION_LIST' => $optionList,
                'PRODUCT_ITEM_PRICE' => sprintf('% 9.2f', $item_price),
                'PRODUCT_TOTAL_PRICE' => sprintf(
                    '% 9.2f', $item_price*$quantity
                ),
            );
//DBG::log("Orders::getSubstitutionArray($order_id, $create_accounts): Adding article: ".var_export($arrProduct, true));
            $orderItemCount += $quantity;
            $total_item_price += $item_price*$quantity;
            if ($create_accounts) {
                // Add an account for every single instance of every Product
                for ($instance = 1; $instance <= $quantity; ++$instance) {
                    $validity = 0; // Default to unlimited validity
                    // In case there are protected downloads in the cart,
                    // collect the group IDs
                    $arrUsergroupId = array();
                    if ($objProduct->distribution() == 'download') {
                        $usergroupIds = $objProduct->usergroup_ids();
                        if ($usergroupIds != '') {
                            $arrUsergroupId = explode(',', $usergroupIds);
                            $validity = $objProduct->weight();
                        }
                    }
                    // create an account that belongs to all collected
                    // user groups, if any.
                    if (count($arrUsergroupId) > 0) {
                        // The login names are created separately for
                        // each product instance
                        $username =
                            self::usernamePrefix.
                            "_${order_id}_${product_id}_${instance}";
                        $userEmail =
                            $username.'-'.$arrSubstitution['CUSTOMER_EMAIL'];
                        $userpass = \User::make_password();
                        $objUser = new \User();
                        $objUser->setUsername($username);
                        $objUser->setPassword($userpass);
                        $objUser->setEmail($userEmail);
                        $objUser->setAdminStatus(false);
                        $objUser->setActiveStatus(true);
                        $objUser->setGroups($arrUsergroupId);
                        $objUser->setValidityTimePeriod($validity);
                        $objUser->setFrontendLanguage(FRONTEND_LANG_ID);
                        $objUser->setBackendLanguage(FRONTEND_LANG_ID);
                        $objUser->setProfile(array(
                            'firstname' => array(
                                0 => $arrSubstitution['CUSTOMER_FIRSTNAME']
                            ),
                            'lastname' => array(
                                0 => $arrSubstitution['CUSTOMER_LASTNAME']
                            ),
                            'company' => array(
                                0 => $arrSubstitution['CUSTOMER_COMPANY']
                            ),
                            'address' => array(
                                0 => $arrSubstitution['CUSTOMER_ADDRESS']
                            ),
                            'zip' => array(
                                0 => $arrSubstitution['CUSTOMER_ZIP']
                            ),
                            'city' => array(
                                0 => $arrSubstitution['CUSTOMER_CITY']
                            ),
                            'country' => array(
                                0 => $arrSubstitution['CUSTOMER_COUNTRY_ID']
                            ),
                            'phone_office' => array(
                                0 => $arrSubstitution['CUSTOMER_PHONE']
                            ),
                            'phone_fax' => array(
                                0 => $arrSubstitution['CUSTOMER_FAX']
                            ),
                        ));
                        if (!$objUser->store()) {
                            \Message::error(implode(
                                '<br />', $objUser->getErrorMsg()));
                            return false;
                        }
                        if (empty($arrProduct['USER_DATA']))
                            $arrProduct['USER_DATA'] = array();
                        $arrProduct['USER_DATA'][] = array(
                            'USER_NAME' => $username,
                            'USER_PASS' => $userpass,
                        );
                    }
//echo("Instance $instance");
                    if ($objProduct->distribution() == 'coupon') {
                        if (empty($arrProduct['COUPON_DATA']))
                            $arrProduct['COUPON_DATA'] = array();
//DBG::log("Orders::getSubstitutionArray(): Getting code");
                        $code =
                            \Cx\Modules\Shop\Controller\DiscountCouponController::
                                getNewCode();

                        $newCoupon =
                            new \Cx\Modules\Shop\Model\Entity\DiscountCoupon();
                        $newCoupon->setCode($code);
                        $newCoupon->setDiscountAmount($item_price);
                        $newCoupon->setGlobal(true);
                        $newCoupon->setUses(1e10);

                        $this->_em->persist($newCoupon);
                        $this->_em->flush();

                        $arrProduct['COUPON_DATA'][] = array(
                            'COUPON_CODE' => $code
                        );
                    }
                }
                // Redeem the *product* Coupon, if possible for the Product
                if ($coupon_code) {
                    $objCoupon = $couponRepo->available($coupon_code,
                        $item_price*$quantity, $customer_id, $product_id,
                        $payment_id);
                    if ($objCoupon) {
                        $coupon_code = NULL;
                        $coupon_amount = $objCoupon->getDiscountAmountOrRate(
                            $item_price, $customer_id);
                        if ($create_accounts) {
                            $objCoupon->redeem($order_id, $customer_id,
                                $item_price*$quantity);
                        }
                    }
//\DBG::log("Orders::getSubstitutionArray(): Got Product Coupon $coupon_code");
                }
            }
            if (empty($arrSubstitution['ORDER_ITEM']))
                $arrSubstitution['ORDER_ITEM'] = array();
            $arrSubstitution['ORDER_ITEM'][] = $arrProduct;
        }
        $arrSubstitution['ORDER_ITEM_SUM'] =
            sprintf('% 9.2f', $total_item_price);
        $arrSubstitution['ORDER_ITEM_COUNT'] = sprintf('% 4u', $orderItemCount);
        // Redeem the *global* Coupon, if possible for the Order
        if ($coupon_code) {
            $objCoupon = $couponRepo->available($coupon_code,
                $total_item_price, $customer_id, null, $payment_id);
            if ($objCoupon) {
                $coupon_amount = $objCoupon->getDiscountAmountOrRate(
                    $total_item_price, $customer_id);
                if ($create_accounts) {
                    $objCoupon->redeem($order_id, $customer_id, $total_item_price);
                }
            }
        }
        \Message::restore();
        // Fill in the Coupon block with proper discount and amount
        if ($objCoupon) {
            $coupon_code = $objCoupon->getCode();
//\DBG::log("Orders::getSubstitutionArray(): Coupon $coupon_code, amount $coupon_amount");
        }
        if ($coupon_amount) {
//\DBG::log("Orders::getSubstitutionArray(): Got Order Coupon $coupon_code");
            $arrSubstitution['DISCOUNT_COUPON'][] = array(
                'DISCOUNT_COUPON_CODE' => sprintf('%-40s', $coupon_code),
                'DISCOUNT_COUPON_AMOUNT' => sprintf('% 9.2f', -$coupon_amount),
            );
        }

        if (\Cx\Modules\Shop\Controller\Vat::isEnabled()) {
//DBG::log("Orders::getSubstitutionArray(): VAT amount: ".$objOrder->vat_amount());
            $arrSubstitution['VAT'] = array(0 => array(
                'VAT_TEXT' => sprintf('%-40s',
                    (\Cx\Modules\Shop\Controller\Vat::isIncluded()
                        ? $_ARRAYLANG['TXT_SHOP_VAT_PREFIX_INCL']
                        : $_ARRAYLANG['TXT_SHOP_VAT_PREFIX_EXCL']
                    )),
                'VAT_PRICE' => $objOrder->getVatAmount(),
            ));
        }
        return $arrSubstitution;
    }

    /**
     * Update status of an order
     *
     * @param int  $orderId     order for which the status is to be changed
     * @param int  $statusId    new status id
     * @param int  $oldStatusId old status id
     * @param bool $updateStock
     *
     * @return bool if the update of the status was successful
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateStatus($orderId, $statusId, $oldStatusId, $updateStock)
    {
        $order = $this->find($orderId);
        if (empty($order)) {
            return false;
        }
        $order->setStatus($statusId);

        if ($updateStock) {
            $this->updateStock(
                $order,
                $this->isStockIncreasable($oldStatusId, $statusId)
            );
        }
        $this->_em->persist($order);
        $this->_em->flush();
        return true;
    }

    /**
     * Is given status can increase stock
     *
     * @param integer $oldStatus Old order status
     * @param integer $newStatus New order status
     *
     * @return boolean True when given status can increase stock, False otherwise
     */
    public function isStockIncreasable($oldStatus, $newStatus)
    {
        $deletedStatus = array(self::STATUS_DELETED, self::STATUS_CANCELLED);
        return   in_array($newStatus, $deletedStatus)
            && !in_array($oldStatus, $deletedStatus);
    }
}
