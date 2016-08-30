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
 * Paymill online payment
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     3.1.1
 * @package     cloudrexx
 * @subpackage  module_shop
 */

/**
 * PostFinance online payment
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     3.1.1
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class PaymillCCHandler extends PaymillHandler {

   private static $formScript = <<< FORMTEMPLATE

            \$J(document).ready(function() {
                // 3d secure credit card form
                \$J("#card-tds-form").submit(function(event) {
                    // Deactivate submit button to avoid further clicks
                    \$J('.submit-button').attr("disabled", "disabled");

                    if (false === paymill.validateHolder(\$J('.card-holdername').val())) {
                        logResponse(cx.variables.get('invalid-card-holdername', 'shop'));
                        \$J(".submit-button").removeAttr("disabled");
                        return false;
                    }
                    if ((false === paymill.validateCvc(\$J('.card-cvc').val()))) {
                        if(VALIDATE_CVC){
                            logResponse(cx.variables.get('invalid-card-cvc', 'shop'));
                            \$J(".submit-button").removeAttr("disabled");
                            return false;
                        } else {
                            \$J('.card-cvc').val("");
                        }
                    }
                    if (false === paymill.validateCardNumber(\$J('.card-number').val())) {
                        logResponse(cx.variables.get('invalid-card-number', 'shop'));
                        \$J(".submit-button").removeAttr("disabled");
                        return false;
                    }
                    if (false === paymill.validateExpiry(\$J('.card-expiry-month').val(), \$J('.card-expiry-year').val())) {
                        logResponse(cx.variables.get('invalid-card-expiry-date', 'shop'));
                        \$J(".submit-button").removeAttr("disabled");
                        return false;
                    }

                    event.preventDefault();
                    try {
                        paymill.createToken({
                            number:     \$J('#card-tds-form .card-number').val(),
                            exp_month:  \$J('#card-tds-form .card-expiry-month').val(),
                            exp_year:   \$J('#card-tds-form .card-expiry-year').val(),
                            cvc:        \$J('#card-tds-form .card-cvc').val(),
                            cardholder: \$J('#card-tds-form .card-holdername').val(),
                        }, PaymillResponseHandler);
                    } catch(e) {
                        logResponse(e.message);
                    }
                });
                \$J('.card-number').keyup(function() {
                    var brand = detectCreditcardBranding(\$J('.card-number').val());
                    brand = brand.replace(' ','-');
                    \$J(".card-number")[0].className = \$J(".card-number")[0].className.replace(/paymill-card-number-.*/g, '');
                    if (brand !== 'unknown') {
                        \$J('.card-number').addClass("paymill-card-number-" + brand);
                    }

                    if (brand !== 'maestro') {
                        VALIDATE_CVC = true;
                    } else {
                        VALIDATE_CVC = false;
                    }
                });
            });
            function PaymillResponseHandler(error, result) {
                if (error) {
                    logResponse(error.apierror);
                    \$J(".submit-button").removeAttr("disabled");
                } else {
                    //logResponse(result.token);
                    var form = \$J("#card-tds-form");
                    // Token
                    var token = result.token;

                    // Insert token into form in order to submit to server
                    form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
                    form.get(0).submit();

                    \$J(".submit-button").removeAttr("disabled");
                }

            }

            function logResponse(res) {
                /*
                // create console.log to avoid errors in old IE browsers
                if (!window.console) console = {log:function(){}};

                console.log(res);
                if(PAYMILL_TEST_MODE)
                    \$J('.debug').text(res).show().fadeOut(8000);
                */
                \$J('.paymill-error-text').text(res).show().fadeOut(8000);
            }
FORMTEMPLATE;

    /**
     * Creates and returns the HTML Form for requesting the payment service.
     *
     * @access  public
     * @return  string                      The HTML form code
     */
    static function getForm($arrOrder, $landingPage = null)
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

        JS::registerCSS('modules/shop/payments/paymill/css/paymill_styles.css');
        JS::registerJS('modules/shop/payments/paymill/js/creditcardBrandDetection.js');
        JS::registerJS(self::$paymillJsBridge);

        $testMode = intval(\Cx\Core\Setting\Controller\Setting::getValue('paymill_use_test_account','Shop')) == 0;
        $apiKey   = $testMode ? \Cx\Core\Setting\Controller\Setting::getValue('paymill_test_public_key','Shop') : \Cx\Core\Setting\Controller\Setting::getValue('paymill_live_public_key','Shop');
        $mode     = $testMode ? 'true' : 'false';

        $code = <<< APISETTING
                var PAYMILL_PUBLIC_KEY = '$apiKey';
                var PAYMILL_TEST_MODE  = $mode;
                var VALIDATE_CVC       = true;
APISETTING;
        JS::registerCode($code);
        JS::registerCode(self::$formScript);

        \ContrexxJavascript::getInstance()->setVariable(array(
                'invalid-card-holdername'   => $_ARRAYLANG['TXT_SHOP_PAYMILL_INVAILD_CARD_HOLDER'],
                'invalid-card-cvc'          => $_ARRAYLANG['TXT_SHOP_PAYMILL_INVAILD_CARD_CVC'],
                'invalid-card-number'       => $_ARRAYLANG['TXT_SHOP_PAYMILL_INVAILD_CARD_NUMBER'],
                'invalid-card-expiry-date'  => $_ARRAYLANG['TXT_SHOP_PAYMILL_INVAILD_CARD_EXPIRY_DATE']
            ),
            'shop'
        );

        $formContent  = self::getElement('div', 'class="paymill-error-text"');

        $formContent .= self::fieldset('');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', $_ARRAYLANG['TXT_SHOP_CREDIT_CARD_NUMBER']);
        $formContent .= Html::getInputText('', '', '', 'class="card-number" size="20"');
        $formContent .= self::closeElement('div');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::openElement('label');
        $formContent .= $_ARRAYLANG['TXT_SHOP_CVC'] . '&nbsp;';
        $formContent .= self::getElement('span', 'class="tooltip-trigger icon-info"');
        $formContent .= self::getElement('span', 'class="tooltip-message"', $_ARRAYLANG['TXT_SHOP_CVC_TOOLTIP']);
        $formContent .= self::closeElement('label');
        $formContent .= Html::getInputText('', '', '', 'class ="card-cvc" size="4" maxlength="4"');
        $formContent .= self::closeElement('div');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', $_ARRAYLANG['TXT_SHOP_CARD_HOLDER']);
        $formContent .= Html::getInputText('', '', '', 'class="card-holdername" size="20"');
        $formContent .= self::closeElement('div');

        $arrMonths = array();
        for ($i=1;$i<=12;$i++) {
            $month             = str_pad($i, 2, '0', STR_PAD_LEFT);
            $arrMonths[$month] = $month;
        }

        $arrYears    = array();
        $currentYear = date('Y');
        for ($i=$currentYear;$i<=($currentYear+6);$i++) {
            $arrYears[$i] = $i;
        }

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', $_ARRAYLANG['TXT_SHOP_CARD_EXPIRY']);
        $formContent .= Html::getSelect('card-expiry-month', $arrMonths, '', false, '', 'class="card-expiry-month"');
        $formContent .= Html::getSelect('card-expiry-year', $arrYears, '', false, '', 'class="card-expiry-year"');
        $formContent .= self::closeElement('div');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', '&nbsp;');
        $formContent .= Html::getInputButton('', $_ARRAYLANG['TXT_SHOP_BUY_NOW'], 'submit', '', 'class="submit-button"');
        $formContent .= self::closeElement('div');

        $formContent .= Html::getHidden('handler', 'paymill_cc');

        $formContent .= self::closeElement('fieldset');

        $form = Html::getForm('', Cx\Core\Routing\Url::fromPage($landingPage)->toString(), $formContent, 'card-tds-form', 'post');

        return $form;
    }
}
