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
 * Mobile Payment
 *
 * Pay your bill using your mobile phone
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

/**
 * Mobile Payment
 *
 * Pay your bill using your mobile phone
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class PostfinanceMobile
{
    /**
     * Error messages
     * @access  public
     * @var     array
     * @static
     */
    private static $arrError = array();


    /**
     * Creates and returns the HTML form for initialising the
     * Postfinance Mobile payment.
     *
     * Fields:
     *  - Mandatory:
     *      currency    ISO 4217 currency code (only CHF for the time being)
     *      amount      Amount in cents (2.50 CHF = 250)
     *      orderid     Unique order ID
     *      webuser     The Mobilesolutions webuser name
     *      sign        SHA-1 Signature
     *      urlsuccess  Target URL after successful payment
     *      urlerror    Target URL after failed payment
     *  - Optional:
     *      customparam     Parameters to be appended to the success or error URL
     *      ijustwanttotest Enables the test mode if present
     * @param   integer   $amount           The order amount in cents
     * @param   integer   $order_id         The order ID
     * @param   string    $customparam      The optional custom parameter(s)
     * @param   boolean   $ijustwanttotest  Enable test mode if true
     * @return  mixed                       The HTML form on success, false
     *                                      otherwise
     * @static
     */
    static function getForm(
        $amount, $order_id, $customparam='', $ijustwanttotest=null
    ) {
        global $_ARRAYLANG, $_CONFIG;

        if (!isset($ijustwanttotest))
            $ijustwanttotest =
                \Cx\Core\Setting\Controller\Setting::getValue('postfinance_mobile_ijustwanttotest','Shop');
        if (empty($amount)) {
            self::$arrError[] = sprintf(
                $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_INVALID_AMOUNT'],
                $amount
            );
            return false;
        }
        if (empty($order_id)) {
            self::$arrError[] = sprintf(
                $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_INVALID_ORDER_ID'],
                $order_id
            );
            return false;
        }
        $currency = \Cx\Modules\Shop\Controller\CurrencyController::getActiveCurrencyCode();
        if (empty($currency)) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_FAILED_TO_DETERMINE_ACTIVE_CURRENCY'];
            return false;
        }
        $webuser = \Cx\Core\Setting\Controller\Setting::getValue('postfinance_mobile_webuser','Shop');
        if (empty($webuser)) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_FAILED_TO_DETERMINE_WEBUSER'];
            return false;
        }
        $sign = \Cx\Core\Setting\Controller\Setting::getValue('postfinance_mobile_sign','Shop');
        if (empty($sign)) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_FAILED_TO_DETERMINE_SIGNATURE'];
            return false;
        }
        $signature = hash_hmac(
            'sha1',
            $amount.$currency.$order_id.$webuser,
            pack('H*', $sign)
        );
        $urlsuccess =
            Cx\Core\Routing\Url::fromModuleAndCmd('Shop', 'success')->toString().
            '?handler=mobilesolutions&amp;result=1'.
            '&amp;order_id='.$order_id;
        $urlerror =
            Cx\Core\Routing\Url::fromModuleAndCmd('Shop', 'success')->toString().
            '?handler=mobilesolutions&amp;result=0'.
            '&amp;order_id='.$order_id;
/*
Live URIs:
https://postfinance.mobilesolutions.ch/webshop/handyzahlung
http://api.smsserv.ch/webshop/handyzahlung

Test URIs:
https://postfinance.mobilesolutions.ch/shoptest/handyzahlung
http://api.smsserv.ch/shoptest/handyzahlung

On the testing environment, use the flag "ijustwanttotest", the mobile
phone number 079 999 99 99, and the security code 12345678 to enforce
a successful payment.  Any other numbers will produce a failed transaction.
*/
        return
            $_ARRAYLANG['TXT_ORDER_LINK_PREPARED']."<br/><br/>\n".
            '<form name="postfinancemobile" method="post" '.
                'action="'.
            ($ijustwanttotest
              ? 'https://postfinance.mobilesolutions.ch/shoptest/handyzahlung'
              : 'https://postfinance.mobilesolutions.ch/webshop/handyzahlung').
// The live link without SSL does not seem to exist (as of 20100226)
//              ? 'http://api.smsserv.ch/shoptest/handyzahlung'
//              : 'http://api.smsserv.ch/webshop/handyzahlung').
            '">'."\n".
            '<input type="hidden" name="currency" value="'.$currency.'" />'."\n".
            '<input type="hidden" name="amount" value="'.$amount.'" />'."\n".
            '<input type="hidden" name="orderid" value="'.$order_id.'" />'."\n".
            '<input type="hidden" name="webuser" value="'.$webuser.'" />'."\n".
            '<input type="hidden" name="sign" value="'.$signature.'" />'."\n".
            '<input type="hidden" name="urlsuccess" value="'.$urlsuccess.'" />'."\n".
            '<input type="hidden" name="urlerror" value="'.$urlerror.'" />'."\n".
            ($customparam
              ? '<input type="hidden" name="customparam" value="'.
                urlencode($customparam).'" />'."\n"
              : '').
            ($ijustwanttotest
              ? '<input type="hidden" name="ijustwanttotest" value="1" />'."\n"
              : '').
            '<input type="submit" name="bsubmit" value="'.
            $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_SUBMIT'].'" />'."\n".
          '</form>'."\n";
    }


    /**
     * Verify the result of the payment
     *
     * Validates the parameters in the fields of the POST request:
     *  - state     Payment status; "success" or "error"
     *  - amount    Amount in cents (2.50 CHF = 250)
     *  - currency  ISO 4217 currency code (CHF)
     *  - orderid   Order ID
     *  - mosoauth  Mobile Solutions Authorisation ID
     *  - postref   PostFinance Reference ID
     *  - sign      SHA-1 Signature
     * @return    integer         The order ID on success, zero otherwise
     *
     */
    static function validateSign()
    {
        global $_ARRAYLANG;

        if (empty($_POST['state'])) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_MISSING_STATE'];
            return false;
        }
        if (empty($_POST['amount'])) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_MISSING_AMOUNT'];
            return false;
        }
        if (empty($_POST['currency'])) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_MISSING_CURRENCY'];
            return false;
        }
        if (empty($_POST['orderid'])) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_MISSING_ORDERID'];
            return false;
        }
        if (empty($_POST['mosoauth'])) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_MISSING_MOSOAUTH'];
            return false;
        }
        if (empty($_POST['postref'])) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_MISSING_POSTREF'];
            return false;
        }
        if (empty($_POST['sign'])) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_MISSING_SIGN'];
            return false;
        }
        $sign     = \Cx\Core\Setting\Controller\Setting::getValue('postfinance_mobile_sign','Shop');
        $state    = $_POST['state'];
        if ($state != 'success') return false;
        $amount   = $_POST['amount'];
        $currency = $_POST['currency'];
        $order_id = $_POST['orderid'];
        $mosoauth = $_POST['mosoauth'];
        $postref  = $_POST['postref'];
        $signature_post = $_POST['sign'];
        $signature_self = hash_hmac(
            'sha1',
            $state.$amount.$currency.$order_id.$mosoauth.$postref,
            pack('H*', $sign)
        );
        if ($signature_post != $signature_self) {
            self::$arrError[] = $_ARRAYLANG['TXT_SHOP_POSTFINANCE_MOBILE_ERROR_INVALID_SIGNATURE'];
            return false;
        }
        return $order_id;
    }


    /**
     * Returns the Order ID from the POST request, if present
     *
     * If the "orderid" index is missing in the $_POST array, returns zero.
     * @return    integer           The Order ID, or zero
     */
    static function getOrderId()
    {
        return (isset($_POST['orderid']) ? $_POST['orderid'] : 0);
    }


    /**
     * Returns the array with error messages
     *
     * Call this when any of the other methods returns boolean false.
     * @return  array                 The error message array
     */
    static function getErrors()
    {
        return self::$arrError;
    }

}
