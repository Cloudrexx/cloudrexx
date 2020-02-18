<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * JsonController for Order
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */

namespace Cx\Modules\Shop\Controller;

/**
 * JsonController for Order
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class JsonOrderController
    extends \Cx\Core\Core\Model\Entity\Controller
    implements \Cx\Core\Json\JsonAdapter
{
    /**
     * @var array messages from this controller
     */
    protected $messages;

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'Order';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'updateOrderStatus',
            'deleteOrder',
            'setEmptyForOrderItem',
            'generateOrderItemShowView',
            'setEmptyForWeight',
            'setEmptyForPrice',
            'appendCurrency',
            'addCustomerLink',
            'getZipAndCity',
            'getStatus',
            'getCustomInputField',
            'formatDateInOverview',
            'formatDateInDetail',
            'getStatusMenuForOverview',
            'getStatusMenuForDetail',
            'getStatusMenuForFilter',
            'getGenderMenu',
            'getNoteToolTip',
            'getDivWrapper',
            'formatModifiedOnDate',
            'getCurrentDate',
            'getCurrentUser',
            'generateOrderItemView',
            'storeOrderItem',
            'getCustomerLink',
            'getCustomerGroupMenu',
            'getTitleAddress',
            'getTitlePaymentInfo',
            'getTitleBill',
            'getTitleNote',
            'getShowAllPendentOrders',
            'generateLsvs',
            'filterCallback',
            'searchCallback',
            'deleteOrders'
        );
    }

    /**
     * Returns all messages as string
     *
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString()
    {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     *
     * @return \Cx\Core_Modules\Access\Model\Entity\Permission
     */
    public function getDefaultPermissions()
    {
        $permission = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array('http', 'https'),
            array('get', 'post'),
            true,
            array()
        );

        return $permission;
    }

    /**
     * Update the status of an order. Depending on the given parameters, the
     * stock is reset and a mail is sent
     *
     * @param array $arguments to update status, update stock and send mail
     *
     * @return array message if the status could be updated successfully
     * @throws \Doctrine\ORM\ORMException handle orm interaction
     */
    public function updateOrderStatus($arguments)
    {
        global $_ARRAYLANG, $objInit;

        $langData   = $objInit->getComponentSpecificLanguageData(
            'Shop',
            false
        );
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        if (empty($arguments['post']) ||
            empty($arguments['post']['orderId']) ||
            !isset($arguments['post']['statusId']) ||
            !isset($arguments['post']['oldStatusId'])
        ) {
            $this->messages[] = 'Zu wenige Argumente';
            return array('status' => 'error', 'message' => $this->messages);

        }
        $updateStock = false;
        if (!empty($arguments['post']['updateStock'])) {
            $updateStock = filter_var(
                $arguments['post']['updateStock'],
                FILTER_VALIDATE_BOOLEAN
            );
        }
        $sendMail = false;
        if (!empty($arguments['post']['sendMailToCrm'])) {
            $sendMail = filter_var(
                $arguments['post']['sendMailToCrm'],
                FILTER_VALIDATE_BOOLEAN
            );
        }
        $em = $this->cx->getDb()->getEntityManager();
        $orderRepo = $em->getRepository('Cx\Modules\Shop\Model\Entity\Order');
        $orderRepo->updateStatus(
            intval($arguments['post']['orderId']),
            intval($arguments['post']['statusId']),
            intval($arguments['post']['oldStatusId']),
            $updateStock
        );

        if ($sendMail) {
            $email = false;
            switch ($arguments['post']['statusId']) {
                case \Cx\Modules\Shop\Model\Repository\OrderRepository::
                    STATUS_CONFIRMED:
                    $email = \Cx\Modules\Shop\Controller\ShopLibrary
                        ::sendConfirmationMail($arguments['post']['orderId']);
                    break;
                case \Cx\Modules\Shop\Model\Repository\OrderRepository::
                    STATUS_COMPLETED:
                    $email = \Cx\Modules\Shop\Controller\ShopManager::
                        sendProcessedMail($arguments['post']['orderId']);
                    break;
            }
            if ($email) {
                $this->messages[] = sprintf(
                    $_ARRAYLANG['TXT_EMAIL_SEND_SUCCESSFULLY'],
                    $email
                );
            } else {
                $this->messages[] = $_ARRAYLANG['TXT_MESSAGE_SEND_ERROR'];
            }
        }

        $this->messages[] = $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_CHANGED'];

        return array('message' => $this->messages);
    }

    /**
     * Delete order by order id. Update stock if certain param is set
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return array Status message if the order order was successfully deleted
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     * @throws \Cx\Core\Error\Model\Entity\ShinyException if order was not found
     */
    public function deleteOrder($params)
    {
        global $_ARRAYLANG, $objInit;

        $langData   = $objInit->getComponentSpecificLanguageData(
            'Shop',
            false
        );
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        $entityId = 0;
        $updateStock = false;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if ($params['post'] && !empty($params['post']['orderId'])) {
            $entityId = $params['post']['orderId'];

            if (isset($params['post']['updateStock'])) {
                $updateStock = filter_var(
                    $params['post']['updateStock'],
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        if (empty($entityId)) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_SHOP_ORDER_COULD_NOT_BE_FOUND']
            );
        }

        $em = $cx->getDb()->getEntityManager();

        $em->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Order'
        )->deleteById($entityId, $updateStock);

        $this->messages[] = $_ARRAYLANG['TXT_SHOP_DELETED_ORDER'];

        return array('message' => $this->messages);
    }

    /**
     * Delete order by order ids. Update stock if certain param is set
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return array Status message if the order order was successfully deleted
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     * @throws \Cx\Core\Error\Model\Entity\ShinyException if order was not found
     */
    public function deleteOrders($params)
    {
        global $_ARRAYLANG, $objInit;

        $langData   = $objInit->getComponentSpecificLanguageData(
            'Shop',
            false
        );
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        foreach ($params['post']['orderIds'] as $orderId) {
            $param = array(
                'post' => array(
                    'orderId' => $orderId,
                    'updateStock' => $params['post']['updateStock']
                )
            );
            $this->deleteOrder($param);
        }

        $this->messages = array();
        $this->messages[] = $_ARRAYLANG['TXT_SHOP_DELETED_ORDERS'];

        return array('message' => $this->messages);
    }

    /**
     * Return an empty string if data is empty, or return data
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string empty string or data
     */
    public function setEmptyForOrderItem($params)
    {
        if (empty($params['data'])) {
            return ' ';
        }
        return $params['data'];
    }

    /**
     * Return an empty string if data is empty, or return data with an
     * additional "g"
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string empty string or data with "g" addition
     */
    public function setEmptyForWeight($params)
    {
        if (empty($params['data']) && !is_numeric($params['data'])) {
            return '';
        }
        return $params['data'] .' g';
    }

    /**
     * Return an empty string if data is empty, or return data and append the
     * active currency
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string empty string or data with active currency
     */
    public function setEmptyForPrice($params)
    {
        if (empty($params['data']) && !is_numeric($params['data'])) {
            return '';
        }
        $currency = $this->cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Currency'
        )->findOneBy(array('active' => 1));
        return $params['data'] .' ' . $currency->getCode();
    }

    /**
     * Get a table to display the order items
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \BackendTable|null table for order items
     * @throws \Cx\Core\Error\Model\Entity\ShinyException if the parameters are
     *                                                    not valid
     * @throws \Doctrine\ORM\Mapping\MappingException if the field does not
     *                                                exist in the mapping
     */
    public function generateOrderItemShowView($params)
    {
        global $_ARRAYLANG;

        if (!isset($params['entity'])) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_SHOP_ORDER_COULD_NOT_BE_FOUND']
            );
        }

        $order = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Order'
        )->findOneBy(array('id' => $params['entity']['id']));

        if (empty($order)) {
            return null;
        }

        $tableContent = array();
        $netPrice = 0;
        $totalWeight = 0;
        $totalVat = $params['entity']['vatAmount'];

        foreach ($order->getOrderItems() as $item) {
            $price = $item->getPrice();
            if (count($item->getOrderAttributes()) > 0) {
                foreach ($item->getOrderAttributes() as $attribute) {
                    $price += $attribute->getPrice();
                }
            }
            $price = number_format($price, 2, '.', '');
            $sum = number_format(
                $price * $item->getQuantity(),
                2,
                '.', ''
            );

            $totalWeight += $item->getWeight();
            $netPrice += $sum;

            $productCode = '';

            $tableContent[] = array(
                'quantity' => $item->getQuantity(),
                'productId' => $item->getProductId(),
                'productCode' => $productCode,
                'productName' => $item->getProductName(),
                'totalWeight' => '',
                'weight' => $item->getWeight(),
                'price' => $price,
                'vat' => $item->getVatRate(),
                'sum' => $sum,
                'orderAttributes' => $item->getOrderAttributes()
            );
        }

        foreach ($order->getRelCustomerCoupons() as $coupon) {
            $netPrice -= $coupon->getAmount();

            $tableContent[] = array(
                'quantity' => '',
                'productId' => '',
                'productCode' => $coupon->getCode(),
                'productName' => $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_CODE'],
                'totalWeight' => '',
                'weight' => '',
                'price' => '',
                'vat' => '',
                'sum' => '-' . $coupon->getAmount(),
                'orderAttributes' => ''
            );
        }
        $total = (float)$params['entity']['shipmentAmount']
            + (float)$params['entity']['paymentAmount']
            + (float)$netPrice;

        if (!Vat::isIncluded()) {
            $total += $totalVat;
        }

        $netPrice = number_format($netPrice, 2, '.', '');
        $total = number_format($total, 2, '.', '');

        $customAttrs = array(
            'TXT_SHOP_DETAIL_NETPRICE' => $netPrice,
            Vat::isIncluded() ? 'TXT_TAX_PREFIX_INCL'
                : 'TXT_TAX_PREFIX_EXCL' => $totalVat,
            'empty' => '',
            'shipmentAmount' => $params['entity']['shipmentAmount'],
            'paymentAmount' => $params['entity']['paymentAmount'],
            'sum' => $total
        );

        foreach ($customAttrs as $key=>$value) {
            $row = array(
                'quantity' => '',
                'productId' => '',
                'productCode' => '',
                'productName' => '',
                'totalWeight' => '',
                'weight' => '',
                'price' => '',
                'vat' => $_ARRAYLANG[$key],
                'sum' => $value
            );
            if ($key == 'shipmentAmount') {
                $row['totalWeight'] = $_ARRAYLANG['TXT_TOTAL_WEIGHT'];
                $row['weight'] = $totalWeight;
            }
            $tableContent[] = $row;
        }

        $options = array(
            'functions' => array(
                'searching' => true
            ),
            'fields' => array(
                'orderAttributes' => array(
                    'showOverview' => false,
                ),
                'vat' => array(
                    'table' => array(
                        'parse' => function($vatRate, $rowData) {
                            if (!is_numeric($vatRate)) {
                                return '<b>'.$vatRate.'</b>';
                            }
                            $price = $rowData['price'];
                            $quantity = $rowData['quantity'];
                            $sum = $price * $quantity;
                            if (Vat::isIncluded()) {
                                $vatSum = $sum - ($sum / ($vatRate / 100 + 1));
                            } else {
                                $vatSum = $sum * $vatRate;
                            }

                            $vatSum = round($vatSum * 2, 1) / 2;
                            $vatSum = number_format($vatSum, 2, '.', '');

                            $vatRateWrapper =
                                new \Cx\Core\Html\Model\Entity\HtmlElement(
                                    'span'
                                );
                            $vatRateWrapper->addChild(
                                new \Cx\Core\Html\Model\Entity\TextElement(
                                    $vatRate .'%'
                                )
                            );
                            $vatSumWrapper =
                                new \Cx\Core\Html\Model\Entity\HtmlElement(
                                    'span'
                                );
                            $vatSumWrapper->addChild(
                                new \Cx\Core\Html\Model\Entity\TextElement(
                                    $vatSum . ' CHF'
                                )
                            );
                            return $vatRateWrapper . $vatSumWrapper;
                        },
                        'attributes' => array(
                            'class' => 'vat-detail align-right'
                        )
                    ),
                ),
                'productName' => array(
                    'table' => array(
                        'parse' => function($value, $rowData) {
                            $productName = $value;
                            if (isset($rowData['orderAttributes'])) {
                                foreach (
                                    $rowData['orderAttributes'] as $attribute
                                ) {
                                    $productName .= '<br/><i> - '
                                        . $attribute->getAttributeName() .': '
                                        . $attribute->getOptionName() . ' ('
                                        . $attribute->getPrice() . ')</i>';
                                }
                            }

                            return $productName;
                        },
                        'attributes' => array(
                            'class' => 'product-name-detail'
                        ),
                    )
                ),
                'productId' => array(
                    'table' => array(
                        'parse' => array(
                            'adapter' => 'Order',
                            'method' => 'setEmptyForOrderItem'
                        ),
                        'attributes' => array(
                            'class' => 'product-id-detail'
                        ),
                    )
                ),
                'productCode' => array(
                    'table' => array(
                        'parse' => array(
                            'adapter' => 'Order',
                            'method' => 'setEmptyForOrderItem'
                        ),
                        'attributes' => array(
                            'class' => 'product-code-detail'
                        ),
                    )
                ),
                'quantity' => array(
                    'table' => array(
                        'parse' => array(
                            'adapter' => 'Order',
                            'method' => 'setEmptyForOrderItem'
                        ),
                        'attributes' => array(
                            'class' => 'quantity-detail'
                        ),
                    )
                ),
                'totalWeight' => array(
                    'table' => array(
                        'parse' => array(
                            'adapter' => 'Order',
                            'method' => 'setEmptyForOrderItem'
                        ),
                        'attributes' => array(
                            'class' => 'strong-text align-right'
                        ),
                    )
                ),
                'weight' => array(
                    'table' => array(
                        'parse' => array(
                            'adapter' => 'Order',
                            'method' => 'setEmptyForWeight'
                        ),
                        'attributes' => array(
                            'class' => 'align-right weight-detail'
                        ),
                    )
                ),
                'price' => array(
                    'table' => array(
                        'parse' => array(
                            'adapter' => 'Order',
                            'method' => 'setEmptyForPrice'
                        ),
                        'attributes' => array(
                            'class' => 'align-right price-detail'
                        ),
                    )
                ),
                'sum' => array(
                    'table' => array(
                        'parse' => array(
                            'adapter' => 'Order',
                            'method' => 'setEmptyForPrice'
                        ),
                        'attributes' => array(
                            'class' => 'align-right sum-detail'
                        ),
                    ),
                    'header' => $_ARRAYLANG['TXT_SHOP_ORDER_SUM']
                ),
            )
        );

        $table = new \Cx\Core_Modules\Listing\Model\Entity\DataSet(
            $tableContent
        );

        return new \BackendTable($table, $options, '', null);
    }

    /**
     * Append a string to the given callback value
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string value with currency code
     */
    public function appendCurrency($params)
    {
        $currency = $params['entity']['currency'];
        return $params['value'] . ' ' . $currency->getCode();
    }

    /**
     * Add a link to the customer page
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement link element
     */
    public function addCustomerLink($params)
    {
        if (empty($params['value'])) {
            return new \Cx\Core\Html\Model\Entity\TextElement('');
        }
        if (empty($params['entity']['customerId'])) {
            return $params['value'];
        }

        $link = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
        $link->addChild(
            new \Cx\Core\Html\Model\Entity\TextElement(
                $params['value']
            )
        );

        $customerId = $params['entity']['customerId'];
        $linkUrl = \Cx\Core\Routing\Url::fromBackend('Shop', 'customerdetails');
        $linkUrl->setParam('customer_id', $customerId);

        $link->setAttribute('href', $linkUrl);

        return $link;
    }

    /**
     * Append the city to the zip
     *
     * @param array $params contains the zip value
     *
     * @return string zip with city
     */
    public function getZipAndCity($params)
    {
        return $params['value'] . ' ' . $params['entity']['city'];
    }

    /**
     * Get the status name
     *
     * @param array $params contains the status index
     *
     * @return string get the status text
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getStatus($params)
    {
        $statusValues = $this->cx->getDb()
            ->getEntityManager()->getRepository(
                $this->getNamespace()
                . '\\Model\Entity\Order'
            )->getStatusValues();

        return $statusValues[$params['value']];
    }

    /**
     * Get custom input fields to align them on the right side.
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getCustomInputField($params)
    {
        global $_ARRAYLANG;

        $fieldname = !empty($params['name']) ? $params['name'] : '';
        $fieldvalue = !empty($params['value']) ? $params['value'] : '';

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $wrapper->addClass('custom-input');

        if ($fieldname == 'vatAmount') {
            $header = Vat::isIncluded()
                ? $_ARRAYLANG['TXT_SHOP_VAT_PREFIX_INCL']
                : $_ARRAYLANG['TXT_SHOP_VAT_PREFIX_EXCL'];
        } else {
            $header = $_ARRAYLANG[$fieldname];
        }

        $title = new \Cx\Core\Html\Model\Entity\TextElement(
            $header
        );
        $input = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldname,
            $fieldvalue,
            'input'
        );

        $repo = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Currency'
        );
        $defaultCurrency = $repo->getDefaultCurrency();

        $addition = new \Cx\Core\Html\Model\Entity\TextElement(
            $defaultCurrency->getCode()
        );
        $spanWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $spanWrapper->addChild($addition);

        $wrapper->addChild($title);
        $wrapper->addChild($input);
        $wrapper->addChild($spanWrapper);

        return $wrapper;
    }

    /**
     * Format the date into an other date format to display it in the overview
     *
     * @param array $params callback params contain the value to be formatted
     *
     * @return string formatted date
     */
    public function formatDateInOverview($params)
    {
        $date = new \DateTime($params['data']);
        return $date->format('d.m.Y H:i:s');
    }

    /**
     * Format the date into an other date format to display it in the detail
     * view
     *
     * @param array $params callback params contain the value to be formatted
     *
     * @return string formatted date
     */
    public function formatDateInDetail($params)
    {
        $date = new \DateTime($params['value']);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Get the status menu for the overview
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\DataElement status menu
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getStatusMenuForOverview($params)
    {
        $rowData = !empty($params['rows']) ? $params['rows'] : array();
        return $this->getStatusMenu($params['data'], '', $rowData['id']);
    }

    /**
     * Get status menu for detail view. It has a custom field to send a mail
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement status menu
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getStatusMenuForDetail($params)
    {
        global $_ARRAYLANG;

        $fieldname = !empty($params['name']) ? $params['name'] : '';
        $fieldvalue = !empty($params['value']) ? $params['value'] : '';

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $statusMenu = $this->getStatusMenu($fieldvalue, $fieldname);

        $wrapperEmail = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $textEmail = new \Cx\Core\Html\Model\Entity\TextElement(
            $_ARRAYLANG['TXT_SEND_MAIL']
        );
        $labelEmail = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $inputEmail = new \Cx\Core\Html\Model\Entity\DataElement(
            'sendMail',
            '1',
            'input'
        );

        $wrapperEmail->setAttributes(
            array(
                'id' => 'sendMailDiv',
                'style' => 'display: inline',
            )
        );
        $labelEmail->setAttribute('for', 'sendMail');
        $inputEmail->setAttributes(
            array(
                'type' => 'checkbox',
                'id' => 'sendMail',
                'onclick' => 'swapSendToStatus();',
            )
        );

        if (
            $fieldvalue !=
            \Cx\Modules\Shop\Model\Repository\OrderRepository::STATUS_COMPLETED
        ) {
            $wrapperEmail->setAttribute('style', 'display:none');
        }

        $labelEmail->addChild($textEmail);
        $wrapperEmail->addChild($inputEmail);
        $wrapperEmail->addChild($labelEmail);
        $wrapper->addChild($statusMenu);
        $wrapper->addChild($wrapperEmail);

        return $wrapper;
    }

    /**
     * Get status menu for filter
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement status menu
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getStatusMenuForFilter($params)
    {
        return $this->getStatusMenu(
            '',
            $params['fieldName'],
            0,
            $params['formName']
        );
    }

    /**
     * Get a dropdown with all status values.
     *
     * @param string $value    value of field
     * @param string $name     name of field
     * @param string $formName name of form
     *
     * @return \Cx\Core\Html\Model\Entity\DataElement status menu
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    protected function getStatusMenu($value, $name = '', $id = 0, $formName = '')
    {
        global $_ARRAYLANG;

        $validValues = array();
        $statusValues = $this->cx->getDb()
            ->getEntityManager()->getRepository(
                $this->getNamespace()
                . '\\Model\Entity\Order'
            )->getStatusValues();
        if (!empty($formName)) {
            $validValues = array(
                '' => $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_PLEASE_CHOOSE'],
            );
        }
        $validValues = array_merge($validValues, $statusValues);

        if (empty($name)) {
            $name = 'status';
        }

        if (!empty($id)) {
            $name = $name . '-' . $id;
        }
        $statusField = new \Cx\Core\Html\Model\Entity\DataElement(
            $name,
            $value,
            'select',
            null,
            $validValues
        );

        if (!empty($formName)) {
            $statusField->setAttributes(
                array(
                    'form' => $formName,
                    'data-vg-attrgroup' => 'search',
                    'data-vg-field' => 'status',
                    'class' => 'vg-encode'
                )
            );
        }

        return $statusField;
    }

    /**
     * Get menu to select the gender
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\DataElement gender menu
     */
    public function getGenderMenu($params)
    {
        global $_ARRAYLANG;

        $fieldname = !empty($params['name']) ? $params['name'] : '';
        $fieldvalue = !empty($params['value']) ? $params['value'] : '';

        $validData = array(
            'gender_undefined' => $_ARRAYLANG[
            'TXT_SHOP_GENDER_UNDEFINED'
            ],
            'gender_male' => $_ARRAYLANG[
            'TXT_SHOP_GENDER_MALE'
            ],
            'gender_female' => $_ARRAYLANG[
            'TXT_SHOP_GENDER_FEMALE'
            ]
        );

        $genderDropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldname,
            $fieldvalue,
            'select',
            null,
            $validData
        );

        return $genderDropdown;
    }

    /**
     * Return a tooltip containing the note of the order.
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement note tool tip
     */
    public function getNoteToolTip($params)
    {
        $value = !empty($params['data']) ? $params['data'] : '';

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $wrapper->addClass('tooltip-wrapper');

        if (empty($value) || $value === ' ') {
            return $wrapper;
        }

        $tooltipTrigger = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $tooltipTrigger->setAttribute(
            'class',
            'icon-info tooltip-trigger icon-comment'
        );
        $tooltipTrigger->allowDirectClose(false);

        $tooltipMessage = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $tooltipMessage->setAttribute('class', 'tooltip-message');
        $tooltipMessage->addChild(
            new \Cx\Core\Html\Model\Entity\TextElement($value)
        );

        $wrapper->addChild($tooltipTrigger);
        $wrapper->addChild($tooltipMessage);

        return $wrapper;
    }

    /**
     * Wrap a div around the given callback value
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement simple div with content
     */
    public function getDivWrapper($params)
    {
        $value = !empty($params['value']) ? $params['value'] : '';

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $value = new \Cx\Core\Html\Model\Entity\TextElement($value);
        $wrapper->addChild($value);

        return $wrapper;
    }

    /**
     * If an order wasn't edited yet, return this info. Otherwise, return the
     * last editing date.
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\TextElement element with information
     */
    public function formatModifiedOnDate($params)
    {
        global $_ARRAYLANG;

        $fieldvalue = !empty($params['value']) ? $params['value'] : '';

        if (empty($fieldvalue)) {
            $field = new \Cx\Core\Html\Model\Entity\TextElement(
                $_ARRAYLANG['TXT_ORDER_WASNT_YET_EDITED']
            );
            return $field;
        }

        $date = new \DateTime($fieldvalue);
        return $field = new \Cx\Core\Html\Model\Entity\TextElement(
            $date->format('Y-m-d H:i:s')
        );
    }

    /**
     * Get the current date
     *
     * @return string current date
     */
    public function getCurrentDate()
    {
        $date = new \DateTime('now');

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Get the email of the active user
     *
     * @return string email of the active user
     */
    public function getCurrentUser()
    {
        return $objFWUser = \FWUser::getFWUserObject()->objUser->getEmail();
    }

    /**
     * Get the order item table
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement order item view
     * @throws \Cx\Core\Setting\Controller\SettingException handle setting fails
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function generateOrderItemView($params)
    {
        global $_ARRAYLANG;

        if (empty($params['id'])) {
            return;
        }
        $orderId = $params['id'];

        $tableConfig['entity'] = '\Cx\Modules\Shop\Model\Entity\OrderItem';
        $tableConfig['criteria'] = array('orderId' => $orderId);

        $orderItems = $this->cx->getDb()->getEntityManager()->getRepository(
            $tableConfig['entity']
        )->findBy($tableConfig['criteria']);

        $order = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Order'
        )->findOneBy(array('id' => $orderId));

        $currency = $order->getCurrency()->getCode();

        $tableConfig['header'] = array(
            'quantity' => array(
                'type' => 'input',
            ),
            'product_name' => array(
                'type' => 'text',
            ),
            'weight' => array(
                'type' => 'input',
            ),
            'price' => array(
                'type' => 'input',
                'addition' => $currency,
            ),
            'vat_rate' => array(
                'type' => 'input',
                'addition' => '%',
            ),
            'sum' => array(
                'type' => 'input',
                'addition' => $currency,
                'header' => $_ARRAYLANG['TXT_SHOP_ORDER_SUM']
            ),
        );

        $table = new \Cx\Core\Html\Model\Entity\HtmlElement('table');
        $tableBody = new \Cx\Core\Html\Model\Entity\HtmlElement('tbody');;
        $headerTr = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');

        $table->addChild($tableBody);
        $tableBody->addChild($headerTr);

        foreach ($tableConfig['header'] as $key => $header) {
            $th = new \Cx\Core\Html\Model\Entity\HtmlElement('th');
            $title = $_ARRAYLANG[$key];
            if (isset($header['header'])) {
                $title = $header['header'];
            }
            $title = new \Cx\Core\Html\Model\Entity\TextElement($title);
            $th->addChild($title);
            $th->setAttributes(
                array(
                    'id' => $key,
                    'name' => $key,
                )
            );
            $headerTr->addChild($th);
        }
        $cols = $this->cx->getDb()->getEntityManager()->getClassMetadata(
            $tableConfig['entity']
        )->getColumnNames();

        foreach ($orderItems as $orderItem) {
            $tr = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');
            $id = $orderItem->getId();

            foreach ($tableConfig['header'] as $key => $header) {
                $td = new \Cx\Core\Html\Model\Entity\HtmlElement('td');

                // Replace _ and set new word to uppercase, to get the getter
                // name
                $methodName = str_replace(
                    " ",
                    "",
                    mb_convert_case(
                        str_replace(
                            "_",
                            " ",
                            $key
                        ),
                        MB_CASE_TITLE
                    )
                );

                $getter = 'get' . ucfirst($methodName);
                $value = '';
                if (in_array($key, $cols)) {
                    $value = $orderItem->$getter();
                }

                if ($header['type'] == 'input') {
                    $field = new \Cx\Core\Html\Model\Entity\DataElement(
                        'product_' . $key .'-'. $id,
                        $value,
                        'input'
                    );
                    $field->setAttributes(
                        array(
                            'onchange' => 'calcPrice(' . $id . ')',
                            'id' => 'product_' . $key .'-'. $id
                        )
                    );
                } else {
                    $field = new \Cx\Core\Html\Model\Entity\HtmlElement(
                        'label'
                    );
                    $text = new \Cx\Core\Html\Model\Entity\TextElement(
                        $value
                    );
                    $field->setAttributes(
                        array(
                            'name' => 'product_' . $key .'-'. $id,
                            'id' => 'product_' . $key .'-'. $id,
                            'class' => 'product',
                        )
                    );
                    $field->addChild($text);
                    $hiddenField = new \Cx\Core\Html\Model\Entity\DataElement(
                        'product_product_id-'. $id,
                        $orderItem->getProductId(),
                        'input'
                    );
                    $hiddenField->setAttributes(
                        array(
                            'id' => 'product_product_id-'. $id,
                            'class' => 'product_ids',
                            'type' => 'hidden'
                        )
                    );
                    $td->addChild($hiddenField);
                }

                $td->addChild($field);

                $spanWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement(
                    'span'
                );
                if (!empty($header['addition'])) {
                    $addition = new \Cx\Core\Html\Model\Entity\TextElement(
                        $header['addition']
                    );

                    $spanWrapper->addChild($addition);
                }

                if ($key == 'sum') {
                    $field->setAttribute('readonly', 'readonly');
                    $toolTipTrigger = new \Cx\Core\Html\Model\Entity\HtmlElement(
                        'span'
                    );
                    $toolTipTrigger->addClass(
                        'icon-info tooltip-trigger tooltip-order-item'
                    );
                    $toolTipTrigger->allowDirectClose(false);
                    $toolTipMessage = new \Cx\Core\Html\Model\Entity\HtmlElement(
                        'span'
                    );
                    $toolTipMessage->addClass('tooltip-message');
                    $messageText = $_ARRAYLANG[
                        'TXT_SHOP_ORDER_ITEMS_ARE_ADDED_TO_SUM'
                    ];
                    $message = new \Cx\Core\Html\Model\Entity\TextElement(
                        $messageText
                    );
                    $toolTipMessage->addChild($message);
                    $spanWrapper->addChild($toolTipTrigger);
                    $spanWrapper->addChild($toolTipMessage);
                    if (count($orderItem->getOrderAttributes()) > 0) {
                        $toolTipTrigger->addClass('show');
                    }
                } else if ($key == 'price') {
                    $attributePrice = 0;
                    if (count($orderItem->getOrderAttributes()) > 0) {
                        foreach (
                            $orderItem->getOrderAttributes() as $attribute
                        ) {
                            $attributePrice += $attribute->getPrice();
                        }
                    }
                    $field->setAttribute(
                        'data-priceattributes', $attributePrice
                    );
                } else if ($key == 'product_name') {
                    if (count($orderItem->getOrderAttributes()) > 0) {
                        $toolTipTrigger =
                            new \Cx\Core\Html\Model\Entity\HtmlElement('span');
                        $toolTipTrigger->addClass('icon-info tooltip-trigger');
                        $toolTipTrigger->allowDirectClose(false);
                        $toolTipMessage =
                            new \Cx\Core\Html\Model\Entity\HtmlElement('span');
                        $toolTipMessage->addClass('tooltip-message');
                        $messageText = $_ARRAYLANG[
                        'TXT_SHOP_ORDER_ITEM_WITH_OPTIONS'
                        ];
                        foreach (
                            $orderItem->getOrderAttributes() as $attribute
                        ) {
                            $attributeText = '- '.$attribute->getAttributeName()
                                . ': '. $attribute->getOptionName()
                                . ' (' . $attribute->getPrice() . ' '
                                . $currency .')<br/>';
                            $messageText .= $attributeText;
                        }
                        $message = new \Cx\Core\Html\Model\Entity\TextElement(
                            $messageText
                        );
                        $toolTipMessage->addChild($message);
                        $spanWrapper->addChild($toolTipTrigger);
                        $spanWrapper->addChild($toolTipMessage);
                    }
                }
                $td->addChild($spanWrapper);
                $tr->addChild($td);
            }
            $tableBody->addChild($tr);
        }

        // add new empty order item
        $trEmpty = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');

        foreach ($tableConfig['header'] as $key => $header) {
            $td = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $value = '0';

            if ($key == 'product_name') {
                $validValues[0] = '-';
                $products = $this->cx->getDb()->getEntityManager()
                    ->getRepository(
                        '\Cx\Modules\Shop\Model\Entity\Product'
                    )->findAll();

                foreach ($products as $product) {
                    $validValues[$product->getId()] = $product->getName();
                }

                $field = new \Cx\Core\Html\Model\Entity\DataElement(
                    'product_' . $key .'-0',
                    0,
                    'select',
                    null,
                    $validValues
                );
                $field->setAttributes(
                    array(
                        'onchange' =>'changeProduct(0,this.value);',
                        'id' => 'product_' . $key .'-0',
                        'class' => 'product',
                    )
                );
                $hiddenField = new \Cx\Core\Html\Model\Entity\DataElement(
                    'product_product_id-0', '0', 'input'
                );
                $hiddenField->setAttributes(
                    array(
                        'id' => 'product_product_id-0',
                        'class' => 'product_ids',
                        'type' => 'hidden'
                    )
                );

                $td->addChild($hiddenField);
            } else if ($header['type'] == 'input') {
                $field = new \Cx\Core\Html\Model\Entity\DataElement(
                    'product_' . $key .'-0',
                    $value,
                    'input'
                );
                $field->setAttributes(
                    array(
                        'onchange' => 'calcPrice(0)',
                        'id' => 'product_' . $key .'-0',
                    )
                );
            } else {
                $field = new \Cx\Core\Html\Model\Entity\TextElement(
                    $value
                );
                $field->setAttribute('name', 'product' . $key .'-0');
            }

            if ($key == 'sum') {
                $field->setAttribute('readonly', 'readonly');
            }

            $td->addChild($field);
            $trEmpty->addChild($td);

            if (empty($header['addition'])) {
                continue;
            }
            $addition = new \Cx\Core\Html\Model\Entity\TextElement(
                $header['addition']
            );
            $spanWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement(
                'span'
            );
            $spanWrapper->addChild($addition);
            $td->addChild($spanWrapper);
        }

        $tableBody->addChild($trEmpty);

        // add coupon
        $couponRel = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\RelCustomerCoupon'
        )->findOneBy(array('orderId' => $orderId));

        if (!empty($couponRel)) {
            $trCoupon = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');
            $tdEmpty = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $trCoupon->addChild($tdEmpty);

            $tdName = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $text = new \Cx\Core\Html\Model\Entity\TextElement(
                $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_CODE'] . ' ' .
                $couponRel->getCode()
            );
            $tdName->addChild($text);
            $trCoupon->addChild($tdName);

            $tdEmpty = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $tdEmpty->setAttribute('colspan', 3);
            $trCoupon->addChild($tdEmpty);

            $tdAmount = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $input = new \Cx\Core\Html\Model\Entity\DataElement(
                'amount',
                '-' . $couponRel->getAmount(),
                'input'
            );
            $discountCoupon = $this->cx->getDb()->getEntityManager()
                ->getRepository('\Cx\Modules\Shop\Model\Entity\DiscountCoupon')
                ->findOneBy(array('code' => $couponRel->getCode()));

            $attributes =  array(
                'id' => 'coupon-amount',
                'readonly' => 'readonly'
            );

            if (!empty($discountCoupon)) {
                $attributes['data-rate'] = $discountCoupon->getDiscountRate();
                $attributes[
                    'data-amount'
                ] = $discountCoupon->getDiscountAmount();
            } else {
                $attributes['data-amount'] = $couponRel->getAmount();
            }


            $addition = new \Cx\Core\Html\Model\Entity\TextElement(
                $currency
            );
            $spanWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
            $spanWrapper->addChild($addition);

            $input->setAttributes(
                $attributes
            );

            $tdAmount->addChild($input);
            $tdAmount->addChild($spanWrapper);
            $trCoupon->addChild($tdAmount);

            $tableBody->addChild($trCoupon);
        }

        // add weight and netprice
        $trCustom = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');
        $tdEmpty = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
        $trCustom->addChild($tdEmpty);

        $tdWeightTitle = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
        $weightTitle = new \Cx\Core\Html\Model\Entity\TextElement(
            $_ARRAYLANG['TXT_TOTAL_WEIGHT']
        );
        $tdWeightTitle->addClass('shop-order-info');
        $tdWeightTitle->addChild($weightTitle);

        $tdWeightInput = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
        $weightInput = new \Cx\Core\Html\Model\Entity\DataElement(
            'total-weight',
            '',
            'input'
        );

        $weightInput->setAttributes(
            array(
                'id' => 'total-weight',
                'readonly' => 'readonly',
            )
        );

        $tdWeightInput->addChild($weightInput);

        $trCustom->addChild($tdWeightTitle);
        $trCustom->addChild($tdWeightInput);

        $trCustom->addChild($tdEmpty);

        $tdNetpriceTitle = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
        $netpriceTitle = new \Cx\Core\Html\Model\Entity\TextElement(
            $_ARRAYLANG['TXT_SHOP_DETAIL_NETPRICE']
        );
        $tdNetpriceTitle->addClass('shop-order-info');
        $tdNetpriceTitle->addChild($netpriceTitle);

        $tdNetpriceInput = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
        $netpriceInput = new \Cx\Core\Html\Model\Entity\DataElement(
            'netprice',
            '',
            'input'
        );

        $netpriceInput->setAttributes(
            array(
                'id' => 'netprice',
                'readonly' => 'readonly'
            )
        );

        $tdNetpriceInput->addChild($netpriceInput);
        $tdNetpriceInput->addChild($spanWrapper);

        $trCustom->addChild($tdNetpriceTitle);
        $trCustom->addChild($tdNetpriceInput);
        $tableBody->addChild($trCustom);

        $customerId = $order->getCustomerId();
        $this->defineJsVariables($customerId);

        // Load custom Js File for order edit view
        \JS::registerJS('modules/Shop/View/Script/EditOrder.js');

        return $table;
    }

    /**
     * Defines variables that are used in the javascript file EditOrder.js.
     *
     * @global array $_ARRAYLANG array containing the language variables
     *
     * @param  int   $customerId Id of customer
     *
     * @throws \Cx\Core\Setting\Controller\SettingException handle setting fails
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    protected function defineJsVariables($customerId)
    {
        global $_ARRAYLANG;

        $shipper = new \Cx\Modules\Shop\Model\Entity\Shipper();
        $products = new \Cx\Modules\Shop\Model\Entity\Product();
        $customer = \Cx\Modules\Shop\Controller\Customer::getById($customerId);

        $isReseller = false;
        $groupId = 0;
        if ($customer) {
            $isReseller = $customer->isReseller();
            $groupId = $customer->getGroupId();
        }
        $productsJsArr = $products->getJsArray($groupId, $isReseller);

        $shipmentCostJsArr = $shipper->getJsArray();

        $jsVariables = array(
            array(
                'name' => 'SHIPPER_INFORMATION',
                'content' => $shipmentCostJsArr,
            ),
            array(
                'name' => 'VAT_INCLUDED',
                'content' => \Cx\Modules\Shop\Model\Entity\Vat::isIncluded(),
            ),
            array(
                'name' => 'PRODUCT_LIST',
                'content' => $productsJsArr,
            ),
            array(
                'name' => 'TXT_WARNING_SHIPPER_WEIGHT',
                'content' => $_ARRAYLANG['TXT_WARNING_SHIPPER_WEIGHT'],
            ),
            array(
                'name' => 'TXT_PRODUCT_ALREADY_PRESENT',
                'content' => $_ARRAYLANG['TXT_PRODUCT_ALREADY_PRESENT'],
            ),
        );

        $scope = 'order';
        foreach ($jsVariables as $jsVariable) {
            \ContrexxJavascript::getInstance()->setVariable(
                $jsVariable['name'],
                $jsVariable['content'],
                $scope
            );
        }
    }

    /**
     * Store the order item
     *
     * @param array $params contains the parameters of the callback function
     *
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function storeOrderItem($params)
    {
        $this->cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\OrderItem'
        )->save($params['entity'], $params['postedValue']);
    }

    /**
     * Add a link to the customer page
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement|string link element or
     *                                                       value
     */
    public function getCustomerLink($params)
    {
        $rowData = !empty($params['rows']) ? $params['rows'] : array();
        $value = !empty($params['data']) ? $params['data'] : '';

        if (empty($value)) {
            if (isset($rowData['firstname']) && isset($rowData['lastname'])) {
                $name = $rowData['firstname'] .' '. $rowData['lastname'];
            } else {
                return $value;
            }
        } else {
            $objUser = \FWUser::getFWUserObject()->objUser->getUser(
                $value->getId()
            );

            $name = $objUser->getProfileAttribute(
                'lastname'
            ) . ' ' .$objUser->getProfileAttribute(
                'firstname'
            );
        }

        $link = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
        $nameElement = new \Cx\Core\Html\Model\Entity\TextElement($name);
        $link->addChild($nameElement);
        $showUrl = \Cx\Core\Html\Controller\ViewGenerator::getVgShowUrl(
            0, $rowData['id']
        );
        $link->setAttribute('href', $showUrl);

        return $link;
    }

    /**
     * Get a search menu to filter by customer groups
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\DataElement search menu
     * @throws \Cx\Core\Setting\Controller\SettingException if setting does
     *                                                      not exist
     */
    public function getCustomerGroupMenu($params)
    {
        global $_ARRAYLANG;

        $elementName = !empty($params['fieldName']) ? $params['fieldName'] : '';
        $formName = !empty($params['formName']) ? $params['formName'] : '';

        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        $resellerGroup = \Cx\Core\Setting\Controller\Setting::getValue(
            'usergroup_id_reseller',
            'Shop'
        );

        $customerGroup = \Cx\Core\Setting\Controller\Setting::getValue(
            'usergroup_id_customer',
            'Shop'
        );

        $validValues = array(
            '' => $_ARRAYLANG['TXT_SHOP_ORDER_CUSTOMER_GROUP_PLEASE_CHOOSE'],
            $resellerGroup => $_ARRAYLANG['TXT_CUSTOMER'],
            $customerGroup => $_ARRAYLANG['TXT_RESELLER'],
        );
        $searchField = new \Cx\Core\Html\Model\Entity\DataElement(
            $elementName,
            '',
            'select',
            null,
            $validValues
        );

        $searchField->setAttributes(
            array(
                'form' => $formName,
                'data-vg-attrgroup' => 'search',
                'data-vg-field' => 'customer',
                'class' => 'vg-encode'
            )
        );
        return $searchField;
    }

    /**
     * Get a new title row to begin the address area
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement title row
     */
    public function getTitleAddress()
    {
        global $_ARRAYLANG;

        return $this->getTitleRow(
            array(
                $_ARRAYLANG['TXT_BILLING_ADDRESS'],
                $_ARRAYLANG['TXT_SHIPPING_ADDRESS']
            )
        );
    }

    /**
     * Get a new title row to begin the payment info area
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement title row
     */
    public function getTitlePaymentInfo()
    {
        global $_ARRAYLANG;

        return $this->getTitleRow(
            array($_ARRAYLANG['TXT_PAYMENT_INFORMATIONS'])
        );
    }

    /**
     * Get a new title row to begin the bill area
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement title row
     */
    public function getTitleBill()
    {
        global $_ARRAYLANG;

        return $this->getTitleRow(
            array($_ARRAYLANG['TXT_BILL'])
        );
    }

    /**
     * Get a new title row to begin the note area
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement title row
     */
    public function getTitleNote()
    {
        global $_ARRAYLANG;

        return $this->getTitleRow(
            array($_ARRAYLANG['TXT_CUSTOMER_REMARKS'])
        );
    }

    /**
     * Get a title row that can contain as many titles as you like
     *
     * @param array $titles row titles
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement title row
     */
    protected function getTitleRow($titles)
    {
        $table = new \Cx\Core\Html\Model\Entity\HtmlElement('table');
        $tr = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');

        foreach ($titles as $title) {
            $th = new \Cx\Core\Html\Model\Entity\HtmlElement('th');
            $title = new \Cx\Core\Html\Model\Entity\TextElement($title);
            $th->addChild($title);
            $tr->addChild($th);
        }
        $table->addChild($tr);
        $table->addClass('adminlist title-table');

        return $table;
    }

    /**
     * Get a checkbox for the search to view all pending order
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\DataElement checkbox to show pendent
     *                                                orders
     * @throws \Exception if param not exist
     */
    public function getShowAllPendentOrders($params)
    {
        $elementName = !empty($params['elementName'])
            ? $params['elementName'] : '';
        $fieldName = !empty($params['fieldName']) ? $params['fieldName'] : '';
        $formName = !empty($params['formName']) ? $params['formName'] : '';

        $checkbox = new \Cx\Core\Html\Model\Entity\DataElement(
            $elementName,
            1
        );
        $checkbox->setAttributes(
            array(
                'data-vg-attrgroup' => 'search',
                'data-vg-field' => $fieldName,
                'data-vg-no-fill' => true,
                'form' => $formName,
                'type' => 'checkbox',
                'class' => 'vg-encode',
                'id' => $elementName,
            )
        );

        if (
            $this->cx->getRequest()->hasParam('search') &&
            strpos(
                $this->cx->getRequest()->getParam('search'),
                '{0,showAllPendentOrders=1}'
            ) === 0
        ) {
            $checkbox->setAttribute('checked', true);
            $checkbox->setAttribute('value', 0);
        }

        return $checkbox;
    }

    /**
     * Return custom lsv edit field
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    public function generateLsvs($params)
    {
        global $_ARRAYLANG;

        $orderId = !empty($params['id']) ? $params['id'] : 0;

        $entity = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Lsv'
        )->findOneBy(array('orderId' => $orderId));

        if (empty($entity)) {
            $empty = new \Cx\Core\Html\Model\Entity\TextElement('');
            return $empty;
        }

        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()
            ->getEntityManager();
        $meta = $em->getClassMetadata('\Cx\Modules\Shop\Model\Entity\Lsv');
        $attributes = $meta->getFieldNames();
        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        $doNotShow = array('orderId');

        foreach ($attributes as $attribute) {

            if (in_array($attribute, $doNotShow)) {
                continue;
            }

            $divGroup = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
            $label = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
            $title = new \Cx\Core\Html\Model\Entity\TextElement(
                $_ARRAYLANG[$attribute]
            );
            $divControls = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
            $input = new \Cx\Core\Html\Model\Entity\HtmlElement('input');

            $getter = 'get' . ucfirst($attribute);

            $divGroup->addClass('group');
            $label->setAttribute('for', 'form-0-' . $attribute);
            $divControls->addClass('controls');
            $input->setAttributes(
                array(
                    'name' => $attribute,
                    'value' => $entity->$getter(),
                    'type' => 'text',
                    'id' => 'form-0-'.$attribute,
                    'onkeyup' => 'return true;',
                    'class' => 'form-control'
                )
            );

            $label->addChild($title);
            $divGroup->addChild($label);
            $divGroup->addChild($divControls);
            $divControls->addChild($input);
            $wrapper->addChild($divGroup);
        }

        return $wrapper;
    }

    /**
     * Callback function for expanded search
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Doctrine\ORM\QueryBuilder $qb
     */
    public function filterCallback($params)
    {
        if (empty($params['qb'])) {
            return null;
        }

        $qb = $params['qb'];
        $crit = !empty($params['crit']) ? $params['crit'] : array();

        $i = 1;
        foreach ($crit as $field=>$value) {
            if ($field == 'customer') {
                $qb->join(
                    '\Cx\Core\User\Model\Entity\User',
                    'u', 'WITH', 'u.id = x.customerId'
                );
                $qb->andWhere('?'. $i .' MEMBER OF u.group');
            } else if ($field == 'showAllPendentOrders') {
                continue;
            } else if ($field == 'id') {
                $qb->andWhere(
                    $qb->expr()->eq('x.' . $field, '?' . $i)
                );
            } else {
                $qb->andWhere(
                    $qb->expr()->like('x.' . $field, '?' . $i)
                );
            }

            $qb->setParameter($i, $value);
            $i++;
        }

        if (
            empty($crit['showAllPendentOrders']) &&
            empty($this->cx->getRequest()->hasParam('showid'))
        ) {
            $qb->andWhere($qb->expr()->notLike('x.' . 'status', ':status'));
            $qb->setParameter('status', 0);
        }

        return $qb;
    }

    /**
     * Callback function for search
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Doctrine\ORM\QueryBuilder $qb
     */
    public function searchCallback($params)
    {
        if (empty($params['qb'])) {
            return null;
        }
        $qb = $params['qb'];
        $fields = !empty($params['fields']) ? $params['fields'] : array();
        $term = !empty($params['crit']) ? $params['crit'] : '';

        $orX = new \Doctrine\DBAL\Query\Expression\CompositeExpression(
            \Doctrine\DBAL\Query\Expression\CompositeExpression::TYPE_OR
        );
        foreach ($fields as $field) {
            if ($field == 'customer') {
                $andXLastname =
                    new \Doctrine\DBAL\Query\Expression\CompositeExpression(
                        \Doctrine\DBAL\Query\Expression\CompositeExpression::
                        TYPE_AND
                    );
                $andXFirstname =
                    new \Doctrine\DBAL\Query\Expression\CompositeExpression(
                        \Doctrine\DBAL\Query\Expression\CompositeExpression::
                        TYPE_AND
                    );
                $qb->join(
                    'Cx\Core\User\Model\Entity\UserAttributeValue',
                    'v', 'WITH', 'x.customerId = v.userId'
                );
                $qb->join(
                    '\Cx\Core\User\Model\Entity\UserAttributeName',
                    'a', 'WITH', 'v.attributeId = a.attributeId'
                );
                $andXLastname->add($qb->expr()->like('v.value', ':search'));
                $andXLastname->add($qb->expr()->like('a.name', ':lastname'));
                $orX->add($andXLastname);

                $andXFirstname->add($qb->expr()->like('v.value', ':search'));
                $andXFirstname->add($qb->expr()->like('a.name', ':firstname'));
                $orX->add($andXFirstname);
                $qb->setParameter('lastname', 'lastname');
                $qb->setParameter('firstname', 'firstname');
            } else {
                $orX->add($qb->expr()->like('x.' . $field, ':search'));
            }
        }
        $qb->andWhere($orX);
        $qb->setParameter('search', '%' . $term . '%');

        return $qb;
    }
}