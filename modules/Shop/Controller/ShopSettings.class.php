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
 * Shop settings
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @author      Reto Kohli <reto.kohli@comvation.com> (parts)
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Shop settings
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @author      Reto Kohli <reto.kohli@comvation.com> (parts)
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */
class ShopSettings
{
    /**
     * This flag is set to true as soon as any changed setting is
     * detected and stored.  Only used by new methods that support it.
     * @var     boolean
     * @access  private
     */
    private static $changed = null;

    /**
     * This flag is set to false as soon as storing any setting fails.
     * Only used by new methods that support it.
     * @var     boolean
     * @access  private
     */
    private static $success = null;


    /**
     * Runs all the methods to store the various settings from the shop
     * admin zone.
     *
     * Note that not all of the methods report their success or failure back
     * here (yet), so you should not rely on the result of this method.
     * @return  mixed               True on success, false on failure,
     *                              null if no change is detected.
     * @static
     */
    static function storeSettings()
    {
        global $_CORELANG;
        self::$success = true;
        self::$changed = false;

        self::storeGeneral();
        self::storeCurrencies();
        self::storePayments();
        self::storeShipping();
        self::storeCountries();
        $result = Zones::store_from_post();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
        self::storeVat();
        if (\Cx\Core\Setting\Controller\Setting::changed()) {
            self::$changed = true;
            if (\Cx\Core\Setting\Controller\Setting::updateAll() === false) {
                return false;
            }
        }
        if (self::$changed) {
            return (self::$success
                ? \Message::ok($_CORELANG['TXT_CORE_SETTING_STORED_SUCCESSFULLY'])
                : \Message::error($_CORELANG['TXT_CORE_SETTING_ERROR_STORING'])
            );
        }
        return null;
    }


    /**
     * Store general settings
     *
     * @return  boolean     true on success, false otherwise.
     * @static
     */
    static function storeGeneral()
    {
        if (empty($_POST['general'])) return;

// TODO: Use
//        \Cx\Core\Setting\Controller\Setting::storeFromPost();

        \Cx\Core\Setting\Controller\Setting::set('email',
            trim(strip_tags(contrexx_input2raw($_POST['email']))));
        \Cx\Core\Setting\Controller\Setting::set('email_confirmation',
            trim(strip_tags(contrexx_input2raw($_POST['email_confirmation']))));
        // added: shop company name and address
        \Cx\Core\Setting\Controller\Setting::set('company',
            trim(strip_tags(contrexx_input2raw($_POST['company']))));
        \Cx\Core\Setting\Controller\Setting::set('address',
            trim(strip_tags(contrexx_input2raw($_POST['address']))));
        \Cx\Core\Setting\Controller\Setting::set('telephone',
            trim(strip_tags(contrexx_input2raw($_POST['telephone']))));
        \Cx\Core\Setting\Controller\Setting::set('fax',
            trim(strip_tags(contrexx_input2raw($_POST['fax']))));
        \Cx\Core\Setting\Controller\Setting::set('country_id', intval($_POST['country_id']));
        // Thumbnail settings
        \Cx\Core\Setting\Controller\Setting::set('thumbnail_max_width', intval($_POST['thumbnail_max_width']));
        \Cx\Core\Setting\Controller\Setting::set('thumbnail_max_height', intval($_POST['thumbnail_max_height']));
        \Cx\Core\Setting\Controller\Setting::set('thumbnail_quality', intval($_POST['thumbnail_quality']));
        // Extended settings
        // New in V2.something
        \Cx\Core\Setting\Controller\Setting::set('weight_enable', !empty($_POST['weight_enable']));
        \Cx\Core\Setting\Controller\Setting::set('show_products_default',
            empty($_POST['show_products_default'])
              ? 0 : intval($_POST['show_products_default']));
        // Mind that this defaults to 1, zero is not a valid value
        \Cx\Core\Setting\Controller\Setting::set('product_sorting',
            empty($_POST['product_sorting'])
              ? 1 : intval($_POST['product_sorting']));
        // Order amount lower limit (new in 3.1.0)
        $orderItemsAmountMin = empty($_POST['orderitems_amount_min'])
            ? 0 : floatval($_POST['orderitems_amount_min']);

        if (!\Cx\Core\Setting\Controller\Setting::isDefined('orderitems_amount_min')){
            \Cx\Core\Setting\Controller\Setting::add('orderitems_amount_min', $orderItemsAmountMin, false,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        }
        else {
            \Cx\Core\Setting\Controller\Setting::set('orderitems_amount_min',$orderItemsAmountMin);
        }

        // Order amount upper limit (applicable when using Saferpay)
        \Cx\Core\Setting\Controller\Setting::set('orderitems_amount_max',
            empty($_POST['orderitems_amount_max'])
                ? 0 : floatval($_POST['orderitems_amount_max']));
        // New in V3.0.0
        \Cx\Core\Setting\Controller\Setting::set('use_js_cart',
            intval($_POST['use_js_cart']));
        \Cx\Core\Setting\Controller\Setting::set('shopnavbar_on_all_pages',
            intval($_POST['shopnavbar_on_all_pages']));
        \Cx\Core\Setting\Controller\Setting::set('register',
            trim(strip_tags(contrexx_input2raw($_POST['register']))));
        \Cx\Core\Setting\Controller\Setting::set('numof_customers_per_page_backend',
            intval($_POST['numof_customers_per_page_backend']));
        \Cx\Core\Setting\Controller\Setting::set('numof_manufacturers_per_page_backend',
            intval($_POST['numof_manufacturers_per_page_backend']));
        \Cx\Core\Setting\Controller\Setting::set('numof_mailtemplate_per_page_backend',
            intval($_POST['numof_mailtemplate_per_page_backend']));
        \Cx\Core\Setting\Controller\Setting::set('usergroup_id_customer',
            intval($_POST['usergroup_id_customer']));
        \Cx\Core\Setting\Controller\Setting::set('usergroup_id_reseller',
            intval($_POST['usergroup_id_reseller']));
        \Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_customer_group_id',
            intval($_POST['user_profile_attribute_customer_group_id']));
        \Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_notes',
            intval($_POST['user_profile_attribute_notes']));
        // New in V3.0.4 or V3.1.0
        if (!\Cx\Core\Setting\Controller\Setting::set('numof_products_per_page_backend',
            intval($_POST['numof_products_per_page_backend']))) {
            \Cx\Core\Setting\Controller\Setting::add('numof_products_per_page_backend',
                intval($_POST['numof_products_per_page_backend']), 53,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        }
        if (!\Cx\Core\Setting\Controller\Setting::set('numof_orders_per_page_backend',
            intval($_POST['numof_orders_per_page_backend']))) {
            \Cx\Core\Setting\Controller\Setting::add('numof_orders_per_page_backend',
                intval($_POST['numof_orders_per_page_backend']), 54,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        }
        if (!\Cx\Core\Setting\Controller\Setting::set('numof_coupon_per_page_backend',
            intval($_POST['numof_coupon_per_page_backend']))) {
            \Cx\Core\Setting\Controller\Setting::add('numof_coupon_per_page_backend',
                intval($_POST['numof_coupon_per_page_backend']), 58,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        }
        if (!\Cx\Core\Setting\Controller\Setting::set('numof_products_per_page_frontend',
            intval($_POST['numof_products_per_page_frontend']))) {
            \Cx\Core\Setting\Controller\Setting::add('numof_products_per_page_frontend',
                intval($_POST['numof_products_per_page_frontend']), null,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        }
        if (!\Cx\Core\Setting\Controller\Setting::set('num_categories_per_row',
            intval($_POST['num_categories_per_row']))) {
            \Cx\Core\Setting\Controller\Setting::add('num_categories_per_row',
                intval($_POST['num_categories_per_row']), null,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        }
        if (!\Cx\Core\Setting\Controller\Setting::set('activate_product_attribute_children',
            !empty($_POST['shop_activate_product_attribute_children']))) {
            \Cx\Core\Setting\Controller\Setting::add('activate_product_attribute_children',
                !empty($_POST['shop_activate_product_attribute_children']), null,
                \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, null, 'config');
        }
        if (!\Cx\Core\Setting\Controller\Setting::set('force_select_option',
            !empty($_POST['shop_force_select_option']))) {
            \Cx\Core\Setting\Controller\Setting::add('force_select_option',
                !empty($_POST['shop_force_select_option']), null,
                \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, null, 'config');
        }
        if (!\Cx\Core\Setting\Controller\Setting::set('verify_account_email',
            !empty($_POST['shop_verify_account_email']))) {
            \Cx\Core\Setting\Controller\Setting::add('verify_account_email',
                !empty($_POST['shop_verify_account_email']), null,
                \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, null, 'config');
        }
    }


    /**
     * Stores the Currencies as present in the POST request
     *
     * See {@see Currency::delete()},
     * {@see Currency::add()}, and
     * {@see Currency::update()}.
     */
    static function storeCurrencies()
    {
//DBG::log("start of storeCurrencies: ".self::$success.", changed: ".self::$changed);
        $result = Currency::delete();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after delete: ".self::$success.", changed: ".self::$changed);
        $result = Currency::add();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after add: ".self::$success.", changed: ".self::$changed);
        $result = Currency::update();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after update: ".self::$success.", changed: ".self::$changed);
        if (self::$changed) {
            // Remember to reinit the Currencies, or the User
            // won't see changes instantly
            Currency::reset();
        }
    }


    /**
     * Stores the Payments as present in the POST request
     *
     * See {@see Payment::delete()},
     * {@see Payment::add()}, and
     * {@see Payment::update()}.
     */
    static function storePayments()
    {
        $result = NULL;
        if (isset ($_GET['delete_payment'])) {
            $payment_id = intval($_GET['delete_payment']);
            $result = Payment::delete($payment_id);
        }
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after Payment::delete: ".self::$success.", changed: ".self::$changed);
        $result = Payment::add();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after Payment::add: ".self::$success.", changed: ".self::$changed);
        $result = Payment::update();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after Payment::update: ".self::$success.", changed: ".self::$changed);
        Payment::reset();
        if (empty ($_POST['bpayment'])) return;
// NOTE: All the following could be handled by Payment::settings()
        // Payrexx
        \Cx\Core\Setting\Controller\Setting::set('payrexx_instance_name',
            trim(strip_tags(contrexx_input2raw($_POST['payrexx_instance_name']))));
        \Cx\Core\Setting\Controller\Setting::set('payrexx_api_secret',
            trim(strip_tags(contrexx_input2raw($_POST['payrexx_api_secret']))));
        \Cx\Core\Setting\Controller\Setting::set('payrexx_active',
            !empty($_POST['payrexx_active']));
        // PostFinance
        \Cx\Core\Setting\Controller\Setting::set('postfinance_shop_id',
            trim(strip_tags(contrexx_input2raw($_POST['postfinance_shop_id']))));
        \Cx\Core\Setting\Controller\Setting::set('postfinance_active',
            !empty($_POST['postfinance_active']));
//        \Cx\Core\Setting\Controller\Setting::set('postfinance_hash_seed',
//            trim(strip_tags(contrexx_input2raw($_POST['postfinance_hash_seed']);
// Replaced by
        \Cx\Core\Setting\Controller\Setting::set('postfinance_hash_signature_in',
            trim(strip_tags(contrexx_input2raw($_POST['postfinance_hash_signature_in']))));
        \Cx\Core\Setting\Controller\Setting::set('postfinance_hash_signature_out',
            trim(strip_tags(contrexx_input2raw($_POST['postfinance_hash_signature_out']))));
        \Cx\Core\Setting\Controller\Setting::set('postfinance_authorization_type',
            trim(strip_tags(contrexx_input2raw($_POST['postfinance_authorization_type']))));
// OBSOLETE -- Determined by the available cards and the PostFinance
// backend settings
//        \Cx\Core\Setting\Controller\Setting::set('postfinance_accepted_payment_methods', $strYellowpayAcceptedPM);
        \Cx\Core\Setting\Controller\Setting::set('postfinance_use_testserver',
            !empty($_POST['postfinance_use_testserver']));
        // Postfinance Mobile
        \Cx\Core\Setting\Controller\Setting::set('postfinance_mobile_webuser',
            trim(strip_tags(contrexx_input2raw($_POST['postfinance_mobile_webuser']))));
        \Cx\Core\Setting\Controller\Setting::set('postfinance_mobile_sign',
            trim(strip_tags(contrexx_input2raw($_POST['postfinance_mobile_sign']))));
        \Cx\Core\Setting\Controller\Setting::set('postfinance_mobile_ijustwanttotest',
            !empty($_POST['postfinance_mobile_ijustwanttotest']));
        \Cx\Core\Setting\Controller\Setting::set('postfinance_mobile_status',
            !empty($_POST['postfinance_mobile_status']));
        // Legacy Saferpay
        \Cx\Core\Setting\Controller\Setting::set('saferpay_id',
            trim(strip_tags(contrexx_input2raw($_POST['saferpay_id']))));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_active',
            !empty($_POST['saferpay_active']));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_finalize_payment',
            !empty($_POST['saferpay_finalize_payment']));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_use_test_account',
            !empty($_POST['saferpay_use_test_account']));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_window_option',
            intval($_POST['saferpay_window_option']));
        // JSON Saferpay
        \Cx\Core\Setting\Controller\Setting::set('saferpay_json_id',
            trim(strip_tags(contrexx_input2raw($_POST['saferpay_json_id']))));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_json_terminal_id',
            trim(strip_tags(contrexx_input2raw($_POST['saferpay_json_terminal_id']))));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_json_user',
            trim(strip_tags(contrexx_input2raw($_POST['saferpay_json_user']))));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_json_pass',
            trim(strip_tags(contrexx_input2raw($_POST['saferpay_json_pass']))));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_json_active',
            !empty($_POST['saferpay_json_active']));
        \Cx\Core\Setting\Controller\Setting::set('saferpay_json_use_test_account',
            !empty($_POST['saferpay_json_use_test_account']));
        // Paypal
        \Cx\Core\Setting\Controller\Setting::set('paypal_account_email',
            trim(strip_tags(contrexx_input2raw($_POST['paypal_account_email']))));
        \Cx\Core\Setting\Controller\Setting::set('paypal_active', !empty($_POST['paypal_active']));
        \Cx\Core\Setting\Controller\Setting::set('paypal_default_currency',
            trim(strip_tags(contrexx_input2raw($_POST['paypal_default_currency']))));
        // Datatrans
        \Cx\Core\Setting\Controller\Setting::set('datatrans_merchant_id',
            trim(strip_tags(contrexx_input2raw($_POST['datatrans_merchant_id']))));
        \Cx\Core\Setting\Controller\Setting::set('datatrans_active', !empty($_POST['datatrans_active']));
        \Cx\Core\Setting\Controller\Setting::set('datatrans_request_type',
            trim(strip_tags(contrexx_input2raw($_POST['datatrans_request_type']))));
        \Cx\Core\Setting\Controller\Setting::set('datatrans_use_testserver', !empty($_POST['datatrans_use_testserver']));
        // Paymill
        \Cx\Core\Setting\Controller\Setting::set('paymill_active',
            !empty($_POST['paymill_active']));
        \Cx\Core\Setting\Controller\Setting::set('paymill_use_test_account', !empty($_POST['paymill_use_test_account']));
        \Cx\Core\Setting\Controller\Setting::set('paymill_test_private_key',
            trim(strip_tags(contrexx_input2raw($_POST['paymill_test_private_key']))));
        \Cx\Core\Setting\Controller\Setting::set('paymill_test_public_key',
            trim(strip_tags(contrexx_input2raw($_POST['paymill_test_public_key']))));
        \Cx\Core\Setting\Controller\Setting::set('paymill_live_private_key',
            trim(strip_tags(contrexx_input2raw($_POST['paymill_live_private_key']))));
        \Cx\Core\Setting\Controller\Setting::set('paymill_live_public_key',
            trim(strip_tags(contrexx_input2raw($_POST['paymill_live_public_key']))));
        // LSV
        \Cx\Core\Setting\Controller\Setting::set('payment_lsv_active', !empty($_POST['payment_lsv_active']));
// All preceding should be handled by Payment::settings()
    }


    /**
     * OBSOLETE
     * Delete currency
    function _deleteCurrency()
    {
        global $objDatabase;

        if (isset($_GET['currencyId']) && !empty($_GET['currencyId'])) {
            $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_currencies WHERE id=".intval($_GET['currencyId'])." AND is_default=0");
        }
    }
     */


    /**
     * Stores any changes made to any shipper or shipment
     */
    static function storeShipping()
    {
        Shipment::init(true);
        // new methods - these set $flagChanged accordingly.
        $result = Shipment::delete_shipper();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after delete_shipper: ".self::$success.", changed: ".self::$changed);
        $result = Shipment::delete_shipment();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after delete_shipment: ".self::$success.", changed: ".self::$changed);
        $result = Shipment::add_shipper();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after add_shipper: ".self::$success.", changed: ".self::$changed);
        $result = Shipment::add_shipments();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after add_shipments: ".self::$success.", changed: ".self::$changed);
        $result = Shipment::update_shipments_from_post();
        if (isset($result)) {
            self::$changed = true;
            self::$success &= $result;
        }
//DBG::log("after update_shipments_from_post: ".self::$success.", changed: ".self::$changed);
        Shipment::reset();
    }


    /**
     * Stores countries settings
     *
     * Returns null if nothing is changed.
     * @return    boolean               True on success, false on failure,
     *                                  or null on noop.
     */
    private static function storeCountries()
    {
        // Skip if not submitted or if the list is empty.
        // At least one Country needs to be active.
        // "list1" contains the active Country IDs
        if (   empty($_POST['countries'])
            || empty($_POST['list1'])) return null;
        $list = contrexx_input2raw($_POST['list1']);
        sort($list);
        $arrCountryIdActive = array_keys(\Cx\Core\Country\Controller\Country::getNameArray(true));
        sort($arrCountryIdActive);
        if ($list == $arrCountryIdActive) return null;
        self::$changed = true;
        $strCountryIdActive = join(',', $list);
        return \Cx\Core\Country\Controller\Country::activate($strCountryIdActive);
    }


    /**
     * Stores all VAT settings
     *
     * Takes all values from the POST array.
     * @static
     */
    static function storeVat()
    {
//DBG::log("start of storeVat: ".self::$success.", changed: ".self::$changed);
        if (empty($_POST['bvat'])) {
//DBG::log("No bvat");
            self::deleteVat();
            self::setProductsVat();
            return;
        }
//DBG::log("Got bvat");

        $result = \Cx\Core\Setting\Controller\Setting::set('vat_number',
            trim(strip_tags(contrexx_input2raw($_POST['vat_number']))));
        if (isset($result)) self::$success &= $result;

//DBG::log("HERE: ".self::$success);
        $result = \Cx\Core\Setting\Controller\Setting::set('vat_default_id',
            intval($_POST['vat_default_id']));
        if (isset($result)) self::$success &= $result;
        $result = \Cx\Core\Setting\Controller\Setting::set('vat_other_id',
            intval($_POST['vat_other_id']));
        if (isset($result)) self::$success &= $result;
        $vat_enabled_home_customer = !empty($_POST['vat_enabled_home_customer']);
        $result = \Cx\Core\Setting\Controller\Setting::set(
            'vat_enabled_home_customer', $vat_enabled_home_customer);
        if (isset($result)) self::$success &= $result;
        if ($vat_enabled_home_customer) {
            $result = \Cx\Core\Setting\Controller\Setting::set('vat_included_home_customer',
                !empty($_POST['vat_included_home_customer']));
            if (isset($result)) self::$success &= $result;
        }
        $vat_enabled_home_reseller = !empty($_POST['vat_enabled_home_reseller']);
        $result = \Cx\Core\Setting\Controller\Setting::set(
            'vat_enabled_home_reseller', $vat_enabled_home_reseller);
        if (isset($result)) self::$success &= $result;
//DBG::log("after set(): ".self::$success.", my changed: ".self::$changed.", \Cx\Core\Setting\Controller\Setting: ".\Cx\Core\Setting\Controller\Setting::changed());
        if ($vat_enabled_home_reseller) {
            $result = \Cx\Core\Setting\Controller\Setting::set('vat_included_home_reseller',
                !empty($_POST['vat_included_home_reseller']));
            if (isset($result)) self::$success &= $result;
        }
        $vat_enabled_foreign_customer = !empty($_POST['vat_enabled_foreign_customer']);
        $result = \Cx\Core\Setting\Controller\Setting::set(
            'vat_enabled_foreign_customer', $vat_enabled_foreign_customer);
        if (isset($result)) self::$success &= $result;
        if ($vat_enabled_foreign_customer) {
            $result = \Cx\Core\Setting\Controller\Setting::set('vat_included_foreign_customer',
                !empty($_POST['vat_included_foreign_customer']));
            if (isset($result)) self::$success &= $result;
        }
        $vat_enabled_foreign_reseller = !empty($_POST['vat_enabled_foreign_reseller']);
        $result = \Cx\Core\Setting\Controller\Setting::set(
            'vat_enabled_foreign_reseller', $vat_enabled_foreign_reseller);
        if (isset($result)) self::$success &= $result;
        if ($vat_enabled_foreign_reseller) {
            $result = \Cx\Core\Setting\Controller\Setting::set('vat_included_foreign_reseller',
                !empty($_POST['vat_included_foreign_reseller']));
            if (isset($result)) self::$success &= $result;
        }
//DBG::log("storeVat(): after \Cx\Core\Setting\Controller\Setting: ".self::$success.", changed: ".self::$changed);
        self::update_vat();
//DBG::log("end of storeVat(): ".self::$success.", changed: ".self::$changed);
        Vat::init();
    }


    /**
     * Delete VAT entry
     *
     * Takes the ID of the record to be deleted from $_GET['vatid']
     * and passes it on the {@link Vat::deleteVat()} static method.
     * @static
     */
    static function deleteVat()
    {
        if (empty($_GET['vatid'])) return;
        self::$changed = true;
        self::$success &= Vat::deleteVat($_GET['vatid']);
//DBG::log("end of deleteVat: ".self::$success.", changed: ".self::$changed);
    }


    /**
     * Add and/or update VAT entries
     *
     * Takes the class and rate of the VAT to be added from the $_POST array
     * variable and passes them on to {@link addVat()}.
     * Takes the IDs, classes and rates of the records to be updated from the
     * $_POST array variable and passes them on to {@link updateVat()}.
     * @static
     */
    static function update_vat()
    {
//DBG::log("update_vat: ".self::$success.", changed: ".self::$changed);
        if (!empty($_POST['vatratenew'])) {
            self::$changed = true;
            self::$success &= Vat::addVat(
                trim(strip_tags(contrexx_input2raw($_POST['vatclassnew']))),
                floatval($_POST['vatratenew']));
        }
//DBG::log("Success: ".self::$success.", changed: ".self::$changed);
        if (!empty($_POST['vatclass'])) {
            $result = Vat::updateVat(
                contrexx_input2raw($_POST['vatclass']), $_POST['vatrate']);
            if (isset($result)) {
                self::$changed = true;
                self::$success &= $result;
            }
        }
//DBG::log("end of update_vat: ".self::$success.", changed: ".self::$changed);
    }


    /**
     * Apply default VAT rate
     *
     * If the get request array field "setVatAll" is present, sets the VAT ID
     * to the ID found therein for all the products.
     * If the get request array field "setVatUnset" is present, sets the VAT ID
     * to the ID found therein for all products having a zero or NULL VAT ID.
     * @todo    Add possibility to choose some products to change,
     *          and add a parameter for this list of IDs
     * @global  ADONewConnection
     * @static
     */
    static function setProductsVat()
    {
        global $objDatabase;

        $vatId = 0;
        $query_where = '';
        if (isset($_GET['setVatAll'])) {
            $vatId = intval($_GET['setVatAll']);
        }
        if (isset($_GET['setVatUnset'])) {
            $vatId = intval($_GET['setVatUnset']);
            $query_where = ' WHERE vat_id IS NULL OR vat_id=0';
        }
        if ($vatId) {
            self::$changed = true;
            self::$success = self::$success && (boolean)$objDatabase->Execute("
                UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_products
                   SET vat_id=$vatId".$query_where
            );
        }
    }


    /**
     * Fixes database errors.
     *
     * Also migrates settings from the old Shop settings table to \Cx\Core\Setting.
     * @return  boolean                 False.  Always.
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
        global $_CONFIGURATION;
// ShopSettings
        \Cx\Core\Setting\Controller\Setting::errorHandler();
        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        $table_name = DBPREFIX.'module_shop_config';
        $i = 0;
        if (\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            // Migrate all entries using the \Cx\Core\Setting\Controller\Setting class
            $query = "
                SELECT `name`, `value`, `status`
                  FROM ".DBPREFIX."module_shop_config
                 ORDER BY `id` ASC";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            if (!$objResult) {
                throw new \Cx\Lib\Update_DatabaseException(
                   'Failed to query old Shop settings', $query);
            }
            while (!$objResult->EOF) {
                $name = $objResult->fields['name'];
                $value = $objResult->fields['value'];
                $status = $objResult->fields['status'];
                $name_status = null;
                switch ($name) {
                  // OBSOLETE
                  case 'tax_default_id':
                  case 'tax_enabled':
                  case 'tax_included':
                  case 'tax_number':
                    // Ignore, do not migrate!
                    $name = null;
                    break;
                  // VALUE ONLY (RE: arrConfig\[.*?\]\[.value.\])
                  case 'confirmation_emails':
                    $name = 'email_confirmation';
                    break;
                  case 'country_id':
                  case 'datatrans_merchant_id':
                  case 'datatrans_request_type':
                    break;
                  case 'datatrans_status':
                    $name = 'datatrans_active';
                    break;
                  case 'datatrans_use_testserver':
                  case 'email':
                  case 'fax':
                  case 'orderitems_amount_max':
                  case 'paypal_default_currency':
                  case 'postfinance_mobile_ijustwanttotest':
                  case 'postfinance_mobile_sign':
                  case 'postfinance_mobile_status':
                  case 'postfinance_mobile_webuser':
                  case 'product_sorting':
                  case 'saferpay_finalize_payment':
                  case 'saferpay_window_option':
                    break;
                  case 'shop_address':
                  case 'shop_company':
                  case 'shop_show_products_default':
                  case 'shop_thumbnail_max_height':
                  case 'shop_thumbnail_max_width':
                  case 'shop_thumbnail_quality':
                  case 'shop_weight_enable':
                    $name = preg_replace('/^shop_/', '', $name);
                    break;
                  case 'telephone':
                  case 'vat_default_id':
                  case 'vat_enabled_foreign_customer':
                  case 'vat_enabled_foreign_reseller':
                  case 'vat_enabled_home_customer':
                  case 'vat_enabled_home_reseller':
                  case 'vat_included_foreign_customer':
                  case 'vat_included_foreign_reseller':
                  case 'vat_included_home_customer':
                  case 'vat_included_home_reseller':
                  case 'vat_number':
                  case 'vat_other_id':
                    break;
                  case 'yellowpay_accepted_payment_methods':
                  case 'yellowpay_authorization_type':
                  case 'yellowpay_hash_seed':
                  case 'yellowpay_hash_signature_in':
                  case 'yellowpay_hash_signature_out':
                  case 'yellowpay_use_testserver':
                    $name = preg_replace('/^yellowpay(.*)$/', 'postfinance$1', $name);
                    break;
                  case 'yellowpay_id':
                    // Obsolete
                    $name = null;
                    break;
                  // VALUE & STATUS
                  case 'paypal_account_email':
                    $name_status = 'paypal_active';
                    break;
                  case 'saferpay_id':
                    $name_status = 'saferpay_active';
                    break;
                  case 'yellowpay_shop_id':
                    $name = 'postfinance_shop_id';
                    $name_status = 'postfinance_active';
                    break;
                  // STATUS ONLY (RE: arrConfig\[.*?\]\[.status.\])
                  case 'payment_lsv_status':
                    $name_status = 'payment_lsv_active';
                    $name = null;
                    break;
                  case 'saferpay_use_test_account':
                    $name_status = $name;
                    $name = null;
                    break;
                }
                if ($name) {
                    if (   \Cx\Core\Setting\Controller\Setting::getValue($name,'Shop') === NULL
                        && !\Cx\Core\Setting\Controller\Setting::add($name, $value, ++$i)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                           "Failed to add \Cx\Core\Setting entry for '$name'");
                    }
                }
                if ($name_status) {
                    if (   \Cx\Core\Setting\Controller\Setting::getValue($name_status,'Shop') === NULL
                        && !\Cx\Core\Setting\Controller\Setting::add($name_status, $status, ++$i)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                           "Failed to add \Cx\Core\Setting entry for status '$name_status'");
                    }
                }
                $objResult->MoveNext();
            }
        }
        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        // Try adding any that just *might* be missing for *any* reason
        \Cx\Core\Setting\Controller\Setting::add('email', 'no-reply@comvation.com', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('email_confirmation', 'no-reply@comvation.com', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('company', 'Comvation AG', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('address', 'Burgstrasse 20', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('country_id', 204, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('telephone', '+4133 2266000', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('fax', '+4133 2266001', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_number', '12345678', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_enabled_foreign_customer', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_enabled_foreign_reseller', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_enabled_home_customer', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_enabled_home_reseller', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_included_foreign_customer', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_included_foreign_reseller', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_included_home_customer', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_included_home_reseller', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_default_id', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('vat_other_id', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('weight_enable', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('show_products_default', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('product_sorting', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN,
            '0:TXT_SHOP_PRODUCT_SORTING_ALPHABETIC,'.
            '1:TXT_SHOP_PRODUCT_SORTING_INDIVIDUAL,'.
            '2:TXT_SHOP_PRODUCT_SORTING_PRODUCTCODE',
            'config');
        \Cx\Core\Setting\Controller\Setting::add('thumbnail_max_width', 140, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('thumbnail_max_height', 140, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('thumbnail_quality', 90, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // Legacy Saferpay
        \Cx\Core\Setting\Controller\Setting::add('saferpay_id', '1234', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_active', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_use_test_account', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_finalize_payment', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_window_option', 2, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // JSON Saferpay
        \Cx\Core\Setting\Controller\Setting::add('saferpay_json_id', '', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_json_terminal_id', '', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_json_user', '', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_json_pass', '', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_json_active', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('saferpay_json_use_test_account', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // Paypal
        \Cx\Core\Setting\Controller\Setting::add('paypal_active', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('paypal_account_email', 'no-reply@comvation.com', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('paypal_default_currency', 'CHF', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // Also see Yellowpay.class
        // Payrexx
        \Cx\Core\Setting\Controller\Setting::add('payrexx_instance_name', 'Instanz Name', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('payrexx_api_secret', 'API Secret', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('payrexx_active', '0', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1');
        // PostFinance
        \Cx\Core\Setting\Controller\Setting::add('postfinance_shop_id', 'Ihr Kontoname', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('postfinance_active', '0', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1');
        \Cx\Core\Setting\Controller\Setting::add('postfinance_authorization_type', 'SAL', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'RES:Reservation,SAL:Verkauf');
// OBSOLETE
        // As it appears that in_array(0, $array) is true for each non-empty
        // $array, indices for the entries must be numbered starting at 1.
//        $arrPayments = array();
//        foreach (self::$arrKnownPaymentMethod as $index => $name) {
//            $arrPayments[$index] = $name;
//        }
//        \Cx\Core\Setting\Controller\Setting::add('postfinance_accepted_payment_methods', '', ++$i,
//                \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOXGROUP,
//                \Cx\Core\Setting\Controller\Setting::joinValues($arrPayments));
        \Cx\Core\Setting\Controller\Setting::add('postfinance_hash_signature_in',
            'Mindestens 16 Buchstaben, Ziffern und Zeichen', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('postfinance_hash_signature_out',
            'Mindestens 16 Buchstaben, Ziffern und Zeichen', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('postfinance_use_testserver', '1', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1');
        \Cx\Core\Setting\Controller\Setting::add('postfinance_mobile_webuser', '1234', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('postfinance_mobile_sign', 'geheimer_schlüssel', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('postfinance_mobile_ijustwanttotest', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('postfinance_mobile_status', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('datatrans_merchant_id', '1234', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // DataTrans
        \Cx\Core\Setting\Controller\Setting::add('datatrans_active', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('datatrans_request_type', 'CAA', ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('datatrans_use_testserver', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // LSV
        \Cx\Core\Setting\Controller\Setting::add('payment_lsv_active', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // New for V3.0
        // Disable jsCart by default.
        $useJsCart = '0';
        // Activate it in case it was activated in config/configuration.php
        if (   isset($_CONFIGURATION['custom']['shopJsCart'])
            && $_CONFIGURATION['custom']['shopJsCart']) {
            $useJsCart = '1';
        }
        \Cx\Core\Setting\Controller\Setting::add('use_js_cart', $useJsCart, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX);
        // Disable shopnavbar on other pages by default.
        $shopnavbar = '0';
        // Activate it in case it was activated in config/configuration.php
        if (   isset($_CONFIGURATION['custom']['shopnavbar'])
            && $_CONFIGURATION['custom']['shopnavbar']) {
            $shopnavbar = '1';
        }
        \Cx\Core\Setting\Controller\Setting::add('shopnavbar_on_all_pages', $shopnavbar, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX);
        // New for v3.1.0
        \Cx\Core\Setting\Controller\Setting::add('orderitems_amount_min', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // New for v2.2(?)
        \Cx\Core\Setting\Controller\Setting::add('orderitems_amount_max', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        // New for v2.3
        \Cx\Core\Setting\Controller\Setting::add('register',
            ShopLibrary::REGISTER_MANDATORY, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN,
            \Cx\Core\Setting\Controller\Setting::joinValues(array(
                ShopLibrary::REGISTER_MANDATORY,
                ShopLibrary::REGISTER_OPTIONAL,
                ShopLibrary::REGISTER_NONE)),
            'config');
        \Cx\Core\Setting\Controller\Setting::add('numof_products_per_page_frontend', 25, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('history_maximum_age_days', 730, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('numof_orders_per_page_frontend', 10, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('numof_orders_per_page_backend', 25, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('numof_customers_per_page_backend', 25, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('numof_manufacturers_per_page_backend', 25, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('numof_mailtemplate_per_page_backend', 25, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('numof_coupon_per_page_backend', 25, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('usergroup_id_customer', 0, 341,
            \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN_USERGROUP, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('usergroup_id_reseller', 0, 342,
            \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN_USERGROUP, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('user_profile_attribute_customer_group_id', 0, 351,
            \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN_USER_CUSTOM_ATTRIBUTE, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('user_profile_attribute_notes', 0, 352,
            \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN_USER_CUSTOM_ATTRIBUTE, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('num_categories_per_row', 4, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'config');


        // New for v5.0.0
        \Cx\Core\Setting\Controller\Setting::add('activate_product_attribute_children', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('force_select_option', 0, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, null, 'config');
        \Cx\Core\Setting\Controller\Setting::add('verify_account_email', 1, ++$i,
            \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, null, 'config');

        // Note that the Settings *MUST* be reinited after adding new entries!

        // Add more new/missing settings here

        \Cx\Lib\UpdateUtil::drop_table($table_name);

        // Always
        return false;
    }

}
