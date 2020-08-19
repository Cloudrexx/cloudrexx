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
 * Datatrans PSP Interface
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */


/**
 * Payment gateway URIs
 *
 * Live Service URIs:
 * UTF-8 encoding: https://payment.datatrans.biz/upp/jsp/upStart.jsp
 * ISO encoding: https://payment.datatrans.biz/upp/jsp/upStartIso.jsp
 *
 * Service URIs for testing:
 * UTF-8 encoding: https://pilot.datatrans.biz/upp/jsp/upStart.jsp
 * ISO encoding: https://pilot.datatrans.biz/upp/jsp/upStartIso.jsp
 * @see     checkOut(), success()
 */
define('SHOP_PSP_URI_DATATRANS', 'https://payment.datatrans.biz/upp/jsp/upStart.jsp');
define('SHOP_PSP_URI_DATATRANS_TEST', 'https://pilot.datatrans.biz/upp/jsp/upStart.jsp');


/*

Test Data (excerpt from Datatrans documentation)

Credit Cards

Notes:
- Don't use parameter "testOnly".
- Merchant ID must be set to 1000011011 (test merchant).
- These card numbers only work in our test environment! Productive cards
  cannot be processed on the test account.

Card type   Card number       Exp. Date CVV   Test rule
-----------------------------------------------------------
Visa        4242424242424242  12/2010   123   With limit
Visa        4900000000000003  12/2010   123   Without limit
Mastercard  5404000000000001  12/2010   123   With limit
Mastercard  5200000000000007  12/2010   123   Without limit
Amex        375811111111115   12/2010   1234  With limit
Amex        375000000000007   12/2010   1234  Without limit
Diners      36168002586009    12/2010   123   With limit
Diners      36167719110012    12/2010   123   Without limit
JCB         3569990010030442  12/2010   123   With limit
JCB         3569990010030400  12/2010   123   Without limit
AirPlus     122000200924770   12/2010         With limit
AirPlus     192072420096379   12/2010         Without limit

The following test rules apply for all cards with limit (see column "Test rule"):
Amount / range          Error message
< 90.--                 Transaction authorised
> 90.-- and <= 100.--   Transaction declined (i.e. insufficient limit, bad expiry Date)
> 100.-- and <= 110.--  Referral
> 110.--                Card blocked (lost or stolen)


Postfinance

Test option for Postfinance available upon request.
Parameters:
- Card number: 12345677
- Account number: 30-999999-3
There are no amount restrictions applicable to Postfinance testing.


Deutsches Elektronisches Lastschrift Verfahren (ELV)

Test option for German ELV available upon request.
bankaccount   bankrouting Restriction
1234512345    12345678    if > 90.00 ?? declined
5432154321    12345678    no restriction


Restrictions

The following features can't be tested or simulated using the default test
merchant ID 1000011011:
- Post URL
- "sign" parameter
- Individual design of the payment- or process page

These features require access to the technical administration tool
http://pilot.datatrans.biz. If you need to implement one of the features which
are unavailable on the default test merchant ID please apply for a dedicated
test account at support@datatrans.ch. Datatrans will then provide a merchant ID
which is dedicated to the merchant for 3 months.
*/

/**
 * Datatrans PSP Interface
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */
class Datatrans
{
    /**
     * Use test server URI if true
     * @var   boolean
     */
    private static $useTestserver = false;

    /**
     * Codes of supported languages
     * @access  private
     * @var     array
     */
    private static $arrLangId = array(
        'en', 'de', 'fr', 'it', 'es',
    );

    /**
     * Payment methods
     * @access  public
     * @var     array
     */
    private static $arrPaymentmethod = array(
        'VIS', // VISA
        'ECA', // Mastercard
        'AMX', // American Express
        'DIN', // Diners Club
        'POS', // Swiss Post Yellow Account
        'CLB', // Swisscom ClickandBuy
        'PAP', // PayPal
        'MYO', // Manor MyOne Card
        'JEL', // Jelmoli Bonus Card
        'PSC', // Paysafecard
        'ELV', // Deutsches ELV (requires "bankaccount" and "bankrouting")
    );

    /**
     * Mandatory fields
     * @access  private
     * @var     array
     */
    private static $arrFieldMandatory = array(
        'merchantId' => false,
        'amount' => false,
        'currency' => false,
        'refno' => false,
    );

    /**
     * Optional fields
     * @access  private
     * @var     array
     */
    private static $arrFieldOptional = array(
        'successUrl' => false,
        'errorUrl' => false,
        'cancelUrl' => false,
        'language' => false,
        'sign' => false,
        'reqtype' => false,
        'hiddenMode' => false,
        'cvv' => false,
        'useAlias' => false,
        'testOnly' => false,
        'uppCustomerTitle' => false,
        'uppCustomerName' => false,
        'uppCustomerFirstName' => false,
        'uppCustomerLastName' => false,
        'uppCustomerStreet' => false,
        'uppCustomerStreet2' => false,
        'uppCustomerCity' => false,
        'uppCustomerCountry' => false,
        'uppCustomerZipCode' => false,
        'uppCustomerPhone' => false,
        'uppCustomerFax' => false,
        'uppCustomerEmail' => false,
        'uppCustomerDetails' => false, // Pilot parameter, to be submitted with value "yes" if address details are submitted
    );

    /**
     * Return fields
     * @access  private
     * @var     array
     */
    private static $arrFieldReturn = array(
        // Common
        'uppTransactionId' => false,
        'refno' => false,
        'amount' => false,
        'currency' => false,
        'status' => false,
        'uppMsgType' => false,
        // Success/unsuccessful/failed, but not when cancelled
        'pmethod' => false,
        'reqtype' => false,
        // Success only
        'authorizationCode' => false,
        'responseMessage' => false,
        'acqAuthorizationCode' => false,
        // Success only -- optional
        'aliasCC' => false,
        'maskedCC' => false,
        'sign2' => false,
        // Unsuccessful/failed only
        'errorCode' => false,
        'errorMessage' => false,
        'errorDetail' => false,
    );

    /**
     * Error codes
     * @access  private
     * @var     array
     */
    private static $arrErrorcode = array(
        1001 => 'Missing required parameter',
        1002 => 'Not valid parameter format',
        1003 => 'Value of parameter not found',
        1004 => 'Invalid card number',
        1007 => 'Access denied by sign control / parameter "sign" invalid',
        1008 => 'Merchant disabled by Datatrans',
        1201 => 'System error',
        1400 => 'Invalid card number',
        1401 => 'Invalid expiration date',
        1402 => 'Expired card',
        1403 => 'Transaction declined by card issuer',
        1404 => 'Card blocked',
        1405 => 'Amount exceeded',
        3000 => 'Denied by fraud management',
        3001 => 'IP address declined by global fraud management',
        3002 => 'IP address declined by merchant fraud management',
        3003 => 'CC number declined by global fraud management',
        3004 => 'CC number declined by merchant fraud management',
        3005 => 'Denied by fraud management',
        3006 => 'Denied by fraud management',
        3011 => 'Denied by fraud management',
        3012 => 'Denied by fraud management',
        3013 => 'Denied by fraud management',
        3014 => 'Denied by fraud management',
        3015 => 'Denied by fraud management',
        3016 => 'Denied by fraud management',
        3031 => 'Declined due to response code 02',
        3041 => 'Declined due to post error / post URL check failed',
        10412 => 'PayPal duplicate error',
        -885 => 'CC-alias update error',
        -886 => 'CC-alias insert error',
        -887 => 'CC-alias does not match with cardno',
        -888 => 'CC-alias not found',
        -900 => 'CC-alias service not enabled',
        -999 => 'Undefined error',
    );


    /**
     * Set up the mandatory parameters
     *
     * @param   integer $merchantId     The Datatrans merchant ID
     * @param   string  $refno          The unique merchant reference number,
     *                                  aka order ID
     * @param   string  $amount         The amount, in cents
     * @param   string  $currency       The three letter currency code
     * @return  boolean                 True on success, false otherwise
     */
    static function initialize($merchantId, $refno, $amount, $currency)
    {
//echo("Datatrans::initialize(merchantId $merchantId, refno $refno, amount $amount, currency $currency): Entered<br />");
        /**
         * Currency: Conversion, formatting.
         */
        self::$arrFieldMandatory['merchantId'] = trim(strip_tags($merchantId));
        self::$arrFieldMandatory['refno'] = trim(strip_tags($refno));
        self::$arrFieldMandatory['amount'] = \Cx\Modules\Shop\Controller\CurrencyController::formatCents($amount);
        self::$arrFieldMandatory['currency'] = trim(strip_tags($currency));
        if (   empty($merchantId)
            || empty($refno)
            || empty($amount)
            || empty($currency)) {
//die("Datatrans::initialize(): Failed - invalid parameters: merchantId $merchantId, refno $refno, amount $amount, currency $currency");
            return false;
        }
//echo("Datatrans::initialize(): SUCCESS - parameters: ".var_export(self::$arrFieldMandatory, true)."<br />");
        return true;
    }


    static function setSuccessurl($successUrl)
    {
        self::$arrFieldOptional['successUrl'] = trim(strip_tags($successUrl));
    }
    static function setErrorurl($errorUrl)
    {
        self::$arrFieldOptional['errorUrl'] = trim(strip_tags($errorUrl));
    }
    static function setCancelurl($cancelUrl)
    {
        self::$arrFieldOptional['cancelUrl'] = trim(strip_tags($cancelUrl));
    }
    /**
     * Set the language to be used for the payment
     *
     * Currently supported:
     * - de (German)
     * - en (English)
     * - fr (French)
     * - it (Italian)
     * - es (Spanish)
     * If any other value is specified, this method sets the default language,
     * German.
     */
    static function setLanguage($language)
    {
        if (   $language != 'de'
            && $language != 'en'
            && $language != 'fr'
            && $language != 'it'
            && $language != 'es')
            $language = 'de';
        self::$arrFieldOptional['language'] = $language;
    }
    /**
     * Set the additional security identifier.
     *
     * Note:  This is currently not supported by this class.
     * @param   string    $sign       The identifier
     */
    static function setSign($sign)
    {
        self::$arrFieldOptional['sign'] = trim(strip_tags($sign));
    }
    /**
     * Set the request type
     *
     * Specifies whether the transaction has to be immediately settled
     * or authorised only. There are two request types available:
     * - NOA: authorisation only
     * - CAA: authorisation with immediate settlement in case of successful authorisation
     * If any other value is specified, this method sets the default type, CAA.
     */
    static function setReqtype($reqtype)
    {
        if (   $reqtype != 'NOA' && $reqtype != 'CAA')
            $reqtype = 'CAA';
        self::$arrFieldOptional['reqtype'] = $reqtype;
    }
    /**
     * Enables hîdden mode if called.
     *
     * Note that this class currently does not support all possible payment
     * methods in hidden mode.  This is only implemented to enable German
     * ELV for the time being.
     */
    static function setHiddenmode()
    {
        self::$arrFieldOptional['hiddenMode'] = 'yes';
    }
    /**
     * Set the CVC/CVV
     * Note: This is currently not supported by this class.
     * @param   string    $cvv      The CVC or CVV
     */
    static function setCvv($cvv)
    {
        self::$arrFieldOptional['cvv'] = trim(strip_tags($cvv));
    }
    /**
     * Request the credit card alias if called
     */
    static function setUsealias()
    {
        self::$arrFieldOptional['useAlias'] = 'yes';
    }
    /**
     * Enable test mode if called
     */
    static function setTestonly()
    {
// It seems that this parameter isn't needed after all.
//        self::$arrFieldOptional['testOnly'] = 'yes';
// Just go to the test server
          $this->useTestserver = true;
    }
    static function setUppcustomertitle($uppCustomerTitle)
    {
        self::$arrFieldOptional['uppCustomerTitle'] = trim(strip_tags($uppCustomerTitle));
        self::setUppcustomerdetails();
    }
    static function setUppcustomername($uppCustomerName)
    {
        self::$arrFieldOptional['uppCustomerName'] = trim(strip_tags($uppCustomerName));
        self::setUppcustomerdetails();
    }
    static function setUppcustomerfirstname($uppCustomerFirstName)
    {
        self::$arrFieldOptional['uppCustomerFirstName'] = trim(strip_tags($uppCustomerFirstName));
        self::setUppcustomerdetails();
    }
    static function setUppcustomerlastname($uppCustomerLastName)
    {
        self::$arrFieldOptional['uppCustomerLastName'] = trim(strip_tags($uppCustomerLastName));
        self::setUppcustomerdetails();
    }
    static function setUppcustomerstreet($uppCustomerStreet)
    {
        self::$arrFieldOptional['uppCustomerStreet'] = trim(strip_tags($uppCustomerStreet));
        self::setUppcustomerdetails();
    }
    static function setUppcustomerstreet2($uppCustomerStreet2)
    {
        self::$arrFieldOptional['uppCustomerStreet2'] = trim(strip_tags($uppCustomerStreet2));
        self::setUppcustomerdetails();
    }
    static function setUppcustomercity($uppCustomerCity)
    {
        self::$arrFieldOptional['uppCustomerCity'] = trim(strip_tags($uppCustomerCity));
        self::setUppcustomerdetails();
    }
    static function setUppcustomercountry($uppCustomerCountry)
    {
        self::$arrFieldOptional['uppCustomerCountry'] = trim(strip_tags($uppCustomerCountry));
        self::setUppcustomerdetails();
    }
    static function setUppcustomerzipcode($uppCustomerZipCode)
    {
        self::$arrFieldOptional['uppCustomerZipCode'] = trim(strip_tags($uppCustomerZipCode));
        self::setUppcustomerdetails();
    }
    static function setUppcustomerphone($uppCustomerPhone)
    {
        self::$arrFieldOptional['uppCustomerPhone'] = trim(strip_tags($uppCustomerPhone));
        self::setUppcustomerdetails();
    }
    static function setUppcustomerfax($uppCustomerFax)
    {
        self::$arrFieldOptional['uppCustomerFax'] = trim(strip_tags($uppCustomerFax));
        self::setUppcustomerdetails();
    }
    static function setUppcustomeremail($uppCustomerEmail)
    {
        self::$arrFieldOptional['uppCustomerEmail'] = trim(strip_tags($uppCustomerEmail));
        self::setUppcustomerdetails();
    }
    /**
     * Enable sending of customer details if called.
     *
     * This is called by all of the setUppcutomer*() methods whenever
     * a valid value is set.
     */
    static function setUppcustomerdetails()
    {
        self::$arrFieldOptional['uppCustomerDetails'] = 'yes';
    }


    static function getGatewayUri()
    {
//echo("Testserver: self: ".(self::$useTestserver ? 'Ja' : 'Nein').", conf: ".(\Cx\Core\Setting\Controller\Setting::getValue('datatrans_use_testserver','Shop') ? 'Ja' : 'Nein')."<br />");
// See setTestonly() for details on why this is not used.
//            (self::$arrFieldOptional['testOnly']
        return
            (   self::$useTestserver
             || \Cx\Core\Setting\Controller\Setting::getValue('datatrans_use_testserver','Shop')
              ? SHOP_PSP_URI_DATATRANS_TEST
              : SHOP_PSP_URI_DATATRANS
            );
    }


    /**
     * Generates the HTML code containing all available parameters
     * with respective values in hidden input fields
     *
     * Note:  The form tag is not included in the result, nor is the
     * submit button.
     * @return  string                  The HTML code on success,
     *                                  the empty string otherwise
     */
    static function getHtml()
    {
        $strFormhtml = '';
        foreach (self::$arrFieldMandatory as $name => $value) {
            if (empty($value)) return '';
//echo("name $name, value $value<br />");
            $strFormhtml .=
                '<input type="hidden" name="'.$name.
                  '" value="'.$value.'" />'."\n";
        }
        foreach (self::$arrFieldOptional as $name => $value) {
            if (empty($value)) continue;
//echo("name $name, value $value<br />");
            $strFormhtml .=
                '<input type="hidden" name="'.$name.
                  '" value="'.$value.'" />'."\n";
        }
//echo("/".nl2br(htmlentities($strFormhtml, ENT_QUOTES, 'UTF-8'))."/<br />");
        return $strFormhtml;
    }


    /**
     * Validates the parameters returned after a payment has been completed
     *
     * Picks the parameters from the post request and stores the values.
     * Note that this method unset()s the elements from the $_POST array.
     * Mind that the result does not reflect the outcome of the
     * payment process, but only whether the returned values are
     * complete and valid.
     * @return  boolean               True on success, false otherwise
     */
    static function validateReturn()
    {
        foreach (array_keys(self::$arrFieldReturn) as $name) {
            if (isset($_POST[$name]))
                self::$arrFieldReturn[$name] =
                    contrexx_input2raw($_POST[$name]);
            unset($_POST[$name]);
        }
        // Common parameters must be non-empty
        if (   empty(self::$arrFieldReturn['uppTransactionId'])
            || empty(self::$arrFieldReturn['refno'])
            || empty(self::$arrFieldReturn['amount'])
            || empty(self::$arrFieldReturn['currency'])
            || empty(self::$arrFieldReturn['uppMsgType'])
            || empty(self::$arrFieldReturn['status'])) {
//echo("Datatrans::validateReturn(): Empty common parameters, FAIL<br />");
            return false;
        }
        // Determine the outcome of the payment.
        // One of Success, error, or cancel
        $status = self::$arrFieldReturn['status'];
        switch ($status) {
          case 'success':
//echo("Datatrans::validateReturn(): status is SUCCESS...<br />");
            if (   empty(self::$arrFieldReturn['authorizationCode'])
                || empty(self::$arrFieldReturn['responseMessage'])
                || empty(self::$arrFieldReturn['acqAuthorizationCode'])
                || empty(self::$arrFieldReturn['pmethod'])
                || empty(self::$arrFieldReturn['reqtype'])
// Note that these are neither required nor supported by this class for now:
//                || empty(self::$arrFieldReturn['aliasCC'])
//                || empty(self::$arrFieldReturn['maskedCC'])
//                || empty(self::$arrFieldReturn['sign2'])
            ) {
//echo("Datatrans::validateReturn(): Empty success parameters, FAIL<br />");
                return false;
            }
            if (!in_array(self::$arrFieldReturn['pmethod'], self::$arrPaymentmethod)) {
//echo("Datatrans::validateReturn(): Illegal payment method, FAIL<br />");
                return false;
            }
            if (   self::$arrFieldReturn['reqtype'] != 'NOA'
                && self::$arrFieldReturn['reqtype'] != 'CAA') {
//echo("Datatrans::validateReturn(): Illegal request type, FAIL<br />");
                return false;
            }
            break;
          case 'error':
//echo("Datatrans::validateReturn(): status is ERROR...<br />");
            // Unsuccessful/failed only
            if (   empty(self::$arrFieldReturn['errorCode'])
                || empty(self::$arrFieldReturn['errorMessage'])
                || empty(self::$arrFieldReturn['errorDetail'])
                || empty(self::$arrFieldReturn['pmethod'])
                || empty(self::$arrFieldReturn['reqtype'])) {
//echo("Datatrans::validateReturn(): Empty error parameters, FAIL<br />");
                return false;
            }
            if (!in_array(self::$arrFieldReturn['pmethod'], self::$arrPaymentmethod)) {
//echo("Datatrans::validateReturn(): Illegal payment method, FAIL<br />");
                return false;
            }
            if (   self::$arrFieldReturn['reqtype'] != 'NOA'
               && self::$arrFieldReturn['reqtype'] != 'CAA') {
//echo("Datatrans::validateReturn(): Illegal request type, FAIL<br />");
                return false;
           }
            break;
          case 'cancel':
//echo("Datatrans::validateReturn(): status is CANCEL<br />");
            break;
          default:
//echo("Datatrans::validateReturn(): error, unknown status<br />");
            return false;
        }
        return true;
    }


    /**
     * Returns a numeric value representing the outcome of the payment process
     *
     * The returned value has the same meaning as the result value
     * that is usually set in the request when returning to the shop
     * from other PSPs.  This is necessary because Datatrans sends
     * the request to the POST URI even if the payment fails or is
     * cancelled.
     * Note:  You must call this only *after* validateReturn() has set
     * up the array with all request parameters!
     * @return  integer               The payment result value
     */
    static function getPaymentResult()
    {
        $status = self::$arrFieldReturn['status'];
        switch ($status) {
          case 'success':
            return 1;
          case 'cancel':
            return 2;
        }
        // Everything else is considered a failure
        // (even if it's not their fault)
        return 0;
    }


    /**
     * Returns the returned order ID, if any
     *
     * The order ID is taken from the array created by validateReturn()
     * after a payment has completed.
     * If you call this method before that point, you'll get back false
     * in any case.
     * @access  public
     * @return  string                  The order ID if known, false otherwise
     */
    static function getOrderId()
    {
        return (self::$arrFieldReturn['refno']);
    }


    static function getReqtypeMenuoptions($datatrans_request_type)
    {
        global $_ARRAYLANG;

        return
            '<option value="NOA"'.
            ($datatrans_request_type == 'NOA' ? 'selected="selected"' : '').
            '>'.$_ARRAYLANG['TXT_SHOP_DATATRANS_REQTYPE_NOA'].'</option>'.
            '<option value="CAA"'.
            ($datatrans_request_type == 'CAA' ? 'selected="selected"' : '').
            '>'.$_ARRAYLANG['TXT_SHOP_DATATRANS_REQTYPE_CAA'].'</option>';
    }

}


/* TEST
Datatrans::initialize('1000011011', '995', 'CHF', '234');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <title>Datatrans Test Page</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="robots" content="noindex" />
    <meta name="robots" content="nofollow" />
    <meta name="robots" content="noarchive" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="cache-control" content="no-cache" />
  </head>
  <body>
    <h1>Previous results:</h1>
    <h2>GET:</h2>
<? echo(nl2br(htmlentities(print_r($_GET, true), ENT_QUOTES, 'UTF-8'))); ?>
    <h2>POST:</h2>
<? echo(nl2br(htmlentities(print_r($_POST, true), ENT_QUOTES, 'UTF-8'))); ?>
    <h1>Current Form:</h1>
<? echo(nl2br(htmlentities(
'    <form action="'.Datatrans::getGatewayUri().'" method="post">
'.Datatrans::getHtml().'
      <input type="submit" value="go">
    </form>
', ENT_QUOTES, 'UTF-8')));
?>
    <form action="<? echo(Datatrans::getGatewayUri()); ?>" method="post">
<? echo(Datatrans::getHtml()); ?>
      <input type="submit" value="go">
    </form>
  </body>
</html>
*/
