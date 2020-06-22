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
 * Interface for the PayPal payment service provider
 * @link https://www.paypal.com/ch/cgi-bin/webscr?cmd=_pdn_howto_checkout_outside
 * @link https://www.paypal.com/ipn
 * @author Stefan Heinemannn <stefan.heinemann@comvation.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 */

/**
 * Debug mode
 * @internal
    Debug modes:
    0   No debugging, normal operation
    1   Use PayPal Sandbox, create log files
    2   Use test suite, create log files
*/
define('_PAYPAL_DEBUG', 0);

/**
 * Interface for the PayPal payment service provider
 * @link https://www.paypal.com/ch/cgi-bin/webscr?cmd=_pdn_howto_checkout_outside
 * @link https://www.paypal.com/ipn
 * @author Stefan Heinemannn <stefan.heinemann@comvation.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class PayPal
{
    /**
     * Currency codes accepted by PayPal
     *
     * Mind that both key and value are required by the methods below.
     * @var     array
     */
    private static $arrAcceptedCurrencyCode = array(
        'AUD' => 'AUD', // Australian Dollar
        'CAD' => 'CAD', // Canadian Dollar
        'CHF' => 'CHF', // Swiss Franc
        'CZK' => 'CZK', // Czech Koruna
        'DKK' => 'DKK', // Danish Krone
        'EUR' => 'EUR', // Euro
        'GBP' => 'GBP', // British Pound
        'HKD' => 'HKD', // Hong Kong Dollar
        'HUF' => 'HUF', // Hungarian Forint
        'JPY' => 'JPY', // Japanese Yen
        'NOK' => 'NOK', // Norwegian Krone
        'NZD' => 'NZD', // New Zealand Dollar
        'PLN' => 'PLN', // Polish Zloty
        'SEK' => 'SEK', // Swedish Krona
        'SGD' => 'SGD', // Singapore Dollar
        'THB' => 'THB', // Thai Baht
        'USD' => 'USD', // U.S. Dollar
// 20120601 New supported currencies:
        'ILS' => 'ILS', // Israeli New Shekel
        'MXN' => 'MXN', // Mexican Peso
        'PHP' => 'PHP', // Philippine Peso
        'TWD' => 'TWD', // New Taiwan Dollar
// Note that the following are only supported by accounts
// in the respective countries and must be enabled here:
//        'BRL' => 'BRL', // Brazilian Real (only for Brazilian members)
//        'MYR' => 'MYR', // Malaysian Ringgit (only for Malaysian members)
//        'TRY' => 'TRY', // Turkish Lira (only for Turkish members)
    );


    /**
     * Returns the PayPal form for initializing the payment process
     * @param   string  $account_email  The PayPal account e-mail address
     * @param   string  $order_id       The Order ID
     * @param   string  $currency_code  The Currency code
     * @param   string  $amount         The amount
     * @param   string  $item_name      The description used for the payment
     * @return  string                  The HTML code for the PayPal form
     */
    static function getForm($account_email, $order_id, $currency_code,
        $amount, $item_name)
    {
        global $_ARRAYLANG;

//DBG::log("getForm($account_email, $order_id, $currency_code, $amount, $item_name): Entered");
        $return = \Cx\Core\Routing\Url::fromModuleAndCmd(
            'Shop', 'success', FRONTEND_LANG_ID, array(
                'handler' => 'paypal',
                'result' => '1',
                'order_id' => $order_id,
            ))->toString();
        $cancel_return = \Cx\Core\Routing\Url::fromModuleAndCmd(
            'Shop', 'success', FRONTEND_LANG_ID, array(
                'handler' => 'paypal',
                'result' => '2',
                'order_id' => $order_id,
            ))->toString();
        $notify_url = \Cx\Core\Routing\Url::fromModuleAndCmd(
            'Shop', 'success', FRONTEND_LANG_ID, array(
                'handler' => 'paypal',
                'result' => '-1',
                'order_id' => $order_id,
            ))->toString();
        $retval = (\Cx\Core\Setting\Controller\Setting::getValue('paypal_active','Shop')
            ? '<script type="text/javascript">
// <![CDATA[
function go() { document.paypal.submit(); }
window.setTimeout("go()", 3000);
// ]]>
</script>
<form name="paypal" method="post"
      action="https://www.paypal.com/ch/cgi-bin/webscr">
' :
'<form name="paypal" method="post"
      action="https://www.sandbox.paypal.com/ch/cgi-bin/webscr">
').
            Html::getHidden('cmd', '_xclick').
            Html::getHidden('business', $account_email).
            Html::getHidden('item_name', $item_name).
            Html::getHidden('currency_code', $currency_code).
            Html::getHidden('amount', $amount).
            Html::getHidden('custom', $order_id).
            Html::getHidden('notify_url', $notify_url).
            Html::getHidden('return', $return).
            // PayPal does no longer use 'return' by default for successfull payments.
            // Instead is uses 'cancel_return'. Therefore we simply set the success-link
            // as cancel-link
            Html::getHidden('cancel_return', $return).
            $_ARRAYLANG['TXT_PAYPAL_SUBMIT'].'<br /><br />'.
            '<input type="submit" name="submitbutton" value="'.
            $_ARRAYLANG['TXT_PAYPAL_SUBMIT_BUTTON'].
            "\" />\n</form>\n";
        return $retval;
    }


    /**
     * Returns the Order ID taken from either the "custom" or "order_id"
     * parameter value, in that order
     *
     * If none of these parameters is present in the $_REQUEST array,
     * returns false.
     * @return  mixed               The Order ID on success, false otherwise
     */
    static function getOrderId()
    {
        return (isset($_REQUEST['custom'])
          ? intval($_REQUEST['custom'])
          : (isset($_REQUEST['order_id'])
              ? intval($_REQUEST['order_id'])
              : false));
    }


    /**
     * This method is called whenever the IPN from PayPal is received
     *
     * The data from the IPN is verified and answered.  After that,
     * PayPal must reply again with either the "VERIFIED" or "INVALID"
     * keyword.
     * All parameter values are optional.  Any that are non-empty are
     * compared to their respective counterparts received in the post
     * from PayPal.  The verification fails if any comparison fails.
     * You should consider the payment as failed whenever an empty
     * (false or NULL) value is returned.  The latter is intended for
     * diagnostic purposes only, but will never be returned on success.
     * @param   string  $amount         The optional amount
     * @param   string  $currency       The optional currency code
     * @param   string  $order_id       The optional  order ID
     * @param   string  $customer_email The optional customer e-mail address
     * @param   string  $account_email  The optional PayPal account e-mail
     * @return  boolean                 True on successful verification,
     *                                  false on failure, or NULL when
     *                                  an arbitrary result is received.
     */
    static function ipnCheck($amount=NULL, $currency=NULL, $order_id=NULL,
        $customer_email=NULL, $account_email=NULL)
    {
        global $objDatabase;

//DBG::log("ipnCheck($amount, $currency, $order_id, $customer_email, $account_email): Entered");
//DBG::log("Paypal::ipnCheck(): Checking POST");
        if (   empty ($_POST['mc_gross'])
            || empty ($_POST['mc_currency'])
            || empty ($_POST['custom'])
            || empty ($_POST['payer_email'])
            || empty ($_POST['business'])) {
//DBG::log("Paypal::ipnCheck(): Incomplete IPN parameter values:");
//DBG::log(var_export($_POST, true));
            return false;
        }
        // Copy the post from PayPal and prepend 'cmd'
        $encoded = 'cmd=_notify-validate';
        // Mind: It is absolutely necessary to clear keys not required for
        // the verification.  Otherwise, PayPal comes up with... nothing!
        unset ($_POST['section']);
        unset ($_POST['cmd']);
        foreach($_POST as $name => $value) {
            $encoded .= '&'.urlencode($name).'='.urlencode($value);
        }
//DBG::log("Paypal::ipnCheck(): Made parameters: $encoded");
// 20120530 cURL version
        $host = (\Cx\Core\Setting\Controller\Setting::getValue('paypal_active','Shop')
            ? 'www.paypal.com'
            : 'www.sandbox.paypal.com');
        $uri = 'https://'.$host.'/cgi-bin/webscr?'.$encoded;
        $res = $ch = '';
        if (function_exists('curl_init')) {
            $ch = curl_init();
        }
        if ($ch) {
            curl_setopt($ch, CURLOPT_URL, $uri);
            // Return the received data as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $res = curl_exec($ch);
            if (curl_errno($ch)) {
//DBG::log("Paypal::ipnCheck(): ERROR: cURL: ".curl_errno($ch)." - ".curl_error($ch));
                return false;
            }
            curl_close($ch);
        } else {
            $res = file_get_contents($uri);
            if (!$res) {
                $res = Socket::getHttp10Response($uri);
            }
            if (!$res) {
//DBG::log("Paypal::ipnCheck(): ERROR: failed to connect to PayPal");
                return false;
            }
        }
//DBG::log("Paypal::ipnCheck(): PayPal response: $res");
        if (preg_match('/^VERIFIED/', $res)) {
//DBG::log("Paypal::ipnCheck(): PayPal IPN verification successful (VERIFIED)");
            return true;
        }
        if (preg_match('/^INVALID/', $res)) {
            // The payment failed.
//DBG::log("Paypal::ipnCheck(): PayPal IPN verification failed (INVALID)");
            return false;
        }
//DBG::log("Paypal::ipnCheck(): WARNING: PayPal IPN verification unclear (none of the expected results)");
        return NULL;
    }


    /**
     * Returns the array of currency codes accepted by PayPal
     *
     * Note that both keys and values of the returned array contain the
     * same strings.
     * @return  array           The array of currency codes
     */
    static function getAcceptedCurrencyCodeArray()
    {
        return self::$arrAcceptedCurrencyCode;
    }


    /**
     * Returns true if the given string equals one of the currency codes
     * accepted by PayPal
     * @return  boolean         True if the currency code is accepted,
     *                          false otherwise
     */
    static function isAcceptedCurrencyCode($currency_code)
    {
        return isset (self::$arrAcceptedCurrencyCode[$currency_code]);
    }


    /**
     * Returns HTML code representing select options for choosing one of
     * the currency codes accepted by PayPal
     * @param   string  $selected   The optional preselected currency code
     * @return  string              The HTML select options
     */
    static function getAcceptedCurrencyCodeMenuoptions($selected='')
    {
        return Html::getOptions(self::$arrAcceptedCurrencyCode, $selected);
    }

}
