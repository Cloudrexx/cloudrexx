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
class PaymillHandler {
    /**
     * section name
     *
     * @access  private
     * @var     string
     */
    public static $sectionName = null;

    /**
     * Error messages
     * @access  public
     * @var     array
     */
    public static $arrError = array();

    /**
     * Paymill JS Bridge url
     *
     * @var string
     */
    public static $paymillJsBridge = "https://bridge.paymill.com";

    /**
     * Warning messages
     * @access  public
     * @var     array
     */
    public static $arrWarning = array();

    public static function processRequest($token, $arrOrder) {
        global $_CONFIG;

        if (empty($token)) {
            return array(
                        'status'  => 'error',
                        'message' => 'invalid token'
                       );
        }

        $testMode = intval(\Cx\Core\Setting\Controller\Setting::getValue('paymill_use_test_account','Shop')) == 0;
        $apiKey   = $testMode ? \Cx\Core\Setting\Controller\Setting::getValue('paymill_test_private_key','Shop') : \Cx\Core\Setting\Controller\Setting::getValue('paymill_live_private_key','Shop');

        if ($token) {
            try {

                $request     = new Paymill\Request($apiKey);
                $transaction = new Paymill\Models\Request\Transaction();
                $transaction->setAmount($arrOrder['amount'])
                            ->setCurrency($arrOrder['currency'])
                            ->setToken($token)
                            ->setDescription($arrOrder['note'])
                            ->setSource('contrexx_'.$_CONFIG['coreCmsVersion']);

                DBG::log("Transactoin created with token:". $token);
                $response = $request->create($transaction);
                $paymentId = $response->getId();
                DBG::log("Payment ID".$paymentId);

                return array('status' => 'success', 'payment_id' => $paymentId);
            } catch(\Paymill\Services\PaymillException $e) {
                //Do something with the error informations below
                return array(
                        'status' => 'error',
                        'response_code' => $e->getResponseCode(),
                        'status_code' => $e->getStatusCode(),
                        'message'       => $e->getErrorMessage()
                       );
            }
        }
    }

    static function fieldset($legend = false, $selfClose = false) {
        $fieldset = self::openElement('fieldset');
        if ($legend) {
            $fieldset .= self::getElement('legend', '', $legend);
        }
        if ($selfClose) {
            $fieldset .= self::closeElement('fieldset');
        }

        return $fieldset;
    }

    static function getElement($elm, $attrbs = '', $content ='') {
        return "<$elm ". self::getFormattedAttrbs($attrbs) . ">". $content ."</$elm> \n";
    }

    static function openElement($elm , $attributes = '')
    {
        return "<$elm ". self::getFormattedAttrbs($attributes)." > \n";
    }

    static function closeElement($elm)
    {
        return "</$elm> \n";
    }

    static function getFormattedAttrbs($attributes) {
        $html = '';
        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                $html .= $attribute;
            }
        } else {
            $html .= $attributes;
        }
        return $html;
    }
}
