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
 * Shop Order Helpers
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Shop Order Helpers
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class Orders
{
    const usernamePrefix = 'shop_customer';

    /**
     * Returns an array of Orders for the given parameters
     *
     * See {@see getIdArray()} and {@see getById()} for details.
     * @param   integer   $count      The actual number of Orders returned,
     *                                by reference
     * @param   string    $order      The optional sorting order, SQL syntax
     * @param   array     $filter     The optional array of filter values
     * @param   integer   $offset     The zero based offset for the list
     *                                of Orders returned.
     *                                Defaults to 0 (zero)
     * @param   integer   $limit      The maximum number of Orders to be
     *                                returned.
     *                                Defaults to -1 (no limit)
     * @return  array                 The array of Order objects on success,
     *                                false otherwise
     */
    static function getArray(
        &$count, $order=null, $filter=null, $offset=0, $limit=-1
    ) {
//DBG::log("Orders::getArray(count $count, order $order, filter ".var_export($filter, true).", offset $offset, limit $limit): Entered");

        $arrId = self::getIdArray($count, $order, $filter, $offset, $limit);
//DBG::log("Orders::getArray(): Got IDs: ".var_export($arrId, true));
        $arrOrders = array();
        if (empty ($arrId)) return $arrOrders;
        foreach ($arrId as $id) {
            $objOrder = Order::getById($id);
            if (!$objOrder) {
                --$count;
                continue;
            }
//DBG::log("Orders::getArray(): Got Order: ".var_export($objOrder, true));
            $arrOrders[$id] = $objOrder;
        }
        return $arrOrders;
    }


    /**
     * Returns an array of Order IDs for the given parameters
     *
     * The $filter array may include zero or more of the following field
     * names as indices, plus some value or array of values that will be tested:
     * - id             An Order ID or array of IDs
     * - customer_id    A Customer ID or array of IDs
     * - status         An Order status or array of status
     * - term           An arbitrary search term.  Matched against the fields
     *                  company, firstname, lastname, address, city,
     *                  phone, and email (shipping address).
     * - letter         A letter (or string) that will be matched at the
     *                  beginning of the fields company, firstname, or lastname.
     * Add more fields when needed.
     *
     * The $order parameter value may be one of the table field names plus
     * an optional SQL order direction.
     * Add Backticks to the table and field names as required.
     * $limit defaults to 1000 if it is empty or greater.
     * Note that the array returned is empty if no matching Order is found.
     * @param   integer   $count      The actual number of records returned,
     *                                by reference
     * @param   string    $order      The optional sorting order field,
     *                                SQL syntax. Defaults to 'id ASC'
     * @param   array     $filter     The optional array of filter values
     * @param   integer   $offset     The optional zero based offset for the
     *                                results returned.
     *                                Defaults to 0 (zero)
     * @param   integer   $limit      The optional maximum number of results
     *                                to be returned.
     *                                Defaults to 1000
     * @return  array                 The array of Order IDs on success,
     *                                false otherwise
     */
    static function getIdArray(
        &$count, $order=null, $filter=null, $offset=0, $limit=0
    ) {
        global $objDatabase;
//DBG::activate(DBG_ADODB);
//DBG::log("Order::getIdArray(): Order $order");

        $query_id = "SELECT `order`.`id`";
        $query_count = "SELECT COUNT(*) AS `numof_orders`";
        $query_from = "
              FROM `".DBPREFIX."module_shop_orders` AS `order`";
        $query_where = "
             WHERE 1".
              (empty($filter['id'])
                  ? ''
                  : (is_array($filter['id'])
                      ? " AND `order`.`id` IN (".join(',', $filter['id']).")"
                      : " AND `order`.`id`=".intval($filter['id']))).
              (empty($filter['id>'])
                  ? ''
                  : " AND `order`.`id`>".intval($filter['id>'])).
              (empty($filter['customer_id'])
                  ? ''
                  : (is_array($filter['customer_id'])
                      ? " AND `order`.`customer_id` IN (".
                        join(',', $filter['customer_id']).")"
                      : " AND `order`.`customer_id`=".
                        intval($filter['customer_id']))).
              (empty($filter['status'])
                  ? ''
                  // Include status
                  : (is_array($filter['status'])
                      ? " AND `order`.`status` IN (".join(',', $filter['status']).")"
                      : " AND `order`.`status`=".intval($filter['status']))).
              (empty($filter['!status'])
                  ? ''
                  // Exclude status
                  : (is_array($filter['!status'])
                      ? " AND `order`.`status` NOT IN (".join(',', $filter['!status']).")"
                      : " AND `order`.`status`!=".intval($filter['!status']))).
              (empty($filter['date>='])
                  ? ''
                  : " AND `order`.`date_time`>='".
                    addslashes($filter['date>='])."'");
              (empty($filter['date<'])
                  ? ''
                  : " AND `order`.`date_time`<'".
                    addslashes($filter['date<'])."'");

        if (isset($filter['letter'])) {
            $term = addslashes($filter['letter']).'%';
            $query_where .= "
                AND (   `profile`.`company` LIKE '$term'
                     OR `profile`.`firstname` LIKE '$term'
                     OR `profile`.`lastname` LIKE '$term')";
        }
        if (isset($filter['term'])) {
            $term = '%'.addslashes($filter['term']).'%';
            $query_where .= "
                AND (   `user`.`username` LIKE '$term'
                     OR `user`.`email` LIKE '$term'
                     OR `profile`.`company` LIKE '$term'
                     OR `profile`.`firstname` LIKE '$term'
                     OR `profile`.`lastname` LIKE '$term'
                     OR `profile`.`address` LIKE '$term'
                     OR `profile`.`city` LIKE '$term'
                     OR `profile`.`phone_private` LIKE '$term'
                     OR `profile`.`phone_fax` LIKE '$term'
                     OR `order`.`company` LIKE '$term'
                     OR `order`.`firstname` LIKE '$term'
                     OR `order`.`lastname` LIKE '$term'
                     OR `order`.`address` LIKE '$term'
                     OR `order`.`city` LIKE '$term'
                     OR `order`.`phone` LIKE '$term'
                     OR `order`.`note` LIKE '$term')";
        }

// NOTE: For customized Order IDs
        // Check if the user wants to search the pseudo "account names".
        // These may be customized with pre- or postfixes.
        // Adapt the regex as needed.
//        $arrMatch = array();
//        $searchAccount = '';
//            (preg_match('/^A-(\d{1,2})-?8?(\d{0,2})?/i', $term, $arrMatch)
//                ? "OR (    `order`.`date_time` LIKE '__".$arrMatch[1]."%'
//                       AND `order`.`id` LIKE '%".$arrMatch[2]."')"
//                : ''
//            );

        // Need to join the User for filter and sorting.
        // Note: This might be optimized, so the join only occurs when
        // searching or sorting by Customer name.
        $query_join = "
            LEFT JOIN `".DBPREFIX."access_users` AS `user`
              ON `order`.`customer_id`=`user`.`id`
            LEFT JOIN `".DBPREFIX."access_user_profile` AS `profile`
              ON `user`.`id`=`profile`.`user_id`";
        // The order *SHOULD* contain the direction.  Defaults to DESC here!
        $direction = (preg_match('/\sASC$/i', $order) ? 'ASC' : 'DESC');
        if (preg_match('/customer_name/', $order)) {
            $order =
                "`profile`.`lastname` $direction, ".
                "`profile`.`firstname` $direction";
        }
        $query_order = ($order ? " ORDER BY $order" : '');
        $count = 0;
        // Some sensible hardcoded limit to prevent memory problems
        $limit = intval($limit);
        if ($limit < 0 || $limit > 1000) $limit = 1000;
        // Get the IDs of the Orders according to the offset and limit
//DBG::activate(DBG_ADODB);
        $objResult = $objDatabase->SelectLimit(
            $query_id.$query_from.$query_join.$query_where.$query_order,
            $limit, intval($offset));
//DBG::deactivate(DBG_ADODB);
        if (!$objResult) return Order::errorHandler();
        $arrId = array();
        while (!$objResult->EOF) {
            $arrId[] = $objResult->fields['id'];
            $objResult->MoveNext();
        }
//DBG::log("Order::getIdArray(): limit $limit, count $count, got ".count($arrId)." IDs: ".var_export($arrId, true));
//DBG::deactivate(DBG_ADODB);
        // Get the total count of matching Orders, set $count
        $objResult = $objDatabase->Execute(
            $query_count.$query_from.$query_join.$query_where);
        if (!$objResult) return Order::errorHandler();
        $count = $objResult->fields['numof_orders'];
//DBG::log("Count: $count");
        // Return the array of IDs
        return $arrId;
    }


    /**
     * Sets up the Order list view
     *
     * Sets the $objTemplate parameter to the default backend template,
     * if empty.
     * @param   \Cx\Core\Html\Sigma $objTemplate    The Template, by reference
     * @param   array       $filter                 The optional filter
     * @return  boolean                             True on success,
     *                                              false otherwise
     */
    static function view_list(&$objTemplate=null, $filter=NULL)
    {
        global $_ARRAYLANG, $objInit;

        $backend = ($objInit->mode == 'backend');
        if (!$objTemplate) {
            $objTemplate = new \Cx\Core\Html\Sigma(
                    \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath() . '/Shop/View/Template/Backend');
//DBG::log("Orders::view_list(): new Template: ".$objTemplate->get());
            $objTemplate->loadTemplateFile('module_shop_orders.html');
//DBG::log("Orders::view_list(): loaded Template: ".$objTemplate->get());
        }
        $uri = ($backend
            ? \Html::getRelativeUri_entities()
            : \Cx\Core\Routing\Url::fromModuleAndCmd(
                'Shop', 'history', NULL));
//DBG::log("Orders::view_list(): URI: $uri");
// TODO: Better use a redirect after doing stuff!
        \Html::stripUriParam($uri, 'act');
        \Html::stripUriParam($uri, 'searchterm');
        \Html::stripUriParam($uri, 'listletter');
        \Html::stripUriParam($uri, 'customer_type');
        \Html::stripUriParam($uri, 'status');
        \Html::stripUriParam($uri, 'show_pending_orders');
        \Html::stripUriParam($uri, 'order_id');
        \Html::stripUriParam($uri, 'changeOrderStatus');
        \Html::stripUriParam($uri, 'sendMail');
        if (!is_array($filter)) {
            $filter = array();
        }
        if (!empty($_REQUEST['searchterm'])) {
            $filter['term'] =
                trim(strip_tags(contrexx_input2raw($_REQUEST['searchterm'])));
            \Html::replaceUriParameter($uri, 'searchterm='.$filter['term']);
        } elseif (!empty($_REQUEST['listletter'])) {
            $filter['letter'] =
                trim(strip_tags(contrexx_input2raw($_REQUEST['listletter'])));
            \Html::replaceUriParameter($uri, 'listletter='.$filter['letter']);
        }
        $customer_type = $usergroup_id = null; // Ignore
        if (   isset($_REQUEST['customer_type'])
            && $_REQUEST['customer_type'] !== '') {
            $customer_type = intval($_REQUEST['customer_type']);
            \Html::replaceUriParameter($uri, 'customer_type='.$customer_type);
            if ($customer_type == 0) {
                $usergroup_id = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_customer','Shop');
            }
            if ($customer_type == 1) {
                $usergroup_id = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_reseller','Shop');
            }
            $objFWUser = \FWUser::getFWUserObject();
            $objGroup  = $objFWUser->objGroup->getGroup($usergroup_id);
            if ($objGroup) {
                $filter['customer_id'] = $objGroup->getAssociatedUserIds();
                // No customers of that type, so suppress all results
                if (empty($filter['customer_id']))
                    $filter['customer_id'] = array(0);
//DBG::log("Orders::view_list(): Group ID $usergroup_id, Customers: ".var_export($filter['customer_id'], true));
            }
        }
        $status = null; // Ignore
        $arrStatus = null;
        if (isset($_REQUEST['status'])
         && $_REQUEST['status'] !== '') {
            $status = intval($_REQUEST['status']);
            if (   $status >= Order::STATUS_PENDING
                && $status < Order::STATUS_MAX) {
                $arrStatus = array($status => true);
                \Html::replaceUriParameter($uri, 'status='.$status);
                if ($status == Order::STATUS_PENDING) {
                    $_REQUEST['show_pending_orders'] = true;
                }
            }
        }
        // Let the user choose whether to see pending orders, too
        $show_pending_orders = false;
        if ($backend) {
            if (empty($_REQUEST['show_pending_orders'])) {
                if (empty($arrStatus)) {
                    $arrStatus = self::getStatusArray();
                    unset($arrStatus[Order::STATUS_PENDING]);
                }
            } else {
                if ($arrStatus) {
                    $arrStatus[Order::STATUS_PENDING] = true;
                }
                $show_pending_orders = true;
                \Html::replaceUriParameter($uri, 'show_pending_orders=1');
            }
        }
        if ($arrStatus) {
            $filter['status'] = array_keys($arrStatus);
        }
//DBG::log("Orders::view_list(): URI for Sorting: $uri, decoded ".html_entity_decode($uri));
        $arrSorting = array(
            // Too long
//            'id' => $_ARRAYLANG['TXT_SHOP_ORDER_ID'],
            'id' => $_ARRAYLANG['TXT_SHOP_ID'],
            'date_time' => $_ARRAYLANG['TXT_SHOP_ORDER_DATE'],
//            'name' => $_ARRAYLANG['TXT_SHOP_CUSTOMER'],
            'customer_name' => $_ARRAYLANG['TXT_SHOP_CUSTOMER'],
            'sum' => $_ARRAYLANG['TXT_SHOP_ORDER_SUM'],
            'status' => $_ARRAYLANG['TXT_SHOP_ORDER_STATUS'],
        );
        $objSorting = new \Sorting($uri, $arrSorting, false, 'order_shop_orders');
        $uri_search = $uri;
        \Html::stripUriParam($uri_search, 'searchterm');
        \Html::stripUriParam($uri_search, 'customer_type');
        \Html::stripUriParam($uri_search, 'status');
        \Html::stripUriParam($uri_search, 'show_pending_orders');
        $objTemplate->setGlobalVariable($_ARRAYLANG);
        if ($backend) {
            $txt_order_complete = sprintf(
                $_ARRAYLANG['TXT_SEND_TEMPLATE_TO_CUSTOMER'],
                $_ARRAYLANG['TXT_ORDER_COMPLETE']);
            $objTemplate->setVariable(array(
                'SHOP_SEND_TEMPLATE_TO_CUSTOMER' => $txt_order_complete,
                'SHOP_CUSTOMER_TYPE_MENUOPTIONS' => Customers::getTypeMenuoptions($customer_type, true),
                'SHOP_CUSTOMER_SORT_MENUOPTIONS' => Customers::getSortMenuoptions(
                    $objSorting->getOrderField()),
                'SHOP_SHOW_PENDING_ORDERS_CHECKED' =>
                    ($show_pending_orders ? \Html::ATTRIBUTE_CHECKED : ''),
                'SHOP_ORDER_STATUS_MENUOPTIONS' => self::getStatusMenuoptions($status, true),
            ));
        }
//DBG::log("Orders::view_list(): Order complete: $txt_order_complete");
//DBG::log("Orders::view_list(): URI: $uri");
        $objTemplate->setGlobalVariable(array(
            'SHOP_SEARCH_TERM' => (isset($filter['term'])
                ? $filter['term'] : ''),
            'SHOP_ORDERS_ORDER_NAME' => $objSorting->getOrderParameterName(),
            'SHOP_ORDERS_ORDER_VALUE' => $objSorting->getOrderUriEncoded(),
            'SHOP_ACTION_URI_SEARCH_ENCODED' => $uri_search,
            'SHOP_ACTION_URI_ENCODED' => $uri,
            'SHOP_ACTION_URI' => html_entity_decode($uri),
            'SHOP_CURRENCY', Currency::getDefaultCurrencySymbol()
        ));
        $count = 0;
        $limit = \Cx\Core\Setting\Controller\Setting::getValue('numof_orders_per_page_backend','Shop');
// TODO: Obsolete ASAP
if (!$limit) {
    ShopSettings::errorHandler();
    $limit = 25;
}
        $tries = 2;
        $arrOrders = null;
//\DBG::activate(DBG_DB_FIREPHP);
        while ($tries-- && $count == 0) {
            $arrOrders = self::getArray(
                $count, $objSorting->getOrder(), $filter,
                \Paging::getPosition(), $limit);
            if ($count > 0) break;
            \Paging::reset();
        }
//DBG::deactivate(DBG_DB);
//\DBG::log("Orders: ".count($arrOrders));
        $paging = \Paging::get($uri, $_ARRAYLANG['TXT_ORDERS'],
            $count, $limit, ($count > 0));
        $objTemplate->setVariable(array(
            'SHOP_ORDER_PAGING' => $paging,
            'SHOP_CUSTOMER_LISTLETTER' => (isset($filter['letter'])
                ? $filter['letter'] : ''),
            'SHOP_HEADER_ID' => $objSorting->getHeaderForField('id'),
            'SHOP_HEADER_DATE_TIME' => $objSorting->getHeaderForField('date_time'),
            'SHOP_HEADER_STATUS' => $objSorting->getHeaderForField('status'),
            'SHOP_HEADER_CUSTOMER_NAME' => $objSorting->getHeaderForField('customer_name'),
            'SHOP_HEADER_NOTES' => $_ARRAYLANG['TXT_SHOP_ORDER_NOTES'],
            'SHOP_HEADER_SUM' => $objSorting->getHeaderForField('sum'),
            'SHOP_LISTLETTER_LINKS' => self::getListletterLinks(
                (isset($filter['letter']) ? $filter['letter'] : NULL)),
        ));
        if (empty($arrOrders)) {
//            $objTemplate->hideBlock('orderTable');
            $objTemplate->setVariable(
                'SHOP_ORDER_NONE_FOUND',
                $_ARRAYLANG['TXT_SHOP_ORDERS_NONE_FOUND']);
//\DBG::log("NO Orders!");
            return true;
        }
        $i = 0;
// TODO: For Order export
/*        $min_date = '9999-00-00 00:00:00';
        $max_date = '0000-00-00 00:00:00';
        $min_id = 1e10;
        $max_id = 0;*/
        foreach ($arrOrders as $objOrder) {
            $order_id = $objOrder->id();
            // Custom order ID may be created and used as account name.
            // Adapt the method as needed.
//            $order_id_custom = ShopLibrary::getCustomOrderId(
//                $order_id, $objOrder->date_time()
//            );
            // Take billing address from the Order.
            // No need to load the Customer.
            $customer_name = '';
                $company = $objOrder->billing_company();
                $customer_name = ($company
                    ? $company
                    : $objOrder->billing_lastname().' '.
                      $objOrder->billing_firstname());
            $tipNote = $objOrder->note();
            $tipLink = (empty($tipNote)
                ? ''
                : '<span class="tooltip-trigger icon-comment"></span>'.
                  '<span class="tooltip-message">'.
                  preg_replace('/[\n\r]+/', '<br />',
                      nl2br(contrexx_raw2xhtml($tipNote))).
                  '</span>');
            $status = $objOrder->status();
            $objTemplate->setVariable(array(
                'SHOP_ROWCLASS' => ($status == 0
                    ? 'rowwarn' : 'row'.(++$i % 2 + 1)),
                'SHOP_ORDERID' => $order_id,
                'SHOP_TIP_LINK' => $tipLink,
                'SHOP_DATE' => date(ASCMS_DATE_FORMAT_DATETIME,
                    strtotime($objOrder->date_time())),
                'SHOP_NAME' => $customer_name,
                'SHOP_ORDER_SUM' => Currency::getDefaultCurrencyPrice(
                    $objOrder->sum()),
                'SHOP_ORDER_STATUS' => ($backend
                    ? self::getStatusMenu(
                        intval($status), false, $order_id,
                        'changeOrderStatus('.
                          $order_id.','.$status.',this.value)')
                    : $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_'.$status]),
                'SHOP_ORDER_STATUS_ID' => contrexx_input2int($status),
                // Protected download account validity end date
// TODO (still unused in the view)
//                'SHOP_VALIDITY' => $endDate,
            ));
            $objTemplate->parse('orderRow');
//\DBG::log("Parsed Order ID $order_id");
// TODO: Order export
/*            if ($objOrder->date_time() < $min_date)
                $min_date = $objOrder->date_time();
            if ($objOrder->date_time() > $max_date)
                $max_date = $objOrder->date_time();
            if ($objOrder->id() < $min_id) $min_id = $objOrder->id();
            if ($objOrder->id() > $max_id) $max_id = $objOrder->id();*/
        }
        $objTemplate->setVariable('SHOP_ORDER_PAGING', $paging);
// TODO: Order export
/*        $arrId = range($min_id-1, $max_id);
        $arrId = array(0 => "0") + array_combine($arrId, $arrId);
        $objTemplate->setVariable(array(
            'SHOP_ORDER_EXPORT_LAST_ID_MENUOPTIONS' =>
                Html::getOptions($arrId),
            'SHOP_ORDER_EXPORT_START_DATE' =>
                Html::getDatepicker('start_date', array(
                    'defaultDate' => date(ASCMS_DATE_FORMAT_DATE,
                        strtotime($min_date)),
                    'minDate' => '-7d',
                    'maxDate' => '+0d', )),
            'SHOP_ORDER_EXPORT_END_DATE' =>
                Html::getDatepicker('end_date', array(
                    'defaultDate' => date(ASCMS_DATE_FORMAT_DATE,
                        strtotime($max_date)+86400),
                    'minDate' => '-6d',
                    'maxDate' => '+1d', )),
        ));
//die("Template: ".  nl2br(htmlentities(var_export($objTemplate, true))));
//die("Template: ".  $objTemplate->get());*/
        return true;
    }


    /**
     * Sets up the Order statistics
     * @param   \Cx\Core\Html\Sigma     $objTemplate  The optional Template,
     *                                                by reference
     * @global  ADONewConnection        $objDatabase
     * @global  array                   $_ARRAYLANG
     * @todo    Rewrite the statistics in a seperate class, extending Order
     * @static
     */
    static function view_statistics(&$objTemplate=null)
    {
        global $objDatabase, $_ARRAYLANG;

        if (!$objTemplate || !$objTemplate->blockExists('no_order')) {
            $objTemplate = new \Cx\Core\Html\Sigma(
                    \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath() . '/Shop/View/Template/Backend');
            $objTemplate->loadTemplateFile('module_shop_statistic.html');
        }
        $objTemplate->setGlobalVariable($_ARRAYLANG);
        // Get the first order date; if its empty, no order has been placed yet
        $time_first_order = Order::getFirstOrderTime();
        if (!$time_first_order) {
            $objTemplate->touchBlock('no_order');
            return $objTemplate;
        }
        $year_first_order = date('Y', $time_first_order);
        $month_first_order = date('m', $time_first_order);
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
        $objTemplate->setVariable(array(
            'SHOP_START_MONTH' =>
                Shopmanager::getMonthDropdownMenu($start_month),
            'SHOP_END_MONTH' =>
                Shopmanager::getMonthDropdownMenu($end_month),
            'SHOP_START_YEAR' =>
                Shopmanager::getYearDropdownMenu(
                    $start_year, $year_first_order),
            'SHOP_END_YEAR' =>
                Shopmanager::getYearDropdownMenu(
                    $end_year, $year_first_order),
        ));
        $start_date = date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME,
            mktime(0, 0, 0, $start_month, 1, $start_year));
        // mktime() will fix the month from 13 to 01, see example 2
        // on http://php.net/manual/de/function.mktime.php.
        // Mind that this is exclusive and only used in the queries below
        // so that Order date < $end_date!
        $end_date = date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME,
            mktime(0, 0, 0, $end_month+1, 1, $end_year));
        $selectedStat = (isset($_REQUEST['selectstats'])
                ? intval($_REQUEST['selectstats']) : 0);
        if ($selectedStat == 2) {
            // Product statistic
            $objTemplate->setVariable(array(
                'TXT_COLUMN_1_DESC' => $_ARRAYLANG['TXT_PRODUCT_NAME'],
                'TXT_COLUMN_2_DESC' => $_ARRAYLANG['TXT_COUNT_ARTICLES'],
                'TXT_COLUMN_3_DESC' => $_ARRAYLANG['TXT_STOCK'],
                'SHOP_ORDERS_SELECTED' => '',
                'SHOP_ARTICLES_SELECTED' => \Html::ATTRIBUTE_SELECTED,
                'SHOP_CUSTOMERS_SELECTED' => '',
            ));
            $arrSql = \Text::getSqlSnippets('`B`.`id`', FRONTEND_LANG_ID,
                'Shop', array('title' => Product::TEXT_NAME));
            $query = "
                SELECT A.product_id AS id,
                       A.quantity AS shopColumn2,
                       A.price AS sum,
                       B.stock AS shopColumn3,
                       C.currency_id, ".
                $arrSql['field']."
                  FROM ".DBPREFIX."module_shop".MODULE_INDEX."_order_items AS A
                  JOIN ".DBPREFIX."module_shop".MODULE_INDEX."_orders AS C
                    ON A.order_id=C.id
                  JOIN ".DBPREFIX."module_shop".MODULE_INDEX."_products AS B
                    ON A.product_id=B.id".
                $arrSql['join']."
                 WHERE C.date_time>='$start_date'
                   AND C.date_time<'$end_date'
                   AND (   C.status=".Order::STATUS_CONFIRMED."
                        OR C.status=".Order::STATUS_COMPLETED.")
                 ORDER BY shopColumn2 DESC";
        } elseif ($selectedStat == 3) {
            // Customer statistic
            $objTemplate->setVariable(array(
                'TXT_COLUMN_1_DESC' => $_ARRAYLANG['TXT_NAME'],
                'TXT_COLUMN_2_DESC' => $_ARRAYLANG['TXT_COMPANY'],
                'TXT_COLUMN_3_DESC' => $_ARRAYLANG['TXT_COUNT_ARTICLES'],
                'SHOP_ORDERS_SELECTED' => '',
                'SHOP_ARTICLES_SELECTED' => '',
                'SHOP_CUSTOMERS_SELECTED' => \Html::ATTRIBUTE_SELECTED,
            ));
            $query = "
                SELECT A.sum AS sum,
                       A.currency_id AS currency_id,
                       sum(B.quantity) AS shopColumn3,
                       A.customer_id AS id
                  FROM ".DBPREFIX."module_shop".MODULE_INDEX."_orders AS A
                  JOIN ".DBPREFIX."module_shop".MODULE_INDEX."_order_items AS B
                    ON A.id=B.order_id
                 WHERE A.date_time>='$start_date'
                   AND A.date_time<'$end_date'
                   AND (   A.status=".Order::STATUS_CONFIRMED."
                        OR A.status=".Order::STATUS_COMPLETED.")
                 GROUP BY B.order_id
                 ORDER BY sum DESC";
        } else {
            // Order statistic (default); sales per month
            $objTemplate->setVariable(array(
                'TXT_COLUMN_1_DESC' => $_ARRAYLANG['TXT_DATE'],
                'TXT_COLUMN_2_DESC' => $_ARRAYLANG['TXT_COUNT_ORDERS'],
                'TXT_COLUMN_3_DESC' => $_ARRAYLANG['TXT_COUNT_ARTICLES'],
                'SHOP_ORDERS_SELECTED' => \Html::ATTRIBUTE_SELECTED,
                'SHOP_ARTICLES_SELECTED' => '',
                'SHOP_CUSTOMERS_SELECTED' => '',
            ));
            $query = "
                SELECT SUM(A.quantity) AS shopColumn3,
                       COUNT(A.order_id) AS shopColumn2,
                       B.currency_id,
                       B.sum AS sum,
                       DATE_FORMAT(B.date_time, '%m') AS month,
                       DATE_FORMAT(B.date_time, '%Y') AS year
                  FROM ".DBPREFIX."module_shop".MODULE_INDEX."_order_items AS A,
                       ".DBPREFIX."module_shop".MODULE_INDEX."_orders AS B
                 WHERE A.order_id=B.id
                   AND B.date_time>='$start_date'
                   AND B.date_time<'$end_date'
                   AND (   B.status=".Order::STATUS_CONFIRMED."
                        OR B.status=".Order::STATUS_COMPLETED.")
                 GROUP BY B.id
                 ORDER BY year DESC, month DESC";
        }
        $arrayResults = array();
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return Order::errorHandler();
        }
        $sumColumn3 = $sumColumn4 = 0;
        $sumColumn2 = '';
        if ($selectedStat == 2) {
            // Product statistc
            while (!$objResult->EOF) {
                // set currency id
                Currency::setActiveCurrencyId($objResult->fields['currency_id']);
                $key = $objResult->fields['id'];
                if (!isset($arrayResults[$key])) {
                    $arrayResults[$key] = array(
                        'column1' =>
                            '<a href="index.php?cmd=Shop'.MODULE_INDEX.
                            '&amp;act=products&amp;tpl=manage&amp;id='.
                            $objResult->fields['id'].
                            '" title="'.$objResult->fields['title'].'">'.
                            $objResult->fields['title'].'</a>',
                        'column2' => 0,
                        'column3' => $objResult->fields['shopColumn3'],
                        'column4' => 0,
                    );
                }
                $arrayResults[$key]['column2'] +=
                  + $objResult->fields['shopColumn2'];
                $arrayResults[$key]['column4'] +=
                  + $objResult->fields['shopColumn2']
                  * Currency::getDefaultCurrencyPrice($objResult->fields['sum']);
                $objResult->MoveNext();
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
            while (!$objResult->EOF) {
                Currency::setActiveCurrencyId($objResult->fields['currency_id']);
                $key = $objResult->fields['id'];
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
                            $objResult->fields['id'].'">'.$name.'</a>',
                        'column2' => $company,
                        'column3' => 0,
                        'column4' => 0,
                    );
                }
                $arrayResults[$key]['column3'] += $objResult->fields['shopColumn3'];
                $arrayResults[$key]['column4'] += Currency::getDefaultCurrencyPrice($objResult->fields['sum']);
                $sumColumn3 += $objResult->fields['shopColumn3'];
                $sumColumn4 += Currency::getDefaultCurrencyPrice($objResult->fields['sum']);
                $objResult->MoveNext();
            }
        } else {
            // Order statistic (default)
            $arrayMonths = explode(',', $_ARRAYLANG['TXT_MONTH_ARRAY']);
            while (!$objResult->EOF) {
                $key = $objResult->fields['year'].'.'.$objResult->fields['month'];
                if (!isset($arrayResults[$key])) {
                    $arrayResults[$key] = array(
                        'column1' => '',
                        'column2' => 0,
                        'column3' => 0,
                        'column4' => 0,
                    );
                }
                $arrayResults[$key]['column1'] = $arrayMonths[intval($objResult->fields['month'])-1].' '.$objResult->fields['year'];
                $arrayResults[$key]['column2'] = $arrayResults[$key]['column2'] + 1;
                $arrayResults[$key]['column3'] = $arrayResults[$key]['column3'] + $objResult->fields['shopColumn3'];
                $arrayResults[$key]['column4'] = $arrayResults[$key]['column4'] + Currency::getDefaultCurrencyPrice($objResult->fields['sum']);
                $sumColumn2 = $sumColumn2 + 1;
                $sumColumn3 = $sumColumn3 + $objResult->fields['shopColumn3'];
                $sumColumn4 = $sumColumn4 + Currency::getDefaultCurrencyPrice($objResult->fields['sum']);
                $objResult->MoveNext();
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
                        Currency::formatPrice($entry['column4']).' '.
                        Currency::getDefaultCurrencySymbol(),
                ));
                $objTemplate->parse('statisticRow');
            }
        }

        $query_currency = "
            SELECT currency_id, sum,
                   DATE_FORMAT(date_time, '%m') AS month,
                   DATE_FORMAT(date_time, '%Y') AS year
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_orders
             WHERE status=".Order::STATUS_CONFIRMED."
                OR status=".Order::STATUS_COMPLETED."
             ORDER BY date_time DESC";
        $objResult = $objDatabase->Execute($query_currency);
        if (!$objResult) {
            return Order::errorHandler();
        }
        $totalSoldProducts = 0;
        $query_totalproducts = "
            SELECT sum(A.quantity) AS shopTotalSoldProducts
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_order_items AS A,
                   ".DBPREFIX."module_shop".MODULE_INDEX."_orders AS B
             WHERE A.order_id=B.id
               AND (   B.status=".Order::STATUS_CONFIRMED."
                    OR B.status=".Order::STATUS_COMPLETED.")";
        $objResult = $objDatabase->SelectLimit($query_totalproducts, 1);
        if ($objResult) {
            if (!$objResult->EOF) {
                $totalSoldProducts = $objResult->fields['shopTotalSoldProducts'];
                $objResult->MoveNext();
            }
        }
        $totalOrderSum = 0;
        $totalOrders = 0;
        $bestMonthSum = 0;
        $bestMonthDate = '';
        $arrShopMonthSum = array();
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $orderSum = Currency::getDefaultCurrencyPrice($objResult->fields['sum']);
            if (!isset($arrShopMonthSum[$objResult->fields['year']][$objResult->fields['month']])) {
                $arrShopMonthSum[$objResult->fields['year']][$objResult->fields['month']] = 0;
            }
            $arrShopMonthSum[$objResult->fields['year']][$objResult->fields['month']] += $orderSum;
            $totalOrderSum += $orderSum;
            $totalOrders++;
            $objResult->MoveNext();
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
                Currency::formatPrice($totalOrderSum).' '.
                Currency::getDefaultCurrencySymbol(),
            'SHOP_MONTH' => $bestMonthDate,
            'SHOP_MONTH_SUM' =>
                Currency::formatPrice($bestMonthSum).' '.
                Currency::getDefaultCurrencySymbol(),
            'SHOP_TOTAL_ORDERS' => $totalOrders,
            'SHOP_SOLD_ARTICLES' => $totalSoldProducts,
            'SHOP_SUM_COLUMN_2' => $sumColumn2,
            'SHOP_SUM_COLUMN_3' => $sumColumn3,
            'SHOP_SUM_COLUMN_4' =>
                Currency::formatPrice($sumColumn4).' '.
                Currency::getDefaultCurrencySymbol(),
        ));
        return true;
    }


    /**
     * Returns a string with HTML code for the starting letter links
     * @param   integer     $selected   The optional preselected letter
     * @return  string                  The links HTML code
     */
    static function getListletterLinks($selected=null)
    {
        global $_ARRAYLANG;
//DBG::log("Orders::getListletterLinks($selected)");

        $format = "[ <a href=\"javascript:sendForm('%1\$s')\" title=\"%2\$s\">%2\$s</a> ]\n";
        $links = '';
        $match = false;
        for ($i = 65; $i < 91; ++$i) {
            $letter = chr($i);
            $link = sprintf($format, $letter, $letter);
            if ($selected == $letter) {
                $link = '<b>'.$link.'</b>';
                $match = true;
            }
            $links .= $link;
        }
        $link = sprintf($format, '', $_ARRAYLANG['TXT_SHOP_ALL']);
        if (!$match) {
            $link = '<b>'.$link.'</b>';
        }
        $links .=
            $link.
            \Html::getHidden('listletter', '');
        return $links;

    }


    /**
     * Deletes all Orders with the given Customer ID
     * @param   integer   $customer_id    The Customer ID
     * @return  boolean                   True on success, false otherwise
     */
    static function deleteByCustomerId($customer_id)
    {
        global $_ARRAYLANG;

        $count = 0;
        $arrOrderId = Orders::getIdArray(
            $count, null, array('customer_id' => $customer_id));
        if ($arrOrderId === false) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_CUSTOMER_QUERYING_ORDERS']);
        }
        foreach ($arrOrderId as $order_id) {
            if (!Order::deleteById($order_id)) {
                return \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_CUSTOMER_DELETING_ORDERS']);
            }
        }
        return true;
    }


    /**
     * Updates the Order status with parameter values from the GET request
     * @return  boolean             True on success, false on failure,
     *                              or null (on NOOP)
     * @todo    Should definitely use POST instead.
     */
    static function updateStatusFromGet()
    {
        global $objDatabase, $_ARRAYLANG;

        // Update the order status if valid
        if (   !isset($_GET['changeOrderStatus'])
            || empty($_GET['order_id'])) {
            return null;
        }
        $status = intval($_GET['changeOrderStatus']);
        $order_id = intval($_GET['order_id']);
        if (   $status < Order::STATUS_PENDING
            || $status >= Order::STATUS_MAX
            || $order_id <= 0)
        {
            \Message::error($_ARRAYLANG['TXT_SHOP_ORDER_ERROR_UPDATING_STATUS']);
            \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop&act=orders');
        }
        $objUser = \FWUser::getFWUserObject()->objUser;

        $oldStatus = $objDatabase->getOne('
            SELECT
                `status`
            FROM
                `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_orders`
            WHERE
                `id`=' . contrexx_input2int($order_id));

        $query = "
            UPDATE `".DBPREFIX."module_shop".MODULE_INDEX."_orders`
               SET `status`=$status,
                   `modified_by`='".addslashes($objUser->getUsername())."',
                   `modified_on`='".date('Y-m-d H:i:s')."'
             WHERE `id`=$order_id";
        if (!$objDatabase->Execute($query)) {
            \Message::error($_ARRAYLANG['TXT_SHOP_ORDER_ERROR_UPDATING_STATUS']);
            \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop&act=orders');
        }

        $order = new Order();
        $order->setId($order_id);
        if (   !empty($_GET['stock_update'])
            && $_GET['stock_update'] == 1
            && (
                   $order->isStockIncreasable($oldStatus, $status)
                || $order->isStockDecreasable($oldStatus, $status)
            )
        ) {
            $order->updateStock(
                $order->isStockIncreasable($oldStatus, $status)
            );
        }
        // Send an email to the customer
        if (   !empty($_GET['sendMail'])
            && !empty($_GET['order_id'])) {

            // TODO: It might be useful to move this to its own method:
            $hasMail = false;
            $result = null;
            switch ($status) {
                case Order::STATUS_CONFIRMED:
                    $result = ShopLibrary::sendConfirmationMail($_GET['order_id']);
                    $hasMail = true;
                    break;
                case Order::STATUS_COMPLETED:
                    $result = ShopManager::sendProcessedMail($_GET['order_id']);
                    $hasMail = true;
                    break;
            }
            if ($hasMail) {
                if (!empty($result)) {
                    \Message::ok(sprintf($_ARRAYLANG['TXT_EMAIL_SEND_SUCCESSFULLY'],
                        $result));
                } else {
                    \Message::error($_ARRAYLANG['TXT_MESSAGE_SEND_ERROR']);
                }
            }
        }
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop&act=orders');
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
     * @access  private
     * @static
     * @param   integer $order_id    The ID of the current order
     * @param   integer $newOrderStatus The optional new order status.
     * @param   string  $handler    The Payment type name in use
     * @return  integer             The new order status (may be zero)
     *                              if the order status can be changed
     *                              accordingly, zero otherwise
     */
    static function update_status($order_id, $newOrderStatus=0, $handler=NULL)
    {
        global $objDatabase, $_ARRAYLANG;

        if (is_null($handler) && isset($_REQUEST['handler'])) {
            $handler = contrexx_input2raw($_REQUEST['handler']);
        }
        $order_id = intval($order_id);
        if ($order_id == 0) {
            return Order::STATUS_CANCELLED;
        }
        $query = "
            SELECT status, payment_id, shipment_id
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_orders
             WHERE id=$order_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) {
            return Order::STATUS_CANCELLED;
        }
        $status = $objResult->fields['status'];
        // Never change a non-pending status!
        // Whether a payment was successful or not, the status must be
        // left alone.
        if ($status != Order::STATUS_PENDING) {
            // The status of the order is not pending.
            // This may be due to a wrong order ID, a page reload,
            // or a PayPal IPN that has been received already.
            // No order status is changed automatically in these cases!
            // Leave it as it is.
            return $status;
        }
        // Determine and verify the payment handler
        $payment_id = $objResult->fields['payment_id'];
//if (!$payment_id) DBG::log("update_status($order_id, $newOrderStatus): Failed to find Payment ID for Order ID $order_id");
        $processor_id = Payment::getPaymentProcessorId($payment_id);
//if (!$processor_id) DBG::log("update_status($order_id, $newOrderStatus): Failed to find Processor ID for Payment ID $payment_id");
        $processorName = PaymentProcessing::getPaymentProcessorName($processor_id);
//if (!$processorName) DBG::log("update_status($order_id, $newOrderStatus): Failed to find Processor Name for Processor ID $processor_id");
        // The payment processor *MUST* match the handler returned.
        if (!preg_match("/^$handler/i", $processorName)) {
//DBG::log("update_status($order_id, $newOrderStatus): Mismatching Handlers: Order $processorName, Request ".$_GET['handler']);
            return Order::STATUS_CANCELLED;
        }
        // Only if the optional new order status argument is zero,
        // determine the new status automatically.
        if ($newOrderStatus == Order::STATUS_PENDING) {
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
            $newOrderStatus = Order::STATUS_CONFIRMED;
            $processorType =
                PaymentProcessing::getCurrentPaymentProcessorType($processor_id);
            $shipmentId = $objResult->fields['shipment_id'];
            if ($processorType == 'external') {
                // External payment types are considered instant.
                // See $_SESSION['shop']['isInstantPayment'].
                if ($shipmentId == 0) {
                    // instant, download -> completed
                    $newOrderStatus = Order::STATUS_COMPLETED;
                } else {
                    // There is a shipper, so this order will bedelivered.
                    // See $_SESSION['shop']['isDelivery'].
                    // instant, delivery -> paid
                    $newOrderStatus = Order::STATUS_PAID;
                }
            } else {
                // Internal payment types are considered deferred.
                if ($shipmentId == 0) {
                    // deferred, download -> shipped
                    $newOrderStatus = Order::STATUS_SHIPPED;
                }
                //else { deferred, delivery -> confirmed }
            }
        }
        $query = "
            UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_orders
               SET status='$newOrderStatus'
             WHERE id=$order_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            // The query failed, but all the data is okay.
            // Don't cancel the order, leave it as it is and let the shop
            // manager handle this.  Return pending status.
            return Order::STATUS_PENDING;
        }
        if (   $newOrderStatus == Order::STATUS_CONFIRMED
            || $newOrderStatus == Order::STATUS_PAID
            || $newOrderStatus == Order::STATUS_SHIPPED
            || $newOrderStatus == Order::STATUS_COMPLETED) {
            if (!ShopLibrary::sendConfirmationMail($order_id)) {
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
     * Returns a dropdown menu string with all available order status.
     *
     * If $flagFilter is true, an additional null element is added.
     * This is intended to indicate an empty filter value only,
     * not as an Order status to be applied!
     * @param   string  $selected       Optional selected status
     * @param   boolean $flagFilter     Add null elememt if true
     * @param   string  $menuName       Optional menu name
     * @param   string  $onchange       Optional onchange callback function
     * @return  string  $menu           The dropdown menu string
     * @global  array
     */
    static function getStatusMenu(
        $selected='', $flagFilter=false, $order_id=null, $onchange=''
    ) {
        return ($order_id
            ? \Html::getSelect(
                'order_status['.$order_id.']', self::getStatusArray($flagFilter),
                $selected, 'order_status-'.$order_id, $onchange)
            : \Html::getSelect(
                'order_status', self::getStatusArray($flagFilter),
                $selected, 'order_status', $onchange));
    }


    /**
     * Returns the HTML menu options for selecting an order status
     *
     * Adds a "Status" header option with empty string value
     * if the $flagFilter parameter is true.
     * @param   string      $selected       The value of the preselected status
     * @param   boolean     $flagFilter     If true, the header option is added
     * @return  string                      The HTML menu options string
     */
    static function getStatusMenuoptions($selected='', $flagFilter=false)
    {
        return \Html::getOptions(self::getStatusArray($flagFilter), $selected);
    }


    /**
     * Returns the array of Order status strings, indexed by the status values
     *
     * Adds a "Status" null element with empty string value index
     * if the $flagFilter parameter is true.
     * @param   boolean     $flagFilter     If true, the null element is added
     * @return  string                      The status array
     */
    static function getStatusArray($flagFilter=false)
    {
        global $_ARRAYLANG;

        $arrStatus = ($flagFilter
            ? array('' => $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_PLEASE_CHOOSE'])
            : array());
        for ($i = Order::STATUS_PENDING; $i < Order::STATUS_MAX; ++$i) {
            $arrStatus[$i] =
                $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_'.$i];
        }
        return $arrStatus;
    }


    /**
     * Returns an array with all placeholders and their values to be
     * replaced in any shop mailtemplate for the given order ID.
     *
     * You only have to set the 'substitution' index value of your MailTemplate
     * array to the array returned.
     * Customer data is not included here.  See {@see Customer::getSubstitutionArray()}.
     * Note that this method is now mostly independent of the current session.
     * The language of the mail template is determined by the browser
     * language range stored with the order.
     * @access  private
     * @static
     * @param   integer $order_id     The order ID
     * @param   boolean $create_accounts  If true, creates User accounts
     *                                    and Coupon codes.  Defaults to true
     * @param   boolean $updateStock  If true, decreases stock according to order.
     *                                Defaults to true
     * @return  array                 The array with placeholders as keys
     *                                and values from the order on success,
     *                                false otherwise
     */
    static function getSubstitutionArray($order_id, $create_accounts=true, $updateStock=true)
    {
        global $_ARRAYLANG;
/*
            $_ARRAYLANG['TXT_SHOP_URI_FOR_DOWNLOAD'].":\r\n".
            'http://'.$_SERVER['SERVER_NAME'].
            "/index.php?section=download\r\n";
*/
        $objOrder = Order::getById($order_id);
        if (!$objOrder) {
            // Order not found
            return false;
        }
        $lang_id = $objOrder->lang_id();
        $status = $objOrder->status();
        $customer_id = $objOrder->customer_id();
        $customer = Customer::getById($customer_id);
        $payment_id = $objOrder->payment_id();
        $shipment_id = $objOrder->shipment_id();
        $arrSubstitution = array (
            'CUSTOMER_COUNTRY_ID' => $objOrder->billing_country_id(),
            'LANG_ID' => $lang_id,
            'NOW' => date(ASCMS_DATE_FORMAT_DATETIME),
            'TODAY' => date(ASCMS_DATE_FORMAT_DATE),
//            'DATE' => date(ASCMS_DATE_FORMAT_DATE, strtotime($objOrder->date_time())),
            'ORDER_ID' => $order_id,
            'ORDER_ID_CUSTOM' => ShopLibrary::getCustomOrderId($order_id),
// TODO: Use proper localized date formats
            'ORDER_DATE' =>
                date(ASCMS_DATE_FORMAT_DATE,
                    strtotime($objOrder->date_time())),
            'ORDER_TIME' =>
                date(ASCMS_DATE_FORMAT_TIME,
                    strtotime($objOrder->date_time())),
            'ORDER_STATUS_ID' => $status,
            'ORDER_STATUS' => $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_'.$status],
            'MODIFIED' =>
                date(ASCMS_DATE_FORMAT_DATETIME,
                    strtotime($objOrder->modified_on())),
            'REMARKS' => $objOrder->note(),
            'ORDER_SUM' => sprintf('% 9.2f', $objOrder->sum()),
            'CURRENCY' => Currency::getCodeById($objOrder->currency_id()),
        );
        $arrSubstitution += $customer->getSubstitutionArray();
        if ($shipment_id) {
            $arrSubstitution += array (
                'SHIPMENT' => array(0 => array(
                    'SHIPMENT_NAME' => sprintf('%-40s', Shipment::getShipperName($shipment_id)),
                    'SHIPMENT_PRICE' => sprintf('% 9.2f', $objOrder->shipment_amount()),
                )),
// Unused
//                'SHIPMENT_ID' => $objOrder->shipment_id(),
                'SHIPPING_ADDRESS' => array(0 => array(
                    'SHIPPING_COMPANY' => $objOrder->company(),
                    'SHIPPING_TITLE' =>
                        $_ARRAYLANG['TXT_SHOP_'.strtoupper($objOrder->gender())],
                    'SHIPPING_FIRSTNAME' => $objOrder->firstname(),
                    'SHIPPING_LASTNAME' => $objOrder->lastname(),
                    'SHIPPING_ADDRESS' => $objOrder->address(),
                    'SHIPPING_ZIP' => $objOrder->zip(),
                    'SHIPPING_CITY' => $objOrder->city(),
                    'SHIPPING_COUNTRY_ID' => $objOrder->country_id(),
                    'SHIPPING_COUNTRY' => \Cx\Core\Country\Controller\Country::getNameById(
                        $objOrder->country_id()),
                    'SHIPPING_PHONE' => $objOrder->phone(),
                )),
            );
        }
        if ($payment_id) {
            $arrSubstitution += array (
                'PAYMENT' => array(0 => array(
                    'PAYMENT_NAME' => sprintf('%-40s', Payment::getNameById($payment_id)),
                    'PAYMENT_PRICE' => sprintf('% 9.2f', $objOrder->payment_amount()),
                )),
            );
        }
        $arrItems = $objOrder->getItems(false);
        if (!$arrItems) {
            \Message::warning($_ARRAYLANG['TXT_SHOP_ORDER_WARNING_NO_ITEM']);
        }
        // Deduct Coupon discounts, either from each Product price, or
        // from the items total.  Mind that the Coupon has already been
        // stored with the Order, but not redeemed yet.  This is done
        // in this method, but only if $create_accounts is true.
        $coupon_code = NULL;
        $coupon_amount = 0;
        $objCoupon = Coupon::getByOrderId($order_id);
        if ($objCoupon) {
            $coupon_code = $objCoupon->code();
        }
        $orderItemCount = 0;
        $total_item_price = 0;
        // Suppress Coupon messages (see Coupon::available())
        \Message::save();
        foreach ($arrItems as $item) {
            $product_id = $item['product_id'];
            $objProduct = Product::getById($product_id);
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
                foreach ($item['attributes'] as $attribute_name => $arrAttribute) {
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
                        $option_name_stripped = ShopLibrary::stripUniqidFromFilename($option_name);
                        $path = Order::UPLOAD_FOLDER.$option_name;
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
                                Currency::formatPrice($option_price).
                                ' '.Currency::getActiveCurrencyCode()
//                                .')'
                                ;
                            $option['PRODUCT_OPTIONS_PRICE'] = Currency::formatPrice($option_price);
                            $option['PRODUCT_OPTIONS_CURRENCY'] = Currency::getActiveCurrencyCode();
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
                'PRODUCT_TOTAL_PRICE' => sprintf('% 9.2f', $item_price*$quantity),
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
                            'firstname' => array(0 => $arrSubstitution['CUSTOMER_FIRSTNAME']),
                            'lastname' => array(0 => $arrSubstitution['CUSTOMER_LASTNAME']),
                            'company' => array(0 => $arrSubstitution['CUSTOMER_COMPANY']),
                            'address' => array(0 => $arrSubstitution['CUSTOMER_ADDRESS']),
                            'zip' => array(0 => $arrSubstitution['CUSTOMER_ZIP']),
                            'city' => array(0 => $arrSubstitution['CUSTOMER_CITY']),
                            'country' => array(0 => $arrSubstitution['CUSTOMER_COUNTRY_ID']),
                            'phone_office' => array(0 => $arrSubstitution['CUSTOMER_PHONE']),
                            'phone_fax' => array(0 => $arrSubstitution['CUSTOMER_FAX']),
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
                        $code = Coupon::getNewCode();
//DBG::log("Orders::getSubstitutionArray(): Got code: $code, calling Coupon::addCode($code, 0, 0, 0, $item_price)");
                        Coupon::storeCode(
                            $code, 0, 0, 0, $item_price, 0, 0, 1e10, true);
                        $arrProduct['COUPON_DATA'][] = array(
                            'COUPON_CODE' => $code
                        );
                    }
                }
                // Redeem the *product* Coupon, if possible for the Product
                if ($coupon_code) {
                    $objCoupon = Coupon::available($coupon_code,
                        $item_price*$quantity, $customer_id, $product_id,
                        $payment_id);
                    if ($objCoupon) {
                        $coupon_code = NULL;
                        $coupon_amount = $objCoupon->getDiscountAmount(
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
            $objCoupon = Coupon::available($coupon_code,
                $total_item_price, $customer_id, null, $payment_id);
            if ($objCoupon) {
                $coupon_amount = $objCoupon->getDiscountAmount(
                    $total_item_price, $customer_id);
                if ($create_accounts) {
                    $objCoupon->redeem($order_id, $customer_id, $total_item_price);
                }
            }
        }
        \Message::restore();
        // Fill in the Coupon block with proper discount and amount
        if ($objCoupon) {
            $coupon_code = $objCoupon->code();
//\DBG::log("Orders::getSubstitutionArray(): Coupon $coupon_code, amount $coupon_amount");
        }
        if ($coupon_amount) {
//\DBG::log("Orders::getSubstitutionArray(): Got Order Coupon $coupon_code");
            $arrSubstitution['DISCOUNT_COUPON'][] = array(
                'DISCOUNT_COUPON_CODE' => sprintf('%-40s', $coupon_code),
                'DISCOUNT_COUPON_AMOUNT' => sprintf('% 9.2f', -$coupon_amount),
            );
        } else {
//\DBG::log("Orders::getSubstitutionArray(): No Coupon for Order ID $order_id");
        }
        if (Vat::isEnabled()) {
//DBG::log("Orders::getSubstitutionArray(): VAT amount: ".$objOrder->vat_amount());
            $arrSubstitution['VAT'] = array(0 => array(
                'VAT_TEXT' => sprintf('%-40s',
                    (Vat::isIncluded()
                        ? $_ARRAYLANG['TXT_SHOP_VAT_PREFIX_INCL']
                        : $_ARRAYLANG['TXT_SHOP_VAT_PREFIX_EXCL']
                    )),
                'VAT_PRICE' => $objOrder->vat_amount(),
            ));
        }
        return $arrSubstitution;
    }

}
