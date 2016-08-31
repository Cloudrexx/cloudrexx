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
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

/**
 * Payrexx Payment Processor
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
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

        $order = \Cx\Modules\Shop\Controller\Order::getById($_SESSION['shop']['order_id']);

        $payrexx = new \Payrexx\Payrexx($instanceName, $apiSecret);
        $invoice = new \Payrexx\Models\Request\Invoice();
        $invoice->setReferenceId('Shop-' . $order->id());
        $invoice->setTitle($_CONFIG['coreGlobalPageTitle']);
        $invoice->setDescription('&nbsp;');
        // We have to set all known PSPs to support all PSPs.
        // Known PSP are listed on https://payrexx.readme.io/docs/miscellaneous
        $invoice->setPsp(array(2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,20,21,22,23));
        $invoice->setName('Contrexx Shop Order: #' . $_SESSION['shop']['order_id']);
        $invoice->setPurpose('Shop Order #' . $_SESSION['shop']['order_id']);
        $invoice->setAmount(intval($_SESSION['shop']['grand_total_price']*100));
        $invoice->setCurrency(\Cx\Modules\Shop\Controller\Currency::getCodeById($order->currency_id()));
        $invoice->addField('email', false, $order->billing_email());
        $invoice->addField('company', false, $order->billing_company());
        $invoice->addField('forename', false, $order->billing_firstname());
        $invoice->addField('surname', false, $order->billing_lastname());
        $invoice->addField('street', false, $order->billing_address());
        $invoice->addField('postcode', false, $order->billing_zip());
        $invoice->addField('place', false, $order->billing_city());

        try {
            /**
             * @var \Payrexx\Models\Response\Invoice $invoice
             */
            $invoice = $payrexx->create($invoice);
        } catch (\Payrexx\PayrexxException $e) {
            self::$arrError[] = $e->getMessage();
            return false;
        }

        $successPage = \Cx\Core\Routing\Url::fromModuleAndCmd('Shop', 'success');
        $successPageUrl = $successPage->toString();

        try {
            $link = $invoice->getLink() . '&RETURN_URL=' . base64_encode($successPageUrl);
        } catch (\Cx\Core\Routing\UrlException $e) {
            self::$arrError[] = 'Could not find success page for shop module!';
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

        if ($_POST['transaction']['status'] === 'waiting') {
            die(); // we don't want the shop to update the status to cancelled or confirmed
        }

        if ($_POST['transaction']['status'] !== 'confirmed') {
            return false;
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

        $invoice = new \Payrexx\Models\Request\Invoice();
        $invoice->setId($invoiceId);
        try {
            $invoice = $payrexx->getOne($invoice);
        } catch (\Payrexx\PayrexxException $e) {
            return false;
        }

        return $invoice->getStatus() === $_POST['transaction']['status'];
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
