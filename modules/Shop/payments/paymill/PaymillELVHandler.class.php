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
class PaymillELVHandler extends PaymillHandler {

   private static $formScript = <<< FORMTEMPLATE

            \$J(document).ready(function() {
                \$J("#payment-form").submit(function (event) {

                    \$J('.submit-button').attr("disabled", "disabled");

                    if ("" === \$J('.elv-holdername').val()) {
                        logResponse(cx.variables.get('invalid-card-holder', 'shop'));
                        \$J(".submit-button").removeAttr("disabled");
                        return false;
                    }
                    if (false == paymill.validateAccountNumber(\$J('.elv-account').val())) {
                        logResponse(cx.variables.get('invalid-account-number', 'shop'));
                        \$J(".submit-button").removeAttr("disabled");
                        return false;
                    }
                    if (false == paymill.validateBankCode(\$J('.elv-bankcode').val())) {
                        logResponse(cx.variables.get('invalid-bank-code', 'shop'));
                        \$J(".submit-button").removeAttr("disabled");
                        return false;
                    }

                    var params = {
                        number:         \$J('.elv-account').val(),
                        bank:           \$J('.elv-bankcode').val(),
                        accountholder:  \$J('.elv-holdername').val()
                    };

                    paymill.createToken(params, PaymillResponseHandler);
                    return false;
                });
            });
            function PaymillResponseHandler(error, result) {
                if (error) {
                    // display any error occurs
                    logResponse(error.apierror);
                    \$J(".submit-button").removeAttr("disabled");
                } else {
                    //logResponse(result.token);
                    var form = \$J("#payment-form");
                    // Token
                    var token = result.token;
                    // Insert token into form in order to submit to server
                    form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");

                    form.get(0).submit();
                }
                \$J(".submit-button").removeAttr("disabled");
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

        JS::registerJS(self::$paymillJsBridge);

        \ContrexxJavascript::getInstance()->setVariable(array(
                'invalid-card-holder'    => $_ARRAYLANG['TXT_SHOP_PAYMILL_INVAILD_CARD_HOLDER'],
                'invalid-account-number' => $_ARRAYLANG['TXT_SHOP_PAYMILL_INVALID_ACC_NUMBER'],
                'invalid-bank-code'      => $_ARRAYLANG['TXT_SHOP_PAYMILL_INVALID_BANK_CODE'],
            ),
            'shop'
        );

        $testMode = intval(\Cx\Core\Setting\Controller\Setting::getValue('paymill_use_test_account','Shop')) == 0;
        $apiKey   = $testMode ? \Cx\Core\Setting\Controller\Setting::getValue('paymill_test_public_key','Shop') : \Cx\Core\Setting\Controller\Setting::getValue('paymill_live_public_key','Shop');
        $mode     = $testMode ? 'true' : 'false';

        $code = <<< APISETTING
                var PAYMILL_PUBLIC_KEY = '$apiKey';
                var PAYMILL_TEST_MODE  = $mode;
APISETTING;
        JS::registerCode($code);
        JS::registerCode(self::$formScript);

        $formContent  = self::getElement('div', 'class="paymill-error-text"');

        $formContent .= self::fieldset('');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', $_ARRAYLANG['TXT_SHOP_PAYMILL_ELV_ACCOUNT_NUMBER']);
        $formContent .= Html::getInputText('', '', '', 'class="elv-account"');
        $formContent .= self::closeElement('div');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', $_ARRAYLANG['TXT_SHOP_PAYMILL_ELV_BANK_CODE']);
        $formContent .= Html::getInputText('', '', '', 'class ="elv-bankcode"');
        $formContent .= self::closeElement('div');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', $_ARRAYLANG['TXT_SHOP_PAYMILL_ACCOUNT_HOLDER']);
        $formContent .= Html::getInputText('', '', '', 'class="elv-holdername"');
        $formContent .= self::closeElement('div');

        $formContent .= self::openElement('div', 'class="row"');
        $formContent .= self::getElement('label', '', '&nbsp;');
        $formContent .= Html::getInputButton('', $_ARRAYLANG['TXT_SHOP_BUY_NOW'], 'submit', '', 'class="submit-button"');
        $formContent .= self::closeElement('div');

        $formContent .= Html::getHidden('handler', 'paymill_elv');

        $formContent .= self::closeElement('fieldset');

        $form = Html::getForm('', Cx\Core\Routing\Url::fromPage($landingPage)->toString(), $formContent, 'payment-form', 'post');

        return $form;
    }

}
