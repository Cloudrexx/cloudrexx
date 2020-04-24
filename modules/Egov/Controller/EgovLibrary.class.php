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
 * EgovLibrary
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_egov
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Egov\Controller;
// load validator class for Regex-Constants in static array $arrCheckTypes

/**
 * EgovLibrary
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_egov
 */
class EgovLibrary {

    private $arrForms;

    static $arrCheckTypes = array(
        1 => array(
            'regex' => '.*',
            'name' => 'TXT_EGOV_REGEX_EVERYTHING',
        ),
        2 => array(
            'regex' => \FWValidator::REGEX_EMAIL_JS,
            'name' => 'TXT_EGOV_REGEX_EMAIL',
            'modifiers' => 'i',
        ),
        3 => array(
            'regex' => \FWValidator::REGEX_URI_JS,
            'name' => 'TXT_EGOV_REGEX_URL',
            'modifiers' => 'i',
        ),
        4 => array(
            'regex'     => '^[a-zäàáüâûôñèöéè\ ]*[a-zäàáüâûôñèöéè]+[a-zäàáüâûôñèöéè\ ]*$',
            'name'      => 'TXT_CONTACT_REGEX_TEXT',
            'modifiers' => 'i'
        ),
        5 => array(
            'regex' => '^[0-9]*$',
            'name'  => 'TXT_EGOV_REGEX_NUMBERS',
        ),
    );


    /**
     * OBSOLETE
     *
     * Use GetProduktValue('product_name', $ProductID) instead.
     */
    function GetProduktName($ProductID)
    {
        die("Error: Obsolete method GetProduktName(\$ProductID=$ProductID) called.  Please use GetProduktValue('product_name', \$ProductID) instead.");
    }


    /**
     * Return the value in the field indicated by $FieldName
     * for the given product ID
     * @param   string  $FieldName    The field name
     * @param   integer $ProductID    The product ID
     * @return  string                The value from the database field
     *                                on success, the empty string otherwise
     * @static
     */
    static function GetProduktValue($FieldName, $ProductID)
    {
        global $objDatabase;

        $query = "
            SELECT `".contrexx_raw2db($FieldName)."`
              FROM `".DBPREFIX."module_egov_products`
             WHERE `product_id`=".intval($ProductID);
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) {
            return '';
        }
        return $objResult->fields[$FieldName];
    }


    /**
     * Return the value in the field indicated by $FieldName
     * for the given order ID
     * @param   string  $FieldName    The field name
     * @param   integer $order_id     The order ID
     * @return  string                The value from the database field
     *                                on success, the empty string otherwise
     * @static
     */
    static function GetOrderValue($FieldName, $order_id)
    {
        global $objDatabase;

        $query = "
            SELECT ".contrexx_raw2db($FieldName)."
              FROM ".DBPREFIX."module_egov_orders
             WHERE order_id=".intval($order_id);
        $objResult = $objDatabase->Execute($query);
        if ($objResult && $objResult->RecordCount() == 1) {
            return $objResult->fields[$FieldName];
        }
        return '';
    }


    /**
     * Search the order values for the order ID given for a valid
     * e-mail address and return the last one found, if any.
     *
     * Note that the specific behaviour of taking only the last address
     * into account seems to be by design. -- RK
     * @param   integer   $order_id   The order ID
     * @return  string                The e-mail address found, if any,
     *                                or the empty string
     * @static
     */
    static function GetEmailAdress($order_id)
    {
        global $objDatabase;

        $arrOrderValues = EgovLibrary::getOrderValues($order_id);
        $strEmail = '';
        foreach ($arrOrderValues as $value) {
            if (\FWValidator::isEmail($value)) {
                $strEmail = $value;
            }
        }
        return $strEmail;
    }


    static function ParseFormValues($Field='', $Values='')
    {
        $ValuesArray = explode(';;', $Values);
        $FormArray = array();
        $ArrayName = $ArrayValue = NULL;
        foreach ($ValuesArray as $value) {
            if (empty($value)) continue;
            list ($ArrayName, $ArrayValue) = explode('::', $value);
            $FormArray[$ArrayName] = $ArrayValue;
        }
        if (isset($FormArray[$Field])) {
            return $FormArray[$Field];
        }
        return '';
    }


    static function MaskState($State)
    {
        global $_ARRAYLANG;

        switch($State) {
            case 0:
                return $_ARRAYLANG['TXT_STATE_NEW'];
            case 1:
                return $_ARRAYLANG['TXT_STATE_OK'];
            case 2:
                return $_ARRAYLANG['TXT_STATE_DELETED'];
            case 3:
                // Used when alternative payment methods are selected
                return $_ARRAYLANG['TXT_STATE_ALTERNATIVE'];
            case 4:
                return $_ARRAYLANG['TXT_EGOV_ORDER_STATE_RESERVED'];
            default:
                return 'unknown';
        }
    }


    /**
     * Return the configuration setting from the database for the given name.
     * @param   string  $name     The name of the configuration setting
     * @return  string            The settings' value on success,
     *                            false otherwise
     * @static
     */
    static function GetSettings($name='')
    {
        global $objDatabase;

        $query = "
            SELECT `value`
              FROM ".DBPREFIX."module_egov_configuration
             WHERE `name`='$name'
        ";
        $objResult = $objDatabase->Execute($query);
        if ($objResult && $objResult->RecordCount() > 0) {
            return $objResult->fields['value'];
        }
        return false;
    }


    /**
     * Returns an array with available attributes for the form fields
     * related to the product ID given.
     * @param   integer   $id         The product ID
     * @return  mixed                 The field array on success,
     *                                false otherwise
     * @static
     */
    static function getFormFields($id)
    {
        global $objDatabase;

        $objResult  = $objDatabase->Execute("
            SELECT id, name, type, attributes, is_required, check_type, order_id
              FROM ".DBPREFIX."module_egov_product_fields
             WHERE product=".intval($id)."
             ORDER BY order_id
        ");
        if (!$objResult) {
            return false;
        }
        $arrFields = array();
        while (!$objResult->EOF) {
            $arrFields[$objResult->fields['id']] = array(
                'name' => $objResult->fields['name'],
                'type' => $objResult->fields['type'],
                'attributes' => $objResult->fields['attributes'],
                'is_required' => $objResult->fields['is_required'],
                'check_type' => $objResult->fields['check_type'],
                'order_id' => $objResult->fields['order_id']
            );
            $objResult->MoveNext();
        }
        return $arrFields;
    }


    /**
     * Returns an array with all form values stored with the order.
     *
     * Note that the names need to be unique for this to work!
     * @param   integer   $order_id   The order ID
     * @return  array                 The array with name/value pairs
     * @static
     */
    static function getOrderValues($order_id)
    {
        $arrResult = array();
        $order_values = EgovLibrary::GetOrderValue('order_values', $order_id);
        $arrFields = preg_split('/;;/', $order_values, null, PREG_SPLIT_NO_EMPTY);
        $name = $value = NULL;
        foreach ($arrFields as $field) {
            list ($name, $value) = explode('::', $field);
            if ($name != '') {
                $arrResult[$name] = $value;
            }
        }
        return $arrResult;
    }


    function initContactForms()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            SELECT product_id, product_name, product_desc,
                   product_price, product_per_day, product_quantity,
                   product_target_email, product_target_url, product_message,
                   COUNT(order_id) AS number, MAX(order_date) AS last
              FROM ".DBPREFIX."module_egov_products
              LEFT OUTER JOIN ".DBPREFIX."module_egov_orders
                ON product_id=order_product
             GROUP BY product_id
             ORDER BY last DESC
        ");
        if (!$objResult) {
            return false;
        }
        $this->arrForms = array();
        while (!$objResult->EOF) {
            $this->arrForms[$objResult->fields['product_id']] = array(
                'product_name' => $objResult->fields['product_name'],
                'product_desc' => $objResult->fields['product_desc'],
                'product_price' => intval($objResult->fields['product_price']),
                'product_per_day' => $objResult->fields['product_per_day'],
                'product_quantity' => intval($objResult->fields['product_quantity']),
                'product_target_email' => $objResult->fields['product_target_email'],
                'product_target_url' => $objResult->fields['product_target_url'],
                'product_message' => $objResult->fields['product_message']
            );
            $objResult->MoveNext();
        }
        return true;
    }


    static function _QuantityDropdown()
    {
        return
            '<select name="contactFormField_Quantity" '.
            'id="contactFormField_Quantity" '.
            'onchange="changeDropdown(document.getElementById(\'CalDate\').value);">'.
            '</select>';
    }


    static function _QuantityDropdownKids() {
        return
            '<select name="contactFormField_Quantity_Kids" '.
            'id="contactFormField_Quantity_Kids" '.
            'onchange="changeDropdown(document.getElementById(\'CalDate\').value);">'.
            '</select>';
    }


    static function _GetOrdersQuantityArray($id, $datum='')
    {
        global $objDatabase;

        $JSquantityArray = '';
        if ($datum == '') {
            $datum = date('Y-m');
        } else {
            $dat1 = substr($datum, 0, 4);
            $dat2 = substr($datum, 4, 2);
            $datum = "$dat1-$dat2";
        }
        for ($x = 1; $x <= 31; ++$x) {
            $daydate = sprintf('%02u', $x);
            $datumToSend = "$datum-$daydate";
            $JSquantityArray .=
                'DayArray['.$x.'] = '.
                EgovLibrary::_GetOrderedQuantity($id, $datumToSend).";\n";
        }
        return $JSquantityArray;
    }


    static function _GetOrderedQuantity($id, $datum)
    {
        global $objDatabase;

        $year = $month = $day = NULL;
        list ($year, $month, $day) = explode('-', $datum);
        $query = "
            SELECT count(*) AS anzahl
              FROM ".DBPREFIX."module_egov_product_calendar
             WHERE calendar_day=".intval($day)."
               AND calendar_month=".intval($month)."
               AND calendar_year=".intval($year)."
               AND calendar_act=1
               AND calendar_product=".intval($id)."
        ";
        $objResult = $objDatabase->Execute($query);
        return $objResult->fields['anzahl'];
    }


    /**
     * Get the Egov custom fields source code
     *
     * @param integer $id           Product id
     * @param boolean $preview      Is preview mode
     * @param boolean $flagBackend  Is backend form requested
     *
     * @return String form soruce code
     */
    function getSourceCode($id, $preview = false, $flagBackend = false)
    {
        global $_ARRAYLANG, $_CORELANG;

        $arrFields = EgovLibrary::getFormFields($id);
        $flagYellowbill = false;
        $yellowpayEnabled =
            EgovLibrary::GetProduktValue('yellowpay', $id);
        $strCalendarSource = '';
        if (EgovLibrary::GetProduktValue('product_per_day', $id) == 'yes') {
            $strCalendarSource = $this->getCalendarSource($id, $flagBackend);
        }
        $FormActionTarget = ($preview ? '../' : '')
            . ($flagBackend
                ? "index.php?cmd=Egov&amp;act=detail&amp;id=$id"
                : \Cx\Core\Routing\Url::fromModuleAndCmd('Egov', 'detail', '', array('id' => $id))
            );

        $captchaError = '';
        if (   !$flagBackend
            && isset($_POST['send'])
            && !\FWUser::getFWUserObject()->objUser->login()
            && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()
        ) {
            $captchaError = '<div class="error text-danger">'. $_ARRAYLANG['TXT_EGOV_CAPTCHA_ERROR'] .'</div>';
        }

        //$sourcecode = $this->_getJsSourceCode($id, $arrFields, $preview, $flagBackend).
        $sourcecode = $this->_getJsSourceCode($arrFields, $preview, $flagBackend).
// TODO: This index is never set
//            $this->arrForms[$id]['text'].
            "\n".
            "<div id=\"contactFormError\" style=\"color: red; display: none;\">".
            "<br />".$_ARRAYLANG['TXT_EGOV_CHECK_YOUR_INPUT'].
            "</div>\n<br />\n".
            $captchaError.
            "<!-- BEGIN contact_form -->\n".
            "<form action=\"$FormActionTarget\" ".
            "method=\"post\" enctype=\"multipart/form-data\" ".
            "onsubmit=\"return checkAllFields();\" id=\"contactForm\">\n".
            "<input type=\"hidden\" name=\"send\" value=\"1\" />".
//            "<input type=\"hidden\" name=\"paypal\" value=\"".EgovLibrary::GetProduktValue('product_paypal', $id)."\" />".
            $strCalendarSource.
            "<br />\n";
        $i = 1;

            $formTemplate = <<<FORMTEMPLATE
    <div class="table-responsive">
        <table class="table" summary="" border="0">
            <!-- BEGIN product_fields -->
            <tr class="{EGOV_PRODUCT_ROW_CLASS}">
                <td>{EGOV_PRODUCT_FIELD_LABEL} {EGOV_PRODUCT_FIELD_REQUIRED}</td>
                <td>{EGOV_PRODUCT_FIELD}</td>
            </tr>
            <!-- END product_fields -->
        </table>
    </div>
FORMTEMPLATE;
        $template = new \Cx\Core\Html\Sigma('.');
        $template->setTemplate($formTemplate);

        foreach ($arrFields as $fieldId => $arrField) {
            $feldbezeichnung = $arrField['name'];
            if ($feldbezeichnung == "AGB") {
                if ($flagBackend) {
                    continue;
                }
                $feldbezeichnung = '<a href="index.php?section=Agb" target="_blank">AGB akzeptieren</a>';
            }
            $rowClass   = ($flagBackend ? ' class="row' . (( ++$i % 2) + 1) . '"' : '');
            $label      = ($arrField['type'] != 'hidden' && $arrField['type'] != 'label' ? $feldbezeichnung : '&nbsp;');
            $required   = ($arrField['is_required'] ? ' <span style="color: red;">*</span>' : '');
            $inputField = '';
            $value      = isset($_POST['contactFormField_'.$fieldId])
                         ? contrexx_input2raw($_POST['contactFormField_'.$fieldId])
                         : (!isset($_POST['submitContactForm']) ? $arrField['attributes'] : '');
            switch ($arrField['type']) {
                case 'text':
                    $inputField = \Html::getInputText('contactFormField_'.$fieldId, $value);
                    break;
                case 'label':
                    $inputField = $value;
                    break;
                case 'checkbox':
                    $inputField = \Html::getCheckbox('contactFormField_'. $fieldId, 1, '', $value);
                    break;
                case 'checkboxGroup':
                    $options = explode(',', $arrField['attributes']);
                    $inputField = '';
                    $nr = 0;
                    $selectedArray = isset($_POST['contactFormField_'.$fieldId])
                                    ? contrexx_input2raw($_POST['contactFormField_'.$fieldId])
                                    : array();
                    foreach ($options as $option) {
                        $inputField .=
                                "<span class=\"checkbox\"><label for=\"contactFormField_{$nr}_$fieldId\"><input type=\"checkbox\" " .
                                "name=\"contactFormField_{$fieldId}[]\" " .
                                "id=\"contactFormField_{$nr}_$fieldId\" " .
                                (in_array($option, $selectedArray) ? 'checked="checked"' : '').
                                "value=\"". contrexx_raw2xhtml($option) ."\" />" .
                                "". contrexx_raw2xhtml($option) ."</label></span>\n";
                        ++$nr;
                    }
                    break;
                case 'file':
                    $inputField =
                            "<input type=\"file\" " .
                            "name=\"contactFormField_$fieldId\" />\n";
                    break;
                case 'hidden':
                    $inputField = \Html::getHidden('contactFormField_' . $fieldId, $value);
                    break;
                case 'password':
                    $inputField = \Html::getInputPassword('contactFormField_'. $fieldId, '');
                    break;
                case 'radio':
                    $options = explode(',', $arrField['attributes']);
                    $nr = 0;
                    $inputField = '';
                    foreach ($options as $option) {
                        $inputField .=
                            "<span class=\"radio\"><label for=\"contactFormField_{$nr}_$fieldId\">" .
                            "<input type=\"radio\" name=\"contactFormField_$fieldId\" id=\"contactFormField_{$nr}_$fieldId\"" .
                            ($option == $value ? 'checked="checked"' : '') .
                            " value=\"". contrexx_raw2xhtml($option) ."\" />" .
                            "". contrexx_raw2xhtml($option) ."</label></span>\n";
                        ++$nr;
                    }
                    break;
                case 'select':
                    $options = explode(',', $arrField['attributes']);
                    $nr = 0;
                    $inputField = "<select class=\"form-control\" name=\"contactFormField_$fieldId\">";
                    foreach ($options as $option) {
                        $selected    = $option == $value ? 'selected="selected"' : '';
                        $inputField .= "<option $selected >". $option ."</option>";
                    }
                    $inputField .= "</select>";
                    break;
                case 'textarea':
                    $inputField = \Html::getTextarea('contactFormField_'. $fieldId, $value, '', '', 'style="height:100px;"');
                    break;
            }
            $template->setVariable(array(
                'EGOV_PRODUCT_ROW_CLASS'        => $rowClass,
                'EGOV_PRODUCT_FIELD_LABEL'      => $label,
                'EGOV_PRODUCT_FIELD_REQUIRED'   => $required,
                'EGOV_PRODUCT_FIELD'            => $inputField,
            ));
            $template->parse('product_fields');
        }

        // Add payment selection or hidden fields here,
        // according to price and payment settings.
        $paymentPaypal    = EgovLibrary::GetProduktValue('product_paypal', $id);
        $paymentYellowpay = EgovLibrary::GetProduktValue('yellowpay', $id);
        $paymentPrice     = EgovLibrary::GetProduktValue('product_price', $id);
        $strAlternativePaymentMethods = EgovLibrary::GetProduktValue('alternative_names', $id);
        // Using the $flagBackend flag to disable payment in the backend
        if (   $flagBackend === false
            && $paymentPrice > 0
            && ($paymentYellowpay || $paymentPaypal || !empty($strAlternativePaymentMethods))
        ) {
            $inputField =
                    '<select style="width: 306px;" name="handler" id="handler" ' .
                    'onchange="toggleYellowpayFields();">';
            if ($paymentYellowpay) {
                // Yellowpay is enabled
                $inputField .=
                        '<option value="PostFinance">' .
                        $_ARRAYLANG['TXT_EGOV_POSTFINANCE'] . '</option>';
            }
            if ($paymentPaypal) {
                // PayPal is enabled
                $inputField .=
                        '<option value="paypal">' . $_ARRAYLANG['TXT_EGOV_PAYPAL'] . '</option>';
            }
            // Alternative payment methods
            $arrAlternativePaymentMethods = preg_split(
                    '/\s*,\s*/', $strAlternativePaymentMethods, 0, PREG_SPLIT_NO_EMPTY
            );
            foreach ($arrAlternativePaymentMethods as $strPaymentMethod) {
                $inputField .=
                        '<option value="$strPaymentMethod">' .
                        $strPaymentMethod .
                        '</option>';
            }
            $inputField .= '</select>';
            $template->setVariable(array(
                'EGOV_PRODUCT_ROW_CLASS'        => '',
                'EGOV_PRODUCT_FIELD_LABEL'      => $_ARRAYLANG['TXT_EGOV_PAYMENT_HANDLER'],
                'EGOV_PRODUCT_FIELD_REQUIRED'   => '',
                'EGOV_PRODUCT_FIELD'            => $inputField,
            ));
            $template->parse('product_fields');
        }

        if (   !$flagBackend
            && !\FWUser::getFWUserObject()->objUser->login()
        ) {
            $template->setVariable(array(
                'EGOV_PRODUCT_ROW_CLASS'        => '',
                'EGOV_PRODUCT_FIELD_LABEL'      => $_CORELANG['TXT_CORE_CAPTCHA'],
                'EGOV_PRODUCT_FIELD_REQUIRED'   => '',
                'EGOV_PRODUCT_FIELD'            => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
            ));
            $template->parse('product_fields');
        }

        if (count($arrFields) > 0) {
            $inputField =
                "<br /><input type=\"reset\" class=\"btn btn-default egov-btn\" value=\"".
                $_ARRAYLANG['TXT_EGOV_DELETE']."\" />\n".
                "<input type=\"submit\" name=\"submitContactForm\" class=\"btn btn-default\" value=\"".
                $_ARRAYLANG['TXT_EGOV_SUBMIT']."\" />\n";
            $template->setVariable(array(
                'EGOV_PRODUCT_ROW_CLASS'        => '',
                'EGOV_PRODUCT_FIELD_LABEL'      => '&nbsp;',
                'EGOV_PRODUCT_FIELD_REQUIRED'   => '',
                'EGOV_PRODUCT_FIELD'            => $inputField,
            ));
            $template->parse('product_fields');
        }
        $sourcecode .= $template->get();
        $sourcecode .=
            "\n</form>".
            ($flagYellowbill
              ? "<script type=\"text/javascript\">\n".
                "/* <![CDATA[ */\n".
                "  toggleYellowpayFields();".
                "/* ]]> */\n".
                "</script>\n"
              : ''
            ).
            "<!-- END contact_form -->\n";
        return $sourcecode;
    }


    static function _getJsSourceCode($formFields, $preview=false, $flagBackend=false)
    {
        $code =
            "<script type=\"text/javascript\">\n".
            "// <![CDATA[\n".
            "fields = new Array();\n";
        foreach ($formFields as $key => $field) {
            $code .=
                "fields[$key] = Array(\n".
                "  '{$field['name']}',\n".
                "  {$field['is_required']},\n";
            if ($preview) {
                $code .= "  '".
                addslashes(EgovLibrary::$arrCheckTypes[$field['check_type']]['regex']).
                "',\n";
            } elseif ($flagBackend) {
                $code .= "  '".
                addslashes(EgovLibrary::$arrCheckTypes[$field['check_type']]['regex']).
                "',\n";
            } else {
                $code .= "  '".
                addslashes(EgovLibrary::$arrCheckTypes[$field['check_type']]['regex']).
                "',\n";
            }
            $code .= "  '".$field['type']."');\n";
        }
        /*
        if (EgovLibrary::GetProduktValue('product_per_day', $_REQUEST['id'] == 'yes')) {
            $code .= "fields[1000] = Array('Datum', 1, '', 'text');\n";
        }
        */
        $code .=
            "var readBefore = false;\n".
            "var borderBefore = \"\";\n".

            "\nfunction checkAllFields() {\n".
            "    var isOk = true;\n".
            "    for (var field in fields) {\n".
            "        if (!readBefore) {\n".
            "            if (document.getElementsByName('contactFormField_' + field)[0]) {borderBefore = document.getElementsByName('contactFormField_' + field)[0].style.border;} else {borderBefore = '#000000';}\n".
            "            readBefore = true;\n".
            "        }\n\n".

            "        var type = fields[field][3];\n".
            "        if (type == 'text' || type == 'file' || type == 'password' || type == 'textarea') {\n".
            "            value = document.getElementsByName('contactFormField_' + field)[0].value;\n".
            "            if (value == \"\" && isRequiredNorm(fields[field][1], value)) {\n".
            "                isOk = false;\n".
            "                document.getElementsByName('contactFormField_' + field)[0].style.border = \"red 1px solid\";\n".
            "            } else if (value != \"\" && !matchType(fields[field][2], value)) {\n".
            "                isOk = false;\n".
            "                document.getElementsByName('contactFormField_' + field)[0].style.border = \"red 1px solid\";\n".
            "            } else {\n".
            "                document.getElementsByName('contactFormField_' + field)[0].style.border = borderBefore;\n".
            "            }\n".
            "        } else if (type == 'checkbox') {\n".
            "            if (!isRequiredCheckbox(fields[field][1], field)) {\n".
            "                isOk = false;\n".
            "            }\n".
            "        } else if (type == 'checkboxGroup') {\n".
            "            if (!isRequiredCheckBoxGroup(fields[field][1], field)) {\n".
            "                isOk = false;\n".
            "            }\n".
            "        } else if (type == 'radio') {\n".
            "            if (!isRequiredRadio(fields[field][1], field)) {\n".
            "                isOk = false;\n".
            "            }\n".
            "        }\n".
            "    }\n\n".
            "    if (!isOk) {\n".
            "        document.getElementById('contactFormError').style.display = \"block\";\n".
            "    } else {\n".
            "        if (document.getElementById('bill1')) {\n".
            "            document.getElementById('bill1').value = document.getElementById('yellow1').value;\n".
            "            document.getElementById('bill2').value = document.getElementById('yellow2').value;\n".
            "            document.getElementById('bill3').value = document.getElementById('yellow3').value;\n".
            "            document.getElementById('bill4').value = document.getElementById('yellow4').value;\n".
            "            document.getElementById('bill5').value = document.getElementById('yellow5').value;\n".
            "            document.getElementById('bill6').value = document.getElementById('yellow6').value;\n".
            "        }\n".
            "    }\n".
            "    return isOk;\n".
            "}\n\n".

        // This is for checking normal text input field if they are required.
        // If yes, it also checks if the field is set. If it is not set, it returns true.
        // Uses a hack to skip Yellowbill fields when this payment method
        // is not selected.
            "function isRequiredNorm(required, value) {\n".
            "    if (   (   required == -1\n".
            "            && document.getElementById('handler')\n".
            "            && document.getElementById('handler').value == 'yellowbill')\n".
            "        ||  required == 1) {\n".
            "        if (value == \"\") {\n".
            "            return true;\n".
            "        }\n".
            "    }\n".
            "    return false;\n".
            "}\n\n".

        // Matches the type of the value and pattern. Returns true if it matched, false if not.
            "function matchType(pattern, value) {\n".
            "    var reg = new RegExp(pattern);\n".
            "    if (value.match(reg)) {\n".
            "        return true;\n".
            "    }\n".
            "    return false;\n".
            "}\n\n".

        // Checks if a checkbox is required but not set. Returns false when finding an error.
            "function isRequiredCheckbox(required, field) {\n".
            "    if (document.getElementsByName('contactFormField_' + field).length == 0) {\n".
            "        return true;\n".
            "    }\n".
            "    if (required == 1) {\n".
            "        if (!document.getElementsByName('contactFormField_' + field)[0].checked) {\n".
            "            document.getElementsByName('contactFormField_' + field)[0].style.border = \"red 1px solid\";\n".
            "            return false;\n".
            "        }\n".
            "    }\n".
            "    document.getElementsByName('contactFormField_' + field)[0].style.border = borderBefore;\n".
            "    return true;\n".
            "}\n\n".

        // Checks if a multiple checkbox is required but not set. Returns false when finding an error.
            "function isRequiredCheckBoxGroup(required, field) {\n".
            "    if (required == true) {\n".
            "        var boxes = document.getElementsByName('contactFormField_' + field + '[]');\n".
            "        var checked = false;\n".
            "        for (var i = 0; i < boxes.length; i++) {\n".
            "             if (boxes[i].checked) {\n".
            "                checked = true;\n".
            "            }\n".
            "        }\n".
            "        if (checked) {\n".
            "            setListBorder('contactFormField_' + field + '[]', borderBefore);\n".
            "            return true;\n".
            "        } else {\n".
            "            setListBorder('contactFormField_' + field + '[]', '1px red solid');\n".
            "            return false;\n".
            "        }\n".
            "    } else {\n".
            "        return true;\n".
            "    }\n".
            "}\n\n".

        // Checks if some radio button need to be checked. Returns false if it finds an error
            "function isRequiredRadio(required, field) {\n".
            "    if (required == 1) {\n".
            "        var buttons = document.getElementsByName('contactFormField_' + field);\n".
            "        var checked = false;\n".
            "        for (var i = 0; i < buttons.length; i++) {\n".
            "            if (buttons[i].checked) {\n".
            "                checked = true;\n".
            "            }\n".
            "        }\n".
            "        if (checked) {\n".
            "            setListBorder('contactFormField_' + field, borderBefore);\n".
            "            return true;\n".
            "        } else {\n".
            "            setListBorder('contactFormField_' + field, '1px red solid');\n".
            "            return false;\n".
            "        }\n".
            "    } else {\n".
            "        return true;\n".
            "    }\n".
            "}\n\n".

        // Sets the border attribute of a group of checkboxes or radiobuttons
            "function setListBorder(field, borderColor) {\n".
            "    var boxes = document.getElementsByName(field);\n".
            "    for (var i = 0; i < boxes.length; i++) {\n".
            "        boxes[i].style.border = borderColor;\n".
            "    }\n".
            "}\n\n".

        // Show Yellowbill fields when this payment method is selected,
        // hide them otherwise.
            "function toggleYellowpayFields() {\n".
            "    if (!document.getElementById('yellow1')) {\n".
            "        return;\n".
            "    }\n".
            "    display = (document.getElementById('handler').value == 'yellowbill' ? 1 : 0);\n".
            "    for (i = 1; i < 7; ++i) {\n".
            "        row = document.getElementById('yellow'+i).parentNode.parentNode;\n".
            "        if (display == 1) {\n".
            // Firefox won't display the table rows properly using 'block'.
            "            row.style.display = (document.all ? 'block' : 'table-row');\n".
            "        } else {\n".
            "            row.style.display = 'none';\n".
            "        }\n".
            "    }\n".
//"    checkAllFields();\n".
            "}\n\n".
            "// ]]>\n".
            "</script>\n";
        return $code;
    }


    function getCalendarSource($product_id, $flagBackend=false)
    {
        global $objDatabase, $_ARRAYLANG;

        $last_y = date('Y')-1;
        $query = "
            SELECT calendar_product, calendar_order, calendar_day,
                   calendar_month, calendar_year
              FROM ".DBPREFIX."module_egov_product_calendar
             WHERE calendar_product=".intval($product_id)."
               AND calendar_act=1
               AND calendar_year>".intval($last_y);
        $objResult = $objDatabase->Execute($query);
        $ArrayRD = array();
        if ($objResult) {
            while (!$objResult->EOF) {
                if (!isset($ArrayRD[$objResult->fields['calendar_year']][$objResult->fields['calendar_month']][$objResult->fields['calendar_day']])) {
                    $ArrayRD[$objResult->fields['calendar_year']][$objResult->fields['calendar_month']][$objResult->fields['calendar_day']] = 0;
                }
                ++$ArrayRD[$objResult->fields['calendar_year']][$objResult->fields['calendar_month']][$objResult->fields['calendar_day']];
                $objResult->MoveNext();
            }
        }
        \Env::get('ClassLoader')->loadFile(ASCMS_MODULE_PATH.'/Egov/Controller/Cal/Calendrier.php');
        $AnzahlTxT = $_ARRAYLANG['TXT_EGOV_QUANTITY'];
        $AnzahlDropdown = EgovLibrary::_QuantityDropdown();
        $Datum4JS = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $QuantArray =
            EgovLibrary::_GetOrdersQuantityArray($product_id, $Datum4JS);
        return calendar(
            $QuantArray,
            $AnzahlDropdown,
            $AnzahlTxT,
            EgovLibrary::GetSettings('set_calendar_date_desc'),
            EgovLibrary::GetSettings('set_calendar_date_label'),
            $ArrayRD,
            EgovLibrary::GetProduktValue('product_quantity', $product_id),
            EgovLibrary::GetProduktValue('product_quantity_limit', $product_id),
            $Datum4JS,
            EgovLibrary::GetSettings('set_calendar_background'),
            EgovLibrary::GetSettings('set_calendar_legende_1'),
            EgovLibrary::GetSettings('set_calendar_legende_2'),
            EgovLibrary::GetSettings('set_calendar_legende_3'),
            EgovLibrary::GetSettings('set_calendar_color_1'),
            EgovLibrary::GetSettings('set_calendar_color_2'),
            EgovLibrary::GetSettings('set_calendar_color_3'),
            EgovLibrary::GetSettings('set_calendar_border'),
            $flagBackend
        );
    }


    function getSourceCodeBackend($id, $preview=false, $flagBackend=true)
    {
        global $objDatabase, $_ARRAYLANG;

        $arrFields = EgovLibrary::getFormFields($id);
        $strCalendarSource = '';
        if (EgovLibrary::GetProduktValue('product_per_day', $id) == 'yes') {
            $strCalendarSource = $this->getCalendarSourceBackend($id, $flagBackend);
        }

        $FormActionTarget =
            ($preview ? '../' : '').
            ($flagBackend
              ? "index.php?cmd=Egov&amp;act=detail&amp;id=$id"
              : "index.php?section=Egov&amp;id=$id"
            );

        $sourcecode = $this->_getJsSourceCodeBackend($arrFields, $preview, $flagBackend).
            "\n".
            "<div id=\"alertbox\" style=\"overflow: auto; display: none;\">".
            $_ARRAYLANG['TXT_EGOV_CHECK_YOUR_INPUT']."\n".
            "</div><br />\n".
            "<!-- BEGIN contact_form -->\n".
            "<form action=\"$FormActionTarget\" ".
            "method=\"post\" enctype=\"multipart/form-data\" ".
            "onsubmit=\"return checkAllFields();\" id=\"contactForm\">\n".
            "<input type=\"hidden\" name=\"send\" value=\"1\" />\n".
            '<table summary="" border="0" cellpadding="3" cellspacing="0" class="adminlist" width="100%">'."\n".
            '  <tbody style="vertical-align:top;">'."\n".
            '    <tr>'."\n".
            '      <th colspan="2">'.$this->GetProduktValue('product_name', $id).' (ID '.$id.')</th>'."\n".
            '    </tr>'."\n".
'    <tr>'."\n".
            "<td>&nbsp;</td><td>$strCalendarSource</td></tr>\n";
        $i = 1;
        foreach ($arrFields as $fieldId => $arrField) {
            $feldbezeichnung = $arrField['name'];
            if ($feldbezeichnung == "AGB") {
                if ($flagBackend) continue;
                $feldbezeichnung = '<a href="index.php?section=Agb" target="_blank">AGB akzeptieren</a>';
            }
            $sourcecode .=
                "<tr".
                ' class="row'.((++$i % 2)+1).'"'.
                ">\n<td style=\"width:180px;\">".
                ($arrField['type'] != 'hidden' && $arrField['type'] != 'label'
                    ? $feldbezeichnung : '&nbsp;'
                ).
                ($arrField['is_required']
                    ? ' <span style="color: red;">*</span>' : ''
                ).
                "</td>\n<td>";
            switch ($arrField['type']) {
                case 'text':
                    $sourcecode .=
                        "<input style=\"width:300px;\" type=\"text\" ".
                        "name=\"contactFormField_$fieldId\" ".
                        "value=\"".$arrField['attributes']."\" />\n";
                    break;
                case 'label':
                    $sourcecode .= $arrField['attributes']."\n";
                    break;
                case 'checkbox':
                    $sourcecode .=
                        "<input type=\"checkbox\" ".
                        "name=\"contactFormField_$fieldId\" ".
                        "value=\"1\"".
                        ($arrField['attributes'] == '1'
                            ? ' checked="checked"' : ''
                        )." />\n";
                    break;
                case 'checkboxGroup':
                    $options = explode(',', $arrField['attributes']);
                    $nr = 0;
                    foreach ($options as $option) {
                        $sourcecode .=
                            "<input type=\"checkbox\" ".
                            "name=\"contactFormField_{$fieldId}[]\" ".
                            "id=\"contactFormField_{$nr}_$fieldId\" ".
                            "value=\"$option\" />".
                            "<label for=\"contactFormField_{$nr}_$fieldId\">$option</label>\n";
                        ++$nr;
                    }
                    break;
                case 'file':
                    $sourcecode .=
                        "<input style=\"width:300px;\" type=\"file\" ".
                        "name=\"contactFormField_$fieldId\" />\n";
                    break;
                case 'hidden':
                    $sourcecode .=
                        "<input type=\"hidden\" ".
                        "name=\"contactFormField_$fieldId\" ".
                        "value=\"".$arrField['attributes']."\" />\n";
                    break;
                case 'password':
                    $sourcecode .=
                        "<input style=\"width:300px;\" type=\"password\" ".
                        "name=\"contactFormField_$fieldId\" value=\"\" />\n";
                    break;
                case 'radio':
                    $options = explode(',', $arrField['attributes']);
                    $nr = 0;
                    foreach ($options as $option) {
                        $sourcecode .=
                            "<input type=\"radio\" name=\"contactFormField_$fieldId\" id=\"contactFormField_{$nr}_$fieldId\" value=\"$option\" />".
                            "<label for=\"contactFormField_{$nr}_$fieldId\">$option</label>\n";
                        ++$nr;
                    }
                    break;
                case 'select':
                    $options = explode(',', $arrField['attributes']);
                    $nr = 0;
                    $sourcecode .=
                        "<select style=\"width:300px;\" name=\"contactFormField_$fieldId\">\n";
                    foreach ($options as $option) {
                        $sourcecode .= "<option>$option</option>\n";
                    }
                    $sourcecode .= "</select>\n";
                    break;
                case 'textarea':
                    $sourcecode .= "<textarea style=\"width:300px; height:100px;\" name=\"contactFormField_$fieldId\"></textarea>\n";
                    break;
            }
            $sourcecode .=
                "</td>\n</tr>\n";
        }

        if (count($arrFields) > 0) {
            $sourcecode .=
                "  </tbody>\n".
                "</table>\n".
                "<br /><br /><input type=\"reset\" value=\"".
                $_ARRAYLANG['TXT_EGOV_DELETE']."\" />\n".
                "<input type=\"submit\" name=\"submitContactForm\" value=\"".
                $_ARRAYLANG['TXT_EGOV_SUBMIT']."\" />\n";
        }
        $sourcecode .=
            "</form>\n".
            "<!-- END contact_form -->\n";
        return $sourcecode;
    }


    function _getJsSourceCodeBackend($formFields, $preview=false, $flagBackend=false)
    {
        $code =
            "<script type=\"text/javascript\">\n".
            "// <![CDATA[\n".
            "fields = new Array();\n";
        foreach ($formFields as $key => $field) {
            $code .=
                "fields[$key] = Array(\n".
                "  '{$field['name']}',\n".
                "  {$field['is_required']},\n";
            if ($preview) {
                $code .= "  '".
                addslashes(EgovLibrary::$arrCheckTypes[$field['check_type']]['regex']).
                "',\n";
            } elseif ($flagBackend) {
                $code .= "  '".
                addslashes(EgovLibrary::$arrCheckTypes[$field['check_type']]['regex']).
                "',\n";
            } else {
                $code .= "  '".
                addslashes(EgovLibrary::$arrCheckTypes[$field['check_type']]['regex']).
                "',\n";
            }
            $code .= "  '".$field['type']."');\n";
        }
        /*
        if (EgovLibrary::GetProduktValue('product_per_day', $_REQUEST['id'] == 'yes')) {
            $code .= "fields[1000] = Array('Datum', 1, '', 'text');\n";
        }
        */
        $code .=
            "var readBefore = false;\n".
            "var borderBefore = \"\";\n".

            "\nfunction checkAllFields() {\n".
            "    var isOk = true;\n".
            "    for (var field in fields) {\n".
            "        if (!readBefore) {\n".
            "            if (document.getElementsByName('contactFormField_' + field)[0]) {borderBefore = document.getElementsByName('contactFormField_' + field)[0].style.border;} else {borderBefore = '#000000';}\n".
            "            readBefore = true;\n".
            "        }\n\n".

            "        var type = fields[field][3];\n".
            "        if (type == 'text' || type == 'file' || type == 'password' || type == 'textarea') {\n".
            "            value = document.getElementsByName('contactFormField_' + field)[0].value;\n".
            "            if (value == \"\" && isRequiredNorm(fields[field][1], value)) {\n".
            "                isOk = false;\n".
            "                document.getElementsByName('contactFormField_' + field)[0].style.border = \"red 1px solid\";\n".
            "            } else if (value != \"\" && !matchType(fields[field][2], value)) {\n".
            "                isOk = false;\n".
            "                document.getElementsByName('contactFormField_' + field)[0].style.border = \"red 1px solid\";\n".
            "            } else {\n".
            "                document.getElementsByName('contactFormField_' + field)[0].style.border = borderBefore;\n".
            "            }\n".
            "        } else if (type == 'checkbox') {\n".
            "            if (!isRequiredCheckbox(fields[field][1], field)) {\n".
            "                isOk = false;\n".
            "            }\n".
            "        } else if (type == 'checkboxGroup') {\n".
            "            if (!isRequiredCheckBoxGroup(fields[field][1], field)) {\n".
            "                isOk = false;\n".
            "            }\n".
            "        } else if (type == 'radio') {\n".
            "            if (!isRequiredRadio(fields[field][1], field)) {\n".
            "                isOk = false;\n".
            "            }\n".
            "        }\n".
            "    }\n\n".
            "    if (!isOk) {\n".
            "        document.getElementById('alertbox').style.display = \"block\";\n".
            "    } else {\n".
            "        if (document.getElementById('bill1')) {\n".
            "            document.getElementById('bill1').value = document.getElementById('yellow1').value;\n".
            "            document.getElementById('bill2').value = document.getElementById('yellow2').value;\n".
            "            document.getElementById('bill3').value = document.getElementById('yellow3').value;\n".
            "            document.getElementById('bill4').value = document.getElementById('yellow4').value;\n".
            "            document.getElementById('bill5').value = document.getElementById('yellow5').value;\n".
            "            document.getElementById('bill6').value = document.getElementById('yellow6').value;\n".
            "        }\n".
            "    }\n".
            "    return isOk;\n".
            "}\n\n".

        // This is for checking normal text input field if they are required.
        // If yes, it also checks if the field is set. If it is not set, it returns true.
        // Uses a hack to skip Yellowbill fields when this payment method
        // is not selected.
            "function isRequiredNorm(required, value) {\n".
            "    if (   (   required == -1\n".
            "            && document.getElementById('handler')\n".
            "            && document.getElementById('handler').value == 'yellowbill')\n".
            "        ||  required == 1) {\n".
            "        if (value == \"\") {\n".
            "            return true;\n".
            "        }\n".
            "    }\n".
            "    return false;\n".
            "}\n\n".

        // Matches the type of the value and pattern. Returns true if it matched, false if not.
            "function matchType(pattern, value) {\n".
            "    var reg = new RegExp(pattern);\n".
            "    if (value.match(reg)) {\n".
            "        return true;\n".
            "    }\n".
            "    return false;\n".
            "}\n\n".

        // Checks if a checkbox is required but not set. Returns false when finding an error.
            "function isRequiredCheckbox(required, field) {\n".
            "    if (document.getElementsByName('contactFormField_' + field).length == 0) {\n".
            "        return true;\n".
            "    }\n".
            "    if (required == 1) {\n".
            "        if (!document.getElementsByName('contactFormField_' + field)[0].checked) {\n".
            "            document.getElementsByName('contactFormField_' + field)[0].style.border = \"red 1px solid\";\n".
            "            return false;\n".
            "        }\n".
            "    }\n".
            "    document.getElementsByName('contactFormField_' + field)[0].style.border = borderBefore;\n".
            "    return true;\n".
            "}\n\n".

        // Checks if a multiple checkbox is required but not set. Returns false when finding an error.
            "function isRequiredCheckBoxGroup(required, field) {\n".
            "    if (required == true) {\n".
            "        var boxes = document.getElementsByName('contactFormField_' + field + '[]');\n".
            "        var checked = false;\n".
            "        for (var i = 0; i < boxes.length; i++) {\n".
            "             if (boxes[i].checked) {\n".
            "                checked = true;\n".
            "            }\n".
            "        }\n".
            "        if (checked) {\n".
            "            setListBorder('contactFormField_' + field + '[]', borderBefore);\n".
            "            return true;\n".
            "        } else {\n".
            "            setListBorder('contactFormField_' + field + '[]', '1px red solid');\n".
            "            return false;\n".
            "        }\n".
            "    } else {\n".
            "        return true;\n".
            "    }\n".
            "}\n\n".

        // Checks if some radio button need to be checked. Returns false if it finds an error
            "function isRequiredRadio(required, field) {\n".
            "    if (required == 1) {\n".
            "        var buttons = document.getElementsByName('contactFormField_' + field);\n".
            "        var checked = false;\n".
            "        for (var i = 0; i < buttons.length; i++) {\n".
            "            if (buttons[i].checked) {\n".
            "                checked = true;\n".
            "            }\n".
            "        }\n".
            "        if (checked) {\n".
            "            setListBorder('contactFormField_' + field, borderBefore);\n".
            "            return true;\n".
            "        } else {\n".
            "            setListBorder('contactFormField_' + field, '1px red solid');\n".
            "            return false;\n".
            "        }\n".
            "    } else {\n".
            "        return true;\n".
            "    }\n".
            "}\n\n".

        // Sets the border attribute of a group of checkboxes or radiobuttons
            "function setListBorder(field, borderColor) {\n".
            "    var boxes = document.getElementsByName(field);\n".
            "    for (var i = 0; i < boxes.length; i++) {\n".
            "        boxes[i].style.border = borderColor;\n".
            "    }\n".
            "}\n\n".

        // Show Yellowbill fields when this payment method is selected,
        // hide them otherwise.
            "function toggleYellowpayFields() {\n".
            "    if (!document.getElementById('yellow1')) {\n".
            "        return;\n".
            "    }\n".
            "    display = (document.getElementById('handler').value == 'yellowbill' ? 1 : 0);\n".
            "    for (i = 1; i < 7; ++i) {\n".
            "        row = document.getElementById('yellow'+i).parentNode.parentNode;\n".
            "        if (display == 1) {\n".
            // Firefox won't display the table rows properly using 'block'.
            "            row.style.display = (document.all ? 'block' : 'table-row');\n".
            "        } else {\n".
            "            row.style.display = 'none';\n".
            "        }\n".
            "    }\n".
//"    checkAllFields();\n".
            "}\n\n".
            "// ]]>\n".
            "</script>\n";
        return $code;
    }


    function getCalendarSourceBackend($product_id, $flagBackend=false)
    {
        global $objDatabase, $_ARRAYLANG;

        $last_y = date('Y')-1;
        $query = "
            SELECT calendar_product, calendar_order, calendar_day,
                   calendar_month, calendar_year
              FROM ".DBPREFIX."module_egov_product_calendar
             WHERE calendar_product=".intval($product_id)."
               AND calendar_act=1
               AND calendar_year>".intval($last_y);
        $objResult = $objDatabase->Execute($query);
        $ArrayRD = array();
        if ($objResult) {
            while (!$objResult->EOF) {
                if (!isset($ArrayRD[$objResult->fields['calendar_year']][$objResult->fields['calendar_month']][$objResult->fields['calendar_day']])) {
                    $ArrayRD[$objResult->fields['calendar_year']][$objResult->fields['calendar_month']][$objResult->fields['calendar_day']] = 0;
                }
                ++$ArrayRD[$objResult->fields['calendar_year']][$objResult->fields['calendar_month']][$objResult->fields['calendar_day']];
                $objResult->MoveNext();
            }
        }
        \Env::get('ClassLoader')->loadFile(ASCMS_MODULE_PATH.'/Egov/Controller/Cal/Calendrier.php');
        $AnzahlTxT = $_ARRAYLANG['TXT_EGOV_QUANTITY'];
        $AnzahlDropdown = $this->_QuantityDropdown();
        $Datum4JS = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $QuantArray = $this->_GetOrdersQuantityArray($product_id, $Datum4JS);
        return calendar(
            $QuantArray,
            $AnzahlDropdown,
            $AnzahlTxT,
            EgovLibrary::GetSettings('set_calendar_date_desc'),
            EgovLibrary::GetSettings('set_calendar_date_label'),
            $ArrayRD,
            EgovLibrary::GetProduktValue('product_quantity', $product_id),
            EgovLibrary::GetProduktValue('product_quantity_limit', $product_id),
            $Datum4JS,
            EgovLibrary::GetSettings('set_calendar_background'),
            EgovLibrary::GetSettings('set_calendar_legende_1'),
            EgovLibrary::GetSettings('set_calendar_legende_2'),
            EgovLibrary::GetSettings('set_calendar_legende_3'),
            EgovLibrary::GetSettings('set_calendar_color_1'),
            EgovLibrary::GetSettings('set_calendar_color_2'),
            EgovLibrary::GetSettings('set_calendar_color_3'),
            EgovLibrary::GetSettings('set_calendar_border'),
            $flagBackend
        );
    }


    /**
     * Update the order status of an order specified by its ID
     * @param   integer     $order_id   The order ID
     * @param   integer     $status     The new status
     * @return  boolean                 True on success, false otherwise
     */
    static function updateOrderStatus($order_id, $status)
    {
        global $objDatabase;

        $order_id = intval($order_id);
        $status = intval($status);
        $query = "
            UPDATE ".DBPREFIX."module_egov_orders
               SET order_state=$status
             WHERE order_id=$order_id
        ";
        if (!$objDatabase->Execute($query)) {
            return false;
        }
        $query = "
            UPDATE ".DBPREFIX."module_egov_product_calendar
               SET calendar_act=".($status == 1 || $status == 3 ? 1 : 0)."
             WHERE calendar_order=$order_id
        ";
        if (!$objDatabase->Execute($query)) {
            return false;
        }
        return true;
    }


    /**
     * Add a line to the log file
     *
     * Prepends the current date and time to the string,
     * adds a line terminator and appends this to the log file.
     * Silently terminates if the log file cannot be opened for appending.
     * @param   string   $strLine     The entry to be logged
     */
    function addLog($strLine)
    {
        $fp = fopen('egov.log', 'a');
        if (!$fp) return;
        fwrite($fp, date('Ymd His')." $strLine\n");
        fclose($fp);
    }


    /**
     * Handles and fixes database related problems
     * @return  boolean             False.  Always.
     */
    static function errorHandler()
    {
        \Yellowpay::errorHandler(); // Also calls \Cx\Core\Setting\Controller\Setting::errorHandler()
        foreach (array(
            'postfinance_accepted_payment_methods' =>
                'yellowpay_accepted_payment_methods',
            'postfinance_shop_id' =>
                'yellowpay_shopid',
            'postfinance_hash_signature_in' =>
                'yellowpay_hashseed',
            'postfinance_hash_signature_out' =>
                'yellowpay_hashseed',
            'postfinance_authorization_type' =>
                'yellowpay_authorization',
            'postfinance_use_testserver' =>
                'yellowpay_use_testserver',
        ) as $to => $from) {
            $value = EgovLibrary::GetSettings($from);
//DBG::log("EgovLibrary::errorHandler(): Copying from $from, value $value, to $to<br />");
            \Cx\Core\Setting\Controller\Setting::set($to, $value);
        }
        \Cx\Core\Setting\Controller\Setting::updateAll();
    }

    /**
     * Get media browser button
     *
     * @param string $id       Id of the button
     * @param string $callback Name of the callback function
     * @return string HTML element of browse button
     */
    protected function getMediaBrowserButton($id, $callback = '')
    {
        global $_ARRAYLANG;

        $dataMbViews = 'filebrowser';
        if ($id === 'productTargetUrlButton') {
            $dataMbViews = 'filebrowser,sitestructure';
        }

        $options = array(
            'type'  => 'button',
            'views' => $dataMbViews,
            'id'    => $id,
        );
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions($options);
        if ($callback) {
            $mediaBrowser->setCallback($callback);
        }

        return $mediaBrowser->getXHtml($_ARRAYLANG['TXT_BROWSE']);
    }
}
