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
 * Interface to Saferpay
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */

/**
 * Interface to Saferpay
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */
class Saferpay
{
    /**
     * 'Is test' flag
     * @access  public
     * @var     boolean
     */
    public static $isTest = false;

    /**
     * The test account ID
     * @todo    Implement using this
     * @access  public
     * @var     string
     */
    private static $testAccountId = '99867-94913159';

    /**
     * The hosting gateways
     * @access  private
     * @var     array
     * @see     checkOut(), success()
     */
    private static $gateway = array(
        'payInit' => 'https://www.saferpay.com/hosting/CreatePayInit.asp',
        'payConfirm' => 'https://www.saferpay.com/hosting/VerifyPayConfirm.asp',
        'payComplete' => 'https://www.saferpay.com/hosting/PayComplete.asp',
    );

    /**
     * Currency codes
     * @access  private
     * @var     array
     */
    private static $arrCurrency = array('CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'PLN', 'SEK', 'USD',);

    /**
     * Language codes
     * @access  private
     * @var     array
     */
    private static $arrLangId = array('de', 'en', 'fr', 'it',);
//    private static $arrLangId = array('en', 'de', 'fr', 'it',);

    /**
     * Keys needed for the respective operations
     * @access  private
     * @var     array
     */
    private static $arrKeys = array(
        'payInit' => array(
            'AMOUNT',
            'CURRENCY',
            'ACCOUNTID',
            'SUCCESSLINK',
            'DESCRIPTION',
        ),
        'payConfirm' => array(
            'DATA',
            'SIGNATURE',
        ),
        'payComplete' => array(
            'ACCOUNTID',
            'ID',
//            'TOKEN', // Obsolete
        )
    );

    /**
     * Error messages
     * @access  public
     * @var     array
     * @see
     */
    private static $arrError = array();

    /**
     * Error messages
     * @access  public
     * @var     array
     * @see
     */
    private static $arrWarning = array();

    /**
     * OBSOLETE
     * Payment providers
     * @access  public
     * @var     array
    private $arrProviders = array(
        'Airplus Corporate Card' => 486,
        'American Express' => 1,
        'AMEX DE' => 77,
        'AMEX DE CHF' => 333,
        'AMEX DE GBP' => 303,
        'AMEX DE USD' => 156,
        'AMEX EUR' => 112,
        'AMEX USD' => 57,
        'Bonus Card' => 15,
        'Bonus Card NSP' => 454,
        'Diners Citicorp' => 81,
        'Diners Citicorp GBP' => 334,
        'Diners Citicorp Int. CHF' => 199,
        'Diners Citicorp Int. EUR' => 179,
        'Diners Citicorp Int. GBP' => 197,
        'Diners Citicorp Int. USD' => 195,
        'Diners Citicorp USD' => 245,
        'Diners Club' => 5,
        'Diners Club EUR' => 205,
        'Diners Club USD' => 181,
        'eScore Adress Verification' => 167,
        'eScore Adress Verification' => 155,
        'Geschenkkarte EP2' => 415,
        'Homebanking DirectPay' => 345,
        'Homebanking ELBA' => 344,
        'Homebanking netpay' => 343,
        'Homebanking netpay (T)' => 357,
        'Homebanking POP' => 341,
        'InterCard LSV' => 132,
        'IQA Adress Verification' => 159,
        'JCB B+S' => 106,
        'JCB B+S CHF' => 277,
        'JCB B+S ep2 (TKC)' => 478,
        'JCB CAR' => 12,
        'Lastschrift B+S' => 352,
        'Maestro CH Multipay ep2' => 332,
        'Maestro CH. B+S ep2 (TKC)' => 480,
        'Maestro Intl. B+S ep2 (TKC)' => 474,
        'Maestro Intl. Multipay ep2' => 361,
        'Maestro Intl. Streamline ep2' => 427,
        'MasterCard Airplus' => 141,
        'MasterCard Airplus USD' => 432,
        'MasterCard B+S' => 104,
        'MasterCard B+S CHF' => 273,
        'Mastercard B+S ep2 (Datatrans)' => 379,
        'Mastercard B+S ep2 (TKC)' => 472,
        'MasterCard B+S GBP' => 227,
        'MasterCard B+S USD' => 223,
        'MasterCard Citicorp' => 79,
        'MasterCard Citicorp CHF' => 255,
        'MasterCard Citicorp DKK' => 257,
        'MasterCard Citicorp GBP' => 259,
        'MasterCard Citicorp Int. CHF' => 193,
        'MasterCard Citicorp Int. DKK' => 207,
        'MasterCard Citicorp Int. EUR' => 177,
        'MasterCard Citicorp Int. GBP' => 189,
        'MasterCard Citicorp Int. SEK' => 209,
        'MasterCard Citicorp Int. USD' => 187,
        'MasterCard Citicorp SEK' => 261,
        'MasterCard Citicorp USD' => 219,
        'Mastercard Concardis ep2' => 463,
        'Mastercard Corner CDS' => 100,
        'Mastercard Corner CDS EUR' => 110,
        'Mastercard Corner CDS GBP' => 327,
        'Mastercard Corner CDS USD' => 108,
        'Mastercard Corner ep2' => 328,
        'MasterCard GZS' => 116,
        'MasterCard GZS ATS' => 120,
        'MasterCard GZS USD' => 148,
        'Mastercard Multipay CAR' => 2,
        'Mastercard Multipay ep2' => 330,
        'MasterCard Multipay NSP' => 324,
        'MasterCard OmniPay Postbank' => 358,
        'MasterCard SET B+S' => 163,
        'MasterCard SET CITICORP' => 166,
        'MasterCard SET GZS' => 153,
        'MasterCard SET Multipay' => 96,
        'MasterCard Streamline ep2' => 423,
        'MC ConCardis CHF' => 124,
        'Mediamarkt EP2' => 413,
        'Multipay CAR' => 400,
        'myOne Card EP2' => 411,
        'myOne NSP' => 444,
        'Paybox' => 147,
        'Paybox Test' => 164,
        'Post Finance Yellownet' => 384,
        'PostCard DebitDirect' => 322,
        'POSTCARD SET' => 173,
        'POSTCARD SET - OLD' => 88,
        'Rechnung' => 114,
        'Telekurs American Express' => 239,
        'Telekurs Bonus Card' => 452,
        'Telekurs Diners' => 235,
        'Telekurs ex MasterCard' => 251,
        'Telekurs Geschenkkarte' => 402,
        'Telekurs JCB' => 253,
        'Telekurs Maestro CH' => 241,
        'Telekurs Maestro Intl.' => 249,
        'Telekurs MasterCard' => 237,
        'Telekurs Mastercard B+S' => 482,
        'Telekurs Mediamarkt' => 393,
        'Telekurs myOne Card' => 391,
        'Telekurs PowerCard' => 459,
        'Telekurs VISA' => 231,
        'Telekurs Visa B+S' => 484,
        'Telekurs VISA Corner' => 389,
        'Telekurs VISA Epsys' => 233,
        'VISA Airplus' => 139,
        'VISA Airplus USD' => 430,
        'VISA B+S' => 102,
        'VISA B+S CHF' => 275,
        'Visa B+S ep2 (Datatrans)' => 381,
        'Visa B+S ep2 (TKC)' => 476,
        'VISA B+S GBP' => 229,
        'VISA B+S USD' => 225,
        'VISA Citicorp' => 69,
        'VISA Citicorp CHF' => 263,
        'VISA Citicorp DKK' => 265,
        'VISA Citicorp GBP' => 269,
        'VISA Citicorp Int. CHF' => 191,
        'VISA Citicorp Int. DKK' => 211,
        'VISA Citicorp Int. EUR' => 175,
        'VISA Citicorp Int. GBP' => 185,
        'VISA Citicorp Int. SEK' => 213,
        'VISA Citicorp Int. USD' => 183,
        'VISA Citicorp SEK' => 267,
        'VISA Citicorp USD' => 221,
        'Visa ConCardis CHF' => 126,
        'Visa Concardis ep2' => 461,
        'VISA Corner CHF' => 4,
        'VISA Corner DEM' => 135,
        'Visa Corner ep2' => 365,
        'VISA Corner EURO' => 65,
        'VISA Corner GBP' => 133,
        'VISA Corner ITL' => 143,
        'VISA Corner USD' => 55,
        'VISA GZS' => 118,
        'VISA GZS ATS' => 122,
        'VISA GZS USD' => 150,
        'Visa Multipay CAR' => 339,
        'Visa Multipay ep2' => 363,
        'VISA Multipay NSP' => 337,
        'Visa OmniPay Postbank' => 359,
        'VISA SET B+S' => 162,
        'VISA SET CITICORP' => 165,
        'VISA SET GZS' => 152,
        'VISA SET Multipay' => 94,
        'VISA Streamline ep2' => 425,
        'VISA UBS CHF' => 3,
        'VISA UBS DEM' => 137,
        'VISA UBS EUR' => 51,
        'VISA UBS GBP' => 310,
        'VISA UBS Purchasing' => 63,
        'VISA UBS USD' => 13,
    );
     */

    /**
     * Window options constants
     * @access  public
     */
    // Iframes are disabled due to handling problems.
    // After the payment is complete, the shop itself is displayed in the
    // frame instead of its parent!
    //const saferpay_windowoption_id_iframe = 0;
    const saferpay_windowoption_id_popup  = 1;
    const saferpay_windowoption_id_window = 2;
    // keep this up to date!
    // Note that the class method getWindowMenuoptions() has been
    // adapted to skip the disabled option 0!
    const saferpay_windowoption_id_count  = 3;


    /**
     * Generates a list of all attributes
     *
     * Note that attribute names and values are {@see urlencode()}d here, so
     * you *MUST NOT* do that yourself before or after.
     * @access  private
     * @static
     * @param   string  $step       The current payment step
     * @param   array   $arrOrder   The attributes array
     * @return  string              The URL parameter list on success,
     *                              the empty string otherwise
     */
    private static function getAttributeList($step, $arrOrder)
    {
        $attributes = '';
        foreach (self::$arrKeys[$step] as $attribute) {
            if (empty ($arrOrder[$attribute])) {
                self::$arrError[] = $attribute." is missing";
            }
        }
        foreach ($arrOrder as $attribute => $value) {
            $value = self::checkAttribute($attribute, $value);
            if ($value !== NULL) {
                $attributes .=
                    ($attributes ? '&' : '').
                    urlencode($attribute).'='.urlencode($arrOrder[$attribute]);
            }
        }
        if (empty (self::$arrError)) {
            return $attributes;
        }
        return '';
    }


    static function getForm($arrOrder, $autopost=false)
    {
        global $_ARRAYLANG;
/*        'AMOUNT' => str_replace('.', '', $_SESSION['shop']['grand_total_price']),
        'CURRENCY' => \Cx\Modules\Shop\Controller\Currency::getActiveCurrencyCode(),
        'ORDERID' => $_SESSION['shop']['order_id'],
        'ACCOUNTID' => \Cx\Core\Setting\Controller\Setting::getValue('saferpay_id','Shop'),
        'SUCCESSLINK' => urlencode('http://'.$serverBase.'index.php?section=Shop'.MODULE_INDEX.'&cmd=success&result=1&handler=saferpay'),
        'FAILLINK' => urlencode('http://'.$serverBase.'index.php?section=Shop'.MODULE_INDEX.'&cmd=success&result=0&handler=saferpay'),
        'BACKLINK' => urlencode('http://'.$serverBase.'index.php?section=Shop'.MODULE_INDEX.'&cmd=success&result=2&handler=saferpay'),
        'DESCRIPTION' => urlencode('"'.$_ARRAYLANG['TXT_ORDER_NR'].' '.$_SESSION['shop']['order_id'].'"'),
        'LANGID' => FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
        'NOTIFYURL' => urlencode('http://'.$serverBase.'index.php?section=Shop'.MODULE_INDEX.'&cmd=success&result=-1&handler=saferpay'),
        'ALLOWCOLLECT' => 'no',
        'DELIVERY' => 'no',
        'PROVIDERSET' = $arrCards; // if set*/
        $payInitUrl = self::payInit($arrOrder,
            \Cx\Core\Setting\Controller\Setting::getValue('saferpay_use_test_account','Shop'));
//DBG::log("Saferpay::getForm(): payInit URL: $payInitUrl");
        if (   !$payInitUrl
            || strtoupper(substr($payInitUrl, 0, 5)) == 'ERROR') {
            return
                "<font color='red'><b>".
                $_ARRAYLANG['TXT_SHOP_PSP_FAILED_TO_INITIALISE_SAFERPAY'].
                "<br />Warnings:<br />".
                Saferpay::getErrors().
                "<br />Errors:<br />".
                Saferpay::getWarnings().
                "</b></font>";
        }
        $return = "<script src='http://www.saferpay.com/OpenSaferpayScript.js'></script>\n";
        switch (\Cx\Core\Setting\Controller\Setting::getValue('saferpay_window_option','Shop')) {
            case 0: // iframe -- UNUSED, because it does not work reliably!
                return
                    $return.
                    $_ARRAYLANG['TXT_ORDER_PREPARED']."<br/><br/>\n".
                    "<iframe src='$payInitUrl' width='580' height='400' scrolling='no' marginheight='0' marginwidth='0' frameborder='0' name='saferpay'></iframe>\n";
            case 1: // popup
                return
                    $return.
                    $_ARRAYLANG['TXT_ORDER_LINK_PREPARED'].'<br/><br/>
<script type="text/javascript">
function openSaferpay() {
  strUrl = "'.$payInitUrl.'";
  if (strUrl.indexOf("WINDOWMODE=Standalone") == -1) {
    strUrl += "&WINDOWMODE=Standalone";
  }
  oWin = window.open(strUrl, "SaferpayTerminal",
    "scrollbars=1,resizable=0,toolbar=0,location=0,directories=0,status=1,menubar=0,width=580,height=400");
  if (oWin == null || typeof(oWin) == "undefined") {
    alert("The payment couldn\'t be initialized.  Maybe you are using a popup blocker?");
  }
}
'.($autopost
? 'window.setTimeout(3000, openSaferpay());
' : '').'
</script>
<input type="button" name="order_now" value="'.
  $_ARRAYLANG['TXT_ORDER_NOW'].'" onclick="openSaferpay();" />
';
            default: //case 2: // new window
        }
        return
            $return.
            $_ARRAYLANG['TXT_ORDER_LINK_PREPARED']."<br/><br/>
<form method='post' action='$payInitUrl'>
  <input type='submit' value='".$_ARRAYLANG['TXT_ORDER_NOW']."' />
</form>
".($autopost ? '
<script type="text/javascript">
window.setTimeout(3000, function() {
  document.forms[0].submit();
});
</script>
' : '');
    }


    /**
     * Returns the URI for initializing the payment with Saferpay
     * @access  public
     * @static
     * @param   array   $arrOrder   The attributes array
     * @param   boolean $is_test    If true, uses the test account.
     *                              Defaults to NULL (real account)
     * @return  string              The URI for the payment initialisation
     *                              on success, the empty string otherwise
     */
    static function payInit($arrOrder, $is_test=NULL)
    {
        if ($is_test) {
            $arrOrder['ACCOUNTID'] = self::$testAccountId;
        }
        $attributes = self::getAttributeList('payInit', $arrOrder);
        $result = '';
// NOTE: This only works when cURL is available
        if (function_exists('curl_init')) {
            $ch = curl_init(self::$gateway['payInit'].'?'.$attributes);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSLVERSION, 'CURL_SSLVERSION_TLSv1_2');
            $result = curl_exec($ch);
        }
        if ($result) return $result;
        self::$arrWarning[] = "cURL is not available";
// NOTE: These won't work without "allow_url_fopen" enabled in php.ini,
// and PHP compiled with --configure_ssl
        $result = file_get_contents(self::$gateway['payInit'].'?'.$attributes);
        if ($result) return $result;
        self::$arrWarning[] = "SSL wrapper for fopen() is not available";
        // Try socket connection as well
        $result = Socket::getHttp10Response(
            self::$gateway['payInit'].'?'.$attributes);
        if ($result) return $result;
        self::$arrWarning[] = "SSL transport for sockets is not available";
        self::$arrError[] = "Failed to open an SSL connection";
        return '';
    }


    /**
     * Confirms the payment transaction
     * @access  public
     * @static
     * @return  boolean     The transaction ID on success, NULL otherwise
     */
    static function payConfirm()
    {
//DBG::log("Saferpay::payConfirm():");
//DBG::log("POST: ".var_export($_POST, true));
//DBG::log("GET: ".var_export($_GET, true));
        $arguments = array();
        parse_str($_SERVER['QUERY_STRING'], $arguments);
        // Note: parse_str()'s results comply with the magic quotes setting!
        $arrOrder = array(
            'DATA' => urlencode(contrexx_input2raw($arguments['DATA'])),
            'SIGNATURE' => urlencode(contrexx_input2raw($arguments['SIGNATURE'])),
        );
        $attributes = self::getAttributeList('payConfirm', $arrOrder);
        // This won't work without allow_url_fopen
        $confirmUrl = self::$gateway['payConfirm'].'?'.$attributes;
//DBG::log("payConfirm: URL: $confirmUrl");
        $result = file_get_contents($confirmUrl);
        if (!$result) {
            // Try socket connection as well
            $result = Socket::getHttp10Response($confirmUrl);
        }
//DBG::log("payConfirm: Result: ".self::$arrTemp['result']);
        if (substr($result, 0, 2) == 'OK') {
            parse_str(substr($result, 3), $arguments);
  //DBG::log("Saferpay::payConfirm(): SUCCESS, ID $ID");
            return $arguments['ID'];
// Obsolete
//            self::$token = $TOKEN;
        }
        self::$arrError[] = $result;
//DBG::log("Saferpay::payConfirm(): FAIL, Error: ".join("\n", self::$arrError));
        return NULL;
    }


    /**
     * Completes the payment transaction
     * @access  public
     * @static
     * @param   array       $arrOrder   The attributes array
     * @return  boolean                 True on success, false otherwise
     */
    static function payComplete($arrOrder)
    {
        $attributes = self::getAttributeList('payComplete', $arrOrder).
            // Business account *ONLY*, like the test account.
            // There is no password setting (yet), so this is for
            // future testing purposes *ONLY*
            (\Cx\Core\Setting\Controller\Setting::getValue('saferpay_use_test_account','Shop')
              ? '&spPassword=XAjc3Kna'
              : '');
        // This won't work without allow_url_fopen
        $result = file_get_contents(
            self::$gateway['payComplete'].'?'.$attributes);
        if (!$result) {
            // Try socket connection as well
            $result = Socket::getHttp10Response(
                self::$gateway['payComplete'].'?'.$attributes);
        }
        if (substr($result, 0, 2) == 'OK') {
            return true;
        }
        self::$arrError[] = $result;
        return false;
    }


    /**
     * Returns the order ID of the current transaction
     * @access  public
     * @static
     * @return  integer         The Order ID
     */
    static function getOrderId()
    {
        $match = array();
        $data = urldecode(contrexx_stripslashes($_GET['DATA']));
        if (!preg_match('/\sORDERID\=\"(\d+)\"/', $data, $match))
            return false;
        return $match[1];
    }


    /**
     * Verifies the value of an attribute
     * @access  private
     * @static
     * @param   string  $attribute  The attribute name
     * @param   string  $value      The attribute value
     * @return  boolean             True for valid values, false otherwise
     */
    private static function checkAttribute($attribute, $value)
    {
        switch ($attribute) {
            case 'AMOUNT':
                $value = intval($value);
                if ($value > 0) return $value;
                break;
            case 'CURRENCY':
                $value = strtoupper($value);
                if (in_array($value, self::$arrCurrency)) return $value;
                break;
            case 'ACCOUNTID':
                if ($value != '') return $value;
                break;
            case 'ORDERID':
                if (strlen($value) > 80) {
                    $value = substr($value, 0, 80);
                    self::$arrWarning[] = $attribute.' was cut to 80 characters.';
                }
                return $value;
            case 'SUCCESSLINK':
            case 'FAILLINK':
            case 'BACKLINK':
            case 'NOTIFYURL':
// TODO: Verify URLs
                if ($value) return $value;
                break;
            case 'ALLOWCOLLECT':
            case 'DELIVERY':
                if ($value != 'yes') {
                    $value = 'no';
                }
                return $value;
            case 'LANGID':
                $value = strtolower($value);
                if (!in_array($value, self::$arrLangId)) {
                    $value = self::$arrLangId[0];
                    self::$arrWarning[] = $attribute.' was set to default value "'.self::$arrLangId['0'].'".';
                }
                return $value;
            case 'DURATION':
                if (strlen($value) == 14) return $value;
            case 'DESCRIPTION':
            case 'NOTIFYADDRESS':
            case 'TOLERANCE':
            case 'DATA':
            case 'SIGNATURE':
            case 'ID':
            case 'TOKEN':
            case 'EXPIRATION':
            case 'PROVIDERID':
            case 'PROVIDERNAME':
            case 'PAYMENTAPPLICATION':
            case 'ACTION':
                return $value;
            case 'PROVIDERSET':
                self::$arrWarning[] = "$attribute is obsolete";
                return NULL;
/* OBSOLETE
                // see http://www.saferpay.com/help/ProviderTable.asp
                if (is_array($value)) {
                    foreach ($value as $provider) {
                        if (isset(self::$arrProviders[$provider])) {
                            $arrProviders[] = self::$arrProviders[$provider];
                            //$arrProviders[] = $provider;
                        } else {
                            self::$arrWarning[] = 'Unknown provider "'.$provider.'"';
                        }
                    }
                }
// fixed: $arrProviders may be undefined!
                if (isset($arrProviders) && is_array($arrProviders)) {
                    $value = urlencode(implode(',', $arrProviders));
                } else {
                    $value = '';
                }
                return $value;
 */
            default:
                self::$arrError[] = "Invalid or unknown attribute /$attribute/ value /$value/";
        }
        return NULL;
    }


    /**
     * Returns code for HTML menu options for choosing the window display
     * option
     * @param   integer     $selected       The selected option ID
     * @return  string                      The HTML menu options
     */
    static function getWindowMenuoptions($selected=0)
    {
        global $_ARRAYLANG;

        $strMenuoptions = '';
        // Set $id to start at 0 (zero) to enable iframes!
        for ($id = 1; $id < self::saferpay_windowoption_id_count; ++$id) {
            $strMenuoptions .=
                '<option value="'.$id.'"'.
                ($id == $selected ? ' selected="selected"' : '').'>'.
                $_ARRAYLANG['TXT_SHOP_SAFERPAY_WINDOWOPTION_'.$id].
                "</option>\n";
        }
        return $strMenuoptions;
    }


    /**
     * Returns accumulated warnings as a HTML string
     * @return  string          The warnings, if any, or the empty string
     */
    static function getWarnings()
    {
        return join("<br />", self::$arrWarning);
    }


    /**
     * Returns accumulated warnings as a HTML string
     * @return  string          The warnings, if any, or the empty string
     */
    static function getErrors()
    {
        return join("<br />", self::$arrError);
    }

}
