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
 * Payrexx Payment Processor
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

/**
 * Payrexx Payment Processor
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class PayrexxProcessor
{
    /**
     * Error messages
     * @access  public
     * @var     array
     */
    public static $arrError = array();

    /**
     * @return string|boolean
     */
    public static function getModalCode()
    {
        global $_CONFIG;

        $settingDb = \Cx\Core\Setting\Controller\Setting::getArray('Shop', 'config');
        if (empty($settingDb) || !$settingDb['payrexx_active']['value']) {
            self::$arrError[] = "Could not load settings.";
            return false;
        }

        $arrSettings = $settingDb;

        $instanceName = !empty($arrSettings['payrexx_instance_name']['value']) ? $arrSettings['payrexx_instance_name']['value'] : '';
        $apiSecret = !empty($arrSettings['payrexx_api_secret']['value']) ? $arrSettings['payrexx_api_secret']['value'] : '';

        if (empty($instanceName) || empty($apiSecret)) {
            self::$arrError[] = "Wrong Payrexx instance name or Payrexx API secret";
            return false;
        }

        $successPage = \Cx\Core\Routing\Url::fromModuleAndCmd('Shop', 'success');
        $successPageUrl = $successPage->toString();

        $order = \Cx\Modules\Shop\Controller\Order::getById($_SESSION['shop']['order_id']);

        $payrexx = new \Payrexx\Payrexx($instanceName, $apiSecret);
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setReferenceId('Shop-' . $order->id());
        // Known PSP are listed on https://payrexx.readme.io/docs/miscellaneous
        // Let Payrexx set the available PSP automatically
        $gateway->setPsp([]);
        $gateway->setPreAuthorization(false);
        $gateway->setSuccessRedirectUrl($successPageUrl);
        $gateway->setFailedRedirectUrl($successPageUrl);
        $gateway->setAmount(intval(bcmul($_SESSION['shop']['grand_total_price'], 100, 0)));
        $gateway->setCurrency(\Cx\Modules\Shop\Controller\Currency::getCodeById($order->currency_id()));
        $gateway->addField('email', $order->billing_email());
        $gateway->addField('company', $order->billing_company());
        $gateway->addField('forename', $order->billing_firstname());
        $gateway->addField('surname', $order->billing_lastname());
        $gateway->addField('street', $order->billing_address());
        $gateway->addField('postcode', $order->billing_zip());
        $gateway->addField('place', $order->billing_city());
        $gateway->addField('country', \Cx\Core\Country\Controller\Country::getAlpha2ById($order->billing_country_id()));
        $gateway->addField('phone', $order->billing_phone());

        try {
            $response = $payrexx->create($gateway);
            $link = $response->getLink();
        } catch (\Payrexx\PayrexxException $e) {
            self::$arrError[] = $e->getMessage();
            return false;
        }

        \header('Location: ' . $link);
        exit;

        // modal solution, not yet implemented
        $modalJs = \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModuleWebPath() . '/Shop/payments/payrexx/modal.js';
        $jqueryJs = \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseOffsetPath() . '/lib/javascript/jqeury/2.0.2/js/jquery.min.js';
        $code = <<<EOF
<a style="display: none;" class="payrexx-modal-window" href="#" data-href="{PAYREXX_LINK}"></a>
<script type="text/javascript" src= "$jqueryJs"></script>
<script type="text/javascript">
    cx.jQuery = jQuery.noConflict();
</script>
<script type="text/javascript" src= "$modalJs"></script>
<script type="text/javascript">
    cx.ready(function() {
        cx.jQuery(".payrexx-modal-window").payrexxModal({
            hideObjects: ["#contact-details", ".contact"],
            hidden: function (transaction) {
                location.href = "$successPageUrl";
            }
        });
        cx.jQuery(".payrexx-modal-window").click();
    });
</script>
EOF;
        $code = str_replace('{PAYREXX_LINK}', $link, $code);
        return $code;
    }

    /**
     * Verifies the parameters posted back by Payrexx
     * @return boolean True on success, false otherwise
     */
    public static function checkIn()
    {
        if (
            empty($_POST['transaction']['invoice']['paymentRequestId'])
            || empty($_POST['transaction']['status'])
        ) {
            return false;
        }

        /*
            Payrexx knows the following states

            - Order placed (status: waiting)
            - Successful payment processed (status: confirmed) --> Payment was successful
            - Payment aborted by customer (status: cancelled) --> Payment failed
            - Payment declined (status: declined) --> Payment failed
            - Technical error (status: error)

            Additional for cloudrexx non-relevant states:
            - Pre-authorization successful (status: authorized)
            - Payment (partial-) refunded by merchant (status: refunded / partially-refunded)
            - Refund pending (status: refund_pending)
            - Chargeback by card holder (status: chargeback)
        */

        // Return null in any other case than 'confirmed'.
        // This shall enure compatability with payrexx as the gateway sends
        // status notifications of all events including when a card is
        // declined and more.
        if ($_POST['transaction']['status'] !== 'confirmed') {
            return null;
        }

        $invoiceId = $_POST['transaction']['invoice']['paymentRequestId'];

        $arrSettings = \Cx\Core\Setting\Controller\Setting::getArray('Shop', 'config');
        if (empty($arrSettings)) {
            return false;
        }

        $instanceName = !empty($arrSettings['payrexx_instance_name']['value']) ? $arrSettings['payrexx_instance_name']['value'] : '';
        $apiSecret = !empty($arrSettings['payrexx_api_secret']['value']) ? $arrSettings['payrexx_api_secret']['value'] : '';

        if (empty($instanceName) || empty($apiSecret)) {
            return false;
        }
        $payrexx = new \Payrexx\Payrexx($instanceName, $apiSecret);

        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($invoiceId);
        try {
            $response = $payrexx->getOne($gateway);
        } catch (\Payrexx\PayrexxException $e) {
            return false;
        }

        return $response->getStatus() === $_POST['transaction']['status'];
    }


    /**
     * Returns the order id from the request, if present
     *
     * @return  integer     The order id, or false
     */
    public static function getOrderId()
    {
        if (empty($_POST['transaction']['invoice']['referenceId'])) {
            return false;
        }
        $orderId = explode('-', $_POST['transaction']['invoice']['referenceId']);
        if (empty($orderId[1]) || !is_numeric($orderId[1])) {
            return false;
        }
        return $orderId[1];
    }
}
