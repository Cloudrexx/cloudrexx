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
 * OrderController to handle orders
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * OrderController to handle orders
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class OrderController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * @var array all possible fields for the order show view
     */
    protected $allFields = array(
        'id',
        'dateTime',
        'status',
        'modifiedOn',
        'modifiedBy',
        'lang',
        'billingCompany',
        'billingGender',
        'billingLastname',
        'billingFirstname',
        'billingAddress',
        'billingZip',
        'billingCity',
        'billingCountryId',
        'billingPhone',
        'billingFax',
        'billingEmail',
        'company',
        'gender',
        'lastname',
        'firstname',
        'address',
        'zip',
        'city',
        'country',
        'phone',
        'shipper',
        'payment',
        'lsvs',
        'orderItems',
        'vatAmount',
        'emptyField',
        'shipmentAmount',
        'paymentAmount',
        'sum',
        'note',
        'currencyId',
        'countryId',
        'shipmentId',
        'paymentId',
        'ip',
        'langId',
        'relCustomerCoupons',
        'currency',
        'customer',
        'customerId'
    );

    /**
     * Get ViewGenerator options for Manufacturer entity
     *
     * @param $options array predefined ViewGenerator options
     *
     * @return array includes ViewGenerator options for Order entity
     * @throws \Exception
     */
    public function getViewGeneratorOptions($options)
    {
        global $_ARRAYLANG;

        // Until we know how to get the editId without the $_GET param
        if ($this->cx->getRequest()->hasParam('editid')) {
            $this->orderId = explode(
                '}',
                explode(
                    ',',
                    $this->cx->getRequest()->getParam('editid')
                )[1]
            )[0];
        }
        if ($this->cx->getRequest()->hasParam('showid')) {
            $this->orderId = explode(
                '}',
                explode(
                    ',',
                    $this->cx->getRequest()->getParam('showid')
                )[1]
            )[0];
        }

        $options['showPrimaryKeys'] = true;
        $options['functions']['filtering'] = true;
        $options['functions']['searching'] = true;
        $options['functions']['show'] = true;
        $options['functions']['editable'] = true;
        $options['functions']['paging'] = true;
        $options['functions']['add'] = false;
        $options['functions']['onclick']['delete'] = 'deleteOrder';
        $options['functions']['order']['id'] = SORT_DESC;
        $options['functions']['alphabetical'] = 'customer';
        $options['multiActions']['delete'] = array(
            'title' => $_ARRAYLANG['TXT_DELETE'],
            'jsEvent' => 'delete:order'
        );

        // Callback for expanded search
        $options['functions']['filterCallback'] = array(
            'adapter' => 'Order',
            'method' => 'filterCallback'
        );

        // Callback for search
        $options['functions']['searchCallback'] = array(
            'adapter' => 'Order',
            'method' => 'searchCallback'
        );

        // Delete Event
        $scope = 'order';
        \ContrexxJavascript::getInstance()->setVariable(
            'CSRF_PARAM',
            \Cx\Core\Csrf\Controller\Csrf::code(),
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_CONFIRM_DELETE_ORDER',
            $_ARRAYLANG['TXT_CONFIRM_DELETE_ORDER'],
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_ACTION_IS_IRREVERSIBLE',
            $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_SHOP_CONFIRM_RESET_STOCK',
            $_ARRAYLANG['TXT_SHOP_CONFIRM_RESET_STOCK'],
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_SHOP_CONFIRM_REDUCE_STOCK',
            $_ARRAYLANG['TXT_SHOP_CONFIRM_REDUCE_STOCK'],
            $scope
        );

        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_SHOP_CONFIRM_UPDATE_STATUS',
            $_ARRAYLANG['TXT_CONFIRM_CHANGE_STATUS'],
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'SHOP_UPDATE_ORDER_STATUS_URL',
            \Cx\Core\Routing\Url::fromApi(
                'updateOrderStatus', array()
            )->toString(),
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_SHOP_SEND_TEMPLATE_TO_CUSTOMER',
            $_ARRAYLANG['TXT_SEND_MAIL'],
            $scope
        );

        \ContrexxJavascript::getInstance()->setVariable(
            'SHOP_ORDER_PENDENT_KEY',
            \Cx\Modules\Shop\Model\Repository\OrderRepository::STATUS_PENDING,
            $scope
        );

        $options['order'] = array(
            'overview' => array(
                'id',
                'dateTime',
                'status',
                'customer',
                'note',
                'sum'
            ),
            'form' => array(
                'id',
                'dateTime',
                'status',
                'modifiedOn',
                'modifiedBy',
                'lang',
                'titleAddress',
                'billingCompany',
                'company',
                'billingGender',
                'gender',
                'billingLastname',
                'lastname',
                'billingFirstname',
                'firstname',
                'billingAddress',
                'address',
                'billingZip',
                'zip',
                'billingCity',
                'city',
                'billingCountryId',
                'countryId',
                'billingPhone',
                'phone',
                'billingFax',
                'emptyFieldBill',
                'billingEmail',
                'shipper',
                'titlePaymentInfos',
                'payment',
                'lsvs',
                'titleBill',
                'orderItems',
                'vatAmount',
                'emptyField',
                'shipmentAmount',
                'paymentAmount',
                'sum',
                'titleNote',
                'note'
            ),
        );
        $options['fields'] = array(
            'id' => array(
                'showOverview' => true,
                'showDetail' => true,
                'allowSearching' => true,
                'allowFiltering' => false,
                'formtext' => $_ARRAYLANG['DETAIL_ID'],
                'table' => array(
                    'attributes' => array(
                        'class' => 'order-id',
                    ),
                ),
                'attributes' => array(
                    'class' => 'readonly',
                ),
                'readonly' => true,
                'sorting' => true,
            ),
            'customerId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'currencyId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'sum' => array(
                'showOverview' => true,
                'allowFiltering' => false,
                'sorting' => false,
                'header' => $_ARRAYLANG['TXT_SHOP_ORDER_SUM'],
                'table' => array(
                    'attributes' => array(
                        'class' => 'order-sum',
                    ),
                ),
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getCustomInputField'
                ),
            ),
            'dateTime' => array(
                'showOverview' => true,
                'allowFiltering' => false,
                'allowSearching' => true,
                'sorting' => false,
                'formtext' => $_ARRAYLANG['DETAIL_DATETIME'],
                'table' => array (
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'formatDateInOverview'
                    ),
                    'attributes' => array(
                        'class' => 'order-date-time',
                    ),
                ),
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'formatDateInDetail',
                ),
                'attributes' => array(
                    'class' => 'readonly',
                ),
                'readonly' => true,
                'type' => 'input',
            ),
            'status' => array(
                'showOverview' => true,
                'sorting' => false,
                'searchCheckbox' => 0,
                'formtext' => $_ARRAYLANG['DETAIL_STATUS'],
                'table' => array (
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getStatusMenuForOverview'
                    ),
                    'attributes' => array(
                        'class' => 'order-status',
                    ),
                ),
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getStatusMenuForDetail'
                ),
                'filterOptionsField' => array(
                    'adapter' => 'Order',
                    'method' => 'getStatusMenuForFilter'
                ),
            ),
            'gender' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'showDetail' => true,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getGenderMenu'
                ),
            ),
            'company' => array(
                'showOverview' => false,
                'allowFiltering' => false,
            ),
            'firstname' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'lastname' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'address' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'city' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'zip' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
                'formtext' => $_ARRAYLANG['DETAIL_ZIP_CITY'],
            ),
            'countryId' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'type' => 'Country',
            ),
            'phone' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'vatAmount' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getCustomInputField'
                ),
            ),
            'shipmentAmount' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getCustomInputField'
                ),
            ),
            'shipmentId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'paymentId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'paymentAmount' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getCustomInputField'
                ),
            ),
            'ip' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'langId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'note' => array(
                'showOverview' => true,
                'allowFiltering' => false,
                'allowSearching' => true,
                'sorting' => false,
                'type' => 'div',
                'table' => array(
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getNoteToolTip'
                    ),
                    'attributes' => array(
                        'class' => 'order-note',
                    ),
                ),
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getDivWrapper'
                ),
            ),
            'modifiedOn' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'formatModifiedOnDate'
                ),
                'storecallback' => array(
                    'adapter' => 'Order',
                    'method' => 'getCurrentDate'
                ),
            ),
            'modifiedBy' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'readonly' => false,
                'attributes' => array(
                    'class' => 'readonly'
                ),
                'storecallback' => array(
                    'adapter' => 'Order',
                    'method' => 'getCurrentUser'
                ),
            ),
            'billingGender' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'allowSearching' => true,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getGenderMenu'
                ),
            ),
            'billingCompany' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingFirstname' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingLastname' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingAddress' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingCity' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingZip' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'allowSearching' => true,
                'formtext' => $_ARRAYLANG['DETAIL_ZIP_CITY'],
            ),
            'billingCountryId' => array(
                'showOverview' => false,
                'type' => 'Country',
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingPhone' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingFax' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'billingEmail' => array(
                'showOverview' => false,
                'allowSearching' => true,
                'allowFiltering' => false,
            ),
            'orderItems' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'generateOrderItemView'
                ),
                'storecallback' => array(
                    'adapter' => 'Order',
                    'method' => 'storeOrderItem'
                ),
            ),
            'relCustomerCoupons' => array(
                'showOverview' => false,
                'showDetail' => true,
                'mode' => 'associate',
                'type' => 'hidden',
                'allowFiltering' => false,
            ),
            'lang' => array(
                'header' => $_ARRAYLANG['TXT_BROWSER_LANGUAGE'],
                'showOverview' => false,
                'allowFiltering' => false,
                'attributes' => array(
                    'class' => 'readonly',
                ),
                'readonly' => true,
                'type' => 'input',
            ),
            'currency' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'shipper' => array(
                'showOverview' => false,
                'allowFiltering' => false,
            ),
            'payment' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'attributes' => array(
                    'class' => 'readonly',
                ),
                'readonly' => true,
                'type' => 'input',
            ),
            'customer' => array(
                'showOverview' => true,
                'showDetail' => false,
                'sorting' => false,
                'allowSearching' => true,
                'table' => array (
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getCustomerLink'
                    ),
                    'attributes' => array(
                        'class' => 'order-customer',
                    ),
                ),
                'filterOptionsField' => array(
                    'adapter' => 'Order',
                    'method' => 'getCustomerGroupMenu'
                ),
            ),
            'titleAddress' => array(
                'custom' => true,
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getTitleAddress'
                ),
            ),
            'titlePaymentInfos' => array(
                'custom' => true,
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getTitlePaymentInfo'
                ),
            ),
            'titleBill' => array(
                'custom' => true,
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getTitleBill'
                ),
            ),
            'titleNote' => array(
                'custom' => true,
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getTitleNote'
                ),
            ),
            'emptyField' => array(
                'custom' => true,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getDivWrapper'
                ),
                'showOverview' => false,
            ),
            'emptyFieldBill' => array(
                'custom' => true,
                'header' => ' ',
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'getDivWrapper'
                ),
                'showOverview' => false,
            ),
            'showAllPendentOrders' => array(
                'custom' => true,
                'showOverview' => false,
                'showDetail' => false,
                'filterOptionsField' => array(
                    'adapter' => 'Order',
                    'method' => 'getShowAllPendentOrders'
                )
            )
        );

        $order = new \Cx\Modules\Shop\Model\Entity\Order();

        if (!empty($this->orderId)) {
            $order = $this->cx->getDb()->getEntityManager()->getRepository(
                '\Cx\Modules\Shop\Model\Entity\Order'
            )->findOneBy(array('id' => $this->orderId));
        }
        if (!empty($order) && count($order->getLsvs()) > 0) {
            $options['fields']['lsvs'] = array(
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Order',
                    'method' => 'generateLsvs'
                ),
                'storecallback' => function($value, $entity) {
                    $repo = $this->cx->getDb()->getEntityManager()
                        ->getRepository(
                            '\Cx\Modules\Shop\Model\Entity\Lsv'
                        );
                    $repo->save($value, $entity->getId());
                },
            );
        } else {
            $options['fields']['lsvs'] = array(
                'showOverview' => false,
                'allowFiltering' => false,
                'showDetail' => false,
            );
        }
        return $options;
    }


    /**
     * Return custom lsv edit field.
     *
     * @param \Cx\Modules\Shop\Model\Entity\Lsv $entity lsv entity
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function generateLsvs($entity)
    {
        global $_ARRAYLANG;

        $entity = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Lsv'
        )->findOneBy(array('orderId' => $this->orderId));

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
     * Sets up the Order statistics
     *
     * @param \Cx\Core\Html\Sigma $objTemplate The optional Template, by
     *                                         reference
     *
     * @return bool if the view was created successfully
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    static function view_statistics(&$objTemplate=null)
    {
        global $_ARRAYLANG;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        if (!$objTemplate || !$objTemplate->blockExists('no_order')) {
            $objTemplate = new \Cx\Core\Html\Sigma(
                \Cx\Core\Core\Controller\Cx::instanciate()
                    ->getCodeBaseModulePath() . '/Shop/View/Template/Backend'
            );
            $objTemplate->loadTemplateFile('module_shop_statistic.html');
        }
        $objTemplate->setGlobalVariable($_ARRAYLANG);
        // Get the first order date; if its empty, no order has been placed yet
        $firstOrder = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Order'
        )->getFirstOrder();
        if (empty($firstOrder)) {
            $objTemplate->touchBlock('no_order');
            return $objTemplate;
        }
        $year_first_order = $firstOrder->getDateTime()->format('Y');
        $month_first_order = $firstOrder->getDateTime()->format('m');
        $start_month = $end_month = $start_year = $end_year = NULL;
        if (isset($_REQUEST['submitdate'])) {
            // A range is requested
            $start_month = intval($_REQUEST['startmonth']);
            $end_month = intval($_REQUEST['stopmonth']);
            $start_year = intval($_REQUEST['startyear']);
            $end_year = intval($_REQUEST['stopyear']);
        } else {
            // Default range to one year, or back to the first order if less
            $start_month = $month_first_order;
            $end_month = Date('m');
            $start_year = $end_year = Date('Y');
            if ($year_first_order < $start_year) {
                $start_year -= 1;
                if (   $year_first_order < $start_year
                    || $month_first_order < $start_month) {
                    $start_month = $end_month;
                }
            }
        }
        $objTemplate->setVariable(
            array(
                'SHOP_START_MONTH' =>
                    Shopmanager::getMonthDropdownMenu($start_month),
                'SHOP_END_MONTH' =>
                    Shopmanager::getMonthDropdownMenu($end_month),
                'SHOP_START_YEAR' =>
                    Shopmanager::getYearDropdownMenu(
                        $start_year, $year_first_order
                    ),
                'SHOP_END_YEAR' =>
                    Shopmanager::getYearDropdownMenu(
                        $end_year, $year_first_order
                    ),
            )
        );
        $start_date = date(
            ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME,
            mktime(0, 0, 0, $start_month, 1, $start_year)
        );
        // mktime() will fix the month from 13 to 01, see example 2
        // on http://php.net/manual/de/function.mktime.php.
        // Mind that this is exclusive and only used in the queries below
        // so that Order date < $end_date!
        $end_date = date(
            ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME,
            mktime(
                0, 0, 0, $end_month+1, 1,
                $end_year
            )
        );
        $qb = $cx->getDb()->getEntityManager()->createQueryBuilder();

        $selectedStat = (isset($_REQUEST['selectstats'])
            ? intval($_REQUEST['selectstats']) : 0);
        if ($selectedStat == 2) {
            // Product statistic
            $objTemplate->setVariable(
                array(
                    'TXT_COLUMN_1_DESC' => $_ARRAYLANG['TXT_PRODUCT_NAME'],
                    'TXT_COLUMN_2_DESC' => $_ARRAYLANG['TXT_COUNT_ARTICLES'],
                    'TXT_COLUMN_3_DESC' => $_ARRAYLANG['TXT_STOCK'],
                    'SHOP_ORDERS_SELECTED' => '',
                    'SHOP_ARTICLES_SELECTED' => \Html::ATTRIBUTE_SELECTED,
                    'SHOP_CUSTOMERS_SELECTED' => '',
                )
            );
            $query = $qb->select(
                array(
                    'A.productId AS id', 'A.quantity AS shopColumn2',
                    'A.price AS total', 'B.stock AS shopColumn3',
                    'C.currencyId', 'B.name AS title'
                )
            )->from('Cx\Modules\Shop\Model\Entity\OrderItem', 'A')
                ->join(
                    'A.order', 'C', 'WITH',
                    $qb->expr()->eq('A.orderId', 'C.id')
                )->join(
                    'A.product', 'B', 'WITH',
                    $qb->expr()->eq('A.productId', 'B.id')
                )->where(
                    $qb->expr()->andX(
                        'C.dateTime >= ?1',
                        'C.dateTime < ?2',
                        $qb->expr()->orX(
                            $qb->expr()->eq('C.status', '?3'),
                            $qb->expr()->eq('C.status', '?4')
                        )
                    )
                )->orderBy('shopColumn2', 'DESC')->setParameters(
                    array(
                        1 => $start_date,
                        2 => $end_date,
                        3 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                            STATUS_CONFIRMED,
                        4 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                            STATUS_COMPLETED
                    )
                )->getQuery();
        } elseif ($selectedStat == 3) {
            // Customer statistic
            $objTemplate->setVariable(
                array(
                    'TXT_COLUMN_1_DESC' => $_ARRAYLANG['TXT_NAME'],
                    'TXT_COLUMN_2_DESC' => $_ARRAYLANG['TXT_COMPANY'],
                    'TXT_COLUMN_3_DESC' => $_ARRAYLANG['TXT_COUNT_ARTICLES'],
                    'SHOP_ORDERS_SELECTED' => '',
                    'SHOP_ARTICLES_SELECTED' => '',
                    'SHOP_CUSTOMERS_SELECTED' => \Html::ATTRIBUTE_SELECTED,
                )
            );
            $query = $qb->select(
                array(
                    'A.sum AS total ', 'A.currencyId',
                    'SUM(B.quantity) AS shopColumn3', 'A.customerId'
                )
            )->from(
                'Cx\Modules\Shop\Model\Entity\Order', 'A'
            )->join(
                'A.orderItems', 'B', 'WITH',
                $qb->expr()->eq('A.id', 'B.orderId')
            )->where(
                $qb->expr()->andX(
                    'A.dateTime >= ?1',
                    'A.dateTime < ?2',
                    $qb->expr()->orX(
                        $qb->expr()->eq('A.status', '?3'),
                        $qb->expr()->eq('A.status', '?4')
                    )
                )
            )->groupBy('B.orderId')->orderBy('A.sum', 'DESC')
                ->setParameters(
                    array(
                        1 => $start_date,
                        2 => $end_date,
                        3 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                            STATUS_CONFIRMED,
                        4 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                            STATUS_COMPLETED
                    )
                )->getQuery();
        } else {
            // Order statistic (default); sales per month
            $objTemplate->setVariable(
                array(
                    'TXT_COLUMN_1_DESC' => $_ARRAYLANG['TXT_DATE'],
                    'TXT_COLUMN_2_DESC' => $_ARRAYLANG['TXT_COUNT_ORDERS'],
                    'TXT_COLUMN_3_DESC' => $_ARRAYLANG['TXT_COUNT_ARTICLES'],
                    'SHOP_ORDERS_SELECTED' => \Html::ATTRIBUTE_SELECTED,
                    'SHOP_ARTICLES_SELECTED' => '',
                    'SHOP_CUSTOMERS_SELECTED' => '',
                )
            );

            $query = $qb->select(
                array(
                    'SUM(A.quantity) AS shopColumn3',
                    'COUNT(A.orderId) AS shopColumn2', 'B.currencyId',
                    'B.sum AS total', 'B.dateTime'
                )
            )->from('Cx\Modules\Shop\Model\Entity\OrderItem', 'A')
                ->join(
                    'A.order', 'B', 'WITH',
                    $qb->expr()->eq('A.orderId', 'B.id')
                )->where(
                    $qb->expr()->andX(
                        'B.dateTime >= ?1',
                        'B.dateTime < ?2',
                        $qb->expr()->orX(
                            $qb->expr()->eq('B.status', '?3'),
                            $qb->expr()->eq('B.status', '?4')
                        )
                    )
                )->groupBy('A.id')->orderBy('B.dateTime', 'DESC')
                ->setParameters(
                    array(
                        1 => $start_date,
                        2 => $end_date,
                        3 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                        STATUS_CONFIRMED,
                        4 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                        STATUS_COMPLETED
                    )
                )->getQuery();
        }
        $arrayResults = array();
        $results = $query->getArrayResult();

        $sumColumn3 = $sumColumn4 = 0;
        $sumColumn2 = '';

        $defaultCurrency = $cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Currency'
        )->getDefaultCurrency();
        if ($selectedStat == 2) {
            // Product statistc
            foreach ($results as $result) {
                // set currency id
                \Cx\Modules\Shop\Controller\CurrencyController::
                    setActiveCurrencyId($result['currencyId']);
                $key = $result['id'];
                if (!isset($arrayResults[$key])) {
                    $arrayResults[$key] = array(
                        'column1' =>
                            '<a href="index.php?cmd=Shop'.MODULE_INDEX.
                            '&amp;act=products&amp;tpl=manage&amp;id='.
                            $result['id'].
                            '" title="'.$result['title'].'">'.
                            $result['title'].'</a>',
                        'column2' => 0,
                        'column3' => $result['shopColumn3'],
                        'column4' => 0,
                    );
                }
                $arrayResults[$key]['column2'] +=
                    + $result['shopColumn2'];
                $arrayResults[$key]['column4'] +=
                    + $result['shopColumn2']
                    * \Cx\Modules\Shop\Controller\CurrencyController::
                        getDefaultCurrencyPrice($result['total']);
            }
            if (is_array($arrayResults)) {
                foreach ($arrayResults AS $entry) {
                    $sumColumn2 = $sumColumn2 + $entry['column2'];
                    $sumColumn3 = $sumColumn3 + $entry['column3'];
                    $sumColumn4 = $sumColumn4 + $entry['column4'];
                }
                rsort($arrayResults);
            }
        } elseif ($selectedStat == 3) {
            // Customer statistic
            foreach ($results as $result) {
                \Cx\Modules\Shop\Controller\CurrencyController::
                    setActiveCurrencyId($result['currencyId']);
                $key = $result['customerId'];
                if (!isset($arrayResults[$key])) {
                    $objUser = \FWUser::getFWUserObject()->objUser;
                    $objUser = $objUser->getUser($key);
                    $company = '';
                    $name = $_ARRAYLANG['TXT_SHOP_CUSTOMER_NOT_FOUND'];
                    if ($objUser) {
                        $company = $objUser->getProfileAttribute('company');
                        $name =
                            $objUser->getProfileAttribute('firstname').' '.
                            $objUser->getProfileAttribute('lastname');
                    }
                    $arrayResults[$key] = array(
                        'column1' =>
                            '<a href="index.php?cmd=Shop'.MODULE_INDEX.
                            '&amp;act=customerdetails&amp;customer_id='.
                            $result->fields['id'].'">'.$name.'</a>',
                        'column2' => $company,
                        'column3' => 0,
                        'column4' => 0,
                    );
                }
                $arrayResults[$key]['column3'] += $result['shopColumn3'];
                $arrayResults[$key]['column4'] +=
                    \Cx\Modules\Shop\Controller\CurrencyController::
                        getDefaultCurrencyPrice($result['total']);
                $sumColumn3 += $result['shopColumn3'];
                $sumColumn4 += \Cx\Modules\Shop\Controller\CurrencyController::
                    getDefaultCurrencyPrice($result['total']);
            }
        } else {
            // Order statistic (default)
            $arrayMonths = explode(',', $_ARRAYLANG['TXT_MONTH_ARRAY']);
            foreach ($results as $result) {
                $key = $result['dateTime']->format('Y').'.'
                    . $result['dateTime']->format('M');
                if (!isset($arrayResults[$key])) {
                    $arrayResults[$key] = array(
                        'column1' => '',
                        'column2' => 0,
                        'column3' => 0,
                        'column4' => 0,
                    );
                }
                $arrayResults[$key]['column1'] = $arrayMonths[
                        intval($result['dateTime']->format('m'))-1
                    ].' '.$result['dateTime']->format('Y');
                $arrayResults[$key]['column2'] = $arrayResults[$key]['column2']
                    + 1;
                $arrayResults[$key]['column3'] = $arrayResults[$key]['column3']
                    + $result['shopColumn3'];
                $arrayResults[$key]['column4'] = $arrayResults[$key]['column4']
                    + \Cx\Modules\Shop\Controller\CurrencyController::
                        getDefaultCurrencyPrice($result['total']);
                $sumColumn2 = $sumColumn2 + 1;
                $sumColumn3 = $sumColumn3 + $result['shopColumn3'];
                $sumColumn4 = $sumColumn4 +
                    \Cx\Modules\Shop\Controller\CurrencyController::
                        getDefaultCurrencyPrice($result['total']);
            }
            krsort($arrayResults, SORT_NUMERIC);
        }
        $objTemplate->setCurrentBlock('statisticRow');
        $i = 0;
        if (is_array($arrayResults)) {
            foreach ($arrayResults as $entry) {
                $objTemplate->setVariable(array(
                    'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
                    'SHOP_COLUMN_1' => $entry['column1'],
                    'SHOP_COLUMN_2' => $entry['column2'],
                    'SHOP_COLUMN_3' => $entry['column3'],
                    'SHOP_COLUMN_4' =>
                        \Cx\Modules\Shop\Controller\CurrencyController::
                            formatPrice($entry['column4'])
                        .' '. $defaultCurrency->getSymbol(),
                ));
                $objTemplate->parse('statisticRow');
            }
        }
        $qbCurrency = $cx->getDb()->getEntityManager()->createQueryBuilder();
        $queryCurrency = $qbCurrency->select(
            array(
                'A.currencyId', 'A.sum', 'A.dateTime',
            )
        )->from(
            'Cx\Modules\Shop\Model\Entity\Order', 'A'
        )->where(
            $qbCurrency->expr()->orX(
                $qbCurrency->expr()->eq('A.status', '?1'),
                $qbCurrency->expr()->eq('A.status', '?2')
            )
        )->orderBy('A.dateTime', 'DESC')->setParameters(
            array(
                1 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                    STATUS_CONFIRMED,
                2 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                    STATUS_COMPLETED
            )
        )->getQuery();

        $resultsCurrency = $queryCurrency->getArrayResult();

        if (empty($resultsCurrency)) {
            return false;
        }
        $totalSoldProducts = 0;

        $qbTotal = $cx->getDb()->getEntityManager()->createQueryBuilder();
        $queryTotalProducts = $qbTotal->select(
            'SUM(B.quantity) AS shopTotalSoldProducts'
        )->from('Cx\Modules\Shop\Model\Entity\OrderItem', 'B')
            ->join(
                'B.order', 'A', 'WITH',
                $qbTotal->expr()->eq('A.id', 'B.orderId')
            )->where(
                $qbTotal->expr()->orX(
                    $qbTotal->expr()->eq('A.status', '?1'),
                    $qbTotal->expr()->eq('A.status', '?2')
                )
            )->setParameters(
                array(
                    1 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                        STATUS_CONFIRMED,
                    2 => \Cx\Modules\Shop\Model\Repository\OrderRepository::
                        STATUS_COMPLETED
                )
            )->getQuery();
        $resultTotal = $queryTotalProducts->getSingleResult();

        if ($resultTotal) {
            $totalSoldProducts = $resultTotal['shopTotalSoldProducts'];
        }
        $totalOrderSum = 0;
        $totalOrders = 0;
        $bestMonthSum = 0;
        $bestMonthDate = '';
        $arrShopMonthSum = array();
        foreach ($results as $result) {
            $orderSum = \Cx\Modules\Shop\Controller\CurrencyController::
                getDefaultCurrencyPrice($result['total']);
            $date = new \DateTime($resultsCurrency['dateTime']);
            if (
                !isset($arrShopMonthSum[$date->format('Y')][
                    $date->format('m')
                    ])
            ) {
                $arrShopMonthSum[$date->format('Y')][
                    $date->format('m')
                ] = 0;
            }
            $arrShopMonthSum[$date->format('Y')][
                $date->format('m')
            ] += $orderSum;
            $totalOrderSum += $orderSum;
            $totalOrders++;
        }
        $months = explode(',', $_ARRAYLANG['TXT_MONTH_ARRAY']);
        foreach ($arrShopMonthSum as $year => $arrMonth) {
            foreach ($arrMonth as $month => $sum) {
                if ($bestMonthSum < $sum) {
                    $bestMonthSum = $sum;
                    $bestMonthDate = $months[$month-1].' '.$year;
                }
            }
        }
        $objTemplate->setVariable(array(
            'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
            'SHOP_TOTAL_SUM' =>
                \Cx\Modules\Shop\Controller\CurrencyController::formatPrice(
                    $totalOrderSum
                ).' '. $defaultCurrency->getSymbol(),
            'SHOP_MONTH' => $bestMonthDate,
            'SHOP_MONTH_SUM' =>
                \Cx\Modules\Shop\Controller\CurrencyController::formatPrice(
                    $bestMonthSum
                ).' '. $defaultCurrency->getSymbol(),
            'SHOP_TOTAL_ORDERS' => $totalOrders,
            'SHOP_SOLD_ARTICLES' => $totalSoldProducts,
            'SHOP_SUM_COLUMN_2' => $sumColumn2,
            'SHOP_SUM_COLUMN_3' => $sumColumn3,
            'SHOP_SUM_COLUMN_4' =>
                \Cx\Modules\Shop\Controller\CurrencyController::formatPrice(
                    $sumColumn4
                ).' '. $defaultCurrency->getSymbol(),
        ));
        return true;
    }

    /**
     * Parse detail page for orders
     *
     * @param \Cx\Core\Html\Sigma $template template to parse
     * @param string $entityClassName       name of entity class
     * @param array $options                previous options
     *
     * @return \Cx\Core\Html\Sigma modified template
     * @throws \Cx\Core\Html\Controller\ViewGeneratorException
     */
    public function parseOrderDetailPage($template, $entityClassName, $options)
    {
        if (!$template->blockExists('shop_order_detail')) {
            return $template;
        }

        $orderSections = array(
            'Info',
            'Billing',
            'Shipping',
            'Payment',
            'Items',
            'Note'
        );

        $entityId = 0;
        if ($this->cx->getRequest()->hasParam('showid')) {
            $entityId = \Cx\Core\Html\Controller\ViewGenerator::getParam(
                0, $this->cx->getRequest()->getParam('showid')
            );
        }

        $i = 0;
        foreach ($orderSections as $section) {
            $methodName = 'getVgOptionsOrder'.$section;
            $vgOptions = $this->$methodName($options);
            if ($i > 0) {
                $vgEntityId = ',{'.$i.','.$entityId.'}';
                if ($this->cx->getRequest()->hasParam('showid')) {
                    $_GET['showid'] .= $vgEntityId;
                }
            }
            $view = new \Cx\Core\Html\Controller\ViewGenerator(
                $entityClassName,
                array($entityClassName => $vgOptions)
            );

            $renderedContent = $view->render($isSingle);
            $template->setVariable(
                'SHOP_ORDER_' . strtoupper($section),
                $renderedContent
            );
            $i++;
        }

        $template->touchBlock('shop_order_detail');
        return $template;
    }

    /**
     * Select all options that are specified and hide the others
     *
     * @param array $options previous options
     * @param array $fieldsToShow selected pptions to be displayed
     *
     * @return array ViewGenerator options of the show view of the orders
     */
    protected function selectOrderOptions($options, $fieldsToShow)
    {
        $options['order']['show'] = $fieldsToShow;
        $options['functions']['order']['id'] = SORT_DESC;
        $options['functions']['filtering'] = true;
        $options['functions']['filterCallback'] = array(
            'adapter' => 'Order',
            'method' => 'filterCallback'
        );
        foreach ($this->allFields as $field) {
            if (!in_array($field, $fieldsToShow)) {
                $options['fields'][$field] = array(
                    'show' => array(
                        'show' => false,
                    )
                );
            }
        }

        return $options;
    }

    /**
     * Get ViewGenerator options for area Order info to display them in the show
     * view of the orders
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return array ViewGenerator options of the show view of the orders
     */
    protected function getVgOptionsOrderInfo()
    {
        global $_ARRAYLANG;

        $options = array(
            'header' => $_ARRAYLANG['TXT_ORDER']
        );

        $fieldsToShow = array(
            'id',
            'dateTime',
            'status',
            'modifiedOn',
            'lang',
            'sum'
        );

        $options['fields'] = array(
            'id' => array(
                'show' => array(
                    'header' => $_ARRAYLANG['DETAIL_ID'],
                ),
            ),
            'sum' => array(
                'header' => $_ARRAYLANG['TXT_SHOP_ORDER_SUM'],
                'show' => array(
                    'header' => $_ARRAYLANG['TXT_ORDER_SUM'],
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'appendCurrency'
                    )
                )
            ),
            'dateTime' => array(
                'show' => array(
                    'header' => $_ARRAYLANG['DETAIL_DATETIME'],
                    'parse' => function($value) {
                        $date = new \DateTime($value);
                        return $date->format('Y-m-d H:i:s');
                    }
                ),
            ),
            'status' => array(
                'show' => array(
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getStatus'
                    ),
                    'header' => $_ARRAYLANG['DETAIL_STATUS'],
                ),
            ),
            'modifiedOn' => array(
                'show' => array(
                    'parse' => function($value, $entity) {
                        global $_ARRAYLANG;
                        if (empty($value)) {
                            return $_ARRAYLANG['TXT_ORDER_WASNT_YET_EDITED'];
                        }
                        $date = new \DateTime($value);
                        return  $date->format('Y-m-d H:i:s') . ' ' .
                            $_ARRAYLANG['modifiedBy'] . ' ' .
                            $entity['modifiedBy'];
                    }
                )
            ),
        );

        return $this->selectOrderOptions($options, $fieldsToShow);
    }

    /**
     * Get ViewGenerator options for area Billing to display them in the show
     * view of the orders
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return array ViewGenerator options of the show view of the orders
     */
    protected function getVgOptionsOrderBilling()
    {
        global $_ARRAYLANG;

        $options = array(
            'header' => $_ARRAYLANG['TXT_BILLING_ADDRESS']
        );

        $fieldsToShow = array(
            'billingCompany',
            'billingGender',
            'billingLastname',
            'billingFirstname',
            'billingAddress',
            'billingZip',
            'billingCountryId',
            'billingPhone',
            'billingFax',
            'billingEmail',
            'emptyField'
        );

        $options['fields'] = array(
            'billingCompany' => array(
                'show' => array(
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'addCustomerLink'
                    )
                )
            ),
            'billingGender' => array(
                'show' => array(
                    'parse' => function($value) {
                        global $_ARRAYLANG;

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
                        $value = $validData[$value];
                        return $value;
                    }
                )
            ),
            'billingLastname' => array(
                'show' => array(
                    'show' => true,
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'addCustomerLink'
                    )
                )
            ),
            'billingFirstname' => array(
                'show' => array(
                    'show' => true,
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'addCustomerLink'
                    )
                )
            ),
            'billingZip' => array(
                'show' => array(
                    'header' => $_ARRAYLANG['DETAIL_ZIP_CITY'],
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getZipAndCity'
                    )
                ),
            ),
            'billingCountryId' => array(
                'type' => 'Country',
                'show' => array(
                    'parse' => function($value) {
                        return \Cx\Core\Country\Controller\Country::getNameById($value);
                    }
                )
            ),
            'emptyField' => array(
                'custom' => true,
                'show' => array(
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getDivWrapper'
                    ),
                ),
            ),
        );

        return $this->selectOrderOptions($options, $fieldsToShow);
    }

    /**
     * Get ViewGenerator options for area Shipping to display them in the show
     * view of the orders
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return array ViewGenerator options of the show view of the orders
     */
    protected function getVgOptionsOrderShipping()
    {
        global $_ARRAYLANG;

        $options = array(
            'header' => $_ARRAYLANG['TXT_SHIPPING_ADDRESS']
        );

        $fieldsToShow = array(
            'company',
            'gender',
            'lastname',
            'firstname',
            'address',
            'zip',
            'countryId',
            'phone',
            'emptyField',
            'shipper',
            'endRow'
        );

        $options['fields'] = array(
            'gender' => array(
                'show' => array(
                    'parse' => function($value) {
                        global $_ARRAYLANG;

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
                        $value = $validData[$value];
                        return $value;
                    }
                )
            ),
            'zip' => array(
                'show' => array(
                    'header' => $_ARRAYLANG['DETAIL_ZIP_CITY'],
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getZipAndCity'
                    )
                ),
            ),
            'countryId' => array(
                'type' => 'Country',
                'show' => array(
                    'parse' => function($value) {
                        return \Cx\Core\Country\Controller\Country::getNameById($value);
                    }
                )
            ),
            'emptyField' => array(
                'custom' => true,
                'show' => array(
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getDivWrapper'
                    ),
                ),
            ),
            'endRow' => array(
                'header' => ' ',
                'custom' => true,
                'show' => array(
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'getDivWrapper'
                    ),
                ),
            ),
        );

        return $this->selectOrderOptions($options, $fieldsToShow);
    }

    /**
     * Get ViewGenerator options for area Payment to display them in the show
     * view of the orders
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return array ViewGenerator options of the show view of the orders
     */
    protected function getVgOptionsOrderPayment()
    {
        global $_ARRAYLANG;

        $options = array(
            'header' => $_ARRAYLANG['TXT_PAYMENT_INFORMATIONS']
        );

        $fieldsToShow = array(
            'payment',
            'lsvs',
        );

        $order = new \Cx\Modules\Shop\Model\Entity\Order();
        if (!empty($this->orderId)) {
            $order = $this->cx->getDb()->getEntityManager()->getRepository(
                '\Cx\Modules\Shop\Model\Entity\Order'
            )->findOneBy(array('id' => $this->orderId));
        }
        if (empty($order) || count($order->getLsvs()) == 0) {
            $options['fields']['lsvs'] = array(
                'show' => array(
                    'show' => false,
                ),
            );
        }

        return $this->selectOrderOptions($options, $fieldsToShow);
    }

    /**
     * Get ViewGenerator options for area Order Items to display them in the
     * show view of the orders
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return array ViewGenerator options of the show view of the orders
     */
    protected function getVgOptionsOrderItems()
    {
        global $_ARRAYLANG;

        $options = array(
            'header' => $_ARRAYLANG['TXT_BILL'],
            'showPrimaryKeys' => true,
        );

        $fieldsToShow = array(
            'orderItems',
        );

        $options['fields'] = array(
            'orderItems' => array(
                'show' => array(
                    'show' => true,
                    'parse' => array(
                        'adapter' => 'Order',
                        'method' => 'generateOrderItemShowView'
                    ),
                ),
            ),
        );

        return $this->selectOrderOptions($options, $fieldsToShow);
    }

    /**
     * Get ViewGenerator options for area Note to display them in the show view
     * of the orders
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @return array ViewGenerator options of the show view of the orders
     */
    protected function getVgOptionsOrderNote()
    {
        global $_ARRAYLANG;

        $options = array(
            'header' => $_ARRAYLANG['TXT_CUSTOMER_REMARKS']
        );

        $fieldsToShow = array(
            'note'
        );

        $options['fields'] = array(
            'note' => array(
                'show' => array(
                    'parse' => function($value) {
                        if (empty($value)) {
                            return ' ';
                        }
                        return $value;
                    }
                )
            ),
        );

        return $this->selectOrderOptions($options, $fieldsToShow);
    }

    /**
     * Handles database errors
     *
     * Also migrates the old database structure to the new one
     * @return  boolean             False.  Always.
     */
    static function errorHandler()
    {
// Order
        ShopSettings::errorHandler();
        \Cx\Core\Country\Controller\Country::errorHandler();

        $table_name = DBPREFIX.'module_shop_order_items';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true, 'renamefrom' => 'order_items_id'),
            'order_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'orderid'),
            'product_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'productid'),
            'product_name' => array('type' => 'VARCHAR(255)', 'default' => ''),
            'price' => array('type' => 'DECIMAL(9,2)', 'unsigned' => true, 'default' => '0.00'),
            'quantity' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0'),
            'vat_rate' => array('type' => 'DECIMAL(5,2)', 'unsigned' => true, 'notnull' => false, 'default' => null, 'renamefrom' => 'vat_percent'),
            'weight' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0'),
        );
        $table_index = array(
            'order' => array('fields' => array('order_id')));
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        $table_name = DBPREFIX.'module_shop_order_attributes';
        if (!\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            $table_name_old = DBPREFIX.'module_shop_order_items_attributes';
            $table_structure = array(
                'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true, 'renamefrom' => 'orders_items_attributes_id'),
                'item_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'order_items_id'),
                'attribute_name' => array('type' => 'VARCHAR(255)', 'default' => '', 'renamefrom' => 'product_option_name'),
                'option_name' => array('type' => 'VARCHAR(255)', 'default' => '', 'renamefrom' => 'product_option_value'),
                'price' => array('type' => 'DECIMAL(9,2)', 'unsigned' => false, 'default' => '0.00', 'renamefrom' => 'product_option_values_price'),
            );
            $table_index = array(
                'item_id' => array('fields' => array('item_id')));
            \Cx\Lib\UpdateUtil::table($table_name_old, $table_structure, $table_index);
            \Cx\Lib\UpdateUtil::table_rename($table_name_old, $table_name);
        }

        // LSV
        $table_name = DBPREFIX.'module_shop_lsv';
        $table_structure = array(
            'order_id' => array('type' => 'INT(10)', 'unsigned' => true, 'primary' => true, 'renamefrom' => 'id'),
            'holder' => array('type' => 'tinytext', 'default' => ''),
            'bank' => array('type' => 'tinytext', 'default' => ''),
            'blz' => array('type' => 'tinytext', 'default' => ''),
        );
        $table_index = array();
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        $table_name = DBPREFIX.'module_shop_orders';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true, 'renamefrom' => 'orderid'),
            'customer_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'customerid'),
            'currency_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'selected_currency_id'),
            'shipment_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null, 'renamefrom' => 'shipping_id'),
            'payment_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0'),
            'lang_id' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'customer_lang'),
            'status' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'order_status'),
            'sum' => array('type' => 'DECIMAL(9,2)', 'unsigned' => true, 'default' => '0.00', 'renamefrom' => 'currency_order_sum'),
            'vat_amount' => array('type' => 'DECIMAL(9,2)', 'unsigned' => true, 'default' => '0.00', 'renamefrom' => 'tax_price'),
            'shipment_amount' => array('type' => 'DECIMAL(9,2)', 'unsigned' => true, 'default' => '0.00', 'renamefrom' => 'currency_ship_price'),
            'payment_amount' => array('type' => 'DECIMAL(9,2)', 'unsigned' => true, 'default' => '0.00', 'renamefrom' => 'currency_payment_price'),
// 20111017 Added billing address
            'billing_gender' => array('type' => 'VARCHAR(50)', 'notnull' => false, 'default' => null),
            'billing_company' => array('type' => 'VARCHAR(100)', 'notnull' => false, 'default' => null),
            'billing_firstname' => array('type' => 'VARCHAR(40)', 'notnull' => false, 'default' => null),
            'billing_lastname' => array('type' => 'VARCHAR(100)', 'notnull' => false, 'default' => null),
            'billing_address' => array('type' => 'VARCHAR(40)', 'notnull' => false, 'default' => null),
            'billing_city' => array('type' => 'VARCHAR(50)', 'notnull' => false, 'default' => null),
            'billing_zip' => array('type' => 'VARCHAR(10)', 'notnull' => false, 'default' => null),
            'billing_country_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'billing_phone' => array('type' => 'VARCHAR(20)', 'notnull' => false, 'default' => null),
            'billing_fax' => array('type' => 'VARCHAR(20)', 'notnull' => false, 'default' => null),
            'billing_email' => array('type' => 'VARCHAR(255)', 'notnull' => false, 'default' => null),
            'gender' => array('type' => 'VARCHAR(50)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_prefix'),
            'company' => array('type' => 'VARCHAR(100)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_company'),
            'firstname' => array('type' => 'VARCHAR(40)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_firstname'),
            'lastname' => array('type' => 'VARCHAR(100)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_lastname'),
            'address' => array('type' => 'VARCHAR(40)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_address'),
            'city' => array('type' => 'VARCHAR(50)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_city'),
            'zip' => array('type' => 'VARCHAR(10)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_zip'),
            'country_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_country_id'),
            'phone' => array('type' => 'VARCHAR(20)', 'notnull' => false, 'default' => null, 'renamefrom' => 'ship_phone'),
            'ip' => array('type' => 'VARCHAR(50)', 'default' => '', 'renamefrom' => 'customer_ip'),
            'note' => array('type' => 'TEXT', 'default' => '', 'renamefrom' => 'customer_note'),
            'date_time' => array('type' => 'TIMESTAMP', 'default' => '0000-00-00 00:00:00', 'renamefrom' => 'order_date'),
            'modified_on' => array('type' => 'TIMESTAMP', 'default' => null, 'notnull' => false, 'renamefrom' => 'last_modified'),
            'modified_by' => array('type' => 'VARCHAR(50)', 'notnull' => false, 'default' => null),
        );
        $table_index = array(
            'status' => array('fields' => array('status')));
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

// TODO: TEST
// Migrate present Customer addresses to the new billing address fields.
// Note that this method is also called in Customer::errorHandler() *before*
// any Customer is modified.  Thus, we can safely depend on the old
// Customer table in one way -- if it doesn't exist, all Orders and Customers
// have been successfully migrated already.
        $table_name_customer = DBPREFIX."module_shop_customers";
        if (\Cx\Lib\UpdateUtil::table_exist($table_name_customer)) {
// On the other hand, there may have been an error somewhere in between
// altering the Orders table and moving Customers to the Users table.
// So, to be on the safe side, we will only update Orders where the billing
// address fields are all NULL, as is the case just after the alteration
// of the Orders table above.
// Also note that any inconsistencies involving missing Customer records will
// be left over as-is and may later be handled in the backend.
            $objResult = \Cx\Lib\UpdateUtil::sql("
                SELECT DISTINCT `customer_id`,
                       `customer`.`prefix`,
                       `customer`.`firstname`, `customer`.`lastname`,
                       `customer`.`company`, `customer`.`address`,
                       `customer`.`city`, `customer`.`zip`,
                       `customer`.`country_id`,
                       `customer`.`phone`, `customer`.`fax`,
                       `customer`.`email`
                  FROM `$table_name`
                  JOIN `$table_name_customer` AS `customer`
                    ON `customerid`=`customer_id`
                 WHERE `billing_gender` IS NULL
                   AND `billing_company` IS NULL
                   AND `billing_firstname` IS NULL
                   AND `billing_lastname` IS NULL
                   AND `billing_address` IS NULL
                   AND `billing_city` IS NULL
                   AND `billing_zip` IS NULL
                   AND `billing_country_id` IS NULL
                   AND `billing_phone` IS NULL
                   AND `billing_fax` IS NULL
                   AND `billing_email` IS NULL");
            while ($objResult && !$objResult->EOF) {
                $customer_id = $objResult->fields['customer_id'];
                $gender = 'gender_unknown';
                if (preg_match('/^(?:frau|mad|mme|signora|miss)/i',
                    $objResult->fields['prefix'])) {
                    $gender = 'gender_female';
                } elseif (preg_match('/^(?:herr|mon|signore|mister|mr)/i',
                    $objResult->fields['prefix'])) {
                    $gender = 'gender_male';
                }
                \Cx\Lib\UpdateUtil::sql("
                    UPDATE `$table_name`
                       SET `billing_gender`='".addslashes($gender)."',
                           `billing_company`='".addslashes($objResult->fields['company'])."',
                           `billing_firstname`='".addslashes($objResult->fields['firstname'])."',
                           `billing_lastname`='".addslashes($objResult->fields['lastname'])."',
                           `billing_address`='".addslashes($objResult->fields['address'])."',
                           `billing_city`='".addslashes($objResult->fields['city'])."',
                           `billing_zip`='".addslashes($objResult->fields['zip'])."',
                           `billing_country_id`=".intval($objResult->fields['country_id']).",
                           `billing_phone`='".addslashes($objResult->fields['phone'])."',
                           `billing_fax`='".addslashes($objResult->fields['fax'])."',
                           `billing_email`='".addslashes($objResult->fields['email'])."'
                     WHERE `customer_id`=$customer_id
                       AND `billing_gender` IS NULL
                       AND `billing_company` IS NULL
                       AND `billing_firstname` IS NULL
                       AND `billing_lastname` IS NULL
                       AND `billing_address` IS NULL
                       AND `billing_city` IS NULL
                       AND `billing_zip` IS NULL
                       AND `billing_country_id` IS NULL
                       AND `billing_phone` IS NULL
                       AND `billing_fax` IS NULL
                       AND `billing_email` IS NULL");
                $objResult->MoveNext();
            }
        }

        // Finally, update the migrated Order records with the proper gender
        // strings as used in the User class hierarchy as well
        $objResult = \Cx\Lib\UpdateUtil::sql("
            SELECT `id`, `gender`
              FROM `$table_name`
             WHERE `gender` NOT IN
                   ('gender_male', 'gender_female', 'gender_undefined')");
        while ($objResult && !$objResult->EOF) {
            $gender = 'gender_unknown';
            if (preg_match('/^(?:frau|mad|mme|signora|miss)/i',
                $objResult->fields['gender'])) {
                $gender = 'gender_female';
            } elseif (preg_match('/^(?:herr|mon|signore|mister|mr)/i',
                $objResult->fields['gender'])) {
                $gender = 'gender_male';
            }
            \Cx\Lib\UpdateUtil::sql("
                UPDATE `$table_name`
                   SET `gender`='".addslashes($gender)."'
                 WHERE `id`=".$objResult->fields['id']);
            $objResult->MoveNext();
        }

        // Always
        return false;
    }
}