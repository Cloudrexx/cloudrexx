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
 * PostFinance online payment
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

/**
 * PostFinance online payment
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 * @internal    Yellowpay must be configured to return with the follwing requests:
 * POST after payment was made:
 *      http://<my>.com/index.php?section=Shop&cmd=success&handler=yellowpay&result=-1
 * GET after payment has completed successfully:
 *      http://<my>.com/index.php?section=Shop&cmd=success&handler=yellowpay&result=1
 * GET after payment has failed:
 *      http://<my>.com/index.php?section=Shop&cmd=success&handler=yellowpay&result=0
 * GET after payment has been cancelled:
 *      http://<my>.com/index.php?section=Shop&cmd=success&handler=yellowpay&result=2
 */
class Yellowpay
{

    /**
     * section name
     *
     * @access  private
     * @var     string
     */
    private static $sectionName = null;

    /**
     * Error messages
     * @access  public
     * @var     array
     */
    public static $arrError = array();

    /**
     * Warning messages
     * @access  public
     * @var     array
     */
    public static $arrWarning = array();

    /**
     * Known payment method names
     *
     * Note that these correspond to the names used for
     * dynamically choosing the payment methods using the
     * txtPM_*_Status" parameters and must thus
     * be spelt *exactly* as specified.
     * @var     array
     * @static
     */
    private static $arrKnownPaymentMethod = array(
        1 => 'PostFinanceCard',
        2 => 'yellownet',
        3 => 'Master',
        4 => 'Visa',
        5 => 'Amex',
        6 => 'Diners',
    );


    private static $arrFieldMandatory = array(
        'PSPID',
        'ORDERID',
        'AMOUNT',
        'CURRENCY',
        // Not mandatory, but needed for SHA-1 anyway
        'OPERATION',
        // The following  parameters are not mandatory, but we're being nice to customers
        'LANGUAGE',
        // post payment redirection: see chapter 8.2
        'ACCEPTURL',
        'DECLINEURL',
        'EXCEPTIONURL',
        'CANCELURL',
        // needed for payment redirection
        'PARAMPLUS',
    );


    /**
     * Parameter names used for computing the SHASign IN
     * @var     array
     */
    private static $arrFieldShasignIn = array(
        'ACCEPTANCE',
        'ACCEPTURL',
        'ADDMATCH',
        'ADDRMATCH',
        'AIAGIATA',
        'AIAIRNAME',
        'AIAIRTAX',
        'AIBOOKIND*XX*',
        'AICARRIER*XX*',
        'AICHDET',
        'AICLASS*XX*',
        'AICONJTI',
        'AIDEPTCODE',
        'AIDESTCITY*XX*',
        'AIDESTCITYL*XX*',
        'AIEXTRAPASNAME*XX*',
        'AIEYCD',
        'AIFLDATE*XX*',
        'AIFLNUM*XX*',
        'AIGLNUM',
        'AIINVOICE',
        'AIIRST',
        'AIORCITY*XX*',
        'AIORCITYL*XX*',
        'AIPASNAME',
        'AIPROJNUM',
        'AISTOPOV*XX*',
        'AITIDATE',
        'AITINUM',
        'AITINUML*XX*',
        'AITYPCH',
        'AIVATAMNT',
        'AIVATAPPL',
        'ALIAS',
        'ALIASOPERATION',
        'ALIASUSAGE',
        'ALLOWCORRECTION',
        'AMOUNT',
        'AMOUNT*XX*',
        'AMOUNTHTVA',
        'AMOUNTTVA',
        'BACKURL',
        'BATCHID',
        'BGCOLOR',
        'BLVERNUM',
        'BRAND',
        'BRANDVISUAL',
        'BUTTONBGCOLOR',
        'BUTTONTXTCOLOR',
        'CANCELURL',
        'CARDNO',
        'CATALOGURL',
        'CAVV_3D',
        'CAVVALGORITHM_3D',
        'CERTID',
        'CHECK_AAV',
        'CIVILITY',
        'CN',
        'COM',
        'COMPLUS',
        'COSTCENTER',
        'COSTCODE',
        'CREDITCODE',
        'CUID',
        'CURRENCY',
        'CVC',
        'CVCFLAG',
        'DATA',
        'DATATYPE',
        'DATEIN',
        'DATEOUT',
        'DECLINEURL',
        'DEVICE',
        'DISCOUNTRATE',
        'DISPLAYMODE',
        'ECI',
        'ECI_3D',
        'ECOM_BILLTO_POSTAL_CITY',
        'ECOM_BILLTO_POSTAL_COUNTRYCODE',
        'ECOM_BILLTO_POSTAL_NAME_FIRST',
        'ECOM_BILLTO_POSTAL_NAME_LAST',
        'ECOM_BILLTO_POSTAL_POSTALCODE',
        'ECOM_BILLTO_POSTAL_STREET_LINE1',
        'ECOM_BILLTO_POSTAL_STREET_LINE2',
        'ECOM_BILLTO_POSTAL_STREET_NUMBER',
        'ECOM_CONSUMERID',
        'ECOM_CONSUMER_GENDER',
        'ECOM_CONSUMEROGID',
        'ECOM_CONSUMERORDERID',
        'ECOM_CONSUMERUSERALIAS',
        'ECOM_CONSUMERUSERPWD',
        'ECOM_CONSUMERUSERID',
        'ECOM_PAYMENT_CARD_EXPDATE_MONTH',
        'ECOM_PAYMENT_CARD_EXPDATE_YEAR',
        'ECOM_PAYMENT_CARD_NAME',
        'ECOM_PAYMENT_CARD_VERIFICATION',
        'ECOM_SHIPTO_COMPANY',
        'ECOM_SHIPTO_DOB',
        'ECOM_SHIPTO_ONLINE_EMAIL',
        'ECOM_SHIPTO_POSTAL_CITY',
        'ECOM_SHIPTO_POSTAL_COUNTRYCODE',
        'ECOM_SHIPTO_POSTAL_NAME_FIRST',
        'ECOM_SHIPTO_POSTAL_NAME_LAST',
        'ECOM_SHIPTO_POSTAL_NAME_PREFIX',
        'ECOM_SHIPTO_POSTAL_POSTALCODE',
        'ECOM_SHIPTO_POSTAL_STREET_LINE1',
        'ECOM_SHIPTO_POSTAL_STREET_LINE2',
        'ECOM_SHIPTO_POSTAL_STREET_NUMBER',
        'ECOM_SHIPTO_TELECOM_FAX_NUMBER',
        'ECOM_SHIPTO_TELECOM_PHONE_NUMBER',
        'ECOM_SHIPTO_TVA',
        'ED',
        'EMAIL',
        'EXCEPTIONURL',
        'EXCLPMLIST',
        'EXECUTIONDATE*XX*',
        'FACEXCL*XX*',
        'FACTOTAL*XX*',
        'FIRSTCALL',
        'FLAG3D',
        'FONTTYPE',
        'FORCECODE1',
        'FORCECODE2',
        'FORCECODEHASH',
        'FORCEPROCESS',
        'FORCETP',
        'GENERIC_BL',
        'GIROPAY_ACCOUNT_NUMBER',
        'GIROPAY_BLZ',
        'GIROPAY_OWNER_NAME',
        'GLOBORDERID',
        'GUID',
        'HDFONTTYPE',
        'HDTBLBGCOLOR',
        'HDTBLTXTCOLOR',
        'HEIGHTFRAME',
        'HOMEURL',
        'HTTP_ACCEPT',
        'HTTP_USER_AGENT',
        'INCLUDE_BIN',
        'INCLUDE_COUNTRIES',
        'INVDATE',
        'INVDISCOUNT',
        'INVLEVEL',
        'INVORDERID',
        'ISSUERID',
        'IST_MOBILE',
        'ITEM_COUNT',
        'ITEMATTRIBUTES*XX*',
        'ITEMCATEGORY*XX*',
        'ITEMCOMMENTS*XX*',
        'ITEMDESC*XX*',
        'ITEMDISCOUNT*XX*',
        'ITEMID*XX*',
        'ITEMNAME*XX*',
        'ITEMPRICE*XX*',
        'ITEMQUANT*XX*',
        'ITEMUNITOFMEASURE*XX*',
        'ITEMVAT*XX*',
        'ITEMVATCODE*XX*',
        'ITEMWEIGHT*XX*',
        'LANGUAGE',
        'LEVEL1AUTHCPC',
        'LIDEXCL*XX*',
        'LIMITCLIENTSCRIPTUSAGE',
        'LINE_REF',
        'LINE_REF1',
        'LINE_REF2',
        'LINE_REF3',
        'LINE_REF4',
        'LINE_REF5',
        'LINE_REF6',
        'LIST_BIN',
        'LIST_COUNTRIES',
        'LOGO',
        'MAXITEMQUANT*XX*',
        'MERCHANTID',
        'MODE',
        'MTIME',
        'MVER',
        'NETAMOUNT',
        'OPERATION',
        'ORDERID',
        'ORDERSHIPCOST',
        'ORDERSHIPTAX',
        'ORDERSHIPTAXCODE',
        'ORIG',
        'OR_INVORDERID',
        'OR_ORDERID',
        'OWNERADDRESS',
        'OWNERADDRESS2',
        'OWNERCTY',
        'OWNERTELNO',
        'OWNERTOWN',
        'OWNERZIP',
        'PAIDAMOUNT',
        'PARAMPLUS',
        'PARAMVAR',
        'PAYID',
        'PAYMETHOD',
        'PM',
        'PMLIST',
        'PMLISTPMLISTTYPE',
        'PMLISTTYPE',
        'PMLISTTYPEPMLIST',
        'PMTYPE',
        'POPUP',
        'POST',
        'PSPID',
        'PSWD',
        'REF',
        'REFER',
        'REFID',
        'REFKIND',
        'REF_CUSTOMERID',
        'REF_CUSTOMERREF',
        'REGISTRED',
        'REMOTE_ADDR',
        'REQGENFIELDS',
        'RTIMEOUT',
        'RTIMEOUTREQUESTEDTIMEOUT',
        'SCORINGCLIENT',
        'SETT_BATCH',
        'SID',
        'STATUS_3D',
        'SUBSCRIPTION_ID',
        'SUB_AM',
        'SUB_AMOUNT',
        'SUB_COM',
        'SUB_COMMENT',
        'SUB_CUR',
        'SUB_ENDDATE',
        'SUB_ORDERID',
        'SUB_PERIOD_MOMENT',
        'SUB_PERIOD_MOMENT_M',
        'SUB_PERIOD_MOMENT_WW',
        'SUB_PERIOD_NUMBER',
        'SUB_PERIOD_NUMBER_D',
        'SUB_PERIOD_NUMBER_M',
        'SUB_PERIOD_NUMBER_WW',
        'SUB_PERIOD_UNIT',
        'SUB_STARTDATE',
        'SUB_STATUS',
        'TAAL',
        'TAXINCLUDED*XX*',
        'TBLBGCOLOR',
        'TBLTXTCOLOR',
        'TID',
        'TITLE',
        'TOTALAMOUNT',
        'TP',
        'TRACK2',
        'TXTBADDR2',
        'TXTCOLOR',
        'TXTOKEN',
        'TXTOKENTXTOKENPAYPAL',
        'TYPE_COUNTRY',
        'UCAF_AUTHENTICATION_DATA',
        'UCAF_PAYMENT_CARD_CVC2',
        'UCAF_PAYMENT_CARD_EXPDATE_MONTH',
        'UCAF_PAYMENT_CARD_EXPDATE_YEAR',
        'UCAF_PAYMENT_CARD_NUMBER',
        'USERID',
        'USERTYPE',
        'VERSION',
        'WBTU_MSISDN',
        'WBTU_ORDERID',
        'WEIGHTUNIT',
        'WIN3DS',
        'WITHROOT',
    );

    /**
     * Parameter names used for computing the SHASign OUT
     * @var     array
     */
    private static $arrFieldShasignOut = array(
        'AAVADDRESS',
        'AAVCHECK',
        'AAVZIP',
        'ACCEPTANCE',
        'ALIAS',
        'AMOUNT',
        'BIN',
        'BRAND',
        'CARDNO',
        'CCCTY',
        'CN',
        'COMPLUS',
        'CREATION_STATUS',
        'CURRENCY',
        'CVCCHECK',
        'DCC_COMMPERCENTAGE',
        'DCC_CONVAMOUNT',
        'DCC_CONVCCY',
        'DCC_EXCHRATE',
        'DCC_EXCHRATESOURCE',
        'DCC_EXCHRATETS',
        'DCC_INDICATOR',
        'DCC_MARGINPERCENTAGE',
        'DCC_VALIDHOURS',
        'DIGESTCARDNO',
        'ECI',
        'ED',
        'ENCCARDNO',
        'IP',
        'IPCTY',
        'NBREMAILUSAGE',
        'NBRIPUSAGE',
        'NBRIPUSAGE_ALLTX',
        'NBRUSAGE',
        'NCERROR',
        'ORDERID',
        'PAYID',
        'PM',
        'SCO_CATEGORY',
        'SCORING',
        'STATUS',
        'SUBBRAND',
        'SUBSCRIPTION_ID',
        'TRXDATE',
        'VC',
    );


    /**
     * Creates and returns the HTML Form for requesting the payment service.
     *
     * The parameters in $uriparam are appended to the base index URI.
     * If empty, this defaults to "section=Shop&cmd=success".
     *
     * @access  public
     * @global  array       $_ARRAYLANG
     * @param   array       $arrFields      The parameter array
     * @param   string      $submitValue    The optional label for the submit button
     * @param   boolean     $autopost       If true, the form is automatically submitted. Defaults to false.
     * @param   array       $arrSettings    Settings from \Cx\Core\Setting
     * @param   object      $landingPage    The optional URI parameter string
     * @return  string                      The HTML form code
     */
    static function getForm($arrFields, $submitValue='Send', $autopost=false, $arrSettings=null, $landingPage=null)
    {
        global $_ARRAYLANG;

        if ((gettype($landingPage) != 'object') || (get_class($landingPage) != 'Cx\Core\ContentManager\Model\Entity\Page')) {
            self::$arrError[] = 'No landing page passed.';
        }

        if (($sectionName = $landingPage->getModule()) && !empty($sectionName)) {
            self::$sectionName = $sectionName;
        } else {
            self::$arrError[] = 'Passed landing page is not an application.';
        }

        if (empty($arrSettings)) {
            $settingDb = \Cx\Core\Setting\Controller\Setting::getArray(self::$sectionName, 'config');
            if (!empty($settingDb) && $settingDb['postfinance_active']['value']) {
                $arrSettings = $settingDb;
            } else {
                self::$arrError[] = "Could not load settings.";
            }
        }

        if (empty($arrFields['PSPID'])) {
            $arrFields['PSPID'] = $arrSettings['postfinance_shop_id']['value'];
        }
        if (empty($arrFields['OPERATION'])) {
            $arrFields['OPERATION'] = $arrSettings['postfinance_authorization_type']['value'];
        }
        if (empty($arrFields['LANGUAGE'])) {
            $shortCode = FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
            if (!strpos($shortCode, '-')) { // short code only contains iso1, like 'de' or 'en'
                $arrFields['LANGUAGE'] = strtolower($shortCode).'_'.strtoupper($shortCode);
            } else { // short code contains alpha2 code as well, like 'de-DE' or 'en-GB'
                $arrFields['LANGUAGE'] = str_replace('-', '_', $shortCode);
            }
        }

        $baseUri = Cx\Core\Routing\Url::fromPage($landingPage)->toString().'?result=';
        if (empty($arrFields['ACCEPTURL'])) {
            $arrFields['ACCEPTURL'] = $baseUri.'1';
        }
        if (empty($arrFields['DECLINEURL'])) {
            $arrFields['DECLINEURL'] = $baseUri.'2';
        }
        if (empty($arrFields['EXCEPTIONURL'])) {
            $arrFields['EXCEPTIONURL'] = $baseUri.'2';
        }
        if (empty($arrFields['CANCELURL'])) {
            $arrFields['CANCELURL'] = $baseUri.'0';
        }
        if (empty($arrFields['BACKURL'])) {
            $arrFields['BACKURL'] = $baseUri.'2';
        }


        if (!self::setFields($arrFields)) {
            self::$arrError[] = 'Failed to verify keys.';
            return false;
        }
        $arrFields['SHASIGN'] = self::signature($arrFields, $arrSettings['postfinance_hash_signature_in']['value']);

        $server = $arrSettings['postfinance_use_testserver']['value'] ? 'test' : 'prod';
        $charset = (CONTREXX_CHARSET == 'UTF-8') ? '_utf8' : '';

        $hiddenFields = '';
        foreach ($arrFields as $name => $value) {
            $hiddenFields .= Html::getHidden($name, $value);
        }

        $autoSubmit = !$autopost ? '' : '
            <script type="text/javascript">
            /* <![CDATA[ */
                document.yellowpay.submit();
            /* ]]> */
            </script>
        ';

        $form =
            $_ARRAYLANG['TXT_ORDER_LINK_PREPARED'].'<br/><br/>'.
            '<form name="yellowpay" method="post" action="https://e-payment.postfinance.ch/ncol/'.$server.'/orderstandard'.$charset.'.asp">'.
                $hiddenFields.
                '<input type="submit" name="go" value="'.$submitValue.'" />'.
            '</form>'.
            $autoSubmit;

        return $form;
    }


    /**
     * Sets the parameters with name/value pairs from the given array
     *
     * If $arrField is missing mandatory fields, or contains invalid values,
     * this method fails and returns false.
     * @param   array     $arrField     The parameter array, by reference
     * @return  boolean                 True on success, false otherwise
     */
    static function setFields(&$arrField=null)
    {
        if (empty($arrField)) {
            self::$arrError[] = "Empty parameter array";
            return false;
        }
        if (!is_array($arrField)) {
            self::$arrError[] = "Parameter must be an array";
            return false;
        }
        foreach (self::$arrFieldMandatory as $name) {
            if (empty($arrField[$name])) {
                self::$arrError[] = "Missing mandatory name '$name'";
                return false;
            }
        }

        self::prependSectionNameToOrderId($arrField);

        foreach (array_keys($arrField) as $name) {
            $value = $arrField[$name];
            unset($arrField[$name]);
            $name = strtoupper($name);
            $value = self::verifyParameter($name, $value);
            if ($value === null) {
                self::$arrError[] = "Invalid value '$value' for name '$name'";
                return false;
            }
            $arrField[$name] = $value;
        }
        return true;
    }

    /**
     * Prepend section name to the field "ORDERID".
     * This is needed to avoid id conflicts (since multiple modules use the same Yellowpay account).
     *
     * @param   array   $arrField   The parameter array, by reference
     */
    static function prependSectionNameToOrderId(&$arrField=null)
    {
        $arrField['ORDERID'] = self::$sectionName.'_'.$arrField['ORDERID'];
    }

    /**
     * Verifies a name/value pair
     *
     * May change the value before returning it.
     * Use the value returned when adding to the form in any case.
     * @access  private
     * @param   string    $name     The name of the parameter
     * @param   string    $value    The value of the parameter
     * @return  boolean             The verified value on success,
     *                              null otherwise
     */
    static function verifyParameter($name, $value)
    {
        switch ($name) {
            // Mandatory
            case 'ORDERID':
                if ($value) return $value;
                break;
            case 'AMOUNT':
                // Fix cents, like "1.23" to "123"
                if (preg_match('/\./', $value)) {
                    $value = intval(bcmul($value, 100, 0));
                }
                if ($value === intval($value)) return $value;
                break;
            case 'CURRENCY':
                if (preg_match('/^\w{3}$/', $value)) return $value;
                break;
            case 'PSPID':
                if (preg_match('/.+/', $value)) return $value;
                break;
            // The above four are needed to form the hash:
            case 'SHASIGN':
                // 40 digit hexadecimal string, like
                // 4d0a445beac3561528dc26023e9ecb2d38fadc61
                if (preg_match('/^[0-9a-f]{40}$/i', $value)) return $value;
            case 'LANGUAGE':
                if (preg_match('/^\w{2}(?:_\w{2})?$/', $value)) return $value;
                break;
            case 'OPERATION':
                if ($value == 'RES' || $value == 'SAL') return $value;
                break;
            case 'ACCEPTURL':
            case 'DECLINEURL':
            case 'EXCEPTIONURL':
            case 'CANCELURL':
            case 'BACKURL':
//                if (FWValidator::isUri($value)) return $value;
// *SHOULD* verify the URIs, but the expression is not fit
                if ($value) return $value;
                break;
            // Optional
            // optional customer details, highly recommended for fraud prevention: see chapter 5.2
            case 'CN':
            case 'OWNERADDRESS':
            case 'OWNERCTY':
            case 'OWNERZIP':
            case 'OWNERTOWN':
            case 'OWNERTELNO':
            case 'COM':
                if (preg_match('/.*/', $value)) return $value;
                break;
            case 'EMAIL':
                if (FWValidator::isEmail($value)) return $value;
                break;
            case 'PMLIST':
                if (preg_match('/.*/', $value)) return $value;
                break;
            case 'WIN3DS':
                if ($value == 'MAINW' || $value = 'POPUP') return $value;
                break;
            // post payment parameters: see chapter 8.2
            case 'COMPLUS':
                if (preg_match('/.*/', $value)) return $value;
                break;
            case 'PARAMPLUS':
                if (preg_match('/.*/', $value)) return $value;
                break;
            // post payment parameters: see chapter 8.3
            case 'PARAMVAR':
                if (preg_match('/.*/', $value)) return $value;
                break;
            // optional operation field: see chapter 9.2
            case 'operation':
                if ($value == 'RES' || $value == 'SAL') return $value;
                break;
            // layout information: see chapter 7.1
            case 'TITLE':
            case 'BGCOLOR':
            case 'TXTCOLOR':
            case 'TBLBGCOLOR':
            case 'TBLTXTCOLOR':
            case 'BUTTONBGCOLOR':
            case 'BUTTONTXTCOLOR':
            case 'LOGO':
            case 'FONTTYPE':
                return $value;
            // dynamic template page: see chapter 7.2
            case 'TP':
                if (preg_match('/.+/', $value)) return $value;
                break;

            // Alias details: see Alias Management documentation
            case 'ALIAS':
                if (strlen($value) <= 40) return $value;
                break;
            case 'ALIASUSAGE':
                if (strlen($value) <= 255) return $value;
                break;
            case 'ALIASOPERATION':
                // Valid values: BYMERCHANT (or empty), BYPSP
                if (   $value == ''
                    || $value == 'BYMERCHANT'
                    || $value == 'BYPSP') return $value;
                break;

            // Cloudrexx does not yet supply nor support the following:
            // payment methods/page specifics: see chapter 9.1
            case 'PM':
            case 'BRAND':
            case 'PMLISTTYPE':
            // link to your website: see chapter 8.1
            case 'HOMEURL':
            case 'CATALOGURL':
            // optional extra login field: see chapter 9.3
            case 'USERID':
                break;
        }
        self::$arrError[] = "Unknown or unsupported field '$name' (value '$value')";
        return null;
    }


    /**
     * Returns the current SHA signature
     *
     * Concatenates the values of all fields, separating them with the secret
     * passphrase (in or out).
     * @param   array       $fields         The parameter array
     * @param   string      $passphrase     The passphrase
     * @param   boolean     $out            Create the OUT signature if true.
     *                                      Defaults to false (IN)
     * @return  string                      The signature hash on success,
     *                                      null otherwise
     */
    static function signature($fields, $passphrase, $out=false)
    {
        $hash_string = self::concatenateFields($fields, $passphrase, $out);
        $sha1 = strtoupper(sha1($hash_string));
        return $sha1;
    }


    /**
     * Returns a string formed by concatenating all fields
     *
     * Name/value pairs are separated by an equals sign, and individual pairs
     * separated by the passphrase (in or out).
     * Mind that according to the new specification, all field names must be
     * all uppercase, thus the array is reindexed using uppercase only keys
     * before it is sorted and concatenated.
     * @todo    Currently, all fields present in the $fields array are added
     *          to the string with SHASIGN being the only exception that is
     *          skipped.  It will probably be necessary to exclude further
     *          keys that are not used for computing the hash.
     * @param   array       $fields         The parameter array
     * @param   string      $passphrase     The passphrase
     * @param   boolean     $out            Create the OUT signature if true.
     *                                      Defaults to false (IN)
     * @return  string                      The signature string on success,
     *                                      null otherwise
     */
    static function concatenateFields($fields, $passphrase, $out=false)
    {
        $filter = array_flip($out
            ? self::$arrFieldShasignOut
            : self::$arrFieldShasignIn);
        $hash_string = '';
        foreach ($fields as $name => $value) {
            unset($fields[$name]);
            $name = strtoupper($name);
            if (isset ($filter[$name])) {
                $fields[$name] = $value;
            }
        }
        ksort($fields);
        foreach ($fields as $name => $value) {
            // NOTE: It's obviously correct and necessary to skip empty values,
            // although I find no mentioning of this in the documentation.
            // However, including parameters with empty values produces
            // invalid SHASigns!
            if ($value === '') {
                continue;
            }
            $hash_string .=
                $name.
                '='.
                $fields[$name].
                $passphrase;
        }
        return $hash_string;
    }


    /**
     * Verifies the parameters posted back by e-commerce
     * @param   string  $passphrase     The SHA-OUT passphrase
     * @return  boolean                 True on success, false otherwise
     */
    static function checkIn($passphrase)
    {
//DBG::activate(DBG_LOG_FILE);
//DBG::log("Yellowpay::checkIn(): POST: ".var_export($_POST, true));
//DBG::log("Yellowpay::checkIn(): GET: ".var_export($_GET, true));
        if (empty($_REQUEST['SHASIGN'])) {
            self::$arrError[] = 'No SHASIGN value in request';
            return false;
        }
        $arrField = contrexx_input2raw($_REQUEST);
        $shasign_request = $arrField['SHASIGN'];
        // If the hash is correct, so is the Order (and ID)
        $shasign_computed = self::signature($arrField, $passphrase, true);
//DBG::log("Yellowpay::checkIn(): SHA Request $shasign_request <> $shasign_computed ?");
        return ($shasign_request == $shasign_computed);
    }


    /**
     * Returns the order id from the request, if present
     *
     * @return  integer     The order id, or false
     */
    static function getOrderId()
    {
        if (isset($_REQUEST['orderID'])) {
            $orderId = explode('_', $_REQUEST['orderID']);
            return $orderId[1];
        } else {
            return false;
        }
    }


    /**
     * Returns a string with all currently accepted payment methods.
     *
     * This string is ready to be used in the PMLIST field of the payment form.
     * @param   string  $indices    The comma separated list of payment type
     *                              indices
     * @return  string              The payment type string
     */
    static function getAcceptedPaymentMethodsString($indices)
    {
        if (empty ($indices)) return '';
        $strAcceptedPaymentMethods = '';
        foreach (preg_split('/\s*,\s*/', $indices, null, PREG_SPLIT_NO_EMPTY)
                as $index) {
            if (empty (self::$arrKnownPaymentMethod[$index])) continue;
            $strAcceptedPaymentMethods .=
                ($strAcceptedPaymentMethods ? ';' : '').
                self::$arrKnownPaymentMethod[$index];
        }
        return $strAcceptedPaymentMethods;
    }


    /**
     * UNUSED (for the time being) --
     * Returns the HTML menu options for selecting from the currently accepted
     * payment methods.

     * The functionality had been in use in the egov module only, where it
     * has become obsolete.
     * @param   string  $indices        The comma separated list of payment type
     *                                  indices
     * @param   integer $selected       The optional preselected payment type
     *                                  index
     * @return  string                  The HTML menu options
     */
    static function getAcceptedPaymentMethodMenuOptions(
        $indices, $selected=null)
    {
        global $_ARRAYLANG;

        $strOptions = '';
        $arrIndices = preg_split('/\s*,\s*/', $indices, null, PREG_SPLIT_NO_EMPTY);
        foreach (self::$arrKnownPaymentMethod as $index => $strPaymentMethod) {
            if (!in_array($index, $arrIndices)) continue;
            $strOptions .=
                '<option value="'.$strPaymentMethod.'"'.
                ($strPaymentMethod == $selected
                    ? ' selected="selected"' : ''
                ).'>'.
                $_ARRAYLANG['TXT_SHOP_YELLOWPAY_'.strtoupper($strPaymentMethod)].
                '</option>';
        }
        return $strOptions;
    }


    /**
     * Returns HTML code for the authorization menuoptions
     * @global  array   $_ARRAYLANG
     * @param   string  $authorization  The selected authorization method
     * @return  string                  The HTML menuoptions
     */
    static function getAuthorizationMenuoptions($authorization)
    {
        global $_ARRAYLANG;

        return
            '<option value="SAL"'.
            ($authorization == 'SAL' ? ' selected="selected"' : '').'>'.
            $_ARRAYLANG['TXT_SHOP_YELLOWPAY_REQUEST_FOR_SALE'].
            '</option>'.
            '<option value="RES"'.
            ($authorization == 'RES' ? ' selected="selected"' : '').'>'.
            $_ARRAYLANG['TXT_SHOP_YELLOWPAY_REQUEST_FOR_AUTHORIZATION'].
            '</option>';
    }


    /**
     * Handles errors ocurring in this class
     *
     * Applies to the section (module) SettingsDb has been initialized with.
     * In particular, tries to add missing Settings using the defaults.
     * However, you will have to set them to their correct values after this.
     * Note that you *MUST* call \Cx\Core\Setting\Controller\Setting::init() using the proper section
     * and group parameters beforehand.  Otherwise, no settings will be added.
     */
    static function errorHandler()
    {
// Yellowpay
        \Cx\Core\Setting\Controller\Setting::errorHandler();
        // You *MUST* call this yourself beforehand, using the proper section!
        //\Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        // Signature: ($name, $value, $ord, $type, $values, $key)
        \Cx\Core\Setting\Controller\Setting::add('postfinance_shop_id', 'Ihr Kontoname',
                1, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('postfinance_active', '0',
                2, \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1');
        \Cx\Core\Setting\Controller\Setting::add('postfinance_authorization_type', 'SAL',
                3, \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'RES:Reservation,SAL:Verkauf');
// OBSOLETE
        // As it appears that in_array(0, $array) is true for each non-empty
        // $array, indices for the entries must be numbered starting at 1.
//        $arrPayments = array();
//        foreach (self::$arrKnownPaymentMethod as $index => $name) {
//            $arrPayments[$index] = $name;
//        }
//        \Cx\Core\Setting\Controller\Setting::add('postfinance_accepted_payment_methods', '',
//                4, \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOXGROUP,
//                \Cx\Core\Setting\Controller\Setting::joinValues($arrPayments));
        \Cx\Core\Setting\Controller\Setting::add('postfinance_hash_signature_in',
                'Mindestens 16 Buchstaben, Ziffern und Zeichen',
                5, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('postfinance_hash_signature_out',
                'Mindestens 16 Buchstaben, Ziffern und Zeichen',
                6, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT);
        \Cx\Core\Setting\Controller\Setting::add('postfinance_use_testserver', '1',
                7, \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1');

        // Always
        return false;
    }

}
