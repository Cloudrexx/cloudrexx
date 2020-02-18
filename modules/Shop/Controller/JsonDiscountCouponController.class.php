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
 * JsonController for Discount Coupon
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_shop
 * @version    5.0.0
 */
namespace Cx\Modules\Shop\Controller;

/**
 * JsonController for Discount Coupon
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_shop
 * @version    5.0.0
 */
class JsonDiscountCouponController
    extends \Cx\Core\Core\Model\Entity\Controller
    implements \Cx\Core\Json\JsonAdapter
{
    /**
     * From this value the coupon is unlimited
     */
    const USES_UNLIMITED = 1e10;

    /**
     * @var array messages from this controller
     */
    protected $messages;

    /**
     * Returns the internal name used as identifier for this adapter
     *
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'DiscountCoupon';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'getUsernameOrAny',
            'getNameOrAny',
            'getProductDropdown',
            'getPaymentDropdown',
            'formatDate',
            'convertDateToInt',
            'getDashIfEmpty',
            'getNullPriceIfEmpty',
            'getEndTime',
            'addUnlimitedEndTimeCheckbox',
            'addUnlimitedUsesCheckbox',
            'setDiscountAmount',
            'setDiscountRate',
            'setUnlimitedUsesIfEmpty',
            'getUseStatus',
            'getGlobalAndUserCheckboxes',
            'checkIfCouponIsGlobal',
            'getInfoIfIsGlobalOrCustomer',
            'getTypeCheckboxes',
            'getCouponLink'
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
     * Get the username or a general response depending on whether a customer is
     * assigned
     *
     * @global array  $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string username or general response
     */
    public function getUsernameOrAny($params)
    {
        global $_ARRAYLANG;

        $value = !empty($params['data']) ? $params['data'] : '';

        if (empty($value)) {
            return $_ARRAYLANG['TXT_MODULE_SHOP_ANY'];
        }
        $user = \FWUser::getFWUserObject()->objUser->getUser($value->getId());

        return $user->getUsername() . ' (' . $value . ')';
    }

    /**
     * Get the name or a general response depending on whether a entity
     * is assigned
     *
     * @global array  $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string name or general response
     */
    public function getNameOrAny($params)
    {
        global $_ARRAYLANG;

        $value = !empty($params['data']) ? $params['data'] : '';

        if (empty($value)) {
            return $_ARRAYLANG['TXT_MODULE_SHOP_ANY'];
        }

        return $value  . ' (' . $value->getId() . ')';
    }

    /**
     * Add an element so that "no product" can also be selected
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\DataElement dropdown for products
     */
    public function getProductDropdown($params)
    {
        return $this->getDropdownWithOtherDefault($params, 'Product');
    }

    /**
     * Add an element so that "no product" can also be selected
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\DataElement dropdown for products
     */
    public function getPaymentDropdown($params)
    {
        return $this->getDropdownWithOtherDefault($params, 'Payment');
    }

    /**
     * Set $_ARRAYLANG['TXT_SHOP_PAYMENT_ANY'] as first element of a
     * dropdown.
     *
     * @global array  $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     * @param $entityName string name of selected entity
     *
     * @return \Cx\Core\Html\Model\Entity\DataElement dropdown with element
     */
    protected function getDropdownWithOtherDefault($params, $entityName)
    {
        global $_ARRAYLANG;

        $name = !empty($params['name']) ? $params['name'] : '';
        $value = !empty($params['value']) ? $params['value'] : '';

        $entities = $this->cx->getDb()->getEntityManager()->getRepository(
            'Cx\\Modules\\Shop\\Model\\Entity\\' . $entityName
        )->findAll();

        $validValues = array($_ARRAYLANG['TXT_MODULE_SHOP_ANY']);
        foreach ($entities as $entity) {
            $validValues[$entity->getId()] = $entity;
        }

        $dropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            $name,
            $value->getId(),
            'select',
            null,
            $validValues
        );

        return $dropdown;
    }

    /**
     * Format data which are stored as integers to a readable date
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string formatted date
     */
    public function formatDate($params)
    {
        $value = !empty($params['fieldvalue']) ? $params['fieldvalue'] : '';

        if (!empty($value) && is_numeric($value)) {
            $value = date(ASCMS_DATE_FORMAT_DATE, $value);
        }
        return $value;
    }

    /**
     * Convert the date to an integer before saving the entity
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return int converted date
     */
    public function convertDateToInt($params)
    {
        $value = !empty($params['postedValue']) ? $params['postedValue'] : '';

        return strtotime($value);
    }

    /**
     * Return a dash (-) if the value is empty.
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string value or dash if value is empty
     */
    public function getDashIfEmpty($params)
    {
        if (empty($params['data'])) {
            return '-';
        }
        return $params['data'];
    }

    /**
     * Return a string (-.--) if the value is empty.
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string value or dash if value is empty
     */
    public function getNullPriceIfEmpty($params)
    {
        if (!isset($params['data']) || $params['data'] == 0.00) {
            return '-.--';
        }
        return $params['data'];
    }

    /**
     * Return a string that can contain a date or the infrormation that this
     * coupon has no end time
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string information to the end time
     */
    public function getEndTime($params)
    {
        global $_ARRAYLANG;

        $value = !empty($params['data']) ? $params['data'] : '';

        if (empty($value)) {
            return $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_END_TIME_UNLIMITED'];
        }
        return $value;
    }

    /**
     * Append a checkbox to the normal input to define the end time as unlimited
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement end time input field
     */
    public function addUnlimitedEndTimeCheckbox($params)
    {
        global $_ARRAYLANG;

        return $this->addCheckbox(
            $params,
            $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_END_TIME_UNLIMITED']
        );
    }

    /**
     * Append a checkbox to the normal input to define the uses as unlimited
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement uses input field
     */
    public function addUnlimitedUsesCheckbox($params)
    {
        global $_ARRAYLANG;

        return $this->addCheckbox(
            $params,
            $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_USES_UNLIMITED']
        );
    }

    /**
     * Append a checkbox to the normal input field
     *
     * @param array $params contains the parameters of the callback function
     * @param $labelText string label which the checkbox should have
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement input field with checkbox
     */
    protected function addCheckbox($params, $labelText)
    {
        $name = !empty($params['name']) ? $params['name'] : '';
        $value = !empty($params['value']) ? $params['value'] : '';
        $type = !empty($params['type']) ? $params['type'] : '';

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $input = new \Cx\Core\Html\Model\Entity\DataElement($name);
        $input->setAttribute('type', 'text');

        if ($type == 'date') {
            $input->addClass('datepicker');
            $isChecked = !empty($value);
        } else {
            $isChecked = $value > self::USES_UNLIMITED;
        }

        $text = new \Cx\Core\Html\Model\Entity\TextElement($labelText);
        $label = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $label->setAttribute('for', $name . '-unlimited');
        $checkbox = new \Cx\Core\Html\Model\Entity\DataElement(
            $name . '-unlimited'
        );
        $checkbox->setAttributes(
            array(
                'type' => 'checkbox',
                'id' => $name . '-unlimited',
                'checked' => $isChecked,
            )
        );
        $checkbox->addClass('shop-unlimited');

        $label->addChild($text);
        $wrapper->addChildren(array($input, $checkbox, $label));

        return $wrapper;
    }

    /**
     * Set if coupon has a discount amount and the discount amount
     * option is selected
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string discount amount to set
     * @throws \Exception handle if param does not exist
     */
    public function setDiscountAmount($params)
    {
        return $this->setDiscountAmountOrRate($params, 'discountAmount');
    }

    /**
     * Set if coupon has a discount rate and the discount rate
     * option is selected
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string discount rate to set
     * @throws \Exception handle if param does not exist
     */
    public function setDiscountRate($params)
    {
        return $this->setDiscountAmountOrRate($params, 'discountRate');
    }

    /**
     * Check whether the amount or the rate for the coupon should be set. To do
     * this, we check which option was selected. If the given option is not
     * selected, an empty value is returned.
     *
     * @param array $params contains the parameters of the callback function
     * @param string $postKey name of the option to check
     *
     * @return string value to set
     * @throws \Exception handle if param does not exist
     */
    protected function setDiscountAmountOrRate($params, $postKey)
    {
        $value = !empty($params['postedValue']) ? $params['postedValue'] : '';

        if (
            $this->cx->getRequest()->hasParam('coupon_type', false) &&
            $this->cx->getRequest()->getParam('coupon_type', false) !=
            $postKey
        ) {
            return '';
        }
        return $value;
    }

    /**
     * If value is empty set uses to unlimited
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return float amount of uses
     */
    public function setUnlimitedUsesIfEmpty($params)
    {
        if (!empty($params['postedValue'])) {
            return $params['postedValue'];
        }
        return static::USES_UNLIMITED;
    }

    /**
     * Get ratio of used and max uses of a coupon.
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string ratio of used and max uses
     */
    public function getUseStatus($params)
    {
        global $_ARRAYLANG;

        $value = !empty($params['data']) ? $params['data'] : '';
        $rows = !empty($params['rows']) ? $params['rows'] : array();
        $couponId = !empty($rows['id']) ? $rows['id'] : '';

        $coupon = $this->cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\DiscountCoupon'
        )->find($couponId);

        if (empty($coupon)) {
            return '';
        }

        $uses = $coupon->getUsedCount();
        $max = $value;
        if ($value > 1e9) {
            $max = $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_USES_UNLIMITED'];
        }

        return $uses .' / '. $max;
    }

    /**
     * Get the detail elements for the global field. Contains a checkbox to
     * select global or customer and a customer live search.
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     * @throws \Exception
     */
    public function getGlobalAndUserCheckboxes($params)
    {
        global $_ARRAYLANG;

        $value = isset($params['value']) ? $params['value'] : true;
        $name = !empty($params['name']) ? $params['name'] : '';
        $couponId = !empty($params['id']) ? $params['id'] : 0;

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        $types = array(
            'couponGlobal' => $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_GLOBALLY'],
            'couponCustomer' => $_ARRAYLANG[
                'TXT_SHOP_DISCOUNT_COUPON_PER_CUSTOMER'
            ]
        );

        $default = 'couponCustomer';
        if ($value || ($value == '' && !$couponId)) {
            $default = 'couponGlobal';
        }

        $checkboxes = $this->getCustomRadioButtons(
            $name,
            $types,
            $default
        );

        $wrapper->addChild($checkboxes);

        \FWUser::getUserLiveSearch(
            array(
                'minLength' => 3,
                'canCancel' => true,
                'canClear' => true
            )
        );

        $customerId = 0;
        $customerName = '';

        $coupon = $this->cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\DiscountCoupon'
        )->find($couponId);

        if (!empty($coupon) && !empty($coupon->getCustomer())) {
            $customer = $coupon->getCustomer();
            $customerId = $customer->getId();
            $customerName = $customer->getUsername();
        }

        $customerWidget = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $customerId = new \Cx\Core\Html\Model\Entity\DataElement(
            'customer', $customerId
        );
        $customerName = new \Cx\Core\Html\Model\Entity\DataElement(
            'customer_name', $customerName
        );

        $widgetDisplay = $value || $value == '' ? 'none' : 'block';
        $customerWidget->setAttributes(
            array(
                'style' => 'display:' . $widgetDisplay,
                'id' => 'user-live-search'
            )
        );
        $customerId->setAttribute('id', 'customer');
        $customerId->addClass('live-search-user-id');
        $customerName->setAttribute('id', 'customer_name');
        $customerName->addClass('live-search-user-name');

        $customerWidget->addChildren(array($customerId, $customerName));
        $wrapper->addChild($customerWidget);

        return $wrapper;
    }

    /**
     * Return a div with radio buttons
     *
     * @param $name    string field name
     * @param $options array  contains content for radio buttons
     * @param $default string define which button is selected
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement custom radio buttons
     */
    protected function getCustomRadioButtons($name, $options, $default)
    {
        $div = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        foreach ($options as $option=>$text) {
            $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
            $input = new \Cx\Core\Html\Model\Entity\DataElement($name, $option);
            $label = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
            $labelText = new \Cx\Core\Html\Model\Entity\TextElement($text);
            $input->setAttributes(
                array(
                    'type' => 'radio',
                    'id' => $option,
                )
            );
            $label->setAttribute('for', $option);

            if ($option == $default) {
                $input->setAttribute('checked', true);
            }

            $label->addChild($labelText);
            $wrapper->addChild($input);
            $wrapper->addChild($label);
            $div->addChild($wrapper);
        }

        return $div;
    }

    /**
     * Check if coupon is global and return the result
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return bool if coupon is global
     */
    public function checkIfCouponIsGlobal($params)
    {
        $value = !empty($params['postedValue'])
            ? $params['postedValue'] : 'couponGlobal';

        return $value == 'couponGlobal';
    }

    /**
     * Get a information if the coupon is global or per customer
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return string info to show is coupon global or per customer
     */
    public function getInfoIfIsGlobalOrCustomer($params)
    {
        global $_ARRAYLANG;

        if (empty($params['data'])) {
            return $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_PER_CUSTOMER'];
        }
        return $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_GLOBALLY'];
    }

    /**
     * Get checkboxes to select the coupon type
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement wrapper with checkboxes
     */
    public function getTypeCheckboxes($params)
    {
        global $_ARRAYLANG;

        $couponId = !empty($params['id']) ? $params['id'] : 0;
        $types = array(
            'discountRate' => $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_TYPE_RATE'],
            'discountAmount' => $_ARRAYLANG[
                'TXT_SHOP_DISCOUNT_COUPON_TYPE_AMOUNT'
            ]
        );

        $coupon = $this->cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\DiscountCoupon'
        )->find($couponId);

        $default = 'discountRate';
        if (
            !empty($coupon) &&
            !empty($coupon->getDiscountAmount()) &&
            $coupon->getDiscountAmount() != 0.00
        ) {
            $default = 'discountAmount';
        }

        return $this->getCustomRadioButtons(
            'coupon_type',
            $types,
            $default
        );
    }

    /**
     * Return an element to display the coupon link
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement link element
     * @throws \Cx\Core\Routing\UrlException
     */
    public function getCouponLink($params)
    {
        $rowData = !empty($params['rows']) ? $params['rows'] : array();
        $code = !empty($rowData['code']) ? $rowData['code'] : '';

        $url = \Cx\Core\Routing\Url::fromModuleAndCmd('Shop');

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $icon = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $input = new \Cx\Core\Html\Model\Entity\DataElement(
            'coupon-url-link',
            $url->toString() . '?coupon_code=' . $code
        );

        $wrapper->addClass('coupon-url');
        $icon->addClass('coupon-url-icon icon_url');
        $input->addClass('coupon-url-link');
        $icon->allowDirectClose(true);
        $icon->addChild(new \Cx\Core\Html\Model\Entity\TextElement(''));

        $wrapper->addChildren(array($icon, $input));

        return $wrapper;
    }
}