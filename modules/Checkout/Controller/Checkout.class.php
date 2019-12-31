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
 * Checkout
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_checkout
 */

namespace Cx\Modules\Checkout\Controller;

/**
 * Checkout
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_checkout
 */
class Checkout extends CheckoutLibrary {

    /**
     * Transaction object.
     *
     * @access      private
     * @var         Transaction
     */
    private $objTransaction;

    /**
     * Template object.
     *
     * @access      private
     * @var         HTML_TEMPLATE_SIGMA
     */
    private $objTemplate;

    /**
     * All negative and positive status messages.
     *
     * @access      private
     * @var         array
     */
    private $arrStatusMessages = array('ok' => array(), 'error' => array());

    /**
     * Constructor
     * Initialize the template and transaction object.
     *
     * @access      public
     * @param       string     $pageContent      content page
     */
    public function __construct($pageContent)
    {
        global $objDatabase;

        parent::__construct();

        $this->objTransaction = new Transaction($objDatabase);

        $this->objTemplate = new \Cx\Core\Html\Sigma('.');
        $this->objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $this->objTemplate->setTemplate($pageContent);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->objTemplate);
    }

    /**
     * Get page depending on the result parameter.
     *
     * @access      public
     * @return      string  content page
     */
    public function getPage()
    {
        if (isset($_GET['result'])) {
            $this->registerPaymentResult();
        } else {
            $this->renderForm();
        }

        $this->parseMessages();
        return $this->objTemplate->get();
    }

    /**
     * Replace status message placeholders with the value.
     *
     * @access      private
     */
    private function parseMessages()
    {
        $this->objTemplate->setVariable(array(
            'CHECKOUT_MSG_OK'       => count($this->arrStatusMessages['ok']) ? '<div id="ok_message">'.implode('<br />', $this->arrStatusMessages['ok']).'</div>' : '',
            'CHECKOUT_MSG_ERROR'    => count($this->arrStatusMessages['error']) ? '<div id="error_message">'.implode('<br />', $this->arrStatusMessages['error']).'</div>' : '',
        ));
    }

    /**
     * Generate the form and show hints if necessary.
     * If user input validation is successful a new transaction will be added.
     * In this case the form will be hidden and only a status message will be shown.
     *
     * @access      private
     */
    private function renderForm()
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        //check the payment service provider configuration
        $objSettingsGeneral = new SettingsGeneral($objDatabase);
        if (!$objSettingsGeneral->getEpaymentStatus()) {
            $this->arrStatusMessages['error'][] = $_ARRAYLANG['TXT_CHECKOUT_EPAYMENT_DEACTIVATED'];
            $this->objTemplate->hideblock('form');
            $this->objTemplate->hideblock('redirect');
            return;
        }

        //initialize variables
        $arrFieldValues = array();
        $arrFieldsToHighlight = array();
        $arrCssClasses = array();
        $cssHighlightingClass = 'highlight';
        $cssLabelClass = 'label';
        $htmlRequiredField = ' *';
        $arrSelectOptions[] = array();

        //validate submitted user data
        if (isset($_REQUEST['submit'])) {
            $arrFieldValues['invoice_number'] = !empty($_REQUEST['invoice_number']) && ($_REQUEST['invoice_number'] !== $_ARRAYLANG['TXT_CHECKOUT_INVOICE_NUMBER'].$htmlRequiredField) ? $_REQUEST['invoice_number'] : '';
            $arrFieldValues['invoice_currency'] = !empty($_REQUEST['invoice_currency']) ? $_REQUEST['invoice_currency'] : '';
            $arrFieldValues['invoice_amount'] = !empty($_REQUEST['invoice_amount']) && ($_REQUEST['invoice_amount'] !== $_ARRAYLANG['TXT_CHECKOUT_INVOICE_AMOUNT'].$htmlRequiredField) ? $_REQUEST['invoice_amount'] : '';
            $arrFieldValues['contact_title'] = !empty($_REQUEST['contact_title']) ? $_REQUEST['contact_title'] : '';
            $arrFieldValues['contact_forename'] = !empty($_REQUEST['contact_forename']) && ($_REQUEST['contact_forename'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_FORENAME'].$htmlRequiredField) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_forename'])) : '';
            $arrFieldValues['contact_surname'] = !empty($_REQUEST['contact_surname']) && ($_REQUEST['contact_surname'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_SURNAME'].$htmlRequiredField) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_surname'])) : '';
            $arrFieldValues['contact_company'] = !empty($_REQUEST['contact_company']) && ($_REQUEST['contact_company'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_COMPANY']) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_company'])) : '';
            $arrFieldValues['contact_street'] = !empty($_REQUEST['contact_street']) && ($_REQUEST['contact_street'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_STREET'].$htmlRequiredField) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_street'])) : '';
            $arrFieldValues['contact_postcode'] = !empty($_REQUEST['contact_postcode']) && ($_REQUEST['contact_postcode'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_POSTCODE'].$htmlRequiredField) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_postcode'])) : '';
            $arrFieldValues['contact_place'] = !empty($_REQUEST['contact_place']) && ($_REQUEST['contact_place'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_PLACE'].$htmlRequiredField) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_place'])) : '';
            $arrFieldValues['contact_country'] = !empty($_REQUEST['contact_country']) ? $_REQUEST['contact_country'] : '';
            $arrFieldValues['contact_phone'] = !empty($_REQUEST['contact_phone']) && ($_REQUEST['contact_phone'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_PHONE'].$htmlRequiredField) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_phone'])) : '';
            $arrFieldValues['contact_email'] = !empty($_REQUEST['contact_email']) && ($_REQUEST['contact_email'] !== $_ARRAYLANG['TXT_CHECKOUT_CONTACT_EMAIL'].$htmlRequiredField) ? contrexx_input2raw(contrexx_strip_tags($_REQUEST['contact_email'])) : '';

            $arrUserData = array(
                'text' => array(
                    'invoice_number' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_INVOICE_NUMBER'],
                        'value'     => $arrFieldValues['invoice_number'],
                        'length'    => 255,
                        'mandatory' => 1,
                    ),
                    'contact_forename' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_FORENAME'],
                        'value'     => $arrFieldValues['contact_forename'],
                        'length'    => 255,
                        'mandatory' => 1,
                    ),
                    'contact_surname' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_SURNAME'],
                        'value'     => $arrFieldValues['contact_surname'],
                        'length'    => 255,
                        'mandatory' => 1,
                    ),
                    'contact_company' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_COMPANY'],
                        'value'     => $arrFieldValues['contact_company'],
                        'length'    => 255,
                        'mandatory' => 0,
                    ),
                    'contact_street' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_STREET'],
                        'value'     => $arrFieldValues['contact_street'],
                        'length'    => 255,
                        'mandatory' => 1,
                    ),
                    'contact_postcode' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_POSTCODE'],
                        'value'     => $arrFieldValues['contact_postcode'],
                        'length'    => 255,
                        'mandatory' => 1,
                    ),
                    'contact_place' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_PLACE'],
                        'value'     => $arrFieldValues['contact_place'],
                        'length'    => 255,
                        'mandatory' => 1,
                    ),
                    'contact_phone' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_PHONE'],
                        'value'     => $arrFieldValues['contact_phone'],
                        'length'    => 255,
                        'mandatory' => 1,
                    ),
                ),
                'selection' => array(
                    'invoice_currency' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_INVOICE_CURRENCY'],
                        'value'     => $arrFieldValues['invoice_currency'],
                        'options'   => $this->arrCurrencies,
                        'mandatory' => 1,
                    ),
                    'contact_title' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE'],
                        'value'     => $arrFieldValues['contact_title'],
                        'options'   => array(self::MISTER => '', self::MISS => ''),
                        'mandatory' => 1,
                    ),
                    'contact_country' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_COUNTRY'],
                        'value'     => $arrFieldValues['contact_country'],
                        'options'   => $this->arrCountries,
                        'mandatory' => 1,
                    )
                ),
                'numeric' => array(
                    'invoice_amount' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_INVOICE_AMOUNT'],
                        'value'     => $arrFieldValues['invoice_amount'],
                        'length'    => 15,
                        'mandatory' => 1,
                    )
                ),
                'email' => array(
                    'contact_email' => array(
                        'name'      => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_EMAIL'],
                        'value'     => $arrFieldValues['contact_email'],
                        'length'    => 255,
                        'mandatory' => 1,
                    )
                )
            );

            $arrFieldsToHighlight = $this->validateUserData($arrUserData);

            if (empty($arrFieldsToHighlight)) {
                //validation was successful. now add a new transaction.
                $id = $this->objTransaction->add(
                    self::WAITING,
                    $arrUserData['text']['invoice_number']['value'],
                    $arrUserData['selection']['invoice_currency']['value'],
                    $arrUserData['numeric']['invoice_amount']['value'],
                    $arrUserData['selection']['contact_title']['value'],
                    $arrUserData['text']['contact_forename']['value'],
                    $arrUserData['text']['contact_surname']['value'],
                    $arrUserData['text']['contact_company']['value'],
                    $arrUserData['text']['contact_street']['value'],
                    $arrUserData['text']['contact_postcode']['value'],
                    $arrUserData['text']['contact_place']['value'],
                    $arrUserData['selection']['contact_country']['value'],
                    $arrUserData['text']['contact_phone']['value'],
                    $arrUserData['email']['contact_email']['value']
                );
                if ($id) {
                    $objSettingsYellowpay = new SettingsYellowpay($objDatabase);
                    $arrYellowpay = $objSettingsYellowpay->get();

                    $arrOrder = array(
                        'ORDERID'   => $id,
                        'AMOUNT'    => intval($arrFieldValues['invoice_amount'] * 100),
                        'CURRENCY'  => $this->arrCurrencies[$arrFieldValues['invoice_currency']],
                        'PARAMPLUS' => 'section=Checkout',
                    );

                    $arrSettings = array(
                        'postfinance_shop_id'            => array('value' => $arrYellowpay['pspid']),
                        'postfinance_hash_signature_in'  => array('value' => $arrYellowpay['sha_in']),
                        'postfinance_authorization_type' => array('value' => $arrYellowpay['operation']),
                        'postfinance_use_testserver'     => array('value' => $arrYellowpay['testserver'])
                    );

                    $landingPage = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->findOneByModuleCmdLang('Checkout', '', FRONTEND_LANG_ID);

                    $this->objTemplate->setVariable('CHECKOUT_YELLOWPAY_FORM', \Yellowpay::getForm($arrOrder, $_ARRAYLANG['TXT_CHECKOUT_START_PAYMENT'], false, $arrSettings, $landingPage));

                    if (\Yellowpay::$arrError) {
                        $this->arrStatusMessages['error'][] = $_ARRAYLANG['TXT_CHECKOUT_FAILED_TO_INITIALISE_YELLOWPAY'];
                    } else {
                        $this->arrStatusMessages['ok'][] = $_ARRAYLANG['TXT_CHECKOUT_ENTRY_SAVED_SUCCESSFULLY'];
                    }

                    $this->objTemplate->hideBlock('form');
                    $this->objTemplate->touchBlock('redirect');

                    return;
                } else {
                    $this->arrStatusMessages['error'][] = $_ARRAYLANG['TXT_CHECKOUT_ENTRY_SAVED_ERROR'];
                }
            }
        } else {
            //get passed data
            $arrFieldValues['invoice_number'] = !empty($_REQUEST['invoice_number']) ? $_REQUEST['invoice_number'] : '';
            $arrFieldValues['invoice_currency'] = !empty($_REQUEST['invoice_currency']) ? $_REQUEST['invoice_currency'] : '';
            $arrFieldValues['invoice_amount'] = !empty($_REQUEST['invoice_amount']) ? $_REQUEST['invoice_amount'] : '';
            $arrFieldValues['contact_title'] = !empty($_REQUEST['contact_title']) ? $_REQUEST['contact_title'] : '';
            $arrFieldValues['contact_forename'] = !empty($_REQUEST['contact_forename']) ? $_REQUEST['contact_forename'] : '';
            $arrFieldValues['contact_surname'] = !empty($_REQUEST['contact_surname']) ? $_REQUEST['contact_surname'] : '';
            $arrFieldValues['contact_company'] = !empty($_REQUEST['contact_company']) ? $_REQUEST['contact_company'] : '';
            $arrFieldValues['contact_street'] = !empty($_REQUEST['contact_street']) ? $_REQUEST['contact_street'] : '';
            $arrFieldValues['contact_postcode'] = !empty($_REQUEST['contact_postcode']) ? $_REQUEST['contact_postcode'] : '';
            $arrFieldValues['contact_place'] = !empty($_REQUEST['contact_place']) ? $_REQUEST['contact_place'] : '';
            $arrFieldValues['contact_country'] = !empty($_REQUEST['contact_country']) ? $_REQUEST['contact_country'] : '';
            $arrFieldValues['contact_phone'] = !empty($_REQUEST['contact_phone']) ? $_REQUEST['contact_phone'] : '';
            $arrFieldValues['contact_email'] = !empty($_REQUEST['contact_email']) ? $_REQUEST['contact_email'] : '';

            //get keys of passed options selection
            if (!isset($this->arrCurrencies[$arrFieldValues['invoice_currency']]) && ($key = array_search(strtoupper($arrFieldValues['invoice_currency']), $this->arrCurrencies))) {
                $arrFieldValues['invoice_currency'] = $key;
            }
            if ((strtolower($arrFieldValues['contact_title']) !== self::MISTER) && (strtolower($arrFieldValues['contact_title']) !== self::MISS)) {
                if (ucfirst(strtolower($arrFieldValues['contact_title'])) == $_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE_MISTER']) {
                    $arrFieldValues['contact_title'] = self::MISTER;
                } elseif (ucfirst(strtolower($arrFieldValues['contact_title'])) == $_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE_MISS']) {
                    $arrFieldValues['contact_title'] = self::MISS;
                }
            } else {
                $arrFieldValues['contact_title'] = strtolower($arrFieldValues['contact_title']);
            }
            if (!isset($this->arrCountries[$arrFieldValues['contact_country']]) && ($key = array_search(ucfirst(strtolower($arrFieldValues['contact_country'])), $this->arrCountries))) {
                $arrFieldValues['contact_country'] = $key;
            }
        }

        //get currency options
        $arrSelectOptions['currencies'][] = '<option value="0">'.$_ARRAYLANG['TXT_CHECKOUT_INVOICE_CURRENCY'].$htmlRequiredField.'</option>';
        foreach ($this->arrCurrencies as $id => $currency) {
            $selected = ($id == $arrFieldValues['invoice_currency']) ? ' selected="selected"' : '';
            $arrSelectOptions['currencies'][] = '<option value="'.$id.'"'.$selected.'>'.contrexx_raw2xhtml($currency).'</option>';
        }

        //get title options
        $selectedMister = (self::MISTER == $arrFieldValues['contact_title']) ? ' selected="selected"' : '';
        $selectedMiss = (self::MISS == $arrFieldValues['contact_title']) ? ' selected="selected"' : '';
        $arrSelectOptions['titles'][] = '<option value="0">'.$_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE'].$htmlRequiredField.'</option>';
        $arrSelectOptions['titles'][] = '<option value="'.self::MISTER.'"'.$selectedMister.'>'.$_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE_MISTER'].'</option>';
        $arrSelectOptions['titles'][] = '<option value="'.self::MISS.'"'.$selectedMiss.'>'.$_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE_MISS'].'</option>';

        //get country options
        if (!empty($this->arrCountries)) {
            //$arrSelectOptions['countries'][] = '<option value="0">'.$_ARRAYLANG['TXT_CHECKOUT_CONTACT_COUNTRY'].$htmlRequiredField.'</option>';
            foreach ($this->arrCountries as $id => $name) {
                if (\Cx\Core\Country\Controller\Country::getAlpha2ById($id) != 'CH') {
                    continue;
                }
                $selected = $id == $arrFieldValues['contact_country'] ? ' selected="selected"' : '';
                $arrSelectOptions['countries'][] = '<option value="'.$id.'"'.$selected.'>'.contrexx_raw2xhtml($name).'</option>';
            }
        }

        // check wihch css classes have to be set
        foreach ($arrFieldValues as $name => $value) {
            if (isset($arrFieldsToHighlight[$name])) {
                $arrCssClasses[$name][] = $cssHighlightingClass;
            }
            if (empty($value)) {
                $arrCssClasses[$name][] = $cssLabelClass;
            }
            $arrCssClasses[$name] = implode(' ', $arrCssClasses[$name]);
        }

        \JS::activate('jquery');
        \JS::registerCode($this->getJavascript($htmlRequiredField));

        $this->objTemplate->setVariable(array(
            'TXT_CHECKOUT_DESCRIPTION'              => $_ARRAYLANG['TXT_CHECKOUT_DESCRIPTION'],
            'TXT_CHECKOUT_BILL_DATA'                => $_ARRAYLANG['TXT_CHECKOUT_BILL_DATA'],
            'TXT_CHECKOUT_CONTACT_DATA'             => $_ARRAYLANG['TXT_CHECKOUT_CONTACT_DATA'],
            'CHECKOUT_INVOICE_NUMBER'               => !empty($arrFieldValues['invoice_number']) ? $arrFieldValues['invoice_number'] : $_ARRAYLANG['TXT_CHECKOUT_INVOICE_NUMBER'].$htmlRequiredField,
            'CHECKOUT_INVOICE_CURRENCY_OPTIONS'     => !empty($arrSelectOptions['currencies']) ? implode($arrSelectOptions['currencies']) : '',
            'CHECKOUT_INVOICE_AMOUNT'               => !empty($arrFieldValues['invoice_amount']) ? $arrFieldValues['invoice_amount'] : $_ARRAYLANG['TXT_CHECKOUT_INVOICE_AMOUNT'].$htmlRequiredField,
            'CHECKOUT_CONTACT_TITLE_OPTIONS'        => !empty($arrSelectOptions['titles']) ? implode($arrSelectOptions['titles']) : '',
            'CHECKOUT_CONTACT_FORENAME'             => !empty($arrFieldValues['contact_forename']) ? $arrFieldValues['contact_forename'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_FORENAME'].$htmlRequiredField,
            'CHECKOUT_CONTACT_SURNAME'              => !empty($arrFieldValues['contact_surname']) ? $arrFieldValues['contact_surname'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_SURNAME'].$htmlRequiredField,
            'CHECKOUT_CONTACT_COMPANY'              => !empty($arrFieldValues['contact_company']) ? $arrFieldValues['contact_company'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_COMPANY'],
            'CHECKOUT_CONTACT_STREET'               => !empty($arrFieldValues['contact_street']) ? $arrFieldValues['contact_street'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_STREET'].$htmlRequiredField,
            'CHECKOUT_CONTACT_POSTCODE'             => !empty($arrFieldValues['contact_postcode']) ? $arrFieldValues['contact_postcode'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_POSTCODE'].$htmlRequiredField,
            'CHECKOUT_CONTACT_PLACE'                => !empty($arrFieldValues['contact_place']) ? $arrFieldValues['contact_place'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_PLACE'].$htmlRequiredField,
            'CHECKOUT_CONTACT_COUNTRY_OPTIONS'      => !empty($arrSelectOptions['countries']) ? implode($arrSelectOptions['countries']) : '',
            'CHECKOUT_CONTACT_PHONE'                => !empty($arrFieldValues['contact_phone']) ? $arrFieldValues['contact_phone'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_PHONE'].$htmlRequiredField,
            'CHECKOUT_CONTACT_EMAIL'                => !empty($arrFieldValues['contact_email']) ? $arrFieldValues['contact_email'] : $_ARRAYLANG['TXT_CHECKOUT_CONTACT_EMAIL'].$htmlRequiredField,
            'CHECKOUT_INVOICE_NUMBER_CLASS'         => $arrCssClasses['invoice_number'],
            'CHECKOUT_INVOICE_CURRENCY_CLASS'       => $arrCssClasses['invoice_currency'],
            'CHECKOUT_INVOICE_AMOUNT_CLASS'         => $arrCssClasses['invoice_amount'],
            'CHECKOUT_CONTACT_TITLE_CLASS'          => $arrCssClasses['contact_title'],
            'CHECKOUT_CONTACT_FORENAME_CLASS'       => $arrCssClasses['contact_forename'],
            'CHECKOUT_CONTACT_SURNAME_CLASS'        => $arrCssClasses['contact_surname'],
            'CHECKOUT_CONTACT_COMPANY_CLASS'        => $arrCssClasses['contact_company'],
            'CHECKOUT_CONTACT_STREET_CLASS'         => $arrCssClasses['contact_street'],
            'CHECKOUT_CONTACT_POSTCODE_CLASS'       => $arrCssClasses['contact_postcode'],
            'CHECKOUT_CONTACT_PLACE_CLASS'          => $arrCssClasses['contact_place'],
            'CHECKOUT_CONTACT_COUNTRY_CLASS'        => $arrCssClasses['contact_country'],
            'CHECKOUT_CONTACT_PHONE_CLASS'          => $arrCssClasses['contact_phone'],
            'CHECKOUT_CONTACT_EMAIL_CLASS'          => $arrCssClasses['contact_email'],
            'TXT_CORE_SUBMIT'                       => $_CORELANG['TXT_CORE_SUBMIT'],
            'TXT_CORE_RESET'                        => $_CORELANG['TXT_CORE_RESET'],
        ));

        $this->objTemplate->hideBlock('redirect');
        $this->objTemplate->parse('form');
    }

    /**
     * Validate user input data.
     *
     * @access      private
     * @param       array       $arrUserData            user input data from submitted form
     * @return      array       $arrFieldsToHighlight   contains all fields which need to be highlighted
     */
    private function validateUserData($arrUserData)
    {
        global $_ARRAYLANG;

        $arrFieldsToHighlight = array();

        foreach ($arrUserData['numeric'] as $key => $field) {
            if (!empty($field['mandatory'])) {
                if (empty($field['value'])) {
                    $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_EMPTY'];
                    $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                    $this->arrStatusMessages['error'][] = $msg;
                    $arrFieldsToHighlight[$key] = '';
                    continue;
                }
            }
            if (strlen($field['value']) > $field['length']) {
                $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_LENGTH_EXCEEDED'];
                $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                $msg = str_replace('{MAX_LENGTH}', $field['length'], $msg);
                $this->arrStatusMessages['error'][] = $msg;
                $arrFieldsToHighlight[$key] = '';
                continue;
            }
            if (!empty($field['value']) && !is_numeric($field['value'])) {
                $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_NOT_NUMERIC'];
                $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                $this->arrStatusMessages['error'][] = $msg;
                $arrFieldsToHighlight[$key] = '';
                continue;
            }
            if (!empty($field['value']) && ($field['value'] < 1)) {
                $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_NOT_POSITIVE'];
                $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                $this->arrStatusMessages['error'][] = $msg;
                $arrFieldsToHighlight[$key] = '';
                continue;
            }
        }

        foreach ($arrUserData['text'] as $key => $field) {
            if (!empty($field['mandatory'])) {
                if (empty($field['value'])) {
                    $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_EMPTY'];
                    $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                    $this->arrStatusMessages['error'][] = $msg;
                    $arrFieldsToHighlight[$key] = '';
                    continue;
                }
            }
            if (strlen($field['value']) > $field['length']) {
                $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_LENGTH_EXCEEDED'];
                $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                $msg = str_replace('{MAX_LENGTH}', $field['length'], $msg);
                $this->arrStatusMessages['error'][] = $msg;
                $arrFieldsToHighlight[$key] = '';
                continue;
            }
        }

        foreach ($arrUserData['selection'] as $key => $field) {
            if (!empty($field['mandatory'])) {
                if (empty($field['value'])) {
                    $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_SELECTION_EMPTY'];
                    $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                    $this->arrStatusMessages['error'][] = $msg;
                    $arrFieldsToHighlight[$key] = '';
                    continue;
                }
            }
            if (!empty($field['value']) && !isset($field['options'][$field['value']])) {
                $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_SELECTION_INVALID_OPTION'];
                $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                $this->arrStatusMessages['error'][] = $msg;
                $arrFieldsToHighlight[$key] = '';
                continue;
            }
        }

        foreach ($arrUserData['email'] as $key => $field) {
            if (!empty($field['mandatory'])) {
                if (empty($field['value'])) {
                    $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_EMPTY'];
                    $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                    $this->arrStatusMessages['error'][] = $msg;
                    $arrFieldsToHighlight[$key] = '';
                    continue;
                }
            }
            if (strlen($field['value']) > $field['length']) {
                $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_FIELD_LENGTH_EXCEEDED'];
                $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                $msg = str_replace('{MAX_LENGTH}', $field['length'], $msg);
                $this->arrStatusMessages['error'][] = $msg;
                $arrFieldsToHighlight[$key] = '';
                continue;
            }
            if (!empty($field['value']) && !\FWValidator::isEmail($field['value'])) {
                $msg = $_ARRAYLANG['TXT_CHECKOUT_VALIDATION_INVALID_EMAIL'];
                $msg = str_replace('{FIELD_NAME}', $field['name'], $msg);
                $msg = str_replace('{MAX_LENGTH}', $field['length'], $msg);
                $this->arrStatusMessages['error'][] = $msg;
                $arrFieldsToHighlight[$key] = '';
                continue;
            }
        }

        return $arrFieldsToHighlight;
    }

    /**
     * Returns the javascript code for the form.
     *
     * @access      private
     * @param       array       $htmlRequiredField      html code for required fields
     * @return      string      javascript code
     */
    private function getJavascript($htmlRequiredField)
    {
        global $_ARRAYLANG;

        $javascript = "
            arrLabels = new Array();
            arrLabels['invoice_number'] = '".$_ARRAYLANG['TXT_CHECKOUT_INVOICE_NUMBER'].$htmlRequiredField."';
            arrLabels['invoice_amount'] = '".$_ARRAYLANG['TXT_CHECKOUT_INVOICE_AMOUNT'].$htmlRequiredField."';
            arrLabels['invoice_currency'] = '".$_ARRAYLANG['TXT_CHECKOUT_INVOICE_CURRENCY'].$htmlRequiredField."';
            arrLabels['contact_title'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE'].$htmlRequiredField."';
            arrLabels['contact_forename'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_FORENAME'].$htmlRequiredField."';
            arrLabels['contact_surname'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_SURNAME'].$htmlRequiredField."';
            arrLabels['contact_company'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_COMPANY']."';
            arrLabels['contact_street'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_STREET'].$htmlRequiredField."';
            arrLabels['contact_postcode'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_POSTCODE'].$htmlRequiredField."';
            arrLabels['contact_place'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_PLACE'].$htmlRequiredField."';
            arrLabels['contact_country'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_COUNTRY'].$htmlRequiredField."';
            arrLabels['contact_phone'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_PHONE'].$htmlRequiredField."';
            arrLabels['contact_email'] = '".$_ARRAYLANG['TXT_CHECKOUT_CONTACT_EMAIL'].$htmlRequiredField."';

            cx.jQuery(document).ready(function() {
                cx.jQuery('#checkout input[type=text]').focus(function() {
                    if (cx.jQuery(this).val() == arrLabels[cx.jQuery(this).attr('name')]) {
                        cx.jQuery(this).val('');
                        cx.jQuery(this).removeClass('label');
                    }
                });

                cx.jQuery('#checkout input[type=text]').blur(function() {
                    if (cx.jQuery(this).val() == '') {
                        cx.jQuery(this).val(arrLabels[cx.jQuery(this).attr('name')]);
                        cx.jQuery(this).addClass('label');
                    } else if (cx.jQuery(this).val() == arrLabels[cx.jQuery(this).attr('name')]) {
                        cx.jQuery(this).addClass('label');
                    }
                });
            });
        ";

        return $javascript;
    }

    /**
     * Evaluate and register the payment result.
     * If the transaction was successful an email will be sent to the customer and administrator.
     *
     * @access      private
     */
    private function registerPaymentResult()
    {
        global $_ARRAYLANG, $_CONFIG, $objDatabase;

        $objSettingsYellowpay = new SettingsYellowpay($objDatabase);
        $arrYellowpay = $objSettingsYellowpay->get();

        //evaluate payment result
        $status = '';
        $orderId = \Yellowpay::getOrderId();
        $arrTransaction = $this->objTransaction->get(array($orderId));

        if (\Yellowpay::checkin($arrYellowpay['sha_out'])) {
            if (abs($_REQUEST['result']) == 1) {
                $status = self::CONFIRMED;

                if (($arrTransaction[0]['status'] == self::WAITING) || ($arrTransaction[0]['status'] == $status)) {
                    $this->arrStatusMessages['ok'][] = $_ARRAYLANG['TXT_CHECKOUT_TRANSACTION_WAS_SUCCESSFUL'];
                }

                if ($arrTransaction[0]['status'] == $status) {
                    return;
                }
            } else if (($_REQUEST['result'] == 0) || (abs($_REQUEST['result']) == 2)) {
                $status = self::CANCELLED;

                if (($arrTransaction[0]['status'] == self::WAITING) || ($arrTransaction[0]['status'] == $status)) {
                    $this->arrStatusMessages['error'][] = $_ARRAYLANG['TXT_CHECKOUT_TRANSACTION_WAS_CANCELLED'];
                }

                if ($arrTransaction[0]['status'] == $status) {
                    return;
                }
            } else {
                $this->arrStatusMessages['error'][] = $_ARRAYLANG['TXT_CHECKOUT_INVALID_TRANSACTION_STATUS'];
                return;
            }
        } else {
            $this->arrStatusMessages['error'][] = $_ARRAYLANG['TXT_CHECKOUT_SECURITY_CHECK_ERROR'];
            return;
        }

        if ($arrTransaction[0]['status'] == self::WAITING) {
            //update transaction status
            $this->objTransaction->updateStatus($orderId, $status);

            //send confirmation email (if the payment was successful)
            if ($status == self::CONFIRMED) {
                $arrTransaction = $this->objTransaction->get(array($orderId));

                if (!empty($arrTransaction[0])) {
                    //prepare transaction data for output
                    $arrTransaction[0]['time'] = date('j.n.Y G:i:s', $arrTransaction[0]['time']);
                    switch ($arrTransaction[0]['status']) {
                        case self::WAITING:
                            $arrTransaction[0]['status'] = $_ARRAYLANG['TXT_CHECKOUT_STATUS_WAITING'];
                            break;
                        case self::CONFIRMED:
                            $arrTransaction[0]['status'] = $_ARRAYLANG['TXT_CHECKOUT_STATUS_CONFIRMED'];
                            break;
                        case self::CANCELLED:
                            $arrTransaction[0]['status'] = $_ARRAYLANG['TXT_CHECKOUT_STATUS_CANCELLED'];
                            break;
                    }
                    $arrTransaction[0]['invoice_currency'] = $this->arrCurrencies[$arrTransaction[0]['invoice_currency']];
                    $arrTransaction[0]['invoice_amount'] = number_format($arrTransaction[0]['invoice_amount'], 2, '.', '\'');
                    switch ($arrTransaction[0]['contact_title']) {
                        case self::MISTER:
                            $arrTransaction[0]['contact_title'] = $_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE_MISTER'];
                            break;
                        case self::MISS:
                            $arrTransaction[0]['contact_title'] = $_ARRAYLANG['TXT_CHECKOUT_CONTACT_TITLE_MISS'];
                            break;
                    }

                    //get mail templates
                    $objSettingsMail = new SettingsMails($objDatabase);
                    $arrAdminMail = $objSettingsMail->getAdminMail();
                    $arrCustomerMail = $objSettingsMail->getCustomerMail();

                    //fill up placeholders in mail templates
                    $arrPlaceholders = array(
                        'DOMAIN_URL'            => ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET,
                        'TRANSACTION_ID'        => $arrTransaction[0]['id'],
                        'TRANSACTION_TIME'      => $arrTransaction[0]['time'],
                        'TRANSACTION_STATUS'    => $arrTransaction[0]['status'],
                        'INVOICE_NUMBER'        => $arrTransaction[0]['invoice_number'],
                        'INVOICE_CURRENCY'      => $arrTransaction[0]['invoice_currency'],
                        'INVOICE_AMOUNT'        => $arrTransaction[0]['invoice_amount'],
                        'CONTACT_TITLE'         => $arrTransaction[0]['contact_title'],
                        'CONTACT_FORENAME'      => $arrTransaction[0]['contact_forename'],
                        'CONTACT_SURNAME'       => $arrTransaction[0]['contact_surname'],
                        'CONTACT_COMPANY'       => $arrTransaction[0]['contact_company'],
                        'CONTACT_STREET'        => $arrTransaction[0]['contact_street'],
                        'CONTACT_POSTCODE'      => $arrTransaction[0]['contact_postcode'],
                        'CONTACT_PLACE'         => $arrTransaction[0]['contact_place'],
                        'CONTACT_COUNTRY'       => $arrTransaction[0]['contact_country'],
                        'CONTACT_PHONE'         => $arrTransaction[0]['contact_phone'],
                        'CONTACT_EMAIL'         => $arrTransaction[0]['contact_email'],
                    );
                    foreach ($arrPlaceholders as $placeholder => $value) {
                        $arrAdminMail['title']      = str_replace('[['.$placeholder.']]', contrexx_raw2xhtml($value), $arrAdminMail['title']);
                        $arrAdminMail['content']    = str_replace('[['.$placeholder.']]', contrexx_raw2xhtml($value), $arrAdminMail['content']);
                        $arrCustomerMail['title']   = str_replace('[['.$placeholder.']]', contrexx_raw2xhtml($value), $arrCustomerMail['title']);
                        $arrCustomerMail['content'] = str_replace('[['.$placeholder.']]', contrexx_raw2xhtml($value), $arrCustomerMail['content']);
                    }

                    //send mail to administrator and customer
                    $this->sendConfirmationMail($_CONFIG['contactFormEmail'], $arrAdminMail);
                    $this->sendConfirmationMail($arrTransaction[0]['contact_email'], $arrCustomerMail);
                }
            }

            exit();
        }
    }

    /**
     * Send confirmation email.
     *
     * @access      private
     * @param       string      $recipient      recipient
     * @param       array       $arrMail        title and content
     */
    private function sendConfirmationMail($recipient, $arrMail)
    {
        global $_ARRAYLANG, $_CONFIG;

        $objPHPMailer = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $objPHPMailer->IsHTML(true);
        $objPHPMailer->Subject = $arrMail['title'];
        $objPHPMailer->SetFrom($_CONFIG['contactFormEmail'], $_CONFIG['domainUrl']);
        $objPHPMailer->AddAddress($recipient);
        $objPHPMailer->Body = $arrMail['content'];
        $objPHPMailer->Send();
    }

}
